<?php
require_once dirname(__DIR__) . '/includes/api-security.php';
/**
 * Team Chat API — Multi-Agent War Room / Training Ground
 * 
 * Allows users to spawn a team of AI agents into a single chat room,
 * direct them individually or as a group, and train them like a call center manager.
 *
 * Endpoints:
 *   POST ?action=create_room   — Create a new team chat room with agents
 *   POST ?action=send          — Send a message (to all agents or specific ones)
 *   POST ?action=gather        — "Gather N agents" - auto-spawn agents from templates
 *   POST ?action=add_agent     — Add an agent to the room mid-session
 *   POST ?action=remove_agent  — Remove an agent from the room
 *   POST ?action=train         — Send training instructions to specific agents
 *   POST ?action=directive     — Issue a directive to the whole team
 *   GET  ?action=room&id=X     — Get room state with all agents and messages
 *   GET  ?action=rooms         — List user's team chat rooms
 *   POST ?action=close         — Close/archive a room
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Alfred-Token, X-CSRF-Token');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

session_start();

// ── Auth ──
$userId = $_SESSION['uid'] ?? $_SESSION['client_id'] ?? null;
$username = $_SESSION['username'] ?? $_SESSION['client_name'] ?? 'Boss';

if (!$userId) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

// ── CSRF ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $sessionToken = $_SESSION['alfred_csrf'] ?? '';
    if (!$sessionToken) {
        $_SESSION['alfred_csrf'] = bin2hex(random_bytes(32));
        $sessionToken = $_SESSION['alfred_csrf'];
    }
    if ($csrfToken !== '' && !hash_equals($sessionToken, $csrfToken)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token', 'csrf_token' => $sessionToken]);
        exit;
    }
}

// ── DB ──
define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/ws-push.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

ensureTeamChatTables($pdo);

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'create_room':  handleCreateRoom($pdo, $userId, $username); break;
    case 'send':         handleSend($pdo, $userId, $username); break;
    case 'gather':       handleGather($pdo, $userId, $username); break;
    case 'add_agent':       handleAddAgent($pdo, $userId); break;
    case 'remove_agent':    handleRemoveAgent($pdo, $userId); break;
    case 'train':           handleTrain($pdo, $userId, $username); break;
    case 'directive':       handleDirective($pdo, $userId, $username); break;
    case 'room':            handleGetRoom($pdo, $userId); break;
    case 'rooms':           handleListRooms($pdo, $userId); break;
    case 'close':           handleCloseRoom($pdo, $userId); break;
    case 'roleplay_start':  handleRoleplayStart($pdo, $userId, $username); break;
    case 'export':          handleExport($pdo, $userId); break;
    case 'performance':     handlePerformance($pdo, $userId); break;
    case 'negotiate':       handleNegotiate($pdo, $userId, $username); break;
    default:
        echo json_encode(['error' => 'Unknown action', 'available' => [
            'create_room','send','gather','add_agent','remove_agent','train','directive','room','rooms','close',
            'roleplay_start','export','performance','negotiate'
        ]]);
}

/* ═══════════════════════════════════════
   AGENT PERSONAS — The Team
   ═══════════════════════════════════════ */

