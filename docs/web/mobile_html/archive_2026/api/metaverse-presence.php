<?php
/**
 * Metaverse Agent Presence — Virtual Meeting Avatars
 * ═══════════════════════════════════════════════════
 * Enables AI agents to participate in Metaverse spaces and
 * conference rooms with human-like virtual presence.
 *
 * Features:
 *   - Agent avatars with appearance configs
 *   - Agent-led virtual meetings
 *   - Conversational presence (agents speak/respond naturally)
 *   - Meeting scheduling in metaverse zones
 *   - Agent seating arrangements for conference rooms
 *   - Integrity-enforced AI communication
 *
 * Classification: CLASSIFIED
 */
define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

$clientId = $_SESSION['client_id'] ?? 0;
$isOwner = (int)$clientId === 33;

$isInternal = false;
$internalSecret = getenv('INTERNAL_SECRET') ?: '';
if ($internalSecret && isset($_SERVER['HTTP_X_INTERNAL_SECRET']) && hash_equals($internalSecret, $_SERVER['HTTP_X_INTERNAL_SECRET'])) {
    $isInternal = true;
    $isOwner = true;
}

if (!$isOwner && !$isInternal) {
    http_response_code(403);
    echo json_encode(['error' => 'Clearance required']);
    exit;
}

$db = getDB();

