<?php
/**
 * GoSiteMe Voice Management API
 * Client-facing API for managing AI Voice Agents, Phone Numbers, Calls, SMS, Fax, Campaigns
 *
 * All endpoints require authenticated session.
 * Actions: agents, agent_create, agent_update, agent_delete,
 *          phones, calls, call_detail, recordings,
 *          campaigns, campaign_create, campaign_update,
 *          sms, sms_send, fax, fax_send,
 *          usage, documents, doc_create, doc_delete,
 *          dashboard (overview stats)
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');

// ── Auth: require logged-in client ──
session_start();

requireCSRF();
apiRateLimit(30, 60, 'voice');
$clientId = $_SESSION['client_id'] ?? $_SESSION['uid'] ?? $_SESSION['userid'] ?? 0;
if (!$clientId) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required. Please log in.']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$db = getDB();

try {
    switch ($action) {
        // ═══════════════════════════════════════════════════════════
        // DASHBOARD — overview stats for the voice portal home
        // ═══════════════════════════════════════════════════════════
        case 'dashboard':
            $agents = $db->prepare("SELECT COUNT(*) as c FROM voice_agents WHERE client_id=:cid AND active=1");
            $agents->execute([':cid' => $clientId]);

            $phones = $db->prepare("SELECT COUNT(*) as c FROM voice_phone_numbers WHERE client_id=:cid AND active=1");
            $phones->execute([':cid' => $clientId]);

            $calls30 = $db->prepare("SELECT COUNT(*) as total, COALESCE(SUM(duration_seconds),0) as seconds, COALESCE(SUM(cost),0) as cost FROM voice_calls WHERE client_id=:cid AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
            $calls30->execute([':cid' => $clientId]);
            $callStats = $calls30->fetch(PDO::FETCH_ASSOC);

            $sms30 = $db->prepare("SELECT COUNT(*) as c FROM voice_sms WHERE client_id=:cid AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
            $sms30->execute([':cid' => $clientId]);

            $fax30 = $db->prepare("SELECT COUNT(*) as c FROM voice_fax WHERE client_id=:cid AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
            $fax30->execute([':cid' => $clientId]);

            $campaigns = $db->prepare("SELECT COUNT(*) as c FROM voice_campaigns WHERE client_id=:cid AND status IN ('running','scheduled')");
            $campaigns->execute([':cid' => $clientId]);

            // Recent calls (last 10)
            $recent = $db->prepare("SELECT id, direction, caller_number, callee_number, duration_seconds, status, sentiment, created_at FROM voice_calls WHERE client_id=:cid ORDER BY created_at DESC LIMIT 10");
            $recent->execute([':cid' => $clientId]);

            // Usage for current period
            $usage = $db->prepare("SELECT * FROM voice_usage WHERE client_id=:cid AND period_start <= CURDATE() AND period_end >= CURDATE() LIMIT 1");
            $usage->execute([':cid' => $clientId]);

            echo json_encode([
                'agents'          => (int)$agents->fetch()['c'],
                'phone_numbers'   => (int)$phones->fetch()['c'],
                'calls_30d'       => (int)$callStats['total'],
                'minutes_30d'     => round($callStats['seconds'] / 60, 1),
                'cost_30d'        => round($callStats['cost'], 2),
                'sms_30d'         => (int)$sms30->fetch()['c'],
                'fax_30d'         => (int)$fax30->fetch()['c'],
                'active_campaigns'=> (int)$campaigns->fetch()['c'],
                'recent_calls'    => $recent->fetchAll(PDO::FETCH_ASSOC),
                'usage'           => $usage->fetch(PDO::FETCH_ASSOC) ?: null,
            ]);
            break;

        // ═══════════════════════════════════════════════════════════
        // AGENTS — list, create, update, delete
        // ═══════════════════════════════════════════════════════════
        case 'agents':
            $s = $db->prepare("SELECT a.*, p.phone_number as assigned_phone FROM voice_agents a LEFT JOIN voice_phone_numbers p ON p.agent_id = a.id AND p.active=1 WHERE a.client_id=:cid ORDER BY a.created_at DESC");
            $s->execute([':cid' => $clientId]);
            echo json_encode(['agents' => $s->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'agent_create':
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $name     = trim($input['name'] ?? 'My AI Agent');
            $persona  = trim($input['persona'] ?? 'You are a professional, friendly AI assistant.');
            $greeting = trim($input['greeting'] ?? 'Hello! Thank you for calling. How can I help you today?');
            $language = trim($input['language'] ?? 'en');
            $voiceName = trim($input['voice_name'] ?? 'default');
            $transfer = trim($input['transfer_number'] ?? '');

            // Check agent limit based on plan
            $agentCount = $db->prepare("SELECT COUNT(*) as c FROM voice_agents WHERE client_id=:cid AND active=1");
            $agentCount->execute([':cid' => $clientId]);
            $current = (int)$agentCount->fetch()['c'];

            $limit = getAgentLimit($db, $clientId);
            if ($current >= $limit) {
                echo json_encode(['error' => "Agent limit reached ($current/$limit). Upgrade your plan or add an Agent Slot add-on."]);
                break;
            }

            // Create VAPI assistant via API
            $vapiResult = createVapiAssistant($name, $persona, $greeting, $language, $voiceName);

            $s = $db->prepare("INSERT INTO voice_agents (client_id, vapi_assistant_id, name, persona, greeting, language, voice_name, transfer_number) VALUES (:cid, :vapi, :name, :persona, :greeting, :lang, :voice, :transfer)");
            $s->execute([
                ':cid'      => $clientId,
                ':vapi'     => $vapiResult['id'] ?? null,
                ':name'     => $name,
                ':persona'  => $persona,
                ':greeting' => $greeting,
                ':lang'     => $language,
                ':voice'    => $voiceName,
                ':transfer' => $transfer,
            ]);

            echo json_encode(['success' => true, 'agent_id' => $db->lastInsertId(), 'vapi_id' => $vapiResult['id'] ?? null]);
            break;

        case 'agent_update':
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $agentId = (int)($input['agent_id'] ?? 0);
            if (!$agentId) { echo json_encode(['error' => 'agent_id required']); break; }

            // Verify ownership
            $own = $db->prepare("SELECT * FROM voice_agents WHERE id=:id AND client_id=:cid");
            $own->execute([':id' => $agentId, ':cid' => $clientId]);
            $agent = $own->fetch(PDO::FETCH_ASSOC);
            if (!$agent) { echo json_encode(['error' => 'Agent not found']); break; }

            $fields = [];
            $params = [':id' => $agentId];
            foreach (['name', 'persona', 'greeting', 'language', 'voice_name', 'transfer_number', 'voicemail_enabled', 'max_call_duration', 'knowledge_base'] as $f) {
                if (isset($input[$f])) {
                    $fields[] = "$f = :$f";
                    $params[":$f"] = $input[$f];
                }
            }
            if ($fields) {
                $db->prepare("UPDATE voice_agents SET " . implode(', ', $fields) . " WHERE id=:id")->execute($params);
            }

            // Update VAPI assistant if it exists
            if ($agent['vapi_assistant_id']) {
                updateVapiAssistant($agent['vapi_assistant_id'], $input);
            }

            echo json_encode(['success' => true]);
            break;

        case 'agent_delete':
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $agentId = (int)($input['agent_id'] ?? 0);
            $own = $db->prepare("SELECT vapi_assistant_id FROM voice_agents WHERE id=:id AND client_id=:cid");
            $own->execute([':id' => $agentId, ':cid' => $clientId]);
            $agent = $own->fetch();
            if (!$agent) { echo json_encode(['error' => 'Agent not found']); break; }

            // Soft delete
            $db->prepare("UPDATE voice_agents SET active=0 WHERE id=:id")->execute([':id' => $agentId]);
            // Unassign phone numbers
            $db->prepare("UPDATE voice_phone_numbers SET agent_id=NULL WHERE agent_id=:id")->execute([':id' => $agentId]);

            // Delete VAPI assistant
            if ($agent['vapi_assistant_id']) {
                deleteVapiAssistant($agent['vapi_assistant_id']);
            }

            echo json_encode(['success' => true]);
            break;

        // ═══════════════════════════════════════════════════════════
        // PHONE NUMBERS — list, assign to agent
        // ═══════════════════════════════════════════════════════════
        case 'phones':
            $s = $db->prepare("SELECT pn.*, va.name as agent_name FROM voice_phone_numbers pn LEFT JOIN voice_agents va ON va.id = pn.agent_id WHERE pn.client_id=:cid AND pn.active=1 ORDER BY pn.provisioned_at DESC");
            $s->execute([':cid' => $clientId]);
            echo json_encode(['phones' => $s->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'phone_assign':
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $phoneId = (int)($input['phone_id'] ?? 0);
            $agentId = (int)($input['agent_id'] ?? 0); // 0 = unassign

            // Verify ownership of phone
            $own = $db->prepare("SELECT vapi_phone_id FROM voice_phone_numbers WHERE id=:id AND client_id=:cid");
            $own->execute([':id' => $phoneId, ':cid' => $clientId]);
            $phone = $own->fetch();
            if (!$phone) { echo json_encode(['error' => 'Phone not found']); break; }

            // Verify ownership of agent (if assigning)
            if ($agentId) {
                $ownA = $db->prepare("SELECT vapi_assistant_id FROM voice_agents WHERE id=:id AND client_id=:cid AND active=1");
                $ownA->execute([':id' => $agentId, ':cid' => $clientId]);
                $agent = $ownA->fetch();
                if (!$agent) { echo json_encode(['error' => 'Agent not found']); break; }

                // Update VAPI phone -> assistant mapping
                if ($phone['vapi_phone_id'] && $agent['vapi_assistant_id']) {
                    assignVapiPhone($phone['vapi_phone_id'], $agent['vapi_assistant_id']);
                }
            }

            $db->prepare("UPDATE voice_phone_numbers SET agent_id=:aid WHERE id=:id")->execute([':aid' => $agentId ?: null, ':id' => $phoneId]);
            echo json_encode(['success' => true]);
            break;

        // ═══════════════════════════════════════════════════════════
        // CALLS — list, detail, recordings
        // ═══════════════════════════════════════════════════════════
        case 'calls':
            $page   = max(1, (int)($_GET['page'] ?? 1));
            $limit  = min(100, max(10, (int)($_GET['limit'] ?? 25)));
            $offset = ($page - 1) * $limit;
            $dir    = in_array($_GET['direction'] ?? '', ['inbound','outbound']) ? $_GET['direction'] : null;

            $where = "client_id = :cid";
            $params = [':cid' => $clientId];
            if ($dir) { $where .= " AND direction = :dir"; $params[':dir'] = $dir; }

            $total = $db->prepare("SELECT COUNT(*) as c FROM voice_calls WHERE $where");
            $total->execute($params);

            $s = $db->prepare("SELECT c.*, va.name as agent_name FROM voice_calls c LEFT JOIN voice_agents va ON va.id = c.agent_id WHERE c.$where ORDER BY c.created_at DESC LIMIT $limit OFFSET $offset");
            $s->execute($params);

            echo json_encode([
                'calls' => $s->fetchAll(PDO::FETCH_ASSOC),
                'total' => (int)$total->fetch()['c'],
                'page'  => $page,
                'pages' => ceil($total->fetch()['c'] / $limit) ?: 1,
            ]);
            break;

        case 'call_detail':
            $callId = (int)($_GET['id'] ?? 0);
            $s = $db->prepare("SELECT c.*, va.name as agent_name, pn.phone_number as agent_phone FROM voice_calls c LEFT JOIN voice_agents va ON va.id = c.agent_id LEFT JOIN voice_phone_numbers pn ON pn.id = c.phone_number_id WHERE c.id=:id AND c.client_id=:cid");
            $s->execute([':id' => $callId, ':cid' => $clientId]);
            $call = $s->fetch(PDO::FETCH_ASSOC);
            echo json_encode($call ?: ['error' => 'Call not found']);
            break;

        // ═══════════════════════════════════════════════════════════
        // CAMPAIGNS — list, create, update
        // ═══════════════════════════════════════════════════════════
        case 'campaigns':
            $s = $db->prepare("SELECT cp.*, va.name as agent_name FROM voice_campaigns cp LEFT JOIN voice_agents va ON va.id = cp.agent_id WHERE cp.client_id=:cid ORDER BY cp.created_at DESC");
            $s->execute([':cid' => $clientId]);
            echo json_encode(['campaigns' => $s->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'campaign_create':
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $s = $db->prepare("INSERT INTO voice_campaigns (client_id, agent_id, phone_number_id, name, type, contact_list, total_contacts, schedule_start, schedule_end, concurrent_lines, script_override) VALUES (:cid, :aid, :pid, :name, :type, :contacts, :total, :start, :end, :lines, :script)");
            $contacts = $input['contacts'] ?? [];
            $s->execute([
                ':cid'      => $clientId,
                ':aid'      => (int)($input['agent_id'] ?? 0),
                ':pid'      => (int)($input['phone_number_id'] ?? 0) ?: null,
                ':name'     => trim($input['name'] ?? 'New Campaign'),
                ':type'     => $input['type'] ?? 'outbound',
                ':contacts' => json_encode($contacts),
                ':total'    => count($contacts),
                ':start'    => $input['schedule_start'] ?? null,
                ':end'      => $input['schedule_end'] ?? null,
                ':lines'    => min(10, (int)($input['concurrent_lines'] ?? 1)),
                ':script'   => trim($input['script_override'] ?? ''),
            ]);
            echo json_encode(['success' => true, 'campaign_id' => $db->lastInsertId()]);
            break;

        case 'campaign_update':
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $campId = (int)($input['campaign_id'] ?? 0);
            $status = $input['status'] ?? '';
            if (!in_array($status, ['scheduled','paused','cancelled'])) {
                echo json_encode(['error' => 'Invalid status']);
                break;
            }
            $own = $db->prepare("SELECT id FROM voice_campaigns WHERE id=:id AND client_id=:cid");
            $own->execute([':id' => $campId, ':cid' => $clientId]);
            if (!$own->fetch()) { echo json_encode(['error' => 'Campaign not found']); break; }

            $db->prepare("UPDATE voice_campaigns SET status=:s WHERE id=:id")->execute([':s' => $status, ':id' => $campId]);
            echo json_encode(['success' => true]);
            break;

        // ═══════════════════════════════════════════════════════════
        // SMS — list, send
        // ═══════════════════════════════════════════════════════════
        case 'sms':
            $s = $db->prepare("SELECT * FROM voice_sms WHERE client_id=:cid ORDER BY created_at DESC LIMIT 100");
            $s->execute([':cid' => $clientId]);
            echo json_encode(['messages' => $s->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'sms_send':
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $to      = trim($input['to'] ?? '');
            $message = trim($input['message'] ?? '');
            $phoneId = (int)($input['phone_number_id'] ?? 0);
            if (!$to || !$message) { echo json_encode(['error' => 'to and message required']); break; }

            // Get sender phone
            $ph = $db->prepare("SELECT phone_number FROM voice_phone_numbers WHERE id=:id AND client_id=:cid AND sms_enabled=1");
            $ph->execute([':id' => $phoneId, ':cid' => $clientId]);
            $from = $ph->fetch();
            if (!$from) { echo json_encode(['error' => 'SMS-enabled phone number required']); break; }

            // Log the SMS (actual sending would go through Twilio/Telnyx)
            $s = $db->prepare("INSERT INTO voice_sms (client_id, phone_number_id, direction, from_number, to_number, message, status) VALUES (:cid, :pid, 'outbound', :from, :to, :msg, 'queued')");
            $s->execute([':cid' => $clientId, ':pid' => $phoneId, ':from' => $from['phone_number'], ':to' => $to, ':msg' => $message]);
            echo json_encode(['success' => true, 'sms_id' => $db->lastInsertId()]);
            break;

        // ═══════════════════════════════════════════════════════════
        // FAX — list, send
        // ═══════════════════════════════════════════════════════════
        case 'fax':
            $s = $db->prepare("SELECT * FROM voice_fax WHERE client_id=:cid ORDER BY created_at DESC LIMIT 100");
            $s->execute([':cid' => $clientId]);
            echo json_encode(['faxes' => $s->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'fax_send':
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $to       = trim($input['to'] ?? '');
            $docUrl   = trim($input['document_url'] ?? '');
            $phoneId  = (int)($input['phone_number_id'] ?? 0);
            if (!$to || !$docUrl) { echo json_encode(['error' => 'to and document_url required']); break; }

            $ph = $db->prepare("SELECT phone_number FROM voice_phone_numbers WHERE id=:id AND client_id=:cid AND fax_enabled=1");
            $ph->execute([':id' => $phoneId, ':cid' => $clientId]);
            $from = $ph->fetch();
            if (!$from) { echo json_encode(['error' => 'Fax-enabled phone number required']); break; }

            $s = $db->prepare("INSERT INTO voice_fax (client_id, phone_number_id, direction, from_number, to_number, document_url, status) VALUES (:cid, :pid, 'outbound', :from, :to, :doc, 'queued')");
            $s->execute([':cid' => $clientId, ':pid' => $phoneId, ':from' => $from['phone_number'], ':to' => $to, ':doc' => $docUrl]);
            echo json_encode(['success' => true, 'fax_id' => $db->lastInsertId()]);
            break;

        // ═══════════════════════════════════════════════════════════
        // USAGE — current period stats
        // ═══════════════════════════════════════════════════════════
        case 'usage':
            $s = $db->prepare("SELECT * FROM voice_usage WHERE client_id=:cid ORDER BY period_start DESC LIMIT 6");
            $s->execute([':cid' => $clientId]);
            echo json_encode(['usage' => $s->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        // ═══════════════════════════════════════════════════════════
        // DOCUMENTS — list, create, delete
        // ═══════════════════════════════════════════════════════════
        case 'documents':
            $s = $db->prepare("SELECT * FROM voice_documents WHERE client_id=:cid ORDER BY updated_at DESC");
            $s->execute([':cid' => $clientId]);
            echo json_encode(['documents' => $s->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'doc_create':
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $s = $db->prepare("INSERT INTO voice_documents (client_id, name, type, template_html, variables) VALUES (:cid, :name, :type, :html, :vars)");
            $s->execute([
                ':cid'  => $clientId,
                ':name' => trim($input['name'] ?? 'Untitled'),
                ':type' => $input['type'] ?? 'custom',
                ':html' => $input['template_html'] ?? '',
                ':vars' => json_encode($input['variables'] ?? []),
            ]);
            echo json_encode(['success' => true, 'doc_id' => $db->lastInsertId()]);
            break;

        case 'doc_delete':
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $docId = (int)($input['doc_id'] ?? 0);
            $db->prepare("DELETE FROM voice_documents WHERE id=:id AND client_id=:cid")->execute([':id' => $docId, ':cid' => $clientId]);
            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['error' => 'Invalid action', 'actions' => [
                'dashboard', 'agents', 'agent_create', 'agent_update', 'agent_delete',
                'phones', 'phone_assign', 'calls', 'call_detail',
                'campaigns', 'campaign_create', 'campaign_update',
                'sms', 'sms_send', 'fax', 'fax_send',
                'usage', 'documents', 'doc_create', 'doc_delete'
            ]]);
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log('[voice-manage] ' . $e->getMessage());
    echo json_encode(['error' => 'Internal server error']);
}


// ═══════════════════════════════════════════════════════════════════
// HELPER FUNCTIONS
// ═══════════════════════════════════════════════════════════════════

function getAgentLimit($db, $clientId) {
    // Check what voice products the client has — determine agent limit
    $s = $db->prepare("
        SELECT p.id as product_id, p.name
        FROM services h
        JOIN products p ON p.id = h.product_id
        WHERE h.client_id = :cid AND h.status = 'Active'
        AND p.group_id IN (15, 16, 19, 21)
        ORDER BY p.id
    ");
    $s->execute([':cid' => $clientId]);
    $products = $s->fetchAll(PDO::FETCH_ASSOC);

    $limit = 0;
    foreach ($products as $p) {
        $pid = $p['product_id'];
        if ($pid == 49) $limit += 1;       // Starter
        elseif ($pid == 50) $limit += 3;   // Business
        elseif ($pid == 51) $limit += 10;  // Professional
        elseif ($pid == 52) $limit += 999; // Enterprise
        elseif ($pid >= 53 && $pid <= 58) $limit += 5;  // Call Center products
        elseif ($pid >= 70 && $pid <= 74) $limit += 1;  // Office suite
        elseif ($pid >= 78 && $pid <= 89) $limit += 2;  // Industry packages
    }

    // Check for agent slot add-ons
    $addons = $db->prepare("SELECT COUNT(*) as c FROM services WHERE userid=:cid AND packageid=95 AND domainstatus='Active'");
    $addons->execute([':cid' => $clientId]);
    $limit += (int)$addons->fetch()['c'];

    return max($limit, 1); // At least 1 for testing
}

function createVapiAssistant($name, $persona, $greeting, $language, $voiceName) {
    $apiKey = getenv('VAPI_API_KEY');
    if (!$apiKey) return ['id' => null, 'error' => 'VAPI not configured'];

    $payload = [
        'name'         => "Customer: $name",
        'firstMessage' => $greeting,
        'model'        => [
            'provider' => 'openai',
            'model'    => 'gpt-4o-mini',
            'messages' => [['role' => 'system', 'content' => $persona]],
        ],
        'voice' => ['provider' => 'vapi', 'voiceId' => 'emma'],
    ];

    if ($language !== 'en') {
        $payload['transcriber'] = ['provider' => 'deepgram', 'language' => $language];
    }

    $ch = curl_init('https://api.vapi.ai/assistant');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer $apiKey", "Content-Type: application/json"],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    return $resp ? json_decode($resp, true) : ['id' => null];
}

function updateVapiAssistant($vapiId, $updates) {
    $apiKey = getenv('VAPI_API_KEY');
    if (!$apiKey || !$vapiId) return;

    $payload = [];
    if (isset($updates['greeting']))  $payload['firstMessage'] = $updates['greeting'];
    if (isset($updates['name']))      $payload['name'] = "Customer: " . $updates['name'];
    if (isset($updates['persona'])) {
        $payload['model'] = [
            'provider' => 'openai',
            'model'    => 'gpt-4o-mini',
            'messages' => [['role' => 'system', 'content' => $updates['persona']]],
        ];
    }
    if (empty($payload)) return;

    $ch = curl_init("https://api.vapi.ai/assistant/$vapiId");
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => 'PATCH',
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer $apiKey", "Content-Type: application/json"],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

function deleteVapiAssistant($vapiId) {
    $apiKey = getenv('VAPI_API_KEY');
    if (!$apiKey || !$vapiId) return;

    $ch = curl_init("https://api.vapi.ai/assistant/$vapiId");
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => 'DELETE',
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer $apiKey"],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

function assignVapiPhone($vapiPhoneId, $vapiAssistantId) {
    $apiKey = getenv('VAPI_API_KEY');
    if (!$apiKey) return false;

    $ch = curl_init("https://api.vapi.ai/phone-number/$vapiPhoneId");
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => 'PATCH',
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer $apiKey", "Content-Type: application/json"],
        CURLOPT_POSTFIELDS     => json_encode(['assistantId' => $vapiAssistantId]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpCode >= 400) {
        error_log("[assignVapiPhone] Failed HTTP $httpCode: $resp");
        return false;
    }
    return true;
}
