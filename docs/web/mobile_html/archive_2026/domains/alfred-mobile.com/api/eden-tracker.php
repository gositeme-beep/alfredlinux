<?php
/**
 * EDEN TRACKER API — Family & Succession Management
 * ═══════════════════════════════════════════════════
 * CRUD API for the eden_family table.
 * Commander-only (client_id === 33).
 */
require_once __DIR__ . '/../includes/db-config.inc.php';
require_once __DIR__ . '/../includes/auth-gate.inc.php';
header('Content-Type: application/json');

if ((int)($clientId ?? 0) !== 33) {
    http_response_code(403);
    echo json_encode(['error' => 'Commander access only']);
    exit;
}

$db = getSharedDB();
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

switch ($action) {

case 'list':
    $rows = $db->query("SELECT * FROM eden_family ORDER BY FIELD(role, 'commander', 'successor', 'family'), full_name")->fetchAll(PDO::FETCH_ASSOC);
    // Decode JSON fields
    foreach ($rows as &$r) {
        $r['traits'] = $r['traits'] ? json_decode($r['traits'], true) : [];
        $r['milestones'] = $r['milestones'] ? json_decode($r['milestones'], true) : [];
    }
    echo json_encode(['ok' => true, 'family' => $rows]);
    break;

case 'update':
    $id = (int)($_POST['id'] ?? 0);
    if ($id < 1) { echo json_encode(['error' => 'Invalid ID']); exit; }

    $allowed = ['full_name','role','relationship','date_of_birth','notes','traits','milestones','emergency_contact','photo_url','status'];
    $sets = [];
    $vals = [];
    foreach ($allowed as $col) {
        if (isset($_POST[$col])) {
            $sets[] = "$col = ?";
            $vals[] = $_POST[$col];
        }
    }
    if (empty($sets)) { echo json_encode(['error' => 'Nothing to update']); exit; }
    $vals[] = $id;
    $db->prepare("UPDATE eden_family SET " . implode(', ', $sets) . " WHERE id = ?")->execute($vals);
    echo json_encode(['ok' => true]);
    break;

case 'add_milestone':
    $id = (int)($_POST['id'] ?? 0);
    $milestone = trim($_POST['milestone'] ?? '');
    $date = trim($_POST['date'] ?? date('Y-m-d'));
    if ($id < 1 || !$milestone) { echo json_encode(['error' => 'Missing data']); exit; }

    $row = $db->prepare("SELECT milestones FROM eden_family WHERE id = ?");
    $row->execute([$id]);
    $current = $row->fetchColumn();
    $ms = $current ? json_decode($current, true) : [];
    $ms[] = ['text' => $milestone, 'date' => $date, 'added' => date('c')];

    $db->prepare("UPDATE eden_family SET milestones = ? WHERE id = ?")->execute([json_encode($ms), $id]);
    echo json_encode(['ok' => true, 'milestones' => $ms]);
    break;

case 'add_trait':
    $id = (int)($_POST['id'] ?? 0);
    $trait = trim($_POST['trait'] ?? '');
    if ($id < 1 || !$trait) { echo json_encode(['error' => 'Missing data']); exit; }

    $row = $db->prepare("SELECT traits FROM eden_family WHERE id = ?");
    $row->execute([$id]);
    $current = $row->fetchColumn();
    $traits = $current ? json_decode($current, true) : [];
    $traits[] = $trait;

    $db->prepare("UPDATE eden_family SET traits = ? WHERE id = ?")->execute([json_encode(array_unique($traits)), $id]);
    echo json_encode(['ok' => true, 'traits' => array_unique($traits)]);
    break;

case 'add':
    $key = trim($_POST['person_key'] ?? '');
    $name = trim($_POST['full_name'] ?? '');
    if (!$key || !$name) { echo json_encode(['error' => 'Name and key required']); exit; }

    $stmt = $db->prepare("INSERT INTO eden_family (person_key, full_name, role, relationship, date_of_birth, notes) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $key, $name,
        $_POST['role'] ?? 'family',
        $_POST['relationship'] ?? null,
        $_POST['date_of_birth'] ?? null,
        $_POST['notes'] ?? null
    ]);
    echo json_encode(['ok' => true, 'id' => $db->lastInsertId()]);
    break;

default:
    echo json_encode(['error' => 'Unknown action']);
}
