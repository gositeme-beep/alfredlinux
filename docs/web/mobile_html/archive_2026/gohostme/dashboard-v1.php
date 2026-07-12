<?php
/**
 * GoHostMe — Management Dashboard
 * The actual control panel where customers manage their infrastructure.
 * Communicates with GoHostMe Node.js backend at 127.0.0.1:2224
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
:root{--bg:#0a0a0f;--bg2:#111118;--bg3:#161622;--border:#1e1e30;--text:#e0e0e8;--text2:#888;--accent:#00d4ff;--accent2:#7b2fff;--green:#00ff88;--red:#ff4455;--orange:#ff9933;--radius:10px}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
a{color:var(--accent);text-decoration:none}
button{cursor:pointer;font-family:inherit}

/* ── Login Screen ── */
#login-screen{display:flex;align-items:center;justify-content:center;min-height:100vh;background:var(--bg)}
.login-box{background:var(--bg2);border:1px solid var(--border);border-radius:16px;padding:48px;width:100%;max-width:420px;text-align:center}
.login-box .logo{font-size:28px;font-weight:900;background:linear-gradient(135deg,var(--accent),var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;margin-bottom:8px}
.login-box .sub{color:var(--text2);font-size:14px;margin-bottom:32px}
.login-box input{width:100%;padding:14px 16px;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);color:var(--text);font-size:15px;margin-bottom:16px;outline:none;transition:border .2s}
.login-box input:focus{border-color:var(--accent)}
.login-box .btn{width:100%;padding:14px;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;border:none;border-radius:var(--radius);font-size:16px;font-weight:700;transition:all .3s}
.login-box .btn:hover{transform:translateY(-2px);box-shadow:0 8px 30px rgba(0,212,255,.3)}
.login-box .error{color:var(--red);font-size:13px;margin-top:12px;display:none}

/* ── Main Layout ── */
#app{display:none;min-height:100vh}
.topbar{background:var(--bg2);border-bottom:1px solid var(--border);padding:12px 24px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100}
.topbar .logo{font-size:20px;font-weight:800;background:linear-gradient(135deg,var(--accent),var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.topbar .user-info{display:flex;align-items:center;gap:12px;font-size:14px;color:var(--text2)}
.topbar .user-info .name{color:var(--text);font-weight:600}
.topbar .logout-btn{background:transparent;border:1px solid var(--border);color:var(--text2);padding:6px 14px;border-radius:6px;font-size:13px;transition:all .2s}
.topbar .logout-btn:hover{border-color:var(--red);color:var(--red)}

.layout{display:flex;min-height:calc(100vh - 52px)}

/* ── Sidebar ── */
.sidebar{width:220px;background:var(--bg2);border-right:1px solid var(--border);padding:16px 0;flex-shrink:0;overflow-y:auto}
.sidebar .section-title{font-size:11px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:1px;padding:8px 20px;margin-top:12px}
.sidebar .nav-item{display:flex;align-items:center;gap:10px;padding:10px 20px;font-size:14px;color:var(--text2);cursor:pointer;transition:all .15s;border-left:3px solid transparent}
.sidebar .nav-item:hover{background:rgba(0,212,255,.05);color:var(--text)}
.sidebar .nav-item.active{background:rgba(0,212,255,.08);color:var(--accent);border-left-color:var(--accent);font-weight:600}
.sidebar .nav-item .icon{font-size:16px;width:20px;text-align:center}

/* ── Main Content ── */
.main{flex:1;padding:24px;overflow-y:auto}
.page-title{font-size:24px;font-weight:800;margin-bottom:8px}
.page-sub{color:var(--text2);font-size:14px;margin-bottom:24px}

/* Stat Cards */
.stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px}
.stat-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);padding:20px}
.stat-card .label{font-size:12px;color:var(--text2);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px}
.stat-card .value{font-size:28px;font-weight:900}
.stat-card .value.accent{background:linear-gradient(135deg,var(--accent),var(--green));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.stat-card .sub{font-size:12px;color:var(--text2);margin-top:4px}

/* Tables */
.panel{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:24px;overflow:hidden}
.panel-header{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.panel-header h3{font-size:16px;font-weight:700}
.panel-body{padding:20px}
table{width:100%;border-collapse:collapse}
table th{text-align:left;padding:10px 12px;font-size:12px;color:var(--text2);text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border);background:var(--bg3)}
table td{padding:10px 12px;font-size:14px;border-bottom:1px solid rgba(30,30,48,.5)}
table tr:hover td{background:rgba(0,212,255,.02)}
.badge{display:inline-block;padding:3px 10px;border-radius:12px;font-size:11px;font-weight:700}
.badge.green{background:rgba(0,255,136,.15);color:var(--green)}
.badge.red{background:rgba(255,68,85,.15);color:var(--red)}
.badge.orange{background:rgba(255,153,51,.15);color:var(--orange)}
.badge.blue{background:rgba(0,212,255,.15);color:var(--accent)}

/* Buttons */
.btn{padding:8px 16px;border-radius:6px;font-size:13px;font-weight:600;border:none;transition:all .2s}
.btn-primary{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff}
.btn-primary:hover{transform:translateY(-1px);box-shadow:0 4px 15px rgba(0,212,255,.3)}
.btn-sm{padding:5px 10px;font-size:12px}
.btn-danger{background:rgba(255,68,85,.15);color:var(--red);border:1px solid rgba(255,68,85,.3)}
.btn-danger:hover{background:rgba(255,68,85,.25)}
.btn-success{background:rgba(0,255,136,.15);color:var(--green);border:1px solid rgba(0,255,136,.3)}

/* Forms */
.form-group{margin-bottom:16px}
.form-group label{display:block;font-size:13px;color:var(--text2);margin-bottom:6px;font-weight:600}
.form-group input,.form-group select,.form-group textarea{width:100%;padding:10px 12px;background:var(--bg);border:1px solid var(--border);border-radius:6px;color:var(--text);font-size:14px;outline:none;font-family:inherit}
.form-group input:focus,.form-group select:focus{border-color:var(--accent)}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}

/* Loading */
.loading{text-align:center;padding:40px;color:var(--text2)}
.loading::after{content:'';display:inline-block;width:20px;height:20px;border:2px solid var(--border);border-top-color:var(--accent);border-radius:50%;animation:spin .8s linear infinite;margin-left:8px;vertical-align:middle}
@keyframes spin{to{transform:rotate(360deg)}}

/* Toast */
.toast{position:fixed;bottom:24px;right:24px;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);padding:14px 20px;font-size:14px;z-index:1000;transform:translateY(100px);opacity:0;transition:all .3s}
.toast.show{transform:translateY(0);opacity:1}
.toast.success{border-color:var(--green);color:var(--green)}
.toast.error{border-color:var(--red);color:var(--red)}

/* Section Pages (hidden by default) */
.page{display:none}
.page.active{display:block}

