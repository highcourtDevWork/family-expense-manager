<?php

namespace App\Http\Controllers;

use App\Http\Requests\Income\StoreIncomeRequest;
use App\Http\Requests\Income\UpdateIncomeRequest;
use App\Models\Income;
use App\Services\IncomeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class IncomeController extends Controller
{
    public function __construct(
        private readonly IncomeService $incomeService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Income::class);

        $family = $request->user()->currentFamily();

        $filters = $request->only([
            'period', 'date_from', 'date_to', 'income_source_id', 'user_id', 'search',
        ]);

        return view('incomes.index', [
            'incomes' => $this->incomeService->paginateForFamily($family, $filters),
            'total' => $this->incomeService->totalForFamily($family, $filters),
            'sources' => $family->incomeSources()->orderBy('name')->get(),
            'members' => $family->users()->orderBy('name')->get(),
            'filters' => $filters,
        ]);
    }

    public function report(Request $request): View
    {
        $this->authorize('viewAny', Income::class);

        $family = $request->user()->currentFamily();

        return view('incomes.report', [
            'report' => $this->incomeService->monthlyReport($family, $request->query('month')),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Income::class);

        $family = $request->user()->currentFamily();

        return view('incomes.create', [
            'sources' => $family->incomeSources()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreIncomeRequest $request): RedirectResponse
    {
        $family = $request->user()->currentFamily();

        $this->incomeService->create($family, $request->user(), $request->validated());

        return redirect()->route('incomes.index')
            ->with('status', 'income-created');
    }

    public function edit(Income $income): View
    {
        $this->authorize('update', $income);

        return view('incomes.edit', [
            'income' => $income,
            'sources' => $income->family->incomeSources()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateIncomeRequest $request, Income $income): RedirectResponse
    {
        $this->incomeService->update($income, $request->validated());

        return redirect()->route('incomes.index')
            ->with('status', 'income-updated');
    }

    public function destroy(Income $income): RedirectResponse
    {
        $this->authorize('delete', $income);

        $this->incomeService->delete($income);

        return redirect()->route('incomes.index')
            ->with('status', 'income-deleted');
    }
}
