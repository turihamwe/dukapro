<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\BusinessModule;
use App\Models\SystemSetting;
use App\Modules\ModuleRegistry;
use App\Support\BillingMode;
use App\Support\SubscriptionPlan;
use Carbon\Carbon;

class ModuleBillingService
{
    protected ModuleRegistry $registry;

    protected BusinessModuleService $moduleService;

    public function __construct(ModuleRegistry $registry, BusinessModuleService $moduleService)
    {
        $this->registry = $registry;
        $this->moduleService = $moduleService;
    }

    /**
     * @return array<string, float>
     */
    public function modulePrices(): array
    {
        $stored = SystemSetting::get('module_prices');

        if (is_string($stored) && $stored !== '') {
            $decoded = json_decode($stored, true);

            if (is_array($decoded)) {
                return array_map('floatval', $decoded);
            }
        }

        return config('billing.default_module_prices', []);
    }

    public function modulePrice(string $moduleKey): float
    {
        $prices = $this->modulePrices();

        return (float) ($prices[$moduleKey] ?? 0);
    }

    public function isBillable(string $moduleKey): bool
    {
        return BillingMode::isAddons() && $this->modulePrice($moduleKey) > 0;
    }

    public function isGrandfathered(Business $business): bool
    {
        return BillingMode::isAddons() && (bool) $business->billing_grandfathered;
    }

    public function isComped(Business $business, string $moduleKey): bool
    {
        if (! BillingMode::isAddons()) {
            return false;
        }

        $record = $this->moduleRecord($business, $moduleKey);

        return $record ? (bool) $record->billing_comped : false;
    }

    public function hasActiveModuleSubscription(Business $business, string $moduleKey): bool
    {
        $record = $this->moduleRecord($business, $moduleKey);

        if (! $record || ! $record->billing_subscribed_until) {
            return false;
        }

        return $record->billing_subscribed_until->isFuture();
    }

    public function isEntitled(Business $business, string $moduleKey): bool
    {
        if (BillingMode::isUnified()) {
            return true;
        }

        if (! $this->isBillable($moduleKey)) {
            return true;
        }

        if ($this->isGrandfathered($business)) {
            return true;
        }

        if ($this->isComped($business, $moduleKey)) {
            return true;
        }

        if ($this->isOnTrial($business)) {
            return true;
        }

        return $this->hasActiveModuleSubscription($business, $moduleKey);
    }

    public function isAccessible(Business $business, string $moduleKey): bool
    {
        if (! $this->moduleService->isEnabled($business, $moduleKey)) {
            return false;
        }

        return $this->isEntitled($business, $moduleKey);
    }

    /**
     * Enabled billable modules included on subscription checkout.
     *
     * @return list<string>
     */
    public function modulesChargedAtCheckout(Business $business): array
    {
        if (BillingMode::isUnified() || $this->isGrandfathered($business)) {
            return [];
        }

        $keys = [];

        foreach ($this->registry->keys() as $moduleKey) {
            if (! $this->moduleService->isEnabled($business, $moduleKey)) {
                continue;
            }

            if (! $this->isBillable($moduleKey)) {
                continue;
            }

            if ($this->isComped($business, $moduleKey)) {
                continue;
            }

            $keys[] = $moduleKey;
        }

        return $keys;
    }

    /**
     * Enabled modules that are toggled on but not yet entitled (needs payment).
     *
     * @return list<string>
     */
    public function modulesNeedingPayment(Business $business): array
    {
        if (BillingMode::isUnified() || $this->isGrandfathered($business) || $this->isOnTrial($business)) {
            return [];
        }

        $keys = [];

        foreach ($this->registry->keys() as $moduleKey) {
            if (! $this->moduleService->isEnabled($business, $moduleKey)) {
                continue;
            }

            if (! $this->isBillable($moduleKey)) {
                continue;
            }

            if ($this->isEntitled($business, $moduleKey)) {
                continue;
            }

            $keys[] = $moduleKey;
        }

        return $keys;
    }

