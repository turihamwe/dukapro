<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateFieldFeedback extends Model
{
    public const STATUS_NEW = 'new';

    public const STATUS_REVIEWED = 'reviewed';

    public const CATEGORY_MERCHANT = 'merchant_issue';

    public const CATEGORY_TECHNICAL = 'technical';

    public const CATEGORY_BILLING = 'billing';

    public const CATEGORY_FEATURE = 'feature';

    public const CATEGORY_OTHER = 'other';

    protected $table = 'affiliate_field_feedback';

    protected $fillable = [
        'affiliate_id',
        'user_id',
        'category',
        'merchant_name',
        'location',
        'summary',
        'message',
        'contact_phone',
        'status',
    ];

    public static function categories(): array
    {
        return [
            self::CATEGORY_MERCHANT => 'Shop owner complaint or concern',
            self::CATEGORY_TECHNICAL => 'App or login problem',
            self::CATEGORY_BILLING => 'Pricing, payment, or subscription',
            self::CATEGORY_FEATURE => 'Feature idea or improvement',
            self::CATEGORY_OTHER => 'Something else',
        ];
    }

    public static function categoryLabel(string $category): string
    {
        return self::categories()[$category] ?? ucfirst(str_replace('_', ' ', $category));
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
