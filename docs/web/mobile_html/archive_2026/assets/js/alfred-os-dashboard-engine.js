const os = window.agentOS;
let allCaps = [];

// ── Tab Navigation ──────────────────────────────────────
function showPanel(id) {
    document.querySelectorAll('.os-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.os-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('panel-' + id).classList.add('active');
    event.currentTarget.classList.add('active');
    // Load data for panel
    const loaders = {
        overview: loadOverview,
        capabilities: loadCapabilities,
        skills: loadSkills,
        tasks: () => loadTasks(),
        memory: loadMemory,
        world: loadWorld,
        policy: loadPolicies,
        simulation: loadSimulations,
        audit: loadAudit,
        approvals: () => loadApprovals(),
        bridge: loadDevices,
    };
    if (loaders[id]) loaders[id]();
}

// ── Helpers ─────────────────────────────────────────────
function esc(s) { return GDS.esc(s); }
function ago(ts) {
    if (!ts) return '-';
    const s = Math.floor((Date.now() - new Date(ts).getTime()) / 1000);
    if (s < 60) return s + 's ago';
    if (s < 3600) return Math.floor(s/60) + 'm ago';
    if (s < 86400) return Math.floor(s/3600) + 'h ago';
    return Math.floor(s/86400) + 'd ago';
}
function riskBadge(r) { return `<span class="badge-pill ${r||'low'}">${esc(r||'low')}</span>`; }
function statusBadge(s) { return `<span class="badge-pill ${s||''}">${esc(s||'-')}</span>`; }

// ── Overview ────────────────────────────────────────────
async function loadOverview() {
    try {
        const [caps, skills, tasks, mem, policies, audit, sims, approvals] = await Promise.all([
            os.capabilities(), os.skills(), os.tasks(null, 10),
            os.memoryStats(), os.policies(), os.auditLog({limit:10}),
            os.simulations(5), os.approvals('pending')
        ]);
        document.getElementById('stat-caps').textContent = caps.capabilities?.length || 0;
        document.getElementById('cap-count').textContent = caps.capabilities?.length || 0;
        document.getElementById('stat-skills').textContent = skills.skills?.length || 0;
        document.getElementById('stat-tasks').textContent = tasks.tasks?.length || 0;
        document.getElementById('stat-memories').textContent = mem.stats?.total || 0;
        document.getElementById('stat-policies').textContent = policies.policies?.length || 0;
        document.getElementById('stat-sims').textContent = sims.simulations?.length || 0;

        // Update approval badge
        const pending = approvals.counts?.pending || 0;
        document.getElementById('approval-count').textContent = pending || '-';
        document.getElementById('approval-count').style.display = pending ? 'inline' : 'none';

        // Recent audit
        const tbody = document.getElementById('recent-audit');
        tbody.innerHTML = (audit.entries || []).map(e => `<tr>
            <td class="mono">${ago(e.created_at)}</td>
            <td>${esc(e.agent_id)}</td>
            <td>${esc(e.action_type)}</td>
            <td class="mono">${esc(e.capability_id||'-')}</td>
            <td>${statusBadge(e.status)}</td>
            <td>${riskBadge(e.risk_level)}</td>
        </tr>`).join('') || '<tr><td colspan="6" class="empty-state">No recent activity</td></tr>';
    } catch (err) {
        console.error('Overview load failed:', err);
    }
}

// ── Capabilities ────────────────────────────────────────
async function loadCapabilities() {
    const data = await os.capabilities();
    allCaps = data.capabilities || [];
    renderCaps(allCaps);
}
function renderCaps(caps) {
    document.getElementById('caps-table').innerHTML = caps.map(c => `<tr>
        <td class="mono">${esc(c.capability_id)}</td>
        <td>${esc(c.display_name)}</td>
        <td>${esc(c.category)}</td>
        <td>${esc(c.capability_type||'-')}</td>
        <td>${riskBadge(c.risk_level)}</td>
        <td>${c.requires_simulation ? '✓' : '-'}</td>
        <td>${c.requires_approval ? '✓' : '-'}</td>
    </tr>`).join('');
}
function filterCaps(q) {
    const t = q.toLowerCase();
    renderCaps(allCaps.filter(c => 
        (c.capability_id+c.display_name+c.category).toLowerCase().includes(t)));
}

// ── Skills ──────────────────────────────────────────────
async function loadSkills() {
    const data = await os.skills();
    document.getElementById('skills-table').innerHTML = (data.skills||[]).map(s => `<tr>
        <td><strong>${esc(s.display_name)}</strong><br><span style="color:var(--os-dim);font-size:11px">${esc(s.skill_id)}</span></td>
        <td>${esc(s.category)}</td>
        <td>${riskBadge(s.risk_level)}</td>
        <td>${s.requires_approval ? '✓' : '-'}</td>
        <td class="mono">${esc(s.version)}</td>
        <td>${esc(s.author)}</td>
        <td><button class="os-btn sm" onclick="executeSkillPrompt('${esc(s.skill_id)}')"><i class="fas fa-play"></i></button></td>
    </tr>`).join('') || '<tr><td colspan="7" class="empty-state">No skills created yet</td></tr>';
}

// ── Tasks ───────────────────────────────────────────────
async function loadTasks(status) {
    const data = await os.tasks(status, 50);
    document.getElementById('tasks-table').innerHTML = (data.tasks||[]).map(t => {
        const isActive = ['running','ready','planning','waiting_approval'].includes(t.status);
        const isComplete = t.status === 'completed';
        let actions = `<button class="os-btn sm" onclick="replayTaskFromId('${esc(t.task_id)}')" title="Replay"><i class="fas fa-play-circle"></i></button>`;
        if (isActive) actions += ` <button class="os-btn sm danger" onclick="killTask('${esc(t.task_id)}')" title="Kill"><i class="fas fa-skull"></i></button>`;
        if (isComplete) actions += ` <button class="os-btn sm" onclick="rollbackTask('${esc(t.task_id)}')" title="Rollback" style="color:#f59e0b"><i class="fas fa-undo"></i></button>`;
        return `<tr>
            <td class="mono">${esc(t.task_id)}</td>
            <td>${esc(t.goal?.substring(0,80))}${t.goal?.length>80?'...':''}</td>
            <td>${esc(t.agent_id)}</td>
            <td>${t.priority}</td>
            <td>${statusBadge(t.status)}</td>
            <td>${ago(t.created_at)}</td>
            <td>${actions}</td>
        </tr>`;
    }).join('') || '<tr><td colspan="7" class="empty-state">No tasks found</td></tr>';
}

// ── Memory ──────────────────────────────────────────────
async function loadMemory() {
    const data = await os.memoryStats();
    const stats = data.stats || {};
    const types = [
        {key:'episodic',icon:'fa-film',color:'var(--os-cyan)',label:'Episodic'},
        {key:'semantic',icon:'fa-book',color:'var(--os-blue)',label:'Semantic'},
        {key:'procedural',icon:'fa-cogs',color:'var(--os-green)',label:'Procedural'},
        {key:'spatial',icon:'fa-map-marker-alt',color:'var(--os-amber)',label:'Spatial'},
        {key:'relational',icon:'fa-project-diagram',color:'var(--os-purple)',label:'Relational'},
    ];
    document.getElementById('memory-stats-grid').innerHTML = types.map(t => `
        <div class="memory-type">
            <div class="m-icon" style="color:${t.color}"><i class="fas ${t.icon}"></i></div>
            <div class="m-label">${t.label}</div>
            <div class="m-count">${stats[t.key]?.count ?? 0}</div>
        </div>`).join('');
}
async function searchMemories() {
    const q = document.getElementById('mem-search').value;
    if (!q) return;
    const data = await os.searchMemory(q);
    document.getElementById('memory-results').innerHTML = (data.results||[]).map(r => `<tr>
        <td>${statusBadge(r.type)}</td>
        <td>${esc(r.label)}</td>
        <td class="mono">${Number(r.score).toFixed(2)}</td>
        <td>${ago(r.created_at)}</td>
    </tr>`).join('') || '<tr><td colspan="4" class="empty-state">No results</td></tr>';
}
async function consolidateMemory() {
    if (!confirm('Run memory consolidation? This prunes stale memories.')) return;
    const data = await os.consolidateMemory();
    alert('Consolidation complete: ' + JSON.stringify(data.actions));
    loadMemory();
}

// ── World State ─────────────────────────────────────────
async function loadWorld() {
    const [stateData, entityData] = await Promise.all([os.worldState(), os.worldEntities()]);
    const states = stateData.state || [];
    const entities = entityData.entities || [];

    document.getElementById('state-table').innerHTML = states.map(s => `<tr>
        <td class="mono">${esc(s.state_key)}</td>
        <td class="mono">${esc(typeof s.state_value === 'string' ? s.state_value.substring(0,60) : JSON.stringify(s.state_value).substring(0,60))}</td>
        <td>${esc(s.state_type)}</td>
        <td>${s.drift_detected ? '<span class="badge-pill high">DRIFT</span>' : '<span class="badge-pill low">OK</span>'}</td>
        <td>${ago(s.observed_at)}</td>
    </tr>`).join('') || '<tr><td colspan="5" class="empty-state">No state data</td></tr>';

    document.getElementById('entities-table').innerHTML = entities.map(e => `<tr>
        <td class="mono">${esc(e.entity_id)}</td>
        <td>${esc(e.entity_type)}</td>
        <td>${esc(e.display_name)}</td>
        <td>${statusBadge(e.status)}</td>
        <td>${ago(e.last_heartbeat)}</td>
    </tr>`).join('') || '<tr><td colspan="5" class="empty-state">No entities spawned</td></tr>';
}

// ── Policies ────────────────────────────────────────────
async function loadPolicies() {
    const data = await os.policies();
    document.getElementById('policies-table').innerHTML = (data.policies||[]).map(p => `<tr>
        <td><strong>${esc(p.display_name)}</strong><br><span style="color:var(--os-dim);font-size:11px">${esc(p.policy_id)}</span></td>
        <td>${esc(p.scope)}</td>
        <td>${esc(p.scope_target||'*')}</td>
        <td>${p.priority}</td>
        <td>${p.rule_count || '-'}</td>
        <td>${p.enabled ? '<span class="badge-pill online">Active</span>' : '<span class="badge-pill offline">Disabled</span>'}</td>
    </tr>`).join('');
}

// ── Simulations ─────────────────────────────────────────
async function loadSimulations() {
    const data = await os.simulations(50);
    document.getElementById('sims-table').innerHTML = (data.simulations||[]).map(s => `<tr>
        <td class="mono">${esc(s.sim_id)}</td>
        <td>${esc(s.sim_type)}</td>
        <td>${statusBadge(s.outcome)}</td>
        <td>${riskBadge(s.risk_score > 0.6 ? 'high' : s.risk_score > 0.3 ? 'medium' : 'low')}</td>
        <td class="mono">${s.duration_ms ? s.duration_ms + 'ms' : '-'}</td>
        <td>${ago(s.created_at)}</td>
    </tr>`).join('') || '<tr><td colspan="6" class="empty-state">No simulations run</td></tr>';
}

// ── Audit ───────────────────────────────────────────────
async function loadAudit() {
    const [stats, log, anomalies] = await Promise.all([
        os.auditStats(), os.auditLog({limit:50}), os.auditAnomalies()
    ]);
    const s = stats.overall || {};
    document.getElementById('audit-stats-grid').innerHTML = `
        <div class="os-stats">
            <div class="os-stat cyan"><div class="label">Total Events</div><div class="val">${s.total||0}</div></div>
            <div class="os-stat green"><div class="label">Completed</div><div class="val">${s.completed||0}</div></div>
            <div class="os-stat red"><div class="label">Failed</div><div class="val">${s.failed||0}</div></div>
            <div class="os-stat amber"><div class="label">Blocked</div><div class="val">${s.blocked||0}</div></div>
        </div>`;

    // Anomalies
    const aList = anomalies.anomalies || [];
    document.getElementById('anomaly-list').innerHTML = aList.length 
        ? aList.map(a => `<div style="padding:8px 12px;margin-bottom:4px;background:rgba(${a.severity==='critical'?'239,68,68':a.severity==='high'?'245,158,11':'99,102,241'},.1);border-radius:8px;border-left:3px solid ${a.severity==='critical'?'var(--os-red)':a.severity==='high'?'#f59e0b':'var(--os-primary)'}">
            <strong>${esc(a.type)}</strong> <span class="badge-pill ${a.severity}">${a.severity}</span><br>
            <span style="font-size:12px">${esc(a.detail)}</span>
        </div>`).join('')
        : '<span style="color:var(--os-green)"><i class="fas fa-check-circle"></i> No anomalies detected</span>';

    document.getElementById('audit-table').innerHTML = (log.entries||[]).map(e => `<tr>
        <td class="mono">${ago(e.created_at)}</td>
        <td class="mono" style="font-size:10px">${esc((e.trace_id||'').substring(0,12))}...</td>
        <td>${esc(e.agent_id)}</td>
        <td>${esc(e.action_type)}</td>
        <td class="mono">${esc(e.capability_id||'-')}</td>
        <td>${riskBadge(e.risk_level)}</td>
        <td>${statusBadge(e.status)}</td>
        <td class="mono">${e.duration_ms ? e.duration_ms+'ms' : '-'}</td>
    </tr>`).join('') || '<tr><td colspan="8" class="empty-state">No audit entries</td></tr>';
}

// ── Devices ─────────────────────────────────────────────
async function loadDevices() {
    const [devData, fleetData, groupData] = await Promise.all([
        os.devices(), os.fleetStatus(), os.deviceGroups()
    ]);

    // Fleet status overview
    const f = fleetData.fleet || {};
    const bs = f.by_status || {};
    const bt = f.by_type || {};
    document.getElementById('fleet-status').innerHTML = `<div class="os-stats">
        <div class="os-stat green"><div class="label">Online</div><div class="val">${bs.online||0}</div></div>
        <div class="os-stat red"><div class="label">Offline</div><div class="val">${bs.offline||0}</div></div>
        <div class="os-stat amber"><div class="label">Error</div><div class="val">${bs.error||0}</div></div>
        <div class="os-stat"><div class="label">Maintenance</div><div class="val">${bs.maintenance||0}</div></div>
        <div class="os-stat cyan"><div class="label">Fleet Health</div><div class="val">${f.health||0}%</div></div>
        <div class="os-stat"><div class="label">Total</div><div class="val">${f.total_devices||0}</div></div>
    </div>${(f.stale_devices||[]).length > 0 ? `<div style="margin-top:8px;padding:8px 12px;background:rgba(245,158,11,.1);border-radius:8px;border-left:3px solid #f59e0b;font-size:12px"><i class="fas fa-exclamation-triangle" style="color:#f59e0b"></i> <strong>${f.stale_devices.length} stale device(s)</strong> — no heartbeat in 5+ minutes</div>` : ''}`;

    // Device groups
    const groups = groupData.groups || [];
    document.getElementById('device-groups').innerHTML = groups.length ? `<h3 style="margin:0 0 10px;font-size:.95rem;color:var(--os-dim)"><i class="fas fa-layer-group"></i> Device Groups</h3>
        <div style="display:flex;gap:10px;flex-wrap:wrap">${groups.map(g => `<div style="padding:10px 16px;background:rgba(124,92,231,.08);border:1px solid rgba(124,92,231,.2);border-radius:10px;cursor:pointer" onclick="sendGroupCommand('${esc(g.group_id)}')">
            <strong>${esc(g.display_name)}</strong><br>
            <span style="font-size:11px;color:var(--os-muted)">${g.device_count} devices · ${g.online_count} online</span>
        </div>`).join('')}</div>` : '';

    // Device table with twin/telemetry buttons
    document.getElementById('devices-table').innerHTML = (devData.devices||[]).map(d => `<tr>
        <td class="mono" style="font-size:11px">${esc(d.device_id)}</td>
        <td>${esc(d.display_name)}</td>
        <td>${esc(d.device_type)}</td>
        <td>${esc(d.protocol)}</td>
        <td>${statusBadge(d.status)}</td>
        <td>${ago(d.last_heartbeat)}</td>
        <td style="white-space:nowrap">
            <button class="os-btn sm" onclick="sendCommand('${esc(d.device_id)}')" title="Command"><i class="fas fa-terminal"></i></button>
            <button class="os-btn sm" onclick="viewDeviceTwin('${esc(d.device_id)}')" title="Twin" style="color:var(--os-cyan)"><i class="fas fa-digital-tachograph"></i></button>
            <button class="os-btn sm" onclick="viewDeviceTelemetry('${esc(d.device_id)}')" title="Telemetry" style="color:#22c55e"><i class="fas fa-chart-line"></i></button>
            <button class="os-btn sm danger" onclick="stopDevice('${esc(d.device_id)}')" title="Stop"><i class="fas fa-stop"></i></button>
        </td>
    </tr>`).join('') || '<tr><td colspan="7" class="empty-state">No devices registered</td></tr>';
}

// ── Actions ─────────────────────────────────────────────
async function createTaskPrompt() {
    const goal = prompt('Enter task goal:');
    if (!goal) return;
    const priority = parseInt(prompt('Priority (1-10):', '5')) || 5;
    await os.createTask(goal, { priority });
    loadTasks();
}

async function executeGoal() {
    const goal = document.getElementById('runtime-goal').value;
    if (!goal) return;
    const agent = document.getElementById('runtime-agent').value;
    const dryRun = document.getElementById('runtime-dryrun').checked;
    const log = document.getElementById('runtime-log');
    
    // Reset loop monitor
    document.querySelectorAll('.loop-phase').forEach(p => p.classList.remove('active','done','failed'));
    document.getElementById('loop-status-badge').innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size:8px"></i> Running';
    document.getElementById('loop-status-badge').style.background = 'rgba(99,102,241,.2)';
    document.getElementById('loop-status-badge').style.color = 'var(--os-primary)';
    document.getElementById('loop-round-counter').textContent = '';
    document.getElementById('loop-detail').textContent = '';
    document.getElementById('loop-task-id').textContent = '';
    
    log.innerHTML = `<div style="color:var(--os-cyan)">[${ts()}] ▶ Goal: ${esc(goal)} (agent: ${agent}, dry_run: ${dryRun})</div>`;
    
    // Start polling for loop events during execution
    let phaseOrder = ['observe','recall','plan','simulate','policy','act','verify','learn'];
    let phaseIdx = 0;
    let loopInterval = setInterval(() => {
        if (phaseIdx < phaseOrder.length) {
            let phase = phaseOrder[phaseIdx];
            document.querySelectorAll('.loop-phase').forEach(p => {
                if (p.dataset.phase === phase) p.classList.add('active');
            });
        }
    }, 800);
    
    try {
        const data = await os.executeAgent(goal, { agent_id: agent, dry_run: dryRun });
        clearInterval(loopInterval);
        
        // Mark all phases done
        document.querySelectorAll('.loop-phase').forEach(p => {
            p.classList.remove('active');
            p.classList.add(data.status === 'completed' ? 'done' : 'failed');
        });
        
        const badge = document.getElementById('loop-status-badge');
        if (data.status === 'completed') {
            badge.innerHTML = '<i class="fas fa-check-circle" style="font-size:8px"></i> Completed';
            badge.style.background = 'rgba(34,197,94,.2)';
            badge.style.color = 'var(--os-green)';
        } else {
            badge.innerHTML = '<i class="fas fa-times-circle" style="font-size:8px"></i> ' + (data.status || 'Failed');
            badge.style.background = 'rgba(239,68,68,.2)';
            badge.style.color = 'var(--os-red)';
        }
        
        document.getElementById('loop-task-id').textContent = data.task_id || '';
        const r = data.result || {};
        document.getElementById('loop-round-counter').textContent = `${r.rounds||0} rounds • ${r.duration_ms||0}ms • ${r.actions_taken||0} actions`;
        document.getElementById('loop-detail').textContent = r.output ? JSON.stringify(r.output).slice(0,200) : '';
        
        log.innerHTML += `<div style="color:var(--os-green)">[${ts()}] ✓ Status: ${esc(data.status)} | Rounds: ${r.rounds||0} | Duration: ${r.duration_ms||0}ms</div>`;
        if (r.output) log.innerHTML += `<div style="color:var(--os-text)">[${ts()}] Output: ${esc(JSON.stringify(r.output, null, 2))}</div>`;
        if (data.trace_id) log.innerHTML += `<div style="color:var(--os-muted)">[${ts()}] Trace: ${esc(data.trace_id)}</div>`;
    } catch(err) {
        clearInterval(loopInterval);
        document.querySelectorAll('.loop-phase').forEach(p => { p.classList.remove('active'); p.classList.add('failed'); });
        const badge = document.getElementById('loop-status-badge');
        badge.innerHTML = '<i class="fas fa-exclamation-triangle" style="font-size:8px"></i> Error';
        badge.style.background = 'rgba(239,68,68,.2)';
        badge.style.color = 'var(--os-red)';
        log.innerHTML += `<div style="color:var(--os-red)">[${ts()}] ✗ Error: ${esc(err.message)}</div>`;
    }
    log.scrollTop = log.scrollHeight;
}

function ts() { return new Date().toLocaleTimeString(); }

async function emergencyStop() {
    if (!confirm('⚠️ EMERGENCY STOP — This will halt ALL connected devices. Proceed?')) return;
    try {
        await os.emergencyStop();
        alert('Emergency stop executed.');
        loadDevices();
    } catch(e) {
        alert('Emergency stop failed: ' + e.message);
    }
}

async function killSwitch() {
    if (!confirm('⚠️ KILL SWITCH — This will cancel ALL running tasks, kill ALL sessions, and deny ALL pending approvals. This is irreversible. Proceed?')) return;
    try {
        const data = await os.killSwitch();
        alert(`Kill switch activated!\nCancelled tasks: ${data.cancelled_tasks}\nKilled sessions: ${data.killed_sessions}`);
        loadTasks();
    } catch(e) {
        alert('Kill switch failed: ' + e.message);
    }
}

async function killTask(taskId) {
    const reason = prompt('Reason for killing task ' + taskId + ':', 'Manual termination');
    if (reason === null) return;
    try {
        await os.killTask(taskId, reason);
        alert('Task killed.');
        loadTasks();
    } catch(e) {
        alert('Kill failed: ' + e.message);
    }
}

async function rollbackTask(taskId) {
    if (!confirm('Rollback task ' + taskId + '? This will undo completed actions where possible.')) return;
    try {
        const data = await os.rollback(taskId, 'Dashboard rollback');
        alert(`Rollback complete: ${data.rolled_back}/${data.total_nodes} actions reversed.`);
        loadTasks();
    } catch(e) {
        alert('Rollback failed: ' + e.message);
    }
}

// ── Approvals ───────────────────────────────────────────
async function loadApprovals(status) {
    const data = await os.approvals(status);
    const counts = data.counts || {};
    const pending = counts.pending || 0;
    document.getElementById('approval-count').textContent = pending || '-';
    document.getElementById('approval-count').style.display = pending ? 'inline' : 'none';

    document.getElementById('approval-stats').innerHTML = `<div class="os-stats">
        <div class="os-stat amber"><div class="label">Pending</div><div class="val">${counts.pending||0}</div></div>
        <div class="os-stat green"><div class="label">Approved</div><div class="val">${counts.approved||0}</div></div>
        <div class="os-stat red"><div class="label">Denied</div><div class="val">${counts.denied||0}</div></div>
        <div class="os-stat"><div class="label">Expired</div><div class="val">${counts.expired||0}</div></div>
    </div>`;

    document.getElementById('approvals-table').innerHTML = (data.approvals||[]).map(a => {
        const isPending = a.status === 'pending';
        const waitStr = a.wait_seconds ? (a.wait_seconds < 60 ? a.wait_seconds+'s' : Math.floor(a.wait_seconds/60)+'m') : '-';
        let actions = '';
        if (isPending) {
            actions = `<button class="os-btn sm" style="background:rgba(34,197,94,.15);color:var(--os-green)" onclick="approveAction('${esc(a.approval_id)}')"><i class="fas fa-check"></i> Approve</button>
                <button class="os-btn sm danger" onclick="denyAction('${esc(a.approval_id)}')"><i class="fas fa-times"></i> Deny</button>`;
        } else {
            actions = `<span style="font-size:11px;color:var(--os-muted)">${a.decided_by ? 'by '+esc(a.decided_by) : '-'}</span>`;
        }
        return `<tr>
            <td class="mono" style="font-size:11px">${esc((a.approval_id||'').substring(0,12))}</td>
            <td><span class="mono" style="font-size:10px">${esc((a.task_id||'').substring(0,12))}</span><br>
                <span style="font-size:12px">${esc((a.task_goal||a.action_summary||'').substring(0,60))}</span></td>
            <td class="mono">${esc(a.capability_id||'-')}</td>
            <td>${riskBadge(a.risk_level)}</td>
            <td>${statusBadge(a.status)}</td>
            <td class="mono">${waitStr}</td>
            <td class="mono" style="font-size:11px">${a.expires_at ? ago(a.expires_at) : '-'}</td>
            <td>${actions}</td>
        </tr>`;
    }).join('') || '<tr><td colspan="8" class="empty-state">No approval requests</td></tr>';
}

async function approveAction(id) {
    const reason = prompt('Approval reason:', 'Approved by admin');
    if (reason === null) return;
    try {
        await os.approveAction(id, reason);
        loadApprovals();
    } catch(e) { alert('Approve failed: ' + e.message); }
}

async function denyAction(id) {
    const reason = prompt('Denial reason:', 'Denied by admin');
    if (reason === null) return;
    try {
        await os.denyAction(id, reason);
        loadApprovals();
    } catch(e) { alert('Deny failed: ' + e.message); }
}

async function expireApprovals() {
    try {
        const data = await os.expireApprovals();
        alert('Expired: ' + (data.expired||0) + ' stale approval(s)');
        loadApprovals();
    } catch(e) { alert('Error: ' + e.message); }
}

// ── Replay ──────────────────────────────────────────────
async function replayTask() {
    const taskId = document.getElementById('replay-task-id').value.trim();
    if (!taskId) return alert('Enter a task ID');
    replayTaskFromId(taskId);
}

async function replayTaskFromId(taskId) {
    document.getElementById('replay-task-id').value = taskId;
    const output = document.getElementById('replay-output');
    output.style.display = 'block';
    output.innerHTML = '<div style="color:var(--os-cyan)">Loading replay...</div>';

    try {
        const data = await os.auditReplay(taskId);
        let html = `<div style="color:var(--os-cyan);margin-bottom:12px"><strong>Goal:</strong> ${esc(data.goal)} | <strong>Agent:</strong> ${esc(data.agent_id)} | <strong>Status:</strong> ${statusBadge(data.status)}</div>`;
        html += `<div style="margin-bottom:8px;font-size:11px;color:var(--os-muted)">${data.started_at} → ${data.completed_at || 'in progress'} | ${data.total_events} events</div>`;

        const timeline = data.timeline || [];
        for (let i = 0; i < timeline.length; i++) {
            const t = timeline[i];
            const statusColor = t.status === 'completed' ? 'var(--os-green)' : t.status === 'failed' ? 'var(--os-red)' : t.status === 'blocked' ? '#f59e0b' : 'var(--os-muted)';
            const icon = {task_created:'▶',observe:'👁',plan:'📋',simulate:'🧪',policy_deny:'🚫',execute:'⚡',verify:'✓',task_completed:'✅',approval_requested:'🔒',sandbox_started:'🏖',sandbox_completed:'🏖'}[t.action] || '•';
            html += `<div style="padding:6px 0;border-bottom:1px solid var(--os-border);display:flex;gap:10px;align-items:flex-start">
                <span style="width:24px;text-align:center;flex-shrink:0">${icon}</span>
                <span style="width:60px;flex-shrink:0;color:${statusColor}">${esc(t.action)}</span>
                <span style="width:80px;flex-shrink:0;font-size:11px">${esc(t.capability||'')}</span>
                <span style="flex:1;font-size:11px;color:var(--os-muted)">${esc(t.reason||'')}</span>
                <span style="width:50px;text-align:right;font-size:11px">${t.duration_ms ? t.duration_ms+'ms' : ''}</span>
            </div>`;
        }
        output.innerHTML = html;
        // Switch to audit panel if not already there
        if (!document.getElementById('panel-audit').classList.contains('active')) {
            showPanel.call(document.querySelector('.os-tab[onclick*="audit"]'), 'audit');
        }
    } catch(e) {
        output.innerHTML = `<div style="color:var(--os-red)">Replay failed: ${esc(e.message)}</div>`;
    }
}

// ── Sandbox ─────────────────────────────────────────────
async function sandboxGoal() {
    const goal = document.getElementById('runtime-goal').value;
    if (!goal) return alert('Enter a goal first');
    const agent = document.getElementById('runtime-agent').value;
    const log = document.getElementById('runtime-log');

    document.querySelectorAll('.loop-phase').forEach(p => p.classList.remove('active','done','failed'));
    const badge = document.getElementById('loop-status-badge');
    badge.innerHTML = '<i class="fas fa-flask fa-spin" style="font-size:8px"></i> Sandbox';
    badge.style.background = 'rgba(245,158,11,.2)';
    badge.style.color = '#f59e0b';

    log.innerHTML = `<div style="color:#f59e0b">[${ts()}] 🏖 SANDBOX: ${esc(goal)} (agent: ${agent})</div>`;

    try {
        const data = await os.sandboxGoal(goal, { agent_id: agent });
        const sb = data.sandbox || {};

        badge.innerHTML = `<i class="fas fa-flask" style="font-size:8px"></i> Sandbox: ${esc(sb.outcome)}`;
        badge.style.background = sb.outcome === 'safe' ? 'rgba(34,197,94,.2)' : sb.outcome === 'unsafe' ? 'rgba(239,68,68,.2)' : 'rgba(245,158,11,.2)';
        badge.style.color = sb.outcome === 'safe' ? 'var(--os-green)' : sb.outcome === 'unsafe' ? 'var(--os-red)' : '#f59e0b';

        document.getElementById('loop-round-counter').textContent = `${sb.total_steps||0} steps • ${sb.duration_ms||0}ms • Risk: ${(sb.cumulative_risk*100).toFixed(1)}%`;

        // Show step-by-step results
        (sb.steps || []).forEach((step, i) => {
            const color = step.would_be_blocked ? 'var(--os-red)' : step.would_need_approval ? '#f59e0b' : 'var(--os-green)';
            const icon = step.would_be_blocked ? '🚫' : step.would_need_approval ? '🔒' : '✅';
            log.innerHTML += `<div style="color:${color}">[${ts()}] ${icon} Step ${step.round}: ${esc(step.capability||'complete')} — Sim: ${step.sim?.outcome||'n/a'} (risk: ${((step.sim?.risk_score||0)*100).toFixed(0)}%) — Policy: ${step.policy?.action||'n/a'}</div>`;
            if (step.reasoning) log.innerHTML += `<div style="color:var(--os-muted);font-size:11px;padding-left:24px">${esc(step.reasoning.substring(0,200))}</div>`;
        });

        log.innerHTML += `<div style="color:#f59e0b;margin-top:8px">[${ts()}] 🏖 Sandbox complete: ${esc(sb.outcome)} | ${sb.blocked_steps} blocked | ${sb.approval_needed} need approval | Cumulative risk: ${(sb.cumulative_risk*100).toFixed(1)}%</div>`;

        document.querySelectorAll('.loop-phase').forEach(p => p.classList.add(sb.outcome === 'safe' ? 'done' : 'failed'));
    } catch(e) {
        badge.innerHTML = '<i class="fas fa-exclamation-triangle" style="font-size:8px"></i> Error';
        badge.style.background = 'rgba(239,68,68,.2)';
        badge.style.color = 'var(--os-red)';
        log.innerHTML += `<div style="color:var(--os-red)">[${ts()}] ✗ Sandbox error: ${esc(e.message)}</div>`;
    }
    log.scrollTop = log.scrollHeight;
}

async function policyCheckPrompt() {
    const cap = prompt('Capability ID to check:', 'cap_web_search');
    if (!cap) return;
    const risk = prompt('Risk level:', 'low');
    const data = await os.checkPolicy(cap, risk);
    alert(`Decision: ${data.decision}\nRequires Simulation: ${data.requires_simulation}\nRequires Approval: ${data.requires_approval}\nReasons: ${(data.reasons||[]).join(', ') || 'none'}`);
}

async function runSimPrompt() {
    const cap = prompt('Capability ID to simulate:', 'cap_web_search');
    if (!cap) return;
    const data = await os.simulate(cap, {});
    alert(`Outcome: ${data.outcome}\nRisk Score: ${data.risk_score}\nDuration: ${data.duration_ms}ms`);
    loadSimulations();
}

function executeSkillPrompt(skillId) { alert('Skill execution UI — coming soon: ' + skillId); }
function sendCommand(deviceId) { alert('Device command UI — coming soon: ' + deviceId); }
function stopDevice(deviceId) {
    if (confirm('Stop device ' + deviceId + '?')) os.emergencyStop(deviceId).then(() => loadDevices());
}
function createSkillModal() { alert('Skill creation wizard — coming in Phase 2'); }
function registerDevicePrompt() {
    const name = prompt('Device name:');
    if (!name) return;
    const type = prompt('Type (robot, iot_sensor, iot_actuator, camera, controller, custom):', 'iot_sensor');
    if (!type) return;
    const protocol = prompt('Protocol (http, mqtt, ros2, websocket):', 'http');
    os.registerDevice({
        display_name: name,
        device_type: type,
        protocol: protocol || 'http',
        connection_url: prompt('Connection URL (optional):', '') || null,
    }).then(data => {
        alert('Device registered!\nID: ' + data.device_id + '\nToken: ' + data.device_token + '\n\nSave this token — it cannot be retrieved again.');
        loadDevices();
    }).catch(e => alert('Error: ' + e.message));
}

function createGroupPrompt() {
    const name = prompt('Group name:');
    if (!name) return;
    const ids = prompt('Device IDs (comma-separated):');
    if (!ids) return;
    os.createDeviceGroup({
        display_name: name,
        device_ids: ids.split(',').map(s => s.trim()).filter(Boolean),
    }).then(() => { alert('Group created!'); loadDevices(); })
      .catch(e => alert('Error: ' + e.message));
}

function sendGroupCommand(groupId) {
    const cmd = prompt('Command to send to all devices in group ' + groupId + ':');
    if (!cmd) return;
    os.groupCommand(groupId, cmd).then(data => {
        alert(`Group command sent!\nSucceeded: ${data.succeeded}/${data.total_devices}`);
        loadDevices();
    }).catch(e => alert('Error: ' + e.message));
}

async function loadSensorPipeline() {
    const minutes = document.getElementById('pipeline-window').value;
    try {
        const data = await os.sensorPipeline(minutes);
        const agg = data.aggregated || {};
        const devices = Object.keys(agg);
        if (devices.length === 0) {
            document.getElementById('sensor-pipeline').innerHTML = '<span class="empty-state">No sensor data in this window</span>';
            return;
        }
        let html = '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:10px">';
        for (const devId of devices) {
            const metrics = agg[devId];
            html += `<div style="padding:12px;background:rgba(0,245,255,.04);border:1px solid rgba(0,245,255,.15);border-radius:10px">
                <div style="font-weight:600;font-size:12px;margin-bottom:6px;color:var(--os-cyan)">${esc(devId)}</div>`;
            for (const [metric, stats] of Object.entries(metrics)) {
                html += `<div style="display:flex;justify-content:space-between;padding:2px 0;font-size:11px;border-bottom:1px solid var(--os-border)">
                    <span>${esc(metric)}</span>
                    <span class="mono">avg:${stats.avg} min:${stats.min} max:${stats.max} <span style="color:var(--os-muted)">(${stats.readings}x)</span></span>
                </div>`;
            }
            html += '</div>';
        }
        html += '</div>';
        document.getElementById('sensor-pipeline').innerHTML = html;
    } catch(e) {
        document.getElementById('sensor-pipeline').innerHTML = `<span style="color:var(--os-red)">Error: ${esc(e.message)}</span>`;
    }
}

function viewDeviceTwin(deviceId) {
    document.getElementById('twin-device-id').value = deviceId;
    loadTwinSnapshots();
}

function viewDeviceTelemetry(deviceId) {
    document.getElementById('telemetry-device-id').value = deviceId;
    loadTelemetryHistory();
}

async function loadTwinSnapshots() {
    const deviceId = document.getElementById('twin-device-id').value.trim();
    if (!deviceId) return alert('Enter a device ID');
    try {
        const data = await os.twinSnapshots(deviceId, 20);
        const snaps = data.snapshots || [];
        if (!snaps.length) {
            document.getElementById('twin-snapshots').innerHTML = '<span class="empty-state">No snapshots for this device</span>';
            return;
        }
        document.getElementById('twin-snapshots').innerHTML = snaps.map(s => {
            const typeColor = {auto:'var(--os-cyan)',manual:'var(--os-primary)',alert:'var(--os-red)',checkpoint:'#22c55e'}[s.snapshot_type] || 'var(--os-muted)';
            return `<div style="padding:10px;margin-bottom:6px;background:rgba(255,255,255,.02);border:1px solid var(--os-border);border-left:3px solid ${typeColor};border-radius:8px">
                <div style="display:flex;justify-content:space-between;margin-bottom:4px">
                    <span class="badge-pill" style="background:${typeColor}20;color:${typeColor}">${s.snapshot_type}</span>
                    <span style="font-size:11px;color:var(--os-muted)">${ago(s.created_at)} · ${s.trigger_event||'-'}</span>
                </div>
                <pre style="font-size:10px;max-height:80px;overflow:auto;margin:0;padding:6px;background:rgba(0,0,0,.2);border-radius:4px">${esc(JSON.stringify(s.twin_state, null, 1))}</pre>
            </div>`;
        }).join('');
    } catch(e) {
        document.getElementById('twin-snapshots').innerHTML = `<span style="color:var(--os-red)">Error: ${esc(e.message)}</span>`;
    }
}

async function createTwinSnapshot() {
    const deviceId = document.getElementById('twin-device-id').value.trim();
    if (!deviceId) return alert('Enter a device ID first');
    try {
        await os.twinSnapshot(deviceId, 'manual_dashboard');
        alert('Snapshot created!');
        loadTwinSnapshots();
    } catch(e) { alert('Error: ' + e.message); }
}

async function loadTelemetryHistory() {
    const deviceId = document.getElementById('telemetry-device-id').value.trim();
    if (!deviceId) return alert('Enter a device ID');
    const metric = document.getElementById('telemetry-metric').value.trim();
    const hours = document.getElementById('telemetry-hours').value;
    try {
        const data = await os.telemetryHistory(deviceId, metric || undefined, hours);
        const stats = data.stats || {};
        const history = data.history || [];

        let html = '';
        // Available metrics
        if (data.available_metrics?.length) {
            html += `<div style="margin-bottom:10px;font-size:11px;color:var(--os-muted)">Available: ${data.available_metrics.map(m => `<button class="os-btn sm" style="padding:2px 8px;font-size:10px" onclick="document.getElementById('telemetry-metric').value='${esc(m)}';loadTelemetryHistory()">${esc(m)}</button>`).join(' ')}</div>`;
        }

        // Stats cards
        const statEntries = Object.entries(stats);
        if (statEntries.length) {
            html += '<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px">';
            for (const [name, s] of statEntries) {
                html += `<div style="padding:8px 12px;background:rgba(34,197,94,.06);border:1px solid rgba(34,197,94,.15);border-radius:8px;font-size:11px">
                    <strong style="color:#22c55e">${esc(name)}</strong><br>
                    avg: ${s.avg} · min: ${s.min} · max: ${s.max} · ${s.count} readings
                </div>`;
            }
            html += '</div>';
        }

        // History table
        if (history.length) {
            html += `<div style="max-height:300px;overflow:auto"><table class="os-table" style="font-size:11px"><thead><tr><th>Time</th><th>Metric</th><th>Value</th><th>Unit</th></tr></thead><tbody>`;
            for (const h of history) {
                html += `<tr><td>${ago(h.recorded_at)}</td><td class="mono">${esc(h.metric_name)}</td><td class="mono">${h.metric_value}</td><td>${esc(h.unit||'-')}</td></tr>`;
            }
            html += '</tbody></table></div>';
        } else {
            html += '<span class="empty-state">No telemetry data for this period</span>';
        }

        document.getElementById('telemetry-history').innerHTML = html;
    } catch(e) {
        document.getElementById('telemetry-history').innerHTML = `<span style="color:var(--os-red)">Error: ${esc(e.message)}</span>`;
    }
}

// ── Init ────────────────────────────────────────────────
loadOverview();
