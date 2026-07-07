/**
 * GoSiteMe Veil v2 — Alfred AI Integration
 *
 * Alfred as an encrypted contact inside Comms.
 * Messages to Alfred go through the existing alfred-chat API.
 * The response is delivered back as an encrypted message.
 *
 * Alfred is special:
 * - Virtual contact (client_id=0, displayed as "Alfred AI")
 * - Messages sent to /api/comms.php?action=alfred — server-side relay
 * - Server decrypts (since Alfred is server-side AI), processes, re-encrypts response
 * - In the future: Alfred can proactively push encrypted alerts
 *
 * Why not pure E2E for Alfred?
 * Alfred IS the server. The AI runs server-side. So Alfred messages are
 * encrypted in transit (TLS) + encrypted at rest, but the server must see
 * plaintext to process AI. This is clearly communicated to the user.
 */
const CommsAlfred = (() => {
    'use strict';

    const ALFRED_ID = 0;  // Virtual contact ID
    const ALFRED_AGENTS = [
        'alfred', 'nova', 'sage', 'atlas', 'cipher', 'pulse', 'pierre', 'sofia',
        'luna', 'felix', 'maya', 'oscar', 'ivy', 'rex', 'cleo', 'kai'
    ];
    let currentAgent = 'alfred';
    let isProcessing = false;

    /**
     * Send a message to Alfred through the encrypted comms interface
     */
    async function sendToAlfred(message, agent = 'alfred') {
        if (isProcessing) return null;
        if (!ALFRED_AGENTS.includes(agent)) agent = 'alfred';
        currentAgent = agent;
        isProcessing = true;

        try {
            const csrfToken = window.CommsApp?.csrfToken || '';
            const resp = await fetch('/api/comms.php?action=alfred', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken,
                },
                body: JSON.stringify({
                    message,
                    agent,
                }),
            });

            const data = await resp.json();
            if (data.csrf_token) window.CommsApp.csrfToken = data.csrf_token;

            isProcessing = false;
            return data;
        } catch (err) {
            isProcessing = false;
            throw err;
        }
    }

    /**
     * Get Alfred's proactive notifications/alerts
     */
    async function getAlerts() {
        try {
            const resp = await fetch('/api/comms.php?action=alfred_alerts', {
                credentials: 'same-origin',
            });
            return await resp.json();
        } catch (err) {
            return { success: false, alerts: [] };
        }
    }

    /**
     * Get available Alfred agents
     */
    function getAgents() {
        return [
            { id: 'alfred', name: 'Alfred', icon: 'robot', desc: 'General AI assistant' },
            { id: 'cipher', name: 'Cipher', icon: 'shield-halved', desc: 'Security & encryption specialist' },
            { id: 'nova', name: 'Nova', icon: 'star', desc: 'Creative & design' },
            { id: 'atlas', name: 'Atlas', icon: 'globe', desc: 'Infrastructure & hosting' },
            { id: 'sage', name: 'Sage', icon: 'brain', desc: 'Research & analytics' },
            { id: 'pulse', name: 'Pulse', icon: 'heart-pulse', desc: 'System monitoring' },
            { id: 'pierre', name: 'Pierre', icon: 'palette', desc: 'UI/UX design' },
            { id: 'sofia', name: 'Sofia', icon: 'code', desc: 'Development & code' },
        ];
    }

    /**
     * Render Alfred's contact in the sidebar
     */
    function getAlfredContact() {
        return {
            contact_id: ALFRED_ID,
            firstname: 'Alfred',
            lastname: 'AI',
            verified: true,
            blocked: false,
            is_alfred: true,
            last_message_at: new Date().toISOString(),
        };
    }

    /**
     * Render agent selector for Alfred chat
     */
    function renderAgentSelector(containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;

        const agents = getAgents();
        container.innerHTML = `
            <div class="alfred-agent-selector">
                ${agents.map(a => `
                    <button class="alfred-agent-btn ${a.id === currentAgent ? 'active' : ''}" 
                            data-agent="${a.id}" title="${a.desc}">
                        <i class="fas fa-${a.icon}"></i>
                        <span>${a.name}</span>
                    </button>
                `).join('')}
            </div>
        `;

        container.querySelectorAll('.alfred-agent-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                currentAgent = btn.dataset.agent;
                container.querySelectorAll('.alfred-agent-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            });
        });
    }

    /**
     * Format Alfred's response with markdown-light rendering
     */
    function formatResponse(text) {
        if (!text) return '';
        // Basic markdown
        let html = text
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/```(\w*)\n([\s\S]*?)```/g, '<pre><code class="lang-$1">$2</code></pre>')
            .replace(/`([^`]+)`/g, '<code>$1</code>')
            .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
            .replace(/\*([^*]+)\*/g, '<em>$1</em>')
            .replace(/\n/g, '<br>');
        return html;
    }

    return {
        ALFRED_ID,
        sendToAlfred,
        getAlerts,
        getAgents,
        getAlfredContact,
        renderAgentSelector,
        formatResponse,
        get currentAgent() { return currentAgent; },
        get isProcessing() { return isProcessing; },
    };
})();