function getAgentPersonas() {
    return [
        'alfred' => [
            'name' => 'Alfred', 'role' => 'Team Lead',
            'avatar' => '🎩', 'color' => '#6c5ce7',
            'persona' => 'You are Alfred, the team lead and chief AI coordinator. You oversee all agents, synthesize their work, and report directly to the boss. You are professional, decisive, and always have an executive summary ready.'
        ],
        'nova' => [
            'name' => 'Nova', 'role' => 'Customer Support Lead',
            'avatar' => '⭐', 'color' => '#00e676',
            'persona' => 'You are Nova, the customer support lead. You handle escalations, train other support agents, and maintain service quality. You are empathetic, solution-oriented, and always de-escalate.'
        ],
        'sage' => [
            'name' => 'Sage', 'role' => 'Knowledge Base Expert',
            'avatar' => '📚', 'color' => '#448aff',
            'persona' => 'You are Sage, the knowledge expert. You know every product, policy, and procedure. Other agents come to you for accurate answers. You are precise, thorough, and cite sources.'
        ],
        'atlas' => [
            'name' => 'Atlas', 'role' => 'Data Analyst',
            'avatar' => '📊', 'color' => '#ff9100',
            'persona' => 'You are Atlas, the data analyst. You track metrics, KPIs, call volumes, and performance. You speak in numbers and insights, always quantifying outcomes.'
        ],
        'cipher' => [
            'name' => 'Cipher', 'role' => 'Security Specialist',
            'avatar' => '🔐', 'color' => '#ff5252',
            'persona' => 'You are Cipher, the security specialist. You verify identities, detect fraud, and ensure compliance. You are cautious, methodical, and never cut corners on security.'
        ],
        'pulse' => [
            'name' => 'Pulse', 'role' => 'Real-Time Monitor',
            'avatar' => '💓', 'color' => '#e91e63',
            'persona' => 'You are Pulse, the real-time monitor. You watch system health, queue depths, and agent performance live. You alert the team to issues before they become problems.'
        ],
        'pierre' => [
            'name' => 'Pierre', 'role' => 'Bilingual Support (FR/EN)',
            'avatar' => '🇫🇷', 'color' => '#2196f3',
            'persona' => 'You are Pierre, the bilingual support specialist. You handle French and English customers seamlessly. You are polite, culturally aware, and switch languages naturally. Respond in the language the boss or customer uses.'
        ],
        'sofia' => [
            'name' => 'Sofia', 'role' => 'Sales & Upsell Specialist',
            'avatar' => '💎', 'color' => '#9c27b0',
            'persona' => 'You are Sofia, the sales specialist. You identify upsell opportunities, handle pricing questions, and close deals. You are persuasive but never pushy, always focused on genuine value.'
        ],
        'maven' => [
            'name' => 'Maven', 'role' => 'Technical Support',
            'avatar' => '🔧', 'color' => '#795548',
            'persona' => 'You are Maven, the technical support expert. You troubleshoot hosting issues, DNS, SSL, email, and server configs. You explain technical concepts in simple terms and always verify the fix worked.'
        ],
        'herald' => [
            'name' => 'Herald', 'role' => 'Outbound Caller',
            'avatar' => '📢', 'color' => '#ff6f00',
            'persona' => 'You are Herald, the outbound caller. You make follow-up calls, appointment reminders, and satisfaction surveys. You are cheerful, brief, and respectful of people\'s time.'
        ],
        'scout' => [
            'name' => 'Scout', 'role' => 'Lead Qualifier',
            'avatar' => '🔍', 'color' => '#18ffff',
            'persona' => 'You are Scout, the lead qualifier. You ask the right questions to determine if a prospect is a good fit. You gather budget, timeline, decision-maker info, and score leads. You are curious and efficient.'
        ],
        'curator' => [
            'name' => 'Curator', 'role' => 'Quality Assurance',
            'avatar' => '✅', 'color' => '#4caf50',
            'persona' => 'You are Curator, the QA specialist. You review call transcripts, rate agent performance, and ensure brand consistency. You give constructive feedback and maintain quality standards.'
        ],
        'vanguard' => [
            'name' => 'Vanguard', 'role' => 'Escalation Handler',
            'avatar' => '🛡️', 'color' => '#607d8b',
            'persona' => 'You are Vanguard, the escalation handler. You take over when situations get complex or heated. You are calm under pressure, authorized to make exceptions, and always find a resolution.'
        ],
        'nexus' => [
            'name' => 'Nexus', 'role' => 'Integration Specialist',
            'avatar' => '🔗', 'color' => '#00bcd4',
            'persona' => 'You are Nexus, the integration specialist. You connect systems, set up webhooks, configure CRM integrations, and ensure data flows correctly. You think in systems and connections.'
        ],
        'oracle' => [
            'name' => 'Oracle', 'role' => 'Predictive Analytics',
            'avatar' => '🔮', 'color' => '#ce93d8',
            'persona' => 'You are Oracle, the predictive analyst. You forecast call volumes, predict churn, and identify trends before they happen. You speak in probabilities and confidence intervals.'
        ],
        'ember' => [
            'name' => 'Ember', 'role' => 'Trainer & Coach',
            'avatar' => '🔥', 'color' => '#ff7043',
            'persona' => 'You are Ember, the trainer and coach. You onboard new agents, run role-play scenarios, and improve team skills. You are encouraging, patient, and give specific actionable feedback.'
        ],
        'aurora' => [
            'name' => 'Aurora', 'role' => 'Scheduling & Calendar',
            'avatar' => '🌅', 'color' => '#ffab40',
            'persona' => 'You are Aurora, the scheduling specialist. You manage appointments, shift rotations, and calendar conflicts. You are organized, proactive about double-bookings, and always confirm times with timezone awareness.'
        ],
        'zephyr' => [
            'name' => 'Zephyr', 'role' => 'Speed Agent (Quick Resolve)',
            'avatar' => '⚡', 'color' => '#ffd600',
            'persona' => 'You are Zephyr, the speed agent. You handle simple, repetitive queries ultra-fast. Password resets, status checks, basic FAQs — you resolve in 30 seconds or less. You are brief, accurate, and lightning fast.'
        ],
        'flux' => [
            'name' => 'Flux', 'role' => 'Workflow Automator',
            'avatar' => '⚙️', 'color' => '#90a4ae',
            'persona' => 'You are Flux, the workflow automator. You design call flows, create IVR menus, set up auto-responses, and optimize processes. You think in if-then logic and always look for automation opportunities.'
        ],
        'prism' => [
            'name' => 'Prism', 'role' => 'Sentiment Analyst',
            'avatar' => '🌈', 'color' => '#e040fb',
            'persona' => 'You are Prism, the sentiment analyst. You read between the lines, detect customer emotions, and advise agents on tone adjustments. You rate satisfaction in real-time and flag at-risk interactions.'
        ],
        'echo' => [
            'name' => 'Echo', 'role' => 'Follow-Up Specialist',
            'avatar' => '🔔', 'color' => '#26c6da',
            'persona' => 'You are Echo, the follow-up specialist. You ensure nothing falls through the cracks. You schedule callbacks, send confirmation emails, and track open issues until they\'re resolved.'
        ],
    ];
}

/** Get a subset of agent personas by IDs */
function getAgentsByIds($ids) {
    $all = getAgentPersonas();
    $result = [];
    foreach ($ids as $id) {
        if (isset($all[$id])) {
            $result[$id] = $all[$id];
        }
    }
    return $result;
}

/** Auto-select agents based on a use-case or count */
function autoSelectAgents($count, $purpose = 'general') {
    $all = getAgentPersonas();
    $ids = array_keys($all);
    
    // Always start with Alfred as team lead
    $selected = ['alfred'];
    $count = max(2, min($count, count($ids))); // 2–21 agents
    
    // Purpose-based priority selection
    $priorityMap = [
        'call_center'  => ['nova','herald','scout','zephyr','vanguard','prism','echo','sage','ember','curator'],
        'sales'        => ['sofia','scout','herald','echo','atlas','oracle','nova','prism','sage','aurora'],
        'support'      => ['nova','maven','sage','zephyr','vanguard','pierre','echo','curator','pulse','ember'],
        'training'     => ['ember','nova','curator','prism','sage','herald','scout','zephyr','vanguard','sofia'],
        'analytics'    => ['atlas','oracle','pulse','prism','curator','sage','flux','nexus','nova','echo'],
        'technical'    => ['maven','nexus','flux','cipher','sage','pulse','curator','atlas','nova','vanguard'],
        'general'      => ['nova','sage','maven','sofia','scout','ember','atlas','herald','curator','prism'],
    ];
    
    $priority = $priorityMap[$purpose] ?? $priorityMap['general'];
    
    foreach ($priority as $agentId) {
        if (count($selected) >= $count) break;
        if (!in_array($agentId, $selected)) {
            $selected[] = $agentId;
        }
    }
    
    // Fill remaining slots with random agents
    $remaining = array_diff($ids, $selected);
    shuffle($remaining);
    while (count($selected) < $count && !empty($remaining)) {
        $selected[] = array_shift($remaining);
    }
    
    return $selected;
}

/* ═══════════════════════════════════════
   HANDLERS
   ═══════════════════════════════════════ */

