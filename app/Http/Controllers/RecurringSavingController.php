<?php

namespace App\Http\Controllers;

use App\Enums\SavingType;
use App\Http\Requests\Recurring\RecurringSavingRequest;
use App\Models\RecurringSaving;
use App\Models\User;
use App\Services\RecurringSavingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class RecurringSavingController extends Controller
{
    public function __construct(
        private readonly RecurringSavingService $service,
    ) {}

    public function index(Request $request): View
    {
        $family = $request->user()->currentFamily();
        abort_if($family === null, 403);

        $month = now();
        $templates = $family->recurringSavings()->with('saver')->latest()->get();

        // Which templates have already been posted this month, for the UI state.
        $postedRefs = $family->savings()
            ->whereIn('reference', $templates->map->referenceFor($month))
            ->pluck('reference')
            ->flip();

        return view('recurring-savings.index', [
            'templates' => $templates,
            'month' => $month,
            'postedRefs' => $postedRefs,
        ]);
    }

    public function create(Request $request): View
    {
        $family = $request->user()->currentFamily();
        abort_if($family === null, 403);

        return view('recurring-savings.create', $this->formData($request));
    }

    public function store(RecurringSavingRequest $request): RedirectResponse
    {
        $request->user()->currentFamily()->recurringSavings()->create($request->validated());

        return redirect()->route('recurring-savings.index')->with('status', 'recurring-saving-created');
    }

    public function edit(Request $request, RecurringSaving $recurringSaving): View
    {
        $this->ensureOwned($request, $recurringSaving);

        return view('recurring-savings.edit', [
            'template' => $recurringSaving,
            ...$this->formData($request),
        ]);
    }

    public function update(RecurringSavingRequest $request, RecurringSaving $recurringSaving): RedirectResponse
    {
        $this->ensureOwned($request, $recurringSaving);

        $recurringSaving->update($request->validated());

        return redirect()->route('recurring-savings.index')->with('status', 'recurring-saving-updated');
    }

    public function destroy(Request $request, RecurringSaving $recurringSaving): RedirectResponse
    {
        $this->ensureOwned($request, $recurringSaving);

        $recurringSaving->delete();

        return redirect()->route('recurring-savings.index')->with('status', 'recurring-saving-deleted');
    }

    /**
     * The single-click action: create this month's saving from the template.
     */
    public function post(Request $request, RecurringSaving $recurringSaving): RedirectResponse
    {
        $this->ensureOwned($request, $recurringSaving);

        $saving = $this->service->postForMonth($recurringSaving, now());

        return redirect()->route('recurring-savings.index')
            ->with('status', $saving ? 'recurring-saving-posted' : 'recurring-saving-already-posted');
    }

    /**
     * @return array{members: Collection<int, User>, savingTypes: array<string, string>}
     */
    private function formData(Request $request): array
    {
        $family = $request->user()->currentFamily();

        return [
            'members' => $family->users()->orderBy('name')->get(),
            'savingTypes' => SavingType::options(),
        ];
    }

    private function ensureOwned(Request $request, RecurringSaving $recurringSaving): void
    {
        abort_unless($recurringSaving->family_id === $request->user()->currentFamily()?->id, 403);
    }
}
