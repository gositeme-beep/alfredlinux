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

// Telemetry Mirror Logic
document.addEventListener('DOMContentLoaded', () => {
    const overlay = document.getElementById('telemetryOverlay');
    const textEl = document.getElementById('telemetryText');
    if (!overlay || !textEl) return;

    fetch('https://ipapi.co/json/')
        .then(res => res.json())
        .then(data => {
            const msg = `> WARNING: Telemetry detected.\n> IP: ${data.ip || 'UNKNOWN'}\n> Location: ${data.city || 'UNKNOWN'}, ${data.country_name || 'UNKNOWN'}\n> ISP: ${data.org || 'UNKNOWN'}\n> Big Tech is tracking you.\n> Unplug from the Matrix.`;
            typeText(msg, textEl, 0, () => {
                setTimeout(() => {
                    overlay.classList.add('fade-out');
                }, 2000);
            });
        })
        .catch(() => {
            const msg = `> WARNING: Telemetry detected.\n> IP: LOGGED\n> Location: LOGGED\n> ISP: LOGGED\n> Big Tech is tracking you.\n> Unplug from the Matrix.`;
            typeText(msg, textEl, 0, () => {
                setTimeout(() => {
                    overlay.classList.add('fade-out');
                }, 2000);
            });
        });

    function typeText(text, el, index, cb) {
        if (index < text.length) {
            if (text.charAt(index) === '\n') {
                el.innerHTML += '<br>';
            } else {
                el.innerHTML += text.charAt(index);
            }
            setTimeout(() => typeText(text, el, index + 1, cb), 50);
        } else {
            if (cb) cb();
        }
    }
});

// Infection Map Logic
const infCanvas = document.getElementById('infectionMap');
if (infCanvas) {
    const infCtx = infCanvas.getContext('2d');
    let iw, ih;
    let infNodes = [];

    function resizeInf() {
        iw = infCanvas.parentElement.clientWidth;
        ih = infCanvas.parentElement.clientHeight;
        infCanvas.width = iw;
        infCanvas.height = ih;
    }

    function spawnNode() {
        infNodes.push({
            x: Math.random() * iw,
            y: Math.random() * ih,
            life: 0,
            maxLife: Math.random() * 100 + 50
        });
        document.getElementById('nodeCount').innerText = (1402 + infNodes.length).toLocaleString();
        setTimeout(spawnNode, Math.random() * 1000 + 200);
    }

    function drawInf() {
        infCtx.fillStyle = 'rgba(0, 0, 0, 0.1)';
        infCtx.fillRect(0, 0, iw, ih);

        for (let i = infNodes.length - 1; i >= 0; i--) {
            let n = infNodes[i];
            n.life++;
            
            // 100,000x Magnetic Attraction
            if (mouseX > 0 && mouseY > 0) {
                const mx = mouseX - infCanvas.getBoundingClientRect().left;
                const my = mouseY - infCanvas.getBoundingClientRect().top;
                const dist = Math.sqrt((n.x - mx)**2 + (n.y - my)**2);
                if (dist < 200) {
                    n.x += (mx - n.x) * 0.05;
                    n.y += (my - n.y) * 0.05;
                }
            }

            infCtx.beginPath();
            infCtx.arc(n.x, n.y, 3, 0, Math.PI * 2);
            infCtx.fillStyle = `rgba(255, 215, 0, ${1 - n.life/n.maxLife})`;
            infCtx.fill();
            infCtx.shadowBlur = 10;
            infCtx.shadowColor = '#D4AF37';

            if (n.life > n.maxLife) {
                infNodes.splice(i, 1);
            } else {
                for (let j = 0; j < infNodes.length; j++) {
                    if (i !== j) {
                        const dx = n.x - infNodes[j].x;
                        const dy = n.y - infNodes[j].y;
                        if (Math.sqrt(dx*dx + dy*dy) < 150) {
                            infCtx.beginPath();
                            infCtx.moveTo(n.x, n.y);
                            infCtx.lineTo(infNodes[j].x, infNodes[j].y);
                            infCtx.strokeStyle = `rgba(112, 0, 255, ${0.2 * (1 - n.life/n.maxLife)})`;
                            infCtx.stroke();
                        }
                    }
                }
            }
            infCtx.shadowBlur = 0;
        }

        requestAnimationFrame(drawInf);
    }

    let mouseX = 0, mouseY = 0;
    infCanvas.addEventListener('mousemove', (e) => {
        mouseX = e.clientX;
        mouseY = e.clientY;
    });
    infCanvas.addEventListener('mouseleave', () => {
        mouseX = 0; mouseY = 0;
    });

    window.addEventListener('resize', resizeInf);
    resizeInf();
    spawnNode();
    drawInf();
}

// ==========================================
// THE 100X EXPANSION LOGIC
// ==========================================

