<?php

use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\IncomeController;
use App\Http\Controllers\Api\V1\MetaController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

/*
| All v1 endpoints require a valid API key + secret (auth.apikey) and are
| rate-limited. Everything is scoped to the authenticated key's family.
*/
Route::prefix('v1')->middleware(['auth.apikey', 'throttle:60,1'])->group(function () {
    Route::get('/user', UserController::class)->name('api.user');
    Route::get('/dashboard', DashboardController::class)->name('api.dashboard');

    Route::apiResource('expenses', ExpenseController::class)->names('api.expenses');
    Route::apiResource('incomes', IncomeController::class)->names('api.incomes');

    Route::get('/categories', [MetaController::class, 'categories'])->name('api.categories');
    Route::get('/income-sources', [MetaController::class, 'incomeSources'])->name('api.income-sources');
});
