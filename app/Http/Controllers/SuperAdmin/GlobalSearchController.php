<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Support\SearchQuery;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function index(Request $request)
    {
        $query = trim((string) $request->input('q', ''));
        $results = [];

        if ($query !== '') {
            $results['businesses'] = Business::query()
                ->tap(fn ($q) => SearchQuery::applyTokenizedSearch($q, [
                    'name', 'email', 'slug', 'portal_slug', 'phone', 'business_type',
                ], $query))
                ->limit(8)
                ->get();

            $results['users'] = User::query()
                ->with('business')
                ->whereNotNull('business_id')
                ->where('is_super_admin', false)
                ->where('is_sub_admin', false)
                ->tap(fn ($q) => SearchQuery::applyTokenizedSearch($q, [
                    'name', 'email', 'username', 'phone', 'role',
                ], $query, [
                    'business' => ['name', 'slug', 'email'],
                ]))
                ->limit(8)
                ->get();

            $results['products'] = Product::with('business')
                ->tap(fn ($q) => SearchQuery::applyTokenizedSearch($q, [
                    'name', 'sku', 'description', 'measurement_unit',
                ], $query, [
                    'business' => ['name', 'slug'],
                ]))
                ->limit(8)
                ->get();

            $results['customers'] = Customer::with('business')
                ->tap(fn ($q) => SearchQuery::applyTokenizedSearch($q, [
                    'name', 'phone', 'email', 'company_name', 'address', 'notes',
                ], $query, [
                    'business' => ['name', 'slug'],
                ]))
                ->limit(8)
                ->get();

            $results['sales'] = Sale::with(['business', 'customer'])
                ->tap(fn ($q) => SearchQuery::applyTokenizedSearch($q, [
                    'sale_number', 'payment_method', 'status', 'notes',
                ], $query, [
                    'business' => ['name', 'slug'],
                    'customer' => ['name', 'phone', 'email', 'company_name'],
                ]))
                ->limit(8)
                ->get();

            $results['expenses'] = Expense::with('business')
                ->tap(fn ($q) => SearchQuery::applyTokenizedSearch($q, [
                    'title', 'category', 'description', 'receipt_reference',
                ], $query, [
                    'business' => ['name', 'slug'],
                ]))
                ->limit(8)
                ->get();
        }

        return view('superadmin.search', compact('query', 'results'));
    }
}
