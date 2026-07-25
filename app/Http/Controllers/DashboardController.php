<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard,
    ) {}

    public function __invoke(Request $request): View
    {
        $family = $request->user()->currentFamily();

        $summary = $this->dashboard->summary($family);
        $byCategory = $this->dashboard->spendingByCategory($family);
        $trend = $this->dashboard->monthlyTrend($family);

        return view('dashboard', [
            'family' => $family,
            'summary' => $summary,
            // Chart datasets are handed to the view as arrays and JSON-encoded
            // into data-* attributes the amCharts bundle reads on page load.
            'categoryData' => $byCategory->values()->all(),
            'trendData' => $trend->values()->all(),
        ]);
    }
}
