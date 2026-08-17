<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\Business;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantRegistrationService
{
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $slug = $this->uniqueSlug($data['business_name']);

            $business = Business::create([
                'name' => $data['business_name'],
                'slug' => $slug,
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'currency' => $data['currency'] ?? 'KES',
                'is_active' => true,
                'trial_ends_at' => Carbon::now()->addDays(30),
                'subscription_status' => SubscriptionStatus::TRIAL,
                'subscription_amount' => 1500,
            ]);

            return User::create([
                'business_id' => $business->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => UserRole::OWNER,
                'is_active' => true,
            ]);
        });
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
}
