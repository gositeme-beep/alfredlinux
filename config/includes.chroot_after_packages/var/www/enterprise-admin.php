<?php
/**
 * Enterprise Admin Dashboard — Alfred AI
 * Agent 4 — Project Phoenix (Master Plan 3)
 *
 * Full admin dashboard for managing organizations, teams, members,
 * roles, audit logs, usage, API keys, SSO, and white-label settings.
 */
require_once __DIR__ . '/includes/auth-gate.inc.php';
// $clientId, $clientName, $clientEmail, $initials guaranteed by auth-gate
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Enterprise Admin — Alfred AI</title>
<meta name="description" content="Enterprise administration dashboard for Alfred AI. Manage your organization, teams, members, and monitor usage.">
<link rel="canonical" href="https://root.com/enterprise-admin.php">
<meta name="theme-color" content="#0a0a14">
<link rel="icon" type="image/png" href="/brand/favicon.png" sizes="32x32">

<link rel="stylesheet" href="/assets/css/fonts.css">
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">

<style>
/* ═══════════════════════════════════════════════
   RESET & VARIABLES
   ═══════════════════════════════════════════════ */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --al-bg:#0a0a14;--al-surface:#12121e;--al-surface-2:#1a1a2e;--al-surface-3:#22223a;
  --al-accent:#6c5ce7;--al-accent-light:#a29bfe;--al-accent-glow:rgba(108,92,231,.3);
  --al-blue:#0984e3;--al-green:#00b894;--al-orange:#e17055;--al-red:#d63031;
  --al-cyan:#00cec9;--al-yellow:#fdcb6e;--al-purple:#6c5ce7;
  --al-text:#e8e8f0;--al-text-secondary:#9898b0;--al-text-muted:#68688a;
  --al-border:rgba(255,255,255,.07);--al-border-light:rgba(255,255,255,.12);
  --al-radius:12px;--al-radius-sm:8px;--al-radius-lg:16px;
  --sidebar-w:260px;
  --font-body:'Inter',system-ui,sans-serif;
  --font-heading:'Space Grotesk','Inter',sans-serif;
  --font-mono:'JetBrains Mono','Fira Code',monospace;
}
html{scroll-behavior:smooth}
body{font-family:var(--font-body);background:var(--al-bg);color:var(--al-text);line-height:1.6;min-height:100vh;overflow-x:hidden}
a{color:var(--al-accent-light);text-decoration:none}
a:hover{color:#fff}

::-webkit-scrollbar{width:5px;height:5px}
::-webkit-scrollbar-track{background:var(--al-surface)}
::-webkit-scrollbar-thumb{background:var(--al-accent);border-radius:3px}

/* ═══════════════════════════════════════════════
   LAYOUT — SIDEBAR + MAIN
   ═══════════════════════════════════════════════ */
.ea-layout{display:flex;min-height:100vh}

/* ─── SIDEBAR ─── */
.ea-sidebar{width:var(--sidebar-w);background:var(--al-surface);border-right:1px solid var(--al-border);position:fixed;top:0;left:0;height:100vh;display:flex;flex-direction:column;z-index:200;transition:transform .3s ease}
.ea-sidebar-header{padding:20px 20px 16px;border-bottom:1px solid var(--al-border);display:flex;align-items:center;gap:12px}
.ea-sidebar-header img{height:32px}
.ea-sidebar-header .ea-brand{font-family:var(--font-heading);font-weight:700;font-size:1rem}
.ea-sidebar-header .ea-brand span{color:var(--al-accent-light);font-size:.7rem;font-weight:500;display:block;margin-top:-2px}

.ea-sidebar-nav{flex:1;overflow-y:auto;padding:12px 10px}
.ea-sidebar-nav ul{list-style:none}
.ea-sidebar-nav li{margin-bottom:2px}
.ea-nav-item{display:flex;align-items:center;gap:12px;padding:10px 14px;color:var(--al-text-secondary);border-radius:var(--al-radius-sm);cursor:pointer;font-size:.875rem;font-weight:500;transition:all .2s;border:none;background:none;width:100%;text-align:left;font-family:var(--font-body)}
.ea-nav-item:hover{background:rgba(108,92,231,.08);color:var(--al-text)}
.ea-nav-item.active{background:rgba(108,92,231,.15);color:var(--al-accent-light);font-weight:600}
.ea-nav-item i{width:20px;text-align:center;font-size:.9rem}

.ea-nav-section{padding:16px 14px 6px;font-size:.65rem;text-transform:uppercase;letter-spacing:.08em;color:var(--al-text-muted);font-weight:700}

.ea-sidebar-footer{padding:14px 16px;border-top:1px solid var(--al-border);display:flex;align-items:center;gap:10px}
.ea-user-avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--al-accent),var(--al-blue));display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;flex-shrink:0;color:#fff}
.ea-user-info{min-width:0;flex:1}
.ea-user-name{font-weight:600;font-size:.8rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ea-user-email{font-size:.7rem;color:var(--al-text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

/* ─── MOBILE TOGGLE ─── */
.ea-mobile-toggle{display:none;position:fixed;top:12px;left:12px;z-index:300;width:42px;height:42px;border-radius:var(--al-radius-sm);background:var(--al-surface-2);border:1px solid var(--al-border);color:var(--al-text);font-size:1.1rem;cursor:pointer;align-items:center;justify-content:center}
.ea-mobile-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:190}

/* ─── MAIN ─── */
.ea-main{flex:1;margin-left:var(--sidebar-w);min-height:100vh}
.ea-topbar{position:sticky;top:0;z-index:100;background:rgba(10,10,20,.92);backdrop-filter:blur(16px);border-bottom:1px solid var(--al-border);padding:0 28px;height:58px;display:flex;align-items:center;justify-content:space-between}
.ea-topbar-title{font-family:var(--font-heading);font-size:1.15rem;font-weight:700}
.ea-topbar-actions{display:flex;align-items:center;gap:12px}
.ea-topbar-btn{background:var(--al-surface-2);border:1px solid var(--al-border);color:var(--al-text-secondary);padding:7px 12px;border-radius:var(--al-radius-sm);font-size:.8rem;cursor:pointer;transition:all .2s;font-family:var(--font-body)}
.ea-topbar-btn:hover{background:var(--al-surface-3);color:var(--al-text)}
.ea-topbar-btn i{margin-right:5px}

.ea-content{padding:24px 28px 40px}

/* ═══════════════════════════════════════════════
   SECTION PANELS (show/hide SPA-like)
   ═══════════════════════════════════════════════ */
.ea-section{display:none}
.ea-section.active{display:block}

/* ═══════════════════════════════════════════════
   STATS CARDS
   ═══════════════════════════════════════════════ */
.ea-stats{display:grid;grid-template-columns:repeat(5,1fr);gap:16px;margin-bottom:24px}
.ea-stat{background:var(--al-surface-2);border:1px solid var(--al-border);border-radius:var(--al-radius-lg);padding:20px;position:relative;overflow:hidden;transition:border-color .25s}
.ea-stat:hover{border-color:rgba(108,92,231,.25)}
.ea-stat::before{content:'';position:absolute;top:0;left:0;right:0;height:3px}
.ea-stat:nth-child(1)::before{background:linear-gradient(90deg,var(--al-accent),transparent)}
.ea-stat:nth-child(2)::before{background:linear-gradient(90deg,var(--al-blue),transparent)}
.ea-stat:nth-child(3)::before{background:linear-gradient(90deg,var(--al-green),transparent)}
.ea-stat:nth-child(4)::before{background:linear-gradient(90deg,var(--al-orange),transparent)}
.ea-stat:nth-child(5)::before{background:linear-gradient(90deg,var(--al-cyan),transparent)}
.ea-stat-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;margin-bottom:12px}
.ea-stat:nth-child(1) .ea-stat-icon{background:rgba(108,92,231,.15);color:var(--al-accent-light)}
.ea-stat:nth-child(2) .ea-stat-icon{background:rgba(9,132,227,.15);color:var(--al-blue)}
.ea-stat:nth-child(3) .ea-stat-icon{background:rgba(0,184,148,.15);color:var(--al-green)}
.ea-stat:nth-child(4) .ea-stat-icon{background:rgba(225,112,85,.15);color:var(--al-orange)}
.ea-stat:nth-child(5) .ea-stat-icon{background:rgba(0,206,201,.15);color:var(--al-cyan)}
.ea-stat-value{font-family:var(--font-heading);font-size:1.6rem;font-weight:700;line-height:1.1}
.ea-stat-label{font-size:.75rem;color:var(--al-text-muted);margin-top:4px;text-transform:uppercase;letter-spacing:.04em}

/* ═══════════════════════════════════════════════
   CARDS / PANELS
   ═══════════════════════════════════════════════ */
.ea-card{background:var(--al-surface-2);border:1px solid var(--al-border);border-radius:var(--al-radius-lg);margin-bottom:20px;overflow:hidden}
.ea-card-header{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--al-border)}
.ea-card-header h2{font-family:var(--font-heading);font-size:1.05rem;font-weight:700;display:flex;align-items:center;gap:8px}
.ea-card-header h2 i{color:var(--al-accent-light);font-size:.9rem}
.ea-card-body{padding:20px}

