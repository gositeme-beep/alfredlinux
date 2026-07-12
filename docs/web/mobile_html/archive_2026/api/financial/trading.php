<?php
/**
 * Advanced Trading API — CEX (Kraken, Coinbase), DEX Aggregators (1inch, Li.Fi), Multi-Chain EVM
 * ATLAS Agents: Trader (#40), Treasurer (#38)
 */
define('GOSITEME_API', true);
require_once dirname(__DIR__) . '/config.php';
require_once __DIR__ . '/config.php';

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Internal-Secret');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

// ─── Schema ───────────────────────────────────────────────────
function ensureTradingSchema(): void {
    $db = getDB();

    $db->exec("CREATE TABLE IF NOT EXISTS fin_cex_orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        exchange VARCHAR(20) NOT NULL,
        pair VARCHAR(20) NOT NULL,
        order_type ENUM('market','limit') DEFAULT 'market',
        side ENUM('buy','sell') NOT NULL,
        amount DECIMAL(20,8) NOT NULL,
        price DECIMAL(20,8),
        filled_amount DECIMAL(20,8) DEFAULT 0,
        filled_price DECIMAL(20,8) DEFAULT 0,
        fee DECIMAL(20,8) DEFAULT 0,
        fee_currency VARCHAR(10),
        status ENUM('pending','open','filled','partially_filled','cancelled','failed') DEFAULT 'pending',
        external_order_id VARCHAR(100),
        metadata JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_exchange (exchange),
        INDEX idx_pair (pair),
        INDEX idx_status (status)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS fin_bridge_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        source_chain VARCHAR(20) NOT NULL,
        dest_chain VARCHAR(20) NOT NULL,
        token_in VARCHAR(20) NOT NULL,
        token_out VARCHAR(20) NOT NULL,
        amount_in DECIMAL(30,18) NOT NULL,
        amount_out DECIMAL(30,18) DEFAULT 0,
        protocol VARCHAR(30),
        tx_hash_source VARCHAR(100),
        tx_hash_dest VARCHAR(100),
        status ENUM('pending','bridging','completed','failed') DEFAULT 'pending',
        metadata JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_chains (source_chain, dest_chain),
        INDEX idx_status (status)
    )");
}
ensureTradingSchema();
// Tight rate limit for financial operations (10 per minute)
apiRateLimit(10, 60, 'financial_trading');
// ─── Routing ──────────────────────────────────────────────────
$action = sanitize($_GET['action'] ?? $_POST['action'] ?? '', 50);

switch ($action) {
    // Kraken
    case 'kraken_ticker':       finRequireAuth(); krakenTicker(); break;
    case 'kraken_balance':      finRequireAdminOrInternal(); krakenBalance(); break;
    case 'kraken_order':        finRequireAdminOrInternal(); krakenOrder(); break;
    case 'kraken_orders':       finRequireAdminOrInternal(); krakenOpenOrders(); break;
    case 'kraken_trades':       finRequireAdminOrInternal(); krakenTradeHistory(); break;

    // Coinbase
    case 'coinbase_prices':     finRequireAuth(); coinbasePrices(); break;
    case 'coinbase_accounts':   finRequireAdminOrInternal(); coinbaseAccounts(); break;
    case 'coinbase_order':      finRequireAdminOrInternal(); coinbaseOrder(); break;

    // DEX — 1inch
    case 'oneinch_quote':       finRequireAuth(); oneInchQuote(); break;
    case 'oneinch_swap':        finRequireAdminOrInternal(); oneInchSwap(); break;
    case 'oneinch_tokens':      finRequireAuth(); oneInchTokens(); break;

    // DEX — Li.Fi (Cross-chain)
    case 'lifi_quote':          finRequireAuth(); lifiQuote(); break;
    case 'lifi_routes':         finRequireAuth(); lifiRoutes(); break;
    case 'lifi_status':         finRequireAuth(); lifiStatus(); break;
    case 'lifi_chains':         finRequireAuth(); lifiChains(); break;

    // EVM Chain
    case 'evm_balance':         finRequireAuth(); evmBalance(); break;
    case 'evm_gas':             finRequireAuth(); evmGasPrice(); break;
    case 'evm_tokens':          finRequireAuth(); evmTokenBalances(); break;

    // Portfolio & Orders
    case 'portfolio':           finRequireAdminOrInternal(); portfolio(); break;
    case 'order_history':       finRequireAdminOrInternal(); orderHistory(); break;
    case 'daily_limit':         finRequireAdminOrInternal(); dailyLimitCheck(); break;

    default:
        jsonResponse(['error' => 'Invalid action', 'valid' => [
            'kraken_ticker','kraken_balance','kraken_order','kraken_orders','kraken_trades',
            'coinbase_prices','coinbase_accounts','coinbase_order',
            'oneinch_quote','oneinch_swap','oneinch_tokens',
            'lifi_quote','lifi_routes','lifi_status','lifi_chains',
            'evm_balance','evm_gas','evm_tokens',
            'portfolio','order_history','daily_limit'
        ]], 400);
}

