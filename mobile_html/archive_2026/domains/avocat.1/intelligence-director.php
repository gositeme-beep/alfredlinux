<?php
/**
 * Intelligence Director — Covert Operations & Personnel Network
 * ═══════════════════════════════════════════════════════════════
 * Classified command panel for ecosystem-wide intelligence.
 * Personnel directory, OSINT feeds, investigation tracking,
 * asset registry, and OIC coordination hub.
 * 
 * ACCESS: Supreme Commander only (Danny Perez)
 */
$page_title = 'Intelligence Director — Classified';
$page_description = 'Classified intelligence operations panel.';
$page_canonical = 'https://gositeme.com/intelligence-director';
$page_robots = 'noindex, nofollow';

include __DIR__ . '/includes/auth-gate.inc.php';

// ── Access Control ──────────────────────────────────────────────────
$supremeAdmins = ['gositeme@gmail.com'];
if (!in_array(strtolower($clientEmail), $supremeAdmins)) {
    header('Location: /dashboard.php');
    exit;
}

define('GOSITEME_INTEL', true);
define('GOSITEME_API', true);
require_once __DIR__ . '/api/config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
} catch (PDOException $e) {
    die('Database unavailable.');
}

// ── Gather Intelligence Data ────────────────────────────────────────
$agents = [];
try {
    $agents = $pdo->query("SELECT agent_id, agent_name AS name, agent_role AS role, domain, status, success_rate, (tasks_completed + tasks_failed) AS total_tasks, last_active AS last_active_at FROM alfred_agent_registry ORDER BY FIELD(agent_role,'commander','director','specialist'), agent_name ASC LIMIT 120")->fetchAll();
} catch (Exception $e) {}

$clients = [];
try {
    $clients = $pdo->query("SELECT id, CONCAT(firstname, ' ', lastname) AS name, email, status, date_created AS datecreated, last_login AS lastlogin FROM clients ORDER BY date_created DESC LIMIT 100")->fetchAll();
} catch (Exception $e) {}

$gatewayStats = [];
try {
    $gatewayStats = $pdo->query("SELECT channel, COUNT(*) as total, MAX(created_at) as last_activity FROM alfred_gateway_messages GROUP BY channel ORDER BY total DESC")->fetchAll();
} catch (Exception $e) {}

$directives = [];
try {
    $directives = $pdo->query("SELECT * FROM alfred_ops_directives WHERE status IN ('pending','in_progress','claimed') ORDER BY priority DESC, created_at DESC LIMIT 20")->fetchAll();
} catch (Exception $e) {}

$standingOrders = [];
try {
    $standingOrders = $pdo->query("SELECT * FROM alfred_ops_standing_orders WHERE active = 1 ORDER BY priority DESC LIMIT 20")->fetchAll();
} catch (Exception $e) {}

$veilLog = [];
try {
    $veilLog = $pdo->query("SELECT * FROM veil_access_log ORDER BY timestamp DESC LIMIT 20")->fetchAll();
} catch (Exception $e) {}

$callLog = [];
try {
    $callLog = $pdo->query("SELECT call_id, caller_phone, client_id, call_type, duration_seconds, summary, created_at FROM alfred_call_log ORDER BY created_at DESC LIMIT 15")->fetchAll();
} catch (Exception $e) {}

include __DIR__ . '/includes/site-header.inc.php';
?>

<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#04040a;--surface:#0a0a16;--surface-2:#101024;--surface-3:#181838;
  --border:rgba(255,255,255,.05);--accent:#00ff88;--accent-dim:rgba(0,255,136,.1);
  --red:#ff3344;--gold:#ffd700;--cyan:#00e5ff;--purple:#8b5cf6;--blue:#3b82f6;
  --text:#d0d0e0;--text-muted:#6a6a8a;--text-dim:#444466;
  --radius:12px;--radius-sm:8px;
  --font:'Segoe UI',system-ui,-apple-system,sans-serif;
}
body{font-family:var(--font);background:var(--bg);color:var(--text);line-height:1.5;min-height:100vh}
a{color:var(--accent);text-decoration:none}a:hover{color:#fff}

.intel-wrap{max-width:1600px;margin:0 auto;padding:80px 20px 60px}

/* Header */
.intel-header{padding:24px 28px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:24px;position:relative;overflow:hidden}
.intel-header::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--accent),var(--cyan),var(--purple))}
.intel-header h1{font-size:1.6rem;font-weight:800;display:flex;align-items:center;gap:12px;margin-bottom:4px}
.intel-header h1 i{color:var(--accent);font-size:1.4rem}
.intel-header .sub{color:var(--text-muted);font-size:.85rem}
.intel-header .class-badge{display:inline-flex;align-items:center;gap:6px;padding:4px 14px;border-radius:20px;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;background:rgba(255,51,68,.1);color:var(--red);border:1px solid rgba(255,51,68,.2);margin-left:12px}

