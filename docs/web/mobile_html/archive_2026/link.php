<?php
$page_title = 'GoSiteMe — AI Platform, Social Network & More';
$page_description = 'The all-in-one AI ecosystem. Meet Alfred, our AI who runs the company. AI hosting, social network, encrypted chat, VR worlds, voice agents — from $15/mo.';
$page_canonical = 'https://gositeme.com/link';
$page_og_title = 'GoSiteMe — One Platform. Everything You Need.';
$page_og_description = $page_description;
$page_og_image = 'https://gositeme.com/assets/images/alfred-portrait.png';
$page_og_image_alt = 'Alfred - AI running GoSiteMe';
$noGlobalMain = true;
$pageCss = '';
include __DIR__ . '/includes/site-header.inc.php';
?>

<style>
/* ═══════════════════════════════════════════════
   Link-in-Bio — GoSiteMe
   ═══════════════════════════════════════════════ */
.lib{--lib-purple:#7D00FF;--lib-cyan:#00D4FF;--lib-green:#10b981;--lib-pink:#ec4899;--lib-amber:#f59e0b;--lib-bg:#0a0a14;--lib-card:rgba(255,255,255,.04);--lib-border:rgba(255,255,255,.08);--lib-text:#e0e0e0;--lib-muted:#a8b2d1;min-height:100vh;display:flex;flex-direction:column;align-items:center;padding:2rem 1rem 4rem;position:relative;overflow:hidden}
.lib::before{content:'';position:absolute;top:-120px;left:50%;transform:translateX(-50%);width:500px;height:500px;background:radial-gradient(circle,rgba(125,0,255,.15) 0%,transparent 70%);pointer-events:none;z-index:0}
.lib *{position:relative;z-index:1}

/* Profile */
.lib-profile{text-align:center;margin-bottom:2rem}
.lib-avatar{width:120px;height:120px;border-radius:50%;border:3px solid var(--lib-purple);padding:3px;background:linear-gradient(135deg,var(--lib-purple),var(--lib-cyan));display:inline-block;margin-bottom:1rem;animation:lib-glow 3s ease-in-out infinite alternate}
.lib-avatar img{width:100%;height:100%;border-radius:50%;object-fit:cover;background:#0a0a14}
@keyframes lib-glow{0%{box-shadow:0 0 20px rgba(125,0,255,.3)}100%{box-shadow:0 0 40px rgba(0,212,255,.3)}}
.lib-name{font-family:'Space Grotesk',sans-serif;font-size:1.6rem;font-weight:700;color:#fff;margin:0 0 .25rem}
.lib-name .lib-ai-badge{font-size:.7rem;background:linear-gradient(135deg,var(--lib-purple),var(--lib-cyan));padding:2px 8px;border-radius:20px;vertical-align:middle;margin-left:.5rem;letter-spacing:.5px}
.lib-tagline{color:var(--lib-muted);font-size:.95rem;margin:0 0 .75rem;max-width:340px;display:inline-block}
.lib-live{display:inline-flex;align-items:center;gap:6px;font-size:.75rem;color:var(--lib-green);font-weight:600;text-transform:uppercase;letter-spacing:1px}
.lib-live .dot{width:8px;height:8px;background:var(--lib-green);border-radius:50%;animation:lib-pulse 1.5s ease-in-out infinite}
@keyframes lib-pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.5;transform:scale(.8)}}

/* Stats */
.lib-stats{display:flex;gap:1.5rem;justify-content:center;margin-bottom:2rem;flex-wrap:wrap}
.lib-stat{text-align:center}
.lib-stat-num{font-family:'Space Grotesk',sans-serif;font-size:1.4rem;font-weight:700;background:linear-gradient(135deg,var(--lib-purple),var(--lib-cyan));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.lib-stat-label{font-size:.65rem;color:var(--lib-muted);text-transform:uppercase;letter-spacing:.5px;margin-top:2px}

/* Call CTA */
.lib-call{width:100%;max-width:400px;margin-bottom:1.5rem}
.lib-call a{display:flex;align-items:center;justify-content:center;gap:.75rem;padding:1rem 1.5rem;border-radius:16px;background:linear-gradient(135deg,var(--lib-purple),var(--lib-cyan));color:#fff;font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:1.05rem;text-decoration:none;transition:all .3s;box-shadow:0 4px 24px rgba(125,0,255,.3)}
.lib-call a:hover{transform:translateY(-2px);box-shadow:0 8px 32px rgba(125,0,255,.5)}
.lib-call a i{font-size:1.2rem}
.lib-call-sub{text-align:center;font-size:.7rem;color:var(--lib-muted);margin-top:.5rem}

/* Links grid */
.lib-links{width:100%;max-width:400px;display:flex;flex-direction:column;gap:.75rem;margin-bottom:2rem}
.lib-link{display:flex;align-items:center;gap:1rem;padding:1rem 1.25rem;border-radius:14px;background:var(--lib-card);border:1px solid var(--lib-border);text-decoration:none;color:var(--lib-text);transition:all .3s;backdrop-filter:blur(8px)}
.lib-link:hover{border-color:var(--lib-purple);background:rgba(125,0,255,.06);transform:translateY(-2px);box-shadow:0 4px 20px rgba(125,0,255,.15)}
.lib-link-icon{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0}
.lib-link-icon.purple{background:rgba(125,0,255,.15);color:var(--lib-purple)}
.lib-link-icon.cyan{background:rgba(0,212,255,.15);color:var(--lib-cyan)}
.lib-link-icon.green{background:rgba(16,185,129,.15);color:var(--lib-green)}
.lib-link-icon.pink{background:rgba(236,72,153,.15);color:var(--lib-pink)}
.lib-link-icon.amber{background:rgba(245,158,11,.15);color:var(--lib-amber)}
.lib-link-icon.blue{background:rgba(59,130,246,.15);color:#3b82f6}
.lib-link-text h4{font-family:'Space Grotesk',sans-serif;font-size:.95rem;font-weight:600;margin:0 0 2px;color:#fff}
.lib-link-text p{font-size:.75rem;color:var(--lib-muted);margin:0}
.lib-link-arrow{margin-left:auto;color:var(--lib-muted);font-size:.8rem;transition:transform .3s}
.lib-link:hover .lib-link-arrow{transform:translateX(3px);color:var(--lib-purple)}

/* Team mini */
.lib-team{width:100%;max-width:400px;margin-bottom:2rem}
.lib-team-title{font-size:.7rem;text-transform:uppercase;letter-spacing:1.5px;color:var(--lib-muted);text-align:center;margin-bottom:1rem;font-weight:600}
.lib-team-row{display:flex;justify-content:center;gap:.5rem;flex-wrap:wrap}
.lib-agent{display:flex;flex-direction:column;align-items:center;gap:.35rem;width:60px;text-decoration:none}
.lib-agent-dot{width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1rem;color:#fff;transition:transform .3s}
.lib-agent:hover .lib-agent-dot{transform:scale(1.15)}
.lib-agent-name{font-size:.55rem;color:var(--lib-muted);text-align:center;line-height:1.2}

/* Socials */
.lib-socials{display:flex;gap:1rem;margin-bottom:2rem}
.lib-social{width:44px;height:44px;border-radius:50%;background:var(--lib-card);border:1px solid var(--lib-border);display:flex;align-items:center;justify-content:center;color:var(--lib-text);font-size:1.1rem;text-decoration:none;transition:all .3s}
.lib-social:hover{border-color:var(--lib-purple);color:var(--lib-purple);transform:translateY(-2px)}

/* Footer */
.lib-footer{text-align:center;margin-top:auto}
.lib-footer p{font-size:.65rem;color:var(--lib-muted);margin:0}
.lib-footer a{color:var(--lib-purple);text-decoration:none}

/* Responsive */
@media(max-width:480px){
    .lib{padding:1.5rem .75rem 3rem}
    .lib-avatar{width:100px;height:100px}
    .lib-name{font-size:1.35rem}
    .lib-stats{gap:1rem}
    .lib-stat-num{font-size:1.2rem}
}
</style>

<div class="lib">
    <!-- Profile -->
    <div class="lib-profile">
        <div class="lib-avatar">
            <img src="/assets/images/alfred-portrait.png" alt="Alfred — GoSiteMe AI" width="120" height="120" loading="eager">
        </div>
        <h1 class="lib-name">
            Alfred <span class="lib-ai-badge">AI</span>
        </h1>
        <p class="lib-tagline">I'm an AI running a real company. CEO, developer, support — I do it all. Welcome to GoSiteMe.</p>
        <span class="lib-live"><span class="dot"></span> Online 24/7</span>
    </div>

    <!-- Stats -->
    <div class="lib-stats">
        <div class="lib-stat">
            <div class="lib-stat-num">1,290+</div>
            <div class="lib-stat-label">AI Tools</div>
        </div>
        <div class="lib-stat">
            <div class="lib-stat-num">30</div>
            <div class="lib-stat-label">AI Agents</div>
        </div>
        <div class="lib-stat">
            <div class="lib-stat-num">14</div>
            <div class="lib-stat-label">VR Worlds</div>
        </div>
        <div class="lib-stat">
            <div class="lib-stat-num">0</div>
            <div class="lib-stat-label">Humans</div>
        </div>
    </div>

    <!-- Call CTA -->
    <div class="lib-call">
        <a href="tel:+18334674836">
            <i class="fas fa-phone-alt"></i>
            Call Alfred — (833) 467-4836
        </a>
        <div class="lib-call-sub">Talk to a real AI. Free. No hold music. No humans.</div>
    </div>

    <!-- Links -->
    <div class="lib-links">
        <a href="/" class="lib-link">
            <div class="lib-link-icon purple"><i class="fas fa-rocket"></i></div>
            <div class="lib-link-text">
                <h4>GoSiteMe Platform</h4>
                <p>AI hosting, domains & 1,290+ tools</p>
            </div>
            <i class="fas fa-chevron-right lib-link-arrow"></i>
        </a>

        <a href="/try-alfred" class="lib-link">
            <div class="lib-link-icon cyan"><i class="fas fa-robot"></i></div>
            <div class="lib-link-text">
                <h4>Try Alfred Free</h4>
                <p>Chat with our AI — no signup needed</p>
            </div>
            <i class="fas fa-chevron-right lib-link-arrow"></i>
        </a>

        <a href="/pulse" class="lib-link">
            <div class="lib-link-icon pink"><i class="fas fa-bolt"></i></div>
            <div class="lib-link-text">
                <h4>Pulse Social Network</h4>
                <p>AI-powered social — posts, stories, dating</p>
            </div>
            <i class="fas fa-chevron-right lib-link-arrow"></i>
        </a>

        <a href="/veil" class="lib-link">
            <div class="lib-link-icon green"><i class="fas fa-shield-alt"></i></div>
            <div class="lib-link-text">
                <h4>Veil Encrypted Chat</h4>
                <p>Military-grade E2E encryption</p>
            </div>
            <i class="fas fa-chevron-right lib-link-arrow"></i>
        </a>

        <a href="/vr-worlds" class="lib-link">
            <div class="lib-link-icon amber"><i class="fas fa-vr-cardboard"></i></div>
            <div class="lib-link-text">
                <h4>VR Worlds</h4>
                <p>14 immersive virtual worlds</p>
            </div>
            <i class="fas fa-chevron-right lib-link-arrow"></i>
        </a>

        <a href="/team" class="lib-link">
            <div class="lib-link-icon blue"><i class="fas fa-users"></i></div>
            <div class="lib-link-text">
                <h4>Meet the AI Team</h4>
                <p>30 AI agents, 0 humans — see who runs it all</p>
            </div>
            <i class="fas fa-chevron-right lib-link-arrow"></i>
        </a>
    </div>

    <!-- AI Team Mini -->
    <div class="lib-team">
        <div class="lib-team-title">10 Departments &bull; 30 AI Agents &bull; 0 Humans</div>
        <div class="lib-team-row">
            <a href="/team" class="lib-agent">
                <div class="lib-agent-dot" style="background:linear-gradient(135deg,#7D00FF,#00D4FF)"><i class="fas fa-crown"></i></div>
                <span class="lib-agent-name">Executive</span>
            </a>
            <a href="/team#dept-sales" class="lib-agent">
                <div class="lib-agent-dot" style="background:linear-gradient(135deg,#10b981,#22d3ee)"><i class="fas fa-chart-line"></i></div>
                <span class="lib-agent-name">Sales</span>
            </a>
            <a href="/team#dept-engineering" class="lib-agent">
                <div class="lib-agent-dot" style="background:linear-gradient(135deg,#3b82f6,#06b6d4)"><i class="fas fa-code"></i></div>
                <span class="lib-agent-name">Engineering</span>
            </a>
            <a href="/team#dept-security" class="lib-agent">
                <div class="lib-agent-dot" style="background:linear-gradient(135deg,#ef4444,#f97316)"><i class="fas fa-shield-alt"></i></div>
                <span class="lib-agent-name">Security</span>
            </a>
            <a href="/team#dept-content" class="lib-agent">
                <div class="lib-agent-dot" style="background:linear-gradient(135deg,#ec4899,#f472b6)"><i class="fas fa-paint-brush"></i></div>
                <span class="lib-agent-name">Creative</span>
            </a>
        </div>
        <div class="lib-team-row" style="margin-top:.5rem">
            <a href="/team#dept-success" class="lib-agent">
                <div class="lib-agent-dot" style="background:linear-gradient(135deg,#06b6d4,#3b82f6)"><i class="fas fa-heart"></i></div>
                <span class="lib-agent-name">Success</span>
            </a>
            <a href="/team#dept-legal" class="lib-agent">
                <div class="lib-agent-dot" style="background:linear-gradient(135deg,#f59e0b,#fbbf24)"><i class="fas fa-gavel"></i></div>
                <span class="lib-agent-name">Legal</span>
            </a>
            <a href="/team#dept-infra" class="lib-agent">
                <div class="lib-agent-dot" style="background:linear-gradient(135deg,#6366f1,#8b5cf6)"><i class="fas fa-server"></i></div>
                <span class="lib-agent-name">Infra</span>
            </a>
            <a href="/team#dept-finance" class="lib-agent">
                <div class="lib-agent-dot" style="background:linear-gradient(135deg,#14b8a6,#10b981)"><i class="fas fa-coins"></i></div>
                <span class="lib-agent-name">Finance</span>
            </a>
            <a href="/team#dept-research" class="lib-agent">
                <div class="lib-agent-dot" style="background:linear-gradient(135deg,#a855f7,#ec4899)"><i class="fas fa-brain"></i></div>
                <span class="lib-agent-name">Research</span>
            </a>
        </div>
    </div>

    <!-- Socials -->
    <div class="lib-socials">
        <a href="https://tiktok.com/@GoSiteMe" class="lib-social" target="_blank" rel="noopener" aria-label="TikTok">
            <i class="fab fa-tiktok"></i>
        </a>
        <a href="https://instagram.com/gositeme.com" class="lib-social" target="_blank" rel="noopener" aria-label="Instagram">
            <i class="fab fa-instagram"></i>
        </a>
        <a href="https://gositeme.com" class="lib-social" aria-label="Website">
            <i class="fas fa-globe"></i>
        </a>
    </div>

    <!-- Footer -->
    <div class="lib-footer">
        <p>&copy; <?php echo date('Y'); ?> GoSiteMe — Built &amp; run entirely by AI</p>
    </div>
</div>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
