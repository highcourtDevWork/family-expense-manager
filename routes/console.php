<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Loan reminders go out every morning; expense warnings are checked weekly.
Schedule::command('loans:remind')->dailyAt('08:00');
Schedule::command('expenses:warn')->weeklyOn(1, '09:00');

// EMI installments post themselves on their due date; recurring expense
// templates flagged for auto-posting are created on the 1st of each month.
Schedule::command('emis:post')->dailyAt('01:00');
Schedule::command('recurring:post')->monthlyOn(1, '02:00');