// ═══ KRAKEN ═══════════════════════════════════════════════════

function krakenPublic(string $endpoint, array $params = []): array {
    $url = 'https://api.kraken.com/0/public/' . $endpoint;
    if ($params) $url .= '?' . http_build_query($params);
    return finApiRequest($url, 'GET');
}

function krakenPrivate(string $endpoint, array $data = []): array {
    $url = 'https://api.kraken.com/0/private/' . $endpoint;
    $nonce = (string) (time() * 1000);
    $data['nonce'] = $nonce;

    $postdata = http_build_query($data);
    $path = '/0/private/' . $endpoint;
    $sign = hash_hmac('sha512',
        $path . hash('sha256', $nonce . $postdata, true),
        base64_decode(KRAKEN_API_SECRET),
        true
    );

    return finApiRequest($url, 'POST', $data, [
        'API-Key: ' . KRAKEN_API_KEY,
        'API-Sign: ' . base64_encode($sign),
    ]);
}

function krakenTicker(): void {
    $pair = sanitize($_GET['pair'] ?? 'XBTCAD', 20);
    $response = krakenPublic('Ticker', ['pair' => $pair]);

    $tickerData = [];
    if ($response['success'] && isset($response['data']['result'])) {
        foreach ($response['data']['result'] as $p => $info) {
            $tickerData[$p] = [
                'ask' => $info['a'][0] ?? null,
                'bid' => $info['b'][0] ?? null,
                'last' => $info['c'][0] ?? null,
                'volume_24h' => $info['v'][1] ?? null,
                'high_24h' => $info['h'][1] ?? null,
                'low_24h' => $info['l'][1] ?? null,
            ];
        }
    }

    jsonResponse(['success' => true, 'pair' => $pair, 'ticker' => $tickerData]);
}

function krakenBalance(): void {
    $response = krakenPrivate('Balance');
    jsonResponse([
        'success' => $response['success'],
        'balances' => $response['data']['result'] ?? [],
        'errors' => $response['data']['error'] ?? [],
    ]);
}

function krakenOrder(): void {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $pair = sanitize($input['pair'] ?? 'XBTCAD', 20);
    $side = sanitize($input['side'] ?? 'buy', 10);
    $orderType = sanitize($input['type'] ?? 'market', 10);
    $volume = (float) ($input['volume'] ?? 0);
    $price = (float) ($input['price'] ?? 0);

    if ($volume <= 0) {
        jsonResponse(['error' => 'Positive volume required'], 400);
    }

    // Safety: estimate USD value and check limits
    $estimatedUSD = estimateOrderValueUSD($pair, $volume, $price);
    if ($estimatedUSD > FIN_MAX_TRADE_USD) {
        jsonResponse(['error' => "Estimated value \${$estimatedUSD} exceeds limit of \$" . FIN_MAX_TRADE_USD], 403);
    }

    // Check daily limit
    if (!checkDailyTradeLimit($estimatedUSD)) {
        jsonResponse(['error' => 'Daily trade limit of $' . FIN_DAILY_TRADE_LIMIT . ' exceeded'], 403);
    }

    $orderData = [
        'pair' => $pair,
        'type' => $side,
        'ordertype' => $orderType,
        'volume' => (string) $volume,
    ];
    if ($orderType === 'limit' && $price > 0) {
        $orderData['price'] = (string) $price;
    }

    $response = krakenPrivate('AddOrder', $orderData);

    if ($response['success'] && !empty($response['data']['result'])) {
        $txids = $response['data']['result']['txid'] ?? [];
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO fin_cex_orders (exchange, pair, order_type, side, amount, price, status, external_order_id)
            VALUES ('kraken', ?, ?, ?, ?, ?, 'open', ?)");
        $stmt->execute([$pair, $orderType, $side, $volume, $price ?: null, implode(',', $txids)]);
    }

    finAuditLog('kraken_order', 'trading', ['pair' => $pair, 'side' => $side, 'volume' => $volume, 'estimated_usd' => $estimatedUSD]);

    jsonResponse([
        'success' => $response['success'],
        'order' => $response['data']['result'] ?? [],
        'errors' => $response['data']['error'] ?? [],
    ]);
}

