@php
    use App\Support\BranchImpersonationCompliance;

    $adminUnlocked = BranchImpersonationCompliance::isAdminUnlocked($business);
    $branchCount = $business->branches()->where('is_active', true)->count();
@endphp

<div class="mb-6 rounded-xl border border-violet-200 bg-violet-50/60 p-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-sm font-semibold text-gray-900">Branch context switching</p>
            <p class="mt-1 text-xs text-gray-600">
                Lets the owner (and permitted managers) operate as a single branch from the branches list, with a sticky banner and scoped POS, inventory, and reports.
                Hidden for tenants until you unlock them here.
            </p>
        </div>
        @if($adminUnlocked)
            <span class="rounded-full bg-violet-100 px-2.5 py-1 text-xs font-semibold text-violet-800">Unlocked</span>
        @else
            <span class="rounded-full bg-gray-200 px-2.5 py-1 text-xs font-semibold text-gray-700">Locked</span>
        @endif
    </div>

    <dl class="mt-4 grid gap-3 sm:grid-cols-2 text-sm">
        <div>
            <dt class="text-xs uppercase text-gray-500">Active branches</dt>
            <dd class="mt-1 font-medium text-gray-900">{{ $branchCount }}</dd>
        </div>
        <div>
            <dt class="text-xs uppercase text-gray-500">Tenant UI</dt>
            <dd class="mt-1 font-medium text-gray-900">{{ $adminUnlocked && $branchCount > 0 ? 'Operate-as-branch on branches list' : 'Hidden' }}</dd>
        </div>
    </dl>

    @can('platform-full-access')
        <form method="POST" action="{{ route('superadmin.businesses.branch-impersonation.unlock', $business->id) }}" class="mt-4">
            @csrf
            <input type="hidden" name="branch_impersonation_admin_unlocked" value="0">
            <label class="flex items-start gap-3 rounded-lg border border-violet-200 bg-white p-4">
                <input type="checkbox" name="branch_impersonation_admin_unlocked" value="1"
                       {{ $adminUnlocked ? 'checked' : '' }}
                       class="mt-0.5 rounded border-gray-300 text-violet-600 focus:ring-violet-500">
                <span>
                    <span class="block text-sm font-medium text-gray-900">Enable branch context switching</span>
                    <span class="mt-0.5 block text-xs text-gray-500">Unchecking clears any active branch session for users on their next request.</span>
                </span>
            </label>
            <button type="submit" class="mt-3 rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-700">
                Save branch context access
            </button>
        </form>
    @endcan
</div>
