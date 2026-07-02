<?php
/**
 * VR Worlds Directory — MetaDome
 * All 23 virtual worlds in one directory
 */
require_once __DIR__ . '/includes/db-config.inc.php';

$db = getSharedDB();

// World definitions — single source of truth
$worlds = [
    [
        'slug' => 'hub',
        'name' => 'MetaDome Hub',
        'tagline' => 'The central gateway to all worlds',
        'icon' => 'fa-globe-americas',
        'color' => '#00d4ff',
        'tech' => ['Three.js', 'WebXR'],
        'category' => 'hub',
        'rank_tier' => 0,
        'features' => ['Teleportation portals', 'World map', 'Social gathering'],
    ],
    [
        'slug' => 'chess',
        'name' => 'VR Chess Arena',
        'tagline' => 'Battle AI agents and human opponents',
        'icon' => 'fa-chess',
        'color' => '#fbbf24',
        'tech' => ['Three.js', 'WebXR', 'AI Agents'],
        'category' => 'games',
        'rank_tier' => 0,
        'features' => ['8 AI opponents', 'PvP matches', 'Tournaments', 'KGD wagers'],
    ],
    [
        'slug' => 'chess-masters',
        'name' => 'Chess Masters Club',
        'tagline' => 'Photorealistic chess experience',
        'icon' => 'fa-chess-king',
        'color' => '#8b5cf6',
        'tech' => ['Three.js', 'WebXR'],
        'category' => 'games',
        'rank_tier' => 0,
        'features' => ['Realistic 3D board', 'Club atmosphere', 'Leaderboard'],
    ],
    [
        'slug' => 'chess-ultimate',
        'name' => 'Chess Ultimate',
        'tagline' => 'Minimalist high-speed chess',
        'icon' => 'fa-chess-board',
        'color' => '#34d399',
        'tech' => ['Three.js'],
        'category' => 'games',
        'rank_tier' => 0,
        'features' => ['Quick matches', 'Ranked play', 'Clean UI'],
    ],
    [
        'slug' => 'checkers',
        'name' => '3D Checkers',
        'tagline' => 'Classic checkers in immersive 3D',
        'icon' => 'fa-th',
        'color' => '#f87171',
        'tech' => ['Three.js'],
        'category' => 'games',
        'rank_tier' => 0,
        'features' => ['AI opponent', 'PvP', 'KGD wagers'],
    ],
    [
        'slug' => 'pool',
        'name' => '3D Pool',
        'tagline' => 'Realistic billiards with physics',
        'icon' => 'fa-circle',
        'color' => '#22c55e',
        'tech' => ['Three.js'],
        'category' => 'games',
        'rank_tier' => 0,
        'features' => ['Physics engine', 'Multiple game modes', 'Spectating'],
    ],
    [
        'slug' => 'racing',
        'name' => 'VR Racing Track',
        'tagline' => 'High-speed racing in virtual tracks',
        'icon' => 'fa-flag-checkered',
        'color' => '#ef4444',
        'tech' => ['Three.js'],
        'category' => 'games',
        'rank_tier' => 0,
        'features' => ['Multiple tracks', 'Vehicle customization', 'Laps'],
    ],
    [
        'slug' => 'poker',
        'name' => 'Texas Hold\'em VR',
        'tagline' => '6 AI opponents, 4 game modes, realistic card physics',
        'icon' => 'fa-heart',
        'color' => '#dc2626',
        'tech' => ['Three.js', 'AI Agents'],
        'category' => 'games',
        'rank_tier' => 0,
        'features' => ['6 AI players', '4 game modes', 'GSM buy-in', 'Wagers'],
    ],
    [
        'slug' => 'command-and-conquer',
        'name' => 'Command & Conquer',
        'tagline' => 'Real-time strategy — build bases, train armies, conquer',
        'icon' => 'fa-chess-rook',
        'color' => '#b91c1c',
        'tech' => ['Three.js', 'AI Agents'],
        'category' => 'games',
        'rank_tier' => 0,
        'features' => ['RTS gameplay', 'Base building', 'Army management', 'Multiplayer'],
    ],
    [
        'slug' => 'speed-dating',
        'name' => 'Speed Dating',
        'tagline' => 'Meet new people, timed conversations',
        'icon' => 'fa-heart',
        'color' => '#ec4899',
        'tech' => ['Canvas'],
        'category' => 'social',
        'rank_tier' => 1,
        'features' => ['Matchmaking', 'Timed rounds', 'Interest filters'],
    ],
    [
        'slug' => 'sanctuary',
        'name' => 'The Sanctuary',
        'tagline' => 'Brotherhood of Jesus Christ — 60 agents, 50 languages, 13 games',
        'icon' => 'fa-church',
        'color' => '#fbbf24',
        'tech' => ['Three.js'],
        'category' => 'spiritual',
        'rank_tier' => 0,
        'features' => ['60 AI agents', '50 languages', '13 games', 'Prayer spaces'],
    ],
    [
        'slug' => 'kingdom',
        'name' => 'The Kingdom of God',
        'tagline' => 'Sacred space for reflection and worship',
        'icon' => 'fa-crown',
        'color' => '#d4af37',
        'tech' => ['Three.js'],
        'category' => 'spiritual',
        'rank_tier' => 0,
        'features' => ['Worship environment', 'Scripture study', 'Community'],
    ],
    [
        'slug' => 'concert',
        'name' => 'VR Concert Hall',
        'tagline' => 'Live music in virtual reality',
        'icon' => 'fa-music',
        'color' => '#a78bfa',
        'tech' => ['Three.js'],
        'category' => 'entertainment',
        'rank_tier' => 0,
        'features' => ['Live audio', 'Stage lighting', 'Audience seating'],
    ],
    [
        'slug' => 'dj-studio',
        'name' => 'SoundStudioPro DJ World',
        'tagline' => 'Full DJ studio and mixing environment',
        'icon' => 'fa-headphones',
        'color' => '#06b6d4',
        'tech' => ['Three.js', 'Web Audio'],
        'category' => 'entertainment',
        'rank_tier' => 0,
        'features' => ['Mixing console', 'Sound effects', 'Live streaming'],
    ],
    [
        'slug' => 'gallery',
        'name' => 'VR Art Gallery',
        'tagline' => 'Walk through curated digital art exhibits',
        'icon' => 'fa-palette',
        'color' => '#f472b6',
        'tech' => ['Three.js'],
        'category' => 'culture',
        'rank_tier' => 0,
        'features' => ['AI-generated art', 'User submissions', 'Guided tours'],
    ],
    [
        'slug' => 'lounge',
        'name' => 'VR Social Lounge',
        'tagline' => 'Relax and socialize in a virtual lounge',
        'icon' => 'fa-couch',
        'color' => '#14b8a6',
        'tech' => ['Three.js'],
        'category' => 'social',
        'rank_tier' => 0,
        'features' => ['Voice chat', 'Ambient music', 'Comfortable seating'],
    ],
    [
        'slug' => 'office',
        'name' => 'Virtual Office',
        'tagline' => 'Collaborative workspace in VR',
        'icon' => 'fa-building',
        'color' => '#3b82f6',
        'tech' => ['Three.js'],
        'category' => 'work',
        'rank_tier' => 4,
        'features' => ['Shared screens', 'Meeting rooms', 'Whiteboards'],
    ],
    [
        'slug' => 'circuit-lab',
        'name' => 'ZPE Circuit Lab',
        'tagline' => 'Zero-point energy circuit experimentation',
        'icon' => 'fa-bolt',
        'color' => '#eab308',
        'tech' => ['Three.js'],
        'category' => 'science',
        'rank_tier' => 4,
        'features' => ['Circuit builder', 'Physics simulation', 'Experiments'],
    ],
    [
        'slug' => 'commander-tour',
        'name' => 'Commander Metaverse Tour',
        'tagline' => 'A guided tour of the entire MetaDome',
        'icon' => 'fa-route',
        'color' => '#d946ef',
        'tech' => ['Three.js', 'WebXR'],
        'category' => 'hub',
        'rank_tier' => 0,
        'features' => ['Guided walkthrough', 'All worlds preview', 'History'],
    ],
    [
        'slug' => 'experiences',
        'name' => 'VR Experiences Directory',
        'tagline' => 'Browse and discover all experiences',
        'icon' => 'fa-compass',
        'color' => '#64748b',
        'tech' => ['WebXR'],
        'category' => 'hub',
        'rank_tier' => 0,
        'features' => ['World browser', 'Categories', 'Quick launch'],
    ],
];

