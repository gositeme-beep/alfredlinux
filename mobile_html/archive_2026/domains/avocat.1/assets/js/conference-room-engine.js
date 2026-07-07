(() => {
'use strict';

const $ = id => document.getElementById(id);
const API = '/api/conference.php';

// LiveKit state
let lkRoom = null;
let roomActive = false;
let timerSeconds = 0;
let timerInterval = null;
let localAudioTrack = null;
let localVideoTrack = null;
let micEnabled = true;
let camEnabled = false;
const participantColors = {};
const colorPool = ['#6c5ce7','#00e676','#448aff','#ff9100','#18ffff','#ff5252','#ffd600','#a29bfe','#00bcd4','#e040fb','#76ff03','#ffab40'];
let colorIdx = 0;

const esc = s => String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');

function getColor(identity) {
  if (!participantColors[identity]) {
    participantColors[identity] = colorPool[colorIdx % colorPool.length];
    colorIdx++;
  }
  return participantColors[identity];
}

function initials(name) {
  return name.split(/\s+/).map(w => w[0]).join('').toUpperCase().slice(0,2);
}

// ---- Timer ----
function startTimer() {
  timerSeconds = 0;
  timerInterval = setInterval(() => {
    timerSeconds++;
    const h = String(Math.floor(timerSeconds/3600)).padStart(2,'0');
    const m = String(Math.floor((timerSeconds%3600)/60)).padStart(2,'0');
    const s = String(timerSeconds%60).padStart(2,'0');
    $('timerDisplay').textContent = `${h}:${m}:${s}`;
  }, 1000);
}

// ---- Copy Room Code ----
window.copyRoomCode = function() {
  const code = $('roomCode').textContent;
  navigator.clipboard.writeText(code).catch(()=>{});
  const toast = $('copyToast');
  toast.classList.add('show');
  setTimeout(()=>toast.classList.remove('show'), 1500);
};

// ---- API helper ----
async function apiCall(action, body) {
  const opts = { credentials: 'same-origin' };
  let url = `${API}?action=${action}`;
  if (body) {
    opts.method = 'POST';
    opts.headers = { 'Content-Type': 'application/json', 'X-CSRF-Token': window.AW_CSRF_TOKEN || '' };
    opts.body = JSON.stringify(body);
  }
  const resp = await fetch(url, opts);
  return resp.json();
}

// ---- Create Room ----
window.createRoom = async function(e) {
  e.preventDefault();
  const topic = $('roomTopic').value.trim();
  const max = parseInt($('maxParticipants').value);
  const agenda = $('agendaItems').value.trim();
  if (!topic) return false;

  const submitBtn = e.target.querySelector('button[type="submit"]');
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';

  try {
    // Generate room name from topic
    const roomSlug = topic.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'').slice(0,40);
    const roomName = roomSlug + '-' + Date.now().toString(36);

    const data = await apiCall('create_room', {
      name: roomName,
      max_participants: max,
      description: topic + (agenda ? '\n\nAgenda:\n' + agenda : '')
    });

    if (!data.success) {
      alert(data.error || 'Failed to create room');
      return false;
    }

    $('roomCode').textContent = roomName;

    // Connect to LiveKit
    await connectLiveKit(data.ws_url, data.token, roomName);

    // Show panels, hide creation
    $('createPanel').style.display = 'none';
    $('participantsPanel').style.display = '';
    $('controlsBar').style.display = '';
    $('transcriptPanel').style.display = '';
    $('summaryPanel').style.display = '';

    roomActive = true;
    startTimer();
    addTranscriptLine('System', 'Conference room created. Waiting for participants...', '#888');

  } catch (err) {
    console.error('[Conference] Create error:', err);
    alert('Failed to create conference room. Please try again.');
  } finally {
    submitBtn.disabled = false;
    submitBtn.innerHTML = '<i class="fas fa-video"></i> Start Conference';
  }
  return false;
};

// ---- Connect to LiveKit ----
async function connectLiveKit(wsUrl, token, roomName) {
  const LivekitClient = window.LivekitClient;
  if (!LivekitClient) {
    console.error('[Conference] LiveKit SDK not loaded');
    addTranscriptLine('System', 'LiveKit SDK not available — running in limited mode.', '#ff5252');
    return;
  }

  lkRoom = new LivekitClient.Room({
    adaptiveStream: true,
    dynacast: true,
    audioCaptureDefaults: { echoCancellation: true, noiseSuppression: true },
  });

  // Participant events
  lkRoom.on(LivekitClient.RoomEvent.ParticipantConnected, (participant) => {
    addTranscriptLine('System', `${participant.identity} joined the conference`, '#00e676');
    renderParticipants();
  });

  lkRoom.on(LivekitClient.RoomEvent.ParticipantDisconnected, (participant) => {
    addTranscriptLine('System', `${participant.identity} left the conference`, '#ff5252');
    renderParticipants();
  });

  // Speaking detection
  lkRoom.on(LivekitClient.RoomEvent.ActiveSpeakersChanged, (speakers) => {
    const cards = document.querySelectorAll('.participant');
    cards.forEach(c => {
      c.classList.remove('speaking');
      c.querySelector('.speak-bars')?.classList.add('silent');
    });
    speakers.forEach(s => {
      const card = document.querySelector(`.participant[data-identity="${s.identity}"]`);
      if (card) {
        card.classList.add('speaking');
        card.querySelector('.speak-bars')?.classList.remove('silent');
      }
    });
  });

  lkRoom.on(LivekitClient.RoomEvent.Disconnected, (reason) => {
    addTranscriptLine('System', `Disconnected: ${reason || 'unknown reason'}`, '#ff5252');
  });

  // Data channel for transcript/chat
  lkRoom.on(LivekitClient.RoomEvent.DataReceived, (payload, participant) => {
    try {
      const msg = JSON.parse(new TextDecoder().decode(payload));
      if (msg.type === 'transcript') {
        const name = participant?.identity || msg.speaker || 'Unknown';
        addTranscriptLine(name, msg.text, getColor(name));
      } else if (msg.type === 'summary') {
        $('summaryText').textContent = msg.text;
      } else if (msg.type === 'action_item') {
        const li = document.createElement('li');
        li.innerHTML = `<i class="fas fa-circle-check"></i> ${esc(msg.text)}`;
        $('actionItems').appendChild(li);
      }
    } catch (e) { /* ignore non-JSON data */ }
  });

  try {
    await lkRoom.connect(wsUrl, token);
    console.log('[Conference] Connected to LiveKit room:', roomName);

    // Publish local microphone
    localAudioTrack = await LivekitClient.createLocalAudioTrack();
    await lkRoom.localParticipant.publishTrack(localAudioTrack);
    micEnabled = true;

    renderParticipants();
    addTranscriptLine('System', 'Connected to conference. Your microphone is active.', '#00e676');
  } catch (err) {
    console.error('[Conference] LiveKit connect error:', err);
    addTranscriptLine('System', 'Could not connect to LiveKit: ' + err.message, '#ff5252');
  }
}

// ---- Render Participants ----
function renderParticipants() {
  const grid = $('participantsGrid');
  if (!lkRoom) { grid.innerHTML = '<p style="color:var(--text2)">No active connection</p>'; return; }

  const all = [lkRoom.localParticipant, ...lkRoom.remoteParticipants.values()];
  $('participantsPanel').querySelector('.badge').innerHTML =
    `<i class="fas fa-circle" style="font-size:.4rem;vertical-align:middle;margin-right:.2rem"></i> ${all.length} LIVE`;

  grid.innerHTML = all.map(p => {
    const name = p.identity || 'Unknown';
    const color = getColor(name);
    const isSpeaking = p.isSpeaking;
    const isMuted = !p.isMicrophoneEnabled;
    const isLocal = p === lkRoom.localParticipant;
    return `<div class="participant${isSpeaking?' speaking':''}" data-identity="${esc(name)}">
      <span class="participant-status ${isMuted?'muted':'online'}"></span>
      <div class="avatar" style="background:${color}22;color:${color}">
        <div class="avatar-ring"></div>
        ${initials(name)}
      </div>
      <div class="participant-name">${esc(name)}${isLocal?' (You)':''}</div>
      <div class="participant-role">${isLocal?'Host':''}</div>
      <div class="speak-bars${isSpeaking?'':' silent'}">
        <span></span><span></span><span></span><span></span>
      </div>
    </div>`;
  }).join('');
}

// ---- Transcript ----
function addTranscriptLine(speaker, text, color) {
  const feed = $('transcriptFeed');
  const div = document.createElement('div');
  div.className = 'transcript-item';
  const now = new Date();
  const time = now.toLocaleTimeString('en',{hour12:false,hour:'2-digit',minute:'2-digit',second:'2-digit'});
  const c = color || getColor(speaker);

  div.innerHTML = `<div class="transcript-avatar" style="background:${c}22;color:${c}">${initials(speaker)}</div>
    <div class="transcript-body">
      <div class="transcript-name" style="color:${c}">${esc(speaker)}</div>
      <div class="transcript-text">${esc(text)}</div>
      <div class="transcript-time">${time}</div>
    </div>`;

  feed.appendChild(div);
  feed.scrollTop = feed.scrollHeight;
}

// ---- Control Buttons ----
window.toggleControl = async function(btn, iconOn, iconOff) {
  btn.classList.toggle('active');
  const id = btn.id;

  if (id === 'btnMic' && lkRoom) {
    micEnabled = btn.classList.contains('active');
    await lkRoom.localParticipant.setMicrophoneEnabled(micEnabled);
  } else if (id === 'btnCam' && lkRoom) {
    camEnabled = btn.classList.contains('active');
    await lkRoom.localParticipant.setCameraEnabled(camEnabled);
  } else if (id === 'btnScreen' && lkRoom) {
    if (btn.classList.contains('active')) {
      try { await lkRoom.localParticipant.setScreenShareEnabled(true); }
      catch(e) { btn.classList.remove('active'); }
    } else {
      await lkRoom.localParticipant.setScreenShareEnabled(false);
    }
  }

  if (iconOn && iconOff) {
    const icon = btn.querySelector('i');
    icon.className = 'fas ' + (btn.classList.contains('active') ? iconOn : iconOff);
  }
};

window.leaveRoom = async function() {
  if (!confirm('Leave the conference room?')) return;
  roomActive = false;
  clearInterval(timerInterval);

  if (lkRoom) {
    try { await lkRoom.disconnect(); } catch(e) {}
    lkRoom = null;
  }

  $('createPanel').style.display = '';
  $('participantsPanel').style.display = 'none';
  $('controlsBar').style.display = 'none';
  $('transcriptPanel').style.display = 'none';
  $('summaryPanel').style.display = 'none';

  $('timerDisplay').textContent = '00:00:00';
  $('transcriptFeed').innerHTML = '';
  $('participantsGrid').innerHTML = '';
  $('roomForm').reset();
};

/* ========== Join Room via URL Parameter ========== */
(async () => {
  const params = new URLSearchParams(location.search);
  const joinRoom = params.get('room');
  if (joinRoom) {
    try {
      const data = await apiCall('join_room', { room: joinRoom, display_name: window._confClientName || 'Guest' });
      if (data.success) {
        $('roomCode').textContent = joinRoom;
        $('createPanel').style.display = 'none';
        $('participantsPanel').style.display = '';
        $('controlsBar').style.display = '';
        $('transcriptPanel').style.display = '';
        $('summaryPanel').style.display = '';
        roomActive = true;
        startTimer();
        await connectLiveKit(data.ws_url, data.token, joinRoom);
      }
    } catch (e) {
      console.log('[Conference] Join failed:', e);
    }
  }
})();

})();
