<?php
/**
 * GoHostMe — Management Dashboard v2.0
 * Complete control panel: all 48 backend endpoints + full VPS/Dedicated server management
 * Bug fixes from v1, plus: file editor, SQL query tool, PM2 logs, system processes,
 * services, Apache vhosts, DA config, rename/chmod, VPS reboot/rescue/console/snapshots,
 * dedicated server reboot/rescue/IPMI/hardware/reinstall
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GoHostMe — Control Panel</title>
<link rel="icon" href="/favicon.ico">
<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{--bg:#0a0a0f;--bg2:#111118;--bg3:#161622;--border:#1e1e30;--text:#e0e0e8;--text2:#888;--accent:#00d4ff;--accent2:#7b2fff;--green:#00ff88;--red:#ff4455;--orange:#ff9933;--yellow:#ffd700;--radius:10px}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
a{color:var(--accent);text-decoration:none}
button{cursor:pointer;font-family:inherit}
code{font-family:'Fira Code','Courier New',monospace;font-size:13px;background:var(--bg);padding:2px 6px;border-radius:4px}

/* Login */
#login-screen{display:flex;align-items:center;justify-content:center;min-height:100vh;background:var(--bg)}
.login-box{background:var(--bg2);border:1px solid var(--border);border-radius:16px;padding:48px;width:100%;max-width:420px;text-align:center}
.login-box .logo{font-size:28px;font-weight:900;background:linear-gradient(135deg,var(--accent),var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;margin-bottom:8px}
.login-box .sub{color:var(--text2);font-size:14px;margin-bottom:32px}
.login-box input{width:100%;padding:14px 16px;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);color:var(--text);font-size:15px;margin-bottom:16px;outline:none;transition:border .2s}
.login-box input:focus{border-color:var(--accent)}
.login-box .btn-login{width:100%;padding:14px;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;border:none;border-radius:var(--radius);font-size:16px;font-weight:700;transition:all .3s;cursor:pointer}
.login-box .btn-login:hover{transform:translateY(-2px);box-shadow:0 8px 30px rgba(0,212,255,.3)}
.login-box .error{color:var(--red);font-size:13px;margin-top:12px;display:none}

/* Layout */
#app{display:none;min-height:100vh}
.topbar{background:var(--bg2);border-bottom:1px solid var(--border);padding:12px 24px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100}
.topbar .logo{font-size:20px;font-weight:800;background:linear-gradient(135deg,var(--accent),var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.topbar .user-info{display:flex;align-items:center;gap:12px;font-size:14px;color:var(--text2)}
.topbar .user-info .name{color:var(--text);font-weight:600}
.topbar .logout-btn{background:transparent;border:1px solid var(--border);color:var(--text2);padding:6px 14px;border-radius:6px;font-size:13px;transition:all .2s}
.topbar .logout-btn:hover{border-color:var(--red);color:var(--red)}
.topbar .mobile-toggle{display:none;background:none;border:none;color:var(--text);font-size:22px;padding:4px 8px}
.layout{display:flex;min-height:calc(100vh - 52px)}

/* Sidebar */
.sidebar{width:220px;background:var(--bg2);border-right:1px solid var(--border);padding:16px 0;flex-shrink:0;overflow-y:auto;height:calc(100vh - 52px);position:sticky;top:52px}
.sidebar .section-title{font-size:10px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:1px;padding:8px 20px;margin-top:8px}
.sidebar .nav-item{display:flex;align-items:center;gap:10px;padding:9px 20px;font-size:13px;color:var(--text2);cursor:pointer;transition:all .15s;border-left:3px solid transparent}
.sidebar .nav-item:hover{background:rgba(0,212,255,.05);color:var(--text)}
.sidebar .nav-item.active{background:rgba(0,212,255,.08);color:var(--accent);border-left-color:var(--accent);font-weight:600}
.sidebar .nav-item .icon{font-size:15px;width:18px;text-align:center}

/* Main */
.main{flex:1;padding:24px;overflow-y:auto;max-height:calc(100vh - 52px)}
.page-title{font-size:24px;font-weight:800;margin-bottom:8px}
.page-sub{color:var(--text2);font-size:14px;margin-bottom:24px}

/* Stat Cards */
.stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px}
.stat-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);padding:20px}
.stat-card .label{font-size:11px;color:var(--text2);text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px}
.stat-card .value{font-size:26px;font-weight:900}
.stat-card .value.cyan{color:var(--accent)}
.stat-card .value.green{color:var(--green)}
.stat-card .value.red{color:var(--red)}
.stat-card .value.orange{color:var(--orange)}
.stat-card .sub{font-size:12px;color:var(--text2);margin-top:4px}

/* Panels & Tables */
.panel{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:24px;overflow:hidden}
.panel-header{padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px}
.panel-header h3{font-size:15px;font-weight:700}
.panel-body{padding:20px;overflow-x:auto}
table{width:100%;border-collapse:collapse}
table th{text-align:left;padding:9px 12px;font-size:11px;color:var(--text2);text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border);background:var(--bg3);white-space:nowrap}
table td{padding:9px 12px;font-size:13px;border-bottom:1px solid rgba(30,30,48,.5)}
table tr:hover td{background:rgba(0,212,255,.02)}
.badge{display:inline-block;padding:3px 10px;border-radius:12px;font-size:11px;font-weight:700;white-space:nowrap}
.badge.green{background:rgba(0,255,136,.15);color:var(--green)}
.badge.red{background:rgba(255,68,85,.15);color:var(--red)}
.badge.orange{background:rgba(255,153,51,.15);color:var(--orange)}
.badge.blue{background:rgba(0,212,255,.15);color:var(--accent)}
.badge.yellow{background:rgba(255,215,0,.15);color:var(--yellow)}

/* Buttons */
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

/* Forms */
.form-group{margin-bottom:14px}
.form-group label{display:block;font-size:12px;color:var(--text2);margin-bottom:5px;font-weight:600}
.form-group input,.form-group select,.form-group textarea{width:100%;padding:10px 12px;background:var(--bg);border:1px solid var(--border);border-radius:6px;color:var(--text);font-size:13px;outline:none;font-family:inherit}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{border-color:var(--accent)}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}

/* Loading */
.loading{text-align:center;padding:40px;color:var(--text2)}
.loading::after{content:'';display:inline-block;width:18px;height:18px;border:2px solid var(--border);border-top-color:var(--accent);border-radius:50%;animation:spin .8s linear infinite;margin-left:8px;vertical-align:middle}
@keyframes spin{to{transform:rotate(360deg)}}

/* Toast */
.toast{position:fixed;bottom:24px;right:24px;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);padding:14px 20px;font-size:14px;z-index:1000;transform:translateY(100px);opacity:0;transition:all .3s;max-width:400px}
.toast.show{transform:translateY(0);opacity:1}
.toast.success{border-color:var(--green);color:var(--green)}
.toast.error{border-color:var(--red);color:var(--red)}

/* Pages */
.page{display:none}
.page.active{display:block}

/* Terminal / Code */
.terminal-out{background:#000;border:1px solid #333;border-radius:6px;padding:14px;font-family:'Fira Code','Courier New',monospace;font-size:12px;color:#ccc;max-height:400px;overflow-y:auto;white-space:pre-wrap;word-break:break-all}
.code-editor{width:100%;min-height:300px;background:#000;border:1px solid #333;border-radius:6px;padding:14px;font-family:'Fira Code','Courier New',monospace;font-size:13px;color:#ccc;resize:vertical;tab-size:4;-moz-tab-size:4;outline:none}

/* Modal */
.modal-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.6);z-index:200;display:none;align-items:center;justify-content:center}
.modal-overlay.show{display:flex}
.modal{background:var(--bg2);border:1px solid var(--border);border-radius:16px;padding:28px;max-width:560px;width:92%;max-height:85vh;overflow-y:auto}
.modal h3{font-size:18px;font-weight:800;margin-bottom:16px}
.modal .close{float:right;background:none;border:none;color:var(--text2);font-size:22px;cursor:pointer;line-height:1}

/* Tabs */
.tab-bar{display:flex;gap:0;border-bottom:1px solid var(--border);margin-bottom:16px}
.tab-bar .tab{padding:10px 18px;font-size:13px;color:var(--text2);cursor:pointer;border-bottom:2px solid transparent;transition:all .2s}
.tab-bar .tab.active{color:var(--accent);border-bottom-color:var(--accent)}
.tab-bar .tab:hover{color:var(--text)}
.tab-content{display:none}
.tab-content.active{display:block}

