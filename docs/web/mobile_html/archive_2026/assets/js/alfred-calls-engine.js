function showTab(name) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    event.target.classList.add('active');
}

function toggleTranscript(i) {
    const el = document.getElementById('tr' + i);
    el.style.display = el.style.display === 'block' ? 'none' : 'block';
}

async function triggerCall() {
    const phone   = document.getElementById('ob-phone').value.trim();
    const reason  = document.getElementById('ob-reason').value;
    const message = document.getElementById('ob-message').value.trim();
    const resEl   = document.getElementById('ob-result');

    if (!phone) { alert('Please enter a phone number'); return; }

    resEl.style.display = 'block';
    resEl.style.background = 'rgba(0,212,255,.1)';
    resEl.style.color = '#00D4FF';
    resEl.textContent = 'Triggering call...';

    const r = await fetch('/api/vapi-outbound.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({action:'call_client', phone, reason, message})
    });
    const d = await r.json();

    if (d.success) {
        resEl.style.background = 'rgba(16,185,129,.1)';
        resEl.style.color = '#10b981';
        resEl.textContent = '✅ Call triggered! Alfred is calling ' + phone + ' now. Call ID: ' + d.call_id;
    } else {
        resEl.style.background = 'rgba(239,68,68,.1)';
        resEl.style.color = '#ef4444';
        resEl.textContent = '❌ Error: ' + (d.error || JSON.stringify(d));
    }
}

async function callClient(clientId, reason) {
    const resEl = document.getElementById('ob-result');
    document.querySelector('[onclick="showTab(\'outbound\')"]').click();
    resEl.style.display = 'block';
    resEl.style.background = 'rgba(0,212,255,.1)';
    resEl.style.color = '#00D4FF';
    resEl.textContent = 'Triggering call...';

    const r = await fetch('/api/vapi-outbound.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({action:'call_client', client_id: clientId, reason})
    });
    const d = await r.json();

    if (d.success) {
        resEl.style.background = 'rgba(16,185,129,.1)';
        resEl.style.color = '#10b981';
        resEl.textContent = '✅ Alfred is calling the customer now!';
    } else {
        resEl.style.background = 'rgba(239,68,68,.1)';
        resEl.style.color = '#ef4444';
        resEl.textContent = '❌ ' + (d.error || 'Error triggering call');
    }
}

async function bulkCall(type) {
    if (!confirm('Alfred will call all customers in this list. Continue?')) return;
    const resEl = document.getElementById('ob-result');
    resEl.style.display = 'block';
    resEl.textContent = 'Starting bulk campaign...';

    const r = await fetch('/api/vapi-outbound.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({action: 'call_' + type})
    });
    const d = await r.json();
    resEl.textContent = '✅ Campaign complete! Called ' + d.called + ' customers.';
}
