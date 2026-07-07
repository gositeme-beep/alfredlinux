<?php
$page_title = 'Alfred Conference Center — AI-Powered Voice Conference Room | GoSiteMe';
$page_description = 'Host AI-powered voice conferences with real-time transcription, auto-generated summaries, and intelligent meeting management by Alfred AI.';
$page_canonical = 'https://root.com/conference-room.php';
$page_og_title = $page_title;
$page_og_description = $page_description;
$page_twitter_description = 'Alfred Conference Center — AI voice conferences with live transcription and smart summaries.';

include __DIR__ . '/includes/auth-gate.inc.php';
include __DIR__ . '/includes/site-header.inc.php';

$is_logged_in = !empty($_SESSION['logged_in']) && !empty($_SESSION['client_id']);
$client_id    = $is_logged_in ? (int)$_SESSION['client_id'] : 0;
$client_name  = $_SESSION['client_name'] ?? '';
$client_email = $_SESSION['client_email'] ?? '';
?>
<style>
/* ========== RESET & VARIABLES ========== */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#0a0a14;--bg2:#0f0f1a;--bg-card:rgba(22,22,42,.65);--bg-card-solid:#16162a;
  --accent:#6c5ce7;--accent-glow:rgba(108,92,231,.3);--accent-light:#a29bfe;
  --green:#00e676;--yellow:#ffd600;--blue:#448aff;--red:#ff5252;--cyan:#18ffff;--orange:#ff9100;
  --text:#e8e8f0;--text2:#9898b0;--text3:#68688a;
  --border:rgba(255,255,255,.07);--glass:rgba(255,255,255,.04);
  --radius:14px;--radius-sm:10px;--radius-lg:18px;
  --font:'Segoe UI',system-ui,-apple-system,sans-serif;
  --mono:'JetBrains Mono','Fira Code',monospace;
}
html{scroll-behavior:smooth}
body{font-family:var(--font);background:var(--bg);color:var(--text);line-height:1.6;min-height:100vh;overflow-x:hidden}
a{color:var(--accent-light);text-decoration:none}a:hover{color:#fff}

::-webkit-scrollbar{width:5px}
::-webkit-scrollbar-track{background:var(--bg2)}
::-webkit-scrollbar-thumb{background:var(--accent);border-radius:3px}

/* ========== GLASS CARD MIXIN ========== */
.glass{background:var(--bg-card);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid var(--border);border-radius:var(--radius-lg)}
.glass:hover{border-color:rgba(108,92,231,.2);box-shadow:0 8px 40px rgba(108,92,231,.06)}

/* ========== NAV ========== */
.conf-nav{position:sticky;top:0;z-index:200;background:rgba(10,10,20,.92);backdrop-filter:blur(16px);border-bottom:1px solid var(--border);padding:0 2rem;display:flex;align-items:center;justify-content:space-between;height:58px}
.conf-nav .logo{display:flex;align-items:center;gap:.6rem;font-weight:700;font-size:1.05rem;color:var(--text)}
.conf-nav .logo i{color:var(--accent);font-size:1.2rem}
.conf-nav .links{display:flex;gap:1.4rem;align-items:center}
.conf-nav .links a{color:var(--text2);font-size:.85rem;transition:color .2s}
.conf-nav .links a:hover{color:#fff}
.conf-nav .links .btn-nav{background:var(--accent);color:#fff;padding:.4rem 1rem;border-radius:var(--radius-sm);font-weight:600;font-size:.82rem}

/* ========== HERO ========== */
.conf-hero{position:relative;padding:3.5rem 2rem 2.5rem;text-align:center;overflow:hidden}
.conf-hero::before{content:'';position:absolute;top:-40%;left:50%;transform:translateX(-50%);width:700px;height:700px;background:radial-gradient(circle,var(--accent-glow) 0%,transparent 70%);pointer-events:none;animation:heroGlow 8s ease-in-out infinite}
@keyframes heroGlow{0%,100%{opacity:.35;transform:translateX(-50%) scale(1)}50%{opacity:.6;transform:translateX(-50%) scale(1.08)}}
.conf-hero h1{font-size:clamp(1.8rem,4.5vw,2.8rem);font-weight:800;letter-spacing:-.02em;position:relative}
.conf-hero h1 .hl{background:linear-gradient(135deg,var(--accent),var(--cyan));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.conf-hero .sub{color:var(--text2);font-size:1rem;margin-top:.6rem;position:relative}

/* ========== ROOM CODE ========== */
.room-code-bar{display:flex;align-items:center;justify-content:center;gap:.8rem;margin-top:1.4rem;position:relative}
.room-code{font-family:var(--mono);font-size:1.3rem;font-weight:700;letter-spacing:.15em;background:var(--bg-card-solid);border:1px solid var(--border);padding:.5rem 1.4rem;border-radius:var(--radius-sm)}
.copy-btn{background:var(--accent);border:none;color:#fff;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:transform .2s,background .2s;font-size:.85rem}
.copy-btn:hover{transform:scale(1.1);background:#7c6cf7}
.copy-toast{position:absolute;top:-30px;background:var(--green);color:#000;padding:.2rem .7rem;border-radius:50px;font-size:.7rem;font-weight:700;opacity:0;transition:opacity .3s}
.copy-toast.show{opacity:1}

/* ========== TIMER ========== */
.meeting-timer{display:inline-flex;align-items:center;gap:.4rem;margin-top:1rem;font-family:var(--mono);font-size:1rem;color:var(--text2);position:relative}
.meeting-timer .rec-dot{width:8px;height:8px;background:var(--red);border-radius:50%;animation:recPulse 1.5s ease-in-out infinite}
@keyframes recPulse{0%,100%{opacity:1}50%{opacity:.3}}

/* ========== LAYOUT ========== */
.conf-layout{max-width:1440px;margin:0 auto;padding:0 1.5rem 3rem;display:grid;grid-template-columns:1fr 380px;gap:1.5rem}
.conf-main{display:flex;flex-direction:column;gap:1.5rem}
.conf-sidebar{display:flex;flex-direction:column;gap:1.5rem}

/* ========== ROOM CREATION ========== */
.create-panel{padding:1.5rem}
.create-panel h2{font-size:1.05rem;font-weight:700;margin-bottom:1rem;display:flex;align-items:center;gap:.5rem}
.create-panel h2 i{color:var(--accent)}
.fg{margin-bottom:.9rem}
.fg label{display:block;font-size:.75rem;font-weight:600;color:var(--text2);margin-bottom:.3rem;text-transform:uppercase;letter-spacing:.04em}
.fg input,.fg select,.fg textarea{width:100%;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius-sm);padding:.65rem .9rem;color:var(--text);font-size:.88rem;font-family:var(--font);outline:none;transition:border-color .2s,box-shadow .2s}
.fg input:focus,.fg select:focus,.fg textarea:focus{border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-glow)}
.fg textarea{resize:vertical;min-height:60px}
.fg select{cursor:pointer;appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%239898b0'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right .6rem center;background-size:1.1rem;padding-right:2.2rem}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:.5rem;padding:.7rem 1.3rem;border:none;border-radius:var(--radius-sm);font-size:.88rem;font-weight:700;cursor:pointer;transition:all .25s;font-family:var(--font)}
.btn-primary{background:linear-gradient(135deg,var(--accent),#7c6cf7);color:#fff;box-shadow:0 4px 20px var(--accent-glow)}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 8px 30px rgba(108,92,231,.4);color:#fff}
.btn-block{width:100%}

/* ========== PARTICIPANT GRID ========== */
.participants-panel{padding:1.5rem}
.participants-panel .panel-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem}
.participants-panel .panel-head h2{font-size:1.05rem;font-weight:700;display:flex;align-items:center;gap:.5rem}
.participants-panel .panel-head h2 i{color:var(--accent)}
.badge{font-size:.68rem;padding:.2rem .55rem;border-radius:50px;font-weight:600;text-transform:uppercase;letter-spacing:.04em}
.badge-live{background:rgba(0,230,118,.12);color:var(--green)}

.participants-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:1rem}
.participant{position:relative;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);padding:1.2rem .8rem;text-align:center;transition:border-color .25s,box-shadow .25s}
.participant:hover{border-color:rgba(108,92,231,.25)}
.participant.speaking{border-color:rgba(0,230,118,.4);box-shadow:0 0 20px rgba(0,230,118,.1)}
.avatar{width:56px;height:56px;border-radius:50%;margin:0 auto .6rem;display:flex;align-items:center;justify-content:center;font-size:1.2rem;font-weight:700;position:relative}
.avatar-ring{position:absolute;inset:-4px;border-radius:50%;border:2px solid transparent}
.speaking .avatar-ring{border-color:var(--green);animation:speakPulse 1.5s ease-in-out infinite}
@keyframes speakPulse{0%,100%{box-shadow:0 0 0 0 rgba(0,230,118,.4)}50%{box-shadow:0 0 0 8px rgba(0,230,118,0)}}
.participant-name{font-size:.82rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.participant-role{font-size:.7rem;color:var(--text3);margin-top:.15rem}
.participant-status{position:absolute;top:.6rem;right:.6rem;width:8px;height:8px;border-radius:50%}
.participant-status.online{background:var(--green)}
.participant-status.muted{background:var(--yellow)}

/* Speaking indicator bars */
.speak-bars{display:flex;gap:2px;justify-content:center;margin-top:.5rem;height:14px;align-items:flex-end}
.speak-bars span{width:3px;border-radius:2px;background:var(--green);animation:speakBar 0.8s ease-in-out infinite}
.speak-bars span:nth-child(1){height:6px;animation-delay:0s}
.speak-bars span:nth-child(2){height:10px;animation-delay:.15s}
.speak-bars span:nth-child(3){height:14px;animation-delay:.3s}
.speak-bars span:nth-child(4){height:8px;animation-delay:.45s}
.speak-bars.silent span{animation:none;height:3px;background:var(--text3)}
@keyframes speakBar{0%,100%{height:3px}50%{height:14px}}

/* ========== CONTROLS BAR ========== */
.controls-bar{padding:1rem 1.5rem;display:flex;align-items:center;justify-content:center;gap:.7rem;flex-wrap:wrap}
.ctrl-btn{width:46px;height:46px;border-radius:50%;border:1px solid var(--border);background:var(--bg);color:var(--text2);display:flex;align-items:center;justify-content:center;font-size:1rem;cursor:pointer;transition:all .2s;position:relative}
.ctrl-btn:hover{background:var(--bg-card-solid);color:#fff;border-color:var(--accent)}
.ctrl-btn.active{background:rgba(108,92,231,.15);color:var(--accent-light);border-color:var(--accent)}
.ctrl-btn.danger{border-color:rgba(255,82,82,.3)}
.ctrl-btn.danger:hover{background:rgba(255,82,82,.15);color:var(--red);border-color:var(--red)}
.ctrl-btn .tooltip{position:absolute;bottom:-28px;left:50%;transform:translateX(-50%);background:var(--bg-card-solid);border:1px solid var(--border);padding:.15rem .5rem;border-radius:6px;font-size:.65rem;white-space:nowrap;opacity:0;pointer-events:none;transition:opacity .2s}
.ctrl-btn:hover .tooltip{opacity:1}
.ctrl-divider{width:1px;height:30px;background:var(--border);margin:0 .3rem}

/* ========== TRANSCRIPT PANEL ========== */
.transcript-panel{padding:1.2rem;display:flex;flex-direction:column;max-height:500px}
.transcript-panel h3{font-size:.95rem;font-weight:700;margin-bottom:.8rem;display:flex;align-items:center;gap:.4rem}
.transcript-panel h3 i{color:var(--cyan);font-size:.85rem}
.transcript-feed{flex:1;overflow-y:auto;display:flex;flex-direction:column;gap:.5rem;padding-right:.3rem}
.transcript-item{display:flex;gap:.6rem;animation:fadeSlide .3s ease-out}
@keyframes fadeSlide{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}
.transcript-avatar{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.6rem;font-weight:700;flex-shrink:0}
.transcript-body{flex:1;min-width:0}
.transcript-name{font-size:.75rem;font-weight:700}
.transcript-text{font-size:.82rem;color:var(--text2);line-height:1.5}
.transcript-time{font-size:.65rem;color:var(--text3);font-family:var(--mono);margin-top:.15rem}

/* ========== AI SUMMARY PANEL ========== */
.summary-panel{padding:1.2rem}
.summary-panel h3{font-size:.95rem;font-weight:700;margin-bottom:.8rem;display:flex;align-items:center;gap:.4rem}
.summary-panel h3 i{color:var(--accent);font-size:.85rem}
.summary-section{margin-bottom:1rem}
.summary-section h4{font-size:.78rem;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.05em;margin-bottom:.4rem;display:flex;align-items:center;gap:.3rem}
.summary-section h4 i{font-size:.7rem}
.summary-text{font-size:.82rem;color:var(--text2);line-height:1.6}
.action-list{list-style:none}
.action-list li{font-size:.82rem;color:var(--text2);padding:.35rem 0;border-bottom:1px solid var(--border);display:flex;align-items:flex-start;gap:.4rem}
.action-list li:last-child{border:none}
.action-list li i{color:var(--green);margin-top:.15rem;flex-shrink:0;font-size:.7rem}
.decision-list li i{color:var(--blue)}
.summary-badge{display:inline-flex;align-items:center;gap:.3rem;background:rgba(108,92,231,.1);color:var(--accent-light);padding:.15rem .5rem;border-radius:50px;font-size:.65rem;font-weight:600;margin-bottom:.6rem}

/* ========== RESPONSIVE ========== */
@media(max-width:1024px){
  .conf-layout{grid-template-columns:1fr}
  .conf-sidebar{order:2}
}
@media(max-width:600px){
  .conf-hero{padding:2rem 1rem 1.5rem}
  .conf-hero h1{font-size:1.6rem}
  .conf-layout{padding:0 .8rem 2rem}
  .participants-grid{grid-template-columns:repeat(auto-fill,minmax(110px,1fr))}
  .avatar{width:44px;height:44px;font-size:1rem}
  .controls-bar{gap:.5rem}
  .ctrl-btn{width:40px;height:40px;font-size:.9rem}
  .conf-nav .links a:not(.btn-nav){display:none}
  .conf-nav{padding:0 1rem}
}

/* ========== FOOTER ========== */
.conf-footer{text-align:center;padding:2rem;border-top:1px solid var(--border);color:var(--text3);font-size:.78rem}
.conf-footer a{color:var(--accent-light)}
</style>

<!-- Navigation -->
<nav class="conf-nav">
  <a href="/" class="logo"><i class="fas fa-headset"></i> Alfred Conference Center</a>
  <div class="links">
    <a href="/alfred.php">Alfred AI</a>
    <a href="/team-chat.php">Team Chat</a>
    <a href="/fleet-dashboard.php">Fleet</a>
    <a href="/voice.php">Voice</a>
    <a href="/dashboard.php" class="btn-nav"><i class="fas fa-th-large"></i> Dashboard</a>
  </div>
</nav>

<!-- Hero -->
<section class="conf-hero">
  <h1><span class="hl">Alfred Conference Center</span></h1>
  <p class="sub">AI-powered voice conferences with real-time transcription &amp; smart summaries</p>
  <div class="room-code-bar">
    <span class="room-code" id="roomCode">ALC-8472-XQ</span>
    <button class="copy-btn" onclick="copyRoomCode()" title="Copy room code"><i class="fas fa-copy"></i></button>
    <span class="copy-toast" id="copyToast">Copied!</span>
  </div>
  <div class="meeting-timer">
    <span class="rec-dot"></span>
    <span id="timerDisplay">00:00:00</span>
  </div>
</section>

<div class="conf-layout">

  <!-- ====== Main Column ====== -->
  <div class="conf-main">

    <!-- Room Creation Panel -->
    <div class="glass create-panel" id="createPanel">
      <h2><i class="fas fa-door-open"></i> Create Conference Room</h2>
      <form id="roomForm" onsubmit="return createRoom(event)">
        <div class="fg">
          <label for="roomTopic">Topic</label>
          <input type="text" id="roomTopic" placeholder="e.g., Q1 Product Sprint Planning" required>
        </div>
        <div class="fg">
          <label for="maxParticipants">Max Participants</label>
          <select id="maxParticipants">
            <option value="4">4 participants</option>
            <option value="8" selected>8 participants</option>
            <option value="12">12 participants</option>
            <option value="20">20 participants</option>
          </select>
        </div>
        <div class="fg">
          <label for="agendaItems">Agenda Items</label>
          <textarea id="agendaItems" placeholder="Enter agenda items, one per line...&#10;1. Review sprint goals&#10;2. Assign tasks&#10;3. Timeline discussion"></textarea>
        </div>
        <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-video"></i> Start Conference</button>
      </form>
    </div>

    <!-- Participants Grid -->
    <div class="glass participants-panel" id="participantsPanel" style="display:none">
      <div class="panel-head">
        <h2><i class="fas fa-users"></i> Participants</h2>
        <span class="badge badge-live"><i class="fas fa-circle" style="font-size:.4rem;vertical-align:middle;margin-right:.2rem"></i> LIVE</span>
      </div>
      <div class="participants-grid" id="participantsGrid">
        <!-- Populated by JS -->
      </div>
    </div>

    <!-- Conference Controls -->
    <div class="glass controls-bar" id="controlsBar" style="display:none">
      <button class="ctrl-btn active" id="btnMic" onclick="toggleControl(this,'fa-microphone','fa-microphone-slash')">
        <i class="fas fa-microphone"></i>
        <span class="tooltip">Mute / Unmute</span>
      </button>
      <button class="ctrl-btn" id="btnCam" onclick="toggleControl(this,'fa-video','fa-video-slash')">
        <i class="fas fa-video"></i>
        <span class="tooltip">Camera</span>
      </button>
      <div class="ctrl-divider"></div>
      <button class="ctrl-btn" id="btnRecord" onclick="toggleControl(this)">
        <i class="fas fa-circle" style="font-size:.7rem"></i>
        <span class="tooltip">Record</span>
      </button>
      <button class="ctrl-btn active" id="btnTranscribe" onclick="toggleControl(this)">
        <i class="fas fa-closed-captioning"></i>
        <span class="tooltip">Transcribe</span>
      </button>
      <button class="ctrl-btn" id="btnPoll" onclick="toggleControl(this)">
        <i class="fas fa-poll"></i>
        <span class="tooltip">Launch Poll</span>
      </button>
      <button class="ctrl-btn" id="btnBreakout" onclick="toggleControl(this)">
        <i class="fas fa-object-ungroup"></i>
        <span class="tooltip">Breakout Rooms</span>
      </button>
      <!-- Share screen placeholder -->
      <button class="ctrl-btn" id="btnScreen" onclick="toggleControl(this)">
        <i class="fas fa-desktop"></i>
        <span class="tooltip">Share Screen</span>
      </button>
      <button class="ctrl-btn" id="btnHand" onclick="toggleControl(this)">
        <i class="fas fa-hand"></i>
        <span class="tooltip">Raise Hand</span>
      </button>
      <div class="ctrl-divider"></div>
      <button class="ctrl-btn danger" id="btnLeave" onclick="leaveRoom()">
        <i class="fas fa-phone-slash"></i>
        <span class="tooltip">Leave</span>
      </button>
    </div>

  </div><!-- /conf-main -->

  <!-- ====== Sidebar ====== -->
  <div class="conf-sidebar">

    <!-- Live Transcript -->
    <div class="glass transcript-panel" id="transcriptPanel" style="display:none">
      <h3><i class="fas fa-closed-captioning"></i> Live Transcript</h3>
      <div class="transcript-feed" id="transcriptFeed">
        <!-- Populated by JS -->
      </div>
    </div>

    <!-- AI Summary -->
    <div class="glass summary-panel" id="summaryPanel" style="display:none">
      <h3><i class="fas fa-brain"></i> AI Summary</h3>
      <div class="summary-badge"><i class="fas fa-sparkles"></i> Auto-generated by Alfred</div>

      <div class="summary-section">
        <h4><i class="fas fa-file-lines"></i> Meeting Summary</h4>
        <p class="summary-text" id="summaryText">Waiting for conversation data...</p>
      </div>

      <div class="summary-section">
        <h4><i class="fas fa-list-check"></i> Action Items</h4>
        <ul class="action-list" id="actionItems">
          <!-- Populated by JS -->
        </ul>
      </div>

      <div class="summary-section">
        <h4><i class="fas fa-gavel"></i> Decisions</h4>
        <ul class="action-list decision-list" id="decisionItems">
          <!-- Populated by JS -->
        </ul>
      </div>
    </div>

  </div><!-- /conf-sidebar -->

</div><!-- /conf-layout -->

<!-- Footer -->
<footer class="conf-footer">
  &copy; <?php echo date('Y'); ?> <a href="/">GoSiteMe</a> &mdash; Alfred Conference Center &middot; <a href="/alfred.php">Alfred AI</a> &middot; <a href="/privacy-policy.php">Privacy</a> &middot; <a href="/terms-of-service.php">Terms</a>
</footer>

<script src="/assets/js/vendor/livekit-client.umd.min.js"></script>
<script>window._confClientName = <?php echo json_encode($client_name ?: "Guest"); ?>;</script>
<script src="/assets/js/conference-room-engine.js"></script>

<script type="application/ld+json">
{"@context":"https://schema.org","@type":"SoftwareApplication","name":"Alfred Conference Center","applicationCategory":"CommunicationApplication","operatingSystem":"Web","url":"https://root.com/conference-room.php","description":"Host AI-powered voice conferences with real-time transcription, auto-generated summaries, and intelligent meeting management by Alfred AI.","featureList":"AI Voice Conferences, Real-Time Transcription, Auto Summaries, Intelligent Meeting Management, Multi-Participant Rooms","offers":{"@type":"Offer","price":"0","priceCurrency":"USD","description":"Included with GoSiteMe hosting plans"},"creator":{"@type":"Organization","name":"GoSiteMe","url":"https://root.com"}}
</script>
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"WebPage","name":"Alfred Conference Center — AI-Powered Voice Conferences","description":"Host AI-powered voice conferences with real-time transcription, auto-generated summaries, and intelligent meeting management.","url":"https://root.com/conference-room.php","isPartOf":{"@type":"WebSite","name":"GoSiteMe","url":"https://root.com"}}
</script>
<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