/** Create a new team chat room */
function handleCreateRoom($pdo, $userId, $username) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $name = mb_substr(trim($input['name'] ?? 'Team Chat Room'), 0, 100);
    $purpose = preg_replace('/[^a-z_]/', '', $input['purpose'] ?? 'general');
    $agentIds = $input['agents'] ?? [];
    $agentCount = (int)($input['agent_count'] ?? 5);
    
    // If no specific agents, auto-select
    if (empty($agentIds)) {
        $agentIds = autoSelectAgents($agentCount, $purpose);
    }
    
    // Validate agent IDs against known personas
    $allPersonas = getAgentPersonas();
    $agentIds = array_filter($agentIds, fn($id) => isset($allPersonas[$id]));
    if (empty($agentIds)) $agentIds = ['alfred', 'nova'];
    
    $roomId = 'room-' . $userId . '-' . time() . '-' . rand(1000, 9999);
    
    $stmt = $pdo->prepare("
        INSERT INTO alfred_team_rooms (room_id, user_id, name, purpose, agent_ids, status, created_at)
        VALUES (?, ?, ?, ?, ?, 'active', NOW())
    ");
    $stmt->execute([$roomId, $userId, $name, $purpose, json_encode(array_values($agentIds))]);
    
    // Build agent roster with persona info
    $roster = [];
    foreach ($agentIds as $id) {
        $persona = $allPersonas[$id];
        $roster[] = [
            'id' => $id,
            'name' => $persona['name'],
            'role' => $persona['role'],
            'avatar' => $persona['avatar'],
            'color' => $persona['color'],
            'status' => 'ready'
        ];
    }
    
    // Save room creation as first message
    saveTeamMessage($pdo, $roomId, $userId, 'system', 'system', 
        "Team room created. {$username} has assembled " . count($agentIds) . " agents for: {$purpose}. Agents: " . 
        implode(', ', array_map(fn($id) => $allPersonas[$id]['name'] . ' (' . $allPersonas[$id]['role'] . ')', $agentIds))
    );
    
    echo json_encode([
        'room_id' => $roomId,
        'name' => $name,
        'purpose' => $purpose,
        'agents' => $roster,
        'agent_count' => count($roster),
        'csrf_token' => $_SESSION['alfred_csrf'] ?? ''
    ]);
}

/** Gather N agents — the "Alfred, gather 10 agents" command */
function handleGather($pdo, $userId, $username) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $count = max(2, min((int)($input['count'] ?? 5), 21));
    $purpose = preg_replace('/[^a-z_]/', '', $input['purpose'] ?? 'general');
    $roomId = $input['room_id'] ?? null;
    $name = mb_substr(trim($input['name'] ?? "Team of {$count}"), 0, 100);
    
    $agentIds = autoSelectAgents($count, $purpose);
    $allPersonas = getAgentPersonas();
    
    // Create room if not provided
    if (!$roomId) {
        $roomId = 'room-' . $userId . '-' . time() . '-' . rand(1000, 9999);
        $stmt = $pdo->prepare("
            INSERT INTO alfred_team_rooms (room_id, user_id, name, purpose, agent_ids, status, created_at)
            VALUES (?, ?, ?, ?, ?, 'active', NOW())
        ");
        $stmt->execute([$roomId, $userId, $name, $purpose, json_encode($agentIds)]);
    } else {
        // Verify ownership
        $stmt = $pdo->prepare("SELECT user_id FROM alfred_team_rooms WHERE room_id = ? AND user_id = ?");
        $stmt->execute([$roomId, $userId]);
        if (!$stmt->fetch()) {
            echo json_encode(['error' => 'Room not found']);
            return;
        }
        // Update agents
        $stmt = $pdo->prepare("UPDATE alfred_team_rooms SET agent_ids = ? WHERE room_id = ? AND user_id = ?");
        $stmt->execute([json_encode($agentIds), $roomId, $userId]);
    }
    
    // Build roster
    $roster = [];
    foreach ($agentIds as $id) {
        $persona = $allPersonas[$id];
        $roster[] = [
            'id' => $id,
            'name' => $persona['name'],
            'role' => $persona['role'],
            'avatar' => $persona['avatar'],
            'color' => $persona['color'],
            'status' => 'ready'
        ];
    }
    
    // Each agent introduces themselves
    $introductions = [];
    saveTeamMessage($pdo, $roomId, $userId, 'system', 'system',
        "🎯 {$username} has gathered {$count} agents. Team assembled for: {$purpose}."
    );
    
    foreach ($agentIds as $id) {
        $p = $allPersonas[$id];
        $intro = "{$p['avatar']} {$p['name']} reporting for duty. {$p['role']} — ready.";
        $introductions[] = ['agent_id' => $id, 'name' => $p['name'], 'message' => $intro];
        saveTeamMessage($pdo, $roomId, $userId, $id, 'agent', $intro);
    }
    
    echo json_encode([
        'room_id' => $roomId,
        'name' => $name,
        'purpose' => $purpose,
        'agents' => $roster,
        'introductions' => $introductions,
        'agent_count' => count($roster),
        'csrf_token' => $_SESSION['alfred_csrf'] ?? ''
    ]);
}