function krakenOpenOrders(): void {
    $response = krakenPrivate('OpenOrders');
    jsonResponse(['success' => $response['success'], 'orders' => $response['data']['result']['open'] ?? []]);
}

function krakenTradeHistory(): void {
    $response = krakenPrivate('TradesHistory');
    jsonResponse(['success' => $response['success'], 'trades' => $response['data']['result']['trades'] ?? []]);
}

// ═══ COINBASE ═════════════════════════════════════════════════

function cbRequest(string $endpoint, string $method = 'GET', ?array $data = null): array {
    $timestamp = time();
    $body = $data ? json_encode($data) : '';
    $message = $timestamp . $method . $endpoint . $body;
    $signature = hash_hmac('sha256', $message, COINBASE_API_SECRET);

    return finApiRequest('https://api.coinbase.com' . $endpoint, $method, $data, [
        'CB-ACCESS-KEY: ' . COINBASE_API_KEY,
        'CB-ACCESS-SIGN: ' . $signature,
        'CB-ACCESS-TIMESTAMP: ' . $timestamp,
        'CB-VERSION: 2024-01-01',
    ]);
}

function coinbasePrices(): void {
    $pair = sanitize($_GET['pair'] ?? 'BTC-CAD', 12);
    $response = finApiRequest("https://api.coinbase.com/v2/prices/{$pair}/spot", 'GET');
    jsonResponse(['success' => $response['success'], 'price' => $response['data']['data'] ?? $response['data']]);
}

function coinbaseAccounts(): void {
    $response = cbRequest('/v2/accounts');
    $accounts = $response['data']['data'] ?? [];
    // Filter to non-zero balances
    $nonZero = array_filter($accounts, fn($a) => (float) ($a['balance']['amount'] ?? 0) > 0);
    jsonResponse(['success' => $response['success'], 'accounts' => array_values($nonZero)]);
}

function coinbaseOrder(): void {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $side = sanitize($input['side'] ?? 'buy', 10);
    $product = sanitize($input['product'] ?? 'BTC-CAD', 12);
    $amount = (float) ($input['amount'] ?? 0);
    $currency = sanitize($input['currency'] ?? 'CAD', 5);

    if ($amount <= 0) {
        jsonResponse(['error' => 'Positive amount required'], 400);
    }
    if ($amount > FIN_MAX_TRADE_USD) {
        jsonResponse(['error' => "Exceeds trade limit of \$" . FIN_MAX_TRADE_USD], 403);
    }
    if (!checkDailyTradeLimit($amount)) {
        jsonResponse(['error' => 'Daily trade limit exceeded'], 403);
    }

    $response = cbRequest("/v2/accounts/{$product}/buys", 'POST', [
        'amount' => (string) $amount,
        'currency' => $currency,
        'commit' => true,
    ]);

    if ($response['success']) {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO fin_cex_orders (exchange, pair, order_type, side, amount, status, external_order_id)
            VALUES ('coinbase', ?, 'market', ?, ?, 'filled', ?)");
        $stmt->execute([$product, $side, $amount, $response['data']['data']['id'] ?? null]);
    }

    finAuditLog('coinbase_order', 'trading', ['product' => $product, 'side' => $side, 'amount' => $amount]);
    jsonResponse(['success' => $response['success'], 'order' => $response['data']['data'] ?? $response['data']]);
}

// ═══ 1INCH DEX AGGREGATOR ════════════════════════════════════

