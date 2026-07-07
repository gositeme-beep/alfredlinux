<?php
/**
 * Alfred Search Engine API
 * ────────────────────────
 * Privacy-first, AI-native search engine. Zero tracking. Zero ads.
 * Combines web search, internal knowledge, news feeds, and AI understanding
 * into a unified search experience that respects user sovereignty.
 *
 * What makes it better:
 *   1. ZERO tracking — no cookies, no profiles, no fingerprinting
 *   2. AI-native — understands intent, not just keywords
 *   3. Source transparency — explains WHY each result ranks
 *   4. Multi-mode — web, news, research, code, images in one bar
 *   5. Instant answers + deep research on demand
 *   6. Self-hostable — run your own instance
 *
 * Endpoints:
 *   GET  ?q=...                      → Universal search (default: web)
 *   GET  ?q=...&mode=web|news|code|instant|ecosystem|deep|images
 *   GET  ?q=...&page=1               → Paginated results
 *   POST ?action=suggest&q=...       → Autocomplete suggestions
 *   POST ?action=instant&q=...       → Instant answer (AI)
 *   GET  ?action=trending            → Trending searches (anonymous)
 *   GET  ?action=stats               → Public search stats
 */

define('GOSITEME_API', true);
$GLOBALS['RATE_LIMIT_EXEMPT'] = true;
$GLOBALS['CSRF_EXEMPT'] = true;
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

// ── Zero-Tracking Headers ───────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
// Explicitly tell browsers NOT to track
header('Tk: N');
header('X-Alfred-Privacy: no-tracking, no-cookies, no-profiling');
header('Cache-Control: private, no-store');
// No cookies set — ever
if (headers_sent() === false) {
    header_remove('Set-Cookie');
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ── Configuration ───────────────────────────────────────────────
define('MEILI_HOST', getenv('MEILI_HOST') ?: 'http://localhost:7700');
$meiliKeyFile = getenv('HOME') . '/.local/meilisearch/master-key.txt';
define('MEILI_KEY', getenv('MEILI_KEY') ?: (is_readable($meiliKeyFile) ? trim(file_get_contents($meiliKeyFile)) : ''));
define('OLLAMA_HOST', getenv('OLLAMA_HOST') ?: 'http://localhost:11434');
define('OPENAI_API_KEY', getenv('OPENAI_API_KEY') ?: '');
define('GROQ_API_KEY', getenv('GROQ_API_KEY') ?: '');

define('RESULTS_PER_PAGE', 15);
define('MAX_QUERY_LENGTH', 500);
define('RATE_LIMIT_SEARCHES', 120);   // per minute
define('RATE_LIMIT_WINDOW', 60);
define('DDG_RESULTS_PER_PAGE', 30);  // DDG returns ~30 per page

// ── Rate Limiting (anonymous, IP-based, no tracking) ────────────
function checkRateLimit() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    // Use a hash so we never store the actual IP
    $key = hash('sha256', $ip . date('Y-m-d-H-i'));
    $file = sys_get_temp_dir() . '/alfred_search_' . substr($key, 0, 16);

    $data = ['count' => 0, 'start' => time()];
    if (file_exists($file)) {
        $stored = json_decode(file_get_contents($file), true);
        if ($stored && (time() - $stored['start']) < RATE_LIMIT_WINDOW) {
            $data = $stored;
        }
    }

    $data['count']++;
    file_put_contents($file, json_encode($data), LOCK_EX);

    if ($data['count'] > RATE_LIMIT_SEARCHES) {
        respond(['error' => 'Rate limit exceeded. Try again shortly.', 'retry_after' => RATE_LIMIT_WINDOW], 429);
    }
}

// ── Response Helper ─────────────────────────────────────────────
function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ── Input Sanitization ──────────────────────────────────────────
function cleanQuery($raw) {
    $q = trim($raw);
    $q = strip_tags($q);
    $q = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $q);
    if (mb_strlen($q) > MAX_QUERY_LENGTH) {
        $q = mb_substr($q, 0, MAX_QUERY_LENGTH);
    }
    return $q;
}

// ── Meilisearch Client ─────────────────────────────────────────
function meili($method, $path, $body = null) {
    $ch = curl_init(MEILI_HOST . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_HTTPHEADER     => array_filter([
            'Content-Type: application/json',
            MEILI_KEY ? 'Authorization: Bearer ' . MEILI_KEY : null,
        ]),
    ]);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if (!$response) return null;
    return json_decode($response, true);
}

// ── AI Query Understanding ──────────────────────────────────────
function understandQuery($query) {
    $analysis = [
        'original'   => $query,
        'intent'     => 'search',      // search, question, navigate, calculate, define
        'mode_hint'  => 'web',         // web, news, code, images, instant
        'entities'   => [],
        'refined'    => $query,
        'is_question' => false,
        'category'   => 'general',     // general, tech, science, health, finance, legal, entertainment
    ];

    // Detect question patterns
    if (preg_match('/^(what|who|where|when|why|how|is|are|can|does|do|will|should|which)\b/i', $query)) {
        $analysis['is_question'] = true;
        $analysis['intent'] = 'question';
    }

    // Detect calculation
    if (preg_match('/^[\d\s\+\-\*\/\(\)\.\^%]+$/', $query)) {
        $analysis['intent'] = 'calculate';
        $analysis['mode_hint'] = 'instant';
    }

    // Detect definition requests
    if (preg_match('/^(define|meaning of|what is a?n?\s)/i', $query)) {
        $analysis['intent'] = 'define';
        $analysis['mode_hint'] = 'instant';
    }

    // Detect code searches
    if (preg_match('/\b(function|class|import|require|npm|pip|apt|docker|git|regex|api|sdk|bug|error|exception|stack\s*trace|syntax|compiler|runtime|typescript|javascript|python|rust|golang|java|php|css|html|react|vue|angular|laravel|django|flask|express|node)\b/i', $query)) {
        $analysis['mode_hint'] = 'code';
        $analysis['category'] = 'tech';
    }

    // Detect news intent
    if (preg_match('/\b(today|latest|breaking|news|announced|released|launched|update|2026|2025|yesterday|this week)\b/i', $query)) {
        $analysis['mode_hint'] = 'news';
    }

    if (preg_match('/\b(price|pricing|cost|plans?|billing|subscription|quote)\b/i', $query)) {
        $analysis['mode_hint'] = 'pricing';
    }

    if (preg_match('/\b(docs?|documentation|api|guide|manual|runbook|architecture|deployment|reference)\b/i', $query)) {
        $analysis['mode_hint'] = 'docs';
    }

    if (preg_match('/\b(tools?|directory|designer|creator|builder|generator|templates?)\b/i', $query)) {
        $analysis['mode_hint'] = 'tools';
    }

    if (preg_match('/\b(products?|platform|ecosystem|browser|voice|wallet|hosting|domains?|pulse|gocodeme|alfred(?!\s+search)|apps?)\b/i', $query)) {
        $analysis['mode_hint'] = 'products';
    }

    if (isBrandQuery($query) && in_array($analysis['mode_hint'], ['web', 'products'], true)) {
        $analysis['mode_hint'] = 'ecosystem';
    }

    if ($analysis['mode_hint'] === 'web' && preg_match('/\b(alfred\s+ide|gocodeme|pulse|hosting|domains?|voice|wallet|search)\b/i', $query)) {
        $analysis['mode_hint'] = 'ecosystem';
    }

    // Detect navigation (trying to go to a specific site)
    if (preg_match('/^(go to|open|visit|navigate to)\s/i', $query) || preg_match('/\.(com|org|net|io|dev|ai)\b/i', $query)) {
        $analysis['intent'] = 'navigate';
    }

    // Category detection
    if (preg_match('/\b(health|medical|symptom|disease|treatment|medicine|doctor|hospital|diagnosis|therapy|surgery)\b/i', $query)) {
        $analysis['category'] = 'health';
    }
    if (preg_match('/\b(stock|invest|market|finance|crypto|bitcoin|trading|portfolio|dividend|bond|banking)\b/i', $query)) {
        $analysis['category'] = 'finance';
    }
    if (preg_match('/\b(law|legal|court|attorney|lawsuit|regulation|statute|patent|copyright|trademark)\b/i', $query)) {
        $analysis['category'] = 'legal';
    }
    if (preg_match('/\b(physics|chemistry|biology|quantum|molecule|genome|evolution|astronomy|climate|research|study|journal)\b/i', $query)) {
        $analysis['category'] = 'science';
    }

    return $analysis;
}

