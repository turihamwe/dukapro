@extends('layouts.auth')

@section('title', 'Subscription Closed — ' . platform_brand('name'))

@section('content')
    @include('layouts.partials.auth-brand', [
        'subtitle' => 'Shareholder program',
    ])

    <x-card class="shadow-sm text-center">
        <h2 class="text-lg font-semibold text-gray-900">Subscription window is closed</h2>
        <p class="mt-2 text-sm text-gray-600">We are not accepting new shareholder applications at this time. Please check back later.</p>
    </x-card>
@endsection
