<?php

namespace App\Console\Commands;

use App\Enums\FamilyRole;
use App\Models\Family;
use App\Notifications\MonthlyExpenseWarning;
use App\Services\DashboardService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendMonthlyExpenseWarnings extends Command
{
    protected $signature = 'expenses:warn';

    protected $description = 'Warn family admins when this month\'s expenses exceed income.';

    public function __construct(
        private readonly DashboardService $dashboard,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $month = Carbon::now()->startOfMonth();
        $warned = 0;

        Family::query()->with('members.user')->chunk(100, function ($families) use ($month, &$warned): void {
            foreach ($families as $family) {
                $summary = $this->dashboard->summary($family, $month);
                $income = (float) $summary['income'];
                $expense = (float) $summary['expense'];

                // Only warn on genuine overspending.
                if ($expense <= 0 || $expense <= $income) {
                    continue;
                }

                $admins = $family->members
                    ->where('role', FamilyRole::Admin)
                    ->pluck('user')
                    ->filter();

                foreach ($admins as $admin) {
                    $admin->notify(new MonthlyExpenseWarning($month, $income, $expense));
                    $warned++;
                }
            }
        });

        $this->info("Sent {$warned} expense warning(s).");

        return self::SUCCESS;
    }
}