function oneInchRequest(string $endpoint, int $chainId = 1): array {
    return finApiRequest("https://api.1inch.dev/swap/v6.0/{$chainId}" . $endpoint, 'GET', null, [
        'Authorization: Bearer ' . ONEINCH_API_KEY,
    ]);
}

function oneInchQuote(): void {
    $chainId = (int) ($_GET['chain_id'] ?? 1);
    $src = sanitize($_GET['src'] ?? '0xEeeeeEeeeEeEeeEeEeEeeEEEeeeeEeeeeeeeEEeE', 50); // ETH
    $dst = sanitize($_GET['dst'] ?? '0xA0b86991c6218b36c1d19D4a2e9Eb0cE3606eB48', 50); // USDC
    $amount = sanitize($_GET['amount'] ?? '1000000000000000000', 40); // 1 ETH in wei

    $response = oneInchRequest("/quote?src={$src}&dst={$dst}&amount={$amount}", $chainId);
    jsonResponse(['success' => $response['success'], 'quote' => $response['data'] ?? []]);
}

function oneInchSwap(): void {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $chainId = (int) ($input['chain_id'] ?? 1);
    $src = sanitize($input['src'] ?? '', 50);
    $dst = sanitize($input['dst'] ?? '', 50);
    $amount = sanitize($input['amount'] ?? '', 40);
    $from = sanitize($input['from'] ?? '', 50);
    $slippage = min((float) ($input['slippage'] ?? 1), 5); // Max 5% slippage

    if (!$src || !$dst || !$amount || !$from) {
        jsonResponse(['error' => 'src, dst, amount, and from required'], 400);
    }

    $response = oneInchRequest("/swap?src={$src}&dst={$dst}&amount={$amount}&from={$from}&slippage={$slippage}", $chainId);

    finAuditLog('oneinch_swap', 'trading', ['chain' => $chainId, 'src' => $src, 'dst' => $dst]);
    jsonResponse(['success' => $response['success'], 'swap' => $response['data'] ?? []]);
}

function oneInchTokens(): void {
    $chainId = (int) ($_GET['chain_id'] ?? 1);
    $response = oneInchRequest('/tokens', $chainId);
    jsonResponse(['success' => $response['success'], 'tokens' => $response['data']['tokens'] ?? []]);
}

// ═══ LI.FI CROSS-CHAIN ═══════════════════════════════════════

function lifiRequest(string $endpoint, string $method = 'GET', ?array $data = null): array {
    return finApiRequest('https://li.quest/v1' . $endpoint, $method, $data, [
        'x-lifi-api-key: ' . LIFI_API_KEY,
    ]);
}

function lifiQuote(): void {
    $params = http_build_query([
        'fromChain' => sanitize($_GET['from_chain'] ?? '1', 10),
        'toChain' => sanitize($_GET['to_chain'] ?? '137', 10),
        'fromToken' => sanitize($_GET['from_token'] ?? '0x0000000000000000000000000000000000000000', 50),
        'toToken' => sanitize($_GET['to_token'] ?? '0x0000000000000000000000000000000000000000', 50),
        'fromAmount' => sanitize($_GET['amount'] ?? '1000000000000000000', 40),
        'fromAddress' => sanitize($_GET['from_address'] ?? '', 50),
    ]);

    $response = lifiRequest("/quote?{$params}");
    jsonResponse(['success' => $response['success'], 'quote' => $response['data'] ?? []]);
}

function lifiRoutes(): void {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $response = lifiRequest('/advanced/routes', 'POST', [
        'fromChainId' => (int) ($input['from_chain'] ?? 1),
        'toChainId' => (int) ($input['to_chain'] ?? 137),
        'fromTokenAddress' => sanitize($input['from_token'] ?? '', 50),
        'toTokenAddress' => sanitize($input['to_token'] ?? '', 50),
        'fromAmount' => sanitize($input['amount'] ?? '', 40),
        'options' => [
            'slippage' => min((float) ($input['slippage'] ?? 0.03), 0.05),
            'order' => 'RECOMMENDED',
        ],
    ]);

    jsonResponse(['success' => $response['success'], 'routes' => $response['data']['routes'] ?? []]);
}

