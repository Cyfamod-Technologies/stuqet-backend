<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Stuqet · API Reference</title>
    <link rel="stylesheet" href="{{ asset('vendor/swagger-ui/swagger-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/mobile-api-docs.css') }}">
</head>
<body>
<a class="skip-link" href="#reference">Skip to API reference</a>
<aside class="sidebar">
    <a class="brand" href="{{ route('api.documentation', [], false) }}"><span class="brand-mark">s.</span> stuqet <small>developers</small></a>
    <div class="workspace"><span>{ }</span><div>Mobile API <small>Developer documentation</small></div><b>v2.0</b></div>
    <p class="nav-label">API REFERENCE</p>
    <nav aria-label="API sections" class="sections">
    @foreach(['All endpoints' => 'All', 'Authentication' => 'Authentication', 'Customer & shared' => 'Customer and shared', 'Seller' => 'Seller', 'Delivery' => 'Delivery', 'Payments' => 'Payments'] as $label => $group)
        <button type="button" class="section-button {{ $group === 'All' ? 'active' : '' }}" data-group="{{ $group }}" aria-pressed="{{ $group === 'All' ? 'true' : 'false' }}"><span class="nav-dot"></span>{{ $label }}<span class="nav-count">{{ $group === 'All' ? $endpointCount : ($counts[$group] ?? 0) }}</span></button>
    @endforeach
    </nav>
    <div class="sidebar-bottom"><span class="status-dot"></span> OpenAPI 3.0 specification<a href="{{ route('api.openapi', [], false) }}" download="stuqet-openapi.json">Download JSON <span>↗</span></a><small>Built for the Stuqet mobile team.</small></div>
</aside>
<main>
    <header class="topbar"><span>Documentation <i>/</i> <strong>API Reference</strong></span><span><span class="status-dot"></span> API v2</span></header>
    <div class="content">
        <section class="hero" aria-labelledby="page-title">
            <div><p class="eyebrow">BUILD WITH STUQET</p><h1 id="page-title">Your next integration<br>starts here<span>.</span></h1><p class="hero-description">One reference for customer, seller, and delivery apps.<br>Explore endpoints, authenticate, and send your first request.</p><div class="hero-meta"><span>{{ $endpointCount }} endpoints</span><i></i><span>REST API</span><i></i><span>JSON</span></div></div>
            <div class="code-preview"><div class="code-heading"><span>● ● ●</span> YOUR FIRST REQUEST</div><pre><span class="code-purple">curl</span> <span class="code-green">"{{ url('/api/v2/auth/user') }}"</span> \
  -H <span class="code-green">"Accept: application/json"</span> \
  -H <span class="code-green">"System-Key: YOUR_SYSTEM_KEY"</span> \
  -H <span class="code-green">"Authorization: Bearer YOUR_TOKEN"</span></pre><div class="code-footer"><span class="status-dot"></span> Replace placeholders with your credentials</div></div>
        </section>
        <section class="quickstart" aria-label="Getting started">
            <article><span>01</span><div><h2>Set your system key</h2><p>Open Authorize and enter the <code>System-Key</code> supplied by the backend team.</p></div></article>
            <article><span>02</span><div><h2>Sign in to your app</h2><p>Use <code>/api/v2/auth/login</code> with role <code>customer</code>, <code>seller</code>, or <code>delivery_boy</code>.</p></div></article>
            <article><span>03</span><div><h2>Add your access token</h2><p>Paste <code>access_token</code> into BearerAuth to try protected requests.</p></div></article>
        </section>
        <section id="reference" class="reference" aria-labelledby="reference-title">
            <div class="reference-heading"><div><p class="eyebrow">EXPLORE THE API</p><h2 id="reference-title">All endpoints</h2></div><span>Use “Try it out” with staging data.</span></div>
            <div id="docs-status" role="status">Loading API reference…</div>
            <div id="swagger-ui" data-spec-url="{{ route('api.openapi', [], false) }}"></div>
        </section>
        <details class="integration-notes"><summary>Integration notes <span>Schema coverage & availability</span></summary><p>Request fields are read from controller and validation code. Conditional requirements, service inputs, and response schemas may need additional backend confirmation. <code>App-Language</code> defaults to <code>en</code>. Authorization is cleared on refresh.</p>
        @if(count($unavailable))
            <h3>{{ count($unavailable) }} unavailable endpoints</h3><p>These routes have missing handlers and are excluded from the interactive reference.</p>
            <ul>@foreach($unavailable as $endpoint)<li><code>{{ implode(', ', $endpoint['methods']) }} {{ $endpoint['path'] }}</code></li>@endforeach</ul>
        @endif
        </details>
        <footer><span>stuqet <strong>developers</strong></span><span>Paystack & Flutterwave payments</span></footer>
    </div>
</main>
<script src="{{ asset('vendor/swagger-ui/swagger-ui-bundle.js') }}"></script>
<script src="{{ asset('js/mobile-api-docs.js') }}"></script>
</body>
</html>
