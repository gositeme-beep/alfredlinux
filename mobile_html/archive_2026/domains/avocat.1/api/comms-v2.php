<?php
/**
 * GoSiteMe Veil API v2 — Extended Endpoints
 *
 * Groups, Alfred AI, reactions, threads, voice messages,
 * typing indicators, multi-device, command center.
 *
 * This file is included by the main comms.php when v2 actions are requested.
require_once dirname(__DIR__) . '/includes/api-security.php';
 * Variables available: $pdo, $clientId, $action, csrfToken functions
 *
 * V2 Actions:
 *   POST  create_group         — Create encrypted group room
 *   POST  group_invite         — Invite member to group
 *   POST  group_remove         — Remove member from group
 *   POST  group_send           — Send encrypted group message
 *   GET   group_messages       — Get group message history
 *   GET   group_members        — List group members
 *   GET   my_groups            — List user's groups
 *   POST  group_distribute_key — Distribute Sender Key to a member
 *   POST  leave_group          — Leave a group
 *   POST  alfred               — Send message to Alfred AI
 *   GET   alfred_alerts        — Get Alfred proactive alerts
 *   POST  react                — Add/remove reaction
 *   GET   reactions            — Get reactions for a message
 *   POST  typing               — Send typing indicator
 *   GET   typing_status        — Poll typing indicators
 *   POST  edit_message         — Edit a sent message
 *   POST  device_register      — Register a new device
 *   GET   devices              — List linked devices
 *   POST  device_remove        — Remove a linked device
 *   GET   dashboard            — Get command center data
 *   POST  push_subscribe       — Subscribe to push notifications
 *   GET   push_key             — Get VAPID public key
 */

