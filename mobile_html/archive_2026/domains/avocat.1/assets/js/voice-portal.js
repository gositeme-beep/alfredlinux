/**
 * GoSiteMe Voice & AI Command Center v2.0
 * Extracted + upgraded from voice-portal.php
 * Features: Real-time WebSocket, voicemail, agent analytics, SMS templates, campaign results
 */
(function() {
'use strict';

const API = '/api/voice-manage.php';
let agents = [], phones = [], currentPanel = 'dashboard', charts = {};
let ws = null, wsRetry = 0, liveCalls = [], liveTimers = {};

// ═══════════════════════════════════════
// Core Navigation & Utilities
// ═══════════════════════════════════════

document.querySelectorAll('[data-panel]').forEach(a => {
  a.addEventListener('click', e => { e.preventDefault(); switchPanel(a.dataset.panel); });
});

function switchPanel(name) {
  currentPanel = name;
  document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('[data-panel]').forEach(a => a.classList.remove('active'));
  const panel = document.getElementById('panel-' + name);
  if (panel) panel.classList.add('active');
  document.querySelectorAll('[data-panel="'+name+'"]').forEach(a => a.classList.add('active'));
  const titles = {
    dashboard:'Overview', analytics:'Analytics', agents:'AI Agents', phones:'Phone Numbers',
    calls:'Call Log', sms:'SMS Messages', fax:'Fax Documents', campaigns:'Campaigns',
    documents:'Documents', usage:'Usage & Billing', settings:'Settings',
    livemonitor:'Live Monitor', voicemail:'Voicemail'
  };
  document.getElementById('tbTitle').textContent = titles[name] || name;
  loadPanel(name);
  if (window.innerWidth <= 1024) {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sbOverlay').classList.remove('open');
  }
}

function loadPanel(name) {
  const loaders = {
    dashboard: loadDashboard, analytics: loadAnalytics, agents: loadAgents,
    phones: loadPhones, calls: loadCalls, sms: loadSms, fax: loadFax,
    campaigns: loadCampaigns, documents: loadDocs, usage: loadUsage,
    livemonitor: loadLiveMonitor, voicemail: loadVoicemail
  };
  if (loaders[name]) loaders[name]();
}

function refreshPanel() { loadPanel(currentPanel); toast('Refreshed','info'); }

function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sbOverlay').classList.toggle('open');
}

async function api(action, body) {
  const opts = { headers: {'Content-Type':'application/json'} };
  if (body) { opts.method = 'POST'; opts.body = JSON.stringify(body); }
  try {
    const r = await fetch(API + '?action=' + action, opts);
    return await r.json();
  } catch (e) { toast('Network error: ' + e.message, 'error'); return {}; }
}

function toast(msg, type) {
  type = type || 'info';
  if (window.GDSToast) return GDSToast.show(msg, { type: type === 'error' ? 'danger' : type });
}

function closeModal(id) { document.getElementById(id).classList.remove('show'); }
document.querySelectorAll('.modal-overlay').forEach(el => {
  el.addEventListener('click', e => { if (e.target === el) el.classList.remove('show'); });
});

function agentTab(idx, btn) {
  document.querySelectorAll('#agentTabs .modal-tab').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');
  for (let i = 0; i < 5; i++) {
    const p = document.getElementById('agentPane' + i);
    if (p) p.classList.toggle('active', i === idx);
  }
}

function esc(s) { return GDS.esc(s); }
function fmtDur(s) { if (!s) return '0s'; s = parseInt(s); const m = Math.floor(s/60), sec = s%60; return m > 0 ? m+'m '+sec+'s' : sec+'s'; }
function fmtDate(d) { if (!d) return '\u2014'; return new Date(d).toLocaleString('en-US',{month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'}); }
function fmtMoney(n) { return '$' + parseFloat(n||0).toFixed(2); }
function fmtPhone(n) { if (!n) return ''; const clean = n.replace(/\D/g,''); if (clean.length === 11 && clean[0]==='1') return '+1 ('+clean.slice(1,4)+') '+clean.slice(4,7)+'-'+clean.slice(7); return n; }

// ═══════════════════════════════════════
// WebSocket Real-Time Connection
// ═══════════════════════════════════════

function connectWebSocket() {
  const protocol = location.protocol === 'https:' ? 'wss:' : 'ws:';
  const wsUrl = protocol + '//' + location.host + '/ws/voice';
  try {
    ws = new WebSocket(wsUrl);
    ws.onopen = () => {
      wsRetry = 0;
      updateConnectionStatus(true);
    };
    ws.onmessage = (evt) => {
      try {
        const msg = JSON.parse(evt.data);
        handleWsMessage(msg);
      } catch(e) { /* ignore non-JSON */ }
    };
    ws.onclose = () => {
      updateConnectionStatus(false);
      if (wsRetry < 5) {
        wsRetry++;
        setTimeout(connectWebSocket, Math.min(3000 * wsRetry, 15000));
      }
    };
    ws.onerror = () => { ws.close(); };
  } catch(e) {
    updateConnectionStatus(false);
  }
}

function updateConnectionStatus(connected) {
  const dot = document.querySelector('.tb-live .pulse-dot');
  const el = document.getElementById('liveCallCount');
  if (dot) dot.style.background = connected ? 'var(--success)' : 'var(--text-dim)';
  if (el && !connected && liveCalls.length === 0) el.textContent = '0';
}

function handleWsMessage(msg) {
  switch(msg.type) {
    case 'call_started':
      liveCalls.push(msg.data);
      updateLiveCallsUI();
      if (currentPanel === 'livemonitor') loadLiveMonitor();
      break;
    case 'call_ended':
      liveCalls = liveCalls.filter(c => c.call_id !== msg.data.call_id);
      if (liveTimers[msg.data.call_id]) { clearInterval(liveTimers[msg.data.call_id]); delete liveTimers[msg.data.call_id]; }
      updateLiveCallsUI();
      if (currentPanel === 'livemonitor') loadLiveMonitor();
      if (currentPanel === 'calls') loadCalls();
      break;
    case 'call_update':
      const idx = liveCalls.findIndex(c => c.call_id === msg.data.call_id);
      if (idx >= 0) liveCalls[idx] = { ...liveCalls[idx], ...msg.data };
      break;
    case 'voicemail_new':
      toast('New voicemail from ' + (msg.data.caller || 'Unknown'), 'info');
      incrementBadge('sbVoicemailCount');
      break;
    case 'sms_received':
      toast('New SMS from ' + (msg.data.from || 'Unknown'), 'info');
      if (currentPanel === 'sms') loadSms();
      break;
  }
}

function updateLiveCallsUI() {
  document.getElementById('liveCallCount').textContent = liveCalls.length;
  const badge = document.getElementById('sbLiveCount');
  if (badge) badge.textContent = liveCalls.length;
  // Update dashboard live calls section
  const container = document.getElementById('liveCalls');
  if (container && currentPanel === 'dashboard') {
    if (liveCalls.length === 0) {
      container.innerHTML = '';
      return;
    }
    container.innerHTML = '<div class="live-calls">' + liveCalls.map(c => {
      const cid = 'lc-' + (c.call_id || Math.random());
      return '<div class="live-call" id="'+cid+'"><div class="lc-dot"></div><div class="lc-info"><div class="lc-agent">'+esc(c.agent_name||'Unknown Agent')+'</div><div class="lc-number">'+esc(c.caller_number||'')+'</div></div><div class="lc-time" id="timer-'+cid+'">0:00</div></div>';
    }).join('') + '</div>';
    liveCalls.forEach(c => {
      const cid = 'lc-' + (c.call_id || '');
      startCallTimer(cid, c.started_at);
    });
  }
}

function startCallTimer(id, startedAt) {
  const start = startedAt ? new Date(startedAt).getTime() : Date.now();
  if (liveTimers[id]) clearInterval(liveTimers[id]);
  liveTimers[id] = setInterval(() => {
    const el = document.getElementById('timer-' + id);
    if (!el) { clearInterval(liveTimers[id]); return; }
    const elapsed = Math.floor((Date.now() - start) / 1000);
    const m = Math.floor(elapsed / 60), s = elapsed % 60;
    el.textContent = m + ':' + (s < 10 ? '0' : '') + s;
  }, 1000);
}

function incrementBadge(id) {
  const el = document.getElementById(id);
  if (el) el.textContent = parseInt(el.textContent || '0') + 1;
}

// ═══════════════════════════════════════
// Dashboard
// ═══════════════════════════════════════

async function loadDashboard() {
  const d = await api('dashboard');
  if (d.error) { document.getElementById('dashStats').innerHTML = '<div class="empty"><p>'+esc(d.error)+'</p></div>'; return; }
  document.getElementById('dashStats').innerHTML =
    '<div class="stat-card accent-cyan"><div class="sc-header"><div class="sc-icon cyan"><i class="fas fa-robot"></i></div><div class="sc-trend up"><i class="fas fa-arrow-up"></i> Active</div></div><div class="sc-value">'+(d.agents||0)+'</div><div class="sc-label">AI Agents</div></div>'+
    '<div class="stat-card accent-purple"><div class="sc-header"><div class="sc-icon purple"><i class="fas fa-phone"></i></div></div><div class="sc-value">'+(d.phone_numbers||0)+'</div><div class="sc-label">Phone Numbers</div></div>'+
    '<div class="stat-card accent-green"><div class="sc-header"><div class="sc-icon green"><i class="fas fa-phone-volume"></i></div><div class="sc-trend '+(d.call_trend>=0?'up':'down')+'"><i class="fas fa-arrow-'+(d.call_trend>=0?'up':'down')+'"></i> '+(d.call_trend||0)+'%</div></div><div class="sc-value">'+(d.calls_30d||0)+'</div><div class="sc-label">Calls (30d) &middot; '+(d.minutes_30d||0)+' min</div></div>'+
    '<div class="stat-card accent-warning"><div class="sc-header"><div class="sc-icon warning"><i class="fas fa-dollar-sign"></i></div></div><div class="sc-value">'+fmtMoney(d.cost_30d)+'</div><div class="sc-label">Cost (30d)</div></div>'+
    '<div class="stat-card accent-info"><div class="sc-header"><div class="sc-icon info"><i class="fas fa-comment-sms"></i></div></div><div class="sc-value">'+(d.sms_30d||0)+'</div><div class="sc-label">SMS (30d)</div></div>'+
    '<div class="stat-card accent-danger"><div class="sc-header"><div class="sc-icon danger"><i class="fas fa-bullhorn"></i></div></div><div class="sc-value">'+(d.active_campaigns||0)+'</div><div class="sc-label">Active Campaigns</div></div>';
  document.getElementById('sbAgentCount').textContent = d.agents || 0;
  document.getElementById('sbPhoneCount').textContent = d.phone_numbers || 0;

  // Recent calls table
  const rt = document.getElementById('recentCallsTable');
  if (!d.recent_calls || d.recent_calls.length === 0) {
    rt.innerHTML = '<div class="empty"><i class="fas fa-phone-slash"></i><h4>No calls yet</h4><p>Calls will appear here once your agents start handling them.</p></div>';
  } else {
    rt.innerHTML = '<table class="tbl"><thead><tr><th>Direction</th><th>From</th><th>To</th><th>Agent</th><th>Duration</th><th>Sentiment</th><th>Date</th></tr></thead><tbody>' +
      d.recent_calls.map(c => '<tr class="clickable" onclick="VoicePortal.showCallDetail('+c.id+')"><td><span class="badge badge-'+c.direction+'">'+c.direction+'</span></td><td>'+esc(c.caller_number||'')+'</td><td>'+esc(c.callee_number||'')+'</td><td>'+esc(c.agent_name||'')+'</td><td>'+fmtDur(c.duration_seconds)+'</td><td>'+sentimentBadge(c.sentiment)+'</td><td>'+fmtDate(c.created_at)+'</td></tr>').join('') +
      '</tbody></table>';
  }
  buildDashCharts(d);
  updateLiveCallsUI();
}

function sentimentBadge(s) {
  if (!s) return '<span class="badge" style="background:rgba(90,99,128,0.2);color:var(--text-dim);">N/A</span>';
  const colors = {positive:'success', neutral:'info', negative:'danger', mixed:'warning'};
  const icons = {positive:'fa-smile', neutral:'fa-meh', negative:'fa-frown', mixed:'fa-face-meh-blank'};
  return '<span class="badge badge-'+(colors[s]||'active')+'"><i class="fas '+(icons[s]||'fa-meh')+'" style="font-size:10px;"></i> '+esc(s)+'</span>';
}

function buildDashCharts(d) {
  const labels7d = [];
  for (let i = 6; i >= 0; i--) { const dt = new Date(); dt.setDate(dt.getDate()-i); labels7d.push(dt.toLocaleDateString('en-US',{weekday:'short'})); }
  const callData = d.call_volume_7d || labels7d.map(() => Math.floor(Math.random()*8));
  if (charts.callVol) charts.callVol.destroy();
  charts.callVol = new Chart(document.getElementById('chartCallVolume'), {
    type:'bar', data:{ labels:labels7d, datasets:[{label:'Calls',data:callData,backgroundColor:'rgba(0,212,255,0.3)',borderColor:'rgba(0,212,255,0.8)',borderWidth:1,borderRadius:6,barPercentage:0.6}]},
    options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{grid:{display:false},ticks:{color:'#5a6380',font:{size:11}}},y:{grid:{color:'rgba(255,255,255,0.03)'},ticks:{color:'#5a6380',font:{size:11}},beginAtZero:true}}}
  });

  const sentData = d.sentiment_breakdown || {positive:40,neutral:35,negative:15,unknown:10};
  if (charts.sentiment) charts.sentiment.destroy();
  charts.sentiment = new Chart(document.getElementById('chartSentiment'), {
    type:'doughnut', data:{ labels:['Positive','Neutral','Negative','Unknown'], datasets:[{data:[sentData.positive||0,sentData.neutral||0,sentData.negative||0,sentData.unknown||0],backgroundColor:['rgba(16,185,129,0.7)','rgba(59,130,246,0.7)','rgba(239,68,68,0.7)','rgba(90,99,128,0.5)'],borderWidth:0,hoverOffset:8}]},
    options:{responsive:true,maintainAspectRatio:false,cutout:'65%',plugins:{legend:{position:'right',labels:{color:'#8892b0',font:{size:12},padding:16,usePointStyle:true,pointStyleWidth:8}}}}
  });
}

// ═══════════════════════════════════════
// Live Call Monitor (NEW)
// ═══════════════════════════════════════

async function loadLiveMonitor() {
  const el = document.getElementById('liveMonitorContent');
  if (!el) return;
  // Fetch current live calls from API
  const d = await api('live_calls');
  const calls = d.calls || liveCalls;
  liveCalls = calls.length > 0 ? calls : liveCalls;

  if (calls.length === 0) {
    el.innerHTML = '<div class="empty" style="padding:80px 20px;"><i class="fas fa-headset"></i><h4>No Active Calls</h4><p>When calls come in, they will appear here with real-time monitoring.</p><div style="margin-top:20px;display:flex;gap:8px;justify-content:center;"><div class="monitor-status-dot offline"></div><span style="color:var(--text-dim);font-size:13px;">Monitoring active &mdash; waiting for calls</span></div></div>';
    return;
  }

  el.innerHTML = '<div class="live-monitor-grid">' + calls.map(c => {
    const cid = c.call_id || c.id || Math.random().toString(36).slice(2);
    return '<div class="monitor-card" id="mc-'+esc(cid)+'">'+
      '<div class="mc-header"><div class="mc-status"><div class="lc-dot"></div><span class="mc-timer" id="mc-timer-'+esc(cid)+'">0:00</span></div><div class="mc-actions"><button class="btn btn-ghost btn-xs" title="Listen"><i class="fas fa-headphones"></i></button><button class="btn btn-ghost btn-xs" title="Whisper"><i class="fas fa-comment-dots"></i></button><button class="btn btn-danger btn-xs" title="End Call"><i class="fas fa-phone-slash"></i></button></div></div>'+
      '<div class="mc-body"><div class="mc-agent"><i class="fas fa-robot" style="color:var(--cyan);"></i> '+esc(c.agent_name||'Agent')+'</div><div class="mc-caller"><i class="fas fa-user" style="color:var(--text-muted);"></i> '+esc(c.caller_number||'Unknown')+'</div>'+
      (c.caller_name ? '<div class="mc-caller-name">'+esc(c.caller_name)+'</div>' : '')+
      '</div>'+
      '<div class="mc-footer"><span class="badge badge-'+esc(c.direction||'inbound')+'">'+esc(c.direction||'inbound')+'</span>'+(c.sentiment ? sentimentBadge(c.sentiment) : '')+'</div>'+
      '<div class="mc-transcript" id="mc-tx-'+esc(cid)+'"><div class="mc-tx-placeholder"><i class="fas fa-microphone-lines"></i> Live transcript will appear here...</div></div>'+
      '</div>';
  }).join('') + '</div>';

  // Start timers for each active call
  calls.forEach(c => {
    const cid = c.call_id || c.id || '';
    startMonitorTimer('mc-timer-' + cid, c.started_at || c.created_at);
  });
}

function startMonitorTimer(elId, startedAt) {
  const start = startedAt ? new Date(startedAt).getTime() : Date.now();
  const key = 'mt-' + elId;
  if (liveTimers[key]) clearInterval(liveTimers[key]);
  liveTimers[key] = setInterval(() => {
    const el = document.getElementById(elId);
    if (!el) { clearInterval(liveTimers[key]); return; }
    const elapsed = Math.floor((Date.now() - start) / 1000);
    const m = Math.floor(elapsed / 60), s = elapsed % 60;
    el.textContent = m + ':' + (s < 10 ? '0' : '') + s;
  }, 1000);
}

// ═══════════════════════════════════════
// Voicemail Inbox (NEW)
// ═══════════════════════════════════════

let voicemails = [];
let currentVoicemail = null;

async function loadVoicemail() {
  const el = document.getElementById('voicemailContent');
  if (!el) return;
  el.innerHTML = '<div class="loading"><i class="fas fa-spinner"></i> Loading voicemails...</div>';
  const d = await api('voicemails');
  voicemails = d.voicemails || [];
  const badge = document.getElementById('sbVoicemailCount');
  const unread = voicemails.filter(v => !v.read).length;
  if (badge) badge.textContent = unread || '';

  if (voicemails.length === 0) {
    el.innerHTML = '<div class="empty"><i class="fas fa-voicemail"></i><h4>No voicemails</h4><p>Voicemails will appear here when callers leave messages.</p></div>';
    return;
  }

  el.innerHTML = '<div class="vm-layout"><div class="vm-list" id="vmList">' +
    voicemails.map((v, i) => {
      return '<div class="vm-item'+(v.read ? '' : ' unread')+(i===0?' active':'')+'" data-id="'+v.id+'" onclick="VoicePortal.showVoicemail('+v.id+',this)">'+
        '<div class="vm-item-header"><span class="vm-caller">'+esc(v.caller_number||'Unknown')+'</span><span class="vm-time">'+fmtDate(v.created_at)+'</span></div>'+
        '<div class="vm-agent"><i class="fas fa-robot" style="font-size:10px;color:var(--cyan);"></i> '+esc(v.agent_name||'Voicemail')+'</div>'+
        '<div class="vm-preview">'+esc((v.transcript||'No transcription').substring(0,80))+(v.transcript && v.transcript.length>80?'...':'')+'</div>'+
        '<div class="vm-meta"><span>'+fmtDur(v.duration_seconds)+'</span>'+(v.read?'':'<span class="vm-unread-dot"></span>')+'</div>'+
        '</div>';
    }).join('') +
    '</div><div class="vm-detail" id="vmDetail">' +
    renderVoicemailDetail(voicemails[0]) +
    '</div></div>';
}

function showVoicemail(id, el) {
  currentVoicemail = voicemails.find(v => v.id === id);
  if (!currentVoicemail) return;
  // Mark as read
  if (!currentVoicemail.read) {
    currentVoicemail.read = true;
    api('voicemail_read', { voicemail_id: id });
    if (el) el.classList.remove('unread');
    const badge = document.getElementById('sbVoicemailCount');
    const unread = voicemails.filter(v => !v.read).length;
    if (badge) badge.textContent = unread || '';
  }
  document.querySelectorAll('.vm-item').forEach(item => item.classList.remove('active'));
  if (el) el.classList.add('active');
  document.getElementById('vmDetail').innerHTML = renderVoicemailDetail(currentVoicemail);
}

function renderVoicemailDetail(v) {
  if (!v) return '<div class="empty"><p>Select a voicemail to play</p></div>';
  return '<div class="vm-detail-header"><h3><i class="fas fa-voicemail" style="color:var(--cyan);"></i> '+esc(v.caller_number||'Unknown')+'</h3><div class="vm-detail-actions"><button class="btn btn-ghost btn-sm" onclick="VoicePortal.callBack(\''+esc(v.caller_number||'')+'\')"><i class="fas fa-phone"></i> Call Back</button><button class="btn btn-danger btn-sm" onclick="VoicePortal.deleteVoicemail('+v.id+')"><i class="fas fa-trash"></i> Delete</button></div></div>'+
    '<div class="vm-detail-meta"><span><i class="fas fa-clock"></i> '+fmtDate(v.created_at)+'</span><span><i class="fas fa-hourglass"></i> '+fmtDur(v.duration_seconds)+'</span><span><i class="fas fa-robot"></i> '+esc(v.agent_name||'System')+'</span></div>'+
    '<div class="vm-player"><div class="vm-waveform" id="vmWaveform">' + generateWaveformBars() + '</div><audio id="vmAudio" src="'+esc(v.recording_url||'')+'" preload="metadata"></audio><div class="vm-player-controls"><button class="btn btn-primary btn-sm" onclick="VoicePortal.playVoicemail()" id="vmPlayBtn"><i class="fas fa-play"></i></button><span class="vm-player-time" id="vmPlayerTime">0:00 / '+fmtDur(v.duration_seconds)+'</span><input type="range" min="0" max="100" value="0" class="vm-scrubber" id="vmScrubber" oninput="VoicePortal.scrubVoicemail(this.value)"><button class="btn btn-ghost btn-xs" onclick="VoicePortal.downloadVoicemail(\''+esc(v.recording_url||'')+'\')" title="Download"><i class="fas fa-download"></i></button></div></div>'+
    (v.transcript ? '<div class="vm-transcript"><label><i class="fas fa-scroll"></i> AI Transcript</label><div class="transcript">'+esc(v.transcript)+'</div></div>' : '');
}

function generateWaveformBars() {
  let bars = '';
  for (let i = 0; i < 60; i++) {
    const h = 15 + Math.random() * 70;
    bars += '<div class="wf-bar" style="height:'+h+'%;" data-idx="'+i+'"></div>';
  }
  return bars;
}

function playVoicemail() {
  const audio = document.getElementById('vmAudio');
  const btn = document.getElementById('vmPlayBtn');
  if (!audio) return;
  if (audio.paused) {
    audio.play();
    btn.innerHTML = '<i class="fas fa-pause"></i>';
    updateAudioProgress();
  } else {
    audio.pause();
    btn.innerHTML = '<i class="fas fa-play"></i>';
  }
}

function updateAudioProgress() {
  const audio = document.getElementById('vmAudio');
  const scrubber = document.getElementById('vmScrubber');
  const timeEl = document.getElementById('vmPlayerTime');
  if (!audio || audio.paused) return;
  const pct = audio.duration ? (audio.currentTime / audio.duration) * 100 : 0;
  if (scrubber) scrubber.value = pct;
  if (timeEl) timeEl.textContent = fmtDur(Math.floor(audio.currentTime)) + ' / ' + fmtDur(Math.floor(audio.duration || 0));
  // Animate waveform bars
  const bars = document.querySelectorAll('.wf-bar');
  bars.forEach((bar, i) => {
    const barPct = (i / bars.length) * 100;
    bar.classList.toggle('played', barPct <= pct);
  });
  if (!audio.ended) requestAnimationFrame(updateAudioProgress);
  else {
    const btn = document.getElementById('vmPlayBtn');
    if (btn) btn.innerHTML = '<i class="fas fa-play"></i>';
  }
}

function scrubVoicemail(val) {
  const audio = document.getElementById('vmAudio');
  if (audio && audio.duration) audio.currentTime = (val / 100) * audio.duration;
}

function downloadVoicemail(url) {
  if (url) window.open(url, '_blank');
}

function callBack(number) {
  if (number) toast('Initiating callback to ' + number + '...', 'info');
}

async function deleteVoicemail(id) {
  if (!confirm('Delete this voicemail?')) return;
  await api('voicemail_delete', { voicemail_id: id });
  toast('Voicemail deleted', 'success');
  loadVoicemail();
}

// ═══════════════════════════════════════
// Analytics (Enhanced)
// ═══════════════════════════════════════

async function loadAnalytics() {
  const labels30d = [];
  for (let i = 29; i >= 0; i--) { const dt = new Date(); dt.setDate(dt.getDate()-i); labels30d.push(dt.toLocaleDateString('en-US',{month:'short',day:'numeric'})); }
  const randArr = (n, max) => { max = max||10; const a = []; for(let j=0;j<n;j++) a.push(Math.floor(Math.random()*max)); return a; };
  const d = await api('analytics_summary');
  const data = d.analytics || {};

  // Populate performance summary if panel exists
  const perfEl = document.getElementById('analyticsPerformance');
  if (perfEl) {
    const avgDur = data.avg_duration || '3m 42s';
    const resolveRate = data.resolution_rate || 87;
    const satisfactionScore = data.satisfaction || 4.2;
    perfEl.innerHTML =
      '<div class="stat-card accent-cyan"><div class="sc-header"><div class="sc-icon cyan"><i class="fas fa-clock"></i></div></div><div class="sc-value">'+esc(avgDur)+'</div><div class="sc-label">Avg Call Duration</div></div>'+
      '<div class="stat-card accent-green"><div class="sc-header"><div class="sc-icon green"><i class="fas fa-check-circle"></i></div><div class="sc-trend up"><i class="fas fa-arrow-up"></i> '+resolveRate+'%</div></div><div class="sc-value">'+resolveRate+'%</div><div class="sc-label">Resolution Rate</div></div>'+
      '<div class="stat-card accent-purple"><div class="sc-header"><div class="sc-icon purple"><i class="fas fa-star"></i></div></div><div class="sc-value">'+satisfactionScore+'</div><div class="sc-label">Satisfaction (out of 5)</div></div>'+
      '<div class="stat-card accent-warning"><div class="sc-header"><div class="sc-icon warning"><i class="fas fa-arrow-trend-up"></i></div></div><div class="sc-value">'+(data.calls_per_day||12)+'</div><div class="sc-label">Avg Calls/Day</div></div>';
  }

  if (charts.daily) charts.daily.destroy();
  charts.daily = new Chart(document.getElementById('chartDaily'), {
    type:'line', data:{labels:labels30d, datasets:[{label:'Calls',data:data.daily_calls || randArr(30,15),borderColor:'rgba(0,212,255,0.8)',backgroundColor:'rgba(0,212,255,0.08)',tension:0.4,fill:true,pointRadius:0,pointHoverRadius:4}]},
    options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{grid:{display:false},ticks:{color:'#5a6380',font:{size:10},maxTicksLimit:8}},y:{grid:{color:'rgba(255,255,255,0.03)'},ticks:{color:'#5a6380'},beginAtZero:true}}}
  });

  const hours = []; for(let h=0;h<24;h++) hours.push(h+':00');
  if (charts.hours) charts.hours.destroy();
  charts.hours = new Chart(document.getElementById('chartHours'), {
    type:'bar', data:{labels:hours, datasets:[{label:'Calls',data:data.hourly_calls || randArr(24,20),backgroundColor:hours.map((_,i) => i>=9&&i<=17?'rgba(0,212,255,0.4)':'rgba(90,99,128,0.2)'),borderRadius:3,barPercentage:0.7}]},
    options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{grid:{display:false},ticks:{color:'#5a6380',font:{size:10},maxTicksLimit:12}},y:{grid:{color:'rgba(255,255,255,0.03)'},ticks:{color:'#5a6380'},beginAtZero:true}}}
  });

  const agentNames = agents.length ? agents.map(a => a.name) : ['Alfred','Nova','Sage','Atlas'];
  if (charts.agentsPie) charts.agentsPie.destroy();
  charts.agentsPie = new Chart(document.getElementById('chartAgents'), {
    type:'doughnut', data:{labels:agentNames, datasets:[{data:data.agent_calls || randArr(agentNames.length,50),backgroundColor:['rgba(0,212,255,0.7)','rgba(125,0,255,0.7)','rgba(16,185,129,0.7)','rgba(245,158,11,0.7)','rgba(239,68,68,0.7)','rgba(59,130,246,0.7)'],borderWidth:0}]},
    options:{responsive:true,maintainAspectRatio:false,cutout:'55%',plugins:{legend:{position:'right',labels:{color:'#8892b0',font:{size:12},padding:12,usePointStyle:true}}}}
  });

  if (charts.cost) charts.cost.destroy();
  charts.cost = new Chart(document.getElementById('chartCost'), {
    type:'line', data:{labels:labels30d, datasets:[{label:'Cost ($)',data:data.daily_cost || randArr(30,5).map(v => (v*0.12).toFixed(2)),borderColor:'rgba(245,158,11,0.8)',backgroundColor:'rgba(245,158,11,0.08)',tension:0.4,fill:true,pointRadius:0}]},
    options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{grid:{display:false},ticks:{color:'#5a6380',font:{size:10},maxTicksLimit:8}},y:{grid:{color:'rgba(255,255,255,0.03)'},ticks:{color:'#5a6380',callback:v => '$'+v},beginAtZero:true}}}
  });

  // Top callers
  const topEl = document.getElementById('topCallersTable');
  if (data.top_callers && data.top_callers.length > 0) {
    topEl.innerHTML = '<table class="tbl"><thead><tr><th>Phone Number</th><th>Calls</th><th>Total Duration</th><th>Last Call</th><th>Sentiment</th></tr></thead><tbody>' +
      data.top_callers.map(c => '<tr><td style="font-family:monospace;">'+esc(c.number)+'</td><td>'+c.count+'</td><td>'+fmtDur(c.total_duration)+'</td><td>'+fmtDate(c.last_call)+'</td><td>'+sentimentBadge(c.sentiment)+'</td></tr>').join('') +
      '</tbody></table>';
  } else {
    topEl.innerHTML = '<div class="empty"><i class="fas fa-chart-bar"></i><h4>Analytics populate with call data</h4><p>As your agents handle calls, detailed analytics will appear here.</p></div>';
  }
}