/* Terminal output */
.terminal-out{background:#000;border:1px solid #333;border-radius:6px;padding:16px;font-family:'Fira Code','Courier New',monospace;font-size:13px;color:#ccc;max-height:400px;overflow-y:auto;white-space:pre-wrap;word-break:break-all}

/* Modal */
.modal-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.6);z-index:200;display:none;align-items:center;justify-content:center}
.modal-overlay.show{display:flex}
.modal{background:var(--bg2);border:1px solid var(--border);border-radius:16px;padding:32px;max-width:500px;width:90%;max-height:80vh;overflow-y:auto}
.modal h3{font-size:20px;font-weight:800;margin-bottom:20px}
.modal .close{float:right;background:none;border:none;color:var(--text2);font-size:24px;cursor:pointer}

/* Responsive */
@media(max-width:768px){
.sidebar{display:none}
.main{padding:16px}
.stat-grid{grid-template-columns:1fr 1fr}
.form-row{grid-template-columns:1fr}
}
</style>
</head>
<body>

<!-- ═══ LOGIN SCREEN ═══ -->
<div id="login-screen">
    <div class="login-box">
        <div class="logo">GoHostMe</div>
        <div class="sub">AI-Powered Cloud Control Panel</div>
        <form id="login-form">
            <input type="text" id="login-email" placeholder="Email address" autocomplete="email" required>
            <input type="password" id="login-password" placeholder="Password" autocomplete="current-password" required>
            <button type="submit" class="btn">Sign In</button>
            <div class="error" id="login-error"></div>
        </form>
    </div>
</div>

<!-- ═══ MAIN APPLICATION ═══ -->
<div id="app">
    <!-- Top Bar -->
    <div class="topbar">
        <div class="logo">GoHostMe</div>
        <div class="user-info">
            <span class="name" id="user-name">Commander</span>
            <button class="logout-btn" onclick="logout()">Sign Out</button>
        </div>
    </div>

    <div class="layout">
        <!-- Sidebar Navigation -->
        <div class="sidebar">
            <div class="section-title">Overview</div>
            <div class="nav-item active" data-page="dashboard"><span class="icon">📊</span> Dashboard</div>

            <div class="section-title">Infrastructure</div>
            <div class="nav-item" data-page="domains"><span class="icon">🌐</span> Domains</div>
            <div class="nav-item" data-page="dns"><span class="icon">📡</span> DNS</div>
            <div class="nav-item" data-page="databases"><span class="icon">🗄</span> Databases</div>
            <div class="nav-item" data-page="files"><span class="icon">📁</span> Files</div>
            <div class="nav-item" data-page="ssl"><span class="icon">🔒</span> SSL/TLS</div>

            <div class="section-title">Services</div>
            <div class="nav-item" data-page="pm2"><span class="icon">⚙️</span> PM2 Processes</div>
            <div class="nav-item" data-page="email"><span class="icon">📧</span> Email</div>
            <div class="nav-item" data-page="cron"><span class="icon">⏰</span> Cron Jobs</div>
            <div class="nav-item" data-page="backups"><span class="icon">💾</span> Backups</div>

            <div class="section-title">AI Development</div>
            <div class="nav-item" data-page="ide"><span class="icon">💻</span> Alfred IDE</div>

            <div class="section-title">Cloud</div>
            <div class="nav-item" data-page="cloud"><span class="icon">☁️</span> OVH Cloud</div>

            <div class="section-title">Quick Links</div>
            <div class="nav-item" onclick="window.open('/alfred-ide/','_blank')"><span class="icon">🚀</span> Open IDE</div>
            <div class="nav-item" onclick="window.open('/clientarea.php','_blank')"><span class="icon">📋</span> Billing (WHMCS)</div>
        </div>

        <!-- Main Content Area -->
        <div class="main">

            <!-- ═══ DASHBOARD ═══ -->
            <div class="page active" id="page-dashboard">
                <h1 class="page-title">Dashboard</h1>
                <p class="page-sub">System overview and quick stats</p>
                <div class="stat-grid" id="dash-stats">
                    <div class="stat-card"><div class="label">Loading...</div><div class="value">—</div></div>
                </div>
                <div class="panel">
                    <div class="panel-header"><h3>Server Health</h3><button class="btn btn-sm btn-primary" onclick="loadDashboard()">Refresh</button></div>
                    <div class="panel-body" id="dash-health"><div class="loading">Loading</div></div>
                </div>
                <div class="panel">
                    <div class="panel-header"><h3>PM2 Services (Quick View)</h3></div>
                    <div class="panel-body" id="dash-pm2"><div class="loading">Loading</div></div>
                </div>
            </div>

            <!-- ═══ DOMAINS ═══ -->
            <div class="page" id="page-domains">
                <h1 class="page-title">Domains</h1>
                <p class="page-sub">Manage your domains</p>
                <div class="panel">
                    <div class="panel-header">
                        <h3>Domain List</h3>
                        <button class="btn btn-sm btn-primary" onclick="showModal('domain-modal')">+ Add Domain</button>
                    </div>
                    <div class="panel-body" id="domains-list"><div class="loading">Loading</div></div>
                </div>
            </div>

            <!-- ═══ DNS ═══ -->
            <div class="page" id="page-dns">
                <h1 class="page-title">DNS Management</h1>
                <p class="page-sub">Manage DNS records for your domains</p>
                <div class="panel">
                    <div class="panel-header">
                        <h3>Select Domain</h3>
                    </div>
                    <div class="panel-body">
                        <div class="form-row">
                            <div class="form-group">
                                <select id="dns-domain-select" onchange="loadDNS()">
                                    <option value="">Select a domain...</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel" id="dns-records-panel" style="display:none">
                    <div class="panel-header">
                        <h3>DNS Records</h3>
                        <button class="btn btn-sm btn-primary" onclick="showModal('dns-modal')">+ Add Record</button>
                    </div>
                    <div class="panel-body" id="dns-records"><div class="loading">Loading</div></div>
                </div>
            </div>

            <!-- ═══ DATABASES ═══ -->
            <div class="page" id="page-databases">
                <h1 class="page-title">Databases</h1>
                <p class="page-sub">MySQL / MariaDB database management</p>
                <div class="panel">
                    <div class="panel-header">
                        <h3>Database List</h3>
                        <button class="btn btn-sm btn-primary" onclick="showModal('db-modal')">+ Create Database</button>
                    </div>
                    <div class="panel-body" id="db-list"><div class="loading">Loading</div></div>
                </div>
            </div>

            <!-- ═══ FILES ═══ -->
            <div class="page" id="page-files">
                <h1 class="page-title">File Manager</h1>
                <p class="page-sub">Browse and manage files on your server</p>
                <div class="panel">
                    <div class="panel-header">
                        <h3>Path: <span id="files-path">/</span></h3>
                        <div>
                            <button class="btn btn-sm btn-primary" onclick="showModal('file-modal')">+ New File</button>
                            <button class="btn btn-sm btn-success" onclick="showModal('mkdir-modal')">+ New Folder</button>
                        </div>
                    </div>
                    <div class="panel-body" id="files-list"><div class="loading">Loading</div></div>
                </div>
            </div>

            <!-- ═══ SSL ═══ -->
            <div class="page" id="page-ssl">
                <h1 class="page-title">SSL / TLS Certificates</h1>
                <p class="page-sub">Manage HTTPS certificates for your domains</p>
                <div class="panel">
                    <div class="panel-header"><h3>Certificate Status</h3></div>
                    <div class="panel-body" id="ssl-list"><div class="loading">Loading</div></div>
                </div>
            </div>

            <!-- ═══ PM2 PROCESSES ═══ -->
            <div class="page" id="page-pm2">
                <h1 class="page-title">PM2 Process Manager</h1>
                <p class="page-sub">Monitor and control running services</p>
                <div class="panel">
                    <div class="panel-header">
                        <h3>Processes</h3>
                        <button class="btn btn-sm btn-primary" onclick="loadPM2()">Refresh</button>
                    </div>
                    <div class="panel-body" id="pm2-list"><div class="loading">Loading</div></div>
                </div>
            </div>

            <!-- ═══ EMAIL ═══ -->
            <div class="page" id="page-email">
                <h1 class="page-title">Email Accounts</h1>
                <p class="page-sub">Manage email addresses for your domains</p>
                <div class="panel">
                    <div class="panel-header">
                        <h3>Email Accounts</h3>
                        <button class="btn btn-sm btn-primary" onclick="showModal('email-modal')">+ Create Account</button>
                    </div>
                    <div class="panel-body" id="email-list"><div class="loading">Loading</div></div>
                </div>
            </div>

            <!-- ═══ CRON JOBS ═══ -->
            <div class="page" id="page-cron">
                <h1 class="page-title">Cron Jobs</h1>
                <p class="page-sub">Scheduled tasks and automation</p>
                <div class="panel">
                    <div class="panel-header">
                        <h3>Active Cron Jobs</h3>
                        <button class="btn btn-sm btn-primary" onclick="showModal('cron-modal')">+ Add Cron Job</button>
                    </div>
                    <div class="panel-body" id="cron-list"><div class="loading">Loading</div></div>
                </div>
            </div>

            <!-- ═══ BACKUPS ═══ -->
            <div class="page" id="page-backups">
                <h1 class="page-title">Backups</h1>
                <p class="page-sub">Database and file backups</p>
                <div class="panel">
                    <div class="panel-header">
                        <h3>Available Backups</h3>
                        <div>
                            <button class="btn btn-sm btn-primary" onclick="createBackup('database')">Backup Database</button>
                            <button class="btn btn-sm btn-success" onclick="createBackup('files')">Backup Files</button>
                        </div>
                    </div>
                    <div class="panel-body" id="backup-list"><div class="loading">Loading</div></div>
                </div>
            </div>

            <!-- ═══ ALFRED IDE ═══ -->
            <div class="page" id="page-ide">
                <h1 class="page-title">Alfred IDE Instances</h1>
                <p class="page-sub">Manage browser-based code editor instances</p>
                <div class="stat-grid" id="ide-stats"></div>
                <div class="panel">
                    <div class="panel-header">
                        <h3>IDE Instances</h3>
                        <button class="btn btn-sm btn-primary" onclick="showModal('ide-modal')">+ Provision IDE</button>
                    </div>
                    <div class="panel-body" id="ide-list"><div class="loading">Loading</div></div>
                </div>
            </div>

            <!-- ═══ OVH CLOUD ═══ -->
            <div class="page" id="page-cloud">
                <h1 class="page-title">OVH Cloud</h1>
                <p class="page-sub">Manage cloud instances and infrastructure on OVH</p>
                <div class="stat-grid" id="cloud-stats"></div>
                <div class="panel">
                    <div class="panel-header">
                        <h3>Cloud Instances</h3>
                        <button class="btn btn-sm btn-primary" onclick="showModal('cloud-modal')">+ Create Instance</button>
                    </div>
                    <div class="panel-body" id="cloud-instances"><div class="loading">Loading</div></div>
                </div>
                <div class="panel">
                    <div class="panel-header"><h3>SSH Keys</h3></div>
                    <div class="panel-body" id="cloud-ssh-keys"><div class="loading">Loading</div></div>
                </div>
            </div>

        </div><!-- /main -->
    </div><!-- /layout -->
