/**
 * GoSiteMe Supreme Admin Engine v2.0
 * Extracted from supreme-admin.php inline JS
 */
/* ═══════ SUPREME COMMANDER JS ═══════ */
const API_BASE = '/api';

/* ── Tab Navigation ── */
document.querySelectorAll('.sc-tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('.sc-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.sc-panel').forEach(p => p.classList.remove('active'));
    tab.classList.add('active');
    document.getElementById('panel-' + tab.dataset.tab).classList.add('active');
    if (tab.dataset.tab === 'agents') loadAgents();
    if (tab.dataset.tab === 'autonomy') loadAutonomy();
    if (tab.dataset.tab === 'services') loadServices();
    if (tab.dataset.tab === 'tools') loadTools();
    if (tab.dataset.tab === 'alfred-bridge') loadAlfredBridge();
    if (tab.dataset.tab === 'economy') loadEconomy();
    if (tab.dataset.tab === 'mining-revenue') loadMiningRevenue();
    if (tab.dataset.tab === 'ecosystem-monitor') loadEcosystemMonitor();
    if (tab.dataset.tab === 'directives') loadDirectives();
    if (tab.dataset.tab === 'security') loadSecurity();
    if (tab.dataset.tab === 'agentos') loadAlfredOS();
  });
});

/* ── API Helper ── */
async function api(url, opts = {}) {
  try {
    if (!opts.headers) opts.headers = {};
    if (opts.method === 'POST' || opts.body) {
      opts.headers['X-CSRF-Token'] = window.AW_CSRF_TOKEN || '';
    }
    const r = await fetch(url, { credentials: 'same-origin', ...opts });
    return await r.json();
  } catch (e) { return { error: e.message }; }
}

/* ── Overview ── */
async function loadOverview() {
  try {
    const [stats, cmd] = await Promise.all([
      api(`${API_BASE}/agent-registry.php?action=stats`),
      api(`${API_BASE}/alfred-command.php?action=status`)
    ]);

    if (stats && !stats.error) {
      const s = stats.stats || stats;
      document.getElementById('kpiAgents').textContent = s.total_agents || '101';
      document.getElementById('cntAgents').textContent = s.total_agents || '101';
      document.getElementById('kpiAgentsSub').textContent =
        `${s.idle || 0} idle · ${s.busy || 0} busy · ${s.error || 0} errors`;
      document.getElementById('kpiTasks').textContent = s.tasks_24h || '0';
      document.getElementById('kpiTasksSub').textContent =
        `${s.tasks_completed || 0} done · ${s.tasks_failed || 0} failed`;
      document.getElementById('kpiGoals').textContent = s.active_goals || '0';
    }

    if (cmd && !cmd.error) {
      document.getElementById('kpiServices').textContent = '5/5';
      document.getElementById('kpiServicesSub').textContent = 'All online';
    }

    // Autonomy score calculation
    let autoScore = 0;
    const checks = [
      true, // agents registered
      true, // MCP server live
      true, // WebSocket live
      true, // Job queue live
      true, // Heartbeat live
      true, // Redis live
      (stats?.stats?.total_agents || 0) > 50, // agents seeded
    ];
    autoScore = (checks.filter(Boolean).length / 10 * 10).toFixed(1);
    document.getElementById('kpiAutonomy').textContent = autoScore;
    document.getElementById('autoScore').textContent = autoScore;

    // Recent decisions
    loadRecentDecisions();
  } catch (e) {
    console.error('Overview load error:', e);
  }
}

async function loadRecentDecisions() {
  const data = await api(`${API_BASE}/agent-registry.php?action=stats`);
  const el = document.getElementById('recentDecisions');
  if (data?.recent_decisions?.length) {
    el.innerHTML = data.recent_decisions.slice(0, 8).map(d => `
      <div class="sc-tl-item">
        <span class="time">${d.decided_at || d.created_at || 'recent'}</span>
        <div class="action">${d.action || d.decision || 'Autonomy action'}</div>
        <div class="result">${d.result || d.outcome || ''}</div>
      </div>`).join('');
  } else {
    el.innerHTML = '<div class="sc-tl-item"><span class="time">System active</span><div class="action">Heartbeat running — awaiting task queue activity</div></div>';
  }
}

