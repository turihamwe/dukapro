<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;

class AnalyticsDateRange
{
    public const LOW_STOCK_THRESHOLD = 5;

    public Carbon $start;

    public Carbon $end;

    public string $key;

    public string $label;

    public function __construct(string $key, string $label, Carbon $start, Carbon $end)
    {
        $this->key = $key;
        $this->label = $label;
        $this->start = $start->copy()->startOfDay();
        $this->end = $end->copy()->endOfDay();
    }

    public static function fromRequest(Request $request): self
    {
        $key = $request->input('range', 'today');

        switch ($key) {
            case 'today':
                return new self('today', 'Today', Carbon::today(), Carbon::today());

            case 'yesterday':
                return new self('yesterday', 'Yesterday', Carbon::yesterday(), Carbon::yesterday());

            case 'last_week':
                return new self('last_week', 'Last Week', Carbon::today()->subDays(6), Carbon::today());

            case 'last_month':
                return new self('last_month', 'Last Month', Carbon::today()->subDays(29), Carbon::today());

            case 'last_6_months':
                return new self('last_6_months', 'Last 6 Months', Carbon::today()->subMonths(6)->addDay(), Carbon::today());

            case 'last_year':
                return new self('last_year', 'Last Year', Carbon::today()->subYear()->addDay(), Carbon::today());

            case 'custom':
                $from = $request->input('from');
                $to = $request->input('to');

                if (! $from || ! $to) {
                    return new self('today', 'Today', Carbon::today(), Carbon::today());
                }

                $start = Carbon::parse($from);
                $end = Carbon::parse($to);

                if ($start->gt($end)) {
                    [$start, $end] = [$end, $start];
                }

                $label = $start->format('M j, Y') . ' – ' . $end->format('M j, Y');

                return new self('custom', $label, $start, $end);

            default:
                return new self('today', 'Today', Carbon::today(), Carbon::today());
        }
    }

    public static function presets(): array
    {
        return [
            ['key' => 'today', 'label' => 'Today'],
            ['key' => 'yesterday', 'label' => 'Yesterday'],
            ['key' => 'last_week', 'label' => 'Last Week'],
            ['key' => 'last_month', 'label' => 'Last Month'],
            ['key' => 'last_6_months', 'label' => 'Last 6 Months'],
            ['key' => 'last_year', 'label' => 'Last Year'],
            ['key' => 'custom', 'label' => 'Custom'],
        ];
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'start' => $this->start->toDateString(),
            'end' => $this->end->toDateString(),
        ];
    }
}
