<?php

namespace App\Providers;

use App\Modules\Contracts\ModuleDefinition;
use App\Modules\ModuleRegistry;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleRegistry::class, function () {
            $registry = new ModuleRegistry();

            foreach ($this->moduleDefinitions() as $module) {
                $registry->register($module);
            }

            return $registry;
        });
    }

    public function boot(): void
    {
        //
    }

    /**
     * @return list<ModuleDefinition>
     */
    protected function moduleDefinitions(): array
    {
        $modules = [];

        foreach (config('modules', []) as $class) {
            if (! is_string($class) || ! class_exists($class)) {
                continue;
            }

            $instance = $this->app->make($class);

            if ($instance instanceof ModuleDefinition) {
                $modules[] = $instance;
            }
        }

        return $modules;
    }
}
