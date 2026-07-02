<?php
/**
 * Project Creator — Admin Dashboard
 * ═══════════════════════════════════════════════════════
 * Create and manage projects using the Editor or Alfred IDE.
 * Owner-only (client_id 33).
 */
$page_title = 'Project Creator — GoSiteMe Admin';
$page_description = 'Create and manage projects from a central admin dashboard.';
$page_canonical = 'https://root.com/project-creator';
$page_robots = 'noindex, nofollow';

include __DIR__ . '/includes/auth-gate.inc.php';
include __DIR__ . '/includes/site-header.inc.php';

// Owner-only gate
if ((int)($clientId ?? 0) !== 33) {
    header('Location: /dashboard');
    exit;
}
?>

<style>
/* ── Project Creator Theme ────────────────────────────────────── */
.pc-page {
    max-width: 1100px;
    margin: 0 auto;
    padding: 2rem 1.5rem 4rem;
    min-height: 70vh;
}
.pc-page h1 {
    font-size: 2rem;
    margin-bottom: 0.25rem;
}
.pc-page h1 i { color: var(--alfred-primary, #6c5ce7); }
.pc-subtitle {
    color: var(--text-secondary, #888);
    margin-bottom: 2rem;
}

/* ── Tabs ────────────────────────────────────────────────────── */
.pc-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
    border-bottom: 1px solid var(--border, #333);
    padding-bottom: 0.5rem;
    flex-wrap: wrap;
}
.pc-tab {
    padding: 10px 20px;
    border-radius: 10px 10px 0 0;
    border: none;
    background: transparent;
    color: var(--text-secondary, #888);
    cursor: pointer;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.2s;
}
.pc-tab.active {
    background: var(--surface-1, #12121a);
    color: var(--alfred-primary, #6c5ce7);
    border-bottom: 2px solid var(--alfred-primary, #6c5ce7);
}
.pc-tab:hover { color: var(--text-primary, #fff); }
.pc-panel { display: none; }
.pc-panel.active { display: block; }

/* ── Create Form ─────────────────────────────────────────────── */
.pc-form {
    background: var(--surface-1, #12121a);
    border: 1px solid var(--border, #333);
    border-radius: 16px;
    padding: 2rem;
    max-width: 600px;
}
.pc-form label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.4rem;
    font-size: 0.9rem;
}
.pc-form input,
.pc-form select,
.pc-form textarea {
    width: 100%;
    padding: 12px;
    border-radius: 10px;
    border: 1px solid var(--border, #333);
    background: var(--surface-2, #1e1e2e);
    color: var(--text-primary, #fff);
    font-size: 0.95rem;
    margin-bottom: 1.25rem;
    box-sizing: border-box;
    font-family: inherit;
}
.pc-form textarea { resize: vertical; min-height: 80px; }
.pc-form input:focus,
.pc-form select:focus,
.pc-form textarea:focus {
    outline: none;
    border-color: var(--alfred-primary, #6c5ce7);
}

.pc-btn {
    padding: 12px 28px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    font-weight: 600;
    font-size: 0.95rem;
    transition: opacity 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.pc-btn:hover { opacity: 0.85; }
.pc-btn-primary {
    background: var(--alfred-primary, #6c5ce7);
    color: #fff;
}
.pc-btn-secondary {
    background: var(--surface-2, #1e1e2e);
    color: var(--text-primary, #fff);
    border: 1px solid var(--border, #333);
}
.pc-btn:disabled { opacity: 0.5; cursor: not-allowed; }

/* ── Template Grid ───────────────────────────────────────────── */
.pc-templates {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 1rem;
}
.pc-template-card {
    background: var(--surface-1, #12121a);
    border: 1px solid var(--border, #333);
    border-radius: 16px;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    cursor: pointer;
    transition: border-color 0.2s, transform 0.15s;
}
.pc-template-card:hover {
    border-color: var(--alfred-primary, #6c5ce7);
    transform: translateY(-2px);
}
.pc-template-card.selected {
    border-color: var(--alfred-primary, #6c5ce7);
    background: rgba(108, 92, 231, 0.08);
}
.pc-template-icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 0.75rem;
}
.pc-template-name {
    font-weight: 600;
    margin-bottom: 0.25rem;
}
.pc-template-desc {
    font-size: 0.8rem;
    color: var(--text-secondary, #888);
}

/* ── Projects List ───────────────────────────────────────────── */
.pc-project-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
}
.pc-project-card {
    background: var(--surface-1, #12121a);
    border: 1px solid var(--border, #333);
    border-radius: 16px;
    padding: 1.5rem;
    transition: border-color 0.2s;
}
.pc-project-card:hover { border-color: var(--alfred-primary, #6c5ce7); }
.pc-project-card h3 {
    font-size: 1.1rem;
    margin-bottom: 0.25rem;
}
.pc-project-meta {
    font-size: 0.8rem;
    color: var(--text-secondary, #888);
    margin-bottom: 1rem;
}
.pc-project-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }

/* ── Ask Alfred ──────────────────────────────────────────────── */
.pc-alfred-chat {
    background: var(--surface-1, #12121a);
    border: 1px solid var(--border, #333);
    border-radius: 16px;
    padding: 2rem;
    max-width: 700px;
}
.pc-alfred-chat p {
    color: var(--text-secondary, #888);
    margin-bottom: 1rem;
}
.pc-alfred-prompt {
    width: 100%;
    padding: 14px;
    border-radius: 12px;
    border: 1px solid var(--border, #333);
    background: var(--surface-2, #1e1e2e);
    color: var(--text-primary, #fff);
    font-size: 1rem;
    min-height: 100px;
    resize: vertical;
    font-family: inherit;
    box-sizing: border-box;
    margin-bottom: 1rem;
}
.pc-alfred-result {
    background: var(--surface-2, #1e1e2e);
    border-radius: 12px;
    padding: 1.5rem;
    margin-top: 1rem;
    display: none;
    white-space: pre-wrap;
    line-height: 1.6;
    font-size: 0.9rem;
}

/* ── Status / Toast ──────────────────────────────────────────── */
.pc-status {
    padding: 12px 16px;
    border-radius: 10px;
    margin-bottom: 1rem;
    font-size: 0.9rem;
    display: none;
}
.pc-status.success { display: block; background: rgba(34,197,94,0.15); color: #22c55e; border: 1px solid rgba(34,197,94,0.3); }
.pc-status.error { display: block; background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); }

@media (max-width: 768px) {
    .pc-page { padding: 1.5rem 1rem 3rem; }
    .pc-page h1 { font-size: 1.5rem; }
    .pc-templates { grid-template-columns: 1fr; }
    .pc-project-list { grid-template-columns: 1fr; }
    .pc-form { padding: 1.25rem; }
}
</style>

<div class="pc-page">
    <h1><i class="fas fa-folder-plus"></i> Project Creator</h1>
    <p class="pc-subtitle">Create new projects, pick templates, or ask Alfred to build something for you.</p>

    <!-- Tabs -->
    <div class="pc-tabs">
        <button class="pc-tab active" data-tab="create"><i class="fas fa-plus-circle"></i> Create Project</button>
        <button class="pc-tab" data-tab="templates"><i class="fas fa-copy"></i> Templates</button>
        <button class="pc-tab" data-tab="projects"><i class="fas fa-folder-open"></i> My Projects</button>
        <button class="pc-tab" data-tab="alfred"><i class="fas fa-robot"></i> Ask Alfred</button>
    </div>

    <div id="pc-status" class="pc-status"></div>

    <!-- ═══ CREATE PANEL ═══ -->
    <div class="pc-panel active" id="panel-create">
        <div class="pc-form">
            <label for="pc-name">Project Name</label>
            <input type="text" id="pc-name" placeholder="My Awesome Project" maxlength="100" autofocus>

            <label for="pc-type">Project Type</label>
            <select id="pc-type">
                <option value="editor">Editor (HTML/CSS/JS)</option>
                <option value="gocodeme">Alfred IDE Workspace</option>
            </select>

            <div id="pc-editor-fields">
                <label for="pc-desc">Description (optional)</label>
                <textarea id="pc-desc" placeholder="What is this project about?" maxlength="500"></textarea>
            </div>

            <div id="pc-gocodeme-fields" style="display:none">
                <label for="pc-template">Template</label>
                <select id="pc-template">
                    <option value="static-html">Static Website (HTML/CSS/JS)</option>
                    <option value="nextjs">Next.js App</option>
                    <option value="react-vite">React + Vite</option>
                    <option value="vue-vite">Vue 3 + Vite</option>
                    <option value="express-api">Express API</option>
                    <option value="python-flask">Python Flask API</option>
                    <option value="php-laravel">Laravel API</option>
                    <option value="wordpress">WordPress Theme</option>
                </select>
            </div>

            <button class="pc-btn pc-btn-primary" id="pc-create-btn" onclick="createProject()">
                <i class="fas fa-rocket"></i> Create Project
            </button>
        </div>
    </div>

    <!-- ═══ TEMPLATES PANEL ═══ -->
    <div class="pc-panel" id="panel-templates">
        <div class="pc-templates">
            <div class="pc-template-card" onclick="useTemplate('static-html')">
                <div class="pc-template-icon" style="background:rgba(108,92,231,0.15);color:#6c5ce7"><i class="fas fa-code"></i></div>
                <div class="pc-template-name">Static Website</div>
                <div class="pc-template-desc">HTML, CSS, JavaScript</div>
            </div>
            <div class="pc-template-card" onclick="useTemplate('nextjs')">
                <div class="pc-template-icon" style="background:rgba(0,0,0,0.3);color:#fff"><i class="fab fa-react"></i></div>
                <div class="pc-template-name">Next.js App</div>
                <div class="pc-template-desc">React framework with SSR</div>
            </div>
            <div class="pc-template-card" onclick="useTemplate('react-vite')">
                <div class="pc-template-icon" style="background:rgba(97,218,251,0.15);color:#61dafb"><i class="fab fa-react"></i></div>
                <div class="pc-template-name">React + Vite</div>
                <div class="pc-template-desc">Fast React development</div>
            </div>
            <div class="pc-template-card" onclick="useTemplate('vue-vite')">
                <div class="pc-template-icon" style="background:rgba(65,184,131,0.15);color:#41b883"><i class="fab fa-vuejs"></i></div>
                <div class="pc-template-name">Vue 3 + Vite</div>
                <div class="pc-template-desc">Progressive framework</div>
            </div>
            <div class="pc-template-card" onclick="useTemplate('express-api')">
                <div class="pc-template-icon" style="background:rgba(104,159,56,0.15);color:#689f38"><i class="fab fa-node-js"></i></div>
                <div class="pc-template-name">Express API</div>
                <div class="pc-template-desc">Node.js REST backend</div>
            </div>
            <div class="pc-template-card" onclick="useTemplate('python-flask')">
                <div class="pc-template-icon" style="background:rgba(55,118,171,0.15);color:#3776ab"><i class="fab fa-python"></i></div>
                <div class="pc-template-name">Python Flask</div>
                <div class="pc-template-desc">Lightweight Python API</div>
            </div>
            <div class="pc-template-card" onclick="useTemplate('php-laravel')">
                <div class="pc-template-icon" style="background:rgba(255,45,32,0.15);color:#ff2d20"><i class="fab fa-laravel"></i></div>
                <div class="pc-template-name">Laravel</div>
                <div class="pc-template-desc">PHP framework</div>
            </div>
            <div class="pc-template-card" onclick="useTemplate('wordpress')">
                <div class="pc-template-icon" style="background:rgba(33,117,155,0.15);color:#21759b"><i class="fab fa-wordpress"></i></div>
                <div class="pc-template-name">WordPress</div>
                <div class="pc-template-desc">Theme development</div>
            </div>
        </div>
    </div>

    <!-- ═══ MY PROJECTS PANEL ═══ -->
    <div class="pc-panel" id="panel-projects">
        <div class="pc-project-list" id="pc-projects-container">
            <div style="color:var(--text-secondary);text-align:center;padding:2rem;grid-column:1/-1">
                <i class="fas fa-spinner fa-spin" style="font-size:2rem;margin-bottom:1rem;display:block"></i>
                Loading projects...
            </div>
        </div>
    </div>

    <!-- ═══ ASK ALFRED PANEL ═══ -->
    <div class="pc-panel" id="panel-alfred">
        <div class="pc-alfred-chat">
            <p><i class="fas fa-robot" style="color:var(--alfred-primary, #6c5ce7)"></i> Tell Alfred what you want to build. He'll create the project structure for you.</p>
            <textarea class="pc-alfred-prompt" id="pc-alfred-prompt" placeholder="Example: Create a landing page for a coffee shop with a menu, gallery, and contact form..."></textarea>
            <button class="pc-btn pc-btn-primary" id="pc-alfred-btn" onclick="askAlfred()">
                <i class="fas fa-wand-magic-sparkles"></i> Ask Alfred
            </button>
            <div class="pc-alfred-result" id="pc-alfred-result"></div>
        </div>
    </div>
</div>

<script>
(function() {
    // Tab switching
    document.querySelectorAll('.pc-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.pc-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.pc-panel').forEach(p => p.classList.remove('active'));
            tab.classList.add('active');
            document.getElementById('panel-' + tab.dataset.tab)?.classList.add('active');
            if (tab.dataset.tab === 'projects') loadProjects();
        });
    });

    // Toggle fields based on project type
    document.getElementById('pc-type').addEventListener('change', function() {
        document.getElementById('pc-editor-fields').style.display = this.value === 'editor' ? '' : 'none';
        document.getElementById('pc-gocodeme-fields').style.display = this.value === 'gocodeme' ? '' : 'none';
    });

    function status(msg, type) {
        const el = document.getElementById('pc-status');
        el.textContent = msg;
        el.className = 'pc-status ' + type;
        if (type === 'success') setTimeout(() => { el.className = 'pc-status'; }, 4000);
    }

    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    // ── Create Project ──────────────────────────────────────────
    window.createProject = async function() {
        const name = document.getElementById('pc-name').value.trim();
        const type = document.getElementById('pc-type').value;

        if (!name) { status('Please enter a project name', 'error'); return; }

        const btn = document.getElementById('pc-create-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';

        try {
            if (type === 'editor') {
                const desc = document.getElementById('pc-desc').value.trim();
                const res = await fetch('/editor/api/projects.php?action=create', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify({ name, description: desc }),
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.error || 'Failed to create project');
                status('Project "' + escapeHtml(name) + '" created! Opening editor...', 'success');
                setTimeout(() => { window.open('/editor/?project=' + data.project_id, '_blank'); }, 1000);
            } else {
                const template = document.getElementById('pc-template').value;
                const res = await fetch('/gocodeme/middleware/api/templates/apply', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify({ template_id: template, project_name: name }),
                });
                if (res.ok) {
                    status('Alfred IDE workspace "' + escapeHtml(name) + '" created with ' + template + ' template!', 'success');
                } else {
                    const data = await res.json().catch(() => ({}));
                    throw new Error(data.error || 'Failed to create workspace.');
                }
            }
            document.getElementById('pc-name').value = '';
            document.getElementById('pc-desc').value = '';
        } catch (err) {
            status(err.message, 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-rocket"></i> Create Project';
        }
    };

    // ── Use Template (from templates tab) ────────────────────────
    window.useTemplate = function(templateId) {
        // Switch to create tab, set gocodeme + template
        document.querySelectorAll('.pc-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.pc-panel').forEach(p => p.classList.remove('active'));
        document.querySelector('.pc-tab[data-tab="create"]').classList.add('active');
        document.getElementById('panel-create').classList.add('active');

        document.getElementById('pc-type').value = 'gocodeme';
        document.getElementById('pc-type').dispatchEvent(new Event('change'));
        document.getElementById('pc-template').value = templateId;
        document.getElementById('pc-name').focus();

        status('Template selected: ' + templateId + '. Enter a name and click Create.', 'success');
    };

    // ── Load Projects ───────────────────────────────────────────
    async function loadProjects() {
        const container = document.getElementById('pc-projects-container');
        try {
            const res = await fetch('/editor/api/projects.php?action=list', { credentials: 'same-origin' });
            const data = await res.json();
            if (!res.ok) throw new Error(data.error || 'Failed to load');

            const projects = data.projects || [];
            if (projects.length === 0) {
                container.innerHTML = '<div style="color:var(--text-secondary);text-align:center;padding:2rem;grid-column:1/-1"><i class="fas fa-folder-open" style="font-size:2rem;margin-bottom:1rem;display:block;opacity:0.5"></i>No projects yet. Create one from the Create tab!</div>';
                return;
            }

            container.innerHTML = projects.map(p => `
                <div class="pc-project-card">
                    <h3>${escapeHtml(p.name || 'Untitled')}</h3>
                    <div class="pc-project-meta">
                        Created: ${new Date(p.created_at).toLocaleDateString()}
                        ${p.published ? ' · <span style="color:var(--green,#22c55e)">Published</span>' : ''}
                    </div>
                    <div class="pc-project-actions">
                        <a href="/editor/?project=${p.id}" target="_blank" class="pc-btn pc-btn-primary" style="font-size:0.8rem;padding:8px 16px"><i class="fas fa-edit"></i> Edit</a>
                        ${p.slug ? `<a href="/sites/${p.slug}" target="_blank" class="pc-btn pc-btn-secondary" style="font-size:0.8rem;padding:8px 16px"><i class="fas fa-eye"></i> View</a>` : ''}
                        <button class="pc-btn pc-btn-secondary" style="font-size:0.8rem;padding:8px 16px;color:var(--danger,#ef4444)" onclick="deleteProject(${p.id},'${escapeHtml(p.name)}')"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            `).join('');
        } catch (err) {
            container.innerHTML = '<div style="color:var(--text-secondary);text-align:center;padding:2rem;grid-column:1/-1">' + escapeHtml(err.message) + '</div>';
        }
    }

    window.deleteProject = async function(id, name) {
        if (!confirm('Delete project "' + name + '"? This cannot be undone.')) return;
        try {
            const res = await fetch('/editor/api/projects.php?action=delete&id=' + id, {
                method: 'DELETE',
                credentials: 'same-origin',
            });
            if (!res.ok) throw new Error('Delete failed');
            status('Project deleted', 'success');
            loadProjects();
        } catch (err) {
            status(err.message, 'error');
        }
    };

    // ── Ask Alfred ──────────────────────────────────────────────
    window.askAlfred = async function() {
        const prompt = document.getElementById('pc-alfred-prompt').value.trim();
        if (!prompt) { status('Tell Alfred what you want to build', 'error'); return; }

        const btn = document.getElementById('pc-alfred-btn');
        const result = document.getElementById('pc-alfred-result');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Alfred is thinking...';
        result.style.display = 'block';
        result.textContent = 'Generating project plan...';

        try {
            // Use the trial chat API or the conversations endpoint
            const res = await fetch('/api/trial/chat', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    message: 'You are a project planning assistant. The user wants to create a web project. Generate a brief project plan with: 1) Recommended tech stack, 2) Key files needed, 3) Suggested template from these options: static-html, nextjs, react-vite, vue-vite, express-api, python-flask, php-laravel, wordpress. Here is what they want to build:\n\n' + prompt,
                }),
            });
            const data = await res.json();
            if (data.reply) {
                result.textContent = data.reply;
            } else {
                result.textContent = 'Alfred could not generate a plan. Try describing your project in more detail.';
            }
        } catch (err) {
            result.textContent = 'Error: ' + err.message;
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-wand-magic-sparkles"></i> Ask Alfred';
        }
    };
})();
</script>

<?php include 'includes/site-footer.inc.php'; ?>