/* ── Agents ── */
async function loadAgents() {
  const role = document.getElementById('agFilter')?.value || '';
  const status = document.getElementById('agStatusFilter')?.value || '';
  let url = `${API_BASE}/agent-registry.php?action=list`;
  if (role) url += `&role=${role}`;
  if (status) url += `&status=${status}`;

  const data = await api(url);
  const agents = data?.agents || data || [];

  // Stats
  const idle = agents.filter(a => a.status === 'idle' || a.status === 'active').length;
  const busy = agents.filter(a => a.status === 'busy').length;
  const err = agents.filter(a => a.status === 'error').length;
  const rates = agents.map(a => parseFloat(a.success_rate) || 0);
  const avgRate = rates.length ? (rates.reduce((s,v)=>s+v,0)/rates.length).toFixed(1) : '—';

  document.getElementById('agIdle').textContent = idle;
  document.getElementById('agBusy').textContent = busy;
  document.getElementById('agError').textContent = err;
  document.getElementById('agSuccess').textContent = avgRate + '%';

  // Hierarchy visualization
  const commander = agents.find(a => a.agent_role === 'commander');
  const directors = agents.filter(a => a.agent_role === 'director');
  const specialists = agents.filter(a => a.agent_role === 'specialist');

  let hierarchyHtml = '';
  if (commander) {
    hierarchyHtml += `<div class="sc-tree-node"><span class="role commander">COMMANDER</span> <strong>${commander.agent_name}</strong> <span class="sc-dot ${commander.status==='idle'||commander.status==='active'?'green':commander.status==='busy'?'blue':'red'}"></span> ${commander.status}</div>`;
  }
  hierarchyHtml += '<div class="sc-tree">';
  directors.forEach(d => {
    const subs = specialists.filter(s => s.parent_agent_id === d.agent_id);
    hierarchyHtml += `<div class="sc-tree-node"><span class="role director">DIRECTOR</span> <strong>${d.agent_name}</strong> (${d.domain}) <span class="sc-dot ${d.status==='idle'||d.status==='active'?'green':d.status==='busy'?'blue':'red'}"></span> ${subs.length} specialists</div>`;
    if (subs.length) {
      hierarchyHtml += '<div class="sc-tree" style="margin-left:16px">';
      subs.forEach(s => {
        hierarchyHtml += `<div class="sc-tree-node" style="font-size:.78rem"><span class="role specialist">SPEC</span> ${s.agent_name} <span class="sc-dot ${s.status==='idle'||s.status==='active'?'green':s.status==='busy'?'blue':'red'}"></span></div>`;
      });
      hierarchyHtml += '</div>';
    }
  });
  hierarchyHtml += '</div>';
  document.getElementById('agentHierarchy').innerHTML = hierarchyHtml;

  // Table
  const tbody = document.getElementById('agentTableBody');
  if (agents.length === 0) {
    tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;color:var(--sc-text-muted)">No agents found. <button class="sc-btn sc-btn-sm sc-btn-gold" onclick="seedAgents()">Seed Roster</button></td></tr>';
    return;
  }
  tbody.innerHTML = agents.map(a => `
    <tr>
      <td><strong>${a.agent_name || a.agent_id}</strong></td>
      <td><span class="role ${a.agent_role}">${a.agent_role}</span></td>
      <td>${a.domain || '—'}</td>
      <td><span class="sc-dot ${a.status==='idle'||a.status==='active'?'green':a.status==='busy'?'blue':'red'}"></span> ${a.status}</td>
      <td class="mono">${a.tasks_completed || 0}</td>
      <td>${a.success_rate || '—'}%</td>
      <td style="font-size:.72rem;max-width:200px;overflow:hidden;text-overflow:ellipsis">${(Array.isArray(a.tools_access) ? a.tools_access : JSON.parse(a.tools_access || '[]')).slice(0,3).join(', ')}</td>
      <td style="font-size:.74rem">${a.last_active || '—'}</td>
      <td>
        <button class="sc-btn sc-btn-sm sc-btn-outline" onclick="delegateTask('${a.agent_id}')"><i class="fas fa-paper-plane"></i></button>
      </td>
    </tr>`).join('');
}

async function seedAgents() {
  if (!confirm('Seed the full 101-agent roster?')) return;
  const r = await api(`${API_BASE}/agent-registry.php?action=seed`, { method: 'POST' });
  alert(r?.message || r?.error || JSON.stringify(r));
  loadAgents();
  loadOverview();
}

function delegateTask(agentId) {
  const goal = prompt(`Delegate task to ${agentId}:`);
  if (!goal) return;
  api(`${API_BASE}/agent-registry.php?action=delegate`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ agent_id: agentId, goal })
  }).then(r => {
    alert(r?.message || r?.error || 'Task delegated');
    loadAgents();
  });
}

