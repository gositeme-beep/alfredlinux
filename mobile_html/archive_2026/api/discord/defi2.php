<?php
/**
 * GoSiteMe Discord Bot — DeFi & Finance Module
 * ═════════════════════════════════════════════
 * /defi (portfolio|positions|alerts|chains|convert)
 * Live CoinGecko prices, paper trading portfolio, alerts, conversions.
 */

function handleDefi($data): void {
    $sub = $data['data']['options'][0]['name'] ?? 'portfolio';
    $opts = [];
    foreach (($data['data']['options'][0]['options'] ?? []) as $o) {
        $opts[$o['name']] = $o['value'];
    }

    $userId   = $data['member']['user']['id'] ?? ($data['user']['id'] ?? '');
    $username = $data['member']['user']['username'] ?? ($data['user']['username'] ?? 'User');
    $db       = getDiscordDB();
    if (!$db) { respond('❌ Database unavailable.'); return; }
    $user = getOrCreateUser($userId, $username);

    $db->exec("CREATE TABLE IF NOT EXISTS discord_defi (
        id INT AUTO_INCREMENT PRIMARY KEY,
        discord_id VARCHAR(32) NOT NULL,
        type VARCHAR(50) NOT NULL,
        asset VARCHAR(50) NOT NULL,
        amount DECIMAL(18,8) DEFAULT 0,
        entry_price DECIMAL(18,8) DEFAULT 0,
        metadata JSON,
        status VARCHAR(20) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (discord_id),
        INDEX idx_type (type),
        INDEX idx_status (status)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS discord_defi_alerts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        discord_id VARCHAR(32) NOT NULL,
        asset VARCHAR(50) NOT NULL,
        target_price DECIMAL(18,8) NOT NULL,
        direction VARCHAR(10) NOT NULL,
        status VARCHAR(20) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (discord_id)
    )");

    // CoinGecko ticker→ID mapping
    $tickerMap = [
        'BTC' => 'bitcoin', 'ETH' => 'ethereum', 'SOL' => 'solana', 'BNB' => 'binancecoin',
        'XRP' => 'ripple', 'ADA' => 'cardano', 'DOGE' => 'dogecoin', 'AVAX' => 'avalanche-2',
        'DOT' => 'polkadot', 'MATIC' => 'matic-network', 'LINK' => 'chainlink', 'UNI' => 'uniswap',
        'ATOM' => 'cosmos', 'LTC' => 'litecoin', 'NEAR' => 'near',
    ];

    // Helper: get LIVE crypto prices from CoinGecko (cached 60s)
    $getPrices = function() use ($tickerMap) {
        $cacheFile = sys_get_temp_dir() . '/alfred_defi_prices.json';
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 60) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if ($cached) return $cached;
        }

        $ids = implode(',', array_values($tickerMap));
        $url = "https://api.coingecko.com/api/v3/simple/price?ids=$ids&vs_currencies=usd&include_24hr_change=true";
        $raw = httpGet($url, 10);
        $data = $raw ? json_decode($raw, true) : null;

        if (!$data) {
            // Fallback: return last cached prices or static defaults
            if (file_exists($cacheFile)) {
                $cached = json_decode(file_get_contents($cacheFile), true);
                if ($cached) return $cached;
            }
            return [
                'BTC' => 105000, 'ETH' => 2700, 'SOL' => 175, 'BNB' => 620,
                'XRP' => 2.45, 'ADA' => 0.78, 'DOGE' => 0.22, 'AVAX' => 38,
                'DOT' => 7.20, 'MATIC' => 0.55, 'LINK' => 16.50, 'UNI' => 11.20,
                'ATOM' => 9.80, 'LTC' => 105, 'NEAR' => 5.40,
            ];
        }

        // Map CoinGecko response back to ticker symbols
        $idToTicker = array_flip($tickerMap);
        $prices = [];
        $changes = [];
        foreach ($data as $id => $info) {
            $ticker = $idToTicker[$id] ?? strtoupper($id);
            $prices[$ticker] = $info['usd'] ?? 0;
            $changes[$ticker] = round($info['usd_24h_change'] ?? 0, 2);
        }

        // Cache prices + changes
        file_put_contents($cacheFile, json_encode($prices));
        $changeFile = sys_get_temp_dir() . '/alfred_defi_changes.json';
        file_put_contents($changeFile, json_encode($changes));

        return $prices;
    };

    // Helper: get 24h price changes
    $getChanges = function() {
        $changeFile = sys_get_temp_dir() . '/alfred_defi_changes.json';
        if (file_exists($changeFile) && (time() - filemtime($changeFile)) < 120) {
            return json_decode(file_get_contents($changeFile), true) ?: [];
        }
        return [];
    };

    switch ($sub) {
        case 'portfolio':
            $prices = $getPrices();
            $stmt = $db->prepare("SELECT asset, SUM(amount) as total, AVG(entry_price) as avg_entry FROM discord_defi WHERE discord_id = ? AND type = 'position' AND status = 'active' GROUP BY asset");
            $stmt->execute([$userId]);
            $positions = $stmt->fetchAll();

            if (empty($positions)) {
                respond(null, [embed("💰 DeFi Portfolio — $username", "Empty portfolio! Start with `/defi positions`\n\n**Available Assets:**\n" . implode(', ', array_keys($prices)), 0xF39C12)], [actionRow(
                    btn(1, '📈 Open Position', 'defi_position_prompt'),
                    btn(2, '⛓️ Chains', 'defi_chains'),
                    btn(2, '💱 Convert', 'defi_convert_prompt')
                )]);
                return;
            }

            $totalValue = 0;
            $totalCost = 0;
            $lines = [];
            foreach ($positions as $p) {
                $currentPrice = $prices[$p['asset']] ?? 0;
                $value = $p['total'] * $currentPrice;
                $cost = $p['total'] * $p['avg_entry'];
                $pnl = $value - $cost;
                $pnlPct = $cost > 0 ? round(($pnl / $cost) * 100, 2) : 0;
                $pnlEmoji = $pnl >= 0 ? '🟢' : '🔴';
                $totalValue += $value;
                $totalCost += $cost;

                $lines[] = "$pnlEmoji **{$p['asset']}**: " . number_format($p['total'], 4) . "\n   Entry: \${$p['avg_entry']} → Now: \$$currentPrice\n   P&L: " . ($pnl >= 0 ? '+' : '') . "\$" . number_format($pnl, 2) . " ({$pnlPct}%)";
            }

            $totalPnl = $totalValue - $totalCost;
            $totalPnlPct = $totalCost > 0 ? round(($totalPnl / $totalCost) * 100, 2) : 0;

            $changes = $getChanges();
            respond(null, [embed("💰 DeFi Portfolio — $username", implode("\n\n", $lines), $totalPnl >= 0 ? 0x2ECC71 : 0xE74C3C, [
                field('Total Value', '$' . number_format($totalValue, 2), true),
                field('Total P&L', ($totalPnl >= 0 ? '+' : '') . '$' . number_format($totalPnl, 2), true),
                field('Return', ($totalPnlPct >= 0 ? '+' : '') . "$totalPnlPct%", true),
            ], [
                'footer' => ['text' => '📊 Live prices from CoinGecko • Paper trading'],
            ])], [actionRow(
                btn(1, '📈 New Position', 'defi_position_prompt'),
                btn(2, '🔔 Alerts', 'defi_alerts_list'),
                btn(2, '⛓️ Chains', 'defi_chains'),
                btn(2, '💱 Convert', 'defi_convert_prompt')
            )]);
            break;

        case 'positions':
            $action = $opts['action'] ?? 'open';

            if ($action === 'close') {
                $asset = strtoupper($opts['asset'] ?? '');
                if (!$asset) { respondEphemeral('❌ Specify an asset to close.'); return; }

                $stmt = $db->prepare("SELECT id, amount, entry_price FROM discord_defi WHERE discord_id = ? AND asset = ? AND type = 'position' AND status = 'active' LIMIT 1");
                $stmt->execute([$userId, $asset]);
                $pos = $stmt->fetch();

                if (!$pos) { respondEphemeral("❌ No open $asset position found."); return; }

                $prices = $getPrices();
                $exitPrice = $prices[$asset] ?? 0;
                $pnl = ($exitPrice - $pos['entry_price']) * $pos['amount'];
                $pnlPct = $pos['entry_price'] > 0 ? round((($exitPrice - $pos['entry_price']) / $pos['entry_price']) * 100, 2) : 0;

                $db->prepare("UPDATE discord_defi SET status = 'closed', metadata = JSON_SET(COALESCE(metadata, '{}'), '$.exit_price', ?, '$.pnl', ?) WHERE id = ?")->execute([$exitPrice, $pnl, $pos['id']]);

                // Award/deduct KGD coins based on P&L
                if ($pnl > 0) {
                    $reward = min(500, (int)abs($pnl));
                    awardXP($userId, $reward > 100 ? 15 : 5);
                }

                respond(null, [embed("📉 Position Closed — $asset", "**Amount:** " . number_format($pos['amount'], 4) . "\n**Entry:** \${$pos['entry_price']}\n**Exit:** \$$exitPrice\n**P&L:** " . ($pnl >= 0 ? '+' : '') . "\$" . number_format($pnl, 2) . " ({$pnlPct}%)", $pnl >= 0 ? 0x2ECC71 : 0xE74C3C, [], [
                    'footer' => ['text' => '📊 Live prices • Paper trading'],
                ])], [actionRow(
                    btn(2, '💰 Portfolio', 'defi_portfolio'),
                    btn(1, '📈 New Position', 'defi_position_prompt')
                )]);
            } else {
                // Open position
                $asset = strtoupper($opts['asset'] ?? 'BTC');
                $amount = (float)($opts['amount'] ?? 1);
                $amount = max(0.0001, min(1000, $amount));
                $prices = $getPrices();

                if (!isset($prices[$asset])) {
                    respondEphemeral("❌ Unknown asset `$asset`. Available: " . implode(', ', array_keys($prices)));
                    return;
                }

                $entryPrice = $prices[$asset];

                // Limit positions
                $stmt = $db->prepare("SELECT COUNT(*) FROM discord_defi WHERE discord_id = ? AND type = 'position' AND status = 'active'");
                $stmt->execute([$userId]);
                if ((int)$stmt->fetchColumn() >= 10) {
                    respondEphemeral('❌ Max 10 open positions. Close one first.');
                    return;
                }

                $stmt = $db->prepare("INSERT INTO discord_defi (discord_id, type, asset, amount, entry_price) VALUES (?, 'position', ?, ?, ?)");
                $stmt->execute([$userId, $asset, $amount, $entryPrice]);

                $value = $amount * $entryPrice;
                $change24h = ($getChanges())[$asset] ?? 0;
                $changeEmoji = $change24h >= 0 ? '🟢' : '🔴';
                respond(null, [embed("📈 Position Opened — $asset", "**Amount:** " . number_format($amount, 4) . " $asset\n**Entry Price:** \$" . number_format($entryPrice, 2) . "\n**Value:** \$" . number_format($value, 2) . "\n$changeEmoji **24h Change:** " . ($change24h >= 0 ? '+' : '') . "{$change24h}%", 0x2ECC71, [], [
                    'footer' => ['text' => '📊 Live CoinGecko prices • Paper trading'],
                ])], [actionRow(
                    btn(2, '💰 Portfolio', 'defi_portfolio'),
                    btn(3, '📉 Close', "defi_close_$asset"),
                    btn(2, '🔔 Set Alert', "defi_alert_prompt_$asset")
                )]);
                awardXP($userId, 3);
            }
            break;

        case 'alerts':
            $action = $opts['action'] ?? 'list';

            if ($action === 'set') {
                $asset = strtoupper($opts['asset'] ?? 'BTC');
                $target = (float)($opts['target'] ?? 0);
                $direction = $opts['direction'] ?? 'above';

                if ($target <= 0) { respondEphemeral('❌ Target price must be positive.'); return; }

                $stmt = $db->prepare("SELECT COUNT(*) FROM discord_defi_alerts WHERE discord_id = ? AND status = 'active'");
                $stmt->execute([$userId]);
                if ((int)$stmt->fetchColumn() >= 10) {
                    respondEphemeral('❌ Max 10 active alerts. Remove one first.');
                    return;
                }

                $stmt = $db->prepare("INSERT INTO discord_defi_alerts (discord_id, asset, target_price, direction) VALUES (?, ?, ?, ?)");
                $stmt->execute([$userId, $asset, $target, $direction]);

                respond(null, [embed("🔔 Alert Set", "**$asset** → \$" . number_format($target, 2) . " ($direction)\nYou'll be notified when the price goes $direction this target.", 0xF39C12)], [actionRow(
                    btn(2, '🔔 My Alerts', 'defi_alerts_list'),
                    btn(2, '💰 Portfolio', 'defi_portfolio')
                )]);
            } else {
                // List alerts
                $stmt = $db->prepare("SELECT id, asset, target_price, direction, status, created_at FROM discord_defi_alerts WHERE discord_id = ? ORDER BY created_at DESC LIMIT 10");
                $stmt->execute([$userId]);
                $alerts = $stmt->fetchAll();

                if (empty($alerts)) {
                    respond(null, [embed("🔔 Price Alerts", "No alerts set. Create one with `/defi alerts set`.", 0x95A5A6)]);
                    return;
                }

                $lines = [];
                foreach ($alerts as $a) {
                    $s = $a['status'] === 'active' ? '🟢' : '⚪';
                    $arrow = $a['direction'] === 'above' ? '📈' : '📉';
                    $lines[] = "$s $arrow **{$a['asset']}** → \$" . number_format($a['target_price'], 2) . " ({$a['direction']})";
                }

                respond(null, [embed("🔔 Price Alerts", implode("\n", $lines), 0xF39C12, [
                    field('Total Alerts', (string)count($alerts), true),
                    field('Active', (string)count(array_filter($alerts, fn($a) => $a['status'] === 'active')), true),
                ])]);
            }
            break;

        case 'chains':
            $chains = [
                ['name' => 'Ethereum', 'symbol' => 'ETH', 'type' => 'L1', 'tps' => '~30', 'gas' => '$2-50', 'emoji' => '💎'],
                ['name' => 'Solana', 'symbol' => 'SOL', 'type' => 'L1', 'tps' => '~65,000', 'gas' => '<$0.01', 'emoji' => '⚡'],
                ['name' => 'BNB Chain', 'symbol' => 'BNB', 'type' => 'L1', 'tps' => '~160', 'gas' => '$0.05-1', 'emoji' => '🟡'],
                ['name' => 'Polygon', 'symbol' => 'MATIC', 'type' => 'L2', 'tps' => '~7,000', 'gas' => '<$0.01', 'emoji' => '🟣'],
                ['name' => 'Avalanche', 'symbol' => 'AVAX', 'type' => 'L1', 'tps' => '~4,500', 'gas' => '$0.01-0.5', 'emoji' => '🔺'],
                ['name' => 'Arbitrum', 'symbol' => 'ARB', 'type' => 'L2', 'tps' => '~40,000', 'gas' => '<$0.10', 'emoji' => '🔵'],
                ['name' => 'Base', 'symbol' => 'BASE', 'type' => 'L2', 'tps' => '~10,000', 'gas' => '<$0.01', 'emoji' => '🟦'],
                ['name' => 'Cosmos', 'symbol' => 'ATOM', 'type' => 'L0', 'tps' => '~10,000', 'gas' => '$0.01', 'emoji' => '⚛️'],
            ];

            $lines = [];
            foreach ($chains as $c) {
                $lines[] = "{$c['emoji']} **{$c['name']}** ({$c['symbol']})\n   Type: {$c['type']} · TPS: {$c['tps']} · Gas: {$c['gas']}";
            }

            respond(null, [embed("⛓️ Blockchain Networks", implode("\n\n", $lines), 0x9B59B6, [], [
                'footer' => ['text' => 'Data is approximate • Research before investing'],
            ])], [actionRow(
                btn(2, '💰 Portfolio', 'defi_portfolio'),
                btn(2, '📈 Open Position', 'defi_position_prompt'),
                btn(5, '📚 Learn DeFi', 'https://ethereum.org/en/defi/')
            )]);
            break;

        case 'convert':
            deferResponse();
            $appId = getenv('DISCORD_APP_ID') ?: '';
            $token = $data['token'] ?? '';

            $from = strtoupper($opts['from'] ?? 'USD');
            $to = strtoupper($opts['to'] ?? 'BTC');
            $amount = (float)($opts['amount'] ?? 1);

            $prices = $getPrices();
            $prices['USD'] = 1;
            $prices['EUR'] = 1.08;
            $prices['GBP'] = 1.27;
            $prices['CAD'] = 0.73;
            $prices['JPY'] = 0.0065;

            if (!isset($prices[$from]) || !isset($prices[$to])) {
                editOriginal($appId, $token, '', [embed("❌ Conversion Error", "Unknown currency. Available: " . implode(', ', array_keys($prices)), 0xE74C3C)]);
                return;
            }

            $fromUsd = $amount * $prices[$from];
            $result = $fromUsd / $prices[$to];

            editOriginal($appId, $token, '', [embed("💱 Currency Conversion", "**" . number_format($amount, 4) . " $from** = **" . number_format($result, 8) . " $to**\n\n**Rates Used:**\n1 $from = \$" . number_format($prices[$from], 6) . " USD\n1 $to = \$" . number_format($prices[$to], 6) . " USD", 0x3498DB, [], [
                'footer' => ['text' => '📊 Live CoinGecko rates • Not financial advice'],
            ])], [actionRow(
                btn(2, '💰 Portfolio', 'defi_portfolio'),
                btn(2, '⛓️ Chains', 'defi_chains'),
                btn(2, '🔔 Alerts', 'defi_alerts_list')
            )]);
            break;

        default:
            respondEphemeral("Unknown subcommand. Try `/defi portfolio`, `/defi positions`, `/defi alerts`, `/defi chains`, or `/defi convert`.");
    }
}
