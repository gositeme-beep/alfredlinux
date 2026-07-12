/* ── AI Employee Marketplace ── */
let allEmployees = [];
let myTeamCount = 0;

const roleBadges = {
    specialist:  { label: 'Specialist',  color: '#6c5ce7' },
    coordinator: { label: 'Coordinator', color: '#0984e3' },
    analyst:     { label: 'Analyst',     color: '#00b894' },
    reviewer:    { label: 'Reviewer',    color: '#fdcb6e' },
    leader:      { label: 'Leader',      color: '#e17055' }
};

const diffBadges = {
    beginner:     { label: 'Beginner',     color: '#00b894' },
    intermediate: { label: 'Intermediate', color: '#fdcb6e' },
    advanced:     { label: 'Advanced',     color: '#e17055' }
};

function renderCard(e) {
    const role = roleBadges[e.agent_role] || roleBadges.specialist;
    const diff = diffBadges[e.difficulty] || diffBadges.beginner;
    const catColor = e.category_color || '#6c5ce7';
    const catLabel = e.category_label || e.category;
    const toolCount = e.tool_count || (e.tools ? e.tools.length : 0);

    return `<div class="mp-card" data-cat="${e.category}" data-role="${e.agent_role}" data-diff="${e.difficulty}" data-id="${e.id}">
        <div class="mp-card-thumb" style="color: ${catColor};">
            <span class="badge-popular" style="background:${role.color};"><i class="fas fa-user-tie"></i> ${role.label}</span>
            <i class="fas ${e.icon || 'fa-robot'}"></i>
        </div>
        <div class="mp-card-body">
            <h3 class="mp-card-title">${e.name}</h3>
            <p class="mp-card-desc">${(e.description || '').substring(0, 130)}${(e.description || '').length > 130 ? '...' : ''}</p>
            <div class="mp-card-seller" style="color:${catColor};"><i class="fas fa-tag"></i> ${catLabel}</div>
            <div class="mp-card-meta">
                <span style="background:${diff.color}22;color:${diff.color};padding:2px 8px;border-radius:8px;font-size:0.75rem;font-weight:600;">${diff.label}</span>
                <span class="mp-card-downloads"><i class="fas fa-wrench"></i> ${toolCount} tools</span>
                <span class="mp-card-downloads"><i class="fas fa-clock"></i> ${e.estimated_setup || '3 min'}</span>
            </div>
            <div class="mp-card-footer">
                <span class="mp-price free">Free</span>
                <button class="mp-btn-cart" onclick="hireEmployee(this, '${e.id}')"><i class="fas fa-user-plus"></i> Hire</button>
            </div>
        </div>
    </div>`;
}

async function loadEmployees(params = '') {
    const grid = document.getElementById('mpGrid');
    grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:3rem;color:var(--mp-text-muted);"><i class="fas fa-spinner fa-spin" style="font-size:2rem;color:var(--mp-accent);"></i><p style="margin-top:1rem;">Loading AI employees...</p></div>';
    try {
        const resp = await fetch('/api/marketplace.php?action=list' + params, { credentials: 'same-origin' });
        const data = await resp.json();
        if (data.success && data.employees && data.employees.length > 0) {
            allEmployees = data.employees;
            grid.innerHTML = data.employees.map(renderCard).join('');
        } else {
            grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:3rem;color:var(--mp-text-muted);"><p>No AI employees match your criteria.</p></div>';
        }
    } catch (err) {
        grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:3rem;color:var(--mp-text-muted);"><p>Failed to load employees. Please refresh.</p></div>';
    }
}

