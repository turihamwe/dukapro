<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        Paginator::defaultView('pagination::tailwind');
        Paginator::defaultSimpleView('pagination::simple-tailwind');

        if ($this->app->environment('local') && ! $this->app->runningInConsole()) {
            URL::forceRootUrl(rtrim(request()->getSchemeAndHttpHost() . request()->getBaseUrl(), '/'));
        }

        Blade::directive('money', function ($expression) {
            return "<?php echo e(format_money($expression)); ?>";
        });
    }
}
