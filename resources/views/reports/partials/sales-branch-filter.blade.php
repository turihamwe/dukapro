@php
    $branchQuery = $branchQuery ?? [];
    $period = $period ?? request('period', 'daily');
    $branchFormAction = $branchFormAction ?? tenant_route('tenant.reports.sales.index');
@endphp

@if(!empty($showBranchPicker) && ($branches ?? collect())->isNotEmpty())
    <form method="GET" action="{{ $branchFormAction }}" class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center">
        <input type="hidden" name="period" value="{{ $period }}">
        @foreach(request()->only(['from', 'to']) as $key => $value)
            @if($value !== null && $value !== '')
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach
        <label for="sales-report-branch" class="text-sm font-medium text-gray-700 shrink-0">Branch</label>
        <select id="sales-report-branch" name="branch_id" onchange="this.form.submit()"
                class="w-full max-w-xs rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
            @foreach($branches as $id => $name)
                <option value="{{ $id }}" @selected((int) ($branchId ?? 0) === (int) $id)>{{ $name }}</option>
            @endforeach
        </select>
        <p class="text-xs text-gray-500 sm:ml-2">Sales totals are for the selected branch only.</p>
    </form>
@elseif(!empty($branchName))
    <p class="mb-4 text-sm text-gray-600">Branch: <span class="font-medium text-gray-900">{{ $branchName }}</span></p>
@endif
