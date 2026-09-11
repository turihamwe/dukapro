@extends('layouts.superadmin')

@section('title', 'View ' . $config['label'])

@section('content')
@php
    $businessTab = $businessTab ?? 'details';
@endphp
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold tracking-tight">{{ $config['label'] }} #{{ $item->id }}</h1>
        <p class="mt-1 text-sm text-gray-500">Record details</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('superadmin.entities.index', $entity) }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium hover:bg-gray-50">Back</a>
        @if(! empty($supportsSoftDeletes) && method_exists($item, 'trashed') && $item->trashed())
            <a href="{{ route('superadmin.entities.index', [$entity, 'trashed' => 1]) }}" class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-medium text-amber-800 hover:bg-amber-100">View archived</a>
        @else
            @can('platform-full-access')
                <a href="{{ route('superadmin.entities.edit', [$entity, $item->id]) }}" class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500">Edit</a>
                @if($config['deletable'] ?? true)
                    <form method="POST" action="{{ route('superadmin.entities.destroy', [$entity, $item->id]) }}" onsubmit="return confirm('Archive this record? It will be hidden but kept for historical data such as sales reports.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50">Archive</button>
                    </form>
                @endif
            @endcan
        @endif
    </div>
</div>

@if(! empty($supportsSoftDeletes) && method_exists($item, 'trashed') && $item->trashed())
    <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
        This record was archived on {{ optional($item->deleted_at)->format('M j, Y g:i A') ?? '—' }}.
        It is hidden from the business but preserved so linked history (for example sales line items) stays intact.
    </div>
@endif

@if($entity === 'businesses')
    <div class="mb-6 flex gap-1 rounded-lg border border-gray-200 bg-gray-50 p-1">
        <a href="{{ route('superadmin.entities.show', ['businesses', $item->id, 'tab' => 'details']) }}"
           class="rounded-md px-4 py-2 text-sm font-medium {{ $businessTab === 'details' ? 'bg-white text-violet-700 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
            Details
        </a>
        <a href="{{ route('superadmin.entities.show', ['businesses', $item->id, 'tab' => 'modules']) }}"
           class="rounded-md px-4 py-2 text-sm font-medium {{ $businessTab === 'modules' ? 'bg-white text-violet-700 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
            Modules
        </a>
    </div>
@endif

