<?php

namespace App\Http\Controllers;

use App\Services\TransactionsLogService;
use Illuminate\Http\Request;

class TransactionsLogController extends Controller
{
    public function index(Request $request, TransactionsLogService $log)
    {
        return view('reports.transactions-log', $log->forRequest($request));
    }
}
