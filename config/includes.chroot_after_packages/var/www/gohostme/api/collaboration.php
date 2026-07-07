<?php
/**
 * Collaboration & Conferencing API
 * Shared documents, whiteboards, sessions, conferencing, screen sharing
 * Extends existing collab_sessions, collab_participants, collab_invites tables
 */
define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Internal-Secret');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

function collabIsInternal(): bool {
    $secret = defined('INTERNAL_SECRET') ? INTERNAL_SECRET : '';
    return $secret && isset($_SERVER['HTTP_X_INTERNAL_SECRET']) && hash_equals($secret, $_SERVER['HTTP_X_INTERNAL_SECRET']);
}
function collabRequireAuth(): void {
    if (collabIsInternal()) return;
    if (empty($_SESSION['logged_in']) || empty($_SESSION['client_id'])) jsonResponse(['error' => 'Authentication required'], 401);
require_once dirname(__DIR__) . '/includes/api-security.php';
}
function collabGetClientId(): int {
    if (collabIsInternal()) {
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        return (int) ($body['client_id'] ?? $_SESSION['client_id'] ?? 0);
    }
    return (int) ($_SESSION['client_id'] ?? 0);
}

// ─── Extended Schema ──────────────────────────────────────────
function ensureCollabSchema(): void {
    $db = getDB();
    try {

    // Existing tables: collab_sessions, collab_participants, collab_invites
    // We add: documents, whiteboards, conference rooms, chat, recordings

    // Add type/description to existing sessions if missing
    try {
        $db->exec("ALTER TABLE collab_sessions ADD COLUMN IF NOT EXISTS session_type ENUM('document','whiteboard','conference','general') DEFAULT 'general'");
        $db->exec("ALTER TABLE collab_sessions ADD COLUMN IF NOT EXISTS description TEXT AFTER name");
        $db->exec("ALTER TABLE collab_sessions ADD COLUMN IF NOT EXISTS max_participants INT DEFAULT 50 AFTER description");
    } catch (\Throwable $e) { /* columns may already exist */ }

    // Shared Documents
    $db->exec("CREATE TABLE IF NOT EXISTS collab_documents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        session_code VARCHAR(16) NOT NULL,
        client_id INT NOT NULL,
        title VARCHAR(200) NOT NULL,
        content LONGTEXT,
        doc_type ENUM('text','markdown','code','spreadsheet') DEFAULT 'text',
        version INT DEFAULT 1,
        locked_by INT DEFAULT NULL,
        locked_at DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_session (session_code),
        INDEX idx_client (client_id)
    )");

    // Document revision history
    $db->exec("CREATE TABLE IF NOT EXISTS collab_doc_revisions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        document_id INT NOT NULL,
        client_id INT NOT NULL,
        version INT NOT NULL,
        content LONGTEXT,
        change_summary VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_doc (document_id)
    )");

    // Whiteboards
    $db->exec("CREATE TABLE IF NOT EXISTS collab_whiteboards (
        id INT AUTO_INCREMENT PRIMARY KEY,
        session_code VARCHAR(16) NOT NULL,
        client_id INT NOT NULL,
        name VARCHAR(128) NOT NULL,
        canvas_data JSON,
        width INT DEFAULT 1920,
        height INT DEFAULT 1080,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_session (session_code)
    )");

    // Conference Rooms
    $db->exec("CREATE TABLE IF NOT EXISTS collab_conferences (
        id INT AUTO_INCREMENT PRIMARY KEY,
        session_code VARCHAR(16) NOT NULL,
        client_id INT NOT NULL,
        room_name VARCHAR(128) NOT NULL,
        status ENUM('waiting','active','ended') DEFAULT 'waiting',
        started_at DATETIME,
        ended_at DATETIME,
        recording_url VARCHAR(512),
        max_duration INT DEFAULT 3600,
        features JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_session (session_code),
        INDEX idx_status (status)
    )");

    // Conference participants (tracks join/leave)
    $db->exec("CREATE TABLE IF NOT EXISTS collab_conference_participants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        conference_id INT NOT NULL,
        client_id INT NOT NULL,
        display_name VARCHAR(64),
        role ENUM('host','moderator','participant','viewer') DEFAULT 'participant',
        joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        left_at DATETIME,
        muted TINYINT(1) DEFAULT 0,
        video_on TINYINT(1) DEFAULT 1,
        screen_sharing TINYINT(1) DEFAULT 0,
        INDEX idx_conference (conference_id)
    )");

    // Session chat messages
    $db->exec("CREATE TABLE IF NOT EXISTS collab_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        session_code VARCHAR(16) NOT NULL,
        client_id INT NOT NULL,
        message TEXT NOT NULL,
        message_type ENUM('text','system','file','reaction') DEFAULT 'text',
        reply_to INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_session (session_code),
        INDEX idx_created (created_at)
    )");

    // Polls / Votes
    $db->exec("CREATE TABLE IF NOT EXISTS collab_polls (
        id INT AUTO_INCREMENT PRIMARY KEY,
        session_code VARCHAR(16) NOT NULL,
        client_id INT NOT NULL,
        question VARCHAR(255) NOT NULL,
        options JSON NOT NULL,
        anonymous TINYINT(1) DEFAULT 0,
        status ENUM('active','closed') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_session (session_code)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS collab_poll_votes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        poll_id INT NOT NULL,
        client_id INT NOT NULL,
        option_index INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_poll_client (poll_id, client_id)
    )");
    } catch (PDOException $e) {
        error_log('Collaboration schema error: ' . $e->getMessage());
    }
}
ensureCollabSchema();

