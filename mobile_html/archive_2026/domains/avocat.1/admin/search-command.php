<?php
/**
 * Search Command — Intelligence & Control Panel
 * ───────────────────────────────────────────────
 * Admin panel for Alfred Search engine operations.
 * Monitor crawler, index health, search analytics,
 * department recommendations, and marketing metrics.
 */
$page_title = 'Search Command — Alfred Search Intelligence';
$page_description = 'Command and control panel for Alfred Search engine operations.';
$page_canonical = 'https://gositeme.com/admin/search-command';
$page_robots = 'noindex, nofollow';

require_once dirname(__DIR__) . '/includes/auth-gate.inc.php';
require_once dirname(__DIR__) . '/includes/site-header.inc.php';

// Admin check
if ((int)($clientId ?? 0) !== 33) {
    header('Location: /dashboard.php');
    exit;
}

require_once dirname(__DIR__) . '/api/config.php';
$db = getDB();

// ── Gather Stats ────────────────────────────────────────────────
$stats = [
    'queue_total' => 0, 'queue_pending' => 0, 'queue_done' => 0, 'queue_failed' => 0,
    'pages_indexed' => 0, 'domains_known' => 0, 'avg_quality' => 0,
    'searches_today' => 0, 'searches_total' => 0, 'top_queries' => [],
    'recent_pages' => [], 'top_domains' => [], 'search_modes' => [],
    'cost_today' => 0, 'cost_total' => 0, 'avg_response_ms' => 0,
];

