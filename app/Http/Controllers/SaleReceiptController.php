<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Sale;
use App\Support\SaleReceipt;
use Illuminate\Http\Request;

class SaleReceiptController extends Controller
{
    public function show(Business $business, Sale $sale, Request $request)
    {
        abort_unless((int) $sale->business_id === (int) $business->id, 404);
        $this->authorize('viewReceipt', $sale);

        $sale = SaleReceipt::load($sale);
        $defaultPhone = $request->query('phone') ?: optional($sale->customer)->phone;

        return view('sales.receipt', [
            'business' => $business,
            'sale' => $sale,
            'receiptMessage' => SaleReceipt::message($sale),
            'whatsAppUrl' => SaleReceipt::whatsAppUrl($sale, $defaultPhone),
            'defaultPhone' => $defaultPhone,
        ]);
    }
}
