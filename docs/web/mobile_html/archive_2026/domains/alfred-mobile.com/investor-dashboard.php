<?php
$page_title = 'Investor Dashboard — GoSiteMe Growth Tracker';
$page_description = 'Track your GoSiteMe investment, returns, and platform growth in real time.';
$page_canonical = 'https://gositeme.com/investor-dashboard';
include __DIR__ . '/includes/auth-gate.inc.php';
include __DIR__ . '/includes/site-header.inc.php';
?>

<style>
:root{--inv-bg:#0a0a14;--inv-surface:#12121e;--inv-surface-2:#1a1a2e;--inv-surface-3:#222240;--inv-border:rgba(255,255,255,0.08);--inv-border-hover:rgba(255,255,255,0.18);--inv-accent:#00b894;--inv-accent-light:#55efc4;--inv-gold:#fdcb6e;--inv-purple:#6c5ce7;--inv-blue:#0984e3;--inv-red:#e17055;--inv-text:#e8e8f0;--inv-text-muted:#8a8a9a;--inv-text-dim:#5a5a6e;--inv-radius:16px;--inv-gradient:linear-gradient(135deg,#00b894 0%,#0984e3 50%,#6c5ce7 100%)}

/* Layout */
.inv-dash{max-width:1360px;margin:0 auto;padding:90px 24px 60px}
.inv-dash-header{display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px;margin-bottom:28px}
.inv-dash h1{font-family:'Space Grotesk',sans-serif;font-size:1.8rem;font-weight:700;color:#fff;margin:0 0 4px;display:flex;align-items:center;gap:10px}
.inv-dash .inv-subtitle{color:var(--inv-text-muted);font-size:.95rem;margin:0}
.inv-header-actions{display:flex;align-items:center;gap:12px;flex-wrap:wrap}

/* Badge */
.inv-badge{display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:20px;font-size:.8rem;font-weight:600}
.inv-badge.funded{background:rgba(0,184,148,0.15);color:#55efc4}
.inv-badge.approved{background:rgba(253,203,110,0.15);color:#fdcb6e}
.inv-badge.pending{background:rgba(108,92,231,0.15);color:#a29bfe}

/* Tab Navigation */
.inv-tabs{display:flex;gap:4px;border-bottom:1px solid var(--inv-border);margin-bottom:28px;overflow-x:auto;padding-bottom:0}
.inv-tab{padding:12px 20px;color:var(--inv-text-muted);font-size:.9rem;font-weight:500;cursor:pointer;border-bottom:2px solid transparent;transition:all .3s;white-space:nowrap;font-family:'Inter',sans-serif;background:none;border:none;border-bottom:2px solid transparent}
.inv-tab:hover{color:#fff}
.inv-tab.active{color:var(--inv-accent-light);border-bottom-color:var(--inv-accent)}
.inv-tab-content{display:none}.inv-tab-content.active{display:block}

/* KPI Grid */
.inv-kpi-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;margin-bottom:28px}
.inv-kpi{background:var(--inv-surface);border:1px solid var(--inv-border);border-radius:var(--inv-radius);padding:22px;position:relative;overflow:hidden;transition:all .3s}
.inv-kpi:hover{border-color:var(--inv-border-hover);transform:translateY(-2px)}
.inv-kpi::before{content:'';position:absolute;top:0;left:0;right:0;height:3px}
.inv-kpi.green::before{background:linear-gradient(90deg,#00b894,#55efc4)}
.inv-kpi.blue::before{background:linear-gradient(90deg,#0984e3,#74b9ff)}
.inv-kpi.purple::before{background:linear-gradient(90deg,#6c5ce7,#a29bfe)}
.inv-kpi.gold::before{background:linear-gradient(90deg,#fdcb6e,#ffeaa7)}
.inv-kpi.gradient::before{background:var(--inv-gradient)}
.inv-kpi-label{font-size:.75rem;color:var(--inv-text-muted);text-transform:uppercase;letter-spacing:.8px;margin-bottom:6px}
.inv-kpi-value{font-family:'Space Grotesk',sans-serif;font-size:1.7rem;font-weight:800;background:var(--inv-gradient);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.inv-kpi-change{font-size:.78rem;margin-top:4px;display:flex;align-items:center;gap:4px}
.inv-kpi-change.up{color:#55efc4}
.inv-kpi-change.down{color:#e17055}
.inv-kpi-change.neutral{color:var(--inv-text-muted)}
.inv-kpi-icon{position:absolute;top:18px;right:18px;font-size:1.3rem;opacity:0.15;color:#fff}

/* Cards */
.inv-card{background:var(--inv-surface);border:1px solid var(--inv-border);border-radius:var(--inv-radius);padding:28px;margin-bottom:20px;transition:border-color .3s}
.inv-card:hover{border-color:var(--inv-border-hover)}
.inv-card h2{font-family:'Space Grotesk',sans-serif;font-size:1.15rem;color:#fff;margin:0 0 20px;display:flex;align-items:center;gap:10px}
.inv-card h2 i{color:var(--inv-accent-light);font-size:1rem}
.inv-card-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px}
.inv-card-header h2{margin:0}

/* Grid layouts */
.inv-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.inv-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px}
.inv-grid-4{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}

/* Table */
.inv-table{width:100%;border-collapse:collapse}
.inv-table th,.inv-table td{padding:12px 14px;text-align:left;border-bottom:1px solid var(--inv-border);font-size:.88rem}
.inv-table th{color:var(--inv-text-muted);font-weight:600;font-size:.75rem;text-transform:uppercase;letter-spacing:.5px}
.inv-table td{color:var(--inv-text)}
.inv-table .amount{color:var(--inv-accent-light);font-weight:700;font-family:'Space Grotesk',sans-serif}
.inv-table tr:hover td{background:rgba(255,255,255,0.02)}

/* Chart bars */
.inv-chart-container{position:relative;padding:8px 0}
.inv-bar-chart{display:flex;align-items:flex-end;gap:8px;height:180px;padding:0 8px}
.inv-bar-col{flex:1;display:flex;flex-direction:column;align-items:center;gap:4px}
.inv-bar-rect{width:100%;border-radius:6px 6px 0 0;background:var(--inv-gradient);min-height:4px;transition:height 1s ease}
.inv-bar-label{font-size:.7rem;color:var(--inv-text-muted);text-align:center}
.inv-bar-value{font-size:.72rem;color:var(--inv-accent-light);font-weight:600;font-family:'Space Grotesk',sans-serif}

/* Metric bar */
.inv-bar{height:20px;border-radius:12px;background:var(--inv-surface-2);overflow:hidden;margin:4px 0 12px}
.inv-bar-fill{height:100%;border-radius:12px;background:var(--inv-gradient);transition:width 1.2s ease}
.inv-metric-row{display:flex;justify-content:space-between;align-items:center;font-size:.88rem;color:var(--inv-text)}
.inv-metric-row span:last-child{color:var(--inv-accent-light);font-weight:700;font-family:'Space Grotesk',sans-serif}

/* Projections */
.inv-proj-card{background:var(--inv-surface-2);border:1px solid var(--inv-border);border-radius:12px;padding:20px;text-align:center;transition:all .3s}
.inv-proj-card:hover{border-color:var(--inv-border-hover);transform:translateY(-2px)}
.inv-proj-card .proj-label{font-size:.72rem;color:var(--inv-text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:6px}
.inv-proj-card .proj-value{font-family:'Space Grotesk',sans-serif;font-size:1.4rem;font-weight:700;color:var(--inv-accent-light)}
.inv-proj-card .proj-sub{font-size:.78rem;color:var(--inv-text-muted);margin-top:4px}

/* Timeline */
.inv-timeline{position:relative;padding-left:28px}
.inv-timeline::before{content:'';position:absolute;left:8px;top:0;bottom:0;width:2px;background:var(--inv-border)}
.inv-timeline-item{position:relative;padding-bottom:24px}
.inv-timeline-item::before{content:'';position:absolute;left:-24px;top:4px;width:12px;height:12px;border-radius:50%;background:var(--inv-accent);border:2px solid var(--inv-bg)}
.inv-timeline-item.future::before{background:var(--inv-surface-3);border-color:var(--inv-border)}
.inv-timeline-date{font-size:.75rem;color:var(--inv-text-muted);margin-bottom:4px}
.inv-timeline-title{font-size:.92rem;color:#fff;font-weight:600;margin-bottom:2px}
.inv-timeline-desc{font-size:.82rem;color:var(--inv-text-muted)}

/* Benchmark comparison */
.inv-benchmark{display:flex;align-items:center;gap:12px;padding:14px 0;border-bottom:1px solid var(--inv-border)}
.inv-benchmark:last-child{border:none}
.inv-benchmark-name{min-width:130px;font-size:.88rem;color:var(--inv-text)}
.inv-benchmark-bar{flex:1;height:24px;background:var(--inv-surface-2);border-radius:12px;overflow:hidden;position:relative}
.inv-benchmark-fill{height:100%;border-radius:12px;transition:width 1.2s ease}
.inv-benchmark-value{font-size:.85rem;font-weight:700;font-family:'Space Grotesk',sans-serif;min-width:70px;text-align:right}
.inv-benchmark-fill.green{background:linear-gradient(90deg,#00b894,#55efc4)}
.inv-benchmark-fill.blue{background:linear-gradient(90deg,#0984e3,#74b9ff)}
.inv-benchmark-fill.gold{background:linear-gradient(90deg,#856d00,#fdcb6e)}
.inv-benchmark-fill.red{background:linear-gradient(90deg,#c0392b,#e17055)}
.inv-benchmark-fill.purple{background:linear-gradient(90deg,#6c5ce7,#a29bfe)}

/* Payout table */
.inv-payout-status{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:12px;font-size:.75rem;font-weight:600}
.inv-payout-status.paid{background:rgba(0,184,148,0.15);color:#55efc4}
.inv-payout-status.approved{background:rgba(253,203,110,0.15);color:#fdcb6e}
.inv-payout-status.calculated{background:rgba(108,92,231,0.15);color:#a29bfe}

/* Buttons */
.inv-btn{display:inline-flex;align-items:center;gap:8px;padding:10px 24px;border-radius:12px;font-weight:600;font-size:.88rem;text-decoration:none;transition:all .3s;cursor:pointer;border:none;font-family:'Inter',sans-serif}
.inv-btn-primary{background:var(--inv-gradient);color:#fff;box-shadow:0 4px 18px rgba(0,184,148,0.25)}
.inv-btn-primary:hover{transform:translateY(-2px);box-shadow:0 8px 28px rgba(0,184,148,0.4);color:#fff}
.inv-btn-outline{background:transparent;color:var(--inv-accent-light);border:1px solid var(--inv-accent);padding:9px 23px}
.inv-btn-outline:hover{background:rgba(0,184,148,0.1);color:#fff}
.inv-btn-sm{padding:6px 14px;font-size:.8rem;border-radius:8px}
.inv-btn-ghost{background:rgba(255,255,255,0.05);color:var(--inv-text);border:1px solid var(--inv-border)}
.inv-btn-ghost:hover{background:rgba(255,255,255,0.1);color:#fff}

/* Donut chart */
.inv-donut-wrap{position:relative;width:160px;height:160px;margin:0 auto}
.inv-donut-center{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center}
.inv-donut-center .big{font-family:'Space Grotesk',sans-serif;font-size:1.5rem;font-weight:800;color:#fff}
.inv-donut-center .sub{font-size:.72rem;color:var(--inv-text-muted)}

/* Empty state */
.inv-empty{text-align:center;padding:80px 20px}
.inv-empty h2{font-family:'Space Grotesk',sans-serif;color:#fff;margin-bottom:12px}
.inv-empty p{color:var(--inv-text-muted);max-width:500px;margin:0 auto 24px;line-height:1.7}

/* Communication */
.inv-comm-item{padding:16px;border-bottom:1px solid var(--inv-border);display:flex;gap:14px;transition:background .2s}
.inv-comm-item:hover{background:rgba(255,255,255,0.02)}
.inv-comm-item:last-child{border:none}
.inv-comm-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:.85rem}
.inv-comm-icon.email{background:rgba(9,132,227,0.15);color:#74b9ff}
.inv-comm-icon.update{background:rgba(0,184,148,0.15);color:#55efc4}
.inv-comm-icon.payout{background:rgba(253,203,110,0.15);color:#fdcb6e}
.inv-comm-icon.alert{background:rgba(225,112,85,0.15);color:#fab1a0}
.inv-comm-content{flex:1;min-width:0}
.inv-comm-title{font-size:.88rem;color:#fff;font-weight:600;margin-bottom:2px}
.inv-comm-desc{font-size:.8rem;color:var(--inv-text-muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.inv-comm-date{font-size:.72rem;color:var(--inv-text-dim);flex-shrink:0}

/* Print */
@media print{.inv-dash{padding:20px}.navbar,.urgency-banner,.top-bar,.mobile-menu,footer,.alfred-widget{display:none!important}.inv-card,.inv-kpi{break-inside:avoid}}

/* Responsive */
@media(max-width:1024px){.inv-grid-4{grid-template-columns:repeat(2,1fr)}}
@media(max-width:768px){.inv-grid-2,.inv-grid-3{grid-template-columns:1fr}.inv-kpi-grid{grid-template-columns:1fr 1fr}.inv-dash-header{flex-direction:column}}
@media(max-width:480px){.inv-kpi-grid{grid-template-columns:1fr}.inv-grid-4{grid-template-columns:1fr}}
</style>

<div class="inv-dash" id="investorDash">
    <div id="dashLoading" style="text-align:center;padding:60px 0;">
        <i class="fas fa-spinner fa-spin" style="font-size:2rem;color:var(--inv-accent-light);"></i>
        <p style="color:var(--inv-text-muted);margin-top:16px;">Loading your investor dashboard...</p>
    </div>
    <div id="dashContent" style="display:none;"></div>
    <div id="dashEmpty" style="display:none;"></div>
</div>


<script src="/assets/js/investor-dashboard-engine.js"></script>

<div style="text-align:center;padding:8px 0;font-size:10px;color:rgba(255,255,255,0.35)">
    Investment involves risk. Past performance is not indicative of future results. Not financial advice. Please review our
    <a href="/privacy-policy/" style="color:inherit;text-decoration:underline">Privacy Policy</a> and
    <a href="/terms-of-service.php" style="color:inherit;text-decoration:underline">Terms of Service</a>.
</div>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