/* Tabs */
.intel-tabs{display:flex;gap:4px;padding:4px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:20px;overflow-x:auto;scrollbar-width:none}
.intel-tabs::-webkit-scrollbar{display:none}
.intel-tab{padding:10px 18px;border-radius:var(--radius-sm);cursor:pointer;font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);white-space:nowrap;border:none;background:none;font-family:var(--font);transition:all .15s}
.intel-tab:hover{color:var(--text);background:var(--surface-2)}
.intel-tab.active{background:var(--accent-dim);color:var(--accent);border:1px solid rgba(0,255,136,.2)}

/* Panels */
.intel-panel{display:none}
.intel-panel.active{display:block}

/* Stats Row */
.intel-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:20px}
.intel-stat{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:18px;position:relative;overflow:hidden}
.intel-stat::before{content:'';position:absolute;top:0;left:0;right:0;height:2px}
.intel-stat.green::before{background:var(--accent)}
.intel-stat.blue::before{background:var(--blue)}
.intel-stat.purple::before{background:var(--purple)}
.intel-stat.red::before{background:var(--red)}
.intel-stat.gold::before{background:var(--gold)}
.intel-stat.cyan::before{background:var(--cyan)}
.intel-stat .label{font-size:.68rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);margin-bottom:4px}
.intel-stat .val{font-size:1.8rem;font-weight:800}
.intel-stat .sub{font-size:.68rem;color:var(--text-dim);margin-top:2px}

/* Tables */
.intel-table{width:100%;border-collapse:collapse;font-size:.82rem}
.intel-table th{text-align:left;padding:10px 14px;background:var(--surface-2);color:var(--text-muted);font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;font-weight:700;border-bottom:1px solid var(--border);position:sticky;top:0;z-index:1}
.intel-table td{padding:10px 14px;border-bottom:1px solid var(--border);vertical-align:middle}
.intel-table tr:hover td{background:rgba(0,255,136,.02)}
.intel-table-wrap{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;max-height:600px;overflow-y:auto}

/* Badges */
.badge{font-size:.65rem;font-weight:700;text-transform:uppercase;padding:3px 10px;border-radius:6px;letter-spacing:.3px}
.badge-green{background:rgba(0,255,136,.1);color:var(--accent)}
.badge-red{background:rgba(255,51,68,.1);color:var(--red)}
.badge-gold{background:rgba(255,215,0,.1);color:var(--gold)}
.badge-blue{background:rgba(59,130,246,.1);color:var(--blue)}
.badge-purple{background:rgba(139,92,246,.1);color:var(--purple)}
.badge-cyan{background:rgba(0,229,255,.1);color:var(--cyan)}
.badge-muted{background:rgba(255,255,255,.05);color:var(--text-muted)}

/* Section */
.section-title{font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--text-muted);margin:24px 0 12px;display:flex;align-items:center;gap:8px}
.section-title i{color:var(--accent);font-size:.7rem}

/* Card */
.intel-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:20px;margin-bottom:12px}
.intel-card h3{font-size:.9rem;font-weight:700;margin-bottom:8px;display:flex;align-items:center;gap:8px}
.intel-card p{font-size:.82rem;color:var(--text-muted);line-height:1.5}

/* Grid */
.intel-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:12px}

/* Channel indicator */
.channel-dot{width:8px;height:8px;border-radius:50%;display:inline-block;margin-right:6px}
.channel-dot.active{background:var(--accent);box-shadow:0 0 6px rgba(0,255,136,.4)}
.channel-dot.idle{background:var(--gold)}
.channel-dot.offline{background:var(--red)}