/* Responsive */
@media(max-width:768px){
.sidebar{position:fixed;left:-220px;top:52px;height:calc(100vh - 52px);z-index:50;transition:left .3s}
.sidebar.open{left:0}
.main{padding:16px}
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

   <div class="section-title">Infrastructure</div>
   <div class="nav-item" data-page="domains"><span class="icon">&#127760;</span> Domains</div>
   <div class="nav-item" data-page="dns"><span class="icon">&#128225;</span> DNS</div>
   <div class="nav-item" data-page="databases"><span class="icon">&#128451;</span> Databases</div>
   <div class="nav-item" data-page="files"><span class="icon">&#128193;</span> File Manager</div>
   <div class="nav-item" data-page="ssl"><span class="icon">&#128274;</span> SSL/TLS</div>
   <div class="nav-item" data-page="apache"><span class="icon">&#9881;</span> Apache</div>

   <div class="section-title">Services</div>
   <div class="nav-item" data-page="pm2"><span class="icon">&#9881;&#65039;</span> PM2</div>
   <div class="nav-item" data-page="email"><span class="icon">&#128231;</span> Email</div>
   <div class="nav-item" data-page="cron"><span class="icon">&#9200;</span> Cron Jobs</div>
   <div class="nav-item" data-page="backups"><span class="icon">&#128190;</span> Backups</div>

   <div class="section-title">AI Development</div>
   <div class="nav-item" data-page="ide"><span class="icon">&#128187;</span> Alfred IDE</div>

   <div class="section-title">Servers</div>
   <div class="nav-item" data-page="cloud"><span class="icon">&#9729;&#65039;</span> Cloud VPS</div>
   <div class="nav-item" data-page="vps"><span class="icon">&#128421;</span> VPS Services</div>
   <div class="nav-item" data-page="dedicated"><span class="icon">&#127959;</span> Dedicated</div>

   <div class="section-title">Quick Links</div>
   <div class="nav-item" onclick="window.open('/alfred-ide/','_blank')"><span class="icon">&#128640;</span> Open IDE</div>
   <div class="nav-item" onclick="window.open('/clientarea.php','_blank')"><span class="icon">&#128203;</span> Billing</div>
  </div>

  <div class="main">

<!-- DASHBOARD -->
<div class="page active" id="page-dashboard">
 <h1 class="page-title">Dashboard</h1>
 <p class="page-sub">System overview</p>
 <div class="stat-grid" id="dash-stats"><div class="stat-card"><div class="label">Loading...</div><div class="value">-</div></div></div>
 <div class="panel"><div class="panel-header"><h3>Server Health</h3><button class="btn btn-sm btn-primary" onclick="loadDashboard()">Refresh</button></div><div class="panel-body" id="dash-health"><div class="loading">Loading</div></div></div>
 <div class="panel"><div class="panel-header"><h3>PM2 Services (Quick View)</h3></div><div class="panel-body" id="dash-pm2"><div class="loading">Loading</div></div></div>
</div>

<!-- SERVICES -->
<div class="page" id="page-services">
 <h1 class="page-title">Service Status</h1>
 <p class="page-sub">Key system services health</p>
 <div class="panel"><div class="panel-header"><h3>Services</h3><button class="btn btn-sm btn-primary" onclick="loadServices()">Refresh</button></div><div class="panel-body" id="services-list"><div class="loading">Loading</div></div></div>
</div>

<!-- PROCESSES -->
<div class="page" id="page-processes">
 <h1 class="page-title">System Processes</h1>
 <p class="page-sub">Top processes by CPU usage</p>
 <div class="panel"><div class="panel-header"><h3>Top 20 Processes</h3><button class="btn btn-sm btn-primary" onclick="loadProcesses()">Refresh</button></div><div class="panel-body" id="processes-list"><div class="loading">Loading</div></div></div>
</div>

<!-- DOMAINS -->
<div class="page" id="page-domains">
 <h1 class="page-title">Domains</h1>
 <p class="page-sub">Manage your domains</p>
 <div class="panel"><div class="panel-header"><h3>Domain List</h3><button class="btn btn-sm btn-primary" onclick="showModal('domain-modal')">+ Add Domain</button></div><div class="panel-body" id="domains-list"><div class="loading">Loading</div></div></div>
</div>

<!-- DNS -->
<div class="page" id="page-dns">
 <h1 class="page-title">DNS Management</h1>
 <p class="page-sub">Manage DNS records</p>
 <div class="panel"><div class="panel-header"><h3>Select Domain</h3></div><div class="panel-body"><div class="form-group"><select id="dns-domain-select" onchange="loadDNS()"><option value="">Select a domain...</option></select></div></div></div>
 <div class="panel" id="dns-records-panel" style="display:none"><div class="panel-header"><h3>DNS Records</h3><button class="btn btn-sm btn-primary" onclick="showModal('dns-modal')">+ Add Record</button></div><div class="panel-body" id="dns-records"><div class="loading">Loading</div></div></div>
</div>

<!-- DATABASES -->
<div class="page" id="page-databases">
 <h1 class="page-title">Databases</h1>
 <p class="page-sub">MySQL / MariaDB management</p>
 <div class="tab-bar"><div class="tab active" onclick="switchTab(this,'db-tab-list')">Databases</div><div class="tab" onclick="switchTab(this,'db-tab-query')">SQL Query</div></div>
 <div class="tab-content active" id="db-tab-list">
  <div class="panel"><div class="panel-header"><h3>Database List</h3><button class="btn btn-sm btn-primary" onclick="showModal('db-modal')">+ Create Database</button></div><div class="panel-body" id="db-list"><div class="loading">Loading</div></div></div>
 </div>
 <div class="tab-content" id="db-tab-query">
  <div class="panel"><div class="panel-header"><h3>SQL Query Tool</h3></div><div class="panel-body">
   <div class="form-group"><label>Database</label><select id="sql-db-select"><option value="">Default</option></select></div>
   <div class="form-group"><label>Query (SELECT/SHOW/DESCRIBE/EXPLAIN only)</label><textarea class="code-editor" id="sql-query" rows="4" placeholder="SELECT * FROM table_name LIMIT 10;"></textarea></div>
   <button class="btn btn-primary" onclick="runSQL()">Execute Query</button>
   <div id="sql-results" style="margin-top:16px"></div>
  </div></div>
 </div>
</div>

<!-- FILE MANAGER -->
<div class="page" id="page-files">
 <h1 class="page-title">File Manager</h1>
 <p class="page-sub">Browse, edit, and manage files</p>
 <div class="panel"><div class="panel-header"><h3>Path: <span id="files-path">/</span></h3>
  <div class="btn-group"><button class="btn btn-sm btn-primary" onclick="showModal('file-modal')">+ File</button><button class="btn btn-sm btn-success" onclick="showModal('mkdir-modal')">+ Folder</button></div></div>
  <div class="panel-body" id="files-list"><div class="loading">Loading</div></div></div>
 <div class="panel" id="file-editor-panel" style="display:none"><div class="panel-header"><h3>Editing: <span id="editing-file"></span></h3>
  <div class="btn-group"><button class="btn btn-sm btn-primary" onclick="saveFile()">Save</button><button class="btn btn-sm btn-danger" onclick="closeEditor()">Close</button></div></div>
  <div class="panel-body"><textarea class="code-editor" id="file-editor-content"></textarea></div></div>
</div>

<!-- SSL -->
<div class="page" id="page-ssl">
 <h1 class="page-title">SSL / TLS Certificates</h1>
 <p class="page-sub">Manage HTTPS certificates</p>
 <div class="panel"><div class="panel-header"><h3>Certificate Status</h3></div><div class="panel-body" id="ssl-list"><div class="loading">Loading</div></div></div>
</div>

<!-- APACHE -->
<div class="page" id="page-apache">
 <h1 class="page-title">Apache Configuration</h1>
 <p class="page-sub">Virtual hosts and server config</p>
 <div class="panel"><div class="panel-header"><h3>Virtual Hosts</h3><button class="btn btn-sm btn-primary" onclick="loadApache()">Refresh</button></div><div class="panel-body" id="apache-vhosts"><div class="loading">Loading</div></div></div>
 <div class="panel"><div class="panel-header"><h3>DirectAdmin Status</h3></div><div class="panel-body" id="da-status"><div class="loading">Loading</div></div></div>
</div>

<!-- PM2 -->
<div class="page" id="page-pm2">
 <h1 class="page-title">PM2 Process Manager</h1>
 <p class="page-sub">Monitor and control services</p>
 <div class="panel"><div class="panel-header"><h3>Processes</h3><button class="btn btn-sm btn-primary" onclick="loadPM2()">Refresh</button></div><div class="panel-body" id="pm2-list"><div class="loading">Loading</div></div></div>
 <div class="panel" id="pm2-log-panel" style="display:none"><div class="panel-header"><h3>Logs: <span id="pm2-log-name"></span></h3><button class="btn btn-sm btn-danger" onclick="document.getElementById('pm2-log-panel').style.display='none'">Close</button></div><div class="panel-body"><div class="terminal-out" id="pm2-log-output"></div></div></div>
</div>

<!-- EMAIL -->
<div class="page" id="page-email">
 <h1 class="page-title">Email Accounts</h1>
 <p class="page-sub">Manage email addresses</p>
 <div class="panel"><div class="panel-header"><h3>Email Accounts</h3><button class="btn btn-sm btn-primary" onclick="showModal('email-modal')">+ Create Account</button></div><div class="panel-body" id="email-list"><div class="loading">Loading</div></div></div>
</div>

<!-- CRON -->
<div class="page" id="page-cron">
 <h1 class="page-title">Cron Jobs</h1>
 <p class="page-sub">Scheduled tasks</p>
 <div class="panel"><div class="panel-header"><h3>Active Cron Jobs</h3><button class="btn btn-sm btn-primary" onclick="showModal('cron-modal')">+ Add Cron Job</button></div><div class="panel-body" id="cron-list"><div class="loading">Loading</div></div></div>
</div>

<!-- BACKUPS -->
<div class="page" id="page-backups">
 <h1 class="page-title">Backups</h1>
 <p class="page-sub">Database and file backups</p>
 <div class="panel"><div class="panel-header"><h3>Available Backups</h3><div class="btn-group"><button class="btn btn-sm btn-primary" onclick="createBackup('database')">Backup DB</button><button class="btn btn-sm btn-success" onclick="createBackup('files')">Backup Files</button></div></div><div class="panel-body" id="backup-list"><div class="loading">Loading</div></div></div>
</div>

<!-- ALFRED IDE -->
<div class="page" id="page-ide">
 <h1 class="page-title">Alfred IDE Instances</h1>
 <p class="page-sub">Browser-based code editor management</p>
 <div class="stat-grid" id="ide-stats"></div>
 <div class="panel"><div class="panel-header"><h3>IDE Instances</h3><button class="btn btn-sm btn-primary" onclick="showModal('ide-modal')">+ Provision IDE</button></div><div class="panel-body" id="ide-list"><div class="loading">Loading</div></div></div>
</div>

<!-- CLOUD VPS -->
<div class="page" id="page-cloud">
 <h1 class="page-title">Cloud VPS Instances</h1>
 <p class="page-sub">OVH Public Cloud instance management - reboot, rescue, resize, snapshots</p>
 <div class="stat-grid" id="cloud-stats"></div>
 <div class="panel"><div class="panel-header"><h3>Instances</h3><div class="btn-group"><button class="btn btn-sm btn-primary" onclick="showModal('cloud-modal')">+ Create</button><button class="btn btn-sm btn-success" onclick="loadCloud()">Refresh</button></div></div><div class="panel-body" id="cloud-instances"><div class="loading">Loading</div></div></div>
 <div class="panel"><div class="panel-header"><h3>Snapshots</h3></div><div class="panel-body" id="cloud-snapshots"><div class="loading">Loading</div></div></div>
 <div class="panel"><div class="panel-header"><h3>SSH Keys</h3><button class="btn btn-sm btn-primary" onclick="showModal('ssh-key-modal')">+ Add Key</button></div><div class="panel-body" id="cloud-ssh-keys"><div class="loading">Loading</div></div></div>
</div>

<!-- VPS SERVICES -->
<div class="page" id="page-vps">
 <h1 class="page-title">VPS Services</h1>
 <p class="page-sub">OVH VPS - reboot, stop, start, rescue, snapshots, console</p>
 <div class="panel"><div class="panel-header"><h3>Your VPS</h3><button class="btn btn-sm btn-primary" onclick="loadVPS()">Refresh</button></div><div class="panel-body" id="vps-list"><div class="loading">Loading</div></div></div>
 <div id="vps-detail-section" style="display:none">
  <div class="stat-grid" id="vps-detail-stats"></div>
  <div class="panel"><div class="panel-header"><h3>VPS Controls: <span id="vps-selected-name"></span></h3></div><div class="panel-body" id="vps-controls"></div></div>
  <div class="panel"><div class="panel-header"><h3>IPs</h3></div><div class="panel-body" id="vps-ips"></div></div>
  <div class="panel"><div class="panel-header"><h3>Snapshot</h3></div><div class="panel-body" id="vps-snapshot"></div></div>
 </div>
</div>

<!-- DEDICATED SERVERS -->
<div class="page" id="page-dedicated">
 <h1 class="page-title">Dedicated Servers</h1>
 <p class="page-sub">Bare metal - reboot, rescue mode, IPMI/KVM, hardware info, reinstall</p>
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

<!-- Domain -->
<div class="modal-overlay" id="domain-modal"><div class="modal"><button class="close" onclick="hideModal('domain-modal')">&times;</button><h3>Add Domain</h3>
 <div class="form-group"><label>Domain Name</label><input type="text" id="new-domain" placeholder="example.com"></div>
 <button class="btn btn-primary" onclick="createDomain()">Add Domain</button>
</div></div>

<!-- DNS Record -->
<div class="modal-overlay" id="dns-modal"><div class="modal"><button class="close" onclick="hideModal('dns-modal')">&times;</button><h3>Add DNS Record</h3>
 <div class="form-row"><div class="form-group"><label>Type</label><select id="dns-type"><option>A</option><option>AAAA</option><option>CNAME</option><option>MX</option><option>TXT</option><option>SRV</option><option>NS</option></select></div><div class="form-group"><label>Name</label><input type="text" id="dns-name" placeholder="@ or subdomain"></div></div>
 <div class="form-group"><label>Value</label><input type="text" id="dns-value" placeholder="IP or target"></div>
 <div class="form-group"><label>TTL</label><input type="number" id="dns-ttl" value="3600"></div>
 <button class="btn btn-primary" onclick="addDNSRecord()">Add Record</button>
</div></div>

<!-- Database -->
<div class="modal-overlay" id="db-modal"><div class="modal"><button class="close" onclick="hideModal('db-modal')">&times;</button><h3>Create Database</h3>
 <div class="form-group"><label>Database Name</label><input type="text" id="new-db-name" placeholder="myapp_db"></div>
 <button class="btn btn-primary" onclick="createDatabase()">Create</button>
</div></div>

<!-- Email -->
<div class="modal-overlay" id="email-modal"><div class="modal"><button class="close" onclick="hideModal('email-modal')">&times;</button><h3>Create Email Account</h3>
 <div class="form-row"><div class="form-group"><label>User</label><input type="text" id="new-email-user" placeholder="info"></div><div class="form-group"><label>Domain</label><select id="new-email-domain"></select></div></div>
 <div class="form-group"><label>Password</label><input type="password" id="new-email-pass" placeholder="Strong password"></div>
 <div class="form-group"><label>Quota (MB, 0=unlimited)</label><input type="number" id="new-email-quota" value="500"></div>
 <button class="btn btn-primary" onclick="createEmail()">Create Account</button>
</div></div>

<!-- Cron -->
<div class="modal-overlay" id="cron-modal"><div class="modal"><button class="close" onclick="hideModal('cron-modal')">&times;</button><h3>Add Cron Job</h3>
 <div class="form-group"><label>Schedule (cron expression)</label><input type="text" id="new-cron-schedule" placeholder="*/5 * * * *"></div>
 <div class="form-group"><label>Command</label><input type="text" id="new-cron-command" placeholder="/usr/bin/php /path/to/script.php"></div>
 <button class="btn btn-primary" onclick="addCronJob()">Add Cron Job</button>
</div></div>

<!-- File -->
<div class="modal-overlay" id="file-modal"><div class="modal"><button class="close" onclick="hideModal('file-modal')">&times;</button><h3>Create File</h3>
 <div class="form-group"><label>Filename</label><input type="text" id="new-filename" placeholder="newfile.txt"></div>
 <div class="form-group"><label>Content</label><textarea id="new-file-content" rows="6" style="font-family:monospace"></textarea></div>
 <button class="btn btn-primary" onclick="createFileAction()">Create</button>
</div></div>

<!-- Mkdir -->
<div class="modal-overlay" id="mkdir-modal"><div class="modal"><button class="close" onclick="hideModal('mkdir-modal')">&times;</button><h3>Create Directory</h3>
 <div class="form-group"><label>Directory Name</label><input type="text" id="new-dirname" placeholder="new-folder"></div>
 <button class="btn btn-primary" onclick="createDir()">Create</button>
</div></div>

<!-- Rename -->
<div class="modal-overlay" id="rename-modal"><div class="modal"><button class="close" onclick="hideModal('rename-modal')">&times;</button><h3>Rename / Move</h3>
 <div class="form-group"><label>Current Path</label><input type="text" id="rename-from" readonly></div>
 <div class="form-group"><label>New Path</label><input type="text" id="rename-to"></div>
 <button class="btn btn-primary" onclick="renameFile()">Rename</button>
</div></div>

<!-- Chmod -->
<div class="modal-overlay" id="chmod-modal"><div class="modal"><button class="close" onclick="hideModal('chmod-modal')">&times;</button><h3>Change Permissions</h3>
 <div class="form-group"><label>File</label><input type="text" id="chmod-file" readonly></div>
 <div class="form-group"><label>Mode (e.g. 755, 644)</label><input type="text" id="chmod-mode" placeholder="755" maxlength="4"></div>
 <button class="btn btn-primary" onclick="chmodFile()">Set Permissions</button>
</div></div>

<!-- IDE Provision -->
<div class="modal-overlay" id="ide-modal"><div class="modal"><button class="close" onclick="hideModal('ide-modal')">&times;</button><h3>Provision Alfred IDE</h3>
 <div class="form-group"><label>Username</label><input type="text" id="ide-username" placeholder="customer-name"></div>
 <div class="form-group"><label>Port (9000-9999)</label><input type="number" id="ide-port" min="9000" max="9999" placeholder="9001"></div>
 <div class="form-group"><label>Password (optional)</label><input type="text" id="ide-password" placeholder="Auto-generated if blank"></div>
 <button class="btn btn-primary" onclick="provisionIDE()">Provision</button>
</div></div>

<!-- Cloud Instance Create -->
<div class="modal-overlay" id="cloud-modal"><div class="modal"><button class="close" onclick="hideModal('cloud-modal')">&times;</button><h3>Create Cloud Instance</h3>
 <div class="form-group"><label>Name</label><input type="text" id="cloud-name" placeholder="my-server"></div>
 <div class="form-group"><label>Region</label><select id="cloud-region"><option value="">Loading...</option></select></div>
 <div class="form-group"><label>Flavor (Size)</label><select id="cloud-flavor"><option value="">Loading...</option></select></div>
 <div class="form-group"><label>Image (OS)</label><select id="cloud-image"><option value="">Loading...</option></select></div>
 <div class="form-group"><label><input type="checkbox" id="cloud-monthly"> Monthly Billing</label></div>
 <button class="btn btn-primary" onclick="createCloudInstance()">Create Instance</button>
</div></div>

<!-- SSH Key -->
<div class="modal-overlay" id="ssh-key-modal"><div class="modal"><button class="close" onclick="hideModal('ssh-key-modal')">&times;</button><h3>Add SSH Key</h3>
 <div class="form-group"><label>Name</label><input type="text" id="ssh-key-name" placeholder="my-key"></div>
 <div class="form-group"><label>Public Key</label><textarea id="ssh-key-content" rows="4" placeholder="ssh-rsa AAAA..." style="font-family:monospace;font-size:12px"></textarea></div>
 <button class="btn btn-primary" onclick="addSSHKey()">Add Key</button>
</div></div>

<!-- DA Configure -->
<div class="modal-overlay" id="da-modal"><div class="modal"><button class="close" onclick="hideModal('da-modal')">&times;</button><h3>Configure DirectAdmin</h3>
 <div class="form-group"><label>Admin Username</label><input type="text" id="da-admin" placeholder="admin"></div>
 <div class="form-group"><label>Admin Password</label><input type="password" id="da-pass" placeholder="password"></div>
 <button class="btn btn-primary" onclick="configureDA()">Save</button>
</div></div>

<!-- Toast -->
<div class="toast" id="toast"></div>

<script>
// GoHostMe Dashboard v2.0 - Complete Control Panel

const API = '/gohostme/api';
const OVH = '/api/ovh/';
let token = localStorage.getItem('gohostme_token');
let user = null;
let filePath = '/';
let editingPath = '';

// Fetch Helpers
async function api(method, path, body) {
    const o = { method, headers: { 'Content-Type': 'application/json' } };
    if (token) o.headers['Authorization'] = 'Bearer ' + token;
    if (body) o.body = JSON.stringify(body);
    const r = await fetch(API + path, o);
    const d = await r.json().catch(function() { return {}; });
    if (r.status === 401) { logout(); throw new Error('Session expired'); }
    if (!r.ok && d.error) throw new Error(d.error);
    return d;
}
async function ovh(action, method, body) {
    method = method || 'GET';
    const o = { method: method, headers: { 'Content-Type': 'application/json' } };
    if (body) o.body = JSON.stringify(body);
    const r = await fetch(OVH + '?action=' + action, o);
    return r.json().catch(function() { return {}; });
}

// Utils
function E(s) { if (!s && s !== 0) return ''; var d = document.createElement('div'); d.textContent = String(s); return d.innerHTML; }
function B(b) { if (!b) return '0 B'; var u = ['B','KB','MB','GB','TB']; var i = 0; while (b >= 1024 && i < u.length-1) { b /= 1024; i++; } return b.toFixed(i > 0 ? 1 : 0) + ' ' + u[i]; }
function T(ms) { if (!ms) return '-'; var h = Math.floor(ms / 3600000); var m = Math.floor((ms % 3600000) / 60000); return h > 0 ? h + 'h ' + m + 'm' : m + 'm'; }
function toast(msg, type) { type = type || 'success'; var el = document.getElementById('toast'); el.textContent = msg; el.className = 'toast show ' + type; setTimeout(function() { el.className = 'toast'; }, 4000); }
function showModal(id) { document.getElementById(id).classList.add('show'); }
function hideModal(id) { document.getElementById(id).classList.remove('show'); }
function switchTab(el, tabId) { el.parentElement.querySelectorAll('.tab').forEach(function(t) { t.classList.remove('active'); }); el.classList.add('active'); var parent = el.closest('.page'); parent.querySelectorAll('.tab-content').forEach(function(c) { c.classList.remove('active'); }); document.getElementById(tabId).classList.add('active'); }
function statusBadge(s) { var m = { online:'green', running:'green', ACTIVE:'green', ok:'green', stopped:'red', errored:'red', SHUTOFF:'red', BUILD:'orange', RESCUE:'yellow' }; return '<span class="badge ' + (m[s] || 'blue') + '">' + E(s) + '</span>'; }

// AUTH
document.getElementById('login-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    var email = document.getElementById('login-email').value;
    var password = document.getElementById('login-password').value;
    var err = document.getElementById('login-error');
    try {
        var d = await fetch(API + '/auth/login', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ email: email, password: password }) }).then(function(r) { return r.json(); });
        if (d.error) { err.textContent = d.error; err.style.display = 'block'; return; }
        token = d.token; localStorage.setItem('gohostme_token', token); user = d.user; showApp();
    } catch (ex) { err.textContent = 'Connection failed'; err.style.display = 'block'; }
});

