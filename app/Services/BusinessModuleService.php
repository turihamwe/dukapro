<?php

namespace App\Services;

use App\Enums\BusinessType;
use App\Models\Business;
use App\Models\BusinessModule;
use App\Modules\ModuleKeys;
use App\Modules\ModuleRegistry;
use Illuminate\Support\Collection;

class BusinessModuleService
{
    public const SOURCE_MIGRATION = 'migration';

    public const SOURCE_OWNER = 'owner';

    public const SOURCE_PRESET = 'preset';

    public const SOURCE_SUPERADMIN = 'superadmin';

    /** @var array<int, list<string>> */
    protected static $enabledKeysCache = [];

    protected ModuleRegistry $registry;

    public function __construct(ModuleRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @return list<string>
     */
    public function enabledKeys(Business $business): array
    {
        if (isset(self::$enabledKeysCache[$business->id])) {
            return self::$enabledKeysCache[$business->id];
        }

        $records = $this->moduleRecords($business);

        if ($records->isNotEmpty()) {
            $keys = $records
                ->where('enabled', true)
                ->pluck('module_key')
                ->values()
                ->all();
        } else {
            $keys = $this->legacyEnabledKeys($business);
        }

        self::$enabledKeysCache[$business->id] = $keys;

        return $keys;
    }

    public function isEnabled(Business $business, string $moduleKey): bool
    {
        return in_array($moduleKey, $this->enabledKeys($business), true);
    }

    /**
     * @return array<string, mixed>
     */
    public function settings(Business $business, string $moduleKey): array
    {
        $record = $this->moduleRecord($business, $moduleKey);

        if ($record) {
            return $record->settings ?? [];
        }

        return $this->legacySettings($business, $moduleKey);
    }

    public function setting(Business $business, string $moduleKey, string $settingKey, $default = null)
    {
        $settings = $this->settings($business, $moduleKey);

        return $settings[$settingKey] ?? $default;
    }

    public function syncFromLegacySettings(Business $business, string $source = self::SOURCE_OWNER): void
    {
        foreach ($this->registry->keys() as $moduleKey) {
            $this->upsertFromLegacy($business, $moduleKey, $source);
        }

        $business->clearModuleCache();
    }

    /**
     * @return array<string, array{label: string, description: string, enabled: bool, settings: array<string, mixed>, suggested: bool, billing: array<string, mixed>}>
     */
    public function capabilityStates(Business $business): array
    {
        $states = [];
        $billing = app(ModuleBillingService::class)->billingStates($business);

        foreach ($this->registry->all() as $definition) {
            $key = $definition->key();
            $states[$key] = [
                'label' => $definition->label(),
                'description' => $definition->description(),
                'enabled' => $this->isEnabled($business, $key),
                'settings' => $this->settings($business, $key),
                'suggested' => $definition->defaultEnabledFor($business),
                'billing' => $billing[$key] ?? [],
            ];
        }

        return $states;
    }

    /**
     * @param  array<string, array<string, mixed>>  $capabilities
     */
    public function updateCapabilities(Business $business, array $capabilities, string $source = self::SOURCE_OWNER): void
    {
        foreach ($this->registry->keys() as $moduleKey) {
            $payload = $capabilities[$moduleKey] ?? [];
            $enabled = (bool) ($payload['enabled'] ?? false);
            $settings = $this->settings($business, $moduleKey);

            BusinessModule::query()->updateOrCreate(
                [
                    'business_id' => $business->id,
                    'module_key' => $moduleKey,
                ],
                [
                    'enabled' => $enabled,
                    'settings' => $settings,
                    'source' => $source,
                ]
            );
        }

        $fresh = $business->fresh();
        $this->syncToLegacySettings($fresh);
        $fresh->clearModuleCache();
    }

    /**
     * @param  array<string, array<string, mixed>>  $modulesInput
     */
    public function updateSuperadminModules(Business $business, array $modulesInput, bool $billingGrandfathered, array $floorInput = []): void
    {
        $this->updateCapabilities(
            $business,
            $this->capabilitiesFromModulesInput($modulesInput),
            self::SOURCE_SUPERADMIN
        );

        $this->syncFloorSettings($business->fresh(), $floorInput);

        $business->update(['billing_grandfathered' => $billingGrandfathered]);

        foreach ($this->registry->keys() as $moduleKey) {
            $comped = filter_var(
                data_get($modulesInput, "{$moduleKey}.billing_comped", false),
                FILTER_VALIDATE_BOOLEAN
            );

            BusinessModule::query()
                ->where('business_id', $business->id)
                ->where('module_key', $moduleKey)
                ->update(['billing_comped' => $comped]);
        }

        $business->fresh()->clearModuleCache();
    }

    /**
     * @param  array<string, array<string, mixed>>  $modules
     * @return array<string, array<string, mixed>>
     */
    public function capabilitiesFromModulesInput(array $modules): array
    {
        $enabled = function (string $key) use ($modules) {
            return filter_var(data_get($modules, $key, false), FILTER_VALIDATE_BOOLEAN);
        };

        $capabilities = [];

        foreach ($this->registry->keys() as $moduleKey) {
            $entry = [
                'enabled' => $enabled("{$moduleKey}.enabled"),
            ];

            if ($moduleKey === ModuleKeys::RESTAURANT) {
                $entry['use_tables'] = $enabled('restaurant.use_tables');
                $entry['use_waiters'] = $enabled('restaurant.use_waiters');
            }

            $capabilities[$moduleKey] = $entry;
        }

        return $capabilities;
    }

    public function usesHospitalityFloor(Business $business): bool
    {
        return $this->isEnabled($business, ModuleKeys::RESTAURANT)
            || $this->isEnabled($business, ModuleKeys::BAR_SHIFT);
    }

    /**
     * @return array{use_waiters: bool, use_tables: bool}
     */
    public function floorSettings(Business $business): array
    {
        $floor = ($business->settings ?? [])['floor'] ?? null;

        if (is_array($floor)) {
            return [
                'use_waiters' => (bool) ($floor['use_waiters'] ?? false),
                'use_tables' => (bool) ($floor['use_tables'] ?? false),
            ];
        }

        return $this->legacyFloorSettings($business);
    }

    /**
     * @param  array<string, mixed>  $floorInput
     */
    public function syncFloorSettings(Business $business, array $floorInput): void
    {
        if (! $this->usesHospitalityFloor($business)) {
            return;
        }

        $this->persistFloorSettings($business, [
            'use_waiters' => filter_var($floorInput['use_waiters'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'use_tables' => filter_var($floorInput['use_tables'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    public function migrateFloorSettingsFromLegacy(Business $business): void
    {
        if (! $this->usesHospitalityFloor($business)) {
            return;
        }

        $this->persistFloorSettings($business, $this->legacyFloorSettings($business));
    }

    /**
     * @return array{use_waiters: bool, use_tables: bool}
     */
    protected function legacyFloorSettings(Business $business): array
    {
        if ($this->isEnabled($business, ModuleKeys::RESTAURANT)) {
            return [
                'use_waiters' => (bool) $this->setting($business, ModuleKeys::RESTAURANT, 'use_waiters', false),
                'use_tables' => (bool) $this->setting($business, ModuleKeys::RESTAURANT, 'use_tables', false),
            ];
        }

        if ($this->isEnabled($business, ModuleKeys::BAR_SHIFT)) {
            $barSettings = $this->settings($business, ModuleKeys::BAR_SHIFT);

            return [
                'use_waiters' => (bool) ($barSettings['use_waiters'] ?? true),
                'use_tables' => (bool) ($barSettings['use_tables'] ?? false),
            ];
        }

        return [
            'use_waiters' => false,
            'use_tables' => false,
        ];
    }

    /**
     * @param  array{use_waiters: bool, use_tables: bool}  $floor
     */
    protected function persistFloorSettings(Business $business, array $floor): void
    {
        $settings = $business->settings ?? [];
        $settings['floor'] = $floor;
        $business->update(['settings' => $settings]);

        foreach ([ModuleKeys::RESTAURANT, ModuleKeys::BAR_SHIFT] as $moduleKey) {
            if (! $this->isEnabled($business, $moduleKey)) {
                continue;
            }

            $record = $this->moduleRecord($business, $moduleKey);
            $moduleSettings = $record ? ($record->settings ?? []) : [];
            $moduleSettings['use_waiters'] = $floor['use_waiters'];
            $moduleSettings['use_tables'] = $floor['use_tables'];

            BusinessModule::query()->updateOrCreate(
                [
                    'business_id' => $business->id,
                    'module_key' => $moduleKey,
                ],
                [
                    'enabled' => true,
                    'settings' => $moduleSettings,
                    'source' => $record ? $record->source : self::SOURCE_OWNER,
                ]
            );
        }

        $fresh = $business->fresh();
        $this->syncToLegacySettings($fresh);
        $fresh->clearModuleCache();
    }

    public function syncToLegacySettings(Business $business): void
    {
        $this->forgetBusiness($business);

        $settings = $business->settings ?? [];
        $floor = $this->floorSettings($business);
        $settings['restaurant_mode'] = $this->isEnabled($business, ModuleKeys::RESTAURANT);
        $settings['use_restaurant_tables'] = $this->usesHospitalityFloor($business) ? $floor['use_tables'] : false;
        $settings['use_restaurant_waiters'] = $this->usesHospitalityFloor($business) ? $floor['use_waiters'] : false;
        $settings['shift_waiter_mode'] = $this->isEnabled($business, ModuleKeys::BAR_SHIFT);
        $settings['use_product_variants'] = $this->isEnabled($business, ModuleKeys::CATALOG_VARIANTS);

        $business->update(['settings' => $settings]);
    }

    public function forgetBusiness(Business $business): void
    {
        unset(self::$enabledKeysCache[$business->id]);
    }

    public function seedDefaultsForBusiness(Business $business, string $source = self::SOURCE_PRESET): void
    {
        foreach ($this->registry->all() as $definition) {
            BusinessModule::query()->updateOrCreate(
                [
                    'business_id' => $business->id,
                    'module_key' => $definition->key(),
                ],
                [
                    'enabled' => $definition->defaultEnabledFor($business),
                    'settings' => $definition->defaultSettingsFor($business),
                    'source' => $source,
                ]
            );
        }

        $this->syncFromLegacySettings($business, $source);

        $business->clearModuleCache();
    }

    public function migrateAllBusinesses(): int
    {
        $count = 0;

        Business::query()->orderBy('id')->chunkById(100, function ($businesses) use (&$count) {
            foreach ($businesses as $business) {
                $this->syncFromLegacySettings($business, self::SOURCE_MIGRATION);
                $count++;
            }
        });

        return $count;
    }

    protected function upsertFromLegacy(Business $business, string $moduleKey, string $source): void
    {
        BusinessModule::query()->updateOrCreate(
            [
                'business_id' => $business->id,
                'module_key' => $moduleKey,
            ],
            [
                'enabled' => $this->legacyIsEnabled($business, $moduleKey),
                'settings' => $this->legacySettings($business, $moduleKey),
                'source' => $source,
            ]
        );
    }

    protected function moduleRecords(Business $business): Collection
    {
        if ($business->relationLoaded('businessModules')) {
            return $business->businessModules;
        }

        return BusinessModule::query()
            ->where('business_id', $business->id)
            ->get();
    }

    protected function moduleRecord(Business $business, string $moduleKey): ?BusinessModule
    {
        $records = $this->moduleRecords($business);

        if ($records->isNotEmpty()) {
            return $records->firstWhere('module_key', $moduleKey);
        }

        return BusinessModule::query()
            ->where('business_id', $business->id)
            ->where('module_key', $moduleKey)
            ->first();
    }

    /**
     * @return list<string>
     */
    protected function legacyEnabledKeys(Business $business): array
    {
        $enabled = [];

        if ($this->legacyIsEnabled($business, ModuleKeys::RESTAURANT)) {
            $enabled[] = ModuleKeys::RESTAURANT;
        }

        if ($this->legacyIsEnabled($business, ModuleKeys::BAR_SHIFT)) {
            $enabled[] = ModuleKeys::BAR_SHIFT;
        }

        if ($this->legacyIsEnabled($business, ModuleKeys::CATALOG_VARIANTS)) {
            $enabled[] = ModuleKeys::CATALOG_VARIANTS;
        }

        return $enabled;
    }

    protected function legacyIsEnabled(Business $business, string $moduleKey): bool
    {
        $settings = $business->settings ?? [];

        switch ($moduleKey) {
            case ModuleKeys::RESTAURANT:
                return (bool) ($settings['restaurant_mode'] ?? false);

            case ModuleKeys::BAR_SHIFT:
                return (bool) ($settings['shift_waiter_mode'] ?? false);

            case ModuleKeys::CATALOG_VARIANTS:
                return (bool) ($settings['use_product_variants'] ?? false);

            default:
                return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function legacySettings(Business $business, string $moduleKey): array
    {
        $settings = $business->settings ?? [];

        if ($moduleKey === ModuleKeys::RESTAURANT) {
            $restaurantMode = (bool) ($settings['restaurant_mode'] ?? false);
            $shiftWaiterMode = (bool) ($settings['shift_waiter_mode'] ?? false);

            return [
                'use_tables' => (bool) ($settings['use_restaurant_tables'] ?? false),
                'use_waiters' => (bool) ($settings['use_restaurant_waiters'] ?? ($restaurantMode && $shiftWaiterMode)),
            ];
        }

        if ($moduleKey === ModuleKeys::BAR_SHIFT) {
            $floor = ($business->settings ?? [])['floor'] ?? [];

            return [
                'use_tables' => (bool) ($floor['use_tables'] ?? false),
                'use_waiters' => (bool) ($floor['use_waiters'] ?? true),
            ];
        }

        if ($moduleKey === ModuleKeys::CATALOG_VARIANTS) {
            return [];
        }

        return [];
    }
}
