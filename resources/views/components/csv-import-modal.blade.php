@props([
    'modalId',
    'title',
    'action',
    'templateUrl',
    'columnsHint',
])

<div id="{{ $modalId }}" class="app-modal-overlay" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="{{ $modalId }}-title">
    <div class="app-modal-panel max-w-lg">
        <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="flex min-h-0 flex-1 flex-col">
            @csrf
            <div class="app-modal-header">
                <h2 id="{{ $modalId }}-title" class="text-lg font-semibold text-gray-900">{{ $title }}</h2>
                <button type="button" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600" onclick="closeAppModal('{{ $modalId }}')">&times;</button>
            </div>
            <div class="app-modal-body space-y-4">
                <p class="text-sm text-gray-600">{!! $columnsHint !!}</p>
                <p class="text-sm">
                    <a href="{{ $templateUrl }}" class="font-medium text-emerald-700 hover:text-emerald-800 underline">Download CSV template</a>
                </p>
                <div>
                    <label for="{{ $modalId }}-file" class="mb-1 block text-sm font-medium text-gray-700">CSV file</label>
                    <input type="file" name="csv_file" id="{{ $modalId }}-file" accept=".csv,text/csv,text/plain" required
                           class="block w-full text-sm text-gray-700 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-emerald-800 hover:file:bg-emerald-100">
                    @error('csv_file')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div class="app-modal-footer">
                <x-button variant="secondary" type="button" onclick="closeAppModal('{{ $modalId }}')">Cancel</x-button>
                <x-button variant="primary" type="submit">Import</x-button>
            </div>
        </form>
    </div>
</div>
