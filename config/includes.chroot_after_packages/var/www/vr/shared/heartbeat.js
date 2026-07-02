/**
 * GoSiteMe — Universal Game Presence Heartbeat
 * Auto-detects game slug from URL, reports presence every 30s.
 * Include via: <script src="/vr/shared/heartbeat.js"></script>
 */
(function() {
    'use strict';

    var pathParts = location.pathname.split('/vr/');
    var slug = (pathParts[1] || 'unknown').replace(/\/.*$/, '').replace(/[^a-z0-9-]/gi, '');
    if (!slug) slug = 'unknown';

    var role = 'viewer';
    var API = '/api/game-ecosystem.php?action=heartbeat';

    function sendBeat() {
        try {
            fetch(API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ game: slug, role: role })
            }).catch(function() {});
        } catch (e) {}
    }

    // Expose role upgrade for games to call when user starts playing
    window.gsmSetRole = function(newRole) {
        if (newRole === 'player' || newRole === 'viewer') role = newRole;
    };

    // Expose game slug override (for games with sub-modes)
    window.gsmSetGame = function(newSlug) {
        slug = (newSlug || slug).replace(/[^a-z0-9-]/gi, '');
    };

    // Initial beat + interval
    sendBeat();
    setInterval(sendBeat, 30000);

    // Upgrade to "player" automatically on first user interaction
    var upgraded = false;
    function autoUpgrade() {
        if (!upgraded) {
            upgraded = true;
            role = 'player';
            sendBeat();
        }
    }
    document.addEventListener('click', autoUpgrade, { once: true });
    document.addEventListener('keydown', autoUpgrade, { once: true });
    document.addEventListener('touchstart', autoUpgrade, { once: true });
})();
