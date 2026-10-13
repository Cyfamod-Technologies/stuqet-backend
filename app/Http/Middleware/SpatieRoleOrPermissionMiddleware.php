<?php

namespace App\Http\Middleware;

use Closure;
use RuntimeException;

class SpatieRoleOrPermissionMiddleware
{
    public function handle($request, Closure $next, $roleOrPermission, $guard = null)
    {
        return app($this->middlewareClass())->handle($request, $next, $roleOrPermission, $guard);
    }

    private function middlewareClass(): string
    {
        foreach ([
            \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            \Spatie\Permission\Middlewares\RoleOrPermissionMiddleware::class,
        ] as $class) {
            if (class_exists($class)) {
                return $class;
            }
        }

        throw new RuntimeException('Spatie role-or-permission middleware class is not installed.');
    }
}
