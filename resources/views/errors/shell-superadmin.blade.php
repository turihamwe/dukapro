@extends('layouts.superadmin')

@section('title')
    @yield('title')
@endsection

@section('content')
    <div class="mx-auto max-w-lg py-4 sm:py-8">
        <x-card class="shadow-sm">
            @include('errors.partials.content')
        </x-card>
    </div>
@endsection