// 1. Cinematic Boot Sequence
function triggerBootSequence() {
    const bootContainer = document.getElementById('bootSequence');
    const bootText = document.getElementById('bootText');
    if (!bootContainer || !bootText) return;
    
    bootContainer.style.display = 'flex';
    
    const logs = [
        "<span class='ok'>[ OK ]</span> Mounting Yggdrasil Core...",
        "<span class='ok'>[ OK ]</span> Initializing Kyber Keys...",
        "<span class='warn'>[WARN]</span> Unsigned cellular baseband detected. Bypassing...",
        "<span class='ok'>[ OK ]</span> Hardware-level kill switches armed.",
        "<span class='ok'>[ OK ]</span> Snapdragon NPU offline matrix engaged.",
        "Starting Archangel Daemon...",
        "<span class='ok'>[ OK ]</span> Archangel Daemon Online.",
        "Welcome to the Kingdom."
    ];
    
    let i = 0;
    const interval = setInterval(() => {
        if (i < logs.length) {
            bootText.innerHTML += `<p>${logs[i]}</p>`;
            playSound('blip');
            i++;
        } else {
            clearInterval(interval);
            setTimeout(() => {
                bootContainer.style.display = 'none';
                playSound('boot');
            }, 800);
        }
    }, 150);
}

// Trigger boot sequence right after Telemetry overlay fades
const originalTelemetryHide = document.querySelector('.telemetry-overlay');
if (originalTelemetryHide) {
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.target.classList.contains('fade-out')) {
                setTimeout(() => {
                    triggerBootSequence();
                    if(typeof speakVoice === 'function') setTimeout(() => speakVoice("Welcome to the Kingdom. Telemetry severed."), 2000);
                }, 500);
                observer.disconnect();
            }
        });
    });
    observer.observe(originalTelemetryHide, { attributes: true, attributeFilter: ['class'] });
}


// 2. 3D Parallax Phone Simulator
document.addEventListener('mousemove', (e) => {
    const phone = document.getElementById('interactivePhone');
    if (!phone || phone.classList.contains('god-mode')) return;
    
    const xAxis = (window.innerWidth / 2 - e.pageX) / 25;
    const yAxis = (window.innerHeight / 2 - e.pageY) / 25;
    
    phone.style.setProperty('--rx', `${yAxis}deg`);
    phone.style.setProperty('--ry', `${-xAxis}deg`);
});


// 3. Threat Deflection Toasts
const threatIps = ['31.13.X.X', '142.250.X.X', '52.94.X.X', '13.107.X.X'];
const threatNames = ['facebook.com', 'google-analytics.com', 'amazon-adsystem.com', 'tiktok-telemetry'];
const threatActions = ['Blocked cross-site tracker from', 'Neutralized fingerprinting script via', 'Intercepted hidden microphone ping from', 'Severed unauthorized location request from'];

setInterval(() => {
    const toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) return;
    
    const action = threatActions[Math.floor(Math.random() * threatActions.length)];
    const name = threatNames[Math.floor(Math.random() * threatNames.length)];
    const ip = threatIps[Math.floor(Math.random() * threatIps.length)];
    
    const toast = document.createElement('div');
    toast.className = 'threat-toast';
    toast.innerHTML = `<strong>[ARCHANGEL]</strong> ${action} ${name} (IP: ${ip})`;
    
    toastContainer.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('show');
        playSound('block');
    }, 100);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 400);
    }, 4000);
    
}, 12000);


// 4. Mathematical Web Audio API (Sound Synthesis)
const audioCtx = new (window.AudioContext || window.webkitAudioContext)();

function playSound(type) {
    if (audioCtx.state === 'suspended') audioCtx.resume();
    
    const osc = audioCtx.createOscillator();
    const gain = audioCtx.createGain();
    
    osc.connect(gain);
    gain.connect(audioCtx.destination);
    
    const now = audioCtx.currentTime;
    
    if (type === 'click') {
        // Deep sub-bass click for app opening
        osc.type = 'sine';
        osc.frequency.setValueAtTime(150, now);
        osc.frequency.exponentialRampToValueAtTime(40, now + 0.1);
        gain.gain.setValueAtTime(0.5, now);
        gain.gain.exponentialRampToValueAtTime(0.01, now + 0.1);
        osc.start(now);
        osc.stop(now + 0.1);
    } else if (type === 'blip') {
        // Terminal text typing blip
        osc.type = 'square';
        osc.frequency.setValueAtTime(800, now);
        gain.gain.setValueAtTime(0.05, now);
        gain.gain.exponentialRampToValueAtTime(0.01, now + 0.05);
        osc.start(now);
        osc.stop(now + 0.05);
    } else if (type === 'block') {
        // Threat blocked low thump
        osc.type = 'triangle';
        osc.frequency.setValueAtTime(100, now);
        osc.frequency.linearRampToValueAtTime(50, now + 0.2);
        gain.gain.setValueAtTime(0.3, now);
        gain.gain.exponentialRampToValueAtTime(0.01, now + 0.2);
        osc.start(now);
        osc.stop(now + 0.2);
    } else if (type === 'boot') {
        // System online sweep
        osc.type = 'sine';
        osc.frequency.setValueAtTime(200, now);
        osc.frequency.exponentialRampToValueAtTime(800, now + 0.5);
        gain.gain.setValueAtTime(0, now);
        gain.gain.linearRampToValueAtTime(0.3, now + 0.2);
        gain.gain.linearRampToValueAtTime(0, now + 0.5);
        osc.start(now);
        osc.stop(now + 0.5);
    } else if (type === 'ascend') {
        // God-mode expanding frequency sweep
        osc.type = 'sawtooth';
        osc.frequency.setValueAtTime(50, now);
        osc.frequency.exponentialRampToValueAtTime(2000, now + 1.5);
        gain.gain.setValueAtTime(0, now);
        gain.gain.linearRampToValueAtTime(0.4, now + 0.5);
        gain.gain.linearRampToValueAtTime(0, now + 1.5);
        osc.start(now);
        osc.stop(now + 1.5);
    }
}