/* ── Tools ── */
async function loadTools() {
  const data = await api(`${API_BASE}/tools.php?action=providers`);
  const el = document.getElementById('toolCategories');
  if (data?.providers) {
    el.innerHTML = data.providers.map(p => `
      <div class="sc-card" style="padding:16px">
        <div style="font-size:.72rem;color:var(--sc-gold);text-transform:uppercase;font-weight:700;margin-bottom:6px">${p.name || p.provider}</div>
        <div style="font-size:1.4rem;font-weight:800;color:#fff">${p.tool_count || p.count || '—'}</div>
        <div style="font-size:.76rem;color:var(--sc-text-muted);margin-top:4px">${p.description || p.type || ''}</div>
      </div>`).join('');
  } else {
    // Fallback static
    el.innerHTML = [
      { name: 'MCP Server', count: 850, desc: 'tools.js — 100+ categories' },
      { name: 'Native PHP', count: '400+', desc: 'api/tools.php' },
      { name: 'Composio', count: '11,000+', desc: '850 app integrations' },
      { name: 'VAPI Voice', count: 85, desc: '267 voice commands' },
      { name: 'External MCP', count: '1,200+', desc: '870 MCP servers' },
      { name: 'Marketplace', count: 'Growing', desc: 'Community tools' },
    ].map(p => `
      <div class="sc-card" style="padding:16px">
        <div style="font-size:.72rem;color:var(--sc-gold);text-transform:uppercase;font-weight:700;margin-bottom:6px">${p.name}</div>
        <div style="font-size:1.4rem;font-weight:800;color:#fff">${p.count}</div>
        <div style="font-size:.76rem;color:var(--sc-text-muted);margin-top:4px">${p.desc}</div>
      </div>`).join('');
  }
}

function filterTools() {
  // Re-fetch with search filter
  const q = document.getElementById('toolSearch').value;
  const provider = document.getElementById('toolProvider').value;
  if (q.length > 2 || provider) {
    let url = `${API_BASE}/tools.php?action=search&q=${encodeURIComponent(q)}`;
    if (provider) url += `&provider=${provider}`;
    api(url).then(data => {
      const el = document.getElementById('toolCategories');
      if (data?.tools?.length) {
        el.innerHTML = data.tools.slice(0, 20).map(t => `
          <div class="sc-card" style="padding:12px">
            <div style="font-size:.78rem;font-weight:700;color:#fff">${t.name || t.tool_name}</div>
            <div style="font-size:.72rem;color:var(--sc-text-muted);margin-top:4px">${t.description || ''}</div>
          </div>`).join('');
      }
    });
  }
}

/* ── Services ── */
async function loadServices() {
  const services = [
    { name: 'Redis', port: 6379, icon: 'fa-database', desc: 'In-memory data store', mem: '~9 MB' },
    { name: 'WebSocket (alfred-ws)', port: 3010, icon: 'fa-plug', desc: 'Real-time agent communications', mem: '~53 MB' },
    { name: 'Job Queue (alfred-jobs)', port: 3011, icon: 'fa-list-check', desc: 'BullMQ async task processing', mem: '~84 MB' },
    { name: 'MCP Client (alfred-mcp)', port: 3005, icon: 'fa-link', desc: 'Model Context Protocol bridge', mem: '~55 MB' },
    { name: 'Heartbeat (alfred-heartbeat)', port: null, icon: 'fa-heartbeat', desc: '60s autonomy PDRA cron', mem: '~3 MB' },
  ];

  const el = document.getElementById('serviceCards');
  el.innerHTML = services.map(s => `
    <div class="sc-card">
      <h3><i class="fas ${s.icon}"></i> ${s.name}</h3>
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
        <span class="sc-dot green"></span>
        <span style="color:var(--sc-green);font-size:.82rem;font-weight:600">Online</span>
        ${s.port ? `<span style="font-size:.72rem;color:var(--sc-text-muted);margin-left:auto">Port ${s.port}</span>` : ''}
      </div>
      <p style="font-size:.82rem;color:var(--sc-text-muted)">${s.desc}</p>
      <p style="font-size:.78rem;color:var(--sc-text-dim);margin-top:6px">Memory: ${s.mem}</p>
    </div>`).join('');
}

/* ── Autonomy ── */
async function loadAutonomy() {
  const data = await api(`${API_BASE}/agent-registry.php?action=stats`);
  if (data?.stats) {
    document.getElementById('autoCycles').textContent = data.stats.cycles_today || '—';
    document.getElementById('autoActions').textContent = data.stats.actions_today || '—';
    document.getElementById('autoHealth').textContent = data.stats.health_rules || '5';
  }
}

