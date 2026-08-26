@extends('layouts.admin')

@section('title', 'Map CSV Columns')

@section('content')
<x-page-header title="Map CSV Columns" subtitle="{{ $totalRows }} contacts ready to import" />

<x-card class="mb-6">
    <h2 class="mb-3 text-sm font-semibold text-gray-900">Preview (first {{ count($preview) }} rows)</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    @foreach($headers as $header)
                        <th class="px-3 py-2 text-left font-medium text-gray-500">{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($preview as $row)
                    <tr>
                        @foreach($headers as $header)
                            <td class="px-3 py-2 text-gray-700">{{ $row[$header] ?? '—' }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-card>

<x-card class="max-w-3xl">
    <form method="POST" action="{{ tenant_route('tenant.contacts.import.process') }}" class="space-y-4">
        @csrf
        @foreach($fields as $field => $label)
            <div class="grid gap-3 sm:grid-cols-2 sm:items-center">
                <label class="text-sm font-medium text-gray-700">
                    {{ $label }}
                    @if($field === 'name')<span class="text-red-500">*</span>@endif
                </label>
                <select name="mapping[{{ $field }}]" class="rounded-lg border border-gray-300 px-3 py-2 text-sm" {{ $field === 'name' ? 'required' : '' }}>
                    <option value="">— Skip —</option>
                    @foreach($headers as $header)
                        <option value="{{ $header }}" @selected(($mapping[$field] ?? '') === $header)>{{ $header }}</option>
                    @endforeach
                </select>
            </div>
        @endforeach

        <div class="flex gap-3 pt-4">
            <x-button type="submit" variant="primary">Import contacts</x-button>
            <x-button variant="secondary" href="{{ tenant_route('tenant.contacts.import.show') }}">Cancel</x-button>
        </div>
    </form>
</x-card>
@endsection
