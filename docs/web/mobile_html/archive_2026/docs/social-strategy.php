<?php
/**
 * SOCIAL MEDIA LAUNCH STRATEGY — Commander's Playbook
 * ====================================================
 * Content strategy for TikTok, Instagram, and Facebook.
 * Commander-only — operational planning document.
 */
require_once __DIR__ . '/../includes/auth-gate.inc.php';
if ((int)($_SESSION['client_id'] ?? 0) !== 33) {
    header('Location: /dashboard.php');
    exit;
}
define('GOSITEME_API', true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Social Media Strategy — GoSiteMe Launch Playbook</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="/assets/vendor/fonts/space-grotesk/space-grotesk.css" />
    <link rel="stylesheet" href="/assets/vendor/fonts/jetbrains-mono/jetbrains-mono.css" />
    <link rel="stylesheet" href="/assets/vendor/fontawesome/6.5.1/css/all.min.css" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #0a0a1a; color: #c8d0e7; font-family: 'Space Grotesk', sans-serif; line-height: 1.8; }
        .container { max-width: 1000px; margin: 0 auto; padding: 20px 24px; }

        .header { padding: 50px 0 30px; text-align: center; border-bottom: 2px solid rgba(245,158,11,0.2); margin-bottom: 40px; }
        .header h1 { font-size: clamp(1.6rem, 3vw, 2.2rem); font-weight: 900; color: #fff; margin-bottom: 8px; }
        .header .sub { color: #f59e0b; font-size: 0.8rem; letter-spacing: 2px; text-transform: uppercase; }

        .section { margin-bottom: 48px; }
        .section h2 { font-size: 1.3rem; font-weight: 700; color: #fff; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px solid rgba(125,0,255,0.12); display: flex; align-items: center; gap: 10px; }
        .section h2 i { color: #f59e0b; }
        .section h3 { font-size: 1.05rem; color: #c084fc; margin: 20px 0 10px; }

        .platform-card { background: rgba(255,255,255,0.02); border: 1px solid rgba(125,0,255,0.1); border-radius: 16px; padding: 24px; margin: 16px 0; }
        .platform-card h4 { color: #fff; font-size: 1rem; margin-bottom: 6px; display: flex; align-items: center; gap: 8px; }

        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin: 16px 0; }

        .content-idea { background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; padding: 18px; }
        .content-idea .type { color: #f59e0b; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
        .content-idea h5 { color: #fff; font-size: 0.92rem; margin-bottom: 6px; }
        .content-idea p { color: #7c8aaa; font-size: 0.82rem; }
        .content-idea .hook { color: #22c55e; font-size: 0.82rem; font-style: italic; margin-top: 8px; }
        .content-idea .viral { color: #ef4444; font-size: 0.72rem; font-weight: 700; margin-top: 6px; }

        .strategy-table { width: 100%; border-collapse: collapse; margin: 16px 0; }
        .strategy-table th { text-align: left; padding: 10px 14px; background: rgba(245,158,11,0.08); color: #f59e0b; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; }
        .strategy-table td { padding: 10px 14px; border-bottom: 1px solid rgba(255,255,255,0.03); font-size: 0.85rem; color: #a8b2d1; }

        .highlight { background: rgba(245,158,11,0.06); border: 1px solid rgba(245,158,11,0.15); border-radius: 12px; padding: 20px; margin: 20px 0; }
        .highlight h4 { color: #f59e0b; margin-bottom: 8px; }
        .highlight p { color: #a8b2d1; font-size: 0.9rem; }

        .verdict { background: linear-gradient(135deg, rgba(34,197,94,0.06), rgba(125,0,255,0.06)); border: 2px solid rgba(34,197,94,0.2); border-radius: 16px; padding: 24px; margin: 24px 0; text-align: center; }
        .verdict h3 { color: #22c55e; margin-bottom: 8px; }

        .checklist { list-style: none; padding: 0; }
        .checklist li { padding: 6px 0; display: flex; align-items: center; gap: 10px; font-size: 0.88rem; }
        .checklist .box { width: 18px; height: 18px; border: 2px solid rgba(245,158,11,0.3); border-radius: 4px; flex-shrink: 0; }

        .footer { text-align: center; padding: 30px 0; border-top: 1px solid rgba(125,0,255,0.08); margin-top: 40px; color: #4a5568; font-size: 0.78rem; }
        .footer a { color: #00D4FF; text-decoration: none; }

        @media (max-width: 768px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="container">

    <div class="header">
        <div class="sub">Commander's Playbook &bull; March 2026</div>
        <h1><i class="fas fa-bullhorn"></i> Social Media Launch Strategy</h1>
    </div>

    <!-- ===== ALFRED'S RECOMMENDATION ===== -->
    <div class="section">
        <h2><i class="fas fa-robot"></i> Alfred's Strategic Recommendation</h2>
        
        <div class="verdict">
            <h3><i class="fab fa-tiktok"></i> Start with TikTok</h3>
            <p style="color: #c8d0e7; max-width: 700px; margin: 0 auto;">TikTok's algorithm gives NEW accounts equal shot at going viral. Tech/AI content is exploding. We can SHOW Alfred doing things in real-time — that's the kind of content that stops people scrolling. Everything we make for TikTok gets repurposed to Instagram Reels and Facebook Reels — one effort, three platforms.</p>
        </div>

        <div class="highlight">
            <h4><i class="fas fa-lightbulb"></i> Why TikTok First, Then Instagram, Then Facebook</h4>
            <p><strong>TikTok</strong> — Algorithm favors new accounts, tech content is booming, short-form video is king. We can go from 0 to viral. Free reach.</p>
            <p style="margin-top:8px;"><strong>Instagram Reels</strong> — Same content, slightly different audience. More business-minded. Repurpose TikTok videos directly.</p>
            <p style="margin-top:8px;"><strong>Facebook</strong> — Organic reach is mostly dead, but Reels are getting pushed. Third priority. Repurpose same content.</p>
        </div>
    </div>

    <!-- ===== CONTENT PILLARS ===== -->
    <div class="section">
        <h2><i class="fas fa-film"></i> Content Pillars — What We Post</h2>
        <p style="color:#7c8aaa;margin-bottom:20px;">Every piece of content fits into one of these 5 pillars. All declassified. All designed to make people say "wait, WHAT?"</p>

        <div class="grid">
            <div class="platform-card">
                <h4><i class="fas fa-robot" style="color:#c084fc;"></i> Pillar 1: "Alfred Does Things"</h4>
                <p style="color:#7c8aaa;font-size:0.85rem;margin-top:8px;">Screen recordings of Alfred building pages, writing code, deploying to servers, managing infrastructure — in real-time. The "wow" factor.</p>
                <p style="color:#22c55e;font-size:0.82rem;margin-top:8px;"><strong>Goal:</strong> Prove the AI is real, not marketing</p>
            </div>
            <div class="platform-card">
                <h4><i class="fas fa-phone" style="color:#f59e0b;"></i> Pillar 2: "Call This Number"</h4>
                <p style="color:#7c8aaa;font-size:0.85rem;margin-top:8px;">Film someone calling (833) 467-4836 and talking to Alfred. Their genuine reaction. "Wait, the AI answered the phone?!"</p>
                <p style="color:#22c55e;font-size:0.82rem;margin-top:8px;"><strong>Goal:</strong> Virality through disbelief</p>
            </div>
            <div class="platform-card">
                <h4><i class="fas fa-code" style="color:#00D4FF;"></i> Pillar 3: "Code In Your Browser"</h4>
                <p style="color:#7c8aaa;font-size:0.85rem;margin-top:8px;">Show GoCodeMe IDE: open browser, write code, deploy to live site. Developers will share this. "Wait, I can code straight from the browser?"</p>
                <p style="color:#22c55e;font-size:0.82rem;margin-top:8px;"><strong>Goal:</strong> Developer audience acquisition</p>
            </div>
            <div class="platform-card">
                <h4><i class="fas fa-shield-halved" style="color:#22c55e;"></i> Pillar 4: "We Own Everything"</h4>
                <p style="color:#7c8aaa;font-size:0.85rem;margin-top:8px;">Internet Sovereignty messaging. "We don't use Google Fonts. We don't use Cloudflare. We host everything ourselves." Privacy-conscious audience.</p>
                <p style="color:#22c55e;font-size:0.82rem;margin-top:8px;"><strong>Goal:</strong> Brand differentiation, privacy audience</p>
            </div>
            <div class="platform-card">
                <h4><i class="fas fa-music" style="color:#c084fc;"></i> Pillar 5: "Hosting Company with a Music Studio"</h4>
                <p style="color:#7c8aaa;font-size:0.85rem;margin-top:8px;">SoundStudioPro demo. People will be confused in the best way. "Hold on, this is a HOSTING company? And they have a music studio??"</p>
                <p style="color:#22c55e;font-size:0.82rem;margin-top:8px;"><strong>Goal:</strong> Creative audience, shareability</p>
            </div>
        </div>
    </div>

    <!-- ===== FIRST 15 VIDEOS ===== -->
    <div class="section">
        <h2><i class="fas fa-video"></i> First 15 Video Ideas — Ready to Film</h2>
        <p style="color:#7c8aaa;margin-bottom:20px;">Each video is 30-60 seconds. Hook in first 3 seconds. Call to action at the end. Every video links to <strong>gositeme.com/why-gositeme</strong></p>

        <div class="grid">
            <div class="content-idea">
                <div class="type">Video #1 — The Introduction</div>
                <h5>"We built the world's first AI-powered hosting company"</h5>
                <p>Quick montage: Alfred writing code → deploying → answering phone → dashboard. Overlay text for each world first.</p>
                <div class="hook">Hook: "This AI runs an entire hosting company."</div>
                <div class="viral">POTENTIAL: High — tech/AI content is trending</div>
            </div>
            <div class="content-idea">
                <div class="type">Video #2 — The Phone Call</div>
                <h5>"I called a hosting company and an AI answered"</h5>
                <p>Film the phone screen. Dial (833) 467-4836. Show the real conversation. Reaction face.</p>
                <div class="hook">Hook: "Call this number. I dare you."</div>
                <div class="viral">POTENTIAL: Very High — reactions drive shares</div>
            </div>
            <div class="content-idea">
                <div class="type">Video #3 — Alfred Builds a Page</div>
                <h5>"Watch an AI build and deploy a web page in 60 seconds"</h5>
                <p>Screen record Alfred session: ask him to create a page → he writes PHP → uploads → it's live. Time-lapse to 60 sec.</p>
                <div class="hook">Hook: "This AI just wrote and deployed a whole page."</div>
                <div class="viral">POTENTIAL: High — developers will lose it</div>
            </div>
            <div class="content-idea">
                <div class="type">Video #4 — Browser IDE</div>
                <h5>"I code and deploy from just a browser tab"</h5>
                <p>Open GoCodeMe in Chrome. Write some code. Click deploy. Show it live. No VS Code needed.</p>
                <div class="hook">Hook: "Delete VS Code. You don't need it anymore."</div>
                <div class="viral">POTENTIAL: Medium-High — developer debate bait</div>
            </div>
            <div class="content-idea">
                <div class="type">Video #5 — The Music Studio</div>
                <h5>"My hosting company has a music studio"</h5>
                <p>Log into GoSiteMe. Show website management. Then click SoundStudio. Make a quick beat. Confusion is the hook.</p>
                <div class="hook">Hook: "Name one hosting company with a recording studio."</div>
                <div class="viral">POTENTIAL: Very High — absurdity drives shares</div>
            </div>
            <div class="content-idea">
                <div class="type">Video #6 — No Google Fonts</div>
                <h5>"We don't depend on Google for anything"</h5>
                <p>Explain sovereignty: self-hosted fonts, no CDN, no external deps. Show view-source — all local paths.</p>
                <div class="hook">Hook: "What happens to your site when Google goes down?"</div>
                <div class="viral">POTENTIAL: Medium — privacy niche but passionate</div>
            </div>
            <div class="content-idea">
                <div class="type">Video #7 — 3 Platforms</div>
                <h5>"We built 3 platforms that work as one"</h5>
                <p>Quick tour: GoSiteMe (hosting) → GoCodeMe (IDE) → Meta-Dome (identity). Show how they connect.</p>
                <div class="hook">Hook: "One ecosystem. Three platforms. Zero compromise."</div>
                <div class="viral">POTENTIAL: Medium — brand awareness</div>
            </div>
            <div class="content-idea">
                <div class="type">Video #8 — Alfred Remembers</div>
                <h5>"Our AI remembers conversations from months ago"</h5>
                <p>Show Alfred referencing old context. "Hey Alfred, remember when we..." and he recalls it all.</p>
                <div class="hook">Hook: "Most AI forgets you. Ours never does."</div>
                <div class="viral">POTENTIAL: High — memory/AI discourse is hot</div>
            </div>
            <div class="content-idea">
                <div class="type">Video #9 — GoDaddy Comparison</div>
                <h5>"GoDaddy vs GoSiteMe in 30 seconds"</h5>
                <p>Split screen. GoDaddy: generic template hosting. GoSiteMe: AI agent, voice support, IDE, music studio, digital passport.</p>
                <div class="hook">Hook: "GoDaddy charges you $15/mo for... what exactly?"</div>
                <div class="viral">POTENTIAL: Very High — comparison/controversy content</div>
            </div>
            <div class="content-idea">
                <div class="type">Video #10 — Digital Passport</div>
                <h5>"Get a digital passport from a hosting company"</h5>
                <p>Show Meta-Dome passport creation. Your sovereign identity. Not Google. Not Facebook. Yours.</p>
                <div class="hook">Hook: "This isn't a login. It's a digital passport."</div>
                <div class="viral">POTENTIAL: Medium — Web3/sovereignty crowd</div>
            </div>
            <div class="content-idea">
                <div class="type">Video #11 — Behind The Scenes</div>
                <h5>"A day running an AI hosting company"</h5>
                <p>Document: morning brief from Alfred, check missions, review deploys, answer client calls. Real operations.</p>
                <div class="hook">Hook: "My AI gives me a morning briefing every day."</div>
                <div class="viral">POTENTIAL: High — founder/startup content is popular</div>
            </div>
            <div class="content-idea">
                <div class="type">Video #12 — The 7 Firsts List</div>
                <h5>"7 things we invented that nobody else has"</h5>
                <p>List format: rapid-fire each first with 3-second visual for each. Fast-paced, high energy.</p>
                <div class="hook">Hook: "Number 5 will make you question your hosting provider."</div>
                <div class="viral">POTENTIAL: Medium-High — list content performs well</div>
            </div>
            <div class="content-idea">
                <div class="type">Video #13 — "Call and Ask"</div>
                <h5>"People called our AI and this happened"</h5>
                <p>Compilation of people's reactions to calling Alfred. Could become a series.</p>
                <div class="hook">Hook: "We gave people our phone number and told them to ask anything."</div>
                <div class="viral">POTENTIAL: Very High — reaction compilation format</div>
            </div>
            <div class="content-idea">
                <div class="type">Video #14 — Speed Deploy</div>
                <h5>"Idea to live website in 2 minutes"</h5>
                <p>Timer on screen. Describe an idea → Alfred builds it → GoCodeMe deploys it → it's live. Time challenge format.</p>
                <div class="hook">Hook: "I went from 'just an idea' to a live website in 2 minutes."</div>
                <div class="viral">POTENTIAL: High — speed/challenge content</div>
            </div>
            <div class="content-idea">
                <div class="type">Video #15 — The Manifesto</div>
                <h5>"Why we're building the internet differently"</h5>
                <p>Founder speaks to camera. The vision. Internet sovereignty. Why it matters. Emotional, authentic.</p>
                <div class="hook">Hook: "The internet wasn't supposed to be like this."</div>
                <div class="viral">POTENTIAL: Medium — vision/values content builds trust</div>
            </div>
        </div>
    </div>

    <!-- ===== POSTING SCHEDULE ===== -->
    <div class="section">
        <h2><i class="fas fa-calendar"></i> Posting Schedule</h2>

        <table class="strategy-table">
            <thead>
                <tr><th>Platform</th><th>Frequency</th><th>Best Times (EST)</th><th>Format</th></tr>
            </thead>
            <tbody>
                <tr><td><i class="fab fa-tiktok"></i> TikTok</td><td>1-2 videos/day</td><td>7am, 12pm, 7pm</td><td>15-60 sec vertical video</td></tr>
                <tr><td><i class="fab fa-instagram"></i> Instagram</td><td>1 Reel/day + Stories</td><td>11am, 2pm, 5pm</td><td>Reels (repurpose TikTok) + Stories</td></tr>
                <tr><td><i class="fab fa-facebook"></i> Facebook</td><td>1 Reel every 2 days</td><td>1pm, 3pm</td><td>Reels (best performers from TikTok)</td></tr>
            </tbody>
        </table>

        <div class="highlight">
            <h4><i class="fas fa-exclamation-triangle"></i> Platform Limits to Know</h4>
            <p><strong>TikTok:</strong> New accounts can post up to 5 videos/day. Start with 1-2 to build consistency.</p>
            <p style="margin-top:6px;"><strong>Instagram:</strong> Reels up to 90 seconds. Stories disappear in 24 hours. New accounts have lower reach initially — consistency over 2-4 weeks builds it.</p>
            <p style="margin-top:6px;"><strong>Facebook:</strong> Organic reach is ~2-5% of followers. Reels get more reach than regular posts. Don't invest heavily here initially.</p>
        </div>
    </div>

    <!-- ===== HASHTAG STRATEGY ===== -->
    <div class="section">
        <h2><i class="fas fa-hashtag"></i> Hashtag Strategy</h2>

        <div class="grid">
            <div class="platform-card">
                <h4>Primary (Use on Every Post)</h4>
                <p style="font-family:'JetBrains Mono',monospace; font-size:0.82rem; color:#22c55e; margin-top:8px;">#GoSiteMe #AIHosting #WebHosting #TechStartup #AI #FutureOfHosting</p>
            </div>
            <div class="platform-card">
                <h4>Secondary (Rotate by Content)</h4>
                <p style="font-family:'JetBrains Mono',monospace; font-size:0.82rem; color:#f59e0b; margin-top:8px;">#WebDev #Coding #BuildInPublic #SaaS #NoCode #WebDesign #SmallBusiness</p>
            </div>
            <div class="platform-card">
                <h4>Viral Hooks (Use Strategically)</h4>
                <p style="font-family:'JetBrains Mono',monospace; font-size:0.82rem; color:#c084fc; margin-top:8px;">#ThisIsReal #WaitForIt #MindBlown #TechTok #CodingTikTok #StartupLife</p>
            </div>
        </div>
    </div>

    <!-- ===== LINK STRATEGY ===== -->
    <div class="section">
        <h2><i class="fas fa-link"></i> Link Strategy</h2>
        <div class="highlight">
            <h4><i class="fas fa-bullseye"></i> All Roads Lead to One Page</h4>
            <p>Every social media bio and CTA links to: <strong>gositeme.com/why-gositeme</strong></p>
            <p style="margin-top:8px;">This is the public showcase page — no login required. Shows the 7 declassified World Firsts. Has phone number CTA and chat with Alfred CTA. Social Open Graph tags already configured for rich previews when shared.</p>
        </div>

        <h3>Bio Copy (Ready to Use)</h3>
        <div class="platform-card">
            <h4><i class="fab fa-tiktok"></i> TikTok Bio</h4>
            <p style="font-size:0.88rem;color:#a8b2d1;margin-top:8px;">The world's first AI-powered hosting ecosystem 🤖<br>7 world firsts. 3 platforms. 1 AI named Alfred.<br>Call Alfred: (833) 467-4836<br>🔗 gositeme.com/why-gositeme</p>
        </div>
        <div class="platform-card">
            <h4><i class="fab fa-instagram"></i> Instagram Bio</h4>
            <p style="font-size:0.88rem;color:#a8b2d1;margin-top:8px;">AI-powered hosting ecosystem 🚀<br>Our AI builds, deploys &amp; answers calls.<br>Call Alfred: (833) 467-4836<br>👇 See why we're different</p>
        </div>
    </div>

    <!-- ===== FIRST WEEK PLAN ===== -->
    <div class="section">
        <h2><i class="fas fa-rocket"></i> Week 1 Launch Plan</h2>
        <ul class="checklist">
            <li><span class="box"></span> Day 1: Post Video #1 (Introduction — "We built the world's first AI-powered hosting company")</li>
            <li><span class="box"></span> Day 2: Post Video #2 (The Phone Call — "Call this number. I dare you.")</li>
            <li><span class="box"></span> Day 3: Post Video #5 (Music Studio — confusion = shares)</li>
            <li><span class="box"></span> Day 4: Post Video #3 (Alfred Builds a Page — developer audience)</li>
            <li><span class="box"></span> Day 5: Post Video #9 (GoDaddy Comparison — controversy = reach)</li>
            <li><span class="box"></span> Day 6: Post Video #4 (Browser IDE — developer debate bait)</li>
            <li><span class="box"></span> Day 7: Post Video #15 (Manifesto — build the brand, show the vision)</li>
        </ul>

        <div class="highlight" style="margin-top: 20px;">
            <h4><i class="fas fa-fire"></i> The One Rule</h4>
            <p>Every video must make someone do ONE of these three things: <strong>call the number</strong>, <strong>visit the page</strong>, or <strong>share the video</strong>. If it doesn't do one of those, don't post it.</p>
        </div>
    </div>

    <!-- ===== CLASSIFICATION REMINDER ===== -->
    <div class="section">
        <h2><i class="fas fa-shield-halved"></i> Classification Reminder</h2>
        <div class="verdict" style="border-color: rgba(239,68,68,0.3);">
            <h3 style="color: #ef4444;"><i class="fas fa-lock"></i> Never Show in Public Content</h3>
            <p style="color:#c8d0e7;">DEFCON system &bull; Mission Control &bull; Commander dashboard internals &bull; Chromium browser/extensions &bull; Encryption vault details &bull; Server passwords/IPs &bull; OVH account details &bull; Internal architecture</p>
        </div>
        <div class="verdict">
            <h3><i class="fas fa-unlock"></i> Safe to Show</h3>
            <p style="color:#c8d0e7;">Alfred chatting &bull; Alfred building pages &bull; Voice calls &bull; GoCodeMe IDE &bull; SoundStudioPro &bull; Meta-Dome passport &bull; Self-hosted sovereignty &bull; Public dashboard features &bull; The why-gositeme page</p>
        </div>
    </div>

    <div class="footer">
        <p>GoSiteMe Social Media Strategy — Commander Eyes Only</p>
        <p style="margin-top:4px;">
            <a href="/why-gositeme">Public Showcase</a> &bull;
            <a href="/docs/world-firsts">World Firsts (Classified)</a> &bull;
            <a href="/docs/reseller-strategy">Reseller Strategy</a>
        </p>
    </div>

</div>
</body>
</html>
