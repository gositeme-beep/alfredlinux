<?php
$page_title = 'Alfred Fleet Command — AI Swarm Orchestration | GoSiteMe';
$page_description = 'Launch and manage AI agent fleets with Alfred Fleet Command. Real-time swarm orchestration, parallel task execution, and intelligent agent coordination.';
$page_canonical = 'https://root.com/fleet-dashboard.php';
$page_og_title = $page_title;
$page_og_description = $page_description;
$page_twitter_description = 'Alfred Fleet Command — AI swarm orchestration dashboard. Launch fleets of AI agents.';

include __DIR__ . '/includes/auth-gate.inc.php';
include __DIR__ . '/includes/site-header.inc.php';

$is_logged_in = !empty($_SESSION['logged_in']) && !empty($_SESSION['client_id']);
$client_id    = $is_logged_in ? (int)$_SESSION['client_id'] : 0;
$client_name  = $_SESSION['client_name'] ?? '';
$client_email = $_SESSION['client_email'] ?? '';
?>
<style>
/* ========== RESET & BASE ========== */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg-primary:#0a0a14;--bg-secondary:#12121e;--bg-card:#1a1a2e;--bg-card-hover:#22223a;
  --accent:#6c5ce7;--accent-glow:rgba(108,92,231,.35);--accent-light:#a29bfe;
  --green:#00e676;--yellow:#ffd600;--blue:#448aff;--red:#ff5252;--cyan:#18ffff;
  --text-primary:#e8e8f0;--text-secondary:#9898b0;--text-muted:#68688a;
  --border:rgba(255,255,255,.06);--glass:rgba(255,255,255,.04);
  --radius:12px;--radius-sm:8px;--radius-lg:16px;
  --font-main:'Segoe UI',system-ui,-apple-system,sans-serif;
  --font-mono:'JetBrains Mono','Fira Code','Cascadia Code',monospace;
}
html{scroll-behavior:smooth}
body{font-family:var(--font-main);background:var(--bg-primary);color:var(--text-primary);line-height:1.6;min-height:100vh;overflow-x:hidden}
a{color:var(--accent-light);text-decoration:none}
a:hover{color:#fff}

/* ========== SCROLLBAR ========== */
::-webkit-scrollbar{width:6px}
::-webkit-scrollbar-track{background:var(--bg-secondary)}
::-webkit-scrollbar-thumb{background:var(--accent);border-radius:3px}

/* ========== NAV BAR ========== */
.fleet-nav{position:sticky;top:0;z-index:100;background:rgba(10,10,20,.92);backdrop-filter:blur(16px);border-bottom:1px solid var(--border);padding:0 2rem;display:flex;align-items:center;justify-content:space-between;height:60px}
.fleet-nav .logo{display:flex;align-items:center;gap:.6rem;font-size:1.1rem;font-weight:700;color:var(--text-primary)}
.fleet-nav .logo i{color:var(--accent);font-size:1.3rem}
.fleet-nav .nav-links{display:flex;gap:1.5rem;align-items:center}
.fleet-nav .nav-links a{color:var(--text-secondary);font-size:.875rem;transition:color .2s}
.fleet-nav .nav-links a:hover{color:#fff}
.fleet-nav .nav-links .btn-sm{background:var(--accent);color:#fff;padding:.4rem 1rem;border-radius:var(--radius-sm);font-weight:600;transition:background .2s}
.fleet-nav .nav-links .btn-sm:hover{background:#7c6cf7}

/* ========== HERO ========== */
.fleet-hero{position:relative;padding:4rem 2rem 3rem;text-align:center;overflow:hidden}
.fleet-hero::before{content:'';position:absolute;top:-50%;left:50%;transform:translateX(-50%);width:800px;height:800px;background:radial-gradient(circle,var(--accent-glow) 0%,transparent 70%);pointer-events:none;animation:heroPulse 6s ease-in-out infinite}
@keyframes heroPulse{0%,100%{opacity:.4;transform:translateX(-50%) scale(1)}50%{opacity:.7;transform:translateX(-50%) scale(1.1)}}
.fleet-hero h1{font-size:clamp(2rem,5vw,3.2rem);font-weight:800;letter-spacing:-.02em;position:relative}
.fleet-hero h1 .highlight{background:linear-gradient(135deg,var(--accent),var(--cyan));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.fleet-hero .subtitle{color:var(--text-secondary);font-size:1.1rem;margin-top:.8rem;position:relative}
.agent-counter{display:inline-flex;align-items:center;gap:.5rem;background:var(--bg-card);border:1px solid var(--border);padding:.5rem 1.2rem;border-radius:50px;margin-top:1.5rem;font-size:.95rem;position:relative}
.agent-counter .count{font-weight:800;font-size:1.4rem;color:var(--green);font-family:var(--font-mono);min-width:2ch;text-align:center}
.agent-counter .pulse-dot{width:8px;height:8px;background:var(--green);border-radius:50%;animation:pulseDot 2s ease-in-out infinite}
@keyframes pulseDot{0%,100%{box-shadow:0 0 0 0 rgba(0,230,118,.6)}50%{box-shadow:0 0 0 6px rgba(0,230,118,0)}}

/* ========== CONTAINER / GRID ========== */
.fleet-container{max-width:1400px;margin:0 auto;padding:0 1.5rem 3rem}
.fleet-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem}
.fleet-full{grid-column:1/-1}

/* ========== CARDS ========== */
.card{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:1.5rem;transition:border-color .25s,box-shadow .25s}
.card:hover{border-color:rgba(108,92,231,.25);box-shadow:0 0 30px rgba(108,92,231,.08)}
.card-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.2rem}
.card-header h2{font-size:1.1rem;font-weight:700;display:flex;align-items:center;gap:.5rem}
.card-header h2 i{color:var(--accent);font-size:1rem}
.card-header .badge{font-size:.7rem;padding:.25rem .6rem;border-radius:50px;font-weight:600;text-transform:uppercase;letter-spacing:.04em}
.badge-live{background:rgba(0,230,118,.15);color:var(--green)}
.badge-count{background:rgba(108,92,231,.15);color:var(--accent-light)}

/* ========== STATS ROW ========== */
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem}
.stat-card{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:1.2rem;text-align:center;position:relative;overflow:hidden}
.stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px}
.stat-card:nth-child(1)::before{background:linear-gradient(90deg,var(--accent),transparent)}
.stat-card:nth-child(2)::before{background:linear-gradient(90deg,var(--green),transparent)}
.stat-card:nth-child(3)::before{background:linear-gradient(90deg,var(--blue),transparent)}
.stat-card:nth-child(4)::before{background:linear-gradient(90deg,var(--cyan),transparent)}
.stat-card .stat-icon{font-size:1.4rem;margin-bottom:.5rem}
.stat-card:nth-child(1) .stat-icon{color:var(--accent)}
.stat-card:nth-child(2) .stat-icon{color:var(--green)}
.stat-card:nth-child(3) .stat-icon{color:var(--blue)}
.stat-card:nth-child(4) .stat-icon{color:var(--cyan)}
.stat-card .stat-value{font-size:1.8rem;font-weight:800;font-family:var(--font-mono);line-height:1}
.stat-card .stat-label{color:var(--text-muted);font-size:.75rem;margin-top:.3rem;text-transform:uppercase;letter-spacing:.06em}

/* ========== FLEET CREATION FORM ========== */
.form-group{margin-bottom:1rem}
.form-group label{display:block;font-size:.8rem;font-weight:600;color:var(--text-secondary);margin-bottom:.4rem;text-transform:uppercase;letter-spacing:.04em}
.form-input,.form-select,.form-textarea{width:100%;background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-sm);padding:.7rem 1rem;color:var(--text-primary);font-size:.9rem;font-family:var(--font-main);transition:border-color .2s,box-shadow .2s;outline:none}
.form-input:focus,.form-select:focus,.form-textarea:focus{border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-glow)}
.form-textarea{resize:vertical;min-height:80px}
.form-select{cursor:pointer;appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%239898b0'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right .7rem center;background-size:1.2rem;padding-right:2.5rem}
.slider-wrap{display:flex;align-items:center;gap:1rem}
.slider-wrap input[type=range]{flex:1;-webkit-appearance:none;height:6px;background:var(--bg-secondary);border-radius:3px;outline:none}
.slider-wrap input[type=range]::-webkit-slider-thumb{-webkit-appearance:none;width:20px;height:20px;border-radius:50%;background:var(--accent);cursor:pointer;box-shadow:0 0 8px var(--accent-glow)}
.slider-val{font-family:var(--font-mono);font-weight:700;font-size:1.1rem;min-width:2.5ch;text-align:center;color:var(--accent-light)}

/* ========== BUTTONS ========== */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:.5rem;padding:.75rem 1.5rem;border:none;border-radius:var(--radius-sm);font-size:.9rem;font-weight:700;cursor:pointer;transition:all .25s;font-family:var(--font-main);text-transform:uppercase;letter-spacing:.04em}
.btn-primary{background:linear-gradient(135deg,var(--accent),#7c6cf7);color:#fff;box-shadow:0 4px 20px var(--accent-glow)}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 8px 30px rgba(108,92,231,.4);color:#fff}
.btn-primary:active{transform:translateY(0)}
.btn-block{width:100%}
.btn-launch{font-size:1.05rem;padding:1rem 2rem;position:relative;overflow:hidden}
.btn-launch::after{content:'';position:absolute;top:50%;left:50%;width:0;height:0;background:rgba(255,255,255,.15);border-radius:50%;transform:translate(-50%,-50%);transition:width .5s,height .5s}
.btn-launch:hover::after{width:300px;height:300px}

/* ========== ACTIVE FLEETS ========== */
.fleet-list{display:flex;flex-direction:column;gap:.8rem;max-height:400px;overflow-y:auto;padding-right:.3rem}
.fleet-item{background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius);padding:1rem 1.2rem;transition:background .2s}
.fleet-item:hover{background:var(--bg-card-hover)}
.fleet-item-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:.6rem}
.fleet-item-name{font-weight:700;font-size:.95rem}
.fleet-item-status{font-size:.7rem;padding:.2rem .5rem;border-radius:50px;font-weight:600}
.status-running{background:rgba(0,230,118,.12);color:var(--green)}
.status-queued{background:rgba(255,214,0,.12);color:var(--yellow)}
.status-completed{background:rgba(68,138,255,.12);color:var(--blue)}
.fleet-item-objective{color:var(--text-secondary);font-size:.8rem;margin-bottom:.6rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.fleet-item-meta{display:flex;gap:1rem;font-size:.75rem;color:var(--text-muted);align-items:center}
.fleet-item-meta i{margin-right:.3rem}
.progress-bar{width:100%;height:4px;background:var(--bg-primary);border-radius:2px;margin-top:.6rem;overflow:hidden}
.progress-fill{height:100%;border-radius:2px;transition:width 1s ease;background:linear-gradient(90deg,var(--accent),var(--green))}

/* ========== AGENT ACTIVITY FEED ========== */
.activity-feed{max-height:420px;overflow-y:auto;display:flex;flex-direction:column;gap:.4rem;padding-right:.3rem}
.activity-item{display:flex;gap:.7rem;padding:.6rem .8rem;border-radius:var(--radius-sm);background:var(--bg-secondary);border-left:3px solid transparent;animation:slideInFeed .4s ease-out;font-size:.85rem}
@keyframes slideInFeed{from{opacity:0;transform:translateX(-10px)}to{opacity:1;transform:translateX(0)}}
.activity-item.type-action{border-left-color:var(--accent)}
.activity-item.type-complete{border-left-color:var(--green)}
.activity-item.type-thinking{border-left-color:var(--yellow)}
.activity-item.type-error{border-left-color:var(--red)}
.activity-icon{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.7rem;flex-shrink:0}
.type-action .activity-icon{background:rgba(108,92,231,.15);color:var(--accent)}
.type-complete .activity-icon{background:rgba(0,230,118,.15);color:var(--green)}
.type-thinking .activity-icon{background:rgba(255,214,0,.15);color:var(--yellow)}
.type-error .activity-icon{background:rgba(255,82,82,.15);color:var(--red)}
.activity-content{flex:1;min-width:0}
.activity-agent{font-weight:700;font-size:.8rem}
.activity-msg{color:var(--text-secondary);font-size:.8rem}
.activity-time{color:var(--text-muted);font-size:.7rem;font-family:var(--font-mono);flex-shrink:0;align-self:center}

/* ========== FLEET TOPOLOGY ========== */
.topology-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(56px,1fr));gap:.6rem;padding:.5rem 0}
.agent-node{width:50px;height:50px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;font-family:var(--font-mono);position:relative;cursor:default;transition:transform .2s,box-shadow .2s;margin:0 auto}
.agent-node:hover{transform:scale(1.15)}
.agent-node::after{content:'';position:absolute;inset:-3px;border-radius:50%;border:2px solid transparent}
.node-active{background:rgba(0,230,118,.15);color:var(--green);border:2px solid rgba(0,230,118,.3)}
.node-active::after{border-color:rgba(0,230,118,.2);animation:nodeRing 2s ease-in-out infinite}
.node-thinking{background:rgba(255,214,0,.15);color:var(--yellow);border:2px solid rgba(255,214,0,.3);animation:nodeThink 1.5s ease-in-out infinite}
.node-completed{background:rgba(68,138,255,.15);color:var(--blue);border:2px solid rgba(68,138,255,.3)}
.node-error{background:rgba(255,82,82,.15);color:var(--red);border:2px solid rgba(255,82,82,.3)}
.node-idle{background:var(--glass);color:var(--text-muted);border:2px solid var(--border)}
@keyframes nodeRing{0%,100%{box-shadow:0 0 0 0 rgba(0,230,118,.3)}50%{box-shadow:0 0 0 6px rgba(0,230,118,0)}}
@keyframes nodeThink{0%,100%{opacity:.6}50%{opacity:1}}
.topology-legend{display:flex;gap:1.2rem;flex-wrap:wrap;margin-top:.8rem;padding-top:.8rem;border-top:1px solid var(--border)}
.legend-item{display:flex;align-items:center;gap:.4rem;font-size:.75rem;color:var(--text-secondary)}
.legend-dot{width:10px;height:10px;border-radius:50%}
.legend-dot.active{background:var(--green)}
.legend-dot.thinking{background:var(--yellow)}
.legend-dot.completed{background:var(--blue)}
.legend-dot.error{background:var(--red)}
.legend-dot.idle{background:var(--text-muted)}

