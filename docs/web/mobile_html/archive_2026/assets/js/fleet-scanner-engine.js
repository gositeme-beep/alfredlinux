(function() {
    const API = '/api/fleet-scanner.php';
    let currentScanId = '';
    let currentPage = 1;
    let currentSeverity = '';

    async function api(action, params = {}) {
        const url = new URL(API, location.origin);
        url.searchParams.set('action', action);
        Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, v));
        try {
            const r = await fetch(url, { credentials: 'include' });
            return await r.json();
        } catch (e) {
            return { error: e.message };
        }
    }

    function badge(sev) {
        return '<span class="fsd-badge fsd-badge--' + sev + '">' + sev + '</span>';
    }

    function escHTML(s) {
        if (!s) return '';
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function barChart(data, maxVal, colorFn) {
        let html = '';
        Object.entries(data).forEach(([label, count]) => {
            const w = maxVal > 0 ? Math.max(2, (count / maxVal) * 100) : 0;
            const color = colorFn ? colorFn(label) : 'var(--alfred-primary, #00d4ff)';
            html += '<div class="fsd-chart-bar">';
            html += '<div class="fsd-chart-label">' + escHTML(label) + '</div>';
            html += '<div class="fsd-chart-fill" style="width:' + w + '%;background:' + color + '"></div>';
            html += '<div class="fsd-chart-count">' + count + '</div>';
            html += '</div>';
        });
        return html;
    }

    const sevColors = { critical: '#ef4444', high: '#f59e0b', medium: '#eab308', low: '#22c55e', info: '#3b82f6' };
    const typeColors = { security: '#ef4444', logic: '#8b5cf6', performance: '#f59e0b', frontend: '#3b82f6', accessibility: '#22c55e', deprecated: '#94a3b8', error_handling: '#ec4899', data_validation: '#14b8a6' };

    async function loadScanStatus() {
        const data = await api('scan_status');
        if (!data.success) return;

        if (data.scans && data.scans.length > 0) {
            const scan = data.scans[0];
            currentScanId = scan.scan_id;
            return loadScanDetails();
        }
    }

    async function loadScanDetails() {
        const data = await api('scan_status', { scan_id: currentScanId });
        if (!data.success || !data.scan) return;

        const s = data.scan;
        document.getElementById('totalTasks').textContent = Number(s.total_tasks).toLocaleString();
        document.getElementById('completedTasks').textContent = Number(s.completed_tasks).toLocaleString();
        document.getElementById('totalBugs').textContent = Number(s.total_bugs_found || 0).toLocaleString();
        document.getElementById('criticalBugs').textContent = s.critical_bugs || 0;
        document.getElementById('highBugs').textContent = s.high_bugs || 0;
        document.getElementById('mediumBugs').textContent = s.medium_bugs || 0;

        const pct = data.progress_pct || 0;
        const bar = document.getElementById('progressBar');
        bar.style.width = pct + '%';
        bar.textContent = pct + '%';

        document.getElementById('scanStatus').textContent = 'Status: ' + (s.status || 'unknown').toUpperCase();
        document.getElementById('scanId').textContent = 'ID: ' + currentScanId;
    }

    async function loadBugSummary() {
        const data = await api('bug_summary', { scan_id: currentScanId });
        if (!data.success) return;

        const maxType = Math.max(...Object.values(data.by_type || {}).map(Number), 1);
        document.getElementById('byTypeChart').innerHTML = barChart(data.by_type || {}, maxType, l => typeColors[l] || '#00d4ff');

        const maxSev = Math.max(...Object.values(data.by_severity || {}).map(Number), 1);
        document.getElementById('bySeverityChart').innerHTML = barChart(data.by_severity || {}, maxSev, l => sevColors[l] || '#94a3b8');

        // Hot files
        const tbody = document.getElementById('hotFilesBody');
        tbody.innerHTML = '';
        (data.hot_files || []).forEach(f => {
            const tr = document.createElement('tr');
            tr.innerHTML = '<td style="font-family:monospace;font-size:0.8rem">' + escHTML(f.file_path) + '</td><td>' + f.cnt + '</td><td>' + (f.crits || 0) + '</td><td>' + (f.highs || 0) + '</td>';
            tbody.appendChild(tr);
        });
    }

    async function loadBugs(page, severity) {
        const params = { scan_id: currentScanId, page: page, limit: 50 };
        if (severity) params.severity = severity;
        const data = await api('list_bugs', params);
        if (!data.success) return;

        const list = document.getElementById('bugList');
        if (page === 1) list.innerHTML = '';

        (data.bugs || []).forEach(bug => {
            const div = document.createElement('div');
            div.className = 'fsd-bug';
            let html = '<div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">';
            html += badge(bug.severity);
            html += '<span class="fsd-badge" style="background:rgba(139,92,246,0.15);color:#8b5cf6;">' + escHTML(bug.scan_type) + '</span>';
            html += '<span class="fsd-bug-title">' + escHTML(bug.title) + '</span>';
            html += '</div>';
            html += '<div class="fsd-bug-file">' + escHTML(bug.file_path) + (bug.line_number ? ':' + bug.line_number : '') + '</div>';
            if (bug.description) html += '<div class="fsd-bug-desc">' + escHTML(bug.description) + '</div>';
            if (bug.code_snippet) html += '<div class="fsd-bug-code">' + escHTML(bug.code_snippet) + '</div>';
            if (bug.suggested_fix) html += '<div class="fsd-bug-fix"><strong>Fix:</strong> ' + escHTML(bug.suggested_fix) + '</div>';
            div.innerHTML = html;
            list.appendChild(div);
        });

        if (data.bugs.length === 0 && page === 1) {
            list.innerHTML = '<div class="fsd-loading">No bugs found yet. Scan may still be in progress.</div>';
        }

        document.getElementById('loadMoreBtn').style.display = data.page < data.pages ? '' : 'none';
    }

    async function refresh() {
        await loadScanStatus();
        await loadBugSummary();
        await loadBugs(1, currentSeverity);
    }

    // Filter clicks
    document.getElementById('filters').addEventListener('click', function(e) {
        if (!e.target.classList.contains('fsd-filter')) return;
        document.querySelectorAll('#filters .fsd-filter').forEach(f => f.classList.remove('active'));
        e.target.classList.add('active');
        currentSeverity = e.target.dataset.severity || '';
        currentPage = 1;
        loadBugs(1, currentSeverity);
    });

    document.getElementById('loadMoreBtn').addEventListener('click', function() {
        currentPage++;
        loadBugs(currentPage, currentSeverity);
    });

    document.getElementById('refreshBtn').addEventListener('click', refresh);

    // Initial load
    refresh();

    // Auto-refresh every 30 seconds
    setInterval(refresh, 30000);
})();
