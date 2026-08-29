<?php

namespace App\Providers;

use App\Models\Business;
use App\Models\Customer;
use App\Models\EndOfDayReconciliation;
use App\Models\Expense;
use App\Models\Product;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/register';

    public function boot()
    {
        Route::bind('business', function (string $value) {
            return Business::where('slug', $value)->firstOrFail();
        });

        Route::bind('employee', function ($value, $route) {
            return $this->resolveTenantRecord(User::class, $value, $route);
        });

        Route::bind('product', function ($value, $route) {
            return $this->resolveTenantRecord(Product::class, $value, $route);
        });

        Route::bind('customer', function ($value, $route) {
            return $this->resolveTenantRecord(Customer::class, $value, $route);
        });

        Route::bind('expense', function ($value, $route) {
            return $this->resolveTenantRecord(Expense::class, $value, $route);
        });

        Route::bind('reconciliation', function ($value, $route) {
            return $this->resolveTenantRecord(EndOfDayReconciliation::class, $value, $route);
        });

        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });
    }

    protected function resolveTenantRecord(string $modelClass, $value, $route)
    {
        $business = $route->parameter('business');

        if (! $business instanceof Business) {
            abort(404);
        }

        return $modelClass::query()
            ->where('business_id', $business->id)
            ->whereKey($value)
            ->firstOrFail();
    }
}
