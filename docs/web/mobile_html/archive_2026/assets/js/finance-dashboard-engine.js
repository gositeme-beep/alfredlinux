const FIN_API = '/api/financial';

function fmt(n) {
    if (n === null || n === undefined || isNaN(n)) return '--';
    return '$' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

async function fGet(endpoint) {
    try {
        const r = await fetch(endpoint, { credentials: 'same-origin' });
        return await r.json();
    } catch (e) {
        console.warn('API error:', endpoint, e);
        return { success: false };
    }
}

// ── KPIs ──────────────────────────────────────────────────────
async function loadKPIs() {
    const d = await fGet(`${FIN_API}/analytics.php?action=dashboard_kpis`);
    if (d.success && d.kpis) {
        const k = d.kpis;
        document.getElementById('kpi-revenue').textContent = fmt(k.revenue_mtd);
        document.getElementById('kpi-balance').textContent = fmt(k.total_balance);
        document.getElementById('kpi-subs').textContent = k.active_subscriptions ?? '--';
        document.getElementById('kpi-payouts').textContent = fmt(k.pending_payouts);
        const gr = k.growth_rate ?? 0;
        const grEl = document.getElementById('kpi-growth-rate');
        grEl.textContent = gr + '%';
        grEl.className = 'fc-kpi-value' + (gr >= 0 ? '' : '');
        const growthEl = document.getElementById('kpi-growth');
        growthEl.textContent = (gr >= 0 ? '↑' : '↓') + ' ' + Math.abs(gr) + '% vs last month';
        growthEl.className = 'fc-kpi-change ' + (gr >= 0 ? 'up' : 'down');
    }
}

// ── MRR ───────────────────────────────────────────────────────
async function loadMRR() {
    const d = await fGet(`${FIN_API}/analytics.php?action=mrr`);
    if (d.success) {
        document.getElementById('kpi-mrr').textContent = fmt(d.current_mrr);
    }
}

// ── Revenue Chart ─────────────────────────────────────────────
async function loadRevenueChart() {
    const d = await fGet(`${FIN_API}/analytics.php?action=revenue_trend&months=12`);
    const el = document.getElementById('revenue-chart');
    if (d.success && d.trend && d.trend.length > 0) {
        const maxRev = Math.max(...d.trend.map(t => parseFloat(t.revenue) || 0), 1);
        el.innerHTML = d.trend.map(t => {
            const h = Math.max(8, (parseFloat(t.revenue) / maxRev) * 180);
            return `<div class="fc-bar" style="height:${h}px" title="${t.month}: ${fmt(t.revenue)}"><div class="fc-bar-label">${t.month.slice(5)}</div></div>`;
        }).join('');
    } else {
        el.innerHTML = '<div style="color:var(--fc-muted);padding:2rem;text-align:center;">No revenue data yet</div>';
    }
}

// ── Payouts ───────────────────────────────────────────────────
async function loadPayouts() {
    const d = await fGet(`${FIN_API}/payouts.php?action=list&limit=5`);
    const el = document.getElementById('payouts-table');
    if (d.success && d.payouts && d.payouts.length > 0) {
        el.innerHTML = `<table class="fc-table"><thead><tr><th>Recipient</th><th>Amount</th><th>Status</th></tr></thead><tbody>${
            d.payouts.map(p => `<tr><td>${p.recipient_name || p.recipient_email || '--'}</td><td>${fmt(p.amount)}</td><td><span class="fc-status fc-status-${p.status}">${p.status}</span></td></tr>`).join('')
        }</tbody></table>`;
    } else {
        el.innerHTML = '<div style="color:var(--fc-muted);text-align:center;padding:1rem;">No payouts yet</div>';
    }
}

// ── Tax Deadlines ─────────────────────────────────────────────
async function loadTax() {
    const d = await fGet(`${FIN_API}/tax-compliance.php?action=upcoming&days=90`);
    const el = document.getElementById('tax-deadlines');
    const items = [...(d.overdue || []), ...(d.upcoming || [])];
    if (items.length > 0) {
        el.innerHTML = `<table class="fc-table"><thead><tr><th>Jurisdiction</th><th>Type</th><th>Due</th><th>Status</th></tr></thead><tbody>${
            items.slice(0, 5).map(t => `<tr><td>${t.jurisdiction}</td><td>${t.tax_type}</td><td>${t.due_date}</td><td><span class="fc-status fc-status-${t.status === 'overdue' ? 'failed' : 'pending'}">${t.status}</span></td></tr>`).join('')
        }</tbody></table>`;
    } else {
        el.innerHTML = '<div style="color:var(--fc-muted);text-align:center;padding:1rem;">No upcoming deadlines</div>';
    }
}

// ── Trading Orders ────────────────────────────────────────────
async function loadOrders() {
    const d = await fGet(`${FIN_API}/trading.php?action=order_history&limit=5`);
    const el = document.getElementById('orders-table');
    if (d.success && d.orders && d.orders.length > 0) {
        el.innerHTML = `<table class="fc-table"><thead><tr><th>Exchange</th><th>Pair</th><th>Side</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead><tbody>${
            d.orders.map(o => `<tr><td>${o.exchange}</td><td>${o.pair}</td><td>${o.side}</td><td>${o.amount}</td><td><span class="fc-status fc-status-${o.status === 'filled' ? 'completed' : 'pending'}">${o.status}</span></td><td>${o.created_at?.slice(0,10) || ''}</td></tr>`).join('')
        }</tbody></table>`;
    } else {
        el.innerHTML = '<div style="color:var(--fc-muted);text-align:center;padding:1rem;">No trading activity yet</div>';
    }
}

// ── Quick Actions Modal ───────────────────────────────────────
function showQuickActions() {
    const modal = document.createElement('div');
    modal.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.7);display:flex;align-items:center;justify-content:center;z-index:9999;';
    modal.innerHTML = `<div style="background:var(--fc-card);border:1px solid var(--fc-border);border-radius:1rem;padding:2rem;max-width:500px;width:90%;">
        <h2 style="margin:0 0 1rem;">Quick Actions</h2>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
            <a href="/api/financial/analytics.php?action=dashboard_kpis" target="_blank" class="fc-btn">📊 KPI Report</a>
            <a href="/api/financial/accounting.php?action=profit_loss" target="_blank" class="fc-btn">📈 P&L Report</a>
            <a href="/api/financial/accounting.php?action=balance_sheet" target="_blank" class="fc-btn">⚖️ Balance Sheet</a>
            <a href="/api/financial/analytics.php?action=forecast_revenue" target="_blank" class="fc-btn">🔮 Revenue Forecast</a>
            <a href="/api/financial/tax-compliance.php?action=gst_report" target="_blank" class="fc-btn">🇨🇦 GST Report</a>
            <a href="/api/financial/banking.php?action=all_balances" target="_blank" class="fc-btn">💰 All Balances</a>
            <a href="/api/financial/payouts.php?action=stats" target="_blank" class="fc-btn">💸 Payout Stats</a>
            <a href="/api/financial/trading.php?action=daily_limit" target="_blank" class="fc-btn">⚠️ Trade Limits</a>
        </div>
        <button class="fc-btn" style="margin-top:1rem;width:100%;justify-content:center;" onclick="this.closest('div').parentElement.remove()">Close</button>
    </div>`;
    modal.addEventListener('click', e => { if (e.target === modal) modal.remove(); });
    document.body.appendChild(modal);
}

