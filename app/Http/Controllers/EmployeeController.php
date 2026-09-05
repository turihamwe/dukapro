<?php

namespace App\Http\Controllers;

use App\Helpers\AuditLogger;
use App\Models\Branch;
use App\Models\Business;
use App\Models\User;
use App\Services\EmployeeService;
use App\Services\OnboardingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    protected EmployeeService $employeeService;

    protected OnboardingService $onboardingService;

    public function __construct(EmployeeService $employeeService, OnboardingService $onboardingService)
    {
        $this->employeeService = $employeeService;
        $this->onboardingService = $onboardingService;
        $this->middleware('can:manage-employees');
    }

    public function index(Request $request)
    {
        $query = User::query()
            ->where('business_id', $request->user()->business_id)
            ->where('role', '!=', 'owner')
            ->with('branch')
            ->orderBy('name');

        if ($request->user()->isBranchScoped() && $request->user()->branch_id) {
            $query->where('branch_id', $request->user()->branch_id);
        }

        $staff = $query->get();

        return view('staff.index', compact('staff'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', User::class);

        $roles = $this->employeeService->assignableRoles($request->user(), $request->user()->business);
        $branches = $this->employeeService->branchOptions($request->user()->business, $request->user());

        return view('staff.create', compact('roles', 'branches'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', User::class);

        $roles = $this->employeeService->assignableRoles($request->user(), $request->user()->business);

        if ($request->filled('username')) {
            $request->merge(['username' => strtolower(trim($request->input('username')))]);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:50|alpha_dash|unique:users,username',
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => 'nullable|string|max:30',
            'password' => 'required|string|min:8|confirmed',
            'role' => ['required', Rule::in($roles)],
            'branch_id' => [
                Rule::requiredIf(! $request->user()->isBranchScoped()),
                'nullable',
                'integer',
                Rule::exists('branches', 'id')->where(fn ($q) => $q->where('business_id', $request->user()->business_id)),
            ],
        ]);

        $staff = $this->employeeService->create($request->user()->business, $request->user(), $data);

        AuditLogger::record('staff_created', $staff, null, $staff->toArray());

        return redirect()
            ->to(tenant_route('tenant.staff.index'))
            ->with('success', 'Staff member added successfully.');
    }

    public function edit(Business $business, User $employee)
    {
        $this->authorize('update', $employee);

        $roles = $this->employeeService->assignableRoles(auth()->user(), $business);
        $branches = $this->employeeService->branchOptions($business, auth()->user());

        return view('staff.edit', compact('employee', 'roles', 'branches'));
    }

    public function update(Request $request, Business $business, User $employee)
    {
        $this->authorize('update', $employee);

        $roles = $this->employeeService->assignableRoles($request->user(), $business);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($employee->id)],
            'phone' => 'nullable|string|max:30',
            'password' => 'nullable|string|min:8|confirmed',
            'role' => ['required', Rule::in($roles)],
            'branch_id' => [
                Rule::requiredIf(! $request->user()->isBranchScoped()),
                'nullable',
                'integer',
                Rule::exists('branches', 'id')->where(fn ($q) => $q->where('business_id', $request->user()->business_id)),
            ],
            'is_active' => 'nullable|boolean',
        ]);

        $old = $employee->toArray();

        $employee->name = $data['name'];
        $employee->email = ! empty($data['email']) ? $data['email'] : null;
        $employee->phone = $data['phone'] ?? null;
        $employee->role = $data['role'];
        $this->employeeService->updateBranch($request->user(), $employee, $business, $data);
        $employee->is_active = $request->boolean('is_active', true);

        if (! empty($data['password'])) {
            $employee->password = Hash::make($data['password']);
        }

        $employee->save();

        AuditLogger::record('staff_updated', $employee, $old, $employee->fresh()->toArray());

        return redirect()
            ->to(tenant_route('tenant.staff.index'))
            ->with('success', 'Staff member updated.');
    }

    public function destroy(Request $request, Business $business, User $employee)
    {
        $this->authorize('delete', $employee);

        $old = $employee->toArray();
        $employee->delete();

        AuditLogger::record('staff_deleted', $employee, $old, null);

        return back()->with('success', 'Staff member removed.');
    }
}
