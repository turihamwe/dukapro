<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\User;
use App\Support\SaleDocument;
use Illuminate\Validation\ValidationException;

class PosOfflineSyncService
{
    protected SaleService $saleService;

    protected KitchenOrderService $kitchenOrderService;

    public function __construct(SaleService $saleService, KitchenOrderService $kitchenOrderService)
    {
        $this->saleService = $saleService;
        $this->kitchenOrderService = $kitchenOrderService;
    }

    public function syncBatch(User $user, array $sales): array
    {
        $business = $user->business;
        $acknowledged = [];
        $failed = [];

        foreach ($sales as $entry) {
            $localId = (string) ($entry['local_id'] ?? '');
            $payload = $entry['payload'] ?? null;

            if ($localId === '' || ! is_array($payload)) {
                $failed[] = [
                    'local_id' => $localId ?: null,
                    'message' => 'Invalid offline sale payload.',
                ];
                continue;
            }

            $existing = Sale::query()
                ->where('business_id', $user->business_id)
                ->where('offline_local_id', $localId)
                ->first();

            if ($existing) {
                $acknowledged[] = $this->ackEntry($existing, $localId);

                continue;
            }

            try {
                $payload['offline_local_id'] = $localId;
                $sale = $this->saleService->completeSale($user, $payload);

                if ($business && $business->usesRestaurantMode()) {
                    $this->kitchenOrderService->recordCounterSaleOrder($user, $sale, $payload);
                    $sale = $sale->fresh(['items']);
                }

                $acknowledged[] = $this->ackEntry($sale->fresh(['customer', 'items']), $localId);
            } catch (ValidationException $exception) {
                $failed[] = [
                    'local_id' => $localId,
                    'message' => collect($exception->errors())->flatten()->first() ?: 'Validation failed.',
                ];
            } catch (\Throwable $exception) {
                $failed[] = [
                    'local_id' => $localId,
                    'message' => $exception->getMessage(),
                ];
            }
        }

        return [
            'acknowledged' => $acknowledged,
            'failed' => $failed,
        ];
    }

    protected function ackEntry(Sale $sale, string $localId): array
    {
        return [
            'local_id' => $localId,
            'sale_id' => $sale->id,
            'sale_number' => $sale->sale_number,
            'receipt_url' => SaleDocument::receiptUrl($sale),
        ];
    }
}