$action = sanitize($_GET['action'] ?? $_POST['action'] ?? '', 50);

switch ($action) {
    // Sessions
    case 'create_session':    collabRequireAuth(); createSession(); break;
    case 'join_session':      collabRequireAuth(); joinSession(); break;
    case 'leave_session':     collabRequireAuth(); leaveSession(); break;
    case 'end_session':       collabRequireAuth(); endSession(); break;
    case 'my_sessions':       collabRequireAuth(); mySessions(); break;
    case 'session_detail':    collabRequireAuth(); sessionDetail(); break;
    case 'invite':            collabRequireAuth(); inviteToSession(); break;
    // Documents
    case 'doc_create':        collabRequireAuth(); docCreate(); break;
    case 'doc_update':        collabRequireAuth(); docUpdate(); break;
    case 'doc_get':           collabRequireAuth(); docGet(); break;
    case 'doc_list':          collabRequireAuth(); docList(); break;
    case 'doc_revisions':     collabRequireAuth(); docRevisions(); break;
    case 'doc_lock':          collabRequireAuth(); docLock(); break;
    case 'doc_unlock':        collabRequireAuth(); docUnlock(); break;
    // Whiteboards
    case 'wb_create':         collabRequireAuth(); wbCreate(); break;
    case 'wb_update':         collabRequireAuth(); wbUpdate(); break;
    case 'wb_get':            collabRequireAuth(); wbGet(); break;
    // Conferencing
    case 'conf_create':       collabRequireAuth(); confCreate(); break;
    case 'conf_join':         collabRequireAuth(); confJoin(); break;
    case 'conf_leave':        collabRequireAuth(); confLeave(); break;
    case 'conf_end':          collabRequireAuth(); confEnd(); break;
    case 'conf_toggle':       collabRequireAuth(); confToggle(); break;
    case 'conf_status':       collabRequireAuth(); confStatus(); break;
    // Chat
    case 'chat_send':         collabRequireAuth(); chatSend(); break;
    case 'chat_history':      collabRequireAuth(); chatHistory(); break;
    // Polls
    case 'poll_create':       collabRequireAuth(); pollCreate(); break;
    case 'poll_vote':         collabRequireAuth(); pollVote(); break;
    case 'poll_results':      collabRequireAuth(); pollResults(); break;
    default: jsonResponse(['error' => 'Unknown action', 'actions' => [
        'create_session','join_session','leave_session','end_session','my_sessions','session_detail','invite',
        'doc_create','doc_update','doc_get','doc_list','doc_revisions','doc_lock','doc_unlock',
        'wb_create','wb_update','wb_get',
        'conf_create','conf_join','conf_leave','conf_end','conf_toggle','conf_status',
        'chat_send','chat_history','poll_create','poll_vote','poll_results'
    ]], 400);
}

