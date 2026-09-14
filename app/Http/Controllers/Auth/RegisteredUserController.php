<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Models\User;
use App\Services\FamilyService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisteredUserController extends Controller
{
    public function __construct(
        private readonly FamilyService $familyService,
    ) {}

    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterUserRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
        ]);

        // Every new user starts as the admin of their own workspace, of the
        // account type they chose at signup, pre-seeded with default categories.
        $this->familyService->createForUser(
            $user,
            accountType: AccountType::from($request->validated('account_type')),
        );

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
