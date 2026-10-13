<?php

namespace App\Providers;

use App\Services\Licensing\CoreComponentRepositoryManager;
use App\Services\Licensing\Drivers\EnvLicenseDriver;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class LicenseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('core-component-repository', function () {
            return new CoreComponentRepositoryManager(
                $this->resolveCoreComponentDriver()
            );
        });
    }

    protected function resolveCoreComponentDriver(): object
    {
        $driver = config('license.driver', 'env');

        return match ($driver) {
            'env' => new EnvLicenseDriver(),
            default => throw new InvalidArgumentException("Unsupported license driver [{$driver}]."),
        };
    }
}
