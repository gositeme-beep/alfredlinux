<?php
/**
 * About Alfred Search Crawler
 * ────────────────────────────
 * This is the page server admins see when they check their logs
 * and find AlfredSearchBot. It's a marketing opportunity —
 * convert sysadmins into users.
 */
$page_title = 'About AlfredSearchBot — Alfred Search Crawler';
$page_description = 'AlfredSearchBot is the web crawler for Alfred Search, a privacy-first AI search engine. Zero tracking, zero cookies, zero profiling. Learn more about how we crawl and how to control it.';
$page_canonical = 'https://root.com/about-crawler';
require_once __DIR__ . '/includes/site-header.inc.php';
?>

<style>
:root {
    --bg-deep: #0a0a0f;
    --bg-card: #12121a;
    --bg-card-hover: #1a1a28;
    --border: #1e1e30;
    --accent: #60a5fa;
    --accent2: #a78bfa;
    --green: #34d399;
    --text: #e2e8f0;
    --text-dim: #8892a8;
}
.ac-hero {
    background: linear-gradient(135deg, #0f0f1a 0%, #1a1034 50%, #0f172a 100%);
    padding: 100px 20px 80px;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.ac-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle at 30% 40%, rgba(96,165,250,0.08) 0%, transparent 50%),
                radial-gradient(circle at 70% 60%, rgba(167,139,250,0.06) 0%, transparent 50%);
    animation: hero-drift 20s ease-in-out infinite alternate;
}
@keyframes hero-drift {
    0% { transform: translate(0, 0) rotate(0deg); }
    100% { transform: translate(-3%, -3%) rotate(5deg); }
}
.ac-hero h1 {
    font-size: clamp(2rem, 5vw, 3.5rem);
    font-weight: 800;
    color: #fff;
    position: relative;
    z-index: 1;
    margin-bottom: 16px;
}
.ac-hero h1 span { background: linear-gradient(135deg, var(--accent), var(--accent2)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.ac-hero .subtitle {
    font-size: 1.25rem;
    color: var(--text-dim);
    max-width: 700px;
    margin: 0 auto 32px;
    position: relative;
    z-index: 1;
}
.ac-hero .badge-row {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
    position: relative;
    z-index: 1;
}
.ac-hero .badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: rgba(96,165,250,0.1);
    border: 1px solid rgba(96,165,250,0.2);
    border-radius: 50px;
    color: var(--accent);
    font-size: 0.85rem;
    font-weight: 600;
}
.ac-hero .badge i { font-size: 0.75rem; }
.ac-hero .badge.green { background: rgba(52,211,153,0.1); border-color: rgba(52,211,153,0.2); color: var(--green); }
.ac-hero .badge.purple { background: rgba(167,139,250,0.1); border-color: rgba(167,139,250,0.2); color: var(--accent2); }

.ac-section {
    max-width: 1000px;
    margin: 0 auto;
    padding: 60px 20px;
}
.ac-section h2 {
    font-size: 1.8rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 24px;
}
.ac-section h2 i { color: var(--accent); margin-right: 10px; }

/* Info Cards */
.ac-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin: 30px 0;
}
.ac-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 28px;
    transition: all 0.3s;
}
.ac-card:hover {
    background: var(--bg-card-hover);
    border-color: rgba(96,165,250,0.3);
    transform: translateY(-2px);
}
.ac-card .card-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    margin-bottom: 16px;
}
.ac-card .card-icon.blue { background: rgba(96,165,250,0.15); color: var(--accent); }
.ac-card .card-icon.green { background: rgba(52,211,153,0.15); color: var(--green); }
.ac-card .card-icon.purple { background: rgba(167,139,250,0.15); color: var(--accent2); }
.ac-card .card-icon.orange { background: rgba(245,158,11,0.15); color: #f59e0b; }
.ac-card h3 { font-size: 1.1rem; color: #fff; margin-bottom: 8px; }
.ac-card p { color: var(--text-dim); line-height: 1.6; font-size: 0.95rem; }

/* UA String Box */
.ua-box {
    background: #0d0d15;
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 20px;
    margin: 20px 0;
    font-family: 'JetBrains Mono', 'Fira Code', monospace;
}
.ua-box .label { color: var(--text-dim); font-size: 0.8rem; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px; }
.ua-box .value { color: var(--green); font-size: 1rem; word-break: break-all; }

/* robots.txt example */
.robots-example {
    background: #0d0d15;
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 24px;
    margin: 20px 0;
}
.robots-example pre {
    color: var(--text);
    font-family: 'JetBrains Mono', 'Fira Code', monospace;
    font-size: 0.9rem;
    line-height: 1.7;
    margin: 0;
    white-space: pre-wrap;
}
.robots-example .comment { color: #6b7280; }
.robots-example .key { color: var(--accent); }
.robots-example .val { color: var(--green); }

/* Comparison Table */
.compare-table {
    width: 100%;
    border-collapse: collapse;
    margin: 24px 0;
    border-radius: 12px;
    overflow: hidden;
}
.compare-table thead th {
    background: rgba(96,165,250,0.1);
    color: var(--accent);
    padding: 14px 16px;
    text-align: left;
    font-weight: 600;
    font-size: 0.9rem;
}
.compare-table tbody td {
    padding: 12px 16px;
    border-top: 1px solid var(--border);
    color: var(--text);
    font-size: 0.9rem;
}
.compare-table tbody tr:hover { background: rgba(96,165,250,0.03); }
.compare-table .check { color: var(--green); font-weight: bold; }
.compare-table .cross { color: #ef4444; }
.compare-table .alfred-col { background: rgba(96,165,250,0.05); }

/* CTA */
.ac-cta {
    background: linear-gradient(135deg, #1a1034 0%, #0f172a 100%);
    border: 1px solid rgba(96,165,250,0.2);
    border-radius: 20px;
    padding: 48px;
    text-align: center;
    margin: 40px 0;
}
.ac-cta h2 { margin-bottom: 12px; }
.ac-cta p { color: var(--text-dim); margin-bottom: 24px; font-size: 1.05rem; }
.ac-cta .btn-row { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
.ac-cta .btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 14px 28px;
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    color: #fff;
    font-weight: 700;
    border-radius: 12px;
    font-size: 1rem;
    text-decoration: none;
    transition: transform 0.2s, box-shadow 0.2s;
}
.ac-cta .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 32px rgba(96,165,250,0.3); }
.ac-cta .btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 14px 28px;
    background: rgba(255,255,255,0.05);
    border: 1px solid var(--border);
    color: var(--text);
    font-weight: 600;
    border-radius: 12px;
    font-size: 1rem;
    text-decoration: none;
    transition: all 0.2s;
}
.ac-cta .btn-secondary:hover { background: rgba(255,255,255,0.08); border-color: var(--accent); }

/* Stats Row */
.ac-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 16px;
    margin: 30px 0;
}
.ac-stat {
    text-align: center;
    padding: 20px;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 12px;
}
.ac-stat .num { font-size: 2rem; font-weight: 800; background: linear-gradient(135deg, var(--accent), var(--accent2)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.ac-stat .lbl { color: var(--text-dim); font-size: 0.85rem; margin-top: 4px; }

/* Contact */
.contact-box {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 24px;
    margin: 24px 0;
    color: var(--text);
}
.contact-box a { color: var(--accent); text-decoration: none; }
.contact-box a:hover { text-decoration: underline; }

@media (max-width: 600px) {
    .ac-hero { padding: 60px 16px 50px; }
    .ac-section { padding: 40px 16px; }
    .ac-cta { padding: 32px 20px; }
}
</style>

<!-- ═══ HERO ═══ -->
<div class="ac-hero">
    <h1><span>AlfredSearchBot</span></h1>
    <p class="subtitle">
        You're seeing our crawler in your server logs? Great. We're building a search engine
        that doesn't spy on people. Here's everything you need to know.
    </p>
    <div class="badge-row">
        <span class="badge green"><i class="fas fa-shield-halved"></i> Respects robots.txt</span>
        <span class="badge"><i class="fas fa-clock"></i> Rate limited</span>
        <span class="badge purple"><i class="fas fa-eye-slash"></i> Zero tracking engine</span>
        <span class="badge green"><i class="fas fa-handshake"></i> Ethical crawling</span>
    </div>
</div>

<!-- ═══ WHO WE ARE ═══ -->
<div class="ac-section">
    <h2><i class="fas fa-robot"></i> What is Alfred Search?</h2>
    <p style="color:var(--text-dim); font-size:1.1rem; line-height:1.7; margin-bottom:24px;">
        Alfred Search is a <strong style="color:#fff">privacy-first, AI-native search engine</strong> built by
        <a href="https://root.com" style="color:var(--accent)">GoSiteMe</a>. Unlike Google, Bing, or even DuckDuckGo,
        we build our own independent web index using AlfredSearchBot — and we do it ethically.
    </p>
    <p style="color:var(--text-dim); font-size:1.05rem; line-height:1.7;">
        No tracking. No cookies. No fingerprinting. No ad profiles. No filter bubbles.
        Just answers. We believe the web deserves a search engine that works <em>for</em> people, not <em>on</em> people.
    </p>

    <div class="ac-stats">
        <div class="ac-stat"><div class="num">0</div><div class="lbl">Cookies set</div></div>
        <div class="ac-stat"><div class="num">0</div><div class="lbl">Trackers used</div></div>
        <div class="ac-stat"><div class="num">0</div><div class="lbl">Ads shown</div></div>
        <div class="ac-stat"><div class="num">100%</div><div class="lbl">Self-hosted</div></div>
        <div class="ac-stat"><div class="num">AI</div><div class="lbl">Powered answers</div></div>
    </div>
</div>

<!-- ═══ OUR CRAWLER ═══ -->
<div class="ac-section" style="border-top: 1px solid var(--border);">
    <h2><i class="fas fa-spider"></i> About Our Crawler</h2>
    <p style="color:var(--text-dim); font-size:1.05rem; line-height:1.7; margin-bottom:20px;">
        AlfredSearchBot crawls publicly accessible web pages to build Alfred Search's independent web index.
        Here's our user agent string:
    </p>

    <div class="ua-box">
        <div class="label">User-Agent String</div>
        <div class="value">AlfredSearchBot/1.0 (+https://root.com/about-crawler)</div>
    </div>

    <div class="ac-cards">
        <div class="ac-card">
            <div class="card-icon green"><i class="fas fa-shield-halved"></i></div>
            <h3>Respects robots.txt</h3>
            <p>We fully obey your robots.txt directives. Block us with <code>User-agent: AlfredSearchBot</code> and we'll never visit again.</p>
        </div>
        <div class="ac-card">
            <div class="card-icon blue"><i class="fas fa-clock"></i></div>
            <h3>Rate Limited</h3>
            <p>Maximum 1 request per second per domain. We also honor your Crawl-delay directive. We're a polite guest.</p>
        </div>
        <div class="ac-card">
            <div class="card-icon purple"><i class="fas fa-route"></i></div>
            <h3>Depth Limited</h3>
            <p>We crawl to a maximum depth of 3 links from seed pages. We won't spider your entire site unless you invite us to.</p>
        </div>
        <div class="ac-card">
            <div class="card-icon orange"><i class="fas fa-file-alt"></i></div>
            <h3>HTML Only</h3>
            <p>We only request text/html pages. No images, PDFs, videos, or binaries are downloaded. Minimal bandwidth impact.</p>
        </div>
    </div>
</div>

<!-- ═══ CONTROLLING THE CRAWLER ═══ -->
<div class="ac-section" style="border-top: 1px solid var(--border);">
    <h2><i class="fas fa-sliders"></i> Control How We Crawl Your Site</h2>
    <p style="color:var(--text-dim); font-size:1.05rem; line-height:1.7; margin-bottom:20px;">
        Add these directives to your <code>robots.txt</code> file to control AlfredSearchBot:
    </p>

    <div class="robots-example">
        <pre><span class="comment"># Block AlfredSearchBot entirely:</span>
<span class="key">User-agent:</span> <span class="val">AlfredSearchBot</span>
<span class="key">Disallow:</span> <span class="val">/</span>

<span class="comment"># Allow crawling but set a crawl delay:</span>
<span class="key">User-agent:</span> <span class="val">AlfredSearchBot</span>
<span class="key">Crawl-delay:</span> <span class="val">5</span>
<span class="key">Allow:</span> <span class="val">/</span>

<span class="comment"># Allow crawling except private areas:</span>
<span class="key">User-agent:</span> <span class="val">AlfredSearchBot</span>
<span class="key">Disallow:</span> <span class="val">/admin/</span>
<span class="key">Disallow:</span> <span class="val">/private/</span>
<span class="key">Allow:</span> <span class="val">/</span></pre>
    </div>

    <div class="contact-box">
        <strong>Need help?</strong> If you have questions about how we crawl your site, or want to request
        specific pages be added or removed from our index, contact us at
        <a href="mailto:crawler@root.com">crawler@root.com</a> or visit our
        <a href="/help.php">Help Center</a>.
    </div>
</div>

<!-- ═══ WHY ALFRED SEARCH ═══ -->
<div class="ac-section" style="border-top: 1px solid var(--border);">
    <h2><i class="fas fa-scale-balanced"></i> How Alfred Search Compares</h2>

    <table class="compare-table">
        <thead>
            <tr>
                <th>Feature</th>
                <th>Google</th>
                <th>Bing</th>
                <th>DuckDuckGo</th>
                <th class="alfred-col">Alfred Search</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Zero tracking</td>
                <td class="cross"><i class="fas fa-times"></i></td>
                <td class="cross"><i class="fas fa-times"></i></td>
                <td class="check"><i class="fas fa-check"></i> *</td>
                <td class="alfred-col check"><i class="fas fa-check"></i></td>
            </tr>
            <tr>
                <td>Own web index</td>
                <td class="check"><i class="fas fa-check"></i></td>
                <td class="check"><i class="fas fa-check"></i></td>
                <td class="cross"><i class="fas fa-times"></i> (uses Bing)</td>
                <td class="alfred-col check"><i class="fas fa-check"></i></td>
            </tr>
            <tr>
                <td>AI instant answers</td>
                <td class="check"><i class="fas fa-check"></i></td>
                <td class="check"><i class="fas fa-check"></i></td>
                <td class="cross"><i class="fas fa-times"></i></td>
                <td class="alfred-col check"><i class="fas fa-check"></i></td>
            </tr>
            <tr>
                <td>No ad profiles</td>
                <td class="cross"><i class="fas fa-times"></i></td>
                <td class="cross"><i class="fas fa-times"></i></td>
                <td class="check"><i class="fas fa-check"></i></td>
                <td class="alfred-col check"><i class="fas fa-check"></i></td>
            </tr>
            <tr>
                <td>Self-hostable</td>
                <td class="cross"><i class="fas fa-times"></i></td>
                <td class="cross"><i class="fas fa-times"></i></td>
                <td class="cross"><i class="fas fa-times"></i></td>
                <td class="alfred-col check"><i class="fas fa-check"></i></td>
            </tr>
            <tr>
                <td>Transparent ranking</td>
                <td class="cross"><i class="fas fa-times"></i></td>
                <td class="cross"><i class="fas fa-times"></i></td>
                <td class="cross"><i class="fas fa-times"></i></td>
                <td class="alfred-col check"><i class="fas fa-check"></i></td>
            </tr>
            <tr>
                <td>Deep research mode</td>
                <td class="cross"><i class="fas fa-times"></i></td>
                <td class="cross"><i class="fas fa-times"></i></td>
                <td class="cross"><i class="fas fa-times"></i></td>
                <td class="alfred-col check"><i class="fas fa-check"></i></td>
            </tr>
            <tr>
                <td>Open-source friendly</td>
                <td class="cross"><i class="fas fa-times"></i></td>
                <td class="cross"><i class="fas fa-times"></i></td>
                <td>Partial</td>
                <td class="alfred-col check"><i class="fas fa-check"></i></td>
            </tr>
        </tbody>
    </table>
    <p style="color:var(--text-dim); font-size:0.85rem;">* DuckDuckGo has improved privacy but still relies on Microsoft's Bing index and shows tracking-based ads.</p>
</div>

<!-- ═══ FOR WEBMASTERS ═══ -->
<div class="ac-section" style="border-top: 1px solid var(--border);">
    <h2><i class="fas fa-code"></i> For Webmasters &amp; Developers</h2>

    <div class="ac-cards">
        <div class="ac-card">
            <div class="card-icon blue"><i class="fas fa-plus-circle"></i></div>
            <h3>Submit Your Site</h3>
            <p>Want your pages in Alfred Search faster? Contact us to add your domain to our priority crawl queue. It's free.</p>
        </div>
        <div class="ac-card">
            <div class="card-icon green"><i class="fas fa-code-branch"></i></div>
            <h3>Search API Access</h3>
            <p>Integrate Alfred Search into your own apps. Our REST API is simple, powerful, and comes with SDKs for Node.js, Python, and PHP.</p>
        </div>
        <div class="ac-card">
            <div class="card-icon purple"><i class="fas fa-building"></i></div>
            <h3>White-Label Search</h3>
            <p>Run Alfred Search under your own brand. Perfect for enterprises, schools, and organizations that need private, internal search.</p>
        </div>
    </div>
</div>

<!-- ═══ CTA ═══ -->
<div class="ac-section">
    <div class="ac-cta">
        <h2 style="color:#fff; font-size:1.8rem;">Try Alfred Search Right Now</h2>
        <p>Search the web without being watched. It's that simple.</p>
        <div class="btn-row">
            <a href="/search.php" class="btn-primary"><i class="fas fa-search"></i> Launch Alfred Search</a>
            <a href="/developer-portal.php" class="btn-secondary"><i class="fas fa-code"></i> API Documentation</a>
            <a href="/about.php" class="btn-secondary"><i class="fas fa-info-circle"></i> About GoSiteMe</a>
        </div>
    </div>
</div>

<!-- ═══ Technical Specs ═══ -->
<div class="ac-section" style="border-top: 1px solid var(--border);">
    <h2><i class="fas fa-microchip"></i> Technical Specifications</h2>
    <div style="color:var(--text-dim); line-height:1.8;">
        <p><strong style="color:var(--text)">Bot Name:</strong> AlfredSearchBot</p>
        <p><strong style="color:var(--text)">Version:</strong> 1.0</p>
        <p><strong style="color:var(--text)">Operator:</strong> GoSiteMe (root.com)</p>
        <p><strong style="color:var(--text)">Purpose:</strong> Web indexing for Alfred Search</p>
        <p><strong style="color:var(--text)">Crawl rate:</strong> ≤1 request/second per domain (respects Crawl-delay)</p>
        <p><strong style="color:var(--text)">Max depth:</strong> 3 links from seed pages</p>
        <p><strong style="color:var(--text)">Content types:</strong> text/html only</p>
        <p><strong style="color:var(--text)">Max page size:</strong> 2MB</p>
        <p><strong style="color:var(--text)">Robots compliance:</strong> Full (User-agent, Disallow, Allow, Crawl-delay)</p>
        <p><strong style="color:var(--text)">Contact:</strong> <a href="mailto:crawler@root.com" style="color:var(--accent)">crawler@root.com</a></p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>