if ($db) {
    try {
        // Crawler stats
        $r = $db->query("SELECT COUNT(*) FROM crawler_queue")->fetchColumn();
        $stats['queue_total'] = (int) $r;
        $r = $db->query("SELECT COUNT(*) FROM crawler_queue WHERE status='pending'")->fetchColumn();
        $stats['queue_pending'] = (int) $r;
        $r = $db->query("SELECT COUNT(*) FROM crawler_queue WHERE status='done'")->fetchColumn();
        $stats['queue_done'] = (int) $r;
        $r = $db->query("SELECT COUNT(*) FROM crawler_queue WHERE status='failed'")->fetchColumn();
        $stats['queue_failed'] = (int) $r;
        $r = $db->query("SELECT COUNT(*) FROM crawler_pages")->fetchColumn();
        $stats['pages_indexed'] = (int) $r;
        $r = $db->query("SELECT COUNT(*) FROM crawler_domains")->fetchColumn();
        $stats['domains_known'] = (int) $r;
        $r = $db->query("SELECT ROUND(AVG(quality_score),3) FROM crawler_pages")->fetchColumn();
        $stats['avg_quality'] = $r ?: 0;

        // Search stats
        $r = $db->query("SELECT COUNT(*) FROM alfred_search_log WHERE searched_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
        $stats['searches_today'] = (int) $r;
        $r = $db->query("SELECT COUNT(*) FROM alfred_search_log")->fetchColumn();
        $stats['searches_total'] = (int) $r;

        // Top queries (24h) — handle both encrypted and legacy schemas
        try {
            $stmt = $db->query("SELECT query_encrypted, query_hash, COUNT(*) as cnt FROM alfred_search_log WHERE searched_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) GROUP BY query_hash ORDER BY cnt DESC LIMIT 20");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // Decrypt for admin display
            $keyFile = getenv('HOME') . '/.local/alfred/search-log-key.bin';
            $decryptKey = is_readable($keyFile) ? file_get_contents($keyFile) : null;
            foreach ($rows as &$row) {
                $plain = '(encrypted)';
                if ($decryptKey && strlen($decryptKey) === SODIUM_CRYPTO_SECRETBOX_KEYBYTES && !empty($row['query_encrypted'])) {
                    $decoded = base64_decode($row['query_encrypted'], true);
                    if ($decoded && strlen($decoded) > SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
                        $nonce = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
                        $cipher = substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
                        $result = sodium_crypto_secretbox_open($cipher, $nonce, $decryptKey);
                        if ($result !== false) $plain = $result;
                    }
                }
                $row['query'] = $plain;
            }
            unset($row);
            $stats['top_queries'] = $rows;
        } catch (Exception $e) {
            // Fallback for legacy plain-text schema
            try {
                $stmt = $db->query("SELECT query, COUNT(*) as cnt FROM alfred_search_log WHERE searched_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) GROUP BY query ORDER BY cnt DESC LIMIT 20");
                $stats['top_queries'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e2) {}
        }

        // Search modes breakdown
        $stmt = $db->query("SELECT mode, COUNT(*) as cnt FROM alfred_search_log WHERE searched_at > DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY mode ORDER BY cnt DESC");
        $stats['search_modes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Cost tracking
        try {
            $r = $db->query("SELECT COALESCE(SUM(cost_usd),0) FROM alfred_search_log WHERE searched_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
            $stats['cost_today'] = round((float) $r, 4);
            $r = $db->query("SELECT COALESCE(SUM(cost_usd),0) FROM alfred_search_log")->fetchColumn();
            $stats['cost_total'] = round((float) $r, 4);
        } catch (Exception $e) { /* cost column may not exist yet */ }

        // Average response time
        try {
            $r = $db->query("SELECT ROUND(AVG(response_ms)) FROM alfred_search_log WHERE searched_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
            $stats['avg_response_ms'] = (int) ($r ?: 0);
        } catch (Exception $e) {}

        // Recent crawled pages
        $stmt = $db->query("SELECT url, domain, title, quality_score, crawled_at FROM crawler_pages ORDER BY crawled_at DESC LIMIT 15");
        $stats['recent_pages'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Top domains by pages
        $stmt = $db->query("SELECT domain, pages_crawled, reputation, last_crawled FROM crawler_domains ORDER BY pages_crawled DESC LIMIT 15");
        $stats['top_domains'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ── Intel Crawler Stats ─────────────────────────────────
        try {
            $stats['intel_domains'] = (int)$db->query("SELECT COUNT(*) FROM intel_domains")->fetchColumn();
            $stats['intel_classified'] = (int)$db->query("SELECT COUNT(*) FROM intel_classifications")->fetchColumn();
            $stats['intel_feeds'] = (int)$db->query("SELECT COUNT(*) FROM intel_feeds WHERE status='active'")->fetchColumn();
            $stats['intel_threats'] = (int)$db->query("SELECT COUNT(*) FROM intel_domains WHERE threat_level != 'safe'")->fetchColumn();
            $stats['intel_edges'] = (int)$db->query("SELECT COUNT(*) FROM intel_link_graph")->fetchColumn();
            $stats['intel_fingerprints'] = (int)$db->query("SELECT COUNT(*) FROM intel_fingerprints")->fetchColumn();

            $stmt = $db->query("SELECT domain, authority_score, threat_level, category FROM intel_domains ORDER BY authority_score DESC LIMIT 10");
            $stats['intel_top_authorities'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $db->query("SELECT category, COUNT(*) as cnt FROM intel_classifications GROUP BY category ORDER BY cnt DESC LIMIT 8");
            $stats['intel_categories'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $db->query("SELECT domain, threat_level, threat_tags FROM intel_domains WHERE threat_level != 'safe' ORDER BY FIELD(threat_level,'critical','high','medium','low') LIMIT 10");
            $stats['intel_threat_list'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) { /* intel tables may not exist yet */ }

    } catch (Exception $e) {
        // Tables may not exist yet — that's ok
    }
}
?>

<style>
:root {
    --sc-bg: #04040a;
    --sc-surface: #0a0a16;
    --sc-surface2: #10101e;
    --sc-border: rgba(96,165,250,0.1);
    --sc-accent: #60a5fa;
    --sc-accent2: #a78bfa;
    --sc-green: #34d399;
    --sc-red: #ef4444;
    --sc-gold: #fbbf24;
    --sc-text: #e2e8f0;
    --sc-dim: #6b7fa3;
}
.sc-wrap {
    background: var(--sc-bg);
    min-height: 100vh;
    padding: 20px;
    margin: -20px -20px 0;
}
.sc-header {
    max-width: 1400px;
    margin: 0 auto 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
}
.sc-header h1 {
    font-size: 1.8rem;
    font-weight: 800;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 12px;
}
.sc-header h1 i {
    background: linear-gradient(135deg, var(--sc-accent), var(--sc-accent2));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.sc-header .class-badge {
    padding: 6px 14px;
    background: rgba(96,165,250,0.15);
    border: 1px solid rgba(96,165,250,0.3);
    border-radius: 50px;
    color: var(--sc-accent);
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 2px;
}
.sc-header-actions {
    display: flex;
    gap: 8px;
}
.sc-btn {
    padding: 10px 18px;
    border-radius: 10px;
    font-size: 0.85rem;
    font-weight: 600;
    border: 1px solid var(--sc-border);
    background: var(--sc-surface);
    color: var(--sc-text);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
}
.sc-btn:hover { background: var(--sc-surface2); border-color: var(--sc-accent); }
.sc-btn.primary { background: linear-gradient(135deg, var(--sc-accent), var(--sc-accent2)); color: #fff; border-color: transparent; }
.sc-btn.primary:hover { opacity: 0.9; transform: translateY(-1px); }
.sc-btn.danger { border-color: rgba(239,68,68,0.3); color: var(--sc-red); }

/* Tabs */
.sc-tabs {
    max-width: 1400px;
    margin: 0 auto 24px;
    display: flex;
    gap: 4px;
    background: var(--sc-surface);
    border-radius: 14px;
    padding: 4px;
    overflow-x: auto;
    border: 1px solid var(--sc-border);
}
.sc-tab {
    padding: 10px 20px;
    border-radius: 10px;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--sc-dim);
    background: transparent;
    border: none;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.2s;
}
.sc-tab:hover { color: var(--sc-text); }
.sc-tab.active {
    background: rgba(96,165,250,0.15);
    color: var(--sc-accent);
}

/* Panels */
.sc-panel { display: none; max-width: 1400px; margin: 0 auto; }
.sc-panel.active { display: block; }

/* Grid */
.sc-grid-4 { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
.sc-grid-3 { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; margin-bottom: 24px; }
.sc-grid-2 { display: grid; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); gap: 16px; margin-bottom: 24px; }

/* Stat Card */
.sc-stat {
    background: var(--sc-surface);
    border: 1px solid var(--sc-border);
    border-radius: 14px;
    padding: 20px;
    text-align: center;
}
.sc-stat .num {
    font-size: 2rem;
    font-weight: 800;
    background: linear-gradient(135deg, var(--sc-accent), var(--sc-accent2));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.sc-stat .lbl { color: var(--sc-dim); font-size: 0.8rem; margin-top: 4px; }
.sc-stat.green .num { background: linear-gradient(135deg, var(--sc-green), #06b6d4); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.sc-stat.gold .num { background: linear-gradient(135deg, var(--sc-gold), #f59e0b); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.sc-stat.red .num { background: linear-gradient(135deg, var(--sc-red), #f97316); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }

/* Cards */
.sc-card {
    background: var(--sc-surface);
    border: 1px solid var(--sc-border);
    border-radius: 14px;
    padding: 24px;
    margin-bottom: 16px;
}
.sc-card h3 {
    font-size: 1rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.sc-card h3 i { color: var(--sc-accent); font-size: 0.85rem; }

/* Table */
.sc-table {
    width: 100%;
    border-collapse: collapse;
}
.sc-table thead th {
    text-align: left;
    padding: 10px 12px;
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--sc-dim);
    text-transform: uppercase;
    letter-spacing: 1px;
    border-bottom: 1px solid var(--sc-border);
}
.sc-table tbody td {
    padding: 10px 12px;
    font-size: 0.85rem;
    color: var(--sc-text);
    border-bottom: 1px solid rgba(96,165,250,0.05);
    max-width: 300px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.sc-table tbody tr:hover { background: rgba(96,165,250,0.03); }

/* Quality bar */
.q-bar { width: 60px; height: 6px; background: rgba(255,255,255,0.1); border-radius: 3px; display: inline-block; vertical-align: middle; margin-left: 6px; }
.q-bar-fill { height: 100%; border-radius: 3px; }

/* Status pill */
.sc-pill {
    padding: 3px 10px;
    border-radius: 50px;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
}
.sc-pill.green { background: rgba(52,211,153,0.15); color: var(--sc-green); }
.sc-pill.red { background: rgba(239,68,68,0.15); color: var(--sc-red); }
.sc-pill.blue { background: rgba(96,165,250,0.15); color: var(--sc-accent); }
.sc-pill.gold { background: rgba(251,191,36,0.15); color: var(--sc-gold); }

/* Department Recs */
.dept-card {
    background: var(--sc-surface);
    border: 1px solid var(--sc-border);
    border-radius: 14px;
    padding: 20px;
    transition: all 0.3s;
}
.dept-card:hover { border-color: rgba(96,165,250,0.3); transform: translateY(-2px); }
.dept-card .dept-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    margin-bottom: 12px;
}
.dept-card h4 { color: #fff; font-size: 0.95rem; margin-bottom: 8px; }
.dept-card p { color: var(--sc-dim); font-size: 0.8rem; line-height: 1.5; margin-bottom: 6px; }
.dept-card .rec-tag {
    display: inline-block;
    padding: 2px 8px;
    font-size: 0.65rem;
    font-weight: 700;
    border-radius: 4px;
    margin-right: 4px;
    margin-bottom: 4px;
}
.rec-tag.sprint { background: rgba(52,211,153,0.15); color: var(--sc-green); }
.rec-tag.q2 { background: rgba(96,165,250,0.15); color: var(--sc-accent); }
.rec-tag.q3 { background: rgba(167,139,250,0.15); color: var(--sc-accent2); }

/* Add URL form */
.sc-form {
    display: flex;
    gap: 8px;
    margin: 16px 0;
}
.sc-input {
    flex: 1;
    padding: 10px 14px;
    background: var(--sc-bg);
    border: 1px solid var(--sc-border);
    border-radius: 10px;
    color: var(--sc-text);
    font-size: 0.9rem;
}
.sc-input:focus { outline: none; border-color: var(--sc-accent); }

/* Marketing stats */
.mkt-metric {
    text-align: center;
    padding: 20px;
}
.mkt-metric .icon { font-size: 2rem; margin-bottom: 8px; }
.mkt-metric .val { font-size: 1.5rem; font-weight: 800; color: #fff; }
.mkt-metric .label { color: var(--sc-dim); font-size: 0.8rem; margin-top: 4px; }

@media (max-width: 768px) {
    .sc-grid-4 { grid-template-columns: repeat(2, 1fr); }
    .sc-grid-2 { grid-template-columns: 1fr; }
    .sc-header { flex-direction: column; align-items: flex-start; }
}
</style>

<div class="sc-wrap">
    <!-- Header -->
    <div class="sc-header">
        <h1><i class="fas fa-satellite-dish"></i> Search Command</h1>
        <div style="display:flex;align-items:center;gap:12px;">
            <span class="class-badge">COMMANDER ACCESS</span>
            <div class="sc-header-actions">
                <button class="sc-btn" onclick="refreshStats()"><i class="fas fa-sync"></i> Refresh</button>
                <a href="/search.php" class="sc-btn"><i class="fas fa-external-link-alt"></i> Live Search</a>
                <a href="/about-crawler" class="sc-btn"><i class="fas fa-spider"></i> Crawler Page</a>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="sc-tabs">
        <button class="sc-tab active" data-panel="overview"><i class="fas fa-tachometer-alt"></i> Overview</button>
        <button class="sc-tab" data-panel="crawler"><i class="fas fa-spider"></i> Crawler Ops</button>
        <button class="sc-tab" data-panel="analytics"><i class="fas fa-chart-bar"></i> Search Analytics</button>
        <button class="sc-tab" data-panel="marketing"><i class="fas fa-bullhorn"></i> Marketing Intel</button>
        <button class="sc-tab" data-panel="departments"><i class="fas fa-building"></i> Dept. Reports</button>
        <button class="sc-tab" data-panel="intelligence"><i class="fas fa-user-secret"></i> Intelligence</button>
        <button class="sc-tab" data-panel="upgrades"><i class="fas fa-rocket"></i> Upgrade Pipeline</button>
    </div>

    <!-- ═══ OVERVIEW PANEL ═══ -->
    <div class="sc-panel active" id="panel-overview">
        <div class="sc-grid-4">
            <div class="sc-stat">
                <div class="num"><?php echo number_format($stats['pages_indexed']); ?></div>
                <div class="lbl">Pages Indexed</div>
            </div>
            <div class="sc-stat green">
                <div class="num"><?php echo number_format($stats['domains_known']); ?></div>
                <div class="lbl">Domains Known</div>
            </div>
            <div class="sc-stat gold">
                <div class="num"><?php echo number_format($stats['searches_today']); ?></div>
                <div class="lbl">Searches Today</div>
            </div>
            <div class="sc-stat">
                <div class="num"><?php echo number_format($stats['searches_total']); ?></div>
                <div class="lbl">Total Searches</div>
            </div>
            <div class="sc-stat green">
                <div class="num"><?php echo number_format($stats['queue_pending']); ?></div>
                <div class="lbl">Queue Pending</div>
            </div>
            <div class="sc-stat">
                <div class="num"><?php echo number_format($stats['queue_done']); ?></div>
                <div class="lbl">Queue Done</div>
            </div>
            <div class="sc-stat red">
                <div class="num"><?php echo number_format($stats['queue_failed']); ?></div>
                <div class="lbl">Failed</div>
            </div>
            <div class="sc-stat green">
                <div class="num"><?php echo $stats['avg_quality']; ?></div>
                <div class="lbl">Avg Quality</div>
            </div>
        </div>

        <!-- Row 2: Financial & Performance Stats -->
        <div class="sc-grid-4">
            <div class="sc-stat gold">
                <div class="num">$<?php echo number_format($stats['cost_today'], 4); ?></div>
                <div class="lbl">Cost Today (USD)</div>
            </div>
            <div class="sc-stat">
                <div class="num">$<?php echo number_format($stats['cost_total'], 4); ?></div>
                <div class="lbl">Total Cost (USD)</div>
            </div>
            <div class="sc-stat green">
                <div class="num"><?php echo number_format($stats['avg_response_ms']); ?>ms</div>
                <div class="lbl">Avg Response</div>
            </div>
            <div class="sc-stat green">
                <div class="num"><i class="fas fa-lock" style="font-size:1.5rem;"></i></div>
                <div class="lbl">Logs Encrypted (Sodium)</div>
            </div>
        </div>

        <div class="sc-grid-2">
            <!-- Trending Queries -->
            <div class="sc-card">
                <h3><i class="fas fa-fire"></i> Top Queries (24h)</h3>
                <?php if (empty($stats['top_queries'])): ?>
                    <p style="color:var(--sc-dim);">No searches yet. Launch Alfred Search to start collecting data.</p>
                <?php else: ?>
                    <table class="sc-table">
                        <thead><tr><th>Query</th><th>Count</th></tr></thead>
                        <tbody>
                        <?php foreach ($stats['top_queries'] as $q): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($q['query']); ?></td>
                                <td><span class="sc-pill blue"><?php echo $q['cnt']; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Search Mode Breakdown -->
            <div class="sc-card">
                <h3><i class="fas fa-pie-chart"></i> Search Modes (7d)</h3>
                <?php if (empty($stats['search_modes'])): ?>
                    <p style="color:var(--sc-dim);">No mode data yet.</p>
                <?php else: ?>
                    <?php foreach ($stats['search_modes'] as $m): ?>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid rgba(96,165,250,0.05);">
                            <span style="color:#fff;font-weight:600;text-transform:capitalize;"><?php echo htmlspecialchars($m['mode']); ?></span>
                            <span class="sc-pill green"><?php echo number_format($m['cnt']); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- System Status -->
        <div class="sc-card">
            <h3><i class="fas fa-heartbeat"></i> System Status</h3>
            <div class="sc-grid-4" style="margin-bottom:0;">
                <div style="text-align:center;padding:12px;">
                    <div id="meili-status" style="font-size:2rem;">&#9679;</div>
                    <div style="color:var(--sc-dim);font-size:0.8rem;">Meilisearch</div>
                </div>
                <div style="text-align:center;padding:12px;">
                    <div id="ollama-status" style="font-size:2rem;">&#9679;</div>
                    <div style="color:var(--sc-dim);font-size:0.8rem;">Ollama AI</div>
                </div>
                <div style="text-align:center;padding:12px;">
                    <div id="jina-status" style="font-size:2rem;">&#9679;</div>
                    <div style="color:var(--sc-dim);font-size:0.8rem;">Jina Web Search</div>
                </div>
                <div style="text-align:center;padding:12px;">
                    <div id="db-status" style="font-size:2rem;color:var(--sc-green);">&#9679;</div>
                    <div style="color:var(--sc-dim);font-size:0.8rem;">Database</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ CRAWLER OPS PANEL ═══ -->
    <div class="sc-panel" id="panel-crawler">
        <!-- Add URL -->
        <div class="sc-card">
            <h3><i class="fas fa-plus-circle"></i> Add URLs to Crawl Queue</h3>
            <div class="sc-form">
                <input class="sc-input" id="add-url-input" type="url" placeholder="https://example.com" />
                <button class="sc-btn primary" onclick="addUrl()"><i class="fas fa-plus"></i> Add</button>
            </div>
            <div id="add-url-result" style="color:var(--sc-green);font-size:0.85rem;margin-top:8px;"></div>

            <h3 style="margin-top:20px;"><i class="fas fa-upload"></i> Bulk Add (one URL per line)</h3>
            <textarea class="sc-input" id="bulk-urls" rows="5" placeholder="https://example.com&#10;https://another.com&#10;https://third.com" style="resize:vertical;width:100%;box-sizing:border-box;"></textarea>
            <button class="sc-btn primary" onclick="addBulkUrls()" style="margin-top:8px;"><i class="fas fa-upload"></i> Bulk Add</button>
            <div id="bulk-url-result" style="color:var(--sc-green);font-size:0.85rem;margin-top:8px;"></div>
        </div>

        <!-- Recent Crawled -->
        <div class="sc-card">
            <h3><i class="fas fa-clock"></i> Recently Crawled Pages</h3>
            <?php if (empty($stats['recent_pages'])): ?>
                <p style="color:var(--sc-dim);">No pages crawled yet. Run: <code style="color:var(--sc-accent);">php scripts/alfred-crawler.php seed && php scripts/alfred-crawler.php crawl 50</code></p>
            <?php else: ?>
                <table class="sc-table">
                    <thead><tr><th>Title</th><th>Domain</th><th>Quality</th><th>When</th></tr></thead>
                    <tbody>
                    <?php foreach ($stats['recent_pages'] as $p): ?>
                        <tr>
                            <td><a href="<?php echo htmlspecialchars($p['url']); ?>" target="_blank" rel="noopener" style="color:var(--sc-accent);text-decoration:none;"><?php echo htmlspecialchars($p['title'] ?: '(untitled)'); ?></a></td>
                            <td><?php echo htmlspecialchars($p['domain']); ?></td>
                            <td>
                                <?php
                                    $q = round($p['quality_score'], 2);
                                    $color = $q >= 0.7 ? 'var(--sc-green)' : ($q >= 0.4 ? 'var(--sc-gold)' : 'var(--sc-red)');
                                ?>
                                <?php echo $q; ?>
                                <span class="q-bar"><span class="q-bar-fill" style="width:<?php echo $q * 100; ?>%;background:<?php echo $color; ?>"></span></span>
                            </td>
                            <td style="color:var(--sc-dim);font-size:0.8rem;"><?php echo $p['crawled_at']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Top Domains -->
        <div class="sc-card">
            <h3><i class="fas fa-globe"></i> Top Crawler Domains</h3>
            <?php if (empty($stats['top_domains'])): ?>
                <p style="color:var(--sc-dim);">No domains crawled yet.</p>
            <?php else: ?>
                <table class="sc-table">
                    <thead><tr><th>Domain</th><th>Pages</th><th>Reputation</th><th>Last Crawl</th></tr></thead>
                    <tbody>
                    <?php foreach ($stats['top_domains'] as $d): ?>
                        <tr>
                            <td style="font-weight:600;"><?php echo htmlspecialchars($d['domain']); ?></td>
                            <td><span class="sc-pill blue"><?php echo $d['pages_crawled']; ?></span></td>
                            <td><?php echo round($d['reputation'], 2); ?></td>
                            <td style="color:var(--sc-dim);font-size:0.8rem;"><?php echo $d['last_crawled'] ?? 'Never'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Crawler Commands -->
        <div class="sc-card">
            <h3><i class="fas fa-terminal"></i> Crawler CLI Commands</h3>
            <div style="background:var(--sc-bg);border-radius:10px;padding:16px;font-family:'JetBrains Mono',monospace;font-size:0.85rem;color:var(--sc-text);line-height:2;">
                <div><span style="color:var(--sc-dim);"># Seed initial URLs (28 high-quality domains)</span></div>
                <div style="color:var(--sc-green);">php scripts/alfred-crawler.php seed</div>
                <br>
                <div><span style="color:var(--sc-dim);"># Crawl 50 pages</span></div>
                <div style="color:var(--sc-green);">php scripts/alfred-crawler.php crawl 50</div>
                <br>
                <div><span style="color:var(--sc-dim);"># Show stats</span></div>
                <div style="color:var(--sc-green);">php scripts/alfred-crawler.php stats</div>
                <br>
                <div><span style="color:var(--sc-dim);"># Reindex all to Meilisearch</span></div>
                <div style="color:var(--sc-green);">php scripts/alfred-crawler.php reindex</div>
                <br>
                <div><span style="color:var(--sc-dim);"># Cron (every 10 min)</span></div>
                <div style="color:var(--sc-gold);">*/10 * * * * php <?php echo dirname(__DIR__); ?>/scripts/alfred-crawler.php crawl 50</div>
            </div>
        </div>
    </div>

    <!-- ═══ SEARCH ANALYTICS PANEL ═══ -->
    <div class="sc-panel" id="panel-analytics">
        <div class="sc-grid-4">
            <div class="sc-stat">
                <div class="num"><?php echo number_format($stats['searches_total']); ?></div>
                <div class="lbl">All-Time Searches</div>
            </div>
            <div class="sc-stat gold">
                <div class="num"><?php echo number_format($stats['searches_today']); ?></div>
                <div class="lbl">Today</div>
            </div>
            <div class="sc-stat green">
                <div class="num">0</div>
                <div class="lbl">Cookies Set</div>
            </div>
            <div class="sc-stat green">
                <div class="num">0</div>
                <div class="lbl">Users Tracked</div>
            </div>
        </div>

        <!-- Live Search Test -->
        <div class="sc-card">
            <h3><i class="fas fa-flask"></i> Live Search Test</h3>
            <div class="sc-form">
                <input class="sc-input" id="test-query" placeholder="Test a search query..." />
                <select class="sc-input" id="test-mode" style="max-width:140px;">
                    <option value="web">Web</option>
                    <option value="news">News</option>
                    <option value="code">Code</option>
                    <option value="instant">Instant</option>
                </select>
                <button class="sc-btn primary" onclick="testSearch()"><i class="fas fa-search"></i> Search</button>
            </div>
            <div id="test-results" style="margin-top:16px;"></div>
        </div>

        <!-- Query Analytics -->
        <div class="sc-card">
            <h3><i class="fas fa-fire"></i> Top Queries (All Time)</h3>
            <?php
            $allTimeQueries = [];
            if ($db) {
                try {
                    $stmt = $db->query("SELECT query, COUNT(*) as cnt, ROUND(AVG(response_ms)) as avg_ms FROM alfred_search_log GROUP BY query ORDER BY cnt DESC LIMIT 25");
                    $allTimeQueries = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {}
            }
            ?>
            <?php if (empty($allTimeQueries)): ?>
                <p style="color:var(--sc-dim);">No search data yet.</p>
            <?php else: ?>
                <table class="sc-table">
                    <thead><tr><th>Query</th><th>Searches</th><th>Avg Response</th></tr></thead>
                    <tbody>
                    <?php foreach ($allTimeQueries as $q): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($q['query']); ?></td>
                            <td><span class="sc-pill blue"><?php echo $q['cnt']; ?></span></td>
                            <td style="color:var(--sc-dim);"><?php echo $q['avg_ms']; ?>ms</td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- ═══ MARKETING INTEL PANEL ═══ -->
    <div class="sc-panel" id="panel-marketing">
        <div class="sc-card">
            <h3><i class="fas fa-bullseye"></i> Marketing Strategy: Crawler Visibility</h3>
            <p style="color:var(--sc-dim);line-height:1.7;margin-bottom:16px;">
                Every server admin who sees <code style="color:var(--sc-accent);">AlfredSearchBot/1.0</code> in their logs
                will visit <a href="/about-crawler" style="color:var(--sc-accent);">gositeme.com/about-crawler</a>.
                That page is optimized to convert sysadmins into users, API customers, and white-label partners.
            </p>
            <div class="sc-grid-3" style="margin-top:20px;">
                <div class="dept-card">
                    <div class="dept-icon" style="background:rgba(96,165,250,0.15);color:var(--sc-accent);"><i class="fas fa-eye"></i></div>
                    <h4>Visibility Channel</h4>
                    <p>Every crawled domain = 1 server admin who sees our bot in their logs. At scale, this is thousands of tech decision-makers discovering us organically.</p>
                    <span class="rec-tag sprint">Active Now</span>
                </div>
                <div class="dept-card">
                    <div class="dept-icon" style="background:rgba(52,211,153,0.15);color:var(--sc-green);"><i class="fas fa-handshake"></i></div>
                    <h4>Trust Signal</h4>
                    <p>The about-crawler page shows transparency: robots.txt compliance, rate limits, privacy comparison table. Server admins respect ethical bots.</p>
                    <span class="rec-tag sprint">Active Now</span>
                </div>
                <div class="dept-card">
                    <div class="dept-icon" style="background:rgba(167,139,250,0.15);color:var(--sc-accent2);"><i class="fas fa-funnel-dollar"></i></div>
                    <h4>Conversion Funnel</h4>
                    <p>Crawler page → Try Alfred Search → API Documentation → White-Label inquiry. Each crawled domain feeds the top of this funnel.</p>
                    <span class="rec-tag q2">Building Q2</span>
                </div>
            </div>
        </div>

        <!-- Marketing Metrics -->
        <div class="sc-card">
            <h3><i class="fas fa-chart-line"></i> Marketing KPIs</h3>
            <div class="sc-grid-4">
                <div class="mkt-metric">
                    <div class="icon" style="color:var(--sc-accent);"><i class="fas fa-globe"></i></div>
                    <div class="val"><?php echo number_format($stats['domains_known']); ?></div>
                    <div class="label">Domains Reached</div>
                </div>
                <div class="mkt-metric">
                    <div class="icon" style="color:var(--sc-green);"><i class="fas fa-user-tie"></i></div>
                    <div class="val"><?php echo number_format($stats['domains_known']); ?></div>
                    <div class="label">Sysadmins Exposed</div>
                </div>
                <div class="mkt-metric">
                    <div class="icon" style="color:var(--sc-gold);"><i class="fas fa-spider"></i></div>
                    <div class="val"><?php echo number_format($stats['pages_indexed']); ?></div>
                    <div class="label">Pages Crawled</div>
                </div>
                <div class="mkt-metric">
                    <div class="icon" style="color:var(--sc-accent2);"><i class="fas fa-link"></i></div>
                    <div class="val">/about-crawler</div>
                    <div class="label">Landing Page Active</div>
                </div>
            </div>
        </div>

        <!-- Crawler as Marketing -->
        <div class="sc-card">
            <h3><i class="fas fa-lightbulb"></i> Additional Marketing Channels</h3>
            <div class="sc-grid-3" style="margin:0;">
                <div class="dept-card">
                    <h4><i class="fas fa-newspaper" style="color:var(--sc-accent);margin-right:6px;"></i> Press Release</h4>
                    <p>"GoSiteMe launches Alfred Search — the first fully sovereign, privacy-first search engine with its own web index."</p>
                    <span class="rec-tag q2">Draft Ready</span>
                </div>
                <div class="dept-card">
                    <h4><i class="fab fa-hacker-news" style="color:var(--sc-gold);margin-right:6px;"></i> Hacker News</h4>
                    <p>"Show HN: Alfred Search — Privacy-first search with sovereign web index, AI answers, zero tracking"</p>
                    <span class="rec-tag q2">Scheduled</span>
                </div>
                <div class="dept-card">
                    <h4><i class="fab fa-product-hunt" style="color:var(--sc-red);margin-right:6px;"></i> Product Hunt</h4>
                    <p>Launch day: screenshots, comparison table, live demo. Goal: Top 5 Product of the Day.</p>
                    <span class="rec-tag q2">Planned</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ DEPARTMENT REPORTS PANEL ═══ -->
    <div class="sc-panel" id="panel-departments">
        <div class="sc-card" style="margin-bottom:20px;">
            <h3><i class="fas fa-building"></i> 12-Department Consultation — Search Engine Upgrade Recommendations</h3>
            <p style="color:var(--sc-dim);">Each department was consulted on how Alfred Search can leverage existing infrastructure for competitive advantage.</p>
        </div>

        <div class="sc-grid-3">
            <!-- Voice -->
            <div class="dept-card">
                <div class="dept-icon" style="background:rgba(96,165,250,0.15);color:var(--sc-accent);"><i class="fas fa-microphone"></i></div>
                <h4>Voice Department</h4>
                <p><strong style="color:#fff;">Voice Search Input</strong> — Speech-to-text queries via Whisper/Ollama STT</p>
                <p><strong style="color:#fff;">Voice Result Delivery</strong> — TTS-optimized results using 18-voice library</p>
                <p><strong style="color:#fff;">Call Transcript Search</strong> — Index support call history</p>
                <span class="rec-tag sprint">30-Day Sprint</span>
            </div>

            <!-- Fleet -->
            <div class="dept-card">
                <div class="dept-icon" style="background:rgba(52,211,153,0.15);color:var(--sc-green);"><i class="fas fa-satellite-dish"></i></div>
                <h4>Fleet / Agents</h4>
                <p><strong style="color:#fff;">Agent Search Routing</strong> — Auto-route queries to domain expert agents</p>
                <p><strong style="color:#fff;">Knowledge Graph</strong> — Agent-learned solutions auto-indexed</p>
                <p><strong style="color:#fff;">Fleet Analytics</strong> — What are agents searching for?</p>
                <span class="rec-tag q2">Q2 2026</span>
            </div>

            <!-- Security -->
            <div class="dept-card">
                <div class="dept-icon" style="background:rgba(239,68,68,0.15);color:var(--sc-red);"><i class="fas fa-shield-halved"></i></div>
                <h4>Security / Veil</h4>
                <p><strong style="color:#fff;">Encrypted Index</strong> — Kyber-768 encrypted search index at rest</p>
                <p><strong style="color:#fff;">Threat Intel</strong> — Detect malware/phishing in crawled pages</p>
                <p><strong style="color:#fff;">Zero-Knowledge Admin</strong> — Admin panel without raw query access</p>
                <span class="rec-tag sprint">30-Day Sprint</span>
            </div>

            <!-- Commerce -->
            <div class="dept-card">
                <div class="dept-icon" style="background:rgba(167,139,250,0.15);color:var(--sc-accent2);"><i class="fas fa-store"></i></div>
                <h4>Commerce / Marketplace</h4>
                <p><strong style="color:#fff;">Product Indexing</strong> — Auto-index marketplace AI employees + tools</p>
                <p><strong style="color:#fff;">Ethical Ads</strong> — Context-based results, not profile-based tracking</p>
                <p><strong style="color:#fff;">Creator Search Boost</strong> — Paid visibility for creators ($10/mo)</p>
                <span class="rec-tag q2">Q2 2026</span>
            </div>

            <!-- AI/Research -->
            <div class="dept-card">
                <div class="dept-icon" style="background:rgba(251,191,36,0.15);color:var(--sc-gold);"><i class="fas fa-brain"></i></div>
                <h4>AI / Research</h4>
                <p><strong style="color:#fff;">Semantic RAG</strong> — Ollama embeddings + retrieval-augmented generation</p>
                <p><strong style="color:#fff;">Intent Classification</strong> — Factual vs how-to vs opinion routing</p>
                <p><strong style="color:#fff;">Learning Loop</strong> — Retrain ranking from click signals</p>
                <span class="rec-tag sprint">30-Day Sprint</span>
            </div>

            <!-- Communications -->
            <div class="dept-card">
                <div class="dept-icon" style="background:rgba(96,165,250,0.15);color:var(--sc-accent);"><i class="fas fa-comments"></i></div>
                <h4>Communications</h4>
                <p><strong style="color:#fff;">Team Chat Search</strong> — Index 12 months of team conversations</p>
                <p><strong style="color:#fff;">Context Retention</strong> — Follow-up queries inherit conversation</p>
                <p><strong style="color:#fff;">Knowledge Gaps</strong> — Dashboard showing unanswered team questions</p>
                <span class="rec-tag q2">Q2 2026</span>
            </div>

            <!-- Developer -->
            <div class="dept-card">
                <div class="dept-icon" style="background:rgba(52,211,153,0.15);color:var(--sc-green);"><i class="fas fa-code"></i></div>
                <h4>Developer</h4>
                <p><strong style="color:#fff;">Search SDKs</strong> — NPM, PyPI, Composer SDK wrappers</p>
                <p><strong style="color:#fff;">Analytics API</strong> — Developer-facing search metrics endpoint</p>
                <p><strong style="color:#fff;">Plugin Marketplace</strong> — Custom ranking/formatting plugins</p>
                <span class="rec-tag q3">Q3 2026</span>
            </div>

            <!-- Finance -->
            <div class="dept-card">
                <div class="dept-icon" style="background:rgba(251,191,36,0.15);color:var(--sc-gold);"><i class="fas fa-dollar-sign"></i></div>
                <h4>Finance</h4>
                <p><strong style="color:#fff;">Cost per Query</strong> — Transparent breakdown: crawler + AI + bandwidth</p>
                <p><strong style="color:#fff;">Usage Pricing</strong> — Free (100/day), Starter ($4.99), Pro ($24.99)</p>
                <p><strong style="color:#fff;">Crawler ROI</strong> — Which domains cost most to crawl vs value</p>
                <span class="rec-tag q2">Q2 2026</span>
            </div>

            <!-- Enterprise -->
            <div class="dept-card">
                <div class="dept-icon" style="background:rgba(96,165,250,0.15);color:var(--sc-accent);"><i class="fas fa-building"></i></div>
                <h4>Enterprise</h4>
                <p><strong style="color:#fff;">White-Label Search</strong> — Custom branding, own domain, SLA</p>
                <p><strong style="color:#fff;">Custom Crawl Domains</strong> — Enterprise chooses what to index</p>
                <p><strong style="color:#fff;">Audit Trails</strong> — Encrypted access logs, monthly SLA reports</p>
                <span class="rec-tag q3">Q3 2026</span>
            </div>

            <!-- Gaming/VR -->
            <div class="dept-card">
                <div class="dept-icon" style="background:rgba(239,68,68,0.15);color:var(--sc-red);"><i class="fas fa-gamepad"></i></div>
                <h4>Gaming / VR</h4>
                <p><strong style="color:#fff;">In-Game Search</strong> — Asset/mod/player discovery while playing</p>
                <p><strong style="color:#fff;">Game Content Index</strong> — Chess puzzles, game levels, leaderboards</p>
                <p><strong style="color:#fff;">VR Environment Search</strong> — VR experience discovery + compatibility</p>
                <span class="rec-tag q3">Q3 2026</span>
            </div>

            <!-- Healthcare -->
            <div class="dept-card">
                <div class="dept-icon" style="background:rgba(45,212,191,0.15);color:#2dd4bf;"><i class="fas fa-heart-pulse"></i></div>
                <h4>Healthcare</h4>
                <p><strong style="color:#fff;">Medical Quality Scoring</strong> — Source authority ranking (NIH/WHO/Mayo priority)</p>
                <p><strong style="color:#fff;">HIPAA-Safe Search</strong> — Encrypted patient data search for providers</p>
                <p><strong style="color:#fff;">Medical Disambiguation</strong> — "MI" → heart attack vs Michigan vs mortgage</p>
                <span class="rec-tag q3">Q3 2026</span>
            </div>

            <!-- Collaboration -->
            <div class="dept-card">
                <div class="dept-icon" style="background:rgba(167,139,250,0.15);color:var(--sc-accent2);"><i class="fas fa-users"></i></div>
                <h4>Collaboration</h4>
                <p><strong style="color:#fff;">Shared Collections</strong> — Curate & share search results as team collections</p>
                <p><strong style="color:#fff;">Search Leaderboards</strong> — Gamified "Power Searcher" achievements</p>
                <p><strong style="color:#fff;">Real-time Research</strong> — Collaborative search sessions via WebSocket</p>
                <span class="rec-tag q2">Q2 2026</span>
            </div>
        </div>
    </div>

    <!-- ═══ INTELLIGENCE PANEL ═══ -->
    <div class="sc-panel" id="panel-intelligence">
        <div class="sc-grid-4">
            <div class="sc-stat">
                <div class="num"><?php echo number_format($stats['intel_domains'] ?? 0); ?></div>
                <div class="lbl">Domains Profiled</div>
            </div>
            <div class="sc-stat green">
                <div class="num"><?php echo number_format($stats['intel_classified'] ?? 0); ?></div>
                <div class="lbl">Pages Classified</div>
            </div>
            <div class="sc-stat gold">
                <div class="num"><?php echo number_format($stats['intel_feeds'] ?? 0); ?></div>
                <div class="lbl">Active RSS Feeds</div>
            </div>
            <div class="sc-stat" style="border-color:rgba(239,68,68,0.3);">
                <div class="num" style="color:#ef4444;"><?php echo number_format($stats['intel_threats'] ?? 0); ?></div>
                <div class="lbl">Threat Domains</div>
            </div>
        </div>

        <div class="sc-grid-2" style="margin-top:20px;">
            <div class="sc-card">
                <h3><i class="fas fa-trophy"></i> Top Authorities</h3>
                <table class="sc-table">
                    <thead><tr><th>Domain</th><th>Score</th><th>Category</th><th>Threat</th></tr></thead>
                    <tbody>
                    <?php foreach (($stats['intel_top_authorities'] ?? []) as $a): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($a['domain']); ?></code></td>
                            <td><strong><?php echo number_format($a['authority_score'], 1); ?></strong></td>
                            <td><?php echo htmlspecialchars($a['category']); ?></td>
                            <td><span class="sc-badge <?php echo $a['threat_level'] === 'safe' ? 'green' : 'red'; ?>"><?php echo $a['threat_level']; ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($stats['intel_top_authorities'] ?? [])): ?>
                        <tr><td colspan="4" style="color:var(--sc-muted);text-align:center;">Run <code>php scripts/intel-crawler.php full</code> to populate</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="sc-card">
                <h3><i class="fas fa-tags"></i> Content Categories</h3>
                <?php foreach (($stats['intel_categories'] ?? []) as $cat): ?>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid rgba(255,255,255,0.05);">
                        <span><?php echo htmlspecialchars($cat['category']); ?></span>
                        <span style="background:rgba(96,165,250,0.1);color:var(--sc-accent);padding:2px 10px;border-radius:20px;font-size:12px;font-weight:700;"><?php echo number_format($cat['cnt']); ?></span>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($stats['intel_categories'] ?? [])): ?>
                    <p style="color:var(--sc-muted);text-align:center;padding:20px;">No classifications yet. Run <code>php scripts/intel-crawler.php classify</code></p>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($stats['intel_threat_list'] ?? [])): ?>
        <div class="sc-card" style="margin-top:20px;border-color:rgba(239,68,68,0.2);">
            <h3 style="color:#ef4444;"><i class="fas fa-exclamation-triangle"></i> Threat Alerts</h3>
            <table class="sc-table">
                <thead><tr><th>Domain</th><th>Level</th><th>Tags</th></tr></thead>
                <tbody>
                <?php foreach ($stats['intel_threat_list'] as $t): ?>
                    <tr>
                        <td><code><?php echo htmlspecialchars($t['domain']); ?></code></td>
                        <td><span class="sc-badge red"><?php echo $t['threat_level']; ?></span></td>
                        <td><?php $tags = json_decode($t['threat_tags'] ?? '[]', true); echo htmlspecialchars(implode(', ', $tags ?: [])); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <div class="sc-card" style="margin-top:20px;">
            <h3><i class="fas fa-terminal"></i> Intel Commands</h3>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:12px;">
                <div style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06);border-radius:10px;padding:16px;">
                    <code style="color:var(--sc-accent);">php scripts/intel-crawler.php full</code>
                    <div style="color:var(--sc-muted);font-size:12px;margin-top:6px;">Run complete 7-step intelligence cycle</div>
                </div>
                <div style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06);border-radius:10px;padding:16px;">
                    <code style="color:var(--sc-accent);">php scripts/intel-crawler.php intel example.com</code>
                    <div style="color:var(--sc-muted);font-size:12px;margin-top:6px;">Full domain intelligence report</div>
                </div>
                <div style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06);border-radius:10px;padding:16px;">
                    <code style="color:var(--sc-accent);">php scripts/intel-crawler.php classify 100</code>
                    <div style="color:var(--sc-muted);font-size:12px;margin-top:6px;">AI-classify 100 pages via Groq</div>
                </div>
                <div style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06);border-radius:10px;padding:16px;">
                    <code style="color:var(--sc-accent);">php scripts/intel-crawler.php authority</code>
                    <div style="color:var(--sc-muted);font-size:12px;margin-top:6px;">Build link graph + calculate PageRank</div>
                </div>
            </div>
            <div style="margin-top:16px;display:flex;gap:12px;">
                <div style="font-size:12px;color:var(--sc-muted);">
                    <i class="fas fa-clock"></i> Auto-schedule: Feeds + Changes every 2h, Authority daily via heartbeat
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ UPGRADE PIPELINE PANEL ═══ -->
    <div class="sc-panel" id="panel-upgrades">
        <div class="sc-card">
            <h3><i class="fas fa-rocket"></i> Quick Wins — 30-Day Sprints</h3>
            <div style="line-height:2.2;">
                <div style="display:flex;align-items:center;gap:8px;padding:8px 0;border-bottom:1px solid rgba(96,165,250,0.05);">
                    <span class="sc-pill blue">Sprint</span>
                    <span style="color:#fff;font-weight:600;">Semantic Search + RAG</span>
                    <span style="color:var(--sc-dim);font-size:0.8rem;margin-left:auto;">Enable Ollama embeddings in Meilisearch → RAG pipeline</span>
                </div>
                <div style="display:flex;align-items:center;gap:8px;padding:8px 0;border-bottom:1px solid rgba(96,165,250,0.05);">
                    <span class="sc-pill green">✓ DONE</span>
                    <span style="color:var(--sc-green);font-weight:600;">Encrypted Query Logs</span>
                    <span style="color:var(--sc-dim);font-size:0.8rem;margin-left:auto;">Sodium secretbox encryption → zero-knowledge at rest</span>
                </div>
                <div style="display:flex;align-items:center;gap:8px;padding:8px 0;border-bottom:1px solid rgba(96,165,250,0.05);">
                    <span class="sc-pill green">✓ DONE</span>
                    <span style="color:var(--sc-green);font-weight:600;">Voice Search Input</span>
                    <span style="color:var(--sc-dim);font-size:0.8rem;margin-left:auto;">Web Speech API + Whisper fallback → search.php mic button</span>
                </div>
                <div style="display:flex;align-items:center;gap:8px;padding:8px 0;border-bottom:1px solid rgba(96,165,250,0.05);">
                    <span class="sc-pill blue">Sprint</span>
                    <span style="color:#fff;font-weight:600;">Team Chat Indexing</span>
                    <span style="color:var(--sc-dim);font-size:0.8rem;margin-left:auto;">Sync team_chat → Meilisearch nightly</span>
                </div>
                <div style="display:flex;align-items:center;gap:8px;padding:8px 0;">
                    <span class="sc-pill green">✓ DONE</span>
                    <span style="color:var(--sc-green);font-weight:600;">Cost per Query Tracking</span>
                    <span style="color:var(--sc-dim);font-size:0.8rem;margin-left:auto;">LLM token cost + financial dashboard → $<?php echo number_format($stats['cost_total'], 4); ?> total</span>
                </div>
            </div>
        </div>

        <div class="sc-card">
            <h3><i class="fas fa-calendar-alt"></i> 6-Month Roadmap</h3>
            <div class="sc-grid-3" style="margin:0;">
                <div class="dept-card">
                    <h4 style="color:var(--sc-green);">Q2 2026</h4>
                    <p>Voice search, agent routing, encrypted logs, team search, product indexing, ethical ads system, cost tracking</p>
                </div>
                <div class="dept-card">
                    <h4 style="color:var(--sc-accent);">Q3 2026</h4>
                    <p>Semantic RAG, white-label appliance, healthcare quality scoring, developer SDKs, game content search</p>
                </div>
                <div class="dept-card">
                    <h4 style="color:var(--sc-accent2);">Q4 2026</h4>
                    <p>Creator monetization, enterprise crawl UI, VR content discovery, collaborative research, search plugin marketplace</p>
                </div>
            </div>
        </div>

        <!-- Competitive Edge -->
        <div class="sc-card">
            <h3><i class="fas fa-trophy"></i> Competitive Advantages vs Google/Bing/DuckDuckGo</h3>
            <table class="sc-table">
                <thead><tr><th>Feature</th><th>Others</th><th>Alfred Search</th><th>Dept</th></tr></thead>
                <tbody>
                    <tr><td>Own web index</td><td>Google/Bing yes, DDG no</td><td style="color:var(--sc-green);font-weight:700;">Yes (sovereign)</td><td>AI/Research</td></tr>
                    <tr><td>Self-hostable</td><td style="color:var(--sc-red);">None</td><td style="color:var(--sc-green);font-weight:700;">Yes</td><td>Enterprise</td></tr>
                    <tr><td>Voice search + TTS results</td><td>Google only</td><td style="color:var(--sc-green);font-weight:700;">18 voices, 6 langs</td><td>Voice</td></tr>
                    <tr><td>AI agent routing</td><td style="color:var(--sc-red);">None</td><td style="color:var(--sc-green);font-weight:700;">100+ specialized agents</td><td>Fleet</td></tr>
                    <tr><td>Post-quantum encryption</td><td style="color:var(--sc-red);">None</td><td style="color:var(--sc-green);font-weight:700;">Kyber-768</td><td>Security</td></tr>
                    <tr><td>White-label</td><td style="color:var(--sc-red);">None</td><td style="color:var(--sc-green);font-weight:700;">Full rebrand</td><td>Enterprise</td></tr>
                    <tr><td>Search plugin marketplace</td><td style="color:var(--sc-red);">None</td><td style="color:var(--sc-green);font-weight:700;">Coming Q3</td><td>Developer</td></tr>
                    <tr><td>HIPAA-safe search</td><td style="color:var(--sc-red);">None</td><td style="color:var(--sc-green);font-weight:700;">Coming Q3</td><td>Healthcare</td></tr>
                    <tr><td>Collaborative search sessions</td><td style="color:var(--sc-red);">None</td><td style="color:var(--sc-green);font-weight:700;">LiveSync via WS</td><td>Collaboration</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Tab switching
document.querySelectorAll('.sc-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.sc-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.sc-panel').forEach(p => p.classList.remove('active'));
        tab.classList.add('active');
        const panel = document.getElementById('panel-' + tab.dataset.panel);
        if (panel) panel.classList.add('active');
    });
});

// System status checks
async function checkStatus(url, elementId) {
    const el = document.getElementById(elementId);
    try {
        const r = await fetch(url, { signal: AbortSignal.timeout(5000) });
        el.style.color = r.ok ? 'var(--sc-green)' : 'var(--sc-red)';
    } catch {
        el.style.color = 'var(--sc-red)';
    }
}

checkStatus('/api/alfred-search.php?action=stats', 'meili-status');
checkStatus('/api/alfred-search.php?action=trending', 'jina-status');
// Ollama
fetch('http://localhost:11434/api/tags', { signal: AbortSignal.timeout(3000) })
    .then(r => { document.getElementById('ollama-status').style.color = r.ok ? 'var(--sc-green)' : 'var(--sc-red)'; })
    .catch(() => { document.getElementById('ollama-status').style.color = 'var(--sc-red)'; });

// Add URL
async function addUrl() {
    const url = document.getElementById('add-url-input').value.trim();
    if (!url) return;
    const r = await fetch('/api/crawler-admin.php?action=add_url', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'url=' + encodeURIComponent(url)
    });
    const data = await r.json();
    document.getElementById('add-url-result').textContent = data.added ? '✓ Added to queue' : (data.error || 'Already in queue');
    document.getElementById('add-url-input').value = '';
}

// Bulk add
async function addBulkUrls() {
    const text = document.getElementById('bulk-urls').value.trim();
    if (!text) return;
    const urls = text.split('\n').map(u => u.trim()).filter(Boolean);
    const r = await fetch('/api/crawler-admin.php?action=add_urls', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ urls })
    });
    const data = await r.json();
    document.getElementById('bulk-url-result').textContent = `✓ Added ${data.added}/${data.total_submitted} URLs`;
    document.getElementById('bulk-urls').value = '';
}

// Test search
async function testSearch() {
    const q = document.getElementById('test-query').value.trim();
    if (!q) return;
    const mode = document.getElementById('test-mode').value;
    const target = document.getElementById('test-results');
    target.innerHTML = '<div style="color:var(--sc-dim);">Searching...</div>';

    try {
        const r = await fetch(`/api/alfred-search.php?q=${encodeURIComponent(q)}&mode=${mode}`);
        const data = await r.json();
        let html = `<div style="color:var(--sc-dim);margin-bottom:12px;">
            ${data.total || 0} results in ${data.response_ms || 0}ms (mode: ${data.mode})
            ${data.understanding ? ` | intent: ${data.understanding.intent} | category: ${data.understanding.category || 'general'}` : ''}
            ${data.cost_usd !== undefined ? ` | cost: $${data.cost_usd.toFixed(6)}` : ''}
            ${data.privacy?.logs_encrypted ? ' | 🔒 encrypted' : ''}
        </div>`;

        if (data.instant_answer) {
            html += `<div style="background:rgba(96,165,250,0.1);border:1px solid rgba(96,165,250,0.2);border-radius:10px;padding:14px;margin-bottom:12px;color:var(--sc-text);font-size:0.9rem;">${data.instant_answer}</div>`;
        }

        (data.results || []).forEach(r => {
            html += `<div style="padding:10px 0;border-bottom:1px solid rgba(96,165,250,0.05);">
                <div style="font-size:0.75rem;color:var(--sc-green);">${r.source || ''} · ${r.type || 'web'}</div>
                <a href="${r.url}" target="_blank" rel="noopener" style="color:var(--sc-accent);font-weight:600;text-decoration:none;">${r.title || '(untitled)'}</a>
                <div style="color:var(--sc-dim);font-size:0.85rem;margin-top:4px;">${(r.snippet || '').substring(0, 200)}</div>
                <div style="font-size:0.7rem;color:var(--sc-dim);margin-top:2px;">${r.rank_reason || ''}</div>
            </div>`;
        });
        target.innerHTML = html;
    } catch (e) {
        target.innerHTML = `<div style="color:var(--sc-red);">Error: ${e.message}</div>`;
    }
}

function refreshStats() {
    window.location.reload();
}

// Enter key for search test
document.getElementById('test-query')?.addEventListener('keypress', e => {
    if (e.key === 'Enter') testSearch();
});
document.getElementById('add-url-input')?.addEventListener('keypress', e => {
    if (e.key === 'Enter') addUrl();
});
</script>

<?php require_once dirname(__DIR__) . '/includes/site-footer.inc.php'; ?>