.ea-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.ea-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px}

/* ═══════════════════════════════════════════════
   TABLES
   ═══════════════════════════════════════════════ */
.ea-table-wrap{overflow-x:auto}
.ea-table{width:100%;border-collapse:collapse;font-size:.85rem}
.ea-table thead th{background:rgba(108,92,231,.08);padding:10px 14px;text-align:left;font-weight:600;font-size:.75rem;text-transform:uppercase;letter-spacing:.04em;color:var(--al-accent-light);white-space:nowrap}
.ea-table thead th:first-child{border-radius:var(--al-radius-sm) 0 0 var(--al-radius-sm)}
.ea-table thead th:last-child{border-radius:0 var(--al-radius-sm) var(--al-radius-sm) 0}
.ea-table tbody td{padding:12px 14px;border-bottom:1px solid var(--al-border);vertical-align:middle}
.ea-table tbody tr:last-child td{border-bottom:none}
.ea-table tbody tr:hover{background:rgba(108,92,231,.04)}

/* ═══════════════════════════════════════════════
   BADGES
   ═══════════════════════════════════════════════ */
.ea-badge{display:inline-flex;align-items:center;padding:3px 10px;border-radius:50px;font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.03em}
.ea-badge-owner{background:rgba(108,92,231,.15);color:var(--al-accent-light)}
.ea-badge-admin{background:rgba(9,132,227,.15);color:var(--al-blue)}
.ea-badge-manager{background:rgba(0,184,148,.15);color:var(--al-green)}
.ea-badge-member{background:rgba(255,255,255,.08);color:var(--al-text-secondary)}
.ea-badge-viewer{background:rgba(255,255,255,.05);color:var(--al-text-muted)}
.ea-badge-active{background:rgba(0,184,148,.12);color:var(--al-green)}
.ea-badge-pending{background:rgba(253,203,110,.12);color:var(--al-yellow)}
.ea-badge-auth{background:rgba(9,132,227,.12);color:var(--al-blue)}
.ea-badge-admin-action{background:rgba(108,92,231,.12);color:var(--al-accent-light)}
.ea-badge-billing{background:rgba(0,184,148,.12);color:var(--al-green)}
.ea-badge-security{background:rgba(214,48,49,.12);color:var(--al-red)}
.ea-badge-team{background:rgba(225,112,85,.12);color:var(--al-orange)}

/* ═══════════════════════════════════════════════
   BUTTONS
   ═══════════════════════════════════════════════ */
