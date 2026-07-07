// Pulse Social — Desktop Frontend

const PAGES = {
    feed: 'https://gositeme.com/pulse.php',
    search: 'https://gositeme.com/pulse.php?tab=search',
    post: 'https://gositeme.com/pulse.php?action=new',
    notifications: 'https://gositeme.com/pulse.php?tab=notifications',
    profile: 'https://gositeme.com/pulse.php?tab=profile'
};

document.addEventListener('DOMContentLoaded', () => {
    // Splash → Social
    setTimeout(() => {
        document.getElementById('splash').classList.add('hidden');
        document.getElementById('social').classList.remove('hidden');
    }, 1800);

    // Navigation
    document.querySelectorAll('.nav-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const page = btn.dataset.page;
            if (PAGES[page]) {
                document.getElementById('pulse-frame').src = PAGES[page];
            }
        });
    });

    // Header buttons
    document.getElementById('btn-new-post')?.addEventListener('click', () => {
        document.getElementById('pulse-frame').src = PAGES.post;
    });
    document.getElementById('btn-search')?.addEventListener('click', () => {
        document.getElementById('pulse-frame').src = PAGES.search;
    });
});