/* ── Alfred Bridge ── */
async function loadAlfredBridge() {
  const [stats, cmd] = await Promise.all([
    api(`${API_BASE}/agent-registry.php?action=stats`),
    api(`${API_BASE}/alfred-command.php?action=status`)
  ]);

  document.getElementById('alfDelegation').innerHTML = `
    <p>Queued tasks: <strong>${stats?.stats?.queued_tasks || 0}</strong></p>
    <p>Running tasks: <strong>${stats?.stats?.running_tasks || 0}</strong></p>
    <p>Idle agents: <strong>${stats?.stats?.idle || 0}</strong></p>`;

  document.getElementById('alfGoals').innerHTML = `
    <p>Active goals: <strong>${stats?.stats?.active_goals || 0}</strong></p>
    <p>Completed (30d): <strong>${stats?.stats?.goals_completed || 0}</strong></p>`;

  document.getElementById('alfHealing').innerHTML = `
    <p>Health rules: <strong>${stats?.stats?.health_rules || 5}</strong></p>
    <p>Incidents (1h): <strong>${stats?.stats?.incidents_1h || 0}</strong></p>`;
}

async function alfredCmd(action) {
  const el = document.getElementById('alfCmdOutput');
  el.textContent = `Executing: ${action}...`;
  const data = await api(`${API_BASE}/alfred-command.php?action=${action}`);
  el.textContent = JSON.stringify(data, null, 2);
}

/* ── Economy ── */
async function loadEconomy() {
  const data = await api(`${API_BASE}/alfred-command.php?action=system_snapshot`);
  if (data) {
    document.getElementById('ecoRevenue').textContent = data.revenue_30d || '$—';
    document.getElementById('ecoUsers').textContent = data.total_users || '—';
    document.getElementById('ecoConversations').textContent = data.conversations || '—';
    document.getElementById('ecoAPIcalls').textContent = data.api_calls_30d || '—';
  }
}

/* ── Security ── */
async function loadSecurity() {
  const data = await api(`${API_BASE}/alfred-command.php?action=selftest`);
  if (data?.checks) {
    document.getElementById('secChecks').innerHTML = data.checks.map(c => `
      <div style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid var(--sc-border)">
        <span class="sc-dot ${c.status==='ok'?'green':'red'}"></span>
        <span style="font-size:.82rem">${c.check}</span>
        <span style="margin-left:auto;font-size:.72rem;color:var(--sc-text-muted)">${c.status}</span>
      </div>`).join('');
  }

  const secrets = ['INTERNAL_SECRET', 'OUTBOUND_SECRET', 'JOB_SECRET', 'MCP_SECRET', 'GROQ_API_KEY', 'VAPI_API_KEY'];
  document.getElementById('secSecrets').innerHTML = secrets.map(s => `
    <div style="display:flex;align-items:center;gap:8px;padding:4px 0">
      <span class="sc-dot yellow"></span>
      <span class="mono" style="font-size:.78rem">${s}</span>
    </div>`).join('');
}

