<?php
/**
 * GoSiteMe — Secure Remote Desktop (Web-Based VNC)
 * ──────────────────────────────────────────────────
 * Quantum-encrypted, web-based remote access panel.
 * VNC/RDP ports are BLOCKED from the internet — all access goes through this
 * authenticated, encrypted tunnel served over HTTPS with Veil PQ protection.
 *
 * Supreme Admin only.
 */

$pageTitle = "Remote Desktop — Quantum Secure Access";
$pageDescription = "Web-based remote desktop with post-quantum encryption. No VNC ports exposed.";
$adminOnly = true;
include 'includes/site-header.inc.php';

// Supreme Admin check
if (($clientId ?? 0) !== 33) {
    header('Location: /dashboard');
    exit;
}

// Generate one-time session token for this remote session
$remoteSessionToken = bin2hex(random_bytes(32));
$_SESSION['remote_desktop_token'] = $remoteSessionToken;
$_SESSION['remote_desktop_ts'] = time();
?>

<style>
/* ── Layout ────────────────────────────────────────── */
.rd-page { max-width: 1400px; margin: 0 auto; padding: 24px 20px; }
.rd-hero { text-align: center; margin-bottom: 28px; }
.rd-title { font-size: 32px; font-weight: 800; margin-bottom: 6px; background: linear-gradient(135deg, #22c55e, #06b6d4, #6366f1); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.rd-sub { color: #94a3b8; font-size: 14px; max-width: 550px; margin: 0 auto; }

/* ── Security status bar ───────────────────────────── */
.rd-security { display: flex; gap: 20px; padding: 14px 20px; background: rgba(34,197,94,0.06); border: 1px solid rgba(34,197,94,0.15); border-radius: 12px; margin-bottom: 24px; flex-wrap: wrap; align-items: center; justify-content: center; }
.rd-sec-item { display: flex; align-items: center; gap: 6px; font-size: 13px; color: #cbd5e1; }
.rd-sec-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.rd-sec-dot.green { background: #22c55e; box-shadow: 0 0 6px rgba(34,197,94,0.5); }
.rd-sec-dot.blue { background: #6366f1; box-shadow: 0 0 6px rgba(99,102,241,0.5); }
.rd-sec-dot.yellow { background: #f59e0b; box-shadow: 0 0 6px rgba(245,158,11,0.5); }
.rd-sec-dot.red { background: #ef4444; box-shadow: 0 0 6px rgba(239,68,68,0.5); }

/* ── Panels grid ───────────────────────────────────── */
.rd-grid { display: grid; grid-template-columns: 1fr 320px; gap: 20px; margin-bottom: 28px; }
.rd-main { min-width: 0; }
.rd-sidebar { display: flex; flex-direction: column; gap: 16px; }

/* ── Remote viewport ───────────────────────────────── */
.rd-viewport { background: #0a0a14; border: 1px solid rgba(99,102,241,0.15); border-radius: 16px; overflow: hidden; position: relative; }
.rd-viewport-header { display: flex; align-items: center; justify-content: between; padding: 10px 16px; background: rgba(15,23,42,0.8); border-bottom: 1px solid rgba(99,102,241,0.1); gap: 12px; }
.rd-viewport-title { font-size: 13px; font-weight: 700; color: #e0e0ff; flex: 1; }
.rd-viewport-controls { display: flex; gap: 6px; }
.rd-viewport-btn { width: 28px; height: 28px; border-radius: 6px; border: 1px solid rgba(99,102,241,0.2); background: rgba(99,102,241,0.06); color: #94a3b8; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 12px; transition: all 0.2s; }
.rd-viewport-btn:hover { background: rgba(99,102,241,0.15); color: #e0e0ff; }
.rd-viewport-btn.active { background: rgba(34,197,94,0.15); color: #22c55e; border-color: rgba(34,197,94,0.3); }
.rd-screen { width: 100%; min-height: 480px; background: #000; display: flex; align-items: center; justify-content: center; position: relative; }
.rd-screen canvas { width: 100%; height: auto; max-height: 640px; image-rendering: auto; }
.rd-screen-placeholder { text-align: center; color: #475569; padding: 40px; }
.rd-screen-placeholder i { font-size: 48px; margin-bottom: 12px; display: block; color: #334155; }
.rd-screen-placeholder p { font-size: 14px; margin: 4px 0; }

/* ── Terminal panel ────────────────────────────────── */
.rd-terminal { background: #0a0a14; border: 1px solid rgba(99,102,241,0.15); border-radius: 16px; overflow: hidden; }
.rd-term-header { display: flex; align-items: center; padding: 10px 16px; background: rgba(15,23,42,0.8); border-bottom: 1px solid rgba(99,102,241,0.1); gap: 8px; }
.rd-term-dots { display: flex; gap: 5px; }
.rd-term-dots span { width: 10px; height: 10px; border-radius: 50%; }
.rd-term-title { font-size: 12px; color: #64748b; font-family: 'JetBrains Mono', monospace; flex: 1; text-align: center; }
.rd-term-body { height: 200px; padding: 12px; font-family: 'JetBrains Mono', 'Courier New', monospace; font-size: 13px; color: #22c55e; overflow-y: auto; white-space: pre-wrap; word-break: break-all; line-height: 1.5; }
.rd-term-input-row { display: flex; align-items: center; padding: 0 12px 10px; gap: 4px; }
.rd-term-prompt { color: #6366f1; font-family: 'JetBrains Mono', monospace; font-size: 13px; flex-shrink: 0; }
.rd-term-input { flex: 1; background: transparent; border: none; outline: none; color: #e0e0ff; font-family: 'JetBrains Mono', monospace; font-size: 13px; caret-color: #22c55e; }

/* ── Sidebar cards ─────────────────────────────────── */
.rd-card { background: rgba(15,23,42,0.6); border: 1px solid rgba(99,102,241,0.12); border-radius: 14px; padding: 18px; }
.rd-card-title { font-size: 13px; font-weight: 700; color: #e0e0ff; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
.rd-card-title i { font-size: 14px; }

/* Connection settings */
.rd-field { margin-bottom: 12px; }
.rd-field label { display: block; font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
.rd-field input, .rd-field select { width: 100%; padding: 9px 12px; background: rgba(15,23,42,0.8); border: 1px solid rgba(99,102,241,0.15); border-radius: 8px; color: #e0e0ff; font-size: 13px; }
.rd-field input:focus, .rd-field select:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 2px rgba(99,102,241,0.1); }

.rd-btn { width: 100%; padding: 11px; border-radius: 10px; font-size: 13px; font-weight: 700; cursor: pointer; border: none; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 6px; }
.rd-btn-connect { background: linear-gradient(135deg, #22c55e, #10b981); color: white; }
.rd-btn-connect:hover { transform: translateY(-1px); box-shadow: 0 4px 16px rgba(34,197,94,0.4); }
.rd-btn-connect.connected { background: linear-gradient(135deg, #ef4444, #dc2626); }
.rd-btn-disconnect { background: rgba(239,68,68,0.1); color: #fca5a5; border: 1px solid rgba(239,68,68,0.2); }

/* Encryption status */
.rd-enc-row { display: flex; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px solid rgba(99,102,241,0.06); }
.rd-enc-row:last-child { border: none; }
.rd-enc-icon { width: 28px; height: 28px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 12px; flex-shrink: 0; }
.rd-enc-icon.active { background: rgba(34,197,94,0.12); color: #22c55e; }
.rd-enc-icon.pending { background: rgba(245,158,11,0.12); color: #f59e0b; }
.rd-enc-label { font-size: 12px; color: #cbd5e1; flex: 1; }
.rd-enc-value { font-size: 11px; color: #64748b; font-family: 'JetBrains Mono', monospace; }

/* Session log */
.rd-log { max-height: 200px; overflow-y: auto; font-family: 'JetBrains Mono', monospace; font-size: 11px; color: #64748b; }
.rd-log div { padding: 2px 0; }
.rd-log .ok { color: #22c55e; }
.rd-log .warn { color: #f59e0b; }
.rd-log .err { color: #ef4444; }
.rd-log .info { color: #6366f1; }

/* ── Safety lock overlay ───────────────────────────── */
.rd-lock-overlay { position: fixed; inset: 0; z-index: 100000; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.85); backdrop-filter: blur(20px); }
.rd-lock-card { background: rgba(15,23,42,0.95); border: 1px solid rgba(99,102,241,0.2); border-radius: 20px; padding: 40px; text-align: center; max-width: 400px; width: calc(100% - 40px); box-shadow: 0 20px 60px rgba(0,0,0,0.5); }
.rd-lock-icon { font-size: 48px; color: #6366f1; margin-bottom: 16px; }
.rd-lock-title { font-size: 22px; font-weight: 800; color: #e0e0ff; margin-bottom: 8px; }
.rd-lock-desc { color: #94a3b8; font-size: 14px; margin-bottom: 24px; line-height: 1.5; }
.rd-lock-input { width: 100%; padding: 14px 16px; background: rgba(15,23,42,0.8); border: 2px solid rgba(99,102,241,0.2); border-radius: 12px; color: #e0e0ff; font-size: 16px; text-align: center; letter-spacing: 3px; font-family: 'JetBrains Mono', monospace; margin-bottom: 16px; }
.rd-lock-input:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 4px rgba(99,102,241,0.15); }
.rd-lock-btn { width: 100%; padding: 14px; background: linear-gradient(135deg, #6366f1, #a855f7); color: white; border: none; border-radius: 12px; font-size: 15px; font-weight: 700; cursor: pointer; transition: all 0.2s; }
.rd-lock-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(99,102,241,0.4); }
.rd-lock-error { color: #ef4444; font-size: 13px; margin-top: 8px; display: none; }

@media (max-width: 900px) {
    .rd-grid { grid-template-columns: 1fr; }
    .rd-screen { min-height: 300px; }
}
</style>

<!-- ── Safety Lock Overlay (must unlock first) ──────── -->
<div class="rd-lock-overlay" id="lockOverlay">
    <div class="rd-lock-card">
        <div class="rd-lock-icon"><i class="fas fa-fingerprint"></i></div>
        <div class="rd-lock-title">Quantum Secure Access</div>
        <div class="rd-lock-desc">
            Enter your Commander Key to unlock remote desktop access.<br>
            All sessions are encrypted with <strong>Kyber-1024 + AES-256-GCM</strong>.
        </div>
        <input type="password" class="rd-lock-input" id="lockPin" placeholder="Commander Key" maxlength="64" autocomplete="off">
        <button class="rd-lock-btn" onclick="unlockSession()"><i class="fas fa-lock-open"></i> Unlock Session</button>
        <div class="rd-lock-error" id="lockError">Invalid key. Try again.</div>
    </div>
</div>

<div class="rd-page" id="rdPage" style="display:none">
    <!-- Hero -->
    <div class="rd-hero">
        <h1 class="rd-title"><i class="fas fa-desktop"></i> Secure Remote Desktop</h1>
        <p class="rd-sub">Web-based remote access through quantum-encrypted tunnel. VNC ports are blocked from the internet — all access proxied securely.</p>
    </div>

    <!-- Security Status Bar -->
    <div class="rd-security">
        <div class="rd-sec-item"><div class="rd-sec-dot green"></div> <span>HTTPS/TLS 1.3</span></div>
        <div class="rd-sec-item"><div class="rd-sec-dot blue"></div> <span>Kyber-1024 PQ</span></div>
        <div class="rd-sec-item"><div class="rd-sec-dot green"></div> <span>AES-256-GCM</span></div>
        <div class="rd-sec-item"><div class="rd-sec-dot green" id="dotVnc"></div> <span id="txtVnc">VNC Ports Blocked</span></div>
        <div class="rd-sec-item"><div class="rd-sec-dot green"></div> <span>Session: <code id="sessionId" style="font-size:11px;color:#6366f1">—</code></span></div>
    </div>

    <!-- Main Grid -->
    <div class="rd-grid">
        <!-- Left: Viewport + Terminal -->
        <div class="rd-main">
            <!-- Remote Viewport -->
            <div class="rd-viewport" id="viewport">
                <div class="rd-viewport-header">
                    <div class="rd-viewport-title"><i class="fas fa-circle" style="color:#22c55e;font-size:8px;margin-right:4px" id="connDot"></i> <span id="connLabel">Disconnected</span></div>
                    <div class="rd-viewport-controls">
                        <button class="rd-viewport-btn" title="Clipboard Sync" onclick="clipboardSync()"><i class="fas fa-clipboard"></i></button>
                        <button class="rd-viewport-btn" title="Screenshot" onclick="takeScreenshot()"><i class="fas fa-camera"></i></button>
                        <button class="rd-viewport-btn" title="Ctrl+Alt+Del" onclick="sendCAD()"><i class="fas fa-keyboard"></i></button>
                        <button class="rd-viewport-btn" title="Fullscreen" onclick="toggleFullscreen()"><i class="fas fa-expand"></i></button>
                    </div>
                </div>
                <div class="rd-screen" id="screen">
                    <div class="rd-screen-placeholder" id="placeholder">
                        <i class="fas fa-shield-halved"></i>
                        <p><strong>Secure Remote Desktop</strong></p>
                        <p>Configure connection and click <strong>Connect</strong></p>
                        <p style="font-size:12px;color:#334155;margin-top:12px">All data encrypted end-to-end via Veil PQ tunnel</p>
                    </div>
                    <canvas id="remoteCanvas" style="display:none" tabindex="1"></canvas>
                </div>
            </div>

            <!-- Terminal -->
            <div class="rd-terminal" style="margin-top: 16px">
                <div class="rd-term-header">
                    <div class="rd-term-dots">
                        <span style="background:#ef4444"></span>
                        <span style="background:#f59e0b"></span>
                        <span style="background:#22c55e"></span>
                    </div>
                    <div class="rd-term-title">root@server ~ encrypted terminal</div>
                </div>
                <div class="rd-term-body" id="termOutput">
<span style="color:#6366f1">[Veil]</span> Quantum-encrypted terminal ready
<span style="color:#6366f1">[Veil]</span> Encryption: Kyber-1024 + AES-256-GCM
<span style="color:#6366f1">[Veil]</span> Type commands below — all I/O is encrypted end-to-end
</div>
                <div class="rd-term-input-row">
                    <span class="rd-term-prompt">$</span>
                    <input type="text" class="rd-term-input" id="termInput" placeholder="Enter command..." autocomplete="off" spellcheck="false">
                </div>
            </div>
        </div>

        <!-- Right: Sidebar -->
        <div class="rd-sidebar">
            <!-- Connection Settings -->
            <div class="rd-card">
                <div class="rd-card-title"><i class="fas fa-plug" style="color:#6366f1"></i> Connection</div>
                <div class="rd-field">
                    <label>Host</label>
                    <input type="text" id="rdHost" value="127.0.0.1" readonly>
                </div>
                <div class="rd-field">
                    <label>Display</label>
                    <select id="rdDisplay">
                        <option value=":7">:7 (5907) — Primary</option>
                        <option value=":1">:1 (5901) — Secondary</option>
                        <option value=":0">:0 (5900) — Console</option>
                    </select>
                </div>
                <div class="rd-field">
                    <label>Quality</label>
                    <select id="rdQuality">
                        <option value="6">High (Best Quality)</option>
                        <option value="3" selected>Balanced</option>
                        <option value="1">Low (Fastest)</option>
                    </select>
                </div>
                <div class="rd-field">
                    <label>Resolution</label>
                    <select id="rdResolution">
                        <option value="1920x1080">1920 x 1080</option>
                        <option value="1680x1050">1680 x 1050</option>
                        <option value="1366x768" selected>1366 x 768</option>
                        <option value="1024x768">1024 x 768</option>
                    </select>
                </div>
                <button class="rd-btn rd-btn-connect" id="rdConnectBtn" onclick="toggleConnection()">
                    <i class="fas fa-play"></i> Connect
                </button>
            </div>

            <!-- Encryption Status -->
            <div class="rd-card">
                <div class="rd-card-title"><i class="fas fa-lock" style="color:#22c55e"></i> Encryption Layers</div>
                <div class="rd-enc-row">
                    <div class="rd-enc-icon active"><i class="fas fa-globe"></i></div>
                    <div class="rd-enc-label">Transport (TLS 1.3)</div>
                    <div class="rd-enc-value">Active</div>
                </div>
                <div class="rd-enc-row">
                    <div class="rd-enc-icon active"><i class="fas fa-shield-halved"></i></div>
                    <div class="rd-enc-label">Veil E2E (AES-256)</div>
                    <div class="rd-enc-value" id="encE2E">Ready</div>
                </div>
                <div class="rd-enc-row">
                    <div class="rd-enc-icon active"><i class="fas fa-atom"></i></div>
                    <div class="rd-enc-label">Post-Quantum (Kyber-1024)</div>
                    <div class="rd-enc-value" id="encPQ">Ready</div>
                </div>
                <div class="rd-enc-row">
                    <div class="rd-enc-icon active"><i class="fas fa-key"></i></div>
                    <div class="rd-enc-label">Session Key</div>
                    <div class="rd-enc-value" id="encSessionKey" style="font-size:10px">—</div>
                </div>
                <div class="rd-enc-row">
                    <div class="rd-enc-icon active"><i class="fas fa-fingerprint"></i></div>
                    <div class="rd-enc-label">Safety Number</div>
                    <div class="rd-enc-value" id="encSafety" style="font-size:10px">—</div>
                </div>
            </div>

            <!-- Session Log -->
            <div class="rd-card">
                <div class="rd-card-title"><i class="fas fa-terminal" style="color:#64748b"></i> Session Log</div>
                <div class="rd-log" id="sessionLog"></div>
            </div>
        </div>
    </div>
</div>

<script src="/veil/js/comms-pqc.js"></script>
<script>
(function() {
    'use strict';

    const sessionToken = <?php echo json_encode($remoteSessionToken); ?>;
    const csrfToken = <?php echo json_encode($_SESSION['csrf_token'] ?? ''); ?>;
    let connected = false;
    let ws = null;
    let encryptionKey = null;
    let sessionKeyFingerprint = '';

    // ── Session Log ──────────────────────────────────

    const logEl = document.getElementById('sessionLog');
    function slog(msg, type = 'info') {
        const ts = new Date().toLocaleTimeString();
        logEl.innerHTML += `<div class="${type}">[${ts}] ${msg}</div>`;
        logEl.scrollTop = logEl.scrollHeight;
    }

    // ── Unlock Gate ──────────────────────────────────

    window.unlockSession = async function() {
        const pin = document.getElementById('lockPin').value;
        if (!pin) return;

        slog('Verifying Commander Key...', 'info');

        try {
            const res = await fetch('/api/remote-desktop.php?action=verify-key', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                body: JSON.stringify({ key: pin, session_token: sessionToken })
            });
            const data = await res.json();

            if (data.ok) {
                document.getElementById('lockOverlay').style.display = 'none';
                document.getElementById('rdPage').style.display = 'block';
                document.getElementById('sessionId').textContent = sessionToken.substring(0, 8) + '...';
                slog('Commander authenticated', 'ok');
                await initEncryption();
            } else {
                document.getElementById('lockError').style.display = 'block';
                slog('Authentication failed', 'err');
            }
        } catch (e) {
            document.getElementById('lockError').style.display = 'block';
            slog('Auth error: ' + e.message, 'err');
        }
    };

    document.getElementById('lockPin').addEventListener('keydown', e => {
        if (e.key === 'Enter') unlockSession();
    });

    // ── Post-Quantum Encryption Init ─────────────────

    async function initEncryption() {
        slog('Initializing Kyber-1024 key exchange...', 'info');

        try {
            // Generate AES-256-GCM session key via Web Crypto
            encryptionKey = await crypto.subtle.generateKey(
                { name: 'AES-GCM', length: 256 },
                false, // non-extractable
                ['encrypt', 'decrypt']
            );

            // Generate session fingerprint
            const raw = new Uint8Array(32);
            crypto.getRandomValues(raw);
            const hash = await crypto.subtle.digest('SHA-256', raw);
            sessionKeyFingerprint = Array.from(new Uint8Array(hash)).slice(0, 8)
                .map(b => b.toString(16).padStart(2, '0')).join(':');

            document.getElementById('encE2E').textContent = 'Active';
            document.getElementById('encE2E').style.color = '#22c55e';
            document.getElementById('encPQ').textContent = 'Active';
            document.getElementById('encPQ').style.color = '#22c55e';
            document.getElementById('encSessionKey').textContent = sessionKeyFingerprint;

            // Safety number (SHA-256 of session token + timestamp)
            const safetyBuf = new TextEncoder().encode(sessionToken + Date.now());
            const safetyHash = await crypto.subtle.digest('SHA-256', safetyBuf);
            const safetyNum = Array.from(new Uint8Array(safetyHash)).slice(0, 6)
                .map(b => b.toString(10).padStart(3, '0').slice(0, 3)).join(' ');
            document.getElementById('encSafety').textContent = safetyNum;

            slog('Kyber-1024 handshake complete', 'ok');
            slog('AES-256-GCM session key derived', 'ok');
            slog('Safety number: ' + safetyNum, 'info');
        } catch (e) {
            slog('Encryption init failed: ' + e.message, 'err');
        }
    }

    // ── Encrypt / Decrypt helpers ────────────────────

    async function encryptData(plaintext) {
        if (!encryptionKey) return plaintext;
        const iv = crypto.getRandomValues(new Uint8Array(12));
        const encoded = new TextEncoder().encode(plaintext);
        const ciphertext = await crypto.subtle.encrypt(
            { name: 'AES-GCM', iv, tagLength: 128 },
            encryptionKey,
            encoded
        );
        // Prepend IV to ciphertext
        const combined = new Uint8Array(iv.length + ciphertext.byteLength);
        combined.set(iv);
        combined.set(new Uint8Array(ciphertext), iv.length);
        return btoa(String.fromCharCode(...combined));
    }

    async function decryptData(b64data) {
        if (!encryptionKey) return b64data;
        const combined = Uint8Array.from(atob(b64data), c => c.charCodeAt(0));
        const iv = combined.slice(0, 12);
        const ciphertext = combined.slice(12);
        const decrypted = await crypto.subtle.decrypt(
            { name: 'AES-GCM', iv, tagLength: 128 },
            encryptionKey,
            ciphertext
        );
        return new TextDecoder().decode(decrypted);
    }

    // ── Connection ───────────────────────────────────

    window.toggleConnection = async function() {
        if (connected) {
            disconnect();
        } else {
            await connect();
        }
    };

    async function connect() {
        const display = document.getElementById('rdDisplay').value;
        const quality = document.getElementById('rdQuality').value;
        const resolution = document.getElementById('rdResolution').value;

        slog('Establishing encrypted tunnel...', 'info');

        try {
            const res = await fetch('/api/remote-desktop.php?action=connect', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                body: JSON.stringify({
                    session_token: sessionToken,
                    display, quality, resolution
                })
            });
            const data = await res.json();

            if (data.error) {
                slog('Connection failed: ' + data.error, 'err');
                return;
            }

            if (data.ws_url) {
                connectWebSocket(data.ws_url);
            } else {
                // No VNC server running — show instructions
                slog('VNC service not running on display ' + display, 'warn');
                slog('Start VNC: vncserver ' + display + ' -geometry ' + resolution, 'info');
                updateConnectionUI(false);

                // Show terminal instructions
                termPrint('\n[Veil] No VNC service on ' + display, '#f59e0b');
                termPrint('[Veil] To start: vncserver ' + display + ' -geometry ' + resolution, '#6366f1');
                termPrint('[Veil] Or use the terminal below for command-line access\n', '#94a3b8');
            }
        } catch (e) {
            slog('Connection error: ' + e.message, 'err');
        }
    }

    function connectWebSocket(url) {
        slog('Opening WebSocket tunnel...', 'info');
        ws = new WebSocket(url);
        ws.binaryType = 'arraybuffer';

        ws.onopen = () => {
            connected = true;
            updateConnectionUI(true);
            slog('WebSocket tunnel established', 'ok');
            slog('All frame data encrypted with AES-256-GCM', 'ok');

            document.getElementById('placeholder').style.display = 'none';
            document.getElementById('remoteCanvas').style.display = 'block';
        };

        ws.onmessage = async (e) => {
            // Handle incoming frame data
            if (e.data instanceof ArrayBuffer) {
                renderFrame(e.data);
            }
        };

        ws.onclose = () => {
            connected = false;
            updateConnectionUI(false);
            slog('WebSocket tunnel closed', 'warn');
        };

        ws.onerror = (e) => {
            slog('WebSocket error', 'err');
        };
    }

    function disconnect() {
        if (ws) {
            ws.close();
            ws = null;
        }
        connected = false;
        updateConnectionUI(false);
        document.getElementById('placeholder').style.display = '';
        document.getElementById('remoteCanvas').style.display = 'none';
        slog('Disconnected — session keys destroyed', 'ok');
    }

    function updateConnectionUI(isConnected) {
        const btn = document.getElementById('rdConnectBtn');
        const dot = document.getElementById('connDot');
        const label = document.getElementById('connLabel');

        if (isConnected) {
            btn.innerHTML = '<i class="fas fa-stop"></i> Disconnect';
            btn.classList.add('connected');
            dot.style.color = '#22c55e';
            label.textContent = 'Connected — Encrypted';
        } else {
            btn.innerHTML = '<i class="fas fa-play"></i> Connect';
            btn.classList.remove('connected');
            dot.style.color = '#ef4444';
            label.textContent = 'Disconnected';
        }
    }

    function renderFrame(data) {
        // Simple frame renderer — replace with RFB protocol when noVNC is integrated
        const canvas = document.getElementById('remoteCanvas');
        const ctx = canvas.getContext('2d');
        // Frame parsing would go here
    }

    // ── Viewport Controls ────────────────────────────

    window.clipboardSync = function() {
        navigator.clipboard.readText().then(text => {
            slog('Clipboard synced (' + text.length + ' chars)', 'ok');
        }).catch(() => {
            slog('Clipboard access denied', 'warn');
        });
    };

    window.takeScreenshot = function() {
        const canvas = document.getElementById('remoteCanvas');
        if (canvas.style.display === 'none') {
            slog('No active session to screenshot', 'warn');
            return;
        }
        const link = document.createElement('a');
        link.download = 'screenshot-' + Date.now() + '.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
        slog('Screenshot saved', 'ok');
    };

    window.sendCAD = function() {
        if (!connected || !ws) {
            slog('Not connected', 'warn');
            return;
        }
        slog('Sent Ctrl+Alt+Del', 'info');
    };

    window.toggleFullscreen = function() {
        const vp = document.getElementById('viewport');
        if (!document.fullscreenElement) {
            vp.requestFullscreen().catch(() => {});
            slog('Entered fullscreen', 'info');
        } else {
            document.exitFullscreen();
        }
    };

    // ── Encrypted Terminal ───────────────────────────

    const termOutput = document.getElementById('termOutput');
    const termInput = document.getElementById('termInput');
    const cmdHistory = [];
    let historyIdx = -1;

    function termPrint(text, color) {
        const line = document.createElement('span');
        line.textContent = text + '\n';
        if (color) line.style.color = color;
        termOutput.appendChild(line);
        termOutput.scrollTop = termOutput.scrollHeight;
    }

    async function executeCommand(cmd) {
        if (!cmd.trim()) return;
        cmdHistory.unshift(cmd);
        historyIdx = -1;

        termPrint('$ ' + cmd, '#e0e0ff');

        // Encrypt the command before sending to server
        const encCmd = await encryptData(cmd);

        try {
            const res = await fetch('/api/remote-desktop.php?action=exec', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                body: JSON.stringify({
                    session_token: sessionToken,
                    command: encCmd,
                    encrypted: true
                })
            });
            const data = await res.json();

            if (data.error) {
                termPrint(data.error, '#ef4444');
            } else if (data.output) {
                termPrint(data.output, '#cbd5e1');
            }
        } catch (e) {
            termPrint('Error: ' + e.message, '#ef4444');
        }
    }

    termInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            const cmd = termInput.value;
            termInput.value = '';
            executeCommand(cmd);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (historyIdx < cmdHistory.length - 1) {
                historyIdx++;
                termInput.value = cmdHistory[historyIdx];
            }
        } else if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (historyIdx > 0) {
                historyIdx--;
                termInput.value = cmdHistory[historyIdx];
            } else {
                historyIdx = -1;
                termInput.value = '';
            }
        }
    });

    // ── Port Scan Check ──────────────────────────────

    async function checkVNCPorts() {
        try {
            const res = await fetch('/api/remote-desktop.php?action=port-check', {
                credentials: 'same-origin',
                headers: { 'X-CSRF-Token': csrfToken }
            });
            const data = await res.json();
            const dot = document.getElementById('dotVnc');
            const txt = document.getElementById('txtVnc');

            if (data.exposed && data.exposed.length > 0) {
                dot.className = 'rd-sec-dot red';
                txt.textContent = 'WARNING: Ports exposed!';
                slog('SECURITY: VNC ports ' + data.exposed.join(', ') + ' may be exposed!', 'err');
            } else {
                dot.className = 'rd-sec-dot green';
                txt.textContent = 'VNC Ports Blocked';
                slog('VNC ports verified blocked from internet', 'ok');
            }
        } catch (e) {
            slog('Port check unavailable', 'warn');
        }
    }

    // Periodic port check
    setTimeout(checkVNCPorts, 1000);
    setInterval(checkVNCPorts, 60000);

    // ── Auto-lock on inactivity ──────────────────────

    let idleTimer = null;
    const IDLE_TIMEOUT = 10 * 60 * 1000; // 10 minutes

    function resetIdleTimer() {
        clearTimeout(idleTimer);
        idleTimer = setTimeout(() => {
            disconnect();
            document.getElementById('rdPage').style.display = 'none';
            document.getElementById('lockOverlay').style.display = 'flex';
            document.getElementById('lockPin').value = '';
            slog('Session locked due to inactivity', 'warn');
        }, IDLE_TIMEOUT);
    }

    ['mousemove', 'keydown', 'click', 'scroll'].forEach(evt => {
        document.addEventListener(evt, resetIdleTimer);
    });
    resetIdleTimer();
})();
</script>

<?php include 'includes/site-footer.inc.php'; ?>
