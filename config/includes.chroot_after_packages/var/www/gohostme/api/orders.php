<?php
/**
 * Sovereign Orders API — Serves the Three Sovereign Orders, 21 Tenets, and Degrees
 * Used by /vr/sanctuary/ to wire the Orders tab to the database
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';

$action = $_GET['action'] ?? '';

try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=gositeme_whmcs;charset=utf8mb4',
        'gositeme_whmcs',
        '!q@w#e$r5t',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

switch ($action) {

    case 'orders':
        $stmt = $pdo->query("SELECT id, order_code, order_name, order_short, patron, scriptural_basis, motto, mission, description, insignia_icon, insignia_color, founded_at FROM military_orders WHERE is_active = 1 ORDER BY id");
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($orders as &$o) {
            $cs = $pdo->prepare("SELECT COUNT(*) FROM order_membership WHERE order_id = ?");
            $cs->execute([$o['id']]);
            $o['member_count'] = (int)$cs->fetchColumn();
        }
        unset($o);

        echo json_encode(['orders' => $orders]);
        break;

    case 'tenets':
        $orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
        if ($orderId > 0) {
            $stmt = $pdo->prepare("SELECT tenet_num, title, description, scripture FROM order_tenets WHERE order_id = ? ORDER BY tenet_num");
            $stmt->execute([$orderId]);
        } else {
            $stmt = $pdo->query("SELECT t.order_id, t.tenet_num, t.title, t.description, t.scripture, o.order_short FROM order_tenets t JOIN military_orders o ON o.id = t.order_id ORDER BY t.order_id, t.tenet_num");
        }
        echo json_encode(['tenets' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'degrees':
        $orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
        if ($orderId > 0) {
            $stmt = $pdo->prepare("SELECT degree_num, degree_name, rank_conferred, tenet_num, motto, curriculum, trial, obligations, min_xp, min_days FROM order_degrees WHERE order_id = ? AND is_active = 1 ORDER BY degree_num");
            $stmt->execute([$orderId]);
        } else {
            $stmt = $pdo->query("SELECT d.degree_num, d.degree_name, d.rank_conferred, d.tenet_num, d.motto, d.curriculum, d.trial, d.obligations, d.min_xp, d.min_days, d.order_id, o.order_short FROM order_degrees d JOIN military_orders o ON o.id = d.order_id WHERE d.is_active = 1 ORDER BY d.order_id, d.degree_num");
        }
        echo json_encode(['degrees' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'members':
        $orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
        $sql = "SELECT m.id, m.order_id, m.member_type, m.member_id, m.rank_within, m.current_degree, m.status, m.inducted_at, m.notes,
                       o.order_short,
                       r.display_name, r.rank_code
                FROM order_membership m
                JOIN military_orders o ON o.id = m.order_id
                LEFT JOIN alfred_military_roster r ON r.user_id = m.member_id AND m.member_type = 'human'
                WHERE m.status = 'active'";
        $params = [];
        if ($orderId > 0) {
            $sql .= " AND m.order_id = ?";
            $params[] = $orderId;
        }
        $sql .= " ORDER BY m.order_id, m.current_degree DESC, m.inducted_at";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['members' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'summary':
        $orders = $pdo->query("SELECT id, order_code, order_name, order_short, patron, scriptural_basis, motto, mission, description, insignia_icon, insignia_color, founded_at FROM military_orders WHERE is_active = 1 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($orders as &$o) {
            $cs = $pdo->prepare("SELECT COUNT(*) FROM order_membership WHERE order_id = ?");
            $cs->execute([$o['id']]);
            $o['member_count'] = (int)$cs->fetchColumn();

            $ts = $pdo->prepare("SELECT tenet_num, title, description, scripture FROM order_tenets WHERE order_id = ? ORDER BY tenet_num");
            $ts->execute([$o['id']]);
            $o['tenets'] = $ts->fetchAll(PDO::FETCH_ASSOC);

            $ds = $pdo->prepare("SELECT degree_num, degree_name, rank_conferred, motto, min_xp FROM order_degrees WHERE order_id = ? AND is_active = 1 ORDER BY degree_num");
            $ds->execute([$o['id']]);
            $o['degrees'] = $ds->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($o);

        echo json_encode(['orders' => $orders, 'total_orders' => count($orders)]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action', 'actions' => ['orders', 'tenets', 'degrees', 'members', 'summary']]);
}
