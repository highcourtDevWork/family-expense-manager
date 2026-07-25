<?php

namespace App\Services;

use App\Models\Family;
use Illuminate\Support\Facades\View;
use TCPDF;

class MonthlyReportPdfService
{
    /**
     * Render the monthly report array into a PDF and return the raw bytes.
     *
     * The layout lives in a Blade view (resources/views/reports/pdf/monthly)
     * which is rendered to HTML and handed to TCPDF's HTML writer, so the PDF
     * design stays in a template rather than in PHP string concatenation.
     *
     * @param  array<string, mixed>  $report
     */
    public function render(Family $family, array $report): string
    {
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');

        $pdf->SetCreator('Family Expense Manager');
        $pdf->SetAuthor($family->name);
        $pdf->SetTitle('Monthly Report — '.$report['month']->format('F Y'));
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();

        $html = View::make('reports.pdf.monthly', [
            'family' => $family,
            'report' => $report,
        ])->render();

        $pdf->writeHTML($html, true, false, true, false, '');

        // 'S' returns the document as a string instead of streaming it, so the
        // controller stays in charge of the HTTP response/headers.
        return $pdf->Output('report.pdf', 'S');
    }
}
