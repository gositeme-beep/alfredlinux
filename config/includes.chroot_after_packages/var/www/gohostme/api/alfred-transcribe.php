<?php
/**
 * Alfred Transcription API — /api/alfred-transcribe.php
 * ══════════════════════════════════════════════════════
 * Universal audio-to-text endpoint.
 *
 * POST /api/alfred-transcribe.php
 *   - multipart/form-data with field "audio" (file upload)
 *   - Optional fields: language, prompt, provider, source
 *
 * POST /api/alfred-transcribe.php?action=url
 *   - JSON body: { "url": "https://...", "language": "en" }
 *   - Downloads and transcribes audio from a URL
 *
 * GET /api/alfred-transcribe.php?action=list&limit=20
 *   - Returns recent transcriptions (Commander only)
 *
 * GET /api/alfred-transcribe.php?action=get&id=123
 *   - Returns a specific transcription (Commander only)
 *
 * Auth: Commander (client_id 33) bypasses limits.
 *       Others need active session or X-Alfred-Token header.
 */

define('GOSITEME_API', true);
$GLOBALS['CSRF_EXEMPT'] = true;

// Basic security headers
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: https://gositeme.com');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Alfred-Token');
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../scripts/alfred-transcribe.php';

// ── Authentication ──────────────────────────────────────────────
$clientId = null;
$isCommander = false;

// Check session
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['uid']) && (int)$_SESSION['uid'] > 0) {
    $clientId = (int)$_SESSION['uid'];
    $isCommander = ($clientId === 33);
}

// Check X-Alfred-Token header (for programmatic access)
if (!$clientId) {
    $token = $_SERVER['HTTP_X_ALFRED_TOKEN'] ?? '';
    if ($token) {
        try {
            $pdo = new PDO('mysql:unix_socket=/run/mysql/mysql.sock;dbname=gositeme_whmcs', 'gositeme_whmcs', '!q@w#e$r5t');
            $hash = hash('sha256', $token);
            $stmt = $pdo->prepare("SELECT user_id FROM alfred_ide_sessions WHERE session_token = ? AND expires_at > NOW() LIMIT 1");
            $stmt->execute([$hash]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                // Get client_id from IDE user
                $stmt2 = $pdo->prepare("SELECT client_id FROM alfred_ide_users WHERE id = ? LIMIT 1");
                $stmt2->execute([$row['user_id']]);
                $u = $stmt2->fetch(PDO::FETCH_ASSOC);
                if ($u) {
                    $clientId = (int)$u['client_id'];
                    $isCommander = ($clientId === 33);
                }
            }
        } catch (\Throwable $e) {
            error_log("alfred-transcribe API: token auth error: " . $e->getMessage());
        }
    }
}

// Check X-Job-Secret (for internal service calls)
if (!$clientId) {
    $jobSecret = $_SERVER['HTTP_X_JOB_SECRET'] ?? '';
    if ($jobSecret) {
        $envFile = dirname(__DIR__) . '/.env';
        if (file_exists($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if (preg_match('/^JOB_SECRET=(.+)$/', trim($line), $m)) {
                    if (hash_equals(trim($m[1]), $jobSecret)) {
                        $clientId = 33; // Internal = Commander
                        $isCommander = true;
                    }
                    break;
                }
            }
        }
    }
}

// Public uploads allowed but rate-limited
$action = $_GET['action'] ?? '';

// Commander-only endpoints
if (in_array($action, ['list', 'get'])) {
    if (!$isCommander) {
        http_response_code(403);
        echo json_encode(['error' => 'Commander access required']);
        exit;
    }
}

// ── Route Actions ───────────────────────────────────────────────
switch ($action) {
    case 'list':
        handleList();
        break;
    case 'get':
        handleGet();
        break;
    case 'url':
        handleUrlTranscribe();
        break;
    default:
        handleFileUpload();
        break;
}

