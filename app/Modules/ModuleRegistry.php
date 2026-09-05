<?php

namespace App\Modules;

use App\Modules\Contracts\ModuleDefinition;
use InvalidArgumentException;

class ModuleRegistry
{
    /** @var array<string, ModuleDefinition> */
    protected array $modules = [];

    public function register(ModuleDefinition $module): void
    {
        $this->modules[$module->key()] = $module;
    }

    public function has(string $key): bool
    {
        return isset($this->modules[$key]);
    }

    public function get(string $key): ModuleDefinition
    {
        if (! isset($this->modules[$key])) {
            throw new InvalidArgumentException("Unknown module [{$key}].");
        }

        return $this->modules[$key];
    }

    /**
     * @return array<string, ModuleDefinition>
     */
    public function all(): array
    {
        return $this->modules;
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_keys($this->modules);
    }
}
