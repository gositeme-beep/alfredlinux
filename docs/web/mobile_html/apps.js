
// App Simulator Logic
const appIcons = document.querySelectorAll('.app-icon');

appIcons.forEach(icon => {
    icon.addEventListener('click', () => {
        const appName = icon.getAttribute('data-app');
        if (!appName) return;
        
        const appLayer = document.getElementById('app' + appName.charAt(0).toUpperCase() + appName.slice(1));
        if (appLayer) {
            appLayer.classList.add('active');
            
            // Focus terminal input if opening terminal
            if (appName === 'terminal') {
                setTimeout(() => document.getElementById('termInput').focus(), 400);
            }
        }
    });
});

// Home Button Logic
window.goHome = function(btnElement) {
    const layer = btnElement.closest('.app-layer');
    if (layer) {
        layer.classList.remove('active');
    }
};

// Terminal Logic
const termInput = document.getElementById('termInput');
const termOutput = document.getElementById('termOutput');
let awaitingAscendAnswer = false;

if (termInput && termOutput) {
    termInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            const cmd = termInput.value.trim().toLowerCase();
            termInput.value = '';
            
            if (awaitingAscendAnswer) {
                const pAns = document.createElement('p');
                pAns.innerHTML = `<span class="prompt">></span> ${cmd}`;
                termOutput.appendChild(pAns);
                
                const pRes = document.createElement('p');
                if (cmd === '9' || cmd === 'martyr' || cmd === 'phase 9') {
                    pRes.innerHTML = 'ACCESS GRANTED. Alpha Token: XJ-992-ASC.<br>Welcome to the Kingdom.';
                    pRes.style.color = '#00ff00';
                    pRes.style.textShadow = '0 0 20px #00ff00';
                    if(typeof speakVoice === 'function') speakVoice("Alpha access granted. Sovereignty proven.");
                } else {
                    pRes.innerHTML = 'ACCESS DENIED. Sovereignty unproven.<br>Reverting to standard interface.';
                    pRes.style.color = '#ff0000';
                    setTimeout(() => {
                        document.getElementById('interactivePhone').classList.remove('god-mode');
                        termOutput.innerHTML = '';
                    }, 3000);
                }
                termOutput.appendChild(pRes);
                termOutput.scrollTop = termOutput.scrollHeight;
                awaitingAscendAnswer = false;
                return;
            }

            // Print command
            const pCmd = document.createElement('p');
            pCmd.innerHTML = `<span class="prompt">root@m3:~#</span> ${cmd}`;
            termOutput.appendChild(pCmd);
            
            // Process command
            const pRes = document.createElement('p');
            if (cmd === 'help') {
                pRes.innerHTML = 'Available commands: help, status, ping mesh, whoami, clear, nuke, ls, pwd, date, uname, uptime, /ascend';
            } else if (cmd === 'status') {
                pRes.innerHTML = 'All sovereign sub-systems online.<br>Daemon: ACTIVE<br>Mesh: ROUTING';
            } else if (cmd === 'ping mesh') {
                pRes.innerHTML = 'PING yggdrasil-mesh...<br>Reply from 200:1db8:85a3::1 time=12ms<br>Reply from 200:1db8:85a3::1 time=14ms';
            } else if (cmd === 'whoami') {
                pRes.innerHTML = 'root (God Mode)';
            } else if (cmd === 'clear') {
                termOutput.innerHTML = '';
                return;
            } else if (cmd === 'ls' || cmd === 'ls -l' || cmd === 'ls -la') {
                pRes.innerHTML = `drwx------ 2 root root 4096 Jun 08 20:00 /root<br>drwxr-xr-x 2 root root 4096 Jun 08 20:01 /bin<br>-rwxr-xr-x 1 root root  1337 Jun 08 20:05 /bin/ascend<br>-rw-r--r-- 1 root root 80085 Jun 08 20:10 /etc/kyber_keys.pub`;
            } else if (cmd === 'pwd') {
                pRes.innerHTML = `/data/data/com.sovereign.terminal/home`;
            } else if (cmd === 'date') {
                pRes.innerHTML = new Date().toString();
            } else if (cmd === 'uname' || cmd === 'uname -a') {
                pRes.innerHTML = `Linux localhost 6.1.0-android-sovereign #1 SMP PREEMPT_DYNAMIC aarch64 GNU/Linux`;
            } else if (cmd === 'uptime') {
                pRes.innerHTML = ` 20:42:00 up 999 days, 23:59,  1 user,  load average: 0.00, 0.01, 0.05`;
            } else if (cmd === 'echo') {
                pRes.innerHTML = '';
            } else if (cmd.startsWith("echo ")) {
                pRes.innerHTML = cmd.substring(5);
            } else if (cmd === 'cat /etc/kyber_keys.pub' || cmd === 'cat kyber_keys.pub') {
                pRes.innerHTML = `-----BEGIN PUBLIC KEY-----<br>MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA...<br>-----END PUBLIC KEY-----`;
            } else if (cmd === 'nuke') {
                pRes.style.color = '#ff3366';
                pRes.innerHTML = 'Initiating cryptographic wipe...<br>Keys deleted.<br>Rebooting...';
                setTimeout(() => location.reload(), 2000);
            } else if (cmd === '/ascend') {
                document.getElementById('interactivePhone').classList.add('god-mode');
                termOutput.innerHTML = '';
                pRes.innerHTML = 'SYSTEM OVERRIDE DETECTED.<br><br>To access the Alpha Forge, prove your sovereignty.<br>Which Phase arms the cryptographic wipe?';
                pRes.style.color = '#00ff00';
                awaitingAscendAnswer = true;
                if(typeof playSound === 'function') playSound('ascend');
            } else if (cmd === '') {
                return;
            } else {
                pRes.innerHTML = `bash: ${cmd}: command not found`;
            }
            termOutput.appendChild(pRes);
            termOutput.scrollTop = termOutput.scrollHeight;
        }
    });
}

