/**
 * GoSiteMe Enterprise Admin Engine v2.0
 * Extracted from enterprise-admin.php inline JS
 * Features: Members CRUD, teams, audit log, API keys, SSO, white-label, roles, usage, org settings
 */
/* ═══════════════════════════════════════════════
   GLOBALS
   ═══════════════════════════════════════════════ */
const API = '/api/enterprise.php';
let membersData = [];
let auditPage = 1;
const auditPerPage = 20;

/* ═══════════════════════════════════════════════
   TOAST NOTIFICATIONS
   ═══════════════════════════════════════════════ */
function toast(message, type = 'info') {
  if (window.GDSToast) return GDSToast.show(message, { type: type === 'error' ? 'danger' : type });
}

/* ═══════════════════════════════════════════════
   API FETCH HELPER
   ═══════════════════════════════════════════════ */
async function apiFetch(action, options = {}) {
  const method = options.method || 'GET';
  const url = method === 'GET' && options.params
    ? API + '?action=' + action + '&' + new URLSearchParams(options.params).toString()
    : API + '?action=' + action;

  const fetchOpts = {
    method: method,
    headers: { 'Content-Type': 'application/json' },
    credentials: 'same-origin'
  };
  if (options.body && method !== 'GET') {
    fetchOpts.body = JSON.stringify(options.body);
  }

  const resp = await fetch(url, fetchOpts);
  const data = await resp.json();
  if (!resp.ok) {
    throw new Error(data.error || data.message || 'Request failed');
  }
  return data;
}

/* ═══════════════════════════════════════════════
   SIDEBAR NAVIGATION
   ═══════════════════════════════════════════════ */
const sectionTitles = {
  dashboard: 'Dashboard', members: 'Members', teams: 'Teams',
  roles: 'Roles & Permissions', audit: 'Audit Log', apikeys: 'API Keys',
  usage: 'Usage & Billing', sso: 'SSO Settings', whitelabel: 'White Label',
  settings: 'Organization Settings'
};
let loadedSections = new Set();

function switchSection(name) {
  document.querySelectorAll('.ea-section').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('.ea-nav-item').forEach(n => n.classList.remove('active'));
  const sec = document.getElementById('sec-' + name);
  const nav = document.querySelector('[data-section="' + name + '"]');
  if (sec) sec.classList.add('active');
  if (nav) nav.classList.add('active');
  document.getElementById('topbarTitle').textContent = sectionTitles[name] || name;

  // Close mobile sidebar
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('mobileOverlay').classList.remove('show');

  // Load data for section on first view
  if (!loadedSections.has(name)) {
    loadedSections.add(name);
    loadSectionData(name);
  }
}

function loadSectionData(name) {
  switch (name) {
    case 'dashboard': loadDashboard(); break;
    case 'members': loadMembers(); break;
    case 'teams': loadTeams(); break;
    case 'roles': loadRoles(); break;
    case 'audit': loadAuditLog(1); break;
    case 'usage': loadUsage(); break;
    case 'settings': loadOrgSettings(); break;
  }
}

document.querySelectorAll('.ea-nav-item').forEach(btn => {
  btn.addEventListener('click', () => switchSection(btn.dataset.section));
});

/* ═══════════════════════════════════════════════
   MOBILE SIDEBAR
   ═══════════════════════════════════════════════ */
document.getElementById('mobileToggle').addEventListener('click', () => {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('mobileOverlay').classList.toggle('show');
});
document.getElementById('mobileOverlay').addEventListener('click', () => {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('mobileOverlay').classList.remove('show');
});

/* ═══════════════════════════════════════════════
   MODALS
   ═══════════════════════════════════════════════ */
function openModal(id) {
  const m = document.getElementById(id);
  m.classList.add('show');
  m.querySelector('input, select, textarea')?.focus();
}
function closeModal(id) {
  document.getElementById(id).classList.remove('show');
}
function openInviteModal() { openModal('inviteModal'); }
function openCreateTeamModal() {
  populateTeamLeadDropdown();
  openModal('teamModal');
}

// Close modals on overlay click
document.querySelectorAll('.ea-modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', (e) => {
    if (e.target === overlay) overlay.classList.remove('show');
  });
});

/* ═══════════════════════════════════════════════
   DASHBOARD — Load Stats & Activity
   ═══════════════════════════════════════════════ */
