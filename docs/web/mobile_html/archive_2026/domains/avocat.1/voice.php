<?php
/**
 * Voice.php — Alfred Voice Client
 * Detects login → passes auth token to JS for authenticated tool access.
 * Anonymous users get chat-only mode (no tool access, no private data).
 */

if (session_status() === PHP_SESSION_NONE) session_start();

$voiceAuthToken = '';
$voiceUsername  = '';
$voiceIsAuth    = false;

if (isset($_SESSION['uid']) && $_SESSION['uid'] > 0) {
    $jwtSecret = '';
    $envFile = __DIR__ . '/gocodeme/mcp-server/.env';
    if (file_exists($envFile)) {
        $envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($envLines as $line) {
            if (strpos($line, '#') === 0) continue;
            if (preg_match('/^JWT_SECRET=(.+)$/', trim($line), $m)) { $jwtSecret = trim($m[1]); break; }
        }
    }
    if ($jwtSecret) {
        try {
            $pdo = function_exists('getDB') ? getDB() : null;
            if (!$pdo) {
                $dbHost = defined('CONFIG_DB_HOST') ? CONFIG_DB_HOST : 'localhost';
                $dbName = defined('CONFIG_DB_NAME') ? CONFIG_DB_NAME : '';
                $dbUser = defined('CONFIG_DB_USER') ? CONFIG_DB_USER : '';
                $dbPass = defined('CONFIG_DB_PASS') ? CONFIG_DB_PASS : '';
                if ($dbName) { $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass); $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); }
            }
            if ($pdo) {
                $stmt = $pdo->prepare("SELECT c.firstname, c.lastname, (SELECT cfv.value FROM custom_field_values cfv JOIN custom_fields cf ON cf.id = cfv.field_id WHERE cfv.entity_id = c.id AND cf.field_name = 'DirectAdmin Username' LIMIT 1) as da_username FROM clients c WHERE c.id = ? AND c.status = 'Active'");
                $stmt->execute([(int)$_SESSION['uid']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user) {
                    $daUser = $user['da_username'] ?: '';
                    if (!$daUser) { $stmt2 = $pdo->prepare("SELECT username FROM services WHERE userid = ? AND domainstatus = 'Active' LIMIT 1"); $stmt2->execute([(int)$_SESSION['uid']]); $hosting = $stmt2->fetch(PDO::FETCH_ASSOC); if ($hosting) $daUser = $hosting['username']; }
                    if ($daUser) {
                        $header  = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
                        $payload = json_encode(['clientId' => (int)$_SESSION['uid'], 'daUsername' => $daUser, 'plan' => 'active', 'iat' => time(), 'exp' => time() + 28800]);
                        $b64Header  = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
                        $b64Payload = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
                        $b64Sig     = rtrim(strtr(base64_encode(hash_hmac('sha256', "$b64Header.$b64Payload", $jwtSecret, true)), '+/', '-_'), '=');
                        $voiceAuthToken = "$b64Header.$b64Payload.$b64Sig";
                        $voiceUsername  = $daUser;
                        $voiceIsAuth    = true;
                    }
                }
            }
        } catch (Throwable $e) {}
    }
}

