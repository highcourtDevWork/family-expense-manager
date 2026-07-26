<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * The settings hub: quick links plus counts for the family's configurable
     * lists (categories, income sources) and profile.
     */
    public function index(Request $request): View
    {
        $family = $request->user()->currentFamily();

        return view('settings.index', [
            'family' => $family,
            'categoryCount' => $family?->categories()->count() ?? 0,
            'sourceCount' => $family?->incomeSources()->count() ?? 0,
        ]);
    }
}
