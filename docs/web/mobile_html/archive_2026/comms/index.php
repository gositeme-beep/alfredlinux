<?php
/**
 * GoSiteMe Veil — E2E Encrypted Communications
 * Zero-knowledge architecture. Server never sees plaintext.
 */
require_once __DIR__ . '/../includes/auth-gate.inc.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veil • GoSiteMe Encrypted</title>
    <meta name="description" content="End-to-end encrypted communications. Text, voice, video, files. Zero knowledge.">
    <link rel="icon" href="/brand/favicon.ico">
    <link rel="manifest" href="/comms/manifest.json">
    <meta name="theme-color" content="#0a0a14">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="stylesheet" href="/assets/css/fonts.css">
    <link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="/comms/css/comms.css">
    <link rel="stylesheet" href="/comms/css/comms-v2.css">
</head>
<body>

<!-- ═══════════════════════════════════════════════════════════════════
     SETUP SCREEN — Key generation for first-time users
     ═══════════════════════════════════════════════════════════════════ -->
<div id="setup-screen" class="setup-screen hidden">
    <div class="lock-icon"><i class="fas fa-shield-halved"></i></div>
    <h2 id="setup-title">Initialize End-to-End Encryption</h2>
    <p id="setup-desc">
        Generate your encryption keys to enable secure communications.
        Your private keys never leave this device. Not even GoSiteMe can read your messages.
    </p>
    <div style="display:flex;gap:12px;flex-wrap:wrap;justify-content:center">
        <button class="btn btn-primary" id="btn-generate-keys" onclick="CommsApp.generateKeys()">
            <i class="fas fa-key" style="margin-right:8px"></i> Generate Encryption Keys
        </button>
        <button class="btn btn-secondary hidden" id="btn-import-backup" onclick="CommsApp.importBackup()">
            <i class="fas fa-file-import" style="margin-right:8px"></i> Import Key Backup
        </button>
    </div>
    <div class="security-badges" style="margin-top:32px">
        <div class="security-badge"><i class="fas fa-lock"></i> AES-256-GCM</div>
        <div class="security-badge"><i class="fas fa-key"></i> ECDH P-256</div>
        <div class="security-badge"><i class="fas fa-fingerprint"></i> ECDSA Signatures</div>
        <div class="security-badge"><i class="fas fa-database"></i> Zero-Knowledge Server</div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════
     MAIN APP
     ═══════════════════════════════════════════════════════════════════ -->
