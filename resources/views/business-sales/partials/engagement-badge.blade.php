@php
    use App\Support\BusinessEngagementTier;
    $tier = $tier ?? BusinessEngagementTier::DORMANT;
    $classes = [
        BusinessEngagementTier::ACTIVE => 'bg-emerald-100 text-emerald-800',
        BusinessEngagementTier::AT_RISK => 'bg-amber-100 text-amber-900',
        BusinessEngagementTier::DORMANT => 'bg-rose-100 text-rose-800',
    ];
@endphp
<span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $classes[$tier] ?? 'bg-gray-100 text-gray-700' }}">
    {{ BusinessEngagementTier::label($tier) }}
</span>
