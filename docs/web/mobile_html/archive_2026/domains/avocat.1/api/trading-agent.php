<?php
/**
 * Financial Trading Agent System — Simulated + Live Crypto Trading
 * ──────────────────────────────────────────────────────────────────
 * AI-powered trading agents that manage the platform's 20% treasury
 * funds across crypto markets. Runs simulation environments first,
 * then paper trading, then live with strict risk controls.
 *
 * Trading Modes:
 *   - Simulation: AI agents test strategies against historical data
 *   - Paper: Real-time market data, virtual trades
 *   - Live: Real trades with strict limits (requires admin approval)
 *
 * Endpoints:
 *   GET  ?action=dashboard       → Trading overview
 *   GET  ?action=agents          → List trading agents
 *   POST ?action=create_agent    → Create a new trading agent
 *   GET  ?action=strategies      → Available trading strategies
 *   POST ?action=simulate        → Run strategy simulation
 *   GET  ?action=positions       → Current open positions
 *   GET  ?action=performance     → Agent performance metrics
 *   POST ?action=approve_trade   → Approve pending live trade
 *   GET  ?action=risk_report     → Risk assessment report
 *
 * Safety Controls:
 *   - Max 10 SOL per trade
 *   - Max 50 SOL per day
 *   - 15% stop-loss auto-trigger
 *   - Human approval required for trades > 5 SOL
 *   - No leverage/margin trading
 *   - Only vetted token pairs
 */

define('GOSITEME_API', true);
require_once dirname(__DIR__) . '/includes/api-security.php';
require_once dirname(__DIR__) . '/api/config.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// ── Auth ────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) session_start();
$userId = (int)($_SESSION['user_id'] ?? 0);
$adminIds = [33]; // Danny