</div><!-- /app -->

<!-- ═══ MODALS ═══ -->

<!-- Domain Modal -->
<div class="modal-overlay" id="domain-modal">
    <div class="modal">
        <button class="close" onclick="hideModal('domain-modal')">&times;</button>
        <h3>Add Domain</h3>
        <div class="form-group"><label>Domain Name</label><input type="text" id="new-domain" placeholder="example.com"></div>
        <button class="btn btn-primary" onclick="createDomain()">Add Domain</button>
    </div>
</div>

<!-- DNS Record Modal -->
<div class="modal-overlay" id="dns-modal">
    <div class="modal">
        <button class="close" onclick="hideModal('dns-modal')">&times;</button>
        <h3>Add DNS Record</h3>
        <div class="form-row">
            <div class="form-group"><label>Type</label><select id="dns-type"><option>A</option><option>AAAA</option><option>CNAME</option><option>MX</option><option>TXT</option><option>SRV</option><option>NS</option></select></div>
            <div class="form-group"><label>Name</label><input type="text" id="dns-name" placeholder="@ or subdomain"></div>
        </div>
        <div class="form-group"><label>Value</label><input type="text" id="dns-value" placeholder="IP or target"></div>
        <div class="form-group"><label>TTL</label><input type="number" id="dns-ttl" value="3600"></div>
        <button class="btn btn-primary" onclick="addDNSRecord()">Add Record</button>
    </div>
</div>

<!-- Database Modal -->
<div class="modal-overlay" id="db-modal">
    <div class="modal">
        <button class="close" onclick="hideModal('db-modal')">&times;</button>
        <h3>Create Database</h3>
        <div class="form-group"><label>Database Name</label><input type="text" id="new-db-name" placeholder="myapp_db"></div>
        <button class="btn btn-primary" onclick="createDatabase()">Create</button>
    </div>
</div>

<!-- Email Modal -->
<div class="modal-overlay" id="email-modal">
    <div class="modal">
        <button class="close" onclick="hideModal('email-modal')">&times;</button>
        <h3>Create Email Account</h3>
        <div class="form-group"><label>Email Address</label><input type="text" id="new-email" placeholder="user@yourdomain.com"></div>
        <div class="form-group"><label>Password</label><input type="password" id="new-email-pass" placeholder="Strong password"></div>
        <button class="btn btn-primary" onclick="createEmail()">Create Account</button>
    </div>
</div>

<!-- Cron Modal -->
<div class="modal-overlay" id="cron-modal">
    <div class="modal">
        <button class="close" onclick="hideModal('cron-modal')">&times;</button>
        <h3>Add Cron Job</h3>
        <div class="form-group"><label>Schedule (cron expression)</label><input type="text" id="new-cron-schedule" placeholder="*/5 * * * *"></div>
        <div class="form-group"><label>Command</label><input type="text" id="new-cron-command" placeholder="/usr/bin/php /path/to/script.php"></div>
        <button class="btn btn-primary" onclick="addCronJob()">Add Cron Job</button>
    </div>
</div>

