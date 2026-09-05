<?php

namespace App\Http\Middleware;

use App\Modules\ModuleRegistry;
use App\Services\ModuleBillingService;
use Closure;
use Illuminate\Http\Request;
use InvalidArgumentException;

class EnsureModuleEnabled
{
    protected ModuleRegistry $registry;

    public function __construct(ModuleRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function handle(Request $request, Closure $next, string $moduleKey)
    {
        if (! $this->registry->has($moduleKey)) {
            throw new InvalidArgumentException("Unknown module middleware key [{$moduleKey}].");
        }

        $user = $request->user();
        $business = $user ? $user->business : null;

        if (! $business) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'This capability is not enabled for your business.'], 404);
            }

            abort(404);
        }

        $billing = app(ModuleBillingService::class);

        if ($billing->isAccessible($business, $moduleKey)) {
            return $next($request);
        }

        $message = $business->hasModuleEnabled($moduleKey) && ! $billing->isEntitled($business, $moduleKey)
            ? 'This capability requires an active add-on subscription. Renew your subscription to unlock it.'
            : 'This capability is not enabled for your business.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 404);
        }

        abort(404, $message);
    }
}