    /**
     * @return array{
     *     total: float,
     *     base_amount: float,
     *     module_amount: float,
     *     module_keys: list<string>,
     *     line_items: list<array{key: string, label: string, amount: float}>
     * }
     */
    public function calculatePaymentAmount(Business $business, string $planKey): array
    {
        $plan = SubscriptionPlan::find($planKey);
        $baseAmount = (float) $plan['amount'];
        $periodFactor = max(1, (int) $plan['days']) / 30;
        $moduleKeys = $this->modulesChargedAtCheckout($business);
        $lineItems = [
            [
                'key' => 'base',
                'label' => 'Platform subscription — ' . $plan['label'],
                'amount' => $baseAmount,
            ],
        ];
        $moduleAmount = 0.0;

        if (BillingMode::isAddons() && ! $this->isGrandfathered($business)) {
            foreach ($moduleKeys as $moduleKey) {
                $definition = $this->registry->get($moduleKey);
                $price = round($this->modulePrice($moduleKey) * $periodFactor, 2);
                $moduleAmount += $price;
                $lineItems[] = [
                    'key' => $moduleKey,
                    'label' => $definition->label() . ' add-on',
                    'amount' => $price,
                ];
            }
        } else {
            $moduleKeys = [];
        }

        return [
            'total' => round($baseAmount + $moduleAmount, 2),
            'base_amount' => $baseAmount,
            'module_amount' => round($moduleAmount, 2),
            'module_keys' => $moduleKeys,
            'line_items' => $lineItems,
            'plan' => $plan,
            'period_factor' => $periodFactor,
        ];
    }

    /**
     * @param  list<string>  $moduleKeys
     */
    public function activateModuleSubscriptions(Business $business, array $moduleKeys, Carbon $until): void
    {
        if (BillingMode::isUnified() || empty($moduleKeys)) {
            return;
        }

        foreach ($moduleKeys as $moduleKey) {
            if (! $this->registry->has($moduleKey)) {
                continue;
            }

            $existing = BusinessModule::query()
                ->where('business_id', $business->id)
                ->where('module_key', $moduleKey)
                ->first();

            BusinessModule::query()->updateOrCreate(
                [
                    'business_id' => $business->id,
                    'module_key' => $moduleKey,
                ],
                [
                    'enabled' => $existing ? $existing->enabled : false,
                    'settings' => $existing ? ($existing->settings ?? []) : [],
                    'source' => $existing ? $existing->source : BusinessModuleService::SOURCE_OWNER,
                    'billing_subscribed_until' => $until,
                ]
            );
        }

        $business->clearModuleCache();
    }

    /**
     * @return array<string, array{
     *     monthly_price: float,
     *     billable: bool,
     *     entitled: bool,
     *     accessible: bool,
     *     comped: bool,
     *     subscribed_until: ?string
     * }>
     */
    public function billingStates(Business $business): array
    {
        $states = [];

        foreach ($this->registry->keys() as $moduleKey) {
            $record = $this->moduleRecord($business, $moduleKey);
            $subscribedUntil = $record && $record->billing_subscribed_until
                ? $record->billing_subscribed_until->toDateTimeString()
                : null;

            $states[$moduleKey] = [
                'monthly_price' => $this->modulePrice($moduleKey),
                'billable' => $this->isBillable($moduleKey),
                'entitled' => $this->isEntitled($business, $moduleKey),
                'accessible' => $this->isAccessible($business, $moduleKey),
                'comped' => $this->isComped($business, $moduleKey),
                'subscribed_until' => $subscribedUntil,
            ];
        }

        return $states;
    }

    public function isOnTrial(Business $business): bool
    {
        if ($business->subscription_status !== SubscriptionStatus::TRIAL) {
            return false;
        }

        return ! $business->trial_ends_at || $business->trial_ends_at->isFuture();
    }

    protected function moduleRecord(Business $business, string $moduleKey): ?BusinessModule
    {
        if ($business->relationLoaded('businessModules')) {
            return $business->businessModules->firstWhere('module_key', $moduleKey);
        }

        return BusinessModule::query()
            ->where('business_id', $business->id)
            ->where('module_key', $moduleKey)
            ->first();
    }
}
