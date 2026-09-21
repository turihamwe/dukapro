<?php

namespace App\Services;

use App\Models\Business;
use Illuminate\Support\Facades\Log;

class BusinessActivityService
{
    protected const THROTTLE_MINUTES = 15;

    public function record(?Business $business, bool $force = false): void
    {
        if (! $business) {
            return;
        }

        if (! $force && $this->recentlyRecorded($business)) {
            return;
        }

        try {
            $business->update(['last_active_at' => now()]);
        } catch (\Throwable $exception) {
            Log::warning('Failed to update business last_active_at', [
                'business_id' => $business->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function recordFromId(?int $businessId, bool $force = false): void
    {
        if (! $businessId) {
            return;
        }

        $business = Business::query()->find($businessId);
        $this->record($business, $force);
    }

    protected function recentlyRecorded(Business $business): bool
    {
        if (! $business->last_active_at) {
            return false;
        }

        return $business->last_active_at->gt(now()->subMinutes(self::THROTTLE_MINUTES));
    }
}
