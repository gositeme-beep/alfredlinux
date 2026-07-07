// ═══ Live Viewer/Member Stats ═══
(function() {
    async function fetchLiveStats() {
        try {
            const resp = await fetch('/api/game-ecosystem.php?action=live-stats');
            const data = await resp.json();
            if (!data.success) return;

            // Update hero stats
            const p = data.platform || {};
            const statOnline = document.getElementById('statOnline');
            const statMembers = document.getElementById('statMembers');
            const statGames = document.getElementById('statGames');
            if (statOnline) statOnline.textContent = p.total_online || 0;
            if (statMembers) statMembers.textContent = (p.total_members || 0).toLocaleString();
            if (statGames) statGames.textContent = (p.total_games || 0).toLocaleString();

            // Update per-game badges
            const games = data.games || {};
            document.querySelectorAll('.gm-live-badge').forEach(badge => {
                const game = badge.dataset.game;
                const info = games[game];
                const countEl = badge.querySelector('.live-count');
                if (countEl) {
                    const total = info ? info.total : 0;
                    const members = info ? info.members : 0;
                    countEl.textContent = total;
                    // Show member count if any
                    let memberTag = badge.querySelector('.gm-member-tag');
                    if (members > 0) {
                        if (!memberTag) {
                            memberTag = document.createElement('span');
                            memberTag.className = 'gm-member-tag';
                            badge.appendChild(memberTag);
                        }
                        memberTag.textContent = '(' + members + ' members)';
                    } else if (memberTag) {
                        memberTag.remove();
                    }
                }
            });
        } catch(e) {}
    }

    // Send heartbeat for games page visitors
    async function sendHeartbeat() {
        try {
            await fetch('/api/game-ecosystem.php?action=heartbeat', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ game: 'arcade', role: 'viewer' })
            });
        } catch(e) {}
    }

    // Initial load + intervals
    fetchLiveStats();
    sendHeartbeat();
    setInterval(fetchLiveStats, 15000);
    setInterval(sendHeartbeat, 30000);

    // Hidden discovery — type "kingdom" anywhere on the page
    let kBuf = '';
    document.addEventListener('keydown', function(e) {
        kBuf += e.key.toLowerCase();
        if (kBuf.length > 7) kBuf = kBuf.slice(-7);
        if (kBuf === 'kingdom') {
            kBuf = '';
            const overlay = document.createElement('div');
            overlay.style.cssText = 'position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,.95);display:flex;flex-direction:column;align-items:center;justify-content:center;opacity:0;transition:opacity 1.5s;cursor:pointer';
            overlay.innerHTML = '<div style="font-size:4rem;color:#FFD700;text-shadow:0 0 60px rgba(255,215,0,.5);margin-bottom:1rem">✝</div>' +
                '<div style="font-size:1.8rem;color:#FFD700;font-family:Georgia,serif;letter-spacing:3px;opacity:0;animation:kFade 2s ease forwards .8s">The Kingdom of God</div>' +
                '<div style="color:rgba(255,215,0,.4);font-size:.85rem;font-family:Georgia,serif;font-style:italic;margin-top:1rem;opacity:0;animation:kFade 2s ease forwards 2s">"Seek and you shall find"</div>' +
                '<a href="/vr/kingdom/" style="color:rgba(255,215,0,.25);font-size:.7rem;text-decoration:none;margin-top:2rem;opacity:0;animation:kFade 2s ease forwards 3.5s;font-family:system-ui">Enter the Sanctuary →</a>' +
                '<style>@keyframes kFade{to{opacity:1}}</style>';
            overlay.onclick = function() { overlay.style.opacity = '0'; setTimeout(function() { overlay.remove(); }, 1500); };
            document.body.appendChild(overlay);
            requestAnimationFrame(function() { overlay.style.opacity = '1'; });
        }
    });
})();