@if($entity !== 'businesses' || $businessTab === 'details')
<div class="rounded-xl border border-gray-200 bg-white p-6">
    @if($entity === 'businesses')
        <div class="mb-6 rounded-xl border border-violet-200 bg-violet-50 p-4">
            <p class="text-sm font-semibold text-violet-950">Affiliate attribution</p>
            <dl class="mt-3 grid gap-3 sm:grid-cols-2 text-sm">
                <div>
                    <dt class="text-xs uppercase text-violet-800/70">Primary affiliate (sponsor)</dt>
                    <dd class="mt-1 font-medium text-violet-950">
                        @if($item->sponsor)
                            {{ $item->sponsor->name }}
                            <span class="text-violet-700">({{ $item->sponsor->code }})</span>
                            @if($item->sponsor->isSystemDefault())
                                <span class="ml-1 rounded bg-violet-200 px-1.5 py-0.5 text-[10px] font-bold uppercase">System</span>
                            @endif
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-violet-800/70">Sub-affiliate attribution</dt>
                    <dd class="mt-1 font-medium text-violet-950">
                        @if($item->referringAffiliate)
                            {{ $item->referringAffiliate->name }}
                            <span class="text-violet-700">({{ $item->referringAffiliate->code }})</span>
                            @if($item->referringAffiliate->parent)
                                <span class="block text-xs font-normal text-violet-800/80">Team under {{ $item->referringAffiliate->parent->name }}</span>
                            @endif
                        @else
                            Direct / no sub-affiliate
                        @endif
                    </dd>
                </div>
            </dl>
        </div>
    @endif

    @if($entity === 'affiliates')
        <div class="mb-6 grid gap-4 sm:grid-cols-3">
            <div class="rounded-lg bg-gray-50 p-4">
                <p class="text-xs uppercase text-gray-500">Referrals</p>
                <p class="text-2xl font-bold">{{ $item->referred_businesses_count ?? 0 }}</p>
            </div>
            <div class="rounded-lg bg-gray-50 p-4">
                <p class="text-xs uppercase text-gray-500">Commissions</p>
                <p class="text-2xl font-bold">{{ $item->commissions_count ?? 0 }}</p>
            </div>
            <div class="rounded-lg bg-gray-50 p-4">
                <p class="text-xs uppercase text-gray-500">Referral URL</p>
                <p class="mt-1 break-all text-xs text-violet-700">{{ $item->referralUrl() }}</p>
            </div>
        </div>
        @can('platform-full-access')
            <div class="mb-6 flex flex-wrap gap-2">
                @if($item->status === 'pending')
                    <form method="POST" action="{{ route('superadmin.affiliates.approve', $item) }}">
                        @csrf
                        <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Approve</button>
                    </form>
                    <form method="POST" action="{{ route('superadmin.affiliates.reject', $item) }}">
                        @csrf
                        <button type="submit" class="rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50">Reject</button>
                    </form>
                @elseif($item->status === 'approved' || $item->status === 'suspended')
                    <form method="POST" action="{{ route('superadmin.affiliates.toggle-active', $item) }}">
                        @csrf
                        <button type="submit" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium hover:bg-gray-50">
                            {{ $item->is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                    </form>
                @endif
            </div>
        @endcan

        @if(! $item->isSubAffiliate())
            <div class="mb-6 rounded-xl border border-gray-200 bg-gray-50 p-4">
                <p class="text-sm font-semibold text-gray-900">Team sub-affiliates</p>
                @if(($item->teamMembers ?? collect())->isNotEmpty())
                    <ul class="mt-3 space-y-2 text-sm">
                        @foreach($item->teamMembers as $member)
                            <li class="flex items-center justify-between rounded-lg bg-white px-3 py-2">
                                <span>{{ $member->name }} <span class="text-gray-500">({{ $member->code }})</span></span>
                                <a href="{{ route('superadmin.entities.show', ['affiliates', $member->id]) }}" class="text-xs font-semibold text-violet-600">View</a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-2 text-xs text-gray-500">No sub-affiliates yet.</p>
                @endif

                @can('platform-full-access')
                    <form method="POST" action="{{ route('superadmin.affiliates.team.store', $item) }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                        @csrf
                        <input type="text" name="name" placeholder="Full name" required class="rounded-lg border-gray-300 text-sm">
                        <input type="email" name="email" placeholder="Email" required class="rounded-lg border-gray-300 text-sm">
                        <input type="text" name="username" placeholder="Username" required class="rounded-lg border-gray-300 text-sm">
                        <input type="text" name="phone" placeholder="Phone (optional)" class="rounded-lg border-gray-300 text-sm">
                        <input type="password" name="password" placeholder="Password" required class="rounded-lg border-gray-300 text-sm">
                        <input type="password" name="password_confirmation" placeholder="Confirm password" required class="rounded-lg border-gray-300 text-sm">
                        <div class="sm:col-span-2">
                            <button type="submit" class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500">Add sub-affiliate</button>
                        </div>
                    </form>
                @endcan
            </div>
        @endif
    @endif

    @if($entity === 'shareholders')
        <div class="mb-6 grid gap-4 sm:grid-cols-4">
            <div class="rounded-lg bg-gray-50 p-4">
                <p class="text-xs uppercase text-gray-500">Shares owned</p>
                <p class="text-2xl font-bold">{{ number_format($item->shares_owned, 2) }}</p>
            </div>
            <div class="rounded-lg bg-gray-50 p-4">
                <p class="text-xs uppercase text-gray-500">Capital invested</p>
                <p class="text-2xl font-bold">UGX {{ number_format($item->capital_invested, 0) }}</p>
            </div>
            <div class="rounded-lg bg-gray-50 p-4">
                <p class="text-xs uppercase text-gray-500">Earnings cap (3×)</p>
                <p class="text-2xl font-bold">UGX {{ number_format($item->earningsCap(), 0) }}</p>
            </div>
            <div class="rounded-lg bg-gray-50 p-4">
                <p class="text-xs uppercase text-gray-500">Progress</p>
                <p class="text-2xl font-bold">{{ number_format($item->earningsProgressPercent(), 1) }}%</p>
            </div>
        </div>
        @can('platform-full-access')
            <div class="mb-6 flex flex-wrap gap-2">
                @if($item->status === 'pending')
                    <form method="POST" action="{{ route('superadmin.shareholders.approve', $item) }}">
                        @csrf
                        <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Approve</button>
                    </form>
                    <form method="POST" action="{{ route('superadmin.shareholders.reject', $item) }}">
                        @csrf
                        <button type="submit" class="rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50">Reject</button>
                    </form>
                @elseif(in_array($item->status, ['active', 'approved', 'suspended'], true) && ! $item->contract_completed)
                    <form method="POST" action="{{ route('superadmin.shareholders.toggle-active', $item) }}">
                        @csrf
                        <button type="submit" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium hover:bg-gray-50">
                            {{ $item->is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                    </form>
                @endif
            </div>
            @if(! $item->isContractComplete())
                <form method="POST" action="{{ route('superadmin.shareholders.record-earning', $item) }}" class="mb-6 flex flex-wrap items-end gap-3 rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4">
                    @csrf
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Record earning (UGX)</label>
                        <input type="number" step="0.01" min="0.01" name="amount" required class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Description</label>
                        <input type="text" name="description" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Reference</label>
                        <input type="text" name="reference" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <button type="submit" class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500">Record payout</button>
                </form>
            @endif
        @endcan
    @endif

    @if($entity === 'users' && ! empty($promotionUser))
        @can('platform-full-access')
            <div class="mb-6 rounded-lg border border-dashed border-violet-200 bg-violet-50/50 p-4">
                <h2 class="text-sm font-semibold text-gray-900">Role promotion</h2>
                <p class="mt-1 text-xs text-gray-600">
                    Promote <strong>{{ $promotionUser->name }}</strong> ({{ $promotionUser->email }}) to a platform role with dedicated dashboard access.
                </p>
                <div class="mt-4 flex flex-wrap items-end gap-3">
                    @if($canPromoteAffiliate ?? false)
                        <form method="POST" action="{{ route('superadmin.users.promote-affiliate', $promotionUser) }}"
                              onsubmit="return confirm('Promote {{ $promotionUser->name }} to Affiliate? They will gain affiliate dashboard access.')">
                            @csrf
                            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                                Promote to Affiliate
                            </button>
                        </form>
                    @elseif($promotionUser->affiliateProfile)
                        <a href="{{ route('superadmin.entities.show', ['affiliates', $promotionUser->affiliateProfile->id]) }}"
                           class="rounded-lg border border-indigo-200 px-4 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-50">
                            View affiliate profile →
                        </a>
                    @endif

                    @if($canPromoteShareholder ?? false)
                        <form method="POST" action="{{ route('superadmin.users.promote-shareholder', $promotionUser) }}" class="flex flex-wrap items-end gap-2">
                            @csrf
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600">Shares</label>
                                <input type="number" step="0.01" min="0.01" max="{{ $remainingShares ?? 100 }}" name="shares"
                                       value="{{ $defaultPromotionShares ?? 1 }}" required
                                       class="w-28 rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            </div>
                            <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500"
                                    onclick="return confirm('Promote {{ $promotionUser->name }} to Shareholder?')">
                                Promote to Shareholder
                            </button>
                        </form>
                    @elseif($promotionUser->isShareholder() && $promotionUser->shareholderProfile)
                        <a href="{{ route('superadmin.entities.show', ['shareholders', $promotionUser->shareholderProfile->id]) }}"
                           class="rounded-lg border border-emerald-200 px-4 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50">
                            View shareholder profile →
                        </a>
                    @endif
                </div>
            </div>
        @endcan
    @endif

    <dl class="grid gap-4 sm:grid-cols-2">
        @foreach($item->getAttributes() as $key => $value)
            @if(! in_array($key, ['password', 'remember_token'], true))
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ str_replace('_', ' ', $key) }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 break-all">
                        @if($value instanceof \Carbon\Carbon)
                            {{ $value->format('M j, Y g:i A') }}
                        @elseif(is_bool($value))
                            {{ $value ? 'Yes' : 'No' }}
                        @elseif($value === null || $value === '')
                            —
                        @else
                            {{ is_array($value) ? json_encode($value) : $value }}
                        @endif
                    </dd>
                </div>
            @endif
        @endforeach
    </dl>
</div>
@endif

@if($entity === 'businesses' && $businessTab === 'modules')
    @include('superadmin.businesses._modules-panel', ['business' => $item, 'capabilities' => $capabilities ?? [], 'floor' => $floor ?? []])
@endif
@endsection