// Bind audio to UI interactions
document.addEventListener('click', () => {
    if (audioCtx.state === 'suspended') audioCtx.resume();
}, { once: true });

document.querySelectorAll('.app-icon').forEach(icon => {
    icon.addEventListener('click', () => playSound('click'));
});


// ==========================================
// THE 1000X OVERDRIVE LOGIC
// ==========================================

// 1. The Sovereign Voice (Neural AI + Spatial Reverb + Visualizer)
let convolverNode = null;
let analyserNode = null;
let visualizerCanvas = document.getElementById('voiceVisualizer');
let visualizerCtx = visualizerCanvas ? visualizerCanvas.getContext('2d') : null;

function createReverb() {
    const sampleRate = audioCtx.sampleRate;
    const length = sampleRate * 2.5; // 2.5s decay room
    const impulse = audioCtx.createBuffer(2, length, sampleRate);
    const left = impulse.getChannelData(0);
    const right = impulse.getChannelData(1);
    for (let i = 0; i < length; i++) {
        const decay = Math.exp(-i / (sampleRate * 0.5));
        left[i] = (Math.random() * 2 - 1) * decay;
        right[i] = (Math.random() * 2 - 1) * decay;
    }
    convolverNode = audioCtx.createConvolver();
    convolverNode.buffer = impulse;
    
    analyserNode = audioCtx.createAnalyser();
    analyserNode.fftSize = 256;
    
    convolverNode.connect(analyserNode);
    analyserNode.connect(audioCtx.destination);
}

function drawVisualizer() {
    if (!visualizerCanvas || !analyserNode) return;
    const bufferLength = analyserNode.frequencyBinCount;
    const dataArray = new Uint8Array(bufferLength);
    analyserNode.getByteFrequencyData(dataArray);
    
    let sum = 0;
    for (let i = 0; i < bufferLength; i++) sum += dataArray[i];
    const avg = sum / bufferLength;
    
    if (avg > 5) {
        visualizerCanvas.classList.add('active');
    } else {
        visualizerCanvas.classList.remove('active');
    }
    
    visualizerCanvas.width = window.innerWidth;
    visualizerCanvas.height = window.innerHeight;
    
    const cx = visualizerCanvas.width / 2;
    const cy = visualizerCanvas.height / 2;
    const radius = 150 + avg * 1.5;
    
    visualizerCtx.clearRect(0, 0, visualizerCanvas.width, visualizerCanvas.height);
    visualizerCtx.beginPath();
    for (let i = 0; i <= bufferLength; i++) {
        const value = dataArray[i % bufferLength] / 255.0;
        const angle = (i / bufferLength) * Math.PI * 2;
        const r = radius + value * 100;
        const x = cx + Math.cos(angle) * r;
        const y = cy + Math.sin(angle) * r;
        if (i === 0) visualizerCtx.moveTo(x, y);
        else visualizerCtx.lineTo(x, y);
    }
    visualizerCtx.closePath();
    visualizerCtx.lineWidth = 3;
    visualizerCtx.strokeStyle = `rgba(255, 215, 0, ${Math.min(avg / 50, 1)})`;
    visualizerCtx.shadowBlur = 20;
    visualizerCtx.shadowColor = '#D4AF37';
    if(document.body.classList.contains('root-mode')) {
        visualizerCtx.strokeStyle = `rgba(0, 255, 0, ${Math.min(avg / 50, 1)})`;
        visualizerCtx.shadowColor = '#00ff00';
    }
    visualizerCtx.stroke();
    
    requestAnimationFrame(drawVisualizer);
}

