<?php
$page_title = 'Investor Command Center — GoSiteMe';
$page_description = 'Enterprise admin dashboard for managing investors, tracking metrics, and portfolio performance.';
$page_canonical = 'https://gositeme.com/investor-admin';
$page_robots = 'noindex, nofollow';
include __DIR__ . '/includes/auth-gate.inc.php';
include __DIR__ . '/includes/site-header.inc.php';

$adminEmails = ['gositeme@gmail.com'];
if (!$clientEmail || !in_array(strtolower($clientEmail), $adminEmails)) {
    header('Location: /investor-dashboard.php');
    exit;
}
?>

<style>
/* ═══════════════════════════════════════════════════════════════
   INVESTOR COMMAND CENTER — Enterprise Admin Theme
   ═══════════════════════════════════════════════════════════════ */
:root{
  --inv-bg:#0a0a14;--inv-surface:#12121e;--inv-surface-2:#1a1a2e;--inv-surface-3:#222240;
  --inv-border:rgba(255,255,255,0.08);--inv-border-focus:rgba(0,184,148,0.4);
  --inv-accent:#00b894;--inv-accent-light:#55efc4;--inv-accent-dark:#00896d;
  --inv-gold:#fdcb6e;--inv-purple:#6c5ce7;--inv-blue:#0984e3;--inv-red:#ff6b6b;
  --inv-orange:#e17055;--inv-cyan:#00cec9;
  --inv-text:#e8e8f0;--inv-text-muted:#8a8a9a;--inv-text-dim:#555570;
  --inv-radius:16px;--inv-radius-sm:10px;--inv-radius-xs:6px;
  --inv-gradient:linear-gradient(135deg,#00b894 0%,#0984e3 50%,#6c5ce7 100%);
  --inv-gradient-gold:linear-gradient(135deg,#fdcb6e 0%,#e17055 100%);
  --inv-shadow:0 8px 32px rgba(0,0,0,0.3);
}

/* Layout */
.icc{max-width:1480px;margin:0 auto;padding:100px 24px 60px;min-height:100vh}
.icc-header{display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px;margin-bottom:32px}
.icc-header h1{font-family:'Space Grotesk',sans-serif;font-size:2.2rem;font-weight:800;color:#fff;margin:0;display:flex;align-items:center;gap:14px}
.icc-header h1 i{background:var(--inv-gradient);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.icc-sub{color:var(--inv-text-muted);font-size:1rem;margin:6px 0 0}
.icc-badge{display:inline-flex;align-items:center;gap:6px;padding:5px 16px;border-radius:20px;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
.icc-badge.admin{background:rgba(253,203,110,0.15);color:var(--inv-gold);border:1px solid rgba(253,203,110,0.2)}
.icc-header-actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center}

/* Tabs Navigation */
.icc-tabs{display:flex;gap:4px;background:var(--inv-surface);border:1px solid var(--inv-border);border-radius:var(--inv-radius);padding:5px;margin-bottom:28px;overflow-x:auto}
.icc-tab{padding:10px 22px;border-radius:12px;font-size:.88rem;font-weight:600;color:var(--inv-text-muted);cursor:pointer;transition:all .25s;border:none;background:none;white-space:nowrap;font-family:'Space Grotesk',sans-serif;display:flex;align-items:center;gap:8px}
.icc-tab:hover{color:var(--inv-text);background:var(--inv-surface-2)}
.icc-tab.active{color:#fff;background:var(--inv-gradient);box-shadow:0 4px 16px rgba(0,184,148,0.25)}
.icc-tab .tab-count{background:rgba(255,255,255,0.15);padding:2px 8px;border-radius:10px;font-size:.72rem;font-weight:700}
.icc-tab-panel{display:none}
.icc-tab-panel.active{display:block}

/* Cards */
.icc-card{background:var(--inv-surface);border:1px solid var(--inv-border);border-radius:var(--inv-radius);padding:24px;margin-bottom:24px;transition:border-color .2s}
.icc-card:hover{border-color:rgba(255,255,255,0.12)}
.icc-card h2{font-family:'Space Grotesk',sans-serif;font-size:1.2rem;color:#fff;margin:0 0 18px;display:flex;align-items:center;gap:10px}
.icc-card h2 i{color:var(--inv-accent-light);font-size:1rem}
.icc-card h2 .card-actions{margin-left:auto;display:flex;gap:8px}

/* KPI Grid */
.icc-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px}
.icc-kpi{background:var(--inv-surface);border:1px solid var(--inv-border);border-radius:var(--inv-radius);padding:22px;position:relative;overflow:hidden;transition:transform .2s,border-color .2s}
.icc-kpi:hover{transform:translateY(-2px);border-color:rgba(0,184,148,0.25)}
.icc-kpi::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--inv-gradient)}
.icc-kpi-icon{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;margin-bottom:14px}
.icc-kpi-icon.green{background:rgba(0,184,148,0.12);color:var(--inv-accent-light)}
.icc-kpi-icon.purple{background:rgba(108,92,231,0.12);color:#a29bfe}
.icc-kpi-icon.blue{background:rgba(9,132,227,0.12);color:#74b9ff}
.icc-kpi-icon.gold{background:rgba(253,203,110,0.12);color:var(--inv-gold)}
.icc-kpi-icon.red{background:rgba(255,107,107,0.12);color:var(--inv-red)}
.icc-kpi-icon.cyan{background:rgba(0,206,201,0.12);color:var(--inv-cyan)}
.icc-kpi-icon.orange{background:rgba(225,112,85,0.12);color:var(--inv-orange)}
.icc-kpi-label{font-size:.72rem;color:var(--inv-text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:6px}
.icc-kpi-value{font-family:'Space Grotesk',sans-serif;font-size:1.9rem;font-weight:800;background:var(--inv-gradient);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.icc-kpi-sub{font-size:.76rem;color:var(--inv-text-muted);margin-top:4px;display:flex;align-items:center;gap:4px}
.icc-kpi-sub .up{color:var(--inv-accent-light)}
.icc-kpi-sub .down{color:var(--inv-red)}

/* Pipeline Funnel */
.icc-funnel{display:flex;flex-direction:column;align-items:center;gap:0;margin:20px 0 24px}
.icc-funnel-step{position:relative;text-align:center;padding:16px 20px;color:#fff;font-weight:600;font-size:.92rem;font-family:'Space Grotesk',sans-serif;transition:all .2s;cursor:default;border-radius:4px;display:flex;align-items:center;justify-content:space-between}
.icc-funnel-step:hover{filter:brightness(1.15)}
.icc-funnel-step .funnel-label{flex:1;text-align:left}
.icc-funnel-step .funnel-count{font-size:1.4rem;font-weight:800;margin:0 16px}
.icc-funnel-step .funnel-pct{font-size:.78rem;opacity:0.8;min-width:50px;text-align:right}
.icc-funnel-step:nth-child(1){background:rgba(108,92,231,0.3);border:1px solid rgba(108,92,231,0.4);width:100%}
.icc-funnel-step:nth-child(2){background:rgba(9,132,227,0.3);border:1px solid rgba(9,132,227,0.4);width:85%}
.icc-funnel-step:nth-child(3){background:rgba(253,203,110,0.3);border:1px solid rgba(253,203,110,0.4);width:65%}
.icc-funnel-step:nth-child(4){background:rgba(0,184,148,0.3);border:1px solid rgba(0,184,148,0.4);width:45%}
.icc-funnel-step:nth-child(5){background:rgba(255,107,107,0.15);border:1px solid rgba(255,107,107,0.3);width:30%}
.icc-funnel-arrow{color:var(--inv-text-dim);font-size:.8rem;margin:2px 0}

/* Status Badges */
.st-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:16px;font-size:.74rem;font-weight:600;text-transform:capitalize}
.st-badge.pending{background:rgba(108,92,231,0.12);color:#a29bfe}
.st-badge.contacted{background:rgba(9,132,227,0.12);color:#74b9ff}
.st-badge.approved{background:rgba(253,203,110,0.12);color:#fdcb6e}
.st-badge.funded{background:rgba(0,184,148,0.12);color:#55efc4}
.st-badge.declined{background:rgba(255,107,107,0.12);color:#ff6b6b}
.tier-badge{padding:3px 10px;border-radius:12px;font-size:.74rem;font-weight:600;text-transform:capitalize}
.tier-badge.seed{background:rgba(108,92,231,0.12);color:#a29bfe}
.tier-badge.growth{background:rgba(0,184,148,0.12);color:#55efc4}
.tier-badge.strategic{background:rgba(253,203,110,0.12);color:#fdcb6e}

/* Buttons */
.icc-btn{padding:8px 18px;border-radius:var(--inv-radius-sm);font-size:.82rem;font-weight:600;border:none;cursor:pointer;transition:all .2s;font-family:'Space Grotesk',sans-serif;display:inline-flex;align-items:center;gap:6px;white-space:nowrap}
.icc-btn-sm{padding:5px 12px;font-size:.74rem;border-radius:var(--inv-radius-xs)}
.icc-btn.primary{background:var(--inv-gradient);color:#fff;box-shadow:0 4px 16px rgba(0,184,148,0.2)}
.icc-btn.primary:hover{box-shadow:0 6px 20px rgba(0,184,148,0.35);transform:translateY(-1px)}
.icc-btn.green{background:rgba(0,184,148,0.15);color:#55efc4}
.icc-btn.green:hover{background:rgba(0,184,148,0.3)}
.icc-btn.blue{background:rgba(9,132,227,0.15);color:#74b9ff}
.icc-btn.blue:hover{background:rgba(9,132,227,0.3)}
.icc-btn.gold{background:rgba(253,203,110,0.15);color:#fdcb6e}
.icc-btn.gold:hover{background:rgba(253,203,110,0.3)}
.icc-btn.red{background:rgba(255,107,107,0.15);color:#ff6b6b}
.icc-btn.red:hover{background:rgba(255,107,107,0.3)}
.icc-btn.purple{background:rgba(108,92,231,0.15);color:#a29bfe}
.icc-btn.purple:hover{background:rgba(108,92,231,0.3)}
.icc-btn.ghost{background:transparent;color:var(--inv-text-muted);border:1px solid var(--inv-border)}
.icc-btn.ghost:hover{border-color:var(--inv-accent);color:var(--inv-accent-light)}

/* Table */
.icc-table-wrap{overflow-x:auto;margin:0 -4px}
.icc-table{width:100%;border-collapse:collapse;font-size:.86rem}
.icc-table th,.icc-table td{padding:13px 14px;text-align:left;border-bottom:1px solid var(--inv-border)}
.icc-table th{color:var(--inv-text-muted);font-weight:600;font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;white-space:nowrap;cursor:pointer;user-select:none;position:sticky;top:0;background:var(--inv-surface)}
.icc-table th:hover{color:var(--inv-accent-light)}
.icc-table th .sort-icon{margin-left:4px;font-size:.6rem;opacity:0.5}
.icc-table th.sorted .sort-icon{opacity:1;color:var(--inv-accent-light)}
.icc-table td{color:var(--inv-text)}
.icc-table .amt{color:var(--inv-accent-light);font-weight:700;font-family:'Space Grotesk',sans-serif}
.icc-table tr:hover{background:rgba(255,255,255,0.02)}
.icc-table tr.expanded-parent{background:rgba(0,184,148,0.04);border-bottom:none}
.icc-table tr.expanded-parent td{border-bottom:none}
.icc-table .expand-row{background:rgba(0,184,148,0.02)}
.icc-table .expand-row td{padding:0 14px 14px 50px;border-bottom:1px solid var(--inv-border)}
.icc-table .checkbox-col{width:36px;text-align:center}
.icc-table input[type="checkbox"]{accent-color:var(--inv-accent);width:16px;height:16px;cursor:pointer}

/* Search & Toolbar */
.icc-toolbar{display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:16px}
.icc-search{flex:1;min-width:200px;padding:10px 16px 10px 40px;border-radius:var(--inv-radius-sm);border:1px solid var(--inv-border);background:var(--inv-surface-2);color:var(--inv-text);font-size:.9rem;font-family:inherit;transition:border-color .2s}
.icc-search:focus{outline:none;border-color:var(--inv-border-focus)}
.icc-search-wrap{position:relative;flex:1;min-width:200px}
.icc-search-wrap i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--inv-text-muted);font-size:.85rem}
.icc-bulk-actions{display:flex;gap:8px;align-items:center}
.icc-bulk-actions select{padding:8px 12px;border-radius:var(--inv-radius-xs);border:1px solid var(--inv-border);background:var(--inv-surface-2);color:var(--inv-text);font-size:.82rem;font-family:inherit}
.icc-bulk-count{font-size:.82rem;color:var(--inv-accent-light);font-weight:600;min-width:80px}

/* Filters */
.icc-filters{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px}
.icc-filter{padding:6px 16px;border-radius:20px;background:var(--inv-surface-2);border:1px solid var(--inv-border);color:var(--inv-text-muted);font-size:.82rem;cursor:pointer;transition:all .2s;font-family:inherit}
.icc-filter:hover,.icc-filter.active{border-color:var(--inv-accent);color:var(--inv-accent-light)}

/* Pagination */
.icc-pagination{display:flex;gap:6px;align-items:center;justify-content:center;margin-top:18px;flex-wrap:wrap}
.icc-pagination button{padding:6px 12px;border-radius:var(--inv-radius-xs);border:1px solid var(--inv-border);background:var(--inv-surface-2);color:var(--inv-text-muted);font-size:.82rem;cursor:pointer;transition:all .2s;font-family:inherit}
.icc-pagination button:hover{border-color:var(--inv-accent);color:var(--inv-accent-light)}
.icc-pagination button.active{background:var(--inv-gradient);color:#fff;border-color:transparent}
.icc-pagination button:disabled{opacity:0.3;cursor:not-allowed}
.icc-pagination .page-info{font-size:.82rem;color:var(--inv-text-muted);padding:0 8px}

/* Kanban Pipeline */
.icc-kanban{display:grid;grid-template-columns:repeat(5,1fr);gap:14px;min-height:300px}
.icc-kanban-col{background:var(--inv-surface);border:1px solid var(--inv-border);border-radius:var(--inv-radius);padding:16px;min-height:200px}
.icc-kanban-col-header{font-family:'Space Grotesk',sans-serif;font-size:.82rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:14px;display:flex;align-items:center;justify-content:space-between;padding-bottom:10px;border-bottom:2px solid}
.icc-kanban-col-header .col-count{padding:2px 8px;border-radius:10px;font-size:.72rem}
.icc-kanban-col.pending .icc-kanban-col-header{color:#a29bfe;border-color:#6c5ce7}
.icc-kanban-col.pending .col-count{background:rgba(108,92,231,0.15);color:#a29bfe}
.icc-kanban-col.contacted .icc-kanban-col-header{color:#74b9ff;border-color:#0984e3}
.icc-kanban-col.contacted .col-count{background:rgba(9,132,227,0.15);color:#74b9ff}
.icc-kanban-col.approved .icc-kanban-col-header{color:#fdcb6e;border-color:#fdcb6e}
.icc-kanban-col.approved .col-count{background:rgba(253,203,110,0.15);color:#fdcb6e}
.icc-kanban-col.funded .icc-kanban-col-header{color:#55efc4;border-color:#00b894}
.icc-kanban-col.funded .col-count{background:rgba(0,184,148,0.15);color:#55efc4}
.icc-kanban-col.declined .icc-kanban-col-header{color:#ff6b6b;border-color:#ff6b6b}
.icc-kanban-col.declined .col-count{background:rgba(255,107,107,0.15);color:#ff6b6b}
.icc-kanban-card{background:var(--inv-surface-2);border:1px solid var(--inv-border);border-radius:var(--inv-radius-sm);padding:14px;margin-bottom:10px;cursor:pointer;transition:all .2s}
.icc-kanban-card:hover{border-color:rgba(255,255,255,0.15);transform:translateY(-1px);box-shadow:var(--inv-shadow)}
.icc-kanban-card .card-name{font-weight:600;color:#fff;font-size:.88rem;margin-bottom:6px}
.icc-kanban-card .card-amount{font-family:'Space Grotesk',sans-serif;font-weight:700;color:var(--inv-accent-light);font-size:.92rem}
.icc-kanban-card .card-meta{font-size:.72rem;color:var(--inv-text-muted);margin-top:6px;display:flex;justify-content:space-between;align-items:center}

/* Analytics Charts */
.icc-chart-container{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px}
.icc-bar-chart{display:flex;align-items:flex-end;gap:10px;height:200px;padding:20px 10px 0}
.icc-bar{display:flex;flex-direction:column;align-items:center;flex:1;gap:6px}
.icc-bar-fill{width:100%;min-width:30px;border-radius:6px 6px 0 0;transition:height .5s ease;position:relative}
.icc-bar-fill.gradient{background:var(--inv-gradient)}
.icc-bar-fill.green{background:rgba(0,184,148,0.6)}
.icc-bar-fill.blue{background:rgba(9,132,227,0.6)}
.icc-bar-fill.purple{background:rgba(108,92,231,0.6)}
.icc-bar-fill.gold{background:rgba(253,203,110,0.6)}
.icc-bar-value{font-family:'Space Grotesk',sans-serif;font-size:.72rem;color:var(--inv-accent-light);font-weight:700}
.icc-bar-label{font-size:.68rem;color:var(--inv-text-muted);text-align:center;white-space:nowrap}

/* Quick Actions */
.icc-actions-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px}
.icc-action-card{background:var(--inv-surface-2);border:1px solid var(--inv-border);border-radius:var(--inv-radius-sm);padding:18px;cursor:pointer;transition:all .2s;text-align:center;text-decoration:none}
.icc-action-card:hover{border-color:var(--inv-accent);background:rgba(0,184,148,0.05);transform:translateY(-2px)}
.icc-action-card i{font-size:1.5rem;margin-bottom:10px;display:block}
.icc-action-card span{font-size:.86rem;color:var(--inv-text);font-weight:600;display:block}
.icc-action-card small{font-size:.72rem;color:var(--inv-text-muted);margin-top:4px;display:block}

/* Activity Feed */
.icc-activity{list-style:none;padding:0;margin:0}
.icc-activity li{padding:12px 0;border-bottom:1px solid var(--inv-border);display:flex;align-items:flex-start;gap:12px;font-size:.86rem}
.icc-activity li:last-child{border-bottom:none}
.icc-activity .act-icon{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:.75rem}
.icc-activity .act-icon.green{background:rgba(0,184,148,0.12);color:#55efc4}
.icc-activity .act-icon.blue{background:rgba(9,132,227,0.12);color:#74b9ff}
.icc-activity .act-icon.purple{background:rgba(108,92,231,0.12);color:#a29bfe}
.icc-activity .act-icon.gold{background:rgba(253,203,110,0.12);color:#fdcb6e}
.icc-activity .act-text{color:var(--inv-text)}
.icc-activity .act-text strong{color:#fff}
.icc-activity .act-time{font-size:.72rem;color:var(--inv-text-dim);margin-top:2px}

/* Settings */
.icc-setting-group{margin-bottom:28px}
.icc-setting-group h3{font-family:'Space Grotesk',sans-serif;font-size:1rem;color:#fff;margin:0 0 14px;padding-bottom:10px;border-bottom:1px solid var(--inv-border)}
.icc-setting-row{display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid rgba(255,255,255,0.04)}
.icc-setting-row label{font-size:.88rem;color:var(--inv-text)}
.icc-setting-row small{font-size:.76rem;color:var(--inv-text-muted);display:block;margin-top:2px}
.icc-toggle{position:relative;width:44px;height:24px;flex-shrink:0}
.icc-toggle input{opacity:0;width:0;height:0}
.icc-toggle .slider{position:absolute;cursor:pointer;inset:0;background:var(--inv-surface-3);border-radius:24px;transition:.3s;border:1px solid var(--inv-border)}
.icc-toggle .slider:before{content:'';position:absolute;height:18px;width:18px;left:3px;bottom:2px;background:var(--inv-text-muted);border-radius:50%;transition:.3s}
.icc-toggle input:checked+.slider{background:rgba(0,184,148,0.3);border-color:var(--inv-accent)}
.icc-toggle input:checked+.slider:before{transform:translateX(19px);background:var(--inv-accent-light)}

/* Modal */
.icc-modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:10000;align-items:center;justify-content:center;backdrop-filter:blur(4px)}
.icc-modal-overlay.open{display:flex}
.icc-modal{background:var(--inv-surface);border:1px solid var(--inv-border);border-radius:var(--inv-radius);padding:0;max-width:580px;width:94%;max-height:85vh;overflow:hidden;display:flex;flex-direction:column;box-shadow:var(--inv-shadow)}
.icc-modal-header{padding:20px 24px;border-bottom:1px solid var(--inv-border);display:flex;justify-content:space-between;align-items:center}
.icc-modal-header h3{font-family:'Space Grotesk',sans-serif;color:#fff;margin:0;font-size:1.1rem}
.icc-modal-header .close-btn{background:none;border:none;color:var(--inv-text-muted);font-size:1.4rem;cursor:pointer;padding:4px;line-height:1;transition:color .2s}
.icc-modal-header .close-btn:hover{color:#fff}
.icc-modal-body{padding:24px;overflow-y:auto;flex:1}
.icc-modal-footer{padding:16px 24px;border-top:1px solid var(--inv-border);display:flex;gap:10px;justify-content:flex-end}
.form-group{margin-bottom:16px}
.form-group label{display:block;font-size:.78rem;color:var(--inv-text-muted);margin-bottom:5px;font-weight:600;text-transform:uppercase;letter-spacing:.3px}
.form-group input,.form-group select,.form-group textarea{width:100%;padding:10px 14px;border-radius:var(--inv-radius-sm);border:1px solid var(--inv-border);background:var(--inv-surface-2);color:var(--inv-text);font-size:.9rem;font-family:inherit;box-sizing:border-box;transition:border-color .2s}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{outline:none;border-color:var(--inv-border-focus)}
.form-group textarea{min-height:80px;resize:vertical}
.form-group .readonly{opacity:0.6;cursor:not-allowed}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.form-group .field-hint{font-size:.72rem;color:var(--inv-text-dim);margin-top:4px}

/* Toast */
.icc-toast{position:fixed;bottom:24px;right:24px;padding:14px 24px;border-radius:var(--inv-radius-sm);font-size:.88rem;font-weight:600;z-index:10002;opacity:0;transform:translateY(20px);transition:all .3s ease;pointer-events:none;display:flex;align-items:center;gap:10px;box-shadow:var(--inv-shadow)}
.icc-toast.show{opacity:1;transform:translateY(0)}
.icc-toast.success{background:#00b894;color:#fff}
.icc-toast.error{background:#ff6b6b;color:#fff}
.icc-toast.info{background:#0984e3;color:#fff}

/* Expand details */
.expand-details{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px}
.expand-detail{background:var(--inv-surface);border-radius:var(--inv-radius-xs);padding:10px 14px}
.expand-detail .ed-label{font-size:.68rem;color:var(--inv-text-muted);text-transform:uppercase;letter-spacing:.3px}
.expand-detail .ed-value{font-size:.86rem;color:var(--inv-text);margin-top:2px;word-break:break-all}

/* Responsive */
@media(max-width:1200px){.icc-kanban{grid-template-columns:repeat(3,1fr)}.icc-chart-container{grid-template-columns:1fr}}
@media(max-width:900px){.icc-kpis{grid-template-columns:repeat(2,1fr)}.icc-kanban{grid-template-columns:repeat(2,1fr)}}
@media(max-width:768px){.icc-kpis{grid-template-columns:1fr 1fr}.icc-table{font-size:.78rem}.icc-table th,.icc-table td{padding:8px 6px}.icc-tabs{overflow-x:auto}.form-row{grid-template-columns:1fr}}
@media(max-width:480px){.icc-kpis{grid-template-columns:1fr}.icc-kanban{grid-template-columns:1fr}}

/* Loading / Skeleton */
.icc-loading{text-align:center;padding:60px 20px}
.icc-loading i{font-size:2.5rem;background:var(--inv-gradient);-webkit-background-clip:text;-webkit-text-fill-color:transparent;animation:spin 1s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
.icc-loading p{color:var(--inv-text-muted);margin-top:16px;font-size:.94rem}

/* Scrollbar */
.icc ::-webkit-scrollbar{width:6px;height:6px}
.icc ::-webkit-scrollbar-track{background:transparent}
.icc ::-webkit-scrollbar-thumb{background:var(--inv-surface-3);border-radius:3px}
.icc ::-webkit-scrollbar-thumb:hover{background:var(--inv-text-dim)}
</style>

<!-- ═══════════════════════════════════════════════════════════════════
     INVESTOR COMMAND CENTER — HTML
     ═══════════════════════════════════════════════════════════════════ -->
<div class="icc" id="iccApp">

  <!-- Header -->
  <div class="icc-header">
    <div>
      <h1><i class="fas fa-shield-halved"></i> Investor Command Center</h1>
      <p class="icc-sub">Enterprise admin dashboard &mdash; manage investors, pipeline, analytics &amp; portfolio</p>
    </div>
    <div class="icc-header-actions">
      <span class="icc-badge admin"><i class="fas fa-crown"></i> Admin: <?php echo htmlspecialchars($clientEmail); ?></span>
      <button class="icc-btn green" onclick="refreshData()" title="Refresh"><i class="fas fa-sync-alt"></i> Refresh</button>
    </div>
  </div>

  <!-- Loading State -->
  <div class="icc-loading" id="loadingState">
    <i class="fas fa-spinner fa-spin"></i>
    <p>Loading investor intelligence data&hellip;</p>
  </div>

  <!-- Main Content (hidden until loaded) -->
  <div id="mainContent" style="display:none;">

    <!-- Tab Navigation -->
    <nav class="icc-tabs" id="tabNav">
      <button class="icc-tab active" data-tab="overview"><i class="fas fa-chart-pie"></i> Overview</button>
      <button class="icc-tab" data-tab="investors"><i class="fas fa-users"></i> Investors <span class="tab-count" id="tabCountInvestors">0</span></button>
      <button class="icc-tab" data-tab="pipeline"><i class="fas fa-filter"></i> Pipeline</button>
      <button class="icc-tab" data-tab="analytics"><i class="fas fa-chart-line"></i> Analytics</button>
      <button class="icc-tab" data-tab="settings"><i class="fas fa-cog"></i> Settings</button>
    </nav>

    <!-- ═══════════ TAB: OVERVIEW ═══════════ -->
    <div class="icc-tab-panel active" id="panel-overview">

      <!-- KPI Row -->
      <div class="icc-kpis" id="kpiGrid"></div>

      <div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;">
        <div>
          <!-- Pipeline Funnel -->
          <div class="icc-card">
            <h2><i class="fas fa-filter"></i> Investor Pipeline Funnel</h2>
            <div class="icc-funnel" id="funnelVis"></div>
          </div>

          <!-- Revenue Chart Placeholder -->
          <div class="icc-card">
            <h2><i class="fas fa-chart-area"></i> Platform Metrics Overview
              <span class="card-actions"><button class="icc-btn icc-btn-sm ghost" onclick="switchTab('analytics')">View Full Analytics <i class="fas fa-arrow-right"></i></button></span>
            </h2>
            <div id="overviewMetrics" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:10px;"></div>
          </div>
        </div>

        <div>
          <!-- Quick Actions -->
          <div class="icc-card">
            <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
            <div class="icc-actions-grid" style="grid-template-columns:1fr;">
              <div class="icc-action-card" onclick="exportAllCSV()">
                <i class="fas fa-file-csv" style="color:var(--inv-accent-light)"></i>
                <span>Export CSV</span>
                <small>Download all investor data</small>
              </div>
              <a class="icc-action-card" href="/invest" target="_blank">
                <i class="fas fa-external-link-alt" style="color:var(--inv-blue)"></i>
                <span>Public Invest Page</span>
                <small>invest.php &mdash; live page</small>
              </a>
              <a class="icc-action-card" href="/investor-dashboard" target="_blank">
                <i class="fas fa-tachometer-alt" style="color:var(--inv-purple)"></i>
                <span>Investor Dashboard</span>
                <small>What investors see</small>
              </a>
              <div class="icc-action-card" onclick="emailAllInvestors()">
                <i class="fas fa-envelope" style="color:var(--inv-gold)"></i>
                <span>Email All Investors</span>
                <small>Compose bulk email</small>
              </div>
            </div>
          </div>

          <!-- Recent Activity -->
          <div class="icc-card">
            <h2><i class="fas fa-clock"></i> Recent Activity</h2>
            <ul class="icc-activity" id="activityFeed">
              <li><span class="act-icon blue"><i class="fas fa-spinner"></i></span><span class="act-text" style="color:var(--inv-text-muted)">Loading...</span></li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══════════ TAB: INVESTORS ═══════════ -->
    <div class="icc-tab-panel" id="panel-investors">
      <div class="icc-card">
        <h2><i class="fas fa-users-gear"></i> Investor Management
          <span class="card-actions">
            <button class="icc-btn icc-btn-sm green" onclick="exportFilteredCSV()"><i class="fas fa-download"></i> Export CSV</button>
          </span>
        </h2>

        <!-- Toolbar -->
        <div class="icc-toolbar">
          <div class="icc-search-wrap">
            <i class="fas fa-search"></i>
            <input type="text" class="icc-search" id="investorSearch" placeholder="Search by name, email, ref code, tier..." oninput="handleSearch()">
          </div>
          <div class="icc-bulk-actions" id="bulkBar" style="display:none;">
            <span class="icc-bulk-count" id="bulkCount">0 selected</span>
            <select id="bulkAction">
              <option value="">Bulk Actions...</option>
              <option value="contacted">Mark Contacted</option>
              <option value="approved">Mark Approved</option>
              <option value="funded">Mark Funded</option>
              <option value="declined">Mark Declined</option>
            </select>
            <button class="icc-btn icc-btn-sm blue" onclick="applyBulkAction()"><i class="fas fa-check"></i> Apply</button>
          </div>
        </div>

        <!-- Status Filters -->
        <div class="icc-filters" id="statusFilters">
          <button class="icc-filter active" data-filter="all">All</button>
          <button class="icc-filter" data-filter="pending"><i class="fas fa-circle" style="font-size:.4rem;color:#a29bfe"></i> Pending</button>
          <button class="icc-filter" data-filter="contacted"><i class="fas fa-circle" style="font-size:.4rem;color:#74b9ff"></i> Contacted</button>
          <button class="icc-filter" data-filter="approved"><i class="fas fa-circle" style="font-size:.4rem;color:#fdcb6e"></i> Approved</button>
          <button class="icc-filter" data-filter="funded"><i class="fas fa-circle" style="font-size:.4rem;color:#55efc4"></i> Funded</button>
          <button class="icc-filter" data-filter="declined"><i class="fas fa-circle" style="font-size:.4rem;color:#ff6b6b"></i> Declined</button>
        </div>

        <!-- Table -->
        <div class="icc-table-wrap" style="max-height:600px;overflow-y:auto;">
          <table class="icc-table" id="investorTable">
            <thead>
              <tr>
                <th class="checkbox-col"><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                <th data-sort="ref_code">Ref <i class="fas fa-sort sort-icon"></i></th>
                <th data-sort="name">Name <i class="fas fa-sort sort-icon"></i></th>
                <th data-sort="email">Email <i class="fas fa-sort sort-icon"></i></th>
                <th data-sort="phone">Phone <i class="fas fa-sort sort-icon"></i></th>
                <th data-sort="tier">Tier <i class="fas fa-sort sort-icon"></i></th>
                <th data-sort="amount">Amount <i class="fas fa-sort sort-icon"></i></th>
                <th data-sort="status">Status <i class="fas fa-sort sort-icon"></i></th>
                <th data-sort="created_at">Date <i class="fas fa-sort sort-icon"></i></th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="investorRows"></tbody>
          </table>
        </div>
        <p id="noResults" style="display:none;text-align:center;color:var(--inv-text-muted);padding:24px;">No investors match your search or filter.</p>

        <!-- Pagination -->
        <div class="icc-pagination" id="pagination"></div>
      </div>
    </div>

    <!-- ═══════════ TAB: PIPELINE ═══════════ -->
    <div class="icc-tab-panel" id="panel-pipeline">
      <div class="icc-card" style="background:transparent;border:none;padding:0;">
        <h2 style="margin-bottom:20px;"><i class="fas fa-columns"></i> Pipeline Board
          <span class="card-actions">
            <span style="font-size:.78rem;color:var(--inv-text-muted);font-weight:400;">Click any card to view details</span>
          </span>
        </h2>
        <div class="icc-kanban" id="kanbanBoard"></div>
      </div>
    </div>

    <!-- ═══════════ TAB: ANALYTICS ═══════════ -->
    <div class="icc-tab-panel" id="panel-analytics">

      <!-- Investment Distribution -->
      <div class="icc-chart-container">
        <div class="icc-card">
          <h2><i class="fas fa-money-bill-wave"></i> Investment by Tier</h2>
          <div class="icc-bar-chart" id="tierChart"></div>
        </div>
        <div class="icc-card">
          <h2><i class="fas fa-calendar-alt"></i> Monthly Submissions</h2>
          <div class="icc-bar-chart" id="monthlyChart"></div>
        </div>
      </div>

      <!-- Platform KPIs -->
      <div class="icc-card">
        <h2><i class="fas fa-server"></i> Platform Health Metrics</h2>
        <div class="icc-bar-chart" id="platformChart" style="height:180px;"></div>
      </div>

      <!-- Financial Projections -->
      <div class="icc-card">
        <h2><i class="fas fa-chart-line"></i> Financial Growth Projections</h2>
        <div id="projectionsGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px;"></div>
      </div>
    </div>

    <!-- ═══════════ TAB: SETTINGS ═══════════ -->
    <div class="icc-tab-panel" id="panel-settings">
      <div class="icc-card" style="max-width:700px;">
        <h2><i class="fas fa-cog"></i> Admin Settings</h2>

        <div class="icc-setting-group">
          <h3><i class="fas fa-envelope"></i> Admin Configuration</h3>
          <div class="icc-setting-row">
            <div>
              <label>Admin Email</label>
              <small><?php echo htmlspecialchars($clientEmail); ?></small>
            </div>
            <span class="icc-badge admin" style="font-size:.7rem;">Active</span>
          </div>
          <div class="icc-setting-row">
            <div>
              <label>Dashboard URL</label>
              <small>https://gositeme.com/investor-admin</small>
            </div>
            <button class="icc-btn icc-btn-sm ghost" onclick="navigator.clipboard.writeText('https://gositeme.com/investor-admin');toast('Copied!','success')"><i class="fas fa-copy"></i></button>
          </div>
        </div>

        <div class="icc-setting-group">
          <h3><i class="fas fa-bell"></i> Notification Preferences</h3>
          <div class="icc-setting-row">
            <div><label>New Submission Alerts</label><small>Email notification when a new investor submits interest</small></div>
            <label class="icc-toggle"><input type="checkbox" checked><span class="slider"></span></label>
          </div>
          <div class="icc-setting-row">
            <div><label>Status Change Notifications</label><small>Email investors when their status changes</small></div>
            <label class="icc-toggle"><input type="checkbox" checked><span class="slider"></span></label>
          </div>
          <div class="icc-setting-row">
            <div><label>Weekly Summary Report</label><small>Receive a weekly digest of investor activity</small></div>
            <label class="icc-toggle"><input type="checkbox"><span class="slider"></span></label>
          </div>
          <div class="icc-setting-row">
            <div><label>Funding Milestone Alerts</label><small>Get notified at funding milestones ($10k, $50k, $100k)</small></div>
            <label class="icc-toggle"><input type="checkbox" checked><span class="slider"></span></label>
          </div>
        </div>

        <div class="icc-setting-group">
          <h3><i class="fas fa-database"></i> Data Management</h3>
          <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:12px;">
            <button class="icc-btn green" onclick="exportAllCSV()"><i class="fas fa-file-csv"></i> Export All Data (CSV)</button>
            <button class="icc-btn blue" onclick="exportJSON()"><i class="fas fa-code"></i> Export JSON</button>
            <button class="icc-btn purple" onclick="refreshData()"><i class="fas fa-sync-alt"></i> Force Refresh Cache</button>
          </div>
        </div>

        <div class="icc-setting-group">
          <h3><i class="fas fa-info-circle"></i> System Info</h3>
          <div class="icc-setting-row">
            <div><label>API Endpoint</label><small>/api/investor.php</small></div>
            <span style="font-size:.78rem;color:var(--inv-accent-light)"><i class="fas fa-circle" style="font-size:.4rem"></i> Online</span>
          </div>
          <div class="icc-setting-row">
            <div><label>Data Last Refreshed</label><small id="lastRefreshed">—</small></div>
          </div>
          <div class="icc-setting-row">
            <div><label>Version</label><small>Investor Command Center v2.0</small></div>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /mainContent -->
</div><!-- /icc -->

<!-- ═══════════════════════════════════════════════════════════════════
     EDIT MODAL
     ═══════════════════════════════════════════════════════════════════ -->
<div class="icc-modal-overlay" id="editModal">
  <div class="icc-modal">
    <div class="icc-modal-header">
      <h3 id="modalTitle"><i class="fas fa-user-edit"></i> Investor Details</h3>
      <button class="close-btn" onclick="closeModal()">&times;</button>
    </div>
    <div class="icc-modal-body">
      <form id="editForm" onsubmit="return saveInvestor(event)">
        <input type="hidden" id="editId">

        <div class="form-row">
          <div class="form-group">
            <label>Reference Code</label>
            <input id="editRef" class="readonly" readonly>
          </div>
          <div class="form-group">
            <label>Date Submitted</label>
            <input id="editDate" class="readonly" readonly>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Full Name</label>
            <input id="editName" class="readonly" readonly>
          </div>
          <div class="form-group">
            <label>Email Address</label>
            <input id="editEmail" class="readonly" readonly>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Phone</label>
            <input id="editPhone" class="readonly" readonly>
          </div>
          <div class="form-group">
            <label>IP Address</label>
            <input id="editIP" class="readonly" readonly>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Investment Tier</label>
            <input id="editTier" class="readonly" readonly>
          </div>
          <div class="form-group">
            <label>Investment Amount</label>
            <input id="editAmount" class="readonly" readonly>
          </div>
        </div>

        <div class="form-group">
          <label>Investor Message</label>
          <textarea id="editMessage" class="readonly" readonly></textarea>
        </div>

        <div class="form-group">
          <label>Status</label>
          <select id="editStatus">
            <option value="pending">Pending</option>
            <option value="contacted">Contacted</option>
            <option value="approved">Approved</option>
            <option value="funded">Funded</option>
            <option value="declined">Declined</option>
          </select>
          <div class="field-hint" id="statusWarning" style="display:none;color:var(--inv-gold);">
            <i class="fas fa-exclamation-triangle"></i> Changing to this status will notify the investor via email.
          </div>
        </div>

        <div class="form-group">
          <label>Admin Notes <small style="text-transform:none;letter-spacing:0;font-weight:400">(internal only)</small></label>
          <textarea id="editNotes" placeholder="Add internal notes about this investor..." rows="3"></textarea>
        </div>

        <div id="noteHistory" style="display:none;margin-top:8px;padding:12px;background:var(--inv-surface-2);border-radius:var(--inv-radius-xs);font-size:.78rem;color:var(--inv-text-muted);max-height:100px;overflow-y:auto;"></div>
      </form>
    </div>
    <div class="icc-modal-footer">
      <button class="icc-btn ghost" onclick="sendCustomEmail()" title="Send a custom email to this investor"><i class="fas fa-envelope"></i> Email Investor</button>
      <button class="icc-btn ghost" onclick="closeModal()">Cancel</button>
      <button class="icc-btn primary" onclick="saveInvestor(event)"><i class="fas fa-save"></i> Save Changes</button>
    </div>
  </div>
</div>

<!-- Toast -->
<div class="icc-toast" id="toast"></div>

<!-- ═══════════════════════════════════════════════════════════════════
     JAVASCRIPT — DATA, TABS, SEARCH, SORT, PAGINATION, EXPORT
     ═══════════════════════════════════════════════════════════════════ -->

<script src="/assets/js/investor-admin-engine.js"></script>


<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
