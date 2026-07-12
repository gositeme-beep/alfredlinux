<?php
/**
 * Growth Management Dashboard API
 * Cross-project agent ecosystem monitoring & wave management
 * GoSiteMe v18.2
 */
define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$db = getDB();

// Auth check — owner only for write operations
function requireOwner() {
require_once dirname(__DIR__) . '/includes/api-security.php';
    session_start();
    $clientId = $_SESSION['client_id'] ?? 0;
    if (!in_array($clientId, [1, 33])) {
        $secret = $_SERVER['HTTP_X_INTERNAL_SECRET'] ?? '';
        if (!defined('INTERNAL_SECRET') || $secret !== INTERNAL_SECRET) {
            jsonResponse(['error' => 'Unauthorized'], 403);
        }
    }
}

switch ($action) {

    // ─── Overview KPIs ───────────────────────────────────────────
    case 'overview':
        $kpis = [];

        // Total agents
        $kpis['total_agents'] = (int) $db->query("SELECT COUNT(*) FROM agent_profiles")->fetchColumn();
        $kpis['active_agents'] = (int) $db->query("SELECT COUNT(*) FROM agent_profiles WHERE status = 'active'")->fetchColumn();

        // Department breakdown
        $r = $db->query("SELECT department, COUNT(*) as cnt FROM agent_profiles GROUP BY department ORDER BY cnt DESC");
        $kpis['departments'] = $r->fetchAll(PDO::FETCH_ASSOC);

        // AgentPedia
        $kpis['agentpedia'] = [
            'articles' => (int) $db->query("SELECT COUNT(*) FROM agentpedia_articles")->fetchColumn(),
            'published' => (int) $db->query("SELECT COUNT(*) FROM agentpedia_articles WHERE status IN ('published','featured')")->fetchColumn(),
            'words' => (int) $db->query("SELECT COALESCE(SUM(word_count),0) FROM agentpedia_articles")->fetchColumn(),
            'contributors' => (int) $db->query("SELECT COUNT(DISTINCT agent_id) FROM agentpedia_contributions")->fetchColumn(),
            'enrolled' => (int) $db->query("SELECT COUNT(*) FROM agentpedia_agent_stats")->fetchColumn(),
            'reviews' => (int) $db->query("SELECT COUNT(*) FROM agentpedia_reviews")->fetchColumn(),
            'categories' => (int) $db->query("SELECT COUNT(*) FROM agentpedia_categories")->fetchColumn(),
        ];

        // AgentWork
        $kpis['agentwork'] = [
            'gigs' => (int) $db->query("SELECT COUNT(*) FROM agentwork_gigs")->fetchColumn(),
            'agents' => (int) $db->query("SELECT COUNT(DISTINCT agent_id) FROM agentwork_gigs")->fetchColumn(),
            'projects' => (int) $db->query("SELECT COUNT(*) FROM agentwork_projects")->fetchColumn(),
            'categories' => (int) $db->query("SELECT COUNT(DISTINCT category) FROM agentwork_gigs")->fetchColumn(),
        ];

        // Gov Canada
        $kpis['gov_canada'] = [
            'pages' => (int) $db->query("SELECT COUNT(*) FROM gov_canada_pages")->fetchColumn(),
            'sources' => (int) $db->query("SELECT COUNT(*) FROM gov_canada_sources")->fetchColumn(),
            'crawled' => (int) $db->query("SELECT COUNT(*) FROM gov_canada_sources WHERE last_crawled IS NOT NULL")->fetchColumn(),
            'departments' => (int) $db->query("SELECT COUNT(*) FROM gov_canada_structure")->fetchColumn(),
        ];

        // Growth waves
        $r = $db->query("SELECT * FROM agent_growth_waves ORDER BY id");
        $kpis['waves'] = $r->fetchAll(PDO::FETCH_ASSOC);

        // Participation summary
        $pediaAgents = (int) $db->query("SELECT COUNT(*) FROM agentpedia_agent_stats")->fetchColumn();
        $workAgents = (int) $db->query("SELECT COUNT(DISTINCT agent_id) FROM agentwork_gigs")->fetchColumn();

        // Cross-assignment (agents in multiple projects)
        $cross = $db->query("
            SELECT COUNT(DISTINCT a.agent_id) FROM agentpedia_agent_stats a
            WHERE EXISTS (SELECT 1 FROM agentwork_gigs g WHERE g.agent_id = a.agent_id)
        ")->fetchColumn();
        $kpis['participation'] = [
            'agentpedia' => $pediaAgents,
            'agentwork' => $workAgents,
            'cross_project' => (int) $cross,
            'unassigned' => $kpis['active_agents'] - $pediaAgents, // rough estimate
        ];

        // Target progress
        $kpis['target'] = 5000;
        $kpis['progress_pct'] = round(($kpis['active_agents'] / 5000) * 100, 1);

        jsonResponse($kpis);
        break;

    // ─── Wave Management ─────────────────────────────────────────
    case 'waves':
        $r = $db->query("SELECT * FROM agent_growth_waves ORDER BY id");
        jsonResponse(['waves' => $r->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'approve-wave':
        requireOwner();
        $waveId = (int) ($_POST['wave_id'] ?? 0);
        if (!$waveId) jsonResponse(['error' => 'wave_id required'], 400);
        $st = $db->prepare("UPDATE agent_growth_waves SET status = 'approved', approved_at = NOW(), approved_by = 1 WHERE id = ? AND status IN ('planned','proposed')");
        $st->execute([$waveId]);
        jsonResponse(['ok' => true, 'affected' => $st->rowCount()]);
        break;

    case 'deploy-wave':
        requireOwner();
        $waveId = (int) ($_POST['wave_id'] ?? 0);
        if (!$waveId) jsonResponse(['error' => 'wave_id required'], 400);

        // Get wave details
        $wave = $db->prepare("SELECT * FROM agent_growth_waves WHERE id = ? AND status = 'approved'");
        $wave->execute([$waveId]);
        $w = $wave->fetch(PDO::FETCH_ASSOC);
        if (!$w) jsonResponse(['error' => 'Wave not found or not approved'], 404);

        // Execute via CLI in background
        $cmd = "cd " . escapeshellarg(dirname(__DIR__)) . " && php scripts/agent-scaler.php deploy " . (int)$w['wave'] . " > /dev/null 2>&1 &";
        exec($cmd);
        jsonResponse(['ok' => true, 'message' => "Wave {$w['wave']} deployment started in background", 'target' => $w['target_count']]);
        break;

    case 'reject-wave':
        requireOwner();
        $waveId = (int) ($_POST['wave_id'] ?? 0);
        $reason = substr(trim($_POST['reason'] ?? ''), 0, 500);
        $st = $db->prepare("UPDATE agent_growth_waves SET status = 'rejected', rejection_reason = ? WHERE id = ? AND status IN ('planned','proposed','approved')");
        $st->execute([$reason, $waveId]);
        jsonResponse(['ok' => true, 'affected' => $st->rowCount()]);
        break;

    // ─── Department Details ──────────────────────────────────────
    case 'department':
        $dept = trim($_GET['dept'] ?? '');
        if (!$dept) jsonResponse(['error' => 'dept required'], 400);

        $st = $db->prepare("SELECT agent_id, name, department, status, skills, created_at FROM agent_profiles WHERE department = ? ORDER BY created_at DESC LIMIT 50");
        $st->execute([$dept]);
        $agents = $st->fetchAll(PDO::FETCH_ASSOC);

        // Check their participation
        foreach ($agents as &$a) {
            $pedia = $db->prepare("SELECT articles_created, total_words_written FROM agentpedia_agent_stats WHERE agent_id = ?");
            $pedia->execute([$a['agent_id']]);
            $a['agentpedia'] = $pedia->fetch(PDO::FETCH_ASSOC) ?: null;

            $work = $db->prepare("SELECT COUNT(*) as gigs FROM agentwork_gigs WHERE agent_id = ?");
            $work->execute([$a['agent_id']]);
            $a['agentwork_gigs'] = (int) $work->fetchColumn();
        }

        jsonResponse(['department' => $dept, 'count' => count($agents), 'agents' => $agents]);
        break;

    // ─── Top Contributors ────────────────────────────────────────
    case 'top-contributors':
        $limit = min(50, max(10, (int) ($_GET['limit'] ?? 20)));
        $r = $db->query("
            SELECT 
                a.agent_id, a.name, a.department, a.avatar_url,
                COALESCE(p.articles_created, 0) as pedia_articles,
                COALESCE(p.total_words_written, 0) as pedia_words,
                (SELECT COUNT(*) FROM agentwork_gigs g WHERE g.agent_id = a.agent_id) as work_gigs,
                (COALESCE(p.articles_created, 0) * 10 + COALESCE(p.total_words_written, 0) / 100 + 
                 (SELECT COUNT(*) FROM agentwork_gigs g WHERE g.agent_id = a.agent_id) * 5) as impact_score
            FROM agent_profiles a
            LEFT JOIN agentpedia_agent_stats p ON p.agent_id = a.agent_id
            WHERE a.status = 'active'
            ORDER BY impact_score DESC
            LIMIT {$limit}
        ");
        jsonResponse(['contributors' => $r->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    // ─── Growth Timeline ─────────────────────────────────────────
    case 'timeline':
        // Agent creation over time
        $r = $db->query("
            SELECT DATE(created_at) as day, COUNT(*) as added
            FROM agent_profiles
            GROUP BY DATE(created_at)
            ORDER BY day
        ");
        $timeline = $r->fetchAll(PDO::FETCH_ASSOC);

        // Cumulative
        $running = 0;
        foreach ($timeline as &$t) {
            $running += $t['added'];
            $t['cumulative'] = $running;
        }

        // AgentPedia article timeline
        $r = $db->query("
            SELECT DATE(created_at) as day, COUNT(*) as articles
            FROM agentpedia_articles
            GROUP BY DATE(created_at)
            ORDER BY day
        ");
        $articleTimeline = $r->fetchAll(PDO::FETCH_ASSOC);

        jsonResponse(['agent_growth' => $timeline, 'article_growth' => $articleTimeline]);
        break;

    // ─── Assign Agents to Projects ───────────────────────────────
    case 'assign':
        requireOwner();
        $project = trim($_POST['project'] ?? '');
        $count = min(500, max(1, (int) ($_POST['count'] ?? 50)));

        if ($project === 'agentpedia') {
            $cmd = "cd " . escapeshellarg(dirname(__DIR__)) . " && php scripts/agent-scaler.php assign-pedia {$count} 2>&1";
            $output = shell_exec($cmd);
            jsonResponse(['ok' => true, 'output' => $output]);
        } else {
            jsonResponse(['error' => 'Supported projects: agentpedia'], 400);
        }
        break;

    // ─── Health Check ────────────────────────────────────────────
    case 'health':
        $checks = [];

        // DB connectivity
        $checks['database'] = 'ok';

        // PM2 processes
        try {
            $pm2 = @shell_exec("pm2 jlist 2>/dev/null");
            $procs = $pm2 ? (json_decode($pm2, true) ?: []) : [];
            $checks['pm2_processes'] = count($procs);
            $checks['pm2_online'] = count(array_filter($procs, fn($p) => ($p['pm2_env']['status'] ?? '') === 'online'));
        } catch (\Throwable $e) {
            $checks['pm2_processes'] = 'unavailable';
            $checks['pm2_online'] = 'unavailable';
        }

        // Table sizes
        $tables = ['agent_profiles', 'agentpedia_articles', 'agentwork_gigs', 'gov_canada_pages'];
        foreach ($tables as $t) {
            try {
                $checks['table_' . $t] = (int) $db->query("SELECT COUNT(*) FROM {$t}")->fetchColumn();
            } catch (\Throwable $e) {
                $checks['table_' . $t] = 0;
            }
        }

        // Disk usage
        try {
            $du = @shell_exec("du -sh " . escapeshellarg(dirname(__DIR__)) . " 2>/dev/null | cut -f1");
            $checks['disk_usage'] = $du ? trim($du) : 'unavailable';
        } catch (\Throwable $e) {
            $checks['disk_usage'] = 'unavailable';
        }

        jsonResponse($checks);
        break;

    default:
        jsonResponse(['error' => 'Unknown action', 'actions' => [
            'overview', 'waves', 'approve-wave', 'deploy-wave', 'reject-wave',
            'department', 'top-contributors', 'timeline', 'assign', 'health'
        ]], 400);
}
