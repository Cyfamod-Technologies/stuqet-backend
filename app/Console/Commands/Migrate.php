<?php

namespace App\Console\Commands;

use Illuminate\Database\Console\Migrations\MigrateCommand;

class Migrate extends MigrateCommand
{
    protected $description = 'Import the shop.sql baseline; use migrate:local for application migrations';

    protected function prepareDatabase()
    {
        // This runs after confirmation and inside the selected database connection.
        $connection = $this->migrator->resolveConnection($this->option('database'));
        $schema = $connection->getSchemaBuilder();

        if (! $schema->hasTable('users') && ! $schema->hasTable('business_settings')) {
            $tables = $schema->getTables();
            $emptyRepositoryOnly = count($tables) === 1
                && $schema->hasTable('migrations')
                && ! $connection->table('migrations')->exists();

            if (count($tables) > 0 && ! $emptyRepositoryOnly) {
                throw new \RuntimeException('Cannot import shop.sql into a partially populated database. Restore the baseline before continuing.');
            }

            $path = base_path('shop.sql');

            if (! is_file($path)) {
                throw new \RuntimeException('The database is empty but shop.sql is missing.');
            }

            if ($this->option('pretend')) {
                throw new \RuntimeException('Import the shop.sql baseline with migrate before using --pretend.');
            }

            // Laravel may have created an empty repository before the SQL import.
            // The dump creates and populates its own migration history table.
            if ($emptyRepositoryOnly) {
                $schema->drop('migrations');
            }

            $this->components->info('Importing shop.sql baseline...');
            $connection->unprepared(file_get_contents($path));
            $this->components->info('shop.sql baseline imported.');
        } elseif (! $schema->hasTable('users') || ! $schema->hasTable('business_settings')) {
            throw new \RuntimeException('The shop.sql baseline is incomplete. Restore the missing baseline tables before continuing.');
        } else {
            $this->components->info('shop.sql baseline already present; skipping import.');
        }

        parent::prepareDatabase();
    }

    protected function getMigrationPaths()
    {
        // shop.sql supplies the base schema, including package-owned tables.
        // Allow package migrations only when an explicit path is requested.
        if ($this->option('path')) {
            return parent::getMigrationPaths();
        }

        return [];
    }
}
