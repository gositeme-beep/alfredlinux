// Glitch effect for hero text
const glitchElement = document.querySelector('.glitch');
if (glitchElement) {
    setInterval(() => {
        if (Math.random() > 0.95) {
            glitchElement.style.transform = `translate(${Math.random() * 4 - 2}px, ${Math.random() * 4 - 2}px)`;
            glitchElement.style.opacity = Math.random() * 0.5 + 0.5;
            setTimeout(() => {
                glitchElement.style.transform = 'translate(0, 0)';
                glitchElement.style.opacity = 1;
            }, 50);
        }
    }, 100);
}

// Yggdrasil Mesh Visualization
const canvas = document.getElementById('meshCanvas');
if (canvas) {
    const ctx = canvas.getContext('2d');
    let width, height;
    let nodes = [];

    function resize() {
        width = canvas.parentElement.clientWidth;
        height = canvas.parentElement.clientHeight;
        canvas.width = width;
        canvas.height = height;
        initNodes();
    }

    function initNodes() {
        nodes = [];
        const numNodes = Math.floor((width * height) / 10000);
        for (let i = 0; i < numNodes; i++) {
            nodes.push({
                x: Math.random() * width,
                y: Math.random() * height,
                vx: (Math.random() - 0.5) * 0.5,
                vy: (Math.random() - 0.5) * 0.5,
                radius: Math.random() * 2 + 1
            });
        }
    }

    function draw() {
        ctx.clearRect(0, 0, width, height);
        
        ctx.fillStyle = '#7000FF';
        ctx.strokeStyle = 'rgba(212, 175, 55, 0.15)';
        
        nodes.forEach(node => {
            node.x += node.vx;
            node.y += node.vy;
            
            if (node.x < 0 || node.x > width) node.vx *= -1;
            if (node.y < 0 || node.y > height) node.vy *= -1;
            
            ctx.beginPath();
            ctx.arc(node.x, node.y, node.radius, 0, Math.PI * 2);
            ctx.fill();
        });
        
        for (let i = 0; i < nodes.length; i++) {
            for (let j = i + 1; j < nodes.length; j++) {
                const dx = nodes[i].x - nodes[j].x;
                const dy = nodes[i].y - nodes[j].y;
                const dist = Math.sqrt(dx * dx + dy * dy);
                
                if (dist < 100) {
                    ctx.beginPath();
                    ctx.moveTo(nodes[i].x, nodes[i].y);
                    ctx.lineTo(nodes[j].x, nodes[j].y);
                    ctx.stroke();
                }
            }
        }
        
        requestAnimationFrame(draw);
    }

    window.addEventListener('resize', resize);
    resize();
    draw();
}

// Interactive Phone Mockup Logic
const layerBoot = document.getElementById('layerBoot');
const layerLock = document.getElementById('layerLock');
const layerHome = document.getElementById('layerHome');
const swipeArea = document.getElementById('swipeArea');

if (layerBoot && layerLock && layerHome && swipeArea) {
    // 1. Boot sequence to Lock Screen transition
    setTimeout(() => {
        layerBoot.classList.remove('active');
        layerLock.classList.add('active');
        updateClock();
    }, 5000); // 5 seconds of boot terminal

    // 2. Swipe to unlock logic
    let isDragging = false;
    let startY = 0;

    swipeArea.addEventListener('mousedown', (e) => {
        isDragging = true;
        startY = e.clientY;
    });

    window.addEventListener('mouseup', () => {
        if (isDragging) {
            isDragging = false;
            swipeArea.style.transform = 'translateY(0)';
            swipeArea.style.opacity = '1';
        }
    });

    window.addEventListener('mousemove', (e) => {
        if (!isDragging) return;
        
        const deltaY = e.clientY - startY;
        if (deltaY < 0) { // Swiping up
            swipeArea.style.transform = `translateY(${deltaY}px)`;
            swipeArea.style.opacity = Math.max(0, 1 - Math.abs(deltaY) / 100);
            
            if (deltaY < -80) { // Threshold reached, unlock!
                isDragging = false;
                layerLock.classList.remove('active');
                layerHome.classList.add('active');
            }
        }
    });

    // Touch support for mobile
    swipeArea.addEventListener('touchstart', (e) => {
        isDragging = true;
        startY = e.touches[0].clientY;
    });

    window.addEventListener('touchend', () => {
        if (isDragging) {
            isDragging = false;
            swipeArea.style.transform = 'translateY(0)';
            swipeArea.style.opacity = '1';
        }
    });

    window.addEventListener('touchmove', (e) => {
        if (!isDragging) return;
        
        const deltaY = e.touches[0].clientY - startY;
        if (deltaY < 0) {
            swipeArea.style.transform = `translateY(${deltaY}px)`;
            swipeArea.style.opacity = Math.max(0, 1 - Math.abs(deltaY) / 100);
            
            if (deltaY < -80) {
                isDragging = false;
                layerLock.classList.remove('active');
                layerHome.classList.add('active');
            }
        }
    });

    // Live Clock for Lock Screen
    function updateClock() {
        const now = new Date();
        let h = now.getHours();
        let m = now.getMinutes();
        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12;
        h = h ? h : 12; 
        m = m < 10 ? '0'+m : m;
        document.getElementById('lockTime').textContent = `${h}:${m}`;
        
        const options = { weekday: 'short', month: 'short', day: 'numeric' };
        document.getElementById('lockDate').textContent = now.toLocaleDateString('en-US', options);
        
        setTimeout(updateClock, 1000);
    }
}

