<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Business extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sponsor_id',
        'name',
        'business_type',
        'slug',
        'portal_slug',
        'logo_path',
        'brand_color',
        'email',
        'phone',
        'address',
        'tax_number',
        'currency',
        'currency_symbol',
        'currency_position',
        'settings',
        'is_active',
        'trial_ends_at',
        'subscription_status',
        'subscription_ends_at',
        'subscription_amount',
        'sole_proprietor',
        'employees_onboarding_complete',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'sole_proprietor' => 'boolean',
        'employees_onboarding_complete' => 'boolean',
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
        'subscription_amount' => 'float',
    ];

    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class, 'sponsor_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class);
    }

    public function productAttributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class);
    }

    public function soldByUnits(): HasMany
    {
        return $this->hasMany(SoldByUnit::class);
    }

    public function usesProductVariants(): bool
    {
        return (bool) ($this->settings['use_product_variants'] ?? false);
    }

    public function usesShiftWaiterMode(): bool
    {
        return (bool) ($this->settings['shift_waiter_mode'] ?? false);
    }

    public function usesRestaurantMode(): bool
    {
        if (! \App\Enums\BusinessType::isHospitality($this->business_type)) {
            return false;
        }

        return (bool) ($this->settings['restaurant_mode'] ?? true);
    }

    public function usesRestaurantTables(): bool
    {
        return $this->usesRestaurantMode()
            && $this->hasRestaurantTablesSetting();
    }

    public function hasRestaurantTablesSetting(): bool
    {
        if (! \App\Enums\BusinessType::isHospitality($this->business_type)) {
            return false;
        }

        return (bool) ($this->settings['use_restaurant_tables'] ?? false);
    }

    public function isHospitality(): bool
    {
        return \App\Enums\BusinessType::isHospitality($this->business_type);
    }

    public function suggestsShiftWaiterMode(): bool
    {
        return \App\Enums\BusinessType::isHospitality($this->business_type);
    }

    public function shiftWaiterBalances(): HasMany
    {
        return $this->hasMany(ShiftWaiterBalance::class);
    }

    public function kitchenOrders(): HasMany
    {
        return $this->hasMany(KitchenOrder::class);
    }

    public function restaurantTables(): HasMany
    {
        return $this->hasMany(RestaurantTable::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function subscriptionPayments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function reconciliations(): HasMany
    {
        return $this->hasMany(EndOfDayReconciliation::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function damages(): HasMany
    {
        return $this->hasMany(Damage::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        if ($field === 'portal_slug') {
            return static::where('portal_slug', $value)->firstOrFail();
        }

        return parent::resolveRouteBinding($value, $field);
    }

    public function logoUrl(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        return asset('storage/' . ltrim($this->logo_path, '/'));
    }

    public function portalLoginUrl(): string
    {
        return route('business.login', ['portal' => $this->portal_slug]);
    }

    public function formatMoney($amount, int $decimals = 0): string
    {
        return format_money($amount, $this, $decimals);
    }

    public function isSubscriptionExpired(): bool
    {
        if ($this->subscription_status === SubscriptionStatus::ACTIVE) {
            if ($this->subscription_ends_at && $this->subscription_ends_at->isPast()) {
                return true;
            }

            return false;
        }

        if ($this->subscription_status === SubscriptionStatus::TRIAL) {
            return $this->trial_ends_at && $this->trial_ends_at->isPast();
        }

        return in_array($this->subscription_status, [
            SubscriptionStatus::INACTIVE,
            SubscriptionStatus::EXPIRED,
        ], true);
    }

    public function activateSubscription(int $days = 30): void
    {
        $startsFrom = $this->subscription_ends_at && $this->subscription_ends_at->isFuture()
            ? $this->subscription_ends_at
            : Carbon::now();

        $this->update([
            'subscription_status' => SubscriptionStatus::ACTIVE,
            'subscription_ends_at' => $startsFrom->copy()->addDays($days),
        ]);
    }

    public function staffUsers()
    {
        return $this->users()->where('role', '!=', \App\Enums\UserRole::OWNER);
    }
}
