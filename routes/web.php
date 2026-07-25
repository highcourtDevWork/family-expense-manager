<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FamilyMemberController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('expenses', ExpenseController::class)->except('show');

    // Monthly report is declared before the resource so its static /incomes/report
    // path is never mistaken for a resource segment.
    Route::get('/incomes/report', [IncomeController::class, 'report'])->name('incomes.report');
    Route::resource('incomes', IncomeController::class)->except('show');

    // Family member management. Named "family.*" for readable URLs (/family),
    // while the {family_member} parameter drives implicit model binding.
    Route::get('/family', [FamilyMemberController::class, 'index'])->name('family.index');
    Route::get('/family/create', [FamilyMemberController::class, 'create'])->name('family.create');
    Route::post('/family', [FamilyMemberController::class, 'store'])->name('family.store');
    Route::get('/family/{family_member}/edit', [FamilyMemberController::class, 'edit'])->name('family.edit');
    Route::put('/family/{family_member}', [FamilyMemberController::class, 'update'])->name('family.update');
    Route::delete('/family/{family_member}', [FamilyMemberController::class, 'destroy'])->name('family.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';