async function hireEmployee(btn, templateId) {
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Hiring...';
    btn.disabled = true;
    try {
        const resp = await fetch('/api/marketplace.php?action=hire', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': window.AW_CSRF_TOKEN || '' },
            credentials: 'same-origin',
            body: 'template_id=' + encodeURIComponent(templateId)
        });
        const data = await resp.json();
        if (data.success) {
            if (data.already_hired) {
                btn.innerHTML = '<i class="fas fa-check"></i> On Team';
                btn.style.background = 'var(--mp-blue)';
            } else {
                btn.innerHTML = '<i class="fas fa-check"></i> Hired!';
                btn.style.background = 'var(--mp-green)';
                myTeamCount++;
                document.getElementById('cartCount').textContent = myTeamCount;
                const icon = document.getElementById('cartIcon');
                icon.style.transform = 'scale(1.3)';
                setTimeout(() => icon.style.transform = '', 300);
                loadMyTeam();
            }
            // Show fleet info
            if (data.fleet_name) {
                const msg = document.createElement('div');
                msg.style.cssText = 'position:fixed;bottom:20px;right:20px;background:var(--mp-green);color:#fff;padding:12px 20px;border-radius:12px;font-size:0.9rem;z-index:9999;box-shadow:0 4px 20px rgba(0,0,0,0.3);animation:fadeIn 0.3s;';
                msg.innerHTML = '<i class="fas fa-check-circle"></i> ' + (data.message || 'Deployed to ' + data.fleet_name);
                document.body.appendChild(msg);
                setTimeout(() => msg.remove(), 4000);
            }
        } else {
            if (data.error && data.error.includes('Login')) {
                btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Login to Hire';
                btn.onclick = () => { window.location.href = '/alfred.php'; };
                btn.disabled = false;
                btn.style.background = 'var(--mp-accent)';
            } else {
                btn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + (data.error || 'Error');
                setTimeout(() => { btn.innerHTML = '<i class="fas fa-user-plus"></i> Retry'; btn.disabled = false; btn.style.background = ''; }, 2000);
            }
        }
    } catch (err) {
        btn.innerHTML = '<i class="fas fa-user-plus"></i> Retry';
        btn.disabled = false;
    }
}

async function loadMyTeam() {
    try {
        const resp = await fetch('/api/marketplace.php?action=my_team', { credentials: 'same-origin' });
        const data = await resp.json();
        if (data.success) {
            myTeamCount = data.count;
            document.getElementById('cartCount').textContent = myTeamCount;
            const list = document.getElementById('myTeamList');
            if (data.employees && data.employees.length > 0) {
                list.innerHTML = data.employees.map(em => `<div style="display:flex;align-items:center;gap:10px;padding:8px 12px;border-bottom:1px solid var(--mp-border);">
                    <i class="fas fa-user-tie" style="color:var(--mp-accent);"></i>
                    <div style="flex:1;">
                        <div style="color:var(--mp-text);font-weight:600;font-size:0.9rem;">${em.agent_name}</div>
                        <div style="color:var(--mp-text-muted);font-size:0.8rem;">${em.agent_role} · ${em.agent_status || 'queued'}</div>
                    </div>
                    <span style="font-size:0.75rem;color:var(--mp-text-muted);">${em.hired_at ? new Date(em.hired_at).toLocaleDateString() : ''}</span>
                </div>`).join('');
            } else {
                list.innerHTML = '<p style="padding:1rem;color:var(--mp-text-muted);">No AI employees hired yet. Browse above and click "Hire" to get started!</p>';
            }
        }
    } catch (e) {}
}

/* Seller Dashboard Toggle */
function toggleSeller() {
    const panel = document.getElementById('sellerPanel');
    const toggle = document.getElementById('sellerToggle');
    panel.classList.toggle('show');
    toggle.classList.toggle('open');
}

/* Sort */
document.getElementById('mpSort').addEventListener('change', function() {
    loadEmployees('&sort=' + this.value);
});

/* Role Filter */
document.getElementById('mpRole').addEventListener('change', function() {
    const v = this.value;
    if (v === 'all') { loadEmployees(''); return; }
    loadEmployees('&role=' + v);
});

/* Difficulty Filter */
document.getElementById('mpDifficulty').addEventListener('change', function() {
    const v = this.value;
    if (v === 'all') { loadEmployees(''); return; }
    loadEmployees('&difficulty=' + v);
});

/* Category Tabs */
document.querySelectorAll('.mp-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.mp-tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        const cat = tab.dataset.cat;
        loadEmployees(cat === 'all' ? '' : '&category=' + cat);
    });
});

/* Search */
const mpSearchInput = document.getElementById('mpSearch');
let mpDebounce;
mpSearchInput.addEventListener('input', function() {
    const q = this.value.trim();
    if (q.length >= 2) {
        clearTimeout(mpDebounce);
        mpDebounce = setTimeout(() => loadEmployees('&q=' + encodeURIComponent(q)), 400);
    } else if (q.length === 0) {
        loadEmployees('');
    }
});

/* Load stats */
async function loadStats() {
    try {
        const resp = await fetch('/api/marketplace.php?action=stats');
        const data = await resp.json();
        if (data.success && data.stats) {
            const el = document.getElementById('statHired');
            if (el) el.textContent = data.stats.total_hired || '0';
        }
    } catch (e) {}
}

/* Boot */
loadEmployees('');
loadMyTeam();
loadStats();
