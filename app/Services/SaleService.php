<?php

namespace App\Services;

use App\Enums\DebtEntryType;
use App\Helpers\AuditLogger;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    protected DebtLedgerService $debtLedgerService;

    public function __construct(DebtLedgerService $debtLedgerService)
    {
        $this->debtLedgerService = $debtLedgerService;
    }

    public function completeSale(User $user, array $payload): Sale
    {
        return DB::transaction(function () use ($user, $payload) {
            $businessId = $user->business_id;
            $items = $payload['items'];
            $paymentMethod = $payload['payment_method'] ?? 'cash';
            $isCreditSale = (bool) ($payload['is_credit_sale'] ?? false);
            $customerId = $payload['customer_id'] ?? null;

            if ($isCreditSale && ! $customerId) {
                throw ValidationException::withMessages([
                    'customer_id' => 'A customer is required for credit sales.',
                ]);
            }

            $subtotal = 0;
            $lineItems = [];

            foreach ($items as $item) {
                $product = Product::where('business_id', $businessId)
                    ->where('id', $item['product_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $quantity = (float) $item['quantity'];
                $unitPrice = (float) ($item['unit_price'] ?? $product->price);
                $lineSubtotal = round($quantity * $unitPrice, 2);
                $subtotal += $lineSubtotal;

                if ($product->stock_quantity < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => "Insufficient stock for {$product->name}. Available: {$product->stock_quantity}",
                    ]);
                }

                $lineItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $lineSubtotal,
                ];
            }

            $taxAmount = (float) ($payload['tax_amount'] ?? 0);
            $discountAmount = (float) ($payload['discount_amount'] ?? 0);
            $total = round($subtotal + $taxAmount - $discountAmount, 2);

            if ($isCreditSale) {
                $customer = Customer::where('business_id', $businessId)
                    ->where('id', $customerId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (($customer->outstanding_balance + $total) > $customer->credit_limit) {
                    throw ValidationException::withMessages([
                        'customer_id' => 'Credit limit exceeded for this customer.',
                    ]);
                }
            }

            $saleNumber = $this->generateSaleNumber($businessId);

            $sale = Sale::create([
                'business_id' => $businessId,
                'user_id' => $user->id,
                'customer_id' => $customerId,
                'sale_number' => $saleNumber,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total' => $total,
                'payment_method' => $isCreditSale ? 'credit' : $paymentMethod,
                'is_credit_sale' => $isCreditSale,
                'status' => 'completed',
                'notes' => $payload['notes'] ?? null,
                'completed_at' => Carbon::now(),
            ]);

            foreach ($lineItems as $line) {
                /** @var Product $product */
                $product = $line['product'];
                $oldStock = $product->stock_quantity;

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'product_name' => $product->displayName(),
                    'sku' => $product->sku,
                    'variant_attributes' => $product->attribute_values ?? $product->variant_attributes,
                    'measurement_unit' => $product->measurement_unit,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'discount_amount' => 0,
                    'subtotal' => $line['subtotal'],
                ]);

                $product->decrement('stock_quantity', $line['quantity']);

                AuditLogger::record(
                    'stock_decremented',
                    $product->fresh(),
                    ['stock_quantity' => $oldStock],
                    ['stock_quantity' => $product->fresh()->stock_quantity, 'sale_id' => $sale->id],
                    $businessId,
                    $user->id
                );
            }

            if ($isCreditSale) {
                $this->debtLedgerService->recordDebit(
                    Customer::find($customerId),
                    $total,
                    $user,
                    $sale,
                    'Hardware credit sale #' . $saleNumber
                );
            }

            AuditLogger::record(
                'sale_completed',
                $sale,
                null,
                $sale->toArray(),
                $businessId,
                $user->id
            );

            return $sale->load('items');
        });
    }

    protected function generateSaleNumber(int $businessId): string
    {
        $count = Sale::where('business_id', $businessId)->count() + 1;

        return 'SALE-' . str_pad((string) $businessId, 3, '0', STR_PAD_LEFT) . '-' . str_pad((string) $count, 6, '0', STR_PAD_LEFT);
    }
}