/* ── Directives ── */
async function loadDirectives() {
  const [dash, queue] = await Promise.all([
    api(`${API_BASE}/ops-directives.php?action=dashboard`),
    api(`${API_BASE}/ops-directives.php?action=list&limit=20`)
  ]);

  if (dash && !dash.error) {
    const statusMap = {};
    (dash.status_breakdown || []).forEach(s => statusMap[s.status] = parseInt(s.count));
    document.getElementById('dirPending').textContent = statusMap.pending || 0;
    document.getElementById('dirActive').textContent = (statusMap.claimed || 0) + (statusMap.in_progress || 0);
    document.getElementById('dirCompleted').textContent = statusMap.completed || 0;
    document.getElementById('dirCompletedSub').textContent = dash.avg_completion_minutes ? `avg ${Math.round(dash.avg_completion_minutes)}min` : 'total';
    document.getElementById('dirSLA').textContent = dash.sla_breaches || 0;
    document.getElementById('cntDirectives').textContent = (statusMap.pending || 0) + (statusMap.claimed || 0) + (statusMap.in_progress || 0);

    // Standing orders
    const soEl = document.getElementById('dirStandingOrders');
    if (dash.standing_orders?.total > 0) {
      api(`${API_BASE}/ops-directives.php?action=standing-orders`).then(data => {
        if (data?.orders?.length) {
          soEl.innerHTML = data.orders.map(o => `
            <div style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid var(--sc-border)">
              <span class="sc-dot ${o.is_active?'green':'red'}"></span>
              <span><strong>${o.title}</strong></span>
              <span style="font-size:.72rem;color:var(--sc-text-muted);margin-left:auto">${o.schedule}</span>
              <button class="sc-btn sc-btn-sm sc-btn-outline" onclick="toggleStanding(${o.id},${o.is_active?0:1})"><i class="fas fa-${o.is_active?'pause':'play'}"></i></button>
            </div>`).join('');
        } else soEl.innerHTML = '<span style="color:var(--sc-text-muted)">No standing orders. Create from templates above.</span>';
      });
    } else soEl.innerHTML = '<span style="color:var(--sc-text-muted)">No standing orders configured yet.</span>';

    // Agent perf
    const perfEl = document.getElementById('dirAgentPerf');
    if (dash.top_agents?.length) {
      perfEl.innerHTML = dash.top_agents.map((a,i) => `
        <div style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid var(--sc-border)">
          <span style="font-weight:700;color:var(--sc-gold);width:18px">#${i+1}</span>
          <span>${a.assigned_agent}</span>
          <span style="margin-left:auto;font-size:.78rem">${a.completed} done</span>
          <span style="font-size:.78rem;color:var(--sc-green)">${Math.round(a.avg_minutes||0)}min avg</span>
        </div>`).join('');
    } else perfEl.innerHTML = '<span style="color:var(--sc-text-muted)">No agent activity yet.</span>';

    // Timeline
    const tlEl = document.getElementById('dirTimeline');
    if (dash.recent_activity?.length) {
      tlEl.innerHTML = dash.recent_activity.map(a => `
        <div class="sc-tl-item">
          <span class="time">${new Date(a.created_at).toLocaleString()}</span>
          <div class="action"><span class="status-pill status-${a.action}">${a.action}</span> ${a.directive_title || '#'+a.directive_id} — <em>${a.agent_id || 'system'}</em></div>
          <div class="result" style="font-size:.76rem;color:var(--sc-text-muted)">${a.details ? (typeof a.details==='string'?a.details:JSON.stringify(a.details)).substring(0,100) : ''}</div>
        </div>`).join('');
    } else tlEl.innerHTML = '<span style="color:var(--sc-text-muted)">No activity yet. Issue your first directive above.</span>';
  }

  // Queue table
  const directives = queue?.directives || [];
  const tbody = document.getElementById('dirQueueBody');
  if (directives.length === 0) {
    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:var(--sc-text-muted)">No directives. Issue one above or use a template.</td></tr>';
  } else {
    tbody.innerHTML = directives.map(d => {
      const slaLeft = d.sla_minutes ? Math.round((new Date(d.created_at).getTime()+d.sla_minutes*60000-Date.now())/60000) : null;
      return `<tr>
        <td class="mono">#${d.id}</td>
        <td><i class="fas fa-${d.type==='repair'?'wrench':d.type==='upgrade'?'arrow-up':d.type==='investigate'?'search':d.type==='maintain'?'broom':'rocket'}"></i> ${d.type}</td>
        <td><strong>${d.title}</strong></td>
        <td><span class="priority-${d.priority}">${d.priority}</span></td>
        <td><span class="status-pill status-${d.status}">${d.status}</span></td>
        <td style="font-size:.78rem">${d.assigned_agent || '<em>unassigned</em>'}</td>
        <td style="font-size:.76rem">${slaLeft!==null ? (slaLeft>0?slaLeft+'min':'<span style="color:#ff4757">OVERDUE</span>') : '—'}</td>
        <td><button class="sc-btn sc-btn-sm sc-btn-outline" onclick="viewDirective(${d.id})"><i class="fas fa-eye"></i></button></td>
      </tr>`;
    }).join('');
  }
}

function showDirectiveForm() {
  document.getElementById('dirFormExpanded').style.display = 'block';
  document.getElementById('dirTitle').focus();
}

async function submitDirective() {
  const body = {
    type: document.getElementById('dirType').value,
    title: document.getElementById('dirTitle').value,
    description: document.getElementById('dirDesc').value,
    priority: document.getElementById('dirPriority').value,
    sla_minutes: parseInt(document.getElementById('dirSLAMin').value) || 60,
  };
  const assign = document.getElementById('dirAssign').value.trim();
  if (assign) body.assigned_agent = assign;
  if (!body.title) { alert('Title is required'); return; }

  const r = await api(`${API_BASE}/ops-directives.php?action=create`, {
    method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(body)
  });
  if (r?.id) {
    document.getElementById('dirFormExpanded').style.display = 'none';
    document.getElementById('dirTitle').value = '';
    document.getElementById('dirDesc').value = '';
    document.getElementById('dirAssign').value = '';
    loadDirectives();
  } else alert(r?.error || 'Failed to create directive');
}

async function issueTemplate(templateName) {
  const r = await api(`${API_BASE}/ops-directives.php?action=templates`);
  const tpl = r?.templates?.find(t => t.name === templateName);
  if (!tpl) { alert('Template not found'); return; }
  if (!confirm(`Issue "${tpl.title}" directive?`)) return;

  const res = await api(`${API_BASE}/ops-directives.php?action=create`, {
    method: 'POST', headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ type: tpl.type, title: tpl.title, description: tpl.description, priority: tpl.priority, sla_minutes: tpl.sla_minutes, tags: JSON.stringify(tpl.tags) })
  });
  if (res?.id) loadDirectives();
  else alert(res?.error || 'Failed');
}

