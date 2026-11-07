<?php

namespace Tests\Feature;

use App\Support\MobileOpenApi;
use Illuminate\Routing\Router;
use Tests\TestCase;

class ApiDocumentationTest extends TestCase
{
    public function test_documentation_and_specification_are_available_without_api_credentials(): void
    {
        config(['cache.default' => 'array']);
        $this->get('/api/docs')->assertOk()->assertSee('mobile-api-docs.js')->assertSee('unavailable endpoints')->assertSee('data-group="Payments"', false);
        $this->get('/api/openapi.json')->assertOk()->assertHeader('Content-Type', 'application/json');
    }

    public function test_docs_assets_are_same_origin_even_when_the_proxy_forwards_http(): void
    {
        config(['cache.default' => 'array', 'app.url' => 'http://api.stuqet.com', 'app.asset_url' => 'http://api.stuqet.com']);
        $response = $this->get('http://api.stuqet.com/api/docs');
        $response->assertOk();
        foreach (['vendor/swagger-ui/swagger-ui.css', 'css/mobile-api-docs.css', 'vendor/swagger-ui/swagger-ui-bundle.js', 'js/mobile-api-docs.js', 'favicon-docs.svg'] as $asset) {
            $response->assertSee('"/'.public_asset_path($asset).'"', false);
            $this->assertFileExists(public_path($asset));
        }
        $this->assertDoesNotMatchRegularExpression('/(?:href|src)="http:\/\//', $response->getContent());
    }

    public function test_specification_matches_routes_and_authentication_requirements(): void
    {
        $spec = app(MobileOpenApi::class)->generate(app(Router::class));
        $this->assertSame(
            file_get_contents(resource_path('docs/openapi.json')),
            json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL
        );
        $login = $spec['paths']['/api/v2/auth/login']['post'];
        $this->assertSame([['SystemKey' => []]], $login['security']);
        $this->assertSame(['customer', 'seller', 'delivery_boy'], $login['requestBody']['content']['application/json']['schema']['properties']['user_type']['enum']);
        foreach (['/api/v2/seller/orders', '/api/v2/purchase-history', '/api/v2/delivery-boy/deliveries/assigned/{id}'] as $path) {
            $this->assertSame([['SystemKey' => [], 'BearerAuth' => []]], $spec['paths'][$path]['get']['security']);
        }
        foreach ($spec['x-unavailable-endpoints'] as $endpoint) {
            $this->assertArrayNotHasKey($endpoint['path'], $spec['paths']);
        }
        $ids = [];
        foreach ($spec['paths'] as $path => $operations) {
            foreach ($operations as $operation) {
                $ids[] = $operation['operationId'];
                foreach ($operation['parameters'] as $parameter) {
                    if ($parameter['in'] === 'path') {
                        $this->assertTrue($parameter['required']);
                        $this->assertStringContainsString('{'.$parameter['name'].'}', $path);
                    }
                }
            }
        }
        $this->assertSame(array_unique($ids), $ids);
    }

    public function test_only_paystack_and_flutterwave_payment_routes_are_available(): void
    {
        $router = app(Router::class);
        $paths = array_map(fn ($route) => $route->uri(), iterator_to_array($router->getRoutes()));
        foreach (['api/v2/paystack/init', 'api/v2/paystack/success', 'api/v2/flutterwave/payment/url', 'api/v2/flutterwave/payment/callback'] as $path) {
            $this->assertContains($path, $paths);
        }
        foreach ($paths as $path) {
            if (!str_starts_with($path, 'api/v2/')) {
                continue;
            }
            $this->assertDoesNotMatchRegularExpression('#^api/v2/(stripe|paypal|amarpay|khalti|razorpay|iyzico|bkash|nagad|sslcommerz|paytm|instamojo|payfast|myfatoorah|phonepe|cyber-source|online-pay|offline|payments/pay)(/|$)#', $path);
        }
        $spec = app(MobileOpenApi::class)->generate($router);
        foreach (['/api/v2/paystack/init', '/api/v2/flutterwave/payment/url'] as $path) {
            $this->assertSame(['Payments'], $spec['paths'][$path]['post']['tags']);
        }
    }

    public function test_school_routes_are_documented_and_auction_and_pos_are_hidden_only_in_docs(): void
    {
        $router = app(Router::class);
        $spec = app(MobileOpenApi::class)->generate($router);
        foreach (['/api/v2/schools', '/api/v2/schools/{id}', '/api/v2/seller/products/schools'] as $path) {
            $this->assertArrayHasKey($path, $spec['paths']);
        }
        $this->assertSame([['SystemKey' => []]], $spec['paths']['/api/v2/schools']['get']['security']);
        $this->assertSame([['SystemKey' => [], 'BearerAuth' => []]], $spec['paths']['/api/v2/seller/products/schools']['get']['security']);
        foreach (array_keys($spec['paths']) as $path) {
            $this->assertDoesNotMatchRegularExpression('#/auction(?:/|-)|/pos(?:/|$)#', $path);
        }
        $paths = array_map(fn ($route) => $route->uri(), iterator_to_array($router->getRoutes()));
        $this->assertContains('api/v2/auction/products', $paths);
        $this->assertContains('api/v2/seller/pos/products', $paths);
    }
}
