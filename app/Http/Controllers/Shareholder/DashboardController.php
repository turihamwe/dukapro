<?php

namespace App\Http\Controllers\Shareholder;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $shareholder = $user->shareholderProfile;

        abort_unless($shareholder, 404);

        $shareholder->load(['earnings' => function ($query) {
            $query->latest('id')->limit(50);
        }]);

        return view('shareholder.dashboard', [
            'shareholder' => $shareholder,
            'earningsCap' => $shareholder->earningsCap(),
            'progressPercent' => $shareholder->earningsProgressPercent(),
            'remainingCapacity' => $shareholder->remainingEarningsCapacity(),
        ]);
    }
}