/* ========== FLEET TEMPLATES ========== */
.fleet-templates{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:.6rem;margin-bottom:1.2rem}
.fleet-tpl{background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-sm);padding:.7rem .8rem;cursor:pointer;transition:all .25s;text-align:center}
.fleet-tpl:hover{border-color:var(--accent);background:rgba(108,92,231,.08);transform:translateY(-1px)}
.fleet-tpl .tpl-icon{font-size:1.1rem;margin-bottom:.3rem}
.fleet-tpl .tpl-name{font-size:.75rem;font-weight:700;margin-bottom:.15rem}
.fleet-tpl .tpl-agents{font-size:.65rem;color:var(--text-muted);font-family:var(--font-mono)}

/* ========== RESPONSIVE ========== */
@media(max-width:1024px){
  .fleet-grid{grid-template-columns:1fr}
  .stats-row{grid-template-columns:repeat(2,1fr)}
}
@media(max-width:600px){
  .fleet-hero{padding:2.5rem 1rem 2rem}
  .fleet-hero h1{font-size:1.8rem}
  .stats-row{grid-template-columns:1fr 1fr}
  .stat-card .stat-value{font-size:1.4rem}
  .fleet-nav .nav-links a:not(.btn-sm){display:none}
  .fleet-container{padding:0 1rem 2rem}
  .topology-grid{grid-template-columns:repeat(auto-fill,minmax(44px,1fr))}
  .agent-node{width:40px;height:40px;font-size:.6rem}
}

