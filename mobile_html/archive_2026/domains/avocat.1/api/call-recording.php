<?php
/**
 * Call Recording & AI Summary API
 * ────────────────────────────────
 * Manages call recordings, transcriptions, and AI-generated summaries.
 *
 * Endpoints:
 *   ?action=list           — List recordings with filters
 *   ?action=detail         — Get recording detail with transcript & summary
 *   ?action=transcribe     — Trigger AI transcription for a recording
 *   ?action=summarize      — Generate AI summary from transcript
 *   ?action=search         — Full-text search across transcripts
 *   ?action=stats          — Recording analytics
 *   ?action=export         — Export transcript/summary
 *   ?action=conference_save — Save a conference recording
 */
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../includes/ws-push.php';

$userId = $_SESSION['uid'] ?? $_SESSION['userid'] ?? 0;
if (!$userId) {
    echo json_encode(['error' => 'Authentication required']);
require_once dirname(__DIR__) . '/includes/api-security.php';
    exit;
}

$pdo = getDB();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':       handleList($pdo, $userId); break;
    case 'detail':     handleDetail($pdo, $userId); break;
    case 'transcribe': handleTranscribe($pdo, $userId); break;
    case 'summarize':  handleSummarize($pdo, $userId); break;
    case 'search':     handleSearch($pdo, $userId); break;
    case 'stats':      handleStats($pdo, $userId); break;
    case 'export':     handleExport($pdo, $userId); break;
    case 'conference_save': handleConferenceSave($pdo, $userId); break;
    default:
        echo json_encode(['error' => 'Unknown action', 'available' => [
            'list','detail','transcribe','summarize','search','stats','export','conference_save'
        ]]);
}

/* ═══════════════════════════════════════════════════════════════ */

