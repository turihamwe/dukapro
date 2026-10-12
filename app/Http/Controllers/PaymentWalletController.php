<?php

namespace App\Http\Controllers;

use App\Helpers\AuditLogger;
use App\Models\Business;
use App\Models\PaymentWallet;
use App\Models\PaymentWalletTransaction;
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
