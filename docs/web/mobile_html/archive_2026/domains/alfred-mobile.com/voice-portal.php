<?php
require_once __DIR__ . '/includes/auth-gate.inc.php';

if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
<title>Voice & AI Command Center v2.0 — GoSiteMe</title>
<link rel="stylesheet" href="/assets/css/fonts.css">
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<script src="/assets/js/vendor/chart.umd.min.js"></script>
<style>
:root {
  --primary:#0074D9; --primary-light:#00A8FF; --cyan:#00D4FF;
  --purple:#7D00FF; --purple-light:#a855f7;
  --dark:#0a0a14; --dark-card:#12122a; --dark-card-alt:#16162e;
  --dark-surface:#1a1a36; --dark-elevated:#1e1e3a;
  --text:#e8e8f0; --text-muted:#8892b0; --text-dim:#5a6380;
  --success:#10b981; --warning:#f59e0b; --danger:#ef4444; --info:#3b82f6;
  --border:rgba(255,255,255,0.06); --border-hover:rgba(255,255,255,0.12);
  --glow-purple:rgba(125,0,255,0.15); --glow-cyan:rgba(0,212,255,0.15);
  --sidebar-w:260px; --header-h:64px;
  --radius:12px; --radius-sm:8px; --radius-lg:16px;
  --shadow:0 4px 24px rgba(0,0,0,0.3); --shadow-lg:0 8px 40px rgba(0,0,0,0.5);
  --transition:all 0.25s cubic-bezier(0.4,0,0.2,1);
}
*{margin:0;padding:0;box-sizing:border-box;}
html{height:100%;}
body{font-family:'Inter',sans-serif;background:var(--dark);color:var(--text);min-height:100vh;overflow-x:hidden;}
::-webkit-scrollbar{width:6px;} ::-webkit-scrollbar-track{background:transparent;} ::-webkit-scrollbar-thumb{background:rgba(255,255,255,0.1);border-radius:3px;} ::-webkit-scrollbar-thumb:hover{background:rgba(255,255,255,0.2);}
::selection{background:rgba(0,212,255,0.3);}

.sidebar{position:fixed;top:0;left:0;width:var(--sidebar-w);height:100vh;background:rgba(10,10,22,0.97);border-right:1px solid var(--border);z-index:200;display:flex;flex-direction:column;overflow-y:auto;backdrop-filter:blur(20px);transition:transform 0.3s ease;}
.sb-logo{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px;text-decoration:none;}
.sb-logo img{height:28px;}
.sb-logo span{font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:15px;color:var(--cyan);letter-spacing:-0.5px;}
.sb-nav{list-style:none;padding:8px;flex:1;}
.sb-nav li{margin:1px 0;}
.sb-nav a,.sb-nav button{display:flex;align-items:center;gap:12px;padding:9px 14px;border-radius:var(--radius-sm);color:var(--text-muted);text-decoration:none;font-size:13px;font-weight:500;transition:var(--transition);cursor:pointer;width:100%;border:none;background:none;text-align:left;font-family:inherit;}
.sb-nav a:hover,.sb-nav button:hover{background:rgba(0,116,217,0.1);color:var(--text);}
.sb-nav a.active{background:linear-gradient(135deg,rgba(0,116,217,0.15),rgba(125,0,255,0.1));color:var(--cyan);font-weight:600;}
.sb-nav a i,.sb-nav button i{width:18px;text-align:center;font-size:14px;}
.sb-badge{font-size:10px;padding:2px 7px;border-radius:10px;margin-left:auto;font-weight:700;line-height:1.4;}
.sb-badge-cyan{background:rgba(0,212,255,0.15);color:var(--cyan);}
.sb-badge-purple{background:rgba(125,0,255,0.15);color:var(--purple-light);}
.sb-badge-green{background:rgba(16,185,129,0.15);color:var(--success);}
.sb-section{padding:4px 8px;margin-top:4px;}
.sb-section h4{color:var(--text-dim);font-size:10px;text-transform:uppercase;letter-spacing:2px;padding:12px 14px 6px;font-weight:700;}
.sb-user{padding:12px 16px;border-top:1px solid var(--border);display:flex;align-items:center;gap:10px;margin-top:auto;}
.sb-avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--purple));display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;flex-shrink:0;}
.sb-user-info{flex:1;min-width:0;}
.sb-user-name{font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.sb-user-email{font-size:11px;color:var(--text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}

.sb-toggle{display:none;position:fixed;top:12px;left:12px;z-index:300;width:40px;height:40px;border-radius:var(--radius-sm);background:var(--dark-card);border:1px solid var(--border);color:var(--text);cursor:pointer;font-size:16px;align-items:center;justify-content:center;}
.sb-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:199;backdrop-filter:blur(4px);}
@media(max-width:1024px){
  .sidebar{transform:translateX(-100%);}
  .sidebar.open{transform:translateX(0);}
  .sb-toggle{display:flex;}
  .sb-overlay.open{display:block;}
  .main{margin-left:0!important;}
  .top-bar{left:0!important;padding-left:56px!important;}
}

.top-bar{position:fixed;top:0;left:var(--sidebar-w);right:0;height:var(--header-h);background:rgba(10,10,22,0.85);backdrop-filter:blur(20px);border-bottom:1px solid var(--border);z-index:100;display:flex;align-items:center;justify-content:space-between;padding:0 28px;gap:16px;}
.tb-left{display:flex;align-items:center;gap:16px;}
.tb-breadcrumb{display:flex;align-items:center;gap:8px;font-size:13px;}
.tb-breadcrumb a{color:var(--text-muted);text-decoration:none;transition:color 0.2s;} .tb-breadcrumb a:hover{color:var(--cyan);}
.tb-breadcrumb .sep{color:var(--text-dim);}
.tb-breadcrumb .current{color:var(--text);font-weight:600;}
.tb-search{position:relative;}
.tb-search input{background:rgba(255,255,255,0.05);border:1px solid var(--border);border-radius:var(--radius-sm);padding:7px 12px 7px 34px;color:var(--text);font-size:13px;width:240px;transition:var(--transition);font-family:inherit;}
.tb-search input:focus{outline:none;border-color:var(--cyan);background:rgba(255,255,255,0.08);width:320px;}
.tb-search i{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--text-dim);font-size:13px;}
.tb-search kbd{position:absolute;right:8px;top:50%;transform:translateY(-50%);font-size:10px;color:var(--text-dim);background:rgba(255,255,255,0.05);padding:2px 6px;border-radius:4px;border:1px solid var(--border);}
.tb-actions{display:flex;align-items:center;gap:8px;}
.tb-btn{width:36px;height:36px;border-radius:var(--radius-sm);border:1px solid var(--border);background:transparent;color:var(--text-muted);cursor:pointer;font-size:14px;display:flex;align-items:center;justify-content:center;transition:var(--transition);position:relative;}
.tb-btn:hover{background:rgba(255,255,255,0.05);color:var(--text);border-color:var(--border-hover);}
.tb-btn .notif-dot{position:absolute;top:6px;right:6px;width:7px;height:7px;border-radius:50%;background:var(--danger);}
.tb-live{display:flex;align-items:center;gap:6px;padding:6px 14px;border-radius:20px;border:1px solid rgba(16,185,129,0.3);background:rgba(16,185,129,0.08);font-size:12px;font-weight:600;color:var(--success);cursor:default;}
.tb-live .pulse-dot{width:7px;height:7px;border-radius:50%;background:var(--success);animation:pulse-glow 2s ease-in-out infinite;}
@keyframes pulse-glow{0%,100%{opacity:1;box-shadow:0 0 0 0 rgba(16,185,129,0.4);}50%{opacity:0.7;box-shadow:0 0 0 6px rgba(16,185,129,0);}}

