/* ═══════════════════════════════════════════════════════════════
   ALFRED COMMAND — Main Orchestrator
   Global AlfredCommand object, game flow, loading, side panels
   ═══════════════════════════════════════════════════════════════ */

const AlfredCommand = {
    // State
    gameState: null,
    pollInterval: null,
    currentPanel: null,

    /* ───────── Boot sequence ───────── */
    async boot() {
        this._setLoading('Connecting to network...', 10);

        // 1. Load initial game state
        try {
            this.gameState = await GameAPI.getGameState();
        } catch (e) {
            console.warn('Game state load failed — offline mode', e);
            this.gameState = { player: {}, resources: [], missions: [], deployments: [], territories: [] };
        }
        this._setLoading('Game state loaded', 30);

        // 2. Init 3D engine
        this._setLoading('Initializing Babylon.js...', 40);
        await GameEngine.init();
        this._setLoading('3D engine ready', 60);

        // 3. Pre-load domain list + territory data
        this._setLoading('Loading agent domains...', 70);
        await AgentSystem.loadDomains();
        await TerritorySystem.load();
        await MissionSystem.loadTemplates();
        this._setLoading('Mission systems online', 85);

        // 4. Populate menu with player data
        this._updateMenu();

        // 5. Detect VR
        this._setLoading('Detecting VR headset...', 90);
        const vrStatus = document.getElementById('vrStatus');
        if (navigator.xr) {
            try {
                const supported = await navigator.xr.isSessionSupported('immersive-vr');
                vrStatus.textContent = supported ? '✅ VR headset detected — Quest 3 ready' : '⚠️ No VR headset — desktop mode';
                vrStatus.style.color = supported ? '#10b981' : '#f59e0b';
            } catch {
                vrStatus.textContent = '⚠️ WebXR not available — desktop mode';
                vrStatus.style.color = '#f59e0b';
            }
        } else {
            vrStatus.textContent = '⚠️ WebXR not supported — desktop mode';
            vrStatus.style.color = '#f59e0b';
        }

        this._setLoading('All systems operational', 100);

        // 6. Show menu after brief delay
        setTimeout(() => {
            document.getElementById('loadingScreen').style.display = 'none';
            document.getElementById('menuScreen').style.display = 'flex';
        }, 600);
    },

    _setLoading(msg, pct) {
        const bar = document.getElementById('loadingBar');
        const status = document.getElementById('loadingStatus');
        if (bar) bar.style.width = pct + '%';
        if (status) status.textContent = msg;
    },

    _updateMenu() {
        const p = this.gameState?.player || {};
        const rank = p.rank_title || 'E-1 Recruit';
        const xp = p.total_xp || 0;
        document.getElementById('menuRankBadge').textContent = rank;
        document.getElementById('menuXP').textContent = `XP: ${this._fmt(xp)}`;
    },

    /* ───────── Start game session ───────── */
    async startGame(mode) {
        document.getElementById('menuScreen').style.display = 'none';
        document.getElementById('gameHUD').style.display = 'block';

        // Start backend session
        try {
            const vrAvail = navigator.xr ? await navigator.xr.isSessionSupported('immersive-vr').catch(() => false) : false;
            const session = await GameAPI.startSession(mode, vrAvail, navigator.userAgent);
            GameEngine.state.session = session;
        } catch (e) {
            console.warn('Session start failed:', e);
        }

        // Sync state into engine
        GameEngine.state.resources = this.gameState?.resources || [];
        GameEngine.state.activeMissions = this.gameState?.active_missions || [];
        GameEngine.state.deployments = this.gameState?.deployments || [];
        GameEngine.state.territories = TerritorySystem.territories;
        GameEngine.state.zones = TerritorySystem.zones;
        GameEngine.state.structures = this.gameState?.structures || [];

        // Build 3D environment
        Environments.buildCommandCenter(GameEngine.scene);
        Environments.buildTerritoryMarkers(GameEngine.scene, GameEngine.state);
        Environments.buildPlayerStructures(GameEngine.scene, GameEngine.state);
        CommandTable.build(GameEngine.scene, GameEngine.state);

        // Init HUD
        HUD.show();
        HUD.refresh(GameEngine.state, this.gameState?.player);

        // Start game loop polling (every 30s)
        this.pollInterval = setInterval(() => this.refreshGameState(), 30000);

        // Resize handler
        GameEngine.engine.resize();

        this.toast(`Mode: ${mode} — Let's build the new world, Commander.`);
    },

    /* ───────── Refresh game state ───────── */
    async refreshGameState() {
        try {
            this.gameState = await GameAPI.getGameState();
            GameEngine.state.resources = this.gameState.resources || [];
            GameEngine.state.activeMissions = this.gameState.active_missions || [];
            GameEngine.state.deployments = this.gameState.deployments || [];
            GameEngine.state.structures = this.gameState.structures || [];
            HUD.refresh(GameEngine.state, this.gameState.player);
        } catch (e) {
            console.warn('State refresh failed:', e);
        }
    },

    /* ───────── Side Panels ───────── */
    openSidePanel(title, htmlContent) {
        this.currentPanel = title;
        document.getElementById('sidePanelTitle').textContent = title;
        document.getElementById('sidePanelBody').innerHTML = htmlContent;
        document.getElementById('sidePanel').style.display = 'block';
    },

    closeSidePanel() {
        document.getElementById('sidePanel').style.display = 'none';
        this.currentPanel = null;
    },

    async openMissions() {
        await MissionSystem.loadTemplates();
        this.openSidePanel('MISSIONS', MissionSystem.renderPanel());
    },

    async openTerritories() {
        await TerritorySystem.load();
        this.openSidePanel('TERRITORIES', TerritorySystem.renderPanel());
    },

    async openAgents(targetZone) {
        await AgentSystem.loadDomains();
        this.openSidePanel('AGENT DEPLOYMENT', AgentSystem.renderPanel(targetZone));
    },

    openSupply() {
        this.openSidePanel('SUPPLY & RESOURCES', ResourceSystem.renderPanel());
    },

    async openComms() {
        await CommsSystem.loadBattleLog();
        this.openSidePanel('COMMS CENTER', CommsSystem.renderPanel());
    },

    openBuild() {
        this.openSidePanel('BUILD', ResourceSystem._renderBuildForm());
    },

    /* ───────── Stats panel ───────── */
    showStats() {
        const p = this.gameState?.player || {};
        const resources = this.gameState?.resources || [];
        const stats = this.gameState?.stats || {};

        document.getElementById('statsBody').innerHTML = `
            <div class="stat-grid">
                <div class="stat-item">
                    <span class="stat-label">Rank</span>
                    <span class="stat-value">${p.rank_title || 'E-0 Civilian'}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Total XP</span>
                    <span class="stat-value">${this._fmt(p.total_xp || 0)}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Missions Completed</span>
                    <span class="stat-value">${stats.missions_completed || 0}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Zones Captured</span>
                    <span class="stat-value">${stats.zones_captured || 0}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Agents Deployed</span>
                    <span class="stat-value">${this._fmt(stats.agents_deployed || 0)}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Battles Won</span>
                    <span class="stat-value">${stats.battles_won || 0}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Total Playtime</span>
                    <span class="stat-value">${this._formatPlaytime(stats.total_playtime_minutes || 0)}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">XP This Session</span>
                    <span class="stat-value">${this._fmt(stats.xp_earned_session || 0)}</span>
                </div>
            </div>
            <h4 style="color:#94a3b8;margin-top:16px;">Resources</h4>
            <div class="stat-grid">
                ${resources.map(r => `
                    <div class="stat-item">
                        <span class="stat-label">${r.resource_type.replace(/_/g, ' ')}</span>
                        <span class="stat-value">${this._fmt(r.quantity)}</span>
                    </div>
                `).join('')}
            </div>
        `;
        document.getElementById('statsPanel').style.display = 'flex';
    },

    hideStats() {
        document.getElementById('statsPanel').style.display = 'none';
    },

    /* ───────── VR toggle ───────── */
    toggleVR() {
        GameEngine.toggleVR();
    },

    /* ───────── Toast notifications ───────── */
    toast(message) {
        const el = document.getElementById('toast');
        if (!el) return;
        el.textContent = message;
        el.classList.add('show');
        setTimeout(() => el.classList.remove('show'), 3500);
    },

    /* ───────── Utilities ───────── */
    _fmt(n) {
        if (n >= 1e6) return (n / 1e6).toFixed(1) + 'M';
        if (n >= 1e3) return (n / 1e3).toFixed(1) + 'K';
        return String(n);
    },

    _formatPlaytime(mins) {
        if (mins < 60) return mins + ' min';
        const h = Math.floor(mins / 60);
        const m = mins % 60;
        return h + 'h ' + m + 'm';
    },
};

/* ───────── Auto-boot on page load ───────── */
window.addEventListener('DOMContentLoaded', () => {
    AlfredCommand.boot();
});