function logout() { token = null; user = null; localStorage.removeItem('gohostme_token'); document.getElementById('app').style.display = 'none'; document.getElementById('login-screen').style.display = 'flex'; }

async function showApp() {
    document.getElementById('login-screen').style.display = 'none';
    document.getElementById('app').style.display = 'block';
    if (!user) { try { user = await api('GET', '/auth/me'); } catch(e) { logout(); return; } }
    document.getElementById('user-name').textContent = user.name || user.firstname || 'User';
    loadDashboard();
}
if (token) showApp();

// NAVIGATION
document.querySelectorAll('.nav-item[data-page]').forEach(function(item) {
    item.addEventListener('click', function() {
        document.querySelectorAll('.nav-item').forEach(function(i) { i.classList.remove('active'); });
        item.classList.add('active');
        var p = item.dataset.page;
        document.querySelectorAll('.page').forEach(function(pg) { pg.classList.remove('active'); });
        document.getElementById('page-' + p).classList.add('active');
        document.querySelector('.sidebar').classList.remove('open');
        var loaders = { dashboard: loadDashboard, services: loadServices, processes: loadProcesses, domains: loadDomains, dns: loadDNSSelect, databases: loadDatabases, files: loadFiles, ssl: loadSSL, apache: loadApache, pm2: loadPM2, email: loadEmail, cron: loadCron, backups: loadBackups, ide: loadIDE, cloud: loadCloud, vps: loadVPS, dedicated: loadDedicated };
        if (loaders[p]) loaders[p]();
    });
});

