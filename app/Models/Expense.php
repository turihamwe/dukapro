<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use BelongsToBranch, BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'branch_id',
        'user_id',
        'title',
        'category',
        'description',
        'amount',
        'expense_date',
        'payment_method',
        'receipt_reference',
    ];

    protected $casts = [
        'amount' => 'float',
        'expense_date' => 'date',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
