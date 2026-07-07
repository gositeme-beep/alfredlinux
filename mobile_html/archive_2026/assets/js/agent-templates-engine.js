/* ── Template data in JS for modal/search ── */
const TEMPLATES = window._atTemplates || [];
const CATEGORIES = window._atCategories || [];
const IS_LOGGED_IN = window._atLoggedIn || false;
const DIFF_COLORS = window._atDiffColors || {};
const esc = s => String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');

/* ── Indexed ── */
const TEMPLATE_MAP = {};
TEMPLATES.forEach(t => TEMPLATE_MAP[t.id] = t);

/* ── Search & Filter ── */
const searchInput = document.getElementById('atSearch');
const tabs = document.querySelectorAll('.at-tab');
const cards = document.querySelectorAll('.at-card');
const sections = document.querySelectorAll('.at-category-section');
const noResults = document.getElementById('atNoResults');
let currentCategory = 'all';

searchInput.addEventListener('input', filterTemplates);
tabs.forEach(tab => tab.addEventListener('click', () => {
    tabs.forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    currentCategory = tab.dataset.category;
    filterTemplates();
}));

function filterTemplates() {
    const q = searchInput.value.toLowerCase().trim();
    let visibleCount = 0;
    const visibleCategories = new Set();

    cards.forEach(card => {
        const matchesCat = currentCategory === 'all' || card.dataset.category === currentCategory;
        const matchesSearch = !q
            || card.dataset.name.includes(q)
            || card.dataset.tags.includes(q)
            || card.dataset.desc.includes(q);
        const show = matchesCat && matchesSearch;
        card.style.display = show ? '' : 'none';
        if (show) {
            visibleCount++;
            visibleCategories.add(card.dataset.category);
        }
    });

    sections.forEach(sec => {
        const cat = sec.dataset.category;
        if (currentCategory !== 'all' && cat !== currentCategory) {
            sec.style.display = 'none';
        } else {
            sec.style.display = visibleCategories.has(cat) ? '' : 'none';
        }
    });

    noResults.style.display = visibleCount === 0 ? 'block' : 'none';
}

/* ── Preview Modal ── */
function previewTemplate(id) {
    const t = TEMPLATE_MAP[id];
    if (!t) return;
    const catMeta = CATEGORIES[t.category] || { color: '#6c5ce7', icon: 'fa-folder', label: t.category };
    const diffColor = DIFF_COLORS[t.difficulty] || '#a29bfe';

    document.getElementById('modalIcon').innerHTML = `<i class="fa-solid ${esc(t.icon)}"></i>`;
    document.getElementById('modalIcon').style.background = catMeta.color;
    document.getElementById('modalName').textContent = t.name;
    document.getElementById('modalBadges').innerHTML = `
        <span class="at-badge at-badge-role"><i class="fa-solid fa-user-tag"></i> ${esc(t.agent_role)}</span>
        <span class="at-badge at-badge-diff" style="background:${diffColor}20;color:${diffColor}">${esc(t.difficulty)}</span>
        <span class="at-badge" style="background:${catMeta.color}20;color:${catMeta.color}">${esc(catMeta.label)}</span>
    `;
    document.getElementById('modalDesc').textContent = t.description;
    document.getElementById('modalTask').textContent = t.default_task;

    document.getElementById('modalTools').innerHTML = t.tools.map(tool =>
        `<span class="at-modal-tool"><i class="fa-solid fa-wrench" style="font-size:.7rem;margin-right:3px"></i>${esc(tool)}</span>`
    ).join('');

    document.getElementById('modalConfig').innerHTML = Object.entries(t.config).map(([k, v]) =>
        `<div class="at-modal-config-row"><span class="key">${esc(k)}</span><span class="val">${esc(v)}</span></div>`
    ).join('');

    document.getElementById('modalInfo').innerHTML = `
        <div class="at-modal-info-item"><div class="label">Setup Time</div><div class="value">${esc(t.estimated_setup)}</div></div>
        <div class="at-modal-info-item"><div class="label">Difficulty</div><div class="value" style="color:${diffColor}">${esc(t.difficulty.charAt(0).toUpperCase() + t.difficulty.slice(1))}</div></div>
        <div class="at-modal-info-item"><div class="label">Tools Included</div><div class="value">${t.tools.length}</div></div>
        <div class="at-modal-info-item"><div class="label">Agent Role</div><div class="value">${esc(t.agent_role)}</div></div>
    `;

    const deployBtn = document.getElementById('modalDeployBtn');
    if (IS_LOGGED_IN) {
        deployBtn.innerHTML = '<i class="fa-solid fa-rocket"></i> Deploy This Agent';
        deployBtn.onclick = () => { closeModal(); deployTemplate(id); };
    } else {
        deployBtn.innerHTML = '<i class="fa-solid fa-arrow-right-to-bracket"></i> Sign Up to Deploy';
        deployBtn.onclick = () => { window.location.href = '/?login=1'; };
    }

    document.getElementById('atModal').classList.add('visible');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('atModal').classList.remove('visible');
    document.body.style.overflow = '';
}

document.getElementById('atModal').addEventListener('click', e => {
    if (e.target === e.currentTarget) closeModal();
});
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeModal();
});

/* ── Deploy ── */
async function deployTemplate(id) {
    if (!IS_LOGGED_IN) {
        window.location.href = '/?login=1';
        return;
    }

    const t = TEMPLATE_MAP[id];
    if (!t) return;

    /* Find & disable the deploy button on the card */
    const card = document.querySelector(`.at-card[data-id="${id}"]`);
    const btn = card ? card.querySelector('.at-btn-deploy') : null;
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Deploying…';
    }

    try {
        const form = new FormData();
        form.append('action', 'deploy');
        form.append('template_id', id);

        const res = await fetch('/api/agent-templates.php?action=deploy', {
            method: 'POST',
            credentials: 'same-origin',
            body: form
        });
        const data = await res.json();

        if (data.success) {
            showToast(`${t.name} deployed! Fleet: ${data.fleet_name}`, false);
            if (btn) {
                btn.innerHTML = '<i class="fa-solid fa-check"></i> Deployed!';
                setTimeout(() => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-rocket"></i> Deploy';
                }, 3000);
            }
        } else {
            showToast(data.error || 'Deployment failed', true);
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-rocket"></i> Deploy';
            }
        }
    } catch (err) {
        showToast('Network error. Please try again.', true);
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-rocket"></i> Deploy';
        }
    }
}

function showToast(msg, isError) {
    if (window.GDSToast) return GDSToast.show(msg, { type: isError ? 'danger' : 'success' });
}

/* ── Smooth scroll to category on tab click ── */
tabs.forEach(tab => {
    tab.addEventListener('click', () => {
        if (tab.dataset.category !== 'all') {
            const section = document.getElementById('cat-' + tab.dataset.category);
            if (section) {
                setTimeout(() => section.scrollIntoView({ behavior: 'smooth', block: 'start' }), 100);
            }
        }
    });
});
