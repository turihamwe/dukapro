<?php

namespace App\Services;

use App\Models\Business;
use App\Models\EfrisSetting;
use App\Models\User;
use App\Support\EfrisCompliance;
use App\Support\WeafPlatformCredentials;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class WeafAccountProvisioner
{
    public const STATUS_NOT_STARTED = 'not_started';

    public const STATUS_REGISTERED = 'registered';

    public const STATUS_TOKEN_READY = 'token_ready';

    public const STATUS_COMPANY_PENDING = 'company_pending';

    public const STATUS_CONNECTED = 'connected';

    public const STATUS_EMAIL_VERIFICATION = 'email_verification';

    public const STATUS_FAILED = 'failed';

    /** @var EfrisService */
    protected $efrisService;

    public function __construct(EfrisService $efrisService)
    {
        $this->efrisService = $efrisService;
    }

    public function connect(Business $business, User $user, ?string $existingPassword = null): array
    {
        $email = strtolower(trim((string) ($business->email ?: $user->email)));
        $tin = $this->normalizeTin($business->tax_number);

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Add a business email on your profile before connecting EFRIS.');
        }

        if ($tin === null) {
            throw new RuntimeException('Add your company TIN under Tax / registration number before connecting EFRIS.');
        }

        if (! EfrisCompliance::globallyEnabled()) {
            throw new RuntimeException('EFRIS is not available on this platform.');
        }

        if (! WeafPlatformCredentials::autoProvisionEnabled()) {
            throw new RuntimeException('Automatic WEAF registration is disabled. Contact DukaPro support.');
        }

        $settings = EfrisSetting::query()->firstOrNew(['business_id' => $business->id]);
        $settings->efris_tin = $tin;
        $settings->provisioning_error = null;
        $settings->save();

        if (WeafPlatformCredentials::usePlatformAccount()) {
            $platform = WeafPlatformCredentials::credentials();

            return $this->connectWithCredentials(
                $settings,
                $platform['email'],
                $platform['password'],
                $tin,
                false
            );
        }

        $password = $existingPassword ?: $settings->getDecryptedWeafPassword();

        if ($password) {
            return $this->connectWithCredentials($settings, $email, $password, $tin, false);
        }

        $password = Str::random(12) . random_int(10, 99);

        try {
            $this->registerWeafAccount($email, $password, $business, $user);
            $settings->weaf_email = $email;
            $settings->weaf_password = $password;
            $settings->provisioning_status = self::STATUS_REGISTERED;
            $settings->save();
        } catch (RuntimeException $e) {
            if ($this->isEmailAlreadyRegistered($e)) {
                throw new RuntimeException(
                    'A WEAF account already exists for ' . $email . '. Enter your WEAF password below and click Connect again.'
                );
            }

            throw $e;
        }

        return $this->connectWithCredentials($settings, $email, $password, $tin, true);
    }

    protected function connectWithCredentials(
        EfrisSetting $settings,
        string $email,
        string $password,
        string $tin,
        bool $freshRegistration
    ): array {
        try {
            $businessName = optional($settings->business)->name ?: 'Business';

            $tokenData = $this->efrisService->generateAccessToken(
                $email,
                $password,
                30,
                'DukaPro — ' . $businessName
            );
        } catch (RuntimeException $e) {
            $message = $e->getMessage();

            if ($this->isInactiveAccount($message)) {
                $settings->update([
                    'weaf_email' => $email,
                    'weaf_password' => $password,
                    'provisioning_status' => self::STATUS_EMAIL_VERIFICATION,
                    'provisioning_error' => 'Verify your WEAF account using the email sent to ' . $email . ', then click Connect again.',
                ]);

                return [
                    'status' => self::STATUS_EMAIL_VERIFICATION,
                    'message' => 'WEAF account created. Check ' . $email . ' to verify your account, then click Connect EFRIS again.',
                    'email' => $email,
                ];
            }

            if ($this->isInvalidPassword($message) && ! $freshRegistration) {
                $settings->update([
                    'provisioning_status' => self::STATUS_FAILED,
                    'provisioning_error' => 'WEAF password was rejected. Enter the correct password and try again.',
                ]);
            }

            throw $e;
        }

        $token = $tokenData['token'] ?? null;

        if (! $token) {
            throw new RuntimeException('WEAF did not return an API token.');
        }

        $companies = $tokenData['companies'] ?? [];
        $tinLinked = $this->companyIncludesTin($companies, $tin);
        $status = $tinLinked ? self::STATUS_CONNECTED : self::STATUS_COMPANY_PENDING;

        $settings->update([
            'weaf_email' => $email,
            'weaf_password' => $password,
            'efris_api_token' => $token,
            'weaf_token_expires_at' => $tokenData['expires_at'] ?? null,
            'provisioning_status' => $status,
            'provisioning_error' => $tinLinked
                ? null
                : 'WEAF account is ready. DukaPro support will link TIN ' . $tin . ' to your account, or add it in your WEAF dashboard.',
        ]);

        return [
            'status' => $status,
            'message' => $this->statusMessage($status, $email, $tin),
            'email' => $email,
            'companies' => $companies,
            'expires_at' => $tokenData['expires_at'] ?? null,
        ];
    }

    protected function registerWeafAccount(string $email, string $password, Business $business, User $user): void
    {
        $registerUrl = rtrim(config('efris.base_url'), '/') . '/register';
        $cookieJar = new \GuzzleHttp\Cookie\CookieJar;

        $page = Http::withOptions(['cookies' => $cookieJar])
            ->timeout(30)
            ->get($registerUrl);

        if ($page->failed()) {
            throw new RuntimeException('Could not reach WEAF registration (HTTP ' . $page->status() . ').');
        }

        $html = $page->body();

        if (! preg_match('/name="_token"\s+value="([^"]+)"/', $html, $matches)) {
            throw new RuntimeException('Could not start WEAF registration (missing security token).');
        }

        [$num1, $num2, $operation, $answer] = $this->generateCaptchaValues();

        $payload = [
            '_token' => $matches[1],
            'source' => 'dukapro',
            'product_interest' => config('efris.registration.product_interest', 'efris_api'),
            'name' => $business->name ?: $user->name,
            'email' => $email,
            'phone' => $this->normalizePhone($business->phone ?: $user->phone),
            'password' => $password,
            'password_confirmation' => $password,
            'captcha_answer' => $answer,
            'captcha_value_1' => $num1,
            'captcha_value_2' => $num2,
            'captcha_operation' => $operation,
        ];

        $response = Http::withOptions([
            'cookies' => $cookieJar,
            'allow_redirects' => false,
        ])
            ->timeout(30)
            ->asForm()
            ->post($registerUrl, $payload);

        if (in_array($response->status(), [301, 302, 303, 307, 308], true)) {
            return;
        }

        if ($response->successful()) {
            $body = $response->body();

            if ($this->responseIndicatesSuccess($body)) {
                return;
            }

            if ($this->responseIndicatesDuplicateEmail($body)) {
                throw new RuntimeException('EMAIL_ALREADY_REGISTERED');
            }

            throw new RuntimeException($this->extractRegistrationError($body));
        }

        throw new RuntimeException('WEAF registration failed (HTTP ' . $response->status() . ').');
    }

    protected function generateCaptchaValues(): array
    {
        $operation = random_int(0, 1) === 0 ? '+' : '-';

        if ($operation === '+') {
            $num1 = random_int(1, 50);
            $num2 = random_int(1, 50);
            $answer = $num1 + $num2;
        } else {
            $num1 = random_int(20, 69);
            $num2 = random_int(0, $num1);
            $answer = $num1 - $num2;
        }

        return [$num1, $num2, $operation, $answer];
    }

    protected function companyIncludesTin(array $companies, string $tin): bool
    {
        foreach ($companies as $company) {
            if ($this->normalizeTin($company['tin'] ?? null) === $tin) {
                return true;
            }
        }

        return false;
    }

    protected function normalizeTin(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        return $digits !== '' ? $digits : null;
    }

    protected function normalizePhone(?string $value): string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        if (strlen($digits) >= 9) {
            return $digits;
        }

        return '0700000000';
    }

    protected function isEmailAlreadyRegistered(RuntimeException $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'email_already_registered')
            || str_contains($message, 'already been taken')
            || str_contains($message, 'already registered');
    }

    protected function isInvalidPassword(string $message): bool
    {
        return str_contains(strtoupper($message), 'INVALID_PASSWORD');
    }

    protected function isInactiveAccount(string $message): bool
    {
        $upper = strtoupper($message);

        return str_contains($upper, 'ACCOUNT_INACTIVE')
            || str_contains($upper, 'EMAIL')
            || str_contains(strtolower($message), 'verify');
    }

    protected function responseIndicatesSuccess(string $body): bool
    {
        $lower = strtolower($body);

        return str_contains($lower, 'dashboard')
            || str_contains($lower, 'verification email')
            || str_contains($lower, 'registration successful');
    }

    protected function responseIndicatesDuplicateEmail(string $body): bool
    {
        $lower = strtolower($body);

        return str_contains($lower, 'already been taken')
            || str_contains($lower, 'already registered');
    }

    protected function extractRegistrationError(string $body): string
    {
        if (preg_match('/class="[^"]*error[^"]*"[^>]*>([^<]+)</i', $body, $matches)) {
            return trim(html_entity_decode(strip_tags($matches[1])));
        }

        return 'WEAF registration was rejected. Check your business email and phone, then try again.';
    }

    protected function statusMessage(string $status, string $email, string $tin): string
    {
        if ($status === self::STATUS_CONNECTED) {
            return 'EFRIS is connected. Fiscal receipts will be submitted automatically after each sale.';
        }

        if ($status === self::STATUS_COMPANY_PENDING) {
            return 'WEAF account is ready for ' . $email . '. Your TIN (' . $tin . ') still needs to be linked on WEAF — our team has been notified.';
        }

        if ($status === self::STATUS_EMAIL_VERIFICATION) {
            return 'Check ' . $email . ' to verify your WEAF account, then click Connect EFRIS again.';
        }

        return 'WEAF connection updated.';
    }
}