/* ========== FOOTER ========== */
.fleet-footer{text-align:center;padding:2rem;border-top:1px solid var(--border);color:var(--text-muted);font-size:.8rem}
.fleet-footer a{color:var(--accent-light)}

/* ========== v2.0 TABS ========== */
.fleet-tabs{display:flex;gap:.5rem;margin-bottom:1.5rem;border-bottom:1px solid var(--border);padding-bottom:.5rem;overflow-x:auto}
.fleet-tab-btn{background:none;border:none;color:var(--text-secondary);font-size:.85rem;font-weight:600;cursor:pointer;padding:.6rem 1rem;border-radius:var(--radius-sm) var(--radius-sm) 0 0;transition:all .2s;font-family:var(--font-main);display:flex;align-items:center;gap:.4rem;white-space:nowrap}
.fleet-tab-btn:hover{color:var(--text-primary);background:var(--glass)}
.fleet-tab-btn.active{color:var(--accent-light);background:rgba(108,92,231,.1);border-bottom:2px solid var(--accent)}
.fleet-tab-panel{display:none}
.fleet-tab-panel.active{display:block}

/* ========== v2.0 HISTORY TABLE ========== */
.history-table{width:100%;border-collapse:collapse}
.history-table th{text-align:left;font-size:.7rem;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);padding:.6rem .8rem;border-bottom:1px solid var(--border)}
.history-table td{padding:.6rem .8rem;font-size:.85rem;border-bottom:1px solid var(--border)}
.history-table tr:hover{background:var(--glass)}
.status-error{background:rgba(255,82,82,.12);color:var(--red)}

/* ========== v2.0 PERFORMANCE BARS ========== */
.perf-metrics{display:flex;flex-direction:column;gap:1rem}
.perf-metric{display:grid;grid-template-columns:100px 1fr 60px;align-items:center;gap:.8rem}
.perf-label{font-size:.8rem;font-weight:600;color:var(--text-secondary)}
.perf-bar-wrap{height:8px;background:var(--bg-secondary);border-radius:4px;overflow:hidden}
.perf-bar{height:100%;border-radius:4px;transition:width 1s ease}
.perf-val{font-size:.85rem;font-weight:700;font-family:var(--font-mono);text-align:right;color:var(--text-primary)}

/* ========== v2.0 HEATMAP ========== */
.heatmap-wrap{margin-top:1rem;border-radius:var(--radius-sm);overflow:hidden;background:var(--bg-secondary);padding:.6rem}
.heatmap-wrap canvas{display:block;width:100%;border-radius:4px}

/* ========== v2.0 TOASTS ========== */
.fleet-toast{position:fixed;bottom:1.5rem;right:1.5rem;padding:.8rem 1.5rem;border-radius:var(--radius-sm);font-size:.85rem;font-weight:600;z-index:9999;transform:translateY(20px);opacity:0;transition:all .3s;backdrop-filter:blur(12px);border:1px solid var(--border)}
.fleet-toast.show{transform:translateY(0);opacity:1}
.fleet-toast-success{background:rgba(0,230,118,.15);color:var(--green);border-color:rgba(0,230,118,.3)}
.fleet-toast-info{background:rgba(108,92,231,.15);color:var(--accent-light);border-color:rgba(108,92,231,.3)}
.fleet-toast-error{background:rgba(255,82,82,.15);color:var(--red);border-color:rgba(255,82,82,.3)}

/* ========== v2.0 EXPORT BAR ========== */
.export-bar{display:flex;gap:.6rem;align-items:center}
.btn-icon{background:var(--bg-secondary);border:1px solid var(--border);color:var(--text-secondary);padding:.4rem .7rem;border-radius:var(--radius-sm);cursor:pointer;font-size:.75rem;transition:all .2s;display:inline-flex;align-items:center;gap:.3rem}
.btn-icon:hover{border-color:var(--accent);color:var(--accent-light);background:rgba(108,92,231,.08)}

.fleet-item-action{cursor:pointer;transition:opacity .2s}
.fleet-item-action:hover{opacity:.7}

/* ========== v3.0 FLEET OVERVIEW ========== */
.v2-stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem}
.v2-stat{background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius);padding:1.2rem;text-align:center;position:relative;overflow:hidden}
.v2-stat::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:var(--accent-c,var(--accent))}
.v2-stat-icon{font-size:1.3rem;color:var(--accent-c,var(--accent));margin-bottom:.4rem}
.v2-stat-val{font-size:1.6rem;font-weight:800;font-family:var(--font-mono);line-height:1.2}
.v2-stat-label{color:var(--text-muted);font-size:.7rem;text-transform:uppercase;letter-spacing:.06em;margin-top:.2rem}

