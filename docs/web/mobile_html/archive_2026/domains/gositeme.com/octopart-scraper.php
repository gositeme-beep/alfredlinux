<?php
require_once __DIR__ . '/includes/auth-gate.inc.php';

$baseDirCandidates = [
    '/home/gositeme/octopart_scraper',
    dirname(__DIR__) . '/octopart_scraper',
    __DIR__ . '/../octopart_scraper',
    __DIR__ . '/octopart_scraper',
];

$baseDir = null;
foreach ($baseDirCandidates as $candidate) {
    if (is_file($candidate . '/scrape_octopart.py')) {
        $baseDir = $candidate;
        break;
    }
}

$runtimeMissingReason = '';
if ($baseDir === null) {
    $runtimeMissingReason = 'Checked: ' . implode(', ', $baseDirCandidates);
    // Default path for rendering, but runtime check will fail until fixed.
    $baseDir = $baseDirCandidates[0];
}

$uploadDir = $baseDir . '/uploads';
$scriptPath = $baseDir . '/scrape_octopart.py';
$outputPath = $baseDir . '/octopart_results.csv';

$pythonCandidates = [
    $baseDir . '/.venv/bin/python',
    'python3',
    '/usr/bin/env python3',
    'python',
];

$message = '';
$messageType = '';
$logs = [];

if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0775, true);
}

