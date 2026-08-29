<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\UserRole;
use App\Helpers\SystemAuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Product;
use App\Models\User;
use App\Support\SuperAdmin\EntityRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EntityController extends Controller
{
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

        if ($entity === 'users' || $entity === 'products' || $entity === 'customers' || $entity === 'expenses') {
            $query->with('business');
        }

        if ($entity === 'sales') {
            $query->with('business', 'user')->latest('completed_at');
        } else {
            $query->latest('id');
        }

        $records = $query->paginate(20)->appends($request->only('q'));

        return view('superadmin.entities.index', [
            'entity' => $entity,
            'config' => $config,
            'records' => $records,
            'businesses' => Business::orderBy('name')->get(['id', 'name']),
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
            'roles' => UserRole::all(),
            'categories' => \App\Services\ExpenseService::CATEGORIES,
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
                break;

            case 'users':
                $data = $request->validate([
                    'business_id' => 'required|exists:businesses,id',
                    'name' => 'required|string|max:255',
                    'username' => 'required|string|max:50|alpha_dash|unique:users,username',
                    'email' => 'required|email|unique:users,email',
                    'password' => 'required|string|min:8',
                    'role' => 'required|in:' . implode(',', UserRole::all()),
                ]);
                $record = User::create([
                    'business_id' => $data['business_id'],
                    'name' => $data['name'],
                    'username' => strtolower($data['username']),
                    'email' => $data['email'],
                    'password' => Hash::make($data['password']),
                    'role' => $data['role'],
                    'is_active' => true,
                    'ui_theme' => 'modern',
                ]);
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
                $record = Expense::create($data);
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

        return view('superadmin.entities.show', compact('entity', 'config', 'item'));
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
            'roles' => UserRole::all(),
            'categories' => \App\Services\ExpenseService::CATEGORIES,
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
            ->with('success', 'Record deleted.');
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
