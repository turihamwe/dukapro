@extends(auth()->user()->can('view-dashboard') ? 'layouts.admin' : 'layouts.cashier')

@section('title', 'End-of-Day Reconciliation')
@section('container_class', 'max-w-4xl')

@section('content')
<x-page-header
    title="{{ auth()->user()->can('view-dashboard') ? 'Cashier EOD Reports' : 'My Shift History' }}"
    subtitle="{{ auth()->user()->can('view-dashboard') ? 'Submitted shift reconciliations from all cashiers' : 'Your submitted shift reconciliations' }}">
    @can('submit-reconciliation')
        <x-slot name="actions">
            <x-button variant="primary" size="sm" href="{{ tenant_route('tenant.reconciliation.create') }}">Close Shift</x-button>
        </x-slot>
    @endcan
</x-page-header>

{{-- Mobile cards --}}
<div class="space-y-3 md:hidden">
    @forelse($reconciliations as $recon)
        <x-card :padding="false" class="p-4">
            <div class="flex items-start justify-between">
                <div>
                    <p class="font-medium text-gray-900">{{ $recon->reconciliation_date->format('M d, Y') }}</p>
                    <p class="text-xs text-gray-500">{{ $recon->user->name }}</p>
                </div>
                <div class="text-right text-sm">
                    <p>Cash: <span class="{{ $recon->cash_variance >= 0 ? 'text-emerald-600' : 'text-red-600' }} font-medium">@money($recon->cash_variance)</span></p>
                    <p>M-Pesa: <span class="{{ $recon->mobile_variance >= 0 ? 'text-emerald-600' : 'text-red-600' }} font-medium">@money($recon->mobile_variance)</span></p>
                </div>
            </div>
        </x-card>
    @empty
        <x-card class="text-center text-sm text-gray-500">No reconciliations yet.</x-card>
    @endforelse
</div>

{{-- Desktop table --}}
<x-card :padding="false" class="hidden md:block overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Cashier</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Expected Cash</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Cash Var.</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">M-Pesa Var.</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($reconciliations as $recon)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $recon->reconciliation_date->format('M d, Y') }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $recon->user->name }}</td>
                        <td class="px-6 py-4 text-right text-sm text-gray-900">@money($recon->expected_cash)</td>
                        <td class="px-6 py-4 text-right text-sm font-medium {{ $recon->cash_variance >= 0 ? 'text-emerald-600' : 'text-red-600' }}">@money($recon->cash_variance)</td>
                        <td class="px-6 py-4 text-right text-sm font-medium {{ $recon->mobile_variance >= 0 ? 'text-emerald-600' : 'text-red-600' }}">@money($recon->mobile_variance)</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">No reconciliations yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>

<div class="mt-6">{{ $reconciliations->links() }}</div>
@endsection
