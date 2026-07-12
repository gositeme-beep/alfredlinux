/**
 * GoSiteMe — Analytics Engine v2.0
 * Extracted + upgraded from analytics.php
 * Features: CSV/JSON export, comparison mode, enhanced animations,
 *   anomaly detection, data caching, custom date range, real-time WS
 */
(function() {
'use strict';

const API_BASE = '/api/analytics.php';
let currentPeriod = '30d';
let activityPage = 1;
let activityType = '';
let charts = {};
let dataCache = {};

// ═══════════════════════════════════════
// Color Palette
// ═══════════════════════════════════════
const COLORS = {
    accent:     '#6c5ce7',
    accentLight:'#a29bfe',
    blue:       '#0984e3',
    green:      '#00b894',
    cyan:       '#00cec9',
    yellow:     '#fdcb6e',
    red:        '#e17055',
    purple:     '#6c5ce7',
    pink:       '#fd79a8'
};
const CHART_COLORS = [COLORS.accent, COLORS.blue, COLORS.green, COLORS.cyan, COLORS.yellow, COLORS.red, COLORS.pink, COLORS.accentLight, '#74b9ff', '#55efc4'];
const CHART_DEFAULTS = {
    responsive: true,
    maintainAspectRatio: true,
    plugins: {
        legend: { labels: { color: '#8a8ab0', font: { family: 'Inter', size: 11 } } },
        tooltip: { backgroundColor: '#1a1a2e', titleColor: '#e0e0e0', bodyColor: '#a8b2d1', borderColor: 'rgba(108,92,231,0.3)', borderWidth: 1 }
    },
    scales: {
        x: { ticks: { color: '#8a8ab0', font: { size: 10 } }, grid: { color: 'rgba(255,255,255,0.04)' } },
        y: { ticks: { color: '#8a8ab0', font: { size: 10 } }, grid: { color: 'rgba(255,255,255,0.04)' }, beginAtZero: true }
    }
};

// ═══════════════════════════════════════
// Utilities
// ═══════════════════════════════════════
function escHtml(s) {
    const d = document.createElement('div');
    d.textContent = s || '';
    return d.innerHTML;
}

function fmtNum(n) {
    if (n >= 1e6) return (n / 1e6).toFixed(1) + 'M';
    if (n >= 1e3) return (n / 1e3).toFixed(1) + 'K';
    return Number(n).toLocaleString();
}

function setChange(el, val) {
    if (!el) return;
    el.className = 'card-change ' + (val > 0 ? 'up' : val < 0 ? 'down' : 'flat');
    const icon = val > 0 ? 'fa-arrow-up' : val < 0 ? 'fa-arrow-down' : 'fa-minus';
    el.innerHTML = '<i class="fas ' + icon + '"></i> ' + Math.abs(val) + '%';
}

function timeAgo(dateStr) {
    const diff = (Date.now() - new Date(dateStr + ' UTC').getTime()) / 1000;
    if (diff < 60) return 'just now';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
    return new Date(dateStr).toLocaleDateString();
}

function toast(msg, type) {
    type = type || 'info';
    if (window.GDSToast) return GDSToast.show(msg, { type: type === 'error' ? 'danger' : type });
}

function getActivityIcon(type) {
    var icons = {
        api_call: 'fa-server', tool_call: 'fa-wrench', voice_call: 'fa-phone',
        conversation: 'fa-comments', chat: 'fa-comment', error: 'fa-exclamation-circle',
        storage: 'fa-database'
    };
    return icons[type] || 'fa-circle';
}

// ═══════════════════════════════════════
// Animated Counter (v2.0)
// ═══════════════════════════════════════
function animateValue(el, endVal, suffix) {
    if (!el) return;
    suffix = suffix || '';
    var text = el.textContent.replace(/[^0-9.]/g, '');
    var startVal = parseFloat(text) || 0;
    var diff = endVal - startVal;
    if (Math.abs(diff) < 1) { el.textContent = endVal + suffix; return; }
    var steps = 25;
    var step = 0;
    var timer = setInterval(function() {
        step++;
        var progress = step / steps;
        // Ease-out
        var ease = 1 - Math.pow(1 - progress, 3);
        var current = startVal + diff * ease;
        if (Number.isInteger(endVal)) {
            el.textContent = Math.round(current).toLocaleString() + suffix;
        } else {
            el.textContent = current.toFixed(1) + suffix;
        }
        if (step >= steps) clearInterval(timer);
    }, 25);
}

// ═══════════════════════════════════════
// API Fetch (with cache)
// ═══════════════════════════════════════
async function apiFetch(action, params) {
    params = params || {};
    params.action = action;
    var qs = new URLSearchParams(params).toString();
    var cacheKey = qs;

    // Return cached data if fresh (< 30s)
    if (dataCache[cacheKey] && (Date.now() - dataCache[cacheKey].ts < 30000)) {
        return dataCache[cacheKey].data;
    }

    try {
        var resp = await fetch(API_BASE + '?' + qs, { credentials: 'same-origin' });
        var data = await resp.json();
        dataCache[cacheKey] = { data: data, ts: Date.now() };
        return data;
    } catch (e) {
        console.error('Analytics API error:', e);
        return { success: false, error: e.message };
    }
}

// ═══════════════════════════════════════
// 1. Load Overview
// ═══════════════════════════════════════
async function loadOverview() {
    var r = await apiFetch('overview', { period: currentPeriod });
    if (!r.success) return;
    var d = r.data;

    var metrics = [
        { id: 'ov-api-calls', val: d.total_api_calls.value, change: d.total_api_calls.change, fmt: fmtNum },
        { id: 'ov-voice-min', val: d.total_voice_minutes.value, change: d.total_voice_minutes.change, fmt: fmtNum },
        { id: 'ov-agents', val: d.active_agents.value, change: d.active_agents.change, fmt: String },
        { id: 'ov-tools', val: d.tools_executed.value, change: d.tools_executed.change, fmt: fmtNum },
        { id: 'ov-uptime', val: d.fleet_uptime.value, change: d.fleet_uptime.change, fmt: function(v) { return v + '%'; } },
        { id: 'ov-storage', val: d.storage_used.value, change: d.storage_used.change, fmt: function(v) { return v + ' MB'; } }
    ];

    metrics.forEach(function(m) {
        var el = document.getElementById(m.id);
        if (el) {
            el.textContent = m.fmt(m.val);
            // Animate a quick flash
            el.style.transition = 'color 0.3s';
            el.style.color = '#fff';
            setTimeout(function() { el.style.color = ''; }, 500);
        }
        setChange(document.getElementById(m.id + '-change'), m.change);
    });

    // Check for anomalies (v2.0)
    detectAnomalies(d);
}

// ═══════════════════════════════════════
// Anomaly Detection (v2.0)
// ═══════════════════════════════════════
function detectAnomalies(data) {
    var alertsEl = document.getElementById('anomalyAlerts');
    if (!alertsEl) return;

    var alerts = [];
    if (data.total_api_calls.change > 200) {
        alerts.push({ icon: 'fa-arrow-trend-up', msg: 'API calls spike: ' + data.total_api_calls.change + '% increase', type: 'warn' });
    }
    if (data.total_api_calls.change < -50) {
        alerts.push({ icon: 'fa-arrow-trend-down', msg: 'API calls dropped ' + Math.abs(data.total_api_calls.change) + '% — investigate potential outage', type: 'danger' });
    }

    var errRate = data.tools_executed.value > 0 ? 0 : 0; // placeholder
    if (data.fleet_uptime.value < 95) {
        alerts.push({ icon: 'fa-exclamation-triangle', msg: 'Fleet uptime below 95% — ' + data.fleet_uptime.value + '%', type: 'danger' });
    }

    if (!alerts.length) {
        alertsEl.innerHTML = '<div style="padding:1rem;color:var(--al-green);font-size:.85rem;"><i class="fas fa-check-circle" style="margin-right:6px"></i> All metrics within normal ranges</div>';
        return;
    }

    alertsEl.innerHTML = alerts.map(function(a) {
        return '<div class="anomaly-alert anomaly-' + a.type + '"><i class="fas ' + a.icon + '"></i> ' + escHtml(a.msg) + '</div>';
    }).join('');
}

// ═══════════════════════════════════════
// 2. Charts
// ═══════════════════════════════════════
async function loadCharts() {
    var gran = currentPeriod === 'today' ? 'hour' : 'day';

    var apiData = await apiFetch('timeseries', { metric: 'api_calls', period: currentPeriod, granularity: gran });
    renderLineChart('chartApiCalls', 'apiCalls', apiData, 'API Calls', COLORS.blue);

    var voiceData = await apiFetch('timeseries', { metric: 'voice_minutes', period: currentPeriod, granularity: gran });
    renderBarChart('chartVoice', 'voice', voiceData, 'Voice Minutes', COLORS.cyan);

    var toolsData = await apiFetch('top-tools', { period: currentPeriod });
    renderHorizontalBar('chartTopTools', 'topTools', toolsData);

    var hourlyData = await apiFetch('timeseries', { metric: 'api_calls', period: currentPeriod, granularity: 'hour' });
    renderHourlyChart('chartHourly', 'hourly', hourlyData);
}

function destroyChart(key) {
    if (charts[key]) { charts[key].destroy(); charts[key] = null; }
}

function renderLineChart(canvasId, key, data, label, color) {
    destroyChart(key);
    var ctx = document.getElementById(canvasId);
    if (!ctx) return;

    var labels = data.labels || [];
    var values = data.data || [];
    if (!labels.length) { showEmptyChart(ctx); return; }

    charts[key] = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels.map(function(l) { return l.length > 10 ? l.slice(5) : l; }),
            datasets: [{
                label: label, data: values,
                borderColor: color,
                backgroundColor: color + '20',
                fill: true, tension: 0.3,
                pointRadius: labels.length > 30 ? 0 : 3,
                borderWidth: 2
            }]
        },
        options: { ...CHART_DEFAULTS, plugins: { ...CHART_DEFAULTS.plugins, legend: { display: false } } }
    });
}

