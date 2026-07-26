<?php

namespace App\Http\Controllers;

use App\Models\Family;
use App\Services\MonthlyReportPdfService;
use App\Services\ReportService;
use App\Support\CsvExporter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
        private readonly MonthlyReportPdfService $pdfService,
    ) {}

    /**
     * Report hub — links to the daily, monthly, and yearly reports.
     */
    public function index(): View
    {
        return view('reports.index', [
            'today' => Carbon::today(),
            'thisMonth' => Carbon::now(),
            'thisYear' => (int) Carbon::now()->format('Y'),
        ]);
    }

    public function daily(Request $request): View
    {
        $family = $this->family($request);
        $date = $this->resolveDate($request->query('date'));

        return view('reports.daily', [
            'report' => $this->reportService->daily($family, $date),
        ]);
    }

    public function monthly(Request $request): View
    {
        $family = $this->family($request);
        $month = $this->resolveMonth($request->query('month'));

        return view('reports.monthly', [
            'report' => $this->reportService->monthly($family, $month),
        ]);
    }

    public function yearly(Request $request): View
    {
        $family = $this->family($request);
        $year = $this->resolveYear($request->query('year'));

        return view('reports.yearly', [
            'report' => $this->reportService->yearly($family, $year),
        ]);
    }

    /**
     * Stream the monthly financial report for the current family as a PDF.
     */
    public function monthlyPdf(Request $request): Response
    {
        $family = $this->family($request);
        $month = $this->resolveMonth($request->query('month'));

        $report = $this->reportService->monthly($family, $month);
        $pdf = $this->pdfService->render($family, $report);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="monthly-report-'.$month->format('Y-m').'.pdf"',
        ]);
    }

    /**
     * Download the monthly report as a CSV (opens in Excel).
     */
    public function monthlyCsv(Request $request): StreamedResponse
    {
        $family = $this->family($request);
        $month = $this->resolveMonth($request->query('month'));
        $report = $this->reportService->monthly($family, $month);

        $rows = [
            ['Total Income', $report['income']],
            ['Total Expense', $report['expense']],
            ['Balance', $report['balance']],
            ['Savings (this month)', $report['savings']],
            ['Loan Balance (outstanding)', $report['loanBalance']],
            ['Net Debt (owed)', $report['debtOwed']],
            [],
            ['Expenses by Category', ''],
        ];

        foreach ($report['expensesByCategory'] as $row) {
            $rows[] = [$row['name'], number_format($row['total'], 2, '.', '')];
        }

        $rows[] = [];
        $rows[] = ['Income by Source', ''];
        foreach ($report['incomeBySource'] as $row) {
            $rows[] = [$row['name'], number_format($row['total'], 2, '.', '')];
        }

        return CsvExporter::download(
            'monthly-report-'.$month->format('Y-m').'.csv',
            ['Item', 'Amount (INR)'],
            $rows,
        );
    }

    /**
     * Download the yearly report (month-by-month) as a CSV.
     */
    public function yearlyCsv(Request $request): StreamedResponse
    {
        $family = $this->family($request);
        $year = $this->resolveYear($request->query('year'));
        $report = $this->reportService->yearly($family, $year);

        $rows = $report['months']->map(fn (array $m): array => [
            $m['label'],
            number_format($m['income'], 2, '.', ''),
            number_format($m['expense'], 2, '.', ''),
            number_format($m['balance'], 2, '.', ''),
        ])->all();

        $rows[] = [
            'TOTAL',
            number_format((float) $report['totalIncome'], 2, '.', ''),
            number_format((float) $report['totalExpense'], 2, '.', ''),
            number_format((float) $report['totalBalance'], 2, '.', ''),
        ];

        return CsvExporter::download(
            'yearly-report-'.$year.'.csv',
            ['Month', 'Income (INR)', 'Expense (INR)', 'Balance (INR)'],
            $rows,
        );
    }

    private function family(Request $request): Family
    {
        $family = $request->user()->currentFamily();

        abort_if($family === null, 403);

        return $family;
    }

    /**
     * Parse ?date=YYYY-MM-DD, falling back to today when missing/malformed.
     */
    private function resolveDate(?string $date): Carbon
    {
        if ($date === null) {
            return Carbon::today();
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
        } catch (\Throwable) {
            return Carbon::today();
        }
    }

    /**
     * Parse ?month=YYYY-MM, falling back to the current month.
     */
    private function resolveMonth(?string $month): Carbon
    {
        if ($month === null) {
            return Carbon::now()->startOfMonth();
        }

        try {
            return Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (\Throwable) {
            return Carbon::now()->startOfMonth();
        }
    }

    /**
     * Parse ?year=YYYY, clamped to a sane range, defaulting to the current year.
     */
    private function resolveYear(?string $year): int
    {
        $current = (int) Carbon::now()->format('Y');

        if ($year === null || ! ctype_digit($year)) {
            return $current;
        }

        return max(2000, min((int) $year, $current + 1));
    }
}
