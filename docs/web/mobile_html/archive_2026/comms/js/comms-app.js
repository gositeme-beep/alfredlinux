/**
 * GoSiteMe Veil — Main Application Controller
 *
 * Ties together: CommsCrypto (E2E encryption), CommsRTC (video/audio),
 * and the UI layer. Manages contacts, conversations, message flow,
 * file transfers, and call lifecycle.
 */

const CommsApp = (() => {
    'use strict';

    // ── State ──────────────────────────────────────────────────────
    let csrfToken       = '';
    let myClientId      = 0;
    let contacts        = [];
    let activeContact   = null;
    let messages        = {};     // { contactId: [msg, ...] }
    let pollTimer       = null;
    let lastMsgId       = 0;
    let initialized     = false;
    let callTimerInterval = null;
    let callStartTime   = 0;
    let selfDestructTime = 0;     // seconds, 0 = off

    const API = '/api/comms.php';

    // ── API Helpers ────────────────────────────────────────────────

    async function api(action, opts = {}) {
        const url = API + '?action=' + encodeURIComponent(action) +
            (opts.params ? '&' + new URLSearchParams(opts.params).toString() : '');
        const fetchOpts = {
            credentials: 'same-origin',
            headers: {},
        };
        if (opts.method === 'POST') {
            fetchOpts.method = 'POST';
            fetchOpts.headers['Content-Type'] = 'application/json';
            fetchOpts.headers['X-CSRF-Token'] = csrfToken;
            fetchOpts.body = JSON.stringify(opts.body || {});
        }
        const res = await fetch(url, fetchOpts);
        const data = await res.json();
        data._httpStatus = res.status;
        if (data.csrf_token) csrfToken = data.csrf_token;
        return data;
    }

    // ── Toast Notifications ────────────────────────────────────────

    function toast(msg, type = 'info') {
        const container = document.getElementById('toast-container');
        if (!container) return;
        const el = document.createElement('div');
        el.className = 'toast ' + type;
        el.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i> ${escapeHtml(msg)}`;
        container.appendChild(el);
        setTimeout(() => el.remove(), 4000);
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // ── Initialization ─────────────────────────────────────────────

    async function init() {
        // Check auth + get CSRF token
        const status = await api('my_keys');
        if (!status.success) {
            toast('Authentication required. Please log in.', 'error');
            return;
        }

        myClientId = status.client_id;
        csrfToken = status.csrf_token;

        if (!status.has_keys) {
            showSetupScreen();
            return;
        }

        // Verify local keys exist
        const hasLocal = await CommsCrypto.hasIdentityKeys();
        if (!hasLocal) {
            showSetupScreen(true); // keys on server but not local = new device
            return;
        }

        // Check prekey count
        if (status.prekey_count < 5) {
            await replenishPrekeys();
        }

        await loadContacts();
        startMessagePolling();
        setupRTCCallbacks();
        CommsRTC.startSignalPolling();
        setupUIEvents();
        showMainUI();
        initialized = true;
    }

    // ── Key Setup ──────────────────────────────────────────────────

    function showSetupScreen(isNewDevice = false) {
        document.getElementById('setup-screen').classList.remove('hidden');
        document.getElementById('main-content').classList.add('hidden');

        if (isNewDevice) {
            document.getElementById('setup-title').textContent = 'New Device Detected';
            document.getElementById('setup-desc').textContent =
                'Your encryption keys were generated on another device. Import your key backup or generate new keys (you will lose previous conversation history).';
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
            toast('Generating encryption keys...', 'info');

            // Generate identity keys
            const keys = await CommsCrypto.generateIdentityKeys();

            // Generate prekeys
            const prekeys = await CommsCrypto.generatePreKeys(20);

            // Register with server
            const result = await api('register_keys', {
                method: 'POST',
                body: {
                    ecdh_public: keys.ecdhPublic,
                    ecdsa_public: keys.ecdsaPublic,
                    fingerprint: keys.fingerprint,
                    prekeys: prekeys,
                },
            });

            if (!result.success) throw new Error(result.error || 'Registration failed');

            toast('Encryption keys generated! Your communications are now E2E encrypted.', 'success');

            await loadContacts();
            startMessagePolling();
            setupRTCCallbacks();
            CommsRTC.startSignalPolling();
            setupUIEvents();
            showMainUI();
            initialized = true;

        } catch (err) {
            toast('Key generation failed: ' + err.message, 'error');
            btn.disabled = false;
            btn.textContent = 'Generate Encryption Keys';
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
                prekeys: prekeys,
            },
        });
    }

    // ── Contacts ───────────────────────────────────────────────────

    async function loadContacts() {
        const data = await api('contacts');
        if (data.success) {
            contacts = data.contacts || [];
            renderContactList();
        }
    }

    function renderContactList() {
        const list = document.getElementById('conversations-list');
        if (!list) return;

        if (contacts.length === 0) {
            list.innerHTML = `
                <div style="padding: 40px 20px; text-align: center; color: var(--text-muted); font-size: 0.85rem;">
                    <i class="fas fa-user-plus" style="font-size: 2rem; margin-bottom: 12px; display: block; color: var(--accent);"></i>
                    No contacts yet.<br>Tap + to add someone.
                </div>`;
            return;
        }

        list.innerHTML = contacts.map(c => {
            const initials = getInitials(c.firstname, c.lastname);
            const name = (c.firstname + ' ' + c.lastname).trim();
            const isActive = activeContact && activeContact.contact_id === c.contact_id;
            const lastMsg = messages[c.contact_id]?.slice(-1)[0];
            const preview = lastMsg ? '🔒 Encrypted message' : 'Start a conversation';
            const time = c.last_message_at ? formatTime(c.last_message_at) : '';

            return `
                <div class="conv-item ${isActive ? 'active' : ''}" data-id="${c.contact_id}">
                    <div class="conv-avatar ${c.verified ? 'verified' : ''}">${escapeHtml(initials)}</div>
                    <div class="conv-info">
                        <div class="conv-name">${escapeHtml(name)}</div>
                        <div class="conv-preview">${preview}</div>
                    </div>
                    <div class="conv-meta">
                        <span class="conv-time">${escapeHtml(time)}</span>
                    </div>
                </div>`;
        }).join('');

        // Click handlers
        list.querySelectorAll('.conv-item').forEach(el => {
            el.addEventListener('click', () => {
                const id = parseInt(el.dataset.id);
                const contact = contacts.find(c => c.contact_id === id);
                if (contact) openConversation(contact);
            });
        });
    }

    // ── Conversations ──────────────────────────────────────────────

    async function openConversation(contact) {
        activeContact = contact;
        renderContactList(); // Highlight active

        const chatArea = document.getElementById('active-chat');
        const emptyState = document.getElementById('empty-state');
        chatArea.classList.remove('hidden');
        emptyState.classList.add('hidden');

        // Set header
        const name = (contact.firstname + ' ' + contact.lastname).trim();
        document.getElementById('chat-contact-name').textContent = name;
        document.getElementById('chat-contact-avatar').textContent = getInitials(contact.firstname, contact.lastname);

        // Load history
        await loadHistory(contact.contact_id);
        renderMessages();
        scrollToBottom();

        // Focus input
        document.getElementById('msg-input').focus();

        // Mobile: hide sidebar
        if (window.innerWidth <= 768) {
            document.getElementById('sidebar').classList.add('hidden-mobile');
        }
    }

    async function loadHistory(contactId) {
        if (!messages[contactId]) messages[contactId] = [];

        const data = await api('history', { params: { with: contactId, limit: 50 } });
        if (!data.success) return;

        // Decrypt each message
        const decrypted = [];
        for (const msg of (data.messages || [])) {
            try {
                let sessionKey = await CommsCrypto.getSessionKey(msg.sender_id === myClientId ? contactId : msg.sender_id);

                // If no session key and there's an ephemeral key, establish session
                if (!sessionKey && msg.sender_ephemeral && msg.sender_id !== myClientId) {
                    sessionKey = await CommsCrypto.acceptSession(msg.sender_id, msg.sender_ephemeral);
                }

                if (sessionKey) {
                    const plaintext = await CommsCrypto.decryptMessage(msg.ciphertext, msg.iv, sessionKey);
                    decrypted.push({
                        id: msg.id,
                        sender_id: msg.sender_id,
                        text: plaintext,
                        type: parseInt(msg.message_type),
                        time: msg.created_at,
                        expires: msg.expires_at,
                    });
                } else {
                    decrypted.push({
                        id: msg.id,
                        sender_id: msg.sender_id,
                        text: '🔒 Cannot decrypt (missing session key)',
                        type: 0,
                        time: msg.created_at,
                        encrypted: true,
                    });
                }
            } catch (e) {
                decrypted.push({
                    id: msg.id,
                    sender_id: msg.sender_id,
                    text: '🔒 Decryption failed',
                    type: 0,
                    time: msg.created_at,
                    encrypted: true,
                });
            }
        }

        messages[contactId] = decrypted;
    }

    function renderMessages() {
        const container = document.getElementById('messages-container');
        if (!container || !activeContact) return;

        const msgs = messages[activeContact.contact_id] || [];

        if (msgs.length === 0) {
            container.innerHTML = `
                <div class="e2e-banner">
                    <i class="fas fa-lock"></i>
                    Messages are end-to-end encrypted. No one outside this chat can read them.
                </div>`;
            return;
        }

        let html = `<div class="e2e-banner"><i class="fas fa-lock"></i> End-to-end encrypted</div>`;
        let lastDate = '';

        for (const msg of msgs) {
            const date = formatDate(msg.time);
            if (date !== lastDate) {
                html += `<div class="msg-date-divider">${escapeHtml(date)}</div>`;
                lastDate = date;
            }

            const isSent = msg.sender_id === myClientId;
            const timeStr = formatTimeShort(msg.time);

            if (msg.type === 1) {
                // File message
                let fileInfo;
                try { fileInfo = JSON.parse(msg.text); } catch { fileInfo = { name: 'File', size: 0 }; }
                html += `
                    <div class="msg ${isSent ? 'sent' : 'received'}">
                        <div class="msg-file" data-token="${escapeHtml(fileInfo.token || '')}" data-key="${escapeHtml(fileInfo.key || '')}">
                            <div class="msg-file-icon"><i class="fas fa-file-arrow-down"></i></div>
                            <div class="msg-file-info">
                                <div class="msg-file-name">${escapeHtml(fileInfo.name || 'Encrypted File')}</div>
                                <div class="msg-file-size">${formatFileSize(fileInfo.size || 0)}</div>
                            </div>
                        </div>
                        <div class="msg-time">${timeStr}</div>
                    </div>`;
            } else {
                html += `
                    <div class="msg ${isSent ? 'sent' : 'received'}">
                        <div class="msg-text">${escapeHtml(msg.text)}</div>
                        <div class="msg-time">${timeStr}${msg.encrypted ? ' 🔒' : ''}</div>
                    </div>`;
            }
        }

        container.innerHTML = html;

        // File download handlers
        container.querySelectorAll('.msg-file[data-token]').forEach(el => {
            el.addEventListener('click', () => downloadFile(el.dataset.token, el.dataset.key));
        });
    }

    function scrollToBottom() {
        const container = document.getElementById('messages-container');
        if (container) container.scrollTop = container.scrollHeight;
    }

    // ── Send Message ───────────────────────────────────────────────

    async function sendMessage() {
        if (!activeContact) return;

        const input = document.getElementById('msg-input');
        const text = input.value.trim();
        if (!text) return;

        input.value = '';
        autoResizeInput(input);

        try {
            // Get or establish session key
            let sessionKey = await CommsCrypto.getSessionKey(activeContact.contact_id);
            let ephemeralPub = null;

            if (!sessionKey) {
                // Fetch their public keys and establish session
                const keysData = await api('get_keys', { params: { id: activeContact.contact_id } });
                if (!keysData.success) {
                    toast(keysData.error || 'Cannot reach contact', 'error');
                    return;
                }

                const session = await CommsCrypto.establishSession(
                    activeContact.contact_id,
                    keysData.ecdh_public,
                    keysData.prekey?.ecdh_public || null
                );
                sessionKey = session.sharedKey;
                ephemeralPub = session.ephemeralPublic;
            }

            // Encrypt message
            const encrypted = await CommsCrypto.encryptMessage(text, sessionKey);

            // Send to server
            const result = await api('send', {
                method: 'POST',
                body: {
                    recipient_id: activeContact.contact_id,
                    ciphertext: encrypted.ciphertext,
                    iv: encrypted.iv,
                    sender_ephemeral: ephemeralPub,
                    message_type: 0,
                    expires_in: selfDestructTime,
                },
            });

            if (!result.success) throw new Error(result.error);

            // Add to local messages
            if (!messages[activeContact.contact_id]) messages[activeContact.contact_id] = [];
            messages[activeContact.contact_id].push({
                id: result.message_id,
                sender_id: myClientId,
                text: text,
                type: 0,
                time: new Date().toISOString(),
            });

            renderMessages();
            scrollToBottom();

        } catch (err) {
            toast('Failed to send: ' + err.message, 'error');
        }
    }

    // ── File Transfer ──────────────────────────────────────────────

    async function sendFile(file) {
        if (!activeContact) return;
        if (file.size > 100 * 1024 * 1024) {
            toast('File too large. Max 100MB.', 'error');
            return;
        }

        toast('Encrypting file...', 'info');

        try {
            // Encrypt file client-side
            const encrypted = await CommsCrypto.encryptFile(file);

            // Upload encrypted blob
            const formData = new FormData();
            formData.append('file', encrypted.encryptedBlob);
            formData.append('encrypted_meta', encrypted.encryptedMeta);

            const uploadRes = await fetch(API + '?action=upload', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-CSRF-Token': csrfToken },
                body: formData,
            });
            const uploadData = await uploadRes.json();
            if (uploadData.csrf_token) csrfToken = uploadData.csrf_token;
            if (!uploadData.success) throw new Error(uploadData.error);

            // Send file info as encrypted message
            const fileMsg = JSON.stringify({
                token: uploadData.file_token,
                key: encrypted.fileKey,
                iv: encrypted.iv,
                meta: encrypted.encryptedMeta,
                name: file.name,
                size: file.size,
            });

            let sessionKey = await CommsCrypto.getSessionKey(activeContact.contact_id);
            let ephemeralPub = null;

            if (!sessionKey) {
                const keysData = await api('get_keys', { params: { id: activeContact.contact_id } });
                if (!keysData.success) throw new Error('Cannot reach contact');
                const session = await CommsCrypto.establishSession(
                    activeContact.contact_id, keysData.ecdh_public, keysData.prekey?.ecdh_public
                );
                sessionKey = session.sharedKey;
                ephemeralPub = session.ephemeralPublic;
            }

            const encMsg = await CommsCrypto.encryptMessage(fileMsg, sessionKey);
            const result = await api('send', {
                method: 'POST',
                body: {
                    recipient_id: activeContact.contact_id,
                    ciphertext: encMsg.ciphertext,
                    iv: encMsg.iv,
                    sender_ephemeral: ephemeralPub,
                    message_type: 1,
                },
            });

            if (!result.success) throw new Error(result.error);

            if (!messages[activeContact.contact_id]) messages[activeContact.contact_id] = [];
            messages[activeContact.contact_id].push({
                id: result.message_id,
                sender_id: myClientId,
                text: fileMsg,
                type: 1,
                time: new Date().toISOString(),
            });
            renderMessages();
            scrollToBottom();
            toast('File sent securely!', 'success');

        } catch (err) {
            toast('File transfer failed: ' + err.message, 'error');
        }
    }

    async function downloadFile(token, keyB64) {
        if (!token) return;
        toast('Downloading encrypted file...', 'info');

        try {
            const res = await fetch(API + '?action=download&t=' + encodeURIComponent(token), {
                credentials: 'same-origin',
            });
            if (!res.ok) throw new Error('Download failed');

            const encMeta = res.headers.get('X-Encrypted-Meta') || '';
            const blob = await res.blob();

            // Decrypt file
            const decrypted = await CommsCrypto.decryptFile(blob, keyB64, '', encMeta);

            // Download to user
            const url = URL.createObjectURL(decrypted.blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = decrypted.filename;
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);

            toast('File decrypted and downloaded!', 'success');

        } catch (err) {
            toast('File download failed: ' + err.message, 'error');
        }
    }

    // ── Message Polling ────────────────────────────────────────────

    let msgPollInterval = 5000;

    function startMessagePolling() {
        stopMessagePolling();
        msgPollInterval = 5000;
        pollTimer = setInterval(pollMessages, msgPollInterval);
    }

    function stopMessagePolling() {
        if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
    }

    async function pollMessages() {
        try {
            const data = await api('receive', { params: { since: lastMsgId } });
            if (data._httpStatus === 429) {
                stopMessagePolling();
                msgPollInterval = Math.min(msgPollInterval * 2, 30000);
                pollTimer = setInterval(pollMessages, msgPollInterval);
                return;
            }
            if (!data.success || !data.messages?.length) return;

            // Reset interval on success if backed off
            if (msgPollInterval > 5000) {
                stopMessagePolling();
                msgPollInterval = 5000;
                pollTimer = setInterval(pollMessages, msgPollInterval);
            }

            for (const msg of data.messages) {
                if (msg.id > lastMsgId) lastMsgId = msg.id;

                const senderId = parseInt(msg.sender_id);
                try {
                    let sessionKey = await CommsCrypto.getSessionKey(senderId);

                    if (!sessionKey && msg.sender_ephemeral) {
                        sessionKey = await CommsCrypto.acceptSession(senderId, msg.sender_ephemeral);
                    }

                    if (!sessionKey) continue;

                    const text = await CommsCrypto.decryptMessage(msg.ciphertext, msg.iv, sessionKey);

                    if (!messages[senderId]) messages[senderId] = [];
                    messages[senderId].push({
                        id: msg.id,
                        sender_id: senderId,
                        text: text,
                        type: parseInt(msg.message_type),
                        time: msg.created_at,
                    });

                    // If this is the active conversation, re-render
                    if (activeContact && activeContact.contact_id === senderId) {
                        renderMessages();
                        scrollToBottom();
                    } else {
                        // Show notification
                        const contact = contacts.find(c => c.contact_id === senderId);
                        const name = contact ? (contact.firstname + ' ' + contact.lastname).trim() : 'Someone';
                        toast('New message from ' + name, 'info');
                    }

                } catch (e) {
                    console.error('Decrypt error:', e);
                }
            }

            // Replenish prekeys if low
            if (data.prekeys_remaining < 5) {
                replenishPrekeys().catch(() => {});
            }

        } catch (err) {
            // Silent — polling continues
        }
    }

    // ── RTC Callbacks ──────────────────────────────────────────────

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
            // Show incoming call notification
            const contact = contacts.find(c => c.contact_id === contactId);
            const name = contact ? (contact.firstname + ' ' + contact.lastname).trim() : 'Unknown';
            incoming.classList.remove('hidden');
            document.getElementById('incoming-call-name').textContent = name;
            incoming.dataset.from = contactId;
            incoming.dataset.payload = payload;

            // Play ringtone (optional)
        } else if (state === 'calling') {
            overlay.classList.remove('hidden');
            document.getElementById('call-status').textContent = 'Calling...';
            const contact = contacts.find(c => c.contact_id === contactId);
            if (contact) {
                document.getElementById('call-name').textContent =
                    (contact.firstname + ' ' + contact.lastname).trim();
                document.getElementById('call-avatar-text').textContent =
                    getInitials(contact.firstname, contact.lastname);
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
        const remoteVideo = document.getElementById('remote-video');
        if (remoteVideo) remoteVideo.srcObject = stream;

        const localVideo = document.getElementById('local-video');
        if (localVideo && CommsRTC.localStream) {
            localVideo.srcObject = CommsRTC.localStream;
        }
    }

    function startCallTimer() {
        callStartTime = Date.now();
        stopCallTimer();
        callTimerInterval = setInterval(() => {
            const elapsed = Math.floor((Date.now() - callStartTime) / 1000);
            const min = Math.floor(elapsed / 60).toString().padStart(2, '0');
            const sec = (elapsed % 60).toString().padStart(2, '0');
            const timerEl = document.getElementById('call-timer');
            if (timerEl) timerEl.textContent = min + ':' + sec;
        }, 1000);
    }

    function stopCallTimer() {
        if (callTimerInterval) { clearInterval(callTimerInterval); callTimerInterval = null; }
    }

    // ── UI Events ──────────────────────────────────────────────────

    function setupUIEvents() {
        // Send message
        const input = document.getElementById('msg-input');
        if (input) {
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
            input.addEventListener('input', () => autoResizeInput(input));
        }

        const sendBtn = document.getElementById('btn-send');
        if (sendBtn) sendBtn.addEventListener('click', sendMessage);

        // File attachment
        const fileBtn = document.getElementById('btn-attach');
        if (fileBtn) {
            fileBtn.addEventListener('click', () => {
                const fi = document.getElementById('file-input');
                if (fi) fi.click();
            });
        }
        const fileInput = document.getElementById('file-input');
        if (fileInput) {
            fileInput.addEventListener('change', (e) => {
                if (e.target.files[0]) sendFile(e.target.files[0]);
                e.target.value = '';
            });
        }

        // Drag and drop files
        const chatArea = document.getElementById('chat-area');
        if (chatArea) {
            chatArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                document.getElementById('file-drop-zone')?.classList.remove('hidden');
            });
            chatArea.addEventListener('dragleave', () => {
                document.getElementById('file-drop-zone')?.classList.add('hidden');
            });
            chatArea.addEventListener('drop', (e) => {
                e.preventDefault();
                document.getElementById('file-drop-zone')?.classList.add('hidden');
                if (e.dataTransfer.files[0]) sendFile(e.dataTransfer.files[0]);
            });
        }

        // Call buttons
        document.getElementById('btn-video-call')?.addEventListener('click', () => {
            if (activeContact) CommsRTC.startCall(activeContact.contact_id, true);
        });
        document.getElementById('btn-audio-call')?.addEventListener('click', () => {
            if (activeContact) CommsRTC.startCall(activeContact.contact_id, false);
        });
        document.getElementById('btn-end-call')?.addEventListener('click', () => CommsRTC.endCall());
        document.getElementById('btn-mute')?.addEventListener('click', (e) => {
            const muted = CommsRTC.toggleMute();
            e.currentTarget.classList.toggle('active', muted);
        });
        document.getElementById('btn-cam-off')?.addEventListener('click', (e) => {
            const off = CommsRTC.toggleVideo();
            e.currentTarget.classList.toggle('active', off);
        });
        document.getElementById('btn-screen-share')?.addEventListener('click', () => CommsRTC.startScreenShare());

        // Incoming call
        document.getElementById('btn-answer-call')?.addEventListener('click', () => {
            const incoming = document.getElementById('incoming-call');
            const fromId = parseInt(incoming.dataset.from);
            const payload = incoming.dataset.payload;
            CommsRTC.answerCall(fromId, payload);
        });
        document.getElementById('btn-reject-call')?.addEventListener('click', () => {
            const incoming = document.getElementById('incoming-call');
            const fromId = parseInt(incoming.dataset.from);
            CommsRTC.rejectCall(fromId);
            incoming.classList.add('hidden');
        });

        // New chat modal
        document.getElementById('btn-new-chat')?.addEventListener('click', () => {
            document.getElementById('new-chat-modal')?.classList.remove('hidden');
            document.getElementById('search-contact-input')?.focus();
        });

        // Search contacts
        let searchDebounce;
        document.getElementById('search-contact-input')?.addEventListener('input', (e) => {
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(() => searchUsers(e.target.value), 300);
        });

        // Close modals
        document.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', () => {
                btn.closest('.modal-overlay')?.classList.add('hidden');
            });
        });

        // Settings
        document.getElementById('btn-settings')?.addEventListener('click', showSettings);

        // Self-destruct timer
        document.getElementById('destruct-timer')?.addEventListener('change', (e) => {
            selfDestructTime = parseInt(e.target.value) || 0;
        });

        // Back button (mobile)
        document.getElementById('btn-back')?.addEventListener('click', () => {
            document.getElementById('sidebar').classList.remove('hidden-mobile');
            document.getElementById('active-chat').classList.add('hidden');
            document.getElementById('empty-state').classList.remove('hidden');
            activeContact = null;
        });

        // Verify contact
        document.getElementById('btn-verify')?.addEventListener('click', showVerifyModal);
    }

    // ── Search Users ───────────────────────────────────────────────

    async function searchUsers(query) {
        const resultsEl = document.getElementById('search-results');
        if (!resultsEl) return;

        if (query.length < 2) {
            resultsEl.innerHTML = '<div style="padding:16px;color:var(--text-muted);font-size:0.85rem">Type at least 2 characters...</div>';
            return;
        }

        const data = await api('search', { params: { q: query } });
        if (!data.success || !data.users?.length) {
            resultsEl.innerHTML = '<div style="padding:16px;color:var(--text-muted);font-size:0.85rem">No users found</div>';
            return;
        }

        resultsEl.innerHTML = data.users.map(u => `
            <div class="search-result-item" data-id="${u.id}">
                <div class="conv-avatar" style="width:36px;height:36px;font-size:0.75rem">${escapeHtml(getInitials(u.firstname, u.lastname))}</div>
                <div>
                    <div style="font-weight:600;font-size:0.88rem">${escapeHtml(u.firstname + ' ' + u.lastname)}</div>
                    <span class="${u.has_comms ? 'has-comms' : 'no-comms'}">
                        <i class="fas fa-${u.has_comms ? 'lock' : 'lock-open'}" style="font-size:0.6rem"></i>
                        ${u.has_comms ? 'E2E Ready' : 'Not set up'}
                    </span>
                </div>
            </div>
        `).join('');

        resultsEl.querySelectorAll('.search-result-item').forEach(el => {
            el.addEventListener('click', () => addContact(parseInt(el.dataset.id)));
        });
    }

    async function addContact(userId) {
        // We need their email — prompt user
        const data = await api('search', { params: { q: userId.toString() } });
        // For now, add directly via the add_contact with a proxy approach
        // Actually we need email. Let's search by ID workaround:
        toast('Adding contact...', 'info');

        // Fetch user details through search and add
        const resp = await api('add_contact', {
            method: 'POST',
            body: { email: '__by_id__' + userId },
        });

        // If that fails, try a different approach
        if (!resp.success) {
            // Prompt for email
            const email = prompt('Enter the contact\'s email address:');
            if (!email) return;

            const result = await api('add_contact', { method: 'POST', body: { email } });
            if (result.success) {
                toast('Contact added!', 'success');
                document.getElementById('new-chat-modal')?.classList.add('hidden');
                await loadContacts();
            } else {
                toast(result.error || 'Failed to add contact', 'error');
            }
        } else {
            toast('Contact added!', 'success');
            document.getElementById('new-chat-modal')?.classList.add('hidden');
            await loadContacts();
        }
    }

    // ── Settings ───────────────────────────────────────────────────

    async function showSettings() {
        const modal = document.getElementById('settings-modal');
        if (!modal) return;
        modal.classList.remove('hidden');

        const fp = await CommsCrypto.getFingerprint();
        document.getElementById('my-fingerprint').textContent = fp || 'Not generated';
    }

    // ── Verify Contact ─────────────────────────────────────────────

    async function showVerifyModal() {
        if (!activeContact) return;
        const modal = document.getElementById('verify-modal');
        if (!modal) return;

        const myFp = await CommsCrypto.getFingerprint();
        const theirKeys = await api('get_keys', { params: { id: activeContact.contact_id } });

        if (theirKeys.success && myFp) {
            const safetyNumber = await CommsCrypto.generateSafetyNumber(myFp, theirKeys.fingerprint);
            document.getElementById('safety-number').textContent = safetyNumber;
        }

        modal.classList.remove('hidden');
    }

    // ── Key Backup ─────────────────────────────────────────────────

    async function exportBackup() {
        const passphrase = prompt('Enter a strong passphrase to protect your key backup:');
        if (!passphrase || passphrase.length < 8) {
            toast('Passphrase must be at least 8 characters', 'error');
            return;
        }

        const backup = await CommsCrypto.exportKeyBackup(passphrase);
        const blob = new Blob([JSON.stringify(backup)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'gositeme-comms-backup-' + new Date().toISOString().split('T')[0] + '.json';
        a.click();
        URL.revokeObjectURL(url);
        toast('Key backup exported! Store it safely.', 'success');
    }

    async function importBackup() {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = '.json';
        input.onchange = async (e) => {
            const file = e.target.files[0];
            if (!file) return;

            const passphrase = prompt('Enter the passphrase for this backup:');
            if (!passphrase) return;

            try {
                const text = await file.text();
                const backup = JSON.parse(text);
                await CommsCrypto.importKeyBackup(backup, passphrase);
                toast('Keys restored! Reloading...', 'success');
                setTimeout(() => location.reload(), 1500);
            } catch (err) {
                toast('Import failed: wrong passphrase or corrupted backup', 'error');
            }
        };
        input.click();
    }

    // ── Utilities ──────────────────────────────────────────────────

    function getInitials(first, last) {
        return ((first?.[0] || '') + (last?.[0] || '')).toUpperCase() || '?';
    }

    function formatTime(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        const now = new Date();
        const diff = now - d;
        if (diff < 86400000 && d.getDate() === now.getDate()) {
            return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }
        if (diff < 604800000) {
            return d.toLocaleDateString([], { weekday: 'short' });
        }
        return d.toLocaleDateString([], { month: 'short', day: 'numeric' });
    }

    function formatTimeShort(dateStr) {
        if (!dateStr) return '';
        return new Date(dateStr).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    function formatDate(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        const now = new Date();
        if (d.toDateString() === now.toDateString()) return 'Today';
        const yesterday = new Date(now);
        yesterday.setDate(yesterday.getDate() - 1);
        if (d.toDateString() === yesterday.toDateString()) return 'Yesterday';
        return d.toLocaleDateString([], { month: 'long', day: 'numeric', year: 'numeric' });
    }

    function formatFileSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        if (bytes < 1073741824) return (bytes / 1048576).toFixed(1) + ' MB';
        return (bytes / 1073741824).toFixed(1) + ' GB';
    }

    function autoResizeInput(el) {
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 120) + 'px';
    }

    // ── Public API ─────────────────────────────────────────────────

    return {
        init,
        generateKeys,
        exportBackup,
        importBackup,
        get csrfToken() { return csrfToken; },
        set csrfToken(v) { csrfToken = v; },
        get myClientId() { return myClientId; },
    };
})();

// Auto-initialize when DOM ready
document.addEventListener('DOMContentLoaded', () => CommsApp.init());
