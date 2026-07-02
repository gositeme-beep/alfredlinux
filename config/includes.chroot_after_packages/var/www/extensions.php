<?php
$page_title       = 'Alfred Everywhere — Extensions & Integrations | GoSiteMe';
$page_description = 'Access Alfred AI from Chrome, VS Code, your terminal, or any platform. 13,000+ AI tools everywhere you work.';
$page_canonical   = 'https://root.com/extensions.php';
$page_og_title    = 'Alfred Everywhere — Chrome, VS Code, CLI & More';
$page_og_description = 'Access Alfred AI from Chrome, VS Code, your terminal, or any platform. 13,000+ AI tools everywhere you work.';
$page_twitter_description = 'Alfred Everywhere: Chrome extension, VS Code, CLI, Slack, Discord. 13,000+ AI tools wherever you work.';
require_once __DIR__ . '/includes/site-header.inc.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$is_logged_in = !empty($_SESSION['logged_in']) && !empty($_SESSION['client_id']);
$client_id    = $is_logged_in ? (int)$_SESSION['client_id'] : 0;
$client_name  = $_SESSION['client_name'] ?? '';
$client_email = $_SESSION['client_email'] ?? '';
?>

<style>
/* ===== EXTENSIONS PAGE — SCOPED STYLES ===== */

.ext-page {
    --ext-bg: #0a0a14;
    --ext-surface: #12121e;
    --ext-surface-2: #1a1a2e;
    --ext-border: rgba(255,255,255,.06);
    --ext-text: #e2e8f0;
    --ext-text-muted: #94a3b8;
    --ext-accent: #6c5ce7;
    --ext-accent-light: #a29bfe;
    --ext-blue: #0984e3;
    --ext-green: #00b894;
    --ext-orange: #e17055;
    --ext-radius: 16px;
    --ext-radius-sm: 12px;
    --ext-transition: .3s cubic-bezier(.4,0,.2,1);
    --ext-shadow: 0 8px 32px rgba(0,0,0,.35);
    --ext-gradient: linear-gradient(135deg, #6c5ce7, #0984e3);
    --ext-gradient-soft: linear-gradient(135deg, rgba(108,92,231,.12), rgba(9,132,227,.12));
}

.ext-page { background: var(--ext-bg); color: var(--ext-text); overflow-x: hidden; }
.ext-page *, .ext-page *::before, .ext-page *::after { box-sizing: border-box; }

/* Layout */
.ext-container { max-width: 1200px; margin: 0 auto; padding: 0 24px; }
.ext-section { padding: 80px 0; }
.ext-section--alt { background: var(--ext-surface); }

/* Typography */
.ext-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 16px; border-radius: 50px; font-size: .78rem; font-weight: 600;
    letter-spacing: .5px; text-transform: uppercase;
    background: var(--ext-gradient-soft); color: var(--ext-accent-light);
    border: 1px solid rgba(108,92,231,.2);
}
.ext-section-title {
    font-family: 'Space Grotesk', sans-serif; font-size: clamp(1.8rem, 4vw, 2.8rem);
    font-weight: 800; line-height: 1.15; margin: 12px 0;
    background: var(--ext-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
}
.ext-section-sub {
    font-size: 1.1rem; color: var(--ext-text-muted); max-width: 640px; margin: 0 auto 48px; line-height: 1.6;
}
.ext-header { text-align: center; margin-bottom: 48px; }

/* Buttons */
.ext-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 14px 32px; border: none; border-radius: 50px; cursor: pointer;
    font-size: 1rem; font-weight: 700; text-decoration: none;
    background: var(--ext-gradient); color: #fff;
    box-shadow: 0 4px 24px rgba(108,92,231,.35);
    transition: transform var(--ext-transition), box-shadow var(--ext-transition);
    font-family: inherit;
}
.ext-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 32px rgba(108,92,231,.5); color: #fff; text-decoration: none; }
.ext-btn--ghost {
    background: transparent; border: 2px solid rgba(255,255,255,.15);
    color: var(--ext-text); box-shadow: none;
}
.ext-btn--ghost:hover { border-color: var(--ext-accent); color: var(--ext-accent-light); background: rgba(108,92,231,.08); }
.ext-btn--sm { padding: 10px 22px; font-size: .9rem; }
.ext-btn--disabled {
    background: rgba(255,255,255,.06); color: var(--ext-text-muted);
    cursor: default; box-shadow: none; pointer-events: none;
}