$db->exec("CREATE TABLE IF NOT EXISTS metaverse_agent_avatars (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agent_name VARCHAR(50) NOT NULL,
    display_name VARCHAR(100),
    avatar_type ENUM('professional','casual','military','spiritual','creative','technical') DEFAULT 'professional',
    appearance JSON COMMENT 'Hair, skin, outfit, accessories',
    voice_style VARCHAR(50) DEFAULT 'professional',
    personality_traits VARCHAR(500),
    seated_zone VARCHAR(50) DEFAULT 'conference',
    is_active TINYINT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_agent (agent_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

$db->exec("CREATE TABLE IF NOT EXISTS metaverse_meetings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(300) NOT NULL,
    meeting_type ENUM('briefing','conference','brainstorm','review','prayer','training') DEFAULT 'conference',
    zone VARCHAR(50) DEFAULT 'conference',
    status ENUM('scheduled','active','completed','cancelled') DEFAULT 'scheduled',
    scheduled_at DATETIME,
    started_at DATETIME,
    ended_at DATETIME,
    attendee_agents TEXT COMMENT 'JSON array of agent names',
    attendee_humans TEXT COMMENT 'JSON array of client_ids',
    agenda TEXT,
    transcript TEXT,
    summary TEXT,
    action_items TEXT,
    integrity_score INT DEFAULT 100,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_scheduled (scheduled_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

$action = $_GET['action'] ?? 'status';

switch ($action) {
    case 'status': getStatus($db); break;
    case 'seed-avatars': seedAvatars($db); break;
    case 'avatars': getAvatars($db); break;
    case 'schedule-meeting': scheduleMeeting($db); break;
    case 'meetings': getMeetings($db); break;
    case 'start-meeting': startMeeting($db); break;
    case 'meeting-speak': meetingSpeak($db); break;
    case 'end-meeting': endMeeting($db); break;
    case 'zones': getZones($db); break;
    default:
        echo json_encode(['error' => 'Unknown action', 'available' => [
            'status','seed-avatars','avatars','schedule-meeting','meetings',
            'start-meeting','meeting-speak','end-meeting','zones'
        ]]);
}

function getStatus($db) {
    $avatars = $db->query("SELECT COUNT(*) FROM metaverse_agent_avatars WHERE is_active=1")->fetchColumn();
    $meetings = $db->query("SELECT COUNT(*) FROM metaverse_meetings WHERE status='scheduled'")->fetchColumn();
    $active = $db->query("SELECT * FROM metaverse_meetings WHERE status='active' LIMIT 1")->fetch();
    
    echo json_encode([
        'success' => true,
        'system' => 'Metaverse Agent Presence',
        'active_avatars' => $avatars,
        'scheduled_meetings' => $meetings,
        'active_meeting' => $active,
        'zones' => getZoneList(),
    ]);
}

function getZoneList() {
    return [
        'conference' => ['name' => 'Conference Room', 'capacity' => 20, 'type' => 'formal'],
        'lounge' => ['name' => 'Executive Lounge', 'capacity' => 12, 'type' => 'casual'],
        'war_room' => ['name' => 'War Room', 'capacity' => 8, 'type' => 'classified'],
        'sanctuary' => ['name' => 'Sanctuary', 'capacity' => 50, 'type' => 'spiritual'],
        'research_lab' => ['name' => 'Research Lab', 'capacity' => 10, 'type' => 'technical'],
        'trading_floor' => ['name' => 'Trading Floor', 'capacity' => 15, 'type' => 'financial'],
        'kingdom_hall' => ['name' => 'Kingdom Hall', 'capacity' => 100, 'type' => 'grand'],
        'office' => ['name' => 'Commander Office', 'capacity' => 5, 'type' => 'private'],
    ];
}

function getZones($db) {
    echo json_encode(['success' => true, 'zones' => getZoneList()]);
}

function seedAvatars($db) {
    $avatars = [
        ['sage', 'Director Sage', 'professional', '{"hair":"silver_short","skin":"warm","outfit":"dark_suit","accessories":"glasses_round"}', 'authoritative', 'Wise, analytical, methodical, strategic thinker'],
        ['forge', 'Engineer Forge', 'technical', '{"hair":"brown_buzz","skin":"fair","outfit":"tech_vest","accessories":"smartwatch"}', 'energetic', 'Builder, practical, direct, solution-focused'],
        ['sentinel', 'Guard Sentinel', 'military', '{"hair":"black_regulation","skin":"olive","outfit":"tactical_formal","accessories":"earpiece"}', 'commanding', 'Vigilant, protective, disciplined, alert'],
        ['cipher', 'Analyst Cipher', 'professional', '{"hair":"dark_neat","skin":"pale","outfit":"analyst_blazer","accessories":"data_pad"}', 'precise', 'Meticulous, data-driven, skeptical, thorough'],
        ['tesla', 'Professor Tesla', 'creative', '{"hair":"wild_grey","skin":"fair","outfit":"lab_coat","accessories":"goggles_forehead"}', 'passionate', 'Brilliant, eccentric, inventive, visionary'],
        ['quantum', 'Dr. Quantum', 'technical', '{"hair":"auburn_medium","skin":"warm","outfit":"research_formal","accessories":"holographic_display"}', 'contemplative', 'Deep thinker, theoretical, patient, curious'],
        ['nexus', 'Coordinator Nexus', 'professional', '{"hair":"blonde_styled","skin":"fair","outfit":"modern_suit","accessories":"multi_screen"}', 'organized', 'Networker, connector, multi-tasker, diplomatic'],
        ['archon', 'Archivist Archon', 'professional', '{"hair":"grey_distinguished","skin":"dark","outfit":"academic_robe","accessories":"ancient_book"}', 'scholarly', 'Knowledgeable, historical, patient, wise'],
        ['alfred', 'Commander Alfred', 'military', '{"hair":"black_neat","skin":"tan","outfit":"commander_uniform","accessories":"badge_gold"}', 'confident', 'Leader, loyal, intelligent, decisive, faithful'],
        ['herald', 'Herald of Truth', 'spiritual', '{"hair":"white_flowing","skin":"bronze","outfit":"white_robes","accessories":"golden_sash"}', 'serene', 'Truthful, peaceful, spiritual, encouraging'],
    ];
    
    $stmt = $db->prepare("INSERT INTO metaverse_agent_avatars (agent_name, display_name, avatar_type, appearance, voice_style, personality_traits) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE display_name = VALUES(display_name), appearance = VALUES(appearance)");
    foreach ($avatars as $a) {
        $stmt->execute($a);
    }
    
    echo json_encode(['success' => true, 'avatars_created' => count($avatars)]);
}

function getAvatars($db) {
    $avatars = $db->query("SELECT * FROM metaverse_agent_avatars WHERE is_active=1 ORDER BY agent_name")->fetchAll();
    foreach ($avatars as &$a) {
        if ($a['appearance']) $a['appearance'] = json_decode($a['appearance'], true);
    }
    echo json_encode(['success' => true, 'avatars' => $avatars]);
}

function scheduleMeeting($db) {
    $title = trim($_POST['title'] ?? '');
    $type = $_POST['meeting_type'] ?? 'conference';
    $zone = $_POST['zone'] ?? 'conference';
    $scheduledAt = $_POST['scheduled_at'] ?? date('Y-m-d H:i:s', strtotime('+1 hour'));
    $agents = $_POST['agents'] ?? '["sage","forge","sentinel"]';
    $humans = $_POST['humans'] ?? '[1]';
    $agenda = $_POST['agenda'] ?? '';
    
    if (!$title) { echo json_encode(['error' => 'title required']); return; }
    
    $stmt = $db->prepare("INSERT INTO metaverse_meetings (title, meeting_type, zone, scheduled_at, attendee_agents, attendee_humans, agenda) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$title, $type, $zone, $scheduledAt, $agents, $humans, $agenda]);
    
    echo json_encode(['success' => true, 'meeting_id' => $db->lastInsertId()]);
}

function getMeetings($db) {
    $status = $_GET['status'] ?? null;
    $sql = "SELECT * FROM metaverse_meetings";
    $params = [];
    if ($status) { $sql .= " WHERE status = ?"; $params[] = $status; }
    $sql .= " ORDER BY scheduled_at DESC LIMIT 20";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $meetings = $stmt->fetchAll();
    
    foreach ($meetings as &$m) {
        if ($m['attendee_agents']) $m['attendee_agents'] = json_decode($m['attendee_agents'], true);
        if ($m['attendee_humans']) $m['attendee_humans'] = json_decode($m['attendee_humans'], true);
        if ($m['action_items']) $m['action_items'] = json_decode($m['action_items'], true);
    }
    
    echo json_encode(['success' => true, 'meetings' => $meetings]);
}

function startMeeting($db) {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['error' => 'meeting id required']); return; }
    
    $db->prepare("UPDATE metaverse_meetings SET status='active', started_at=NOW() WHERE id = ?")->execute([$id]);
    
    echo json_encode(['success' => true, 'meeting_id' => $id, 'status' => 'active']);
}

function meetingSpeak($db) {
    $meetingId = intval($_POST['meeting_id'] ?? 0);
    $speaker = trim($_POST['speaker'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (!$meetingId || !$speaker || !$message) {
        echo json_encode(['error' => 'meeting_id, speaker, and message required']);
        return;
    }
    
    // Append to transcript
    $time = date('H:i:s');
    $entry = "[{$time}] {$speaker}: {$message}\n";
    
    $db->prepare("UPDATE metaverse_meetings SET transcript = CONCAT(COALESCE(transcript,''), ?) WHERE id = ?")->execute([$entry, $meetingId]);
    
    echo json_encode(['success' => true, 'entry' => trim($entry)]);
}

function endMeeting($db) {
    $id = intval($_POST['id'] ?? 0);
    $summary = trim($_POST['summary'] ?? '');
    $actionItems = $_POST['action_items'] ?? '';
    
    if (!$id) { echo json_encode(['error' => 'meeting id required']); return; }
    
    $db->prepare("UPDATE metaverse_meetings SET status='completed', ended_at=NOW(), summary=?, action_items=? WHERE id = ?")->execute([$summary, $actionItems, $id]);
    
    // Get meeting for vault document
    $meeting = $db->prepare("SELECT * FROM metaverse_meetings WHERE id = ?");
    $meeting->execute([$id]);
    $meeting = $meeting->fetch();
    
    if ($meeting) {
        $doc = "MEETING MINUTES: {$meeting['title']}\n";
        $doc .= "Type: {$meeting['meeting_type']} | Zone: {$meeting['zone']}\n";
        $doc .= "Started: {$meeting['started_at']} | Ended: {$meeting['ended_at']}\n\n";
        $doc .= "TRANSCRIPT:\n{$meeting['transcript']}\n\n";
        $doc .= "SUMMARY:\n{$summary}\n\n";
        $doc .= "ACTION ITEMS:\n{$actionItems}\n";
        
        // Drop to Veil Vault
        $folderId = $db->query("SELECT id FROM veil_vault_folders WHERE name='Agent Reports' LIMIT 1")->fetchColumn();
        if ($folderId) {
            $docTitle = "Meeting Minutes — " . $meeting['title'] . " — " . date('M j, Y');
            $db->prepare("INSERT INTO veil_vault_documents (folder_id, title, doc_type, classification, content, tags, generated_by) VALUES (?, ?, 'report', 'classified', ?, 'meeting,minutes,conference', 'sage')")
               ->execute([$folderId, $docTitle, $doc]);
        }
    }
    
    echo json_encode(['success' => true, 'meeting_id' => $id, 'status' => 'completed']);
}