<!-- File Creation Modal -->
<div class="modal-overlay" id="file-modal">
    <div class="modal">
        <button class="close" onclick="hideModal('file-modal')">&times;</button>
        <h3>Create File</h3>
        <div class="form-group"><label>Filename</label><input type="text" id="new-filename" placeholder="newfile.txt"></div>
        <div class="form-group"><label>Content</label><textarea id="new-file-content" rows="6" placeholder="File content..."></textarea></div>
        <button class="btn btn-primary" onclick="createFileAction()">Create</button>
    </div>
</div>

<!-- Mkdir Modal -->
<div class="modal-overlay" id="mkdir-modal">
    <div class="modal">
        <button class="close" onclick="hideModal('mkdir-modal')">&times;</button>
        <h3>Create Directory</h3>
        <div class="form-group"><label>Directory Name</label><input type="text" id="new-dirname" placeholder="new-folder"></div>
        <button class="btn btn-primary" onclick="createDir()">Create Directory</button>
    </div>
</div>

<!-- IDE Provision Modal -->
<div class="modal-overlay" id="ide-modal">
    <div class="modal">
        <button class="close" onclick="hideModal('ide-modal')">&times;</button>
        <h3>Provision Alfred IDE Instance</h3>
        <div class="form-group"><label>Username</label><input type="text" id="ide-username" placeholder="customer-username"></div>
        <div class="form-group"><label>Port (9000-9999)</label><input type="number" id="ide-port" min="9000" max="9999" placeholder="9001"></div>
        <div class="form-group"><label>Password (optional, auto-generated if empty)</label><input type="text" id="ide-password" placeholder="Leave blank to auto-generate"></div>
        <button class="btn btn-primary" onclick="provisionIDE()">Provision</button>
    </div>
</div>

<!-- OVH Create Instance Modal -->
<div class="modal-overlay" id="cloud-modal">
    <div class="modal">
        <button class="close" onclick="hideModal('cloud-modal')">&times;</button>
        <h3>Create Cloud Instance</h3>
        <div class="form-group"><label>Instance Name</label><input type="text" id="cloud-name" placeholder="my-server"></div>
        <div class="form-group"><label>Region</label><select id="cloud-region"><option value="">Loading...</option></select></div>
        <div class="form-group"><label>Flavor (Size)</label><select id="cloud-flavor"><option value="">Loading...</option></select></div>
        <div class="form-group"><label>Image (OS)</label><select id="cloud-image"><option value="">Loading...</option></select></div>
        <div class="form-group"><label><input type="checkbox" id="cloud-monthly"> Monthly Billing</label></div>
        <button class="btn btn-primary" onclick="createCloudInstance()">Create Instance</button>
    </div>
</div>

<!-- Toast -->
<div class="toast" id="toast"></div>

<script>
// ═══════════════════════════════════════════════
//  GoHostMe Dashboard — Client-Side Application
// ═══════════════════════════════════════════════

const API_BASE = '/gohostme/api';
let authToken = localStorage.getItem('gohostme_token');
let currentUser = null;
let currentFilePath = '/';

// ── API Helper ──
async function api(method, path, body = null) {
    const opts = {
        method,
        headers: { 'Content-Type': 'application/json' }
    };
    if (authToken) opts.headers['Authorization'] = 'Bearer ' + authToken;
    if (body) opts.body = JSON.stringify(body);
    const res = await fetch(API_BASE + path, opts);
    const data = await res.json();
    if (res.status === 401) { logout(); throw new Error('Session expired'); }
    if (!res.ok && data.error) throw new Error(data.error);
    return data;
}

// OVH API (different base)
async function ovhApi(action, method = 'GET', body = null) {
    const opts = { method, headers: { 'Content-Type': 'application/json' } };
    if (body) opts.body = JSON.stringify(body);
    // OVH API uses session auth (Commander must be logged in to WHMCS)
    const res = await fetch('/api/ovh/?action=' + action, opts);
    return await res.json();
}

// ── Toast notifications ──
function toast(msg, type = 'success') {
    const el = document.getElementById('toast');
    el.textContent = msg;
    el.className = 'toast show ' + type;
    setTimeout(() => el.className = 'toast', 3000);
}

// ── Modals ──
function showModal(id) { document.getElementById(id).classList.add('show'); }
function hideModal(id) { document.getElementById(id).classList.remove('show'); }

// ═══ AUTH ═══
document.getElementById('login-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const email = document.getElementById('login-email').value;
    const password = document.getElementById('login-password').value;
    const errEl = document.getElementById('login-error');
    try {
        const data = await fetch(API_BASE + '/auth/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        }).then(r => r.json());
        if (data.error) { errEl.textContent = data.error; errEl.style.display = 'block'; return; }
        authToken = data.token;
        localStorage.setItem('gohostme_token', authToken);
        currentUser = data.user;
        showApp();
    } catch (err) {
        errEl.textContent = 'Connection failed. Try again.';
        errEl.style.display = 'block';
    }
});

function logout() {
    authToken = null;
    currentUser = null;
    localStorage.removeItem('gohostme_token');
    document.getElementById('app').style.display = 'none';
    document.getElementById('login-screen').style.display = 'flex';
}

async function showApp() {
    document.getElementById('login-screen').style.display = 'none';
    document.getElementById('app').style.display = 'block';
    if (!currentUser) {
        try { currentUser = await api('GET', '/auth/me'); } catch(e) { logout(); return; }
    }
    document.getElementById('user-name').textContent = currentUser.name || currentUser.firstname || 'User';
    loadDashboard();
}

// ── Check existing session ──
if (authToken) { showApp(); }

// ═══ NAVIGATION ═══
document.querySelectorAll('.nav-item[data-page]').forEach(item => {
    item.addEventListener('click', () => {
        document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
        item.classList.add('active');
        const page = item.dataset.page;
        document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
        document.getElementById('page-' + page).classList.add('active');
        // Load page data
        const loaders = { dashboard: loadDashboard, domains: loadDomains, dns: loadDNSSelect, databases: loadDatabases, files: loadFiles, ssl: loadSSL, pm2: loadPM2, email: loadEmail, cron: loadCron, backups: loadBackups, ide: loadIDE, cloud: loadCloud };
        if (loaders[page]) loaders[page]();
    });
});

