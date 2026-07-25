<?php

namespace App\Http\Controllers;

use App\Services\MonthlyReportPdfService;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
        private readonly MonthlyReportPdfService $pdfService,
    ) {}

    /**
     * Stream the monthly financial report for the current family as a PDF.
     */
    public function monthlyPdf(Request $request): Response
    {
        $family = $request->user()->currentFamily();

        abort_if($family === null, 403);

        $month = $this->resolveMonth($request->query('month'));

        $report = $this->reportService->monthly($family, $month);
        $pdf = $this->pdfService->render($family, $report);

        $filename = 'monthly-report-'.$month->format('Y-m').'.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    /**
     * Parse the ?month=YYYY-MM query param, falling back to the current month
     * when it is missing or malformed (never trust query input for date math).
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
}
