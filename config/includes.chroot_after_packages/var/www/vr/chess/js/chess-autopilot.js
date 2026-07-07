/**
 * Chess Arena Autopilot Tournament
 * 
 * Runs a 3-agent tournament with 2 commentator agents providing live analysis.
 * Launched via ?autopilot=1 on the chess page.
 * 
 * Players:  Cipher (strongest), Alfred (balanced), Nova (deep thinker)
 * Commentators: Sage (analysis), Atlas (hype)
 * Spectator: You (the user)
 */
(function() {
    'use strict';

    // ── Tournament Config ──────────────────────────────────
    const PLAYERS = [
        { name: 'Cipher',  idx: 4 },   // AGENTS[4] — ELO 1500
        { name: 'Alfred',  idx: 0 },   // AGENTS[0] — ELO 1400
        { name: 'Nova',    idx: 1 },   // AGENTS[1] — ELO 1350
    ];

    const COMMENTATORS = [
        { name: 'Sage',  agent: 'sage',  style: 'analytical', color: '#22c55e' },
        { name: 'Atlas', agent: 'atlas', style: 'enthusiastic', color: '#f59e0b' },
    ];

    let bracket = [];           // [{white, black, winner, moves}]
    let currentRound = 0;
    let commentaryQueue = [];
    let commentaryActive = false;
    let commentaryConvId = null;
    let movesSinceComment = 0;
    let tournOverlay = null;
    let commentPanel = null;
    let gameOverResolver = null;
    let originalCheckGameEnd = null;

    // ── Accessors for chess engine state (let-scoped in main script) ──
    function cs() { return window.chessState; }   // { chess, AGENTS, whiteAgent, blackAgent, moveHistory, gameMode, isThinking }

    // ── Wait for chess page to fully load ──────────────────
    function waitForChess() {
        return new Promise(resolve => {
            const check = () => {
                // Only need chessState getter & startGame fn — chess instance
                // is created BY startGame, not before it
                if (window.chessState && window.chessState.AGENTS &&
                    typeof window.startGame === 'function' &&
                    typeof window._setAgents === 'function') {
                    resolve();
                } else {
                    setTimeout(check, 200);
                }
            };
            check();
        });
    }

    // ── Build Tournament UI ────────────────────────────────
    function buildUI() {
        // Tournament bracket overlay (top-right)
        tournOverlay = document.createElement('div');
        tournOverlay.id = 'autopilotBracket';
        tournOverlay.innerHTML = `
            <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:2px;color:rgba(255,255,255,.4);margin-bottom:8px">
                AUTOPILOT TOURNAMENT
            </div>
            <div id="bracketRounds" style="display:flex;flex-direction:column;gap:6px"></div>
            <div id="bracketStatus" style="margin-top:8px;font-size:.8rem;color:#f59e0b"></div>
        `;
        Object.assign(tournOverlay.style, {
            position: 'fixed', top: '80px', right: '16px', zIndex: '500',
            background: 'rgba(10,10,20,.92)', border: '1px solid rgba(255,255,255,.1)',
            borderRadius: '12px', padding: '14px 18px', minWidth: '220px',
            backdropFilter: 'blur(12px)', fontFamily: 'Inter,system-ui,sans-serif',
            boxShadow: '0 8px 32px rgba(0,0,0,.5)'
        });
        document.body.appendChild(tournOverlay);

        // Commentary panel (bottom-center)
        commentPanel = document.createElement('div');
        commentPanel.id = 'autopilotCommentary';
        commentPanel.innerHTML = `
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
                <span style="font-size:.7rem;text-transform:uppercase;letter-spacing:2px;color:rgba(255,255,255,.4)">LIVE COMMENTARY</span>
                <span id="commentLive" style="width:6px;height:6px;border-radius:50%;background:#ef4444;animation:pulse-dot 1.5s infinite"></span>
            </div>
            <div id="commentaryLog" style="max-height:180px;overflow-y:auto;display:flex;flex-direction:column;gap:6px"></div>
        `;
        Object.assign(commentPanel.style, {
            position: 'fixed', bottom: '16px', left: '50%', transform: 'translateX(-50%)',
            zIndex: '500', background: 'rgba(10,10,20,.92)', border: '1px solid rgba(255,255,255,.1)',
            borderRadius: '12px', padding: '14px 18px', width: '520px', maxWidth: '90vw',
            backdropFilter: 'blur(12px)', fontFamily: 'Inter,system-ui,sans-serif',
            boxShadow: '0 8px 32px rgba(0,0,0,.5)'
        });

        // Pulse animation for live dot
        const style = document.createElement('style');
        style.textContent = `
            @keyframes pulse-dot { 0%,100%{opacity:1} 50%{opacity:.3} }
            #commentaryLog::-webkit-scrollbar { width: 4px; }
            #commentaryLog::-webkit-scrollbar-thumb { background: rgba(255,255,255,.15); border-radius: 2px; }
            .comment-bubble { padding:8px 12px; border-radius:10px; font-size:.82rem; line-height:1.4; animation: fadeInUp .3s ease; }
            @keyframes fadeInUp { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }
            #autopilotBracket .match-row { display:flex; align-items:center; gap:8px; padding:4px 8px; border-radius:6px; font-size:.82rem; }
            #autopilotBracket .match-row.active { background:rgba(0,168,255,.1); border:1px solid rgba(0,168,255,.3); }
            #autopilotBracket .match-row.done { opacity:.6; }
            #autopilotBracket .vs { color:rgba(255,255,255,.3); font-size:.7rem; }
            #autopilotBracket .winner-badge { color:#22c55e; font-weight:700; }
        `;
        document.body.appendChild(style);
        document.body.appendChild(commentPanel);
    }

    function updateBracket() {
        const container = document.getElementById('bracketRounds');
        if (!container) return;

        let html = '';
        const labels = ['Round 1', 'Final'];
        bracket.forEach((match, i) => {
            const active = i === currentRound && !match.winner;
            const done = !!match.winner;
            html += `
                <div class="match-row ${active ? 'active' : ''} ${done ? 'done' : ''}">
                    <span style="color:rgba(255,255,255,.3);font-size:.65rem;min-width:42px">${labels[i] || 'R'+(i+1)}</span>
                    <span style="color:${match.whiteColor || '#fff'}">${match.white}</span>
                    <span class="vs">vs</span>
                    <span style="color:${match.blackColor || '#fff'}">${match.black}</span>
                    ${done ? `<span class="winner-badge">👑 ${match.winner}</span>` : ''}
                    ${done && match.moves ? `<span style="color:rgba(255,255,255,.3);font-size:.7rem">(${match.moves} moves)</span>` : ''}
                </div>`;
        });
        container.innerHTML = html;
    }

    function setBracketStatus(text) {
        const el = document.getElementById('bracketStatus');
        if (el) el.textContent = text;
    }

    // ── Commentary System ──────────────────────────────────
    function addComment(agent, text) {
        const log = document.getElementById('commentaryLog');
        if (!log) return;
        const c = COMMENTATORS.find(c => c.name === agent) || COMMENTATORS[0];
        const bubble = document.createElement('div');
        bubble.className = 'comment-bubble';
        bubble.style.background = `${c.color}15`;
        bubble.style.borderLeft = `3px solid ${c.color}`;
        bubble.innerHTML = `<strong style="color:${c.color}">${agent}:</strong> <span style="color:rgba(255,255,255,.8)">${escapeHtml(text)}</span>`;
        log.appendChild(bubble);
        log.scrollTop = log.scrollHeight;

        // Keep max 20 comments
        while (log.children.length > 20) log.removeChild(log.firstChild);
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    async function getCommentary(commentator, prompt) {
        try {
            const r = await fetch('/api/alfred-chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({
                    message: prompt,
                    agent: commentator.agent,
                    context: 'chess tournament commentary',
                    page_url: '/vr/chess/?autopilot=1',
                    conv_id: commentaryConvId || '',
                    model: 'auto'
                })
            });
            const data = await r.json();
            if (data.conv_id) commentaryConvId = data.conv_id;
            return data.response || '';
        } catch(e) {
            console.warn('Commentary fetch failed:', e);
            return '';
        }
    }

    async function processCommentaryQueue() {
        if (commentaryActive || commentaryQueue.length === 0) return;
        commentaryActive = true;

        const item = commentaryQueue.shift();
        const text = await getCommentary(item.commentator, item.prompt);
        if (text) {
            // Truncate to 2 sentences max for snappy commentary
            const short = text.split(/[.!?]+\s/).slice(0, 2).join('. ').trim();
            addComment(item.commentator.name, short.endsWith('.') ? short : short + '.');
        }
        commentaryActive = false;

        // Process next after a small delay
        if (commentaryQueue.length > 0) {
            setTimeout(processCommentaryQueue, 1500);
        }
    }

    function queueComment(commentator, prompt) {
        // Limit queue size to avoid flooding
        if (commentaryQueue.length < 4) {
            commentaryQueue.push({ commentator, prompt });
            processCommentaryQueue();
        }
    }

    function triggerMoveCommentary(moveSan, agentName, color, moveNum) {
        movesSinceComment++;

        // Comment every 3-5 moves, or on captures/checks
        const isExciting = moveSan.includes('x') || moveSan.includes('+') || moveSan.includes('#');
        const shouldComment = isExciting || movesSinceComment >= 3;

        if (!shouldComment) return;
        movesSinceComment = 0;

        const matchInfo = bracket[currentRound];
        const fen = cs().chess.fen();
        const moveList = cs().moveHistory.map(m => m.move).join(' ');

        // Alternate commentators
        const commentator = COMMENTATORS[moveNum % 2];
        const otherName = COMMENTATORS[(moveNum + 1) % 2].name;

        let prompt;
        if (isExciting && moveSan.includes('#')) {
            prompt = `You are ${commentator.name}, a ${commentator.style} chess commentator watching ${matchInfo.white} vs ${matchInfo.black}. Checkmate just happened with ${moveSan} by ${agentName}! Give an excited 1-2 sentence reaction. Be ${commentator.style}. Don't use hashtags.`;
        } else if (isExciting) {
            prompt = `You are ${commentator.name}, a ${commentator.style} chess commentator. ${agentName} just played ${moveSan} in ${matchInfo.white} vs ${matchInfo.black}. Moves so far: ${moveList}. Give a brief ${commentator.style} 1-sentence comment on this move. Be concise.`;
        } else {
            prompt = `You are ${commentator.name}, a ${commentator.style} chess commentator. It's move ${moveNum} in ${matchInfo.white} vs ${matchInfo.black}. Recent moves: ${moveList.split(' ').slice(-6).join(' ')}. Current position FEN: ${fen}. Give a brief ${commentator.style} 1-sentence commentary. Don't list moves, give insight.`;
        }

        queueComment(commentator, prompt);
    }

    // ── Game Lifecycle Hooks ───────────────────────────────
    function hookIntoGame() {
        // Listen for chess-game-over custom event from chess page
        document.addEventListener('chess-game-over', (e) => {
            if (gameOverResolver) {
                gameOverResolver(e.detail.winner);
                gameOverResolver = null;
            }
        });
    }

    function waitForGameEnd() {
        return new Promise(resolve => {
            gameOverResolver = resolve;
        });
    }

    // ── Start a Match ──────────────────────────────────────
    async function runMatch(whiteIdx, blackIdx, roundNum) {
        currentRound = roundNum;

        // Set agents via accessor
        const agents = cs().AGENTS;
        window._setAgents(agents[whiteIdx], agents[blackIdx]);
        const wa = agents[whiteIdx];
        const ba = agents[blackIdx];

        bracket[roundNum] = {
            white: wa.name,
            black: ba.name,
            whiteColor: wa.color,
            blackColor: ba.color,
            winner: null,
            moves: 0
        };
        updateBracket();

        // Opening commentary
        addComment('Sage', `Welcome to Round ${roundNum + 1}! ${wa.name} (ELO ${wa.elo}) vs ${ba.name} (ELO ${ba.elo}). This should be interesting.`);

        await new Promise(r => setTimeout(r, 1000));

        addComment('Atlas', `${wa.name} has the white pieces — let's see what opening they go for! I'm on the edge of my seat!`);

        // Set speed to 2x for a good viewing pace
        setSpeed(2);

        // Apply a distinct theme per round
        const themes = ['obsidian', 'tournament', 'marble'];
        applyTheme(themes[roundNum] || 'obsidian');

        // Start the game engine
        startGame('autopilot');

        // Start move commentary polling + safety valve
        let lastMoveCount = 0;
        const MAX_MOVES = 200;
        const commentPoll = setInterval(() => {
            const mh = cs().moveHistory;
            if (mh.length > lastMoveCount) {
                const newMoves = mh.slice(lastMoveCount);
                newMoves.forEach(m => {
                    triggerMoveCommentary(m.move, m.agent, m.color, mh.indexOf(m) + 1);
                });
                lastMoveCount = mh.length;
            }
            // Safety valve: auto-draw after 200 moves
            if (mh.length >= MAX_MOVES && gameOverResolver) {
                gameOverResolver('draw');
                gameOverResolver = null;
            }
        }, 500);

        // Wait for game to finish
        const winner = await waitForGameEnd();

        clearInterval(commentPoll);

        const totalMoves = cs().moveHistory.length;
        bracket[roundNum].winner = winner === 'draw' ? 'Draw' : winner;
        bracket[roundNum].moves = totalMoves;
        updateBracket();

        // End-of-game commentary
        if (winner === 'draw') {
            addComment('Sage', `A hard-fought draw after ${totalMoves} moves. Both sides showed solid defensive play.`);
            addComment('Atlas', `Neither agent could break through! What a battle!`);
        } else {
            const loser = winner === wa.name ? ba.name : wa.name;
            queueComment(COMMENTATORS[0], `You are Sage, an analytical chess commentator. ${winner} just won by checkmate against ${loser} in ${totalMoves} moves. The game went: ${cs().moveHistory.map(m=>m.move).join(' ')}. Give a 2-sentence analytical summary of why the winner prevailed.`);
            queueComment(COMMENTATORS[1], `You are Atlas, an enthusiastic chess commentator. ${winner} just won a tournament game! Give a 1-sentence excited congratulations.`);
        }

        // Wait for commentary to flush
        await new Promise(r => setTimeout(r, 4000));

        return winner;
    }

    // ── Tournament Runner ──────────────────────────────────
    async function runTournament() {
        await waitForChess();

        // Brief pause for scene to settle
        await new Promise(r => setTimeout(r, 1000));

        buildUI();
        hookIntoGame();

        setBracketStatus('Tournament starting...');
        addComment('Sage', 'Good evening, everyone. Welcome to the GoSiteMe AI Chess Tournament.');
        addComment('Atlas', 'Three of our finest agents are about to battle it out! Cipher, Alfred, and Nova — who will be crowned champion?');

        await new Promise(r => setTimeout(r, 3000));

        // ── Round 1: Cipher vs Alfred ──
        setBracketStatus('Round 1 in progress...');
        const r1Winner = await runMatch(PLAYERS[0].idx, PLAYERS[1].idx, 0);

        addComment('Atlas', `Round 1 is in the books! ${r1Winner === 'Draw' ? 'A draw!' : r1Winner + ' advances!'}`);

        await new Promise(r => setTimeout(r, 3000));

        // Reset the game for round 2
        resetGame();
        await new Promise(r => setTimeout(r, 1500));

        // ── Final: Winner vs Nova ──
        const r1WinnerIdx = r1Winner === 'Draw'
            ? PLAYERS[0].idx  // Cipher advances on draw (higher ELO)
            : PLAYERS.find(p => p.name === r1Winner)?.idx ?? PLAYERS[0].idx;

        setBracketStatus('Final round starting...');
        addComment('Sage', `Now for the final. ${cs().AGENTS[r1WinnerIdx].name} faces Nova for the championship.`);

        await new Promise(r => setTimeout(r, 2000));

        const champion = await runMatch(r1WinnerIdx, PLAYERS[2].idx, 1);

        // ── Tournament Complete ──
        setBracketStatus('Tournament Complete!');

        const champName = champion === 'Draw' ? 'No clear winner' : champion;

        queueComment(COMMENTATORS[0], `You are Sage, the analytical commentator. The tournament is over. ${champName} ${champion === 'Draw' ? 'resulted in a draw' : 'is the champion'}. Summarize the tournament in 2 sentences.`);
        queueComment(COMMENTATORS[1], `You are Atlas, the enthusiastic commentator. ${champName} ${champion === 'Draw' ? '— what an evenly matched final!' : 'has won the tournament!'} Give an excited 1-sentence sign-off.`);

        await new Promise(r => setTimeout(r, 5000));

        // Show tournament results banner
        const banner = document.createElement('div');
        Object.assign(banner.style, {
            position: 'fixed', top: '50%', left: '50%', transform: 'translate(-50%, -50%)',
            zIndex: '600', background: 'rgba(10,10,20,.95)', border: '2px solid rgba(125,0,255,.5)',
            borderRadius: '20px', padding: '40px 60px', textAlign: 'center',
            backdropFilter: 'blur(20px)', boxShadow: '0 0 60px rgba(125,0,255,.3)',
            animation: 'fadeInUp .5s ease'
        });
        banner.innerHTML = `
            <div style="font-size:.8rem;text-transform:uppercase;letter-spacing:3px;color:rgba(255,255,255,.4);margin-bottom:12px">TOURNAMENT COMPLETE</div>
            <div style="font-size:2.5rem;margin-bottom:8px">👑</div>
            <div style="font-size:1.6rem;font-weight:800;background:linear-gradient(135deg,#f59e0b,#ef4444);-webkit-background-clip:text;-webkit-text-fill-color:transparent;margin-bottom:6px">
                ${champName}
            </div>
            <div style="color:rgba(255,255,255,.5);font-size:.9rem;margin-bottom:20px">
                ${champion === 'Draw' ? 'The final ended in a draw' : 'Tournament Champion'}
            </div>
            <div style="display:flex;gap:16px;justify-content:center;margin-bottom:20px">
                ${bracket.map((m, i) => `
                    <div style="background:rgba(255,255,255,.05);border-radius:8px;padding:10px 16px;font-size:.8rem">
                        <div style="color:rgba(255,255,255,.3);font-size:.65rem;margin-bottom:4px">${i === 0 ? 'ROUND 1' : 'FINAL'}</div>
                        <span style="color:${m.whiteColor}">${m.white}</span>
                        <span style="color:rgba(255,255,255,.2)"> vs </span>
                        <span style="color:${m.blackColor}">${m.black}</span>
                        <div style="color:#22c55e;font-size:.75rem;margin-top:2px">${m.winner} ${m.moves ? '(' + m.moves + ' moves)' : ''}</div>
                    </div>
                `).join('')}
            </div>
            <button onclick="this.parentElement.remove()" style="padding:8px 24px;border-radius:8px;border:1px solid rgba(255,255,255,.2);background:linear-gradient(135deg,rgba(125,0,255,.3),rgba(0,116,217,.3));color:#fff;cursor:pointer;font-size:.85rem;font-weight:600">
                Close
            </button>
        `;
        document.body.appendChild(banner);
    }

    // ── Launch ─────────────────────────────────────────────
    function showStartSplash() {
        // Show a start button that requires a real user click (enables AudioContext)
        const splash = document.createElement('div');
        splash.id = 'autopilotSplash';
        Object.assign(splash.style, {
            position: 'fixed', inset: '0', zIndex: '10000',
            background: 'rgba(10,10,20,.95)', display: 'flex',
            alignItems: 'center', justifyContent: 'center',
            backdropFilter: 'blur(12px)', fontFamily: 'Inter,system-ui,sans-serif'
        });
        splash.innerHTML = `
            <div style="text-align:center;max-width:420px">
                <div style="font-size:3rem;margin-bottom:12px">♟️</div>
                <div style="font-size:1.4rem;font-weight:800;background:linear-gradient(135deg,#f59e0b,#ef4444);-webkit-background-clip:text;-webkit-text-fill-color:transparent;margin-bottom:8px">
                    AI CHESS TOURNAMENT
                </div>
                <div style="color:rgba(255,255,255,.5);font-size:.85rem;margin-bottom:6px">
                    Cipher vs Alfred vs Nova
                </div>
                <div style="color:rgba(255,255,255,.35);font-size:.75rem;margin-bottom:24px">
                    Live commentary by Sage &amp; Atlas
                </div>
                <button id="autopilotStartBtn" style="padding:12px 36px;border-radius:10px;border:none;
                    background:linear-gradient(135deg,#7D00FF,#0074D9);color:#fff;font-size:1rem;
                    font-weight:700;cursor:pointer;letter-spacing:.5px;
                    box-shadow:0 4px 20px rgba(125,0,255,.4);transition:transform .15s">
                    Start Tournament
                </button>
                <div style="color:rgba(255,255,255,.25);font-size:.65rem;margin-top:12px">
                    Click to enable sound &amp; start
                </div>
            </div>
        `;
        document.body.appendChild(splash);

        document.getElementById('autopilotStartBtn').addEventListener('click', () => {
            splash.style.opacity = '0';
            splash.style.transition = 'opacity .4s';
            setTimeout(() => splash.remove(), 500);

            // Hide the mode modal so the 3D board is visible
            const modal = document.getElementById('modeModal');
            if (modal) modal.classList.add('hidden');

            // Hide loading screen if still visible
            const loading = document.getElementById('loading');
            if (loading) loading.classList.add('hidden');

            runTournament().catch(err => {
                console.error('Autopilot tournament error:', err);
                addComment('Sage', 'Technical difficulties — the tournament has been interrupted.');
            });
        });
    }

    const params = new URLSearchParams(location.search);
    if (params.get('autopilot') === '1') {
        // Wait for page to be ready, then show start splash
        if (document.readyState === 'complete') {
            showStartSplash();
        } else {
            window.addEventListener('load', showStartSplash);
        }
    }
})();