// ── File Upload Transcription ───────────────────────────────────
function handleFileUpload(): void {
    global $clientId, $isCommander;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'POST required']);
        return;
    }

    if (!isset($_FILES['audio']) || $_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
        $errCode = $_FILES['audio']['error'] ?? 'missing';
        http_response_code(400);
        echo json_encode(['error' => 'No audio file uploaded', 'code' => $errCode]);
        return;
    }

    $file = $_FILES['audio'];

    // Validate file size (25MB max)
    if ($file['size'] > 25 * 1024 * 1024) {
        http_response_code(413);
        echo json_encode(['error' => 'File too large (max 25MB)']);
        return;
    }

    // Validate MIME type first
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $detectedMime = $finfo->file($file['tmp_name']);
    $validMimes = ['audio/mpeg', 'audio/mp3', 'audio/mp4', 'audio/x-m4a', 'audio/wav', 'audio/x-wav',
                   'audio/webm', 'audio/ogg', 'audio/flac', 'audio/x-flac', 'video/mp4', 'video/webm',
                   'application/octet-stream', 'application/ogg'];
    if (!in_array($detectedMime, $validMimes)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid audio file (detected: ' . $detectedMime . ')']);
        return;
    }

    // Validate extension (with MIME-based fallback)
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $validExts = ['mp3', 'mp4', 'mpeg', 'mpga', 'm4a', 'wav', 'webm', 'ogg', 'flac'];
    if (!in_array($ext, $validExts)) {
        // Fallback: infer extension from MIME type
        $mimeToExt = [
            'audio/mpeg' => 'mp3', 'audio/mp3' => 'mp3', 'audio/mp4' => 'mp4',
            'audio/x-m4a' => 'm4a', 'audio/wav' => 'wav', 'audio/x-wav' => 'wav',
            'audio/webm' => 'webm', 'audio/ogg' => 'ogg', 'audio/flac' => 'flac',
            'audio/x-flac' => 'flac', 'video/mp4' => 'mp4', 'video/webm' => 'webm',
            'application/ogg' => 'ogg',
        ];
        $ext = $mimeToExt[$detectedMime] ?? '';
        if (!$ext) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Unsupported format. Supported: ' . implode(', ', $validExts)]);
            return;
        }
    }

    $source = $_POST['source'] ?? 'web-upload';
    $options = [];
    if (!empty($_POST['language'])) $options['language'] = substr($_POST['language'], 0, 5);
    if (!empty($_POST['prompt'])) $options['prompt'] = $_POST['prompt'];
    if (!empty($_POST['provider'])) $options['provider'] = $_POST['provider'];
    if (!empty($_POST['translate'])) $options['translate'] = true;

    $result = (!empty($options['translate']))
        ? alfred_translate($file['tmp_name'], $options)
        : alfred_transcribe($file['tmp_name'], $options);

    if ($result['ok']) {
        $savedId = alfred_save_transcription($result, $source, $clientId, $file['name']);
        echo json_encode([
            'ok' => true,
            'id' => $savedId,
            'text' => $result['text'],
            'language' => $result['language'] ?? null,
            'duration' => $result['duration'] ?? 0,
            'provider' => $result['provider'] ?? null,
            'filename' => $file['name'],
        ]);
    } else {
        http_response_code(502);
        echo json_encode([
            'ok' => false,
            'error' => $result['error'] ?? 'Transcription failed',
        ]);
    }
}

