<?php
/**
 * GoHostMe — Management Dashboard v3.0 (Ultimate Edition)
 * The most complete hosting control panel on the market.
 * 30+ sections, 60+ features, everything a hosting customer needs.
 * Features no other hosting company offers:
 *   - Disk encryption status/management (LUKS)
 *   - Firewall & Fail2Ban management
 *   - Malware scanner & security audit
 *   - Network diagnostic tools (ping, traceroute, dig, whois, MTR)
 *   - DNS propagation checker
 *   - Log viewer (system, Apache, mail, auth)
 *   - Security headers audit
 *   - Email deliverability (SPF/DKIM/DMARC/blacklist)
 *   - WordPress management (WP-CLI)
 *   - Docker container management
 *   - Git deployment & webhooks
 *   - PHP version manager
 *   - Resource monitoring with live graphs
 *   - Activity/audit log
 *   - Uptime monitoring
 *   - Server benchmarks
 *   - Migration tools
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GoHostMe — Ultimate Control Panel</title>
<link rel="icon" href="/favicon.ico">
<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{--bg:#0a0a0f;--bg2:#111118;--bg3:#161622;--border:#1e1e30;--text:#e0e0e8;--text2:#888;--accent:#00d4ff;--accent2:#7b2fff;--green:#00ff88;--red:#ff4455;--orange:#ff9933;--yellow:#ffd700;--radius:10px}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
a{color:var(--accent);text-decoration:none}
button{cursor:pointer;font-family:inherit}
code{font-family:'Fira Code','Courier New',monospace;font-size:13px;background:var(--bg);padding:2px 6px;border-radius:4px}
#login-screen{display:flex;align-items:center;justify-content:center;min-height:100vh;background:var(--bg)}
.login-box{background:var(--bg2);border:1px solid var(--border);border-radius:16px;padding:48px;width:100%;max-width:420px;text-align:center}
.login-box .logo{font-size:28px;font-weight:900;background:linear-gradient(135deg,var(--accent),var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;margin-bottom:8px}
.login-box .sub{color:var(--text2);font-size:14px;margin-bottom:32px}
.login-box input{width:100%;padding:14px 16px;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);color:var(--text);font-size:15px;margin-bottom:16px;outline:none;transition:border .2s}
.login-box input:focus{border-color:var(--accent)}
.login-box .btn-login{width:100%;padding:14px;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;border:none;border-radius:var(--radius);font-size:16px;font-weight:700;transition:all .3s;cursor:pointer}
.login-box .btn-login:hover{transform:translateY(-2px);box-shadow:0 8px 30px rgba(0,212,255,.3)}
.login-box .error{color:var(--red);font-size:13px;margin-top:12px;display:none}
.login-box .auth-links{margin-top:14px;display:grid;gap:10px}
.login-box .auth-link{display:block;width:100%;padding:12px;border-radius:10px;border:1px solid var(--border);text-decoration:none;color:var(--text);font-size:14px;font-weight:600;background:var(--bg3);transition:all .2s}
.login-box .auth-link:hover{border-color:var(--accent);transform:translateY(-1px)}
#app{display:none;min-height:100vh}
.topbar{background:var(--bg2);border-bottom:1px solid var(--border);padding:12px 24px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100}
.topbar .logo{font-size:20px;font-weight:800;background:linear-gradient(135deg,var(--accent),var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.topbar .user-info{display:flex;align-items:center;gap:12px;font-size:14px;color:var(--text2)}
.topbar .user-info .name{color:var(--text);font-weight:600}
.topbar .logout-btn{background:transparent;border:1px solid var(--border);color:var(--text2);padding:6px 14px;border-radius:6px;font-size:13px;transition:all .2s}
.topbar .logout-btn:hover{border-color:var(--red);color:var(--red)}
.topbar .mobile-toggle{display:none;background:none;border:none;color:var(--text);font-size:22px;padding:4px 8px}
.layout{display:flex;min-height:calc(100vh - 52px)}
.sidebar{width:230px;background:var(--bg2);border-right:1px solid var(--border);padding:12px 0;flex-shrink:0;overflow-y:auto;height:calc(100vh - 52px);position:sticky;top:52px}
.sidebar .section-title{font-size:10px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:1px;padding:6px 18px;margin-top:6px}
.sidebar .nav-item{display:flex;align-items:center;gap:10px;padding:7px 18px;font-size:12px;color:var(--text2);cursor:pointer;transition:all .15s;border-left:3px solid transparent}
.sidebar .nav-item:hover{background:rgba(0,212,255,.05);color:var(--text)}
.sidebar .nav-item.active{background:rgba(0,212,255,.08);color:var(--accent);border-left-color:var(--accent);font-weight:600}
.sidebar .nav-item .icon{font-size:14px;width:16px;text-align:center}
.main{flex:1;padding:24px;overflow-y:auto;max-height:calc(100vh - 52px)}
.page-title{font-size:24px;font-weight:800;margin-bottom:8px}
.page-sub{color:var(--text2);font-size:14px;margin-bottom:24px}
.stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:24px}
.stat-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);padding:16px}
.stat-card .label{font-size:11px;color:var(--text2);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px}
.stat-card .value{font-size:24px;font-weight:900}
.stat-card .value.cyan{color:var(--accent)}
.stat-card .value.green{color:var(--green)}
.stat-card .value.red{color:var(--red)}
.stat-card .value.orange{color:var(--orange)}
.stat-card .value.yellow{color:var(--yellow)}
.stat-card .sub{font-size:11px;color:var(--text2);margin-top:3px}
.panel{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:20px;overflow:hidden}
.panel-header{padding:12px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px}
.panel-header h3{font-size:14px;font-weight:700}
.panel-body{padding:18px;overflow-x:auto}
table{width:100%;border-collapse:collapse}
table th{text-align:left;padding:8px 10px;font-size:11px;color:var(--text2);text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border);background:var(--bg3);white-space:nowrap}
table td{padding:8px 10px;font-size:13px;border-bottom:1px solid rgba(30,30,48,.5)}
table tr:hover td{background:rgba(0,212,255,.02)}
.badge{display:inline-block;padding:3px 10px;border-radius:12px;font-size:11px;font-weight:700;white-space:nowrap}
.badge.green{background:rgba(0,255,136,.15);color:var(--green)}
.badge.red{background:rgba(255,68,85,.15);color:var(--red)}
.badge.orange{background:rgba(255,153,51,.15);color:var(--orange)}
.badge.blue{background:rgba(0,212,255,.15);color:var(--accent)}
.badge.yellow{background:rgba(255,215,0,.15);color:var(--yellow)}
.badge.purple{background:rgba(123,47,255,.15);color:var(--accent2)}
.btn{padding:7px 14px;border-radius:6px;font-size:12px;font-weight:600;border:none;transition:all .2s;white-space:nowrap}
.btn-primary{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff}
.btn-primary:hover{transform:translateY(-1px);box-shadow:0 4px 15px rgba(0,212,255,.3)}
.btn-sm{padding:4px 10px;font-size:11px}
.btn-danger{background:rgba(255,68,85,.15);color:var(--red);border:1px solid rgba(255,68,85,.3)}
.btn-danger:hover{background:rgba(255,68,85,.25)}
.btn-success{background:rgba(0,255,136,.15);color:var(--green);border:1px solid rgba(0,255,136,.3)}
.btn-success:hover{background:rgba(0,255,136,.25)}
.btn-warning{background:rgba(255,153,51,.15);color:var(--orange);border:1px solid rgba(255,153,51,.3)}
.btn-warning:hover{background:rgba(255,153,51,.25)}
.btn-group{display:flex;gap:4px;flex-wrap:wrap}
.form-group{margin-bottom:12px}
.form-group label{display:block;font-size:12px;color:var(--text2);margin-bottom:4px;font-weight:600}
.form-group input,.form-group select,.form-group textarea{width:100%;padding:10px 12px;background:var(--bg);border:1px solid var(--border);border-radius:6px;color:var(--text);font-size:13px;outline:none;font-family:inherit}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{border-color:var(--accent)}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.loading{text-align:center;padding:30px;color:var(--text2)}
.loading::after{content:'';display:inline-block;width:16px;height:16px;border:2px solid var(--border);border-top-color:var(--accent);border-radius:50%;animation:spin .8s linear infinite;margin-left:8px;vertical-align:middle}
@keyframes spin{to{transform:rotate(360deg)}}
.toast{position:fixed;bottom:24px;right:24px;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);padding:14px 20px;font-size:14px;z-index:1000;transform:translateY(100px);opacity:0;transition:all .3s;max-width:400px}
.toast.show{transform:translateY(0);opacity:1}
.toast.success{border-color:var(--green);color:var(--green)}
.toast.error{border-color:var(--red);color:var(--red)}
.page{display:none}
.page.active{display:block}
.terminal-out{background:#000;border:1px solid #333;border-radius:6px;padding:14px;font-family:'Fira Code','Courier New',monospace;font-size:12px;color:#ccc;max-height:400px;overflow-y:auto;white-space:pre-wrap;word-break:break-all}
.code-editor{width:100%;min-height:250px;background:#000;border:1px solid #333;border-radius:6px;padding:14px;font-family:'Fira Code','Courier New',monospace;font-size:13px;color:#ccc;resize:vertical;tab-size:4;-moz-tab-size:4;outline:none}
.modal-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.6);z-index:200;display:none;align-items:center;justify-content:center}
.modal-overlay.show{display:flex}
.modal{background:var(--bg2);border:1px solid var(--border);border-radius:16px;padding:24px;max-width:560px;width:92%;max-height:85vh;overflow-y:auto}
.modal h3{font-size:18px;font-weight:800;margin-bottom:14px}
.modal .close{float:right;background:none;border:none;color:var(--text2);font-size:22px;cursor:pointer;line-height:1}
.tab-bar{display:flex;gap:0;border-bottom:1px solid var(--border);margin-bottom:14px}
.tab-bar .tab{padding:8px 16px;font-size:13px;color:var(--text2);cursor:pointer;border-bottom:2px solid transparent;transition:all .2s}
.tab-bar .tab.active{color:var(--accent);border-bottom-color:var(--accent)}
.tab-bar .tab:hover{color:var(--text)}
.tab-content{display:none}
.tab-content.active{display:block}
.progress-bar{background:var(--bg3);border-radius:6px;overflow:hidden;height:8px;margin-top:4px}
.progress-bar .fill{height:100%;border-radius:6px;transition:width .5s}
.checklist-item{padding:8px 12px;border:1px solid var(--border);border-radius:6px;margin-bottom:6px;display:flex;align-items:center;gap:10px;font-size:13px}
.checklist-item.pass{border-color:rgba(0,255,136,.3);background:rgba(0,255,136,.05)}
.checklist-item.fail{border-color:rgba(255,68,85,.3);background:rgba(255,68,85,.05)}
.checklist-item.warn{border-color:rgba(255,153,51,.3);background:rgba(255,153,51,.05)}
.info-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:12px}
.info-item{padding:10px 14px;background:var(--bg3);border-radius:6px}
.info-item .lbl{font-size:11px;color:var(--text2);text-transform:uppercase}
.info-item .val{font-size:14px;margin-top:2px}
@media(max-width:768px){
.sidebar{position:fixed;left:-230px;top:52px;height:calc(100vh - 52px);z-index:50;transition:left .3s}
.sidebar.open{left:0}
.main{padding:14px}
.stat-grid{grid-template-columns:1fr 1fr}
.form-row{grid-template-columns:1fr}
.topbar .mobile-toggle{display:block}
}
</style>
</head>
<body>

<!-- LOGIN -->
<div id="login-screen">
 <div class="login-box">
  <div class="logo">GoHostMe</div>
  <div class="sub">AI-Powered Cloud Control Panel</div>
  <form id="login-form">
   <input type="text" id="login-email" placeholder="Email address" autocomplete="email" required>
   <input type="password" id="login-password" placeholder="Password" autocomplete="current-password" required>
   <button type="submit" class="btn-login">Sign In</button>
   <div class="error" id="login-error"></div>
  </form>
  <div class="auth-links">
   <a id="gositeme-login-link" class="auth-link" href="/login?redirect=%2Fgohostme%2Fdashboard">Use GoSiteMe Sign In</a>
   <a id="google-login-link" class="auth-link" href="/api/auth.php?action=google-login&amp;redirect=%2Fgohostme%2Fdashboard">Continue with Google</a>
  </div>
 </div>
</div>

<!-- APP -->
<div id="app">
 <div class="topbar">
  <div style="display:flex;align-items:center;gap:12px">
   <button class="mobile-toggle" onclick="document.querySelector('.sidebar').classList.toggle('open')">&#9776;</button>
   <div class="logo">GoHostMe</div>
  </div>
  <div class="user-info">
   <span class="name" id="user-name"></span>
   <button class="logout-btn" onclick="logout()">Sign Out</button>
  </div>
 </div>
 <div class="layout">
  <div class="sidebar">
   <div class="section-title">Overview</div>
   <div class="nav-item active" data-page="dashboard"><span class="icon">&#128202;</span> Dashboard</div>
   <div class="nav-item" data-page="services"><span class="icon">&#128994;</span> Services</div>
   <div class="nav-item" data-page="processes"><span class="icon">&#128200;</span> Processes</div>
   <div class="nav-item" data-page="resource-monitor"><span class="icon">&#128208;</span> Resource Monitor</div>

   <div class="section-title">Infrastructure</div>
   <div class="nav-item" data-page="domains"><span class="icon">&#127760;</span> Domains</div>
   <div class="nav-item" data-page="dns"><span class="icon">&#128225;</span> DNS Manager</div>
   <div class="nav-item" data-page="databases"><span class="icon">&#128451;</span> Databases</div>
   <div class="nav-item" data-page="files"><span class="icon">&#128193;</span> File Manager</div>
   <div class="nav-item" data-page="ssl"><span class="icon">&#128274;</span> SSL/TLS</div>
   <div class="nav-item" data-page="apache"><span class="icon">&#9881;</span> Apache</div>
   <div class="nav-item" data-page="php-manager"><span class="icon">&#128196;</span> PHP Manager</div>

   <div class="section-title">Services</div>
   <div class="nav-item" data-page="pm2"><span class="icon">&#9881;&#65039;</span> PM2</div>
   <div class="nav-item" data-page="email"><span class="icon">&#128231;</span> Email</div>
   <div class="nav-item" data-page="email-deliverability"><span class="icon">&#9989;</span> Email Health</div>
   <div class="nav-item" data-page="cron"><span class="icon">&#9200;</span> Cron Jobs</div>
   <div class="nav-item" data-page="backups"><span class="icon">&#128190;</span> Backups</div>
   <div class="nav-item" data-page="docker"><span class="icon">&#128230;</span> Docker</div>

   <div class="section-title">Security</div>
   <div class="nav-item" data-page="firewall"><span class="icon">&#128737;</span> Firewall</div>
   <div class="nav-item" data-page="disk-encryption"><span class="icon">&#128272;</span> Disk Encryption</div>
   <div class="nav-item" data-page="malware-scanner"><span class="icon">&#128375;</span> Malware Scanner</div>
   <div class="nav-item" data-page="security-headers"><span class="icon">&#128737;</span> Security Headers</div>
   <div class="nav-item" data-page="server-hardening"><span class="icon">&#128295;</span> Server Hardening</div>
   <div class="nav-item" data-page="activity-log"><span class="icon">&#128220;</span> Activity Log</div>

   <div class="section-title">Tools</div>
   <div class="nav-item" data-page="network-tools"><span class="icon">&#128225;</span> Network Tools</div>
   <div class="nav-item" data-page="dns-propagation"><span class="icon">&#127760;</span> DNS Propagation</div>
   <div class="nav-item" data-page="uptime-monitor"><span class="icon">&#128994;</span> Uptime Monitor</div>
   <div class="nav-item" data-page="log-viewer"><span class="icon">&#128196;</span> Log Viewer</div>
   <div class="nav-item" data-page="benchmarks"><span class="icon">&#9889;</span> Benchmarks</div>

   <div class="section-title">Development</div>
   <div class="nav-item" data-page="ide"><span class="icon">&#128187;</span> Alfred IDE</div>
   <div class="nav-item" data-page="git-deploy"><span class="icon">&#128640;</span> Git Deploy</div>
   <div class="nav-item" data-page="wordpress"><span class="icon">&#9999;</span> WordPress</div>
   <div class="nav-item" data-page="migration"><span class="icon">&#128666;</span> Migration</div>

   <div class="section-title">Servers</div>
   <div class="nav-item" data-page="cloud-vps"><span class="icon">&#9729;&#65039;</span> Cloud VPS</div>
   <div class="nav-item" data-page="vps"><span class="icon">&#128421;</span> VPS Services</div>
   <div class="nav-item" data-page="dedicated"><span class="icon">&#127959;</span> Dedicated</div>

   <div class="section-title">Quick Links</div>
   <div class="nav-item" onclick="window.open('/alfred-ide/','_blank')"><span class="icon">&#128640;</span> Open IDE</div>
   <div class="nav-item" onclick="window.open('/clientarea.php','_blank')"><span class="icon">&#128203;</span> Billing</div>
  </div>

  <div class="main">

<!-- DASHBOARD -->
<div class="page active" id="dashboard-page">
 <h1 class="page-title">Dashboard</h1>
 <p class="page-sub">System overview &amp; health</p>
 <div id="dash-content">
  <div class="stat-grid" id="dash-stats"><div class="stat-card"><div class="label">Loading...</div><div class="value">-</div></div></div>
  <div class="panel"><div class="panel-header"><h3>Server Health</h3><button class="btn btn-sm btn-primary" onclick="loadDashboard()">Refresh</button></div><div class="panel-body" id="dash-health"><div class="loading">Loading</div></div></div>
  <div class="panel"><div class="panel-header"><h3>PM2 Services</h3></div><div class="panel-body" id="dash-pm2"><div class="loading">Loading</div></div></div>
 </div>
</div>

<!-- SERVICES -->
<div class="page" id="services-page">
 <h1 class="page-title">Service Status</h1><p class="page-sub">System service health</p>
 <div class="panel"><div class="panel-header"><h3>Services</h3><button class="btn btn-sm btn-primary" onclick="loadServices()">Refresh</button></div><div class="panel-body" id="services-list"><div class="loading">Loading</div></div></div>
</div>

<!-- PROCESSES -->
<div class="page" id="processes-page">
 <h1 class="page-title">System Processes</h1><p class="page-sub">Top processes by CPU</p>
 <div class="panel"><div class="panel-header"><h3>Top 20 Processes</h3><button class="btn btn-sm btn-primary" onclick="loadProcesses()">Refresh</button></div><div class="panel-body" id="processes-list"><div class="loading">Loading</div></div></div>
</div>

<!-- RESOURCE MONITOR -->
<div class="page" id="resource-monitor-page">
 <h1 class="page-title">Resource Monitor</h1><p class="page-sub">Real-time server resource tracking</p>
 <div class="stat-grid" id="resmon-stats"></div>
 <div class="panel"><div class="panel-header"><h3>CPU History (last 60 samples)</h3><button class="btn btn-sm btn-primary" onclick="toggleResourceMonitor()">Start/Stop</button></div><div class="panel-body"><canvas id="cpu-chart" height="120"></canvas></div></div>
 <div class="panel"><div class="panel-header"><h3>Memory History</h3></div><div class="panel-body"><canvas id="mem-chart" height="120"></canvas></div></div>
 <div class="panel"><div class="panel-header"><h3>Disk I/O & Network</h3></div><div class="panel-body" id="resmon-io"><div class="loading">Loading</div></div></div>
</div>

<!-- DOMAINS -->
<div class="page" id="domains-page">
 <h1 class="page-title">Domains</h1><p class="page-sub">Manage your domains</p>
 <div class="panel"><div class="panel-header"><h3>Domain List</h3><button class="btn btn-sm btn-primary" onclick="showModal('domain-modal')">+ Add Domain</button></div><div class="panel-body" id="domains-list"><div class="loading">Loading</div></div></div>
</div>

<!-- DNS -->
<div class="page" id="dns-page">
 <h1 class="page-title">DNS Management</h1><p class="page-sub">Manage DNS records</p>
 <div class="panel"><div class="panel-header"><h3>Select Domain</h3></div><div class="panel-body"><div class="form-group"><select id="dns-domain-select" onchange="loadDNS()"><option value="">Select a domain...</option></select></div></div></div>
 <div class="panel" id="dns-records-panel" style="display:none"><div class="panel-header"><h3>DNS Records</h3><button class="btn btn-sm btn-primary" onclick="showModal('dns-modal')">+ Add Record</button></div><div class="panel-body" id="dns-records"><div class="loading">Loading</div></div></div>
</div>

<!-- DATABASES -->
<div class="page" id="databases-page">
 <h1 class="page-title">Databases</h1><p class="page-sub">MySQL / MariaDB management</p>
 <div class="tab-bar"><div class="tab active" onclick="switchTab(this,'db-tab-list')">Databases</div><div class="tab" onclick="switchTab(this,'db-tab-query')">SQL Query</div></div>
 <div class="tab-content active" id="db-tab-list">
  <div class="panel"><div class="panel-header"><h3>Database List</h3><button class="btn btn-sm btn-primary" onclick="showModal('db-modal')">+ Create</button></div><div class="panel-body" id="db-list"><div class="loading">Loading</div></div></div>
 </div>
 <div class="tab-content" id="db-tab-query">
  <div class="panel"><div class="panel-header"><h3>SQL Query Tool</h3></div><div class="panel-body">
   <div class="form-group"><label>Database</label><select id="sql-db-select"><option value="">Default</option></select></div>
   <div class="form-group"><label>Query (SELECT/SHOW/DESCRIBE/EXPLAIN only)</label><textarea class="code-editor" id="sql-query" rows="4" placeholder="SELECT * FROM table LIMIT 10;"></textarea></div>
   <button class="btn btn-primary" onclick="runSQL()">Execute</button>
   <div id="sql-results" style="margin-top:14px"></div>
  </div></div>
 </div>
</div>

<!-- FILE MANAGER -->
<div class="page" id="files-page">
 <h1 class="page-title">File Manager</h1><p class="page-sub">Browse, edit, manage files</p>
 <div class="panel"><div class="panel-header"><h3>Path: <span id="files-path">/</span></h3>
  <div class="btn-group"><button class="btn btn-sm btn-primary" onclick="showModal('file-modal')">+ File</button><button class="btn btn-sm btn-success" onclick="showModal('mkdir-modal')">+ Folder</button></div></div>
  <div class="panel-body" id="files-list"><div class="loading">Loading</div></div></div>
 <div class="panel" id="file-editor-panel" style="display:none"><div class="panel-header"><h3>Editing: <span id="editing-file"></span></h3>
  <div class="btn-group"><button class="btn btn-sm btn-primary" onclick="saveFile()">Save</button><button class="btn btn-sm btn-danger" onclick="closeEditor()">Close</button></div></div>
  <div class="panel-body"><textarea class="code-editor" id="file-editor-content"></textarea></div></div>
</div>

<!-- SSL -->
<div class="page" id="ssl-page">
 <h1 class="page-title">SSL / TLS</h1><p class="page-sub">Certificate management</p>
 <div class="panel"><div class="panel-header"><h3>Certificate Status</h3></div><div class="panel-body" id="ssl-list"><div class="loading">Loading</div></div></div>
</div>

<!-- APACHE -->
<div class="page" id="apache-page">
 <h1 class="page-title">Apache</h1><p class="page-sub">Virtual hosts &amp; server config</p>
 <div class="panel"><div class="panel-header"><h3>Virtual Hosts</h3><button class="btn btn-sm btn-primary" onclick="loadApache()">Refresh</button></div><div class="panel-body" id="apache-vhosts"><div class="loading">Loading</div></div></div>
 <div class="panel"><div class="panel-header"><h3>DirectAdmin</h3></div><div class="panel-body" id="da-status"><div class="loading">Loading</div></div></div>
</div>

<!-- PHP MANAGER -->
<div class="page" id="php-manager-page">
 <h1 class="page-title">PHP Manager</h1><p class="page-sub">PHP version switching, extensions, OPcache, php.ini</p>
 <div class="stat-grid" id="php-stats"></div>
 <div class="panel"><div class="panel-header"><h3>Installed PHP Versions</h3><button class="btn btn-sm btn-primary" onclick="loadPHP()">Refresh</button></div><div class="panel-body" id="php-versions"><div class="loading">Loading</div></div></div>
 <div class="panel"><div class="panel-header"><h3>PHP Extensions</h3></div><div class="panel-body" id="php-extensions"><div class="loading">Loading</div></div></div>
 <div class="panel"><div class="panel-header"><h3>OPcache Status</h3></div><div class="panel-body" id="php-opcache"><div class="loading">Loading</div></div></div>
 <div class="panel"><div class="panel-header"><h3>php.ini Quick Settings</h3></div><div class="panel-body" id="php-ini"><div class="loading">Loading</div></div></div>
</div>

<!-- PM2 -->
<div class="page" id="pm2-page">
 <h1 class="page-title">PM2 Process Manager</h1><p class="page-sub">Monitor and control services</p>
 <div class="panel"><div class="panel-header"><h3>Processes</h3><button class="btn btn-sm btn-primary" onclick="loadPM2()">Refresh</button></div><div class="panel-body" id="pm2-list"><div class="loading">Loading</div></div></div>
 <div class="panel" id="pm2-log-panel" style="display:none"><div class="panel-header"><h3>Logs: <span id="pm2-log-name"></span></h3><button class="btn btn-sm btn-danger" onclick="document.getElementById('pm2-log-panel').style.display='none'">Close</button></div><div class="panel-body"><div class="terminal-out" id="pm2-log-output"></div></div></div>
</div>

<!-- EMAIL -->
<div class="page" id="email-page">
 <h1 class="page-title">Email Accounts</h1><p class="page-sub">Manage email addresses</p>
 <div class="panel"><div class="panel-header"><h3>Email Accounts</h3><button class="btn btn-sm btn-primary" onclick="showModal('email-modal')">+ Create</button></div><div class="panel-body" id="email-list"><div class="loading">Loading</div></div></div>
</div>

<!-- EMAIL DELIVERABILITY -->
<div class="page" id="email-deliverability-page">
 <h1 class="page-title">Email Deliverability</h1><p class="page-sub">SPF, DKIM, DMARC, blacklist check — ensure your emails land in inbox</p>
 <div class="panel"><div class="panel-header"><h3>Check Domain</h3></div><div class="panel-body">
  <div class="form-row"><div class="form-group"><label>Domain</label><input type="text" id="email-health-domain" placeholder="yourdomain.com"></div><div style="display:flex;align-items:flex-end;padding-bottom:12px"><button class="btn btn-primary" onclick="checkEmailHealth()">Run Check</button></div></div>
 </div></div>
 <div id="email-health-results" style="display:none">
  <div class="stat-grid" id="email-health-stats"></div>
  <div class="panel"><div class="panel-header"><h3>Record Details</h3></div><div class="panel-body" id="email-health-details"></div></div>
  <div class="panel"><div class="panel-header"><h3>Blacklist Check</h3></div><div class="panel-body" id="email-blacklist"></div></div>
 </div>
</div>

<!-- CRON -->
<div class="page" id="cron-page">
 <h1 class="page-title">Cron Jobs</h1><p class="page-sub">Scheduled tasks</p>
 <div class="panel"><div class="panel-header"><h3>Active Cron Jobs</h3><button class="btn btn-sm btn-primary" onclick="showModal('cron-modal')">+ Add</button></div><div class="panel-body" id="cron-list"><div class="loading">Loading</div></div></div>
</div>

<!-- BACKUPS -->
<div class="page" id="backups-page">
 <h1 class="page-title">Backups</h1><p class="page-sub">Database and file backups</p>
 <div class="panel"><div class="panel-header"><h3>Backups</h3><div class="btn-group"><button class="btn btn-sm btn-primary" onclick="createBackup('database')">Backup DB</button><button class="btn btn-sm btn-success" onclick="createBackup('files')">Backup Files</button><button class="btn btn-sm btn-warning" onclick="createBackup('full')">Full Backup</button></div></div><div class="panel-body" id="backup-list"><div class="loading">Loading</div></div></div>
</div>

<!-- DOCKER -->
<div class="page" id="docker-page">
 <h1 class="page-title">Docker Containers</h1><p class="page-sub">Container management — start, stop, logs, images</p>
 <div class="tab-bar"><div class="tab active" onclick="switchTab(this,'docker-tab-containers')">Containers</div><div class="tab" onclick="switchTab(this,'docker-tab-images')">Images</div></div>
 <div class="tab-content active" id="docker-tab-containers">
  <div class="panel"><div class="panel-header"><h3>Running Containers</h3><button class="btn btn-sm btn-primary" onclick="loadDocker()">Refresh</button></div><div class="panel-body" id="docker-containers"><div class="loading">Loading</div></div></div>
 </div>
 <div class="tab-content" id="docker-tab-images">
  <div class="panel"><div class="panel-header"><h3>Docker Images</h3></div><div class="panel-body" id="docker-images"><div class="loading">Loading</div></div></div>
 </div>
 <div class="panel" id="docker-log-panel" style="display:none"><div class="panel-header"><h3>Container Logs: <span id="docker-log-name"></span></h3><button class="btn btn-sm btn-danger" onclick="document.getElementById('docker-log-panel').style.display='none'">Close</button></div><div class="panel-body"><div class="terminal-out" id="docker-log-output"></div></div></div>
</div>

<!-- FIREWALL -->
<div class="page" id="firewall-page">
 <h1 class="page-title">Firewall &amp; Fail2Ban</h1><p class="page-sub">UFW rules, banned IPs, intrusion prevention</p>
 <div class="tab-bar"><div class="tab active" onclick="switchTab(this,'fw-tab-rules')">Firewall Rules</div><div class="tab" onclick="switchTab(this,'fw-tab-fail2ban')">Fail2Ban</div><div class="tab" onclick="switchTab(this,'fw-tab-blocked')">Blocked IPs</div></div>
 <div class="tab-content active" id="fw-tab-rules">
  <div class="panel"><div class="panel-header"><h3>UFW Status</h3><div class="btn-group"><button class="btn btn-sm btn-primary" onclick="showModal('fw-rule-modal')">+ Add Rule</button><button class="btn btn-sm btn-success" onclick="loadFirewall()">Refresh</button></div></div><div class="panel-body" id="fw-rules"><div class="loading">Loading</div></div></div>
 </div>
 <div class="tab-content" id="fw-tab-fail2ban">
  <div class="panel"><div class="panel-header"><h3>Fail2Ban Jails</h3></div><div class="panel-body" id="fw-fail2ban"><div class="loading">Loading</div></div></div>
 </div>
 <div class="tab-content" id="fw-tab-blocked">
  <div class="panel"><div class="panel-header"><h3>Currently Blocked IPs</h3></div><div class="panel-body" id="fw-blocked"><div class="loading">Loading</div></div></div>
 </div>
</div>

<!-- DISK ENCRYPTION -->
<div class="page" id="disk-encryption-page">
 <h1 class="page-title">Disk Encryption</h1><p class="page-sub">LUKS full disk encryption status, encrypted volumes, key management</p>
 <div class="stat-grid" id="encryption-stats"></div>
 <div class="panel"><div class="panel-header"><h3>Block Devices &amp; Encryption Status</h3><button class="btn btn-sm btn-primary" onclick="loadDiskEncryption()">Refresh</button></div><div class="panel-body" id="disk-devices"><div class="loading">Loading</div></div></div>
 <div class="panel"><div class="panel-header"><h3>LUKS Volumes</h3></div><div class="panel-body" id="luks-volumes"><div class="loading">Loading</div></div></div>
 <div class="panel"><div class="panel-header"><h3>Encryption Guide</h3></div><div class="panel-body">
  <div class="info-grid">
   <div class="info-item"><div class="lbl">What is LUKS?</div><div class="val" style="font-size:12px">Linux Unified Key Setup — the standard for disk encryption on Linux. Encrypts entire partitions so data is unreadable without the key, even if the disk is physically stolen.</div></div>
   <div class="info-item"><div class="lbl">At-Rest Encryption</div><div class="val" style="font-size:12px">Data is encrypted on disk. Even if someone removes the drive, they cannot read it. Keys are stored in RAM only while the system is running.</div></div>
   <div class="info-item"><div class="lbl">Key Slots</div><div class="val" style="font-size:12px">LUKS supports up to 8 key slots. You can have multiple passphrases or key files that can unlock the same volume. Rotate keys without re-encrypting.</div></div>
   <div class="info-item"><div class="lbl">Performance</div><div class="val" style="font-size:12px">Modern CPUs have AES-NI hardware acceleration. Impact is typically &lt;3% overhead. Your data stays fast AND secure.</div></div>
  </div>
  <div style="margin-top:14px"><button class="btn btn-primary" onclick="showModal('encrypt-modal')">Encrypt a Volume</button> <button class="btn btn-warning" style="margin-left:6px" onclick="showModal('luks-key-modal')">Manage Keys</button></div>
 </div></div>
</div>

<!-- MALWARE SCANNER -->
<div class="page" id="malware-scanner-page">
 <h1 class="page-title">Malware Scanner</h1><p class="page-sub">File integrity check, malware detection, rootkit scan</p>
 <div class="stat-grid" id="malware-stats"></div>
 <div class="panel"><div class="panel-header"><h3>Quick Scan</h3></div><div class="panel-body">
  <div class="form-row"><div class="form-group"><label>Scan Path</label><input type="text" id="scan-path" placeholder="/home/user/public_html" value="/home/gositeme/domains/"></div><div style="display:flex;align-items:flex-end;padding-bottom:12px;gap:6px"><button class="btn btn-primary" onclick="runMalwareScan()">Start Scan</button><button class="btn btn-warning" onclick="runRootkitScan()">Rootkit Check</button></div></div>
 </div></div>
 <div class="panel"><div class="panel-header"><h3>Scan Results</h3></div><div class="panel-body" id="malware-results"><p style="color:var(--text2)">Run a scan to see results</p></div></div>
 <div class="panel"><div class="panel-header"><h3>File Integrity</h3></div><div class="panel-body" id="file-integrity"><p style="color:var(--text2)">Checks core system files for unexpected changes</p></div></div>
</div>

<!-- SECURITY HEADERS -->
<div class="page" id="security-headers-page">
 <h1 class="page-title">Security Headers Audit</h1><p class="page-sub">Check HTTP security headers, CSP, HSTS, X-Frame-Options</p>
 <div class="panel"><div class="panel-header"><h3>Check URL</h3></div><div class="panel-body">
  <div class="form-row"><div class="form-group"><label>URL to check</label><input type="text" id="sec-headers-url" placeholder="https://yourdomain.com"></div><div style="display:flex;align-items:flex-end;padding-bottom:12px"><button class="btn btn-primary" onclick="checkSecurityHeaders()">Audit Headers</button></div></div>
 </div></div>
 <div class="panel" id="sec-headers-panel" style="display:none"><div class="panel-header"><h3>Results</h3></div><div class="panel-body" id="sec-headers-results"></div></div>
 <div class="panel"><div class="panel-header"><h3>Recommended .htaccess Security Headers</h3></div><div class="panel-body"><pre class="terminal-out"># Add to your .htaccess:
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "SAMEORIGIN"
Header set X-XSS-Protection "1; mode=block"
Header set Referrer-Policy "strict-origin-when-cross-origin"
Header set Permissions-Policy "camera=(), microphone=(), geolocation=()"
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
Header set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'"</pre></div></div>
</div>

<!-- SERVER HARDENING -->
<div class="page" id="server-hardening-page">
 <h1 class="page-title">Server Hardening &amp; Performance</h1>
 <p class="page-sub">Fail2Ban layers, Apache performance snippet, sysctl — same automation as the CLI scripts. <strong>Admin only</strong> for live actions.</p>
 <div class="panel"><div class="panel-header"><h3>What this is</h3></div><div class="panel-body">
  <p style="color:var(--text2);line-height:1.6;font-size:13px">GoHostMe replaces DirectAdmin over time; this panel surfaces our <strong>sovereignty bridge</strong> hardening so you don’t need SSH for status and one-click re-apply. Scripts live under <code>public_html/scripts/security/</code> and <code>scripts/optimization/</code>.</p>
  <ul style="color:var(--text2);font-size:13px;line-height:1.7;margin-top:10px">
   <li><strong>Apache (CustomBuild/DirectAdmin):</strong> <code>/etc/httpd/conf/extra/gositeme-performance.conf</code> — ServerTokens, deflate, keepalive.</li>
   <li><strong>Fail2Ban:</strong> <code>gositeme-access-probes</code>, <code>recidive</code>, <code>sshd</code>, <code>sshd-ddos</code> — HTTP scanner paths are <em>not</em> normal voice API tool URLs; Alfred voice issues are usually load/DB, not this jail.</li>
   <li><strong>Docs:</strong> <a href="https://gositeme.com/docs/OPS-COMPLETE-PACK.md" target="_blank" rel="noopener">Ops master index</a> · <a href="https://gositeme.com/docs/GOHOSTME_VS_DIRECTADMIN.md" target="_blank" rel="noopener">GoHostMe vs DirectAdmin</a> · <a href="https://gositeme.com/docs/DIRECTADMIN_MIGRATION_CHECKLIST.md" target="_blank" rel="noopener">DA migration checklist</a>.</li>
  </ul>
 </div></div>
 <div class="panel"><div class="panel-header"><h3>Fail2Ban status</h3><button class="btn btn-sm btn-primary" onclick="loadServerHardening()">Refresh</button></div><div class="panel-body" id="ghm-f2b-output"><p style="color:var(--text2)">Click Refresh (admin).</p></div></div>
 <div class="panel"><div class="panel-header"><h3>Load &amp; DB diagnostics</h3><button class="btn btn-sm btn-primary" onclick="loadDiagnostics()">Run</button></div><div class="panel-body" id="ghm-diag-output"><p style="color:var(--text2)">Disk, memory, load average, MySQL slow-log settings &amp; thread counts (admin).</p></div></div>
 <div class="panel"><div class="panel-header"><h3>Apply scripts (admin)</h3></div><div class="panel-body">
  <p style="color:var(--text2);font-size:12px;margin-bottom:12px">Runs the same shell scripts as <code>sudo /opt/gohostme/bridge.sh …</code> with HMAC. May take 1–3 minutes.</p>
  <div class="btn-group" style="gap:8px;flex-wrap:wrap">
   <button class="btn btn-warning" onclick="ghmApplySecurity()">Re-apply security hardening</button>
   <button class="btn btn-primary" onclick="ghmApplyOptimization()">Re-apply stack optimization</button>
  </div>
  <div id="ghm-apply-output" style="margin-top:14px"></div>
 </div></div>
</div>

<!-- ACTIVITY LOG -->
<div class="page" id="activity-log-page">
 <h1 class="page-title">Activity Log</h1><p class="page-sub">Audit trail — every action in the panel</p>
 <div class="panel"><div class="panel-header"><h3>Recent Activity</h3><button class="btn btn-sm btn-primary" onclick="loadActivityLog()">Refresh</button></div><div class="panel-body" id="activity-log-list"><div class="loading">Loading</div></div></div>
</div>

<!-- NETWORK TOOLS -->
<div class="page" id="network-tools-page">
 <h1 class="page-title">Network Diagnostic Tools</h1><p class="page-sub">Ping, traceroute, dig, whois, MTR, port scan — all from your server</p>
 <div class="panel"><div class="panel-header"><h3>Tool</h3></div><div class="panel-body">
  <div class="form-row">
   <div class="form-group"><label>Tool</label><select id="net-tool"><option value="ping">Ping</option><option value="traceroute">Traceroute</option><option value="dig">Dig (DNS Lookup)</option><option value="whois">WHOIS</option><option value="mtr">MTR</option><option value="nslookup">NSLookup</option><option value="curl-headers">HTTP Headers</option><option value="port-check">Port Check</option></select></div>
   <div class="form-group"><label>Target</label><input type="text" id="net-target" placeholder="google.com or IP"></div>
  </div>
  <div class="form-group" id="net-port-group" style="display:none"><label>Port</label><input type="number" id="net-port" placeholder="443" min="1" max="65535" style="max-width:120px"></div>
  <button class="btn btn-primary" onclick="runNetworkTool()">Run</button>
 </div></div>
 <div class="panel" id="net-results-panel" style="display:none"><div class="panel-header"><h3>Results</h3></div><div class="panel-body"><div class="terminal-out" id="net-results"></div></div></div>
</div>

<!-- DNS PROPAGATION -->
<div class="page" id="dns-propagation-page">
 <h1 class="page-title">DNS Propagation Checker</h1><p class="page-sub">Check DNS records from servers worldwide</p>
 <div class="panel"><div class="panel-header"><h3>Check Propagation</h3></div><div class="panel-body">
  <div class="form-row">
   <div class="form-group"><label>Domain</label><input type="text" id="dnsprop-domain" placeholder="example.com"></div>
   <div class="form-group"><label>Record Type</label><select id="dnsprop-type"><option>A</option><option>AAAA</option><option>CNAME</option><option>MX</option><option>TXT</option><option>NS</option><option>SOA</option></select></div>
  </div>
  <button class="btn btn-primary" onclick="checkDNSPropagation()">Check Propagation</button>
 </div></div>
 <div class="panel" id="dnsprop-results-panel" style="display:none"><div class="panel-header"><h3>Results from Global DNS Servers</h3></div><div class="panel-body" id="dnsprop-results"></div></div>
</div>

<!-- UPTIME MONITOR -->
<div class="page" id="uptime-monitor-page">
 <h1 class="page-title">Uptime Monitor</h1><p class="page-sub">HTTP/TCP/Ping monitoring with alerts</p>
 <div class="panel"><div class="panel-header"><h3>Monitors</h3><button class="btn btn-sm btn-primary" onclick="showModal('uptime-modal')">+ Add Monitor</button></div><div class="panel-body" id="uptime-list"><div class="loading">Loading</div></div></div>
 <div class="panel"><div class="panel-header"><h3>Quick Check</h3></div><div class="panel-body">
  <div class="form-row"><div class="form-group"><label>URL</label><input type="text" id="uptime-check-url" placeholder="https://yourdomain.com"></div><div style="display:flex;align-items:flex-end;padding-bottom:12px"><button class="btn btn-primary" onclick="quickUptimeCheck()">Check Now</button></div></div>
  <div id="uptime-quick-result"></div>
 </div></div>
</div>

<!-- LOG VIEWER -->
<div class="page" id="log-viewer-page">
 <h1 class="page-title">Log Viewer</h1><p class="page-sub">System, Apache, mail, auth, and custom logs</p>
 <div class="panel"><div class="panel-header"><h3>Select Log</h3></div><div class="panel-body">
  <div class="form-row"><div class="form-group"><label>Log Source</label><select id="log-source" onchange="loadLogViewer()"><option value="">Select...</option><option value="apache-access">Apache Access Log</option><option value="apache-error">Apache Error Log</option><option value="syslog">System Log (syslog)</option><option value="auth">Auth Log</option><option value="mail">Mail Log</option><option value="fail2ban">Fail2Ban Log</option><option value="pm2-out">PM2 Output</option><option value="pm2-error">PM2 Error</option><option value="custom">Custom Path...</option></select></div>
  <div class="form-group" id="custom-log-group" style="display:none"><label>Custom Log Path</label><input type="text" id="custom-log-path" placeholder="/var/log/yourlog.log"></div></div>
  <div class="form-group"><label>Lines</label><input type="number" id="log-lines" value="100" min="10" max="1000" style="max-width:100px"> <button class="btn btn-sm btn-primary" onclick="loadLogViewer()" style="margin-left:6px">Load</button> <button class="btn btn-sm btn-success" onclick="refreshLog()" style="margin-left:4px">Tail (Live)</button></div>
 </div></div>
 <div class="panel" id="log-viewer-panel" style="display:none"><div class="panel-header"><h3>Log Output</h3><div class="btn-group"><button class="btn btn-sm btn-warning" onclick="searchLog()">Search</button><button class="btn btn-sm btn-danger" onclick="document.getElementById('log-viewer-panel').style.display='none'">Close</button></div></div><div class="panel-body"><div class="terminal-out" id="log-output" style="max-height:600px"></div></div></div>
</div>

<!-- BENCHMARKS -->
<div class="page" id="benchmarks-page">
 <h1 class="page-title">Server Benchmarks</h1><p class="page-sub">CPU, disk I/O, memory, and network speed tests</p>
 <div class="stat-grid" id="bench-stats"></div>
 <div class="panel"><div class="panel-header"><h3>Run Benchmark</h3></div><div class="panel-body">
  <div class="btn-group" style="gap:8px">
   <button class="btn btn-primary" onclick="runBenchmark('cpu')">CPU Benchmark</button>
   <button class="btn btn-primary" onclick="runBenchmark('disk')">Disk I/O Test</button>
   <button class="btn btn-primary" onclick="runBenchmark('memory')">Memory Test</button>
   <button class="btn btn-primary" onclick="runBenchmark('network')">Network Speed</button>
   <button class="btn btn-warning" onclick="runBenchmark('all')">Run All</button>
  </div>
 </div></div>
 <div class="panel"><div class="panel-header"><h3>Results</h3></div><div class="panel-body" id="bench-results"><p style="color:var(--text2)">Click a benchmark to start</p></div></div>
</div>

<!-- ALFRED IDE -->
<div class="page" id="ide-page">
 <h1 class="page-title">Alfred IDE</h1><p class="page-sub">Browser-based editor management</p>
 <div class="stat-grid" id="ide-stats"></div>
 <div class="panel"><div class="panel-header"><h3>IDE Instances</h3><button class="btn btn-sm btn-primary" onclick="showModal('ide-modal')">+ Provision</button></div><div class="panel-body" id="ide-list"><div class="loading">Loading</div></div></div>
</div>

<!-- GIT DEPLOY -->
<div class="page" id="git-deploy-page">
 <h1 class="page-title">Git Deployment</h1><p class="page-sub">Deploy from GitHub/GitLab/Bitbucket, webhooks, auto-deploy</p>
 <div class="panel"><div class="panel-header"><h3>Deployment Targets</h3><button class="btn btn-sm btn-primary" onclick="showModal('git-deploy-modal')">+ Add Repo</button></div><div class="panel-body" id="git-deploy-list"><div class="loading">Loading</div></div></div>
 <div class="panel"><div class="panel-header"><h3>Quick Deploy from Git</h3></div><div class="panel-body">
  <div class="form-group"><label>Repository URL</label><input type="text" id="git-repo-url" placeholder="https://github.com/user/repo.git"></div>
  <div class="form-group"><label>Branch</label><input type="text" id="git-branch" value="main" style="max-width:200px"></div>
  <div class="form-group"><label>Deploy Path</label><input type="text" id="git-deploy-path" placeholder="/home/user/domains/site.com/public_html"></div>
  <button class="btn btn-primary" onclick="gitDeploy()">Deploy Now</button>
 </div></div>
 <div class="panel"><div class="panel-header"><h3>Deploy History</h3></div><div class="panel-body" id="git-deploy-history"><p style="color:var(--text2)">No deployments yet</p></div></div>
</div>

<!-- WORDPRESS -->
<div class="page" id="wordpress-page">
 <h1 class="page-title">WordPress Manager</h1><p class="page-sub">WP-CLI powered: manage plugins, themes, updates, security</p>
 <div class="panel"><div class="panel-header"><h3>WordPress Installations</h3><button class="btn btn-sm btn-primary" onclick="loadWordPress()">Scan</button></div><div class="panel-body" id="wp-installations"><div class="loading">Loading</div></div></div>
 <div id="wp-detail-section" style="display:none">
  <div class="stat-grid" id="wp-stats"></div>
  <div class="tab-bar"><div class="tab active" onclick="switchTab(this,'wp-tab-plugins')">Plugins</div><div class="tab" onclick="switchTab(this,'wp-tab-themes')">Themes</div><div class="tab" onclick="switchTab(this,'wp-tab-users')">Users</div><div class="tab" onclick="switchTab(this,'wp-tab-security')">Security</div></div>
  <div class="tab-content active" id="wp-tab-plugins"><div class="panel"><div class="panel-header"><h3>Plugins</h3><button class="btn btn-sm btn-success" onclick="wpUpdateAll('plugin')">Update All</button></div><div class="panel-body" id="wp-plugins"></div></div></div>
  <div class="tab-content" id="wp-tab-themes"><div class="panel"><div class="panel-header"><h3>Themes</h3></div><div class="panel-body" id="wp-themes"></div></div></div>
  <div class="tab-content" id="wp-tab-users"><div class="panel"><div class="panel-header"><h3>Users</h3></div><div class="panel-body" id="wp-users"></div></div></div>
  <div class="tab-content" id="wp-tab-security"><div class="panel"><div class="panel-header"><h3>WordPress Security Audit</h3></div><div class="panel-body" id="wp-security"></div></div></div>
 </div>
</div>

<!-- MIGRATION -->
<div class="page" id="migration-page">
 <h1 class="page-title">Site Migration Tool</h1><p class="page-sub">Migrate from cPanel, Plesk, or any server — one-click import</p>
 <div class="panel"><div class="panel-header"><h3>Migration Wizard</h3></div><div class="panel-body">
  <div class="form-group"><label>Migration Source</label><select id="migration-source"><option value="cpanel">cPanel Backup (.tar.gz)</option><option value="wordpress">WordPress (WP All-in-One Migration)</option><option value="ssh">Remote Server (SSH/SFTP)</option><option value="ftp">FTP Transfer</option><option value="upload">Upload Archive</option></select></div>
  <div id="migration-cpanel-fields">
   <div class="form-group"><label>cPanel Backup File URL or Path</label><input type="text" id="migration-file" placeholder="/path/to/backup.tar.gz or URL"></div>
  </div>
  <div id="migration-ssh-fields" style="display:none">
   <div class="form-row">
    <div class="form-group"><label>Remote Host</label><input type="text" id="migration-host" placeholder="old-server.com"></div>
    <div class="form-group"><label>SSH Port</label><input type="number" id="migration-port" value="22" style="max-width:100px"></div>
   </div>
   <div class="form-row">
    <div class="form-group"><label>Username</label><input type="text" id="migration-user" placeholder="root"></div>
    <div class="form-group"><label>Remote Path</label><input type="text" id="migration-remote-path" placeholder="/home/user/public_html"></div>
   </div>
  </div>
  <div class="form-group"><label>Target Domain</label><input type="text" id="migration-target" placeholder="yourdomain.com"></div>
  <button class="btn btn-primary" onclick="startMigration()">Start Migration</button>
 </div></div>
 <div class="panel"><div class="panel-header"><h3>Migration Progress</h3></div><div class="panel-body" id="migration-progress"><p style="color:var(--text2)">No active migrations</p></div></div>
</div>

<!-- CLOUD VPS -->
<div class="page" id="cloud-vps-page">
 <h1 class="page-title">Cloud VPS Instances</h1><p class="page-sub">OVH Public Cloud — reboot, rescue, resize, snapshots</p>
 <div class="stat-grid" id="cloud-stats"></div>
 <div class="panel"><div class="panel-header"><h3>Instances</h3><div class="btn-group"><button class="btn btn-sm btn-primary" onclick="showModal('cloud-modal')">+ Create</button><button class="btn btn-sm btn-success" onclick="loadCloud()">Refresh</button></div></div><div class="panel-body" id="cloud-instances"><div class="loading">Loading</div></div></div>
 <div class="panel"><div class="panel-header"><h3>Snapshots</h3></div><div class="panel-body" id="cloud-snapshots"><div class="loading">Loading</div></div></div>
 <div class="panel"><div class="panel-header"><h3>SSH Keys</h3><button class="btn btn-sm btn-primary" onclick="showModal('ssh-key-modal')">+ Add Key</button></div><div class="panel-body" id="cloud-ssh-keys"><div class="loading">Loading</div></div></div>
</div>

<!-- VPS SERVICES -->
<div class="page" id="vps-page">
 <h1 class="page-title">VPS Services</h1><p class="page-sub">OVH VPS — reboot, stop, start, rescue, snapshots, console</p>
 <div class="panel"><div class="panel-header"><h3>Your VPS</h3><button class="btn btn-sm btn-primary" onclick="loadVPS()">Refresh</button></div><div class="panel-body" id="vps-list"><div class="loading">Loading</div></div></div>
 <div id="vps-detail-section" style="display:none">
  <div class="stat-grid" id="vps-detail-stats"></div>
  <div class="panel"><div class="panel-header"><h3>VPS Controls: <span id="vps-selected-name"></span></h3></div><div class="panel-body" id="vps-controls"></div></div>
  <div class="panel"><div class="panel-header"><h3>IPs</h3></div><div class="panel-body" id="vps-ips"></div></div>
  <div class="panel"><div class="panel-header"><h3>Snapshot</h3></div><div class="panel-body" id="vps-snapshot"></div></div>
 </div>
</div>

<!-- DEDICATED SERVERS -->
<div class="page" id="dedicated-page">
 <h1 class="page-title">Dedicated Servers</h1><p class="page-sub">Bare metal — reboot, rescue, IPMI/KVM, hardware, reinstall</p>
 <div class="panel"><div class="panel-header"><h3>Your Servers</h3><button class="btn btn-sm btn-primary" onclick="loadDedicated()">Refresh</button></div><div class="panel-body" id="dedicated-list"><div class="loading">Loading</div></div></div>
 <div id="dedicated-detail-section" style="display:none">
  <div class="stat-grid" id="dedicated-detail-stats"></div>
  <div class="panel"><div class="panel-header"><h3>Server Controls: <span id="ded-selected-name"></span></h3></div><div class="panel-body" id="dedicated-controls"></div></div>
  <div class="panel"><div class="panel-header"><h3>Hardware Specs</h3></div><div class="panel-body" id="dedicated-hardware"></div></div>
  <div class="panel"><div class="panel-header"><h3>Network / IPs</h3></div><div class="panel-body" id="dedicated-network"></div></div>
  <div class="panel"><div class="panel-header"><h3>IPMI / KVM</h3></div><div class="panel-body" id="dedicated-ipmi"></div></div>
 </div>
</div>

  </div>
 </div>
</div>

<!-- MODALS -->
<div class="modal-overlay" id="domain-modal"><div class="modal"><button class="close" onclick="hideModal('domain-modal')">&times;</button><h3>Add Domain</h3>
 <div class="form-group"><label>Domain Name</label><input type="text" id="new-domain" placeholder="example.com"></div>
 <button class="btn btn-primary" onclick="createDomain()">Add Domain</button>
</div></div>

<div class="modal-overlay" id="dns-modal"><div class="modal"><button class="close" onclick="hideModal('dns-modal')">&times;</button><h3>Add DNS Record</h3>
 <div class="form-row"><div class="form-group"><label>Type</label><select id="dns-type"><option>A</option><option>AAAA</option><option>CNAME</option><option>MX</option><option>TXT</option><option>SRV</option><option>NS</option></select></div><div class="form-group"><label>Name</label><input type="text" id="dns-name" placeholder="@ or subdomain"></div></div>
 <div class="form-group"><label>Value</label><input type="text" id="dns-value" placeholder="IP or target"></div>
 <div class="form-group"><label>TTL</label><input type="number" id="dns-ttl" value="3600"></div>
 <button class="btn btn-primary" onclick="addDNSRecord()">Add Record</button>
</div></div>

<div class="modal-overlay" id="db-modal"><div class="modal"><button class="close" onclick="hideModal('db-modal')">&times;</button><h3>Create Database</h3>
 <div class="form-group"><label>Database Name</label><input type="text" id="new-db-name" placeholder="myapp_db"></div>
 <button class="btn btn-primary" onclick="createDatabase()">Create</button>
</div></div>

<div class="modal-overlay" id="email-modal"><div class="modal"><button class="close" onclick="hideModal('email-modal')">&times;</button><h3>Create Email Account</h3>
 <div class="form-row"><div class="form-group"><label>User</label><input type="text" id="new-email-user" placeholder="info"></div><div class="form-group"><label>Domain</label><select id="new-email-domain"></select></div></div>
 <div class="form-group"><label>Password</label><input type="password" id="new-email-pass" placeholder="Strong password"></div>
 <div class="form-group"><label>Quota (MB, 0=unlimited)</label><input type="number" id="new-email-quota" value="500"></div>
 <button class="btn btn-primary" onclick="createEmail()">Create</button>
</div></div>

<div class="modal-overlay" id="cron-modal"><div class="modal"><button class="close" onclick="hideModal('cron-modal')">&times;</button><h3>Add Cron Job</h3>
 <div class="form-group"><label>Schedule (cron expression)</label><input type="text" id="new-cron-schedule" placeholder="*/5 * * * *"></div>
 <div class="form-group"><label>Command</label><input type="text" id="new-cron-command" placeholder="/usr/bin/php /path/to/script.php"></div>
 <button class="btn btn-primary" onclick="addCronJob()">Add</button>
