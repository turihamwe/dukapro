<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Mail\WelcomeOwnerMail;
use App\Models\Business;
use App\Models\SystemSetting;
use App\Models\User;
use Carbon\Carbon;
use App\Services\UsernameService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TenantRegistrationService
{
    protected UsernameService $usernameService;

    public function __construct(UsernameService $usernameService)
    {
        $this->usernameService = $usernameService;
    }

    public function register(array $data): User
    {
        $user = DB::transaction(function () use ($data) {
            $slug = $this->uniqueSlug($data['business_name']);

            $business = Business::create([
                'name' => $data['business_name'],
                'slug' => $slug,
                'portal_slug' => $this->uniquePortalSlug($data['business_name']),
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'currency' => $data['currency_symbol'] ?? SystemSetting::get('default_currency_symbol', 'UGX'),
                'currency_symbol' => $data['currency_symbol'] ?? SystemSetting::get('default_currency_symbol', 'UGX'),
                'currency_position' => $data['currency_position'] ?? SystemSetting::get('default_currency_position', 'prefix'),
                'is_active' => true,
                'trial_ends_at' => Carbon::now()->addDays(30),
                'subscription_status' => SubscriptionStatus::TRIAL,
                'subscription_amount' => 1500,
                'employees_onboarding_complete' => false,
            ]);

            return User::create([
                'business_id' => $business->id,
                'name' => $data['name'],
                'username' => $this->usernameService->generateUnique($data['name']),
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => UserRole::OWNER,
                'is_active' => true,
            ]);
        });

        $user->load('business');
        $this->sendWelcomeEmail($user);

        return $user;
    }

    protected function sendWelcomeEmail(User $user): void
    {
        try {
            Mail::to($user->email)->send(new WelcomeOwnerMail($user, $user->business));
        } catch (\Throwable $e) {
            Log::warning('Welcome email failed after registration', [
                'user_id' => $user->id,
                'business_id' => $user->business_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (Business::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    protected function uniquePortalSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'store';

        do {
            $portalSlug = $base . '-' . Str::lower(Str::random(8));
        } while (Business::where('portal_slug', $portalSlug)->exists());

        return $portalSlug;
    }
}
