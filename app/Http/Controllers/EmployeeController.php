<?php

namespace App\Http\Controllers;

use App\Helpers\AuditLogger;
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
        $staff = User::query()
            ->where('business_id', $request->user()->business_id)
            ->where('role', '!=', 'owner')
            ->orderBy('name')
            ->get();

        return view('staff.index', compact('staff'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', User::class);

        $roles = $this->employeeService->assignableRoles($request->user());

        return view('staff.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', User::class);

        $roles = $this->employeeService->assignableRoles($request->user());

        if ($request->filled('username')) {
            $request->merge(['username' => strtolower(trim($request->input('username')))]);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:50|alpha_dash|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:30',
            'password' => 'required|string|min:8|confirmed',
            'role' => ['required', Rule::in($roles)],
            'branch_name' => 'nullable|string|max:100',
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

        $roles = $this->employeeService->assignableRoles(auth()->user());

        return view('staff.edit', compact('employee', 'roles'));
    }

    public function update(Request $request, Business $business, User $employee)
    {
        $this->authorize('update', $employee);

        $roles = $this->employeeService->assignableRoles($request->user());

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($employee->id)],
            'phone' => 'nullable|string|max:30',
            'password' => 'nullable|string|min:8|confirmed',
            'role' => ['required', Rule::in($roles)],
            'branch_name' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $old = $employee->toArray();

        $employee->name = $data['name'];
        $employee->email = $data['email'];
        $employee->phone = $data['phone'] ?? null;
        $employee->role = $data['role'];
        $employee->branch_name = $data['role'] === 'supervisor' ? ($data['branch_name'] ?? null) : null;
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
