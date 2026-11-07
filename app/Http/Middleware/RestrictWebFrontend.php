<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RestrictWebFrontend
{
    public function handle(Request $request, Closure $next)
    {
        if (strtolower(trim((string) config('app.web_frontend_status', 'enable'))) !== 'disable') {
            return $next($request);
        }

        // Existing authentication/role middleware still protects admin routes.
        if ($request->is('admin', 'admin/*', 'login', 'logout', 'password/*')) {
            return $next($request);
        }

        // Mobile payment flows also return through these web callbacks.
        if ($request->is('paystack/payment/callback', 'paystack/new-callback', 'rave/callback')) {
            return $next($request);
        }

        // Administrative forms use these shared endpoints outside /admin.
        if (in_array($request->user()?->user_type, ['admin', 'staff'], true)
            && $request->is('aiz-uploader/*', 'subcategories*', 'subsubcategories*', 'home_categories*')) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['result' => false, 'message' => 'The web application is temporarily unavailable.'], 503)
                ->header('Cache-Control', 'no-store');
        }

        return response()->view('errors.frontend-disabled', [], 503)->header('Cache-Control', 'no-store');
    }
}