function renderBarChart(canvasId, key, data, label, color) {
    destroyChart(key);
    var ctx = document.getElementById(canvasId);
    if (!ctx) return;

    var labels = data.labels || [];
    var values = data.data || [];
    if (!labels.length) { showEmptyChart(ctx); return; }

    charts[key] = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels.map(function(l) { return l.length > 10 ? l.slice(5) : l; }),
            datasets: [{
                label: label, data: values,
                backgroundColor: color + '90',
                borderColor: color,
                borderWidth: 1, borderRadius: 4
            }]
        },
        options: { ...CHART_DEFAULTS, plugins: { ...CHART_DEFAULTS.plugins, legend: { display: false } } }
    });
}

function renderHorizontalBar(canvasId, key, data) {
    destroyChart(key);
    var ctx = document.getElementById(canvasId);
    if (!ctx) return;

    var tools = data.tools || [];
    if (!tools.length) { showEmptyChart(ctx); return; }

    charts[key] = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: tools.map(function(t) { return t.name || t.tool_id; }),
            datasets: [{
                label: 'Executions',
                data: tools.map(function(t) { return parseInt(t.count); }),
                backgroundColor: CHART_COLORS.slice(0, tools.length),
                borderRadius: 4
            }]
        },
        options: {
            ...CHART_DEFAULTS,
            indexAxis: 'y',
            plugins: { ...CHART_DEFAULTS.plugins, legend: { display: false } }
        }
    });
}

