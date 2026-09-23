@extends('layouts.affiliate')

@section('title')
    @yield('title')
@endsection

@section('content')
    <div class="mx-auto max-w-lg">
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            @include('errors.partials.content')
        </div>
        <p class="mt-6 text-center text-xs text-gray-400">{{ platform_footer_tagline() }}</p>
    </div>
@endsection
