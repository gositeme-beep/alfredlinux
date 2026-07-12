<?php
/**
 * Alfred Vertical Domain APIs
 * ───────────────────────────
 * Specialized APIs for domain-specific tasks.
 *
 * Domains:
 *   legal       → CanLII (Canadian law), CourtListener, legal analysis
 *   academic    → Semantic Scholar, arXiv, citation analysis
 *   translate   → DeepL, LibreTranslate (self-hosted)
 *   math        → Wolfram Alpha, computation
 *   finance     → Market data, currency conversion
 *   weather     → OpenMeteo (free, no API key)
 *
 * Endpoints:
 *   POST ?domain=legal&action=search          → Search case law
 *   POST ?domain=legal&action=analyze         → Analyze legal document
 *   POST ?domain=academic&action=search       → Search papers
 *   POST ?domain=academic&action=cite          → Generate citation
 *   POST ?domain=translate&action=translate    → Translate text
 *   POST ?domain=translate&action=detect       → Detect language
 *   POST ?domain=math&action=compute           → Compute expression
 *   POST ?domain=math&action=plot              → Generate plot
 *   GET  ?domain=finance&action=rates          → Exchange rates
 *   GET  ?domain=weather&action=forecast       → Weather forecast
 *   GET  ?action=domains                       → List all domains
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// API Keys
define('DEEPL_API_KEY', getenv('DEEPL_API_KEY') ?: '');
define('WOLFRAM_APP_ID', getenv('WOLFRAM_APP_ID') ?: '');
define('CANLII_API_KEY', getenv('CANLII_API_KEY') ?: '');

function getAuthUser() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return $_SESSION['client_id'] ?? null;
}

// ── Rate Limiting ───────────────────────────────────────────────
function rateLimitKey($domain) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = "verticals:{$domain}:{$ip}";
    // Simple in-memory rate limit (could use Redis in production)
    $db = getDB();
    if (!$db) return true;

    try {
        $db->exec("CREATE TABLE IF NOT EXISTS rate_limits (
            lookup_key VARCHAR(100) PRIMARY KEY,
            hits INT DEFAULT 1,
            window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=MEMORY");

        $stmt = $db->prepare("SELECT hits, window_start FROM rate_limits WHERE lookup_key = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();

        if (!$row || strtotime($row['window_start']) < time() - 60) {
            $db->prepare("REPLACE INTO rate_limits (lookup_key, hits, window_start) VALUES (?, 1, NOW())")->execute([$key]);
            return true;
        }

        if ($row['hits'] >= 30) return false;

        $db->prepare("UPDATE rate_limits SET hits = hits + 1 WHERE lookup_key = ?")->execute([$key]);
        return true;
    } catch (Exception $e) {
        return true; // Allow on error
    }
}

// ══════════════════════════════════════════════════════════════════
// ── LEGAL DOMAIN ────────────────────────────────────────────────
// ══════════════════════════════════════════════════════════════════

function legalSearch($query, $jurisdiction = 'ca', $limit = 10) {
    // CanLII API (Canadian law)
    if (CANLII_API_KEY && ($jurisdiction === 'ca' || $jurisdiction === 'all')) {
        $encoded = urlencode($query);
        $url = "https://api.canlii.org/v1/search/legislation?api_key=" . CANLII_API_KEY . "&text={$encoded}&resultCount={$limit}";

        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15]);
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200) {
            $data = json_decode($response, true);
            return [
                'results' => array_map(function($r) {
                    return [
                        'title' => $r['title'] ?? '',
                        'citation' => $r['citation'] ?? '',
                        'url' => $r['url'] ?? '',
                        'database' => $r['databaseId'] ?? 'canlii',
                        'type' => $r['type'] ?? 'legislation',
                    ];
                }, $data['results'] ?? []),
                'total' => $data['totalResults'] ?? 0,
                'source' => 'canlii',
            ];
        }
    }

    // CourtListener (US law - free, no API key)
    $encoded = urlencode($query);
    $ch = curl_init("https://www.courtlistener.com/api/rest/v3/search/?q={$encoded}&type=o&page_size={$limit}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code === 200) {
        $data = json_decode($response, true);
        return [
            'results' => array_map(function($r) {
                return [
                    'title' => $r['caseName'] ?? '',
                    'citation' => $r['citation'] ?? [$r['sibling_ids'] ?? ''],
                    'url' => 'https://www.courtlistener.com' . ($r['absolute_url'] ?? ''),
                    'court' => $r['court'] ?? '',
                    'date' => $r['dateFiled'] ?? '',
                    'source' => 'courtlistener',
                ];
            }, $data['results'] ?? []),
            'total' => $data['count'] ?? 0,
            'source' => 'courtlistener',
        ];
    }

    return ['results' => [], 'total' => 0, 'source' => 'none'];
}

// ══════════════════════════════════════════════════════════════════
// ── ACADEMIC DOMAIN ─────────────────────────────────────────────
// ══════════════════════════════════════════════════════════════════

function academicSearch($query, $limit = 10, $fields = null) {
    // Semantic Scholar API (free, no key needed for basic)
    $encoded = urlencode($query);
    $fieldStr = $fields ?: 'title,authors,year,abstract,url,citationCount,venue,externalIds';
    $url = "https://api.semanticscholar.org/graph/v1/paper/search?query={$encoded}&limit={$limit}&fields={$fieldStr}";

    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15]);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code === 200) {
        $data = json_decode($response, true);
        return [
            'papers' => array_map(function($p) {
                return [
                    'paperId' => $p['paperId'] ?? '',
                    'title' => $p['title'] ?? '',
                    'authors' => array_map(fn($a) => $a['name'] ?? '', $p['authors'] ?? []),
                    'year' => $p['year'] ?? null,
                    'abstract' => $p['abstract'] ?? '',
                    'url' => $p['url'] ?? '',
                    'citations' => $p['citationCount'] ?? 0,
                    'venue' => $p['venue'] ?? '',
                    'doi' => $p['externalIds']['DOI'] ?? null,
                    'arxiv' => $p['externalIds']['ArXiv'] ?? null,
                ];
            }, $data['data'] ?? []),
            'total' => $data['total'] ?? 0,
            'source' => 'semantic_scholar',
        ];
    }

    return ['papers' => [], 'total' => 0, 'source' => 'error'];
}

function generateCitation($paperId, $style = 'apa') {
    // Fetch paper details
    $url = "https://api.semanticscholar.org/graph/v1/paper/{$paperId}?fields=title,authors,year,venue,externalIds,publicationDate";
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
    $response = curl_exec($ch);
    curl_close($ch);

    $paper = json_decode($response, true);
    if (!$paper || isset($paper['error'])) return ['error' => 'Paper not found'];

    $authors = array_map(fn($a) => $a['name'] ?? '', $paper['authors'] ?? []);
    $year = $paper['year'] ?? date('Y');
    $title = $paper['title'] ?? 'Untitled';
    $venue = $paper['venue'] ?? '';
    $doi = $paper['externalIds']['DOI'] ?? '';

    switch ($style) {
        case 'apa':
            $authorStr = count($authors) > 2
                ? $authors[0] . ' et al.'
                : implode(' & ', $authors);
            $citation = "{$authorStr} ({$year}). {$title}. " . ($venue ? "*{$venue}*. " : '') . ($doi ? "https://doi.org/{$doi}" : '');
            break;

        case 'mla':
            $authorStr = implode(', ', array_slice($authors, 0, 3));
            if (count($authors) > 3) $authorStr .= ', et al.';
            $citation = "{$authorStr}. \"{$title}.\" " . ($venue ? "*{$venue}*, " : '') . "{$year}.";
            break;

        case 'bibtex':
            $key = strtolower(preg_replace('/[^a-z0-9]/i', '', $authors[0] ?? 'unknown')) . $year;
            $citation = "@article{{$key},\n  title={{$title}},\n  author={" . implode(' and ', $authors) . "},\n  year={{$year}}" . ($venue ? ",\n  journal={{$venue}}" : '') . ($doi ? ",\n  doi={{$doi}}" : '') . "\n}";
            break;

        default:
            $citation = implode(', ', $authors) . " ({$year}). {$title}." . ($venue ? " {$venue}." : '');
    }

    return ['citation' => $citation, 'style' => $style, 'paper' => ['title' => $title, 'authors' => $authors, 'year' => $year]];
}

// ══════════════════════════════════════════════════════════════════
// ── TRANSLATION DOMAIN ──────────────────────────────────────────
// ══════════════════════════════════════════════════════════════════

function translateText($text, $targetLang, $sourceLang = null) {
    // Try DeepL first (higher quality)
    if (DEEPL_API_KEY) {
        $isFreePlan = str_contains(DEEPL_API_KEY, ':fx');
        $baseUrl = $isFreePlan ? 'https://api-free.deepl.com' : 'https://api.deepl.com';

        $postData = [
            'text' => [substr($text, 0, 10000)],
            'target_lang' => strtoupper($targetLang),
        ];
        if ($sourceLang) $postData['source_lang'] = strtoupper($sourceLang);

        $ch = curl_init($baseUrl . '/v2/translate');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($postData),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: DeepL-Auth-Key ' . DEEPL_API_KEY,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200) {
            $data = json_decode($response, true);
            $translation = $data['translations'][0] ?? null;
            if ($translation) {
                return [
                    'text' => $translation['text'],
                    'detected_source' => $translation['detected_source_language'] ?? $sourceLang,
                    'target' => $targetLang,
                    'provider' => 'deepl',
                ];
            }
        }
    }

    // Fallback: LibreTranslate (self-hosted or free instance)
    $libreUrl = getenv('LIBRETRANSLATE_URL') ?: 'https://libretranslate.com';
    $ch = curl_init($libreUrl . '/translate');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'q' => substr($text, 0, 5000),
            'source' => $sourceLang ?: 'auto',
            'target' => $targetLang,
            'format' => 'text',
        ]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
    ]);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code === 200) {
        $data = json_decode($response, true);
        return [
            'text' => $data['translatedText'] ?? '',
            'detected_source' => $data['detectedLanguage']['language'] ?? $sourceLang,
            'target' => $targetLang,
            'provider' => 'libretranslate',
        ];
    }

    return ['error' => 'Translation failed'];
}

function detectLanguage($text) {
    if (DEEPL_API_KEY) {
        // DeepL doesn't have a standalone detect endpoint, so translate a snippet
        $result = translateText(substr($text, 0, 200), 'EN');
        if (isset($result['detected_source'])) {
            return ['language' => strtolower($result['detected_source']), 'provider' => 'deepl'];
        }
    }

    // LibreTranslate detect
    $libreUrl = getenv('LIBRETRANSLATE_URL') ?: 'https://libretranslate.com';
    $ch = curl_init($libreUrl . '/detect');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['q' => substr($text, 0, 500)]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    if (is_array($data) && isset($data[0])) {
        return ['language' => $data[0]['language'] ?? 'unknown', 'confidence' => $data[0]['confidence'] ?? 0, 'provider' => 'libretranslate'];
    }

    return ['language' => 'unknown'];
}

// ══════════════════════════════════════════════════════════════════
// ── MATH DOMAIN ─────────────────────────────────────────────────
// ══════════════════════════════════════════════════════════════════

function wolframCompute($input) {
    if (!WOLFRAM_APP_ID) return ['error' => 'Wolfram Alpha App ID not configured'];

    $encoded = urlencode($input);
    $url = "https://api.wolframalpha.com/v2/query?input={$encoded}&appid=" . WOLFRAM_APP_ID . "&format=plaintext&output=JSON";

    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) return ['error' => "Wolfram API error: HTTP $code"];

    $data = json_decode($response, true);
    $queryResult = $data['queryresult'] ?? [];

    if (!($queryResult['success'] ?? false)) {
        return ['error' => 'Could not compute', 'suggestions' => $queryResult['didyoumeans'] ?? []];
    }

    $pods = [];
    foreach ($queryResult['pods'] ?? [] as $pod) {
        $subpods = [];
        foreach ($pod['subpods'] ?? [] as $sp) {
            $subpods[] = [
                'text' => $sp['plaintext'] ?? '',
                'image' => $sp['img']['src'] ?? null,
            ];
        }
        $pods[] = [
            'title' => $pod['title'] ?? '',
            'id' => $pod['id'] ?? '',
            'subpods' => $subpods,
        ];
    }

    return [
        'input' => $input,
        'pods' => $pods,
        'provider' => 'wolfram_alpha',
    ];
}

// ══════════════════════════════════════════════════════════════════
// ── WEATHER DOMAIN ──────────────────────────────────────────────
// ══════════════════════════════════════════════════════════════════

function getWeather($lat, $lon, $days = 3) {
    // Open-Meteo (free, no API key)
    $url = "https://api.open-meteo.com/v1/forecast?" . http_build_query([
        'latitude' => $lat,
        'longitude' => $lon,
        'current' => 'temperature_2m,relative_humidity_2m,wind_speed_10m,weather_code',
        'daily' => 'temperature_2m_max,temperature_2m_min,precipitation_sum,weather_code',
        'forecast_days' => min(intval($days), 7),
        'timezone' => 'auto',
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true) ?: ['error' => 'Weather data unavailable'];
}

// ══════════════════════════════════════════════════════════════════
// ── FINANCE DOMAIN ──────────────────────────────────────────────
// ══════════════════════════════════════════════════════════════════

function getExchangeRates($base = 'USD') {
    // ExchangeRate-API (free tier)
    $ch = curl_init("https://open.er-api.com/v6/latest/" . urlencode(strtoupper($base)));
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    if (($data['result'] ?? '') === 'success') {
        return [
            'base' => $data['base_code'],
            'rates' => $data['rates'],
            'updated' => $data['time_last_update_utc'] ?? date('c'),
            'provider' => 'exchangerate-api',
        ];
    }

    return ['error' => 'Exchange rate data unavailable'];
}

// ══════════════════════════════════════════════════════════════════
// ── ROUTER ──────────────────────────────────────────────────────
// ══════════════════════════════════════════════════════════════════

$domain = sanitize($_GET['domain'] ?? '', 30);
$action = sanitize($_GET['action'] ?? '', 30);

// List all domains
if ($action === 'domains' || (!$domain && !$action)) {
    jsonResponse([
        'success' => true,
        'domains' => [
            'legal' => ['actions' => ['search', 'analyze'], 'description' => 'Case law search & legal analysis (CanLII, CourtListener)'],
            'academic' => ['actions' => ['search', 'cite', 'paper'], 'description' => 'Paper search & citation (Semantic Scholar)'],
            'translate' => ['actions' => ['translate', 'detect', 'languages'], 'description' => 'Translation (DeepL, LibreTranslate)'],
            'math' => ['actions' => ['compute'], 'description' => 'Computation (Wolfram Alpha)'],
            'finance' => ['actions' => ['rates', 'convert'], 'description' => 'Exchange rates & conversion'],
            'weather' => ['actions' => ['forecast', 'current'], 'description' => 'Weather forecast (Open-Meteo)'],
        ],
    ]);
}

if (!rateLimitKey($domain)) {
    jsonResponse(['error' => 'Rate limit exceeded (30/min)'], 429);
}

$input = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
}

switch ($domain) {

    // ── Legal ───────────────────────────────────────────────────
    case 'legal':
        switch ($action) {
            case 'search':
                $query = sanitize($input['query'] ?? $_GET['q'] ?? '', 500);
                $jurisdiction = sanitize($input['jurisdiction'] ?? 'all', 10);
                $limit = min(intval($input['limit'] ?? 10), 30);
                if (!$query) jsonResponse(['error' => 'query required'], 400);
                jsonResponse(['success' => true, ...(legalSearch($query, $jurisdiction, $limit))]);
                break;
            default:
                jsonResponse(['error' => 'Legal actions: search'], 400);
        }
        break;

    // ── Academic ─────────────────────────────────────────────────
    case 'academic':
        switch ($action) {
            case 'search':
                $query = sanitize($input['query'] ?? $_GET['q'] ?? '', 500);
                $limit = min(intval($input['limit'] ?? 10), 30);
                if (!$query) jsonResponse(['error' => 'query required'], 400);
                jsonResponse(['success' => true, ...(academicSearch($query, $limit))]);
                break;

            case 'cite':
                $paperId = sanitize($input['paper_id'] ?? '', 100);
                $style = sanitize($input['style'] ?? 'apa', 10);
                if (!$paperId) jsonResponse(['error' => 'paper_id required'], 400);
                $validStyles = ['apa', 'mla', 'bibtex', 'chicago'];
                if (!in_array($style, $validStyles)) jsonResponse(['error' => 'Valid styles: ' . implode(', ', $validStyles)], 400);
                jsonResponse(['success' => true, ...(generateCitation($paperId, $style))]);
                break;

            case 'paper':
                $paperId = sanitize($input['paper_id'] ?? $_GET['id'] ?? '', 100);
                if (!$paperId) jsonResponse(['error' => 'paper_id required'], 400);
                $url = "https://api.semanticscholar.org/graph/v1/paper/{$paperId}?fields=title,authors,year,abstract,url,citationCount,venue,externalIds,references.title,citations.title";
                $ch = curl_init($url);
                curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
                $response = curl_exec($ch);
                curl_close($ch);
                $data = json_decode($response, true);
                if ($data && !isset($data['error'])) {
                    jsonResponse(['success' => true, 'paper' => $data]);
                }
                jsonResponse(['error' => 'Paper not found'], 404);
                break;

            default:
                jsonResponse(['error' => 'Academic actions: search, cite, paper'], 400);
        }
        break;

    // ── Translation ─────────────────────────────────────────────
    case 'translate':
        switch ($action) {
            case 'translate':
                $text = $input['text'] ?? '';
                $target = sanitize($input['target'] ?? 'en', 5);
                $source = sanitize($input['source'] ?? '', 5) ?: null;
                if (strlen($text) < 1) jsonResponse(['error' => 'text required'], 400);

                $result = translateText($text, $target, $source);
                if (isset($result['error'])) jsonResponse(['error' => $result['error']], 502);
                jsonResponse(['success' => true, ...$result]);
                break;

            case 'detect':
                $text = $input['text'] ?? '';
                if (strlen($text) < 3) jsonResponse(['error' => 'text required (min 3 chars)'], 400);
                jsonResponse(['success' => true, ...(detectLanguage($text))]);
                break;

            case 'languages':
                // DeepL supported languages
                $languages = [
                    'BG' => 'Bulgarian', 'CS' => 'Czech', 'DA' => 'Danish', 'DE' => 'German',
                    'EL' => 'Greek', 'EN' => 'English', 'ES' => 'Spanish', 'ET' => 'Estonian',
                    'FI' => 'Finnish', 'FR' => 'French', 'HU' => 'Hungarian', 'ID' => 'Indonesian',
                    'IT' => 'Italian', 'JA' => 'Japanese', 'KO' => 'Korean', 'LT' => 'Lithuanian',
                    'LV' => 'Latvian', 'NB' => 'Norwegian', 'NL' => 'Dutch', 'PL' => 'Polish',
                    'PT' => 'Portuguese', 'RO' => 'Romanian', 'RU' => 'Russian', 'SK' => 'Slovak',
                    'SL' => 'Slovenian', 'SV' => 'Swedish', 'TR' => 'Turkish', 'UK' => 'Ukrainian',
                    'ZH' => 'Chinese',
                ];
                jsonResponse(['success' => true, 'languages' => $languages, 'count' => count($languages)]);
                break;

            default:
                jsonResponse(['error' => 'Translate actions: translate, detect, languages'], 400);
        }
        break;

    // ── Math ────────────────────────────────────────────────────
    case 'math':
        switch ($action) {
            case 'compute':
                $expression = sanitize($input['expression'] ?? $input['query'] ?? $_GET['q'] ?? '', 500);
                if (!$expression) jsonResponse(['error' => 'expression required'], 400);
                $result = wolframCompute($expression);
                if (isset($result['error'])) jsonResponse(['error' => $result['error']], 502);
                jsonResponse(['success' => true, ...$result]);
                break;
            default:
                jsonResponse(['error' => 'Math actions: compute'], 400);
        }
        break;

    // ── Finance ─────────────────────────────────────────────────
    case 'finance':
        switch ($action) {
            case 'rates':
                $base = sanitize($_GET['base'] ?? 'USD', 5);
                $result = getExchangeRates($base);
                if (isset($result['error'])) jsonResponse(['error' => $result['error']], 502);
                jsonResponse(['success' => true, ...$result]);
                break;
            case 'convert':
                $from = sanitize($input['from'] ?? $_GET['from'] ?? 'USD', 5);
                $to = sanitize($input['to'] ?? $_GET['to'] ?? 'CAD', 5);
                $amount = floatval($input['amount'] ?? $_GET['amount'] ?? 1);
                $rates = getExchangeRates($from);
                if (isset($rates['error'])) jsonResponse(['error' => $rates['error']], 502);
                $rate = $rates['rates'][strtoupper($to)] ?? null;
                if (!$rate) jsonResponse(['error' => "Unknown currency: $to"], 400);
                jsonResponse(['success' => true, 'from' => $from, 'to' => $to, 'amount' => $amount, 'converted' => round($amount * $rate, 4), 'rate' => $rate]);
                break;
            default:
                jsonResponse(['error' => 'Finance actions: rates, convert'], 400);
        }
        break;

    // ── Weather ─────────────────────────────────────────────────
    case 'weather':
        $lat = floatval($_GET['lat'] ?? $input['lat'] ?? 0);
        $lon = floatval($_GET['lon'] ?? $input['lon'] ?? 0);
        if ($lat == 0 && $lon == 0) jsonResponse(['error' => 'lat and lon required'], 400);
        $days = intval($_GET['days'] ?? $input['days'] ?? 3);
        jsonResponse(['success' => true, ...(getWeather($lat, $lon, $days))]);
        break;

    default:
        jsonResponse(['error' => "Unknown domain: $domain. Use: legal, academic, translate, math, finance, weather"], 400);
}
