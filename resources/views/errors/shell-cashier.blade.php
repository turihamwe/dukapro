@extends('layouts.cashier')

@section('title')
    @yield('title')
@endsection

@section('content')
    <div class="mx-auto max-w-lg py-2">
        <x-card class="shadow-sm">
            @include('errors.partials.content')
        </x-card>
    </div>
@endsection
