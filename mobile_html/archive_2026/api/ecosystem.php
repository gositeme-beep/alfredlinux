<?php
/**
 * Alfred Ecosystem API — Sovereign Infrastructure Hub
 * ────────────────────────────────────────────────────
 * Central API that all ecosystem components call to discover,
 * authenticate, and communicate with each other.
 *
 * Endpoints:
 *   GET  ?action=status          → Full ecosystem health
 *   GET  ?action=services        → Service registry / discovery
 *   GET  ?action=intel           → Intelligence summary
 *   POST ?action=event           → Publish ecosystem event (WebSocket fan-out)
 *   GET  ?action=manifest        → PWA manifest with dynamic ecosystem links
 */

define('GOSITEME_API', true);
require_once dirname(__DIR__) . '/api/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');
header('X-Powered-By: Alfred Ecosystem/2.0');

$action = $_GET['action'] ?? 'status';

switch ($action) {

    // ── Ecosystem Status ────────────────────────────────────────
    case 'status':
        $status = [
            'ecosystem' => 'alfred',
            'version' => '2.0.0',
            'timestamp' => date('c'),
            'services' => [],
        ];

        // Check Meilisearch
        $meiliHost = getenv('MEILI_HOST') ?: 'http://localhost:7700';
        $ch = curl_init("$meiliHost/health");
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 3]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $status['services']['meilisearch'] = ['status' => $code === 200 ? 'up' : 'down', 'port' => 7700];

        // Check WebSocket server
        $ws = @fsockopen('127.0.0.1', 3010, $errno, $errstr, 2);
        $status['services']['websocket'] = ['status' => $ws ? 'up' : 'down', 'port' => 3010];
        if ($ws) fclose($ws);

        // Check Redis
        $redis = @fsockopen('127.0.0.1', 6379, $errno, $errstr, 2);
        $status['services']['redis'] = ['status' => $redis ? 'up' : 'down', 'port' => 6379];
        if ($redis) fclose($redis);

        // Check Ollama
        $ch = curl_init('http://127.0.0.1:11434/api/tags');
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 3]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $status['services']['ollama'] = ['status' => $code === 200 ? 'up' : 'down', 'port' => 11434];

        // Check GoCodeMe middleware
        $mid = @fsockopen('127.0.0.1', 3001, $errno, $errstr, 2);
        $status['services']['middleware'] = ['status' => $mid ? 'up' : 'down', 'port' => 3001];
        if ($mid) fclose($mid);

        // Database stats
        $db = getDB();
        if ($db) {
            try {
                $status['index'] = [
                    'pages' => (int)$db->query("SELECT COUNT(*) FROM crawler_pages")->fetchColumn(),
                    'domains' => (int)$db->query("SELECT COUNT(DISTINCT domain) FROM crawler_pages")->fetchColumn(),
                    'queue' => (int)$db->query("SELECT COUNT(*) FROM crawler_queue WHERE status='pending'")->fetchColumn(),
                ];
            } catch (Exception $e) {
                $status['index'] = ['pages' => 0, 'domains' => 0, 'queue' => 0];
            }

            try {
                $status['intel'] = [
                    'profiled' => (int)$db->query("SELECT COUNT(*) FROM intel_domains")->fetchColumn(),
                    'classified' => (int)$db->query("SELECT COUNT(*) FROM intel_classifications")->fetchColumn(),
                    'feeds' => (int)$db->query("SELECT COUNT(*) FROM intel_feeds WHERE status='active'")->fetchColumn(),
                    'threats' => (int)$db->query("SELECT COUNT(*) FROM intel_domains WHERE threat_level != 'safe'")->fetchColumn(),
                ];
            } catch (Exception $e) {
                $status['intel'] = ['profiled' => 0, 'classified' => 0, 'feeds' => 0, 'threats' => 0];
            }
        }

        $overall = true;
        foreach ($status['services'] as $svc) {
            if ($svc['status'] !== 'up') $overall = false;
        }
        $status['overall'] = $overall ? 'healthy' : 'degraded';

        jsonResponse($status);
        break;

    // ── Service Discovery Registry ──────────────────────────────
    case 'services':
        jsonResponse([
            'services' => [
                [
                    'name' => 'Alfred Search',
                    'endpoint' => '/api/alfred-search.php',
                    'type' => 'search',
                    'methods' => ['GET'],
                    'params' => ['q' => 'query', 'mode' => 'web|code|news|instant|deep|emergency', 'action' => 'suggest|stats|voice'],
                ],
                [
                    'name' => 'Sovereignty Gateway',
                    'endpoint' => '/api/gateway.php',
                    'type' => 'proxy',
                    'methods' => ['GET', 'POST'],
                    'params' => ['service' => 'openai|groq|anthropic|telnyx|stripe|vapi|together|weather', 'endpoint' => 'API path'],
                ],
                [
                    'name' => 'WebSocket Server',
                    'endpoint' => 'wss://gositeme.com/ws/',
                    'type' => 'realtime',
                    'port' => 3010,
                    'channels' => ['fleet:*', 'agent:*', 'call:*', 'marketplace:*', 'alert:*', 'chat:*', 'presence:*', 'metrics:*'],
                ],
                [
                    'name' => 'Crawler Admin',
                    'endpoint' => '/api/crawler-admin.php',
                    'type' => 'admin',
                    'methods' => ['GET', 'POST'],
                    'params' => ['action' => 'stats|queue|domains|recent|add_url|add_urls'],
                ],
                [
                    'name' => 'Ecosystem Hub',
                    'endpoint' => '/api/ecosystem.php',
                    'type' => 'discovery',
                    'methods' => ['GET', 'POST'],
                    'params' => ['action' => 'status|services|intel|event|manifest'],
                ],
                [
                    'name' => 'Ollama AI',
                    'endpoint' => 'http://127.0.0.1:11434',
                    'type' => 'ai',
                    'models' => ['llama3.1'],
                    'internal' => true,
                ],
                [
                    'name' => 'Meilisearch',
                    'endpoint' => 'http://127.0.0.1:7700',
                    'type' => 'search-index',
                    'indexes' => ['tools', 'articles', 'agents', 'web', 'intel'],
                    'internal' => true,
                ],
                [
                    'name' => 'Veil Protocol',
                    'endpoint' => '/veil/',
                    'type' => 'encryption',
                    'features' => ['E2E', 'Kyber-1024', 'AES-256-GCM', 'Double-Ratchet', 'Steganography'],
                ],
                [
                    'name' => 'Emergency Kit',
                    'endpoint' => '/emergency-kit',
                    'type' => 'survival',
                    'features' => ['offline-cache', 'medical', 'mesh-comms', 'maps', 'water', 'shelter'],
                ],
            ],
            'downloads' => [
                ['platform' => 'windows', 'arch' => 'x64', 'file' => '/downloads/Veil-Browser-3.0.0-win-x64.zip'],
                ['platform' => 'macos-intel', 'arch' => 'x64', 'file' => '/downloads/Veil-Browser-3.0.0-mac-intel.zip'],
                ['platform' => 'macos-arm64', 'arch' => 'arm64', 'file' => '/downloads/Veil-Browser-3.0.0-mac-arm64.zip'],
                ['platform' => 'linux-appimage', 'arch' => 'x64', 'file' => '/downloads/Veil-Browser-3.0.0.AppImage'],
                ['platform' => 'linux-deb', 'arch' => 'x64', 'file' => '/downloads/veil-browser_3.0.0_amd64.deb'],
                ['platform' => 'android', 'arch' => 'universal', 'file' => '/downloads/GoSiteMe-Veil.apk'],
                ['platform' => 'chrome', 'arch' => 'universal', 'file' => '/downloads/alfred-chrome-extension/'],
                ['platform' => 'web', 'arch' => 'universal', 'file' => '/editor/'],
            ],
        ]);
        break;

    // ── Intelligence Summary ────────────────────────────────────
    case 'intel':
        // Requires auth
        session_start();
        if (empty($_SESSION['logged_in'])) {
            http_response_code(403);
            jsonResponse(['error' => 'Authentication required']);
            break;
        }

        $db = getDB();
        $intel = ['timestamp' => date('c')];

        try {
            // Top authorities
            $stmt = $db->query("SELECT domain, authority_score, category, threat_level
                FROM intel_domains ORDER BY authority_score DESC LIMIT 20");
            $intel['top_authorities'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Category distribution
            $stmt = $db->query("SELECT category, COUNT(*) as count
                FROM intel_classifications GROUP BY category ORDER BY count DESC");
            $intel['categories'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Active threats
            $stmt = $db->query("SELECT domain, threat_level, threat_tags
                FROM intel_domains WHERE threat_level != 'safe'
                ORDER BY FIELD(threat_level,'critical','high','medium','low')");
            $intel['threats'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Recent changes
            $stmt = $db->query("SELECT f.url_hash, p.url, p.title
                FROM intel_fingerprints f
                JOIN crawler_pages p ON f.url_hash = p.url_hash
                WHERE f.checked_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY f.url_hash
                HAVING COUNT(*) > 1
                LIMIT 20");
            $intel['recent_changes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Feed activity
            $stmt = $db->query("SELECT domain, title, item_count, last_fetched
                FROM intel_feeds WHERE status = 'active'
                ORDER BY last_fetched DESC LIMIT 20");
            $intel['active_feeds'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            $intel['error'] = 'Intel tables not initialized. Run: php scripts/intel-crawler.php full';
        }

        jsonResponse($intel);
        break;

    // ── Publish Ecosystem Event ─────────────────────────────────
    case 'event':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            jsonResponse(['error' => 'POST required']);
            break;
        }

        // Internal only — check secret or localhost
        $isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']);
        $secret = $_SERVER['HTTP_X_INTERNAL_SECRET'] ?? '';
        $expectedSecret = getenv('INTERNAL_API_SECRET') ?: '';

        if (!$isLocal && ($expectedSecret === '' || !hash_equals($expectedSecret, $secret))) {
            http_response_code(403);
            jsonResponse(['error' => 'Internal access only']);
            break;
        }

        $body = json_decode(file_get_contents('php://input'), true);
        $channel = $body['channel'] ?? '';
        $event = $body['event'] ?? '';
        $data = $body['data'] ?? [];

        if (!$channel || !$event) {
            http_response_code(400);
            jsonResponse(['error' => 'channel and event required']);
            break;
        }

        // Fan out to WebSocket server
        $ch = curl_init('http://127.0.0.1:3010/publish');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode([
                'channel' => $channel,
                'event' => $event,
                'data' => $data,
                'source' => 'ecosystem-api',
                'timestamp' => date('c'),
            ]),
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        jsonResponse(['published' => $code >= 200 && $code < 300, 'channel' => $channel, 'event' => $event]);
        break;

    // ── Dynamic PWA Manifest ────────────────────────────────────
    case 'manifest':
        jsonResponse([
            'name' => 'Alfred — Sovereign Internet Platform',
            'short_name' => 'Alfred',
            'description' => 'Search, browse, communicate, encrypt, survive — all sovereign.',
            'start_url' => '/search',
            'display' => 'standalone',
            'background_color' => '#030308',
            'theme_color' => '#5b9cf5',
            'icons' => [
                ['src' => '/assets/images/alfred-icon-512.png', 'sizes' => '512x512', 'type' => 'image/png'],
                ['src' => '/assets/images/alfred-icon.svg', 'sizes' => 'any', 'type' => 'image/svg+xml'],
            ],
            'shortcuts' => [
                ['name' => 'Search', 'url' => '/search', 'description' => 'Alfred Search Engine'],
                ['name' => 'Emergency Kit', 'url' => '/emergency-kit', 'description' => 'Survival Systems'],
                ['name' => 'Veil', 'url' => '/veil/', 'description' => 'Encrypted Communications'],
                ['name' => 'Ecosystem', 'url' => '/ecosystem', 'description' => 'Full Platform Overview'],
            ],
        ]);
        break;

    default:
        http_response_code(400);
        jsonResponse(['error' => 'Unknown action', 'available' => ['status', 'services', 'intel', 'event', 'manifest']]);
}