// DASHBOARD
async function loadDashboard() {
    try {
        var stats = await api('GET', '/system/stats');
        var cpuVal = typeof stats.cpu === 'object' ? (stats.cpu.currentLoad || stats.cpu.loadavg && stats.cpu.loadavg[0] || 0) : (stats.cpu || 0);
        var cpuCores = typeof stats.cpu === 'object' ? (stats.cpu.cores || '?') : '?';
        var memUsed = stats.memory ? stats.memory.used || 0 : 0;
        var memTotal = stats.memory ? stats.memory.total || 1 : 1;
        var diskUsed = stats.disk ? stats.disk.used || 0 : 0;
        var diskTotal = stats.disk ? stats.disk.total || 1 : 1;
        var memPct = Math.round((memUsed / memTotal) * 100);
        var diskPct = Math.round((diskUsed / diskTotal) * 100);

        document.getElementById('dash-stats').innerHTML =
            '<div class="stat-card"><div class="label">CPU</div><div class="value cyan">' + (typeof cpuVal === 'number' ? cpuVal.toFixed(1) + '%' : cpuVal) + '</div><div class="sub">' + cpuCores + ' cores</div></div>' +
            '<div class="stat-card"><div class="label">Memory</div><div class="value ' + (memPct > 80 ? 'red' : 'cyan') + '">' + memPct + '%</div><div class="sub">' + B(memUsed) + ' / ' + B(memTotal) + '</div></div>' +
            '<div class="stat-card"><div class="label">Disk</div><div class="value ' + (diskPct > 80 ? 'orange' : 'cyan') + '">' + diskPct + '%</div><div class="sub">' + B(diskUsed) + ' / ' + B(diskTotal) + '</div></div>' +
            '<div class="stat-card"><div class="label">Uptime</div><div class="value cyan">' + Math.floor((stats.uptime || 0) / 3600) + 'h</div><div class="sub">' + E(stats.hostname || stats.platform || 'Linux') + '</div></div>';

        document.getElementById('dash-health').innerHTML = '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px;font-size:13px">' +
            '<div><strong>OS:</strong> ' + E(stats.platform || 'Linux') + ' ' + E(stats.arch || '') + '</div>' +
            '<div><strong>Hostname:</strong> ' + E(stats.hostname || '-') + '</div>' +
            '<div><strong>Node:</strong> ' + E(stats.nodeVersion || '-') + '</div></div>';

        try {
            var pm2 = await api('GET', '/pm2/list');
            var procs = pm2.processes || pm2 || [];
            var on = procs.filter(function(p) { return p.status === 'online'; }).length;
            var off = procs.filter(function(p) { return p.status !== 'online'; }).length;
            var rows = procs.slice(0, 20).map(function(p) {
                return '<tr><td>' + E(p.name) + '</td><td>' + statusBadge(p.status) + '</td><td>' + (p.cpu || 0) + '%</td><td>' + B(p.memory || 0) + '</td><td>' + (p.restarts || 0) + '</td></tr>';
            }).join('');
            document.getElementById('dash-pm2').innerHTML = '<div style="margin-bottom:12px">' + statusBadge('online') + ' ' + on + ' online &nbsp; <span class="badge red">' + off + ' stopped</span> &nbsp; <span class="badge blue">' + procs.length + ' total</span></div>' +
                '<table><thead><tr><th>Name</th><th>Status</th><th>CPU</th><th>Mem</th><th>Restarts</th></tr></thead><tbody>' + rows + '</tbody></table>';
        } catch(e) { document.getElementById('dash-pm2').innerHTML = '<p style="color:var(--text2)">PM2 data unavailable</p>'; }
    } catch (err) { document.getElementById('dash-stats').innerHTML = '<div class="stat-card"><div class="label">Error</div><div class="value red" style="font-size:14px">' + E(err.message) + '</div></div>'; }
}

// SERVICES
async function loadServices() {
    try {
        var d = await api('GET', '/system/services');
        var svcs = d.services || d || [];
        if (!svcs.length) { document.getElementById('services-list').innerHTML = '<p style="color:var(--text2)">No service data</p>'; return; }
        var rows = svcs.map(function(s) { return '<tr><td><strong>' + E(s.name || s.service) + '</strong></td><td>' + statusBadge(s.status || s.state) + '</td><td style="color:var(--text2)">' + E(s.details || s.message || '') + '</td></tr>'; }).join('');
        document.getElementById('services-list').innerHTML = '<table><thead><tr><th>Service</th><th>Status</th><th>Details</th></tr></thead><tbody>' + rows + '</tbody></table>';
    } catch(e) { document.getElementById('services-list').innerHTML = '<p style="color:var(--red)">' + E(e.message) + '</p>'; }
}

// PROCESSES
async function loadProcesses() {
    try {
        var d = await api('GET', '/system/processes');
        var procs = d.processes || d || [];
        var rows = procs.map(function(p) { return '<tr><td>' + p.pid + '</td><td>' + E(p.name || p.command) + '</td><td>' + p.cpu + '%</td><td>' + (p.mem || p.memory || 0) + '%</td></tr>'; }).join('');
        document.getElementById('processes-list').innerHTML = '<table><thead><tr><th>PID</th><th>Name</th><th>CPU %</th><th>Memory %</th></tr></thead><tbody>' + rows + '</tbody></table>';
    } catch(e) { document.getElementById('processes-list').innerHTML = '<p style="color:var(--red)">' + E(e.message) + '</p>'; }
}

// DOMAINS
async function loadDomains() {
    try {
        var d = await api('GET', '/domains');
        var domains = d.domains || d || [];
        if (!domains.length) { document.getElementById('domains-list').innerHTML = '<p style="color:var(--text2)">No domains</p>'; return; }
        var rows = domains.map(function(dm) { var name = typeof dm === 'string' ? dm : dm.domain; return '<tr><td><strong>' + E(name) + '</strong></td><td style="color:var(--text2)">' + E(dm.path || '-') + '</td><td><button class="btn btn-sm btn-danger" onclick="deleteDomain(\'' + E(name) + '\')">Remove</button></td></tr>'; }).join('');
        document.getElementById('domains-list').innerHTML = '<table><thead><tr><th>Domain</th><th>Path</th><th>Actions</th></tr></thead><tbody>' + rows + '</tbody></table>';
    } catch(e) { document.getElementById('domains-list').innerHTML = '<p style="color:var(--red)">' + E(e.message) + '</p>'; }
}
async function createDomain() { var d = document.getElementById('new-domain').value.trim(); if (!d) return; try { await api('POST', '/domains/create', { domain: d }); toast('Domain added'); hideModal('domain-modal'); loadDomains(); } catch(e) { toast(e.message, 'error'); } }
async function deleteDomain(d) { if (!confirm('Delete domain ' + d + '?')) return; try { await api('DELETE', '/domains/' + encodeURIComponent(d)); toast('Domain removed'); loadDomains(); } catch(e) { toast(e.message, 'error'); } }

// DNS
async function loadDNSSelect() {
    try { var d = await api('GET', '/domains'); var domains = d.domains || d || []; var sel = document.getElementById('dns-domain-select'); sel.innerHTML = '<option value="">Select a domain...</option>' + domains.map(function(dm) { var n = typeof dm === 'string' ? dm : dm.domain; return '<option value="' + E(n) + '">' + E(n) + '</option>'; }).join(''); } catch(e) {}
}
async function loadDNS() {
    var domain = document.getElementById('dns-domain-select').value;
    if (!domain) { document.getElementById('dns-records-panel').style.display = 'none'; return; }
    document.getElementById('dns-records-panel').style.display = 'block';
    try {
        var d = await api('GET', '/dns/' + encodeURIComponent(domain));
        var records = d.records || d || [];
        var rows = records.map(function(r) { return '<tr><td><span class="badge blue">' + E(r.type) + '</span></td><td>' + E(r.name || '@') + '</td><td style="max-width:250px;overflow:hidden;text-overflow:ellipsis">' + E(r.value) + '</td><td>' + (r.ttl || 3600) + '</td><td><button class="btn btn-sm btn-danger" onclick="deleteDNSRecord(\'' + E(domain) + '\',\'' + E(r.name) + '\',\'' + E(r.type) + '\',\'' + E(r.value) + '\')">Delete</button></td></tr>'; }).join('');
        document.getElementById('dns-records').innerHTML = '<table><thead><tr><th>Type</th><th>Name</th><th>Value</th><th>TTL</th><th>Actions</th></tr></thead><tbody>' + rows + '</tbody></table>';
    } catch(e) { document.getElementById('dns-records').innerHTML = '<p style="color:var(--red)">' + E(e.message) + '</p>'; }
}
async function addDNSRecord() { var domain = document.getElementById('dns-domain-select').value; if (!domain) return; try { await api('POST', '/dns/' + encodeURIComponent(domain) + '/record', { type: document.getElementById('dns-type').value, name: document.getElementById('dns-name').value, value: document.getElementById('dns-value').value, ttl: parseInt(document.getElementById('dns-ttl').value) || 3600 }); toast('Record added'); hideModal('dns-modal'); loadDNS(); } catch(e) { toast(e.message, 'error'); } }
async function deleteDNSRecord(domain, name, type, value) { if (!confirm('Delete ' + type + ' record?')) return; try { await api('DELETE', '/dns/' + encodeURIComponent(domain) + '/record?type=' + encodeURIComponent(type) + '&name=' + encodeURIComponent(name) + '&value=' + encodeURIComponent(value)); toast('Record deleted'); loadDNS(); } catch(e) { toast(e.message, 'error'); } }

// DATABASES
async function loadDatabases() {
    try {
        var d = await api('GET', '/databases');
        var dbs = d.databases || d || [];
        var rows = dbs.map(function(db) { var name = typeof db === 'string' ? db : db.name; return '<tr><td><strong>' + E(name) + '</strong></td><td><button class="btn btn-sm btn-primary" onclick="viewTables(\'' + E(name) + '\')">View</button></td><td>' + (name.indexOf('whmcs') >= 0 ? '<span class="badge orange">Protected</span>' : '<button class="btn btn-sm btn-danger" onclick="deleteDatabase(\'' + E(name) + '\')">Drop</button>') + '</td></tr>'; }).join('');
        document.getElementById('db-list').innerHTML = '<table><thead><tr><th>Database</th><th>Tables</th><th>Actions</th></tr></thead><tbody>' + rows + '</tbody></table>';
        var sel = document.getElementById('sql-db-select');
        sel.innerHTML = '<option value="">Default</option>' + dbs.map(function(db) { var n = typeof db === 'string' ? db : db.name; return '<option value="' + E(n) + '">' + E(n) + '</option>'; }).join('');
    } catch(e) { document.getElementById('db-list').innerHTML = '<p style="color:var(--red)">' + E(e.message) + '</p>'; }
}
async function createDatabase() { var n = document.getElementById('new-db-name').value.trim(); if (!n) return; try { await api('POST', '/databases/create', { name: n }); toast('Database created'); hideModal('db-modal'); loadDatabases(); } catch(e) { toast(e.message, 'error'); } }
async function deleteDatabase(n) { if (!confirm('DROP database ' + n + '? This cannot be undone!')) return; try { await api('DELETE', '/databases/' + encodeURIComponent(n)); toast('Database dropped'); loadDatabases(); } catch(e) { toast(e.message, 'error'); } }
async function viewTables(db) { try { var d = await api('GET', '/databases/' + encodeURIComponent(db) + '/tables'); var t = d.tables || d || []; var names = t.map(function(x) { return typeof x === 'string' ? x : x.name || x.TABLE_NAME || JSON.stringify(x); }); alert('Tables in ' + db + ' (' + names.length + '):\n\n' + names.join('\n')); } catch(e) { toast(e.message, 'error'); } }
async function runSQL() {
    var sql = document.getElementById('sql-query').value.trim();
    var db = document.getElementById('sql-db-select').value;
    if (!sql) return;
    var el = document.getElementById('sql-results');
    el.innerHTML = '<div class="loading">Executing</div>';
    try {
        var body = { sql: sql };
        if (db) body.database = db;
        var d = await api('POST', '/databases/query', body);
        var rows = d.rows || d || [];
        if (!rows.length) { el.innerHTML = '<p style="color:var(--text2)">No results</p>'; return; }
        var cols = Object.keys(rows[0]);
        var thead = cols.map(function(c) { return '<th>' + E(c) + '</th>'; }).join('');
        var tbody = rows.slice(0, 100).map(function(r) { return '<tr>' + cols.map(function(c) { return '<td>' + E(r[c]) + '</td>'; }).join('') + '</tr>'; }).join('');
        el.innerHTML = '<p style="color:var(--green);margin-bottom:8px">' + (d.count || rows.length) + ' rows</p><div style="overflow-x:auto"><table><thead><tr>' + thead + '</tr></thead><tbody>' + tbody + '</tbody></table></div>';
    } catch(e) { el.innerHTML = '<p style="color:var(--red)">' + E(e.message) + '</p>'; }
}

