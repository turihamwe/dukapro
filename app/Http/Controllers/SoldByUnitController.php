<?php

namespace App\Http\Controllers;

use App\Helpers\AuditLogger;
use App\Models\SoldByUnit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SoldByUnitController extends Controller
{
    public function quickStore(Request $request)
    {
        $this->authorize('create', SoldByUnit::class);

        $businessId = (int) $request->user()->business_id;

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('sold_by_units', 'name')->where(fn ($q) => $q->where('business_id', $businessId)),
            ],
        ]);

        $unit = SoldByUnit::create([
            'business_id' => $businessId,
            'name' => $data['name'],
            'slug' => SoldByUnit::uniqueSlug($businessId, $data['name']),
            'is_active' => true,
        ]);

        AuditLogger::record('sold_by_unit_created', $unit, null, $unit->toArray());

        return response()->json([
            'slug' => $unit->slug,
            'name' => $unit->name,
        ]);
    }
}