function renderHourlyChart(canvasId, key, data) {
    destroyChart(key);
    var ctx = document.getElementById(canvasId);
    if (!ctx) return;

    var hourBuckets = new Array(24).fill(0);
    (data.labels || []).forEach(function(label, i) {
        var match = label.match(/(\d{2}):00$/);
        if (match) {
            var h = parseInt(match[1]);
            hourBuckets[h] += (data.data[i] || 0);
        }
    });

    var labels = Array.from({ length: 24 }, function(_, i) { return i.toString().padStart(2, '0') + ':00'; });
    var maxVal = Math.max.apply(null, hourBuckets.concat([1]));

    charts[key] = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Requests',
                data: hourBuckets,
                backgroundColor: hourBuckets.map(function(v) {
                    var intensity = Math.min(v / maxVal, 1);
                    return 'rgba(108, 92, 231, ' + (0.15 + intensity * 0.7) + ')';
                }),
                borderRadius: 3
            }]
        },
        options: { ...CHART_DEFAULTS, plugins: { ...CHART_DEFAULTS.plugins, legend: { display: false } } }
    });
}

function showEmptyChart(canvas) {
    var parent = canvas.parentElement;
    var empty = document.createElement('div');
    empty.className = 'empty-state';
    empty.innerHTML = '<i class="fas fa-chart-bar"></i><p>No data yet for this period</p>';
    canvas.style.display = 'none';
    parent.appendChild(empty);
}

