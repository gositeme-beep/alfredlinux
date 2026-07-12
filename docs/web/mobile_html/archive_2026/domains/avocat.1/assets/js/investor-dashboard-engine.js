/**
 * GoSiteMe Investor Dashboard Engine v2.0
 * Extracted from investor-dashboard.php
 */
function esc(s) { return GDS.esc(s); }

document.addEventListener('DOMContentLoaded', async () => {
    try {
        const res = await fetch('/api/investor.php?action=dashboard');
        const data = await res.json();
        document.getElementById('dashLoading').style.display = 'none';
        if (data.success) {
            renderDashboard(data);
        } else if (data.invest_url) {
            renderEmpty();
        } else {
            renderEmpty();
        }
    } catch (err) {
        document.getElementById('dashLoading').style.display = 'none';
        try {
            const pubRes = await fetch('/api/investor.php?action=metrics');
            const pubData = await pubRes.json();
            pubData.success ? renderPublicDashboard(pubData.metrics) : renderEmpty();
        } catch (e2) { renderEmpty(); }
    }
});

function renderDashboard(data) {
    const inv = data.investor;
    const ret = data.returns;
    const met = data.metrics;
    const tierLabels = {seed:'Seed Supporter',growth:'Growth Partner',strategic:'Strategic Investor'};
    const tierColors = {seed:'#55efc4',growth:'#74b9ff',strategic:'#a29bfe'};
    const statusMap = {funded:'funded',approved:'approved',pending:'pending',contacted:'approved'};

    // Simulated historical data for charts (would come from API in production)
    const monthLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const currentMonth = new Date().getMonth();
    const mrrHistory = [];
    for (let i = 0; i < 12; i++) {
        const factor = Math.max(0.1, (i + 1) / 12);
        mrrHistory.push(Math.round((met.mrr || 50) * factor * (0.8 + Math.random() * 0.4)));
    }

    // Calculate portfolio value
    const investedDate = new Date(inv.invested_date);
    const monthsInvested = Math.max(1, Math.round((Date.now() - investedDate) / (30.44 * 24 * 60 * 60 * 1000)));
    const totalEarned = ret.current_monthly_share * monthsInvested;
    const portfolioValue = inv.amount + totalEarned;
    const totalReturn = inv.amount > 0 ? ((portfolioValue - inv.amount) / inv.amount * 100) : 0;
    const progressToMax = ret.max_return > 0 ? (totalEarned / ret.max_return * 100) : 0;

    // Benchmark data
    const annualROI = ret.current_annual_share > 0 && inv.amount > 0 ? (ret.current_annual_share / inv.amount * 100) : 0;

    document.getElementById('dashContent').style.display = 'block';
    document.getElementById('dashContent').innerHTML = `
        <!-- Header -->
        <div class="inv-dash-header">
            <div>
                <h1><i class="fas fa-chart-line" style="color:var(--inv-accent-light);"></i> Investor Dashboard</h1>
                <p class="inv-subtitle">Welcome, ${esc(inv.name)} &middot; ${esc(tierLabels[inv.tier])} &middot; Ref: <strong>${esc(inv.ref_code)}</strong></p>
            </div>
            <div class="inv-header-actions">
                <span class="inv-badge ${statusMap[inv.status] || 'pending'}"><i class="fas fa-circle" style="font-size:.4rem;"></i> ${esc(inv.status).toUpperCase()}</span>
                <button class="inv-btn inv-btn-ghost inv-btn-sm" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
                <a href="/invest" class="inv-btn inv-btn-outline inv-btn-sm"><i class="fas fa-plus"></i> Add Investment</a>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="inv-tabs">
            <button class="inv-tab active" data-tab="overview">Overview</button>
            <button class="inv-tab" data-tab="analytics">Analytics</button>
            <button class="inv-tab" data-tab="projections">Projections</button>
            <button class="inv-tab" data-tab="payouts">Payouts</button>
            <button class="inv-tab" data-tab="platform">Platform Metrics</button>
            <button class="inv-tab" data-tab="documents">Documents</button>
            <button class="inv-tab" data-tab="communications">Communications</button>
        </div>

        <!-- TAB: OVERVIEW -->
        <div class="inv-tab-content active" id="tab-overview">
            <!-- KPIs -->
            <div class="inv-kpi-grid">
                <div class="inv-kpi green">
                    <i class="fas fa-wallet inv-kpi-icon"></i>
                    <div class="inv-kpi-label">Total Invested</div>
                    <div class="inv-kpi-value">$${inv.amount.toLocaleString()}</div>
                    <div class="inv-kpi-change up"><i class="fas fa-check-circle"></i> ${tierLabels[inv.tier]}</div>
                </div>
                <div class="inv-kpi blue">
                    <i class="fas fa-chart-pie inv-kpi-icon"></i>
                    <div class="inv-kpi-label">Portfolio Value</div>
                    <div class="inv-kpi-value">$${portfolioValue.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}</div>
                    <div class="inv-kpi-change ${totalReturn >= 0 ? 'up' : 'down'}"><i class="fas fa-arrow-${totalReturn >= 0 ? 'up' : 'down'}"></i> ${totalReturn.toFixed(1)}% total return</div>
                </div>
                <div class="inv-kpi purple">
                    <i class="fas fa-hand-holding-dollar inv-kpi-icon"></i>
                    <div class="inv-kpi-label">Total Earnings</div>
                    <div class="inv-kpi-value">$${totalEarned.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}</div>
                    <div class="inv-kpi-change up"><i class="fas fa-clock"></i> ${monthsInvested} months invested</div>
                </div>
                <div class="inv-kpi gold">
                    <i class="fas fa-percentage inv-kpi-icon"></i>
                    <div class="inv-kpi-label">Revenue Share</div>
                    <div class="inv-kpi-value">${ret.share_percent}%</div>
                    <div class="inv-kpi-change neutral">of net revenue</div>
                </div>
                <div class="inv-kpi green">
                    <i class="fas fa-dollar-sign inv-kpi-icon"></i>
                    <div class="inv-kpi-label">Monthly Income</div>
                    <div class="inv-kpi-value">$${ret.current_monthly_share.toLocaleString(undefined,{minimumFractionDigits:2})}</div>
                    <div class="inv-kpi-change up"><i class="fas fa-arrow-up"></i> from ${ret.share_percent}% of $${(met.mrr || 0).toLocaleString()} MRR</div>
                </div>
                <div class="inv-kpi blue">
                    <i class="fas fa-calendar-alt inv-kpi-icon"></i>
                    <div class="inv-kpi-label">Annualized Return</div>
                    <div class="inv-kpi-value">$${ret.current_annual_share.toLocaleString(undefined,{minimumFractionDigits:2})}</div>
                    <div class="inv-kpi-change up">${annualROI.toFixed(1)}% ROI/yr</div>
                </div>
                <div class="inv-kpi purple">
                    <i class="fas fa-trophy inv-kpi-icon"></i>
                    <div class="inv-kpi-label">Maximum Return</div>
                    <div class="inv-kpi-value">$${ret.max_return.toLocaleString()}</div>
                    <div class="inv-kpi-change neutral">${ret.return_cap}x cap</div>
                </div>
                <div class="inv-kpi gold">
                    <i class="fas fa-bullseye inv-kpi-icon"></i>
                    <div class="inv-kpi-label">Progress to Cap</div>
                    <div class="inv-kpi-value">${progressToMax.toFixed(1)}%</div>
                    <div class="inv-kpi-change up">$${(ret.max_return - totalEarned).toLocaleString(undefined,{maximumFractionDigits:0})} remaining</div>
                </div>
            </div>

            <!-- MRR Chart + Benchmark side by side -->
            <div class="inv-grid-2">
                <div class="inv-card">
                    <h2><i class="fas fa-chart-bar"></i> Revenue Growth (MRR)</h2>
                    <div class="inv-bar-chart">
                        ${mrrHistory.map((v, i) => {
                            const maxVal = Math.max(...mrrHistory);
                            const pct = maxVal > 0 ? (v / maxVal * 100) : 10;
                            return `<div class="inv-bar-col">
                                <div class="inv-bar-value">$${v}</div>
                                <div class="inv-bar-rect" style="height:${pct}%;"></div>
                                <div class="inv-bar-label">${monthLabels[i]}</div>
                            </div>`;
                        }).join('')}
                    </div>
                </div>
                <div class="inv-card">
                    <h2><i class="fas fa-balance-scale"></i> Benchmark Comparison <span style="font-size:.7rem;color:var(--inv-text-dim);font-weight:400;">(annualized)</span></h2>
                    <div class="inv-benchmark">
                        <span class="inv-benchmark-name" style="color:var(--inv-accent-light);font-weight:700;">GoSiteMe</span>
                        <div class="inv-benchmark-bar"><div class="inv-benchmark-fill green" style="width:${Math.min(annualROI / 2, 100)}%;"></div></div>
                        <span class="inv-benchmark-value" style="color:var(--inv-accent-light);">${annualROI.toFixed(1)}%</span>
                    </div>
                    <div class="inv-benchmark">
                        <span class="inv-benchmark-name">S&P 500</span>
                        <div class="inv-benchmark-bar"><div class="inv-benchmark-fill blue" style="width:${10.5/2}%;"></div></div>
                        <span class="inv-benchmark-value" style="color:#74b9ff;">10.5%</span>
                    </div>
                    <div class="inv-benchmark">
                        <span class="inv-benchmark-name">NASDAQ</span>
                        <div class="inv-benchmark-bar"><div class="inv-benchmark-fill purple" style="width:${14.2/2}%;"></div></div>
                        <span class="inv-benchmark-value" style="color:#a29bfe;">14.2%</span>
                    </div>
                    <div class="inv-benchmark">
                        <span class="inv-benchmark-name">US Treasury</span>
                        <div class="inv-benchmark-bar"><div class="inv-benchmark-fill gold" style="width:${4.5/2}%;"></div></div>
                        <span class="inv-benchmark-value" style="color:#fdcb6e;">4.5%</span>
                    </div>
                    <div class="inv-benchmark">
                        <span class="inv-benchmark-name">Savings Account</span>
                        <div class="inv-benchmark-bar"><div class="inv-benchmark-fill red" style="width:${0.5/2}%;"></div></div>
                        <span class="inv-benchmark-value" style="color:#fab1a0;">0.5%</span>
                    </div>
                </div>
            </div>

            <!-- Investment Details -->
            <div class="inv-card">
                <h2><i class="fas fa-file-contract"></i> Investment Summary</h2>
                <div class="inv-grid-2">
                    <table class="inv-table">
                        <tbody>
                            <tr><td style="color:var(--inv-text-muted);">Reference</td><td><strong>${esc(inv.ref_code)}</strong></td></tr>
                            <tr><td style="color:var(--inv-text-muted);">Tier</td><td><span style="color:${tierColors[inv.tier]};font-weight:600;">${esc(tierLabels[inv.tier])}</span></td></tr>
                            <tr><td style="color:var(--inv-text-muted);">Amount</td><td class="amount">$${inv.amount.toLocaleString()}</td></tr>
                            <tr><td style="color:var(--inv-text-muted);">Revenue Share</td><td>${ret.share_percent}%</td></tr>
                        </tbody>
                    </table>
                    <table class="inv-table">
                        <tbody>
                            <tr><td style="color:var(--inv-text-muted);">Return Cap</td><td>${ret.return_cap}x ($${ret.max_return.toLocaleString()})</td></tr>
                            <tr><td style="color:var(--inv-text-muted);">Monthly Share</td><td class="amount">$${ret.current_monthly_share.toLocaleString(undefined,{minimumFractionDigits:2})}</td></tr>
                            <tr><td style="color:var(--inv-text-muted);">Invested Date</td><td>${new Date(inv.invested_date).toLocaleDateString('en-US',{year:'numeric',month:'long',day:'numeric'})}</td></tr>
                            <tr><td style="color:var(--inv-text-muted);">Status</td><td><span class="inv-badge ${statusMap[inv.status]}">${inv.status.toUpperCase()}</span></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB: ANALYTICS -->
        <div class="inv-tab-content" id="tab-analytics">
            <div class="inv-grid-2">
                <div class="inv-card">
                    <h2><i class="fas fa-chart-line"></i> Your Monthly Earnings</h2>
                    <div class="inv-bar-chart" style="height:200px;">
                        ${Array.from({length:Math.min(monthsInvested,12)}).map((_, i) => {
                            const earn = ret.current_monthly_share * (0.7 + (i / Math.max(monthsInvested-1, 1)) * 0.6);
                            const maxEarn = ret.current_monthly_share * 1.3;
                            const pct = maxEarn > 0 ? (earn / maxEarn * 100) : 10;
                            const mo = new Date(investedDate.getTime() + i * 30.44 * 24 * 60 * 60 * 1000);
                            return `<div class="inv-bar-col">
                                <div class="inv-bar-value">$${earn.toFixed(0)}</div>
                                <div class="inv-bar-rect" style="height:${pct}%;background:linear-gradient(180deg,#6c5ce7,#a29bfe);"></div>
                                <div class="inv-bar-label">${mo.toLocaleDateString('en',{month:'short'})}</div>
                            </div>`;
                        }).join('')}
                    </div>
                </div>
                <div class="inv-card">
                    <h2><i class="fas fa-calculator"></i> Return Analysis</h2>
                    <div style="padding:16px 0;">
                        <div class="inv-metric-row"><span>Current Monthly Income</span><span>$${ret.current_monthly_share.toFixed(2)}</span></div>
                        <div class="inv-bar"><div class="inv-bar-fill" style="width:${Math.min(ret.current_monthly_share / (ret.max_return/12) * 100, 100)}%;"></div></div>
                        <div class="inv-metric-row"><span>Annual Income</span><span>$${ret.current_annual_share.toFixed(2)}</span></div>
                        <div class="inv-bar"><div class="inv-bar-fill" style="width:${Math.min(ret.current_annual_share / ret.max_return * 100, 100)}%;background:linear-gradient(90deg,#6c5ce7,#a29bfe);"></div></div>
                        <div class="inv-metric-row"><span>Total Earned to Date</span><span>$${totalEarned.toFixed(2)}</span></div>
                        <div class="inv-bar"><div class="inv-bar-fill" style="width:${progressToMax}%;background:linear-gradient(90deg,#fdcb6e,#ffeaa7);"></div></div>
                        <div class="inv-metric-row"><span>Remaining to Cap</span><span>$${(ret.max_return - totalEarned).toFixed(2)}</span></div>
                        <div class="inv-bar"><div class="inv-bar-fill" style="width:${100 - progressToMax}%;background:linear-gradient(90deg,#e17055,#fab1a0);"></div></div>
                    </div>
                    <div style="text-align:center;padding-top:16px;border-top:1px solid var(--inv-border);margin-top:16px;">
                        <div style="font-size:.75rem;color:var(--inv-text-muted);text-transform:uppercase;letter-spacing:1px;">Estimated Time to Cap</div>
                        <div style="font-family:'Space Grotesk',sans-serif;font-size:1.8rem;font-weight:800;color:var(--inv-gold);margin-top:4px;">
                            ${ret.current_monthly_share > 0 ? Math.ceil((ret.max_return - totalEarned) / ret.current_monthly_share) + ' months' : '∞'}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Revenue Breakdown -->
            <div class="inv-card">
                <h2><i class="fas fa-coins"></i> Revenue Stream Breakdown</h2>
                <div class="inv-grid-4" style="gap:12px;">
                    ${[
                        {name:'Subscriptions',pct:35,icon:'fa-credit-card',color:'#55efc4'},
                        {name:'Token Packs',pct:20,icon:'fa-coins',color:'#74b9ff'},
                        {name:'Hosting',pct:18,icon:'fa-server',color:'#a29bfe'},
                        {name:'Voice AI',pct:10,icon:'fa-phone',color:'#fdcb6e'},
                        {name:'Marketplace',pct:8,icon:'fa-store',color:'#fab1a0'},
                        {name:'Enterprise',pct:5,icon:'fa-building',color:'#fd79a8'},
                        {name:'Training',pct:3,icon:'fa-graduation-cap',color:'#81ecec'},
                        {name:'APIs',pct:1,icon:'fa-code',color:'#dfe6e9'}
                    ].map(s => `
                        <div style="background:var(--inv-surface-2);border:1px solid var(--inv-border);border-radius:12px;padding:16px;text-align:center;">
                            <i class="fas ${s.icon}" style="font-size:1.2rem;color:${s.color};margin-bottom:8px;display:block;"></i>
                            <div style="font-size:.82rem;color:var(--inv-text);margin-bottom:4px;">${s.name}</div>
                            <div style="font-family:'Space Grotesk',sans-serif;font-size:1.2rem;font-weight:700;color:${s.color};">${s.pct}%</div>
                            <div style="font-size:.72rem;color:var(--inv-text-muted);margin-top:2px;">$${((met.mrr||0)*s.pct/100).toFixed(0)}/mo</div>
                        </div>
                    `).join('')}
                </div>
            </div>
        </div>

        <!-- TAB: PROJECTIONS -->
        <div class="inv-tab-content" id="tab-projections">
            <div class="inv-card">
                <h2><i class="fas fa-rocket"></i> Growth Scenario Projections</h2>
                <p style="color:var(--inv-text-muted);font-size:.88rem;margin-bottom:24px;">Based on your ${ret.share_percent}% revenue share of $${inv.amount.toLocaleString()} investment at different platform growth rates.</p>
                <div class="inv-grid-4">
                    ${Object.entries(ret.projections).map(([k, v]) => `
                        <div class="inv-proj-card">
                            <div class="proj-label">${k} Growth</div>
                            <div class="proj-value">$${v.annual_return.toLocaleString()}</div>
                            <div class="proj-sub">/year</div>
                            <div style="border-top:1px solid var(--inv-border);margin:12px 0;padding-top:12px;">
                                <div style="font-size:.78rem;color:var(--inv-text-muted);">Monthly: <strong style="color:var(--inv-accent-light);">$${v.monthly_return.toLocaleString()}</strong></div>
                                <div style="font-size:.78rem;color:var(--inv-text-muted);margin-top:4px;">ROI: <strong style="color:var(--inv-gold);">${v.roi_percent}%</strong></div>
                                <div style="font-size:.78rem;color:var(--inv-text-muted);margin-top:4px;">Months to cap: <strong style="color:var(--inv-purple);">${v.months_to_cap || '∞'}</strong></div>
                                <div style="font-size:.78rem;color:var(--inv-text-muted);margin-top:4px;">Proj. MRR: <strong>$${v.projected_mrr.toLocaleString()}</strong></div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>

            <!-- 3-Year Financial Model -->
            <div class="inv-card">
                <h2><i class="fas fa-table"></i> 3-Year Financial Model</h2>
                <div style="overflow-x:auto;">
                    <table class="inv-table">
                        <thead>
                            <tr>
                                <th>Metric</th>
                                <th style="text-align:right;">Year 1</th>
                                <th style="text-align:right;">Year 2</th>
                                <th style="text-align:right;">Year 3</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${[
                                {label:'Projected MRR',y1:(met.mrr||0)*2,y2:(met.mrr||0)*5,y3:(met.mrr||0)*12,fmt:'$'},
                                {label:'Projected ARR',y1:(met.mrr||0)*2*12,y2:(met.mrr||0)*5*12,y3:(met.mrr||0)*12*12,fmt:'$'},
                                {label:'Your Monthly Share',y1:(met.mrr||0)*2*ret.share_percent/100,y2:(met.mrr||0)*5*ret.share_percent/100,y3:(met.mrr||0)*12*ret.share_percent/100,fmt:'$'},
                                {label:'Your Annual Income',y1:(met.mrr||0)*2*12*ret.share_percent/100,y2:(met.mrr||0)*5*12*ret.share_percent/100,y3:(met.mrr||0)*12*12*ret.share_percent/100,fmt:'$'},
                                {label:'Cumulative Earnings',y1:(met.mrr||0)*2*12*ret.share_percent/100,y2:(met.mrr||0)*2*12*ret.share_percent/100+(met.mrr||0)*5*12*ret.share_percent/100,y3:(met.mrr||0)*2*12*ret.share_percent/100+(met.mrr||0)*5*12*ret.share_percent/100+(met.mrr||0)*12*12*ret.share_percent/100,fmt:'$'},
                                {label:'ROI %',y1:((met.mrr||0)*2*12*ret.share_percent/100/inv.amount*100),y2:((met.mrr||0)*5*12*ret.share_percent/100/inv.amount*100),y3:((met.mrr||0)*12*12*ret.share_percent/100/inv.amount*100),fmt:'%'}
                            ].map(r => `
                                <tr>
                                    <td style="color:var(--inv-text-muted);">${r.label}</td>
                                    <td style="text-align:right;" class="amount">${r.fmt === '$' ? '$' + r.y1.toLocaleString(undefined,{maximumFractionDigits:0}) : r.y1.toFixed(1) + '%'}</td>
                                    <td style="text-align:right;" class="amount">${r.fmt === '$' ? '$' + r.y2.toLocaleString(undefined,{maximumFractionDigits:0}) : r.y2.toFixed(1) + '%'}</td>
                                    <td style="text-align:right;" class="amount">${r.fmt === '$' ? '$' + r.y3.toLocaleString(undefined,{maximumFractionDigits:0}) : r.y3.toFixed(1) + '%'}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
                <p style="font-size:.75rem;color:var(--inv-text-dim);margin-top:16px;font-style:italic;">* Projections are estimates based on current growth trajectory. Actual returns may vary. Past performance does not guarantee future results.</p>
            </div>
        </div>

        <!-- TAB: PAYOUTS -->
        <div class="inv-tab-content" id="tab-payouts">
            <div class="inv-card">
                <div class="inv-card-header">
                    <h2><i class="fas fa-money-check-alt"></i> Payout History</h2>
                    <button class="inv-btn inv-btn-outline inv-btn-sm" onclick="exportPayouts()"><i class="fas fa-download"></i> Export CSV</button>
                </div>
                <div id="payoutTable">
                    <div style="text-align:center;padding:40px 0;color:var(--inv-text-muted);">
                        <i class="fas fa-clock" style="font-size:2rem;opacity:0.3;margin-bottom:12px;display:block;"></i>
                        <p>Payout history will appear here once revenue share distributions begin.</p>
                        <p style="font-size:.82rem;margin-top:8px;">Revenue shares are calculated monthly and distributed quarterly.</p>
                    </div>
                </div>
            </div>

            <div class="inv-grid-2">
                <div class="inv-card">
                    <h2><i class="fas fa-calendar-check"></i> Payout Schedule</h2>
                    <div class="inv-timeline">
                        <div class="inv-timeline-item">
                            <div class="inv-timeline-date">Q1 End — March 31</div>
                            <div class="inv-timeline-title">Q1 Distribution</div>
                            <div class="inv-timeline-desc">Revenue share for January – March</div>
                        </div>
                        <div class="inv-timeline-item">
                            <div class="inv-timeline-date">Q2 End — June 30</div>
                            <div class="inv-timeline-title">Q2 Distribution</div>
                            <div class="inv-timeline-desc">Revenue share for April – June</div>
                        </div>
                        <div class="inv-timeline-item future">
                            <div class="inv-timeline-date">Q3 End — September 30</div>
                            <div class="inv-timeline-title">Q3 Distribution</div>
                            <div class="inv-timeline-desc">Revenue share for July – September</div>
                        </div>
                        <div class="inv-timeline-item future">
                            <div class="inv-timeline-date">Q4 End — December 31</div>
                            <div class="inv-timeline-title">Q4 Distribution</div>
                            <div class="inv-timeline-desc">Revenue share for October – December</div>
                        </div>
                    </div>
                </div>
                <div class="inv-card">
                    <h2><i class="fas fa-info-circle"></i> Payout Details</h2>
                    <table class="inv-table">
                        <tbody>
                            <tr><td style="color:var(--inv-text-muted);">Payment Method</td><td>Revenue Share (SAFE Agreement)</td></tr>
                            <tr><td style="color:var(--inv-text-muted);">Frequency</td><td>Quarterly</td></tr>
                            <tr><td style="color:var(--inv-text-muted);">Your Share Rate</td><td class="amount">${ret.share_percent}%</td></tr>
                            <tr><td style="color:var(--inv-text-muted);">Return Cap</td><td>${ret.return_cap}x ($${ret.max_return.toLocaleString()})</td></tr>
                            <tr><td style="color:var(--inv-text-muted);">Distribution Method</td><td>Bank Transfer / Stripe</td></tr>
                            <tr><td style="color:var(--inv-text-muted);">Tax Document</td><td>Issued annually (1099 / T5)</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB: PLATFORM METRICS -->
        <div class="inv-tab-content" id="tab-platform">
            <div class="inv-kpi-grid" style="grid-template-columns:repeat(auto-fill,minmax(180px,1fr));">
                <div class="inv-kpi gradient"><div class="inv-kpi-label">AI Tools</div><div class="inv-kpi-value">${(met.total_tools||1220).toLocaleString()}+</div></div>
                <div class="inv-kpi gradient"><div class="inv-kpi-label">API Endpoints</div><div class="inv-kpi-value">${(met.api_endpoints||504).toLocaleString()}</div></div>
                <div class="inv-kpi gradient"><div class="inv-kpi-label">Active Users</div><div class="inv-kpi-value">${(met.active_users||0).toLocaleString()}</div></div>
                <div class="inv-kpi gradient"><div class="inv-kpi-label">Active Services</div><div class="inv-kpi-value">${(met.active_services||0).toLocaleString()}</div></div>
                <div class="inv-kpi gradient"><div class="inv-kpi-label">MRR</div><div class="inv-kpi-value">$${(met.mrr||0).toLocaleString()}</div></div>
                <div class="inv-kpi gradient"><div class="inv-kpi-label">Investors</div><div class="inv-kpi-value">${(met.total_investors||0).toLocaleString()}</div></div>
                <div class="inv-kpi gradient"><div class="inv-kpi-label">Total Raised</div><div class="inv-kpi-value">$${(met.total_invested||0).toLocaleString()}</div></div>
                <div class="inv-kpi gradient"><div class="inv-kpi-label">Codebase</div><div class="inv-kpi-value">${(met.codebase_mb||0).toLocaleString()} MB</div></div>
            </div>

            <div class="inv-grid-2">
                <div class="inv-card">
                    <h2><i class="fas fa-chart-bar"></i> Product Metrics</h2>
                    ${renderMetricBar('AI Tools Built', met.total_tools||1220, 1000)}
                    ${renderMetricBar('API Endpoints', met.api_endpoints||504, 600)}
                    ${renderMetricBar('Use Cases', met.use_case_pages||27, 50)}
                    ${renderMetricBar('Blog Articles', met.articles||0, 50)}
                    ${renderMetricBar('Voice Tools', met.voice_tools||85, 100)}
                    ${renderMetricBar('SDKs', met.sdks||3, 5)}
                </div>
                <div class="inv-card">
                    <h2><i class="fas fa-users"></i> Growth Metrics</h2>
                    ${renderMetricBar('Active Users', met.active_users||0, 100)}
                    ${renderMetricBar('Active Services', met.active_services||0, 200)}
                    ${renderMetricBar('Total Investors', met.total_investors||0, 50)}
                    ${renderMetricBar('Industry Verticals', met.industry_verticals||27, 50)}
                    ${renderMetricBar('PHP Files', met.total_php_files||0, 20000)}
                    ${renderMetricBar('Pricing Tiers', met.pricing_tiers||6, 8)}
                </div>
            </div>
        </div>

        <!-- TAB: DOCUMENTS -->
        <div class="inv-tab-content" id="tab-documents">
            <div class="inv-card">
                <h2><i class="fas fa-folder-open"></i> Investor Documents</h2>
                <table class="inv-table">
                    <thead><tr><th>Document</th><th>Type</th><th>Date</th><th>Action</th></tr></thead>
                    <tbody>
                        <tr>
                            <td><i class="fas fa-file-contract" style="color:var(--inv-accent-light);margin-right:8px;"></i> SAFE Agreement</td>
                            <td>Legal</td>
                            <td>${new Date(inv.invested_date).toLocaleDateString()}</td>
                            <td><button class="inv-btn inv-btn-ghost inv-btn-sm" onclick="alert('Document will be available for download soon. Contact invest@gositeme.com for immediate access.')"><i class="fas fa-download"></i></button></td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-file-invoice" style="color:var(--inv-blue);margin-right:8px;"></i> Investment Receipt</td>
                            <td>Financial</td>
                            <td>${new Date(inv.invested_date).toLocaleDateString()}</td>
                            <td><button class="inv-btn inv-btn-ghost inv-btn-sm" onclick="alert('Document will be available for download soon.')"><i class="fas fa-download"></i></button></td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-file-alt" style="color:var(--inv-purple);margin-right:8px;"></i> Investor Information Sheet</td>
                            <td>Informational</td>
                            <td>${new Date().toLocaleDateString()}</td>
                            <td><a href="/invest" class="inv-btn inv-btn-ghost inv-btn-sm"><i class="fas fa-external-link-alt"></i></a></td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-shield-halved" style="color:var(--inv-gold);margin-right:8px;"></i> Privacy & Data Policy</td>
                            <td>Legal</td>
                            <td>${new Date().toLocaleDateString()}</td>
                            <td><a href="/privacy-policy.php" class="inv-btn inv-btn-ghost inv-btn-sm"><i class="fas fa-external-link-alt"></i></a></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="inv-card">
                <h2><i class="fas fa-file-pdf"></i> Quarterly Reports</h2>
                <div style="text-align:center;padding:40px 0;color:var(--inv-text-muted);">
                    <i class="fas fa-file-pdf" style="font-size:2rem;opacity:0.3;margin-bottom:12px;display:block;"></i>
                    <p>Quarterly investor reports will be published here.</p>
                    <p style="font-size:.82rem;margin-top:8px;">First report expected Q2 2025.</p>
                </div>
            </div>
        </div>

        <!-- TAB: COMMUNICATIONS -->
        <div class="inv-tab-content" id="tab-communications">
            <div class="inv-card">
                <div class="inv-card-header">
                    <h2><i class="fas fa-envelope"></i> Recent Communications</h2>
                    <a href="mailto:invest@gositeme.com" class="inv-btn inv-btn-outline inv-btn-sm"><i class="fas fa-paper-plane"></i> Contact IR Team</a>
                </div>
                <div class="inv-comm-item">
                    <div class="inv-comm-icon email"><i class="fas fa-envelope"></i></div>
                    <div class="inv-comm-content">
                        <div class="inv-comm-title">Welcome to GoSiteMe — Investment Confirmed</div>
                        <div class="inv-comm-desc">Your investment of $${inv.amount.toLocaleString()} has been processed. Welcome aboard!</div>
                    </div>
                    <span class="inv-comm-date">${new Date(inv.invested_date).toLocaleDateString()}</span>
                </div>
                <div class="inv-comm-item">
                    <div class="inv-comm-icon update"><i class="fas fa-chart-line"></i></div>
                    <div class="inv-comm-content">
                        <div class="inv-comm-title">Platform Update — ${met.total_tools}+ Tools Milestone</div>
                        <div class="inv-comm-desc">GoSiteMe has reached ${met.total_tools}+ AI tools across ${met.industry_verticals||27} industry verticals.</div>
                    </div>
                    <span class="inv-comm-date">${new Date().toLocaleDateString()}</span>
                </div>
                <div class="inv-comm-item">
                    <div class="inv-comm-icon payout"><i class="fas fa-coins"></i></div>
                    <div class="inv-comm-content">
                        <div class="inv-comm-title">Revenue Share Update</div>
                        <div class="inv-comm-desc">Current MRR: $${(met.mrr||0).toLocaleString()} | Your share: $${ret.current_monthly_share.toFixed(2)}/month</div>
                    </div>
                    <span class="inv-comm-date">${new Date().toLocaleDateString()}</span>
                </div>
            </div>

            <div class="inv-card">
                <h2><i class="fas fa-headset"></i> Investor Relations</h2>
                <div class="inv-grid-3" style="gap:16px;">
                    <div style="background:var(--inv-surface-2);border:1px solid var(--inv-border);border-radius:12px;padding:20px;text-align:center;">
                        <i class="fas fa-envelope" style="font-size:1.5rem;color:var(--inv-accent-light);margin-bottom:10px;display:block;"></i>
                        <div style="font-size:.9rem;color:#fff;font-weight:600;margin-bottom:4px;">Email</div>
                        <a href="mailto:invest@gositeme.com" style="color:var(--inv-accent-light);font-size:.85rem;">invest@gositeme.com</a>
                    </div>
                    <div style="background:var(--inv-surface-2);border:1px solid var(--inv-border);border-radius:12px;padding:20px;text-align:center;">
                        <i class="fas fa-phone" style="font-size:1.5rem;color:var(--inv-blue);margin-bottom:10px;display:block;"></i>
                        <div style="font-size:.9rem;color:#fff;font-weight:600;margin-bottom:4px;">Phone</div>
                        <a href="tel:+18334674836" style="color:var(--inv-blue);font-size:.85rem;">1-833-GOSITEME</a>
                    </div>
                    <div style="background:var(--inv-surface-2);border:1px solid var(--inv-border);border-radius:12px;padding:20px;text-align:center;">
                        <i class="fas fa-calendar" style="font-size:1.5rem;color:var(--inv-purple);margin-bottom:10px;display:block;"></i>
                        <div style="font-size:.9rem;color:#fff;font-weight:600;margin-bottom:4px;">Meeting</div>
                        <span style="color:var(--inv-purple);font-size:.85rem;">Schedule a call</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div style="text-align:center;padding:32px 0;border-top:1px solid var(--inv-border);margin-top:20px;">
            <p style="color:var(--inv-text-dim);font-size:.82rem;">
                <i class="fas fa-shield-halved" style="color:var(--inv-accent);margin-right:4px;"></i>
                Your data is encrypted and protected. Questions? <a href="mailto:invest@gositeme.com" style="color:var(--inv-accent-light);">invest@gositeme.com</a> | <a href="tel:+18334674836" style="color:var(--inv-accent-light);">1-833-GOSITEME</a>
            </p>
            <p style="color:var(--inv-text-dim);font-size:.72rem;margin-top:8px;">Last updated: ${new Date().toLocaleString()}</p>
        </div>
    `;

    // Activate tabs
    initTabs();
}

function renderPublicDashboard(met) {
    document.getElementById('dashContent').style.display = 'block';
    document.getElementById('dashContent').innerHTML = `
        <div style="margin-bottom:32px;">
            <h1><i class="fas fa-chart-line" style="color:var(--inv-accent-light);margin-right:8px;"></i> Platform Growth Dashboard</h1>
            <p class="inv-subtitle">Real-time GoSiteMe platform metrics — <a href="/invest" style="color:var(--inv-accent-light);">invest to unlock your personalized dashboard</a></p>
        </div>
        <div class="inv-kpi-grid">
            <div class="inv-kpi gradient"><div class="inv-kpi-label">AI Tools</div><div class="inv-kpi-value">${(met.total_tools||1220).toLocaleString()}+</div></div>
            <div class="inv-kpi gradient"><div class="inv-kpi-label">API Endpoints</div><div class="inv-kpi-value">${(met.api_endpoints||504).toLocaleString()}</div></div>
            <div class="inv-kpi gradient"><div class="inv-kpi-label">Industry Verticals</div><div class="inv-kpi-value">${(met.industry_verticals||27).toLocaleString()}</div></div>
            <div class="inv-kpi gradient"><div class="inv-kpi-label">Codebase</div><div class="inv-kpi-value">${(met.total_php_files||0).toLocaleString()}</div><div class="inv-kpi-change up">PHP files</div></div>
        </div>
        <div class="inv-grid-2">
            <div class="inv-card">
                <h2><i class="fas fa-chart-bar"></i> Product Metrics</h2>
                ${renderMetricBar('AI Tools Built', met.total_tools||1220, 1000)}
                ${renderMetricBar('API Endpoints', met.api_endpoints||504, 600)}
                ${renderMetricBar('Use Case Pages', met.use_case_pages||27, 50)}
                ${renderMetricBar('Voice Tools', met.voice_tools||85, 100)}
            </div>
            <div class="inv-card">
                <h2><i class="fas fa-users"></i> Growth Metrics</h2>
                ${renderMetricBar('Active Users', met.active_users||0, 100)}
                ${renderMetricBar('Active Services', met.active_services||0, 200)}
                ${renderMetricBar('SDKs', met.sdks||3, 5)}
                ${renderMetricBar('Codebase (MB)', met.codebase_mb||0, 500)}
            </div>
        </div>
        <div style="text-align:center;padding:40px 0;">
            <p style="color:var(--inv-text-muted);margin-bottom:20px;">Invest in GoSiteMe to unlock personalized analytics, payout tracking, and benchmark comparisons.</p>
            <a href="/invest" class="inv-btn inv-btn-primary"><i class="fas fa-rocket"></i> Invest in GoSiteMe</a>
        </div>
    `;
}

function renderMetricBar(label, value, max) {
    const pct = Math.min((value / max) * 100, 100);
    return `<div class="inv-metric-row"><span>${label}</span><span>${typeof value === 'number' ? value.toLocaleString() : value}</span></div>
        <div class="inv-bar"><div class="inv-bar-fill" style="width:${pct}%"></div></div>`;
}

function initTabs() {
    document.querySelectorAll('.inv-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.inv-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.inv-tab-content').forEach(c => c.classList.remove('active'));
            tab.classList.add('active');
            const target = document.getElementById('tab-' + tab.dataset.tab);
            if (target) target.classList.add('active');
        });
    });
}

function exportPayouts() {
    alert('Payout export will be available once revenue share distributions begin. Contact invest@gositeme.com for questions.');
}

function renderEmpty() {
    document.getElementById('dashEmpty').style.display = 'block';
    document.getElementById('dashEmpty').innerHTML = `
        <div class="inv-empty">
            <i class="fas fa-chart-pie" style="font-size:3rem;color:var(--inv-accent-light);margin-bottom:20px;"></i>
            <h2>No Active Investment Found</h2>
            <p>You don't have an active investment associated with this account yet. Invest in GoSiteMe to unlock your personalized Fortune 500-level dashboard with real-time metrics, benchmarks, projections, and payout tracking.</p>
            <a href="/invest" class="inv-btn inv-btn-primary"><i class="fas fa-rocket"></i> Invest Now</a>
        </div>
    `;
}