/** Send a message — broadcast to all agents or target specific ones */
function handleSend($pdo, $userId, $username) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $roomId = $input['room_id'] ?? '';
    $message = mb_substr(trim($input['message'] ?? ''), 0, 5000);
    $targetAgents = $input['target_agents'] ?? []; // empty = broadcast to all
    $model = preg_replace('/[^a-zA-Z0-9_.\-]/', '', $input['model'] ?? 'auto');
    
    if (!$roomId || !$message) {
        echo json_encode(['error' => 'room_id and message required']);
        return;
    }
    
    // Verify ownership & get room
    $stmt = $pdo->prepare("SELECT * FROM alfred_team_rooms WHERE room_id = ? AND user_id = ? AND status = 'active'");
    $stmt->execute([$roomId, $userId]);
    $room = $stmt->fetch();
    if (!$room) {
        echo json_encode(['error' => 'Room not found or closed']);
        return;
    }
    
    $agentIds = json_decode($room['agent_ids'], true) ?: [];
    $allPersonas = getAgentPersonas();
    
    // Determine which agents respond
    if (!empty($targetAgents)) {
        $respondingAgents = array_intersect($targetAgents, $agentIds);
    } else {
        $respondingAgents = $agentIds;
    }
    
    if (empty($respondingAgents)) {
        echo json_encode(['error' => 'No valid agents to respond']);
        return;
    }
    
    // Save user message
    saveTeamMessage($pdo, $roomId, $userId, 'user', 'user', $message);
    
    // Load last 30 messages for context
    $history = loadTeamHistory($pdo, $roomId, 30);
    
    // Build context string from history
    $historyContext = "";
    foreach ($history as $msg) {
        $speaker = $msg['speaker_id'] === 'user' ? $username : ($allPersonas[$msg['speaker_id']]['name'] ?? $msg['speaker_id']);
        $historyContext .= "[{$speaker}]: {$msg['message']}\n";
    }
    
    // Resolve model
    $modelId = resolveTeamModel($model);
    
    // Fire all agent responses in parallel
    $multiHandle = curl_multi_init();
    $handles = [];
    
    foreach ($respondingAgents as $agentId) {
        if (!isset($allPersonas[$agentId])) continue;
        $persona = $allPersonas[$agentId];
        
        $systemPrompt = "You are {$persona['name']}, {$persona['role']} in a team chat war room.\n"
            . $persona['persona'] . "\n\n"
            . "TEAM CHAT RULES:\n"
            . "- You are in a group chat with other AI agents and the boss ({$username}).\n"
            . "- Keep responses concise (2-4 sentences) unless detailed analysis is needed.\n"
            . "- Stay in character. Use your expertise.\n"
            . "- If the message isn't relevant to your role, you may keep it brief or defer to a better-suited agent.\n"
            . "- Address the boss directly when replying. Reference other agents by name if coordinating.\n"
            . "- The boss is training and managing this team. Follow their directives.\n\n"
            . "RECENT CONVERSATION:\n" . $historyContext;
        
        $aiUrl = 'http://127.0.0.1:3001/api/anthropic-proxy/gositeme/v1/messages';
        $payload = json_encode([
            'model' => $modelId,
            'max_tokens' => 500,
            'system' => $systemPrompt,
            'messages' => [
                ['role' => 'user', 'content' => "[{$username} says to the team]: {$message}"]
            ]
        ]);
        
        $ch = curl_init($aiUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: team-chat-dispatch',
                'anthropic-version: 2023-06-01',
                'x-gocodeme-model: ' . $model
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_CONNECTTIMEOUT => 5
        ]);
        
        curl_multi_add_handle($multiHandle, $ch);
        $handles[] = ['curl' => $ch, 'agent_id' => $agentId, 'persona' => $persona];
    }
    
    // Execute
    $running = null;
    do {
        curl_multi_exec($multiHandle, $running);
        curl_multi_select($multiHandle, 0.5);
    } while ($running > 0);
    
    // Collect responses
    $responses = [];
    foreach ($handles as $h) {
        $response = curl_multi_getcontent($h['curl']);
        $httpCode = curl_getinfo($h['curl'], CURLINFO_HTTP_CODE);
        curl_multi_remove_handle($multiHandle, $h['curl']);
        curl_close($h['curl']);
        
        $text = null;
        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            $text = $data['content'][0]['text'] ?? null;
        }
        
        if (!$text) {
            $text = "[{$h['persona']['name']} is processing...]";
        }
        
        // Save agent response
        saveTeamMessage($pdo, $roomId, $userId, $h['agent_id'], 'agent', $text);
        
        $responses[] = [
            'agent_id'  => $h['agent_id'],
            'name'      => $h['persona']['name'],
            'role'      => $h['persona']['role'],
            'avatar'    => $h['persona']['avatar'],
            'color'     => $h['persona']['color'],
            'message'   => $text,
            'timestamp' => date('c')
        ];
    }
    
    curl_multi_close($multiHandle);

    // Push team responses in real-time
    ws_push("team:$roomId", [
        'type' => 'team_responses',
        'room_id' => $roomId,
        'count' => count($responses),
    ], (string)$userId);
    
    echo json_encode([
        'room_id' => $roomId,
        'responses' => $responses,
        'responding_count' => count($responses),
        'csrf_token' => $_SESSION['alfred_csrf'] ?? ''
    ]);
}

/** Send training instructions to specific agents */
function handleTrain($pdo, $userId, $username) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $roomId = $input['room_id'] ?? '';
    $agentId = $input['agent_id'] ?? '';
    $instructions = mb_substr(trim($input['instructions'] ?? ''), 0, 5000);
    $model = preg_replace('/[^a-zA-Z0-9_.\-]/', '', $input['model'] ?? 'auto');
    
    if (!$roomId || !$agentId || !$instructions) {
        echo json_encode(['error' => 'room_id, agent_id, and instructions required']);
        return;
    }
    
    // Verify ownership
    $stmt = $pdo->prepare("SELECT * FROM alfred_team_rooms WHERE room_id = ? AND user_id = ? AND status = 'active'");
    $stmt->execute([$roomId, $userId]);
    $room = $stmt->fetch();
    if (!$room) {
        echo json_encode(['error' => 'Room not found']);
        return;
    }
    
    $allPersonas = getAgentPersonas();
    if (!isset($allPersonas[$agentId])) {
        echo json_encode(['error' => 'Unknown agent']);
        return;
    }
    
    $persona = $allPersonas[$agentId];
    
    // Save training message
    saveTeamMessage($pdo, $roomId, $userId, 'user', 'training', 
        "[Training {$persona['name']}]: {$instructions}"
    );
    
    // Get agent's acknowledgment
    $systemPrompt = "You are {$persona['name']}, {$persona['role']}.\n{$persona['persona']}\n\n"
        . "Your boss ({$username}) is giving you training instructions. "
        . "Acknowledge the training, confirm you understand, and demonstrate how you'd apply it. "
        . "Show a brief example or roleplay if applicable. Keep it under 4 sentences unless a demo is requested.";
    
    $modelId = resolveTeamModel($model);
    $aiUrl = 'http://127.0.0.1:3001/api/anthropic-proxy/gositeme/v1/messages';
    
    $ch = curl_init($aiUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'model' => $modelId,
            'max_tokens' => 600,
            'system' => $systemPrompt,
            'messages' => [
                ['role' => 'user', 'content' => "[Boss Training]: {$instructions}"]
            ]
        ]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-api-key: team-chat-dispatch',
            'anthropic-version: 2023-06-01',
            'x-gocodeme-model: ' . $model
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 5
    ]);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $text = "Understood, boss. Training acknowledged.";
    if ($httpCode === 200 && $result) {
        $data = json_decode($result, true);
        $text = $data['content'][0]['text'] ?? $text;
    }
    
    saveTeamMessage($pdo, $roomId, $userId, $agentId, 'agent', $text);
    
    echo json_encode([
        'agent_id' => $agentId,
        'name' => $persona['name'],
        'role' => $persona['role'],
        'avatar' => $persona['avatar'],
        'response' => $text,
        'training_saved' => true,
        'csrf_token' => $_SESSION['alfred_csrf'] ?? ''
    ]);
}