switch ($action) {

// =====================================================================
// CREATE GROUP
// =====================================================================
case 'create_group':
    requirePost();
    $input = getInput();

    $name = trim($input['name'] ?? '');
    $desc = trim($input['description'] ?? '');
    $type = in_array($input['type'] ?? '', ['private','channel','broadcast']) ? $input['type'] : 'private';
    $memberIds = $input['member_ids'] ?? [];

    if (!$name || strlen($name) < 1 || strlen($name) > 200) {
        respond(['error' => 'Group name required (max 200 chars)'], 400);
    }
    if (!is_array($memberIds)) $memberIds = [];

    $groupId = bin2hex(random_bytes(16));
    $inviteLink = bin2hex(random_bytes(16));

    $pdo->beginTransaction();
    try {
        // Create group
        $stmt = $pdo->prepare("
            INSERT INTO comms_groups (group_id, name, description, creator_id, group_type, invite_link)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$groupId, $name, $desc, $clientId, $type, $inviteLink]);

        // Add creator as owner
        $pdo->prepare("
            INSERT INTO comms_group_members (group_id, client_id, role) VALUES (?, ?, 'owner')
        ")->execute([$groupId, $clientId]);

        // Add initial members
        $memberIds = array_map('intval', array_slice($memberIds, 0, 255));
        $memberIds = array_filter($memberIds, fn($id) => $id > 0 && $id !== $clientId);

        if (!empty($memberIds)) {
            $stmt = $pdo->prepare("
                INSERT IGNORE INTO comms_group_members (group_id, client_id, role) VALUES (?, ?, 'member')
            ");
            foreach ($memberIds as $mid) {
                $stmt->execute([$groupId, $mid]);
            }
        }

        // Send system message
        $pdo->prepare("
            INSERT INTO comms_group_messages (group_id, sender_id, ciphertext, iv, message_type)
            VALUES (?, ?, ?, '', 3)
        ")->execute([$groupId, $clientId, json_encode(['event' => 'group_created', 'name' => $name])]);

        $pdo->commit();

        respond([
            'success'      => true,
            'group_id'     => $groupId,
            'invite_link'  => $inviteLink,
            'csrf_token'   => $_SESSION['comms_csrf'],
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('comms create_group: ' . $e->getMessage());
        respond(['error' => 'Failed to create group'], 500);
    }
    break;

// =====================================================================
// GROUP INVITE
// =====================================================================
case 'group_invite':
    requirePost();
    $input = getInput();

    $groupId   = preg_replace('/[^a-f0-9]/', '', $input['group_id'] ?? '');
    $inviteeId = (int) ($input['member_id'] ?? 0);

    if (!$groupId || $inviteeId < 1) respond(['error' => 'Invalid parameters'], 400);

    // Verify caller is admin/owner
    $stmt = $pdo->prepare("SELECT role FROM comms_group_members WHERE group_id = ? AND client_id = ?");
    $stmt->execute([$groupId, $clientId]);
    $membership = $stmt->fetch();
    if (!$membership || !in_array($membership['role'], ['owner', 'admin'])) {
        respond(['error' => 'Only admins can invite members'], 403);
    }

    // Check group max members
    $stmt = $pdo->prepare("SELECT max_members FROM comms_groups WHERE group_id = ?");
    $stmt->execute([$groupId]);
    $group = $stmt->fetch();
    $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM comms_group_members WHERE group_id = ?");
    $stmt2->execute([$groupId]);
    if ((int)$stmt2->fetchColumn() >= (int)($group['max_members'] ?? 256)) {
        respond(['error' => 'Group is full'], 400);
    }

    $pdo->prepare("
        INSERT IGNORE INTO comms_group_members (group_id, client_id, role) VALUES (?, ?, 'member')
    ")->execute([$groupId, $inviteeId]);

    respond(['success' => true, 'csrf_token' => $_SESSION['comms_csrf']]);
    break;

// =====================================================================
// GROUP REMOVE MEMBER
// =====================================================================
case 'group_remove':
    requirePost();
    $input = getInput();

    $groupId  = preg_replace('/[^a-f0-9]/', '', $input['group_id'] ?? '');
    $removeId = (int) ($input['member_id'] ?? 0);

    if (!$groupId || $removeId < 1) respond(['error' => 'Invalid parameters'], 400);

    // Verify caller is admin/owner
    $stmt = $pdo->prepare("SELECT role FROM comms_group_members WHERE group_id = ? AND client_id = ?");
    $stmt->execute([$groupId, $clientId]);
    $membership = $stmt->fetch();
    if (!$membership || !in_array($membership['role'], ['owner', 'admin'])) {
        respond(['error' => 'Only admins can remove members'], 403);
    }

    // Can't remove the owner
    $stmt = $pdo->prepare("SELECT role FROM comms_group_members WHERE group_id = ? AND client_id = ?");
    $stmt->execute([$groupId, $removeId]);
    $target = $stmt->fetch();
    if ($target && $target['role'] === 'owner') {
        respond(['error' => 'Cannot remove group owner'], 403);
    }

    $pdo->prepare("DELETE FROM comms_group_members WHERE group_id = ? AND client_id = ?")->execute([$groupId, $removeId]);

    respond(['success' => true, 'rotate_keys' => true, 'csrf_token' => $_SESSION['comms_csrf']]);
    break;

// =====================================================================
// LEAVE GROUP
// =====================================================================
case 'leave_group':
    requirePost();
    $input = getInput();
    $groupId = preg_replace('/[^a-f0-9]/', '', $input['group_id'] ?? '');

    if (!$groupId) respond(['error' => 'Invalid group'], 400);

    // Check if owner (can't leave unless transferring)
    $stmt = $pdo->prepare("SELECT role FROM comms_group_members WHERE group_id = ? AND client_id = ?");
    $stmt->execute([$groupId, $clientId]);
    $membership = $stmt->fetch();
    if ($membership && $membership['role'] === 'owner') {
        // Transfer to next admin, or first member
        $stmt = $pdo->prepare("SELECT client_id FROM comms_group_members WHERE group_id = ? AND client_id != ? ORDER BY role ASC, joined_at ASC LIMIT 1");
        $stmt->execute([$groupId, $clientId]);
        $next = $stmt->fetch();
        if ($next) {
            $pdo->prepare("UPDATE comms_group_members SET role = 'owner' WHERE group_id = ? AND client_id = ?")->execute([$groupId, $next['client_id']]);
        }
    }

    $pdo->prepare("DELETE FROM comms_group_members WHERE group_id = ? AND client_id = ?")->execute([$groupId, $clientId]);

    respond(['success' => true, 'csrf_token' => $_SESSION['comms_csrf']]);
    break;

// =====================================================================
// GROUP SEND — Encrypted group message
// =====================================================================
case 'group_send':
    requirePost();
    $input = getInput();

    $groupId      = preg_replace('/[^a-f0-9]/', '', $input['group_id'] ?? '');
    $ciphertext   = $input['ciphertext'] ?? '';
    $iv           = $input['iv'] ?? '';
    $senderKeyId  = preg_replace('/[^a-f0-9]/', '', $input['sender_key_id'] ?? '');
    $messageType  = (int) ($input['message_type'] ?? 0);
    $replyTo      = !empty($input['reply_to']) ? (int) $input['reply_to'] : null;
    $expiresIn    = (int) ($input['expires_in'] ?? 0);

    if (!$groupId || !$ciphertext || !$iv) {
        respond(['error' => 'Missing required fields'], 400);
    }
    if (strlen($ciphertext) > MAX_MESSAGE_SIZE) {
        respond(['error' => 'Message too large'], 413);
    }

    // Verify membership
    $stmt = $pdo->prepare("SELECT role FROM comms_group_members WHERE group_id = ? AND client_id = ?");
    $stmt->execute([$groupId, $clientId]);
    $member = $stmt->fetch();
    if (!$member) respond(['error' => 'Not a member of this group'], 403);
    if ($member['role'] === 'readonly') respond(['error' => 'Read-only members cannot send messages'], 403);

    $expiresAt = $expiresIn > 0 ? date('Y-m-d H:i:s', time() + $expiresIn) : null;

    $stmt = $pdo->prepare("
        INSERT INTO comms_group_messages (group_id, sender_id, ciphertext, iv, sender_key_id, message_type, reply_to, expires_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$groupId, $clientId, $ciphertext, $iv, $senderKeyId, $messageType, $replyTo, $expiresAt]);

    respond([
        'success'    => true,
        'message_id' => (int) $pdo->lastInsertId(),
        'csrf_token' => $_SESSION['comms_csrf'],
    ]);
    break;

// =====================================================================
// GROUP MESSAGES — History
// =====================================================================
case 'group_messages':
    $groupId = preg_replace('/[^a-f0-9]/', '', $_GET['group_id'] ?? '');
    $before  = (int) ($_GET['before'] ?? PHP_INT_MAX);
    $limit   = min(100, max(1, (int) ($_GET['limit'] ?? 50)));

    if (!$groupId) respond(['error' => 'Missing group_id'], 400);

    // Verify membership
    $stmt = $pdo->prepare("SELECT role FROM comms_group_members WHERE group_id = ? AND client_id = ?");
    $stmt->execute([$groupId, $clientId]);
    if (!$stmt->fetch()) respond(['error' => 'Not a member'], 403);

    $stmt = $pdo->prepare("
        SELECT gm.id, gm.sender_id, gm.ciphertext, gm.iv, gm.sender_key_id,
               gm.message_type, gm.reply_to, gm.edited_at, gm.expires_at, gm.created_at,
               cl.firstname, cl.lastname
        FROM comms_group_messages gm
        LEFT JOIN clients cl ON cl.id = gm.sender_id
        WHERE gm.group_id = ? AND gm.id < ?
        ORDER BY gm.created_at DESC LIMIT ?
    ");
    dbExecute($stmt, [$groupId, $before, $limit]);
    $messages = array_reverse($stmt->fetchAll());

    respond(['success' => true, 'messages' => $messages]);
    break;

// =====================================================================
// GROUP MEMBERS — List
// =====================================================================
case 'group_members':
    $groupId = preg_replace('/[^a-f0-9]/', '', $_GET['group_id'] ?? '');
    if (!$groupId) respond(['error' => 'Missing group_id'], 400);

    // Verify membership
    $stmt = $pdo->prepare("SELECT role FROM comms_group_members WHERE group_id = ? AND client_id = ?");
    $stmt->execute([$groupId, $clientId]);
    if (!$stmt->fetch()) respond(['error' => 'Not a member'], 403);

    $stmt = $pdo->prepare("
        SELECT gm.client_id, gm.role, gm.joined_at, cl.firstname, cl.lastname
        FROM comms_group_members gm
        JOIN clients cl ON cl.id = gm.client_id
        WHERE gm.group_id = ?
        ORDER BY FIELD(gm.role, 'owner', 'admin', 'member', 'readonly'), gm.joined_at
    ");
    $stmt->execute([$groupId]);

    respond(['success' => true, 'members' => $stmt->fetchAll()]);
    break;

// =====================================================================
// MY GROUPS — User's group list
// =====================================================================
case 'my_groups':
    $stmt = $pdo->prepare("
        SELECT g.group_id, g.name, g.description, g.group_type, g.created_at,
               gm.role,
               (SELECT COUNT(*) FROM comms_group_members WHERE group_id = g.group_id) AS member_count,
               (SELECT MAX(created_at) FROM comms_group_messages WHERE group_id = g.group_id) AS last_activity
        FROM comms_group_members gm
        JOIN comms_groups g ON g.group_id = gm.group_id
        WHERE gm.client_id = ?
        ORDER BY last_activity DESC
    ");
    $stmt->execute([$clientId]);

    respond(['success' => true, 'groups' => $stmt->fetchAll()]);
    break;

// =====================================================================
// GROUP DISTRIBUTE KEY — Sender Key distribution
// =====================================================================
case 'group_distribute_key':
    requirePost();
    $input = getInput();

    $groupId    = preg_replace('/[^a-f0-9]/', '', $input['group_id'] ?? '');
    $toId       = (int) ($input['to_id'] ?? 0);
    $ciphertext = $input['ciphertext'] ?? '';
    $iv         = $input['iv'] ?? '';
    $senderKeyId = preg_replace('/[^a-f0-9]/', '', $input['sender_key_id'] ?? '');

    if (!$groupId || $toId < 1 || !$ciphertext || !$iv) {
        respond(['error' => 'Invalid key distribution data'], 400);
    }

    // Store as a system message (type 3) so recipient receives it
    $convHash = conversationHash($clientId, $toId);
    $pdo->prepare("
        INSERT INTO comms_messages (conversation_hash, sender_id, recipient_id, ciphertext, iv, message_type)
        VALUES (?, ?, ?, ?, ?, 3)
    ")->execute([$convHash, $clientId, $toId, $ciphertext, $iv]);

    // Update sender key in group_members
    $pdo->prepare("
        UPDATE comms_group_members SET sender_key = ? WHERE group_id = ? AND client_id = ?
    ")->execute([$senderKeyId, $groupId, $clientId]);

    respond(['success' => true, 'csrf_token' => $_SESSION['comms_csrf']]);
    break;

// =====================================================================
// ALFRED — AI Chat in Comms
// =====================================================================
case 'alfred':
    requirePost();
    $input = getInput();

    $message = trim($input['message'] ?? '');
    $agent   = $input['agent'] ?? 'alfred';

    if (!$message || strlen($message) > 10000) {
        respond(['error' => 'Message required (max 10000 chars)'], 400);
    }

    $allowedAgents = ['alfred','nova','sage','atlas','cipher','pulse','pierre','sofia',
                      'luna','felix','maya','oscar','ivy','rex','cleo','kai'];
    if (!in_array($agent, $allowedAgents, true)) $agent = 'alfred';

    // Forward to Alfred chat API internally
    try {
        // Use internal function call instead of HTTP to avoid overhead
        require_once dirname(__DIR__) . '/includes/db-config.inc.php';

        // Load API keys
        $envFile = dirname(dirname(__DIR__)) . '/.env.php';
        if (file_exists($envFile)) require_once $envFile;

        $apiKey = defined('GROQ_API_KEY') ? GROQ_API_KEY : (getenv('GROQ_API_KEY') ?: '');

        if (!$apiKey) {
            respond(['error' => 'AI service not configured'], 503);
        }

        // Build Alfred context
        $systemPrompt = "You are {$agent}, an AI assistant for GoSiteMe — a secure communications and hosting platform. ";
        $systemPrompt .= "You are responding through the encrypted Comms app. Be helpful, concise, and security-aware. ";
        $systemPrompt .= "The user's client ID is {$clientId}. Current time: " . date('Y-m-d H:i:s T') . ".";

        if ($agent === 'cipher') {
            $systemPrompt .= " You specialize in security, encryption, and privacy. Advise on best practices.";
        } elseif ($agent === 'atlas') {
            $systemPrompt .= " You specialize in infrastructure, hosting, DNS, and server management.";
        } elseif ($agent === 'pulse') {
            $systemPrompt .= " You specialize in system monitoring, uptime, and performance metrics.";
        }

        $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_POSTFIELDS     => json_encode([
                'model'       => 'llama-3.3-70b-versatile',
                'messages'    => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $message],
                ],
                'max_tokens'  => 2048,
                'temperature' => 0.7,
            ]),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            respond(['error' => 'AI service unavailable'], 503);
        }

        $data = json_decode($response, true);
        $aiText = $data['choices'][0]['message']['content'] ?? 'I could not generate a response.';

        respond([
            'success'    => true,
            'response'   => $aiText,
            'agent'      => $agent,
            'csrf_token' => $_SESSION['comms_csrf'],
        ]);

    } catch (Exception $e) {
        error_log('comms alfred error: ' . $e->getMessage());
        respond(['error' => 'AI processing failed'], 500);
    }
    break;

// =====================================================================
// ALFRED ALERTS — Proactive notifications
// =====================================================================
case 'alfred_alerts':
    // Check for alerts relevant to this user
    $alerts = [];

    // Check SSL expiry
    try {
        $stmt = $pdo->prepare("
            SELECT domain, nextduedate FROM tblhosting
            WHERE userid = ? AND domainstatus = 'Active'
            AND nextduedate BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
            LIMIT 5
        ");
        $stmt->execute([$clientId]);
        while ($row = $stmt->fetch()) {
            $alerts[] = [
                'type' => 'billing',
                'severity' => 'warning',
                'message' => "Service for {$row['domain']} due {$row['nextduedate']}",
                'icon' => 'credit-card',
            ];
        }
    } catch (Exception $e) { /* table may not exist */ }

    // Check for unread support tickets
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM tbltickets
            WHERE userid = ? AND status IN ('Open','Answered')
        ");
        $stmt->execute([$clientId]);
        $tickets = (int) $stmt->fetchColumn();
        if ($tickets > 0) {
            $alerts[] = [
                'type' => 'support',
                'severity' => 'info',
                'message' => "{$tickets} open support ticket" . ($tickets > 1 ? 's' : ''),
                'icon' => 'ticket',
            ];
        }
    } catch (Exception $e) { /* table may not exist */ }

    respond(['success' => true, 'alerts' => $alerts]);
    break;

// =====================================================================
// REACT — Add/toggle emoji reaction
// =====================================================================
case 'react':
    requirePost();
    $input = getInput();

    $messageId = (int) ($input['message_id'] ?? 0);
    $source    = in_array($input['source'] ?? '', ['dm', 'group']) ? $input['source'] : 'dm';
    $reaction  = trim($input['reaction'] ?? '');

    if ($messageId < 1 || !$reaction || strlen($reaction) > 32) {
        respond(['error' => 'Invalid reaction'], 400);
    }

    // Toggle — if already reacted, remove it
    $stmt = $pdo->prepare("
        SELECT id FROM comms_reactions
        WHERE message_id = ? AND message_source = ? AND client_id = ? AND reaction = ?
    ");
    $stmt->execute([$messageId, $source, $clientId, $reaction]);
    $existing = $stmt->fetch();

    if ($existing) {
        $pdo->prepare("DELETE FROM comms_reactions WHERE id = ?")->execute([$existing['id']]);
        respond(['success' => true, 'action' => 'removed', 'csrf_token' => $_SESSION['comms_csrf']]);
    } else {
        $pdo->prepare("
            INSERT INTO comms_reactions (message_id, message_source, client_id, reaction) VALUES (?, ?, ?, ?)
        ")->execute([$messageId, $source, $clientId, $reaction]);
        respond(['success' => true, 'action' => 'added', 'csrf_token' => $_SESSION['comms_csrf']]);
    }
    break;

// =====================================================================
// REACTIONS — Get reactions for a message
// =====================================================================
case 'reactions':
    $messageId = (int) ($_GET['message_id'] ?? 0);
    $source = in_array($_GET['source'] ?? '', ['dm', 'group']) ? $_GET['source'] : 'dm';

    if ($messageId < 1) respond(['error' => 'Invalid message'], 400);

    $stmt = $pdo->prepare("
        SELECT r.reaction, r.client_id, cl.firstname, cl.lastname
        FROM comms_reactions r
        JOIN clients cl ON cl.id = r.client_id
        WHERE r.message_id = ? AND r.message_source = ?
    ");
    $stmt->execute([$messageId, $source]);

    respond(['success' => true, 'reactions' => $stmt->fetchAll()]);
    break;

// =====================================================================
// TYPING — Send typing indicator
// =====================================================================
case 'typing':
    requirePost();
    $input = getInput();

    $targetType = in_array($input['target_type'] ?? '', ['dm', 'group']) ? $input['target_type'] : 'dm';
    $targetId   = preg_replace('/[^a-zA-Z0-9_\-]/', '', $input['target_id'] ?? '');

    if (!$targetId) respond(['error' => 'Invalid target'], 400);

    $pdo->prepare("
        INSERT INTO comms_typing (client_id, target_type, target_id, updated_at) VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE updated_at = NOW()
    ")->execute([$clientId, $targetType, $targetId]);

    respond(['success' => true]);
    break;

// =====================================================================
// TYPING STATUS — Poll who's typing
// =====================================================================
case 'typing_status':
    $targetType = in_array($_GET['target_type'] ?? '', ['dm', 'group']) ? $_GET['target_type'] : 'dm';
    $targetId   = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['target_id'] ?? '');

    if (!$targetId) respond(['error' => 'Invalid target'], 400);

    $stmt = $pdo->prepare("
        SELECT t.client_id, cl.firstname
        FROM comms_typing t
        JOIN clients cl ON cl.id = t.client_id
        WHERE t.target_type = ? AND t.target_id = ? AND t.client_id != ?
          AND t.updated_at > DATE_SUB(NOW(), INTERVAL 5 SECOND)
    ");
    $stmt->execute([$targetType, $targetId, $clientId]);

    respond(['success' => true, 'typing' => $stmt->fetchAll()]);
    break;

// =====================================================================
// EDIT MESSAGE — Edit a previously sent message
// =====================================================================
case 'edit_message':
    requirePost();
    $input = getInput();

    $messageId  = (int) ($input['message_id'] ?? 0);
    $source     = in_array($input['source'] ?? '', ['dm', 'group']) ? $input['source'] : 'dm';
    $ciphertext = $input['ciphertext'] ?? '';
    $iv         = $input['iv'] ?? '';

    if ($messageId < 1 || !$ciphertext || !$iv) {
        respond(['error' => 'Invalid edit data'], 400);
    }

    if ($source === 'dm') {
        $stmt = $pdo->prepare("SELECT sender_id FROM comms_messages WHERE id = ?");
        $stmt->execute([$messageId]);
        $msg = $stmt->fetch();
        if (!$msg || (int)$msg['sender_id'] !== $clientId) {
            respond(['error' => 'Can only edit your own messages'], 403);
        }

        $pdo->prepare("
            UPDATE comms_messages SET ciphertext = ?, iv = ?, edited_at = NOW() WHERE id = ?
        ")->execute([$ciphertext, $iv, $messageId]);
    } else {
        $stmt = $pdo->prepare("SELECT sender_id FROM comms_group_messages WHERE id = ?");
        $stmt->execute([$messageId]);
        $msg = $stmt->fetch();
        if (!$msg || (int)$msg['sender_id'] !== $clientId) {
            respond(['error' => 'Can only edit your own messages'], 403);
        }

        $pdo->prepare("
            UPDATE comms_group_messages SET ciphertext = ?, iv = ?, edited_at = NOW() WHERE id = ?
        ")->execute([$ciphertext, $iv, $messageId]);
    }

    respond(['success' => true, 'csrf_token' => $_SESSION['comms_csrf']]);
    break;

// =====================================================================
// DEVICE REGISTER — Multi-device support
// =====================================================================
case 'device_register':
    requirePost();
    $input = getInput();

    $deviceId   = preg_replace('/[^a-f0-9]/', '', $input['device_id'] ?? '');
    $deviceName = substr(strip_tags(trim($input['device_name'] ?? 'Unknown')), 0, 100);
    $ecdhPub    = $input['ecdh_public'] ?? '';
    $ecdsaPub   = $input['ecdsa_public'] ?? '';

    if (!$deviceId || !$ecdhPub || !$ecdsaPub) {
        respond(['error' => 'Missing device data'], 400);
    }

    // Count existing devices (max 5)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM comms_devices WHERE client_id = ?");
    $stmt->execute([$clientId]);
    if ((int)$stmt->fetchColumn() >= 5) {
        respond(['error' => 'Maximum 5 linked devices'], 400);
    }

    // Check if first device = make primary
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM comms_devices WHERE client_id = ?");
    $stmt->execute([$clientId]);
    $isPrimary = (int)$stmt->fetchColumn() === 0 ? 1 : 0;

    $pdo->prepare("
        INSERT INTO comms_devices (client_id, device_id, device_name, ecdh_public, ecdsa_public, is_primary)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE device_name = VALUES(device_name), ecdh_public = VALUES(ecdh_public),
            ecdsa_public = VALUES(ecdsa_public), last_seen = NOW()
    ")->execute([$clientId, $deviceId, $deviceName, $ecdhPub, $ecdsaPub, $isPrimary]);

    respond(['success' => true, 'is_primary' => (bool)$isPrimary, 'csrf_token' => $_SESSION['comms_csrf']]);
    break;

// =====================================================================
// DEVICES — List linked devices
// =====================================================================
case 'devices':
    $stmt = $pdo->prepare("
        SELECT device_id, device_name, is_primary, last_seen, created_at
        FROM comms_devices WHERE client_id = ? ORDER BY is_primary DESC, last_seen DESC
    ");
    $stmt->execute([$clientId]);

    respond(['success' => true, 'devices' => $stmt->fetchAll()]);
    break;

// =====================================================================
// DEVICE REMOVE — Unlink a device
// =====================================================================
case 'device_remove':
    requirePost();
    $input = getInput();

    $deviceId = preg_replace('/[^a-f0-9]/', '', $input['device_id'] ?? '');
    if (!$deviceId) respond(['error' => 'Invalid device'], 400);

    // Can't remove primary device if others exist
    $stmt = $pdo->prepare("SELECT is_primary FROM comms_devices WHERE client_id = ? AND device_id = ?");
    $stmt->execute([$clientId, $deviceId]);
    $device = $stmt->fetch();

    if ($device && $device['is_primary']) {
        $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM comms_devices WHERE client_id = ? AND device_id != ?");
        $stmt2->execute([$clientId, $deviceId]);
        if ((int)$stmt2->fetchColumn() > 0) {
            respond(['error' => 'Transfer primary status before removing this device'], 400);
        }
    }

    $pdo->prepare("DELETE FROM comms_devices WHERE client_id = ? AND device_id = ?")->execute([$clientId, $deviceId]);
    respond(['success' => true, 'csrf_token' => $_SESSION['comms_csrf']]);
    break;

// =====================================================================
// DASHBOARD — Command Center data
// =====================================================================
case 'dashboard':
    $dashboard = [];

    // Communication stats
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM comms_messages WHERE (sender_id = ? OR recipient_id = ?)");
    $stmt->execute([$clientId, $clientId]);
    $dashboard['total_messages'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM comms_contacts WHERE client_id = ? AND blocked = 0");
    $stmt->execute([$clientId]);
    $dashboard['total_contacts'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM comms_group_members WHERE client_id = ?");
    $stmt->execute([$clientId]);
    $dashboard['total_groups'] = (int) $stmt->fetchColumn();

    // Unread messages
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM comms_messages WHERE recipient_id = ? AND delivered = 0");
    $stmt->execute([$clientId]);
    $dashboard['unread_messages'] = (int) $stmt->fetchColumn();

    // Active devices
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM comms_devices WHERE client_id = ?");
    $stmt->execute([$clientId]);
    $dashboard['active_devices'] = (int) $stmt->fetchColumn();

    // Encryption status
    $stmt = $pdo->prepare("SELECT key_fingerprint FROM comms_identity_keys WHERE client_id = ?");
    $stmt->execute([$clientId]);
    $key = $stmt->fetch();
    $dashboard['encryption_active'] = !!$key;
    $dashboard['fingerprint'] = $key['key_fingerprint'] ?? null;

    // Prekey count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM comms_prekeys WHERE client_id = ? AND used = 0");
    $stmt->execute([$clientId]);
    $dashboard['prekeys_remaining'] = (int) $stmt->fetchColumn();

    // File storage usage
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(file_size), 0) FROM comms_files WHERE uploader_id = ?");
    $stmt->execute([$clientId]);
    $dashboard['storage_used'] = (int) $stmt->fetchColumn();

    respond(['success' => true, 'dashboard' => $dashboard]);
    break;

// =====================================================================
// PUSH SUBSCRIBE — Web Push notification subscription
// =====================================================================
case 'push_subscribe':
    requirePost();
    $input = getInput();

    $subscription = $input['subscription'] ?? null;
    if (!$subscription || empty($subscription['endpoint'])) {
        respond(['error' => 'Invalid subscription'], 400);
    }

    $endpoint = filter_var($subscription['endpoint'], FILTER_VALIDATE_URL);
    if (!$endpoint) respond(['error' => 'Invalid push endpoint'], 400);

    $p256dh = $subscription['keys']['p256dh'] ?? '';
    $auth   = $subscription['keys']['auth'] ?? '';

    $pdo->prepare("
        INSERT INTO comms_notification_prefs (client_id, push_enabled, push_endpoint, push_p256dh, push_auth)
        VALUES (?, 1, ?, ?, ?)
        ON DUPLICATE KEY UPDATE push_enabled = 1, push_endpoint = VALUES(push_endpoint),
            push_p256dh = VALUES(push_p256dh), push_auth = VALUES(push_auth)
    ")->execute([$clientId, $endpoint, $p256dh, $auth]);

    respond(['success' => true, 'csrf_token' => $_SESSION['comms_csrf']]);
    break;

// =====================================================================
// PUSH KEY — Get VAPID public key
// =====================================================================
case 'push_key':
    $vapidPublic = getenv('VAPID_PUBLIC_KEY') ?: (defined('VAPID_PUBLIC_KEY') ? VAPID_PUBLIC_KEY : '');
    respond([
        'success'      => true,
        'vapid_public'  => $vapidPublic,
    ]);
    break;

// =====================================================================
// SEND with reply_to support (override v1 for DMs)
// =====================================================================
case 'send_reply':
    requirePost();
    $input = getInput();

    $recipientId      = (int) ($input['recipient_id'] ?? 0);
    $ciphertext       = $input['ciphertext']       ?? '';
    $iv               = $input['iv']               ?? '';
    $senderEphemeral  = $input['sender_ephemeral'] ?? null;
    $kyberCt          = $input['kyber_ct']         ?? null;
    $messageType      = (int) ($input['message_type'] ?? 0);
    $expiresIn        = (int) ($input['expires_in']  ?? 0);
    $replyTo          = !empty($input['reply_to']) ? (int) $input['reply_to'] : null;

    if ($recipientId < 1 || !$ciphertext || !$iv) respond(['error' => 'Missing fields'], 400);
    if (strlen($ciphertext) > MAX_MESSAGE_SIZE) respond(['error' => 'Message too large'], 413);
    if (!in_array($messageType, [0,1,2,3,4], true)) $messageType = 0;

    $convHash  = conversationHash($clientId, $recipientId);
    $expiresAt = $expiresIn > 0 ? date('Y-m-d H:i:s', time() + $expiresIn) : null;

    $stmt = $pdo->prepare("
        INSERT INTO comms_messages (conversation_hash, sender_id, recipient_id, ciphertext, iv, sender_ephemeral, kyber_ct, message_type, reply_to, expires_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$convHash, $clientId, $recipientId, $ciphertext, $iv, $senderEphemeral, $kyberCt, $messageType, $replyTo, $expiresAt]);

    $pdo->prepare("
        INSERT INTO comms_contacts (client_id, contact_id, last_message_at) VALUES (?, ?, NOW()), (?, ?, NOW())
        ON DUPLICATE KEY UPDATE last_message_at = NOW()
    ")->execute([$clientId, $recipientId, $recipientId, $clientId]);

    respond([
        'success'    => true,
        'message_id' => (int) $pdo->lastInsertId(),
        'csrf_token' => $_SESSION['comms_csrf'],
    ]);
    break;

// =====================================================================
// REGISTER PQ KEY — Store Kyber-768 public key for this client
// =====================================================================
case 'register_pq_key':
    requirePost();
    $input = getInput();
    $pqPublic = $input['pq_public'] ?? '';

    if (!$pqPublic || strlen($pqPublic) > 10000) {
        respond(['error' => 'Invalid PQ public key'], 400);
    }

    $stmt = $pdo->prepare("UPDATE comms_identity_keys SET pq_public = ? WHERE client_id = ?");
    $stmt->execute([$pqPublic, $clientId]);

    respond(['success' => true, 'csrf_token' => $_SESSION['comms_csrf']]);
    break;

// =====================================================================
// GET PQ KEY — Retrieve someone's Kyber public key
// =====================================================================
case 'get_pq_key':
    $targetId = (int) ($_GET['id'] ?? 0);
    if ($targetId < 1) respond(['error' => 'Invalid user ID'], 400);

    $stmt = $pdo->prepare("SELECT pq_public FROM comms_identity_keys WHERE client_id = ?");
    $stmt->execute([$targetId]);
    $row = $stmt->fetch();

    respond([
        'success'   => true,
        'pq_public' => $row['pq_public'] ?? null,
        'has_pq'    => !empty($row['pq_public']),
    ]);
    break;
}
