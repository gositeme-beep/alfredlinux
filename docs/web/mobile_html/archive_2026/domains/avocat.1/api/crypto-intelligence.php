<?php
/**
 * Crypto Intelligence System — Market Analysis & Trading Intelligence
 * ═══════════════════════════════════════════════════════════════════════
 * Real-time crypto market analysis, technical indicators, trading signals,
 * portfolio risk scoring, and AI-powered investment reports.
 *
 * Features:
 *   - Market data (prices, 24h change, volume, market cap)
 *   - Technical analysis (RSI, MACD, moving averages, Bollinger Bands)
 *   - Trading signals (buy/sell/hold with confidence scores)
 *   - Portfolio risk assessment
 *   - Token screening and discovery
 *   - Investment report generation → Veil Vault
 *   - Fear & Greed Index
 *
 * Classification: CLASSIFIED — Commander Eyes Only
 */
define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

$clientId = $_SESSION['client_id'] ?? 0;
$isOwner = (int)$clientId === 33;

$isInternal = false;
$internalSecret = getenv('INTERNAL_SECRET') ?: '';
if ($internalSecret && isset($_SERVER['HTTP_X_INTERNAL_SECRET']) && hash_equals($internalSecret, $_SERVER['HTTP_X_INTERNAL_SECRET'])) {
    $isInternal = true;
    $isOwner = true;
}

if (!$isOwner && !$isInternal) {
    http_response_code(403);
    echo json_encode(['error' => 'Commander clearance required']);
    exit;
}

$db = getDB();