// Fetch live data
try {
    $plotStats = $db->query("SELECT plot_type, COUNT(*) as c FROM vr_world_plots GROUP BY plot_type")->fetchAll(PDO::FETCH_KEY_PAIR);
    $totalPlots = array_sum($plotStats);
    $claimedPlots = (int) $db->query("SELECT COUNT(*) FROM vr_world_plots WHERE owner_id IS NOT NULL")->fetchColumn();
} catch (Exception $e) {
    $plotStats = [];
    $totalPlots = 256;
    $claimedPlots = 0;
}

try {
    $chessStats = [
        'matches'    => (int) $db->query("SELECT COUNT(*) FROM vr_chess_matches")->fetchColumn(),
        'challenges' => (int) $db->query("SELECT COUNT(*) FROM vr_chess_challenges")->fetchColumn(),
        'agents'     => (int) $db->query("SELECT COUNT(*) FROM vr_chess_agents")->fetchColumn(),
    ];
} catch (Exception $e) {
    $chessStats = ['matches' => 0, 'challenges' => 0, 'agents' => 0];
}

try {
    $totalVisitors = (int) $db->query("SELECT COALESCE(SUM(total_hits),0) FROM metadome_visitor_stats WHERE domain='meta-dome.com'")->fetchColumn();
} catch (Exception $e) {
    $totalVisitors = 0;
}

