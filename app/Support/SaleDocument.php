<?php

namespace App\Support;

use App\Models\Sale;

class SaleDocument
{
    public static function isInvoice(Sale $sale): bool
    {
        return (bool) $sale->is_credit_sale && ! $sale->credit_settled_at;
    }

    public static function hasCompanionReceipt(Sale $sale): bool
    {
        return (bool) ($sale->companion_receipt_issued ?? false);
    }

    public static function type(Sale $sale): string
    {
        if (self::hasCompanionReceipt($sale)) {
            return 'invoice_pair';
        }

        return self::isInvoice($sale) ? 'invoice' : 'receipt';
    }

    public static function title(Sale $sale): string
    {
        if (self::hasCompanionReceipt($sale)) {
            return 'Invoice & Receipt';
        }

        return self::isInvoice($sale) ? 'Invoice' : 'Receipt';
    }

    public static function url(Sale $sale): string
    {
        return self::isInvoice($sale) ? self::invoiceUrl($sale) : self::receiptUrl($sale);
    }

    public static function receiptUrl(Sale $sale): string
    {
        return tenant_route('tenant.sales.receipt', ['sale' => $sale->id]);
    }

    public static function invoiceUrl(Sale $sale): string
    {
        return tenant_route('tenant.sales.invoice', ['sale' => $sale->id]);
    }

    public static function load(Sale $sale): Sale
    {
        return SaleReceipt::load($sale);
    }

    public static function message(Sale $sale): string
    {
        $sale = self::load($sale);

        if (self::hasCompanionReceipt($sale)) {
            return self::pairedMessage($sale);
        }

        if (self::isInvoice($sale)) {
            return self::invoiceMessage($sale);
        }

        return SaleReceipt::message($sale);
    }

    public static function whatsAppUrl(Sale $sale, ?string $phone = null): string
    {
        return whatsapp_share_url($phone, self::message($sale));
    }

    public static function pairedMessage(Sale $sale): string
    {
        $sale = self::load($sale);

        return implode("\n\n", array_filter([
            self::invoiceMessage($sale),
            '──────────────',
            SaleReceipt::companionMessage($sale),
        ]));
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
        $lines[] = 'Amount: ' . format_money($sale->total, $business);

        if ($sale->customer) {
            $lines[] = 'Account balance after this invoice: '
                . format_money($sale->customer->outstanding_balance, $business);
        }

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
