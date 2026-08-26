@extends('layouts.admin')

@section('title', 'Add Employee')

@section('content')
<x-page-header title="Add Employee" subtitle="How would you like to set up your team?" />

<div class="mx-auto max-w-3xl">
    <div class="grid gap-6 md:grid-cols-2">
        <x-card class="text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-indigo-50 text-2xl">👤</div>
            <h2 class="mt-4 text-lg font-semibold text-gray-900">I am alone</h2>
            <p class="mt-2 text-sm text-gray-600">Perfect for sole proprietorships. You can switch to Cashier Mode instantly from the top bar without adding staff.</p>
            <form method="POST" action="{{ tenant_route('tenant.employees.sole-proprietor') }}" class="mt-6">
                @csrf
                <x-button type="submit" variant="primary" class="w-full">Continue as sole proprietor</x-button>
            </form>
        </x-card>

        <x-card class="text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-50 text-2xl">👥</div>
            <h2 class="mt-4 text-lg font-semibold text-gray-900">Add employee</h2>
            <p class="mt-2 text-sm text-gray-600">Add Managers, Supervisors, or Cashiers to match your business structure. You can add as many as you need.</p>
            <x-button variant="secondary" href="{{ tenant_route('tenant.employees.create') }}" class="mt-6 w-full">Open employee form</x-button>
        </x-card>
    </div>
</div>
@endsection