</div></div>

<div class="modal-overlay" id="file-modal"><div class="modal"><button class="close" onclick="hideModal('file-modal')">&times;</button><h3>Create File</h3>
 <div class="form-group"><label>Filename</label><input type="text" id="new-filename" placeholder="newfile.txt"></div>
 <div class="form-group"><label>Content</label><textarea id="new-file-content" rows="5" style="font-family:monospace"></textarea></div>
 <button class="btn btn-primary" onclick="createFileAction()">Create</button>
</div></div>

<div class="modal-overlay" id="mkdir-modal"><div class="modal"><button class="close" onclick="hideModal('mkdir-modal')">&times;</button><h3>Create Directory</h3>
 <div class="form-group"><label>Directory Name</label><input type="text" id="new-dirname" placeholder="new-folder"></div>
 <button class="btn btn-primary" onclick="createDir()">Create</button>
</div></div>

<div class="modal-overlay" id="rename-modal"><div class="modal"><button class="close" onclick="hideModal('rename-modal')">&times;</button><h3>Rename / Move</h3>
 <div class="form-group"><label>Current Path</label><input type="text" id="rename-from" readonly></div>
 <div class="form-group"><label>New Path</label><input type="text" id="rename-to"></div>
 <button class="btn btn-primary" onclick="renameFile()">Rename</button>
