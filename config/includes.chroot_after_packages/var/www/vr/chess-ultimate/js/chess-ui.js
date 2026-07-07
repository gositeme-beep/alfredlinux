/* ═══════════════════════════════════════════════════════════════
   CHESS ULTIMATE — Game UI / HUD Module
   Agents: Interface, Dashboard, Panel, Layout (UI Division)
   
   Complete game UI including:
   - Move history with navigation
   - Player clocks
   - Captured pieces display
   - Analysis panel with eval bar
   - Chat panel
   - Voice changer controls
   - Settings panel
   - Tournament bracket
   - Coaching hints
   ═══════════════════════════════════════════════════════════════ */

const ChessUI = (() => {
    let moveList = [];
    let clockIntervals = {};
    let currentPanel = 'moves';

    const PIECE_UNICODE = {
        wk: '♔', wq: '♕', wr: '♖', wb: '♗', wn: '♘', wp: '♙',
        bk: '♚', bq: '♛', br: '♜', bb: '♝', bn: '♞', bp: '♟',
    };

    function init() {
        createHUD();
        createPanels();
        bindPanelSwitcher();
    }

    function createHUD() {
        const hud = document.getElementById('game-hud');
        if (!hud) return;

        hud.innerHTML = `
            <div class="hud-top">
                <div class="player-bar opponent">
                    <div class="player-avatar" id="opponent-avatar">♚</div>
                    <div class="player-info">
                        <span class="player-name" id="opponent-name">Opponent</span>
                        <span class="player-elo" id="opponent-elo">1200</span>
                    </div>
                    <div class="captured-pieces" id="opponent-captured"></div>
                    <div class="player-clock" id="opponent-clock">10:00</div>
                </div>
            </div>
            <div class="hud-bottom">
                <div class="player-bar self">
                    <div class="player-avatar" id="self-avatar">♔</div>
                    <div class="player-info">
                        <span class="player-name" id="self-name">You</span>
                        <span class="player-elo" id="self-elo">1200</span>
                    </div>
                    <div class="captured-pieces" id="self-captured"></div>
                    <div class="player-clock" id="self-clock">10:00</div>
                </div>
            </div>
            <div class="hud-center">
                <div class="eval-bar-container">
                    <div class="eval-bar" id="eval-bar">
                        <div class="eval-fill" id="eval-fill"></div>
                    </div>
                    <span class="eval-value" id="eval-value">0.0</span>
                </div>
            </div>
            <div class="hud-left">
                <div class="game-controls">
                    <button class="ctrl-btn" id="btn-flip" title="Flip Board"><i class="fas fa-sync-alt"></i></button>
                    <button class="ctrl-btn" id="btn-undo" title="Undo Move"><i class="fas fa-undo"></i></button>
                    <button class="ctrl-btn" id="btn-hint" title="Get Hint"><i class="fas fa-lightbulb"></i></button>
                    <button class="ctrl-btn" id="btn-analyze" title="Analyze"><i class="fas fa-search"></i></button>
                    <button class="ctrl-btn" id="btn-fullscreen" title="Fullscreen"><i class="fas fa-expand"></i></button>
                    <button class="ctrl-btn" id="btn-vr" title="Enter VR"><i class="fas fa-vr-cardboard"></i></button>
                </div>
            </div>
        `;
    }

    function createPanels() {
        const panel = document.getElementById('side-panel');
        if (!panel) return;

        panel.innerHTML = `
            <div class="panel-tabs">
                <button class="panel-tab active" data-panel="moves">♟ Moves</button>
                <button class="panel-tab" data-panel="chat">💬 Chat</button>
                <button class="panel-tab" data-panel="analysis">📊 Analysis</button>
                <button class="panel-tab" data-panel="settings">⚙ Settings</button>
            </div>
            <div class="panel-content" id="panel-content">
                <div class="panel-section active" id="panel-moves">
                    <div class="opening-name" id="opening-name"></div>
                    <div class="move-list" id="move-list"></div>
                    <div class="move-nav">
                        <button class="nav-btn" id="nav-start" title="Start">⏮</button>
                        <button class="nav-btn" id="nav-prev" title="Previous">◀</button>
                        <button class="nav-btn" id="nav-next" title="Next">▶</button>
                        <button class="nav-btn" id="nav-end" title="End">⏭</button>
                    </div>
                </div>
                <div class="panel-section" id="panel-chat">
                    <div class="chat-messages" id="chat-messages"></div>
                    <div class="chat-input-row">
                        <input type="text" id="chat-input" placeholder="Type a message..." maxlength="200">
                        <button class="chat-send" id="chat-send">➤</button>
                    </div>
                    <div class="voice-controls" id="voice-controls">
                        <button class="voice-btn" id="btn-voice-toggle" title="Toggle Voice"><i class="fas fa-microphone"></i></button>
                        <button class="voice-btn" id="btn-voice-mute" title="Mute"><i class="fas fa-volume-mute"></i></button>
                        <select id="voice-changer-select" class="voice-select">
                            <option value="off">Human Voice</option>
                            <option value="deep">Deep</option>
                            <option value="high">High</option>
                            <option value="robotic">Robotic</option>
                            <option value="whisper">Whisper</option>
                            <option value="echo">Echo</option>
                            <option value="alien">Alien</option>
                        </select>
                    </div>
                </div>
                <div class="panel-section" id="panel-analysis">
                    <div class="analysis-eval">
                        <div class="eval-label">Evaluation</div>
                        <div class="eval-big" id="analysis-eval-value">0.0</div>
                    </div>
                    <div class="analysis-depth">
                        <span>Depth: </span><span id="analysis-depth">0</span>
                    </div>
                    <div class="analysis-pv" id="analysis-pv"></div>
                    <div class="analysis-controls">
                        <button class="ctrl-btn" id="btn-analyze-toggle">Start Analysis</button>
                    </div>
                    <div class="coaching-hints" id="coaching-hints"></div>
                </div>
                <div class="panel-section" id="panel-settings">
                    <div class="settings-group">
                        <label>Board Theme</label>
                        <select id="setting-theme" class="setting-select"></select>
                    </div>
                    <div class="settings-group">
                        <label>Sound</label>
                        <input type="range" id="setting-volume" min="0" max="100" value="80" class="setting-range">
                    </div>
                    <div class="settings-group">
                        <label>Show Coordinates</label>
                        <input type="checkbox" id="setting-coords" checked class="setting-check">
                    </div>
                    <div class="settings-group">
                        <label>Show Legal Moves</label>
                        <input type="checkbox" id="setting-legal" checked class="setting-check">
                    </div>
                    <div class="settings-group">
                        <label>Auto-Queen</label>
                        <input type="checkbox" id="setting-autoqueen" checked class="setting-check">
                    </div>
                    <div class="settings-group">
                        <label>Animation Speed</label>
                        <select id="setting-animation" class="setting-select">
                            <option value="fast">Fast</option>
                            <option value="normal" selected>Normal</option>
                            <option value="slow">Slow</option>
                        </select>
                    </div>
                    <div class="settings-group">
                        <label>Camera View</label>
                        <div class="camera-presets">
                            <button class="preset-btn" data-view="white">White</button>
                            <button class="preset-btn" data-view="black">Black</button>
                            <button class="preset-btn" data-view="top">Top</button>
                            <button class="preset-btn" data-view="side">Side</button>
                            <button class="preset-btn" data-view="cinematic">Cinema</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Populate theme selector
        const themeSelect = document.getElementById('setting-theme');
        if (themeSelect && typeof ChessRenderer !== 'undefined') {
            Object.entries(ChessRenderer.themes).forEach(([key, theme]) => {
                const opt = document.createElement('option');
                opt.value = key;
                opt.textContent = theme.name;
                if (key === 'classic') opt.selected = true;
                themeSelect.appendChild(opt);
            });
        }
    }

    function bindPanelSwitcher() {
        document.querySelectorAll('.panel-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.panel-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.panel-section').forEach(s => s.classList.remove('active'));
                tab.classList.add('active');
                const target = document.getElementById(`panel-${tab.dataset.panel}`);
                if (target) target.classList.add('active');
                currentPanel = tab.dataset.panel;
            });
        });
    }

    // Move List
    function addMove(moveData) {
        moveList.push(moveData);
        renderMoveList();
    }

    function renderMoveList() {
        const el = document.getElementById('move-list');
        if (!el) return;

        let html = '';
        for (let i = 0; i < moveList.length; i += 2) {
            const num = Math.floor(i / 2) + 1;
            const white = moveList[i];
            const black = moveList[i + 1];
            html += `<div class="move-row">
                <span class="move-num">${num}.</span>
                <span class="move-white ${i === moveList.length - 1 ? 'last-move' : ''}" data-idx="${i}">${white.san}</span>
                ${black ? `<span class="move-black ${i + 1 === moveList.length - 1 ? 'last-move' : ''}" data-idx="${i + 1}">${black.san}</span>` : ''}
            </div>`;
        }
        el.innerHTML = html;
        el.scrollTop = el.scrollHeight;
    }

    function clearMoves() {
        moveList = [];
        const el = document.getElementById('move-list');
        if (el) el.innerHTML = '';
    }

    // Clocks
    function startClock(color, timeMs) {
        stopClock('w');
        stopClock('b');

        const elId = color === 'w' ? 'self-clock' : 'opponent-clock';
        let remaining = timeMs;

        clockIntervals[color] = setInterval(() => {
            remaining -= 100;
            if (remaining <= 0) {
                remaining = 0;
                stopClock(color);
                // Emit timeout event would go here
            }
            updateClockDisplay(elId, remaining);
        }, 100);
    }

    function stopClock(color) {
        if (clockIntervals[color]) {
            clearInterval(clockIntervals[color]);
            delete clockIntervals[color];
        }
    }

    function updateClockDisplay(elId, ms) {
        const el = document.getElementById(elId);
        if (!el) return;
        const totalSec = Math.ceil(ms / 1000);
        const min = Math.floor(totalSec / 60);
        const sec = totalSec % 60;
        el.textContent = `${min}:${sec.toString().padStart(2, '0')}`;
        el.classList.toggle('clock-low', totalSec < 30);
        el.classList.toggle('clock-critical', totalSec < 10);
    }

    function setClockTime(color, ms) {
        const elId = color === 'w' ? 'self-clock' : 'opponent-clock';
        updateClockDisplay(elId, ms);
    }

    // Player Info
    function setPlayerInfo(side, name, elo, avatar) {
        const prefix = side === 'self' ? 'self' : 'opponent';
        const nameEl = document.getElementById(`${prefix}-name`);
        const eloEl = document.getElementById(`${prefix}-elo`);
        const avatarEl = document.getElementById(`${prefix}-avatar`);
        if (nameEl) nameEl.textContent = name;
        if (eloEl) eloEl.textContent = elo;
        if (avatarEl) avatarEl.textContent = avatar || (side === 'self' ? '♔' : '♚');
    }

    // Captured Pieces
    function updateCaptured(capturedByWhite, capturedByBlack) {
        const selfEl = document.getElementById('self-captured');
        const oppEl = document.getElementById('opponent-captured');
        if (selfEl) selfEl.innerHTML = capturedByWhite.map(p => `<span class="captured-piece">${PIECE_UNICODE['b' + p]}</span>`).join('');
        if (oppEl) oppEl.innerHTML = capturedByBlack.map(p => `<span class="captured-piece">${PIECE_UNICODE['w' + p]}</span>`).join('');
    }

    // Evaluation Bar
    function updateEval(evalValue) {
        const fill = document.getElementById('eval-fill');
        const valueEl = document.getElementById('eval-value');
        const analysisEl = document.getElementById('analysis-eval-value');

        if (fill) {
            const pct = Math.max(5, Math.min(95, 50 - evalValue * 5));
            fill.style.height = pct + '%';
            fill.style.background = evalValue > 0 ? '#fff' : '#333';
        }
        if (valueEl) {
            valueEl.textContent = typeof evalValue === 'number' ?
                (evalValue > 0 ? '+' : '') + evalValue.toFixed(1) : evalValue;
        }
        if (analysisEl) {
            analysisEl.textContent = typeof evalValue === 'number' ?
                (evalValue > 0 ? '+' : '') + evalValue.toFixed(2) : evalValue;
        }
    }

    // Analysis
    function updateAnalysis(info) {
        const depthEl = document.getElementById('analysis-depth');
        const pvEl = document.getElementById('analysis-pv');
        if (depthEl) depthEl.textContent = info.depth || 0;
        if (pvEl) pvEl.textContent = info.pv || '';
        if (info.eval !== undefined) updateEval(info.eval);
        if (info.mate !== undefined) {
            updateEval(info.mate > 0 ? `M${info.mate}` : `M${info.mate}`);
        }
    }

    // Chat
    function addChatMessage(sender, message, isSystem = false) {
        const container = document.getElementById('chat-messages');
        if (!container) return;
        const div = document.createElement('div');
        div.className = `chat-msg ${isSystem ? 'system' : sender === 'self' ? 'self' : 'other'}`;
        const senderSpan = document.createElement('span');
        senderSpan.className = 'chat-sender';
        senderSpan.textContent = sender;
        const textSpan = document.createElement('span');
        textSpan.className = 'chat-text';
        textSpan.textContent = message;
        div.appendChild(senderSpan);
        div.appendChild(textSpan);
        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
    }

    // Opening Name
    function setOpening(opening) {
        const el = document.getElementById('opening-name');
        if (el) el.textContent = opening ? `${opening.eco}: ${opening.name}` : '';
    }

    // Coaching Hints
    function showHint(hint) {
        const el = document.getElementById('coaching-hints');
        if (!el) return;
        el.innerHTML = `
            <div class="hint-card">
                <div class="hint-icon">💡</div>
                <div class="hint-text">${sanitizeHTML(hint.text)}</div>
                ${hint.move ? `<div class="hint-move">Suggested: <strong>${hint.move}</strong></div>` : ''}
            </div>
        `;
    }

    // Game Over Modal
    function showGameOver(result) {
        const overlay = document.createElement('div');
        overlay.className = 'game-over-overlay';
        overlay.innerHTML = `
            <div class="game-over-modal">
                <div class="game-over-icon">${result.winner === 'draw' ? '🤝' : '🏆'}</div>
                <h2 class="game-over-title">${result.title || 'Game Over'}</h2>
                <p class="game-over-reason">${sanitizeHTML(result.reason || '')}</p>
                ${result.eloChange ? `<p class="game-over-elo">ELO: ${result.eloChange > 0 ? '+' : ''}${result.eloChange}</p>` : ''}
                <div class="game-over-buttons">
                    <button class="go-btn" id="btn-rematch">Rematch</button>
                    <button class="go-btn" id="btn-new-game">New Game</button>
                    <button class="go-btn secondary" id="btn-analyze-game">Analyze</button>
                </div>
            </div>
        `;
        document.body.appendChild(overlay);
        requestAnimationFrame(() => overlay.classList.add('visible'));

        return {
            el: overlay,
            close: () => { overlay.classList.remove('visible'); setTimeout(() => overlay.remove(), 300); },
        };
    }

    // Promotion Dialog
    function showPromotion(color) {
        return new Promise(resolve => {
            const pieces = ['q', 'r', 'b', 'n'];
            const overlay = document.createElement('div');
            overlay.className = 'promotion-overlay';
            overlay.innerHTML = `
                <div class="promotion-dialog">
                    <h3>Promote Pawn</h3>
                    <div class="promotion-pieces">
                        ${pieces.map(p => `<button class="promo-btn" data-piece="${p}">${PIECE_UNICODE[color + p]}</button>`).join('')}
                    </div>
                </div>
            `;
            document.body.appendChild(overlay);
            requestAnimationFrame(() => overlay.classList.add('visible'));

            overlay.querySelectorAll('.promo-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    overlay.remove();
                    resolve(btn.dataset.piece);
                });
            });
        });
    }

    function sanitizeHTML(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function destroy() {
        stopClock('w');
        stopClock('b');
    }

    return {
        init,
        addMove,
        clearMoves,
        startClock,
        stopClock,
        setClockTime,
        setPlayerInfo,
        updateCaptured,
        updateEval,
        updateAnalysis,
        addChatMessage,
        setOpening,
        showHint,
        showGameOver,
        showPromotion,
        destroy,
        get moveList() { return moveList; },
    };
})();
