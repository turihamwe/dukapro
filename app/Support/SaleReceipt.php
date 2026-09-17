<?php

namespace App\Support;

use App\Models\Sale;

class SaleReceipt
{
    public static function load(Sale $sale): Sale
    {
        return $sale->loadMissing([
            'items',
            'business',
            'user',
            'waiter',
            'customer',
            'kitchenOrder',
            'branch',
        ]);
    }

    public static function companionMessage(Sale $sale): string
    {
        $sale = self::load($sale);
        $business = $sale->business;

        $lines = [
            "Receipt confirmation — {$business->name}",
            "Receipt #{$sale->sale_number}",
        ];

        if ($sale->completed_at) {
            $lines[] = 'Date: ' . $sale->completed_at->format('M j, Y g:i A');
        }

        if ($sale->customer) {
            $lines[] = "Customer: {$sale->customer->name}";
        }

        $lines[] = '';

        foreach ($sale->items as $item) {
            $line = self::formatQuantity($item->quantity) . ' x ' . $item->product_name
                . ' — ' . format_money($item->subtotal, $business);
            if ($item->notes) {
                $line .= ' (' . $item->notes . ')';
            }
            $lines[] = $line;
        }

        $lines[] = '';
        $lines[] = 'Total: ' . format_money($sale->total, $business);
        $lines[] = 'Payment: Invoice (on account)';

        if ($business->phone) {
            $lines[] = 'Contact: ' . $business->phone;
        }

        $lines[] = '';
        $lines[] = 'PAID — Thank you!';

        return implode("\n", $lines);
    }

    public static function message(Sale $sale): string
    {
        $sale = self::load($sale);
        $business = $sale->business;

        $lines = [
            "Receipt — {$business->name}",
            "Sale #{$sale->sale_number}",
        ];

        if ($sale->completed_at) {
            $lines[] = $sale->completed_at->format('M j, Y g:i A');
        }

        $table = $sale->tableDisplay();
        if ($table) {
            $lines[] = "Table: {$table}";
        }

        if ($sale->waiter) {
            $lines[] = "Server: {$sale->waiter->name}";
        }

        if ($sale->customer) {
            $lines[] = "Customer: {$sale->customer->name}";
        }

        $lines[] = '';

        foreach ($sale->items as $item) {
            $line = self::formatQuantity($item->quantity) . ' x ' . $item->product_name
                . ' — ' . format_money($item->subtotal, $business);
            if ($item->notes) {
                $line .= ' (' . $item->notes . ')';
            }
            $lines[] = $line;
        }

        $lines[] = '';
        $lines[] = 'Subtotal: ' . format_money($sale->subtotal, $business);

        if ($sale->discount_amount > 0) {
            $lines[] = 'Discount: ' . format_money($sale->discount_amount, $business);
        }

        if ($sale->tax_amount > 0) {
            $lines[] = 'Tax: ' . format_money($sale->tax_amount, $business);
        }

        $lines[] = 'Total: ' . format_money($sale->total, $business);
        $lines[] = 'Payment: ' . self::paymentLabel($sale);

        if ($sale->efris_fdn) {
            $lines[] = 'EFRIS FDN: ' . $sale->efris_fdn;
        }

        if ($sale->efris_qr_code) {
            $lines[] = 'Verify: ' . $sale->efris_qr_code;
        }

        if ($sale->is_credit_sale && ! $sale->credit_settled_at && ! SaleDocument::hasCompanionReceipt($sale)) {
            $lines[] = 'Status: Credit — payment pending';
        }

        if ($business->phone) {
            $lines[] = '';
            $lines[] = 'Contact: ' . $business->phone;
        }

        $lines[] = '';
        $lines[] = 'Thank you for your purchase!';

        return implode("\n", $lines);
    }

    public static function whatsAppUrl(Sale $sale, ?string $phone = null): string
    {
        return whatsapp_share_url($phone, self::message($sale));
    }

    protected static function paymentLabel(Sale $sale): string
    {
        $method = ucfirst(str_replace('_', ' ', (string) $sale->payment_method));

        if ($sale->payment_method === 'mobile_money' && $sale->mobile_money_provider) {
            $method .= ' (' . strtoupper($sale->mobile_money_provider) . ')';
        }

        return $method;
    }

    protected static function formatQuantity(float $quantity): string
    {
        if (floor($quantity) == $quantity) {
            return (string) (int) $quantity;
        }

        return rtrim(rtrim(number_format($quantity, 2, '.', ''), '0'), '.');
    }
}
