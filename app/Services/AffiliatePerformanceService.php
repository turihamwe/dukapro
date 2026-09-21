<?php

namespace App\Services;

use App\Enums\AffiliateStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\Business;
use App\Support\AffiliateTargets;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AffiliatePerformanceService
{
    protected SystemAffiliateService $systemAffiliateService;

    protected AffiliateTargetTrackingService $targetTrackingService;

    protected AffiliateAttributionService $attributionService;

    public function __construct(
        SystemAffiliateService $systemAffiliateService,
        AffiliateTargetTrackingService $targetTrackingService,
        AffiliateAttributionService $attributionService
    ) {
        $this->systemAffiliateService = $systemAffiliateService;
        $this->targetTrackingService = $targetTrackingService;
        $this->attributionService = $attributionService;
    }

    public function dateRange(?string $period): ?array
    {
        switch ($period) {
            case 'this_month':
                return [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()];
            case 'last_month':
                $lastMonth = Carbon::now()->subMonth();

                return [$lastMonth->copy()->startOfMonth(), $lastMonth->copy()->endOfMonth()];
            default:
                return null;
        }
    }

    public function platformSummary(array $filters = []): array
    {
        $range = $this->dateRange($filters['period'] ?? null);

        $activeAffiliatesQuery = Affiliate::query()
            ->where('status', AffiliateStatus::APPROVED)
            ->where('is_active', true);

        $businessQuery = Business::query();
        if ($range) {
            $businessQuery->whereBetween('created_at', $range);
        }

        $onboardedTotal = (clone $businessQuery)->count();
        $activeSubscribers = (clone $businessQuery)
            ->where('subscription_status', SubscriptionStatus::ACTIVE)
            ->count();

        $commissionQuery = AffiliateCommission::query();
        if ($range) {
            $commissionQuery->whereBetween('created_at', $range);
        }

        $paidCommission = (float) (clone $commissionQuery)->where('status', 'paid')->sum('commission_amount');
        $pendingCommission = (float) (clone $commissionQuery)->where('status', 'pending')->sum('commission_amount');

        return [
            'active_affiliates' => $activeAffiliatesQuery->count(),
            'onboarded_businesses' => $onboardedTotal,
            'active_subscribers' => $activeSubscribers,
            'conversion_rate' => $onboardedTotal > 0
                ? round(($activeSubscribers / $onboardedTotal) * 100, 1)
                : 0.0,
            'paid_commission' => $paidCommission,
            'pending_commission' => $pendingCommission,
            'total_commission' => $paidCommission + $pendingCommission,
        ];
    }

    public function affiliateRows(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $range = $this->dateRange($filters['period'] ?? null);
        $search = trim((string) ($filters['search'] ?? ''));
        $status = (string) ($filters['status'] ?? 'all');
        $systemCode = $this->systemAffiliateService->systemCode();

        $query = Affiliate::query()
            ->with(['user:id,username,name,email'])
            ->withCount([
                'referredBusinesses as businesses_onboarded_count' => function (Builder $builder) use ($range) {
                    if ($range) {
                        $builder->whereBetween('created_at', $range);
                    }
                },
                'directReferredBusinesses as direct_referrals_count' => function (Builder $builder) use ($range) {
                    if ($range) {
                        $builder->whereBetween('created_at', $range);
                    }
                },
                'subAffiliateReferredBusinesses as sub_referrals_count' => function (Builder $builder) use ($range) {
                    if ($range) {
                        $builder->whereBetween('created_at', $range);
                    }
                },
                'referredBusinesses as active_subscribers_count' => function (Builder $builder) use ($range) {
                    $builder->where('subscription_status', SubscriptionStatus::ACTIVE);
                    if ($range) {
                        $builder->whereBetween('created_at', $range);
                    }
                },
            ])
            ->withSum([
                'commissions as total_earnings' => function (Builder $builder) use ($range) {
                    if ($range) {
                        $builder->whereBetween('created_at', $range);
                    }
                },
            ], 'commission_amount')
            ->withSum([
                'commissions as paid_earnings' => function (Builder $builder) use ($range) {
                    $builder->where('status', 'paid');
                    if ($range) {
                        $builder->whereBetween('created_at', $range);
                    }
                },
            ], 'commission_amount')
            ->withSum([
                'commissions as pending_earnings' => function (Builder $builder) use ($range) {
                    $builder->where('status', 'pending');
                    if ($range) {
                        $builder->whereBetween('created_at', $range);
                    }
                },
            ], 'commission_amount');

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhereHas('user', function (Builder $userQuery) use ($search) {
                        $userQuery->where('username', 'like', "%{$search}%");
                    });
            });
        }

        $this->applyStatusFilter($query, $status);

        $paginator = $query
            ->orderByRaw('CASE WHEN LOWER(code) = ? THEN 0 ELSE 1 END', [$systemCode])
            ->orderByDesc('businesses_onboarded_count')
            ->orderBy('name')
            ->paginate($perPage);

        $referralCounts = $this->targetTrackingService->batchReferralCounts($paginator->getCollection()->pluck('id'));

        $paginator->setCollection($paginator->getCollection()->map(function (Affiliate $affiliate) use ($referralCounts) {
            $onboarded = (int) ($affiliate->businesses_onboarded_count ?? 0);
            $active = (int) ($affiliate->active_subscribers_count ?? 0);
            $actuals = $referralCounts[$affiliate->id] ?? [
                'daily' => 0,
                'weekly' => 0,
                'monthly' => 0,
                'annual' => 0,
            ];

            return [
                'affiliate' => $affiliate,
                'onboarded_count' => $onboarded,
                'direct_referrals_count' => (int) ($affiliate->direct_referrals_count ?? 0),
                'sub_referrals_count' => (int) ($affiliate->sub_referrals_count ?? 0),
                'active_subscribers' => $active,
                'conversion_rate' => $onboarded > 0 ? round(($active / $onboarded) * 100, 1) : 0.0,
                'target_status' => AffiliateTargets::labelForCount($onboarded),
                'target_tiers_hit' => AffiliateTargets::statusForCount($onboarded)['hit_tiers'],
                'tracking' => $this->targetTrackingService->trackingPayload($affiliate, $actuals),
                'total_earnings' => (float) ($affiliate->total_earnings ?? 0),
                'paid_earnings' => (float) ($affiliate->paid_earnings ?? 0),
                'pending_earnings' => (float) ($affiliate->pending_earnings ?? 0),
                'is_system_default' => $this->systemAffiliateService->isSystemDefault($affiliate),
            ];
        }));

        return $paginator;
    }

    public function affiliateBreakdown(Affiliate $affiliate, ?array $range = null): array
    {
        $businessQuery = $affiliate->referredBusinesses()
            ->orderByDesc('created_at');

        if ($range) {
            $businessQuery->whereBetween('created_at', $range);
        }

        $businesses = $businessQuery
            ->with('referringAffiliate:id,name,code')
            ->get([
                'id',
                'name',
                'email',
                'phone',
                'subscription_status',
                'subscription_ends_at',
                'created_at',
                'referring_affiliate_id',
            ]);

        $onboarded = $businesses->count();
        $active = $businesses->where('subscription_status', SubscriptionStatus::ACTIVE)->count();
        $referralCounts = $this->attributionService->referralCounts($affiliate, $range);

        return [
            'affiliate' => $affiliate->loadMissing('user:id,username,name,email'),
            'businesses' => $businesses,
            'onboarded_count' => $onboarded,
            'direct_referrals_count' => $referralCounts['direct'],
            'sub_referrals_count' => $referralCounts['sub'],
            'by_sub_affiliate' => $this->attributionService->breakdownBySubAffiliate($affiliate, $range),
            'active_subscribers' => $active,
            'conversion_rate' => $onboarded > 0 ? round(($active / $onboarded) * 100, 1) : 0.0,
            'target_status' => AffiliateTargets::labelForCount($onboarded),
            'tracking' => $this->targetTrackingService->trackingPayload($affiliate),
            'total_earnings' => (float) $affiliate->commissions()->when($range, fn ($q) => $q->whereBetween('created_at', $range))->sum('commission_amount'),
            'paid_earnings' => (float) $affiliate->commissions()->where('status', 'paid')->when($range, fn ($q) => $q->whereBetween('created_at', $range))->sum('commission_amount'),
            'pending_earnings' => (float) $affiliate->commissions()->where('status', 'pending')->when($range, fn ($q) => $q->whereBetween('created_at', $range))->sum('commission_amount'),
        ];
    }

    protected function applyStatusFilter(Builder $query, string $status): void
    {
        switch ($status) {
            case AffiliateStatus::APPROVED:
                $query->where('status', AffiliateStatus::APPROVED)->where('is_active', true);
                break;
            case AffiliateStatus::PENDING:
                $query->where('status', AffiliateStatus::PENDING);
                break;
            case AffiliateStatus::REJECTED:
                $query->where('status', AffiliateStatus::REJECTED);
                break;
            case 'disabled':
                $query->where(function (Builder $builder) {
                    $builder->where('status', AffiliateStatus::SUSPENDED)
                        ->orWhere(function (Builder $nested) {
                            $nested->where('status', AffiliateStatus::APPROVED)
                                ->where('is_active', false);
                        });
                });
                break;
        }
    }
}
