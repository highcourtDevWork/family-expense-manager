<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\LoanEmi;
use App\Services\EmiService;
use Illuminate\Http\RedirectResponse;

class LoanEmiController extends Controller
{
    public function __construct(
        private readonly EmiService $emiService,
    ) {}

    /**
     * Manually mark an installment as paid.
     */
    public function pay(Loan $loan, LoanEmi $emi): RedirectResponse
    {
        $this->authorizeInstallment($loan, $emi);

        $this->emiService->markPaid($emi);

        return back()->with('status', 'emi-paid');
    }

    /**
     * Revert an installment back to pending.
     */
    public function unpay(Loan $loan, LoanEmi $emi): RedirectResponse
    {
        $this->authorizeInstallment($loan, $emi);

        $this->emiService->markPending($emi);

        return back()->with('status', 'emi-unpaid');
    }

    /**
     * Only someone who can update the loan may change its installments, and the
     * installment must actually belong to this loan.
     */
    private function authorizeInstallment(Loan $loan, LoanEmi $emi): void
    {
        $this->authorize('update', $loan);

        abort_unless($emi->loan_id === $loan->id, 404);
    }
}
