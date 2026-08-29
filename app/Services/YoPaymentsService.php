<?php

namespace App\Services;

use App\Models\SystemSetting;

class YoPaymentsService
{
    public function isConfigured(): bool
    {
        $config = $this->config();

        return $config['enabled']
            && ! empty($config['api_username'])
            && ! empty($config['api_password'])
            && ! empty($config['account_id']);
    }

    public function config(): array
    {
        $keys = config('yopayments.settings_keys');

        return [
            'enabled' => $this->boolSetting($keys['enabled'], config('yopayments.enabled')),
            'environment' => SystemSetting::get($keys['environment'], config('yopayments.environment')),
            'api_url' => config('yopayments.api_url'),
            'api_username' => SystemSetting::get($keys['api_username'], config('yopayments.api_username')),
            'api_password' => SystemSetting::get($keys['api_password'], config('yopayments.api_password')),
            'account_id' => SystemSetting::get($keys['account_id'], config('yopayments.account_id')),
        ];
    }

    /**
     * Placeholder for future YoPayments deposit/collection API integration.
     */
    public function initiateCollection(string $phoneNumber, float $amount, string $reference, string $narrative): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'YoPayments is not configured. Add API credentials in SuperAdmin settings.',
            ];
        }

        return [
            'success' => true,
            'message' => 'YoPayments credentials are configured. Wire the live API call when ready.',
            'reference' => $reference,
            'environment' => $this->config()['environment'],
        ];
    }

    protected function boolSetting(string $key, $default = false): bool
    {
        $value = SystemSetting::get($key);

        if ($value === null || $value === '') {
            return (bool) $default;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }
}
