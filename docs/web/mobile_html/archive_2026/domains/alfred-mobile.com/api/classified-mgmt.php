<?php
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();
$clientId = $_SESSION['client_id'] ?? 0;
if ((int)$clientId !== 33) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Commander access only']);
    exit;
}

$db = getDB();
$action = $_GET['action'] ?? $_POST['action'] ?? 'dashboard';

switch ($action) {

case 'dashboard':
    $secretCount = $db->query("SELECT COUNT(*) FROM veil_agenda WHERE tags LIKE '%secret%' OR tags LIKE '%classified%'")->fetchColumn();
    $activeMissions = $db->query("SELECT COUNT(*) FROM commander_missions WHERE status IN ('in_progress','active')")->fetchColumn();
    $classifiedMissions = $db->query("SELECT COUNT(*) FROM commander_missions WHERE category='classified'")->fetchColumn();
    $releaseReady = $db->query("SELECT COUNT(*) FROM commander_missions WHERE tags LIKE '%release-ready%'")->fetchColumn();
    $totalMissions = $db->query("SELECT COUNT(*) FROM commander_missions")->fetchColumn();

    $secrets = $db->query("SELECT id, title, description, event_date, category, priority, status, tags FROM veil_agenda WHERE tags LIKE '%secret%' OR tags LIKE '%classified%' ORDER BY priority DESC, event_date DESC")->fetchAll(PDO::FETCH_ASSOC);

    $missions = $db->query("SELECT id, category, title, description, priority, status, progress, due_date, notes, created_at, updated_at FROM commander_missions ORDER BY FIELD(status,'in_progress','active','not_started','completed') ASC, FIELD(priority,'critical','high','medium','low') ASC, created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

    $vaultPrograms = [
        ['name' => 'TITAN', 'codename' => 'Project Titan', 'status' => 'active', 'classification' => 'TOP SECRET', 'description' => 'Full-stack sovereign infrastructure'],
        ['name' => 'PROMETHEUS', 'codename' => 'Project Prometheus', 'status' => 'active', 'classification' => 'TOP SECRET', 'description' => 'Advanced AI reasoning & autonomy'],
        ['name' => 'SOVEREIGN', 'codename' => 'Project Sovereign', 'status' => 'planning', 'classification' => 'SECRET', 'description' => 'Digital nation sovereignty framework'],
    ];

    $releaseQueue = $db->query("SELECT id, title, category, status, notes FROM commander_missions WHERE tags LIKE '%release-ready%' ORDER BY updated_at DESC")->fetchAll(PDO::FETCH_ASSOC);

    $fleetPassportStats = ['total' => 0, 'fleet_size' => 0, 'coverage_pct' => 0, 'detailed_passports' => 0];
    try {
        require_once dirname(__DIR__) . '/includes/db-config.inc.php';
        $sharedDb = getSharedDB();
        $fleetPassportStats['total'] = (int)$sharedDb->query("SELECT COUNT(*) FROM fleet_passports")->fetchColumn();
        $fleetPassportStats['detailed_passports'] = (int)$sharedDb->query("SELECT COUNT(*) FROM fleet_passport_ext")->fetchColumn();
        $fmRow = $sharedDb->query("SELECT total_agents FROM fleet_metrics_cache ORDER BY updated_at DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $fleetPassportStats['fleet_size'] = $fmRow ? (int)$fmRow['total_agents'] : 51000000;
        $fleetPassportStats['coverage_pct'] = $fleetPassportStats['fleet_size'] > 0
            ? round($fleetPassportStats['total'] / $fleetPassportStats['fleet_size'] * 100, 2)
            : 0;
    } catch (PDOException $e) {
        $fleetPassportStats['error'] = $e->getMessage();
    }

    echo json_encode([
        'ok' => true,
        'stats' => [
            'secrets' => (int)$secretCount,
            'activeMissions' => (int)$activeMissions,
            'classifiedMissions' => (int)$classifiedMissions,
            'releaseReady' => (int)$releaseReady,
            'totalMissions' => (int)$totalMissions,
        ],
        'fleetPassports' => $fleetPassportStats,
        'secrets' => $secrets,
        'missions' => $missions,
        'vault' => $vaultPrograms,
        'releaseQueue' => $releaseQueue,
    ]);
    break;

case 'toggle-release':
    $id = (int)($_POST['id'] ?? 0);
    $table = $_POST['table'] ?? 'commander_missions';
    if (!$id || !in_array($table, ['commander_missions', 'veil_agenda'])) {
        echo json_encode(['ok' => false, 'error' => 'Invalid params']);
        exit;
    }

    $row = $db->prepare("SELECT tags FROM $table WHERE id = ?");
    $row->execute([$id]);
    $tags = $row->fetchColumn() ?: '';

    if (strpos($tags, 'release-ready') !== false) {
        $tags = str_replace('release-ready', '', $tags);
        $tags = trim(preg_replace('/,{2,}/', ',', trim($tags, ',')));
        $marked = false;
    } else {
        $tags = $tags ? $tags . ',release-ready' : 'release-ready';
        $marked = true;
    }

    $upd = $db->prepare("UPDATE $table SET tags = ? WHERE id = ?");
    $upd->execute([$tags, $id]);

    echo json_encode(['ok' => true, 'marked' => $marked, 'id' => $id]);
    break;

default:
    echo json_encode(['ok' => false, 'error' => 'Unknown action']);
}
