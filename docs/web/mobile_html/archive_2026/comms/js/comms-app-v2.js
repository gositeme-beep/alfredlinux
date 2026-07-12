/**
 * GoSiteMe Veil v2 — Upgraded Application Controller
 *
 * Full integration of all Jetsons-era features:
 * - Alfred AI as encrypted contact
 * - Group rooms with Sender Key encryption
 * - Voice messages (record, encrypt, send)
 * - Reactions (emoji) + Threads (reply-to)
 * - Typing indicators
 * - Message editing
 * - Command Center dashboard
 * - Multi-device support
 * - PWA + Offline queue
 * - Post-quantum readiness markers
 */

const CommsApp = (() => {
    'use strict';

    // ── State ──────────────────────────────────────────────────────
    let csrfToken         = '';
    let myClientId        = 0;
    let contacts          = [];
    let groups            = [];
    let activeContact     = null;
    let activeGroup       = null;
    let messages          = {};       // { contactId: [msg, ...] }
    let groupMessages     = {};       // { groupId: [msg, ...] }
    let pollTimer         = null;
    let typingTimer       = null;
    let typingPollTimer   = null;
    let lastMsgId         = 0;
    let initialized       = false;
    let callTimerInterval = null;
    let callStartTime     = 0;
    let selfDestructTime  = 0;
    let replyToMsg        = null;     // Message being replied to
    let sidebarView       = 'chats'; // chats | groups | dashboard

    const API = '/api/comms.php';

    // ── API Helpers ────────────────────────────────────────────────

    async function api(action, opts = {}) {
        const url = API + '?action=' + encodeURIComponent(action) +
            (opts.params ? '&' + new URLSearchParams(opts.params).toString() : '');
        const fetchOpts = { credentials: 'same-origin', headers: {} };

        if (opts.method === 'POST') {
            fetchOpts.method = 'POST';
            fetchOpts.headers['Content-Type'] = 'application/json';
            fetchOpts.headers['X-CSRF-Token'] = csrfToken;
            fetchOpts.body = JSON.stringify(opts.body || {});
        }

        try {
            const res = await fetch(url, fetchOpts);
            const data = await res.json();
            if (data.csrf_token) csrfToken = data.csrf_token;
            return data;
        } catch (err) {
            // Offline — queue POST requests
            if (!navigator.onLine && opts.method === 'POST' && typeof CommsPWA !== 'undefined') {
                CommsPWA.queueMessage(opts.body || {});
                return { success: true, queued: true };
            }
            throw err;
        }
    }

    // ── Notifications ──────────────────────────────────────────────

    function toast(msg, type = 'info') {
        const container = document.getElementById('toast-container');
        if (!container) return;
        const el = document.createElement('div');
        el.className = 'toast ' + type;
        const icon = type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle';
        el.innerHTML = `<i class="fas fa-${icon}"></i> ${escapeHtml(msg)}`;
        container.appendChild(el);
        setTimeout(() => el.remove(), 4000);
    }

    function escapeHtml(text) {
        const d = document.createElement('div');
        d.textContent = text;
        return d.innerHTML;
    }

    // ══════════════════════════════════════════════════════════════
    // INITIALIZATION
    // ══════════════════════════════════════════════════════════════

    async function init() {
        const status = await api('my_keys');
        if (!status.success) {
            toast('Authentication required. Please log in.', 'error');
            return;
        }

        myClientId = status.client_id;
        csrfToken  = status.csrf_token;

        if (!status.has_keys) { showSetupScreen(); return; }

        const hasLocal = await CommsCrypto.hasIdentityKeys();
        if (!hasLocal) { showSetupScreen(true); return; }

        if (status.prekey_count < 5) await replenishPrekeys();

        // Check PQ key availability
        if (typeof CommsPQC !== 'undefined' && await CommsPQC.hasKeys()) {
            window._commsPQActive = true;
        }

        await Promise.all([loadContacts(), loadGroups()]);
        startMessagePolling();
        setupRTCCallbacks();
        CommsRTC.startSignalPolling();
        setupUIEvents();
        setupVoiceRecording();
        showMainUI();

        // Init PWA
        if (typeof CommsPWA !== 'undefined') CommsPWA.init();

        // Init Alfred alerts
        checkAlfredAlerts();

        initialized = true;
    }

    // ── Setup / Key Generation ─────────────────────────────────────

    function showSetupScreen(isNewDevice = false) {
        document.getElementById('setup-screen').classList.remove('hidden');
        document.getElementById('main-content').classList.add('hidden');
        if (isNewDevice) {
            document.getElementById('setup-title').textContent = 'New Device Detected';
            document.getElementById('setup-desc').textContent =
                'Import your key backup from another device, or generate new keys.';
            document.getElementById('btn-import-backup').classList.remove('hidden');
        }
    }

    function showMainUI() {
        document.getElementById('setup-screen').classList.add('hidden');
        document.getElementById('main-content').classList.remove('hidden');
    }

    async function generateKeys() {
        const btn = document.getElementById('btn-generate-keys');
        btn.disabled = true;
        btn.innerHTML = '<div class="spinner" style="margin:0 auto"></div>';

        try {
            const keys = await CommsCrypto.generateIdentityKeys();
            const prekeys = await CommsCrypto.generatePreKeys(20);

            const result = await api('register_keys', {
                method: 'POST',
                body: {
                    ecdh_public: keys.ecdhPublic,
                    ecdsa_public: keys.ecdsaPublic,
                    fingerprint: keys.fingerprint,
                    prekeys,
                },
            });
            if (!result.success) throw new Error(result.error || 'Registration failed');

            // Generate Post-Quantum Kyber-1024 keys
            if (typeof CommsPQC !== 'undefined') {
                try {
                    const hybrid = await CommsPQC.generateHybridKeys();
                    await CommsPQC.storeKeys(hybrid.kyber.publicKey, hybrid.kyber.secretKey, hybrid.ecdh.privateKey);
                    // Bundle PQ ECDH public (65 bytes) + Kyber public (1184 bytes) together
                    const pqBundle = new Uint8Array(hybrid.ecdhPublicRaw.length + hybrid.kyber.publicKey.length);
                    pqBundle.set(hybrid.ecdhPublicRaw, 0);
                    pqBundle.set(hybrid.kyber.publicKey, hybrid.ecdhPublicRaw.length);
                    await api('register_pq_key', {
                        method: 'POST',
                        body: { pq_public: CommsPQC.bytesToBase64(pqBundle) },
                    });
                    toast('Post-quantum keys generated!', 'success');
                    window._commsPQActive = true;
                } catch (pqErr) {
                    console.warn('PQ keygen skipped:', pqErr);
                }
            }

            // Register device
            const deviceId = CommsCrypto.ab2hex(crypto.getRandomValues(new Uint8Array(16)));
            await api('device_register', {
                method: 'POST',
                body: {
                    device_id: deviceId,
                    device_name: detectDeviceName(),
                    ecdh_public: keys.ecdhPublic,
                    ecdsa_public: keys.ecdsaPublic,
                },
            });

            toast('Encryption initialized! Your messages are now E2E encrypted.', 'success');

            await Promise.all([loadContacts(), loadGroups()]);
            startMessagePolling();
            setupRTCCallbacks();
            CommsRTC.startSignalPolling();
            setupUIEvents();
            setupVoiceRecording();
            showMainUI();
            if (typeof CommsPWA !== 'undefined') CommsPWA.init();
            initialized = true;

        } catch (err) {
            toast('Key generation failed: ' + err.message, 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-key" style="margin-right:8px"></i> Generate Encryption Keys';
        }
    }

    async function replenishPrekeys() {
        const prekeys = await CommsCrypto.generatePreKeys(20);
        const keys = await CommsCrypto.generateIdentityKeys();
        await api('register_keys', {
            method: 'POST',
            body: {
                ecdh_public: keys.ecdhPublic,
                ecdsa_public: keys.ecdsaPublic,
                fingerprint: keys.fingerprint,
                prekeys,
            },
        });
    }

    function detectDeviceName() {
        const ua = navigator.userAgent;
        if (/iPhone/.test(ua)) return 'iPhone';
        if (/iPad/.test(ua)) return 'iPad';
        if (/Android/.test(ua)) return 'Android';
        if (/Mac/.test(ua)) return 'Mac';
        if (/Windows/.test(ua)) return 'Windows';
        if (/Linux/.test(ua)) return 'Linux';
        return 'Browser';
    }

    // ══════════════════════════════════════════════════════════════
    // CONTACTS + GROUPS
    // ══════════════════════════════════════════════════════════════

    async function loadContacts() {
        const data = await api('contacts');
        if (data.success) {
            contacts = data.contacts || [];
            // Add Alfred as first contact
            if (typeof CommsAlfred !== 'undefined') {
                contacts.unshift(CommsAlfred.getAlfredContact());
            }
            if (sidebarView === 'chats') renderContactList();
        }
    }

    async function loadGroups() {
        const data = await api('my_groups');
        if (data.success) {
            groups = data.groups || [];
            if (sidebarView === 'groups') renderGroupList();
        }
    }

    function renderContactList() {
        const list = document.getElementById('conversations-list');
        if (!list) return;

        if (contacts.length === 0) {
            list.innerHTML = `
                <div style="padding:40px 20px;text-align:center;color:var(--text-muted);font-size:0.85rem">
                    <i class="fas fa-user-plus" style="font-size:2rem;margin-bottom:12px;display:block;color:var(--accent)"></i>
                    No contacts yet. Tap + to add someone.
                </div>`;
            return;
        }

        list.innerHTML = contacts.map(c => {
            const isAlfred = c.is_alfred;
            const initials = isAlfred ? 'AI' : getInitials(c.firstname, c.lastname);
            const name = isAlfred ? 'Alfred AI' : (c.firstname + ' ' + c.lastname).trim();
            const isActive = activeContact && activeContact.contact_id === c.contact_id;
            const preview = isAlfred ? 'AI Assistant — Always available' :
                (messages[c.contact_id]?.slice(-1)[0] ? '🔒 Encrypted message' : 'Start a conversation');
            const time = c.last_message_at ? formatTime(c.last_message_at) : '';

            return `
                <div class="conv-item ${isActive ? 'active' : ''} ${isAlfred ? 'alfred-contact' : ''}" data-id="${c.contact_id}" data-type="dm">
                    <div class="conv-avatar ${c.verified ? 'verified' : ''} ${isAlfred ? 'alfred-avatar' : ''}">${escapeHtml(initials)}</div>
                    <div class="conv-info">
                        <div class="conv-name">${escapeHtml(name)} ${isAlfred ? '<i class="fas fa-robot" style="font-size:0.65rem;color:var(--accent)"></i>' : ''}</div>
                        <div class="conv-preview">${preview}</div>
                    </div>
                    <div class="conv-meta">
                        <span class="conv-time">${escapeHtml(time)}</span>
                    </div>
                </div>`;
        }).join('');

        list.querySelectorAll('.conv-item[data-type="dm"]').forEach(el => {
            el.addEventListener('click', () => {
                const id = parseInt(el.dataset.id);
                const contact = contacts.find(c => c.contact_id === id);
                if (contact) openConversation(contact);
            });
        });
    }

    function renderGroupList() {
        const list = document.getElementById('conversations-list');
        if (!list) return;

        if (groups.length === 0) {
            list.innerHTML = `
                <div style="padding:40px 20px;text-align:center;color:var(--text-muted);font-size:0.85rem">
                    <i class="fas fa-users" style="font-size:2rem;margin-bottom:12px;display:block;color:var(--accent)"></i>
                    No groups yet. Create one!
                </div>`;
            return;
        }

        list.innerHTML = groups.map(g => {
            const isActive = activeGroup && activeGroup.group_id === g.group_id;
            const time = g.last_activity ? formatTime(g.last_activity) : '';

            return `
                <div class="conv-item ${isActive ? 'active' : ''}" data-id="${g.group_id}" data-type="group">
                    <div class="conv-avatar group-avatar"><i class="fas fa-users"></i></div>
                    <div class="conv-info">
                        <div class="conv-name">${escapeHtml(g.name)}</div>
                        <div class="conv-preview">${g.member_count} members · ${escapeHtml(g.group_type)}</div>
                    </div>
                    <div class="conv-meta">
                        <span class="conv-time">${escapeHtml(time)}</span>
                    </div>
                </div>`;
        }).join('');

        list.querySelectorAll('.conv-item[data-type="group"]').forEach(el => {
            el.addEventListener('click', () => {
                const group = groups.find(g => g.group_id === el.dataset.id);
                if (group) openGroupConversation(group);
            });
        });
    }

    // ══════════════════════════════════════════════════════════════
    // CONVERSATIONS — DM
    // ══════════════════════════════════════════════════════════════

    async function openConversation(contact) {
        activeContact = contact;
        activeGroup   = null;
        renderContactList();

        const chatArea = document.getElementById('active-chat');
        const emptyState = document.getElementById('empty-state');
        const dashPanel = document.getElementById('dashboard-panel');
        chatArea.classList.remove('hidden');
        emptyState.classList.add('hidden');
        if (dashPanel) dashPanel.classList.add('hidden');

        const name = contact.is_alfred ? 'Alfred AI' : (contact.firstname + ' ' + contact.lastname).trim();
        document.getElementById('chat-contact-name').textContent = name;
        document.getElementById('chat-contact-avatar').textContent = contact.is_alfred ? 'AI' : getInitials(contact.firstname, contact.lastname);

        // Show/hide call buttons for Alfred (no calls to AI)
        const callBtns = document.querySelectorAll('#btn-audio-call, #btn-video-call');
        callBtns.forEach(b => b.style.display = contact.is_alfred ? 'none' : '');

        // Show agent selector for Alfred
        const agentBar = document.getElementById('alfred-agent-bar');
        if (agentBar) agentBar.style.display = contact.is_alfred ? 'flex' : 'none';
        if (contact.is_alfred && typeof CommsAlfred !== 'undefined') {
            CommsAlfred.renderAgentSelector('alfred-agent-selector');
        }

        // Show voice record button (always available)
        document.getElementById('btn-voice-record')?.classList.remove('hidden');

        if (!contact.is_alfred) {
            await loadHistory(contact.contact_id);
        } else {
            // Alfred messages stored locally
            if (!messages[0]) messages[0] = [];
        }
        renderMessages();
        scrollToBottom();
        document.getElementById('msg-input').focus();

        // Start typing indicator polling
        startTypingPoll(contact.is_alfred ? null : contact.contact_id.toString(), 'dm');

        // Mobile
        if (window.innerWidth <= 768) {
            document.getElementById('sidebar').classList.add('hidden-mobile');
            document.getElementById('btn-back').style.display = '';
        }
    }

    async function loadHistory(contactId) {
        if (!messages[contactId]) messages[contactId] = [];

        const data = await api('history', { params: { with: contactId, limit: 50 } });
        if (!data.success) return;

        const decrypted = [];
        for (const msg of (data.messages || [])) {
            try {
                let sessionKey = await CommsCrypto.getSessionKey(msg.sender_id === myClientId ? contactId : msg.sender_id);
                if (!sessionKey && msg.kyber_ct && typeof CommsPQC !== 'undefined' && await CommsPQC.hasKeys()) {
                    try {
                        const myKeys = await CommsPQC.getKeys();
                        const capsule = {
                            ecdhEphemeral: Uint8Array.from(atob(msg.sender_ephemeral), c => c.charCodeAt(0)),
                            kyberCiphertext: CommsPQC.base64ToBytes(msg.kyber_ct),
                        };
                        const rawKey = await CommsPQC.hybridDecapsulate(myKeys.ecdhPrivate, myKeys.secretKey, capsule);
                        sessionKey = await CommsCrypto.storeSessionKey(msg.sender_id, rawKey);
                    } catch (pqErr) {
                        console.warn('PQ history decaps failed, trying classical:', pqErr);
                        if (msg.sender_ephemeral && msg.sender_id !== myClientId) {
                            sessionKey = await CommsCrypto.acceptSession(msg.sender_id, msg.sender_ephemeral);
                        }
                    }
                } else if (!sessionKey && msg.sender_ephemeral && msg.sender_id !== myClientId) {
                    sessionKey = await CommsCrypto.acceptSession(msg.sender_id, msg.sender_ephemeral);
                }
                if (sessionKey) {
                    const text = await CommsCrypto.decryptMessage(msg.ciphertext, msg.iv, sessionKey);
                    decrypted.push({
                        id: msg.id, sender_id: msg.sender_id, text,
                        type: parseInt(msg.message_type), time: msg.created_at,
                        expires: msg.expires_at, reply_to: msg.reply_to,
                        edited: !!msg.edited_at,
                    });
                } else {
                    decrypted.push({ id: msg.id, sender_id: msg.sender_id, text: '🔒 Cannot decrypt', type: 0, time: msg.created_at, encrypted: true });
                }
            } catch {
                decrypted.push({ id: msg.id, sender_id: msg.sender_id, text: '🔒 Decryption failed', type: 0, time: msg.created_at, encrypted: true });
            }
        }
        messages[contactId] = decrypted;
    }

    // ══════════════════════════════════════════════════════════════
    // CONVERSATIONS — GROUP
    // ══════════════════════════════════════════════════════════════

    async function openGroupConversation(group) {
        activeGroup   = group;
        activeContact = null;

        const chatArea = document.getElementById('active-chat');
        const emptyState = document.getElementById('empty-state');
        const dashPanel = document.getElementById('dashboard-panel');
        chatArea.classList.remove('hidden');
        emptyState.classList.add('hidden');
        if (dashPanel) dashPanel.classList.add('hidden');

        document.getElementById('chat-contact-name').textContent = group.name;
        document.getElementById('chat-contact-avatar').innerHTML = '<i class="fas fa-users" style="font-size:0.8rem"></i>';

        // Hide Alfred agent bar for groups
        const agentBar = document.getElementById('alfred-agent-bar');
        if (agentBar) agentBar.style.display = 'none';

        await loadGroupHistory(group.group_id);
        renderMessages();
        scrollToBottom();
        document.getElementById('msg-input').focus();
        startTypingPoll(group.group_id, 'group');

        if (window.innerWidth <= 768) {
            document.getElementById('sidebar').classList.add('hidden-mobile');
            document.getElementById('btn-back').style.display = '';
        }
    }

    async function loadGroupHistory(groupId) {
        if (!groupMessages[groupId]) groupMessages[groupId] = [];

        const data = await api('group_messages', { params: { group_id: groupId, limit: 50 } });
        if (!data.success) return;

        const decrypted = [];
        for (const msg of (data.messages || [])) {
            if (msg.message_type === 3) {
                // System message (unencrypted)
                try {
                    const sys = JSON.parse(msg.ciphertext);
                    decrypted.push({
                        id: msg.id, sender_id: msg.sender_id, text: sys.event || 'System',
                        type: 3, time: msg.created_at, system: true,
                        sender_name: (msg.firstname || '') + ' ' + (msg.lastname || ''),
                    });
                } catch {
                    decrypted.push({ id: msg.id, sender_id: msg.sender_id, text: 'System event', type: 3, time: msg.created_at, system: true });
                }
                continue;
            }

            try {
                if (typeof CommsGroup !== 'undefined') {
                    const text = await CommsGroup.decryptGroupMessage(groupId, msg.sender_id, msg.ciphertext, msg.iv);
                    decrypted.push({
                        id: msg.id, sender_id: msg.sender_id, text,
                        type: parseInt(msg.message_type), time: msg.created_at,
                        reply_to: msg.reply_to, edited: !!msg.edited_at,
                        sender_name: (msg.firstname || '') + ' ' + (msg.lastname || ''),
                    });
                } else {
                    decrypted.push({ id: msg.id, sender_id: msg.sender_id, text: '🔒 Group decryption unavailable', type: 0, time: msg.created_at });
                }
            } catch {
                decrypted.push({ id: msg.id, sender_id: msg.sender_id, text: '🔒 Decryption failed', type: 0, time: msg.created_at });
            }
        }
        groupMessages[groupId] = decrypted;
    }

    // ══════════════════════════════════════════════════════════════
    // RENDER MESSAGES
    // ══════════════════════════════════════════════════════════════

    function renderMessages() {
        const container = document.getElementById('messages-container');
        if (!container) return;

        const isGroup = !!activeGroup;
        const msgs = isGroup ? (groupMessages[activeGroup?.group_id] || []) :
            (messages[activeContact?.contact_id] || []);

        const isAlfred = activeContact?.is_alfred;

        if (msgs.length === 0) {
            const pqLabel = !isAlfred && window._commsPQActive ? ' · Post-Quantum' : '';
            const banner = isAlfred ?
                '<i class="fas fa-robot"></i> Chat with Alfred AI. Your messages are processed server-side.' :
                `<i class="fas fa-shield-alt"></i> End-to-end encrypted${pqLabel}`;
            container.innerHTML = `<div class="e2e-banner">${banner}</div>`;
            return;
        }

        const pqLabel = !isAlfred && window._commsPQActive ? ' · Post-Quantum' : '';
        let html = `<div class="e2e-banner"><i class="fas fa-${isAlfred ? 'robot' : 'shield-alt'}"></i> ${isAlfred ? 'Alfred AI — processed server-side' : 'E2E encrypted' + pqLabel}</div>`;
        let lastDate = '';

        for (const msg of msgs) {
            const date = formatDate(msg.time);
            if (date !== lastDate) {
                html += `<div class="msg-date-divider">${escapeHtml(date)}</div>`;
                lastDate = date;
            }

            if (msg.system) {
                html += `<div class="msg-system"><i class="fas fa-info-circle"></i> ${escapeHtml(msg.text)}</div>`;
                continue;
            }

            const isSent = msg.sender_id === myClientId;
            const timeStr = formatTimeShort(msg.time);

            // Reply reference
            let replyHtml = '';
            if (msg.reply_to) {
                const allMsgs = isGroup ? (groupMessages[activeGroup.group_id] || []) : (messages[activeContact.contact_id] || []);
                const original = allMsgs.find(m => m.id === msg.reply_to);
                if (original) {
                    replyHtml = `<div class="msg-reply-ref" data-reply-id="${msg.reply_to}">
                        <span class="reply-author">${original.sender_id === myClientId ? 'You' : escapeHtml(original.sender_name || 'Someone')}</span>
                        <span class="reply-preview">${escapeHtml((original.text || '').substring(0, 60))}</span>
                    </div>`;
                }
            }

            // Sender name in group
            const senderNameHtml = (isGroup && !isSent) ?
                `<div class="msg-sender-name">${escapeHtml(msg.sender_name || 'Unknown')}</div>` : '';

            // Voice message
            if (msg.type === 2) {
                let voiceData;
                try { voiceData = JSON.parse(msg.text); } catch { voiceData = {}; }
                html += `
                    <div class="msg ${isSent ? 'sent' : 'received'}" data-msg-id="${msg.id}">
                        ${senderNameHtml}${replyHtml}
                        <div class="voice-msg" data-token="${escapeHtml(voiceData.token || '')}" data-key="${escapeHtml(voiceData.key || '')}">
                            <button class="voice-play-btn"><i class="fas fa-play"></i></button>
                            <div class="voice-waveform">${generateWaveformBars(voiceData.waveform)}</div>
                            <span class="voice-duration">${CommsVoice?.formatDuration(voiceData.duration || 0) || '0:00'}</span>
                        </div>
                        <div class="msg-time">${timeStr}${msg.edited ? ' <i class="fas fa-pen" style="font-size:0.55rem"></i>' : ''}</div>
                        <div class="msg-actions">
                            <button class="msg-action-btn" data-action="reply" data-msg-id="${msg.id}" title="Reply"><i class="fas fa-reply"></i></button>
                            <button class="msg-action-btn" data-action="react" data-msg-id="${msg.id}" title="React"><i class="fas fa-face-smile"></i></button>
                        </div>
                    </div>`;
                continue;
            }

            // File message
            if (msg.type === 1) {
                let fileInfo;
                try { fileInfo = JSON.parse(msg.text); } catch { fileInfo = { name: 'File', size: 0 }; }
                html += `
                    <div class="msg ${isSent ? 'sent' : 'received'}" data-msg-id="${msg.id}">
                        ${senderNameHtml}${replyHtml}
                        <div class="msg-file" data-token="${escapeHtml(fileInfo.token || '')}" data-key="${escapeHtml(fileInfo.key || '')}">
                            <div class="msg-file-icon"><i class="fas fa-file-arrow-down"></i></div>
                            <div class="msg-file-info">
                                <div class="msg-file-name">${escapeHtml(fileInfo.name || 'Encrypted File')}</div>
                                <div class="msg-file-size">${formatFileSize(fileInfo.size || 0)}</div>
                            </div>
                        </div>
                        <div class="msg-time">${timeStr}</div>
                        <div class="msg-actions">
                            <button class="msg-action-btn" data-action="reply" data-msg-id="${msg.id}" title="Reply"><i class="fas fa-reply"></i></button>
                            <button class="msg-action-btn" data-action="react" data-msg-id="${msg.id}" title="React"><i class="fas fa-face-smile"></i></button>
                        </div>
                    </div>`;
                continue;
            }

            // Alfred response
            if (msg.type === 4 || (isAlfred && !isSent)) {
                const formatted = typeof CommsAlfred !== 'undefined' ? CommsAlfred.formatResponse(msg.text) : escapeHtml(msg.text);
                html += `
                    <div class="msg received alfred-msg" data-msg-id="${msg.id}">
                        <div class="alfred-badge"><i class="fas fa-robot"></i> ${escapeHtml(msg.agent || 'Alfred')}</div>
                        <div class="msg-text alfred-text">${formatted}</div>
                        <div class="msg-time">${timeStr}</div>
                    </div>`;
                continue;
            }

            // Regular text message
            html += `
                <div class="msg ${isSent ? 'sent' : 'received'}" data-msg-id="${msg.id}">
                    ${senderNameHtml}${replyHtml}
                    <div class="msg-text">${escapeHtml(msg.text)}</div>
                    <div class="msg-time">${timeStr}${msg.encrypted ? ' 🔒' : ''}${msg.edited ? ' <i class="fas fa-pen" style="font-size:0.55rem"></i>' : ''}</div>
                    <div class="msg-actions">
                        <button class="msg-action-btn" data-action="reply" data-msg-id="${msg.id}" title="Reply"><i class="fas fa-reply"></i></button>
                        <button class="msg-action-btn" data-action="react" data-msg-id="${msg.id}" title="React"><i class="fas fa-face-smile"></i></button>
                        ${isSent ? `<button class="msg-action-btn" data-action="edit" data-msg-id="${msg.id}" title="Edit"><i class="fas fa-pen"></i></button>` : ''}
                    </div>
                </div>`;
        }

        // Typing indicator
        html += '<div id="typing-indicator" class="typing-indicator hidden"><span></span><span></span><span></span></div>';

        // Reactions summary
        html += '<div id="reactions-overlay"></div>';

        container.innerHTML = html;

        // Event handlers
        container.querySelectorAll('.msg-file[data-token]').forEach(el => {
            el.addEventListener('click', () => downloadFile(el.dataset.token, el.dataset.key));
        });
        container.querySelectorAll('.msg-action-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const action = btn.dataset.action;
                const msgId = parseInt(btn.dataset.msgId);
                if (action === 'reply') startReply(msgId);
                else if (action === 'react') showReactionPicker(msgId, btn);
                else if (action === 'edit') startEdit(msgId);
            });
        });
    }

    function generateWaveformBars(waveform) {
        const data = waveform || new Array(32).fill(0.3);
        return data.map(amp => `<div class="voice-bar" style="height:${Math.max(4, amp * 32)}px"></div>`).join('');
    }

    function scrollToBottom() {
        const c = document.getElementById('messages-container');
        if (c) c.scrollTop = c.scrollHeight;
    }

    // ══════════════════════════════════════════════════════════════
    // SEND MESSAGE
    // ══════════════════════════════════════════════════════════════

    async function sendMessage() {
        const input = document.getElementById('msg-input');
        const text = input.value.trim();
        if (!text) return;
        input.value = '';
        autoResizeInput(input);

        // Alfred chat
        if ((activeContact?.is_alfred || activeContact?.contact_id === 0) && typeof CommsAlfred !== 'undefined') {
            await sendAlfredMessage(text);
            return;
        }

        // Group message
        if (activeGroup) {
            await sendGroupMessage(text);
            return;
        }

        if (!activeContact) return;

        try {
            let sessionKey = await CommsCrypto.getSessionKey(activeContact.contact_id);
            let ephemeralPub = null;
            let kyberCt = null;

            if (!sessionKey) {
                const keysData = await api('get_keys', { params: { id: activeContact.contact_id } });
                if (!keysData.success) { toast(keysData.error || 'Cannot reach contact', 'error'); return; }

                // Try hybrid PQ session if both sides support it
                if (keysData.has_pq && typeof CommsPQC !== 'undefined' && await CommsPQC.hasKeys()) {
                    try {
                        // pq_public is bundled: PQ ECDH public (65 bytes) + Kyber PK (1184 bytes)
                        const pqBundle = CommsPQC.base64ToBytes(keysData.pq_public);
                        const theirEcdh = pqBundle.slice(0, 65);
                        const theirPQ = pqBundle.slice(65);
                        const hybrid = await CommsPQC.hybridEncapsulate(theirEcdh, theirPQ);
                        ephemeralPub = btoa(String.fromCharCode(...hybrid.capsule.ecdhEphemeral));
                        kyberCt = CommsPQC.bytesToBase64(hybrid.capsule.kyberCiphertext);
                        sessionKey = await CommsCrypto.storeSessionKey(activeContact.contact_id, hybrid.sharedKey);
                    } catch (pqErr) {
                        console.warn('PQ session failed, falling back:', pqErr);
                        const session = await CommsCrypto.establishSession(activeContact.contact_id, keysData.ecdh_public, keysData.prekey?.ecdh_public || null);
                        sessionKey = session.sharedKey;
                        ephemeralPub = session.ephemeralPublic;
                    }
                } else {
                    const session = await CommsCrypto.establishSession(activeContact.contact_id, keysData.ecdh_public, keysData.prekey?.ecdh_public || null);
                    sessionKey = session.sharedKey;
                    ephemeralPub = session.ephemeralPublic;
                }
            }

            const encrypted = await CommsCrypto.encryptMessage(text, sessionKey);
            const action = replyToMsg ? 'send_reply' : 'send';
            const result = await api(action, {
                method: 'POST',
                body: {
                    recipient_id: activeContact.contact_id,
                    ciphertext: encrypted.ciphertext,
                    iv: encrypted.iv,
                    sender_ephemeral: ephemeralPub,
                    kyber_ct: kyberCt,
                    message_type: 0,
                    expires_in: selfDestructTime,
                    reply_to: replyToMsg?.id || null,
                },
            });
            if (!result.success && !result.queued) throw new Error(result.error);

            if (!messages[activeContact.contact_id]) messages[activeContact.contact_id] = [];
            messages[activeContact.contact_id].push({
                id: result.message_id || Date.now(),
                sender_id: myClientId, text, type: 0,
                time: new Date().toISOString(),
                reply_to: replyToMsg?.id || null,
            });
            clearReply();
            renderMessages();
            scrollToBottom();
        } catch (err) {
            toast('Failed to send: ' + err.message, 'error');
        }
    }

    async function sendAlfredMessage(text) {
        if (!messages[0]) messages[0] = [];
        messages[0].push({ id: Date.now(), sender_id: myClientId, text, type: 0, time: new Date().toISOString() });
        renderMessages();
        scrollToBottom();

        try {
            const data = await CommsAlfred.sendToAlfred(text, CommsAlfred.currentAgent);
            if (data.success) {
                messages[0].push({
                    id: Date.now() + 1, sender_id: 0,
                    text: data.response, type: 4,
                    time: new Date().toISOString(),
                    agent: data.agent,
                });
            } else {
                messages[0].push({ id: Date.now() + 1, sender_id: 0, text: data.error || 'Alfred is unavailable', type: 0, time: new Date().toISOString() });
            }
        } catch (err) {
            messages[0].push({ id: Date.now() + 1, sender_id: 0, text: 'Connection error: ' + err.message, type: 0, time: new Date().toISOString() });
        }
        renderMessages();
        scrollToBottom();
    }

    async function sendGroupMessage(text) {
        if (!activeGroup || typeof CommsGroup === 'undefined') return;

        try {
            const encrypted = await CommsGroup.encryptGroupMessage(activeGroup.group_id, text, myClientId);
            const result = await api('group_send', {
                method: 'POST',
                body: {
                    group_id: activeGroup.group_id,
                    ciphertext: encrypted.ciphertext,
                    iv: encrypted.iv,
                    sender_key_id: encrypted.senderKeyId,
                    message_type: 0,
                    reply_to: replyToMsg?.id || null,
                    expires_in: selfDestructTime,
                },
            });
            if (!result.success) throw new Error(result.error);

            if (!groupMessages[activeGroup.group_id]) groupMessages[activeGroup.group_id] = [];
            groupMessages[activeGroup.group_id].push({
                id: result.message_id, sender_id: myClientId, text, type: 0,
                time: new Date().toISOString(), reply_to: replyToMsg?.id || null,
            });
            clearReply();
            renderMessages();
            scrollToBottom();
        } catch (err) {
            toast('Failed to send: ' + err.message, 'error');
        }
    }

    // ══════════════════════════════════════════════════════════════
    // REPLIES & THREADS
    // ══════════════════════════════════════════════════════════════

    function startReply(msgId) {
        const allMsgs = activeGroup ?
            (groupMessages[activeGroup.group_id] || []) :
            (messages[activeContact?.contact_id] || []);
        const msg = allMsgs.find(m => m.id === msgId);
        if (!msg) return;

        replyToMsg = msg;
        const replyBar = document.getElementById('reply-bar');
        if (replyBar) {
            replyBar.classList.remove('hidden');
            const authorName = msg.sender_id === myClientId ? 'You' : (msg.sender_name || activeContact?.firstname || 'Someone');
            replyBar.innerHTML = `
                <div class="reply-bar-content">
                    <i class="fas fa-reply" style="color:var(--accent)"></i>
                    <div>
                        <span class="reply-bar-author">${escapeHtml(authorName)}</span>
                        <span class="reply-bar-text">${escapeHtml((msg.text || '').substring(0, 80))}</span>
                    </div>
                    <button class="icon-btn reply-bar-close" id="btn-clear-reply"><i class="fas fa-xmark"></i></button>
                </div>`;
            document.getElementById('btn-clear-reply')?.addEventListener('click', clearReply);
        }
        document.getElementById('msg-input')?.focus();
    }

    function clearReply() {
        replyToMsg = null;
        const replyBar = document.getElementById('reply-bar');
        if (replyBar) { replyBar.classList.add('hidden'); replyBar.innerHTML = ''; }
    }

    // ══════════════════════════════════════════════════════════════
    // REACTIONS
    // ══════════════════════════════════════════════════════════════

    function showReactionPicker(msgId, anchorEl) {
        const existing = document.getElementById('reaction-picker');
        if (existing) existing.remove();

        const emojis = ['👍', '❤️', '😂', '😮', '😢', '🔥', '👏', '🙏'];
        const picker = document.createElement('div');
        picker.id = 'reaction-picker';
        picker.className = 'reaction-picker';
        picker.innerHTML = emojis.map(e => `<button class="reaction-emoji" data-emoji="${e}">${e}</button>`).join('');

        // Position near the button
        const rect = anchorEl.getBoundingClientRect();
        picker.style.position = 'fixed';
        picker.style.top = (rect.top - 40) + 'px';
        picker.style.left = rect.left + 'px';
        picker.style.zIndex = '1000';

        document.body.appendChild(picker);

        picker.querySelectorAll('.reaction-emoji').forEach(btn => {
            btn.addEventListener('click', async () => {
                picker.remove();
                const source = activeGroup ? 'group' : 'dm';
                await api('react', { method: 'POST', body: { message_id: msgId, source, reaction: btn.dataset.emoji } });
                toast('Reaction added!', 'success');
            });
        });

        setTimeout(() => {
            document.addEventListener('click', function handler() {
                picker.remove();
                document.removeEventListener('click', handler);
            }, { once: true });
        }, 0);
    }

    // ══════════════════════════════════════════════════════════════
    // MESSAGE EDITING
    // ══════════════════════════════════════════════════════════════

    function startEdit(msgId) {
        const allMsgs = activeGroup ?
            (groupMessages[activeGroup.group_id] || []) :
            (messages[activeContact?.contact_id] || []);
        const msg = allMsgs.find(m => m.id === msgId);
        if (!msg || msg.sender_id !== myClientId) return;

        const newText = prompt('Edit message:', msg.text);
        if (newText === null || newText.trim() === msg.text) return;

        editMessage(msgId, newText.trim());
    }

    async function editMessage(msgId, newText) {
        try {
            const source = activeGroup ? 'group' : 'dm';
            const contactId = activeGroup ? null : activeContact?.contact_id;

            let ciphertext, iv;
            if (source === 'dm') {
                const sessionKey = await CommsCrypto.getSessionKey(contactId);
                if (!sessionKey) { toast('Cannot encrypt edited message', 'error'); return; }
                const enc = await CommsCrypto.encryptMessage(newText, sessionKey);
                ciphertext = enc.ciphertext;
                iv = enc.iv;
            } else {
                const enc = await CommsGroup.encryptGroupMessage(activeGroup.group_id, newText, myClientId);
                ciphertext = enc.ciphertext;
                iv = enc.iv;
            }

            await api('edit_message', { method: 'POST', body: { message_id: msgId, source, ciphertext, iv } });

            // Update local
            const msgs = source === 'dm' ? messages[contactId] : groupMessages[activeGroup.group_id];
            const m = msgs?.find(m => m.id === msgId);
            if (m) { m.text = newText; m.edited = true; }
            renderMessages();
        } catch (err) {
            toast('Edit failed: ' + err.message, 'error');
        }
    }

    // ══════════════════════════════════════════════════════════════
    // VOICE MESSAGES
    // ══════════════════════════════════════════════════════════════

    function setupVoiceRecording() {
        if (typeof CommsVoice === 'undefined') return;

        CommsVoice.setCallbacks({
            onRecordingState: (state) => {
                const recordBtn = document.getElementById('btn-voice-record');
                const recordPanel = document.getElementById('voice-record-panel');
                if (state === 'recording') {
                    if (recordBtn) recordBtn.classList.add('recording');
                    if (recordPanel) recordPanel.classList.remove('hidden');
                } else {
                    if (recordBtn) recordBtn.classList.remove('recording');
                    if (recordPanel) recordPanel.classList.add('hidden');
                }
            },
            onTimer: (seconds) => {
                const timerEl = document.getElementById('voice-timer');
                if (timerEl) timerEl.textContent = CommsVoice.formatDuration(seconds);
            },
            onWaveform: (amplitude) => {
                const level = document.getElementById('voice-level');
                if (level) level.style.width = (amplitude * 100) + '%';
            },
        });
    }

    async function toggleVoiceRecord() {
        if (typeof CommsVoice === 'undefined') return;

        if (CommsVoice.isRecording) {
            const recording = await CommsVoice.stopRecording();
            if (recording) await sendVoiceMessage(recording);
        } else {
            await CommsVoice.startRecording();
        }
    }

    async function sendVoiceMessage(recording) {
        if (!activeContact && !activeGroup) return;

        // Route to Alfred — no encryption needed for AI
        if (activeContact && (activeContact.is_alfred || activeContact.contact_id === 0)) {
            toast('Voice messages to Alfred require the Veil app', 'info');
            return;
        }

        toast('Encrypting voice message...', 'info');

        try {
            const file = new File([recording.blob], 'voice.webm', { type: recording.mimeType });
            const encrypted = await CommsCrypto.encryptFile(file);

            const formData = new FormData();
            formData.append('file', encrypted.encryptedBlob);
            formData.append('encrypted_meta', encrypted.encryptedMeta);

            const uploadRes = await fetch(API + '?action=upload', {
                method: 'POST', credentials: 'same-origin',
                headers: { 'X-CSRF-Token': csrfToken },
                body: formData,
            });
            const uploadData = await uploadRes.json();
            if (uploadData.csrf_token) csrfToken = uploadData.csrf_token;
            if (!uploadData.success) throw new Error(uploadData.error);

            const voiceMsg = JSON.stringify({
                token: uploadData.file_token,
                key: encrypted.fileKey,
                iv: encrypted.iv,
                meta: encrypted.encryptedMeta,
                duration: recording.duration,
                waveform: recording.waveform,
                name: 'voice.webm',
                size: recording.size,
            });

            if (activeGroup) {
                const enc = await CommsGroup.encryptGroupMessage(activeGroup.group_id, voiceMsg, myClientId);
                await api('group_send', {
                    method: 'POST',
                    body: { group_id: activeGroup.group_id, ciphertext: enc.ciphertext, iv: enc.iv, sender_key_id: enc.senderKeyId, message_type: 2 },
                });
            } else {
                let sessionKey = await CommsCrypto.getSessionKey(activeContact.contact_id);
                let ephemeralPub = null;
                let kyberCt = null;
                if (!sessionKey) {
                    const keysData = await api('get_keys', { params: { id: activeContact.contact_id } });
                    if (!keysData.success) { toast(keysData.error || 'Cannot reach contact', 'error'); return; }
                    if (keysData.has_pq && typeof CommsPQC !== 'undefined' && await CommsPQC.hasKeys()) {
                        try {
                            const pqBundle = CommsPQC.base64ToBytes(keysData.pq_public);
                            const hybrid = await CommsPQC.hybridEncapsulate(pqBundle.slice(0, 65), pqBundle.slice(65));
                            ephemeralPub = btoa(String.fromCharCode(...hybrid.capsule.ecdhEphemeral));
                            kyberCt = CommsPQC.bytesToBase64(hybrid.capsule.kyberCiphertext);
                            sessionKey = await CommsCrypto.storeSessionKey(activeContact.contact_id, hybrid.sharedKey);
                        } catch (pqErr) {
                            console.warn('PQ voice session failed, falling back:', pqErr);
                            const session = await CommsCrypto.establishSession(activeContact.contact_id, keysData.ecdh_public, keysData.prekey?.ecdh_public);
                            sessionKey = session.sharedKey;
                            ephemeralPub = session.ephemeralPublic;
                        }
                    } else {
                        const session = await CommsCrypto.establishSession(activeContact.contact_id, keysData.ecdh_public, keysData.prekey?.ecdh_public);
                        sessionKey = session.sharedKey;
                        ephemeralPub = session.ephemeralPublic;
                    }
                }
                const encMsg = await CommsCrypto.encryptMessage(voiceMsg, sessionKey);
                await api('send', { method: 'POST', body: { recipient_id: activeContact.contact_id, ciphertext: encMsg.ciphertext, iv: encMsg.iv, sender_ephemeral: ephemeralPub, kyber_ct: kyberCt, message_type: 2 } });
            }

            toast('Voice message sent!', 'success');
            // Reload
            if (activeGroup) await loadGroupHistory(activeGroup.group_id);
            else await loadHistory(activeContact.contact_id);
            renderMessages();
            scrollToBottom();
        } catch (err) {
            toast('Voice send failed: ' + err.message, 'error');
        }
    }

    // ══════════════════════════════════════════════════════════════
    // FILE TRANSFER
    // ══════════════════════════════════════════════════════════════

    async function sendFile(file) {
        if (!activeContact && !activeGroup) return;
        if (file.size > 100 * 1024 * 1024) { toast('Max 100MB', 'error'); return; }
        toast('Encrypting file...', 'info');

        try {
            const encrypted = await CommsCrypto.encryptFile(file);
            const formData = new FormData();
            formData.append('file', encrypted.encryptedBlob);
            formData.append('encrypted_meta', encrypted.encryptedMeta);

            const uploadRes = await fetch(API + '?action=upload', {
                method: 'POST', credentials: 'same-origin',
                headers: { 'X-CSRF-Token': csrfToken },
                body: formData,
            });
            const uploadData = await uploadRes.json();
            if (uploadData.csrf_token) csrfToken = uploadData.csrf_token;
            if (!uploadData.success) throw new Error(uploadData.error);

            const fileMsg = JSON.stringify({
                token: uploadData.file_token, key: encrypted.fileKey,
                iv: encrypted.iv, meta: encrypted.encryptedMeta,
                name: file.name, size: file.size,
            });

            if (activeGroup) {
                const enc = await CommsGroup.encryptGroupMessage(activeGroup.group_id, fileMsg, myClientId);
                await api('group_send', { method: 'POST', body: { group_id: activeGroup.group_id, ciphertext: enc.ciphertext, iv: enc.iv, sender_key_id: enc.senderKeyId, message_type: 1 } });
            } else {
                let sessionKey = await CommsCrypto.getSessionKey(activeContact.contact_id);
                let ephemeralPub = null;
                let kyberCt = null;
                if (!sessionKey) {
                    const keysData = await api('get_keys', { params: { id: activeContact.contact_id } });
                    if (!keysData.success) { toast(keysData.error || 'Cannot reach contact', 'error'); return; }
                    if (keysData.has_pq && typeof CommsPQC !== 'undefined' && await CommsPQC.hasKeys()) {
                        try {
                            const pqBundle = CommsPQC.base64ToBytes(keysData.pq_public);
                            const hybrid = await CommsPQC.hybridEncapsulate(pqBundle.slice(0, 65), pqBundle.slice(65));
                            ephemeralPub = btoa(String.fromCharCode(...hybrid.capsule.ecdhEphemeral));
                            kyberCt = CommsPQC.bytesToBase64(hybrid.capsule.kyberCiphertext);
                            sessionKey = await CommsCrypto.storeSessionKey(activeContact.contact_id, hybrid.sharedKey);
                        } catch (pqErr) {
                            console.warn('PQ file session failed, falling back:', pqErr);
                            const session = await CommsCrypto.establishSession(activeContact.contact_id, keysData.ecdh_public, keysData.prekey?.ecdh_public);
                            sessionKey = session.sharedKey;
                            ephemeralPub = session.ephemeralPublic;
                        }
                    } else {
                        const session = await CommsCrypto.establishSession(activeContact.contact_id, keysData.ecdh_public, keysData.prekey?.ecdh_public);
                        sessionKey = session.sharedKey;
                        ephemeralPub = session.ephemeralPublic;
                    }
                }
                const encMsg = await CommsCrypto.encryptMessage(fileMsg, sessionKey);
                await api('send', { method: 'POST', body: { recipient_id: activeContact.contact_id, ciphertext: encMsg.ciphertext, iv: encMsg.iv, sender_ephemeral: ephemeralPub, kyber_ct: kyberCt, message_type: 1 } });
            }

            toast('File sent!', 'success');
            if (activeGroup) await loadGroupHistory(activeGroup.group_id);
            else await loadHistory(activeContact.contact_id);
            renderMessages();
            scrollToBottom();
        } catch (err) {
            toast('File send failed: ' + err.message, 'error');
        }
    }

    async function downloadFile(token, keyB64) {
        if (!token) return;
        toast('Downloading...', 'info');
        try {
            const res = await fetch(API + '?action=download&t=' + encodeURIComponent(token), { credentials: 'same-origin' });
            if (!res.ok) throw new Error('Download failed');
            const encMeta = res.headers.get('X-Encrypted-Meta') || '';
            const blob = await res.blob();
            const dec = await CommsCrypto.decryptFile(blob, keyB64, '', encMeta);
            const url = URL.createObjectURL(dec.blob);
            const a = document.createElement('a');
            a.href = url; a.download = dec.filename;
            document.body.appendChild(a); a.click(); a.remove();
            URL.revokeObjectURL(url);
            toast('File decrypted!', 'success');
        } catch (err) {
            toast('Download failed: ' + err.message, 'error');
        }
    }

    // ══════════════════════════════════════════════════════════════
    // TYPING INDICATORS
    // ══════════════════════════════════════════════════════════════

    function sendTypingIndicator() {
        if (!activeContact && !activeGroup) return;
        if (activeContact?.is_alfred) return;

        clearTimeout(typingTimer);
        typingTimer = setTimeout(() => {
            const targetType = activeGroup ? 'group' : 'dm';
            const targetId = activeGroup ? activeGroup.group_id : activeContact.contact_id.toString();
            api('typing', { method: 'POST', body: { target_type: targetType, target_id: targetId } }).catch(() => {});
        }, 300);
    }

    function startTypingPoll(targetId, targetType) {
        stopTypingPoll();
        if (!targetId) return;
        typingPollTimer = setInterval(async () => {
            try {
                const data = await api('typing_status', { params: { target_type: targetType, target_id: targetId } });
                const el = document.getElementById('typing-indicator');
                if (el && data.success && data.typing?.length > 0) {
                    const names = data.typing.map(t => t.firstname).join(', ');
                    el.classList.remove('hidden');
                    el.setAttribute('data-who', names + ' typing...');
                } else if (el) {
                    el.classList.add('hidden');
                }
            } catch { /* ignore */ }
        }, 2000);
    }

    function stopTypingPoll() {
        if (typingPollTimer) { clearInterval(typingPollTimer); typingPollTimer = null; }
    }

    // ══════════════════════════════════════════════════════════════
    // MESSAGE POLLING
    // ══════════════════════════════════════════════════════════════

    function startMessagePolling() {
        stopMessagePolling();
        pollTimer = setInterval(pollMessages, 2000);
    }

    function stopMessagePolling() {
        if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
    }

    async function pollMessages() {
        try {
            const data = await api('receive', { params: { since: lastMsgId } });
            if (!data.success || !data.messages?.length) return;

            for (const msg of data.messages) {
                if (msg.id > lastMsgId) lastMsgId = msg.id;
                const senderId = parseInt(msg.sender_id);

                try {
                    let sessionKey = await CommsCrypto.getSessionKey(senderId);
                    if (!sessionKey && msg.kyber_ct && typeof CommsPQC !== 'undefined' && await CommsPQC.hasKeys()) {
                        // Hybrid PQ decapsulation
                        try {
                            const myKeys = await CommsPQC.getKeys();
                            const capsule = {
                                ecdhEphemeral: Uint8Array.from(atob(msg.sender_ephemeral), c => c.charCodeAt(0)),
                                kyberCiphertext: CommsPQC.base64ToBytes(msg.kyber_ct),
                            };
                            sessionKey = await CommsPQC.hybridDecapsulate(myKeys.ecdhPrivate, myKeys.secretKey, capsule);
                            sessionKey = await CommsCrypto.storeSessionKey(senderId, sessionKey);
                        } catch (pqErr) {
                            console.warn('PQ decaps failed, trying classical:', pqErr);
                            if (msg.sender_ephemeral) sessionKey = await CommsCrypto.acceptSession(senderId, msg.sender_ephemeral);
                        }
                    } else if (!sessionKey && msg.sender_ephemeral) {
                        sessionKey = await CommsCrypto.acceptSession(senderId, msg.sender_ephemeral);
                    }
                    if (!sessionKey) continue;
                    const text = await CommsCrypto.decryptMessage(msg.ciphertext, msg.iv, sessionKey);

                    if (!messages[senderId]) messages[senderId] = [];
                    messages[senderId].push({
                        id: msg.id, sender_id: senderId, text,
                        type: parseInt(msg.message_type), time: msg.created_at,
                        reply_to: msg.reply_to,
                    });

                    if (activeContact?.contact_id === senderId) {
                        renderMessages(); scrollToBottom();
                    } else {
                        const c = contacts.find(c => c.contact_id === senderId);
                        toast('New message from ' + (c ? (c.firstname + ' ' + c.lastname).trim() : 'Someone'), 'info');
                    }
                } catch { /* ignore decrypt errors */ }
            }

            if (data.prekeys_remaining < 5) replenishPrekeys().catch(() => {});
        } catch { /* silent */ }
    }

    // ══════════════════════════════════════════════════════════════
    // COMMAND CENTER DASHBOARD
    // ══════════════════════════════════════════════════════════════

    async function showDashboard() {
        activeContact = null;
        activeGroup = null;

        const chatArea = document.getElementById('active-chat');
        const emptyState = document.getElementById('empty-state');
        chatArea.classList.add('hidden');
        emptyState.classList.add('hidden');

        let dashPanel = document.getElementById('dashboard-panel');
        if (!dashPanel) {
            dashPanel = document.createElement('div');
            dashPanel.id = 'dashboard-panel';
            dashPanel.className = 'dashboard-panel';
            document.getElementById('chat-area').appendChild(dashPanel);
        }
        dashPanel.classList.remove('hidden');
        dashPanel.innerHTML = '<div style="padding:40px;text-align:center"><div class="spinner"></div><p style="margin-top:16px;color:var(--text-muted)">Loading command center...</p></div>';

        try {
            const data = await api('dashboard');
            if (!data.success) throw new Error(data.error);
            const d = data.dashboard;

            // Get Alfred alerts
            let alertsHtml = '';
            if (typeof CommsAlfred !== 'undefined') {
                const alertData = await CommsAlfred.getAlerts();
                if (alertData.alerts?.length) {
                    alertsHtml = `
                        <div class="dash-section">
                            <h3><i class="fas fa-bell" style="color:var(--yellow)"></i> Alfred Alerts</h3>
                            ${alertData.alerts.map(a => `
                                <div class="dash-alert ${a.severity}">
                                    <i class="fas fa-${a.icon}"></i> ${escapeHtml(a.message)}
                                </div>
                            `).join('')}
                        </div>`;
                }
            }

            dashPanel.innerHTML = `
                <div class="dashboard-content">
                    <div class="dash-header">
                        <h2><i class="fas fa-gauge-high" style="color:var(--accent)"></i> Command Center</h2>
                        <p>Your encrypted mission control</p>
                    </div>

                    <div class="dash-grid">
                        <div class="dash-card">
                            <div class="dash-card-icon"><i class="fas fa-envelope"></i></div>
                            <div class="dash-card-value">${d.total_messages}</div>
                            <div class="dash-card-label">Total Messages</div>
                        </div>
                        <div class="dash-card">
                            <div class="dash-card-icon"><i class="fas fa-users"></i></div>
                            <div class="dash-card-value">${d.total_contacts}</div>
                            <div class="dash-card-label">Contacts</div>
                        </div>
                        <div class="dash-card">
                            <div class="dash-card-icon"><i class="fas fa-user-group"></i></div>
                            <div class="dash-card-value">${d.total_groups}</div>
                            <div class="dash-card-label">Groups</div>
                        </div>
                        <div class="dash-card accent">
                            <div class="dash-card-icon"><i class="fas fa-envelope-open"></i></div>
                            <div class="dash-card-value">${d.unread_messages}</div>
                            <div class="dash-card-label">Unread</div>
                        </div>
                        <div class="dash-card">
                            <div class="dash-card-icon"><i class="fas fa-laptop"></i></div>
                            <div class="dash-card-value">${d.active_devices}</div>
                            <div class="dash-card-label">Devices</div>
                        </div>
                        <div class="dash-card ${d.encryption_active ? 'green' : 'red'}">
                            <div class="dash-card-icon"><i class="fas fa-${d.encryption_active ? 'shield-halved' : 'shield-exclamation'}"></i></div>
                            <div class="dash-card-value">${d.encryption_active ? 'Active' : 'Off'}</div>
                            <div class="dash-card-label">Encryption</div>
                        </div>
                        <div class="dash-card">
                            <div class="dash-card-icon"><i class="fas fa-key"></i></div>
                            <div class="dash-card-value">${d.prekeys_remaining}</div>
                            <div class="dash-card-label">Prekeys Left</div>
                        </div>
                        <div class="dash-card">
                            <div class="dash-card-icon"><i class="fas fa-hard-drive"></i></div>
                            <div class="dash-card-value">${formatFileSize(d.storage_used)}</div>
                            <div class="dash-card-label">Encrypted Storage</div>
                        </div>
                    </div>

                    ${alertsHtml}

                    <div class="dash-section">
                        <h3><i class="fas fa-fingerprint" style="color:var(--green)"></i> Your Key Fingerprint</h3>
                        <div class="fingerprint">${d.fingerprint || 'Not generated'}</div>
                    </div>

                    <div class="dash-section">
                        <h3><i class="fas fa-shield-halved" style="color:var(--accent)"></i> Security Stack</h3>
                        <div class="security-stack">
                            <div class="sec-item"><i class="fas fa-check"></i> AES-256-GCM</div>
                            <div class="sec-item"><i class="fas fa-check"></i> ECDH P-256</div>
                            <div class="sec-item"><i class="fas fa-check"></i> ECDSA Signatures</div>
                            <div class="sec-item"><i class="fas fa-check"></i> HKDF-SHA256</div>
                            <div class="sec-item"><i class="fas fa-check"></i> Sender Key Protocol</div>
                            <div class="sec-item"><i class="fas fa-check"></i> DTLS-SRTP Calls</div>
                            <div class="sec-item"><i class="fas fa-check"></i> Zero-Knowledge Server</div>
                            <div class="sec-item"><i class="fas fa-check"></i> PBKDF2 Key Backup</div>
                            <div class="sec-item pq${window._commsPQActive ? ' active' : ''}"><i class="fas fa-atom"></i> ${window._commsPQActive ? 'Post-Quantum Active ✓' : 'Post-Quantum Ready'}</div>
                        </div>
                    </div>
                </div>`;
        } catch (err) {
            dashPanel.innerHTML = `<div style="padding:40px;text-align:center;color:var(--text-muted)">Dashboard unavailable: ${escapeHtml(err.message)}</div>`;
        }
    }

    async function checkAlfredAlerts() {
        if (typeof CommsAlfred === 'undefined') return;
        try {
            const data = await CommsAlfred.getAlerts();
            if (data.alerts?.length > 0) {
                toast(`Alfred: ${data.alerts.length} alert${data.alerts.length > 1 ? 's' : ''} — check Command Center`, 'info');
            }
        } catch { /* ignore */ }
    }

    // ══════════════════════════════════════════════════════════════
    // GROUPS — Create
    // ══════════════════════════════════════════════════════════════

    async function createGroup() {
        const name = prompt('Group name:');
        if (!name || !name.trim()) return;

        try {
            const result = await api('create_group', {
                method: 'POST',
                body: { name: name.trim(), type: 'private' },
            });
            if (!result.success) throw new Error(result.error);

            toast('Group created!', 'success');
            await loadGroups();
            sidebarView = 'groups';
            renderGroupList();
        } catch (err) {
            toast('Failed: ' + err.message, 'error');
        }
    }

    // ══════════════════════════════════════════════════════════════
    // CALL LIFECYCLE
    // ══════════════════════════════════════════════════════════════

    function setupRTCCallbacks() {
        CommsRTC.setCallbacks({
            onCallStateChange: handleCallStateChange,
            onRemoteStream: handleRemoteStream,
            onError: (type, msg) => toast(msg, 'error'),
        });
    }

    function handleCallStateChange(state, contactId, payload) {
        const overlay = document.getElementById('call-overlay');
        const incoming = document.getElementById('incoming-call');

        if (state === 'ringing' && payload) {
            const c = contacts.find(c => c.contact_id === contactId);
            incoming.classList.remove('hidden');
            document.getElementById('incoming-call-name').textContent = c ? (c.firstname + ' ' + c.lastname).trim() : 'Unknown';
            incoming.dataset.from = contactId;
            incoming.dataset.payload = payload;
        } else if (state === 'calling') {
            overlay.classList.remove('hidden');
            document.getElementById('call-status').textContent = 'Calling...';
            const c = contacts.find(c => c.contact_id === contactId);
            if (c) {
                document.getElementById('call-name').textContent = (c.firstname + ' ' + c.lastname).trim();
                document.getElementById('call-avatar-text').textContent = getInitials(c.firstname, c.lastname);
            }
        } else if (state === 'connected') {
            incoming.classList.add('hidden');
            overlay.classList.remove('hidden');
            document.getElementById('call-status').textContent = 'Connected';
            document.getElementById('call-info-panel').classList.add('hidden');
            startCallTimer();
        } else if (state === 'idle') {
            overlay.classList.add('hidden');
            incoming.classList.add('hidden');
            stopCallTimer();
        }
    }

    function handleRemoteStream(stream) {
        const rv = document.getElementById('remote-video');
        if (rv) rv.srcObject = stream;
        const lv = document.getElementById('local-video');
        if (lv && CommsRTC.localStream) lv.srcObject = CommsRTC.localStream;
    }

    function startCallTimer() {
        callStartTime = Date.now();
        stopCallTimer();
        callTimerInterval = setInterval(() => {
            const s = Math.floor((Date.now() - callStartTime) / 1000);
            const el = document.getElementById('call-timer');
            if (el) el.textContent = Math.floor(s/60).toString().padStart(2,'0') + ':' + (s%60).toString().padStart(2,'0');
        }, 1000);
    }
    function stopCallTimer() { if (callTimerInterval) { clearInterval(callTimerInterval); callTimerInterval = null; } }

    // ══════════════════════════════════════════════════════════════
    // UI EVENTS
    // ══════════════════════════════════════════════════════════════

    function setupUIEvents() {
        // Send message
        const input = document.getElementById('msg-input');
        if (input) {
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
            });
            input.addEventListener('input', () => {
                autoResizeInput(input);
                sendTypingIndicator();
            });
        }
        document.getElementById('btn-send')?.addEventListener('click', sendMessage);

        // File
        document.getElementById('btn-attach')?.addEventListener('click', () => document.getElementById('file-input')?.click());
        document.getElementById('file-input')?.addEventListener('change', (e) => {
            if (e.target.files[0]) sendFile(e.target.files[0]);
            e.target.value = '';
        });

        // Drag & drop
        const chatArea = document.getElementById('chat-area');
        if (chatArea) {
            chatArea.addEventListener('dragover', (e) => { e.preventDefault(); document.getElementById('file-drop-zone')?.classList.remove('hidden'); });
            chatArea.addEventListener('dragleave', () => document.getElementById('file-drop-zone')?.classList.add('hidden'));
            chatArea.addEventListener('drop', (e) => { e.preventDefault(); document.getElementById('file-drop-zone')?.classList.add('hidden'); if (e.dataTransfer.files[0]) sendFile(e.dataTransfer.files[0]); });
        }

        // Voice record
        document.getElementById('btn-voice-record')?.addEventListener('click', toggleVoiceRecord);
        document.getElementById('btn-cancel-voice')?.addEventListener('click', () => { if (typeof CommsVoice !== 'undefined') CommsVoice.cancelRecording(); });

        // Calls
        document.getElementById('btn-video-call')?.addEventListener('click', () => { if (activeContact && !activeContact.is_alfred) CommsRTC.startCall(activeContact.contact_id, true); });
        document.getElementById('btn-audio-call')?.addEventListener('click', () => { if (activeContact && !activeContact.is_alfred) CommsRTC.startCall(activeContact.contact_id, false); });
        document.getElementById('btn-end-call')?.addEventListener('click', () => CommsRTC.endCall());
        document.getElementById('btn-mute')?.addEventListener('click', (e) => { e.currentTarget.classList.toggle('active', CommsRTC.toggleMute()); });
        document.getElementById('btn-cam-off')?.addEventListener('click', (e) => { e.currentTarget.classList.toggle('active', CommsRTC.toggleVideo()); });
        document.getElementById('btn-screen-share')?.addEventListener('click', () => CommsRTC.startScreenShare());

        // Incoming call
        document.getElementById('btn-answer-call')?.addEventListener('click', () => {
            const ic = document.getElementById('incoming-call');
            CommsRTC.answerCall(parseInt(ic.dataset.from), ic.dataset.payload);
        });
        document.getElementById('btn-reject-call')?.addEventListener('click', () => {
            const ic = document.getElementById('incoming-call');
            CommsRTC.rejectCall(parseInt(ic.dataset.from));
            ic.classList.add('hidden');
        });

        // New chat/group
        document.getElementById('btn-new-chat')?.addEventListener('click', () => document.getElementById('new-chat-modal')?.classList.remove('hidden'));
        document.getElementById('btn-create-group')?.addEventListener('click', createGroup);

        // Search
        let sd;
        document.getElementById('search-contact-input')?.addEventListener('input', (e) => { clearTimeout(sd); sd = setTimeout(() => searchUsers(e.target.value), 300); });

        // Modal close
        document.querySelectorAll('.modal-close').forEach(btn => btn.addEventListener('click', () => btn.closest('.modal-overlay')?.classList.add('hidden')));

        // Settings
        document.getElementById('btn-settings')?.addEventListener('click', showSettings);

        // Self-destruct
        document.getElementById('destruct-timer')?.addEventListener('change', (e) => { selfDestructTime = parseInt(e.target.value) || 0; });

        // Back (mobile)
        document.getElementById('btn-back')?.addEventListener('click', () => {
            document.getElementById('sidebar').classList.remove('hidden-mobile');
            document.getElementById('active-chat').classList.add('hidden');
            document.getElementById('empty-state').classList.remove('hidden');
            const dp = document.getElementById('dashboard-panel');
            if (dp) dp.classList.add('hidden');
            activeContact = null; activeGroup = null;
        });

        // Verify
        document.getElementById('btn-verify')?.addEventListener('click', showVerifyModal);

        // Sidebar tabs
        document.querySelectorAll('.sidebar-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                sidebarView = tab.dataset.view;
                document.querySelectorAll('.sidebar-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                if (sidebarView === 'chats') renderContactList();
                else if (sidebarView === 'groups') renderGroupList();
                else if (sidebarView === 'dashboard') showDashboard();
            });
        });

        // Sidebar search filter
        document.getElementById('sidebar-search')?.addEventListener('input', (e) => {
            const q = e.target.value.toLowerCase();
            document.querySelectorAll('.conv-item').forEach(el => {
                const name = el.querySelector('.conv-name')?.textContent.toLowerCase() || '';
                el.style.display = name.includes(q) ? '' : 'none';
            });
        });

        // Push notifications opt-in
        document.getElementById('btn-enable-push')?.addEventListener('click', async () => {
            if (typeof CommsPWA !== 'undefined') {
                const sub = await CommsPWA.subscribePush();
                toast(sub ? 'Push notifications enabled!' : 'Push not available', sub ? 'success' : 'error');
            }
        });
    }

    // ── Search, Add Contact, Settings ──────────────────────────────

    async function searchUsers(query) {
        const el = document.getElementById('search-results');
        if (!el) return;
        if (query.length < 2) { el.innerHTML = '<div style="padding:16px;color:var(--text-muted);font-size:0.85rem">Type 2+ chars...</div>'; return; }

        const data = await api('search', { params: { q: query } });
        if (!data.success || !data.users?.length) { el.innerHTML = '<div style="padding:16px;color:var(--text-muted);font-size:0.85rem">No users found</div>'; return; }

        el.innerHTML = data.users.map(u => `
            <div class="search-result-item" data-id="${u.id}">
                <div class="conv-avatar" style="width:36px;height:36px;font-size:0.75rem">${escapeHtml(getInitials(u.firstname, u.lastname))}</div>
                <div>
                    <div style="font-weight:600;font-size:0.88rem">${escapeHtml(u.firstname + ' ' + u.lastname)}</div>
                    <span class="${u.has_comms ? 'has-comms' : 'no-comms'}"><i class="fas fa-${u.has_comms ? 'lock' : 'lock-open'}" style="font-size:0.6rem"></i> ${u.has_comms ? 'E2E Ready' : 'Not set up'}</span>
                </div>
            </div>`).join('');

        el.querySelectorAll('.search-result-item').forEach(item => {
            item.addEventListener('click', async () => {
                const email = prompt("Enter this contact's email address:");
                if (!email) return;
                const result = await api('add_contact', { method: 'POST', body: { email } });
                if (result.success) {
                    toast('Contact added!', 'success');
                    document.getElementById('new-chat-modal')?.classList.add('hidden');
                    await loadContacts();
                } else {
                    toast(result.error || 'Failed', 'error');
                }
            });
        });
    }

    async function showSettings() {
        document.getElementById('settings-modal')?.classList.remove('hidden');
        const fp = await CommsCrypto.getFingerprint();
        document.getElementById('my-fingerprint').textContent = fp || 'Not generated';

        // Load devices
        const devData = await api('devices');
        const devContainer = document.getElementById('devices-list');
        if (devContainer && devData.success) {
            devContainer.innerHTML = (devData.devices || []).map(d => `
                <div class="device-item ${d.is_primary ? 'primary' : ''}">
                    <i class="fas fa-${d.is_primary ? 'star' : 'laptop'}" style="color:${d.is_primary ? 'var(--yellow)' : 'var(--text-muted)'}"></i>
                    <div>
                        <div style="font-weight:600;font-size:0.85rem">${escapeHtml(d.device_name)}</div>
                        <div style="font-size:0.7rem;color:var(--text-muted)">Last seen ${formatTime(d.last_seen)}</div>
                    </div>
                    ${!d.is_primary ? `<button class="icon-btn" data-device="${d.device_id}" title="Remove"><i class="fas fa-trash"></i></button>` : ''}
                </div>
            `).join('') || '<div style="color:var(--text-muted);font-size:0.85rem">No devices linked</div>';

            devContainer.querySelectorAll('button[data-device]').forEach(btn => {
                btn.addEventListener('click', async () => {
                    if (!confirm('Remove this device?')) return;
                    await api('device_remove', { method: 'POST', body: { device_id: btn.dataset.device } });
                    toast('Device removed', 'success');
                    showSettings();
                });
            });
        }
    }

    async function showVerifyModal() {
        if (!activeContact) return;
        document.getElementById('verify-modal')?.classList.remove('hidden');
        const myFp = await CommsCrypto.getFingerprint();
        const theirKeys = await api('get_keys', { params: { id: activeContact.contact_id } });
        if (theirKeys.success && myFp) {
            document.getElementById('safety-number').textContent = await CommsCrypto.generateSafetyNumber(myFp, theirKeys.fingerprint);
        }
    }

    // ── Backup ─────────────────────────────────────────────────────

    async function exportBackup() {
        const pass = prompt('Passphrase (8+ chars):');
        if (!pass || pass.length < 8) { toast('Too short', 'error'); return; }
        const backup = await CommsCrypto.exportKeyBackup(pass);
        const blob = new Blob([JSON.stringify(backup)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a'); a.href = url;
        a.download = 'comms-backup-' + new Date().toISOString().split('T')[0] + '.json';
        a.click(); URL.revokeObjectURL(url);
        toast('Backup exported!', 'success');
    }

    async function importBackup() {
        const input = document.createElement('input');
        input.type = 'file'; input.accept = '.json';
        input.onchange = async (e) => {
            const file = e.target.files[0]; if (!file) return;
            const pass = prompt('Passphrase:'); if (!pass) return;
            try {
                await CommsCrypto.importKeyBackup(JSON.parse(await file.text()), pass);
                toast('Keys restored! Reloading...', 'success');
                setTimeout(() => location.reload(), 1500);
            } catch { toast('Import failed', 'error'); }
        };
        input.click();
    }

    // ── Utilities ──────────────────────────────────────────────────

    function getInitials(f, l) { return ((f?.[0] || '') + (l?.[0] || '')).toUpperCase() || '?'; }
    function formatTime(d) {
        if (!d) return '';
        const dt = new Date(d), now = new Date(), diff = now - dt;
        if (diff < 86400000 && dt.getDate() === now.getDate()) return dt.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        if (diff < 604800000) return dt.toLocaleDateString([], { weekday: 'short' });
        return dt.toLocaleDateString([], { month: 'short', day: 'numeric' });
    }
    function formatTimeShort(d) { return d ? new Date(d).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : ''; }
    function formatDate(d) {
        if (!d) return '';
        const dt = new Date(d), now = new Date();
        if (dt.toDateString() === now.toDateString()) return 'Today';
        const y = new Date(now); y.setDate(y.getDate() - 1);
        if (dt.toDateString() === y.toDateString()) return 'Yesterday';
        return dt.toLocaleDateString([], { month: 'long', day: 'numeric', year: 'numeric' });
    }
    function formatFileSize(b) {
        if (b < 1024) return b + ' B';
        if (b < 1048576) return (b / 1024).toFixed(1) + ' KB';
        if (b < 1073741824) return (b / 1048576).toFixed(1) + ' MB';
        return (b / 1073741824).toFixed(1) + ' GB';
    }
    function autoResizeInput(el) { el.style.height = 'auto'; el.style.height = Math.min(el.scrollHeight, 120) + 'px'; }

    // ── Public API ─────────────────────────────────────────────────

    return {
        init, generateKeys, exportBackup, importBackup,
        showDashboard, createGroup, pollMessages,
        get csrfToken() { return csrfToken; },
        set csrfToken(v) { csrfToken = v; },
        get myClientId() { return myClientId; },
    };
})();

document.addEventListener('DOMContentLoaded', () => CommsApp.init());
