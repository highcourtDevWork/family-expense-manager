<?php

namespace App\Http\Controllers;

use App\Http\Requests\Debt\StoreDebtPaymentRequest;
use App\Models\Debt;
use App\Models\DebtPayment;
use App\Services\DebtService;
use Illuminate\Http\RedirectResponse;

class DebtPaymentController extends Controller
{
    public function __construct(
        private readonly DebtService $debtService,
    ) {}

    public function store(StoreDebtPaymentRequest $request, Debt $debt): RedirectResponse
    {
        $this->debtService->addPayment($debt, $request->validated());

        return redirect()->route('debts.show', $debt)
            ->with('status', 'payment-added');
    }

    public function destroy(Debt $debt, DebtPayment $payment): RedirectResponse
    {
        $this->authorize('update', $debt);

        // Guard against tampering: the payment must belong to this debt.
        abort_if($payment->debt_id !== $debt->id, 404);

        $this->debtService->deletePayment($payment);

        return redirect()->route('debts.show', $debt)
            ->with('status', 'payment-deleted');
    }
}
