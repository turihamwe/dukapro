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
                if (! BusinessType::isHospitality($business->business_type)) {
                    return false;
                }

                return (bool) ($settings['restaurant_mode'] ?? true);

            case ModuleKeys::BAR_SHIFT:
                return (bool) ($settings['shift_waiter_mode'] ?? BusinessType::isHospitality($business->business_type));

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
            return [
                'use_tables' => (bool) ($settings['use_restaurant_tables'] ?? false),
            ];
        }

        if ($moduleKey === ModuleKeys::CATALOG_VARIANTS || $moduleKey === ModuleKeys::BAR_SHIFT) {
            return [];
        }

        return [];
    }
}
