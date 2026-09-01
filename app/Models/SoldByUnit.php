<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SoldByUnit extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'business_id',
        'name',
        'slug',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function (SoldByUnit $unit) {
            if (! $unit->slug) {
                $unit->slug = static::uniqueSlug($unit->business_id, $unit->name);
            }
        });
    }

    public static function uniqueSlug(int $businessId, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'unit';
        $slug = $base;
        $counter = 1;

        while (static::query()
            ->where('business_id', $businessId)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base . '-' . $counter++;
        }

        return $slug;
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
