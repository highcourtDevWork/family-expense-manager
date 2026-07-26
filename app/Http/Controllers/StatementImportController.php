<?php

namespace App\Http\Controllers;

use App\Http\Requests\Import\ImportStatementRequest;
use App\Models\Expense;
use App\Services\GpayStatementParser;
use App\Services\StatementImportService;
use App\Support\ParsedTransaction;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StatementImportController extends Controller
{
    /**
     * Session key holding the parsed batch between preview and confirm.
     */
    private const SESSION_KEY = 'gpay_import';

    public function __construct(
        private readonly GpayStatementParser $parser,
        private readonly StatementImportService $importer,
    ) {}

    public function create(): View
    {
        $this->authorize('create', Expense::class);

        return view('imports.create');
    }

    /**
     * Parse the uploaded statement and stash the result for the preview step —
     * nothing is written to the ledger yet.
     */
    public function preview(ImportStatementRequest $request): RedirectResponse
    {
        $transactions = $this->parser->parseFile($request->file('statement')->getRealPath());

        if ($transactions === []) {
            return back()->withErrors([
                'statement' => 'No transactions could be read from this PDF. Is it a Google Pay statement?',
            ]);
        }

        $request->session()->put(
            self::SESSION_KEY,
            array_map(fn (ParsedTransaction $t) => $t->toArray(), $transactions),
        );

        return redirect()->route('imports.preview');
    }

    public function showPreview(Request $request): View|RedirectResponse
    {
        $this->authorize('create', Expense::class);

        $transactions = $this->fromSession($request);

        if ($transactions === []) {
            return redirect()->route('imports.create');
        }

        $family = $request->user()->currentFamily();
        $partitioned = $this->importer->partition($family, $transactions);

        return view('imports.preview', [
            'new' => $partitioned['new'],
            'duplicates' => $partitioned['duplicates'],
            'expenseTotal' => $this->sumByType($partitioned['new'], 'expense'),
            'incomeTotal' => $this->sumByType($partitioned['new'], 'income'),
        ]);
    }

    public function confirm(Request $request): RedirectResponse
    {
        $this->authorize('create', Expense::class);

        $transactions = $this->fromSession($request);

        if ($transactions === []) {
            return redirect()->route('imports.create');
        }

        $summary = $this->importer->import($request->user()->currentFamily(), $request->user(), $transactions);

        $request->session()->forget(self::SESSION_KEY);

        return redirect()->route('expenses.index')->with('status', sprintf(
            'Imported %d expense(s) and %d income entr(ies). %d duplicate(s) skipped.',
            $summary['expenses'],
            $summary['incomes'],
            $summary['skipped'],
        ));
    }

    /**
     * Rehydrate the parsed batch from the session.
     *
     * @return list<ParsedTransaction>
     */
    private function fromSession(Request $request): array
    {
        return array_map(
            fn (array $row) => ParsedTransaction::fromArray($row),
            $request->session()->get(self::SESSION_KEY, []),
        );
    }

    /**
     * @param  list<ParsedTransaction>  $transactions
     */
    private function sumByType(array $transactions, string $type): float
    {
        return array_sum(array_map(
            fn (ParsedTransaction $t) => $t->type === $type ? $t->amount : 0,
            $transactions,
        ));
    }
}
