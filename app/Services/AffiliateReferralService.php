<?php

namespace App\Services;

use App\Models\Affiliate;
use Illuminate\Http\Request;

class AffiliateReferralService
{
    public function captureFromRequest(Request $request): void
    {
        $code = trim((string) $request->query('ref', ''));

        if ($code !== '') {
            $this->captureCode($request, $code);
        }

        $subCode = trim((string) $request->query('sub', ''));

        if ($subCode !== '') {
            $this->captureSubCode($request, $subCode);
        }
    }

    public function captureCode(Request $request, string $code): ?Affiliate
    {
        $code = strtolower(trim($code));

        if ($code === '') {
            return null;
        }

        $affiliate = $this->findActiveByCode($code);

        if ($affiliate) {
            $request->session()->put(config('affiliates.referral_session_key'), $affiliate->effectiveReferralCode());
        }

        return $affiliate;
    }

    public function captureSubCode(Request $request, string $subCode): ?Affiliate
    {
        $subCode = strtolower(trim($subCode));

        if ($subCode === '') {
            return null;
        }

        $request->session()->put(config('affiliates.sub_referral_session_key'), $subCode);

        return $this->resolveSubFromSession($request);
    }

    public function resolveFromSession(Request $request): ?Affiliate
    {
        return $this->resolveReferralPairFromSession($request)['parent'];
    }

    public function resolveReferralPairFromSession(Request $request): array
    {
        $code = (string) $request->session()->get(config('affiliates.referral_session_key'), '');

        if ($code === '') {
            return ['parent' => null, 'sub' => null];
        }

        $parent = $this->findActiveByCode($code);

        if (! $parent) {
            return ['parent' => null, 'sub' => null];
        }

        $sub = $this->resolveSubFromSession($request, $parent);

        return ['parent' => $parent->primaryAffiliate(), 'sub' => $sub];
    }

    public function resolveSubFromSession(Request $request, ?Affiliate $parent = null): ?Affiliate
    {
        $subCode = (string) $request->session()->get(config('affiliates.sub_referral_session_key'), '');

        if ($subCode === '') {
            return null;
        }

        $parent = $parent ?: $this->resolveFromSession($request);

        if (! $parent) {
            return null;
        }

        return Affiliate::query()
            ->where('parent_affiliate_id', $parent->id)
            ->where('code', $subCode)
            ->where('is_active', true)
            ->where('status', 'approved')
            ->first();
    }

    public function clearSession(Request $request): void
    {
        $request->session()->forget(config('affiliates.referral_session_key'));
        $request->session()->forget(config('affiliates.sub_referral_session_key'));
    }

    public function findActiveByCode(string $code): ?Affiliate
    {
        $affiliate = Affiliate::query()
            ->where('code', strtolower(trim($code)))
            ->first();

        if (! $affiliate || ! $affiliate->canRefer()) {
            return null;
        }

        return $affiliate->primaryAffiliate();
    }

    public function findTeamLeaderByCode(string $code): ?Affiliate
    {
        $affiliate = Affiliate::query()
            ->where('code', strtolower(trim($code)))
            ->first();

        if (! $affiliate || ! $affiliate->canRefer() || $affiliate->isSubAffiliate()) {
            return null;
        }

        return $affiliate;
    }
}
