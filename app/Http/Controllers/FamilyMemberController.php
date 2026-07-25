<?php

namespace App\Http\Controllers;

use App\Enums\FamilyRole;
use App\Enums\Relationship;
use App\Http\Requests\Family\StoreFamilyMemberRequest;
use App\Http\Requests\Family\UpdateFamilyMemberRequest;
use App\Models\FamilyMember;
use App\Services\FamilyMemberService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FamilyMemberController extends Controller
{
    public function __construct(
        private readonly FamilyMemberService $familyMemberService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', FamilyMember::class);

        $family = $request->user()->currentFamily();

        return view('family.index', [
            'family' => $family,
            'members' => $family->members()->with('user')->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', FamilyMember::class);

        return view('family.create', [
            'roles' => FamilyRole::options(),
            'relationships' => Relationship::options(),
        ]);
    }

    public function store(StoreFamilyMemberRequest $request): RedirectResponse
    {
        $this->familyMemberService->addMember(
            $request->user()->currentFamily(),
            $request->validated(),
        );

        return redirect()->route('family.index')
            ->with('status', 'member-added');
    }

    public function edit(FamilyMember $familyMember): View
    {
        $this->authorize('update', $familyMember);

        return view('family.edit', [
            'member' => $familyMember->load('user'),
            'roles' => FamilyRole::options(),
            'relationships' => Relationship::options(),
        ]);
    }

    public function update(UpdateFamilyMemberRequest $request, FamilyMember $familyMember): RedirectResponse
    {
        $this->familyMemberService->updateMember($familyMember, $request->validated());

        return redirect()->route('family.index')
            ->with('status', 'member-updated');
    }

    public function destroy(Request $request, FamilyMember $familyMember): RedirectResponse
    {
        $this->authorize('delete', $familyMember);

        $this->familyMemberService->removeMember($familyMember, $request->user());

        return redirect()->route('family.index')
            ->with('status', 'member-removed');
    }
}
