(() => {
'use strict';

const API = '/api/agent-orchestrator.php';
const isOwner = window._orchIsOwner || false;
const CSRF_TOKEN = window._orchCsrfToken || '';
let currentPage = 1;
let searchTimer = null;

// ── CSRF-safe POST helper ─────────────────────────────────────
function apiPost(action, taskId, body = null) {
  let url = `${API}?action=${action}`;
  if (taskId) url += `&id=${encodeURIComponent(taskId)}`;
  return fetch(url, {
    method: 'POST',
    credentials: 'same-origin',
    headers: {'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN},
    body: body ? JSON.stringify(body) : undefined
  });
}

// ── Category Config ───────────────────────────────────────────
const catIcons = {
  security:'🛡️', frontend:'🎨', api:'⚙️', javascript:'📜',
  test:'🧪', script:'🔧', docs:'📄', sdk:'📦', debt:'🧹', feature:'✨'
};
const catColors = {
  security:'var(--ao-red)', frontend:'var(--ao-accent)', api:'var(--ao-blue)',
  javascript:'var(--ao-yellow)', test:'var(--ao-green)', script:'var(--ao-cyan)',
  docs:'var(--ao-purple)', sdk:'var(--ao-accent2)', debt:'var(--ao-muted)', feature:'var(--ao-green)'
};

// ── Load Stats ────────────────────────────────────────────────
async function loadStats() {
  try {
    const r = await fetch(`${API}?action=stats`, {credentials:'same-origin'});
    const d = await r.json();
    if (!d.success) return;
    const s = d.stats;
    const bs = s.by_status;

    document.getElementById('statPending').textContent = bs.pending || 0;
    document.getElementById('statRunning').textContent = bs.running || 0;
    document.getElementById('statDone').textContent = bs.done || 0;
    document.getElementById('statClaimed').textContent = bs.claimed || 0;
    document.getElementById('statFailed').textContent = bs.failed || 0;
    document.getElementById('statTotal').textContent = s.total || 0;

    document.getElementById('heroRunning').textContent = bs.running || 0;
    document.getElementById('heroPending').textContent = bs.pending || 0;
    document.getElementById('heroDone').textContent = bs.done || 0;
    document.getElementById('heroRate').textContent = s.completion_rate_24h ?? 100;

    // Categories (use only known ENUM values to prevent XSS)
    const validCats = ['security','frontend','api','javascript','test','script','docs','sdk','debt','feature'];
    const grid = document.getElementById('categoryGrid');
    grid.innerHTML = Object.entries(s.by_category || {}).filter(([cat]) => validCats.includes(cat)).map(([cat, count]) =>
      `<div class="ao-cat" onclick="filterByCategory('${cat}')">
        <span class="ao-cat-icon">${catIcons[cat]||'📁'}</span>
        <span class="ao-cat-name">${cat}</span>
        <span class="ao-cat-count">${count}</span>
      </div>`
    ).join('');
  } catch(e) { console.error('Stats error:', e); }
}

// ── Load Tasks ────────────────────────────────────────────────
async function loadTasks(page) {
  if (page) currentPage = page;
  const status = document.getElementById('filterStatus').value;
  const category = document.getElementById('filterCategory').value;
  const priority = document.getElementById('filterPriority').value;
  const q = document.getElementById('searchInput').value;

  let url = `${API}?action=backlog&page=${currentPage}`;
  if (status) url += `&status=${encodeURIComponent(status)}`;
  if (category) url += `&category=${encodeURIComponent(category)}`;
  if (priority) url += `&priority=${encodeURIComponent(priority)}`;
  if (q) url += `&q=${encodeURIComponent(q)}`;

  try {
    const r = await fetch(url, {credentials:'same-origin'});
    const d = await r.json();
    if (!d.success) return;

    renderTasks(d.tasks);
    renderPagination(d.page, d.pages, d.total);
  } catch(e) { console.error('Load tasks error:', e); }
}
window.loadTasks = loadTasks;

function renderTasks(tasks) {
  const el = document.getElementById('taskList');
  if (!tasks.length) {
    el.innerHTML = '<div style="text-align:center;padding:3rem;color:var(--ao-muted)"><i class="fas fa-inbox" style="font-size:2rem;margin-bottom:.8rem;display:block"></i>No tasks found</div>';
    return;
  }

  el.innerHTML = tasks.map(t => {
    const actions = [];
    if (t.status === 'pending') {
      actions.push(`<button class="ao-btn ao-btn-sm ao-btn-primary" onclick="event.stopPropagation();claimTask('${t.task_id}')"><i class="fas fa-hand"></i> Claim</button>`);
      if (isOwner) actions.push(`<button class="ao-btn ao-btn-sm ao-btn-success" onclick="event.stopPropagation();spawnTask('${t.task_id}')"><i class="fas fa-rocket"></i> Spawn</button>`);
    }
    if (t.status === 'claimed' || t.status === 'running') {
      actions.push(`<button class="ao-btn ao-btn-sm ao-btn-success" onclick="event.stopPropagation();completeTask('${t.task_id}')"><i class="fas fa-check"></i> Done</button>`);
      actions.push(`<button class="ao-btn ao-btn-sm ao-btn-danger" onclick="event.stopPropagation();failTask('${t.task_id}')"><i class="fas fa-times"></i> Fail</button>`);
    }
    if (t.status === 'failed') {
      if (isOwner) actions.push(`<button class="ao-btn ao-btn-sm ao-btn-primary" onclick="event.stopPropagation();retryTask('${t.task_id}')"><i class="fas fa-redo"></i> Retry</button>`);
    }

    const since = t.updated_at ? timeAgo(t.updated_at) : '';

    return `<div class="ao-task priority-${t.priority}" onclick="showTaskDetail('${t.task_id}')">
      <div class="ao-task-top">
        <span class="ao-task-id">${t.task_id}</span>
        <span class="ao-task-status status-${t.status}">${t.status}</span>
      </div>
      <div class="ao-task-title">${escHtml(t.title)}</div>
      <div class="ao-task-meta">
        <span><i class="fas fa-tag"></i>${t.category}</span>
        <span><i class="fas fa-flag"></i>${t.priority}</span>
        ${t.target_file ? `<span><i class="fas fa-file-code"></i>${escHtml(t.target_file)}</span>` : ''}
        ${since ? `<span><i class="fas fa-clock"></i>${since}</span>` : ''}
      </div>
      ${actions.length ? `<div class="ao-task-actions">${actions.join('')}</div>` : ''}
    </div>`;
  }).join('');
}

function renderPagination(page, pages, total) {
  const el = document.getElementById('pagination');
  if (pages <= 1) { el.innerHTML = ''; return; }
  let html = '';
  if (page > 1) html += `<button class="ao-btn ao-btn-sm ao-btn-secondary" onclick="loadTasks(${page-1})"><i class="fas fa-chevron-left"></i></button>`;
  for (let i = Math.max(1, page-2); i <= Math.min(pages, page+2); i++) {
    html += `<button class="ao-btn ao-btn-sm ${i===page?'ao-btn-primary':'ao-btn-secondary'}" onclick="loadTasks(${i})">${i}</button>`;
  }
  if (page < pages) html += `<button class="ao-btn ao-btn-sm ao-btn-secondary" onclick="loadTasks(${page+1})"><i class="fas fa-chevron-right"></i></button>`;
  html += `<span style="color:var(--ao-muted);font-size:.75rem;align-self:center;margin-left:.5rem">${total} tasks</span>`;
  el.innerHTML = html;
}

// ── Debounced Search ──────────────────────────────────────────
window.debouncedSearch = function() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => loadTasks(1), 300);
};

// ── Filter by Category ───────────────────────────────────────
window.filterByCategory = function(cat) {
  document.getElementById('filterCategory').value = cat;
  loadTasks(1);
};

// ── Task Actions ──────────────────────────────────────────────
window.claimTask = async function(taskId) {
  const r = await apiPost('claim', taskId);
  const d = await r.json();
  if (d.success) { loadTasks(); loadStats(); addLocalLog('info', 'You', `Claimed task ${taskId}`); }
  else alert(d.message || 'Failed to claim');
};

window.spawnTask = async function(taskId) {
  const r = await apiPost('spawn', taskId, {task_id: taskId});
  const d = await r.json();
  if (d.success) { loadTasks(); loadStats(); addLocalLog('success', 'Orchestrator', `Spawned agent for ${taskId}`); }
  else alert(d.message || 'Failed to spawn');
};

window.completeTask = async function(taskId) {
  const summary = prompt('Brief summary of what was done (optional):');
  const r = await apiPost('complete', taskId, {summary: summary || ''});
  const d = await r.json();
  if (d.success) { loadTasks(); loadStats(); addLocalLog('success', 'System', `Task ${taskId} completed`); }
  else alert(d.message || 'Failed to complete');
};

window.failTask = async function(taskId) {
  const error = prompt('Error reason:');
  if (!error) return;
  const r = await apiPost('fail', taskId, {error});
  const d = await r.json();
  if (d.success) { loadTasks(); loadStats(); addLocalLog('error', 'System', `Task ${taskId} failed: ${error}`); }
};

window.retryTask = async function(taskId) {
  const r = await apiPost('retry', taskId);
  const d = await r.json();
  if (!d.success) { alert(d.message || 'Failed to retry'); return; }
  addLocalLog('info', 'System', `Task ${taskId} reset to pending`);
  loadTasks(); loadStats();
};

window.cancelTask = async function(taskId) {
  if (!confirm(`Cancel task ${taskId}?`)) return;
  const r = await apiPost('cancel', taskId);
  const d = await r.json();
  if (d.success) { loadTasks(); loadStats(); addLocalLog('info', 'System', `Task ${taskId} cancelled`); }
  else alert(d.message || 'Failed to cancel');
};

// ── Create Task ───────────────────────────────────────────────
window.createTask = async function(e) {
  e.preventDefault();
  const body = {
    title: document.getElementById('newTitle').value.trim(),
    description: document.getElementById('newDesc').value.trim(),
    category: document.getElementById('newCategory').value,
    priority: document.getElementById('newPriority').value,
    target_file: document.getElementById('newFile').value.trim()
  };
  if (!body.title) return false;

  const r = await fetch(`${API}?action=create`, {
    method:'POST', credentials:'same-origin',
    headers:{'Content-Type':'application/json', 'X-CSRF-Token': CSRF_TOKEN},
    body: JSON.stringify(body)
  });
  const d = await r.json();
  if (d.success) {
    document.getElementById('newTitle').value = '';
    document.getElementById('newDesc').value = '';
    document.getElementById('newFile').value = '';
    loadTasks(1);
    loadStats();
    addLocalLog('info', 'System', `Created task ${d.task_id}`);
    document.getElementById('createForm').style.display = 'none';
  } else alert(d.message || 'Failed to create task');
  return false;
};

window.toggleCreateForm = function() {
  const form = document.getElementById('createForm');
  form.style.display = form.style.display === 'none' ? 'block' : 'none';
};

// ── Import Backlog ────────────────────────────────────────────
window.importBacklog = async function() {
  if (!confirm('Import all tasks from UPGRADE_BACKLOG.md? Existing tasks will be skipped.')) return;
  const r = await apiPost('import_backlog', '');
  const d = await r.json();
  if (d.success) {
    alert(`Imported ${d.imported} tasks (${d.skipped_duplicates} duplicates skipped)`);
    loadTasks(1);
    loadStats();
  } else alert(d.message || 'Import failed');
};

// ── Spawn All Pending ─────────────────────────────────────────
window.spawnAll = async function() {
  if (!confirm('Spawn agents for ALL pending tasks? This will queue them for the PM2 runner.')) return;
  const r = await fetch(`${API}?action=backlog&status=pending&limit=100`, {credentials:'same-origin'});
  const d = await r.json();
  if (!d.success || !d.tasks.length) { alert('No pending tasks'); return; }

  let spawned = 0;
  const batch = 5;
  for (let i = 0; i < d.tasks.length; i += batch) {
    const chunk = d.tasks.slice(i, i + batch);
    const results = await Promise.all(chunk.map(t =>
      apiPost('spawn', t.task_id, {task_id: t.task_id}).then(r => r.json()).catch(() => ({success:false}))
    ));
    spawned += results.filter(r => r.success).length;
  }
  alert(`Spawned ${spawned} agents`);
  loadTasks();
  loadStats();
};

// ── Spawn Batch by Category ───────────────────────────────────
window.spawnBatch = async function(category) {
  if (!confirm(`Spawn agents for all pending ${category} tasks?`)) return;
  const r = await fetch(`${API}?action=backlog&status=pending&category=${encodeURIComponent(category)}&limit=100`, {credentials:'same-origin'});
  const d = await r.json();
  if (!d.success || !d.tasks.length) { alert(`No pending ${category} tasks`); return; }

  let spawned = 0;
  const batch = 5;
  for (let i = 0; i < d.tasks.length; i += batch) {
    const chunk = d.tasks.slice(i, i + batch);
    const results = await Promise.all(chunk.map(t =>
      apiPost('spawn', t.task_id, {task_id: t.task_id}).then(r => r.json()).catch(() => ({success:false}))
    ));
    spawned += results.filter(r => r.success).length;
  }
  alert(`Spawned ${spawned} ${category} agents`);
  loadTasks();
  loadStats();
};

// ── Generate CLI Command ──────────────────────────────────────
window.generatePrompt = function() {
  const cmd = `# Spawn agents for all pending tasks via Claude Code CLI
cd /home/gositeme/domains/gositeme.com/public_html

# Get pending tasks from API
curl -s 'https://gositeme.com/api/agent-orchestrator.php?action=backlog&status=pending&limit=100' \\
  -H 'Cookie: PHPSESSID=YOUR_SESSION' | \\
  jq -r '.tasks[].task_id' | while read TASK_ID; do
    echo "Spawning agent for $TASK_ID..."
    claude -p "Read .github/copilot-instructions.md and UPGRADE_BACKLOG.md. \\
      Complete task $TASK_ID. Follow all conventions. \\
      When done, curl POST to /api/agent-orchestrator.php?action=complete&id=$TASK_ID \\
      with summary of changes." &
    sleep 2  # Rate limit spawning
done
wait
echo "All agents spawned"`;

  // Show in modal
  document.getElementById('modalTitle').textContent = 'CLI Spawn Command';
  document.getElementById('modalBody').innerHTML = `
    <div class="ao-detail-val"><pre>${escHtml(cmd)}</pre></div>
    <button class="ao-btn ao-btn-primary" style="margin-top:1rem;width:100%" onclick="navigator.clipboard.writeText(${JSON.stringify(cmd)});this.innerHTML='<i class=\\'fas fa-check\\'></i> Copied!'">
      <i class="fas fa-copy"></i> Copy to Clipboard
    </button>`;
  document.getElementById('taskModal').classList.add('active');
};

// ── Task Detail Modal ─────────────────────────────────────────
window.showTaskDetail = async function(taskId) {
  const [tr, lr] = await Promise.all([
    fetch(`${API}?action=task&id=${taskId}`, {credentials:'same-origin'}).then(r=>r.json()),
    fetch(`${API}?action=logs&id=${taskId}`, {credentials:'same-origin'}).then(r=>r.json())
  ]);

  if (!tr.success) return;
  const t = tr.task;
  const logs = (lr.success ? lr.logs : []);

  document.getElementById('modalTitle').textContent = t.task_id + ' — ' + t.title;
  document.getElementById('modalBody').innerHTML = `
    <div class="ao-detail-row"><span class="ao-detail-label">Status</span><span class="ao-detail-val"><span class="ao-task-status status-${t.status}">${t.status}</span></span></div>
    <div class="ao-detail-row"><span class="ao-detail-label">Priority</span><span class="ao-detail-val">${t.priority}</span></div>
    <div class="ao-detail-row"><span class="ao-detail-label">Category</span><span class="ao-detail-val">${catIcons[t.category]||''} ${t.category}</span></div>
    ${t.description ? `<div class="ao-detail-row"><span class="ao-detail-label">Description</span><span class="ao-detail-val">${escHtml(t.description)}</span></div>` : ''}
    ${t.target_file ? `<div class="ao-detail-row"><span class="ao-detail-label">Target File</span><span class="ao-detail-val"><code>${escHtml(t.target_file)}</code></span></div>` : ''}
    ${t.agent_session_id ? `<div class="ao-detail-row"><span class="ao-detail-label">Session</span><span class="ao-detail-val" style="font-family:var(--ao-mono);font-size:.78rem">${escHtml(t.agent_session_id)}</span></div>` : ''}
    ${t.result_summary ? `<div class="ao-detail-row"><span class="ao-detail-label">Result</span><span class="ao-detail-val">${escHtml(t.result_summary)}</span></div>` : ''}
    ${t.error_message ? `<div class="ao-detail-row"><span class="ao-detail-label">Error</span><span class="ao-detail-val" style="color:var(--ao-red)">${escHtml(t.error_message)}</span></div>` : ''}
    ${t.files_changed ? `<div class="ao-detail-row"><span class="ao-detail-label">Files Changed</span><span class="ao-detail-val"><pre>${escHtml(t.files_changed)}</pre></span></div>` : ''}
    ${t.validation_log ? `<div class="ao-detail-row"><span class="ao-detail-label">Validation</span><span class="ao-detail-val"><pre>${escHtml(t.validation_log)}</pre></span></div>` : ''}
    <div class="ao-detail-row"><span class="ao-detail-label">Created</span><span class="ao-detail-val">${t.created_at || 'N/A'}</span></div>
    ${t.started_at ? `<div class="ao-detail-row"><span class="ao-detail-label">Started</span><span class="ao-detail-val">${t.started_at}</span></div>` : ''}
    ${t.completed_at ? `<div class="ao-detail-row"><span class="ao-detail-label">Completed</span><span class="ao-detail-val">${t.completed_at}</span></div>` : ''}
    <div class="ao-detail-row"><span class="ao-detail-label">Retries</span><span class="ao-detail-val">${t.retry_count}/${t.max_retries}</span></div>
    ${logs.length ? `
    <h4 style="margin-top:1rem;margin-bottom:.5rem;font-size:.85rem"><i class="fas fa-stream" style="color:var(--ao-accent);margin-right:.4rem"></i>Execution Log</h4>
    <div class="ao-log-list" style="max-height:200px">
      ${logs.map(l => `<div class="ao-log-item log-${l.level}">
        <div class="ao-log-icon"><i class="fas ${l.level==='success'?'fa-check':l.level==='error'?'fa-times':l.level==='warn'?'fa-exclamation':'fa-info'}"></i></div>
        <div class="ao-log-msg"><strong>${escHtml(l.agent_name||'')}</strong> ${escHtml(l.message)}</div>
        <span class="ao-log-time">${l.created_at ? timeAgo(l.created_at) : ''}</span>
      </div>`).join('')}
    </div>` : ''}
    <div style="display:flex;gap:.5rem;margin-top:1rem">
      ${t.status==='pending' ? `<button class="ao-btn ao-btn-primary" onclick="claimTask('${t.task_id}');closeModal()"><i class="fas fa-hand"></i> Claim</button>` : ''}
      ${t.status==='pending' && isOwner ? `<button class="ao-btn ao-btn-success" onclick="spawnTask('${t.task_id}');closeModal()"><i class="fas fa-rocket"></i> Spawn Agent</button>` : ''}
      ${(t.status==='claimed'||t.status==='running') ? `<button class="ao-btn ao-btn-success" onclick="completeTask('${t.task_id}');closeModal()"><i class="fas fa-check"></i> Mark Done</button>` : ''}
    </div>`;
  document.getElementById('taskModal').classList.add('active');
};

window.closeModal = function() {
  document.getElementById('taskModal').classList.remove('active');
};

// Close modal on overlay click
document.getElementById('taskModal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});

// Close modal on Escape
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

// ── Local Activity Log ────────────────────────────────────────
function addLocalLog(level, agent, msg) {
  const list = document.getElementById('logList');
  const icons = {info:'fa-info', success:'fa-check', warn:'fa-exclamation', error:'fa-times'};
  const div = document.createElement('div');
  div.className = `ao-log-item log-${level}`;
  div.innerHTML = `<div class="ao-log-icon"><i class="fas ${icons[level]||'fa-info'}"></i></div>
    <div class="ao-log-msg"><strong>${escHtml(agent)}</strong> ${escHtml(msg)}</div>
    <span class="ao-log-time">now</span>`;
  list.prepend(div);
  while (list.children.length > 30) list.lastChild.remove();
}

// ── Helpers ────────────────────────────────────────────────────
function escHtml(s) {
  if (!s) return '';
  const d = document.createElement('div');
  d.textContent = s;
  return d.innerHTML;
}

function timeAgo(dateStr) { return GDS.timeAgo(dateStr); }

// ── Load Recent Activity Logs ─────────────────────────────────
async function loadRecentLogs() {
  try {
    const r = await fetch(`${API}?action=logs&id=*`, {credentials:'same-origin'});
    const d = await r.json();
    if (!d.success || !d.logs.length) return;
    const list = document.getElementById('logList');
    const icons = {info:'fa-info', success:'fa-check', warn:'fa-exclamation', error:'fa-times'};
    list.innerHTML = d.logs.slice(0, 20).map(l =>
      `<div class="ao-log-item log-${l.level}"><div class="ao-log-icon"><i class="fas ${icons[l.level]||'fa-info'}"></i></div>
        <div class="ao-log-msg"><strong>${escHtml(l.agent_name||'')}</strong> ${escHtml(l.message)}</div>
        <span class="ao-log-time">${l.created_at ? timeAgo(l.created_at) : ''}</span></div>`
    ).join('');
  } catch(e) {}
}

// ── Boot ──────────────────────────────────────────────────────
loadStats();
loadTasks();
loadRecentLogs();

// Auto-refresh every 15s
setInterval(() => { loadStats(); loadTasks(); }, 15000);
// Refresh logs every 30s
setInterval(loadRecentLogs, 30000);

})();
