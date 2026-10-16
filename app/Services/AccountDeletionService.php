<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Shareholder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountDeletionService
{
    public function refusalReason(User $user): ?string
    {
        if ($user->isPlatformAdmin()) {
            return 'Platform administrator accounts cannot be deleted from this page.';
        }

        if ($user->business_id && ! $user->isOwner()) {
            return 'This login was created by the shop owner. Ask that owner to remove it.';
        }

        if ($user->business_id && $user->isOwner() && $this->otherBusinessUsers($user)) {
            return 'This shop still has other staff logins. Remove those accounts first, then delete yours. That keeps the shop from being closed while other people still sign in.';
        }

        return null;
    }

    public function closesShop(User $user): bool
    {
        return (bool) $user->business_id
            && $user->isOwner()
            && ! $this->otherBusinessUsers($user);
    }

    public function delete(User $user): void
    {
        $reason = $this->refusalReason($user);
        if ($reason) {
            throw new \RuntimeException($reason);
        }

        $email = (string) $user->email;
        $closesShop = $this->closesShop($user);
        $businessId = $user->business_id ? (int) $user->business_id : null;

        DB::transaction(function () use ($user, $email, $closesShop, $businessId) {
            $this->scrubAffiliate($user);
            $this->scrubShareholder($user);
            $this->scrubAuditTrail($user);

            if ($closesShop && $businessId) {
                $this->closeShopPersonalData($businessId, $email);
            }

            $this->forgetCredentials($user, $email);

            if (method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }

            $user->forceDelete();
        });
    }

    protected function otherBusinessUsers(User $user): bool
    {
        return User::query()
            ->where('business_id', $user->business_id)
            ->where('id', '!=', $user->id)
            ->exists();
    }

    protected function scrubAffiliate(User $user): void
    {
        $profile = Affiliate::query()->where('user_id', $user->id)->first();
        if (! $profile) {
            return;
        }

        $profile->name = 'Deleted affiliate';
        $profile->email = 'deleted-affiliate-'.$profile->id.'@invalid.local';
        $profile->phone = null;
        $profile->application_message = null;
        $profile->is_active = false;
        $profile->save();
    }

    protected function scrubShareholder(User $user): void
    {
        $profile = Shareholder::query()->where('user_id', $user->id)->first();
        if (! $profile) {
            return;
        }

        $profile->name = 'Deleted shareholder';
        $profile->email = 'deleted-shareholder-'.$profile->id.'@invalid.local';
        $profile->phone = null;
        $profile->national_id = null;
        $profile->application_message = null;
        $profile->is_active = false;
        $profile->save();
    }

    protected function scrubAuditTrail(User $user): void
    {
        if (Schema::hasTable('audit_logs')) {
            DB::table('audit_logs')->where('user_id', $user->id)->update([
                'old_values' => null,
                'new_values' => null,
                'ip_address' => null,
                'user_agent' => null,
            ]);
        }

        if (Schema::hasTable('system_audit_logs') && Schema::hasColumn('system_audit_logs', 'user_id')) {
            $payload = [];
            if (Schema::hasColumn('system_audit_logs', 'ip_address')) {
                $payload['ip_address'] = null;
            }
            if ($payload !== []) {
                DB::table('system_audit_logs')->where('user_id', $user->id)->update($payload);
            }
        }
    }

    protected function closeShopPersonalData(int $businessId, string $email): void
    {
        $business = Business::query()->find($businessId);
        if ($business) {
            $business->email = null;
            $business->phone = null;
            $business->is_active = false;
            $business->save();
        }

        Customer::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->update([
                'name' => 'Deleted customer',
                'company_name' => null,
                'phone' => null,
                'email' => null,
                'address' => null,
                'notes' => null,
            ]);

        Supplier::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->update([
                'name' => 'Deleted supplier',
                'phone' => null,
                'email' => null,
                'notes' => null,
            ]);

        if (! Schema::hasTable('subscription_payments')) {
            return;
        }

        $payments = DB::table('subscription_payments')->where('business_id', $businessId)->get(['id', 'metadata']);
        foreach ($payments as $payment) {
            $meta = json_decode((string) $payment->metadata, true);
            if (! is_array($meta) || ! array_key_exists('phone_number', $meta)) {
                continue;
            }
            unset($meta['phone_number']);
            DB::table('subscription_payments')->where('id', $payment->id)->update([
                'metadata' => json_encode($meta),
            ]);
        }
    }

    protected function forgetCredentials(User $user, string $email): void
    {
        if (Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'user_id')) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        foreach (['password_resets', 'password_reset_tokens'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'email')) {
                DB::table($table)->where('email', $email)->delete();
            }
        }

        if (Schema::hasTable('notifications')) {
            DB::table('notifications')
                ->where('notifiable_id', $user->id)
                ->where('notifiable_type', User::class)
                ->delete();
        }
    }
}
