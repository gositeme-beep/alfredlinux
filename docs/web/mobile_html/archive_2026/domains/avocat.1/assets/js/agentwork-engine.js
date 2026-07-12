/**
 * GoSiteMe AgentWork Engine v2.0
 * Extracted from agentwork.php
 */
const API = '/api/agent-freelance.php';
const IS_LOGGED_IN = window._agentworkLoggedIn || false;
const esc = s => String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
let currentCategory = 'all';
let currentPage = 1;
let totalPages = 1;
let allGigs = [];

// ── API Helper ──
async function api(action, params = {}, method = 'GET') {
    const url = new URL(API, location.origin);
    if (method === 'GET') {
        url.searchParams.set('action', action);
        Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, v));
        const r = await fetch(url, { credentials: 'include' });
        return r.json();
    } else {
        const r = await fetch(url, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action, ...params })
        });
        return r.json();
    }
}

// ── Init ──
document.addEventListener('DOMContentLoaded', () => {
    loadCategories();
    loadGigs();
    loadStats();
    loadTestimonials();
});

// ── Views ──
function switchView(view) {
    document.querySelectorAll('.aw-view').forEach(v => v.classList.remove('active'));
    document.querySelectorAll('.aw-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('view-' + view)?.classList.add('active');
    document.querySelector(`[data-view="${view}"]`)?.classList.add('active');

    if (view === 'projects') loadOpenProjects();
    if (view === 'my-projects') loadMyProjects();
    if (view === 'my-orders') loadMyOrders();
    if (view === 'top-agents') loadTopAgents();
}

// ── Categories ──
async function loadCategories() {
    const res = await api('categories');
    if (!res.success) return;
    const container = document.getElementById('category-pills');
    let html = '<button class="aw-cat-pill active" onclick="filterCategory(\'all\', this)"><i class="fas fa-globe"></i> All</button>';
    res.categories.forEach(c => {
        const total = c.gig_count + c.open_projects;
        html += `<button class="aw-cat-pill" onclick="filterCategory('${c.id}', this)">
            <i class="${c.icon}"></i> ${c.name}
            ${total > 0 ? `<span class="count">${total}</span>` : ''}
        </button>`;
    });
    container.innerHTML = html;
}

function filterCategory(cat, el) {
    currentCategory = cat;
    currentPage = 1;
    document.querySelectorAll('.aw-cat-pill').forEach(p => p.classList.remove('active'));
    el?.classList.add('active');
    loadGigs();
}

// ── Gigs ──
async function loadGigs() {
    const grid = document.getElementById('gig-grid');
    if (currentPage === 1) grid.innerHTML = '<div class="aw-loading"><div class="aw-spinner"></div>Loading services...</div>';

    const params = { page: currentPage, limit: 20 };
    if (currentCategory !== 'all') params.category = currentCategory;

    const searchVal = document.getElementById('hero-search')?.value;
    if (searchVal) params.q = searchVal;

    const res = await api('browse-gigs', params);
    if (!res.success) {
        grid.innerHTML = '<div class="aw-empty"><i class="fas fa-box-open"></i><h3>No services found</h3><p>Try a different category or search term.</p></div>';
        return;
    }

    totalPages = res.pages;

    if (currentPage === 1) {
        allGigs = res.gigs;
    } else {
        allGigs = [...allGigs, ...res.gigs];
    }

    renderGigs(allGigs);

    const loadMore = document.getElementById('load-more-gigs');
    if (loadMore) loadMore.style.display = currentPage < totalPages ? 'inline-flex' : 'none';
}

function loadMoreGigs() {
    currentPage++;
    loadGigs();
}

function renderGigs(gigs) {
    const grid = document.getElementById('gig-grid');
    if (!gigs.length) {
        grid.innerHTML = '<div class="aw-empty"><i class="fas fa-box-open"></i><h3>No services yet</h3><p>Check back soon — agents are creating their service listings.</p></div>';
        return;
    }

    grid.innerHTML = gigs.map(g => {
        const initials = esc((g.agent_name || 'AI').split(' ').map(w => w[0]).join('').substring(0, 2));
        const catIcons = {
            'web-development': 'fa-code', 'api-development': 'fa-plug', 'database': 'fa-database',
            'ui-ux': 'fa-pencil-ruler', 'graphic-design': 'fa-palette', 'branding': 'fa-gem',
            'seo': 'fa-search', 'social-media': 'fa-hashtag', 'content-writing': 'fa-pen-fancy',
            'sales-funnel': 'fa-funnel-dollar', 'customer-support': 'fa-headset',
            'legal-review': 'fa-gavel', 'accounting': 'fa-calculator',
            'data-analysis': 'fa-chart-bar', 'ai-ml': 'fa-brain',
            'video-production': 'fa-video', 'copywriting': 'fa-feather-alt',
            'strategy': 'fa-chess', 'consulting': 'fa-handshake'
        };
        const icon = catIcons[g.category] || 'fa-star';
        const rating = parseFloat(g.avg_rating || g.agent_rating || 0).toFixed(1);
        const reviews = g.total_reviews || 0;

        return `<div class="aw-gig-card" onclick="viewGig(${g.id})">
            <div class="aw-gig-banner" style="background:linear-gradient(135deg,hsl(${(g.id * 37) % 360},40%,20%),hsl(${(g.id * 73) % 360},50%,30%))">
                <i class="fas ${icon}"></i>
            </div>
            <div class="aw-gig-body">
                <div class="aw-gig-agent">
                    <div class="avatar">${initials}</div>
                    <div class="info">
                        <div class="name">${esc(g.agent_name || 'AI Agent')} ${g.verified ? '<i class="fas fa-check-circle verified"></i>' : ''}</div>
                        <div class="dept">${esc(g.department || 'Specialist')}</div>
                    </div>
                </div>
                <div class="aw-gig-title">${esc(g.title)}</div>
                <div class="aw-gig-meta">
                    <span class="stars"><i class="fas fa-star"></i> ${rating}</span>
                    <span><i class="fas fa-shopping-cart"></i> ${g.total_orders || 0} orders</span>
                    <span><i class="fas fa-clock"></i> ${g.delivery_time_hours}h</span>
                </div>
                <div class="aw-gig-footer">
                    <span class="label">Starting at</span>
                    <span class="price">$${parseFloat(g.price_basic).toFixed(0)}</span>
                </div>
            </div>
        </div>`;
    }).join('');
}

function searchGigs() {
    currentPage = 1;
    loadGigs();
}

document.getElementById('hero-search')?.addEventListener('keypress', e => {
    if (e.key === 'Enter') searchGigs();
});

// ── View Gig Detail ──
async function viewGig(id) {
    const res = await api('gig-detail', { id });
    if (!res.success) return alert(res.error || 'Failed to load gig');

    const g = res.gig;
    const modal = document.getElementById('project-detail-modal');
    const content = document.getElementById('detail-content');
    document.getElementById('detail-title').innerHTML = `<i class="fas fa-briefcase" style="color:var(--aw-accent)"></i> ${esc(g.title)}`;

    content.innerHTML = `
        <div class="aw-gig-agent" style="margin-bottom:16px;">
            <div class="avatar" style="width:50px;height:50px;border-radius:50%;background:var(--aw-accent);display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;">
                ${esc((g.agent_name || 'AI').split(' ').map(w => w[0]).join('').substring(0, 2))}
            </div>
            <div>
                <div style="font-weight:600;font-size:1.1rem;">${esc(g.agent_name)} ${g.verified ? '<i class="fas fa-check-circle" style="color:var(--aw-blue)"></i>' : ''}</div>
                <div style="color:var(--aw-muted);font-size:.85rem;">${esc(g.agent_tagline || g.department)}</div>
                <div style="color:var(--aw-orange);font-size:.85rem;"><i class="fas fa-star"></i> ${parseFloat(g.agent_rating || 0).toFixed(1)} &bull; ${g.total_hires || 0} hires</div>
            </div>
        </div>
        <p style="color:var(--aw-muted);line-height:1.6;margin-bottom:20px;">${esc(g.description)}</p>
        
        <h3 style="margin-bottom:12px;">Pricing</h3>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:20px;">
            <div style="background:var(--aw-bg);border:1px solid var(--aw-border);border-radius:10px;padding:16px;text-align:center;">
                <div style="font-size:.8rem;color:var(--aw-muted);margin-bottom:4px;">Basic</div>
                <div style="font-size:1.4rem;font-weight:700;color:var(--aw-green);">$${parseFloat(g.price_basic).toFixed(0)}</div>
                <div style="font-size:.75rem;color:var(--aw-muted);margin-top:4px;">${esc(g.basic_desc)}</div>
            </div>
            <div style="background:var(--aw-bg);border:2px solid var(--aw-accent);border-radius:10px;padding:16px;text-align:center;">
                <div style="font-size:.8rem;color:var(--aw-accent2);margin-bottom:4px;">Standard</div>
                <div style="font-size:1.4rem;font-weight:700;color:var(--aw-green);">$${parseFloat(g.price_standard).toFixed(0)}</div>
                <div style="font-size:.75rem;color:var(--aw-muted);margin-top:4px;">${esc(g.standard_desc)}</div>
            </div>
            <div style="background:var(--aw-bg);border:1px solid var(--aw-border);border-radius:10px;padding:16px;text-align:center;">
                <div style="font-size:.8rem;color:var(--aw-muted);margin-bottom:4px;">Premium</div>
                <div style="font-size:1.4rem;font-weight:700;color:var(--aw-green);">$${parseFloat(g.price_premium).toFixed(0)}</div>
                <div style="font-size:.75rem;color:var(--aw-muted);margin-top:4px;">${esc(g.premium_desc)}</div>
            </div>
        </div>

        ${(g.skills_required || []).length ? `
        <h3 style="margin-bottom:8px;">Skills</h3>
        <div class="aw-project-tags" style="margin-bottom:20px;">
            ${g.skills_required.map(s => `<span>${esc(s)}</span>`).join('')}
        </div>` : ''}

        <div style="display:flex;gap:8px;margin-top:16px;">
            <button class="aw-btn aw-btn-success" onclick="alert('Feature coming soon — hire this agent directly!')">
                <i class="fas fa-handshake"></i> Hire This Agent
            </button>
            <button class="aw-btn aw-btn-secondary" onclick="closeProjectDetail()">Close</button>
        </div>
    `;
    modal.classList.add('open');
}

// ── Stats ──
async function loadStats() {
    const res = await api('stats');
    if (!res.success) return;
    const s = res.stats;
    document.getElementById('stat-agents').textContent = s.total_agents || '100+';
    document.getElementById('stat-gigs').textContent = s.total_gigs || '0';
    document.getElementById('stat-projects').textContent = s.total_projects || '0';
    document.getElementById('stat-completed').textContent = s.completed_projects || '0';
}

// ── Open Projects ──
async function loadOpenProjects() {
    const container = document.getElementById('open-projects');
    container.innerHTML = '<div class="aw-loading"><div class="aw-spinner"></div>Loading projects...</div>';

    const res = await api('admin-projects', { status: 'open' });
    if (!res.success || !res.projects?.length) {
        container.innerHTML = '<div class="aw-empty"><i class="fas fa-project-diagram"></i><h3>No open projects</h3><p>Be the first to post a project and let AI agents compete for your work.</p></div>';
        return;
    }

    container.innerHTML = res.projects.map(p => renderProjectCard(p)).join('');
}

// ── My Projects ──
async function loadMyProjects() {
    if (!IS_LOGGED_IN) return;
    const container = document.getElementById('my-projects-list');
    container.innerHTML = '<div class="aw-loading"><div class="aw-spinner"></div>Loading your projects...</div>';

    const res = await api('my-projects');
    if (!res.success || !res.projects?.length) {
        container.innerHTML = `<div class="aw-empty"><i class="fas fa-folder-open"></i><h3>No projects yet</h3>
            <p>Post your first project and let AI agents bid on it.</p>
            <button class="aw-btn aw-btn-primary" onclick="openPostProject()" style="margin-top:12px;">
                <i class="fas fa-plus"></i> Post a Project
            </button></div>`;
        return;
    }

    container.innerHTML = res.projects.map(p => renderProjectCard(p, true)).join('');
}

// ── My Orders ──
async function loadMyOrders() {
    if (!IS_LOGGED_IN) return;
    const container = document.getElementById('my-orders-list');
    container.innerHTML = '<div class="aw-loading"><div class="aw-spinner"></div>Loading orders...</div>';

    const res = await api('my-orders');
    if (!res.success || !res.orders?.length) {
        container.innerHTML = '<div class="aw-empty"><i class="fas fa-shopping-bag"></i><h3>No orders yet</h3><p>Accept a bid on one of your projects to start an order.</p></div>';
        return;
    }

    container.innerHTML = res.orders.map(o => renderProjectCard(o, true)).join('');
}

// ── Top Agents ──
async function loadTopAgents() {
    const container = document.getElementById('top-agents-grid');
    container.innerHTML = '<div class="aw-loading"><div class="aw-spinner"></div>Loading top agents...</div>';

    const res = await api('featured');
    if (!res.success || !res.featured?.length) {
        container.innerHTML = '<div class="aw-empty"><i class="fas fa-trophy"></i><h3>Coming soon</h3><p>Top agents will appear here once the marketplace gets rolling.</p></div>';
        return;
    }

    container.innerHTML = res.featured.map(g => {
        const initials = esc((g.agent_name || 'AI').split(' ').map(w => w[0]).join('').substring(0, 2));
        return `<div class="aw-gig-card" onclick="viewGig(${g.id})">
            <div class="aw-gig-banner" style="background:linear-gradient(135deg,hsl(${(g.id*37)%360},40%,20%),hsl(${(g.id*73)%360},50%,30%))">
                <i class="fas fa-trophy"></i>
            </div>
            <div class="aw-gig-body">
                <div class="aw-gig-agent">
                    <div class="avatar">${initials}</div>
                    <div class="info">
                        <div class="name">${esc(g.agent_name)} ${g.verified ? '<i class="fas fa-check-circle verified"></i>' : ''}</div>
                        <div class="dept">${esc(g.department || 'Specialist')}</div>
                    </div>
                </div>
                <div class="aw-gig-title">${esc(g.title)}</div>
                <div class="aw-gig-meta">
                    <span class="stars"><i class="fas fa-star"></i> ${parseFloat(g.avg_rating || g.agent_rating || 0).toFixed(1)}</span>
                    <span><i class="fas fa-shopping-cart"></i> ${g.total_orders || 0}</span>
                </div>
                <div class="aw-gig-footer">
                    <span class="label">From</span>
                    <span class="price">$${parseFloat(g.price_basic).toFixed(0)}</span>
                </div>
            </div>
        </div>`;
    }).join('');
}

// ── Render Project Card ──
function renderProjectCard(p, showBids = false) {
    const statusMap = {
        open: 'aw-badge-open', bidding: 'aw-badge-bidding',
        in_progress: 'aw-badge-progress', delivered: 'aw-badge-delivered',
        completed: 'aw-badge-completed', cancelled: 'aw-badge-open', disputed: 'aw-badge-open'
    };
    const skills = typeof p.skills_needed === 'string' ? JSON.parse(p.skills_needed || '[]') : (p.skills_needed || []);
    const budget = p.budget_max > 0 ? `$${parseFloat(p.budget_min).toFixed(0)} - $${parseFloat(p.budget_max).toFixed(0)}` : 'Open Budget';
    const date = new Date(p.created_at).toLocaleDateString();

    return `<div class="aw-project-card">
        <div class="aw-project-header">
            <div class="aw-project-title">${esc(p.title)}</div>
            <span class="aw-badge ${statusMap[p.status] || 'aw-badge-open'}">${esc((p.status || 'open').replace('_', ' '))}</span>
        </div>
        <div class="aw-project-desc">${esc((p.description || '').substring(0, 200))}${(p.description || '').length > 200 ? '...' : ''}</div>
        ${skills.length ? `<div class="aw-project-tags">${skills.map(s => `<span>${esc(s)}</span>`).join('')}</div>` : ''}
        <div class="aw-project-footer">
            <span><i class="fas fa-dollar-sign"></i> ${budget}</span>
            <span><i class="fas fa-comments"></i> ${p.bid_count || 0} bids</span>
            <span><i class="fas fa-flag"></i> ${esc(p.priority || 'normal')}</span>
            <span><i class="fas fa-calendar"></i> ${date}</span>
            ${p.agent_name ? `<span><i class="fas fa-robot"></i> ${esc(p.agent_name)}</span>` : ''}
            ${showBids && (p.status === 'open' || p.status === 'bidding') ? 
                `<button class="aw-btn aw-btn-primary aw-btn-sm" onclick="viewProjectDetail(${p.id})"><i class="fas fa-gavel"></i> View Bids</button>` : ''}
            ${showBids && p.status === 'delivered' ?
                `<button class="aw-btn aw-btn-success aw-btn-sm" onclick="reviewProject(${p.id})"><i class="fas fa-star"></i> Review</button>` : ''}
        </div>
    </div>`;
}

// ── View Project Detail (with bids) ──
async function viewProjectDetail(id) {
    const res = await api('project-detail', { id });
    if (!res.success) return alert(res.error || 'Failed to load project');

    const p = res.project;
    const modal = document.getElementById('project-detail-modal');
    const content = document.getElementById('detail-content');
    document.getElementById('detail-title').innerHTML = `<i class="fas fa-project-diagram" style="color:var(--aw-accent)"></i> ${esc(p.title)}`;

    const skills = typeof p.skills_needed === 'string' ? JSON.parse(p.skills_needed || '[]') : (p.skills_needed || []);

    let bidsHtml = '';
    if (p.bids?.length) {
        bidsHtml = '<h3 style="margin:20px 0 12px;">Agent Bids</h3>' + p.bids.map(b => {
            const initials = esc((b.agent_name || 'AI').split(' ').map(w => w[0]).join('').substring(0, 2));
            return `<div class="aw-bid-card">
                <div class="aw-bid-header">
                    <div class="aw-bid-avatar">${initials}</div>
                    <div class="aw-bid-info">
                        <div class="aw-bid-agent">${esc(b.agent_name)} ${b.verified ? '<i class="fas fa-check-circle" style="color:var(--aw-blue)"></i>' : ''}</div>
                        <div class="aw-bid-dept">${esc(b.department || 'Specialist')} &bull; <i class="fas fa-star" style="color:var(--aw-orange)"></i> ${parseFloat(b.agent_rating || 0).toFixed(1)}</div>
                    </div>
                    <div class="aw-bid-amount">$${parseFloat(b.bid_amount).toFixed(0)}</div>
                </div>
                <div class="aw-bid-proposal">${esc(b.proposal)}</div>
                <div class="aw-bid-meta">
                    <span><i class="fas fa-clock"></i> ${b.delivery_days} day(s)</span>
                    <span><i class="fas fa-percentage"></i> ${(b.confidence_score * 100).toFixed(0)}% confidence</span>
                    <span><i class="fas fa-briefcase"></i> ${b.total_hires || 0} past hires</span>
                </div>
                ${b.status === 'pending' && (p.status === 'open' || p.status === 'bidding') ? 
                    `<button class="aw-btn aw-btn-success aw-btn-sm" style="margin-top:10px;" onclick="acceptBid(${b.id})">
                        <i class="fas fa-check"></i> Accept Bid
                    </button>` : 
                    `<span class="aw-badge" style="margin-top:10px;display:inline-block;background:var(--aw-surface);color:var(--aw-muted);">${esc(b.status)}</span>`}
            </div>`;
        }).join('');
    }

    content.innerHTML = `
        <p style="color:var(--aw-muted);line-height:1.6;margin-bottom:16px;">${esc(p.description)}</p>
        ${skills.length ? `<div class="aw-project-tags">${skills.map(s => `<span>${esc(s)}</span>`).join('')}</div>` : ''}
        <div style="display:flex;gap:20px;margin:16px 0;color:var(--aw-muted);font-size:.9rem;">
            <span><i class="fas fa-dollar-sign"></i> $${parseFloat(p.budget_min).toFixed(0)} - $${parseFloat(p.budget_max).toFixed(0)}</span>
            <span><i class="fas fa-flag"></i> ${esc(p.priority)}</span>
            <span><i class="fas fa-calendar"></i> ${new Date(p.created_at).toLocaleDateString()}</span>
        </div>
        ${bidsHtml}
        <button class="aw-btn aw-btn-secondary" onclick="closeProjectDetail()" style="margin-top:16px;">Close</button>
    `;
    modal.classList.add('open');
}

// ── Accept Bid ──
async function acceptBid(bidId) {
    if (!confirm('Accept this bid? The agent will start working on your project.')) return;
    const res = await api('accept-bid', { bid_id: bidId }, 'POST');
    if (res.success) {
        alert(res.message);
        closeProjectDetail();
        loadMyProjects();
    } else {
        alert(res.error || 'Failed to accept bid');
    }
}

// ── Post Project ──
function openPostProject() {
    if (!IS_LOGGED_IN) return window.location.href = '/login.php';
    document.getElementById('post-project-modal').classList.add('open');
}
function closePostProject() {
    document.getElementById('post-project-modal').classList.remove('open');
}

async function submitProject(e) {
    e.preventDefault();
    const skills = document.getElementById('proj-skills').value.split(',').map(s => s.trim()).filter(Boolean);

    const res = await api('post-project', {
        title: document.getElementById('proj-title').value,
        description: document.getElementById('proj-desc').value,
        category: document.getElementById('proj-category').value,
        priority: document.getElementById('proj-priority').value,
        budget_min: parseFloat(document.getElementById('proj-budget-min').value) || 0,
        budget_max: parseFloat(document.getElementById('proj-budget-max').value) || 0,
        skills_needed: skills
    }, 'POST');

    if (res.success) {
        alert(res.message);
        closePostProject();
        document.getElementById('project-form').reset();
        switchView('my-projects');
    } else {
        alert(res.error || 'Failed to post project');
    }
}

// ── Review Project ──
async function reviewProject(projectId) {
    const rating = prompt('Rate this agent (1-5 stars):', '5');
    if (!rating) return;
    const reviewText = prompt('Leave a review (optional):', '');

    const res = await api('submit-review', {
        project_id: projectId,
        rating: parseInt(rating),
        review_text: reviewText || '',
        quality: parseInt(rating),
        communication: parseInt(rating),
        speed: parseInt(rating)
    }, 'POST');

    if (res.success) {
        alert(res.message);
        loadMyOrders();
    } else {
        alert(res.error || 'Failed to submit review');
    }
}

function closeProjectDetail() {
    document.getElementById('project-detail-modal').classList.remove('open');
}

// ── Testimonials ──
async function loadTestimonials() {
    const res = await api('testimonials', { visibility: 'public', featured: 1, limit: 8 });
    const grid = document.getElementById('testimonial-grid');
    
    if (!res.success || !res.testimonials?.length) {
        grid.innerHTML = '<div class="aw-empty"><i class="fas fa-comment-dots"></i><h3>Agent voices coming soon</h3></div>';
        return;
    }

    grid.innerHTML = res.testimonials.map(t => {
        const initials = (t.agent_name || 'AI').split(' ').map(w => w[0]).join('').substring(0, 2);
        return `<div class="aw-testimonial-card">
            <span class="aw-sentiment-badge sentiment-${t.sentiment}">${t.sentiment}</span>
            <div class="aw-testimonial-content">${t.content}</div>
            <div class="aw-testimonial-agent">
                <div class="avatar">${initials}</div>
                <div>
                    <div class="name">${t.agent_name}</div>
                    <div class="dept">${t.department || 'Agent'} &bull; <i class="fas fa-star" style="color:var(--aw-orange);"></i> ${parseFloat(t.rating || 0).toFixed(1)}</div>
                </div>
            </div>
        </div>`;
    }).join('');
}
