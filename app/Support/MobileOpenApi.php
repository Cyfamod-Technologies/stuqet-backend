<?php

namespace App\Support;

use App\Http\Middleware\EnsureSystemKey;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use ReflectionMethod;
use ReflectionFunction;
use ReflectionFunctionAbstract;

class MobileOpenApi
{
    public function generate(Router $router): array
    {
        $paths = [];
        $unavailable = [];

        foreach ($router->getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'api/v2/')) {
                continue;
            }
            // Temporarily keep auction and POS out of the mobile reference.
            if (preg_match('#^api/v2/(auction(?:/|$)|seller/(?:auction-products|auction-product-bids)(?:/|$)|seller/pos(?:/|$))#', $route->uri())) {
                continue;
            }

            $action = $route->getActionName();
            [$class, $method] = array_pad(explode('@', $action, 2), 2, null);
            if ($route->getAction('uses') instanceof \Closure) {
                $reflection = new ReflectionFunction($route->getAction('uses'));
                $method = basename($route->uri());
            } elseif (! $method || ! class_exists($class) || ! method_exists($class, $method)) {
                $unavailable[] = ['path' => '/'.$route->uri(), 'methods' => array_values(array_diff($route->methods(), ['HEAD'])), 'reason' => 'Controller or action is missing.'];
                continue;
            } else {
                $reflection = new ReflectionMethod($class, $method);
            }

            $source = implode('', array_slice(file($reflection->getFileName()), $reflection->getStartLine() - 1, $reflection->getEndLine() - $reflection->getStartLine() + 1));
            $fields = $this->requestFields($reflection, $source);
            $uri = '/'.str_replace('?}', '}', $route->uri());
            $tag = str_contains($uri, '/seller/') ? 'Seller' : (str_contains($uri, '/delivery-boy/') ? 'Delivery' : (str_contains($uri, '/auth/') ? 'Authentication' : 'Customer and shared'));
            if (preg_match('#/api/v2/(paystack|flutterwave|payment-types)(/|$)#', $uri)) {
                $tag = 'Payments';
            }
            $security = [];
            if (! in_array(EnsureSystemKey::class, $route->excludedMiddleware(), true)) {
                $security['SystemKey'] = [];
            }
            $authenticated = in_array('auth:sanctum', $route->middleware(), true);
            if ($authenticated) {
                $security['BearerAuth'] = [];
            }

