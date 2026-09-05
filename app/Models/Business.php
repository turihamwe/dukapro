<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use App\Modules\ModuleKeys;
use App\Services\BusinessModuleService;
use App\Services\ModuleBillingService;
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
        'billing_grandfathered',
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
        'billing_grandfathered' => 'boolean',
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

    public function businessModules(): HasMany
    {
        return $this->hasMany(BusinessModule::class);
    }

    public function clearModuleCache(): void
    {
        app(BusinessModuleService::class)->forgetBusiness($this);

        if ($this->relationLoaded('businessModules')) {
            unset($this->relations['businessModules']);
        }
    }

    public function hasModule(string $moduleKey): bool
    {
        return app(ModuleBillingService::class)->isAccessible($this, $moduleKey);
    }

    public function hasModuleEnabled(string $moduleKey): bool
    {
        return app(BusinessModuleService::class)->isEnabled($this, $moduleKey);
    }

    public function moduleSetting(string $moduleKey, string $settingKey, $default = null)
    {
        return app(BusinessModuleService::class)->setting($this, $moduleKey, $settingKey, $default);
    }

    public function usesProductVariants(): bool
    {
        return $this->hasModule(ModuleKeys::CATALOG_VARIANTS);
    }

    public function usesShiftWaiterMode(): bool
    {
        return $this->hasModule(ModuleKeys::BAR_SHIFT);
    }

    public function hasRestaurantWaitersSetting(): bool
    {
        if (! $this->usesRestaurantMode()) {
            return false;
        }

        return (bool) $this->moduleSetting(ModuleKeys::RESTAURANT, 'use_waiters', false);
    }

    /**
     * Waiter picker at POS, sale attribution, and shift balancing.
     */
    public function usesWaiterAssignment(): bool
    {
        return $this->usesShiftWaiterMode() || $this->hasRestaurantWaitersSetting();
    }

    public function usesShiftBalancing(): bool
    {
        return $this->usesWaiterAssignment();
    }

    public function usesRestaurantMode(): bool
    {
        return $this->hasModule(ModuleKeys::RESTAURANT);
    }

    public function usesRestaurantTables(): bool
    {
        return $this->usesRestaurantMode()
            && $this->hasRestaurantTablesSetting();
    }

    public function hasRestaurantTablesSetting(): bool
    {
        if (! $this->usesRestaurantMode()) {
            return false;
        }

        return (bool) $this->moduleSetting(ModuleKeys::RESTAURANT, 'use_tables', false);
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
