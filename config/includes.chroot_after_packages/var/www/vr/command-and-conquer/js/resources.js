/* ═══════════════════════════════════════════════════════════════
   ALFRED COMMAND — Resource & Supply System
   Player inventory, supply transfers, structure production
   ═══════════════════════════════════════════════════════════════ */

const ResourceSystem = {
    resourceIcons: {
        credits: '💰', intel_fragments: '🔍', construction_materials: '🧱',
        rations: '🍞', fuel_cells: '⛽', medical_supplies: '💊',
        comms_tokens: '📡', vr_crystals: '💎', morale_points: '❤️',
        tech_blueprints: '📋',
    },

    renderPanel() {
        const resources = GameEngine.state.resources || [];
        const structures = GameEngine.state.structures || [];
        let html = '';

        // Player resources
        html += `<h4 style="color:#94a3b8;margin-bottom:8px;">Your Resources</h4>`;
        html += `<div class="resource-grid">`;
        resources.forEach(r => {
            const icon = this.resourceIcons[r.resource_type] || '📦';
            html += `
                <div class="resource-item">
                    <span class="ri-icon">${icon}</span>
                    <span class="ri-name">${r.resource_type.replace(/_/g, ' ')}</span>
                    <span class="ri-qty">${this._fmt(r.quantity)}</span>
                </div>
            `;
        });
        html += `</div>`;

        // Supply transfer
        html += `<hr style="border-color:#2d3a5c;margin:16px 0;">`;
        html += `<h4 style="color:#94a3b8;margin-bottom:8px;">Supply Transfer</h4>`;
        html += this._renderTransferForm(resources);

        // Structures
        if (structures.length > 0) {
            html += `<hr style="border-color:#2d3a5c;margin:16px 0;">`;
            html += `<h4 style="color:#94a3b8;margin-bottom:8px;">Structures (${structures.length})</h4>`;
            structures.forEach(s => {
                html += this._renderStructureCard(s);
            });
        }

        // Build structure
        html += `<hr style="border-color:#2d3a5c;margin:16px 0;">`;
        html += `<h4 style="color:#94a3b8;margin-bottom:8px;">Build Structure</h4>`;
        html += this._renderBuildForm();

        return html;
    },

    _renderTransferForm(resources) {
        const resourceTypes = resources.map(r => r.resource_type);
        return `
            <div style="display:flex;flex-direction:column;gap:8px;">
                <select class="form-input" id="transferType">
                    ${resourceTypes.map(t => `<option value="${t}">${t.replace(/_/g, ' ')}</option>`).join('')}
                </select>
                <input type="number" class="form-input" id="transferQty" min="1" value="10" placeholder="Quantity">
                <div style="display:flex;gap:6px;">
                    <button class="action-btn primary" onclick="ResourceSystem.transfer('deposit')">⬆️ Deposit</button>
                    <button class="action-btn accent" onclick="ResourceSystem.transfer('withdraw')">⬇️ Withdraw</button>
                </div>
            </div>
        `;
    },

    _renderStructureCard(s) {
        return `
            <div class="domain-card" style="border-left:3px solid #f59e0b;">
                <div class="dc-header">
                    <span class="dc-name">${s.structure_type.replace(/_/g, ' ')}</span>
                    <span class="dc-count">Lv ${s.level}</span>
                </div>
                <div class="dc-meta">
                    <span>Zone: ${s.zone_code || s.zone_id}</span>
                    <span>HP: ${s.health}/${s.max_health || 100}</span>
                    ${s.production_rate ? `<span>⚙️ ${s.production_rate}/hr</span>` : ''}
                </div>
            </div>
        `;
    },

    _renderBuildForm() {
        const zones = GameEngine.state.zones || [];
        const types = ['barracks', 'watchtower', 'supply_depot', 'comms_relay', 'med_station', 'refinery', 'research_lab'];
        return `
            <div class="build-grid">
                ${types.map(t => `
                    <button class="build-btn" onclick="ResourceSystem.showBuildDialog('${t}')">
                        ${this._buildIcon(t)}<br><span style="font-size:10px;">${t.replace(/_/g, ' ')}</span>
                    </button>
                `).join('')}
            </div>
            <div id="buildDialog" style="display:none;margin-top:8px;">
                <select class="form-input" id="buildZone">
                    <option value="">Select Zone...</option>
                    ${zones.map(z => `<option value="${z.id}">${z.zone_code} — ${z.name}</option>`).join('')}
                </select>
                <input type="hidden" id="buildType" value="">
                <button class="action-btn primary" style="margin-top:8px;width:100%;" onclick="ResourceSystem.build()">🔨 Build (costs 50 materials)</button>
            </div>
        `;
    },

    _buildIcon(type) {
        const icons = {
            barracks: '🏠', watchtower: '🗼', supply_depot: '📦',
            comms_relay: '📡', med_station: '🏥', refinery: '⛽', research_lab: '🔬',
        };
        return icons[type] || '🏗️';
    },

    showBuildDialog(type) {
        document.getElementById('buildType').value = type;
        document.getElementById('buildDialog').style.display = 'block';
        AlfredCommand.toast(`Selected: ${type.replace(/_/g, ' ')} — pick a zone`);
    },

    async transfer(direction) {
        const resourceType = document.getElementById('transferType')?.value;
        const qty = parseInt(document.getElementById('transferQty')?.value) || 10;
        if (!resourceType) return;

        const result = await GameAPI.supplyTransfer(direction, resourceType, qty);
        if (result.success) {
            AlfredCommand.toast(`${direction === 'deposit' ? '⬆️' : '⬇️'} ${qty} ${resourceType.replace(/_/g, ' ')} transferred`);
            await AlfredCommand.refreshGameState();
            document.getElementById('sidePanelBody').innerHTML = this.renderPanel();
        } else {
            AlfredCommand.toast(result.error || 'Transfer failed.');
        }
    },

    async build() {
        const type = document.getElementById('buildType')?.value;
        const zoneId = document.getElementById('buildZone')?.value;
        if (!type || !zoneId) {
            AlfredCommand.toast('Select a structure type and zone.');
            return;
        }

        const result = await GameAPI.buildStructure(parseInt(zoneId), type);
        if (result.success) {
            AlfredCommand.toast(`🔨 ${type.replace(/_/g, ' ')} built!`);
            await AlfredCommand.refreshGameState();
            document.getElementById('sidePanelBody').innerHTML = this.renderPanel();
            if (typeof Environments !== 'undefined') {
                Environments.buildPlayerStructures(GameEngine.scene, GameEngine.state);
            }
        } else {
            AlfredCommand.toast(result.error || 'Build failed.');
        }
    },

    _fmt(n) {
        if (n >= 1e6) return (n / 1e6).toFixed(1) + 'M';
        if (n >= 1e3) return (n / 1e3).toFixed(1) + 'K';
        return n;
    },
};
