<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\DebtPaymentController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FamilyMemberController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\IncomeSourceController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\LoanPaymentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SavingController;
use App\Http\Controllers\SettingsController;
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

    // Debts and their nested payments.
    Route::resource('debts', DebtController::class);
    Route::post('/debts/{debt}/payments', [DebtPaymentController::class, 'store'])->name('debts.payments.store');
    Route::delete('/debts/{debt}/payments/{payment}', [DebtPaymentController::class, 'destroy'])->name('debts.payments.destroy');

    // Savings (single-record CRUD, no payments).
    Route::resource('savings', SavingController::class)->except('show')->parameters(['savings' => 'saving']);

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

    // Reports: on-screen daily / monthly / yearly, plus PDF (TCPDF) and CSV
    // (Excel-compatible) exports.
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/daily', [ReportController::class, 'daily'])->name('reports.daily');
    Route::get('/reports/monthly', [ReportController::class, 'monthly'])->name('reports.monthly');
    Route::get('/reports/monthly/pdf', [ReportController::class, 'monthlyPdf'])->name('reports.monthly.pdf');
    Route::get('/reports/monthly/csv', [ReportController::class, 'monthlyCsv'])->name('reports.monthly.csv');
    Route::get('/reports/yearly', [ReportController::class, 'yearly'])->name('reports.yearly');
    Route::get('/reports/yearly/csv', [ReportController::class, 'yearlyCsv'])->name('reports.yearly.csv');

    // Settings hub + family-level configuration lists.
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('/settings/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/settings/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('/settings/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/settings/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    Route::get('/settings/income-sources', [IncomeSourceController::class, 'index'])->name('sources.index');
    Route::post('/settings/income-sources', [IncomeSourceController::class, 'store'])->name('sources.store');
    Route::put('/settings/income-sources/{source}', [IncomeSourceController::class, 'update'])->name('sources.update');
    Route::delete('/settings/income-sources/{source}', [IncomeSourceController::class, 'destroy'])->name('sources.destroy');

    // In-app notifications (loan reminders, expense warnings).
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::delete('/notifications', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';
