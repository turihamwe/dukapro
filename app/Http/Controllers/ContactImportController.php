<?php

namespace App\Http\Controllers;

use App\Helpers\AuditLogger;
use App\Services\ContactImportService;
use Illuminate\Http\Request;

class ContactImportController extends Controller
{
    protected ContactImportService $importService;

    public function __construct(ContactImportService $importService)
    {
        $this->importService = $importService;
        $this->middleware('can:manage-debts');
        $this->middleware('management.access');
    }

    public function show()
    {
        return view('contacts.import');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $parsed = $this->importService->parseCsv($request->file('csv_file'));
        $mapping = $this->importService->suggestMapping($parsed['headers']);

        $request->session()->put('contact_import', [
            'headers' => $parsed['headers'],
            'rows' => array_slice($parsed['rows'], 0, 500),
            'mapping' => $mapping,
        ]);

        return redirect()->to(tenant_route('tenant.contacts.import.map'));
    }

    public function map(Request $request)
    {
        $import = $request->session()->get('contact_import');

        if (! $import) {
            return redirect()->to(tenant_route('tenant.contacts.import.show'));
        }

        return view('contacts.import-map', [
            'headers' => $import['headers'],
            'preview' => array_slice($import['rows'], 0, 5),
            'mapping' => $import['mapping'],
            'fields' => ContactImportService::FIELDS,
            'totalRows' => count($import['rows']),
        ]);
    }

    public function process(Request $request)
    {
        $import = $request->session()->get('contact_import');

        if (! $import) {
            return redirect()->to(tenant_route('tenant.contacts.import.show'));
        }

        $mapping = $request->validate([
            'mapping' => 'required|array',
            'mapping.name' => 'required|string',
        ])['mapping'];

        $result = $this->importService->importRows(
            $request->user()->business,
            $import['rows'],
            $mapping
        );

        AuditLogger::record(
            'contacts_imported',
            $request->user()->business,
            null,
            $result,
            $request->user()->business_id,
            $request->user()->id
        );

        $request->session()->forget('contact_import');

        $message = "{$result['imported']} contacts imported.";
        if ($result['skipped'] > 0) {
            $message .= " {$result['skipped']} rows skipped.";
        }

        return redirect()
            ->to(tenant_route('tenant.contacts.index'))
            ->with('success', $message)
            ->with('import_errors', $result['errors']);
    }
}
