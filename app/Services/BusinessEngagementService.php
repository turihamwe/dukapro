<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Support\BusinessEngagementTier;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class BusinessEngagementService
{
    public const PRIORITY_TRIAL_DAYS = 7;

    public function trialBusinessesQuery(string $filter = 'all', string $search = ''): Builder
    {
        $query = Business::query()
            ->where('subscription_status', SubscriptionStatus::TRIAL)
            ->orderByRaw('COALESCE(last_active_at, created_at) ASC');

        $this->applyEngagementFilter($query, $filter);
        $this->applySearch($query, $search);

        return $query;
    }

    public function summaryCounts(): array
    {
        $base = Business::query()->where('subscription_status', SubscriptionStatus::TRIAL);

        return [
            'total' => (clone $base)->count(),
            'active' => $this->applyEngagementFilter(clone $base, BusinessEngagementTier::ACTIVE)->count(),
            'at_risk' => $this->applyEngagementFilter(clone $base, BusinessEngagementTier::AT_RISK)->count(),
            'dormant' => $this->applyEngagementFilter(clone $base, BusinessEngagementTier::DORMANT)->count(),
            'priority' => $this->applyEngagementFilter(clone $base, 'priority')->count(),
        ];
    }

    public function applyEngagementFilter(Builder $query, string $filter): Builder
    {
        if ($filter === 'all') {
            return $query;
        }

        if ($filter === 'priority') {
            $this->scopeAtRisk($query);

            return $query->whereNotNull('trial_ends_at')
                ->whereBetween('trial_ends_at', [now(), now()->copy()->addDays(self::PRIORITY_TRIAL_DAYS)]);
        }

        if ($filter === BusinessEngagementTier::ACTIVE) {
            return $this->scopeActive($query);
        }

        if ($filter === BusinessEngagementTier::AT_RISK) {
            return $this->scopeAtRisk($query);
        }

        if ($filter === BusinessEngagementTier::DORMANT) {
            return $this->scopeDormant($query);
        }

        return $query;
    }

    public function engagementTierFor(Business $business): string
    {
        $reference = $business->last_active_at ?? $business->created_at;

        if (! $reference instanceof Carbon) {
            return BusinessEngagementTier::DORMANT;
        }

        $daysSince = $reference->diffInDays(now());

        if ($daysSince <= 3) {
            return BusinessEngagementTier::ACTIVE;
        }

        if ($daysSince <= 14) {
            return BusinessEngagementTier::AT_RISK;
        }

        return BusinessEngagementTier::DORMANT;
    }

    public function trialDaysRemaining(Business $business): ?int
    {
        if ($business->subscription_status !== SubscriptionStatus::TRIAL || ! $business->trial_ends_at) {
            return null;
        }

        if ($business->trial_ends_at->isPast()) {
            return 0;
        }

        return (int) now()->startOfDay()->diffInDays($business->trial_ends_at->copy()->startOfDay());
    }

    protected function scopeActive(Builder $query): Builder
    {
        $cutoff = now()->subDays(3);

        return $query->whereRaw('COALESCE(last_active_at, created_at) >= ?', [$cutoff]);
    }

    protected function scopeAtRisk(Builder $query): Builder
    {
        $recent = now()->subDays(3);
        $older = now()->subDays(14);

        return $query->whereRaw('COALESCE(last_active_at, created_at) < ?', [$recent])
            ->whereRaw('COALESCE(last_active_at, created_at) >= ?', [$older]);
    }

    protected function scopeDormant(Builder $query): Builder
    {
        $cutoff = now()->subDays(14);

        return $query->whereRaw('COALESCE(last_active_at, created_at) < ?', [$cutoff]);
    }

    protected function applySearch(Builder $query, string $search): void
    {
        $search = trim($search);
        if ($search === '') {
            return;
        }

        $query->where(function (Builder $builder) use ($search) {
            $builder->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%");
        });
    }
}
