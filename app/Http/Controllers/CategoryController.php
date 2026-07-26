<?php

namespace App\Http\Controllers;

use App\Http\Requests\Settings\CategoryRequest;
use App\Models\ExpenseCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $family = $request->user()->currentFamily();
        abort_if($family === null, 403);

        return view('settings.categories', [
            // withCount so the view can warn before deleting an in-use category.
            'categories' => $family->categories()->withCount('expenses')->orderBy('name')->get(),
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $request->user()->currentFamily()->categories()->create($request->validated());

        return redirect()->route('categories.index')->with('status', 'category-created');
    }

    public function update(CategoryRequest $request, ExpenseCategory $category): RedirectResponse
    {
        $this->ensureOwned($request, $category);

        $category->update($request->validated());

        return redirect()->route('categories.index')->with('status', 'category-updated');
    }

    public function destroy(Request $request, ExpenseCategory $category): RedirectResponse
    {
        $this->ensureOwned($request, $category);

        // Protect referential integrity: a category in use by expenses (the FK
        // is restrictOnDelete) cannot be removed.
        if ($category->expenses()->exists()) {
            return redirect()->route('categories.index')
                ->withErrors(['category' => 'This category is used by existing expenses and cannot be deleted.']);
        }

        $category->delete();

        return redirect()->route('categories.index')->with('status', 'category-deleted');
    }

    /**
     * A user may only manage categories belonging to their own family.
     */
    private function ensureOwned(Request $request, ExpenseCategory $category): void
    {
        abort_unless($category->family_id === $request->user()->currentFamily()?->id, 403);
    }
}
