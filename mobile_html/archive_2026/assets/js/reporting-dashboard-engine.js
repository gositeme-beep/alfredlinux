const API = '/api/reporting-engine.php';
function fmt(n) { return n === null || n === undefined || isNaN(n) ? '--' : '$' + Number(n).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}); }

async function rGet(action, params = '') {
    try { const r = await fetch(`${API}?action=${action}${params}`, {credentials:'same-origin'}); return await r.json(); }
    catch(e) { console.warn('API error:', action, e); return {success:false}; }
}

async function loadKPIs() {
    const d = await rGet('dashboard_kpis');
    if (d.success && d.kpis) {
        const k = d.kpis;
        document.getElementById('kpi-convos').textContent = Number(k.total_conversations || 0).toLocaleString();
        document.getElementById('kpi-tools').textContent = Number(k.tool_calls_30d || 0).toLocaleString();
        document.getElementById('kpi-users').textContent = Number(k.active_users_30d || 0).toLocaleString();
        document.getElementById('kpi-revenue').textContent = fmt(k.revenue_mtd);
        const gr = k.growth_rate || 0;
        document.getElementById('kpi-growth').textContent = (gr >= 0 ? '+' : '') + gr + '%';
        document.getElementById('kpi-growth').style.color = gr >= 0 ? 'var(--rp-green)' : 'var(--rp-red)';
    }
}

async function loadUsage(period = 'daily', btn = null) {
    if (btn) { document.querySelectorAll('.rp-tab').forEach(t => t.classList.remove('active')); btn.classList.add('active'); }
    const d = await rGet('usage_report', `&period=${period}&days=30`);
    const el = document.getElementById('usage-chart');
    if (d.success && d.data) {
        const data = d.data.daily_conversations || d.data.daily || [];
        if (data.length > 0) {
            const maxVal = Math.max(...data.map(r => r.count || r.tool_calls || 1), 1);
            el.innerHTML = data.map(r => {
                const v = r.count || r.tool_calls || 0;
                const h = Math.max(8, (v / maxVal) * 180);
                return `<div class="rp-bar" style="height:${h}px" title="${r.date || r.period}: ${v}"><div class="rp-bar-label">${(r.date || r.period || '').slice(-5)}</div></div>`;
            }).join('');
        } else { el.innerHTML = '<div style="color:var(--rp-muted);padding:2rem;text-align:center;">No usage data yet</div>'; }
    } else { el.innerHTML = '<div style="color:var(--rp-muted);padding:2rem;text-align:center;">No usage data yet</div>'; }
}

async function loadToolUsage() {
    const d = await rGet('tool_usage', '&limit=10');
    const el = document.getElementById('tool-usage');
    if (d.success && d.tools && d.tools.length > 0) {
        el.innerHTML = `<table class="rp-table"><thead><tr><th>Tool</th><th>Calls</th><th>Avg Time</th></tr></thead><tbody>${
            d.tools.map(t => `<tr><td>${t.tool_name}</td><td>${Number(t.call_count).toLocaleString()}</td><td>${t.avg_duration || '--'}ms</td></tr>`).join('')
        }</tbody></table>`;
    } else { el.innerHTML = '<div style="color:var(--rp-muted);text-align:center;padding:1rem;">No tool data yet</div>'; }
}

async function loadAgentPerf() {
    const d = await rGet('agent_performance');
    const el = document.getElementById('agent-perf');
    if (d.success && d.agents && d.agents.length > 0) {
        el.innerHTML = `<table class="rp-table"><thead><tr><th>Agent</th><th>Tasks</th><th>Success</th></tr></thead><tbody>${
            d.agents.map(a => `<tr><td>${a.agent_name}</td><td>${a.tasks_completed || 0}</td><td style="color:var(--rp-green)">${a.success_rate || '--'}%</td></tr>`).join('')
        }</tbody></table>`;
    } else { el.innerHTML = '<div style="color:var(--rp-muted);text-align:center;padding:1rem;">No agent data yet</div>'; }
}

