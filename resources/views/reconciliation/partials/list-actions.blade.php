@can('manage-reconciliation-reports')
    <div class="flex flex-wrap items-center justify-end gap-2">
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.reconciliation.show', ['reconciliation' => $recon]) }}">View</x-button>
        <x-button variant="primary" size="sm" href="{{ tenant_route('tenant.reconciliation.edit', ['reconciliation' => $recon]) }}">Edit</x-button>
        <x-delete-confirm-button
            :action="tenant_route('tenant.reconciliation.destroy', ['reconciliation' => $recon])"
            message="Remove this end-of-day report?"
            detail="Sales and POS history stay unchanged. Pending shortages from this report will be cleared."
            label="Delete"
            button-class="inline-flex items-center rounded-lg border border-red-200 bg-white px-3 py-1.5 text-sm font-medium text-red-700 hover:bg-red-50"
        />
    </div>
@else
    <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.reconciliation.show', ['reconciliation' => $recon]) }}">View</x-button>
@endcan
