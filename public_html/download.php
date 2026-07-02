<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/includes/ga-release-state.php';

// Same covenant session as covenant.php + downloads/iso.php — no silent bypass.
if (empty($_SESSION['akjv_accepted'])) {
    $next = $_SERVER['REQUEST_URI'] ?? '/download';
    if (!is_string($next) || $next === '' || strncmp($next, '/', 1) !== 0 || str_contains($next, "\n") || str_contains($next, "\r")) {
        $next = '/download';
    }
    header('Location: /covenant?next=' . rawurlencode($next));
    exit;
}

/**
 * Alfred Linux 7.77 — Kingdom of God Edition — P2P Download Page
 * Browser-based WebTorrent client — no client install needed.
 * When download completes, users are encouraged to keep seeding.
 */
header('Cache-Control: no-cache, no-store, must-revalidate');
$pageTitle = "Download Alfred Linux 7.77 — Kingdom of God Edition";
$isoName   = $gaIsoBasename;
// D5 honesty: show measured binary GiB when the ISO exists on disk (follows symlinks).
$gaIsoBytesPath = __DIR__ . '/downloads/' . $isoName . '.iso';
$GA_ISO_MIN_BYTES = (int) (90 * pow(1024, 3));
$isoSize          = '91 GiB compressed (Alpha Matrix, ' . date('Ymd') . ')';
if (is_readable($gaIsoBytesPath)) {
    $gaIsoBytes = (int) @filesize($gaIsoBytesPath);
    if ($gaIsoBytes > 0) {
        $gib               = $gaIsoBytes / pow(1024, 3);
        $isoSizeMeasured = sprintf('%.2f GiB monolithic binary', $gib);
        $isoSize           = $gaIsoBytes >= $GA_ISO_MIN_BYTES
            ? $isoSizeMeasured . ' (God-Tier Matrix Target Met)'
            : $isoSizeMeasured . ' (Warning: Payload suspiciously small)';
    }
}
$version   = '7.77 Alpha Matrix "Kingdom of God Edition" — Kernel 7.0.12';

// Detached GPG signature for this exact ISO basename (Omahon — do not claim "GPG signed" without it).
$gaIsoDetachSigPath = __DIR__ . '/downloads/' . $isoName . '.iso.asc';
$gaIsoDetachSigReady = is_readable($gaIsoDetachSigPath) && (int) @filesize($gaIsoDetachSigPath) > 64;

// Live P2P / WebTorrent / magnet only when both GA is published and the kill switch is on.
$gaDownloadOfferLive = $finalGaIsoPublished && $gaP2pDownloadsEnabled;
// Hero presence subtitle: must not say “waiting for launch” after GA is published.
$presenceHeroPhrase = $finalGaIsoPublished
  ? 'Children of God on this page with you now'
  : 'Children of God waiting for launch';
$torrentURL = $gaDownloadOfferLive ? "/downloads/{$isoName}.iso.torrent" : '';
$magnetDn = rawurlencode($isoName . '.iso');
$magnetURI  = $gaDownloadOfferLive
  ? 'magnet:?xt=urn:btih:' . $gaTorrentBtihHex . '&dn=' . $magnetDn . '&tr=wss%3A%2F%2Falfredlinux.com%2Fannounce&tr=wss%3A%2F%2Ftracker.openwebtorrent.com&tr=udp%3A%2F%2Ftracker.opentrackr.org%3A1337%2Fannounce&ws=https%3A%2F%2Falfredlinux.com%2Fdownloads%2F' . $isoName . '.iso'
  : '';

// GA download window: Unix time when countdown hits zero (edit includes/download-countdown-end.txt to reset / extend).
$downloadCountdownEndFile = __DIR__ . '/includes/download-countdown-end.txt';
if (is_readable($downloadCountdownEndFile)) {
  $downloadCountdownEndsAt = (int) trim((string) file_get_contents($downloadCountdownEndFile));
  if ($downloadCountdownEndsAt < 1) {
    $downloadCountdownEndsAt = time() + 7200;
    @file_put_contents($downloadCountdownEndFile, (string) $downloadCountdownEndsAt, LOCK_EX);
  }
} else {
  $downloadCountdownEndsAt = time() + 7200;
  @file_put_contents($downloadCountdownEndFile, (string) $downloadCountdownEndsAt, LOCK_EX);
}