if (isset($_GET['download']) && $_GET['download'] === '1') {
    if (is_file($outputPath)) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="octopart_results.csv"');
        header('Content-Length: ' . filesize($outputPath));
        readfile($outputPath);
        exit;
    }
    http_response_code(404);
    echo 'No output file available.';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $partsText = trim((string)($_POST['parts_text'] ?? ''));
    if (
        (
            !isset($_FILES['parts_file']) ||
            !is_array($_FILES['parts_file']) ||
            ($_FILES['parts_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE
        ) && $partsText === ''
    ) {
        $message = 'Upload a file or paste part numbers.';
        $messageType = 'error';
    } else {
        $inputPath = '';
        if ($partsText !== '') {
            $inputPath = $uploadDir . '/' . date('Ymd_His') . '_pasted_parts.txt';
            @file_put_contents($inputPath, $partsText);
        } else {
            $tmpName = $_FILES['parts_file']['tmp_name'];
            $origName = basename((string)($_FILES['parts_file']['name'] ?? 'parts.data'));
            $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $origName);
            if ($safeName === '' || $safeName === null) {
                $safeName = 'parts.data';
            }
            $inputPath = $uploadDir . '/' . date('Ymd_His') . '_' . $safeName;
            if (!move_uploaded_file($tmpName, $inputPath)) {
                $message = 'Could not save uploaded file.';
                $messageType = 'error';
            }
        }

        if ($messageType === 'error') {
            // Stop here if upload save failed.
        } elseif (!is_file($scriptPath)) {
            $details = [];
            if (!is_file($scriptPath)) {
                $details[] = 'Missing scraper script: ' . $scriptPath;
            }
            if ($runtimeMissingReason !== '') {
                $details[] = $runtimeMissingReason;
            }
            $message = 'Scraper runtime not found. ' . implode(' | ', $details);
            $messageType = 'error';
        } else {
            $runOk = false;
            $allLogs = [];
            foreach ($pythonCandidates as $pyCmd) {
                $cmd = $pyCmd
                    . ' ' . escapeshellarg($scriptPath)
                    . ' --input ' . escapeshellarg($inputPath)
                    . ' --output ' . escapeshellarg($outputPath)
                    . ' --timeout-ms 12000 --delay-ms 700'
                    . ' 2>&1';

                $exitCode = 1;
                $attemptLogs = [];
                @exec($cmd, $attemptLogs, $exitCode);
                $allLogs[] = '--- Attempt: ' . $pyCmd . ' (exit ' . $exitCode . ') ---';
                foreach ($attemptLogs as $line) {
                    $allLogs[] = $line;
                }

                if ($exitCode === 0 && is_file($outputPath)) {
                    $runOk = true;
                    break;
                }
            }

            $logs = $allLogs;
            if ($runOk) {
                $message = 'Scrape completed. Download the latest CSV below.';
                $messageType = 'success';
            } else {
                $joinedLogs = strtolower(implode("\n", $logs));
                if (strpos($joinedLogs, 'wrapper/metadata file') !== false) {
                    $message = 'Your .data upload is a wrapper file, not the actual part list. Upload the real .txt/.csv/.xlsx file.';
                } else {
                    $message = 'Scrape failed. Python runtime may be unavailable for PHP exec. Review logs below.';
                }
                $messageType = 'error';
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Octopart Scraper</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <style>
    :root {
      --bg: #0a0f1a;
      --card: #10192b;
      --card2: #0f1727;
      --text: #e8eefc;
      --muted: #94a3b8;
      --border: #1f2a44;
      --primary: #3b82f6;
      --ok: #16a34a;
      --err: #dc2626;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
      background: radial-gradient(1200px 600px at 20% -20%, #16213a 0%, var(--bg) 60%);
      color: var(--text);
      min-height: 100vh;
    }
    .wrap { max-width: 980px; margin: 0 auto; padding: 28px 16px 50px; }
    .head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 18px; }
    .head h1 { margin: 0; font-size: 1.35rem; font-weight: 800; }
    .head a { color: var(--muted); text-decoration: none; font-size: .92rem; }
    .card {
      background: linear-gradient(180deg, var(--card), var(--card2));
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 16px;
      margin-bottom: 14px;
    }
    .muted { color: var(--muted); font-size: .9rem; }
    .msg {
      padding: 10px 12px;
      border-radius: 10px;
      border: 1px solid transparent;
      margin-bottom: 10px;
      font-size: .92rem;
    }
    .msg.ok { background: rgba(22,163,74,.12); border-color: rgba(22,163,74,.4); }
    .msg.err { background: rgba(220,38,38,.12); border-color: rgba(220,38,38,.4); }
    form { display: grid; gap: 12px; }
    input[type=file] {
      width: 100%;
      padding: 10px;
      border: 1px dashed var(--border);
      border-radius: 10px;
      background: rgba(255,255,255,.02);
      color: var(--text);
    }
    .row { display: flex; flex-wrap: wrap; gap: 10px; }
    button, .btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      border: 1px solid transparent;
      border-radius: 10px;
      padding: 10px 14px;
      font-weight: 700;
      font-size: .9rem;
      cursor: pointer;
      text-decoration: none;
    }
    button { background: var(--primary); color: #fff; }
    .btn.secondary { background: transparent; border-color: var(--border); color: var(--text); }
    pre {
      margin: 0;
      max-height: 360px;
      overflow: auto;
      background: #060b14;
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 12px;
      color: #dbeafe;
      font-size: .82rem;
      line-height: 1.4;
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="head">
      <h1><i class="fas fa-search"></i> Octopart Scraper</h1>
      <a href="/dashboard"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <div class="card">
      <p class="muted">
        Upload a parts file and run scraper for updated <b>price</b>, <b>quantity</b>, and <b>vendor</b>.
        Supported files: <code>.data</code>, <code>.txt</code>, <code>.csv</code>, <code>.xlsx</code>.
      </p>
      <?php if ($message !== ''): ?>
        <div class="msg <?= $messageType === 'success' ? 'ok' : 'err' ?>">
          <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
      <?php endif; ?>
      <form method="post" enctype="multipart/form-data">
        <input type="file" name="parts_file">
        <textarea
          name="parts_text"
          rows="7"
          placeholder="Or paste part numbers here, one per line..."
          style="width:100%;padding:10px;border:1px dashed var(--border);border-radius:10px;background:rgba(255,255,255,.02);color:var(--text);resize:vertical;"
        ></textarea>
        <div class="row">
          <button type="submit"><i class="fas fa-play"></i> Run Scrape</button>
          <?php if (is_file($outputPath)): ?>
            <a class="btn secondary" href="/octopart-scraper.php?download=1"><i class="fas fa-download"></i> Download Latest CSV</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <?php if (!empty($logs)): ?>
      <div class="card">
        <h3 style="margin:0 0 10px 0;font-size:1rem;">Run Logs</h3>
        <pre><?= htmlspecialchars(implode("\n", $logs), ENT_QUOTES, 'UTF-8') ?></pre>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>