            foreach (array_diff($route->methods(), ['HEAD', 'OPTIONS']) as $verb) {
                $operation = [
                    'tags' => [$tag],
                    'summary' => Str::headline($method),
                    'operationId' => strtolower($verb).'_'.str_replace(['/', '{', '}', '-'], ['_', '', '', '_'], trim($uri, '/')).'_'.substr(sha1($verb.$uri), 0, 8),
                    'description' => 'Request fields below are discovered from direct controller request access; conditional requirements, helper methods, nested structures, and response contracts are not fully inferred. See the controller for additional constraints. HTTP 200 may also contain result=false and an application error message.',
                    'security' => $security ? [$security] : [],
                    'parameters' => [['name' => 'App-Language', 'in' => 'header', 'required' => false, 'schema' => ['type' => 'string', 'default' => 'en']]],
                    'responses' => ['default' => ['description' => 'Endpoint response. Depending on the operation this may be JSON, a file download, or a payment redirect. Inspect the live response; endpoint-specific response schemas are not yet specified.']],
                ];
                preg_match_all('/\{([^}]+)\}/', $uri, $matches);
                foreach ($matches[1] as $parameter) {
                    $operation['parameters'][] = ['name' => $parameter, 'in' => 'path', 'required' => true, 'description' => $tag === 'Delivery' && str_contains($uri, '/deliveries/') ? 'Delivery staff user ID.' : 'Resource identifier; use the identifier expected by this endpoint.', 'schema' => ['type' => 'string']];
                    unset($fields[$parameter]);
                }
                if (str_contains($source, '->paginate(')) {
                    $fields['page'] = ['type' => 'integer', 'minimum' => 1, 'description' => 'Pagination page number.'];
                }

                if (in_array($verb, ['GET', 'DELETE'], true)) {
                    foreach ($fields as $name => $schema) {
                        $operation['parameters'][] = ['name' => $name, 'in' => 'query', 'required' => false, 'schema' => $schema];
                    }
                } elseif ($fields) {
                    $multipart = (bool) preg_match('/(?:->file\(|->hasFile\()/', $source);
                    $schema = ['type' => 'object', 'properties' => $fields, 'additionalProperties' => true];
                    $operation['requestBody'] = ['required' => false, 'content' => [($multipart ? 'multipart/form-data' : 'application/json') => ['schema' => $schema]]];
                }

                $this->customize($uri, $operation);
                MobileFoodDocumentation::apply($uri, $operation);
                if (in_array($uri, ['/api/v2/schools', '/api/v2/schools/{id}', '/api/v2/seller/products/schools'], true)) {
                    $operation['summary'] = str_ends_with($uri, '/{id}') ? 'Get an active school' : 'List active schools';
                    $operation['description'] = 'Active schools available for mobile selectors. Use the returned id as school_id in the seller profile or product form. School administration stays in the admin dashboard.';
                    foreach ($operation['parameters'] as &$parameter) {
                        if ($parameter['name'] === 'search') {
                            $parameter['schema'] = ['type' => 'string', 'maxLength' => 100];
                        }
                        if ($parameter['name'] === 'per_page') {
                            $parameter['schema'] = ['type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 20];
                        }
                    }
                    unset($parameter);
                    $school = ['type' => 'object', 'properties' => ['id' => ['type' => 'integer'], 'name' => ['type' => 'string'], 'slug' => ['type' => 'string'], 'logo_id' => ['type' => 'integer', 'nullable' => true], 'logo_url' => ['type' => 'string', 'nullable' => true]]];
                    $data = str_ends_with($uri, '/{id}') ? $school : ['type' => 'array', 'items' => $school];
                    $operation['responses'] = ['200' => ['description' => 'Active school data; list responses also include pagination metadata and links.', 'content' => ['application/json' => ['schema' => ['type' => 'object', 'properties' => ['data' => $data]]]]], '404' => ['description' => 'School missing or inactive (detail only).'], '422' => ['description' => 'Invalid search or pagination parameters.']];
                }
                $paths[$uri][strtolower($verb)] = $operation;
            }
        }

        ksort($paths);
        usort($unavailable, fn ($a, $b) => strcmp($a['path'], $b['path']));