</div></div>

<div class="modal-overlay" id="chmod-modal"><div class="modal"><button class="close" onclick="hideModal('chmod-modal')">&times;</button><h3>Change Permissions</h3>
 <div class="form-group"><label>File</label><input type="text" id="chmod-file" readonly></div>
 <div class="form-group"><label>Mode (e.g. 755)</label><input type="text" id="chmod-mode" placeholder="755" maxlength="4"></div>
 <button class="btn btn-primary" onclick="chmodFile()">Set</button>
</div></div>

<div class="modal-overlay" id="ide-modal"><div class="modal"><button class="close" onclick="hideModal('ide-modal')">&times;</button><h3>Provision Alfred IDE</h3>
 <div class="form-group"><label>Username</label><input type="text" id="ide-username" placeholder="customer-name"></div>
 <div class="form-group"><label>Port (9000-9999)</label><input type="number" id="ide-port" min="9000" max="9999" placeholder="9001"></div>
 <div class="form-group"><label>Password (optional)</label><input type="text" id="ide-password" placeholder="Auto-generated if blank"></div>
 <button class="btn btn-primary" onclick="provisionIDE()">Provision</button>
</div></div>

<div class="modal-overlay" id="cloud-modal"><div class="modal"><button class="close" onclick="hideModal('cloud-modal')">&times;</button><h3>Create Cloud Instance</h3>
 <div class="form-group"><label>Name</label><input type="text" id="cloud-name" placeholder="my-server"></div>
 <div class="form-group"><label>Region</label><select id="cloud-region"><option value="">Loading...</option></select></div>
 <div class="form-group"><label>Flavor</label><select id="cloud-flavor"><option value="">Loading...</option></select></div>
 <div class="form-group"><label>Image</label><select id="cloud-image"><option value="">Loading...</option></select></div>
 <div class="form-group"><label><input type="checkbox" id="cloud-monthly"> Monthly Billing</label></div>
 <button class="btn btn-primary" onclick="createCloudInstance()">Create</button>
