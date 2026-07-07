/**
 * GoSiteMe Store Engine v2.0
 * Extracted from store.php
 */
/* ═══════════════════════════════════════════════════════════════
   GoSiteMe Store — Client-Side Controller
   ═══════════════════════════════════════════════════════════════ */
(function() {
    'use strict';

    const API = '/api/store.php';
    const isLoggedIn = window._storeLoggedIn || false;
    let currentType = '';
    let carouselIndex = 0;
    let carouselTimer = null;
    let searchDebounce = null;

    // ── HELPERS ──
    async function api(params) {
        const qs = new URLSearchParams(params).toString();
        const res = await fetch(`${API}?${qs}`);
        return res.json();
    }

    function stars(rating, size) {
        const full = Math.floor(rating);
        const half = rating - full >= 0.5 ? 1 : 0;
        const empty = 5 - full - half;
        const sz = size || '';
        return '★'.repeat(full) + (half ? '½' : '') + '☆'.repeat(empty);
    }

    function formatInstalls(n) {
        if (!n) return '0';
        if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M';
        if (n >= 1000) return (n / 1000).toFixed(1) + 'K';
        return n.toString();
    }

    function priceLabel(price, cycle) {
        if (!price || parseFloat(price) === 0) return '<span class="app-card-price free">Free</span>';
        const p = parseFloat(price).toFixed(2);
        const c = cycle === 'monthly' ? '/mo' : cycle === 'yearly' ? '/yr' : '';
        return `<span class="app-card-price paid">$${p}${c}</span>`;
    }

    function typeLabel(t) {
        const labels = { game:'Game', agent:'AI Agent', extension:'Extension', service:'Service', app:'App', template:'Template', tool:'Tool' };
        return labels[t] || t;
    }

    function escHtml(str) {
        const d = document.createElement('div');
        d.textContent = str || '';
        return d.innerHTML;
    }

    // ── APP CARD HTML ──
    function cardHtml(item) {
        return `<div class="app-card" data-slug="${escHtml(item.slug)}" data-id="${item.id}">
            <span class="app-card-type">${typeLabel(item.item_type)}</span>
            <div class="app-card-icon">${item.icon_url && item.icon_url.startsWith('http') ? `<img src="${escHtml(item.icon_url)}" alt="">` : escHtml(item.icon_url || '📦')}</div>
            <h3 class="app-card-title">${escHtml(item.title)}</h3>
            <p class="app-card-dev">${escHtml(item.developer_name || 'GoSiteMe')}</p>
            <div class="app-card-meta">
                <span class="app-card-rating"><span class="star">★</span> ${parseFloat(item.rating_avg || 0).toFixed(1)}</span>
                ${priceLabel(item.price, item.billing_cycle)}
            </div>
        </div>`;
    }

    // ── LOAD HOME ──
    async function loadHome() {
        const data = await api({ action: 'home' });

        // Featured carousel
        const slidesEl = document.getElementById('featuredSlides');
        const dotsEl = document.getElementById('carouselDots');
        if (data.featured && data.featured.length) {
            slidesEl.innerHTML = data.featured.map((f, i) => `
                <div class="featured-slide" data-slug="${escHtml(f.slug)}" data-id="${f.id}">
                    <div class="featured-info">
                        <span class="featured-badge">${typeLabel(f.item_type)} · ${escHtml(f.content_rating || 'Everyone')}</span>
                        <h2 class="featured-title">${escHtml(f.title)}</h2>
                        <p class="featured-desc">${escHtml(f.short_desc)}</p>
                        <div class="featured-meta">
                            <span class="featured-stars">${stars(f.rating_avg)}</span>
                            <span class="featured-installs">${formatInstalls(f.installs)} installs</span>
                            ${priceLabel(f.price, f.billing_cycle).replace('app-card-price', 'featured-price')}
                        </div>
                    </div>
                    <div class="featured-icon">${escHtml(f.icon_url || '📦')}</div>
                </div>
            `).join('');
            dotsEl.innerHTML = data.featured.map((_, i) => `<span class="carousel-dot ${i === 0 ? 'active' : ''}" data-i="${i}"></span>`).join('');
            startCarousel(data.featured.length);
        }

        // Sections
        renderSection('editorsRow', data.editors_choice);
        renderSection('trendingRow', data.trending);
        renderSection('newRow', data.new);
        renderSection('topRatedRow', data.top_rated);

        // Collections
        const colGrid = document.getElementById('collectionsGrid');
        if (data.collections) {
            colGrid.innerHTML = data.collections.map(c => `
                <div class="collection-card" data-collection="${escHtml(c.slug)}">
                    <div class="collection-icon">${escHtml(c.icon)}</div>
                    <h3 class="collection-title">${escHtml(c.title)}</h3>
                    <p class="collection-desc">${escHtml(c.description)}</p>
                </div>
            `).join('');
        }
    }

    function renderSection(elId, items) {
        const el = document.getElementById(elId);
        if (!el || !items) return;
        el.innerHTML = items.map(cardHtml).join('');
    }

    // ── CAROUSEL ──
    function startCarousel(count) {
        if (carouselTimer) clearInterval(carouselTimer);
        carouselTimer = setInterval(() => moveCarousel((carouselIndex + 1) % count, count), 5000);
    }
    function moveCarousel(idx, count) {
        carouselIndex = idx;
        document.getElementById('featuredSlides').style.transform = `translateX(-${idx * 100}%)`;
        document.querySelectorAll('.carousel-dot').forEach((d, i) => d.classList.toggle('active', i === idx));
    }

    // ── BROWSE BY TYPE ──
    async function browseType(type, sort, page) {
        currentType = type;
        sort = sort || 'popular';
        page = page || 1;

        const data = await api({ action: 'browse', type, sort, page, limit: 24 });

        document.getElementById('homeContent').querySelectorAll('.store-section').forEach(s => {
            s.style.display = type ? 'none' : '';
        });
        document.getElementById('sectionBrowse').style.display = type ? '' : 'none';
        document.getElementById('featuredCarousel').style.display = type ? 'none' : '';

        if (type) {
            document.getElementById('browseTitle').innerHTML = `<span class="section-icon">📂</span> ${typeLabel(type)}s`;
            document.getElementById('browseResults').innerHTML = data.items.map(cardHtml).join('');

            // Load categories for this type
            const cats = await api({ action: 'categories', type });
            const chipsEl = document.getElementById('categoryChips');
            if (cats.categories && cats.categories.length > 1) {
                chipsEl.style.display = 'flex';
                chipsEl.innerHTML = `<span class="category-chip active" data-cat="">All</span>` +
                    cats.categories.map(c => `<span class="category-chip" data-cat="${escHtml(c.category)}">${escHtml(c.category)} (${c.count})</span>`).join('');
            } else {
                chipsEl.style.display = 'none';
            }

            // Pagination
            if (data.pagination.pages > 1) {
                let pgHtml = '';
                for (let p = 1; p <= data.pagination.pages; p++) {
                    pgHtml += `<button style="padding:6px 14px;margin:4px;border-radius:8px;border:1px solid ${p === page ? 'var(--st-accent)' : 'var(--st-border)'};background:${p === page ? 'var(--st-accent)' : 'var(--st-surface)'};color:#fff;cursor:pointer;" onclick="GoStore.browse('${type}','${sort}',${p})">${p}</button>`;
                }
                document.getElementById('browsePagination').innerHTML = pgHtml;
            }
        } else {
            document.getElementById('categoryChips').style.display = 'none';
        }
    }

    // ── SEARCH ──
    async function search(query) {
        if (!query || query.length < 2) {
            document.getElementById('searchResultsPanel').classList.remove('active');
            return;
        }
        const data = await api({ action: 'search', q: query, type: currentType, limit: 30 });
        document.getElementById('searchResultsPanel').classList.add('active');
        document.getElementById('searchInfo').textContent = `${data.count || 0} results for "${query}"`;
        document.getElementById('searchResults').innerHTML = (data.results || []).map(cardHtml).join('') || '<p style="color:var(--st-text-muted);">No results found.</p>';
    }

    // ── APP DETAIL MODAL ──
    async function openDetail(slug, id) {
        const params = slug ? { action: 'detail', slug } : { action: 'detail', id };
        const item = await api(params);
        if (item.error) return;

        const screenshots = item.screenshots || [];
        const reviews = item.recent_reviews || [];
        const rd = item.rating_breakdown || {};
        const totalRatings = item.rating_count || 1;

        document.getElementById('appModalContent').innerHTML = `
            <div class="app-modal-header">
                <div class="app-modal-icon">${item.icon_url && item.icon_url.startsWith('http') ? `<img src="${escHtml(item.icon_url)}" alt="" style="width:100%;height:100%;border-radius:24px;object-fit:cover;">` : escHtml(item.icon_url || '📦')}</div>
                <div class="app-modal-title-wrap">
                    <h2 class="app-modal-title">${escHtml(item.title)}</h2>
                    <p class="app-modal-dev">${escHtml(item.developer_name || 'GoSiteMe')}</p>
                    <div class="app-modal-badges">
                        <span class="app-modal-badge">${typeLabel(item.item_type)}</span>
                        <span class="app-modal-badge">${escHtml(item.content_rating || 'Everyone')}</span>
                        <span class="app-modal-badge">v${escHtml(item.version)}</span>
                        ${item.editors_choice == 1 ? '<span class="app-modal-badge" style="background:rgba(108,92,231,0.3);color:var(--st-accent-light);">⭐ Editor\'s Choice</span>' : ''}
                    </div>
                </div>
            </div>

            <div class="app-modal-stats">
                <div class="app-modal-stat">
                    <div class="app-modal-stat-value">${stars(item.rating_avg)} ${parseFloat(item.rating_avg || 0).toFixed(1)}</div>
                    <div class="app-modal-stat-label">${item.rating_count || 0} reviews</div>
                </div>
                <div class="app-modal-stat">
                    <div class="app-modal-stat-value">${formatInstalls(item.installs)}</div>
                    <div class="app-modal-stat-label">Installs</div>
                </div>
                <div class="app-modal-stat">
                    <div class="app-modal-stat-value">${escHtml(item.content_rating || 'E')}</div>
                    <div class="app-modal-stat-label">Content Rating</div>
                </div>
                <div class="app-modal-stat">
                    <div class="app-modal-stat-value">${item.size_bytes ? (item.size_bytes / 1048576).toFixed(1) + ' MB' : 'Web'}</div>
                    <div class="app-modal-stat-label">Size</div>
                </div>
            </div>

            <div class="app-modal-actions">
                <button class="btn-install" data-id="${item.id}" data-url="${escHtml(item.launch_url || '')}">${parseFloat(item.price) > 0 ? 'Buy $' + parseFloat(item.price).toFixed(2) : 'Install'}</button>
                <button class="btn-wishlist">♡ Wishlist</button>
            </div>

            ${screenshots.length ? `<div class="app-modal-screenshots">${screenshots.map(s => `<div class="screenshot-thumb"><img src="${escHtml(s)}" alt="Screenshot"></div>`).join('')}</div>` : ''}

            <div class="app-modal-body">
                <h3>About this ${typeLabel(item.item_type).toLowerCase()}</h3>
                <p>${escHtml(item.description || item.short_desc)}</p>

                ${item.tags && item.tags.length ? `<div style="margin-top:12px;display:flex;gap:6px;flex-wrap:wrap;">${item.tags.map(t => `<span style="padding:4px 12px;border-radius:16px;background:var(--st-surface-3);font-size:0.8rem;color:var(--st-text-muted);">${escHtml(t)}</span>`).join('')}</div>` : ''}

                <h3>Ratings & Reviews</h3>
                <div class="rating-breakdown">
                    <div class="rating-big">
                        <div class="rating-big-number">${parseFloat(item.rating_avg || 0).toFixed(1)}</div>
                        <div class="rating-big-stars">${stars(item.rating_avg)}</div>
                        <div class="rating-big-count">${item.rating_count || 0} ratings</div>
                    </div>
                    <div class="rating-bars">
                        ${[5,4,3,2,1].map(n => {
                            const count = parseInt(rd[n] || 0);
                            const pct = totalRatings > 0 ? (count / totalRatings * 100) : 0;
                            return `<div class="rating-bar-row"><span>${n}</span><div class="rating-bar-fill"><span style="width:${pct}%"></span></div><span>${count}</span></div>`;
                        }).join('')}
                    </div>
                </div>

                ${reviews.length ? reviews.map(r => `
                    <div class="review-card">
                        <div class="review-header">
                            <span class="review-stars">${stars(r.rating)}</span>
                            <span class="review-date">${new Date(r.created_at).toLocaleDateString()}</span>
                        </div>
                        ${r.title ? `<strong style="color:#fff;font-size:0.9rem;">${escHtml(r.title)}</strong>` : ''}
                        <p class="review-body">${escHtml(r.body || '')}</p>
                        ${r.helpful_count ? `<p class="review-helpful">${r.helpful_count} people found this helpful</p>` : ''}
                        ${r.developer_reply ? `<div style="margin-top:8px;padding:10px;background:var(--st-surface);border-radius:8px;"><strong style="color:var(--st-accent-light);font-size:0.8rem;">Developer Response</strong><p style="font-size:0.85rem;color:var(--st-text-muted);margin:4px 0 0;">${escHtml(r.developer_reply)}</p></div>` : ''}
                    </div>
                `).join('') : '<p style="color:var(--st-text-dim);font-size:0.9rem;">No reviews yet. Be the first!</p>'}

                ${item.similar && item.similar.length ? `
                    <h3>Similar ${typeLabel(item.item_type)}s</h3>
                    <div class="app-row">${item.similar.map(s => `
                        <div class="app-card" data-slug="${escHtml(s.slug)}" data-id="${s.id}" style="min-width:0;">
                            <div class="app-card-icon">${escHtml(s.icon_url || '📦')}</div>
                            <h3 class="app-card-title">${escHtml(s.title)}</h3>
                            <div class="app-card-meta">
                                <span class="app-card-rating"><span class="star">★</span> ${parseFloat(s.rating_avg || 0).toFixed(1)}</span>
                                ${priceLabel(s.price)}
                            </div>
                        </div>
                    `).join('')}</div>
                ` : ''}

                ${item.latest_version ? `
                    <h3>What's New</h3>
                    <p><strong>v${escHtml(item.latest_version.version)}</strong> — ${new Date(item.latest_version.released_at).toLocaleDateString()}</p>
                    <p>${escHtml(item.latest_version.changelog || 'Bug fixes and improvements.')}</p>
                ` : ''}
            </div>
        `;

        document.getElementById('appModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    // ── MY APPS ──
    async function loadMyApps() {
        if (!isLoggedIn) { window.location.href = '/login.php'; return; }
        const data = await api({ action: 'my_apps' });
        const panel = document.getElementById('myAppsPanel');
        panel.classList.add('active');
        document.getElementById('myAppsList').innerHTML = (data.apps || []).map(a => `
            <div class="app-list-card" data-slug="${escHtml(a.slug)}" data-id="${a.id}">
                <div class="app-list-icon">${escHtml(a.icon_url || '📦')}</div>
                <div class="app-list-info">
                    <div class="app-list-title">${escHtml(a.title)}</div>
                    <div class="app-list-subtitle">
                        <span>v${escHtml(a.installed_version)}</span>
                        ${a.update_available == 1 ? '<span style="color:var(--st-green);font-weight:700;">Update available</span>' : '<span style="color:var(--st-text-dim);">Up to date</span>'}
                    </div>
                </div>
            </div>
        `).join('') || '<p style="color:var(--st-text-muted);">No apps installed yet.</p>';
    }

    // ── INSTALL ──
    async function installApp(itemId, launchUrl) {
        if (!isLoggedIn) { window.location.href = '/login.php'; return; }
        const data = await api({ action: 'install', item_id: itemId });
        if (data.success) {
            const btn = document.querySelector(`.btn-install[data-id="${itemId}"]`);
            if (btn) {
                btn.classList.add('installed');
                btn.textContent = '✓ Installed';
            }
            if (launchUrl) {
                setTimeout(() => window.open(launchUrl, '_blank'), 600);
            }
        }
    }

    // ── EVENT LISTENERS ──
    function init() {
        loadHome();

        // Tab clicks
        document.getElementById('storeTabs').addEventListener('click', e => {
            const tab = e.target.closest('.store-tab');
            if (!tab) return;
            document.querySelectorAll('.store-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            const type = tab.dataset.type;
            if (type) {
                browseType(type);
            } else {
                currentType = '';
                document.getElementById('sectionBrowse').style.display = 'none';
                document.getElementById('categoryChips').style.display = 'none';
                document.querySelectorAll('#homeContent > .store-section').forEach(s => s.style.display = '');
                document.getElementById('featuredCarousel').style.display = '';
            }
        });

        // Search
        document.getElementById('storeSearchInput').addEventListener('input', e => {
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(() => search(e.target.value.trim()), 350);
        });

        document.getElementById('clearSearch').addEventListener('click', () => {
            document.getElementById('storeSearchInput').value = '';
            document.getElementById('searchResultsPanel').classList.remove('active');
        });

        // Carousel
        document.getElementById('carouselPrev').addEventListener('click', () => {
            const count = document.querySelectorAll('.featured-slide').length;
            moveCarousel((carouselIndex - 1 + count) % count, count);
        });
        document.getElementById('carouselNext').addEventListener('click', () => {
            const count = document.querySelectorAll('.featured-slide').length;
            moveCarousel((carouselIndex + 1) % count, count);
        });
        document.getElementById('carouselDots').addEventListener('click', e => {
            if (e.target.classList.contains('carousel-dot')) {
                const count = document.querySelectorAll('.featured-slide').length;
                moveCarousel(parseInt(e.target.dataset.i), count);
            }
        });

        // Card clicks (delegated)
        document.addEventListener('click', e => {
            const card = e.target.closest('.app-card, .app-list-card, .featured-slide');
            if (card && !e.target.closest('.btn-install')) {
                const slug = card.dataset.slug;
                const id = card.dataset.id;
                if (slug || id) openDetail(slug, id);
            }

            // Install button
            const installBtn = e.target.closest('.btn-install');
            if (installBtn) {
                installApp(installBtn.dataset.id, installBtn.dataset.url);
            }

            // Collection
            const colCard = e.target.closest('.collection-card');
            if (colCard) {
                // Navigate to collection detail (future)
                console.log('Collection:', colCard.dataset.collection);
            }

            // Category chips
            const chip = e.target.closest('.category-chip');
            if (chip) {
                document.querySelectorAll('.category-chip').forEach(c => c.classList.remove('active'));
                chip.classList.add('active');
                const cat = chip.dataset.cat;
                // Reload browse with category
                api({ action: 'browse', type: currentType, category: cat, limit: 24 }).then(data => {
                    document.getElementById('browseResults').innerHTML = data.items.map(cardHtml).join('');
                });
            }
        });

        // Sort
        document.getElementById('browseSort').addEventListener('change', e => {
            browseType(currentType, e.target.value);
        });

        // Modal close
        document.getElementById('appModalClose').addEventListener('click', closeModal);
        document.getElementById('appModal').addEventListener('click', e => {
            if (e.target === e.currentTarget) closeModal();
        });
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeModal();
        });

        // My Apps
        const btnMyApps = document.getElementById('btnMyApps');
        if (btnMyApps) btnMyApps.addEventListener('click', loadMyApps);
        const closeMyApps = document.getElementById('closeMyApps');
        if (closeMyApps) closeMyApps.addEventListener('click', () => {
            document.getElementById('myAppsPanel').classList.remove('active');
        });
    }

    function closeModal() {
        document.getElementById('appModal').classList.remove('active');
        document.body.style.overflow = '';
    }

    // Public API
    window.GoStore = { browse: browseType, search, openDetail };

    // Boot
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
