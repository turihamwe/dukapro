<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErrorLog extends Model
{
    public const ENV_BACKEND = 'backend';

    public const ENV_FRONTEND = 'frontend';

    protected $fillable = [
        'business_id',
        'user_id',
        'environment',
        'exception_class',
        'error_message',
        'stack_trace',
        'url',
        'device_info',
        'payload',
    ];

    protected $casts = [
        'device_info' => 'array',
        'payload' => 'array',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function deviceTypeLabel(): string
    {
        $type = $this->device_info['device_type'] ?? 'unknown';

        return ucfirst((string) $type);
    }

    public function httpStatus(): ?int
    {
        $status = $this->payload['http_status'] ?? null;

        return $status !== null ? (int) $status : null;
    }

    public function clientContext(): array
    {
        return is_array($this->payload['client'] ?? null) ? $this->payload['client'] : [];
    }

    public function clientUser(): array
    {
        $user = $this->clientContext()['user'] ?? [];

        return is_array($user) ? $user : [];
    }

    public function contactUsername(): ?string
    {
        if ($this->user && $this->user->username) {
            return $this->user->username;
        }

        $snapshot = $this->clientUser()['username'] ?? null;

        return $snapshot ? (string) $snapshot : null;
    }

    public function contactEmail(): ?string
    {
        if ($this->user && $this->user->email) {
            return $this->user->email;
        }

        $snapshot = $this->clientUser()['email'] ?? null;

        return $snapshot ? (string) $snapshot : null;
    }

    public function contactPhone(): ?string
    {
        if ($this->user && $this->user->phone) {
            return $this->user->phone;
        }

        $snapshot = $this->clientUser()['phone'] ?? null;

        return $snapshot ? (string) $snapshot : null;
    }

    public function contactName(): ?string
    {
        if ($this->user && $this->user->name) {
            return $this->user->name;
        }

        $snapshot = $this->clientUser()['name'] ?? null;

        return $snapshot ? (string) $snapshot : null;
    }

    public function countryLabel(): ?string
    {
        $code = $this->clientContext()['country_code'] ?? null;

        return $code ? (string) $code : null;
    }

    public function ipAddress(): ?string
    {
        $ip = $this->clientContext()['ip'] ?? null;

        return $ip ? (string) $ip : null;
    }

    public function isGuest(): bool
    {
        if ($this->user_id) {
            return false;
        }

        return ! ($this->clientContext()['authenticated'] ?? false);
    }
}