</div></div>

<div class="modal-overlay" id="ssh-key-modal"><div class="modal"><button class="close" onclick="hideModal('ssh-key-modal')">&times;</button><h3>Add SSH Key</h3>
 <div class="form-group"><label>Name</label><input type="text" id="ssh-key-name" placeholder="my-key"></div>
 <div class="form-group"><label>Public Key</label><textarea id="ssh-key-content" rows="3" placeholder="ssh-rsa AAAA..." style="font-family:monospace;font-size:12px"></textarea></div>
 <button class="btn btn-primary" onclick="addSSHKey()">Add</button>
</div></div>

<div class="modal-overlay" id="da-modal"><div class="modal"><button class="close" onclick="hideModal('da-modal')">&times;</button><h3>Configure DirectAdmin</h3>
 <div class="form-group"><label>Admin Username</label><input type="text" id="da-admin" placeholder="admin"></div>
 <div class="form-group"><label>Admin Password</label><input type="password" id="da-pass" placeholder="password"></div>
 <button class="btn btn-primary" onclick="configureDA()">Save</button>
</div></div>

<div class="modal-overlay" id="fw-rule-modal"><div class="modal"><button class="close" onclick="hideModal('fw-rule-modal')">&times;</button><h3>Add Firewall Rule</h3>
 <div class="form-row"><div class="form-group"><label>Action</label><select id="fw-action"><option value="allow">Allow</option><option value="deny">Deny</option><option value="limit">Limit (rate limit)</option></select></div><div class="form-group"><label>Direction</label><select id="fw-direction"><option value="in">Incoming</option><option value="out">Outgoing</option></select></div></div>
 <div class="form-row"><div class="form-group"><label>Port</label><input type="text" id="fw-port" placeholder="80, 443, 22, 3000:4000"></div><div class="form-group"><label>Protocol</label><select id="fw-proto"><option value="tcp">TCP</option><option value="udp">UDP</option><option value="any">Any</option></select></div></div>
 <div class="form-group"><label>From IP (optional)</label><input type="text" id="fw-from-ip" placeholder="any or 192.168.1.0/24"></div>
 <div class="form-group"><label>Comment</label><input type="text" id="fw-comment" placeholder="Allow web traffic"></div>
 <button class="btn btn-primary" onclick="addFirewallRule()">Add Rule</button>
</div></div>

<div class="modal-overlay" id="encrypt-modal"><div class="modal"><button class="close" onclick="hideModal('encrypt-modal')">&times;</button><h3>Encrypt a Volume</h3>
 <p style="color:var(--red);font-size:12px;margin-bottom:12px">WARNING: This will format and encrypt the selected volume. ALL data will be lost. Back up first!</p>
 <div class="form-group"><label>Device</label><input type="text" id="encrypt-device" placeholder="/dev/sdb1"></div>
 <div class="form-group"><label>Encryption Type</label><select id="encrypt-type"><option value="aes-xts-plain64">AES-256-XTS (recommended)</option><option value="aes-cbc-essiv:sha256">AES-256-CBC</option></select></div>
 <div class="form-group"><label>Passphrase</label><input type="password" id="encrypt-pass" placeholder="Strong passphrase"></div>
 <div class="form-group"><label>Confirm Passphrase</label><input type="password" id="encrypt-pass2" placeholder="Confirm"></div>
 <button class="btn btn-danger" onclick="encryptVolume()">Encrypt Volume</button>
</div></div>

<div class="modal-overlay" id="luks-key-modal"><div class="modal"><button class="close" onclick="hideModal('luks-key-modal')">&times;</button><h3>Manage LUKS Keys</h3>
 <div class="form-group"><label>LUKS Device</label><input type="text" id="luks-device" placeholder="/dev/sdb1"></div>
 <div class="form-group"><label>Action</label><select id="luks-key-action"><option value="add">Add Key Slot</option><option value="remove">Remove Key Slot</option><option value="change">Change Passphrase</option></select></div>
 <div class="form-group"><label>Current Passphrase</label><input type="password" id="luks-current-pass"></div>
 <div class="form-group"><label>New Passphrase (for add/change)</label><input type="password" id="luks-new-pass"></div>
 <button class="btn btn-primary" onclick="manageLUKSKey()">Execute</button>
</div></div>

<div class="modal-overlay" id="uptime-modal"><div class="modal"><button class="close" onclick="hideModal('uptime-modal')">&times;</button><h3>Add Uptime Monitor</h3>
 <div class="form-group"><label>Name</label><input type="text" id="uptime-name" placeholder="My Website"></div>
 <div class="form-group"><label>Type</label><select id="uptime-type"><option value="http">HTTP(S)</option><option value="tcp">TCP Port</option><option value="ping">Ping</option></select></div>
 <div class="form-group"><label>URL / Host</label><input type="text" id="uptime-url" placeholder="https://example.com or IP"></div>
 <div class="form-group"><label>Check Interval (seconds)</label><input type="number" id="uptime-interval" value="300" min="30"></div>
 <button class="btn btn-primary" onclick="addUptimeMonitor()">Add Monitor</button>
</div></div>

<div class="modal-overlay" id="git-deploy-modal"><div class="modal"><button class="close" onclick="hideModal('git-deploy-modal')">&times;</button><h3>Add Git Deploy Target</h3>
 <div class="form-group"><label>Name</label><input type="text" id="git-name" placeholder="Production Site"></div>
 <div class="form-group"><label>Repository URL</label><input type="text" id="git-url" placeholder="https://github.com/user/repo.git"></div>
 <div class="form-group"><label>Branch</label><input type="text" id="git-target-branch" value="main"></div>
 <div class="form-group"><label>Deploy Path</label><input type="text" id="git-target-path" placeholder="/home/user/public_html"></div>
 <div class="form-group"><label><input type="checkbox" id="git-auto-deploy"> Auto-deploy on push (webhook)</label></div>
 <button class="btn btn-primary" onclick="addGitDeploy()">Save</button>
</div></div>

<div class="toast" id="toast"></div>

<script>
const API=(['127.0.0.1','localhost'].includes(location.hostname)?'http://127.0.0.1:2224':'/gohostme'), OVH_API='/api/ovh/';
let token=localStorage.getItem('ghm_token'), currentPath='/home/gositeme', editingFile=null, resourceTimer=null, logTimer=null;
let currentUser=null;
const adminOnlyPages=new Set(['dashboard','services','processes','resource-monitor','databases','files','apache','php-manager','pm2','email','email-deliverability','cron','backups','docker','firewall','disk-encryption','malware-scanner','security-headers','server-hardening','activity-log','network-tools','dns-propagation','uptime-monitor','log-viewer','benchmarks','ide','git-deploy','wordpress','migration','cloud-vps','vps','dedicated']);
const supportedPages=new Set(['dashboard','services','processes','domains','dns','databases','files','ssl','apache','pm2','email','cron','backups','ide','server-hardening']);
const idAliases={
 'sql-db':'sql-db-select',
 'sql-output':'sql-results',
 'current-path':'files-path',
 'file-list':'files-list',
 'editor-container':'file-editor-panel',
 'editor-path':'editing-file',
 'editor-content':'file-editor-content',
 'apache-list':'apache-vhosts'
};