        return [
            'openapi' => '3.0.3',
            'info' => ['title' => 'Stuqet Mobile API', 'version' => '2.0', 'description' => "Customer, seller, and delivery mobile APIs.\n\n1. Obtain the System-Key from the backend team and enter it under Authorize.\n2. POST /api/v2/auth/login with login_by, email (also used for phone login), password, and user_type: customer, seller, or delivery_boy.\n3. Copy access_token from the response into BearerAuth (without the Bearer prefix).\n4. Use App-Language for localization.\n\nShared customer endpoints also provide catalog and configuration data for other apps. Some routes depend on enabled add-ons or payment-provider configuration. Try it out performs real requests, including mutations: use a test account and staging data. Request schemas discovered from source are partial; login, signup, and delivery-status payloads have explicit examples. Unavailable route handlers are listed separately and excluded from executable operations. No credentials are embedded in this document."],
            'servers' => [['url' => '/', 'description' => 'The application serving these docs']],
            'tags' => array_map(fn ($name) => ['name' => $name], ['Authentication', 'Customer and shared', 'Seller', 'Delivery', 'Payments']),
            'paths' => $paths,
            'components' => ['schemas' => MobileFoodDocumentation::schemas(), 'securitySchemes' => [
                'SystemKey' => ['type' => 'apiKey', 'in' => 'header', 'name' => 'System-Key', 'description' => 'Provided by the backend team.'],
                'BearerAuth' => ['type' => 'http', 'scheme' => 'bearer', 'description' => 'Sanctum access_token returned by login.'],
            ]],
            'x-unavailable-endpoints' => $unavailable,
        ];
    }

    private function requestFields(ReflectionFunctionAbstract $method, string $source): array
    {
        $variables = [];
        $validationSource = '';
        foreach ($method->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type instanceof \ReflectionNamedType && is_a($type->getName(), Request::class, true)) {
                $variables[] = '\$'.preg_quote($parameter->getName(), '/');
                if (is_subclass_of($type->getName(), \Illuminate\Foundation\Http\FormRequest::class)) {
                    $rules = new ReflectionMethod($type->getName(), 'rules');
                    $validationSource .= implode('', array_slice(file($rules->getFileName()), $rules->getStartLine() - 1, $rules->getEndLine() - $rules->getStartLine() + 1));
                }
            }
        }
        $receiver = '(?:'.implode('|', array_merge($variables, ['request\(\)'])).')';
        preg_match_all('/'.$receiver.'->([a-zA-Z_][a-zA-Z0-9_]*)(?![a-zA-Z0-9_]|\s*\()/', $source, $properties);
        preg_match_all('/'.$receiver.'(?:->(?:input|query|get|has|filled|file|hasFile)\(\s*|\[\s*)[\'\"]([a-zA-Z_][a-zA-Z0-9_-]*)[\'\"]/', $source, $inputs);
        preg_match_all('/'.$receiver.'->only\(\s*\[([^]]*)\]/s', $source, $onlyLists);
        $onlyFields = [];
        foreach ($onlyLists[1] as $list) {
            preg_match_all('/[\'\"]([a-zA-Z_][a-zA-Z0-9_-]*)[\'\"]/', $list, $names);
            $onlyFields = array_merge($onlyFields, $names[1]);
        }
        $fields = [];
        foreach (array_unique(array_merge($properties[1], $inputs[1], $onlyFields)) as $name) {
            $fields[$name] = ['description' => 'Read by the controller; type and conditional requirements must be checked for this workflow.'];
            if (preg_match('/->(?:file|hasFile)\(\s*[\'\"]'.preg_quote($name, '/').'[\'\"]/', $source)) {
                $fields[$name] = ['type' => 'string', 'format' => 'binary'];
            }
        }
        if ($validationSource) {
            preg_match_all('/\$rules\[\s*[\'\"]([^\'\"]+)[\'\"]\s*\]|[\'\"]([a-zA-Z_][a-zA-Z0-9_.]*)[\'\"]\s*=>/', $validationSource, $ruleNames, PREG_SET_ORDER);
            foreach ($ruleNames as $match) {
                $name = $match[1] ?: $match[2];
                $root = explode('.', $name)[0];
                $fields[$root] ??= ['description' => 'Declared in FormRequest rules; requirements may depend on the listing type or authenticated user.'];
            }
        }
        // Read literal validation declarations without executing conditional rules
        // (which can depend on the authenticated user or database settings).
        preg_match_all('/(?:\$rules\[\s*[\'\"]([^\'\"]+)[\'\"]\s*\]\s*=|[\'\"]([a-zA-Z_][a-zA-Z0-9_.]*)[\'\"]\s*=>)\s*([\'\"])(.*?)\3/s', $validationSource ?: $source, $declarations, PREG_SET_ORDER);
        foreach ($declarations as $declaration) {
            $name = $declaration[1] ?: $declaration[2];
            $rules = $declaration[4];
            if (! preg_match('/^(required|nullable|sometimes|string|integer|numeric|boolean|array|file|image|email)(?:\||$)/', $rules)) {
                continue;
            }
            $root = explode('.', $name)[0];
            $fields[$root] ??= [];
            $fields[$root]['description'] = ($fields[$root]['description'] ?? '').' Validation declaration (may be conditional): '.$name.' = '.$rules.'.';
            if (str_contains($name, '.')) {
                $fields[$root]['type'] ??= 'object';
                $fields[$root]['additionalProperties'] = true;
            } elseif (preg_match('/(?:^|\|)(integer|numeric|boolean|string|array)(?:\||$)/', $rules, $type)) {
                $fields[$root]['type'] = ['numeric' => 'number', 'integer' => 'integer', 'boolean' => 'boolean', 'string' => 'string', 'array' => 'array'][$type[1]];
                if ($type[1] === 'array') {
                    $fields[$root]['items'] = (object) [];
                }
            }
        }
        ksort($fields);

        return $fields;
    }

    private function customize(string $uri, array &$operation): void
    {
        if (in_array($uri, ['/api/v2/seller/products/add', '/api/v2/seller/products/update/{product}'], true)) {
            $schema = &$operation['requestBody']['content']['application/json']['schema'];
            $schema['required'] = ['name', 'category_ids', 'category_id'];
            $schema['properties']['name'] = ['type' => 'string', 'maxLength' => 255];
            $schema['properties']['category_ids'] = ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => 'Category IDs to attach to the product.'];
            $schema['properties']['category_id'] = ['type' => 'integer', 'description' => 'Primary category; must be one of category_ids.'];
            $schema['properties']['listing_type'] = ['type' => 'string', 'enum' => ['product', 'food', 'service'], 'description' => 'Controls additional conditional food_meta/service_meta rules. Defaults to the seller shop category.'];
        }

        if ($uri === '/api/v2/auth/login') {
            $operation['summary'] = 'Log in as a customer, seller, or delivery staff member';
            $operation['requestBody'] = ['required' => true, 'content' => ['application/json' => ['schema' => [
                'type' => 'object', 'required' => ['login_by', 'email', 'password'],
                'properties' => [
                    'login_by' => ['type' => 'string', 'enum' => ['email', 'phone']],
                    'email' => ['type' => 'string', 'description' => 'Email address, or phone number when login_by=phone.'],
                    'password' => ['type' => 'string', 'format' => 'password'],
                    'user_type' => ['type' => 'string', 'enum' => ['customer', 'seller', 'delivery_boy'], 'default' => 'customer'],
                    'temp_user_id' => ['type' => 'string', 'description' => 'Optional guest cart identifier.'],
                    'g-recaptcha-response' => ['type' => 'string', 'description' => 'Required when the relevant reCAPTCHA setting is enabled.'],
                    'recaptcha_action' => ['type' => 'string', 'description' => 'Backend-configured reCAPTCHA action, when applicable.'],
                ],
            ], 'example' => ['login_by' => 'email', 'email' => 'developer@example.com', 'password' => 'replace-with-test-account-password', 'user_type' => 'customer']]]];
            $operation['responses']['200'] = ['description' => 'Login result. On success, access_token is a Sanctum Bearer token. On failure, result is false and message describes the failure.', 'content' => ['application/json' => ['schema' => ['type' => 'object', 'properties' => ['result' => ['type' => 'boolean'], 'message' => (object) [], 'access_token' => ['type' => 'string'], 'token_type' => ['type' => 'string'], 'user' => ['type' => 'object', 'additionalProperties' => true]]]]]];
        }

        if ($uri === '/api/v2/auth/signup') {
            $properties = &$operation['requestBody']['content']['application/json']['schema']['properties'];
            $properties['register_by'] = ['type' => 'string', 'enum' => ['email', 'phone']];
            $properties['password'] = ['type' => 'string', 'format' => 'password', 'minLength' => 6];
            $properties['password_confirmation'] = ['type' => 'string', 'format' => 'password'];
            $operation['requestBody']['content']['application/json']['example'] = ['name' => 'Mobile Developer', 'register_by' => 'email', 'email_or_phone' => 'developer@example.com', 'password' => 'test-password', 'password_confirmation' => 'test-password'];
        }

        if ($uri === '/api/v2/delivery-boy/change-delivery-status') {
            $operation['requestBody']['content']['application/json']['example'] = ['order_id' => 123, 'delivery_boy_id' => 45, 'status' => 'picked_up'];
        }
    }
}
