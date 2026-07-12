/**
 * GoSiteMe Agent Presence System v2.0
 * Shared module for all VR games — live agent status, spawn controls, activity feed
 * Include this script in any VR game page to get the presence overlay.
 *
 * Usage: <script src="/assets/js/agent-presence.js" data-game="chess"></script>
 *        AgentPresence.init('chess');  // or auto-detects from data-game
 */
(function() {
    'use strict';

    const API = '/api/game-ecosystem.php';
    const POLL_INTERVAL = 30000;
    const GAME_LABELS = {
        'chess':        '♟️ Chess Arena',
        'checkers':     '🏁 Checkers',
        'pool':         '🎱 Pool Hall',
        'dj-studio':    '🎧 DJ Studio',
        'speed-dating': '💕 Speed Dating',
        'sanctuary':    '⛪ Sanctuary',
    };
    const STATUS_ICONS = {
        'playing':    '🟢',
        'spectating': '👁️',
        'available':  '🔵',
        'traveling':  '✈️',
        'resting':    '💤',
    };
    const MISSION_LABELS = {
        'play':     '⚔️ Play',
        'spectate': '👁️ Watch',
        'train':    '🏋️ Train',
        'explore':  '🔍 Explore',
    };

    let currentGame = null;
    let pollTimer = null;
    let panelEl = null;
    let minimized = false;
    let spawnOpen = false;
    let cachedAgentProfiles = null;

    function esc(s) {
        return GDS.esc(s).replace(/'/g, '&#39;');
    }

    // ── Inject CSS ──
    function injectStyles() {
        if (document.getElementById('agent-presence-styles')) return;
        const style = document.createElement('style');
        style.id = 'agent-presence-styles';
        style.textContent = `
            #agentPresencePanel {
                position: fixed;
                top: 70px;
                right: 12px;
                width: 310px;
                max-height: 85vh;
                background: rgba(10, 10, 30, 0.95);
                border: 1px solid rgba(100, 140, 255, 0.3);
                border-radius: 12px;
                color: #e0e0ff;
                font-family: 'Segoe UI', system-ui, sans-serif;
                font-size: 13px;
                z-index: 10000;
                overflow: hidden;
                backdrop-filter: blur(12px);
                box-shadow: 0 4px 24px rgba(0,0,0,0.5);
                transition: all 0.3s ease;
            }
            #agentPresencePanel.minimized {
                max-height: 42px;
                width: 200px;
            }
            #agentPresencePanel.minimized .ap-body { display: none; }

            .ap-header {
                display: flex; align-items: center; justify-content: space-between;
                padding: 10px 14px;
                background: rgba(30, 40, 80, 0.6);
                border-bottom: 1px solid rgba(100, 140, 255, 0.15);
                cursor: pointer; user-select: none;
            }
            .ap-header-title { font-weight: 600; font-size: 13px; display: flex; align-items: center; gap: 6px; }
            .ap-header-count { background: #3b82f6; color: #fff; font-size: 11px; padding: 2px 7px; border-radius: 10px; font-weight: 700; }
            .ap-header-toggle { font-size: 16px; opacity: 0.6; transition: transform 0.3s; }
            #agentPresencePanel.minimized .ap-header-toggle { transform: rotate(180deg); }

            .ap-body { padding: 0; overflow-y: auto; max-height: calc(85vh - 42px); }
            .ap-section { padding: 8px 12px; border-bottom: 1px solid rgba(100, 140, 255, 0.1); }
            .ap-section-title { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #8899cc; margin-bottom: 6px; font-weight: 600; display: flex; justify-content: space-between; align-items: center; }

            .ap-agent-row { display: flex; align-items: center; gap: 8px; padding: 5px 0; border-bottom: 1px solid rgba(255,255,255,0.04); }
            .ap-agent-row:last-child { border-bottom: none; }
            .ap-agent-avatar { font-size: 20px; width: 28px; text-align: center; flex-shrink: 0; }
            .ap-agent-info { flex: 1; min-width: 0; }
            .ap-agent-name { font-weight: 600; font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .ap-agent-activity { font-size: 10px; color: #8899bb; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .ap-agent-status { font-size: 14px; flex-shrink: 0; }
            .ap-agent-elo { font-size: 10px; color: #aab; background: rgba(255,255,255,0.06); padding: 2px 5px; border-radius: 4px; flex-shrink: 0; }

            .ap-world-row { display: flex; align-items: center; justify-content: space-between; padding: 4px 0; font-size: 11px; }
            .ap-world-name { color: #aabbdd; }
            .ap-world-pop { display: flex; gap: 8px; }
            .ap-world-pop span { display: flex; align-items: center; gap: 2px; }

            .ap-feed-item { padding: 3px 0; font-size: 11px; color: #8899bb; display: flex; gap: 6px; }
            .ap-feed-emoji { flex-shrink: 0; }
            .ap-feed-text { flex: 1; }
            .ap-feed-time { color: #556; font-size: 10px; flex-shrink: 0; }

            .ap-stats-bar { display: flex; justify-content: space-around; padding: 8px 0; text-align: center; }
            .ap-stat-value { font-size: 18px; font-weight: 700; color: #6ea8fe; }
            .ap-stat-label { font-size: 9px; text-transform: uppercase; color: #667; letter-spacing: 0.5px; }

            /* ── Spawn Controls ── */
            .ap-spawn-btn {
                background: linear-gradient(135deg, #3b82f6, #8b5cf6);
                border: none; color: #fff; padding: 6px 12px; border-radius: 8px;
                font-size: 11px; font-weight: 600; cursor: pointer;
                transition: all 0.2s; display: flex; align-items: center; gap: 4px;
            }
            .ap-spawn-btn:hover { transform: scale(1.05); box-shadow: 0 2px 12px rgba(99,102,241,0.4); }
            .ap-spawn-btn:active { transform: scale(0.97); }

            #apSpawnPanel { display: none; }
            #apSpawnPanel.open { display: block; }

            .ap-spawn-grid {
                display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px;
                margin: 6px 0;
            }
            .ap-spawn-agent {
                display: flex; flex-direction: column; align-items: center; gap: 2px;
                padding: 6px 4px; border-radius: 8px; cursor: pointer;
                background: rgba(255,255,255,0.04); border: 2px solid transparent;
                transition: all 0.2s; font-size: 10px;
            }
            .ap-spawn-agent:hover { background: rgba(99,102,241,0.15); }
            .ap-spawn-agent.selected { border-color: #6366f1; background: rgba(99,102,241,0.2); }
            .ap-spawn-agent-emoji { font-size: 22px; }
            .ap-spawn-agent-name { font-weight: 600; color: #ccd; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 60px; }

            .ap-spawn-row { display: flex; gap: 6px; margin: 6px 0; }
            .ap-spawn-select {
                flex: 1; padding: 6px 8px; border-radius: 6px;
                background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1);
                color: #dde; font-size: 11px; outline: none;
            }
            .ap-spawn-select:focus { border-color: #6366f1; }
            .ap-spawn-select option { background: #1a1a2e; color: #dde; }

            .ap-spawn-go {
                width: 100%; padding: 8px; border: none; border-radius: 8px;
                background: linear-gradient(135deg, #10b981, #059669);
                color: #fff; font-weight: 700; font-size: 12px; cursor: pointer;
                transition: all 0.2s; letter-spacing: 0.5px;
            }
            .ap-spawn-go:hover { transform: translateY(-1px); box-shadow: 0 3px 12px rgba(16,185,129,0.4); }
            .ap-spawn-go:active { transform: translateY(0); }
            .ap-spawn-go:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

            .ap-spawn-narrative {
                background: rgba(16,185,129,0.1); border-left: 3px solid #10b981;
                padding: 8px 10px; border-radius: 0 8px 8px 0;
                font-size: 12px; color: #a7f3d0; margin: 8px 0;
                animation: apNarrativeIn 0.4s ease;
            }
            @keyframes apNarrativeIn {
                from { opacity: 0; transform: translateX(-10px); }
                to { opacity: 1; transform: translateX(0); }
            }

            .ap-spawn-history {
                max-height: 80px; overflow-y: auto; margin-top: 4px;
            }
            .ap-spawn-history-item {
                font-size: 10px; color: #8899bb; padding: 2px 0;
                border-bottom: 1px solid rgba(255,255,255,0.03);
            }

            @media (max-width: 600px) {
                #agentPresencePanel { width: 280px; right: 6px; top: 60px; }
                .ap-spawn-grid { grid-template-columns: repeat(4, 1fr); }
            }
        `;
        document.head.appendChild(style);
    }

    // ── Build Panel DOM ──
    function createPanel() {
        if (document.getElementById('agentPresencePanel')) {
            panelEl = document.getElementById('agentPresencePanel');
            return;
        }
        panelEl = document.createElement('div');
        panelEl.id = 'agentPresencePanel';

        const gameOptions = Object.entries(GAME_LABELS).map(([k, v]) =>
            '<option value="' + k + '"' + (k === currentGame ? ' selected' : '') + '>' + v + '</option>'
        ).join('');

        const missionOptions = Object.entries(MISSION_LABELS).map(([k, v]) =>
            '<option value="' + k + '">' + v + '</option>'
        ).join('');

        panelEl.innerHTML = `
            <div class="ap-header" onclick="AgentPresence.toggle()">
                <div class="ap-header-title">
                    🤖 Agent Presence <span class="ap-header-count" id="apCount">0</span>
                </div>
                <span class="ap-header-toggle">▼</span>
            </div>
            <div class="ap-body" id="apBody">
                <div class="ap-section" id="apStatsSection">
                    <div class="ap-stats-bar">
                        <div class="ap-stat-item"><div class="ap-stat-value" id="apTotalAgents">0</div><div class="ap-stat-label">Agents</div></div>
                        <div class="ap-stat-item"><div class="ap-stat-value" id="apTotalUsers">0</div><div class="ap-stat-label">Users</div></div>
                        <div class="ap-stat-item"><div class="ap-stat-value" id="apTotalGames">0</div><div class="ap-stat-label">Worlds</div></div>
                    </div>
                </div>

                <div class="ap-section" id="apSpawnSection">
                    <div class="ap-section-title">
                        Spawn Agent
                        <button class="ap-spawn-btn" onclick="AgentPresence.toggleSpawn(event)">🚀 Deploy</button>
                    </div>
                    <div id="apSpawnPanel">
                        <div class="ap-spawn-grid" id="apSpawnGrid">
                            <em style="color:#556;font-size:10px;grid-column:1/-1">Loading agents...</em>
                        </div>
                        <div class="ap-spawn-row">
                            <select class="ap-spawn-select" id="apSpawnGame">${gameOptions}</select>
                            <select class="ap-spawn-select" id="apSpawnMission">${missionOptions}</select>
                        </div>
                        <button class="ap-spawn-go" id="apSpawnGo" onclick="AgentPresence.spawnAgent()" disabled>
                            Select an agent above ↑
                        </button>
                        <div id="apSpawnNarrative"></div>
                        <div class="ap-spawn-history" id="apSpawnHistory"></div>
                    </div>
                </div>

                <div class="ap-section" id="apLocalSection">
                    <div class="ap-section-title">In This Game</div>
                    <div id="apLocalAgents"><em style="color:#556">Loading...</em></div>
                </div>
                <div class="ap-section" id="apWorldsSection">
                    <div class="ap-section-title">All Worlds</div>
                    <div id="apWorlds"></div>
                </div>
                <div class="ap-section" id="apFeedSection">
                    <div class="ap-section-title">Activity Feed</div>
                    <div id="apFeed"></div>
                </div>
            </div>
        `;
        document.body.appendChild(panelEl);
    }

    // ── Load agent roster for spawn grid ──
    async function loadAgentRoster() {
        if (cachedAgentProfiles) return cachedAgentProfiles;
        try {
            const res = await fetch(API + '?action=agent-profiles');
            const d = await res.json();
            if (d.success && d.agents) {
                cachedAgentProfiles = d.agents;
                renderSpawnGrid(d.agents);
                return d.agents;
            }
        } catch(e) {}
        return [];
    }

    function renderSpawnGrid(agents) {
        const grid = document.getElementById('apSpawnGrid');
        if (!grid) return;
        grid.innerHTML = agents.map(a => `
            <div class="ap-spawn-agent" data-id="${esc(a.id)}" onclick="AgentPresence.selectAgent('${esc(a.id)}', event)">
                <div class="ap-spawn-agent-emoji">${esc(a.emoji)}</div>
                <div class="ap-spawn-agent-name">${esc(a.name)}</div>
            </div>
        `).join('');
    }

    let selectedAgentId = null;

    function selectAgent(id, evt) {
        if (evt) evt.stopPropagation();
        selectedAgentId = id;
        // Update selection UI
        document.querySelectorAll('.ap-spawn-agent').forEach(el => {
            el.classList.toggle('selected', el.dataset.id === id);
        });
        // Update button
        const btn = document.getElementById('apSpawnGo');
        const agent = (cachedAgentProfiles || []).find(a => a.id === id);
        if (btn && agent) {
            btn.disabled = false;
            btn.textContent = '🚀 Spawn ' + agent.name + '!';
        }
    }

    // ── Spawn Agent ──
    async function doSpawn() {
        if (!selectedAgentId) return;
        const gameEl = document.getElementById('apSpawnGame');
        const missionEl = document.getElementById('apSpawnMission');
        const btn = document.getElementById('apSpawnGo');
        const narEl = document.getElementById('apSpawnNarrative');
        const histEl = document.getElementById('apSpawnHistory');

        const game = gameEl ? gameEl.value : currentGame;
        const mission = missionEl ? missionEl.value : 'play';

        if (btn) { btn.disabled = true; btn.textContent = '⏳ Spawning...'; }

        try {
            const res = await fetch(API + '?action=spawn-agent', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ agent_id: selectedAgentId, game: game, mission: mission }),
            });
            const d = await res.json();

            if (d.success && d.spawn) {
                // Show narrative
                if (narEl) {
                    narEl.innerHTML = '<div class="ap-spawn-narrative">' + esc(d.narrative) + '</div>';
                }
                // Add to history
                if (histEl) {
                    const item = document.createElement('div');
                    item.className = 'ap-spawn-history-item';
                    item.textContent = d.spawn.emoji + ' ' + d.spawn.name + ' → ' + (GAME_LABELS[game] || game) + ' (' + mission + ')';
                    histEl.insertBefore(item, histEl.firstChild);
                }
                // Refresh presence data
                await refresh();
                if (btn) { btn.disabled = false; btn.textContent = '🚀 Spawn ' + d.spawn.name + '!'; }
            } else {
                if (btn) { btn.disabled = false; btn.textContent = '❌ ' + (d.error || 'Failed'); }
                setTimeout(() => { if (btn) { btn.textContent = '🚀 Try again'; btn.disabled = false; } }, 2000);
            }
        } catch(e) {
            if (btn) { btn.disabled = false; btn.textContent = '❌ Network error'; }
        }
    }

    // ── Fetch and Render ──
    async function refresh() {
        try {
            const [presenceRes, worldRes] = await Promise.all([
                fetch(API + '?action=agent-presence' + (currentGame ? '&game=' + currentGame : '')),
                fetch(API + '?action=agent-world-stats'),
            ]);
            const presence = await presenceRes.json();
            const world = await worldRes.json();

            if (!presence.success || !world.success) return;

            const countEl = document.getElementById('apCount');
            const localAgents = presence.agents || [];
            if (countEl) countEl.textContent = localAgents.length;

            const totalAgentsEl = document.getElementById('apTotalAgents');
            const totalUsersEl = document.getElementById('apTotalUsers');
            const totalGamesEl = document.getElementById('apTotalGames');
            if (totalAgentsEl) totalAgentsEl.textContent = world.totals.agents_deployed;
            if (totalUsersEl) totalUsersEl.textContent = world.totals.users_online;
            if (totalGamesEl) totalGamesEl.textContent = world.totals.games_active;

            // Local agents
            const localEl = document.getElementById('apLocalAgents');
            if (localEl) {
                if (localAgents.length === 0) {
                    localEl.innerHTML = '<em style="color:#556">No agents here — spawn one! 🚀</em>';
                } else {
                    localEl.innerHTML = localAgents.map(a => `
                        <div class="ap-agent-row">
                            <div class="ap-agent-avatar">${esc(a.emoji)}</div>
                            <div class="ap-agent-info">
                                <div class="ap-agent-name" style="color:${esc(a.color)}">${esc(a.name)}</div>
                                <div class="ap-agent-activity">${esc(a.activity)}</div>
                            </div>
                            <div class="ap-agent-elo">${a.elo}</div>
                            <div class="ap-agent-status" title="${esc(a.status)}">${STATUS_ICONS[a.status] || '⚪'}</div>
                        </div>
                    `).join('');
                }
            }

            // All worlds
            const worldsEl = document.getElementById('apWorlds');
            if (worldsEl && world.worlds) {
                worldsEl.innerHTML = Object.entries(world.worlds).map(([game, w]) => `
                    <div class="ap-world-row">
                        <span class="ap-world-name">${GAME_LABELS[game] || game}</span>
                        <div class="ap-world-pop">
                            <span title="Agents">🤖 ${w.agents_total}</span>
                            <span title="Users">👤 ${w.users_total}</span>
                        </div>
                    </div>
                `).join('');
            }

            // Activity feed
            const feedEl = document.getElementById('apFeed');
            if (feedEl && world.recent_activity) {
                if (world.recent_activity.length === 0) {
                    feedEl.innerHTML = '<em style="color:#556">No recent activity</em>';
                } else {
                    feedEl.innerHTML = world.recent_activity.slice(0, 6).map(ev => {
                        const ago = timeAgo(ev.created_at);
                        return `
                            <div class="ap-feed-item">
                                <span class="ap-feed-emoji">${esc(ev.agent_emoji)}</span>
                                <span class="ap-feed-text"><b>${esc(ev.agent_name)}</b> ${esc(ev.detail)}</span>
                                <span class="ap-feed-time">${ago}</span>
                            </div>
                        `;
                    }).join('');
                }
            }
        } catch (err) {
            console.warn('Agent presence refresh error:', err);
        }
    }

    function timeAgo(dateStr) {
        const now = new Date();
        const then = new Date(dateStr.replace(' ', 'T') + 'Z');
        const diff = Math.floor((now - then) / 1000);
        if (diff < 60) return 'now';
        if (diff < 3600) return Math.floor(diff / 60) + 'm';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h';
        return Math.floor(diff / 86400) + 'd';
    }

    // ── Public API ──
    window.AgentPresence = {
        init(game) {
            currentGame = game || null;
            if (!currentGame) {
                const scriptTag = document.querySelector('script[data-game]');
                if (scriptTag) currentGame = scriptTag.getAttribute('data-game');
            }

            injectStyles();
            createPanel();
            refresh();
            loadAgentRoster();

            if (pollTimer) clearInterval(pollTimer);
            pollTimer = setInterval(refresh, POLL_INTERVAL);
        },

        toggle() {
            if (!panelEl) return;
            minimized = !minimized;
            panelEl.classList.toggle('minimized', minimized);
        },

        toggleSpawn(evt) {
            if (evt) evt.stopPropagation();
            spawnOpen = !spawnOpen;
            const panel = document.getElementById('apSpawnPanel');
            if (panel) panel.classList.toggle('open', spawnOpen);
            if (spawnOpen) loadAgentRoster();
        },

        selectAgent(id, evt) {
            selectAgent(id, evt);
        },

        spawnAgent() {
            doSpawn();
        },

        refresh() { return refresh(); },

        setGame(game) { currentGame = game; refresh(); },

        destroy() {
            if (pollTimer) clearInterval(pollTimer);
            if (panelEl) panelEl.remove();
            panelEl = null;
        },
    };

    // Auto-init
    document.addEventListener('DOMContentLoaded', function() {
        const scriptTag = document.querySelector('script[src*="agent-presence"]');
        if (scriptTag && scriptTag.dataset.game !== undefined) {
            AgentPresence.init(scriptTag.dataset.game);
        }
    });
})();
