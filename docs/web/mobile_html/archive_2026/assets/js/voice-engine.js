/**
 * GoSiteMe Voice Command Center v2.0
 * Extracted + upgraded from voice.php inline JS
 * Features: Voice relay, VAD live mode, agent selector, help panel,
 *           steering queue, background canvas, text input, browser TTS fallback
 */
(function() {
'use strict';

// ═══════════════════════════════════════
// Config (injected from PHP via init)
// ═══════════════════════════════════════
let VOICE_AUTH_TOKEN = '';
let VOICE_USERNAME = '';
let alfredCsrfToken = (typeof window !== 'undefined' && window.AW_CSRF_TOKEN) ? window.AW_CSRF_TOKEN : '';
let VOICE_IS_AUTH = false;
const RELAY_URL = '/middleware/api/voice-relay';
let textOnlyMode = false;

// ═══════════════════════════════════════
// Browser TTS Failsafe
// ═══════════════════════════════════════
function browserSpeak(text, onEnd) {
  if (!text || !('speechSynthesis' in window)) { if(onEnd) onEnd(); return; }
  window.speechSynthesis.cancel();
  const utt = new SpeechSynthesisUtterance(text.replace(/\*\*/g,'').replace(/[#_`]/g,'').substring(0, 500));
  utt.rate = 1.05; utt.pitch = 1.0; utt.volume = 1.0; utt.lang = 'en-US';
  const voices = window.speechSynthesis.getVoices();
  const preferred = voices.find(v => v.name.includes('Google') && v.lang.startsWith('en'))
    || voices.find(v => v.lang.startsWith('en') && v.localService)
    || voices.find(v => v.lang.startsWith('en'));
  if (preferred) utt.voice = preferred;
  utt.onend = () => { if(onEnd) onEnd(); };
  utt.onerror = () => { if(onEnd) onEnd(); };
  isAlfredSpeaking = true;
  window.speechSynthesis.speak(utt);
}
// Fetch TTS audio from server (returns ArrayBuffer for playAudioResponse)
async function fetchTTSAudio(text) {
  const resp = await fetch(RELAY_URL + '/tts', {
    method: 'POST',
    headers: Object.assign({'Content-Type': 'application/json'}, relayHeaders()),
    body: JSON.stringify({ text: text, voice: 'onyx' })  // LOCKED: Always use onyx
  });
  if (!resp.ok) throw new Error('TTS failed');
  return await resp.arrayBuffer();
}

if ('speechSynthesis' in window) {
  window.speechSynthesis.getVoices();
  window.speechSynthesis.onvoiceschanged = () => window.speechSynthesis.getVoices();
}

// ═══════════════════════════════════════
// Auth Headers
// ═══════════════════════════════════════
function relayHeaders(extra) {
  const h = {'Content-Type':'application/json'};
  if (VOICE_AUTH_TOKEN) h['Authorization'] = 'Bearer ' + VOICE_AUTH_TOKEN;
  return Object.assign(h, extra || {});
}

// ═══════════════════════════════════════
// Agent Roster
// ═══════════════════════════════════════
const AGENT_CATEGORIES = [
  {
    id: 'flagship', name: '⭐ Flagship', desc: 'The core Alfred team — our most refined AI agents',
    agents: [
      { name:'Alfred',  engine:'kokoro', voice:'onyx',    emoji:'🎩', crown:true,  role:'AI Assistant',          bio:'Deep & authoritative. The original.',   color:'rgba(125,0,255,0.2)',  border:'rgba(192,132,252,0.5)', isDefault:true },
      { name:'Aria',    engine:'kokoro', voice:'nova',    emoji:'✨', crown:false, role:'Lead Assistant',           bio:'Bright, warm & endlessly helpful.',     color:'rgba(0,212,255,0.15)', border:'rgba(0,212,255,0.4)' },
      { name:'Luna',    engine:'kokoro', voice:'shimmer', emoji:'🌙', crown:false, role:'Wellness Advisor',          bio:'Soft, calm & deeply reassuring.',       color:'rgba(99,102,241,0.15)', border:'rgba(99,102,241,0.4)' },
      { name:'Marcus',  engine:'kokoro', voice:'echo',    emoji:'🧑‍💼', crown:false, role:'Business Analyst',        bio:'Warm, clear & straight to the point.',  color:'rgba(16,185,129,0.15)', border:'rgba(16,185,129,0.4)' },
      { name:'Sage',    engine:'kokoro', voice:'fable',   emoji:'📖', crown:false, role:'Storyteller & Strategist', bio:'Thoughtful, poetic, never rushes.',     color:'rgba(251,146,60,0.15)', border:'rgba(251,146,60,0.4)' },
      { name:'Rex',     engine:'kokoro', voice:'alloy',   emoji:'🤖', crown:false, role:'Technical Specialist',     bio:'Precise, neutral, laser-focused.',      color:'rgba(59,130,246,0.15)', border:'rgba(59,130,246,0.4)' },
    ]
  },
  {
    id: 'expressive', name: '🎭 Expressive', desc: 'Highly emotional character agents — full of personality',
    agents: [
      { name:'Tara',  engine:'orpheus', voice:'tara', emoji:'👩',    crown:false, role:'Support Specialist',    bio:'Warm, friendly & genuinely caring.',    color:'rgba(236,72,153,0.15)',  border:'rgba(236,72,153,0.4)' },
      { name:'Leah',  engine:'orpheus', voice:'leah', emoji:'👩‍🦰', crown:false, role:'Data Analyst',          bio:'Calm, professional & razor-sharp.',     color:'rgba(99,102,241,0.15)',  border:'rgba(99,102,241,0.4)' },
      { name:'Jess',  engine:'orpheus', voice:'jess', emoji:'🎉',    crown:false, role:'Marketing Agent',       bio:'Energetic, fun & impossible to ignore.',color:'rgba(251,146,60,0.15)',  border:'rgba(251,146,60,0.4)' },
      { name:'Leo',   engine:'orpheus', voice:'leo',  emoji:'🦁',    crown:false, role:'Executive Advisor',     bio:'Confident, bold & in command.',         color:'rgba(234,179,8,0.15)',   border:'rgba(234,179,8,0.4)' },
      { name:'Dan',   engine:'orpheus', voice:'dan',  emoji:'☕',    crown:false, role:'Creative Director',     bio:'Casual, relaxed & full of ideas.',      color:'rgba(120,113,108,0.2)',  border:'rgba(120,113,108,0.4)' },
      { name:'Mia',   engine:'orpheus', voice:'mia',  emoji:'🌸',    crown:false, role:'Brand Voice',           bio:'Soft, elegant & unforgettable.',        color:'rgba(244,114,182,0.15)', border:'rgba(244,114,182,0.4)' },
      { name:'Zac',   engine:'orpheus', voice:'zac',  emoji:'⚡',    crown:false, role:'Growth Hacker',         bio:'Young, upbeat & always on trend.',      color:'rgba(34,211,238,0.15)',  border:'rgba(34,211,238,0.4)' },
      { name:'Zoe',   engine:'orpheus', voice:'zoe',  emoji:'🌟',    crown:false, role:'Community Manager',     bio:'Bright, cheerful & lights up the room.',color:'rgba(251,191,36,0.15)', border:'rgba(251,191,36,0.4)' },
    ]
  },
  {
    id: 'multilingual', name: '🌍 Global', desc: 'Multilingual specialists — crisp clarity in any language (Cartesia)',
    agents: [
      { name:'Nova',   engine:'cartesia-sonic', voice:'nova',    emoji:'🚀', crown:false, role:'Global Communicator',   bio:'Multilingual · crisp & professional.',   color:'rgba(0,212,255,0.15)',   border:'rgba(0,212,255,0.4)' },
      { name:'Ethan',  engine:'cartesia-sonic', voice:'echo',    emoji:'🌐', crown:false, role:'International Liaison', bio:'Multilingual · warm male presence.',     color:'rgba(16,185,129,0.15)',  border:'rgba(16,185,129,0.4)' },
      { name:'Sophie', engine:'cartesia-sonic', voice:'shimmer', emoji:'💎', crown:false, role:'Luxury Brand Voice',    bio:'Multilingual · refined & polished.',     color:'rgba(236,72,153,0.15)',  border:'rgba(236,72,153,0.4)' },
      { name:'Lyra',   engine:'cartesia-sonic', voice:'alloy',   emoji:'🎵', crown:false, role:'Conversational AI',     bio:'Natural · flows like real conversation.',color:'rgba(99,102,241,0.15)',  border:'rgba(99,102,241,0.4)' },
      { name:'Finn',   engine:'cartesia-sonic', voice:'fable',   emoji:'🏄', crown:false, role:'Lifestyle Coach',       bio:'Natural · casual & super likeable.',     color:'rgba(34,211,238,0.15)',  border:'rgba(34,211,238,0.4)' },
      { name:'Clara',  engine:'cartesia-sonic', voice:'onyx',    emoji:'📚', crown:false, role:'Education Specialist',  bio:'Natural · clear, patient & smart.',      color:'rgba(251,146,60,0.15)',  border:'rgba(251,146,60,0.4)' },
    ]
  },
  {
    id: 'premium', name: '💎 Premium', desc: 'Kokoro premium voices — best for long-form & deep conversations',
    agents: [
      { name:'Max',    engine:'kokoro', voice:'am_eric',    emoji:'🎯', crown:false, role:'Strategy Lead',     bio:'Rich & warm · precise & driven.',         color:'rgba(59,130,246,0.15)',  border:'rgba(59,130,246,0.4)' },
      { name:'Elena',  engine:'kokoro', voice:'af_bella',   emoji:'🌺', crown:false, role:'Wellness Coach',    bio:'Smooth & nurturing · kind & clear.',      color:'rgba(244,114,182,0.15)', border:'rgba(244,114,182,0.4)' },
      { name:'Victor', engine:'kokoro', voice:'bm_george',  emoji:'🏛️', crown:false, role:'Legal Advisor',     bio:'Deep & commanding · British authority.',  color:'rgba(120,113,108,0.2)',  border:'rgba(120,113,108,0.4)' },
      { name:'Grace',  engine:'kokoro', voice:'bf_emma',    emoji:'🕊️', crown:false, role:'Mindfulness Guide', bio:'Gentle & wise · soft British elegance.',  color:'rgba(167,243,208,0.15)', border:'rgba(167,243,208,0.4)' },
    ]
  },
];

let selEngine   = 'kokoro';
let selVoice    = 'onyx';
let selName     = 'Alfred';
let selRole     = 'AI Assistant · Deep & Authoritative';
let selEmoji    = '🎩';
let selColor    = 'rgba(125,0,255,0.2)';
let selCrown    = true;
let activeCatTab = 0;
let agentPanelOpen = false;

function buildAgentSelector() {
  const bar = document.getElementById('agentCatBar');
  if (!bar) return;
  AGENT_CATEGORIES.forEach((cat, i) => {
    const tab = document.createElement('button');
    tab.className = 'agent-cat-tab' + (i===0?' active':'');
    tab.textContent = cat.name;
    tab.onclick = () => switchCatTab(i);
    bar.appendChild(tab);
  });
  renderAgentGrid(0);
}

function switchCatTab(idx) {
  activeCatTab = idx;
  document.querySelectorAll('.agent-cat-tab').forEach((t,i) => t.classList.toggle('active', i===idx));
  renderAgentGrid(idx);
}

function renderAgentGrid(idx) {
  const cat  = AGENT_CATEGORIES[idx];
  document.getElementById('agentCatDesc').textContent = cat.desc;
  const grid = document.getElementById('agentGrid');
  grid.innerHTML = '';
  grid.style.gridTemplateColumns = cat.agents.length > 6 ? 'repeat(4,1fr)' : 'repeat(3,1fr)';
  cat.agents.forEach(a => {
    const isSelected = a.engine === selEngine && a.voice === selVoice;
    const card = document.createElement('div');
    card.className = 'agent-card' + (isSelected ? ' selected' : '');
    card.style.setProperty('--ac', a.color);
    card.style.setProperty('--ab', a.border);
    card.innerHTML = `
      ${a.isDefault ? '<div class="ac-default">default</div>' : ''}
      <div style="position:relative;display:inline-block;">
        <span class="ac-avatar">${a.emoji}</span>
        ${a.crown ? '<span class="ac-crown">👑</span>' : ''}
      </div>
      <div class="ac-name">${a.name}</div>
      <div class="ac-role" style="color:${a.border.replace('0.4','0.8')}">${a.role}</div>
      <div class="ac-bio">${a.bio}</div>
      <div class="ac-check">✓</div>
    `;
    card.onclick = (ev) => selectAgent(a, ev);
    grid.appendChild(card);
  });
  updateAgentPreviewBar();
}

function selectAgent(a, ev) {
  selEngine = a.engine; selVoice = a.voice; selName = a.name;
  selRole = a.role + ' · ' + a.bio.split('·')[0].trim();
  selEmoji = a.emoji; selColor = a.color; selCrown = !!a.crown;
  document.querySelectorAll('.agent-card').forEach(c => c.classList.remove('selected'));
  if (ev && ev.currentTarget) ev.currentTarget.classList.add('selected');
  updateAgentPreviewBar();
}

function updateAgentPreviewBar() {
  const el = document.getElementById('apSelected');
  if (el) el.textContent = selName + ' · ' + selRole.split('·')[0].trim();
}

function updateToggleUI() {
  const nameEl = document.getElementById('toggleName');
  const roleEl = document.getElementById('toggleRole');
  if (nameEl) nameEl.textContent = selName;
  if (roleEl) roleEl.textContent = selRole;
  const av = document.getElementById('toggleAvatar');
  if (av) {
    av.style.background = selColor;
    av.innerHTML = selEmoji + (selCrown ? '<span class="crown">👑</span>' : '');
  }
}

function toggleAgentPanel() {
  agentPanelOpen = !agentPanelOpen;
  const panel = document.getElementById('agentPanel');
  const toggle = document.getElementById('agentToggle');
  if (panel) panel.classList.toggle('open', agentPanelOpen);
  if (toggle) toggle.classList.toggle('open', agentPanelOpen);
  if (agentPanelOpen) {
    setTimeout(() => {
      if (panel) panel.scrollIntoView({behavior:'smooth', block:'nearest'});
    }, 50);
  }
}

async function previewAgent() {
  const btn = document.getElementById('btnPrevAgent');
  if (!btn) return;
  btn.disabled = true; btn.textContent = '⏳ Loading…';
  if (isConnected) {
    relaySend({ type:'preview_voice', engine:selEngine, voice:selVoice, text:'Hi! I\'m ' + selName + '. I\'m here to help you with anything you need. Let\'s get started!' });
    btn.textContent = '🔊 Playing…';
    setTimeout(() => { btn.disabled=false; btn.textContent='▶ Preview'; }, 6000);
  } else {
    addTranscript('system', '⚠️ Connect to Alfred first to preview agents');
    btn.disabled=false; btn.textContent='▶ Preview';
  }
}

function applyAgent() {
  updateToggleUI();
  if (isConnected) {
    relaySend({ type:'set_voice', engine:selEngine, voice:selVoice });
  }
  localStorage.setItem('alfred_agent_engine', selEngine);
  localStorage.setItem('alfred_agent_voice',  selVoice);
  localStorage.setItem('alfred_agent_name',   selName);
  localStorage.setItem('alfred_agent_role',   selRole);
  localStorage.setItem('alfred_agent_emoji',  selEmoji);
  localStorage.setItem('alfred_agent_color',  selColor);
  localStorage.setItem('alfred_agent_crown',  selCrown ? '1' : '0');
  addTranscript('system', '🎙️ Agent switched to ' + selName + ' — ' + selRole.split('·')[0].trim());
  toggleAgentPanel();
}

function loadSavedAgent() {
  const se = localStorage.getItem('alfred_agent_engine');
  const sv = localStorage.getItem('alfred_agent_voice');
  if (se && sv) {
    selEngine = se; selVoice = sv;
    selName   = localStorage.getItem('alfred_agent_name')  || selName;
    selRole   = localStorage.getItem('alfred_agent_role')  || selRole;
    selEmoji  = localStorage.getItem('alfred_agent_emoji') || selEmoji;
    selColor  = localStorage.getItem('alfred_agent_color') || selColor;
    selCrown  = localStorage.getItem('alfred_agent_crown') === '1';
    updateToggleUI();
  }
}

// ═══════════════════════════════════════
// State
// ═══════════════════════════════════════
let sessionId=null, pollActive=false, mediaRecorder=null, micStream=null;
let analyserCtx=null, analyser=null;
let ttsCtx=null;
let isListening=false, isConnected=false, volumeInterval=null, silenceTimer=null;
let audioChunks=[], userDisconnected=false, currentMode='tap', isAlfredSpeaking=false;
let vadActive=false, vadInterval=null, vadSpeaking=false, vadSilenceMs=0, vadSpeechMs=0;
let liveRecording=false, silenceCdInterval=null;
let reconnectTimer=null, reconnectCount=0;
const MAX_RECONNECT=3;
const VAD_SPEECH_THRESHOLD=12, VAD_SILENCE_DELAY_MS=1500, VAD_MIN_SPEECH_MS=400, VAD_TICK_MS=80;
let steeringQueueCount=0, steeringToastTimer=null;

// ═══════════════════════════════════════
// DOM References
// ═══════════════════════════════════════
let orb, micIcon, statusLabel, statusSub, transcriptBox, btnConnect;
let connDot, connLabel, volumeBar, volumeFill;
let ring1, ring2, ring3, vadRing, holdHint, orbWrapper, liveBadge;
let modeStrip, btnTapMode, btnLiveMode;
let vadMeter, vadStateLabel, vadBarsEl;
let silenceCountdown, silenceCircle;
let queueBadge, queueIndicator, queueCountEl;
let textInputBar, textInput, textSendBtn;
let steeringToast;
let helpBtn, helpPanel, helpExamplesEl;
let vadBarEls = [];

function cacheDom() {
  orb=document.getElementById('orb'); micIcon=document.getElementById('micIcon');
  statusLabel=document.getElementById('statusLabel'); statusSub=document.getElementById('statusSub');
  transcriptBox=document.getElementById('transcriptBox'); btnConnect=document.getElementById('btnConnect');
  connDot=document.getElementById('connDot'); connLabel=document.getElementById('connLabel');
  volumeBar=document.getElementById('volumeBar'); volumeFill=document.getElementById('volumeFill');
  ring1=document.getElementById('ring1'); ring2=document.getElementById('ring2'); ring3=document.getElementById('ring3');
  vadRing=document.getElementById('vadRing'); holdHint=document.getElementById('holdHint');
  orbWrapper=document.getElementById('orbWrapper'); liveBadge=document.getElementById('liveBadge');
  modeStrip=document.getElementById('modeStrip'); btnTapMode=document.getElementById('btnTapMode'); btnLiveMode=document.getElementById('btnLiveMode');
  vadMeter=document.getElementById('vadMeter'); vadStateLabel=document.getElementById('vadStateLabel'); vadBarsEl=document.getElementById('vadBars');
  silenceCountdown=document.getElementById('silenceCountdown');
  if (silenceCountdown) silenceCircle=silenceCountdown.querySelector('circle');
  queueBadge=document.getElementById('queueBadge'); queueIndicator=document.getElementById('queueIndicator'); queueCountEl=document.getElementById('queueCount');
  textInputBar=document.getElementById('textInputBar'); textInput=document.getElementById('textInput'); textSendBtn=document.getElementById('textSendBtn');
  steeringToast=document.getElementById('steeringToast');
  helpBtn=document.getElementById('helpBtn'); helpPanel=document.getElementById('helpPanel'); helpExamplesEl=document.getElementById('helpExamples');

  if (vadBarsEl) {
    for(let i=0;i<20;i++){const b=document.createElement('div');b.className='vad-bar';vadBarsEl.appendChild(b);}
    vadBarEls=Array.from(vadBarsEl.querySelectorAll('.vad-bar'));
  }
}

// ═══════════════════════════════════════
// Tap Handler
// ═══════════════════════════════════════
let lastTap=0;
function handleTap(e){
  e.preventDefault(); const now=Date.now(); if(now-lastTap<400)return; lastTap=now;
  if(helpOpen) toggleHelp();
  if(currentMode==='live')return; toggleMic();
}

let stateTimer=null;
function resetStateTimer(){
  clearTimeout(stateTimer);
  stateTimer=setTimeout(()=>{
    if(orb.className.includes('processing')||orb.className.includes('speaking')){
      setOrbState(currentMode==='live'?'live-idle':'idle');
      setStatus(currentMode==='live'?'🔴 Listening…':'Tap to speak','Alfred is ready');
      isAlfredSpeaking=false;
    }
  },30000);
}
function setStatus(l,s){if(statusLabel)statusLabel.textContent=l;if(statusSub)statusSub.textContent=s||'';}
function setOrbState(state){
  if(!orb)return;
  orb.className='orb '+state;
  const isL=state==='listening'||state==='live-listening';
  [ring1,ring2,ring3].forEach(r=>{if(r)r.style.display=isL?'block':'none';});
  if(micIcon){
    if(isL)micIcon.textContent='🎤';
    else if(state==='speaking')micIcon.textContent='🔊';
    else if(state==='processing')micIcon.textContent='⚡';
    else if(state==='live-idle')micIcon.textContent='📡';
    else micIcon.textContent='🎙️';
  }
  if(state==='processing'||state==='speaking')resetStateTimer();
  else clearTimeout(stateTimer);
}

// ═══════════════════════════════════════
// Mode Switch
// ═══════════════════════════════════════
function setMode(mode){
  if(!isConnected||mode===currentMode)return;
  if(currentMode==='tap'&&isListening)stopRecording();
  if(currentMode==='live')stopLiveMode();
  currentMode=mode;
  if(btnTapMode)btnTapMode.classList.toggle('active',mode==='tap');
  if(btnLiveMode)btnLiveMode.classList.toggle('active',mode==='live');
  if(mode==='live'){startLiveMode();}
  else{
    document.body.classList.remove('live-active'); if(liveBadge)liveBadge.classList.remove('visible');
    if(vadMeter)vadMeter.classList.remove('visible'); if(volumeBar)volumeBar.classList.remove('visible');
    if(silenceCountdown)silenceCountdown.style.display='none'; if(vadRing){vadRing.style.borderColor='rgba(0,212,255,0)';vadRing.style.transform='scale(1)';}
    setOrbState('idle'); setStatus('Tap the orb to speak','Tap mode active');
    if(holdHint)holdHint.textContent='Tap the orb · speak · tap again to send';
  }
}

// ═══════════════════════════════════════
// Live Mode
// ═══════════════════════════════════════
async function startLiveMode(){
  document.body.classList.add('live-active'); if(liveBadge)liveBadge.classList.add('visible'); if(vadMeter)vadMeter.classList.add('visible');
  setStatus('🔴 Listening…','Live mode — just start talking');
  if(holdHint)holdHint.textContent='Hands-free · Alfred detects when you speak & pause';
  addTranscript('system','🔴 Live mode activated — just start talking naturally');
  try {
    micStream=await navigator.mediaDevices.getUserMedia({audio:{echoCancellation:true,noiseSuppression:true,sampleRate:16000},video:false});
    if(analyserCtx){try{analyserCtx.close();}catch(e){} analyserCtx=null; analyser=null;}
    analyserCtx=new(window.AudioContext||window.webkitAudioContext)();
    analyser=analyserCtx.createAnalyser(); analyser.fftSize=512;
    analyserCtx.createMediaStreamSource(micStream).connect(analyser);
    vadActive=true; vadSpeaking=false; vadSilenceMs=0; vadSpeechMs=0;
    setOrbState('live-idle'); startVADLoop();
  } catch(err){
    document.body.classList.remove('live-active'); if(liveBadge)liveBadge.classList.remove('visible'); if(vadMeter)vadMeter.classList.remove('visible');
    currentMode='tap'; if(btnTapMode)btnTapMode.classList.add('active'); if(btnLiveMode)btnLiveMode.classList.remove('active');
    setStatus(err.name==='NotAllowedError'?'Microphone blocked ❌':'Mic error ❌',err.name==='NotAllowedError'?'Please allow mic access':err.message);
  }
}
function stopLiveMode(){
  vadActive=false;
  clearInterval(vadInterval); vadInterval=null;
  clearInterval(silenceCdInterval); silenceCdInterval=null;
  liveStopRecording(false);
  stopMic();
  vadSpeaking=false; vadSilenceMs=0; vadSpeechMs=0;
  if(silenceCountdown)silenceCountdown.style.display='none';
  if(vadRing){vadRing.style.borderColor='rgba(0,212,255,0)'; vadRing.style.transform='scale(1)';}
  updateVADBars(0); document.body.classList.remove('live-active');
  if(liveBadge)liveBadge.classList.remove('visible'); if(vadMeter)vadMeter.classList.remove('visible');
}

// ═══════════════════════════════════════
// VAD (Voice Activity Detection)
// ═══════════════════════════════════════
function startVADLoop(){
  clearInterval(vadInterval);
  const fd=new Uint8Array(analyser.frequencyBinCount);
  vadInterval=setInterval(()=>{
    if(!vadActive)return;
    analyser.getByteFrequencyData(fd);
    const norm=Math.min(100,fd.reduce((a,b)=>a+b,0)/fd.length*2.2);
    updateVADBars(norm); updateVADRing(norm);
    if(isAlfredSpeaking){if(norm>=VAD_SPEECH_THRESHOLD*2){window.speechSynthesis.cancel();isAlfredSpeaking=false;}else{vadSpeaking=false;vadSilenceMs=0;vadSpeechMs=0;updateSilenceCountdown(0);return;}}
    if(norm>=VAD_SPEECH_THRESHOLD){
      vadSpeechMs+=VAD_TICK_MS; vadSilenceMs=0;
      clearInterval(silenceCdInterval); silenceCdInterval=null;
      if(silenceCountdown)silenceCountdown.style.display='none'; updateSilenceCountdown(0);
      if(!vadSpeaking){vadSpeaking=true;if(vadStateLabel){vadStateLabel.textContent='speaking';vadStateLabel.className='vad-state speaking';}if(!liveRecording&&!isAlfredSpeaking){liveStartRecording();setOrbState('live-listening');setStatus('🔴 Listening…','Speak naturally');}}
    } else {
      if(vadSpeaking){
        vadSilenceMs+=VAD_TICK_MS;
        if(vadSilenceMs>200){if(silenceCountdown)silenceCountdown.style.display='block';updateSilenceCountdown(Math.min(1,vadSilenceMs/VAD_SILENCE_DELAY_MS));}
        if(vadSilenceMs>=VAD_SILENCE_DELAY_MS){
          vadSpeaking=false;vadSilenceMs=0;if(vadStateLabel){vadStateLabel.textContent='silence';vadStateLabel.className='vad-state silence';}
          if(silenceCountdown)silenceCountdown.style.display='none';updateSilenceCountdown(0);
          if(vadSpeechMs>=VAD_MIN_SPEECH_MS)liveStopRecording(true);
          else{liveStopRecording(false);setOrbState('live-idle');setStatus('🔴 Listening…','Speak naturally');}
          vadSpeechMs=0;
        }
      } else {
        vadSilenceMs=0;if(vadStateLabel){vadStateLabel.textContent='silence';vadStateLabel.className='vad-state silence';}
        if(!liveRecording)setOrbState('live-idle');
      }
    }
  },VAD_TICK_MS);
}
function updateVADBars(level){
  vadBarEls.forEach((bar,i)=>{
    const d=Math.abs(i-vadBarEls.length/2)/(vadBarEls.length/2);
    const h=Math.max(4,Math.min(28,level*(1-d*0.5)*(0.8+Math.random()*0.4)*0.28));
    bar.style.height=h+'px'; bar.style.opacity=level>VAD_SPEECH_THRESHOLD?(0.5+(h/28)*0.5).toFixed(2):'0.2';
  });
}
function updateVADRing(level){
  if(!vadRing)return;
  if(level>VAD_SPEECH_THRESHOLD){vadRing.style.transform='scale('+(1+(level/100)*0.25).toFixed(3)+')';vadRing.style.borderColor='rgba(0,212,255,'+Math.min(0.6,level/100*0.8).toFixed(2)+')';}
  else{vadRing.style.transform='scale(1)';vadRing.style.borderColor='rgba(0,212,255,0)';}
}
function updateSilenceCountdown(pct){if(silenceCircle)silenceCircle.style.strokeDashoffset=(477*(1-pct)).toFixed(1);}

// ═══════════════════════════════════════
// Live Recording
// ═══════════════════════════════════════
function liveStartRecording(){
  if(liveRecording||!micStream)return;
  const mime=['audio/webm;codecs=opus','audio/webm','audio/ogg'].find(t=>MediaRecorder.isTypeSupported(t))||'';
  mediaRecorder=new MediaRecorder(micStream,mime?{mimeType:mime}:{});
  audioChunks=[]; mediaRecorder.ondataavailable=e=>{if(e.data.size>0)audioChunks.push(e.data);}; mediaRecorder.start(100); liveRecording=true;
}
function liveStopRecording(send){
  if(!liveRecording||!mediaRecorder)return; liveRecording=false;
  const chunks=[...audioChunks]; audioChunks=[];
  const done=()=>{
    if(send&&chunks.length){sendAudioBlob(new Blob(chunks,{type:'audio/webm'}));setOrbState('processing');setStatus(selName+' is thinking…','Processing your voice');}
    if(vadActive&&micStream&&!isAlfredSpeaking)setTimeout(()=>{if(vadActive&&!liveRecording){setOrbState('live-idle');if(!isAlfredSpeaking)setStatus('🔴 Listening…','Speak naturally');}},200);
  };
  if(mediaRecorder.state!=='inactive'){mediaRecorder.onstop=done;mediaRecorder.stop();}else done();
}

// ═══════════════════════════════════════
// Connect / Disconnect (HTTP relay)
// ═══════════════════════════════════════
async function relaySend(msg){
  if(!sessionId||!isConnected) return;
  try {
    await fetch(RELAY_URL+'/send',{
      method:'POST', headers:relayHeaders(),
      body:JSON.stringify({session_id:sessionId, message:msg})
    });
  } catch(e){ console.warn('[Voice] Send error:',e); }
}

async function pollLoop(){
  while(pollActive && sessionId){
    try {
      const res=await fetch(RELAY_URL+'/poll?session_id='+sessionId,{headers:relayHeaders()});
      if(!res.ok){ if(res.status===404){handleRelayDisconnect();return;} await new Promise(r=>setTimeout(r,1000)); continue; }
      const data=await res.json();
      if(data.messages && data.messages.length){
        // Check if server sent TTS audio in this batch — skip browserSpeak if so
        const hasServerAudio = data.messages.some(m=>m.type==='audio_binary'||m.type==='audio_response');
        for(const msg of data.messages){
          try {
            if(msg.type==='audio_binary'&&msg.data){
              playAudioResponse(Uint8Array.from(atob(msg.data),c=>c.charCodeAt(0)).buffer);
            } else if(msg.type==='audio_response'&&msg.data){
              playAudioResponse(Uint8Array.from(atob(msg.data),c=>c.charCodeAt(0)).buffer);
            } else {
              if(hasServerAudio && msg.type==='text_response') msg._hasAudio=true;
              handleServerMessage(msg);
            }
          } catch(e){ console.warn('[Voice] Message error:',e); }
        }
      }
    } catch(e){
      if(!pollActive) return;
      await new Promise(r=>setTimeout(r,1000));
    }
  }
}

function handleRelayDisconnect(){
  const was=isConnected;
  pollActive=false; sessionId=null;
  isConnected=false; isListening=false; isAlfredSpeaking=false;
  if(connDot)connDot.className='conn-dot'; if(connLabel)connLabel.textContent='Disconnected';
  if(btnConnect){btnConnect.textContent='⚡ Initialize Alfred'; btnConnect.classList.remove('connected'); btnConnect.disabled=false;}
  if(modeStrip)modeStrip.classList.remove('visible');
  const agentWrap=document.getElementById('agentSelectorWrap'); if(agentWrap)agentWrap.classList.remove('visible');
  if(helpBtn)helpBtn.classList.remove('visible'); if(helpOpen) toggleHelp();
  if(textInputBar)textInputBar.classList.remove('visible'); updateQueueBadge(0);
  if(agentPanelOpen)toggleAgentPanel();
  if(currentMode==='live')stopLiveMode();
  currentMode='tap'; if(btnTapMode)btnTapMode.classList.add('active'); if(btnLiveMode)btnLiveMode.classList.remove('active');
  setOrbState('idle'); if(holdHint)holdHint.textContent=''; stopMic();
  clearTimeout(reconnectTimer);
  if(was && !userDisconnected && reconnectCount < MAX_RECONNECT){
    reconnectCount++;
    const delay=Math.min(2000*reconnectCount,8000);
    setStatus('Reconnecting…','Attempt '+reconnectCount+'/'+MAX_RECONNECT);
    if(connDot)connDot.className='conn-dot connecting'; if(connLabel)connLabel.textContent='Reconnecting…';
    reconnectTimer=setTimeout(()=>{ if(!isConnected&&!userDisconnected) connectVoice(); },delay);
  } else {
    reconnectCount=0;
    setStatus('Tap to connect','Alfred is waiting');
  }
}

async function connectVoice(){
  if(isConnected){disconnect();return;}
  if(sessionId) return;
  userDisconnected=false;
  if(connDot)connDot.className='conn-dot connecting'; if(connLabel)connLabel.textContent='Connecting…';
  setStatus('Connecting to Alfred…','Initializing command center'); if(btnConnect){btnConnect.textContent='⏳ Initializing…'; btnConnect.disabled=true;}

  try {
    const res=await fetch(RELAY_URL+'/connect',{method:'POST',headers:relayHeaders()});
    const data=await res.json();
    if(!data.session_id) throw new Error(data.error||'No session');
    sessionId=data.session_id;
    textOnlyMode = (data.mode === 'text');

    reconnectCount=0;
    isConnected=true;
    if(connDot)connDot.className='conn-dot connected';
    if(connLabel)connLabel.textContent = textOnlyMode ? 'Connected ✓ (Browser Voice)' : 'Connected ✓';
    if(btnConnect){btnConnect.textContent='🔴 Disconnect'; btnConnect.classList.add('connected'); btnConnect.disabled=false;}
    setOrbState('idle'); if(modeStrip)modeStrip.classList.add('visible');
    const agentWrap=document.getElementById('agentSelectorWrap'); if(agentWrap)agentWrap.classList.add('visible');
    if(helpBtn)helpBtn.classList.add('visible');
    if(textInputBar)textInputBar.classList.add('visible'); updateQueueBadge(0);
    setTimeout(()=>{if(isConnected)relaySend({type:'set_voice',engine:selEngine,voice:selVoice});},800);
    if(VOICE_IS_AUTH&&VOICE_AUTH_TOKEN){
      relaySend({type:'auth',token:VOICE_AUTH_TOKEN,username:VOICE_USERNAME});
      if(connLabel)connLabel.textContent='Connected ✓ ('+VOICE_USERNAME+')';
      setStatus('Command Center Online','Speak with '+selName+' · Full access · 1,220+ tools');
      if(holdHint)holdHint.textContent='Tap orb · Go Live for hands-free · Switch agents below';
      addTranscript('system','🤖 Alfred Command Center initialized — authenticated as '+VOICE_USERNAME+' (full tool access)');
      if(textOnlyMode) addTranscript('system','🔊 Using browser voice synthesis — voice server warming up. All AI features are fully operational.');
    } else {
      setStatus('Command Center Online','Speak with '+selName+' · Chat mode');
      if(holdHint)holdHint.textContent='Tap orb · Go Live for hands-free · Switch agents below';
      addTranscript('system','🤖 Alfred Command Center initialized (chat mode)');
    }
    pollActive=true;
    pollLoop();
  } catch(e){
    sessionId=null;
    if(connDot)connDot.className='conn-dot error'; if(connLabel)connLabel.textContent='Failed';
    setStatus('Connection failed ❌','Tap Initialize to retry');
    if(btnConnect){btnConnect.textContent='⚡ Initialize Alfred'; btnConnect.classList.remove('connected'); btnConnect.disabled=false;}
    addTranscript('system','Could not connect — voice server may be restarting');
  }
}

function disconnect(){
  userDisconnected=true;
  clearTimeout(reconnectTimer); reconnectCount=0;
  if(currentMode==='live')stopLiveMode();
  pollActive=false;
  if(sessionId){
    fetch(RELAY_URL+'/disconnect',{
      method:'POST',headers:relayHeaders(),
      body:JSON.stringify({session_id:sessionId})
    }).catch(()=>{});
    sessionId=null;
  }
  stopMic();
  if(modeStrip)modeStrip.classList.remove('visible');
  const agentWrap=document.getElementById('agentSelectorWrap'); if(agentWrap)agentWrap.classList.remove('visible');
  if(helpBtn)helpBtn.classList.remove('visible'); if(helpOpen) toggleHelp();
  if(textInputBar)textInputBar.classList.remove('visible'); updateQueueBadge(0);
  if(agentPanelOpen)toggleAgentPanel();
  isConnected=false;
  setTimeout(()=>{userDisconnected=false;},500);
}

// ═══════════════════════════════════════
// Message Handler
// ═══════════════════════════════════════
function handleServerMessage(msg){
  switch(msg.type){
    case 'session_start': addTranscript('alfred',msg.message||selName+' is ready! Tap the orb to speak.'); break;
    case 'transcript': case 'transcription':
      addTranscript('user',msg.text); setOrbState('processing'); setStatus(selName+' is thinking…','"'+msg.text.substring(0,50)+'"'); break;
    case 'response_text': case 'response':
      addTranscript('alfred',msg.text); setOrbState('speaking'); setStatus(selName+' is speaking…',msg.text.substring(0,60)+(msg.text.length>60?'…':'')); break;
    case 'response_complete': case 'pipeline_complete':
      isAlfredSpeaking=false;
      updateQueueBadge(0);
      if(currentMode==='live'){setOrbState('live-idle');setStatus('🔴 Listening…','Speak naturally');}
      else{setOrbState('idle');setStatus('Tap to speak',selName+' is ready');}
      break;
    case 'tts_error': addTranscript('system','🔇 '+(msg.message||'TTS unavailable')); break;
    case 'status':
      if(msg.stage==='transcribing'){setOrbState('processing');setStatus('Transcribing…','Converting speech to text');}
      else if(msg.stage==='thinking'){setOrbState('processing');setStatus(selName+' is thinking…','');}
      else if(msg.stage==='speaking'){isAlfredSpeaking=true;setOrbState('speaking');setStatus(selName+' is speaking…','');}
      else if(msg.stage==='silence'){isAlfredSpeaking=false;if(currentMode==='live'){setOrbState('live-idle');setStatus('🔴 Listening…','No speech detected');}else{setOrbState('idle');setStatus('Tap to speak','No speech detected');}}
      else if(msg.stage==='using_tools'){setOrbState('processing');setStatus(selName+' is working…',msg.detail||'Using tools');}
      else if(msg.stage==='tool_result'){setOrbState('processing');setStatus(selName+' is working…',msg.detail||'Processing results');}
      break;
    case 'auth_ok': addTranscript('system','🔑 Authenticated as '+(msg.username||'user')+' — Full access enabled'); break;
    case 'auth_fail': addTranscript('system','⚠️ Authentication failed — Chat mode only'); if(connLabel)connLabel.textContent='Connected ✓'; break;
    case 'error': isAlfredSpeaking=false; setStatus('Error: '+(msg.message||'unknown'),''); setOrbState(currentMode==='live'?'live-idle':'idle'); addTranscript('system','⚠️ '+(msg.message||'error')); break;
    case 'text_response':
      addTranscript('alfred', msg.text||msg.message||'');
      setOrbState('speaking'); setStatus(selName+' is speaking…',(msg.text||'').substring(0,60));
      if(!msg._hasAudio) { fetchTTSAudio(msg.text||msg.message||'').then(ab=>playAudioResponse(ab)).then(()=>{
        isAlfredSpeaking=false;
        if(currentMode==='live'){setOrbState('live-idle');setStatus('🔴 Listening…','Speak naturally');}
        else{setOrbState('idle');setStatus('Tap to speak',selName+' is ready');}
      });
      break;
    case 'ws_closed':
      textOnlyMode=true;
      addTranscript('system','🔄 Voice server reconnecting — using browser voice in the meantime');
      break;
    case 'steering_queued': case 'steering_abort': case 'steering_processing': case 'steering_executing': case 'transcribing_queued':
      handleSteeringMessage(msg); break;
  }
}

// ═══════════════════════════════════════
// Tap Recording
// ═══════════════════════════════════════
async function toggleMic(){if(!isConnected){connectVoice();return;} if(isListening)stopRecording();else await startRecording();}
async function startRecording(){
  try {
    if(micStream)stopMic();
    micStream=await navigator.mediaDevices.getUserMedia({audio:{echoCancellation:true,noiseSuppression:true,sampleRate:16000},video:false});
    if(analyserCtx){try{analyserCtx.close();}catch(e){} analyserCtx=null; analyser=null;}
    analyserCtx=new(window.AudioContext||window.webkitAudioContext)();
    analyser=analyserCtx.createAnalyser(); analyser.fftSize=256;
    analyserCtx.createMediaStreamSource(micStream).connect(analyser);
    const mime=['audio/webm;codecs=opus','audio/webm','audio/ogg'].find(t=>MediaRecorder.isTypeSupported(t))||'';
    mediaRecorder=new MediaRecorder(micStream,mime?{mimeType:mime}:{});
    audioChunks=[]; mediaRecorder.ondataavailable=e=>{if(e.data.size>0)audioChunks.push(e.data);}; mediaRecorder.onstop=()=>sendAudio(); mediaRecorder.start(100); isListening=true;
    setOrbState('listening'); setStatus('Listening… tap orb to send','Speak now');
    if(volumeBar)volumeBar.classList.add('visible'); startVolumeMonitor();
    silenceTimer=setTimeout(()=>{if(isListening)stopRecording();},8000);
  } catch(err){
    setStatus(err.name==='NotAllowedError'?'Microphone blocked ❌':'Mic error: '+err.message,'');
    if(err.name==='NotAllowedError')addTranscript('system','⚠️ Microphone permission denied');
  }
}
function stopRecording(){
  clearTimeout(silenceTimer);
  if(mediaRecorder&&mediaRecorder.state!=='inactive')mediaRecorder.stop();
  isListening=false; stopMic();
  setOrbState('processing'); setStatus('Sending to '+selName+'…','Processing your voice');
  clearInterval(volumeInterval); volumeInterval=null;
  if(volumeBar)volumeBar.classList.remove('visible');
}
function stopMic(){
  if(micStream){micStream.getTracks().forEach(t=>t.stop());micStream=null;}
  if(analyserCtx){try{analyserCtx.close();}catch(e){} analyserCtx=null; analyser=null;}
  isListening=false; liveRecording=false;
  clearInterval(volumeInterval); volumeInterval=null;
}
function sendAudio(){
  if(!audioChunks.length||!isConnected){setOrbState(currentMode==='live'?'live-idle':'idle');setStatus(currentMode==='live'?'🔴 Listening…':'Tap to speak',selName+' is ready');return;}
  sendAudioBlob(new Blob(audioChunks,{type:'audio/webm'})); audioChunks=[];
}
function sendAudioBlob(blob){
  if(!isConnected)return;
  if(textOnlyMode) { sendAudioFallback(blob); return; }
  const r=new FileReader(); r.onloadend=()=>{relaySend({type:'audio',data:r.result.split(',')[1],format:'webm'});};
  r.readAsDataURL(blob);
}

// ═══════════════════════════════════════
// Browser Whisper Fallback
// ═══════════════════════════════════════
async function sendAudioFallback(blob, isRetry) {
  setOrbState('processing'); setStatus('Transcribing…','Using cloud speech-to-text');
  try {
    const fd = new FormData();
    fd.append('audio', blob, 'recording.webm');
    fd.append('agent', 'alfred');
    fd.append('context', 'Voice Command Center — browser voice fallback');
    fd.append('page_url', '/voice.php');
    fd.append('username', VOICE_USERNAME || 'Guest');
    fd.append('model', 'sonnet');
    if (alfredCsrfToken) fd.append('csrf_token', alfredCsrfToken);
    const headers = {};
    if (alfredCsrfToken) headers['X-CSRF-Token'] = alfredCsrfToken;
    const resp = await fetch('/api/alfred-chat.php?action=voice', { method: 'POST', body: fd, headers });
    const data = await resp.json();
    if (data.csrf_refresh && data.csrf_token) {
      alfredCsrfToken = data.csrf_token;
      if (!isRetry) { return sendAudioFallback(blob, true); }
    }
    if (data.transcript) addTranscript('user', data.transcript);
    if (data.response && !data.csrf_refresh) {
      addTranscript('alfred', data.response);
      setOrbState('speaking'); setStatus(selName+' is speaking…', data.response.substring(0,60));
      // Use server TTS instead of broken browserSpeak

      fetchTTSAudio(data.response).then(audioBuf => {

        playAudioResponse(audioBuf);

        isAlfredSpeaking = false;

        if(currentMode==='live'){setOrbState('live-idle');setStatus('\ud83d\udd34 Listening\u2026','Speak naturally');}

        else{setOrbState('idle');setStatus('Tap to speak',selName+' is ready');}

      }).catch(() => {

        browserSpeak(data.response, () => {

          isAlfredSpeaking = false;

          if(currentMode==='live'){setOrbState('live-idle');setStatus('\ud83d\udd34 Listening\u2026','Speak naturally');}

          else{setOrbState('idle');setStatus('Tap to speak',selName+' is ready');}

        });

      });
    } else if (!data.csrf_refresh) {
      setOrbState('idle'); setStatus('Tap to speak', selName+' is ready');
      addTranscript('system','⚠️ ' + (data.error || 'Could not process audio'));
    }
  } catch (e) {
    setOrbState('idle'); setStatus('Error', e.message);
    addTranscript('system', '⚠️ Voice fallback error: ' + e.message);
  }
}

function startVolumeMonitor(){
  if(volumeInterval){clearInterval(volumeInterval);volumeInterval=null;}
  if(!analyser)return;
  const data=new Uint8Array(analyser.frequencyBinCount);
  volumeInterval=setInterval(()=>{
    if(!analyser){clearInterval(volumeInterval);volumeInterval=null;return;}
    analyser.getByteFrequencyData(data);
    if(volumeFill)volumeFill.style.width=Math.min(100,data.reduce((a,b)=>a+b,0)/data.length*2)+'%';
  },50);
}

// ═══════════════════════════════════════
// TTS Audio Playback
// ═══════════════════════════════════════
function getTTSContext(){
  if(!ttsCtx||ttsCtx.state==='closed'){
    ttsCtx=new(window.AudioContext||window.webkitAudioContext)();
  }
  return ttsCtx;
}
async function playAudioResponse(arrayBuffer){
  try {
    isAlfredSpeaking=true;
    const ctx=getTTSContext();
    if(ctx.state==='suspended') await ctx.resume();
    const src=ctx.createBufferSource();
    src.buffer=await ctx.decodeAudioData(arrayBuffer.slice(0));
    src.connect(ctx.destination); src.start();
    setOrbState('speaking'); setStatus(selName+' is speaking…','');
    src.onended=()=>{
      isAlfredSpeaking=false;
      if(currentMode==='live'){setOrbState('live-idle');setStatus('🔴 Listening…',selName+' is ready');}
      else{setOrbState('idle');setStatus('Tap to speak',selName+' is ready');}
    };
  } catch(e){
    isAlfredSpeaking=false;
    setOrbState(currentMode==='live'?'live-idle':'idle');
    setStatus(currentMode==='live'?'🔴 Listening…':'Tap to speak',selName+' is ready');
  }
}
function addTranscript(role,text){
  if(!transcriptBox)return;
  transcriptBox.classList.add('visible');
  const el=document.createElement('div'); el.className='transcript-entry '+role; el.textContent=text;
  transcriptBox.appendChild(el); transcriptBox.scrollTop=transcriptBox.scrollHeight;
}

// ═══════════════════════════════════════
// Help Panel
// ═══════════════════════════════════════
const HELP_EXAMPLES = [
  {cat:'web', icon:'🌐', cmd:'Install WordPress on myblog.com', desc:'Full WordPress setup with SSL'},
  {cat:'web', icon:'📧', cmd:'Create hello@mybusiness.com and forward to Gmail', desc:'Email with SPF/DKIM/DMARC'},
  {cat:'web', icon:'🔒', cmd:'Set up SSL for all my domains', desc:'Free Let\'s Encrypt certificates'},
  {cat:'web', icon:'💾', cmd:'Back up my entire site right now', desc:'Full backup with one-click restore'},
  {cat:'web', icon:'📁', cmd:'Show me what files are on my server', desc:'Browse your hosting files'},
  {cat:'ecommerce', icon:'🛒', cmd:'Set up an online store selling handmade candles', desc:'Full WooCommerce + Stripe setup'},
  {cat:'ecommerce', icon:'💳', cmd:'Connect Stripe payments to my store', desc:'Accept cards, Apple Pay, Google Pay'},
  {cat:'ecommerce', icon:'📦', cmd:'Set up free shipping over $50 for Canada', desc:'Shipping zones and rules'},
  {cat:'ecommerce', icon:'🧾', cmd:'Generate an invoice for $500 web design work', desc:'Professional branded PDF'},
  {cat:'ecommerce', icon:'💰', cmd:'Show me my revenue this month', desc:'Sales trends and analytics'},
  {cat:'seo', icon:'🔍', cmd:'Run an SEO audit on my homepage', desc:'Score + actionable fixes'},
  {cat:'seo', icon:'🗺️', cmd:'Generate a sitemap for my website', desc:'Auto-submitted to Google'},
  {cat:'seo', icon:'📊', cmd:'Find the best keywords for my bakery site', desc:'Volume, difficulty, suggestions'},
  {cat:'seo', icon:'🎴', cmd:'Set up social media preview cards', desc:'Look great when shared'},
  {cat:'design', icon:'🎨', cmd:'Design a logo for my coffee shop Brew & Bean', desc:'AI-generated in multiple styles'},
  {cat:'design', icon:'🖼️', cmd:'Generate a hero image for my restaurant', desc:'AI photorealistic images'},
  {cat:'design', icon:'🎯', cmd:'Create a landing page for my SaaS launch', desc:'Hero, features, CTA, responsive'},
  {cat:'design', icon:'🌈', cmd:'Generate a warm color palette for my brand', desc:'Hex codes + CSS variables'},
  {cat:'design', icon:'⚡', cmd:'Optimize all images on my site for speed', desc:'WebP, lazy load, compression'},
  {cat:'devops', icon:'🚀', cmd:'Set up auto-deploy from GitHub on push', desc:'CI/CD pipeline in seconds'},
  {cat:'devops', icon:'🔬', cmd:'Create a staging copy of my site', desc:'Safe testing environment'},
  {cat:'devops', icon:'🧪', cmd:'Run all my tests and show failures', desc:'Jest, PHPUnit, pytest support'},
  {cat:'devops', icon:'📈', cmd:'Benchmark my site performance', desc:'Load times, TTFB, throughput'},
  {cat:'security', icon:'🛡️', cmd:'Is my site hacked? Run a malware scan', desc:'Deep security scan'},
  {cat:'security', icon:'👤', cmd:'Add user login and registration to my app', desc:'Full auth system'},
  {cat:'security', icon:'🔐', cmd:'Enable two-factor authentication', desc:'TOTP, SMS, or email codes'},
  {cat:'security', icon:'♿', cmd:'Run an accessibility audit on my site', desc:'WCAG 2.1 compliance'},
  {cat:'security', icon:'📋', cmd:'Make my site GDPR compliant', desc:'Privacy, consent, data rights'},
  {cat:'content', icon:'✍️', cmd:'Write a blog post about 10 tips for small businesses', desc:'SEO-optimized with images'},
  {cat:'content', icon:'🌍', cmd:'Translate my homepage to French and Spanish', desc:'Context-aware, not literal'},
  {cat:'content', icon:'⚖️', cmd:'Generate a privacy policy for my Canadian store', desc:'Legal pages, jurisdiction-specific'},
  {cat:'content', icon:'📱', cmd:'Write 5 Instagram posts for my new collection', desc:'Hashtags, emojis, CTAs'},
  {cat:'steering', icon:'🎯', cmd:'Install WordPress, then set up SSL, then configure email', desc:'Queue multiple tasks — Alfred runs them in order'},
  {cat:'steering', icon:'🛑', cmd:'Stop! Cancel that and check my DNS instead', desc:'Abort current task and redirect Alfred instantly'},
  {cat:'steering', icon:'📋', cmd:'After you finish that, also back up my site', desc:'Add commands while Alfred is busy working'},
  {cat:'steering', icon:'⚡', cmd:'Set up my store: WooCommerce, Stripe, shipping, taxes', desc:'Chain complex multi-step operations in one go'},
];

let helpOpen = false;
let helpCurrentCat = 'all';

function toggleHelp() {
  helpOpen = !helpOpen;
  if(helpPanel)helpPanel.classList.toggle('open', helpOpen);
}

function renderHelpExamples(cat) {
  helpCurrentCat = cat;
  const filtered = cat === 'all' ? HELP_EXAMPLES : HELP_EXAMPLES.filter(e => e.cat === cat);
  if(!helpExamplesEl)return;
  helpExamplesEl.innerHTML = '';
  filtered.forEach(ex => {
    const div = document.createElement('div');
    div.className = 'help-example';
    div.innerHTML = '<span class="he-icon">' + ex.icon + '</span><div class="he-body"><div class="he-cmd">' + ex.cmd + '</div><div class="he-desc">' + ex.desc + '</div></div>';
    div.onclick = () => sendHelpCommand(ex.cmd);
    helpExamplesEl.appendChild(div);
  });
}

function sendHelpCommand(cmd) {
  toggleHelp();
  if (isConnected) {
    addTranscript('user', cmd);
    relaySend({ type: 'text', text: cmd, ttsEnabled: true });
    setOrbState('processing');
    setStatus(selName + ' is thinking…', '"' + cmd.substring(0, 50) + '"');
  } else {
    addTranscript('system', '⚠️ Connect to Alfred first');
  }
}

// ═══════════════════════════════════════
// Text Input
// ═══════════════════════════════════════
function sendTextCommand() {
  if(!textInput)return;
  const cmd = textInput.value.trim();
  if (!cmd) return;
  if (!isConnected) { addTranscript('system', '⚠️ Connect to Alfred first'); return; }
  textInput.value = '';
  addTranscript('user', cmd);
  relaySend({ type: 'text', text: cmd, ttsEnabled: true });
  setOrbState('processing');
  setStatus(selName + ' is thinking…', '"' + cmd.substring(0, 50) + '"');
}

// ═══════════════════════════════════════
// Steering Queue UI
// ═══════════════════════════════════════
function showSteeringToast(html, isAbort) {
  clearTimeout(steeringToastTimer);
  if(!steeringToast)return;
  steeringToast.innerHTML = html;
  steeringToast.className = 'steering-toast visible' + (isAbort ? ' abort' : '');
  steeringToastTimer = setTimeout(() => { steeringToast.classList.remove('visible'); }, 3500);
}
function updateQueueBadge(count) {
  steeringQueueCount = count;
  if (count > 0) { if(queueBadge){queueBadge.textContent=count;queueBadge.classList.add('visible');} if(queueIndicator)queueIndicator.classList.add('visible'); if(queueCountEl)queueCountEl.textContent=count; }
  else { if(queueBadge)queueBadge.classList.remove('visible'); if(queueIndicator)queueIndicator.classList.remove('visible'); }
}
function handleSteeringMessage(msg) {
  switch (msg.type) {
    case 'steering_queued':
      updateQueueBadge(msg.position || steeringQueueCount + 1);
      showSteeringToast('<span class="st-icon">📋</span> Queued: "' + (msg.text || '').substring(0, 50) + '"');
      addTranscript('system', '📋 Queued (#' + (msg.position || '?') + '): ' + (msg.text || ''));
      break;
    case 'steering_abort':
      updateQueueBadge(0);
      showSteeringToast('<span class="st-icon">🛑</span> Cancelled — queue cleared', true);
      addTranscript('system', '🛑 ' + (msg.message || 'Cancelled by user'));
      isAlfredSpeaking = false;
      setOrbState(currentMode === 'live' ? 'live-idle' : 'idle');
      setStatus('Cancelled', 'Say or type a new command');
      break;
    case 'steering_processing':
      updateQueueBadge(0);
      showSteeringToast('<span class="st-icon">⚡</span> Running ' + (msg.count || '') + ' queued command' + ((msg.count||0) > 1 ? 's' : '') + '…');
      addTranscript('system', '⚡ ' + (msg.message || 'Processing queued commands'));
      setStatus(selName + ' is working…', msg.message || '');
      break;
    case 'steering_executing':
      showSteeringToast('<span class="st-icon">▶</span> Now: "' + (msg.text || '').substring(0, 50) + '"');
      addTranscript('system', '▶ ' + (msg.message || msg.text || ''));
      setOrbState('processing');
      setStatus(selName + ' is working…', (msg.text || '').substring(0, 60));
      break;
    case 'transcribing_queued':
      showSteeringToast('<span class="st-icon">🎤</span> Heard you — transcribing…');
      break;
  }
}

// ═══════════════════════════════════════
// Command Center Background Canvas
// ═══════════════════════════════════════
function initCommandCenterBg() {
  const canvas = document.getElementById('cmdBg');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  let w, h, particles = [];

  function resize() {
    w = canvas.width = window.innerWidth;
    h = canvas.height = window.innerHeight;
  }
  resize();
  window.addEventListener('resize', resize);

  for (let i = 0; i < 60; i++) {
    particles.push({
      x: Math.random() * w, y: Math.random() * h,
      vx: (Math.random() - 0.5) * 0.3, vy: (Math.random() - 0.5) * 0.3,
      r: Math.random() * 1.5 + 0.5,
      alpha: Math.random() * 0.3 + 0.05,
      color: Math.random() > 0.5 ? '125,0,255' : '0,212,255'
    });
  }

  function drawFrame() {
    ctx.clearRect(0, 0, w, h);
    ctx.strokeStyle = 'rgba(125,0,255,0.03)';
    ctx.lineWidth = 0.5;
    const gridSize = 80;
    for (let x = 0; x < w; x += gridSize) {
      ctx.beginPath(); ctx.moveTo(x, 0); ctx.lineTo(x, h); ctx.stroke();
    }
    for (let y = 0; y < h; y += gridSize) {
      ctx.beginPath(); ctx.moveTo(0, y); ctx.lineTo(w, y); ctx.stroke();
    }
    particles.forEach(p => {
      p.x += p.vx; p.y += p.vy;
      if (p.x < 0) p.x = w; if (p.x > w) p.x = 0;
      if (p.y < 0) p.y = h; if (p.y > h) p.y = 0;
      ctx.beginPath();
      ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
      ctx.fillStyle = 'rgba('+p.color+','+p.alpha+')';
      ctx.fill();
    });
    for (let i = 0; i < particles.length; i++) {
      for (let j = i + 1; j < particles.length; j++) {
        const dx = particles[i].x - particles[j].x;
        const dy = particles[i].y - particles[j].y;
        const dist = Math.sqrt(dx * dx + dy * dy);
        if (dist < 120) {
          ctx.beginPath();
          ctx.moveTo(particles[i].x, particles[i].y);
          ctx.lineTo(particles[j].x, particles[j].y);
          ctx.strokeStyle = 'rgba(125,0,255,'+(0.04 * (1 - dist / 120))+')';
          ctx.lineWidth = 0.5;
          ctx.stroke();
        }
      }
    }
    requestAnimationFrame(drawFrame);
  }
  drawFrame();
}

// ═══════════════════════════════════════
// Capability Ticker
// ═══════════════════════════════════════
function initCapTicker() {
  const capabilities = [
    'Fleet Orchestration', 'MCP Gateway · 801 Tools', 'Code Interpreter',
    'Browser Agent', 'RAG Knowledge Base', 'Voice Conferencing',
    'Solana Blockchain', 'E-Commerce', 'SEO Audit', 'DevOps & CI/CD',
    'Domain Management', 'Server Management', 'Security Scanning',
    'Legal & Compliance', 'Content Generation', 'Data Visualization',
    'Team War Room', 'White-Label', 'Chaos Engineering', 'Crypto Trading',
    'Voice Cloning', 'Multi-Channel Messaging', 'Remote Tech Support',
    'Private On-Server AI', 'Agent-to-Agent Protocol', 'Zero-Downtime Deploys'
  ];
  const ticker = document.getElementById('capTicker');
  if (!ticker) return;
  const items = [...capabilities, ...capabilities];
  ticker.innerHTML = items.map(c => '<span>'+c+'</span>').join('');
}

// ═══════════════════════════════════════
// Engine Status Bar
// ═══════════════════════════════════════
function initEngineBar() {
  const engines = [
    'ELEPHANT', 'ORACLE', 'FORGE', 'NEXUS', 'CORTEX', 'SENTINEL',
    'HIVEMIND', 'CLOCKWORK', 'PLAYBOOK', 'MCP', 'CHARTS', 'VOICE'
  ];
  const bar = document.getElementById('engineBar');
  if (!bar) return;
  bar.innerHTML = engines.map(e => '<div class="engine-chip"><span class="ec-dot"></span>'+e+'</div>').join('');
}

// ═══════════════════════════════════════
// Fleet Status Panel
// ═══════════════════════════════════════
let fleetOpen = false;
function toggleFleet() {
  fleetOpen = !fleetOpen;
  const grid = document.getElementById('fleetGrid');
  const arrow = document.getElementById('fleetArrow');
  if(grid) grid.classList.toggle('open', fleetOpen);
  if(arrow) arrow.style.transform = fleetOpen ? 'rotate(180deg)' : '';
}

function initFleetPanel() {
  const allAgents = [];
  AGENT_CATEGORIES.forEach(cat => {
    cat.agents.forEach(a => {
      allAgents.push({ name: a.name, emoji: a.emoji, role: a.role, color: a.color, border: a.border, crown: a.crown });
    });
  });
  const grid = document.getElementById('fleetGrid');
  if (!grid) return;
  const count = document.querySelector('.fleet-count');
  if (count) count.textContent = allAgents.length + ' Active';
  grid.innerHTML = allAgents.map(a =>
    '<div class="fleet-agent" style="border-color:'+(a.border || 'rgba(255,255,255,0.04)')+'">'+
      '<div class="fa-dot"></div>'+
      '<div class="fa-emoji">'+a.emoji+(a.crown ? '<span style="font-size:0.5rem;margin-left:-2px">👑</span>' : '')+'</div>'+
      '<div class="fa-name">'+a.name+'</div>'+
      '<div class="fa-role">'+a.role+'</div>'+
    '</div>'
  ).join('');
}

// ═══════════════════════════════════════
// Initialization
// ═══════════════════════════════════════
function init(config) {
  VOICE_AUTH_TOKEN = config.authToken || '';
  VOICE_USERNAME = config.username || '';
  VOICE_IS_AUTH = !!config.isAuth;

  cacheDom();
  buildAgentSelector();
  loadSavedAgent();

  // Orb tap handler
  if (orbWrapper) {
    orbWrapper.addEventListener('click', handleTap);
    orbWrapper.addEventListener('touchend', handleTap, {passive:false});
  }

  // Text input
  if (textInput) {
    textInput.addEventListener('keydown', e => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendTextCommand(); } });
  }

  // Help categories
  document.querySelectorAll('.help-cat').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.help-cat').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      renderHelpExamples(btn.dataset.cat);
    });
  });
  if (helpBtn) helpBtn.addEventListener('click', toggleHelp);
  renderHelpExamples('all');

  // Init visual systems
  initCommandCenterBg();
  initCapTicker();
  initEngineBar();
  initFleetPanel();
}

// ═══════════════════════════════════════
// Public API (window.VoiceCmd)
// ═══════════════════════════════════════
window.VoiceCmd = {
  init,
  connectVoice,
  disconnect,
  setMode,
  toggleAgentPanel,
  previewAgent,
  applyAgent,
  toggleHelp,
  sendTextCommand,
  toggleFleet,
  toggleMic
};

})();