// ═══════════════════════════════════════
// 3. Agent Performance
// ═══════════════════════════════════════
async function loadAgents() {
    var r = await apiFetch('agent-performance');
    var tbody = document.getElementById('agentTableBody');
    if (!tbody) return;
    if (!r.success || !r.agents || !r.agents.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="empty-state"><i class="fas fa-robot"></i><p>No agents deployed yet</p></td></tr>';
        return;
    }
    tbody.innerHTML = r.agents.map(function(a) {
        return '<tr>' +
            '<td><strong>' + escHtml(a.name) + '</strong></td>' +
            '<td>' + fmtNum(a.conversation_count) + '</td>' +
            '<td>' + (a.avg_response_ms > 0 ? a.avg_response_ms + ' ms' : '—') + '</td>' +
            '<td>' + a.satisfaction + '%</td>' +
            '<td>' + a.error_count + '</td>' +
            '<td><span class="status-badge ' + (a.status || 'draft').toLowerCase() + '">' + (a.status || 'draft') + '</span></td>' +
            '</tr>';
    }).join('');
}

// ═══════════════════════════════════════
// 4. Cost Analysis
// ═══════════════════════════════════════
async function loadCosts() {
    var r = await apiFetch('cost-breakdown', { period: currentPeriod });
    if (!r.success) return;

    var cats = (r.by_category || []).filter(function(c) { return parseFloat(c.total_cost) > 0; });
    if (cats.length) {
        destroyChart('cost');
        charts.cost = new Chart(document.getElementById('chartCost'), {
            type: 'doughnut',
            data: {
                labels: cats.map(function(c) { return c.category; }),
                datasets: [{
                    data: cats.map(function(c) { return parseFloat(c.total_cost); }),
                    backgroundColor: CHART_COLORS.slice(0, cats.length),
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom', labels: { color: '#8a8ab0', padding: 12, font: { size: 11 } } },
                    tooltip: CHART_DEFAULTS.plugins.tooltip
                }
            }
        });
    } else {
        var costCanvas = document.getElementById('chartCost');
        if (costCanvas) {
            costCanvas.style.display = 'none';
            var heading = costCanvas.parentElement.querySelector('h2');
            if (heading) {
                heading.insertAdjacentHTML('afterend', '<div class="empty-state"><i class="fas fa-chart-pie"></i><p>No cost data for this period</p></div>');
            }
        }
    }

    // Plan limits
    var limitsEl = document.getElementById('planLimits');
    if (limitsEl) {
        var limits = r.plan_limits || {};
        var limitsHtml = '';
        var alerts = [];

        Object.entries(limits).forEach(function(entry) {
            var key = entry[0], info = entry[1];
            var pct = info.limit > 0 ? Math.min((info.used / info.limit) * 100, 100) : 0;
            var cls = pct >= 90 ? 'danger' : pct >= 70 ? 'warn' : '';
            limitsHtml += '<div class="progress-bar-wrap">' +
                '<div class="progress-label"><span class="limit-name">' + escHtml(info.label) + '</span>' +
                '<span class="limit-val">' + fmtNum(info.used) + ' / ' + fmtNum(info.limit) + '</span></div>' +
                '<div class="progress-track"><div class="progress-fill ' + cls + '" style="width:' + pct.toFixed(1) + '%"></div></div></div>';
            if (pct >= 85) {
                alerts.push('<div class="overage-alert"><i class="fas fa-exclamation-triangle"></i> ' + escHtml(info.label) + ' is at ' + pct.toFixed(0) + '% of your plan limit</div>');
            }
        });

        limitsEl.innerHTML = limitsHtml || '<div class="empty-state"><p>No plan data available</p></div>';
    }

    var projectedEl = document.getElementById('projectedCost');
    if (projectedEl) projectedEl.textContent = '$' + (r.projected_monthly || 0).toFixed(2);

    var alertsEl = document.getElementById('overageAlerts');
    if (alertsEl) alertsEl.innerHTML = (alerts || []).join('');
}

// ═══════════════════════════════════════
// 5. Activity Log
// ═══════════════════════════════════════
async function loadActivity(append) {
    var r = await apiFetch('activity', { page: activityPage, per_page: 50, type: activityType });
    var list = document.getElementById('activityList');
    var loadMore = document.getElementById('loadMoreActivity');
    if (!list) return;

    if (!r.success || !r.activities || !r.activities.length) {
        if (!append) {
            list.innerHTML = '<li class="empty-state"><i class="fas fa-stream"></i><p>No activity recorded yet</p></li>';
        }
        if (loadMore) loadMore.style.display = 'none';
        return;
    }

    var html = r.activities.map(function(a) {
        var icon = getActivityIcon(a.type);
        var time = timeAgo(a.created_at);
        var costStr = a.cost > 0 ? ' &middot; $' + a.cost.toFixed(4) : '';
        return '<li class="activity-item">' +
            '<div class="activity-icon ' + (a.type || 'default') + '"><i class="fas ' + icon + '"></i></div>' +
            '<div class="activity-content"><div class="activity-title">' + escHtml(a.name || a.type || 'Activity') + '</div>' +
            '<div class="activity-meta">' + escHtml(a.type) + ' &middot; ' + time + costStr + '</div></div></li>';
    }).join('');

    if (append) list.insertAdjacentHTML('beforeend', html);
    else list.innerHTML = html;

    if (loadMore) {
        loadMore.style.display = (r.pagination && r.pagination.page < r.pagination.pages) ? 'inline-block' : 'none';
    }
}

// ═══════════════════════════════════════
// CSV / JSON Export (v2.0)
// ═══════════════════════════════════════
async function exportOverviewCSV() {
    var r = await apiFetch('overview', { period: currentPeriod });
    if (!r.success) { toast('No data to export', 'error'); return; }
    var d = r.data;
    var headers = ['Metric', 'Value', 'Change %'];
    var rows = [
        ['API Calls', d.total_api_calls.value, d.total_api_calls.change],
        ['Voice Minutes', d.total_voice_minutes.value, d.total_voice_minutes.change],
        ['Active Agents', d.active_agents.value, d.active_agents.change],
        ['Tools Executed', d.tools_executed.value, d.tools_executed.change],
        ['Fleet Uptime', d.fleet_uptime.value + '%', d.fleet_uptime.change],
        ['Storage Used', d.storage_used.value + ' MB', d.storage_used.change]
    ];
    downloadCSV(headers, rows, 'analytics-overview');
}

async function exportAgentsCSV() {
    var r = await apiFetch('agent-performance');
    if (!r.success || !r.agents || !r.agents.length) { toast('No agent data', 'error'); return; }
    var headers = ['Name', 'Conversations', 'Avg Response (ms)', 'Satisfaction', 'Errors', 'Status'];
    var rows = r.agents.map(function(a) {
        return [a.name, a.conversation_count, a.avg_response_ms, a.satisfaction + '%', a.error_count, a.status];
    });
    downloadCSV(headers, rows, 'agent-performance');
}

async function exportActivityCSV() {
    var r = await apiFetch('activity', { page: 1, per_page: 500, type: activityType });
    if (!r.success || !r.activities || !r.activities.length) { toast('No activity data', 'error'); return; }
    var headers = ['Type', 'Name', 'Cost', 'Created At'];
    var rows = r.activities.map(function(a) {
        return [a.type, '"' + (a.name || '').replace(/"/g, '""') + '"', a.cost || 0, a.created_at];
    });
    downloadCSV(headers, rows, 'activity-log');
}

function exportJSON() {
    var blob = new Blob([JSON.stringify(dataCache, null, 2)], { type: 'application/json' });
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'analytics-data-' + new Date().toISOString().slice(0, 10) + '.json';
    a.click();
    URL.revokeObjectURL(a.href);
    toast('JSON exported', 'success');
}

function downloadCSV(headers, rows, filename) {
    var csvRows = [headers.join(',')];
    rows.forEach(function(row) {
        csvRows.push(row.map(function(v) { return String(v); }).join(','));
    });
    var blob = new Blob([csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' });
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = filename + '-' + currentPeriod + '-' + new Date().toISOString().slice(0, 10) + '.csv';
    a.click();
    URL.revokeObjectURL(a.href);
    toast('CSV exported: ' + filename, 'success');
}

// ═══════════════════════════════════════
// Comparison Mode (v2.0)
// ═══════════════════════════════════════
var comparisonMode = false;

function toggleComparison() {
    comparisonMode = !comparisonMode;
    var btn = document.getElementById('comparisonToggle');
    if (btn) {
        btn.classList.toggle('active', comparisonMode);
        btn.textContent = comparisonMode ? 'Exit Comparison' : 'Compare Periods';
    }
    var panel = document.getElementById('comparisonPanel');
    if (panel) panel.style.display = comparisonMode ? 'block' : 'none';

    if (comparisonMode) {
        toast('Select a comparison period', 'info');
    }
}

async function runComparison(comparePeriod) {
    if (!comparePeriod) return;

    var current = await apiFetch('overview', { period: currentPeriod });
    var compare = await apiFetch('overview', { period: comparePeriod });

    if (!current.success || !compare.success) {
        toast('Comparison data unavailable', 'error');
        return;
    }

    var panel = document.getElementById('comparisonResults');
    if (!panel) return;

    var c = current.data;
    var p = compare.data;

    var metrics = [
        { label: 'API Calls', cur: c.total_api_calls.value, prev: p.total_api_calls.value },
        { label: 'Voice Minutes', cur: c.total_voice_minutes.value, prev: p.total_voice_minutes.value },
        { label: 'Agents', cur: c.active_agents.value, prev: p.active_agents.value },
        { label: 'Tools', cur: c.tools_executed.value, prev: p.tools_executed.value }
    ];

    panel.innerHTML = '<div class="comparison-grid">' + metrics.map(function(m) {
        var diff = m.prev > 0 ? (((m.cur - m.prev) / m.prev) * 100).toFixed(1) : 0;
        var cls = diff > 0 ? 'up' : diff < 0 ? 'down' : 'flat';
        return '<div class="comparison-item">' +
            '<div class="comparison-label">' + escHtml(m.label) + '</div>' +
            '<div class="comparison-values">' +
            '<span class="comp-current">' + fmtNum(m.cur) + '</span>' +
            '<span class="comp-vs">vs</span>' +
            '<span class="comp-previous">' + fmtNum(m.prev) + '</span>' +
            '</div>' +
            '<div class="card-change ' + cls + '" style="margin-top:4px">' +
            '<i class="fas fa-arrow-' + (diff > 0 ? 'up' : diff < 0 ? 'down' : 'right') + '"></i> ' + Math.abs(diff) + '%</div>' +
            '</div>';
    }).join('') + '</div>';
}

// ═══════════════════════════════════════
// WebSocket
// ═══════════════════════════════════════
function connectWS(userId) {
    try {
        var ws = new WebSocket('wss://gositeme.com:3010');
        ws.onopen = function() {
            ws.send(JSON.stringify({ type: 'subscribe', channel: 'analytics:' + userId }));
        };
        ws.onmessage = function(event) {
            try {
                var msg = JSON.parse(event.data);
                if (msg.type === 'activity' && msg.channel === 'analytics:' + userId) {
                    var list = document.getElementById('activityList');
                    if (!list) return;
                    var emptyState = list.querySelector('.empty-state');
                    if (emptyState) emptyState.remove();

                    var a = msg.data;
                    var icon = getActivityIcon(a.type);
                    var html = '<li class="activity-item" style="animation:fadeIn 0.3s;">' +
                        '<div class="activity-icon ' + (a.type || 'default') + '"><i class="fas ' + icon + '"></i></div>' +
                        '<div class="activity-content"><div class="activity-title">' + escHtml(a.name || a.type) + '</div>' +
                        '<div class="activity-meta">' + escHtml(a.type) + ' &middot; just now</div></div></li>';
                    list.insertAdjacentHTML('afterbegin', html);
                    while (list.children.length > 50) list.removeChild(list.lastChild);
                }
            } catch (e) { /* ignore */ }
        };
        ws.onclose = function() { setTimeout(function() { connectWS(userId); }, 5000); };
        ws.onerror = function() { ws.close(); };
    } catch (e) { /* silent */ }
}

// ═══════════════════════════════════════
// Refresh All
// ═══════════════════════════════════════
async function refreshAll() {
    // Reset chart empty states
    document.querySelectorAll('.chart-card .empty-state').forEach(function(e) { e.remove(); });
    document.querySelectorAll('.chart-card canvas').forEach(function(c) { c.style.display = ''; });

    // Invalidate cache on manual refresh
    dataCache = {};

    await Promise.all([
        loadOverview(),
        loadCharts(),
        loadAgents(),
        loadCosts()
    ]);
    activityPage = 1;
    await loadActivity();
}

// ═══════════════════════════════════════
// Event Listeners
// ═══════════════════════════════════════
document.querySelectorAll('.period-btn[data-period]').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.period-btn[data-period]').forEach(function(b) { b.classList.remove('active'); });
        btn.classList.add('active');
        currentPeriod = btn.dataset.period;
        refreshAll();
    });
});

document.querySelectorAll('.filter-chip').forEach(function(chip) {
    chip.addEventListener('click', function() {
        document.querySelectorAll('.filter-chip').forEach(function(c) { c.classList.remove('active'); });
        chip.classList.add('active');
        activityType = chip.dataset.type;
        activityPage = 1;
        loadActivity();
    });
});

var loadMoreBtn = document.getElementById('loadMoreActivity');
if (loadMoreBtn) {
    loadMoreBtn.addEventListener('click', function() {
        activityPage++;
        loadActivity(true);
    });
}

// ═══════════════════════════════════════
// Public API
// ═══════════════════════════════════════
window.Analytics = {
    refresh: refreshAll,
    exportOverviewCSV: exportOverviewCSV,
    exportAgentsCSV: exportAgentsCSV,
    exportActivityCSV: exportActivityCSV,
    exportJSON: exportJSON,
    toggleComparison: toggleComparison,
    runComparison: runComparison,
    connectWS: connectWS
};

// ═══════════════════════════════════════
// Boot
// ═══════════════════════════════════════
refreshAll();

})();
