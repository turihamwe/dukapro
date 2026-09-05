@extends(auth()->user()->usesCashierExperience() ? 'layouts.cashier' : 'layouts.admin')

@section('title', 'End-of-Day Reconciliation')
@section('container_class', 'max-w-4xl')

@section('content')
<x-page-header
    title="{{ auth()->user()->usesCashierExperience() ? 'My Shift History' : 'Cashier EOD Reports' }}"
    subtitle="{{ auth()->user()->usesCashierExperience() ? 'Your submitted shift reconciliations' : 'Submitted shift reconciliations from all cashiers' }}">
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
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="font-medium text-gray-900">{{ $recon->reconciliation_date->format('M d, Y') }}</p>
                    <p class="text-xs text-gray-500">{{ $recon->user->name }}</p>
                    <div class="mt-2 text-sm">
                        <p>Exp: <span class="font-medium text-red-600">@money($recon->total_expenses ?? 0)</span></p>
                        <p>Dmg: <span class="font-medium text-amber-700">@money($recon->total_damages ?? 0)</span></p>
                        <p class="mt-1 text-xs text-gray-600">Net: <span class="font-semibold {{ ($recon->net_income ?? 0) >= 0 ? 'text-emerald-700' : 'text-red-600' }}">@money($recon->net_income ?? 0)</span></p>
                    </div>
                </div>
                <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.reconciliation.show', ['reconciliation' => $recon]) }}">View</x-button>
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
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Sales</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Expenses</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Damages</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Net Income</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Missing Money</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($reconciliations as $recon)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $recon->reconciliation_date->format('M d, Y') }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $recon->user->name }}</td>
                        <td class="px-6 py-4 text-right text-sm text-gray-900">@money($recon->total_sales ?? 0)</td>
                        <td class="px-6 py-4 text-right text-sm text-red-600">@money($recon->total_expenses ?? 0)</td>
                        <td class="px-6 py-4 text-right text-sm text-amber-700">@money($recon->total_damages ?? 0)</td>
                        <td class="px-6 py-4 text-right text-sm font-semibold {{ ($recon->net_income ?? 0) >= 0 ? 'text-emerald-700' : 'text-red-600' }}">@money($recon->net_income ?? 0)</td>
                        <td class="px-6 py-4 text-right text-sm font-medium {{ ($recon->missing_money ?? 0) > 0 ? 'text-red-600' : 'text-emerald-600' }}">@money($recon->missing_money ?? 0)</td>
                        <td class="px-6 py-4 text-right text-sm">
                            <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.reconciliation.show', ['reconciliation' => $recon]) }}">View</x-button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-6 py-12 text-center text-sm text-gray-500">No reconciliations yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>

<div class="mt-6">{{ $reconciliations->links() }}</div>
@endsection