// ── Web Search via DuckDuckGo (with real pagination) ─────────
function webSearch($query, $count = 15, $page = 1, $timeFilter = '') {
    // DDG HTML doesn't support reliable GET pagination,
    // so for page 2+ we use query expansion to get different results
    $searchQuery = $query;
    if ($page === 2) {
        $searchQuery = $query . ' detailed information';
    } elseif ($page === 3) {
        $searchQuery = $query . ' guide resource';
    } elseif ($page === 4) {
        $searchQuery = '"' . $query . '"';  // Exact phrase search
    } elseif ($page > 4) {
        $suffixes = ['explained', 'overview', 'history', 'analysis', 'reference', 'documentation'];
        $searchQuery = $query . ' ' . $suffixes[($page - 5) % count($suffixes)];
    }

    $url = 'https://html.duckduckgo.com/html/?q=' . rawurlencode($searchQuery);
    // Time filter: d=day, w=week, m=month, y=year
    if ($timeFilter && in_array($timeFilter, ['d', 'w', 'm', 'y'])) {
        $url .= '&df=' . $timeFilter;
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER     => [
            'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.9',
        ],
    ]);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200 || !$response) return [];

    $results = parseDDGHtml($response, $count);
    // Signal whether we should try more pages
    $GLOBALS['_ddg_has_more'] = count($results) >= 8;
    return $results;
}

function parseDDGHtml($html, $count = 10) {
    $results = [];
    // Match each result block: title link + snippet
    if (!preg_match_all('/<a[^>]*class="result__a"[^>]*href="[^"]*uddg=([^&"]+)[^"]*"[^>]*>(.*?)<\/a>/s', $html, $titleMatches, PREG_SET_ORDER)) {
        return [];
    }
    preg_match_all('/<a[^>]*class="result__snippet"[^>]*>(.*?)<\/a>/s', $html, $snippetMatches, PREG_SET_ORDER);

    foreach ($titleMatches as $i => $m) {
        if (count($results) >= $count) break;
        $url = urldecode($m[1]);
        $title = strip_tags($m[2]);
        $snippet = isset($snippetMatches[$i]) ? strip_tags($snippetMatches[$i][1]) : '';
        if (!$url || !$title) continue;
        $results[] = [
            'title'       => trim($title),
            'url'         => $url,
            'snippet'     => trim($snippet),
            'source'      => extractDomain($url),
            'type'        => 'web',
            'rank_reason' => 'Matched your search query',
        ];
    }
    return $results;
}

// ── News Search (feeds + web) ───────────────────────────────────
function newsSearch($query, $count = 10) {
    $results = [];

    // Search internal feeds database
    $db = getDB();
    if ($db) {
        try {
            $stmt = $db->prepare("SELECT title, url, snippet, source, published_at
                FROM alfred_feed_items
                WHERE MATCH(title, snippet) AGAINST(? IN BOOLEAN MODE)
                ORDER BY published_at DESC LIMIT ?");
            if ($stmt) {
                $stmt->execute([$query, $count]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $results[] = [
                    'title'       => $row['title'],
                    'url'         => $row['url'],
                    'snippet'     => $row['snippet'],
                    'source'      => $row['source'] ?: extractDomain($row['url']),
                    'type'        => 'news',
                    'published'   => $row['published_at'],
                    'rank_reason' => 'From monitored news feeds',
                ];
            }
        }
        } catch (Exception $e) {
            // Feed table may not exist
        }
    }

    // Supplement with web search if not enough
    if (count($results) < $count) {
        $webResults = webSearch($query . ' news latest', $count - count($results));
        foreach ($webResults as &$r) {
            $r['type'] = 'news';
            $r['rank_reason'] = 'Web search for recent news';
        }
        $results = array_merge($results, $webResults);
    }

    return array_slice($results, 0, $count);
}

// ── Internal Knowledge Search (Meilisearch) ─────────────────────
function knowledgeSearch($query, $count = 5) {
    $results = [];

    // Search tools index
    $meiliResults = meili('POST', '/indexes/tools/search', [
        'q'     => $query,
        'limit' => $count,
    ]);
    if ($meiliResults && isset($meiliResults['hits'])) {
        foreach ($meiliResults['hits'] as $hit) {
            $results[] = [
                'title'       => $hit['name'] ?? '',
                'url'         => 'https://gositeme.com/tools/' . ($hit['slug'] ?? ''),
                'snippet'     => $hit['description'] ?? '',
                'source'      => 'Alfred Tools',
                'type'        => 'tool',
                'icon'        => $hit['icon'] ?? '',
                'category'    => $hit['category'] ?? '',
                'rank_reason' => 'Matched in Alfred tool library',
            ];
        }
    }

    // Search articles
    $articleResults = meili('POST', '/indexes/articles/search', [
        'q'     => $query,
        'limit' => min(3, $count),
    ]);
    if ($articleResults && isset($articleResults['hits'])) {
        foreach ($articleResults['hits'] as $hit) {
            $results[] = [
                'title'       => $hit['title'] ?? $hit['name'] ?? '',
                'url'         => 'https://gositeme.com/articles/' . ($hit['slug'] ?? ''),
                'snippet'     => $hit['description'] ?? $hit['excerpt'] ?? '',
                'source'      => 'Alfred Articles',
                'type'        => 'article',
                'rank_reason' => 'From Alfred knowledge base',
            ];
        }
    }

    return $results;
}

// ── Sovereign Web Index Search (Crawler-built index) ────────────
function crawlerSearch($query, $count = 10) {
    $results = [];

    // Search the 'web' index built by our own crawler
    $meiliResults = meili('POST', '/indexes/web/search', [
        'q'      => $query,
        'limit'  => $count,
        'sort'   => ['quality_score:desc'],
        'filter' => 'quality_score >= 0.45',
    ]);

    if ($meiliResults && isset($meiliResults['hits'])) {
        foreach ($meiliResults['hits'] as $hit) {
            $results[] = [
                'title'       => $hit['title'] ?? '',
                'url'         => $hit['url'] ?? '',
                'snippet'     => mb_substr($hit['description'] ?: ($hit['content'] ?? ''), 0, 300),
                'source'      => $hit['domain'] ?? extractDomain($hit['url'] ?? ''),
                'type'        => 'web',
                'quality'     => round($hit['quality_score'] ?? 0.5, 2),
                'rank_reason' => 'From Alfred sovereign web index',
            ];
        }
    }

    // Also check our own MySQL crawler_pages table (fallback if Meilisearch is down)
    if (empty($results)) {
        $db = getDB();
        if ($db) {
            try {
                $stmt = $db->prepare("SELECT url, domain, title, description, content, quality_score
                    FROM crawler_pages
                    WHERE MATCH(title, description, content) AGAINST(? IN BOOLEAN MODE)
                    AND quality_score >= 0.45
                    ORDER BY quality_score DESC
                    LIMIT ?");
                if ($stmt && $stmt->execute([$query, $count])) {
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $results[] = [
                            'title'       => $row['title'],
                            'url'         => $row['url'],
                            'snippet'     => mb_substr($row['description'] ?: $row['content'], 0, 300),
                            'source'      => $row['domain'],
                            'type'        => 'web',
                            'quality'     => round($row['quality_score'], 2),
                            'rank_reason' => 'From Alfred sovereign web index',
                        ];
                    }
                }
            } catch (Exception $e) {
                // Table may not exist yet — crawler hasn't run
            }
        }
    }

    return $results;
}

// ── Cost Tracking ───────────────────────────────────────────────
// Track per-query costs for AI model usage & API calls
$GLOBALS['_search_cost'] = 0.0;

function trackCost($model, $inputTokens, $outputTokens) {
    // Cost per 1K tokens (approximate)
    $rates = [
        'llama-3.3-70b-versatile' => ['in' => 0.00059, 'out' => 0.00079],   // Groq
        'llama3.1'                => ['in' => 0.0,     'out' => 0.0],        // Ollama (self-hosted, free)
        'gpt-4o-mini'             => ['in' => 0.00015, 'out' => 0.0006],     // OpenAI
        'ddg-web-search'          => ['in' => 0.0,     'out' => 0.0],        // DuckDuckGo HTML (free)
        'meilisearch'             => ['in' => 0.0,     'out' => 0.0],        // Self-hosted
    ];
    $rate = $rates[$model] ?? ['in' => 0.0, 'out' => 0.0];
    $cost = ($inputTokens / 1000.0) * $rate['in'] + ($outputTokens / 1000.0) * $rate['out'];
    $GLOBALS['_search_cost'] += $cost;
    return $cost;
}

function getSearchCost() {
    return round($GLOBALS['_search_cost'], 6);
}

// ── AI Instant Answer ───────────────────────────────────────────
function getInstantAnswer($query, $context = '') {
    static $fleetHeadline = null;
    if ($fleetHeadline === null) {
        require_once __DIR__ . '/../includes/db-config.inc.php';
        require_once __DIR__ . '/../includes/fleet-public-stats.inc.php';
        $fleetHeadline = gositeme_fleet_public_stats()['fleet_headline'];
    }
    $systemPrompt = "You are Alfred, the AI assistant powering Alfred Search on GoSiteMe.com. GoSiteMe is a web hosting, AI website builder, and digital ecosystem platform founded by Danny William Perez. It features: Alfred AI assistant, Alfred IDE (AI code editor/IDE), Pulse (social network), QGSM cryptocurrency, Agent Metaverse, voice cloning, VR games, a self-hosted sovereign search engine, {$fleetHeadline} AI agents in the fleet, and post-quantum security. When asked about GoSiteMe or its products, explain them accurately. For general questions, give a direct, concise answer. Be accurate with facts. If uncertain, say so. Keep answers under 200 words. Format with markdown if helpful. Do NOT make up URLs or sources.";

    $userPrompt = $query;
    if ($context) {
        $userPrompt .= "\n\nContext from search results:\n" . mb_substr($context, 0, 2000);
    }

    // Try Groq first (fastest)
    if (GROQ_API_KEY) {
        $answer = callLLM('https://api.groq.com/openai/v1/chat/completions', GROQ_API_KEY, [
            'model'       => 'llama-3.3-70b-versatile',
            'messages'    => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userPrompt],
            ],
            'max_tokens'  => 500,
            'temperature' => 0.3,
        ]);
        if ($answer) return $answer;
    }

    // Try Ollama (self-hosted)
    $ch = curl_init(OLLAMA_HOST . '/api/generate');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            'model'   => 'llama3.1',
            'prompt'  => $systemPrompt . "\n\nQuestion: " . $userPrompt,
            'stream'  => false,
            'options' => ['num_predict' => 500, 'temperature' => 0.3],
        ]),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code === 200) {
        $data = json_decode($response, true);
        if (!empty($data['response'])) return trim($data['response']);
    }

    // Fallback to OpenAI
    if (OPENAI_API_KEY) {
        $answer = callLLM('https://api.openai.com/v1/chat/completions', OPENAI_API_KEY, [
            'model'       => 'gpt-4o-mini',
            'messages'    => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userPrompt],
            ],
            'max_tokens'  => 500,
            'temperature' => 0.3,
        ]);
        if ($answer) return $answer;
    }

    return null;
}

