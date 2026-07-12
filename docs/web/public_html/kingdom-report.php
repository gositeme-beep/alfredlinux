<?php
$reportPath = '/home/gositeme/law/SESSION TEXTS/older session texts because they got deelted so they may have a newer created date on this server/THE KINGDOM — STATUS REPORT.txt';
$report = '';
$lastUpdated = '';

if (is_readable($reportPath)) {
    $report = file_get_contents($reportPath);
    $lastUpdated = date('Y-m-d H:i:s T', filemtime($reportPath));
} else {
    $report = "Latest kingdom status report file was not readable on this server.";
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Kingdom Status | Alfred Linux</title>
  <style>
    body { margin: 0; font-family: Georgia, serif; background: #0e1116; color: #efe7d2; }
    .wrap { max-width: 1000px; margin: 0 auto; padding: 32px 20px 56px; }
    h1 { margin: 0 0 8px; font-size: 34px; }
    .meta { color: #c2b792; margin-bottom: 24px; }
    .card { background: #151b22; border: 1px solid #2c3745; border-radius: 12px; padding: 18px; }
    pre { white-space: pre-wrap; word-break: break-word; line-height: 1.45; font-family: Consolas, monospace; font-size: 13px; margin: 0; }
    a { color: #d9bf7a; }
  </style>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/img/favicon/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/img/favicon/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#D4AF37">
</head>
<body>
  <div class="wrap">
    <h1>Kingdom Status</h1>
    <div class="meta">
      Latest build source: THE KINGDOM — STATUS REPORT.txt
      <?php if ($lastUpdated): ?>
      <br>Last updated: <?php echo htmlspecialchars($lastUpdated, ENT_QUOTES, 'UTF-8'); ?>
      <?php endif; ?>
    </div>
    <div class="card">
      <pre><?php echo htmlspecialchars($report, ENT_QUOTES, 'UTF-8'); ?></pre>
    </div>
    <p style="margin-top:16px;"><a href="/">Back to Alfred Linux</a></p>
  </div>
</body>
</html>
