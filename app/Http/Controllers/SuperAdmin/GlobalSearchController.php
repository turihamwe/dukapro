<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SystemAuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class GlobalSearchController extends Controller
{
    public function index(Request $request)
    {
        $query = trim((string) $request->input('q', ''));
        $results = [];

        if ($query !== '') {
            $results['businesses'] = Business::query()
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', '%' . $query . '%')
                        ->orWhere('email', 'like', '%' . $query . '%')
                        ->orWhere('slug', 'like', '%' . $query . '%')
                        ->orWhere('portal_slug', 'like', '%' . $query . '%');
                })
                ->limit(8)
                ->get();

            $results['users'] = User::query()
                ->with('business')
                ->whereNotNull('business_id')
                ->where('is_super_admin', false)
                ->where('is_sub_admin', false)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', '%' . $query . '%')
                        ->orWhere('email', 'like', '%' . $query . '%')
                        ->orWhere('username', 'like', '%' . $query . '%');
                })
                ->limit(8)
                ->get();

            $results['products'] = Product::with('business')
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', '%' . $query . '%')
                        ->orWhere('sku', 'like', '%' . $query . '%');
                })
                ->limit(8)
                ->get();

            $results['customers'] = Customer::with('business')
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', '%' . $query . '%')
                        ->orWhere('phone', 'like', '%' . $query . '%')
                        ->orWhere('email', 'like', '%' . $query . '%');
                })
                ->limit(8)
                ->get();

            $results['sales'] = Sale::with('business')
                ->where(function ($q) use ($query) {
                    $q->where('sale_number', 'like', '%' . $query . '%')
                        ->orWhere('payment_method', 'like', '%' . $query . '%');
                })
                ->limit(8)
                ->get();

            $results['expenses'] = Expense::with('business')
                ->where(function ($q) use ($query) {
                    $q->where('title', 'like', '%' . $query . '%')
                        ->orWhere('category', 'like', '%' . $query . '%');
                })
                ->limit(8)
                ->get();
        }

        return view('superadmin.search', compact('query', 'results'));
    }
}
