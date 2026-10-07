<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Customer;
use App\Models\DebtLedgerEntry;
use App\Models\Sale;
use App\Services\CustomerCreditService;
use App\Services\DebtLedgerService;
use Illuminate\Http\Request;

class CustomerCreditController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:access-customer-credit');
        $this->middleware('customer.credit');
        $this->middleware('management.access');
    }

    public function index()
    {
        return redirect()->to(tenant_route('tenant.customer-credit.customers.index'));
    }

    public function invoices(Request $request)
    {
        $status = $request->input('status');

        $sales = Sale::query()
            ->with(['customer', 'user'])
            ->where('is_credit_sale', true)
            ->when($status === 'open', function ($q) {
                $q->whereHas('customer', function ($cq) {
                    $cq->where('outstanding_balance', '>', 0);
                });
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $totalReceivable = (float) Customer::query()
            ->where('is_credit_customer', true)
            ->sum('outstanding_balance');

        return view('customer-credit.invoices.index', [
            'sales' => $sales,
            'status' => $status,
            'totalReceivable' => $totalReceivable,
        ]);
    }

    public function showInvoice(Business $business, Sale $sale)
    {
        if ((int) $sale->business_id !== (int) $business->id || ! $sale->is_credit_sale) {
            abort(404);
        }

        $sale->load(['customer', 'items.product', 'user']);

        $ledgerEntries = DebtLedgerEntry::query()
            ->where('sale_id', $sale->id)
            ->with('user')
            ->get();

        return view('customer-credit.invoices.show', [
            'sale' => $sale,
            'ledgerEntries' => $ledgerEntries,
        ]);
    }

    public function destroyInvoice(Business $business, Sale $sale, CustomerCreditService $service, Request $request)
    {
        if ((int) $sale->business_id !== (int) $business->id) {
            abort(404);
        }

        $service->softDeleteInvoice($sale, $request->user());

        return redirect()
            ->to(tenant_route('tenant.customer-credit.invoices.index'))
            ->with('success', 'Credit invoice removed from active records. Stock levels were not changed.');
    }

    public function payments(Request $request)
    {
        $payments = DebtLedgerEntry::query()
            ->with(['customer', 'user'])
            ->where('type', 'payment')
            ->latest()
            ->paginate(25);

        return view('customer-credit.payments.index', [
            'payments' => $payments,
        ]);
    }

    public function destroyPayment(Business $business, DebtLedgerEntry $ledgerEntry, CustomerCreditService $service, Request $request)
    {
        if ((int) $ledgerEntry->business_id !== (int) $business->id) {
            abort(404);
        }

        $service->softDeletePayment($ledgerEntry, $request->user());

        return redirect()
            ->to(tenant_route('tenant.customer-credit.payments.index'))
            ->with('success', 'Payment removed and customer balance updated.');
    }

    public function recordPayment(Request $request, Business $business, Customer $customer, DebtLedgerService $debtLedgerService)
    {
        if ((int) $customer->business_id !== (int) $business->id) {
            abort(404);
        }

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        $debtLedgerService->recordPayment(
            $customer,
            $data['amount'],
            $request->user(),
            $data['description'] ?? null
        );

        return back()->with('success', 'Payment recorded.');
    }
}
