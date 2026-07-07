<?php
/**
 * Help Center API
 * Endpoints: search, feedback, popular
 */
define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

// CORS & content type
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Ensure feedback table exists
function ensureHelpTable() {
    $db = getDB();
    if (!$db) return false;
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS alfred_help_feedback (
            id INT AUTO_INCREMENT PRIMARY KEY,
            article_id VARCHAR(50) NOT NULL,
            user_id INT DEFAULT NULL,
            helpful TINYINT(1) NOT NULL,
            ip_hash VARCHAR(64) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_article (article_id),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        return true;
    } catch (PDOException $e) {
        error_log("Help table creation failed: " . $e->getMessage());
        return false;
    }
}

// Get action
$action = isset($_GET['action']) ? sanitize($_GET['action']) : '';

switch ($action) {

    // ── Search articles ──────────────────────────────────────────────
    case 'search':
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            jsonResponse(['status' => 'error', 'message' => 'Method not allowed'], 405);
        }

        $query = isset($_GET['q']) ? trim($_GET['q']) : '';
        if (strlen($query) < 2) {
            jsonResponse(['status' => 'error', 'message' => 'Query must be at least 2 characters'], 400);
        }

        $query = sanitize($query, 100);
        $terms = preg_split('/\s+/', strtolower($query));

        // Load article data (same as help.php)
        $articles = getArticleIndex();
        $results = [];

        foreach ($articles as $art) {
            $searchable = strtolower($art['title'] . ' ' . $art['tags'] . ' ' . $art['category']);
            $match = true;
            foreach ($terms as $term) {
                if (strpos($searchable, $term) === false) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                $results[] = [
                    'id'       => $art['id'],
                    'title'    => $art['title'],
                    'category' => $art['category'],
                    'url'      => '/help#' . $art['id']
                ];
            }
        }

        jsonResponse([
            'status'  => 'success',
            'query'   => $query,
            'count'   => count($results),
            'results' => $results
        ]);
        break;

    // ── Submit feedback ──────────────────────────────────────────────
    case 'feedback':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['status' => 'error', 'message' => 'Method not allowed'], 405);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            jsonResponse(['status' => 'error', 'message' => 'Invalid JSON body'], 400);
        }

        $articleId = isset($input['article_id']) ? sanitize($input['article_id'], 50) : '';
        $helpful   = isset($input['helpful']) ? (int)$input['helpful'] : -1;

        if (empty($articleId) || !preg_match('/^[a-z0-9\-]+$/', $articleId)) {
            jsonResponse(['status' => 'error', 'message' => 'Invalid article_id'], 400);
        }
        if ($helpful !== 0 && $helpful !== 1) {
            jsonResponse(['status' => 'error', 'message' => 'helpful must be 0 or 1'], 400);
        }

        // Rate limit by IP hash
        $ipHash = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $userId = null;
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (isset($_SESSION['user_id'])) { $userId = (int)$_SESSION['user_id']; }

        if (!ensureHelpTable()) {
            jsonResponse(['status' => 'error', 'message' => 'Database unavailable'], 503);
        }

        $db = getDB();

        // Check for duplicate (same IP + article in last hour)
        try {
            $check = $db->prepare("SELECT id FROM alfred_help_feedback WHERE article_id = ? AND ip_hash = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR) LIMIT 1");
            $check->execute([$articleId, $ipHash]);
            if ($check->fetch()) {
                jsonResponse(['status' => 'ok', 'message' => 'Feedback already recorded']);
            }
        } catch (PDOException $e) {
            error_log("Help feedback check failed: " . $e->getMessage());
        }

        try {
            $stmt = $db->prepare("INSERT INTO alfred_help_feedback (article_id, user_id, helpful, ip_hash) VALUES (?, ?, ?, ?)");
            $stmt->execute([$articleId, $userId, $helpful, $ipHash]);
            jsonResponse(['status' => 'success', 'message' => 'Feedback recorded']);
        } catch (PDOException $e) {
            error_log("Help feedback insert failed: " . $e->getMessage());
            jsonResponse(['status' => 'error', 'message' => 'Failed to record feedback'], 500);
        }
        break;

    // ── Popular articles ─────────────────────────────────────────────
    case 'popular':
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            jsonResponse(['status' => 'error', 'message' => 'Method not allowed'], 405);
        }

        if (!ensureHelpTable()) {
            jsonResponse(['status' => 'error', 'message' => 'Database unavailable'], 503);
        }

        $db = getDB();
        $popular = [];

        try {
            $stmt = $db->query("SELECT article_id, COUNT(*) AS total_votes, SUM(helpful) AS helpful_votes FROM alfred_help_feedback GROUP BY article_id ORDER BY helpful_votes DESC, total_votes DESC LIMIT 10");
            $rows = $stmt->fetchAll();

            $index = getArticleIndex();
            $indexMap = [];
            foreach ($index as $art) { $indexMap[$art['id']] = $art; }

            foreach ($rows as $row) {
                $id = $row['article_id'];
                $popular[] = [
                    'id'            => $id,
                    'title'         => isset($indexMap[$id]) ? $indexMap[$id]['title'] : $id,
                    'category'      => isset($indexMap[$id]) ? $indexMap[$id]['category'] : 'unknown',
                    'total_votes'   => (int)$row['total_votes'],
                    'helpful_votes' => (int)$row['helpful_votes'],
                    'url'           => '/help#' . $id
                ];
            }
        } catch (PDOException $e) {
            error_log("Help popular query failed: " . $e->getMessage());
        }

        jsonResponse([
            'status' => 'success',
            'count'  => count($popular),
            'articles' => $popular
        ]);
        break;

    // ── Default ──────────────────────────────────────────────────────
    default:
        jsonResponse([
            'status'    => 'error',
            'message'   => 'Invalid action. Valid actions: search, feedback, popular',
            'endpoints' => [
                'GET  /api/help.php?action=search&q=query'  => 'Search articles',
                'POST /api/help.php?action=feedback'        => 'Submit article feedback (JSON: article_id, helpful)',
                'GET  /api/help.php?action=popular'         => 'Top 10 most helpful articles'
            ]
        ], 400);
}

