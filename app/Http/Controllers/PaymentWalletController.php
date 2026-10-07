<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\PaymentWallet;
use App\Services\PaymentWalletService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentWalletController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage-wallets');
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

        return view('wallets.index', [
            'wallets' => $wallets,
            'totalLiquid' => $summary['total_liquid'],
            'types' => PaymentWallet::types(),
        ]);
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

        $wallet->update([
            'name' => $data['name'],
            'type' => $data['type'],
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) ($data['sort_order'] ?? $wallet->sort_order),
        ]);

        return redirect()
            ->to(tenant_route('tenant.wallets.index'))
            ->with('success', 'Wallet updated.');
    }
}