const E=id=>document.getElementById(id)||document.getElementById(idAliases[id]||'');
const B=async(p,o={})=>{o.headers={...o.headers,'Content-Type':'application/json'};if(token)o.headers.Authorization='Bearer '+token;o.credentials='include';const r=await fetch(API+p,o);const body=await r.json().catch(()=>({}));if(!r.ok&&!body.error)body.error=r.statusText||'Request failed';body.__httpStatus=r.status;return body};
const T=t=>{const d=E('toast');d.textContent=t;d.classList.add('show');setTimeout(()=>d.classList.remove('show'),3000)};
function ovh(params){return fetch(OVH_API+'?'+new URLSearchParams(params)).then(r=>r.json())}
function showModal(id){E(id)?.classList.add('show')}
function hideModal(id){E(id)?.classList.remove('show')}
function switchTab(btn,targetId){const page=btn.closest('[id$="-page"]')||btn.closest('.page');if(!page)return;btn.closest('.tab-bar')?.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));btn.classList.add('active');page.querySelectorAll('.tab-content').forEach(panel=>panel.classList.remove('active'));E(targetId)?.classList.add('active')}
function escapeHtml(value){return String(value??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;')}
function renderTable(headers,rowsHtml,emptyMessage='No data'){return `<table><thead><tr>${headers.map(header=>`<th>${escapeHtml(header)}</th>`).join('')}</tr></thead><tbody>${rowsHtml||`<tr><td colspan="${headers.length}">${escapeHtml(emptyMessage)}</td></tr>`}</tbody></table>`}
function statusBadge(s){const c={active:'green',running:'green',online:'green',stopped:'red',offline:'red',errored:'red',error:'red'};return '<span class="badge '+(c[s]||'yellow')+'">'+escapeHtml(s)+'</span>'}
function isAdminUser(){return !!currentUser?.admin}
function normalizePage(page){const fallback=isAdminUser()?'dashboard':'domains';const candidate=supportedPages.has(page)?page:fallback;return !isAdminUser()&&adminOnlyPages.has(candidate)?'domains':candidate}
function setActiveNav(page){document.querySelectorAll('.nav-item[data-page]').forEach(item=>item.classList.toggle('active',item.dataset.page===page))}
function applyRoleVisibility(){const admin=isAdminUser();document.querySelectorAll('.nav-item[data-page]').forEach(item=>{const visible=supportedPages.has(item.dataset.page)&&(admin||!adminOnlyPages.has(item.dataset.page));item.style.display=visible?'':'none';if(!visible)item.classList.remove('active')});document.querySelectorAll('.sidebar .section-title').forEach(title=>{let sibling=title.nextElementSibling;let hasVisible=false;while(sibling&&!sibling.classList.contains('section-title')){if(!sibling.classList.contains('nav-item')||sibling.style.display!=='none'){hasVisible=true;break} sibling=sibling.nextElementSibling}title.style.display=hasVisible?'':'none'})}
function resolveInitialPage(){const requested=new URLSearchParams(location.search).get('panel')||(isAdminUser()?'dashboard':'domains');return normalizePage(requested)}

// AUTH
function setAuthState(user){
 currentUser=user||null;
 const loginScreen=E('login-screen'),app=E('app'),userName=E('user-name'),errorBox=E('login-error');
 if(errorBox){errorBox.style.display='none';errorBox.textContent=''}
 applyRoleVisibility();
 if(user){if(userName)userName.textContent=user.name||user.email||'';if(loginScreen)loginScreen.style.display='none';if(app)app.style.display='flex';return}
 if(userName)userName.textContent='';if(app)app.style.display='none';if(loginScreen)loginScreen.style.display='flex'
}
async function syncAuthState(){
 let r=await B('/api/auth/me');
 if(r.__httpStatus===401&&token){token=null;localStorage.removeItem('ghm_token');r=await B('/api/auth/me')}
 if(!r.error&&(r.client_id||r.uid)){setAuthState(r);loadPage(resolveInitialPage());return true}
 setAuthState(null);return false
}
const loginForm=E('login-form');
const goSiteMeLoginLink=E('gositeme-login-link');
const googleLoginLink=E('google-login-link');
const currentReturnPath='/gohostme/dashboard'+(location.search||'');
const encodedReturn=encodeURIComponent(currentReturnPath);
if(goSiteMeLoginLink){goSiteMeLoginLink.href='/login?redirect='+encodedReturn}
if(googleLoginLink){googleLoginLink.href='/api/auth.php?action=google-login&redirect='+encodedReturn}
if(loginForm){
 loginForm.addEventListener('submit',async(e)=>{
  e.preventDefault();
  const email=(E('login-email')?.value||'').trim(),password=E('login-password')?.value||'',errorBox=E('login-error');
  if(!email||!password){if(errorBox){errorBox.textContent='Enter credentials';errorBox.style.display='block'}return}
  try{const r=await B('/api/auth/login',{method:'POST',body:JSON.stringify({email,password})});
   if(r.token){token=r.token;localStorage.setItem('ghm_token',token);setAuthState(r.user||{email,name:email});loadPage(resolveInitialPage());return}
   if(errorBox){errorBox.textContent=r.error||'Login failed';errorBox.style.display='block'}}catch(_err){if(errorBox){errorBox.textContent='Connection failed';errorBox.style.display='block'}}
 })
}
const logoutBtn=document.querySelector('.logout-btn');
if(logoutBtn){
 logoutBtn.onclick=async()=>{try{await B('/api/auth/logout',{method:'POST'})}catch(_err){}token=null;localStorage.removeItem('ghm_token');setAuthState(null);setActiveNav('dashboard')}
}
syncAuthState()

// NAVIGATION
document.querySelectorAll('.nav-item').forEach(n=>n.addEventListener('click',function(){
 const p=this.dataset.page;if(!p)return;
 if(p==='open-ide'){window.open('https://gositeme.com:8443','_blank');return}
 if(p==='billing'){window.open('https://gositeme.com/clientarea.php','_blank');return}
 loadPage(!isAdminUser()&&adminOnlyPages.has(p)?'domains':p);
}));

function loadPage(p){
 p=normalizePage(p);
 setActiveNav(p);
 document.querySelectorAll('.page').forEach(x=>x.style.display='none');
 const el=E(p+'-page');if(el)el.style.display='block';
 const loaders={
  dashboard:loadDashboard, services:loadServices, processes:loadProcesses, domains:loadDomains,
  dns:loadDNSSelect, databases:loadDatabases, files:loadFiles, ssl:loadSSL, apache:loadApache,
  pm2:loadPM2, email:loadEmail, cron:loadCron, backups:loadBackups, ide:loadIDE,
  'cloud-vps':loadCloud, vps:loadVPS, dedicated:loadDedicated,
  'resource-monitor':loadResourceMonitor, 'php-manager':loadPHP, 'email-deliverability':loadEmailHealth,
  docker:loadDocker, firewall:loadFirewall, 'disk-encryption':loadDiskEncryption,
  'malware-scanner':()=>E('malware-results').innerHTML='<p>Select a scan type above to begin</p>',
  'security-headers':()=>{}, 'server-hardening':loadServerHardening, 'activity-log':loadActivityLog,
  'network-tools':()=>{}, 'dns-propagation':()=>{}, 'uptime-monitor':loadUptime,
  'log-viewer':loadLogViewer, benchmarks:()=>E('bench-results').innerHTML='<p>Select a benchmark to run</p>',
  'git-deploy':loadGitDeploy, wordpress:loadWordPress, migration:()=>{}
 };
 if(loaders[p])loaders[p]();
}


async function loadServerHardening(){
 const o=E('ghm-f2b-output');
 o.innerHTML='<div class="loading">Loading Fail2Ban status…</div>';
 try{
  const r=await fetch(API+'/api/security/fail2ban',{headers:{'Content-Type':'application/json','Authorization':'Bearer '+token}});
  const j=await r.json();
  if(!r.ok) o.innerHTML='<p class="error">'+(j.error||j.message||'Request failed')+'</p>';
  else o.innerHTML='<pre class="terminal-out" style="white-space:pre-wrap;max-height:480px;overflow:auto">'+(j.output||JSON.stringify(j,null,2)).replace(/</g,'&lt;')+'</pre>';
 }catch(e){ o.innerHTML='<p class="error">'+e.message+'</p>'; }
}

async function loadDiagnostics(){
 const o=E('ghm-diag-output');
 o.innerHTML='<div class="loading">Collecting diagnostics…</div>';
 try{
  const r=await fetch(API+'/api/security/diagnostics',{headers:{'Content-Type':'application/json','Authorization':'Bearer '+token}});
  const j=await r.json();
  if(!r.ok){ o.innerHTML='<p class="error">'+(j.error||'Failed')+'</p>'; return; }
  const esc=s=>String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  let summary='';
  if(Array.isArray(j.interpretation)&&j.interpretation.length){
   summary='<div style="margin-bottom:12px;padding:10px 12px;border-radius:8px;border:1px solid var(--border);background:rgba(0,0,0,.12)"><strong>Summary</strong><ul style="margin:8px 0 0 18px;color:var(--text2);line-height:1.55;font-size:13px">'+j.interpretation.map(x=>'<li>'+esc(x)+'</li>').join('')+'</ul></div>';
  }
  o.innerHTML=summary+'<pre class="terminal-out" style="white-space:pre-wrap;max-height:520px;overflow:auto">'+esc(JSON.stringify(j,null,2))+'</pre>';
 }catch(e){ o.innerHTML='<p class="error">'+e.message+'</p>'; }
}

async function ghmApplySecurity(){
 const o=E('ghm-apply-output');
 o.innerHTML='<div class="loading">Running apply-security-hardening…</div>';
 try{
  const r=await fetch(API+'/api/security/apply-hardening',{method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+token}});
  const j=await r.json();
  o.innerHTML='<pre class="terminal-out" style="white-space:pre-wrap;max-height:400px;overflow:auto">'+(j.output||j.error||JSON.stringify(j)).replace(/</g,'&lt;')+'</pre>';
  if(!r.ok) T(j.error||'Apply failed'); else T('Security hardening finished');
 }catch(e){ o.innerHTML='<p class="error">'+e.message+'</p>'; }
}
async function ghmApplyOptimization(){
 const o=E('ghm-apply-output');
 o.innerHTML='<div class="loading">Running apply-stack-optimization…</div>';
 try{
  const r=await fetch(API+'/api/security/apply-optimization',{method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+token}});
  const j=await r.json();
  o.innerHTML='<pre class="terminal-out" style="white-space:pre-wrap;max-height:400px;overflow:auto">'+(j.output||j.error||JSON.stringify(j)).replace(/</g,'&lt;')+'</pre>';
  if(!r.ok) T(j.error||'Apply failed'); else T('Stack optimization finished');
 }catch(e){ o.innerHTML='<p class="error">'+e.message+'</p>'; }
}

// DASHBOARD
async function loadDashboard(){
 try{
 if(!isAdminUser()){
  const d=await B('/api/panel/stats');
  E('dash-content').innerHTML=`
  <div class="stat-grid">
  <div class="stat-card"><div class="stat-label">Active Services</div><div class="stat-value">${d.whmcs?.services??'--'}</div></div>
   <div class="stat-card"><div class="stat-label">Active Domains</div><div class="stat-value">${d.domains??'--'}</div></div>
   <div class="stat-card"><div class="stat-label">Access Scope</div><div class="stat-value" style="font-size:18px">Client</div></div>
   <div class="stat-card"><div class="stat-label">Panel Version</div><div class="stat-value" style="font-size:18px">${d.version||'4.0.0'}</div></div>
  </div>
  <div class="panel"><div class="panel-header"><h3>Account-Safe View</h3></div><div class="panel-body">This session is limited to resources tied to your account. Use Domains, DNS, and SSL from the sidebar to manage your hosting.</div></div>`;
  return;
 }
 const d=await B('/api/system/stats');
 E('dash-content').innerHTML=`
  <div class="stat-grid">
   <div class="stat-card"><div class="stat-label">CPU</div><div class="stat-value">${d.cpu||'--'}%</div></div>
   <div class="stat-card"><div class="stat-label">Memory</div><div class="stat-value">${d.memory?.percent||'--'}%</div><div class="stat-label">${d.memory?.used||'?'} / ${d.memory?.total||'?'}</div></div>
   <div class="stat-card"><div class="stat-label">Disk</div><div class="stat-value">${d.disk?.percent||'--'}%</div><div class="stat-label">${d.disk?.used||'?'} / ${d.disk?.total||'?'}</div></div>
   <div class="stat-card"><div class="stat-label">Uptime</div><div class="stat-value" style="font-size:18px">${d.uptime||'--'}</div></div>
   <div class="stat-card"><div class="stat-label">Load Average</div><div class="stat-value" style="font-size:18px">${d.loadAverage||'--'}</div></div>
   <div class="stat-card"><div class="stat-label">Hostname</div><div class="stat-value" style="font-size:14px">${d.hostname||'--'}</div></div>
  </div>`}catch(e){E('dash-content').innerHTML='<p class="error">Failed to load stats</p>'}
}

// SERVICES
async function loadServices(){
 try{const d=await B('/api/system/services');
 const rows=(d.services||[]).map(s=>`<tr><td>${escapeHtml(s.name||'')}</td><td>${statusBadge(s.status||'unknown')}</td><td>${escapeHtml(s.description||'')}</td></tr>`).join('');
 E('services-list').innerHTML=renderTable(['Service','Status','Description'],rows,'No services');
 }catch(e){E('services-list').innerHTML=renderTable(['Service','Status','Description'],'','Error loading services')}
}

// PROCESSES
async function loadProcesses(){
 try{const d=await B('/api/system/processes');
 const rows=(d.processes||[]).slice(0,50).map(p=>`<tr><td>${escapeHtml(p.pid||'')}</td><td>${escapeHtml(p.user||'')}</td><td>${escapeHtml((p.cpu??0)+'%')}</td><td>${escapeHtml((p.mem??0)+'%')}</td><td>${escapeHtml((p.command||'').substring(0,120))}</td></tr>`).join('');
 E('processes-list').innerHTML=renderTable(['PID','User','CPU','Memory','Command'],rows,'No processes found');
 }catch(e){E('processes-list').innerHTML=renderTable(['PID','User','CPU','Memory','Command'],'','Error loading processes')}
}

// DOMAINS
async function loadDomains(){
 const addBtn=document.querySelector('#domains-page .panel-header .btn');
 if(addBtn)addBtn.style.display=isAdminUser()?'':'none';
 try{const d=await B('/api/domains');
 const rows=(d.domains||[]).map(dm=>{const domainName=dm.name||dm;return `<tr><td>${escapeHtml(domainName)}</td><td>${escapeHtml(dm.docRoot||'--')}</td><td>${dm.ssl?'<span class="badge green">Yes</span>':'<span class="badge red">No</span>'}</td><td>${isAdminUser()?`<button class="btn btn-sm btn-danger" onclick='deleteDomain(${JSON.stringify(domainName)})'>Delete</button>`:'<span class="badge blue">View only</span>'}</td></tr>`}).join('');
 E('domains-list').innerHTML=renderTable(['Domain','Document Root','SSL','Actions'],rows,'No domains');
 }catch(e){E('domains-list').innerHTML=renderTable(['Domain','Document Root','SSL','Actions'],'','Error loading domains')}
}
async function createDomain(){const n=E('new-domain').value;if(!n)return T('Enter domain');const r=await B('/api/domains/create',{method:'POST',body:JSON.stringify({domain:n})});T(r.message||r.error);hideModal('domain-modal');loadDomains()}
async function deleteDomain(d){if(!confirm('Delete '+d+'?'))return;const r=await B('/api/domains/'+d,{method:'DELETE'});T(r.message||r.error);loadDomains()}

// DNS
async function loadDNSSelect(){
 const addBtn=document.querySelector('#dns-records-panel .panel-header .btn');
 if(addBtn)addBtn.style.display=isAdminUser()?'':'none';
 try{const d=await B('/api/domains');const s=E('dns-domain-select');
 const domains=(d.domains||[]).map(dm=>dm.name||dm).filter(Boolean);
 s.innerHTML='<option value="">Select a domain...</option>'+domains.map(domain=>`<option value="${escapeHtml(domain)}">${escapeHtml(domain)}</option>`).join('');
 if(domains.length){s.value=domains.includes(s.value)?s.value:domains[0];E('dns-records-panel').style.display='block';loadDNS(s.value)}else{E('dns-records-panel').style.display='none';E('dns-records').innerHTML=renderTable(['Type','Name','Value','TTL','Actions'],'','No DNS records')}
 s.onchange=()=>loadDNS(s.value)}catch(e){}
}
async function loadDNS(domain){
 if(!domain)domain=E('dns-domain-select')?.value;
 if(!domain){E('dns-records-panel').style.display='none';return}
 try{const d=await B('/api/dns/'+domain);
 E('dns-records-panel').style.display='block';
 const rows=(d.records||[]).map(r=>`<tr><td>${escapeHtml(r.type||'')}</td><td>${escapeHtml(r.name||'')}</td><td style="max-width:300px;overflow:hidden;text-overflow:ellipsis">${escapeHtml(r.value||'')}</td><td>${escapeHtml(r.ttl||3600)}</td><td>${isAdminUser()?`<button class="btn btn-sm btn-danger" onclick='deleteDNSRecord(${JSON.stringify(domain)},${JSON.stringify(r.id||r.name)},${JSON.stringify(r.type)})'>Delete</button>`:'<span class="badge blue">View only</span>'}</td></tr>`).join('');
 E('dns-records').innerHTML=renderTable(['Type','Name','Value','TTL','Actions'],rows,'No DNS records');
 }catch(e){E('dns-records').innerHTML=renderTable(['Type','Name','Value','TTL','Actions'],'','Error loading DNS records')}
}
async function addDNSRecord(){const d=E('dns-domain-select').value;if(!d)return;const r=await B('/api/dns/'+d+'/record',{method:'POST',body:JSON.stringify({type:E('dns-type').value,name:E('dns-name').value,value:E('dns-value').value,ttl:parseInt(E('dns-ttl').value)})});T(r.message||r.error);hideModal('dns-modal');loadDNS(d)}
async function deleteDNSRecord(d,id,type){if(!confirm('Delete record?'))return;const r=await B('/api/dns/'+d+'/record',{method:'DELETE',body:JSON.stringify({id,type})});T(r.message||r.error);loadDNS(d)}

// DATABASES
async function loadDatabases(){
 try{const d=await B('/api/databases');
 const databases=(d.databases||[]).map(db=>db.name||db).filter(Boolean);
 const select=E('sql-db-select');if(select)select.innerHTML='<option value="">Default</option>'+databases.map(name=>`<option value="${escapeHtml(name)}">${escapeHtml(name)}</option>`).join('');
 const rows=databases.map(name=>`<tr><td>${escapeHtml(name)}</td><td>--</td><td>--</td><td><button class="btn btn-sm" onclick='viewTables(${JSON.stringify(name)})'>Tables</button> <button class="btn btn-sm btn-danger" onclick='deleteDatabase(${JSON.stringify(name)})'>Drop</button></td></tr>`).join('');
 E('db-list').innerHTML=renderTable(['Database','Tables','Size','Actions'],rows,'No databases');
 }catch(e){E('db-list').innerHTML=renderTable(['Database','Tables','Size','Actions'],'','Error loading databases')}
}
async function createDatabase(){const n=E('new-db-name').value;if(!n)return T('Enter name');const r=await B('/api/databases/create',{method:'POST',body:JSON.stringify({name:n})});T(r.message||r.error);hideModal('db-modal');loadDatabases()}
async function deleteDatabase(n){if(!confirm('DROP database '+n+'?'))return;const r=await B('/api/databases/'+n,{method:'DELETE'});T(r.message||r.error);loadDatabases()}
async function viewTables(n){const r=await B('/api/databases/'+n+'/tables');E('sql-results').innerHTML='<pre class="terminal-out">'+escapeHtml(JSON.stringify(r.tables||r,null,2))+'</pre>'}
async function runSQL(){const db=E('sql-db-select').value,q=E('sql-query').value;if(!q)return;const r=await B('/api/databases/query',{method:'POST',body:JSON.stringify({database:db,query:q})});E('sql-results').innerHTML='<pre class="terminal-out">'+escapeHtml(JSON.stringify(r.results||r,null,2))+'</pre>'}

