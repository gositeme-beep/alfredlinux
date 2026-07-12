/**
 * GSM Alfred OS — JavaScript Client SDK v1.0
 * Vanilla JS client for all Alfred OS backend APIs
 * Zero dependencies — works with native fetch
 */
class AlfredOS {
    constructor(opts = {}) {
        this.base = opts.base || '/api/agentos';
        this.ws = null;
        this.listeners = {};
        this._wsRetries = 0;
    }

    // ── Core Fetch ──────────────────────────────────────────
    async _call(module, action, params = {}, method = 'GET', body = null) {
        const qs = new URLSearchParams({ action, ...params }).toString();
        const url = `${this.base}/${module}.php?${qs}`;
        const opts = { method, credentials: 'include' };
        if (body && method === 'POST') {
            opts.headers = { 'Content-Type': 'application/json' };
            opts.body = JSON.stringify(body);
        }
        const res = await fetch(url, opts);
        const data = await res.json();
        if (!data.ok) throw new Error(data.error || 'Alfred OS request failed');
        return data;
    }

    // ── Capabilities ────────────────────────────────────────
    async capabilities(category) {
        const p = category ? { category } : {};
        return this._call('capabilities', 'list', p);
    }

    // ── Skills ──────────────────────────────────────────────
    async skills() { return this._call('skills', 'list'); }
    async skill(id) { return this._call('skills', 'get', { id }); }
    async createSkill(data) { return this._call('skills', 'create', {}, 'POST', data); }
    async executeSkill(skillId, input) {
        return this._call('skills', 'execute', {}, 'POST', { skill_id: skillId, input });
    }
    async suggestSkills(goal) { return this._call('skills', 'suggest', { goal }); }

    // ── Tasks ───────────────────────────────────────────────
    async tasks(status, limit = 50) {
        const p = { limit };
        if (status) p.status = status;
        return this._call('tasks', 'list', p);
    }
    async task(id) { return this._call('tasks', 'get', { id }); }
    async createTask(goal, opts = {}) {
        return this._call('tasks', 'create', {}, 'POST', { goal, ...opts });
    }
    async updateTaskStatus(taskId, status) {
        return this._call('tasks', 'update_status', {}, 'POST', { task_id: taskId, status });
    }

    // ── Memory ──────────────────────────────────────────────
    async storeMemory(type, data) {
        return this._call('memory', 'store', { type }, 'POST', data);
    }
    async recallMemory(type, opts = {}) {
        return this._call('memory', 'recall', { type, ...opts });
    }
    async searchMemory(query) {
        return this._call('memory', 'search', { q: query });
    }
    async memoryStats(agentId) {
        const p = agentId ? { agent_id: agentId } : {};
        return this._call('memory', 'stats', p);
    }
    async consolidateMemory() {
        return this._call('memory', 'consolidate', {}, 'POST', {});
    }

    // ── World State ─────────────────────────────────────────
    async worldState(worldId) {
        const p = worldId ? { world_id: worldId } : {};
        return this._call('world-state', 'get', p);
    }
    async updateWorldState(updates, worldId) {
        return this._call('world-state', 'update', {}, 'POST', { updates, world_id: worldId });
    }
    async worldEntities(worldId) {
        const p = worldId ? { world_id: worldId } : {};
        return this._call('world-state', 'entities', p);
    }
    async spawnEntity(data) {
        return this._call('world-state', 'spawn', {}, 'POST', data);
    }
    async worldDrifts(worldId) {
        return this._call('world-state', 'drifts', { world_id: worldId || 'default' });
    }

    // ── Policy ──────────────────────────────────────────────
    async policies() { return this._call('policy', 'list'); }
    async policy(id) { return this._call('policy', 'get', { id }); }
    async checkPolicy(capabilityId, riskLevel, opts = {}) {
        return this._call('policy', 'check', {}, 'POST', {
            capability_id: capabilityId, risk_level: riskLevel, ...opts
        });
    }
    async createPolicy(data) {
        return this._call('policy', 'create', {}, 'POST', data);
    }
    async approvals(status) {
        const p = status ? { status } : {};
        return this._call('policy', 'approvals', p);
    }
    async approveAction(approvalId, reason) {
        return this._call('policy', 'approve', {}, 'POST', { approval_id: approvalId, reason });
    }
    async denyAction(approvalId, reason) {
        return this._call('policy', 'deny', {}, 'POST', { approval_id: approvalId, reason });
    }
    async killSwitch() {
        return this._call('policy', 'kill_switch', {}, 'POST', {});
    }
    async killTask(taskId, reason) {
        return this._call('policy', 'kill_task', {}, 'POST', { task_id: taskId, reason });
    }
    async rollback(taskId, reason) {
        return this._call('policy', 'rollback', {}, 'POST', { task_id: taskId, reason });
    }
    async expireApprovals() {
        return this._call('policy', 'expire_approvals', {}, 'POST', {});
    }

