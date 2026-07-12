<?php
$page_title = 'Alfred Team Chat — AI Agent War Room | GoSiteMe';
$page_description = 'Train and manage a fleet of AI agents in a team chat war room. Direct agents individually or as a group, like managing a call center team.';
$page_canonical = 'https://gositeme.com/team-chat.php';
$page_og_title = $page_title;
$page_og_description = $page_description;
$page_twitter_description = 'Alfred Team Chat — manage and train a fleet of AI agents in a single war room.';

include __DIR__ . '/includes/auth-gate.inc.php';
include __DIR__ . '/includes/site-header.inc.php';

$is_logged_in = !empty($_SESSION['logged_in']) && !empty($_SESSION['client_id']);
$client_id    = $is_logged_in ? (int)$_SESSION['client_id'] : 0;
$client_name  = $_SESSION['client_name'] ?? 'Boss';
$csrf_token   = $_SESSION['alfred_csrf'] ?? '';
?>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#0a0a14;--bg2:#0f0f1a;--bg-card:rgba(22,22,42,.65);--bg-card-solid:#16162a;--bg-input:#0d0d18;
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

/* ═══ NAV BAR ═══ */
.tc-nav{position:sticky;top:0;z-index:200;background:rgba(10,10,20,.94);backdrop-filter:blur(16px);border-bottom:1px solid var(--border);padding:0 1.5rem;display:flex;align-items:center;justify-content:space-between;height:56px}
.tc-nav .logo{display:flex;align-items:center;gap:.6rem;font-weight:700;font-size:1rem;color:var(--text)}
.tc-nav .logo i{color:var(--accent);font-size:1.1rem}
.tc-nav .links{display:flex;gap:1.2rem;align-items:center}
.tc-nav .links a{color:var(--text2);font-size:.82rem;transition:color .2s}
.tc-nav .links a:hover{color:#fff}
.tc-nav .links .btn-nav{background:var(--accent);color:#fff;padding:.35rem .9rem;border-radius:var(--radius-sm);font-weight:600;font-size:.8rem}

/* ═══ LAYOUT: 3-Column ═══ */
.tc-layout{display:grid;grid-template-columns:260px 1fr 280px;height:calc(100vh - 56px);overflow:hidden}

/* ═══ LEFT SIDEBAR — Rooms ═══ */
.tc-sidebar{background:var(--bg2);border-right:1px solid var(--border);display:flex;flex-direction:column;overflow:hidden}
.tc-sidebar .sidebar-header{padding:1rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.tc-sidebar .sidebar-header h3{font-size:.85rem;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.06em}
.btn-new-room{background:var(--accent);border:none;color:#fff;width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.85rem;transition:transform .2s,background .2s}
.btn-new-room:hover{transform:scale(1.1);background:#7c6cf7}
.room-list{flex:1;overflow-y:auto;padding:.5rem}
.room-item{padding:.7rem .9rem;border-radius:var(--radius-sm);cursor:pointer;transition:background .15s;margin-bottom:.3rem;border:1px solid transparent}
.room-item:hover{background:rgba(108,92,231,.08);border-color:rgba(108,92,231,.15)}
.room-item.active{background:rgba(108,92,231,.12);border-color:rgba(108,92,231,.25)}
.room-item .room-name{font-size:.88rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.room-item .room-meta{font-size:.72rem;color:var(--text3);margin-top:.2rem;display:flex;gap:.6rem}
.room-item .room-meta .dot{width:6px;height:6px;border-radius:50%;display:inline-block;margin-right:.2rem}
.room-item .room-meta .dot.active{background:var(--green)}
.room-item .room-meta .dot.closed{background:var(--text3)}

/* ═══ CENTER — Chat Area ═══ */
.tc-chat{display:flex;flex-direction:column;overflow:hidden;background:var(--bg)}
.tc-chat-header{padding:.8rem 1.2rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:rgba(10,10,20,.6);backdrop-filter:blur(10px);min-height:54px}
.tc-chat-header .room-info{display:flex;align-items:center;gap:.8rem}
.tc-chat-header .room-title{font-size:1rem;font-weight:700}
.tc-chat-header .room-badge{font-size:.65rem;padding:.15rem .5rem;border-radius:50px;font-weight:700;text-transform:uppercase;letter-spacing:.04em}
.badge-active{background:rgba(0,230,118,.12);color:var(--green)}
.badge-closed{background:rgba(152,152,176,.12);color:var(--text3)}
.tc-chat-header .header-actions{display:flex;gap:.5rem}
.header-btn{background:var(--bg-card-solid);border:1px solid var(--border);color:var(--text2);padding:.35rem .7rem;border-radius:var(--radius-sm);cursor:pointer;font-size:.78rem;transition:all .2s;display:flex;align-items:center;gap:.3rem}
.header-btn:hover{border-color:var(--accent);color:#fff}

.tc-messages{flex:1;overflow-y:auto;padding:1rem 1.5rem;display:flex;flex-direction:column;gap:.6rem}

/* Message bubbles */
.msg{display:flex;gap:.7rem;max-width:85%;animation:msgIn .3s ease-out}
@keyframes msgIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
.msg.user-msg{align-self:flex-end;flex-direction:row-reverse}
.msg .msg-avatar{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0;border:2px solid var(--border);background:var(--bg-card-solid)}
.msg .msg-body{background:var(--bg-card-solid);border:1px solid var(--border);border-radius:var(--radius);padding:.6rem .9rem;position:relative}
.msg.user-msg .msg-body{background:rgba(108,92,231,.15);border-color:rgba(108,92,231,.25)}
.msg .msg-header{display:flex;align-items:center;gap:.5rem;margin-bottom:.25rem}
.msg .msg-name{font-size:.78rem;font-weight:700}
.msg .msg-role{font-size:.65rem;color:var(--text3);background:var(--glass);padding:.1rem .4rem;border-radius:50px}
.msg .msg-time{font-size:.62rem;color:var(--text3);margin-left:auto}
.msg .msg-text{font-size:.88rem;line-height:1.5;color:var(--text)}
.msg.system-msg{align-self:center;max-width:70%}
.msg.system-msg .msg-body{background:rgba(152,152,176,.06);border-color:rgba(152,152,176,.1);text-align:center}
.msg.system-msg .msg-text{font-size:.78rem;color:var(--text3);font-style:italic}

/* Typing indicators */
.agent-typing{display:flex;align-items:center;gap:.5rem;padding:.4rem .8rem;font-size:.78rem;color:var(--text3);animation:fadeIn .3s}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
.typing-dots{display:flex;gap:3px}
.typing-dots span{width:5px;height:5px;border-radius:50%;background:var(--text3);animation:typingBounce 1.4s ease-in-out infinite}
.typing-dots span:nth-child(2){animation-delay:.2s}
.typing-dots span:nth-child(3){animation-delay:.4s}
@keyframes typingBounce{0%,60%,100%{transform:translateY(0)}30%{transform:translateY(-6px)}}

/* Input area */
.tc-input-area{padding:.8rem 1.2rem;border-top:1px solid var(--border);background:rgba(10,10,20,.6);backdrop-filter:blur(10px)}
.tc-input-row{display:flex;gap:.5rem;align-items:flex-end}
.tc-input-row textarea{flex:1;background:var(--bg-input);border:1px solid var(--border);border-radius:var(--radius-sm);padding:.65rem .9rem;color:var(--text);font-size:.9rem;font-family:var(--font);resize:none;min-height:42px;max-height:120px;outline:none;transition:border-color .2s}
.tc-input-row textarea:focus{border-color:var(--accent)}
.tc-input-row textarea::placeholder{color:var(--text3)}
.tc-send-btn{background:var(--accent);border:none;color:#fff;width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:1rem;transition:transform .2s,background .2s;flex-shrink:0}
.tc-send-btn:hover{transform:scale(1.08);background:#7c6cf7}
.tc-send-btn:disabled{opacity:.4;cursor:not-allowed;transform:none}
.tc-input-controls{display:flex;gap:.5rem;margin-top:.4rem;flex-wrap:wrap}
.input-chip{background:var(--glass);border:1px solid var(--border);color:var(--text2);padding:.2rem .6rem;border-radius:50px;font-size:.72rem;cursor:pointer;transition:all .15s;display:flex;align-items:center;gap:.3rem}
.input-chip:hover{border-color:var(--accent);color:#fff;background:rgba(108,92,231,.1)}
.input-chip.active{border-color:var(--accent);color:var(--accent-light);background:rgba(108,92,231,.15)}

/* ═══ RIGHT SIDEBAR — Agent Roster ═══ */
.tc-roster{background:var(--bg2);border-left:1px solid var(--border);display:flex;flex-direction:column;overflow:hidden}
.tc-roster .roster-header{padding:1rem;border-bottom:1px solid var(--border)}
.tc-roster .roster-header h3{font-size:.85rem;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.06em;display:flex;align-items:center;gap:.5rem}
.tc-roster .roster-header .agent-count{font-family:var(--mono);color:var(--green);font-size:.9rem}
.agent-roster-list{flex:1;overflow-y:auto;padding:.5rem}
.agent-card{display:flex;align-items:center;gap:.6rem;padding:.6rem .8rem;border-radius:var(--radius-sm);margin-bottom:.3rem;cursor:pointer;transition:all .15s;border:1px solid transparent;position:relative}
.agent-card:hover{background:rgba(108,92,231,.08);border-color:rgba(108,92,231,.12)}
.agent-card.selected{background:rgba(108,92,231,.12);border-color:rgba(108,92,231,.25)}
.agent-card .agent-avatar{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.9rem;border:2px solid;flex-shrink:0}
.agent-card .agent-info{flex:1;min-width:0}
.agent-card .agent-name{font-size:.82rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.agent-card .agent-role{font-size:.68rem;color:var(--text3);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.agent-card .agent-status{width:7px;height:7px;border-radius:50%;background:var(--green);flex-shrink:0}
.agent-card .agent-status.thinking{background:var(--yellow);animation:pulseDot 1.5s ease-in-out infinite}
@keyframes pulseDot{0%,100%{box-shadow:0 0 0 0 rgba(255,214,0,.5)}50%{box-shadow:0 0 0 4px rgba(255,214,0,0)}}
.agent-card .remove-btn{position:absolute;right:.5rem;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--red);font-size:.7rem;cursor:pointer;opacity:0;transition:opacity .15s}
.agent-card:hover .remove-btn{opacity:.6}
.agent-card .remove-btn:hover{opacity:1}

/* Add agent button */
.add-agent-section{padding:.8rem;border-top:1px solid var(--border)}
.add-agent-btn{width:100%;background:var(--glass);border:1px dashed var(--border);color:var(--text2);padding:.6rem;border-radius:var(--radius-sm);cursor:pointer;font-size:.8rem;display:flex;align-items:center;justify-content:center;gap:.4rem;transition:all .2s}
.add-agent-btn:hover{border-color:var(--accent);color:var(--accent-light);background:rgba(108,92,231,.08)}

/* ═══ MODALS ═══ */
.modal-overlay{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);backdrop-filter:blur(4px);z-index:1000;display:none;align-items:center;justify-content:center}
.modal-overlay.show{display:flex}
.modal{background:var(--bg-card-solid);border:1px solid var(--border);border-radius:var(--radius-lg);padding:1.5rem;width:90%;max-width:520px;max-height:80vh;overflow-y:auto;animation:modalIn .2s ease-out}
@keyframes modalIn{from{opacity:0;transform:scale(.95)}to{opacity:1;transform:scale(1)}}
.modal h2{font-size:1.1rem;font-weight:700;margin-bottom:1rem;display:flex;align-items:center;gap:.5rem}
.modal h2 i{color:var(--accent)}
.modal .fg{margin-bottom:.9rem}
.modal .fg label{display:block;font-size:.75rem;font-weight:600;color:var(--text2);margin-bottom:.3rem;text-transform:uppercase;letter-spacing:.04em}
.modal .fg input,.modal .fg select,.modal .fg textarea{width:100%;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius-sm);padding:.6rem .8rem;color:var(--text);font-size:.88rem;font-family:var(--font);outline:none;transition:border-color .2s}
.modal .fg input:focus,.modal .fg select:focus,.modal .fg textarea:focus{border-color:var(--accent)}
.modal .fg textarea{resize:vertical;min-height:60px}
.modal .fg select{cursor:pointer;appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%239898b0'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right .6rem center;background-size:1.1rem;padding-right:2.2rem}
.modal-actions{display:flex;gap:.6rem;justify-content:flex-end;margin-top:1.2rem}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:.5rem;padding:.6rem 1.1rem;border:none;border-radius:var(--radius-sm);font-size:.85rem;font-weight:700;cursor:pointer;transition:all .2s;font-family:var(--font)}
.btn-primary{background:linear-gradient(135deg,var(--accent),#7c6cf7);color:#fff;box-shadow:0 4px 20px var(--accent-glow)}
.btn-primary:hover{transform:translateY(-1px);box-shadow:0 6px 25px rgba(108,92,231,.4)}
.btn-ghost{background:var(--glass);color:var(--text2);border:1px solid var(--border)}
.btn-ghost:hover{border-color:var(--accent);color:#fff}

/* Gather slider */
.gather-slider{display:flex;align-items:center;gap:1rem}
.gather-slider input[type=range]{flex:1;accent-color:var(--accent);height:6px}
.gather-slider .count-display{font-family:var(--mono);font-size:1.5rem;font-weight:800;color:var(--accent-light);min-width:2ch;text-align:center}

/* Agent picker grid */
.agent-picker-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:.5rem;max-height:300px;overflow-y:auto;padding:.3rem}
.agent-pick{display:flex;align-items:center;gap:.4rem;padding:.5rem;border-radius:var(--radius-sm);cursor:pointer;border:1px solid var(--border);transition:all .15s;font-size:.78rem}
.agent-pick:hover{border-color:var(--accent)}
.agent-pick.picked{border-color:var(--accent);background:rgba(108,92,231,.12)}
.agent-pick .pick-avatar{font-size:1rem}
.agent-pick .pick-name{font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

/* ═══ ONBOARDING / EMPTY STATE ═══ */
.tc-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;text-align:center;gap:1.5rem;padding:2rem}
.tc-empty .big-icon{font-size:4rem;animation:floatIcon 3s ease-in-out infinite}
@keyframes floatIcon{0%,100%{transform:translateY(0)}50%{transform:translateY(-12px)}}
.tc-empty h2{font-size:1.6rem;font-weight:800}
.tc-empty h2 .hl{background:linear-gradient(135deg,var(--accent),var(--cyan));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.tc-empty p{color:var(--text2);max-width:400px;font-size:.95rem}
.tc-empty .quick-actions{display:flex;gap:.8rem;flex-wrap:wrap;justify-content:center}
.quick-action-btn{background:var(--bg-card-solid);border:1px solid var(--border);color:var(--text);padding:.8rem 1.2rem;border-radius:var(--radius);cursor:pointer;transition:all .2s;text-align:center;font-size:.85rem;min-width:140px}
.quick-action-btn:hover{border-color:var(--accent);transform:translateY(-2px);box-shadow:0 6px 20px rgba(108,92,231,.15)}
.quick-action-btn .qa-icon{font-size:1.5rem;display:block;margin-bottom:.3rem}
.quick-action-btn .qa-label{font-weight:700}
.quick-action-btn .qa-desc{font-size:.72rem;color:var(--text3);margin-top:.2rem}

/* ═══ RESPONSIVE ═══ */
@media(max-width:1024px){.tc-layout{grid-template-columns:1fr;}.tc-sidebar,.tc-roster{display:none}.tc-layout.show-sidebar .tc-sidebar{display:flex;position:fixed;left:0;top:56px;bottom:0;width:280px;z-index:300}.tc-layout.show-roster .tc-roster{display:flex;position:fixed;right:0;top:56px;bottom:0;width:280px;z-index:300}}
@media(max-width:600px){.tc-chat-header{flex-direction:column;gap:.4rem;padding:.5rem}.tc-input-controls{display:none}}

/* ═══ @MENTION POPUP ═══ */
.mention-popup{position:absolute;bottom:100%;left:0;right:0;background:var(--bg-card-solid);border:1px solid var(--border);border-radius:var(--radius-sm);max-height:200px;overflow-y:auto;display:none;z-index:50;box-shadow:0 -4px 20px rgba(0,0,0,.3)}
.mention-popup.show{display:block}
.mention-item{display:flex;align-items:center;gap:.5rem;padding:.5rem .8rem;cursor:pointer;transition:background .1s;font-size:.85rem}
.mention-item:hover,.mention-item.active{background:rgba(108,92,231,.15)}
.mention-item .m-avatar{font-size:1rem}
.mention-item .m-name{font-weight:600}
.mention-item .m-role{color:var(--text3);font-size:.72rem}

/* ═══ v2.0 — VERSION BADGE ═══ */
.tc-version-badge{background:linear-gradient(135deg,var(--accent),#7c6cf7);color:#fff;font-size:.55rem;padding:.15rem .45rem;border-radius:50px;font-weight:800;letter-spacing:.04em;vertical-align:middle;margin-left:.4rem}

/* ═══ v2.0 — WS STATUS BADGE ═══ */
.ws-badge{font-size:.65rem;padding:.2rem .5rem;border-radius:50px;font-weight:700;letter-spacing:.03em}
.ws-connected{background:rgba(0,230,118,.12);color:var(--green)}
.ws-disconnected{background:rgba(255,82,82,.12);color:var(--red)}

/* ═══ v2.0 — UNREAD BADGE ═══ */
.unread-badge{background:var(--accent);color:#fff;font-size:.6rem;padding:.1rem .35rem;border-radius:50px;font-weight:800;margin-left:.4rem;vertical-align:middle}

/* ═══ v2.0 — TOAST NOTIFICATIONS ═══ */
.tc-toast{position:fixed;bottom:1.5rem;right:1.5rem;padding:.7rem 1.2rem;border-radius:var(--radius-sm);font-size:.85rem;font-weight:600;z-index:9999;transform:translateY(20px);opacity:0;transition:all .3s;pointer-events:none;max-width:320px}
.tc-toast.show{transform:translateY(0);opacity:1}
.tc-toast-info{background:var(--bg-card-solid);border:1px solid var(--border);color:var(--text)}
.tc-toast-success{background:rgba(0,230,118,.12);border:1px solid rgba(0,230,118,.25);color:var(--green)}
.tc-toast-error{background:rgba(255,82,82,.12);border:1px solid rgba(255,82,82,.25);color:var(--red)}

/* ═══ v2.0 — SEARCH PANEL ═══ */
.tc-search-panel{display:flex;gap:.5rem;padding:.5rem 1rem;border-bottom:1px solid var(--border);background:rgba(10,10,20,.6);backdrop-filter:blur(10px)}
.search-input{flex:1;background:var(--bg-input);border:1px solid var(--border);border-radius:var(--radius-sm);padding:.5rem .8rem;color:var(--text);font-size:.85rem;font-family:var(--font);outline:none}
.search-input:focus{border-color:var(--accent)}
.tc-search-results{max-height:200px;overflow-y:auto}
.search-result-item{padding:.5rem 1rem;cursor:pointer;border-bottom:1px solid var(--border);transition:background .1s}
.search-result-item:hover{background:rgba(108,92,231,.08)}
.sr-name{font-size:.75rem;font-weight:700;color:var(--accent-light)}
.sr-text{font-size:.82rem;color:var(--text2);margin-top:.1rem}

/* ═══ v2.0 — PINNED MESSAGES ═══ */
.pinned-bar{display:flex;align-items:center;gap:.5rem;padding:.4rem 1rem;background:rgba(255,214,0,.06);border-bottom:1px solid rgba(255,214,0,.15);font-size:.78rem;color:var(--yellow)}
.pinned-bar i{font-size:.7rem}
.pin-toggle{background:none;border:none;color:var(--accent-light);cursor:pointer;font-size:.75rem;text-decoration:underline}
.tc-pinned-panel{padding:.8rem 1rem;background:rgba(255,214,0,.04);border-bottom:1px solid rgba(255,214,0,.1);max-height:200px;overflow-y:auto}
.pinned-item{padding:.4rem .6rem;border-radius:var(--radius-sm);cursor:pointer;font-size:.82rem;color:var(--text2);margin-bottom:.3rem;transition:background .1s}
.pinned-item:hover{background:rgba(108,92,231,.08)}
.msg-pinned{border-left:2px solid var(--yellow) !important}
.msg-highlight{animation:highlightPulse .5s ease 2}
@keyframes highlightPulse{0%,100%{background:transparent}50%{background:rgba(108,92,231,.12)}}

/* ═══ v2.0 — MESSAGE ACTIONS ═══ */
.msg-actions{margin-left:auto;display:flex;gap:.2rem}
.msg-action-btn{background:none;border:none;color:var(--text3);cursor:pointer;font-size:.65rem;padding:.15rem .3rem;border-radius:3px;opacity:0;transition:all .15s}
.msg:hover .msg-action-btn{opacity:.5}
.msg-action-btn:hover{opacity:1 !important;color:var(--accent-light);background:rgba(108,92,231,.1)}

/* ═══ PERFORMANCE STATS ═══ */
.perf-grid{display:grid;grid-template-columns:1fr 1fr;gap:.8rem;margin-top:.8rem}
.perf-agent{background:var(--bg);border:1px solid var(--border);border-radius:var(--radius-sm);padding:.7rem}
.perf-agent .pa-header{display:flex;align-items:center;gap:.4rem;margin-bottom:.4rem}
.perf-agent .pa-avatar{font-size:1rem}
.perf-agent .pa-name{font-weight:700;font-size:.82rem}
.perf-agent .pa-stats{display:flex;flex-direction:column;gap:.2rem}
.perf-agent .pa-stat{display:flex;justify-content:space-between;font-size:.75rem}
.perf-agent .pa-stat .label{color:var(--text3)}
.perf-agent .pa-stat .value{font-weight:600;font-family:var(--mono)}
.perf-bar{height:4px;background:var(--border);border-radius:2px;margin-top:.3rem;overflow:hidden}
.perf-bar .fill{height:100%;border-radius:2px;transition:width .5s}

/* ═══ ROLE-PLAY BANNER ═══ */
.roleplay-banner{background:linear-gradient(135deg,rgba(108,92,231,.12),rgba(224,64,251,.12));border:1px solid rgba(108,92,231,.25);border-radius:var(--radius-sm);padding:.6rem 1rem;margin:.5rem 1.5rem;display:flex;align-items:center;gap:.6rem;font-size:.82rem;animation:fadeIn .3s}
.roleplay-banner i{color:var(--accent-light);font-size:1rem}
.roleplay-banner .rp-info{flex:1}
.roleplay-banner .rp-title{font-weight:700;color:var(--accent-light)}
.roleplay-banner .rp-desc{color:var(--text2);font-size:.75rem}
.roleplay-banner .rp-end{background:var(--red);border:none;color:#fff;padding:.3rem .7rem;border-radius:var(--radius-sm);cursor:pointer;font-size:.75rem;font-weight:600}
</style>

<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">

<!-- ═══ NAV BAR ═══ -->
<nav class="tc-nav">
    <div class="logo"><i class="fas fa-users-cog"></i> Alfred Team Chat <span class="tc-version-badge">v2.0</span></div>
    <div class="links">
        <span class="ws-badge ws-disconnected" id="wsBadge" title="WebSocket status">OFFLINE</span>
        <a href="/fleet-dashboard.php"><i class="fas fa-rocket"></i> Fleet Command</a>
        <a href="/agent-templates.php"><i class="fas fa-puzzle-piece"></i> Templates</a>
        <a href="/conference-room.php"><i class="fas fa-headset"></i> Conference</a>
        <a href="/dashboard.php" class="btn-nav"><i class="fas fa-th-large"></i> Dashboard</a>
    </div>
</nav>

<!-- ═══ 3-Column Layout ═══ -->
<div class="tc-layout" id="tcLayout">

    <!-- LEFT: Room List -->
    <div class="tc-sidebar" id="tcSidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-comments"></i> Rooms</h3>
            <button class="btn-new-room" onclick="window.TeamChat.showCreateModal()" title="New Room"><i class="fas fa-plus"></i></button>
        </div>
        <div class="room-list" id="roomList">
            <!-- Populated by JS -->
        </div>
    </div>

    <!-- CENTER: Chat -->
    <div class="tc-chat" id="tcChat">
        <!-- Empty state (shown when no room selected) -->
        <div class="tc-empty" id="emptyState">
            <div class="big-icon">🎩</div>
            <h2>Welcome to the <span class="hl">War Room</span></h2>
            <p>Assemble a team of AI agents and manage them like a call center. Train, direct, and orchestrate — all in one chat.</p>
            <div class="quick-actions">
                <button class="quick-action-btn" onclick="window.TeamChat.quickGather(5,'call_center')">
                    <span class="qa-icon">📞</span>
                    <span class="qa-label">Call Center Team</span>
                    <span class="qa-desc">5 agents for phone support</span>
                </button>
                <button class="quick-action-btn" onclick="window.TeamChat.quickGather(5,'sales')">
                    <span class="qa-icon">💰</span>
                    <span class="qa-label">Sales Squad</span>
                    <span class="qa-desc">5 agents for lead conversion</span>
                </button>
                <button class="quick-action-btn" onclick="window.TeamChat.quickGather(5,'support')">
                    <span class="qa-icon">🛟</span>
                    <span class="qa-label">Support Team</span>
                    <span class="qa-desc">5 agents for helpdesk</span>
                </button>
                <button class="quick-action-btn" onclick="window.TeamChat.quickGather(10,'general')">
                    <span class="qa-icon">⚡</span>
                    <span class="qa-label">Full Squad</span>
                    <span class="qa-desc">10 agents, all roles</span>
                </button>
                <button class="quick-action-btn" onclick="window.TeamChat.showCreateModal()">
                    <span class="qa-icon">🎯</span>
                    <span class="qa-label">Custom Team</span>
                    <span class="qa-desc">Pick your agents</span>
                </button>
            </div>
        </div>

        <!-- Chat header (hidden until room selected) -->
        <div class="tc-chat-header" id="chatHeader" style="display:none">
            <div class="room-info">
                <button class="header-btn" onclick="window.TeamChat.toggleSidebar()" title="Rooms" style="display:none" id="btnShowSidebar"><i class="fas fa-bars"></i></button>
                <span class="room-title" id="roomTitle">—</span>
                <span class="room-badge badge-active" id="roomBadge">LIVE</span>
            </div>
            <div class="header-actions">
                <button class="header-btn" onclick="window.TeamChat.showGatherModal()" title="Gather more agents"><i class="fas fa-user-plus"></i> Gather</button>
                <button class="header-btn" onclick="window.TeamChat.showRoleplayModal()" title="Role-play scenario"><i class="fas fa-theater-masks"></i> Role-Play</button>
                <button class="header-btn" onclick="window.TeamChat.showNegotiateModal()" title="Agent-to-agent negotiation"><i class="fas fa-handshake"></i> Negotiate</button>
                <button class="header-btn" onclick="window.TeamChat.exportTranscript()" title="Export transcript"><i class="fas fa-download"></i> Export</button>
                <button class="header-btn" onclick="window.TeamChat.exportCSV()" title="Export CSV"><i class="fas fa-file-csv"></i> CSV</button>
                <button class="header-btn" onclick="window.TeamChat.toggleSearch()" title="Search messages"><i class="fas fa-search"></i> Search</button>
                <button class="header-btn" onclick="window.TeamChat.showAddAgentModal()" title="Add agent"><i class="fas fa-plus"></i></button>
                <button class="header-btn" onclick="window.TeamChat.toggleRoster()" title="Agent roster" id="btnShowRoster"><i class="fas fa-users"></i></button>
                <button class="header-btn" onclick="window.TeamChat.closeRoom()" title="Close room"><i class="fas fa-door-open"></i></button>
            </div>
        </div>

        <!-- Search panel (v2.0) -->
        <div class="tc-search-panel" id="searchPanel" style="display:none">
            <input type="text" id="searchInput" placeholder="Search messages..." oninput="window.TeamChat.performSearch(this.value)" class="search-input">
            <button class="header-btn" onclick="window.TeamChat.toggleSearch()" style="flex-shrink:0"><i class="fas fa-times"></i></button>
        </div>
        <div class="tc-search-results" id="searchResults"></div>

        <!-- Pinned messages panel (v2.0) -->
        <div class="tc-pinned-panel" id="pinnedPanel" style="display:none"></div>

        <!-- Messages -->
        <div class="tc-messages" id="messagesContainer" style="display:none"></div>

        <!-- Typing indicator -->
        <div class="agent-typing" id="typingIndicator" style="display:none">
            <span id="typingText">Agents are thinking</span>
            <div class="typing-dots"><span></span><span></span><span></span></div>
        </div>

        <!-- Input -->
        <div class="tc-input-area" id="inputArea" style="display:none">
            <div class="tc-input-row" style="position:relative">
                <textarea id="messageInput" placeholder="Message your team... (type @ to mention an agent)" rows="1" onkeydown="window.TeamChat.handleInputKey(event)" oninput="window.TeamChat.handleInputChange(event)"></textarea>
                <button class="tc-send-btn" id="sendBtn" onclick="window.TeamChat.sendMessage()"><i class="fas fa-paper-plane"></i></button>
                <!-- @mention autocomplete dropdown -->
                <div class="mention-popup" id="mentionPopup"></div>
            </div>
            <div class="tc-input-controls" id="inputControls">
                <button class="input-chip" onclick="window.TeamChat.toggleBroadcast()" id="broadcastChip" title="Message all agents">
                    <i class="fas fa-bullhorn"></i> All Agents
                </button>
                <button class="input-chip" onclick="window.TeamChat.setMode('train')" id="trainChip" title="Training mode">
                    <i class="fas fa-graduation-cap"></i> Train
                </button>
                <button class="input-chip" onclick="window.TeamChat.setMode('directive')" id="directiveChip" title="Issue directive">
                    <i class="fas fa-gavel"></i> Directive
                </button>
                <button class="input-chip" onclick="window.TeamChat.setMode('roleplay')" id="roleplayChip" title="Role-play mode">
                    <i class="fas fa-theater-masks"></i> Role-Play
                </button>
                <!-- Agent-specific target chips populated dynamically -->
            </div>
        </div>
    </div>

    <!-- RIGHT: Agent Roster -->
    <div class="tc-roster" id="tcRoster">
        <div class="roster-header">
            <h3><i class="fas fa-id-badge"></i> Team Roster <span class="agent-count" id="rosterCount">0</span></h3>
        </div>
        <div class="agent-roster-list" id="rosterList">
            <!-- Populated by JS -->
        </div>
        <div class="add-agent-section">
            <button class="add-agent-btn" onclick="window.TeamChat.showAddAgentModal()"><i class="fas fa-user-plus"></i> Add Agent</button>
        </div>
        <!-- Agent Performance Summary -->
        <div class="add-agent-section" style="border-top:none;padding-top:0">
            <button class="add-agent-btn" onclick="window.TeamChat.showPerformancePanel()" id="perfBtn" style="display:none"><i class="fas fa-chart-bar"></i> Performance</button>
        </div>
    </div>
</div>

<!-- ═══ CREATE ROOM MODAL ═══ -->
<div class="modal-overlay" id="createModal">
    <div class="modal">
        <h2><i class="fas fa-users-cog"></i> Assemble Your Team</h2>
        <div class="fg">
            <label>Room Name</label>
            <input type="text" id="modalRoomName" placeholder="e.g. Morning Support Shift" maxlength="100">
        </div>
        <div class="fg">
            <label>Purpose</label>
            <select id="modalPurpose">
                <option value="general">General — All-purpose team</option>
                <option value="call_center">Call Center — Phone support team</option>
                <option value="sales">Sales — Lead conversion squad</option>
                <option value="support">Support — Helpdesk team</option>
                <option value="training">Training — Agent coaching session</option>
                <option value="analytics">Analytics — Data & metrics team</option>
                <option value="technical">Technical — Engineering team</option>
            </select>
        </div>
        <div class="fg">
            <label>How many agents?</label>
            <div class="gather-slider">
                <input type="range" id="modalAgentCount" min="2" max="21" value="5" oninput="document.getElementById('countDisplay').textContent=this.value">
                <span class="count-display" id="countDisplay">5</span>
            </div>
        </div>
        <div class="modal-actions">
            <button class="btn btn-ghost" onclick="window.TeamChat.hideModal('createModal')">Cancel</button>
            <button class="btn btn-primary" onclick="window.TeamChat.createRoom()"><i class="fas fa-rocket"></i> Launch Team</button>
        </div>
    </div>
</div>

<!-- ═══ ADD AGENT MODAL ═══ -->
<div class="modal-overlay" id="addAgentModal">
    <div class="modal">
        <h2><i class="fas fa-user-plus"></i> Add Agent to Team</h2>
        <div class="agent-picker-grid" id="agentPickerGrid">
            <!-- Populated by JS from AGENT_PERSONAS -->
        </div>
        <div class="modal-actions">
            <button class="btn btn-ghost" onclick="window.TeamChat.hideModal('addAgentModal')">Cancel</button>
            <button class="btn btn-primary" onclick="window.TeamChat.addPickedAgents()"><i class="fas fa-plus"></i> Add Selected</button>
        </div>
    </div>
</div>

<!-- ═══ GATHER MODAL ═══ -->
<div class="modal-overlay" id="gatherModal">
    <div class="modal">
        <h2><i class="fas fa-users"></i> Gather More Agents</h2>
        <div class="fg">
            <label>How many total agents?</label>
            <div class="gather-slider">
                <input type="range" id="gatherCount" min="2" max="21" value="10" oninput="document.getElementById('gatherCountDisplay').textContent=this.value">
                <span class="count-display" id="gatherCountDisplay">10</span>
            </div>
        </div>
        <div class="fg">
            <label>Team focus</label>
            <select id="gatherPurpose">
                <option value="general">General</option>
                <option value="call_center">Call Center</option>
                <option value="sales">Sales</option>
                <option value="support">Support</option>
                <option value="training">Training</option>
            </select>
        </div>
        <div class="modal-actions">
            <button class="btn btn-ghost" onclick="window.TeamChat.hideModal('gatherModal')">Cancel</button>
            <button class="btn btn-primary" onclick="window.TeamChat.gatherAgents()"><i class="fas fa-users"></i> Gather Team</button>
        </div>
    </div>
</div>

<!-- ═══ ROLE-PLAY MODAL ═══ -->
<div class="modal-overlay" id="roleplayModal">
    <div class="modal">
        <h2><i class="fas fa-theater-masks"></i> Launch Role-Play Scenario</h2>
        <div class="fg">
            <label>Scenario</label>
            <select id="rpScenario">
                <option value="angry_billing">😡 Angry customer — billing dispute</option>
                <option value="confused_newbie">😕 Confused new customer — needs onboarding</option>
                <option value="tech_emergency">🔥 Technical emergency — site down</option>
                <option value="cancellation">🚪 Customer wants to cancel</option>
                <option value="upsell_opportunity">💰 Customer asks about upgrading</option>
                <option value="language_barrier">🌍 Non-native speaker — patience needed</option>
                <option value="vip_complaint">👑 VIP client — high expectations</option>
                <option value="fraud_attempt">🚨 Suspicious activity — possible fraud</option>
                <option value="multi_issue">📋 Customer with 3 different problems</option>
                <option value="custom">✏️ Custom scenario...</option>
            </select>
        </div>
        <div class="fg" id="rpCustomWrap" style="display:none">
            <label>Describe the scenario</label>
            <textarea id="rpCustomText" placeholder="e.g. A restaurant owner calls asking about setting up a reservation bot for their busy Friday nights..." rows="3"></textarea>
        </div>
        <div class="fg">
            <label>Which agent is being tested?</label>
            <select id="rpTargetAgent">
                <!-- Populated dynamically -->
            </select>
        </div>
        <div class="fg">
            <label>Difficulty</label>
            <select id="rpDifficulty">
                <option value="easy">🟢 Easy — straightforward interaction</option>
                <option value="medium" selected>🟡 Medium — some pushback</option>
                <option value="hard">🔴 Hard — hostile, impatient, complex</option>
            </select>
        </div>
        <div class="modal-actions">
            <button class="btn btn-ghost" onclick="window.TeamChat.hideModal('roleplayModal')">Cancel</button>
            <button class="btn btn-primary" onclick="window.TeamChat.launchRoleplay()"><i class="fas fa-play"></i> Start Scenario</button>
        </div>
    </div>
</div>

<!-- ═══ PERFORMANCE MODAL ═══ -->
<!-- ═══ NEGOTIATE MODAL ═══ -->
<div class="modal-overlay" id="negotiateModal">
    <div class="modal">
        <h2><i class="fas fa-handshake"></i> Agent-to-Agent Negotiation</h2>
        <p style="color:var(--text2);font-size:.85rem;margin-bottom:1rem">Set a topic and let your agents debate, negotiate, and reach consensus autonomously.</p>
        <div class="fg">
            <label>Negotiation Topic</label>
            <textarea id="negTopic" rows="3" placeholder="e.g. What's the best tech stack for a restaurant booking system with voice AI? Consider budget, speed, and scalability."></textarea>
        </div>
        <div class="fg">
            <label>Rounds (1-10)</label>
            <input type="number" id="negRounds" value="3" min="1" max="10">
            <small style="color:var(--text2);font-size:.75rem">Each agent responds once per round. More rounds = deeper discussion.</small>
        </div>
        <div class="modal-actions">
            <button class="btn btn-ghost" onclick="window.TeamChat.hideModal('negotiateModal')">Cancel</button>
            <button class="btn btn-primary" onclick="window.TeamChat.launchNegotiation()"><i class="fas fa-gavel"></i> Start Negotiation</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="performanceModal">
    <div class="modal" style="max-width:600px">
        <h2><i class="fas fa-chart-bar"></i> Agent Performance</h2>
        <div id="perfContent" style="font-size:.88rem">Loading...</div>
        <div class="modal-actions">
            <button class="btn btn-ghost" onclick="window.TeamChat.hideModal('performanceModal')">Close</button>
        </div>
    </div>
</div>

<script src="/assets/js/team-chat-engine.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    window.TeamChat.init({
        csrfToken: <?= json_encode($csrf_token) ?>,
        userName: <?= json_encode(htmlspecialchars($client_name, ENT_QUOTES, 'UTF-8')) ?>,
        userId: <?= json_encode($client_id) ?>
    });
});
</script>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
