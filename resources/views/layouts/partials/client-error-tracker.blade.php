@if(app(\App\Services\ErrorLogService::class)->enabled())
<script>
(function () {
    var endpoint = @json(route('telemetry.client-errors'));
    var reported = {};

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function fingerprint(message, url, source) {
        return String(message || '') + '|' + String(url || '') + '|' + String(source || '');
    }

    function collectFormPayload() {
        var payload = { forms: [] };
        document.querySelectorAll('form').forEach(function (form, index) {
            if (index > 2) {
                return;
            }

            var fields = {};
            form.querySelectorAll('input, select, textarea').forEach(function (field) {
                var name = field.name || field.id;
                if (!name || field.type === 'password' || field.type === 'hidden' && name === '_token') {
                    return;
                }
                if (field.type === 'checkbox' || field.type === 'radio') {
                    if (field.checked) {
                        fields[name] = field.value;
                    }
                    return;
                }
                if (field.value !== '') {
                    fields[name] = String(field.value).slice(0, 500);
                }
            });

            if (Object.keys(fields).length) {
                payload.forms.push({
                    action: form.getAttribute('action') || '',
                    method: (form.getAttribute('method') || 'GET').toUpperCase(),
                    fields: fields,
                });
            }
        });

        return payload;
    }

    function sendReport(data) {
        var key = fingerprint(data.message, data.url, data.source);
        if (reported[key]) {
            return;
        }
        reported[key] = true;

        var body = JSON.stringify(data);
        var headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        };
        var token = csrfToken();
        if (token) {
            headers['X-CSRF-TOKEN'] = token;
        }

        try {
            if (navigator.sendBeacon && !token) {
                navigator.sendBeacon(endpoint, new Blob([body], { type: 'application/json' }));
                return;
            }
        } catch (e) {}

        fetch(endpoint, {
            method: 'POST',
            headers: headers,
            body: body,
            credentials: 'same-origin',
            keepalive: true,
        }).catch(function () {});
    }

    window.addEventListener('error', function (event) {
        sendReport({
            message: event.message || 'Script error',
            stack: event.error && event.error.stack ? event.error.stack : (event.filename + ':' + event.lineno + ':' + event.colno),
            url: window.location.href,
            source: event.filename || 'window.onerror',
            user_agent: navigator.userAgent,
            payload: collectFormPayload(),
        });
    });

    window.addEventListener('unhandledrejection', function (event) {
        var reason = event.reason || {};
        var message = typeof reason === 'string' ? reason : (reason.message || 'Unhandled promise rejection');
        var stack = reason && reason.stack ? reason.stack : null;

        sendReport({
            message: message,
            stack: stack,
            url: window.location.href,
            source: 'unhandledrejection',
            user_agent: navigator.userAgent,
            payload: collectFormPayload(),
        });
    });
})();
</script>
@endif