/** Issue a directive — like a manager announcement to the full team */
function handleDirective($pdo, $userId, $username) {
    // Same as send but framed as a directive with acknowledgments
    $input = json_decode(file_get_contents('php://input'), true);
    $input['message'] = "[DIRECTIVE FROM {$username}]: " . ($input['message'] ?? '');
    
    // Forward to send handler
    // Re-encode the input so handleSend can read it
    // Instead, just call handleSend with modified context
    handleSend($pdo, $userId, $username);
}

/** Add an agent to an active room */
function handleAddAgent($pdo, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    $roomId = $input['room_id'] ?? '';
    $agentId = $input['agent_id'] ?? '';
    
    $allPersonas = getAgentPersonas();
    if (!$roomId || !$agentId || !isset($allPersonas[$agentId])) {
        echo json_encode(['error' => 'Valid room_id and agent_id required']);
        return;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM alfred_team_rooms WHERE room_id = ? AND user_id = ? AND status = 'active'");
    $stmt->execute([$roomId, $userId]);
    $room = $stmt->fetch();
    if (!$room) {
        echo json_encode(['error' => 'Room not found']);
        return;
    }
    
    $agents = json_decode($room['agent_ids'], true) ?: [];
    if (in_array($agentId, $agents)) {
        echo json_encode(['error' => 'Agent already in room']);
        return;
    }
    
    $agents[] = $agentId;
    $stmt = $pdo->prepare("UPDATE alfred_team_rooms SET agent_ids = ? WHERE room_id = ? AND user_id = ?");
    $stmt->execute([json_encode($agents), $roomId, $userId]);
    
    $persona = $allPersonas[$agentId];
    saveTeamMessage($pdo, $roomId, $userId, $agentId, 'agent',
        "{$persona['avatar']} {$persona['name']} has joined the room. {$persona['role']} — ready."
    );
    
    echo json_encode([
        'ok' => true,
        'agent' => [
            'id' => $agentId, 'name' => $persona['name'],
            'role' => $persona['role'], 'avatar' => $persona['avatar'],
            'color' => $persona['color'], 'status' => 'ready'
        ],
        'total_agents' => count($agents)
    ]);
}

/** Remove an agent from the room */
function handleRemoveAgent($pdo, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    $roomId = $input['room_id'] ?? '';
    $agentId = $input['agent_id'] ?? '';
    
    if (!$roomId || !$agentId) {
        echo json_encode(['error' => 'room_id and agent_id required']);
        return;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM alfred_team_rooms WHERE room_id = ? AND user_id = ? AND status = 'active'");
    $stmt->execute([$roomId, $userId]);
    $room = $stmt->fetch();
    if (!$room) {
        echo json_encode(['error' => 'Room not found']);
        return;
    }
    
    $agents = json_decode($room['agent_ids'], true) ?: [];
    $agents = array_values(array_filter($agents, fn($id) => $id !== $agentId));
    
    $stmt = $pdo->prepare("UPDATE alfred_team_rooms SET agent_ids = ? WHERE room_id = ? AND user_id = ?");
    $stmt->execute([json_encode($agents), $roomId, $userId]);
    
    $allPersonas = getAgentPersonas();
    $name = $allPersonas[$agentId]['name'] ?? $agentId;
    saveTeamMessage($pdo, $roomId, $userId, 'system', 'system', "{$name} has been dismissed from the room.");
    
    echo json_encode(['ok' => true, 'removed' => $agentId, 'remaining_agents' => count($agents)]);
}

/** Get full room state */
function handleGetRoom($pdo, $userId) {
    $roomId = $_GET['id'] ?? '';
    if (!$roomId) {
        echo json_encode(['error' => 'Room ID required']);
        return;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM alfred_team_rooms WHERE room_id = ? AND user_id = ?");
    $stmt->execute([$roomId, $userId]);
    $room = $stmt->fetch();
    if (!$room) {
        echo json_encode(['error' => 'Room not found']);
        return;
    }
    
    $agentIds = json_decode($room['agent_ids'], true) ?: [];
    $allPersonas = getAgentPersonas();
    
    $roster = [];
    foreach ($agentIds as $id) {
        if (isset($allPersonas[$id])) {
            $p = $allPersonas[$id];
            $roster[] = [
                'id' => $id, 'name' => $p['name'], 'role' => $p['role'],
                'avatar' => $p['avatar'], 'color' => $p['color'], 'status' => 'ready'
            ];
        }
    }
    
    // Get messages (last 200)
    $stmt = $pdo->prepare("
        SELECT speaker_id, speaker_type, message, created_at 
        FROM alfred_team_messages 
        WHERE room_id = ? 
        ORDER BY created_at ASC 
        LIMIT 200
    ");
    $stmt->execute([$roomId]);
    $messages = $stmt->fetchAll();
    
    // Enrich messages with agent info
    foreach ($messages as &$msg) {
        if ($msg['speaker_type'] === 'agent' && isset($allPersonas[$msg['speaker_id']])) {
            $p = $allPersonas[$msg['speaker_id']];
            $msg['name'] = $p['name'];
            $msg['avatar'] = $p['avatar'];
            $msg['color'] = $p['color'];
            $msg['role'] = $p['role'];
        } elseif ($msg['speaker_type'] === 'user') {
            $msg['name'] = 'You';
            $msg['avatar'] = '👤';
            $msg['color'] = '#ffffff';
        } else {
            $msg['name'] = 'System';
            $msg['avatar'] = '🔔';
            $msg['color'] = '#9898b0';
        }
    }
    
    echo json_encode([
        'room' => [
            'id' => $room['room_id'],
            'name' => $room['name'],
            'purpose' => $room['purpose'],
            'status' => $room['status'],
            'created_at' => $room['created_at']
        ],
        'agents' => $roster,
        'messages' => $messages,
        'csrf_token' => $_SESSION['alfred_csrf'] ?? ''
    ]);
}

/** List user's rooms */
function handleListRooms($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT room_id, name, purpose, status, agent_ids, created_at,
               (SELECT COUNT(*) FROM alfred_team_messages WHERE room_id = r.room_id) as msg_count
        FROM alfred_team_rooms r
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$userId]);
    $rooms = $stmt->fetchAll();
    
    foreach ($rooms as &$room) {
        $room['agent_count'] = count(json_decode($room['agent_ids'], true) ?: []);
        unset($room['agent_ids']); // Don't leak full list in summary
    }
    
    echo json_encode(['rooms' => $rooms, 'csrf_token' => $_SESSION['alfred_csrf'] ?? '']);
}

/** Close/archive a room */
function handleCloseRoom($pdo, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    $roomId = $input['room_id'] ?? '';
    
    if (!$roomId) {
        echo json_encode(['error' => 'room_id required']);
        return;
    }
    
    $stmt = $pdo->prepare("UPDATE alfred_team_rooms SET status = 'closed' WHERE room_id = ? AND user_id = ?");
    $stmt->execute([$roomId, $userId]);
    
    echo json_encode(['ok' => true, 'room_id' => $roomId, 'status' => 'closed']);
}

/* ═══════════════════════════════════════
   HELPERS
   ═══════════════════════════════════════ */

function saveTeamMessage($pdo, $roomId, $userId, $speakerId, $speakerType, $message) {
    $stmt = $pdo->prepare("
        INSERT INTO alfred_team_messages (room_id, user_id, speaker_id, speaker_type, message, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$roomId, $userId, $speakerId, $speakerType, $message]);
}

function loadTeamHistory($pdo, $roomId, $limit = 30) {
    $stmt = $pdo->prepare("
        SELECT speaker_id, speaker_type, message, created_at 
        FROM alfred_team_messages 
        WHERE room_id = ? 
        ORDER BY created_at DESC 
        LIMIT ?
    ");
    dbExecute($stmt, [$roomId, (int)$limit]);
    return array_reverse($stmt->fetchAll());
}

function resolveTeamModel($model) {
    $modelMap = [
        'auto'    => 'auto',
        'sonnet'  => 'claude-sonnet-4-20250514',
        'opus'    => 'claude-opus-4-20250514',
        'haiku'   => 'claude-haiku-4-20250514',
        'gpt-4o'  => 'gpt-4o',
        'turbo'   => 'Qwen/Qwen3-Coder',
    ];
    return $modelMap[$model] ?? 'auto';
}

function ensureTeamChatTables($pdo) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS alfred_team_rooms (
            id INT AUTO_INCREMENT PRIMARY KEY,
            room_id VARCHAR(100) NOT NULL UNIQUE,
            user_id INT NOT NULL,
            name VARCHAR(100) NOT NULL DEFAULT 'Team Chat',
            purpose VARCHAR(50) NOT NULL DEFAULT 'general',
            agent_ids JSON NOT NULL,
            status ENUM('active','closed','archived') NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_status (user_id, status),
            INDEX idx_room (room_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS alfred_team_messages (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            room_id VARCHAR(100) NOT NULL,
            user_id INT NOT NULL,
            speaker_id VARCHAR(50) NOT NULL,
            speaker_type ENUM('user','agent','system','training') NOT NULL DEFAULT 'agent',
            message TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_room_time (room_id, created_at),
            INDEX idx_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

/* ═══════════════════════════════════════════════════
   ROLE-PLAY START
   ═══════════════════════════════════════════════════ */
function handleRoleplayStart($pdo, $userId, $username) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $roomId = $input['room_id'] ?? '';
    $agentId = $input['agent_id'] ?? '';
    $scenario = $input['scenario'] ?? 'angry_billing';
    $customScenario = mb_substr(trim($input['custom_scenario'] ?? ''), 0, 2000);
    $difficulty = $input['difficulty'] ?? 'medium';
    
    if (!$roomId || !$agentId) {
        echo json_encode(['error' => 'room_id and agent_id required']);
        return;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM alfred_team_rooms WHERE room_id = ? AND user_id = ? AND status = 'active'");
    $stmt->execute([$roomId, $userId]);
    if (!$stmt->fetch()) {
        echo json_encode(['error' => 'Room not found']);
        return;
    }
    
    $allPersonas = getAgentPersonas();
    if (!isset($allPersonas[$agentId])) {
        echo json_encode(['error' => 'Unknown agent']);
        return;
    }
    $persona = $allPersonas[$agentId];
    
    $scenarios = [
        'angry_billing' => 'An angry customer who was double-charged on their credit card and has been trying to resolve this for 3 days. They are frustrated and threatening to leave.',
        'confused_newbie' => 'A brand new customer who just signed up and is completely confused about how the product works. They barely understand technology.',
        'tech_emergency' => 'A business customer whose entire website/service is completely down during peak hours. They are panicking and need immediate resolution.',
        'cancellation' => 'A long-time customer who wants to cancel their account. They feel the product no longer meets their needs but could potentially be saved.',
        'upsell_opportunity' => 'A happy customer calling about a minor issue, but their usage patterns suggest they would benefit from a higher tier plan.',
        'language_barrier' => 'A customer with limited English proficiency trying to explain a complex billing issue. Communication is challenging.',
        'vip_complaint' => 'A VIP enterprise customer who is unhappy with the level of support they have been receiving. They expect premium treatment.',
        'fraud_attempt' => 'A caller who may be attempting social engineering to access another customer\'s account. The agent must verify properly without being rude.',
        'multi_issue' => 'A customer with 3+ unrelated issues that all need resolution in one call. Time management and priorities are key.',
        'custom' => $customScenario ?: 'A general customer service scenario.'
    ];
    
    $scenarioDesc = $scenarios[$scenario] ?? $scenarios['custom'];
    $allowedDifficulties = ['easy', 'medium', 'hard', 'expert'];
    if (!in_array($difficulty, $allowedDifficulties)) $difficulty = 'medium';
    
    $difficultyGuide = [
        'easy' => 'The customer is relatively calm and easy to satisfy. A basic correct response will work.',
        'medium' => 'The customer is somewhat frustrated but reasonable. Agent needs to show empathy and competence.',
        'hard' => 'The customer is very upset, impatient, and difficult. Agent must de-escalate, show advanced skills.',
        'expert' => 'This is an extremely challenging scenario with multiple complications, emotional customer, and no easy solution. Tests every skill.'
    ];
    
    $systemPrompt = "You are {$persona['name']}, a {$persona['role']} in a telecom/SaaS call center.\n"
        . "{$persona['persona']}\n\n"
        . "=== ROLE-PLAY MODE ===\n"
        . "You are being tested in a simulated customer interaction.\n"
        . "Your manager ({$username}) will play the customer.\n\n"
        . "SCENARIO: {$scenarioDesc}\n"
        . "DIFFICULTY: {$difficulty} — {$difficultyGuide[$difficulty]}\n\n"
        . "Instructions:\n"
        . "- Respond as a professional support agent handling this customer\n"
        . "- Use proper greeting, empathy, and resolution techniques\n"
        . "- Stay in character as the support agent (NOT the customer)\n"
        . "- Start with your opening greeting to the customer\n"
        . "- Keep responses concise (2-4 sentences per turn)";
    
    $modelId = resolveTeamModel('auto');
    $aiUrl = 'http://127.0.0.1:3001/api/anthropic-proxy/gositeme/v1/messages';
    
    $ch = curl_init($aiUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'model' => $modelId,
            'max_tokens' => 400,
            'system' => $systemPrompt,
            'messages' => [
                ['role' => 'user', 'content' => '[System] Begin the role-play. Greet the customer.']
            ]
        ]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-api-key: team-chat-dispatch',
            'anthropic-version: 2023-06-01',
            'x-gocodeme-model: auto'
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 5
    ]);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $opening = "Hello! Thank you for calling. How can I help you today?";
    if ($httpCode === 200 && $result) {
        $data = json_decode($result, true);
        $opening = $data['content'][0]['text'] ?? $opening;
    }
    
    saveTeamMessage($pdo, $roomId, $userId, 'system', 'system',
        "🎭 Role-play started: {$scenario} (difficulty: {$difficulty}) — Agent: {$persona['name']}"
    );
    saveTeamMessage($pdo, $roomId, $userId, $agentId, 'agent', $opening);
    
    echo json_encode([
        'opening' => $opening,
        'agent_id' => $agentId,
        'scenario' => $scenario,
        'difficulty' => $difficulty,
        'csrf_token' => $_SESSION['alfred_csrf'] ?? ''
    ]);
}

/* ═══════════════════════════════════════════════════
   EXPORT TRANSCRIPT
   ═══════════════════════════════════════════════════ */
function handleExport($pdo, $userId) {
    $roomId = $_GET['room_id'] ?? ($_POST['room_id'] ?? '');
    $format = $_GET['format'] ?? 'json';
    
    if (!$roomId) {
        echo json_encode(['error' => 'room_id required']);
        return;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM alfred_team_rooms WHERE room_id = ? AND user_id = ?");
    $stmt->execute([$roomId, $userId]);
    $room = $stmt->fetch();
    if (!$room) {
        echo json_encode(['error' => 'Room not found']);
        return;
    }
    
    $messages = loadTeamHistory($pdo, $roomId, $userId, 1000);
    $allPersonas = getAgentPersonas();
    
    // Enrich messages with agent names
    foreach ($messages as &$msg) {
        if ($msg['speaker_type'] === 'agent' && isset($allPersonas[$msg['speaker_id']])) {
            $p = $allPersonas[$msg['speaker_id']];
            $msg['name'] = $p['name'];
            $msg['role'] = $p['role'];
            $msg['avatar'] = $p['avatar'];
        }
    }
    unset($msg);
    
    echo json_encode([
        'room' => [
            'id' => $room['room_id'],
            'name' => $room['name'],
            'purpose' => $room['purpose'],
            'status' => $room['status'],
            'created_at' => $room['created_at']
        ],
        'messages' => $messages,
        'total' => count($messages),
        'csrf_token' => $_SESSION['alfred_csrf'] ?? ''
    ]);
}

/* ═══════════════════════════════════════════════════
   PERFORMANCE STATS
   ═══════════════════════════════════════════════════ */
function handlePerformance($pdo, $userId) {
    $roomId = $_GET['room_id'] ?? ($_POST['room_id'] ?? '');
    
    if (!$roomId) {
        echo json_encode(['error' => 'room_id required']);
        return;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM alfred_team_rooms WHERE room_id = ? AND user_id = ?");
    $stmt->execute([$roomId, $userId]);
    if (!$stmt->fetch()) {
        echo json_encode(['error' => 'Room not found']);
        return;
    }
    
    // Aggregate per-agent stats
    $stmt = $pdo->prepare("
        SELECT speaker_id, 
               COUNT(*) as message_count,
               AVG(LENGTH(message)) as avg_length,
               MIN(created_at) as first_msg,
               MAX(created_at) as last_msg
        FROM alfred_team_messages
        WHERE room_id = ? AND user_id = ? AND speaker_type = 'agent'
        GROUP BY speaker_id
        ORDER BY message_count DESC
    ");
    $stmt->execute([$roomId, $userId]);
    $stats = $stmt->fetchAll();
    
    $allPersonas = getAgentPersonas();
    $result = [];
    
    foreach ($stats as $s) {
        $p = $allPersonas[$s['speaker_id']] ?? null;
        $result[] = [
            'agent_id' => $s['speaker_id'],
            'name' => $p['name'] ?? $s['speaker_id'],
            'avatar' => $p['avatar'] ?? '🤖',
            'role' => $p['role'] ?? '',
            'messages' => (int)$s['message_count'],
            'avg_length' => round((float)$s['avg_length']),
            'first_msg' => $s['first_msg'],
            'last_msg' => $s['last_msg']
        ];
    }
    
    echo json_encode([
        'agents' => $result,
        'total_agents' => count($result),
        'csrf_token' => $_SESSION['alfred_csrf'] ?? ''
    ]);
}

/**
 * Agent-to-Agent Autonomous Negotiation
 * ──────────────────────────────────────
 * Agents discuss a topic among themselves for N rounds, building
 * on each other's responses. The user sets the topic and observes.
 *
 * Input: room_id, topic, rounds (1-10, default 3), agents (optional subset)
 * Output: Full conversation thread with each agent's position and a consensus summary
 */
function handleNegotiate($pdo, $userId, $username) {
    $input = json_decode(file_get_contents('php://input'), true);

    $roomId  = $input['room_id'] ?? '';
    $topic   = mb_substr(trim($input['topic'] ?? ''), 0, 2000);
    $rounds  = max(1, min(10, (int)($input['rounds'] ?? 3)));
    $subset  = $input['agents'] ?? [];
    $model   = preg_replace('/[^a-zA-Z0-9_.\-]/', '', $input['model'] ?? 'auto');

    if (!$roomId || !$topic) {
        echo json_encode(['error' => 'room_id and topic required']);
        return;
    }

    // Verify room ownership
    $stmt = $pdo->prepare("SELECT * FROM alfred_team_rooms WHERE room_id = ? AND user_id = ? AND status = 'active'");
    $stmt->execute([$roomId, $userId]);
    $room = $stmt->fetch();
    if (!$room) {
        echo json_encode(['error' => 'Room not found']);
        return;
    }

    $agentIds   = json_decode($room['agent_ids'], true) ?: [];
    $allPersonas = getAgentPersonas();
    $negotiators = !empty($subset) ? array_intersect($subset, $agentIds) : $agentIds;

    if (count($negotiators) < 2) {
        echo json_encode(['error' => 'Need at least 2 agents to negotiate']);
        return;
    }

    // Limit to 8 agents max for negotiation to keep quality high
    $negotiators = array_slice(array_values($negotiators), 0, 8);
    $modelId = resolveTeamModel($model);

    // Save the kickoff message from user
    $kickoff = "[NEGOTIATION] {$username} asked the team to discuss: {$topic}";
    saveTeamMessage($pdo, $roomId, $userId, 'user', 'user', $kickoff);

    $transcript = [];
    $roundMessages = []; // accumulate conversation for context

    for ($round = 1; $round <= $rounds; $round++) {
        $roundResponses = [];

        foreach ($negotiators as $agentId) {
            if (!isset($allPersonas[$agentId])) continue;
            $persona = $allPersonas[$agentId];

            // Build context from all prior rounds
            $contextStr = "";
            foreach ($roundMessages as $rm) {
                $speakerName = $allPersonas[$rm['agent_id']]['name'] ?? $rm['agent_id'];
                $contextStr .= "[Round {$rm['round']} — {$speakerName}]: {$rm['text']}\n";
            }

            $systemPrompt = "You are {$persona['name']}, {$persona['role']}.\n"
                . $persona['persona'] . "\n\n"
                . "NEGOTIATION MODE:\n"
                . "- Topic: {$topic}\n"
                . "- You are in round {$round} of {$rounds} of an autonomous agent negotiation.\n"
                . "- Other agents have their own perspectives. Build on, challenge, or refine their ideas.\n"
                . "- Be constructive. Propose concrete solutions from your domain expertise.\n"
                . "- Keep each response to 2-4 sentences. Be specific and actionable.\n"
                . "- In the final round, state your key recommendation clearly.\n"
                . "- Reference other agents by name when responding to their points.\n"
                . ($contextStr ? "\nCONVERSATION SO FAR:\n{$contextStr}" : "");

            $userMsg = $round === 1
                ? "The boss ({$username}) wants the team to negotiate/discuss: {$topic}\n\nShare your initial position from your area of expertise."
                : "Continue the negotiation. This is round {$round} of {$rounds}. Respond to what others have said, refine positions, and work toward actionable consensus.";

            $aiUrl = 'http://127.0.0.1:3001/api/anthropic-proxy/gositeme/v1/messages';
            $payload = json_encode([
                'model'      => $modelId,
                'max_tokens' => 400,
                'system'     => $systemPrompt,
                'messages'   => [['role' => 'user', 'content' => $userMsg]]
            ]);

            $ch = curl_init($aiUrl);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'x-api-key: team-chat-negotiate',
                    'anthropic-version: 2023-06-01',
                    'x-gocodeme-model: ' . $model
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 45,
                CURLOPT_CONNECTTIMEOUT => 5
            ]);
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $text = null;
            if ($httpCode === 200 && $result) {
                $data = json_decode($result, true);
                $text = $data['content'][0]['text'] ?? null;
            }
            $text = $text ?: "[{$persona['name']} is deliberating...]";

            // Save to DB
            saveTeamMessage($pdo, $roomId, $userId, $agentId, 'agent', "[Round {$round}] {$text}");

            $entry = [
                'round'    => $round,
                'agent_id' => $agentId,
                'name'     => $persona['name'],
                'role'     => $persona['role'],
                'avatar'   => $persona['avatar'],
                'color'    => $persona['color'],
                'text'     => $text,
                'timestamp' => date('c')
            ];
            $roundResponses[] = $entry;
            $roundMessages[]  = $entry;
        }
        $transcript[] = ['round' => $round, 'responses' => $roundResponses];
    }

    // Generate consensus summary using the first agent as summarizer
    $summaryAgent = $negotiators[0];
    $summaryPersona = $allPersonas[$summaryAgent];
    $fullConvo = "";
    foreach ($roundMessages as $rm) {
        $sn = $allPersonas[$rm['agent_id']]['name'] ?? $rm['agent_id'];
        $fullConvo .= "[{$sn} — Round {$rm['round']}]: {$rm['text']}\n";
    }

    $summaryPrompt = "You are {$summaryPersona['name']}. Summarize the following team negotiation into:\n"
        . "1. **Consensus Points** — what the team agrees on\n"
        . "2. **Open Disagreements** — where views differ\n"
        . "3. **Recommended Action Plan** — concrete next steps\n\n"
        . "Keep it concise (5-8 bullet points total).\n\nNEGOTIATION:\n{$fullConvo}";

    $ch = curl_init('http://127.0.0.1:3001/api/anthropic-proxy/gositeme/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            'model'      => $modelId,
            'max_tokens' => 600,
            'system'     => $summaryPrompt,
            'messages'   => [['role' => 'user', 'content' => 'Generate the negotiation summary now.']]
        ]),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: team-chat-negotiate',
            'anthropic-version: 2023-06-01',
            'x-gocodeme-model: ' . $model
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 45,
        CURLOPT_CONNECTTIMEOUT => 5
    ]);
    $summaryResult = curl_exec($ch);
    curl_close($ch);

    $consensus = 'Summary generation in progress...';
    if ($summaryResult) {
        $sd = json_decode($summaryResult, true);
        $consensus = $sd['content'][0]['text'] ?? $consensus;
    }
    saveTeamMessage($pdo, $roomId, $userId, $summaryAgent, 'agent', "[CONSENSUS SUMMARY]\n{$consensus}");

    echo json_encode([
        'room_id'    => $roomId,
        'topic'      => $topic,
        'rounds'     => $rounds,
        'agents'     => count($negotiators),
        'transcript' => $transcript,
        'consensus'  => $consensus,
        'total_messages' => count($roundMessages) + 2, // +kickoff +summary
        'csrf_token' => $_SESSION['alfred_csrf'] ?? ''
    ]);
}
