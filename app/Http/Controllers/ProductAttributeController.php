<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductAttributeController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view-inventory');
        $this->middleware('management.access');
    }

    public function index(Request $request)
    {
        $business = $request->user()->business;

        $attributes = ProductAttribute::query()
            ->with('values')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('inventory.attributes.index', compact('attributes', 'business'));
    }

    public function store(Request $request)
    {
        $this->authorize('create-inventory');

        $businessId = (int) $request->user()->business_id;

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('product_attributes', 'name')->where(fn ($q) => $q->where('business_id', $businessId)),
            ],
            'values' => 'required|string|max:2000',
        ]);

        $attribute = ProductAttribute::create([
            'business_id' => $businessId,
            'name' => $data['name'],
            'sort_order' => (int) ProductAttribute::where('business_id', $businessId)->max('sort_order') + 1,
        ]);

        $this->syncValues($attribute, $data['values']);

        return redirect()
            ->to(tenant_route('tenant.inventory.attributes.index'))
            ->with('success', 'Attribute added.');
    }

    public function quickStore(Request $request)
    {
        $this->authorize('create-inventory');

        $businessId = (int) $request->user()->business_id;

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('product_attributes', 'name')->where(fn ($q) => $q->where('business_id', $businessId)),
            ],
            'values' => 'nullable|array',
            'values.*' => 'string|max:100',
            'values_text' => 'nullable|string|max:2000',
        ]);

        $attribute = ProductAttribute::create([
            'business_id' => $businessId,
            'name' => $data['name'],
            'sort_order' => (int) ProductAttribute::where('business_id', $businessId)->max('sort_order') + 1,
        ]);

        $values = $data['values'] ?? [];
        if (! empty($data['values_text'])) {
            $values = array_merge($values, preg_split('/\s*,\s*/', $data['values_text']));
        }

        $this->syncValues($attribute, implode(', ', array_filter(array_map('trim', $values))));

        $attribute->load('values');

        return response()->json([
            'attribute' => [
                'id' => $attribute->id,
                'name' => $attribute->name,
                'values' => $attribute->values->map(fn ($value) => [
                    'id' => $value->id,
                    'value' => $value->value,
                ])->values(),
            ],
        ]);
    }

    public function quickValue(Request $request)
    {
        $this->authorize('create-inventory');

        $data = $request->validate([
            'attribute_id' => 'required|exists:product_attributes,id',
            'value' => 'required|string|max:100',
        ]);

        $attribute = ProductAttribute::query()
            ->where('business_id', $request->user()->business_id)
            ->whereKey($data['attribute_id'])
            ->firstOrFail();

        $record = ProductAttributeValue::firstOrCreate(
            [
                'product_attribute_id' => $attribute->id,
                'value' => trim($data['value']),
            ],
            [
                'sort_order' => (int) $attribute->values()->max('sort_order') + 1,
            ]
        );

        return response()->json([
            'value' => [
                'id' => $record->id,
                'value' => $record->value,
            ],
            'attribute_id' => $attribute->id,
        ]);
    }

    public function update(Request $request, Business $business, ProductAttribute $attribute)
    {
        $this->authorize('update-inventory');

        if ((int) $attribute->business_id !== (int) $business->id) {
            abort(404);
        }

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('product_attributes', 'name')
                    ->where(fn ($q) => $q->where('business_id', $business->id))
                    ->ignore($attribute->id),
            ],
            'values' => 'required|string|max:2000',
        ]);

        $attribute->update(['name' => $data['name']]);
        $this->syncValues($attribute, $data['values']);

        return redirect()
            ->to(tenant_route('tenant.inventory.attributes.index'))
            ->with('success', 'Attribute updated.');
    }

    public function destroy(Business $business, ProductAttribute $attribute)
    {
        $this->authorize('delete-inventory');

        if ((int) $attribute->business_id !== (int) $business->id) {
            abort(404);
        }

        $attribute->delete();

        return redirect()
            ->to(tenant_route('tenant.inventory.attributes.index'))
            ->with('success', 'Attribute removed.');
    }

    protected function syncValues(ProductAttribute $attribute, string $rawValues): void
    {
        $values = collect(preg_split('/\s*,\s*/', trim($rawValues)))
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values();

        $attribute->values()->whereNotIn('value', $values->all())->delete();

        foreach ($values as $index => $value) {
            ProductAttributeValue::updateOrCreate(
                [
                    'product_attribute_id' => $attribute->id,
                    'value' => $value,
                ],
                ['sort_order' => $index]
            );
        }
    }
}
