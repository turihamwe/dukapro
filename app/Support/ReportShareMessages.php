<?php

namespace App\Support;

use App\Models\Business;
use App\Models\SupplierCreditPurchase;

class ReportShareMessages
{
    public static function salesSummary(Business $business, string $label, array $totals, ?string $branchName = null): string
    {
        $header = "Sales report — {$business->name}";
        if ($branchName) {
            $header .= " ({$branchName})";
        }

        return implode("\n", array_filter([
            $header,
            "Period: {$label}",
            '',
            'Total sales: ' . format_money($totals['sales_total'] ?? 0, $business),
            'Transactions: ' . number_format($totals['sales_count'] ?? 0),
            'Cash: ' . format_money($totals['cash'] ?? 0, $business),
            'Mobile money: ' . format_money($totals['mobile_money'] ?? 0, $business),
            'Bank: ' . format_money($totals['bank'] ?? 0, $business),
            'Credit: ' . format_money($totals['credit'] ?? 0, $business),
        ]));
    }

    public static function salesDay(Business $business, string $label, array $totals, ?string $branchName = null): string
    {
        $lines = [
            "Daily sales — {$business->name}",
        ];
        if ($branchName) {
            $lines[] = "Branch: {$branchName}";
        }
        $lines[] = "Date: {$label}";
        $lines[] = '';
        $lines[] = 'Total: ' . format_money($totals['sales_total'] ?? 0, $business);
        $lines[] = 'Transactions: ' . number_format($totals['sales_count'] ?? 0);
        $lines[] = 'Cash: ' . format_money($totals['cash'] ?? 0, $business);
        $lines[] = 'Mobile money: ' . format_money($totals['mobile_money'] ?? 0, $business);
        $lines[] = 'Bank: ' . format_money($totals['bank'] ?? 0, $business);
        $lines[] = 'Credit: ' . format_money($totals['credit'] ?? 0, $business);

        return implode("\n", $lines);
    }

    public static function incomeStatement(Business $business, string $label, array $statement): string
    {
        return implode("\n", [
            "Income statement — {$business->name}",
            "Period: {$label}",
            '',
            'Revenue: ' . format_money($statement['revenue'] ?? 0, $business),
            'Cost of goods sold: ' . format_money($statement['cost_of_goods_sold'] ?? 0, $business),
            'Gross profit: ' . format_money($statement['gross_profit'] ?? 0, $business),
            'Operating expenses: ' . format_money($statement['total_expenses'] ?? 0, $business),
            'Net income: ' . format_money($statement['net_income'] ?? 0, $business),
        ]);
    }

    public static function balanceSheet(Business $business, string $label, array $sheet): string
    {
        $assetBlock = $sheet['assets'] ?? [];
        $assets = $assetBlock['total'] ?? 0;
        $liabilities = $sheet['liabilities']['total'] ?? 0;
        $net = $sheet['net_position'] ?? ($assets - $liabilities);

        $lines = [
            "Balance sheet — {$business->name}",
            "As of: {$label}",
            '',
            'Inventory: ' . format_money($assetBlock['inventory'] ?? 0, $business),
            'Accounts receivable: ' . format_money($assetBlock['accounts_receivable'] ?? 0, $business),
        ];

        if (($assetBlock['cash_source'] ?? '') === 'wallets' && ! empty($assetBlock['wallets'])) {
            $lines[] = 'Cash & equivalents (wallets):';
            foreach ($assetBlock['wallets'] as $wallet) {
                $lines[] = '  · ' . ($wallet['name'] ?? 'Wallet') . ' (' . ($wallet['type_label'] ?? '') . '): '
                    . format_money($wallet['balance'] ?? 0, $business);
            }
            $lines[] = '  Subtotal: ' . format_money($assetBlock['cash_and_equivalents'] ?? 0, $business);
        } else {
            $lines[] = 'Cash & equivalents: ' . format_money($assetBlock['cash_and_equivalents'] ?? 0, $business);
        }

        $lines[] = '';
        $lines[] = 'Total assets: ' . format_money($assets, $business);
        $lines[] = 'Total liabilities: ' . format_money($liabilities, $business);
        $lines[] = 'Net position: ' . format_money($net, $business);

        return implode("\n", $lines);
    }

    public static function supplierBill(Business $business, SupplierCreditPurchase $purchase, float $balance): string
    {
        $vendor = optional($purchase->supplier)->name ?? 'Vendor';

        return implode("\n", array_filter([
            "Supplier bill — {$business->name}",
            "Vendor: {$vendor}",
            'Date: ' . $purchase->purchase_date->format('M j, Y'),
            $purchase->reference ? "Reference: {$purchase->reference}" : null,
            '',
            'Bill total: ' . format_money($purchase->total_amount, $business),
            'Paid: ' . format_money($purchase->amount_paid, $business),
            'Balance due: ' . format_money($balance, $business),
            'Status: ' . ucfirst($purchase->status),
        ]));
    }
}
