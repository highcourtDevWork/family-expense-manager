<?php

namespace App\Http\Controllers;

use App\Http\Requests\Loan\StoreLoanPaymentRequest;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Services\LoanService;
use Illuminate\Http\RedirectResponse;

class LoanPaymentController extends Controller
{
    public function __construct(
        private readonly LoanService $loanService,
    ) {}

    public function store(StoreLoanPaymentRequest $request, Loan $loan): RedirectResponse
    {
        $this->loanService->addPayment($loan, $request->validated());

        return redirect()->route('loans.show', $loan)
            ->with('status', 'payment-added');
    }

    public function destroy(Loan $loan, LoanPayment $payment): RedirectResponse
    {
        $this->authorize('update', $loan);

        // Guard against tampering: the payment must belong to this loan.
        abort_if($payment->loan_id !== $loan->id, 404);

        $this->loanService->deletePayment($payment);

        return redirect()->route('loans.show', $loan)
            ->with('status', 'payment-deleted');
    }
}
