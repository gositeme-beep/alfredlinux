<?php
/**
 * SoundStudioPro — Audio API
 * /api/audio.php
 * 
 * Endpoints:
 *   POST /api/audio.php?action=upload     — Upload audio file
 *   POST /api/audio.php?action=analyze    — Analyze uploaded file (BPM, key, etc.)
 *   POST /api/audio.php?action=waveform   — Generate waveform data for player
 *   POST /api/audio.php?action=transcribe — Extract lyrics/speech
 *   POST /api/audio.php?action=stems      — Separate into stems (vocals/drums/bass/other)
 *   GET  /api/audio.php?action=status&job_id=X — Check async job status
 *   GET  /api/audio.php?action=tracks     — List all tracks
 *   GET  /api/audio.php?action=track&id=X — Get track details + analysis
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/../api/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$action = $_GET['action'] ?? '';
$db = getDB();

// Audio engine path
$ENGINE = '/opt/soundstudiopro/audio-engine.py';
$UPLOAD_DIR = '/home/gositeme/domains/gositeme.com/public_html/audio/uploads';
$STEMS_DIR = '/home/gositeme/domains/gositeme.com/public_html/audio/stems';
$WAVEFORM_DIR = '/home/gositeme/domains/gositeme.com/public_html/audio/waveforms';

// Ensure directories exist
foreach ([$UPLOAD_DIR, $STEMS_DIR, $WAVEFORM_DIR] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Allowed audio MIME types
$ALLOWED_TYPES = [
    'audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/x-wav',
    'audio/flac', 'audio/x-flac', 'audio/ogg', 'audio/aac',
    'audio/mp4', 'audio/x-m4a', 'audio/webm'
];
$MAX_FILE_SIZE = 100 * 1024 * 1024; // 100MB

try {
    switch ($action) {
        
        case 'upload':
            handleUpload($db);
            break;
            
        case 'analyze':
            handleAnalyze($db);
            break;
            
        case 'waveform':
            handleWaveform($db);
            break;
            
        case 'transcribe':
            handleTranscribe($db);
            break;
            
        case 'stems':
            handleStems($db);
            break;
            
        case 'status':
            handleJobStatus($db);
            break;
            
        case 'tracks':
            handleListTracks($db);
            break;
            
        case 'track':
            handleGetTrack($db);
            break;
            
        default:
            json_response(['error' => 'Invalid action. Use: upload, analyze, waveform, transcribe, stems, status, tracks, track'], 400);
    }
} catch (Exception $e) {
    json_response(['error' => $e->getMessage()], 500);
}

// ============================================================
// HANDLERS
// ============================================================

function handleUpload($db) {
    global $UPLOAD_DIR, $ALLOWED_TYPES, $MAX_FILE_SIZE;
    
    if (empty($_FILES['audio'])) {
        json_response(['error' => 'No audio file provided. Use multipart/form-data with field "audio"'], 400);
    }
    
    $file = $_FILES['audio'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        json_response(['error' => 'Upload error: ' . $file['error']], 400);
    }
    
    if ($file['size'] > $MAX_FILE_SIZE) {
        json_response(['error' => 'File too large. Max 100MB.'], 400);
    }
    
    // Validate MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $ALLOWED_TYPES)) {
        json_response(['error' => "Invalid file type: $mime. Accepted: mp3, wav, flac, ogg, aac, m4a, webm"], 400);
    }
    
    // Generate safe filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $ext = preg_replace('/[^a-zA-Z0-9]/', '', $ext);
    if (empty($ext)) $ext = 'mp3';
    $track_id = bin2hex(random_bytes(8));
    $safe_name = $track_id . '.' . $ext;
    $dest = $UPLOAD_DIR . '/' . $safe_name;
    
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        json_response(['error' => 'Failed to save file'], 500);
    }
    
    // Get basic info via ffprobe
    $ffprobe_cmd = sprintf(
        'ffprobe -v quiet -print_format json -show_format -show_streams %s 2>&1',
        escapeshellarg($dest)
    );
    $ffprobe_out = shell_exec($ffprobe_cmd);
    $ffprobe = json_decode($ffprobe_out, true);
    
    $duration = isset($ffprobe['format']['duration']) ? (float)$ffprobe['format']['duration'] : 0;
    $bitrate = isset($ffprobe['format']['bit_rate']) ? (int)$ffprobe['format']['bit_rate'] : 0;
    $format = $ffprobe['format']['format_name'] ?? $ext;
    
    // Store in DB
    $title = $_POST['title'] ?? pathinfo($file['name'], PATHINFO_FILENAME);
    $artist = $_POST['artist'] ?? 'Unknown';
    $album = $_POST['album'] ?? '';
    
    $stmt = $db->prepare("INSERT INTO audio_tracks (track_id, title, artist, album, filename, original_name, file_size, mime_type, duration, bitrate, format, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'uploaded', NOW())");
    $stmt->execute([$track_id, $title, $artist, $album, $safe_name, $file['name'], $file['size'], $mime, $duration, $bitrate, $format]);
    
    json_response([
        'success' => true,
        'track_id' => $track_id,
        'title' => $title,
        'artist' => $artist,
        'duration' => round($duration, 2),
        'file_size' => $file['size'],
        'format' => $format,
        'message' => 'Upload successful. Use analyze, waveform, or transcribe endpoints with this track_id.'
    ]);
}

function handleAnalyze($db) {
    global $ENGINE, $UPLOAD_DIR;
    
    $track_id = $_POST['track_id'] ?? $_GET['track_id'] ?? '';
    if (empty($track_id)) json_response(['error' => 'track_id required'], 400);
    
    $track = getTrack($db, $track_id);
    $filepath = $UPLOAD_DIR . '/' . $track['filename'];
    
    // Run analysis
    $cmd = sprintf('python3 %s analyze %s 2>&1', escapeshellarg($ENGINE), escapeshellarg($filepath));
    $output = shell_exec($cmd);
    
    // Parse JSON from output
    $json_start = strpos($output, '=== JSON OUTPUT ===');
    if ($json_start !== false) {
        $json_str = trim(substr($output, $json_start + 19));
    } else {
        // Try to parse entire output
        $json_str = $output;
    }
    
    $analysis = json_decode($json_str, true);
    if (!$analysis) {
        json_response(['error' => 'Analysis failed', 'raw_output' => $output], 500);
    }
    
    // Store analysis in DB
    $stmt = $db->prepare("UPDATE audio_tracks SET bpm = ?, musical_key = ?, energy_db = ?, analysis_json = ?, status = 'analyzed', analyzed_at = NOW() WHERE track_id = ?");
    $stmt->execute([
        $analysis['bpm'],
        $analysis['key'],
        $analysis['energy_db'],
        json_encode($analysis),
        $track_id
    ]);
    
    json_response([
        'success' => true,
        'track_id' => $track_id,
        'analysis' => $analysis
    ]);
}

function handleWaveform($db) {
    global $ENGINE, $UPLOAD_DIR, $WAVEFORM_DIR;
    
    $track_id = $_POST['track_id'] ?? $_GET['track_id'] ?? '';
    if (empty($track_id)) json_response(['error' => 'track_id required'], 400);
    
    $track = getTrack($db, $track_id);
    $filepath = $UPLOAD_DIR . '/' . $track['filename'];
    $waveform_file = $WAVEFORM_DIR . '/' . $track_id . '.json';
    
    // Check if already generated
    if (file_exists($waveform_file)) {
        $data = json_decode(file_get_contents($waveform_file), true);
        json_response(['success' => true, 'track_id' => $track_id, 'waveform' => $data]);
    }
    
    $cmd = sprintf('python3 %s waveform %s 2>&1', escapeshellarg($ENGINE), escapeshellarg($filepath));
    $output = shell_exec($cmd);
    
    $json_start = strpos($output, '=== JSON OUTPUT ===');
    $json_str = $json_start !== false ? trim(substr($output, $json_start + 19)) : $output;
    
    $waveform = json_decode($json_str, true);
    if (!$waveform) {
        json_response(['error' => 'Waveform generation failed'], 500);
    }
    
    // Cache to file
    file_put_contents($waveform_file, json_encode($waveform));
    
    // Update DB
    $db->prepare("UPDATE audio_tracks SET waveform_file = ? WHERE track_id = ?")->execute([$track_id . '.json', $track_id]);
    
    json_response(['success' => true, 'track_id' => $track_id, 'waveform' => $waveform]);
}

function handleTranscribe($db) {
    global $ENGINE, $UPLOAD_DIR;
    
    $track_id = $_POST['track_id'] ?? $_GET['track_id'] ?? '';
    if (empty($track_id)) json_response(['error' => 'track_id required'], 400);
    
    $track = getTrack($db, $track_id);
    $filepath = $UPLOAD_DIR . '/' . $track['filename'];
    
    $model = $_POST['model'] ?? 'small';
    $allowed_models = ['tiny', 'base', 'small'];
    if (!in_array($model, $allowed_models)) $model = 'small';
    
    // This can take a while — run async via job queue
    $job_id = bin2hex(random_bytes(8));
    $stmt = $db->prepare("INSERT INTO audio_jobs (job_id, track_id, job_type, status, created_at) VALUES (?, ?, 'transcribe', 'queued', NOW())");
    $stmt->execute([$job_id, $track_id]);
    
    // Run in background
    $cmd = sprintf(
        'nohup python3 %s transcribe %s %s > /tmp/job_%s.log 2>&1 &',
        escapeshellarg($ENGINE),
        escapeshellarg($filepath),
        escapeshellarg($model),
        $job_id
    );
    exec($cmd);
    
    // Also start a PHP watcher to update the DB when done
    $watcher = sprintf(
        'nohup php %s/audio-job-watcher.php %s %s > /dev/null 2>&1 &',
        escapeshellarg(__DIR__),
        escapeshellarg($job_id),
        escapeshellarg($track_id)
    );
    exec($watcher);
    
    json_response([
        'success' => true,
        'job_id' => $job_id,
        'track_id' => $track_id,
        'status' => 'queued',
        'message' => 'Transcription queued. Check status with ?action=status&job_id=' . $job_id
    ]);
}

function handleStems($db) {
    global $ENGINE, $UPLOAD_DIR, $STEMS_DIR;
    
    $track_id = $_POST['track_id'] ?? $_GET['track_id'] ?? '';
    if (empty($track_id)) json_response(['error' => 'track_id required'], 400);
    
    $track = getTrack($db, $track_id);
    $filepath = $UPLOAD_DIR . '/' . $track['filename'];
    
    $job_id = bin2hex(random_bytes(8));
    $stmt = $db->prepare("INSERT INTO audio_jobs (job_id, track_id, job_type, status, created_at) VALUES (?, ?, 'stems', 'queued', NOW())");
    $stmt->execute([$job_id, $track_id]);
    
    // Run demucs in background (takes minutes)
    $cmd = sprintf(
        'nohup python3 %s stems %s %s > /tmp/job_%s.log 2>&1 &',
        escapeshellarg($ENGINE),
        escapeshellarg($filepath),
        escapeshellarg($STEMS_DIR),
        $job_id
    );
    exec($cmd);
    
    json_response([
        'success' => true,
        'job_id' => $job_id,
        'track_id' => $track_id,
        'status' => 'queued',
        'message' => 'Stem separation queued (~6 min per song). Check status with ?action=status&job_id=' . $job_id
    ]);
}

function handleJobStatus($db) {
    $job_id = $_GET['job_id'] ?? '';
    if (empty($job_id)) json_response(['error' => 'job_id required'], 400);
    
    $stmt = $db->prepare("SELECT * FROM audio_jobs WHERE job_id = ?");
    $stmt->execute([$job_id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$job) json_response(['error' => 'Job not found'], 404);
    
    // Check log file for progress
    $log_file = "/tmp/job_{$job_id}.log";
    $log_tail = '';
    if (file_exists($log_file)) {
        $log_tail = trim(shell_exec("tail -5 " . escapeshellarg($log_file)));
    }
    
    json_response([
        'job_id' => $job_id,
        'track_id' => $job['track_id'],
        'type' => $job['job_type'],
        'status' => $job['status'],
        'created_at' => $job['created_at'],
        'completed_at' => $job['completed_at'],
        'result' => $job['result_json'] ? json_decode($job['result_json'], true) : null,
        'log' => $log_tail
    ]);
}

function handleListTracks($db) {
    $limit = min((int)($_GET['limit'] ?? 50), 100);
    $offset = max((int)($_GET['offset'] ?? 0), 0);
    
    $stmt = $db->prepare("SELECT track_id, title, artist, album, duration, bpm, musical_key, energy_db, format, file_size, status, created_at FROM audio_tracks ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute([$limit, $offset]);
    $tracks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $count = $db->query("SELECT COUNT(*) FROM audio_tracks")->fetchColumn();
    
    json_response(['tracks' => $tracks, 'total' => (int)$count, 'limit' => $limit, 'offset' => $offset]);
}

function handleGetTrack($db) {
    $track_id = $_GET['id'] ?? $_GET['track_id'] ?? '';
    if (empty($track_id)) json_response(['error' => 'track_id required'], 400);
    
    $track = getTrack($db, $track_id);
    
    // Include analysis if available
    if ($track['analysis_json']) {
        $track['analysis'] = json_decode($track['analysis_json'], true);
    }
    
    // Include transcription if available
    if ($track['transcription_json']) {
        $track['transcription'] = json_decode($track['transcription_json'], true);
    }
    
    // Get related jobs
    $stmt = $db->prepare("SELECT job_id, job_type, status, created_at, completed_at FROM audio_jobs WHERE track_id = ? ORDER BY created_at DESC");
    $stmt->execute([$track_id]);
    $track['jobs'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    json_response(['track' => $track]);
}

// ============================================================
// HELPERS
// ============================================================

function getTrack($db, $track_id) {
    $track_id = preg_replace('/[^a-f0-9]/', '', $track_id);
    $stmt = $db->prepare("SELECT * FROM audio_tracks WHERE track_id = ?");
    $stmt->execute([$track_id]);
    $track = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$track) {
        json_response(['error' => 'Track not found'], 404);
        exit;
    }
    return $track;
}

function json_response($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}