function callLLM($url, $apiKey, $body) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($body),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code === 200) {
        $data = json_decode($response, true);
        // Track cost from usage data
        $model = $body['model'] ?? 'unknown';
        $usage = $data['usage'] ?? [];
        trackCost($model, $usage['prompt_tokens'] ?? 0, $usage['completion_tokens'] ?? 0);
        return trim($data['choices'][0]['message']['content'] ?? '');
    }
    return null;
}

// ── Search Result Merger & Ranker (v2 — BM25-inspired) ──────────
function mergeAndRank($webResults, $newsResults, $knowledgeResults, $query) {
    $all = [];
    $seenUrls = [];
    $brandQuery = isBrandQuery($query);
    $activeMode = $GLOBALS['_alfred_mode'] ?? 'web';

    // Deduplicate by URL
    foreach ([$knowledgeResults, $webResults, $newsResults] as $source) {
        foreach ($source as $result) {
            $url = $result['url'] ?? '';
            $normalized = preg_replace('#^https?://(www\.)?#', '', rtrim($url, '/'));
            if (isset($seenUrls[$normalized])) continue;
            $seenUrls[$normalized] = true;
            $all[] = $result;
        }
    }

    $queryLower = strtolower(trim($query));
    // Split into meaningful words (3+ chars to avoid noise)
    $words = array_filter(preg_split('/\s+/', $queryLower), fn($w) => mb_strlen($w) >= 2);
    $wordCount = count($words);
    $totalDocs = max(count($all), 1);

    // Pre-compute IDF-like weights for each query word
    $wordDocFreq = [];
    foreach ($words as $w) {
        $freq = 0;
        foreach ($all as $r) {
            $text = strtolower(($r['title'] ?? '') . ' ' . ($r['snippet'] ?? ''));
            if (preg_match('/\b' . preg_quote($w, '/') . '/i', $text)) $freq++;
        }
        $wordDocFreq[$w] = $freq;
    }

    foreach ($all as &$result) {
        $score = 0;
        $title = strtolower($result['title'] ?? '');
        $snippet = strtolower($result['snippet'] ?? '');
        $url = strtolower($result['url'] ?? '');

        // ── EXACT PHRASE MATCH (highest signal) ──
        if ($queryLower && strpos($title, $queryLower) !== false) {
            $score += 80; // Exact query in title
        }
        if ($queryLower && strpos($snippet, $queryLower) !== false) {
            $score += 30; // Exact query in snippet
        }

        // ── WORD BOUNDARY MATCHING (prevents "car" matching "scar") ──
        $titleWordHits = 0;
        $snippetWordHits = 0;
        $weightedTitleScore = 0;
        foreach ($words as $w) {
            $escaped = preg_quote($w, '/');
            // IDF weight — rare words score higher
            $df = max($wordDocFreq[$w] ?? 1, 1);
            $idf = log(1 + $totalDocs / $df);

            if (preg_match('/\b' . $escaped . '/i', $title)) {
                $titleWordHits++;
                $weightedTitleScore += 12 * $idf;
                // Position bonus — words earlier in title score more
                $pos = mb_strpos($title, $w);
                if ($pos !== false && $pos < 20) $score += 5;
            }
            if (preg_match('/\b' . $escaped . '/i', $snippet)) {
                $snippetWordHits++;
                $score += 4 * $idf;
            }
            if (preg_match('/\b' . $escaped . '/i', $url)) {
                $score += 8; // URL contains keyword
            }
        }
        $score += $weightedTitleScore;

        // ── COVERAGE BONUS — reward results matching more query words ──
        if ($wordCount > 1) {
            $titleCoverage = $titleWordHits / $wordCount;
            $snippetCoverage = $snippetWordHits / $wordCount;
            $score += $titleCoverage * 25;   // Full coverage in title = +25
            $score += $snippetCoverage * 10; // Full coverage in snippet = +10
        }

        // ── PROXIMITY BONUS — query words close together in title ──
        if ($wordCount >= 2 && $titleWordHits >= 2) {
            $positions = [];
            foreach ($words as $w) {
                $pos = mb_strpos($title, $w);
                if ($pos !== false) $positions[] = $pos;
            }
            if (count($positions) >= 2) {
                sort($positions);
                $span = end($positions) - reset($positions);
                if ($span < 30) $score += 15;      // Very close
                elseif ($span < 60) $score += 8;    // Nearby
            }
        }

        // ── SOURCE TYPE BOOST ──
        $type = $result['type'] ?? 'web';
        if ($type === 'tool')    $score += 12;
        if ($type === 'article') $score += 8;
        if ($type === 'news')    $score += 6;

        // ── QUALITY-WEIGHTED SOVEREIGN BOOST ──
        $rankReason = $result['rank_reason'] ?? '';
        if (stripos($rankReason, 'sovereign') !== false) {
            $quality = $result['quality'] ?? 0.5;
            // Only boost high-quality sovereign results
            if ($quality >= 0.7)      $score += 15 + ($quality * 10);
            elseif ($quality >= 0.5)  $score += 8 + ($quality * 5);
            else                      $score += 3; // Low quality sovereign pages get minimal boost
        }

        // ── FRESHNESS (all results, not just news) ──
        if (!empty($result['published']) || !empty($result['crawled_at'])) {
            $ts = !empty($result['published']) ? strtotime($result['published']) : strtotime($result['crawled_at']);
            if ($ts) {
                $age = time() - $ts;
                if ($age < 3600)        $score += 20;
                elseif ($age < 86400)   $score += 12;
                elseif ($age < 604800)  $score += 6;
                elseif ($age < 2592000) $score += 2;
            }
        }

        // ── DOMAIN AUTHORITY SIGNAL ──
        $domain = strtolower($result['source'] ?? '');
        // Known high-quality domains get a small boost
        $trustedDomains = ['wikipedia.org','github.com','stackoverflow.com','developer.mozilla.org','docs.python.org','w3.org','gositeme.com'];
        foreach ($trustedDomains as $td) {
            if (strpos($domain, $td) !== false) { $score += 5; break; }
        }

        if ($brandQuery) {
            if (strpos($url, 'gositeme.com') !== false) $score += 140;
            if (strpos($url, 'gocodeme') !== false) $score += 70;
            if (strpos($title, 'gositeme') !== false) $score += 80;
            if (strpos($snippet, 'gositeme') !== false) $score += 40;
            if (strpos($title, 'alfred') !== false) $score += 25;
            if (strpos($snippet, 'alfred') !== false) $score += 12;
        }

        $section = $result['section'] ?? '';
        if (in_array($activeMode, ['products', 'docs', 'tools', 'pricing'], true)) {
            if ($section === $activeMode) {
                $score += 140;
            } elseif ($section !== '') {
                $score -= 20;
            }
        }

        // ── PENALTY: very short title or snippet ──
        if (mb_strlen($title) < 10) $score -= 10;
        if (mb_strlen($snippet) < 30) $score -= 5;

        $result['_score'] = max($score, 0);
    }
    unset($result);

    usort($all, function ($a, $b) {
        return ($b['_score'] ?? 0) - ($a['_score'] ?? 0);
    });

    foreach ($all as &$r) {
        unset($r['_score']);
    }

    return $all;
}