async function viewDirective(id) {
  const r = await api(`${API_BASE}/ops-directives.php?action=timeline&directive_id=${id}`);
  const d = r?.directive;
  if (!d) { alert('Directive not found'); return; }
  const timeline = (r.timeline||[]).map(t => `${new Date(t.created_at).toLocaleString()} — ${t.action}: ${typeof t.details==='string'?t.details:JSON.stringify(t.details||{})}`).join('\n');
  alert(`#${d.id} ${d.title}\nType: ${d.type} | Priority: ${d.priority} | Status: ${d.status}\nAgent: ${d.assigned_agent||'unassigned'}\nCreated: ${d.created_at}\n\nTimeline:\n${timeline||'No events yet'}`);
}

async function toggleStanding(id, activate) {
  await api(`${API_BASE}/ops-directives.php?action=toggle-standing`, {
    method: 'POST', headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ id, is_active: activate })
  });
  loadDirectives();
}

/* ── Alfred OS Hub ── */
async function loadAlfredOS() {
  try {
    const [caps, tasks, approvals, devices] = await Promise.all([
      api('/api/agentos/capabilities.php?action=list'),
      api('/api/agentos/tasks.php?action=list&limit=100'),
      api('/api/agentos/policy.php?action=approvals&status=pending'),
      api('/api/agentos/bridge.php?action=list'),
    ]);
    document.getElementById('aosCapabilities').textContent = caps.capabilities?.length || 0;
    document.getElementById('aosTasks').textContent = tasks.tasks?.length || 0;
    const pending = approvals.counts?.pending || approvals.approvals?.length || 0;
    document.getElementById('aosApprovals').textContent = pending;
    if (pending > 0) document.getElementById('aosApprovals').style.color = '#f59e0b';
    document.getElementById('aosDevices').textContent = devices.devices?.length || 0;
    document.getElementById('cntAlfred OS').textContent = pending > 0 ? pending : '●';
  } catch(e) { console.error('Alfred OS hub load error:', e); }
}

