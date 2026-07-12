<?php
/**
 * ═══════════════════════════════════════════════════════════════
 *  ALFRED LIVESTREAM — Commander Control Panel
 *  Start/stop streams on TikTok, Instagram, YouTube, or RTMP
 *  Make Alfred speak, manage chat, set stream keys
 *  Commander-only access (client_id=33)
 * ═══════════════════════════════════════════════════════════════
 */

session_start();

// Commander-only access
$isCommander = false;
if (isset($_SESSION['uid']) && $_SESSION['uid'] == 33) $isCommander = true;
if (isset($_SESSION['client_id']) && $_SESSION['client_id'] == 33) $isCommander = true;
if (isset($_SESSION['discord_admin'])) $isCommander = true;

if (!$isCommander) {
    http_response_code(403);
    echo '<!DOCTYPE html><html><body style="background:#0a0a1a;color:#e53e3e;font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh"><h1>🔒 Commander Access Required</h1></body></html>';
    exit;
}

// API proxy — forward requests to alfred-livestream service
if (isset($_GET['api'])) {
    $endpoint = $_GET['api'];
    $allowed = ['status', 'start', 'stop', 'speak', 'chat', 'config', 'chat-log'];
    if (!in_array($endpoint, $allowed)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid endpoint']);
        exit;
    }

    $url = "http://127.0.0.1:3100/{$endpoint}";
    $method = $_SERVER['REQUEST_METHOD'];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    if ($method === 'POST') {
        $input = file_get_contents('php://input');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    header('Content-Type: application/json');
    http_response_code($httpCode ?: 502);
    echo $response ?: json_encode(['error' => 'Service unreachable']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alfred Livestream — Commander Control</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #0a0a1a;
            color: #e0e0ff;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            padding: 24px;
        }
        h1 { font-size: 1.8rem; margin-bottom: 8px; }
        h1 span { color: #7B61FF; }
        .subtitle { color: #666; margin-bottom: 24px; }
        
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; max-width: 1200px; }
        @media (max-width: 768px) { .grid { grid-template-columns: 1fr; } }
        
        .card {
            background: #111133;
            border: 1px solid #2a2a5e;
            border-radius: 16px;
            padding: 24px;
        }
        .card h2 {
            font-size: 1.1rem;
            color: #7B61FF;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        /* Status */
        .status-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .stat {
            background: #0d0d2a;
            border-radius: 10px;
            padding: 14px;
            text-align: center;
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
        }
        .stat-label {
            font-size: 0.75rem;
            color: #888;
            margin-top: 4px;
        }
        .stat.live .stat-value { color: #e53e3e; }
        .stat.speaking .stat-value { color: #7B61FF; }
        
        /* Buttons */
        button, .btn {
            background: #1a1a3e;
            border: 1px solid #3a3a7e;
            color: #e0e0ff;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        button:hover { border-color: #7B61FF; background: #222255; }
        button.primary { background: #7B61FF; border-color: #7B61FF; color: #fff; }
        button.primary:hover { background: #6a50ee; }
        button.danger { background: #e53e3e; border-color: #e53e3e; color: #fff; }
        button.danger:hover { background: #cc3333; }
        button:disabled { opacity: 0.5; cursor: not-allowed; }
        
        /* Platform selector */
        .platform-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-bottom: 16px;
        }
        .platform-btn {
            background: #0d0d2a;
            border: 2px solid #2a2a5e;
            border-radius: 12px;
            padding: 16px 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .platform-btn:hover { border-color: #7B61FF; }
        .platform-btn.selected { border-color: #7B61FF; background: #1a1a4e; }
        .platform-btn .icon { font-size: 2rem; display: block; margin-bottom: 6px; }
        .platform-btn .name { font-size: 0.8rem; color: #aaa; }
        
        /* RTMP config */
        .input-group {
            margin-bottom: 12px;
        }
        .input-group label {
            display: block;
            font-size: 0.8rem;
            color: #888;
            margin-bottom: 4px;
        }
        .input-group input {
            width: 100%;
            background: #0d0d2a;
            border: 1px solid #2a2a5e;
            color: #e0e0ff;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-family: monospace;
        }
        .input-group input:focus {
            outline: none;
            border-color: #7B61FF;
        }
        
        /* Chat & speak */
        textarea {
            width: 100%;
            background: #0d0d2a;
            border: 1px solid #2a2a5e;
            color: #e0e0ff;
            padding: 12px;
            border-radius: 8px;
            font-size: 0.9rem;
            resize: vertical;
            min-height: 60px;
            font-family: inherit;
        }
        textarea:focus { outline: none; border-color: #7B61FF; }
        
        /* Chat log */
        .chat-log {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #1a1a3e;
            border-radius: 8px;
            padding: 8px;
            background: #080820;
        }
        .chat-msg {
            padding: 6px 10px;
            border-bottom: 1px solid #1a1a3e;
            font-size: 0.85rem;
        }
        .chat-msg:last-child { border: none; }
        .chat-msg .user { color: #7B61FF; font-weight: 600; }
        .chat-msg .response { color: #22c55e; font-style: italic; }
        .chat-msg .time { color: #555; font-size: 0.7rem; }
        .chat-msg .platform-tag {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 4px;
            font-size: 0.65rem;
            margin-left: 4px;
        }
        .platform-tag.tiktok { background: #1a1a1a; color: #ee1d52; }
        .platform-tag.instagram { background: #1a1a1a; color: #e1306c; }
        .platform-tag.youtube { background: #1a1a1a; color: #ff0000; }
        
        /* Errors */
        .error-log {
            font-family: monospace;
            font-size: 0.75rem;
            color: #e53e3e;
            max-height: 120px;
            overflow-y: auto;
            background: #0d0d1a;
            border-radius: 6px;
            padding: 8px;
        }
        
        /* Flex helpers */
        .flex { display: flex; gap: 8px; align-items: center; }
        .flex-col { display: flex; flex-direction: column; gap: 12px; }
        .mt-12 { margin-top: 12px; }
        .mb-8 { margin-bottom: 8px; }
        
        .stream-actions { display: flex; gap: 12px; margin-top: 16px; }
        
        /* Live preview */
        .preview-frame {
            width: 100%;
            aspect-ratio: 1;
            border: 1px solid #2a2a5e;
            border-radius: 12px;
            overflow: hidden;
            background: #000;
        }
        .preview-frame iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        
        /* Invite URLs */
        .url-box {
            background: #0d0d2a;
            border: 1px solid #2a2a5e;
            border-radius: 8px;
            padding: 10px 14px;
            font-family: monospace;
            font-size: 0.75rem;
            word-break: break-all;
            color: #aaa;
            cursor: pointer;
        }
        .url-box:hover { border-color: #7B61FF; }
    </style>
</head>
<body>

<h1>📡 Alfred <span>Livestream</span></h1>
<p class="subtitle">Stream Alfred's AI avatar live to TikTok, Instagram, YouTube, or any RTMP endpoint</p>

<div class="grid">
    <!-- LEFT COLUMN -->
    <div class="flex-col">
        <!-- Status Card -->
        <div class="card">
            <h2>📊 Stream Status</h2>
            <div class="status-grid">
                <div class="stat" id="stat-live">
                    <div class="stat-value" id="live-status">OFFLINE</div>
                    <div class="stat-label">Status</div>
                </div>
                <div class="stat">
                    <div class="stat-value" id="uptime">0:00</div>
                    <div class="stat-label">Uptime</div>
                </div>
                <div class="stat">
                    <div class="stat-value" id="chat-count">0</div>
                    <div class="stat-label">Chat Messages</div>
                </div>
                <div class="stat speaking">
                    <div class="stat-value" id="speak-status">Idle</div>
                    <div class="stat-label">Voice</div>
                </div>
            </div>
            <div id="error-section" class="mt-12" style="display:none;">
                <div class="error-log" id="error-log"></div>
            </div>
        </div>
        
        <!-- Platform Selection -->
        <div class="card">
            <h2>🎯 Platform</h2>
            <div class="platform-grid">
                <div class="platform-btn" onclick="selectPlatform('tiktok')" id="plat-tiktok">
                    <span class="icon">🎵</span>
                    <span class="name">TikTok</span>
                </div>
                <div class="platform-btn" onclick="selectPlatform('instagram')" id="plat-instagram">
                    <span class="icon">📸</span>
                    <span class="name">Instagram</span>
                </div>
                <div class="platform-btn selected" onclick="selectPlatform('youtube')" id="plat-youtube">
                    <span class="icon">▶️</span>
                    <span class="name">YouTube</span>
                </div>
                <div class="platform-btn" onclick="selectPlatform('custom')" id="plat-custom">
                    <span class="icon">🔗</span>
                    <span class="name">Custom</span>
                </div>
            </div>
            
            <!-- RTMP config for selected platform -->
            <div id="rtmp-config">
                <div class="input-group">
                    <label>RTMP URL</label>
                    <input type="text" id="rtmp-url" placeholder="rtmp://...">
                </div>
                <div class="input-group">
                    <label>Stream Key</label>
                    <input type="password" id="rtmp-key" placeholder="Your stream key">
                </div>
                <div class="flex">
                    <button onclick="saveRtmpConfig()">💾 Save Keys</button>
                    <button onclick="document.getElementById('rtmp-key').type = document.getElementById('rtmp-key').type === 'password' ? 'text' : 'password'">👁️ Toggle</button>
                </div>
            </div>
            
            <div class="stream-actions">
                <button class="primary" id="btn-go-live" onclick="goLive()">🔴 Go Live</button>
                <button class="danger" id="btn-stop" onclick="endStream()" disabled>⏹ End Stream</button>
            </div>
        </div>
        
        <!-- Make Alfred Speak -->
        <div class="card">
            <h2>🗣️ Make Alfred Speak</h2>
            <textarea id="speak-text" placeholder="Type what Alfred should say on stream..." maxlength="500"></textarea>
            <div class="flex mt-12">
                <button class="primary" onclick="makeSpeak()">🗣️ Speak</button>
                <button onclick="quickSpeak('Hello everyone! Welcome to my live stream!')">👋 Hello</button>
                <button onclick="quickSpeak('Thank you so much for watching! I really appreciate it.')">🙏 Thanks</button>
            </div>
        </div>
    </div>
    
    <!-- RIGHT COLUMN -->
    <div class="flex-col">
        <!-- Live Preview -->
        <div class="card">
            <h2>👁️ Live Preview</h2>
            <div class="preview-frame">
                <iframe src="/alfred-voice-live/index.html" id="preview-iframe"></iframe>
            </div>
        </div>
        
        <!-- Chat Log -->
        <div class="card">
            <h2>💬 Live Chat</h2>
            <div class="chat-log" id="chat-log">
                <div class="chat-msg" style="color:#555;">No chat messages yet...</div>
            </div>
            <div class="flex mt-12">
                <input type="text" id="test-chat-user" placeholder="Username" style="width:100px;background:#0d0d2a;border:1px solid #2a2a5e;color:#e0e0ff;padding:8px;border-radius:6px;">
                <input type="text" id="test-chat-msg" placeholder="Test chat message..." style="flex:1;background:#0d0d2a;border:1px solid #2a2a5e;color:#e0e0ff;padding:8px;border-radius:6px;">
                <button onclick="sendTestChat()">Send</button>
            </div>
        </div>
        
        <!-- Quick Links -->
        <div class="card">
            <h2>🔗 Links</h2>
            <div class="flex-col">
                <div>
                    <label style="font-size:0.8rem;color:#888;">Avatar Page (normal)</label>
                    <div class="url-box" onclick="window.open('/alfred-voice-live/','_blank')">/alfred-voice-live/</div>
                </div>
                <div>
                    <label style="font-size:0.8rem;color:#888;">Avatar Page (stream mode)</label>
                    <div class="url-box" onclick="window.open('/alfred-voice-live/?stream=1','_blank')">/alfred-voice-live/?stream=1</div>
                </div>
                <div>
                    <label style="font-size:0.8rem;color:#888;">TikTok RTMP URL</label>
                    <div class="url-box">rtmp://push.tiktok.com/live/</div>
                </div>
                <div>
                    <label style="font-size:0.8rem;color:#888;">Instagram RTMP URL</label>
                    <div class="url-box">rtmp://live-upload.instagram.com/rtmp/</div>
                </div>
                <div>
                    <label style="font-size:0.8rem;color:#888;">YouTube RTMP URL</label>
                    <div class="url-box">rtmp://a.rtmp.youtube.com/live2/</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let selectedPlatform = 'youtube';
let refreshInterval;

const defaultUrls = {
    tiktok: 'rtmp://push.tiktok.com/live/',
    instagram: 'rtmp://live-upload.instagram.com/rtmp/',
    youtube: 'rtmp://a.rtmp.youtube.com/live2/',
    custom: '',
};

function selectPlatform(p) {
    document.querySelectorAll('.platform-btn').forEach(b => b.classList.remove('selected'));
    document.getElementById('plat-' + p).classList.add('selected');
    selectedPlatform = p;
    document.getElementById('rtmp-url').value = defaultUrls[p] || '';
    document.getElementById('rtmp-key').value = '';
}

async function api(endpoint, method = 'GET', data = null) {
    const opts = { method, headers: {'Content-Type': 'application/json'} };
    if (data) opts.body = JSON.stringify(data);
    try {
        const r = await fetch(`?api=${endpoint}`, opts);
        return await r.json();
    } catch (e) {
        return { error: 'Service unreachable — is alfred-livestream running in PM2?' };
    }
}

async function refreshStatus() {
    const s = await api('status');
    if (s.error) {
        document.getElementById('live-status').textContent = 'ERROR';
        document.getElementById('stat-live').className = 'stat';
        return;
    }
    
    // Update status
    const liveEl = document.getElementById('live-status');
    const statEl = document.getElementById('stat-live');
    if (s.streaming) {
        liveEl.textContent = '🔴 LIVE';
        liveEl.style.color = '#e53e3e';
        statEl.className = 'stat live';
        document.getElementById('btn-go-live').disabled = true;
        document.getElementById('btn-stop').disabled = false;
    } else {
        liveEl.textContent = 'OFFLINE';
        liveEl.style.color = '#888';
        statEl.className = 'stat';
        document.getElementById('btn-go-live').disabled = false;
        document.getElementById('btn-stop').disabled = true;
    }
    
    // Uptime
    if (s.uptimeSeconds > 0) {
        const h = Math.floor(s.uptimeSeconds / 3600);
        const m = Math.floor((s.uptimeSeconds % 3600) / 60);
        const sec = s.uptimeSeconds % 60;
        document.getElementById('uptime').textContent = h > 0 ? `${h}:${String(m).padStart(2,'0')}:${String(sec).padStart(2,'0')}` : `${m}:${String(sec).padStart(2,'0')}`;
    } else {
        document.getElementById('uptime').textContent = '0:00';
    }
    
    document.getElementById('chat-count').textContent = s.chatCount;
    document.getElementById('speak-status').textContent = s.isSpeaking ? '🗣️ Speaking' : (s.queueLength > 0 ? `⏳ Queue: ${s.queueLength}` : 'Idle');
    
    // Errors
    if (s.errors && s.errors.length > 0) {
        document.getElementById('error-section').style.display = 'block';
        document.getElementById('error-log').innerHTML = s.errors.map(e => `<div>${e.time}: ${e.msg}</div>`).join('');
    } else {
        document.getElementById('error-section').style.display = 'none';
    }
    
    // Platform indicators
    ['tiktok','instagram','youtube','custom'].forEach(p => {
        const el = document.getElementById('plat-' + p);
        if (s.rtmpConfigured?.[p]) {
            el.style.borderColor = '#22c55e';
        }
    });
}

async function saveRtmpConfig() {
    const url = document.getElementById('rtmp-url').value.trim();
    const key = document.getElementById('rtmp-key').value.trim();
    const result = await api('config', 'POST', { platform: selectedPlatform, url, key });
    if (result.success) {
        alert(`✅ ${selectedPlatform} RTMP keys saved!`);
    } else {
        alert(`❌ Error: ${result.error}`);
    }
}

async function goLive() {
    if (!confirm(`Go LIVE on ${selectedPlatform.toUpperCase()}? Make sure your stream key is set!`)) return;
    document.getElementById('btn-go-live').disabled = true;
    document.getElementById('btn-go-live').textContent = '⏳ Starting...';
    
    const result = await api('start', 'POST', { platform: selectedPlatform });
    if (result.success) {
        document.getElementById('btn-go-live').textContent = '🔴 LIVE';
    } else {
        alert(`❌ Error: ${result.error}`);
        document.getElementById('btn-go-live').disabled = false;
        document.getElementById('btn-go-live').textContent = '🔴 Go Live';
    }
}

async function endStream() {
    if (!confirm('End the livestream?')) return;
    document.getElementById('btn-stop').disabled = true;
    document.getElementById('btn-stop').textContent = '⏳ Stopping...';
    
    await api('stop', 'POST');
    document.getElementById('btn-stop').textContent = '⏹ End Stream';
    document.getElementById('btn-go-live').textContent = '🔴 Go Live';
    refreshStatus();
}

async function makeSpeak() {
    const text = document.getElementById('speak-text').value.trim();
    if (!text) return;
    await api('speak', 'POST', { text });
    document.getElementById('speak-text').value = '';
}

function quickSpeak(text) {
    api('speak', 'POST', { text });
}

async function sendTestChat() {
    const user = document.getElementById('test-chat-user').value.trim() || 'TestUser';
    const msg = document.getElementById('test-chat-msg').value.trim();
    if (!msg) return;
    const result = await api('chat', 'POST', { user, text: msg, platform: selectedPlatform });
    document.getElementById('test-chat-msg').value = '';
    if (result.response) {
        refreshChatLog();
    }
}

async function refreshChatLog() {
    const data = await api('chat-log');
    if (!data.messages || data.messages.length === 0) return;
    
    const log = document.getElementById('chat-log');
    log.innerHTML = data.messages.slice(-20).map(m => {
        const time = new Date(m.time).toLocaleTimeString();
        const tag = m.platform ? `<span class="platform-tag ${m.platform}">${m.platform}</span>` : '';
        return `<div class="chat-msg"><span class="time">${time}</span> ${tag} <span class="user">${escHtml(m.user)}:</span> ${escHtml(m.text)}</div>`;
    }).join('');
    log.scrollTop = log.scrollHeight;
}

function escHtml(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

// Auto-refresh
refreshStatus();
refreshInterval = setInterval(() => {
    refreshStatus();
    refreshChatLog();
}, 5000);

// Enter key for speak
document.getElementById('speak-text').addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); makeSpeak(); }
});
document.getElementById('test-chat-msg').addEventListener('keydown', e => {
    if (e.key === 'Enter') { sendTestChat(); }
});
</script>

</body>
</html>
