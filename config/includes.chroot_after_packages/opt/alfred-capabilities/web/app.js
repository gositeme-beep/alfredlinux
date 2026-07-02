const MODELS = {
    chat: [
        { id: "phi3:mini", name: "Phi-3 Mini", desc: "Microsoft's highly capable 3.8B parameter model. Extremely fast and uncensored.", type: "ollama", size: "2.4 GB", gated: false, dls: "12M" },
        { id: "llama3:8b", name: "Llama 3 (8B)", desc: "Meta's flagship open-weights model. Incredible reasoning capabilities.", type: "ollama", size: "4.7 GB", gated: true, dls: "45M" },
        { id: "dolphin-mixtral", name: "Dolphin Mixtral", desc: "Uncensored MoE model for complex, unrestricted reasoning tasks.", type: "ollama", size: "26.0 GB", gated: false, dls: "2M" }
    ],
    code: [
        { id: "deepseek-coder:33b", name: "DeepSeek Coder (33B)", desc: "State-of-the-art coding assistant trained on trillions of tokens of code.", type: "ollama", size: "19.0 GB", gated: false, dls: "8M" },
        { id: "starcoder2", name: "StarCoder 2", desc: "Excellent code completion model built by BigCode.", type: "ollama", size: "8.0 GB", gated: false, dls: "3M" }
    ],
    vision: [
        { id: "llava:13b", name: "LLaVA 13B", desc: "Vision-language model. Upload images and ask questions about them.", type: "ollama", size: "8.5 GB", gated: false, dls: "5M" },
        { id: "flux-schnell", name: "FLUX.1 Schnell", desc: "Black Forest Labs' ultra-fast 4-step image generator for ComfyUI.", type: "comfyui", size: "24.0 GB", gated: true, dls: "20M" }
    ],
    audio: [
        { id: "whisper-large-v3", name: "Whisper V3", desc: "OpenAI's state-of-the-art speech recognition model.", type: "comfyui", size: "3.1 GB", gated: false, dls: "15M" },
        { id: "xtts-v2", name: "XTTS v2", desc: "Voice cloning and text-to-speech in multiple languages.", type: "comfyui", size: "1.8 GB", gated: false, dls: "4M" }
    ]
};

const TITLES = {
    chat: { title: "Chat & Reasoning", sub: "Core conversational AI models for general tasks." },
    code: { title: "Coding Assistants", sub: "Specialized models for programming and logic." },
    vision: { title: "Vision & Images", sub: "Multimodal and image generation capabilities." },
    audio: { title: "Audio & Voice", sub: "Speech recognition and synthesis capabilities." },
    settings: { title: "Settings & Import", sub: "Manage your API keys and offline models." }
};

let apiKey = localStorage.getItem('hf_api_key') || "";