<div id="main-content" class="hidden">
    <div id="comms-app">

        <!-- ── SIDEBAR ──────────────────────────────────────────── -->
        <aside id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-brand">
                    <div class="brand-icon"><i class="fas fa-shield-halved"></i></div>
                    <div>
                        <h1>Veil</h1>
                    </div>
                    <span class="badge-e2e">E2E v2</span>
                </div>
                <div class="sidebar-actions">
                    <button title="New chat" id="btn-new-chat"><i class="fas fa-pen-to-square"></i></button>
                    <button title="Create group" id="btn-create-group"><i class="fas fa-users"></i></button>
                    <button title="Settings" id="btn-settings"><i class="fas fa-gear"></i></button>
                </div>
            </div>

            <!-- Sidebar Tabs -->
            <div class="sidebar-tabs">
                <div class="sidebar-tab active" data-view="chats"><i class="fas fa-comment"></i> Chats</div>
                <div class="sidebar-tab" data-view="groups"><i class="fas fa-users"></i> Groups</div>
                <div class="sidebar-tab" data-view="dashboard"><i class="fas fa-gauge-high"></i> HQ</div>
            </div>

            <div class="sidebar-search-bar">
                <i class="fas fa-magnifying-glass"></i>
                <input type="text" placeholder="Search..." id="sidebar-search">
            </div>

            <div class="conversations-list" id="conversations-list">
                <!-- Populated by JS -->
            </div>

            <!-- PWA Install -->
            <div id="pwa-install-banner" class="pwa-install-banner hidden">
                <i class="fas fa-download" style="color:var(--accent);font-size:1.1rem"></i>
                <p>Install Veil for the best experience</p>
                <button id="btn-pwa-install">Install</button>
            </div>
        </aside>

        <!-- ── CHAT AREA ────────────────────────────────────────── -->
        <div id="chat-area">

            <!-- Empty state -->
            <div id="empty-state" class="empty-state">
                <div class="shield-icon"><i class="fas fa-shield-halved"></i></div>
                <h2>GoSiteMe Veil</h2>
                <p>End-to-end encrypted messaging, voice, video, and file transfer. Your messages are yours alone.</p>
                <div class="security-badges">
                    <div class="security-badge"><i class="fas fa-lock"></i> E2E Encrypted</div>
                    <div class="security-badge"><i class="fas fa-eye-slash"></i> Zero Knowledge</div>
                    <div class="security-badge"><i class="fas fa-video"></i> P2P Video</div>
                    <div class="security-badge"><i class="fas fa-file-shield"></i> Encrypted Files</div>
                    <div class="security-badge"><i class="fas fa-clock"></i> Self-Destruct</div>
                </div>
            </div>

            <!-- Active Chat -->
            <div id="active-chat" class="active-chat hidden">

                <!-- Chat Header -->
                <header class="chat-header">
                    <div class="chat-header-info">
                        <button class="icon-btn" id="btn-back" title="Back" style="display:none">
                            <i class="fas fa-arrow-left"></i>
                        </button>
                        <div class="conv-avatar" id="chat-contact-avatar" style="width:38px;height:38px;font-size:0.8rem"></div>
                        <div>
                            <div class="chat-header-name" id="chat-contact-name"></div>
                            <div class="chat-header-status">
                                <span class="dot"></span> Encrypted
                            </div>
                        </div>
                    </div>
                    <div class="chat-header-actions">
                        <button class="icon-btn" id="btn-audio-call" title="Audio call">
                            <i class="fas fa-phone"></i>
                        </button>
                        <button class="icon-btn" id="btn-video-call" title="Video call">
                            <i class="fas fa-video"></i>
                        </button>
                        <button class="icon-btn" id="btn-verify" title="Verify contact">
                            <i class="fas fa-fingerprint"></i>
                        </button>
                    </div>
                </header>

                <!-- Alfred Agent Bar -->
                <div id="alfred-agent-bar">
                    <div id="alfred-agent-selector"></div>
                </div>

                <!-- Messages -->
                <div class="messages-container" id="messages-container">
                    <!-- Populated by JS -->
                </div>

                <!-- File drop zone -->
                <div id="file-drop-zone" class="file-drop-zone hidden">
                    <i class="fas fa-file-arrow-up" style="margin-right:10px"></i> Drop file to send encrypted
                </div>

                <!-- Voice Record Panel -->
                <div id="voice-record-panel" class="hidden">
                    <div class="recording-dot"></div>
                    <span id="voice-timer">0:00</span>
                    <div id="voice-level"></div>
                    <button class="icon-btn" id="btn-cancel-voice" title="Cancel">
                        <i class="fas fa-xmark" style="color:var(--red)"></i>
                    </button>
                </div>

                <!-- Reply Bar -->
                <div id="reply-bar" class="hidden"></div>

                <!-- Message Input -->
                <footer class="message-input">
                    <div class="message-input-actions">
                        <button class="icon-btn" id="btn-attach" title="Attach file">
                            <i class="fas fa-paperclip"></i>
                        </button>
                        <button class="icon-btn" id="btn-voice-record" title="Voice message">
                            <i class="fas fa-microphone"></i>
                        </button>
                        <input type="file" id="file-input" style="display:none">
                    </div>
                    <div class="input-wrapper">
                        <textarea id="msg-input" rows="1" placeholder="Type a message..." autocomplete="off"></textarea>
                    </div>
                    <div class="destruct-selector" title="Self-destruct timer">
                        <i class="fas fa-clock text-muted" style="font-size:0.75rem"></i>
                        <select id="destruct-timer">
                            <option value="0">Off</option>
                            <option value="30">30s</option>
                            <option value="300">5m</option>
                            <option value="3600">1h</option>
                            <option value="86400">24h</option>
                            <option value="604800">7d</option>
                        </select>
                    </div>
                    <button class="send-btn" id="btn-send" title="Send">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </footer>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════
     CALL OVERLAY
     ═══════════════════════════════════════════════════════════════════ -->
