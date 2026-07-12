<?php
/**
 * GoSiteMe Discord Bot — Finance Module
 * ══════════════════════════════════════
 * /stock     — Real-time crypto/stock prices (CoinGecko)
 * /portfolio — View your Kingdom financial overview
 *
 * UNIQUE: Real-time market data + integrated with Kingdom economy.
 */

function handleStock(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $ticker = '';
    foreach ($opts as $o) { if ($o['name'] === 'ticker') $ticker = strtolower($o['value']); }

    $userId = $data['member']['user']['id'] ?? '0';
    $appId = $data['application_id'] ?? '';
    $token = $data['token'] ?? '';
    $channelId = $data['channel_id'] ?? '';

    if (!$ticker) { respond("📈 Usage: `/stock ticker:bitcoin` or `/stock ticker:ethereum`"); return; }

    // Map common aliases
    $aliases = [
        'btc' => 'bitcoin', 'eth' => 'ethereum', 'sol' => 'solana',
        'ada' => 'cardano', 'doge' => 'dogecoin', 'xrp' => 'ripple',
        'dot' => 'polkadot', 'matic' => 'matic-network', 'avax' => 'avalanche-2',
        'link' => 'chainlink', 'bnb' => 'binancecoin', 'ltc' => 'litecoin',
        'shib' => 'shiba-inu', 'uni' => 'uniswap', 'atom' => 'cosmos',
    ];
    $coinId = $aliases[$ticker] ?? $ticker;

    deferResponse();

    $url = "https://api.coingecko.com/api/v3/coins/$coinId?localization=false&tickers=false&community_data=false&developer_data=false";
    $json = httpGet($url, 15);

    if (!$json) {
        followUp($appId, $token, "❌ Could not find data for `$ticker`. Try the full name (e.g., `bitcoin`, `ethereum`).");
        return;
    }

    $coin = json_decode($json, true);
    if (isset($coin['error'])) {
        followUp($appId, $token, "❌ Unknown ticker: `$ticker`. Try: bitcoin, ethereum, solana, dogecoin");
        return;
    }

    $name = $coin['name'] ?? $ticker;
    $symbol = strtoupper($coin['symbol'] ?? $ticker);
    $price = $coin['market_data']['current_price']['usd'] ?? 0;
    $change24h = $coin['market_data']['price_change_percentage_24h'] ?? 0;
    $change7d = $coin['market_data']['price_change_percentage_7d'] ?? 0;
    $change30d = $coin['market_data']['price_change_percentage_30d'] ?? 0;
    $marketCap = $coin['market_data']['market_cap']['usd'] ?? 0;
    $volume = $coin['market_data']['total_volume']['usd'] ?? 0;
    $high24 = $coin['market_data']['high_24h']['usd'] ?? 0;
    $low24 = $coin['market_data']['low_24h']['usd'] ?? 0;
    $ath = $coin['market_data']['ath']['usd'] ?? 0;
    $athChange = $coin['market_data']['ath_change_percentage']['usd'] ?? 0;
    $rank = $coin['market_cap_rank'] ?? '?';
    $image = $coin['image']['small'] ?? '';

    $changeEmoji = $change24h >= 0 ? '📈' : '📉';
    $priceStr = $price >= 1 ? number_format($price, 2) : number_format($price, 6);
    $color = $change24h >= 0 ? 0x57F287 : 0xED4245;

    $fields = [
        field('💵 Price', "\$$priceStr", true),
        field("$changeEmoji 24h", sprintf('%+.2f%%', $change24h), true),
        field('📊 7d', sprintf('%+.2f%%', $change7d), true),
        field('📅 30d', sprintf('%+.2f%%', $change30d), true),
        field('🏆 Rank', "#$rank", true),
        field('📊 Market Cap', '$' . formatNumber($marketCap), true),
        field('📈 24h Volume', '$' . formatNumber($volume), true),
        field('⬆️ 24h High', '$' . number_format($high24, 2), true),
        field('⬇️ 24h Low', '$' . number_format($low24, 2), true),
        field('🏅 ATH', '$' . number_format($ath, 2) . " (" . sprintf('%+.1f%%', $athChange) . ")", false),
    ];

    $extra = [];
    if ($image) $extra['thumbnail'] = ['url' => $image];
    $extra['footer'] = ['text' => 'Data from CoinGecko • Updates every 60s'];

    followUp($appId, $token, '', [embed(
        "$changeEmoji $name ($symbol)",
        '',
        $color,
        $fields,
        $extra
    )], [actionRow(
        btn(2, '🔄 Refresh', "stock_refresh_$coinId"),
        btn(5, '📊 CoinGecko', "https://www.coingecko.com/en/coins/$coinId")
    )]);

    awardXP($userId, 3, $appId, $token, $channelId);
}


function handlePortfolio(array $data): void {
    $userId = $data['member']['user']['id'] ?? '0';
    $appId = $data['application_id'] ?? '';
    $token = $data['token'] ?? '';
    $globalName = $data['member']['user']['global_name'] ?? $data['member']['user']['username'] ?? 'User';

    deferResponse();

    $user = getOrCreateUser($userId, $globalName);
    $pdo = getDiscordDB();

    $balance = $user['kgd_balance'] ?? 0;
    $totalEarned = $user['total_earned'] ?? 0;
    $totalSpent = $user['total_spent'] ?? 0;
    $level = $user['level'] ?? 1;

    // Get recent transactions
    $recentTxns = [];
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT entry_type, amount, reason, created_at FROM discord_economy WHERE discord_id = ? ORDER BY id DESC LIMIT 10");
        $stmt->execute([$userId]);
        $recentTxns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $txnText = '';
    foreach ($recentTxns as $tx) {
        $icon = $tx['entry_type'] === 'earn' || $tx['entry_type'] === 'signup_bonus' ? '🟢' : '🔴';
        $sign = $tx['entry_type'] === 'earn' || $tx['entry_type'] === 'signup_bonus' ? '+' : '-';
        $txnText .= "$icon {$sign}{$tx['amount']} KGD — {$tx['reason']}\n";
    }
    if (!$txnText) $txnText = 'No transactions yet.';

    // Get inventory count
    $invCount = 0;
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM discord_inventory WHERE discord_id = ?");
        $stmt->execute([$userId]);
        $invCount = $stmt->fetchColumn();
    }

    $fields = [
        field('💰 Balance', number_format($balance) . ' KGD', true),
        field('📈 Total Earned', number_format($totalEarned) . ' KGD', true),
        field('📉 Total Spent', number_format($totalSpent) . ' KGD', true),
        field('⭐ Level', (string)$level, true),
        field('🎒 Items', (string)$invCount, true),
        field('📊 Net Worth', number_format($balance + ($invCount * 100)) . ' KGD*', true),
    ];

    followUp($appId, $token, '', [embed(
        "💎 Financial Portfolio — $globalName",
        "**Recent Transactions:**\n$txnText",
        0xF1C40F,
        $fields,
        ['footer' => ['text' => '*Net worth includes estimated item values']]
    )], [actionRow(
        btn(2, '💰 Balance', 'coins_balance'),
        btn(2, '🏪 Shop', 'shop_browse'),
        btn(2, '🏆 Leaderboard', 'leaderboard_view')
    )]);
}


function formatNumber(float $n): string {
    if ($n >= 1e12) return number_format($n / 1e12, 2) . 'T';
    if ($n >= 1e9) return number_format($n / 1e9, 2) . 'B';
    if ($n >= 1e6) return number_format($n / 1e6, 2) . 'M';
    if ($n >= 1e3) return number_format($n / 1e3, 1) . 'K';
    return number_format($n, 2);
}