// ── Autocomplete Suggestions ────────────────────────────────────
function getSuggestions($query) {
    $suggestions = [];

    if (isBrandQuery($query)) {
        $suggestions = array_merge($suggestions, [
            ['text' => 'GoSiteMe', 'type' => 'brand', 'icon' => 'fas fa-star'],
            ['text' => 'GoSiteMe pricing', 'type' => 'brand', 'icon' => 'fas fa-tags'],
            ['text' => 'Alfred AI', 'type' => 'brand', 'icon' => 'fas fa-brain'],
            ['text' => 'Alfred IDE', 'type' => 'brand', 'icon' => 'fas fa-terminal'],
            ['text' => 'Alfred IDE', 'type' => 'brand', 'icon' => 'fas fa-code'],
            ['text' => 'GoSiteMe tools', 'type' => 'brand', 'icon' => 'fas fa-toolbox'],
        ]);
    }

    // Meilisearch tool suggestions
    $meiliResults = meili('POST', '/indexes/tools/search', [
        'q'     => $query,
        'limit' => 5,
    ]);
    if ($meiliResults && isset($meiliResults['hits'])) {
        foreach ($meiliResults['hits'] as $hit) {
            $suggestions[] = [
                'text' => $hit['name'] ?? '',
                'type' => 'tool',
                'icon' => $hit['icon'] ?? 'fas fa-wrench',
            ];
        }
    }

    // Common query completions from database (limited since queries are encrypted)
    // We show trending queries as suggestions since individual query text is encrypted at rest
    $db = getDB();
    if ($db) {
        try {
            $stmt = $db->query("SELECT query_encrypted, query_hash, COUNT(*) as cnt
                FROM alfred_search_log
                WHERE searched_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY query_hash
                ORDER BY cnt DESC LIMIT 8");
            if ($stmt) {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $decrypted = decryptQuery($row['query_encrypted']);
                    if (stripos($decrypted, $query) === 0) {
                        $suggestions[] = [
                            'text' => $decrypted,
                            'type' => 'trending',
                            'icon' => 'fas fa-fire',
                        ];
                    }
                }
            }
        } catch (Exception $e) {
            // Fallback for legacy table schema
            try {
                $stmt = $db->prepare("SELECT query, COUNT(*) as cnt
                    FROM alfred_search_log
                    WHERE query LIKE CONCAT(?, '%')
                    GROUP BY query
                    ORDER BY cnt DESC LIMIT 5");
                $stmt->execute([$query]);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $suggestions[] = [
                        'text' => $row['query'],
                        'type' => 'recent',
                        'icon' => 'fas fa-clock',
                    ];
                }
            } catch (Exception $e2) {}
        }
    }

    $seen = [];
    $deduped = [];
    foreach ($suggestions as $item) {
        $text = (string)($item['text'] ?? '');
        if ($text === '' || isset($seen[strtolower($text)])) continue;
        $seen[strtolower($text)] = true;
        $deduped[] = $item;
        if (count($deduped) >= 8) break;
    }

    return $deduped;
}

// ── Encrypted Search Logging (no personal data, encrypted at rest) ──
function getSearchEncryptionKey() {
    $keyFile = getenv('HOME') . '/.local/alfred/search-log-key.bin';
    if (is_readable($keyFile)) {
        $key = file_get_contents($keyFile);
        if (strlen($key) === SODIUM_CRYPTO_SECRETBOX_KEYBYTES) return $key;
    }
    // Generate a new key if none exists
    $dir = dirname($keyFile);
    if (!is_dir($dir)) mkdir($dir, 0700, true);
    $key = sodium_crypto_secretbox_keygen();
    file_put_contents($keyFile, $key);
    chmod($keyFile, 0600);
    return $key;
}

function encryptQuery($plaintext) {
    if (!function_exists('sodium_crypto_secretbox')) return $plaintext;
    $key = getSearchEncryptionKey();
    $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $cipher = sodium_crypto_secretbox($plaintext, $nonce, $key);
    return base64_encode($nonce . $cipher);
}

function decryptQuery($encrypted) {
    if (!function_exists('sodium_crypto_secretbox_open')) return $encrypted;
    // If it doesn't look base64-encoded, return as-is (legacy unencrypted entry)
    $decoded = base64_decode($encrypted, true);
    if ($decoded === false || strlen($decoded) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) return $encrypted;
    $key = getSearchEncryptionKey();
    $nonce = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $cipher = substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $plain = sodium_crypto_secretbox_open($cipher, $nonce, $key);
    return $plain !== false ? $plain : $encrypted;
}

