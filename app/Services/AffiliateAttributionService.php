<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Models\Affiliate;
use App\Models\Business;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

class AffiliateAttributionService
{
    public function directReferralsQuery(Affiliate $sponsor): Builder
    {
        return Business::query()
            ->where('sponsor_id', $sponsor->id)
            ->whereNull('referring_affiliate_id');
    }

    public function subAffiliateReferralsQuery(Affiliate $sponsor): Builder
    {
        return Business::query()
            ->where('sponsor_id', $sponsor->id)
            ->whereNotNull('referring_affiliate_id');
    }

    public function applyReferralTypeFilter(Relation $query, ?string $referralType): Relation
    {
        if ($referralType === 'direct') {
            return $query->whereNull('referring_affiliate_id');
        }

        if ($referralType === 'sub') {
            return $query->whereNotNull('referring_affiliate_id');
        }

        return $query;
    }

    public function referralCounts(Affiliate $sponsor, ?array $range = null): array
    {
        $directQuery = $this->directReferralsQuery($sponsor);
        $subQuery = $this->subAffiliateReferralsQuery($sponsor);

        if ($range) {
            $directQuery->whereBetween('created_at', $range);
            $subQuery->whereBetween('created_at', $range);
        }

        $direct = (int) $directQuery->count();
        $sub = (int) $subQuery->count();

        return [
            'direct' => $direct,
            'sub' => $sub,
            'total' => $direct + $sub,
        ];
    }

    /**
     * @return Collection<int, array{sub_affiliate: Affiliate|null, count: int, active_count: int, businesses: Collection}>
     */
    public function breakdownBySubAffiliate(Affiliate $sponsor, ?array $range = null): Collection
    {
        $query = $this->subAffiliateReferralsQuery($sponsor)
            ->with('referringAffiliate:id,name,code,parent_affiliate_id')
            ->orderByDesc('created_at');

        if ($range) {
            $query->whereBetween('created_at', $range);
        }

        $businesses = $query->get([
            'id',
            'name',
            'email',
            'phone',
            'subscription_status',
            'created_at',
            'referring_affiliate_id',
        ]);

        return $businesses
            ->groupBy('referring_affiliate_id')
            ->map(function (Collection $group) {
                $sub = $group->first()->referringAffiliate;

                return [
                    'sub_affiliate' => $sub,
                    'count' => $group->count(),
                    'active_count' => $group->where('subscription_status', SubscriptionStatus::ACTIVE)->count(),
                    'businesses' => $group->values(),
                ];
            })
            ->sortByDesc('count')
            ->values();
    }

    public function attributionLabel(Business $business): string
    {
        if ($business->referring_affiliate_id && $business->relationLoaded('referringAffiliate') && $business->referringAffiliate) {
            return $business->referringAffiliate->name;
        }

        if ($business->referring_affiliate_id) {
            return 'Sub-affiliate';
        }

        return 'Direct (you)';
    }
}