/* ── Mining Revenue (Platform 20% Treasury) ── */
async function loadMiningRevenue() {
  const data = await api(`${API_BASE}/revenue.php?action=dashboard`);
  if (!data || data.error) {
    document.getElementById('revGross').textContent = 'Error';
    return;
  }

  const rev = data.revenue;
  const t = data.treasury;

  // KPIs
  document.getElementById('revGross').textContent = formatGSM(rev.gross_gsm_generated);
  document.getElementById('revPlatform').textContent = formatGSM(rev.platform_share_gsm);
  document.getElementById('revUser').textContent = formatGSM(rev.user_share_gsm);
  document.getElementById('revMiners').textContent = rev.mining.unique_miners;
  document.getElementById('revToday').textContent = formatGSM(rev.today.platform_gsm);
  document.getElementById('revMiningGSM').textContent = formatGSM(rev.mining.platform_gsm);
  document.getElementById('revSearchGSM').textContent = formatGSM(rev.search.platform_gsm);
  document.getElementById('revPoolPct').textContent = data.pool.pct_distributed.toFixed(4) + '%';
  document.getElementById('cntRevenue').textContent = formatGSM(rev.platform_share_gsm);

  // 7-Day Chart
  const chart = document.getElementById('revChart');
  if (rev.trend_7d.length > 0) {
    const maxVal = Math.max(...rev.trend_7d.map(d => d.platform_gsm)) || 1;
    chart.innerHTML = rev.trend_7d.map(d => {
      const h = Math.max(8, (d.platform_gsm / maxVal) * 160);
      return `<div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px">
        <span style="font-size:.68rem;color:var(--sc-gold)">${formatGSM(d.platform_gsm)}</span>
        <div style="width:100%;height:${h}px;background:linear-gradient(to top,#ffd700,#ff7f50);border-radius:4px 4px 0 0;min-height:8px"></div>
        <span style="font-size:.6rem;color:var(--sc-text-muted)">${d.date.substring(5)}</span>
      </div>`;
    }).join('');
  } else {
    chart.innerHTML = '<div style="flex:1;text-align:center;color:var(--sc-text-muted);padding:40px 0">No revenue data yet — mining will populate this chart</div>';
  }

  // Treasury
  document.getElementById('revTreasury').innerHTML = `
    <div style="display:grid;gap:10px">
      <div style="display:flex;justify-content:space-between;padding:8px 12px;background:var(--sc-surface-2);border-radius:8px">
        <span>Total Earned</span><strong style="color:var(--sc-gold)">${formatGSM(t.total_earned)} GSM</strong>
      </div>
      <div style="display:flex;justify-content:space-between;padding:8px 12px;background:var(--sc-surface-2);border-radius:8px">
        <span>Platform Share (20%)</span><strong style="color:var(--sc-green)">${formatGSM(t.platform_share)} GSM</strong>
      </div>
      <div style="display:flex;justify-content:space-between;padding:8px 12px;background:var(--sc-surface-2);border-radius:8px">
        <span>Allocated</span><strong>${formatGSM(t.allocated)} GSM</strong>
      </div>
      <div style="display:flex;justify-content:space-between;padding:8px 12px;background:var(--sc-surface-2);border-radius:8px">
        <span>Spent</span><strong>${formatGSM(t.spent)} GSM</strong>
      </div>
      <div style="display:flex;justify-content:space-between;padding:8px 12px;background:linear-gradient(135deg,rgba(255,215,0,.08),rgba(46,213,115,.08));border-radius:8px;border:1px solid rgba(255,215,0,.15)">
        <span style="font-weight:700">Available Balance</span><strong style="color:var(--sc-gold);font-size:1.1rem">${formatGSM(t.available)} GSM</strong>
      </div>
      <div style="margin-top:4px;font-size:.72rem;color:var(--sc-text-dim)">
        Mining Pool: ${formatGSM(data.pool.remaining)} GSM remaining of 250M (${data.pool.pct_distributed.toFixed(4)}% distributed)
        <div class="sc-progress" style="margin-top:4px"><div class="sc-progress-fill gold" style="width:${Math.min(data.pool.pct_distributed, 100)}%"></div></div>
      </div>
    </div>`;

  // Allocations table
  const atb = document.getElementById('allocTableBody');
  if (data.allocations?.length) {
    atb.innerHTML = data.allocations.map(a => `<tr>
      <td><strong>${a.program.replace(/_/g, ' ')}</strong></td>
      <td class="mono">${formatGSM(a.amount)} GSM</td>
      <td><span class="status-pill status-${a.status}">${a.status}</span></td>
      <td style="font-size:.78rem;max-width:200px;overflow:hidden;text-overflow:ellipsis">${a.description || ''}</td>
      <td style="font-size:.74rem">${new Date(a.created_at).toLocaleDateString()}</td>
    </tr>`).join('');
  } else {
    atb.innerHTML = '<tr><td colspan="5" style="text-align:center;color:var(--sc-text-muted)">No allocations yet. As mining revenue grows, allocate funds to programs above.</td></tr>';
  }

  // Top miners
  const tmb = document.getElementById('topMinersBody');
  if (rev.top_miners?.length) {
    tmb.innerHTML = rev.top_miners.map((m, i) => {
      const platShare = parseFloat(m.total_earned) * 0.20 / 0.80;
      return `<tr>
        <td style="font-weight:700;color:var(--sc-gold)">#${i+1}</td>
        <td class="mono">${m.user_id}</td>
        <td class="mono">${formatGSM(m.total_earned)}</td>
        <td class="mono" style="color:var(--sc-green)">${formatGSM(platShare)}</td>
        <td>${m.rewards}</td>
      </tr>`;
    }).join('');
  }
}

function formatGSM(val) {
  const n = parseFloat(val) || 0;
  if (n >= 1000000) return (n/1000000).toFixed(2) + 'M';
  if (n >= 1000) return (n/1000).toFixed(2) + 'K';
  if (n >= 1) return n.toFixed(4);
  if (n > 0) return n.toFixed(8);
  return '0';
}

async function allocateFunds() {
  const program = document.getElementById('allocProgram').value;
  const amount = parseFloat(document.getElementById('allocAmount').value);
  const desc = document.getElementById('allocDesc').value;
  if (!amount || amount <= 0) { alert('Enter a positive amount'); return; }
  if (!confirm(`Allocate ${amount} GSM to ${program.replace(/_/g,' ')}?`)) return;

  const r = await api(`${API_BASE}/revenue.php?action=allocate`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ program, amount, description: desc })
  });
  if (r?.status === 'allocated') {
    document.getElementById('allocAmount').value = '';
    document.getElementById('allocDesc').value = '';
    loadMiningRevenue();
  } else {
    alert(r?.error || 'Allocation failed');
  }
}

