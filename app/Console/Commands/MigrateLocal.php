<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MigrateLocal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:local
                            {--database= : The database connection to use}
                            {--force : Force the operation to run when in production}
                            {--pretend : Dump the SQL queries that would be run}
                            {--seed : Re-run the database seeder after migrations}
                            {--seeder= : The class name of the root seeder}
                            {--step : Run the migrations so they can be rolled back individually}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run only the application migrations from database/migrations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $options = [
            '--path' => [base_path('database/migrations')],
            '--realpath' => true,
        ];

        foreach (['database', 'seeder'] as $option) {
            $value = $this->option($option);

            if (!empty($value)) {
                $options["--{$option}"] = $value;
            }
        }

        foreach (['force', 'pretend', 'seed', 'step'] as $flag) {
            if ($this->option($flag)) {
                $options["--{$flag}"] = true;
            }
        }

        $this->components->info('Running local migrations from database/migrations only.');

        return (int) $this->call('migrate', $options);
    }
}
