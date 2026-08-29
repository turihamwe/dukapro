<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportPeriodResolver
{
    public static function resolve(string $period, Request $request): array
    {
        switch ($period) {
            case 'yesterday':
                return [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay(), 'Yesterday'];

            case 'weekly':
                return [Carbon::today()->subDays(6)->startOfDay(), Carbon::today()->endOfDay(), 'Last 7 Days'];

            case 'monthly':
                return [Carbon::today()->subDays(29)->startOfDay(), Carbon::today()->endOfDay(), 'Last 30 Days'];

            case 'yearly':
                return [Carbon::today()->startOfYear()->startOfDay(), Carbon::today()->endOfDay(), 'This Year'];

            case 'custom':
                $from = Carbon::parse($request->input('from', Carbon::today()->toDateString()));
                $to = Carbon::parse($request->input('to', Carbon::today()->toDateString()));
                if ($from->gt($to)) {
                    [$from, $to] = [$to, $from];
                }

                return [$from->startOfDay(), $to->endOfDay(), $from->format('M j') . ' – ' . $to->format('M j, Y')];

            default:
                return [Carbon::today()->startOfDay(), Carbon::today()->endOfDay(), 'Today'];
        }
    }

    public static function periods(): array
    {
        return [
            'daily' => 'Today',
            'yesterday' => 'Yesterday',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'yearly' => 'Yearly',
        ];
    }
}
