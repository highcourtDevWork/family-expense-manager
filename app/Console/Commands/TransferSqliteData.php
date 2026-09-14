<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-off utility: copy application data from the legacy SQLite file into the
 * current (MySQL/MariaDB) default connection, preserving primary keys. Framework
 * / transient tables are skipped. Safe to re-run — each target table is
 * truncated before load. Kept in the repo as a reproducible migration record.
 */
class TransferSqliteData extends Command
{
    protected $signature = 'db:transfer-sqlite {--source= : Path to the source .sqlite file}';

    protected $description = 'Copy data from the legacy SQLite database into the current MySQL/MariaDB connection.';

    /**
     * Domain tables to copy, ordered parents-before-children. Framework/transient
     * tables (migrations, cache, jobs, sessions, password_reset_tokens) are omitted.
     *
     * @var list<string>
     */
    private const TABLES = [
        'users',
        'families',
        'family_members',
        'expense_categories',
        'expenses',
        'income_sources',
        'incomes',
        'loans',
        'loan_payments',
        'loan_emis',
        'debts',
        'debt_payments',
        'savings',
        'notifications',
        'recurring_expenses',
        'recurring_incomes',
        'recurring_savings',
        'api_credentials',
    ];

    public function handle(): int
    {
        $sourcePath = $this->option('source') ?: database_path('database.sqlite');

        if (! is_file($sourcePath)) {
            $this->error("Source SQLite file not found: {$sourcePath}");

            return self::FAILURE;
        }

        // Define an explicit source connection so it can't be confused with the
        // (now MySQL) default connection that the sqlite config block inherits.
        config()->set('database.connections.sqlite_src', [
            'driver' => 'sqlite',
            'database' => $sourcePath,
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);

        $src = DB::connection('sqlite_src');
        $dest = DB::connection();

        if ($dest->getDriverName() === 'sqlite') {
            $this->error('The default connection is still SQLite. Point DB_CONNECTION at MySQL/MariaDB first.');

            return self::FAILURE;
        }

        $this->info("Source: {$sourcePath}");
        $this->info('Target: '.$dest->getDriverName().' / '.$dest->getDatabaseName());
        $this->newLine();

        // Relax FK + strict checks for the bulk load, then restore.
        $dest->statement('SET SESSION sql_mode = \'\'');
        $dest->statement('SET FOREIGN_KEY_CHECKS = 0');

        $grandTotal = 0;

        foreach (self::TABLES as $table) {
            $dest->table($table)->truncate();

            $count = 0;
            $src->table($table)->orderBy('id')->chunk(200, function ($rows) use ($dest, $table, &$count): void {
                $batch = array_map(static fn ($row): array => (array) $row, $rows->all());
                $dest->table($table)->insert($batch);
                $count += count($batch);
            });

            $grandTotal += $count;
            $this->line(sprintf('  %-20s %6d rows', $table, $count));
        }

        $dest->statement('SET FOREIGN_KEY_CHECKS = 1');

        $this->newLine();
        $this->info("Done. Copied {$grandTotal} rows across ".count(self::TABLES).' tables.');

        return self::SUCCESS;
    }
}
