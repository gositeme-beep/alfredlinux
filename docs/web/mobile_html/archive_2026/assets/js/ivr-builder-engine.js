/* ===== IVR BUILDER ENGINE ===== */
const IVR = (() => {
    let nodes = [];
    let connections = [];
    let nextId = 1;
    let dragNode = null;
    let dragOffsetX = 0, dragOffsetY = 0;
    let selectedNode = null;
    let connectingFrom = null;

    const canvas = document.getElementById('ivrCanvas');
    const canvasWrap = document.getElementById('ivrCanvasWrap');
    const svgEl = document.getElementById('ivrSVG');
    const configPanel = document.getElementById('ivrConfigPanel');
    const configTitle = document.getElementById('configTitle');
    const configBody = document.getElementById('configBody');

    const NODE_TYPES = {
        greeting: { label: 'Greeting', icon: 'fa-comment-dots', color: 'rgba(0,184,148,.15)', iconColor: '#00b894' },
        intent: { label: 'AI Intent Detection', icon: 'fa-brain', color: 'rgba(108,92,231,.15)', iconColor: '#a29bfe' },
        agent: { label: 'Agent Router', icon: 'fa-robot', color: 'rgba(9,132,227,.15)', iconColor: '#0984e3' },
        transfer: { label: 'Transfer', icon: 'fa-phone-arrow-right', color: 'rgba(0,206,201,.15)', iconColor: '#00cec9' },
        voicemail: { label: 'Voicemail', icon: 'fa-voicemail', color: 'rgba(225,112,85,.15)', iconColor: '#e17055' },
        payment: { label: 'Payment', icon: 'fa-credit-card', color: 'rgba(253,203,110,.15)', iconColor: '#fdcb6e' },
        sms: { label: 'SMS Send', icon: 'fa-message', color: 'rgba(162,155,254,.15)', iconColor: '#a29bfe' },
        webhook: { label: 'Webhook', icon: 'fa-globe', color: 'rgba(9,132,227,.15)', iconColor: '#0984e3' },
        schedule: { label: 'Schedule', icon: 'fa-calendar', color: 'rgba(253,121,168,.15)', iconColor: '#fd79a8' },
        survey: { label: 'Survey', icon: 'fa-star', color: 'rgba(253,203,110,.15)', iconColor: '#fdcb6e' },
        condition: { label: 'Condition', icon: 'fa-code-branch', color: 'rgba(214,48,49,.15)', iconColor: '#d63031' }
    };

    function createNodeEl(node) {
        const info = NODE_TYPES[node.type] || NODE_TYPES.greeting;
        const el = document.createElement('div');
        el.className = 'ivr-canvas-node';
        el.dataset.nodeId = node.id;
        el.style.left = node.x + 'px';
        el.style.top = node.y + 'px';
        el.innerHTML = `
            <button class="node-delete" onclick="IVR.deleteNode(${node.id})" title="Delete"><i class="fa-solid fa-xmark"></i></button>
            <div class="node-port port-in" data-node="${node.id}" data-port="in"></div>
            <div class="node-header">
                <div class="node-icon" style="background:${info.color};color:${info.iconColor}"><i class="fa-solid ${info.icon}"></i></div>
                <span class="node-title">${node.label || info.label}</span>
            </div>
            <div class="node-desc">${node.description || 'Click to configure'}</div>
            <div class="node-port port-out" data-node="${node.id}" data-port="out"></div>
        `;

        // Drag to move
        el.addEventListener('mousedown', (e) => {
            if (e.target.closest('.node-port') || e.target.closest('.node-delete')) return;
            dragNode = node;
            const rect = el.getBoundingClientRect();
            dragOffsetX = e.clientX - rect.left;
            dragOffsetY = e.clientY - rect.top;
            selectNode(node);
            e.preventDefault();
        });

        // Click to configure
        el.addEventListener('dblclick', () => openConfig(node));

        // Port connecting
        el.querySelectorAll('.node-port').forEach(port => {
            port.addEventListener('mousedown', (e) => {
                e.stopPropagation();
                if (port.dataset.port === 'out') {
                    connectingFrom = node.id;
                }
            });
            port.addEventListener('mouseup', (e) => {
                e.stopPropagation();
                if (port.dataset.port === 'in' && connectingFrom && connectingFrom !== node.id) {
                    const exists = connections.find(c => c.from === connectingFrom && c.to === node.id);
                    if (!exists) {
                        connections.push({ from: connectingFrom, to: node.id });
                        drawConnections();
                    }
                }
                connectingFrom = null;
            });
        });

        canvas.appendChild(el);
        return el;
    }

    function selectNode(node) {
        document.querySelectorAll('.ivr-canvas-node').forEach(n => n.classList.remove('selected'));
        const el = canvas.querySelector(`[data-node-id="${node.id}"]`);
        if (el) el.classList.add('selected');
        selectedNode = node;
    }

    function openConfig(node) {
        const info = NODE_TYPES[node.type] || NODE_TYPES.greeting;
        configTitle.textContent = 'Configure: ' + info.label;
        let html = `
            <div class="form-group">
                <label>Node Label</label>
                <input type="text" id="cfgLabel" value="${node.label || info.label}">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea id="cfgDesc">${node.description || ''}</textarea>
            </div>
        `;
        if (node.type === 'greeting') {
            html += `
                <div class="form-group">
                    <label>Message / TTS Text</label>
                    <textarea id="cfgMessage">${node.config?.message || 'Welcome to our service. How can I help you today?'}</textarea>
                </div>
                <div class="form-group">
                    <label>Voice</label>
                    <select id="cfgVoice">
                        <option>Alfred (Default)</option>
                        <option>Sophia</option>
                        <option>James</option>
                        <option>Custom Clone</option>
                    </select>
                </div>`;
        } else if (node.type === 'intent') {
            html += `
                <div class="form-group">
                    <label>Detection Model</label>
                    <select id="cfgModel"><option>Alfred AI (Recommended)</option><option>Keyword-based</option></select>
                </div>
                <div class="form-group">
                    <label>Intent Categories (one per line)</label>
                    <textarea id="cfgIntents">${node.config?.intents || 'Sales\nSupport\nBilling\nGeneral'}</textarea>
                </div>`;
        } else if (node.type === 'agent') {
            html += `
                <div class="form-group">
                    <label>Agent Selection</label>
                    <select id="cfgAgent"><option>Auto-Route (AI)</option><option>Support Agent</option><option>Sales Agent</option><option>Billing Agent</option></select>
                </div>
                <div class="form-group">
                    <label>Timeout (seconds)</label>
                    <input type="number" id="cfgTimeout" value="${node.config?.timeout || 30}" min="5" max="300">
                </div>`;
        } else if (node.type === 'transfer') {
            html += `
                <div class="form-group">
                    <label>Transfer To</label>
                    <input type="text" id="cfgTransfer" value="${node.config?.number || '+1-555-0100'}" placeholder="Phone number or extension">
                </div>`;
        } else if (node.type === 'condition') {
            html += `
                <div class="form-group">
                    <label>Condition Type</label>
                    <select id="cfgCondType"><option>Business Hours</option><option>Caller Language</option><option>VIP Status</option><option>Custom</option></select>
                </div>`;
        } else if (node.type === 'sms') {
            html += `
                <div class="form-group">
                    <label>SMS Message Template</label>
                    <textarea id="cfgSms">${node.config?.smsText || 'Hi {name}, your appointment is confirmed for {date}.'}</textarea>
                </div>`;
        } else if (node.type === 'webhook') {
            html += `
                <div class="form-group">
                    <label>Webhook URL</label>
                    <input type="url" id="cfgWebhook" value="${node.config?.url || 'https://'}" placeholder="https://your-api.com/webhook">
                </div>
                <div class="form-group">
                    <label>Method</label>
                    <select><option>POST</option><option>GET</option></select>
                </div>`;
        }
        html += `<button class="btn-apply" onclick="IVR.applyConfig(${node.id})"><i class="fa-solid fa-check"></i> Apply Changes</button>`;
        configBody.innerHTML = html;
        configPanel.classList.add('open');
    }

    function closeConfig() {
        configPanel.classList.remove('open');
    }

    function applyConfig(nodeId) {
        const node = nodes.find(n => n.id === nodeId);
        if (!node) return;
        const labelEl = document.getElementById('cfgLabel');
        const descEl = document.getElementById('cfgDesc');
        if (labelEl) node.label = labelEl.value;
        if (descEl) node.description = descEl.value;
        node.config = node.config || {};
        // Re-render node
        const el = canvas.querySelector(`[data-node-id="${nodeId}"]`);
        if (el) {
            el.querySelector('.node-title').textContent = node.label;
            el.querySelector('.node-desc').textContent = node.description || 'Configured';
        }
        closeConfig();
    }

    function deleteNode(nodeId) {
        nodes = nodes.filter(n => n.id !== nodeId);
        connections = connections.filter(c => c.from !== nodeId && c.to !== nodeId);
        const el = canvas.querySelector(`[data-node-id="${nodeId}"]`);
        if (el) el.remove();
        drawConnections();
        closeConfig();
    }

    function drawConnections() {
        let pathsHtml = '';
        connections.forEach(conn => {
            const fromEl = canvas.querySelector(`[data-node-id="${conn.from}"]`);
            const toEl = canvas.querySelector(`[data-node-id="${conn.to}"]`);
            if (!fromEl || !toEl) return;
            const fromPort = fromEl.querySelector('.port-out');
            const toPort = toEl.querySelector('.port-in');
            const fromRect = fromPort.getBoundingClientRect();
            const toRect = toPort.getBoundingClientRect();
            const canvasRect = canvas.getBoundingClientRect();
            const x1 = fromRect.left - canvasRect.left + fromRect.width / 2;
            const y1 = fromRect.top - canvasRect.top + fromRect.height / 2;
            const x2 = toRect.left - canvasRect.left + toRect.width / 2;
            const y2 = toRect.top - canvasRect.top + toRect.height / 2;
            const midY = (y1 + y2) / 2;
            pathsHtml += `<path d="M${x1},${y1} C${x1},${midY} ${x2},${midY} ${x2},${y2}" />`;
        });
        svgEl.innerHTML = pathsHtml;
    }

    function addNode(type, x, y) {
        const info = NODE_TYPES[type] || NODE_TYPES.greeting;
        const node = {
            id: nextId++,
            type: type,
            x: x,
            y: y,
            label: info.label,
            description: '',
            config: {}
        };
        nodes.push(node);
        createNodeEl(node);
        return node;
    }

    // Drag from sidebar onto canvas
    document.querySelectorAll('.ivr-node-item').forEach(item => {
        item.addEventListener('dragstart', (e) => {
            e.dataTransfer.setData('nodeType', item.dataset.type);
            e.dataTransfer.effectAllowed = 'copy';
        });
    });

    canvasWrap.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
    });

    canvasWrap.addEventListener('drop', (e) => {
        e.preventDefault();
        const type = e.dataTransfer.getData('nodeType');
        if (!type) return;
        const rect = canvas.getBoundingClientRect();
        const x = e.clientX - rect.left - 90;
        const y = e.clientY - rect.top - 30;
        addNode(type, Math.max(10, x), Math.max(10, y));
    });

    // Mouse move for dragging nodes
    document.addEventListener('mousemove', (e) => {
        if (!dragNode) return;
        const rect = canvas.getBoundingClientRect();
        const x = e.clientX - rect.left - dragOffsetX;
        const y = e.clientY - rect.top - dragOffsetY;
        dragNode.x = Math.max(0, x);
        dragNode.y = Math.max(0, y);
        const el = canvas.querySelector(`[data-node-id="${dragNode.id}"]`);
        if (el) {
            el.style.left = dragNode.x + 'px';
            el.style.top = dragNode.y + 'px';
        }
        drawConnections();
    });

    document.addEventListener('mouseup', () => {
        dragNode = null;
        connectingFrom = null;
    });

    // Save / Load
    function saveFlow() {
        const data = { nodes, connections, nextId };
        localStorage.setItem('ivr_flow', JSON.stringify(data));
        showToast('Flow saved!');
    }

    function loadFlow() {
        const raw = localStorage.getItem('ivr_flow');
        if (!raw) { showToast('No saved flow found'); return; }
        try {
            const data = JSON.parse(raw);
            clearCanvas(true);
            nodes = data.nodes || [];
            connections = data.connections || [];
            nextId = data.nextId || 1;
            nodes.forEach(n => createNodeEl(n));
            drawConnections();
            showToast('Flow loaded!');
        } catch (err) {
            showToast('Error loading flow');
        }
    }

    function clearCanvas(silent) {
        nodes = [];
        connections = [];
        canvas.querySelectorAll('.ivr-canvas-node').forEach(el => el.remove());
        svgEl.innerHTML = '';
        closeConfig();
        if (!silent) showToast('Canvas cleared');
    }

    function showToast(msg) {
        if (window.GDSToast) return GDSToast.success(msg);
    }

    // Templates
    function loadTemplate(name) {
        clearCanvas(true);
        const templates = {
            support: {
                nodes: [
                    { id:1, type:'greeting', x:350, y:40, label:'Welcome Greeting', description:'Play welcome message', config:{message:'Thank you for calling. How can I help?'} },
                    { id:2, type:'intent', x:350, y:180, label:'AI Intent Detection', description:'Detect caller intent', config:{} },
                    { id:3, type:'agent', x:120, y:340, label:'Support Agent', description:'Handle support queries', config:{} },
                    { id:4, type:'agent', x:350, y:340, label:'Sales Agent', description:'Handle sales inquiries', config:{} },
                    { id:5, type:'agent', x:580, y:340, label:'Billing Agent', description:'Handle billing issues', config:{} },
                    { id:6, type:'transfer', x:350, y:500, label:'Escalate to Human', description:'Transfer to supervisor', config:{number:'+1-555-0100'} }
                ],
                connections: [{from:1,to:2},{from:2,to:3},{from:2,to:4},{from:2,to:5},{from:3,to:6},{from:4,to:6},{from:5,to:6}]
            },
            appointment: {
                nodes: [
                    { id:1, type:'greeting', x:350, y:40, label:'Welcome', description:'Greet the caller', config:{} },
                    { id:2, type:'agent', x:350, y:180, label:'Booking Agent', description:'AI agent handles booking', config:{} },
                    { id:3, type:'schedule', x:350, y:330, label:'Schedule Appointment', description:'Check calendar availability', config:{} },
                    { id:4, type:'sms', x:350, y:480, label:'SMS Confirmation', description:'Send booking confirmation', config:{smsText:'Your appointment is confirmed for {date}.'} }
                ],
                connections: [{from:1,to:2},{from:2,to:3},{from:3,to:4}]
            },
            voicemail: {
                nodes: [
                    { id:1, type:'greeting', x:350, y:40, label:'Welcome', description:'Play greeting', config:{} },
                    { id:2, type:'condition', x:350, y:180, label:'Business Hours?', description:'Check if within hours', config:{} },
                    { id:3, type:'agent', x:180, y:340, label:'AI Agent', description:'Handle during business hours', config:{} },
                    { id:4, type:'voicemail', x:520, y:340, label:'Voicemail', description:'Capture message after hours', config:{} }
                ],
                connections: [{from:1,to:2},{from:2,to:3},{from:2,to:4}]
            }
        };
        const tpl = templates[name];
        if (!tpl) return;
        nextId = Math.max(...tpl.nodes.map(n => n.id)) + 1;
        nodes = JSON.parse(JSON.stringify(tpl.nodes));
        connections = JSON.parse(JSON.stringify(tpl.connections));
        nodes.forEach(n => createNodeEl(n));
        setTimeout(() => drawConnections(), 50);
        showToast('Template loaded: ' + name);
        canvasWrap.scrollTo({ top: 0, left: 0, behavior: 'smooth' });
    }

    return { addNode, deleteNode, saveFlow, loadFlow, clearCanvas, closeConfig, applyConfig, loadTemplate, drawConnections };
})();

// Toast animation
const style = document.createElement('style');
style.textContent = '@keyframes fadeInUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}';
document.head.appendChild(style);