.v2-capacity-bar{background:var(--bg-secondary);border-radius:var(--radius);padding:1.2rem;margin-bottom:1.5rem;border:1px solid var(--border)}
.v2-cap-header{display:flex;justify-content:space-between;font-size:.85rem;font-weight:600;margin-bottom:.6rem}
.v2-cap-pct{color:var(--accent-light);font-family:var(--font-mono)}
.v2-cap-track{height:10px;background:var(--bg-primary);border-radius:5px;overflow:hidden}
.v2-cap-fill{height:100%;border-radius:5px;background:linear-gradient(90deg,var(--accent),var(--green));transition:width 1s ease}

.v2-hierarchy{text-align:center;padding:1.5rem;background:var(--bg-secondary);border-radius:var(--radius);border:1px solid var(--border)}
.v2-hier-node{display:inline-flex;flex-direction:column;align-items:center;gap:.2rem;background:var(--bg-card);border:2px solid var(--accent);border-radius:var(--radius-sm);padding:.5rem .8rem;font-size:.75rem;font-weight:600;min-width:60px}
.v2-hier-commander{border-color:var(--yellow);background:rgba(255,214,0,.08)}
.v2-hier-commander i{color:var(--yellow);font-size:1.2rem}
.v2-hier-arrow{color:var(--text-muted);margin:.5rem 0;font-size:.9rem}
.v2-hier-row{display:flex;gap:.5rem;justify-content:center;flex-wrap:wrap}
.v2-hier-label{margin-top:.8rem;color:var(--text-secondary);font-size:.8rem}

/* ========== v3.0 DOMAIN GRID ========== */
.v2-domain-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:1rem}
.v2-domain-card{background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius);padding:1rem;text-align:center;cursor:pointer;transition:all .25s}
.v2-domain-card:hover,.v2-domain-card.active{border-color:var(--accent);transform:translateY(-2px);box-shadow:0 4px 20px rgba(108,92,231,.15)}
.v2-domain-icon{font-size:1.5rem;margin-bottom:.4rem}
.v2-domain-name{font-weight:700;font-size:.85rem;margin-bottom:.2rem}
.v2-domain-count{font-size:1.4rem;font-weight:800;font-family:var(--font-mono)}
.v2-domain-bar{height:4px;background:var(--bg-primary);border-radius:2px;margin:.5rem 0;overflow:hidden}
.v2-domain-bar-fill{height:100%;border-radius:2px;transition:width .8s ease}
.v2-domain-meta{color:var(--text-muted);font-size:.7rem}

