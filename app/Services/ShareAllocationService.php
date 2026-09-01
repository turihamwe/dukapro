<?php

namespace App\Services;

use App\Enums\ShareholderStatus;
use App\Models\Shareholder;
use Illuminate\Validation\ValidationException;

class ShareAllocationService
{
    public function totalShares(): float
    {
        return (float) config('shareholders.total_shares', 100);
    }

    public function pricePerShare(): float
    {
        return (float) config('shareholders.price_per_share', 1000000);
    }

    public function maxShareholders(): int
    {
        return (int) config('shareholders.max_shareholders', 100);
    }

    public function allocatedShares(?int $excludeShareholderId = null): float
    {
        $query = Shareholder::query()
            ->whereIn('status', ShareholderStatus::allocated());

        if ($excludeShareholderId) {
            $query->where('id', '!=', $excludeShareholderId);
        }

        return (float) $query->sum('shares_owned');
    }

    public function remainingShares(?int $excludeShareholderId = null): float
    {
        return max(0, round($this->totalShares() - $this->allocatedShares($excludeShareholderId), 4));
    }

    public function activeShareholderCount(?int $excludeShareholderId = null): int
    {
        $query = Shareholder::query()
            ->whereIn('status', ShareholderStatus::allocated());

        if ($excludeShareholderId) {
            $query->where('id', '!=', $excludeShareholderId);
        }

        return (int) $query->count();
    }

    public function capitalForShares(float $shares): float
    {
        return round($shares * $this->pricePerShare(), 2);
    }

    public function validateAllocation(float $shares, ?int $excludeShareholderId = null, bool $isNewShareholder = true): void
    {
        $min = (float) config('shareholders.min_shares_per_application', 0.01);

        if ($shares < $min) {
            throw ValidationException::withMessages([
                'shares' => 'Minimum subscription is ' . $min . ' share(s).',
            ]);
        }

        if ($shares > $this->remainingShares($excludeShareholderId)) {
            throw ValidationException::withMessages([
                'shares' => 'Only ' . number_format($this->remainingShares($excludeShareholderId), 2) . ' share(s) remain out of ' . number_format($this->totalShares(), 0) . '.',
            ]);
        }

        if ($isNewShareholder && $this->activeShareholderCount($excludeShareholderId) >= $this->maxShareholders()) {
            throw ValidationException::withMessages([
                'shares' => 'The maximum of ' . $this->maxShareholders() . ' shareholders has been reached.',
            ]);
        }
    }
}
