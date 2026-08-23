<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SystemAuditLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = SystemAuditLog::with('business', 'user')->latest('created_at');

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('business_id')) {
            $query->where('business_id', $request->business_id);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($builder) use ($q) {
                $builder->where('summary', 'like', "%{$q}%")
                    ->orWhere('action', 'like', "%{$q}%");
            });
        }

        $logs = $query->paginate(30)->withQueryString();
        $actions = SystemAuditLog::distinct()->orderBy('action')->pluck('action');
        $businesses = \App\Models\Business::orderBy('name')->get(['id', 'name']);

        return view('superadmin.activity', compact('logs', 'actions', 'businesses'));
    }
}
