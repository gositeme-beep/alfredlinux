const API = '/api/collaboration.php';
function esc(s) { return typeof GDS !== 'undefined' ? GDS.esc(s) : String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

async function cGet(action, params = '') {
    try { const r = await fetch(`${API}?action=${action}${params}`, {credentials:'same-origin'}); return await r.json(); }
    catch(e) { console.warn('API error:', action, e); return {success:false}; }
}

async function loadSessions() {
    const d = await cGet('my_sessions');
    const el = document.getElementById('sessions-list');
    if (d.success && d.sessions && d.sessions.length > 0) {
        const active = d.sessions.filter(s => s.status === 'active');
        document.getElementById('kpi-sessions').textContent = active.length;
        el.innerHTML = d.sessions.slice(0, 6).map(s => `<div class="cb-session-card">
            <div>
                <h3>${esc(s.name || 'Untitled Session')}</h3>
                <div class="meta">${esc(s.session_type || 'general')} · Created ${esc(s.created_at?.slice(0,10) || '')}</div>
            </div>
            <div style="display:flex;gap:.5rem;align-items:center;">
                <span class="cb-status ${s.status === 'active' ? 'cb-s-active' : 'cb-s-ended'}">${esc(s.status)}</span>
                ${s.status === 'active' ? `<button class="cb-btn" onclick="joinSession(${parseInt(s.id) || 0})">Join</button>` : ''}
            </div>
        </div>`).join('');
    } else { el.innerHTML = '<div style="color:var(--cb-muted);text-align:center;padding:1rem;">No sessions yet — create one!</div>'; }
}

async function loadDocs() {
    const d = await cGet('doc_list');
    const el = document.getElementById('docs-list');
    if (d.success && d.documents && d.documents.length > 0) {
        document.getElementById('kpi-docs').textContent = d.documents.length;
        el.innerHTML = `<table class="cb-table"><thead><tr><th>Title</th><th>Status</th><th>Updated</th></tr></thead><tbody>${
            d.documents.slice(0, 6).map(doc => `<tr><td>${esc(doc.title)}</td><td>${doc.locked_by ? '<span class="cb-status cb-s-locked">🔒 Locked</span>' : '<span class="cb-status cb-s-active">Open</span>'}</td><td style="color:var(--cb-muted)">${esc(doc.updated_at?.slice(0,10) || '')}</td></tr>`).join('')
        }</tbody></table>`;
    } else { el.innerHTML = '<div style="color:var(--cb-muted);text-align:center;padding:1rem;">No documents yet</div>'; document.getElementById('kpi-docs').textContent = '0'; }
}

async function loadBoards() {
    const el = document.getElementById('boards-list');
    // Whiteboards are session-scoped; show a prompt to create
    el.innerHTML = '<div style="text-align:center;padding:1rem;"><p style="color:var(--cb-muted);margin-bottom:.75rem;">Create whiteboards within collaboration sessions</p><button class="cb-btn cb-btn-primary" onclick="createSession()">+ Start Session</button></div>';
    document.getElementById('kpi-boards').textContent = '—';
}

async function loadConferences() {
    const el = document.getElementById('conf-list');
    el.innerHTML = '<div style="text-align:center;padding:1rem;"><p style="color:var(--cb-muted);margin-bottom:.75rem;">Start a video conference from any session</p><a href="/conference-room" class="cb-btn cb-btn-primary">Open Conference Room</a></div>';
    document.getElementById('kpi-confs').textContent = '—';
}

async function loadPolls() {
    const el = document.getElementById('polls-list');
    el.innerHTML = '<div style="color:var(--cb-muted);text-align:center;padding:1rem;">Create polls within active sessions</div>';
    document.getElementById('kpi-polls').textContent = '—';
}

async function loadChat() {
    const d = await cGet('chat_history', '&limit=10');
    const el = document.getElementById('chat-list');
    if (d.success && d.messages && d.messages.length > 0) {
        el.innerHTML = d.messages.map(m => `<div style="display:flex;gap:.75rem;padding:.5rem 0;border-bottom:1px solid rgba(255,255,255,.05);">
            <div style="width:32px;height:32px;border-radius:50%;background:var(--cb-purple);display:flex;align-items:center;justify-content:center;font-size:.8rem;flex-shrink:0;">👤</div>
            <div><div style="font-weight:500;font-size:.85rem;">User #${esc(m.client_id || '?')} <span style="color:var(--cb-muted);font-weight:400;font-size:.75rem;">${esc(m.created_at?.slice(11,16) || '')}</span></div>
            <div style="font-size:.85rem;color:var(--cb-muted);margin-top:.15rem;">${esc(m.message)}</div></div>
        </div>`).join('');
    } else { el.innerHTML = '<div style="color:var(--cb-muted);text-align:center;padding:1rem;">No chat messages yet</div>'; }
}

async function createSession() {
    const name = prompt('Session name:');
    if (!name) return;
    try {
        const r = await fetch(`${API}?action=create_session`, {method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/json', 'X-CSRF-Token': window.AW_CSRF_TOKEN || ''}, body:JSON.stringify({name, session_type:'general'})});
        const d = await r.json();
        if (d.success) { refreshAll(); } else { alert(d.error || 'Failed'); }
    } catch(e) { alert('Network error'); }
}

async function joinSession(id) {
    const d = await cGet('join_session', `&session_id=${id}`);
    if (d.success) { alert('Joined session #' + id); } else { alert(d.error || 'Could not join'); }
}

function refreshAll() { loadSessions(); loadDocs(); loadBoards(); loadConferences(); loadPolls(); loadChat(); }
refreshAll();