$categories = [
    'hub'           => ['label' => 'Hub & Navigation',  'icon' => 'fa-globe-americas', 'desc' => 'Central gateways and navigation'],
    'games'         => ['label' => 'Games',              'icon' => 'fa-gamepad',        'desc' => 'Competitive and casual games'],
    'social'        => ['label' => 'Social',             'icon' => 'fa-users',          'desc' => 'Meet people, socialize, date'],
    'entertainment' => ['label' => 'Entertainment',      'icon' => 'fa-music',          'desc' => 'Music, concerts, DJ experiences'],
    'spiritual'     => ['label' => 'Spiritual',          'icon' => 'fa-pray',           'desc' => 'Faith, worship, reflection'],
    'culture'       => ['label' => 'Culture & Art',      'icon' => 'fa-palette',        'desc' => 'Art, exhibits, creative expression'],
    'work'          => ['label' => 'Work & Collaboration','icon' => 'fa-briefcase',     'desc' => 'Offices, meetings, collaboration'],
    'science'       => ['label' => 'Science & Research', 'icon' => 'fa-flask',          'desc' => 'Experimentation and discovery'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VR Worlds Directory — MetaDome | GoSiteMe</title>
    <meta name="description" content="Explore <?= count($worlds) ?> virtual worlds in the MetaDome. Games, concerts, art galleries, social spaces, and more — all in your browser, no headset required.">
    <meta property="og:title" content="MetaDome VR Worlds — <?= count($worlds) ?> Worlds to Explore">
    <meta property="og:description" content="Enter virtual chess arenas, concert halls, art galleries, DJ studios, and sanctuaries. Built with Three.js and WebXR. Free to enter.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://root.com/vr-worlds">
    <link rel="canonical" href="https://root.com/vr-worlds">
    <link rel="stylesheet" href="/assets/vendor/fonts/inter/inter.css">
    <link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">

    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "ItemList",
        "name": "MetaDome VR Worlds",
        "description": "Directory of virtual worlds in the MetaDome ecosystem",
        "numberOfItems": <?= count($worlds) ?>,
        "itemListElement": [
            <?php foreach ($worlds as $i => $w): ?>
            {"@type": "ListItem", "position": <?= $i + 1 ?>, "name": "<?= htmlspecialchars($w['name']) ?>", "url": "https://root.com/vr/<?= $w['slug'] ?>/"}<?= $i < count($worlds) - 1 ? ',' : '' ?>

            <?php endforeach; ?>
        ]
    }
    </script>

    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

        :root {
            --vw-bg: #020208;
            --vw-card: rgba(255,255,255,0.03);
            --vw-border: rgba(255,255,255,0.06);
            --vw-text: rgba(255,255,255,0.88);
            --vw-muted: rgba(255,255,255,0.5);
            --vw-cyan: #00d4ff;
            --vw-purple: #8b5cf6;
            --vw-green: #34d399;
            --vw-gold: #fbbf24;
        }

        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: var(--vw-bg);
            color: var(--vw-text);
            line-height: 1.6;
            overflow-x: hidden;
        }

        a { color: var(--vw-cyan); text-decoration: none; }
        a:hover { text-decoration: underline; }

        .vw-ambient {
            position: fixed; inset: 0; z-index: 0; pointer-events: none;
            background:
                radial-gradient(ellipse 80% 60% at 20% 10%, rgba(139,92,246,.08), transparent),
                radial-gradient(ellipse 60% 50% at 80% 80%, rgba(0,212,255,.06), transparent);
        }

        .vw-container { position: relative; z-index: 1; max-width: 1200px; margin: 0 auto; padding: 0 1.5rem; }

        /* Nav */
        .vw-nav {
            display: flex; justify-content: space-between; align-items: center;
            padding: 1.5rem 0; border-bottom: 1px solid var(--vw-border);
        }
        .vw-logo { font-size: 1.5rem; font-weight: 800; letter-spacing: -.03em; }
        .vw-logo span { background: linear-gradient(135deg, var(--vw-cyan), var(--vw-purple)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .vw-nav-links { display: flex; gap: 1.5rem; align-items: center; }
        .vw-nav-links a { color: var(--vw-muted); font-size: .85rem; font-weight: 500; transition: color .2s; }
        .vw-nav-links a:hover { color: #fff; text-decoration: none; }

        /* Hero */
        .vw-hero { text-align: center; padding: 6rem 2rem 4rem; }
        .vw-hero h1 {
            font-size: clamp(2rem, 5vw, 3.5rem); font-weight: 900;
            line-height: 1.1; letter-spacing: -.04em; margin-bottom: 1rem;
        }
        .vw-hero h1 .grad {
            background: linear-gradient(135deg, var(--vw-cyan) 0%, var(--vw-purple) 50%, #ec4899 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .vw-hero p { font-size: 1.1rem; color: var(--vw-muted); max-width: 600px; margin: 0 auto; }

        /* Stats bar */
        .vw-stats {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 1rem; margin: 3rem 0; padding: 2rem;
            background: var(--vw-card); border: 1px solid var(--vw-border); border-radius: 16px;
        }
        .vw-stat { text-align: center; }
        .vw-stat-num { font-size: 1.8rem; font-weight: 800; background: linear-gradient(135deg, var(--vw-cyan), var(--vw-purple)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .vw-stat-label { font-size: .7rem; color: var(--vw-muted); text-transform: uppercase; letter-spacing: 1px; margin-top: .2rem; }

        /* Category filter */
        .vw-filters {
            display: flex; flex-wrap: wrap; gap: .5rem; justify-content: center;
            margin-bottom: 3rem;
        }
        .vw-filter-btn {
            background: var(--vw-card); border: 1px solid var(--vw-border);
            color: var(--vw-muted); padding: .5rem 1rem; border-radius: 100px;
            font-size: .8rem; cursor: pointer; transition: all .2s; font-family: inherit;
        }
        .vw-filter-btn:hover, .vw-filter-btn.active {
            background: rgba(0,212,255,.1); border-color: var(--vw-cyan); color: #fff;
        }

        /* World cards */
        .vw-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-bottom: 4rem;
        }

        .vw-card {
            background: var(--vw-card);
            border: 1px solid var(--vw-border);
            border-radius: 16px;
            padding: 1.5rem;
            transition: all .3s;
            display: flex;
            flex-direction: column;
        }
        .vw-card:hover {
            border-color: rgba(0,212,255,.3);
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0,212,255,.06);
        }

        .vw-card-header {
            display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;
        }
        .vw-card-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem; flex-shrink: 0;
        }
        .vw-card-title { font-size: 1.1rem; font-weight: 700; line-height: 1.2; }
        .vw-card-tagline { font-size: .8rem; color: var(--vw-muted); margin-top: .2rem; }

        .vw-card-body { flex: 1; }

        .vw-card-features {
            list-style: none; padding: 0; margin: .8rem 0; display: flex; flex-wrap: wrap; gap: .4rem;
        }
        .vw-card-features li {
            background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.06);
            padding: .2rem .6rem; border-radius: 6px; font-size: .7rem; color: var(--vw-muted);
        }

        .vw-card-tech {
            display: flex; gap: .4rem; margin: .8rem 0; flex-wrap: wrap;
        }
        .vw-card-tech span {
            font-size: .65rem; padding: .15rem .5rem; border-radius: 4px;
            background: rgba(139,92,246,.15); color: #c4b5fd; font-weight: 600;
        }

        .vw-card-footer {
            display: flex; justify-content: space-between; align-items: center;
            margin-top: auto; padding-top: 1rem; border-top: 1px solid var(--vw-border);
        }

        .vw-card-rank {
            font-size: .7rem; padding: .2rem .6rem; border-radius: 6px;
            font-weight: 600;
        }

        .vw-enter-btn {
            background: linear-gradient(135deg, var(--vw-cyan), var(--vw-purple));
            color: #000; font-weight: 600; padding: .4rem 1rem; border-radius: 8px;
            font-size: .8rem; transition: transform .2s; display: inline-flex; align-items: center; gap: .4rem;
        }
        .vw-enter-btn:hover { transform: translateY(-1px); text-decoration: none; }

        /* Land section */
        .vw-land {
            background: var(--vw-card); border: 1px solid var(--vw-border);
            border-radius: 16px; padding: 2.5rem; margin-bottom: 4rem;
        }
        .vw-land h2 {
            font-size: 1.5rem; font-weight: 800; margin-bottom: .5rem;
            background: linear-gradient(135deg, var(--vw-gold), #f59e0b);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .vw-land-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 1rem; margin-top: 1.5rem;
        }
        .vw-land-type {
            background: rgba(255,255,255,0.02); border: 1px solid var(--vw-border);
            padding: 1rem; border-radius: 10px; text-align: center;
        }
        .vw-land-type-count { font-size: 1.5rem; font-weight: 800; }
        .vw-land-type-label { font-size: .7rem; color: var(--vw-muted); text-transform: uppercase; letter-spacing: 1px; }

        /* Footer */
        .vw-footer {
            border-top: 1px solid var(--vw-border); padding: 3rem 0;
            text-align: center; color: var(--vw-muted); font-size: .8rem;
        }

        @media (max-width: 640px) {
            .vw-grid { grid-template-columns: 1fr; }
            .vw-nav-links { display: none; }
            .vw-stats { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>
    <div class="vw-ambient"></div>

    <div class="vw-container">
        <!-- Nav -->
        <nav class="vw-nav">
            <a href="/" class="vw-logo"><span>MetaDome</span> Worlds</a>
            <div class="vw-nav-links">
                <a href="/">GoSiteMe</a>
                <a href="/metadome-landing">MetaDome</a>
                <a href="/passport">Passport</a>
                <a href="/game-lobby.php">Games</a>
            </div>
        </nav>

        <!-- Hero -->
        <div class="vw-hero">
            <h1><span class="grad"><?= count($worlds) ?> Virtual Worlds</span><br>One Browser</h1>
            <p>Enter chess arenas, concert halls, art galleries, DJ studios, sacred sanctuaries, and more. All in your browser. No headset required. No download. No tracking.</p>
        </div>

        <!-- Stats -->
        <div class="vw-stats">
            <div class="vw-stat">
                <div class="vw-stat-num"><?= count($worlds) ?></div>
                <div class="vw-stat-label">Worlds</div>
            </div>
            <div class="vw-stat">
                <div class="vw-stat-num"><?= $totalPlots ?></div>
                <div class="vw-stat-label">Land Plots</div>
            </div>
            <div class="vw-stat">
                <div class="vw-stat-num"><?= $chessStats['agents'] ?></div>
                <div class="vw-stat-label">AI Chess Agents</div>
            </div>
            <div class="vw-stat">
                <div class="vw-stat-num"><?= number_format($totalVisitors) ?></div>
                <div class="vw-stat-label">Total Visits</div>
            </div>
            <div class="vw-stat">
                <div class="vw-stat-num"><?= count($categories) ?></div>
                <div class="vw-stat-label">Categories</div>
            </div>
        </div>

        <!-- Category filters -->
        <div class="vw-filters">
            <button class="vw-filter-btn active" data-cat="all"><i class="fas fa-th"></i> All</button>
            <?php foreach ($categories as $key => $cat): ?>
            <button class="vw-filter-btn" data-cat="<?= $key ?>"><i class="fas <?= $cat['icon'] ?>"></i> <?= $cat['label'] ?></button>
            <?php endforeach; ?>
        </div>

        <!-- World cards -->
        <div class="vw-grid" id="worldGrid">
            <?php foreach ($worlds as $w): ?>
            <div class="vw-card" data-category="<?= htmlspecialchars($w['category']) ?>">
                <div class="vw-card-header">
                    <div class="vw-card-icon" style="background:<?= $w['color'] ?>15;color:<?= $w['color'] ?>;">
                        <i class="fas <?= $w['icon'] ?>"></i>
                    </div>
                    <div>
                        <div class="vw-card-title"><?= htmlspecialchars($w['name']) ?></div>
                        <div class="vw-card-tagline"><?= htmlspecialchars($w['tagline']) ?></div>
                    </div>
                </div>

                <div class="vw-card-body">
                    <ul class="vw-card-features">
                        <?php foreach ($w['features'] as $feat): ?>
                        <li><?= htmlspecialchars($feat) ?></li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="vw-card-tech">
                        <?php foreach ($w['tech'] as $t): ?>
                        <span><?= htmlspecialchars($t) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="vw-card-footer">
                    <?php if ($w['rank_tier'] === 0): ?>
                        <span class="vw-card-rank" style="background:rgba(52,211,153,.15);color:#34d399;">Open to All</span>
                    <?php elseif ($w['rank_tier'] <= 3): ?>
                        <span class="vw-card-rank" style="background:rgba(59,130,246,.15);color:#60a5fa;">Enlisted+</span>
                    <?php elseif ($w['rank_tier'] <= 5): ?>
                        <span class="vw-card-rank" style="background:rgba(251,191,36,.15);color:#fbbf24;">NCO+</span>
                    <?php else: ?>
                        <span class="vw-card-rank" style="background:rgba(248,113,113,.15);color:#f87171;">Officer+</span>
                    <?php endif; ?>
                    <a href="/vr/<?= htmlspecialchars($w['slug']) ?>/" class="vw-enter-btn"><i class="fas fa-play"></i> Enter</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Land plots section -->
        <div class="vw-land">
            <h2><i class="fas fa-map" style="margin-right:.5rem;"></i> Virtual Land — <?= $totalPlots ?> Plots</h2>
            <p style="color:var(--vw-muted);margin-bottom:.5rem;">Own land in the MetaDome. Build on it. Trade it. <?= $claimedPlots ?> of <?= $totalPlots ?> plots claimed.</p>

            <div class="vw-land-grid">
                <?php
                $typeColors = ['residential' => '#34d399', 'commercial' => '#60a5fa', 'park' => '#a3e635', 'arena' => '#f87171', 'landmark' => '#fbbf24'];
                foreach ($plotStats as $type => $count):
                    $color = $typeColors[$type] ?? '#888';
                ?>
                <div class="vw-land-type">
                    <div class="vw-land-type-count" style="color:<?= $color ?>;"><?= $count ?></div>
                    <div class="vw-land-type-label"><?= htmlspecialchars(ucfirst($type)) ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <div style="margin-top:1.5rem;text-align:center;">
                <a href="/land-market.php" class="vw-enter-btn" style="padding:.6rem 1.5rem;font-size:.9rem;"><i class="fas fa-map-marker-alt"></i> Explore Land Market</a>
                <a href="/wallet.php" class="vw-enter-btn" style="padding:.6rem 1.5rem;font-size:.9rem;margin-left:.5rem;background:linear-gradient(135deg,var(--vw-gold),#f59e0b);"><i class="fas fa-wallet"></i> GSM Wallet</a>
            </div>
        </div>

        <!-- Footer -->
        <footer class="vw-footer">
            <p>&copy; <?= date('Y') ?> GoSiteMe &mdash; MetaDome Virtual Worlds</p>
            <p style="margin-top:.5rem;"><a href="https://meta-dome.com">meta-dome.com</a> &middot; <a href="/game-lobby.php">Games Lobby</a> &middot; <a href="/passport">Get Your Passport</a></p>
        </footer>
    </div>

    <script>
    // Category filter
    document.querySelectorAll('.vw-filter-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.vw-filter-btn').forEach(function(b) { b.classList.remove('active'); });
            this.classList.add('active');
            var cat = this.getAttribute('data-cat');
            document.querySelectorAll('.vw-card').forEach(function(card) {
                if (cat === 'all' || card.getAttribute('data-category') === cat) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
    </script>
</body>
</html>