<div id="call-overlay" class="call-overlay hidden">
    <video id="remote-video" class="call-remote-video" autoplay playsinline></video>
    <video id="local-video" class="call-local-video" autoplay playsinline muted></video>

    <div class="call-info" id="call-info-panel">
        <div class="call-avatar" id="call-avatar"><span id="call-avatar-text"></span></div>
        <div class="call-name" id="call-name"></div>
        <div class="call-status" id="call-status">Connecting...</div>
    </div>

    <div class="call-timer" id="call-timer" style="position:absolute;top:20px;left:50%;transform:translateX(-50%);z-index:10"></div>

    <div class="call-controls">
        <button class="call-btn" id="btn-mute" title="Mute"><i class="fas fa-microphone"></i></button>
        <button class="call-btn" id="btn-cam-off" title="Camera"><i class="fas fa-video"></i></button>
        <button class="call-btn" id="btn-screen-share" title="Share screen"><i class="fas fa-display"></i></button>
        <button class="call-btn end-call" id="btn-end-call" title="End call"><i class="fas fa-phone-slash"></i></button>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════
     INCOMING CALL NOTIFICATION
     ═══════════════════════════════════════════════════════════════════ -->
<div id="incoming-call" class="incoming-call hidden">
    <div class="conv-avatar" style="width:48px;height:48px"><i class="fas fa-phone" style="animation:pulse-glow 1s infinite"></i></div>
    <div>
        <div style="font-weight:700" id="incoming-call-name">Incoming Call</div>
        <div style="font-size:0.8rem;color:var(--text-secondary)">Encrypted call</div>
    </div>
    <div class="call-actions">
        <button class="call-btn answer-call" id="btn-answer-call" style="width:44px;height:44px;font-size:1rem">
            <i class="fas fa-phone"></i>
        </button>
        <button class="call-btn end-call" id="btn-reject-call" style="width:44px;height:44px;font-size:1rem">
            <i class="fas fa-phone-slash"></i>
        </button>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════
     MODALS
     ═══════════════════════════════════════════════════════════════════ -->

<!-- New Chat Modal -->
<div id="new-chat-modal" class="modal-overlay hidden">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-user-plus" style="margin-right:8px;color:var(--accent)"></i> New Conversation</h3>
            <button class="icon-btn modal-close"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label>Search by name or email</label>
                <input type="text" class="form-input" id="search-contact-input" placeholder="Enter name or exact email..." autocomplete="off">
            </div>
            <div id="search-results" class="search-results">
                <div style="padding:16px;color:var(--text-muted);font-size:0.85rem;text-align:center">
                    Search for users to start an encrypted conversation
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Verify Contact Modal -->
<div id="verify-modal" class="modal-overlay hidden">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-fingerprint" style="margin-right:8px;color:var(--green)"></i> Verify Contact</h3>
            <button class="icon-btn modal-close"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <p style="font-size:0.85rem;color:var(--text-secondary);margin-bottom:16px">
                Compare this safety number with your contact (in person or via a trusted channel).
                If they match, your conversation is verified and secure.
            </p>
            <div class="safety-number" id="safety-number">Loading...</div>
            <p style="font-size:0.75rem;color:var(--text-muted);margin-top:12px;text-align:center">
                <i class="fas fa-info-circle"></i>
                Both you and your contact should see the same number.
            </p>
        </div>
    </div>
</div>