    // ── Simulation ──────────────────────────────────────────
    async simulate(capabilityId, input, taskId) {
        return this._call('simulation', 'run', {}, 'POST', {
            capability_id: capabilityId, input, task_id: taskId
        });
    }
    async simulations(limit = 20) {
        return this._call('simulation', 'list', { limit });
    }

    // ── Audit ───────────────────────────────────────────────
    async auditStats() { return this._call('audit', 'stats'); }
    async auditLog(filters = {}) { return this._call('audit', 'list', filters); }
    async auditTrace(traceId) { return this._call('audit', 'trace', { trace_id: traceId }); }    async auditReplay(taskId) { return this._call('audit', 'replay', { task_id: taskId }); }
    async auditExport(taskId) { return this._call('audit', 'export', { task_id: taskId }); }
    async auditTimeline(hours) { return this._call('audit', 'timeline', { hours: hours || 24 }); }
    async auditAnomalies() { return this._call('audit', 'anomalies'); }
    // ── Bridge / Devices ────────────────────────────────────
    async devices() { return this._call('bridge', 'list'); }
    async device(id) { return this._call('bridge', 'get', { id }); }
    async registerDevice(data) { return this._call('bridge', 'register', {}, 'POST', data); }
    async deviceCommand(deviceId, command, params) {
        return this._call('bridge', 'command', {}, 'POST', {
            device_id: deviceId, command, params
        });
    }
    async emergencyStop(deviceId) {
        return this._call('bridge', 'emergency_stop', {}, 'POST', { device_id: deviceId });
    }
    async fleetStatus() { return this._call('bridge', 'fleet_status'); }
    async twinSync(deviceId, twinState, opts = {}) {
        return this._call('bridge', 'twin_sync', {}, 'POST', { device_id: deviceId, twin_state: twinState, ...opts });
    }
    async twinSnapshot(deviceId, trigger) {
        return this._call('bridge', 'twin_snapshot', {}, 'POST', { device_id: deviceId, trigger });
    }
    async twinSnapshots(deviceId, limit) { return this._call('bridge', 'twin_snapshot', { id: deviceId, limit }); }
    async telemetryHistory(deviceId, metric, hours) {
        return this._call('bridge', 'telemetry_history', { id: deviceId, metric, hours: hours || 24 });
    }
    async deviceGroups() { return this._call('bridge', 'groups'); }
    async createDeviceGroup(data) { return this._call('bridge', 'group_create', {}, 'POST', data); }
    async groupCommand(groupId, command, params) {
        return this._call('bridge', 'group_command', {}, 'POST', { group_id: groupId, command, params });
    }
    async sensorPipeline(minutes, group) { return this._call('bridge', 'sensor_pipeline', { minutes, group }); }

    // ── Runtime (Agent Loop) ────────────────────────────────
    async executeAgent(goal, opts = {}) {
        return this._call('runtime', 'execute', {}, 'POST', { goal, ...opts });
    }
    async sandboxGoal(goal, opts = {}) {
        return this._call('runtime', 'sandbox', {}, 'POST', { goal, ...opts });
    }
    async taskStatus(taskId) {
        return this._call('runtime', 'status', { task_id: taskId });
    }
    async pauseTask(taskId) {
        return this._call('runtime', 'pause', {}, 'POST', { task_id: taskId });
    }
    async resumeTask(taskId) {
        return this._call('runtime', 'resume', {}, 'POST', { task_id: taskId });
    }

    // ── WebSocket (Real-time) ───────────────────────────────
    connectWS() {
        if (this.ws && this.ws.readyState <= 1) return;
        const proto = location.protocol === 'https:' ? 'wss' : 'ws';
        this.ws = new WebSocket(`${proto}://${location.host}:3010/agentos`);
        this.ws.onmessage = (e) => {
            try {
                const msg = JSON.parse(e.data);
                const handlers = this.listeners[msg.type] || [];
                handlers.forEach(fn => fn(msg.data));
                (this.listeners['*'] || []).forEach(fn => fn(msg));
            } catch (_) {}
        };
        this.ws.onclose = () => {
            if (this._wsRetries < 5) {
                this._wsRetries++;
                setTimeout(() => this.connectWS(), 2000 * this._wsRetries);
            }
        };
        this.ws.onopen = () => { this._wsRetries = 0; };
    }

    on(event, fn) {
        (this.listeners[event] = this.listeners[event] || []).push(fn);
        return this;
    }

    off(event, fn) {
        if (!this.listeners[event]) return;
        this.listeners[event] = this.listeners[event].filter(f => f !== fn);
    }
}

// Global singleton
window.agentOS = new AlfredOS();