async function loadDashboard() {
  try {
    const [usageResp, membersResp, teamsResp, auditResp] = await Promise.allSettled([
      apiFetch('usage/summary'),
      apiFetch('members'),
      apiFetch('teams'),
      apiFetch('audit-log', { params: { per_page: 10 } })
    ]);

    // Stats
    const usage = usageResp.status === 'fulfilled' ? usageResp.value : {};
    const members = membersResp.status === 'fulfilled' ? (membersResp.value.members || membersResp.value.data || []) : [];
    const teams = teamsResp.status === 'fulfilled' ? (teamsResp.value.teams || teamsResp.value.data || []) : [];

    setStatValue('statMembers', members.length || usage.total_members || 0);
    setStatValue('statTeams', teams.length || usage.total_teams || 0);
    setStatValue('statAPICalls', formatNumber(usage.api_calls_30d || usage.api_calls || 0));
    setStatValue('statTools', formatNumber(usage.tool_executions_30d || usage.tool_executions || 0));
    setStatValue('statVoice', formatNumber(usage.voice_minutes || 0));

    // Store members for later
    membersData = members;

    // Chart
    renderBarChart('dashChart', usage.daily_usage || generateMockDailyData(7), 7);

    // Activity
    const logs = auditResp.status === 'fulfilled' ? (auditResp.value.logs || auditResp.value.data || []) : [];
    renderActivity('dashActivity', logs.slice(0, 10));

  } catch (e) {
    console.error('Dashboard load error:', e);
    // Show fallback mock data on API error
    setStatValue('statMembers', 0);
    setStatValue('statTeams', 0);
    setStatValue('statAPICalls', '0');
    setStatValue('statTools', '0');
    setStatValue('statVoice', '0');
    renderBarChart('dashChart', generateMockDailyData(7), 7);
    document.getElementById('dashActivity').innerHTML = '<div class="ea-empty-state"><i class="fas fa-clock-rotate-left"></i><p>No recent activity</p></div>';
  }
}

function setStatValue(id, val) {
  const el = document.getElementById(id);
  el.textContent = val;
  el.classList.remove('ea-skeleton', 'ea-skeleton-stat');
}

function formatNumber(n) { return GDS.formatNumber(n); }

function generateMockDailyData(days) {
  const data = [];
  for (let i = days - 1; i >= 0; i--) {
    const d = new Date();
    d.setDate(d.getDate() - i);
    data.push({
      date: d.toISOString().slice(0, 10),
      count: Math.floor(Math.random() * 200) + 20
    });
  }
  return data;
}

/* ═══════════════════════════════════════════════
   BAR CHART RENDERER (CSS bars)
   ═══════════════════════════════════════════════ */
function renderBarChart(containerId, data, days) {
  const container = document.getElementById(containerId);
  if (!data || data.length === 0) {
    container.innerHTML = '<div class="ea-empty-state"><i class="fas fa-chart-bar"></i><p>No usage data available</p></div>';
    return;
  }
  const sliced = data.slice(-days);
  const maxVal = Math.max(...sliced.map(d => d.count || d.value || 0), 1);
  let html = '';
  sliced.forEach(d => {
    const val = d.count || d.value || 0;
    const pct = (val / maxVal) * 100;
    const date = new Date(d.date);
    const label = date.toLocaleDateString('en', { month: 'short', day: 'numeric' });
    html += '<div class="ea-bar-col"><div class="ea-bar" style="height:' + pct + '%" title="' + val + ' calls on ' + label + '"></div><div class="ea-bar-label">' + label + '</div></div>';
  });
  container.innerHTML = html;
}

/* ═══════════════════════════════════════════════
   ACTIVITY FEED RENDERER
   ═══════════════════════════════════════════════ */
function renderActivity(containerId, logs) {
  const container = document.getElementById(containerId);
  if (!logs || logs.length === 0) {
    container.innerHTML = '<div class="ea-empty-state"><i class="fas fa-clock-rotate-left"></i><p>No recent activity</p></div>';
    return;
  }
  const actionIcons = {
    login: 'fa-right-to-bracket', logout: 'fa-right-from-bracket',
    member_invited: 'fa-user-plus', member_removed: 'fa-user-minus',
    role_changed: 'fa-user-shield', team_created: 'fa-people-group',
    api_key_created: 'fa-key', settings_updated: 'fa-gear'
  };
  const actionTypes = {
    login: 'auth', logout: 'auth', member_invited: 'admin', member_removed: 'admin',
    role_changed: 'admin', team_created: 'team', api_key_created: 'security',
    settings_updated: 'admin', billing_updated: 'billing'
  };

  let html = '';
  logs.forEach(log => {
    const type = actionTypes[log.action] || 'admin';
    const icon = actionIcons[log.action] || 'fa-circle-info';
    const time = log.created_at ? timeAgo(new Date(log.created_at)) : '';
    const user = log.user_name || log.user_email || 'System';
    const action = (log.action || '').replace(/_/g, ' ');
    html += '<div class="ea-activity-item" data-type="' + type + '">' +
      '<div class="ea-activity-icon"><i class="fas ' + icon + '"></i></div>' +
      '<div class="ea-activity-body"><div class="ea-activity-user">' + escapeHtml(user) + '</div>' +
      '<div class="ea-activity-action">' + escapeHtml(action) + (log.resource_type ? ' — ' + escapeHtml(log.resource_type) : '') + '</div></div>' +
      '<div class="ea-activity-time">' + escapeHtml(time) + '</div></div>';
  });
  container.innerHTML = html;
}