function logSearch($query, $mode, $resultCount, $responseTime, $costUsd = 0.0) {
    $db = getDB();
    if (!$db) return;

    // Ensure table exists with encryption & cost columns
    $db->exec("CREATE TABLE IF NOT EXISTS alfred_search_log (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        query_encrypted TEXT NOT NULL,
        query_hash VARCHAR(64) NOT NULL,
        mode VARCHAR(20) DEFAULT 'web',
        result_count INT DEFAULT 0,
        response_ms INT DEFAULT 0,
        cost_usd DECIMAL(10,6) DEFAULT 0.000000,
        searched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_hash (query_hash),
        INDEX idx_time (searched_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Add cost column if missing (for existing tables)
    try {
        $db->exec("ALTER TABLE alfred_search_log ADD COLUMN IF NOT EXISTS cost_usd DECIMAL(10,6) DEFAULT 0.000000 AFTER response_ms");
        $db->exec("ALTER TABLE alfred_search_log ADD COLUMN IF NOT EXISTS query_encrypted TEXT AFTER id");
        $db->exec("ALTER TABLE alfred_search_log ADD COLUMN IF NOT EXISTS query_hash VARCHAR(64) AFTER query_encrypted");
    } catch (Exception $e) { /* columns may already exist */ }

    // Encrypt the query, store hash for trending/suggestions (no plaintext stored)
    $encrypted = encryptQuery($query);
    $queryHash = hash('sha256', strtolower(trim($query)));

    $stmt = $db->prepare("INSERT INTO alfred_search_log (query_encrypted, query_hash, mode, result_count, response_ms, cost_usd) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$encrypted, $queryHash, $mode, $resultCount, $responseTime, $costUsd]);
}

// ── Trending Searches (anonymous aggregation) ───────────────────
function getTrending() {
    $db = getDB();
    if (!$db) return [];

    // Group by query_hash and pick one encrypted query per group
    $stmt = $db->query("SELECT query_encrypted, query_hash, COUNT(*) as searches
        FROM alfred_search_log
        WHERE searched_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        GROUP BY query_hash
        ORDER BY searches DESC
        LIMIT 10");
    if (!$stmt) {
        // Fallback for tables without query_hash column
        $stmt = $db->query("SELECT query, COUNT(*) as searches
            FROM alfred_search_log
            WHERE searched_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY query
            ORDER BY searches DESC
            LIMIT 10");
        if (!$stmt) return [];
        $trending = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $trending[] = [
                'query'    => $row['query'],
                'searches' => (int) $row['searches'],
            ];
        }
        return $trending;
    }

    $trending = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $decrypted = isset($row['query_encrypted']) ? decryptQuery($row['query_encrypted']) : ($row['query'] ?? '');
        $trending[] = [
            'query'    => $decrypted,
            'searches' => (int) $row['searches'],
        ];
    }
    return $trending;
}

// ── Related Searches Generator ──────────────────────────────────
function generateRelatedSearches($query, $understanding) {
    if (isBrandQuery($query)) {
        return [
            'gositeme products',
            'gositeme docs',
            'gositeme tools',
            'gositeme pricing',
            'alfred ai',
            'gocodeme ai ide',
            'alfred ide',
            'gositeme hosting',
            'pulse network',
        ];
    }

    $related = [];
    $words = preg_split('/\s+/', trim($query));

    // Category-based related queries
    $category = $understanding['category'] ?? 'general';
    $suffixes = [
        'general' => ['explained', 'guide', 'examples', 'vs', 'alternatives', 'review'],
        'tech'    => ['tutorial', 'documentation', 'github', 'example code', 'best practices', 'library'],
        'science' => ['research paper', 'theory explained', 'experiments', 'latest findings', 'history'],
        'health'  => ['symptoms', 'treatment', 'causes', 'prevention', 'research'],
        'finance' => ['analysis', 'forecast', 'news', 'comparison', 'performance'],
        'legal'   => ['law explained', 'case study', 'requirements', 'regulations'],
    ];

    $applicable = $suffixes[$category] ?? $suffixes['general'];
    foreach (array_slice($applicable, 0, 4) as $suffix) {
        $related[] = $query . ' ' . $suffix;
    }

    // If multi-word, suggest with/without words
    if (count($words) > 2) {
        // Core phrase (first 2 words)
        $related[] = implode(' ', array_slice($words, 0, 2));
    }
    // Add "what is" variant for non-question queries
    if (!$understanding['is_question'] && count($words) <= 4) {
        $related[] = 'what is ' . $query;
    }

    // Deduplicate and limit
    $related = array_unique($related);
    return array_values(array_slice($related, 0, 8));
}

// ── Utility ─────────────────────────────────────────────────────
function extractDomain($url) {
    $host = parse_url($url, PHP_URL_HOST);
    return $host ? preg_replace('/^www\./', '', $host) : '';
}

function getFirstPartySectionMap(): array {
    return [
        'products' => [
            'title' => 'Products',
            'url' => 'https://gositeme.com/ecosystem.php',
            'snippet' => 'Explore Alfred AI, Alfred IDE, hosting, browser, voice, wallet, and the wider GoSiteMe product stack.',
            'icon' => 'fas fa-cubes',
            'rank_reason' => 'Official GoSiteMe product collection',
            'links' => [
                ['label' => 'Alfred AI', 'url' => 'https://gositeme.com/alfred.php'],
                ['label' => 'GoCodeMe', 'url' => 'https://gositeme.com/gocodeme.php'],
                ['label' => 'Alfred Browser', 'url' => 'https://gositeme.com/alfred-browser.php'],
                ['label' => 'Voice Products', 'url' => 'https://gositeme.com/voice-products.php'],
            ],
        ],
        'docs' => [
            'title' => 'Docs',
            'url' => 'https://gositeme.com/docs/',
            'snippet' => 'Read first-party documentation for setup, API usage, architecture, security, and GoSiteMe operations.',
            'icon' => 'fas fa-book-open',
            'rank_reason' => 'Official GoSiteMe documentation',
            'links' => [
                ['label' => 'Getting Started', 'url' => 'https://gositeme.com/docs/getting-started.php'],
                ['label' => 'API Reference', 'url' => 'https://gositeme.com/docs/api-reference.php'],
                ['label' => 'Deployment Guide', 'url' => 'https://gositeme.com/docs/deployment-guide.md'],
                ['label' => 'Commander Manual', 'url' => 'https://gositeme.com/docs/commander-manual.php'],
            ],
        ],
        'tools' => [
            'title' => 'Tools',
            'url' => 'https://gositeme.com/tools/',
            'snippet' => 'Launch GoSiteMe tools for AI discovery, circuit design, game creation, storefronts, and Alfred workflows.',
            'icon' => 'fas fa-toolbox',
            'rank_reason' => 'Official GoSiteMe tools directory',
            'links' => [
                ['label' => 'AI Directory', 'url' => 'https://gositeme.com/tools/ai-directory.php'],
                ['label' => 'Circuit Designer', 'url' => 'https://gositeme.com/tools/circuit-designer.php'],
                ['label' => 'Game Creator', 'url' => 'https://gositeme.com/tools/game-creator.php'],
                ['label' => 'Game Store', 'url' => 'https://gositeme.com/tools/game-store.php'],
            ],
        ],
        'pricing' => [
            'title' => 'Pricing',
            'url' => 'https://gositeme.com/pricing.php',
            'snippet' => 'Compare plans for hosting, enterprise, white-label, domains, and platform services across GoSiteMe.',
            'icon' => 'fas fa-tags',
            'rank_reason' => 'Official GoSiteMe pricing and plan pages',
            'links' => [
                ['label' => 'Pricing', 'url' => 'https://gositeme.com/pricing.php'],
                ['label' => 'Hosting', 'url' => 'https://gositeme.com/hosting/'],
                ['label' => 'Domains', 'url' => 'https://gositeme.com/domains/'],
                ['label' => 'Enterprise', 'url' => 'https://gositeme.com/enterprise.php'],
            ],
        ],
    ];
}

function getFirstPartyCatalog(): array {
    return [
        [
            'title' => 'GoSiteMe - AI Hosting, Alfred AI, and the GoSiteMe Ecosystem',
            'url' => 'https://gositeme.com/',
            'snippet' => 'GoSiteMe is the platform for AI hosting, Alfred AI, websites, domains, tools, voice products, and the wider ecosystem.',
            'source' => 'gositeme.com',
            'section' => 'products',
            'type' => 'web',
            'rank_reason' => 'Official GoSiteMe home page',
        ],
        [
            'title' => 'Alfred AI',
            'url' => 'https://gositeme.com/alfred.php',
            'snippet' => 'Meet Alfred AI, the assistant powering search, tools, automation, and the GoSiteMe platform.',
            'source' => 'gositeme.com',
            'section' => 'products',
            'type' => 'web',
            'rank_reason' => 'Official GoSiteMe Alfred page',
        ],
        [
            'title' => 'GoCodeMe AI IDE',
            'url' => 'https://gositeme.com/gocodeme.php',
            'snippet' => 'GoCodeMe is the AI IDE and coding environment inside the GoSiteMe ecosystem.',
            'source' => 'gositeme.com',
            'section' => 'products',
            'type' => 'web',
            'rank_reason' => 'Official GoSiteMe coding product page',
        ],
        [
            'title' => 'Alfred Browser',
            'url' => 'https://gositeme.com/alfred-browser.php',
            'snippet' => 'Browse with Alfred Browser for AI-assisted navigation, privacy, and first-party GoSiteMe integration.',
            'source' => 'gositeme.com',
            'section' => 'products',
            'type' => 'web',
            'rank_reason' => 'Official GoSiteMe browser page',
        ],
        [
            'title' => 'Pulse Network',
            'url' => 'https://gositeme.com/pulse.php',
            'snippet' => 'Pulse is the social and collaboration layer inside the GoSiteMe ecosystem.',
            'source' => 'gositeme.com',
            'section' => 'products',
            'type' => 'web',
            'rank_reason' => 'Official GoSiteMe Pulse page',
        ],
        [
            'title' => 'Voice Products',
            'url' => 'https://gositeme.com/voice-products.php',
            'snippet' => 'Explore GoSiteMe voice products, AI phone agents, and Alfred-powered voice experiences.',
            'source' => 'gositeme.com',
            'section' => 'products',
            'type' => 'web',
            'rank_reason' => 'Official GoSiteMe voice page',
        ],
        [
            'title' => 'GoHostMe Hosting',
            'url' => 'https://gositeme.com/gohostme.php',
            'snippet' => 'Managed hosting, infrastructure, and platform services inside the GoSiteMe ecosystem.',
            'source' => 'gositeme.com',
            'section' => 'products',
            'type' => 'web',
            'rank_reason' => 'Official GoSiteMe hosting page',
        ],
        [
            'title' => 'GoSiteMe Wallet',
            'url' => 'https://gositeme.com/wallet.php',
            'snippet' => 'Manage balances, account value, and ecosystem transactions with the GoSiteMe wallet.',
            'source' => 'gositeme.com',
            'section' => 'products',
            'type' => 'web',
            'rank_reason' => 'Official GoSiteMe wallet page',
        ],
        [
            'title' => 'GoSiteMe Documentation',
            'url' => 'https://gositeme.com/docs/',
            'snippet' => 'Read documentation and guides for GoSiteMe products, Alfred AI, and related platform features.',
            'source' => 'gositeme.com',
            'section' => 'docs',
            'type' => 'article',
            'rank_reason' => 'Official GoSiteMe docs',
        ],
        [
            'title' => 'Getting Started',
            'url' => 'https://gositeme.com/docs/getting-started.php',
            'snippet' => 'Start with core GoSiteMe concepts, account setup, and the first steps inside the platform.',
            'source' => 'gositeme.com',
            'section' => 'docs',
            'type' => 'article',
            'rank_reason' => 'Official GoSiteMe getting started guide',
        ],
        [
            'title' => 'API Reference',
            'url' => 'https://gositeme.com/docs/api-reference.php',
            'snippet' => 'Browse API reference material for GoSiteMe services and integrations.',
            'source' => 'gositeme.com',
            'section' => 'docs',
            'type' => 'article',
            'rank_reason' => 'Official GoSiteMe API documentation',
        ],
        [
            'title' => 'Commander Manual',
            'url' => 'https://gositeme.com/docs/commander-manual.php',
            'snippet' => 'Operational documentation for Commander workflows, controls, and internal systems.',
            'source' => 'gositeme.com',
            'section' => 'docs',
            'type' => 'article',
            'rank_reason' => 'Official GoSiteMe operations manual',
        ],
        [
            'title' => 'Deployment Guide',
            'url' => 'https://gositeme.com/docs/deployment-guide.md',
            'snippet' => 'Deployment-oriented documentation for infrastructure, rollout, and environment setup.',
            'source' => 'gositeme.com',
            'section' => 'docs',
            'type' => 'article',
            'rank_reason' => 'Official GoSiteMe deployment documentation',
        ],
        [
            'title' => 'Infrastructure Capabilities',
            'url' => 'https://gositeme.com/docs/infra-capabilities.php',
            'snippet' => 'Review platform infrastructure capabilities, security posture, and service architecture.',
            'source' => 'gositeme.com',
            'section' => 'docs',
            'type' => 'article',
            'rank_reason' => 'Official GoSiteMe infrastructure documentation',
        ],
        [
            'title' => 'GoSiteMe AI Tools',
            'url' => 'https://gositeme.com/tools/',
            'snippet' => 'Explore GoSiteMe and Alfred AI tools for coding, marketing, automation, and business operations.',
            'source' => 'gositeme.com',
            'section' => 'tools',
            'type' => 'tool',
            'rank_reason' => 'Official GoSiteMe tools directory',
        ],
        [
            'title' => 'AI Directory',
            'url' => 'https://gositeme.com/tools/ai-directory.php',
            'snippet' => 'Browse AI tools, utilities, and indexed capabilities inside the GoSiteMe tools directory.',
            'source' => 'gositeme.com',
            'section' => 'tools',
            'type' => 'tool',
            'rank_reason' => 'Official GoSiteMe tool',
        ],
        [
            'title' => 'Circuit Designer',
            'url' => 'https://gositeme.com/tools/circuit-designer.php',
            'snippet' => 'Design circuits and technical layouts with a dedicated GoSiteMe tool.',
            'source' => 'gositeme.com',
            'section' => 'tools',
            'type' => 'tool',
            'rank_reason' => 'Official GoSiteMe tool',
        ],
        [
            'title' => 'Game Creator',
            'url' => 'https://gositeme.com/tools/game-creator.php',
            'snippet' => 'Create, prototype, and ship interactive experiences with the GoSiteMe game creator.',
            'source' => 'gositeme.com',
            'section' => 'tools',
            'type' => 'tool',
            'rank_reason' => 'Official GoSiteMe tool',
        ],
        [
            'title' => 'Game Store',
            'url' => 'https://gositeme.com/tools/game-store.php',
            'snippet' => 'Discover published games and storefront flows inside the GoSiteMe tool ecosystem.',
            'source' => 'gositeme.com',
            'section' => 'tools',
            'type' => 'tool',
            'rank_reason' => 'Official GoSiteMe tool',
        ],
        [
            'title' => 'Alfred Tools',
            'url' => 'https://gositeme.com/alfred-tools.php',
            'snippet' => 'Browse Alfred-connected tools, automation surfaces, and utility flows across the platform.',
            'source' => 'gositeme.com',
            'section' => 'tools',
            'type' => 'tool',
            'rank_reason' => 'Official GoSiteMe tool hub',
        ],
        [
            'title' => 'GoSiteMe Pricing',
            'url' => 'https://gositeme.com/pricing.php',
            'snippet' => 'Browse hosting, AI, and platform pricing across the GoSiteMe ecosystem.',
            'source' => 'gositeme.com',
            'section' => 'pricing',
            'type' => 'web',
            'rank_reason' => 'Official GoSiteMe pricing page',
        ],
        [
            'title' => 'Hosting Plans',
            'url' => 'https://gositeme.com/hosting/',
            'snippet' => 'Compare hosting plans, infrastructure options, and deployment capacity.',
            'source' => 'gositeme.com',
            'section' => 'pricing',
            'type' => 'web',
            'rank_reason' => 'Official GoSiteMe hosting plans',
        ],
        [
            'title' => 'Domains',
            'url' => 'https://gositeme.com/domains/',
            'snippet' => 'Review domain options, registration flows, and naming services offered by GoSiteMe.',
            'source' => 'gositeme.com',
            'section' => 'pricing',
            'type' => 'web',
            'rank_reason' => 'Official GoSiteMe domains page',
        ],
        [
            'title' => 'Enterprise',
            'url' => 'https://gositeme.com/enterprise.php',
            'snippet' => 'Enterprise plans, custom deployments, and high-scale platform options.',
            'source' => 'gositeme.com',
            'section' => 'pricing',
            'type' => 'web',
            'rank_reason' => 'Official GoSiteMe enterprise page',
        ],
        [
            'title' => 'White Label',
            'url' => 'https://gositeme.com/white-label.php',
            'snippet' => 'White-label offerings for partners, agencies, and branded platform deployments.',
            'source' => 'gositeme.com',
            'section' => 'pricing',
            'type' => 'web',
            'rank_reason' => 'Official GoSiteMe white-label page',
        ],
    ];
}

function scoreCatalogEntry(array $entry, string $query): int {
    $q = strtolower(trim($query));
    $haystack = strtolower(($entry['title'] ?? '') . ' ' . ($entry['snippet'] ?? '') . ' ' . ($entry['url'] ?? '') . ' ' . ($entry['section'] ?? ''));
    $score = strpos((string)($entry['url'] ?? ''), 'gositeme.com') !== false ? 50 : 0;

    if ($q === '') {
        return $score;
    }

    if ($q === 'gosite' || $q === 'gositeme') {
        $score += 100;
    }
    if (strpos($haystack, $q) !== false) {
        $score += 70;
    }

    foreach (preg_split('/\s+/', $q) ?: [] as $word) {
        if ($word !== '' && strpos($haystack, $word) !== false) {
            $score += 12;
        }
    }

    return $score;
}

function rankCatalogEntries(array $entries, string $query, int $limit): array {
    foreach ($entries as &$entry) {
        $entry['_score'] = scoreCatalogEntry($entry, $query);
    }
    unset($entry);

    usort($entries, static function ($a, $b) {
        return ($b['_score'] ?? 0) <=> ($a['_score'] ?? 0);
    });

    $entries = array_slice($entries, 0, $limit);
    foreach ($entries as &$entry) {
        unset($entry['_score']);
    }
    unset($entry);

    return $entries;
}

function getFirstPartyResultsBySections(string $query, array $sections, int $limit = 10): array {
    $catalog = array_values(array_filter(getFirstPartyCatalog(), static function ($entry) use ($sections) {
        return in_array($entry['section'] ?? '', $sections, true);
    }));

    return rankCatalogEntries($catalog, $query, $limit);
}

function getFirstPartyCards(string $query, string $mode = 'web', int $limit = 3): array {
    $sectionMap = getFirstPartySectionMap();
    $cardPool = [];

    foreach ($sectionMap as $section => $meta) {
        $score = scoreCatalogEntry([
            'title' => $meta['title'] ?? '',
            'snippet' => $meta['snippet'] ?? '',
            'url' => $meta['url'] ?? '',
            'section' => $section,
        ], $query);

        if ($mode === $section) {
            $score += 120;
        }
        if ($mode === 'ecosystem' && in_array($section, ['products', 'docs', 'tools', 'pricing'], true)) {
            $score += 20;
        }
        if (isBrandQuery($query)) {
            $score += 30;
        }

        $cardPool[] = [
            'section' => $section,
            'title' => $meta['title'],
            'url' => $meta['url'],
            'snippet' => $meta['snippet'],
            'icon' => $meta['icon'],
            'eyebrow' => 'First-Party GoSiteMe',
            'links' => $meta['links'],
            '_score' => $score,
        ];
    }

    usort($cardPool, static function ($a, $b) {
        return ($b['_score'] ?? 0) <=> ($a['_score'] ?? 0);
    });

    $cardPool = array_slice($cardPool, 0, $limit);
    foreach ($cardPool as &$card) {
        unset($card['_score']);
    }
    unset($card);

    return $cardPool;
}

function isBrandQuery(string $query): bool {
    $q = strtolower(trim($query));
    if ($q === '') return false;

    $compact = preg_replace('/[^a-z0-9]+/', '', $q);
    if (in_array($compact, ['gosite', 'gositeme', 'gositemecom', 'gocodeme', 'alfred'], true)) {
        return true;
    }

    foreach (preg_split('/\s+/', $q) ?: [] as $token) {
        if ($token === '') continue;
        if (strpos($token, 'gosite') === 0 || strpos($token, 'gositeme') === 0 || strpos($token, 'gocodeme') === 0 || strpos($token, 'alfred') === 0) {
            return true;
        }
    }

    return false;
}

function getBrandFirstPartyResults(string $query, int $limit = 4): array {
    $results = getFirstPartyResultsBySections($query, ['products', 'docs', 'tools', 'pricing'], $limit);

    if ($limit >= 6) {
        $results[] = [
            'title' => 'Alfred Search',
            'url' => 'https://gositeme.com/search',
            'snippet' => 'Search the sovereign web with Alfred Search, built by GoSiteMe.',
            'source' => 'gositeme.com',
            'section' => 'products',
            'type' => 'web',
            'rank_reason' => 'Official GoSiteMe search experience',
        ];
        $results = rankCatalogEntries($results, $query, $limit);
    }

    return $results;
}

function balanceBrandResults(array $results, int $firstPartyCap = 5): array {
    $firstParty = [];
    $external = [];

    foreach ($results as $result) {
        $url = strtolower((string)($result['url'] ?? ''));
        if (strpos($url, 'gositeme.com') !== false) {
            $firstParty[] = $result;
        } else {
            $external[] = $result;
        }
    }

    return array_merge(
        array_slice($firstParty, 0, $firstPartyCap),
        $external,
        array_slice($firstParty, $firstPartyCap)
    );
}

function getEcosystemResults(string $query, int $limit = 12): array {
    $results = getFirstPartyResultsBySections($query, ['products', 'docs', 'tools', 'pricing'], 12);
    $knowledge = knowledgeSearch($query, max(3, min(8, $limit)));

    $extras = [
        [
            'title' => 'Alfred IDE Init',
            'url' => 'https://gositeme.com/alfred-ide-init.php',
            'snippet' => 'Enter Alfred IDE for coding, chat, automation, and AI-assisted development inside the GoSiteMe ecosystem.',
            'source' => 'gositeme.com',
            'section' => 'products',
            'type' => 'web',
            'rank_reason' => 'Official GoSiteMe IDE page',
        ],
    ];

    $all = array_merge($results, $extras, $knowledge);
    $seen = [];
    $ranked = [];
    $q = strtolower(trim($query));

    foreach ($all as $item) {
        $url = (string)($item['url'] ?? '');
        if ($url === '' || isset($seen[$url])) continue;
        $seen[$url] = true;
        $haystack = strtolower(($item['title'] ?? '') . ' ' . ($item['snippet'] ?? '') . ' ' . $url);
        $score = strpos($url, 'gositeme.com') !== false ? 80 : 20;
        if ($q !== '' && strpos($haystack, $q) !== false) $score += 60;
        foreach (preg_split('/\s+/', $q) ?: [] as $word) {
            if ($word !== '' && strpos($haystack, $word) !== false) $score += 10;
        }
        $item['_score'] = $score;
        $ranked[] = $item;
    }

    usort($ranked, static function ($a, $b) {
        return ($b['_score'] ?? 0) <=> ($a['_score'] ?? 0);
    });

    $ranked = array_slice($ranked, 0, $limit);
    foreach ($ranked as &$item) {
        unset($item['_score']);
    }
    unset($item);

    return $ranked;
}

function getBrandInstantAnswer(string $query): ?string {
    $q = strtolower(trim($query));
    if (!isBrandQuery($q)) {
        return null;
    }

    if (strpos($q, 'alfred ide') !== false || strpos($q, 'ide') !== false || strpos($q, 'gocodeme') !== false) {
        return "**GoCodeMe / Alfred IDE** is GoSiteMe's AI coding environment. It combines VS Code style editing, Alfred chat, tool use, automation, and GoSiteMe account integration inside one workspace.";
    }
    if (strpos($q, 'pulse') !== false) {
        return "**Pulse** is the GoSiteMe social and collaboration layer. It connects messaging, team interaction, shared spaces, and AI-assisted communication inside the ecosystem.";
    }
    if (strpos($q, 'hosting') !== false || strpos($q, 'domain') !== false || strpos($q, 'price') !== false || strpos($q, 'pricing') !== false) {
        return "**GoSiteMe** is an AI-first hosting and product ecosystem. It offers hosting, domains, Alfred AI, GoCodeMe, voice products, tools, and related platform services from one account.";
    }
    return "**GoSiteMe** is the main platform behind Alfred AI, GoCodeMe, Alfred Search, hosting, domains, AI tools, and voice products. Use **Ecosystem** mode for first-party GoSiteMe results or **Web** mode for the broader web.";
}

// ═══════════════════════════════════════════════════════════════
//  ROUTER
// ═══════════════════════════════════════════════════════════════
checkRateLimit();

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$query  = cleanQuery($_GET['q'] ?? $_POST['q'] ?? '');
$mode   = $_GET['mode'] ?? 'auto';
$page   = max(1, (int) ($_GET['page'] ?? 1));

// ── Static endpoints ────────────────────────────────────────────
if ($action === 'trending') {
    respond([
        'trending' => getTrending(),
        'privacy'  => 'Aggregated anonymously. No personal data collected.',
    ]);
}

if ($action === 'stats') {
    $db = getDB();
    $total = 0;
    $todayCount = 0;
    if ($db) {
        try {
            $row = $db->query("SELECT COUNT(*) as c FROM alfred_search_log")->fetch(PDO::FETCH_ASSOC);
            $total = $row['c'] ?? 0;
            $row = $db->query("SELECT COUNT(*) as c FROM alfred_search_log WHERE searched_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch(PDO::FETCH_ASSOC);
            $todayCount = $row['c'] ?? 0;
        } catch (Exception $e) { /* table may not exist yet */ }
    }

    // Check service health
    $meiliOk = false;
    $meiliHealth = meili('GET', '/health', null);
    if ($meiliHealth && isset($meiliHealth['status']) && $meiliHealth['status'] === 'available') $meiliOk = true;

    // Total pages crawled (from DB — includes all pages, not just quality-filtered ones in Meilisearch)
    $webIndexSize = 0;
    if ($db) {
        try {
            $row = $db->query("SELECT COUNT(*) as c FROM crawler_pages_v2")->fetch(PDO::FETCH_ASSOC);
            $webIndexSize = (int)($row['c'] ?? 0);
        } catch (Exception $e) { /* table may not exist */ }
    }
    // Fallback to meilisearch if DB query failed
    if ($webIndexSize === 0) {
        $webIndex = meili('GET', '/indexes/web/stats', null);
        if ($webIndex && isset($webIndex['numberOfDocuments'])) $webIndexSize = $webIndex['numberOfDocuments'];
    }

    // Cost tracking
    $totalCost = 0;
    $todayCost = 0;
    if ($db) {
        try {
            $row = $db->query("SELECT COALESCE(SUM(cost_usd),0) as c FROM alfred_search_log")->fetch(PDO::FETCH_ASSOC);
            $totalCost = round((float)($row['c'] ?? 0), 4);
            $row = $db->query("SELECT COALESCE(SUM(cost_usd),0) as c FROM alfred_search_log WHERE searched_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch(PDO::FETCH_ASSOC);
            $todayCost = round((float)($row['c'] ?? 0), 4);
        } catch (Exception $e) { /* cost column may not exist yet */ }
    }

    respond([
        'total_searches'      => (int) $total,
        'searches_today'      => (int) $todayCount,
        'tracking'            => 'none',
        'cookies'             => 'none',
        'ads'                 => 'none',
        'data_sold'           => 'never',
        'engine'              => 'Alfred Search',
        'version'             => '1.0.0',
        'meilisearch'         => $meiliOk ? 'online' : 'offline',
        'web_index_documents' => $webIndexSize,
        'cost_total_usd'      => $totalCost,
        'cost_today_usd'      => $todayCost,
        'logs_encrypted'      => true,
        'features'            => [
            'sovereign_web_index'  => true,
            'ai_instant_answers'   => true,
            'deep_research'        => true,
            'multi_mode_search'    => true,
            'zero_tracking'        => true,
            'self_hostable'        => true,
        ],
    ]);
}

if ($action === 'suggest' && $query) {
    respond(['suggestions' => getSuggestions($query)]);
}

// ── Voice Search (Whisper transcription → search) ───────────────
if ($action === 'voice' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Accept audio file upload, transcribe with Whisper, return query text
    if (!isset($_FILES['audio']) || $_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
        respond(['error' => 'Audio file required. Upload as multipart/form-data with field "audio"'], 400);
    }

    $audioFile = $_FILES['audio']['tmp_name'];
    $audioSize = $_FILES['audio']['size'];

    if ($audioSize > 25 * 1024 * 1024) {
        respond(['error' => 'Audio file too large (max 25MB)'], 400);
    }

    $transcribed = null;

    // Try Groq Whisper first (fastest, free tier)
    if (GROQ_API_KEY) {
        $ch = curl_init('https://api.groq.com/openai/v1/audio/transcriptions');
        $cfile = new CURLFile($audioFile, $_FILES['audio']['type'] ?: 'audio/webm', 'audio.webm');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => ['file' => $cfile, 'model' => 'whisper-large-v3', 'response_format' => 'json'],
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . GROQ_API_KEY],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
        ]);
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code === 200) {
            $data = json_decode($response, true);
            $transcribed = trim($data['text'] ?? '');
            trackCost('whisper-large-v3', 0, 0); // Groq whisper is free
        }
    }

    // Fallback to OpenAI Whisper
    if (!$transcribed && OPENAI_API_KEY) {
        $ch = curl_init('https://api.openai.com/v1/audio/transcriptions');
        $cfile = new CURLFile($audioFile, $_FILES['audio']['type'] ?: 'audio/webm', 'audio.webm');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => ['file' => $cfile, 'model' => 'whisper-1', 'response_format' => 'json'],
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . OPENAI_API_KEY],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
        ]);
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code === 200) {
            $data = json_decode($response, true);
            $transcribed = trim($data['text'] ?? '');
            trackCost('whisper-1', 0, 0);
        }
    }

    if (!$transcribed) {
        respond(['error' => 'Voice transcription failed. Please try again or type your query.'], 500);
    }

    respond([
        'transcription' => $transcribed,
        'confidence'    => 1.0,
        'action'        => 'Search for: ' . $transcribed,
    ]);
}

