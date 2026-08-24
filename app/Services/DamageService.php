<?php

namespace App\Services;

use App\Enums\DamageReason;
use App\Helpers\AuditLogger;
use App\Models\Damage;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DamageService
{
    public function record(User $user, array $data): Damage
    {
        return DB::transaction(function () use ($user, $data) {
            $businessId = $user->business_id;
            $quantity = (float) $data['quantity'];

            $product = Product::where('business_id', $businessId)
                ->where('id', $data['product_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($quantity <= 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Quantity must be greater than zero.',
                ]);
            }

            if ($product->stock_quantity < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "Insufficient stock for {$product->name}. Available: {$product->stock_quantity}",
                ]);
            }

            $oldStock = $product->stock_quantity;
            $costPrice = (float) ($product->cost_price ?? 0);
            $damageDate = Carbon::parse($data['damage_date'] ?? today());

            $damage = Damage::create([
                'business_id' => $businessId,
                'product_id' => $product->id,
                'user_id' => $user->id,
                'quantity' => $quantity,
                'reason' => $data['reason'],
                'cost_price' => $costPrice,
                'damage_date' => $damageDate->toDateString(),
            ]);

            $product->decrement('stock_quantity', $quantity);

            AuditLogger::record(
                'stock_damaged',
                $product->fresh(),
                ['stock_quantity' => $oldStock],
                [
                    'stock_quantity' => $product->fresh()->stock_quantity,
                    'damage_id' => $damage->id,
                    'quantity' => $quantity,
                    'reason' => $data['reason'],
                    'loss_value' => $damage->lossValue(),
                ],
                $businessId,
                $user->id
            );

            AuditLogger::record(
                'damage_recorded',
                $damage,
                null,
                $damage->load('product')->toArray(),
                $businessId,
                $user->id
            );

            return $damage->load('product', 'user');
        });
    }

    public function summarizeForDate(int $businessId, Carbon $date): array
    {
        $damages = Damage::with('product', 'user')
            ->where('business_id', $businessId)
            ->whereDate('damage_date', $date)
            ->orderByDesc('created_at')
            ->get();

        $totalLoss = $damages->sum(function (Damage $damage) {
            return $damage->lossValue();
        });

        return [
            'total_items' => round($damages->sum('quantity'), 3),
            'total_loss' => round($totalLoss, 2),
            'entry_count' => $damages->count(),
            'entries' => $damages,
        ];
    }

    public static function reasonOptions(): array
    {
        return DamageReason::labels();
    }
}