// App Simulator Logic
const appIcons = document.querySelectorAll('.app-icon');
const homeBars = document.querySelectorAll('.home-bar');

appIcons.forEach(icon => {
    icon.addEventListener('click', () => {
        const appName = icon.getAttribute('data-app');
        if (!appName || appName === 'signal') return; // Signal not implemented
        
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

// Home Bar Swipe to Close
homeBars.forEach(bar => {
    let isDraggingBar = false;
    let startYBar = 0;
    
    const handleStart = (y) => {
        isDraggingBar = true;
        startYBar = y;
    };
    
    const handleMove = (y, appLayer) => {
        if (!isDraggingBar) return;
        const deltaY = y - startYBar;
        if (deltaY < 0) { // Swiping UP
            appLayer.style.transform = `translateY(${-deltaY}px)`; // move layer up
            
            if (deltaY < -50) { // Close threshold
                isDraggingBar = false;
                appLayer.style.transform = '';
                appLayer.classList.remove('active');
            }
        }
    };
    
    const handleEnd = (appLayer) => {
        if (isDraggingBar) {
            isDraggingBar = false;
            appLayer.style.transform = '';
        }
    };

    const layer = bar.closest('.app-layer');

    bar.addEventListener('mousedown', (e) => handleStart(e.clientY));
    window.addEventListener('mousemove', (e) => handleMove(e.clientY, layer));
    window.addEventListener('mouseup', () => handleEnd(layer));
    
    bar.addEventListener('touchstart', (e) => handleStart(e.touches[0].clientY));
    window.addEventListener('touchmove', (e) => handleMove(e.touches[0].clientY, layer));
    window.addEventListener('touchend', () => handleEnd(layer));
});

// Terminal Logic
const termInput = document.getElementById('termInput');
const termOutput = document.getElementById('termOutput');

if (termInput && termOutput) {
    termInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            const cmd = termInput.value.trim().toLowerCase();
            termInput.value = '';
            
            // Print command
            const pCmd = document.createElement('p');
            pCmd.innerHTML = `<span class="prompt">root@m3:~#</span> ${cmd}`;
            termOutput.appendChild(pCmd);
            
            // Process command
            const pRes = document.createElement('p');
            if (cmd === 'help') {
                pRes.innerHTML = 'Available commands: help, status, ping mesh, whoami, clear, nuke';
            } else if (cmd === 'status') {
                pRes.innerHTML = 'All sovereign sub-systems online.<br>Daemon: ACTIVE<br>Mesh: ROUTING';
            } else if (cmd === 'ping mesh') {
                pRes.innerHTML = 'PING yggdrasil-mesh...<br>Reply from 200:1db8:85a3::1 time=12ms<br>Reply from 200:1db8:85a3::1 time=14ms';
            } else if (cmd === 'whoami') {
                pRes.innerHTML = 'root (God Mode)';
            } else if (cmd === 'clear') {
                termOutput.innerHTML = '';
                return;
            } else if (cmd === 'nuke') {
                pRes.style.color = '#ff3366';
                pRes.innerHTML = 'Initiating cryptographic wipe...<br>Keys deleted.<br>Rebooting...';
                setTimeout(() => location.reload(), 2000);
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
