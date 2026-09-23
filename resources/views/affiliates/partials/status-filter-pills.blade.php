@php
    use App\Enums\AffiliateStatus;

    $statusFilterBaseUrl = $statusFilterBaseUrl ?? route('superadmin.entities.index', 'affiliates');
    $activeStatus = $affiliateStatusFilter ?? ($filters['status'] ?? 'all');
    $statusQuery = $statusQueryParams ?? array_filter([
        'q' => request('q'),
        'search' => request('search'),
        'period' => request('period'),
        'trashed' => request('trashed'),
    ]);
@endphp

<div class="flex flex-wrap items-center gap-2">
    @foreach([
        'all' => 'All',
        AffiliateStatus::PENDING => 'Pending',
        AffiliateStatus::APPROVED => 'Approved',
        AffiliateStatus::REJECTED => 'Rejected',
        'disabled' => 'Disabled',
    ] as $statusKey => $statusLabel)
        <a href="{{ $statusFilterBaseUrl . '?' . http_build_query(array_merge($statusQuery, ['status' => $statusKey])) }}{{ $statusFilterFragment ?? '' }}"
           class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $activeStatus === $statusKey ? 'bg-gray-900 text-white' : 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50' }}">
            {{ $statusLabel }}
        </a>
    @endforeach
</div>
