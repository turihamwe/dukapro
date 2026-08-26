@extends('layouts.admin')

@section('title', 'Import Contacts')

@section('content')
<x-page-header title="Import Contacts" subtitle="Upload a CSV file to batch-import your address book">
    <x-slot name="actions">
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.contacts.index') }}">Back to contacts</x-button>
    </x-slot>
</x-page-header>

<x-card class="max-w-2xl">
    <form method="POST" action="{{ tenant_route('tenant.contacts.import.upload') }}" enctype="multipart/form-data" class="space-y-5">
        @csrf
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700">CSV file</label>
            <input type="file" name="csv_file" accept=".csv,text/csv" required
                   class="block w-full rounded-lg border border-gray-300 text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-indigo-700">
        </div>

        <div class="rounded-lg bg-gray-50 p-4 text-sm text-gray-600">
            <p class="font-medium text-gray-900">Supported columns</p>
            <p class="mt-2">name, company, phone, email, address, notes, credit (yes/no), credit_limit</p>
            <p class="mt-2 text-xs">You'll map your CSV headers on the next step before importing.</p>
        </div>

        <x-button type="submit" variant="primary">Upload &amp; map columns</x-button>
    </form>
</x-card>
@endsection
