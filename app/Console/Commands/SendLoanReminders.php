<?php

namespace App\Console\Commands;

use App\Enums\LoanStatus;
use App\Models\Loan;
use App\Notifications\LoanPaymentReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendLoanReminders extends Command
{
    protected $signature = 'loans:remind {--days=7 : How many days ahead to look for due loans}';

    protected $description = 'Notify loan holders of active loans due within the given window.';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = Carbon::today()->addDays($days);

        // Active loans with a balance whose end date falls on or before the
        // cutoff (including already-overdue ones).
        $loans = Loan::query()
            ->where('status', LoanStatus::Active)
            ->where('remaining_amount', '>', 0)
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<=', $cutoff)
            ->with('user')
            ->get();

        foreach ($loans as $loan) {
            $loan->user?->notify(new LoanPaymentReminder($loan));
        }

        $this->info("Sent {$loans->count()} loan reminder(s).");

        return self::SUCCESS;
    }
}
