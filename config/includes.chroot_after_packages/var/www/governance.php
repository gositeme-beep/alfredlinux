<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$loggedIn = !empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$userName = $_SESSION['client_name'] ?? 'User';
$firstName = htmlspecialchars(explode(' ', $userName)[0]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GSM Governance — Proposals & Voting</title>
    <meta name="description" content="Participate in GSM governance. Create proposals, vote on platform decisions, delegate your voting power.">
    <link rel="stylesheet" href="/assets/css/fonts.css">
    <link rel="icon" href="/brand/logo.png" type="image/png">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        :root{--bg:#030308;--surface:#0c0c1a;--surface2:#111126;--border:rgba(100,140,255,0.08);--glow:rgba(100,160,255,0.12);--text:#d8dce8;--dim:#5a6488;--accent:#6c5ce7;--accent2:#a29bfe;--green:#00b894;--red:#ff6b6b;--gold:#ffd700;--glass:rgba(12,12,26,0.85)}
        body{font-family:'Inter',system-ui,sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
        a{color:var(--accent2);text-decoration:none}
        .container{max-width:1200px;margin:0 auto;padding:2rem 1.5rem}
        .page-header{text-align:center;margin-bottom:2rem}
        .page-header h1{font-size:2rem;font-weight:800;background:linear-gradient(135deg,var(--accent),var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
        .page-header p{color:var(--dim);margin-top:.5rem}
        .tabs{display:flex;gap:.5rem;justify-content:center;margin-bottom:2rem;flex-wrap:wrap}
        .tab{padding:.6rem 1.2rem;border-radius:10px;background:var(--surface);border:1px solid var(--border);color:var(--dim);cursor:pointer;font-size:.85rem;font-weight:600;transition:.2s}
        .tab.active,.tab:hover{background:var(--accent);color:#fff;border-color:var(--accent)}
        .card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:1.5rem;margin-bottom:1rem}
        .card:hover{border-color:var(--glow)}
        .proposal-title{font-size:1.1rem;font-weight:700;margin-bottom:.5rem}
        .proposal-meta{display:flex;gap:1rem;flex-wrap:wrap;font-size:.8rem;color:var(--dim);margin-bottom:.75rem}
        .badge{display:inline-block;padding:.2rem .6rem;border-radius:6px;font-size:.7rem;font-weight:700;text-transform:uppercase}
        .badge-active{background:rgba(0,184,148,0.15);color:var(--green)}
        .badge-passed{background:rgba(108,92,231,0.15);color:var(--accent2)}
        .badge-rejected{background:rgba(255,107,107,0.15);color:var(--red)}
        .badge-pending{background:rgba(255,215,0,0.15);color:var(--gold)}
        .vote-bar{display:flex;height:8px;border-radius:4px;overflow:hidden;background:var(--surface2);margin:.5rem 0}
        .vote-yes{background:var(--green)}
        .vote-no{background:var(--red)}
        .vote-actions{display:flex;gap:.5rem;margin-top:1rem}
        .btn{padding:.6rem 1.2rem;border-radius:10px;border:none;cursor:pointer;font-weight:700;font-size:.85rem;transition:.2s}
        .btn-yes{background:rgba(0,184,148,0.15);color:var(--green);border:1px solid rgba(0,184,148,0.3)}
        .btn-yes:hover{background:rgba(0,184,148,0.3)}
        .btn-no{background:rgba(255,107,107,0.15);color:var(--red);border:1px solid rgba(255,107,107,0.3)}
        .btn-no:hover{background:rgba(255,107,107,0.3)}
        .btn-primary{background:var(--accent);color:#fff;border:1px solid var(--accent)}
        .btn-primary:hover{opacity:.9}
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:2rem}
        .stat-card{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:1rem;text-align:center}
        .stat-val{font-size:1.5rem;font-weight:800;color:var(--accent2)}
        .stat-label{font-size:.75rem;color:var(--dim);margin-top:.25rem}
        .form-group{margin-bottom:1rem}
        .form-group label{display:block;font-size:.85rem;color:var(--dim);margin-bottom:.3rem}
        .form-group input,.form-group textarea,.form-group select{width:100%;padding:.6rem;background:var(--surface2);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:.9rem}
        .form-group textarea{min-height:100px;resize:vertical}
        #createForm{display:none}
        .empty{text-align:center;padding:3rem;color:var(--dim)}
        .nav-back{display:inline-flex;align-items:center;gap:.5rem;color:var(--dim);font-size:.85rem;margin-bottom:1rem}
        .nav-back:hover{color:var(--text)}
        @media(max-width:600px){.container{padding:1rem}.stats-grid{grid-template-columns:1fr 1fr}}
    </style>
</head>
<body>
<div class="container">
    <a href="/wallet.php" class="nav-back">← Back to Wallet</a>
    <div class="page-header">
        <h1>🏛️ GSM Governance</h1>
        <p>Shape the future of the platform. Create proposals and vote with your GSM.</p>
    </div>

    <div class="stats-grid" id="govStats">
        <div class="stat-card"><div class="stat-val" id="statActive">—</div><div class="stat-label">Active Proposals</div></div>
        <div class="stat-card"><div class="stat-val" id="statTotal">—</div><div class="stat-label">Total Proposals</div></div>
        <div class="stat-card"><div class="stat-val" id="statPassed">—</div><div class="stat-label">Passed</div></div>
        <div class="stat-card"><div class="stat-val" id="statVoters">—</div><div class="stat-label">Unique Voters</div></div>
    </div>

    <div class="tabs">
        <div class="tab active" onclick="switchTab('active')">Active</div>
        <div class="tab" onclick="switchTab('all')">All Proposals</div>
        <div class="tab" onclick="switchTab('my_votes')">My Votes</div>
        <div class="tab" onclick="switchTab('delegate')">Delegation</div>
        <div class="tab" onclick="toggleCreate()">+ Create</div>
    </div>

    <div id="createForm" class="card">
        <h3 style="margin-bottom:1rem">Create Proposal</h3>
        <div class="form-group"><label>Title</label><input id="propTitle" placeholder="A clear title for your proposal"></div>
        <div class="form-group"><label>Description</label><textarea id="propDesc" placeholder="Describe what you're proposing and why..."></textarea></div>
        <div class="form-group"><label>Category</label><select id="propCat"><option value="platform">Platform</option><option value="economy">Economy</option><option value="feature">Feature</option><option value="governance">Governance</option><option value="community">Community</option></select></div>
        <div class="form-group"><label>Voting Duration (hours)</label><input id="propDuration" type="number" value="72" min="24" max="720"></div>
        <div class="form-group"><label>Options (comma-separated, or leave blank for Yes/No)</label><input id="propOptions" placeholder="Option A, Option B, Option C"></div>
        <div class="vote-actions"><button class="btn btn-primary" onclick="submitProposal()">Submit Proposal</button></div>
    </div>

    <div id="proposalList"></div>
</div>

<script>
const API = '/api/governance.php';
let currentTab = 'active';

function getCsrf() {
    const m = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return m ? decodeURIComponent(m[1]) : '';
}

async function api(action, body) {
    const opts = body ? { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrf() }, credentials: 'same-origin', body: JSON.stringify(body) } : { credentials: 'same-origin' };
    const r = await fetch(API + '?action=' + action, opts);
    return r.json();
}

function switchTab(tab) {
    currentTab = tab;
    document.querySelectorAll('.tab').forEach((t, i) => t.classList.toggle('active', ['active','all','my_votes','delegate'][i] === tab));
    document.getElementById('createForm').style.display = 'none';
    loadTab();
}

function toggleCreate() {
    const f = document.getElementById('createForm');
    f.style.display = f.style.display === 'none' ? 'block' : 'none';
}

async function loadTab() {
    const el = document.getElementById('proposalList');
    try {
        if (currentTab === 'my_votes') {
            const d = await api('my_votes');
            if (!d.success) { el.innerHTML = '<div class="empty">Login required</div>'; return; }
            el.innerHTML = (d.votes || []).length ? d.votes.map(v => `<div class="card"><div class="proposal-title">${esc(v.proposal_title || 'Proposal #'+v.proposal_id)}</div><div class="proposal-meta"><span>Voted: <strong>${esc(v.option_text || v.option_index)}</strong></span><span>Weight: ${v.weight || 1} GSM</span><span>${v.voted_at}</span></div></div>`).join('') : '<div class="empty">No votes yet</div>';
            return;
        }
        if (currentTab === 'delegate') {
            const d = await api('my_delegations');
            el.innerHTML = `<div class="card"><h3 style="margin-bottom:1rem">Delegate Voting Power</h3><div class="form-group"><label>Delegate to (Client ID)</label><input id="delTo" type="number" placeholder="Client ID"></div><div class="form-group"><label>Weight (GSM)</label><input id="delWeight" type="number" value="100" min="1"></div><div class="vote-actions"><button class="btn btn-primary" onclick="delegate()">Delegate</button></div></div>` + ((d.delegations || []).length ? '<h3 style="margin:1rem 0">Active Delegations</h3>' + d.delegations.map(x => `<div class="card"><div class="proposal-meta"><span>To: Client #${x.delegate_id}</span><span>Weight: ${x.weight} GSM</span><span>Category: ${x.category || 'all'}</span></div></div>`).join('') : '');
            return;
        }
        const status = currentTab === 'active' ? '&status=active' : '';
        const d = await api('list_proposals' + status);
        const proposals = d.proposals || [];
        if (!proposals.length) { el.innerHTML = '<div class="empty">No proposals found</div>'; return; }
        el.innerHTML = proposals.map(p => {
            const totalVotes = (p.votes_summary || []).reduce((s, v) => s + (v.total_weight || 0), 0);
            const yesW = (p.votes_summary || []).find(v => v.option_index == 0)?.total_weight || 0;
            const noW = (p.votes_summary || []).find(v => v.option_index == 1)?.total_weight || 0;
            const yesPct = totalVotes > 0 ? (yesW / totalVotes * 100).toFixed(1) : 50;
            const noPct = totalVotes > 0 ? (noW / totalVotes * 100).toFixed(1) : 50;
            const badge = p.status === 'active' ? 'badge-active' : p.status === 'passed' ? 'badge-passed' : p.status === 'rejected' ? 'badge-rejected' : 'badge-pending';
            return `<div class="card">
                <div class="proposal-title">${esc(p.title)}</div>
                <div class="proposal-meta">
                    <span class="badge ${badge}">${p.status}</span>
                    <span>Category: ${p.category || 'general'}</span>
                    <span>Quorum: ${p.quorum_gsm || 100} GSM</span>
                    <span>Ends: ${p.ends_at ? new Date(p.ends_at).toLocaleDateString() : '—'}</span>
                </div>
                <p style="font-size:.85rem;color:var(--dim);margin-bottom:.5rem">${esc((p.description || '').substring(0, 200))}</p>
                <div class="vote-bar"><div class="vote-yes" style="width:${yesPct}%"></div><div class="vote-no" style="width:${noPct}%"></div></div>
                <div class="proposal-meta"><span style="color:var(--green)">Yes: ${yesPct}% (${yesW} GSM)</span><span style="color:var(--red)">No: ${noPct}% (${noW} GSM)</span><span>${totalVotes} GSM total</span></div>
                ${p.status === 'active' ? `<div class="vote-actions"><button class="btn btn-yes" onclick="vote(${p.id},0)">👍 Vote Yes</button><button class="btn btn-no" onclick="vote(${p.id},1)">👎 Vote No</button></div>` : ''}
            </div>`;
        }).join('');
    } catch (e) { el.innerHTML = '<div class="empty">Error loading data</div>'; }
}

async function vote(proposalId, optionIndex) {
    const weight = prompt('GSM weight for your vote (min 1):', '10');
    if (!weight || isNaN(weight) || weight < 1) return;
    const d = await api('cast_vote', { proposal_id: proposalId, option_index: optionIndex, weight: parseFloat(weight) });
    if (d.success) { alert('Vote cast!'); loadTab(); } else { alert(d.error || 'Vote failed'); }
}

async function submitProposal() {
    const title = document.getElementById('propTitle').value.trim();
    const desc = document.getElementById('propDesc').value.trim();
    const cat = document.getElementById('propCat').value;
    const duration = parseInt(document.getElementById('propDuration').value) || 72;
    const optStr = document.getElementById('propOptions').value.trim();
    const options = optStr ? optStr.split(',').map(s => s.trim()).filter(Boolean) : ['Yes', 'No'];
    if (!title || title.length < 10) { alert('Title must be at least 10 characters'); return; }
    const d = await api('create_proposal', { title, description: desc, category: cat, duration_hours: duration, options });
    if (d.success) { alert('Proposal created!'); toggleCreate(); loadTab(); } else { alert(d.error || 'Failed to create proposal'); }
}

async function delegate() {
    const to = parseInt(document.getElementById('delTo').value);
    const weight = parseFloat(document.getElementById('delWeight').value) || 100;
    if (!to) { alert('Enter a client ID'); return; }
    const d = await api('delegate', { delegate_id: to, weight });
    if (d.success) { alert('Delegation set!'); switchTab('delegate'); } else { alert(d.error || 'Delegation failed'); }
}

async function loadStats() {
    try {
        const d = await api('active_summary');
        if (d.success) {
            document.getElementById('statActive').textContent = d.summary?.active_proposals ?? 0;
            document.getElementById('statTotal').textContent = d.summary?.total_proposals ?? 0;
            document.getElementById('statPassed').textContent = d.summary?.passed ?? 0;
            document.getElementById('statVoters').textContent = d.summary?.unique_voters ?? 0;
        }
    } catch (e) {}
}

function esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

loadStats();
loadTab();
</script>
</body>
</html>
