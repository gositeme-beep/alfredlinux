<?php
/**
 * Alfred Deep Research API
 * ────────────────────────
 * Multi-step research pipeline: Plan → Search → Analyze → Synthesize
 * Uses web search, document parsing, and AI synthesis for comprehensive reports.
 *
 * Endpoints:
 *   POST ?action=research       → Start deep research task
 *   GET  ?action=status&id=...  → Check research status
 *   GET  ?action=result&id=...  → Get research results
 *   GET  ?action=history        → User's research history
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

requireCSRF();
apiRateLimit(10, 60, 'deep-research');

// AI config
define('OLLAMA_HOST', getenv('OLLAMA_HOST') ?: 'http://localhost:11434');
define('OPENAI_API_KEY', getenv('OPENAI_API_KEY') ?: '');

// ── Database Setup ──────────────────────────────────────────────
function ensureResearchTable() {
    $db = getDB();
    if (!$db) return;
    $db->exec("CREATE TABLE IF NOT EXISTS alfred_research (
        id VARCHAR(32) PRIMARY KEY,
        client_id INT DEFAULT NULL,
        query TEXT NOT NULL,
        mode VARCHAR(20) DEFAULT 'standard',
        status ENUM('planning','searching','analyzing','synthesizing','complete','failed') DEFAULT 'planning',
        steps JSON DEFAULT NULL,
        sources JSON DEFAULT NULL,
        result LONGTEXT DEFAULT NULL,
        token_count INT DEFAULT 0,
        source_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        completed_at TIMESTAMP NULL,
        KEY idx_client (client_id),
        KEY idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function getAuthUser() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return $_SESSION['client_id'] ?? null;
}

// ── AI Model Interface ──────────────────────────────────────────
function aiComplete($prompt, $options = []) {
    $model = $options['model'] ?? 'auto';
    $maxTokens = $options['max_tokens'] ?? 2000;
    $temperature = $options['temperature'] ?? 0.3;

    // Try Ollama first (self-hosted, free)
    $ollamaResult = ollamaGenerate($prompt, $model, $maxTokens, $temperature);
    if ($ollamaResult) return $ollamaResult;

    // Fallback to OpenAI
    if (OPENAI_API_KEY) {
        return openaiComplete($prompt, $maxTokens, $temperature);
    }

    // Last fallback: simple extraction without AI
    return ['text' => extractKeyPoints($prompt), 'tokens' => 0, 'model' => 'fallback'];
}

function ollamaGenerate($prompt, $model = 'auto', $maxTokens = 2000, $temperature = 0.3) {
    $ollamaModel = ($model === 'auto') ? 'llama3.1' : $model;

    $ch = curl_init(OLLAMA_HOST . '/api/generate');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'model' => $ollamaModel,
            'prompt' => $prompt,
            'stream' => false,
            'options' => [
                'num_predict' => $maxTokens,
                'temperature' => $temperature,
            ],
        ]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 120,
    ]);

    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) return null;

    $data = json_decode($response, true);
    return [
        'text' => $data['response'] ?? '',
        'tokens' => ($data['eval_count'] ?? 0) + ($data['prompt_eval_count'] ?? 0),
        'model' => $ollamaModel,
    ];
}

function openaiComplete($prompt, $maxTokens = 2000, $temperature = 0.3) {
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => 'You are Alfred, an expert research analyst. Provide thorough, well-structured analysis with citations.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
        ]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
    ]);

    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) return null;

    $data = json_decode($response, true);
    return [
        'text' => $data['choices'][0]['message']['content'] ?? '',
        'tokens' => $data['usage']['total_tokens'] ?? 0,
        'model' => 'gpt-4o-mini',
    ];
}

function extractKeyPoints($text) {
    // Simple extractive summary as last fallback
    $sentences = preg_split('/(?<=[.!?])\s+/', $text);
    return implode(' ', array_slice($sentences, 0, 10));
}

// ── Web Search ──────────────────────────────────────────────────
function webSearch($query, $limit = 5) {
    $encoded = urlencode($query);
    $ch = curl_init("https://s.jina.ai/{$encoded}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'X-Return-Format: text',
        ],
    ]);

    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) return [];

    $data = json_decode($response, true);
    $results = [];
    foreach (($data['data'] ?? []) as $item) {
        $results[] = [
            'title' => $item['title'] ?? '',
            'url' => $item['url'] ?? '',
            'snippet' => substr($item['content'] ?? '', 0, 500),
        ];
        if (count($results) >= $limit) break;
    }
    return $results;
}

function fetchPage($url) {
    // Validate URL
    $parsed = parse_url($url);
    if (!$parsed || !isset($parsed['host'])) return null;

    // SSRF protection
    $ip = gethostbyname($parsed['host']);
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        return null;
    }

    $encoded = urlencode($url);
    $ch = curl_init("https://r.jina.ai/{$url}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => ['Accept: text/plain'],
        CURLOPT_FOLLOWLOCATION => false,
    ]);

    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($code === 200) ? substr($response, 0, 50000) : null;
}

// ── Research Pipeline ───────────────────────────────────────────
function runResearch($researchId, $query, $mode, $db) {
    $totalTokens = 0;
    $allSources = [];
    $steps = [];

    // ── Step 1: Plan ────────────────────────────────────────────
    updateStatus($db, $researchId, 'planning');

    $planPrompt = "You are a research planner. Given this research query, generate 3-5 specific search queries that will cover different aspects of the topic. Return ONLY a JSON array of strings.\n\nQuery: {$query}";
    $planResult = aiComplete($planPrompt, ['max_tokens' => 500, 'temperature' => 0.4]);
    $totalTokens += $planResult['tokens'] ?? 0;

    // Parse search queries from AI response
    $searchQueries = [$query]; // always include original
    $jsonMatch = [];
    if (preg_match('/\[.*\]/s', $planResult['text'], $jsonMatch)) {
        $parsed = json_decode($jsonMatch[0], true);
        if (is_array($parsed)) {
            $searchQueries = array_merge($searchQueries, array_slice($parsed, 0, 4));
        }
    }

    $steps[] = ['step' => 'plan', 'queries' => $searchQueries, 'model' => $planResult['model'] ?? 'unknown'];
    updateSteps($db, $researchId, $steps);

    // ── Step 2: Search ──────────────────────────────────────────
    updateStatus($db, $researchId, 'searching');

    $searchResults = [];
    foreach (array_unique($searchQueries) as $sq) {
        $results = webSearch($sq, 3);
        $searchResults = array_merge($searchResults, $results);
    }

    // Deduplicate by URL
    $seen = [];
    $uniqueResults = [];
    foreach ($searchResults as $r) {
        $url = $r['url'] ?? '';
        if ($url && !isset($seen[$url])) {
            $seen[$url] = true;
            $uniqueResults[] = $r;
        }
    }
    $searchResults = array_slice($uniqueResults, 0, 10); // max 10 sources

    $steps[] = ['step' => 'search', 'results_found' => count($searchResults)];
    updateSteps($db, $researchId, $steps);

    // ── Step 3: Analyze (fetch & extract from top sources) ──────
    updateStatus($db, $researchId, 'analyzing');

    $sourceContents = [];
    $fetchCount = min(count($searchResults), ($mode === 'deep') ? 8 : 5);

    for ($i = 0; $i < $fetchCount; $i++) {
        $url = $searchResults[$i]['url'] ?? '';
        if (!$url) continue;

        $content = fetchPage($url);
        if ($content) {
            $sourceContents[] = [
                'title' => $searchResults[$i]['title'],
                'url' => $url,
                'content' => substr($content, 0, 8000),
            ];
            $allSources[] = [
                'title' => $searchResults[$i]['title'],
                'url' => $url,
            ];
        }
    }

    $steps[] = ['step' => 'analyze', 'sources_fetched' => count($sourceContents)];
    updateSteps($db, $researchId, $steps);

    // ── Step 4: Synthesize ──────────────────────────────────────
    updateStatus($db, $researchId, 'synthesizing');

    // Build context from all sources
    $context = "";
    foreach ($sourceContents as $idx => $src) {
        $context .= "\n\n--- Source [" . ($idx + 1) . "]: {$src['title']} ({$src['url']}) ---\n";
        $context .= $src['content'];
    }

    $synthesisPrompt = "You are Alfred, an expert research analyst. Based on the following sources, provide a comprehensive research report answering this query:\n\n**Query:** {$query}\n\n**Sources:**{$context}\n\n**Instructions:**\n1. Provide a structured report with sections\n2. Cite sources using [1], [2], etc.\n3. Include key findings, analysis, and conclusions\n4. Highlight any conflicting information\n5. End with a summary and confidence assessment\n\nGenerate the report in Markdown format.";

    $maxTokens = ($mode === 'deep') ? 4000 : 2000;
    $synthesisResult = aiComplete($synthesisPrompt, ['max_tokens' => $maxTokens, 'temperature' => 0.3]);
    $totalTokens += $synthesisResult['tokens'] ?? 0;

    $steps[] = ['step' => 'synthesize', 'model' => $synthesisResult['model'] ?? 'unknown', 'tokens' => $synthesisResult['tokens'] ?? 0];

    // ── Save results ────────────────────────────────────────────
    $report = $synthesisResult['text'];
    $stmt = $db->prepare("UPDATE alfred_research SET
        status = 'complete',
        steps = ?,
        sources = ?,
        result = ?,
        token_count = ?,
        source_count = ?,
        completed_at = NOW()
        WHERE id = ?");
    $stmt->execute([
        json_encode($steps),
        json_encode($allSources),
        $report,
        $totalTokens,
        count($allSources),
        $researchId,
    ]);

    return [
        'report' => $report,
        'sources' => $allSources,
        'steps' => $steps,
        'tokens_used' => $totalTokens,
        'model' => $synthesisResult['model'] ?? 'unknown',
    ];
}

function updateStatus($db, $id, $status) {
    $stmt = $db->prepare("UPDATE alfred_research SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);
}

function updateSteps($db, $id, $steps) {
    $stmt = $db->prepare("UPDATE alfred_research SET steps = ? WHERE id = ?");
    $stmt->execute([json_encode($steps), $id]);
}

// ── Rate Limiting ───────────────────────────────────────────────
function checkResearchRateLimit($clientId) {
    $db = getDB();
    if (!$db || !$clientId) return true;

    $stmt = $db->prepare("SELECT COUNT(*) FROM alfred_research
        WHERE client_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmt->execute([$clientId]);
    return $stmt->fetchColumn() < 10; // max 10 research tasks per hour
}

// ── Router ──────────────────────────────────────────────────────
$action = sanitize($_GET['action'] ?? '', 30);

switch ($action) {

    // ── Start research ──────────────────────────────────────────
    case 'research':
        $clientId = getAuthUser();
        if (!$clientId) {
            jsonResponse(['error' => 'Authentication required'], 401);
        }

        if (!checkResearchRateLimit($clientId)) {
            jsonResponse(['error' => 'Rate limit: max 10 research tasks per hour'], 429);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $query = sanitize($input['query'] ?? '', 1000);
        $mode = in_array($input['mode'] ?? 'standard', ['standard', 'deep', 'quick']) ? $input['mode'] : 'standard';

        if (strlen($query) < 5) {
            jsonResponse(['error' => 'Query must be at least 5 characters'], 400);
        }

        $db = getDB();
        ensureResearchTable();

        $researchId = bin2hex(random_bytes(16));

        $stmt = $db->prepare("INSERT INTO alfred_research (id, client_id, query, mode, status)
            VALUES (?, ?, ?, ?, 'planning')");
        $stmt->execute([$researchId, $clientId, $query, $mode]);

        // Run synchronously for now (BullMQ async in production)
        try {
            $result = runResearch($researchId, $query, $mode, $db);
            jsonResponse([
                'success' => true,
                'research_id' => $researchId,
                'status' => 'complete',
                'report' => $result['report'],
                'sources' => $result['sources'],
                'meta' => [
                    'steps' => count($result['steps']),
                    'sources_analyzed' => count($result['sources']),
                    'tokens_used' => $result['tokens_used'],
                    'model' => $result['model'],
                    'mode' => $mode,
                ],
            ]);
        } catch (Exception $e) {
            updateStatus($db, $researchId, 'failed');
            error_log("Research failed: " . $e->getMessage());
            jsonResponse(['error' => 'Research failed', 'research_id' => $researchId], 500);
        }
        break;

    // ── Check status ────────────────────────────────────────────
    case 'status':
        $id = sanitize($_GET['id'] ?? '', 32);
        if (!$id) jsonResponse(['error' => 'id required'], 400);

        $db = getDB();
        ensureResearchTable();
        $stmt = $db->prepare("SELECT id, status, steps, source_count, token_count, created_at, completed_at FROM alfred_research WHERE id = ?");
        $stmt->execute([$id]);
        $research = $stmt->fetch();

        if (!$research) jsonResponse(['error' => 'Research not found'], 404);

        $research['steps'] = json_decode($research['steps'], true);
        jsonResponse(['success' => true, 'research' => $research]);
        break;

    // ── Get result ──────────────────────────────────────────────
    case 'result':
        $clientId = getAuthUser();
        $id = sanitize($_GET['id'] ?? '', 32);
        if (!$id) jsonResponse(['error' => 'id required'], 400);

        $db = getDB();
        ensureResearchTable();

        $stmt = $db->prepare("SELECT * FROM alfred_research WHERE id = ? AND (client_id = ? OR client_id IS NULL)");
        $stmt->execute([$id, $clientId]);
        $research = $stmt->fetch();

        if (!$research) jsonResponse(['error' => 'Research not found'], 404);

        $research['sources'] = json_decode($research['sources'], true);
        $research['steps'] = json_decode($research['steps'], true);
        jsonResponse(['success' => true, 'research' => $research]);
        break;

    // ── Research history ────────────────────────────────────────
    case 'history':
        $clientId = getAuthUser();
        if (!$clientId) jsonResponse(['error' => 'Authentication required'], 401);

        $db = getDB();
        ensureResearchTable();

        $limit = min(intval($_GET['limit'] ?? 20), 50);
        $stmt = $db->prepare("SELECT id, query, mode, status, source_count, token_count, created_at, completed_at
            FROM alfred_research WHERE client_id = ?
            ORDER BY created_at DESC LIMIT ?");
        dbExecute($stmt, [$clientId, $limit]);

        jsonResponse(['success' => true, 'history' => $stmt->fetchAll()]);
        break;

    default:
        jsonResponse(['error' => 'Unknown action. Use: research, status, result, history'], 400);
}
