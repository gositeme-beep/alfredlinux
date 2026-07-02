/* ═══════════════════════════════════════════════════════════════
   ALFRED COMMAND — Agent Deployment System
   Browse 137 domains, deploy/recall agents to zones
   ═══════════════════════════════════════════════════════════════ */

const AgentSystem = {
    domains: [],
    deployments: [],
    selectedZone: null,

    async loadDomains() {
        const data = await GameAPI.getAgentDomains();
        this.domains = data.domains || [];
        return this.domains;
    },

    renderPanel(targetZone) {
        this.selectedZone = targetZone || null;
        const deployments = GameEngine.state.deployments || [];
        let html = '';

        // Active deployments
        if (deployments.length > 0) {
            html += `<h4 style="color:#10b981;margin-bottom:8px;">Active Deployments (${deployments.length})</h4>`;
            deployments.forEach(d => {
                html += this._renderDeploymentCard(d);
            });
            html += `<hr style="border-color:#2d3a5c;margin:16px 0;">`;
        }

        // Deploy form
        html += `<h4 style="color:#94a3b8;margin-bottom:8px;">Deploy Agents</h4>`;
        html += this._renderDeployForm();

        // Domain browser
        html += `<hr style="border-color:#2d3a5c;margin:16px 0;">`;
        html += `<h4 style="color:#94a3b8;margin-bottom:8px;">Agent Domains (${this.domains.length})</h4>`;
        html += `<input type="text" class="form-input" placeholder="Search domains..." oninput="AgentSystem.filterDomains(this.value)" id="domainSearch">`;
        html += `<div id="domainList">`;
        this.domains.slice(0, 30).forEach(d => {
            html += this._renderDomainCard(d);
        });
        if (this.domains.length > 30) {
            html += `<p style="color:#64748b;font-size:12px;text-align:center;">Showing 30 of ${this.domains.length} domains. Search to filter.</p>`;
        }
        html += `</div>`;

        return html;
    },

    _renderDeploymentCard(d) {
        return `
            <div class="domain-card" style="border-left:3px solid #10b981;">
                <div class="dc-header">
                    <span class="dc-name">${d.domain || d.agent_domain}</span>
                    <span class="dc-count">${d.agent_count} agents</span>
                </div>
                <div class="dc-meta">
                    <span>Zone: ${d.zone_code || 'N/A'}</span>
                    <span>Role: ${d.role || 'assault'}</span>
                    <span>Status: ${d.status}</span>
                </div>
                <button class="action-btn danger" onclick="AgentSystem.recall('${d.deployment_id}')">Recall</button>
            </div>
        `;
    },

    _renderDeployForm() {
        const zones = GameEngine.state.zones || [];
        const roles = ['assault', 'defense', 'recon', 'support', 'engineer'];

        return `
            <div class="deploy-form" style="display:flex;flex-direction:column;gap:8px;">
                <select class="form-input" id="deployZone">
                    <option value="">Select Zone...</option>
                    ${zones.map(z => `<option value="${z.zone_code}" ${this.selectedZone === z.zone_code ? 'selected' : ''}>${z.zone_code} — ${z.name}</option>`).join('')}
                </select>
                <select class="form-input" id="deployDomain">
                    <option value="">Select Domain...</option>
                    ${this.domains.map(d => `<option value="${d.domain}">${d.domain} (${this._formatCount(d.agent_count)})</option>`).join('')}
                </select>
                <select class="form-input" id="deployRole">
                    ${roles.map(r => `<option value="${r}">${r}</option>`).join('')}
                </select>
                <div style="display:flex;gap:8px;align-items:center;">
                    <input type="number" class="form-input" id="deployCount" min="1" max="10000" value="100" placeholder="Count" style="flex:1;">
                    <button class="action-btn primary" onclick="AgentSystem.deploy()">🚀 Deploy</button>
                </div>
            </div>
        `;
    },

    _renderDomainCard(d) {
        return `
            <div class="domain-card" onclick="AgentSystem.selectDomain('${d.domain}')">
                <div class="dc-header">
                    <span class="dc-name">${d.domain}</span>
                    <span class="dc-count">${this._formatCount(d.agent_count)}</span>
                </div>
            </div>
        `;
    },

    selectDomain(domain) {
        const sel = document.getElementById('deployDomain');
        if (sel) sel.value = domain;
    },

    filterDomains(query) {
        const q = query.toLowerCase();
        const filtered = q ? this.domains.filter(d => d.domain.toLowerCase().includes(q)) : this.domains.slice(0, 30);
        const container = document.getElementById('domainList');
        if (!container) return;
        container.innerHTML = filtered.slice(0, 30).map(d => this._renderDomainCard(d)).join('');
    },

    async deploy() {
        const zoneCode = document.getElementById('deployZone')?.value;
        const domain = document.getElementById('deployDomain')?.value;
        const role = document.getElementById('deployRole')?.value;
        const count = parseInt(document.getElementById('deployCount')?.value) || 100;

        if (!zoneCode || !domain) {
            AlfredCommand.toast('Select a zone and domain first.');
            return;
        }

        // Find zone ID from code
        const zone = (GameEngine.state.zones || []).find(z => z.zone_code === zoneCode);
        if (!zone) return;

        AlfredCommand.toast(`Deploying ${count} agents from ${domain}...`);
        const result = await GameAPI.deployAgents(zone.id, domain, count, role);
        if (result.success) {
            AlfredCommand.toast(`✅ ${count} ${domain} agents deployed to ${zoneCode}!`);
            await AlfredCommand.refreshGameState();
            document.getElementById('sidePanelBody').innerHTML = this.renderPanel();
            // Refresh 3D markers
            if (typeof Environments !== 'undefined') {
                Environments.buildTerritoryMarkers(GameEngine.scene, GameEngine.state);
            }
        } else {
            AlfredCommand.toast(result.error || 'Deployment failed.');
        }
    },

    async recall(deploymentId) {
        const result = await GameAPI.recallAgents(deploymentId);
        if (result.success) {
            AlfredCommand.toast('Agents recalled.');
            await AlfredCommand.refreshGameState();
            document.getElementById('sidePanelBody').innerHTML = this.renderPanel();
        }
    },

    _formatCount(n) {
        if (n >= 1e6) return (n / 1e6).toFixed(1) + 'M';
        if (n >= 1e3) return (n / 1e3).toFixed(1) + 'K';
        return n;
    },
};
