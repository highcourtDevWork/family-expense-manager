<?php

namespace App\Console\Commands;

use App\Services\RecurringSavingService;
use Illuminate\Console\Command;

class PostRecurringSavings extends Command
{
    protected $signature = 'recurring-savings:post';

    protected $description = 'Create this month\'s savings from all auto-post recurring templates.';

    public function __construct(
        private readonly RecurringSavingService $service,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $created = $this->service->postAllDue();

        $this->info("Created {$created} recurring saving entr(ies).");

        return self::SUCCESS;
    }
}
