@extends('layouts.admin')

@section('title', 'Appointments')

@section('content')
<x-page-header title="Appointments" subtitle="Preview module — enabled via Capabilities">
    <x-slot name="actions">
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.business.edit') }}">Capabilities</x-button>
    </x-slot>
</x-page-header>

<x-card class="max-w-2xl">
    <p class="text-sm text-gray-700">
        The <strong>Appointments (Preview)</strong> module is active for <strong>{{ $business->name }}</strong>.
    </p>
    <p class="mt-3 text-sm text-gray-600">
        This page exists to prove the module pattern: register in <code class="rounded bg-gray-100 px-1">config/modules.php</code>,
        gate with <code class="rounded bg-gray-100 px-1">module:appointments</code>, and toggle per tenant in Business → Capabilities or Superadmin → Modules.
    </p>
    <p class="mt-3 text-xs text-gray-500">
        Replace this stub with real appointment booking logic when you build the feature. See <code class="rounded bg-gray-100 px-1">app/Modules/README.md</code>.
    </p>
</x-card>
@endsection