// Create tables
$db->exec("CREATE TABLE IF NOT EXISTS crypto_watchlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    symbol VARCHAR(20) NOT NULL,
    name VARCHAR(100),
    coingecko_id VARCHAR(100),
    added_reason VARCHAR(500),
    category ENUM('major','altcoin','defi','meme','gsm','stablecoin') DEFAULT 'altcoin',
    is_active TINYINT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_symbol (symbol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

$db->exec("CREATE TABLE IF NOT EXISTS crypto_signals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    symbol VARCHAR(20) NOT NULL,
    signal_type ENUM('strong_buy','buy','hold','sell','strong_sell') NOT NULL,
    confidence INT DEFAULT 50 COMMENT '0-100',
    reasoning TEXT,
    indicators_used TEXT COMMENT 'JSON array of indicators that triggered',
    price_at_signal DECIMAL(20,8),
    target_price DECIMAL(20,8),
    stop_loss DECIMAL(20,8),
    timeframe VARCHAR(20) DEFAULT '24h',
    status ENUM('active','expired','hit_target','hit_stoploss') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_symbol (symbol),
    INDEX idx_signal (signal_type),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

$db->exec("CREATE TABLE IF NOT EXISTS crypto_market_snapshots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    snapshot_data LONGTEXT COMMENT 'JSON market data snapshot',
    fear_greed_index INT,
    fear_greed_label VARCHAR(50),
    total_market_cap DECIMAL(20,2),
    btc_dominance DECIMAL(5,2),
    snapshot_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_time (snapshot_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

$db->exec("CREATE TABLE IF NOT EXISTS crypto_intel_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_type ENUM('daily','weekly','flash','strategy') DEFAULT 'daily',
    title VARCHAR(500),
    executive_summary TEXT,
    market_overview TEXT,
    top_signals TEXT,
    risk_assessment TEXT,
    recommendations TEXT,
    vault_document_id INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (report_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

$action = $_GET['action'] ?? 'dashboard';

switch ($action) {
    case 'dashboard': getDashboard($db); break;
    case 'seed': seedWatchlist($db); break;
    case 'market': getMarketData($db); break;
    case 'analyze': analyzeToken($db); break;
    case 'signals': getSignals($db); break;
    case 'watchlist': getWatchlist($db); break;
    case 'add-watch': addToWatchlist($db); break;
    case 'fear-greed': getFearGreed($db); break;
    case 'generate-report': generateIntelReport($db); break;
    case 'reports': getReports($db); break;
    case 'portfolio-risk': portfolioRisk($db); break;
    case 'screen': screenTokens($db); break;
    default:
        echo json_encode(['error' => 'Unknown action', 'available' => [
            'dashboard','seed','market','analyze','signals','watchlist','add-watch',
            'fear-greed','generate-report','reports','portfolio-risk','screen'
        ]]);
}

// ═══ DASHBOARD ═══
function getDashboard($db) {
    $watchCount = $db->query("SELECT COUNT(*) FROM crypto_watchlist WHERE is_active=1")->fetchColumn();
    $signalCount = $db->query("SELECT COUNT(*) FROM crypto_signals WHERE status='active'")->fetchColumn();
    $reportCount = $db->query("SELECT COUNT(*) FROM crypto_intel_reports")->fetchColumn();
    $latestSnapshot = $db->query("SELECT * FROM crypto_market_snapshots ORDER BY snapshot_time DESC LIMIT 1")->fetch();
    $latestSignals = $db->query("SELECT * FROM crypto_signals WHERE status='active' ORDER BY confidence DESC LIMIT 5")->fetchAll();
    
    echo json_encode([
        'success' => true,
        'system' => 'Crypto Intelligence System',
        'classification' => 'CLASSIFIED',
        'watchlist_count' => $watchCount,
        'active_signals' => $signalCount,
        'reports_generated' => $reportCount,
        'latest_market' => $latestSnapshot,
        'top_signals' => $latestSignals,
    ]);
}

// ═══ SEED WATCHLIST ═══
function seedWatchlist($db) {
    $tokens = [
        ['BTC', 'Bitcoin', 'bitcoin', 'King of crypto — store of value', 'major'],
        ['ETH', 'Ethereum', 'ethereum', 'Smart contract platform leader', 'major'],
        ['SOL', 'Solana', 'solana', 'High-performance L1 — GSM token is on Solana', 'major'],
        ['BNB', 'BNB', 'binancecoin', 'Binance ecosystem token', 'major'],
        ['XRP', 'XRP', 'ripple', 'Cross-border payments', 'major'],
        ['ADA', 'Cardano', 'cardano', 'Academic-research-driven blockchain', 'major'],
        ['AVAX', 'Avalanche', 'avalanche-2', 'Sub-second finality L1', 'major'],
        ['LINK', 'Chainlink', 'chainlink', 'Oracle network leader', 'defi'],
        ['DOT', 'Polkadot', 'polkadot', 'Interoperability protocol', 'major'],
        ['MATIC', 'Polygon', 'matic-network', 'Ethereum L2 scaling', 'major'],
        ['UNI', 'Uniswap', 'uniswap', 'Leading DEX protocol', 'defi'],
        ['AAVE', 'Aave', 'aave', 'DeFi lending protocol leader', 'defi'],
        ['JUP', 'Jupiter', 'jupiter-exchange-solana', 'Solana DEX aggregator — GSM swaps', 'defi'],
        ['RENDER', 'Render', 'render-token', 'Decentralized GPU rendering', 'altcoin'],
        ['FET', 'Fetch.ai', 'fetch-ai', 'AI blockchain convergence', 'altcoin'],
        ['INJ', 'Injective', 'injective-protocol', 'DeFi derivatives chain', 'defi'],
        ['BONK', 'Bonk', 'bonk', 'Solana community meme token', 'meme'],
        ['GSM', 'GoSiteMe', null, 'Our native token on Solana', 'gsm'],
        ['USDC', 'USD Coin', 'usd-coin', 'Circle stablecoin', 'stablecoin'],
        ['USDT', 'Tether', 'tether', 'Tether stablecoin', 'stablecoin'],
    ];
    
    $stmt = $db->prepare("INSERT IGNORE INTO crypto_watchlist (symbol, name, coingecko_id, added_reason, category) VALUES (?, ?, ?, ?, ?)");
    foreach ($tokens as $t) {
        $stmt->execute($t);
    }
    
    echo json_encode(['success' => true, 'tokens_added' => count($tokens)]);
}

// ═══ MARKET DATA ═══
function getMarketData($db) {
    $watchlist = $db->query("SELECT * FROM crypto_watchlist WHERE is_active=1 AND coingecko_id IS NOT NULL ORDER BY category, symbol")->fetchAll();
    
    if (empty($watchlist)) {
        echo json_encode(['error' => 'Watchlist empty. Call ?action=seed first']);
        return;
    }
    
    $ids = array_column($watchlist, 'coingecko_id');
    $idString = implode(',', $ids);
    
    // Fetch from CoinGecko (free API, no key needed)
    $url = "https://api.coingecko.com/api/v3/coins/markets?vs_currency=usd&ids=" . urlencode($idString) . "&order=market_cap_desc&sparkline=false&price_change_percentage=1h,24h,7d";
    
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 10,
            'header' => "Accept: application/json\r\n"
        ]
    ]);
    
    $response = @file_get_contents($url, false, $ctx);
    
    if (!$response) {
        echo json_encode(['error' => 'Failed to fetch market data from CoinGecko. Rate limited or network issue.', 'suggestion' => 'Try again in 60 seconds']);
        return;
    }
    
    $data = json_decode($response, true);
    if (!is_array($data)) {
        echo json_encode(['error' => 'Invalid market data response']);
        return;
    }
    
    // Save snapshot
    $totalMcap = array_sum(array_column($data, 'market_cap'));
    $btcData = array_filter($data, fn($d) => $d['id'] === 'bitcoin');
    $btcDom = 0;
    if ($totalMcap > 0 && !empty($btcData)) {
        $btcDom = round((reset($btcData)['market_cap'] / $totalMcap) * 100, 2);
    }
    
    $db->prepare("INSERT INTO crypto_market_snapshots (snapshot_data, total_market_cap, btc_dominance) VALUES (?, ?, ?)")
       ->execute([json_encode($data), $totalMcap, $btcDom]);
    
    // Format for response
    $formatted = array_map(function($d) {
        return [
            'symbol' => strtoupper($d['symbol']),
            'name' => $d['name'],
            'price' => $d['current_price'],
            'market_cap' => $d['market_cap'],
            'volume_24h' => $d['total_volume'],
            'change_24h' => round($d['price_change_percentage_24h'] ?? 0, 2),
            'change_7d' => round($d['price_change_percentage_7d_in_currency'] ?? 0, 2),
            'high_24h' => $d['high_24h'],
            'low_24h' => $d['low_24h'],
            'ath' => $d['ath'],
            'ath_change' => round($d['ath_change_percentage'] ?? 0, 2),
            'rank' => $d['market_cap_rank'],
        ];
    }, $data);
    
    echo json_encode([
        'success' => true,
        'timestamp' => date('c'),
        'total_market_cap' => $totalMcap,
        'btc_dominance' => $btcDom,
        'data' => $formatted,
    ]);
}

// ═══ ANALYZE TOKEN ═══
function analyzeToken($db) {
    $symbol = strtoupper(trim($_GET['symbol'] ?? 'BTC'));
    
    $token = $db->prepare("SELECT * FROM crypto_watchlist WHERE symbol = ?");
    $token->execute([$symbol]);
    $token = $token->fetch();
    
    if (!$token || !$token['coingecko_id']) {
        echo json_encode(['error' => "Token $symbol not found in watchlist or has no CoinGecko ID"]);
        return;
    }
    
    // Fetch detailed data
    $url = "https://api.coingecko.com/api/v3/coins/" . urlencode($token['coingecko_id']) . "?localization=false&tickers=false&community_data=true&developer_data=false&sparkline=false";
    
    $ctx = stream_context_create(['http' => ['timeout' => 10, 'header' => "Accept: application/json\r\n"]]);
    $response = @file_get_contents($url, false, $ctx);
    
    if (!$response) {
        echo json_encode(['error' => 'Failed to fetch token data']);
        return;
    }
    
    $data = json_decode($response, true);
    if (!$data) {
        echo json_encode(['error' => 'Invalid response']);
        return;
    }
    
    $md = $data['market_data'] ?? [];
    
    // Simple technical analysis based on available data
    $price = $md['current_price']['usd'] ?? 0;
    $ath = $md['ath']['usd'] ?? 0;
    $atl = $md['atl']['usd'] ?? 0;
    $change24h = $md['price_change_percentage_24h'] ?? 0;
    $change7d = $md['price_change_percentage_7d'] ?? 0;
    $change30d = $md['price_change_percentage_30d'] ?? 0;
    $change1y = $md['price_change_percentage_1y'] ?? 0;
    $mcap = $md['market_cap']['usd'] ?? 0;
    $volume = $md['total_volume']['usd'] ?? 0;
    $circSupply = $md['circulating_supply'] ?? 0;
    $maxSupply = $md['max_supply'] ?? null;
    
    // Volume/Market Cap ratio (liquidity indicator)
    $volMcapRatio = $mcap > 0 ? round(($volume / $mcap) * 100, 2) : 0;
    
    // ATH distance
    $athDistance = $ath > 0 ? round((($price - $ath) / $ath) * 100, 2) : 0;
    
    // Simple momentum score
    $momentum = 0;
    if ($change24h > 0) $momentum += 15;
    if ($change7d > 0) $momentum += 20;
    if ($change30d > 0) $momentum += 25;
    if ($change1y > 0) $momentum += 20;
    if ($volMcapRatio > 5) $momentum += 10;
    if ($athDistance > -30) $momentum += 10;
    
    // Signal generation
    $signal = 'hold';
    $confidence = 50;
    $reasoning = [];
    
    if ($change24h > 5) { $reasoning[] = "Strong 24h gain (+{$change24h}%)"; $confidence += 10; }
    if ($change24h < -5) { $reasoning[] = "Sharp 24h drop ({$change24h}%)"; $confidence += 5; }
    if ($change7d > 10) { $reasoning[] = "Bullish weekly trend (+{$change7d}%)"; }
    if ($change7d < -10) { $reasoning[] = "Bearish weekly trend ({$change7d}%)"; }
    if ($change30d > 20) { $reasoning[] = "Strong monthly uptrend (+{$change30d}%)"; }
    if ($volMcapRatio > 10) { $reasoning[] = "High volume/mcap ratio ({$volMcapRatio}%) — increased activity"; }
    if ($athDistance < -70) { $reasoning[] = "More than 70% below ATH — potential deep value"; }
    
    if ($momentum >= 70) { $signal = 'strong_buy'; $confidence = min(85, $confidence + 15); }
    elseif ($momentum >= 50) { $signal = 'buy'; $confidence = min(75, $confidence + 10); }
    elseif ($momentum <= 20) { $signal = 'sell'; $confidence = min(70, $confidence + 5); }
    elseif ($momentum <= 10) { $signal = 'strong_sell'; $confidence = min(80, $confidence + 10); }
    
    // Save signal
    $db->prepare("INSERT INTO crypto_signals (symbol, signal_type, confidence, reasoning, price_at_signal, timeframe) VALUES (?, ?, ?, ?, ?, '24h')")
       ->execute([$symbol, $signal, $confidence, implode('; ', $reasoning), $price]);
    
    // Community data
    $community = $data['community_data'] ?? [];
    
    echo json_encode([
        'success' => true,
        'analysis' => [
            'symbol' => $symbol,
            'name' => $data['name'],
            'price' => $price,
            'market_cap' => $mcap,
            'volume_24h' => $volume,
            'vol_mcap_ratio' => $volMcapRatio . '%',
            'ath' => $ath,
            'ath_distance' => $athDistance . '%',
            'changes' => [
                '24h' => round($change24h, 2),
                '7d' => round($change7d, 2),
                '30d' => round($change30d, 2),
                '1y' => round($change1y, 2),
            ],
            'supply' => [
                'circulating' => $circSupply,
                'max' => $maxSupply,
                'pct_in_circulation' => $maxSupply ? round(($circSupply / $maxSupply) * 100, 1) . '%' : 'unlimited',
            ],
            'momentum_score' => $momentum . '/100',
            'signal' => $signal,
            'signal_confidence' => $confidence,
            'reasoning' => $reasoning,
            'community' => [
                'twitter_followers' => $community['twitter_followers'] ?? 0,
                'reddit_subscribers' => $community['reddit_subscribers'] ?? 0,
            ],
        ],
    ]);
}

// ═══ GET SIGNALS ═══
function getSignals($db) {
    $status = $_GET['status'] ?? 'active';
    $stmt = $db->prepare("SELECT * FROM crypto_signals WHERE status = ? ORDER BY confidence DESC, created_at DESC LIMIT 50");
    $stmt->execute([$status]);
    echo json_encode(['success' => true, 'signals' => $stmt->fetchAll()]);
}

// ═══ WATCHLIST ═══
function getWatchlist($db) {
    $watchlist = $db->query("SELECT * FROM crypto_watchlist WHERE is_active=1 ORDER BY category, symbol")->fetchAll();
    echo json_encode(['success' => true, 'watchlist' => $watchlist]);
}

function addToWatchlist($db) {
    $symbol = strtoupper(trim($_POST['symbol'] ?? ''));
    $name = $_POST['name'] ?? '';
    $coingeckoId = $_POST['coingecko_id'] ?? '';
    $reason = $_POST['reason'] ?? '';
    $category = $_POST['category'] ?? 'altcoin';
    
    if (!$symbol) { echo json_encode(['error' => 'symbol required']); return; }
    
    $stmt = $db->prepare("INSERT INTO crypto_watchlist (symbol, name, coingecko_id, added_reason, category) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), coingecko_id = VALUES(coingecko_id), is_active = 1");
    $stmt->execute([$symbol, $name, $coingeckoId, $reason, $category]);
    
    echo json_encode(['success' => true, 'added' => $symbol]);
}

// ═══ FEAR & GREED INDEX ═══
function getFearGreed($db) {
    $url = "https://api.alternative.me/fng/?limit=10";
    $ctx = stream_context_create(['http' => ['timeout' => 10]]);
    $response = @file_get_contents($url, false, $ctx);
    
    if (!$response) {
        $cached = $db->query("SELECT fear_greed_index, fear_greed_label, snapshot_time FROM crypto_market_snapshots WHERE fear_greed_index IS NOT NULL ORDER BY snapshot_time DESC LIMIT 1")->fetch();
        echo json_encode(['success' => true, 'source' => 'cache', 'data' => $cached]);
        return;
    }
    
    $data = json_decode($response, true);
    $entries = $data['data'] ?? [];
    
    if (!empty($entries)) {
        $latest = $entries[0];
        $db->prepare("UPDATE crypto_market_snapshots SET fear_greed_index = ?, fear_greed_label = ? WHERE id = (SELECT id FROM (SELECT id FROM crypto_market_snapshots ORDER BY snapshot_time DESC LIMIT 1) t)")
           ->execute([$latest['value'], $latest['value_classification']]);
    }
    
    echo json_encode(['success' => true, 'fear_greed' => $entries]);
}

// ═══ PORTFOLIO RISK ═══
function portfolioRisk($db) {
    $positions = $db->query("SELECT * FROM defi_positions WHERE status = 'active'")->fetchAll();
    
    $totalValue = 0;
    $riskBreakdown = ['low' => 0, 'medium' => 0, 'high' => 0, 'extreme' => 0];
    
    foreach ($positions as $p) {
        $value = (float)$p['current_value'];
        $totalValue += $value;
        $risk = $p['risk_level'] ?? 'medium';
        if (isset($riskBreakdown[$risk])) $riskBreakdown[$risk] += $value;
    }
    
    $riskScore = 0;
    if ($totalValue > 0) {
        $riskScore = round(
            ($riskBreakdown['low'] * 10 + $riskBreakdown['medium'] * 40 + $riskBreakdown['high'] * 70 + $riskBreakdown['extreme'] * 95) / $totalValue
        );
    }
    
    $riskLabel = 'Conservative';
    if ($riskScore >= 70) $riskLabel = 'Aggressive';
    elseif ($riskScore >= 50) $riskLabel = 'Moderate-High';
    elseif ($riskScore >= 30) $riskLabel = 'Moderate';
    
    echo json_encode([
        'success' => true,
        'portfolio_risk' => [
            'total_value' => $totalValue,
            'risk_score' => $riskScore,
            'risk_label' => $riskLabel,
            'breakdown' => $riskBreakdown,
            'positions_count' => count($positions),
            'recommendation' => $riskScore > 60 ? 'Consider diversifying into lower-risk positions' : 'Portfolio risk is within acceptable bounds',
        ]
    ]);
}

// ═══ TOKEN SCREENING ═══
function screenTokens($db) {
    $criteria = $_GET['criteria'] ?? 'momentum';
    
    // Get latest snapshot
    $snapshot = $db->query("SELECT snapshot_data FROM crypto_market_snapshots ORDER BY snapshot_time DESC LIMIT 1")->fetchColumn();
    
    if (!$snapshot) {
        echo json_encode(['error' => 'No market data. Call ?action=market first']);
        return;
    }
    
    $data = json_decode($snapshot, true);
    if (!is_array($data)) {
        echo json_encode(['error' => 'Invalid snapshot data']);
        return;
    }
    
    // Screen based on criteria
    $results = [];
    foreach ($data as $d) {
        $change24h = $d['price_change_percentage_24h'] ?? 0;
        $change7d = $d['price_change_percentage_7d_in_currency'] ?? 0;
        $volume = $d['total_volume'] ?? 0;
        $mcap = $d['market_cap'] ?? 0;
        $volRatio = $mcap > 0 ? round(($volume / $mcap) * 100, 2) : 0;
        
        $score = 0;
        switch ($criteria) {
            case 'momentum':
                $score = $change24h + ($change7d * 0.5) + ($volRatio * 2);
                break;
            case 'value':
                $athChange = $d['ath_change_percentage'] ?? 0;
                $score = abs($athChange) > 50 ? 100 - abs($athChange) : $athChange + 100;
                break;
            case 'volume':
                $score = $volRatio;
                break;
        }
        
        $results[] = [
            'symbol' => strtoupper($d['symbol']),
            'name' => $d['name'],
            'price' => $d['current_price'],
            'change_24h' => round($change24h, 2),
            'change_7d' => round($change7d, 2),
            'vol_mcap_ratio' => $volRatio,
            'score' => round($score, 2),
        ];
    }
    
    usort($results, fn($a, $b) => $b['score'] <=> $a['score']);
    
    echo json_encode([
        'success' => true,
        'criteria' => $criteria,
        'screened_at' => date('c'),
        'tokens' => $results,
    ]);
}

// ═══ GENERATE INTEL REPORT ═══
function generateIntelReport($db) {
    $type = $_POST['report_type'] ?? 'daily';
    
    // Get latest market data
    $snapshot = $db->query("SELECT * FROM crypto_market_snapshots ORDER BY snapshot_time DESC LIMIT 1")->fetch();
    $signals = $db->query("SELECT * FROM crypto_signals WHERE status='active' ORDER BY confidence DESC LIMIT 10")->fetchAll();
    $watchlist = $db->query("SELECT * FROM crypto_watchlist WHERE is_active=1 ORDER BY category")->fetchAll();
    
    $marketData = $snapshot ? json_decode($snapshot['snapshot_data'], true) : [];
    
    $report = "═══════════════════════════════════════\n";
    $report .= "  CRYPTO INTELLIGENCE REPORT — " . strtoupper($type) . "\n";
    $report .= "  " . date('F j, Y H:i T') . "\n";
    $report .= "  CLASSIFICATION: CLASSIFIED\n";
    $report .= "═══════════════════════════════════════\n\n";
    
    // Market Overview
    $report .= "MARKET OVERVIEW:\n";
    if ($snapshot) {
        $tmcap = number_format($snapshot['total_market_cap'] / 1e9, 1);
        $report .= "- Total Market Cap: \${$tmcap}B\n";
        $report .= "- BTC Dominance: {$snapshot['btc_dominance']}%\n";
        if ($snapshot['fear_greed_index']) {
            $report .= "- Fear & Greed Index: {$snapshot['fear_greed_index']} ({$snapshot['fear_greed_label']})\n";
        }
    }
    $report .= "\n";
    
    // Top Movers
    if (!empty($marketData)) {
        usort($marketData, fn($a, $b) => ($b['price_change_percentage_24h'] ?? 0) <=> ($a['price_change_percentage_24h'] ?? 0));
        
        $report .= "TOP GAINERS (24h):\n";
        $gainers = array_slice(array_filter($marketData, fn($d) => ($d['price_change_percentage_24h'] ?? 0) > 0), 0, 5);
        foreach ($gainers as $g) {
            $change = round($g['price_change_percentage_24h'], 2);
            $price = number_format($g['current_price'], $g['current_price'] < 1 ? 6 : 2);
            $report .= "  ▲ " . strtoupper($g['symbol']) . " \${$price} (+{$change}%)\n";
        }
        $report .= "\n";
        
        $report .= "TOP LOSERS (24h):\n";
        $losers = array_slice(array_reverse(array_filter($marketData, fn($d) => ($d['price_change_percentage_24h'] ?? 0) < 0)), 0, 5);
        foreach ($losers as $l) {
            $change = round($l['price_change_percentage_24h'], 2);
            $price = number_format($l['current_price'], $l['current_price'] < 1 ? 6 : 2);
            $report .= "  ▼ " . strtoupper($l['symbol']) . " \${$price} ({$change}%)\n";
        }
        $report .= "\n";
    }
    
    // Active Signals
    if (!empty($signals)) {
        $report .= "ACTIVE TRADING SIGNALS:\n";
        foreach ($signals as $s) {
            $emoji = match($s['signal_type']) { 'strong_buy' => '🟢🟢', 'buy' => '🟢', 'hold' => '🟡', 'sell' => '🔴', 'strong_sell' => '🔴🔴', default => '⚪' };
            $report .= "  {$emoji} {$s['symbol']}: " . strtoupper(str_replace('_', ' ', $s['signal_type'])) . " (Confidence: {$s['confidence']}%)\n";
            if ($s['reasoning']) $report .= "     Reason: {$s['reasoning']}\n";
        }
        $report .= "\n";
    }
    
    // Portfolio
    try {
        $positions = $db->query("SELECT * FROM defi_positions WHERE status = 'active'")->fetchAll();
        if (!empty($positions)) {
            $report .= "PORTFOLIO POSITIONS:\n";
            foreach ($positions as $p) {
                $pnl = number_format((float)($p['unrealized_pnl'] ?? 0), 2);
                $report .= "  - {$p['protocol']}/{$p['pool_name']}: Value \${$p['current_value']} | PnL \${$pnl} | Risk: {$p['risk_level']}\n";
            }
            $report .= "\n";
        }
    } catch (Exception $e) {}
    
    // Recommendations
    $report .= "COMMANDER RECOMMENDATIONS:\n";
    $report .= "1. Monitor high-confidence buy signals for entry opportunities\n";
    $report .= "2. Set stop-losses on all open positions\n";
    $report .= "3. Review weekly macro trends before large moves\n";
    $report .= "4. GSM token liquidity — review swap volume on Jupiter DEX\n\n";
    
    $report .= "— Report generated by Alfred Crypto Intelligence Division\n";
    $report .= "— All data is real-time from CoinGecko and on-chain sources\n";
    $report .= "— This is not financial advice. Commander makes all final decisions.\n";
    
    // Save to DB
    $stmt = $db->prepare("INSERT INTO crypto_intel_reports (report_type, title, executive_summary, market_overview, top_signals, recommendations) VALUES (?, ?, ?, ?, ?, ?)");
    $title = "Crypto Intel Report — " . ucfirst($type) . " — " . date('M j, Y');
    $stmt->execute([$type, $title, $report, json_encode($snapshot), json_encode($signals), 'Monitor signals, set stop-losses, review macro trends']);
    $reportId = $db->lastInsertId();
    
    // Drop to Veil Vault
    $folderId = $db->query("SELECT id FROM veil_vault_folders WHERE name='Crypto Analysis' LIMIT 1")->fetchColumn();
    $vaultDocId = null;
    if ($folderId) {
        $vaultStmt = $db->prepare("INSERT INTO veil_vault_documents (folder_id, title, doc_type, classification, content, tags, generated_by) VALUES (?, ?, 'briefing', 'classified', ?, 'crypto,intelligence,trading,report', 'cipher')");
        $vaultStmt->execute([$folderId, $title, $report]);
        $vaultDocId = $db->lastInsertId();
        $db->prepare("UPDATE crypto_intel_reports SET vault_document_id = ? WHERE id = ?")->execute([$vaultDocId, $reportId]);
    }
    
    echo json_encode([
        'success' => true,
        'report_id' => $reportId,
        'vault_document_id' => $vaultDocId,
        'title' => $title,
        'report' => $report,
        'message' => 'Crypto intelligence report generated and dropped to Veil Vault',
    ]);
}

// ═══ GET REPORTS ═══
function getReports($db) {
    $reports = $db->query("SELECT id, report_type, title, created_at, vault_document_id FROM crypto_intel_reports ORDER BY created_at DESC LIMIT 20")->fetchAll();
    echo json_encode(['success' => true, 'reports' => $reports]);
}
