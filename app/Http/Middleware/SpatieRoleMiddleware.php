<?php

namespace App\Http\Middleware;

use Closure;
use RuntimeException;

class SpatieRoleMiddleware
{
    public function handle($request, Closure $next, $role, $guard = null)
    {
        return app($this->middlewareClass())->handle($request, $next, $role, $guard);
    }

    private function middlewareClass(): string
    {
        foreach ([
            \Spatie\Permission\Middleware\RoleMiddleware::class,
            \Spatie\Permission\Middlewares\RoleMiddleware::class,
        ] as $class) {
            if (class_exists($class)) {
                return $class;
            }
        }

        throw new RuntimeException('Spatie role middleware class is not installed.');
    }
}
