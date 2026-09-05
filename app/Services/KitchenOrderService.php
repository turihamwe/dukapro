<?php

namespace App\Services;

use App\Enums\KitchenOrderStatus;
use App\Helpers\AuditLogger;
use App\Models\Branch;
use App\Models\KitchenOrder;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KitchenOrderService
{
    public function placeOrder(User $user, array $payload): KitchenOrder
    {
        $business = $user->business;

        if (! $business || ! $business->usesRestaurantMode()) {
            throw ValidationException::withMessages([
                'items' => 'Restaurant mode is not enabled for this business.',
            ]);
        }

        $items = $payload['items'] ?? [];

        if (empty($items)) {
            throw ValidationException::withMessages([
                'items' => 'Add at least one menu item.',
            ]);
        }

        return DB::transaction(function () use ($user, $business, $payload, $items) {
            $lineItems = $this->buildLineItems($business->id, $items);
            $subtotal = round(collect($lineItems)->sum('subtotal'), 2);
            $waiterId = $user->isWaiter()
                ? $user->id
                : (int) ($payload['waiter_id'] ?? $user->id);
            $branchId = $this->resolveBranchId($user);
            $tableMeta = app(RestaurantTableService::class)->resolveForOrder($user, $business, $payload);

            $order = KitchenOrder::create([
                'business_id' => $business->id,
                'branch_id' => $branchId,
                'order_number' => $this->generateOrderNumber($business->id),
                'waiter_id' => $waiterId,
                'placed_by_user_id' => $user->id,
                'restaurant_table_id' => $tableMeta['restaurant_table_id'],
                'table_label' => $tableMeta['table_label'],
                'status' => KitchenOrderStatus::PENDING_KITCHEN,
                'subtotal' => $subtotal,
                'notes' => $payload['notes'] ?? null,
                'placed_at' => Carbon::now(),
            ]);

            foreach ($lineItems as $line) {
                $order->items()->create($line);
            }

            AuditLogger::record(
                'kitchen_order_placed',
                $order,
                null,
                $order->load('items')->toArray(),
                $business->id,
                $user->id
            );

            return $order->load(['items', 'waiter']);
        });
    }

    public function listForKitchen(User $user, ?array $statuses = null): Collection
    {
        $statuses = $statuses ?? KitchenOrderStatus::active();

        return KitchenOrder::query()
            ->with(['items', 'waiter'])
            ->where('business_id', $user->business_id)
            ->whereIn('status', $statuses)
            ->orderBy('placed_at')
            ->get();
    }

    public function listReadyForPayment(User $user): Collection
    {
        return KitchenOrder::query()
            ->with(['items', 'waiter', 'restaurantTable'])
            ->where('business_id', $user->business_id)
            ->whereIn('status', KitchenOrderStatus::active())
            ->whereNull('sale_id')
            ->orderBy('placed_at')
            ->get();
    }

    public function listForCashier(User $user): Collection
    {
        return KitchenOrder::query()
            ->with(['items', 'waiter', 'restaurantTable', 'sale'])
            ->where('business_id', $user->business_id)
            ->whereDate('placed_at', Carbon::today())
            ->orderByDesc('placed_at')
            ->get();
    }

    public function advanceStatus(User $user, KitchenOrder $order, string $newStatus): KitchenOrder
    {
        $this->assertKitchenAccess($user, $order);

        if (! KitchenOrderStatus::canTransition($order->status, $newStatus)) {
            throw ValidationException::withMessages([
                'status' => 'This order cannot move to ' . KitchenOrderStatus::label($newStatus) . '.',
            ]);
        }

        $order->status = $newStatus;

        if ($newStatus === KitchenOrderStatus::PREPARING) {
            $order->preparing_at = Carbon::now();
        }

        if ($newStatus === KitchenOrderStatus::READY) {
            $order->ready_at = Carbon::now();

            if ($order->sale_id) {
                $order->status = KitchenOrderStatus::COMPLETED;
                $order->completed_at = Carbon::now();
                $order->save();

                AuditLogger::record(
                    'kitchen_order_status',
                    $order,
                    null,
                    ['status' => KitchenOrderStatus::COMPLETED, 'auto_completed' => true],
                    $order->business_id,
                    $user->id
                );

                return $order->fresh(['items', 'waiter']);
            }
        }

        if ($newStatus === KitchenOrderStatus::CANCELLED) {
            $order->completed_at = Carbon::now();
        }

        $order->status = $newStatus;
        $order->save();

        AuditLogger::record(
            'kitchen_order_status',
            $order,
            null,
            ['status' => $newStatus],
            $order->business_id,
            $user->id
        );

        return $order->fresh(['items', 'waiter']);
    }

    public function settleOrder(User $cashier, KitchenOrder $order, array $payment): Sale
    {
        if ((int) $order->business_id !== (int) $cashier->business_id) {
            abort(404);
        }

        if (! in_array($order->status, KitchenOrderStatus::active(), true)) {
            throw ValidationException::withMessages([
                'status' => 'This order cannot be settled.',
            ]);
        }

        if ($order->sale_id) {
            throw ValidationException::withMessages([
                'status' => 'This order has already been settled.',
            ]);
        }

        $payload = [
            'items' => $order->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'notes' => $item->notes,
            ])->all(),
            'payment_method' => $payment['payment_method'] ?? 'cash',
            'is_credit_sale' => (bool) ($payment['is_credit_sale'] ?? false),
            'customer_id' => $payment['customer_id'] ?? null,
            'waiter_id' => $order->waiter_id,
            'mobile_money_provider' => $payment['mobile_money_provider'] ?? null,
            'notes' => trim(($order->notes ? $order->notes . ' · ' : '') . 'Table ' . ($order->table_label ?: '—') . ' · ' . $order->order_number),
            'kitchen_order_id' => $order->id,
        ];

        return DB::transaction(function () use ($cashier, $order, $payload) {
            $sale = app(SaleService::class)->completeSale($cashier, $payload);

            $updates = [
                'sale_id' => $sale->id,
            ];

            if ($order->status === KitchenOrderStatus::READY) {
                $updates['status'] = KitchenOrderStatus::COMPLETED;
                $updates['completed_at'] = Carbon::now();
            }

            $order->update($updates);

            return $sale->load('items');
        });
    }

    public function recordCounterSaleOrder(User $user, Sale $sale, array $payload = []): KitchenOrder
    {
        $business = $user->business;

        if (! $business || ! $business->usesRestaurantMode()) {
            throw ValidationException::withMessages([
                'items' => 'Restaurant mode is not enabled for this business.',
            ]);
        }

        if ($sale->kitchen_order_id) {
            return KitchenOrder::query()
                ->with(['items', 'waiter', 'sale'])
                ->findOrFail($sale->kitchen_order_id);
        }

        return DB::transaction(function () use ($user, $business, $sale, $payload) {
            $branchId = $this->resolveBranchId($user);
            $tableMeta = app(RestaurantTableService::class)->resolveForOrder($user, $business, $payload);
            $completedAt = $sale->completed_at ?? Carbon::now();

            $order = KitchenOrder::create([
                'business_id' => $business->id,
                'branch_id' => $branchId,
                'order_number' => $this->generateOrderNumber($business->id),
                'waiter_id' => $sale->waiter_id ?? $user->id,
                'placed_by_user_id' => $user->id,
                'restaurant_table_id' => $tableMeta['restaurant_table_id'],
                'table_label' => $tableMeta['table_label'],
                'status' => KitchenOrderStatus::COMPLETED,
                'subtotal' => $sale->subtotal,
                'notes' => $payload['notes'] ?? $sale->notes,
                'sale_id' => $sale->id,
                'placed_at' => $completedAt,
                'completed_at' => $completedAt,
            ]);

            foreach ($sale->items as $item) {
                $order->items()->create([
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'sku' => $item->sku,
                    'measurement_unit' => $item->measurement_unit,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                    'notes' => $item->notes,
                ]);
            }

            $sale->update(['kitchen_order_id' => $order->id]);

            AuditLogger::record(
                'kitchen_order_counter_sale',
                $order,
                null,
                $order->load('items')->toArray(),
                $business->id,
                $user->id
            );

            return $order->load(['items', 'waiter', 'sale']);
        });
    }

    protected function buildLineItems(int $businessId, array $items): array
    {
        $lineItems = [];

        foreach ($items as $item) {
            $product = Product::query()
                ->where('business_id', $businessId)
                ->where('id', $item['product_id'])
                ->where('is_active', true)
                ->where('is_sellable', true)
                ->first();

            if (! $product) {
                throw ValidationException::withMessages([
                    'items' => 'One or more menu items are unavailable.',
                ]);
            }

            $quantity = (float) ($item['quantity'] ?? 0);

            if ($quantity <= 0) {
                continue;
            }

            $unitPrice = $product->price;
            $notes = isset($item['notes']) ? trim((string) $item['notes']) : null;
            $lineItems[] = [
                'product_id' => $product->id,
                'product_name' => $product->displayName(),
                'sku' => $product->sku,
                'measurement_unit' => $product->measurement_unit,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => round($unitPrice * $quantity, 2),
                'notes' => $notes !== '' ? mb_substr($notes, 0, 500) : null,
            ];
        }

        if (empty($lineItems)) {
            throw ValidationException::withMessages([
                'items' => 'Add at least one menu item.',
            ]);
        }

        return $lineItems;
    }

    protected function assertKitchenAccess(User $user, KitchenOrder $order): void
    {
        if ((int) $order->business_id !== (int) $user->business_id) {
            abort(404);
        }

        if ($user->isBranchScoped() && $user->branch_id && (int) $order->branch_id !== (int) $user->branch_id) {
            abort(403);
        }
    }

    protected function generateOrderNumber(int $businessId): string
    {
        $count = KitchenOrder::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->count() + 1;

        return 'ORD-' . str_pad((string) $businessId, 3, '0', STR_PAD_LEFT) . '-' . str_pad((string) $count, 6, '0', STR_PAD_LEFT);
    }

    protected function resolveBranchId(User $user): int
    {
        if ($user->branch_id) {
            return (int) $user->branch_id;
        }

        $branch = Branch::query()
            ->where('business_id', $user->business_id)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();

        if (! $branch) {
            throw ValidationException::withMessages([
                'branch_id' => 'No active branch found for this business.',
            ]);
        }

        return (int) $branch->id;
    }
}