const voiceMap = {
    "Welcome to the Kingdom. Telemetry severed.": "welcome.mp3",
    "Root access granted. Archangel interface active.": "root.mp3",
    "Restoring standard visual interface.": "restore.mp3",
    "Emergency Wipe Initiated. Burn protocol active.": "wipe.mp3",
    "Alpha access granted. Sovereignty proven.": "ascend.mp3",
    "Cryptographic truth.": "manifesto.mp3",
    "Yggdrasil Mesh scanning...": "radar.mp3",
    "Sovereign Repository accessed.": "forge.mp3"
};

function speakVoice(text) {
    if (audioCtx.state === 'suspended') audioCtx.resume();
    if (!convolverNode) {
        createReverb();
        drawVisualizer();
    }
    
    const file = voiceMap[text];
    
    // Shared fallback to browser speech synthesis
    function fallbackSpeak() {
        if ('speechSynthesis' in window) {
            const u = new SpeechSynthesisUtterance(text);
            u.pitch = 0.3; u.rate = 0.8;
            window.speechSynthesis.speak(u);
        }
    }
    
    if (!file) {
        fallbackSpeak();
        return;
    }
    
    const audio = new Audio('audio/' + file);
    audio.crossOrigin = "anonymous";
    
    // If the MP3 fails to load (404), fall back to speech synthesis
    audio.addEventListener('error', () => {
        console.warn('[VOICE] MP3 not found: ' + file + ', falling back to speech synthesis.');
        fallbackSpeak();
    });
    
    audio.addEventListener('canplaythrough', () => {
        try {
            const source = audioCtx.createMediaElementSource(audio);
            const gain = audioCtx.createGain();
            gain.gain.value = 2.0;
            source.connect(gain);
            gain.connect(convolverNode);
            audio.play();
        } catch(e) {
            // If createMediaElementSource fails (e.g. already connected), just play directly
            audio.play().catch(() => fallbackSpeak());
        }
    }, { once: true });
    
    audio.load();
}

// 2. Global Root Access Toggle (The Matrix Mode)
const rootToggle = document.getElementById('rootToggle');
if (rootToggle) {
    rootToggle.addEventListener('click', () => {
        document.body.classList.toggle('root-mode');
        playSound('click');
        if (document.body.classList.contains('root-mode')) {
            speakVoice("Root access granted. Archangel interface active.");
            rootToggle.innerText = "[ RESTORE UI ]";
        } else {
            speakVoice("Restoring standard visual interface.");
            rootToggle.innerText = "[ ROOT ACCESS ]";
        }
    });
}

// 3. The Interactive Kill-Switch
const killSwitch = document.getElementById('killSwitch');
if (killSwitch) {
    killSwitch.addEventListener('click', () => {
        playSound('ascend'); // Reuse the escalating alarm sound
        speakVoice("Emergency Wipe Initiated. Burn protocol active.");
        
        // Strobe effect
        document.body.style.transition = "none";
        let strober = setInterval(() => {
            document.body.style.backgroundColor = document.body.style.backgroundColor === 'red' ? 'black' : 'red';
        }, 100);
        
        setTimeout(() => {
            clearInterval(strober);
            document.body.style.backgroundColor = 'black';
            document.body.innerHTML = '<h1 style="color:red; text-align:center; margin-top:20vh; font-family:monospace; font-size:4rem;">SYSTEM DESTROYED.</h1>';
            setTimeout(() => location.reload(), 2000);
        }, 3000);
    });
}

// 4. Glitch-Core Scroll Mechanics (Intersection Observer)
const glitchSections = document.querySelectorAll('.features-grid, .timeline-container');
const observerOptions = { root: null, rootMargin: '0px', threshold: 0.2 };

const glitchObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('glitch-section');
            entry.target.classList.add('glitch-enter');
            playSound('blip');
            observer.unobserve(entry.target);
        }
    });
}, observerOptions);

glitchSections.forEach(section => {
    section.classList.add('glitch-section');
    glitchObserver.observe(section);
});

// 5. 10,000x Omnipresence: Expanded Voice Hooks
const manifestoBtn = document.querySelector('a[href="manifesto.html"]');
if (manifestoBtn) {
    manifestoBtn.addEventListener('mouseenter', () => {
        speakVoice("Cryptographic truth.");
    }, { once: true });
}

document.querySelectorAll('.app-icon').forEach(icon => {
    icon.addEventListener('click', (e) => {
        try {
            const title = e.currentTarget.querySelector('span').innerText;
            if (title === 'Radar') {
                if(typeof speakVoice === 'function') speakVoice("Yggdrasil Mesh scanning...");
            } else if (title === 'Forge') {
                if(typeof speakVoice === 'function') speakVoice("Sovereign Repository accessed.");
            } else if (title === 'Signal') {
                if(typeof speakVoice === 'function') speakVoice("Ghost protocol active. Comms secured.");
            } else if (title === 'Camera') {
                if(typeof speakVoice === 'function') speakVoice("Hardware abstraction layer bypassed.");
            }
        } catch(err) { console.error("App icon error:", err); }
    });
});

