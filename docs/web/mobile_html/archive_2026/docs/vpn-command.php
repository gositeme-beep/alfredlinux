<?php
/**
 * GoSiteMe VPN Command Center
 * Commander's personal VPN vault — manage all devices + provision friends
 * Access: Commander only (client_id 33)
 */
session_start();

// Commander auth check
$isCommander = false;
if (isset($_SESSION['uid']) && $_SESSION['uid'] == 33) {
    $isCommander = true;
}
// Fallback for direct access during dev
if (isset($_GET['commander_key']) && $_GET['commander_key'] === 'gositeme-commander-33-vpn') {
    $isCommander = true;
}
if (!$isCommander) {
    header('HTTP/1.1 403 Forbidden');
    echo '<!DOCTYPE html><html><body style="background:#0a0a14;color:#ff4444;display:flex;align-items:center;justify-content:center;height:100vh;font-family:Inter,sans-serif"><h1>⛔ Commander Access Only</h1></body></html>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VPN Command Center — GoSiteMe</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            background: #0a0a14;
            color: #e0e0e0;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
        }
        
        .header {
            background: linear-gradient(135deg, rgba(125,0,255,0.15), rgba(0,212,255,0.1));
            border-bottom: 1px solid rgba(125,0,255,0.3);
            padding: 24px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .header h1 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 24px;
            background: linear-gradient(135deg, #7D00FF, #00D4FF);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .header .status-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
        }
        
        .status-badge.online {
            background: rgba(0,255,136,0.1);
            border: 1px solid rgba(0,255,136,0.3);
            color: #00ff88;
        }
        
        .status-badge.offline {
            background: rgba(255,68,68,0.1);
            border: 1px solid rgba(255,68,68,0.3);
            color: #ff4444;
        }
        
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        .online .status-dot { background: #00ff88; }
        .offline .status-dot { background: #ff4444; }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 24px;
        }
        
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }
        
        .grid-full {
            grid-column: 1 / -1;
        }
        
        .card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px;
            padding: 24px;
            transition: border-color 0.3s;
        }
        
        .card:hover {
            border-color: rgba(125,0,255,0.3);
        }
        
        .card-title {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 16px;
            font-weight: 600;
            color: #00D4FF;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .card-title .icon {
            font-size: 20px;
        }
        
        /* Config display */
        .config-block {
            background: #0d0d1a;
            border: 1px solid rgba(125,0,255,0.2);
            border-radius: 12px;
            padding: 16px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            line-height: 1.8;
            color: #a0a0c0;
            position: relative;
            white-space: pre-wrap;
            word-break: break-all;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .config-block .section-header {
            color: #7D00FF;
            font-weight: 600;
        }
        
        .config-block .key-name {
            color: #00D4FF;
        }
        
        .config-block .key-value {
            color: #e0e0e0;
        }
        
        .copy-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(125,0,255,0.2);
            border: 1px solid rgba(125,0,255,0.4);
            color: #00D4FF;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 11px;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s;
        }
        
        .copy-btn:hover {
            background: rgba(125,0,255,0.4);
        }
        
        .copy-btn.copied {
            background: rgba(0,255,136,0.2);
            border-color: rgba(0,255,136,0.4);
            color: #00ff88;
        }
        
        /* QR Code */
        .qr-container {
            text-align: center;
            padding: 16px;
        }
        
        .qr-container img {
            background: white;
            padding: 16px;
            border-radius: 12px;
            max-width: 200px;
            image-rendering: pixelated;
        }
        
        .qr-label {
            font-size: 12px;
            color: #666;
            margin-top: 8px;
        }
        
        /* Setup tabs */
        .tabs {
            display: flex;
            gap: 4px;
            margin-bottom: 16px;
            background: rgba(0,0,0,0.3);
            border-radius: 12px;
            padding: 4px;
        }
        
        .tab {
            flex: 1;
            padding: 10px 16px;
            border-radius: 10px;
            border: none;
            background: transparent;
            color: #888;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }
        
        .tab.active {
            background: rgba(125,0,255,0.2);
            color: #00D4FF;
        }
        
        .tab:hover:not(.active) {
            color: #ccc;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .setup-steps {
            counter-reset: step;
            list-style: none;
            padding: 0;
        }
        
        .setup-steps li {
            counter-increment: step;
            padding: 12px 16px 12px 48px;
            position: relative;
            border-left: 2px solid rgba(125,0,255,0.2);
            margin-left: 16px;
            font-size: 14px;
            line-height: 1.6;
            color: #c0c0d0;
        }
        
        .setup-steps li::before {
            content: counter(step);
            position: absolute;
            left: -13px;
            width: 24px;
            height: 24px;
            background: #7D00FF;
            border-radius: 50%;
            color: white;
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .setup-steps li:last-child {
            border-left-color: transparent;
        }
        
        .setup-steps code {
            background: rgba(125,0,255,0.15);
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            color: #00D4FF;
        }
        
        .setup-steps a {
            color: #7D00FF;
            text-decoration: none;
        }
        
        .setup-steps a:hover {
            text-decoration: underline;
        }
        
        /* Client list */
        .client-list {
            list-style: none;
            padding: 0;
        }
        
        .client-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            transition: background 0.2s;
        }
        
        .client-item:hover {
            background: rgba(125,0,255,0.05);
        }
        
        .client-item:last-child {
            border-bottom: none;
        }
        
        .client-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .client-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #7D00FF, #00D4FF);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 600;
            color: white;
        }
        
        .client-name {
            font-weight: 500;
            font-size: 14px;
        }
        
        .client-ip {
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            color: #00D4FF;
        }
        
        .client-date {
            font-size: 11px;
            color: #666;
        }
        
        .client-actions {
            display: flex;
            gap: 6px;
        }
        
        .btn-sm {
            padding: 6px 12px;
            border-radius: 8px;
            border: none;
            font-size: 11px;
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-config {
            background: rgba(0,212,255,0.1);
            border: 1px solid rgba(0,212,255,0.3);
            color: #00D4FF;
        }
        
        .btn-qr {
            background: rgba(125,0,255,0.1);
            border: 1px solid rgba(125,0,255,0.3);
            color: #b366ff;
        }
        
        .btn-revoke {
            background: rgba(255,68,68,0.1);
            border: 1px solid rgba(255,68,68,0.3);
            color: #ff4444;
        }
        
        .btn-sm:hover {
            filter: brightness(1.3);
        }
        
        /* Add client form */
        .add-client-form {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .add-client-form input {
            flex: 1;
            background: #0d0d1a;
            border: 1px solid rgba(125,0,255,0.3);
            border-radius: 12px;
            padding: 12px 16px;
            color: #e0e0e0;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }
        
        .add-client-form input:focus {
            border-color: #7D00FF;
        }
        
        .add-client-form input::placeholder {
            color: #444;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #7D00FF, #5a00cc);
            border: none;
            color: white;
            padding: 12px 24px;
            border-radius: 12px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }
        
        .btn-primary:hover {
            filter: brightness(1.2);
            transform: translateY(-1px);
        }
        
        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-overlay.active {
            display: flex;
        }
        
        .modal {
            background: #12121e;
            border: 1px solid rgba(125,0,255,0.3);
            border-radius: 20px;
            padding: 32px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal h3 {
            font-family: 'Space Grotesk', sans-serif;
            color: #00D4FF;
            margin-bottom: 16px;
        }
        
        .modal .close-btn {
            float: right;
            background: none;
            border: none;
            color: #666;
            font-size: 24px;
            cursor: pointer;
        }
        
        .modal .close-btn:hover {
            color: #ff4444;
        }
        
        /* Download button */
        .btn-download {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #00D4FF, #0099cc);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 12px;
            transition: all 0.2s;
        }
        
        .btn-download:hover {
            filter: brightness(1.2);
        }
        
        /* Toast notifications */
        .toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #12121e;
            border: 1px solid rgba(0,255,136,0.3);
            color: #00ff88;
            padding: 12px 24px;
            border-radius: 12px;
            font-size: 14px;
            z-index: 2000;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s;
        }
        
        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }
        
        .toast.error {
            border-color: rgba(255,68,68,0.3);
            color: #ff4444;
        }
        
        /* Server info grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        
        .info-item {
            background: rgba(0,0,0,0.2);
            border-radius: 10px;
            padding: 12px;
        }
        
        .info-label {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        
        .info-value {
            font-family: 'JetBrains Mono', monospace;
            font-size: 14px;
            color: #e0e0e0;
        }
        
        /* Back link */
        .back-link {
            color: #7D00FF;
            text-decoration: none;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .back-link:hover {
            color: #00D4FF;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }
            .header {
                flex-direction: column;
                gap: 12px;
                text-align: center;
            }
            .add-client-form {
                flex-direction: column;
            }
            .info-grid {
                grid-template-columns: 1fr;
            }
            .tabs {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <a href="/docs/commander-briefing" class="back-link">← Back to Command Center</a>
            <h1>🔐 VPN Command Center</h1>
        </div>
        <div id="serverStatus" class="status-badge online">
            <div class="status-dot"></div>
            <span>Checking...</span>
        </div>
    </div>
    
    <div class="container">
        <!-- Server Info + Your Config -->
        <div class="grid">
            <!-- Server Info -->
            <div class="card">
                <div class="card-title"><span class="icon">🖥️</span> Server Info</div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Endpoint</div>
                        <div class="info-value">15.235.50.60</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Port</div>
                        <div class="info-value">51820</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Protocol</div>
                        <div class="info-value">WireGuard</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Subnet</div>
                        <div class="info-value">10.66.66.0/24</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Your IP</div>
                        <div class="info-value" style="color: #00ff88;">10.66.66.2</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Capacity</div>
                        <div class="info-value" id="capacity">252 slots</div>
                    </div>
                </div>
            </div>
            
            <!-- QR Code -->
            <div class="card" style="text-align: center;">
                <div class="card-title" style="justify-content: center;"><span class="icon">📱</span> Your QR Code — Scan to Connect</div>
                <div class="qr-container">
                    <img id="commanderQR" src="/api/vpn-manager.php?action=get_qr&client=commander&commander_key=gositeme-commander-33-vpn" alt="Commander VPN QR Code">
                    <div class="qr-label">Open WireGuard app → Scan QR → Connected</div>
                </div>
                <button class="btn-download" onclick="downloadConfig('commander')">
                    ⬇️ Download Config File
                </button>
            </div>
        </div>
        
        <!-- Your Full Config -->
        <div class="grid">
            <div class="card grid-full">
                <div class="card-title"><span class="icon">📄</span> Your WireGuard Configuration</div>
                <div class="config-block" id="commanderConfig">
                    Loading...
                </div>
            </div>
        </div>
        
        <!-- Device Setup Instructions -->
        <div class="grid">
            <div class="card grid-full">
                <div class="card-title"><span class="icon">📋</span> Setup Instructions — Pick Your Device</div>
                
                <div class="tabs">
                    <button class="tab active" onclick="switchTab('android')">📱 Android</button>
                    <button class="tab" onclick="switchTab('iphone')">🍎 iPhone</button>
                    <button class="tab" onclick="switchTab('windows')">🪟 Windows</button>
                    <button class="tab" onclick="switchTab('mac')">💻 macOS</button>
                    <button class="tab" onclick="switchTab('linux')">🐧 Linux</button>
                </div>
                
                <!-- Android -->
                <div id="tab-android" class="tab-content active">
                    <ol class="setup-steps">
                        <li>Install <strong>WireGuard</strong> from <a href="https://play.google.com/store/apps/details?id=com.wireguard.android" target="_blank">Google Play Store</a></li>
                        <li>Open the WireGuard app and tap the <strong>+</strong> button</li>
                        <li>Choose <strong>"Scan from QR code"</strong> — point your camera at the QR code above</li>
                        <li>Give the tunnel a name (e.g., <code>GoSiteMe-VPN</code>) and tap <strong>Create Tunnel</strong></li>
                        <li>Toggle the switch <strong>ON</strong> — you're connected! 🎉</li>
                    </ol>
                    <p style="margin-top:12px; font-size:13px; color:#666;">💡 <em>Alternative: Tap "Import from file" and use the downloaded .conf file</em></p>
                </div>
                
                <!-- iPhone -->
                <div id="tab-iphone" class="tab-content">
                    <ol class="setup-steps">
                        <li>Install <strong>WireGuard</strong> from the <a href="https://apps.apple.com/app/wireguard/id1441195209" target="_blank">App Store</a></li>
                        <li>Open WireGuard and tap <strong>"Add a tunnel"</strong></li>
                        <li>Choose <strong>"Create from QR code"</strong> and scan the QR code above</li>
                        <li>Name it <code>GoSiteMe-VPN</code> and tap <strong>Save</strong></li>
                        <li>Allow VPN configuration when prompted by iOS</li>
                        <li>Toggle <strong>ON</strong> — you'll see a VPN icon in your status bar! 🔒</li>
                    </ol>
                </div>
                
                <!-- Windows -->
                <div id="tab-windows" class="tab-content">
                    <ol class="setup-steps">
                        <li>Download WireGuard from <a href="https://www.wireguard.com/install/" target="_blank">wireguard.com/install</a> (Windows installer)</li>
                        <li>Install and open WireGuard</li>
                        <li>Click <strong>"Import tunnel(s) from file"</strong> (bottom left)</li>
                        <li>Select the downloaded <code>wg0.conf</code> file</li>
                        <li>Click <strong>"Activate"</strong> — look for the green status indicator ✅</li>
                    </ol>
                    <p style="margin-top:12px; font-size:13px; color:#666;">💡 <em>Or create a new tunnel and paste the config text manually</em></p>
                </div>
                
                <!-- macOS -->
                <div id="tab-mac" class="tab-content">
                    <ol class="setup-steps">
                        <li>Install <strong>WireGuard</strong> from the <a href="https://apps.apple.com/app/wireguard/id1451685025" target="_blank">Mac App Store</a></li>
                        <li>Open WireGuard → click <strong>"Import tunnel(s) from file"</strong></li>
                        <li>Select the downloaded <code>wg0.conf</code> file</li>
                        <li>Allow VPN configuration when macOS prompts you</li>
                        <li>Click <strong>"Activate"</strong> — you'll see the WireGuard icon in your menu bar 🔒</li>
                    </ol>
                    <p style="margin-top:12px; font-size:13px; color:#666;">💡 <em>Alternative: <code>brew install wireguard-tools</code> then <code>wg-quick up ./wg0.conf</code></em></p>
                </div>
                
                <!-- Linux -->
                <div id="tab-linux" class="tab-content">
                    <ol class="setup-steps">
                        <li>Install WireGuard: <code>sudo apt install wireguard</code> (Ubuntu/Debian) or <code>sudo dnf install wireguard-tools</code> (Fedora)</li>
                        <li>Save the config file to <code>/etc/wireguard/wg0.conf</code></li>
                        <li>Set permissions: <code>sudo chmod 600 /etc/wireguard/wg0.conf</code></li>
                        <li>Connect: <code>sudo wg-quick up wg0</code></li>
                        <li>Verify: <code>sudo wg show</code> — should show active handshake</li>
                        <li>Auto-start on boot: <code>sudo systemctl enable wg-quick@wg0</code></li>
                    </ol>
                    <p style="margin-top:12px; font-size:13px; color:#666;">💡 <em>Disconnect with: <code>sudo wg-quick down wg0</code></em></p>
                </div>
            </div>
        </div>
        
        <!-- Manage Devices / Friends -->
        <div class="grid">
            <div class="card grid-full">
                <div class="card-title"><span class="icon">👥</span> Device & Friend Profiles</div>
                
                <div class="add-client-form">
                    <input type="text" id="newClientName" placeholder="Enter name (e.g., danny-laptop, maria-phone, alex-pc)" maxlength="32" pattern="[a-zA-Z0-9_-]+">
                    <button class="btn-primary" onclick="createClient()" id="createBtn">
                        ➕ Create Profile
                    </button>
                </div>
                
                <ul class="client-list" id="clientList">
                    <li class="client-item" style="justify-content: center; color: #666;">Loading profiles...</li>
                </ul>
            </div>
        </div>
        
        <!-- Quick Tips -->
        <div class="grid">
            <div class="card">
                <div class="card-title"><span class="icon">💡</span> Quick Tips</div>
                <ul style="list-style: none; padding: 0; font-size: 13px; color: #888; line-height: 2;">
                    <li>🔒 All traffic is encrypted end-to-end</li>
                    <li>🌍 Browsing appears from server IP (Canada)</li>
                    <li>📱 Works on WiFi, cellular, and ethernet</li>
                    <li>⚡ WireGuard is the fastest VPN protocol</li>
                    <li>🔄 Reconnects automatically after sleep/disconnect</li>
                    <li>🛡️ Each device gets its own unique key pair</li>
                </ul>
            </div>
            <div class="card">
                <div class="card-title"><span class="icon">⚠️</span> Security Notes</div>
                <ul style="list-style: none; padding: 0; font-size: 13px; color: #888; line-height: 2;">
                    <li>🔑 Never share your private key with anyone</li>
                    <li>📵 Revoke friend profiles if no longer needed</li>
                    <li>🔐 Each profile has a unique preshared key</li>
                    <li>📊 Commander profile cannot be revoked</li>
                    <li>🏠 Use separate profiles for home vs work</li>
                    <li>📋 Config is only shown to you (client_id 33)</li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Modal for showing config/QR -->
    <div class="modal-overlay" id="modal">
        <div class="modal">
            <button class="close-btn" onclick="closeModal()">×</button>
            <h3 id="modalTitle">Profile Details</h3>
            <div id="modalContent"></div>
        </div>
    </div>
    
    <!-- Toast -->
    <div class="toast" id="toast"></div>
    
    <script>
        const API_BASE = '/api/vpn-manager.php';
        const AUTH = 'commander_key=gositeme-commander-33-vpn';
        
        // Load everything on page load
        document.addEventListener('DOMContentLoaded', () => {
            loadServerStatus();
            loadCommanderConfig();
            loadClients();
        });
        
        // Enter key to create client
        document.getElementById('newClientName').addEventListener('keydown', (e) => {
            if (e.key === 'Enter') createClient();
        });
        
        async function apiCall(action, params = {}, method = 'GET') {
            const url = new URL(API_BASE, window.location.origin);
            url.searchParams.set('action', action);
            url.searchParams.set('commander_key', 'gositeme-commander-33-vpn');
            
            const opts = { method, headers: { 'X-Commander-Key': 'gositeme-commander-33-vpn' } };
            
            if (method === 'POST') {
                const form = new FormData();
                form.append('action', action);
                for (const [k, v] of Object.entries(params)) form.append(k, v);
                opts.body = form;
            } else {
                for (const [k, v] of Object.entries(params)) url.searchParams.set(k, v);
            }
            
            const res = await fetch(url, opts);
            return res.json();
        }
        
        async function loadServerStatus() {
            try {
                const data = await apiCall('server_status');
                const badge = document.getElementById('serverStatus');
                const isUp = data.status === 'running';
                badge.className = 'status-badge ' + (isUp ? 'online' : 'offline');
                badge.querySelector('span').textContent = isUp ? 'VPN Server Online' : 'VPN Server Offline';
            } catch (e) {
                const badge = document.getElementById('serverStatus');
                badge.className = 'status-badge offline';
                badge.querySelector('span').textContent = 'Connection Error';
            }
        }
        
        async function loadCommanderConfig() {
            try {
                const data = await apiCall('get_config', { client: 'commander' });
                const block = document.getElementById('commanderConfig');
                
                if (data.config) {
                    // Syntax highlight the config
                    let html = data.config
                        .replace(/\[(\w+)\]/g, '<span class="section-header">[$1]</span>')
                        .replace(/(PrivateKey|PublicKey|PresharedKey|Address|DNS|Endpoint|AllowedIPs|PersistentKeepalive)\s*=/g, '<span class="key-name">$1</span> =');
                    
                    block.innerHTML = html + '<button class="copy-btn" onclick="copyConfig(this)">📋 Copy</button>';
                    
                    // Store raw config for copying
                    block.dataset.raw = data.config;
                } else {
                    block.textContent = 'Could not load config: ' + (data.error || 'Unknown error');
                }
            } catch (e) {
                document.getElementById('commanderConfig').textContent = 'Error loading config';
            }
        }
        
        async function loadClients() {
            try {
                const data = await apiCall('list_clients');
                const list = document.getElementById('clientList');
                
                if (!data.clients || data.clients.length === 0) {
                    list.innerHTML = '<li class="client-item" style="justify-content:center;color:#666;">No profiles yet — create one above</li>';
                    return;
                }
                
                // Update capacity display
                const used = data.clients.length;
                document.getElementById('capacity').textContent = `${252 - used} / 252 slots`;
                
                list.innerHTML = data.clients.map(c => {
                    const initials = c.name.substring(0, 2).toUpperCase();
                    const isCommander = c.name === 'commander';
                    
                    return `
                        <li class="client-item">
                            <div class="client-info">
                                <div class="client-avatar" style="${isCommander ? 'background: linear-gradient(135deg, #FFD700, #FF8C00);' : ''}">${isCommander ? '👑' : initials}</div>
                                <div>
                                    <div class="client-name">${escapeHtml(c.name)}${isCommander ? ' <span style="color:#FFD700;font-size:11px;">COMMANDER</span>' : ''}</div>
                                    <div class="client-ip">${escapeHtml(c.ip)}</div>
                                    <div class="client-date">Created: ${escapeHtml(c.created)}</div>
                                </div>
                            </div>
                            <div class="client-actions">
                                <button class="btn-sm btn-config" onclick="showConfig('${escapeHtml(c.name)}')">📄 Config</button>
                                ${c.has_qr ? `<button class="btn-sm btn-qr" onclick="showQR('${escapeHtml(c.name)}')">📱 QR</button>` : ''}
                                <button class="btn-sm btn-config" onclick="downloadConfig('${escapeHtml(c.name)}')">⬇️</button>
                                ${!isCommander ? `<button class="btn-sm btn-revoke" onclick="revokeClient('${escapeHtml(c.name)}')">🗑️</button>` : ''}
                            </div>
                        </li>
                    `;
                }).join('');
            } catch (e) {
                document.getElementById('clientList').innerHTML = '<li class="client-item" style="justify-content:center;color:#ff4444;">Error loading profiles</li>';
            }
        }
        
        async function createClient() {
            const input = document.getElementById('newClientName');
            const name = input.value.trim().replace(/[^a-zA-Z0-9_-]/g, '');
            
            if (!name || name.length < 2) {
                showToast('Enter a name (2+ characters, letters/numbers/hyphens)', true);
                return;
            }
            
            const btn = document.getElementById('createBtn');
            btn.disabled = true;
            btn.textContent = '⏳ Queuing...';
            
            try {
                const data = await apiCall('create_client', { name }, 'POST');
                
                if (data.queued) {
                    showToast(`⏳ "${name}" queued — processing securely. Polling for result...`);
                    input.value = '';
                    btn.textContent = '⏳ Waiting...';
                    // Poll for result every 5s, up to 90s
                    pollForResult(data.request_id, name, btn);
                } else if (data.success) {
                    showToast(`✅ Profile "${name}" created — IP: ${data.ip}`);
                    input.value = '';
                    loadClients();
                    showNewClientModal(name, data);
                    btn.disabled = false;
                    btn.textContent = '➕ Create Profile';
                } else {
                    showToast(data.error || 'Failed to create profile', true);
                    btn.disabled = false;
                    btn.textContent = '➕ Create Profile';
                }
            } catch (e) {
                showToast('Error creating profile', true);
                btn.disabled = false;
                btn.textContent = '➕ Create Profile';
            }
        }
        
        async function pollForResult(requestId, name, btn, attempt = 0) {
            if (attempt > 18) { // 18 * 5s = 90s max
                showToast(`⏳ "${name}" is still processing. Refresh the page in a minute.`, true);
                btn.disabled = false;
                btn.textContent = '➕ Create Profile';
                return;
            }
            
            setTimeout(async () => {
                try {
                    const data = await apiCall('check_request', { id: requestId });
                    
                    if (data.success) {
                        showToast(`✅ Profile "${name}" created — IP: ${data.ip}`);
                        loadClients();
                        showNewClientModal(name, data);
                        btn.disabled = false;
                        btn.textContent = '➕ Create Profile';
                    } else if (data.error) {
                        showToast(`❌ ${data.error}`, true);
                        btn.disabled = false;
                        btn.textContent = '➕ Create Profile';
                    } else {
                        // Still pending
                        btn.textContent = `⏳ Processing (${(attempt+1)*5}s)...`;
                        pollForResult(requestId, name, btn, attempt + 1);
                    }
                } catch (e) {
                    pollForResult(requestId, name, btn, attempt + 1);
                }
            }, 5000);
        }
        
        function showNewClientModal(name, data) {
            const modal = document.getElementById('modal');
            document.getElementById('modalTitle').textContent = `✅ Profile Created: ${name}`;
            
            let html = `
                <p style="color:#00ff88;margin-bottom:16px;">VPN profile for <strong>${escapeHtml(name)}</strong> is ready!</p>
                <div class="config-block" data-raw="${escapeHtml(data.config)}">${
                    data.config
                        .replace(/\[(\w+)\]/g, '<span class="section-header">[$1]</span>')
                        .replace(/(PrivateKey|PublicKey|PresharedKey|Address|DNS|Endpoint|AllowedIPs|PersistentKeepalive)\s*=/g, '<span class="key-name">$1</span> =')
                }<button class="copy-btn" onclick="copyConfig(this)">📋 Copy</button></div>
                <div style="margin-top:16px;display:flex;gap:8px;">
                    <button class="btn-download" onclick="downloadConfigText('${escapeHtml(name)}', \`${escapeJs(data.config)}\`)">⬇️ Download .conf</button>
                    ${data.has_qr ? `<button class="btn-download" style="background:linear-gradient(135deg,#7D00FF,#5a00cc);" onclick="showQR('${escapeHtml(name)}');closeModal();">📱 Show QR</button>` : ''}
                </div>
                <p style="color:#888;font-size:12px;margin-top:12px;">💡 Send the config file or show the QR code to your friend for easy setup</p>
            `;
            
            document.getElementById('modalContent').innerHTML = html;
            modal.classList.add('active');
        }
        
        async function showConfig(name) {
            const data = await apiCall('get_config', { client: name });
            
            if (data.config) {
                const modal = document.getElementById('modal');
                document.getElementById('modalTitle').textContent = `📄 Config: ${name}`;
                
                let html = `
                    <div class="config-block" data-raw="${escapeHtml(data.config)}">${
                        data.config
                            .replace(/\[(\w+)\]/g, '<span class="section-header">[$1]</span>')
                            .replace(/(PrivateKey|PublicKey|PresharedKey|Address|DNS|Endpoint|AllowedIPs|PersistentKeepalive)\s*=/g, '<span class="key-name">$1</span> =')
                    }<button class="copy-btn" onclick="copyConfig(this)">📋 Copy</button></div>
                    <button class="btn-download" onclick="downloadConfigText('${escapeHtml(name)}', \`${escapeJs(data.config)}\`)">⬇️ Download .conf File</button>
                `;
                
                document.getElementById('modalContent').innerHTML = html;
                modal.classList.add('active');
            } else {
                showToast(data.error || 'Error loading config', true);
            }
        }
        
        function showQR(name) {
            const modal = document.getElementById('modal');
            document.getElementById('modalTitle').textContent = `📱 QR Code: ${name}`;
            document.getElementById('modalContent').innerHTML = `
                <div style="text-align:center;padding:16px;">
                    <img src="${API_BASE}?action=get_qr&client=${encodeURIComponent(name)}&${AUTH}" 
                         style="background:white;padding:16px;border-radius:12px;max-width:250px;image-rendering:pixelated;" 
                         alt="QR Code">
                    <p style="color:#888;font-size:13px;margin-top:12px;">Scan with WireGuard app on any mobile device</p>
                </div>
            `;
            modal.classList.add('active');
        }
        
        async function revokeClient(name) {
            if (!confirm(`⚠️ Revoke VPN access for "${name}"?\n\nThis will disconnect them within ~60 seconds and delete their profile. This cannot be undone.`)) {
                return;
            }
            
            try {
                const data = await apiCall('revoke_client', { name }, 'POST');
                if (data.queued) {
                    showToast(`⏳ Revocation of "${name}" queued — takes effect within 60 seconds`);
                    // Poll and refresh after
                    setTimeout(() => loadClients(), 65000);
                } else if (data.success) {
                    showToast(`🗑️ Profile "${name}" revoked`);
                    loadClients();
                } else {
                    showToast(data.error || 'Failed to revoke', true);
                }
            } catch (e) {
                showToast('Error revoking profile', true);
            }
        }
        
        function downloadConfig(name) {
            window.open(`${API_BASE}?action=get_config&client=${encodeURIComponent(name)}&${AUTH}&download=1`, '_blank');
        }
        
        function downloadConfigText(name, config) {
            const blob = new Blob([config], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = name + '-vpn.conf';
            a.click();
            URL.revokeObjectURL(url);
            showToast(`⬇️ Downloaded ${name}-vpn.conf`);
        }
        
        function copyConfig(btn) {
            const block = btn.closest('.config-block');
            const raw = block.dataset.raw || block.textContent;
            navigator.clipboard.writeText(raw).then(() => {
                btn.textContent = '✅ Copied!';
                btn.classList.add('copied');
                setTimeout(() => {
                    btn.textContent = '📋 Copy';
                    btn.classList.remove('copied');
                }, 2000);
            });
        }
        
        function closeModal() {
            document.getElementById('modal').classList.remove('active');
        }
        
        // Close modal on overlay click
        document.getElementById('modal').addEventListener('click', (e) => {
            if (e.target === e.currentTarget) closeModal();
        });
        
        // Close modal on Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeModal();
        });
        
        // Tab switching
        function switchTab(tab) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            document.getElementById('tab-' + tab).classList.add('active');
            event.target.classList.add('active');
        }
        
        // Toast notification
        function showToast(msg, isError = false) {
            const toast = document.getElementById('toast');
            toast.textContent = msg;
            toast.className = 'toast show' + (isError ? ' error' : '');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }
        
        // Escape HTML
        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }
        
        // Escape for JS template literals
        function escapeJs(str) {
            return str.replace(/\\/g, '\\\\').replace(/`/g, '\\`').replace(/\$/g, '\\$');
        }
    </script>
</body>
</html>