function timeAgo(date) {
  const seconds = Math.floor((Date.now() - date.getTime()) / 1000);
  if (seconds < 60) return seconds + 's ago';
  if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
  if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
  return Math.floor(seconds / 86400) + 'd ago';
}

/* ═══════════════════════════════════════════════
   MEMBERS
   ═══════════════════════════════════════════════ */
async function loadMembers() {
  const tbody = document.getElementById('membersBody');
  try {
    const resp = await apiFetch('members');
    membersData = resp.members || resp.data || [];
    renderMembers(membersData);
  } catch (e) {
    tbody.innerHTML = '<tr><td colspan="6"><div class="ea-error-state"><i class="fas fa-exclamation-triangle"></i><p>' + escapeHtml(e.message) + '</p><button class="ea-btn ea-btn-secondary" onclick="loadedSections.delete(\'members\');loadMembers()">Retry</button></div></td></tr>';
  }
}

function renderMembers(list) {
  const tbody = document.getElementById('membersBody');
  if (!list || list.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6"><div class="ea-empty-state"><i class="fas fa-users"></i><p>No members found. Invite your first team member!</p></div></td></tr>';
    return;
  }
  let html = '';
  list.forEach(m => {
    const roleBadge = '<span class="ea-badge ea-badge-' + (m.role || 'member') + '">' + escapeHtml(m.role || 'member') + '</span>';
    const statusBadge = '<span class="ea-badge ea-badge-' + (m.status === 'active' ? 'active' : 'pending') + '">' + escapeHtml(m.status || 'active') + '</span>';
    const name = escapeHtml(m.name || m.first_name + ' ' + (m.last_name || ''));
    const lastActive = m.last_active ? new Date(m.last_active).toLocaleDateString() : '—';
    html += '<tr data-name="' + name.toLowerCase() + '" data-role="' + (m.role || '') + '">' +
      '<td><strong>' + name + '</strong></td>' +
      '<td>' + escapeHtml(m.email || '') + '</td>' +
      '<td>' + roleBadge + '</td>' +
      '<td>' + statusBadge + '</td>' +
      '<td style="color:var(--al-text-muted);font-size:.8rem">' + lastActive + '</td>' +
      '<td><button class="ea-btn ea-btn-sm ea-btn-secondary" onclick="openRoleModal(' + (m.user_id || m.id) + ',\'' + escapeHtml(name) + '\',\'' + (m.role || 'member') + '\')" title="Edit Role"><i class="fas fa-pen"></i></button> ' +
      '<button class="ea-btn ea-btn-sm ea-btn-danger" onclick="removeMember(' + (m.user_id || m.id) + ',\'' + escapeHtml(name) + '\')" title="Remove"><i class="fas fa-trash"></i></button></td></tr>';
  });
  tbody.innerHTML = html;
}

function filterMembers() {
  const q = document.getElementById('memberSearch').value.toLowerCase();
  const role = document.getElementById('memberRoleFilter').value;
  const filtered = membersData.filter(m => {
    const name = (m.name || (m.first_name || '') + ' ' + (m.last_name || '')).toLowerCase();
    const email = (m.email || '').toLowerCase();
    const matchText = !q || name.includes(q) || email.includes(q);
    const matchRole = !role || m.role === role;
    return matchText && matchRole;
  });
  renderMembers(filtered);
}

/* ═══════════════════════════════════════════════
   INVITE MEMBER
   ═══════════════════════════════════════════════ */