// ─── Helper: generate session code ────────────────────────────
function generateSessionCode(): string {
    return strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
}

// ─── Helper: verify session access ───────────────────────────
function verifySessionAccess(PDO $db, string $code, int $clientId): array {
    $stmt = $db->prepare("SELECT * FROM collab_sessions WHERE session_code = ?");
    $stmt->execute([$code]);
    $session = $stmt->fetch();
    if (!$session) jsonResponse(['error' => 'Session not found'], 404);

    // Owner or participant
    if ((int) $session['client_id'] !== $clientId) {
        $stmt = $db->prepare("SELECT id FROM collab_participants WHERE session_code = ? AND client_id = ?");
        $stmt->execute([$code, $clientId]);
        if (!$stmt->fetch()) jsonResponse(['error' => 'Not a participant'], 403);
    }
    return $session;
}

// ═══════════════════════════════════════════════════════════════
// Sessions
// ═══════════════════════════════════════════════════════════════
function createSession(): void {
    $db = getDB();
    $clientId = collabGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $name = sanitize($input['name'] ?? 'Untitled Session', 128);
    $code = generateSessionCode();
    $type = in_array($input['session_type'] ?? '', ['document','whiteboard','conference','general']) ? $input['session_type'] : 'general';

    $stmt = $db->prepare("INSERT INTO collab_sessions (session_code, client_id, name, status) VALUES (?, ?, ?, 'live')");
    $stmt->execute([$code, $clientId, $name]);

    try {
        $db->prepare("UPDATE collab_sessions SET session_type = ?, description = ? WHERE session_code = ?")
           ->execute([$type, sanitize($input['description'] ?? '', 500), $code]);
    } catch (\Throwable $e) { /* extended columns may not exist */ }

    // Auto-join creator
    $db->prepare("INSERT INTO collab_participants (session_code, client_id) VALUES (?, ?)")->execute([$code, $clientId]);

    jsonResponse(['success' => true, 'session_code' => $code, 'name' => $name, 'type' => $type]);
}

function joinSession(): void {
    $db = getDB();
    $clientId = collabGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $code = sanitize($input['session_code'] ?? '', 16);

    $stmt = $db->prepare("SELECT * FROM collab_sessions WHERE session_code = ? AND status = 'live'");
    $stmt->execute([$code]);
    if (!$stmt->fetch()) jsonResponse(['error' => 'Session not found or ended'], 404);

    $db->prepare("INSERT IGNORE INTO collab_participants (session_code, client_id) VALUES (?, ?)")->execute([$code, $clientId]);
    jsonResponse(['success' => true, 'message' => 'Joined session']);
}

function leaveSession(): void {
    $db = getDB();
    $clientId = collabGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $code = sanitize($input['session_code'] ?? '', 16);

    $db->prepare("DELETE FROM collab_participants WHERE session_code = ? AND client_id = ?")->execute([$code, $clientId]);
    jsonResponse(['success' => true, 'message' => 'Left session']);
}

function endSession(): void {
    $db = getDB();
    $clientId = collabGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $code = sanitize($input['session_code'] ?? '', 16);

    // Only owner can end
    $stmt = $db->prepare("UPDATE collab_sessions SET status = 'ended' WHERE session_code = ? AND client_id = ?");
    $stmt->execute([$code, $clientId]);
    if ($stmt->rowCount() === 0) jsonResponse(['error' => 'Not authorized or not found'], 403);

    jsonResponse(['success' => true, 'message' => 'Session ended']);
}

