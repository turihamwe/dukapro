<?php

namespace App\Http\Controllers;

use App\Helpers\AuditLogger;
use App\Models\Branch;
use App\Models\Business;
use App\Services\BranchService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    protected BranchService $branchService;

    public function __construct(BranchService $branchService)
    {
        $this->branchService = $branchService;
        $this->middleware('can:manage-branches');
    }

    public function index(Request $request)
    {
        $branches = Branch::query()
            ->where('business_id', $request->user()->business_id)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->withCount('users')
            ->get();

        return view('branches.index', compact('branches'));
    }

    public function create()
    {
        return view('branches.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
        ]);

        $branch = $this->branchService->create($request->user()->business, $data);

        AuditLogger::record('branch_created', $branch, null, $branch->toArray());

        return redirect()
            ->to(tenant_route('tenant.branches.index'))
            ->with('success', 'Branch created successfully.');
    }

    public function edit(Business $business, Branch $branch)
    {
        $this->ensureSameBusiness($branch);

        return view('branches.edit', compact('branch'));
    }

    public function update(Request $request, Business $business, Branch $branch)
    {
        $this->ensureSameBusiness($branch);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
        ]);

        $old = $branch->toArray();
        $branch = $this->branchService->update($branch, $data);

        AuditLogger::record('branch_updated', $branch, $old, $branch->toArray());

        return redirect()
            ->to(tenant_route('tenant.branches.index'))
            ->with('success', 'Branch updated.');
    }

    public function destroy(Business $business, Branch $branch)
    {
        $this->ensureSameBusiness($branch);

        if (! $this->branchService->canDelete($branch)) {
            return back()->with('error', 'Cannot delete the default branch or a branch that still has staff assigned.');
        }

        $old = $branch->toArray();
        $branch->delete();

        AuditLogger::record('branch_deleted', $branch, $old, null);

        return back()->with('success', 'Branch removed.');
    }

    protected function ensureSameBusiness(Branch $branch): void
    {
        abort_unless((int) $branch->business_id === (int) auth()->user()->business_id, 404);
    }
}
