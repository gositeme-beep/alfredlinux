// changelog-engine.js — Filter functionality for the Changelog page
(function () {
    'use strict';

    document.querySelectorAll('.cl-filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.cl-filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const filter = btn.dataset.filter;
            document.querySelectorAll('.cl-release').forEach(release => {
                if (filter === 'all') {
                    release.style.display = '';
                } else {
                    const tags = release.dataset.tags || '';
                    release.style.display = tags.includes(filter) ? '' : 'none';
                }
            });
        });
    });
})();