document.addEventListener("DOMContentLoaded", () => {
    const tabs = document.querySelectorAll('.nav-links li');
    const container = document.getElementById('view-container');

    function renderView(tabId) {
        if(tabId === 'settings') {
            renderSettings(container);
            return;
        }

        const data = TITLES[tabId];
        const models = MODELS[tabId];

        let html = `
            <div class="view-header">
                <h1 class="view-title">${data.title}</h1>
                <p class="view-subtitle">${data.sub}</p>
            </div>
            <div class="models-grid">
        `;

        models.forEach(m => {
            const isGated = m.gated;
            const btnClass = isGated ? (apiKey ? 'install-btn' : 'install-btn gated') : 'install-btn';
            const btnText = isGated && !apiKey ? '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg> API Key Required' : 
                            '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg> Install Capability';
            
            html += `
                <div class="model-card" id="card-${m.id.replace(':','-')}">
                    <div class="card-header">
                        <div class="card-title">${m.name}</div>
                        <div class="card-badge">${m.type.toUpperCase()}</div>
                    </div>
                    <div class="card-desc">${m.desc}</div>
                    <div class="card-meta">
                        <span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path></svg> ${m.size}</span>
                        <span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg> ${m.dls}</span>
                    </div>
                    <div class="progress-container" id="prog-cont-${m.id.replace(':','-')}">
                        <div class="progress-bar" id="prog-bar-${m.id.replace(':','-')}"></div>
                    </div>
                    <button class="${btnClass}" onclick="installModel('${m.id}', ${isGated})" id="btn-${m.id.replace(':','-')}">${btnText}</button>
                </div>
            `;
        });

        html += `</div>`;
        container.innerHTML = html;
    }

    function renderSettings(container) {
        const data = TITLES.settings;
        container.innerHTML = `
            <div class="view-header">
                <h1 class="view-title">${data.title}</h1>
                <p class="view-subtitle">${data.sub}</p>
            </div>
            
            <div class="settings-panel">
                <div class="form-group">
                    <label>HuggingFace API Key (For Gated Models)</label>
                    <input type="password" class="form-control" id="hf-key-input" placeholder="hf_..." value="${apiKey}">
                    <p style="color: #6b7280; font-size: 0.85rem; margin-top: 8px;">
                        Required to download models like Llama 3 or FLUX.1 directly into the OS. 
                        <a href="#" onclick="openExternalLink('https://huggingface.co/settings/tokens')" style="color: #3b82f6; text-decoration: none;">Get your free API key here &rarr;</a>
                    </p>
                </div>
                <button class="install-btn" onclick="saveKey()" style="width: auto;">Save API Key</button>

                <div class="divider"></div>

                <div class="form-group">
                    <label>Offline Manual Import</label>
                    <p style="color: #6b7280; font-size: 0.85rem; margin-bottom: 12px;">Have a .gguf or .safetensors file on a USB drive? Import it directly into the Alfred Vault.</p>
                    <button class="install-btn" onclick="manualImport()" style="width: auto; background: rgba(255,255,255,0.1);"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg> Select Offline Model</button>
                </div>
            </div>
        `;
    }

    window.saveKey = function() {
        const key = document.getElementById('hf-key-input').value;
        localStorage.setItem('hf_api_key', key);
        apiKey = key;
        alert('API Key Saved Successfully!');
    }

    window.manualImport = function() {
        // In the real Python backend, this triggers QFileDialog or similar via REST
        fetch('http://127.0.0.1:5999/api/import', { method: 'POST' })
            .then(res => res.json())
            .then(data => alert(data.message || 'Import triggered'))
            .catch(err => alert('Backend not connected (running in UI-only prototype mode)'));
    }

    window.installModel = function(id, isGated) {
        if (isGated && !apiKey) {
            alert('This model is gated. Please enter your HuggingFace API Key in the Settings tab first.');
            // Auto switch to settings
            document.querySelector('[data-tab="settings"]').click();
            return;
        }

        const cleanId = id.replace(':', '-');
        const btn = document.getElementById(`btn-${cleanId}`);
        const progCont = document.getElementById(`prog-cont-${cleanId}`);
        const progBar = document.getElementById(`prog-bar-${cleanId}`);

        btn.style.display = 'none';
        progCont.style.display = 'block';

        // Simulate backend download via fetch
        fetch(`http://127.0.0.1:5999/api/install?model=${id}`, { method: 'POST' })
            .catch(e => console.log("Backend not active, simulating"));

        // Simulate Progress
        let p = 0;
        const interval = setInterval(() => {
            p += Math.random() * 5;
            if (p >= 100) p = 100;
            progBar.style.width = p + '%';
            
            if (p === 100) {
                clearInterval(interval);
                setTimeout(() => {
                    progCont.style.display = 'none';
                    btn.style.display = 'flex';
                    btn.className = 'install-btn installed';
                    btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg> Installed';
                    btn.onclick = null;
                }, 500);
            }
        }, 200);
    }

    window.openExternalLink = function(url) {
        // Send to Python backend to open in default native browser (Firefox/Chromium)
        fetch(\`http://127.0.0.1:5999/api/open?url=\${encodeURIComponent(url)}\`, { method: 'POST' })
            .catch(e => window.open(url, '_blank'));
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            renderView(tab.dataset.tab);
        });
    });

    // Init
    renderView('chat');
});