async function loadGrowth() {
    const d = await rGet('growth_metrics');
    const el = document.getElementById('growth-metrics');
    if (d.success && d.metrics) {
        const m = d.metrics;
        el.innerHTML = `<div style="display:grid;gap:.75rem;">
            <div style="display:flex;justify-content:space-between;"><span style="color:var(--rp-muted)">New Users (30d)</span><span style="font-weight:600">${m.new_users_30d || 0}</span></div>
            <div style="display:flex;justify-content:space-between;"><span style="color:var(--rp-muted)">User Retention</span><span style="font-weight:600;color:var(--rp-green)">${m.retention_rate || '--'}%</span></div>
            <div style="display:flex;justify-content:space-between;"><span style="color:var(--rp-muted)">MRR Growth</span><span style="font-weight:600;color:var(--rp-blue)">${m.mrr_growth || '--'}%</span></div>
            <div style="display:flex;justify-content:space-between;"><span style="color:var(--rp-muted)">Churn Rate</span><span style="font-weight:600;color:var(--rp-red)">${m.churn_rate || '--'}%</span></div>
        </div>`;
    } else { el.innerHTML = '<div style="color:var(--rp-muted);text-align:center;padding:1rem;">No growth data yet</div>'; }
}

async function loadSavedReports() {
    const d = await rGet('saved_reports');
    const el = document.getElementById('saved-reports');
    if (d.success && d.reports && d.reports.length > 0) {
        el.innerHTML = d.reports.slice(0, 5).map(r => `<div style="display:flex;justify-content:space-between;align-items:center;padding:.5rem 0;border-bottom:1px solid rgba(255,255,255,.05);">
            <span>${r.name}</span>
            <span style="color:var(--rp-muted);font-size:.8rem;">${r.created_at?.slice(0,10) || ''}</span>
        </div>`).join('');
    } else { el.innerHTML = '<div style="color:var(--rp-muted);text-align:center;padding:1rem;">No saved reports</div>'; }
}

async function loadConvoStats() {
    const d = await rGet('conversation_stats');
    const el = document.getElementById('convo-stats');
    if (d.success && d.stats) {
        const s = d.stats;
        el.innerHTML = `<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1rem;">
            <div><div style="color:var(--rp-muted);font-size:.75rem;text-transform:uppercase;">Total</div><div style="font-size:1.25rem;font-weight:700;">${Number(s.total || 0).toLocaleString()}</div></div>
            <div><div style="color:var(--rp-muted);font-size:.75rem;text-transform:uppercase;">Today</div><div style="font-size:1.25rem;font-weight:700;">${s.today || 0}</div></div>
            <div><div style="color:var(--rp-muted);font-size:.75rem;text-transform:uppercase;">Avg Length</div><div style="font-size:1.25rem;font-weight:700;">${s.avg_messages || '--'} msgs</div></div>
            <div><div style="color:var(--rp-muted);font-size:.75rem;text-transform:uppercase;">Avg Duration</div><div style="font-size:1.25rem;font-weight:700;">${s.avg_duration || '--'}s</div></div>
        </div>`;
    } else { el.innerHTML = '<div style="color:var(--rp-muted);text-align:center;padding:1rem;">No conversation data yet</div>'; }
}

async function exportReport() {
    const d = await rGet('export', '&format=csv&type=usage&days=30');
    if (d.success && d.csv) {
        const blob = new Blob([d.csv], { type: 'text/csv' });
        const a = document.createElement('a'); a.href = URL.createObjectURL(blob);
        a.download = `report_${new Date().toISOString().slice(0,10)}.csv`; a.click();
    } else { alert(d.error || 'Export not available'); }
}

function refreshAll() { loadKPIs(); loadUsage('daily'); loadToolUsage(); loadAgentPerf(); loadGrowth(); loadSavedReports(); loadConvoStats(); }
refreshAll();
