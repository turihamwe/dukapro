<?php

namespace App\Http\Controllers;

use App\Helpers\AuditLogger;
use App\Models\Business;
use App\Models\PaymentWallet;
use App\Models\PaymentWalletTransaction;
use App\Services\PaymentWalletCsvExportService;
use App\Services\PaymentWalletCsvImportService;
use App\Services\PaymentWalletService;
use App\Support\WalletInflowCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class PaymentWalletController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage-wallets');
        $this->middleware('payment.wallets');
        $this->middleware('management.access');
    }

    public function index(Request $request, PaymentWalletService $walletService)
    {
        $businessId = (int) $request->user()->business_id;

        $wallets = PaymentWallet::query()
            ->where('business_id', $businessId)
            ->orderByDesc('is_active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $summary = $walletService->summaryForBusiness($businessId);

        $recentInflows = PaymentWalletTransaction::query()
            ->where('business_id', $businessId)
            ->where('direction', PaymentWalletTransaction::DIRECTION_IN)
            ->with(['wallet:id,name,type', 'user:id,name'])
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit(25)
            ->get();

        return view('wallets.index', [
            'wallets' => $wallets,
            'totalLiquid' => $summary['total_liquid'],
            'types' => PaymentWallet::types(),
            'inflowCategories' => WalletInflowCategory::all(),
            'recentInflows' => $recentInflows,
        ]);
    }

    public function transactions(Request $request, Business $business, PaymentWallet $wallet, PaymentWalletService $walletService)
    {
        if ((int) $wallet->business_id !== (int) $business->id) {
            abort(404);
        }

        $limit = (int) $request->query('limit', 50);

        return response()->json([
            'wallet' => [
                'id' => $wallet->id,
                'name' => $wallet->name,
                'type_label' => $wallet->typeLabel(),
                'current_balance' => (float) $wallet->current_balance,
            ],
            'permissions' => [
                'can_manage' => $request->user()->can('adjust-wallet-transactions'),
            ],
            'transactions' => $walletService->transactionHistory($wallet, $limit),
        ]);
    }

    public function updateTransaction(
        Request $request,
        Business $business,
        PaymentWallet $wallet,
        PaymentWalletTransaction $transaction,
        PaymentWalletService $walletService
    ) {
        $this->authorize('adjust-wallet-transactions');

        if ((int) $wallet->business_id !== (int) $business->id
            || (int) $transaction->business_id !== (int) $business->id
            || (int) $transaction->payment_wallet_id !== (int) $wallet->id) {
            abort(404);
        }

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'category' => ['nullable', Rule::in(array_keys(WalletInflowCategory::all()))],
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $updated = $walletService->updateTransaction($request->user(), $transaction, $data);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        }

        $wallet->refresh();

        return response()->json([
            'message' => 'Transaction updated.',
            'wallet' => [
                'id' => $wallet->id,
                'current_balance' => (float) $wallet->current_balance,
            ],
            'transaction' => [
                'id' => $updated->id,
                'amount' => (float) $updated->amount,
            ],
        ]);
    }

    public function destroyTransaction(
        Request $request,
        Business $business,
        PaymentWallet $wallet,
        PaymentWalletTransaction $transaction,
        PaymentWalletService $walletService
    ) {
        $this->authorize('adjust-wallet-transactions');

        if ((int) $wallet->business_id !== (int) $business->id
            || (int) $transaction->business_id !== (int) $business->id
            || (int) $transaction->payment_wallet_id !== (int) $wallet->id) {
            abort(404);
        }

        try {
            $walletService->deleteTransaction($request->user(), $transaction);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        }

        $wallet->refresh();

        return response()->json([
            'message' => 'Transaction removed and balance adjusted.',
            'wallet' => [
                'id' => $wallet->id,
                'current_balance' => (float) $wallet->current_balance,
            ],
        ]);
    }

    public function deposit(Request $request, Business $business, PaymentWalletService $walletService)
    {
        $data = $request->validate([
            'payment_wallet_id' => 'required|integer|exists:payment_wallets,id',
            'amount' => 'required|numeric|min:0.01',
            'category' => ['required', Rule::in(array_keys(WalletInflowCategory::all()))],
            'source_name' => 'nullable|string|max:500',
            'occurred_at' => 'nullable|date',
        ]);

        $wallet = PaymentWallet::query()
            ->where('business_id', (int) $business->id)
            ->whereKey($data['payment_wallet_id'])
            ->firstOrFail();

        if (! $wallet->is_active) {
            return back()->withErrors(['amount' => 'Cannot deposit into an inactive wallet.']);
        }

        $occurredAt = ! empty($data['occurred_at']) ? Carbon::parse($data['occurred_at']) : null;

        $walletService->recordManualInflow(
            $request->user(),
            $wallet,
            (float) $data['amount'],
            $data['category'],
            $data['source_name'] ?? null,
            $occurredAt
        );

        return redirect()
            ->to(tenant_route('tenant.wallets.index'))
            ->with('success', 'Funds added to ' . $wallet->name . '.');
    }

    public function downloadImportTemplate(PaymentWalletCsvExportService $exportService)
    {
        return response($exportService->templateCsvContents(), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="wallet-import-template.csv"',
        ]);
    }

    public function exportWallets(Request $request, PaymentWalletCsvExportService $exportService)
    {
        return $exportService->download($request->user()->business, $request->user());
    }

    public function storeImport(Request $request, PaymentWalletCsvImportService $importService)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        try {
            $result = $importService->import($request->user()->business, $request->user(), $request->file('csv_file'));
        } catch (\RuntimeException $exception) {
            return redirect()
                ->to(tenant_route('tenant.wallets.index'))
                ->with('error', $exception->getMessage())
                ->with('open_import_wallets_modal', true);
        }

        $message = 'Import complete: ' . $result['inserted'] . ' wallet(s) added.';
        if ($result['skipped_duplicates'] > 0) {
            $message .= ' ' . $result['skipped_duplicates'] . ' duplicate(s) skipped.';
        }
        if ($result['skipped_errors'] > 0) {
            $message .= ' ' . $result['skipped_errors'] . ' row(s) had errors.';
        }

        return redirect()
            ->to(tenant_route('tenant.wallets.index'))
            ->with('success', $message)
            ->with('import_errors', array_slice($result['errors'], 0, 50));
    }

    public function store(Request $request, PaymentWalletService $walletService)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'type' => ['required', Rule::in(array_keys(PaymentWallet::types()))],
            'opening_balance' => 'nullable|numeric|min:0',
        ]);

        $walletService->create($request->user(), $data);

        return redirect()
            ->to(tenant_route('tenant.wallets.index'))
            ->with('success', 'Wallet added.');
    }

    public function update(Request $request, Business $business, PaymentWallet $wallet)
    {
        if ((int) $wallet->business_id !== (int) $business->id) {
            abort(404);
        }

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'type' => ['required', Rule::in(array_keys(PaymentWallet::types()))],
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0|max:9999',
        ]);

        $old = $wallet->toArray();

        $wallet->update([
            'name' => $data['name'],
            'type' => $data['type'],
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) ($data['sort_order'] ?? $wallet->sort_order),
        ]);

        AuditLogger::record(
            'payment_wallet_updated',
            $wallet,
            $old,
            $wallet->fresh()->toArray(),
            (int) $business->id,
            (int) $request->user()->id
        );

        return redirect()
            ->to(tenant_route('tenant.wallets.index'))
            ->with('success', 'Wallet updated.');
    }
}
