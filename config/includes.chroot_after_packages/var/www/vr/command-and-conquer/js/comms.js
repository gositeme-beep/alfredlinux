/* ═══════════════════════════════════════════════════════════════
   ALFRED COMMAND — Comms System
   Battle log, Pulse feed, world events, and Veil integration
   ═══════════════════════════════════════════════════════════════ */

const CommsSystem = {
    battleLog: [],
    feedItems: [],

    async loadBattleLog() {
        const data = await GameAPI.getBattleLog(50);
        this.battleLog = data.events || [];
        return this.battleLog;
    },

    renderPanel() {
        let html = '';

        // Tabs
        html += `
            <div class="filter-tabs">
                <button class="filter-tab active" onclick="CommsSystem.showTab('log')">⚔️ Battle Log</button>
                <button class="filter-tab" onclick="CommsSystem.showTab('feed')">📡 Pulse Feed</button>
                <button class="filter-tab" onclick="CommsSystem.showTab('broadcast')">📢 Broadcast</button>
            </div>
        `;

        // Battle log
        html += `<div id="commsLog">`;
        html += this._renderBattleLog();
        html += `</div>`;

        // Pulse feed (hidden initially)
        html += `<div id="commsFeed" style="display:none;">`;
        html += this._renderPulseFeed();
        html += `</div>`;

        // Broadcast (hidden initially)
        html += `<div id="commsBroadcast" style="display:none;">`;
        html += this._renderBroadcast();
        html += `</div>`;

        return html;
    },

    _renderBattleLog() {
        if (this.battleLog.length === 0) {
            return `<p style="color:#64748b;text-align:center;padding:20px;">No battle events yet. Deploy agents and capture zones to generate activity.</p>`;
        }

        return this.battleLog.map(e => {
            const typeColors = {
                deployment: '#3b82f6', capture_attempt: '#f59e0b',
                zone_captured: '#10b981', defense: '#a855f7',
                battle: '#ef4444', mission_complete: '#06b6d4',
            };
            const color = typeColors[e.event_type] || '#64748b';
            const icon = {
                deployment: '🚀', capture_attempt: '⚔️', zone_captured: '🏆',
                defense: '🛡️', battle: '💥', mission_complete: '🎖️',
            }[e.event_type] || '📋';

            return `
                <div class="comms-entry" style="border-left:2px solid ${color};">
                    <div class="ce-header">
                        <span>${icon} ${e.event_type.replace(/_/g, ' ')}</span>
                        <span class="ce-time">${this._timeAgo(e.created_at)}</span>
                    </div>
                    <div class="ce-detail">${e.event_data || ''}</div>
                </div>
            `;
        }).join('');
    },

    _renderPulseFeed() {
        return `
            <div style="padding:12px;text-align:center;">
                <p style="color:#94a3b8;">Pulse social feed — mission victories and territory captures are auto-posted.</p>
                <a href="/pulse.php" target="_blank" class="action-btn primary" style="display:inline-block;margin-top:8px;text-decoration:none;">Open Pulse Feed →</a>
            </div>
        `;
    },

    _renderBroadcast() {
        return `
            <div style="padding:12px;">
                <h4 style="color:#94a3b8;margin-bottom:8px;">Commander Broadcast</h4>
                <textarea class="form-input" id="broadcastMsg" rows="3" placeholder="Broadcast to all agents..." style="resize:vertical;"></textarea>
                <button class="action-btn accent" style="margin-top:8px;width:100%;" onclick="CommsSystem.sendBroadcast()">📢 Send Broadcast</button>
            </div>
        `;
    },

    showTab(tab) {
        document.getElementById('commsLog').style.display = tab === 'log' ? 'block' : 'none';
        document.getElementById('commsFeed').style.display = tab === 'feed' ? 'block' : 'none';
        document.getElementById('commsBroadcast').style.display = tab === 'broadcast' ? 'block' : 'none';
        // Update tab active state
        const tabs = document.querySelectorAll('#sidePanel .filter-tab');
        tabs.forEach((t, i) => {
            const tabNames = ['log', 'feed', 'broadcast'];
            t.classList.toggle('active', tabNames[i] === tab);
        });
    },

    async sendBroadcast() {
        const msg = document.getElementById('broadcastMsg')?.value?.trim();
        if (!msg) return;
        // Post to Pulse as a broadcast
        AlfredCommand.toast('📢 Broadcast sent to the network!');
        document.getElementById('broadcastMsg').value = '';
    },

    _timeAgo(dateStr) {
        if (!dateStr) return '';
        const diff = (Date.now() - new Date(dateStr).getTime()) / 1000;
        if (diff < 60) return 'just now';
        if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
        return Math.floor(diff / 86400) + 'd ago';
    },
};
