<?php

use App\Http\Controllers\ApiCredentialController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\DebtPaymentController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FamilyMemberController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\IncomeSourceController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\LoanEmiController;
use App\Http\Controllers\LoanPaymentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecurringExpenseController;
use App\Http\Controllers\RecurringIncomeController;
use App\Http\Controllers\Bachelor\BachelorMemberController;
use App\Http\Controllers\Bachelor\SplitExpenseController;
use App\Http\Controllers\RecurringSavingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SavingController;
use App\Http\Controllers\SavingsRateController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StatementImportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Savings rate: how income is allocated across expenses, loans and savings.
    Route::get('/savings-rate', SavingsRateController::class)->name('savings-rate.index');

    // Bulk delete is declared before the resource so its static path is never
    // mistaken for /expenses/{expense}.
    Route::delete('/expenses/bulk-delete', [ExpenseController::class, 'bulkDestroy'])->name('expenses.bulk-delete');
    Route::resource('expenses', ExpenseController::class)->except('show');

    // Monthly report is declared before the resource so its static /incomes/report
    // path is never mistaken for a resource segment.
    Route::get('/incomes/report', [IncomeController::class, 'report'])->name('incomes.report');
    Route::resource('incomes', IncomeController::class)->except('show');

    // Loans and their nested repayments.
    Route::resource('loans', LoanController::class);
    Route::post('/loans/{loan}/payments', [LoanPaymentController::class, 'store'])->name('loans.payments.store');
    Route::delete('/loans/{loan}/payments/{payment}', [LoanPaymentController::class, 'destroy'])->name('loans.payments.destroy');
    // Manually mark an EMI installment paid / revert it to pending.
    Route::post('/loans/{loan}/emis/{emi}/pay', [LoanEmiController::class, 'pay'])->name('loans.emis.pay');
    Route::post('/loans/{loan}/emis/{emi}/unpay', [LoanEmiController::class, 'unpay'])->name('loans.emis.unpay');

    // Debts and their nested payments.
    Route::resource('debts', DebtController::class);
    Route::post('/debts/{debt}/payments', [DebtPaymentController::class, 'store'])->name('debts.payments.store');
    Route::delete('/debts/{debt}/payments/{payment}', [DebtPaymentController::class, 'destroy'])->name('debts.payments.destroy');

    // Savings (single-record CRUD, no payments).
    Route::resource('savings', SavingController::class)->except('show')->parameters(['savings' => 'saving']);

    // Recurring expense templates + the one-click "add to this month" action.
    Route::resource('recurring', RecurringExpenseController::class)->except('show')->parameters(['recurring' => 'recurring']);
    Route::post('/recurring/{recurring}/post', [RecurringExpenseController::class, 'post'])->name('recurring.post');

    // Recurring income templates (e.g. salaries) + one-click "add to this month".
    Route::resource('recurring-incomes', RecurringIncomeController::class)->except('show');
    Route::post('/recurring-incomes/{recurring_income}/post', [RecurringIncomeController::class, 'post'])->name('recurring-incomes.post');

    // Recurring savings templates (e.g. SIPs) + one-click "add to this month".
    Route::resource('recurring-savings', RecurringSavingController::class)->except('show');
    Route::post('/recurring-savings/{recurring_saving}/post', [RecurringSavingController::class, 'post'])->name('recurring-savings.post');

    // Bachelor Split: shared household expenses, member logins, and settlements.
    // Gated so only accounts running in Bachelor Split mode can reach them.
    Route::middleware('bachelor')->prefix('bachelor')->name('bachelor.')->group(function () {
        Route::get('/splits', [SplitExpenseController::class, 'index'])->name('splits.index');
        Route::get('/splits/create', [SplitExpenseController::class, 'create'])->name('splits.create');
        Route::post('/splits', [SplitExpenseController::class, 'store'])->name('splits.store');
        Route::post('/splits/shares/{share}/settle', [SplitExpenseController::class, 'settle'])->name('splits.settle');
        Route::post('/splits/shares/{share}/unsettle', [SplitExpenseController::class, 'unsettle'])->name('splits.unsettle');

        Route::get('/members', [BachelorMemberController::class, 'index'])->name('members.index');
        Route::post('/members', [BachelorMemberController::class, 'store'])->name('members.store');
        Route::delete('/members/{member}', [BachelorMemberController::class, 'destroy'])->name('members.destroy');
    });

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

    // API access — issue and delete API key / secret pairs.
    Route::get('/settings/api-keys', [ApiCredentialController::class, 'index'])->name('api-keys.index');
    Route::post('/settings/api-keys', [ApiCredentialController::class, 'store'])->name('api-keys.store');
    Route::delete('/settings/api-keys/{apiKey}', [ApiCredentialController::class, 'destroy'])->name('api-keys.destroy');

    // In-app notifications (loan reminders, expense warnings).
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::delete('/notifications', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';
