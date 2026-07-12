<?php
/**
 * Alfred Smart Search API
 * ───────────────────────
 * Meilisearch-powered instant search across tools, docs, conversations, and knowledge.
 * Falls back to MySQL FULLTEXT when Meilisearch is unavailable.
 *
 * Endpoints:
 *   GET  ?action=search&q=...&index=...     → Search across indices
 *   GET  ?action=suggest&q=...              → Autocomplete suggestions
 *   POST ?action=index&index=...            → Index a document (internal)
 *   POST ?action=reindex&index=...          → Full reindex (admin)
 *   GET  ?action=stats                      → Index statistics
 *
 * Indices:
 *   tools      → 13,000+ tools from all providers
 *   docs       → Documentation pages
 *   knowledge  → RAG knowledge base
 *   agents     → 100 Alfred agents
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// Meilisearch config
define('MEILI_HOST', getenv('MEILI_HOST') ?: 'http://localhost:7700');
$meiliKeyFile = getenv('HOME') . '/.local/meilisearch/master-key.txt';
define('MEILI_KEY', getenv('MEILI_KEY') ?: (is_readable($meiliKeyFile) ? trim(file_get_contents($meiliKeyFile)) : ''));

// ── Meilisearch Client ─────────────────────────────────────────
function meiliRequest($method, $path, $body = null) {
    $ch = curl_init(MEILI_HOST . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => array_filter([
            'Content-Type: application/json',
            MEILI_KEY ? 'Authorization: Bearer ' . MEILI_KEY : null,
        ]),
    ]);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    } elseif ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }

    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) return ['error' => $error, 'status' => 0];
    return ['data' => json_decode($response, true), 'status' => $code];
}

function isMeiliAvailable() {
    $result = meiliRequest('GET', '/health');
    return ($result['status'] === 200 && ($result['data']['status'] ?? '') === 'available');
}

// ── MySQL Fallback Search ───────────────────────────────────────
function mysqlSearch($query, $index, $limit, $offset) {
    $db = getDB();
    if (!$db) return ['hits' => [], 'total' => 0];

    $query = sanitize($query, 200);

    switch ($index) {
        case 'tools':
            // Search native tool registry
            $stmt = $db->prepare("SELECT name, description, category, 'native' as provider
                FROM alfred_tools
                WHERE name LIKE ? OR description LIKE ?
                ORDER BY name LIMIT ? OFFSET ?");
            $like = '%' . $query . '%';
            dbExecute($stmt, [$like, $like, $limit, $offset]);
            $results = $stmt->fetchAll();

            // Supplement with in-memory tool registry
            require_once __DIR__ . '/tools.php';
            // The tools.php file defines $TOOL_REGISTRY
            break;

        case 'agents':
            $stmt = $db->prepare("SELECT name, role, description, department
                FROM alfred_agents
                WHERE name LIKE ? OR description LIKE ? OR role LIKE ?
                LIMIT ? OFFSET ?");
            $like = '%' . $query . '%';
            dbExecute($stmt, [$like, $like, $like, $limit, $offset]);
            $results = $stmt->fetchAll();
            break;

        case 'docs':
            $stmt = $db->prepare("SELECT title, path, content_preview, category
                FROM alfred_docs
                WHERE MATCH(title, content_preview) AGAINST(? IN NATURAL LANGUAGE MODE)
                LIMIT ? OFFSET ?");
            dbExecute($stmt, [$query, $limit, $offset]);
            $results = $stmt->fetchAll();
            break;

        default:
            $results = [];
    }

    return [
        'hits' => $results ?? [],
        'total' => count($results ?? []),
        'source' => 'mysql_fallback',
    ];
}

// ── Router ──────────────────────────────────────────────────────
$action = sanitize($_GET['action'] ?? '', 30);

switch ($action) {

    // ── Multi-index search ──────────────────────────────────────
    case 'search':
        $q = sanitize($_GET['q'] ?? '', 200);
        $index = sanitize($_GET['index'] ?? 'tools', 30);
        $limit = min(intval($_GET['limit'] ?? 20), 100);
        $offset = max(intval($_GET['offset'] ?? 0), 0);
        $filters = sanitize($_GET['filter'] ?? '', 200);

        if (strlen($q) < 1) {
            jsonResponse(['error' => 'Query too short'], 400);
        }

        $validIndices = ['tools', 'docs', 'knowledge', 'agents', 'articles'];
        if (!in_array($index, $validIndices)) {
            jsonResponse(['error' => 'Invalid index', 'valid' => $validIndices], 400);
        }

        // Try Meilisearch first
        if (isMeiliAvailable()) {
            $searchBody = [
                'q' => $q,
                'limit' => $limit,
                'offset' => $offset,
                'attributesToHighlight' => ['name', 'title', 'description'],
                'highlightPreTag' => '<mark>',
                'highlightPostTag' => '</mark>',
            ];
            if ($filters) $searchBody['filter'] = $filters;

            $result = meiliRequest('POST', "/indexes/{$index}/search", $searchBody);

            if ($result['status'] === 200) {
                jsonResponse([
                    'success' => true,
                    'source' => 'meilisearch',
                    'query' => $q,
                    'index' => $index,
                    'hits' => $result['data']['hits'] ?? [],
                    'total' => $result['data']['estimatedTotalHits'] ?? 0,
                    'processing_time_ms' => $result['data']['processingTimeMs'] ?? 0,
                    'limit' => $limit,
                    'offset' => $offset,
                ]);
            }
        }

        // Fallback to MySQL
        $results = mysqlSearch($q, $index, $limit, $offset);
        jsonResponse([
            'success' => true,
            'source' => 'mysql',
            'query' => $q,
            'index' => $index,
            'hits' => $results['hits'],
            'total' => $results['total'],
            'limit' => $limit,
            'offset' => $offset,
        ]);
        break;

    // ── Multi-index search (search all at once) ─────────────────
    case 'multi-search':
        $q = sanitize($_GET['q'] ?? '', 200);
        if (strlen($q) < 1) {
            jsonResponse(['error' => 'Query too short'], 400);
        }

        if (isMeiliAvailable()) {
            $result = meiliRequest('POST', '/multi-search', [
                'queries' => [
                    ['indexUid' => 'tools', 'q' => $q, 'limit' => 5],
                    ['indexUid' => 'agents', 'q' => $q, 'limit' => 5],
                    ['indexUid' => 'docs', 'q' => $q, 'limit' => 3],
                    ['indexUid' => 'knowledge', 'q' => $q, 'limit' => 3],
                    ['indexUid' => 'articles', 'q' => $q, 'limit' => 3],
                ]
            ]);

            if ($result['status'] === 200) {
                $grouped = [];
                foreach ($result['data']['results'] ?? [] as $r) {
                    $grouped[$r['indexUid']] = [
                        'hits' => $r['hits'] ?? [],
                        'total' => $r['estimatedTotalHits'] ?? 0,
                    ];
                }
                jsonResponse(['success' => true, 'source' => 'meilisearch', 'query' => $q, 'results' => $grouped]);
            }
        }

        // Fallback
        $grouped = [];
        foreach (['tools', 'agents'] as $idx) {
            $grouped[$idx] = mysqlSearch($q, $idx, 5, 0);
        }
        jsonResponse(['success' => true, 'source' => 'mysql', 'query' => $q, 'results' => $grouped]);
        break;

    // ── Autocomplete suggestions ────────────────────────────────
    case 'suggest':
        $q = sanitize($_GET['q'] ?? '', 100);
        if (strlen($q) < 2) {
            jsonResponse(['suggestions' => []]);
        }

        if (isMeiliAvailable()) {
            $result = meiliRequest('POST', '/indexes/tools/search', [
                'q' => $q,
                'limit' => 8,
                'attributesToRetrieve' => ['name', 'category', 'provider'],
            ]);

            if ($result['status'] === 200) {
                $suggestions = array_map(function($hit) {
                    return [
                        'text' => $hit['name'] ?? '',
                        'category' => $hit['category'] ?? '',
                        'provider' => $hit['provider'] ?? 'native',
                    ];
                }, $result['data']['hits'] ?? []);

                jsonResponse(['success' => true, 'suggestions' => $suggestions]);
            }
        }

        // MySQL fallback
        $db = getDB();
        if ($db) {
            $stmt = $db->prepare("SELECT DISTINCT name, category FROM alfred_tools
                WHERE name LIKE ? ORDER BY name LIMIT 8");
            $stmt->execute([$q . '%']);
            jsonResponse(['success' => true, 'suggestions' => $stmt->fetchAll()]);
        }
        jsonResponse(['suggestions' => []]);
        break;

    // ── Index a document (internal) ─────────────────────────────
    case 'index':
        // Verify internal caller
        $secret = $_SERVER['HTTP_X_JOB_SECRET'] ?? '';
        $jobSecret = getenv('JOB_SECRET') ?: '';
        if (!$jobSecret || !$secret || !hash_equals($jobSecret, $secret)) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            if (empty($_SESSION['is_admin'])) {
                jsonResponse(['error' => 'Unauthorized'], 401);
            }
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $index = sanitize($input['index'] ?? 'tools', 30);
        $documents = $input['documents'] ?? [];

        if (empty($documents) || !is_array($documents)) {
            jsonResponse(['error' => 'documents array required'], 400);
        }

        // Each document needs an id
        foreach ($documents as &$doc) {
            if (!isset($doc['id'])) {
                $doc['id'] = uniqid($index . '_');
            }
        }

        $result = meiliRequest('POST', "/indexes/{$index}/documents", $documents);
        jsonResponse([
            'success' => $result['status'] === 202,
            'taskUid' => $result['data']['taskUid'] ?? null,
            'indexed' => count($documents),
        ]);
        break;

    // ── Full reindex (admin) ────────────────────────────────────
    case 'reindex':
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['is_admin'])) {
            jsonResponse(['error' => 'Admin required'], 403);
        }

        $index = sanitize($_POST['index'] ?? $_GET['index'] ?? 'tools', 30);

        if (!isMeiliAvailable()) {
            jsonResponse(['error' => 'Meilisearch not available'], 503);
        }

        // Configure index settings
        $settings = [
            'tools' => [
                'searchableAttributes' => ['name', 'description', 'category', 'provider'],
                'filterableAttributes' => ['category', 'provider', 'type'],
                'sortableAttributes' => ['name', 'relevance_score'],
                'rankingRules' => ['words', 'typo', 'proximity', 'attribute', 'sort', 'exactness'],
            ],
            'agents' => [
                'searchableAttributes' => ['name', 'role', 'description', 'department', 'capabilities'],
                'filterableAttributes' => ['department', 'tier', 'status'],
                'sortableAttributes' => ['name', 'tier'],
            ],
            'docs' => [
                'searchableAttributes' => ['title', 'content', 'category', 'tags'],
                'filterableAttributes' => ['category', 'language', 'type'],
                'sortableAttributes' => ['title', 'updated_at'],
            ],
            'knowledge' => [
                'searchableAttributes' => ['content', 'title', 'source', 'tags'],
                'filterableAttributes' => ['source', 'type', 'language'],
                'sortableAttributes' => ['created_at', 'relevance'],
            ],
        ];

        // Create/update index with settings
        meiliRequest('POST', '/indexes', ['uid' => $index, 'primaryKey' => 'id']);
        if (isset($settings[$index])) {
            meiliRequest('PATCH', "/indexes/{$index}/settings", $settings[$index]);
        }

        // Pull data from source and index
        $count = 0;
        if ($index === 'tools') {
            // Index native tools from registry
            require_once __DIR__ . '/tools.php';
            if (isset($TOOL_REGISTRY) && is_array($TOOL_REGISTRY)) {
                $batch = [];
                foreach ($TOOL_REGISTRY as $name => $tool) {
                    $batch[] = [
                        'id' => 'native_' . md5($name),
                        'name' => $name,
                        'description' => $tool['description'] ?? '',
                        'category' => $tool['category'] ?? 'general',
                        'provider' => 'native',
                        'type' => $tool['type'] ?? 'function',
                    ];
                }
                if ($batch) {
                    meiliRequest('POST', "/indexes/tools/documents", $batch);
                    $count += count($batch);
                }
            }
        } elseif ($index === 'agents') {
            $db = getDB();
            if ($db) {
                try {
                    $agents = $db->query("SELECT * FROM alfred_agents LIMIT 500")->fetchAll();
                    $batch = array_map(function($a) {
                        return [
                            'id' => 'agent_' . $a['id'],
                            'name' => $a['name'],
                            'role' => $a['role'] ?? '',
                            'description' => $a['description'] ?? '',
                            'department' => $a['department'] ?? '',
                            'tier' => $a['tier'] ?? 1,
                        ];
                    }, $agents);
                    if ($batch) {
                        meiliRequest('POST', "/indexes/agents/documents", $batch);
                        $count += count($batch);
                    }
                } catch (Exception $e) {
                    // Table may not exist yet
                }
            }
        }

        jsonResponse([
            'success' => true,
            'index' => $index,
            'documents_indexed' => $count,
            'message' => "Reindex started for '$index'",
        ]);
        break;

    // ── Index stats ─────────────────────────────────────────────
    case 'stats':
        if (isMeiliAvailable()) {
            $result = meiliRequest('GET', '/stats');
            jsonResponse([
                'success' => true,
                'source' => 'meilisearch',
                'stats' => $result['data'] ?? [],
            ]);
        }

        jsonResponse([
            'success' => true,
            'source' => 'mysql_only',
            'message' => 'Meilisearch not available, using MySQL fallback',
        ]);
        break;

    default:
        jsonResponse(['error' => 'Unknown action. Use: search, multi-search, suggest, index, reindex, stats'], 400);
}
