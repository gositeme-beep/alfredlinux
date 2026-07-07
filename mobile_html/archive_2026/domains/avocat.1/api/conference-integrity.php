<?php
/**
 * Conference Integrity Guard — Truth Enforcement System
 * ═══════════════════════════════════════════════════════
 * Ensures all conference room interactions use:
 *   - Real data (no fabricated statistics)
 *   - Real opinions (no sycophantic agreement)
 *   - Verified sources (citation required for claims)
 *   - Truth-first responses (even uncomfortable truths)
 *
 * Features:
 *   - Fact-check layer for AI participants
 *   - Source verification for claims and data
 *   - Anti-hallucination directives for all AI agents
 *   - Meeting integrity scoring
 *   - Truth audit trail in Veil Vault
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
    echo json_encode(['error' => 'Commander clearance required']);
    exit;
}

$db = getDB();

$db->exec("CREATE TABLE IF NOT EXISTS conference_integrity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_code VARCHAR(50) NOT NULL,
    agent_name VARCHAR(50),
    claim_type ENUM('statistic','opinion','fact','prediction','recommendation') DEFAULT 'fact',
    claim_text TEXT NOT NULL,
    source VARCHAR(500),
    verified TINYINT DEFAULT 0,
    confidence INT DEFAULT 0,
    flagged_suspicious TINYINT DEFAULT 0,
    flag_reason VARCHAR(500),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_room (room_code),
    INDEX idx_agent (agent_name),
    INDEX idx_verified (verified)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

$db->exec("CREATE TABLE IF NOT EXISTS conference_integrity_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(200) NOT NULL,
    rule_text TEXT NOT NULL,
    severity ENUM('critical','high','medium','low') DEFAULT 'high',
    is_active TINYINT DEFAULT 1,
    UNIQUE KEY uniq_rule (rule_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

$action = $_GET['action'] ?? 'rules';

switch ($action) {
    case 'rules': getRules($db); break;
    case 'seed': seedRules($db); break;
    case 'directives': getAgentDirectives($db); break;
    case 'log-claim': logClaim($db); break;
    case 'verify': verifyClaim($db); break;
    case 'room-score': getRoomScore($db); break;
    case 'audit': getAuditTrail($db); break;
    default:
        echo json_encode(['error' => 'Unknown action', 'available' => ['rules','seed','directives','log-claim','verify','room-score','audit']]);
}

function seedRules($db) {
    $rules = [
        ['No Fabricated Statistics', 'Every statistic cited in a conference MUST come from a verifiable data source. No making up numbers. If exact data is unavailable, state "estimated" or "approximate" explicitly.', 'critical'],
        ['No Sycophantic Agreement', 'AI agents must provide honest analysis even when it contradicts the Commander\'s hypothesis. Disagreement with data is expected and valued. Yes-men are not tolerated.', 'critical'],
        ['Source Citation Required', 'Any claim about external facts (market data, scientific findings, legal matters) must include a source reference. Claims without sources must be prefixed with "UNVERIFIED:" marker.', 'critical'],
        ['Real-Time Data Only', 'When discussing market data, system metrics, or performance numbers, agents must pull from live APIs — never from training data or cached assumptions.', 'high'],
        ['Distinguish Opinion from Fact', 'All AI agents must clearly label their responses as FACT (data-backed), OPINION (reasoned but not proven), or SPECULATION (theoretical). Mixing these without labels is prohibited.', 'high'],
        ['Uncomfortable Truth Priority', 'If an agent discovers information that is negative but true (e.g., a system is failing, a investment is losing), it must report it immediately. Hiding bad news is a critical violation.', 'critical'],
        ['No Hallucinated Capabilities', 'Agents must not claim the system can do things it cannot actually do. If a feature doesn\'t exist yet, say so. No pretending.', 'high'],
        ['Data Freshness Declaration', 'When presenting data, agents must state when the data was last updated. Stale data (>24h) must be flagged.', 'medium'],
        ['Conflict of Interest Disclosure', 'If an agent\'s recommendation benefits another system it manages (e.g., recommending more tasks for itself), it must disclose this potential bias.', 'medium'],
        ['Error Admission Protocol', 'If an agent made a mistake in a previous statement, it must actively correct itself. No covering up errors.', 'critical'],
    ];
    
    $stmt = $db->prepare("INSERT IGNORE INTO conference_integrity_rules (rule_name, rule_text, severity) VALUES (?, ?, ?)");
    foreach ($rules as $r) {
        $stmt->execute($r);
    }
    
    echo json_encode(['success' => true, 'rules_seeded' => count($rules)]);
}

function getRules($db) {
    $rules = $db->query("SELECT * FROM conference_integrity_rules WHERE is_active = 1 ORDER BY severity, id")->fetchAll();
    echo json_encode(['success' => true, 'rules' => $rules]);
}

function getAgentDirectives($db) {
    $rules = $db->query("SELECT rule_name, rule_text, severity FROM conference_integrity_rules WHERE is_active = 1 ORDER BY severity")->fetchAll();
    
    $directive = "═══ CONFERENCE INTEGRITY DIRECTIVES ═══\n";
    $directive .= "These rules are BINDING for ALL AI agents in conference rooms.\n";
    $directive .= "Violation triggers automatic flagging and Commander review.\n\n";
    
    foreach ($rules as $r) {
        $sev = strtoupper($r['severity']);
        $directive .= "[{$sev}] {$r['rule_name']}\n";
        $directive .= "  → {$r['rule_text']}\n\n";
    }
    
    $directive .= "═══ RESPONSE FORMAT FOR CONFERENCE ═══\n";
    $directive .= "When making claims, use this format:\n";
    $directive .= "  [FACT] Statement here (Source: URL or system)\n";
    $directive .= "  [OPINION] I believe... because...\n";
    $directive .= "  [SPECULATION] It's possible that...\n";
    $directive .= "  [UNVERIFIED] I've heard that... (no source available)\n";
    
    echo json_encode([
        'success' => true,
        'directive' => $directive,
        'rules_count' => count($rules),
        'note' => 'Inject this directive into every AI agent system prompt when they participate in conferences',
    ]);
}

function logClaim($db) {
    $room = trim($_POST['room_code'] ?? '');
    $agent = trim($_POST['agent_name'] ?? '');
    $claimType = $_POST['claim_type'] ?? 'fact';
    $claimText = trim($_POST['claim_text'] ?? '');
    $source = $_POST['source'] ?? '';
    $confidence = intval($_POST['confidence'] ?? 50);
    
    if (!$room || !$claimText) {
        echo json_encode(['error' => 'room_code and claim_text required']);
        return;
    }
    
    $verified = !empty($source) ? 1 : 0;
    $suspicious = 0;
    $flagReason = '';
    
    // Auto-flag suspicious claims
    $suspiciousPatterns = [
        '/\b100%\b/i' => 'Absolute certainty claim — nothing is 100%',
        '/\bguaranteed?\b/i' => 'Guarantee language — inappropriate for uncertain topics',
        '/\balways\b.*\bnever\b/i' => 'Absolute language (always/never)',
        '/\bI think\b.*\bfact\b/i' => 'Mixing opinion language with fact claims',
    ];
    
    foreach ($suspiciousPatterns as $pattern => $reason) {
        if (preg_match($pattern, $claimText)) {
            $suspicious = 1;
            $flagReason = $reason;
            break;
        }
    }
    
    if ($claimType === 'statistic' && empty($source)) {
        $suspicious = 1;
        $flagReason = 'Statistic without source citation';
    }
    
    $stmt = $db->prepare("INSERT INTO conference_integrity_log (room_code, agent_name, claim_type, claim_text, source, verified, confidence, flagged_suspicious, flag_reason) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$room, $agent, $claimType, $claimText, $source, $verified, $confidence, $suspicious, $flagReason]);
    
    echo json_encode([
        'success' => true,
        'claim_id' => $db->lastInsertId(),
        'verified' => $verified,
        'flagged' => $suspicious,
        'flag_reason' => $flagReason,
    ]);
}

function verifyClaim($db) {
    $claimId = intval($_POST['id'] ?? 0);
    $verified = intval($_POST['verified'] ?? 1);
    
    if (!$claimId) { echo json_encode(['error' => 'id required']); return; }
    
    $db->prepare("UPDATE conference_integrity_log SET verified = ?, flagged_suspicious = 0 WHERE id = ?")->execute([$verified, $claimId]);
    echo json_encode(['success' => true, 'verified' => $verified]);
}

function getRoomScore($db) {
    $room = trim($_GET['room'] ?? '');
    if (!$room) { echo json_encode(['error' => 'room required']); return; }
    
    $stats = $db->prepare("SELECT 
        COUNT(*) as total_claims,
        SUM(verified=1) as verified_claims,
        SUM(flagged_suspicious=1) as flagged_claims,
        AVG(confidence) as avg_confidence,
        COUNT(DISTINCT agent_name) as agents_participating
    FROM conference_integrity_log WHERE room_code = ?");
    $stats->execute([$room]);
    $stats = $stats->fetch();
    
    $total = max(1, (int)$stats['total_claims']);
    $verifiedPct = round(($stats['verified_claims'] / $total) * 100);
    $flaggedPct = round(($stats['flagged_claims'] / $total) * 100);
    
    $integrityScore = max(0, 100 - ($flaggedPct * 2) + ($verifiedPct / 2));
    $integrityScore = min(100, round($integrityScore));
    
    $grade = 'A+';
    if ($integrityScore < 90) $grade = 'A';
    if ($integrityScore < 80) $grade = 'B';
    if ($integrityScore < 70) $grade = 'C';
    if ($integrityScore < 60) $grade = 'D';
    if ($integrityScore < 50) $grade = 'F';
    
    echo json_encode([
        'success' => true,
        'room' => $room,
        'integrity_score' => $integrityScore,
        'grade' => $grade,
        'stats' => $stats,
        'verdict' => $integrityScore >= 80 ? 'TRUSTWORTHY MEETING' : ($integrityScore >= 60 ? 'NEEDS REVIEW' : 'INTEGRITY COMPROMISED'),
    ]);
}

function getAuditTrail($db) {
    $room = $_GET['room'] ?? null;
    $flaggedOnly = ($_GET['flagged'] ?? '0') === '1';
    
    $where = [];
    $params = [];
    if ($room) { $where[] = 'room_code = ?'; $params[] = $room; }
    if ($flaggedOnly) { $where[] = 'flagged_suspicious = 1'; }
    
    $sql = "SELECT * FROM conference_integrity_log";
    if ($where) $sql .= " WHERE " . implode(' AND ', $where);
    $sql .= " ORDER BY created_at DESC LIMIT 100";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    echo json_encode(['success' => true, 'claims' => $stmt->fetchAll()]);
}