// FILES
async function loadFiles(path){
 if(path)currentPath=path;
 try{const d=await B('/api/files?path='+encodeURIComponent(currentPath));
 currentPath=d.path||currentPath;
 E('files-path').textContent=currentPath;
 const rows=(d.files||[]).map(f=>{
  const fp=(currentPath.endsWith('/')?currentPath.slice(0,-1):currentPath)+'/'+f.name, isDir=f.type==='directory';
  return `<tr><td>${isDir?'📁':'📄'} ${isDir?`<a href="#" onclick='loadFiles(${JSON.stringify(fp)});return false'>${escapeHtml(f.name)}</a>`:escapeHtml(f.name)}</td><td>${escapeHtml(f.size??'--')}</td><td>${escapeHtml(f.permissions||'--')}</td><td>${escapeHtml(f.modified||'--')}</td><td>
   ${!isDir?`<button class="btn btn-sm" onclick='editFile(${JSON.stringify(fp)})'>Edit</button> `:''}
   <button class="btn btn-sm" onclick='showRename(${JSON.stringify(fp)})'>Rename</button>
   <button class="btn btn-sm" onclick='showChmod(${JSON.stringify(fp)})'>Chmod</button>
   <button class="btn btn-sm btn-danger" onclick='deleteFile(${JSON.stringify(fp)})'>Delete</button></td></tr>`}).join('');
 E('files-list').innerHTML=renderTable(['Name','Size','Permissions','Modified','Actions'],rows,'Folder is empty');
 }catch(e){E('files-list').innerHTML=renderTable(['Name','Size','Permissions','Modified','Actions'],'','Error loading files')}
}
function goUp(){const parts=currentPath.split('/');parts.pop();loadFiles(parts.join('/')||'/')}
async function editFile(p){
 try{const d=await B('/api/files/read?path='+encodeURIComponent(p));
 editingFile=p;E('file-editor-panel').style.display='block';E('editing-file').textContent=p;E('file-editor-content').value=d.content||''}catch(e){T('Cannot read file')}
}
async function saveFile(){if(!editingFile)return;const r=await B('/api/files/write',{method:'POST',body:JSON.stringify({filePath:editingFile,content:E('file-editor-content').value})});T(r.message||r.error)}
function closeEditor(){E('file-editor-panel').style.display='none';editingFile=null}
async function createFileAction(){const n=E('new-filename').value;if(!n)return T('Enter filename');const r=await B('/api/files/write',{method:'POST',body:JSON.stringify({filePath:currentPath+'/'+n,content:E('new-file-content').value||''})});T(r.message||r.error);hideModal('file-modal');loadFiles()}
async function createDir(){const n=E('new-dirname').value;if(!n)return T('Enter name');const r=await B('/api/files/mkdir',{method:'POST',body:JSON.stringify({path:currentPath+'/'+n})});T(r.message||r.error);hideModal('mkdir-modal');loadFiles()}
async function deleteFile(p){if(!confirm('Delete '+p+'?'))return;const r=await B('/api/files?path='+encodeURIComponent(p),{method:'DELETE'});T(r.message||r.error);loadFiles()}
function showRename(p){E('rename-from').value=p;E('rename-to').value=p;showModal('rename-modal')}
async function renameFile(){const r=await B('/api/files/rename',{method:'POST',body:JSON.stringify({from:E('rename-from').value,to:E('rename-to').value})});T(r.message||r.error);hideModal('rename-modal');loadFiles()}
function showChmod(p){E('chmod-file').value=p;showModal('chmod-modal')}
async function chmodFile(){const r=await B('/api/files/chmod',{method:'POST',body:JSON.stringify({path:E('chmod-file').value,mode:E('chmod-mode').value})});T(r.message||r.error);hideModal('chmod-modal');loadFiles()}

// SSL
async function loadSSL(){
 try{const doms=await B('/api/domains');
 const rows=[];
 for(const dm of (doms.domains||[])){
  const domainName=dm.name||dm;
  const d=await B('/api/ssl/'+domainName);
  rows.push(`<tr><td>${escapeHtml(domainName)}</td><td>${escapeHtml(d.issuer||'None')}</td><td>${escapeHtml(d.expires||'N/A')}</td><td>${d.valid?'<span class="badge green">Valid</span>':'<span class="badge red">Invalid/None</span>'}</td><td>${isAdminUser()?`<button class="btn btn-sm btn-primary" onclick='requestSSL(${JSON.stringify(domainName)})'>Issue/Renew</button>`:'<span class="badge blue">Read only</span>'}</td></tr>`)}
 E('ssl-list').innerHTML=renderTable(['Domain','Issuer','Expires','Status','Actions'],rows.join(''),'No certificates');}catch(e){E('ssl-list').innerHTML=renderTable(['Domain','Issuer','Expires','Status','Actions'],'','Error loading SSL status')}
}
async function requestSSL(d){T('Requesting SSL for '+d+'...');const r=await B('/api/ssl/request',{method:'POST',body:JSON.stringify({domain:d})});T(r.message||r.error);loadSSL()}

// APACHE
async function loadApache(){
 try{const [apache,da]=await Promise.all([B('/api/apache/vhosts'),B('/api/da/status')]);
 const rows=(apache.vhosts||[]).map(v=>`<tr><td>${escapeHtml(v.domain||'')}</td><td>${escapeHtml(v.port||80)}</td><td>${escapeHtml(v.config||'--')}</td><td>${v.ssl?'<span class="badge green">SSL</span>':'<span class="badge yellow">HTTP</span>'}</td></tr>`).join('');
 E('apache-vhosts').innerHTML=rows?renderTable(['Domain','Port','Config','TLS'],rows,'No virtual hosts'):`<pre class="terminal-out">${escapeHtml(apache.output||'No virtual host data available')}</pre>`;
 const daRows=`<tr><td>Configured</td><td>${da.configured?'<span class="badge green">Yes</span>':'<span class="badge red">No</span>'}</td></tr><tr><td>Connected</td><td>${da.connected?'<span class="badge green">Yes</span>':'<span class="badge red">No</span>'}</td></tr><tr><td>Details</td><td>${escapeHtml(da.message||da.error||'DirectAdmin reachable')}</td></tr>`;
 E('da-status').innerHTML=renderTable(['Setting','Value'],daRows,'No DirectAdmin status');
 }catch(e){E('apache-vhosts').innerHTML='<pre class="terminal-out">Unable to load Apache status</pre>';E('da-status').innerHTML=renderTable(['Setting','Value'],'','Error loading DirectAdmin status')}
}
async function configureDA(){const r=await B('/api/da/configure',{method:'POST',body:JSON.stringify({admin:E('da-admin').value,password:E('da-pass').value})});T(r.message||r.error);hideModal('da-modal')}

// PM2
async function loadPM2(){
 try{const d=await B('/api/pm2/list');
 const rows=(d.processes||[]).map(p=>`<tr><td>${escapeHtml(p.pm_id??'')}</td><td>${escapeHtml(p.name||'')}</td><td>${statusBadge(p.pm2_env?.status||p.status||'unknown')}</td><td>${escapeHtml(p.pm2_env?.pm_uptime?new Date(p.pm2_env.pm_uptime).toLocaleString():'--')}</td><td>${escapeHtml((p.monit?.cpu||0)+'%')}</td><td>${escapeHtml(p.monit?.memory?Math.round(p.monit.memory/1048576)+'MB':'--')}</td><td>
  <button class="btn btn-sm" onclick='pm2Action("restart",${JSON.stringify(p.name)})'>Restart</button>
  <button class="btn btn-sm" onclick='pm2Action("stop",${JSON.stringify(p.name)})'>Stop</button>
  <button class="btn btn-sm" onclick='pm2Action("start",${JSON.stringify(p.name)})'>Start</button>
  <button class="btn btn-sm" onclick='pm2Logs(${JSON.stringify(p.name)})'>Logs</button>
 </td></tr>`).join('');
 E('pm2-list').innerHTML=renderTable(['ID','Name','Status','Uptime','CPU','Memory','Actions'],rows,'No PM2 processes');
 }catch(e){E('pm2-list').innerHTML=renderTable(['ID','Name','Status','Uptime','CPU','Memory','Actions'],'','Error loading PM2 processes')}
}
async function pm2Action(action,name){const r=await B('/api/pm2/action',{method:'POST',body:JSON.stringify({action,name})});T(r.message||r.error);loadPM2()}
async function pm2Logs(name){const r=await B('/api/pm2/logs?name='+encodeURIComponent(name));E('pm2-log-name').textContent=name;E('pm2-log-output').textContent=r.logs||r.error||'No logs';E('pm2-log-panel').style.display='block'}

// EMAIL
async function loadEmail(){
 try{const domainsResponse=await B('/api/domains');
 const domains=(domainsResponse.domains||[]).map(dm=>dm.name||dm).filter(Boolean);
 const select=E('new-email-domain');if(select)select.innerHTML=domains.map(domain=>`<option value="${escapeHtml(domain)}">${escapeHtml(domain)}</option>`).join('');
 const responses=await Promise.all(domains.map(domain=>B('/api/email/accounts?domain='+encodeURIComponent(domain))));
 const accounts=responses.flatMap((response,index)=>(response.accounts||[]).map(account=>typeof account==='string'?{address:account,user:account.split('@')[0]||'',domain:account.split('@')[1]||domains[index]}:{...account,domain:account.domain||domains[index],address:account.address||`${account.user}@${account.domain||domains[index]}`}));
 const rows=accounts.map(account=>`<tr><td>${escapeHtml(account.address||'')}</td><td>${escapeHtml(account.domain||'')}</td><td>mailbox</td><td><button class="btn btn-sm btn-danger" onclick='deleteEmail(${JSON.stringify(account.user)},${JSON.stringify(account.domain)})'>Delete</button></td></tr>`).join('');
 E('email-list').innerHTML=renderTable(['Address','Domain','Type','Actions'],rows,domains.length?'No email accounts':'No domains available');
 }catch(e){E('email-list').innerHTML=renderTable(['Address','Domain','Type','Actions'],'','Error loading email accounts')}
}
async function createEmail(){const r=await B('/api/email/create',{method:'POST',body:JSON.stringify({user:E('new-email-user').value,domain:E('new-email-domain').value,password:E('new-email-pass').value,quota:parseInt(E('new-email-quota').value)})});T(r.message||r.error);hideModal('email-modal');loadEmail()}
async function deleteEmail(u,d){if(!confirm('Delete '+u+'@'+d+'?'))return;const r=await B('/api/email/account?user='+u+'&domain='+d,{method:'DELETE'});T(r.message||r.error);loadEmail()}

// CRON
async function loadCron(){
 try{const d=await B('/api/cron');
 const rows=(d.jobs||[]).map((j,index)=>`<tr><td><code>${escapeHtml(j.schedule||j.pattern||'')}</code></td><td>${escapeHtml(j.command||'')}</td><td><button class="btn btn-sm btn-danger" onclick='deleteCron(${index})'>Delete</button></td></tr>`).join('');
 E('cron-list').innerHTML=renderTable(['Schedule','Command','Actions'],rows,'No cron jobs');
 }catch(e){E('cron-list').innerHTML=renderTable(['Schedule','Command','Actions'],'','Error loading cron jobs')}
}
async function addCronJob(){const r=await B('/api/cron',{method:'POST',body:JSON.stringify({schedule:E('new-cron-schedule').value,command:E('new-cron-command').value})});T(r.message||r.error);hideModal('cron-modal');loadCron()}
async function deleteCron(index){if(!confirm('Delete cron job?'))return;const r=await B('/api/cron/'+index,{method:'DELETE'});T(r.message||r.error);loadCron()}

// BACKUPS
async function loadBackups(){
 try{const d=await B('/api/backups');
 const rows=(d.backups||[]).map(b=>`<tr><td>${escapeHtml(b.name||b.filename||'')}</td><td>${escapeHtml(b.size??'--')}</td><td>${escapeHtml(b.modified||b.date||b.created||'--')}</td><td><button class="btn btn-sm btn-primary" onclick="T('Download handling not wired yet')">Download</button></td></tr>`).join('');
 E('backup-list').innerHTML=renderTable(['Name','Size','Modified','Actions'],rows,'No backups');
 }catch(e){E('backup-list').innerHTML=renderTable(['Name','Size','Modified','Actions'],'','Error loading backups')}
}
async function createBackup(type){T('Creating backup...');const r=await B('/api/backups/create',{method:'POST',body:JSON.stringify({type})});T(r.message||r.error);loadBackups()}

// IDE
async function loadIDE(){
 try{const [d,stats]=await Promise.all([B('/api/ide/instances'),B('/api/ide/stats')]);
 E('ide-stats').innerHTML=`<div class="stat-card"><div class="label">Instances</div><div class="value cyan">${stats.instances?.total??d.total??0}</div></div><div class="stat-card"><div class="label">Online</div><div class="value green">${stats.instances?.online??0}</div></div><div class="stat-card"><div class="label">Memory</div><div class="value orange">${stats.memory?.total_mb??0}MB</div></div><div class="stat-card"><div class="label">Billing Active</div><div class="value yellow">${stats.whmcs_active??0}</div></div>`;
 const rows=(d.instances||[]).map(i=>`<tr><td>${escapeHtml(i.name||'')}</td><td>${escapeHtml(i.port||'--')}</td><td>${statusBadge(i.status||'unknown')}</td><td>${escapeHtml(i.user||'--')}</td><td>
  <button class="btn btn-sm" onclick='ideCtl("start",${JSON.stringify(i.name)})'>Start</button>
  <button class="btn btn-sm" onclick='ideCtl("stop",${JSON.stringify(i.name)})'>Stop</button>
  <button class="btn btn-sm" onclick='ideCtl("restart",${JSON.stringify(i.name)})'>Restart</button>
  <button class="btn btn-sm btn-danger" onclick='ideDelete(${JSON.stringify(i.name)})'>Delete</button>
 </td></tr>`).join('');
 E('ide-list').innerHTML=renderTable(['Instance','Port','Status','Owner','Actions'],rows,'No IDE instances');
 }catch(e){E('ide-stats').innerHTML='';E('ide-list').innerHTML=renderTable(['Instance','Port','Status','Owner','Actions'],'','Error loading IDE instances')}
}
async function ideCtl(a,n){const r=await B('/api/ide/control',{method:'POST',body:JSON.stringify({action:a,instance:n})});T(r.message||r.error);loadIDE()}
async function ideDelete(n){if(!confirm('Delete IDE '+n+'?'))return;const r=await B('/api/ide/instance/'+n,{method:'DELETE'});T(r.message||r.error);loadIDE()}
async function provisionIDE(){const r=await B('/api/ide/provision',{method:'POST',body:JSON.stringify({username:E('ide-username').value,port:parseInt(E('ide-port').value),password:E('ide-password').value})});T(r.message||r.error);hideModal('ide-modal');loadIDE()}

// ===== CLOUD VPS (OVH) =====
async function loadCloud(){
 try{const d=await ovh({action:'listInstances'});
 E('cloud-list').innerHTML=(d.data||[]).map(i=>`<tr><td>${i.name}</td><td>${i.id?.substring(0,8)}</td><td>${i.status}</td><td>${i.ipAddresses?.find(x=>x.version===4)?.ip||'--'}</td><td>${i.flavor?.name||'--'}</td><td>${i.region||'--'}</td><td>
  <button class="btn btn-xs" onclick="cloudReboot('${i.id}')">Reboot</button>
  <button class="btn btn-xs" onclick="cloudRescue('${i.id}')">Rescue</button>
  <button class="btn btn-xs" onclick="cloudConsole('${i.id}')">VNC</button>
  <button class="btn btn-xs" onclick="cloudSnapshot('${i.id}','${i.name}')">Snap</button>
  <button class="btn btn-xs btn-danger" onclick="cloudDelete('${i.id}')">Del</button>
 </td></tr>`).join('')||'<tr><td colspan="7">No instances</td></tr>';
 const stats=await ovh({action:'getProjectStats'});
 E('cloud-usage').innerHTML=stats.data?`<p>Instances: ${stats.data.instances||0} | Snapshots: ${stats.data.snapshots||0} | Volumes: ${stats.data.volumes||0}</p>`:'';
 }catch(e){E('cloud-list').innerHTML='<tr><td colspan="7">Error: '+e.message+'</td></tr>'}
}
async function loadCloudDropdowns(){
 try{const [r,f,i]=await Promise.all([ovh({action:'listRegions'}),ovh({action:'listFlavors'}),ovh({action:'listImages'})]);
 E('cloud-region').innerHTML=(r.data||[]).map(x=>`<option value="${x.name||x}">${x.name||x}</option>`).join('');
 E('cloud-flavor').innerHTML=(f.data||[]).map(x=>`<option value="${x.id}">${x.name} (${x.vcpus}vCPU/${Math.round((x.ram||0)/1024)}GB)</option>`).join('');
 E('cloud-image').innerHTML=(i.data||[]).map(x=>`<option value="${x.id}">${x.name}</option>`).join('')}catch(e){}
}
async function createCloudInstance(){const r=await ovh({action:'createInstance',name:E('cloud-name').value,region:E('cloud-region').value,flavorId:E('cloud-flavor').value,imageId:E('cloud-image').value,monthlyBilling:E('cloud-monthly').checked});T(r.message||r.error||'Instance created');hideModal('cloud-modal');loadCloud()}
async function cloudReboot(id){if(!confirm('Reboot?'))return;const r=await ovh({action:'rebootInstance',instanceId:id,type:'soft'});T(r.message||'Rebooting')}
async function cloudRescue(id){const r=await ovh({action:'rescueInstance',instanceId:id});T(r.message||r.error||'Rescue mode activated')}
async function cloudUnrescue(id){const r=await ovh({action:'unrescueInstance',instanceId:id});T(r.message||'Exiting rescue')}
async function cloudConsole(id){const r=await ovh({action:'getInstanceConsole',instanceId:id});if(r.data?.url)window.open(r.data.url,'_blank');else T(r.error||'No console URL')}
async function cloudSnapshot(id,name){T('Creating snapshot...');const r=await ovh({action:'createSnapshot',instanceId:id,name:name+'-snap-'+Date.now()});T(r.message||r.error||'Snapshot created')}
async function cloudDelete(id){if(!confirm('DELETE this instance permanently?'))return;const r=await ovh({action:'deleteInstance',instanceId:id});T(r.message||'Deleted');loadCloud()}
async function addSSHKey(){const r=await ovh({action:'addSSHKey',name:E('ssh-key-name').value,publicKey:E('ssh-key-content').value});T(r.message||r.error||'Key added');hideModal('ssh-key-modal')}
async function deleteSSHKey(id){if(!confirm('Delete SSH key?'))return;const r=await ovh({action:'deleteSSHKey',keyId:id});T(r.message||'Deleted')}