function lifiStatus(): void {
    $txHash = sanitize($_GET['tx_hash'] ?? '', 100);
    $bridge = sanitize($_GET['bridge'] ?? '', 30);
    $fromChain = sanitize($_GET['from_chain'] ?? '', 10);
    $toChain = sanitize($_GET['to_chain'] ?? '', 10);

    $params = http_build_query(array_filter([
        'txHash' => $txHash,
        'bridge' => $bridge,
        'fromChain' => $fromChain,
        'toChain' => $toChain,
    ]));

    $response = lifiRequest("/status?{$params}");
    jsonResponse(['success' => $response['success'], 'status' => $response['data'] ?? []]);
}

function lifiChains(): void {
    $response = lifiRequest('/chains');
    jsonResponse(['success' => $response['success'], 'chains' => $response['data']['chains'] ?? []]);
}

// ═══ EVM CHAIN ════════════════════════════════════════════════

function getEvmRpc(string $chain): string {
    return match($chain) {
        'ethereum' => FIN_ETH_RPC,
        'polygon'  => FIN_POLYGON_RPC,
        'bsc'      => FIN_BSC_RPC,
        'base'     => FIN_BASE_RPC,
        'solana'   => FIN_SOL_RPC,
        default    => FIN_ETH_RPC,
    };
}

function evmRpcCall(string $chain, string $method, array $params = []): array {
    $rpc = getEvmRpc($chain);
    return finApiRequest($rpc, 'POST', [
        'jsonrpc' => '2.0',
        'method' => $method,
        'params' => $params,
        'id' => 1,
    ]);
}

function evmBalance(): void {
    $chain = sanitize($_GET['chain'] ?? 'ethereum', 20);
    $address = sanitize($_GET['address'] ?? '', 50);

    if (!$address || !preg_match('/^0x[0-9a-fA-F]{40}$/', $address)) {
        jsonResponse(['error' => 'Valid EVM address required'], 400);
    }

    $response = evmRpcCall($chain, 'eth_getBalance', [$address, 'latest']);
    $balanceWei = $response['data']['result'] ?? '0x0';
    $balanceEth = hexdec($balanceWei) / 1e18;

    jsonResponse([
        'success' => true,
        'chain' => $chain,
        'address' => $address,
        'balance_wei' => $balanceWei,
        'balance' => $balanceEth,
    ]);
}

function evmGasPrice(): void {
    $chain = sanitize($_GET['chain'] ?? 'ethereum', 20);
    $response = evmRpcCall($chain, 'eth_gasPrice');
    $gasPriceWei = hexdec($response['data']['result'] ?? '0x0');
    $gasPriceGwei = $gasPriceWei / 1e9;

    jsonResponse([
        'success' => true,
        'chain' => $chain,
        'gas_price_wei' => $gasPriceWei,
        'gas_price_gwei' => round($gasPriceGwei, 2),
    ]);
}

function evmTokenBalances(): void {
    $chain = sanitize($_GET['chain'] ?? 'ethereum', 20);
    $address = sanitize($_GET['address'] ?? '', 50);

    if (!$address || !preg_match('/^0x[0-9a-fA-F]{40}$/', $address)) {
        jsonResponse(['error' => 'Valid EVM address required'], 400);
    }

    // ERC-20 balanceOf(address) selector = 0x70a08231
    $tokens = [
        'USDC' => '0xA0b86991c6218b36c1d19D4a2e9Eb0cE3606eB48',
        'USDT' => '0xdAC17F958D2ee523a2206206994597C13D831ec7',
        'DAI'  => '0x6B175474E89094C44Da98b954EedeAC495271d0F',
    ];

    if ($chain === 'polygon') {
        $tokens = [
            'USDC' => '0x2791Bca1f2de4661ED88A30C99A7a9449Aa84174',
            'USDT' => '0xc2132D05D31c914a87C6611C10748AEb04B58e8F',
        ];
    }

    $balances = [];
    $paddedAddress = str_pad(str_replace('0x', '', strtolower($address)), 64, '0', STR_PAD_LEFT);

    foreach ($tokens as $symbol => $contract) {
        $response = evmRpcCall($chain, 'eth_call', [
            ['to' => $contract, 'data' => '0x70a08231000000000000000000000000' . $paddedAddress],
            'latest'
        ]);
        $rawBalance = $response['data']['result'] ?? '0x0';
        $decimals = in_array($symbol, ['USDC', 'USDT']) ? 6 : 18;
        $balance = hexdec($rawBalance) / pow(10, $decimals);
        $balances[$symbol] = round($balance, $decimals > 6 ? 8 : 2);
    }

    jsonResponse(['success' => true, 'chain' => $chain, 'address' => $address, 'tokens' => $balances]);
}

