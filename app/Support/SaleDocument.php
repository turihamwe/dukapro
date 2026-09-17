<?php

namespace App\Support;

use App\Models\Sale;

class SaleDocument
{
    public static function isInvoice(Sale $sale): bool
    {
        return (bool) $sale->is_credit_sale && ! $sale->credit_settled_at;
    }

    public static function type(Sale $sale): string
    {
        return self::isInvoice($sale) ? 'invoice' : 'receipt';
    }

    public static function title(Sale $sale): string
    {
        return self::isInvoice($sale) ? 'Invoice' : 'Receipt';
    }

    public static function url(Sale $sale): string
    {
        $route = self::isInvoice($sale) ? 'tenant.sales.invoice' : 'tenant.sales.receipt';

        return tenant_route($route, ['sale' => $sale->id]);
    }

    public static function load(Sale $sale): Sale
    {
        return SaleReceipt::load($sale);
    }

    public static function message(Sale $sale): string
    {
        $sale = self::load($sale);

        if (self::isInvoice($sale)) {
            return self::invoiceMessage($sale);
        }

        return SaleReceipt::message($sale);
    }

    public static function whatsAppUrl(Sale $sale, ?string $phone = null): string
    {
        return whatsapp_share_url($phone, self::message($sale));
    }

    protected static function invoiceMessage(Sale $sale): string
    {
        $business = $sale->business;

        $lines = [
            "Invoice — {$business->name}",
            "Invoice #{$sale->sale_number}",
        ];

        if ($sale->completed_at) {
            $lines[] = 'Date: ' . $sale->completed_at->format('M j, Y g:i A');
        }

        if ($sale->invoice_due_at) {
            $lines[] = 'Due: ' . $sale->invoice_due_at->format('M j, Y');
        }

        if ($sale->customer) {
            $lines[] = "Customer: {$sale->customer->name}";
            if ($sale->customer->phone) {
                $lines[] = "Phone: {$sale->customer->phone}";
            }
        }

        $lines[] = '';

        foreach ($sale->items as $item) {
            $lines[] = self::formatQuantity($item->quantity) . ' x ' . $item->product_name
                . ' — ' . format_money($item->subtotal, $business);
        }

        $lines[] = '';
        $lines[] = 'Total due: ' . format_money($sale->total, $business);

        if ($sale->customer) {
            $lines[] = 'Account balance after this invoice: '
                . format_money($sale->customer->outstanding_balance, $business);
        }

        $lines[] = '';
        $lines[] = 'Please settle this invoice by the due date.';

        if ($business->phone) {
            $lines[] = 'Contact: ' . $business->phone;
        }

        return implode("\n", $lines);
    }

    protected static function formatQuantity(float $quantity): string
    {
        if (floor($quantity) == $quantity) {
            return (string) (int) $quantity;
        }

        return rtrim(rtrim(number_format($quantity, 2, '.', ''), '0'), '.');
    }
}
