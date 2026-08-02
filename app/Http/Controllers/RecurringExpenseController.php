<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Http\Requests\Recurring\RecurringExpenseRequest;
use App\Models\RecurringExpense;
use App\Services\RecurringExpenseService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RecurringExpenseController extends Controller
{
    public function __construct(
        private readonly RecurringExpenseService $service,
    ) {}

    public function index(Request $request): View
    {
        $family = $request->user()->currentFamily();
        abort_if($family === null, 403);

        $month = now();

        $templates = $family->recurringExpenses()->with('category')->latest()->get();

        // Which templates have already been posted this month, for the UI state.
        $postedRefs = $family->expenses()
            ->whereIn('reference', $templates->map->referenceFor($month))
            ->pluck('reference')
            ->flip();

        return view('recurring.index', [
            'templates' => $templates,
            'month' => $month,
            'postedRefs' => $postedRefs,
        ]);
    }

    public function create(Request $request): View
    {
        $family = $request->user()->currentFamily();
        abort_if($family === null, 403);

        return view('recurring.create', [
            'categories' => $family->categories()->orderBy('name')->get(),
            'paymentMethods' => PaymentMethod::options(),
        ]);
    }

    public function store(RecurringExpenseRequest $request): RedirectResponse
    {
        $request->user()->currentFamily()->recurringExpenses()->create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('recurring.index')->with('status', 'recurring-created');
    }

    public function edit(Request $request, RecurringExpense $recurring): View
    {
        $this->ensureOwned($request, $recurring);

        $family = $request->user()->currentFamily();

        return view('recurring.edit', [
            'template' => $recurring,
            'categories' => $family->categories()->orderBy('name')->get(),
            'paymentMethods' => PaymentMethod::options(),
        ]);
    }

    public function update(RecurringExpenseRequest $request, RecurringExpense $recurring): RedirectResponse
    {
        $this->ensureOwned($request, $recurring);

        $recurring->update($request->validated());

        return redirect()->route('recurring.index')->with('status', 'recurring-updated');
    }

    public function destroy(Request $request, RecurringExpense $recurring): RedirectResponse
    {
        $this->ensureOwned($request, $recurring);

        $recurring->delete();

        return redirect()->route('recurring.index')->with('status', 'recurring-deleted');
    }

    /**
     * The single-click action: create this month's expense from the template.
     */
    public function post(Request $request, RecurringExpense $recurring): RedirectResponse
    {
        $this->ensureOwned($request, $recurring);

        $expense = $this->service->postForMonth($recurring, now());

        return redirect()->route('recurring.index')
            ->with('status', $expense ? 'recurring-posted' : 'recurring-already-posted');
    }

    private function ensureOwned(Request $request, RecurringExpense $recurring): void
    {
        abort_unless($recurring->family_id === $request->user()->currentFamily()?->id, 403);
    }
}
