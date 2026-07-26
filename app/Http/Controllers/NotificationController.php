<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // Viewing the list marks everything read.
        $user->unreadNotifications->markAsRead();

        return view('notifications.index', [
            'notifications' => $user->notifications()->latest()->paginate(20),
        ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->user()->notifications()->delete();

        return redirect()->route('notifications.index');
    }
}
