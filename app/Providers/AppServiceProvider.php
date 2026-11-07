<?php

namespace App\Providers;

use Illuminate\Console\Events\CommandStarting;
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

      if ($this->app->environment('production')
          || config('app.force_https')
          || parse_url((string) config('app.url'), PHP_URL_SCHEME) === 'https') {
          URL::forceScheme('https');
      }

      if ($this->app->runningInConsole()) {
          Event::listen(CommandStarting::class, function (CommandStarting $event) {
              if ($event->command === 'migrate:refresh') {
                  $this->wipeDatabaseForRefresh($event);
                  return;
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
      $this->app->singleton(\App\Console\Commands\Migrate::class, function ($app) {
          return new \App\Console\Commands\Migrate($app['migrator'], $app['events']);
      });
  }

  protected function wipeDatabaseForRefresh(CommandStarting $event): void
  {
      $event->output?->writeln('<info>Dropping all database tables before migrate:refresh...</info>');

      Schema::dropAllTables();
      Schema::dropAllViews();

      $event->output?->writeln('<info>Database wipe completed. shop.sql will be imported when migrate runs.</info>');
  }

}