.ea-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border:none;border-radius:var(--al-radius-sm);font-size:.82rem;font-weight:600;font-family:var(--font-body);cursor:pointer;transition:all .2s;white-space:nowrap}
.ea-btn-primary{background:linear-gradient(135deg,var(--al-accent),#7c6cf7);color:#fff;box-shadow:0 4px 15px var(--al-accent-glow)}
.ea-btn-primary:hover{transform:translateY(-1px);box-shadow:0 6px 25px rgba(108,92,231,.4);color:#fff}
.ea-btn-secondary{background:var(--al-surface-3);border:1px solid var(--al-border);color:var(--al-text-secondary)}
.ea-btn-secondary:hover{background:var(--al-surface-2);color:var(--al-text);border-color:var(--al-border-light)}
.ea-btn-danger{background:rgba(214,48,49,.15);color:var(--al-red);border:1px solid rgba(214,48,49,.2)}
.ea-btn-danger:hover{background:rgba(214,48,49,.25)}
.ea-btn-sm{padding:5px 10px;font-size:.75rem}
.ea-btn-icon{padding:6px 8px;font-size:.8rem}
.ea-btn:disabled{opacity:.5;cursor:not-allowed;transform:none!important}

/* ═══════════════════════════════════════════════
   FORMS
   ═══════════════════════════════════════════════ */
.ea-form-group{margin-bottom:14px}
.ea-form-group label{display:block;font-size:.75rem;font-weight:600;color:var(--al-text-secondary);margin-bottom:5px;text-transform:uppercase;letter-spacing:.03em}
.ea-input,.ea-select,.ea-textarea{width:100%;padding:9px 12px;background:var(--al-surface);border:1px solid var(--al-border);border-radius:var(--al-radius-sm);color:var(--al-text);font-size:.85rem;font-family:var(--font-body);transition:border-color .2s,box-shadow .2s;outline:none}
.ea-input:focus,.ea-select:focus,.ea-textarea:focus{border-color:var(--al-accent);box-shadow:0 0 0 3px var(--al-accent-glow)}
.ea-select{cursor:pointer;appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%239898b0'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 8px center;background-size:1.1rem;padding-right:32px}
.ea-textarea{resize:vertical;min-height:70px}

/* ═══════════════════════════════════════════════
   FILTER BAR
   ═══════════════════════════════════════════════ */
.ea-filters{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:16px}
.ea-filters .ea-input,.ea-filters .ea-select{width:auto;min-width:160px;padding:7px 12px;font-size:.8rem}
.ea-search-box{position:relative}
.ea-search-box input{padding-left:34px}
.ea-search-box i{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--al-text-muted);font-size:.8rem}

/* ═══════════════════════════════════════════════
   MODALS
   ═══════════════════════════════════════════════ */
.ea-modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:500;display:none;align-items:center;justify-content:center;opacity:0;transition:opacity .25s}
.ea-modal-overlay.show{display:flex;opacity:1}
.ea-modal{background:var(--al-surface-2);border:1px solid var(--al-border-light);border-radius:var(--al-radius-lg);width:90%;max-width:480px;max-height:90vh;overflow-y:auto;transform:translateY(20px);transition:transform .25s}
.ea-modal-overlay.show .ea-modal{transform:translateY(0)}
.ea-modal-header{display:flex;align-items:center;justify-content:space-between;padding:18px 20px;border-bottom:1px solid var(--al-border)}
.ea-modal-header h3{font-family:var(--font-heading);font-size:1.05rem;font-weight:700}
.ea-modal-close{background:none;border:none;color:var(--al-text-muted);font-size:1.1rem;cursor:pointer;padding:4px;transition:color .2s}
.ea-modal-close:hover{color:var(--al-text)}
.ea-modal-body{padding:20px}
.ea-modal-footer{padding:14px 20px;border-top:1px solid var(--al-border);display:flex;justify-content:flex-end;gap:10px}

/* ═══════════════════════════════════════════════
   TOAST NOTIFICATIONS
   ═══════════════════════════════════════════════ */
.ea-toast-container{position:fixed;top:16px;right:16px;z-index:9999;display:flex;flex-direction:column;gap:8px;pointer-events:none}
.ea-toast{pointer-events:auto;display:flex;align-items:center;gap:10px;padding:12px 18px;border-radius:var(--al-radius-sm);font-size:.85rem;font-weight:500;min-width:280px;max-width:420px;box-shadow:0 8px 30px rgba(0,0,0,.4);transform:translateX(120%);transition:transform .35s ease;border:1px solid var(--al-border)}
.ea-toast.show{transform:translateX(0)}
.ea-toast-success{background:var(--al-surface-2);border-left:4px solid var(--al-green);color:var(--al-green)}
.ea-toast-error{background:var(--al-surface-2);border-left:4px solid var(--al-red);color:var(--al-red)}
.ea-toast-info{background:var(--al-surface-2);border-left:4px solid var(--al-blue);color:var(--al-blue)}

/* ═══════════════════════════════════════════════
   LOADING / SKELETON
   ═══════════════════════════════════════════════ */
.ea-skeleton{background:linear-gradient(90deg,var(--al-surface-2) 25%,var(--al-surface-3) 50%,var(--al-surface-2) 75%);background-size:200% 100%;animation:shimmer 1.5s infinite;border-radius:var(--al-radius-sm);height:18px;margin-bottom:8px}
@keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}
.ea-skeleton-stat{height:42px;width:80px;margin-bottom:6px}
.ea-skeleton-row{height:46px;width:100%;margin-bottom:4px}
.ea-spinner{display:inline-block;width:20px;height:20px;border:2px solid var(--al-border);border-top-color:var(--al-accent);border-radius:50%;animation:spin .6s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
.ea-loading-state{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:60px 20px;color:var(--al-text-muted)}
.ea-loading-state .ea-spinner{width:32px;height:32px;border-width:3px;margin-bottom:14px}

.ea-error-state{text-align:center;padding:60px 20px;color:var(--al-text-muted)}
.ea-error-state i{font-size:2rem;margin-bottom:12px;color:var(--al-red)}
.ea-error-state p{margin-bottom:14px}

.ea-empty-state{text-align:center;padding:50px 20px;color:var(--al-text-muted)}
.ea-empty-state i{font-size:2.5rem;margin-bottom:12px;opacity:.4}

/* ═══════════════════════════════════════════════
   ACTIVITY FEED
   ═══════════════════════════════════════════════ */
.ea-activity{display:flex;flex-direction:column;gap:6px;max-height:380px;overflow-y:auto}
.ea-activity-item{display:flex;align-items:flex-start;gap:10px;padding:10px 12px;background:var(--al-surface);border-radius:var(--al-radius-sm);font-size:.82rem;border-left:3px solid var(--al-border)}
.ea-activity-item[data-type="auth"]{border-left-color:var(--al-blue)}
.ea-activity-item[data-type="admin"]{border-left-color:var(--al-accent)}
.ea-activity-item[data-type="billing"]{border-left-color:var(--al-green)}
.ea-activity-item[data-type="security"]{border-left-color:var(--al-red)}
.ea-activity-item[data-type="team"]{border-left-color:var(--al-orange)}
.ea-activity-icon{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.7rem;flex-shrink:0;background:rgba(108,92,231,.1);color:var(--al-accent-light)}
.ea-activity-body{flex:1;min-width:0}
.ea-activity-user{font-weight:600;font-size:.8rem}
.ea-activity-action{color:var(--al-text-secondary);font-size:.78rem}
.ea-activity-time{color:var(--al-text-muted);font-size:.7rem;flex-shrink:0;font-family:var(--font-mono)}

/* ═══════════════════════════════════════════════
   QUICK ACTIONS
   ═══════════════════════════════════════════════ */
.ea-quick-actions{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
.ea-quick-action{display:flex;flex-direction:column;align-items:center;gap:8px;padding:20px;background:var(--al-surface);border:1px solid var(--al-border);border-radius:var(--al-radius);cursor:pointer;transition:all .2s;text-align:center}
.ea-quick-action:hover{border-color:var(--al-accent);background:rgba(108,92,231,.06);transform:translateY(-2px)}
.ea-quick-action i{font-size:1.3rem;color:var(--al-accent-light)}
.ea-quick-action span{font-size:.8rem;font-weight:600;color:var(--al-text-secondary)}

/* ═══════════════════════════════════════════════
   USAGE BARS
   ═══════════════════════════════════════════════ */
.ea-usage-item{margin-bottom:18px}
.ea-usage-label{display:flex;justify-content:space-between;font-size:.82rem;margin-bottom:6px}
.ea-usage-label span:first-child{font-weight:600}
.ea-usage-label span:last-child{color:var(--al-text-muted);font-family:var(--font-mono);font-size:.78rem}
.ea-progress{width:100%;height:8px;background:var(--al-surface);border-radius:4px;overflow:hidden}
.ea-progress-bar{height:100%;border-radius:4px;transition:width .6s ease;background:linear-gradient(90deg,var(--al-accent),var(--al-blue))}
.ea-progress-bar.high{background:linear-gradient(90deg,var(--al-orange),var(--al-red))}
.ea-progress-bar.medium{background:linear-gradient(90deg,var(--al-yellow),var(--al-orange))}

/* ═══════════════════════════════════════════════
   USAGE CHART (CSS bar chart)
   ═══════════════════════════════════════════════ */
.ea-bar-chart{display:flex;align-items:flex-end;gap:4px;height:160px;padding-top:10px}
.ea-bar-col{flex:1;display:flex;flex-direction:column;align-items:center;gap:4px}
.ea-bar{width:100%;max-width:28px;margin:0 auto;border-radius:4px 4px 0 0;background:linear-gradient(180deg,var(--al-accent),var(--al-blue));transition:height .5s ease;min-height:4px}
.ea-bar-label{font-size:.6rem;color:var(--al-text-muted);writing-mode:horizontal-tb;text-align:center;font-family:var(--font-mono)}

/* ═══════════════════════════════════════════════
   TEAM CARDS
   ═══════════════════════════════════════════════ */
.ea-teams-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px}
.ea-team-card{background:var(--al-surface);border:1px solid var(--al-border);border-radius:var(--al-radius);padding:18px;cursor:pointer;transition:all .2s}
.ea-team-card:hover{border-color:rgba(108,92,231,.3);box-shadow:0 4px 20px rgba(0,0,0,.3)}
.ea-team-card-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px}
.ea-team-card-header h3{font-family:var(--font-heading);font-size:.95rem;font-weight:700}
.ea-team-meta{display:flex;gap:14px;font-size:.78rem;color:var(--al-text-muted)}
.ea-team-meta i{margin-right:4px}
.ea-team-members{margin-top:12px;padding-top:12px;border-top:1px solid var(--al-border);display:none}
.ea-team-card.expanded .ea-team-members{display:block}

/* ═══════════════════════════════════════════════
   PAGINATION
   ═══════════════════════════════════════════════ */
.ea-pagination{display:flex;align-items:center;justify-content:center;gap:6px;margin-top:16px}
.ea-page-btn{width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:var(--al-radius-sm);background:var(--al-surface);border:1px solid var(--al-border);color:var(--al-text-secondary);font-size:.8rem;cursor:pointer;transition:all .2s;font-family:var(--font-body)}
.ea-page-btn:hover{border-color:var(--al-accent);color:var(--al-text)}
.ea-page-btn.active{background:var(--al-accent);border-color:var(--al-accent);color:#fff}
.ea-page-btn:disabled{opacity:.4;cursor:not-allowed}
.ea-page-info{font-size:.78rem;color:var(--al-text-muted);margin:0 8px}

/* ═══════════════════════════════════════════════
   ROLES & PERMISSIONS TABLE
   ═══════════════════════════════════════════════ */
.ea-perm-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:8px;font-size:.8rem}
.ea-perm-item{display:flex;align-items:center;gap:6px;padding:8px 10px;background:var(--al-surface);border-radius:var(--al-radius-sm)}
.ea-perm-item i{color:var(--al-green);font-size:.7rem}

/* ═══════════════════════════════════════════════
   SETTINGS SECTIONS
   ═══════════════════════════════════════════════ */
.ea-settings-card{background:var(--al-surface);border:1px solid var(--al-border);border-radius:var(--al-radius);padding:20px;margin-bottom:16px}
.ea-settings-card h3{font-family:var(--font-heading);font-size:.95rem;font-weight:700;margin-bottom:4px}
.ea-settings-card p{font-size:.82rem;color:var(--al-text-muted);margin-bottom:14px}

/* ═══════════════════════════════════════════════
   RESPONSIVE
   ═══════════════════════════════════════════════ */
@media(max-width:1200px){
  .ea-stats{grid-template-columns:repeat(3,1fr)}
}
@media(max-width:1024px){
  .ea-grid-2,.ea-grid-3{grid-template-columns:1fr}
  .ea-stats{grid-template-columns:repeat(2,1fr)}
  .ea-quick-actions{grid-template-columns:repeat(2,1fr)}
}
@media(max-width:768px){
  .ea-sidebar{transform:translateX(-100%)}
  .ea-sidebar.open{transform:translateX(0)}
  .ea-mobile-toggle{display:flex}
  .ea-mobile-overlay.show{display:block}
  .ea-main{margin-left:0}
  .ea-topbar{padding:0 16px;padding-left:56px}
  .ea-content{padding:16px}
  .ea-stats{grid-template-columns:1fr 1fr}
  .ea-quick-actions{grid-template-columns:1fr}
  .ea-filters{flex-direction:column;align-items:stretch}
  .ea-filters .ea-input,.ea-filters .ea-select{width:100%;min-width:0}
  .ea-teams-grid{grid-template-columns:1fr}
  .ea-bar-chart{height:120px}
}
@media(max-width:480px){
  .ea-stats{grid-template-columns:1fr}
  .ea-topbar-actions .ea-topbar-btn span{display:none}
}
</style>
    <link rel="stylesheet" href="/assets/css/design-tokens.css?v=20260310">
    <link rel="stylesheet" href="/assets/css/components.css?v=20260310">
    <script src="/assets/js/gds-utils.js?v=20260310" defer></script>
    <script src="/assets/js/gds-toast.js?v=20260310" defer></script>
    <script src="/assets/js/gds-modal.js?v=20260310" defer></script>
</head>
<body>

<!-- Toast Container -->
<div class="ea-toast-container" id="toastContainer" aria-live="polite"></div>

<!-- Mobile Sidebar Toggle -->
<button class="ea-mobile-toggle" id="mobileToggle" aria-label="Toggle navigation">
  <i class="fas fa-bars"></i>
</button>
<div class="ea-mobile-overlay" id="mobileOverlay"></div>

<div class="ea-layout">
  <!-- ═══ SIDEBAR ═══ -->
  <aside class="ea-sidebar" id="sidebar" role="navigation" aria-label="Admin navigation">
    <div class="ea-sidebar-header">
      <img src="/brand/logo_w.png" alt="GoSiteMe" loading="lazy">
      <div class="ea-brand">Enterprise<span>Admin Dashboard</span></div>
    </div>

    <nav class="ea-sidebar-nav">
      <ul>
        <li><button class="ea-nav-item active" data-section="dashboard"><i class="fas fa-tachometer-alt"></i> Dashboard</button></li>
        <li><button class="ea-nav-item" data-section="members"><i class="fas fa-users"></i> Members</button></li>
        <li><button class="ea-nav-item" data-section="teams"><i class="fas fa-people-group"></i> Teams</button></li>
        <li><button class="ea-nav-item" data-section="roles"><i class="fas fa-shield-halved"></i> Roles &amp; Permissions</button></li>
        <li><button class="ea-nav-item" data-section="audit"><i class="fas fa-scroll"></i> Audit Log</button></li>

        <div class="ea-nav-section">Configuration</div>
        <li><button class="ea-nav-item" data-section="apikeys"><i class="fas fa-key"></i> API Keys</button></li>
        <li><button class="ea-nav-item" data-section="usage"><i class="fas fa-chart-bar"></i> Usage &amp; Billing</button></li>
        <li><button class="ea-nav-item" data-section="sso"><i class="fas fa-lock"></i> SSO Settings</button></li>
        <li><button class="ea-nav-item" data-section="whitelabel"><i class="fas fa-palette"></i> White Label</button></li>
        <li><button class="ea-nav-item" data-section="settings"><i class="fas fa-gear"></i> Organization Settings</button></li>
      </ul>
    </nav>

    <div class="ea-sidebar-footer">
      <div class="ea-user-avatar"><?php echo htmlspecialchars($initials); ?></div>
      <div class="ea-user-info">
        <div class="ea-user-name"><?php echo htmlspecialchars($clientName); ?></div>
        <div class="ea-user-email"><?php echo htmlspecialchars($clientEmail); ?></div>
      </div>
    </div>
  </aside>

  <!-- ═══ MAIN ═══ -->
  <div class="ea-main">
    <header class="ea-topbar">
      <h1 class="ea-topbar-title" id="topbarTitle">Dashboard</h1>
      <div class="ea-topbar-actions">
        <button class="ea-topbar-btn" onclick="openInviteModal()"><i class="fas fa-user-plus"></i> <span>Invite</span></button>
        <button class="ea-topbar-btn" onclick="window.location.href='/enterprise.php'"><i class="fas fa-building"></i> <span>Enterprise</span></button>
        <button class="ea-topbar-btn" onclick="window.location.href='/dashboard.php'"><i class="fas fa-arrow-left"></i> <span>Dashboard</span></button>
      </div>
    </header>

    <main class="ea-content" id="main">

      <!-- ═══════════════════════════════════════
           SECTION: DASHBOARD
           ═══════════════════════════════════════ -->
      <section class="ea-section active" id="sec-dashboard" aria-label="Dashboard overview">
        <!-- Stats -->
        <div class="ea-stats" id="dashStats">
          <div class="ea-stat"><div class="ea-stat-icon"><i class="fas fa-users"></i></div><div class="ea-stat-value ea-skeleton ea-skeleton-stat" id="statMembers">&nbsp;</div><div class="ea-stat-label">Total Members</div></div>
          <div class="ea-stat"><div class="ea-stat-icon"><i class="fas fa-people-group"></i></div><div class="ea-stat-value ea-skeleton ea-skeleton-stat" id="statTeams">&nbsp;</div><div class="ea-stat-label">Active Teams</div></div>
          <div class="ea-stat"><div class="ea-stat-icon"><i class="fas fa-code"></i></div><div class="ea-stat-value ea-skeleton ea-skeleton-stat" id="statAPICalls">&nbsp;</div><div class="ea-stat-label">API Calls (30d)</div></div>
          <div class="ea-stat"><div class="ea-stat-icon"><i class="fas fa-wrench"></i></div><div class="ea-stat-value ea-skeleton ea-skeleton-stat" id="statTools">&nbsp;</div><div class="ea-stat-label">Tool Executions (30d)</div></div>
          <div class="ea-stat"><div class="ea-stat-icon"><i class="fas fa-microphone"></i></div><div class="ea-stat-value ea-skeleton ea-skeleton-stat" id="statVoice">&nbsp;</div><div class="ea-stat-label">Voice Minutes</div></div>
        </div>

        <div class="ea-grid-2">
          <!-- Usage Chart -->
          <div class="ea-card">
            <div class="ea-card-header">
              <h2><i class="fas fa-chart-area"></i> Usage — Last 7 Days</h2>
            </div>
            <div class="ea-card-body">
              <div class="ea-bar-chart" id="dashChart">
                <div class="ea-loading-state" style="width:100%"><div class="ea-spinner"></div><span>Loading chart…</span></div>
              </div>
            </div>
          </div>

          <!-- Recent Activity -->
          <div class="ea-card">
            <div class="ea-card-header">
              <h2><i class="fas fa-clock-rotate-left"></i> Recent Activity</h2>
              <button class="ea-btn ea-btn-sm ea-btn-secondary" onclick="switchSection('audit')">View All</button>
            </div>
            <div class="ea-card-body">
              <div class="ea-activity" id="dashActivity">
                <div class="ea-loading-state"><div class="ea-spinner"></div><span>Loading activity…</span></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="ea-card">
          <div class="ea-card-header"><h2><i class="fas fa-bolt"></i> Quick Actions</h2></div>
          <div class="ea-card-body">
            <div class="ea-quick-actions">
              <div class="ea-quick-action" onclick="openInviteModal()"><i class="fas fa-user-plus"></i><span>Invite Member</span></div>
              <div class="ea-quick-action" onclick="openCreateTeamModal()"><i class="fas fa-people-group"></i><span>Create Team</span></div>
              <div class="ea-quick-action" onclick="switchSection('apikeys')"><i class="fas fa-key"></i><span>Generate API Key</span></div>
            </div>
          </div>
        </div>
      </section>

      <!-- ═══════════════════════════════════════
           SECTION: MEMBERS
           ═══════════════════════════════════════ -->
      <section class="ea-section" id="sec-members" aria-label="Members management">
        <div class="ea-card">
          <div class="ea-card-header">
            <h2><i class="fas fa-users"></i> Organization Members</h2>
            <button class="ea-btn ea-btn-primary" onclick="openInviteModal()"><i class="fas fa-user-plus"></i> Invite Member</button>
          </div>
          <div class="ea-card-body">
            <div class="ea-filters">
              <div class="ea-search-box">
                <i class="fas fa-search"></i>
                <input type="text" class="ea-input" id="memberSearch" placeholder="Search members…" oninput="filterMembers()">
              </div>
              <select class="ea-select" id="memberRoleFilter" onchange="filterMembers()">
                <option value="">All Roles</option>
                <option value="owner">Owner</option>
                <option value="admin">Admin</option>
                <option value="manager">Manager</option>
                <option value="member">Member</option>
                <option value="viewer">Viewer</option>
              </select>
            </div>
            <div class="ea-table-wrap">
              <table class="ea-table" id="membersTable">
                <thead>
                  <tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last Active</th><th>Actions</th></tr>
                </thead>
                <tbody id="membersBody">
                  <tr><td colspan="6"><div class="ea-loading-state"><div class="ea-spinner"></div><span>Loading members…</span></div></td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </section>

      <!-- ═══════════════════════════════════════
           SECTION: TEAMS
           ═══════════════════════════════════════ -->
      <section class="ea-section" id="sec-teams" aria-label="Teams management">
        <div class="ea-card">
          <div class="ea-card-header">
            <h2><i class="fas fa-people-group"></i> Teams</h2>
            <button class="ea-btn ea-btn-primary" onclick="openCreateTeamModal()"><i class="fas fa-plus"></i> Create Team</button>
          </div>
          <div class="ea-card-body">
            <div class="ea-teams-grid" id="teamsGrid">
              <div class="ea-loading-state" style="grid-column:1/-1"><div class="ea-spinner"></div><span>Loading teams…</span></div>
            </div>
          </div>
        </div>
      </section>

      <!-- ═══════════════════════════════════════
           SECTION: ROLES & PERMISSIONS
           ═══════════════════════════════════════ -->
      <section class="ea-section" id="sec-roles" aria-label="Roles and permissions">
        <div class="ea-card">
          <div class="ea-card-header">
            <h2><i class="fas fa-shield-halved"></i> Roles &amp; Permissions</h2>
          </div>
          <div class="ea-card-body" id="rolesContent">
            <div class="ea-loading-state"><div class="ea-spinner"></div><span>Loading roles…</span></div>
          </div>
        </div>
      </section>

      <!-- ═══════════════════════════════════════
           SECTION: AUDIT LOG
           ═══════════════════════════════════════ -->
      <section class="ea-section" id="sec-audit" aria-label="Audit log">
        <div class="ea-card">
          <div class="ea-card-header">
            <h2><i class="fas fa-scroll"></i> Audit Log</h2>
            <button class="ea-btn ea-btn-secondary" onclick="exportAuditCSV()"><i class="fas fa-download"></i> Export CSV</button>
          </div>
          <div class="ea-card-body">
            <div class="ea-filters">
              <select class="ea-select" id="auditActionFilter" onchange="loadAuditLog(1)">
                <option value="">All Actions</option>
                <option value="login">Login</option>
                <option value="logout">Logout</option>
                <option value="member_invited">Member Invited</option>
                <option value="member_removed">Member Removed</option>
                <option value="role_changed">Role Changed</option>
                <option value="team_created">Team Created</option>
                <option value="api_key_created">API Key Created</option>
                <option value="settings_updated">Settings Updated</option>
              </select>
              <select class="ea-select" id="auditUserFilter" onchange="loadAuditLog(1)">
                <option value="">All Users</option>
              </select>
              <input type="date" class="ea-input" id="auditDateFrom" onchange="loadAuditLog(1)" style="width:auto">
              <input type="date" class="ea-input" id="auditDateTo" onchange="loadAuditLog(1)" style="width:auto">
            </div>
            <div class="ea-table-wrap">
              <table class="ea-table">
                <thead>
                  <tr><th>Date/Time</th><th>User</th><th>Action</th><th>Resource</th><th>Details</th><th>IP Address</th></tr>
                </thead>
                <tbody id="auditBody">
                  <tr><td colspan="6"><div class="ea-loading-state"><div class="ea-spinner"></div><span>Loading audit log…</span></div></td></tr>
                </tbody>
              </table>
            </div>
            <div class="ea-pagination" id="auditPagination"></div>
          </div>
        </div>
      </section>

      <!-- ═══════════════════════════════════════
           SECTION: API KEYS
           ═══════════════════════════════════════ -->
      <section class="ea-section" id="sec-apikeys" aria-label="API key management">
        <div class="ea-card">
          <div class="ea-card-header">
            <h2><i class="fas fa-key"></i> API Keys</h2>
            <button class="ea-btn ea-btn-primary" onclick="generateAPIKey()"><i class="fas fa-plus"></i> Generate Key</button>
          </div>
          <div class="ea-card-body">
            <p style="color:var(--al-text-muted);font-size:.85rem;margin-bottom:16px">Manage API keys for programmatic access to the Alfred Enterprise API. Keys inherit your organization's permissions.</p>
            <div class="ea-table-wrap">
              <table class="ea-table">
                <thead><tr><th>Name</th><th>Key</th><th>Created</th><th>Last Used</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody id="apiKeysBody">
                  <tr><td colspan="6" class="ea-empty-state"><i class="fas fa-key"></i><p>No API keys yet. Generate your first key to get started.</p></td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </section>

      <!-- ═══════════════════════════════════════
           SECTION: USAGE & BILLING
           ═══════════════════════════════════════ -->
      <section class="ea-section" id="sec-usage" aria-label="Usage and billing">
        <div class="ea-card">
          <div class="ea-card-header">
            <h2><i class="fas fa-chart-bar"></i> Usage Overview — Last 30 Days</h2>
          </div>
          <div class="ea-card-body">
            <div class="ea-bar-chart" id="usageChart" style="margin-bottom:24px">
              <div class="ea-loading-state" style="width:100%"><div class="ea-spinner"></div><span>Loading chart…</span></div>
            </div>
          </div>
        </div>

        <div class="ea-card">
          <div class="ea-card-header">
            <h2><i class="fas fa-gauge-high"></i> Resource Breakdown</h2>
          </div>
          <div class="ea-card-body" id="usageBreakdown">
            <div class="ea-loading-state"><div class="ea-spinner"></div><span>Loading usage…</span></div>
          </div>
        </div>
      </section>

      <!-- ═══════════════════════════════════════
           SECTION: SSO SETTINGS
           ═══════════════════════════════════════ -->
      <section class="ea-section" id="sec-sso" aria-label="SSO settings">
        <div class="ea-card">
          <div class="ea-card-header"><h2><i class="fas fa-lock"></i> Single Sign-On (SSO) Configuration</h2></div>
          <div class="ea-card-body">
            <div class="ea-settings-card">
              <h3>SAML 2.0</h3>
              <p>Connect your identity provider for seamless SSO. Supports Okta, Azure AD, OneLogin, and custom SAML.</p>
              <div class="ea-form-group"><label>Identity Provider SSO URL</label><input type="url" class="ea-input" id="ssoUrl" placeholder="https://idp.example.com/saml/sso"></div>
              <div class="ea-form-group"><label>Entity ID / Issuer</label><input type="text" class="ea-input" id="ssoEntityId" placeholder="https://idp.example.com/entity"></div>
              <div class="ea-form-group"><label>X.509 Certificate</label><textarea class="ea-textarea" id="ssoCert" placeholder="Paste your IdP certificate here…" rows="4"></textarea></div>
              <button class="ea-btn ea-btn-primary" onclick="saveSSO()"><i class="fas fa-save"></i> Save SSO Configuration</button>
            </div>

            <div class="ea-settings-card">
              <h3>Service Provider Details</h3>
              <p>Provide these values to your identity provider:</p>
              <div class="ea-form-group"><label>ACS URL</label><input type="text" class="ea-input" value="https://root.com/api/enterprise.php?action=sso/callback" readonly onclick="this.select()"></div>
              <div class="ea-form-group"><label>Entity ID</label><input type="text" class="ea-input" value="https://root.com/enterprise" readonly onclick="this.select()"></div>
            </div>
          </div>
        </div>
      </section>

      <!-- ═══════════════════════════════════════
           SECTION: WHITE LABEL
           ═══════════════════════════════════════ -->
      <section class="ea-section" id="sec-whitelabel" aria-label="White label settings">
        <div class="ea-card">
          <div class="ea-card-header"><h2><i class="fas fa-palette"></i> White Label Configuration</h2></div>
          <div class="ea-card-body">
            <div class="ea-settings-card">
              <h3>Branding</h3>
              <p>Customize the Alfred interface with your organization's branding. Changes apply to all team members.</p>
              <div class="ea-grid-2">
                <div class="ea-form-group"><label>Company Name</label><input type="text" class="ea-input" id="wlCompanyName" placeholder="Your Company"></div>
                <div class="ea-form-group"><label>Primary Color</label><input type="color" class="ea-input" id="wlColor" value="#6c5ce7" style="height:38px;padding:4px"></div>
              </div>
              <div class="ea-form-group"><label>Logo URL</label><input type="url" class="ea-input" id="wlLogo" placeholder="https://example.com/logo.png"></div>
              <div class="ea-form-group"><label>Custom CSS (Advanced)</label><textarea class="ea-textarea" id="wlCSS" placeholder="/* Custom CSS overrides */" rows="4"></textarea></div>
              <button class="ea-btn ea-btn-primary" onclick="saveWhiteLabel()"><i class="fas fa-save"></i> Save Branding</button>
            </div>

            <div class="ea-settings-card">
              <h3>Custom Domain</h3>
              <p>Serve Alfred from your own domain. CNAME your subdomain to <code style="color:var(--al-accent-light)">enterprise.root.com</code>.</p>
              <div class="ea-form-group"><label>Custom Domain</label><input type="text" class="ea-input" id="wlDomain" placeholder="ai.yourcompany.com"></div>
              <button class="ea-btn ea-btn-secondary" onclick="saveWhiteLabel()"><i class="fas fa-globe"></i> Verify Domain</button>
            </div>
          </div>
        </div>
      </section>

      <!-- ═══════════════════════════════════════
           SECTION: ORGANIZATION SETTINGS
           ═══════════════════════════════════════ -->
      <section class="ea-section" id="sec-settings" aria-label="Organization settings">
        <div class="ea-card">
          <div class="ea-card-header"><h2><i class="fas fa-gear"></i> Organization Settings</h2></div>
          <div class="ea-card-body" id="orgSettingsContent">
            <div class="ea-loading-state"><div class="ea-spinner"></div><span>Loading settings…</span></div>
          </div>
        </div>
      </section>

    </main>
  </div>
</div>

<!-- ═══ INVITE MEMBER MODAL ═══ -->
<div class="ea-modal-overlay" id="inviteModal">
  <div class="ea-modal" role="dialog" aria-labelledby="inviteModalTitle" aria-modal="true">
    <div class="ea-modal-header">
      <h3 id="inviteModalTitle"><i class="fas fa-user-plus" style="color:var(--al-accent-light);margin-right:8px"></i> Invite Member</h3>
      <button class="ea-modal-close" onclick="closeModal('inviteModal')" aria-label="Close">&times;</button>
    </div>
    <div class="ea-modal-body">
      <div class="ea-form-group">
        <label for="inviteEmail">Email Address</label>
        <input type="email" class="ea-input" id="inviteEmail" placeholder="colleague@company.com" required>
      </div>
      <div class="ea-form-group">
        <label for="inviteRole">Role</label>
        <select class="ea-select" id="inviteRole">
          <option value="member">Member — can create agents &amp; execute tools</option>
          <option value="viewer">Viewer — read-only dashboard access</option>
          <option value="manager">Manager — can create teams &amp; manage agents</option>
          <option value="admin">Admin — full management except billing</option>
          <option value="owner">Owner — full access including billing</option>
        </select>
      </div>
    </div>
    <div class="ea-modal-footer">
      <button class="ea-btn ea-btn-secondary" onclick="closeModal('inviteModal')">Cancel</button>
      <button class="ea-btn ea-btn-primary" id="inviteSubmitBtn" onclick="submitInvite()"><i class="fas fa-paper-plane"></i> Send Invite</button>
    </div>
  </div>
</div>

<!-- ═══ CREATE TEAM MODAL ═══ -->
<div class="ea-modal-overlay" id="teamModal">
  <div class="ea-modal" role="dialog" aria-labelledby="teamModalTitle" aria-modal="true">
    <div class="ea-modal-header">
      <h3 id="teamModalTitle"><i class="fas fa-people-group" style="color:var(--al-accent-light);margin-right:8px"></i> Create Team</h3>
      <button class="ea-modal-close" onclick="closeModal('teamModal')" aria-label="Close">&times;</button>
    </div>
    <div class="ea-modal-body">
      <div class="ea-form-group">
        <label for="teamName">Team Name</label>
        <input type="text" class="ea-input" id="teamName" placeholder="e.g. Engineering, Sales, Legal" required>
      </div>
      <div class="ea-form-group">
        <label for="teamDesc">Description</label>
        <textarea class="ea-textarea" id="teamDesc" placeholder="What does this team work on?" rows="3"></textarea>
      </div>
      <div class="ea-form-group">
        <label for="teamLead">Team Lead</label>
        <select class="ea-select" id="teamLead">
          <option value="">Select a team lead…</option>
        </select>
      </div>
    </div>
    <div class="ea-modal-footer">
      <button class="ea-btn ea-btn-secondary" onclick="closeModal('teamModal')">Cancel</button>
      <button class="ea-btn ea-btn-primary" id="teamSubmitBtn" onclick="submitCreateTeam()"><i class="fas fa-plus"></i> Create Team</button>
    </div>
  </div>
</div>

<!-- ═══ ROLE EDIT MODAL ═══ -->
<div class="ea-modal-overlay" id="roleModal">
  <div class="ea-modal" role="dialog" aria-labelledby="roleModalTitle" aria-modal="true">
    <div class="ea-modal-header">
      <h3 id="roleModalTitle"><i class="fas fa-user-shield" style="color:var(--al-accent-light);margin-right:8px"></i> Change Role</h3>
      <button class="ea-modal-close" onclick="closeModal('roleModal')" aria-label="Close">&times;</button>
    </div>
    <div class="ea-modal-body">
      <p style="margin-bottom:14px;font-size:.85rem;color:var(--al-text-secondary)">Change the role for <strong id="roleUserName"></strong>:</p>
      <input type="hidden" id="roleUserId">
      <div class="ea-form-group">
        <label for="roleSelect">New Role</label>
        <select class="ea-select" id="roleSelect">
          <option value="viewer">Viewer</option>
          <option value="member">Member</option>
          <option value="manager">Manager</option>
          <option value="admin">Admin</option>
          <option value="owner">Owner</option>
        </select>
      </div>
    </div>
    <div class="ea-modal-footer">
      <button class="ea-btn ea-btn-secondary" onclick="closeModal('roleModal')">Cancel</button>
      <button class="ea-btn ea-btn-primary" onclick="submitRoleChange()"><i class="fas fa-save"></i> Update Role</button>
    </div>
  </div>
</div>


<script src="/assets/js/enterprise-admin-engine.js"></script>


</body>
</html>