<?php

namespace App\Http\Controllers;

use App\Enums\LoanType;
use App\Http\Requests\Loan\StoreLoanRequest;
use App\Http\Requests\Loan\UpdateLoanRequest;
use App\Models\Loan;
use App\Services\LoanService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function __construct(
        private readonly LoanService $loanService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Loan::class);

        $family = $request->user()->currentFamily();

        return view('loans.index', [
            'loans' => $family->loans()->with('user')->latest()->paginate(15),
            'totalBalance' => (string) $family->loans()->sum('remaining_amount'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Loan::class);

        return view('loans.create', [
            'loanTypes' => LoanType::options(),
        ]);
    }

    public function store(StoreLoanRequest $request): RedirectResponse
    {
        $loan = $this->loanService->create(
            $request->user()->currentFamily(),
            $request->user(),
            $request->validated(),
        );

        return redirect()->route('loans.show', $loan)
            ->with('status', 'loan-created');
    }

    public function show(Loan $loan): View
    {
        $this->authorize('view', $loan);

        return view('loans.show', [
            'loan' => $loan->load('user'),
            'payments' => $loan->payments()->latest('payment_date')->latest('id')->get(),
        ]);
    }

    public function edit(Loan $loan): View
    {
        $this->authorize('update', $loan);

        return view('loans.edit', [
            'loan' => $loan,
            'loanTypes' => LoanType::options(),
        ]);
    }

    public function update(UpdateLoanRequest $request, Loan $loan): RedirectResponse
    {
        $this->loanService->update($loan, $request->validated());

        return redirect()->route('loans.show', $loan)
            ->with('status', 'loan-updated');
    }

    public function destroy(Loan $loan): RedirectResponse
    {
        $this->authorize('delete', $loan);

        $this->loanService->delete($loan);

        return redirect()->route('loans.index')
            ->with('status', 'loan-deleted');
    }
}
