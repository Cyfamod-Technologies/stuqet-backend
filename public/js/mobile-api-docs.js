(function () {
    'use strict';
    var container = document.getElementById('swagger-ui');
    var status = document.getElementById('docs-status');
    var title = document.getElementById('reference-title');
    var buttons = document.querySelectorAll('[data-group]');
    var spec, ui;
    var selected = 'All';
    var exampleUrl = document.querySelector('[data-request-url]');
    if (exampleUrl) exampleUrl.textContent = JSON.stringify(new URL(exampleUrl.dataset.requestUrl, window.location.origin).href);
    function filteredSpec(group) {
        var document = Object.assign({}, spec, { paths: {} });
        Object.keys(spec.paths).forEach(function (path) {
            var operations = {};
            Object.keys(spec.paths[path]).forEach(function (method) {
                var operation = spec.paths[path][method];
                if (group === 'All' || operation.tags.indexOf(group) !== -1) operations[method] = operation;
            });
            if (Object.keys(operations).length) document.paths[path] = operations;
        });
        return document;
    }
    buttons.forEach(function (button) {
        button.addEventListener('click', function () {
            selected = button.dataset.group;
            buttons.forEach(function (item) {
                var active = item === button;
                item.classList.toggle('active', active);
                item.setAttribute('aria-pressed', String(active));
            });
            title.textContent = selected === 'All' ? 'All endpoints' : (selected === 'Customer and shared' ? 'Customer & shared' : selected) + ' endpoints';
            if (ui) ui.specActions.updateJson(filteredSpec(selected));
            if (window.innerWidth <= 780) document.getElementById('reference').scrollIntoView();
        });
    });
    fetch(container.dataset.specUrl, { headers: { Accept: 'application/json' } })
        .then(function (response) {
            if (!response.ok) throw new Error('Could not load API specification.');
            return response.json();
        })
        .then(function (document) {
            spec = document;
            ui = SwaggerUIBundle({
                spec: filteredSpec(selected), dom_id: '#swagger-ui', deepLinking: true,
                filter: true, docExpansion: 'none', displayRequestDuration: true,
                persistAuthorization: false, validatorUrl: null,
                requestInterceptor: function (request) { request.headers.Accept = 'application/json'; return request; },
                onComplete: function () { status.hidden = true; }
            });
            status.hidden = true;
        })
        .catch(function () { status.textContent = 'The reference could not load. Refresh this page or download OpenAPI JSON from the sidebar.'; });
}());