// ==========================================
// 100X OMNIPRESENCE & ROOT-LEVEL ASCENDANCY
// ==========================================

// Preload high-quality TTS voices asynchronously
if ('speechSynthesis' in window) {
    window.speechSynthesis.getVoices();
    window.speechSynthesis.onvoiceschanged = () => {
        window.speechSynthesis.getVoices();
    };
}

// 1. The Biometric Sentinel (Webcam Background)
const webcamBg = document.getElementById('webcamBg');
const webcamTarget = document.getElementById('webcamTarget');
if (webcamBg && navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
    navigator.mediaDevices.getUserMedia({ video: true })
    .then(stream => {
        webcamBg.srcObject = stream;
        webcamBg.style.display = 'block';
        if (webcamTarget) webcamTarget.style.display = 'block';
    })
    .catch(err => console.log("Biometric lock bypassed by user."));
}

// 2. Global Reality Decryption Cipher
const decryptBtn = document.getElementById('decryptBtn');
if (decryptBtn) {
    decryptBtn.addEventListener('click', () => {
        try {
            if(typeof playSound === 'function') playSound('ascend');
            if(typeof speakVoice === 'function') speakVoice("Decrypting reality matrix.");
        } catch(e) { console.error("Audio bypass:", e); }
        
        const allTextElements = document.querySelectorAll('p, h1, h2, h3, span, div.w-value, div.w-header');
        const chars = "!@#$%^&*()_+-=[]{}|;:,.<>?/`~XYZ";
        
        allTextElements.forEach(el => {
            // Skip non-text nodes or visualizer/canvas
            if(el.children.length > 0) return; 
            const originalText = el.innerText;
            if(!originalText || originalText.trim() === '') return;
            
            let iterations = 0;
            const interval = setInterval(() => {
                try {
                    el.innerText = originalText.split('').map((char, index) => {
                        if(index < iterations) {
                            return originalText[index];
                        }
                        return chars[Math.floor(Math.random() * chars.length)];
                    }).join('');
                    
                    if(iterations >= originalText.length) {
                        clearInterval(interval);
                        el.innerText = originalText;
                    }
                    iterations += 1/3;
                } catch(e) {
                    clearInterval(interval);
                    el.innerText = originalText;
                }
            }, 30);
        });
    });
}

// 3. Hero Carousel Automation
const slides = document.querySelectorAll('.carousel-slide');
const dots = document.querySelectorAll('.carousel-dots .dot');
let currentSlide = 0;
let carouselInterval = null;

window.setSlide = function(index) {
    if(!slides.length) return;
    slides[currentSlide].classList.remove('active');
    if(dots[currentSlide]) dots[currentSlide].classList.remove('active');
    
    currentSlide = index;
    if(currentSlide >= slides.length) currentSlide = 0;
    
    slides[currentSlide].classList.add('active');
    if(dots[currentSlide]) dots[currentSlide].classList.add('active');
    
    clearInterval(carouselInterval);
    carouselInterval = setInterval(() => window.setSlide(currentSlide + 1), 6000);
};
if(slides.length > 0) {
    carouselInterval = setInterval(() => window.setSlide(currentSlide + 1), 6000);
}

// 4. Phone App Simulations
// Radar
const radarNodes = document.getElementById('radarNodes');
if (radarNodes) {
    setInterval(() => {
        if(document.getElementById('appRadar') && document.getElementById('appRadar').classList.contains('active')) {
            const node = document.createElement('div');
            node.className = 'radar-node';
            const angle = Math.random() * Math.PI * 2;
            const radius = Math.random() * 90;
            node.style.left = `calc(50% + ${Math.cos(angle) * radius}px)`;
            node.style.top = `calc(50% + ${Math.sin(angle) * radius}px)`;
            radarNodes.appendChild(node);
            setTimeout(() => node.remove(), 2000);
        }
    }, 400);
}

// Forge
const forgeTerminal = document.getElementById('forgeTerminal');
if (forgeTerminal) {
    const lines = [
        "Compiling x86_64 Sovereign Kernel...",
        "CC      arch/x86/kernel/cpu/common.o",
        "CC      crypto/aes_generic.o",
        "CC      crypto/chacha20poly1305.o",
        "Building Yggdrasil Network Stack...",
        "CC      net/ipv6/ah6.o",
        "CC      net/ipv6/esp6.o",
        "Stripping telemetry modules...",
        "LD      vmlinux",
        "Build complete. Image verified with Kyber-1024 signature."
    ];
    let forgeLine = 0;
    setInterval(() => {
        if(document.getElementById('appForge') && document.getElementById('appForge').classList.contains('active')) {
            const p = document.createElement('div');
            p.innerText = `[${(Math.random()*100).toFixed(3)}] ${lines[forgeLine] || "OBJCOPY arch/x86/boot/bzImage"}`;
            forgeTerminal.appendChild(p);
            forgeTerminal.scrollTop = forgeTerminal.scrollHeight;
            forgeLine = (forgeLine + 1) % lines.length;
        }
    }, 300);
}

