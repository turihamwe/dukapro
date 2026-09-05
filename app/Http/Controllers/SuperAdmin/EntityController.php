<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\UserRole;
use App\Enums\AffiliateStatus;
use App\Enums\ShareholderStatus;
use App\Helpers\SystemAuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\Branch;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Shareholder;
use App\Models\ShareholderEarning;
use App\Models\User;
use App\Services\AffiliateReferralCodeGenerator;
use App\Services\BranchService;
use App\Services\BusinessModuleService;
use App\Services\ShareAllocationService;
use App\Services\ShareholderEarningsService;
use App\Services\ShareholderRegistrationService;
use App\Services\UserPromotionService;
use App\Support\SuperAdmin\EntityRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EntityController extends Controller
{
    protected ShareAllocationService $allocationService;

    protected ShareholderRegistrationService $shareholderRegistrationService;

    protected ShareholderEarningsService $shareholderEarningsService;

    protected UserPromotionService $userPromotionService;

    protected AffiliateReferralCodeGenerator $referralCodeGenerator;

    protected BranchService $branchService;

    public function __construct(
        ShareAllocationService $allocationService,
        ShareholderRegistrationService $shareholderRegistrationService,
        ShareholderEarningsService $shareholderEarningsService,
        UserPromotionService $userPromotionService,
        AffiliateReferralCodeGenerator $referralCodeGenerator,
        BranchService $branchService
    ) {
        $this->allocationService = $allocationService;
        $this->shareholderRegistrationService = $shareholderRegistrationService;
        $this->shareholderEarningsService = $shareholderEarningsService;
        $this->userPromotionService = $userPromotionService;
        $this->referralCodeGenerator = $referralCodeGenerator;
        $this->branchService = $branchService;
    }

    public function index(Request $request, string $entity)
    {
        $config = EntityRegistry::get($entity);
        abort_unless($config, 404);

        $modelClass = $config['model'];
        $query = $modelClass::query();

        if (isset($config['scope']) && is_callable($config['scope'])) {
            $query = $config['scope']($query);
        }

        if ($request->filled('q')) {
            $term = $request->input('q');
            $query->where(function ($q) use ($config, $term) {
                foreach ($config['search'] as $index => $column) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $q->{$method}($column, 'like', '%' . $term . '%');
                }
            });
        }

        if (in_array($entity, ['users', 'staff', 'products', 'customers', 'expenses', 'branches'], true)) {
            $query->with('business');
        }

        if ($entity === 'affiliates') {
            $query->withCount('referredBusinesses', 'commissions');
        }

        if ($entity === 'affiliate_commissions') {
            $query->with('affiliate', 'business');
        }

        if ($entity === 'shareholders') {
            $query->latest('id');
        }

        if ($entity === 'shareholder_earnings') {
            $query->with('shareholder')->latest('id');
        }

        if ($entity === 'sales') {
            $query->with('business', 'user')->latest('completed_at');
        } else {
            $query->latest('id');
        }

        $records = $query->paginate(20)->appends($request->only('q'));

        $shareStats = null;
        if ($entity === 'shareholders') {
            $shareStats = [
                'total_shares' => $this->allocationService->totalShares(),
                'allocated_shares' => $this->allocationService->allocatedShares(),
                'remaining_shares' => $this->allocationService->remainingShares(),
                'shareholder_count' => $this->allocationService->activeShareholderCount(),
                'max_shareholders' => $this->allocationService->maxShareholders(),
                'price_per_share' => $this->allocationService->pricePerShare(),
            ];
        }

        return view('superadmin.entities.index', [
            'entity' => $entity,
            'config' => $config,
            'records' => $records,
            'businesses' => Business::orderBy('name')->get(['id', 'name']),
            'shareStats' => $shareStats,
            'defaultPromotionShares' => config('shareholders.default_promotion_shares', 1),
            'remainingShares' => $this->allocationService->remainingShares(),
        ]);
    }

    public function create(string $entity)
    {
        $config = EntityRegistry::get($entity);
        abort_unless($config && ($config['creatable'] ?? false), 404);

        return view('superadmin.entities.create', [
            'entity' => $entity,
            'config' => $config,
            'businesses' => Business::orderBy('name')->get(['id', 'name']),
            'roles' => $entity === 'staff' ? UserRole::staffRoles() : UserRole::all(),
            'categories' => \App\Services\ExpenseService::CATEGORIES,
            'affiliateStatuses' => AffiliateStatus::all(),
            'shareholderStatuses' => ShareholderStatus::all(),
            'remainingShares' => $entity === 'shareholders' ? $this->allocationService->remainingShares() : null,
            'pricePerShare' => $this->allocationService->pricePerShare(),
            'shareholders' => Shareholder::orderBy('name')->get(['id', 'name', 'email']),
            'branches' => Branch::orderBy('name')->get(['id', 'name', 'business_id']),
        ]);
    }

    public function store(Request $request, string $entity)
    {
        $config = EntityRegistry::get($entity);
        abort_unless($config && ($config['creatable'] ?? false), 404);

        $record = null;

        switch ($entity) {
            case 'businesses':
                $data = $request->validate([
                    'name' => 'required|string|max:255',
                    'email' => 'nullable|email|max:255',
                    'phone' => 'nullable|string|max:30',
                    'currency_symbol' => 'nullable|string|max:20',
                ]);
                $slug = Str::slug($data['name']) ?: 'store';
                $record = Business::create(array_merge($data, [
                    'slug' => $this->uniqueBusinessSlug($slug),
                    'portal_slug' => $slug . '-' . Str::lower(Str::random(8)),
                    'currency' => $data['currency_symbol'] ?? 'UGX',
                    'currency_symbol' => $data['currency_symbol'] ?? 'UGX',
                    'currency_position' => 'prefix',
                    'is_active' => true,
                    'subscription_status' => 'trial',
                    'trial_ends_at' => now()->addDays(30),
                ]));
                $this->branchService->createDefault($record);
                break;

            case 'branches':
                $data = $request->validate([
                    'business_id' => 'required|exists:businesses,id',
                    'name' => 'required|string|max:255',
                    'address' => 'nullable|string|max:255',
                    'phone' => 'nullable|string|max:30',
                    'is_active' => 'nullable|boolean',
                    'is_default' => 'nullable|boolean',
                ]);
                $business = Business::findOrFail($data['business_id']);
                $record = $this->branchService->create($business, [
                    'name' => $data['name'],
                    'address' => $data['address'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'is_active' => $request->boolean('is_active', true),
                    'is_default' => $request->boolean('is_default', false),
                ]);
                break;

            case 'staff':
                $data = $request->validate([
                    'business_id' => 'required|exists:businesses,id',
                    'branch_id' => 'required|exists:branches,id',
                    'name' => 'required|string|max:255',
                    'username' => 'required|string|max:50|alpha_dash|unique:users,username',
                    'email' => 'required|email|unique:users,email',
                    'password' => 'required|string|min:8',
                    'role' => 'required|in:' . implode(',', UserRole::staffRoles()),
                ]);
                abort_unless(
                    Branch::where('id', $data['branch_id'])->where('business_id', $data['business_id'])->exists(),
                    422,
                    'Branch must belong to the selected business.'
                );
                $record = User::create([
                    'business_id' => $data['business_id'],
                    'branch_id' => $data['branch_id'],
                    'name' => $data['name'],
                    'username' => strtolower($data['username']),
                    'email' => $data['email'],
                    'password' => Hash::make($data['password']),
                    'role' => $data['role'],
                    'is_active' => true,
                    'ui_theme' => 'modern',
                ]);
                break;

            case 'users':
                abort(403, 'Create users from the Staff entity or promote existing accounts.');
                break;

            case 'products':
                $data = $request->validate([
                    'business_id' => 'required|exists:businesses,id',
                    'name' => 'required|string|max:255',
                    'sku' => 'nullable|string|max:100',
                    'price' => 'required|numeric|min:0',
                    'measurement_unit' => 'required|string|max:50',
                    'stock_quantity' => 'required|numeric|min:0',
                ]);
                $record = Product::create(array_merge($data, ['is_active' => true]));
                break;

            case 'customers':
                $data = $request->validate([
                    'business_id' => 'required|exists:businesses,id',
                    'name' => 'required|string|max:255',
                    'phone' => 'nullable|string|max:30',
                    'email' => 'nullable|email|max:255',
                ]);
                $record = Customer::create(array_merge($data, ['is_active' => true]));
                break;

            case 'expenses':
                $data = $request->validate([
                    'business_id' => 'required|exists:businesses,id',
                    'title' => 'required|string|max:255',
                    'category' => 'required|string|max:100',
                    'amount' => 'required|numeric|min:0.01',
                    'expense_date' => 'required|date',
                    'description' => 'nullable|string|max:1000',
                ]);
                $record = Expense::create(array_merge($data, [
                    'description' => $data['description'] ?? '',
                ]));
                break;

            case 'affiliates':
                $data = $request->validate([
                    'name' => 'required|string|max:255',
                    'email' => 'required|email|unique:affiliates,email',
                    'phone' => 'nullable|string|max:30',
                    'code' => 'nullable|string|max:32|unique:affiliates,code',
                    'commission_rate' => 'nullable|numeric|min:0|max:1',
                    'status' => 'required|in:' . implode(',', AffiliateStatus::all()),
                    'is_active' => 'nullable|boolean',
                ]);
                $code = ! empty($data['code'])
                    ? strtolower(trim($data['code']))
                    : $this->referralCodeGenerator->generateUnique();
                while (Affiliate::where('code', $code)->exists()) {
                    $code = $this->referralCodeGenerator->generateUnique();
                }
                $record = Affiliate::create([
                    'name' => $data['name'],
                    'email' => strtolower($data['email']),
                    'phone' => $data['phone'] ?? null,
                    'code' => $code,
                    'commission_rate' => $data['commission_rate'] ?? config('affiliates.default_commission_rate', 0.10),
                    'status' => $data['status'],
                    'is_active' => $request->boolean('is_active'),
                    'approved_at' => $data['status'] === AffiliateStatus::APPROVED ? now() : null,
                    'approved_by' => $data['status'] === AffiliateStatus::APPROVED ? $request->user()->id : null,
                ]);
                break;

            case 'shareholders':
                $data = $request->validate([
                    'name' => 'required|string|max:255',
                    'email' => 'required|email|unique:shareholders,email',
                    'phone' => 'nullable|string|max:30',
                    'national_id' => 'nullable|string|max:50',
                    'shares_owned' => 'required|numeric|min:0.01',
                    'status' => 'required|in:' . implode(',', ShareholderStatus::all()),
                    'is_active' => 'nullable|boolean',
                ]);
                $shares = (float) $data['shares_owned'];
                if (in_array($data['status'], ShareholderStatus::allocated(), true)) {
                    $this->allocationService->validateAllocation($shares);
                }
                $record = Shareholder::create([
                    'name' => $data['name'],
                    'email' => strtolower($data['email']),
                    'phone' => $data['phone'] ?? null,
                    'national_id' => $data['national_id'] ?? null,
                    'shares_owned' => $shares,
                    'capital_invested' => $this->allocationService->capitalForShares($shares),
                    'total_earnings' => 0,
                    'status' => $data['status'],
                    'is_active' => $request->boolean('is_active'),
                    'registered_at' => now(),
                    'approved_at' => in_array($data['status'], ShareholderStatus::allocated(), true) ? now() : null,
                    'approved_by' => in_array($data['status'], ShareholderStatus::allocated(), true) ? $request->user()->id : null,
                ]);
                break;

            case 'shareholder_earnings':
                $data = $request->validate([
                    'shareholder_id' => 'required|exists:shareholders,id',
                    'amount' => 'required|numeric|min:0.01',
                    'description' => 'nullable|string|max:255',
                    'reference' => 'nullable|string|max:100',
                ]);
                $shareholder = Shareholder::findOrFail($data['shareholder_id']);
                $record = $this->shareholderEarningsService->record(
                    $shareholder,
                    (float) $data['amount'],
                    $request->user(),
                    $data['description'] ?? null,
                    $data['reference'] ?? null
                );
                break;
        }

        abort_unless($record, 422);

        SystemAuditLogger::record(
            'platform_entity_created',
            'Created ' . $entity . ' #' . $record->id,
            $record->business_id ?? null,
            $request->user()->id
        );

        return redirect()
            ->route('superadmin.entities.index', $entity)
            ->with('success', ucfirst(rtrim($entity, 's')) . ' created successfully.');
    }

    public function show(string $entity, int $record)
    {
        $config = EntityRegistry::get($entity);
        abort_unless($config, 404);

        $modelClass = $config['model'];
        $item = $modelClass::query()->findOrFail($record);

        if ($entity === 'affiliates') {
            $item->loadCount('referredBusinesses', 'commissions');
            $item->load('user', 'approver');
        }

        if ($entity === 'affiliate_commissions') {
            $item->load('affiliate', 'business', 'subscriptionPayment');
        }

        if ($entity === 'shareholders') {
            $item->load('user', 'approver', 'earnings');
        }

        if ($entity === 'shareholder_earnings') {
            $item->load('shareholder', 'recorder');
        }

        if (in_array($entity, ['users', 'staff'], true)) {
            $item->load('business', 'affiliateProfile', 'shareholderProfile');
        }

        if ($entity === 'businesses') {
            $item->load('businessModules');
            $capabilities = app(BusinessModuleService::class)->capabilityStates($item);
            $floor = app(BusinessModuleService::class)->floorSettings($item);
        }

        $promotionUser = $entity === 'users' ? $item : null;

        return view('superadmin.entities.show', [
            'entity' => $entity,
            'config' => $config,
            'item' => $item,
            'canPromoteAffiliate' => $entity === 'users'
                && $this->userPromotionService->canPromoteToAffiliate($item),
            'canPromoteShareholder' => $entity === 'users'
                && $this->userPromotionService->canPromoteToShareholder($item),
            'promotionUser' => $promotionUser,
            'defaultPromotionShares' => config('shareholders.default_promotion_shares', 1),
            'remainingShares' => $this->allocationService->remainingShares(),
            'capabilities' => $entity === 'businesses' ? ($capabilities ?? []) : [],
            'floor' => $entity === 'businesses' ? ($floor ?? []) : [],
            'businessTab' => $entity === 'businesses' ? request('tab', 'details') : 'details',
        ]);
    }

    public function edit(string $entity, int $record)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $config = EntityRegistry::get($entity);
        abort_unless($config, 404);

        $modelClass = $config['model'];
        $item = $modelClass::query()->findOrFail($record);

        return view('superadmin.entities.edit', [
            'entity' => $entity,
            'config' => $config,
            'item' => $item,
            'businesses' => Business::orderBy('name')->get(['id', 'name']),
            'roles' => $entity === 'staff' ? UserRole::staffRoles() : UserRole::all(),
            'categories' => \App\Services\ExpenseService::CATEGORIES,
            'affiliateStatuses' => AffiliateStatus::all(),
            'shareholderStatuses' => ShareholderStatus::all(),
            'remainingShares' => $entity === 'shareholders' ? $this->allocationService->remainingShares($item->id) : null,
            'pricePerShare' => $this->allocationService->pricePerShare(),
            'shareholders' => Shareholder::orderBy('name')->get(['id', 'name', 'email']),
            'branches' => Branch::orderBy('name')->get(['id', 'name', 'business_id']),
        ]);
    }

    public function update(Request $request, string $entity, int $record)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $config = EntityRegistry::get($entity);
        abort_unless($config, 404);

        $modelClass = $config['model'];
        $item = $modelClass::query()->findOrFail($record);

        switch ($entity) {
            case 'businesses':
                $data = $request->validate([
                    'name' => 'required|string|max:255',
                    'email' => 'nullable|email|max:255',
                    'phone' => 'nullable|string|max:30',
                    'is_active' => 'nullable|boolean',
                    'subscription_status' => 'required|string|max:50',
                ]);
                $data['is_active'] = $request->boolean('is_active', true);
                $item->update($data);
                break;

            case 'branches':
                $data = $request->validate([
                    'name' => 'required|string|max:255',
                    'address' => 'nullable|string|max:255',
                    'phone' => 'nullable|string|max:30',
                    'is_active' => 'nullable|boolean',
                    'is_default' => 'nullable|boolean',
                ]);
                $this->branchService->update($item, [
                    'name' => $data['name'],
                    'address' => $data['address'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'is_active' => $request->boolean('is_active', true),
                    'is_default' => $request->boolean('is_default', false),
                ]);
                break;

            case 'staff':
                $data = $request->validate([
                    'name' => 'required|string|max:255',
                    'email' => 'required|email|unique:users,email,' . $item->id,
                    'role' => 'required|in:' . implode(',', UserRole::staffRoles()),
                    'branch_id' => 'required|exists:branches,id',
                    'is_active' => 'nullable|boolean',
                ]);
                abort_unless(
                    Branch::where('id', $data['branch_id'])->where('business_id', $item->business_id)->exists(),
                    422,
                    'Branch must belong to the staff member business.'
                );
                $data['is_active'] = $request->boolean('is_active', true);
                $item->update($data);
                break;

            case 'users':
                $data = $request->validate([
                    'name' => 'required|string|max:255',
                    'email' => 'required|email|unique:users,email,' . $item->id,
                    'role' => 'required|in:' . implode(',', UserRole::all()),
                    'is_active' => 'nullable|boolean',
                ]);
                $data['is_active'] = $request->boolean('is_active', true);
                $item->update($data);
                break;

            case 'products':
                $data = $request->validate([
                    'name' => 'required|string|max:255',
                    'price' => 'required|numeric|min:0',
                    'stock_quantity' => 'required|numeric|min:0',
                    'is_active' => 'nullable|boolean',
                ]);
                $data['is_active'] = $request->boolean('is_active', true);
                $item->update($data);
                break;

            case 'customers':
                $data = $request->validate([
                    'name' => 'required|string|max:255',
                    'phone' => 'nullable|string|max:30',
                    'email' => 'nullable|email|max:255',
                    'is_active' => 'nullable|boolean',
                ]);
                $data['is_active'] = $request->boolean('is_active', true);
                $item->update($data);
                break;

            case 'expenses':
                $data = $request->validate([
                    'title' => 'required|string|max:255',
                    'category' => 'required|string|max:100',
                    'amount' => 'required|numeric|min:0.01',
                    'expense_date' => 'required|date',
                    'description' => 'nullable|string|max:1000',
                ]);
                $item->update(array_merge($data, [
                    'description' => $data['description'] ?? '',
                ]));
                break;

            case 'affiliates':
                $data = $request->validate([
                    'name' => 'required|string|max:255',
                    'email' => 'required|email|unique:affiliates,email,' . $item->id,
                    'phone' => 'nullable|string|max:30',
                    'code' => 'required|string|max:32|unique:affiliates,code,' . $item->id,
                    'commission_rate' => 'required|numeric|min:0|max:1',
                    'status' => 'required|in:' . implode(',', AffiliateStatus::all()),
                    'is_active' => 'nullable|boolean',
                ]);
                $data['is_active'] = $request->boolean('is_active');
                if ($data['status'] === AffiliateStatus::APPROVED && ! $item->approved_at) {
                    $data['approved_at'] = now();
                    $data['approved_by'] = $request->user()->id;
                }
                $item->update($data);
                break;

            case 'affiliate_commissions':
                $data = $request->validate([
                    'status' => 'required|in:pending,paid,cancelled',
                ]);
                $data['paid_at'] = $data['status'] === 'paid' ? now() : null;
                $item->update($data);
                break;

            case 'shareholders':
                $data = $request->validate([
                    'name' => 'required|string|max:255',
                    'email' => 'required|email|unique:shareholders,email,' . $item->id,
                    'phone' => 'nullable|string|max:30',
                    'national_id' => 'nullable|string|max:50',
                    'shares_owned' => 'required|numeric|min:0.01',
                    'status' => 'required|in:' . implode(',', ShareholderStatus::all()),
                    'is_active' => 'nullable|boolean',
                ]);
                $data['is_active'] = $request->boolean('is_active');
                $shares = (float) $data['shares_owned'];
                if (in_array($data['status'], ShareholderStatus::allocated(), true)) {
                    $this->allocationService->validateAllocation($shares, $item->id, false);
                }
                $data['capital_invested'] = $this->allocationService->capitalForShares($shares);
                if (in_array($data['status'], ShareholderStatus::allocated(), true) && ! $item->approved_at) {
                    $data['approved_at'] = now();
                    $data['approved_by'] = $request->user()->id;
                }
                if ($item->total_earnings >= ($data['capital_invested'] * config('shareholders.earnings_cap_multiplier', 3))) {
                    $data['contract_completed'] = true;
                    $data['contract_completed_at'] = $item->contract_completed_at ?? now();
                    $data['status'] = ShareholderStatus::COMPLETED;
                }
                $item->update($data);
                break;

            default:
                abort(404);
        }

        SystemAuditLogger::record(
            'platform_entity_updated',
            'Updated ' . $entity . ' #' . $item->id,
            $item->business_id ?? null,
            $request->user()->id
        );

        return redirect()
            ->route('superadmin.entities.show', [$entity, $item->id])
            ->with('success', 'Record updated.');
    }

    public function destroy(Request $request, string $entity, int $record)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $config = EntityRegistry::get($entity);
        abort_unless($config, 404);

        $modelClass = $config['model'];
        $item = $modelClass::query()->findOrFail($record);

        if ($entity === 'branches' && ! $this->branchService->canDelete($item)) {
            return back()->with('error', 'Cannot delete the default branch or a branch that still has staff assigned.');
        }

        $businessId = $item->business_id ?? null;
        $itemId = $item->id;
        $item->delete();

        SystemAuditLogger::record(
            'platform_entity_deleted',
            'Deleted ' . $entity . ' #' . $itemId,
            $businessId,
            $request->user()->id
        );

        return redirect()
            ->route('superadmin.entities.index', $entity)
            ->with('success', 'Record soft-deleted successfully.');
    }

    protected function uniqueBusinessSlug(string $base): string
    {
        $slug = $base;
        $counter = 1;
        while (Business::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter++;
        }

        return $slug;
    }
}