/* ========== v3.0 AGENT GRID ========== */
.v2-grid-section{margin-bottom:1.5rem}
.v2-grid-section-title{font-size:1rem;font-weight:700;margin-bottom:.8rem;display:flex;align-items:center;gap:.5rem}
.v2-grid-count{font-size:.75rem;font-weight:400;color:var(--text-muted);margin-left:auto}
.v2-agent-dots{display:flex;flex-wrap:wrap;gap:3px}
.v2-dot{width:8px;height:8px;border-radius:50%;background:var(--dot-color,var(--accent));opacity:.6;transition:opacity .2s}
.v2-dot:hover{opacity:1;transform:scale(1.8)}
.v2-dot-busy{animation:dotPulse 1.5s ease-in-out infinite}
@keyframes dotPulse{0%,100%{opacity:.4}50%{opacity:1}}
.v2-agent-nodes{display:grid;grid-template-columns:repeat(auto-fill,minmax(52px,1fr));gap:4px}
.v2-agent-tile{background:var(--bg-secondary);border:1px solid var(--border);border-radius:4px;padding:4px;text-align:center;font-size:.55rem;position:relative;cursor:default;transition:border-color .2s}
.v2-agent-tile:hover{border-color:var(--accent)}
.v2-tile-dot{width:6px;height:6px;border-radius:50%;position:absolute;top:3px;right:3px}
.v2-tile-id{font-family:var(--font-mono);color:var(--text-secondary);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.v2-grid-pager{display:flex;gap:.4rem;justify-content:center;margin-top:1rem;flex-wrap:wrap}
.v2-page-btn{background:var(--bg-secondary);border:1px solid var(--border);color:var(--text-secondary);padding:.3rem .6rem;border-radius:4px;cursor:pointer;font-size:.75rem;font-family:var(--font-mono)}
.v2-page-btn:hover,.v2-page-btn.active{background:var(--accent);color:#fff;border-color:var(--accent)}
.v2-grid-footer{text-align:center;padding:1rem;color:var(--text-muted);font-size:.8rem;border-top:1px solid var(--border);margin-top:1rem}

/* ========== v3.0 MESSAGING ========== */
.v2-msg-compose{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.5rem}
.v2-msg-compose-full{grid-column:1/-1}
.v2-msg{background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-sm);padding:1rem;margin-bottom:.6rem;transition:border-color .2s}
.v2-msg-unread{border-left:3px solid var(--accent)}
.v2-msg-acked{border-left:3px solid var(--green);opacity:.7}
.v2-msg-header{display:flex;gap:.8rem;align-items:center;flex-wrap:wrap;margin-bottom:.5rem;font-size:.8rem}
.v2-msg-type{font-weight:700;text-transform:uppercase;font-size:.7rem;letter-spacing:.04em}
.v2-msg-from{color:var(--text-secondary)}
.v2-msg-domain{background:rgba(108,92,231,.1);padding:.1rem .4rem;border-radius:4px;font-size:.7rem;color:var(--accent-light)}
.v2-msg-time{color:var(--text-muted);font-size:.7rem;font-family:var(--font-mono);margin-left:auto}
.v2-msg-pri{background:rgba(255,214,0,.1);color:var(--yellow);padding:.1rem .4rem;border-radius:4px;font-size:.65rem;font-weight:700}
.v2-msg-payload{background:var(--bg-primary);border-radius:4px;padding:.6rem .8rem;font-size:.75rem;font-family:var(--font-mono);color:var(--text-secondary);overflow-x:auto;max-height:120px;margin:0;white-space:pre-wrap;word-break:break-word}
.v2-ack-btn{background:var(--accent);color:#fff;border:none;padding:.3rem .8rem;border-radius:4px;font-size:.75rem;cursor:pointer;margin-top:.5rem;font-weight:600}
.v2-ack-btn:hover{background:#7c6cf7}
.v2-ack-done{color:var(--green);font-size:.75rem;margin-top:.5rem;display:inline-block}
.v2-empty{text-align:center;padding:3rem;color:var(--text-muted);font-size:.9rem}

/* ========== v3.0 ROUTE TASK ========== */
.v2-route-panel{display:grid;grid-template-columns:1fr 1fr;gap:1rem;align-items:end}
.v2-route-entry{display:flex;gap:.6rem;align-items:center;padding:.5rem .8rem;background:var(--bg-secondary);border-radius:var(--radius-sm);font-size:.8rem;margin-top:.5rem;animation:slideInFeed .4s ease-out}

@media(max-width:1024px){
  .v2-stat-grid{grid-template-columns:repeat(2,1fr)}
  .v2-msg-compose{grid-template-columns:1fr}
  .v2-route-panel{grid-template-columns:1fr}
}
@media(max-width:600px){
  .fleet-tabs{gap:.2rem}
  .fleet-tab-btn{font-size:.75rem;padding:.5rem .6rem}
  .perf-metric{grid-template-columns:80px 1fr 50px}
  .v2-stat-grid{grid-template-columns:1fr 1fr}
  .v2-hier-row{gap:.3rem}
  .v2-hier-node{min-width:48px;padding:.3rem .4rem;font-size:.6rem}
  .v2-domain-grid{grid-template-columns:1fr 1fr}
}
</style>

<!-- Navigation -->
<nav class="fleet-nav">
  <a href="/" class="logo"><i class="fas fa-satellite-dish"></i> Alfred Fleet Command</a>
  <div class="nav-links">
    <a href="/alfred.php">Alfred AI</a>
    <a href="/team-chat.php">Team Chat</a>
    <a href="/voice.php">Voice</a>
    <a href="/conference-room.php">Conference</a>
    <span id="wsStatus" class="badge badge-count" style="font-size:.65rem;padding:.2rem .5rem">OFFLINE</span>
    <a href="/dashboard.php" class="btn-sm"><i class="fas fa-th-large"></i> Dashboard</a>
  </div>
</nav>

<!-- Hero Section -->
<section class="fleet-hero">
  <h1><span class="highlight">Alfred Fleet Command</span> <span style="font-size:.5em;color:var(--accent);font-weight:600;vertical-align:super">v3.0</span></h1>
  <p class="subtitle">10,000 AI agents across 15 domains — real-time orchestration at scale</p>
  <div class="agent-counter">
    <span class="pulse-dot"></span>
    <span><span class="count" id="liveAgentCount">0</span> agents online</span>
  </div>
</section>

<!-- Stats Row -->
<div class="fleet-container">
  <div class="stats-row">
    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
      <div class="stat-value" id="statFleets">0</div>
      <div class="stat-label">Active Fleets</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-robot"></i></div>
      <div class="stat-value" id="statAgents">0</div>
      <div class="stat-label">Agents Deployed</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-check-double"></i></div>
      <div class="stat-value" id="statTasks">0</div>
      <div class="stat-label">Tasks Completed</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-clock"></i></div>
      <div class="stat-value" id="statAvgTime">0s</div>
      <div class="stat-label">Avg Completion</div>
    </div>
  </div>

  <!-- v2.0 Tab Navigation -->
  <div class="fleet-tabs">
    <button class="fleet-tab-btn" data-fleet-tab="overview"><i class="fas fa-satellite-dish"></i> Fleet Overview</button>
    <button class="fleet-tab-btn active" data-fleet-tab="command"><i class="fas fa-rocket"></i> Command Center</button>
    <button class="fleet-tab-btn" data-fleet-tab="agentgrid"><i class="fas fa-th"></i> Agent Grid</button>
    <button class="fleet-tab-btn" data-fleet-tab="messaging"><i class="fas fa-envelope"></i> Messaging</button>
    <button class="fleet-tab-btn" data-fleet-tab="history"><i class="fas fa-history"></i> Fleet History</button>
    <button class="fleet-tab-btn" data-fleet-tab="performance"><i class="fas fa-tachometer-alt"></i> Performance</button>
  </div>

  <!-- Tab: Command Center -->
  <div id="ftab-command" class="fleet-tab-panel active">

  <!-- Main Grid -->
  <div class="fleet-grid">

    <!-- Fleet Creation Form -->
    <div class="card">
      <div class="card-header">
        <h2><i class="fas fa-plus-circle"></i> Launch New Fleet</h2>
      </div>
      <form id="fleetForm" onsubmit="return window.FC.launchFleet(event)">
        <div class="form-group">
          <label>Quick Templates</label>
          <div class="fleet-templates">
            <div class="fleet-tpl" onclick="applyTemplate('research')">
              <div class="tpl-icon" style="color:var(--cyan)"><i class="fas fa-microscope"></i></div>
              <div class="tpl-name">Research Team</div>
              <div class="tpl-agents">5 agents &middot; adaptive</div>
            </div>
            <div class="fleet-tpl" onclick="applyTemplate('content')">
              <div class="tpl-icon" style="color:var(--green)"><i class="fas fa-pen-fancy"></i></div>
              <div class="tpl-name">Content Factory</div>
              <div class="tpl-agents">10 agents &middot; parallel</div>
            </div>
            <div class="fleet-tpl" onclick="applyTemplate('security')">
              <div class="tpl-icon" style="color:var(--red)"><i class="fas fa-shield-alt"></i></div>
              <div class="tpl-name">Security Audit</div>
              <div class="tpl-agents">8 agents &middot; sequential</div>
            </div>
            <div class="fleet-tpl" onclick="applyTemplate('data')">
              <div class="tpl-icon" style="color:var(--blue)"><i class="fas fa-chart-bar"></i></div>
              <div class="tpl-name">Data Analysis</div>
              <div class="tpl-agents">12 agents &middot; parallel</div>
            </div>
            <div class="fleet-tpl" onclick="applyTemplate('support')">
              <div class="tpl-icon" style="color:var(--yellow)"><i class="fas fa-headset"></i></div>
              <div class="tpl-name">Support Team</div>
              <div class="tpl-agents">6 agents &middot; adaptive</div>
            </div>
            <div class="fleet-tpl" onclick="applyTemplate('health')">
              <div class="tpl-icon" style="color:var(--green)"><i class="fas fa-dna"></i></div>
              <div class="tpl-name">Health Research</div>
              <div class="tpl-agents">15 agents &middot; parallel</div>
            </div>
          </div>
        </div>
        <div class="form-group">
          <label for="fleetObjective">Mission Objective</label>
          <textarea id="fleetObjective" class="form-textarea" placeholder="Describe the objective for your AI fleet..." required></textarea>
        </div>
        <div class="form-group">
          <label for="fleetStrategy">Execution Strategy</label>
          <select id="fleetStrategy" class="form-select" required>
            <option value="parallel">⚡ Parallel — All agents work simultaneously</option>
            <option value="sequential">📋 Sequential — Agents work in order</option>
            <option value="adaptive">🧠 Adaptive — AI decides optimal strategy</option>
          </select>
        </div>
        <div class="form-group">
          <label>Agent Count: <span id="agentCountLabel" class="slider-val">5</span></label>
          <div class="slider-wrap">
            <span style="color:var(--text-muted);font-size:.75rem">2</span>
            <input type="range" id="agentCount" min="2" max="35" value="5" oninput="document.getElementById('agentCountLabel').textContent=this.value">
            <span style="color:var(--text-muted);font-size:.75rem">35</span>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-launch">
          <i class="fas fa-rocket"></i> Launch Fleet
        </button>
      </form>
    </div>

    <!-- Active Fleets -->
    <div class="card">
      <div class="card-header">
        <h2><i class="fas fa-satellite"></i> Active Fleets</h2>
        <div class="export-bar">
          <button class="btn-icon" onclick="window.FC&&FC.exportFleetCSV()" title="Export Fleets"><i class="fas fa-download"></i> CSV</button>
          <span class="badge badge-live"><i class="fas fa-circle" style="font-size:.45rem;vertical-align:middle;margin-right:.3rem"></i> LIVE</span>
        </div>
      </div>
      <div class="fleet-list" id="fleetList">
        <!-- Populated by JS -->
      </div>
    </div>

    <!-- Agent Activity Feed -->
    <div class="card">
      <div class="card-header">
        <h2><i class="fas fa-stream"></i> Agent Activity</h2>
        <div class="export-bar">
          <button class="btn-icon" onclick="window.FC&&FC.exportActivityLog()" title="Export Activity"><i class="fas fa-download"></i> CSV</button>
          <span class="badge badge-count" id="activityCount">0 events</span>
        </div>
      </div>
      <div class="activity-feed" id="activityFeed">
        <!-- Populated by JS -->
      </div>
    </div>

    <!-- Fleet Topology -->
    <div class="card">
      <div class="card-header">
        <h2><i class="fas fa-project-diagram"></i> Fleet Topology</h2>
        <span class="badge badge-count" id="topoCount">0 nodes</span>
      </div>
      <div class="topology-grid" id="topologyGrid">
        <!-- Populated by JS -->
      </div>
      <div class="topology-legend">
        <div class="legend-item"><span class="legend-dot active"></span> Active</div>
        <div class="legend-item"><span class="legend-dot thinking"></span> Thinking</div>
        <div class="legend-item"><span class="legend-dot completed"></span> Completed</div>
        <div class="legend-item"><span class="legend-dot error"></span> Error</div>
        <div class="legend-item"><span class="legend-dot idle"></span> Idle</div>
      </div>
    </div>

  </div><!-- /fleet-grid -->

  <!-- Fleet Capabilities -->
  <div class="card fleet-full" style="margin-top:1.5rem">
    <div class="card-header">
      <h2><i class="fas fa-th"></i> Fleet Capabilities — 842 Tools</h2>
      <span class="badge badge-live"><i class="fas fa-bolt" style="font-size:.45rem;vertical-align:middle;margin-right:.3rem"></i> v18.0</span>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1rem;margin-top:.5rem">
      <div style="background:var(--bg-secondary);border-radius:var(--radius-sm);padding:1rem;border-left:3px solid var(--accent)">
        <div style="font-weight:700;font-size:.9rem;margin-bottom:.4rem"><i class="fas fa-network-wired" style="color:var(--accent);margin-right:.4rem"></i>Fleet Orchestration</div>
        <div style="color:var(--text-secondary);font-size:.78rem">Create swarms, assign missions, broadcast commands, topology mapping, performance reports</div>
      </div>
      <div style="background:var(--bg-secondary);border-radius:var(--radius-sm);padding:1rem;border-left:3px solid var(--green)">
        <div style="font-weight:700;font-size:.9rem;margin-bottom:.4rem"><i class="fas fa-brain" style="color:var(--green);margin-right:.4rem"></i>Consciousness Engine</div>
        <div style="color:var(--text-secondary);font-size:.78rem">Personality evolution, memory consolidation, emotional states, dream synthesis, metacognition</div>
      </div>
      <div style="background:var(--bg-secondary);border-radius:var(--radius-sm);padding:1rem;border-left:3px solid var(--blue)">
        <div style="font-weight:700;font-size:.9rem;margin-bottom:.4rem"><i class="fas fa-shield-alt" style="color:var(--blue);margin-right:.4rem"></i>Security Operations</div>
        <div style="color:var(--text-secondary);font-size:.78rem">Threat detection, vulnerability scanning, incident response, audit logging, compliance</div>
      </div>
      <div style="background:var(--bg-secondary);border-radius:var(--radius-sm);padding:1rem;border-left:3px solid var(--cyan)">
        <div style="font-weight:700;font-size:.9rem;margin-bottom:.4rem"><i class="fas fa-chart-line" style="color:var(--cyan);margin-right:.4rem"></i>Financial Command</div>
        <div style="color:var(--text-secondary);font-size:.78rem">Revenue forecasting, expense tracking, invoicing, payment reconciliation, MRR/ARR analytics</div>
      </div>
      <div style="background:var(--bg-secondary);border-radius:var(--radius-sm);padding:1rem;border-left:3px solid var(--yellow)">
        <div style="font-weight:700;font-size:.9rem;margin-bottom:.4rem"><i class="fas fa-microphone-alt" style="color:var(--yellow);margin-right:.4rem"></i>Voice Pipeline v3</div>
        <div style="color:var(--text-secondary);font-size:.78rem">Voice cloning, custom wake words, multi-language TTS, noise cancellation, voice transform</div>
      </div>
      <div style="background:var(--bg-secondary);border-radius:var(--radius-sm);padding:1rem;border-left:3px solid var(--red)">
        <div style="font-weight:700;font-size:.9rem;margin-bottom:.4rem"><i class="fas fa-code" style="color:var(--red);margin-right:.4rem"></i>Developer Tools</div>
        <div style="color:var(--text-secondary);font-size:.78rem">API key management, webhook config, SDK generation, rate limits, sandbox environments</div>
      </div>
      <div style="background:var(--bg-secondary);border-radius:var(--radius-sm);padding:1rem;border-left:3px solid var(--accent-light)">
        <div style="font-weight:700;font-size:.9rem;margin-bottom:.4rem"><i class="fas fa-server" style="color:var(--accent-light);margin-right:.4rem"></i>Server Operations</div>
        <div style="color:var(--text-secondary);font-size:.78rem">Provisioning, monitoring, log analysis, process management, cron, auto-scaling</div>
      </div>
      <div style="background:var(--bg-secondary);border-radius:var(--radius-sm);padding:1rem;border-left:3px solid var(--green)">
        <div style="font-weight:700;font-size:.9rem;margin-bottom:.4rem"><i class="fas fa-building" style="color:var(--green);margin-right:.4rem"></i>Enterprise Admin</div>
        <div style="color:var(--text-secondary);font-size:.78rem">Organization management, SSO, usage quotas, audit trails, compliance, white-label branding</div>
      </div>
    </div>
  </div>

  </div><!-- /tab: Command Center -->

  <!-- Tab: Fleet History (v2.0) -->
  <div id="ftab-history" class="fleet-tab-panel">
    <div class="card">
      <div class="card-header">
        <h2><i class="fas fa-history"></i> Fleet Mission History</h2>
        <div class="export-bar">
          <button class="btn-icon" onclick="window.FC&&FC.exportFleetCSV()" title="Export History"><i class="fas fa-file-csv"></i> Export</button>
          <button class="btn-icon" onclick="window.FC&&FC.refresh()" title="Refresh"><i class="fas fa-sync-alt"></i></button>
        </div>
      </div>
      <div style="overflow-x:auto">
        <table class="history-table">
          <thead>
            <tr><th>Fleet Name</th><th>Objective</th><th>Agents</th><th>Strategy</th><th>Status</th><th>Completed</th></tr>
          </thead>
          <tbody id="historyTable">
            <tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-muted);">Switch to this tab to load history</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div><!-- /tab: History -->

  <!-- Tab: Performance (v2.0) -->
  <div id="ftab-performance" class="fleet-tab-panel">
    <div class="fleet-grid">
      <div class="card">
        <div class="card-header">
          <h2><i class="fas fa-tachometer-alt"></i> System Performance</h2>
          <span class="badge badge-live"><i class="fas fa-circle" style="font-size:.45rem;vertical-align:middle;margin-right:.3rem"></i> REAL-TIME</span>
        </div>
        <div class="perf-metrics" id="perfMetrics">
          <div style="text-align:center;padding:2rem;color:var(--text-muted);font-size:.85rem;">Loading performance data...</div>
        </div>
      </div>
      <div class="card">
        <div class="card-header">
          <h2><i class="fas fa-fire"></i> Agent Activity Heatmap</h2>
          <span class="badge badge-count">7-day view</span>
        </div>
        <div class="heatmap-wrap">
          <canvas id="heatmapCanvas"></canvas>
        </div>
        <div class="topology-legend" style="margin-top:.6rem">
          <div class="legend-item"><span class="legend-dot" style="background:#3d2b9e"></span> Low</div>
          <div class="legend-item"><span class="legend-dot" style="background:var(--accent)"></span> Medium</div>
          <div class="legend-item"><span class="legend-dot" style="background:var(--green)"></span> High</div>
        </div>
      </div>
    </div>
  </div><!-- /tab: Performance -->

  <!-- Tab: Fleet Overview (v3.0) -->
  <div id="ftab-overview" class="fleet-tab-panel">
    <div class="card fleet-full">
      <div class="card-header">
        <h2><i class="fas fa-satellite-dish"></i> Fleet Overview — 5,000 Agents</h2>
        <div class="export-bar">
          <button class="btn-icon" onclick="FC2.refreshAll()" title="Refresh"><i class="fas fa-sync-alt"></i> Refresh</button>
          <span class="badge badge-live"><i class="fas fa-circle" style="font-size:.45rem;vertical-align:middle;margin-right:.3rem"></i> v3.0</span>
        </div>
      </div>
      <div id="v2OverviewStats">
        <div style="text-align:center;padding:2rem;color:var(--text-muted)"><i class="fas fa-spinner fa-spin"></i> Loading fleet overview...</div>
      </div>
    </div>

    <div class="card fleet-full" style="margin-top:1.5rem">
      <div class="card-header">
        <h2><i class="fas fa-th-large"></i> Domain Breakdown</h2>
        <span class="badge badge-count">10 domains</span>
      </div>
      <div id="v2DomainGrid" class="v2-domain-grid">
        <div style="text-align:center;padding:2rem;color:var(--text-muted);grid-column:1/-1"><i class="fas fa-spinner fa-spin"></i> Loading domains...</div>
      </div>
    </div>

    <div class="fleet-grid" style="margin-top:1.5rem">
      <div class="card">
        <div class="card-header">
          <h2><i class="fas fa-route"></i> Route Task</h2>
        </div>
        <div class="v2-route-panel">
          <div class="form-group">
            <label>Target Domain</label>
            <select id="v2RouteDomain" class="form-select">
              <option value="engineering">Engineering</option>
              <option value="security">Security</option>
              <option value="research">Research</option>
              <option value="finance">Finance</option>
              <option value="communications">Communications</option>
              <option value="infrastructure">Infrastructure</option>
              <option value="marketing">Marketing</option>
              <option value="analytics">Analytics</option>
              <option value="creative">Creative</option>
              <option value="robotics">Robotics</option>
            </select>
          </div>
          <div class="form-group">
            <label>&nbsp;</label>
            <button id="v2RouteBtn" class="btn btn-primary" onclick="FC2.routeTask()" style="width:100%"><i class="fas fa-route"></i> Route Task</button>
          </div>
        </div>
        <div id="v2RouteLog"></div>
      </div>
      <div class="card">
        <div class="card-header">
          <h2><i class="fas fa-chart-line"></i> Agent Metrics</h2>
          <button class="btn-icon" onclick="FC2.loadV2Metrics()"><i class="fas fa-sync-alt"></i></button>
        </div>
        <div id="v2MetricsContent">
          <div style="text-align:center;padding:2rem;color:var(--text-muted)">Switch to this tab to load metrics</div>
        </div>
      </div>
    </div>
  </div><!-- /tab: Overview -->

  <!-- Tab: Agent Grid (v3.0) -->
  <div id="ftab-agentgrid" class="fleet-tab-panel">
    <div class="card fleet-full">
      <div class="card-header">
        <h2><i class="fas fa-th"></i> Agent Grid</h2>
        <div class="export-bar">
          <button class="btn-icon" onclick="FC2.loadGrid()" title="All Domains"><i class="fas fa-globe"></i> All</button>
          <span class="badge badge-count" id="v2GridCount">5,000 agents</span>
        </div>
      </div>
      <div style="display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:1rem">
        <button class="btn-icon" onclick="FC2.loadGrid('engineering')"><i class="fas fa-code" style="color:#6c5ce7"></i> Eng</button>
        <button class="btn-icon" onclick="FC2.loadGrid('security')"><i class="fas fa-shield-alt" style="color:#ff5252"></i> Sec</button>
        <button class="btn-icon" onclick="FC2.loadGrid('research')"><i class="fas fa-microscope" style="color:#18ffff"></i> Res</button>
        <button class="btn-icon" onclick="FC2.loadGrid('finance')"><i class="fas fa-chart-line" style="color:#ffd600"></i> Fin</button>
        <button class="btn-icon" onclick="FC2.loadGrid('communications')"><i class="fas fa-satellite-dish" style="color:#ff9800"></i> Com</button>
        <button class="btn-icon" onclick="FC2.loadGrid('infrastructure')"><i class="fas fa-server" style="color:#448aff"></i> Infra</button>
        <button class="btn-icon" onclick="FC2.loadGrid('marketing')"><i class="fas fa-bullhorn" style="color:#e040fb"></i> Mkt</button>
        <button class="btn-icon" onclick="FC2.loadGrid('analytics')"><i class="fas fa-chart-bar" style="color:#00e676"></i> Ana</button>
        <button class="btn-icon" onclick="FC2.loadGrid('creative')"><i class="fas fa-palette" style="color:#ff4081"></i> Cre</button>
        <button class="btn-icon" onclick="FC2.loadGrid('robotics')"><i class="fas fa-robot" style="color:#76ff03"></i> Rob</button>
      </div>
      <div id="v2AgentGridContent">
        <div style="text-align:center;padding:3rem;color:var(--text-muted)"><i class="fas fa-spinner fa-spin"></i> Loading agent grid...</div>
      </div>
    </div>
  </div><!-- /tab: Agent Grid -->

  <!-- Tab: Messaging (v3.0) -->
  <div id="ftab-messaging" class="fleet-tab-panel">
    <div class="card fleet-full">
      <div class="card-header">
        <h2><i class="fas fa-paper-plane"></i> Send Message</h2>
      </div>
      <div class="v2-msg-compose">
        <div class="form-group">
          <label>To Agent (leave empty for broadcast)</label>
          <input type="text" id="v2MsgTo" class="form-input" placeholder="e.g. cipher_access_control_1">
        </div>
        <div class="form-group">
          <label>Or Broadcast to Domain</label>
          <select id="v2MsgDomain" class="form-select">
            <option value="">— Select domain —</option>
            <option value="engineering">Engineering (500)</option>
            <option value="security">Security (500)</option>
            <option value="research">Research (500)</option>
            <option value="finance">Finance (500)</option>
            <option value="communications">Communications (500)</option>
            <option value="infrastructure">Infrastructure (500)</option>
            <option value="marketing">Marketing (500)</option>
            <option value="analytics">Analytics (500)</option>
            <option value="creative">Creative (500)</option>
            <option value="robotics">Robotics (500)</option>
          </select>
        </div>
        <div class="form-group">
          <label>Message Type</label>
          <select id="v2MsgType" class="form-select">
            <option value="task">Task</option>
            <option value="query">Query</option>
            <option value="coordination">Coordination</option>
            <option value="alert">Alert</option>
            <option value="status">Status</option>
          </select>
        </div>
        <div class="form-group">
          <label>&nbsp;</label>
          <button id="v2SendBtn" class="btn btn-primary" onclick="FC2.sendMessage()" style="width:100%"><i class="fas fa-paper-plane"></i> Send</button>
        </div>
        <div class="form-group v2-msg-compose-full">
          <label>Payload (JSON or plain text)</label>
          <textarea id="v2MsgPayload" class="form-textarea" rows="3" placeholder='{"instruction": "Run security scan on api/ directory"}'></textarea>
        </div>
      </div>
    </div>

    <div class="card fleet-full" style="margin-top:1.5rem">
      <div class="card-header">
        <h2><i class="fas fa-inbox"></i> Inbox</h2>
        <div class="export-bar">
          <div class="form-group" style="margin:0;min-width:180px">
            <input type="text" id="v2InboxAgent" class="form-input" value="alfred" placeholder="Agent ID" style="padding:.4rem .7rem;font-size:.8rem">
          </div>
          <button class="btn-icon" onclick="FC2.loadInbox(document.getElementById('v2InboxAgent').value)" title="Load Inbox"><i class="fas fa-sync-alt"></i> Load</button>
        </div>
      </div>
      <div id="v2InboxContent">
        <div class="v2-empty">Enter an agent ID and click Load to view their inbox</div>
      </div>
    </div>
  </div><!-- /tab: Messaging -->

</div><!-- /fleet-container -->

<!-- Footer -->
<footer class="fleet-footer">
  &copy; <?php echo date('Y'); ?> <a href="/">GoSiteMe</a> &mdash; Alfred Fleet Command &middot; <a href="/alfred.php">Alfred AI</a> &middot; <a href="/privacy-policy.php">Privacy</a> &middot; <a href="/terms-of-service.php">Terms</a>
</footer>

<script>
const FLEET_TEMPLATES = {
  research: { objective: 'Research and compile a comprehensive report on the specified topic. Cross-reference multiple sources, verify claims, and produce a structured analysis with citations.', strategy: 'adaptive', agents: 5 },
  content: { objective: 'Generate a complete content package: blog post, social media captions, email newsletter copy, and SEO metadata. Maintain consistent voice and messaging.', strategy: 'parallel', agents: 10 },
  security: { objective: 'Perform a full security audit: scan for vulnerabilities (XSS, SQLi, CSRF, auth bypass), review access controls, check dependencies, and produce a prioritized remediation report.', strategy: 'sequential', agents: 8 },
  data: { objective: 'Analyze the provided dataset: clean and normalize data, identify patterns and outliers, generate statistical summaries, and produce visualizable insights.', strategy: 'parallel', agents: 12 },
  support: { objective: 'Process support tickets: categorize issues, draft responses, escalate critical items, update knowledge base, and track resolution metrics.', strategy: 'adaptive', agents: 6 },
  health: { objective: 'Research health topic across genetics, nutrition, longevity, natural compounds, and clinical evidence. Cross-reference PubMed, compile findings with evidence grades.', strategy: 'parallel', agents: 15 }
};
function applyTemplate(key) {
  const t = FLEET_TEMPLATES[key];
  if (!t) return;
  const obj = document.getElementById('fleetObjective');
  const strat = document.getElementById('fleetStrategy');
  const count = document.getElementById('agentCount');
  const label = document.getElementById('agentCountLabel');
  if (obj) obj.value = t.objective;
  if (strat) strat.value = t.strategy;
  if (count) { count.value = t.agents; if (label) label.textContent = t.agents; }
}
</script>
<script src="/assets/js/fleet-command.js"></script>
<script src="/assets/js/fleet-command-v2.js"></script>

<!-- Schema.org: SoftwareApplication -->
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"SoftwareApplication","name":"Alfred Fleet Command","applicationCategory":"BusinessApplication","operatingSystem":"Web","url":"https://root.com/fleet-dashboard.php","description":"Launch and manage AI agent fleets with Alfred Fleet Command. Real-time swarm orchestration, parallel task execution, and intelligent agent coordination.","featureList":"5000 Agent Fleet, 10 Domain Orchestration, Agent Messaging Bus, Smart Task Routing, Session Management, Performance Metrics, Real-Time Monitoring, Fleet Analytics, CSV Export","offers":{"@type":"Offer","price":"0","priceCurrency":"USD","description":"Included with GoSiteMe hosting plans"},"creator":{"@type":"Organization","name":"GoSiteMe","url":"https://root.com"}}
</script>
<!-- Schema.org: WebPage -->
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"WebPage","name":"Alfred Fleet Command — AI Swarm Orchestration","description":"Launch and manage AI agent fleets with real-time swarm orchestration, parallel task execution, and intelligent agent coordination.","url":"https://root.com/fleet-dashboard.php","isPartOf":{"@type":"WebSite","name":"GoSiteMe","url":"https://root.com"}}
</script>
<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
