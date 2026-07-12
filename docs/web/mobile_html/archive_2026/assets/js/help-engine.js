document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('helpSearch');
    const accordions = document.querySelectorAll('.help-accordion');
    const categorySections = document.querySelectorAll('.help-category-section');
    const catGrid = document.getElementById('helpCatGrid');
    const searchStatus = document.getElementById('helpSearchStatus');
    const noResults = document.getElementById('helpNoResults');
    const sidebarLinks = document.querySelectorAll('.help-sidebar-nav a');

    // ── Accordion toggle ──
    document.querySelectorAll('.help-accordion-header').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const acc = btn.closest('.help-accordion');
            const isOpen = acc.classList.contains('open');
            acc.classList.toggle('open');
            btn.setAttribute('aria-expanded', !isOpen);
        });
    });

    // ── Deep link: open category from hash ──
    function handleHash() {
        const hash = window.location.hash.replace('#', '');
        if (!hash) return;
        const section = document.getElementById(hash);
        if (section && section.classList.contains('help-category-section')) {
            setTimeout(function() {
                section.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 100);
            sidebarLinks.forEach(function(l) {
                l.classList.toggle('active', l.dataset.category === hash);
            });
        }
        // open specific article if hash matches article id
        const target = document.querySelector('[data-article-id="' + hash + '"]');
        if (target) {
            target.classList.add('open');
            target.querySelector('.help-accordion-header').setAttribute('aria-expanded', 'true');
            setTimeout(function() {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 150);
        }
    }
    handleHash();
    window.addEventListener('hashchange', handleHash);

    // ── Category card click ──
    document.querySelectorAll('.help-cat-card').forEach(function(card) {
        card.addEventListener('click', function(e) {
            e.preventDefault();
            const cat = card.dataset.category;
            window.location.hash = cat;
            sidebarLinks.forEach(function(l) {
                l.classList.toggle('active', l.dataset.category === cat);
            });
        });
    });

    // ── Sidebar scrollspy ──
    const observerOpts = { rootMargin: '-100px 0px -60% 0px', threshold: 0 };
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                const cat = entry.target.dataset.categorySection;
                sidebarLinks.forEach(function(l) {
                    l.classList.toggle('active', l.dataset.category === cat);
                });
            }
        });
    }, observerOpts);
    categorySections.forEach(function(s) { observer.observe(s); });

    // ── Client-side search ──
    let searchTimeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(performSearch, 200);
    });

    // Popular search links
    document.querySelectorAll('.help-popular a').forEach(function(a) {
        a.addEventListener('click', function(e) {
            e.preventDefault();
            searchInput.value = a.dataset.search;
            performSearch();
            searchInput.focus();
        });
    });

    function performSearch() {
        const q = searchInput.value.trim().toLowerCase();
        if (!q) {
            // Reset — show everything
            catGrid.style.display = '';
            searchStatus.classList.remove('visible');
            noResults.classList.remove('visible');
            categorySections.forEach(function(s) { s.style.display = ''; });
            accordions.forEach(function(a) {
                a.style.display = '';
                a.classList.remove('open');
                a.querySelector('.help-accordion-header').setAttribute('aria-expanded', 'false');
            });
            return;
        }

        catGrid.style.display = 'none';
        const terms = q.split(/\s+/);
        let matchCount = 0;

        accordions.forEach(function(acc) {
            const title = acc.querySelector('.help-accordion-header span').textContent.toLowerCase();
            const tags = (acc.dataset.tags || '').toLowerCase();
            const body = acc.querySelector('.help-accordion-body').textContent.toLowerCase();
            const searchable = title + ' ' + tags + ' ' + body;

            const matches = terms.every(function(t) { return searchable.indexOf(t) !== -1; });
            acc.style.display = matches ? '' : 'none';
            if (matches) matchCount++;
            // Open matching accordion
            if (matches && q.length >= 3) {
                acc.classList.add('open');
                acc.querySelector('.help-accordion-header').setAttribute('aria-expanded', 'true');
            } else {
                acc.classList.remove('open');
                acc.querySelector('.help-accordion-header').setAttribute('aria-expanded', 'false');
            }
        });

        // Show/hide category sections
        categorySections.forEach(function(sec) {
            const vis = sec.querySelectorAll('.help-accordion[style=""], .help-accordion:not([style])');
            let hasVisible = false;
            sec.querySelectorAll('.help-accordion').forEach(function(a) {
                if (a.style.display !== 'none') hasVisible = true;
            });
            sec.style.display = hasVisible ? '' : 'none';
        });

        if (matchCount > 0) {
            searchStatus.textContent = matchCount + ' article' + (matchCount !== 1 ? 's' : '') + ' found for "' + searchInput.value.trim() + '"';
            searchStatus.classList.add('visible');
            noResults.classList.remove('visible');
        } else {
            searchStatus.classList.remove('visible');
            noResults.classList.add('visible');
        }
    }

    // ── Feedback buttons ──
    document.querySelectorAll('.help-feedback-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const articleId = btn.dataset.article;
            const helpful = parseInt(btn.dataset.helpful);
            const parent = btn.closest('.help-article-feedback');

            // Mark voted
            parent.querySelectorAll('.help-feedback-btn').forEach(function(b) { b.classList.remove('voted'); });
            btn.classList.add('voted');

            // Send feedback
            fetch('/api/help.php?action=feedback', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ article_id: articleId, helpful: helpful })
            }).catch(function() { /* silent fail */ });
        });
    });

    // ── Open Alfred chat widget ──
    document.getElementById('helpOpenChat').addEventListener('click', function() {
        if (typeof window.alfredOpen === 'function') {
            window.alfredOpen();
        } else if (document.querySelector('.alfred-widget-toggle')) {
            document.querySelector('.alfred-widget-toggle').click();
        }
    });
});
