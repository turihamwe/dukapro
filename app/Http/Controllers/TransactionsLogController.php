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

    public function feed(Request $request, TransactionsLogService $log)
    {
        $data = $log->forRequest($request);
        $rows = $data['rows'];
        $entityName = $data['entity']['name'] ?? null;
        $typeLabel = $data['types'][$data['type']] ?? 'Transactions';

        return response()->json([
            'title' => $entityName ? 'History for '.$entityName : $typeLabel,
            'range' => $data['range'],
            'range_label' => $data['rangeLabel'],
            'start_date' => $data['startDate'],
            'end_date' => $data['endDate'],
            'notice' => $data['notice'],
            'branch' => [
                'selected' => $data['branch']['selected'],
                'show_picker' => (bool) ($data['branch']['show_picker'] ?? false),
                'options' => collect($data['branch']['branches'] ?? [])->map(function ($branch) {
                    return [
                        'id' => (int) $branch->id,
                        'name' => $branch->name,
                    ];
                })->values(),
            ],
            'rows' => $rows->getCollection()->map(function ($row) {
                return [
                    'occurred_at' => $row->occurred_label,
                    'category' => $row->category,
                    'entity_name' => $row->entity_name,
                    'activity' => $row->activity,
                    'reference_no' => $row->reference_no,
                    'impact' => $row->impact_label,
                    'negative' => (float) $row->impact_value < 0,
                    'branch' => $row->branch_name,
                ];
            })->values(),
            'page' => $rows->currentPage(),
            'last_page' => $rows->lastPage(),
            'total' => $rows->total(),
        ]);
    }
}
