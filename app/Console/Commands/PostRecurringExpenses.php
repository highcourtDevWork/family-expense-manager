<?php

namespace App\Console\Commands;

use App\Services\RecurringExpenseService;
use Illuminate\Console\Command;

class PostRecurringExpenses extends Command
{
    protected $signature = 'recurring:post';

    protected $description = 'Create this month\'s expenses from all auto-post recurring templates.';

    public function __construct(
        private readonly RecurringExpenseService $service,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $created = $this->service->postAllDue();

        $this->info("Created {$created} recurring expense(s).");

        return self::SUCCESS;
    }
}
