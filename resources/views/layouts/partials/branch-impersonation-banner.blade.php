@php
    use App\Support\BranchImpersonation;

    $authUser = auth()->user();
    $branchView = ($authUser && $authUser->can('impersonate-branches'))
        ? BranchImpersonation::branch()
        : null;
@endphp
@if($branchView)
    <div class="sticky top-0 z-40 border-b border-indigo-300 bg-indigo-600 px-4 py-2.5 text-center text-sm text-white shadow-sm">
        <span>Operating as <strong>{{ $branchView->name }}</strong>.</span>
        <a href="{{ tenant_route('tenant.branches.index') }}" class="ml-2 font-semibold underline decoration-indigo-200 underline-offset-2 hover:text-indigo-100">Change branch</a>
        <form method="POST" action="{{ tenant_route('tenant.branch-context.clear') }}" class="ml-2 inline">
            @csrf
            <button type="submit" class="font-semibold underline decoration-indigo-200 underline-offset-2 hover:text-indigo-100">
                Exit branch view
            </button>
        </form>
    </div>
@endif
