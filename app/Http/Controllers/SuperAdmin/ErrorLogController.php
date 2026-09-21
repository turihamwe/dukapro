<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\ErrorLog;
use App\Models\SystemSetting;
use App\Services\ErrorLogService;
use Illuminate\Http\Request;

class ErrorLogController extends Controller
{
    protected ErrorLogService $errorLogService;

    public function __construct(ErrorLogService $errorLogService)
    {
        $this->errorLogService = $errorLogService;
    }

    public function index(Request $request)
    {
        $query = ErrorLog::query()
            ->with(['business:id,name', 'user:id,name,email'])
            ->latest('created_at');

        if ($request->filled('environment') && in_array($request->environment, ['backend', 'frontend'], true)) {
            $query->where('environment', $request->environment);
        }

        if ($request->filled('business_id')) {
            $query->where('business_id', (int) $request->business_id);
        }

        if ($request->filled('device_type')) {
            $query->where('device_info->device_type', $request->device_type);
        }

        if ($request->filled('q')) {
            $term = $request->q;
            $query->where(function ($builder) use ($term) {
                $builder->where('error_message', 'like', "%{$term}%")
                    ->orWhere('url', 'like', "%{$term}%")
                    ->orWhere('exception_class', 'like', "%{$term}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('http_status')) {
            if ($request->http_status === '5xx') {
                $query->where('payload->http_status', '>=', 500);
            } else {
                $query->where('payload->http_status', (int) $request->http_status);
            }
        }

        $logs = $query->paginate(25)->withQueryString();

        $summary = [
            'total' => ErrorLog::count(),
            'backend' => ErrorLog::where('environment', ErrorLog::ENV_BACKEND)->count(),
            'frontend' => ErrorLog::where('environment', ErrorLog::ENV_FRONTEND)->count(),
            'last_24h' => ErrorLog::where('created_at', '>=', now()->subDay())->count(),
        ];

        $businesses = Business::orderBy('name')->get(['id', 'name']);

        $trackingEnabled = $this->errorLogService->enabled();
        $trackingEnvLocked = $this->errorLogService->envDisabled();

        return view('superadmin.errors.index', compact(
            'logs',
            'summary',
            'businesses',
            'trackingEnabled',
            'trackingEnvLocked'
        ));
    }

    public function updateSettings(Request $request)
    {
        if ($this->errorLogService->envDisabled()) {
            return back()->with('error', 'Error tracking is turned off in server configuration (ERROR_TRACKING_ENABLED=false). The admin toggle cannot enable it until that is changed.');
        }

        $request->validate([
            'enabled' => 'nullable|boolean',
        ]);

        $enabled = $request->boolean('enabled');
        SystemSetting::set('error_tracking_enabled', $enabled ? '1' : '0');

        return back()->with(
            'success',
            $enabled
                ? 'Error telemetry is now ON — new client and server errors will be recorded.'
                : 'Error telemetry is now OFF — no new errors will be recorded until you turn it back on.'
        );
    }

    public function show(ErrorLog $errorLog)
    {
        $errorLog->load(['business', 'user']);

        return view('superadmin.errors.show', [
            'log' => $errorLog,
        ]);
    }
}
