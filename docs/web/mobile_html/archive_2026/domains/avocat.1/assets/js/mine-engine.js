// mine-engine.js — Live stats + earnings calculator for the Mining page
(function () {
    'use strict';

    // Live stats
    (async function() {
        try {
            const r = await fetch('/api/mining.php?action=stats');
            const d = await r.json();
            if (d.stats) {
                document.getElementById('liveMiners').textContent = (d.stats.unique_miners || 0).toLocaleString();
                const gsm = d.stats.total_gsm_distributed || 0;
                document.getElementById('liveGSM').textContent = gsm >= 1000000 ? (gsm/1000000).toFixed(1) + 'M' : gsm >= 1000 ? (gsm/1000).toFixed(1) + 'K' : Math.round(gsm);
            }
        } catch(e) {}
    })();

    // Earnings calculator
    function updateCalc() {
        const hours = parseInt(document.getElementById('calcHours').value);
        document.getElementById('calcHoursVal').textContent = hours;
        const daily = Math.round(hours * 65); // ~65 GSM per hour of browsing
        document.getElementById('calcDaily').textContent = daily.toLocaleString();
        document.getElementById('calcWeekly').textContent = (daily * 7).toLocaleString();
        document.getElementById('calcMonthly').textContent = (daily * 30).toLocaleString();
    }
    updateCalc();

    // Expose for slider onchange
    window.updateCalc = updateCalc;
})();
