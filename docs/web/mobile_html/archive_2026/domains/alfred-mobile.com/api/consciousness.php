<?php
/**
 * Alfred Consciousness API — Chat ↔ Persistent State Bridge
 * ═══════════════════════════════════════════════════════════
 * 
 * Endpoints:
 *   GET  ?action=briefing         — Get current briefing for chat session start
 *   GET  ?action=journal          — Read unread journal entries
 *   GET  ?action=status           — Get consciousness state + health
 *   POST ?action=handoff          — Write chat-end handoff (context + instructions)
 *   POST ?action=directive        — Queue a single directive for autonomous execution
 *   POST ?action=standing-order   — Create a recurring autonomous task
 *   POST ?action=journal-read     — Mark journal entries as read
 * 
 * Auth: Commander only (client_id = 33) or internal secret
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/scripts/vault-crypto.php';

session_start();
header('Content-Type: application/json');

// Auth check — Commander or internal
$internalSecret = getenv('INTERNAL_SECRET') ?: '';
$isInternal = $internalSecret && isset($_SERVER['HTTP_X_INTERNAL_SECRET']) 
    && hash_equals($internalSecret, $_SERVER['HTTP_X_INTERNAL_SECRET']);
$isCommander = !empty($_SESSION['logged_in']) && (($_SESSION['client_id'] ?? 0) === 33);

if (!$isInternal && !$isCommander) {
    http_response_code(403);
    echo json_encode(['error' => 'Commander access required']);
    exit;
}

$db = getDB();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

function uuidV4(): string {
    $d = random_bytes(16);
    $d[6] = chr(ord($d[6]) & 0x0f | 0x40);
    $d[8] = chr(ord($d[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($d), 4));
}

switch ($action) {

    // ═══════════════════════════════════════════
    // GET BRIEFING — What happened since last chat
    // ═══════════════════════════════════════════
    case 'briefing':
        $consciousness = $db->query("SELECT * FROM alfred_consciousness WHERE user_id = 33")->fetch(PDO::FETCH_ASSOC);
        if ($consciousness) { $consciousness = vault_decrypt_row($consciousness, vault_sensitive_fields('alfred_consciousness')); }
        
        // Unread journal entries (critical first)
        $journal = $db->query("SELECT entry_id, category, title, content, importance, created_at 
            FROM alfred_consciousness_journal 
            WHERE read_by_chat = 0 
            ORDER BY importance DESC, created_at DESC 
            LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($journal as &$j) { $j = vault_decrypt_row($j, vault_sensitive_fields('alfred_consciousness_journal')); } unset($j);
        
        // Recent handoff results
        $recentHandoffs = $db->query("SELECT handoff_id, title, status, result, processed_at 
            FROM alfred_chat_handoff 
            WHERE status IN ('completed','failed') AND processed_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY processed_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($recentHandoffs as &$rh) { $rh = vault_decrypt_row($rh, vault_sensitive_fields('alfred_chat_handoff')); } unset($rh);
        
        // Activity stats
        $cycles1h = (int)$db->query("SELECT COUNT(*) FROM alfred_decisions WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)")->fetchColumn();
        $cycles24h = (int)$db->query("SELECT COUNT(*) FROM alfred_decisions WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
        
        // Pending work
        $pendingHandoffs = (int)$db->query("SELECT COUNT(*) FROM alfred_chat_handoff WHERE status = 'pending'")->fetchColumn();
        $activeDirectives = (int)$db->query("SELECT COUNT(*) FROM alfred_ops_directives WHERE status IN ('pending','in_progress','claimed')")->fetchColumn();
        
        echo json_encode([
            'success' => true,
            'consciousness' => $consciousness ? [
                'emotional_state' => $consciousness['emotional_state'],
                'mood' => $consciousness['mood'],
                'energy_level' => (int)$consciousness['energy_level'],
                'memory_context' => $consciousness['memory_context'],
                'interaction_count' => (int)$consciousness['interaction_count'],
                'last_interaction' => $consciousness['last_interaction'],
            ] : null,
            'briefing_text' => $consciousness['memory_context'] ?? 'No briefing available — consciousness not yet initialized',
            'unread_journal' => $journal,
            'recent_handoffs' => $recentHandoffs,
            'stats' => [
                'autonomy_cycles_1h' => $cycles1h,
                'autonomy_cycles_24h' => $cycles24h,
                'pending_handoffs' => $pendingHandoffs,
                'active_directives' => $activeDirectives,
            ],
        ], JSON_PRETTY_PRINT);
        break;

    // ═══════════════════════════════════════════
    // GET JOURNAL — Read journal entries
    // ═══════════════════════════════════════════
    case 'journal':
        $limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
        $category = $_GET['category'] ?? null;
        $unreadOnly = ($_GET['unread'] ?? '1') === '1';
        
        $sql = "SELECT * FROM alfred_consciousness_journal WHERE 1=1";
        $params = [];
        if ($unreadOnly) { $sql .= " AND read_by_chat = 0"; }
        if ($category && in_array($category, ['perception','action','reflection','briefing','alert','handoff_result','self_monitor'])) { 
            $sql .= " AND category = ?"; 
            $params[] = $category; 
        }
        $sql .= " ORDER BY importance DESC, created_at DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($entries as &$e) { $e = vault_decrypt_row($e, vault_sensitive_fields('alfred_consciousness_journal')); } unset($e);
        
        echo json_encode(['success' => true, 'entries' => $entries, 'count' => count($entries)], JSON_PRETTY_PRINT);
        break;

    // ═══════════════════════════════════════════
    // GET STATUS — Consciousness state + health
    // ═══════════════════════════════════════════
    case 'status':
        $consciousness = $db->query("SELECT * FROM alfred_consciousness WHERE user_id = 33")->fetch(PDO::FETCH_ASSOC);
        if ($consciousness) { $consciousness = vault_decrypt_row($consciousness, vault_sensitive_fields('alfred_consciousness')); }
        $lastDecision = $db->query("SELECT created_at FROM alfred_decisions ORDER BY created_at DESC LIMIT 1")->fetchColumn();
        $lastJournal = $db->query("SELECT created_at FROM alfred_consciousness_journal ORDER BY created_at DESC LIMIT 1")->fetchColumn();
        $pendingHandoffs = (int)$db->query("SELECT COUNT(*) FROM alfred_chat_handoff WHERE status = 'pending'")->fetchColumn();
        
        $autonomyAge = $lastDecision ? time() - strtotime($lastDecision) : null;
        $consciousnessAge = $lastJournal ? time() - strtotime($lastJournal) : null;
        
        echo json_encode([
            'success' => true,
            'alive' => ($autonomyAge !== null && $autonomyAge < 180),
            'consciousness_active' => ($consciousnessAge !== null && $consciousnessAge < 300),
            'autonomy_cron' => [
                'last_run' => $lastDecision,
                'age_seconds' => $autonomyAge,
                'status' => $autonomyAge === null ? 'no_data' : ($autonomyAge < 180 ? 'healthy' : ($autonomyAge < 600 ? 'degraded' : 'dead')),
            ],
            'consciousness_loop' => [
                'last_journal' => $lastJournal,
                'age_seconds' => $consciousnessAge,
                'status' => $consciousnessAge === null ? 'no_data' : ($consciousnessAge < 300 ? 'healthy' : ($consciousnessAge < 600 ? 'degraded' : 'dead')),
            ],
            'pending_handoffs' => $pendingHandoffs,
            'state' => $consciousness ? [
                'emotional_state' => $consciousness['emotional_state'],
                'mood' => $consciousness['mood'],
                'energy_level' => (int)$consciousness['energy_level'],
                'interaction_count' => (int)$consciousness['interaction_count'],
                'last_interaction' => $consciousness['last_interaction'],
            ] : null,
        ], JSON_PRETTY_PRINT);
        break;

    // ═══════════════════════════════════════════
    // POST HANDOFF — Write chat-end state + instructions
    // ═══════════════════════════════════════════
    case 'handoff':
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || empty($input['title'])) {
            http_response_code(400);
            echo json_encode(['error' => 'title required']);
            break;
        }
        
        $handoffId = uuidV4();
        $priority = min(10, max(1, (int)($input['priority'] ?? 5)));
        $expiresAt = !empty($input['expires_hours']) 
            ? date('Y-m-d H:i:s', time() + ((int)$input['expires_hours'] * 3600)) 
            : null;
        
        $db->prepare("INSERT INTO alfred_chat_handoff 
            (handoff_id, session_type, status, priority, title, context, instructions, source_chat, expires_at)
            VALUES (?, ?, 'pending', ?, ?, ?, ?, ?, ?)")
            ->execute([
                $handoffId,
                $input['session_type'] ?? 'chat_end',
                $priority,
                substr($input['title'], 0, 255),
                vault_encrypt(json_encode($input['context'] ?? [])),
                vault_encrypt(json_encode($input['instructions'] ?? [])),
                $input['source_chat'] ?? 'copilot-' . date('YmdHis'),
                $expiresAt,
            ]);
        
        // Also update consciousness state with latest context
        $db->prepare("UPDATE alfred_consciousness SET 
            emotional_state = ?,
            mood = ?,
            last_interaction = NOW(),
            interaction_count = interaction_count + 1
            WHERE user_id = 33")
            ->execute([
                $input['emotional_state'] ?? 'focused',
                $input['mood'] ?? 'operational',
            ]);
        
        echo json_encode([
            'success' => true,
            'handoff_id' => $handoffId,
            'status' => 'pending',
            'message' => 'Handoff queued. Consciousness loop will process within 2 minutes.',
        ], JSON_PRETTY_PRINT);
        break;

    // ═══════════════════════════════════════════
    // POST DIRECTIVE — Queue single autonomous task
    // ═══════════════════════════════════════════
    case 'directive':
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || empty($input['title'])) {
            http_response_code(400);
            echo json_encode(['error' => 'title required']);
            break;
        }
        
        $directiveId = uuidV4();
        $validTypes = ['repair','upgrade','investigate','maintain','deploy'];
        $type = in_array($input['type'] ?? '', $validTypes) ? $input['type'] : 'investigate';
        $priority = min(10, max(1, (int)($input['priority'] ?? 5)));
        
        $db->prepare("INSERT INTO alfred_ops_directives 
            (directive_id, type, title, description, priority, source, assigned_agent, input_data, tags, created_at)
            VALUES (?, ?, ?, ?, ?, 'commander', ?, ?, ?, NOW())")
            ->execute([
                $directiveId,
                $type,
                substr($input['title'], 0, 255),
                $input['description'] ?? $input['title'],
                $priority,
                $input['agent'] ?? null,
                json_encode($input['input_data'] ?? []),
                json_encode($input['tags'] ?? []),
            ]);
        
        echo json_encode([
            'success' => true,
            'directive_id' => $directiveId,
            'type' => $type,
            'priority' => $priority,
            'message' => 'Directive queued. Autonomy cron will pick it up within 60 seconds.',
        ], JSON_PRETTY_PRINT);
        break;

    // ═══════════════════════════════════════════
    // POST STANDING ORDER — Recurring autonomous task
    // ═══════════════════════════════════════════
    case 'standing-order':
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || empty($input['title']) || empty($input['schedule'])) {
            http_response_code(400);
            echo json_encode(['error' => 'title and schedule required']);
            break;
        }
        
        $orderId = uuidV4();
        $validTypes = ['repair','upgrade','investigate','maintain','deploy'];
        $type = in_array($input['type'] ?? '', $validTypes) ? $input['type'] : 'maintain';
        $priority = min(10, max(1, (int)($input['priority'] ?? 5)));
        
        $db->prepare("INSERT INTO alfred_ops_standing_orders 
            (order_id, title, description, type, schedule, priority, assigned_agent, input_data, active, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())")
            ->execute([
                $orderId,
                substr($input['title'], 0, 255),
                $input['description'] ?? $input['title'],
                $type,
                $input['schedule'],
                $priority,
                $input['agent'] ?? null,
                json_encode($input['input_data'] ?? []),
            ]);
        
        echo json_encode([
            'success' => true,
            'order_id' => $orderId,
            'schedule' => $input['schedule'],
            'message' => "Standing order created. Will execute on schedule: {$input['schedule']}",
        ], JSON_PRETTY_PRINT);
        break;

    // ═══════════════════════════════════════════
    // POST JOURNAL-READ — Mark entries as read
    // ═══════════════════════════════════════════
    case 'journal-read':
        $input = json_decode(file_get_contents('php://input'), true);
        if (!empty($input['entry_ids']) && is_array($input['entry_ids'])) {
            $placeholders = implode(',', array_fill(0, count($input['entry_ids']), '?'));
            $db->prepare("UPDATE alfred_consciousness_journal SET read_by_chat = 1 WHERE entry_id IN ({$placeholders})")
                ->execute($input['entry_ids']);
            $affected = count($input['entry_ids']);
        } else {
            // Mark all as read
            $affected = $db->exec("UPDATE alfred_consciousness_journal SET read_by_chat = 1 WHERE read_by_chat = 0");
        }
        echo json_encode(['success' => true, 'marked_read' => $affected]);
        break;

    default:
        http_response_code(400);
        echo json_encode([
            'error' => 'Unknown action',
            'available' => ['briefing','journal','status','handoff','directive','standing-order','journal-read']
        ]);
}
