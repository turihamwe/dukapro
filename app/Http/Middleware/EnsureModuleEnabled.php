<?php

namespace App\Http\Middleware;

use App\Modules\ModuleRegistry;
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

        if (! $business || ! $business->hasModule($moduleKey)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'This capability is not enabled for your business.',
                ], 404);
            }

            abort(404);
        }

        return $next($request);
    }
}
