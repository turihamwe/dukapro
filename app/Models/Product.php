<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'brand_id',
        'parent_id',
        'name',
        'sku',
        'description',
        'price',
        'cost_price',
        'variant_attributes',
        'attribute_values',
        'measurement_unit',
        'stock_quantity',
        'critical_threshold',
        'is_active',
        'is_sellable',
    ];

    protected $casts = [
        'price' => 'float',
        'cost_price' => 'float',
        'variant_attributes' => 'array',
        'attribute_values' => 'array',
        'stock_quantity' => 'float',
        'critical_threshold' => 'integer',
        'is_active' => 'boolean',
        'is_sellable' => 'boolean',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'parent_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(Product::class, 'parent_id');
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function damages(): HasMany
    {
        return $this->hasMany(Damage::class);
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    public function scopeCatalog($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeSellable($query)
    {
        return $query->where('is_sellable', true);
    }

    public function isVariableParent(): bool
    {
        if ($this->parent_id !== null) {
            return false;
        }

        if ($this->relationLoaded('variants')) {
            return $this->variants->isNotEmpty();
        }

        return $this->variants()->exists();
    }

    public function displayName(): string
    {
        if (empty($this->attribute_values)) {
            return $this->name;
        }

        $parts = [];
        foreach ($this->attribute_values as $attribute => $value) {
            $parts[] = $attribute . ': ' . $value;
        }

        return $this->name . ' (' . implode(', ', $parts) . ')';
    }

    public function formattedAttributes(): ?string
    {
        if (empty($this->attribute_values)) {
            return null;
        }

        $parts = [];
        foreach ($this->attribute_values as $attribute => $value) {
            $parts[] = $attribute . ': ' . $value;
        }

        return implode(' · ', $parts);
    }
}
