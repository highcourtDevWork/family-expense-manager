<?php

namespace App\Http\Controllers;

use App\Models\ApiCredential;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ApiCredentialController extends Controller
{
    public function index(Request $request): View
    {
        return view('settings.api', [
            'credentials' => $request->user()->apiCredentials()->latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        [$credential, $plainSecret] = ApiCredential::generate($request->user(), $validated['name']);

        // The secret is shown exactly once, flashed to the next request only.
        return redirect()->route('api-keys.index')->with([
            'new_api_key' => $credential->key,
            'new_api_secret' => $plainSecret,
        ]);
    }

    public function destroy(Request $request, ApiCredential $apiKey): RedirectResponse
    {
        // A user may only manage their own credentials.
        abort_unless($apiKey->user_id === $request->user()->id, 403);

        $apiKey->delete();

        return redirect()->route('api-keys.index')->with('status', 'api-key-deleted');
    }
}