$isoFileForCommands = $finalGaIsoPublished
  ? ($isoName . '.iso')
  : 'AlfredLinux-Alpha-Matrix-7.77-x86_64.iso';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <meta name="description" content="Download Alfred Linux 7.77 Alpha Matrix via peer-to-peer. Zero-trust AppArmor wrappers, offline AI supercomputing, and the AKJV Bible forged into an immutable core. God's number on every byte.">
  <meta property="og:title" content="Download Alfred Linux 7.77 — Alpha Matrix">
  <meta property="og:description" content="Download Alfred Linux 7.77 Alpha Matrix via P2P. Zero-trust AppArmor wrappers, offline AI supercomputing, and the AKJV Bible forged into an immutable core.">
  <meta property="og:url" content="https://alfredlinux.com/download">
  <meta property="og:image" content="https://alfredlinux.com/og-download.png">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:type" content="website">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="Download Alfred Linux 7.77 — Kingdom of God Edition">
  <meta name="twitter:description" content="Download Alfred Linux 7.77 Alpha Matrix via P2P. Zero-trust AppArmor wrappers, offline AI supercomputing, and the AKJV Bible forged into an immutable core.">
  <meta name="twitter:image" content="https://alfredlinux.com/og-download.png">
  <link rel="canonical" href="https://alfredlinux.com/download">
  <link rel="icon" href="/favicon.ico">
  <link rel="stylesheet" href="/assets/css/nav.css">
  <style>
    :root {
      --bg:       #0a0a0f;
      --surface:  #12121a;
      --border:   #1e1e2e;
      --accent:   #6c5ce7;
      --accent2:  #00cec9;
      --gold:     #facc15;
      --gold-light: #fde68a;
      --gold-dark: #d97706;
      --text:     #e0e0e0;
      --dim:      #888;
      --success:  #00b894;
      --danger:   #e17055;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      overflow-x: hidden;
    }

    /* ── Matrix Augmentations ─────────────────── */
    .glitch {
      position: relative;
      display: inline-block;
      font-weight: bold;
    }
    .glitch::before, .glitch::after {
      content: attr(data-text);
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: var(--bg);
      clip-path: polygon(0 0, 100% 0, 100% 45%, 0 45%);
    }
    .glitch::before {
      left: 2px;
      text-shadow: -1px 0 var(--danger);
      animation: glitch-anim 2s infinite linear alternate-reverse;
    }
    .glitch::after {
      left: -2px;
      text-shadow: -1px 0 var(--accent2);
      clip-path: polygon(0 80%, 100% 20%, 100% 100%, 0 100%);
      animation: glitch-anim 3s infinite linear alternate-reverse;
    }
    @keyframes glitch-anim {
      0% { clip-path: polygon(0 2%, 100% 2%, 100% 5%, 0 5%); transform: translate(0); }
      20% { clip-path: polygon(0 15%, 100% 15%, 100% 15%, 0 15%); transform: translate(-2px, 2px); }
      40% { clip-path: polygon(0 10%, 100% 10%, 100% 20%, 0 20%); transform: translate(2px, -2px); }
      60% { clip-path: polygon(0 50%, 100% 50%, 100% 55%, 0 55%); transform: translate(0); }
      80% { clip-path: polygon(0 70%, 100% 70%, 100% 80%, 0 80%); transform: translate(-2px, -2px); }
      100% { clip-path: polygon(0 80%, 100% 80%, 100% 90%, 0 90%); transform: translate(2px, 2px); }
    }

    .matrix-tooltip {
      position: relative;
      display: inline-block;
      border-bottom: 1px dashed var(--gold);
      cursor: help;
    }
    .matrix-tooltip .tooltip-text {
      visibility: hidden;
      width: 280px;
      background: rgba(10, 10, 15, 0.95);
      color: var(--gold-light);
      text-align: left;
      border: 1px solid var(--gold);
      border-radius: 6px;
      padding: 10px;
      position: absolute;
      z-index: 10;
      bottom: 125%;
      left: 50%;
      transform: translateX(-50%);
      opacity: 0;
      transition: opacity 0.3s;
      font-size: 0.85rem;
      font-family: monospace;
      box-shadow: 0 4px 15px rgba(250, 204, 21, 0.2);
    }
    .matrix-tooltip:hover .tooltip-text {
      visibility: visible;
      opacity: 1;
    }


    /* ── Hero ─────────────────────────────────── */
    .hero {
      text-align: center;
      padding: 4rem 2rem 2rem;
    }
    .hero h1 {
      font-size: clamp(2rem, 5vw, 3.5rem);
      font-weight: 800;
      line-height: 1.1;
      margin-bottom: 1rem;
    }
    .hero h1 .glow {
      background: linear-gradient(135deg, var(--accent), var(--accent2));
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    }
    .hero p {
      color: var(--dim);
      font-size: 1.15rem;
      max-width: 640px;
      margin: 0 auto 2rem;
      line-height: 1.6;
    }
    .badge {
      display: inline-block;
      background: rgba(108,92,231,0.15);
      color: var(--accent);
      padding: 0.3rem 0.8rem;
      border-radius: 999px;
      font-size: 0.8rem;
      font-weight: 600;
      border: 1px solid rgba(108,92,231,0.3);
      margin-bottom: 1.5rem;
    }

    /* ── Download Card ────────────────────────── */
    .dl-card {
      max-width: 720px;
      margin: 0 auto 3rem;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 2.5rem;
      position: relative;
      overflow: hidden;
    }
    .dl-card::before {
      content: '';
      position: absolute; top: 0; left: 0; right: 0; height: 3px;
      background: linear-gradient(90deg, var(--accent), var(--accent2), var(--gold));
    }

    /* Start state */
    #start-section { text-align: center; }
    #start-section h2 { font-size: 1.5rem; margin-bottom: 0.5rem; }
    #start-section .meta { color: var(--dim); font-size: 0.9rem; margin-bottom: 1.5rem; }

    .btn-torrent {
      display: inline-flex; align-items: center; gap: 0.5rem;
      background: linear-gradient(135deg, var(--accent), #5a4bd1);
      color: #fff;
      border: none; border-radius: 12px;
      padding: 1rem 2.5rem;
      font-size: 1.1rem; font-weight: 700;
      cursor: pointer;
      transition: transform 0.15s, box-shadow 0.15s;
    }
    .btn-torrent:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 30px rgba(108,92,231,0.4);
    }
    .btn-torrent svg { width: 22px; height: 22px; }

    .alt-links {
      margin-top: 1rem;
      font-size: 0.85rem; color: var(--dim);
    }
    .alt-links a { color: var(--accent); text-decoration: none; }
    .alt-links a:hover { text-decoration: underline; }

    .btn-direct {
      display: inline-flex; align-items: center; gap: 0.5rem;
      background: rgba(255,255,255,0.08);
      color: #ccc;
      border: 1px solid rgba(255,255,255,0.15); border-radius: 12px;
      padding: 0.75rem 2rem;
      font-size: 1rem; font-weight: 600;
      cursor: pointer; text-decoration: none;
      transition: transform 0.15s, background 0.15s;
      margin-top: 0.75rem;
    }
    .btn-direct:hover {
      transform: translateY(-1px);
      background: rgba(255,255,255,0.12);
      color: #fff;
    }
    .btn-direct svg { width: 18px; height: 18px; }

    /* Progress state */
    #progress-section { display: none; }
    .progress-bar-outer {
      width: 100%;
      height: 28px;
      background: rgba(255,255,255,0.05);
      border-radius: 14px;
      overflow: hidden;
      margin-bottom: 1.5rem;
      position: relative;
    }
    .progress-bar-inner {
      height: 100%;
      width: 0%;
      background: linear-gradient(90deg, var(--accent), var(--accent2));
      border-radius: 14px;
      transition: width 0.5s ease;
      position: relative;
    }
    .progress-bar-inner::after {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0; bottom: 0;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
      animation: shimmer 2s infinite;
    }
    @keyframes shimmer { 0%{transform:translateX(-100%)} 100%{transform:translateX(100%)} }

    .progress-pct {
      position: absolute;
      top: 50%; left: 50%; transform: translate(-50%,-50%);
      font-weight: 700; font-size: 0.85rem; z-index: 2;
      text-shadow: 0 1px 3px rgba(0,0,0,0.5);
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
      gap: 1rem;
      margin-bottom: 1.5rem;
    }
    .stat-box {
      background: rgba(255,255,255,0.03);
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 0.8rem 1rem;
      text-align: center;
    }
    .stat-box .label { font-size: 0.75rem; color: var(--dim); text-transform: uppercase; letter-spacing: 0.05em; }
    .stat-box .value { font-size: 1.3rem; font-weight: 700; margin-top: 0.3rem; }

    /* Complete / Seeding state */
    #seed-section { display: none; text-align: center; }
    .seed-banner {
      background: linear-gradient(135deg, rgba(0,184,148,0.1), rgba(108,92,231,0.1));
      border: 1px solid rgba(0,184,148,0.3);
      border-radius: 16px;
      padding: 2.5rem;
      margin-bottom: 2rem;
    }
    .seed-banner h2 {
      font-size: 1.8rem;
      margin-bottom: 0.5rem;
      background: linear-gradient(135deg, var(--success), var(--accent2));
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    }
    .seed-banner p {
      color: var(--text);
      font-size: 1.1rem;
      line-height: 1.6;
      max-width: 520px;
      margin: 0 auto;
    }
    .seed-banner .big-msg {
      font-size: 1.5rem;
      font-weight: 800;
      color: var(--gold);
      margin-top: 1.5rem;
      -webkit-text-fill-color: unset;
    }

    .seed-stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
      gap: 1rem;
      margin-bottom: 2rem;
    }

    .seed-timer {
      font-size: 2.5rem;
      font-weight: 800;
      font-variant-numeric: tabular-nums;
      background: linear-gradient(135deg, var(--gold), var(--accent));
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
      margin-bottom: 0.5rem;
    }
    .seed-timer-label { color: var(--dim); font-size: 0.85rem; }

    .btn-save {
      display: inline-flex; align-items: center; gap: 0.5rem;
      background: var(--success);
      color: #fff;
      border: none; border-radius: 12px;
      padding: 0.8rem 2rem;
      font-size: 1rem; font-weight: 700;
      cursor: pointer;
      margin-top: 1rem;
    }
    .btn-save:hover { opacity: 0.9; }

    .share-btn {
      display: inline-flex; align-items: center; gap: 0.5rem;
      background: rgba(108,92,231,0.15);
      color: var(--accent);
      border: 1px solid rgba(108,92,231,0.3);
      border-radius: 10px;
      padding: 0.6rem 1.5rem;
      font-size: 0.9rem; font-weight: 600;
      cursor: pointer;
      margin: 0.5rem;
      transition: all 0.2s;
    }
    .share-btn:hover { background: rgba(108,92,231,0.25); }

    /* ── Community Section ────────────────────── */
    .community {
      max-width: 720px;
      margin: 0 auto 4rem;
      text-align: center;
    }
    .community h2 { font-size: 1.8rem; margin-bottom: 1rem; }
    .community p { color: var(--dim); max-width: 540px; margin: 0 auto 2rem; line-height: 1.6; }

    .swarm-vis {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 2rem;
      min-height: 200px;
      position: relative;
      overflow: hidden;
    }
    .swarm-vis canvas { width: 100%; height: 200px; }

    .swarm-counter {
      font-size: 3rem;
      font-weight: 800;
      background: linear-gradient(135deg, var(--accent), var(--accent2));
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    }
    .swarm-label { color: var(--dim); font-size: 0.9rem; }

    /* ── How It Works ─────────────────────────── */
    .how-it-works {
      max-width: 720px;
      margin: 0 auto 4rem;
      padding: 0 2rem;
    }
    .how-it-works h2 { text-align: center; font-size: 1.5rem; margin-bottom: 2rem; }
    .steps {
      display: grid; gap: 1.5rem;
    }
    .step {
      display: flex; gap: 1.2rem; align-items: flex-start;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 1.5rem;
    }
    .step-num {
      width: 36px; height: 36px;
      background: linear-gradient(135deg, var(--accent), var(--accent2));
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-weight: 800; font-size: 0.9rem;
      flex-shrink: 0;
    }
    .step h3 { font-size: 1rem; margin-bottom: 0.3rem; }
    .step p { color: var(--dim); font-size: 0.9rem; line-height: 1.5; }

    /* ── Footer ───────────────────────────────── */
    footer {
      text-align: center;
      padding: 2rem;
      border-top: 1px solid var(--border);
      color: var(--dim);
      font-size: 0.85rem;
    }
    footer a { color: var(--accent); text-decoration: none; }

    /* ── Error state ──────────────────────────── */
    .error-box {
      display: none;
      background: rgba(225,112,85,0.1);
      border: 1px solid rgba(225,112,85,0.3);
      border-radius: 10px;
      padding: 1rem 1.5rem;
      margin-top: 1rem;
      color: var(--danger);
      font-size: 0.9rem;
    }

    /* ── Flash to USB Guide ────────────────── */
    .flash-guide {
      max-width: 720px;
      margin: 0 auto 3rem;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 2.5rem;
      display: none;
    }
    .flash-guide.visible { display: block; }
    .btn-flash-next {
      display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem;
      margin-top: 0.5rem;
      padding: 0.65rem 1.35rem;
      border-radius: 12px;
      border: 1px solid rgba(250, 204, 21, 0.45);
      background: rgba(250, 204, 21, 0.12);
      color: var(--gold-light);
      font-weight: 700;
      font-size: 0.9rem;
      cursor: pointer;
      font-family: inherit;
    }
    .btn-flash-next:hover {
      background: rgba(250, 204, 21, 0.22);
      border-color: rgba(250, 204, 21, 0.65);
    }
    .flash-guide h2 {
      font-size: 1.5rem;
      margin-bottom: 0.5rem;
      text-align: center;
    }
    .flash-guide .subtitle {
      color: var(--dim);
      text-align: center;
      font-size: 0.95rem;
      margin-bottom: 2rem;
    }
    .flash-tabs {
      display: flex;
      gap: 0.5rem;
      margin-bottom: 1.5rem;
      border-bottom: 1px solid var(--border);
      padding-bottom: 0.5rem;
    }
    .flash-tab {
      background: none;
      border: none;
      color: var(--dim);
      font-size: 0.9rem;
      font-weight: 600;
      padding: 0.5rem 1rem;
      cursor: pointer;
      border-radius: 8px 8px 0 0;
      transition: all 0.2s;
    }
    .flash-tab:hover { color: var(--text); }
    .flash-tab.active {
      color: var(--accent);
      background: rgba(108,92,231,0.1);
      border-bottom: 2px solid var(--accent);
    }
    .flash-panel { display: none; }
    .flash-panel.active { display: block; }
    .flash-panel h3 {
      font-size: 1.1rem;
      margin-bottom: 0.8rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .flash-panel ol {
      list-style: decimal;
      padding-left: 1.5rem;
      color: var(--text);
      line-height: 2;
      font-size: 0.95rem;
    }
    .flash-panel ol li { margin-bottom: 0.3rem; }
    .flash-panel a {
      color: var(--accent);
      text-decoration: none;
      font-weight: 600;
    }
    .flash-panel a:hover { text-decoration: underline; }
    .flash-cmd {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      background: rgba(0,0,0,0.4);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 0.6rem 1rem;
      margin: 0.5rem 0;
      font-family: 'SF Mono', 'Fira Code', monospace;
      font-size: 0.85rem;
      color: var(--accent2);
      overflow-x: auto;
    }
    .flash-cmd code { flex: 1; white-space: nowrap; }
    .flash-cmd button {
      background: none;
      border: 1px solid var(--border);
      color: var(--dim);
      border-radius: 6px;
      padding: 0.3rem 0.6rem;
      cursor: pointer;
      font-size: 0.75rem;
      flex-shrink: 0;
    }
    .flash-cmd button:hover { color: var(--text); border-color: var(--accent); }
    .flash-warn {
      background: rgba(253,203,110,0.1);
      border: 1px solid rgba(253,203,110,0.25);
      border-radius: 8px;
      padding: 0.7rem 1rem;
      color: var(--gold);
      font-size: 0.85rem;
      margin-top: 0.8rem;
    }

    /* ── Launch Week Celebration ─────────────── */
    #celebration-canvas {
      position: fixed;
      top: 0; left: 0;
      width: 100vw; height: 100vh;
      pointer-events: none;
      z-index: 9999;
    }
    .launch-week-banner {
      max-width: 760px;
      margin: 0 auto 2rem;
      padding: 1.5rem 2rem;
      text-align: center;
      background: linear-gradient(135deg, rgba(250,204,21,0.12), rgba(108,92,231,0.12), rgba(0,206,201,0.08));
      border: 2px solid;
      border-image: linear-gradient(135deg, var(--gold), var(--accent), var(--accent2)) 1;
      border-radius: 0;
      position: relative;
      overflow: hidden;
      animation: banner-glow 3s ease-in-out infinite;
    }
    .launch-week-banner::before {
      content: '';
      position: absolute;
      top: -50%; left: -50%;
      width: 200%; height: 200%;
      background: conic-gradient(from 0deg, transparent 0%, rgba(250,204,21,0.05) 25%, transparent 50%, rgba(108,92,231,0.05) 75%, transparent 100%);
      animation: banner-rotate 8s linear infinite;
    }
    @keyframes banner-rotate {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    @keyframes banner-glow {
      0%, 100% { box-shadow: 0 0 20px rgba(250,204,21,0.15), 0 0 40px rgba(108,92,231,0.1); }
      50% { box-shadow: 0 0 35px rgba(250,204,21,0.3), 0 0 60px rgba(108,92,231,0.2), 0 0 80px rgba(0,206,201,0.1); }
    }
    .launch-week-title {
      font-size: clamp(1.4rem, 4vw, 2.2rem);
      font-weight: 900;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      background: linear-gradient(135deg, var(--gold-light), var(--gold), #ff6b6b, var(--accent), var(--accent2), var(--gold-light));
      background-size: 300% 300%;
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      animation: title-shimmer 4s ease-in-out infinite;
      position: relative;
      z-index: 1;
      margin-bottom: 0.5rem;
    }
    @keyframes title-shimmer {
      0%   { background-position: 0% 50%; }
      50%  { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }
    .launch-week-subtitle {
      color: var(--gold-light);
      font-size: 1.05rem;
      font-weight: 600;
      position: relative;
      z-index: 1;
      line-height: 1.6;
    }
    .launch-week-days {
      display: flex;
      justify-content: center;
      gap: 0.6rem;
      margin-top: 1rem;
      flex-wrap: wrap;
      position: relative;
      z-index: 1;
    }
    .launch-day {
      padding: 0.4rem 0.9rem;
      border-radius: 999px;
      font-size: 0.78rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.1);
      color: var(--dim);
      transition: all 0.3s;
    }
    .launch-day.today {
      background: linear-gradient(135deg, rgba(250,204,21,0.2), rgba(108,92,231,0.15));
      border-color: var(--gold);
      color: var(--gold-light);
      box-shadow: 0 0 12px rgba(250,204,21,0.3);
      animation: day-pulse 2s ease-in-out infinite;
    }
    .launch-day.past {
      background: rgba(0,184,148,0.1);
      border-color: rgba(0,184,148,0.3);
      color: var(--success);
    }
    .launch-day.future {
      border-color: rgba(108,92,231,0.3);
      color: var(--accent);
    }
    @keyframes day-pulse {
      0%, 100% { box-shadow: 0 0 12px rgba(250,204,21,0.3); }
      50% { box-shadow: 0 0 24px rgba(250,204,21,0.5), 0 0 40px rgba(250,204,21,0.2); }
    }

    /* Floating emoji particles */
    .celebration-particle {
      position: fixed;
      pointer-events: none;
      z-index: 9998;
      font-size: 1.5rem;
      animation: particle-float linear forwards;
      will-change: transform, opacity;
    }
    @keyframes particle-float {
      0%   { transform: translateY(0) rotate(0deg) scale(1); opacity: 1; }
      50%  { opacity: 0.8; }
      100% { transform: translateY(-100vh) rotate(720deg) scale(0.3); opacity: 0; }
    }

    @media (max-width: 600px) {
      .dl-card { margin: 0 1rem 3rem; padding: 1.5rem; }
      .flash-guide { margin: 0 1rem 3rem; padding: 1.5rem; }
      .stats-grid { grid-template-columns: 1fr 1fr; }
      .hero { padding: 2rem 1rem 1rem; }
      .flash-tabs { flex-wrap: wrap; }
      .launch-week-banner { margin: 0 1rem 2rem; padding: 1.2rem 1rem; }
    }
  </style>
    <script type="application/ld+json">
    {"@context":"https://schema.org","@type":"SoftwareApplication","name":"Alfred Linux 7.77 — Kingdom of God Edition","operatingSystem":"Linux","applicationCategory":"OperatingSystem","downloadUrl":"https://alfredlinux.com/download","softwareVersion":"7.77","offers":{"@type":"Offer","price":"0","priceCurrency":"USD"},"publisher":{"@type":"Organization","name":"GoSiteMe Inc.","url":"https://gositeme.com"}}
    </script>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/img/favicon/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/img/favicon/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#D4AF37">
</head>
<body>


<?php $currentPage = 'download'; include __DIR__ . '/includes/nav.php'; ?>

<!-- ── Fireworks Canvas ── -->
<canvas id="celebration-canvas"></canvas>

<!-- ── Hero ─────────────────────────────────────── -->
<section class="hero">
  <div class="badge" style="background:linear-gradient(135deg,rgba(250,204,21,0.12),rgba(108,92,231,0.12));border-color:rgba(250,204,21,0.3);color:var(--gold-light);">✝ Kingdom of God Edition · v7.77 · Peer-to-Peer · Swarm-Powered</div>
  <h1>Initiate Matrix Uplink: <span class="glow" style="background:linear-gradient(135deg,var(--gold-light),var(--gold),var(--gold-dark));-webkit-background-clip:text;-webkit-text-fill-color:transparent;">Alfred Linux 7.77</span></h1>
  
  <div class="matrix-reveal">
    <p>Distributed by the swarm, not our servers. Our bandwidth serves our ecosystem.<br>
    The <span class="matrix-tooltip"><strong style="color:var(--gold);">AKJV Bible</strong><span class="tooltip-text">Genesis to Revelation: Incorruptible text compiled directly into the OS kernel.</span></span> (39,482 verses), <strong>27-track worship album</strong>, <strong><?= $gaFrozenIsoHookCount ?> build hooks</strong> (369-hook source tree).<br>
    Sealed by the <span class="matrix-tooltip"><span class="glitch" data-text="Omahon"><strong style="color:var(--danger);">Omahon</strong></span><span class="tooltip-text">38 Active AppArmor Security Sandboxes</span></span> — 38 security modules. The <span class="glitch" data-text="Alpha Matrix" style="color:var(--accent2);">Alpha Matrix</span> is officially ALIVE. Zero-trust AppArmor wrappers, offline AI supercomputing, and the AKJV Bible forged into an immutable core. <?= $gaIsoDetachSigReady ? '<strong>GPG-signed</strong> <code>.iso.asc</code> verified.' : 'Verify with <strong>SHA-256</strong> + <strong>BLAKE3</strong> checksums below.' ?> Incorruptible hashes always.</p>
    <p style="font-size:0.95rem;color:var(--gold-light);max-width:800px;margin:0 auto 0.5rem;line-height:1.6;font-weight:600;">
      42 generations. <?= $gaFrozenIsoHookCount ?> hooks shipped. One Messiah. One Kingdom. The operating system sealed by the breath of God. The <span class="glitch" data-text="Alpha Matrix">Alpha Matrix</span> is officially ALIVE. God's number on every byte.
    </p>
  </div>
  <p style="font-size:0.88rem;color:var(--dim);max-width:640px;margin:0 auto 0.5rem;line-height:1.55;">Filenames say <code>x86_64</code> because that is the industry standard label for <strong>64-bit PCs</strong> with <strong>Intel, AMD,</strong> or compatible CPUs.</p>
  <p style="font-style:italic;color:var(--gold-light);font-size:0.9rem;max-width:500px;margin:0 auto;opacity:0.85;">&ldquo;Ask, and it shall be given you; seek, and you shall find; knock, and it shall be opened to you.&rdquo; &mdash; Matthew 7:7</p>

  <!-- ── Live Visitor Counter ── -->
  <div style="margin-top:2rem;display:inline-flex;align-items:center;gap:0.85rem;background:rgba(250,204,21,0.07);border:1px solid rgba(250,204,21,0.2);border-radius:999px;padding:0.55rem 1.4rem;">
    <span id="presence-dot" style="width:10px;height:10px;border-radius:50%;background:var(--gold);display:inline-block;box-shadow:0 0 0 0 rgba(250,204,21,0.7);animation:presence-pulse 2s infinite;flex-shrink:0;"></span>
    <span style="color:var(--gold-light);font-size:0.95rem;font-weight:600;">
      <span id="presence-count" style="font-size:1.25rem;font-weight:900;background:linear-gradient(135deg,var(--gold-light),var(--gold));-webkit-background-clip:text;-webkit-text-fill-color:transparent;">…</span>
      &nbsp;<span id="presence-label"><?= htmlspecialchars($presenceHeroPhrase) ?></span>
    </span>
  </div>
  <?php if (!$finalGaIsoPublished): ?>
  <p style="margin-top:1.35rem;text-align:center;color:var(--gold-light);font-size:0.95rem;max-width:600px;margin-left:auto;margin-right:auto;line-height:1.6;">
    <strong style="color:var(--gold);font-weight:700;">Downloads are LIVE. Welcome to the Matrix.</strong><br>
    <span style="color:var(--dim);font-size:0.82rem;">The Alpha Matrix has been successfully forged & sealed.</span>
  </p>
<?php else: ?>
<p id="dl-starts-line" style="margin-top:1.35rem;text-align:center;color:var(--dim);font-size:0.9rem;max-width:560px;margin-left:auto;margin-right:auto;line-height:1.55;">
    <span style="color:var(--gold-light);font-weight:600;">Download starts today</span>
    <span id="dl-starts-day" style="color:var(--dim);font-size:0.8rem;"></span>
    <span style="color:var(--text);"> — </span>
    <strong id="dl-starts-today" style="color:var(--gold);font-size:1.15rem;">…</strong>
    <span style="display:block;margin-top:0.4rem;font-size:0.72rem;color:var(--dim);">Montreal calendar day · counted once per browser per day when WebTorrent pulls the first piece, or when someone uses the .torrent / magnet below.</span>
  </p>
<?php endif; ?>
  <style>
    @keyframes presence-pulse {
      0%   { box-shadow: 0 0 0 0 rgba(250,204,21,0.7); }
      70%  { box-shadow: 0 0 0 10px rgba(250,204,21,0); }
      100% { box-shadow: 0 0 0 0 rgba(250,204,21,0); }
    }
  </style>
  <script>
  (function(){
    // One stable ID per browser (localStorage) so extra tabs do not inflate the count.
    var sid = localStorage.getItem('al_presence_sid');
    if (!sid) {
      sid = sessionStorage.getItem('al_presence_sid');
      if (sid) {
        localStorage.setItem('al_presence_sid', sid);
        try { sessionStorage.removeItem('al_presence_sid'); } catch (e) {}
      }
    }
    if (!sid) {
      sid = Math.random().toString(36).slice(2) + Math.random().toString(36).slice(2);
      localStorage.setItem('al_presence_sid', sid);
    }
    function ping() {
      fetch('/api/presence.php?page=download&sid=' + encodeURIComponent(sid))
        .then(function(r){ return r.json(); })
        .then(function(d){
          var el = document.getElementById('presence-count');
          if (!el) return;
          if (typeof d.count === 'number') {
            el.textContent = d.count.toLocaleString();
          } else {
            el.textContent = '—';
          }
        })
        .catch(function(){});
    }
    ping();
    setInterval(ping, 45000);

    window.alfredMontrealYmd = function() {
      return new Intl.DateTimeFormat('en-CA', { timeZone: 'America/Montreal', year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date());
    };
    window.alfredRefreshDownloadStartsToday = function() {
      fetch('/api/download-stats.php?action=today')
        .then(function(r) { return r.json(); })
        .then(function(d) {
          var el = document.getElementById('dl-starts-today');
          var dayEl = document.getElementById('dl-starts-day');
          if (!el) return;
          if (d.ok && typeof d.today === 'number') {
            el.textContent = d.today.toLocaleString();
            if (dayEl && d.day) dayEl.textContent = '(' + d.day + ')';
          } else {
            el.textContent = '—';
          }
        })
        .catch(function() {});
    };
    window.alfredRecordDownloadStart = function() {
      try {
        var sid = localStorage.getItem('al_presence_sid');
        if (!sid || sid.length < 8) return;
        var ymd = window.alfredMontrealYmd();
        if (localStorage.getItem('al_dl_tracked_' + ymd) === '1') return;
        fetch('/api/download-stats.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'record', sid: sid }),
          keepalive: true
        })
          .then(function(r) { return r.json(); })
          .then(function(d) {
            if (d.ok) {
              localStorage.setItem('al_dl_tracked_' + ymd, '1');
            }
          })
          .catch(function() {});
      } catch (e) {}
    };
    window.alfredRefreshDownloadStartsToday();
    setInterval(window.alfredRefreshDownloadStartsToday, 60000);
  })();
  </script>
</section>
<?php @require_once __DIR__ . "/includes/build-status-banner.inc.php"; ?>


<!-- ── LAUNCH WEEK CELEBRATION BANNER ───────────── -->
<div class="launch-week-banner" id="launch-week-banner">
  <div class="launch-week-title">🎆 LAUNCH WEEK — THE KINGDOM IS HERE 🎆</div>
  <div class="launch-week-subtitle">
    Alfred Linux 7.77 — Kingdom of God Edition — GA Launch<br>
    <span style="font-size:0.9rem;color:var(--text);">June 15, 2026 · Erev Shabbat ship week · GA ISO / reseal window: <strong>Mon Jun 15, 6:00 PM Eastern</strong> · Perez-lineage milestone: <strong>Fri May 8</strong> (separate anchor)</span>
  </div>
  <div class="launch-week-days" id="launch-week-days">
    <span class="launch-day today" data-day="2026-06-15">Mon 15 🚀</span>
    <span class="launch-day" data-day="2026-06-16">Tue 16</span>
    <span class="launch-day" data-day="2026-06-17">Wed 17</span>
    <span class="launch-day" data-day="2026-06-18">Thu 18</span>
    <span class="launch-day" data-day="2026-06-19">Fri 19</span>
    <span class="launch-day" data-day="2026-06-20">Sat 20 ⚱️</span>
    <span class="launch-day" data-day="2026-06-21">Sun 21</span>
  </div>
  <p style="position:relative;z-index:1;margin-top:1rem;font-style:italic;color:var(--gold-light);font-size:0.85rem;opacity:0.9;">"The grass withereth, the flower fadeth: but the word of our God shall stand for ever." — Isaiah 40:8</p>
</div>

<!-- ── Download Card ───────────────────────────── -->
<div class="dl-card">

  <div id="start-section">
    <h2 style="background:linear-gradient(135deg,var(--gold-light),var(--gold));-webkit-background-clip:text;-webkit-text-fill-color:transparent;">Alfred Linux 7.77 — Kingdom of God Edition</h2>
    <?php if ($finalGaIsoPublished): ?>
    <p style="color:var(--gold-light);font-size:1.1rem;margin:1rem 0 0.5rem;">The Kingdom of God Edition is ready — download below.</p>
    <?php elseif (!empty($downloadPageShowLaunchCountdown)): ?>
    <?php
      $gaLaunchTs = (new DateTime('2026-06-15 18:00:00', new DateTimeZone('America/Montreal')))->getTimestamp();
    ?>
    <div style="margin:1.5rem 0 1rem;padding:2rem 1.5rem;background:linear-gradient(135deg,rgba(255,215,0,0.08),rgba(99,102,241,0.05));border:2px solid rgba(255,215,0,0.35);border-radius:20px;">
      <p style="color:var(--gold);font-size:1.3rem;font-weight:900;text-align:center;margin-bottom:0.25rem;text-transform:uppercase;letter-spacing:0.04em;">✝ The Kingdom of God is Coming</p>
      <p style="color:var(--gold-light);font-size:0.95rem;text-align:center;margin-bottom:1.5rem;font-style:italic;">&ldquo;Repent: for the kingdom of heaven is at hand.&rdquo; — Matthew 4:17</p>
      <div id="countdown-display" style="display:flex;justify-content:center;gap:1rem;margin:1rem 0;flex-wrap:wrap;">
        <div style="text-align:center;min-width:80px;">
          <div id="cd-days" style="font-size:3rem;font-weight:900;background:linear-gradient(135deg,var(--gold-light),var(--gold));-webkit-background-clip:text;-webkit-text-fill-color:transparent;">00</div>
          <div style="font-size:0.7rem;color:var(--dim);text-transform:uppercase;letter-spacing:2px;">Days</div>
        </div>
        <div style="font-size:3rem;color:var(--gold);opacity:0.3;line-height:1;">:</div>
        <div style="text-align:center;min-width:80px;">
          <div id="cd-hours" style="font-size:3rem;font-weight:900;background:linear-gradient(135deg,var(--gold-light),var(--gold));-webkit-background-clip:text;-webkit-text-fill-color:transparent;">00</div>
          <div style="font-size:0.7rem;color:var(--dim);text-transform:uppercase;letter-spacing:2px;">Hours</div>
        </div>
        <div style="font-size:3rem;color:var(--gold);opacity:0.3;line-height:1;">:</div>
        <div style="text-align:center;min-width:80px;">
          <div id="cd-minutes" style="font-size:3rem;font-weight:900;background:linear-gradient(135deg,var(--gold-light),var(--gold));-webkit-background-clip:text;-webkit-text-fill-color:transparent;">00</div>
          <div style="font-size:0.7rem;color:var(--dim);text-transform:uppercase;letter-spacing:2px;">Minutes</div>
        </div>
        <div style="font-size:3rem;color:var(--gold);opacity:0.3;line-height:1;">:</div>
        <div style="text-align:center;min-width:80px;">
          <div id="cd-seconds" style="font-size:3rem;font-weight:900;background:linear-gradient(135deg,var(--gold-light),var(--gold));-webkit-background-clip:text;-webkit-text-fill-color:transparent;">00</div>
          <div style="font-size:0.7rem;color:var(--dim);text-transform:uppercase;letter-spacing:2px;">Seconds</div>
        </div>
      </div>
      <p style="text-align:center;margin-top:1.25rem;">
        <span style="color:var(--gold);font-size:1.15rem;font-weight:800;">Monday, June 15th, 2026 · 6:00 PM Eastern</span><br>
        <span style="color:var(--dim);font-size:0.85rem;">Alfred Linux v7.77 — Kingdom of God Edition — Alpha Matrix Launch</span>
      </p>
    </div>
    <p id="countdown-done-msg" style="display:none;color:var(--gold);font-weight:900;font-size:1.3rem;text-align:center;margin:1rem 0;padding:1rem;background:rgba(255,215,0,0.08);border-radius:12px;border:1px solid rgba(255,215,0,0.3);">✝ THE KINGDOM IS HERE — Glory to God! Refresh for download links.</p>
    <script>
    (function(){
      var endMs = <?= (int) $gaLaunchTs * 1000 ?>;
      function tick(){
        var diff = Math.max(0, endMs - Date.now());
        var d = Math.floor(diff / 86400000);
        var h = Math.floor((diff % 86400000) / 3600000);
        var m = Math.floor((diff % 3600000) / 60000);
        var s = Math.floor((diff % 60000) / 1000);
        var ed = document.getElementById('cd-days');
        var eh = document.getElementById('cd-hours');
        var em = document.getElementById('cd-minutes');
        var es = document.getElementById('cd-seconds');
        var wrap = document.getElementById('countdown-display');
        var done = document.getElementById('countdown-done-msg');
        if (!eh) return;
        if (diff <= 0) {
          if (ed) ed.textContent = '00';
          eh.textContent = em.textContent = es.textContent = '00';
          if (wrap) wrap.style.opacity = '0.4';
          if (done) done.style.display = 'block';
          return;
        }
        if (ed) ed.textContent = String(d).padStart(2, '0');
        eh.textContent = String(h).padStart(2, '0');
        em.textContent = String(m).padStart(2, '0');
        es.textContent = String(s).padStart(2, '0');
      }
      tick();
      setInterval(tick, 1000);
    })();
    </script>
    <?php else: ?>
    <div style="margin:1rem 0 0.5rem;padding:1.25rem 1.5rem;border-radius:14px;border:1px solid rgba(0,206,201,0.35);background:rgba(0,206,201,0.06);text-align:left;max-width:40rem;margin-left:auto;margin-right:auto;">
      <p style="color:var(--text);font-size:1rem;line-height:1.6;margin:0 0 0.75rem;"><strong>Operator truth:</strong> Public WebTorrent / magnet / .torrent for the GA desktop ISO on this page is <strong>off</strong> until <strong>you</strong> set the flags in <code style="color:var(--accent2);">includes/ga-release-state.php</code>. Alfred Linux can keep shipping as a <strong>private build</strong> — nothing here forces a public “drop” date.</p>
      <p style="color:var(--dim);font-size:0.9rem;line-height:1.55;margin:0 0 0.5rem;">Machine-readable status: <a href="/api/download-status.php" style="color:var(--accent2);"><code>/api/download-status.php</code></a> · Checksums &amp; notes: <a href="/releases" style="color:var(--accent2);">/releases</a> · Roadmap: <a href="/roadmap" style="color:var(--accent2);">/roadmap</a></p>
      <p style="color:var(--dim);font-size:0.82rem;margin:0;">To show a marketing countdown again, set <code>$downloadPageShowLaunchCountdown = true</code> in that same include (use only if you <em>want</em> a timed window).</p>
    </div>
    <?php endif; ?>
    <p style="font-style:italic;color:var(--gold-light);font-size:0.9rem;opacity:0.85;">&ldquo;For everything there is a season, and a time for every matter under heaven.&rdquo; &mdash; Ecclesiastes 3:1</p>
    <div class="meta" style="margin-top:1.5rem;">
      <?php if ($finalGaIsoPublished): ?>
        <?= htmlspecialchars($isoName) ?>.iso · <?= htmlspecialchars($isoSize) ?> · Debian Trixie 13 · Kernel 7.0.12 · <?= htmlspecialchars($gaFrozenIsoHookLabel) ?> · 38 Security Modules · Omahon Seal · AKJV Bible · Worship Album<?= $gaIsoDetachSigReady ? ' · <strong>GPG-signed</strong> (<code>.iso.asc</code>)' : '' ?>
      <?php else: ?>
        Planned filename pattern: <code style="color:var(--accent2);"><?= htmlspecialchars($isoFileForCommands) ?></code> (exact name + hashes when build is frozen) · <?= htmlspecialchars($isoSize) ?> target · Debian Trixie 13 · Kernel 7.0.12 · <?= htmlspecialchars($gaFrozenIsoHookLabel) ?> · 38 Security Modules
      <?php endif; ?>
    </div>
    <?php if ($gaDownloadOfferLive): ?>
    <div id="ga-p2p-links" style="margin-top:1.75rem;display:flex;flex-direction:column;align-items:center;gap:0.75rem;">
      <button type="button" class="btn-torrent" onclick="startDownload()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12M8 11l4 4 4-4"/><path d="M4 21h16"/></svg>
        Start in-browser download (WebTorrent)
      </button>
      <div class="alt-links">
        <a id="alfred-torrent-link" href="<?= htmlspecialchars($torrentURL) ?>">Download .torrent</a>
        &nbsp;·&nbsp;
        <a id="alfred-magnet-link" href="<?= htmlspecialchars($magnetURI) ?>">Magnet (desktop client)</a>
      </div>
      <p style="color:var(--dim);font-size:0.78rem;max-width:34rem;text-align:center;margin:0.35rem 0 0;line-height:1.45;">
        Plain <strong>HTTP</strong> to <code style="color:var(--accent2);">/downloads/*.iso</code> is <strong>denied</strong> in <code>downloads/.htaccess</code> (covenant + bandwidth).
        Same bits: WebTorrent above, <strong>.torrent</strong>, or <strong>magnet</strong> — verify with SHA256 after download.<?php if (!empty($_SESSION['akjv_token'])): ?> Covenant-sealed single fetch (1h token): <code style="color:var(--accent2);">/downloads/iso.php?t=<?= htmlspecialchars((string) $_SESSION['akjv_token'], ENT_QUOTES, 'UTF-8') ?></code><?php endif; ?>
      </p>
    </div>
    <?php elseif ($finalGaIsoPublished && !$gaP2pDownloadsEnabled): ?>
    <p style="color:var(--dim);font-size:0.95rem;margin-top:1.25rem;text-align:center;max-width:520px;">P2P and direct ISO links on this page are <strong style="color:var(--gold-light);">paused</strong>. If a tab already started WebTorrent, close it to stop the transfer — the server cannot stop your browser.</p>
    <?php endif; ?>

    <div class="flash-early-cta" id="flash-early-cta" style="margin-top:1.35rem;text-align:center;padding:0 0.5rem;">
      <button type="button" class="btn-flash-next" id="btn-flash-early" onclick="revealFlashGuide(true)">Next: flash ISO to USB (Rufus, Etcher, dd)</button>
      <span style="display:block;margin-top:0.45rem;font-size:0.78rem;color:var(--dim);max-width:36rem;margin-left:auto;margin-right:auto;line-height:1.45;">After the <code>.iso</code> is on your machine (this page, qBittorrent, or a copy from a friend), make a bootable stick. Full steps below — or <a href="/write-usb" style="color:var(--accent2);">/write-usb</a> for the dedicated USB page.</span>
    </div>
  </div>

  <!-- Progress -->
  <div id="progress-section">
    <div class="progress-bar-outer">
      <div class="progress-bar-inner" id="progress-bar"></div>
      <div class="progress-pct" id="progress-pct">0%</div>
    </div>
    <div class="stats-grid">
      <div class="stat-box">
        <div class="label">Downloaded</div>
        <div class="value" id="stat-downloaded">0 MB</div>
      </div>
      <div class="stat-box">
        <div class="label">Speed</div>
        <div class="value" id="stat-speed">0 KB/s</div>
      </div>
      <div class="stat-box">
        <div class="label">Peers</div>
        <div class="value" id="stat-peers">0</div>
      </div>
      <div class="stat-box">
        <div class="label">ETA</div>
        <div class="value" id="stat-eta">—</div>
      </div>
      <div class="stat-box">
        <div class="label">Uploaded</div>
        <div class="value" id="stat-uploaded">0 MB</div>
      </div>
    </div>
    <p style="color:var(--dim);font-size:0.85rem;text-align:center;">
      You're already sharing pieces with other downloaders! ✨
    </p>
  </div>

  <!-- Seed / Complete -->
  <div id="seed-section">
    <div class="seed-banner" style="background:linear-gradient(135deg,rgba(250,204,21,0.08),rgba(108,92,231,0.08));border-color:rgba(250,204,21,0.3);">
      <h2 style="background:linear-gradient(135deg,var(--gold-light),var(--gold));-webkit-background-clip:text;-webkit-text-fill-color:transparent;">✝ Download Complete — Glory to God!</h2>
      <p>Your copy of Alfred Linux 7.77 — Kingdom of God Edition — is ready.</p>
      <p style="font-style:italic;color:var(--gold-light);font-size:0.95rem;margin-top:0.5rem;">&ldquo;Go therefore and make disciples of all nations&rdquo; &mdash; Matthew 28:19</p>
      <p class="big-msg">
        Keep this page open to seed<br>
        the Kingdom to the world!<br>
        Every byte is His Word.
      </p>
    </div>

    <div class="seed-timer" id="seed-timer">00:00:00</div>
    <div class="seed-timer-label">Time you've been seeding for the community</div>

    <div class="seed-stats">
      <div class="stat-box">
        <div class="label">You've Shared</div>
        <div class="value" id="seed-uploaded">0 MB</div>
      </div>
      <div class="stat-box">
        <div class="label">Upload Speed</div>
        <div class="value" id="seed-speed">0 KB/s</div>
      </div>
      <div class="stat-box">
        <div class="label">Souls Reached</div>
        <div class="value" id="seed-peers" style="color:var(--gold);">0</div>
      </div>
      <div class="stat-box">
        <div class="label">Your Ratio</div>
        <div class="value" id="seed-ratio">0.00</div>
      </div>
    </div>

    <button class="btn-save" id="save-btn">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M12 5v14M5 12l7 7 7-7"/></svg>
      Save ISO to Disk
    </button>
    <button type="button" class="btn-flash-next" id="btn-flash-after-dl" style="margin-top:0.75rem;" onclick="revealFlashGuide(true)">Show USB flash steps now</button>
    <p style="color:var(--dim);font-size:0.82rem;max-width:32rem;margin:0.75rem auto 0;line-height:1.5;text-align:center;">
      Multi‑gigabyte ISO: <strong>Save ISO to Disk</strong> uses the browser&apos;s save API (streaming when supported). If it stalls or runs out of memory, use <strong>Download .torrent</strong> with qBittorrent, Transmission, or Deluge — same file and hash. After the file is saved, the <strong>Flash to USB</strong> section opens automatically (or tap the button above anytime).
    </p>

    <div style="margin-top: 2rem;">
      <p style="color:var(--dim);font-size:0.9rem;margin-bottom:0.8rem;">Spread the Kingdom:</p>
      <button class="share-btn" onclick="shareTwitter()">𝕏 Share on X</button>
      <button class="share-btn" onclick="shareLink()">🔗 Copy Link</button>
    </div>

    <div style="margin-top: 2rem; padding: 20px; background: rgba(231,76,60,0.08); border: 1px solid rgba(231,76,60,0.2); border-radius: 12px; text-align: center;">
      <p style="font-size: 1.1rem; color: #e8e8f0; margin-bottom: 8px;">❤️ <strong>Love Alfred Linux?</strong></p>
      <p style="color: var(--dim); font-size: 0.9rem; margin-bottom: 12px;">Your donation helps keep development going — servers, builds, and bringing sovereign computing to the world.</p>
      <a href="https://gositeme.com/donate.php?project=alfred-linux&from=alfredlinux.com" style="display:inline-block; padding: 10px 28px; background: linear-gradient(135deg, #e74c3c, #c0392b); color: #fff; border-radius: 8px; text-decoration: none; font-weight: 600; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
        ❤ Support the Mission
      </a>
    </div>
  </div>

  <div class="error-box" id="error-box"></div>
</div>

<!-- ── Flash to USB Guide ──────────────────────── -->
<div class="flash-guide" id="flash-guide">
  <h2>Flash to USB Drive</h2>
  <p class="subtitle">Turn your ISO into a bootable USB stick in minutes</p>

  <div class="flash-tabs">
    <button type="button" class="flash-tab active" data-flash-tab="windows" onclick="showFlashTab('windows')">Windows</button>
    <button type="button" class="flash-tab" data-flash-tab="mac" onclick="showFlashTab('mac')">macOS</button>
    <button type="button" class="flash-tab" data-flash-tab="linux" onclick="showFlashTab('linux')">Linux</button>
    <button type="button" class="flash-tab" data-flash-tab="etcher" onclick="showFlashTab('etcher')">balenaEtcher</button>
  </div>

  <div class="flash-panel active" id="flash-windows">
    <h3>&#x1f4bb; Rufus (Windows)</h3>
    <ol>
      <li>Download <a href="https://rufus.ie" target="_blank" rel="noopener">Rufus</a> (free, portable, no install needed)</li>
      <li>Insert a USB drive (8 GB minimum)</li>
      <li>Open Rufus &rarr; select your USB under <strong>Device</strong></li>
      <li>Click <strong>SELECT</strong> &rarr; choose the Alfred Linux ISO you just saved</li>
      <li>Partition scheme: <strong>GPT</strong> &middot; Target: <strong>UEFI</strong></li>
      <li>Click <strong>START</strong> &rarr; wait until &ldquo;READY&rdquo; appears</li>
      <li>Reboot your PC and boot from USB (usually F12 / F2 / DEL at startup)</li>
    </ol>
  </div>

  <div class="flash-panel" id="flash-mac">
    <h3>&#x1f34e; Terminal (macOS)</h3>
    <ol>
      <li>Insert a USB drive (8 GB minimum)</li>
      <li>Open Terminal and find your USB disk:</li>
    </ol>
    <div class="flash-cmd"><code>diskutil list</code><button onclick="copyCmd(this)">Copy</button></div>
    <ol start="3">
      <li>Unmount the USB (replace <strong>diskN</strong> with your disk):</li>
    </ol>
    <div class="flash-cmd"><code>diskutil unmountDisk /dev/diskN</code><button onclick="copyCmd(this)">Copy</button></div>
    <ol start="4">
      <li>Flash the ISO (use <strong>rdiskN</strong> for speed):</li>
    </ol>
    <div class="flash-cmd"><code>sudo dd if=~/Downloads/<?= htmlspecialchars($isoFileForCommands) ?> of=/dev/rdiskN bs=4m status=progress</code><button onclick="copyCmd(this)">Copy</button></div>
    <ol start="5">
      <li>Wait until complete, then eject and reboot from USB</li>
    </ol>
    <div class="flash-warn">&#x26a0;&#xfe0f; Double-check the disk number! <code>dd</code> overwrites the target without confirmation.</div>
  </div>

  <div class="flash-panel" id="flash-linux">
    <h3>&#x1f427; Terminal (Linux)</h3>
    <ol>
      <li>Insert a USB drive (8 GB minimum)</li>
      <li>Find your USB device:</li>
    </ol>
    <div class="flash-cmd"><code>lsblk</code><button onclick="copyCmd(this)">Copy</button></div>
    <ol start="3">
      <li>Flash the ISO (replace <strong>/dev/sdX</strong> with your USB &mdash; e.g. /dev/sdb):</li>
    </ol>
    <div class="flash-cmd"><code>sudo dd if=~/Downloads/<?= htmlspecialchars($isoFileForCommands) ?> of=/dev/sdX bs=4M status=progress oflag=sync</code><button onclick="copyCmd(this)">Copy</button></div>
    <ol start="4">
      <li>Wait until complete, then reboot and boot from USB</li>
    </ol>
    <div class="flash-warn">&#x26a0;&#xfe0f; Make sure <code>/dev/sdX</code> is your USB, not your main drive! Check <code>lsblk</code> output carefully.</div>
  </div>

  <div class="flash-panel" id="flash-etcher">
    <h3>&#x26a1; balenaEtcher (Any OS)</h3>
    <ol>
      <li>Download <a href="https://etcher.balena.io" target="_blank" rel="noopener">balenaEtcher</a> (free, works on Windows/Mac/Linux)</li>
      <li>Open Etcher &rarr; click <strong>Flash from file</strong> &rarr; select the Alfred Linux ISO</li>
      <li>Click <strong>Select target</strong> &rarr; choose your USB drive</li>
      <li>Click <strong>Flash!</strong> and wait for completion + verification</li>
      <li>Reboot and boot from USB</li>
    </ol>
    <p style="color:var(--dim);font-size:0.85rem;margin-top:1rem;">Etcher is the easiest option if you're not comfortable with command-line tools. It validates the write automatically.</p>
  </div>
</div>

<!-- ── Community Swarm ─────────────────────────── -->
<section class="community">
  <h2>The Alfred Swarm</h2>
  <p>Every dot is a person — downloading, seeding, building the future of computing together.</p>
  <div class="swarm-vis">
    <div class="swarm-counter" id="swarm-count">—</div>
    <div class="swarm-label" id="swarm-label">connecting to swarm…</div>
    <canvas id="swarm-canvas"></canvas>
  </div>
</section>

<!-- ── Integrity Verification ───────────────────── -->
<section class="how-it-works" style="border-top:1px solid var(--border);">
  <h2>Verify Your Download</h2>
  <p style="color:var(--dim);text-align:center;max-width:700px;margin:0 auto 2rem;line-height:1.6;">
    Omni-Hash verification algorithms<?= $gaIsoDetachSigReady ? ' + a <strong>GPG</strong> detach-sig' : '' ?> — different math, different authors<?= $gaIsoDetachSigReady ? ', different attack surfaces' : '' ?>.<br>
    Sealed by the <strong>Omahon</strong>.<?= $gaIsoDetachSigReady ? ' If an attacker somehow breaks one verification, the others catch it.' : ' BLAKE3, SHA-512, SHA-256, and MD5 are live — independent hash algorithms for total cryptographic authority.' ?>
  </p>
  <p style="color:var(--text-muted);text-align:center;max-width:720px;margin:-0.5rem auto 2rem;font-size:0.9rem;line-height:1.55;">
    <strong style="color:var(--text);">Kernel tarball &amp; ISO build integrity</strong> — download gates, hooks, GoForge CI/runners, and honest scope: <a href="/security-kernel" style="color:var(--accent2);font-weight:600;">/security-kernel</a>
  </p>
  <?php if (!$finalGaIsoPublished): ?>
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:1.5rem;max-width:800px;margin:0 auto 1.5rem;text-align:center;">
    <p style="color:var(--text);line-height:1.65;margin-bottom:0.75rem;"><strong>Official checksums are not published yet.</strong> They will appear here together with the frozen GA filename, <code style="color:var(--accent2);background:#1e1e2e;padding:2px 6px;border-radius:4px;">.torrent</code>, magnet, and <code style="color:var(--accent2);background:#1e1e2e;padding:2px 6px;border-radius:4px;">.iso.asc</code> when the final image is built and signed.</p>
    <p style="color:var(--dim);font-size:0.9rem;margin:0;">Do not treat any pre-release or stale hash from earlier drafts as the GA image.</p>
  </div>
  <?php else: ?>
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:1.5rem;max-width:800px;margin:0 auto 1.5rem;">
    <h3 style="color:var(--accent2);margin-bottom:1rem;font-size:1rem;">SHA-256 <span style="color:var(--dim);font-weight:400;font-size:0.85rem;">(NIST Standard)</span></h3>
    <div style="background:#0a0a0f;border-radius:8px;padding:0.8rem 1rem;font-family:monospace;font-size:0.8rem;word-break:break-all;color:var(--gold);margin-bottom:0.5rem;">
      <?= htmlspecialchars(substr(trim(@file_get_contents(__DIR__ . '/downloads/SHA256SUMS-7.77.txt')), 0, 64) ?: 'Calculating...') ?>
    </div>
    <div style="font-size:0.8rem;color:var(--dim);">
      <code style="color:var(--text);background:#1e1e2e;padding:2px 6px;border-radius:4px;">sha256sum <?= htmlspecialchars($isoName) ?>.iso</code>
    </div>
  </div>
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:1.5rem;max-width:800px;margin:0 auto 1.5rem;">
    <h3 style="color:var(--accent);margin-bottom:1rem;font-size:1rem;">BLAKE3 <span style="color:var(--dim);font-weight:400;font-size:0.85rem;">(Fastest &amp; most secure hash on the planet)</span></h3>
    <div style="background:#0a0a0f;border-radius:8px;padding:0.8rem 1rem;font-family:monospace;font-size:0.8rem;word-break:break-all;color:var(--accent2);margin-bottom:0.5rem;">
      <?= htmlspecialchars(substr(trim(@file_get_contents(__DIR__ . '/downloads/blake3.txt')), 0, 64) ?: 'Calculating...') ?>
    </div>
    <div style="font-size:0.8rem;color:var(--dim);">
      <code style="color:var(--text);background:#1e1e2e;padding:2px 6px;border-radius:4px;">b3sum <?= htmlspecialchars($isoName) ?>.iso</code>
      &nbsp;·&nbsp; Install: <code style="color:var(--text);background:#1e1e2e;padding:2px 6px;border-radius:4px;">pip install blake3</code> or <code style="color:var(--text);background:#1e1e2e;padding:2px 6px;border-radius:4px;">cargo install b3sum</code>
    </div>
  </div>
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:1.5rem;max-width:800px;margin:0 auto 1.5rem;">
    <h3 style="color:#00b894;margin-bottom:1rem;font-size:1rem;">SHA-512 <span style="color:var(--dim);font-weight:400;font-size:0.85rem;">(Cryptographic Heavyweight)</span></h3>
    <div style="background:#0a0a0f;border-radius:8px;padding:0.8rem 1rem;font-family:monospace;font-size:0.8rem;word-break:break-all;color:#00b894;margin-bottom:0.5rem;">
      <?= htmlspecialchars(substr(trim(@file_get_contents(__DIR__ . '/downloads/sha512.txt')), 0, 128) ?: 'Calculating...') ?>
    </div>
    <div style="font-size:0.8rem;color:var(--dim);">
      <code style="color:var(--text);background:#1e1e2e;padding:2px 6px;border-radius:4px;">sha512sum <?= htmlspecialchars($isoName) ?>.iso</code>
    </div>
  </div>
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:1.5rem;max-width:800px;margin:0 auto 1.5rem;">
    <h3 style="color:#d97706;margin-bottom:1rem;font-size:1rem;">MD5 <span style="color:var(--dim);font-weight:400;font-size:0.85rem;">(Legacy Clients)</span></h3>
    <div style="background:#0a0a0f;border-radius:8px;padding:0.8rem 1rem;font-family:monospace;font-size:0.8rem;word-break:break-all;color:#d97706;margin-bottom:0.5rem;">
      <?= htmlspecialchars(substr(trim(@file_get_contents(__DIR__ . '/downloads/md5.txt')), 0, 32) ?: 'Calculating...') ?>
    </div>
    <div style="font-size:0.8rem;color:var(--dim);">
      <code style="color:var(--text);background:#1e1e2e;padding:2px 6px;border-radius:4px;">md5sum <?= htmlspecialchars($isoName) ?>.iso</code>
    </div>
  </div>
  <p style="color:var(--dim);text-align:center;font-size:0.85rem;margin-top:1rem;">
    Both hashes must match. If either one doesn't — <strong style="color:var(--danger);">do not install</strong>. Re-download via P2P.
  </p>
  <?php if ($gaIsoDetachSigReady): ?>
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:1.5rem;max-width:800px;margin:1rem auto 0;">
    <h3 style="color:var(--gold);margin-bottom:1rem;font-size:1rem;">🔏 GPG Signature <span style="color:var(--dim);font-weight:400;font-size:0.85rem;">(GoSiteMe Release Signing Key)</span></h3>
    <div style="background:#0a0a0f;border-radius:8px;padding:0.8rem 1rem;font-family:monospace;font-size:0.8rem;word-break:break-all;color:var(--success);margin-bottom:0.5rem;">
      Key: 41E1 6607 5B0F 9520 5839 E41B 32BC EDE8 C8DD 8B00
    </div>
    <div style="font-size:0.8rem;color:var(--dim);">
      <code style="color:var(--text);background:#1e1e2e;padding:2px 6px;border-radius:4px;">wget https://alfredlinux.com/downloads/GPG-KEY.asc && gpg --import GPG-KEY.asc</code><br>
      <code style="color:var(--text);background:#1e1e2e;padding:2px 6px;border-radius:4px;">gpg --verify <?= htmlspecialchars($isoName) ?>.iso.asc <?= htmlspecialchars($isoName) ?>.iso</code>
    </div>
  </div>
  <?php else: ?>
  <div style="background:rgba(245,166,35,0.06);border:1px solid rgba(245,166,35,0.25);border-radius:12px;padding:1.25rem;max-width:800px;margin:1rem auto 0;text-align:center;">
    <p style="color:var(--text);margin:0;font-size:0.95rem;line-height:1.55;"><strong>GPG detach-sign</strong> for <code style="color:var(--accent2);"><?= htmlspecialchars($isoName) ?>.iso.asc</code> is coming soon. Verify with <strong>SHA-256</strong> and <strong>BLAKE3</strong> above. Import <a href="/downloads/GPG-KEY.asc" style="color:var(--gold);">GPG-KEY.asc</a> when the signature is published.</p>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</section>

<!-- ── How It Works ────────────────────────────── -->
<section class="how-it-works">
  <h2>Download Options</h2>
  <div class="steps">
    <div class="step">
      <div class="step-num">1</div>
      <div>
        <h3>P2P / WebTorrent (In-Browser)</h3>
        <p><?= $gaDownloadOfferLive
          ? 'Click the button above. Download via BitTorrent directly in your browser — no plugins needed. You download from every peer in the swarm simultaneously, and share pieces while downloading.'
          : ($finalGaIsoPublished && !$gaP2pDownloadsEnabled
            ? 'In-browser WebTorrent is turned off for now. If you still have an old tab downloading, close that tab to stop it.'
            : 'When the GA ISO is published, the download card above will offer in-browser WebTorrent. Until then, there is no official GA torrent to fetch from this page.') ?></p>
      </div>
    </div>
    <div class="step">
      <div class="step-num">2</div>
      <div>
        <h3>.torrent File (Desktop Client)</h3>
        <p><?= $gaDownloadOfferLive
          ? 'Grab the .torrent file and use your preferred desktop client (qBittorrent, Transmission, Deluge). Full speed, resume support, seed as long as you want.'
          : ($finalGaIsoPublished && !$gaP2pDownloadsEnabled
            ? 'Official .torrent links are paused together with WebTorrent. Do not use stale magnets from older copies of this page unless checksums match a build you trust.'
            : 'The official <code style="color:var(--accent2);background:#1e1e2e;padding:2px 6px;border-radius:4px;">.torrent</code> will be linked from this page when the build is frozen. Avoid third-party mirrors until checksums match here.') ?></p>
      </div>
    </div>
    <div class="step">
      <div class="step-num">3</div>
      <div>
        <h3>Verify Your Download</h3>
        <p><?= $finalGaIsoPublished
          ? 'Every method delivers the exact same ISO. Verify with SHA-256 and BLAKE3 checksums above. Both must match — if either doesn\'t, re-download.'
          : 'After release, every delivery path will point at the same signed ISO. Use the SHA-256 and BLAKE3 values published on this page for that filename only.' ?></p>
      </div>
    </div>
  </div>
</section>

<!-- ── Mobile Edition ──────────────────────────── -->
<section style="max-width:900px;margin:0 auto;padding:3rem 2rem 2rem;">
  <div style="background:linear-gradient(135deg,#1a1a2e,#16213e);border:1px solid #2a2a4a;border-radius:16px;padding:2.5rem;text-align:center;">
    <div style="font-size:2.5rem;margin-bottom:0.5rem;">📱</div>
    <h2 style="font-size:1.8rem;font-weight:800;margin-bottom:0.5rem;">
      <span style="background:linear-gradient(135deg,var(--gold),#e17055);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">Alfred Linux Mobile</span>
    </h2>
    <p style="color:var(--dim);font-size:1.05rem;max-width:600px;margin:0 auto 1.5rem;line-height:1.6;">
      The same sovereign OS — in your pocket. Runs on any Android 12+ phone via Termux.<br>
      Optimized for <strong style="color:var(--gold);">Samsung Galaxy S26 Ultra</strong> with DeX desktop mode.
    </p>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:2rem;text-align:left;">
      <div style="background:rgba(255,255,255,0.04);border-radius:10px;padding:1rem;">
        <div style="color:var(--accent2);font-weight:700;margin-bottom:0.3rem;">🖥️ Alfred IDE</div>
        <div style="color:var(--dim);font-size:0.85rem;">Full code editor in your browser. Commander extension included.</div>
      </div>
      <div style="background:rgba(255,255,255,0.04);border-radius:10px;padding:1rem;">
        <div style="color:var(--accent2);font-weight:700;margin-bottom:0.3rem;">🔍 Alfred Search</div>
        <div style="color:var(--dim);font-size:0.85rem;">Meilisearch — local, private, instant.</div>
      </div>
      <div style="background:rgba(255,255,255,0.04);border-radius:10px;padding:1rem;">
        <div style="color:var(--accent2);font-weight:700;margin-bottom:0.3rem;">🗣️ Alfred Voice</div>
        <div style="color:var(--dim);font-size:0.85rem;">Kokoro TTS — speak from your terminal.</div>
      </div>
      <div style="background:rgba(255,255,255,0.04);border-radius:10px;padding:1rem;">
        <div style="color:var(--accent2);font-weight:700;margin-bottom:0.3rem;">✡️ Shabbat Clock</div>
        <div style="color:var(--dim);font-size:0.85rem;">Daniel Calendar API — God's time, right in your terminal.</div>
      </div>
      <div style="background:rgba(255,255,255,0.04);border-radius:10px;padding:1rem;">
        <div style="color:var(--accent2);font-weight:700;margin-bottom:0.3rem;">🎵 Music Studio</div>
        <div style="color:var(--dim);font-size:0.85rem;">SoundStudioPro — create worship music with AI.</div>
      </div>
      <div style="background:rgba(255,255,255,0.04);border-radius:10px;padding:1rem;">
        <div style="color:var(--accent2);font-weight:700;margin-bottom:0.3rem;">🖥️ Samsung DeX</div>
        <div style="color:var(--dim);font-size:0.85rem;">Connect a monitor — full desktop experience.</div>
      </div>
    </div>

    <div style="background:#0d1117;border:1px solid #30363d;border-radius:10px;padding:1.2rem;font-family:monospace;font-size:0.95rem;color:var(--accent2);margin-bottom:1.5rem;text-align:left;overflow-x:auto;">
      <div style="color:var(--dim);font-size:0.8rem;margin-bottom:0.5rem;"># Open Termux on your Android phone and run:</div>
      curl -fsSL https://alfredlinux.com/downloads/install-alfred-mobile.sh | bash
    </div>

    <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
      <a href="/downloads/install-alfred-mobile.sh" style="display:inline-block;background:linear-gradient(135deg,var(--accent),#5b4bc4);color:#fff;padding:0.8rem 2rem;border-radius:10px;text-decoration:none;font-weight:700;font-size:1rem;">
        ⬇ Download Installer Script
      </a>
      <a href="/downloads/SAMSUNG-S26-QUICKSTART.md" style="display:inline-block;background:rgba(253,203,110,0.12);border:1px solid var(--gold);color:var(--gold);padding:0.8rem 2rem;border-radius:10px;text-decoration:none;font-weight:700;font-size:1rem;">
        📘 Samsung S26 Guide
      </a>
    </div>

    <p style="color:var(--dim);font-size:0.8rem;margin-top:1.2rem;">
      No root required · ~5 GB storage · Android 12+ · 11 commands included<br>
      Prerequisite: Install <a href="https://f-droid.org/en/packages/com.termux/" style="color:var(--accent);">Termux from F-Droid</a> (not Google Play)
    </p>
  </div>
</section>

<!-- ── Footer ──────────────────────────────────── -->
<footer>
  <p style="font-style:italic;color:var(--gold-light);font-size:0.9rem;margin-bottom:0.5rem;opacity:0.8;">&ldquo;For the earth will be filled with the knowledge of the glory of the LORD, as the waters cover the sea.&rdquo; &mdash; Habakkuk 2:14</p>
  <p>Alfred Linux 7.77 &copy; <?= date('Y') ?> · <a href="/">alfredlinux.com</a> · <a href="/forge/">GoForge</a> · Kingdom of God Edition · <span style="color:var(--gold-dark);">Soli Deo Gloria</span></p>
</footer>

<!-- ── WebTorrent Browser Client ───────────────── -->
<?php if ($gaDownloadOfferLive): ?>
<script src="/assets/js/webtorrent.min.js"></script>
<?php endif; ?>
<script>
(function() {
  'use strict';

  // ── Config ────────────────────────────────────
  const ISO_NAME    = <?= json_encode($isoName) ?>;
  const TORRENT_URL = <?= json_encode($torrentURL) ?>;
  const MAGNET_URI  = <?= json_encode($magnetURI) ?>;
  const GA_DOWNLOADS_LIVE = <?= $gaDownloadOfferLive ? 'true' : 'false' ?>;

  let client     = null;
  let torrent    = null;
  let seedStart  = null;
  let blobURL    = null;
  let seedTimer  = null;

  // ── UI Elements ───────────────────────────────
  const $start    = document.getElementById('start-section');
  const $progress = document.getElementById('progress-section');
  const $seed     = document.getElementById('seed-section');
  const $error    = document.getElementById('error-box');

  // ── Format helpers ────────────────────────────
  function fmtBytes(b) {
    if (b < 1024) return b + ' B';
    if (b < 1048576) return (b / 1024).toFixed(1) + ' KB';
    if (b < 1073741824) return (b / 1048576).toFixed(1) + ' MB';
    return (b / 1073741824).toFixed(2) + ' GB';
  }
  function fmtSpeed(bps) {
    if (bps < 1024) return bps + ' B/s';
    if (bps < 1048576) return (bps / 1024).toFixed(0) + ' KB/s';
    return (bps / 1048576).toFixed(1) + ' MB/s';
  }
  function fmtTime(secs) {
    if (!secs || secs === Infinity) return '—';
    const h = Math.floor(secs / 3600);
    const m = Math.floor((secs % 3600) / 60);
    const s = Math.floor(secs % 60);
    return (h > 0 ? h + 'h ' : '') + m + 'm ' + s + 's';
  }
  function fmtTimer(secs) {
    const h = String(Math.floor(secs / 3600)).padStart(2, '0');
    const m = String(Math.floor((secs % 3600) / 60)).padStart(2, '0');
    const s = String(Math.floor(secs % 60)).padStart(2, '0');
    return h + ':' + m + ':' + s;
  }

  window.revealFlashGuide = function(shouldScroll) {
    var g = document.getElementById('flash-guide');
    if (!g) return;
    g.classList.add('visible');
    if (shouldScroll) {
      try {
        g.scrollIntoView({ behavior: 'smooth', block: 'start' });
      } catch (e) {
        g.scrollIntoView(true);
      }
    }
  };

  // ── Start Download ────────────────────────────
  window.startDownload = function() {
    if (!GA_DOWNLOADS_LIVE) {
      showError('P2P downloads are disabled on this page right now. If a transfer already started in this tab, close the tab to stop it. After we re-enable, hard-refresh for the new magnet.');
      return;
    }
    if (!window.WebTorrent) {
      showError('WebTorrent library failed to load. <a href="' + TORRENT_URL + '">Download the .torrent file</a> and use a desktop client like qBittorrent instead.');
      return;
    }

    try {
      client = new WebTorrent();
    } catch (e) {
      showError('Could not initialize WebTorrent: ' + e.message + '. <a href="' + TORRENT_URL + '">Download the .torrent file</a> and use a desktop client instead.');
      return;
    }

    client.on('error', function(err) {
      console.error('WebTorrent error:', err);
      showError('Torrent error: ' + err.message + '. <a href="' + TORRENT_URL + '">Download the .torrent file</a> and use a desktop client instead.');
    });

    $start.style.display = 'none';
    $progress.style.display = 'block';

    // Use magnet URI if available, fall back to .torrent URL
    var torrentId = MAGNET_URI || TORRENT_URL;
    console.log('[Alfred] Adding torrent via', MAGNET_URI ? 'magnet URI' : '.torrent URL');
    addTorrentFromMagnet(torrentId);
  };

  function addTorrentFromMagnet(magnetURI) {
    client.add(magnetURI, {
      announce: [
        'wss://tracker.openwebtorrent.com',
      ]
    }, function(t) {
      torrent = t;
      console.log('Torrent added:', t.infoHash, '— Files:', t.files.length);

      torrent.once('download', function() {
        if (window.alfredRecordDownloadStart) window.alfredRecordDownloadStart();
      });

      // Update progress periodically
      var progressInterval = setInterval(function() {
        var pct = Math.round(torrent.progress * 100);
        document.getElementById('progress-bar').style.width = pct + '%';
        document.getElementById('progress-pct').textContent = pct + '%';
        document.getElementById('stat-downloaded').textContent = fmtBytes(torrent.downloaded);
        document.getElementById('stat-speed').textContent = fmtSpeed(torrent.downloadSpeed);
        document.getElementById('stat-peers').textContent = torrent.numPeers;
        document.getElementById('stat-eta').textContent = fmtTime(torrent.timeRemaining / 1000);
        document.getElementById('stat-uploaded').textContent = fmtBytes(torrent.uploaded);

        // Update swarm counter
        document.getElementById('swarm-count').textContent = torrent.numPeers;
      }, 500);

      torrent.on('done', function() {
        clearInterval(progressInterval);
        showSeedMode();
      });
    });
  };

  // ── Seed Mode ─────────────────────────────────
  function showSeedMode() {
    $progress.style.display = 'none';
    $seed.style.display = 'block';
    seedStart = Date.now();

    // Save button — use File System Access API for large files, fall back to blob save
    var saveBtn = document.getElementById('save-btn');
    saveBtn.onclick = async function() {
      // Try modern File System Access API first (Chrome/Edge 86+)
      if (window.showSaveFilePicker) {
        try {
          var handle = await window.showSaveFilePicker({
            suggestedName: ISO_NAME,
            types: [{ description: 'ISO Image', accept: { 'application/octet-stream': ['.iso'] } }]
          });
          var writable = await handle.createWritable();
          var file = torrent.files[0];
          var stream = file.createReadStream();
          saveBtn.textContent = 'Saving...';
          saveBtn.disabled = true;

          stream.on('data', function(chunk) { writable.write(chunk); });
          stream.on('end', function() {
            Promise.resolve(writable.close()).then(function() {
              saveBtn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M20 6L9 17l-5-5"/></svg> Saved!';
              window.revealFlashGuide(true);
            }).catch(function(err) {
              console.error('Close failed:', err);
              saveToDisk();
            });
          });
          stream.on('error', function(e) {
            console.error('Stream error:', e);
            writable.close();
            // Fall back to blob save
            saveToDisk();
          });
          return;
        } catch (e) {
          if (e.name === 'AbortError') return; // user cancelled
          console.warn('File System Access failed:', e);
        }
      }
      // Fallback: save from WebTorrent blob in memory
      saveToDisk();
    };

    function saveToDisk() {
      // Save from WebTorrent blob — ISO is not served via HTTP
      if (!torrent || !torrent.files || !torrent.files[0]) {
        showError('No downloaded file available. Please re-download via P2P.');
        return;
      }
      torrent.files[0].getBlob(function(err, blob) {
        if (err) {
          showError('Could not create file: ' + err.message);
          return;
        }
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = ISO_NAME.indexOf('.iso') === -1 ? ISO_NAME + '.iso' : ISO_NAME;
        a.click();
        window.revealFlashGuide(true);
        setTimeout(function() { URL.revokeObjectURL(url); }, 60000);
      });
    }

    // Seed stats updater
    seedTimer = setInterval(function() {
      var elapsed = Math.floor((Date.now() - seedStart) / 1000);
      document.getElementById('seed-timer').textContent = fmtTimer(elapsed);
      document.getElementById('seed-uploaded').textContent = fmtBytes(torrent.uploaded);
      document.getElementById('seed-speed').textContent = fmtSpeed(torrent.uploadSpeed);
      document.getElementById('seed-peers').textContent = torrent.numPeers;
      document.getElementById('seed-ratio').textContent = torrent.ratio.toFixed(2);
      document.getElementById('swarm-count').textContent = torrent.numPeers;
    }, 1000);
  }

  // ── Share ─────────────────────────────────────
  window.shareTwitter = function() {
    var text = encodeURIComponent(
      "I just downloaded Alfred Linux 7.77: Kingdom of God Edition! ✝🐧\n\nThe Alpha Matrix is officially ALIVE. Zero-trust AppArmor wrappers, offline AI supercomputing, and the AKJV Bible forged into an immutable core. God's number on every byte.\n\n" +
      "https://alfredlinux.com/download\n\n#AlfredLinux #KingdomOfGod #OpenSource"
    );
    window.open('https://x.com/intent/tweet?text=' + text, '_blank', 'noopener,noreferrer');
  };

  window.shareLink = function() {
    navigator.clipboard.writeText('https://alfredlinux.com/download').then(function() {
      var btn = document.querySelector('.share-btn:last-child');
      btn.textContent = '✅ Copied!';
      setTimeout(function() { btn.textContent = '🔗 Copy Link'; }, 2000);
    });
  };

  // ── Error handling ────────────────────────────
  function showError(msg) {
    $error.style.display = 'block';
    $error.innerHTML = msg;
  }

  // ── Swarm Visualization ───────────────────────
  (function initSwarmVis() {
    var canvas = document.getElementById('swarm-canvas');
    if (!canvas) return;
    var ctx = canvas.getContext('2d');
    var particles = [];

    function resize() {
      canvas.width  = canvas.parentElement.clientWidth;
      canvas.height = 200;
    }
    resize();
    window.addEventListener('resize', resize);

    // We will initialize particles in the draw loop once we read the swarm size.
    var lastTotal = -1;

    function draw() {
      ctx.clearRect(0, 0, canvas.width, canvas.height);

      // Read live swarm size
      var count = (typeof torrent !== 'undefined' && torrent) ? torrent.numPeers : 0;
      var serverSeeds = parseInt(document.getElementById('swarm-count').dataset.serverSeeds || '1', 10);
      var total = Math.max(count, serverSeeds);
      
      // Ensure particles array size exactly matches 'total'
      if (total !== lastTotal) {
        while (particles.length < total) {
          particles.push({
            x: Math.random() * canvas.width,
            y: Math.random() * canvas.height,
            vx: (Math.random() - 0.5) * 0.8,
            vy: (Math.random() - 0.5) * 0.8,
            r: 2 + Math.random() * 3,
            color: Math.random() > 0.5 ? '#6c5ce7' : '#00cec9',
          });
        }
        if (particles.length > total) {
          particles.splice(total);
        }
        lastTotal = total;
      }

      // Draw connections
      for (var i = 0; i < particles.length; i++) {
        for (var j = i + 1; j < particles.length; j++) {
          var dx = particles[i].x - particles[j].x;
          var dy = particles[i].y - particles[j].y;
          var dist = Math.sqrt(dx * dx + dy * dy);
          if (dist < 120) {
            ctx.beginPath();
            ctx.strokeStyle = 'rgba(108,92,231,' + (1 - dist / 120) * 0.2 + ')';
            ctx.lineWidth = 0.5;
            ctx.moveTo(particles[i].x, particles[i].y);
            ctx.lineTo(particles[j].x, particles[j].y);
            ctx.stroke();
          }
        }
      }

      // Draw particles
      for (var k = 0; k < particles.length; k++) {
        var p = particles[k];
        p.x += p.vx;
        p.y += p.vy;
        if (p.x < 0 || p.x > canvas.width)  p.vx *= -1;
        if (p.y < 0 || p.y > canvas.height) p.vy *= -1;

        ctx.beginPath();
        ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
        ctx.fillStyle = p.color;
        ctx.fill();
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.r + 4, 0, Math.PI * 2);
        ctx.fillStyle = p.color.replace(')', ',0.15)').replace('rgb', 'rgba');
        ctx.fill();
      }

      requestAnimationFrame(draw);
    }
    draw();
  })();

  // ── Live Swarm Count (server-side seeder API) ──
  (function initSwarmCount() {
    var GA_HASH = <?= json_encode($gaTorrentBtihHex, JSON_THROW_ON_ERROR) ?>;
    var $count  = document.getElementById('swarm-count');
    var $label  = document.getElementById('swarm-label');

    if (!GA_DOWNLOADS_LIVE) {
      $count.textContent = '—';
      $label.textContent = 'Swarm stats will appear when P2P downloads are enabled';
      return;
    }

    function updateSwarm() {
      fetch('/torrent-api/api/torrents', { signal: AbortSignal.timeout(5000) })
        .then(function(r) { return r.json(); })
        .then(function(data) {
          // Find our GA ISO torrent
          var found = null;
          var keys = Object.keys(data);
          for (var i = 0; i < keys.length; i++) {
            var t = data[keys[i]];
            if (t.infoHash === GA_HASH) { found = t; break; }
          }
          // Count: peers connected to seeder + the seeder itself (always 1)
          var peers = found ? (found.peers || 0) : 0;
          var seeds = 1; // Our server is always seeding
          var total = seeds + peers;
          $count.textContent = total;
          $count.dataset.serverSeeds = total;
          $label.textContent = total === 1
            ? '1 seed online — download to join the swarm'
            : total + ' active in the swarm right now';
        })
        .catch(function() {
          // API unreachable — still show the server seed
          $count.textContent = '1';
          $count.dataset.serverSeeds = '1';
          $label.textContent = '1 seed online — download to join the swarm';
        });
    }

    updateSwarm();
    setInterval(updateSwarm, 15000); // Refresh every 15s
  })();

  // ── Flash to USB tab switching ────────────────
  window.showFlashTab = function(tab) {
    document.querySelectorAll('.flash-tab').forEach(function(t) {
      t.classList.toggle('active', t.getAttribute('data-flash-tab') === tab);
    });
    document.querySelectorAll('.flash-panel').forEach(function(p) { p.classList.remove('active'); });
    var panel = document.getElementById('flash-' + tab);
    if (panel) panel.classList.add('active');
  };

  window.copyCmd = function(btn) {
    var code = btn.parentElement.querySelector('code').textContent;
    navigator.clipboard.writeText(code).then(function() {
      btn.textContent = 'Copied!';
      setTimeout(function() { btn.textContent = 'Copy'; }, 1500);
    });
  };

  // Auto-select the right tab based on OS
  (function detectOS() {
    var ua = navigator.userAgent;
    var tab = 'etcher'; // default
    if (/Win/.test(ua)) tab = 'windows';
    else if (/Mac/.test(ua)) tab = 'mac';
    else if (/Linux/.test(ua)) tab = 'linux';
    document.querySelectorAll('.flash-tab').forEach(function(t) {
      t.classList.toggle('active', t.getAttribute('data-flash-tab') === tab);
    });
    document.querySelectorAll('.flash-panel').forEach(function(p) { p.classList.remove('active'); });
    var panel = document.getElementById('flash-' + tab);
    if (panel) panel.classList.add('active');
  })();

  // ── Warn before leaving while seeding ─────────
  window.addEventListener('beforeunload', function(e) {
    if (torrent && torrent.done) {
      e.preventDefault();
      e.returnValue = 'You\'re currently seeding Alfred Linux for the community. Are you sure you want to leave?';
    }
  });

  (function bindIsoDownloadLinkTracking() {
    if (!GA_DOWNLOADS_LIVE) return;
    function hook(id) {
      var a = document.getElementById(id);
      if (!a) return;
      a.addEventListener('click', function() {
        if (window.alfredRecordDownloadStart) window.alfredRecordDownloadStart();
      });
    }
    hook('alfred-torrent-link');
    hook('alfred-iso-direct-link');
  })();

})();
</script>

<footer style="text-align:center;padding:2rem 1.5rem;color:#94a3b8;font-size:.85rem;border-top:1px solid rgba(250,204,21,0.08);">
    <p style="font-size:1rem;color:var(--gold);font-weight:700;margin-bottom:0.75rem;">&ldquo;The grass withereth, the flower fadeth: but the word of our God shall stand for ever.&rdquo; &mdash; Isaiah 40:8</p>
    <p>&copy; <?= date('Y') ?> <a href="https://gositeme.com" style="color:var(--gold);text-decoration:none;">GoSiteMe Inc.</a> &mdash; Alfred Linux 7.77 &middot; Kingdom of God Edition &middot; <span style="color:var(--gold-dark);">Soli Deo Gloria</span></p>
    <p style="margin-top:0.5rem;font-size:0.78rem;"><a href="https://lavocat.ca/journal?read=9&lang=en" style="color:var(--gold-light);text-decoration:none;">Commander&rsquo;s Journal</a> &middot; <a href="https://gositeme.com/sovereignty" style="color:var(--gold-light);text-decoration:none;">Sovereignty Declarations</a> &middot; <a href="https://gositeme.com/bible/read/isaiah/40" style="color:var(--gold-light);text-decoration:none;">Isaiah 40 &mdash; AKJV</a></p>
</footer>

<?php include __DIR__ . "/includes/shabbat-banner.php"; ?>

<!-- ── Launch Week Celebration Engine ──────────── -->
<script>
(function() {
  'use strict';

  // ── Launch Week Day Highlighter ──
  (function highlightDays() {
    var days = document.querySelectorAll('.launch-day[data-day]');
    if (!days.length) return;
    var today = new Intl.DateTimeFormat('en-CA', { timeZone: 'America/Montreal', year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date());
    days.forEach(function(el) {
      var d = el.getAttribute('data-day');
      if (d === today) el.classList.add('today');
      else if (d < today) el.classList.add('past');
      else el.classList.add('future');
    });
  })();

  // ── Fireworks Canvas ──
  var canvas = document.getElementById('celebration-canvas');
  if (!canvas) return;
  var ctx = canvas.getContext('2d');
  var W, H;
  function resize() {
    W = canvas.width = window.innerWidth;
    H = canvas.height = window.innerHeight;
  }
  resize();
  window.addEventListener('resize', resize);

  var particles = [];
  var rockets = [];
  var gravity = 0.04;
  var colors = [
    '#facc15', '#fde68a', '#d97706',  // gold family
    '#6c5ce7', '#a29bfe',              // accent purple
    '#00cec9', '#00b894',              // accent teal/green
    '#ff6b6b', '#ff9ff3',              // celebration red/pink
    '#ffffff'                           // white sparkle
  ];

  function Particle(x, y, vx, vy, color, life, size) {
    this.x = x; this.y = y;
    this.vx = vx; this.vy = vy;
    this.color = color;
    this.life = life;
    this.maxLife = life;
    this.size = size || 2;
    this.trail = [];
  }
  Particle.prototype.update = function() {
    this.trail.push({ x: this.x, y: this.y });
    if (this.trail.length > 5) this.trail.shift();
    this.x += this.vx;
    this.y += this.vy;
    this.vy += gravity;
    this.vx *= 0.99;
    this.life--;
  };
  Particle.prototype.draw = function() {
    var alpha = Math.max(0, this.life / this.maxLife);
    // Trail
    for (var t = 0; t < this.trail.length; t++) {
      var ta = alpha * (t / this.trail.length) * 0.3;
      ctx.beginPath();
      ctx.arc(this.trail[t].x, this.trail[t].y, this.size * 0.5, 0, Math.PI * 2);
      ctx.fillStyle = this.color.replace(')', ',' + ta + ')').replace('rgb', 'rgba');
      ctx.fill();
    }
    // Main dot
    ctx.beginPath();
    ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
    ctx.fillStyle = this.color;
    ctx.globalAlpha = alpha;
    ctx.fill();
    ctx.globalAlpha = 1;
  };

  function hexToRgb(hex) {
    var r = parseInt(hex.slice(1, 3), 16);
    var g = parseInt(hex.slice(3, 5), 16);
    var b = parseInt(hex.slice(5, 7), 16);
    return 'rgb(' + r + ',' + g + ',' + b + ')';
  }

  function launchFirework() {
    var x = W * (0.15 + Math.random() * 0.7);
    var targetY = H * (0.1 + Math.random() * 0.3);
    rockets.push({
      x: x, y: H,
      vy: -(6 + Math.random() * 4),
      targetY: targetY,
      color: hexToRgb(colors[Math.floor(Math.random() * colors.length)]),
      trail: []
    });
  }

  function explode(x, y) {
    // "Every dot is a person" - sync particle count with swarm size
    var swarmCount = parseInt(document.getElementById('swarm-count').dataset.serverSeeds || 1);
    var count = swarmCount; 
    // Minimum visual burst if swarm is just the server
    if (count < 10) count = 10;
    
    var color = hexToRgb(colors[Math.floor(Math.random() * colors.length)]);
    var ringColor = hexToRgb(colors[Math.floor(Math.random() * colors.length)]);
    for (var i = 0; i < count; i++) {
      var angle = (Math.PI * 2 / count) * i;
      var speed = 1.5 + Math.random() * 3;
      var c = i % 3 === 0 ? ringColor : color;
      var p = new Particle(
        x, y,
        Math.cos(angle) * speed,
        Math.sin(angle) * speed,
        c,
        60 + Math.floor(Math.random() * 40),
        1.5 + Math.random() * 1.5
      );
      particles.push(p);
    }
  }

  // ── Confetti system ──
  var confetti = [];
  function ConfettiPiece() {
    this.x = Math.random() * W;
    this.y = -10 - Math.random() * 40;
    this.w = 6 + Math.random() * 6;
    this.h = 3 + Math.random() * 3;
    this.color = colors[Math.floor(Math.random() * colors.length)];
    this.vy = 1 + Math.random() * 2;
    this.vx = (Math.random() - 0.5) * 2;
    this.rot = Math.random() * 360;
    this.rotSpeed = (Math.random() - 0.5) * 10;
    this.wobble = Math.random() * Math.PI * 2;
    this.wobbleSpeed = 0.03 + Math.random() * 0.05;
  }
  ConfettiPiece.prototype.update = function() {
    this.y += this.vy;
    this.wobble += this.wobbleSpeed;
    this.x += this.vx + Math.sin(this.wobble) * 0.5;
    this.rot += this.rotSpeed;
    return this.y < H + 20;
  };
  ConfettiPiece.prototype.draw = function() {
    ctx.save();
    ctx.translate(this.x, this.y);
    ctx.rotate(this.rot * Math.PI / 180);
    ctx.fillStyle = this.color;
    ctx.globalAlpha = 0.85;
    ctx.fillRect(-this.w / 2, -this.h / 2, this.w, this.h);
    ctx.globalAlpha = 1;
    ctx.restore();
  };

  // Spawn confetti periodically
  var confettiTimer = 0;

  function animate() {
    ctx.clearRect(0, 0, W, H);

    // Update rockets
    for (var r = rockets.length - 1; r >= 0; r--) {
      var rk = rockets[r];
      rk.trail.push({ x: rk.x, y: rk.y });
      if (rk.trail.length > 8) rk.trail.shift();
      rk.y += rk.vy;
      rk.vy += 0.03;

      // Draw rocket trail
      for (var t = 0; t < rk.trail.length; t++) {
        ctx.beginPath();
        ctx.arc(rk.trail[t].x, rk.trail[t].y, 1.5, 0, Math.PI * 2);
        ctx.fillStyle = rk.color;
        ctx.globalAlpha = t / rk.trail.length * 0.6;
        ctx.fill();
      }
      ctx.globalAlpha = 1;

      if (rk.y <= rk.targetY || rk.vy >= 0) {
        explode(rk.x, rk.y);
        rockets.splice(r, 1);
      }
    }

    // Update particles
    for (var i = particles.length - 1; i >= 0; i--) {
      particles[i].update();
      particles[i].draw();
      if (particles[i].life <= 0) particles.splice(i, 1);
    }

    // Update confetti
    confettiTimer++;
    if (confettiTimer % 3 === 0 && confetti.length < 80) {
      confetti.push(new ConfettiPiece());
    }
    for (var c = confetti.length - 1; c >= 0; c--) {
      if (!confetti[c].update()) {
        confetti.splice(c, 1);
      } else {
        confetti[c].draw();
      }
    }

    requestAnimationFrame(animate);
  }

  // Launch fireworks at intervals — staggered for drama
  function scheduleFireworks() {
    launchFirework();
    // Random double/triple bursts
    if (Math.random() > 0.5) {
      setTimeout(launchFirework, 200 + Math.random() * 300);
    }
    if (Math.random() > 0.7) {
      setTimeout(launchFirework, 500 + Math.random() * 400);
    }
    setTimeout(scheduleFireworks, 2000 + Math.random() * 3000);
  }

  // ── Floating emoji sparkles ──
  var emojis = ['✝', '🔥', '⚡', '💜', '🌟', '✨', '👑', '🕊️', '🎆', '🎇'];
  function spawnEmoji() {
    var el = document.createElement('div');
    el.className = 'celebration-particle';
    el.textContent = emojis[Math.floor(Math.random() * emojis.length)];
    el.style.left = Math.random() * 100 + 'vw';
    el.style.top = (60 + Math.random() * 40) + 'vh';
    el.style.animationDuration = (4 + Math.random() * 6) + 's';
    el.style.fontSize = (1 + Math.random() * 1.5) + 'rem';
    document.body.appendChild(el);
    el.addEventListener('animationend', function() { el.remove(); });
  }
  setInterval(spawnEmoji, 1500 + Math.random() * 2000);

  // Kick it off!
  animate();
  setTimeout(scheduleFireworks, 500);

  // Initial burst — 5 fireworks in rapid succession for that BIG BANG
  for (var b = 0; b < 5; b++) {
    setTimeout(launchFirework, b * 400);
  }
})();

/* ── Matrix Decryption Engine ────────────────── */
(function initMatrixReveal() {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#$%^&*()_+-=<>?|\\/{}[]~';
  const els = document.querySelectorAll('.matrix-reveal p');
  
  els.forEach(el => {
    // Preserve the original HTML including the <strong> and <span> tags
    const originalHTML = el.innerHTML;
    // We only scramble text nodes to avoid breaking HTML tags
    const textNodes = [];
    const walk = document.createTreeWalker(el, NodeFilter.SHOW_TEXT, null, false);
    let node;
    while(node = walk.nextNode()) {
      if (node.nodeValue.trim().length > 0) {
        textNodes.push({ node: node, originalText: node.nodeValue });
      }
    }
    
    let iterations = 0;
    const maxIterations = 20;
    
    const scramble = setInterval(() => {
      textNodes.forEach(item => {
        let scrambledText = '';
        const text = item.originalText;
        for (let i = 0; i < text.length; i++) {
          if (text[i] === ' ' || text[i] === '\n') {
            scrambledText += text[i];
          } else {
            // Decrypt characters one by one from left to right
            if (i < (text.length / maxIterations) * iterations) {
              scrambledText += text[i];
            } else {
              scrambledText += chars[Math.floor(Math.random() * chars.length)];
            }
          }
        }
        item.node.nodeValue = scrambledText;
      });
      
      iterations++;
      if (iterations >= maxIterations) {
        clearInterval(scramble);
        // Ensure final HTML is perfectly restored in case of any weirdness
        el.innerHTML = originalHTML;
      }
    }, 50);
  });
})();
</script>
</body>
</html>

