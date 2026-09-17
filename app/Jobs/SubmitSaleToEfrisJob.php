<?php

namespace App\Jobs;

use App\Models\Sale;
use App\Services\EfrisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SubmitSaleToEfrisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;

    public function __construct(protected int $saleId)
    {
        $this->tries = (int) config('efris.job.tries', 3);
    }

    public function backoff(): array
    {
        return config('efris.job.backoff_seconds', [60, 300, 900]);
    }

    public function handle(EfrisService $efrisService): void
    {
        $sale = Sale::query()
            ->withoutGlobalScopes()
            ->with(['items', 'business.efrisSetting', 'customer', 'user'])
            ->find($this->saleId);

        if (! $sale || ! $sale->business || ! $sale->business->usesEfris()) {
            return;
        }

        if ($sale->efris_status === 'success') {
            return;
        }

        try {
            $result = $efrisService->submitFiscalReceipt($sale);

            $sale->update([
                'efris_fdn' => $result['fdn'],
                'efris_antifake_code' => $result['antifake_code'],
                'efris_qr_code' => $result['qr_code'],
                'efris_status' => 'success',
                'efris_error' => null,
                'efris_submitted_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('EFRIS fiscal receipt submission failed', [
                'sale_id' => $sale->id,
                'business_id' => $sale->business_id,
                'message' => $e->getMessage(),
            ]);

            $sale->update([
                'efris_status' => 'failed',
                'efris_error' => Str::limit($e->getMessage(), 1000),
            ]);

            throw $e;
        }
    }
}