// ═══ DASHBOARD ═══
async function loadDashboard() {
    try {
        const [stats, panel] = await Promise.all([
            api('GET', '/system/stats'),
            api('GET', '/panel/stats').catch(() => null)
        ]);
        const cpuPercent = stats.cpu ? (stats.cpu.loadavg?.[0] || 0) : 0;
        const memPct = stats.memory ? Math.round((stats.memory.used / stats.memory.total) * 100) : 0;
        const diskPct = stats.disk ? Math.round((stats.disk.used / stats.disk.total) * 100) : 0;

        document.getElementById('dash-stats').innerHTML = `
            <div class="stat-card"><div class="label">CPU Load</div><div class="value accent">${(stats.cpu?.loadavg?.[0] || 0).toFixed(1)}</div><div class="sub">${stats.cpu?.cores || '?'} cores</div></div>
            <div class="stat-card"><div class="label">Memory</div><div class="value accent">${memPct}%</div><div class="sub">${formatBytes(stats.memory?.used || 0)} / ${formatBytes(stats.memory?.total || 0)}</div></div>
            <div class="stat-card"><div class="label">Disk</div><div class="value accent">${diskPct}%</div><div class="sub">${formatBytes(stats.disk?.used || 0)} / ${formatBytes(stats.disk?.total || 0)}</div></div>
            <div class="stat-card"><div class="label">Uptime</div><div class="value accent">${Math.floor((stats.uptime || 0) / 3600)}h</div><div class="sub">${stats.platform || 'Linux'}</div></div>
        `;

        document.getElementById('dash-health').innerHTML = `
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px">
                <div><strong>OS:</strong> ${stats.platform || 'Linux'} ${stats.arch || ''}</div>
                <div><strong>Hostname:</strong> ${stats.hostname || '—'}</div>
                <div><strong>Node.js:</strong> ${stats.nodeVersion || '—'}</div>
                <div><strong>Load Avg:</strong> ${(stats.cpu?.loadavg || []).map(v => v.toFixed(2)).join(' / ')}</div>
            </div>
        `;

        // PM2 quick view
        try {
            const pm2 = await api('GET', '/pm2/list');
            const procs = pm2.processes || pm2 || [];
            const online = procs.filter(p => p.status === 'online').length;
            const stopped = procs.filter(p => p.status === 'stopped').length;
            document.getElementById('dash-pm2').innerHTML = `
                <div style="margin-bottom:12px"><span class="badge green">${online} online</span> <span class="badge red">${stopped} stopped</span> <span class="badge blue">${procs.length} total</span></div>
                <table><thead><tr><th>Name</th><th>Status</th><th>CPU</th><th>Memory</th><th>Restarts</th></tr></thead><tbody>
                ${procs.slice(0, 15).map(p => `<tr><td>${esc(p.name)}</td><td><span class="badge ${p.status === 'online' ? 'green' : 'red'}">${p.status}</span></td><td>${p.cpu || 0}%</td><td>${formatBytes(p.memory || 0)}</td><td>${p.restarts || 0}</td></tr>`).join('')}
                ${procs.length > 15 ? `<tr><td colspan="5" style="color:var(--text2)">... and ${procs.length - 15} more</td></tr>` : ''}
                </tbody></table>
            `;
        } catch(e) { document.getElementById('dash-pm2').innerHTML = '<p style="color:var(--text2)">Could not load PM2 data</p>'; }
    } catch (err) {
        document.getElementById('dash-stats').innerHTML = `<div class="stat-card"><div class="label">Error</div><div class="value" style="color:var(--red);font-size:16px">${esc(err.message)}</div></div>`;
    }
}

// ═══ DOMAINS ═══
async function loadDomains() {
    try {
        const data = await api('GET', '/domains');
        const domains = data.domains || data || [];
        if (!domains.length) { document.getElementById('domains-list').innerHTML = '<p style="color:var(--text2)">No domains found</p>'; return; }
        document.getElementById('domains-list').innerHTML = `<table><thead><tr><th>Domain</th><th>Path</th><th>Actions</th></tr></thead><tbody>
            ${domains.map(d => `<tr><td><strong>${esc(typeof d === 'string' ? d : d.domain)}</strong></td><td style="color:var(--text2)">${esc(d.path || '—')}</td><td><button class="btn btn-sm btn-danger" onclick="deleteDomain('${esc(typeof d === 'string' ? d : d.domain)}')">Remove</button></td></tr>`).join('')}
        </tbody></table>`;
    } catch (err) { document.getElementById('domains-list').innerHTML = `<p style="color:var(--red)">${esc(err.message)}</p>`; }
}

async function createDomain() {
    const domain = document.getElementById('new-domain').value.trim();
    if (!domain) return;
    try { await api('POST', '/domains/create', { domain }); toast('Domain added'); hideModal('domain-modal'); loadDomains(); } catch(e) { toast(e.message, 'error'); }
}

async function deleteDomain(domain) {
    if (!confirm('Delete domain ' + domain + '?')) return;
    try { await api('DELETE', '/domains/' + encodeURIComponent(domain)); toast('Domain removed'); loadDomains(); } catch(e) { toast(e.message, 'error'); }
}

// ═══ DNS ═══
async function loadDNSSelect() {
    try {
        const data = await api('GET', '/domains');
        const domains = data.domains || data || [];
        const sel = document.getElementById('dns-domain-select');
        sel.innerHTML = '<option value="">Select a domain...</option>' + domains.map(d => {
            const name = typeof d === 'string' ? d : d.domain;
            return `<option value="${esc(name)}">${esc(name)}</option>`;
        }).join('');
    } catch(e) {}
}

async function loadDNS() {
    const domain = document.getElementById('dns-domain-select').value;
    if (!domain) { document.getElementById('dns-records-panel').style.display = 'none'; return; }
    document.getElementById('dns-records-panel').style.display = 'block';
    try {
        const data = await api('GET', '/dns/' + encodeURIComponent(domain));
        const records = data.records || data || [];
        document.getElementById('dns-records').innerHTML = `<table><thead><tr><th>Type</th><th>Name</th><th>Value</th><th>TTL</th><th>Actions</th></tr></thead><tbody>
            ${records.map(r => `<tr><td><span class="badge blue">${esc(r.type)}</span></td><td>${esc(r.name || '@')}</td><td style="max-width:300px;overflow:hidden;text-overflow:ellipsis">${esc(r.value)}</td><td>${r.ttl || 3600}</td><td><button class="btn btn-sm btn-danger" onclick="deleteDNSRecord('${esc(domain)}','${esc(r.name)}','${esc(r.type)}')">Delete</button></td></tr>`).join('')}
        </tbody></table>`;
    } catch(e) { document.getElementById('dns-records').innerHTML = `<p style="color:var(--red)">${esc(e.message)}</p>`; }
}

async function addDNSRecord() {
    const domain = document.getElementById('dns-domain-select').value;
    if (!domain) return;
    try {
        await api('POST', '/dns/' + encodeURIComponent(domain) + '/record', {
            type: document.getElementById('dns-type').value,
            name: document.getElementById('dns-name').value,
            value: document.getElementById('dns-value').value,
            ttl: parseInt(document.getElementById('dns-ttl').value) || 3600
        });
        toast('DNS record added'); hideModal('dns-modal'); loadDNS();
    } catch(e) { toast(e.message, 'error'); }
}

async function deleteDNSRecord(domain, name, type) {
    if (!confirm('Delete ' + type + ' record for ' + name + '?')) return;
    try { await api('DELETE', '/dns/' + encodeURIComponent(domain) + '/record', { name, type }); toast('Record deleted'); loadDNS(); } catch(e) { toast(e.message, 'error'); }
}

