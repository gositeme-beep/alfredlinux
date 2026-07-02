/* ═══════════════════════════════════════════════════════════════
   ALFRED COMMAND — Mission System
   Browse, start, and complete missions
   ═══════════════════════════════════════════════════════════════ */

const MissionSystem = {
    templates: [],
    activeFilter: null,

    async loadTemplates(type) {
        const data = await GameAPI.getMissionList(type || null);
        this.templates = data.missions || [];
        return this.templates;
    },

    renderPanel() {
        const types = ['combat', 'military_police', 'humanitarian', 'intel', 'cadastre', 'logistics', 'morale', 'theory'];
        const typeLabels = {
            combat: '⚔️ Combat', military_police: '🛡️ MP', humanitarian: '🕊️ Aid',
            intel: '🔍 Intel', cadastre: '📐 Land', logistics: '📦 Supply',
            morale: '❤️ Morale', theory: '🧪 Theory',
        };

        let html = `
            <div class="filter-tabs">
                <button class="filter-tab ${!this.activeFilter ? 'active' : ''}" onclick="MissionSystem.filter(null)">All</button>
                ${types.map(t => `
                    <button class="filter-tab ${this.activeFilter === t ? 'active' : ''}" onclick="MissionSystem.filter('${t}')">${typeLabels[t]}</button>
                `).join('')}
            </div>
        `;

        // Active missions section
        const active = GameEngine.state.activeMissions || [];
        if (active.length > 0) {
            html += `<h4 style="color:#10b981;margin-bottom:8px;">Active Missions</h4>`;
            active.forEach(m => {
                html += this._renderActiveMissionCard(m);
            });
            html += `<hr style="border-color:#2d3a5c;margin:16px 0;">`;
        }

        // Available missions
        html += `<h4 style="color:#94a3b8;margin-bottom:8px;">Available Missions (${this.templates.length})</h4>`;
        const filtered = this.activeFilter
            ? this.templates.filter(t => t.mission_type === this.activeFilter)
            : this.templates;

        filtered.forEach(t => {
            html += this._renderTemplateCard(t);
        });

        return html;
    },

    _renderTemplateCard(t) {
        const typeIcons = {
            combat: '⚔️', military_police: '🛡️', humanitarian: '🕊️', intel: '🔍',
            cadastre: '📐', logistics: '📦', morale: '❤️', theory: '🧪',
        };
        return `
            <div class="mission-card" onclick="MissionSystem.startMission('${t.template_code}')">
                <div class="mc-type">${typeIcons[t.mission_type] || ''} ${t.mission_type.replace('_', ' ')}</div>
                <div class="mc-title">${t.title}</div>
                <div class="mc-desc">${t.description || ''}</div>
                <div class="mc-meta">
                    <span class="mc-diff ${t.difficulty}">${t.difficulty}</span>
                    <span class="mc-xp">⚡ ${t.xp_min}-${t.xp_max} XP</span>
                    <span>👥 ${t.min_agents}+ agents</span>
                    <span>⏱️ ${Math.round(t.duration_min / 60)}min</span>
                </div>
            </div>
        `;
    },

    _renderActiveMissionCard(m) {
        return `
            <div class="mission-card" style="border-color:#10b981;">
                <div class="mc-type" style="color:#10b981;">⏳ ${m.mission_type.replace('_', ' ')} — ${m.status}</div>
                <div class="mc-title">${m.title}</div>
                <div class="mc-meta">
                    <span class="mc-xp">⚡ ${m.xp_reward} XP</span>
                    <span>👥 ${m.agents_deployed} deployed</span>
                </div>
                <div style="margin-top:8px;display:flex;gap:6px;">
                    <button class="action-btn primary" onclick="MissionSystem.advanceMission('${m.mission_id}')">Advance</button>
                    <button class="action-btn accent" onclick="MissionSystem.completeMission('${m.mission_id}')">Complete</button>
                    <button class="action-btn danger" onclick="MissionSystem.abandonMission('${m.mission_id}')">Abandon</button>
                </div>
            </div>
        `;
    },

    async filter(type) {
        this.activeFilter = type;
        await this.loadTemplates(type);
        document.getElementById('sidePanelBody').innerHTML = this.renderPanel();
    },

    async startMission(templateCode) {
        const sessionId = GameEngine.state.session?.session_id;
        // Pick a zone (first uncaptured zone, or random)
        const zones = GameEngine.state.zones || [];
        const zone = zones.length > 0 ? zones[Math.floor(Math.random() * zones.length)] : null;
        const zoneId = zone ? zone.id : 1;

        const result = await GameAPI.startMission(templateCode, zoneId, sessionId);
        if (result.success) {
            AlfredCommand.toast(`Mission started: ${result.title} — ${result.xp_reward} XP`);
            await AlfredCommand.refreshGameState();
            document.getElementById('sidePanelBody').innerHTML = this.renderPanel();
        }
    },

    async advanceMission(missionId) {
        // Progress the mission by 25%
        const mission = (GameEngine.state.activeMissions || []).find(m => m.mission_id === missionId);
        const newProgress = Math.min(100, (parseInt(mission?.progress) || 0) + 25);
        const status = newProgress >= 100 ? 'completed' : 'active';

        await GameAPI.updateMission(missionId, newProgress, status);
        AlfredCommand.toast(`Mission progress: ${newProgress}%${status === 'completed' ? ' — COMPLETE!' : ''}`);
        await AlfredCommand.refreshGameState();
        document.getElementById('sidePanelBody').innerHTML = this.renderPanel();
    },

    async completeMission(missionId) {
        await GameAPI.updateMission(missionId, 100, 'completed');
        AlfredCommand.toast('🎖️ Mission Complete! XP awarded.');
        await AlfredCommand.refreshGameState();
        document.getElementById('sidePanelBody').innerHTML = this.renderPanel();
    },

    async abandonMission(missionId) {
        await GameAPI.updateMission(missionId, 0, 'abandoned');
        AlfredCommand.toast('Mission abandoned.');
        await AlfredCommand.refreshGameState();
        document.getElementById('sidePanelBody').innerHTML = this.renderPanel();
    },
};
