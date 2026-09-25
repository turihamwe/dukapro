@extends('layouts.admin')

@section('title', 'Rooms & bookings')

@section('content')
@php
    use App\Enums\HospitalityBookingStatus;
@endphp

<x-page-header title="Rooms & bookings" subtitle="Lodging assets, reservations, and check-in ledger">
    <x-slot name="actions">
        @can('view-inventory')
            <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.inventory.create') }}">+ Add room asset</x-button>
        @endcan
    </x-slot>
</x-page-header>

@if($rooms->isEmpty())
    <x-card class="mb-6">
        <p class="text-sm text-gray-700">No room assets yet. In inventory, add items as <strong>Room / rentable asset</strong> (e.g. Room 101) with a nightly rate.</p>
    </x-card>
@endif

@can('manage-hospitality-bookings')
<div class="mb-6">
    <x-card>
        <h2 class="text-sm font-semibold text-gray-900">New booking</h2>
        <form method="POST" action="{{ tenant_route('tenant.hospitality.bookings.store') }}" class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @csrf
            <div>
                <label for="product_id" class="mb-1 block text-xs font-medium text-gray-700">Room</label>
                <select name="product_id" id="product_id" required class="block w-full rounded-lg border-gray-300 text-sm">
                    <option value="">Select room…</option>
                    @foreach($rooms as $room)
                        <option value="{{ $room->id }}" @selected(old('product_id') == $room->id)>{{ $room->name }}</option>
                    @endforeach
                </select>
                @error('product_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="guest_name" class="mb-1 block text-xs font-medium text-gray-700">Guest name</label>
                <input type="text" name="guest_name" id="guest_name" value="{{ old('guest_name') }}" required maxlength="120" class="block w-full rounded-lg border-gray-300 text-sm">
                @error('guest_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="guest_phone" class="mb-1 block text-xs font-medium text-gray-700">Guest phone</label>
                <input type="text" name="guest_phone" id="guest_phone" value="{{ old('guest_phone') }}" maxlength="30" class="block w-full rounded-lg border-gray-300 text-sm">
            </div>
            <div>
                <label for="check_in" class="mb-1 block text-xs font-medium text-gray-700">Check-in</label>
                <input type="date" name="check_in" id="check_in" value="{{ old('check_in') }}" required class="block w-full rounded-lg border-gray-300 text-sm">
                @error('check_in')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="check_out" class="mb-1 block text-xs font-medium text-gray-700">Check-out</label>
                <input type="date" name="check_out" id="check_out" value="{{ old('check_out') }}" required class="block w-full rounded-lg border-gray-300 text-sm">
                @error('check_out')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            @if($branches->count() > 1)
                <div>
                    <label for="branch_id" class="mb-1 block text-xs font-medium text-gray-700">Branch</label>
                    <select name="branch_id" id="branch_id" class="block w-full rounded-lg border-gray-300 text-sm">
                        <option value="">Any / default</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="sm:col-span-2 lg:col-span-3">
                <label for="notes" class="mb-1 block text-xs font-medium text-gray-700">Notes</label>
                <textarea name="notes" id="notes" rows="2" maxlength="2000" class="block w-full rounded-lg border-gray-300 text-sm">{{ old('notes') }}</textarea>
            </div>
            <div class="sm:col-span-2 lg:col-span-3">
                <x-button type="submit" variant="primary" size="sm">Save booking</x-button>
            </div>
        </form>
    </x-card>
</div>
@endcan

<x-card :padding="false" class="overflow-hidden">
    <div class="flex flex-col gap-3 border-b border-gray-100 px-6 py-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-sm font-semibold text-gray-900">Check-in / check-out ledger</h2>
            <p class="text-xs text-gray-500">Filter by stay dates overlapping the range.</p>
        </div>
        <form method="GET" action="{{ tenant_route('tenant.hospitality.index') }}" class="flex flex-wrap items-end gap-2">
            <div>
                <label for="from" class="mb-1 block text-xs text-gray-500">From</label>
                <input type="date" name="from" id="from" value="{{ $filterFrom }}" class="rounded-lg border-gray-300 text-sm">
            </div>
            <div>
                <label for="to" class="mb-1 block text-xs text-gray-500">To</label>
                <input type="date" name="to" id="to" value="{{ $filterTo }}" class="rounded-lg border-gray-300 text-sm">
            </div>
            <x-button type="submit" variant="secondary" size="sm">Filter</x-button>
        </form>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Room</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Guest</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Stay</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse($ledger as $booking)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $booking->product->name ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            {{ $booking->guest_name }}
                            @if($booking->guest_phone)
                                <span class="block text-xs text-gray-500">{{ $booking->guest_phone }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">
                            {{ $booking->check_in->format('d M Y') }} → {{ $booking->check_out->format('d M Y') }}
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium
                                @if($booking->status === HospitalityBookingStatus::CHECKED_IN) bg-emerald-100 text-emerald-800
                                @elseif($booking->status === HospitalityBookingStatus::CANCELLED) bg-gray-100 text-gray-600
                                @elseif($booking->status === HospitalityBookingStatus::CHECKED_OUT) bg-slate-100 text-slate-700
                                @else bg-indigo-100 text-indigo-800 @endif">
                                {{ HospitalityBookingStatus::label($booking->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right text-sm">
                            @can('manage-hospitality-bookings')
                                <div class="flex flex-wrap justify-end gap-2">
                                    @if($booking->status === HospitalityBookingStatus::RESERVED)
                                        <form method="POST" action="{{ tenant_route('tenant.hospitality.bookings.check-in', ['booking' => $booking->id]) }}">
                                            @csrf
                                            <button type="submit" class="rounded-lg border border-emerald-200 px-2.5 py-1 text-xs font-semibold text-emerald-800 hover:bg-emerald-50">Check in</button>
                                        </form>
                                        <form method="POST" action="{{ tenant_route('tenant.hospitality.bookings.cancel', ['booking' => $booking->id]) }}" onsubmit="return confirm('Cancel this booking?');">
                                            @csrf
                                            <button type="submit" class="rounded-lg border border-gray-200 px-2.5 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-50">Cancel</button>
                                        </form>
                                    @elseif($booking->status === HospitalityBookingStatus::CHECKED_IN)
                                        <form method="POST" action="{{ tenant_route('tenant.hospitality.bookings.check-out', ['booking' => $booking->id]) }}">
                                            @csrf
                                            <button type="submit" class="rounded-lg border border-indigo-200 px-2.5 py-1 text-xs font-semibold text-indigo-800 hover:bg-indigo-50">Check out</button>
                                        </form>
                                    @endif
                                </div>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">No bookings in this view yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>
@endsection
