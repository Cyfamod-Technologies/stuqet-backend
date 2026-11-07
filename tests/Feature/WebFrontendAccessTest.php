<?php

namespace Tests\Feature;

use App\Http\Middleware\RestrictWebFrontend;
use App\Models\User;
use Illuminate\Http\Request;
use Tests\TestCase;

class WebFrontendAccessTest extends TestCase
{
    private function requestThroughGate(string $path, ?string $role = null, bool $json = false)
    {
        $request = Request::create($path);
        $user = $role ? (new User)->forceFill(['id' => 5, 'user_type' => $role]) : null;
        $request->setUserResolver(fn () => $user);
        if ($json) {
            $request->headers->set('Accept', 'application/json');
        }
        return app(RestrictWebFrontend::class)->handle($request, fn () => response('allowed'));
    }

    public function test_disabled_mode_blocks_web_pages_for_guests_and_all_roles(): void
    {
        config(['app.web_frontend_status' => 'disable']);
        foreach ([null, 'customer', 'seller', 'delivery_boy', 'admin', 'staff'] as $role) {
            foreach (['/', '/dashboard', '/seller/dashboard', '/assigned-deliveries', '/shop/example', '/users/registration'] as $path) {
                $response = $this->requestThroughGate($path, $role);
                $this->assertSame(503, $response->status());
                $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
            }
        }
        $json = $this->requestThroughGate('/dashboard', 'customer', true);
        $this->assertSame(503, $json->status());
        $this->assertFalse($json->getData(true)['result']);
    }

    public function test_admin_entry_and_mobile_callbacks_remain_available(): void
    {
        config(['app.web_frontend_status' => 'disable']);
        foreach (['/admin', '/admin/categories', '/login', '/logout', '/password/reset', '/paystack/payment/callback', '/paystack/new-callback', '/rave/callback'] as $path) {
            $this->assertSame('allowed', $this->requestThroughGate($path)->getContent());
        }
        $this->assertSame('allowed', $this->requestThroughGate('/aiz-uploader/upload', 'admin')->getContent());
        $this->assertSame('allowed', $this->requestThroughGate('/subcategories', 'staff')->getContent());
        $this->assertSame(503, $this->requestThroughGate('/aiz-uploader/upload', 'seller')->status());
    }

    public function test_enabled_mode_preserves_normal_access(): void
    {
        config(['app.web_frontend_status' => 'enable']);
        $this->assertSame('allowed', $this->requestThroughGate('/')->getContent());
        $this->assertSame('allowed', $this->requestThroughGate('/seller/dashboard', 'seller')->getContent());
    }

    public function test_actual_homepage_is_blocked_but_swagger_stays_available(): void
    {
        config(['app.web_frontend_status' => 'disable', 'cache.default' => 'array']);
        $this->get('/')->assertStatus(503)->assertSee('We’ll be back soon.');
        $this->get('/api/docs')->assertOk();
        $router = app('router');
        $this->assertNotContains(RestrictWebFrontend::class, $router->getMiddlewareGroups()['api']);
        $admin = $router->getRoutes()->getByName('admin.dashboard');
        $this->assertContains('auth', $admin->gatherMiddleware());
        $this->assertContains('admin', $admin->gatherMiddleware());
    }
}