// ═══ DATABASES ═══
async function loadDatabases() {
    try {
        const data = await api('GET', '/databases');
        const dbs = data.databases || data || [];
        document.getElementById('db-list').innerHTML = `<table><thead><tr><th>Database Name</th><th>Tables</th><th>Actions</th></tr></thead><tbody>
            ${dbs.map(d => {
                const name = typeof d === 'string' ? d : d.name;
                return `<tr><td><strong>${esc(name)}</strong></td><td><button class="btn btn-sm btn-primary" onclick="viewTables('${esc(name)}')">View Tables</button></td><td>${name.includes('whmcs') ? '<span class="badge orange">Protected</span>' : `<button class="btn btn-sm btn-danger" onclick="deleteDatabase('${esc(name)}')">Drop</button>`}</td></tr>`;
            }).join('')}
        </tbody></table>`;
    } catch(e) { document.getElementById('db-list').innerHTML = `<p style="color:var(--red)">${esc(e.message)}</p>`; }
}

async function createDatabase() {
    const name = document.getElementById('new-db-name').value.trim();
    if (!name) return;
    try { await api('POST', '/databases/create', { name }); toast('Database created'); hideModal('db-modal'); loadDatabases(); } catch(e) { toast(e.message, 'error'); }
}

async function deleteDatabase(name) {
    if (!confirm('DROP database ' + name + '? This cannot be undone!')) return;
    try { await api('DELETE', '/databases/' + encodeURIComponent(name)); toast('Database dropped'); loadDatabases(); } catch(e) { toast(e.message, 'error'); }
}

async function viewTables(dbName) {
    try {
        const data = await api('GET', '/databases/' + encodeURIComponent(dbName) + '/tables');
        const tables = data.tables || data || [];
        alert('Tables in ' + dbName + ':\n\n' + tables.map(t => typeof t === 'string' ? t : t.name || t.TABLE_NAME).join('\n'));
    } catch(e) { toast(e.message, 'error'); }
}

// ═══ FILES ═══
async function loadFiles(path) {
    currentFilePath = path || currentFilePath || '/';
    document.getElementById('files-path').textContent = currentFilePath;
    try {
        const data = await api('GET', '/files?path=' + encodeURIComponent(currentFilePath));
        const files = data.files || data || [];
        let html = '';
        if (currentFilePath !== '/') {
            const parent = currentFilePath.split('/').slice(0, -1).join('/') || '/';
            html += `<tr style="cursor:pointer" onclick="loadFiles('${esc(parent)}')"><td>📁 <strong>..</strong></td><td>—</td><td>—</td><td>—</td></tr>`;
        }
        files.forEach(f => {
            const name = f.name || f;
            const isDir = f.isDirectory || f.type === 'directory' || name.endsWith('/');
            const fullPath = currentFilePath === '/' ? '/' + name : currentFilePath + '/' + name;
            html += `<tr${isDir ? ' style="cursor:pointer" onclick="loadFiles(\'' + esc(fullPath) + '\')"' : ''}>
                <td>${isDir ? '📁' : '📄'} <strong>${esc(name)}</strong></td>
                <td>${f.size ? formatBytes(f.size) : '—'}</td>
                <td style="color:var(--text2)">${f.permissions || '—'}</td>
                <td>${isDir ? '' : `<button class="btn btn-sm btn-danger" onclick="event.stopPropagation();deleteFile('${esc(fullPath)}')">Delete</button>`}</td>
            </tr>`;
        });
        document.getElementById('files-list').innerHTML = `<table><thead><tr><th>Name</th><th>Size</th><th>Perms</th><th>Actions</th></tr></thead><tbody>${html}</tbody></table>`;
    } catch(e) { document.getElementById('files-list').innerHTML = `<p style="color:var(--red)">${esc(e.message)}</p>`; }
}

async function createFileAction() {
    const name = document.getElementById('new-filename').value.trim();
    const content = document.getElementById('new-file-content').value;
    if (!name) return;
    const fullPath = currentFilePath === '/' ? '/' + name : currentFilePath + '/' + name;
    try { await api('POST', '/files/write', { path: fullPath, content }); toast('File created'); hideModal('file-modal'); loadFiles(); } catch(e) { toast(e.message, 'error'); }
}

async function createDir() {
    const name = document.getElementById('new-dirname').value.trim();
    if (!name) return;
    const fullPath = currentFilePath === '/' ? '/' + name : currentFilePath + '/' + name;
    try { await api('POST', '/files/mkdir', { path: fullPath }); toast('Directory created'); hideModal('mkdir-modal'); loadFiles(); } catch(e) { toast(e.message, 'error'); }
}

async function deleteFile(path) {
    if (!confirm('Delete ' + path + '?')) return;
    try { await api('DELETE', '/files?path=' + encodeURIComponent(path)); toast('Deleted'); loadFiles(); } catch(e) { toast(e.message, 'error'); }
}

// ═══ SSL ═══
async function loadSSL() {
    try {
        const data = await api('GET', '/domains');
        const domains = data.domains || data || [];
        let html = '<table><thead><tr><th>Domain</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
        for (const d of domains.slice(0, 10)) {
            const domain = typeof d === 'string' ? d : d.domain;
            html += `<tr><td>${esc(domain)}</td><td><span class="badge blue">Check</span></td><td><button class="btn btn-sm btn-primary" onclick="requestSSL('${esc(domain)}')">Request SSL</button></td></tr>`;
        }
        html += '</tbody></table>';
        document.getElementById('ssl-list').innerHTML = html;
    } catch(e) { document.getElementById('ssl-list').innerHTML = `<p style="color:var(--red)">${esc(e.message)}</p>`; }
}

async function requestSSL(domain) {
    try { const r = await api('POST', '/ssl/request', { domain }); toast(r.message || 'SSL requested'); loadSSL(); } catch(e) { toast(e.message, 'error'); }
}

// ═══ PM2 ═══
async function loadPM2() {
    try {
        const data = await api('GET', '/pm2/list');
        const procs = data.processes || data || [];
        document.getElementById('pm2-list').innerHTML = `<table><thead><tr><th>ID</th><th>Name</th><th>Status</th><th>CPU</th><th>Memory</th><th>Restarts</th><th>Uptime</th><th>Actions</th></tr></thead><tbody>
            ${procs.map(p => `<tr>
                <td>${p.pm_id ?? p.id ?? '—'}</td>
                <td><strong>${esc(p.name)}</strong></td>
                <td><span class="badge ${p.status === 'online' ? 'green' : 'red'}">${p.status}</span></td>
                <td>${p.cpu || 0}%</td>
                <td>${formatBytes(p.memory || 0)}</td>
                <td>${p.restarts || 0}</td>
                <td style="color:var(--text2)">${p.uptime ? formatUptime(p.uptime) : '—'}</td>
                <td>
                    ${p.status === 'online' ? `<button class="btn btn-sm btn-danger" onclick="pm2Action('${esc(p.name)}','stop')">Stop</button>` : `<button class="btn btn-sm btn-success" onclick="pm2Action('${esc(p.name)}','start')">Start</button>`}
                    <button class="btn btn-sm btn-primary" onclick="pm2Action('${esc(p.name)}','restart')">Restart</button>
                </td>
            </tr>`).join('')}
        </tbody></table>`;
    } catch(e) { document.getElementById('pm2-list').innerHTML = `<p style="color:var(--red)">${esc(e.message)}</p>`; }
}

