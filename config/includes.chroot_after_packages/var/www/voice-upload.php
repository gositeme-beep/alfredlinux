<?php
/**
 * Alfred Voice Upload — Drop audio, Alfred transcribes it.
 */
if (session_status() === PHP_SESSION_NONE) session_start();
$isAuth = isset($_SESSION['uid']) && (int)$_SESSION['uid'] > 0;
$isCommander = $isAuth && (int)$_SESSION['uid'] === 33;

$page_title = 'Alfred Voice Upload — GoSiteMe';
$page_description = 'Upload an audio file and Alfred will transcribe it for you instantly.';
$page_canonical = 'https://root.com/voice-upload.php';
require_once __DIR__ . '/includes/site-header.inc.php';
?>

<style>
.voice-upload-container {
    max-width: 720px; margin: 40px auto; padding: 30px;
    background: linear-gradient(135deg, #0f0c29, #1a1040); border-radius: 16px;
    border: 1px solid rgba(128,90,255,0.3); color: #e0e0e0; font-family: system-ui, -apple-system, sans-serif;
}
.voice-upload-container h1 { color: #c084fc; margin-bottom: 8px; font-size: 1.8rem; }
.voice-upload-container .subtitle { color: #9ca3af; margin-bottom: 24px; }
.drop-zone {
    border: 2px dashed rgba(128,90,255,0.4); border-radius: 12px;
    padding: 48px 24px; text-align: center; cursor: pointer;
    transition: all 0.3s; background: rgba(128,90,255,0.05);
}
.drop-zone:hover, .drop-zone.drag-over {
    border-color: #c084fc; background: rgba(128,90,255,0.12);
}
.drop-zone .icon { font-size: 48px; margin-bottom: 12px; }
.drop-zone .label { color: #c084fc; font-size: 1.1rem; font-weight: 600; }
.drop-zone .hint { color: #6b7280; font-size: 0.85rem; margin-top: 8px; }
.file-input { display: none; }
.progress-bar { height: 4px; background: rgba(128,90,255,0.2); border-radius: 2px; margin: 16px 0; overflow: hidden; display: none; }
.progress-bar .fill { height: 100%; background: #c084fc; width: 0%; transition: width 0.3s; border-radius: 2px; }
.result-box {
    background: rgba(0,0,0,0.3); border-radius: 10px; padding: 20px;
    margin-top: 20px; display: none; border: 1px solid rgba(128,90,255,0.2);
}
.result-box .meta { color: #9ca3af; font-size: 0.8rem; margin-bottom: 10px; }
.result-box .transcript { color: #e0e0e0; line-height: 1.6; white-space: pre-wrap; font-size: 1rem; }
.result-box .actions { margin-top: 16px; display: flex; gap: 10px; flex-wrap: wrap; }
.result-box .actions button {
    background: rgba(128,90,255,0.2); border: 1px solid rgba(128,90,255,0.4);
    color: #c084fc; padding: 8px 16px; border-radius: 8px; cursor: pointer;
    font-size: 0.85rem; transition: all 0.2s;
}
.result-box .actions button:hover { background: rgba(128,90,255,0.35); }
.status-msg { margin-top: 12px; font-size: 0.9rem; text-align: center; }
.status-msg.error { color: #f87171; }
.status-msg.success { color: #34d399; }
.status-msg.processing { color: #fbbf24; }
.history-list { margin-top: 24px; }
.history-item {
    background: rgba(0,0,0,0.2); border-radius: 8px; padding: 12px; margin-bottom: 8px;
    border: 1px solid rgba(128,90,255,0.1); cursor: pointer; transition: all 0.2s;
}
.history-item:hover { border-color: rgba(128,90,255,0.3); }
.history-item .hi-meta { color: #9ca3af; font-size: 0.75rem; }
.history-item .hi-preview { color: #d1d5db; font-size: 0.9rem; margin-top: 4px; }
.record-section { margin-top: 16px; text-align: center; }
.record-btn {
    background: linear-gradient(135deg, #7c3aed, #c084fc); border: none;
    color: white; padding: 14px 28px; border-radius: 50px; cursor: pointer;
    font-size: 1rem; font-weight: 600; transition: all 0.2s;
}
.record-btn:hover { transform: scale(1.05); }
.record-btn.recording { background: linear-gradient(135deg, #dc2626, #f87171); animation: pulse 1.2s infinite; }
@keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: 0.7; } }
.options-row {
    display: flex; gap: 12px; margin-top: 16px; flex-wrap: wrap; align-items: center; justify-content: center;
}
.options-row select, .options-row label {
    background: rgba(128,90,255,0.1); border: 1px solid rgba(128,90,255,0.3);
    color: #c084fc; padding: 8px 14px; border-radius: 8px; font-size: 0.85rem;
    cursor: pointer; transition: all 0.2s;
}
.options-row select:hover, .options-row label:hover { background: rgba(128,90,255,0.2); }
.options-row select option { background: #1a1040; color: #e0e0e0; }
.toggle-label { display: flex; align-items: center; gap: 6px; user-select: none; }
.toggle-label input[type=checkbox] { accent-color: #c084fc; width: 16px; height: 16px; }
.audio-preview {
    margin-top: 16px; display: none; background: rgba(0,0,0,0.25); border-radius: 10px;
    padding: 14px; border: 1px solid rgba(128,90,255,0.15);
}
.audio-preview audio { width: 100%; height: 40px; border-radius: 8px; }
.audio-preview .ap-label { color: #9ca3af; font-size: 0.78rem; margin-bottom: 8px; }
.word-stats {
    display: flex; gap: 16px; margin-top: 8px; padding: 8px 0;
    border-top: 1px solid rgba(128,90,255,0.1); font-size: 0.78rem; color: #9ca3af;
}
.word-stats span { display: flex; align-items: center; gap: 4px; }
.history-item .hi-full {
    display: none; color: #d1d5db; font-size: 0.85rem; margin-top: 8px;
    white-space: pre-wrap; line-height: 1.5; padding: 10px;
    background: rgba(0,0,0,0.2); border-radius: 6px; max-height: 300px; overflow-y: auto;
}
.history-item.expanded .hi-full { display: block; }
.history-item .hi-toggle {
    color: #c084fc; font-size: 0.75rem; margin-top: 6px; cursor: pointer; display: inline-block;
}
.history-item .hi-toggle:hover { text-decoration: underline; }
</style>

<div class="voice-upload-container">
    <h1>🎙️ Alfred's Ears</h1>
    <p class="subtitle">Upload or record audio — Alfred will transcribe it instantly.</p>

    <!-- Drop Zone -->
    <div class="drop-zone" id="dropZone">
        <div class="icon">📁</div>
        <div class="label">Drop an audio file here, or click to browse</div>
        <div class="hint">MP3, WAV, M4A, OGG, FLAC, WebM — up to 25 MB</div>
    </div>
    <input type="file" class="file-input" id="fileInput" accept="audio/*,.mp3,.wav,.m4a,.ogg,.flac,.webm,.mp4">

    <!-- Options Row -->
    <div class="options-row">
        <select id="langSelect" title="Language hint (auto-detect if not set)">
            <option value="">🌐 Auto-detect</option>
            <option value="en">English</option>
            <option value="fr">Français</option>
            <option value="es">Español</option>
            <option value="de">Deutsch</option>
            <option value="pt">Português</option>
            <option value="it">Italiano</option>
            <option value="nl">Nederlands</option>
            <option value="ja">日本語</option>
            <option value="zh">中文</option>
            <option value="ko">한국어</option>
            <option value="ar">العربية</option>
            <option value="ru">Русский</option>
            <option value="hi">हिन्दी</option>
            <option value="tr">Türkçe</option>
            <option value="pl">Polski</option>
            <option value="uk">Українська</option>
            <option value="sv">Svenska</option>
        </select>
        <label class="toggle-label" title="Translate any language to English">
            <input type="checkbox" id="translateToggle"> 🔄 Translate to English
        </label>
    </div>

    <!-- Record Button -->
    <div class="record-section">
        <span style="color: #6b7280; font-size: 0.85rem;">— or —</span><br><br>
        <button class="record-btn" id="recordBtn">🎤 Record Voice</button>
        <div id="recordTimer" style="color: #fbbf24; font-size: 0.85rem; margin-top: 8px; display: none;"></div>
    </div>

    <!-- Audio Preview -->
    <div class="audio-preview" id="audioPreview">
        <div class="ap-label" id="apLabel">🔊 Audio Preview</div>
        <audio controls id="audioPlayer"></audio>
    </div>

    <!-- Progress -->
    <div class="progress-bar" id="progressBar"><div class="fill" id="progressFill"></div></div>
    <div class="status-msg" id="statusMsg"></div>

    <!-- Result -->
    <div class="result-box" id="resultBox">
        <div class="meta" id="resultMeta"></div>
        <div class="transcript" id="resultText"></div>
        <div class="word-stats" id="wordStats"></div>
        <div class="actions">
            <button onclick="copyTranscript()">📋 Copy</button>
            <button onclick="sendToAlfred()">🎩 Send to Alfred</button>
            <button onclick="downloadTranscript()">💾 Download</button>
            <button onclick="retranslate()" id="retranslateBtn" style="display:none">🔄 Translate to English</button>
        </div>
    </div>

    <!-- History (Commander only) -->
    <?php if ($isCommander): ?>
    <div class="history-list" id="historyList">
        <h3 style="color: #c084fc; margin-bottom: 12px;">📜 Recent Transcriptions</h3>
        <div id="historyItems">Loading...</div>
    </div>
    <?php endif; ?>
</div>

<script>
(function() {
'use strict';

const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const progressBar = document.getElementById('progressBar');
const progressFill = document.getElementById('progressFill');
const statusMsg = document.getElementById('statusMsg');
const resultBox = document.getElementById('resultBox');
const resultMeta = document.getElementById('resultMeta');
const resultText = document.getElementById('resultText');
const recordBtn = document.getElementById('recordBtn');

let lastTranscript = '';
let lastAudioFile = null;
let lastResultData = null;
let mediaRecorder = null;
let audioChunks = [];
let recordStartTime = 0;
let recordTimerInterval = null;

// ── Drop Zone ──────────────────────────────────────────────────
dropZone.addEventListener('click', () => fileInput.click());
dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('drag-over'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
dropZone.addEventListener('drop', (e) => {
    e.preventDefault(); dropZone.classList.remove('drag-over');
    if (e.dataTransfer.files.length) {
        const f = e.dataTransfer.files[0];
        showAudioPreview(f, f.name);
        transcribeFile(f);
    }
});
fileInput.addEventListener('change', () => {
    if (fileInput.files.length) {
        const f = fileInput.files[0];
        showAudioPreview(f, f.name);
        transcribeFile(f);
    }
});

// ── Record ─────────────────────────────────────────────────────
recordBtn.addEventListener('click', toggleRecording);

async function toggleRecording() {
    if (mediaRecorder && mediaRecorder.state === 'recording') {
        mediaRecorder.stop();
        recordBtn.classList.remove('recording');
        recordBtn.textContent = '🎤 Record Voice';
        clearInterval(recordTimerInterval);
        document.getElementById('recordTimer').style.display = 'none';
        return;
    }

    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        // Prefer webm/opus, fall back to whatever browser supports
        const mimeType = MediaRecorder.isTypeSupported('audio/webm;codecs=opus') ? 'audio/webm;codecs=opus'
            : MediaRecorder.isTypeSupported('audio/webm') ? 'audio/webm'
            : MediaRecorder.isTypeSupported('audio/mp4') ? 'audio/mp4' : '';

        mediaRecorder = new MediaRecorder(stream, mimeType ? { mimeType } : {});
        audioChunks = [];

        mediaRecorder.ondataavailable = (e) => { if (e.data.size > 0) audioChunks.push(e.data); };
        mediaRecorder.onstop = () => {
            stream.getTracks().forEach(t => t.stop());
            const ext = mimeType.includes('mp4') ? 'mp4' : 'webm';
            const blob = new Blob(audioChunks, { type: mimeType || 'audio/webm' });
            const file = new File([blob], `recording-${Date.now()}.${ext}`, { type: blob.type });
            showAudioPreview(file, 'Recording');
            transcribeFile(file);
        };

        mediaRecorder.start(1000);
        recordBtn.classList.add('recording');
        recordBtn.textContent = '⏹️ Stop Recording';
        recordStartTime = Date.now();
        const timerEl = document.getElementById('recordTimer');
        timerEl.style.display = 'block';
        recordTimerInterval = setInterval(() => {
            const s = Math.floor((Date.now() - recordStartTime) / 1000);
            timerEl.textContent = `Recording: ${Math.floor(s/60)}:${(s%60).toString().padStart(2,'0')}`;
        }, 500);

        // Auto-stop after 10 minutes
        setTimeout(() => { if (mediaRecorder?.state === 'recording') recordBtn.click(); }, 600000);

    } catch (err) {
        setStatus('Microphone access denied: ' + err.message, 'error');
    }
}

// ── Audio Preview ─────────────────────────────────────────────
function showAudioPreview(file, label) {
    const preview = document.getElementById('audioPreview');
    const player = document.getElementById('audioPlayer');
    const apLabel = document.getElementById('apLabel');
    const url = URL.createObjectURL(file);
    player.src = url;
    apLabel.textContent = `🔊 ${label || file.name} (${(file.size/1024/1024).toFixed(1)} MB)`;
    preview.style.display = 'block';
    lastAudioFile = file;
}

// ── Transcribe ────────────────────────────────────────────────
async function transcribeFile(file) {
    const maxSize = 25 * 1024 * 1024;
    if (file.size > maxSize) { setStatus('File too large (max 25 MB)', 'error'); return; }

    const langSelect = document.getElementById('langSelect');
    const translateToggle = document.getElementById('translateToggle');
    const isTranslate = translateToggle.checked;

    setStatus(`${isTranslate ? 'Translating' : 'Transcribing'} "${file.name}" (${(file.size/1024/1024).toFixed(1)} MB)...`, 'processing');
    progressBar.style.display = 'block';
    progressFill.style.width = '30%';
    resultBox.style.display = 'none';

    const formData = new FormData();
    formData.append('audio', file);
    formData.append('source', 'web-upload');
    if (langSelect.value) formData.append('language', langSelect.value);
    if (isTranslate) formData.append('translate', '1');

    try {
        const resp = await fetch('/api/alfred-transcribe.php', {
            method: 'POST',
            body: formData,
        });
        progressFill.style.width = '90%';

        const data = await resp.json();
        progressFill.style.width = '100%';

        if (data.ok) {
            lastTranscript = data.text;
            lastResultData = data;
            resultMeta.textContent = [
                data.language ? `Language: ${data.language}` : '',
                data.duration ? `Duration: ${Math.round(data.duration)}s` : '',
                data.provider ? `Provider: ${data.provider}` : '',
                data.filename ? `File: ${data.filename}` : '',
                isTranslate ? '🔄 Translated to English' : '',
            ].filter(Boolean).join(' · ');
            resultText.textContent = data.text;
            showWordStats(data.text, data.duration);
            resultBox.style.display = 'block';
            // Show retranslate button if not already translated and language isn't English
            const retBtn = document.getElementById('retranslateBtn');
            retBtn.style.display = (!isTranslate && data.language && data.language.toLowerCase() !== 'english' && data.language !== 'en') ? '' : 'none';
            setStatus(`✅ ${isTranslate ? 'Translation' : 'Transcription'} complete!`, 'success');
            if (document.getElementById('historyItems')) loadHistory();
        } else {
            setStatus('❌ ' + (data.error || 'Transcription failed'), 'error');
        }
    } catch (err) {
        setStatus('❌ Network error: ' + err.message, 'error');
    }

    setTimeout(() => { progressBar.style.display = 'none'; progressFill.style.width = '0%'; }, 1500);
}

function showWordStats(text, duration) {
    const words = text.trim().split(/\s+/).filter(w => w.length > 0).length;
    const chars = text.length;
    const readMin = Math.max(1, Math.round(words / 200));
    const wpm = duration > 0 ? Math.round(words / (duration / 60)) : null;
    let html = `<span>📝 ${words.toLocaleString()} words</span>`;
    html += `<span>📖 ~${readMin} min read</span>`;
    html += `<span>🔤 ${chars.toLocaleString()} chars</span>`;
    if (wpm) html += `<span>🗣️ ${wpm} WPM</span>`;
    document.getElementById('wordStats').innerHTML = html;
}

function setStatus(msg, cls) {
    statusMsg.textContent = msg;
    statusMsg.className = 'status-msg ' + (cls || '');
}

// ── Actions ────────────────────────────────────────────────────
window.copyTranscript = function() {
    navigator.clipboard.writeText(lastTranscript).then(() => setStatus('Copied!', 'success'));
};

window.downloadTranscript = function() {
    const blob = new Blob([lastTranscript], { type: 'text/plain' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'transcript-' + new Date().toISOString().slice(0,10) + '.txt';
    a.click(); URL.revokeObjectURL(a.href);
};

window.sendToAlfred = function() {
    if (window.AlfredChat && typeof window.AlfredChat.open === 'function') {
        window.AlfredChat.open(lastTranscript);
    } else {
        // Encode transcript into URL param for Alfred Voice Live
        const encoded = encodeURIComponent(lastTranscript.substring(0, 2000));
        navigator.clipboard.writeText(lastTranscript);
        setStatus('Transcript copied! Opening Alfred Voice Live...', 'success');
        setTimeout(() => window.location.href = '/alfred-voice-live/?transcript=' + encoded, 500);
    }
};

window.retranslate = async function() {
    if (!lastAudioFile) { setStatus('No audio to retranslate — upload or record again.', 'error'); return; }
    document.getElementById('translateToggle').checked = true;
    transcribeFile(lastAudioFile);
};

// ── History (Commander) ────────────────────────────────────────
async function loadHistory() {
    const el = document.getElementById('historyItems');
    if (!el) return;
    try {
        const resp = await fetch('/api/alfred-transcribe.php?action=list&limit=10');
        const data = await resp.json();
        if (!data.ok || !data.transcriptions?.length) {
            el.innerHTML = '<div style="color:#6b7280;font-size:0.85rem;">No transcriptions yet.</div>';
            return;
        }
        el.innerHTML = data.transcriptions.map(t => {
            const preview = (t.preview || '').substring(0, 150);
            const hasMore = (t.preview || '').length > 150;
            const safePreview = preview.replace(/</g,'&lt;').replace(/>/g,'&gt;');
            return `
            <div class="history-item" data-id="${parseInt(t.id)}">
                <div class="hi-meta">${escHtml(t.source)} · ${escHtml(t.language || '?')} · ${Math.round(t.duration_seconds || 0)}s · ${escHtml(t.provider || '?')} · ${new Date(t.created_at).toLocaleString()}</div>
                <div class="hi-preview">${safePreview}${hasMore ? '...' : ''}</div>
                <div class="hi-full"></div>
                <span class="hi-toggle" onclick="toggleHistoryItem(this, ${parseInt(t.id)})">▶ Show full transcript</span>
            </div>`;
        }).join('');
    } catch (e) {
        el.innerHTML = '<div style="color:#f87171;">Failed to load history.</div>';
    }
}

function escHtml(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

window.toggleHistoryItem = async function(el, id) {
    const item = el.closest('.history-item');
    if (item.classList.contains('expanded')) {
        item.classList.remove('expanded');
        el.textContent = '▶ Show full transcript';
        return;
    }
    const fullDiv = item.querySelector('.hi-full');
    if (!fullDiv.textContent) {
        el.textContent = '⏳ Loading...';
        try {
            const resp = await fetch('/api/alfred-transcribe.php?action=get&id=' + id);
            const data = await resp.json();
            if (data.ok && data.transcription) {
                fullDiv.textContent = data.transcription.transcript;
            } else {
                fullDiv.textContent = '[Could not load transcript]';
            }
        } catch (e) {
            fullDiv.textContent = '[Network error]';
        }
    }
    item.classList.add('expanded');
    el.textContent = '▼ Hide transcript';
};

// Initial history load
if (document.getElementById('historyItems')) loadHistory();

})();
</script>

<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>