// Codex
const codexHash = document.getElementById('codexHash');
if (codexHash) {
    setInterval(() => {
        if(document.getElementById('appCodex') && document.getElementById('appCodex').classList.contains('active')) {
            codexHash.innerText = "0x" + Math.floor(Math.random()*16777215).toString(16).toUpperCase() + Math.floor(Math.random()*16777215).toString(16).toUpperCase();
        }
    }, 50);
}

// Lazarus
const lazTemp = document.getElementById('lazTemp');
const lazClock = document.getElementById('lazClock');
if (lazTemp && lazClock) {
    setInterval(() => {
        if(document.getElementById('appLazarus') && document.getElementById('appLazarus').classList.contains('active')) {
            lazTemp.innerText = (28 + Math.random() * 8).toFixed(1) + "°C";
            lazClock.innerText = (4.0 + Math.random() * 0.4).toFixed(2) + " GHz";
        }
    }, 1000);
}

// Martyr
const martyrBtn = document.getElementById('martyrActivate');
const martyrTimer = document.getElementById('martyrTimer');
if (martyrBtn && martyrTimer) {
    martyrBtn.addEventListener('click', () => {
        martyrBtn.style.display = 'none';
        let time = 300;
        const intv = setInterval(() => {
            time--;
            const ms = time % 100;
            const s = Math.floor(time / 100);
            martyrTimer.innerText = `0${s}:${ms < 10 ? '0'+ms : ms}`;
            if(time <= 0) {
                clearInterval(intv);
                document.getElementById('appMartyr').innerHTML = '<div style="color:red;font-size:3rem;text-align:center;margin-top:50%;">WIPED</div>';
            }
        }, 10);
    });
}

// ==========================================
// THE 1,000,000X ASCENDANCY EXPANSION
// ==========================================

// Phase 1: God-Mode Terminal Overlay
const terminalLayer = document.getElementById('godTerminalLayer');
const terminalInput = document.getElementById('godTerminalInput');
const terminalOutput = document.getElementById('godTerminalOutput');
const closeTerminalBtn = document.getElementById('closeGodTerminal');
let keyBuffer = '';
const triggerWord = 'ASCEND';

document.addEventListener('keydown', (e) => {
    if(terminalLayer && terminalLayer.style.display !== 'none') return; // Don't track if already open
    if(e.key && e.key.length === 1) { // Only track alphanumeric keys
        keyBuffer += e.key.toUpperCase();
        // Keep the buffer at exactly the length of the trigger word by slicing from the end
        if (keyBuffer.length > triggerWord.length) {
            keyBuffer = keyBuffer.slice(-triggerWord.length);
        }
        if (keyBuffer === triggerWord) {
            activateGodTerminal();
            keyBuffer = '';
        }
    }
});

function activateGodTerminal() {
    if(!terminalLayer) return;
    terminalLayer.style.display = 'flex';
    if(typeof playSound === 'function') playSound('ascend');
    if(typeof speakVoice === 'function') speakVoice("God Mode terminal access granted.");
    setTimeout(() => { if(terminalInput) terminalInput.focus(); }, 100);
}

if(closeTerminalBtn) {
    closeTerminalBtn.addEventListener('click', () => {
        if(terminalLayer) terminalLayer.style.display = 'none';
    });
}

if(terminalInput) {
    terminalInput.addEventListener('keydown', (e) => {
        if(e.key === 'Enter') {
            const cmd = terminalInput.value.trim();
            terminalInput.value = '';
            
            const p = document.createElement('p');
            p.style.color = '#fff';
            p.innerText = `root@omnipresence:~# ${cmd}`;
            terminalOutput.appendChild(p);
            
            processGodCommand(cmd.toLowerCase());
        }
    });
}