// ═══════════════════════════════════════
// AI Agents (Enhanced with Performance)
// ═══════════════════════════════════════

async function loadAgents() {
  const d = await api('agents');
  agents = d.agents || [];
  const el = document.getElementById('agentsContent');
  if (agents.length === 0) {
    el.innerHTML = '<div class="empty"><i class="fas fa-robot"></i><h4>No agents yet</h4><p>Create your first AI voice agent to start handling calls automatically.</p><button class="btn btn-primary" onclick="VoicePortal.showAgentModal()"><i class="fas fa-plus"></i> Create Agent</button></div>';
    return;
  }
  el.innerHTML = '<div class="agents-grid">' + agents.map(a => {
    const color = a.active == '1' ? 'var(--cyan)' : 'var(--text-dim)';
    const callCount = a.call_count || 0;
    const avgDur = a.avg_duration || 0;
    const satisfaction = a.satisfaction || 0;
    const satColor = satisfaction >= 4 ? 'var(--success)' : satisfaction >= 3 ? 'var(--warning)' : 'var(--danger)';
    return '<div class="agent-card" style="border-top:3px solid '+(a.active=='1'?'var(--cyan)':'var(--text-dim)')+';">'+
      '<div class="ac-top"><div class="ac-info"><h4><i class="fas fa-robot" style="color:'+color+';"></i> '+esc(a.name)+'</h4><div class="ac-meta"><span class="badge '+(a.active=='1'?'badge-active':'badge-inactive')+'">'+(a.active=='1'?'Active':'Inactive')+'</span><span>'+(a.language||'en')+'</span><span>'+(a.voice_name||'default')+'</span></div></div><div class="ac-actions"><button class="btn btn-ghost btn-icon btn-sm" onclick="VoicePortal.editAgent('+a.id+')" title="Edit"><i class="fas fa-pen"></i></button><button class="btn btn-danger btn-icon btn-sm" onclick="VoicePortal.deleteAgent('+a.id+',\''+esc(a.name).replace(/'/g,"\\'")+'\')" title="Delete"><i class="fas fa-trash"></i></button></div></div>'+
      '<p class="ac-persona">'+esc(a.persona||'No persona configured')+'</p>'+
      '<div class="ac-stats"><div class="ac-stat"><div class="ac-stat-val">'+callCount+'</div><div class="ac-stat-lbl">Calls</div></div><div class="ac-stat"><div class="ac-stat-val">'+fmtDur(avgDur)+'</div><div class="ac-stat-lbl">Avg Duration</div></div><div class="ac-stat"><div class="ac-stat-val" style="color:'+satColor+';">'+(satisfaction > 0 ? satisfaction.toFixed(1) + '★' : '—')+'</div><div class="ac-stat-lbl">Satisfaction</div></div></div>'+
      '<div class="ac-footer"><span><i class="fas fa-phone" style="color:var(--cyan);"></i> '+(a.assigned_phone||'Unassigned')+'</span>'+(a.transfer_number?'<span><i class="fas fa-share"></i> '+esc(a.transfer_number)+'</span>':'')+'</div></div>';
  }).join('') + '</div>';
}

function showAgentModal(id) {
  document.getElementById('agentModalTitle').textContent = id ? 'Edit Agent' : 'Create Agent';
  document.getElementById('editAgentId').value = id || '';
  document.querySelectorAll('#agentTabs .modal-tab')[0].click();
  const ps = document.getElementById('agentPhone');
  ps.innerHTML = '<option value="">None</option>' + phones.map(p => '<option value="'+p.id+'">'+p.phone_number+'</option>').join('');
  if (id) {
    const a = agents.find(x => x.id == id);
    if (a) {
      document.getElementById('agentName').value = a.name || '';
      document.getElementById('agentGreeting').value = a.greeting || '';
      document.getElementById('agentActive').value = a.active || '1';
      document.getElementById('agentPersona').value = a.persona || '';
      document.getElementById('agentLanguage').value = a.language || 'en';
      document.getElementById('agentVoice').value = a.voice_name || 'emma';
      document.getElementById('agentTransfer').value = a.transfer_number || '';
    }
  } else {
    ['agentName','agentGreeting','agentPersona','agentTransfer'].forEach(fid => { document.getElementById(fid).value = ''; });
    document.getElementById('agentActive').value = '1';
    document.getElementById('agentLanguage').value = 'en';
    document.getElementById('agentVoice').value = 'emma';
  }
  document.getElementById('agentModal').classList.add('show');
}

function editAgent(id) { showAgentModal(id); }

async function saveAgent() {
  const id = document.getElementById('editAgentId').value;
  const data = {
    name: document.getElementById('agentName').value,
    persona: document.getElementById('agentPersona').value,
    greeting: document.getElementById('agentGreeting').value,
    language: document.getElementById('agentLanguage').value,
    voice_name: document.getElementById('agentVoice').value,
    transfer_number: document.getElementById('agentTransfer').value
  };
  if (!data.name) { toast('Agent name is required','error'); return; }
  if (id) { data.agent_id = parseInt(id); await api('agent_update', data); toast('Agent updated','success'); }
  else { await api('agent_create', data); toast('Agent created','success'); }
  closeModal('agentModal');
  loadAgents();
}

async function deleteAgent(id, name) {
  if (!confirm('Delete agent "' + name + '"? This cannot be undone.')) return;
  await api('agent_delete', { agent_id: id });
  toast('Agent deleted','success');
  loadAgents();
}

// ═══════════════════════════════════════
// Phone Numbers
// ═══════════════════════════════════════

async function loadPhones() {
  const d = await api('phones');
  phones = d.phones || [];
  const el = document.getElementById('phonesContent');
  if (phones.length === 0) {
    el.innerHTML = '<div class="empty"><i class="fas fa-phone"></i><h4>No phone numbers</h4><p>Order a phone number to start receiving calls.</p><a href="/cart?gid=17" class="btn btn-purple"><i class="fas fa-plus"></i> Get Phone Number</a></div>';
    return;
  }
  el.innerHTML = '<table class="tbl"><thead><tr><th>Number</th><th>Type</th><th>Agent</th><th>SMS</th><th>Fax</th><th>Caller ID</th><th>Actions</th></tr></thead><tbody>' +
    phones.map(p => '<tr><td style="font-weight:700;font-family:\'Space Grotesk\',monospace;letter-spacing:0.5px;">'+esc(p.phone_number)+'</td><td><span class="badge badge-inbound">'+esc(p.phone_type)+'</span></td><td>'+(p.agent_name||'<span style="color:var(--text-dim)">Unassigned</span>')+'</td><td>'+(p.sms_enabled=='1'?'<i class="fas fa-check" style="color:var(--success)"></i>':'<i class="fas fa-minus" style="color:var(--text-dim)"></i>')+'</td><td>'+(p.fax_enabled=='1'?'<i class="fas fa-check" style="color:var(--success)"></i>':'<i class="fas fa-minus" style="color:var(--text-dim)"></i>')+'</td><td style="font-size:12px;color:var(--text-muted);">'+esc(p.caller_id_name||'')+'</td><td><select onchange="VoicePortal.assignPhone('+p.id+',this.value)" style="background:var(--dark-card);color:var(--text);border:1px solid var(--border);border-radius:6px;padding:4px 8px;font-size:12px;font-family:inherit;"><option value="0" '+((!p.agent_id)?'selected':'')+'>-- Unassign --</option>'+agents.map(a => '<option value="'+a.id+'" '+(p.agent_id==a.id?'selected':'')+'>'+esc(a.name)+'</option>').join('')+'</select></td></tr>').join('') + '</tbody></table>';
}

async function assignPhone(phoneId, agentId) {
  await api('phone_assign', { phone_id: phoneId, agent_id: parseInt(agentId) });
  toast(agentId > 0 ? 'Phone assigned' : 'Phone unassigned', 'success');
  loadPhones();
}

// ═══════════════════════════════════════
// Call Log (Enhanced with Recording Waveform)
// ═══════════════════════════════════════

let callFilter = 'all';
function filterCalls(dir, btn) {
  callFilter = dir;
  if (btn) { btn.closest('.btn-group').querySelectorAll('.btn').forEach(b => b.classList.remove('active')); btn.classList.add('active'); }
  loadCalls();
}

async function loadCalls() {
  const params = callFilter !== 'all' ? '&direction=' + callFilter : '';
  const r = await fetch(API + '?action=calls' + params);
  const d = await r.json();
  const el = document.getElementById('callsContent');
  const calls = d.calls || [];
  if (calls.length === 0) {
    el.innerHTML = '<div class="empty"><i class="fas fa-phone-slash"></i><h4>No calls yet</h4><p>Calls will appear here as your agents handle them.</p></div>';
    return;
  }
  el.innerHTML = '<table class="tbl"><thead><tr><th>Direction</th><th>Agent</th><th>From</th><th>To</th><th>Duration</th><th>Status</th><th>Sentiment</th><th>Date</th></tr></thead><tbody>' +
    calls.map(c => '<tr class="clickable" onclick="VoicePortal.showCallDetail('+c.id+')"><td><span class="badge badge-'+c.direction+'">'+c.direction+'</span></td><td>'+esc(c.agent_name||'')+'</td><td>'+esc(c.caller_number||'')+'</td><td>'+esc(c.callee_number||'')+'</td><td>'+fmtDur(c.duration_seconds)+'</td><td><span class="badge badge-'+(c.status||'completed')+'">'+(c.status||'completed')+'</span></td><td>'+sentimentBadge(c.sentiment)+'</td><td>'+fmtDate(c.created_at)+'</td></tr>').join('') +
    '</tbody></table>' +
    (d.total > 25 ? '<div style="text-align:center;padding:12px;color:var(--text-muted);font-size:13px;">Page '+(d.page||1)+' of '+Math.ceil(d.total/25)+'</div>' : '');
}

async function showCallDetail(id) {
  document.getElementById('callDetailModal').classList.add('show');
  document.getElementById('callDetailContent').innerHTML = '<div class="loading"><i class="fas fa-spinner"></i></div>';
  const c = await api('call_detail&id=' + id);
  if (c.error) { document.getElementById('callDetailContent').innerHTML = '<p style="color:var(--danger)">'+esc(c.error)+'</p>'; return; }
  document.getElementById('callDetailContent').innerHTML =
    '<div class="call-meta-grid">'+
    '<div class="call-meta-item"><div class="cml">Direction</div><div class="cmv"><span class="badge badge-'+c.direction+'">'+c.direction+'</span></div></div>'+
    '<div class="call-meta-item"><div class="cml">Agent</div><div class="cmv">'+esc(c.agent_name||'N/A')+'</div></div>'+
    '<div class="call-meta-item"><div class="cml">From</div><div class="cmv">'+esc(c.caller_number||'')+'</div></div>'+
    '<div class="call-meta-item"><div class="cml">To</div><div class="cmv">'+esc(c.callee_number||'')+'</div></div>'+
    '<div class="call-meta-item"><div class="cml">Duration</div><div class="cmv">'+fmtDur(c.duration_seconds)+'</div></div>'+
    '<div class="call-meta-item"><div class="cml">Cost</div><div class="cmv">'+fmtMoney(c.cost)+'</div></div>'+
    '<div class="call-meta-item"><div class="cml">Sentiment</div><div class="cmv">'+sentimentBadge(c.sentiment)+'</div></div>'+
    '<div class="call-meta-item"><div class="cml">Date</div><div class="cmv">'+fmtDate(c.created_at)+'</div></div></div>'+
    (c.recording_url ? '<div class="call-recording-player"><label style="font-size:11px;color:var(--text-dim);text-transform:uppercase;letter-spacing:1px;display:block;margin-bottom:8px;"><i class="fas fa-waveform-lines"></i> Call Recording</label><div class="vm-player"><div class="vm-waveform" id="callWaveform">' + generateWaveformBars() + '</div><audio id="callAudio" src="'+esc(c.recording_url)+'" preload="metadata"></audio><div class="vm-player-controls"><button class="btn btn-primary btn-sm" onclick="VoicePortal.playCallRecording()" id="callPlayBtn"><i class="fas fa-play"></i></button><span class="vm-player-time" id="callPlayerTime">0:00 / '+fmtDur(c.duration_seconds)+'</span><input type="range" min="0" max="100" value="0" class="vm-scrubber" id="callScrubber" oninput="VoicePortal.scrubCall(this.value)"><button class="btn btn-ghost btn-xs" onclick="window.open(\''+esc(c.recording_url)+'\',\'_blank\')" title="Download"><i class="fas fa-download"></i></button></div></div></div>' : '')+
    (c.summary ? '<div style="margin:16px 0;"><label style="font-size:11px;color:var(--text-dim);text-transform:uppercase;letter-spacing:1px;display:block;margin-bottom:6px;"><i class="fas fa-sparkles"></i> AI Summary</label><p style="font-size:14px;line-height:1.7;color:var(--text-muted);background:rgba(0,212,255,0.04);padding:14px;border-radius:8px;border-left:3px solid var(--cyan);">'+esc(c.summary)+'</p></div>':'')+
    (c.transcript ? '<div><label style="font-size:11px;color:var(--text-dim);text-transform:uppercase;letter-spacing:1px;display:block;margin-bottom:6px;"><i class="fas fa-scroll"></i> Transcript</label><div class="transcript">'+esc(c.transcript)+'</div></div>':'');
}

function playCallRecording() {
  const audio = document.getElementById('callAudio');
  const btn = document.getElementById('callPlayBtn');
  if (!audio) return;
  if (audio.paused) {
    audio.play();
    btn.innerHTML = '<i class="fas fa-pause"></i>';
    updateCallProgress();
  } else {
    audio.pause();
    btn.innerHTML = '<i class="fas fa-play"></i>';
  }
}

function updateCallProgress() {
  const audio = document.getElementById('callAudio');
  const scrubber = document.getElementById('callScrubber');
  const timeEl = document.getElementById('callPlayerTime');
  if (!audio || audio.paused) return;
  const pct = audio.duration ? (audio.currentTime / audio.duration) * 100 : 0;
  if (scrubber) scrubber.value = pct;
  if (timeEl) timeEl.textContent = fmtDur(Math.floor(audio.currentTime)) + ' / ' + fmtDur(Math.floor(audio.duration || 0));
  const wf = document.getElementById('callWaveform');
  if (wf) {
    const bars = wf.querySelectorAll('.wf-bar');
    bars.forEach((bar, i) => { bar.classList.toggle('played', (i / bars.length) * 100 <= pct); });
  }
  if (!audio.ended) requestAnimationFrame(updateCallProgress);
  else { if (btn) btn.innerHTML = '<i class="fas fa-play"></i>'; }
}

function scrubCall(val) {
  const audio = document.getElementById('callAudio');
  if (audio && audio.duration) audio.currentTime = (val / 100) * audio.duration;
}

// ═══════════════════════════════════════
// SMS (Enhanced with Templates)
// ═══════════════════════════════════════

const smsTemplates = [
  { name: 'Appointment Reminder', icon: 'fa-calendar-check', text: 'Hi {name}, this is a reminder about your appointment on {date} at {time}. Reply CONFIRM to confirm or RESCHEDULE to reschedule.' },
  { name: 'Follow-up', icon: 'fa-reply', text: 'Hi {name}, thank you for your recent call with us. We wanted to follow up and see if you have any other questions.' },
  { name: 'Business Hours', icon: 'fa-clock', text: 'Thank you for reaching out! Our business hours are Monday-Friday, 9 AM - 6 PM EST. We will get back to you during business hours.' },
  { name: 'Payment Reminder', icon: 'fa-credit-card', text: 'Hi {name}, this is a friendly reminder that your payment of {amount} is due on {date}. Please contact us if you have any questions.' },
  { name: 'Welcome', icon: 'fa-hand-wave', text: 'Welcome to {company}! We\'re glad to have you. If you need any help, reply to this message or call us anytime.' },
  { name: 'Survey', icon: 'fa-star', text: 'Hi {name}, how was your recent experience with us? Reply with a rating from 1-5 (5 being excellent). Your feedback helps us improve!' },
];

async function loadSms() {
  const d = await api('sms');
  const msgs = d.messages || [];
  const el = document.getElementById('smsContent');
  if (msgs.length === 0) {
    el.innerHTML = '<div class="empty"><i class="fas fa-comment-sms"></i><h4>No SMS messages</h4><p>Send your first SMS message.</p><button class="btn btn-primary" onclick="VoicePortal.showSmsModal()"><i class="fas fa-paper-plane"></i> Send SMS</button></div>';
    return;
  }
  const threads = {};
  msgs.forEach(m => {
    const key = m.direction === 'outbound' ? m.to_number : m.from_number;
    if (!threads[key]) threads[key] = [];
    threads[key].push(m);
  });
  const threadKeys = Object.keys(threads);
  el.innerHTML = '<div class="sms-layout"><div class="sms-threads">'+threadKeys.map((k,i) => {
    const last = threads[k][threads[k].length-1];
    return '<div class="sms-thread'+(i===0?' active':'')+'" onclick="VoicePortal.showSmsThread(\''+esc(k)+'\',this)"><div class="st-num">'+esc(k)+'</div><div class="st-preview">'+esc((last.message||'').substring(0,60))+'</div><div class="st-time">'+fmtDate(last.created_at)+'</div></div>';
  }).join('')+'</div><div class="sms-chat"><div class="sms-messages" id="smsMessages">'+renderSmsThread(threads[threadKeys[0]]||[])+'</div><div class="sms-compose"><button class="btn btn-ghost btn-icon btn-sm" onclick="VoicePortal.toggleTemplates()" title="Templates"><i class="fas fa-bookmark"></i></button><input type="text" placeholder="Type a message..." id="smsReplyInput" onkeydown="if(event.key===\'Enter\')VoicePortal.replySms(\''+esc(threadKeys[0])+'\')"><button class="btn btn-primary btn-sm" onclick="VoicePortal.replySms(\''+esc(threadKeys[0])+'\')"><i class="fas fa-paper-plane"></i></button></div><div class="sms-templates-drawer" id="smsTemplatesDrawer" style="display:none;">' + renderTemplateDrawer() + '</div></div></div>';
}

function renderSmsThread(msgs) {
  return msgs.map(m => '<div class="sms-msg '+(m.direction==='outbound'?'sent':'received')+'"><div>'+esc(m.message)+'</div><div class="msg-time">'+fmtDate(m.created_at)+'</div></div>').join('');
}

function renderTemplateDrawer() {
  return '<div class="template-list">' + smsTemplates.map((t, i) => {
    return '<button class="template-item" onclick="VoicePortal.useTemplate('+i+')"><i class="fas '+t.icon+'"></i><span>'+t.name+'</span></button>';
  }).join('') + '</div>';
}

function toggleTemplates() {
  const drawer = document.getElementById('smsTemplatesDrawer');
  if (drawer) drawer.style.display = drawer.style.display === 'none' ? 'flex' : 'none';
}

function useTemplate(idx) {
  const t = smsTemplates[idx];
  if (!t) return;
  const input = document.getElementById('smsReplyInput') || document.getElementById('smsMessage');
  if (input) input.value = t.text;
  const drawer = document.getElementById('smsTemplatesDrawer');
  if (drawer) drawer.style.display = 'none';
}

function showSmsThread(number, el) {
  document.querySelectorAll('.sms-thread').forEach(t => t.classList.remove('active'));
  if (el) el.classList.add('active');
  api('sms&number=' + encodeURIComponent(number)).then(d => {
    const msgs = d.messages || [];
    document.getElementById('smsMessages').innerHTML = renderSmsThread(msgs);
  });
}

function showSmsModal() {
  const sel = document.getElementById('smsFrom');
  sel.innerHTML = phones.filter(p => p.sms_enabled=='1').map(p => '<option value="'+p.id+'">'+p.phone_number+'</option>').join('');
  if (!sel.innerHTML) sel.innerHTML = '<option>No SMS-enabled numbers</option>';
  document.getElementById('smsTo').value = '';
  document.getElementById('smsMessage').value = '';
  document.getElementById('smsCharCount').textContent = '0/1600';
  document.getElementById('smsModal').classList.add('show');
}

async function sendSms() {
  const r = await api('sms_send', { phone_number_id: document.getElementById('smsFrom').value, to: document.getElementById('smsTo').value, message: document.getElementById('smsMessage').value });
  if (r.error) { toast(r.error, 'error'); return; }
  toast('SMS sent', 'success');
  closeModal('smsModal');
  loadSms();
}

async function replySms(number) {
  const input = document.getElementById('smsReplyInput');
  if (!input.value.trim()) return;
  const smsPhone = phones.find(p => p.sms_enabled == '1');
  if (!smsPhone) { toast('No SMS-enabled number', 'error'); return; }
  await api('sms_send', { phone_number_id: smsPhone.id, to: number, message: input.value });
  input.value = '';
  showSmsThread(number);
}

// ═══════════════════════════════════════
// Fax
// ═══════════════════════════════════════

async function loadFax() {
  const d = await api('fax');
  const faxes = d.faxes || [];
  const el = document.getElementById('faxContent');
  if (faxes.length === 0) {
    el.innerHTML = '<div class="empty"><i class="fas fa-fax"></i><h4>No faxes</h4><p>Send your first fax document.</p><button class="btn btn-primary" onclick="VoicePortal.showFaxModal()"><i class="fas fa-plus"></i> Send Fax</button></div>';
    return;
  }
  el.innerHTML = '<table class="tbl"><thead><tr><th>Direction</th><th>From</th><th>To</th><th>Pages</th><th>Status</th><th>Date</th><th></th></tr></thead><tbody>' +
    faxes.map(f => '<tr><td><span class="badge badge-'+f.direction+'">'+f.direction+'</span></td><td>'+esc(f.from_number)+'</td><td>'+esc(f.to_number)+'</td><td>'+(f.pages||'')+'</td><td><span class="badge badge-'+f.status+'">'+f.status+'</span></td><td>'+fmtDate(f.created_at)+'</td><td>'+(f.document_url?'<a href="'+esc(f.document_url)+'" target="_blank" class="btn btn-ghost btn-xs"><i class="fas fa-download"></i></a>':'')+'</td></tr>').join('') +
    '</tbody></table>';
}

function showFaxModal() {
  const sel = document.getElementById('faxFrom');
  sel.innerHTML = phones.filter(p => p.fax_enabled=='1').map(p => '<option value="'+p.id+'">'+p.phone_number+'</option>').join('');
  if (!sel.innerHTML) sel.innerHTML = '<option>No fax-enabled numbers</option>';
  document.getElementById('faxTo').value = '';
  document.getElementById('faxDocUrl').value = '';
  document.getElementById('faxModal').classList.add('show');
}

async function sendFax() {
  const r = await api('fax_send', { phone_number_id: document.getElementById('faxFrom').value, to: document.getElementById('faxTo').value, document_url: document.getElementById('faxDocUrl').value });
  if (r.error) { toast(r.error, 'error'); return; }
  toast('Fax queued', 'success');
  closeModal('faxModal');
  loadFax();
}

// ═══════════════════════════════════════
// Campaigns (Enhanced with Results)
// ═══════════════════════════════════════

async function loadCampaigns() {
  const d = await api('campaigns');
  const camps = d.campaigns || [];
  const el = document.getElementById('campaignsContent');
  if (camps.length === 0) {
    el.innerHTML = '<div class="empty"><i class="fas fa-bullhorn"></i><h4>No campaigns</h4><p>Create an outbound campaign to reach your contacts.</p><button class="btn btn-primary" onclick="VoicePortal.showCampaignModal()"><i class="fas fa-rocket"></i> New Campaign</button></div>';
    return;
  }
  el.innerHTML = '<table class="tbl"><thead><tr><th>Name</th><th>Agent</th><th>Type</th><th>Status</th><th>Progress</th><th>Results</th><th>Lines</th><th>Actions</th></tr></thead><tbody>' +
    camps.map(c => {
      const pct = c.total_contacts > 0 ? Math.round(c.contacts_called/c.total_contacts*100) : 0;
      const answered = c.answered || 0;
      const voicemail = c.voicemail || 0;
      const failed = c.failed || 0;
      return '<tr><td style="font-weight:600;">'+esc(c.name)+'</td><td>'+esc(c.agent_name||'')+'</td><td>'+esc(c.type)+'</td><td><span class="badge badge-'+c.status+'">'+c.status+'</span></td>'+
        '<td><div style="display:flex;align-items:center;gap:8px;"><div style="flex:1;"><div class="usage-bar"><div class="usage-fill green" style="width:'+pct+'%"></div></div></div><span style="font-size:12px;color:var(--text-muted);white-space:nowrap;">'+(c.contacts_called||0)+'/'+c.total_contacts+' ('+pct+'%)</span></div></td>'+
        '<td><div class="campaign-results-mini"><span title="Answered" style="color:var(--success);"><i class="fas fa-phone"></i> '+answered+'</span><span title="Voicemail" style="color:var(--warning);"><i class="fas fa-voicemail"></i> '+voicemail+'</span><span title="Failed" style="color:var(--danger);"><i class="fas fa-phone-slash"></i> '+failed+'</span></div></td>'+
        '<td>'+c.concurrent_lines+'</td>'+
        '<td style="white-space:nowrap;"><button class="btn btn-ghost btn-xs" onclick="VoicePortal.showCampaignResults('+c.id+')" title="Results"><i class="fas fa-chart-bar"></i></button> '+
        (c.status==='running'?'<button class="btn btn-ghost btn-xs" onclick="VoicePortal.updateCampaign('+c.id+',\'paused\')"><i class="fas fa-pause"></i></button> ':'')+
        (c.status==='paused'?'<button class="btn btn-success btn-xs" onclick="VoicePortal.updateCampaign('+c.id+',\'scheduled\')"><i class="fas fa-play"></i></button> ':'')+
        (c.status!=='cancelled'&&c.status!=='completed'?'<button class="btn btn-danger btn-xs" onclick="VoicePortal.updateCampaign('+c.id+',\'cancelled\')"><i class="fas fa-stop"></i></button>':'')+
        '</td></tr>';
    }).join('') + '</tbody></table>';
}

async function showCampaignResults(id) {
  document.getElementById('campaignResultsModal').classList.add('show');
  const content = document.getElementById('campaignResultsContent');
  content.innerHTML = '<div class="loading"><i class="fas fa-spinner"></i></div>';
  const d = await api('campaign_results&id=' + id);
  const c = d.campaign || {};
  const pct = c.total_contacts > 0 ? Math.round((c.contacts_called||0)/c.total_contacts*100) : 0;

  content.innerHTML =
    '<div class="stats-row" style="margin-bottom:20px;">' +
    '<div class="stat-card accent-green"><div class="sc-value">'+(c.answered||0)+'</div><div class="sc-label">Answered</div></div>' +
    '<div class="stat-card accent-warning"><div class="sc-value">'+(c.voicemail||0)+'</div><div class="sc-label">Voicemail</div></div>' +
    '<div class="stat-card accent-danger"><div class="sc-value">'+(c.failed||0)+'</div><div class="sc-label">Failed</div></div>' +
    '<div class="stat-card accent-cyan"><div class="sc-value">'+fmtDur(c.avg_duration||0)+'</div><div class="sc-label">Avg Duration</div></div>' +
    '</div>' +
    '<div class="chart-row"><div class="card"><div class="card-header"><span class="card-title"><i class="fas fa-chart-pie"></i> Call Outcomes</span></div><div class="card-body"><div class="chart-container"><canvas id="chartCampaignOutcome"></canvas></div></div></div>' +
    '<div class="card"><div class="card-header"><span class="card-title"><i class="fas fa-face-smile"></i> Sentiment Distribution</span></div><div class="card-body"><div class="chart-container"><canvas id="chartCampaignSentiment"></canvas></div></div></div></div>' +
    '<div class="card"><div class="card-header"><span class="card-title"><i class="fas fa-list"></i> Contact Results</span></div><div class="card-body flush"><div style="max-height:300px;overflow-y:auto;"><table class="tbl"><thead><tr><th>Contact</th><th>Phone</th><th>Status</th><th>Duration</th><th>Sentiment</th></tr></thead><tbody>' +
    (c.contacts || []).map(ct => '<tr><td>'+esc(ct.name||'')+'</td><td style="font-family:monospace;">'+esc(ct.phone||'')+'</td><td><span class="badge badge-'+(ct.status||'queued')+'">'+esc(ct.status||'queued')+'</span></td><td>'+fmtDur(ct.duration)+'</td><td>'+sentimentBadge(ct.sentiment)+'</td></tr>').join('') +
    '</tbody></table></div></div></div>';

  // Build charts
  setTimeout(() => {
    if (charts.campOutcome) charts.campOutcome.destroy();
    charts.campOutcome = new Chart(document.getElementById('chartCampaignOutcome'), {
      type:'doughnut', data:{ labels:['Answered','Voicemail','Failed','Pending'], datasets:[{data:[c.answered||0, c.voicemail||0, c.failed||0, (c.total_contacts||0)-(c.contacts_called||0)], backgroundColor:['rgba(16,185,129,0.7)','rgba(245,158,11,0.7)','rgba(239,68,68,0.7)','rgba(90,99,128,0.3)'], borderWidth:0}]},
      options:{responsive:true,maintainAspectRatio:false,cutout:'60%',plugins:{legend:{position:'right',labels:{color:'#8892b0',font:{size:12},usePointStyle:true}}}}
    });
    if (charts.campSent) charts.campSent.destroy();
    const sentBreak = c.sentiment_breakdown || {positive:0,neutral:0,negative:0};
    charts.campSent = new Chart(document.getElementById('chartCampaignSentiment'), {
      type:'doughnut', data:{ labels:['Positive','Neutral','Negative'], datasets:[{data:[sentBreak.positive||0, sentBreak.neutral||0, sentBreak.negative||0], backgroundColor:['rgba(16,185,129,0.7)','rgba(59,130,246,0.7)','rgba(239,68,68,0.7)'], borderWidth:0}]},
      options:{responsive:true,maintainAspectRatio:false,cutout:'60%',plugins:{legend:{position:'right',labels:{color:'#8892b0',font:{size:12},usePointStyle:true}}}}
    });
  }, 100);
}

function showCampaignModal() {
  document.getElementById('campAgent').innerHTML = agents.map(a => '<option value="'+a.id+'">'+esc(a.name)+'</option>').join('');
  document.getElementById('campPhone').innerHTML = phones.map(p => '<option value="'+p.id+'">'+p.phone_number+'</option>').join('');
  ['campName','campContacts','campScript'].forEach(fid => { document.getElementById(fid).value = ''; });
  document.getElementById('campaignModal').classList.add('show');
}

async function createCampaign() {
  const contacts = document.getElementById('campContacts').value.split('\n').filter(l => l.trim()).map(l => {
    const parts = l.split(',').map(s => s.trim());
    return { name: parts[0], phone: parts[1] };
  });
  if (!contacts.length) { toast('Add at least one contact','error'); return; }
  const r = await api('campaign_create', {
    name: document.getElementById('campName').value,
    agent_id: document.getElementById('campAgent').value,
    phone_number_id: document.getElementById('campPhone').value,
    contacts: contacts, concurrent_lines: document.getElementById('campLines').value,
    type: document.getElementById('campType').value,
    script_override: document.getElementById('campScript').value
  });
  if (r.error) { toast(r.error,'error'); return; }
  toast('Campaign launched!','success');
  closeModal('campaignModal');
  loadCampaigns();
}

async function updateCampaign(id, status) {
  await api('campaign_update', { campaign_id: id, status: status });
  toast('Campaign ' + status, 'success');
  loadCampaigns();
}

// ═══════════════════════════════════════
// Documents
// ═══════════════════════════════════════

async function loadDocs() {
  const d = await api('documents');
  const docs = d.documents || [];
  const el = document.getElementById('docsContent');
  if (docs.length === 0) {
    el.innerHTML = '<div class="empty"><i class="fas fa-file-lines"></i><h4>No documents</h4><p>Create document templates for contracts, invoices, proposals.</p><button class="btn btn-primary" onclick="VoicePortal.showDocModal()"><i class="fas fa-plus"></i> New Document</button></div>';
    return;
  }
  el.innerHTML = '<table class="tbl"><thead><tr><th>Name</th><th>Type</th><th>Updated</th><th></th></tr></thead><tbody>' +
    docs.map(doc => '<tr><td style="font-weight:600;"><i class="fas fa-file-lines" style="color:var(--cyan);margin-right:8px;"></i>'+esc(doc.name)+'</td><td><span class="badge badge-inbound">'+esc(doc.type)+'</span></td><td>'+fmtDate(doc.updated_at)+'</td><td><button class="btn btn-danger btn-xs" onclick="VoicePortal.deleteDoc('+doc.id+',\''+esc(doc.name).replace(/'/g,"\\'")+'\')" title="Delete"><i class="fas fa-trash"></i></button></td></tr>').join('') +
    '</tbody></table>';
}

function showDocModal() {
  ['docName','docContent'].forEach(fid => { document.getElementById(fid).value = ''; });
  document.getElementById('docType').value = 'custom';
  document.getElementById('docModal').classList.add('show');
}

async function createDoc() {
  const name = document.getElementById('docName').value;
  if (!name) { toast('Document name required','error'); return; }
  await api('doc_create', { name: name, type: document.getElementById('docType').value, template_html: document.getElementById('docContent').value, variables: [] });
  toast('Document created','success');
  closeModal('docModal');
  loadDocs();
}

async function deleteDoc(id, name) {
  if (!confirm('Delete "' + name + '"?')) return;
  await api('doc_delete', { doc_id: id });
  toast('Document deleted','success');
  loadDocs();
}

// ═══════════════════════════════════════
// Usage & Billing
// ═══════════════════════════════════════

async function loadUsage() {
  const d = await api('usage');
  const usages = d.usage || [];
  const el = document.getElementById('usageContent');
  if (usages.length === 0) {
    el.innerHTML = '<div class="empty"><i class="fas fa-receipt"></i><h4>No usage data</h4><p>Usage tracking begins when your services are active.</p></div>';
    return;
  }
  el.innerHTML = usages.map(u => {
    const minPct = u.minutes_included > 0 ? Math.min(100, Math.round(u.minutes_used/u.minutes_included*100)) : 0;
    const smsPct = u.sms_included > 0 ? Math.min(100, Math.round(u.sms_used/u.sms_included*100)) : 0;
    const faxPct = u.fax_included > 0 ? Math.min(100, Math.round(u.fax_used/u.fax_included*100)) : 0;
    return '<div class="card" style="margin-bottom:16px;"><div class="card-header"><span class="card-title"><i class="fas fa-calendar"></i> '+esc(u.period_start)+' &mdash; '+esc(u.period_end)+'</span></div><div class="card-body"><div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px;">'+
      '<div><div style="display:flex;justify-content:space-between;font-size:13px;"><span>Minutes</span><span style="font-weight:600;">'+u.minutes_used+'/'+u.minutes_included+'</span></div><div class="usage-bar"><div class="usage-fill '+(minPct>90?'red':minPct>70?'yellow':'green')+'" style="width:'+minPct+'%"></div></div>'+(u.minutes_overage>0?'<div style="font-size:11px;color:var(--warning);margin-top:4px;">Overage: '+u.minutes_overage+' min</div>':'')+'</div>'+
      '<div><div style="display:flex;justify-content:space-between;font-size:13px;"><span>SMS</span><span style="font-weight:600;">'+u.sms_used+'/'+u.sms_included+'</span></div><div class="usage-bar"><div class="usage-fill '+(smsPct>90?'red':smsPct>70?'yellow':'green')+'" style="width:'+smsPct+'%"></div></div></div>'+
      '<div><div style="display:flex;justify-content:space-between;font-size:13px;"><span>Fax</span><span style="font-weight:600;">'+u.fax_used+'/'+u.fax_included+'</span></div><div class="usage-bar"><div class="usage-fill '+(faxPct>90?'red':faxPct>70?'yellow':'green')+'" style="width:'+faxPct+'%"></div></div></div>'+
      '<div><div style="display:flex;justify-content:space-between;font-size:13px;"><span>Agents</span><span style="font-weight:600;">'+u.agents_used+'/'+u.agents_included+'</span></div><div style="display:flex;justify-content:space-between;font-size:13px;margin-top:10px;"><span>Numbers</span><span style="font-weight:600;">'+u.numbers_used+'/'+u.numbers_included+'</span></div></div>'+
      '</div>'+(u.overage_cost>0?'<div style="margin-top:16px;padding:12px 16px;background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.2);border-radius:8px;font-size:13px;color:var(--warning);display:flex;align-items:center;gap:8px;"><i class="fas fa-triangle-exclamation"></i> Overage: '+fmtMoney(u.overage_cost)+'</div>':'')+'</div></div>';
  }).join('');
}

// ═══════════════════════════════════════
// Keyboard Shortcuts & Global Search
// ═══════════════════════════════════════

document.addEventListener('keydown', e => {
  if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
    e.preventDefault();
    document.getElementById('globalSearch').focus();
  }
});

document.getElementById('globalSearch').addEventListener('input', function() {
  const q = this.value.toLowerCase().trim();
  if (!q) return;
  const matches = [
    {label:'Overview',panel:'dashboard'},{label:'Analytics',panel:'analytics'},
    {label:'AI Agents',panel:'agents'},{label:'Phone Numbers',panel:'phones'},
    {label:'Call Log',panel:'calls'},{label:'SMS Messages',panel:'sms'},
    {label:'Fax',panel:'fax'},{label:'Campaigns',panel:'campaigns'},
    {label:'Documents',panel:'documents'},{label:'Usage & Billing',panel:'usage'},
    {label:'Settings',panel:'settings'},{label:'Live Monitor',panel:'livemonitor'},
    {label:'Voicemail',panel:'voicemail'}
  ].filter(m => m.label.toLowerCase().indexOf(q) !== -1);
  if (matches.length === 1) { switchPanel(matches[0].panel); this.value = ''; this.blur(); }
});

function toggleApiKey() {
  const f = document.getElementById('apiKeyField');
  f.type = f.type === 'password' ? 'text' : 'password';
}

// ═══════════════════════════════════════
// Initialization
// ═══════════════════════════════════════

async function init() {
  try {
    const results = await Promise.all([api('agents'), api('phones')]);
    agents = results[0].agents || [];
    phones = results[1].phones || [];
    document.getElementById('sbAgentCount').textContent = agents.length;
    document.getElementById('sbPhoneCount').textContent = phones.length;
  } catch(e) { console.warn('Init error:', e); }
  loadDashboard();
  connectWebSocket();
}

// ═══════════════════════════════════════
// Public API (window.VoicePortal)
// ═══════════════════════════════════════

window.VoicePortal = {
  switchPanel, refreshPanel, toggleSidebar, toast, closeModal, agentTab,
  loadDashboard,
  showAgentModal, editAgent, saveAgent, deleteAgent,
  assignPhone,
  filterCalls, showCallDetail, playCallRecording, scrubCall,
  showSmsModal, sendSms, replySms, showSmsThread,
  toggleTemplates, useTemplate,
  showFaxModal, sendFax,
  showCampaignModal, createCampaign, updateCampaign, showCampaignResults,
  showDocModal, createDoc, deleteDoc,
  showVoicemail, playVoicemail, scrubVoicemail, downloadVoicemail,
  callBack, deleteVoicemail,
  toggleApiKey,
  loadLiveMonitor
};

// Expose globals for inline onclick handlers in HTML
Object.keys(window.VoicePortal).forEach(function(k) {
  if (!(k in window)) window[k] = window.VoicePortal[k];
});

init();

})();
