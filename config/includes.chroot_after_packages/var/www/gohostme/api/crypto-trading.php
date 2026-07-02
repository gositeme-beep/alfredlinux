<?php
/**
 * GoSiteMe Crypto Trading API — Poloniex Integration
 * ====================================================
 * Endpoint: /api/crypto-trading.php
 * 
 * Public operations (no Poloniex auth):
 *   ?action=markets        — list all USDT pairs
 *   ?action=ticker          — 24h ticker for all or specific pair
 *   ?action=candles&pair=BTC_USDT&interval=HOUR_1&limit=100
 *   ?action=orderbook&pair=BTC_USDT
 *   ?action=analyze&pair=BTC_USDT  — AI technical analysis
 *   ?action=signals         — scan all pairs for trading signals
 *   ?action=portfolio        — show current portfolio value
 *   ?action=dashboard        — full trading dashboard data
 *
 * Authenticated operations (need Poloniex API key in vault):
 *   ?action=balances         — account balances
 *   ?action=buy&pair=BTC_USDT&amount=10&price=market
 *   ?action=sell&pair=BTC_USDT&amount=0.0001&price=market
 *   ?action=orders           — open orders
 *   ?action=cancel&orderId=xxx
 *   ?action=history          — trade history
 *   ?action=bot_status       — trading bot status
 *   ?action=bot_start        — start conservative auto-trading
 *   ?action=bot_stop         — stop auto-trading
 */

if (!defined('GOSITEME_API')) define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$action = $_GET['action'] ?? $_POST['action'] ?? 'dashboard';
$db = getDB();

// Ensure crypto tables exist
ensureCryptoTables($db);

