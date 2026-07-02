/* ═══════════════════════════════════════════════════════════════
   ALFRED COMMAND — API Client
   All communication with the backend pulse.php API
   ═══════════════════════════════════════════════════════════════ */

const GameAPI = {
    BASE: '/api/pulse.php',

    async call(action, params = {}, method = 'GET') {
        const url = new URL(this.BASE, window.location.origin);
        url.searchParams.set('action', action);

        const opts = { credentials: 'include' };

        if (method === 'GET') {
            Object.keys(params).forEach(k => url.searchParams.set(k, params[k]));
        } else {
            opts.method = 'POST';
            opts.headers = { 'Content-Type': 'application/json' };
            opts.body = JSON.stringify(params);
        }

        const res = await fetch(url.toString(), opts);
        const data = await res.json();
        if (!res.ok && data.error) throw new Error(data.error);
        return data;
    },

    // Game State
    getGameState: ()        => GameAPI.call('game-state'),
    getTerritoryStatus: ()  => GameAPI.call('territory-status'),
    getNetworkOverview: ()  => GameAPI.call('network-overview'),

    // Sessions
    startSession: (type, vrMode, device) =>
        GameAPI.call('start-session', { session_type: type, vr_mode: vrMode, device }, 'POST'),
    endSession: (sessionId) =>
        GameAPI.call('end-session', { session_id: sessionId }, 'POST'),

    // Missions
    getMissionList: (type)  => GameAPI.call('mission-list', type ? { type } : {}),
    startMission: (templateCode, zoneId, sessionId) =>
        GameAPI.call('start-mission', { template_code: templateCode, zone_id: zoneId, session_id: sessionId }, 'POST'),
    getMissionStatus: (missionId) =>
        GameAPI.call('mission-status', { mission_id: missionId }),
    updateMission: (missionId, progress, status) =>
        GameAPI.call('mission-status', { mission_id: missionId, progress, status }, 'POST'),

    // Agents
    deployAgents: (zoneId, domain, count, role, missionId) =>
        GameAPI.call('deploy-agents', { zone_id: zoneId, domain, count, role, mission_id: missionId }, 'POST'),
    recallAgents: (deploymentId) =>
        GameAPI.call('recall-agents', { deployment_id: deploymentId }, 'POST'),
    getAgentDomains: () => GameAPI.call('agent-domains'),

    // Territory
    captureZone: (zoneId) =>
        GameAPI.call('capture-zone', { zone_id: zoneId }, 'POST'),

    // Resources
    supplyTransfer: (resourceType, quantity, direction, zoneId) =>
        GameAPI.call('supply-transfer', { resource_type: resourceType, quantity, direction, zone_id: zoneId }, 'POST'),

    // Building
    buildStructure: (zoneId, plotId, type, name) =>
        GameAPI.call('build-structure', { zone_id: zoneId, plot_id: plotId, structure_type: type, name }, 'POST'),

    // War Games
    getWarGames: () => GameAPI.call('war-games'),

    // Battle Log
    getBattleLog: (limit) => GameAPI.call('battle-log', limit ? { limit } : {}),

    // Leaderboard
    getLeaderboard: (metric) => GameAPI.call('leaderboard', metric ? { metric } : {}),
};