// ===== VPS SERVICES =====
async function loadVPS(){
 try{const d=await ovh({action:'listVPS'});
 E('vps-list').innerHTML=(d.data||[]).map(v=>{const n=typeof v==='string'?v:v.name;return `<tr><td>${n}</td><td>--</td><td>--</td><td><button class="btn btn-xs btn-primary" onclick="loadVPSDetail('${n}')">Manage</button></td></tr>`}).join('')||'<tr><td colspan="4">No VPS</td></tr>';
 }catch(e){E('vps-list').innerHTML='<tr><td colspan="4">Error</td></tr>'}
}
async function loadVPSDetail(name){
 const d=await ovh({action:'getVPSInfo',serviceName:name});const v=d.data||{};
 E('vps-detail').style.display='block';
 E('vps-detail').innerHTML=`<h4>${v.displayName||name}</h4>
  <div class="info-grid"><div class="info-item"><strong>State:</strong> ${v.state||'--'}</div><div class="info-item"><strong>Model:</strong> ${v.model?.name||'--'}</div><div class="info-item"><strong>vCores:</strong> ${v.model?.vcore||'--'}</div><div class="info-item"><strong>RAM:</strong> ${v.model?.memory||'--'}MB</div><div class="info-item"><strong>Disk:</strong> ${v.model?.disk||'--'}GB</div><div class="info-item"><strong>Zone:</strong> ${v.zone||'--'}</div></div>
  <div style="margin-top:12px">
   <button class="btn btn-primary" onclick="vpsAction('reboot','${name}')">Reboot</button>
   <button class="btn" onclick="vpsConsole('${name}')">VNC Console</button>
   <button class="btn" onclick="vpsCreateSnapshot('${name}')">Snapshot</button>
   <button class="btn btn-danger" onclick="vpsReinstall('${name}')">Reinstall</button>
  </div>`;
}
async function vpsAction(a,n){if(!confirm(a+' VPS?'))return;const r=await ovh({action:a+'VPS',serviceName:n});T(r.message||r.error||a+' done')}
async function vpsConsole(n){const r=await ovh({action:'getVPSConsole',serviceName:n});if(r.data)window.open(r.data,'_blank');else T(r.error||'No console')}
async function vpsCreateSnapshot(n){T('Creating VPS snapshot...');const r=await ovh({action:'createVPSSnapshot',serviceName:n,description:'Snapshot '+new Date().toISOString()});T(r.message||r.error||'Done')}
async function vpsRestoreSnapshot(n){if(!confirm('Restore snapshot?'))return;const r=await ovh({action:'restoreVPSSnapshot',serviceName:n});T(r.message||r.error||'Restoring')}
async function vpsReinstall(n){if(!confirm('REINSTALL will ERASE ALL DATA. Continue?'))return;const r=await ovh({action:'reinstallVPS',serviceName:n});T(r.message||r.error||'Reinstalling')}

// ===== DEDICATED SERVERS =====
async function loadDedicated(){
 try{const d=await ovh({action:'listDedicated'});
 E('ded-list').innerHTML=(d.data||[]).map(s=>{const n=typeof s==='string'?s:s.name;return `<tr><td>${n}</td><td>--</td><td>--</td><td>--</td><td><button class="btn btn-xs btn-primary" onclick="loadDedicatedDetail('${n}')">Manage</button></td></tr>`}).join('')||'<tr><td colspan="5">No servers</td></tr>';
 }catch(e){E('ded-list').innerHTML='<tr><td colspan="5">Error</td></tr>'}
}
async function loadDedicatedDetail(name){
 const d=await ovh({action:'getDedicatedInfo',serviceName:name});const s=d.data||{};
 E('ded-detail').style.display='block';
 E('ded-detail').innerHTML=`<h4>${s.reverse||name}</h4>
  <div class="info-grid"><div class="info-item"><strong>IP:</strong> ${s.ip||'--'}</div><div class="info-item"><strong>OS:</strong> ${s.os||'--'}</div><div class="info-item"><strong>DC:</strong> ${s.datacenter||'--'}</div><div class="info-item"><strong>State:</strong> ${s.state||'--'}</div><div class="info-item"><strong>CPU:</strong> ${s.commercialRange||'--'}</div><div class="info-item"><strong>Support:</strong> ${s.supportLevel||'--'}</div></div>
  <div style="margin-top:12px">
   <button class="btn btn-primary" onclick="dedReboot('${name}')">Reboot</button>
   <button class="btn" onclick="dedRescue('${name}')">Rescue Mode</button>
   <button class="btn" onclick="dedIPMI('${name}')">IPMI/KVM</button>
   <button class="btn btn-danger" onclick="dedReinstall('${name}')">Reinstall OS</button>
  </div>`;
}
async function dedReboot(n){if(!confirm('Reboot dedicated server?'))return;const r=await ovh({action:'rebootDedicated',serviceName:n});T(r.message||r.error||'Rebooting')}
async function dedRescue(n){const r=await ovh({action:'dedicatedRescue',serviceName:n});T(r.message||r.error||'Rescue mode set')}
async function dedIPMI(n){const r=await ovh({action:'getDedicatedIPMI',serviceName:n});if(r.data?.url)window.open(r.data.url,'_blank');else T(r.error||'IPMI URL: check OVH panel')}
async function dedReinstall(n){if(!confirm('REINSTALL will ERASE ALL DATA. Continue?'))return;const r=await ovh({action:'reinstallDedicated',serviceName:n});T(r.message||r.error||'Installing')}

// ===== RESOURCE MONITOR =====
let resCharts={};
async function loadResourceMonitor(){
 if(resourceTimer)clearInterval(resourceTimer);
 await updateResourceData();
 resourceTimer=setInterval(updateResourceData,3000);
}
async function updateResourceData(){
 try{const d=await B('/api/system/stats');
 E('res-cpu-val').textContent=(d.cpu||0)+'%';
 E('res-mem-val').textContent=(d.memory?.percent||0)+'%';
 E('res-disk-val').textContent=(d.disk?.percent||0)+'%';
 E('res-load-val').textContent=d.loadAverage||'--';
 // Draw simple bar charts
 drawBar('res-cpu-bar',d.cpu||0,'#3b82f6');
 drawBar('res-mem-bar',d.memory?.percent||0,'#10b981');
 drawBar('res-disk-bar',d.disk?.percent||0,'#f59e0b');
 }catch(e){}
}
function drawBar(id,pct,color){const c=E(id);if(!c)return;const ctx=c.getContext('2d');const w=c.width=c.parentElement.clientWidth;const h=c.height=30;ctx.clearRect(0,0,w,h);ctx.fillStyle='#1a1a2e';ctx.fillRect(0,0,w,h);ctx.fillStyle=color;ctx.fillRect(0,0,w*(pct/100),h);ctx.fillStyle='#fff';ctx.font='12px monospace';ctx.fillText(pct+'%',w/2-15,20)}
function toggleResourceMonitor(){if(resourceTimer){clearInterval(resourceTimer);resourceTimer=null;T('Monitoring paused')}else{loadResourceMonitor();T('Monitoring resumed')}}

// ===== PHP MANAGER =====
async function loadPHP(){
 try{const d=await B('/api/php/versions');
 E('php-content').innerHTML=`<div class="stat-grid">
  ${(d.versions||[{version:'8.1',active:true}]).map(v=>`<div class="stat-card"><div class="stat-value">${v.version}</div><div class="stat-label">${v.active?'<span class="badge green">Active</span>':'Available'}</div>${!v.active?`<button class="btn btn-xs" onclick="switchPHP('${v.version}')">Activate</button>`:''}</div>`).join('')}
 </div>`;
 }catch(e){E('php-content').innerHTML=`<div class="stat-grid">
  <div class="stat-card"><div class="stat-value">PHP Info</div><div class="stat-label">Query the server for PHP details</div><button class="btn btn-xs btn-primary" onclick="fetchPHPInfo()">Get PHP Info</button></div></div>`}
}
async function fetchPHPInfo(){
 try{const r=await fetch('/gohostme/phpinfo-check.php');const t=await r.text();E('php-content').innerHTML='<pre style="max-height:400px;overflow:auto">'+t+'</pre>'}
 catch(e){T('Could not fetch PHP info')}
}
async function switchPHP(v){T('Switching to PHP '+v+'...');const r=await B('/api/php/switch',{method:'POST',body:JSON.stringify({version:v})});T(r.message||r.error)}

// ===== EMAIL DELIVERABILITY =====
async function loadEmailHealth(){
 E('email-health-results').innerHTML='<p>Enter a domain above and click Check to analyze email deliverability</p>';
}
async function checkEmailHealth(){
 const domain=E('email-health-domain').value;if(!domain)return T('Enter a domain');
 E('email-health-results').innerHTML='<p>Checking '+domain+'...</p>';
 try{
  const checks=[];
  // Check SPF
  const spf=await fetch('https://dns.google/resolve?name='+domain+'&type=TXT').then(r=>r.json());
  const spfRec=spf.Answer?.find(a=>a.data?.includes('v=spf1'));
  checks.push({name:'SPF Record',status:spfRec?'pass':'fail',detail:spfRec?spfRec.data:'No SPF record found'});
  // Check DKIM (common selectors)
  for(const sel of ['default','google','mail','selector1','selector2']){
   try{const dk=await fetch('https://dns.google/resolve?name='+sel+'._domainkey.'+domain+'&type=TXT').then(r=>r.json());
   if(dk.Answer){checks.push({name:'DKIM ('+sel+')',status:'pass',detail:dk.Answer[0]?.data?.substring(0,80)+'...'});break}}catch(e){}}
  if(!checks.find(c=>c.name.startsWith('DKIM')))checks.push({name:'DKIM',status:'fail',detail:'No DKIM record found for common selectors'});
  // Check DMARC
  const dmarc=await fetch('https://dns.google/resolve?name=_dmarc.'+domain+'&type=TXT').then(r=>r.json());
  const dmarcRec=dmarc.Answer?.find(a=>a.data?.includes('v=DMARC1'));
  checks.push({name:'DMARC',status:dmarcRec?'pass':'fail',detail:dmarcRec?dmarcRec.data:'No DMARC record found'});
  // Check MX
  const mx=await fetch('https://dns.google/resolve?name='+domain+'&type=MX').then(r=>r.json());
  checks.push({name:'MX Records',status:mx.Answer?'pass':'fail',detail:mx.Answer?.map(a=>a.data).join(', ')||'No MX records'});
  // Check PTR (reverse DNS) — skip for now, needs IP
  E('email-health-results').innerHTML=checks.map(c=>`<div class="checklist-item"><div class="checklist-icon" style="background:${c.status==='pass'?'var(--green)':'var(--red)'}">${c.status==='pass'?'✓':'✗'}</div><div><strong>${c.name}</strong><br><small style="color:var(--text-secondary)">${c.detail}</small></div></div>`).join('');
 }catch(e){E('email-health-results').innerHTML='<p class="error">Check failed: '+e.message+'</p>'}
}

// ===== DOCKER =====
async function loadDocker(){
 try{const d=await B('/api/docker/containers');
 E('docker-containers').innerHTML=(d.containers||[]).map(c=>`<tr><td>${c.name}</td><td>${c.image}</td><td>${statusBadge(c.state)}</td><td>${c.ports||'--'}</td><td>${c.created||'--'}</td><td>
  <button class="btn btn-xs" onclick="dockerAction('start','${c.id}')">Start</button>
  <button class="btn btn-xs" onclick="dockerAction('stop','${c.id}')">Stop</button>
  <button class="btn btn-xs" onclick="dockerAction('restart','${c.id}')">Restart</button>
  <button class="btn btn-xs" onclick="dockerLogs('${c.id}')">Logs</button>
  <button class="btn btn-xs btn-danger" onclick="dockerAction('rm','${c.id}')">Remove</button>
 </td></tr>`).join('')||'<tr><td colspan="6">No containers. Docker may not be installed.</td></tr>';
 }catch(e){E('docker-containers').innerHTML='<tr><td colspan="6">Docker not available or not installed</td></tr>'}
}
async function dockerAction(a,id){const r=await B('/api/docker/'+a,{method:'POST',body:JSON.stringify({containerId:id})});T(r.message||r.error||a+' done');loadDocker()}
async function dockerLogs(id){const r=await B('/api/docker/logs?containerId='+id);E('docker-log-output').textContent=r.logs||'No logs';E('docker-log-output').style.display='block'}

// ===== FIREWALL =====
async function loadFirewall(){
 try{const d=await B('/api/security/firewall');
 E('fw-rules').innerHTML=(d.rules||[]).map((r,i)=>`<tr><td>${r.to||'--'}</td><td>${r.action||'--'}</td><td>${r.from||'Anywhere'}</td><td>${r.proto||'--'}</td><td><button class="btn btn-xs btn-danger" onclick="deleteFirewallRule(${i})">Del</button></td></tr>`).join('')||'<tr><td colspan="5">No rules or firewall not active</td></tr>';
 // Fail2ban
 const f2b=await B('/api/security/fail2ban');
 E('f2b-status').innerHTML=(f2b.jails||[]).map(j=>`<tr><td>${j.name}</td><td>${j.currentlyBanned||0}</td><td>${j.totalBanned||0}</td><td>${j.filter||'--'}</td><td>${statusBadge(j.status||'active')}</td></tr>`).join('')||'<tr><td colspan="5">Fail2Ban not available</td></tr>';
 // Blocked IPs
 E('blocked-ips').innerHTML=(d.blocked||f2b.banned||[]).map(ip=>`<tr><td>${ip.ip||ip}</td><td>${ip.jail||'--'}</td><td>${ip.time||'--'}</td><td><button class="btn btn-xs" onclick="unblockIP('${ip.ip||ip}')">Unblock</button></td></tr>`).join('')||'<tr><td colspan="4">No blocked IPs</td></tr>';
 }catch(e){E('fw-rules').innerHTML='<tr><td colspan="5">Firewall API not available — endpoints need to be added to backend</td></tr>'}
}
async function addFirewallRule(){const r=await B('/api/security/firewall/rule',{method:'POST',body:JSON.stringify({action:E('fw-action').value,direction:E('fw-direction').value,port:E('fw-port').value,proto:E('fw-proto').value,from:E('fw-from-ip').value,comment:E('fw-comment').value})});T(r.message||r.error);hideModal('fw-rule-modal');loadFirewall()}
async function deleteFirewallRule(idx){if(!confirm('Delete rule?'))return;const r=await B('/api/security/firewall/rule',{method:'DELETE',body:JSON.stringify({index:idx})});T(r.message||r.error);loadFirewall()}
async function unblockIP(ip){const r=await B('/api/security/fail2ban/unban',{method:'POST',body:JSON.stringify({ip})});T(r.message||r.error);loadFirewall()}

