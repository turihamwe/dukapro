<?php

namespace App\Services;

use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthLoginService
{
    public function resolveLoginField(string $login): string
    {
        return filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
    }

    public function findUser(string $login, ?Business $business = null): ?User
    {
        $field = $this->resolveLoginField($login);

        $query = User::query()->where($field, $login);

        if ($business) {
            $query->where('business_id', $business->id);
        } else {
            $query->where('is_super_admin', false)->whereNotNull('business_id');
        }

        return $query->first();
    }

    public function attempt(string $login, string $password, bool $remember = false, ?Business $business = null): ?User
    {
        $user = $this->findUser($login, $business);

        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        Auth::login($user, $remember);

        return $user;
    }
}
