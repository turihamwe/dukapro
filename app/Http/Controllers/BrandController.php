<?php

namespace App\Http\Controllers;

use App\Helpers\AuditLogger;
use App\Models\Brand;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Brand::class, 'brand');
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));

        $query = Brand::query()->withCount('products')->orderBy('name');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        $brands = $query->paginate(20)->appends(['search' => $search]);

        return view('brands.index', compact('brands', 'search'));
    }

    public function create()
    {
        return view('brands.create');
    }

    public function store(Request $request)
    {
        $businessId = (int) $request->user()->business_id;

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('brands', 'name')->where(fn ($q) => $q->where('business_id', $businessId)),
            ],
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        $brand = Brand::create([
            'business_id' => $businessId,
            'name' => $data['name'],
            'slug' => Brand::uniqueSlug($businessId, $data['name']),
            'description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        AuditLogger::record('brand_created', $brand, null, $brand->toArray());

        return redirect()->to(tenant_route('tenant.brands.index'))->with('success', 'Brand created.');
    }

    public function edit(Business $business, Brand $brand)
    {
        return view('brands.edit', compact('brand'));
    }

    public function update(Request $request, Business $business, Brand $brand)
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('brands', 'name')
                    ->where(fn ($q) => $q->where('business_id', $business->id))
                    ->ignore($brand->id),
            ],
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        $old = $brand->toArray();

        $brand->update([
            'name' => $data['name'],
            'slug' => Brand::uniqueSlug($business->id, $data['name'], $brand->id),
            'description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        AuditLogger::record('brand_updated', $brand, $old, $brand->fresh()->toArray());

        return redirect()->to(tenant_route('tenant.brands.index'))->with('success', 'Brand updated.');
    }

    public function destroy(Business $business, Brand $brand)
    {
        $old = $brand->toArray();
        $brand->products()->update(['brand_id' => null]);
        $brand->delete();

        AuditLogger::record('brand_deleted', $brand, $old, null);

        return redirect()->to(tenant_route('tenant.brands.index'))->with('success', 'Brand removed.');
    }

    public function quickStore(Request $request)
    {
        $this->authorize('create', Brand::class);

        $businessId = (int) $request->user()->business_id;

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('brands', 'name')->where(fn ($q) => $q->where('business_id', $businessId)),
            ],
        ]);

        $brand = Brand::create([
            'business_id' => $businessId,
            'name' => $data['name'],
            'slug' => Brand::uniqueSlug($businessId, $data['name']),
            'is_active' => true,
        ]);

        AuditLogger::record('brand_created', $brand, null, $brand->toArray());

        return response()->json([
            'id' => $brand->id,
            'name' => $brand->name,
        ]);
    }
}
