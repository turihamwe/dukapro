<?php

namespace App\Providers;

use App\Modules\BarShift\BarShiftModule;
use App\Modules\CatalogVariants\CatalogVariantsModule;
use App\Modules\ModuleRegistry;
use App\Modules\Restaurant\RestaurantModule;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleRegistry::class, function () {
            $registry = new ModuleRegistry();

            foreach ([
                new RestaurantModule(),
                new BarShiftModule(),
                new CatalogVariantsModule(),
            ] as $module) {
                $registry->register($module);
            }

            return $registry;
        });
    }

    public function boot(): void
    {
        //
    }
}