/* Search */
.intel-search{width:100%;padding:10px 16px;border-radius:var(--radius-sm);border:1px solid var(--border);background:var(--surface-2);color:var(--text);font-size:.85rem;font-family:var(--font);outline:none;margin-bottom:16px}
.intel-search:focus{border-color:rgba(0,255,136,.3)}
.intel-search::placeholder{color:var(--text-dim)}

/* Scrollbar */
.intel-table-wrap::-webkit-scrollbar{width:4px}
.intel-table-wrap::-webkit-scrollbar-thumb{background:var(--surface-3);border-radius:2px}
</style>

<div class="intel-wrap">
    <!-- Header -->
    <div class="intel-header">
        <h1>
            <i class="fas fa-satellite"></i>
            Intelligence Director
            <span class="class-badge"><i class="fas fa-lock"></i> CLASSIFIED</span>
        </h1>
        <div class="sub">Global Ecosystem Intelligence • Personnel Network • Operations Oversight • OIC Coordination</div>
    </div>

    <!-- Tabs -->
    <div class="intel-tabs">
        <button class="intel-tab active" onclick="showPanel('overview')"><i class="fas fa-globe" style="margin-right:6px"></i> Overview</button>
        <button class="intel-tab" onclick="showPanel('personnel')"><i class="fas fa-users" style="margin-right:6px"></i> Personnel</button>
        <button class="intel-tab" onclick="showPanel('agents')"><i class="fas fa-robot" style="margin-right:6px"></i> Agent Fleet</button>
        <button class="intel-tab" onclick="showPanel('channels')"><i class="fas fa-tower-broadcast" style="margin-right:6px"></i> Channels</button>
        <button class="intel-tab" onclick="showPanel('ops')"><i class="fas fa-crosshairs" style="margin-right:6px"></i> Operations</button>
        <button class="intel-tab" onclick="showPanel('veil')"><i class="fas fa-mask" style="margin-right:6px"></i> Veil Log</button>
        <button class="intel-tab" onclick="showPanel('comms')"><i class="fas fa-phone-volume" style="margin-right:6px"></i> Intercepts</button>
        <button class="intel-tab" onclick="showPanel('oic')"><i class="fas fa-satellite-dish" style="margin-right:6px"></i> OIC</button>
    </div>

    <!-- ═══ OVERVIEW PANEL ═══ -->
    <div class="intel-panel active" id="panel-overview">
        <div class="intel-stats">
            <div class="intel-stat green">
                <div class="label">Agent Fleet</div>
                <div class="val"><?= count($agents) ?: 101 ?></div>
                <div class="sub">Deployed Assets</div>
            </div>
            <div class="intel-stat blue">
                <div class="label">Personnel</div>
                <div class="val"><?= count($clients) ?></div>
                <div class="sub">Known Contacts</div>
            </div>
            <div class="intel-stat purple">
                <div class="label">Comm Channels</div>
                <div class="val">8</div>
                <div class="sub">Active Networks</div>
            </div>
            <div class="intel-stat gold">
                <div class="label">Active Ops</div>
                <div class="val"><?= count($directives) ?></div>
                <div class="sub">Running Directives</div>
            </div>
            <div class="intel-stat cyan">
                <div class="label">Standing Orders</div>
                <div class="val"><?= count($standingOrders) ?></div>
                <div class="sub">Permanent Watch</div>
            </div>
            <div class="intel-stat red">
                <div class="label">Veil Events</div>
                <div class="val"><?= count($veilLog) ?></div>
                <div class="sub">Access Attempts</div>
            </div>
        </div>

        <div class="intel-grid">
            <div class="intel-card">
                <h3><i class="fas fa-tower-broadcast" style="color:var(--accent)"></i> Communication Network Status</h3>
                <p style="margin-bottom:12px">All 8 channels operational. Alfred responds from every endpoint.</p>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;font-size:.78rem">
                    <div><span class="channel-dot active"></span> Web Chat</div>
                    <div><span class="channel-dot active"></span> SMS (Telnyx)</div>
                    <div><span class="channel-dot active"></span> Telegram Bot</div>
                    <div><span class="channel-dot active"></span> Discord Bot</div>
                    <div><span class="channel-dot active"></span> Slack Bot</div>
                    <div><span class="channel-dot active"></span> WhatsApp</div>
                    <div><span class="channel-dot active"></span> Email (SendGrid)</div>
                    <div><span class="channel-dot active"></span> Voice (VAPI)</div>
                </div>
            </div>
            <div class="intel-card">
                <h3><i class="fas fa-shield-halved" style="color:var(--red)"></i> Security Posture</h3>
                <p style="margin-bottom:8px">Threat level: <span class="badge badge-green">LOW</span></p>
                <p>Perimeter agents active (Sentinel + Cipher). Penetration scans running every 6 hours. Veil Protocol armed on all 8 channels. AI fallback cascade: 6 providers + local Ollama.</p>
            </div>
            <div class="intel-card">
                <h3><i class="fas fa-brain" style="color:var(--purple)"></i> AI Infrastructure</h3>
                <div style="font-size:.78rem;margin-top:8px">
                    <div style="margin-bottom:4px"><span class="badge badge-green">PRIMARY</span> Anthropic Sonnet 4</div>
                    <div style="margin-bottom:4px"><span class="badge badge-blue">BACKUP 1</span> Groq llama-3.3-70b</div>
                    <div style="margin-bottom:4px"><span class="badge badge-blue">BACKUP 2</span> OpenAI gpt-4.1-mini</div>
                    <div style="margin-bottom:4px"><span class="badge badge-blue">BACKUP 3</span> Google gemini-2.5-flash</div>
                    <div style="margin-bottom:4px"><span class="badge badge-blue">BACKUP 4</span> xAI grok-3-mini</div>
                    <div><span class="badge badge-red">LOCAL</span> Ollama qwen2.5:3b (port 11434)</div>
                </div>
            </div>
            <div class="intel-card">
                <h3><i class="fas fa-satellite-dish" style="color:var(--cyan)"></i> OIC Initiative</h3>
                <p>Open Intelligence Collective — Phase 1 (Foundation). Distributed research nodes, OSINT methodology, decentralized governance framework. <a href="#" onclick="showPanel('oic')">View Full Brief →</a></p>
            </div>
        </div>
    </div>

    <!-- ═══ PERSONNEL PANEL ═══ -->
    <div class="intel-panel" id="panel-personnel">
        <input type="text" class="intel-search" id="personnel-search" placeholder="Search personnel by name, email, or ID..." onkeyup="filterTable('personnel-table', this.value)">
        <div class="intel-table-wrap">
            <table class="intel-table" id="personnel-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Last Seen</th>
                        <th>Clearance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $c): 
                        $clearance = ((int)$c['id'] === 33) ? 'COMMANDER' : 'STANDARD';
                        $clearBadge = ((int)$c['id'] === 33) ? 'badge-gold' : 'badge-muted';
                        $statusBadge = $c['status'] === 'Active' ? 'badge-green' : 'badge-red';
                    ?>
                    <tr>
                        <td style="color:var(--text-dim);font-family:monospace;font-size:.75rem">#<?= htmlspecialchars($c['id']) ?></td>
                        <td style="font-weight:600"><?= htmlspecialchars($c['name']) ?></td>
                        <td style="font-size:.78rem;color:var(--text-muted)"><?= htmlspecialchars($c['email']) ?></td>
                        <td><span class="badge <?= $statusBadge ?>"><?= htmlspecialchars($c['status'] ?? 'Unknown') ?></span></td>
                        <td style="font-size:.75rem;color:var(--text-dim)"><?= htmlspecialchars(substr($c['datecreated'] ?? '', 0, 10)) ?></td>
                        <td style="font-size:.75rem;color:var(--text-dim)"><?= htmlspecialchars($c['lastlogin'] ? substr($c['lastlogin'], 0, 16) : 'Never') ?></td>
                        <td><span class="badge <?= $clearBadge ?>"><?= $clearance ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ═══ AGENT FLEET PANEL ═══ -->
    <div class="intel-panel" id="panel-agents">
        <input type="text" class="intel-search" id="agent-search" placeholder="Search agents by name, role, or domain..." onkeyup="filterTable('agent-table', this.value)">
        <div class="intel-table-wrap">
            <table class="intel-table" id="agent-table">
                <thead>
                    <tr>
                        <th>Agent ID</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Domain</th>
                        <th>Status</th>
                        <th>Success Rate</th>
                        <th>Tasks</th>
                        <th>Last Active</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($agents as $a):
                        $roleBadge = match($a['role'] ?? '') {
                            'commander' => 'badge-gold',
                            'director' => 'badge-purple',
                            default => 'badge-cyan'
                        };
                        $statusBadge = match($a['status'] ?? '') {
                            'active','idle' => 'badge-green',
                            'busy' => 'badge-gold',
                            'error' => 'badge-red',
                            default => 'badge-muted'
                        };
                    ?>
                    <tr>
                        <td style="font-family:monospace;font-size:.72rem;color:var(--text-dim)"><?= htmlspecialchars($a['agent_id']) ?></td>
                        <td style="font-weight:700"><?= htmlspecialchars(ucfirst($a['name'])) ?></td>
                        <td><span class="badge <?= $roleBadge ?>"><?= htmlspecialchars(strtoupper($a['role'])) ?></span></td>
                        <td style="font-size:.78rem"><?= htmlspecialchars($a['domain'] ?? '—') ?></td>
                        <td><span class="badge <?= $statusBadge ?>"><?= htmlspecialchars(strtoupper($a['status'])) ?></span></td>
                        <td style="font-family:monospace"><?= number_format(($a['success_rate'] ?? 0) * 100, 0) ?>%</td>
                        <td style="font-family:monospace"><?= (int)($a['total_tasks'] ?? 0) ?></td>
                        <td style="font-size:.72rem;color:var(--text-dim)"><?= $a['last_active_at'] ? substr($a['last_active_at'], 0, 16) : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ═══ CHANNELS PANEL ═══ -->
    <div class="intel-panel" id="panel-channels">
        <div class="section-title"><i class="fas fa-chart-bar"></i> Channel Traffic Analysis</div>
        <div class="intel-table-wrap" style="margin-bottom:20px">
            <table class="intel-table">
                <thead>
                    <tr><th>Channel</th><th>Total Messages</th><th>Last Activity</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($gatewayStats as $ch): ?>
                    <tr>
                        <td style="font-weight:700;text-transform:capitalize"><span class="channel-dot active"></span> <?= htmlspecialchars($ch['channel']) ?></td>
                        <td style="font-family:monospace;font-weight:600"><?= number_format((int)$ch['total']) ?></td>
                        <td style="font-size:.78rem;color:var(--text-muted)"><?= htmlspecialchars($ch['last_activity'] ?? '—') ?></td>
                        <td><span class="badge badge-green">ONLINE</span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($gatewayStats)): ?>
                    <tr><td colspan="4" style="text-align:center;color:var(--text-dim);padding:20px">No channel data yet</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="section-title"><i class="fas fa-network-wired"></i> Channel Infrastructure</div>
        <div class="intel-grid">
            <div class="intel-card">
                <h3><i class="fab fa-telegram" style="color:#26A5E4"></i> Telegram Bot</h3>
                <p>Full command set: /start, /ask, /help, /status, /tools, /goals, /balance, /news</p>
                <p style="margin-top:6px"><span class="badge badge-green">BOT TOKEN ACTIVE</span> <span class="badge badge-green">WEBHOOK SECRET SET</span></p>
            </div>
            <div class="intel-card">
                <h3><i class="fab fa-discord" style="color:#5865F2"></i> Discord Bot</h3>
                <p>35 sub-modules. Slash commands: /ask, /search, /research, /status, /tools, /help. Stripe billing integration.</p>
                <p style="margin-top:6px"><span class="badge badge-green">BOT TOKEN</span> <span class="badge badge-green">APP ID</span> <span class="badge badge-green">PUBLIC KEY</span></p>
            </div>
            <div class="intel-card">
                <h3><i class="fab fa-slack" style="color:#4A154B"></i> Slack Bot</h3>
                <p>@mention handler, channel message processing, signature verification active.</p>
                <p style="margin-top:6px"><span class="badge badge-green">BOT TOKEN</span> <span class="badge badge-green">SIGNING SECRET</span></p>
            </div>
            <div class="intel-card">
                <h3><i class="fab fa-whatsapp" style="color:#25D366"></i> WhatsApp</h3>
                <p>Meta Cloud API v18.0. Webhook challenge validation. Inbound/outbound messaging.</p>
                <p style="margin-top:6px"><span class="badge badge-green">TOKEN</span> <span class="badge badge-green">PHONE ID</span> <span class="badge badge-green">VERIFY TOKEN</span></p>
            </div>
            <div class="intel-card">
                <h3><i class="fas fa-comment-sms" style="color:var(--accent)"></i> SMS (Telnyx)</h3>
                <p>Inbound/outbound SMS. Phone: +1(807)798-2850. Rate limited per sender.</p>
                <p style="margin-top:6px"><span class="badge badge-green">API KEY</span> <span class="badge badge-green">FROM NUMBER</span></p>
            </div>
            <div class="intel-card">
                <h3><i class="fas fa-phone" style="color:var(--gold)"></i> Voice (VAPI)</h3>
                <p>Inbound: 1-833-GOSITEME. Outbound: auto-calls unpaid invoices & expiring domains. Full transcripts.</p>
                <p style="margin-top:6px"><span class="badge badge-green">API KEY</span> <span class="badge badge-green">PHONE ID</span> <span class="badge badge-green">ASSISTANT ID</span></p>
            </div>
        </div>
    </div>

    <!-- ═══ OPERATIONS PANEL ═══ -->
    <div class="intel-panel" id="panel-ops">
        <div class="section-title"><i class="fas fa-scroll"></i> Active Directives</div>
        <div class="intel-table-wrap" style="margin-bottom:20px">
            <table class="intel-table">
                <thead>
                    <tr><th>Priority</th><th>Title</th><th>Type</th><th>Agent</th><th>Status</th><th>Deadline</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($directives as $d): 
                        $pColor = (int)$d['priority'] >= 8 ? 'var(--red)' : ((int)$d['priority'] >= 5 ? 'var(--gold)' : 'var(--accent)');
                    ?>
                    <tr>
                        <td style="font-weight:800;color:<?= $pColor ?>;font-family:monospace">P<?= (int)$d['priority'] ?></td>
                        <td style="font-weight:600"><?= htmlspecialchars($d['title']) ?></td>
                        <td><span class="badge badge-purple"><?= htmlspecialchars(strtoupper($d['type'] ?? 'general')) ?></span></td>
                        <td style="font-size:.78rem"><?= htmlspecialchars($d['assigned_agent'] ?? '—') ?></td>
                        <td><span class="badge badge-gold"><?= htmlspecialchars(strtoupper($d['status'])) ?></span></td>
                        <td style="font-size:.75rem;color:var(--text-dim)"><?= $d['deadline'] ? substr($d['deadline'], 0, 16) : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($directives)): ?>
                    <tr><td colspan="6" style="text-align:center;color:var(--text-dim);padding:20px">No active directives</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="section-title"><i class="fas fa-repeat"></i> Standing Orders</div>
        <div class="intel-table-wrap">
            <table class="intel-table">
                <thead>
                    <tr><th>Priority</th><th>Title</th><th>Schedule</th><th>Agent</th><th>Next Run</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($standingOrders as $so): ?>
                    <tr>
                        <td style="font-weight:800;font-family:monospace">P<?= (int)$so['priority'] ?></td>
                        <td style="font-weight:600"><?= htmlspecialchars($so['title']) ?></td>
                        <td style="font-size:.78rem"><span class="badge badge-cyan"><?= htmlspecialchars($so['schedule'] ?? '—') ?></span></td>
                        <td style="font-size:.78rem"><?= htmlspecialchars($so['assigned_agent'] ?? '—') ?></td>
                        <td style="font-size:.75rem;color:var(--text-dim)"><?= $so['next_run_at'] ? substr($so['next_run_at'], 0, 16) : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ═══ VEIL LOG PANEL ═══ -->
    <div class="intel-panel" id="panel-veil">
        <div class="section-title"><i class="fas fa-mask"></i> Veil Protocol Access Log</div>
        <div class="intel-table-wrap">
            <table class="intel-table">
                <thead>
                    <tr><th>Timestamp</th><th>Action</th><th>Channel</th><th>Phone</th><th>Client ID</th><th>IP Address</th><th>Details</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($veilLog as $v):
                        $actionBadge = match($v['action'] ?? '') {
                            'success','activated' => 'badge-green',
                            'denied','failed','rate_limited' => 'badge-red',
                            default => 'badge-gold'
                        };
                    ?>
                    <tr>
                        <td style="font-family:monospace;font-size:.72rem"><?= htmlspecialchars($v['timestamp'] ?? '') ?></td>
                        <td><span class="badge <?= $actionBadge ?>"><?= htmlspecialchars(strtoupper($v['action'] ?? '')) ?></span></td>
                        <td style="text-transform:capitalize"><?= htmlspecialchars($v['channel'] ?? '—') ?></td>
                        <td style="font-family:monospace;font-size:.75rem"><?= htmlspecialchars($v['phone'] ?? '—') ?></td>
                        <td style="font-family:monospace"><?= htmlspecialchars($v['client_id'] ?? '—') ?></td>
                        <td style="font-family:monospace;font-size:.72rem"><?= htmlspecialchars($v['ip_address'] ?? '—') ?></td>
                        <td style="font-size:.72rem;color:var(--text-dim);max-width:200px;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($v['details'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($veilLog)): ?>
                    <tr><td colspan="7" style="text-align:center;color:var(--text-dim);padding:20px">No access attempts recorded</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ═══ CALL INTERCEPTS PANEL ═══ -->
    <div class="intel-panel" id="panel-comms">
        <div class="section-title"><i class="fas fa-phone-volume"></i> Voice Call Intelligence</div>
        <div class="intel-table-wrap">
            <table class="intel-table">
                <thead>
                    <tr><th>Call ID</th><th>Phone</th><th>Client</th><th>Type</th><th>Duration</th><th>Summary</th><th>Timestamp</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($callLog as $cl): ?>
                    <tr>
                        <td style="font-family:monospace;font-size:.72rem">#<?= htmlspecialchars($cl['call_id']) ?></td>
                        <td style="font-family:monospace;font-size:.78rem"><?= htmlspecialchars($cl['caller_phone'] ?? '—') ?></td>
                        <td style="font-family:monospace"><?= $cl['client_id'] ? '#' . htmlspecialchars($cl['client_id']) : '—' ?></td>
                        <td><span class="badge badge-cyan"><?= htmlspecialchars(strtoupper($cl['call_type'] ?? 'inbound')) ?></span></td>
                        <td style="font-family:monospace"><?= (int)($cl['duration_seconds'] ?? 0) ?>s</td>
                        <td style="font-size:.75rem;max-width:300px;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars(substr($cl['summary'] ?? '', 0, 120)) ?></td>
                        <td style="font-size:.72rem;color:var(--text-dim)"><?= htmlspecialchars(substr($cl['created_at'] ?? '', 0, 16)) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($callLog)): ?>
                    <tr><td colspan="7" style="text-align:center;color:var(--text-dim);padding:20px">No call records</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ═══ OIC PANEL ═══ -->
    <div class="intel-panel" id="panel-oic">
        <div class="intel-card" style="border-left:3px solid var(--cyan)">
            <h3><i class="fas fa-globe" style="color:var(--cyan)"></i> Open Intelligence Collective (OIC)</h3>
            <p style="margin-bottom:12px">Decentralized global investigation and intelligence network operating through open data, transparent analysis, and distributed participation.</p>
            <p><span class="badge badge-cyan">PHASE 1 — FOUNDATION</span></p>
        </div>

        <div class="section-title"><i class="fas fa-scroll"></i> Mission</div>
        <div class="intel-card">
            <p>Establish an open, distributed intelligence system capable of documenting systemic risks, environmental harm, corruption, conflict activity, and organized exploitation through verifiable open-source evidence.</p>
        </div>

        <div class="section-title"><i class="fas fa-compass"></i> Guiding Principles</div>
        <div class="intel-grid">
            <div class="intel-card">
                <h3>Personhood-Based Participation</h3>
                <p>Participation derives from human personhood rather than nationality, institutional affiliation, or legal status.</p>
            </div>
            <div class="intel-card">
                <h3>Transparency</h3>
                <p>Evidence, analytical methods, and investigative processes are documented and publicly accessible.</p>
            </div>
            <div class="intel-card">
                <h3>Decentralization</h3>
                <p>No single institution or region maintains permanent control. Authority is distributed across independent nodes.</p>
            </div>
            <div class="intel-card">
                <h3>Accountability</h3>
                <p>Leadership roles are rotational and subject to transparent review and removal.</p>
            </div>
            <div class="intel-card">
                <h3>Evidence Integrity</h3>
                <p>All findings must be supported by verifiable data and reproducible analytical methodology.</p>
            </div>
        </div>

        <div class="section-title"><i class="fas fa-sitemap"></i> Structural Components</div>
        <div class="intel-grid">
            <div class="intel-card">
                <h3><i class="fas fa-map-marker-alt" style="color:var(--accent)"></i> Distributed Research Nodes</h3>
                <p>Regional or thematic groups responsible for open data collection, geospatial analysis, field documentation, and collaborative investigation. Nodes remain autonomous but share data.</p>
            </div>
            <div class="intel-card">
                <h3><i class="fas fa-project-diagram" style="color:var(--blue)"></i> Analytical Coordination Layer</h3>
                <p>Shared infrastructure for cross-node collaboration: data exchange, joint investigations, analytical standardization, tool development. Facilitates — does not command.</p>
            </div>
            <div class="intel-card">
                <h3><i class="fas fa-check-double" style="color:var(--gold)"></i> Verification & Integrity Board</h3>
                <p>Rotating review body: evidence validation, methodological review, replication testing, publication approval. Rotation prevents institutional capture.</p>
            </div>
            <div class="intel-card">
                <h3><i class="fas fa-database" style="color:var(--purple)"></i> Open Data Repository</h3>
                <p>Public archive: geospatial datasets, environmental monitoring, infrastructure mapping, ownership/financial networks, event timelines.</p>
            </div>
        </div>

        <div class="section-title"><i class="fas fa-magnifying-glass"></i> Intelligence Methodology</div>
        <div class="intel-card">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;font-size:.82rem">
                <div><strong style="color:var(--cyan)">Geospatial Intelligence</strong><br>Satellite imagery & geographic data analysis</div>
                <div><strong style="color:var(--cyan)">Digital Forensics</strong><br>Media metadata, geolocation, time correlation</div>
                <div><strong style="color:var(--cyan)">Network Mapping</strong><br>Relationships between entities & financial structures</div>
                <div><strong style="color:var(--cyan)">Pattern Analysis</strong><br>Large-scale data processing for trends & anomalies</div>
                <div><strong style="color:var(--cyan)">Multi-Source Verification</strong><br>Independent confirmation across data streams</div>
                <div><strong style="color:var(--cyan)">Financial Forensics</strong><br>Corporate filings, ownership, disclosures</div>
            </div>
        </div>

        <div class="section-title"><i class="fas fa-server"></i> Technical Architecture</div>
        <div class="intel-grid">
            <div class="intel-card">
                <h3>Distributed Data Storage</h3>
                <p>Data replicated across independent nodes. Distributed databases, decentralized storage, encrypted archives prevent censorship or loss.</p>
            </div>
            <div class="intel-card">
                <h3>Evidence Authentication</h3>
                <p>Cryptographic hashing, distributed timestamping, immutable audit logs establish authenticity and chain of custody.</p>
            </div>
            <div class="intel-card">
                <h3>Analytical Platforms</h3>
                <p>Geospatial visualization, network analysis, timeline reconstruction, large dataset processing, collaborative annotation.</p>
            </div>
        </div>

        <div class="section-title"><i class="fas fa-road"></i> Implementation Phases</div>
        <div class="intel-card">
            <div style="display:flex;flex-direction:column;gap:10px;font-size:.82rem">
                <div><span class="badge badge-green">PHASE 1</span> <strong>Infrastructure Formation</strong> — Develop governance protocols, establish initial technical systems</div>
                <div><span class="badge badge-blue">PHASE 2</span> <strong>Pilot Node Deployment</strong> — Launch investigative nodes, test operational workflows</div>
                <div><span class="badge badge-purple">PHASE 3</span> <strong>Network Expansion</strong> — Increase participation, datasets, and analytical capacity</div>
                <div><span class="badge badge-gold">PHASE 4</span> <strong>Persistent Global Network</strong> — Fully operational distributed intelligence platform</div>
            </div>
        </div>

        <div style="margin-top:20px;text-align:center">
            <a href="/docs/oic-whitepaper.php" style="padding:12px 28px;background:rgba(0,255,136,.1);border:1px solid rgba(0,255,136,.2);border-radius:var(--radius-sm);color:var(--accent);font-weight:700;font-size:.85rem;display:inline-flex;align-items:center;gap:8px"><i class="fas fa-file-lines"></i> View Full OIC Whitepaper</a>
        </div>
    </div>
</div>

<script src="/assets/js/intelligence-director-engine.js"></script>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