// ── Main Search ─────────────────────────────────────────────────
if (empty($query)) {
    respond(['error' => 'Search query required. Use ?q=your+search'], 400);
}

$startTime = microtime(true);

// AI query understanding
$understanding = understandQuery($query);
if ($mode === 'auto') {
    $mode = $understanding['mode_hint'];
}

$GLOBALS['_alfred_mode'] = $mode;

// Execute search based on mode
$webResults = [];
$newsResults = [];
$knowledgeResults = [];
$crawlerResults = [];
$instantAnswer = null;

// Time filter from query params
$timeFilter = $_GET['time'] ?? '';
if ($timeFilter && !in_array($timeFilter, ['d', 'w', 'm', 'y'])) $timeFilter = '';
$GLOBALS['_ddg_has_more'] = false;

switch ($mode) {
    case 'ecosystem':
        $knowledgeResults = getEcosystemResults($query, RESULTS_PER_PAGE);
        $instantAnswer = getBrandInstantAnswer($query);
        break;

    case 'products':
        $knowledgeResults = array_merge(
            getFirstPartyResultsBySections($query, ['products'], RESULTS_PER_PAGE),
            knowledgeSearch($query, 4)
        );
        if ($page <= 1) {
            $crawlerResults = crawlerSearch($query, 4);
        }
        $instantAnswer = getBrandInstantAnswer($query);
        break;

    case 'docs':
        $knowledgeResults = array_merge(
            getFirstPartyResultsBySections($query, ['docs'], RESULTS_PER_PAGE),
            knowledgeSearch($query, 4)
        );
        if ($page <= 1) {
            $crawlerResults = crawlerSearch('documentation ' . $query, 4);
        }
        break;

    case 'tools':
        $knowledgeResults = array_merge(
            getFirstPartyResultsBySections($query, ['tools'], RESULTS_PER_PAGE),
            knowledgeSearch($query, 8)
        );
        if ($page <= 1) {
            $crawlerResults = crawlerSearch('tools ' . $query, 4);
        }
        break;

    case 'pricing':
        $knowledgeResults = array_merge(
            getFirstPartyResultsBySections($query, ['pricing'], RESULTS_PER_PAGE),
            getFirstPartyResultsBySections($query, ['products'], 3)
        );
        $instantAnswer = getBrandInstantAnswer($query);
        break;

    case 'web':
        // Page 1: sovereign index + knowledge + DDG. Page 2+: DDG only (sovereign is small)
        if ($page <= 1) {
            $crawlerResults = crawlerSearch($query, RESULTS_PER_PAGE);
            $knowledgeResults = knowledgeSearch($query, 3);
            if (isBrandQuery($query)) {
                $knowledgeResults = array_merge(getBrandFirstPartyResults($query, 4), $knowledgeResults);
            }
        }
        $webResults = webSearch($query, RESULTS_PER_PAGE + 10, $page, $timeFilter);
        if ($understanding['is_question'] && $page <= 1) {
            $allSnippets = array_merge($crawlerResults, $webResults);
            $context = implode("\n", array_map(fn($r) => $r['title'] . ': ' . $r['snippet'], array_slice($allSnippets, 0, 5)));
            $instantAnswer = getInstantAnswer($query, $context);
        }
        break;

    case 'news':
        $newsResults = newsSearch($query, RESULTS_PER_PAGE);
        $webResults = webSearch($query . ' news latest', RESULTS_PER_PAGE, $page, $timeFilter);
        break;

    case 'deep':
        respond([
            'mode'     => 'deep',
            'message'  => 'Deep research initiated. Use /api/deep-research.php for full results.',
            'redirect' => '/api/deep-research.php?action=research',
            'query'    => $query,
        ]);
        break;

    case 'code':
        if ($page <= 1) {
            $crawlerResults = crawlerSearch($query . ' programming', 5);
            $knowledgeResults = knowledgeSearch($query, 3);
        }
        $webResults = webSearch($query . ' programming code example', RESULTS_PER_PAGE, $page, $timeFilter);
        break;

    case 'instant':
        $crawlerResults = crawlerSearch($query, 3);
        $webResults = webSearch($query, 5, 1, $timeFilter);
        $allSnippets = array_merge($crawlerResults, $webResults);
        $context = implode("\n", array_map(fn($r) => $r['title'] . ': ' . $r['snippet'], $allSnippets));
        $instantAnswer = getBrandInstantAnswer($query) ?: getInstantAnswer($query, $context);
        break;

    default:
        if ($page <= 1) {
            $crawlerResults = crawlerSearch($query, RESULTS_PER_PAGE);
            $knowledgeResults = knowledgeSearch($query, 3);
            if (isBrandQuery($query)) {
                $knowledgeResults = array_merge(getBrandFirstPartyResults($query, 4), $knowledgeResults);
            }
        }
        $webResults = webSearch($query, RESULTS_PER_PAGE, $page, $timeFilter);
        break;
}