/**
 * Hardcoded article index for search/popular lookups
 * Matches the data in help.php
 */
function getArticleIndex() {
    return [
        ['id'=>'gs-first-agent','title'=>'Creating Your First Agent','tags'=>'agent create new setup beginner onboarding','category'=>'Getting Started'],
        ['id'=>'gs-dashboard','title'=>'Understanding the Dashboard','tags'=>'dashboard navigation overview UI interface','category'=>'Getting Started'],
        ['id'=>'gs-voice-setup','title'=>'Setting Up Voice','tags'=>'voice phone number setup telephony call','category'=>'Getting Started'],
        ['id'=>'gs-channels','title'=>'Connecting Channels','tags'=>'channels integrations embed widget website slack','category'=>'Getting Started'],
        ['id'=>'gs-first-api','title'=>'Your First API Call','tags'=>'API curl first call request response','category'=>'Getting Started'],
        ['id'=>'ab-subscription','title'=>'Managing Your Subscription','tags'=>'subscription plan manage change downgrade','category'=>'Account & Billing'],
        ['id'=>'ab-usage-limits','title'=>'Understanding Usage Limits','tags'=>'usage limits tokens conversations quota overage','category'=>'Account & Billing'],
        ['id'=>'ab-upgrade','title'=>'Upgrading Your Plan','tags'=>'upgrade plan pro enterprise features','category'=>'Account & Billing'],
        ['id'=>'ab-payment','title'=>'Payment Methods','tags'=>'payment credit card paypal billing method','category'=>'Account & Billing'],
        ['id'=>'ab-invoices','title'=>'Invoices and Receipts','tags'=>'invoice receipt download PDF tax billing history','category'=>'Account & Billing'],
        ['id'=>'af-custom-agents','title'=>'Creating Custom Agents','tags'=>'agent custom create personality prompt instructions','category'=>'Agents & Fleets'],
        ['id'=>'af-templates','title'=>'Using Agent Templates','tags'=>'templates marketplace clone pre-built agent','category'=>'Agents & Fleets'],
        ['id'=>'af-fleet','title'=>'Fleet Management','tags'=>'fleet manage multiple agents team coordinate','category'=>'Agents & Fleets'],
        ['id'=>'af-roles','title'=>'Agent Roles and Tasks','tags'=>'roles tasks assignment permissions agent config','category'=>'Agents & Fleets'],
        ['id'=>'af-monitoring','title'=>'Monitoring Agent Performance','tags'=>'monitoring analytics performance metrics satisfaction KPI','category'=>'Agents & Fleets'],
        ['id'=>'vc-voice-agents','title'=>'Setting Up Voice Agents','tags'=>'voice agent setup phone telephony inbound outbound','category'=>'Voice & Calls'],
        ['id'=>'vc-ivr','title'=>'IVR Builder Guide','tags'=>'IVR interactive voice response menu builder flow','category'=>'Voice & Calls'],
        ['id'=>'vc-cloning','title'=>'Voice Cloning','tags'=>'voice cloning custom clone audio training','category'=>'Voice & Calls'],
        ['id'=>'vc-campaigns','title'=>'Call Campaigns','tags'=>'call campaign outbound bulk dialer list','category'=>'Voice & Calls'],
        ['id'=>'vc-conference','title'=>'Conference Rooms','tags'=>'conference room multi-party call meeting group','category'=>'Voice & Calls'],
        ['id'=>'ad-auth','title'=>'Authentication (API Keys & OAuth)','tags'=>'authentication API key OAuth token bearer auth','category'=>'API & Development'],
        ['id'=>'ad-rest','title'=>'REST API Quickstart','tags'=>'REST API quickstart endpoints CRUD request response','category'=>'API & Development'],
        ['id'=>'ad-sdks','title'=>'SDKs (Node.js, Python, PHP)','tags'=>'SDK Node.js Python PHP library package npm pip composer','category'=>'API & Development'],
        ['id'=>'ad-webhooks','title'=>'Webhooks Setup','tags'=>'webhook event callback notification real-time','category'=>'API & Development'],
        ['id'=>'ad-rate-limits','title'=>'Rate Limits & Errors','tags'=>'rate limit throttle error code HTTP status 429','category'=>'API & Development'],
        ['id'=>'en-org-setup','title'=>'Organization Setup','tags'=>'organization setup enterprise company account','category'=>'Enterprise'],
        ['id'=>'en-team-rbac','title'=>'Team Management & RBAC','tags'=>'team management RBAC roles permissions members users','category'=>'Enterprise'],
        ['id'=>'en-sso','title'=>'SSO Configuration','tags'=>'SSO single sign-on SAML Okta Azure AD identity provider','category'=>'Enterprise'],
        ['id'=>'en-white-label','title'=>'White-Label Setup','tags'=>'white label branding custom domain logo reseller','category'=>'Enterprise'],
        ['id'=>'en-audit','title'=>'Audit Logging','tags'=>'audit log compliance tracking activity security','category'=>'Enterprise'],
    ];
}
