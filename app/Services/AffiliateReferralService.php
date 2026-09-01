<?php

namespace App\Services;

use App\Models\Affiliate;
use Illuminate\Http\Request;

class AffiliateReferralService
{
    public function captureFromRequest(Request $request): void
    {
        $code = trim((string) $request->query('ref', ''));

        if ($code === '') {
            return;
        }

        $this->captureCode($request, $code);
    }

    public function captureCode(Request $request, string $code): ?Affiliate
    {
        $code = strtolower(trim($code));

        if ($code === '') {
            return null;
        }

        $affiliate = $this->findActiveByCode($code);

        if ($affiliate) {
            $request->session()->put(config('affiliates.referral_session_key'), $affiliate->code);
        }

        return $affiliate;
    }

    public function resolveFromSession(Request $request): ?Affiliate
    {
        $code = (string) $request->session()->get(config('affiliates.referral_session_key'), '');

        if ($code === '') {
            return null;
        }

        return $this->findActiveByCode($code);
    }

    public function clearSession(Request $request): void
    {
        $request->session()->forget(config('affiliates.referral_session_key'));
    }

    public function findActiveByCode(string $code): ?Affiliate
    {
        $affiliate = Affiliate::query()
            ->where('code', strtolower(trim($code)))
            ->first();

        if (! $affiliate || ! $affiliate->canRefer()) {
            return null;
        }

        return $affiliate;
    }
}
