/**
 * Team Chat Engine v2.0
 * AI Agent War Room — real-time team management with WebSocket
 * GoSiteMe.com
 */
(function () {
    'use strict';

    /* ═══════════════════════════════════════
       CONSTANTS & STATE
       ═══════════════════════════════════════ */
    const API = '/api/team-chat.php';

    const AGENT_PERSONAS = {
        alfred:   { name:'Alfred',   avatar:'🎩', role:'Team Lead',             color:'#6c5ce7' },
        nova:     { name:'Nova',     avatar:'⭐', role:'Customer Support Lead', color:'#00e676' },
        sage:     { name:'Sage',     avatar:'📚', role:'Knowledge Expert',      color:'#448aff' },
        atlas:    { name:'Atlas',    avatar:'📊', role:'Data Analyst',          color:'#ff9100' },
        cipher:   { name:'Cipher',   avatar:'🔐', role:'Security Specialist',   color:'#ff5252' },
        pulse:    { name:'Pulse',    avatar:'💓', role:'Real-Time Monitor',     color:'#e91e63' },
        pierre:   { name:'Pierre',   avatar:'🇫🇷', role:'Bilingual (FR/EN)',     color:'#2196f3' },
        sofia:    { name:'Sofia',    avatar:'💎', role:'Sales Specialist',      color:'#9c27b0' },
        maven:    { name:'Maven',    avatar:'🔧', role:'Technical Support',     color:'#795548' },
        herald:   { name:'Herald',   avatar:'📢', role:'Outbound Caller',      color:'#ff6f00' },
        scout:    { name:'Scout',    avatar:'🔍', role:'Lead Qualifier',       color:'#18ffff' },
        curator:  { name:'Curator',  avatar:'✅', role:'Quality Assurance',    color:'#4caf50' },
        vanguard: { name:'Vanguard', avatar:'🛡️', role:'Escalation Handler',  color:'#607d8b' },
        nexus:    { name:'Nexus',    avatar:'🔗', role:'Integration Spec.',    color:'#00bcd4' },
        oracle:   { name:'Oracle',   avatar:'🔮', role:'Predictive Analytics', color:'#ce93d8' },
        ember:    { name:'Ember',    avatar:'🔥', role:'Trainer & Coach',      color:'#ff7043' },
        aurora:   { name:'Aurora',   avatar:'🌅', role:'Scheduling',           color:'#ffab40' },
        zephyr:   { name:'Zephyr',   avatar:'⚡', role:'Speed Agent',          color:'#ffd600' },
        flux:     { name:'Flux',     avatar:'⚙️', role:'Workflow Automator',   color:'#90a4ae' },
        prism:    { name:'Prism',    avatar:'🌈', role:'Sentiment Analyst',    color:'#e040fb' },
        echo:     { name:'Echo',     avatar:'🔔', role:'Follow-Up Specialist', color:'#26c6da' }
    };

    let csrfToken = '';
    let userName = 'Boss';

    let currentRoom = null;
    let currentAgents = [];
    let targetAgents = [];
    let sendMode = 'chat';
    let isSending = false;
    let activeRoleplay = null;
    let agentPerformance = {};
    let pickedAgents = [];
    let allMessages = [];
    let pinnedMessages = [];
    let unreadCounts = {};
    let ws = null;
    let wsReconnectTimer = null;
    let wsReconnectAttempts = 0;
    const WS_MAX_RECONNECT = 10;
    let searchOpen = false;

    /* ═══════════════════════════════════════
       DOM HELPERS
       ═══════════════════════════════════════ */
    const $ = (id) => document.getElementById(id);
    const esc = (text) => { const d = document.createElement('div'); d.textContent = text; return d.innerHTML; };

    function formatMsg(text) {
        let html = esc(text);
        html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        html = html.replace(/`(.+?)`/g, '<code style="background:rgba(108,92,231,.15);padding:.1rem .3rem;border-radius:4px;font-family:var(--mono);font-size:.82em">$1</code>');
        html = html.replace(/\n/g, '<br>');
        return html;
    }

    function showModal(id) { $(id).classList.add('show'); }
    function hideModal(id) { $(id).classList.remove('show'); }
    function toggleSidebar() { $('tcLayout').classList.toggle('show-sidebar'); }
    function toggleRoster() { $('tcLayout').classList.toggle('show-roster'); }

    function scrollToBottom() {
        const c = $('messagesContainer');
        if (c) requestAnimationFrame(() => c.scrollTop = c.scrollHeight);
    }

    function toast(msg, type) {
        if (window.GDSToast) return GDSToast.show(msg, { type: (type || 'info') === 'error' ? 'danger' : (type || 'info') });
    }

    /* ═══════════════════════════════════════
       API
       ═══════════════════════════════════════ */
    async function apiCall(action, data, method) {
        method = method || 'POST';
        const url = method === 'GET'
            ? API + '?action=' + encodeURIComponent(action) + '&' + new URLSearchParams(data)
            : API + '?action=' + encodeURIComponent(action);

        const opts = { method, headers: { 'X-CSRF-Token': csrfToken } };
        if (method === 'POST') {
            opts.headers['Content-Type'] = 'application/json';
            opts.body = JSON.stringify(data);
        }

        const res = await fetch(url, opts);
        if (!res.ok && res.status >= 500) {
            throw new Error('Server error (' + res.status + '). The request timed out or failed.');
        }
        const text = await res.text();
        let json;
        try { json = JSON.parse(text); } catch (e) {
            throw new Error('Invalid response from server. Please try again.');
        }
        if (json.csrf_token) csrfToken = json.csrf_token;
        return json;
    }

    /* ═══════════════════════════════════════
       WEBSOCKET (v2.0)
       ═══════════════════════════════════════ */
    function connectWS(userId) {
        if (ws && ws.readyState <= 1) return;
        const badge = $('wsBadge');
        try {
            ws = new WebSocket('wss://gositeme.com:3010');
            ws.onopen = function () {
                wsReconnectAttempts = 0;
                ws.send(JSON.stringify({ type: 'auth', channel: 'team-chat', userId: userId }));
                if (badge) { badge.textContent = 'LIVE'; badge.className = 'ws-badge ws-connected'; }
            };
            ws.onmessage = function (evt) {
                try {
                    const data = JSON.parse(evt.data);
                    handleWSMessage(data);
                } catch (e) { /* ignore malformed */ }
            };
            ws.onclose = function () {
                if (badge) { badge.textContent = 'OFFLINE'; badge.className = 'ws-badge ws-disconnected'; }
                scheduleReconnect(userId);
            };
            ws.onerror = function () {
                if (badge) { badge.textContent = 'ERROR'; badge.className = 'ws-badge ws-disconnected'; }
            };
        } catch (e) {
            if (badge) { badge.textContent = 'N/A'; badge.className = 'ws-badge ws-disconnected'; }
        }
    }

    function scheduleReconnect(userId) {
        if (wsReconnectAttempts >= WS_MAX_RECONNECT) return;
        wsReconnectAttempts++;
        clearTimeout(wsReconnectTimer);
        wsReconnectTimer = setTimeout(function () { connectWS(userId); }, Math.min(2000 * wsReconnectAttempts, 30000));
    }

    function handleWSMessage(data) {
        switch (data.type) {
            case 'message':
                if (data.room_id && currentRoom && data.room_id === currentRoom.id) {
                    appendMessage(data.message);
                    scrollToBottom();
                    playNotifSound();
                } else if (data.room_id) {
                    unreadCounts[data.room_id] = (unreadCounts[data.room_id] || 0) + 1;
                    renderRoomList();
                }
                break;
            case 'typing':
                if (data.room_id === (currentRoom && currentRoom.id)) {
                    showRemoteTyping(data.agent_id, data.agent_name);
                }
                break;
            case 'presence':
                updateAgentPresence(data.agent_id, data.status);
                break;
            case 'room_update':
                loadRooms();
                break;
        }
    }

    function showRemoteTyping(agentId, agentName) {
        const indicator = $('typingIndicator');
        if (!indicator) return;
        indicator.style.display = 'flex';
        $('typingText').textContent = (agentName || agentId) + ' is typing';
        clearTimeout(indicator._hideTimer);
        indicator._hideTimer = setTimeout(function () { indicator.style.display = 'none'; }, 4000);
    }

    function updateAgentPresence(agentId, status) {
        const card = document.querySelector('.agent-card[data-agent-id="' + CSS.escape(agentId) + '"]');
        if (!card) return;
        const dot = card.querySelector('.agent-status');
        if (dot) {
            dot.className = 'agent-status' + (status === 'typing' ? ' thinking' : '');
            dot.style.background = status === 'online' ? 'var(--green)' : status === 'typing' ? 'var(--yellow)' : 'var(--text3)';
        }
    }

    function wsSend(payload) {
        if (ws && ws.readyState === 1) ws.send(JSON.stringify(payload));
    }

    let notifAudio = null;
    function playNotifSound() {
        try {
            if (!notifAudio) {
                notifAudio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACAgICAgICAgICAfn1+fn+Bg4WIi42Njo2LiIWCf31+fn+Bg4WIio2Oj46Ni4eFgn9+f3+BhIaKjI+QkI+NioeFgn9+');
                notifAudio.volume = 0.3;
            }
            notifAudio.currentTime = 0;
            notifAudio.play().catch(function () {});
        } catch (e) { /* ignore audio errors */ }
    }

    /* ═══════════════════════════════════════
       ROOM MANAGEMENT
       ═══════════════════════════════════════ */
    async function loadRooms() {
        try {
            const res = await apiCall('rooms', {}, 'GET');
            renderRoomList(res.rooms || []);
        } catch (e) {
            console.error('Failed to load rooms:', e);
        }
    }

    function renderRoomList(rooms) {
        const list = $('roomList');
        if (!list) return;
        // Cache rooms for re-render
        if (rooms) list._rooms = rooms;
        else rooms = list._rooms || [];

        list.innerHTML = '';

        if (!rooms.length) {
            list.innerHTML = '<div style="padding:1rem;text-align:center;color:var(--text3);font-size:.82rem">No rooms yet.<br>Create your first team!</div>';
            return;
        }

        rooms.forEach(function (room) {
            const el = document.createElement('div');
            el.className = 'room-item' + (currentRoom && currentRoom.id === room.room_id ? ' active' : '');
            el.dataset.roomId = room.room_id;
            el.onclick = function () { openRoom(room.room_id); };

            const unread = unreadCounts[room.room_id] || 0;
            const unreadBadge = unread > 0 ? '<span class="unread-badge">' + unread + '</span>' : '';

            el.innerHTML =
                '<div class="room-name">' + esc(room.name) + unreadBadge + '</div>' +
                '<div class="room-meta">' +
                    '<span><span class="dot ' + (room.status === 'active' ? 'active' : 'closed') + '"></span>' + room.status + '</span>' +
                    '<span>' + room.agent_count + ' agents</span>' +
                    '<span>' + (room.msg_count || 0) + ' msgs</span>' +
                '</div>';
            list.appendChild(el);
        });
    }

    async function openRoom(roomId) {
        const res = await apiCall('room', { id: roomId }, 'GET');
        if (res.error) { toast(res.error, 'error'); return; }

        currentRoom = res.room;
        currentAgents = res.agents || [];
        allMessages = res.messages || [];
        pinnedMessages = [];

        // Clear unread
        unreadCounts[roomId] = 0;

        // Show chat UI
        $('emptyState').style.display = 'none';
        $('chatHeader').style.display = 'flex';
        $('messagesContainer').style.display = 'flex';
        $('inputArea').style.display = currentRoom.status === 'active' ? 'block' : 'none';

        $('roomTitle').textContent = currentRoom.name;
        var badge = $('roomBadge');
        badge.textContent = currentRoom.status === 'active' ? 'LIVE' : 'CLOSED';
        badge.className = 'room-badge ' + (currentRoom.status === 'active' ? 'badge-active' : 'badge-closed');

        renderMessages(allMessages);
        renderRoster();
        renderTargetChips();
        saveLastRoom();
        loadRooms();
        scrollToBottom();

        // Join WS room
        wsSend({ type: 'join_room', room_id: roomId });
    }

    async function createRoom() {
        var name = $('modalRoomName').value.trim() || 'Team Chat';
        var purpose = $('modalPurpose').value;
        var count = parseInt($('modalAgentCount').value);

        hideModal('createModal');

        var res = await apiCall('gather', { count: count, purpose: purpose, name: name });
        if (res.error) { toast(res.error, 'error'); return; }

        currentRoom = { id: res.room_id, name: res.name, purpose: res.purpose, status: 'active' };
        currentAgents = res.agents || [];
        allMessages = [];

        $('emptyState').style.display = 'none';
        $('chatHeader').style.display = 'flex';
        $('messagesContainer').style.display = 'flex';
        $('inputArea').style.display = 'block';
        $('roomTitle').textContent = currentRoom.name;

        var msgs = [];
        msgs.push({ speaker_id: 'system', speaker_type: 'system', message: '🎯 Team assembled: ' + res.agent_count + ' agents ready for ' + res.purpose + '.', created_at: new Date().toISOString() });
        (res.introductions || []).forEach(function (intro) {
            var p = AGENT_PERSONAS[intro.agent_id];
            msgs.push({
                speaker_id: intro.agent_id, speaker_type: 'agent', message: intro.message,
                name: p ? p.name : intro.name, avatar: p ? p.avatar : '🤖',
                color: p ? p.color : '#6c5ce7', role: p ? p.role : '',
                created_at: new Date().toISOString()
            });
        });
        allMessages = msgs;
        renderMessages(msgs);
        renderRoster();
        renderTargetChips();
        loadRooms();
        scrollToBottom();
        wsSend({ type: 'join_room', room_id: currentRoom.id });
    }

    async function quickGather(count, purpose) {
        var purposeNames = { call_center: 'Call Center Team', sales: 'Sales Squad', support: 'Support Team', general: 'Full Squad' };
        var name = purposeNames[purpose] || 'Team Chat';

        var res = await apiCall('gather', { count: count, purpose: purpose, name: name });
        if (res.error) { toast(res.error, 'error'); return; }

        currentRoom = { id: res.room_id, name: name, purpose: purpose, status: 'active' };
        currentAgents = res.agents || [];
        allMessages = [];

        $('emptyState').style.display = 'none';
        $('chatHeader').style.display = 'flex';
        $('messagesContainer').style.display = 'flex';
        $('inputArea').style.display = 'block';
        $('roomTitle').textContent = name;

        var msgs = [];
        msgs.push({ speaker_id: 'system', speaker_type: 'system', message: '🎯 ' + userName + ' has gathered ' + res.agent_count + ' agents. Mission: ' + purpose.replace('_', ' ') + '.', created_at: new Date().toISOString() });
        (res.introductions || []).forEach(function (intro) {
            var p = AGENT_PERSONAS[intro.agent_id];
            msgs.push({
                speaker_id: intro.agent_id, speaker_type: 'agent', message: intro.message,
                name: p ? p.name : intro.name, avatar: p ? p.avatar : '🤖',
                color: p ? p.color : '#6c5ce7', role: p ? p.role : '',
                created_at: new Date().toISOString()
            });
        });
        allMessages = msgs;
        renderMessages(msgs);
        renderRoster();
        renderTargetChips();
        loadRooms();
        scrollToBottom();
        wsSend({ type: 'join_room', room_id: currentRoom.id });
    }

    /* ═══════════════════════════════════════
       SENDING MESSAGES
       ═══════════════════════════════════════ */
    async function sendMessage() {
        if (isSending || !currentRoom) return;
        var input = $('messageInput');
        var text = input.value.trim();
        if (!text) return;

        isSending = true;
        input.value = '';
        input.style.height = 'auto';
        $('sendBtn').disabled = true;
        hideMentionPopup();

        var mentionedAgents = parseMentions(text);
        if (mentionedAgents.length > 0 && targetAgents.length === 0) {
            targetAgents = mentionedAgents;
        }

        var userMsg = {
            speaker_id: 'user', speaker_type: 'user', message: text,
            name: 'You', avatar: '👤', color: '#ffffff',
            created_at: new Date().toISOString()
        };
        appendMessage(userMsg);
        allMessages.push(userMsg);
        scrollToBottom();

        showTyping(true);

        var res;
        try {
            if (sendMode === 'train' && targetAgents.length === 1) {
                res = await apiCall('train', { room_id: currentRoom.id, agent_id: targetAgents[0], instructions: text });
                var p = AGENT_PERSONAS[res.agent_id];
                var agentMsg = {
                    speaker_id: res.agent_id, speaker_type: 'agent', message: res.response,
                    name: p ? p.name : res.name, avatar: p ? p.avatar : '🤖',
                    color: p ? p.color : '#6c5ce7', role: p ? p.role : res.role,
                    created_at: new Date().toISOString()
                };
                appendMessage(agentMsg);
                allMessages.push(agentMsg);
                trackPerformance(res.agent_id, res.response);
            } else if (sendMode === 'roleplay' && activeRoleplay) {
                res = await apiCall('roleplay', {
                    room_id: currentRoom.id, agent_id: activeRoleplay.targetAgent,
                    customer_message: text, scenario: activeRoleplay.scenario,
                    difficulty: activeRoleplay.difficulty
                });
                if (res.response) {
                    var rpP = AGENT_PERSONAS[res.agent_id];
                    var rpMsg = {
                        speaker_id: res.agent_id, speaker_type: 'agent', message: res.response,
                        name: rpP ? rpP.name : res.name, avatar: rpP ? rpP.avatar : '🤖',
                        color: rpP ? rpP.color : '#6c5ce7', role: rpP ? rpP.role : '',
                        created_at: new Date().toISOString()
                    };
                    appendMessage(rpMsg);
                    allMessages.push(rpMsg);
                    trackPerformance(res.agent_id, res.response);
                }
                if (res.evaluation) {
                    var evalMsg = { speaker_id: 'system', speaker_type: 'system', message: '📋 Evaluation: ' + res.evaluation, created_at: new Date().toISOString() };
                    appendMessage(evalMsg);
                    allMessages.push(evalMsg);
                }
            } else if (sendMode === 'directive') {
                res = await apiCall('send', {
                    room_id: currentRoom.id, message: '[DIRECTIVE]: ' + text,
                    target_agents: targetAgents.length ? targetAgents : undefined
                });
                (res.responses || []).forEach(function (r) {
                    var dMsg = {
                        speaker_id: r.agent_id, speaker_type: 'agent', message: r.message,
                        name: r.name, avatar: r.avatar, color: r.color, role: r.role,
                        created_at: r.timestamp || new Date().toISOString()
                    };
                    appendMessage(dMsg);
                    allMessages.push(dMsg);
                    trackPerformance(r.agent_id, r.message);
                });
            } else {
                res = await apiCall('send', {
                    room_id: currentRoom.id, message: text,
                    target_agents: targetAgents.length ? targetAgents : undefined
                });
                (res.responses || []).forEach(function (r) {
                    var cMsg = {
                        speaker_id: r.agent_id, speaker_type: 'agent', message: r.message,
                        name: r.name, avatar: r.avatar, color: r.color, role: r.role,
                        created_at: r.timestamp || new Date().toISOString()
                    };
                    appendMessage(cMsg);
                    allMessages.push(cMsg);
                    trackPerformance(r.agent_id, r.message);
                });
            }
        } catch (err) {
            console.error('Team chat error:', err);
            appendMessage({ speaker_id: 'system', speaker_type: 'system', message: '⚠️ ' + (err.message || 'Failed to get team responses. Try again.'), created_at: new Date().toISOString() });
        }

        if (res && res.error) {
            appendMessage({ speaker_id: 'system', speaker_type: 'system', message: '⚠️ ' + res.error, created_at: new Date().toISOString() });
        }

        if (mentionedAgents.length > 0) targetAgents = [];

        showTyping(false);
        isSending = false;
        $('sendBtn').disabled = false;
        scrollToBottom();
    }

    /* ═══════════════════════════════════════
       RENDERING
       ═══════════════════════════════════════ */
    function renderMessages(messages) {
        var container = $('messagesContainer');
        container.innerHTML = '';

        // Show pinned messages bar if any
        if (pinnedMessages.length > 0) {
            var pinBar = document.createElement('div');
            pinBar.className = 'pinned-bar';
            pinBar.innerHTML = '<i class="fas fa-thumbtack"></i> ' + pinnedMessages.length + ' pinned message' + (pinnedMessages.length > 1 ? 's' : '') + ' <button class="pin-toggle" onclick="window.TeamChat.togglePinnedPanel()">View</button>';
            container.appendChild(pinBar);
        }

        messages.forEach(function (msg) { appendMessage(msg); });
    }

    function appendMessage(msg) {
        var container = $('messagesContainer');
        var div = document.createElement('div');

        var isUser = msg.speaker_type === 'user';
        var isSystem = msg.speaker_type === 'system';
        var msgId = msg.id || ('msg-' + Date.now() + '-' + Math.random().toString(36).slice(2, 6));

        div.className = 'msg' + (isUser ? ' user-msg' : '') + (isSystem ? ' system-msg' : '');
        div.dataset.msgId = msgId;
        if (!msg.id) msg.id = msgId;

        var name = msg.name || msg.speaker_id;
        var avatar = msg.avatar || '🤖';
        var color = msg.color || '#6c5ce7';
        var role = msg.role || '';

        if (msg.speaker_type === 'agent' && AGENT_PERSONAS[msg.speaker_id]) {
            var p = AGENT_PERSONAS[msg.speaker_id];
            if (!msg.name) name = p.name;
            if (!msg.avatar) avatar = p.avatar;
            if (!msg.color) color = p.color;
            if (!msg.role) role = p.role;
        }

        if (isUser) { name = userName || 'You'; avatar = '👤'; color = '#6c5ce7'; role = 'Boss'; }

        var timeStr = msg.created_at ? new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '';

        if (isSystem) {
            div.innerHTML = '<div class="msg-body"><div class="msg-text">' + esc(msg.message) + '</div></div>';
        } else {
            var pinBtn = !isUser ? '<button class="msg-action-btn" onclick="window.TeamChat.togglePin(\'' + msgId + '\')" title="Pin"><i class="fas fa-thumbtack"></i></button>' : '';
            div.innerHTML =
                '<div class="msg-avatar" style="border-color:' + color + '">' + avatar + '</div>' +
                '<div class="msg-body" style="border-color:' + color + '22">' +
                    '<div class="msg-header">' +
                        '<span class="msg-name" style="color:' + color + '">' + esc(name) + '</span>' +
                        (role ? '<span class="msg-role">' + esc(role) + '</span>' : '') +
                        '<span class="msg-time">' + timeStr + '</span>' +
                        '<span class="msg-actions">' + pinBtn + '</span>' +
                    '</div>' +
                    '<div class="msg-text">' + formatMsg(msg.message) + '</div>' +
                '</div>';
        }

        container.appendChild(div);
    }

    function renderRoster() {
        var list = $('rosterList');
        list.innerHTML = '';
        $('rosterCount').textContent = currentAgents.length;

        currentAgents.forEach(function (agent) {
            var div = document.createElement('div');
            div.className = 'agent-card' + (targetAgents.indexOf(agent.id) >= 0 ? ' selected' : '');
            div.dataset.agentId = agent.id;
            div.onclick = function () { toggleAgentTarget(agent.id); };
            div.innerHTML =
                '<div class="agent-avatar" style="border-color:' + agent.color + '">' + agent.avatar + '</div>' +
                '<div class="agent-info">' +
                    '<div class="agent-name">' + esc(agent.name) + '</div>' +
                    '<div class="agent-role">' + esc(agent.role) + '</div>' +
                '</div>' +
                '<div class="agent-status ' + (agent.status === 'thinking' ? 'thinking' : '') + '"></div>' +
                '<button class="remove-btn" onclick="event.stopPropagation();window.TeamChat.removeAgent(\'' + agent.id + '\')" title="Remove"><i class="fas fa-times"></i></button>';
            list.appendChild(div);
        });
    }

    function renderTargetChips() {
        var controls = $('inputControls');
        controls.querySelectorAll('.agent-chip').forEach(function (el) { el.remove(); });

        currentAgents.forEach(function (agent) {
            var chip = document.createElement('button');
            chip.className = 'input-chip agent-chip' + (targetAgents.indexOf(agent.id) >= 0 ? ' active' : '');
            chip.onclick = function () { toggleAgentTarget(agent.id); };
            chip.innerHTML = agent.avatar + ' ' + agent.name;
            chip.dataset.agentId = agent.id;
            controls.appendChild(chip);
        });
    }

    /* ═══════════════════════════════════════
       AGENT MANAGEMENT
       ═══════════════════════════════════════ */
    function toggleAgentTarget(agentId) {
        var idx = targetAgents.indexOf(agentId);
        if (idx >= 0) targetAgents.splice(idx, 1);
        else targetAgents.push(agentId);
        renderRoster();
        renderTargetChips();
        updateBroadcastChip();
    }

    function toggleBroadcast() {
        if (targetAgents.length > 0) targetAgents = [];
        renderRoster();
        renderTargetChips();
        updateBroadcastChip();
    }

    function updateBroadcastChip() {
        var chip = $('broadcastChip');
        if (!chip) return;
        chip.classList.toggle('active', targetAgents.length === 0);
        chip.innerHTML = targetAgents.length === 0
            ? '<i class="fas fa-bullhorn"></i> All Agents'
            : '<i class="fas fa-bullhorn"></i> ' + targetAgents.length + ' selected';
    }

    function setMode(mode) {
        sendMode = sendMode === mode ? 'chat' : mode;
        $('trainChip').classList.toggle('active', sendMode === 'train');
        $('directiveChip').classList.toggle('active', sendMode === 'directive');
        $('roleplayChip').classList.toggle('active', sendMode === 'roleplay');

        var input = $('messageInput');
        if (sendMode === 'train') input.placeholder = 'Train your agent... (select one agent on the right)';
        else if (sendMode === 'directive') input.placeholder = 'Issue a directive to the team...';
        else if (sendMode === 'roleplay') input.placeholder = 'Play the customer role — test your agent...';
        else input.placeholder = 'Message your team... (type @ to mention an agent)';
    }

    async function removeAgent(agentId) {
        if (!currentRoom) return;
        var res = await apiCall('remove_agent', { room_id: currentRoom.id, agent_id: agentId });
        if (res.ok) {
            currentAgents = currentAgents.filter(function (a) { return a.id !== agentId; });
            targetAgents = targetAgents.filter(function (id) { return id !== agentId; });
            renderRoster();
            renderTargetChips();
            var p = AGENT_PERSONAS[agentId];
            appendMessage({ speaker_id: 'system', speaker_type: 'system', message: (p ? p.name : agentId) + ' has been dismissed.', created_at: new Date().toISOString() });
        }
    }

    async function gatherAgents() {
        if (!currentRoom) return;
        hideModal('gatherModal');

        var count = parseInt($('gatherCount').value);
        var purpose = $('gatherPurpose').value;

        var res = await apiCall('gather', {
            count: count, purpose: purpose,
            room_id: currentRoom.id, name: currentRoom.name
        });

        if (res.agents) {
            currentAgents = res.agents;
            renderRoster();
            renderTargetChips();

            (res.introductions || []).forEach(function (intro) {
                var p = AGENT_PERSONAS[intro.agent_id];
                appendMessage({
                    speaker_id: intro.agent_id, speaker_type: 'agent', message: intro.message,
                    name: p ? p.name : intro.name, avatar: p ? p.avatar : '🤖',
                    color: p ? p.color : '#6c5ce7', role: p ? p.role : '',
                    created_at: new Date().toISOString()
                });
            });
            scrollToBottom();
        }
    }

    async function closeRoom() {
        if (!currentRoom || !confirm('Close this room? You can still view the history.')) return;
        await apiCall('close', { room_id: currentRoom.id });
        currentRoom.status = 'closed';
        $('inputArea').style.display = 'none';
        $('roomBadge').textContent = 'CLOSED';
        $('roomBadge').className = 'room-badge badge-closed';
        loadRooms();
        toast('Room closed', 'info');
    }

    /* ═══════════════════════════════════════
       AGENT PICKER MODAL
       ═══════════════════════════════════════ */
    function showAddAgentModal() {
        pickedAgents = [];
        var grid = $('agentPickerGrid');
        grid.innerHTML = '';

        var currentIds = currentAgents.map(function (a) { return a.id; });

        Object.keys(AGENT_PERSONAS).forEach(function (id) {
            if (currentIds.indexOf(id) >= 0) return;
            var p = AGENT_PERSONAS[id];
            var div = document.createElement('div');
            div.className = 'agent-pick';
            div.dataset.agentId = id;
            div.onclick = function () {
                div.classList.toggle('picked');
                if (div.classList.contains('picked')) pickedAgents.push(id);
                else pickedAgents = pickedAgents.filter(function (x) { return x !== id; });
            };
            div.innerHTML = '<span class="pick-avatar">' + p.avatar + '</span><span class="pick-name">' + p.name + '</span>';
            grid.appendChild(div);
        });

        showModal('addAgentModal');
    }

    async function addPickedAgents() {
        hideModal('addAgentModal');
        if (!currentRoom || !pickedAgents.length) return;

        for (var i = 0; i < pickedAgents.length; i++) {
            var agentId = pickedAgents[i];
            var res = await apiCall('add_agent', { room_id: currentRoom.id, agent_id: agentId });
            if (res.ok && res.agent) {
                currentAgents.push(res.agent);
                var p = AGENT_PERSONAS[agentId];
                appendMessage({
                    speaker_id: agentId, speaker_type: 'agent',
                    message: (p ? p.avatar : '🤖') + ' ' + (p ? p.name : agentId) + ' has joined the room. ' + (p ? p.role : '') + ' — ready.',
                    name: p ? p.name : agentId, avatar: p ? p.avatar : '🤖',
                    color: p ? p.color : '#6c5ce7', role: p ? p.role : '',
                    created_at: new Date().toISOString()
                });
            }
        }
        renderRoster();
        renderTargetChips();
        scrollToBottom();
    }

    /* ═══════════════════════════════════════
       TYPING / INPUT
       ═══════════════════════════════════════ */
    function showTyping(show) {
        var el = $('typingIndicator');
        if (!el) return;
        el.style.display = show ? 'flex' : 'none';
        if (show) {
            var count = targetAgents.length || currentAgents.length;
            $('typingText').textContent = count === 1
                ? ((AGENT_PERSONAS[targetAgents[0]] || {}).name || 'Agent') + ' is thinking'
                : count + ' agents are thinking';
        }
    }

    function handleInputKey(e) {
        if (handleMentionKey(e)) return;
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
        e.target.style.height = 'auto';
        e.target.style.height = Math.min(e.target.scrollHeight, 120) + 'px';
    }

    /* ═══════════════════════════════════════
       @MENTION AUTOCOMPLETE
       ═══════════════════════════════════════ */
    function handleInputChange(e) {
        var input = e.target;
        var text = input.value;
        var cursorPos = input.selectionStart;
        var beforeCursor = text.substring(0, cursorPos);
        var mentionMatch = beforeCursor.match(/@(\w*)$/);

        if (mentionMatch) showMentionPopup(mentionMatch[1].toLowerCase());
        else hideMentionPopup();
    }

    function showMentionPopup(query) {
        var popup = $('mentionPopup');
        popup.innerHTML = '';

        var matches = currentAgents.filter(function (a) {
            return a.name.toLowerCase().startsWith(query) ||
                   a.id.toLowerCase().startsWith(query) ||
                   a.role.toLowerCase().indexOf(query) >= 0;
        });

        if (matches.length === 0) { hideMentionPopup(); return; }

        matches.forEach(function (agent, i) {
            var div = document.createElement('div');
            div.className = 'mention-item' + (i === 0 ? ' active' : '');
            div.dataset.agentId = agent.id;
            div.dataset.agentName = agent.name;
            div.innerHTML = '<span class="m-avatar">' + agent.avatar + '</span><span class="m-name">' + agent.name + '</span><span class="m-role">' + agent.role + '</span>';
            div.onclick = function () { insertMention(agent); };
            popup.appendChild(div);
        });

        popup.classList.add('show');
    }

    function hideMentionPopup() { $('mentionPopup').classList.remove('show'); }

    function insertMention(agent) {
        var input = $('messageInput');
        var text = input.value;
        var cursorPos = input.selectionStart;
        var beforeCursor = text.substring(0, cursorPos);
        var afterCursor = text.substring(cursorPos);
        var newBefore = beforeCursor.replace(/@\w*$/, '@' + agent.name + ' ');
        input.value = newBefore + afterCursor;
        input.selectionStart = input.selectionEnd = newBefore.length;
        input.focus();
        hideMentionPopup();
    }

    function parseMentions(text) {
        var mentioned = [];
        var regex = /@(\w+)/g;
        var match;
        while ((match = regex.exec(text)) !== null) {
            var name = match[1].toLowerCase();
            var agent = currentAgents.find(function (a) { return a.name.toLowerCase() === name || a.id === name; });
            if (agent && mentioned.indexOf(agent.id) < 0) mentioned.push(agent.id);
        }
        return mentioned;
    }

    function handleMentionKey(e) {
        var popup = $('mentionPopup');
        if (!popup.classList.contains('show')) return false;

        var items = popup.querySelectorAll('.mention-item');
        var active = popup.querySelector('.mention-item.active');

        if (e.key === 'Tab' || e.key === 'Enter') {
            e.preventDefault();
            if (active) {
                var agent = currentAgents.find(function (a) { return a.id === active.dataset.agentId; });
                if (agent) insertMention(agent);
            }
            return true;
        } else if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            var idx = Array.from(items).indexOf(active);
            items.forEach(function (i) { i.classList.remove('active'); });
            var next = e.key === 'ArrowDown' ? (idx + 1) % items.length : (idx - 1 + items.length) % items.length;
            if (items[next]) items[next].classList.add('active');
            return true;
        } else if (e.key === 'Escape') {
            hideMentionPopup();
            return true;
        }
        return false;
    }

    /* ═══════════════════════════════════════
       ROLE-PLAY SCENARIOS
       ═══════════════════════════════════════ */
    function showRoleplayModal() {
        var sel = $('rpTargetAgent');
        sel.innerHTML = '';
        currentAgents.forEach(function (a) {
            var opt = document.createElement('option');
            opt.value = a.id;
            opt.textContent = a.avatar + ' ' + a.name + ' — ' + a.role;
            sel.appendChild(opt);
        });

        $('rpScenario').onchange = function () {
            $('rpCustomWrap').style.display = this.value === 'custom' ? 'block' : 'none';
        };

        showModal('roleplayModal');
    }

    async function launchRoleplay() {
        var scenario = $('rpScenario').value;
        var customText = $('rpCustomText').value.trim();
        var targetAgent = $('rpTargetAgent').value;
        var difficulty = $('rpDifficulty').value;

        hideModal('roleplayModal');

        activeRoleplay = { scenario: scenario, customText: customText, targetAgent: targetAgent, difficulty: difficulty };
        sendMode = 'roleplay';
        $('roleplayChip').classList.add('active');

        var agentP = AGENT_PERSONAS[targetAgent];
        var scenarioNames = {
            angry_billing: 'Angry billing customer', confused_newbie: 'Confused new customer',
            tech_emergency: 'Tech emergency (site down)', cancellation: 'Cancellation request',
            upsell_opportunity: 'Upsell opportunity', language_barrier: 'Language barrier',
            vip_complaint: 'VIP complaint', fraud_attempt: 'Fraud detection',
            multi_issue: 'Multi-issue customer', custom: customText || 'Custom scenario'
        };

        var banner = document.createElement('div');
        banner.className = 'roleplay-banner';
        banner.id = 'roleplayBanner';
        banner.innerHTML =
            '<i class="fas fa-theater-masks"></i>' +
            '<div class="rp-info">' +
                '<div class="rp-title">🎭 Role-Play Active: ' + scenarioNames[scenario] + '</div>' +
                '<div class="rp-desc">Testing ' + (agentP ? agentP.name : targetAgent) + ' • Difficulty: ' + difficulty + ' • You are the customer</div>' +
            '</div>' +
            '<button class="rp-end" onclick="window.TeamChat.endRoleplay()">End</button>';
        var msgContainer = $('messagesContainer');
        msgContainer.parentNode.insertBefore(banner, msgContainer);

        targetAgents = [targetAgent];
        renderRoster();
        renderTargetChips();

        appendMessage({ speaker_id: 'system', speaker_type: 'system', message: '🎭 ROLE-PLAY STARTED: ' + scenarioNames[scenario] + '. ' + (agentP ? agentP.name : targetAgent) + ' is the agent. You play the customer. Difficulty: ' + difficulty + '.', created_at: new Date().toISOString() });

        var res = await apiCall('roleplay_start', {
            room_id: currentRoom.id, agent_id: targetAgent,
            scenario: scenario, custom_scenario: customText, difficulty: difficulty
        });

        if (res.opening) {
            appendMessage({
                speaker_id: targetAgent, speaker_type: 'agent', message: res.opening,
                name: agentP ? agentP.name : targetAgent, avatar: agentP ? agentP.avatar : '🤖',
                color: agentP ? agentP.color : '#6c5ce7', role: agentP ? agentP.role : '',
                created_at: new Date().toISOString()
            });
        }
        scrollToBottom();
        $('messageInput').placeholder = 'You are the customer. Say something...';
    }

    function endRoleplay() {
        activeRoleplay = null;
        sendMode = 'chat';
        $('roleplayChip').classList.remove('active');
        $('messageInput').placeholder = 'Message your team... (type @ to mention an agent)';

        var banner = $('roleplayBanner');
        if (banner) banner.remove();

        targetAgents = [];
        renderRoster();
        renderTargetChips();
        updateBroadcastChip();

        appendMessage({ speaker_id: 'system', speaker_type: 'system', message: '🎭 Role-play ended. Returning to normal team chat.', created_at: new Date().toISOString() });
        scrollToBottom();
    }

    /* ═══════════════════════════════════════
       AGENT-TO-AGENT NEGOTIATION
       ═══════════════════════════════════════ */
    function showNegotiateModal() {
        if (!currentRoom) { toast('Please open a room first.', 'error'); return; }
        if (currentAgents.length < 2) { toast('Need at least 2 agents to negotiate.', 'error'); return; }
        showModal('negotiateModal');
    }

    async function launchNegotiation() {
        var topic = $('negTopic').value.trim();
        var rounds = parseInt($('negRounds').value) || 3;
        if (!topic) { toast('Enter a negotiation topic.', 'error'); return; }

        hideModal('negotiateModal');

        appendMessage({ speaker_id: 'system', speaker_type: 'system', message: '🤝 NEGOTIATION STARTED: "' + topic + '" — ' + currentAgents.length + ' agents × ' + rounds + ' rounds. Agents will discuss autonomously...', created_at: new Date().toISOString() });

        var loadingEl = document.createElement('div');
        loadingEl.className = 'system-message';
        loadingEl.id = 'negotiate-loading';
        loadingEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Agents are negotiating... This may take a moment.';
        loadingEl.style.cssText = 'text-align:center;padding:1rem;color:var(--accent-light);animation:pulse 1.5s infinite';
        $('messagesContainer').appendChild(loadingEl);
        scrollToBottom();

        try {
            var res = await apiCall('negotiate', {
                room_id: currentRoom.id, topic: topic, rounds: rounds,
                model: ($('modelSelect') || {}).value || 'auto'
            });

            var loading = $('negotiate-loading');
            if (loading) loading.remove();

            if (res.error) {
                appendMessage({ speaker_id: 'system', speaker_type: 'system', message: '❌ Negotiation error: ' + res.error, created_at: new Date().toISOString() });
                return;
            }

            if (res.transcript) {
                res.transcript.forEach(function (round) {
                    appendMessage({ speaker_id: 'system', speaker_type: 'system', message: '── Round ' + round.round + ' of ' + rounds + ' ──', created_at: new Date().toISOString() });
                    round.responses.forEach(function (r) {
                        appendMessage({ speaker_id: r.agent_id, speaker_type: 'agent', message: r.text, created_at: r.timestamp });
                    });
                });
            }

            if (res.consensus) {
                appendMessage({ speaker_id: 'system', speaker_type: 'system', message: '📋 **CONSENSUS SUMMARY**\n\n' + res.consensus, created_at: new Date().toISOString() });
            }
            scrollToBottom();
        } catch (e) {
            var ld = $('negotiate-loading');
            if (ld) ld.remove();
            appendMessage({ speaker_id: 'system', speaker_type: 'system', message: '❌ Negotiation failed: ' + e.message, created_at: new Date().toISOString() });
        }
    }

    /* ═══════════════════════════════════════
       PERFORMANCE TRACKING
       ═══════════════════════════════════════ */
    function trackPerformance(agentId, message) {
        if (!agentPerformance[agentId]) {
            agentPerformance[agentId] = { messages: 0, totalLength: 0, lastActive: null };
        }
        agentPerformance[agentId].messages++;
        agentPerformance[agentId].totalLength += (message || '').length;
        agentPerformance[agentId].lastActive = new Date();

        var perfBtn = $('perfBtn');
        if (perfBtn) perfBtn.style.display = 'flex';
    }

    function showPerformancePanel() {
        var content = $('perfContent');
        if (Object.keys(agentPerformance).length === 0) {
            content.innerHTML = '<p style="color:var(--text3);text-align:center;padding:1rem">No performance data yet. Start chatting with agents to collect metrics.</p>';
            showModal('performanceModal');
            return;
        }

        var html = '<div class="perf-grid">';
        var sorted = Object.entries(agentPerformance).sort(function (a, b) { return b[1].messages - a[1].messages; });
        var maxMsgs = Math.max.apply(null, sorted.map(function (s) { return s[1].messages; }));

        sorted.forEach(function (entry) {
            var agentId = entry[0], stats = entry[1];
            var p = AGENT_PERSONAS[agentId] || {};
            var avgLen = Math.round(stats.totalLength / stats.messages);
            var activity = Math.round((stats.messages / maxMsgs) * 100);
            var responsiveness = avgLen > 200 ? 'Detailed' : avgLen > 80 ? 'Balanced' : 'Concise';
            var barColor = activity > 70 ? 'var(--green)' : activity > 40 ? 'var(--yellow)' : 'var(--text3)';

            html +=
                '<div class="perf-agent">' +
                    '<div class="pa-header"><span class="pa-avatar">' + (p.avatar || '🤖') + '</span><span class="pa-name">' + (p.name || agentId) + '</span></div>' +
                    '<div class="pa-stats">' +
                        '<div class="pa-stat"><span class="label">Responses</span><span class="value">' + stats.messages + '</span></div>' +
                        '<div class="pa-stat"><span class="label">Avg Length</span><span class="value">' + avgLen + ' chars</span></div>' +
                        '<div class="pa-stat"><span class="label">Style</span><span class="value">' + responsiveness + '</span></div>' +
                    '</div>' +
                    '<div class="perf-bar"><div class="fill" style="width:' + activity + '%;background:' + barColor + '"></div></div>' +
                '</div>';
        });

        html += '</div>';
        content.innerHTML = html;
        showModal('performanceModal');
    }

    /* ═══════════════════════════════════════
       MESSAGE SEARCH (v2.0)
       ═══════════════════════════════════════ */
    function toggleSearch() {
        searchOpen = !searchOpen;
        var panel = $('searchPanel');
        if (!panel) return;
        panel.style.display = searchOpen ? 'flex' : 'none';
        if (searchOpen) { $('searchInput').value = ''; $('searchInput').focus(); $('searchResults').innerHTML = ''; }
    }

    function performSearch(query) {
        var results = $('searchResults');
        if (!results) return;
        if (!query || query.length < 2) { results.innerHTML = '<div style="color:var(--text3);padding:.5rem;font-size:.82rem">Type at least 2 characters...</div>'; return; }

        var q = query.toLowerCase();
        var matches = allMessages.filter(function (m) {
            return m.message && m.message.toLowerCase().indexOf(q) >= 0;
        });

        if (matches.length === 0) {
            results.innerHTML = '<div style="color:var(--text3);padding:.5rem;font-size:.82rem">No matches found.</div>';
            return;
        }

        results.innerHTML = '';
        matches.slice(0, 50).forEach(function (msg) {
            var div = document.createElement('div');
            div.className = 'search-result-item';
            var p = msg.speaker_type === 'agent' ? (AGENT_PERSONAS[msg.speaker_id] || {}) : {};
            var name = msg.speaker_type === 'user' ? 'You' : (p.name || msg.speaker_id || 'System');
            var snippet = msg.message.length > 120 ? msg.message.substring(0, 120) + '...' : msg.message;
            div.innerHTML = '<div class="sr-name">' + esc(name) + '</div><div class="sr-text">' + esc(snippet) + '</div>';
            div.onclick = function () {
                var el = document.querySelector('[data-msg-id="' + CSS.escape(msg.id) + '"]');
                if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); el.classList.add('msg-highlight'); setTimeout(function () { el.classList.remove('msg-highlight'); }, 2000); }
            };
            results.appendChild(div);
        });
    }

    /* ═══════════════════════════════════════
       MESSAGE PINNING (v2.0)
       ═══════════════════════════════════════ */
    function togglePin(msgId) {
        var idx = pinnedMessages.indexOf(msgId);
        if (idx >= 0) {
            pinnedMessages.splice(idx, 1);
            toast('Message unpinned', 'info');
        } else {
            pinnedMessages.push(msgId);
            toast('Message pinned', 'success');
        }
        var el = document.querySelector('[data-msg-id="' + CSS.escape(msgId) + '"]');
        if (el) el.classList.toggle('msg-pinned', idx < 0);
    }

    function togglePinnedPanel() {
        var panel = $('pinnedPanel');
        if (!panel) return;
        var isVisible = panel.style.display === 'block';
        panel.style.display = isVisible ? 'none' : 'block';

        if (!isVisible) {
            panel.innerHTML = '<div style="font-weight:700;margin-bottom:.5rem"><i class="fas fa-thumbtack"></i> Pinned Messages</div>';
            if (pinnedMessages.length === 0) {
                panel.innerHTML += '<div style="color:var(--text3);font-size:.82rem">No pinned messages.</div>';
                return;
            }
            pinnedMessages.forEach(function (msgId) {
                var msg = allMessages.find(function (m) { return m.id === msgId; });
                if (!msg) return;
                var div = document.createElement('div');
                div.className = 'pinned-item';
                var p = msg.speaker_type === 'agent' ? (AGENT_PERSONAS[msg.speaker_id] || {}) : {};
                div.innerHTML = '<strong>' + esc(p.name || msg.name || msg.speaker_id) + ':</strong> ' + esc(msg.message.substring(0, 100));
                div.onclick = function () {
                    var el = document.querySelector('[data-msg-id="' + CSS.escape(msgId) + '"]');
                    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                };
                panel.appendChild(div);
            });
        }
    }

    /* ═══════════════════════════════════════
       EXPORT
       ═══════════════════════════════════════ */
    function exportTranscript() {
        if (!currentRoom) return;

        var msgs = $('messagesContainer');
        var allMsgs = msgs.querySelectorAll('.msg');

        var markdown = '# Team Chat Transcript\n';
        markdown += '**Room:** ' + currentRoom.name + '\n';
        markdown += '**Purpose:** ' + (currentRoom.purpose || 'General') + '\n';
        markdown += '**Agents:** ' + currentAgents.map(function (a) { return a.avatar + ' ' + a.name + ' (' + a.role + ')'; }).join(', ') + '\n';
        markdown += '**Exported:** ' + new Date().toLocaleString() + '\n\n---\n\n';

        allMsgs.forEach(function (el) {
            var nameEl = el.querySelector('.msg-name');
            var textEl = el.querySelector('.msg-text');
            var timeEl = el.querySelector('.msg-time');

            if (el.classList.contains('system-msg')) {
                markdown += '> *' + (textEl ? textEl.textContent : '') + '*\n\n';
            } else {
                var name = nameEl ? nameEl.textContent : 'Unknown';
                var text = textEl ? textEl.textContent : '';
                var time = timeEl ? timeEl.textContent : '';
                markdown += '**' + name + '** ' + (time ? '(' + time + ')' : '') + '\n' + text + '\n\n';
            }
        });

        if (Object.keys(agentPerformance).length > 0) {
            markdown += '---\n\n## Agent Performance Summary\n\n';
            markdown += '| Agent | Responses | Avg Length | Style |\n|-------|-----------|-----------|-------|\n';
            Object.entries(agentPerformance)
                .sort(function (a, b) { return b[1].messages - a[1].messages; })
                .forEach(function (entry) {
                    var agentId = entry[0], stats = entry[1];
                    var p = AGENT_PERSONAS[agentId] || {};
                    var avgLen = Math.round(stats.totalLength / stats.messages);
                    var style = avgLen > 200 ? 'Detailed' : avgLen > 80 ? 'Balanced' : 'Concise';
                    markdown += '| ' + (p.avatar || '') + ' ' + (p.name || agentId) + ' | ' + stats.messages + ' | ' + avgLen + ' chars | ' + style + ' |\n';
                });
        }

        var blob = new Blob([markdown], { type: 'text/markdown' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'team-chat-' + currentRoom.name.replace(/\s+/g, '-').toLowerCase() + '-' + new Date().toISOString().slice(0, 10) + '.md';
        a.click();
        URL.revokeObjectURL(url);
        toast('Transcript exported', 'success');
    }

    function exportCSV() {
        if (!currentRoom || allMessages.length === 0) { toast('No messages to export', 'error'); return; }

        var rows = [['Timestamp', 'Speaker', 'Type', 'Role', 'Message']];
        allMessages.forEach(function (msg) {
            var p = msg.speaker_type === 'agent' ? (AGENT_PERSONAS[msg.speaker_id] || {}) : {};
            var name = msg.speaker_type === 'user' ? userName : (p.name || msg.name || msg.speaker_id || 'System');
            rows.push([
                msg.created_at || '',
                name,
                msg.speaker_type || '',
                p.role || msg.role || '',
                '"' + (msg.message || '').replace(/"/g, '""') + '"'
            ]);
        });

        var csv = rows.map(function (r) { return r.join(','); }).join('\n');
        var blob = new Blob([csv], { type: 'text/csv' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'team-chat-' + currentRoom.name.replace(/\s+/g, '-').toLowerCase() + '-' + new Date().toISOString().slice(0, 10) + '.csv';
        a.click();
        URL.revokeObjectURL(url);
        toast('CSV exported', 'success');
    }

    /* ═══════════════════════════════════════
       ROOM PERSISTENCE
       ═══════════════════════════════════════ */
    function saveLastRoom() {
        if (currentRoom) {
            try { localStorage.setItem('tc_last_room', currentRoom.id); } catch (e) { /* ignore */ }
        }
    }

    function getLastRoom() {
        try { return localStorage.getItem('tc_last_room'); } catch (e) { return null; }
    }

    /* ═══════════════════════════════════════
       INIT
       ═══════════════════════════════════════ */
    function init(config) {
        csrfToken = config.csrfToken || '';
        userName = config.userName || 'Boss';

        loadRooms();
        updateBroadcastChip();

        // WebSocket
        if (config.userId) connectWS(config.userId);

        // URL auto-gather
        var params = new URLSearchParams(window.location.search);
        if (params.get('auto_gather')) {
            var count = parseInt(params.get('auto_gather')) || 5;
            var purpose = params.get('purpose') || 'general';
            quickGather(count, purpose);
            window.history.replaceState({}, '', '/team-chat.php');
        } else {
            var lastRoomId = getLastRoom();
            if (lastRoomId) openRoom(lastRoomId);
        }
    }

    /* ═══════════════════════════════════════
       PUBLIC API
       ═══════════════════════════════════════ */
    window.TeamChat = {
        init: init,
        connectWS: connectWS,
        // Room management
        loadRooms: loadRooms,
        openRoom: openRoom,
        createRoom: createRoom,
        quickGather: quickGather,
        closeRoom: closeRoom,
        // Messaging
        sendMessage: sendMessage,
        handleInputKey: handleInputKey,
        handleInputChange: handleInputChange,
        // Agent management
        toggleAgentTarget: toggleAgentTarget,
        toggleBroadcast: toggleBroadcast,
        setMode: setMode,
        removeAgent: removeAgent,
        gatherAgents: gatherAgents,
        showAddAgentModal: showAddAgentModal,
        addPickedAgents: addPickedAgents,
        // Modals
        showModal: showModal,
        hideModal: hideModal,
        showCreateModal: function () { showModal('createModal'); },
        showGatherModal: function () { showModal('gatherModal'); },
        showRoleplayModal: showRoleplayModal,
        showNegotiateModal: showNegotiateModal,
        // Role-play
        launchRoleplay: launchRoleplay,
        endRoleplay: endRoleplay,
        // Negotiation
        launchNegotiation: launchNegotiation,
        // Performance
        showPerformancePanel: showPerformancePanel,
        // Export
        exportTranscript: exportTranscript,
        exportCSV: exportCSV,
        // v2.0 features
        toggleSearch: toggleSearch,
        performSearch: performSearch,
        togglePin: togglePin,
        togglePinnedPanel: togglePinnedPanel,
        // UI
        toggleSidebar: toggleSidebar,
        toggleRoster: toggleRoster,
        // Data
        AGENT_PERSONAS: AGENT_PERSONAS
    };
})();
