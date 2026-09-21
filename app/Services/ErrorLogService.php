<?php

namespace App\Services;

use App\Models\ErrorLog;
use App\Models\SystemSetting;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ErrorLogService
{
    protected const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        '_token',
        'api_key',
        'api_token',
        'secret',
        'authorization',
        'credit_card',
        'card_number',
        'cvv',
    ];

    public function enabled(): bool
    {
        return SystemSetting::isErrorTrackingEnabled();
    }

    public function envDisabled(): bool
    {
        return ! config('error_tracking.enabled', true);
    }

    public function logBackendException(Throwable $exception, ?Request $request = null): void
    {
        if (! $this->enabled() || ! $this->shouldReportException($exception)) {
            return;
        }

        $request = $request ?: request();

        $payload = $this->mergeClientContext(
            $request,
            $this->attachHttpStatus($exception, $this->buildRequestPayload($request))
        );

        $this->persist([
            'environment' => ErrorLog::ENV_BACKEND,
            'exception_class' => get_class($exception),
            'error_message' => $this->truncateMessage($this->exceptionMessage($exception)),
            'stack_trace' => $this->formatStackTrace($exception),
            'url' => $request ? $this->truncateUrl($request->fullUrl()) : null,
            'device_info' => $this->parseDeviceInfo($request ? $request->userAgent() : null),
            'payload' => $payload,
            'business_id' => $this->resolveBusinessId($request),
            'user_id' => $this->resolveUserId($request),
        ]);
    }

    public function logFrontendReport(array $data, ?Request $request = null): ?ErrorLog
    {
        if (! $this->enabled()) {
            return null;
        }

        $request = $request ?: request();

        $payload = $this->mergeClientContext($request, [
            'frontend' => $this->sanitizePayload(is_array($data['payload'] ?? null) ? $data['payload'] : []),
        ]);

        return $this->persist([
            'environment' => ErrorLog::ENV_FRONTEND,
            'exception_class' => isset($data['source']) ? (string) $data['source'] : null,
            'error_message' => $this->truncateMessage((string) ($data['message'] ?? 'Frontend error')),
            'stack_trace' => isset($data['stack']) ? Str::limit((string) $data['stack'], 65000) : null,
            'url' => $this->truncateUrl((string) ($data['url'] ?? ($request ? $request->fullUrl() : ''))),
            'device_info' => $this->parseDeviceInfo((string) ($data['user_agent'] ?? ($request ? $request->userAgent() : ''))),
            'payload' => $payload,
            'business_id' => $this->resolveBusinessId($request),
            'user_id' => $this->resolveUserId($request),
        ]);
    }

    public function sanitizePayload(array $payload): array
    {
        $sanitized = [];

        foreach ($payload as $key => $value) {
            if ($this->isSensitiveKey($key)) {
                $sanitized[$key] = '[redacted]';
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizePayload($value);
                continue;
            }

            if (is_string($value)) {
                $sanitized[$key] = Str::limit($value, 2000);
                continue;
            }

            if (is_scalar($value) || $value === null) {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    public function parseDeviceInfo(?string $userAgent): array
    {
        $ua = trim((string) $userAgent);

        if ($ua === '') {
            return [
                'raw' => '',
                'browser' => 'Unknown',
                'os' => 'Unknown',
                'device_type' => 'unknown',
            ];
        }

        $deviceType = 'desktop';
        if (preg_match('/Mobile|Android.*Mobile|iPhone|iPod|BlackBerry|IEMobile|Opera Mini/i', $ua)) {
            $deviceType = 'mobile';
        } elseif (preg_match('/iPad|Tablet|Android(?!.*Mobile)/i', $ua)) {
            $deviceType = 'tablet';
        }

        $browser = 'Unknown';
        if (preg_match('/Edg\/([\d\.]+)/i', $ua)) {
            $browser = 'Edge';
        } elseif (preg_match('/Chrome\/([\d\.]+)/i', $ua) && ! preg_match('/Edg/i', $ua)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Firefox\/([\d\.]+)/i', $ua)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Version\/([\d\.]+).*Safari/i', $ua)) {
            $browser = 'Safari';
        }

        $os = 'Unknown';
        if (preg_match('/Windows NT/i', $ua)) {
            $os = 'Windows';
        } elseif (preg_match('/Android/i', $ua)) {
            $os = 'Android';
        } elseif (preg_match('/iPhone|iPad|iPod/i', $ua)) {
            $os = 'iOS';
        } elseif (preg_match('/Mac OS X/i', $ua)) {
            $os = 'macOS';
        } elseif (preg_match('/Linux/i', $ua)) {
            $os = 'Linux';
        }

        return [
            'raw' => Str::limit($ua, 500),
            'browser' => $browser,
            'os' => $os,
            'device_type' => $deviceType,
        ];
    }

    protected function shouldReportException(Throwable $exception): bool
    {
        if (app()->runningInConsole()) {
            return false;
        }

        if ($exception instanceof ValidationException) {
            return false;
        }

        if ($exception instanceof AuthenticationException) {
            return false;
        }

        if ($exception instanceof ModelNotFoundException) {
            return $this->shouldReportHttpStatus(404);
        }

        if ($exception instanceof TokenMismatchException) {
            return $this->shouldReportHttpStatus(419);
        }

        if ($exception instanceof HttpExceptionInterface) {
            return $this->shouldReportHttpStatus($exception->getStatusCode());
        }

        return true;
    }

    protected function attachHttpStatus(Throwable $exception, ?array $payload): ?array
    {
        $status = $this->resolveHttpStatus($exception);
        if ($status === null) {
            return $payload;
        }

        return array_merge($payload ?? [], ['http_status' => $status]);
    }

    protected function resolveHttpStatus(Throwable $exception): ?int
    {
        if ($exception instanceof HttpExceptionInterface) {
            return $exception->getStatusCode();
        }

        if ($exception instanceof ModelNotFoundException) {
            return 404;
        }

        if ($exception instanceof TokenMismatchException) {
            return 419;
        }

        return null;
    }

    protected function shouldReportHttpStatus(int $status): bool
    {
        $serverFrom = (int) config('error_tracking.report_server_errors_from_status', 500);
        if ($status >= $serverFrom) {
            return true;
        }

        $allowed = config('error_tracking.report_http_status_codes', []);

        return in_array($status, $allowed, true);
    }

    protected function exceptionMessage(Throwable $exception): string
    {
        $message = trim($exception->getMessage());

        if ($message !== '') {
            return $message;
        }

        if ($exception instanceof HttpExceptionInterface) {
            return 'HTTP '.$exception->getStatusCode();
        }

        return get_class($exception);
    }

    protected function buildRequestPayload(?Request $request): ?array
    {
        if (! $request) {
            return null;
        }

        $payload = [
            'method' => $request->method(),
            'route' => optional($request->route())->getName(),
            'input' => $this->sanitizePayload($request->except($this->fileKeys($request))),
        ];

        return $payload;
    }

    protected function fileKeys(?Request $request): array
    {
        if (! $request) {
            return [];
        }

        return array_keys($request->allFiles());
    }

    protected function resolveBusinessId(?Request $request): ?int
    {
        $user = $request ? $request->user() : auth()->user();

        if ($user && $user->business_id) {
            return (int) $user->business_id;
        }

        $routeBusiness = $request ? $request->route('business') : null;
        if (is_object($routeBusiness) && isset($routeBusiness->id)) {
            return (int) $routeBusiness->id;
        }

        return null;
    }

    protected function mergeClientContext(?Request $request, ?array $payload): ?array
    {
        $payload = $payload ?? [];
        $payload['client'] = $this->buildClientContext($request);

        return $payload;
    }

    protected function buildClientContext(?Request $request): array
    {
        if (! $request) {
            return ['authenticated' => false];
        }

        $context = [
            'authenticated' => auth()->check(),
        ];

        if (config('error_tracking.store_ip', true)) {
            $context['ip'] = $request->ip();
            $country = $request->header('CF-IPCountry');
            if (is_string($country) && $country !== '' && strtoupper($country) !== 'XX') {
                $context['country_code'] = strtoupper($country);
            }
        }

        $user = $request->user();
        if ($user) {
            $context['user'] = array_filter([
                'id' => (int) $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ], function ($value) {
                return $value !== null && $value !== '';
            });
        }

        return $context;
    }

    protected function resolveUserId(?Request $request): ?int
    {
        $user = $request ? $request->user() : auth()->user();

        return $user ? (int) $user->id : null;
    }

    protected function formatStackTrace(Throwable $exception): string
    {
        return Str::limit($exception->getTraceAsString(), 65000);
    }

    protected function truncateMessage(string $message): string
    {
        return Str::limit(trim($message), 2000);
    }

    protected function truncateUrl(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        return Str::limit($url, 2000);
    }

    protected function isSensitiveKey($key): bool
    {
        $normalized = strtolower((string) $key);

        foreach (self::SENSITIVE_KEYS as $sensitive) {
            if ($normalized === $sensitive || Str::contains($normalized, $sensitive)) {
                return true;
            }
        }

        return false;
    }

    protected function persist(array $attributes): ?ErrorLog
    {
        if (! $this->shouldPersist($attributes)) {
            return null;
        }

        try {
            return ErrorLog::query()->create($attributes);
        } catch (Throwable $exception) {
            logger()->error('Failed to persist error log entry.', [
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    protected function shouldPersist(array $attributes): bool
    {
        if ($this->resolveHttpStatusFromAttributes($attributes) !== 404) {
            return true;
        }

        $dedupeKey = null;
        if (config('error_tracking.dedupe_404.enabled', true)) {
            $dedupeKey = $this->dedupe404CacheKey($attributes);
            if (Cache::has($dedupeKey)) {
                return false;
            }
        }

        if (! $this->passes404Sampling()) {
            return false;
        }

        if ($dedupeKey !== null) {
            $ttl = (int) config('error_tracking.dedupe_404.ttl_seconds', 3600);
            if (! Cache::add($dedupeKey, 1, $ttl)) {
                return false;
            }
        }

        return true;
    }

    protected function resolveHttpStatusFromAttributes(array $attributes): ?int
    {
        $payload = $attributes['payload'] ?? null;
        if (! is_array($payload)) {
            return null;
        }

        $status = $payload['http_status'] ?? null;

        return $status !== null ? (int) $status : null;
    }

    protected function dedupe404CacheKey(array $attributes): string
    {
        $businessId = $attributes['business_id'] ?? 'none';
        $environment = $attributes['environment'] ?? 'unknown';
        $path = $this->normalizeUrlPathForDedupe($attributes['url'] ?? null);

        return 'error_tracking:404:'.hash('sha256', $environment.'|'.$businessId.'|'.$path);
    }

    protected function normalizeUrlPathForDedupe(?string $url): string
    {
        if ($url === null || $url === '') {
            return '/';
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            return '/';
        }

        $path = '/'.strtolower(trim($path, '/'));

        return $path === '/' ? '/' : rtrim($path, '/');
    }

    protected function passes404Sampling(): bool
    {
        $rate = (float) config('error_tracking.sample_404_rate', 1.0);
        if ($rate >= 1.0) {
            return true;
        }
        if ($rate <= 0.0) {
            return false;
        }

        return random_int(1, 10000) <= (int) round($rate * 10000);
    }
}
