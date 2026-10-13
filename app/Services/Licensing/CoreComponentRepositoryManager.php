<?php

namespace App\Services\Licensing;

use App\Models\Addon;

class CoreComponentRepositoryManager
{
    public function __construct(
        protected object $driver
    ) {
    }

    public function instantiateShopRepository(): void
    {
        $this->driver->instantiateShopRepository();
    }

    public function initializeCache(): void
    {
        $this->driver->initializeCache();
    }

    public function finalizeCache(Addon $addon): void
    {
        $this->driver->finalizeCache($addon);
    }
}