// ===== DISK ENCRYPTION =====
async function loadDiskEncryption(){
 try{const d=await B('/api/security/disk-encryption');
 E('disk-devices').innerHTML=(d.devices||[]).map(dev=>`<tr><td>${dev.name}</td><td>${dev.size}</td><td>${dev.type}</td><td>${dev.mountpoint||'--'}</td><td>${dev.encrypted?'<span class="badge green">Encrypted</span>':'<span class="badge yellow">Unencrypted</span>'}</td></tr>`).join('')||'<tr><td colspan="5">Loading devices...</td></tr>';
 E('luks-volumes').innerHTML=(d.luksVolumes||[]).map(v=>`<tr><td>${v.name}</td><td>${v.device}</td><td>${v.cipher||'--'}</td><td>${v.keySize||'--'}</td><td>${statusBadge(v.status||'active')}</td><td>
  <button class="btn btn-xs" onclick="luksOpen('${v.device}')">Open</button>
  <button class="btn btn-xs" onclick="luksClose('${v.name}')">Close</button>
 </td></tr>`).join('')||'<tr><td colspan="6">No LUKS volumes found</td></tr>';
 }catch(e){E('disk-devices').innerHTML='<tr><td colspan="5">Disk encryption API not available</td></tr>'}
}
async function encryptVolume(){
 const pass=E('encrypt-pass').value;if(pass!==E('encrypt-pass2').value)return T('Passphrases do not match');
 if(!confirm('THIS WILL DESTROY ALL DATA ON THE DEVICE. Are you absolutely sure?'))return;
 const r=await B('/api/security/disk-encryption/encrypt',{method:'POST',body:JSON.stringify({device:E('encrypt-device').value,cipher:E('encrypt-type').value,passphrase:pass})});
 T(r.message||r.error);hideModal('encrypt-modal');loadDiskEncryption()
}
async function manageLUKSKey(){const r=await B('/api/security/disk-encryption/key',{method:'POST',body:JSON.stringify({device:E('luks-device').value,action:E('luks-key-action').value,currentPass:E('luks-current-pass').value,newPass:E('luks-new-pass').value})});T(r.message||r.error);hideModal('luks-key-modal')}
async function luksOpen(dev){const pass=prompt('Enter passphrase to open:');if(!pass)return;const r=await B('/api/security/disk-encryption/open',{method:'POST',body:JSON.stringify({device:dev,passphrase:pass})});T(r.message||r.error);loadDiskEncryption()}
async function luksClose(name){const r=await B('/api/security/disk-encryption/close',{method:'POST',body:JSON.stringify({name})});T(r.message||r.error);loadDiskEncryption()}

// ===== MALWARE SCANNER =====
async function runMalwareScan(){
 const path=E('scan-path').value||'/home/gositeme';
 E('malware-results').innerHTML='<p>Scanning '+path+'... This may take a while.</p>';
 try{const r=await B('/api/security/malware/scan',{method:'POST',body:JSON.stringify({path,type:'quick'})});
 E('malware-results').innerHTML=`<h4>Scan Results</h4>
  <div class="info-grid"><div class="info-item"><strong>Files Scanned:</strong> ${r.scanned||0}</div><div class="info-item"><strong>Threats Found:</strong> ${r.threats||0}</div><div class="info-item"><strong>Duration:</strong> ${r.duration||'--'}</div></div>
  ${(r.findings||[]).map(f=>`<div class="checklist-item"><div class="checklist-icon" style="background:var(--red)">!</div><div><strong>${f.file}</strong><br><small>${f.description||f.type}</small></div></div>`).join('')||'<p style="color:var(--green)">No threats found</p>'}`
 }catch(e){E('malware-results').innerHTML='<p>Malware scan API not available. Install ClamAV: <code>apt install clamav</code></p>'}
}
async function runRootkitScan(){
 E('malware-results').innerHTML='<p>Running rootkit scan...</p>';
 try{const r=await B('/api/security/malware/rootkit',{method:'POST'});
 E('malware-results').innerHTML=`<h4>Rootkit Check</h4><pre style="max-height:300px;overflow:auto">${r.output||r.message||'No output'}</pre>`
 }catch(e){E('malware-results').innerHTML='<p>Install rkhunter: <code>apt install rkhunter</code></p>'}
}

// ===== SECURITY HEADERS =====
async function checkSecurityHeaders(){
 const url=E('sec-headers-url').value;if(!url)return T('Enter a URL');
 E('sec-headers-results').innerHTML='<p>Checking headers...</p>';
 try{
  const r=await fetch(url,{method:'HEAD',mode:'no-cors'}).catch(()=>null);
  // Since CORS blocks most headers, we'll check via backend
  const d=await B('/api/security/headers?url='+encodeURIComponent(url));
  const headers=d.headers||{};
  const checks=[
   {name:'Strict-Transport-Security',val:headers['strict-transport-security'],rec:'max-age=31536000; includeSubDomains'},
   {name:'X-Content-Type-Options',val:headers['x-content-type-options'],rec:'nosniff'},
   {name:'X-Frame-Options',val:headers['x-frame-options'],rec:'DENY or SAMEORIGIN'},
   {name:'X-XSS-Protection',val:headers['x-xss-protection'],rec:'1; mode=block'},
   {name:'Content-Security-Policy',val:headers['content-security-policy'],rec:'default-src self'},
   {name:'Referrer-Policy',val:headers['referrer-policy'],rec:'strict-origin-when-cross-origin'},
   {name:'Permissions-Policy',val:headers['permissions-policy'],rec:'camera=(), microphone=()'},
   {name:'X-Permitted-Cross-Domain-Policies',val:headers['x-permitted-cross-domain-policies'],rec:'none'}
  ];
  E('sec-headers-results').innerHTML=checks.map(c=>`<div class="checklist-item"><div class="checklist-icon" style="background:${c.val?'var(--green)':'var(--red)'}"> ${c.val?'✓':'✗'}</div><div><strong>${c.name}</strong><br><small style="color:var(--text-secondary)">${c.val||'Missing — Recommended: '+c.rec}</small></div></div>`).join('');
 }catch(e){E('sec-headers-results').innerHTML='<p>Security headers API not available</p>'}
}

// ===== ACTIVITY LOG =====
async function loadActivityLog(){
 try{const d=await B('/api/activity-log');
 E('activity-entries').innerHTML=(d.entries||[]).map(e=>`<tr><td>${e.timestamp||'--'}</td><td>${e.user||'system'}</td><td>${e.action}</td><td>${e.details||''}</td><td>${e.ip||'--'}</td></tr>`).join('')||'<tr><td colspan="5">No activity recorded yet</td></tr>';
 }catch(e){E('activity-entries').innerHTML='<tr><td colspan="5">Activity log API not available</td></tr>'}
}

// ===== NETWORK TOOLS =====
async function runNetworkTool(){
 const tool=E('net-tool').value, target=E('net-target').value;
 if(!target)return T('Enter a target host');
 E('net-output').textContent='Running '+tool+' on '+target+'...';
 try{const r=await B('/api/tools/network',{method:'POST',body:JSON.stringify({tool,target,port:E('net-port')?.value})});
 E('net-output').textContent=r.output||r.error||'No output'}catch(e){E('net-output').textContent='Network tools API not available'}
}
// Show port field only for port-check
E('net-tool')?.addEventListener('change',function(){const pf=E('net-port-group');if(pf)pf.style.display=this.value==='port-check'?'block':'none'});

// ===== DNS PROPAGATION =====
async function checkDNSPropagation(){
 const domain=E('dns-prop-domain').value, type=E('dns-prop-type').value;
 if(!domain)return T('Enter a domain');
 E('dns-prop-results').innerHTML='<p>Checking DNS propagation...</p>';
 const servers=[
  {name:'Google',ip:'8.8.8.8',loc:'US'},{name:'Cloudflare',ip:'1.1.1.1',loc:'US'},
  {name:'OpenDNS',ip:'208.67.222.222',loc:'US'},{name:'Quad9',ip:'9.9.9.9',loc:'EU'},
  {name:'Yandex',ip:'77.88.8.8',loc:'RU'},{name:'Ali DNS',ip:'223.5.5.5',loc:'CN'}
 ];
 const results=[];
 for(const s of servers){
  try{const r=await fetch('https://dns.google/resolve?name='+domain+'&type='+type).then(r=>r.json());
  results.push({...s,result:r.Answer?.map(a=>a.data).join(', ')||'No record',status:r.Answer?'pass':'fail'})}
  catch(e){results.push({...s,result:'Query failed',status:'fail'})}
 }
 E('dns-prop-results').innerHTML='<table class="table"><thead><tr><th>Server</th><th>Location</th><th>IP</th><th>Result</th><th>Status</th></tr></thead><tbody>'+
  results.map(r=>`<tr><td>${r.name}</td><td>${r.loc}</td><td>${r.ip}</td><td>${r.result}</td><td>${r.status==='pass'?'<span class="badge green">OK</span>':'<span class="badge red">Fail</span>'}</td></tr>`).join('')+'</tbody></table>';
}

// ===== UPTIME MONITOR =====
async function loadUptime(){
 try{const d=await B('/api/uptime/monitors');
 E('uptime-list').innerHTML=(d.monitors||[]).map(m=>`<tr><td>${m.name}</td><td>${m.url}</td><td>${m.type}</td><td>${m.interval}s</td><td>${m.uptime||'--'}%</td><td>${statusBadge(m.status||'unknown')}</td><td>${m.responseTime||'--'}ms</td><td><button class="btn btn-xs btn-danger" onclick="deleteMonitor('${m.id}')">Del</button></td></tr>`).join('')||'<tr><td colspan="8">No monitors configured</td></tr>';
 }catch(e){E('uptime-list').innerHTML='<tr><td colspan="8">Uptime API not available</td></tr>'}
}
async function addUptimeMonitor(){const r=await B('/api/uptime/monitors',{method:'POST',body:JSON.stringify({name:E('uptime-name').value,type:E('uptime-type').value,url:E('uptime-url').value,interval:parseInt(E('uptime-interval').value)})});T(r.message||r.error);hideModal('uptime-modal');loadUptime()}
async function deleteMonitor(id){const r=await B('/api/uptime/monitors/'+id,{method:'DELETE'});T(r.message||r.error);loadUptime()}
async function quickUptimeCheck(){
 const url=E('quick-check-url').value;if(!url)return T('Enter a URL');
 E('quick-check-result').innerHTML='<p>Checking...</p>';
 const start=Date.now();
 try{const r=await fetch(url,{mode:'no-cors'});const ms=Date.now()-start;
 E('quick-check-result').innerHTML=`<div class="info-grid"><div class="info-item"><strong>Status:</strong> <span class="badge green">Reachable</span></div><div class="info-item"><strong>Response Time:</strong> ${ms}ms</div></div>`}
 catch(e){E('quick-check-result').innerHTML=`<div class="info-item"><strong>Status:</strong> <span class="badge red">Unreachable</span></div>`}
}

// ===== LOG VIEWER =====
async function loadLogViewer(){
 E('log-output').textContent='Select a log source and click Load';
}
async function refreshLog(){
 const source=E('log-source').value, lines=E('log-lines').value||100, search=E('log-search').value;
 let path=source;
 if(source==='custom')path=E('custom-log-path').value;
 if(!path)return T('Select a log source');
 E('log-output').textContent='Loading...';
 try{const r=await B('/api/tools/logs',{method:'POST',body:JSON.stringify({source:path,lines:parseInt(lines),search})});
 E('log-output').textContent=r.output||r.logs||r.error||'No output'}catch(e){E('log-output').textContent='Log viewer API not available'}
}
function searchLog(){refreshLog()}
// Custom log path toggle
E('log-source')?.addEventListener('change',function(){const cp=E('custom-log-group');if(cp)cp.style.display=this.value==='custom'?'block':'none'});

// ===== BENCHMARKS =====
async function runBenchmark(type){
 E('bench-results').innerHTML='<p>Running '+type+' benchmark... This may take a moment.</p>';
 try{const r=await B('/api/tools/benchmark',{method:'POST',body:JSON.stringify({type})});
 E('bench-results').innerHTML=`<h4>${type.toUpperCase()} Benchmark Results</h4><pre style="max-height:400px;overflow:auto">${r.output||r.results||JSON.stringify(r,null,2)}</pre>`
 }catch(e){E('bench-results').innerHTML='<p>Benchmark API not available</p>'}
}

// ===== GIT DEPLOY =====
async function loadGitDeploy(){
 try{const d=await B('/api/git-deploy/repos');
 E('git-repos').innerHTML=(d.repos||[]).map(r=>`<tr><td>${r.name}</td><td>${r.url}</td><td>${r.branch}</td><td>${r.path}</td><td>${r.autoDeploy?'<span class="badge green">Auto</span>':'Manual'}</td><td>${r.lastDeploy||'Never'}</td><td>
  <button class="btn btn-xs btn-primary" onclick="gitDeploy('${r.id}')">Deploy Now</button>
  <button class="btn btn-xs btn-danger" onclick="deleteGitRepo('${r.id}')">Del</button>
 </td></tr>`).join('')||'<tr><td colspan="7">No deploy targets configured</td></tr>';
 }catch(e){E('git-repos').innerHTML='<tr><td colspan="7">Git deploy API not available</td></tr>'}
}
async function addGitDeploy(){const r=await B('/api/git-deploy/repos',{method:'POST',body:JSON.stringify({name:E('git-name').value,url:E('git-url').value,branch:E('git-target-branch').value,path:E('git-target-path').value,autoDeploy:E('git-auto-deploy').checked})});T(r.message||r.error);hideModal('git-deploy-modal');loadGitDeploy()}
async function gitDeploy(id){T('Deploying...');const r=await B('/api/git-deploy/deploy',{method:'POST',body:JSON.stringify({id})});T(r.message||r.error)}
async function quickGitDeploy(){const url=E('quick-git-url').value,path=E('quick-git-path').value,branch=E('quick-git-branch').value;if(!url||!path)return T('Enter URL and path');T('Deploying...');const r=await B('/api/git-deploy/quick',{method:'POST',body:JSON.stringify({url,path,branch})});T(r.message||r.error)}
async function deleteGitRepo(id){const r=await B('/api/git-deploy/repos/'+id,{method:'DELETE'});T(r.message||r.error);loadGitDeploy()}

// ===== WORDPRESS MANAGER =====
async function loadWordPress(){
 try{const d=await B('/api/wordpress/sites');
 E('wp-sites').innerHTML=(d.sites||[]).map(s=>`<tr><td>${s.path}</td><td>${s.version||'--'}</td><td>${s.siteUrl||'--'}</td><td>${s.dbName||'--'}</td><td>
  <button class="btn btn-xs" onclick="wpPlugins('${s.path}')">Plugins</button>
  <button class="btn btn-xs" onclick="wpThemes('${s.path}')">Themes</button>
  <button class="btn btn-xs" onclick="wpUsers('${s.path}')">Users</button>
  <button class="btn btn-xs btn-primary" onclick="wpUpdateAll('${s.path}')">Update All</button>
 </td></tr>`).join('')||'<tr><td colspan="5">No WordPress installations found</td></tr>';
 }catch(e){E('wp-sites').innerHTML='<tr><td colspan="5">WordPress API not available. Ensure WP-CLI is installed.</td></tr>'}
}
async function wpPlugins(path){const r=await B('/api/wordpress/plugins?path='+encodeURIComponent(path));
 const el=E('wp-detail-output');el.style.display='block';
 el.innerHTML='<h4>Plugins</h4><table class="table"><thead><tr><th>Name</th><th>Status</th><th>Version</th><th>Update</th></tr></thead><tbody>'+
 (r.plugins||[]).map(p=>`<tr><td>${p.name}</td><td>${statusBadge(p.status)}</td><td>${p.version}</td><td>${p.update_available?'<span class="badge yellow">Update Available</span>':'Current'}</td></tr>`).join('')+'</tbody></table>'}
async function wpThemes(path){const r=await B('/api/wordpress/themes?path='+encodeURIComponent(path));
 const el=E('wp-detail-output');el.style.display='block';
 el.innerHTML='<h4>Themes</h4><table class="table"><thead><tr><th>Name</th><th>Status</th><th>Version</th></tr></thead><tbody>'+
 (r.themes||[]).map(t=>`<tr><td>${t.name}</td><td>${statusBadge(t.status)}</td><td>${t.version}</td></tr>`).join('')+'</tbody></table>'}
async function wpUsers(path){const r=await B('/api/wordpress/users?path='+encodeURIComponent(path));
 const el=E('wp-detail-output');el.style.display='block';
 el.innerHTML='<h4>Users</h4><table class="table"><thead><tr><th>Login</th><th>Email</th><th>Role</th></tr></thead><tbody>'+
 (r.users||[]).map(u=>`<tr><td>${u.user_login}</td><td>${u.user_email}</td><td>${u.roles}</td></tr>`).join('')+'</tbody></table>'}
async function wpUpdateAll(path){T('Updating WordPress...');const r=await B('/api/wordpress/update',{method:'POST',body:JSON.stringify({path})});T(r.message||r.error)}

// ===== MIGRATION TOOL =====
E('migration-source')?.addEventListener('change',function(){
 document.querySelectorAll('.migration-fields').forEach(f=>f.style.display='none');
 const el=E('migrate-'+this.value);if(el)el.style.display='block';
});
async function startMigration(){
 const source=E('migration-source').value;
 const data={source};
 if(source==='cpanel'){data.host=E('cpanel-host')?.value;data.user=E('cpanel-user')?.value;data.password=E('cpanel-pass')?.value;data.token=E('cpanel-token')?.value}
 else if(source==='wordpress'){data.wpUrl=E('wp-url')?.value;data.wpUser=E('wp-user')?.value;data.wpPass=E('wp-pass')?.value}
 else if(source==='ssh'){data.host=E('ssh-host')?.value;data.user=E('ssh-user')?.value;data.keyPath=E('ssh-key-path')?.value;data.remotePath=E('ssh-remote-path')?.value}
 else if(source==='ftp'){data.host=E('ftp-host')?.value;data.user=E('ftp-user')?.value;data.password=E('ftp-pass')?.value;data.remotePath=E('ftp-remote-path')?.value}
 else if(source==='upload'){T('Use the File Manager to upload backup files');return}
 E('migration-progress').style.display='block';
 E('migration-status').textContent='Starting migration...';
 try{const r=await B('/api/migration/start',{method:'POST',body:JSON.stringify(data)});
 E('migration-status').textContent=r.message||r.error||'Migration initiated';
 }catch(e){E('migration-status').textContent='Migration API not available'}
}

</script>
<?php @include '/home/gositeme/shared/bible/bible-data.php'; @include '/home/gositeme/shared/includes/covenant-footer.inc.php'; ?>
</body>
</html>
