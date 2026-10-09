<?php

namespace App\Http\Controllers;

use App\Services\IncomeAllocationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SavingsRateController extends Controller
{
    public function __construct(
        private readonly IncomeAllocationService $allocation,
    ) {}

    public function __invoke(Request $request): View
    {
        $family = $request->user()->currentFamily();
        abort_if($family === null, 403);

        $period = (string) $request->string('period');
        $period = in_array($period, ['month', 'year', 'all'], true) ? $period : 'month';

        [$start, $end, $rangeLabel] = match ($period) {
            'year' => [Carbon::now()->startOfYear(), Carbon::now()->endOfYear(), Carbon::now()->format('Y')],
            'all' => [Carbon::create(2000, 1, 1)->startOfDay(), Carbon::now()->endOfDay(), 'All time'],
            default => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth(), Carbon::now()->format('F Y')],
        };

        return view('savings-rate.index', [
            'family' => $family,
            'period' => $period,
            'rangeLabel' => $rangeLabel,
            'data' => $this->allocation->forPeriod($family, $start, $end),
        ]);
    }
}
