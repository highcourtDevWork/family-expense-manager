<?php

namespace App\Console\Commands;

use App\Services\EmiService;
use Illuminate\Console\Command;

class PostDueEmis extends Command
{
    protected $signature = 'emis:post';

    protected $description = 'Auto-post EMI installments that are due on or before today.';

    public function __construct(
        private readonly EmiService $emiService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $posted = $this->emiService->postDueInstallments();

        $this->info("Posted {$posted} EMI installment(s).");

        return self::SUCCESS;
    }
}
