<?php

namespace App\Services\Licensing\Drivers;

use App\Models\Addon;
use RuntimeException;

class EnvLicenseDriver
{
    public function instantiateShopRepository(): void
    {
        $this->ensureValid();
    }

    public function initializeCache(): void
    {
        $this->ensureValid();
    }

    public function finalizeCache(Addon $addon): void
    {
        $this->ensureValid();
    }

    protected function ensureValid(): void
    {
        $configuredCode = (string) config('license.code', '');
        $expectedCode = (string) config('license.expected_code', '');

        if ($configuredCode === '' || $configuredCode !== $expectedCode) {
            throw new RuntimeException('Invalid or missing LICENSE_CODE configuration.');
        }
    }
}