async function pm2Action(name, action) {
    try { await api('POST', '/pm2/action', { name, action }); toast(name + ' ' + action + 'ed'); setTimeout(loadPM2, 1000); } catch(e) { toast(e.message, 'error'); }
}

// ═══ EMAIL ═══
async function loadEmail() {
    try {
        const data = await api('GET', '/email/accounts');
        const accounts = data.accounts || data || [];
        if (!accounts.length) { document.getElementById('email-list').innerHTML = '<p style="color:var(--text2)">No email accounts</p>'; return; }
        document.getElementById('email-list').innerHTML = `<table><thead><tr><th>Email</th><th>Domain</th><th>Usage</th><th>Actions</th></tr></thead><tbody>
            ${accounts.map(a => `<tr><td><strong>${esc(a.email || a.account || a)}</strong></td><td>${esc(a.domain || '—')}</td><td>${a.usage || '—'}</td><td><button class="btn btn-sm btn-danger" onclick="deleteEmail('${esc(a.email || a.account || a)}')">Delete</button></td></tr>`).join('')}
        </tbody></table>`;
    } catch(e) { document.getElementById('email-list').innerHTML = `<p style="color:var(--red)">${esc(e.message)}</p>`; }
}

async function createEmail() {
    const email = document.getElementById('new-email').value.trim();
    const password = document.getElementById('new-email-pass').value;
    if (!email || !password) return;
    try { await api('POST', '/email/create', { email, password }); toast('Email account created'); hideModal('email-modal'); loadEmail(); } catch(e) { toast(e.message, 'error'); }
}

async function deleteEmail(email) {
    if (!confirm('Delete email account ' + email + '?')) return;
    try { await api('DELETE', '/email/account', { email }); toast('Account deleted'); loadEmail(); } catch(e) { toast(e.message, 'error'); }
}

// ═══ CRON ═══
async function loadCron() {
    try {
        const data = await api('GET', '/cron');
        const jobs = data.jobs || data || [];
        if (!jobs.length) { document.getElementById('cron-list').innerHTML = '<p style="color:var(--text2)">No cron jobs found</p>'; return; }
        document.getElementById('cron-list').innerHTML = `<table><thead><tr><th>Schedule</th><th>Command</th><th>Actions</th></tr></thead><tbody>
            ${jobs.map((j, i) => `<tr><td><code style="color:var(--accent)">${esc(j.schedule || j.time || '—')}</code></td><td style="max-width:400px;overflow:hidden;text-overflow:ellipsis;font-family:monospace;font-size:12px">${esc(j.command || j)}</td><td><button class="btn btn-sm btn-danger" onclick="deleteCron(${i})">Delete</button></td></tr>`).join('')}
        </tbody></table>`;
    } catch(e) { document.getElementById('cron-list').innerHTML = `<p style="color:var(--red)">${esc(e.message)}</p>`; }
}

async function addCronJob() {
    const schedule = document.getElementById('new-cron-schedule').value.trim();
    const command = document.getElementById('new-cron-command').value.trim();
    if (!schedule || !command) return;
    try { await api('POST', '/cron', { schedule, command }); toast('Cron job added'); hideModal('cron-modal'); loadCron(); } catch(e) { toast(e.message, 'error'); }
}

async function deleteCron(index) {
    if (!confirm('Delete this cron job?')) return;
    try { await api('DELETE', '/cron', { index }); toast('Cron job removed'); loadCron(); } catch(e) { toast(e.message, 'error'); }
}

// ═══ BACKUPS ═══
async function loadBackups() {
    try {
        const data = await api('GET', '/backups');
        const backups = data.backups || data || [];
        if (!backups.length) { document.getElementById('backup-list').innerHTML = '<p style="color:var(--text2)">No backups found. Create one using the buttons above.</p>'; return; }
        document.getElementById('backup-list').innerHTML = `<table><thead><tr><th>Filename</th><th>Size</th><th>Date</th><th>Type</th></tr></thead><tbody>
            ${backups.map(b => `<tr><td>${esc(b.name || b.filename || b)}</td><td>${b.size ? formatBytes(b.size) : '—'}</td><td style="color:var(--text2)">${b.date || b.created || '—'}</td><td><span class="badge blue">${b.type || 'unknown'}</span></td></tr>`).join('')}
        </tbody></table>`;
    } catch(e) { document.getElementById('backup-list').innerHTML = `<p style="color:var(--red)">${esc(e.message)}</p>`; }
}

async function createBackup(type) {
    toast('Creating ' + type + ' backup...', 'success');
    try { const r = await api('POST', '/backups/create', { type }); toast(r.message || 'Backup created'); loadBackups(); } catch(e) { toast(e.message, 'error'); }
}

// ═══ ALFRED IDE ═══
async function loadIDE() {
    try {
        const [instances, stats] = await Promise.all([
            api('GET', '/ide/instances').catch(() => ({ instances: [] })),
            api('GET', '/ide/stats').catch(() => ({}))
        ]);
        const list = instances.instances || instances || [];

        document.getElementById('ide-stats').innerHTML = `
            <div class="stat-card"><div class="label">Total Instances</div><div class="value accent">${stats.total || list.length}</div></div>
            <div class="stat-card"><div class="label">Online</div><div class="value" style="color:var(--green)">${stats.online || list.filter(i => i.status === 'online').length}</div></div>
            <div class="stat-card"><div class="label">Memory Usage</div><div class="value accent">${stats.totalMemory ? formatBytes(stats.totalMemory) : '—'}</div></div>
            <div class="stat-card"><div class="label">Available Ports</div><div class="value accent">${stats.availablePorts || '—'}</div></div>
        `;

        if (!list.length) { document.getElementById('ide-list').innerHTML = '<p style="color:var(--text2)">No IDE instances running</p>'; return; }
        document.getElementById('ide-list').innerHTML = `<table><thead><tr><th>Name</th><th>Status</th><th>Port</th><th>Memory</th><th>Restarts</th><th>Actions</th></tr></thead><tbody>
            ${list.map(i => `<tr>
                <td><strong>${esc(i.name)}</strong>${i.user ? ' <span style="color:var(--text2)">(' + esc(i.user) + ')</span>' : ''}</td>
                <td><span class="badge ${i.status === 'online' ? 'green' : 'red'}">${i.status}</span></td>
                <td>${i.port || '—'}</td>
                <td>${i.memory ? formatBytes(i.memory) : '—'}</td>
                <td>${i.restarts || 0}</td>
                <td>
                    ${i.name === 'alfred-ide' ? '<span class="badge orange">Commander</span>' : `
                        ${i.status === 'online' ? `<button class="btn btn-sm btn-danger" onclick="ideControl('${esc(i.name)}','stop')">Stop</button>` : `<button class="btn btn-sm btn-success" onclick="ideControl('${esc(i.name)}','start')">Start</button>`}
                        <button class="btn btn-sm btn-primary" onclick="ideControl('${esc(i.name)}','restart')">Restart</button>
                    `}
                </td>
            </tr>`).join('')}
        </tbody></table>`;
    } catch(e) { document.getElementById('ide-list').innerHTML = `<p style="color:var(--red)">${esc(e.message)}</p>`; }
}

