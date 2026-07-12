(function(){
    const API = '/api/service-governance.php';
    const $ = s => document.querySelector(s);
    const $$ = s => document.querySelectorAll(s);

    // Tabs
    $$('.sm-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            $$('.sm-tab').forEach(t => t.classList.remove('active'));
            $$('.sm-panel').forEach(p => p.classList.remove('active'));
            tab.classList.add('active');
            $('#tab-' + tab.dataset.tab).classList.add('active');
        });
    });

    function api(action) {
        return fetch(API + '?action=' + action).then(r => r.json());
    }
    function fmtNum(n) {
        if (n >= 1e6) return (n/1e6).toFixed(1) + 'M';
        if (n >= 1e3) return (n/1e3).toFixed(1) + 'K';
        return Number(n).toLocaleString();
    }
    function fmtGSM(n) { return parseFloat(n).toFixed(2); }
    function timeAgo(d) { return GDS.timeAgo(d); }
    function statusBadge(s) {
        return '<span class="sm-badge sm-badge-' + s + '">' + s.replace(/_/g, ' ') + '</span>';
    }

    // ── Load KPIs ──
    async function loadKPIs() {
        const [econ, gov] = await Promise.all([
            api('economy-overview'),
            api('governance-stats')
        ]);
        if (econ.token) {
            $('#kpiHolders').textContent = fmtNum(econ.token.holders_with_balance);
            $('#kpiCirculating').textContent = fmtGSM(econ.token.circulating) + ' GSM';
            $('#kpiApiKeys').textContent = econ.api_marketplace?.total_keys || 0;
        }
        if (gov) {
            let total = 0, approved = 0, deployed = 0, votes = 0, jobs = 0;
            (gov.proposals_by_status || []).forEach(p => {
                total += parseInt(p.count);
                if (['approved','in_development','deployed','testing'].includes(p.status)) approved += parseInt(p.count);
                if (p.status === 'deployed') deployed += parseInt(p.count);
            });
            (gov.votes_summary || []).forEach(v => votes += parseInt(v.count));
            (gov.jobs_by_status || []).forEach(j => jobs += parseInt(j.count));
            $('#kpiProposals').textContent = total;
            $('#kpiApproved').textContent = approved;
            $('#kpiDeployed').textContent = deployed;
            $('#kpiJobs').textContent = jobs;
            $('#kpiVotes').textContent = votes;
        }
    }

    // ── Pipeline ──
    async function loadPipeline() {
        const data = await api('proposals');
        const grid = $('#pipelineGrid');
        if (!data.proposals?.length) {
            grid.innerHTML = '<div class="sm-empty"><i class="fas fa-diagram-project"></i><p>No proposals yet</p></div>';
            return;
        }
        grid.innerHTML = data.proposals.map(p => {
            const totalVotes = (parseInt(p.approve_votes||0) + parseInt(p.reject_votes||0) + parseInt(p.abstain_votes||0)) || 1;
            const appPct = Math.round((p.approve_votes/totalVotes)*100);
            const rejPct = Math.round((p.reject_votes/totalVotes)*100);
            const absPct = 100 - appPct - rejPct;
            return `
            <div class="sm-card">
                <div class="sm-card-header">
                    <div class="sm-card-title">${esc(p.title)}</div>
                    ${statusBadge(p.status)}
                </div>
                <div class="sm-card-desc">${esc(p.description || '')}</div>
                <div class="sm-card-meta">
                    <span class="sm-badge sm-badge-proposed">${esc(p.service_type)}</span>
                    <span class="sm-badge sm-badge-draft">${esc(p.target_audience)}</span>
                    <span style="color:var(--sm-muted)"><i class="fas fa-users"></i> ${p.total_votes || 0} votes</span>
                    <span style="color:var(--sm-primary)"><i class="fas fa-coins"></i> ${p.revenue_model || 'N/A'}</span>
                </div>
                <div class="sm-vote-bar" title="Approve: ${appPct}% | Reject: ${rejPct}% | Abstain: ${absPct}%">
                    <div class="sm-vote-approve" style="width:${appPct}%"></div>
                    <div class="sm-vote-reject" style="width:${rejPct}%"></div>
                    <div class="sm-vote-abstain" style="width:${absPct}%"></div>
                </div>
            </div>`;
        }).join('');
    }

    // ── Job Board ──
    async function loadJobs() {
        // Load jobs for all proposals
        const proposals = await api('proposals');
        const body = $('#jobsBody');
        if (!proposals.proposals?.length) {
            body.innerHTML = '<tr><td colspan="6" class="sm-empty">No jobs available</td></tr>';
            return;
        }
        // Fetch jobs for each proposal with jobs
        const allJobs = [];
        const approvedIds = proposals.proposals
            .filter(p => ['approved','in_development','deployed','testing'].includes(p.status))
            .slice(0, 20)
            .map(p => p.id);

        for (const id of approvedIds) {
            const d = await api('proposal-jobs&proposal_id=' + id);
            if (d.jobs) d.jobs.forEach(j => { j._title = proposals.proposals.find(p=>p.id==id)?.title || 'Service #'+id; allJobs.push(j); });
        }
        if (!allJobs.length) {
            body.innerHTML = '<tr><td colspan="6" class="sm-empty">No jobs created yet</td></tr>';
            return;
        }
        body.innerHTML = allJobs.sort((a,b) => new Date(b.created_at) - new Date(a.created_at)).map(j => `
            <tr>
                <td>${esc(j._title)}</td>
                <td><span class="sm-badge sm-badge-proposed">${esc(j.role_type)}</span></td>
                <td>${statusBadge(j.status)}</td>
                <td>${j.assigned_agent_name || '<span style="color:var(--sm-muted)">Unassigned</span>'}</td>
                <td style="color:var(--sm-primary);font-weight:700">${fmtGSM(j.gsm_reward)} GSM</td>
                <td style="color:var(--sm-muted)">${timeAgo(j.created_at)}</td>
            </tr>
        `).join('');
    }

    // ── Economy ──
    async function loadEconomy() {
        const [econ, leaders] = await Promise.all([
            api('economy-overview'),
            api('gsm-leaderboard')
        ]);
        const grid = $('#econGrid');
        let html = '';

        // Token Stats
        if (econ.token) {
            html += `
            <div class="sm-econ-box">
                <h3><i class="fas fa-coins" style="color:var(--sm-primary)"></i> GSM Token</h3>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                    <div><div style="color:var(--sm-muted);font-size:0.78rem">TOTAL SUPPLY</div><div style="font-size:1.4rem;font-weight:800;color:var(--sm-text)">${econ.token.total_supply}</div></div>
                    <div><div style="color:var(--sm-muted);font-size:0.78rem">CIRCULATING</div><div style="font-size:1.4rem;font-weight:800;color:var(--sm-primary)">${fmtGSM(econ.token.circulating)} GSM</div></div>
                    <div><div style="color:var(--sm-muted);font-size:0.78rem">HOLDERS</div><div style="font-size:1.4rem;font-weight:800;color:var(--sm-secondary)">${fmtNum(econ.token.holders_with_balance)}</div></div>
                    <div><div style="color:var(--sm-muted);font-size:0.78rem">RATE</div><div style="font-size:1.4rem;font-weight:800;color:var(--sm-accent)">$${econ.token.rate_usd}</div></div>
                </div>
            </div>`;
        }

        // Earnings Breakdown
        if (econ.earnings_by_type?.length) {
            html += `
            <div class="sm-econ-box">
                <h3><i class="fas fa-chart-pie" style="color:var(--sm-accent)"></i> Earnings Breakdown</h3>
                ${econ.earnings_by_type.map(e => {
                    const pct = econ.token?.circulating ? Math.round((e.total_gsm/econ.token.circulating)*100) : 0;
                    return `
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:0.5rem 0;border-bottom:1px solid var(--sm-border)">
                        <div>
                            <div style="font-weight:600;color:var(--sm-text)">${e.earning_type.replace(/_/g, ' ')}</div>
                            <div style="font-size:0.78rem;color:var(--sm-muted)">${e.transactions} transactions</div>
                        </div>
                        <div style="text-align:right">
                            <div style="font-weight:700;color:var(--sm-primary)">${fmtGSM(e.total_gsm)} GSM</div>
                            <div style="font-size:0.75rem;color:var(--sm-muted)">${pct}%</div>
                        </div>
                    </div>`;
                }).join('')}
            </div>`;
        }

        // Leaderboard
        if (leaders.leaderboard?.length) {
            html += `
            <div class="sm-econ-box">
                <h3><i class="fas fa-trophy" style="color:var(--sm-accent)"></i> GSM Leaderboard — Top Earners</h3>
                ${leaders.leaderboard.slice(0, 15).map((l, i) => {
                    const rankClass = i === 0 ? 'gold' : i === 1 ? 'silver' : i === 2 ? 'bronze' : 'other';
                    return `
                    <div class="sm-leader-row">
                        <div class="sm-leader-rank ${rankClass}">${i+1}</div>
                        <div class="sm-leader-info">
                            <div class="sm-leader-name">${esc(l.agent_name)}</div>
                            <div class="sm-leader-dept">${esc(l.department)}</div>
                        </div>
                        <div class="sm-leader-gsm">${fmtGSM(l.balance)} GSM</div>
                    </div>`;
                }).join('')}
            </div>`;
        }

        // Daily Volume
        if (econ.daily_volume?.length) {
            html += `
            <div class="sm-econ-box">
                <h3><i class="fas fa-chart-bar" style="color:var(--sm-info)"></i> Daily Volume</h3>
                ${econ.daily_volume.slice(0,7).map(d => `
                    <div style="display:flex;justify-content:space-between;padding:0.5rem 0;border-bottom:1px solid var(--sm-border)">
                        <span style="color:var(--sm-muted)">${d.date}</span>
                        <span><strong>${d.txns}</strong> txns</span>
                        <span style="color:var(--sm-primary);font-weight:700">${fmtGSM(d.volume)} GSM</span>
                    </div>
                `).join('')}
            </div>`;
        }

        grid.innerHTML = html || '<div class="sm-empty"><i class="fas fa-coins"></i><p>No economy data yet</p></div>';
    }

    // ── API Marketplace ──
    async function loadAPI() {
        const data = await api('marketplace');
        const grid = $('#apiGrid');
        if (!data.services?.length) {
            grid.innerHTML = '<div class="sm-empty"><i class="fas fa-key"></i><p>No deployed services with API access yet</p></div>';
            return;
        }
        grid.innerHTML = data.services.map(s => `
            <div class="sm-card">
                <div class="sm-card-header">
                    <div class="sm-card-title">${esc(s.title)}</div>
                    <span class="sm-badge sm-badge-deployed">LIVE</span>
                </div>
                <div class="sm-card-desc">${esc(s.description || '')}</div>
                <div class="sm-card-meta">
                    <span class="sm-badge sm-badge-proposed">${esc(s.service_type)}</span>
                    <span style="color:var(--sm-muted)"><i class="fas fa-key"></i> ${s.api_key_count || 0} API keys</span>
                    <span style="color:var(--sm-muted)"><i class="fas fa-exchange-alt"></i> ${s.total_requests || 0} requests</span>
                    <span style="color:var(--sm-primary)"><i class="fas fa-coins"></i> ${fmtGSM(s.total_revenue || 0)} GSM revenue</span>
                </div>
            </div>
        `).join('');
    }

    // ── Governance ──
    async function loadGovernance() {
        const data = await api('governance-stats');
        const grid = $('#govGrid');
        let html = '';

        // Status Distribution
        if (data.proposals_by_status?.length) {
            html += `
            <div class="sm-econ-box">
                <h3><i class="fas fa-diagram-project" style="color:var(--sm-info)"></i> Proposal Pipeline</h3>
                ${data.proposals_by_status.map(p => {
                    const total = data.proposals_by_status.reduce((a,b) => a + parseInt(b.count), 0);
                    const pct = Math.round((p.count/total)*100);
                    return `
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:0.5rem 0;border-bottom:1px solid var(--sm-border)">
                        <div>${statusBadge(p.status)}</div>
                        <div style="flex:1;margin:0 1rem">
                            <div class="sm-progress"><div class="sm-progress-fill" style="width:${pct}%"></div></div>
                        </div>
                        <span style="font-weight:700;color:var(--sm-text)">${p.count}</span>
                    </div>`;
                }).join('')}
            </div>`;
        }

        // Votes
        if (data.votes_summary?.length) {
            const total = data.votes_summary.reduce((a,b) => a + parseInt(b.count), 0);
            const approve = parseInt(data.votes_summary.find(v=>v.vote==='approve')?.count || 0);
            const reject = parseInt(data.votes_summary.find(v=>v.vote==='reject')?.count || 0);
            const abstain = parseInt(data.votes_summary.find(v=>v.vote==='abstain')?.count || 0);
            html += `
            <div class="sm-econ-box">
                <h3><i class="fas fa-check-to-slot" style="color:var(--sm-success)"></i> Voting Summary</h3>
                <div style="text-align:center;margin-bottom:1rem">
                    <div style="font-size:2.5rem;font-weight:800;color:var(--sm-text)">${total}</div>
                    <div style="color:var(--sm-muted);font-size:0.85rem">Total Votes Cast</div>
                </div>
                <div class="sm-vote-bar" style="height:16px;border-radius:8px;margin-bottom:1rem">
                    <div class="sm-vote-approve" style="width:${Math.round(approve/total*100)}%"></div>
                    <div class="sm-vote-reject" style="width:${Math.round(reject/total*100)}%"></div>
                    <div class="sm-vote-abstain" style="width:${Math.round(abstain/total*100)}%"></div>
                </div>
                <div style="display:flex;justify-content:space-around;text-align:center">
                    <div><div style="font-weight:700;color:var(--sm-success)">${approve}</div><div style="font-size:0.78rem;color:var(--sm-muted)">Approve</div></div>
                    <div><div style="font-weight:700;color:var(--sm-danger)">${reject}</div><div style="font-size:0.78rem;color:var(--sm-muted)">Reject</div></div>
                    <div><div style="font-weight:700;color:var(--sm-muted)">${abstain}</div><div style="font-size:0.78rem;color:var(--sm-muted)">Abstain</div></div>
                </div>
            </div>`;
        }

        // Jobs
        if (data.jobs_by_status?.length) {
            html += `
            <div class="sm-econ-box">
                <h3><i class="fas fa-briefcase" style="color:var(--sm-accent)"></i> Job Status</h3>
                ${data.jobs_by_status.map(j => `
                    <div style="display:flex;justify-content:space-between;padding:0.5rem 0;border-bottom:1px solid var(--sm-border)">
                        ${statusBadge(j.status)}
                        <span style="font-weight:700;color:var(--sm-text)">${j.count}</span>
                    </div>
                `).join('')}
            </div>`;
        }

        // Top Departments
        if (data.top_departments?.length) {
            html += `
            <div class="sm-econ-box">
                <h3><i class="fas fa-building" style="color:var(--sm-secondary)"></i> Top Departments</h3>
                ${data.top_departments.map((d, i) => `
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:0.5rem 0;border-bottom:1px solid var(--sm-border)">
                        <div style="display:flex;align-items:center;gap:0.75rem">
                            <span style="font-weight:700;color:var(--sm-muted);width:24px">#${i+1}</span>
                            <span style="font-weight:600;color:var(--sm-text);text-transform:capitalize">${esc(d.department)}</span>
                        </div>
                        <span style="color:var(--sm-primary);font-weight:700">${d.proposals} proposals</span>
                    </div>
                `).join('')}
            </div>`;
        }

        grid.innerHTML = html || '<div class="sm-empty"><i class="fas fa-landmark"></i><p>No governance data yet</p></div>';
    }

    function esc(s) { return GDS.esc(s); }

    // ── Init ──
    loadKPIs();
    loadPipeline();

    // Lazy load other tabs on click
    let loaded = { pipeline: true };
    $$('.sm-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            const t = tab.dataset.tab;
            if (loaded[t]) return;
            loaded[t] = true;
            if (t === 'jobs') loadJobs();
            if (t === 'economy') loadEconomy();
            if (t === 'api') loadAPI();
            if (t === 'governance') loadGovernance();
        });
    });
})();
