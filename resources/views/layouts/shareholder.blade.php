@extends('layouts.base')

@section('body')
<div class="flex min-h-full flex-col bg-gray-50">
    <header class="border-b border-gray-200 bg-white">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-4 sm:px-6">
            <div>
                <x-dukapro-logo size="header" />
                <p class="mt-1 text-xs text-gray-500">Shareholder Investment Portal</p>
            </div>
            <div class="flex items-center gap-4">
                <span class="hidden text-sm text-gray-600 sm:inline">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('shareholder.logout') }}">
                    @csrf
                    <button type="submit" class="text-sm text-gray-500 hover:text-gray-800">Sign out</button>
                </form>
            </div>
        </div>
    </header>

    <main class="mx-auto w-full max-w-5xl flex-1 px-4 py-6 sm:px-6 sm:py-8">
        @include('layouts.partials.flash')
        @yield('content')
    </main>
</div>
@endsection
