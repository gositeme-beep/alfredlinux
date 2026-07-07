// intelligence-director-engine.js — Panel switching + table filter
(function () {
    'use strict';

    function showPanel(id) {
        document.querySelectorAll('.intel-panel').forEach(p => p.classList.remove('active'));
        document.querySelectorAll('.intel-tab').forEach(t => t.classList.remove('active'));
        document.getElementById('panel-' + id)?.classList.add('active');
        // Use implicit event from onclick handler
        if (typeof event !== 'undefined') {
            event.target.closest('.intel-tab')?.classList.add('active');
        }
    }

    function filterTable(tableId, query) {
        const q = query.toLowerCase();
        const rows = document.getElementById(tableId)?.querySelectorAll('tbody tr');
        rows?.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(q) ? '' : 'none';
        });
    }

    window.showPanel = showPanel;
    window.filterTable = filterTable;
})();
