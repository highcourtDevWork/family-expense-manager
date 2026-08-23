<?php

namespace App\Console\Commands;

use App\Services\RecurringIncomeService;
use Illuminate\Console\Command;

class PostRecurringIncomes extends Command
{
    protected $signature = 'recurring-incomes:post';

    protected $description = 'Create this month\'s income from all auto-post recurring templates.';

    public function __construct(
        private readonly RecurringIncomeService $service,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $created = $this->service->postAllDue();

        $this->info("Created {$created} recurring income entr(ies).");

        return self::SUCCESS;
    }
}