function mySessions(): void {
    $db = getDB();
    $clientId = collabGetClientId();

    // Sessions I own
    $stmt = $db->prepare("SELECT s.*, (SELECT COUNT(*) FROM collab_participants p WHERE p.session_code = s.session_code) as participants FROM collab_sessions s WHERE s.client_id = ? ORDER BY s.created_at DESC LIMIT 50");
    $stmt->execute([$clientId]);
    $owned = $stmt->fetchAll();

    // Sessions I participate in
    $stmt = $db->prepare("SELECT s.*, (SELECT COUNT(*) FROM collab_participants p WHERE p.session_code = s.session_code) as participants FROM collab_sessions s JOIN collab_participants cp ON s.session_code = cp.session_code WHERE cp.client_id = ? AND s.client_id != ? ORDER BY cp.joined_at DESC LIMIT 50");
    $stmt->execute([$clientId, $clientId]);
    $joined = $stmt->fetchAll();

    jsonResponse(['success' => true, 'owned' => $owned, 'joined' => $joined]);
}

function sessionDetail(): void {
    $db = getDB();
    $clientId = collabGetClientId();
    $code = sanitize($_GET['session_code'] ?? '', 16);

    $session = verifySessionAccess($db, $code, $clientId);

    // Participants
    $stmt = $db->prepare("SELECT client_id, joined_at FROM collab_participants WHERE session_code = ?");
    $stmt->execute([$code]);
    $session['participants'] = $stmt->fetchAll();

    // Documents count
    $stmt = $db->prepare("SELECT COUNT(*) FROM collab_documents WHERE session_code = ?");
    $stmt->execute([$code]);
    $session['document_count'] = (int) $stmt->fetchColumn();

    // Active conference
    $stmt = $db->prepare("SELECT id, room_name, status, started_at FROM collab_conferences WHERE session_code = ? AND status IN ('waiting','active') LIMIT 1");
    $stmt->execute([$code]);
    $session['active_conference'] = $stmt->fetch() ?: null;

    jsonResponse(['success' => true, 'session' => $session]);
}

function inviteToSession(): void {
    $db = getDB();
    $clientId = collabGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $code = sanitize($input['session_code'] ?? '', 16);
    $email = sanitize($input['email'] ?? '', 255);

    if (!$code || !$email) jsonResponse(['error' => 'session_code and email required'], 400);

    // Verify ownership
    $stmt = $db->prepare("SELECT id FROM collab_sessions WHERE session_code = ? AND client_id = ?");
    $stmt->execute([$code, $clientId]);
    if (!$stmt->fetch()) jsonResponse(['error' => 'Not session owner'], 403);

    $db->prepare("INSERT INTO collab_invites (session_code, inviter_id, email) VALUES (?, ?, ?)")->execute([$code, $clientId, $email]);
    jsonResponse(['success' => true, 'message' => "Invitation sent to $email"]);
}

