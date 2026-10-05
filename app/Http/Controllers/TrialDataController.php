<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Sale;
use App\Scopes\BranchScope;
use App\Services\TrialDataDeletionService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class TrialDataController extends Controller
{
    protected TrialDataDeletionService $trialDataDeletionService;

    public function __construct(TrialDataDeletionService $trialDataDeletionService)
    {
        $this->trialDataDeletionService = $trialDataDeletionService;
    }

    public function destroySale(Business $business, int $sale, Request $request)
    {
        $sale = Sale::query()
            ->withoutGlobalScope(BranchScope::class)
            ->where('business_id', $business->id)
            ->findOrFail($sale);

        $this->authorize('delete', $sale);

        try {
            $this->trialDataDeletionService->deleteSale($sale);
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Sale ' . $sale->sale_number . ' removed.');
    }

    public function purge(Business $business, Request $request)
    {
        abort_unless((int) $request->user()->business_id === (int) $business->id, 403);
        $this->authorize('manage-settings');

        $request->validate([
            'confirm_name' => 'required|string',
        ]);

        if (trim($request->input('confirm_name')) !== $business->name) {
            return back()
                ->withInput()
                ->with('error', 'Business name did not match. Trial data was not removed.');
        }

        try {
            $this->trialDataDeletionService->assertBusinessMayDeleteTrialData($business);
            $counts = $this->trialDataDeletionService->purgeTrialActivity($business);
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $salesRemoved = (int) ($counts['sales'] ?? 0);

        return redirect()
            ->to(tenant_route('tenant.business.edit') . '#trial-data')
            ->with(
                'success',
                'Trial activity removed: '
                . $salesRemoved . ' '
                . \Illuminate\Support\Str::plural('sale', $salesRemoved)
                . ', plus customers, expenses, and related records. Product stock was not changed — update inventory if needed.'
            );
    }
}
