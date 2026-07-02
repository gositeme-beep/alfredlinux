<?php
$pageTitle = "App & Games Maker — GoSiteMe";
$pageDescription = "Create apps, games, and VR experiences with AI-powered tools. Build, test, and publish directly from your browser.";
include 'includes/site-header.inc.php';

$isLoggedIn = !empty($_SESSION['client_id']) || !empty($_SESSION['uid']);
$clientId = (int) ($_SESSION['client_id'] ?? $_SESSION['uid'] ?? 0);
$isOwner = ($clientId === 33);
?>
<style>
.gm-hero { text-align: center; padding: 3rem 1.5rem 2rem; max-width: 900px; margin: 0 auto; }
.gm-hero h1 { font-size: 2.6rem; font-weight: 800; background: linear-gradient(135deg, #f472b6, #a855f7, #60a5fa, #10b981); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: .5rem; line-height: 1.2; }
.gm-hero p { color: rgba(255,255,255,.5); font-size: 1.05rem; max-width: 600px; margin: 0 auto; }

.gm-page { max-width: 1100px; margin: 0 auto; padding: 0 1.5rem 3rem; }

/* Category selector */
.gm-cats { display: flex; gap: .75rem; flex-wrap: wrap; justify-content: center; margin-bottom: 2rem; }
.gm-cat { padding: .6rem 1.3rem; border-radius: 12px; border: 1px solid rgba(255,255,255,.08); background: rgba(255,255,255,.03); color: rgba(255,255,255,.5); cursor: pointer; font-size: .85rem; font-weight: 500; transition: .2s; display: flex; align-items: center; gap: .4rem; }
.gm-cat:hover { border-color: rgba(168,85,247,.3); color: #fff; }
.gm-cat.active { background: rgba(168,85,247,.15); border-color: #a855f7; color: #fff; }
.gm-cat .gm-cat-icon { font-size: 1.1rem; }

/* Template grid */
.gm-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.2rem; }

.gm-template { background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.06); border-radius: 16px; overflow: hidden; transition: .3s; cursor: pointer; position: relative; }
.gm-template:hover { border-color: rgba(168,85,247,.3); transform: translateY(-2px); box-shadow: 0 8px 30px rgba(0,0,0,.3); }
.gm-template-img { height: 160px; display: flex; align-items: center; justify-content: center; font-size: 3.5rem; position: relative; overflow: hidden; }
.gm-template-body { padding: 1.2rem; }
.gm-template-title { font-size: 1.05rem; font-weight: 700; margin-bottom: .3rem; }
.gm-template-desc { font-size: .8rem; color: rgba(255,255,255,.4); line-height: 1.5; margin-bottom: .75rem; }
.gm-template-tags { display: flex; gap: .35rem; flex-wrap: wrap; }
.gm-tag { font-size: .65rem; padding: 2px 8px; border-radius: 6px; background: rgba(255,255,255,.05); color: rgba(255,255,255,.35); }
.gm-tag.new { background: rgba(16,185,129,.15); color: #10b981; }
.gm-tag.vr { background: rgba(168,85,247,.15); color: #a855f7; }
.gm-tag.ai { background: rgba(96,165,250,.15); color: #60a5fa; }
.gm-tag.hot { background: rgba(239,68,68,.15); color: #ef4444; }

/* Flagship banner */
.gm-flagship { background: linear-gradient(135deg, rgba(168,85,247,.08), rgba(244,114,182,.08)); border: 1px solid rgba(168,85,247,.2); border-radius: 20px; padding: 2rem; margin-bottom: 2.5rem; display: flex; align-items: center; gap: 2rem; }
.gm-flagship-icon { font-size: 4rem; flex-shrink: 0; }
.gm-flagship-content h2 { font-size: 1.5rem; font-weight: 800; margin-bottom: .4rem; }
.gm-flagship-content h2 span { background: linear-gradient(135deg, #f472b6, #a855f7); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.gm-flagship-content p { color: rgba(255,255,255,.5); font-size: .9rem; line-height: 1.5; }
.gm-flagship-btn { margin-top: 1rem; display: inline-flex; align-items: center; gap: .5rem; padding: .7rem 1.5rem; background: linear-gradient(135deg, #a855f7, #f472b6); border-radius: 10px; color: #fff; text-decoration: none; font-weight: 700; font-size: .9rem; transition: .2s; }
.gm-flagship-btn:hover { opacity: .85; transform: scale(1.02); }

/* Create modal */
.gm-create-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.7); z-index: 10000; align-items: center; justify-content: center; }
.gm-create-overlay.show { display: flex; }
.gm-create-modal { background: #12121f; border: 1px solid rgba(255,255,255,.1); border-radius: 20px; padding: 2rem; width: 90%; max-width: 600px; max-height: 85vh; overflow-y: auto; }
.gm-create-modal h2 { font-size: 1.3rem; margin-bottom: 1rem; }
.gm-create-modal label { display: block; font-size: .8rem; color: rgba(255,255,255,.5); margin-bottom: .3rem; margin-top: 1rem; }
.gm-create-modal input, .gm-create-modal select, .gm-create-modal textarea { width: 100%; background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.1); border-radius: 10px; padding: .7rem 1rem; color: #fff; font-size: .9rem; outline: none; }
.gm-create-modal textarea { min-height: 80px; resize: vertical; }
.gm-create-btns { display: flex; gap: .75rem; margin-top: 1.5rem; justify-content: flex-end; }
.gm-create-btns button { padding: .65rem 1.3rem; border-radius: 10px; border: none; font-weight: 600; cursor: pointer; font-size: .85rem; }
.gm-btn-create { background: linear-gradient(135deg, #a855f7, #f472b6); color: #fff; }
.gm-btn-cancel { background: rgba(255,255,255,.08); color: rgba(255,255,255,.6); }

/* My projects section */
.gm-my { margin-top: 2.5rem; }
.gm-my h2 { font-size: 1.2rem; margin-bottom: 1rem; display: flex; align-items: center; gap: .5rem; }
.gm-project-list { display: grid; gap: .6rem; }
.gm-project { background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.06); border-radius: 12px; padding: 1rem 1.2rem; display: flex; align-items: center; justify-content: space-between; transition: .2s; }
.gm-project:hover { border-color: rgba(168,85,247,.2); }
.gm-project-name { font-weight: 600; }
.gm-project-meta { font-size: .75rem; color: rgba(255,255,255,.3); }

/* Features */
.gm-features { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 2.5rem 0; }
.gm-feature { background: rgba(255,255,255,.02); border: 1px solid rgba(255,255,255,.05); border-radius: 14px; padding: 1.5rem; text-align: center; }
.gm-feature i { font-size: 1.8rem; margin-bottom: .75rem; display: block; }
.gm-feature h3 { font-size: .9rem; margin-bottom: .3rem; }
.gm-feature p { font-size: .78rem; color: rgba(255,255,255,.35); line-height: 1.5; }

@media (max-width: 768px) {
    .gm-hero h1 { font-size: 1.8rem; }
    .gm-flagship { flex-direction: column; text-align: center; padding: 1.5rem; }
    .gm-grid { grid-template-columns: 1fr; }
    .gm-cats { gap: .4rem; }
    .gm-cat { padding: .45rem .8rem; font-size: .78rem; }
}
</style>

<div class="gm-hero">
    <h1><i class="fa-solid fa-gamepad"></i> App & Games Maker</h1>
    <p>Build apps, games, and VR experiences with AI-powered tools. From concept to published — all in your browser.</p>
</div>

<div class="gm-page">

    <!-- VR DnD Flagship -->
    <div class="gm-flagship">
        <div class="gm-flagship-icon">🐉</div>
        <div class="gm-flagship-content">
            <h2><span>VR Dungeons & Dragons</span> — Our Flagship Game</h2>
            <p>Step into a fully immersive tabletop experience. AI Dungeon Master, procedurally generated worlds, multiplayer parties, and real-time voice chat. Powered by <strong>vr-dnd.com</strong></p>
            <a href="#" class="gm-flagship-btn" onclick="openCreate('vr-dnd','VR Dungeons & Dragons','A fully immersive VR tabletop RPG with AI Dungeon Master, procedural worlds, multiplayer parties, and voice chat.','vr-game');return false;"><i class="fa-solid fa-vr-cardboard"></i> Start Building VR DnD</a>
        </div>
    </div>

    <!-- Features -->
    <div class="gm-features">
        <div class="gm-feature">
            <i class="fa-solid fa-wand-magic-sparkles" style="color:#a855f7"></i>
            <h3>AI-Powered</h3>
            <p>Alfred generates code, assets, and game logic from natural language descriptions</p>
        </div>
        <div class="gm-feature">
            <i class="fa-solid fa-vr-cardboard" style="color:#f472b6"></i>
            <h3>VR Ready</h3>
            <p>WebXR templates for immersive 3D experiences compatible with all headsets</p>
        </div>
        <div class="gm-feature">
            <i class="fa-solid fa-users" style="color:#60a5fa"></i>
            <h3>Multiplayer</h3>
            <p>Built-in WebSocket multiplayer with rooms, matchmaking, and voice chat</p>
        </div>
        <div class="gm-feature">
            <i class="fa-solid fa-rocket" style="color:#10b981"></i>
            <h3>Instant Publish</h3>
            <p>One-click deploy to your GoSiteMe subdomain or custom domain</p>
        </div>
        <div class="gm-feature">
            <i class="fa-solid fa-store" style="color:#fbbf24"></i>
            <h3>Marketplace</h3>
            <p>Sell your games and apps in the GoSiteMe store and earn revenue</p>
        </div>
        <div class="gm-feature">
            <i class="fa-solid fa-code" style="color:#8b5cf6"></i>
            <h3>Full IDE</h3>
            <p>Alfred IDE integration — full code editor with debugging, preview, and Git</p>
        </div>
    </div>

    <!-- Category tabs -->
    <div class="gm-cats">
        <div class="gm-cat active" data-cat="all" onclick="filterCat('all')"><span class="gm-cat-icon">🎯</span> All</div>
        <div class="gm-cat" data-cat="game" onclick="filterCat('game')"><span class="gm-cat-icon">🎮</span> Games</div>
        <div class="gm-cat" data-cat="vr-game" onclick="filterCat('vr-game')"><span class="gm-cat-icon">🥽</span> VR Games</div>
        <div class="gm-cat" data-cat="app" onclick="filterCat('app')"><span class="gm-cat-icon">📱</span> Apps</div>
        <div class="gm-cat" data-cat="web" onclick="filterCat('web')"><span class="gm-cat-icon">🌐</span> Web Apps</div>
        <div class="gm-cat" data-cat="ai" onclick="filterCat('ai')"><span class="gm-cat-icon">🤖</span> AI Projects</div>
    </div>

    <!-- Template grid -->
    <div class="gm-grid" id="templateGrid"></div>

    <!-- My Projects -->
    <?php if ($isLoggedIn): ?>
    <div class="gm-my">
        <h2><i class="fa-solid fa-folder-open"></i> My Projects</h2>
        <div class="gm-project-list" id="myProjects">
            <div style="text-align:center;padding:1.5rem;color:rgba(255,255,255,.3);font-size:.85rem">
                <i class="fa-solid fa-spinner fa-spin"></i> Loading projects...
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- Create Modal -->
<div class="gm-create-overlay" id="createModal">
    <div class="gm-create-modal">
        <h2><i class="fa-solid fa-plus-circle"></i> <span id="createTitle">Create Project</span></h2>
        <label>Project Name</label>
        <input type="text" id="createName" placeholder="My Awesome Game" maxlength="100">
        <label>Description</label>
        <textarea id="createDesc" placeholder="Describe your project..."></textarea>
        <label>Project Type</label>
        <select id="createType">
            <option value="game">Game (2D)</option>
            <option value="vr-game">VR Game (WebXR)</option>
            <option value="app">Mobile App (PWA)</option>
            <option value="web">Web Application</option>
            <option value="ai">AI-Powered Tool</option>
        </select>
        <label>Template</label>
        <select id="createTemplate">
            <option value="blank">Blank Project</option>
            <option value="platformer">2D Platformer</option>
            <option value="rpg">Turn-Based RPG</option>
            <option value="puzzle">Puzzle Game</option>
            <option value="vr-room">VR Room (WebXR)</option>
            <option value="vr-dnd">VR Dungeons & Dragons</option>
            <option value="chat-app">Chat Application</option>
            <option value="dashboard">Dashboard App</option>
            <option value="ai-chatbot">AI Chatbot</option>
        </select>
        <div class="gm-create-btns">
            <button class="gm-btn-cancel" onclick="closeCreate()">Cancel</button>
            <button class="gm-btn-create" onclick="createProject()"><i class="fa-solid fa-rocket"></i> Create Project</button>
        </div>
    </div>
</div>

<script>
(function() {
    const TEMPLATES = [
        { id: 'vr-dnd', name: 'VR Dungeons & Dragons', desc: 'Immersive tabletop RPG with AI Dungeon Master, procedural worlds, multiplayer parties.', cat: 'vr-game', icon: '🐉', bg: 'linear-gradient(135deg,#7c3aed,#db2777)', tags: ['vr','hot','ai'] },
        { id: 'vr-arena', name: 'VR Battle Arena', desc: 'Multiplayer VR combat game with physics-based weapons and destructible environments.', cat: 'vr-game', icon: '⚔️', bg: 'linear-gradient(135deg,#ef4444,#f97316)', tags: ['vr','new'] },
        { id: 'vr-explorer', name: 'VR World Explorer', desc: 'Procedurally generated open worlds to explore in VR. Collect, build, and survive.', cat: 'vr-game', icon: '🌍', bg: 'linear-gradient(135deg,#10b981,#06b6d4)', tags: ['vr'] },
        { id: 'vr-room', name: 'VR Social Room', desc: 'Create virtual hangout spaces with voice chat, avatars, and interactive objects.', cat: 'vr-game', icon: '🏠', bg: 'linear-gradient(135deg,#6366f1,#8b5cf6)', tags: ['vr'] },
        { id: 'platformer', name: '2D Platformer', desc: 'Classic side-scrolling platformer with physics, enemies, and level editor.', cat: 'game', icon: '🍄', bg: 'linear-gradient(135deg,#f59e0b,#ef4444)', tags: ['new'] },
        { id: 'rpg', name: 'Turn-Based RPG', desc: 'Classic RPG with party system, inventory, quests, and AI-generated dialogue.', cat: 'game', icon: '⚔️', bg: 'linear-gradient(135deg,#8b5cf6,#3b82f6)', tags: ['ai'] },
        { id: 'puzzle', name: 'Puzzle Game', desc: 'Match-3, word puzzles, or logic games with adaptive difficulty.', cat: 'game', icon: '🧩', bg: 'linear-gradient(135deg,#06b6d4,#10b981)', tags: [] },
        { id: 'cards', name: 'Card Game', desc: 'Build collectible card games with deck building and online multiplayer.', cat: 'game', icon: '🃏', bg: 'linear-gradient(135deg,#f472b6,#a855f7)', tags: [] },
        { id: 'trivia', name: 'Trivia / Quiz Game', desc: 'AI-generated questions across categories with leaderboards.', cat: 'game', icon: '❓', bg: 'linear-gradient(135deg,#fbbf24,#f59e0b)', tags: ['ai'] },
        { id: 'chat-app', name: 'Chat Application', desc: 'Real-time messaging app with rooms, encryption, and file sharing.', cat: 'app', icon: '💬', bg: 'linear-gradient(135deg,#3b82f6,#6366f1)', tags: [] },
        { id: 'task-app', name: 'Task Manager', desc: 'Kanban boards, calendars, and productivity tracking with team support.', cat: 'app', icon: '✅', bg: 'linear-gradient(135deg,#10b981,#059669)', tags: [] },
        { id: 'social', name: 'Social Network', desc: 'Build a social platform with profiles, feeds, likes, and messaging.', cat: 'web', icon: '👥', bg: 'linear-gradient(135deg,#ec4899,#f43f5e)', tags: [] },
        { id: 'dashboard', name: 'Analytics Dashboard', desc: 'Charts, graphs, and real-time data visualization for any data source.', cat: 'web', icon: '📊', bg: 'linear-gradient(135deg,#6366f1,#8b5cf6)', tags: [] },
        { id: 'ecommerce', name: 'E-Commerce Store', desc: 'Product listings, cart, checkout, and payment integration.', cat: 'web', icon: '🛒', bg: 'linear-gradient(135deg,#f59e0b,#10b981)', tags: [] },
        { id: 'ai-chatbot', name: 'AI Chatbot', desc: 'Custom AI assistant with personality, knowledge base, and API integrations.', cat: 'ai', icon: '🤖', bg: 'linear-gradient(135deg,#3b82f6,#10b981)', tags: ['ai','hot'] },
        { id: 'ai-art', name: 'AI Art Generator', desc: 'Image generation tool with style transfer, upscaling, and gallery.', cat: 'ai', icon: '🎨', bg: 'linear-gradient(135deg,#ec4899,#8b5cf6)', tags: ['ai','new'] },
        { id: 'ai-writer', name: 'AI Content Writer', desc: 'Blog posts, stories, and marketing copy powered by AI.', cat: 'ai', icon: '✍️', bg: 'linear-gradient(135deg,#14b8a6,#3b82f6)', tags: ['ai'] },
    ];

    let currentCat = 'all';

    function renderTemplates() {
        const grid = document.getElementById('templateGrid');
        const filtered = currentCat === 'all' ? TEMPLATES : TEMPLATES.filter(t => t.cat === currentCat);

        grid.innerHTML = filtered.map(t => `
            <div class="gm-template" onclick="openCreate('${t.id}','${esc(t.name)}','${esc(t.desc)}','${t.cat}')">
                <div class="gm-template-img" style="background:${t.bg}">${t.icon}</div>
                <div class="gm-template-body">
                    <div class="gm-template-title">${esc(t.name)}</div>
                    <div class="gm-template-desc">${esc(t.desc)}</div>
                    <div class="gm-template-tags">
                        ${t.tags.map(tag => `<span class="gm-tag ${tag}">${tag.toUpperCase()}</span>`).join('')}
                        <span class="gm-tag">${t.cat}</span>
                    </div>
                </div>
            </div>
        `).join('');
    }

    window.filterCat = function(cat) {
        currentCat = cat;
        document.querySelectorAll('.gm-cat').forEach(c => c.classList.toggle('active', c.dataset.cat === cat));
        renderTemplates();
    };

    window.openCreate = function(templateId, name, desc, type) {
        <?php if (!$isLoggedIn): ?>
        window.location.href = '/login.php?redirect=/games-maker.php';
        return;
        <?php endif; ?>
        document.getElementById('createName').value = name || '';
        document.getElementById('createDesc').value = desc || '';
        document.getElementById('createType').value = type || 'game';
        document.getElementById('createTemplate').value = templateId || 'blank';
        document.getElementById('createModal').classList.add('show');
    };

    window.closeCreate = function() {
        document.getElementById('createModal').classList.remove('show');
    };

    window.createProject = async function() {
        const name = document.getElementById('createName').value.trim();
        const desc = document.getElementById('createDesc').value.trim();
        const type = document.getElementById('createType').value;
        const template = document.getElementById('createTemplate').value;

        if (!name) { alert('Please enter a project name'); return; }

        // Create a Alfred IDE workspace for this project
        const slug = name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '').substring(0, 50);

        try {
            const resp = await fetch('/api/gocodeme.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': window.AW_CSRF_TOKEN || '',
                },
                body: JSON.stringify({
                    action: 'create_project',
                    name: name,
                    slug: slug,
                    description: desc,
                    language: type === 'vr-game' ? 'webxr' : (type === 'game' ? 'javascript' : 'html'),
                    template: template,
                }),
            });

            if (resp.ok) {
                const data = await resp.json();
                closeCreate();
                if (data.workspace_url) {
                    window.location.href = data.workspace_url;
                } else {
                    alert('Project created! Check your Alfred IDE dashboard.');
                    window.location.href = '/alfred-ide.php';
                }
            } else {
                const err = await resp.json().catch(() => ({}));
                alert(err.error || 'Failed to create project. Please try from Alfred IDE directly.');
                window.location.href = '/alfred-ide.php';
            }
        } catch (err) {
            alert('Failed to connect. Opening Alfred IDE...');
            window.location.href = '/alfred-ide.php';
        }
    };

    document.getElementById('createModal').addEventListener('click', function(e) {
        if (e.target === this) closeCreate();
    });

    function esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

    renderTemplates();
})();
</script>

<?php include 'includes/site-footer.inc.php'; ?>
