<?php

namespace App\Support;

class ReconciliationVariance
{
    public static function extraCashLabel(): string
    {
        return 'Unexplained cash found';
    }

    public static function extraCashHint(): string
    {
        return 'Optional — cash in the drawer that did not come from today\'s sales. Not the same as your net drawer surplus.';
    }

    public static function netBalanceHint(): string
    {
        return 'Includes expenses and damages paid from cash — not just sales vs. actual cash counted.';
    }

    public static function label(float $missingMoney): string
    {
        if ($missingMoney > 0) {
            return 'Missing money';
        }

        if ($missingMoney < 0) {
            return 'Net drawer surplus';
        }

        return 'Balanced';
    }

    public static function shortLabel(float $missingMoney): string
    {
        if ($missingMoney > 0) {
            return 'Missing';
        }

        if ($missingMoney < 0) {
            return 'Surplus';
        }

        return 'Balanced';
    }

    public static function displayAmount(float $missingMoney): float
    {
        return $missingMoney < 0 ? abs($missingMoney) : $missingMoney;
    }

    public static function successMessage(float $missingMoney, $business = null): string
    {
        if ($missingMoney > 0) {
            return 'End-of-day reconciliation submitted. Missing money: ' . format_money($missingMoney, $business);
        }

        if ($missingMoney < 0) {
            return 'End-of-day reconciliation submitted. Net drawer surplus: ' . format_money(abs($missingMoney), $business);
        }

        return 'End-of-day reconciliation submitted. Drawer balanced.';
    }

    public static function tone(float $missingMoney): string
    {
        if ($missingMoney > 0) {
            return 'danger';
        }

        if ($missingMoney < 0) {
            return 'success';
        }

        return 'neutral';
    }

    public static function whatsAppVarianceLine(float $missingMoney, $business = null): string
    {
        if ($missingMoney > 0) {
            return '• Missing money: ' . format_money($missingMoney, $business);
        }

        if ($missingMoney < 0) {
            return '• Net drawer surplus: ' . format_money(abs($missingMoney), $business);
        }

        return '• Drawer balanced';
    }
}
