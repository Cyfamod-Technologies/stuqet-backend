<?php

namespace Tests\Feature;

use App\Http\Middleware\TrustProxies;
use App\Providers\AppServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class HttpsUrlGenerationTest extends TestCase
{
    public function test_https_app_url_generates_secure_shared_assets_even_outside_production(): void
    {
        config(['app.url' => 'https://api.stuqet.com', 'app.force_https' => false]);
        URL::forceScheme(null);
        app()->getProvider(AppServiceProvider::class)->boot();
        $this->assertStringStartsWith('https://', static_asset('assets/js/vendors.js'));
        $this->assertStringStartsWith('https://', static_asset('assets/css/vendors.css'));
        $this->assertStringStartsWith('https://', url('/login'));
    }

    public function test_explicit_force_https_controls_url_generation(): void
    {
        config(['app.url' => 'http://localhost', 'app.force_https' => true]);
        URL::forceScheme(null);
        app()->getProvider(AppServiceProvider::class)->boot();
        $this->assertStringStartsWith('https://', asset('public/assets/js/vendors.js'));
    }

    public function test_proxy_protocol_is_honored_only_for_configured_proxies(): void
    {
        $request = Request::create('http://api.stuqet.com/login', 'GET', [], [], [], ['REMOTE_ADDR' => '10.0.0.2', 'HTTP_X_FORWARDED_PROTO' => 'https']);
        try {
            config(['app.trusted_proxies' => ['10.0.0.2']]);
            app(TrustProxies::class)->handle($request, function ($request) {
                $this->assertTrue($request->isSecure());
                return response('ok');
            });
            config(['app.trusted_proxies' => []]);
            app(TrustProxies::class)->handle($request, function ($request) {
                $this->assertFalse($request->isSecure());
                return response('ok');
            });
        } finally {
            Request::setTrustedProxies([], Request::HEADER_X_FORWARDED_PROTO);
        }
    }
}
