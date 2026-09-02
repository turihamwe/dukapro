<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class YoPaymentsService
{
    public const PROVIDER_MTN = 'MTN_UGANDA';

    public const PROVIDER_AIRTEL = 'AIRTEL_UGANDA';

    protected function log(string $level, string $message, array $context = []): void
    {
        Log::channel('yopayments')->{$level}($message, $context);
        Log::{$level}('[YoPayments] ' . $message, $context);
    }

    public function isConfigured(): bool
    {
        $config = $this->config();

        return $config['enabled']
            && ! empty($config['api_username'])
            && ! empty($config['api_password']);
    }

    public function isSandbox(): bool
    {
        $keys = config('yopayments.settings_keys');

        return SystemSetting::get($keys['environment'], config('yopayments.environment')) !== 'live';
    }

    /**
     * Use built-in local simulation only when YoPayments is disabled or credentials are missing.
     * Sandbox mode still calls Yo's sandbox API (per API spec section 24).
     */
    public function shouldSimulate(): bool
    {
        if ((bool) config('yopayments.force_simulate', false)) {
            return true;
        }

        return ! $this->isConfigured();
    }

    public function config(): array
    {
        $keys = config('yopayments.settings_keys');
        $environment = SystemSetting::get($keys['environment'], config('yopayments.environment'));
        $sandbox = $environment !== 'live';

        return [
            'enabled' => $this->boolSetting($keys['enabled'], config('yopayments.enabled')),
            'environment' => $environment,
            'api_url' => $sandbox
                ? config('yopayments.sandbox_api_url')
                : config('yopayments.live_api_url'),
            'api_username' => SystemSetting::get($keys['api_username'], config('yopayments.api_username')),
            'api_password' => SystemSetting::get($keys['api_password'], config('yopayments.api_password')),
            'account_provider_code' => SystemSetting::get($keys['account_id'], config('yopayments.account_id')),
        ];
    }

    public function apiUrl(): string
    {
        return $this->isSandbox()
            ? config('yopayments.sandbox_api_url')
            : config('yopayments.live_api_url');
    }

    public function resolveProviderCode(?string $provider = null): ?string
    {
        $override = trim((string) ($this->config()['account_provider_code'] ?? ''));

        if ($override !== '') {
            return strtoupper($override);
        }

        if (! $provider) {
            return null;
        }

        $map = config('yopayments.provider_codes', []);

        return $map[strtolower($provider)] ?? null;
    }

    public function initiateCollection(
        string $phoneNumber,
        float $amount,
        string $reference,
        string $narrative,
        ?string $provider = null
    ): array {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'YoPayments is not configured. Add API credentials in SuperAdmin settings.',
            ];
        }

        if ($this->shouldSimulate()) {
            return [
                'success' => false,
                'message' => 'YoPayments is disabled or missing credentials. Enable it in SuperAdmin settings.',
            ];
        }

        @set_time_limit(max(90, (int) config('yopayments.timeout', 45) + 15));

        $config = $this->config();
        $msisdn = $this->normalizeMsisdn($phoneNumber);
        $providerCode = $this->resolveProviderCode($provider);
        $ipnUrl = $this->ipnUrl();

        $xml = $this->buildDepositXml(
            $config['api_username'],
            $config['api_password'],
            $msisdn,
            $amount,
            $narrative,
            $reference,
            $providerCode,
            $ipnUrl
        );

        $this->log('info', 'YoPayments collection request', [
            'reference' => $reference,
            'msisdn' => $msisdn,
            'amount' => $amount,
            'provider_code' => $providerCode,
            'environment' => $config['environment'],
            'api_url' => $config['api_url'],
            'ipn_url' => $ipnUrl,
        ]);

        try {
            $response = Http::withOptions([
                'verify' => (bool) config('yopayments.verify_ssl', false),
                'connect_timeout' => 10,
                'timeout' => (int) config('yopayments.timeout', 45),
            ])
                ->withHeaders([
                    'Content-Type' => 'text/xml',
                    'Content-transfer-encoding' => 'text',
                ])
                ->withBody($xml, 'text/xml')
                ->post($config['api_url']);

            if (! $response->successful()) {
                $this->log('warning', 'YoPayments HTTP error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'reference' => $reference,
                ]);

                return [
                    'success' => false,
                    'message' => 'YoPayments gateway returned HTTP ' . $response->status() . '. Please try again.',
                ];
            }

            $parsed = $this->parseResponse($response->body());

            $this->log('info', 'YoPayments collection response', [
                'reference' => $reference,
                'parsed' => $parsed,
                'raw_body' => $response->body(),
            ]);

            if (($parsed['Status'] ?? '') === 'ERROR') {
                $message = $parsed['ErrorMessage']
                    ?? $parsed['StatusMessage']
                    ?? 'YoPayments rejected the payment request.';

                return [
                    'success' => false,
                    'message' => $message,
                    'yo_response' => $parsed,
                ];
            }

            if (($parsed['Status'] ?? '') !== 'OK') {
                return [
                    'success' => false,
                    'message' => $parsed['StatusMessage'] ?? 'Unexpected YoPayments response.',
                    'yo_response' => $parsed,
                ];
            }

            $transactionStatus = strtoupper((string) ($parsed['TransactionStatus'] ?? ''));

            if (in_array($transactionStatus, ['FAILED', 'INDETERMINATE'], true)) {
                return [
                    'success' => false,
                    'message' => $parsed['StatusMessage'] ?? 'Payment request failed.',
                    'yo_response' => $parsed,
                ];
            }

            return [
                'success' => true,
                'message' => 'PIN prompt sent to ' . $phoneNumber . '. Approve the payment on your phone.',
                'reference' => $reference,
                'transaction_reference' => $parsed['TransactionReference'] ?? null,
                'transaction_status' => $transactionStatus ?: 'PENDING',
                'environment' => $config['environment'],
                'yo_response' => $parsed,
            ];
        } catch (\Throwable $e) {
            $this->log('error', 'YoPayments request failed', [
                'reference' => $reference,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            $message = 'Could not reach YoPayments. Please try again shortly.';
            if (Str::contains($e->getMessage(), ['SSL certificate', 'cURL error 60'])) {
                $message = 'Could not connect to YoPayments (SSL certificate issue on this server). Contact support or set YOPAYMENTS_VERIFY_SSL=false.';
            } elseif (Str::contains($e->getMessage(), ['timed out', 'Timeout'])) {
                $message = 'YoPayments took too long to respond. Please try again.';
            }

            return [
                'success' => false,
                'message' => $message,
            ];
        }
    }

    public function checkTransactionStatus(string $transactionReference, ?string $externalReference = null): array
    {
        if (! $this->isConfigured()) {
            return ['success' => false, 'message' => 'YoPayments is not configured.'];
        }

        $config = $this->config();
        $xml = '<?xml version="1.0" encoding="UTF-8"?><AutoCreate><Request>';
        $xml .= '<APIUsername>' . $this->escapeXml($config['api_username']) . '</APIUsername>';
        $xml .= '<APIPassword>' . $this->escapeXml($config['api_password']) . '</APIPassword>';
        $xml .= '<Method>actransactioncheckstatus</Method>';
        $xml .= '<TransactionReference>' . $this->escapeXml($transactionReference) . '</TransactionReference>';

        if ($externalReference) {
            $xml .= '<PrivateTransactionReference>' . $this->escapeXml($externalReference) . '</PrivateTransactionReference>';
        }

        $xml .= '</Request></AutoCreate>';

        try {
            $response = Http::withOptions([
                'verify' => (bool) config('yopayments.verify_ssl', false),
                'timeout' => (int) config('yopayments.timeout', 45),
            ])
                ->withHeaders([
                    'Content-Type' => 'text/xml',
                    'Content-transfer-encoding' => 'text',
                ])
                ->withBody($xml, 'text/xml')
                ->post($config['api_url']);

            return [
                'success' => $response->successful(),
                'parsed' => $this->parseResponse($response->body()),
                'raw_body' => $response->body(),
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function normalizeIpnPayload(array $payload): array
    {
        return [
            'date_time' => $payload['date_time'] ?? null,
            'amount' => $payload['amount'] ?? null,
            'narrative' => $payload['narrative'] ?? null,
            'network_ref' => $payload['network_ref'] ?? null,
            'external_ref' => $payload['external_ref'] ?? $payload['ExternalReference'] ?? null,
            'msisdn' => $payload['msisdn'] ?? $payload['Msisdn'] ?? null,
            'signature' => $payload['signature'] ?? $payload['Signature'] ?? null,
            'failed_transaction_reference' => $payload['failed_transaction_reference'] ?? null,
            'transaction_init_date' => $payload['transaction_init_date'] ?? $payload['transaction_date'] ?? null,
            'verification' => $payload['verification'] ?? null,
        ];
    }

    public function normalizeMsisdn(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if (Str::startsWith($digits, '0')) {
            $digits = '256' . substr($digits, 1);
        } elseif (strlen($digits) === 9 && Str::startsWith($digits, '7')) {
            $digits = '256' . $digits;
        }

        return $digits;
    }

    protected function ipnUrl(): ?string
    {
        if (! config('yopayments.send_ipn', true)) {
            return null;
        }

        $configured = trim((string) config('yopayments.ipn_url', ''));
        $url = $configured !== '' ? $configured : url('/api/mobile-money/webhook');
        $host = parse_url($url, PHP_URL_HOST);

        if (in_array($host, ['127.0.0.1', 'localhost', '::1'], true)) {
            $this->log('warning', 'YoPayments IPN URL is localhost — callbacks will not reach this server. Configure YOPAYMENTS_IPN_URL to a public HTTPS URL.', [
                'url' => $url,
            ]);

            return null;
        }

        return $url;
    }

    protected function buildDepositXml(
        string $username,
        string $password,
        string $msisdn,
        float $amount,
        string $narrative,
        string $externalReference,
        ?string $accountProviderCode,
        ?string $ipnUrl
    ): string {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<AutoCreate><Request>';
        $xml .= '<APIUsername>' . $this->escapeXml($username) . '</APIUsername>';
        $xml .= '<APIPassword>' . $this->escapeXml($password) . '</APIPassword>';
        $xml .= '<Method>acdepositfunds</Method>';
        $xml .= '<NonBlocking>TRUE</NonBlocking>';
        $xml .= '<Account>' . $this->escapeXml($msisdn) . '</Account>';

        if ($accountProviderCode) {
            $xml .= '<AccountProviderCode>' . $this->escapeXml($accountProviderCode) . '</AccountProviderCode>';
        }

        $xml .= '<Amount>' . $this->escapeXml((string) (int) round($amount)) . '</Amount>';
        $xml .= '<Narrative>' . $this->escapeXml($narrative) . '</Narrative>';
        $xml .= '<ExternalReference>' . $this->escapeXml($externalReference) . '</ExternalReference>';

        if ($ipnUrl) {
            $xml .= '<InstantNotificationUrl>' . $this->escapeXml($ipnUrl) . '</InstantNotificationUrl>';
            $xml .= '<FailureNotificationUrl>' . $this->escapeXml($ipnUrl) . '</FailureNotificationUrl>';
        }

        $xml .= '</Request></AutoCreate>';

        return $xml;
    }

    protected function parseResponse(string $body): array
    {
        $body = trim($body);

        if ($body === '') {
            return [];
        }

        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($xml === false || ! isset($xml->Response)) {
            return [];
        }

        $response = $xml->Response;
        $result = [];

        foreach ($response->children() as $child) {
            $result[$child->getName()] = (string) $child;
        }

        return $result;
    }

    protected function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
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
