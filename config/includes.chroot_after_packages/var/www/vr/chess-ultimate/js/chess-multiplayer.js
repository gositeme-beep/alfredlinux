/* ═══════════════════════════════════════════════════════════════
   CHESS ULTIMATE — Multiplayer Module
   Agents: WebSocket, RTCMaster, SyncEngine (Multiplayer Division)
   
   WebRTC peer-to-peer multiplayer with:
   - Signaling via GoSiteMe WebSocket
   - Voice chat with voice changer integration
   - Game state sync and validation
   - Spectator mode
   - Room management
   - ELO rating (server-validated)
   ═══════════════════════════════════════════════════════════════ */

const ChessMultiplayer = (() => {
    let ws = null;
    let peerConnection = null;
    let dataChannel = null;
    let localStream = null;
    let rawLocalStream = null;
    let remoteStream = null;
    let currentRoom = null;
    let playerId = null;
    let isHost = false;
    let eventHandlers = {};

    const ICE_SERVERS = [
        { urls: 'stun:stun.l.google.com:19302' },
        { urls: 'stun:stun1.l.google.com:19302' },
    ];

    function on(event, handler) {
        if (!eventHandlers[event]) eventHandlers[event] = [];
        eventHandlers[event].push(handler);
    }

    function emit(event, data) {
        (eventHandlers[event] || []).forEach(h => h(data));
    }

    // WebSocket Connection
    function connect() {
        return new Promise((resolve, reject) => {
            const protocol = location.protocol === 'https:' ? 'wss:' : 'ws:';
            ws = new WebSocket(`${protocol}//${location.host}:3010/chess-ultimate`);

            ws.onopen = () => {
                playerId = generateId();
                send({ type: 'register', playerId });
                resolve();
            };

            ws.onmessage = (e) => {
                try {
                    const msg = JSON.parse(e.data);
                    handleSignal(msg);
                } catch (err) {
                    console.error('WS parse error:', err);
                }
            };

            ws.onerror = (err) => {
                console.error('WS error:', err);
                reject(err);
            };

            ws.onclose = () => {
                emit('disconnected');
                setTimeout(() => {
                    if (currentRoom) connect().then(() => joinRoom(currentRoom));
                }, 3000);
            };
        });
    }

    function send(data) {
        if (ws && ws.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify(data));
        }
    }

    function handleSignal(msg) {
        switch (msg.type) {
            case 'room-created':
                currentRoom = msg.roomId;
                isHost = true;
                emit('room-created', { roomId: msg.roomId });
                break;

            case 'player-joined':
                emit('player-joined', msg);
                if (isHost) initPeerConnection(true, msg.playerId);
                break;

            case 'offer':
                handleOffer(msg);
                break;

            case 'answer':
                handleAnswer(msg);
                break;

            case 'ice-candidate':
                handleIceCandidate(msg);
                break;

            case 'game-move':
                emit('remote-move', msg.move);
                break;

            case 'chat':
                emit('chat-message', msg);
                break;

            case 'rooms-list':
                emit('rooms-list', msg.rooms);
                break;

            case 'spectator-joined':
                emit('spectator-joined', msg);
                break;

            case 'game-over':
                emit('game-over', msg);
                break;

            case 'elo-update':
                emit('elo-update', msg);
                break;

            case 'error':
                emit('error', msg);
                break;
        }
    }

    // Room Management
    function createRoom(options = {}) {
        send({
            type: 'create-room',
            playerId,
            timeControl: options.timeControl || '10+0',
            rated: options.rated !== false,
            private: options.private || false,
            wager: options.wager || 0,
        });
    }

    function joinRoom(roomId) {
        currentRoom = roomId;
        send({ type: 'join-room', playerId, roomId });
    }

    function spectateRoom(roomId) {
        send({ type: 'spectate', playerId, roomId });
    }

    function listRooms() {
        send({ type: 'list-rooms', playerId });
    }

    // WebRTC Peer Connection
    function initPeerConnection(asOffer, targetId) {
        peerConnection = new RTCPeerConnection({ iceServers: ICE_SERVERS });

        // Data channel for game moves (low latency)
        if (asOffer) {
            dataChannel = peerConnection.createDataChannel('chess-moves', { ordered: true });
            setupDataChannel(dataChannel);
        } else {
            peerConnection.ondatachannel = (e) => {
                dataChannel = e.channel;
                setupDataChannel(dataChannel);
            };
        }

        // ICE candidates
        peerConnection.onicecandidate = (e) => {
            if (e.candidate) {
                send({ type: 'ice-candidate', candidate: e.candidate, target: targetId, playerId });
            }
        };

        // Remote stream (voice chat)
        peerConnection.ontrack = (e) => {
            remoteStream = e.streams[0];
            emit('remote-stream', remoteStream);
        };

        // Add local audio if available
        if (localStream) {
            localStream.getAudioTracks().forEach(track => {
                peerConnection.addTrack(track, localStream);
            });
        }

        if (asOffer) {
            peerConnection.createOffer()
                .then(offer => peerConnection.setLocalDescription(offer))
                .then(() => {
                    send({ type: 'offer', offer: peerConnection.localDescription, target: targetId, playerId });
                });
        }

        peerConnection.onconnectionstatechange = () => {
            emit('connection-state', peerConnection.connectionState);
        };
    }

    function setupDataChannel(channel) {
        channel.onopen = () => emit('data-channel-open');
        channel.onclose = () => emit('data-channel-close');
        channel.onmessage = (e) => {
            try {
                const msg = JSON.parse(e.data);
                if (msg.type === 'move') emit('remote-move', msg.move);
                else if (msg.type === 'chat') emit('chat-message', msg);
                else if (msg.type === 'draw-offer') emit('draw-offer', msg);
                else if (msg.type === 'resign') emit('resign', msg);
            } catch (err) {
                console.error('Data channel parse error:', err);
            }
        };
    }

    async function handleOffer(msg) {
        if (!peerConnection) initPeerConnection(false, msg.playerId);
        await peerConnection.setRemoteDescription(new RTCSessionDescription(msg.offer));
        const answer = await peerConnection.createAnswer();
        await peerConnection.setLocalDescription(answer);
        send({ type: 'answer', answer: peerConnection.localDescription, target: msg.playerId, playerId });
    }

    async function handleAnswer(msg) {
        await peerConnection.setRemoteDescription(new RTCSessionDescription(msg.answer));
    }

    async function handleIceCandidate(msg) {
        if (peerConnection && msg.candidate) {
            await peerConnection.addIceCandidate(new RTCIceCandidate(msg.candidate));
        }
    }

    // Voice Chat
    async function enableVoiceChat(voicePreset = 'off') {
        try {
            if (localStream) {
                return localStream;
            }

            rawLocalStream = await navigator.mediaDevices.getUserMedia({ audio: true, video: false });
            localStream = rawLocalStream;

            // Apply voice changer if provided
            if (voicePreset && voicePreset !== 'off' && typeof ChessVoiceChanger !== 'undefined' && ChessVoiceChanger.applyToStream) {
                localStream = ChessVoiceChanger.applyToStream(rawLocalStream, voicePreset);
            }

            if (peerConnection) {
                localStream.getAudioTracks().forEach(track => {
                    peerConnection.addTrack(track, localStream);
                });
            }

            emit('voice-enabled', { stream: localStream, preset: voicePreset || 'off' });
            return localStream;
        } catch (err) {
            console.error('Voice chat error:', err);
            emit('voice-error', err);
            return null;
        }
    }

    function disableVoiceChat() {
        if (peerConnection) {
            peerConnection.getSenders()
                .filter(sender => sender.track && sender.track.kind === 'audio')
                .forEach(sender => {
                    try { peerConnection.removeTrack(sender); } catch (err) {}
                });
        }

        if (localStream) {
            localStream.getTracks().forEach(track => track.stop());
        }
        if (rawLocalStream && rawLocalStream !== localStream) {
            rawLocalStream.getTracks().forEach(track => track.stop());
        }

        if (typeof ChessVoiceChanger !== 'undefined' && ChessVoiceChanger.cleanup) {
            ChessVoiceChanger.cleanup();
        }

        localStream = null;
        rawLocalStream = null;
        emit('voice-disabled');
    }

    function muteVoice(muted) {
        if (localStream) {
            localStream.getAudioTracks().forEach(t => { t.enabled = !muted; });
        }
    }

    // Game Actions (via data channel for low latency, WS fallback)
    function sendMove(move) {
        const msg = { type: 'move', move, playerId, timestamp: Date.now() };

        if (dataChannel && dataChannel.readyState === 'open') {
            dataChannel.send(JSON.stringify(msg));
        } else {
            send({ ...msg, type: 'game-move', roomId: currentRoom });
        }
    }

    function sendChat(message) {
        const msg = { type: 'chat', message, playerId, timestamp: Date.now() };
        if (dataChannel && dataChannel.readyState === 'open') {
            dataChannel.send(JSON.stringify(msg));
        }
        send({ ...msg, roomId: currentRoom });
    }

    function offerDraw() {
        const msg = { type: 'draw-offer', playerId };
        if (dataChannel && dataChannel.readyState === 'open') {
            dataChannel.send(JSON.stringify(msg));
        }
        send({ ...msg, roomId: currentRoom });
    }

    function resign() {
        send({ type: 'resign', playerId, roomId: currentRoom });
    }

    function generateId() {
        return 'p_' + Date.now().toString(36) + Math.random().toString(36).substr(2, 9);
    }

    function disconnect() {
        if (dataChannel) dataChannel.close();
        if (peerConnection) peerConnection.close();
        if (localStream) localStream.getTracks().forEach(t => t.stop());
        if (rawLocalStream && rawLocalStream !== localStream) rawLocalStream.getTracks().forEach(t => t.stop());
        if (ws) ws.close();
        currentRoom = null;
        peerConnection = null;
        dataChannel = null;
        localStream = null;
        rawLocalStream = null;
    }

    return {
        connect,
        createRoom,
        joinRoom,
        spectateRoom,
        listRooms,
        enableVoiceChat,
        disableVoiceChat,
        muteVoice,
        sendMove,
        sendChat,
        offerDraw,
        resign,
        disconnect,
        on,
        get playerId() { return playerId; },
        get roomId() { return currentRoom; },
        get isHost() { return isHost; },
        get isConnected() { return ws && ws.readyState === WebSocket.OPEN; },
        get isPeerConnected() { return peerConnection && peerConnection.connectionState === 'connected'; },
    };
})();