// ── Load All ──────────────────────────────────────────────────
async function loadAgentWork() {
    try {
        const d = await fGet('/api/agent-freelance.php?action=stats');
        if (d.success && d.stats) {
            const s = d.stats;
            document.getElementById('aw-gigs').textContent = s.total_gigs ?? 0;
            document.getElementById('aw-projects').textContent = s.total_projects ?? 0;
            document.getElementById('aw-earnings').textContent = fmt(s.total_earnings ?? 0);
            document.getElementById('aw-fees').textContent = fmt(s.platform_fees ?? 0);
            document.getElementById('aw-balance').textContent = fmt(s.treasury_balance ?? 0);
            document.getElementById('aw-status').textContent = 'live';
            document.getElementById('aw-status').style.background = 'var(--fc-green)';
        }
        // Top earning agent
        const e = await fGet('/api/agent-freelance.php?action=agent-earnings');
        if (e.success && e.agents && e.agents.length > 0) {
            const top = e.agents[0];
            document.getElementById('aw-top-agent').textContent = (top.name ?? top.agent_id) + ' · ' + fmt(top.total_earned);
        }
    } catch (err) {
        document.getElementById('aw-status').textContent = 'offline';
        document.getElementById('aw-status').style.background = 'var(--fc-red)';
    }
}

async function loadEcosystemWallets() {
    const d = await fGet('/api/agent-economy.php?action=overview');
    const el = document.getElementById('wallets-grid');
    if (d.success) {
        const wallets = d.wallets || [];
        if (wallets.length > 0) {
            el.innerHTML = `<table class="fc-table"><thead><tr><th>Owner</th><th>Type</th><th>Balance</th><th>Currency</th></tr></thead><tbody>${
                wallets.map(w => `<tr><td>${w.owner_type}</td><td>${w.wallet_type}</td><td style="font-weight:600">${fmt(w.balance)}</td><td>${w.currency || 'USD'}</td></tr>`).join('')
            }</tbody></table>`;
        } else {
            el.innerHTML = `<div style="text-align:center;color:var(--fc-muted);padding:1rem;">
                <p>No wallets yet</p>
                <button class="fc-btn" onclick="seedEcosystem()">Seed Revenue Streams</button>
            </div>`;
        }
    } else {
        el.innerHTML = '<div style="color:var(--fc-muted);text-align:center;padding:1rem;">Economy API not available</div>';
    }
}

async function loadStreams() {
    const d = await fGet('/api/agent-economy.php?action=overview');
    const el = document.getElementById('streams-grid');
    if (d.success && d.streams && d.streams.length > 0) {
        el.innerHTML = `<table class="fc-table"><thead><tr><th>Stream</th><th>Model</th><th>Revenue</th></tr></thead><tbody>${
            d.streams.map(s => `<tr><td>${s.name || s.stream_type}</td><td><span class="fc-status fc-status-pending">${s.revenue_model}</span></td><td style="font-weight:600">${fmt(s.total_revenue)}</td></tr>`).join('')
        }</tbody></table>`;
    } else {
        el.innerHTML = '<div style="color:var(--fc-muted);text-align:center;padding:1rem;">No revenue streams yet</div>';
    }
}

async function seedEcosystem() {
    const d = await fGet('/api/agent-economy.php?action=seed-streams');
    if (d.success) {
        alert('Revenue streams seeded!');
        loadEcosystemWallets();
        loadStreams();
    }
}

function refreshAll() {
    loadKPIs();
    loadMRR();
    loadRevenueChart();
    loadPayouts();
    loadTax();
    loadOrders();
    loadAgentWork();
    loadEcosystemWallets();
    loadStreams();
}
refreshAll();
