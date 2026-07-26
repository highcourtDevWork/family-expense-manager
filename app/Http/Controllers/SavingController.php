<?php

namespace App\Http\Controllers;

use App\Enums\SavingType;
use App\Http\Requests\Saving\StoreSavingRequest;
use App\Http\Requests\Saving\UpdateSavingRequest;
use App\Models\Saving;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SavingController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Saving::class);

        $family = $request->user()->currentFamily();

        return view('savings.index', [
            'savings' => $family->savings()->with('user')->latest('date')->latest('id')->paginate(15),
            'total' => (string) $family->savings()->sum('amount'),
            // Per-type totals for the breakdown strip.
            'byType' => $family->savings()
                ->selectRaw('saving_type, SUM(amount) as total')
                ->groupBy('saving_type')
                ->pluck('total', 'saving_type'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Saving::class);

        return view('savings.create', [
            'savingTypes' => SavingType::options(),
        ]);
    }

    public function store(StoreSavingRequest $request): RedirectResponse
    {
        $request->user()->currentFamily()->savings()->create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('savings.index')->with('status', 'saving-created');
    }

    public function edit(Saving $saving): View
    {
        $this->authorize('update', $saving);

        return view('savings.edit', [
            'saving' => $saving,
            'savingTypes' => SavingType::options(),
        ]);
    }

    public function update(UpdateSavingRequest $request, Saving $saving): RedirectResponse
    {
        $saving->update($request->validated());

        return redirect()->route('savings.index')->with('status', 'saving-updated');
    }

    public function destroy(Saving $saving): RedirectResponse
    {
        $this->authorize('delete', $saving);

        $saving->delete();

        return redirect()->route('savings.index')->with('status', 'saving-deleted');
    }
}
