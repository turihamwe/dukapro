<?php

namespace App\Http\Controllers;

use App\Services\ErrorLogService;
use Illuminate\Http\Request;

class ClientErrorReportController extends Controller
{
    protected ErrorLogService $errorLogService;

    public function __construct(ErrorLogService $errorLogService)
    {
        $this->errorLogService = $errorLogService;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'message' => 'required|string|max:2000',
            'stack' => 'nullable|string|max:65000',
            'url' => 'nullable|string|max:2000',
            'source' => 'nullable|string|max:500',
            'user_agent' => 'nullable|string|max:500',
            'payload' => 'nullable|array',
        ]);

        $this->errorLogService->logFrontendReport($data, $request);

        return response()->json(['ok' => true]);
    }
}
