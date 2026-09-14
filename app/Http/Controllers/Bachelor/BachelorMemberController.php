<?php

namespace App\Http\Controllers\Bachelor;

use App\Enums\FamilyRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bachelor\StoreMemberRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class BachelorMemberController extends Controller
{
    public function index(Request $request): View
    {
        $family = $request->user()->currentFamily();

        return view('bachelor.members.index', [
            'family' => $family,
            'members' => $family->users()->orderBy('name')->get(),
        ]);
    }

    /**
     * Create a roommate with their own login and add them to this account.
     */
    public function store(StoreMemberRequest $request): RedirectResponse
    {
        $family = $request->user()->currentFamily();

        DB::transaction(function () use ($request, $family): void {
            $member = User::create([
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'password' => Hash::make($request->validated('password')),
            ]);

            $family->members()->create([
                'user_id' => $member->id,
                'role' => FamilyRole::Member,
            ]);
        });

        return redirect()->route('bachelor.members.index')->with('status', 'member-added');
    }

    /**
     * Remove a roommate's access to this account. Their historical shares stay
     * intact for the record; only their membership is revoked.
     */
    public function destroy(Request $request, User $member): RedirectResponse
    {
        $family = $request->user()->currentFamily();

        abort_unless($family->isBachelor() && $request->user()->isAdminOf($family), 403);
        // Can't remove yourself or a member who isn't part of this account.
        abort_if($member->id === $request->user()->id, 403);
        abort_unless($family->users()->whereKey($member->id)->exists(), 404);

        $family->members()->where('user_id', $member->id)->delete();

        return redirect()->route('bachelor.members.index')->with('status', 'member-removed');
    }
}
