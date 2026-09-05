<?php

namespace App\Http\Controllers;

use App\Helpers\AuditLogger;
use App\Models\Branch;
use App\Models\Business;
use App\Models\RestaurantTable;
use App\Services\RestaurantTableService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RestaurantTableController extends Controller
{
    protected RestaurantTableService $tableService;

    public function __construct(RestaurantTableService $tableService)
    {
        $this->tableService = $tableService;
        $this->middleware('can:manage-restaurant-tables');
    }

    public function index(Request $request)
    {
        $business = $request->user()->business;
        abort_unless($business && $business->usesRestaurantMode(), 404);

        $tables = RestaurantTable::query()
            ->with('branch')
            ->where('business_id', $business->id)
            ->orderBy('branch_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('restaurant-tables.index', compact('business', 'tables'));
    }

    public function create(Request $request)
    {
        $business = $request->user()->business;
        $branches = Branch::query()
            ->where('business_id', $business->id)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->pluck('name', 'id');

        return view('restaurant-tables.create', compact('business', 'branches'));
    }

    public function store(Request $request)
    {
        $business = $request->user()->business;

        $data = $request->validate([
            'branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')->where(fn ($q) => $q->where('business_id', $business->id)),
            ],
            'name' => 'required|string|max:80',
            'code' => 'nullable|string|max:30',
            'capacity' => 'nullable|integer|min:1|max:99',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $table = $this->tableService->create($business, $data);

        AuditLogger::record('restaurant_table_created', $table, null, $table->toArray());

        return redirect()
            ->to(tenant_route('tenant.restaurant-tables.index'))
            ->with('success', 'Table added.');
    }

    public function edit(Business $business, RestaurantTable $restaurantTable)
    {
        $this->ensureSameBusiness($restaurantTable);

        $branches = Branch::query()
            ->where('business_id', $business->id)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->pluck('name', 'id');

        return view('restaurant-tables.edit', compact('restaurantTable', 'branches'));
    }

    public function update(Request $request, Business $business, RestaurantTable $restaurantTable)
    {
        $this->ensureSameBusiness($restaurantTable);

        $data = $request->validate([
            'branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')->where(fn ($q) => $q->where('business_id', $business->id)),
            ],
            'name' => 'required|string|max:80',
            'code' => 'nullable|string|max:30',
            'capacity' => 'nullable|integer|min:1|max:99',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $old = $restaurantTable->toArray();
        $table = $this->tableService->update($restaurantTable, $data);

        AuditLogger::record('restaurant_table_updated', $table, $old, $table->toArray());

        return redirect()
            ->to(tenant_route('tenant.restaurant-tables.index'))
            ->with('success', 'Table updated.');
    }

    public function destroy(Business $business, RestaurantTable $restaurantTable)
    {
        $this->ensureSameBusiness($restaurantTable);

        $old = $restaurantTable->toArray();
        $restaurantTable->delete();

        AuditLogger::record('restaurant_table_deleted', $restaurantTable, $old, null);

        return back()->with('success', 'Table removed.');
    }

    protected function ensureSameBusiness(RestaurantTable $table): void
    {
        abort_unless((int) $table->business_id === (int) auth()->user()->business_id, 404);
    }
}
