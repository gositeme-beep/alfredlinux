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
 *   GET  ?q=...&mode=web|news|deep|code|images|instant
 *   GET  ?q=...&page=1               → Paginated results
 *   POST ?action=suggest&q=...       → Autocomplete suggestions
 *   POST ?action=instant&q=...       → Instant answer (AI)
 *   GET  ?action=trending            → Trending searches (anonymous)
 *   GET  ?action=stats               → Public search stats
 */

define('GOSITEME_API', true);
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

define('RESULTS_PER_PAGE', 10);
define('MAX_QUERY_LENGTH', 500);
define('RATE_LIMIT_SEARCHES', 60);   // per minute
define('RATE_LIMIT_WINDOW', 60);

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

// ── Web Search via DuckDuckGo ─────────────────────────────────
function webSearch($query, $count = 10) {
    $url = 'https://html.duckduckgo.com/html/?q=' . rawurlencode($query);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER     => [
            'User-Agent: Mozilla/5.0 (compatible; AlfredSearch/1.0)',
        ],
    ]);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200 || !$response) return [];

    return parseDDGHtml($response, $count);
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
    $systemPrompt = "You are Alfred, the AI assistant powering Alfred Search on GoSiteMe.com. GoSiteMe is a web hosting, AI website builder, and digital ecosystem platform founded by Danny William Perez. It features: Alfred AI assistant, GoCodeMe (AI code editor/IDE), Pulse (social network), QGSM cryptocurrency, Agent Metaverse, voice cloning, VR games, a self-hosted sovereign search engine, 114,000+ AI agents, and post-quantum security. When asked about GoSiteMe or its products, explain them accurately. For general questions, give a direct, concise answer. Be accurate with facts. If uncertain, say so. Keep answers under 200 words. Format with markdown if helpful. Do NOT make up URLs or sources.";

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

    return $suggestions;
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

// ── Utility ─────────────────────────────────────────────────────
function extractDomain($url) {
    $host = parse_url($url, PHP_URL_HOST);
    return $host ? preg_replace('/^www\./', '', $host) : '';
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

    // Check web index size
    $webIndexSize = 0;
    $webIndex = meili('GET', '/indexes/web/stats', null);
    if ($webIndex && isset($webIndex['numberOfDocuments'])) $webIndexSize = $webIndex['numberOfDocuments'];

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

// Execute search based on mode
$webResults = [];
$newsResults = [];
$knowledgeResults = [];
$crawlerResults = [];
$instantAnswer = null;

switch ($mode) {
    case 'web':
        // Our own sovereign index first, then supplement with DuckDuckGo web search
        $crawlerResults = crawlerSearch($query, RESULTS_PER_PAGE);
        $webResults = webSearch($query, RESULTS_PER_PAGE + 5);
        $knowledgeResults = knowledgeSearch($query, 3);
        if ($understanding['is_question']) {
            $allSnippets = array_merge($crawlerResults, $webResults);
            $context = implode("\n", array_map(fn($r) => $r['title'] . ': ' . $r['snippet'], array_slice($allSnippets, 0, 5)));
            $instantAnswer = getInstantAnswer($query, $context);
        }
        break;

    case 'news':
        $newsResults = newsSearch($query, RESULTS_PER_PAGE);
        break;

    case 'deep':
        // Deep research — return a redirect to the deep research API
        respond([
            'mode'     => 'deep',
            'message'  => 'Deep research initiated. Use /api/deep-research.php for full results.',
            'redirect' => '/api/deep-research.php?action=research',
            'query'    => $query,
        ]);
        break;

    case 'code':
        $crawlerResults = crawlerSearch($query . ' programming', 5);
        $webResults = webSearch($query . ' programming code example', RESULTS_PER_PAGE);
        $knowledgeResults = knowledgeSearch($query, 3);
        break;

    case 'instant':
        $crawlerResults = crawlerSearch($query, 3);
        $webResults = webSearch($query, 3);
        $allSnippets = array_merge($crawlerResults, $webResults);
        $context = implode("\n", array_map(fn($r) => $r['title'] . ': ' . $r['snippet'], $allSnippets));
        $instantAnswer = getInstantAnswer($query, $context);
        break;

    default:
        $crawlerResults = crawlerSearch($query, RESULTS_PER_PAGE);
        $webResults = webSearch($query, RESULTS_PER_PAGE);
        $knowledgeResults = knowledgeSearch($query, 3);
        break;
}

// Merge and rank (crawler results get priority as sovereign results)
$allResults = mergeAndRank(array_merge($crawlerResults, $webResults), $newsResults, $knowledgeResults, $query);

// Paginate
$totalResults = count($allResults);
$offset = ($page - 1) * RESULTS_PER_PAGE;
$pagedResults = array_slice($allResults, $offset, RESULTS_PER_PAGE);

$responseTime = round((microtime(true) - $startTime) * 1000);

// Log anonymously with cost
logSearch($query, $mode, $totalResults, $responseTime, getSearchCost());

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
    'total'         => $totalResults,
    'page'          => $page,
    'pages'         => max(1, ceil($totalResults / RESULTS_PER_PAGE)),
    'response_ms'   => $responseTime,
    'cost_usd'      => getSearchCost(),
    'privacy'       => [
        'tracked'       => false,
        'cookies'       => false,
        'personalized'  => false,
        'ads'           => false,
        'data_shared'   => false,
        'logs_encrypted' => true,
    ],
];

if ($instantAnswer) {
    $response['instant_answer'] = $instantAnswer;
}

respond($response);