// ═══ PORTFOLIO & ORDERS ═══════════════════════════════════════

function portfolio(): void {
    $db = getDB();

    // Aggregate CEX balances
    $cexBalances = [];
    try {
        $krakenResp = krakenPrivate('Balance');
        if ($krakenResp['success'] && isset($krakenResp['data']['result'])) {
            foreach ($krakenResp['data']['result'] as $asset => $balance) {
                if ((float) $balance > 0) {
                    $cexBalances['kraken'][$asset] = (float) $balance;
                }
            }
        }
    } catch (Exception $e) { /* skip */ }

    // Recent orders
    $orders = $db->query("SELECT exchange, pair, side, amount, filled_price, status, created_at
        FROM fin_cex_orders ORDER BY created_at DESC LIMIT 20")->fetchAll();

    // DeFi balances from existing table
    $defiBalances = [];
    try {
        $defi = $db->query("SELECT chain, token_symbol, SUM(amount) as total
            FROM defi_wallets GROUP BY chain, token_symbol HAVING total > 0")->fetchAll();
        foreach ($defi as $d) {
            $defiBalances[$d['chain']][$d['token_symbol']] = (float) $d['total'];
        }
    } catch (Exception $e) { /* skip */ }

    jsonResponse([
        'success' => true,
        'cex_balances' => $cexBalances,
        'defi_balances' => $defiBalances,
        'recent_orders' => $orders,
    ]);
}

function orderHistory(): void {
    $db = getDB();
    $exchange = sanitize($_GET['exchange'] ?? '', 20);
    $limit = min((int) ($_GET['limit'] ?? 50), 200);

    $sql = "SELECT * FROM fin_cex_orders WHERE 1=1";
    $params = [];
    if ($exchange) { $sql .= " AND exchange = ?"; $params[] = $exchange; }
    $sql .= " ORDER BY created_at DESC LIMIT ?";
    $params[] = $limit;

    $stmt = $db->prepare($sql);
    dbExecute($stmt, $params);

    jsonResponse(['success' => true, 'orders' => $stmt->fetchAll()]);
}

function dailyLimitCheck(): void {
    $remainingUSD = getRemainingDailyLimit();
    jsonResponse([
        'success' => true,
        'daily_limit' => FIN_DAILY_TRADE_LIMIT,
        'used_today' => FIN_DAILY_TRADE_LIMIT - $remainingUSD,
        'remaining' => $remainingUSD,
    ]);
}

// ═══ HELPERS ══════════════════════════════════════════════════

function estimateOrderValueUSD(string $pair, float $volume, float $price): float {
    // If price is given, use it directly
    if ($price > 0) {
        $value = $volume * $price;
        // Convert CAD to USD rough estimate if CAD pair
        if (str_contains($pair, 'CAD')) $value *= 0.74;
        return round($value, 2);
    }

    // Fetch current price for market orders
    $response = krakenPublic('Ticker', ['pair' => $pair]);
    if ($response['success'] && !empty($response['data']['result'])) {
        $ticker = reset($response['data']['result']);
        $lastPrice = (float) ($ticker['c'][0] ?? 0);
        $value = $volume * $lastPrice;
        if (str_contains($pair, 'CAD')) $value *= 0.74;
        return round($value, 2);
    }

    // Fallback: assume conservative BTC price
    return round($volume * 60000, 2);
}

function checkDailyTradeLimit(float $estimatedUSD): bool {
    return getRemainingDailyLimit() >= $estimatedUSD;
}

function getRemainingDailyLimit(): float {
    $db = getDB();
    try {
        $stmt = $db->query("SELECT COALESCE(SUM(amount * COALESCE(filled_price, price, 0)), 0) as total
            FROM fin_cex_orders
            WHERE DATE(created_at) = CURDATE() AND status != 'cancelled'");
        $usedToday = (float) $stmt->fetchColumn();
    } catch (Exception $e) {
        $usedToday = 0;
    }
    return max(0, FIN_DAILY_TRADE_LIMIT - $usedToday);
}
