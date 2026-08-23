<?php

namespace App\Http\Controllers;

use App\Http\Requests\Recurring\RecurringIncomeRequest;
use App\Models\IncomeSource;
use App\Models\RecurringIncome;
use App\Models\User;
use App\Services\RecurringIncomeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class RecurringIncomeController extends Controller
{
    public function __construct(
        private readonly RecurringIncomeService $service,
    ) {}

    public function index(Request $request): View
    {
        $family = $request->user()->currentFamily();
        abort_if($family === null, 403);

        $month = now();
        $templates = $family->recurringIncomes()->with(['earner', 'source'])->latest()->get();

        // Which templates have already been posted this month, for the UI state.
        $postedRefs = $family->incomes()
            ->whereIn('reference', $templates->map->referenceFor($month))
            ->pluck('reference')
            ->flip();

        return view('recurring-incomes.index', [
            'templates' => $templates,
            'month' => $month,
            'postedRefs' => $postedRefs,
        ]);
    }

    public function create(Request $request): View
    {
        $family = $request->user()->currentFamily();
        abort_if($family === null, 403);

        return view('recurring-incomes.create', $this->formData($request));
    }

    public function store(RecurringIncomeRequest $request): RedirectResponse
    {
        $request->user()->currentFamily()->recurringIncomes()->create($request->validated());

        return redirect()->route('recurring-incomes.index')->with('status', 'recurring-income-created');
    }

    public function edit(Request $request, RecurringIncome $recurringIncome): View
    {
        $this->ensureOwned($request, $recurringIncome);

        return view('recurring-incomes.edit', [
            'template' => $recurringIncome,
            ...$this->formData($request),
        ]);
    }

    public function update(RecurringIncomeRequest $request, RecurringIncome $recurringIncome): RedirectResponse
    {
        $this->ensureOwned($request, $recurringIncome);

        $recurringIncome->update($request->validated());

        return redirect()->route('recurring-incomes.index')->with('status', 'recurring-income-updated');
    }

    public function destroy(Request $request, RecurringIncome $recurringIncome): RedirectResponse
    {
        $this->ensureOwned($request, $recurringIncome);

        $recurringIncome->delete();

        return redirect()->route('recurring-incomes.index')->with('status', 'recurring-income-deleted');
    }

    /**
     * The single-click action: create this month's income from the template.
     */
    public function post(Request $request, RecurringIncome $recurringIncome): RedirectResponse
    {
        $this->ensureOwned($request, $recurringIncome);

        $income = $this->service->postForMonth($recurringIncome, now());

        return redirect()->route('recurring-incomes.index')
            ->with('status', $income ? 'recurring-income-posted' : 'recurring-income-already-posted');
    }

    /**
     * @return array{members: Collection<int, User>, sources: Collection<int, IncomeSource>}
     */
    private function formData(Request $request): array
    {
        $family = $request->user()->currentFamily();

        return [
            'members' => $family->users()->orderBy('name')->get(),
            'sources' => $family->incomeSources()->orderBy('name')->get(),
        ];
    }

    private function ensureOwned(Request $request, RecurringIncome $recurringIncome): void
    {
        abort_unless($recurringIncome->family_id === $request->user()->currentFamily()?->id, 403);
    }
}
