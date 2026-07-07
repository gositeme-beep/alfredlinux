<?php
/**
 * Alfred Linux — Dedication to Howard Olsen
 * A true General in Jesus' Army.
 */
$year = date('Y');
require_once __DIR__ . '/includes/ga-release-state.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>General Howard Olsen — A Soldier of Christ — Alfred Linux</title>
    <meta name="description" content="Dedicated to General Howard Olsen, one of the greatest soldiers in Christ, a true General in God's Army who loves, helps, fights, and preaches the word of Yeshua.">
    <meta property="og:title" content="General Howard Olsen — A Soldier of Christ">
    <meta property="og:description" content="A dedication to a true soldier in Christ. He loves, he helps, he fights, and he preaches.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://alfredlinux.com/howard-olsen">
    <meta property="og:image" content="https://alfredlinux.com/og-image.png">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="canonical" href="https://alfredlinux.com/howard-olsen">
    <link rel="icon" href="/favicon.ico">
    <link rel="stylesheet" href="/assets/css/nav.css">
    <style>
        :root {
            --bg: #06060b; --surface: rgba(255,255,255,0.03); --surface-hover: rgba(255,255,255,0.06);
            --border: rgba(255,255,255,0.06); --border-hover: rgba(212,175,55,0.3);
            --text: #e0e0e0; --text-muted: #9ca3af; --text-dim: #6b7280;
            --accent: #D4AF37; --accent-light: #FFDF00; --accent2: #B8860B; /* Gold theme for a General */
            --green: #34d399; --amber: #f59e0b; --cyan: #22d3ee;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter',-apple-system,BlinkMacSystemFont,sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; -webkit-font-smoothing: antialiased; line-height: 1.7; }
        a { color: var(--accent-light); text-decoration: none; }
        a:hover { text-decoration: underline; }

        .hero { text-align: center; padding: 6rem 2rem 3rem; background: radial-gradient(ellipse at 50% 20%, rgba(212,175,55,0.12) 0%, transparent 55%); }
        .hero h1 { font-size: clamp(2.5rem, 6vw, 4rem); font-weight: 900; margin-bottom: 0.5rem; background: linear-gradient(135deg, #fff, var(--accent-light), var(--accent2)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; line-height: 1.1;}
        .hero h2 { font-size: 1.5rem; color: var(--accent); margin-bottom: 2rem; font-weight: 600; letter-spacing: 2px; text-transform: uppercase;}
        .hero p { color: var(--text-muted); font-size: 1.2rem; max-width: 750px; margin: 0 auto; line-height: 1.8;}

        .portrait-frame { width: 100%; max-width: 600px; height: auto; aspect-ratio: 5/4; margin: 0 auto 3rem; border-radius: 12px; border: 4px solid var(--accent-light); box-shadow: 0 0 50px rgba(255,223,0,0.6), 0 0 100px rgba(212,175,55,0.4), inset 0 0 40px rgba(0,0,0,0.9); background: #000; position: relative; display: flex; align-items: center; justify-content: center; animation: float 6s ease-in-out infinite, holo-pulse 4s infinite alternate; z-index: 1;}
        .portrait-frame img { width: 100%; height: 100%; object-fit: cover; border-radius: 8px; -webkit-mask-image: linear-gradient(to bottom, black 85%, transparent 100%); mask-image: linear-gradient(to bottom, black 85%, transparent 100%); z-index: 0;}
        .portrait-frame::after { content: ''; position: absolute; top: -15px; left: -15px; right: -15px; bottom: -15px; border-radius: 16px; border: 2px dashed rgba(255,223,0,0.5); pointer-events: none; opacity: 0.8; animation: pulse-border 4s infinite alternate;}
        .portrait-frame::before { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; border-radius: 8px; box-shadow: inset 0 0 60px rgba(255,223,0,0.4); background: linear-gradient(135deg, rgba(255,255,255,0.4) 0%, transparent 40%, transparent 60%, rgba(212,175,55,0.4) 100%); mix-blend-mode: overlay; pointer-events: none; z-index: 2; border: 1px solid rgba(255,255,255,0.2);}

        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-15px); } }
        @keyframes holo-pulse { 0% { box-shadow: 0 0 40px rgba(255,223,0,0.4), 0 0 80px rgba(212,175,55,0.2); filter: brightness(1); } 100% { box-shadow: 0 0 80px rgba(255,223,0,0.8), 0 0 140px rgba(212,175,55,0.6), 0 0 250px rgba(212,175,55,0.3); filter: brightness(1.2); } }
        @keyframes pulse-border { 0% { opacity: 0.4; transform: scale(1); } 100% { opacity: 1; transform: scale(1.02); } }

        .container { max-width: 800px; margin: 0 auto; padding: 0 2rem 4rem; }

        .dedication-box { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 2.5rem; margin: 2rem 0; box-shadow: 0 10px 30px rgba(0,0,0,0.5); text-align: center; position: relative; overflow: hidden; }
        .dedication-box::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, transparent, var(--accent), transparent); }
        .dedication-box p { font-size: 1.1rem; color: #e5e7eb; margin-bottom: 1.5rem; font-style: italic;}
        .dedication-box strong { color: var(--accent-light); font-weight: 700;}

        .traits { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-top: 2rem; }
        .trait { background: rgba(0,0,0,0.3); padding: 1rem; border-radius: 8px; border: 1px solid var(--border); text-align: center; transition: all 0.3s ease;}
        .trait:hover { border-color: var(--accent); transform: translateY(-3px); }
        .trait span { display: block; font-size: 1.2rem; font-weight: 800; color: var(--text); margin-bottom: 0.25rem;}
        .trait p { font-size: 0.85rem; color: var(--text-dim); font-style: normal; margin: 0;}

        .verse { margin-top: 3rem; text-align: center; color: var(--text-dim); padding-top: 2rem; border-top: 1px solid var(--border); }
        .verse-text { font-family: 'Times New Roman', serif; font-size: 1.2rem; font-style: italic; color: #fff; margin-bottom: 0.5rem; }
        .verse-ref { font-size: 0.9rem; font-weight: 600; color: var(--accent); text-transform: uppercase; letter-spacing: 1px;}

        footer { text-align: center; padding: 3rem 2rem; color: var(--text-dim); font-size: 0.85rem; border-top: 1px solid var(--border); margin-top: 4rem;}
        footer a { color: var(--accent-light); }

        @media (max-width: 768px) {
            .hero { padding: 5rem 1.5rem 2rem; }
            .container { padding: 0 1.25rem 3rem; }
            .dedication-box { padding: 1.5rem; }
        }
    </style>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/img/favicon/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/img/favicon/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#D4AF37">
</head>
<body>

<?php $currentPage = 'howard-olsen'; include __DIR__ . '/includes/nav.php'; ?>

<main>
    <section class="hero">
        <div class="portrait-frame">
            <img src="/assets/img/general-howard-olsen.jpg" alt="General Howard Olsen in the Full Armor of God">
        </div>
        <h1>General Howard Olsen</h1>
        <h2 style="color: var(--text-dim); font-size: 1.1rem; letter-spacing: 4px; font-weight: 500; text-transform: uppercase; margin-bottom: 2.5rem;">A Soldier of Christ</h2>
        <p>A special dedication to one of the greatest defenders of the Faith that we have ever known. A man who embodies the love, sacrifice, and unbreakable strength of the Holy Spirit.</p>
    </section>

    <div class="container">
        <div class="dedication-box">
            <p>"To bear the rank of General in the Army of the Most High is not a title of prestige, but a covenant of ultimate sacrifice. God Himself has called him a General—an Apostolic warrior called to fight battles, train the army, prophesy the way forward, and care for the people."</p>
            <p><strong>For 23 solid years, he has served the Lord with all his heart, soul, and mind. When called into full-time ministry, he abandoned worldly security to trust solely in God’s provision. When a brother in Christ cried out for help on the frontlines, he did not hesitate—he provided instantly. He is the living embodiment of brotherhood, love, and unwavering faith.</strong></p>
            
            <div class="traits">
                <div class="trait">
                    <span>Apostolic</span>
                    <p>Warrior Calling</p>
                </div>
                <div class="trait">
                    <span>Trains</span>
                    <p>The Army of God</p>
                </div>
                <div class="trait">
                    <span>Prophesies</span>
                    <p>The Way Forward</p>
                </div>
                <div class="trait">
                    <span>Cares</span>
                    <p>For God's People</p>
                </div>
            </div>
        </div>

        <div class="verse">
            <div class="verse-text">"I have fought the good fight, I have finished the race, I have kept the faith."</div>
            <div class="verse-ref">2 Timothy 4:7</div>
        </div>
    </div>
</main>

<footer>
    <div class="container">
        <p>&copy; <?php echo $year; ?> Alfred Linux. Soli Deo Gloria.</p>
        <p style="margin-top: 0.5rem;"><a href="/">Return to Home</a></p>
    </div>
</footer>

</body>
</html>
