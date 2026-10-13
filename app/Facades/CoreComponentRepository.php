<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class CoreComponentRepository extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'core-component-repository';
    }
}
