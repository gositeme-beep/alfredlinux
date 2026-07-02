/* ═══════════════════════════════════════════════════════════════
   ALFRED COMMAND — HUD System
   2D overlay HUD for game information
   ═══════════════════════════════════════════════════════════════ */

const HUD = {
    resourceIcons: {
        credits: '💰', rations: '🍞', medical: '💊', construction: '🔨',
        intel: '🔍', fuel: '⛽', ammo: '🔫', comms: '📡', water: '💧', seeds: '🌱',
    },

    show() {
        document.getElementById('gameHUD').style.display = 'block';
    },

    hide() {
        document.getElementById('gameHUD').style.display = 'none';
    },

    updateRank(rankInfo) {
        const el = document.getElementById('hudRank');
        if (el && rankInfo) {
            el.textContent = rankInfo.rank_code || 'E-1';
            el.title = rankInfo.rank_name || 'Recruit';
        }
    },

    updateXP(stats, rankInfo) {
        const xp = stats?.total_xp || 0;
        const label = document.getElementById('hudXPLabel');
        const fill = document.getElementById('hudXPFill');
        if (label) label.textContent = this.formatNumber(xp) + ' XP';

        // XP bar — progress toward next rank
        if (fill && rankInfo) {
            const rankLevels = [0, 0, 100, 500, 2000, 5000, 10000, 25000, 50000, 100000, 500000];
            const current = rankInfo.rank_level || 1;
            const currentMin = rankLevels[current] || 0;
            const nextMin = rankLevels[current + 1] || currentMin + 10000;
            const pct = Math.min(100, ((xp - currentMin) / (nextMin - currentMin)) * 100);
            fill.style.width = pct + '%';
        }
    },

    updateResources(resources) {
        const container = document.getElementById('hudResources');
        if (!container) return;

        const order = ['credits', 'rations', 'medical', 'ammo', 'fuel', 'intel', 'construction', 'water', 'seeds', 'comms'];
        container.innerHTML = order
            .filter(r => resources[r] !== undefined)
            .map(r => `
                <div class="res-item">
                    <span class="res-icon">${this.resourceIcons[r]}</span>
                    <span class="res-count">${this.formatNumber(resources[r])}</span>
                </div>
            `).join('');
    },

    updateDeployed(deployments) {
        const el = document.getElementById('hudDeployed');
        if (!el) return;
        const total = deployments.reduce((sum, d) => sum + parseInt(d.agent_count || 0), 0);
        el.textContent = `Deployed: ${this.formatNumber(total)} agents`;
    },

    updateSession(session) {
        const el = document.getElementById('hudSession');
        if (el && session) {
            el.textContent = `${session.session_type} | ${session.vr_mode}`;
        }
    },

    updateActiveMission(mission) {
        let badge = document.querySelector('.active-mission-badge');
        if (!mission) {
            if (badge) badge.remove();
            return;
        }

        if (!badge) {
            badge = document.createElement('div');
            badge.className = 'active-mission-badge';
            document.getElementById('gameHUD').appendChild(badge);
        }

        badge.innerHTML = `
            <div class="amb-title">${mission.title}</div>
            <div style="font-size:0.7rem;color:#94a3b8;">${mission.mission_type} — ${mission.status}</div>
            <div class="amb-progress"><div class="amb-fill" style="width:${mission.progress}%"></div></div>
        `;
    },

    // Refresh entire HUD from game state
    refresh(gameState) {
        if (!gameState) return;
        const player = gameState.player || {};
        this.updateRank(player.rank);
        this.updateXP(player.stats, player.rank);
        this.updateResources(player.resources || {});
        this.updateDeployed(gameState.deployments || []);
        this.updateSession(gameState.session);

        const activeMission = (gameState.active_missions || []).find(m => m.status === 'active');
        this.updateActiveMission(activeMission);
    },

    formatNumber(n) {
        n = parseFloat(n) || 0;
        if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M';
        if (n >= 1000) return (n / 1000).toFixed(1) + 'K';
        return Math.floor(n).toLocaleString();
    },
};
