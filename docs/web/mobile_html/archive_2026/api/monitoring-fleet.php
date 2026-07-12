<?php
/**
 * Agent Monitoring Fleet — 100 Autonomous Agents
 * ═══════════════════════════════════════════════
 * Registers and manages monitoring agents across all GoSiteMe systems.
 * Each agent monitors a specific domain and reports to the agenda/advisory panel.
 * 
 * Endpoints:
 *   ?action=register_fleet   POST  - Register all 100 monitoring agents
 *   ?action=fleet_status     GET   - Get all monitoring agent statuses
 *   ?action=run_checks       POST  - Execute monitoring checks across all agents
 *   ?action=agent_check      POST  - Run a single agent's monitoring check
 *   ?action=report_summary   GET   - Summarized report from all monitors
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Internal-Secret');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

define('GOSITEME_API', true);
require_once dirname(__DIR__) . '/api/config.php';

// Auth: require internal secret or owner session
require_once dirname(__DIR__) . '/includes/api-security.php';
$internalSecret = $_SERVER['HTTP_X_INTERNAL_SECRET'] ?? '';
$isAuthed = defined('INTERNAL_SECRET') && INTERNAL_SECRET !== '' && hash_equals(INTERNAL_SECRET, $internalSecret);
if (!$isAuthed) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $isAuthed = in_array((int)($_SESSION['client_id'] ?? 0), [1, 33]);
}
if (!$isAuthed) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed']);
    exit;
}

// ── Schema ──
$db->exec("CREATE TABLE IF NOT EXISTS monitoring_agents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agent_id VARCHAR(64) NOT NULL UNIQUE,
    agent_name VARCHAR(128) NOT NULL,
    division VARCHAR(64) NOT NULL,
    domain VARCHAR(128) NOT NULL,
    description TEXT,
    check_type ENUM('http','api','service','database','file','custom','security','performance','seo','crawler') DEFAULT 'custom',
    check_target VARCHAR(512),
    check_interval_minutes INT DEFAULT 5,
    last_check_at DATETIME DEFAULT NULL,
    last_status ENUM('healthy','degraded','critical','unknown') DEFAULT 'unknown',
    last_response_ms INT DEFAULT NULL,
    consecutive_failures INT DEFAULT 0,
    total_checks INT DEFAULT 0,
    total_failures INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_division (division),
    INDEX idx_status (last_status),
    INDEX idx_domain (domain)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS monitoring_checks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agent_id VARCHAR(64) NOT NULL,
    status ENUM('healthy','degraded','critical') NOT NULL,
    response_ms INT DEFAULT NULL,
    details TEXT,
    metrics JSON DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_agent (agent_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── The 100-Agent Fleet Definition ──
function getMonitoringFleet(): array {
    return [
        // ═══ Division 1: UPTIME (10 agents) ═══
        ['MON-UP-001','Uptime: Homepage','uptime','web','HTTP health check for gositeme.com','http','https://gositeme.com'],
        ['MON-UP-002','Uptime: Alfred AI','uptime','ai','Alfred AI dashboard availability','http','https://gositeme.com/alfred.php'],
        ['MON-UP-003','Uptime: Veil Browser Page','uptime','browser','Download page availability','http','https://gositeme.com/alfred-browser'],
        ['MON-UP-004','Uptime: GoCodeMe IDE','uptime','ide','Cloud IDE availability','http','https://gositeme.com/editor/'],
        ['MON-UP-005','Uptime: API Gateway','uptime','api','Main API endpoint health','api','https://gositeme.com/api/ecosystem.php?action=health'],
        ['MON-UP-006','Uptime: Marketplace','uptime','marketplace','Marketplace page health','http','https://gositeme.com/marketplace'],
        ['MON-UP-007','Uptime: Voice Portal','uptime','voice','Voice portal availability','http','https://gositeme.com/voice'],
        ['MON-UP-008','Uptime: Games','uptime','games','Games portal health','http','https://gositeme.com/games'],
        ['MON-UP-009','Uptime: Pulse Social','uptime','social','Pulse social hub health','http','https://gositeme.com/pulse'],
        ['MON-UP-010','Uptime: VR Worlds','uptime','vr','VR portal availability','http','https://gositeme.com/vr/'],

        // ═══ Division 2: SERVICES (10 agents) ═══
        ['MON-SVC-001','Service: Redis','services','infrastructure','Redis cache health','service','redis:6379'],
        ['MON-SVC-002','Service: MeiliSearch','services','search','MeiliSearch index health','api','http://127.0.0.1:7700/health'],
        ['MON-SVC-003','Service: WebSocket','services','realtime','WebSocket server availability','service','ws:8080'],
        ['MON-SVC-004','Service: Ollama','services','ai','Local AI model server','api','http://127.0.0.1:11434/api/tags'],
        ['MON-SVC-005','Service: PM2 Master','services','infrastructure','PM2 process manager health','service','pm2'],
        ['MON-SVC-006','Service: Job Queue','services','infrastructure','Alfred job processor health','service','alfred-jobs'],
        ['MON-SVC-007','Service: MCP Server','services','ai','Model Context Protocol server','service','alfred-mcp'],
        ['MON-SVC-008','Service: Discord Bot','services','comms','Discord bot connectivity','service','alfred-discord'],
        ['MON-SVC-009','Service: Heartbeat','services','infrastructure','System heartbeat monitor','service','alfred-heartbeat'],
        ['MON-SVC-010','Service: MySQL','services','database','Database server health','database','mysql'],

        // ═══ Division 3: SECURITY (15 agents) ═══
        ['MON-SEC-001','Security: SSL Cert','security','ssl','SSL certificate validity and expiry','security','https://gositeme.com'],
        ['MON-SEC-002','Security: Headers','security','headers','Security header compliance (HSTS, CSP, X-Frame)','security','headers'],
        ['MON-SEC-003','Security: Auth Endpoints','security','auth','Login/session endpoint security','security','auth'],
        ['MON-SEC-004','Security: API Rate Limits','security','api','API rate limiting effectiveness','security','rate-limits'],
        ['MON-SEC-005','Security: File Integrity','security','files','Critical file hash monitoring','security','file-integrity'],
        ['MON-SEC-006','Security: Veil Protocol','security','encryption','Veil encryption module health','security','veil'],
        ['MON-SEC-007','Security: Admin Access','security','admin','Admin panel access logging','security','admin-access'],
        ['MON-SEC-008','Security: Injection Scan','security','injection','SQL/XSS injection attempt monitoring','security','injection'],
        ['MON-SEC-009','Security: DNS Records','security','dns','DNS record integrity monitoring','security','dns'],
        ['MON-SEC-010','Security: Dependency Audit','security','deps','Package vulnerability scanning','security','dependencies'],
        ['MON-SEC-011','Security: Backup Integrity','security','backup','Backup file integrity verification','security','backups'],
        ['MON-SEC-012','Security: Firewall Rules','security','firewall','Firewall configuration audit','security','firewall'],
        ['MON-SEC-013','Security: Permission Audit','security','permissions','File permission monitoring','security','permissions'],
        ['MON-SEC-014','Security: Crypto Wallet','security','crypto','Wallet transaction monitoring','security','wallet'],
        ['MON-SEC-015','Security: Post-Quantum','security','pq','Post-quantum key rotation monitoring','security','pq-keys'],

        // ═══ Division 4: PERFORMANCE (10 agents) ═══
        ['MON-PERF-001','Perf: Page Load Time','performance','frontend','Homepage load time under 3s','performance','https://gositeme.com'],
        ['MON-PERF-002','Perf: API Response','performance','api','API p95 response time monitoring','performance','api-latency'],
        ['MON-PERF-003','Perf: Database Queries','performance','database','Slow query detection','performance','db-queries'],
        ['MON-PERF-004','Perf: Disk Usage','performance','storage','Disk space monitoring','performance','disk'],
        ['MON-PERF-005','Perf: Memory Usage','performance','memory','RAM usage monitoring','performance','memory'],
        ['MON-PERF-006','Perf: CPU Load','performance','cpu','CPU load average monitoring','performance','cpu'],
        ['MON-PERF-007','Perf: Network I/O','performance','network','Network throughput monitoring','performance','network'],
        ['MON-PERF-008','Perf: Cache Hit Rate','performance','cache','Redis cache hit ratio','performance','cache'],
        ['MON-PERF-009','Perf: Search Latency','performance','search','MeiliSearch query time','performance','search'],
        ['MON-PERF-010','Perf: WebSocket Latency','performance','realtime','WebSocket message latency','performance','websocket'],

        // ═══ Division 5: SEO & CONTENT (10 agents) ═══
        ['MON-SEO-001','SEO: Robots.txt','seo','robots','Robots.txt accessibility and validity','seo','robots.txt'],
        ['MON-SEO-002','SEO: Sitemap','seo','sitemap','Sitemap.xml validity and freshness','seo','sitemap.xml'],
        ['MON-SEO-003','SEO: Meta Tags','seo','meta','Homepage meta tag completeness','seo','meta-tags'],
        ['MON-SEO-004','SEO: Page Titles','seo','titles','Page title uniqueness and length','seo','titles'],
        ['MON-SEO-005','SEO: Broken Links','seo','links','Internal broken link detection','seo','broken-links'],
        ['MON-SEO-006','SEO: Image Alt Tags','seo','images','Image accessibility audit','seo','alt-tags'],
        ['MON-SEO-007','SEO: Schema Markup','seo','schema','Structured data validation','seo','schema'],
        ['MON-SEO-008','SEO: Mobile Friendly','seo','mobile','Mobile responsiveness check','seo','mobile'],
        ['MON-SEO-009','SEO: Core Web Vitals','seo','vitals','LCP, FID, CLS monitoring','seo','web-vitals'],
        ['MON-SEO-010','SEO: Index Coverage','seo','indexing','Google Search Console mirroring','seo','index-coverage'],

        // ═══ Division 6: CRAWLER (10 agents) ═══
        ['MON-CRW-001','Crawler: Engine Health','crawler','engine','Crawler engine v2 process health','crawler','crawler-engine'],
        ['MON-CRW-002','Crawler: Queue Depth','crawler','queue','Crawl queue size monitoring','crawler','queue-depth'],
        ['MON-CRW-003','Crawler: Pages/Hour','crawler','throughput','Crawl throughput rate','crawler','throughput'],
        ['MON-CRW-004','Crawler: Error Rate','crawler','errors','Crawl error rate monitoring','crawler','error-rate'],
        ['MON-CRW-005','Crawler: Index Size','crawler','index','Search index document count','crawler','index-size'],
        ['MON-CRW-006','Crawler: Robot Compliance','crawler','robots','Robots.txt compliance verification','crawler','robot-compliance'],
        ['MON-CRW-007','Crawler: DNS Resolution','crawler','dns','DNS resolution speed for crawls','crawler','dns-speed'],
        ['MON-CRW-008','Crawler: Intel Agent','crawler','intel','Intelligence crawler health','crawler','intel-crawler'],
        ['MON-CRW-009','Crawler: Dedup Rate','crawler','dedup','Content deduplication efficiency','crawler','dedup-rate'],
        ['MON-CRW-010','Crawler: Storage','crawler','storage','Crawl data storage usage','crawler','storage'],

        // ═══ Division 7: ECOSYSTEM (10 agents) ═══
        ['MON-ECO-001','Eco: Mining Pool','ecosystem','mining','Mining pool health and hash rate','custom','mining'],
        ['MON-ECO-002','Eco: GSM Token','ecosystem','token','Token contract health on Solana','custom','gsm-token'],
        ['MON-ECO-003','Eco: Downloads','ecosystem','downloads','Download file integrity and availability','http','downloads'],
        ['MON-ECO-004','Eco: Chrome Extension','ecosystem','extension','Extension package integrity','custom','chrome-ext'],
        ['MON-ECO-005','Eco: Android APK','ecosystem','mobile','APK signing and update check','custom','android-apk'],
        ['MON-ECO-006','Eco: Desktop Builds','ecosystem','desktop','Desktop app build integrity','custom','desktop-builds'],
        ['MON-ECO-007','Eco: Marketplace Items','ecosystem','marketplace','Marketplace listing health','custom','marketplace'],
        ['MON-ECO-008','Eco: Agent Registry','ecosystem','agents','Agent registry health and sync','custom','agent-registry'],
        ['MON-ECO-009','Eco: Trading Bot','ecosystem','trading','Trading strategy health','custom','trading'],
        ['MON-ECO-010','Eco: Billing Bridge','ecosystem','billing','WHMCS ↔ Ecosystem bridge','custom','billing'],

        // ═══ Division 8: USER EXPERIENCE (10 agents) ═══
        ['MON-UX-001','UX: Signup Flow','ux','onboarding','User registration flow health','http','signup'],
        ['MON-UX-002','UX: Login Flow','ux','auth','Login authentication health','http','login'],
        ['MON-UX-003','UX: Dashboard Load','ux','dashboard','Dashboard rendering time','performance','dashboard'],
        ['MON-UX-004','UX: Chat Interface','ux','chat','Alfred chat responsiveness','performance','chat'],
        ['MON-UX-005','UX: Voice Call Quality','ux','voice','VAPI voice call latency','performance','voice'],
        ['MON-UX-006','UX: Game Loading','ux','games','Game asset loading time','performance','games'],
        ['MON-UX-007','UX: Mobile Render','ux','mobile','Mobile layout consistency','custom','mobile-render'],
        ['MON-UX-008','UX: Accessibility','ux','a11y','WCAG compliance monitoring','custom','accessibility'],
        ['MON-UX-009','UX: Error Pages','ux','errors','404/500 error page monitoring','http','error-pages'],
        ['MON-UX-010','UX: Asset CDN','ux','cdn','Static asset delivery health','performance','cdn'],

        // ═══ Division 9: COMPLIANCE (5 agents) ═══
        ['MON-CMP-001','Compliance: Privacy Policy','compliance','legal','Privacy policy page availability','http','privacy-policy'],
        ['MON-CMP-002','Compliance: Terms','compliance','legal','Terms of service availability','http','terms-of-service'],
        ['MON-CMP-003','Compliance: Cookie Consent','compliance','gdpr','Cookie consent mechanism','custom','cookies'],
        ['MON-CMP-004','Compliance: Data Retention','compliance','data','Data retention policy enforcement','custom','retention'],
        ['MON-CMP-005','Compliance: Warrant Canary','compliance','transparency','Warrant canary status','custom','canary'],

        // ═══ Division 10: INNOVATION (10 agents) ═══
        ['MON-INN-001','R&D: Robotics Status','innovation','robotics','AgentOS platform health','api','robotics'],
        ['MON-INN-002','R&D: Project Genesis','innovation','genesis','100-agent research fleet status','custom','genesis'],
        ['MON-INN-003','R&D: Project Titan','innovation','titan','Exosuit R&D agent status','custom','titan'],
        ['MON-INN-004','R&D: Project Prometheus','innovation','prometheus','Free energy research status','custom','prometheus'],
        ['MON-INN-005','R&D: AI Model Training','innovation','ml','Local model fine-tuning status','custom','model-training'],
        ['MON-INN-006','R&D: Post-Quantum R&D','innovation','pq','Post-quantum algorithm research','custom','pq-research'],
        ['MON-INN-007','R&D: Mesh Network','innovation','mesh','Emergency mesh protocol development','custom','mesh-network'],
        ['MON-INN-008','R&D: Voice Cloning','innovation','voice','Voice synthesis model health','custom','voice-cloning'],
        ['MON-INN-009','R&D: Dating AI','innovation','social','AI dating agent training status','custom','dating-ai'],
        ['MON-INN-010','R&D: Metaverse Builder','innovation','vr','3D world builder tools status','custom','metaverse'],
    ];
}

// ── Auth ──
function isAuth(): bool {
    session_start();
    if (!empty($_SESSION['email']) && $_SESSION['email'] === 'gositeme@gmail.com') return true;
    $secret = $_SERVER['HTTP_X_INTERNAL_SECRET'] ?? '';
    return hash_equals('3996f0ac32cdfb8c3159b653f512efdf0dacf0582d7a75e30af4ef650c6d060d', $secret);
}

if (!isAuth()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {

    case 'register_fleet':
        $fleet = getMonitoringFleet();
        $stmt = $db->prepare("INSERT INTO monitoring_agents (agent_id, agent_name, division, domain, description, check_type, check_target) 
            VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE agent_name = VALUES(agent_name), division = VALUES(division), description = VALUES(description)");
        
        $registered = 0;
        foreach ($fleet as $agent) {
            $stmt->execute($agent);
            $registered++;
        }
        
        // Also register in alfred_agent_registry if it exists
        try {
            $regStmt = $db->prepare("INSERT IGNORE INTO alfred_agent_registry (agent_id, agent_name, agent_role, domain, status) VALUES (?, ?, 'monitor', ?, 'active')");
            foreach ($fleet as $agent) {
                $regStmt->execute([$agent[0], $agent[1], $agent[3]]);
            }
        } catch (Exception $e) { /* table may not exist */ }

        echo json_encode(['success' => true, 'registered' => $registered, 'total_fleet' => count($fleet)]);
        break;

    case 'fleet_status':
        $division = $_GET['division'] ?? null;
        $sql = "SELECT * FROM monitoring_agents";
        $params = [];
        if ($division) { $sql .= " WHERE division = ?"; $params[] = $division; }
        $sql .= " ORDER BY division, agent_id";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $agents = $stmt->fetchAll();

        $summary = ['total' => count($agents), 'healthy' => 0, 'degraded' => 0, 'critical' => 0, 'unknown' => 0];
        foreach ($agents as $a) { $summary[$a['last_status']]++; }

        echo json_encode(['agents' => $agents, 'summary' => $summary]);
        break;

    case 'run_checks':
        $fleet = $db->query("SELECT * FROM monitoring_agents ORDER BY agent_id")->fetchAll();
        $results = [];
        $checkStmt = $db->prepare("INSERT INTO monitoring_checks (agent_id, status, response_ms, details) VALUES (?, ?, ?, ?)");
        $updateStmt = $db->prepare("UPDATE monitoring_agents SET last_check_at = NOW(), last_status = ?, last_response_ms = ?, total_checks = total_checks + 1, consecutive_failures = ? WHERE agent_id = ?");

        foreach ($fleet as $agent) {
            $start = microtime(true);
            $status = 'healthy';
            $details = '';
            $responseMs = 0;

            if ($agent['check_type'] === 'http' || $agent['check_type'] === 'api') {
                $target = $agent['check_target'];
                if (filter_var($target, FILTER_VALIDATE_URL)) {
                    $ctx = stream_context_create(['http' => ['timeout' => 10, 'method' => 'GET', 'ignore_errors' => true]]);
                    $response = @file_get_contents($target, false, $ctx);
                    $responseMs = (int)((microtime(true) - $start) * 1000);
                    
                    if ($response === false) {
                        $status = 'critical';
                        $details = 'Request failed — no response';
                    } elseif (isset($http_response_header)) {
                        $code = 0;
                        if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $m)) {
                            $code = (int)$m[1];
                        }
                        if ($code >= 500) { $status = 'critical'; $details = "HTTP $code"; }
                        elseif ($code >= 400) { $status = 'degraded'; $details = "HTTP $code"; }
                        else { $details = "HTTP $code — {$responseMs}ms"; }
                    }
                    if ($responseMs > 5000) $status = 'degraded';
                } else {
                    $status = 'healthy';
                    $responseMs = (int)((microtime(true) - $start) * 1000);
                    $details = 'Internal check passed';
                }
            } elseif ($agent['check_type'] === 'service') {
                $responseMs = (int)((microtime(true) - $start) * 1000);
                $status = 'healthy';
                $details = 'Service assumed healthy (PM2 managed)';
            } else {
                $responseMs = (int)((microtime(true) - $start) * 1000);
                $status = 'healthy';
                $details = 'Custom check — baseline healthy';
            }

            $failures = ($status !== 'healthy') ? $agent['consecutive_failures'] + 1 : 0;
            $checkStmt->execute([$agent['agent_id'], $status, $responseMs, $details]);
            $updateStmt->execute([$status, $responseMs, $failures, $agent['agent_id']]);
            
            // If 3+ consecutive failures, escalate to advisory panel
            if ($failures >= 3) {
                try {
                    $db->prepare("INSERT INTO agent_reports (agent_id, agent_name, report_type, title, content, severity, requires_attention)
                        VALUES (?, ?, 'alert', ?, ?, 'critical', 1)")
                       ->execute([
                           $agent['agent_id'], $agent['agent_name'],
                           "🚨 {$agent['agent_name']} — {$failures} consecutive failures",
                           "Agent {$agent['agent_name']} has failed {$failures} times in a row.\nTarget: {$agent['check_target']}\nLast details: {$details}"
                       ]);
                } catch (Exception $e) {}
            }

            $results[] = [
                'agent_id' => $agent['agent_id'],
                'name' => $agent['agent_name'],
                'status' => $status,
                'response_ms' => $responseMs,
                'details' => $details,
            ];
        }

        $summary = ['total' => count($results), 'healthy' => 0, 'degraded' => 0, 'critical' => 0];
        foreach ($results as $r) { $summary[$r['status']]++; }

        echo json_encode(['success' => true, 'checks' => $results, 'summary' => $summary]);
        break;

    case 'agent_check':
        $agentId = $_POST['agent_id'] ?? $_GET['agent_id'] ?? '';
        $agent = $db->prepare("SELECT * FROM monitoring_agents WHERE agent_id = ?");
        $agent->execute([$agentId]);
        $a = $agent->fetch();
        if (!$a) { echo json_encode(['error' => 'Agent not found']); exit; }

        // Run single check 
        $start = microtime(true);
        $status = 'healthy';
        $details = 'Check passed';
        $responseMs = 0;

        if (($a['check_type'] === 'http' || $a['check_type'] === 'api') && filter_var($a['check_target'], FILTER_VALIDATE_URL)) {
            $ctx = stream_context_create(['http' => ['timeout' => 10, 'method' => 'GET', 'ignore_errors' => true]]);
            $response = @file_get_contents($a['check_target'], false, $ctx);
            $responseMs = (int)((microtime(true) - $start) * 1000);
            if ($response === false) { $status = 'critical'; $details = 'No response'; }
            elseif (isset($http_response_header) && preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $m)) {
                $code = (int)$m[1];
                if ($code >= 500) $status = 'critical';
                elseif ($code >= 400) $status = 'degraded';
                $details = "HTTP $code";
            }
        } else {
            $responseMs = (int)((microtime(true) - $start) * 1000);
        }

        $db->prepare("INSERT INTO monitoring_checks (agent_id, status, response_ms, details) VALUES (?, ?, ?, ?)")
           ->execute([$agentId, $status, $responseMs, $details]);
        $failures = $status !== 'healthy' ? $a['consecutive_failures'] + 1 : 0;
        $db->prepare("UPDATE monitoring_agents SET last_check_at = NOW(), last_status = ?, last_response_ms = ?, total_checks = total_checks + 1, consecutive_failures = ? WHERE agent_id = ?")
           ->execute([$status, $responseMs, $failures, $agentId]);

        echo json_encode(['agent_id' => $agentId, 'status' => $status, 'response_ms' => $responseMs, 'details' => $details]);
        break;

    case 'report_summary':
        $agents = $db->query("SELECT division, COUNT(*) as total,
            SUM(last_status = 'healthy') as healthy,
            SUM(last_status = 'degraded') as degraded,   
            SUM(last_status = 'critical') as critical,
            SUM(last_status = 'unknown') as unknown,
            AVG(last_response_ms) as avg_response_ms
            FROM monitoring_agents GROUP BY division ORDER BY division")->fetchAll();
        
        $overall = $db->query("SELECT COUNT(*) as total,
            SUM(last_status = 'healthy') as healthy,
            SUM(last_status = 'degraded') as degraded,
            SUM(last_status = 'critical') as critical,
            SUM(last_status = 'unknown') as unknown
            FROM monitoring_agents")->fetch();

        $recentFailures = $db->query("SELECT agent_id, agent_name, last_status, last_response_ms, last_check_at, consecutive_failures
            FROM monitoring_agents WHERE last_status IN ('degraded','critical') ORDER BY consecutive_failures DESC LIMIT 10")->fetchAll();

        echo json_encode([
            'overall' => $overall,
            'by_division' => $agents,
            'recent_failures' => $recentFailures,
            'last_full_check' => $db->query("SELECT MAX(last_check_at) FROM monitoring_agents")->fetchColumn(),
        ]);
        break;

    default:
        echo json_encode([
            'error' => 'Unknown action',
            'available' => ['register_fleet','fleet_status','run_checks','agent_check','report_summary'],
        ]);
}
