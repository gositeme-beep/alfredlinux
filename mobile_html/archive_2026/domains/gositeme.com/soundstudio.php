<?php
/**
 * SoundStudioPro — Studio Interface
 * /soundstudio.php
 * Upload, analyze, visualize, separate, and play audio
 */
$page_title = 'SoundStudioPro — AI-Powered Music Studio | GoSiteMe';
$page_description = 'Upload, analyze, separate stems, transcribe lyrics, and stream your music with AI. Powered by Alfred.';
$page_canonical = 'https://gositeme.com/soundstudio';
include __DIR__ . '/includes/site-header.inc.php';
?>

<link rel="stylesheet" href="/assets/vendor/fontawesome/6.5.1/css/all.min.css">
<script src="/assets/vendor/wavesurfer/7/dist/wavesurfer.esm.js" type="module"></script>

<style>
    .ssp-hero { padding: 120px 0 60px; text-align: center; }
    .ssp-hero h1 { font-family: 'Space Grotesk', sans-serif; font-size: clamp(2rem, 4vw, 3rem); font-weight: 900; background: linear-gradient(135deg, #ff6b6b, #c084fc, #00D4FF); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 12px; }
    .ssp-hero p { color: #a8b2d1; max-width: 600px; margin: 0 auto; }

    .ssp-container { max-width: 1000px; margin: 0 auto; padding: 0 24px 60px; }

    /* Upload Zone */
    .ssp-upload { border: 2px dashed rgba(125,0,255,0.3); border-radius: 16px; padding: 48px; text-align: center; cursor: pointer; transition: all 0.3s; margin-bottom: 32px; background: rgba(26,26,46,0.5); }
    .ssp-upload:hover, .ssp-upload.dragover { border-color: #7D00FF; background: rgba(125,0,255,0.05); }
    .ssp-upload i { font-size: 2.5rem; color: #7D00FF; margin-bottom: 12px; }
    .ssp-upload h3 { color: #fff; margin-bottom: 8px; }
    .ssp-upload p { color: #a8b2d1; font-size: 0.85rem; }
    .ssp-upload input[type="file"] { display: none; }
    .ssp-upload .progress-bar { display: none; height: 4px; background: rgba(125,0,255,0.2); border-radius: 2px; margin-top: 16px; overflow: hidden; }
    .ssp-upload .progress-bar .fill { height: 100%; background: linear-gradient(90deg, #7D00FF, #00D4FF); width: 0; transition: width 0.3s; }

    /* Player Card */
    .ssp-player { background: rgba(26,26,46,0.8); border: 1px solid rgba(255,255,255,0.06); border-radius: 16px; padding: 28px; margin-bottom: 24px; display: none; }
    .ssp-player.active { display: block; }
    .ssp-player-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
    .ssp-player-title { font-size: 1.2rem; font-weight: 700; color: #fff; }
    .ssp-player-artist { color: #a8b2d1; font-size: 0.9rem; }
    .ssp-player-controls { display: flex; align-items: center; gap: 12px; margin: 16px 0; }
    .ssp-btn { background: linear-gradient(135deg, #7D00FF, #5B00CC); color: #fff; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px; }
    .ssp-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(125,0,255,0.3); }
    .ssp-btn.secondary { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); }
    .ssp-btn.secondary:hover { border-color: #7D00FF; }
    .ssp-btn i { font-size: 0.85rem; }
    .ssp-time { color: #a8b2d1; font-size: 0.85rem; font-family: 'JetBrains Mono', monospace; }

    #waveform { margin: 12px 0; border-radius: 8px; overflow: hidden; }

    /* Analysis Grid */
    .ssp-analysis { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-top: 20px; }
    .ssp-stat { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); border-radius: 12px; padding: 16px; text-align: center; }
    .ssp-stat .val { font-size: 1.4rem; font-weight: 900; color: #00D4FF; font-family: 'Space Grotesk', sans-serif; }
    .ssp-stat .lbl { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; color: rgba(255,255,255,0.4); margin-top: 4px; }

    /* Stems Section */
    .ssp-stems { display: none; margin-top: 24px; }
    .ssp-stems.active { display: block; }
    .ssp-stem-row { display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: rgba(255,255,255,0.03); border-radius: 8px; margin-bottom: 8px; }
    .ssp-stem-row .name { color: #fff; font-weight: 600; min-width: 80px; }
    .ssp-stem-row .wave { flex: 1; height: 40px; }
    .ssp-stem-row .actions { display: flex; gap: 8px; }

    /* Transcription */
    .ssp-lyrics { display: none; margin-top: 24px; background: rgba(26,26,46,0.8); border: 1px solid rgba(255,255,255,0.06); border-radius: 16px; padding: 24px; max-height: 400px; overflow-y: auto; }
    .ssp-lyrics.active { display: block; }
    .ssp-lyrics h3 { color: #fff; margin-bottom: 12px; }
    .ssp-lyrics .line { padding: 4px 0; color: #a8b2d1; font-size: 0.9rem; cursor: pointer; transition: color 0.2s; }
    .ssp-lyrics .line:hover { color: #00D4FF; }
    .ssp-lyrics .line .ts { color: #7D00FF; font-size: 0.75rem; font-family: monospace; margin-right: 8px; }

    /* Track List */
    .ssp-tracklist { margin-top: 40px; }
    .ssp-tracklist h2 { color: #fff; font-size: 1.3rem; margin-bottom: 16px; }
    .ssp-track-item { display: flex; align-items: center; gap: 16px; padding: 14px 16px; background: rgba(26,26,46,0.5); border: 1px solid rgba(255,255,255,0.04); border-radius: 12px; margin-bottom: 8px; cursor: pointer; transition: all 0.2s; }
    .ssp-track-item:hover { border-color: rgba(125,0,255,0.3); background: rgba(26,26,46,0.8); }
    .ssp-track-item .play-icon { color: #7D00FF; font-size: 1.2rem; }
    .ssp-track-item .info { flex: 1; }
    .ssp-track-item .info .title { color: #fff; font-weight: 600; }
    .ssp-track-item .info .meta { color: #a8b2d1; font-size: 0.8rem; }
    .ssp-track-item .tags { display: flex; gap: 6px; }
    .ssp-tag { padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: 600; }
    .ssp-tag.bpm { background: rgba(255,107,107,0.15); color: #ff6b6b; }
    .ssp-tag.key { background: rgba(0,212,255,0.15); color: #00D4FF; }
    .ssp-tag.energy { background: rgba(192,132,252,0.15); color: #c084fc; }

    /* Loading */
    .ssp-loading { display: none; text-align: center; padding: 20px; color: #a8b2d1; }
    .ssp-loading.active { display: block; }
    .ssp-loading .spinner { display: inline-block; width: 20px; height: 20px; border: 2px solid rgba(125,0,255,0.3); border-top-color: #7D00FF; border-radius: 50%; animation: spin 0.8s linear infinite; margin-right: 8px; vertical-align: middle; }
    @keyframes spin { to { transform: rotate(360deg); } }

    @media (max-width: 768px) {
        .ssp-analysis { grid-template-columns: repeat(2, 1fr); }
        .ssp-player-controls { flex-wrap: wrap; }
    }
</style>

<section class="ssp-hero">
    <div class="container">
        <h1><i class="fas fa-headphones"></i> SoundStudioPro</h1>
        <p>Upload your music. Alfred analyzes BPM, key, mood. Separate stems. Extract lyrics. All AI-powered, all on our server.</p>
    </div>
</section>

<div class="ssp-container">

    <!-- Upload Zone -->
    <div class="ssp-upload" id="uploadZone">
        <i class="fas fa-cloud-arrow-up"></i>
        <h3>Drop your audio here</h3>
        <p>MP3, WAV, FLAC, OGG, AAC, M4A, WEBM — up to 100MB</p>
        <input type="file" id="audioInput" accept="audio/*">
        <div class="progress-bar"><div class="fill"></div></div>
    </div>

    <!-- Player -->
    <div class="ssp-player" id="playerCard">
        <div class="ssp-player-header">
            <div>
                <div class="ssp-player-title" id="trackTitle">—</div>
                <div class="ssp-player-artist" id="trackArtist">—</div>
            </div>
            <div class="ssp-time"><span id="currentTime">0:00</span> / <span id="totalTime">0:00</span></div>
        </div>
        
        <div id="waveform"></div>
        
        <div class="ssp-player-controls">
            <button class="ssp-btn" id="btnPlay"><i class="fas fa-play"></i> Play</button>
            <button class="ssp-btn secondary" id="btnAnalyze"><i class="fas fa-chart-bar"></i> Analyze</button>
            <button class="ssp-btn secondary" id="btnTranscribe"><i class="fas fa-closed-captioning"></i> Lyrics</button>
            <button class="ssp-btn secondary" id="btnStems"><i class="fas fa-layer-group"></i> Separate Stems</button>
        </div>

        <!-- Analysis Results -->
        <div class="ssp-analysis" id="analysisGrid" style="display:none;"></div>
        
        <div class="ssp-loading" id="analyzeLoading"><span class="spinner"></span> Alfred is analyzing your track...</div>
        <div class="ssp-loading" id="transcribeLoading"><span class="spinner"></span> Transcribing lyrics (this may take a minute)...</div>
        <div class="ssp-loading" id="stemsLoading"><span class="spinner"></span> Separating stems with Demucs (~6 min per song)...</div>
    </div>

    <!-- Lyrics -->
    <div class="ssp-lyrics" id="lyricsPanel">
        <h3><i class="fas fa-microphone-alt"></i> Lyrics / Transcription</h3>
        <div id="lyricsContent"></div>
    </div>

    <!-- Stems -->
    <div class="ssp-stems" id="stemsPanel">
        <h3 style="color:#fff; margin-bottom:12px;"><i class="fas fa-layer-group"></i> Stems</h3>
        <div id="stemsContent"></div>
    </div>

    <!-- Track Library -->
    <div class="ssp-tracklist" id="trackList">
        <h2><i class="fas fa-music"></i> Your Library</h2>
        <div id="trackListContent">
            <p style="color:#a8b2d1; text-align:center; padding:20px;">Upload a track to get started.</p>
        </div>
    </div>

</div>

<script type="module">
import WaveSurfer from '/assets/vendor/wavesurfer/7/dist/wavesurfer.esm.js';

const API = '/api/audio.php';
let currentTrackId = null;
let wavesurfer = null;

// ---- Upload Zone ----
const uploadZone = document.getElementById('uploadZone');
const audioInput = document.getElementById('audioInput');
const progressBar = uploadZone.querySelector('.progress-bar');
const progressFill = uploadZone.querySelector('.fill');

uploadZone.addEventListener('click', () => audioInput.click());
uploadZone.addEventListener('dragover', e => { e.preventDefault(); uploadZone.classList.add('dragover'); });
uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('dragover'));
uploadZone.addEventListener('drop', e => {
    e.preventDefault();
    uploadZone.classList.remove('dragover');
    if (e.dataTransfer.files.length) uploadFile(e.dataTransfer.files[0]);
});
audioInput.addEventListener('change', () => { if (audioInput.files.length) uploadFile(audioInput.files[0]); });

async function uploadFile(file) {
    const formData = new FormData();
    formData.append('audio', file);
    formData.append('title', file.name.replace(/\.[^.]+$/, ''));
    
    progressBar.style.display = 'block';
    progressFill.style.width = '0%';
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', `${API}?action=upload`);
    
    xhr.upload.onprogress = e => {
        if (e.lengthComputable) {
            progressFill.style.width = (e.loaded / e.total * 100) + '%';
        }
    };
    
    xhr.onload = () => {
        progressBar.style.display = 'none';
        try {
            const res = JSON.parse(xhr.responseText);
            if (res.success) {
                currentTrackId = res.track_id;
                loadTrack(res);
                loadTrackList();
            } else {
                alert(res.error || 'Upload failed');
            }
        } catch (e) {
            alert('Upload error');
        }
    };
    
    xhr.send(formData);
}

// ---- Player ----
function loadTrack(track) {
    document.getElementById('playerCard').classList.add('active');
    document.getElementById('trackTitle').textContent = track.title || 'Untitled';
    document.getElementById('trackArtist').textContent = track.artist || '';
    
    // Init Wavesurfer
    if (wavesurfer) wavesurfer.destroy();
    
    wavesurfer = WaveSurfer.create({
        container: '#waveform',
        waveColor: '#4a3a6b',
        progressColor: '#7D00FF',
        cursorColor: '#00D4FF',
        height: 80,
        barWidth: 2,
        barGap: 1,
        barRadius: 2,
        responsive: true,
        url: `/audio/uploads/${track.track_id || currentTrackId}.${track.format || 'mp3'}`
    });
    
    wavesurfer.on('timeupdate', t => {
        document.getElementById('currentTime').textContent = formatTime(t);
    });
    wavesurfer.on('ready', () => {
        document.getElementById('totalTime').textContent = formatTime(wavesurfer.getDuration());
    });
    
    // Auto-analyze
    analyzeTrack();
}

function formatTime(s) {
    const m = Math.floor(s / 60);
    const sec = Math.floor(s % 60);
    return `${m}:${sec < 10 ? '0' : ''}${sec}`;
}

// Play button
document.getElementById('btnPlay').addEventListener('click', () => {
    if (!wavesurfer) return;
    wavesurfer.playPause();
    const btn = document.getElementById('btnPlay');
    const playing = wavesurfer.isPlaying();
    btn.innerHTML = playing ? '<i class="fas fa-pause"></i> Pause' : '<i class="fas fa-play"></i> Play';
});

// ---- Analyze ----
document.getElementById('btnAnalyze').addEventListener('click', analyzeTrack);

async function analyzeTrack() {
    if (!currentTrackId) return;
    const loading = document.getElementById('analyzeLoading');
    loading.classList.add('active');
    
    try {
        const res = await fetch(`${API}?action=analyze`, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `track_id=${currentTrackId}`
        });
        const data = await res.json();
        
        if (data.success && data.analysis) {
            const a = data.analysis;
            const grid = document.getElementById('analysisGrid');
            grid.style.display = 'grid';
            grid.innerHTML = `
                <div class="ssp-stat"><div class="val">${a.bpm}</div><div class="lbl">BPM</div></div>
                <div class="ssp-stat"><div class="val">${a.key}</div><div class="lbl">Key</div></div>
                <div class="ssp-stat"><div class="val">${a.duration_formatted}</div><div class="lbl">Duration</div></div>
                <div class="ssp-stat"><div class="val">${a.energy_db} dB</div><div class="lbl">Energy</div></div>
                <div class="ssp-stat"><div class="val">${a.beat_count}</div><div class="lbl">Beats</div></div>
                <div class="ssp-stat"><div class="val">${a.sample_rate}</div><div class="lbl">Sample Rate</div></div>
            `;
        }
    } catch (e) {
        console.error('Analyze error:', e);
    }
    
    loading.classList.remove('active');
}

// ---- Transcribe ----
document.getElementById('btnTranscribe').addEventListener('click', async () => {
    if (!currentTrackId) return;
    const loading = document.getElementById('transcribeLoading');
    loading.classList.add('active');
    
    try {
        const res = await fetch(`${API}?action=transcribe`, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `track_id=${currentTrackId}`
        });
        const data = await res.json();
        
        if (data.job_id) {
            // Poll for completion
            pollJob(data.job_id, 'transcribe');
        }
    } catch (e) {
        console.error('Transcribe error:', e);
        loading.classList.remove('active');
    }
});

// ---- Stems ----
document.getElementById('btnStems').addEventListener('click', async () => {
    if (!currentTrackId) return;
    const loading = document.getElementById('stemsLoading');
    loading.classList.add('active');
    
    try {
        const res = await fetch(`${API}?action=stems`, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `track_id=${currentTrackId}`
        });
        const data = await res.json();
        
        if (data.job_id) {
            pollJob(data.job_id, 'stems');
        }
    } catch (e) {
        console.error('Stems error:', e);
        loading.classList.remove('active');
    }
});

// ---- Job Polling ----
async function pollJob(jobId, type) {
    const interval = setInterval(async () => {
        try {
            const res = await fetch(`${API}?action=status&job_id=${jobId}`);
            const data = await res.json();
            
            if (data.status === 'completed') {
                clearInterval(interval);
                
                if (type === 'transcribe' && data.result) {
                    showTranscription(data.result);
                    document.getElementById('transcribeLoading').classList.remove('active');
                } else if (type === 'stems' && data.result) {
                    showStems(data.result);
                    document.getElementById('stemsLoading').classList.remove('active');
                }
            } else if (data.status === 'failed') {
                clearInterval(interval);
                document.getElementById(`${type}Loading`).classList.remove('active');
                alert(`${type} failed: ${data.error || 'Unknown error'}`);
            }
        } catch (e) {
            // keep polling
        }
    }, 3000);
}

function showTranscription(result) {
    const panel = document.getElementById('lyricsPanel');
    const content = document.getElementById('lyricsContent');
    panel.classList.add('active');
    
    if (result.segments) {
        content.innerHTML = result.segments.map(s => 
            `<div class="line" data-start="${s.start}"><span class="ts">${formatTime(s.start)}</span>${s.text}</div>`
        ).join('');
        
        // Click to seek
        content.querySelectorAll('.line').forEach(line => {
            line.addEventListener('click', () => {
                if (wavesurfer) wavesurfer.seekTo(parseFloat(line.dataset.start) / wavesurfer.getDuration());
            });
        });
    } else {
        content.innerHTML = `<p style="color:#a8b2d1">${result.full_text || 'No lyrics detected.'}</p>`;
    }
}

function showStems(result) {
    const panel = document.getElementById('stemsPanel');
    const content = document.getElementById('stemsContent');
    panel.classList.add('active');
    
    if (result.stems) {
        content.innerHTML = Object.entries(result.stems).map(([name, path]) => `
            <div class="ssp-stem-row">
                <span class="name" style="text-transform:capitalize">${name}</span>
                <div class="wave" id="stem-${name}"></div>
                <div class="actions">
                    <a href="${path}" download class="ssp-btn secondary" style="padding:6px 12px; font-size:0.8rem;"><i class="fas fa-download"></i></a>
                </div>
            </div>
        `).join('');
    }
}

// ---- Track List ----
async function loadTrackList() {
    try {
        const res = await fetch(`${API}?action=tracks`);
        const data = await res.json();
        const content = document.getElementById('trackListContent');
        
        if (data.tracks && data.tracks.length > 0) {
            content.innerHTML = data.tracks.map(t => `
                <div class="ssp-track-item" data-id="${t.track_id}">
                    <i class="fas fa-play-circle play-icon"></i>
                    <div class="info">
                        <div class="title">${t.title}</div>
                        <div class="meta">${t.artist} · ${formatTime(t.duration)} · ${t.format}</div>
                    </div>
                    <div class="tags">
                        ${t.bpm ? `<span class="ssp-tag bpm">${t.bpm} BPM</span>` : ''}
                        ${t.musical_key ? `<span class="ssp-tag key">${t.musical_key}</span>` : ''}
                    </div>
                </div>
            `).join('');
        }
    } catch (e) {
        console.error('Track list error:', e);
    }
}

// Load tracks on page load
loadTrackList();
</script>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