// ── URL-based Transcription ─────────────────────────────────────
function handleUrlTranscribe(): void {
    global $clientId, $isCommander;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'POST required']);
        return;
    }

    // Only authenticated users can transcribe from URLs (SSRF protection)
    if (!$clientId) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required for URL transcription']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $url = $input['url'] ?? '';

    if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Valid URL required']);
        return;
    }

    // SSRF protection: only allow HTTPS and known audio hosts
    $parsed = parse_url($url);
    $scheme = strtolower($parsed['scheme'] ?? '');
    if ($scheme !== 'https') {
        http_response_code(400);
        echo json_encode(['error' => 'Only HTTPS URLs allowed']);
        return;
    }

    // Block internal/private IPs
    $host = $parsed['host'] ?? '';
    $ip = gethostbyname($host);
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        http_response_code(400);
        echo json_encode(['error' => 'URL points to internal network']);
        return;
    }

    // Download audio (max 25MB, 30s timeout)
    $tmpFile = tempnam(sys_get_temp_dir(), 'alfred-audio-');
    $ch = curl_init($url);
    $fp = fopen($tmpFile, 'wb');
    curl_setopt_array($ch, [
        CURLOPT_FILE => $fp,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_MAXFILESIZE => 25 * 1024 * 1024,
        CURLOPT_USERAGENT => 'Alfred-Transcribe/1.0',
    ]);
    $ok = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    fclose($fp);

    if (!$ok || $httpCode !== 200) {
        @unlink($tmpFile);
        http_response_code(502);
        echo json_encode(['error' => 'Failed to download audio from URL']);
        return;
    }

    // Detect format from URL or content
    $ext = strtolower(pathinfo($parsed['path'] ?? '', PATHINFO_EXTENSION));
    $validExts = ['mp3', 'mp4', 'mpeg', 'mpga', 'm4a', 'wav', 'webm', 'ogg', 'flac'];

    if (!in_array($ext, $validExts)) {
        // Try to convert with ffmpeg
        $converted = alfred_convert_audio($tmpFile, 'mp3');
        if ($converted) {
            @unlink($tmpFile);
            $tmpFile = $converted;
            $ext = 'mp3';
        } else {
            @unlink($tmpFile);
            http_response_code(400);
            echo json_encode(['error' => 'Could not determine audio format. Supported: ' . implode(', ', $validExts)]);
            return;
        }
    }

    // Rename with extension for Whisper API
    $namedFile = $tmpFile . '.' . $ext;
    rename($tmpFile, $namedFile);

    $options = [];
    if (!empty($input['language'])) $options['language'] = $input['language'];
    if (!empty($input['prompt'])) $options['prompt'] = $input['prompt'];

    $result = alfred_transcribe($namedFile, $options);
    @unlink($namedFile);

    if ($result['ok']) {
        $savedId = alfred_save_transcription($result, 'url', $clientId, basename($parsed['path'] ?? 'audio'));
        echo json_encode([
            'ok' => true,
            'id' => $savedId,
            'text' => $result['text'],
            'language' => $result['language'] ?? null,
            'duration' => $result['duration'] ?? 0,
            'provider' => $result['provider'] ?? null,
        ]);
    } else {
        http_response_code(502);
        echo json_encode(['ok' => false, 'error' => $result['error']]);
    }
}

// ── List Recent Transcriptions (Commander only) ─────────────────
function handleList(): void {
    $limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
    $source = $_GET['source'] ?? '';

    try {
        $pdo = new PDO('mysql:unix_socket=/run/mysql/mysql.sock;dbname=gositeme_whmcs', 'gositeme_whmcs', '!q@w#e$r5t');
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $sql = "SELECT id, client_id, source, original_filename, LEFT(transcript, 200) as preview, language, duration_seconds, provider, model, created_at FROM alfred_transcriptions";
        $params = [];
        if ($source) {
            $sql .= " WHERE source = ?";
            $params[] = $source;
        }
        $sql .= " ORDER BY created_at DESC LIMIT " . $limit;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['ok' => true, 'count' => count($rows), 'transcriptions' => $rows]);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
}

// ── Get Single Transcription (Commander only) ───────────────────
function handleGet(): void {
    $id = (int)($_GET['id'] ?? 0);
    if ($id < 1) {
        http_response_code(400);
        echo json_encode(['error' => 'Valid id required']);
        return;
    }

    try {
        $pdo = new PDO('mysql:unix_socket=/run/mysql/mysql.sock;dbname=gositeme_whmcs', 'gositeme_whmcs', '!q@w#e$r5t');
        $stmt = $pdo->prepare("SELECT * FROM alfred_transcriptions WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            http_response_code(404);
            echo json_encode(['error' => 'Transcription not found']);
            return;
        }

        echo json_encode(['ok' => true, 'transcription' => $row]);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
}
