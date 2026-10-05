@if(! empty($trialDataSummary) && $business->canDeleteTrialData())
    @php
        $totalActivity = (int) ($trialDataSummary['sales'] ?? 0)
            + (int) ($trialDataSummary['customers'] ?? 0)
            + (int) ($trialDataSummary['expenses'] ?? 0)
            + (int) ($trialDataSummary['reconciliations'] ?? 0);
    @endphp
    <x-card id="trial-data" class="mt-6 max-w-3xl border-amber-200 bg-amber-50/40">
        <h2 class="text-lg font-semibold text-gray-900">Trial data cleanup</h2>
        <p class="mt-2 text-sm text-gray-600">
            Remove test sales and related activity before you activate your subscription. Your
            <strong>products, staff, branches, and settings are kept</strong>. Stock levels are
            <strong>not</strong> changed — update inventory afterward if test sales affected counts.
        </p>
        <p class="mt-2 text-sm text-amber-900">
            After your first successful subscription payment, sales and this cleanup option are no longer available.
        </p>

        <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
            <div class="rounded-lg border border-amber-100 bg-white px-3 py-2">
                <dt class="text-xs uppercase text-gray-500">Sales</dt>
                <dd class="font-semibold text-gray-900">{{ number_format($trialDataSummary['sales'] ?? 0) }}</dd>
            </div>
            <div class="rounded-lg border border-amber-100 bg-white px-3 py-2">
                <dt class="text-xs uppercase text-gray-500">Customers</dt>
                <dd class="font-semibold text-gray-900">{{ number_format($trialDataSummary['customers'] ?? 0) }}</dd>
            </div>
            <div class="rounded-lg border border-amber-100 bg-white px-3 py-2">
                <dt class="text-xs uppercase text-gray-500">Expenses</dt>
                <dd class="font-semibold text-gray-900">{{ number_format($trialDataSummary['expenses'] ?? 0) }}</dd>
            </div>
            <div class="rounded-lg border border-amber-100 bg-white px-3 py-2">
                <dt class="text-xs uppercase text-gray-500">Shift / EOD reports</dt>
                <dd class="font-semibold text-gray-900">{{ number_format($trialDataSummary['reconciliations'] ?? 0) }}</dd>
            </div>
        </dl>

        @if($totalActivity === 0)
            <p class="mt-4 text-sm text-gray-600">No trial activity to remove.</p>
        @else
            <form method="POST"
                  action="{{ tenant_route('tenant.business.trial-data.purge') }}"
                  class="mt-5 space-y-3"
                  onsubmit="return confirm('Remove all trial sales and related data? Products and stock will stay as they are. This cannot be undone.');">
                @csrf
                <x-input type="text"
                         name="confirm_name"
                         label='Type your business name to confirm'
                         value="{{ old('confirm_name') }}"
                         placeholder="{{ $business->name }}"
                         required />
                <button type="submit"
                        class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700">
                    Remove all trial activity
                </button>
            </form>
        @endif

        <p class="mt-4 text-xs text-gray-500">
            To remove individual sales instead, go to
            <a href="{{ tenant_route('tenant.sales.documents') }}" class="font-medium text-indigo-600 hover:text-indigo-800">Invoices &amp; receipts</a>.
        </p>
    </x-card>
@endif
