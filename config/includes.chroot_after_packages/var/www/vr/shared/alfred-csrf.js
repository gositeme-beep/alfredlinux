/**
 * Alfred Chat CSRF Helper — shared across all VR pages.
 * Wraps fetch to /api/alfred-chat.php with automatic CSRF token management.
 * Handles "Session initialized. Please retry." transparently.
 */
(function() {
    'use strict';
    if (window.__ALFRED_CSRF_HELPER) return;
    window.__ALFRED_CSRF_HELPER = true;

    var csrfToken = '';

    // Monkey-patch fetch to inject CSRF for alfred-chat calls
    var originalFetch = window.fetch;
    window.fetch = function(url, opts) {
        if (typeof url === 'string' && url.indexOf('alfred-chat.php') !== -1 && opts && opts.method === 'POST') {
            if (!opts.headers) opts.headers = {};
            // If headers is a Headers object, convert to plain object
            if (opts.headers instanceof Headers) {
                var h = {};
                opts.headers.forEach(function(v, k) { h[k] = v; });
                opts.headers = h;
            }
            if (csrfToken) {
                opts.headers['X-CSRF-TOKEN'] = csrfToken;
            }

            var bodyStr = opts.body;
            return originalFetch.call(this, url, opts).then(function(response) {
                return response.clone().json().then(function(data) {
                    // Save CSRF token from any response
                    if (data.csrf_token) csrfToken = data.csrf_token;
                    // If session init, retry automatically
                    if (data.csrf_refresh) {
                        var retryOpts = Object.assign({}, opts);
                        retryOpts.headers = Object.assign({}, opts.headers);
                        retryOpts.headers['X-CSRF-TOKEN'] = csrfToken;
                        retryOpts.body = bodyStr;
                        return originalFetch.call(window, url, retryOpts);
                    }
                    return response;
                }).catch(function() {
                    return response;
                });
            });
        }
        return originalFetch.apply(this, arguments);
    };
})();
