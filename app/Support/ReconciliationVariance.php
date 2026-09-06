<?php

namespace App\Support;

class ReconciliationVariance
{
    public static function label(float $missingMoney): string
    {
        if ($missingMoney > 0) {
            return 'Missing money';
        }

        if ($missingMoney < 0) {
            return 'Extra money';
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
            return 'End-of-day reconciliation submitted. Extra money: ' . format_money(abs($missingMoney), $business);
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
            return '• Extra money: ' . format_money(abs($missingMoney), $business);
        }

        return '• Drawer balanced';
    }
}
