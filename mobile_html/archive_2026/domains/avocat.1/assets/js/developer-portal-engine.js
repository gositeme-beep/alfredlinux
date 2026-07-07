document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    /* ---------- AOS ---------- */
    if (typeof AOS !== 'undefined') {
        AOS.init({ duration: 800, once: true });
    }

    /* ---------- Copy to clipboard ---------- */
    document.querySelectorAll('[data-copy]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var targetId = btn.getAttribute('data-copy');
            var target = document.getElementById(targetId);
            if (!target) return;
            var text = target.textContent || target.innerText;
            navigator.clipboard.writeText(text).then(function() {
                var origHTML = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                setTimeout(function() { btn.innerHTML = origHTML; }, 2000);
            }).catch(function() {
                /* fallback */
                var ta = document.createElement('textarea');
                ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
                document.body.appendChild(ta); ta.select();
                try { document.execCommand('copy'); } catch(e) {}
                document.body.removeChild(ta);
            });
        });
    });

    /* ---------- Accordion ---------- */
    document.querySelectorAll('.dp-accordion__trigger').forEach(function(trigger) {
        trigger.addEventListener('click', function() {
            var item = trigger.closest('.dp-accordion__item');
            var isOpen = item.classList.contains('is-open');
            item.classList.toggle('is-open');
            trigger.setAttribute('aria-expanded', !isOpen);
        });
    });

    /* ---------- API Playground ---------- */
    var pgEndpoint = document.getElementById('dp-pg-endpoint');
    var pgBody = document.getElementById('dp-pg-body');
    var pgOutput = document.getElementById('dp-pg-output');
    var pgSend = document.getElementById('dp-pg-send');
    var pgApiKey = document.getElementById('dp-pg-apikey');

    var sampleBodies = {
        'GET /v1/tools': '',
        'POST /v1/chat': JSON.stringify({ message: "What tools are available for DevOps?", stream: false }, null, 2),
        'GET /v1/agents': '',
        'POST /v1/agents': JSON.stringify({ name: "Support Bot", description: "Customer support agent", tools: ["chat", "knowledge-base"], personality: "helpful and professional" }, null, 2),
        'GET /v1/fleet': '',
        'POST /v1/voice/call': JSON.stringify({ phone_number: "+1234567890", agent_id: "agent_abc123", greeting: "Hello, this is Alfred AI calling." }, null, 2),
        'GET /v1/tools/providers': '',
        'POST /v1/research': JSON.stringify({ question: "What are the latest advances in quantum computing?", mode: "standard", max_sources: 5 }, null, 2),
        'POST /v1/creative/image': JSON.stringify({ prompt: "A futuristic cityscape at sunset", model: "flux-schnell", width: 1024, height: 1024 }, null, 2),
        'POST /v1/translate': JSON.stringify({ text: "Hello, how are you?", target: "FR", source: "EN" }, null, 2),
        'POST /v1/documents/parse': JSON.stringify({ url: "https://example.com/document.pdf", format: "pdf" }, null, 2)
    };

    function updatePlaygroundBody() {
        if (pgBody) pgBody.value = sampleBodies[pgEndpoint.value] || '';
    }

    if (pgEndpoint) {
        pgEndpoint.addEventListener('change', updatePlaygroundBody);
        updatePlaygroundBody();
    }

    if (pgSend) {
        pgSend.addEventListener('click', function() {
            var endpoint = pgEndpoint.value;
            var apiKey = pgApiKey.value.trim();
            if (!apiKey) {
                pgOutput.textContent = 'Error: Please enter your API key.';
                pgOutput.style.color = 'var(--dp-red)';
                return;
            }

            var parts = endpoint.split(' ');
            var method = parts[0];
            var path = parts[1];
            var url = 'https://api.gositeme.com' + path;

            pgOutput.textContent = 'Sending request…';
            pgOutput.style.color = 'var(--dp-text-muted)';
            pgSend.disabled = true;

            var opts = {
                method: method,
                headers: {
                    'Authorization': 'Bearer ' + apiKey,
                    'Content-Type': 'application/json'
                }
            };

            if (method === 'POST' && pgBody.value.trim()) {
                opts.body = pgBody.value.trim();
            }

            fetch(url, opts)
                .then(function(res) { return res.json().then(function(data) { return { status: res.status, data: data }; }); })
                .then(function(result) {
                    pgOutput.style.color = result.status >= 200 && result.status < 300 ? 'var(--dp-green)' : 'var(--dp-orange)';
                    pgOutput.textContent = 'HTTP ' + result.status + '\n\n' + JSON.stringify(result.data, null, 2);
                })
                .catch(function(err) {
                    pgOutput.style.color = 'var(--dp-red)';
                    pgOutput.textContent = 'Error: ' + err.message + '\n\nNote: Make sure your API key is valid and the endpoint is accessible.';
                })
                .finally(function() { pgSend.disabled = false; });
        });
    }

    /* ---------- API Key Management (logged-in users) ---------- */
    var keysBody = document.getElementById('dp-keys-tbody');
    var genKeyBtn = document.getElementById('dp-gen-key-btn');
    var keyModal = document.getElementById('dp-key-modal');
    var keyModalClose = document.getElementById('dp-modal-close');
    var keyCreateBtn = document.getElementById('dp-key-create');
    var keyResult = document.getElementById('dp-key-result');
    var keyValue = document.getElementById('dp-key-value');

    function loadApiKeys() {
        if (!keysBody) return;
        fetch('/api/enterprise.php?action=api-keys', { credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.keys || data.keys.length === 0) {
                    keysBody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:var(--dp-text-muted);padding:40px">No API keys yet. Click "Generate New Key" to create one.</td></tr>';
                    return;
                }
                keysBody.innerHTML = data.keys.map(function(k) {
                    var statusClass = k.active ? 'dp-key-status--active' : 'dp-key-status--inactive';
                    var statusLabel = k.active ? 'Active' : 'Inactive';
                    return '<tr>' +
                        '<td>' + (k.name || 'Unnamed') + '</td>' +
                        '<td><span class="dp-key-prefix">' + (k.prefix || k.key_prefix || 'ak_live_•••') + '</span></td>' +
                        '<td>' + (k.created_at || '—') + '</td>' +
                        '<td>' + (k.last_used || 'Never') + '</td>' +
                        '<td><span class="dp-key-status ' + statusClass + '"><i class="fas fa-circle" style="font-size:.5rem"></i> ' + statusLabel + '</span></td>' +
                    '</tr>';
                }).join('');
            })
            .catch(function() {
                keysBody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:var(--dp-text-muted);padding:40px">Unable to load API keys. Please try again later.</td></tr>';
            });
    }

    if (keysBody) loadApiKeys();

    /* Modal open/close */
    if (genKeyBtn && keyModal) {
        genKeyBtn.addEventListener('click', function() {
            keyModal.classList.add('is-open');
            if (keyResult) keyResult.style.display = 'none';
        });
    }
    if (keyModalClose && keyModal) {
        keyModalClose.addEventListener('click', function() { keyModal.classList.remove('is-open'); });
    }
    if (keyModal) {
        keyModal.addEventListener('click', function(e) {
            if (e.target === keyModal) keyModal.classList.remove('is-open');
        });
    }

    /* Create key */
    if (keyCreateBtn) {
        keyCreateBtn.addEventListener('click', function() {
            var nameInput = document.getElementById('dp-key-name');
            var scopeInput = document.getElementById('dp-key-scope');
            var name = nameInput ? nameInput.value.trim() : '';
            var scope = scopeInput ? scopeInput.value.trim() : '';

            if (!name) { nameInput.focus(); return; }

            keyCreateBtn.disabled = true;
            keyCreateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating…';

            fetch('/api/enterprise.php?action=generate-api-key', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name: name, scope: scope })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.key) {
                    keyValue.textContent = data.key;
                    keyResult.style.display = 'block';
                    loadApiKeys();
                } else {
                    alert(data.error || 'Failed to generate key. Please try again.');
                }
            })
            .catch(function(err) { alert('Error: ' + err.message); })
            .finally(function() {
                keyCreateBtn.disabled = false;
                keyCreateBtn.innerHTML = '<i class="fas fa-plus"></i> Create Key';
            });
        });
    }
});
