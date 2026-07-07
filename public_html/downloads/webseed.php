<?php
// downloads/webseed.php — HTTP webseed endpoint for WebTorrent
// Streams the ISO with Range request support so browser WebTorrent
// can pull pieces directly over HTTPS without needing WebRTC.
// No covenant gate — this is the P2P webseed fallback.

declare(strict_types=1);

require_once __DIR__ . '/../includes/ga-release-state.php';

$isoPath = '/home/gositeme/law/alfredlinux-com-source-live/iso-output/' . $gaIsoBasename . '.iso';
$isoName = $gaIsoBasename . '.iso';

if (!is_readable($isoPath)) {
    http_response_code(404);
    echo 'ISO not found';
    exit;
}

$size = filesize($isoPath);

// CORS headers for WebTorrent browser requests
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = ['https://alfredlinux.com', 'https://www.alfredlinux.com'];
if (in_array($origin, $allowed, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
}
header('Access-Control-Allow-Methods: GET, HEAD, OPTIONS');
header('Access-Control-Allow-Headers: Range');
header('Access-Control-Expose-Headers: Content-Range, Content-Length, Accept-Ranges');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

header('Accept-Ranges: bytes');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $isoName . '"');
header('Cache-Control: public, max-age=86400');

// Handle Range requests (critical for WebTorrent webseed)
$start = 0;
$end = $size - 1;

if (isset($_SERVER['HTTP_RANGE'])) {
    // Parse Range header: bytes=start-end
    if (preg_match('/bytes=(\d+)-(\d*)/', $_SERVER['HTTP_RANGE'], $m)) {
        $start = (int) $m[1];
        $end = ($m[2] !== '') ? (int) $m[2] : $size - 1;

        if ($start > $end || $start >= $size) {
            http_response_code(416); // Range Not Satisfiable
            header("Content-Range: bytes */$size");
            exit;
        }

        http_response_code(206); // Partial Content
        header("Content-Range: bytes $start-$end/$size");
    }
} else {
    http_response_code(200);
}

$length = $end - $start + 1;
header("Content-Length: $length");

// Stream the requested range
$fp = fopen($isoPath, 'rb');
if ($fp === false) {
    http_response_code(500);
    echo 'Cannot open ISO';
    exit;
}

fseek($fp, $start);
$remaining = $length;
$chunkSize = 8192; // 8 KB chunks

while ($remaining > 0 && !feof($fp)) {
    $read = min($chunkSize, $remaining);
    $data = fread($fp, $read);
    if ($data === false) break;
    echo $data;
    flush();
    $remaining -= strlen($data);
}

fclose($fp);