try {
    switch ($action) {
        case 'markets':
            echo json_encode(getMarkets());
            break;
        case 'ticker':
            $pair = $_GET['pair'] ?? null;
            echo json_encode(getTicker($pair));
            break;
        case 'candles':
            $pair = $_GET['pair'] ?? 'BTC_USDT';
            $interval = $_GET['interval'] ?? 'HOUR_1';
            $limit = min((int)($_GET['limit'] ?? 100), 500);
            echo json_encode(getCandles($pair, $interval, $limit));
            break;
        case 'orderbook':
            $pair = $_GET['pair'] ?? 'BTC_USDT';
            echo json_encode(getOrderBook($pair));
            break;
        case 'analyze':
            $pair = $_GET['pair'] ?? 'BTC_USDT';
            echo json_encode(analyzePair($pair, $db));
            break;
        case 'signals':
            echo json_encode(scanSignals($db));
            break;
        case 'dashboard':
            echo json_encode(getDashboard($db));
            break;
        case 'portfolio':
            echo json_encode(getPortfolio($db));
            break;

        // Authenticated actions
        case 'balances':
            requireCommanderAuth();
            echo json_encode(getBalances($db));
            break;
        case 'buy':
        case 'sell':
            requireCommanderAuth();
            $pair = $_POST['pair'] ?? $_GET['pair'] ?? '';
            $amount = (float)($_POST['amount'] ?? $_GET['amount'] ?? 0);
            $price = $_POST['price'] ?? $_GET['price'] ?? 'market';
            if (!$pair || $amount <= 0) {
                echo json_encode(['error' => 'pair and amount required']);
                break;
            }
            echo json_encode(placeTrade($db, $action, $pair, $amount, $price));
            break;
        case 'orders':
            requireCommanderAuth();
            echo json_encode(getOpenOrders($db));
            break;
        case 'cancel':
            requireCommanderAuth();
            $orderId = $_POST['orderId'] ?? $_GET['orderId'] ?? '';
            echo json_encode(cancelOrder($db, $orderId));
            break;
        case 'history':
            requireCommanderAuth();
            echo json_encode(getTradeHistory($db));
            break;
        case 'bot_status':
            requireCommanderAuth();
            echo json_encode(getBotStatus($db));
            break;
        case 'bot_start':
            requireCommanderAuth();
            echo json_encode(startBot($db));
            break;
        case 'bot_stop':
            requireCommanderAuth();
            echo json_encode(stopBot($db));
            break;
        default:
            echo json_encode(['error' => 'unknown action', 'available' => [
                'markets','ticker','candles','orderbook','analyze','signals',
                'dashboard','portfolio','balances','buy','sell','orders',
                'cancel','history','bot_status','bot_start','bot_stop'
            ]]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

// ============================================================
// DATABASE SETUP
// ============================================================
function ensureCryptoTables($db) {
    $db->exec("CREATE TABLE IF NOT EXISTS crypto_trades (
        id INT AUTO_INCREMENT PRIMARY KEY,
        pair VARCHAR(30) NOT NULL,
        side ENUM('buy','sell') NOT NULL,
        amount DECIMAL(20,8) NOT NULL,
        price DECIMAL(20,8) NOT NULL,
        total DECIMAL(20,8) NOT NULL,
        order_id VARCHAR(64),
        status ENUM('pending','filled','cancelled','failed') DEFAULT 'pending',
        source ENUM('manual','bot','agent') DEFAULT 'manual',
        agent_name VARCHAR(50),
        pnl DECIMAL(20,8) DEFAULT 0,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $db->exec("CREATE TABLE IF NOT EXISTS crypto_portfolio (
        id INT AUTO_INCREMENT PRIMARY KEY,
        coin VARCHAR(20) NOT NULL UNIQUE,
        amount DECIMAL(20,8) DEFAULT 0,
        avg_buy_price DECIMAL(20,8) DEFAULT 0,
        total_invested DECIMAL(20,8) DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $db->exec("CREATE TABLE IF NOT EXISTS crypto_signals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        pair VARCHAR(30) NOT NULL,
        signal_type ENUM('buy','sell','hold','alert') NOT NULL,
        strength ENUM('weak','moderate','strong') DEFAULT 'moderate',
        indicator VARCHAR(50),
        reason TEXT,
        price_at_signal DECIMAL(20,8),
        target_price DECIMAL(20,8),
        stop_loss DECIMAL(20,8),
        expires_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $db->exec("CREATE TABLE IF NOT EXISTS crypto_bot_config (
        id INT AUTO_INCREMENT PRIMARY KEY,
        config_key VARCHAR(50) NOT NULL UNIQUE,
        config_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    // Default bot config
    $db->exec("INSERT IGNORE INTO crypto_bot_config (config_key, config_value) VALUES
        ('enabled', '0'),
        ('strategy', 'conservative'),
        ('max_trade_size', '5'),
        ('max_daily_trades', '10'),
        ('stop_loss_pct', '3'),
        ('take_profit_pct', '5'),
        ('pairs', 'BTC_USDT,ETH_USDT,XRP_USDT,DOGE_USDT,SOL_USDT'),
        ('total_budget', '25')
    ");
}

// ============================================================
// AUTH
// ============================================================
function requireCommanderAuth() {
    // Check for Commander (client_id 33) or API key
    if (isset($_SERVER['HTTP_X_CLIENT_ID']) && $_SERVER['HTTP_X_CLIENT_ID'] == '33') return;
    if (isset($_GET['client_id']) && $_GET['client_id'] == '33') return;
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $internalSecret = getenv('INTERNAL_SECRET') ?: '';
    $headerSecret = $_SERVER['HTTP_X_INTERNAL_SECRET']
        ?? $_SERVER['REDIRECT_HTTP_X_INTERNAL_SECRET']
        ?? ($headers['X-Internal-Secret'] ?? $headers['x-internal-secret'] ?? '');
    if ($internalSecret && $headerSecret !== '' && hash_equals($internalSecret, $headerSecret)) return;
    // Check session
    session_start();
    if (isset($_SESSION['uid']) && $_SESSION['uid'] == 33) return;
    http_response_code(403);
    echo json_encode(['error' => 'Commander access only']);
    exit;
}

// ============================================================
// POLONIEX API HELPERS
// ============================================================
function poloniexPublic($endpoint, $params = []) {
    $url = 'https://api.poloniex.com' . $endpoint;
    if ($params) $url .= '?' . http_build_query($params);
    $ctx = stream_context_create(['http' => ['timeout' => 15, 'header' => 'User-Agent: GoSiteMe-Quant/1.0']]);
    $resp = @file_get_contents($url, false, $ctx);
    return $resp ? json_decode($resp, true) : null;
}

function getPoloniexKeys($db) {
    // 1. Try tblconfiguration (WHMCS config)
    try {
        $stmt = $db->query("SELECT setting, value FROM tblconfiguration WHERE setting IN ('PoloniexApiKey','PoloniexApiSecret','PoloniexPassphrase')");
        $keys = [];
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $keys[$r['setting']] = $r['value'];
        }
        if (!empty($keys['PoloniexApiKey']) && !empty($keys['PoloniexApiSecret'])) {
            return $keys;
        }
    } catch (Exception $e) {}

    // 2. Fallback to environment variables (.env.php)
    $envKey = getenv('POLONIEX_API_KEY');
    $envSecret = getenv('POLONIEX_API_SECRET');
    if ($envKey && $envSecret) {
        return [
            'PoloniexApiKey' => $envKey,
            'PoloniexApiSecret' => $envSecret,
            'PoloniexPassphrase' => getenv('POLONIEX_PASSPHRASE') ?: ''
        ];
    }

    // 3. Try commander_credentials vault
    try {
        $stmt = $db->query("SELECT service_name, username, password FROM commander_credentials WHERE service_name LIKE '%poloniex%' OR service_name LIKE '%Poloniex%' LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && $row['password']) {
            return [
                'PoloniexApiKey' => $row['username'],
                'PoloniexApiSecret' => $row['password'],
                'PoloniexPassphrase' => ''
            ];
        }
    } catch (Exception $e) {}

    return null;
}

function poloniexAuth($method, $endpoint, $db, $body = null) {
    $keys = getPoloniexKeys($db);
    if (!$keys) throw new Exception('Poloniex API keys not configured. Store them in vault.');

    $timestamp = (string)(time() * 1000);
    $url = 'https://api.poloniex.com' . $endpoint;

    // Build signature: timestamp + method + endpoint + body
    $bodyStr = $body ? json_encode($body) : '';
    $signPayload = $timestamp . strtoupper($method) . $endpoint . $bodyStr;
    $signature = base64_encode(hash_hmac('sha256', $signPayload, $keys['PoloniexApiSecret'], true));

    $headers = [
        'Content-Type: application/json',
        'key: ' . $keys['PoloniexApiKey'],
        'signTimestamp: ' . $timestamp,
        'signature: ' . $signature
    ];
    if (!empty($keys['PoloniexPassphrase'])) {
        $headers[] = 'signatureMethod: hmacSHA256';
    }

    $opts = [
        'http' => [
            'method' => strtoupper($method),
            'header' => implode("\r\n", $headers),
            'timeout' => 15,
            'content' => $bodyStr ?: ''
        ]
    ];
    $ctx = stream_context_create($opts);
    $resp = @file_get_contents($url, false, $ctx);
    return $resp ? json_decode($resp, true) : null;
}

// ============================================================
// PUBLIC ENDPOINTS
// ============================================================
function getMarkets() {
    $data = poloniexPublic('/markets');
    if (!$data) return ['error' => 'Failed to fetch markets'];
    // Filter to active USDT pairs
    $usdt = array_filter($data, fn($m) => 
        str_ends_with($m['symbol'] ?? '', '_USDT') && ($m['state'] ?? '') === 'NORMAL'
    );
    return [
        'total' => count($usdt),
        'pairs' => array_values(array_map(fn($m) => [
            'symbol' => $m['symbol'],
            'baseCurrency' => $m['baseCurrencyName'] ?? '',
            'quoteCurrency' => 'USDT',
            'state' => $m['state'] ?? ''
        ], $usdt))
    ];
}

function getTicker($pair = null) {
    $endpoint = $pair ? "/markets/$pair/ticker24h" : '/markets/ticker24h';
    $data = poloniexPublic($endpoint);
    if (!$data) return ['error' => 'Failed to fetch ticker'];
    
    if ($pair) {
        return formatTicker($data, $pair);
    }
    
    // For all tickers, filter to USDT and sort by volume
    $tickers = [];
    foreach ($data as $t) {
        if (str_ends_with($t['symbol'] ?? '', '_USDT') && ($t['amount'] ?? 0) > 100) {
            $tickers[] = formatTicker($t);
        }
    }
    usort($tickers, fn($a, $b) => $b['volume_usd'] <=> $a['volume_usd']);
    return ['count' => count($tickers), 'tickers' => array_slice($tickers, 0, 50)];
}

function formatTicker($t, $pair = null) {
    return [
        'pair' => $pair ?? ($t['symbol'] ?? ''),
        'price' => (float)($t['close'] ?? 0),
        'high' => (float)($t['high'] ?? 0),
        'low' => (float)($t['low'] ?? 0),
        'open' => (float)($t['open'] ?? 0),
        'change_pct' => round((float)($t['dailyChange'] ?? 0) * 100, 2),
        'volume' => (float)($t['quantity'] ?? 0),
        'volume_usd' => (float)($t['amount'] ?? 0)
    ];
}

function getCandles($pair, $interval, $limit) {
    $valid = ['MINUTE_1','MINUTE_5','MINUTE_15','MINUTE_30','HOUR_1','HOUR_4','DAY_1','WEEK_1','MONTH_1'];
    if (!in_array($interval, $valid)) $interval = 'HOUR_1';
    
    $data = poloniexPublic("/markets/$pair/candles", ['interval' => $interval, 'limit' => $limit]);
    if (!$data) return ['error' => 'Failed to fetch candles'];
    
    $candles = [];
    foreach ($data as $c) {
        $candles[] = [
            'time' => date('Y-m-d H:i', ($c[12] ?? 0) / 1000),
            'timestamp' => (int)(($c[12] ?? 0) / 1000),
            'open' => (float)$c[1],
            'high' => (float)$c[0],
            'low' => (float)$c[2],
            'close' => (float)$c[3],
            'volume' => (float)$c[5],
            'volume_quote' => (float)($c[6] ?? 0)
        ];
    }
    return ['pair' => $pair, 'interval' => $interval, 'count' => count($candles), 'candles' => $candles];
}

function getOrderBook($pair) {
    $data = poloniexPublic("/markets/$pair/orderBook", ['limit' => 20]);
    if (!$data) return ['error' => 'Failed to fetch order book'];
    return [
        'pair' => $pair,
        'asks' => array_slice($data['asks'] ?? [], 0, 10),
        'bids' => array_slice($data['bids'] ?? [], 0, 10),
        'spread' => isset($data['asks'][0], $data['bids'][0]) 
            ? round((float)$data['asks'][0][0] - (float)$data['bids'][0][0], 8) : 0
    ];
}

// ============================================================
// TECHNICAL ANALYSIS
// ============================================================
function analyzePair($pair, $db) {
    // Get candles for multiple timeframes
    $h1 = poloniexPublic("/markets/$pair/candles", ['interval' => 'HOUR_1', 'limit' => 100]);
    $h4 = poloniexPublic("/markets/$pair/candles", ['interval' => 'HOUR_4', 'limit' => 50]);
    $d1 = poloniexPublic("/markets/$pair/candles", ['interval' => 'DAY_1', 'limit' => 30]);
    $ticker = poloniexPublic("/markets/$pair/ticker24h");

    if (!$h1 || !$ticker) return ['error' => 'Failed to fetch data for analysis'];

    $closes_1h = array_map(fn($c) => (float)$c[3], $h1);
    $closes_4h = $h4 ? array_map(fn($c) => (float)$c[3], $h4) : [];
    $closes_1d = $d1 ? array_map(fn($c) => (float)$c[3], $d1) : [];
    $volumes_1h = array_map(fn($c) => (float)$c[5], $h1);

    $currentPrice = (float)($ticker['close'] ?? end($closes_1h));
    
    // Calculate indicators
    $sma20 = count($closes_1h) >= 20 ? array_sum(array_slice($closes_1h, -20)) / 20 : $currentPrice;
    $sma50 = count($closes_1h) >= 50 ? array_sum(array_slice($closes_1h, -50)) / 50 : $currentPrice;
    $ema12 = calcEMA($closes_1h, 12);
    $ema26 = calcEMA($closes_1h, 26);
    $macd = $ema12 - $ema26;
    
    // RSI
    $rsi = calcRSI($closes_1h, 14);
    
    // Bollinger Bands
    $bb = calcBollingerBands($closes_1h, 20);
    
    // Volume analysis
    $avgVol = count($volumes_1h) >= 20 ? array_sum(array_slice($volumes_1h, -20)) / 20 : end($volumes_1h);
    $currentVol = end($volumes_1h);
    $volRatio = $avgVol > 0 ? round($currentVol / $avgVol, 2) : 1;

    // Generate signals
    $signals = [];
    $score = 0; // -100 to +100
    
    // Trend
    if ($currentPrice > $sma20) { $signals[] = 'Above SMA20 (bullish)'; $score += 15; }
    else { $signals[] = 'Below SMA20 (bearish)'; $score -= 15; }
    
    if ($currentPrice > $sma50) { $signals[] = 'Above SMA50 (bullish)'; $score += 10; }
    else { $signals[] = 'Below SMA50 (bearish)'; $score -= 10; }
    
    if ($sma20 > $sma50) { $signals[] = 'Golden cross (SMA20 > SMA50)'; $score += 20; }
    else { $signals[] = 'Death cross (SMA20 < SMA50)'; $score -= 20; }
    
    // MACD
    if ($macd > 0) { $signals[] = 'MACD positive'; $score += 15; }
    else { $signals[] = 'MACD negative'; $score -= 15; }
    
    // RSI
    if ($rsi < 30) { $signals[] = "RSI oversold ($rsi) — potential bounce"; $score += 25; }
    elseif ($rsi > 70) { $signals[] = "RSI overbought ($rsi) — potential drop"; $score -= 25; }
    elseif ($rsi > 50) { $signals[] = "RSI bullish ($rsi)"; $score += 5; }
    else { $signals[] = "RSI bearish ($rsi)"; $score -= 5; }
    
    // Bollinger
    if ($currentPrice < $bb['lower']) { $signals[] = 'Below lower Bollinger — oversold'; $score += 20; }
    elseif ($currentPrice > $bb['upper']) { $signals[] = 'Above upper Bollinger — overbought'; $score -= 20; }
    
    // Volume
    if ($volRatio > 2) { $signals[] = "High volume ({$volRatio}x average)"; $score += abs($score) > 0 ? 10 : 0; }
    
    // Overall recommendation
    if ($score >= 40) $recommendation = 'STRONG BUY';
    elseif ($score >= 15) $recommendation = 'BUY';
    elseif ($score > -15) $recommendation = 'HOLD';
    elseif ($score > -40) $recommendation = 'SELL';
    else $recommendation = 'STRONG SELL';

    $analysis = [
        'pair' => $pair,
        'price' => $currentPrice,
        'change_24h' => round((float)($ticker['dailyChange'] ?? 0) * 100, 2) . '%',
        'recommendation' => $recommendation,
        'score' => $score,
        'indicators' => [
            'sma20' => round($sma20, 8),
            'sma50' => round($sma50, 8),
            'ema12' => round($ema12, 8),
            'ema26' => round($ema26, 8),
            'macd' => round($macd, 8),
            'rsi' => $rsi,
            'bollinger' => $bb,
            'volume_ratio' => $volRatio
        ],
        'signals' => $signals,
        'risk_level' => $rsi > 70 || $rsi < 30 ? 'HIGH' : ($score > 30 || $score < -30 ? 'MEDIUM' : 'LOW'),
        'analyzed_at' => date('Y-m-d H:i:s')
    ];

    // Save signal to DB
    $signalType = $score >= 15 ? 'buy' : ($score <= -15 ? 'sell' : 'hold');
    $strength = abs($score) >= 40 ? 'strong' : (abs($score) >= 15 ? 'moderate' : 'weak');
    $stmt = $db->prepare("INSERT INTO crypto_signals (pair, signal_type, strength, indicator, reason, price_at_signal, expires_at) 
        VALUES (?, ?, ?, 'multi', ?, ?, DATE_ADD(NOW(), INTERVAL 4 HOUR))");
    $stmt->execute([$pair, $signalType, $strength, $recommendation . ': ' . implode('; ', array_slice($signals, 0, 3)), $currentPrice]);

    return $analysis;
}

function scanSignals($db) {
    // Scan top pairs for signals
    $watchlist = ['BTC_USDT','ETH_USDT','XRP_USDT','DOGE_USDT','SOL_USDT','ADA_USDT',
                  'AVAX_USDT','DOT_USDT','LINK_USDT','MATIC_USDT','LTC_USDT','SHIB_USDT'];
    
    $results = [];
    foreach ($watchlist as $pair) {
        $analysis = analyzePair($pair, $db);
        if (isset($analysis['error'])) continue;
        $results[] = [
            'pair' => $pair,
            'price' => $analysis['price'],
            'score' => $analysis['score'],
            'recommendation' => $analysis['recommendation'],
            'rsi' => $analysis['indicators']['rsi'],
            'change_24h' => $analysis['change_24h']
        ];
        usleep(200000); // Rate limit
    }
    
    // Sort by absolute score (strongest signals first)
    usort($results, fn($a, $b) => abs($b['score']) <=> abs($a['score']));
    
    return [
        'scanned' => count($results),
        'timestamp' => date('Y-m-d H:i:s'),
        'strongest_buy' => current(array_filter($results, fn($r) => $r['score'] > 0)) ?: null,
        'strongest_sell' => current(array_filter($results, fn($r) => $r['score'] < 0)) ?: null,
        'signals' => $results
    ];
}

// ============================================================
// PORTFOLIO & DASHBOARD
// ============================================================
function getPortfolio($db) {
    $portfolio = $db->query("SELECT * FROM crypto_portfolio WHERE amount > 0 ORDER BY total_invested DESC")->fetchAll(PDO::FETCH_ASSOC);
    $totalValue = 0;
    $totalInvested = 0;
    
    foreach ($portfolio as &$p) {
        $pair = $p['coin'] . '_USDT';
        $ticker = poloniexPublic("/markets/$pair/ticker24h");
        $price = (float)($ticker['close'] ?? 0);
        $value = $p['amount'] * $price;
        $p['current_price'] = $price;
        $p['current_value'] = round($value, 2);
        $p['pnl'] = round($value - $p['total_invested'], 2);
        $p['pnl_pct'] = $p['total_invested'] > 0 ? round(($p['pnl'] / $p['total_invested']) * 100, 2) : 0;
        $totalValue += $value;
        $totalInvested += $p['total_invested'];
    }
    
    return [
        'holdings' => $portfolio,
        'total_value' => round($totalValue, 2),
        'total_invested' => round($totalInvested, 2),
        'total_pnl' => round($totalValue - $totalInvested, 2),
        'total_pnl_pct' => $totalInvested > 0 ? round((($totalValue - $totalInvested) / $totalInvested) * 100, 2) : 0
    ];
}

function getDashboard($db) {
    // Quick dashboard — BTC + top movers + recent signals
    $btcTicker = poloniexPublic('/markets/BTC_USDT/ticker24h');
    $ethTicker = poloniexPublic('/markets/ETH_USDT/ticker24h');
    
    // Recent signals
    $signals = $db->query("SELECT pair, signal_type, strength, reason, price_at_signal, created_at 
        FROM crypto_signals WHERE expires_at > NOW() ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent trades
    $trades = $db->query("SELECT pair, side, amount, price, total, status, source, created_at 
        FROM crypto_trades ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    
    // Bot status
    $botEnabled = $db->query("SELECT config_value FROM crypto_bot_config WHERE config_key='enabled'")->fetchColumn();
    
    return [
        'btc' => $btcTicker ? formatTicker($btcTicker, 'BTC_USDT') : null,
        'eth' => $ethTicker ? formatTicker($ethTicker, 'ETH_USDT') : null,
        'bot_active' => $botEnabled === '1',
        'active_signals' => $signals,
        'recent_trades' => $trades,
        'api_status' => getPoloniexKeys($db) ? 'connected' : 'no_keys',
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

// ============================================================
// TRADING (requires Poloniex auth)
// ============================================================
function getBalances($db) {
    $data = poloniexAuth('GET', '/accounts/balances', $db);
    if (!$data) return ['error' => 'Failed to fetch balances or no API keys'];
    
    $balances = [];
    foreach ($data as $b) {
        if ((float)($b['available'] ?? 0) > 0 || (float)($b['hold'] ?? 0) > 0) {
            $balances[] = [
                'coin' => $b['currency'] ?? '',
                'available' => (float)($b['available'] ?? 0),
                'hold' => (float)($b['hold'] ?? 0),
                'total' => (float)($b['available'] ?? 0) + (float)($b['hold'] ?? 0)
            ];
        }
    }
    return ['balances' => $balances];
}

function placeTrade($db, $side, $pair, $amount, $price) {
    // Safety checks
    $config = [];
    $stmt = $db->query("SELECT config_key, config_value FROM crypto_bot_config");
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) $config[$r['config_key']] = $r['config_value'];
    
    $maxTrade = (float)($config['max_trade_size'] ?? 5);
    
    // Get current price for market orders
    $ticker = poloniexPublic("/markets/$pair/ticker24h");
    $currentPrice = (float)($ticker['close'] ?? 0);
    
    if ($price === 'market') {
        $tradeTotal = $side === 'buy' ? $amount : $amount * $currentPrice;
    } else {
        $tradeTotal = $side === 'buy' ? $amount : $amount * (float)$price;
    }
    
    if ($tradeTotal > $maxTrade) {
        return ['error' => "Trade total \${$tradeTotal} exceeds max allowed \${$maxTrade}. Adjust in bot config."];
    }
    
    // Place order via Poloniex API
    $orderBody = [
        'symbol' => $pair,
        'side' => strtoupper($side),
        'type' => $price === 'market' ? 'MARKET' : 'LIMIT',
    ];
    
    if ($price === 'market') {
        if ($side === 'buy') {
            $orderBody['amount'] = (string)$amount; // USDT amount for market buy
        } else {
            $orderBody['quantity'] = (string)$amount; // coin quantity for market sell
        }
    } else {
        $orderBody['price'] = (string)$price;
        $orderBody['quantity'] = (string)$amount;
    }
    
    $result = poloniexAuth('POST', '/orders', $db, $orderBody);
    
    if (!$result || isset($result['code'])) {
        $errMsg = $result['message'] ?? 'Trade failed';
        // Log failed trade
        $stmt = $db->prepare("INSERT INTO crypto_trades (pair, side, amount, price, total, status, source, notes) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$pair, $side, $amount, $currentPrice, $tradeTotal, 'failed', 'manual', $errMsg]);
        return ['error' => $errMsg, 'details' => $result];
    }
    
    // Log successful trade
    $orderId = $result['id'] ?? '';
    $stmt = $db->prepare("INSERT INTO crypto_trades (pair, side, amount, price, total, order_id, status, source) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->execute([$pair, $side, $amount, $currentPrice, $tradeTotal, $orderId, 'filled', 'manual']);
    
    // Update portfolio
    updatePortfolio($db, $pair, $side, $amount, $currentPrice);
    
    return ['success' => true, 'order_id' => $orderId, 'side' => $side, 'pair' => $pair, 'amount' => $amount, 'price' => $currentPrice];
}

function updatePortfolio($db, $pair, $side, $amount, $price) {
    $coin = explode('_', $pair)[0];
    
    if ($side === 'buy') {
        $stmt = $db->prepare("INSERT INTO crypto_portfolio (coin, amount, avg_buy_price, total_invested) 
            VALUES (?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
                amount = amount + VALUES(amount),
                avg_buy_price = (total_invested + VALUES(total_invested)) / (amount + VALUES(amount)),
                total_invested = total_invested + VALUES(total_invested)");
        $stmt->execute([$coin, $amount, $price, $amount * $price]);
    } else {
        $stmt = $db->prepare("UPDATE crypto_portfolio SET amount = GREATEST(0, amount - ?) WHERE coin = ?");
        $stmt->execute([$amount, $coin]);
    }
}

function getOpenOrders($db) {
    $data = poloniexAuth('GET', '/orders', $db);
    return $data ?: ['orders' => []];
}

function cancelOrder($db, $orderId) {
    if (!$orderId) return ['error' => 'orderId required'];
    $data = poloniexAuth('DELETE', "/orders/$orderId", $db);
    if ($data) {
        $db->prepare("UPDATE crypto_trades SET status='cancelled' WHERE order_id=?")->execute([$orderId]);
    }
    return $data ?: ['error' => 'Cancel failed'];
}

function getTradeHistory($db) {
    $trades = $db->query("SELECT * FROM crypto_trades ORDER BY created_at DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
    $stats = $db->query("SELECT 
        COUNT(*) as total_trades,
        SUM(CASE WHEN side='buy' THEN total ELSE 0 END) as total_bought,
        SUM(CASE WHEN side='sell' THEN total ELSE 0 END) as total_sold,
        SUM(pnl) as total_pnl
        FROM crypto_trades WHERE status='filled'")->fetch(PDO::FETCH_ASSOC);
    return ['trades' => $trades, 'stats' => $stats];
}

// ============================================================
// BOT CONTROL
// ============================================================
function getBotStatus($db) {
    $config = [];
    $stmt = $db->query("SELECT config_key, config_value FROM crypto_bot_config");
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) $config[$r['config_key']] = $r['config_value'];
    
    $todayTrades = $db->query("SELECT COUNT(*) FROM crypto_trades WHERE DATE(created_at) = CURDATE() AND source='bot'")->fetchColumn();
    
    return [
        'enabled' => $config['enabled'] === '1',
        'strategy' => $config['strategy'] ?? 'conservative',
        'max_trade_size' => $config['max_trade_size'] ?? 5,
        'max_daily_trades' => $config['max_daily_trades'] ?? 10,
        'trades_today' => (int)$todayTrades,
        'pairs' => explode(',', $config['pairs'] ?? ''),
        'budget' => $config['total_budget'] ?? 25,
        'stop_loss' => $config['stop_loss_pct'] ?? 3,
        'take_profit' => $config['take_profit_pct'] ?? 5,
        'api_connected' => getPoloniexKeys($db) !== null
    ];
}

function startBot($db) {
    $db->prepare("UPDATE crypto_bot_config SET config_value='1' WHERE config_key='enabled'")->execute();
    return ['success' => true, 'message' => 'Trading bot ENABLED. Conservative strategy active.', 'status' => getBotStatus($db)];
}

function stopBot($db) {
    $db->prepare("UPDATE crypto_bot_config SET config_value='0' WHERE config_key='enabled'")->execute();
    return ['success' => true, 'message' => 'Trading bot STOPPED.', 'status' => getBotStatus($db)];
}

// ============================================================
// MATH HELPERS
// ============================================================
function calcEMA($data, $period) {
    if (count($data) < $period) return end($data);
    $k = 2 / ($period + 1);
    $ema = array_sum(array_slice($data, 0, $period)) / $period;
    for ($i = $period; $i < count($data); $i++) {
        $ema = $data[$i] * $k + $ema * (1 - $k);
    }
    return $ema;
}

function calcRSI($data, $period = 14) {
    if (count($data) < $period + 1) return 50;
    $gains = $losses = [];
    for ($i = 1; $i < count($data); $i++) {
        $change = $data[$i] - $data[$i - 1];
        $gains[] = max(0, $change);
        $losses[] = max(0, -$change);
    }
    $avgGain = array_sum(array_slice($gains, -$period)) / $period;
    $avgLoss = array_sum(array_slice($losses, -$period)) / $period;
    if ($avgLoss == 0) return 100;
    $rs = $avgGain / $avgLoss;
    return round(100 - (100 / (1 + $rs)), 1);
}

function calcBollingerBands($data, $period = 20) {
    if (count($data) < $period) return ['upper' => 0, 'middle' => 0, 'lower' => 0];
    $slice = array_slice($data, -$period);
    $mean = array_sum($slice) / $period;
    $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $slice)) / $period;
    $std = sqrt($variance);
    return [
        'upper' => round($mean + 2 * $std, 8),
        'middle' => round($mean, 8),
        'lower' => round($mean - 2 * $std, 8)
    ];
}
