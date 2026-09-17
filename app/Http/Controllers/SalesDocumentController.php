<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Sale;
use App\Scopes\BranchScope;
use App\Support\SaleDocument;
use Illuminate\Http\Request;

class SalesDocumentController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view-sales-documents')->only('index');
    }

    public function index(Request $request)
    {
        $business = $request->user()->business;
        $search = trim((string) $request->input('search', ''));
        $type = $request->input('type', 'all');
        $status = $request->input('status', 'all');

        $query = Sale::query()
            ->withoutGlobalScope(BranchScope::class)
            ->where('business_id', $business->id)
            ->where('status', 'completed')
            ->with(['customer', 'user'])
            ->orderByDesc('completed_at');

        if ($request->user()->isBranchScoped() && $request->user()->branch_id) {
            $query->where('branch_id', $request->user()->branch_id);
        }

        if ($type === 'invoice') {
            $query->where('is_credit_sale', true)->whereNull('credit_settled_at');
        } elseif ($type === 'receipt') {
            $query->where(function ($q) {
                $q->where('is_credit_sale', false)
                    ->orWhereNotNull('credit_settled_at');
            });
        }

        if ($status === 'open') {
            $query->where('is_credit_sale', true)->whereNull('credit_settled_at');
        } elseif ($status === 'paid') {
            $query->where(function ($q) {
                $q->where('is_credit_sale', false)
                    ->orWhereNotNull('credit_settled_at');
            });
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('sale_number', 'like', '%' . $search . '%')
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('phone', 'like', '%' . $search . '%');
                    });
            });
        }

        $documents = $query->paginate(25)->appends(array_filter([
            'search' => $search !== '' ? $search : null,
            'type' => $type !== 'all' ? $type : null,
            'status' => $status !== 'all' ? $status : null,
        ]));

        return view('sales.documents', compact('documents', 'search', 'type', 'status', 'business'));
    }

    public function showReceipt(Business $business, Sale $sale, Request $request)
    {
        abort_unless((int) $sale->business_id === (int) $business->id, 404);
        $this->authorize('viewReceipt', $sale);

        if (SaleDocument::isInvoice($sale)) {
            return redirect()->to(tenant_route('tenant.sales.invoice', ['sale' => $sale->id]));
        }

        $sale = SaleDocument::load($sale);
        $defaultPhone = $request->query('phone') ?: optional($sale->customer)->phone;

        return view('sales.receipt', [
            'business' => $business,
            'sale' => $sale,
            'receiptMessage' => SaleDocument::message($sale),
            'whatsAppUrl' => SaleDocument::whatsAppUrl($sale, $defaultPhone),
            'defaultPhone' => $defaultPhone,
        ]);
    }

    public function showInvoice(Business $business, Sale $sale, Request $request)
    {
        abort_unless((int) $sale->business_id === (int) $business->id, 404);
        $this->authorize('viewReceipt', $sale);

        if (! SaleDocument::isInvoice($sale)) {
            return redirect()->to(tenant_route('tenant.sales.receipt', ['sale' => $sale->id]));
        }

        $sale = SaleDocument::load($sale);
        $defaultPhone = $request->query('phone') ?: optional($sale->customer)->phone;

        return view('sales.invoice', [
            'business' => $business,
            'sale' => $sale,
            'invoiceMessage' => SaleDocument::message($sale),
            'whatsAppUrl' => SaleDocument::whatsAppUrl($sale, $defaultPhone),
            'defaultPhone' => $defaultPhone,
        ]);
    }
}