async function ideControl(name, action) {
    try { await api('POST', '/ide/control', { instance: name, action }); toast(name + ' ' + action + 'ed'); setTimeout(loadIDE, 1000); } catch(e) { toast(e.message, 'error'); }
}

async function provisionIDE() {
    const username = document.getElementById('ide-username').value.trim();
    const port = parseInt(document.getElementById('ide-port').value);
    const password = document.getElementById('ide-password').value.trim();
    if (!username || !port) { toast('Username and port required', 'error'); return; }
    try {
        const r = await api('POST', '/ide/provision', { username, port, password: password || undefined });
        toast('IDE provisioned for ' + username + ' on port ' + port);
        hideModal('ide-modal');
        loadIDE();
    } catch(e) { toast(e.message, 'error'); }
}

// ═══ OVH CLOUD ═══
async function loadCloud() {
    try {
        const status = await ovhApi('status');
        if (status.status === 'not_configured') {
            document.getElementById('cloud-stats').innerHTML = `
                <div class="stat-card" style="grid-column:1/-1;border-color:var(--orange)">
                    <div class="label">OVH API Status</div>
                    <div class="value" style="color:var(--orange);font-size:18px">Not Configured</div>
                    <div class="sub">API keys needed. Generate at <a href="https://ca.api.ovh.com/createApp" target="_blank">ca.api.ovh.com/createApp</a> then store in vault as service_name="OVH API"</div>
                </div>`;
            document.getElementById('cloud-instances').innerHTML = '<p style="color:var(--text2)">OVH API not configured yet. API keys required.</p>';
            document.getElementById('cloud-ssh-keys').innerHTML = '';
            return;
        }

        if (status.status === 'connected') {
            document.getElementById('cloud-stats').innerHTML = `
                <div class="stat-card"><div class="label">API Status</div><div class="value" style="color:var(--green)">Connected</div></div>
                <div class="stat-card"><div class="label">Endpoint</div><div class="value accent" style="font-size:14px">${esc(status.endpoint || '—')}</div></div>
                <div class="stat-card"><div class="label">Server Time</div><div class="value accent" style="font-size:14px">${status.server_time || '—'}</div></div>
            `;
        }

        // Load instances
        const instances = await ovhApi('instances');
        const list = instances.instances || [];
        if (!list.length) {
            document.getElementById('cloud-instances').innerHTML = '<p style="color:var(--text2)">No cloud instances found</p>';
        } else {
            document.getElementById('cloud-instances').innerHTML = `<table><thead><tr><th>Name</th><th>Status</th><th>Region</th><th>IP</th><th>Flavor</th><th>Actions</th></tr></thead><tbody>
                ${list.map(i => `<tr>
                    <td><strong>${esc(i.name)}</strong></td>
                    <td><span class="badge ${i.status === 'ACTIVE' ? 'green' : 'orange'}">${esc(i.status)}</span></td>
                    <td>${esc(i.region || '—')}</td>
                    <td style="font-family:monospace">${i.ipAddresses ? i.ipAddresses.filter(ip => ip.version === 4).map(ip => esc(ip.ip)).join(', ') : '—'}</td>
                    <td>${esc(i.flavor?.name || '—')}</td>
                    <td><button class="btn btn-sm btn-danger" onclick="deleteCloudInstance('${esc(i.id)}')">Delete</button></td>
                </tr>`).join('')}
            </tbody></table>`;
        }

        // Load SSH keys
        const keys = await ovhApi('ssh_keys');
        const keyList = keys.ssh_keys || [];
        document.getElementById('cloud-ssh-keys').innerHTML = keyList.length ? `<table><thead><tr><th>Name</th><th>Fingerprint</th></tr></thead><tbody>
            ${keyList.map(k => `<tr><td>${esc(k.name)}</td><td style="font-family:monospace;font-size:12px;color:var(--text2)">${esc(k.fingerprint || '—')}</td></tr>`).join('')}
        </tbody></table>` : '<p style="color:var(--text2)">No SSH keys configured</p>';

        // Load modal dropdowns
        loadCloudDropdowns();
    } catch(e) {
        document.getElementById('cloud-stats').innerHTML = `<div class="stat-card"><div class="label">Error</div><div class="value" style="color:var(--red);font-size:16px">${esc(e.message)}</div></div>`;
    }
}

async function loadCloudDropdowns() {
    try {
        const [regions, flavors, images] = await Promise.all([
            ovhApi('regions'), ovhApi('flavors'), ovhApi('images')
        ]);
        const regSel = document.getElementById('cloud-region');
        regSel.innerHTML = '<option value="">Select region...</option>' + (regions.regions || []).map(r => {
            const name = typeof r === 'string' ? r : r.name;
            return `<option value="${esc(name)}">${esc(name)}</option>`;
        }).join('');

        const flvSel = document.getElementById('cloud-flavor');
        flvSel.innerHTML = '<option value="">Select size...</option>' + (flavors.flavors || []).map(f => `<option value="${esc(f.id)}">${esc(f.name)} (${f.vcpus} vCPU, ${f.ram}MB RAM, ${f.disk}GB)</option>`).join('');

        const imgSel = document.getElementById('cloud-image');
        imgSel.innerHTML = '<option value="">Select OS...</option>' + (images.images || []).map(i => `<option value="${esc(i.id)}">${esc(i.name)}</option>`).join('');
    } catch(e) {}
}

async function createCloudInstance() {
    const name = document.getElementById('cloud-name').value.trim();
    const region = document.getElementById('cloud-region').value;
    const flavorId = document.getElementById('cloud-flavor').value;
    const imageId = document.getElementById('cloud-image').value;
    const monthly = document.getElementById('cloud-monthly').checked;
    if (!name || !region || !flavorId || !imageId) { toast('All fields required', 'error'); return; }
    try {
        await ovhApi('create_instance', 'POST', { name, region, flavorId, imageId, monthly });
        toast('Instance creation initiated'); hideModal('cloud-modal'); setTimeout(loadCloud, 3000);
    } catch(e) { toast(e.message, 'error'); }
}

async function deleteCloudInstance(id) {
    if (!confirm('Delete this cloud instance? This cannot be undone!')) return;
    try { await ovhApi('delete_instance', 'POST', { id }); toast('Instance deletion initiated'); setTimeout(loadCloud, 3000); } catch(e) { toast(e.message, 'error'); }
}

// ═══ UTILITIES ═══
function esc(s) { if (!s) return ''; const d = document.createElement('div'); d.textContent = String(s); return d.innerHTML; }
function formatBytes(b) { if (!b) return '0 B'; const u = ['B','KB','MB','GB','TB']; let i = 0; while (b >= 1024 && i < u.length-1) { b /= 1024; i++; } return b.toFixed(i > 0 ? 1 : 0) + ' ' + u[i]; }
function formatUptime(ms) { const h = Math.floor(ms / 3600000); const m = Math.floor((ms % 3600000) / 60000); return h > 0 ? h + 'h ' + m + 'm' : m + 'm'; }
</script>
</body>
</html>