<!-- Settings Modal -->
<div id="settings-modal" class="modal-overlay hidden">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-gear" style="margin-right:8px;color:var(--accent)"></i> Settings</h3>
            <button class="icon-btn modal-close"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div class="settings-section">
                <h4>Encryption</h4>
                <div class="settings-row">
                    <span>Your Key Fingerprint</span>
                </div>
                <div class="fingerprint" id="my-fingerprint">Loading...</div>
            </div>

            <div class="settings-section">
                <h4>Key Backup</h4>
                <p style="font-size:0.8rem;color:var(--text-secondary);margin-bottom:12px">
                    Export your encryption keys to restore on another device. Protected with a passphrase.
                </p>
                <div style="display:flex;gap:8px">
                    <button class="btn btn-secondary" onclick="CommsApp.exportBackup()">
                        <i class="fas fa-download" style="margin-right:6px"></i> Export Backup
                    </button>
                    <button class="btn btn-secondary" onclick="CommsApp.importBackup()">
                        <i class="fas fa-upload" style="margin-right:6px"></i> Import Backup
                    </button>
                </div>
            </div>

            <div class="settings-section">
                <h4>Linked Devices</h4>
                <div id="devices-list">Loading...</div>
            </div>

            <div class="settings-section">
                <h4>Push Notifications</h4>
                <p style="font-size:0.8rem;color:var(--text-secondary);margin-bottom:12px">
                    Receive notifications when you get new encrypted messages.
                </p>
                <button class="btn btn-secondary" id="btn-enable-push">
                    <i class="fas fa-bell" style="margin-right:6px"></i> Enable Push Notifications
                </button>
            </div>

            <div class="settings-section">
                <h4>Security Info</h4>
                <div style="font-size:0.8rem;color:var(--text-secondary);line-height:1.8">
                    <div><i class="fas fa-check text-green" style="width:16px"></i> AES-256-GCM message encryption</div>
                    <div><i class="fas fa-check text-green" style="width:16px"></i> ECDH P-256 key exchange</div>
                    <div><i class="fas fa-check text-green" style="width:16px"></i> ECDSA digital signatures</div>
                    <div><i class="fas fa-check text-green" style="width:16px"></i> HKDF-SHA256 key derivation</div>
                    <div><i class="fas fa-check text-green" style="width:16px"></i> WebRTC DTLS-SRTP calls</div>
                    <div><i class="fas fa-check text-green" style="width:16px"></i> Zero-knowledge server</div>
                    <div><i class="fas fa-check text-green" style="width:16px"></i> Client-side file encryption</div>
                    <div><i class="fas fa-check text-green" style="width:16px"></i> PBKDF2 key backup protection</div>
                    <div><i class="fas fa-check text-green" style="width:16px"></i> No external crypto dependencies</div>
                </div>
            </div>

            <div class="settings-section">
                <h4>About</h4>
                <div style="font-size:0.8rem;color:var(--text-secondary);line-height:1.6">
                    GoSiteMe Veil v2.0<br>
                    Built with Web Crypto API — zero external dependencies.<br>
                    Private keys never leave your device.<br>
                    Server stores only encrypted blobs.<br>
                    <span style="color:var(--green)">Even we can't read your messages.</span><br>
                    <span style="color:var(--purple)">Post-quantum ready.</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div id="toast-container" class="toast-container"></div>

<!-- Scripts -->
<script src="/comms/js/comms-crypto.js?v=4"></script>
<script src="/comms/js/comms-pqc.js?v=4"></script>
<script src="/comms/js/comms-rtc.js?v=4"></script>
<script src="/comms/js/comms-voice.js?v=4"></script>
<script src="/comms/js/comms-groups.js?v=4"></script>
<script src="/comms/js/comms-alfred.js?v=4"></script>
<script src="/comms/js/comms-pwa.js?v=4"></script>
<script src="/comms/js/comms-app-v2.js?v=4"></script>

</body>
</html>
