<?php

namespace App\Http\Middleware;

use Closure;
use RuntimeException;

class SpatiePermissionMiddleware
{
    public function handle($request, Closure $next, $permission, $guard = null)
    {
        return app($this->middlewareClass())->handle($request, $next, $permission, $guard);
    }

    private function middlewareClass(): string
    {
        foreach ([
            \Spatie\Permission\Middleware\PermissionMiddleware::class,
            \Spatie\Permission\Middlewares\PermissionMiddleware::class,
        ] as $class) {
            if (class_exists($class)) {
                return $class;
            }
        }

        throw new RuntimeException('Spatie permission middleware class is not installed.');
    }
}
