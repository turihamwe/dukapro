@extends('layouts.superadmin')

@section('title', 'Field feedback')

@section('content')
<div class="mb-6">
    <a href="{{ route('superadmin.affiliate-feedback.index') }}" class="text-sm font-medium text-violet-600 hover:text-violet-800">&larr; All field feedback</a>
    <h1 class="mt-3 text-2xl font-bold tracking-tight text-gray-900">{{ $item->summary }}</h1>
    <p class="mt-1 text-sm text-gray-500">
        {{ \App\Models\AffiliateFieldFeedback::categoryLabel($item->category) }}
        · {{ $item->created_at->format('M j, Y g:i A') }}
    </p>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-6">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Details</h2>
            <p class="mt-3 whitespace-pre-wrap text-sm leading-relaxed text-gray-800">{{ $item->message }}</p>
        </div>
    </div>
    <div class="space-y-4">
        <div class="rounded-xl border border-gray-200 bg-white p-5 text-sm">
            <h2 class="font-semibold text-gray-900">Affiliate</h2>
            @if($item->affiliate)
                <p class="mt-2 font-medium">{{ $item->affiliate->name }}</p>
                <p class="text-gray-600">{{ $item->affiliate->code }} · {{ $item->affiliate->email }}</p>
                <a href="{{ route('superadmin.entities.show', ['affiliates', $item->affiliate_id]) }}" class="mt-3 inline-block text-violet-600 hover:underline">View affiliate</a>
            @endif
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 text-sm">
            <h2 class="font-semibold text-gray-900">Context</h2>
            <dl class="mt-3 space-y-2 text-gray-700">
                <div>
                    <dt class="text-xs uppercase text-gray-500">Shop / customer</dt>
                    <dd>{{ $item->merchant_name ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-gray-500">Area</dt>
                    <dd>{{ $item->location ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-gray-500">Follow-up phone</dt>
                    <dd>{{ $item->contact_phone ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-gray-500">Status</dt>
                    <dd class="capitalize">{{ $item->status }}</dd>
                </div>
            </dl>
        </div>
    </div>
</div>
@endsection
