<?php

namespace App\Http\Controllers;

use App\Helpers\AuditLogger;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExpenseCategoryController extends Controller
{
    public function quickStore(Request $request)
    {
        $this->authorize('create', ExpenseCategory::class);

        $businessId = (int) $request->user()->business_id;

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('expense_categories', 'name')->where(fn ($q) => $q->where('business_id', $businessId)),
            ],
        ]);

        $category = ExpenseCategory::create([
            'business_id' => $businessId,
            'name' => $data['name'],
            'slug' => ExpenseCategory::uniqueSlug($businessId, $data['name']),
            'is_active' => true,
        ]);

        AuditLogger::record('expense_category_created', $category, null, $category->toArray());

        return response()->json([
            'slug' => $category->slug,
            'name' => $category->name,
        ]);
    }
}
