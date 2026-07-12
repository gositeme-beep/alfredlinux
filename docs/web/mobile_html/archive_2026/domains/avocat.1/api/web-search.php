<?php
/**
 * Alfred Web Search API — Sprint 1
 * 
 * Live web search via Jina Reader (free, no API key required for basic use)
 * and optional Brave Search MCP integration.
 * 
 * Endpoints:
 *   ?action=read&url=...     → Fetch and clean a single URL into markdown
 *   ?action=search&q=...     → Search the web and return results
 *   ?action=summarize&url=.. → Fetch URL + summarize with Alfred AI
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// ─── Auth ──────────────────────────────────────────────────────────────
function requireAuth() {
require_once dirname(__DIR__) . '/includes/api-security.php';
    if (empty($_SESSION['logged_in']) || empty($_SESSION['client_id'])) {
        jsonResponse(['error' => 'Authentication required'], 401);
    }
}

function getClientId() {
    return (int) $_SESSION['client_id'];
}

// ─── Rate Limiting ─────────────────────────────────────────────────────
function checkSearchRateLimit() {
    $key = 'search_' . (isset($_SESSION['client_id']) ? $_SESSION['client_id'] : ip2long($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'));
    $file = sys_get_temp_dir() . '/alfred_rate_' . md5($key);
    
    $window = 60;
    $maxRequests = 20;
    
    $data = ['count' => 0, 'start' => time()];
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true) ?: $data;
    }
    
    if (time() - $data['start'] > $window) {
        $data = ['count' => 0, 'start' => time()];
    }
    
    $data['count']++;
    file_put_contents($file, json_encode($data), LOCK_EX);
    
    if ($data['count'] > $maxRequests) {
        jsonResponse(['error' => 'Rate limit exceeded. Max 20 searches per minute.'], 429);
    }
}

// ─── Jina Reader ───────────────────────────────────────────────────────
function fetchWithJina(string $url): array {
    $jinaUrl = 'https://r.jina.ai/' . $url;
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $jinaUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json',
            'X-Return-Format: markdown',
        ],
        CURLOPT_USERAGENT      => 'AlfredAI/2.0 (+https://gositeme.com)',
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'error' => 'Failed to fetch URL: ' . $error];
    }
    
    if ($httpCode !== 200) {
        return ['success' => false, 'error' => "Jina returned HTTP $httpCode"];
    }
    
    $data = json_decode($response, true);
    if ($data && isset($data['data'])) {
        return [
            'success' => true,
            'title'   => $data['data']['title'] ?? '',
            'content' => $data['data']['content'] ?? '',
            'url'     => $data['data']['url'] ?? $url,
            'length'  => strlen($data['data']['content'] ?? ''),
        ];
    }
    
    // Fallback: Jina returned plain markdown
    return [
        'success' => true,
        'title'   => '',
        'content' => $response,
        'url'     => $url,
        'length'  => strlen($response),
    ];
}

// ─── Jina Search ───────────────────────────────────────────────────────
function searchWithJina(string $query): array {
    $searchUrl = 'https://s.jina.ai/' . urlencode($query);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $searchUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json',
        ],
        CURLOPT_USERAGENT      => 'AlfredAI/2.0 (+https://gositeme.com)',
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'error' => 'Search failed: ' . $error];
    }
    
    $data = json_decode($response, true);
    if ($data && isset($data['data'])) {
        $results = [];
        foreach ($data['data'] as $item) {
            $results[] = [
                'title'       => $item['title'] ?? '',
                'url'         => $item['url'] ?? '',
                'description' => $item['description'] ?? '',
                'content'     => substr($item['content'] ?? '', 0, 500),
            ];
        }
        return ['success' => true, 'results' => $results, 'query' => $query];
    }
    
    // Fallback: plain text results
    return ['success' => true, 'results' => [], 'raw' => $response, 'query' => $query];
}

// ─── URL Validation ────────────────────────────────────────────────────
function validateUrl(string $url): string {
    $url = filter_var(trim($url), FILTER_SANITIZE_URL);
    
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        jsonResponse(['error' => 'Invalid URL'], 400);
    }
    
    $parsed = parse_url($url);
    $scheme = strtolower($parsed['scheme'] ?? '');
    
    if (!in_array($scheme, ['http', 'https'])) {
        jsonResponse(['error' => 'Only HTTP/HTTPS URLs are allowed'], 400);
    }
    
    // Block internal/private IPs (SSRF protection)
    $host = $parsed['host'] ?? '';
    if (empty($host)) {
        jsonResponse(['error' => 'URL must have a host'], 400);
    }
    
    $ip = gethostbyname($host);
    if ($ip !== $host) {
        $longIp = ip2long($ip);
        $privateRanges = [
            [ip2long('10.0.0.0'), ip2long('10.255.255.255')],
            [ip2long('172.16.0.0'), ip2long('172.31.255.255')],
            [ip2long('192.168.0.0'), ip2long('192.168.255.255')],
            [ip2long('127.0.0.0'), ip2long('127.255.255.255')],
            [ip2long('169.254.0.0'), ip2long('169.254.255.255')],
            [ip2long('0.0.0.0'), ip2long('0.255.255.255')],
        ];
        foreach ($privateRanges as [$start, $end]) {
            if ($longIp >= $start && $longIp <= $end) {
                jsonResponse(['error' => 'URLs to private/internal networks are not allowed'], 403);
            }
        }
    }
    
    return $url;
}

// ─── Router ────────────────────────────────────────────────────────────
requireAuth();
checkSearchRateLimit();

$action = sanitize($_GET['action'] ?? '', 20);

switch ($action) {
    case 'read':
        $url = validateUrl($_GET['url'] ?? '');
        $result = fetchWithJina($url);
        
        // Log usage
        $db = getDB();
        if ($db) {
            try {
                $stmt = $db->prepare("INSERT INTO alfred_tool_usage (user_id, tool_name, category, execution_time_ms, success, input_summary, output_summary) VALUES (?, 'web_read', 'web_search', 0, ?, ?, ?)");
                $stmt->execute([getClientId(), $result['success'] ? 1 : 0, substr($url, 0, 500), substr($result['title'] ?? '', 0, 500)]);
            } catch (\Exception $e) { /* non-critical */ }
        }
        
        jsonResponse($result);
        break;
        
    case 'search':
        $query = sanitize($_GET['q'] ?? $_POST['q'] ?? '', 500);
        if (empty($query) || strlen($query) < 2) {
            jsonResponse(['error' => 'Search query must be at least 2 characters'], 400);
        }
        
        $result = searchWithJina($query);
        
        $db = getDB();
        if ($db) {
            try {
                $stmt = $db->prepare("INSERT INTO alfred_tool_usage (user_id, tool_name, category, execution_time_ms, success, input_summary, output_summary) VALUES (?, 'web_search', 'web_search', 0, ?, ?, ?)");
                $stmt->execute([getClientId(), $result['success'] ? 1 : 0, substr($query, 0, 500), count($result['results'] ?? []) . ' results']);
            } catch (\Exception $e) { /* non-critical */ }
        }
        
        jsonResponse($result);
        break;
        
    case 'summarize':
        $url = validateUrl($_GET['url'] ?? '');
        $fetched = fetchWithJina($url);
        
        if (!$fetched['success']) {
            jsonResponse($fetched);
        }
        
        // Truncate to first 8000 chars for summarization
        $content = substr($fetched['content'], 0, 8000);
        
        jsonResponse([
            'success'  => true,
            'title'    => $fetched['title'],
            'url'      => $fetched['url'],
            'content'  => $content,
            'length'   => $fetched['length'],
            'truncated' => $fetched['length'] > 8000,
            'note'     => 'Content fetched and cleaned. Pass to Alfred chat for AI summarization.',
        ]);
        break;
        
    default:
        jsonResponse(['error' => 'Invalid action. Valid: read, search, summarize'], 400);
}
