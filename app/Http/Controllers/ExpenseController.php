<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Http\Requests\Expense\StoreExpenseRequest;
use App\Http\Requests\Expense\UpdateExpenseRequest;
use App\Models\Expense;
use App\Services\ExpenseService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function __construct(
        private readonly ExpenseService $expenseService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Expense::class);

        $family = $request->user()->currentFamily();

        $filters = $request->only([
            'period', 'date_from', 'date_to', 'category_id', 'user_id', 'search',
        ]);

        return view('expenses.index', [
            'expenses' => $this->expenseService->paginateForFamily($family, $filters),
            'total' => $this->expenseService->totalForFamily($family, $filters),
            'categories' => $family->categories()->orderBy('name')->get(),
            'members' => $family->users()->orderBy('name')->get(),
            'filters' => $filters,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Expense::class);

        $family = $request->user()->currentFamily();

        return view('expenses.create', [
            'categories' => $family->categories()->orderBy('name')->get(),
            'paymentMethods' => PaymentMethod::options(),
        ]);
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $family = $request->user()->currentFamily();

        $this->expenseService->create(
            $family,
            $request->user(),
            $request->safe()->except('attachment'),
            $request->file('attachment'),
        );

        return redirect()->route('expenses.index')
            ->with('status', 'expense-created');
    }

    public function edit(Expense $expense): View
    {
        $this->authorize('update', $expense);

        return view('expenses.edit', [
            'expense' => $expense,
            'categories' => $expense->family->categories()->orderBy('name')->get(),
            'paymentMethods' => PaymentMethod::options(),
        ]);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $this->expenseService->update(
            $expense,
            $request->safe()->except('attachment'),
            $request->file('attachment'),
        );

        return redirect()->route('expenses.index')
            ->with('status', 'expense-updated');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $this->authorize('delete', $expense);

        $this->expenseService->delete($expense);

        return redirect()->route('expenses.index')
            ->with('status', 'expense-deleted');
    }

    /**
     * Delete several selected expenses at once. Every id is scoped to the
     * current family and then filtered through the delete policy, so a user can
     * only ever remove rows they are actually allowed to.
     */
    public function bulkDestroy(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', Expense::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $family = $request->user()->currentFamily();

        $deletable = Expense::query()
            ->forFamily($family->id)
            ->whereIn('id', $validated['ids'])
            ->get()
            ->filter(fn (Expense $expense) => $request->user()->can('delete', $expense));

        $count = $this->expenseService->deleteMany($deletable->values());

        return redirect()->route('expenses.index')
            ->with('status', $count === 1 ? '1 expense deleted.' : "{$count} expenses deleted.");
    }
}
