<?php

namespace App\Http\Middleware;

use App\Support\BranchContext;
use App\Support\BranchImpersonation;
use Closure;
use Illuminate\Http\Request;

class ApplyBranchContext
{
    public function handle(Request $request, Closure $next)
    {
        BranchContext::clear();

        $user = $request->user();

        if ($user) {
            $branchId = BranchImpersonation::validatedBranchId($user);

            if ($branchId !== null) {
                BranchContext::set($branchId, true);
            }
        }

        $response = $next($request);

        BranchContext::clear();

        return $response;
    }
}