$page_title = 'Alfred Voice — GoSiteMe.com';
$page_description = 'Talk to Alfred, your AI voice assistant. Get instant help with web hosting, domains, billing, and 24 AI-powered agents across 6 languages.';
$page_canonical = 'https://gositeme.com/voice.php';
$page_og_title = 'Alfred Voice — AI Assistant | GoSiteMe.com';
$page_og_description = 'Talk to Alfred, your AI voice assistant. 24 agents, 18 voices, 3 TTS engines, 6 languages. Instant help with hosting, domains, and billing.';
$page_og_image = 'https://gositeme.com/assets/img/hero-banner.png';
include __DIR__ . '/includes/site-header.inc.php';
?>
<style>
  /* ── Hide site navbar on voice page — immersive app ── */
  .top-bar, .navbar, #navbar { display:none !important; }

  * { margin:0; padding:0; box-sizing:border-box; }

  /* ── MOBILE FIX: scroll instead of clip, no forced centering ── */
  html { height:100%; }
  body {
    background:#06060c;
    color:#fff;
    font-family:'Segoe UI',system-ui,sans-serif;
    min-height:100vh;
    display:flex;
    flex-direction:column;
    align-items:center;
    overflow-x:hidden;
    overflow-y:auto;
    padding-bottom:env(safe-area-inset-bottom, 16px);
  }

  /* Animated particle canvas background */
  #cmdBg { position:fixed; inset:0; z-index:0; pointer-events:none; }

  body::before {
    content:'';
    position:fixed;
    top:-200px;
    left:50%;
    transform:translateX(-50%);
    width:800px;
    height:800px;
    background:radial-gradient(circle,rgba(125,0,255,0.15) 0%,rgba(0,212,255,0.05) 40%,transparent 70%);
    pointer-events:none;
    animation:ambient 6s ease-in-out infinite;
    z-index:0;
  }

  /* Scanline overlay for command center feel */
  body::after {
    content:'';
    position:fixed;
    inset:0;
    background:repeating-linear-gradient(0deg, transparent, transparent 2px, rgba(0,0,0,0.03) 2px, rgba(0,0,0,0.03) 4px);
    pointer-events:none;
    z-index:1;
  }
  body.live-active::before {
    background:radial-gradient(circle,rgba(239,68,68,0.12) 0%,rgba(125,0,255,0.06) 40%,transparent 70%);
    animation:ambient-live 4s ease-in-out infinite;
  }
  @keyframes ambient { 0%,100%{opacity:.6;transform:translateX(-50%) scale(1)} 50%{opacity:1;transform:translateX(-50%) scale(1.1)} }
  @keyframes ambient-live { 0%,100%{opacity:.7;transform:translateX(-50%) scale(1)} 50%{opacity:1;transform:translateX(-50%) scale(1.15)} }

  .container {
    position:relative;
    z-index:2;
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:18px;
    padding:28px 16px 32px;
    width:100%;
    max-width:560px;
  }

  /* Header — Command Center */
  .header { text-align:center; }
  .header .logo { font-size:0.7rem; font-weight:700; letter-spacing:4px; text-transform:uppercase; color:rgba(0,212,255,0.5); margin-bottom:4px; }
  .header h1 { font-size:2.4rem; font-weight:900; background:linear-gradient(135deg,#fff 0%,#c084fc 40%,#00D4FF 100%); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; letter-spacing:-1px; line-height:1.1; }
  .header .subtitle { font-size:0.82rem; color:rgba(255,255,255,0.25); margin-top:4px; letter-spacing:1px; text-transform:uppercase; font-weight:600; }
  .header .tagline { font-size:0.9rem; color:rgba(255,255,255,0.4); margin-top:6px; }

  /* Stats bar */
  .cmd-stats { display:flex; gap:6px; flex-wrap:wrap; justify-content:center; margin-top:2px; }
  .cmd-stat { display:flex; align-items:center; gap:5px; padding:4px 10px; border-radius:100px; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.06); font-size:0.65rem; font-weight:700; color:rgba(255,255,255,0.35); white-space:nowrap; transition:all 0.3s; }
  .cmd-stat:hover { border-color:rgba(125,0,255,0.3); color:rgba(255,255,255,0.6); }
  .cmd-stat .cs-val { color:#c084fc; }
  .cmd-stat .cs-icon { font-size:0.7rem; }

  /* Capability ticker */
  .cap-ticker-wrap { width:100%; overflow:hidden; position:relative; height:22px; margin-top:-6px; }
  .cap-ticker-wrap::before, .cap-ticker-wrap::after { content:''; position:absolute; top:0; bottom:0; width:40px; z-index:3; pointer-events:none; }
  .cap-ticker-wrap::before { left:0; background:linear-gradient(90deg,#06060c,transparent); }
  .cap-ticker-wrap::after { right:0; background:linear-gradient(270deg,#06060c,transparent); }
  .cap-ticker { display:flex; gap:24px; animation:tickerScroll 40s linear infinite; white-space:nowrap; }
  .cap-ticker span { font-size:0.62rem; color:rgba(255,255,255,0.18); font-weight:600; letter-spacing:0.5px; text-transform:uppercase; }
  .cap-ticker span em { font-style:normal; color:rgba(0,212,255,0.4); }
  @keyframes tickerScroll { 0%{transform:translateX(0)} 100%{transform:translateX(-50%)} }

  /* LIVE badge */
  .live-badge { display:none; align-items:center; gap:8px; padding:6px 18px; border-radius:100px; background:rgba(239,68,68,0.15); border:1px solid rgba(239,68,68,0.4); color:#ef4444; font-size:0.82rem; font-weight:800; letter-spacing:2px; text-transform:uppercase; animation:live-pulse 1.5s ease-in-out infinite; margin-top:-4px; }
  .live-badge.visible { display:flex; }
  .live-badge .live-dot { width:8px; height:8px; border-radius:50%; background:#ef4444; animation:live-dot-blink 1s ease-in-out infinite; }
  @keyframes live-pulse { 0%,100%{box-shadow:0 0 0 rgba(239,68,68,0)} 50%{box-shadow:0 0 16px rgba(239,68,68,0.3)} }
  @keyframes live-dot-blink { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.4;transform:scale(.8)} }

  /* Mode strip */
  .mode-strip { display:flex; gap:10px; align-items:center; opacity:0; pointer-events:none; transition:opacity 0.4s; }
  .mode-strip.visible { opacity:1; pointer-events:all; }
  .mode-btn { padding:8px 20px; border-radius:100px; border:none; cursor:pointer; font-size:0.82rem; font-weight:700; transition:all 0.25s; display:flex; align-items:center; gap:6px; }
  .mode-btn.tap-mode { background:rgba(125,0,255,0.15); border:1px solid rgba(125,0,255,0.3); color:#c084fc; }
  .mode-btn.tap-mode.active { background:rgba(125,0,255,0.3); border-color:rgba(125,0,255,0.6); color:#fff; box-shadow:0 0 16px rgba(125,0,255,0.3); }
  .mode-btn.live-mode { background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.25); color:rgba(239,68,68,0.7); }
  .mode-btn.live-mode.active { background:rgba(239,68,68,0.2); border-color:rgba(239,68,68,0.6); color:#ef4444; box-shadow:0 0 16px rgba(239,68,68,0.25); }
  .mode-btn:hover { transform:translateY(-1px); }

  /* Orb — smaller on mobile */
  .orb-wrapper {
    position:relative;
    width:160px; height:160px;
    cursor:pointer;
    touch-action:manipulation;
    -webkit-tap-highlight-color:transparent;
    user-select:none; -webkit-user-select:none;
  }
  .orb {
    width:160px; height:160px;
    border-radius:50%;
    background:radial-gradient(circle at 35% 35%,rgba(192,132,252,0.9),rgba(125,0,255,0.6) 50%,rgba(0,212,255,0.3));
    box-shadow:0 0 40px rgba(125,0,255,0.4),0 0 80px rgba(125,0,255,0.2),inset 0 0 40px rgba(0,212,255,0.1);
    transition:all 0.3s;
    display:flex; align-items:center; justify-content:center;
    position:relative; overflow:hidden;
  }
  .orb::before { content:''; position:absolute; top:15%; left:20%; width:35%; height:30%; background:rgba(255,255,255,0.15); border-radius:50%; filter:blur(8px); transform:rotate(-30deg); }
  .orb .mic-icon { font-size:2.6rem; filter:drop-shadow(0 2px 8px rgba(0,0,0,0.5)); transition:transform 0.3s; position:relative; z-index:1; }
  @keyframes idle-pulse { 0%,100%{box-shadow:0 0 40px rgba(125,0,255,0.4),0 0 80px rgba(125,0,255,0.2)} 50%{box-shadow:0 0 60px rgba(125,0,255,0.6),0 0 120px rgba(125,0,255,0.3)} }
  .orb.idle { animation:idle-pulse 3s ease-in-out infinite; }
  @keyframes live-idle-pulse { 0%,100%{box-shadow:0 0 40px rgba(239,68,68,0.2),0 0 80px rgba(125,0,255,0.15)} 50%{box-shadow:0 0 60px rgba(239,68,68,0.35),0 0 120px rgba(125,0,255,0.2)} }
  .orb.live-idle { animation:live-idle-pulse 2.5s ease-in-out infinite; }
  @keyframes listen-ring { 0%{transform:scale(1);opacity:.8} 100%{transform:scale(1.8);opacity:0} }
  .orb.listening { background:radial-gradient(circle at 35% 35%,rgba(0,212,255,0.9),rgba(0,150,255,0.6) 50%,rgba(125,0,255,0.3)); box-shadow:0 0 60px rgba(0,212,255,0.6),0 0 120px rgba(0,212,255,0.3); animation:none; }
  .ring { position:absolute; width:160px; height:160px; border-radius:50%; border:2px solid rgba(0,212,255,0.5); animation:listen-ring 1.5s ease-out infinite; pointer-events:none; }
  .ring:nth-child(2){animation-delay:.5s} .ring:nth-child(3){animation-delay:1s}
  @keyframes speaking-wave { 0%,100%{transform:scale(1)} 25%{transform:scale(1.05)} 75%{transform:scale(.97)} }
  .orb.speaking { background:radial-gradient(circle at 35% 35%,rgba(16,185,129,0.9),rgba(5,150,105,0.6) 50%,rgba(0,212,255,0.3)); box-shadow:0 0 60px rgba(16,185,129,0.6),0 0 120px rgba(16,185,129,0.3); animation:speaking-wave .5s ease-in-out infinite; }
  .orb.processing { background:radial-gradient(circle at 35% 35%,rgba(251,146,60,0.9),rgba(217,119,6,0.6) 50%,rgba(125,0,255,0.3)); box-shadow:0 0 60px rgba(251,146,60,0.6),0 0 120px rgba(251,146,60,0.3); animation:none; }
  .vad-ring { position:absolute; width:160px; height:160px; border-radius:50%; border:3px solid rgba(0,212,255,0); pointer-events:none; transition:transform .08s,border-color .08s; }
  .silence-countdown { position:absolute; top:0; left:0; width:160px; height:160px; pointer-events:none; transform:rotate(-90deg); display:none; }
  .silence-countdown circle { fill:none; stroke:rgba(239,68,68,0.5); stroke-width:3; stroke-dasharray:477; stroke-dashoffset:477; stroke-linecap:round; transition:stroke-dashoffset .1s linear; }

  /* Volume / VAD */
  .volume-bar { width:100%; height:4px; background:rgba(255,255,255,0.08); border-radius:2px; overflow:hidden; display:none; }
  .volume-bar.visible { display:block; }
  .volume-fill { height:100%; background:linear-gradient(90deg,#7D00FF,#00D4FF); border-radius:2px; width:0%; transition:width .1s; }
  .vad-meter { width:100%; display:none; flex-direction:column; gap:6px; }
  .vad-meter.visible { display:flex; }
  .vad-meter-label { display:flex; justify-content:space-between; align-items:center; font-size:.72rem; color:rgba(255,255,255,0.25); }
  .vad-state { font-weight:700; font-size:.75rem; color:rgba(255,255,255,0.4); transition:color .2s; }
  .vad-state.speaking { color:#00D4FF; }
  .vad-state.silence { color:rgba(255,255,255,0.2); }
  .vad-bars { display:flex; gap:3px; align-items:flex-end; height:28px; justify-content:center; }
  .vad-bar { width:4px; border-radius:2px; background:linear-gradient(180deg,#00D4FF,#7D00FF); height:4px; transition:height .08s,opacity .08s; opacity:.3; }

  /* Status */
  .status { text-align:center; min-height:44px; }
  .status-label { font-size:1rem; font-weight:600; color:rgba(255,255,255,0.8); margin-bottom:4px; }
  .status-sub { font-size:.82rem; color:rgba(255,255,255,0.35); }

  /* ══ AGENT SELECTOR ══ */
  .agent-selector-wrap { width:100%; opacity:0; pointer-events:none; transition:opacity .4s; }
  .agent-selector-wrap.visible { opacity:1; pointer-events:all; }

  /* Toggle row */
  .agent-toggle { width:100%; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:14px; padding:12px 16px; cursor:pointer; display:flex; align-items:center; justify-content:space-between; transition:border-color .2s,background .2s; color:#fff; }
  .agent-toggle:hover { border-color:rgba(125,0,255,0.35); background:rgba(255,255,255,0.05); }
  .agent-toggle.open { border-color:rgba(125,0,255,0.4); border-radius:14px 14px 0 0; }
  .agent-toggle-left { display:flex; align-items:center; gap:12px; }
  .agent-avatar-sm { width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0; position:relative; }
  .agent-avatar-sm .crown { position:absolute; top:-6px; right:-4px; font-size:.7rem; }
  .agent-toggle-info .agent-toggle-label { font-size:.72rem; color:rgba(255,255,255,0.35); letter-spacing:.5px; text-transform:uppercase; }
  .agent-toggle-info .agent-toggle-name { font-size:1rem; font-weight:800; color:#fff; }
  .agent-toggle-info .agent-toggle-role { font-size:.72rem; color:rgba(255,255,255,0.35); margin-top:1px; }
  .agent-toggle-arrow { font-size:.7rem; color:rgba(255,255,255,0.3); transition:transform .2s; }
  .agent-toggle.open .agent-toggle-arrow { transform:rotate(180deg); }

  /* Panel — scrollable on mobile, no overflow:hidden parent to block it */
  .agent-panel { display:none; background:rgba(0,0,0,0.55); border:1px solid rgba(255,255,255,0.08); border-top:none; border-radius:0 0 16px 16px; overflow:hidden; backdrop-filter:blur(14px); }
  .agent-panel.open { display:block; animation:fadeIn .2s ease; }
  @keyframes fadeIn { from{opacity:0;transform:translateY(-6px)} to{opacity:1;transform:translateY(0)} }

  /* Category tabs */
  .agent-cat-bar { display:flex; overflow-x:auto; border-bottom:1px solid rgba(255,255,255,0.06); scrollbar-width:none; }
  .agent-cat-bar::-webkit-scrollbar { display:none; }
  .agent-cat-tab { padding:9px 16px; font-size:.73rem; font-weight:700; color:rgba(255,255,255,0.3); cursor:pointer; white-space:nowrap; border-bottom:2px solid transparent; transition:all .2s; background:none; border-top:none; border-left:none; border-right:none; flex-shrink:0; }
  .agent-cat-tab:hover { color:rgba(255,255,255,0.7); }
  .agent-cat-tab.active { color:#fff; border-bottom-color:#c084fc; }

  .agent-cat-desc { padding:8px 16px; font-size:.72rem; color:rgba(255,255,255,0.25); font-style:italic; border-bottom:1px solid rgba(255,255,255,0.04); }

  /* Agent grid */
  .agent-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; padding:12px; }

  .agent-card { background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.07); border-radius:12px; padding:12px 8px 10px; text-align:center; cursor:pointer; transition:all .2s; position:relative; overflow:hidden; }
  .agent-card::before { content:''; position:absolute; inset:0; opacity:0; transition:opacity .2s; }
  .agent-card:hover { transform:translateY(-2px); }
  .agent-card:hover::before { opacity:1; }
  .agent-card.selected { transform:translateY(-2px); }
  .agent-card.selected::before { opacity:1; }

  .agent-card { --ac: rgba(125,0,255,0.15); --ab: rgba(125,0,255,0.4); }
  .agent-card::before { background:var(--ac); }
  .agent-card.selected { border-color:var(--ab); box-shadow:0 0 16px var(--ac); }
  .agent-card:hover { border-color:rgba(255,255,255,0.15); }

  .agent-card .ac-avatar { font-size:1.6rem; display:block; margin-bottom:5px; position:relative; z-index:1; }
  .agent-card .ac-crown { position:absolute; top:6px; left:50%; transform:translateX(8px); font-size:.65rem; }
  .agent-card .ac-name { font-size:.82rem; font-weight:800; color:#fff; margin-bottom:2px; position:relative; z-index:1; }
  .agent-card .ac-role { font-size:.62rem; font-weight:600; text-transform:uppercase; letter-spacing:.5px; margin-bottom:3px; position:relative; z-index:1; }
  .agent-card .ac-bio { font-size:.6rem; color:rgba(255,255,255,0.28); line-height:1.4; position:relative; z-index:1; }
  .agent-card .ac-check { position:absolute; top:6px; right:6px; width:16px; height:16px; border-radius:50%; background:#c084fc; display:none; align-items:center; justify-content:center; font-size:.55rem; color:#fff; font-weight:900; z-index:2; }
  .agent-card.selected .ac-check { display:flex; }
  .agent-card .ac-default { position:absolute; top:6px; left:6px; background:rgba(16,185,129,0.2); color:#10b981; font-size:.5rem; font-weight:800; padding:2px 5px; border-radius:4px; text-transform:uppercase; letter-spacing:.5px; z-index:2; }

  /* Preview / Apply bar */
  .agent-preview-bar { padding:10px 14px; border-top:1px solid rgba(255,255,255,0.05); display:flex; align-items:center; justify-content:space-between; gap:8px; flex-wrap:wrap; }
  .agent-preview-bar .ap-info { font-size:.72rem; color:rgba(255,255,255,0.28); }
  .agent-preview-bar .ap-info strong { color:rgba(255,255,255,0.65); }
  .btn-prev-agent { padding:6px 14px; border-radius:8px; background:rgba(125,0,255,0.2); border:1px solid rgba(125,0,255,0.35); color:#c084fc; font-size:.75rem; font-weight:700; cursor:pointer; transition:all .2s; display:flex; align-items:center; gap:5px; }
  .btn-prev-agent:hover { background:rgba(125,0,255,0.35); border-color:rgba(125,0,255,0.6); }
  .btn-prev-agent:disabled { opacity:.5; cursor:not-allowed; }
  .btn-apply-agent { padding:6px 16px; border-radius:8px; background:rgba(16,185,129,0.15); border:1px solid rgba(16,185,129,0.3); color:#10b981; font-size:.75rem; font-weight:700; cursor:pointer; transition:all .2s; }
  .btn-apply-agent:hover { background:rgba(16,185,129,0.3); border-color:rgba(16,185,129,0.6); }

  /* Transcript */
  .transcript-box { width:100%; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:16px; padding:20px; min-height:80px; max-height:160px; overflow-y:auto; display:none; }
  .transcript-box.visible { display:block; }
  .transcript-entry { margin-bottom:12px; font-size:.88rem; line-height:1.5; animation:fadeIn .3s ease; }
  .transcript-entry.user { color:#c084fc; }
  .transcript-entry.user::before { content:'🎤 '; }
  .transcript-entry.alfred { color:#00D4FF; }
  .transcript-entry.alfred::before { content:'🤖 '; }
  .transcript-entry.system { color:rgba(255,255,255,0.3); font-style:italic; font-size:.8rem; }

  /* Buttons */
  .btn-connect { padding:14px 40px; background:linear-gradient(135deg,#7D00FF,#00D4FF); border:none; border-radius:50px; color:#fff; font-size:1rem; font-weight:700; cursor:pointer; letter-spacing:.5px; transition:all .3s; box-shadow:0 4px 20px rgba(125,0,255,0.4); width:100%; max-width:300px; }
  .btn-connect:hover { transform:translateY(-2px); box-shadow:0 8px 30px rgba(125,0,255,0.5); }
  .btn-connect:active { transform:translateY(0); }
  .btn-connect.connected { background:linear-gradient(135deg,#ef4444,#dc2626); box-shadow:0 4px 20px rgba(239,68,68,0.4); }
  .btn-connect:disabled { opacity:.6; cursor:not-allowed; transform:none; }
  .conn-dot { display:inline-block; width:8px; height:8px; border-radius:50%; background:#374151; margin-right:6px; vertical-align:middle; transition:background .3s; }
  .conn-dot.connected { background:#10b981; box-shadow:0 0 6px #10b981; }
  .conn-dot.connecting { background:#f59e0b; box-shadow:0 0 6px #f59e0b; animation:blink 1s ease-in-out infinite; }
  .conn-dot.error { background:#ef4444; box-shadow:0 0 6px #ef4444; }
  @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.3} }
  .footer-note { font-size:.75rem; color:rgba(255,255,255,0.2); text-align:center; padding-bottom:8px; }
  .hold-hint { font-size:.78rem; color:rgba(255,255,255,0.25); text-align:center; }

  /* Fleet Status Panel */
  .fleet-panel { width:100%; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.06); border-radius:14px; overflow:hidden; margin-top:4px; }
  .fleet-header { display:flex; align-items:center; justify-content:space-between; padding:10px 14px; border-bottom:1px solid rgba(255,255,255,0.04); cursor:pointer; transition:background 0.2s; }
  .fleet-header:hover { background:rgba(255,255,255,0.02); }
  .fleet-header h4 { font-size:0.7rem; letter-spacing:1px; text-transform:uppercase; color:rgba(255,255,255,0.3); font-weight:700; display:flex; align-items:center; gap:6px; }
  .fleet-header .fleet-count { font-size:0.6rem; background:rgba(125,0,255,0.2); color:#c084fc; padding:2px 8px; border-radius:100px; font-weight:800; }
  .fleet-header .fleet-arrow { font-size:0.6rem; color:rgba(255,255,255,0.2); transition:transform 0.2s; }
  .fleet-grid { display:none; grid-template-columns:repeat(4,1fr); gap:6px; padding:10px; }
  .fleet-grid.open { display:grid; animation:fadeIn 0.3s ease; }
  .fleet-agent { display:flex; flex-direction:column; align-items:center; gap:3px; padding:8px 4px; border-radius:10px; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.04); transition:all 0.3s; position:relative; }
  .fleet-agent:hover { border-color:rgba(125,0,255,0.3); transform:translateY(-1px); }
  .fleet-agent .fa-emoji { font-size:1.2rem; }
  .fleet-agent .fa-name { font-size:0.6rem; font-weight:800; color:rgba(255,255,255,0.6); }
  .fleet-agent .fa-role { font-size:0.5rem; color:rgba(255,255,255,0.2); text-align:center; line-height:1.3; }
  .fleet-agent .fa-dot { position:absolute; top:4px; right:4px; width:5px; height:5px; border-radius:50%; background:#10b981; box-shadow:0 0 4px #10b981; animation:fa-blink 2s ease-in-out infinite; }
  @keyframes fa-blink { 0%,100%{opacity:1} 50%{opacity:0.4} }

  /* Engine status */
  .engine-bar { width:100%; display:flex; gap:4px; flex-wrap:wrap; justify-content:center; padding:0 4px; }
  .engine-chip { display:flex; align-items:center; gap:3px; padding:3px 8px; border-radius:6px; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.04); font-size:0.52rem; font-weight:700; letter-spacing:0.5px; text-transform:uppercase; color:rgba(255,255,255,0.2); transition:all 0.3s; }
  .engine-chip:hover { border-color:rgba(0,212,255,0.3); color:rgba(0,212,255,0.6); }
  .engine-chip .ec-dot { width:4px; height:4px; border-radius:50%; background:#10b981; flex-shrink:0; }

  /* ── Responsive tweaks for small screens ── */
  @media (max-width: 480px) {
    .container { gap:14px; padding:20px 12px 28px; }
    .header h1 { font-size:1.8rem; }
    .orb-wrapper { width:140px; height:140px; }
    .orb { width:140px; height:140px; }
    .orb .mic-icon { font-size:2.2rem; }
    .ring { width:140px; height:140px; }
    .vad-ring { width:140px; height:140px; }
    .silence-countdown { width:140px; height:140px; }
    .silence-countdown circle { stroke-dasharray:420; stroke-dashoffset:420; }
    .agent-grid { grid-template-columns:repeat(2,1fr) !important; }
    .btn-connect { padding:12px 24px; font-size:.95rem; }
    .help-panel { width:100%; }
    .help-trigger { bottom:16px; left:16px; width:42px; height:42px; font-size:1.2rem; }
    .fleet-grid { grid-template-columns:repeat(3,1fr); }
    .cmd-stats { gap:4px; }
    .cmd-stat { padding:3px 7px; font-size:0.58rem; }
    .engine-bar { gap:3px; }
  }

  /* ── Help Panel ── */
  .help-trigger { position:fixed; bottom:24px; left:24px; width:48px; height:48px; border-radius:50%; background:#7c3aed; color:#fff; font-size:1.4rem; font-weight:900; border:none; cursor:pointer; z-index:1000; box-shadow:0 4px 20px rgba(124,58,237,0.5); display:none; align-items:center; justify-content:center; transition:all 0.3s; }
  .help-trigger:hover { transform:scale(1.1); box-shadow:0 6px 28px rgba(124,58,237,0.7); }
  .help-trigger.visible { display:flex; }
  .help-panel { position:fixed; bottom:0; left:0; width:400px; max-height:70vh; background:rgba(15,15,25,0.98); border-radius:16px 16px 0 0; border:1px solid rgba(255,255,255,0.1); border-bottom:none; z-index:999; transform:translateY(100%); transition:transform 0.35s cubic-bezier(0.4,0,0.2,1); overflow:hidden; display:flex; flex-direction:column; backdrop-filter:blur(20px); }
  .help-panel.open { transform:translateY(0); }
  .help-header { display:flex; align-items:center; justify-content:space-between; padding:16px 20px; border-bottom:1px solid rgba(255,255,255,0.08); flex-shrink:0; }
  .help-header h3 { font-size:1.1rem; font-weight:800; color:#fff; margin:0; }
  .help-close { font-size:1.6rem; color:rgba(255,255,255,0.4); cursor:pointer; line-height:1; transition:color 0.2s; }
  .help-close:hover { color:#fff; }
  .help-categories { display:flex; flex-wrap:wrap; gap:6px; padding:12px 20px; border-bottom:1px solid rgba(255,255,255,0.06); flex-shrink:0; }
  .help-cat { padding:5px 12px; border-radius:100px; border:1px solid rgba(255,255,255,0.1); background:rgba(255,255,255,0.04); color:rgba(255,255,255,0.5); font-size:0.72rem; font-weight:700; cursor:pointer; transition:all 0.2s; white-space:nowrap; }
  .help-cat:hover { border-color:rgba(124,58,237,0.4); color:rgba(255,255,255,0.8); }
  .help-cat.active { background:rgba(124,58,237,0.25); border-color:rgba(124,58,237,0.5); color:#c084fc; }
  .help-examples { flex:1; overflow-y:auto; padding:12px 16px; scrollbar-width:thin; scrollbar-color:rgba(255,255,255,0.1) transparent; }
  .help-examples::-webkit-scrollbar { width:4px; }
  .help-examples::-webkit-scrollbar-thumb { background:rgba(255,255,255,0.1); border-radius:2px; }
  .help-example { display:flex; align-items:flex-start; gap:12px; padding:12px 14px; border-radius:12px; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.06); cursor:pointer; transition:all 0.2s; margin-bottom:8px; }
  .help-example:hover { background:rgba(124,58,237,0.12); border-color:rgba(124,58,237,0.3); transform:translateX(4px); }
  .help-example .he-icon { font-size:1.3rem; flex-shrink:0; margin-top:2px; }
  .help-example .he-body { flex:1; min-width:0; }
  .help-example .he-cmd { font-size:0.85rem; font-weight:700; color:#fff; margin-bottom:3px; line-height:1.3; }
  .help-example .he-desc { font-size:0.7rem; color:rgba(255,255,255,0.35); line-height:1.3; }

  /* ── Steering Queue & Text Input ── */
  .steering-toast { position:fixed; top:20px; left:50%; transform:translateX(-50%) translateY(-100px); background:rgba(15,15,25,0.95); border:1px solid rgba(124,58,237,0.4); border-radius:12px; padding:10px 20px; color:#fff; font-size:0.82rem; font-weight:600; z-index:1100; backdrop-filter:blur(12px); transition:transform 0.35s cubic-bezier(0.4,0,0.2,1); pointer-events:none; max-width:90vw; text-align:center; }
  .steering-toast.visible { transform:translateX(-50%) translateY(0); }
  .steering-toast.abort { border-color:rgba(239,68,68,0.5); }
  .steering-toast .st-icon { margin-right:8px; }

  .queue-badge { display:none; position:absolute; top:-4px; right:-4px; min-width:20px; height:20px; border-radius:10px; background:#7c3aed; color:#fff; font-size:0.65rem; font-weight:800; align-items:center; justify-content:center; padding:0 5px; z-index:10; box-shadow:0 2px 8px rgba(124,58,237,0.5); animation:badge-pop 0.3s ease; }
  .queue-badge.visible { display:flex; }
  @keyframes badge-pop { from{transform:scale(0)} to{transform:scale(1)} }

  .text-input-bar { width:100%; display:flex; gap:8px; align-items:center; opacity:0; pointer-events:none; transition:opacity 0.3s; }
  .text-input-bar.visible { opacity:1; pointer-events:all; }
  .text-input-bar input { flex:1; padding:12px 16px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); border-radius:12px; color:#fff; font-size:0.88rem; font-family:inherit; outline:none; transition:border-color 0.2s; }
  .text-input-bar input::placeholder { color:rgba(255,255,255,0.25); }
  .text-input-bar input:focus { border-color:rgba(124,58,237,0.5); }
  .text-input-bar button { padding:12px 18px; border-radius:12px; background:linear-gradient(135deg,#7D00FF,#00D4FF); border:none; color:#fff; font-size:0.88rem; font-weight:700; cursor:pointer; transition:all 0.2s; flex-shrink:0; }
  .text-input-bar button:hover { transform:translateY(-1px); box-shadow:0 4px 16px rgba(125,0,255,0.4); }
  .text-input-bar button:disabled { opacity:0.5; cursor:not-allowed; transform:none; }

  .queue-indicator { display:none; width:100%; text-align:center; padding:8px 16px; background:rgba(124,58,237,0.1); border:1px solid rgba(124,58,237,0.2); border-radius:10px; font-size:0.78rem; color:#c084fc; font-weight:600; animation:fadeIn 0.3s ease; }
  .queue-indicator.visible { display:block; }
  .queue-indicator .qi-count { font-weight:900; color:#fff; }
</style>
<canvas id="cmdBg"></canvas>
<div class="container">

  <div class="header">
    <div class="logo">GoSiteMe.com</div>
    <h1>Alfred Command Center</h1>
    <div class="subtitle">Voice-Activated AI Operations Hub</div>
    <div class="tagline">
      <span class="conn-dot" id="connDot"></span>
      <span id="connLabel">Awaiting connection</span>
    </div>
  </div>

  <div class="cmd-stats">
    <div class="cmd-stat"><span class="cs-icon">⚡</span> <span class="cs-val" id="statTools">1,220</span>+ Tools</div>
    <div class="cmd-stat"><span class="cs-icon">🤖</span> <span class="cs-val">24</span> Agents</div>
    <div class="cmd-stat"><span class="cs-icon">🧠</span> <span class="cs-val">16</span> AI Engines</div>
    <div class="cmd-stat"><span class="cs-icon">🌍</span> <span class="cs-val">89</span> Categories</div>
    <div class="cmd-stat"><span class="cs-icon">💻</span> <span class="cs-val">300</span>+ Languages</div>
  </div>

  <div class="cap-ticker-wrap">
    <div class="cap-ticker" id="capTicker"></div>
  </div>

  <div class="live-badge" id="liveBadge"><span class="live-dot"></span> LIVE</div>

  <div class="orb-wrapper" id="orbWrapper">
    <div class="queue-badge" id="queueBadge">0</div>
    <div class="ring" id="ring1" style="display:none"></div>
    <div class="ring" id="ring2" style="display:none"></div>
    <div class="ring" id="ring3" style="display:none"></div>
    <div class="vad-ring" id="vadRing"></div>
    <svg class="silence-countdown" id="silenceCountdown" viewBox="0 0 160 160"><circle cx="80" cy="80" r="75"/></svg>
    <div class="orb idle" id="orb"><span class="mic-icon" id="micIcon">🎙️</span></div>
  </div>

  <div class="volume-bar" id="volumeBar"><div class="volume-fill" id="volumeFill"></div></div>

  <div class="vad-meter" id="vadMeter">
    <div class="vad-meter-label"><span>Voice Activity</span><span class="vad-state silence" id="vadStateLabel">silence</span></div>
    <div class="vad-bars" id="vadBars"></div>
  </div>

  <div class="status">
    <div class="status-label" id="statusLabel">Tap to start talking</div>
    <div class="status-sub" id="statusSub">Real-time voice · 1,220+ tools · command steering · e-commerce, SEO, DevOps & more</div>
  </div>

  <div class="mode-strip" id="modeStrip">
    <button class="mode-btn tap-mode active" id="btnTapMode" onclick="VoiceCmd.setMode('tap')">🎙️ Tap Mode</button>
    <button class="mode-btn live-mode" id="btnLiveMode" onclick="VoiceCmd.setMode('live')">🔴 Go Live</button>
  </div>

  <!-- ══ AGENT SELECTOR ══ -->
  <div class="agent-selector-wrap" id="agentSelectorWrap">
    <button class="agent-toggle" id="agentToggle" onclick="VoiceCmd.toggleAgentPanel()">
      <div class="agent-toggle-left">
        <div class="agent-avatar-sm" id="toggleAvatar" style="background:rgba(125,0,255,0.2);">
          🎩<span class="crown">👑</span>
        </div>
        <div class="agent-toggle-info">
          <div class="agent-toggle-label">Active Agent</div>
          <div class="agent-toggle-name" id="toggleName">Alfred</div>
          <div class="agent-toggle-role" id="toggleRole">AI Assistant · Deep &amp; Authoritative</div>
        </div>
      </div>
      <span class="agent-toggle-arrow">▼</span>
    </button>

    <div class="agent-panel" id="agentPanel">
      <div class="agent-cat-bar" id="agentCatBar"></div>
      <div class="agent-cat-desc" id="agentCatDesc"></div>
      <div class="agent-grid" id="agentGrid"></div>
      <div class="agent-preview-bar">
        <div class="ap-info">Selected: <strong id="apSelected">Alfred · AI Assistant</strong></div>
        <div style="display:flex;gap:6px;">
          <button class="btn-prev-agent" id="btnPrevAgent" onclick="VoiceCmd.previewAgent()">▶ Preview</button>
          <button class="btn-apply-agent" onclick="VoiceCmd.applyAgent()">✓ Use Agent</button>
        </div>
      </div>
    </div>
  </div>

  <div class="transcript-box" id="transcriptBox"></div>
  <div class="queue-indicator" id="queueIndicator">📋 <span class="qi-count" id="queueCount">0</span> command(s) queued — Alfred will run them next</div>

  <!-- Engine Status Bar -->
  <div class="engine-bar" id="engineBar"></div>

  <!-- Fleet Status Panel -->
  <div class="fleet-panel" id="fleetPanel">
    <div class="fleet-header" onclick="VoiceCmd.toggleFleet()">
      <h4>🛰️ Agent Fleet <span class="fleet-count">24 Active</span></h4>
      <span class="fleet-arrow" id="fleetArrow">▼</span>
    </div>
    <div class="fleet-grid" id="fleetGrid"></div>
  </div>

  <div class="text-input-bar" id="textInputBar">
    <input type="text" id="textInput" placeholder="Type a command for Alfred…" autocomplete="off">
    <button id="textSendBtn" onclick="VoiceCmd.sendTextCommand()">Send</button>
  </div>
  <div class="hold-hint" id="holdHint"></div>
  <button class="btn-connect" id="btnConnect" onclick="VoiceCmd.connectVoice()">⚡ Initialize Alfred</button>
  <div class="footer-note">Microphone access required · Alfred AI Command Center · 1,220+ tools · 16 AI engines · Fleet orchestration · MCP gateway · Voice-activated operations</div>
</div>

<div class="steering-toast" id="steeringToast"></div>

<button id="helpBtn" class="help-trigger" title="What can Alfred do?">?</button>
<div id="helpPanel" class="help-panel">
  <div class="help-header">
    <h3>💡 What can Alfred do?</h3>
    <span class="help-close" onclick="VoiceCmd.toggleHelp()">&times;</span>
  </div>
  <div class="help-categories">
    <button class="help-cat active" data-cat="all">All</button>
    <button class="help-cat" data-cat="web">Web &amp; Hosting</button>
    <button class="help-cat" data-cat="ecommerce">E-Commerce</button>
    <button class="help-cat" data-cat="seo">SEO</button>
    <button class="help-cat" data-cat="design">Design</button>
    <button class="help-cat" data-cat="devops">DevOps</button>
    <button class="help-cat" data-cat="security">Security</button>
    <button class="help-cat" data-cat="content">Content</button>
    <button class="help-cat" data-cat="steering">Steering</button>
  </div>
  <div class="help-examples" id="helpExamples"></div>
</div>


<script src="/assets/js/voice-engine.js"></script>
<script>
window.VoiceCmd.init({
  authToken: '<?php echo $voiceAuthToken; ?>',
  username: '<?php echo htmlspecialchars($voiceUsername, ENT_QUOTES); ?>',
  isAuth: <?php echo $voiceIsAuth ? 'true' : 'false'; ?>
});
</script>
<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