// Merge and rank (crawler results get priority as sovereign results)
$allResults = mergeAndRank(array_merge($crawlerResults, $webResults), $newsResults, $knowledgeResults, $query);
if (isBrandQuery($query) && $mode !== 'ecosystem') {
    $allResults = balanceBrandResults($allResults, 5);
}

// No client-side pagination — backend already fetches the right page from DDG
$pagedResults = array_slice($allResults, 0, RESULTS_PER_PAGE);
// Cap at 8 pages to avoid infinite query expansion
$hasMore = ($GLOBALS['_ddg_has_more'] || count($allResults) > RESULTS_PER_PAGE) && $page < 8;

$responseTime = round((microtime(true) - $startTime) * 1000);

// Log anonymously with cost
logSearch($query, $mode, count($allResults), $responseTime, getSearchCost());

// Generate related searches
$relatedSearches = generateRelatedSearches($query, $understanding);

// Build response
$response = [
    'query'         => $query,
    'mode'          => $mode,
    'understanding' => [
        'intent'      => $understanding['intent'],
        'is_question' => $understanding['is_question'],
        'category'    => $understanding['category'] ?? 'general',
    ],
    'results'       => $pagedResults,
    'total'         => count($allResults),
    'page'          => $page,
    'has_more'      => $hasMore,
    'response_ms'   => $responseTime,
    'cost_usd'      => getSearchCost(),
    'related'       => $relatedSearches,
    'time_filter'   => $timeFilter,
    'privacy'       => [
        'tracked'       => false,
        'cookies'       => false,
        'personalized'  => false,
        'ads'           => false,
        'data_shared'   => false,
        'logs_encrypted' => true,
    ],
];

if ($page <= 1 && (isBrandQuery($query) || in_array($mode, ['ecosystem', 'products', 'docs', 'tools', 'pricing'], true))) {
    $response['first_party_cards'] = getFirstPartyCards($query, $mode, 3);
}

if ($instantAnswer) {
    $response['instant_answer'] = $instantAnswer;
}

respond($response);