// ═══════════════════════════════════════════════════════════════
// Documents
// ═══════════════════════════════════════════════════════════════
function docCreate(): void {
    $db = getDB();
    $clientId = collabGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $code = sanitize($input['session_code'] ?? '', 16);

    verifySessionAccess($db, $code, $clientId);

    $title = sanitize($input['title'] ?? 'Untitled', 200);
    $type = in_array($input['doc_type'] ?? '', ['text','markdown','code','spreadsheet']) ? $input['doc_type'] : 'text';

    $stmt = $db->prepare("INSERT INTO collab_documents (session_code, client_id, title, content, doc_type) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$code, $clientId, $title, $input['content'] ?? '', $type]);

    jsonResponse(['success' => true, 'id' => (int) $db->lastInsertId()]);
}

function docUpdate(): void {
    $db = getDB();
    $clientId = collabGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $docId = (int) ($input['document_id'] ?? 0);
    if (!$docId) jsonResponse(['error' => 'document_id required'], 400);

    $stmt = $db->prepare("SELECT * FROM collab_documents WHERE id = ?");
    $stmt->execute([$docId]);
    $doc = $stmt->fetch();
    if (!$doc) jsonResponse(['error' => 'Document not found'], 404);

    verifySessionAccess($db, $doc['session_code'], $clientId);

    // Check lock
    if ($doc['locked_by'] && (int) $doc['locked_by'] !== $clientId) {
        jsonResponse(['error' => 'Document locked by another user'], 423);
    }

    // Save revision
    $db->prepare("INSERT INTO collab_doc_revisions (document_id, client_id, version, content, change_summary) VALUES (?, ?, ?, ?, ?)")
        ->execute([$docId, $clientId, $doc['version'], $doc['content'], sanitize($input['change_summary'] ?? '', 255)]);

    // Update document
    $newContent = $input['content'] ?? $doc['content'];
    $newTitle = isset($input['title']) ? sanitize($input['title'], 200) : $doc['title'];
    $db->prepare("UPDATE collab_documents SET content = ?, title = ?, version = version + 1 WHERE id = ?")
        ->execute([$newContent, $newTitle, $docId]);

    jsonResponse(['success' => true, 'version' => $doc['version'] + 1]);
}

function docGet(): void {
    $db = getDB();
    $clientId = collabGetClientId();
    $docId = (int) ($_GET['document_id'] ?? 0);
    if (!$docId) jsonResponse(['error' => 'document_id required'], 400);

    $stmt = $db->prepare("SELECT * FROM collab_documents WHERE id = ?");
    $stmt->execute([$docId]);
    $doc = $stmt->fetch();
    if (!$doc) jsonResponse(['error' => 'Not found'], 404);

    verifySessionAccess($db, $doc['session_code'], $clientId);
    jsonResponse(['success' => true, 'document' => $doc]);
}

function docList(): void {
    $db = getDB();
    $clientId = collabGetClientId();
    $code = sanitize($_GET['session_code'] ?? '', 16);
    if (!$code) jsonResponse(['error' => 'session_code required'], 400);

    verifySessionAccess($db, $code, $clientId);

    $stmt = $db->prepare("SELECT id, title, doc_type, version, client_id, locked_by, created_at, updated_at FROM collab_documents WHERE session_code = ? ORDER BY updated_at DESC");
    $stmt->execute([$code]);
    jsonResponse(['success' => true, 'documents' => $stmt->fetchAll()]);
}

function docRevisions(): void {
    $db = getDB();
    $clientId = collabGetClientId();
    $docId = (int) ($_GET['document_id'] ?? 0);
    if (!$docId) jsonResponse(['error' => 'document_id required'], 400);

    $stmt = $db->prepare("SELECT d.session_code FROM collab_documents d WHERE d.id = ?");
    $stmt->execute([$docId]);
    $doc = $stmt->fetch();
    if (!$doc) jsonResponse(['error' => 'Not found'], 404);
    verifySessionAccess($db, $doc['session_code'], $clientId);

    $stmt = $db->prepare("SELECT id, client_id, version, change_summary, created_at FROM collab_doc_revisions WHERE document_id = ? ORDER BY version DESC LIMIT 50");
    $stmt->execute([$docId]);
    jsonResponse(['success' => true, 'revisions' => $stmt->fetchAll()]);
}

function docLock(): void {
    $db = getDB();
    $clientId = collabGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $docId = (int) ($input['document_id'] ?? 0);
    if (!$docId) jsonResponse(['error' => 'document_id required'], 400);

    $stmt = $db->prepare("SELECT session_code, locked_by FROM collab_documents WHERE id = ?");
    $stmt->execute([$docId]);
    $doc = $stmt->fetch();
    if (!$doc) jsonResponse(['error' => 'Not found'], 404);
    verifySessionAccess($db, $doc['session_code'], $clientId);

    if ($doc['locked_by'] && (int)$doc['locked_by'] !== $clientId) {
        jsonResponse(['error' => 'Already locked'], 423);
    }

    $db->prepare("UPDATE collab_documents SET locked_by = ?, locked_at = NOW() WHERE id = ?")->execute([$clientId, $docId]);
    jsonResponse(['success' => true, 'message' => 'Document locked']);
}

function docUnlock(): void {
    $db = getDB();
    $clientId = collabGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $docId = (int) ($input['document_id'] ?? 0);
    if (!$docId) jsonResponse(['error' => 'document_id required'], 400);

    $db->prepare("UPDATE collab_documents SET locked_by = NULL, locked_at = NULL WHERE id = ? AND locked_by = ?")->execute([$docId, $clientId]);
    jsonResponse(['success' => true, 'message' => 'Document unlocked']);
}

// ═══════════════════════════════════════════════════════════════
// Whiteboards
// ═══════════════════════════════════════════════════════════════
function wbCreate(): void {
    $db = getDB();
    $clientId = collabGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $code = sanitize($input['session_code'] ?? '', 16);

    verifySessionAccess($db, $code, $clientId);

    $name = sanitize($input['name'] ?? 'Whiteboard', 128);
    $stmt = $db->prepare("INSERT INTO collab_whiteboards (session_code, client_id, name, canvas_data) VALUES (?, ?, ?, ?)");
    $stmt->execute([$code, $clientId, $name, json_encode($input['canvas_data'] ?? ['objects' => []])]);

    jsonResponse(['success' => true, 'id' => (int) $db->lastInsertId()]);
}

function wbUpdate(): void {
    $db = getDB();
    $clientId = collabGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $wbId = (int) ($input['whiteboard_id'] ?? 0);
    if (!$wbId) jsonResponse(['error' => 'whiteboard_id required'], 400);

    $stmt = $db->prepare("SELECT session_code FROM collab_whiteboards WHERE id = ?");
    $stmt->execute([$wbId]);
    $wb = $stmt->fetch();
    if (!$wb) jsonResponse(['error' => 'Not found'], 404);
    verifySessionAccess($db, $wb['session_code'], $clientId);

    $db->prepare("UPDATE collab_whiteboards SET canvas_data = ?, name = COALESCE(?, name) WHERE id = ?")
        ->execute([json_encode($input['canvas_data'] ?? []), $input['name'] ?? null, $wbId]);

    jsonResponse(['success' => true]);
}

function wbGet(): void {
    $db = getDB();
    $clientId = collabGetClientId();
    $wbId = (int) ($_GET['whiteboard_id'] ?? 0);
    if (!$wbId) jsonResponse(['error' => 'whiteboard_id required'], 400);

    $stmt = $db->prepare("SELECT * FROM collab_whiteboards WHERE id = ?");
    $stmt->execute([$wbId]);
    $wb = $stmt->fetch();
    if (!$wb) jsonResponse(['error' => 'Not found'], 404);
    verifySessionAccess($db, $wb['session_code'], $clientId);

    jsonResponse(['success' => true, 'whiteboard' => $wb]);
}

// ═══════════════════════════════════════════════════════════════
// Conferencing
// ═══════════════════════════════════════════════════════════════
function confCreate(): void {
    $db = getDB();
    $clientId = collabGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $code = sanitize($input['session_code'] ?? '', 16);

    verifySessionAccess($db, $code, $clientId);

    $roomName = sanitize($input['room_name'] ?? 'Conference', 128);
    $features = json_encode($input['features'] ?? ['audio' => true, 'video' => true, 'screen_share' => true, 'chat' => true, 'recording' => false]);

    $stmt = $db->prepare("INSERT INTO collab_conferences (session_code, client_id, room_name, features) VALUES (?, ?, ?, ?)");
    $stmt->execute([$code, $clientId, $roomName, $features]);
    $confId = (int) $db->lastInsertId();

    // Host auto-joins
    $db->prepare("INSERT INTO collab_conference_participants (conference_id, client_id, role) VALUES (?, ?, 'host')")
        ->execute([$confId, $clientId]);

    jsonResponse(['success' => true, 'conference_id' => $confId, 'room_name' => $roomName]);
}

function confJoin(): void {
    $db = getDB();
    $clientId = collabGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $confId = (int) ($input['conference_id'] ?? 0);
    if (!$confId) jsonResponse(['error' => 'conference_id required'], 400);

    $stmt = $db->prepare("SELECT * FROM collab_conferences WHERE id = ? AND status IN ('waiting','active')");
    $stmt->execute([$confId]);
    $conf = $stmt->fetch();
    if (!$conf) jsonResponse(['error' => 'Conference not found or ended'], 404);

    $displayName = sanitize($input['display_name'] ?? '', 64);
    $db->prepare("INSERT IGNORE INTO collab_conference_participants (conference_id, client_id, display_name, role) VALUES (?, ?, ?, 'participant')")
        ->execute([$confId, $clientId, $displayName]);

    // If first join, activate conference
    if ($conf['status'] === 'waiting') {
        $db->prepare("UPDATE collab_conferences SET status = 'active', started_at = NOW() WHERE id = ?")->execute([$confId]);
    }

    jsonResponse(['success' => true, 'message' => 'Joined conference']);
}

function confLeave(): void {
    $db = getDB();
    $clientId = collabGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $confId = (int) ($input['conference_id'] ?? 0);

    $db->prepare("UPDATE collab_conference_participants SET left_at = NOW() WHERE conference_id = ? AND client_id = ? AND left_at IS NULL")
        ->execute([$confId, $clientId]);

    jsonResponse(['success' => true, 'message' => 'Left conference']);
}

function confEnd(): void {
    $db = getDB();
    $clientId = collabGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $confId = (int) ($input['conference_id'] ?? 0);

    $stmt = $db->prepare("UPDATE collab_conferences SET status = 'ended', ended_at = NOW() WHERE id = ? AND client_id = ?");
    $stmt->execute([$confId, $clientId]);
    if ($stmt->rowCount() === 0) jsonResponse(['error' => 'Not authorized'], 403);

    // End all participants
    $db->prepare("UPDATE collab_conference_participants SET left_at = NOW() WHERE conference_id = ? AND left_at IS NULL")->execute([$confId]);

    jsonResponse(['success' => true, 'message' => 'Conference ended']);
}

function confToggle(): void {
    $db = getDB();
    $clientId = collabGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $confId = (int) ($input['conference_id'] ?? 0);
    $field = in_array($input['field'] ?? '', ['muted', 'video_on', 'screen_sharing']) ? $input['field'] : null;
    if (!$confId || !$field) jsonResponse(['error' => 'conference_id and field required'], 400);

    $db->prepare("UPDATE collab_conference_participants SET $field = NOT $field WHERE conference_id = ? AND client_id = ? AND left_at IS NULL")
        ->execute([$confId, $clientId]);

    jsonResponse(['success' => true, 'toggled' => $field]);
}

function confStatus(): void {
    $db = getDB();
    $confId = (int) ($_GET['conference_id'] ?? 0);
    if (!$confId) jsonResponse(['error' => 'conference_id required'], 400);

    $stmt = $db->prepare("SELECT * FROM collab_conferences WHERE id = ?");
    $stmt->execute([$confId]);
    $conf = $stmt->fetch();
    if (!$conf) jsonResponse(['error' => 'Not found'], 404);

    $stmt = $db->prepare("SELECT client_id, display_name, role, muted, video_on, screen_sharing, joined_at, left_at FROM collab_conference_participants WHERE conference_id = ? ORDER BY joined_at");
    $stmt->execute([$confId]);
    $conf['participants'] = $stmt->fetchAll();
    $conf['active_count'] = count(array_filter($conf['participants'], fn($p) => $p['left_at'] === null));

    jsonResponse(['success' => true, 'conference' => $conf]);
}

// ═══════════════════════════════════════════════════════════════
// Chat
// ═══════════════════════════════════════════════════════════════
function chatSend(): void {
    $db = getDB();
    $clientId = collabGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $code = sanitize($input['session_code'] ?? '', 16);
    $message = sanitize($input['message'] ?? '', 5000);

    if (!$code || !$message) jsonResponse(['error' => 'session_code and message required'], 400);
    verifySessionAccess($db, $code, $clientId);

    $type = in_array($input['message_type'] ?? '', ['text','file','reaction']) ? $input['message_type'] : 'text';
    $replyTo = (int) ($input['reply_to'] ?? 0) ?: null;

    $stmt = $db->prepare("INSERT INTO collab_messages (session_code, client_id, message, message_type, reply_to) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$code, $clientId, $message, $type, $replyTo]);

    jsonResponse(['success' => true, 'id' => (int) $db->lastInsertId()]);
}

function chatHistory(): void {
    $db = getDB();
    $clientId = collabGetClientId();
    $code = sanitize($_GET['session_code'] ?? '', 16);
    $limit = min(100, max(10, (int) ($_GET['limit'] ?? 50)));
    $before = (int) ($_GET['before'] ?? 0);

    verifySessionAccess($db, $code, $clientId);

    $where = "session_code = ?";
    $params = [$code];
    if ($before) { $where .= " AND id < ?"; $params[] = $before; }
    $params[] = $limit;

    $stmt = $db->prepare("SELECT * FROM collab_messages WHERE $where ORDER BY created_at DESC LIMIT ?");
    dbExecute($stmt, $params);

    jsonResponse(['success' => true, 'messages' => array_reverse($stmt->fetchAll())]);
}

// ═══════════════════════════════════════════════════════════════
// Polls
// ═══════════════════════════════════════════════════════════════
function pollCreate(): void {
    $db = getDB();
    $clientId = collabGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $code = sanitize($input['session_code'] ?? '', 16);

    verifySessionAccess($db, $code, $clientId);

    $question = sanitize($input['question'] ?? '', 255);
    $options = $input['options'] ?? [];
    if (!$question || count($options) < 2) jsonResponse(['error' => 'question and at least 2 options required'], 400);

    $stmt = $db->prepare("INSERT INTO collab_polls (session_code, client_id, question, options, anonymous) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$code, $clientId, $question, json_encode(array_map(fn($o) => sanitize($o, 100), $options)), (int) ($input['anonymous'] ?? 0)]);

    jsonResponse(['success' => true, 'poll_id' => (int) $db->lastInsertId()]);
}

function pollVote(): void {
    $db = getDB();
    $clientId = collabGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $pollId = (int) ($input['poll_id'] ?? 0);
    $optionIndex = (int) ($input['option_index'] ?? -1);

    if (!$pollId || $optionIndex < 0) jsonResponse(['error' => 'poll_id and option_index required'], 400);

    $stmt = $db->prepare("SELECT * FROM collab_polls WHERE id = ? AND status = 'active'");
    $stmt->execute([$pollId]);
    $poll = $stmt->fetch();
    if (!$poll) jsonResponse(['error' => 'Poll not found or closed'], 404);

    $options = json_decode($poll['options'], true);
    if ($optionIndex >= count($options)) jsonResponse(['error' => 'Invalid option'], 400);

    $db->prepare("INSERT INTO collab_poll_votes (poll_id, client_id, option_index) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE option_index = VALUES(option_index)")
        ->execute([$pollId, $clientId, $optionIndex]);

    jsonResponse(['success' => true, 'voted_for' => $options[$optionIndex]]);
}

function pollResults(): void {
    $db = getDB();
    $pollId = (int) ($_GET['poll_id'] ?? 0);
    if (!$pollId) jsonResponse(['error' => 'poll_id required'], 400);

    $stmt = $db->prepare("SELECT * FROM collab_polls WHERE id = ?");
    $stmt->execute([$pollId]);
    $poll = $stmt->fetch();
    if (!$poll) jsonResponse(['error' => 'Not found'], 404);

    $options = json_decode($poll['options'], true);
    $stmt = $db->prepare("SELECT option_index, COUNT(*) as votes FROM collab_poll_votes WHERE poll_id = ? GROUP BY option_index");
    $stmt->execute([$pollId]);
    $voteMap = [];
    foreach ($stmt->fetchAll() as $v) $voteMap[$v['option_index']] = (int) $v['votes'];

    $totalVotes = array_sum($voteMap);
    $results = [];
    foreach ($options as $i => $opt) {
        $count = $voteMap[$i] ?? 0;
        $results[] = ['option' => $opt, 'votes' => $count, 'percentage' => $totalVotes > 0 ? round($count / $totalVotes * 100, 1) : 0];
    }

    jsonResponse(['success' => true, 'question' => $poll['question'], 'results' => $results, 'total_votes' => $totalVotes]);
}