function handleList($pdo, $userId) {
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $limit  = min(50, max(1, (int)($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    $type   = $_GET['type'] ?? 'all'; // call, conference, all
    $hasRec = $_GET['has_recording'] ?? '';

    $results = [];

    // Get call recordings from alfred_call_log
    if ($type === 'all' || $type === 'call') {
        $sql = "SELECT call_id, caller_number, started_at, ended_at, duration_seconds,
                       recording_url, summary, success_evaluation, cost_usd,
                       'call' as source_type
                FROM alfred_call_log WHERE client_id = ?";
        $params = [$userId];
        if ($hasRec === '1') { $sql .= " AND recording_url IS NOT NULL AND recording_url != ''"; }
        $sql .= " ORDER BY started_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $stmt = $pdo->prepare($sql);
        dbExecute($stmt, $params);
        $results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // Get conference recordings
    if ($type === 'all' || $type === 'conference') {
        $sql = "SELECT id, topic as call_id, created_at as started_at, ended_at,
                       TIMESTAMPDIFF(SECOND, created_at, IFNULL(ended_at, NOW())) as duration_seconds,
                       recording_url, '' as summary, '' as success_evaluation, 0 as cost_usd,
                       'conference' as source_type
                FROM alfred_conferences WHERE host_user_id = ?";
        $params2 = [$userId];
        if ($hasRec === '1') { $sql .= " AND recording_url IS NOT NULL AND recording_url != ''"; }
        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params2[] = $limit;
        $params2[] = $offset;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params2);
        $results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // Sort combined by date descending
    usort($results, fn($a, $b) => strtotime($b['started_at'] ?? 0) - strtotime($a['started_at'] ?? 0));

    echo json_encode([
        'recordings' => array_slice($results, 0, $limit),
        'page'       => $page,
        'limit'      => $limit,
        'total'      => count($results),
    ]);
}

function handleDetail($pdo, $userId) {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_GET;
    $callId = $input['call_id'] ?? '';
    $type   = $input['type'] ?? 'call';

    if (!$callId) {
        echo json_encode(['error' => 'call_id required']);
        return;
    }

    if ($type === 'conference') {
        $stmt = $pdo->prepare("SELECT * FROM alfred_conferences WHERE id = ? AND host_user_id = ?");
        $stmt->execute([$callId, $userId]);
        $rec = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM alfred_call_log WHERE call_id = ? AND client_id = ?");
        $stmt->execute([$callId, $userId]);
        $rec = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$rec) {
        echo json_encode(['error' => 'Recording not found']);
        return;
    }

    echo json_encode(['recording' => $rec]);
}

function handleTranscribe($pdo, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    $callId      = $input['call_id'] ?? '';
    $recordingUrl = $input['recording_url'] ?? '';
    $type        = $input['type'] ?? 'call';

    if (!$callId) {
        echo json_encode(['error' => 'call_id required']);
        return;
    }

    // Get existing recording URL if not provided
    if (!$recordingUrl) {
        if ($type === 'conference') {
            $stmt = $pdo->prepare("SELECT recording_url FROM alfred_conferences WHERE host_user_id = ? AND id = ?");
            $stmt->execute([$userId, $callId]);
        } else {
            $stmt = $pdo->prepare("SELECT recording_url FROM alfred_call_log WHERE call_id = ? AND client_id = ?");
            $stmt->execute([$callId, $userId]);
        }
        $rec = $stmt->fetch();
        $recordingUrl = $rec['recording_url'] ?? '';
    }

    if (!$recordingUrl) {
        echo json_encode(['error' => 'No recording URL available']);
        return;
    }

    // Call Whisper via Groq
    $transcript = whisperTranscribeUrl($recordingUrl);
    if (!$transcript) {
        echo json_encode(['error' => 'Transcription failed']);
        return;
    }

    // Save transcript
    if ($type === 'conference') {
        $stmt = $pdo->prepare("UPDATE alfred_conferences SET transcript = ? WHERE id = ? AND host_user_id = ?");
        $stmt->execute([$transcript, $callId, $userId]);
    } else {
        $stmt = $pdo->prepare("UPDATE alfred_call_log SET transcript = ? WHERE call_id = ? AND client_id = ?");
        $stmt->execute([$transcript, $callId, $userId]);
    }

    ws_push_user((string)$userId, 'transcription_ready', [
        'call_id' => $callId,
        'type' => $type,
    ]);

    echo json_encode(['transcript' => $transcript, 'call_id' => $callId]);
}

function handleSummarize($pdo, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    $callId = $input['call_id'] ?? '';
    $type   = $input['type'] ?? 'call';

    if (!$callId) {
        echo json_encode(['error' => 'call_id required']);
        return;
    }

    // Get transcript
    if ($type === 'conference') {
        $stmt = $pdo->prepare("SELECT transcript FROM alfred_conferences WHERE id = ? AND host_user_id = ?");
        $stmt->execute([$callId, $userId]);
    } else {
        $stmt = $pdo->prepare("SELECT transcript FROM alfred_call_log WHERE call_id = ? AND client_id = ?");
        $stmt->execute([$callId, $userId]);
    }
    $rec = $stmt->fetch();
    $transcript = $rec['transcript'] ?? '';

    if (!$transcript) {
        echo json_encode(['error' => 'No transcript available. Run transcribe first.']);
        return;
    }

    // Generate AI summary
    $summary = generateAISummary($transcript);
    if (!$summary) {
        echo json_encode(['error' => 'Summary generation failed']);
        return;
    }

    // Save summary
    if ($type === 'conference') {
        // Store in the agenda JSON field as summary
        $stmt = $pdo->prepare("UPDATE alfred_conferences SET agenda = JSON_SET(COALESCE(agenda, '{}'), '$.ai_summary', ?) WHERE id = ? AND host_user_id = ?");
        $stmt->execute([$summary, $callId, $userId]);
    } else {
        $stmt = $pdo->prepare("UPDATE alfred_call_log SET summary = ? WHERE call_id = ? AND client_id = ?");
        $stmt->execute([$summary, $callId, $userId]);
    }

    ws_push_user((string)$userId, 'summary_ready', [
        'call_id' => $callId,
        'type' => $type,
    ]);

    echo json_encode(['summary' => $summary, 'call_id' => $callId]);
}

function handleSearch($pdo, $userId) {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_GET;
    $query = mb_substr(trim($input['query'] ?? ''), 0, 200);
    $limit = min(50, max(1, (int)($input['limit'] ?? 20)));

    if (!$query) {
        echo json_encode(['error' => 'query required']);
        return;
    }

    $searchTerm = '%' . $query . '%';

    $stmt = $pdo->prepare(
        "SELECT call_id, caller_number, started_at, duration_seconds, recording_url,
                summary, 'call' as source_type,
                CASE WHEN transcript LIKE ? THEN 'transcript' ELSE 'summary' END as match_type
         FROM alfred_call_log
         WHERE client_id = ? AND (transcript LIKE ? OR summary LIKE ?)
         ORDER BY started_at DESC LIMIT ?"
    );
    dbExecute($stmt, [$searchTerm, $userId, $searchTerm, $searchTerm, $limit]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['results' => $results, 'query' => $query, 'total' => count($results)]);
}

function handleStats($pdo, $userId) {
    $period = $_GET['period'] ?? 'month';
    $days = match($period) {
        'week' => 7, 'month' => 30, 'quarter' => 90, 'year' => 365, default => 30
    };

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) as total_calls,
                SUM(CASE WHEN recording_url IS NOT NULL AND recording_url != '' THEN 1 ELSE 0 END) as recorded_calls,
                SUM(CASE WHEN transcript IS NOT NULL AND transcript != '' THEN 1 ELSE 0 END) as transcribed_calls,
                SUM(CASE WHEN summary IS NOT NULL AND summary != '' THEN 1 ELSE 0 END) as summarized_calls,
                COALESCE(SUM(duration_seconds), 0) as total_duration,
                COALESCE(AVG(duration_seconds), 0) as avg_duration,
                COALESCE(SUM(cost_usd), 0) as total_cost
         FROM alfred_call_log
         WHERE client_id = ? AND started_at >= DATE_SUB(NOW(), INTERVAL ? DAY)"
    );
    $stmt->execute([$userId, $days]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Daily breakdown
    $stmt2 = $pdo->prepare(
        "SELECT DATE(started_at) as date, COUNT(*) as calls,
                SUM(duration_seconds) as duration,
                SUM(CASE WHEN recording_url IS NOT NULL AND recording_url != '' THEN 1 ELSE 0 END) as recorded
         FROM alfred_call_log
         WHERE client_id = ? AND started_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
         GROUP BY DATE(started_at) ORDER BY date DESC"
    );
    $stmt2->execute([$userId, $days]);
    $daily = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['stats' => $stats, 'daily' => $daily, 'period' => $period]);
}

function handleExport($pdo, $userId) {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_GET;
    $callId = $input['call_id'] ?? '';
    $format = $input['format'] ?? 'text'; // text, json, markdown
    $type   = $input['type'] ?? 'call';

    if (!$callId) {
        echo json_encode(['error' => 'call_id required']);
        return;
    }

    if ($type === 'conference') {
        $stmt = $pdo->prepare("SELECT * FROM alfred_conferences WHERE id = ? AND host_user_id = ?");
        $stmt->execute([$callId, $userId]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM alfred_call_log WHERE call_id = ? AND client_id = ?");
        $stmt->execute([$callId, $userId]);
    }
    $rec = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$rec) {
        echo json_encode(['error' => 'Recording not found']);
        return;
    }

    if ($format === 'markdown') {
        $export = "# Call Recording\n\n";
        $export .= "**Date:** " . ($rec['started_at'] ?? $rec['created_at'] ?? 'N/A') . "\n";
        $export .= "**Duration:** " . gmdate('H:i:s', $rec['duration_seconds'] ?? 0) . "\n";
        if (!empty($rec['caller_number'])) $export .= "**Caller:** " . $rec['caller_number'] . "\n";
        $export .= "\n## Summary\n\n" . ($rec['summary'] ?? 'No summary available.') . "\n";
        $export .= "\n## Transcript\n\n" . ($rec['transcript'] ?? 'No transcript available.') . "\n";
    } elseif ($format === 'json') {
        echo json_encode(['export' => $rec]);
        return;
    } else {
        $export = "CALL RECORDING\n";
        $export .= "Date: " . ($rec['started_at'] ?? $rec['created_at'] ?? 'N/A') . "\n";
        $export .= "Duration: " . gmdate('H:i:s', $rec['duration_seconds'] ?? 0) . "\n\n";
        $export .= "SUMMARY:\n" . ($rec['summary'] ?? 'N/A') . "\n\n";
        $export .= "TRANSCRIPT:\n" . ($rec['transcript'] ?? 'N/A') . "\n";
    }

    echo json_encode(['export' => $export, 'format' => $format]);
}

function handleConferenceSave($pdo, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    $conferenceId = $input['conference_id'] ?? '';
    $recordingUrl = $input['recording_url'] ?? '';
    $transcript   = $input['transcript'] ?? '';

    if (!$conferenceId) {
        echo json_encode(['error' => 'conference_id required']);
        return;
    }

    // Verify ownership
    $stmt = $pdo->prepare("SELECT id FROM alfred_conferences WHERE id = ? AND host_user_id = ?");
    $stmt->execute([$conferenceId, $userId]);
    if (!$stmt->fetch()) {
        echo json_encode(['error' => 'Conference not found']);
        return;
    }

    $updates = [];
    $params  = [];
    if ($recordingUrl) {
        $updates[] = "recording_url = ?";
        $params[]  = $recordingUrl;
    }
    if ($transcript) {
        $updates[] = "transcript = ?";
        $params[]  = $transcript;
    }

    if (empty($updates)) {
        echo json_encode(['error' => 'Nothing to save']);
        return;
    }

    $params[] = $conferenceId;
    $params[] = $userId;
    $sql = "UPDATE alfred_conferences SET " . implode(', ', $updates) . " WHERE id = ? AND host_user_id = ?";
    $pdo->prepare($sql)->execute($params);

    // Auto-summarize if transcript provided
    $summary = null;
    if ($transcript) {
        $summary = generateAISummary($transcript);
        if ($summary) {
            $stmt = $pdo->prepare("UPDATE alfred_conferences SET agenda = JSON_SET(COALESCE(agenda, '{}'), '$.ai_summary', ?) WHERE id = ? AND host_user_id = ?");
            $stmt->execute([$summary, $conferenceId, $userId]);
        }
    }

    ws_push_user((string)$userId, 'conference_saved', [
        'conference_id' => $conferenceId,
    ]);

    echo json_encode(['saved' => true, 'summary' => $summary]);
}

/* ═══════════════════════════════════════════════════════════════
   HELPERS
   ═══════════════════════════════════════════════════════════════ */

/**
 * Transcribe audio from a URL using Whisper (Groq primary, OpenAI fallback)
 */
function whisperTranscribeUrl(string $url): ?string {
    // Download audio to temp file
    $tmpFile = tempnam(sys_get_temp_dir(), 'rec_');
    $ch = curl_init($url);
    $fp = fopen($tmpFile, 'w');
    curl_setopt_array($ch, [
        CURLOPT_FILE           => $fp,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 120,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    fclose($fp);

    if ($httpCode !== 200 || filesize($tmpFile) < 1000) {
        @unlink($tmpFile);
        return null;
    }

    // Try Groq Whisper first
    $transcript = callWhisperAPI(
        'https://api.groq.com/openai/v1/audio/transcriptions',
        $_ENV['GROQ_API_KEY'] ?? '',
        $tmpFile,
        'whisper-large-v3'
    );

    // Fallback to OpenAI Whisper
    if (!$transcript) {
        $transcript = callWhisperAPI(
            'https://api.openai.com/v1/audio/transcriptions',
            $_ENV['OPENAI_API_KEY'] ?? '',
            $tmpFile,
            'whisper-1'
        );
    }

    @unlink($tmpFile);
    return $transcript;
}

function callWhisperAPI(string $apiUrl, string $apiKey, string $filePath, string $model): ?string {
    if (!$apiKey) return null;

    $cFile = new CURLFile($filePath, 'audio/mpeg', 'recording.mp3');
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => ['file' => $cFile, 'model' => $model, 'response_format' => 'verbose_json'],
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$apiKey}"],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 120,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$result) return null;

    $data = json_decode($result, true);

    // Build timestamped transcript if segments available
    if (!empty($data['segments'])) {
        $lines = [];
        foreach ($data['segments'] as $seg) {
            $ts = gmdate('H:i:s', (int)($seg['start'] ?? 0));
            $lines[] = "[{$ts}] " . trim($seg['text'] ?? '');
        }
        return implode("\n", $lines);
    }

    return $data['text'] ?? null;
}

/**
 * Generate AI summary from transcript using the AI backbone
 */
function generateAISummary(string $transcript): ?string {
    // Truncate very long transcripts
    $text = mb_substr($transcript, 0, 15000);

    $payload = json_encode([
        'model'      => 'claude-sonnet-4-20250514',
        'max_tokens' => 800,
        'system'     => "You are a call analysis AI. Generate a structured summary of this call/conference transcript.\n\n"
                      . "Format your response with these sections:\n"
                      . "## Summary\nBrief 2-3 sentence overview\n\n"
                      . "## Key Points\n- Bullet points of important topics discussed\n\n"
                      . "## Action Items\n- Numbered list of follow-up actions with assignees if mentioned\n\n"
                      . "## Decisions Made\n- Any agreements or decisions reached\n\n"
                      . "## Sentiment\nOverall tone: positive/neutral/negative with brief explanation",
        'messages'   => [['role' => 'user', 'content' => "Analyze this transcript:\n\n{$text}"]]
    ]);

    $ch = curl_init('http://127.0.0.1:3001/api/anthropic-proxy/gositeme/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: call-recording-summary',
            'anthropic-version: 2023-06-01',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$result) return null;
    $data = json_decode($result, true);
    return $data['content'][0]['text'] ?? null;
}
