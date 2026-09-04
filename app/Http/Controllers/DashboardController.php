<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard,
    ) {}

    public function __invoke(Request $request): View
    {
        $family = $request->user()->currentFamily();

        [$start, $end, $filters] = $this->resolveRange($request);

        $summary = $this->dashboard->summary($family, $start, $end);
        $byCategory = $this->dashboard->spendingByCategory($family, $start, $end);
        // The trend always shows the 6 months ending at the selected range.
        $trend = $this->dashboard->monthlyTrend($family, 6, $end);

        return view('dashboard', [
            'family' => $family,
            'summary' => $summary,
            'filters' => $filters,
            'rangeLabel' => $this->rangeLabel($filters['period'], $start, $end),
            // Chart datasets are handed to the view as arrays and JSON-encoded
            // into data-* attributes the amCharts bundle reads on page load.
            'categoryData' => $byCategory->values()->all(),
            'trendData' => $trend->values()->all(),
        ]);
    }

    /**
     * Resolve the request's period preset (or custom from/to) into a concrete
     * [start, end] range, plus the normalised filter state for the form.
     *
     * @return array{0: Carbon, 1: Carbon, 2: array{period: string, date_from: ?string, date_to: ?string}}
     */
    private function resolveRange(Request $request): array
    {
        $period = (string) $request->string('period');
        $period = in_array($period, ['today', 'week', 'month', 'year', 'custom'], true) ? $period : 'month';

        [$start, $end] = match ($period) {
            'today' => [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()],
            'week' => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()],
            'year' => [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()],
            'custom' => $this->customRange($request),
            default => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
        };

        return [$start, $end, [
            'period' => $period,
            'date_from' => $request->string('date_from')->toString() ?: null,
            'date_to' => $request->string('date_to')->toString() ?: null,
        ]];
    }

    /**
     * A custom from/to range. Missing bounds fall back to the current month, and
     * a reversed range is swapped so start is always before end.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function customRange(Request $request): array
    {
        $from = $request->date('date_from') ?? Carbon::now()->startOfMonth();
        $to = $request->date('date_to') ?? Carbon::now()->endOfMonth();

        if ($to->lessThan($from)) {
            [$from, $to] = [$to, $from];
        }

        return [$from->startOfDay(), $to->endOfDay()];
    }

    private function rangeLabel(string $period, Carbon $start, Carbon $end): string
    {
        return match ($period) {
            'today' => 'Today · '.$start->format('d M Y'),
            'week' => 'This week · '.$start->format('d M').' – '.$end->format('d M Y'),
            'year' => $start->format('Y'),
            'custom' => $start->format('d M Y').' – '.$end->format('d M Y'),
            default => $start->format('F Y'),
        };
    }
}
