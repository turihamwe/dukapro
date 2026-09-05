<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Mail\WelcomeOwnerMail;
use App\Models\Business;
use App\Models\User;
use App\Services\BranchService;
use App\Services\BusinessModuleService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TenantRegistrationService
{
    public function register(array $data): User
    {
        $user = DB::transaction(function () use ($data) {
            $slug = $this->uniqueSlug($data['business_name']);

            $business = Business::create([
                'name' => $data['business_name'],
                'business_type' => $data['business_type'],
                'slug' => $slug,
                'portal_slug' => $this->uniquePortalSlug($data['business_name']),
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'sponsor_id' => $data['sponsor_id'] ?? null,
                'currency' => 'UGX',
                'currency_symbol' => 'UGX',
                'currency_position' => 'prefix',
                'is_active' => true,
                'trial_ends_at' => Carbon::now()->addDays(30),
                'subscription_status' => SubscriptionStatus::TRIAL,
                'subscription_amount' => 1500,
                'employees_onboarding_complete' => false,
            ]);

            app(ExpenseService::class)->seedDefaultCategories((int) $business->id);

            app(BranchService::class)->createDefault($business);

            if (\App\Enums\BusinessType::isHospitality($data['business_type'])) {
                $settings = $business->settings ?? [];
                $settings['restaurant_mode'] = true;
                $settings['shift_waiter_mode'] = true;
                $business->update(['settings' => $settings]);
            }

            app(BusinessModuleService::class)->syncFromLegacySettings(
                $business->fresh(),
                BusinessModuleService::SOURCE_PRESET
            );

            return User::create([
                'business_id' => $business->id,
                'name' => $data['name'],
                'username' => strtolower($data['username']),
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => UserRole::OWNER,
                'is_active' => true,
                'ui_theme' => 'modern',
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
