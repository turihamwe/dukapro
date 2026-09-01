<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Helpers\SystemAuditLogger;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserPromotionService;
use Illuminate\Http\Request;

class UserActionController extends Controller
{
    protected UserPromotionService $promotionService;

    public function __construct(UserPromotionService $promotionService)
    {
        $this->promotionService = $promotionService;
    }

    public function promoteAffiliate(Request $request, User $user)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $affiliate = $this->promotionService->promoteToAffiliate($user, $request->user());

        SystemAuditLogger::record(
            'user_promoted_affiliate',
            'Promoted user #' . $user->id . ' (' . $user->email . ') to affiliate #' . $affiliate->id,
            $user->business_id,
            $request->user()->id,
            ['affiliate_id' => $affiliate->id, 'user_id' => $user->id]
        );

        return redirect()
            ->route('superadmin.entities.show', ['affiliates', $affiliate->id])
            ->with('success', $user->name . ' is now an affiliate with dashboard access.');
    }

    public function promoteShareholder(Request $request, User $user)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $data = $request->validate([
            'shares' => 'nullable|numeric|min:0.01',
        ]);

        $shareholder = $this->promotionService->promoteToShareholder(
            $user,
            $request->user(),
            isset($data['shares']) ? (float) $data['shares'] : null
        );

        SystemAuditLogger::record(
            'user_promoted_shareholder',
            'Promoted user #' . $user->id . ' (' . $user->email . ') to shareholder #' . $shareholder->id,
            $user->business_id,
            $request->user()->id,
            [
                'shareholder_id' => $shareholder->id,
                'user_id' => $user->id,
                'shares' => $shareholder->shares_owned,
            ]
        );

        return redirect()
            ->route('superadmin.entities.show', ['shareholders', $shareholder->id])
            ->with('success', $user->name . ' is now a shareholder with ' . number_format($shareholder->shares_owned, 2) . ' share(s).');
    }
}