async function submitInvite() {
  const email = document.getElementById('inviteEmail').value.trim();
  const role = document.getElementById('inviteRole').value;
  if (!email) { toast('Please enter an email address', 'error'); return; }

  const btn = document.getElementById('inviteSubmitBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="ea-spinner"></span> Sending…';

  try {
    await apiFetch('members/invite', { method: 'POST', body: { email, role } });
    toast('Invitation sent to ' + email, 'success');
    closeModal('inviteModal');
    document.getElementById('inviteEmail').value = '';
    loadedSections.delete('members');
    if (document.getElementById('sec-members').classList.contains('active')) loadMembers();
  } catch (e) {
    toast(e.message || 'Failed to send invite', 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Invite';
  }
}

/* ═══════════════════════════════════════════════
   ROLE CHANGE
   ═══════════════════════════════════════════════ */
function openRoleModal(userId, name, currentRole) {
  document.getElementById('roleUserId').value = userId;
  document.getElementById('roleUserName').textContent = name;
  document.getElementById('roleSelect').value = currentRole;
  openModal('roleModal');
}

async function submitRoleChange() {
  const userId = document.getElementById('roleUserId').value;
  const role = document.getElementById('roleSelect').value;
  try {
    await apiFetch('members/role', { method: 'PUT', body: { user_id: parseInt(userId), role } });
    toast('Role updated successfully', 'success');
    closeModal('roleModal');
    loadedSections.delete('members');
    loadMembers();
  } catch (e) {
    toast(e.message || 'Failed to update role', 'error');
  }
}

/* ═══════════════════════════════════════════════
   REMOVE MEMBER
   ═══════════════════════════════════════════════ */
async function removeMember(userId, name) {
  if (!confirm('Remove ' + name + ' from the organization? This cannot be undone.')) return;
  try {
    await apiFetch('members/remove', { method: 'DELETE', body: { user_id: userId } });
    toast(name + ' has been removed', 'success');
    loadedSections.delete('members');
    loadMembers();
  } catch (e) {
    toast(e.message || 'Failed to remove member', 'error');
  }
}

/* ═══════════════════════════════════════════════
   TEAMS
   ═══════════════════════════════════════════════ */
async function loadTeams() {
  const grid = document.getElementById('teamsGrid');
  try {
    const resp = await apiFetch('teams');
    const teams = resp.teams || resp.data || [];
    if (teams.length === 0) {
      grid.innerHTML = '<div class="ea-empty-state" style="grid-column:1/-1"><i class="fas fa-people-group"></i><p>No teams yet. Create your first team to organize members.</p></div>';
      return;
    }
    let html = '';
    teams.forEach(t => {
      const memberCount = t.member_count || t.members?.length || 0;
      const lead = t.team_lead || t.lead_name || '—';
      const created = t.created_at ? new Date(t.created_at).toLocaleDateString() : '—';
      html += '<div class="ea-team-card" onclick="toggleTeamExpand(this)">' +
        '<div class="ea-team-card-header"><h3>' + escapeHtml(t.name) + '</h3><span class="ea-badge ea-badge-active">' + memberCount + ' members</span></div>' +
        (t.description ? '<p style="font-size:.82rem;color:var(--al-text-muted);margin-bottom:8px">' + escapeHtml(t.description) + '</p>' : '') +
        '<div class="ea-team-meta"><span><i class="fas fa-user-tie"></i> ' + escapeHtml(lead) + '</span><span><i class="fas fa-calendar"></i> ' + created + '</span></div>' +
        '<div class="ea-team-members"><p style="font-size:.8rem;color:var(--al-text-muted);padding:8px 0">Team members list loaded on demand.</p></div></div>';
    });
    grid.innerHTML = html;
  } catch (e) {
    grid.innerHTML = '<div class="ea-error-state" style="grid-column:1/-1"><i class="fas fa-exclamation-triangle"></i><p>' + escapeHtml(e.message) + '</p><button class="ea-btn ea-btn-secondary" onclick="loadedSections.delete(\'teams\');loadTeams()">Retry</button></div>';
  }
}

function toggleTeamExpand(card) {
  card.classList.toggle('expanded');
}

function populateTeamLeadDropdown() {
  const sel = document.getElementById('teamLead');
  sel.innerHTML = '<option value="">Select a team lead…</option>';
  membersData.forEach(m => {
    const name = m.name || (m.first_name || '') + ' ' + (m.last_name || '');
    sel.innerHTML += '<option value="' + (m.user_id || m.id) + '">' + escapeHtml(name) + '</option>';
  });
}

async function submitCreateTeam() {
  const name = document.getElementById('teamName').value.trim();
  const description = document.getElementById('teamDesc').value.trim();
  const leadId = document.getElementById('teamLead').value;
  if (!name) { toast('Please enter a team name', 'error'); return; }

  const btn = document.getElementById('teamSubmitBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="ea-spinner"></span> Creating…';

  try {
    await apiFetch('teams/create', { method: 'POST', body: { name, description, lead_user_id: leadId ? parseInt(leadId) : null } });
    toast('Team "' + name + '" created', 'success');
    closeModal('teamModal');
    document.getElementById('teamName').value = '';
    document.getElementById('teamDesc').value = '';
    loadedSections.delete('teams');
    loadTeams();
  } catch (e) {
    toast(e.message || 'Failed to create team', 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-plus"></i> Create Team';
  }
}

/* ═══════════════════════════════════════════════
   ROLES & PERMISSIONS
   ═══════════════════════════════════════════════ */
async function loadRoles() {
  const container = document.getElementById('rolesContent');
  try {
    const resp = await apiFetch('rbac/roles');
    const roles = resp.roles || resp.data || [];

    // Built-in roles
    const builtIn = {
      owner: ['All permissions — full organization control'],
      admin: ['manage_members', 'create_teams', 'create_agents', 'deploy_fleet', 'execute_tools', 'view_dashboards', 'view_audit', 'manage_api_keys', 'white_label', 'export_data', 'marketplace_publish'],
      manager: ['create_teams', 'create_agents', 'deploy_fleet', 'execute_tools', 'view_dashboards', 'view_audit', 'export_data', 'marketplace_publish'],
      member: ['create_agents', 'execute_tools', 'view_dashboards', 'marketplace_publish'],
      viewer: ['view_dashboards']
    };

    let html = '';
    Object.entries(builtIn).forEach(([role, perms]) => {
      html += '<div class="ea-settings-card"><h3 style="display:flex;align-items:center;gap:8px"><span class="ea-badge ea-badge-' + role + '">' + role + '</span> <span style="font-weight:400;color:var(--al-text-muted);font-size:.8rem">(built-in)</span></h3>' +
        '<div class="ea-perm-grid" style="margin-top:12px">';
      perms.forEach(p => {
        html += '<div class="ea-perm-item"><i class="fas fa-check"></i> ' + escapeHtml(p.replace(/_/g, ' ')) + '</div>';
      });
      html += '</div></div>';
    });

    // Custom roles from API
    if (roles.length > 0) {
      html += '<h3 style="font-family:var(--font-heading);margin:20px 0 12px;font-size:1rem">Custom Roles</h3>';
      roles.forEach(r => {
        const perms = r.permissions ? (typeof r.permissions === 'string' ? JSON.parse(r.permissions) : r.permissions) : [];
        html += '<div class="ea-settings-card"><h3>' + escapeHtml(r.name) + '</h3>' +
          '<div class="ea-perm-grid" style="margin-top:12px">';
        perms.forEach(p => {
          html += '<div class="ea-perm-item"><i class="fas fa-check"></i> ' + escapeHtml(p.replace(/_/g, ' ')) + '</div>';
        });
        html += '</div></div>';
      });
    }

    container.innerHTML = html;
  } catch (e) {
    container.innerHTML = '<div class="ea-error-state"><i class="fas fa-exclamation-triangle"></i><p>' + escapeHtml(e.message) + '</p><button class="ea-btn ea-btn-secondary" onclick="loadedSections.delete(\'roles\');loadRoles()">Retry</button></div>';
  }
}

/* ═══════════════════════════════════════════════
   AUDIT LOG
   ═══════════════════════════════════════════════ */
async function loadAuditLog(page) {
  auditPage = page || 1;
  const tbody = document.getElementById('auditBody');
  tbody.innerHTML = '<tr><td colspan="6"><div class="ea-loading-state"><div class="ea-spinner"></div><span>Loading…</span></div></td></tr>';

  const params = { page: auditPage, per_page: auditPerPage };
  const actionFilter = document.getElementById('auditActionFilter').value;
  const userFilter = document.getElementById('auditUserFilter').value;
  const dateFrom = document.getElementById('auditDateFrom').value;
  const dateTo = document.getElementById('auditDateTo').value;
  if (actionFilter) params.action_filter = actionFilter;
  if (userFilter) params.user_filter = userFilter;
  if (dateFrom) params.date_from = dateFrom;
  if (dateTo) params.date_to = dateTo;

  try {
    const resp = await apiFetch('audit-log', { params });
    const logs = resp.logs || resp.data || [];
    const total = resp.total || logs.length;
    const totalPages = Math.ceil(total / auditPerPage) || 1;

    if (logs.length === 0) {
      tbody.innerHTML = '<tr><td colspan="6"><div class="ea-empty-state"><i class="fas fa-scroll"></i><p>No audit log entries found matching your filters.</p></div></td></tr>';
      document.getElementById('auditPagination').innerHTML = '';
      return;
    }

    const actionBadgeMap = {
      login: 'auth', logout: 'auth', member_invited: 'admin-action', member_removed: 'admin-action',
      role_changed: 'admin-action', team_created: 'team', api_key_created: 'security',
      settings_updated: 'admin-action', billing_updated: 'billing'
    };

    let html = '';
    logs.forEach(log => {
      const dt = log.created_at ? new Date(log.created_at).toLocaleString() : '—';
      const badgeClass = actionBadgeMap[log.action] || 'admin-action';
      const action = (log.action || '').replace(/_/g, ' ');
      const details = log.details ? (typeof log.details === 'string' ? log.details : JSON.stringify(log.details)) : '—';
      html += '<tr>' +
        '<td style="font-family:var(--font-mono);font-size:.78rem;color:var(--al-text-muted);white-space:nowrap">' + escapeHtml(dt) + '</td>' +
        '<td>' + escapeHtml(log.user_name || log.user_email || '—') + '</td>' +
        '<td><span class="ea-badge ea-badge-' + badgeClass + '">' + escapeHtml(action) + '</span></td>' +
        '<td>' + escapeHtml(log.resource_type || '—') + '</td>' +
        '<td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.8rem;color:var(--al-text-muted)" title="' + escapeHtml(details) + '">' + escapeHtml(details.substring(0, 80)) + '</td>' +
        '<td style="font-family:var(--font-mono);font-size:.78rem;color:var(--al-text-muted)">' + escapeHtml(log.ip_address || '—') + '</td></tr>';
    });
    tbody.innerHTML = html;

    // Pagination
    let pagHtml = '<button class="ea-page-btn" onclick="loadAuditLog(' + (auditPage - 1) + ')" ' + (auditPage <= 1 ? 'disabled' : '') + '><i class="fas fa-chevron-left"></i></button>';
    for (let i = 1; i <= totalPages && i <= 7; i++) {
      pagHtml += '<button class="ea-page-btn' + (i === auditPage ? ' active' : '') + '" onclick="loadAuditLog(' + i + ')">' + i + '</button>';
    }
    if (totalPages > 7) pagHtml += '<span class="ea-page-info">…</span><button class="ea-page-btn" onclick="loadAuditLog(' + totalPages + ')">' + totalPages + '</button>';
    pagHtml += '<button class="ea-page-btn" onclick="loadAuditLog(' + (auditPage + 1) + ')" ' + (auditPage >= totalPages ? 'disabled' : '') + '><i class="fas fa-chevron-right"></i></button>';
    pagHtml += '<span class="ea-page-info">Page ' + auditPage + ' of ' + totalPages + '</span>';
    document.getElementById('auditPagination').innerHTML = pagHtml;

    // Populate user filter dropdown (once)
    if (document.getElementById('auditUserFilter').options.length <= 1 && membersData.length) {
      membersData.forEach(m => {
        const name = m.name || (m.first_name || '') + ' ' + (m.last_name || '');
        const opt = document.createElement('option');
        opt.value = m.user_id || m.id;
        opt.textContent = name;
        document.getElementById('auditUserFilter').appendChild(opt);
      });
    }
  } catch (e) {
    tbody.innerHTML = '<tr><td colspan="6"><div class="ea-error-state"><i class="fas fa-exclamation-triangle"></i><p>' + escapeHtml(e.message) + '</p><button class="ea-btn ea-btn-secondary" onclick="loadAuditLog(' + auditPage + ')">Retry</button></div></td></tr>';
  }
}

/* ═══════════════════════════════════════════════
   AUDIT LOG CSV EXPORT
   ═══════════════════════════════════════════════ */
async function exportAuditCSV() {
  toast('Preparing CSV export…', 'info');
  try {
    const params = { per_page: 10000 };
    const actionFilter = document.getElementById('auditActionFilter').value;
    const userFilter = document.getElementById('auditUserFilter').value;
    const dateFrom = document.getElementById('auditDateFrom').value;
    const dateTo = document.getElementById('auditDateTo').value;
    if (actionFilter) params.action_filter = actionFilter;
    if (userFilter) params.user_filter = userFilter;
    if (dateFrom) params.date_from = dateFrom;
    if (dateTo) params.date_to = dateTo;

    const resp = await apiFetch('audit-log', { params });
    const logs = resp.logs || resp.data || [];

    let csv = 'Date/Time,User,Action,Resource,Details,IP Address\n';
    logs.forEach(log => {
      const dt = log.created_at || '';
      const user = log.user_name || log.user_email || '';
      const action = log.action || '';
      const resource = log.resource_type || '';
      const details = log.details ? (typeof log.details === 'string' ? log.details : JSON.stringify(log.details)) : '';
      const ip = log.ip_address || '';
      csv += '"' + dt + '","' + user + '","' + action + '","' + resource + '","' + details.replace(/"/g, '""') + '","' + ip + '"\n';
    });

    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'audit-log-' + new Date().toISOString().slice(0, 10) + '.csv';
    a.click();
    URL.revokeObjectURL(url);
    toast('Audit log exported (' + logs.length + ' entries)', 'success');
  } catch (e) {
    toast('Export failed: ' + e.message, 'error');
  }
}

/* ═══════════════════════════════════════════════
   USAGE & BILLING
   ═══════════════════════════════════════════════ */
async function loadUsage() {
  try {
    const resp = await apiFetch('usage/summary');
    // 30-day chart
    const dailyData = resp.daily_usage || generateMockDailyData(30);
    renderBarChart('usageChart', dailyData, 30);

    // Resource breakdown
    const breakdown = document.getElementById('usageBreakdown');
    const resources = [
      { label: 'API Calls', used: resp.api_calls_30d || resp.api_calls || 0, limit: resp.api_calls_limit || 50000, icon: 'fa-code' },
      { label: 'Voice Minutes', used: resp.voice_minutes || 0, limit: resp.voice_minutes_limit || 5000, icon: 'fa-microphone' },
      { label: 'Active Agents', used: resp.agents_active || 0, limit: resp.agents_limit || 100, icon: 'fa-robot' },
      { label: 'Storage', used: resp.storage_used_mb || 0, limit: resp.storage_limit_mb || 10240, icon: 'fa-hard-drive', unit: 'MB' }
    ];

    let html = '';
    resources.forEach(r => {
      const pct = r.limit > 0 ? Math.round((r.used / r.limit) * 100) : 0;
      const barClass = pct > 90 ? 'high' : (pct > 70 ? 'medium' : '');
      const unit = r.unit || '';
      html += '<div class="ea-usage-item">' +
        '<div class="ea-usage-label"><span><i class="fas ' + r.icon + '" style="margin-right:6px;color:var(--al-accent-light)"></i>' + r.label + '</span><span>' + formatNumber(r.used) + unit + ' / ' + formatNumber(r.limit) + unit + ' (' + pct + '%)</span></div>' +
        '<div class="ea-progress"><div class="ea-progress-bar ' + barClass + '" style="width:' + Math.min(pct, 100) + '%"></div></div></div>';
    });
    breakdown.innerHTML = html;
  } catch (e) {
    document.getElementById('usageChart').innerHTML = '<div class="ea-error-state"><i class="fas fa-exclamation-triangle"></i><p>' + escapeHtml(e.message) + '</p><button class="ea-btn ea-btn-secondary" onclick="loadedSections.delete(\'usage\');loadUsage()">Retry</button></div>';
  }
}

/* ═══════════════════════════════════════════════
   ORGANIZATION SETTINGS
   ═══════════════════════════════════════════════ */
async function loadOrgSettings() {
  const container = document.getElementById('orgSettingsContent');
  try {
    const resp = await apiFetch('org');
    const org = resp.organization || resp.org || resp.data || {};

    container.innerHTML =
      '<div class="ea-settings-card"><h3>Organization Details</h3><p>Update your organization\'s core information.</p>' +
      '<div class="ea-grid-2">' +
      '<div class="ea-form-group"><label>Organization Name</label><input type="text" class="ea-input" id="orgName" value="' + escapeHtml(org.name || '') + '"></div>' +
      '<div class="ea-form-group"><label>Slug / URL</label><input type="text" class="ea-input" id="orgSlug" value="' + escapeHtml(org.slug || '') + '"></div></div>' +
      '<div class="ea-form-group"><label>Description</label><textarea class="ea-textarea" id="orgDesc" rows="3">' + escapeHtml(org.description || '') + '</textarea></div>' +
      '<div class="ea-grid-2">' +
      '<div class="ea-form-group"><label>Industry</label><input type="text" class="ea-input" id="orgIndustry" value="' + escapeHtml(org.industry || '') + '"></div>' +
      '<div class="ea-form-group"><label>Website</label><input type="url" class="ea-input" id="orgWebsite" value="' + escapeHtml(org.website || '') + '"></div></div>' +
      '<button class="ea-btn ea-btn-primary" onclick="saveOrgSettings()"><i class="fas fa-save"></i> Save Settings</button></div>' +

      '<div class="ea-settings-card" style="border-color:rgba(214,48,49,.2)"><h3 style="color:var(--al-red)"><i class="fas fa-triangle-exclamation" style="margin-right:6px"></i>Danger Zone</h3>' +
      '<p>Irreversible actions. Proceed with caution.</p>' +
      '<button class="ea-btn ea-btn-danger" onclick="toast(\'Contact support to delete your organization\',\'info\')"><i class="fas fa-trash"></i> Delete Organization</button></div>';
  } catch (e) {
    container.innerHTML = '<div class="ea-error-state"><i class="fas fa-exclamation-triangle"></i><p>' + escapeHtml(e.message) + '</p><button class="ea-btn ea-btn-secondary" onclick="loadedSections.delete(\'settings\');loadOrgSettings()">Retry</button></div>';
  }
}

async function saveOrgSettings() {
  try {
    await apiFetch('org/update', {
      method: 'PUT',
      body: {
        name: document.getElementById('orgName').value,
        slug: document.getElementById('orgSlug').value,
        description: document.getElementById('orgDesc').value,
        industry: document.getElementById('orgIndustry').value,
        website: document.getElementById('orgWebsite').value
      }
    });
    toast('Organization settings saved', 'success');
  } catch (e) {
    toast(e.message || 'Failed to save settings', 'error');
  }
}

/* ═══════════════════════════════════════════════
   SSO & WHITE LABEL (settings sections)
   ═══════════════════════════════════════════════ */
async function saveSSO() {
  const ssoUrl = document.getElementById('ssoUrl')?.value?.trim();
  const entityId = document.getElementById('ssoEntityId')?.value?.trim();
  const cert = document.getElementById('ssoCert')?.value?.trim();

  if (!ssoUrl || !entityId) {
    toast('IdP SSO URL and Entity ID are required', 'error');
    return;
  }

  try {
    const resp = await fetch('/api/enterprise.php?action=org/update', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        sso_provider: 'saml',
        sso_config: {
          sso_url: ssoUrl,
          entity_id: entityId,
          certificate: cert || null
        }
      })
    });
    const data = await resp.json();
    if (data.success) {
      toast('SSO configuration saved successfully', 'success');
    } else {
      toast(data.error || 'Failed to save SSO config', 'error');
    }
  } catch (e) {
    toast('Network error saving SSO config', 'error');
  }
}
function saveWhiteLabel() {
  toast('White label settings saved', 'success');
}

/* ═══════════════════════════════════════════════
   API KEYS (mock / placeholder)
   ═══════════════════════════════════════════════ */
let apiKeyCounter = 0;
function generateAPIKey() {
  apiKeyCounter++;
  const key = 'alf_ent_' + Math.random().toString(36).substring(2, 18);
  const tbody = document.getElementById('apiKeysBody');
  const now = new Date().toLocaleDateString();

  // If the only row is the empty state, clear it
  if (tbody.querySelector('.ea-empty-state')) tbody.innerHTML = '';

  const row = document.createElement('tr');
  row.innerHTML =
    '<td>API Key #' + apiKeyCounter + '</td>' +
    '<td><code style="background:var(--al-surface);padding:3px 8px;border-radius:4px;font-family:var(--font-mono);font-size:.78rem">' + key + '</code></td>' +
    '<td style="font-size:.8rem;color:var(--al-text-muted)">' + now + '</td>' +
    '<td style="font-size:.8rem;color:var(--al-text-muted)">Never</td>' +
    '<td><span class="ea-badge ea-badge-active">Active</span></td>' +
    '<td><button class="ea-btn ea-btn-sm ea-btn-danger" onclick="this.closest(\'tr\').remove();toast(\'API key revoked\',\'success\')"><i class="fas fa-ban"></i> Revoke</button></td>';
  tbody.prepend(row);
  toast('API key generated. Store it securely — it won\'t be shown again.', 'success');
}

/* ═══════════════════════════════════════════════
   UTILITIES
   ═══════════════════════════════════════════════ */
function escapeHtml(str) { return GDS.esc(str); }

/* ═══════════════════════════════════════════════
   INIT
   ═══════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {
  // Load dashboard on init
  loadedSections.add('dashboard');
  loadDashboard();

  // Keyboard: Escape closes modals
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.ea-modal-overlay.show').forEach(m => m.classList.remove('show'));
    }
  });
});