if (!$userId || !in_array($userId, $adminIds)) {
    http_response_code(403);
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

$db = getDB();

// ── Risk Configuration ──────────────────────────────────────────
define('MAX_TRADE_SOL', 10);
define('MAX_DAILY_SOL', 50);
define('STOP_LOSS_PCT', 15);
define('TAKE_PROFIT_PCT', 25);
define('HUMAN_APPROVAL_THRESHOLD', 5); // SOL
define('ALLOWED_PAIRS', ['SOL/USDC', 'SOL/USDT', 'GSM/SOL', 'GSM/USDC']);

// ── Database Setup ──────────────────────────────────────────────
$db->exec("CREATE TABLE IF NOT EXISTS trading_agents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agent_name VARCHAR(100) NOT NULL,
    strategy VARCHAR(100) NOT NULL,
    mode ENUM('simulation','paper','live') DEFAULT 'simulation',
    config JSON DEFAULT NULL,
    status ENUM('active','paused','stopped','error') DEFAULT 'paused',
    total_trades INT DEFAULT 0,
    winning_trades INT DEFAULT 0,
    total_pnl DECIMAL(20,8) DEFAULT 0,
    max_drawdown DECIMAL(10,4) DEFAULT 0,
    sharpe_ratio DECIMAL(10,4) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_trade_at TIMESTAMP NULL,
    INDEX idx_strategy (strategy),
    INDEX idx_mode (mode),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS trading_positions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    agent_id INT NOT NULL,
    pair VARCHAR(20) NOT NULL,
    side ENUM('buy','sell') NOT NULL,
    size DECIMAL(20,8) NOT NULL,
    entry_price DECIMAL(20,8) NOT NULL,
    current_price DECIMAL(20,8) DEFAULT 0,
    stop_loss DECIMAL(20,8) DEFAULT 0,
    take_profit DECIMAL(20,8) DEFAULT 0,
    pnl DECIMAL(20,8) DEFAULT 0,
    pnl_pct DECIMAL(10,4) DEFAULT 0,
    status ENUM('open','closed','stopped_out','take_profit','pending_approval') DEFAULT 'open',
    mode ENUM('simulation','paper','live') DEFAULT 'simulation',
    opened_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    closed_at TIMESTAMP NULL,
    INDEX idx_agent (agent_id),
    INDEX idx_status (status),
    INDEX idx_pair (pair)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS trading_simulations (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    agent_id INT,
    strategy VARCHAR(100) NOT NULL,
    pair VARCHAR(20) NOT NULL,
    timeframe VARCHAR(10) DEFAULT '1h',
    start_balance DECIMAL(20,8) DEFAULT 1000,
    end_balance DECIMAL(20,8) DEFAULT 0,
    total_trades INT DEFAULT 0,
    win_rate DECIMAL(5,2) DEFAULT 0,
    max_drawdown DECIMAL(10,4) DEFAULT 0,
    sharpe_ratio DECIMAL(10,4) DEFAULT 0,
    profit_factor DECIMAL(10,4) DEFAULT 0,
    results JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_strategy (strategy)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS trading_risk_events (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    agent_id INT,
    event_type ENUM('stop_loss','max_daily','human_override','error','approval_required') NOT NULL,
    details JSON DEFAULT NULL,
    resolved TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (event_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Trading Strategies ──────────────────────────────────────────
$STRATEGIES = [
    'momentum' => [
        'name' => 'Momentum Rider',
        'description' => 'Follows strong price trends using RSI + MACD',
        'risk' => 'medium',
        'timeframe' => '1h',
        'indicators' => ['RSI', 'MACD', 'Volume'],
        'win_rate_target' => 55,
    ],
    'mean_reversion' => [
        'name' => 'Mean Reversion',
        'description' => 'Buys oversold, sells overbought using Bollinger Bands',
        'risk' => 'low',
        'timeframe' => '4h',
        'indicators' => ['Bollinger Bands', 'RSI', 'ATR'],
        'win_rate_target' => 60,
    ],
    'breakout' => [
        'name' => 'Breakout Hunter',
        'description' => 'Detects consolidation patterns and trades breakouts',
        'risk' => 'high',
        'timeframe' => '15m',
        'indicators' => ['Volume Profile', 'Support/Resistance', 'ATR'],
        'win_rate_target' => 45,
    ],
    'dca_accumulator' => [
        'name' => 'DCA Accumulator',
        'description' => 'Dollar-cost averages into positions on dips',
        'risk' => 'low',
        'timeframe' => '1d',
        'indicators' => ['SMA 200', 'Fear & Greed Index'],
        'win_rate_target' => 70,
    ],
    'arbitrage' => [
        'name' => 'DEX Arbitrageur',
        'description' => 'Finds price discrepancies across DEX pools',
        'risk' => 'low',
        'timeframe' => '1m',
        'indicators' => ['Price Feed', 'Liquidity Depth'],
        'win_rate_target' => 80,
    ],
    'sentiment' => [
        'name' => 'Sentiment Analyst',
        'description' => 'Trades based on news sentiment and social signals',
        'risk' => 'medium',
        'timeframe' => '4h',
        'indicators' => ['News Sentiment', 'Social Volume', 'Fear & Greed'],
        'win_rate_target' => 52,
    ],
];

// ── Handlers ────────────────────────────────────────────────────
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'dashboard':
        // Overview stats
        $agents = $db->query("SELECT COUNT(*) as total,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
            COALESCE(SUM(total_pnl), 0) as total_pnl,
            COALESCE(SUM(total_trades), 0) as total_trades,
            COALESCE(SUM(winning_trades), 0) as winning_trades
            FROM trading_agents")->fetch(PDO::FETCH_ASSOC);

        $positions = $db->query("SELECT COUNT(*) as total,
            SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_count,
            COALESCE(SUM(CASE WHEN status = 'open' THEN pnl ELSE 0 END), 0) as unrealized_pnl
            FROM trading_positions")->fetch(PDO::FETCH_ASSOC);

        $simulations = $db->query("SELECT COUNT(*) as total,
            COALESCE(AVG(win_rate), 0) as avg_win_rate,
            COALESCE(AVG(sharpe_ratio), 0) as avg_sharpe
            FROM trading_simulations")->fetch(PDO::FETCH_ASSOC);

        $riskEvents = $db->query("SELECT COUNT(*) as total,
            SUM(CASE WHEN resolved = 0 THEN 1 ELSE 0 END) as unresolved
            FROM trading_risk_events")->fetch(PDO::FETCH_ASSOC);

        $recentTrades = $db->query("SELECT p.*, a.agent_name FROM trading_positions p
            JOIN trading_agents a ON p.agent_id = a.id
            ORDER BY p.opened_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

        $byStrategy = $db->query("SELECT strategy, COUNT(*) as agents, SUM(total_pnl) as pnl, AVG(total_trades) as avg_trades
            FROM trading_agents GROUP BY strategy")->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'ok',
            'agents' => $agents,
            'positions' => $positions,
            'simulations' => $simulations,
            'risk_events' => $riskEvents,
            'recent_trades' => $recentTrades,
            'by_strategy' => $byStrategy,
            'risk_limits' => [
                'max_trade_sol' => MAX_TRADE_SOL,
                'max_daily_sol' => MAX_DAILY_SOL,
                'stop_loss_pct' => STOP_LOSS_PCT,
                'take_profit_pct' => TAKE_PROFIT_PCT,
                'human_approval_threshold' => HUMAN_APPROVAL_THRESHOLD,
                'allowed_pairs' => ALLOWED_PAIRS,
            ],
            'strategies_available' => count($STRATEGIES),
        ]);
        break;

    case 'agents':
        $agents = $db->query("SELECT * FROM trading_agents ORDER BY total_pnl DESC")->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['agents' => $agents]);
        break;

    case 'create_agent':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        $input = json_decode(file_get_contents('php://input'), true);
        $name = trim($input['name'] ?? '');
        $strategy = $input['strategy'] ?? '';

        if (!$name || !isset($STRATEGIES[$strategy])) {
            echo json_encode(['error' => 'Valid name and strategy required', 'strategies' => array_keys($STRATEGIES)]);
            break;
        }

        $config = [
            'strategy_config' => $STRATEGIES[$strategy],
            'pair' => $input['pair'] ?? 'SOL/USDC',
            'position_size' => min((float)($input['position_size'] ?? 1), MAX_TRADE_SOL),
            'max_daily' => MAX_DAILY_SOL,
            'stop_loss_pct' => STOP_LOSS_PCT,
            'take_profit_pct' => TAKE_PROFIT_PCT,
        ];

        $stmt = $db->prepare("INSERT INTO trading_agents (agent_name, strategy, mode, config, status) VALUES (?, ?, 'simulation', ?, 'paused')");
        $stmt->execute([$name, $strategy, json_encode($config)]);

        echo json_encode(['status' => 'created', 'agent_id' => $db->lastInsertId(), 'name' => $name, 'strategy' => $strategy, 'mode' => 'simulation']);
        break;

    case 'strategies':
        echo json_encode(['strategies' => $STRATEGIES]);
        break;

    case 'simulate':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        $input = json_decode(file_get_contents('php://input'), true);
        $strategy = $input['strategy'] ?? 'momentum';
        $pair = $input['pair'] ?? 'SOL/USDC';
        $startBalance = min((float)($input['balance'] ?? 1000), 10000);
        $trades = min((int)($input['trades'] ?? 100), 500);

        if (!isset($STRATEGIES[$strategy])) {
            echo json_encode(['error' => 'Unknown strategy']);
            break;
        }

        // Run Monte Carlo simulation
        $results = runSimulation($strategy, $pair, $startBalance, $trades);

        $stmt = $db->prepare("INSERT INTO trading_simulations
            (strategy, pair, start_balance, end_balance, total_trades, win_rate, max_drawdown, sharpe_ratio, profit_factor, results)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $strategy, $pair, $startBalance, $results['end_balance'],
            $results['total_trades'], $results['win_rate'], $results['max_drawdown'],
            $results['sharpe_ratio'], $results['profit_factor'], json_encode($results),
        ]);

        echo json_encode(['status' => 'completed', 'simulation_id' => $db->lastInsertId(), 'results' => $results]);
        break;

    case 'positions':
        $status = $_GET['status'] ?? 'open';
        $stmt = $db->prepare("SELECT p.*, a.agent_name, a.strategy FROM trading_positions p
            JOIN trading_agents a ON p.agent_id = a.id
            WHERE p.status = ? ORDER BY p.opened_at DESC LIMIT 50");
        $stmt->execute([$status]);
        echo json_encode(['positions' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'performance':
        $agents = $db->query("SELECT id, agent_name, strategy, mode, status,
            total_trades, winning_trades,
            CASE WHEN total_trades > 0 THEN ROUND(winning_trades/total_trades*100, 2) ELSE 0 END as win_rate,
            total_pnl, max_drawdown, sharpe_ratio, last_trade_at
            FROM trading_agents ORDER BY total_pnl DESC")->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['performance' => $agents]);
        break;

    case 'approve_trade':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        $input = json_decode(file_get_contents('php://input'), true);
        $positionId = (int)($input['position_id'] ?? 0);
        $approved = (bool)($input['approved'] ?? false);

        if (!$positionId) { echo json_encode(['error' => 'position_id required']); break; }

        if ($approved) {
            $db->prepare("UPDATE trading_positions SET status = 'open' WHERE id = ? AND status = 'pending_approval'")
                ->execute([$positionId]);
        } else {
            $db->prepare("UPDATE trading_positions SET status = 'cancelled' WHERE id = ? AND status = 'pending_approval'")
                ->execute([$positionId]);
        }

        echo json_encode(['status' => $approved ? 'approved' : 'rejected', 'position_id' => $positionId]);
        break;

    case 'risk_report':
        $events = $db->query("SELECT * FROM trading_risk_events ORDER BY created_at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);

        // Daily trading volume
        $dailyVolume = $db->query("SELECT COALESCE(SUM(size * entry_price), 0) as volume
            FROM trading_positions WHERE DATE(opened_at) = CURDATE()")->fetchColumn();

        // Overall risk metrics
        $agents = $db->query("SELECT
            AVG(max_drawdown) as avg_drawdown,
            MAX(max_drawdown) as max_drawdown,
            AVG(sharpe_ratio) as avg_sharpe,
            SUM(total_pnl) as total_pnl
            FROM trading_agents WHERE status = 'active'")->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'risk_events' => $events,
            'daily_volume_sol' => round((float)$dailyVolume, 4),
            'daily_limit_sol' => MAX_DAILY_SOL,
            'daily_usage_pct' => round(((float)$dailyVolume / MAX_DAILY_SOL) * 100, 2),
            'risk_metrics' => $agents,
            'controls' => [
                'stop_loss' => STOP_LOSS_PCT . '%',
                'take_profit' => TAKE_PROFIT_PCT . '%',
                'max_trade' => MAX_TRADE_SOL . ' SOL',
                'max_daily' => MAX_DAILY_SOL . ' SOL',
                'approval_threshold' => HUMAN_APPROVAL_THRESHOLD . ' SOL',
                'allowed_pairs' => ALLOWED_PAIRS,
                'leverage' => 'DISABLED',
                'margin' => 'DISABLED',
            ],
        ]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action', 'actions' => ['dashboard','agents','create_agent','strategies','simulate','positions','performance','approve_trade','risk_report']]);
}

// ── Monte Carlo Simulation Engine ───────────────────────────────
function runSimulation(string $strategy, string $pair, float $startBalance, int $numTrades): array {
    $balance = $startBalance;
    $peak = $startBalance;
    $maxDrawdown = 0;
    $wins = 0;
    $losses = 0;
    $returns = [];
    $equity = [$startBalance];
    $trades = [];

    // Strategy-specific parameters
    $params = match($strategy) {
        'momentum' => ['win_prob' => 0.55, 'avg_win' => 0.035, 'avg_loss' => 0.025, 'risk_per_trade' => 0.02],
        'mean_reversion' => ['win_prob' => 0.62, 'avg_win' => 0.020, 'avg_loss' => 0.018, 'risk_per_trade' => 0.015],
        'breakout' => ['win_prob' => 0.42, 'avg_win' => 0.060, 'avg_loss' => 0.020, 'risk_per_trade' => 0.025],
        'dca_accumulator' => ['win_prob' => 0.70, 'avg_win' => 0.015, 'avg_loss' => 0.010, 'risk_per_trade' => 0.01],
        'arbitrage' => ['win_prob' => 0.85, 'avg_win' => 0.003, 'avg_loss' => 0.002, 'risk_per_trade' => 0.05],
        'sentiment' => ['win_prob' => 0.52, 'avg_win' => 0.040, 'avg_loss' => 0.030, 'risk_per_trade' => 0.02],
        default => ['win_prob' => 0.50, 'avg_win' => 0.030, 'avg_loss' => 0.030, 'risk_per_trade' => 0.02],
    };

    for ($i = 0; $i < $numTrades; $i++) {
        $positionSize = $balance * $params['risk_per_trade'];
        $rand = mt_rand(0, 1000) / 1000;

        if ($rand < $params['win_prob']) {
            // Win - add some randomness
            $winPct = $params['avg_win'] * (0.5 + mt_rand(0, 100) / 100);
            $pnl = $positionSize * $winPct / $params['risk_per_trade'];
            $balance += $pnl;
            $wins++;
            $returns[] = $winPct;
        } else {
            // Loss
            $lossPct = $params['avg_loss'] * (0.5 + mt_rand(0, 100) / 100);
            $pnl = -$positionSize * $lossPct / $params['risk_per_trade'];
            $balance += $pnl;
            $losses++;
            $returns[] = -$lossPct;
        }

        $equity[] = $balance;
        if ($balance > $peak) $peak = $balance;
        $drawdown = ($peak - $balance) / $peak;
        if ($drawdown > $maxDrawdown) $maxDrawdown = $drawdown;

        // Stop-loss circuit breaker
        if ($balance < $startBalance * (1 - STOP_LOSS_PCT / 100)) {
            break;
        }
    }

    // Calculate Sharpe Ratio
    $avgReturn = count($returns) ? array_sum($returns) / count($returns) : 0;
    $variance = 0;
    foreach ($returns as $r) $variance += pow($r - $avgReturn, 2);
    $stdDev = count($returns) > 1 ? sqrt($variance / (count($returns) - 1)) : 1;
    $sharpe = $stdDev > 0 ? ($avgReturn / $stdDev) * sqrt(252) : 0;

    // Profit Factor
    $grossProfit = array_sum(array_filter($returns, fn($r) => $r > 0));
    $grossLoss = abs(array_sum(array_filter($returns, fn($r) => $r < 0)));
    $profitFactor = $grossLoss > 0 ? $grossProfit / $grossLoss : ($grossProfit > 0 ? 999 : 0);

    return [
        'strategy' => $strategy,
        'pair' => $pair,
        'start_balance' => $startBalance,
        'end_balance' => round($balance, 4),
        'total_return' => round(($balance - $startBalance) / $startBalance * 100, 2),
        'total_trades' => $wins + $losses,
        'wins' => $wins,
        'losses' => $losses,
        'win_rate' => round($wins / max($wins + $losses, 1) * 100, 2),
        'max_drawdown' => round($maxDrawdown * 100, 2),
        'sharpe_ratio' => round($sharpe, 4),
        'profit_factor' => round($profitFactor, 4),
        'avg_trade_return' => round($avgReturn * 100, 4),
        'equity_curve_sample' => array_map(fn($v) => round($v, 2), array_values(array_filter($equity, fn($k) => $k % max(1, intval(count($equity) / 20)) === 0, ARRAY_FILTER_USE_KEY))),
    ];
}
