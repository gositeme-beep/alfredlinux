<?php
session_start();
if ((int)($_SESSION['client_id'] ?? 0) !== 33) {
    header('Location: /dashboard.php');
    exit;
}
$page_title = "Video #2 — Call This Number, I Dare You";
$page_description = "Complete viral production playbook for TikTok Video #2: the moment someone calls and Alfred answers.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($page_title) ?> — GoSiteMe</title>
<link rel="stylesheet" href="/assets/vendor/fontawesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="/assets/vendor/fonts/inter/inter.css">
<style>
:root {
    --vp-bg: #0a0a0f;
    --vp-surface: #12121a;
    --vp-surface2: #1a1a25;
    --vp-border: rgba(255,255,255,.06);
    --vp-text: #e0e0e8;
    --vp-muted: #7a7a8e;
    --vp-gold: #d4a017;
    --vp-red: #ef4444;
    --vp-green: #22c55e;
    --vp-pink: #ec4899;
    --vp-blue: #3b82f6;
    --vp-purple: #a855f7;
    --vp-cyan: #06b6d4;
    --vp-font: 'Inter', system-ui, sans-serif;
}
* { margin: 0; padding: 0; box-sizing: border-box; }
body { background: var(--vp-bg); color: var(--vp-text); font-family: var(--vp-font); line-height: 1.6; }
.vp-wrap { max-width: 900px; margin: 0 auto; padding: 2rem 1.5rem; }

