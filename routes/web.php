<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FamilyMemberController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\LoanPaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StatementImportController;
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

    // Loans and their nested repayments.
    Route::resource('loans', LoanController::class);
    Route::post('/loans/{loan}/payments', [LoanPaymentController::class, 'store'])->name('loans.payments.store');
    Route::delete('/loans/{loan}/payments/{payment}', [LoanPaymentController::class, 'destroy'])->name('loans.payments.destroy');

    // Google Pay statement import: upload -> preview -> confirm.
    Route::get('/imports/gpay', [StatementImportController::class, 'create'])->name('imports.create');
    Route::post('/imports/gpay', [StatementImportController::class, 'preview'])->name('imports.parse');
    Route::get('/imports/gpay/preview', [StatementImportController::class, 'showPreview'])->name('imports.preview');
    Route::post('/imports/gpay/confirm', [StatementImportController::class, 'confirm'])->name('imports.confirm');

    // Family member management. Named "family.*" for readable URLs (/family),
    // while the {family_member} parameter drives implicit model binding.
    Route::get('/family', [FamilyMemberController::class, 'index'])->name('family.index');
    Route::get('/family/create', [FamilyMemberController::class, 'create'])->name('family.create');
    Route::post('/family', [FamilyMemberController::class, 'store'])->name('family.store');
    Route::get('/family/{family_member}/edit', [FamilyMemberController::class, 'edit'])->name('family.edit');
    Route::put('/family/{family_member}', [FamilyMemberController::class, 'update'])->name('family.update');
    Route::delete('/family/{family_member}', [FamilyMemberController::class, 'destroy'])->name('family.destroy');

    // Reports. The monthly financial report is exportable as a PDF (TCPDF).
    Route::get('/reports/monthly/pdf', [ReportController::class, 'monthlyPdf'])->name('reports.monthly.pdf');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';
