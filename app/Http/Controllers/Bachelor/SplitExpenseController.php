<?php

namespace App\Http\Controllers\Bachelor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bachelor\StoreSplitExpenseRequest;
use App\Models\SplitShare;
use App\Services\SplitExpenseService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SplitExpenseController extends Controller
{
    public function __construct(
        private readonly SplitExpenseService $service,
    ) {}

    /**
     * The Bachelor overview: this month's shared expenses, each member's share,
     * who paid, who still owes, and the settlement status.
     */
    public function index(Request $request): View
    {
        $family = $request->user()->currentFamily();

        $month = $request->filled('month')
            ? Carbon::parse($request->string('month').'-01')->startOfMonth()
            : now()->startOfMonth();

        $expenses = $family->splitExpenses()
            ->with(['payer', 'category', 'shares.member'])
            ->whereBetween('period', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->latest('period')->latest('id')
            ->get();

        $members = $family->users()->orderBy('name')->get();

        // Per-member settlement position for the month.
        $balances = $members->map(function ($member) use ($expenses) {
            $owes = 0.0;        // unsettled shares this member must pay back
            $owedToThem = 0.0;  // unsettled shares others owe this member (as payer)

            foreach ($expenses as $expense) {
                foreach ($expense->shares as $share) {
                    if ($share->settled_at !== null) {
                        continue;
                    }
                    if ($share->user_id === $member->id && $expense->paid_by !== $member->id) {
                        $owes += (float) $share->amount;
                    }
                    if ($expense->paid_by === $member->id && $share->user_id !== $member->id) {
                        $owedToThem += (float) $share->amount;
                    }
                }
            }

            return [
                'member' => $member,
                'owes' => $owes,
                'owedToThem' => $owedToThem,
                'net' => $owedToThem - $owes,
            ];
        });

        return view('bachelor.splits.index', [
            'family' => $family,
            'month' => $month,
            'expenses' => $expenses,
            'members' => $members,
            'total' => (float) $expenses->sum('total_amount'),
            'balances' => $balances,
        ]);
    }

    public function create(Request $request): View
    {
        $family = $request->user()->currentFamily();

        return view('bachelor.splits.create', [
            'members' => $family->users()->orderBy('name')->get(),
            'categories' => $family->categories()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreSplitExpenseRequest $request): RedirectResponse
    {
        $family = $request->user()->currentFamily();
        $payer = $family->users()->findOrFail($request->validated('paid_by'));

        $this->service->record($family, $payer, $request->user(), $request->validated());

        return redirect()->route('bachelor.splits.index')->with('status', 'split-recorded');
    }

    public function settle(Request $request, SplitShare $share): RedirectResponse
    {
        $this->authorizeShare($request, $share);
        $this->service->settle($share);

        return back()->with('status', 'share-settled');
    }

    public function unsettle(Request $request, SplitShare $share): RedirectResponse
    {
        $this->authorizeShare($request, $share);
        $this->service->unsettle($share);

        return back()->with('status', 'share-unsettled');
    }

    private function authorizeShare(Request $request, SplitShare $share): void
    {
        abort_unless($share->family_id === $request->user()->currentFamily()?->id, 403);
    }
}
