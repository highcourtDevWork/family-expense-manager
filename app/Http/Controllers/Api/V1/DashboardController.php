<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $family = $request->user()->currentFamily();
        $summary = $this->dashboard->summary($family);

        return response()->json([
            'data' => [
                'month' => $summary['month']->format('Y-m'),
                'income' => (float) $summary['income'],
                'expense' => (float) $summary['expense'],
                'balance' => (float) $summary['balance'],
                'loan_balance' => (float) $summary['loanBalance'],
                'net_debt' => (float) $summary['debtOwed'],
                'savings' => (float) $summary['savings'],
            ],
        ]);
    }
}
