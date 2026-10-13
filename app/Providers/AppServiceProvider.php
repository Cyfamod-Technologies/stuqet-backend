<?php

namespace App\Providers;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
  /**
   * Bootstrap any application services.
   *
   * @return void
   */
  public function boot()
  {
      Schema::defaultStringLength(191);
      Paginator::useBootstrap();

      if ($this->app->environment('production')) {
          URL::forceScheme('https');
      }

      if ($this->app->runningInConsole()) {
          Event::listen(CommandStarting::class, function (CommandStarting $event) {
              if ($event->command === 'migrate:refresh') {
                  $this->wipeDatabaseForRefresh($event);
                  return;
              }

              if ($event->command === 'migrate') {
                  $this->importShopSqlBeforeMigrate($event);
              }
          });
      }
  }

  /**
   * Register any application services.
   *
   * @return void
   */
  public function register()
  {
      Sanctum::ignoreMigrations();
  }

  protected function importShopSqlBeforeMigrate(CommandStarting $event): void
  {
      $sqlPath = base_path('shop.sql');

      if (! is_file($sqlPath)) {
          return;
      }

      if (! $this->shouldImportShopSql()) {
          return;
      }

      $event->output?->writeln('<info>Importing shop.sql before migrate...</info>');
      DB::unprepared(file_get_contents($sqlPath));
      $event->output?->writeln('<info>shop.sql import completed.</info>');
  }

  protected function wipeDatabaseForRefresh(CommandStarting $event): void
  {
      $event->output?->writeln('<info>Dropping all database tables before migrate:refresh...</info>');

      Schema::dropAllTables();
      Schema::dropAllViews();

      $event->output?->writeln('<info>Database wipe completed. shop.sql will be imported when migrate runs.</info>');
  }

  protected function shouldImportShopSql(): bool
  {
      return ! Schema::hasTable('migrations')
          && ! Schema::hasTable('users')
          && ! Schema::hasTable('business_settings');
  }
}
