<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

class UsernameService
{
    public function generateUnique(string $base, ?int $ignoreUserId = null): string
    {
        $slug = Str::slug($base) ?: 'user';
        $username = $slug;
        $counter = 1;

        while ($this->exists($username, $ignoreUserId)) {
            $username = $slug . $counter;
            $counter++;
        }

        return $username;
    }

    public function exists(string $username, ?int $ignoreUserId = null): bool
    {
        $query = User::where('username', $username);

        if ($ignoreUserId) {
            $query->where('id', '!=', $ignoreUserId);
        }

        return $query->exists();
    }
}
