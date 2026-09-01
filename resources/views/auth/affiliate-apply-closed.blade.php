@extends('layouts.auth')

@section('title', 'Recruitment Closed — ' . platform_brand('name'))

@section('content')
    @include('layouts.partials.auth-brand', [
        'subtitle' => 'Affiliate program',
    ])

    <x-card class="shadow-sm text-center">
        <h2 class="text-lg font-semibold text-gray-900">Recruitment is closed</h2>
        <p class="mt-2 text-sm text-gray-600">We are not accepting new affiliate applications at this time. Please check back later or contact support.</p>
        <p class="mt-4 text-xs text-gray-500">
            <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:text-indigo-700">Business sign in</a>
        </p>
    </x-card>
@endsection