/* ===== HERO ===== */
.ext-hero {
    padding: 140px 0 80px; text-align: center; position: relative;
    background: radial-gradient(ellipse at 50% 0%, rgba(108,92,231,.12) 0%, transparent 60%);
}
.ext-hero-title {
    font-family: 'Space Grotesk', sans-serif; font-size: clamp(2.4rem, 5vw, 3.6rem);
    font-weight: 800; line-height: 1.1; margin: 16px 0;
    background: var(--ext-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
}
.ext-hero-sub {
    font-size: 1.2rem; color: var(--ext-text-muted); max-width: 600px; margin: 0 auto 36px; line-height: 1.6;
}
.ext-hero-platforms {
    display: flex; justify-content: center; gap: 32px; margin-top: 48px; flex-wrap: wrap;
}
.ext-platform-icon {
    display: flex; flex-direction: column; align-items: center; gap: 8px;
    color: var(--ext-text-muted); font-size: .82rem; font-weight: 600;
    transition: color var(--ext-transition);
}
.ext-platform-icon:hover { color: var(--ext-accent-light); }
.ext-platform-icon i { font-size: 2rem; }

/* ===== EXTENSION CARDS ===== */
.ext-cards-grid {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
    gap: 24px;
}
.ext-card {
    background: var(--ext-surface); border: 1px solid var(--ext-border);
    border-radius: var(--ext-radius); padding: 32px; position: relative;
    transition: transform var(--ext-transition), border-color var(--ext-transition), box-shadow var(--ext-transition);
}
.ext-card:hover {
    transform: translateY(-4px); border-color: rgba(108,92,231,.25);
    box-shadow: var(--ext-shadow), 0 0 60px rgba(108,92,231,.08);
}
.ext-card-header { display: flex; align-items: center; gap: 16px; margin-bottom: 16px; }
.ext-card-icon {
    width: 56px; height: 56px; border-radius: 14px; display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem; color: #fff; flex-shrink: 0;
}
.ext-card-icon--chrome { background: linear-gradient(135deg, #4285f4, #34a853); }
.ext-card-icon--vscode { background: linear-gradient(135deg, #007acc, #0098ff); }
.ext-card-icon--cli    { background: linear-gradient(135deg, #00b894, #00cec9); }
.ext-card-icon--slack  { background: linear-gradient(135deg, #e01e5a, #ecb22e); }
.ext-card-icon--discord { background: linear-gradient(135deg, #5865f2, #7289da); }

.ext-card-name { font-size: 1.2rem; font-weight: 700; }
.ext-card-subtitle { font-size: .82rem; color: var(--ext-text-muted); }
.ext-card-desc { color: var(--ext-text-muted); font-size: .92rem; line-height: 1.6; margin-bottom: 20px; }

.ext-card-features {
    list-style: none; padding: 0; margin: 0 0 24px;
    display: grid; grid-template-columns: 1fr 1fr; gap: 8px;
}
.ext-card-features li {
    display: flex; align-items: center; gap: 8px; font-size: .84rem; color: var(--ext-text-muted);
}
.ext-card-features li::before {
    content: '✓'; color: var(--ext-green); font-weight: 700; font-size: .75rem;
}

.ext-card-actions { display: flex; gap: 10px; flex-wrap: wrap; }

/* Coming Soon badge */
.ext-coming-soon {
    position: absolute; top: 16px; right: 16px;
    padding: 4px 12px; border-radius: 20px; font-size: .72rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .5px;
    background: rgba(225,112,85,.15); color: var(--ext-orange); border: 1px solid rgba(225,112,85,.2);
}

/* CLI install command */
.ext-cli-cmd {
    display: flex; align-items: center; gap: 10px;
    background: #0a0a14; border: 1px solid var(--ext-border);
    border-radius: 10px; padding: 12px 16px; font-family: 'Fira Code', monospace; font-size: .88rem;
    color: var(--ext-green); margin-bottom: 16px;
}
.ext-cli-cmd code { flex: 1; }
.ext-cli-cmd .copy-btn {
    background: none; border: none; color: var(--ext-text-muted); cursor: pointer;
    font-size: .9rem; padding: 4px; transition: color .2s;
}
.ext-cli-cmd .copy-btn:hover { color: var(--ext-accent-light); }

/* ===== COMPARISON TABLE ===== */
.ext-table-wrap {
    overflow-x: auto; border-radius: var(--ext-radius);
    border: 1px solid var(--ext-border);
}
.ext-table {
    width: 100%; border-collapse: collapse; font-size: .9rem;
}
.ext-table th, .ext-table td {
    padding: 14px 20px; text-align: center; border-bottom: 1px solid var(--ext-border);
}
.ext-table th {
    background: var(--ext-surface-2); color: var(--ext-text); font-weight: 700;
    position: sticky; top: 0;
}
.ext-table th:first-child, .ext-table td:first-child {
    text-align: left; font-weight: 600; color: var(--ext-text);
}
.ext-table td { color: var(--ext-text-muted); }
.ext-table tr:hover td { background: rgba(108,92,231,.04); }
.ext-table .check { color: var(--ext-green); font-weight: 700; }
.ext-table .dash { color: rgba(255,255,255,.15); }
.ext-table .soon { color: var(--ext-orange); font-size: .78rem; font-weight: 600; }

/* ===== INSTALL TABS ===== */
.ext-tabs { display: flex; gap: 4px; margin-bottom: 24px; flex-wrap: wrap; }
.ext-tab {
    padding: 10px 24px; border-radius: 10px; border: 1px solid var(--ext-border);
    background: transparent; color: var(--ext-text-muted); cursor: pointer;
    font-weight: 600; font-size: .9rem; transition: all var(--ext-transition);
    font-family: inherit;
}
.ext-tab:hover { border-color: rgba(108,92,231,.3); color: var(--ext-text); }
.ext-tab.active {
    background: var(--ext-gradient-soft); border-color: rgba(108,92,231,.3);
    color: var(--ext-accent-light);
}

.ext-tab-panel { display: none; }
.ext-tab-panel.active { display: block; }

.ext-steps {
    background: var(--ext-surface); border: 1px solid var(--ext-border);
    border-radius: var(--ext-radius); padding: 32px; counter-reset: step;
}
.ext-step {
    display: flex; gap: 20px; padding: 16px 0;
    border-bottom: 1px solid var(--ext-border);
}
.ext-step:last-child { border-bottom: none; }
.ext-step-num {
    counter-increment: step;
    width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
    background: var(--ext-gradient-soft); border: 1px solid rgba(108,92,231,.2);
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: .9rem; color: var(--ext-accent-light);
}
.ext-step-num::after { content: counter(step); }
.ext-step-content h4 { font-size: 1rem; margin-bottom: 6px; }
.ext-step-content p { font-size: .88rem; color: var(--ext-text-muted); line-height: 1.6; }
.ext-step-content code {
    background: #0a0a14; padding: 2px 8px; border-radius: 6px;
    font-family: 'Fira Code', monospace; font-size: .84rem; color: var(--ext-green);
}

/* ===== CTA ===== */
.ext-cta {
    text-align: center; padding: 100px 0;
    background: radial-gradient(ellipse at 50% 100%, rgba(108,92,231,.1) 0%, transparent 60%);
}
.ext-cta-title {
    font-family: 'Space Grotesk', sans-serif; font-size: clamp(1.6rem, 3vw, 2.4rem);
    font-weight: 800; margin-bottom: 16px;
    background: var(--ext-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
}
.ext-cta-sub { color: var(--ext-text-muted); font-size: 1.05rem; margin-bottom: 32px; }
.ext-cta-actions { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
    .ext-hero { padding: 80px 0 50px; }
    .ext-section { padding: 50px 0; }
    .ext-cards-grid { grid-template-columns: 1fr; }
    .ext-card-features { grid-template-columns: 1fr; }
    .ext-hero-platforms { gap: 20px; }
}
</style>

<div class="ext-page">

<!-- ===== HERO ===== -->
<section class="ext-hero">
    <div class="ext-container">
        <div class="ext-badge" data-aos="fade-down"><i class="fas fa-puzzle-piece"></i> Extensions & Integrations</div>
        <h1 class="ext-hero-title" data-aos="fade-up">Alfred Everywhere</h1>
        <p class="ext-hero-sub" data-aos="fade-up" data-aos-delay="100">
            Use Alfred AI from Chrome, VS Code, your terminal, or any platform. 13,000+ tools wherever you work.
        </p>
        <div data-aos="fade-up" data-aos-delay="200">
            <a href="#extensions" class="ext-btn"><i class="fas fa-download"></i> Browse Extensions</a>
            <a href="/developer-portal.php" class="ext-btn ext-btn--ghost" style="margin-left:12px;"><i class="fas fa-code"></i> API Docs</a>
        </div>

        <div class="ext-hero-platforms" data-aos="fade-up" data-aos-delay="300">
            <div class="ext-platform-icon"><i class="fab fa-chrome"></i> Chrome</div>
            <div class="ext-platform-icon"><i class="fas fa-code"></i> VS Code</div>
            <div class="ext-platform-icon"><i class="fas fa-terminal"></i> Terminal</div>
            <div class="ext-platform-icon"><i class="fab fa-slack"></i> Slack</div>
            <div class="ext-platform-icon"><i class="fab fa-discord"></i> Discord</div>
        </div>
    </div>
</section>

<!-- ===== EXTENSION CARDS ===== -->
<section class="ext-section" id="extensions">
    <div class="ext-container">
        <div class="ext-header">
            <div class="ext-badge" data-aos="fade-down"><i class="fas fa-boxes-stacked"></i> Available Extensions</div>
            <h2 class="ext-section-title" data-aos="fade-up">Choose Your Platform</h2>
            <p class="ext-section-sub" data-aos="fade-up" data-aos-delay="100">
                Install Alfred on the platforms you use every day. One API key works across all extensions.
            </p>
        </div>

        <div class="ext-cards-grid">

            <!-- Chrome Extension -->
            <div class="ext-card" data-aos="fade-up">
                <div class="ext-card-header">
                    <div class="ext-card-icon ext-card-icon--chrome"><i class="fab fa-chrome"></i></div>
                    <div>
                        <div class="ext-card-name">Chrome Extension</div>
                        <div class="ext-card-subtitle">Browser Integration</div>
                    </div>
                </div>
                <p class="ext-card-desc">
                    Access Alfred directly from any webpage. Right-click to analyze pages, chat in the side panel,
                    get SEO checks, and summarize articles — all without leaving your browser.
                </p>
                <ul class="ext-card-features">
                    <li>Side panel chat</li>
                    <li>Page analysis</li>
                    <li>Right-click actions</li>
                    <li>SEO checking</li>
                    <li>Content summarization</li>
                    <li>Voice input</li>
                    <li>Floating AI button</li>
                    <li>13,000+ tools access</li>
                </ul>
                <div class="ext-card-actions">
                    <a href="#" class="ext-btn ext-btn--sm"><i class="fab fa-chrome"></i> Install from Chrome Web Store</a>
                    <a href="/extensions/chrome/" class="ext-btn ext-btn--sm ext-btn--ghost"><i class="fas fa-code"></i> Source</a>
                </div>
            </div>

            <!-- VS Code Extension -->
            <div class="ext-card" data-aos="fade-up" data-aos-delay="100">
                <div class="ext-card-header">
                    <div class="ext-card-icon ext-card-icon--vscode"><i class="fas fa-code"></i></div>
                    <div>
                        <div class="ext-card-name">VS Code Extension</div>
                        <div class="ext-card-subtitle">Editor Integration</div>
                    </div>
                </div>
                <p class="ext-card-desc">
                    Bring Alfred into your code editor. Get AI-powered code completions, refactoring suggestions,
                    documentation generation, and inline chat — all trained on 13,000+ tools.
                </p>
                <ul class="ext-card-features">
                    <li>Inline AI chat</li>
                    <li>Code completions</li>
                    <li>Refactoring help</li>
                    <li>Doc generation</li>
                    <li>Error explanations</li>
                    <li>Test generation</li>
                    <li>Multi-language support</li>
                    <li>Git integration</li>
                </ul>
                <div class="ext-card-actions">
                    <a href="#" class="ext-btn ext-btn--sm"><i class="fas fa-download"></i> Install from Marketplace</a>
                    <a href="/developer-portal.php" class="ext-btn ext-btn--sm ext-btn--ghost"><i class="fas fa-book"></i> Docs</a>
                </div>
            </div>

            <!-- CLI Tool -->
            <div class="ext-card" data-aos="fade-up" data-aos-delay="200">
                <div class="ext-card-header">
                    <div class="ext-card-icon ext-card-icon--cli"><i class="fas fa-terminal"></i></div>
                    <div>
                        <div class="ext-card-name">CLI Tool</div>
                        <div class="ext-card-subtitle">Terminal Integration</div>
                    </div>
                </div>
                <p class="ext-card-desc">
                    Power-user access from your terminal. Chat, execute tools, manage agents, run fleet operations,
                    and automate workflows — all with a single command.
                </p>
                <div class="ext-cli-cmd">
                    <code>npm install -g alfred-cli</code>
                    <button class="copy-btn" onclick="navigator.clipboard.writeText('npm install -g alfred-cli').then(()=>{this.innerHTML='✓';setTimeout(()=>this.innerHTML='📋',1500)})">📋</button>
                </div>
                <ul class="ext-card-features">
                    <li>Interactive REPL</li>
                    <li>Tool execution</li>
                    <li>Agent management</li>
                    <li>Fleet operations</li>
                    <li>JSON output mode</li>
                    <li>Scriptable</li>
                    <li>CI/CD friendly</li>
                    <li>Cross-platform</li>
                </ul>
                <div class="ext-card-actions">
                    <a href="/extensions/cli/" class="ext-btn ext-btn--sm ext-btn--ghost"><i class="fas fa-book"></i> Documentation</a>
                </div>
            </div>

            <!-- Slack Integration -->
            <div class="ext-card" data-aos="fade-up" data-aos-delay="300">
                <span class="ext-coming-soon">Coming Soon</span>
                <div class="ext-card-header">
                    <div class="ext-card-icon ext-card-icon--slack"><i class="fab fa-slack"></i></div>
                    <div>
                        <div class="ext-card-name">Slack Integration</div>
                        <div class="ext-card-subtitle">Team Collaboration</div>
                    </div>
                </div>
                <p class="ext-card-desc">
                    Bring Alfred to your Slack workspace. Mention @alfred in any channel to get AI assistance,
                    run tools, and share results with your team.
                </p>
                <ul class="ext-card-features">
                    <li>Channel mentions</li>
                    <li>DM support</li>
                    <li>Slash commands</li>
                    <li>Thread replies</li>
                    <li>File analysis</li>
                    <li>Team sharing</li>
                </ul>
                <div class="ext-card-actions">
                    <span class="ext-btn ext-btn--sm ext-btn--disabled"><i class="fab fa-slack"></i> Coming Soon</span>
                </div>
            </div>

            <!-- Discord Bot -->
            <div class="ext-card" data-aos="fade-up" data-aos-delay="400">
                <span class="ext-coming-soon">Coming Soon</span>
                <div class="ext-card-header">
                    <div class="ext-card-icon ext-card-icon--discord"><i class="fab fa-discord"></i></div>
                    <div>
                        <div class="ext-card-name">Discord Bot</div>
                        <div class="ext-card-subtitle">Community Integration</div>
                    </div>
                </div>
                <p class="ext-card-desc">
                    Add Alfred to your Discord server. Use slash commands, get AI assistance in threads,
                    and empower your community with 13,000+ tools.
                </p>
                <ul class="ext-card-features">
                    <li>Slash commands</li>
                    <li>Thread support</li>
                    <li>Embed responses</li>
                    <li>Role permissions</li>
                    <li>Server analytics</li>
                    <li>Custom triggers</li>
                </ul>
                <div class="ext-card-actions">
                    <span class="ext-btn ext-btn--sm ext-btn--disabled"><i class="fab fa-discord"></i> Coming Soon</span>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ===== FEATURE COMPARISON ===== -->
<section class="ext-section ext-section--alt">
    <div class="ext-container">
        <div class="ext-header">
            <div class="ext-badge" data-aos="fade-down"><i class="fas fa-table"></i> Comparison</div>
            <h2 class="ext-section-title" data-aos="fade-up">Feature Comparison</h2>
            <p class="ext-section-sub" data-aos="fade-up" data-aos-delay="100">
                See which features are available on each platform.
            </p>
        </div>

        <div class="ext-table-wrap" data-aos="fade-up" data-aos-delay="200">
            <table class="ext-table">
                <thead>
                    <tr>
                        <th>Feature</th>
                        <th><i class="fab fa-chrome"></i> Chrome</th>
                        <th><i class="fas fa-code"></i> VS Code</th>
                        <th><i class="fas fa-terminal"></i> CLI</th>
                        <th><i class="fab fa-slack"></i> Slack</th>
                        <th><i class="fab fa-discord"></i> Discord</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>AI Chat</td>
                        <td class="check">✓</td>
                        <td class="check">✓</td>
                        <td class="check">✓</td>
                        <td class="soon">Soon</td>
                        <td class="soon">Soon</td>
                    </tr>
                    <tr>
                        <td>13,000+ Tools Access</td>
                        <td class="check">✓</td>
                        <td class="check">✓</td>
                        <td class="check">✓</td>
                        <td class="soon">Soon</td>
                        <td class="soon">Soon</td>
                    </tr>
                    <tr>
                        <td>Page / File Analysis</td>
                        <td class="check">✓</td>
                        <td class="check">✓</td>
                        <td class="dash">—</td>
                        <td class="soon">Soon</td>
                        <td class="dash">—</td>
                    </tr>
                    <tr>
                        <td>Code Completions</td>
                        <td class="dash">—</td>
                        <td class="check">✓</td>
                        <td class="dash">—</td>
                        <td class="dash">—</td>
                        <td class="dash">—</td>
                    </tr>
                    <tr>
                        <td>Voice Input</td>
                        <td class="check">✓</td>
                        <td class="dash">—</td>
                        <td class="soon">Soon</td>
                        <td class="dash">—</td>
                        <td class="dash">—</td>
                    </tr>
                    <tr>
                        <td>Interactive REPL</td>
                        <td class="dash">—</td>
                        <td class="dash">—</td>
                        <td class="check">✓</td>
                        <td class="dash">—</td>
                        <td class="dash">—</td>
                    </tr>
                    <tr>
                        <td>Fleet Management</td>
                        <td class="dash">—</td>
                        <td class="dash">—</td>
                        <td class="check">✓</td>
                        <td class="soon">Soon</td>
                        <td class="dash">—</td>
                    </tr>
                    <tr>
                        <td>Agent Management</td>
                        <td class="dash">—</td>
                        <td class="check">✓</td>
                        <td class="check">✓</td>
                        <td class="soon">Soon</td>
                        <td class="dash">—</td>
                    </tr>
                    <tr>
                        <td>SEO Analysis</td>
                        <td class="check">✓</td>
                        <td class="dash">—</td>
                        <td class="check">✓</td>
                        <td class="dash">—</td>
                        <td class="dash">—</td>
                    </tr>
                    <tr>
                        <td>CI/CD Integration</td>
                        <td class="dash">—</td>
                        <td class="dash">—</td>
                        <td class="check">✓</td>
                        <td class="dash">—</td>
                        <td class="dash">—</td>
                    </tr>
                    <tr>
                        <td>Team Collaboration</td>
                        <td class="dash">—</td>
                        <td class="dash">—</td>
                        <td class="dash">—</td>
                        <td class="soon">Soon</td>
                        <td class="soon">Soon</td>
                    </tr>
                    <tr>
                        <td>Context Menu</td>
                        <td class="check">✓</td>
                        <td class="check">✓</td>
                        <td class="dash">—</td>
                        <td class="dash">—</td>
                        <td class="dash">—</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- ===== INSTALLATION GUIDES ===== -->
<section class="ext-section" id="install">
    <div class="ext-container">
        <div class="ext-header">
            <div class="ext-badge" data-aos="fade-down"><i class="fas fa-rocket"></i> Get Started</div>
            <h2 class="ext-section-title" data-aos="fade-up">Installation Guide</h2>
            <p class="ext-section-sub" data-aos="fade-up" data-aos-delay="100">
                Get up and running in under a minute on any platform.
            </p>
        </div>

        <div data-aos="fade-up" data-aos-delay="200">
            <div class="ext-tabs">
                <button class="ext-tab active" data-tab="chrome"><i class="fab fa-chrome"></i> Chrome</button>
                <button class="ext-tab" data-tab="vscode"><i class="fas fa-code"></i> VS Code</button>
                <button class="ext-tab" data-tab="cli"><i class="fas fa-terminal"></i> CLI</button>
            </div>

            <!-- Chrome Tab -->
            <div class="ext-tab-panel active" id="tab-chrome">
                <div class="ext-steps">
                    <div class="ext-step">
                        <div class="ext-step-num"></div>
                        <div class="ext-step-content">
                            <h4>Install from Chrome Web Store</h4>
                            <p>Visit the Chrome Web Store and click "Add to Chrome" to install the Alfred AI extension.</p>
                        </div>
                    </div>
                    <div class="ext-step">
                        <div class="ext-step-num"></div>
                        <div class="ext-step-content">
                            <h4>Get Your API Key</h4>
                            <p>Go to the <a href="/developer-portal.php" style="color:var(--ext-accent-light);">Developer Portal</a> and generate an API key from your dashboard.</p>
                        </div>
                    </div>
                    <div class="ext-step">
                        <div class="ext-step-num"></div>
                        <div class="ext-step-content">
                            <h4>Connect the Extension</h4>
                            <p>Click the Alfred icon in your toolbar, paste your API key, and click "Save & Connect".</p>
                        </div>
                    </div>
                    <div class="ext-step">
                        <div class="ext-step-num"></div>
                        <div class="ext-step-content">
                            <h4>Start Using Alfred</h4>
                            <p>Right-click any page, use the popup, or open the side panel to chat with Alfred. Look for the floating <strong>A</strong> button on every page!</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VS Code Tab -->
            <div class="ext-tab-panel" id="tab-vscode">
                <div class="ext-steps">
                    <div class="ext-step">
                        <div class="ext-step-num"></div>
                        <div class="ext-step-content">
                            <h4>Open VS Code Extensions</h4>
                            <p>In VS Code, press <code>Ctrl+Shift+X</code> (or <code>Cmd+Shift+X</code> on Mac) to open the Extensions panel.</p>
                        </div>
                    </div>
                    <div class="ext-step">
                        <div class="ext-step-num"></div>
                        <div class="ext-step-content">
                            <h4>Search for Alfred AI</h4>
                            <p>Type "Alfred AI" in the search bar and click <strong>Install</strong> on the official extension by GoSiteMe.</p>
                        </div>
                    </div>
                    <div class="ext-step">
                        <div class="ext-step-num"></div>
                        <div class="ext-step-content">
                            <h4>Configure Your API Key</h4>
                            <p>Open Settings (<code>Ctrl+,</code>), search for "Alfred", and paste your API key in the <code>alfred.apiKey</code> field.</p>
                        </div>
                    </div>
                    <div class="ext-step">
                        <div class="ext-step-num"></div>
                        <div class="ext-step-content">
                            <h4>Use Alfred in Your Editor</h4>
                            <p>Press <code>Ctrl+Shift+A</code> to open Alfred chat, or right-click code for AI actions like refactor, explain, and generate tests.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CLI Tab -->
            <div class="ext-tab-panel" id="tab-cli">
                <div class="ext-steps">
                    <div class="ext-step">
                        <div class="ext-step-num"></div>
                        <div class="ext-step-content">
                            <h4>Install via npm</h4>
                            <p>Run <code>npm install -g alfred-cli</code> in your terminal. Requires Node.js 18+.</p>
                        </div>
                    </div>
                    <div class="ext-step">
                        <div class="ext-step-num"></div>
                        <div class="ext-step-content">
                            <h4>Authenticate</h4>
                            <p>Run <code>alfred login</code> and paste your API key when prompted.</p>
                        </div>
                    </div>
                    <div class="ext-step">
                        <div class="ext-step-num"></div>
                        <div class="ext-step-content">
                            <h4>Start Chatting</h4>
                            <p>Run <code>alfred chat "Hello!"</code> to send your first message, or <code>alfred interactive</code> for REPL mode.</p>
                        </div>
                    </div>
                    <div class="ext-step">
                        <div class="ext-step-num"></div>
                        <div class="ext-step-content">
                            <h4>Explore Tools</h4>
                            <p>Run <code>alfred tools --search "seo"</code> to search tools, or <code>alfred exec &lt;tool&gt;</code> to execute one directly.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== CTA ===== -->
<section class="ext-cta">
    <div class="ext-container">
        <h2 class="ext-cta-title" data-aos="fade-up">Ready to Use Alfred Everywhere?</h2>
        <p class="ext-cta-sub" data-aos="fade-up" data-aos-delay="100">
            Get your API key and start using 13,000+ AI tools on every platform you work with.
        </p>
        <div class="ext-cta-actions" data-aos="fade-up" data-aos-delay="200">
            <a href="/developer-portal.php" class="ext-btn"><i class="fas fa-key"></i> Get Your API Key</a>
            <a href="/alfred.php" class="ext-btn ext-btn--ghost"><i class="fas fa-robot"></i> Learn About Alfred</a>
        </div>
    </div>
</section>

</div><!-- /.ext-page -->

<script>
/* Installation Guide Tabs */
document.querySelectorAll('.ext-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.ext-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.ext-tab-panel').forEach(p => p.classList.remove('active'));
        tab.classList.add('active');
        document.getElementById('tab-' + tab.dataset.tab).classList.add('active');
    });
});
</script>

<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>
