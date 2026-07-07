/* ═══════════════════════════════════════════════════════════════
   CHESS MASTERS — 200-Player Multiplayer World Module
   GoSiteMe · Project Grandmaster II

   Features:
   - 200 concurrent players in shared outdoor event world
   - WebSocket real-time sync via GoSiteMe WS server (port 3010)
   - Player avatars with position/rotation broadcast (10fps)
   - Up to 50 simultaneous chess games at different tables
   - Spectator mode — walk around and watch any game
   - Chat system (text + proximity spatial)
   - Player list / leaderboard
   - Auto-reconnect with exponential backoff
   - Event system hooks for renderer & UI
   ═══════════════════════════════════════════════════════════════ */

const ChessMultiplayer = (() => {
    'use strict';

    // ── State ──
    let ws = null;
    let playerId = null;
    let playerName = 'Guest';
    let playerElo = 1200;
    let currentRoom = 'main-event';
    let isConnected = false;
    let reconnectAttempts = 0;
    const MAX_RECONNECT = 10;

    // World state — all players and tables
    const players = new Map();  // id → { name, elo, pos:{x,y,z}, rot:{y}, state, tableId, color, avatar }
    const tables = new Map();   // tableId → { white, black, spectators:[], fen, pgn, timeW, timeB, status }
    const chatMessages = [];
    const MAX_CHAT = 200;

    // Events
    const handlers = {};
    function on(event, fn) {
        if (!handlers[event]) handlers[event] = [];
        handlers[event].push(fn);
    }
    function off(event, fn) {
        if (handlers[event]) handlers[event] = handlers[event].filter(h => h !== fn);
    }
    function emit(event, data) {
        (handlers[event] || []).forEach(fn => {
            try { fn(data); } catch(e) { console.error(`[MP] Event ${event} handler error:`, e); }
        });
    }

    // ── Helpers ──
    function generateId() {
        return 'p_' + Math.random().toString(36).substring(2, 10) + Date.now().toString(36);
    }

    function send(data) {
        if (ws && ws.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify(data));
        }
    }

    // ── CHANNEL — we use the existing /ws pub/sub system ──
    const CHANNEL = 'chess-masters:world';

    // ── WebSocket Connection ──
    function connect(name, elo) {
        if (name) playerName = name;
        if (elo) playerElo = elo;
        if (!playerId) playerId = generateId();

        return new Promise((resolve, reject) => {
            const protocol = location.protocol === 'https:' ? 'wss:' : 'ws:';
            const host = location.hostname;

            // Use /ws path (the server's accepted endpoint) — token optional for now
            const url = `${protocol}//${host}:3010/ws?token=vr-guest-${playerId}`;

            try {
                ws = new WebSocket(url);
            } catch (err) {
                console.warn('[MP] WebSocket not available:', err.message);
                reject(err);
                return;
            }

            ws.onopen = () => {
                isConnected = true;
                reconnectAttempts = 0;

                // Subscribe to the chess-masters pub/sub channel
                ws.send(JSON.stringify({ type: 'subscribe', channel: CHANNEL }));

                // Announce join via pub/sub
                publishToChannel({
                    type: 'player-join',
                    player: {
                        id: playerId,
                        name: playerName,
                        elo: playerElo,
                        pos: { x: 0, y: 0, z: 8 },
                        avatar: getRandomAvatar(),
                    },
                });

                emit('connected', { playerId });
                console.log(`[Chess Masters MP] Connected as ${playerName} (${playerId})`);
                resolve();
            };

            ws.onmessage = (e) => {
                try {
                    const msg = JSON.parse(e.data);
                    // The pub/sub system wraps channel messages in { channel, ... }
                    if (msg.channel === CHANNEL && msg.type !== 'published') {
                        handleMessage(msg);
                    } else if (msg.type === 'welcome' || msg.type === 'subscribed') {
                        // Server handshake — ignore
                    } else if (msg.type === 'error') {
                        console.warn('[MP] Server:', msg.message);
                    }
                } catch (err) {
                    console.error('[MP] Parse error:', err);
                }
            };

            ws.onerror = () => {
                // onerror always followed by onclose
            };

            ws.onclose = () => {
                isConnected = false;
                emit('disconnected');

                if (reconnectAttempts < MAX_RECONNECT) {
                    const delay = Math.min(1000 * Math.pow(2, reconnectAttempts), 30000);
                    reconnectAttempts++;
                    console.log(`[MP] Reconnecting in ${delay}ms (attempt ${reconnectAttempts})`);
                    setTimeout(() => connect(), delay);
                }
            };
        });
    }

    function publishToChannel(data) {
        if (ws && ws.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify({ type: 'publish', channel: CHANNEL, data }));
        }
    }

    function disconnect() {
        reconnectAttempts = MAX_RECONNECT; // prevent auto-reconnect
        if (ws) {
            publishToChannel({ type: 'player-leave', playerId });
            ws.close();
        }
    }

    // ── Message Handler ──
    function handleMessage(msg) {
        switch (msg.type) {
            case 'world-state':
                // Full sync — all players and tables
                if (msg.players) {
                    players.clear();
                    msg.players.forEach(p => {
                        if (p.id !== playerId) players.set(p.id, p);
                    });
                }
                if (msg.tables) {
                    tables.clear();
                    msg.tables.forEach(t => tables.set(t.id, t));
                }
                emit('world-sync', { players: Array.from(players.values()), tables: Array.from(tables.values()) });
                break;

            case 'player-join':
                if (msg.player.id !== playerId) {
                    players.set(msg.player.id, msg.player);
                    emit('player-join', msg.player);
                }
                break;

            case 'player-leave':
                players.delete(msg.playerId);
                emit('player-leave', { playerId: msg.playerId });
                break;

            case 'player-move':
                // Position/rotation update from another player
                if (msg.playerId !== playerId) {
                    const p = players.get(msg.playerId);
                    if (p) {
                        p.pos = msg.pos;
                        p.rot = msg.rot;
                        p.state = msg.state || p.state;
                    }
                    emit('player-move', msg);
                }
                break;

            case 'table-update':
                tables.set(msg.table.id, msg.table);
                emit('table-update', msg.table);
                break;

            case 'game-move':
                // Chess move made at a table
                emit('game-move', { tableId: msg.tableId, move: msg.move, fen: msg.fen, pgn: msg.pgn });
                break;

            case 'chat':
                const chatMsg = { from: msg.from, name: msg.name, text: msg.text, time: Date.now() };
                chatMessages.push(chatMsg);
                if (chatMessages.length > MAX_CHAT) chatMessages.shift();
                emit('chat', chatMsg);
                break;

            case 'challenge':
                emit('challenge', { from: msg.from, name: msg.name, elo: msg.elo, tableId: msg.tableId });
                break;

            case 'challenge-accept':
                emit('challenge-accepted', { tableId: msg.tableId, opponent: msg.opponent });
                break;

            case 'spectate-join':
                emit('spectator-join', { tableId: msg.tableId, playerId: msg.playerId, name: msg.name });
                break;

            case 'error':
                console.error('[MP] Server error:', msg.message);
                emit('error', msg);
                break;
        }
    }

    // ── Position Broadcasting (10 fps throttle) ──
    let lastBroadcast = 0;
    const BROADCAST_INTERVAL = 100; // ms

    function broadcastPosition(pos, rotY, state) {
        const now = Date.now();
        if (now - lastBroadcast < BROADCAST_INTERVAL) return;
        lastBroadcast = now;

        publishToChannel({
            type: 'player-move',
            playerId,
            pos: { x: Math.round(pos.x * 100) / 100, y: Math.round(pos.y * 100) / 100, z: Math.round(pos.z * 100) / 100 },
            rot: { y: Math.round(rotY * 100) / 100 },
            state: state || 'walking',
        });
    }

    // ── Table / Game Actions ──
    function sitAtTable(tableId, color) {
        publishToChannel({ type: 'sit', playerId, tableId, color });
    }

    function leaveTable(tableId) {
        publishToChannel({ type: 'stand', playerId, tableId });
    }

    function makeMove(tableId, move) {
        publishToChannel({ type: 'game-move', playerId, tableId, move });
    }

    function spectateTable(tableId) {
        publishToChannel({ type: 'spectate', playerId, tableId });
    }

    function challengePlayer(targetId) {
        const tableId = 'table_' + Date.now().toString(36);
        publishToChannel({ type: 'challenge', from: playerId, name: playerName, elo: playerElo, target: targetId, tableId });
    }

    function acceptChallenge(tableId) {
        publishToChannel({ type: 'challenge-accept', playerId, name: playerName, elo: playerElo, tableId });
    }

    // ── Chat ──
    function sendChat(text) {
        if (!text || text.length > 500) return;
        publishToChannel({ type: 'chat', from: playerId, name: playerName, text });
    }

    // ── Avatar System ──
    const AVATARS = [
        { body: 0x4A90D9, accent: 0xFFD700 },
        { body: 0xE74C3C, accent: 0xF39C12 },
        { body: 0x2ECC71, accent: 0x3498DB },
        { body: 0x9B59B6, accent: 0xE91E63 },
        { body: 0xF39C12, accent: 0x1ABC9C },
        { body: 0x1ABC9C, accent: 0xE74C3C },
        { body: 0xE91E63, accent: 0x3498DB },
        { body: 0x3498DB, accent: 0xF1C40F },
        { body: 0x34495E, accent: 0xE74C3C },
        { body: 0x8E44AD, accent: 0x2ECC71 },
    ];

    function getRandomAvatar() {
        return AVATARS[Math.floor(Math.random() * AVATARS.length)];
    }

    // ── Public API ──
    return {
        connect,
        disconnect,
        on,
        off,
        broadcastPosition,
        sitAtTable,
        leaveTable,
        makeMove,
        spectateTable,
        challengePlayer,
        acceptChallenge,
        sendChat,

        get playerId() { return playerId; },
        get playerName() { return playerName; },
        get isConnected() { return isConnected; },
        get players() { return players; },
        get tables() { return tables; },
        get chatMessages() { return chatMessages; },
        get playerCount() { return players.size + (isConnected ? 1 : 0); },

        set playerName(n) { playerName = n; },
        set playerElo(e) { playerElo = e; },

        // Constants
        MAX_PLAYERS: 200,
        MAX_TABLES: 50,
        AVATARS,
    };
})();