// FILE MANAGER
async function loadFiles(path) {
    filePath = path || filePath || '/';
    document.getElementById('files-path').textContent = filePath;
    try {
        var d = await api('GET', '/files?path=' + encodeURIComponent(filePath));
        var items = d.items || d.files || d || [];
        var html = '';
        if (filePath !== '/') { var parent = filePath.split('/').slice(0, -1).join('/') || '/'; html += '<tr style="cursor:pointer" onclick="loadFiles(\'' + E(parent) + '\')"><td colspan="5">&#128193; <strong>..</strong> (parent)</td></tr>'; }
        items.forEach(function(f) {
            var name = f.name || f;
            var isDir = f.isDirectory || f.type === 'dir' || f.type === 'directory' || (typeof name === 'string' && name.endsWith('/'));
            var full = filePath === '/' ? '/' + name : filePath + '/' + name;
            html += '<tr' + (isDir ? ' style="cursor:pointer" onclick="loadFiles(\'' + E(full) + '\')"' : '') + '>' +
                '<td>' + (isDir ? '&#128193;' : '&#128196;') + ' <strong>' + E(name) + '</strong></td>' +
                '<td>' + (f.size != null ? B(f.size) : '-') + '</td>' +
                '<td style="color:var(--text2)">' + E(f.permissions || f.mode || '-') + '</td>' +
                '<td style="color:var(--text2)">' + (f.modified ? new Date(f.modified).toLocaleDateString() : '-') + '</td>' +
                '<td><div class="btn-group">' +
                (!isDir ? '<button class="btn btn-sm btn-primary" onclick="event.stopPropagation();editFile(\'' + E(full) + '\')">Edit</button>' : '') +
                '<button class="btn btn-sm btn-warning" onclick="event.stopPropagation();showRename(\'' + E(full) + '\')">Rename</button>' +
                '<button class="btn btn-sm btn-warning" onclick="event.stopPropagation();showChmod(\'' + E(full) + '\')">Chmod</button>' +
                '<button class="btn btn-sm btn-danger" onclick="event.stopPropagation();deleteFile(\'' + E(full) + '\')">Delete</button>' +
                '</div></td></tr>';
        });
        document.getElementById('files-list').innerHTML = '<table><thead><tr><th>Name</th><th>Size</th><th>Perms</th><th>Modified</th><th>Actions</th></tr></thead><tbody>' + html + '</tbody></table>';
    } catch(e) { document.getElementById('files-list').innerHTML = '<p style="color:var(--red)">' + E(e.message) + '</p>'; }
}
async function editFile(path) {
    try {
        var d = await api('GET', '/files/read?path=' + encodeURIComponent(path));
        editingPath = path;
        document.getElementById('editing-file').textContent = path;
        document.getElementById('file-editor-content').value = d.content || '';
        document.getElementById('file-editor-panel').style.display = 'block';
        document.getElementById('file-editor-panel').scrollIntoView({ behavior: 'smooth' });
    } catch(e) { toast(e.message, 'error'); }
}
async function saveFile() { if (!editingPath) return; try { await api('POST', '/files/write', { filePath: editingPath, content: document.getElementById('file-editor-content').value }); toast('File saved'); } catch(e) { toast(e.message, 'error'); } }
function closeEditor() { document.getElementById('file-editor-panel').style.display = 'none'; editingPath = ''; }
async function createFileAction() { var n = document.getElementById('new-filename').value.trim(); var c = document.getElementById('new-file-content').value; if (!n) return; var full = filePath === '/' ? '/' + n : filePath + '/' + n; try { await api('POST', '/files/write', { filePath: full, content: c }); toast('File created'); hideModal('file-modal'); loadFiles(); } catch(e) { toast(e.message, 'error'); } }
async function createDir() { var n = document.getElementById('new-dirname').value.trim(); if (!n) return; var full = filePath === '/' ? '/' + n : filePath + '/' + n; try { await api('POST', '/files/mkdir', { dirPath: full }); toast('Directory created'); hideModal('mkdir-modal'); loadFiles(); } catch(e) { toast(e.message, 'error'); } }
async function deleteFile(p) { if (!confirm('Delete ' + p + '?')) return; try { await api('DELETE', '/files?path=' + encodeURIComponent(p)); toast('Deleted'); loadFiles(); } catch(e) { toast(e.message, 'error'); } }
function showRename(p) { document.getElementById('rename-from').value = p; document.getElementById('rename-to').value = p; showModal('rename-modal'); }
async function renameFile() { var from = document.getElementById('rename-from').value; var to = document.getElementById('rename-to').value.trim(); if (!to || from === to) return; try { await api('POST', '/files/rename', { from: from, to: to }); toast('Renamed'); hideModal('rename-modal'); loadFiles(); } catch(e) { toast(e.message, 'error'); } }
function showChmod(p) { document.getElementById('chmod-file').value = p; document.getElementById('chmod-mode').value = ''; showModal('chmod-modal'); }
async function chmodFile() { var f = document.getElementById('chmod-file').value; var m = document.getElementById('chmod-mode').value.trim(); if (!m) return; try { await api('POST', '/files/chmod', { filePath: f, mode: m }); toast('Permissions set to ' + m); hideModal('chmod-modal'); loadFiles(); } catch(e) { toast(e.message, 'error'); } }

// SSL
async function loadSSL() {
    try {
        var d = await api('GET', '/domains');
        var domains = d.domains || d || [];
        var rows = '';
        for (var i = 0; i < domains.length; i++) {
            var dm = domains[i];
            var name = typeof dm === 'string' ? dm : dm.domain;
            var expiry = '-', badge = '<span class="badge blue">Unknown</span>';
            try { var sd = await api('GET', '/ssl/' + encodeURIComponent(name)); if (sd.expiry) { expiry = new Date(sd.expiry).toLocaleDateString(); badge = new Date(sd.expiry) > new Date() ? '<span class="badge green">Valid</span>' : '<span class="badge red">Expired</span>'; } } catch(e) {}
            rows += '<tr><td>' + E(name) + '</td><td>' + badge + '</td><td>' + expiry + '</td><td><button class="btn btn-sm btn-primary" onclick="requestSSL(\'' + E(name) + '\')">Request/Renew</button></td></tr>';
        }
        document.getElementById('ssl-list').innerHTML = '<table><thead><tr><th>Domain</th><th>Status</th><th>Expires</th><th>Actions</th></tr></thead><tbody>' + rows + '</tbody></table>';
    } catch(e) { document.getElementById('ssl-list').innerHTML = '<p style="color:var(--red)">' + E(e.message) + '</p>'; }
}
async function requestSSL(d) { try { var r = await api('POST', '/ssl/request', { domain: d }); toast(r.message || 'SSL requested'); loadSSL(); } catch(e) { toast(e.message, 'error'); } }

// APACHE
async function loadApache() {
    try { var d = await api('GET', '/apache/vhosts'); document.getElementById('apache-vhosts').innerHTML = '<div class="terminal-out">' + E(d.output || JSON.stringify(d, null, 2)) + '</div>'; } catch(e) { document.getElementById('apache-vhosts').innerHTML = '<p style="color:var(--red)">' + E(e.message) + '</p>'; }
    try { var d = await api('GET', '/da/status'); document.getElementById('da-status').innerHTML = '<p>DirectAdmin: ' + (d.configured ? '<span class="badge green">Connected</span>' : '<span class="badge red">Not Configured</span>') + '</p>' + (!d.configured ? '<button class="btn btn-sm btn-primary" onclick="showModal(\'da-modal\')" style="margin-top:8px">Configure DA</button>' : ''); } catch(e) { document.getElementById('da-status').innerHTML = '<p style="color:var(--text2)">DA status unavailable</p>'; }
}
async function configureDA() { var a = document.getElementById('da-admin').value.trim(); var p = document.getElementById('da-pass').value; if (!a || !p) return; try { await api('POST', '/da/configure', { admin: a, pass: p }); toast('DA configured'); hideModal('da-modal'); loadApache(); } catch(e) { toast(e.message, 'error'); } }

// PM2
async function loadPM2() {
    try {
        var d = await api('GET', '/pm2/list');
        var procs = d.processes || d || [];
        var rows = procs.map(function(p) {
            return '<tr><td>' + (p.pm_id != null ? p.pm_id : p.id != null ? p.id : '-') + '</td><td><strong>' + E(p.name) + '</strong></td><td>' + statusBadge(p.status) + '</td><td>' + (p.cpu || 0) + '%</td><td>' + B(p.memory || 0) + '</td><td>' + (p.restarts || 0) + '</td><td style="color:var(--text2)">' + T(p.uptime) + '</td>' +
                '<td><div class="btn-group">' +
                (p.status === 'online' ? '<button class="btn btn-sm btn-danger" onclick="pm2Action(\'' + E(p.name) + '\',\'stop\')">Stop</button>' : '<button class="btn btn-sm btn-success" onclick="pm2Action(\'' + E(p.name) + '\',\'start\')">Start</button>') +
                '<button class="btn btn-sm btn-primary" onclick="pm2Action(\'' + E(p.name) + '\',\'restart\')">Restart</button>' +
                '<button class="btn btn-sm btn-warning" onclick="pm2Logs(\'' + E(p.name) + '\')">Logs</button>' +
                '</div></td></tr>';
        }).join('');
        document.getElementById('pm2-list').innerHTML = '<table><thead><tr><th>ID</th><th>Name</th><th>Status</th><th>CPU</th><th>Mem</th><th>Restarts</th><th>Uptime</th><th>Actions</th></tr></thead><tbody>' + rows + '</tbody></table>';
    } catch(e) { document.getElementById('pm2-list').innerHTML = '<p style="color:var(--red)">' + E(e.message) + '</p>'; }
}
async function pm2Action(name, action) { try { await api('POST', '/pm2/action', { name: name, action: action }); toast(name + ' ' + action + 'ed'); setTimeout(loadPM2, 1000); } catch(e) { toast(e.message, 'error'); } }
async function pm2Logs(name) {
    document.getElementById('pm2-log-panel').style.display = 'block';
    document.getElementById('pm2-log-name').textContent = name;
    document.getElementById('pm2-log-output').textContent = 'Loading...';
    try { var d = await api('POST', '/pm2/logs', { name: name, lines: 200 }); document.getElementById('pm2-log-output').textContent = d.logs || d.output || JSON.stringify(d, null, 2); } catch(e) { document.getElementById('pm2-log-output').textContent = 'Error: ' + e.message; }
    document.getElementById('pm2-log-panel').scrollIntoView({ behavior: 'smooth' });
}

