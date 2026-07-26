<?php

namespace App\Http\Controllers;

use App\Enums\DebtType;
use App\Http\Requests\Debt\StoreDebtRequest;
use App\Http\Requests\Debt\UpdateDebtRequest;
use App\Models\Debt;
use App\Services\DebtService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DebtController extends Controller
{
    public function __construct(
        private readonly DebtService $debtService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Debt::class);

        $family = $request->user()->currentFamily();

        return view('debts.index', [
            'debts' => $family->debts()->with('user')->latest()->paginate(15),
            // Split so the two directions are shown separately (we owe / owed to us).
            'borrowedBalance' => (string) $family->debts()->where('debt_type', DebtType::Borrowed)->sum('balance_amount'),
            'lentBalance' => (string) $family->debts()->where('debt_type', DebtType::Lent)->sum('balance_amount'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Debt::class);

        return view('debts.create', [
            'debtTypes' => DebtType::options(),
        ]);
    }

    public function store(StoreDebtRequest $request): RedirectResponse
    {
        $debt = $this->debtService->create(
            $request->user()->currentFamily(),
            $request->user(),
            $request->validated(),
        );

        return redirect()->route('debts.show', $debt)
            ->with('status', 'debt-created');
    }

    public function show(Debt $debt): View
    {
        $this->authorize('view', $debt);

        return view('debts.show', [
            'debt' => $debt->load('user'),
            'payments' => $debt->payments()->latest('payment_date')->latest('id')->get(),
        ]);
    }

    public function edit(Debt $debt): View
    {
        $this->authorize('update', $debt);

        return view('debts.edit', [
            'debt' => $debt,
            'debtTypes' => DebtType::options(),
        ]);
    }

    public function update(UpdateDebtRequest $request, Debt $debt): RedirectResponse
    {
        $this->debtService->update($debt, $request->validated());

        return redirect()->route('debts.show', $debt)
            ->with('status', 'debt-updated');
    }

    public function destroy(Debt $debt): RedirectResponse
    {
        $this->authorize('delete', $debt);

        $this->debtService->delete($debt);

        return redirect()->route('debts.index')
            ->with('status', 'debt-deleted');
    }
}