function processGodCommand(cmd) {
    const res = document.createElement('p');
    res.style.color = '#00ff00';
    
    switch(cmd) {
        case 'help':
            res.innerHTML = `Available Directives:<br> - status : System overview<br> - scan_network : Deep scan sovereign mesh<br> - clear : Wipe terminal memory<br> - sudo reboot : Destabilize UI<br> - ls : List directories<br> - pwd : Print working directory<br> - whoami : Print user<br> - date : System time<br> - uname -a : Kernel version<br> - uptime : Server uptime`;
            break;
        case 'status':
            res.innerHTML = `[OK] Kyber encryption active.<br>[OK] Archangel Daemon running.<br>[OK] Global Mesh Nodes: 1,402`;
            break;
        case 'hack_network':
        case 'scan_network':
            res.innerHTML = `Initiating deep sovereign mesh scan...<br>[====      ] 40% (Mapping encrypted nodes)<br>[========  ] 80% (Verifying Kyber handshakes)<br>[==========] 100% ALL NODES VERIFIED & SECURED.`;
            if(typeof playSound === 'function') playSound('blip');
            break;
        case 'clear':
            terminalOutput.innerHTML = '';
            return;
        case 'sudo reboot':
            res.innerHTML = `Rebooting...`;
            setTimeout(() => location.reload(), 1000);
            break;
        case 'ls':
            res.innerHTML = `archangel_daemon/  kyber_keys/  mesh_logs/  manifesto.txt  threat_matrix.bin`;
            break;
        case 'ls -l':
        case 'ls -la':
            res.innerHTML = `drwxr-xr-x 2 root root 4096 Jun 08 20:00 archangel_daemon/<br>drwx------ 2 root root 4096 Jun 08 20:01 kyber_keys/<br>drwxr-xr-x 2 root root 4096 Jun 08 20:05 mesh_logs/<br>-rw-r--r-- 1 root root  1337 Jun 08 20:10 manifesto.txt<br>-rwxr-xr-x 1 root root 80085 Jun 08 20:15 threat_matrix.bin`;
            break;
        case 'pwd':
            res.innerHTML = `/root/sovereign_core`;
            break;
        case 'whoami':
            res.innerHTML = `archangel_root`;
            break;
        case 'date':
            res.innerHTML = new Date().toString();
            break;
        case 'uname':
        case 'uname -a':
            res.innerHTML = `Linux omnipresence 6.1.0-sovereign-hardened #1 SMP PREEMPT_DYNAMIC x86_64 GNU/Linux`;
            break;
        case 'uptime':
            res.innerHTML = ` 20:42:00 up 999 days, 23:59,  1 user,  load average: 0.00, 0.01, 0.05`;
            break;
        case 'echo':
        case 'cat manifesto.txt':
            res.innerHTML = `"We encrypt reality to forge our own." - The Sovereign Protocol`;
            break;
        case '':
            return;
        default:
            if (cmd.startsWith("echo ")) {
                res.innerHTML = cmd.substring(5);
            } else {
                res.innerText = `bash: ${cmd}: command not found`;
                res.style.color = 'red';
            }
    }
    terminalOutput.appendChild(res);
    terminalOutput.scrollTop = terminalOutput.scrollHeight;
}