// EMAIL
async function loadEmail() {
    try {
        var d = await api('GET', '/email/accounts');
        var accounts = d.accounts || d || [];
        if (!accounts.length) { document.getElementById('email-list').innerHTML = '<p style="color:var(--text2)">No email accounts</p>'; return; }
        var rows = accounts.map(function(a) { return '<tr><td><strong>' + E(a.email || a.account || a) + '</strong></td><td>' + E(a.domain || '-') + '</td><td>' + E(a.usage || '-') + '</td><td><button class="btn btn-sm btn-danger" onclick="deleteEmail(\'' + E(a.user || '') + '\',\'' + E(a.domain || '') + '\')">Delete</button></td></tr>'; }).join('');
        document.getElementById('email-list').innerHTML = '<table><thead><tr><th>Email</th><th>Domain</th><th>Usage</th><th>Actions</th></tr></thead><tbody>' + rows + '</tbody></table>';
        try { var dd = await api('GET', '/domains'); var doms = dd.domains || dd || []; document.getElementById('new-email-domain').innerHTML = doms.map(function(dm) { var n = typeof dm === 'string' ? dm : dm.domain; return '<option value="' + E(n) + '">' + E(n) + '</option>'; }).join(''); } catch(e) {}
    } catch(e) { document.getElementById('email-list').innerHTML = '<p style="color:var(--red)">' + E(e.message) + '</p>'; }
}
async function createEmail() {
    var user = document.getElementById('new-email-user').value.trim();
    var domain = document.getElementById('new-email-domain').value;
    var password = document.getElementById('new-email-pass').value;
    var quota = parseInt(document.getElementById('new-email-quota').value) || 0;
    if (!user || !domain || !password) { toast('All fields required', 'error'); return; }
    try { await api('POST', '/email/create', { user: user, domain: domain, password: password, quota: quota }); toast('Email account created'); hideModal('email-modal'); loadEmail(); } catch(e) { toast(e.message, 'error'); }
}
async function deleteEmail(user, domain) { if (!confirm('Delete ' + user + '@' + domain + '?')) return; try { await api('DELETE', '/email/account?user=' + encodeURIComponent(user) + '&domain=' + encodeURIComponent(domain)); toast('Account deleted'); loadEmail(); } catch(e) { toast(e.message, 'error'); } }

