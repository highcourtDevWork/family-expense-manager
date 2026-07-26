<?php

namespace App\Http\Controllers;

use App\Http\Requests\Settings\IncomeSourceRequest;
use App\Models\IncomeSource;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class IncomeSourceController extends Controller
{
    public function index(Request $request): View
    {
        $family = $request->user()->currentFamily();
        abort_if($family === null, 403);

        return view('settings.sources', [
            'sources' => $family->incomeSources()->withCount('incomes')->orderBy('name')->get(),
        ]);
    }

    public function store(IncomeSourceRequest $request): RedirectResponse
    {
        $request->user()->currentFamily()->incomeSources()->create($request->validated());

        return redirect()->route('sources.index')->with('status', 'source-created');
    }

    public function update(IncomeSourceRequest $request, IncomeSource $source): RedirectResponse
    {
        $this->ensureOwned($request, $source);

        $source->update($request->validated());

        return redirect()->route('sources.index')->with('status', 'source-updated');
    }

    public function destroy(Request $request, IncomeSource $source): RedirectResponse
    {
        $this->ensureOwned($request, $source);

        if ($source->incomes()->exists()) {
            return redirect()->route('sources.index')
                ->withErrors(['source' => 'This source is used by existing income entries and cannot be deleted.']);
        }

        $source->delete();

        return redirect()->route('sources.index')->with('status', 'source-deleted');
    }

    private function ensureOwned(Request $request, IncomeSource $source): void
    {
        abort_unless($source->family_id === $request->user()->currentFamily()?->id, 403);
    }
}
