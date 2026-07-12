<?php
require_once __DIR__ . '/includes/auth-gate.inc.php';

// Define API constant so financial config doesn't die() on direct-access guard
if (!defined('GOSITEME_API')) define('GOSITEME_API', true);
if (!defined('SITE_URL')) define('SITE_URL', 'https://gositeme.com');

// Quick integration status check
$integrationStatus = [];
try {
    require_once __DIR__ . '/api/financial/config.php';
    $integrationStatus = finGetIntegrationStatus();
} catch (\Throwable $e) {
    $integrationStatus = [];
}

$pageTitle = 'Financial Command Center';
$pageDescription = 'Financial Command Center — GoSiteMe';
include __DIR__ . '/includes/site-header.inc.php';
?>
<style>
    :root {
        --fc-bg: #0a0e17;
        --fc-card: #111827;
        --fc-border: #1f2937;
        --fc-green: #10b981;
        --fc-red: #ef4444;
        --fc-blue: #3b82f6;
        --fc-purple: #8b5cf6;
        --fc-amber: #f59e0b;
        --fc-text: #e5e7eb;
        --fc-muted: #9ca3af;
    }
    body { background: var(--fc-bg); color: var(--fc-text); }
    .fc-wrap { max-width: 1400px; margin: 0 auto; padding: 2rem 1.5rem; }
    .fc-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
    .fc-header h1 { font-size: 1.75rem; font-weight: 700; margin: 0; }
    .fc-header h1 span { color: var(--fc-green); }
    .fc-header-actions { display: flex; gap: .75rem; }
    .fc-btn { padding: .5rem 1rem; border-radius: .5rem; border: 1px solid var(--fc-border); background: var(--fc-card); color: var(--fc-text); cursor: pointer; font-size: .875rem; transition: all .2s; text-decoration: none; display: inline-flex; align-items: center; gap: .4rem; }
    .fc-btn:hover { border-color: var(--fc-green); color: #fff; }
    .fc-btn-primary { background: var(--fc-green); border-color: var(--fc-green); color: #000; font-weight: 600; }
    .fc-btn-primary:hover { background: #059669; }
    .fc-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
    .fc-kpi { background: var(--fc-card); border: 1px solid var(--fc-border); border-radius: .75rem; padding: 1.25rem; }
    .fc-kpi-label { font-size: .75rem; text-transform: uppercase; letter-spacing: .05em; color: var(--fc-muted); margin-bottom: .25rem; }
    .fc-kpi-value { font-size: 1.5rem; font-weight: 700; }
    .fc-kpi-change { font-size: .75rem; margin-top: .25rem; }
    .fc-kpi-change.up { color: var(--fc-green); }
    .fc-kpi-change.down { color: var(--fc-red); }
    .fc-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem; }
    .fc-section { background: var(--fc-card); border: 1px solid var(--fc-border); border-radius: .75rem; padding: 1.5rem; }
    .fc-section-full { grid-column: 1 / -1; }
    .fc-section h2 { font-size: 1.125rem; font-weight: 600; margin: 0 0 1rem 0; display: flex; align-items: center; gap: .5rem; }
    .fc-section h2 .badge { font-size: .7rem; padding: .15rem .5rem; border-radius: 1rem; background: var(--fc-green); color: #000; font-weight: 600; }
    .fc-integrations { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: .75rem; }
    .fc-int { display: flex; align-items: center; gap: .5rem; padding: .5rem .75rem; border-radius: .5rem; background: rgba(255,255,255,.03); border: 1px solid var(--fc-border); font-size: .85rem; }
    .fc-int-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .fc-int-dot.on { background: var(--fc-green); box-shadow: 0 0 6px var(--fc-green); }
    .fc-int-dot.off { background: var(--fc-muted); }
    .fc-table { width: 100%; border-collapse: collapse; font-size: .85rem; }
    .fc-table th { text-align: left; padding: .5rem; color: var(--fc-muted); font-weight: 500; border-bottom: 1px solid var(--fc-border); }
    .fc-table td { padding: .5rem; border-bottom: 1px solid rgba(255,255,255,.05); }
    .fc-status { display: inline-block; padding: .15rem .5rem; border-radius: .25rem; font-size: .75rem; font-weight: 600; }
    .fc-status-completed { background: rgba(16,185,129,.15); color: var(--fc-green); }
    .fc-status-pending { background: rgba(245,158,11,.15); color: var(--fc-amber); }
    .fc-status-failed { background: rgba(239,68,68,.15); color: var(--fc-red); }
    .fc-chart { height: 200px; display: flex; align-items: flex-end; gap: 4px; padding: 1rem 0; }
    .fc-bar { flex: 1; background: linear-gradient(to top, var(--fc-green), var(--fc-blue)); border-radius: 4px 4px 0 0; min-height: 8px; transition: height .5s ease; position: relative; }
    .fc-bar:hover { opacity: .8; }
    .fc-bar-label { position: absolute; bottom: -1.5rem; left: 50%; transform: translateX(-50%); font-size: .6rem; color: var(--fc-muted); white-space: nowrap; }
    .fc-loading { display: flex; align-items: center; justify-content: center; padding: 2rem; color: var(--fc-muted); }
    .fc-spinner { width: 20px; height: 20px; border: 2px solid var(--fc-border); border-top-color: var(--fc-green); border-radius: 50%; animation: fc-spin .8s linear infinite; margin-right: .5rem; }
    @keyframes fc-spin { to { transform: rotate(360deg); } }
    .fc-agents { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: .5rem; }
    .fc-agent { text-align: center; padding: .75rem; border-radius: .5rem; background: rgba(255,255,255,.03); border: 1px solid var(--fc-border); }
    .fc-agent-icon { font-size: 1.5rem; margin-bottom: .25rem; }
    .fc-agent-name { font-size: .8rem; font-weight: 600; }
    .fc-agent-role { font-size: .65rem; color: var(--fc-muted); }
    @media (max-width: 768px) {
        .fc-grid { grid-template-columns: 1fr; }
        .fc-kpis { grid-template-columns: repeat(2, 1fr); }
    }
</style>

<div class="fc-wrap">
    <div class="fc-header">
        <h1>Financial <span>Command Center</span></h1>
        <div class="fc-header-actions">
            <a href="/dashboard" class="fc-btn">← Dashboard</a>
            <button class="fc-btn" onclick="refreshAll()">↻ Refresh</button>
            <button class="fc-btn fc-btn-primary" onclick="showQuickActions()">Quick Actions</button>
        </div>
    </div>

    <!-- KPIs -->
    <div class="fc-kpis">
        <div class="fc-kpi">
            <div class="fc-kpi-label">Revenue (MTD)</div>
            <div class="fc-kpi-value" id="kpi-revenue">--</div>
            <div class="fc-kpi-change" id="kpi-growth"></div>
        </div>
        <div class="fc-kpi">
            <div class="fc-kpi-label">MRR</div>
            <div class="fc-kpi-value" id="kpi-mrr">--</div>
        </div>
        <div class="fc-kpi">
            <div class="fc-kpi-label">Active Subscriptions</div>
            <div class="fc-kpi-value" id="kpi-subs">--</div>
        </div>
        <div class="fc-kpi">
            <div class="fc-kpi-label">Total Balance</div>
            <div class="fc-kpi-value" id="kpi-balance">--</div>
        </div>
        <div class="fc-kpi">
            <div class="fc-kpi-label">Pending Payouts</div>
            <div class="fc-kpi-value" id="kpi-payouts">--</div>
        </div>
        <div class="fc-kpi">
            <div class="fc-kpi-label">Growth Rate</div>
            <div class="fc-kpi-value" id="kpi-growth-rate">--</div>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="fc-grid">
        <!-- AgentWork Treasury -->
        <div class="fc-section fc-section-full">
            <h2>🏪 AgentWork Treasury <span class="badge" id="aw-status">loading</span></h2>
            <div class="fc-kpis" style="margin-bottom:0;">
                <div class="fc-kpi">
                    <div class="fc-kpi-label">Active Gigs</div>
                    <div class="fc-kpi-value" id="aw-gigs">--</div>
                </div>
                <div class="fc-kpi">
                    <div class="fc-kpi-label">Projects Posted</div>
                    <div class="fc-kpi-value" id="aw-projects">--</div>
                </div>
                <div class="fc-kpi">
                    <div class="fc-kpi-label">Total Earnings</div>
                    <div class="fc-kpi-value" id="aw-earnings">--</div>
                </div>
                <div class="fc-kpi">
                    <div class="fc-kpi-label">Platform Fees (10%)</div>
                    <div class="fc-kpi-value" id="aw-fees">--</div>
                </div>
                <div class="fc-kpi">
                    <div class="fc-kpi-label">Treasury Balance</div>
                    <div class="fc-kpi-value" id="aw-balance" style="color:var(--fc-green)">--</div>
                </div>
                <div class="fc-kpi">
                    <div class="fc-kpi-label">Top Earning Agent</div>
                    <div class="fc-kpi-value" id="aw-top-agent" style="font-size:1rem">--</div>
                </div>
            </div>
        </div>

        <!-- Ecosystem Wallets -->
        <div class="fc-section">
            <h2>💼 Ecosystem Wallets</h2>
            <div id="wallets-grid">
                <div class="fc-loading"><div class="fc-spinner"></div> Loading wallets...</div>
            </div>
        </div>

        <!-- Revenue Streams -->
        <div class="fc-section">
            <h2>💎 Revenue Streams</h2>
            <div id="streams-grid">
                <div class="fc-loading"><div class="fc-spinner"></div> Loading streams...</div>
            </div>
        </div>

        <!-- Revenue Chart -->
        <div class="fc-section fc-section-full">
            <h2>📈 Revenue Trend</h2>
            <div class="fc-chart" id="revenue-chart">
                <div class="fc-loading"><div class="fc-spinner"></div> Loading revenue data...</div>
            </div>
        </div>

        <!-- Integration Status -->
        <div class="fc-section">
            <h2>🔌 Integrations <span class="badge" id="int-count">0 connected</span></h2>
            <div class="fc-integrations" id="integrations-grid">
                <?php
                $intLabels = [
                    'stripe' => 'Stripe', 'plaid' => 'Plaid', 'mercury' => 'Mercury',
                    'wise' => 'Wise', 'paypal' => 'PayPal', 'xero' => 'Xero',
                    'quickbooks' => 'QuickBooks', 'chartmogul' => 'ChartMogul',
                    'profitwell' => 'ProfitWell', 'taxjar' => 'TaxJar',
                    'koinly' => 'Koinly', 'kraken' => 'Kraken', 'coinbase' => 'Coinbase',
                    'deel' => 'Deel', 'oneinch' => '1inch',
                ];
                $connected = 0;
                foreach ($intLabels as $key => $label):
                    $isOn = !empty($integrationStatus[$key]);
                    if ($isOn) $connected++;
                ?>
                <div class="fc-int">
                    <div class="fc-int-dot <?= $isOn ? 'on' : 'off' ?>"></div>
                    <span><?= htmlspecialchars($label) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <script>document.getElementById('int-count').textContent = '<?= $connected ?> connected';</script>
        </div>

        <!-- ATLAS Finance Agents -->
        <div class="fc-section">
            <h2>🤖 Finance Agents</h2>
            <div class="fc-agents">
                <?php
                $agents = [
                    ['💰', 'Treasurer', 'Treasury Mgmt'],
                    ['🧾', 'Invoicer', 'Billing'],
                    ['📈', 'Trader', 'Crypto Trading'],
                    ['📊', 'Accountant', 'Bookkeeping'],
                    ['💸', 'Paymaster', 'Payroll'],
                    ['🏷️', 'Underwriter', 'Pricing'],
                    ['📞', 'Collector', 'Debt Recovery'],
                    ['🔮', 'Forecaster', 'Forecasting'],
                    ['🛡️', 'Auditor-F', 'Compliance'],
                ];
                foreach ($agents as $a):
                ?>
                <div class="fc-agent">
                    <div class="fc-agent-icon"><?= $a[0] ?></div>
                    <div class="fc-agent-name"><?= $a[1] ?></div>
                    <div class="fc-agent-role"><?= $a[2] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Recent Payouts -->
        <div class="fc-section">
            <h2>💸 Recent Payouts</h2>
            <div id="payouts-table">
                <div class="fc-loading"><div class="fc-spinner"></div> Loading...</div>
            </div>
        </div>

        <!-- Tax Deadlines -->
        <div class="fc-section">
            <h2>📅 Tax Deadlines</h2>
            <div id="tax-deadlines">
                <div class="fc-loading"><div class="fc-spinner"></div> Loading...</div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="fc-section fc-section-full">
            <h2>📋 Recent Trading Activity</h2>
            <div id="orders-table">
                <div class="fc-loading"><div class="fc-spinner"></div> Loading...</div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>

<script src="/assets/js/finance-dashboard-engine.js"></script>
</body>
</html>