.main{margin-left:var(--sidebar-w);padding-top:var(--header-h);min-height:100vh;}
.page-content{padding:24px 28px 80px;max-width:1400px;margin:0 auto;}
@media(max-width:768px){.page-content{padding:16px 12px 80px;}}

.stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px;}
.stat-card{background:var(--dark-card);border:1px solid var(--border);border-radius:var(--radius);padding:20px;position:relative;overflow:hidden;transition:var(--transition);}
.stat-card:hover{border-color:var(--border-hover);transform:translateY(-2px);box-shadow:var(--shadow);}
.stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;border-radius:var(--radius) var(--radius) 0 0;}
.stat-card.accent-cyan::before{background:linear-gradient(90deg,var(--cyan),var(--primary-light));}
.stat-card.accent-purple::before{background:linear-gradient(90deg,var(--purple),var(--purple-light));}
.stat-card.accent-green::before{background:linear-gradient(90deg,var(--success),#34d399);}
.stat-card.accent-warning::before{background:linear-gradient(90deg,var(--warning),#fbbf24);}
.stat-card.accent-info::before{background:linear-gradient(90deg,var(--info),#60a5fa);}
.stat-card.accent-danger::before{background:linear-gradient(90deg,var(--danger),#f87171);}
.sc-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;}
.sc-icon{width:36px;height:36px;border-radius:var(--radius-sm);display:flex;align-items:center;justify-content:center;font-size:16px;}
.sc-icon.cyan{background:rgba(0,212,255,0.1);color:var(--cyan);}
.sc-icon.purple{background:var(--glow-purple);color:var(--purple-light);}
.sc-icon.green{background:rgba(16,185,129,0.1);color:var(--success);}
.sc-icon.warning{background:rgba(245,158,11,0.1);color:var(--warning);}
.sc-icon.info{background:rgba(59,130,246,0.1);color:var(--info);}
.sc-icon.danger{background:rgba(239,68,68,0.1);color:var(--danger);}
.sc-trend{font-size:11px;font-weight:600;display:flex;align-items:center;gap:3px;}
.sc-trend.up{color:var(--success);} .sc-trend.down{color:var(--danger);}
.sc-value{font-size:28px;font-weight:800;font-family:'Space Grotesk',sans-serif;letter-spacing:-1px;}
.sc-label{font-size:12px;color:var(--text-muted);margin-top:2px;}

.card{background:var(--dark-card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-bottom:20px;}
.card-header{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;}
.card-title{font-family:'Space Grotesk',sans-serif;font-size:16px;font-weight:700;display:flex;align-items:center;gap:10px;}
.card-title i{color:var(--cyan);font-size:15px;}
.card-body{padding:20px;}
.card-body.flush{padding:0;}
.card-footer{padding:12px 20px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;}

.tabs{display:flex;gap:0;border-bottom:1px solid var(--border);overflow-x:auto;-webkit-overflow-scrolling:touch;}
.tab{padding:10px 18px;border:none;background:none;color:var(--text-muted);cursor:pointer;font-size:13px;font-weight:500;border-bottom:2px solid transparent;white-space:nowrap;transition:var(--transition);font-family:inherit;}
.tab:hover{color:var(--text);background:rgba(255,255,255,0.02);}
.tab.active{color:var(--cyan);border-bottom-color:var(--cyan);}
.panel{display:none;} .panel.active{display:block;}

.tbl{width:100%;border-collapse:collapse;font-size:13px;}
.tbl th{text-align:left;padding:10px 16px;color:var(--text-dim);font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:1px;border-bottom:1px solid var(--border);background:rgba(255,255,255,0.01);position:sticky;top:0;}
.tbl td{padding:12px 16px;border-bottom:1px solid var(--border);vertical-align:middle;}
.tbl tr{transition:background 0.15s;} .tbl tbody tr:hover{background:rgba(0,212,255,0.02);}
.tbl tr.clickable{cursor:pointer;}

.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:var(--radius-sm);border:none;cursor:pointer;font-size:13px;font-weight:600;transition:var(--transition);text-decoration:none;font-family:inherit;white-space:nowrap;}
.btn:active{transform:scale(0.97);}
.btn-primary{background:linear-gradient(135deg,var(--primary),var(--primary-light));color:#fff;box-shadow:0 2px 8px rgba(0,116,217,0.3);}
.btn-primary:hover{box-shadow:0 4px 16px rgba(0,116,217,0.5);transform:translateY(-1px);}
.btn-purple{background:linear-gradient(135deg,var(--purple),var(--purple-light));color:#fff;box-shadow:0 2px 8px rgba(125,0,255,0.3);}
.btn-purple:hover{box-shadow:0 4px 16px rgba(125,0,255,0.5);transform:translateY(-1px);}
.btn-success{background:rgba(16,185,129,0.12);color:var(--success);border:1px solid rgba(16,185,129,0.3);}
.btn-success:hover{background:rgba(16,185,129,0.2);}
.btn-danger{background:rgba(239,68,68,0.1);color:var(--danger);border:1px solid rgba(239,68,68,0.25);}
.btn-danger:hover{background:rgba(239,68,68,0.18);}
.btn-ghost{background:rgba(255,255,255,0.04);color:var(--text-muted);border:1px solid var(--border);}
.btn-ghost:hover{background:rgba(255,255,255,0.08);color:var(--text);border-color:var(--border-hover);}
.btn-sm{padding:5px 10px;font-size:12px;font-weight:500;}
.btn-xs{padding:3px 8px;font-size:11px;font-weight:500;border-radius:6px;}
.btn-icon{width:32px;height:32px;padding:0;justify-content:center;border-radius:var(--radius-sm);}
.btn-group{display:flex;gap:0;}
.btn-group .btn{border-radius:0;} .btn-group .btn:first-child{border-radius:var(--radius-sm) 0 0 var(--radius-sm);} .btn-group .btn:last-child{border-radius:0 var(--radius-sm) var(--radius-sm) 0;}

.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:12px;font-size:11px;font-weight:600;}
.badge-active{background:rgba(16,185,129,0.12);color:var(--success);}
.badge-inactive{background:rgba(239,68,68,0.1);color:var(--danger);}
.badge-inbound{background:rgba(0,212,255,0.12);color:var(--cyan);}
.badge-outbound{background:var(--glow-purple);color:var(--purple-light);}
.badge-queued{background:rgba(245,158,11,0.1);color:var(--warning);}
.badge-completed,.badge-delivered{background:rgba(16,185,129,0.12);color:var(--success);}
.badge-running,.badge-in_progress{background:rgba(0,212,255,0.12);color:var(--cyan);}
.badge-paused{background:rgba(245,158,11,0.1);color:var(--warning);}
.badge-cancelled,.badge-failed{background:rgba(239,68,68,0.1);color:var(--danger);}
.badge-sent{background:rgba(59,130,246,0.1);color:var(--info);}
.badge-received{background:rgba(16,185,129,0.12);color:var(--success);}

.fm{margin-bottom:16px;}
.fm label{display:block;font-size:11px;color:var(--text-muted);margin-bottom:5px;font-weight:600;text-transform:uppercase;letter-spacing:0.8px;}
.fm input,.fm select,.fm textarea{width:100%;padding:9px 14px;background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:var(--radius-sm);color:var(--text);font-size:13px;font-family:inherit;transition:var(--transition);}
.fm input:focus,.fm select:focus,.fm textarea:focus{outline:none;border-color:var(--cyan);background:rgba(255,255,255,0.06);box-shadow:0 0 0 3px rgba(0,212,255,0.08);}
.fm textarea{min-height:80px;resize:vertical;}
.fm-hint{font-size:11px;color:var(--text-dim);margin-top:4px;}
.fm-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
@media(max-width:600px){.fm-row{grid-template-columns:1fr;}}
.fm-switch{display:flex;align-items:center;gap:10px;cursor:pointer;font-size:13px;user-select:none;}
.fm-switch input{display:none;}
.fm-switch .toggle{width:36px;height:20px;border-radius:10px;background:rgba(255,255,255,0.1);position:relative;transition:background 0.2s;}
.fm-switch .toggle::after{content:'';position:absolute;top:2px;left:2px;width:16px;height:16px;border-radius:50%;background:#fff;transition:transform 0.2s;}
.fm-switch input:checked+.toggle{background:var(--cyan);}
.fm-switch input:checked+.toggle::after{transform:translateX(16px);}

.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:1000;align-items:flex-start;justify-content:center;padding:40px 16px;overflow-y:auto;backdrop-filter:blur(4px);}
.modal-overlay.show{display:flex;}
.modal{background:var(--dark-card);border:1px solid var(--border);border-radius:var(--radius-lg);width:100%;max-width:640px;animation:modalIn 0.25s ease;box-shadow:var(--shadow-lg);}
@keyframes modalIn{from{opacity:0;transform:translateY(-20px) scale(0.97);}to{opacity:1;transform:none;}}
.modal-head{padding:20px 24px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;}
.modal-head h3{font-family:'Space Grotesk',sans-serif;font-size:18px;font-weight:700;display:flex;align-items:center;gap:10px;}
.modal-head h3 i{color:var(--cyan);}
.modal-close{width:32px;height:32px;border:none;background:rgba(255,255,255,0.05);border-radius:var(--radius-sm);color:var(--text-muted);cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center;transition:var(--transition);}
.modal-close:hover{background:rgba(239,68,68,0.15);color:var(--danger);}
.modal-body{padding:24px;}
.modal-foot{padding:16px 24px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:8px;}
.modal-tabs{display:flex;border-bottom:1px solid var(--border);margin:-24px -24px 20px;padding:0 24px;}
.modal-tab{padding:10px 16px;border:none;background:none;color:var(--text-muted);cursor:pointer;font-size:13px;font-weight:500;border-bottom:2px solid transparent;transition:var(--transition);font-family:inherit;}
.modal-tab:hover{color:var(--text);} .modal-tab.active{color:var(--cyan);border-bottom-color:var(--cyan);}
.modal-panel{display:none;} .modal-panel.active{display:block;}

.usage-bar{background:rgba(255,255,255,0.04);border-radius:6px;height:8px;overflow:hidden;margin-top:6px;}
.usage-fill{height:100%;border-radius:6px;transition:width 0.6s ease;}
.usage-fill.green{background:linear-gradient(90deg,#10b981,#34d399);} .usage-fill.yellow{background:linear-gradient(90deg,#f59e0b,#fbbf24);} .usage-fill.red{background:linear-gradient(90deg,#ef4444,#f87171);}

.chart-container{position:relative;height:260px;width:100%;}
.chart-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;}
@media(max-width:900px){.chart-row{grid-template-columns:1fr;}}

.empty{text-align:center;padding:60px 20px;}
.empty i{font-size:48px;color:var(--text-dim);margin-bottom:16px;display:block;opacity:0.3;}
.empty h4{font-size:16px;margin-bottom:8px;}
.empty p{color:var(--text-muted);font-size:14px;margin-bottom:20px;max-width:360px;margin-left:auto;margin-right:auto;}

.loading{text-align:center;padding:40px;color:var(--text-muted);}
.loading i{animation:spin 1s linear infinite;font-size:20px;display:block;margin-bottom:8px;}
@keyframes spin{to{transform:rotate(360deg);}}

.toast-container{position:fixed;top:76px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px;max-width:380px;}
.toast{background:var(--dark-elevated);border:1px solid var(--border);border-radius:var(--radius);padding:12px 16px;display:flex;align-items:flex-start;gap:10px;box-shadow:var(--shadow);animation:toastIn 0.3s ease;font-size:13px;min-width:280px;}
.toast.removing{animation:toastOut 0.3s ease forwards;}
@keyframes toastIn{from{opacity:0;transform:translateX(40px);}to{opacity:1;transform:none;}}
@keyframes toastOut{to{opacity:0;transform:translateX(40px);}}
.toast-icon{font-size:16px;flex-shrink:0;margin-top:1px;}
.toast-icon.success{color:var(--success);} .toast-icon.error{color:var(--danger);} .toast-icon.info{color:var(--info);} .toast-icon.warn{color:var(--warning);}
.toast-msg{flex:1;line-height:1.4;}
.toast-close{background:none;border:none;color:var(--text-dim);cursor:pointer;font-size:14px;padding:0 0 0 8px;flex-shrink:0;}

.agents-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:16px;}
.agent-card{background:var(--dark-card);border:1px solid var(--border);border-radius:var(--radius);padding:20px;transition:var(--transition);position:relative;overflow:hidden;}
.agent-card:hover{border-color:var(--border-hover);box-shadow:var(--shadow);}
.agent-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;}
.agent-card .ac-top{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;}
.agent-card .ac-info h4{font-size:15px;font-weight:700;display:flex;align-items:center;gap:6px;}
.agent-card .ac-info .ac-meta{font-size:12px;color:var(--text-muted);margin-top:4px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
.agent-card .ac-actions{display:flex;gap:4px;}
.agent-card .ac-persona{font-size:13px;color:var(--text-muted);line-height:1.5;margin:8px 0;max-height:44px;overflow:hidden;text-overflow:ellipsis;}
.agent-card .ac-footer{border-top:1px solid var(--border);padding-top:10px;display:flex;justify-content:space-between;align-items:center;font-size:12px;color:var(--text-muted);}

.live-calls{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:20px;}
.live-call{background:var(--dark-card);border:1px solid rgba(16,185,129,0.3);border-radius:var(--radius);padding:14px 18px;display:flex;align-items:center;gap:14px;min-width:280px;animation:liveGlow 2s ease-in-out infinite;}
@keyframes liveGlow{0%,100%{box-shadow:0 0 0 0 rgba(16,185,129,0.1);}50%{box-shadow:0 0 20px rgba(16,185,129,0.1);}}
.live-call .lc-dot{width:10px;height:10px;border-radius:50%;background:var(--success);animation:pulse-glow 1.5s ease-in-out infinite;flex-shrink:0;}
.live-call .lc-info{flex:1;}
.live-call .lc-agent{font-weight:600;font-size:13px;} .live-call .lc-number{font-size:12px;color:var(--text-muted);font-family:monospace;}
.live-call .lc-time{font-family:'Space Grotesk',sans-serif;font-size:15px;font-weight:700;color:var(--success);}

.sms-layout{display:grid;grid-template-columns:280px 1fr;gap:0;border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;min-height:500px;}
@media(max-width:768px){.sms-layout{grid-template-columns:1fr;min-height:auto;}}
.sms-threads{background:var(--dark-card);border-right:1px solid var(--border);overflow-y:auto;max-height:500px;}
.sms-thread{padding:12px 16px;border-bottom:1px solid var(--border);cursor:pointer;transition:background 0.15s;}
.sms-thread:hover,.sms-thread.active{background:rgba(0,212,255,0.04);}
.sms-thread .st-num{font-weight:600;font-size:13px;} .sms-thread .st-preview{font-size:12px;color:var(--text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px;} .sms-thread .st-time{font-size:11px;color:var(--text-dim);margin-top:4px;}
.sms-chat{display:flex;flex-direction:column;background:rgba(0,0,0,0.15);}
.sms-messages{flex:1;padding:16px;overflow-y:auto;max-height:400px;display:flex;flex-direction:column;gap:8px;}
.sms-msg{max-width:75%;padding:10px 14px;border-radius:16px;font-size:13px;line-height:1.5;}
.sms-msg.sent{background:linear-gradient(135deg,var(--primary),var(--primary-light));color:#fff;align-self:flex-end;border-bottom-right-radius:4px;}
.sms-msg.received{background:var(--dark-elevated);border:1px solid var(--border);align-self:flex-start;border-bottom-left-radius:4px;}
.sms-msg .msg-time{font-size:10px;opacity:0.6;margin-top:4px;}
.sms-compose{padding:12px 16px;border-top:1px solid var(--border);display:flex;gap:8px;}
.sms-compose input{flex:1;padding:8px 14px;background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:20px;color:var(--text);font-size:13px;font-family:inherit;}
.sms-compose input:focus{outline:none;border-color:var(--cyan);}

.call-meta-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:20px;}
.call-meta-item{padding:14px;background:rgba(255,255,255,0.02);border:1px solid var(--border);border-radius:var(--radius-sm);}
.call-meta-item .cml{font-size:10px;color:var(--text-dim);text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;} .call-meta-item .cmv{font-size:15px;font-weight:600;}
.transcript{background:rgba(255,255,255,0.02);border:1px solid var(--border);border-radius:var(--radius-sm);padding:16px;font-size:13px;line-height:1.8;max-height:300px;overflow-y:auto;white-space:pre-wrap;font-family:inherit;}

@media(max-width:768px){
  .stats-row{grid-template-columns:repeat(2,1fr);}
  .agents-grid{grid-template-columns:1fr;}
  .tb-search{display:none;}
}
@media(max-width:480px){
  .stats-row{grid-template-columns:1fr;}
}

/* ═══ Agent Performance Stats ═══ */
.ac-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin:10px 0;padding-top:10px;border-top:1px solid var(--border);}
.ac-stat{text-align:center;}
.ac-stat-val{font-size:16px;font-weight:800;font-family:'Space Grotesk',sans-serif;}
.ac-stat-lbl{font-size:10px;color:var(--text-dim);text-transform:uppercase;letter-spacing:0.5px;margin-top:2px;}

/* ═══ Live Call Monitor ═══ */
.live-monitor-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(380px,1fr));gap:16px;}
@media(max-width:768px){.live-monitor-grid{grid-template-columns:1fr;}}
.monitor-card{background:var(--dark-card);border:1px solid rgba(16,185,129,0.25);border-radius:var(--radius);overflow:hidden;animation:liveGlow 3s ease-in-out infinite;}
.mc-header{display:flex;justify-content:space-between;align-items:center;padding:14px 18px;border-bottom:1px solid var(--border);background:rgba(16,185,129,0.04);}
.mc-status{display:flex;align-items:center;gap:10px;}
.mc-timer{font-family:'Space Grotesk',sans-serif;font-size:18px;font-weight:700;color:var(--success);}
.mc-actions{display:flex;gap:4px;}
.mc-body{padding:14px 18px;}
.mc-agent{font-weight:600;font-size:14px;display:flex;align-items:center;gap:8px;margin-bottom:4px;}
.mc-caller{font-size:13px;color:var(--text-muted);display:flex;align-items:center;gap:8px;font-family:monospace;}
.mc-caller-name{font-size:12px;color:var(--text-muted);margin-top:2px;padding-left:24px;}
.mc-footer{padding:8px 18px;border-top:1px solid var(--border);display:flex;gap:8px;align-items:center;}
.mc-transcript{padding:10px 18px;border-top:1px solid var(--border);max-height:120px;overflow-y:auto;font-size:12px;color:var(--text-muted);background:rgba(0,0,0,0.15);}
.mc-tx-placeholder{text-align:center;padding:16px;color:var(--text-dim);font-size:12px;}
.monitor-status-dot{width:8px;height:8px;border-radius:50%;display:inline-block;}
.monitor-status-dot.online{background:var(--success);box-shadow:0 0 8px var(--success);}
.monitor-status-dot.offline{background:var(--text-dim);animation:pulse-glow 2s ease-in-out infinite;}

/* ═══ Voicemail Inbox ═══ */
.vm-layout{display:grid;grid-template-columns:340px 1fr;gap:0;border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;min-height:500px;}
@media(max-width:900px){.vm-layout{grid-template-columns:1fr;min-height:auto;}}
.vm-list{background:var(--dark-card);border-right:1px solid var(--border);overflow-y:auto;max-height:600px;}
.vm-item{padding:14px 18px;border-bottom:1px solid var(--border);cursor:pointer;transition:background 0.15s;}
.vm-item:hover,.vm-item.active{background:rgba(0,212,255,0.04);}
.vm-item.unread{border-left:3px solid var(--cyan);}
.vm-item-header{display:flex;justify-content:space-between;align-items:center;}
.vm-caller{font-weight:700;font-size:13px;font-family:'Space Grotesk',monospace;}
.vm-time{font-size:11px;color:var(--text-dim);}
.vm-agent{font-size:11px;color:var(--text-muted);margin-top:4px;display:flex;align-items:center;gap:4px;}
.vm-preview{font-size:12px;color:var(--text-muted);margin-top:6px;line-height:1.4;max-height:36px;overflow:hidden;}
.vm-meta{display:flex;justify-content:space-between;align-items:center;margin-top:6px;font-size:11px;color:var(--text-dim);}
.vm-unread-dot{width:8px;height:8px;border-radius:50%;background:var(--cyan);}
.vm-detail{padding:24px;overflow-y:auto;max-height:600px;}
.vm-detail-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:8px;}
.vm-detail-header h3{font-family:'Space Grotesk',sans-serif;font-size:18px;display:flex;align-items:center;gap:10px;}
.vm-detail-actions{display:flex;gap:6px;}
.vm-detail-meta{display:flex;gap:16px;font-size:12px;color:var(--text-muted);margin-bottom:20px;flex-wrap:wrap;}
.vm-detail-meta span{display:flex;align-items:center;gap:5px;}

/* ═══ Waveform Player ═══ */
.vm-player{background:var(--dark-card-alt);border:1px solid var(--border);border-radius:var(--radius);padding:16px;margin-bottom:16px;}
.vm-waveform{display:flex;align-items:center;gap:2px;height:60px;margin-bottom:12px;padding:0 4px;}
.wf-bar{width:100%;flex:1;min-width:2px;background:rgba(255,255,255,0.12);border-radius:2px;transition:background 0.15s;}
.wf-bar.played{background:var(--cyan);box-shadow:0 0 4px rgba(0,212,255,0.3);}
.vm-player-controls{display:flex;align-items:center;gap:10px;}
.vm-player-time{font-family:'Space Grotesk',sans-serif;font-size:12px;color:var(--text-muted);min-width:80px;}
.vm-scrubber{flex:1;-webkit-appearance:none;appearance:none;height:4px;background:rgba(255,255,255,0.08);border-radius:2px;outline:none;cursor:pointer;}
.vm-scrubber::-webkit-slider-thumb{-webkit-appearance:none;width:14px;height:14px;border-radius:50%;background:var(--cyan);cursor:pointer;box-shadow:0 0 6px rgba(0,212,255,0.4);}
.vm-scrubber::-moz-range-thumb{width:14px;height:14px;border-radius:50%;background:var(--cyan);cursor:pointer;border:none;}
.vm-transcript{margin-top:16px;}
.vm-transcript label{font-size:11px;color:var(--text-dim);text-transform:uppercase;letter-spacing:1px;display:flex;align-items:center;gap:6px;margin-bottom:8px;font-weight:600;}
.call-recording-player{margin-bottom:16px;}

/* ═══ SMS Templates Drawer ═══ */
.sms-templates-drawer{padding:8px 12px;border-top:1px solid var(--border);background:var(--dark-card-alt);display:flex;gap:6px;overflow-x:auto;-webkit-overflow-scrolling:touch;}
.template-list{display:flex;gap:6px;flex-wrap:nowrap;}
.template-item{padding:6px 12px;background:rgba(0,212,255,0.06);border:1px solid rgba(0,212,255,0.15);border-radius:16px;color:var(--text-muted);font-size:11px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:5px;white-space:nowrap;transition:var(--transition);font-family:inherit;}
.template-item:hover{background:rgba(0,212,255,0.12);color:var(--cyan);border-color:rgba(0,212,255,0.3);}
.template-item i{font-size:10px;color:var(--cyan);}

/* ═══ Campaign Results ═══ */
.campaign-results-mini{display:flex;gap:10px;font-size:12px;font-weight:600;}
.campaign-results-mini span{display:flex;align-items:center;gap:3px;}

/* ═══ Animations ═══ */
@keyframes fadeIn{from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:none;}}
.panel.active{animation:fadeIn 0.25s ease;}
</style>
    <link rel="stylesheet" href="/assets/css/design-tokens.css?v=20260310">
    <link rel="stylesheet" href="/assets/css/components.css?v=20260310">
    <script src="/assets/js/gds-utils.js?v=20260310" defer></script>
    <script src="/assets/js/gds-toast.js?v=20260310" defer></script>
    <script src="/assets/js/gds-modal.js?v=20260310" defer></script>
    <script>window.CSRF_TOKEN = "<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>";</script>
</head>
<body>
<button class="sb-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
<div class="sb-overlay" id="sbOverlay" onclick="toggleSidebar()"></div>
<aside class="sidebar" id="sidebar">
  <a href="/" class="sb-logo"><img src="/brand/logo.png" alt="GoSiteMe"><span>Voice AI</span></a>
  <ul class="sb-nav">
    <li><a href="/dashboard.php"><i class="fas fa-arrow-left"></i> Dashboard</a></li>
  </ul>
  <div class="sb-section"><h4>Command Center</h4>
    <ul class="sb-nav">
      <li><a href="#" data-panel="dashboard" class="active"><i class="fas fa-gauge-high"></i> Overview</a></li>
      <li><a href="#" data-panel="livemonitor"><i class="fas fa-headset"></i> Live Monitor <span class="sb-badge sb-badge-green" id="sbLiveCount">0</span></a></li>
      <li><a href="#" data-panel="analytics"><i class="fas fa-chart-area"></i> Analytics</a></li>
      <li><a href="#" data-panel="agents"><i class="fas fa-robot"></i> AI Agents <span class="sb-badge sb-badge-cyan" id="sbAgentCount">0</span></a></li>
      <li><a href="#" data-panel="phones"><i class="fas fa-phone"></i> Phone Numbers <span class="sb-badge sb-badge-purple" id="sbPhoneCount">0</span></a></li>
      <li><a href="#" data-panel="calls"><i class="fas fa-phone-volume"></i> Call Log</a></li>
      <li><a href="#" data-panel="voicemail"><i class="fas fa-voicemail"></i> Voicemail <span class="sb-badge sb-badge-cyan" id="sbVoicemailCount"></span></a></li>
    </ul>
  </div>
  <div class="sb-section"><h4>Messaging</h4>
    <ul class="sb-nav">
      <li><a href="#" data-panel="sms"><i class="fas fa-comment-sms"></i> SMS</a></li>
      <li><a href="#" data-panel="fax"><i class="fas fa-fax"></i> Fax</a></li>
      <li><a href="#" data-panel="campaigns"><i class="fas fa-bullhorn"></i> Campaigns</a></li>
    </ul>
  </div>
  <div class="sb-section"><h4>System</h4>
    <ul class="sb-nav">
      <li><a href="#" data-panel="documents"><i class="fas fa-file-lines"></i> Documents</a></li>
      <li><a href="#" data-panel="usage"><i class="fas fa-receipt"></i> Usage & Billing</a></li>
      <li><a href="#" data-panel="settings"><i class="fas fa-gear"></i> Settings</a></li>
    </ul>
  </div>
  <div class="sb-section"><h4>Quick Actions</h4>
    <ul class="sb-nav">
      <li><button onclick="showAgentModal()"><i class="fas fa-plus"></i> New Agent</button></li>
      <li><button onclick="showSmsModal()"><i class="fas fa-paper-plane"></i> Send SMS</button></li>
      <li><button onclick="showFaxModal()"><i class="fas fa-fax"></i> Send Fax</button></li>
      <li><a href="/alfred-voice-live/"><i class="fas fa-microphone"></i> Talk to Alfred</a></li>
    </ul>
  </div>
  <div class="sb-user">
    <div class="sb-avatar"><?php echo $initials; ?></div>
    <div class="sb-user-info">
      <div class="sb-user-name"><?php echo htmlspecialchars($clientName); ?></div>
      <div class="sb-user-email"><?php echo htmlspecialchars($clientEmail); ?></div>
    </div>
  </div>
</aside>

<div class="top-bar">
  <div class="tb-left">
    <div class="tb-breadcrumb">
      <a href="/dashboard.php">Dashboard</a><span class="sep">/</span>
      <span class="current" id="tbTitle">Overview</span>
    </div>
  </div>
  <div class="tb-search">
    <i class="fas fa-search"></i>
    <input type="text" placeholder="Search agents, calls, numbers..." id="globalSearch" onkeydown="if(event.key==='Escape')this.blur()">
    <kbd>Ctrl+K</kbd>
  </div>
  <div class="tb-actions">
    <div class="tb-live"><span class="pulse-dot"></span> <span id="liveCallCount">0</span> Live</div>
    <button class="tb-btn" title="Notifications" onclick="toast('No new notifications','info')"><i class="fas fa-bell"></i></button>
    <button class="tb-btn" title="Refresh" onclick="refreshPanel()"><i class="fas fa-arrows-rotate"></i></button>
    <a href="/api/auth.php?action=logout" class="tb-btn" title="Sign Out"><i class="fas fa-sign-out-alt"></i></a>
  </div>
</div>

<div class="toast-container" id="toastContainer"></div>

<main class="main">
<div class="page-content">

<div class="panel active" id="panel-dashboard">
  <div id="liveCalls"></div>
  <div class="stats-row" id="dashStats"><div class="loading"><i class="fas fa-spinner"></i> Loading dashboard...</div></div>
  <div class="chart-row">
    <div class="card">
      <div class="card-header"><span class="card-title"><i class="fas fa-chart-line"></i> Call Volume (7 days)</span></div>
      <div class="card-body"><div class="chart-container"><canvas id="chartCallVolume"></canvas></div></div>
    </div>
    <div class="card">
      <div class="card-header"><span class="card-title"><i class="fas fa-face-smile"></i> Sentiment Breakdown</span></div>
      <div class="card-body"><div class="chart-container"><canvas id="chartSentiment"></canvas></div></div>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><span class="card-title"><i class="fas fa-clock-rotate-left"></i> Recent Calls</span><a href="#" onclick="switchPanel('calls');return false" class="btn btn-ghost btn-sm">View All <i class="fas fa-arrow-right"></i></a></div>
    <div class="card-body flush" id="recentCallsTable"><div class="loading"><i class="fas fa-spinner"></i></div></div>
  </div>
</div>

<!-- LIVE MONITOR PANEL -->
<div class="panel" id="panel-livemonitor">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:8px;">
    <h2 style="font-family:'Space Grotesk',sans-serif;font-size:20px;display:flex;align-items:center;gap:10px;"><i class="fas fa-headset" style="color:var(--success);"></i> Live Call Monitor</h2>
    <div style="display:flex;align-items:center;gap:12px;">
      <div class="tb-live"><span class="pulse-dot"></span> <span id="liveMonitorCount">0</span> Active Calls</div>
      <button class="btn btn-ghost btn-sm" onclick="VoicePortal.loadLiveMonitor()"><i class="fas fa-arrows-rotate"></i> Refresh</button>
    </div>
  </div>
  <div id="liveMonitorContent"><div class="loading"><i class="fas fa-spinner"></i> Connecting to live feed...</div></div>
</div>

<div class="panel" id="panel-analytics">
  <div class="stats-row" id="analyticsPerformance"><div class="loading"><i class="fas fa-spinner"></i> Loading performance...</div></div>
  <div class="chart-row">
    <div class="card"><div class="card-header"><span class="card-title"><i class="fas fa-chart-bar"></i> Daily Call Volume (30 days)</span></div><div class="card-body"><div class="chart-container"><canvas id="chartDaily"></canvas></div></div></div>
    <div class="card"><div class="card-header"><span class="card-title"><i class="fas fa-clock"></i> Peak Hours</span></div><div class="card-body"><div class="chart-container"><canvas id="chartHours"></canvas></div></div></div>
  </div>
  <div class="chart-row">
    <div class="card"><div class="card-header"><span class="card-title"><i class="fas fa-robot"></i> Calls by Agent</span></div><div class="card-body"><div class="chart-container"><canvas id="chartAgents"></canvas></div></div></div>
    <div class="card"><div class="card-header"><span class="card-title"><i class="fas fa-dollar-sign"></i> Cost Trend</span></div><div class="card-body"><div class="chart-container"><canvas id="chartCost"></canvas></div></div></div>
  </div>
  <div class="card"><div class="card-header"><span class="card-title"><i class="fas fa-map-marker-alt"></i> Top Callers</span></div><div class="card-body flush" id="topCallersTable"><div class="loading"><i class="fas fa-spinner"></i></div></div></div>
</div>

<div class="panel" id="panel-agents">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:8px;">
    <h2 style="font-family:'Space Grotesk',sans-serif;font-size:20px;display:flex;align-items:center;gap:10px;"><i class="fas fa-robot" style="color:var(--cyan)"></i> AI Agents</h2>
    <button class="btn btn-primary" onclick="showAgentModal()"><i class="fas fa-plus"></i> Create Agent</button>
  </div>
  <div id="agentsContent"><div class="loading"><i class="fas fa-spinner"></i> Loading agents...</div></div>
</div>

<div class="panel" id="panel-phones">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:8px;">
    <h2 style="font-family:'Space Grotesk',sans-serif;font-size:20px;display:flex;align-items:center;gap:10px;"><i class="fas fa-phone" style="color:var(--cyan)"></i> Phone Numbers</h2>
    <a href="/cart?gid=17" class="btn btn-purple"><i class="fas fa-plus"></i> Get Number</a>
  </div>
  <div class="card"><div class="card-body flush" id="phonesContent"><div class="loading"><i class="fas fa-spinner"></i></div></div></div>
</div>

<div class="panel" id="panel-calls">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:8px;">
    <h2 style="font-family:'Space Grotesk',sans-serif;font-size:20px;display:flex;align-items:center;gap:10px;"><i class="fas fa-phone-volume" style="color:var(--cyan)"></i> Call Log</h2>
    <div class="btn-group">
      <button class="btn btn-ghost btn-sm active" onclick="VoicePortal.filterCalls('all',this)">All</button>
      <button class="btn btn-ghost btn-sm" onclick="VoicePortal.filterCalls('inbound',this)">Inbound</button>
      <button class="btn btn-ghost btn-sm" onclick="VoicePortal.filterCalls('outbound',this)">Outbound</button>
    </div>
  </div>
  <div class="card"><div class="card-body flush" id="callsContent"><div class="loading"><i class="fas fa-spinner"></i></div></div></div>
</div>

<!-- VOICEMAIL PANEL -->
<div class="panel" id="panel-voicemail">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:8px;">
    <h2 style="font-family:'Space Grotesk',sans-serif;font-size:20px;display:flex;align-items:center;gap:10px;"><i class="fas fa-voicemail" style="color:var(--cyan);"></i> Voicemail Inbox</h2>
    <button class="btn btn-ghost btn-sm" onclick="VoicePortal.switchPanel('voicemail')"><i class="fas fa-arrows-rotate"></i> Refresh</button>
  </div>
  <div id="voicemailContent"><div class="loading"><i class="fas fa-spinner"></i> Loading voicemails...</div></div>
</div>

<div class="panel" id="panel-sms">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:8px;">
    <h2 style="font-family:'Space Grotesk',sans-serif;font-size:20px;display:flex;align-items:center;gap:10px;"><i class="fas fa-comment-sms" style="color:var(--cyan)"></i> SMS Messages</h2>
    <button class="btn btn-primary" onclick="showSmsModal()"><i class="fas fa-paper-plane"></i> Send SMS</button>
  </div>
  <div id="smsContent"><div class="loading"><i class="fas fa-spinner"></i></div></div>
</div>

<div class="panel" id="panel-fax">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:8px;">
    <h2 style="font-family:'Space Grotesk',sans-serif;font-size:20px;display:flex;align-items:center;gap:10px;"><i class="fas fa-fax" style="color:var(--cyan)"></i> Fax Documents</h2>
    <button class="btn btn-primary" onclick="showFaxModal()"><i class="fas fa-plus"></i> Send Fax</button>
  </div>
  <div class="card"><div class="card-body flush" id="faxContent"><div class="loading"><i class="fas fa-spinner"></i></div></div></div>
</div>

<div class="panel" id="panel-campaigns">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:8px;">
    <h2 style="font-family:'Space Grotesk',sans-serif;font-size:20px;display:flex;align-items:center;gap:10px;"><i class="fas fa-bullhorn" style="color:var(--cyan)"></i> Campaigns</h2>
    <button class="btn btn-primary" onclick="showCampaignModal()"><i class="fas fa-rocket"></i> New Campaign</button>
  </div>
  <div class="card"><div class="card-body flush" id="campaignsContent"><div class="loading"><i class="fas fa-spinner"></i></div></div></div>
</div>

<div class="panel" id="panel-documents">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:8px;">
    <h2 style="font-family:'Space Grotesk',sans-serif;font-size:20px;display:flex;align-items:center;gap:10px;"><i class="fas fa-file-lines" style="color:var(--cyan)"></i> Documents</h2>
    <button class="btn btn-primary" onclick="showDocModal()"><i class="fas fa-plus"></i> New Document</button>
  </div>
  <div class="card"><div class="card-body flush" id="docsContent"><div class="loading"><i class="fas fa-spinner"></i></div></div></div>
</div>

<div class="panel" id="panel-usage">
  <h2 style="font-family:'Space Grotesk',sans-serif;font-size:20px;display:flex;align-items:center;gap:10px;margin-bottom:16px;"><i class="fas fa-receipt" style="color:var(--cyan)"></i> Usage & Billing</h2>
  <div id="usageContent"><div class="loading"><i class="fas fa-spinner"></i></div></div>
</div>

<div class="panel" id="panel-settings">
  <h2 style="font-family:'Space Grotesk',sans-serif;font-size:20px;display:flex;align-items:center;gap:10px;margin-bottom:16px;"><i class="fas fa-gear" style="color:var(--cyan)"></i> Settings</h2>
  <div class="card">
    <div class="card-header"><span class="card-title"><i class="fas fa-bell"></i> Notifications</span></div>
    <div class="card-body">
      <label class="fm-switch"><input type="checkbox" checked><span class="toggle"></span> Email on missed calls</label>
      <label class="fm-switch" style="margin-top:12px;"><input type="checkbox"><span class="toggle"></span> SMS notifications for voicemail</label>
      <label class="fm-switch" style="margin-top:12px;"><input type="checkbox" checked><span class="toggle"></span> Daily usage summary</label>
      <label class="fm-switch" style="margin-top:12px;"><input type="checkbox"><span class="toggle"></span> Campaign completion alerts</label>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><span class="card-title"><i class="fas fa-key"></i> API Access</span></div>
    <div class="card-body">
      <div class="fm"><label>API Key</label><div style="display:flex;gap:8px;"><input type="password" value="sk-voice-XXXXXXXXXXXXXXXX" id="apiKeyField" readonly style="font-family:monospace;"><button class="btn btn-ghost btn-sm" onclick="toggleApiKey()"><i class="fas fa-eye"></i></button><button class="btn btn-ghost btn-sm" onclick="toast('API key copied to clipboard','success')"><i class="fas fa-copy"></i></button></div><div class="fm-hint">Use this key to access the Voice API programmatically.</div></div>
      <div class="fm"><label>Webhook URL</label><input type="url" placeholder="https://your-app.com/webhook" id="webhookUrl"><div class="fm-hint">We will POST call events (start, end, transcript) to this URL.</div></div>
      <button class="btn btn-primary" onclick="toast('Settings saved','success')"><i class="fas fa-save"></i> Save Settings</button>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><span class="card-title"><i class="fas fa-palette"></i> Preferences</span></div>
    <div class="card-body">
      <div class="fm-row">
        <div class="fm"><label>Default Language</label><select><option>English</option><option>French</option><option>Spanish</option><option>German</option><option>Portuguese</option><option>Italian</option></select></div>
        <div class="fm"><label>Timezone</label><select><option>America/New_York (EST)</option><option>America/Chicago (CST)</option><option>America/Denver (MST)</option><option>America/Los_Angeles (PST)</option><option>America/Toronto (EST)</option><option>Europe/London (GMT)</option><option>Europe/Paris (CET)</option></select></div>
      </div>
      <div class="fm-row">
        <div class="fm"><label>Call Recording</label><select><option>Always record</option><option>Record on request</option><option>Never record</option></select></div>
        <div class="fm"><label>Voicemail</label><select><option>Enabled with AI transcription</option><option>Enabled standard</option><option>Disabled</option></select></div>
      </div>
    </div>
  </div>
</div>

</div>
</main>

<!-- AGENT MODAL -->
<div class="modal-overlay" id="agentModal">
  <div class="modal" style="max-width:720px;">
    <div class="modal-head"><h3><i class="fas fa-robot"></i> <span id="agentModalTitle">Create Agent</span></h3><button class="modal-close" onclick="closeModal('agentModal')">&times;</button></div>
    <div class="modal-body">
      <input type="hidden" id="editAgentId">
      <div class="modal-tabs" id="agentTabs">
        <button class="modal-tab active" onclick="agentTab(0,this)">Profile</button>
        <button class="modal-tab" onclick="agentTab(1,this)">Voice & Persona</button>
        <button class="modal-tab" onclick="agentTab(2,this)">Call Handling</button>
        <button class="modal-tab" onclick="agentTab(3,this)">Knowledge</button>
        <button class="modal-tab" onclick="agentTab(4,this)">Advanced</button>
      </div>
      <div class="modal-panel active" id="agentPane0">
        <div class="fm"><label>Agent Name</label><input type="text" id="agentName" placeholder="e.g. Customer Support Bot"></div>
        <div class="fm"><label>Greeting Message</label><input type="text" id="agentGreeting" placeholder="Hello! Thank you for calling..."></div>
        <div class="fm-row">
          <div class="fm"><label>Status</label><select id="agentActive"><option value="1">Active</option><option value="0">Inactive</option></select></div>
          <div class="fm"><label>Assigned Phone</label><select id="agentPhone"><option value="">None</option></select></div>
        </div>
      </div>
      <div class="modal-panel" id="agentPane1">
        <div class="fm"><label>System Prompt / Persona</label><textarea id="agentPersona" rows="5" placeholder="You are a professional, friendly AI assistant..."></textarea><div class="fm-hint">Define your agent's personality, expertise, and behavior rules.</div></div>
        <div class="fm-row">
          <div class="fm"><label>Language</label><select id="agentLanguage"><option value="en">English</option><option value="fr">French</option><option value="es">Spanish</option><option value="de">German</option><option value="pt">Portuguese</option><option value="it">Italian</option><option value="ja">Japanese</option><option value="zh">Chinese</option><option value="ko">Korean</option><option value="ar">Arabic</option></select></div>
          <div class="fm"><label>Voice</label><select id="agentVoice"><option value="emma">Emma - Warm Female</option><option value="maya">Maya - Professional Female</option><option value="aria">Aria - Friendly Female</option><option value="luna">Luna - Calm Female</option><option value="josh">Josh - Friendly Male</option><option value="mark">Mark - Professional Male</option><option value="leo">Leo - Confident Male</option><option value="dan">Dan - Casual Male</option></select></div>
        </div>
        <div class="fm-row">
          <div class="fm"><label>Speaking Speed</label><select><option>Normal</option><option>Slow</option><option>Fast</option></select></div>
          <div class="fm"><label>Emotional Tone</label><select><option>Neutral</option><option>Warm & Friendly</option><option>Professional</option><option>Energetic</option><option>Calm & Soothing</option></select></div>
        </div>
      </div>
      <div class="modal-panel" id="agentPane2">
        <div class="fm"><label>Transfer Number</label><input type="text" id="agentTransfer" placeholder="+1234567890"><div class="fm-hint">When the caller requests a human, the call transfers here.</div></div>
        <div class="fm-row">
          <div class="fm"><label>Max Call Duration</label><select><option>5 minutes</option><option>10 minutes</option><option>15 minutes</option><option value="30">30 minutes</option><option>Unlimited</option></select></div>
          <div class="fm"><label>After-Hours Behavior</label><select><option>Voicemail</option><option>Transfer to mobile</option><option>AI continues handling</option><option>Busy signal</option></select></div>
        </div>
        <div class="fm-row">
          <div class="fm"><label>Ring Timeout (seconds)</label><input type="number" value="25" min="5" max="60"></div>
          <div class="fm"><label>Queue Music</label><select><option>Default hold music</option><option>Classical</option><option>Jazz</option><option>Lo-fi</option><option>Silent</option></select></div>
        </div>
        <label class="fm-switch" style="margin-top:8px;"><input type="checkbox" checked><span class="toggle"></span> Record all calls</label>
        <label class="fm-switch" style="margin-top:8px;"><input type="checkbox" checked><span class="toggle"></span> Auto-transcribe calls</label>
        <label class="fm-switch" style="margin-top:8px;"><input type="checkbox"><span class="toggle"></span> Send summary email after each call</label>
      </div>
      <div class="modal-panel" id="agentPane3">
        <div class="fm"><label>Knowledge Base URLs</label><textarea rows="3" placeholder="https://your-website.com/faq&#10;https://docs.your-api.com"></textarea><div class="fm-hint">The agent will crawl and reference these pages.</div></div>
        <div class="fm"><label>Custom FAQ Pairs</label><textarea rows="4" placeholder="Q: What are your hours?&#10;A: Monday to Friday, 9 AM to 6 PM EST."></textarea></div>
        <div class="fm"><label>Prohibited Topics</label><input type="text" placeholder="competitor pricing, legal advice"><div class="fm-hint">Comma-separated list of topics the agent should decline.</div></div>
      </div>
      <div class="modal-panel" id="agentPane4">
        <div class="fm-row">
          <div class="fm"><label>AI Model</label><select><option>Claude Sonnet 4 (Recommended)</option><option>Claude Opus 4</option><option>Claude Haiku</option><option>GPT-4o</option></select></div>
          <div class="fm"><label>Temperature</label><input type="range" min="0" max="100" value="40" oninput="this.nextElementSibling.textContent=this.value/100"><span style="font-size:13px;color:var(--text-muted);margin-left:8px;">0.4</span></div>
        </div>
        <div class="fm"><label>Webhook URL (per-agent)</label><input type="url" placeholder="https://your-app.com/agent-webhook"><div class="fm-hint">POST call events for this specific agent.</div></div>
        <div class="fm"><label>Custom Headers</label><input type="text" placeholder='{"Authorization": "Bearer xxx"}'></div>
        <label class="fm-switch" style="margin-top:8px;"><input type="checkbox"><span class="toggle"></span> Enable sentiment analysis</label>
        <label class="fm-switch" style="margin-top:8px;"><input type="checkbox"><span class="toggle"></span> Auto-classify call intent</label>
        <label class="fm-switch" style="margin-top:8px;"><input type="checkbox" checked><span class="toggle"></span> Allow tool calling (DNS, billing, etc.)</label>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="closeModal('agentModal')">Cancel</button>
      <button class="btn btn-primary" onclick="saveAgent()"><i class="fas fa-save"></i> Save Agent</button>
    </div>
  </div>
</div>

<!-- SMS MODAL -->
<div class="modal-overlay" id="smsModal">
  <div class="modal">
    <div class="modal-head"><h3><i class="fas fa-paper-plane"></i> Send SMS</h3><button class="modal-close" onclick="closeModal('smsModal')">&times;</button></div>
    <div class="modal-body">
      <div class="fm"><label>From</label><select id="smsFrom"></select></div>
      <div class="fm"><label>To</label><input type="text" id="smsTo" placeholder="+1 (555) 123-4567"></div>
      <div class="fm"><label>Message</label><textarea id="smsMessage" rows="3" maxlength="1600" placeholder="Type your message..." oninput="document.getElementById('smsCharCount').textContent=this.value.length+'/1600'"></textarea><div class="fm-hint"><span id="smsCharCount">0/1600</span></div></div>
    </div>
    <div class="modal-foot"><button class="btn btn-ghost" onclick="closeModal('smsModal')">Cancel</button><button class="btn btn-primary" onclick="sendSms()"><i class="fas fa-paper-plane"></i> Send</button></div>
  </div>
</div>

<!-- FAX MODAL -->
<div class="modal-overlay" id="faxModal">
  <div class="modal">
    <div class="modal-head"><h3><i class="fas fa-fax"></i> Send Fax</h3><button class="modal-close" onclick="closeModal('faxModal')">&times;</button></div>
    <div class="modal-body">
      <div class="fm"><label>From</label><select id="faxFrom"></select></div>
      <div class="fm"><label>To Fax Number</label><input type="text" id="faxTo" placeholder="+1 (555) 123-4567"></div>
      <div class="fm"><label>Document URL (PDF)</label><input type="url" id="faxDocUrl" placeholder="https://example.com/document.pdf"></div>
    </div>
    <div class="modal-foot"><button class="btn btn-ghost" onclick="closeModal('faxModal')">Cancel</button><button class="btn btn-primary" onclick="sendFax()"><i class="fas fa-fax"></i> Send Fax</button></div>
  </div>
</div>

<!-- CAMPAIGN MODAL -->
<div class="modal-overlay" id="campaignModal">
  <div class="modal" style="max-width:700px;">
    <div class="modal-head"><h3><i class="fas fa-rocket"></i> New Campaign</h3><button class="modal-close" onclick="closeModal('campaignModal')">&times;</button></div>
    <div class="modal-body">
      <div class="fm"><label>Campaign Name</label><input type="text" id="campName" placeholder="e.g. Q1 Outreach"></div>
      <div class="fm-row">
        <div class="fm"><label>Agent</label><select id="campAgent"></select></div>
        <div class="fm"><label>Phone Number</label><select id="campPhone"></select></div>
      </div>
      <div class="fm-row">
        <div class="fm"><label>Type</label><select id="campType"><option value="outbound">Outbound Calls</option><option value="survey">Survey</option><option value="appointment">Appointment Setting</option><option value="followup">Follow-up</option></select></div>
        <div class="fm"><label>Concurrent Lines</label><select id="campLines"><option>1</option><option>2</option><option>3</option><option>5</option><option>10</option><option>20</option></select></div>
      </div>
      <div class="fm"><label>Contact List (name, phone per line)</label><textarea id="campContacts" rows="4" placeholder="John Doe, +14155551234&#10;Jane Smith, +14155555678"></textarea></div>
      <div class="fm"><label>Script Override (optional)</label><textarea id="campScript" rows="3" placeholder="Custom script for this campaign..."></textarea></div>
    </div>
    <div class="modal-foot"><button class="btn btn-ghost" onclick="closeModal('campaignModal')">Cancel</button><button class="btn btn-purple" onclick="createCampaign()"><i class="fas fa-rocket"></i> Launch Campaign</button></div>
  </div>
</div>

<!-- CALL DETAIL MODAL -->
<div class="modal-overlay" id="callDetailModal">
  <div class="modal" style="max-width:760px;">
    <div class="modal-head"><h3><i class="fas fa-phone-volume"></i> Call Details</h3><button class="modal-close" onclick="closeModal('callDetailModal')">&times;</button></div>
    <div class="modal-body" id="callDetailContent"><div class="loading"><i class="fas fa-spinner"></i></div></div>
  </div>
</div>

<!-- DOCUMENT MODAL -->
<div class="modal-overlay" id="docModal">
  <div class="modal">
    <div class="modal-head"><h3><i class="fas fa-file-lines"></i> New Document</h3><button class="modal-close" onclick="closeModal('docModal')">&times;</button></div>
    <div class="modal-body">
      <div class="fm"><label>Document Name</label><input type="text" id="docName" placeholder="e.g. Service Agreement Template"></div>
      <div class="fm"><label>Type</label><select id="docType"><option value="contract">Contract</option><option value="invoice">Invoice</option><option value="proposal">Proposal</option><option value="letter">Letter</option><option value="legal">Legal</option><option value="custom">Custom</option></select></div>
      <div class="fm"><label>Content (HTML)</label><textarea id="docContent" rows="6" placeholder="<h1>Document Title</h1><p>Content here...</p>"></textarea></div>
    </div>
    <div class="modal-foot"><button class="btn btn-ghost" onclick="closeModal('docModal')">Cancel</button><button class="btn btn-primary" onclick="createDoc()"><i class="fas fa-save"></i> Create</button></div>
  </div>
</div>

<!-- CAMPAIGN RESULTS MODAL -->
<div class="modal-overlay" id="campaignResultsModal">
  <div class="modal" style="max-width:700px;">
    <div class="modal-head"><h3><i class="fas fa-chart-pie"></i> Campaign Results</h3><button class="modal-close" onclick="closeModal('campaignResultsModal')">&times;</button></div>
    <div class="modal-body" id="campaignResultsContent"><div class="loading"><i class="fas fa-spinner"></i> Loading results...</div></div>
    <div class="modal-foot"><button class="btn btn-ghost" onclick="closeModal('campaignResultsModal')">Close</button></div>
  </div>
</div>

<script src="/assets/js/voice-portal.js" defer></script>
</body>
</html>
