<?php

namespace App\Console\Commands;

use App\Support\MobileOpenApi;
use Illuminate\Console\Command;
use Illuminate\Routing\Router;

class GenerateMobileDocs extends Command
{
    protected $signature = 'docs:generate {--check : Fail if the committed specification is out of date}';

    protected $description = 'Generate Swagger/OpenAPI documentation for the mobile APIs';

    public function handle(MobileOpenApi $generator, Router $router): int
    {
        $json = json_encode($generator->generate($router), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
        $path = resource_path('docs/openapi.json');
        if ($this->option('check')) {
            if (! is_file($path) || file_get_contents($path) !== $json) {
                $this->error('Mobile API documentation is out of date. Run php artisan docs:generate.');
                return self::FAILURE;
            }
            $this->info('Mobile API documentation is up to date.');
            return self::SUCCESS;
        }
        file_put_contents($path, $json);
        $this->info('Generated resources/docs/openapi.json.');
        return self::SUCCESS;
    }
}