// CRON
async function loadCron() {
    try {
        var d = await api('GET', '/cron');
        var jobs = d.jobs || d || [];
        if (!jobs.length) { document.getElementById('cron-list').innerHTML = '<p style="color:var(--text2)">No cron jobs</p>'; return; }
        var rows = jobs.map(function(j) {
            var line = typeof j === 'string' ? j : (j.schedule || j.time || '') + ' ' + (j.command || '');
            var parts = line.trim().split(/\s+/);
            var sched = parts.slice(0, 5).join(' ');
            var cmd = parts.slice(5).join(' ');
            return '<tr><td><code style="color:var(--accent)">' + E(sched) + '</code></td><td style="max-width:400px;overflow:hidden;text-overflow:ellipsis;font-family:monospace;font-size:12px">' + E(cmd || line) + '</td><td><button class="btn btn-sm btn-danger" onclick="deleteCron(\'' + E(line).replace(/'/g, "\\'") + '\')">Delete</button></td></tr>';
        }).join('');
        document.getElementById('cron-list').innerHTML = '<table><thead><tr><th>Schedule</th><th>Command</th><th>Actions</th></tr></thead><tbody>' + rows + '</tbody></table>';
    } catch(e) { document.getElementById('cron-list').innerHTML = '<p style="color:var(--red)">' + E(e.message) + '</p>'; }
}
async function addCronJob() { var s = document.getElementById('new-cron-schedule').value.trim(); var c = document.getElementById('new-cron-command').value.trim(); if (!s || !c) return; try { await api('POST', '/cron', { schedule: s, command: c }); toast('Cron added'); hideModal('cron-modal'); loadCron(); } catch(e) { toast(e.message, 'error'); } }
async function deleteCron(pattern) { if (!confirm('Delete cron job?')) return; try { await api('DELETE', '/cron?pattern=' + encodeURIComponent(pattern)); toast('Cron removed'); loadCron(); } catch(e) { toast(e.message, 'error'); } }

// BACKUPS
async function loadBackups() {
    try {
        var d = await api('GET', '/backups');
        var bk = d.backups || d || [];
        if (!bk.length) { document.getElementById('backup-list').innerHTML = '<p style="color:var(--text2)">No backups yet</p>'; return; }
        var rows = bk.map(function(b) { return '<tr><td>' + E(b.name || b.filename || b) + '</td><td>' + (b.size ? B(b.size) : '-') + '</td><td style="color:var(--text2)">' + E(b.date || b.modified || b.created || '-') + '</td><td><span class="badge blue">' + E(b.type || '-') + '</span></td></tr>'; }).join('');
        document.getElementById('backup-list').innerHTML = '<table><thead><tr><th>File</th><th>Size</th><th>Date</th><th>Type</th></tr></thead><tbody>' + rows + '</tbody></table>';
    } catch(e) { document.getElementById('backup-list').innerHTML = '<p style="color:var(--red)">' + E(e.message) + '</p>'; }
}
async function createBackup(type) { toast('Creating ' + type + ' backup...'); try { var r = await api('POST', '/backups/create', { type: type }); toast(r.message || 'Backup created'); loadBackups(); } catch(e) { toast(e.message, 'error'); } }

// ALFRED IDE
async function loadIDE() {
    try {
        var inst = await api('GET', '/ide/instances').catch(function() { return { instances: [] }; });
        var stats = await api('GET', '/ide/stats').catch(function() { return {}; });
        var list = inst.instances || inst || [];
        document.getElementById('ide-stats').innerHTML =
            '<div class="stat-card"><div class="label">Total</div><div class="value cyan">' + (stats.total || stats.instances || list.length) + '</div></div>' +
            '<div class="stat-card"><div class="label">Online</div><div class="value green">' + (stats.online || list.filter(function(i) { return i.status === 'online'; }).length) + '</div></div>' +
            '<div class="stat-card"><div class="label">Memory</div><div class="value cyan">' + (stats.totalMemory ? B(stats.totalMemory) : '-') + '</div></div>' +
            '<div class="stat-card"><div class="label">Port Range</div><div class="value cyan">' + (stats.port_range || stats.availablePorts || '9000-9999') + '</div></div>';
        if (!list.length) { document.getElementById('ide-list').innerHTML = '<p style="color:var(--text2)">No IDE instances</p>'; return; }
        var rows = list.map(function(i) {
            return '<tr><td><strong>' + E(i.name) + '</strong></td><td>' + statusBadge(i.status) + '</td><td>' + (i.port || '-') + '</td><td>' + (i.memory ? B(i.memory) : '-') + '</td><td>' + (i.restarts || 0) + '</td>' +
            '<td><div class="btn-group">' +
            (i.name === 'alfred-ide' ? '<span class="badge orange">Commander</span>' :
            (i.status === 'online' ? '<button class="btn btn-sm btn-danger" onclick="ideCtl(\'' + E(i.name) + '\',\'stop\')">Stop</button>' : '<button class="btn btn-sm btn-success" onclick="ideCtl(\'' + E(i.name) + '\',\'start\')">Start</button>') +
            '<button class="btn btn-sm btn-primary" onclick="ideCtl(\'' + E(i.name) + '\',\'restart\')">Restart</button>' +
            '<button class="btn btn-sm btn-danger" onclick="ideDelete(\'' + E(i.name) + '\')">Delete</button>') +
            '</div></td></tr>';
        }).join('');
        document.getElementById('ide-list').innerHTML = '<table><thead><tr><th>Name</th><th>Status</th><th>Port</th><th>Mem</th><th>Restarts</th><th>Actions</th></tr></thead><tbody>' + rows + '</tbody></table>';
    } catch(e) { document.getElementById('ide-list').innerHTML = '<p style="color:var(--red)">' + E(e.message) + '</p>'; }
}
async function ideCtl(name, action) { try { await api('POST', '/ide/control', { instance: name, action: action }); toast(name + ' ' + action + 'ed'); setTimeout(loadIDE, 1000); } catch(e) { toast(e.message, 'error'); } }
async function ideDelete(name) { if (!confirm('Delete IDE instance ' + name + '?')) return; try { await api('DELETE', '/ide/instance/' + encodeURIComponent(name)); toast('IDE instance deleted'); loadIDE(); } catch(e) { toast(e.message, 'error'); } }
async function provisionIDE() { var u = document.getElementById('ide-username').value.trim(); var p = parseInt(document.getElementById('ide-port').value); var pw = document.getElementById('ide-password').value.trim(); if (!u || !p) { toast('Username and port required', 'error'); return; } try { await api('POST', '/ide/provision', { username: u, port: p, password: pw || undefined }); toast('IDE provisioned on port ' + p); hideModal('ide-modal'); loadIDE(); } catch(e) { toast(e.message, 'error'); } }

// ===== CLOUD VPS (OVH Public Cloud) =====
async function loadCloud() {
    try {
        var status = await ovh('status');
        if (status.status === 'not_configured') {
            document.getElementById('cloud-stats').innerHTML = '<div class="stat-card" style="grid-column:1/-1;border-color:var(--orange)"><div class="label">OVH API</div><div class="value orange" style="font-size:16px">Not Configured</div><div class="sub">Generate API keys at <a href="https://ca.api.ovh.com/createApp" target="_blank">ca.api.ovh.com/createApp</a></div></div>';
            document.getElementById('cloud-instances').innerHTML = '<p style="color:var(--text2)">API keys required</p>';
            document.getElementById('cloud-snapshots').innerHTML = '';
            document.getElementById('cloud-ssh-keys').innerHTML = '';
            return;
        }
        if (status.status === 'connected') {
            document.getElementById('cloud-stats').innerHTML =
                '<div class="stat-card"><div class="label">API</div><div class="value green">Connected</div></div>' +
                '<div class="stat-card"><div class="label">Endpoint</div><div class="value cyan" style="font-size:13px">' + E(status.endpoint) + '</div></div>' +
                '<div class="stat-card"><div class="label">Server Time</div><div class="value cyan" style="font-size:13px">' + E(status.server_time) + '</div></div>';
        }
        var inst = await ovh('instances');
        var list = inst.instances || [];
        if (!list.length) { document.getElementById('cloud-instances').innerHTML = '<p style="color:var(--text2)">No instances</p>'; }
        else {
            var rows = list.map(function(i) {
                var ip4 = i.ipAddresses ? i.ipAddresses.filter(function(ip) { return ip.version === 4; }).map(function(ip) { return ip.ip; }).join(', ') : '-';
                return '<tr><td><strong>' + E(i.name) + '</strong></td><td>' + statusBadge(i.status) + '</td><td>' + E(i.region) + '</td><td style="font-family:monospace;font-size:12px">' + E(ip4) + '</td><td>' + E(i.flavor ? i.flavor.name : '-') + '</td>' +
                    '<td><div class="btn-group">' +
                    '<button class="btn btn-sm btn-warning" onclick="cloudReboot(\'' + E(i.id) + '\',\'soft\')">Reboot</button>' +
                    '<button class="btn btn-sm btn-danger" onclick="cloudReboot(\'' + E(i.id) + '\',\'hard\')">Hard</button>' +
                    '<button class="btn btn-sm btn-primary" onclick="cloudRescue(\'' + E(i.id) + '\')">Rescue</button>' +
                    '<button class="btn btn-sm btn-success" onclick="cloudUnrescue(\'' + E(i.id) + '\')">Unrescue</button>' +
                    '<button class="btn btn-sm btn-primary" onclick="cloudConsole(\'' + E(i.id) + '\')">Console</button>' +
                    '<button class="btn btn-sm btn-primary" onclick="cloudSnapshot(\'' + E(i.id) + '\')">Snap</button>' +
                    '<button class="btn btn-sm btn-danger" onclick="cloudDelete(\'' + E(i.id) + '\')">Delete</button>' +
                    '</div></td></tr>';
            }).join('');
            document.getElementById('cloud-instances').innerHTML = '<table><thead><tr><th>Name</th><th>Status</th><th>Region</th><th>IP</th><th>Flavor</th><th>Actions</th></tr></thead><tbody>' + rows + '</tbody></table>';
        }
        var snaps = await ovh('snapshots');
        var snapList = snaps.snapshots || [];
        document.getElementById('cloud-snapshots').innerHTML = snapList.length ? '<table><thead><tr><th>Name</th><th>Region</th><th>Size</th><th>Created</th><th>Status</th></tr></thead><tbody>' + snapList.map(function(s) { return '<tr><td>' + E(s.name) + '</td><td>' + E(s.region) + '</td><td>' + (s.size ? s.size + ' GB' : '-') + '</td><td>' + (s.creationDate ? new Date(s.creationDate).toLocaleDateString() : '-') + '</td><td>' + statusBadge(s.status || 'active') + '</td></tr>'; }).join('') + '</tbody></table>' : '<p style="color:var(--text2)">No snapshots</p>';
        var keys = await ovh('ssh_keys');
        var keyList = keys.ssh_keys || [];
        document.getElementById('cloud-ssh-keys').innerHTML = keyList.length ? '<table><thead><tr><th>Name</th><th>Fingerprint</th><th>Actions</th></tr></thead><tbody>' + keyList.map(function(k) { return '<tr><td>' + E(k.name) + '</td><td style="font-family:monospace;font-size:11px;color:var(--text2)">' + E(k.fingerprint || '-') + '</td><td><button class="btn btn-sm btn-danger" onclick="deleteSSHKey(\'' + E(k.id) + '\')">Delete</button></td></tr>'; }).join('') + '</tbody></table>' : '<p style="color:var(--text2)">No SSH keys</p>';
        loadCloudDropdowns();
    } catch(e) { document.getElementById('cloud-stats').innerHTML = '<div class="stat-card"><div class="label">Error</div><div class="value red" style="font-size:14px">' + E(e.message) + '</div></div>'; }
}
async function loadCloudDropdowns() {
    try {
        var rg = await ovh('regions'); var fl = await ovh('flavors'); var im = await ovh('images');
        document.getElementById('cloud-region').innerHTML = '<option value="">Select...</option>' + (rg.regions || []).map(function(r) { var n = typeof r === 'string' ? r : r.name; return '<option value="' + E(n) + '">' + E(n) + '</option>'; }).join('');
        document.getElementById('cloud-flavor').innerHTML = '<option value="">Select...</option>' + (fl.flavors || []).map(function(f) { return '<option value="' + E(f.id) + '">' + E(f.name) + ' (' + f.vcpus + 'CPU, ' + f.ram + 'MB, ' + f.disk + 'GB)</option>'; }).join('');
        document.getElementById('cloud-image').innerHTML = '<option value="">Select...</option>' + (im.images || []).map(function(i) { return '<option value="' + E(i.id) + '">' + E(i.name) + '</option>'; }).join('');
    } catch(e) {}
}
async function createCloudInstance() { var n = document.getElementById('cloud-name').value.trim(); var r = document.getElementById('cloud-region').value; var f = document.getElementById('cloud-flavor').value; var i = document.getElementById('cloud-image').value; var m = document.getElementById('cloud-monthly').checked; if (!n || !r || !f || !i) { toast('All fields required', 'error'); return; } try { await ovh('create_instance', 'POST', { name: n, region: r, flavorId: f, imageId: i, monthly: m }); toast('Instance creating...'); hideModal('cloud-modal'); setTimeout(loadCloud, 3000); } catch(e) { toast(e.message, 'error'); } }
async function cloudReboot(id, type) { if (!confirm(type + ' reboot this instance?')) return; try { await ovh('instance_reboot', 'POST', { id: id, type: type }); toast('Reboot initiated'); setTimeout(loadCloud, 3000); } catch(e) { toast(e.message, 'error'); } }
async function cloudRescue(id) { if (!confirm('Boot into rescue mode?')) return; try { await ovh('instance_rescue', 'POST', { id: id }); toast('Rescue mode initiated'); setTimeout(loadCloud, 5000); } catch(e) { toast(e.message, 'error'); } }
async function cloudUnrescue(id) { try { await ovh('instance_unrescue', 'POST', { id: id }); toast('Exiting rescue mode...'); setTimeout(loadCloud, 3000); } catch(e) { toast(e.message, 'error'); } }
async function cloudConsole(id) { try { var d = await ovh('instance_console', 'POST', { id: id }); if (d.url) { window.open(d.url, '_blank'); toast('Console opened'); } else { toast('Console: ' + JSON.stringify(d), 'error'); } } catch(e) { toast(e.message, 'error'); } }
async function cloudSnapshot(id) { var name = prompt('Snapshot name:', 'snapshot-' + new Date().toISOString().slice(0,10)); if (!name) return; try { await ovh('instance_snapshot', 'POST', { id: id, snapshotName: name }); toast('Snapshot creating...'); setTimeout(loadCloud, 3000); } catch(e) { toast(e.message, 'error'); } }
async function cloudDelete(id) { if (!confirm('DELETE this cloud instance? Cannot be undone!')) return; try { await ovh('delete_instance', 'POST', { id: id }); toast('Instance deleting...'); setTimeout(loadCloud, 3000); } catch(e) { toast(e.message, 'error'); } }
async function addSSHKey() { var n = document.getElementById('ssh-key-name').value.trim(); var k = document.getElementById('ssh-key-content').value.trim(); if (!n || !k) return; try { await ovh('add_ssh_key', 'POST', { name: n, publicKey: k }); toast('SSH key added'); hideModal('ssh-key-modal'); loadCloud(); } catch(e) { toast(e.message, 'error'); } }
async function deleteSSHKey(id) { if (!confirm('Delete this SSH key?')) return; try { await ovh('delete_ssh_key', 'POST', { id: id }); toast('Key deleted'); loadCloud(); } catch(e) { toast(e.message, 'error'); } }

// ===== VPS SERVICES (OVH VPS product) =====
async function loadVPS() {
    document.getElementById('vps-detail-section').style.display = 'none';
    try {
        var d = await ovh('vps_list');
        var vpsList = d.vps || d || [];
        if (!vpsList.length) { document.getElementById('vps-list').innerHTML = '<p style="color:var(--text2)">No VPS services found</p>'; return; }
        var rows = vpsList.map(function(v) { var name = typeof v === 'string' ? v : v.name; return '<tr><td><strong>' + E(name) + '</strong></td><td><button class="btn btn-sm btn-primary" onclick="loadVPSDetail(\'' + E(name) + '\')">Manage</button></td></tr>'; }).join('');
        document.getElementById('vps-list').innerHTML = '<table><thead><tr><th>VPS Name</th><th>Actions</th></tr></thead><tbody>' + rows + '</tbody></table>';
    } catch(e) { document.getElementById('vps-list').innerHTML = '<p style="color:var(--red)">' + E(e.message) + '</p>'; }
}
async function loadVPSDetail(name) {
    document.getElementById('vps-selected-name').textContent = name;
    document.getElementById('vps-detail-section').style.display = 'block';
    try {
        var detail = await ovh('vps_detail&name=' + encodeURIComponent(name));
        var ips = await ovh('vps_ips&name=' + encodeURIComponent(name));
        var snap = await ovh('vps_snapshot&name=' + encodeURIComponent(name));

        document.getElementById('vps-detail-stats').innerHTML =
            '<div class="stat-card"><div class="label">Name</div><div class="value cyan" style="font-size:14px">' + E(detail.displayName || detail.name || name) + '</div></div>' +
            '<div class="stat-card"><div class="label">State</div><div class="value ' + (detail.state === 'running' ? 'green' : 'red') + '">' + E(detail.state || '-') + '</div></div>' +
            '<div class="stat-card"><div class="label">Model</div><div class="value cyan" style="font-size:13px">' + E(detail.model ? detail.model.name : detail.offerType || '-') + '</div></div>' +
            '<div class="stat-card"><div class="label">Datacenter</div><div class="value cyan" style="font-size:14px">' + E(detail.zone || detail.location || '-') + '</div></div>' +
            '<div class="stat-card"><div class="label">vCPU</div><div class="value cyan">' + (detail.model ? detail.model.vcore : detail.vcore || '-') + '</div></div>' +
            '<div class="stat-card"><div class="label">RAM</div><div class="value cyan">' + (detail.model && detail.model.memory ? (detail.model.memory / 1024).toFixed(1) + ' GB' : '-') + '</div></div>' +
            '<div class="stat-card"><div class="label">Disk</div><div class="value cyan">' + (detail.model && detail.model.disk ? detail.model.disk + ' GB' : '-') + '</div></div>' +
            '<div class="stat-card"><div class="label">OS</div><div class="value cyan" style="font-size:12px">' + E(detail.model ? detail.model.os : '-') + '</div></div>';

        document.getElementById('vps-controls').innerHTML =
            '<div class="btn-group" style="flex-wrap:wrap;gap:8px">' +
            '<button class="btn btn-success" onclick="vpsAction(\'' + E(name) + '\',\'vps_start\')">&#9654; Start</button>' +
            '<button class="btn btn-danger" onclick="vpsAction(\'' + E(name) + '\',\'vps_stop\')">&#9724; Stop</button>' +
            '<button class="btn btn-warning" onclick="vpsAction(\'' + E(name) + '\',\'vps_reboot\')">&#128260; Reboot</button>' +
            '<button class="btn btn-primary" onclick="vpsConsole(\'' + E(name) + '\')">&#128421; VNC Console</button>' +
            '<button class="btn btn-primary" onclick="vpsCreateSnapshot(\'' + E(name) + '\')">&#128247; Create Snapshot</button>' +
            (snap && !snap.error ? '<button class="btn btn-warning" onclick="vpsRestoreSnapshot(\'' + E(name) + '\')">&#128260; Restore Snapshot</button>' : '') +
            '<button class="btn btn-danger" onclick="vpsReinstall(\'' + E(name) + '\')">&#128165; Reinstall OS</button>' +
            '</div>';

        var ipList = ips.ips || ips || [];
        document.getElementById('vps-ips').innerHTML = ipList.length ? '<table><thead><tr><th>IP Address</th></tr></thead><tbody>' + ipList.map(function(ip) { return '<tr><td style="font-family:monospace">' + E(typeof ip === 'string' ? ip : ip.ipAddress || JSON.stringify(ip)) + '</td></tr>'; }).join('') + '</tbody></table>' : '<p style="color:var(--text2)">No IPs</p>';

        if (snap && !snap.error && snap.creationDate) {
            document.getElementById('vps-snapshot').innerHTML = '<table><thead><tr><th>Created</th><th>Description</th></tr></thead><tbody><tr><td>' + new Date(snap.creationDate).toLocaleString() + '</td><td>' + E(snap.description || '-') + '</td></tr></tbody></table>';
        } else { document.getElementById('vps-snapshot').innerHTML = '<p style="color:var(--text2)">No snapshot</p>'; }

        document.getElementById('vps-detail-section').scrollIntoView({ behavior: 'smooth' });
    } catch(e) { document.getElementById('vps-detail-stats').innerHTML = '<div class="stat-card"><div class="label">Error</div><div class="value red" style="font-size:14px">' + E(e.message) + '</div></div>'; }
}
async function vpsAction(name, action) { if (!confirm(action.replace('vps_', '') + ' VPS ' + name + '?')) return; try { await ovh(action, 'POST', { name: name }); toast('VPS ' + action.replace('vps_', '') + ' initiated'); setTimeout(function() { loadVPSDetail(name); }, 3000); } catch(e) { toast(e.message, 'error'); } }
async function vpsConsole(name) { try { var d = await ovh('vps_console', 'POST', { name: name }); if (d.url) { window.open(d.url, '_blank'); toast('Console opened'); } else { toast(JSON.stringify(d), 'error'); } } catch(e) { toast(e.message, 'error'); } }
async function vpsCreateSnapshot(name) { var desc = prompt('Snapshot description:', 'Manual snapshot'); if (desc === null) return; try { await ovh('vps_create_snapshot', 'POST', { name: name, description: desc }); toast('Snapshot creating...'); setTimeout(function() { loadVPSDetail(name); }, 3000); } catch(e) { toast(e.message, 'error'); } }
async function vpsRestoreSnapshot(name) { if (!confirm('Restore VPS from snapshot? This will overwrite current data!')) return; try { await ovh('vps_restore_snapshot', 'POST', { name: name }); toast('Snapshot restore initiated'); } catch(e) { toast(e.message, 'error'); } }
async function vpsReinstall(name) { if (!confirm('REINSTALL OS on VPS ' + name + '? ALL DATA WILL BE LOST!')) return; if (!confirm('Are you REALLY sure?')) return; try { await ovh('vps_reinstall', 'POST', { name: name }); toast('Reinstall initiated'); } catch(e) { toast(e.message, 'error'); } }

// ===== DEDICATED SERVERS (Bare Metal) =====
async function loadDedicated() {
    document.getElementById('dedicated-detail-section').style.display = 'none';
    try {
        var d = await ovh('dedicated_servers');
        var servers = d.servers || d || [];
        if (!servers.length) { document.getElementById('dedicated-list').innerHTML = '<p style="color:var(--text2)">No dedicated servers</p>'; return; }
        var rows = servers.map(function(s) { var name = typeof s === 'string' ? s : s.name; return '<tr><td><strong>' + E(name) + '</strong></td><td><button class="btn btn-sm btn-primary" onclick="loadDedicatedDetail(\'' + E(name) + '\')">Manage</button></td></tr>'; }).join('');
        document.getElementById('dedicated-list').innerHTML = '<table><thead><tr><th>Server</th><th>Actions</th></tr></thead><tbody>' + rows + '</tbody></table>';
    } catch(e) { document.getElementById('dedicated-list').innerHTML = '<p style="color:var(--red)">' + E(e.message) + '</p>'; }
}
async function loadDedicatedDetail(name) {
    document.getElementById('ded-selected-name').textContent = name;
    document.getElementById('dedicated-detail-section').style.display = 'block';
    try {
        var detail = await ovh('dedicated_server&name=' + encodeURIComponent(name));
        var hw = {}; try { hw = await ovh('dedicated_hardware&name=' + encodeURIComponent(name)); } catch(e) {}
        var net = {}; try { net = await ovh('dedicated_network&name=' + encodeURIComponent(name)); } catch(e) {}
        var ips = { ips: [] }; try { ips = await ovh('dedicated_ips&name=' + encodeURIComponent(name)); } catch(e) {}
        var ipmi = {}; try { ipmi = await ovh('dedicated_ipmi&name=' + encodeURIComponent(name)); } catch(e) {}

        document.getElementById('dedicated-detail-stats').innerHTML =
            '<div class="stat-card"><div class="label">Server</div><div class="value cyan" style="font-size:13px">' + E(detail.name || name) + '</div></div>' +
            '<div class="stat-card"><div class="label">State</div><div class="value ' + (detail.state === 'ok' ? 'green' : 'orange') + '">' + E(detail.state || '-') + '</div></div>' +
            '<div class="stat-card"><div class="label">Datacenter</div><div class="value cyan" style="font-size:14px">' + E(detail.datacenter || '-') + '</div></div>' +
            '<div class="stat-card"><div class="label">IP</div><div class="value cyan" style="font-size:13px">' + E(detail.ip || '-') + '</div></div>' +
            '<div class="stat-card"><div class="label">OS</div><div class="value cyan" style="font-size:12px">' + E(detail.os || '-') + '</div></div>' +
            '<div class="stat-card"><div class="label">Boot Mode</div><div class="value cyan" style="font-size:13px">' + (detail.bootId || '-') + '</div></div>' +
            '<div class="stat-card"><div class="label">Rescue</div><div class="value ' + (detail.rescueMail ? 'yellow' : 'green') + '">' + (detail.rescueMail ? 'ACTIVE' : 'Normal') + '</div></div>' +
            '<div class="stat-card"><div class="label">Monitoring</div><div class="value ' + (detail.monitoring ? 'green' : 'red') + '">' + (detail.monitoring ? 'Enabled' : 'Disabled') + '</div></div>';

        document.getElementById('dedicated-controls').innerHTML =
            '<div class="btn-group" style="flex-wrap:wrap;gap:8px">' +
            '<button class="btn btn-warning" onclick="dedReboot(\'' + E(name) + '\')">&#128260; Reboot</button>' +
            '<button class="btn btn-danger" onclick="dedRescue(\'' + E(name) + '\', true)">&#128295; Enter Rescue Mode</button>' +
            '<button class="btn btn-success" onclick="dedRescue(\'' + E(name) + '\', false)">&#128994; Exit Rescue Mode</button>' +
            '<button class="btn btn-primary" onclick="dedIPMI(\'' + E(name) + '\')">&#128421; IPMI/KVM Console</button>' +
            '<button class="btn btn-danger" onclick="dedReinstall(\'' + E(name) + '\')">&#128165; Reinstall OS</button>' +
            '</div>' +
            '<p style="margin-top:12px;color:var(--text2);font-size:12px">Rescue mode reboots into a minimal Linux environment for disk repair/recovery. Credentials emailed to your account.</p>';

        if (hw && !hw.error) {
            var specs = [];
            if (hw.motherboard) specs.push(['Motherboard', hw.motherboard]);
            if (hw.processorName) specs.push(['CPU', hw.processorName]);
            if (hw.numberOfProcessors) specs.push(['CPU Count', hw.numberOfProcessors]);
            if (hw.coresPerProcessor) specs.push(['Cores/CPU', hw.coresPerProcessor]);
            if (hw.threadsPerProcessor) specs.push(['Threads/CPU', hw.threadsPerProcessor]);
            if (hw.memorySize) specs.push(['RAM', (hw.memorySize / 1024).toFixed(0) + ' GB']);
            if (hw.diskGroups) { hw.diskGroups.forEach(function(dg, i) { specs.push(['Disk Group ' + (i+1), dg.numberOfDisks + 'x ' + dg.diskSize + 'GB ' + (dg.diskType || 'SSD')]); }); }
            document.getElementById('dedicated-hardware').innerHTML = specs.length ? '<table><tbody>' + specs.map(function(s) { return '<tr><td style="color:var(--text2);width:200px"><strong>' + E(s[0]) + '</strong></td><td>' + E(s[1]) + '</td></tr>'; }).join('') + '</tbody></table>' : '<pre class="terminal-out">' + E(JSON.stringify(hw, null, 2)) + '</pre>';
        } else { document.getElementById('dedicated-hardware').innerHTML = '<p style="color:var(--text2)">Hardware info unavailable</p>'; }

        var ipList = ips.ips || [];
        var netHtml = '';
        if (net && !net.error) { netHtml += '<pre class="terminal-out" style="margin-bottom:12px">' + E(JSON.stringify(net, null, 2)) + '</pre>'; }
        netHtml += ipList.length ? '<table><thead><tr><th>IP Address</th></tr></thead><tbody>' + ipList.map(function(ip) { return '<tr><td style="font-family:monospace">' + E(ip) + '</td></tr>'; }).join('') + '</tbody></table>' : '<p style="color:var(--text2)">No IP data</p>';
        document.getElementById('dedicated-network').innerHTML = netHtml;

        if (ipmi && !ipmi.error) {
            document.getElementById('dedicated-ipmi').innerHTML = '<p>IPMI Available: <span class="badge ' + (ipmi.activated ? 'green' : 'red') + '">' + (ipmi.activated ? 'Yes' : 'No') + '</span></p>' +
                (ipmi.supportedFeatures ? '<p style="margin-top:8px;color:var(--text2)">Features: ' + E(JSON.stringify(ipmi.supportedFeatures)) + '</p>' : '') +
                '<button class="btn btn-primary" style="margin-top:12px" onclick="dedIPMI(\'' + E(name) + '\')">Launch IPMI Session</button>';
        } else { document.getElementById('dedicated-ipmi').innerHTML = '<p style="color:var(--text2)">IPMI data unavailable</p>'; }

        document.getElementById('dedicated-detail-section').scrollIntoView({ behavior: 'smooth' });
    } catch(e) { document.getElementById('dedicated-detail-stats').innerHTML = '<div class="stat-card"><div class="label">Error</div><div class="value red" style="font-size:14px">' + E(e.message) + '</div></div>'; }
}
async function dedReboot(name) { if (!confirm('Reboot dedicated server ' + name + '?')) return; try { await ovh('dedicated_reboot', 'POST', { name: name }); toast('Reboot initiated - server back in ~5 min'); } catch(e) { toast(e.message, 'error'); } }
async function dedRescue(name, enable) {
    var action = enable ? 'ENTER rescue mode' : 'EXIT rescue mode';
    if (!confirm(action + ' on ' + name + '?')) return;
    try { await ovh('dedicated_rescue', 'POST', { name: name, rescue: enable }); toast(enable ? 'Rescue mode enabled - rebooting...' : 'Rescue mode disabled - reboot to return to normal'); } catch(e) { toast(e.message, 'error'); }
}
async function dedIPMI(name) {
    toast('Requesting IPMI session...', 'success');
    try {
        var d = await ovh('dedicated_ipmi_access', 'POST', { name: name, type: 'kvmoverip' });
        if (d.url) { window.open(d.url, '_blank'); toast('IPMI console opened'); }
        else if (d.value) { window.open(d.value, '_blank'); toast('KVM session opened'); }
        else { toast('IPMI response: ' + JSON.stringify(d)); }
    } catch(e) { toast(e.message, 'error'); }
}
async function dedReinstall(name) {
    if (!confirm('REINSTALL OS on dedicated server ' + name + '? ALL DATA DESTROYED!')) return;
    if (!confirm('This is IRREVERSIBLE. Are you absolutely sure?')) return;
    try {
        var t = await ovh('dedicated_templates&name=' + encodeURIComponent(name));
        var templates = t.linux || t.templates || [];
        if (!templates.length) { toast('No OS templates available', 'error'); return; }
        var choice = prompt('Available templates:\n\n' + templates.slice(0, 20).join('\n') + '\n\nEnter template name:');
        if (!choice) return;
        await ovh('dedicated_reinstall', 'POST', { name: name, templateName: choice });
        toast('OS reinstall initiated - 15-30 minutes');
    } catch(e) { toast(e.message, 'error'); }
}

</script>
</body>
</html>
