// launch-event-engine.js — Countdown timer for the Launch Event page
(function () {
    'use strict';

    function updateCountdown() {
        const now = new Date();
        const target = new Date();
        target.setHours(18, 0, 0, 0);
        if (now > target) target.setDate(target.getDate() + 1);

        const diff = target - now;
        const hours = Math.floor(diff / 3600000);
        const mins = Math.floor((diff % 3600000) / 60000);
        const secs = Math.floor((diff % 60000) / 1000);

        document.getElementById('cd-hours').textContent = String(hours).padStart(2, '0');
        document.getElementById('cd-mins').textContent = String(mins).padStart(2, '0');
        document.getElementById('cd-secs').textContent = String(secs).padStart(2, '0');
    }
    updateCountdown();
    setInterval(updateCountdown, 1000);
})();
