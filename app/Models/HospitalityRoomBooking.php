<?php

namespace App\Models;

use App\Enums\HospitalityBookingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HospitalityRoomBooking extends Model
{
    protected $fillable = [
        'business_id',
        'product_id',
        'branch_id',
        'guest_name',
        'guest_phone',
        'check_in',
        'check_out',
        'status',
        'notes',
        'checked_in_at',
        'checked_out_at',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function isBlocking(): bool
    {
        return in_array($this->status, HospitalityBookingStatus::blocking(), true);
    }
}
