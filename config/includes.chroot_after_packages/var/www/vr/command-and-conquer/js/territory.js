/* ═══════════════════════════════════════════════════════════════
   ALFRED COMMAND — Territory System
   View territories, zones, capture zones, and manage control
   ═══════════════════════════════════════════════════════════════ */

const TerritorySystem = {
    territories: [],
    zones: [],

    async load() {
        const data = await GameAPI.getTerritoryStatus();
        this.territories = data.territories || [];
        this.zones = data.zones || [];
        return data;
    },

    renderPanel() {
        let html = '';

        // Territory overview
        html += `<h4 style="color:#94a3b8;margin-bottom:8px;">Territories (${this.territories.length})</h4>`;
        this.territories.forEach(t => {
            html += this._renderTerritoryCard(t);
        });

        html += `<hr style="border-color:#2d3a5c;margin:16px 0;">`;

        // Zone detail list
        html += `<h4 style="color:#94a3b8;margin-bottom:8px;">Zones (${this.zones.length})</h4>`;
        this.zones.forEach(z => {
            html += this._renderZoneCard(z);
        });

        return html;
    },

    _renderTerritoryCard(t) {
        const typeColors = {
            outpost: '#6b7280', base: '#3b82f6', fortress: '#a855f7',
            capital: '#f59e0b', sacred: '#ef4444',
        };
        const color = typeColors[t.territory_type] || '#64748b';

        return `
            <div class="territory-card" style="border-left:3px solid ${color};">
                <div class="tc-header">
                    <span class="tc-name" style="color:${color};">${t.name}</span>
                    <span class="tc-type">${t.territory_type}</span>
                </div>
                <div class="tc-meta">
                    <span>⚡ ${t.passive_xp_rate} XP/hr</span>
                    <span>🛡️ max ${t.max_defenders}</span>
                    ${t.controller ? `<span style="color:#10b981;">Controlled by #${t.controller}</span>` : `<span style="color:#6b7280;">Unclaimed</span>`}
                </div>
                ${t.resources && t.resources.length > 0 ? `
                    <div class="tc-resources">
                        ${t.resources.map(r => `<span class="resource-tag">${r.resource_type}: ${r.quantity}</span>`).join('')}
                    </div>
                ` : ''}
            </div>
        `;
    },

    _renderZoneCard(z) {
        const deployed = z.deployed_agents || 0;
        const capturable = deployed >= (z.capture_difficulty || 50);

        return `
            <div class="zone-card">
                <div class="zc-header">
                    <span class="zc-code">${z.zone_code}</span>
                    <span class="zc-territory">${z.territory_name || ''}</span>
                </div>
                <div class="zc-name">${z.name}</div>
                <div class="zc-meta">
                    <span>⚡ ${z.xp_per_hour} XP/hr</span>
                    <span>🎯 Difficulty: ${z.capture_difficulty}</span>
                    <span>👥 ${deployed} deployed</span>
                </div>
                <div class="zc-bar">
                    <div class="zc-bar-fill" style="width:${Math.min(100, (deployed / z.capture_difficulty) * 100)}%;background:${capturable ? '#10b981' : '#3b82f6'};"></div>
                </div>
                <div style="margin-top:8px;display:flex;gap:6px;">
                    <button class="action-btn primary" onclick="AlfredCommand.openAgents('${z.zone_code}')">Deploy</button>
                    ${capturable ? `<button class="action-btn accent" onclick="TerritorySystem.captureZone('${z.zone_code}')">⚔️ Capture</button>` : ''}
                </div>
            </div>
        `;
    },

    async captureZone(zoneCode) {
        const zone = this.zones.find(z => z.zone_code === zoneCode);
        if (!zone) return;

        AlfredCommand.toast(`Attempting capture of ${zoneCode}...`);
        const result = await GameAPI.captureZone(zone.id);
        if (result.success) {
            if (result.captured) {
                AlfredCommand.toast(`🏆 ${zoneCode} CAPTURED! +${result.xp_earned || 0} XP`);
            } else {
                AlfredCommand.toast(`⚔️ Capture failed — force ${result.force} vs difficulty ${result.difficulty}`);
            }
            await AlfredCommand.refreshGameState();
            await this.load();
            document.getElementById('sidePanelBody').innerHTML = this.renderPanel();
            // Refresh 3D markers
            if (typeof Environments !== 'undefined') {
                Environments.buildTerritoryMarkers(GameEngine.scene, GameEngine.state);
            }
        }
    },
};