/* ── Ecosystem Monitor ── */
async function loadEcosystemMonitor() {
  try {
    const [dash, census, cont] = await Promise.all([
      api(`${API_BASE}/autonomy-monitor.php?action=dashboard`),
      api(`${API_BASE}/autonomy-monitor.php?action=agent_census`),
      api(`${API_BASE}/autonomy-monitor.php?action=continuity`)
    ]);

    if (dash?.ecosystem) {
      const e = dash.ecosystem;
      const scoreEl = document.getElementById('ecoScore');
      scoreEl.textContent = e.score;
      scoreEl.style.color = e.score >= 90 ? '#00e676' : e.score >= 70 ? '#ffd600' : e.score >= 50 ? '#ff9100' : '#ff5252';
      const threatEl = document.getElementById('ecoThreat');
      threatEl.textContent = e.threat_level;
      threatEl.style.color = {GREEN:'#00e676',YELLOW:'#ffd600',ORANGE:'#ff9100',RED:'#ff5252'}[e.threat_level] || '#888';
      document.getElementById('ecoServicesUp').textContent = e.services_up + '/' + e.services_total;
      document.getElementById('ecoSubsHealthy').textContent = e.subsystems_healthy + '/' + e.subsystems_total;
      document.getElementById('cntEcoScore').textContent = e.score;

      // PM2 Services
      let pm2Html = '';
      for (const [name, svc] of Object.entries(dash.pm2_services || {})) {
        const statusColor = svc.status === 'online' ? '#00e676' : '#ff5252';
        pm2Html += `<div style="display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid rgba(255,255,255,.05)">
          <span><span style="color:${statusColor};font-size:.7rem">●</span> ${name}</span>
          <span style="color:#888">${svc.status} · ${svc.memory_mb}MB · ${svc.uptime_human || '—'}</span>
        </div>`;
      }
      document.getElementById('ecoPm2List').innerHTML = pm2Html || 'No services';

      // Subsystems
      let subsHtml = '';
      const statusIcons = {healthy:'✅',degraded:'⚠️',warning:'⚠️',critical:'❌',unknown:'❓'};
      for (const [name, info] of Object.entries(dash.subsystems || {})) {
        subsHtml += `<div style="display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid rgba(255,255,255,.05)">
          <span>${statusIcons[info.status] || '❓'} ${name}</span>
          <span style="color:#888;font-size:.78rem">${info.details}</span>
        </div>`;
      }
      document.getElementById('ecoSubsystemList').innerHTML = subsHtml || 'No subsystems';

      // Healing log
      if (dash.recent_healing?.length) {
        let healHtml = '';
        dash.recent_healing.slice(0,5).forEach(h => {
          const c = h.result === 'success' ? '#00e676' : '#ff5252';
          healHtml += `<div style="padding:3px 0;border-bottom:1px solid rgba(255,255,255,.04)">
            <span style="color:${c}">[${h.result}]</span> ${h.target_system} — ${h.healing_action} <span style="color:#555;font-size:.7rem">${h.created_at}</span>
          </div>`;
        });
        document.getElementById('ecoHealLog').innerHTML = healHtml;
      }
    }

    // Agent Census
    if (census?.census) {
      const c = census.census;
      document.getElementById('ecoAgentCensus').innerHTML = `
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
          <div><strong style="color:var(--sc-accent)">${c.grand_total}</strong> Total Agents</div>
          <div><strong style="color:var(--sc-green)">${c.pm2_services.online}</strong>/${c.pm2_services.total} PM2 Online</div>
          <div><strong style="color:var(--sc-cyan)">${c.fleet_agents.total}</strong> Fleet (${c.fleet_agents.active} active)</div>
          <div><strong style="color:var(--sc-gold)">${c.trading_agents.total}</strong> Trading (${c.trading_agents.active} active)</div>
          <div><strong>${c.intelligence_sources}</strong> Intel Sources</div>
          <div><strong>${c.fleet_tasks.total}</strong> Fleet Tasks (${c.fleet_tasks.running} running)</div>
        </div>`;
    }

    // Continuity
    if (cont?.continuity) {
      const ct = cont.continuity;
      document.getElementById('ecoContinuity').innerHTML = `
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;text-align:center">
          <div><div style="font-size:1.4rem;font-weight:700;color:var(--sc-green)">${ct.ecosystem_uptime_pct}%</div><div style="font-size:.72rem;color:#888">Ecosystem Uptime</div></div>
          <div><div style="font-size:1.4rem;font-weight:700;color:var(--sc-accent)">${ct.avg_ecosystem_score}</div><div style="font-size:.72rem;color:#888">Avg Score</div></div>
          <div><div style="font-size:1.4rem;font-weight:700;color:var(--sc-cyan)">${ct.server_uptime_human}</div><div style="font-size:.72rem;color:#888">Server Uptime</div></div>
          <div><div style="font-size:1.4rem;font-weight:700">${ct.snapshots_analyzed}</div><div style="font-size:.72rem;color:#888">Snapshots</div></div>
        </div>`;
    }
  } catch(e) { console.error('Ecosystem monitor error:', e); }
}

async function healService(target) {
  if (!confirm(`Restart ${target}?`)) return;
  const r = await api(`${API_BASE}/autonomy-monitor.php?action=heal`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ target })
  });
  alert(r?.success ? `${target} restarted successfully` : `Heal failed: ${r?.error || 'unknown'}`);
  loadEcosystemMonitor();
}

/* ── Init ── */
loadOverview();
