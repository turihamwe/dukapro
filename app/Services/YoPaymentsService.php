<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class YoPaymentsService
{
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
        return $this->config()['environment'] !== 'live';
    }

    public function shouldSimulate(): bool
    {
        return $this->isSandbox() || ! $this->isConfigured();
    }

    public function config(): array
    {
        $keys = config('yopayments.settings_keys');

        return [
            'enabled' => $this->boolSetting($keys['enabled'], config('yopayments.enabled')),
            'environment' => SystemSetting::get($keys['environment'], config('yopayments.environment')),
            'api_url' => $this->apiUrl(),
            'api_username' => SystemSetting::get($keys['api_username'], config('yopayments.api_username')),
            'api_password' => SystemSetting::get($keys['api_password'], config('yopayments.api_password')),
            'account_id' => SystemSetting::get($keys['account_id'], config('yopayments.account_id')),
        ];
    }

    public function apiUrl(): string
    {
        return $this->isSandbox()
            ? config('yopayments.sandbox_api_url')
            : config('yopayments.live_api_url');
    }

    public function initiateCollection(string $phoneNumber, float $amount, string $reference, string $narrative): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'YoPayments is not configured. Add API credentials in SuperAdmin settings.',
            ];
        }

        if ($this->shouldSimulate()) {
            return [
                'success' => false,
                'message' => 'YoPayments is in sandbox mode. Use simulation instead of the live API.',
            ];
        }

        @set_time_limit(max(90, (int) config('yopayments.timeout', 45) + 15));

        $config = $this->config();
        $msisdn = $this->normalizeMsisdn($phoneNumber);
        $ipnUrl = $this->ipnUrl();

        $xml = $this->buildDepositXml(
            $config['api_username'],
            $config['api_password'],
            $msisdn,
            $amount,
            $narrative,
            $reference,
            $config['account_id'] ?: null,
            $ipnUrl
        );

        $this->log('info', 'YoPayments collection request', [
            'reference' => $reference,
            'msisdn' => $msisdn,
            'amount' => $amount,
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

            if (($parsed['Status'] ?? '') !== 'OK') {
                $message = $parsed['StatusMessage'] ?? $parsed['ErrorMessage'] ?? 'YoPayments rejected the payment request.';

                return [
                    'success' => false,
                    'message' => $message,
                    'yo_response' => $parsed,
                ];
            }

            $transactionStatus = strtoupper((string) ($parsed['TransactionStatus'] ?? ''));

            if (in_array($transactionStatus, ['FAILED'], true)) {
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
                'transaction_status' => $transactionStatus,
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

        $url = url('/api/mobile-money/webhook');
        $host = parse_url($url, PHP_URL_HOST);

        if (in_array($host, ['127.0.0.1', 'localhost', '::1'], true)) {
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
        ?string $providerReferenceText,
        ?string $ipnUrl
    ): string {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<AutoCreate><Request>';
        $xml .= '<APIUsername>' . $this->escapeXml($username) . '</APIUsername>';
        $xml .= '<APIPassword>' . $this->escapeXml($password) . '</APIPassword>';
        $xml .= '<Method>acdepositfunds</Method>';
        $xml .= '<NonBlocking>TRUE</NonBlocking>';
        $xml .= '<Account>' . $this->escapeXml($msisdn) . '</Account>';
        $xml .= '<Amount>' . $this->escapeXml((string) (int) round($amount)) . '</Amount>';
        $xml .= '<Narrative>' . $this->escapeXml($narrative) . '</Narrative>';
        $xml .= '<ExternalReference>' . $this->escapeXml($externalReference) . '</ExternalReference>';

        if ($providerReferenceText) {
            $xml .= '<ProviderReferenceText>' . $this->escapeXml($providerReferenceText) . '</ProviderReferenceText>';
        }

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