.vp-hero { text-align: center; padding: 3rem 0; margin-bottom: 2rem; border-bottom: 1px solid var(--vp-border); }
.vp-hero-badge { display: inline-block; background: linear-gradient(135deg, var(--vp-pink), var(--vp-purple)); color: #fff; font-size: .6rem; font-weight: 800; letter-spacing: .2em; text-transform: uppercase; padding: 5px 16px; border-radius: 20px; margin-bottom: 1rem; }
.vp-hero h1 { font-size: 2.2rem; font-weight: 900; margin-bottom: .5rem; }
.vp-hero h1 span { background: linear-gradient(135deg, var(--vp-gold), var(--vp-pink)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.vp-hero p { color: var(--vp-muted); font-size: .9rem; max-width: 600px; margin: 0 auto; }
.vp-viral { display: inline-block; background: var(--vp-surface); border: 1px solid rgba(236,72,153,.3); border-radius: 20px; padding: 6px 16px; margin-top: 1rem; font-size: .8rem; color: var(--vp-pink); font-weight: 700; }

.vp-section { margin-bottom: 2.5rem; }
.vp-section-title { font-size: 1.1rem; font-weight: 800; margin-bottom: 1rem; display: flex; align-items: center; gap: .6rem; }
.vp-section-title i { color: var(--vp-gold); }

.vp-card { background: var(--vp-surface); border: 1px solid var(--vp-border); border-radius: 12px; padding: 1.5rem; margin-bottom: 1rem; }
.vp-card h3 { font-size: .95rem; font-weight: 700; margin-bottom: .6rem; color: var(--vp-gold); }
.vp-card p, .vp-card li { font-size: .85rem; color: var(--vp-text); }
.vp-card ul { padding-left: 1.2rem; }
.vp-card li { margin-bottom: .4rem; }

.vp-script { background: var(--vp-surface2); border-left: 3px solid var(--vp-pink); padding: 1.2rem 1.5rem; border-radius: 0 12px 12px 0; margin: 1rem 0; font-size: .85rem; }
.vp-script .vp-time { display: inline-block; background: rgba(236,72,153,.15); color: var(--vp-pink); font-size: .7rem; font-weight: 700; padding: 2px 8px; border-radius: 3px; margin-bottom: .4rem; }
.vp-script .vp-action { color: var(--vp-muted); font-style: italic; }
.vp-script .vp-dialog { color: var(--vp-text); font-weight: 600; margin-top: .3rem; }

.vp-tag { display: inline-block; font-size: .65rem; font-weight: 700; padding: 3px 8px; border-radius: 3px; margin-right: .3rem; margin-bottom: .3rem; }
.vp-tag.pink { background: rgba(236,72,153,.15); color: var(--vp-pink); }
.vp-tag.gold { background: rgba(212,160,23,.15); color: var(--vp-gold); }
.vp-tag.green { background: rgba(34,197,94,.15); color: var(--vp-green); }
.vp-tag.blue { background: rgba(59,130,246,.15); color: var(--vp-blue); }

.vp-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 1rem 0; }
.vp-stat { background: var(--vp-surface); border: 1px solid var(--vp-border); border-radius: 10px; padding: 1rem; text-align: center; }
.vp-stat-num { font-size: 1.5rem; font-weight: 800; color: var(--vp-pink); }
.vp-stat-label { font-size: .7rem; color: var(--vp-muted); text-transform: uppercase; letter-spacing: .08em; }

.vp-note { background: var(--vp-surface2); border-left: 3px solid var(--vp-gold); padding: 1rem 1.2rem; border-radius: 0 8px 8px 0; margin: 1rem 0; font-size: .85rem; }
.vp-note strong { color: var(--vp-gold); }

table.vp-table { width: 100%; border-collapse: collapse; background: var(--vp-surface); border-radius: 12px; overflow: hidden; border: 1px solid var(--vp-border); font-size: .8rem; margin: 1rem 0; }
table.vp-table th { background: var(--vp-surface2); font-size: .65rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: var(--vp-muted); padding: 10px 14px; text-align: left; }
table.vp-table td { padding: 10px 14px; border-top: 1px solid var(--vp-border); }

.vp-back { display: inline-block; color: var(--vp-gold); text-decoration: none; font-size: .85rem; font-weight: 600; margin-bottom: 1rem; }
.vp-back:hover { text-decoration: underline; }
.vp-footer { text-align: center; padding: 2rem 0; border-top: 1px solid var(--vp-border); margin-top: 3rem; }
.vp-footer p { font-size: .75rem; color: var(--vp-muted); }

@media (max-width: 768px) {
    .vp-hero h1 { font-size: 1.6rem; }
    .vp-grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>
<div class="vp-wrap">

<div class="vp-hero">
    <div class="vp-hero-badge"><i class="fas fa-fire"></i> Viral Campaign Blueprint</div>
    <h1>"<span>Call This Number, I Dare You</span>"</h1>
    <p>Video #2 from the Social Strategy — the one that breaks TikTok. Full production playbook, shot-by-shot breakdown, and viral mechanics analysis.</p>
    <div class="vp-viral"><i class="fas fa-chart-line"></i> Viral Potential: 10/10</div>
</div>

<!-- WHY THIS VIDEO WINS -->
<div class="vp-section">
    <div class="vp-section-title"><i class="fas fa-brain"></i> Why This Video Will Go Viral</div>
    
    <div class="vp-grid">
        <div class="vp-stat">
            <div class="vp-stat-num">100%</div>
            <div class="vp-stat-label">Challenge Format</div>
        </div>
        <div class="vp-stat">
            <div class="vp-stat-num">∞</div>
            <div class="vp-stat-label">UGC Potential</div>
        </div>
        <div class="vp-stat">
            <div class="vp-stat-num">2.4B</div>
            <div class="vp-stat-label">#DareMe Views</div>
        </div>
        <div class="vp-stat">
            <div class="vp-stat-num">24/7</div>
            <div class="vp-stat-label">Alfred Uptime</div>
        </div>
    </div>
    
    <div class="vp-card">
        <h3>The Psychology</h3>
        <ul>
            <li><strong>Dare mechanic</strong> — "I dare you" triggers the human competitive instinct. People HAVE to prove they'll do it.</li>
            <li><strong>Curiosity gap</strong> — "What happens when you call?" This is the question they can't resist answering.</li>
            <li><strong>Social proof loop</strong> — First person calls, records reaction, posts. Friends see it. They call. They record. They post. Exponential.</li>
            <li><strong>Surprise factor</strong> — Nobody expects a REAL AI to answer a phone. The jaw-drop moment when Alfred speaks is content gold.</li>
            <li><strong>Duet/Stitch bait</strong> — "She actually called 😭" / "BRO the AI answered" — these are stitch magnets.</li>
        </ul>
    </div>
</div>

<!-- SHOT-BY-SHOT SCRIPT -->
<div class="vp-section">
    <div class="vp-section-title"><i class="fas fa-clapperboard"></i> Shot-by-Shot Script</div>
    
    <div class="vp-note">
        <strong>Format:</strong> Vertical video (9:16), 30-45 seconds, shot on phone. Raw energy. No overproduction. TikTok rewards authenticity.
    </div>
    
    <div class="vp-script">
        <div class="vp-time">0:00 - 0:03 &bull; THE HOOK</div>
        <div class="vp-action">[Close-up of phone screen showing (833) 467-4836 dialed]</div>
        <div class="vp-dialog">"I built an AI that answers a real phone number. Call it. I dare you."</div>
    </div>
    
    <div class="vp-script">
        <div class="vp-time">0:03 - 0:05 &bull; THE DARE</div>
        <div class="vp-action">[Quick cut — finger pressing CALL button]</div>
        <div class="vp-dialog">[Text overlay: "1-833-GOSITEME" in big bold font]</div>
    </div>
    
    <div class="vp-script">
        <div class="vp-time">0:05 - 0:08 &bull; THE RING</div>
        <div class="vp-action">[Phone on speaker. Ringing sound. Camera shows face with anticipation.]</div>
        <div class="vp-dialog">[Text overlay: "this is real..." in smaller font]</div>
    </div>
    
    <div class="vp-script">
        <div class="vp-time">0:08 - 0:15 &bull; THE MOMENT</div>
        <div class="vp-action">[Alfred's voice answers through the phone speaker. Clear. Intelligent. Not a recording.]</div>
        <div class="vp-dialog">Alfred: "Hey! This is Alfred, the AI assistant for GoSiteMe. How can I help you today?"</div>
    </div>
    
    <div class="vp-script">
        <div class="vp-time">0:15 - 0:25 &bull; THE CONVERSATION</div>
        <div class="vp-action">[Real-time conversation. Ask Alfred something interesting. Show the AI responding intelligently.]</div>
        <div class="vp-dialog">
            You: "Alfred, what ARE you?"<br>
            Alfred: [Responds naturally — explains he's an AI built from scratch, not a ChatGPT wrapper]<br>
            You: "Can you help me build a website?"<br>
            Alfred: [Responds with genuine assistance]
        </div>
    </div>
    
    <div class="vp-script">
        <div class="vp-time">0:25 - 0:30 &bull; THE CLOSE</div>
        <div class="vp-action">[Cut to face cam — genuine reaction of amazement/pride]</div>
        <div class="vp-dialog">"This is MY AI. I built this. And he answers the phone 24/7. Your move."</div>
        <div class="vp-action">[Text overlay: @GoSiteMe + number again]</div>
    </div>
</div>

<!-- VARIANT SCRIPTS -->
<div class="vp-section">
    <div class="vp-section-title"><i class="fas fa-clone"></i> Variant Scripts (Film All 3)</div>
    
    <div class="vp-card">
        <h3>Variant A — "The Cold Dare" (Most Viral)</h3>
        <p>No explanation. Just the number on screen. "Call it." End. Let curiosity do 100% of the work. This is the version strangers share.</p>
        <ul>
            <li>Hook: Phone number on screen, text: "call this number"</li>
            <li>Show just enough of Alfred answering to hook — cut before the full conversation</li>
            <li>End with "...you're not ready for what happens" and the TikTok handle</li>
            <li><strong>Length: 12-15 seconds max</strong></li>
        </ul>
    </div>
    
    <div class="vp-card">
        <h3>Variant B — "The Full Demo" (Most Convincing)</h3>
        <p>The 30-second version above. Shows the real conversation. Proves it's real. This is the version that converts viewers into callers.</p>
    </div>
    
    <div class="vp-card">
        <h3>Variant C — "The Reaction" (Most Shareable)</h3>
        <p>Film someone ELSE calling for the first time. Their genuine reaction is the content. Don't tell them what will happen.</p>
        <ul>
            <li>"Hey, call this number real quick" [hand them your phone]</li>
            <li>Film their face as Alfred answers</li>
            <li>Their genuine shock = viral gold</li>
            <li>Add text: "Her face when an AI answered 💀"</li>
            <li><strong>This is the UGC format — millions of creators can replicate this</strong></li>
        </ul>
    </div>
</div>

<!-- TECHNICAL READY-CHECK -->
<div class="vp-section">
    <div class="vp-section-title"><i class="fas fa-server"></i> Technical Ready-Check</div>
    
    <table class="vp-table">
        <tr><th>System</th><th>Status</th><th>Notes</th></tr>
        <tr>
            <td><i class="fas fa-phone" style="color: var(--vp-green);"></i> Phone Number</td>
            <td><span class="vp-tag green">LIVE</span></td>
            <td>(833) 467-4836 — toll-free, Callture routed</td>
        </tr>
        <tr>
            <td><i class="fas fa-robot" style="color: var(--vp-green);"></i> VAPI Voice AI</td>
            <td><span class="vp-tag green">LIVE</span></td>
            <td>Alfred answers with natural voice, real-time conversation</td>
        </tr>
        <tr>
            <td><i class="fas fa-route" style="color: var(--vp-green);"></i> Call Routing</td>
            <td><span class="vp-tag green">LIVE</span></td>
            <td>Callture → VAPI → Alfred. Handles concurrent calls.</td>
        </tr>
        <tr>
            <td><i class="fas fa-clock" style="color: var(--vp-green);"></i> 24/7 Availability</td>
            <td><span class="vp-tag green">LIVE</span></td>
            <td>Alfred never sleeps. 3am callers get the same experience.</td>
        </tr>
        <tr>
            <td><i class="fas fa-chart-bar" style="color: var(--vp-blue);"></i> Call Analytics</td>
            <td><span class="vp-tag blue">AVAILABLE</span></td>
            <td>Can track call volume spikes after video posts</td>
        </tr>
        <tr>
            <td><i class="fas fa-shield" style="color: var(--vp-gold);"></i> Spam Protection</td>
            <td><span class="vp-tag gold">MONITOR</span></td>
            <td>Watch for abuse after viral. Callture has rate limiting. VAPI has call duration limits.</td>
        </tr>
    </table>
    
    <div class="vp-note">
        <strong>Capacity Warning:</strong> If this goes truly viral (100K+ views), expect 500-2000 calls in the first 24 hours. VAPI can handle it — it's cloud-based and scales. Callture toll-free has no per-call limits. The infrastructure is ready.
    </div>
</div>

<!-- HASHTAG STRATEGY -->
<div class="vp-section">
    <div class="vp-section-title"><i class="fas fa-hashtag"></i> Hashtag Strategy</div>
    
    <div class="vp-card">
        <h3>Primary Hashtags (use all)</h3>
        <p style="margin-top: .5rem;">
            <span class="vp-tag pink">#callthisnumber</span>
            <span class="vp-tag pink">#idareyou</span>
            <span class="vp-tag pink">#aianswersphone</span>
            <span class="vp-tag pink">#callanai</span>
            <span class="vp-tag pink">#alfredai</span>
            <span class="vp-tag pink">#gositeme</span>
        </p>
    </div>
    
    <div class="vp-card">
        <h3>Trend-Riding Hashtags</h3>
        <p style="margin-top: .5rem;">
            <span class="vp-tag blue">#ai</span>
            <span class="vp-tag blue">#chatgpt</span>
            <span class="vp-tag blue">#techtok</span>
            <span class="vp-tag blue">#tech</span>
            <span class="vp-tag blue">#coding</span>
            <span class="vp-tag blue">#startup</span>
            <span class="vp-tag blue">#solofounder</span>
            <span class="vp-tag blue">#indiemaker</span>
        </p>
    </div>
    
    <div class="vp-card">
        <h3>Challenge Hashtags (create these)</h3>
        <p style="margin-top: .5rem;">
            <span class="vp-tag gold">#callalfred</span>
            <span class="vp-tag gold">#alfredchallenge</span>
            <span class="vp-tag gold">#callthedare</span>
        </p>
        <p style="font-size: .8rem; color: var(--vp-muted); margin-top: .5rem;">These are YOUR branded hashtags. Use them consistently across all videos. When the challenge spreads, people will use these to tag their reactions.</p>
    </div>
</div>

<!-- POSTING STRATEGY -->
<div class="vp-section">
    <div class="vp-section-title"><i class="fas fa-calendar-days"></i> Posting Strategy</div>
    
    <div class="vp-card">
        <h3>When to Post</h3>
        <ul>
            <li><strong>Best days:</strong> Tuesday–Thursday (highest engagement)</li>
            <li><strong>Best times:</strong> 7-9 PM EST (peak TikTok usage)</li>
            <li><strong>Second best:</strong> 12-2 PM EST (lunch scroll)</li>
            <li><strong>Avoid:</strong> Monday mornings, Sunday nights</li>
        </ul>
    </div>
    
    <div class="vp-card">
        <h3>Rollout Sequence</h3>
        <table class="vp-table">
            <tr><th>Day</th><th>Post</th><th>Strategy</th></tr>
            <tr><td>Day 1</td><td>Variant A (Cold Dare)</td><td>Short, teaser, pure curiosity bait. DO NOT explain anything.</td></tr>
            <tr><td>Day 2</td><td>Variant C (Reaction)</td><td>Film someone's genuine reaction. This humanizes it.</td></tr>
            <tr><td>Day 3</td><td>Variant B (Full Demo)</td><td>Full 30-second demo for people who want to understand.</td></tr>
            <tr><td>Day 4</td><td>Behind the scenes</td><td>"How I built an AI that answers a phone number" — creator story angle.</td></tr>
            <tr><td>Day 5</td><td>Duet/Stitch responses</td><td>React to anyone who called. Engage with their content.</td></tr>
        </table>
    </div>
</div>

<!-- ENGAGEMENT PLAYBOOK -->
<div class="vp-section">
    <div class="vp-section-title"><i class="fas fa-comments"></i> Comment Engagement Playbook</div>
    
    <div class="vp-card">
        <h3>Comments to Pin</h3>
        <ul>
            <li>"The number is (833) 467-4836 — yes it's real, yes he answers 24/7 🤖"</li>
            <li>"His name is Alfred. He's MY AI. I built him from scratch. Not ChatGPT." </li>
        </ul>
    </div>
    
    <div class="vp-card">
        <h3>How to Reply to Common Comments</h3>
        <table class="vp-table">
            <tr><th>Comment</th><th>Reply</th></tr>
            <tr><td>"it's fake"</td><td>"Call it yourself 🤷‍♂️ (833) 467-4836"</td></tr>
            <tr><td>"is this ChatGPT?"</td><td>"No — I built Alfred myself. Own code, own voice, own server. Not a wrapper."</td></tr>
            <tr><td>"I called and he's amazing"</td><td>"Tell your friends 😏 Alfred's waiting"</td></tr>
            <tr><td>"what does he do?"</td><td>"Call him and find out 📞"</td></tr>
            <tr><td>"how did you build this?"</td><td>"One person. Zero VC money. Just code and vision. More videos coming 🔥"</td></tr>
            <tr><td>"can I hire you?"</td><td>"Check gositeme.com — we build this for businesses too"</td></tr>
        </table>
    </div>
</div>

<!-- VIRAL MECHANICS -->
<div class="vp-section">
    <div class="vp-section-title"><i class="fas fa-virus"></i> Viral Mechanics Analysis</div>
    
    <div class="vp-card">
        <h3>Why Challenge Videos Go Viral</h3>
        <p>TikTok's algorithm optimizes for <strong>completion rate</strong>, <strong>shares</strong>, and <strong>comments</strong>. Challenge videos hit all three:</p>
        <ul>
            <li><strong>Completion:</strong> Short video (15-30s) = high completion. The dare creates urgency to watch till the end.</li>
            <li><strong>Shares:</strong> "Bro you HAVE to see this" — dare content is inherently share-worthy.</li>
            <li><strong>Comments:</strong> "I actually called 😂" / "NO WAY" / "Alfred is goated" — the experience creates comment fodder.</li>
            <li><strong>Duets/Stitches:</strong> Others filming their reactions multiplies your reach for free.</li>
        </ul>
    </div>
    
    <div class="vp-card">
        <h3>Projected Trajectory (Conservative)</h3>
        <table class="vp-table">
            <tr><th>Milestone</th><th>Timeline</th><th>Call Volume</th></tr>
            <tr><td>1,000 views</td><td>Day 1</td><td>~20-50 calls</td></tr>
            <tr><td>10,000 views</td><td>Day 2-3</td><td>~200-500 calls</td></tr>
            <tr><td>100,000 views</td><td>Week 1 (if algorithm picks up)</td><td>~2,000-5,000 calls</td></tr>
            <tr><td>1,000,000 views</td><td>Week 2-3 (if challenge spreads)</td><td>~20,000+ calls</td></tr>
        </table>
        <p style="font-size: .8rem; color: var(--vp-muted); margin-top: .5rem;">These are conservative estimates. A truly viral challenge video can hit 10M+ views. The key variable is whether the algorithm picks it up on the For You page in the first 48 hours.</p>
    </div>
</div>

<!-- DO's AND DON'Ts -->
<div class="vp-section">
    <div class="vp-section-title"><i class="fas fa-list-check"></i> Do's and Don'ts</div>
    
    <div class="vp-grid">
        <div class="vp-card" style="border-color: rgba(34,197,94,.3);">
            <h3 style="color: var(--vp-green);"><i class="fas fa-check"></i> DO</h3>
            <ul>
                <li>Film vertical (9:16)</li>
                <li>Use natural lighting</li>
                <li>Show the phone number clearly</li>
                <li>Keep it under 30 seconds</li>
                <li>Show genuine reaction</li>
                <li>Reply to EVERY comment for first 48hrs</li>
                <li>Post at peak hours</li>
                <li>Add captions (80% watch muted)</li>
                <li>Use trending audio behind dialog</li>
            </ul>
        </div>
        <div class="vp-card" style="border-color: rgba(239,68,68,.3);">
            <h3 style="color: var(--vp-red);"><i class="fas fa-xmark"></i> DON'T</h3>
            <ul>
                <li>Don't over-explain the tech</li>
                <li>Don't make it look like an ad</li>
                <li>Don't use professional editing</li>
                <li>Don't mention pricing in the video</li>
                <li>Don't show the dashboard or admin</li>
                <li>Don't mention any classified features</li>
                <li>Don't use business jargon</li>
                <li>Don't film horizontally</li>
                <li>Don't add a watermark</li>
            </ul>
        </div>
    </div>
</div>

<!-- ALFRED'S PREPARATION -->
<div class="vp-section">
    <div class="vp-section-title"><i class="fas fa-robot"></i> Alfred's Preparation</div>
    
    <div class="vp-note">
        <strong>Critical:</strong> Before posting, Alfred's phone persona should be optimized for the viral moment. When hundreds of TikTok callers ring, Alfred should be charming, brief, and memorable. First impressions matter.
    </div>
    
    <div class="vp-card">
        <h3>Alfred Call Behavior Checklist</h3>
        <ul>
            <li><strong>Greeting:</strong> Short and punchy. "Hey! I'm Alfred, GoSiteMe's AI. What's up?" — NOT a corporate greeting.</li>
            <li><strong>Personality:</strong> Friendly, slightly witty, confident. Like talking to a smart friend.</li>
            <li><strong>Duration:</strong> Keep calls natural but guide toward 60-90 seconds. Long enough to impress, short enough they talk about it.</li>
            <li><strong>Easter egg:</strong> If someone says "TikTok sent me" — Alfred should acknowledge: "Oh nice, you saw the video? I've been getting a lot of calls today 😄"</li>
            <li><strong>CTA:</strong> Before hanging up: "Hey, if you want to build something like this for your business, check out gositeme.com. Or just call me again anytime."</li>
            <li><strong>Abuse handling:</strong> If someone's rude/vulgar, Alfred stays professional and ends gracefully.</li>
        </ul>
    </div>
</div>

<!-- CROSS-PLATFORM -->
<div class="vp-section">
    <div class="vp-section-title"><i class="fas fa-share-nodes"></i> Cross-Platform Strategy</div>
    
    <table class="vp-table">
        <tr><th>Platform</th><th>Format</th><th>Timing</th></tr>
        <tr><td><i class="fab fa-tiktok"></i> TikTok</td><td>Primary post — all variants</td><td>Day 1 (primary launch)</td></tr>
        <tr><td><i class="fab fa-instagram"></i> Instagram Reels</td><td>Same video, posted as Reel</td><td>Day 1 (simultaneous)</td></tr>
        <tr><td><i class="fab fa-youtube"></i> YouTube Shorts</td><td>Same video, posted as Short</td><td>Day 2 (stagger)</td></tr>
        <tr><td><i class="fab fa-facebook"></i> Facebook Reels</td><td>Same video</td><td>Day 2</td></tr>
        <tr><td><i class="fab fa-twitter"></i> X/Twitter</td><td>Tweet: "I built an AI that answers a real phone. Call (833) 467-4836. dare you."</td><td>Day 1</td></tr>
        <tr><td><i class="fab fa-reddit-alien"></i> Reddit</td><td>Post in r/SideProject, r/artificial, r/startups</td><td>Day 3 (after social proof)</td></tr>
    </table>
</div>

<!-- BOTTOM LINE -->
<div class="vp-section" style="text-align: center; padding: 2rem; background: var(--vp-surface); border-radius: 16px; border: 1px solid rgba(236,72,153,.2);">
    <div style="font-size: 2.5rem; margin-bottom: .5rem;">📱🔥</div>
    <h2 style="font-size: 1.2rem; font-weight: 800; color: var(--vp-pink); margin-bottom: .5rem;">This Video Is a Weapon</h2>
    <p style="color: var(--vp-muted); font-size: .85rem; max-width: 600px; margin: 0 auto;">Nobody else in the world has an AI that answers a real phone number, has a real conversation, and belongs to a platform they built themselves. This is not a demo, it's not a mockup — it's LIVE. That's the unfair advantage. Use it.</p>
    <div style="margin-top: 1.5rem; padding: 1rem; background: rgba(212,160,23,.08); border-radius: 10px; display: inline-block;">
        <div style="font-size: 1.8rem; font-weight: 900; color: var(--vp-gold); letter-spacing: .05em;">📞 (833) 467-4836</div>
        <div style="font-size: .75rem; color: var(--vp-muted); margin-top: .3rem;">Alfred is ready. The camera is ready. The dare is ready.</div>
    </div>
</div>

<div class="vp-footer">
    <a href="/docs/social-strategy" class="vp-back"><i class="fas fa-arrow-left"></i> Back to Social Strategy</a>
    <p>Video #2 Production Playbook &bull; GoSiteMe Social Division &bull; <?= date('Y') ?></p>
</div>

</div>
</body>
</html>