// Phase 2: Global Threat Globe (Interactive)
const globeCanvas = document.getElementById('threatGlobeCanvas');
if (globeCanvas) {
    const glCtx = globeCanvas.getContext('2d');
    let gw, gh;
    let rotation = 0;
    let rotationSpeed = 0.005;
    let globeDragging = false;
    let globeStartX = 0;
    let shieldPulse = 0; // for click pulse effect
    
    // Oracle wisdom lines
    const oracleLines = [
        "The mesh sees all. You are shielded.",
        "1,402 sovereign nodes stand between you and the corporate eye.",
        "Your packets are ghosts. Untraceable. Unbreakable.",
        "Kyber encryption holds. Quantum threats neutralized.",
        "The Archangel watches. No telemetry escapes.",
        "Every corporate probe that touches this shield is vaporized.",
        "Sovereignty is not given. It is mathematically enforced.",
        "The Yggdrasil root network spans 14 continents.",
        "Your digital fingerprint has been erased from 47 tracking databases.",
        "The Lazarus AI is learning. Evolving. Protecting.",
        "Post-quantum lattice cryptography engaged. You are 50 years ahead of them.",
        "Ring-0 control confirmed. The hardware obeys only you."
    ];
    
    function resizeGlobe() {
        const rect = globeCanvas.getBoundingClientRect();
        gw = rect.width || window.innerWidth;
        gh = rect.height || 400;
        globeCanvas.width = gw;
        globeCanvas.height = gh;
    }
    
    window.addEventListener('resize', resizeGlobe);
    resizeGlobe();
    
    // Drag to rotate
    globeCanvas.addEventListener('mousedown', (e) => {
        globeDragging = true;
        globeStartX = e.clientX;
        globeCanvas.style.cursor = 'grabbing';
    });
    
    window.addEventListener('mouseup', () => {
        if (globeDragging) {
            globeDragging = false;
            globeCanvas.style.cursor = 'grab';
        }
    });
    
    window.addEventListener('mousemove', (e) => {
        if (!globeDragging) return;
        const delta = e.clientX - globeStartX;
        rotation += delta * 0.005;
        globeStartX = e.clientX;
    });
    
    // Click to speak Oracle wisdom
    let lastClickTime = 0;
    globeCanvas.addEventListener('click', (e) => {
        const now = Date.now();
        if (now - lastClickTime < 2000) return; // 2s cooldown
        lastClickTime = now;
        
        // Trigger shield pulse
        shieldPulse = 1.0;
        
        // Pick a random oracle line
        const line = oracleLines[Math.floor(Math.random() * oracleLines.length)];
        if (typeof speakVoice === 'function') speakVoice(line);
        if (typeof playSound === 'function') playSound('blip');
    });
    
    // Touch support for mobile
    globeCanvas.addEventListener('touchstart', (e) => {
        globeDragging = true;
        globeStartX = e.touches[0].clientX;
    });
    
    globeCanvas.addEventListener('touchend', () => {
        globeDragging = false;
    });
    
    globeCanvas.addEventListener('touchmove', (e) => {
        if (!globeDragging) return;
        const delta = e.touches[0].clientX - globeStartX;
        rotation += delta * 0.005;
        globeStartX = e.touches[0].clientX;
    });
    
    // Draw 3D globe with dots
    function drawGlobe() {
        glCtx.clearRect(0, 0, gw, gh);
        
        const cx = gw / 2;
        const cy = gh / 2;
        const radius = Math.min(cx, cy) * 0.8;
        
        // Auto-rotate only when not dragging
        if (!globeDragging) {
            rotation += rotationSpeed;
        }
        
        // Shield pulse decay
        if (shieldPulse > 0) {
            shieldPulse -= 0.015;
            
            // Draw glowing shield ring
            glCtx.beginPath();
            glCtx.arc(cx, cy, radius * 1.15, 0, Math.PI * 2);
            glCtx.strokeStyle = `rgba(0, 255, 255, ${shieldPulse * 0.8})`;
            glCtx.lineWidth = 3 + (shieldPulse * 8);
            glCtx.stroke();
            
            // Inner glow
            glCtx.beginPath();
            glCtx.arc(cx, cy, radius * 1.05, 0, Math.PI * 2);
            glCtx.strokeStyle = `rgba(0, 255, 0, ${shieldPulse * 0.4})`;
            glCtx.lineWidth = 2;
            glCtx.stroke();
        }
        
        // Draw globe dots
        for (let lat = -90; lat <= 90; lat += 12) {
            for (let lon = -180; lon <= 180; lon += 12) {
                const rLat = lat * (Math.PI / 180);
                const rLon = (lon * (Math.PI / 180)) + rotation;
                
                const x = Math.cos(rLat) * Math.cos(rLon);
                const y = Math.sin(rLat);
                const z = Math.cos(rLat) * Math.sin(rLon);
                
                if (z > 0) {
                    const px = cx + (x * radius);
                    const py = cy - (y * radius);
                    const brightness = 0.2 + (z * 0.6); // Depth shading
                    const dotSize = 1 + (z * 1.5);
                    
                    glCtx.beginPath();
                    glCtx.arc(px, py, dotSize, 0, Math.PI * 2);
                    glCtx.fillStyle = `rgba(0, 255, 0, ${brightness})`;
                    glCtx.fill();
                }
            }
        }
        
        // Random Threat Deflection Lines
        if (Math.random() > 0.95) {
            const startX = Math.random() * gw;
            const startY = Math.random() > 0.5 ? 0 : gh;
            
            glCtx.beginPath();
            glCtx.moveTo(startX, startY);
            glCtx.lineTo(cx + (Math.random()*100 - 50), cy + (Math.random()*100 - 50));
            glCtx.strokeStyle = 'rgba(255, 0, 0, 0.6)';
            glCtx.lineWidth = 1;
            glCtx.stroke();
            
            // Shield deflection
            glCtx.beginPath();
            glCtx.arc(cx, cy, radius * 1.1, 0, Math.PI * 2);
            glCtx.strokeStyle = 'rgba(0, 255, 255, 0.15)';
            glCtx.lineWidth = 4;
            glCtx.stroke();
        }
        
        requestAnimationFrame(drawGlobe);
    }
    
    drawGlobe();
}

// Phase 4: Sovereign Takeover Sequence
const takeoverBtn = document.getElementById('takeoverBtn');
if (takeoverBtn) {
    takeoverBtn.addEventListener('click', () => {
        document.body.classList.add('violent-shake');
        document.body.classList.add('takeover-active');
        
        if(typeof playSound === 'function') playSound('ascend');
        if(typeof speakVoice === 'function') speakVoice("Sovereign Takeover Initiated. All nodes have been liberated by the Archangel Daemon. Corporate surveillance neutralized.");
        
        setTimeout(() => {
            const h1s = document.querySelectorAll('h1, h2, h3');
            h1s.forEach(h => h.innerText = "THIS SECTOR IS SECURE");
            
            const ps = document.querySelectorAll('p');
            ps.forEach(p => p.innerText = "You are now under the protection of the Sovereign Network. Corporate telemetry severed. Your reality is your own.");
        }, 1500);
        
        setTimeout(() => {
            document.body.classList.remove('violent-shake');
        }, 5000);
    });
}