// Daemon Mock Data Generator
const threatLog = document.getElementById('threatLog');
if (threatLog) {
    const apps = ['com.meta.tracker', 'com.google.android.gms', 'com.tiktok.app', 'com.amazon.awsd', 'system.analytics.daemon'];
    const perms = ['Location', 'Microphone', 'Camera', 'Contacts', 'Telemetry'];
    
    setInterval(() => {
        if (document.getElementById('appDaemon').classList.contains('active')) {
            const time = new Date().toLocaleTimeString('en-US', { hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit' });
            const app = apps[Math.floor(Math.random() * apps.length)];
            const perm = perms[Math.floor(Math.random() * perms.length)];
            
            const p = document.createElement('p');
            p.innerHTML = `<span class="time">${time}</span> <span class="action block">BLOCK</span> <span class="target">${app} (${perm})</span>`;
            threatLog.insertBefore(p, threatLog.firstChild);
            
            if (threatLog.children.length > 20) {
                threatLog.lastChild.remove();
            }
        }
    }, 2500);
}


// QGSM Mobile App Interactive Logic
const btnToggleMine = document.getElementById('btnToggleMine');
const qgsmLogText = document.getElementById('qgsmLogText');
const qgsmMobileBalance = document.getElementById('qgsmMobileBalance');
const btnVrSync = document.getElementById('btnVrSync');

let hashrate = 4.82;
let balance = 1337.42;
let miningBoosted = false;

if (btnToggleMine && qgsmLogText && qgsmMobileBalance) {
    btnToggleMine.addEventListener('click', () => {
        miningBoosted = !miningBoosted;
        if (miningBoosted) {
            hashrate = 14.77;
            btnToggleMine.style.background = 'linear-gradient(90deg, #ffd700, #ff8c00)';
            btnToggleMine.innerHTML = '🔥 QUANTUM BOOST ACTIVE (14.77 MH/s)';
            qgsmLogText.innerHTML = '[Gossip] Multicast sync on [ff02::1]:7722<br>[SHA-3] Hashrate BOOSTED: 14.77 MH/s<br>[UBE] Mining energy welfare rate 3x!';
            balance += 5.00;
            qgsmMobileBalance.innerHTML = balance.toFixed(2) + ' QGSM';
        } else {
            hashrate = 4.82;
            btnToggleMine.style.background = 'linear-gradient(90deg, #00f2fe, #4facfe)';
            btnToggleMine.innerHTML = '⚡ BOOST SHA-3 HASHRATE';
            qgsmLogText.innerHTML = '[Gossip] Connected to [ff02::1]:7722<br>[SHA-3] Hashrate: 4.82 MH/s (TPM Attested)<br>[VR Sync] Metadome UE5 node acknowledged.';
        }
    });
}

if (btnVrSync && qgsmLogText) {
    btnVrSync.addEventListener('click', () => {
        btnVrSync.innerHTML = '⏳ SYNCING WITH UNREAL ENGINE VR...';
        btnVrSync.style.color = '#00f2fe';
        btnVrSync.style.borderColor = '#00f2fe';
        setTimeout(() => {
            btnVrSync.innerHTML = '✅ METADOME VR PASSPORT SYNCED!';
            btnVrSync.style.color = '#2ecc71';
            btnVrSync.style.borderColor = '#2ecc71';
            qgsmLogText.innerHTML += '<br>[UE5] /etc/metadome/passport/wallet.json synced.';
        }, 1200);
    });
}


// Auto-poll live QGSM proof for mobile phone terminal
setInterval(async () => {
    const logEl = document.getElementById('qgsmLogText');
    if (logEl && logEl.offsetParent !== null) {
        try {
            const res = await fetch('/api/qgsm-proof.php');
            const data = await res.json();
            if (!logEl.innerHTML.includes('LIVE ATTESTATION')) {
                logEl.innerHTML = `<span style="color:#2ecc71;font-weight:bold;">[LIVE ATTESTATION] Block #${data.current_block_height}</span><br>` + logEl.innerHTML;
            }
        } catch(e) {}
    }
}, 4000);
