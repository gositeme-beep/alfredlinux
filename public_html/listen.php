<?php
/**
 * Alfred Linux — Kingdom Music Player
 * "Jesus Christ The Light Our Universe" by Elyon Neshama & Commander Danny William Perez
 * 27 tracks — 13 songs × 2 versions + "All Honor To Your Name"
 *
 * Built by Alfred for the Kingdom of God Edition
 */
$year = date('Y');

$tracks = [
    ['num' => 1,  'file' => '01-Shema-Yisrael-A.mp3',       'title' => 'Shema Yisrael',          'ver' => 'A', 'scripture' => 'Deuteronomy 6:4'],
    ['num' => 2,  'file' => '02-Shema-Yisrael-B.mp3',       'title' => 'Shema Yisrael',          'ver' => 'B', 'scripture' => 'Deuteronomy 6:4'],
    ['num' => 3,  'file' => '03-Most-High-A.mp3',            'title' => 'Most High',              'ver' => 'A', 'scripture' => 'Psalm 91:1'],
    ['num' => 4,  'file' => '04-Most-High-B.mp3',            'title' => 'Most High',              'ver' => 'B', 'scripture' => 'Psalm 91:1'],
    ['num' => 5,  'file' => '05-Heavens-Declare-A.mp3',      'title' => 'The Heavens Declare',    'ver' => 'A', 'scripture' => 'Psalm 19:1'],
    ['num' => 6,  'file' => '06-Heavens-Declare-B.mp3',      'title' => 'The Heavens Declare',    'ver' => 'B', 'scripture' => 'Psalm 19:1'],
    ['num' => 7,  'file' => '07-Light-Of-The-World-A.mp3',   'title' => 'Light of the World',     'ver' => 'A', 'scripture' => 'John 8:12'],
    ['num' => 8,  'file' => '08-Light-Of-The-World-B.mp3',   'title' => 'Light of the World',     'ver' => 'B', 'scripture' => 'John 8:12'],
    ['num' => 9,  'file' => '09-Seraphim-A.mp3',             'title' => 'Seraphim',               'ver' => 'A', 'scripture' => 'Isaiah 6:2-3'],
    ['num' => 10, 'file' => '10-Seraphim-B.mp3',             'title' => 'Seraphim',               'ver' => 'B', 'scripture' => 'Isaiah 6:2-3'],
    ['num' => 11, 'file' => '11-Full-Of-Mercy-A.mp3',        'title' => 'Full of Mercy',          'ver' => 'A', 'scripture' => 'James 3:17'],
    ['num' => 12, 'file' => '12-Full-Of-Mercy-B.mp3',        'title' => 'Full of Mercy',          'ver' => 'B', 'scripture' => 'James 3:17'],
    ['num' => 13, 'file' => '13-Redeemer-A.mp3',             'title' => 'Redeemer',               'ver' => 'A', 'scripture' => 'Isaiah 44:6'],
    ['num' => 14, 'file' => '14-Redeemer-B.mp3',             'title' => 'Redeemer',               'ver' => 'B', 'scripture' => 'Isaiah 44:6'],
    ['num' => 15, 'file' => '15-Beloved-A.mp3',              'title' => 'Beloved',                'ver' => 'A', 'scripture' => 'Song of Solomon 6:3'],
    ['num' => 16, 'file' => '16-Beloved-B.mp3',              'title' => 'Beloved',                'ver' => 'B', 'scripture' => 'Song of Solomon 6:3'],
    ['num' => 17, 'file' => '17-Shofar-A.mp3',               'title' => 'Shofar',                 'ver' => 'A', 'scripture' => 'Joshua 6:20'],
    ['num' => 18, 'file' => '18-Shofar-B.mp3',               'title' => 'Shofar',                 'ver' => 'B', 'scripture' => 'Joshua 6:20'],
    ['num' => 19, 'file' => '19-Truth-Of-The-LORD-A.mp3',    'title' => 'Truth of the LORD',      'ver' => 'A', 'scripture' => 'Psalm 117:2'],
    ['num' => 20, 'file' => '20-Truth-Of-The-LORD-B.mp3',    'title' => 'Truth of the LORD',      'ver' => 'B', 'scripture' => 'Psalm 117:2'],
    ['num' => 21, 'file' => '21-Yeshua-A.mp3',               'title' => 'Yeshua',                 'ver' => 'A', 'scripture' => 'Acts 4:12'],
    ['num' => 22, 'file' => '22-Yeshua-B.mp3',               'title' => 'Yeshua',                 'ver' => 'B', 'scripture' => 'Acts 4:12'],
    ['num' => 23, 'file' => '23-Your-Mercy-A.mp3',           'title' => 'Your Mercy',             'ver' => 'A', 'scripture' => 'Lamentations 3:22-23'],
    ['num' => 24, 'file' => '24-Your-Mercy-B.mp3',           'title' => 'Your Mercy',             'ver' => 'B', 'scripture' => 'Lamentations 3:22-23'],
    ['num' => 25, 'file' => '25-Zion-A.mp3',                 'title' => 'Zion',                   'ver' => 'A', 'scripture' => 'Isaiah 60:14'],
    ['num' => 26, 'file' => '26-Zion-B.mp3',                 'title' => 'Zion',                   'ver' => 'B', 'scripture' => 'Isaiah 60:14'],
    ['num' => 27, 'file' => '27-All-Honor-To-Your-Name.mp3', 'title' => 'All Honor To Your Name', 'ver' => '',  'scripture' => 'Revelation 5:12'],
];

// Load all lyrics server-side
$lyricsMap = [
    '01'=>'01-Shema-Yisrael','02'=>'01-Shema-Yisrael',
    '03'=>'02-Most-High','04'=>'02-Most-High',
    '05'=>'03-Heavens-Declare','06'=>'03-Heavens-Declare',
    '07'=>'04-Light-Of-The-World','08'=>'04-Light-Of-The-World',
    '09'=>'05-Seraphim','10'=>'05-Seraphim',
    '11'=>'06-Full-Of-Mercy','12'=>'06-Full-Of-Mercy',
    '13'=>'07-Redeemer','14'=>'07-Redeemer',
    '15'=>'08-Beloved','16'=>'08-Beloved',
    '17'=>'09-Shofar','18'=>'09-Shofar',
    '19'=>'10-Truth-Of-The-LORD','20'=>'10-Truth-Of-The-LORD',
    '21'=>'11-Yeshua','22'=>'11-Yeshua',
    '23'=>'12-Your-Mercy','24'=>'12-Your-Mercy',
    '25'=>'13-Zion','26'=>'13-Zion',
    '27'=>'13-Zion',
];
$allLyrics = [];
foreach ($lyricsMap as $num => $file) {
    $path = __DIR__ . "/music/lyrics/{$file}.txt";
    if (file_exists($path) && !isset($allLyrics[$num])) {
        $raw = file_get_contents($path);
        $lines = explode("\n", $raw);
        $content = []; $skip = true;
        foreach ($lines as $line) {
            $t = trim($line);
            if ($skip && preg_match("/[\x{0590}-\x{05FF}]/u", $t)) $skip = false;
            if (!$skip) $content[] = rtrim($line, "\r");
        }
        $allLyrics[$num] = implode("\n", $content);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kingdom Music — Jesus Christ The Light Our Universe | Alfred Linux</title>
    <meta name="description" content="Listen to all 27 tracks of 'Jesus Christ The Light Our Universe' by Elyon Neshama — the worship album built into Alfred Linux 7.77 Kingdom of God Edition.">
    <meta property="og:title" content="Kingdom Music — Jesus Christ The Light Our Universe">
    <meta property="og:description" content="27-track Hebrew worship album built into Alfred Linux 7.77 Kingdom of God Edition. Listen now.">
    <meta property="og:type" content="music.album">
    <meta property="og:url" content="https://alfredlinux.com/music">
    <meta property="og:image" content="https://alfredlinux.com/music/cover-art-zion.png">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="canonical" href="https://alfredlinux.com/music">
    <link rel="icon" href="/favicon.ico">
    <link rel="stylesheet" href="/assets/fonts/inter/inter.css">
    <link rel="stylesheet" href="/assets/css/nav.css">
    <style>
        :root {
            --bg: #06060b;
            --bg2: #0a0a14;
            --surface: rgba(212,175,55,0.03);
            --gold: #d4af37;
            --gold-light: #f5d060;
            --gold-dark: #a68a2a;
            --gold-glow: rgba(212,175,55,0.15);
            --text: #e8e8e8;
            --muted: #8b8b9e;
            --dim: #555566;
            --playing: #d4af37;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        html { scroll-behavior:smooth; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ── Animated background ── */
        .bg-canvas {
            position: fixed; inset: 0; z-index: 0; pointer-events: none;
            background:
                radial-gradient(ellipse 80% 60% at 50% 0%, rgba(212,175,55,0.06) 0%, transparent 60%),
                radial-gradient(ellipse 60% 40% at 20% 80%, rgba(212,175,55,0.03) 0%, transparent 50%),
                radial-gradient(ellipse 50% 30% at 80% 60%, rgba(180,140,20,0.04) 0%, transparent 50%);
        }
        .bg-canvas::after {
            content: ''; position: absolute; inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='30' cy='30' r='0.5' fill='%23d4af37' opacity='0.08'/%3E%3C/svg%3E");
            animation: starDrift 120s linear infinite;
        }
        @keyframes starDrift { from { transform: translateY(0); } to { transform: translateY(-60px); } }

        /* ── Hero ── */
        .hero {
            position: relative; z-index: 1;
            text-align: center;
            padding: 4rem 2rem 2rem;
        }
        .album-art {
            width: 280px; height: 420px;
            border-radius: 16px;
            margin: 0 auto 2rem;
            box-shadow: 0 20px 60px rgba(212,175,55,0.2), 0 0 100px rgba(212,175,55,0.08);
            overflow: hidden;
            position: relative;
            transition: transform 0.6s ease;
        }
        .album-art:hover { transform: scale(1.03); }
        .album-art img { width:100%; height:100%; object-fit:contain; }
        .album-art.playing {
            animation: vinylPulse 3s ease-in-out infinite;
        }
        @keyframes vinylPulse {
            0%, 100% { box-shadow: 0 20px 60px rgba(212,175,55,0.3), 0 0 100px rgba(212,175,55,0.1); }
            50% { box-shadow: 0 20px 80px rgba(212,175,55,0.5), 0 0 140px rgba(212,175,55,0.2); }
        }
        .album-title {
            font-size: 2rem; font-weight: 800;
            background: linear-gradient(135deg, var(--gold-light), var(--gold), var(--gold-dark));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.4rem;
        }
        .album-artist {
            font-size: 1rem; color: var(--muted);
            margin-bottom: 0.3rem;
        }
        .album-meta {
            font-size: 0.82rem; color: var(--dim);
            letter-spacing: 0.05em;
        }

        /* ── Player Controls ── */
        .player {
            position: relative; z-index: 1;
            max-width: 720px; margin: 0 auto;
            padding: 0 1.5rem;
        }
        .now-playing {
            background: linear-gradient(135deg, rgba(212,175,55,0.08), rgba(212,175,55,0.02));
            border: 1px solid rgba(212,175,55,0.15);
            border-radius: 16px;
            padding: 1.5rem 2rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .np-title {
            font-size: 1.3rem; font-weight: 700; color: var(--gold-light);
            margin-bottom: 0.3rem;
            transition: all 0.3s;
        }
        .np-scripture {
            font-style: italic; color: var(--gold-dark); font-size: 0.85rem;
            margin-bottom: 1rem;
        }

        /* Progress bar */
        .progress-container {
            width: 100%; height: 6px;
            background: rgba(255,255,255,0.06);
            border-radius: 3px;
            cursor: pointer;
            margin-bottom: 0.6rem;
            position: relative;
            overflow: visible;
        }
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--gold-dark), var(--gold), var(--gold-light));
            border-radius: 3px;
            width: 0%;
            position: relative;
            transition: width 0.1s linear;
        }
        .progress-bar::after {
            content: '';
            position: absolute; right: -6px; top: -3px;
            width: 12px; height: 12px;
            background: var(--gold-light);
            border-radius: 50%;
            box-shadow: 0 0 10px var(--gold);
            opacity: 0;
            transition: opacity 0.2s;
        }
        .progress-container:hover .progress-bar::after { opacity: 1; }

        .time-display {
            display: flex; justify-content: space-between;
            font-size: 0.75rem; color: var(--dim);
            margin-bottom: 1.2rem;
        }

        /* Transport buttons */
        .controls {
            display: flex; align-items: center; justify-content: center; gap: 1.5rem;
        }
        .ctrl-btn {
            background: none; border: none; cursor: pointer;
            color: var(--muted); transition: all 0.2s;
            display: flex; align-items: center; justify-content: center;
        }
        .ctrl-btn:hover { color: var(--gold); transform: scale(1.1); }
        .ctrl-btn svg { width: 28px; height: 28px; fill: currentColor; }
        .ctrl-btn.play-btn {
            width: 64px; height: 64px;
            background: linear-gradient(135deg, var(--gold), var(--gold-dark));
            border-radius: 50%;
            color: #000;
            box-shadow: 0 4px 20px rgba(212,175,55,0.4);
            transition: all 0.3s;
        }
        .ctrl-btn.play-btn:hover {
            transform: scale(1.08);
            box-shadow: 0 6px 30px rgba(212,175,55,0.6);
        }
        .ctrl-btn.play-btn svg { width: 32px; height: 32px; }

        /* Volume */
        .volume-section {
            display: flex; align-items: center; justify-content: center; gap: 0.6rem;
            margin-top: 1rem;
        }
        .volume-section svg { width: 18px; height: 18px; fill: var(--dim); }
        .volume-slider {
            -webkit-appearance: none; appearance: none;
            width: 100px; height: 4px;
            background: rgba(255,255,255,0.1);
            border-radius: 2px; outline: none;
        }
        .volume-slider::-webkit-slider-thumb {
            -webkit-appearance: none; appearance: none;
            width: 14px; height: 14px;
            background: var(--gold);
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 0 6px rgba(212,175,55,0.4);
        }

        /* ── Track List ── */
        .tracklist {
            position: relative; z-index: 1;
            max-width: 720px; margin: 2rem auto 0;
            padding: 0 1.5rem 4rem;
        }
        .tracklist-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 1rem; padding: 0 0.5rem;
        }
        .tracklist-header h3 {
            font-size: 1rem; color: var(--gold-dark); font-weight: 600;
        }
        .tracklist-header span {
            font-size: 0.8rem; color: var(--dim);
        }

        .track {
            display: flex; align-items: center; gap: 1rem;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }
        .track:hover {
            background: rgba(212,175,55,0.06);
        }
        .track.active {
            background: linear-gradient(135deg, rgba(212,175,55,0.1), rgba(212,175,55,0.04));
            border-left: 3px solid var(--gold);
        }
        .track.active .track-title { color: var(--gold-light); }
        .track.active .track-num { color: var(--gold); }

        .track-num {
            width: 28px; text-align: center;
            font-size: 0.85rem; color: var(--dim);
            font-weight: 500;
            flex-shrink: 0;
        }
        .track.active .track-num .num-text { display: none; }
        .track.active .track-num .eq-bars { display: flex; }
        .track .track-num .eq-bars { display: none; }

        /* Equalizer bars animation */
        .eq-bars {
            align-items: flex-end; justify-content: center;
            gap: 2px; height: 16px;
        }
        .eq-bar {
            width: 3px; background: var(--gold);
            border-radius: 1px;
            animation: eqBounce 0.8s ease-in-out infinite;
        }
        .eq-bar:nth-child(1) { height: 8px; animation-delay: 0s; }
        .eq-bar:nth-child(2) { height: 14px; animation-delay: 0.15s; }
        .eq-bar:nth-child(3) { height: 6px; animation-delay: 0.3s; }
        .eq-bar:nth-child(4) { height: 12px; animation-delay: 0.1s; }
        @keyframes eqBounce {
            0%, 100% { transform: scaleY(0.4); }
            50% { transform: scaleY(1); }
        }
        .track.paused .eq-bar { animation-play-state: paused; }

        .track-info { flex: 1; min-width: 0; }
        .track-title {
            font-size: 0.95rem; font-weight: 600; color: var(--text);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .track-sub {
            font-size: 0.78rem; color: var(--dim);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .track-ver {
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: 10px;
            background: rgba(212,175,55,0.1);
            color: var(--gold-dark);
            font-weight: 600;
            flex-shrink: 0;
        }
        .track-ver.crown {
            background: linear-gradient(135deg, rgba(212,175,55,0.2), rgba(212,175,55,0.08));
            color: var(--gold);
        }

        /* ── Visualizer ── */
        .visualizer-container {
            width: 100%; height: 60px;
            margin: 1rem 0;
            border-radius: 8px;
            overflow: hidden;
            background: rgba(0,0,0,0.3);
        }
        #visualizer {
            width: 100%; height: 100%;
            display: block;
        }

        /* ── Footer ── */
        .music-footer {
            text-align: center;
            padding: 2rem;
            color: var(--dim);
            font-size: 0.82rem;
            position: relative; z-index: 1;
        }
        .music-footer a { color: var(--gold-dark); text-decoration: none; }
        .music-footer a:hover { color: var(--gold); }

        /* ── Responsive ── */
        @media (max-width: 600px) {
            .album-art { width: 200px; height: 300px; }
            .album-title { font-size: 1.4rem; }
            .controls { gap: 1rem; }
            .ctrl-btn.play-btn { width: 56px; height: 56px; }
        }

        /* ── Floating particles ── */
        .particle {
            position: fixed; z-index: 0;
            width: 2px; height: 2px;
            background: var(--gold);
            border-radius: 50%;
            opacity: 0;
            pointer-events: none;
        }
        /* ── Lyrics Display (always visible) ── */
        .lyrics-section {
            position: relative; z-index: 1;
            max-width: 720px; margin: 1.5rem auto 0;
            padding: 0 1.5rem;
        }
        .lyrics-card {
            background: linear-gradient(135deg, rgba(212,175,55,0.06), rgba(212,175,55,0.02));
            border: 1px solid rgba(212,175,55,0.15);
            border-radius: 16px;
            padding: 1.5rem 2rem;
            min-height: 160px;
        }
        .lyrics-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 1rem; padding-bottom: 0.6rem;
            border-bottom: 1px solid rgba(212,175,55,0.1);
        }
        .lyrics-song-name { font-size: 1.1rem; font-weight: 700; color: var(--gold-light); }
        .lyrics-badge { font-size: 0.65rem; padding: 3px 10px; border-radius: 12px; background: rgba(212,175,55,0.12); color: var(--gold-dark); font-weight: 600; }
        .lyrics-scroll {
            max-height: 350px; overflow-y: auto; scroll-behavior: smooth;
            padding-right: 0.5rem;
        }
        .lyrics-scroll::-webkit-scrollbar { width: 4px; }
        .lyrics-scroll::-webkit-scrollbar-track { background: transparent; }
        .lyrics-scroll::-webkit-scrollbar-thumb { background: rgba(212,175,55,0.3); border-radius: 2px; }
        .lyric-verse { margin-bottom: 1.2rem; padding: 0.8rem 1rem; border-radius: 10px; transition: background 0.3s; }
        .lyric-verse:hover { background: rgba(212,175,55,0.05); }
        .lyric-hebrew { font-size: 1.5rem; font-weight: 600; color: var(--gold-light); direction: rtl; text-align: right; margin-bottom: 0.3rem; line-height: 1.6; }
        .lyric-translit { font-size: 1rem; color: var(--gold); font-style: italic; margin-bottom: 0.2rem; line-height: 1.5; }
        .lyric-english { font-size: 0.85rem; color: var(--muted); line-height: 1.5; }
        .lyrics-prompt { text-align: center; color: var(--dim); font-style: italic; padding: 2.5rem 0; font-size: 1.05rem; }
        .lyrics-prompt .note-icon { font-size: 2rem; display: block; margin-bottom: 0.8rem; }
        .lyrics-footer { text-align: center; margin-top: 0.8rem; padding-top: 0.6rem; border-top: 1px solid rgba(212,175,55,0.08); font-size: 0.72rem; color: var(--dim); }
        .lyrics-footer span { color: var(--gold-dark); }
    </style>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/img/favicon/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/img/favicon/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#D4AF37">
</head>
<body>

<div class="bg-canvas"></div>

<?php $currentPage = 'music'; include __DIR__ . '/includes/nav.php'; ?>

<!-- Hero -->
<div class="hero">
    <div class="album-art" id="albumArt">
        <img src="/music/cover-art-zion.png" alt="Jesus Christ The Light Our Universe — Album Cover" id="coverImg">
    </div>
    <div class="album-title">Jesus Christ The Light Our Universe</div>
    <div class="album-artist">Elyon Neshama &amp; Commander Danny William Perez</div>
    <div class="album-meta">27 TRACKS &middot; KINGDOM OF GOD EDITION &middot; ALFRED LINUX 7.77</div>
</div>

<!-- Player -->
<div class="player">
    <div class="now-playing">
        <div class="np-title" id="npTitle">Select a track to begin</div>
        <div class="np-scripture" id="npScripture">&ldquo;Make a joyful noise unto the LORD, all the earth.&rdquo; &mdash; Psalm 100:1</div>

        <!-- Visualizer -->
        <div class="visualizer-container">
            <canvas id="visualizer"></canvas>
        </div>

        <!-- Progress -->
        <div class="progress-container" id="progressContainer">
            <div class="progress-bar" id="progressBar"></div>
        </div>
        <div class="time-display">
            <span id="currentTime">0:00</span>
            <span id="totalTime">0:00</span>
        </div>

        <!-- Transport -->
        <div class="controls">
            <button class="ctrl-btn" id="btnShuffle" title="Shuffle" onclick="toggleShuffle()">
                <svg viewBox="0 0 24 24"><path d="M10.59 9.17L5.41 4 4 5.41l5.17 5.17 1.42-1.41zM14.5 4l2.04 2.04L4 18.59 5.41 20 17.96 7.46 20 9.5V4h-5.5zm.33 9.41l-1.41 1.41 3.13 3.13L14.5 20H20v-5.5l-2.04 2.04-3.13-3.13z"/></svg>
            </button>
            <button class="ctrl-btn" title="Previous" onclick="prevTrack()">
                <svg viewBox="0 0 24 24"><path d="M6 6h2v12H6zm3.5 6l8.5 6V6z"/></svg>
            </button>
            <button class="ctrl-btn play-btn" id="btnPlay" title="Play" onclick="togglePlay()">
                <svg viewBox="0 0 24 24" id="playIcon"><path d="M8 5v14l11-7z"/></svg>
            </button>
            <button class="ctrl-btn" title="Next" onclick="nextTrack()">
                <svg viewBox="0 0 24 24"><path d="M6 18l8.5-6L6 6v12zM16 6v12h2V6h-2z"/></svg>
            </button>
            <button class="ctrl-btn" id="btnRepeat" title="Repeat" onclick="toggleRepeat()">
                <svg viewBox="0 0 24 24"><path d="M7 7h10v3l4-4-4-4v3H5v6h2V7zm10 10H7v-3l-4 4 4 4v-3h12v-6h-2v4z"/></svg>
            </button>
        </div>

        <!-- Volume -->
        <div class="volume-section">
            <svg viewBox="0 0 24 24"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02z"/></svg>
            <input type="range" class="volume-slider" id="volumeSlider" min="0" max="100" value="80" oninput="setVolume(this.value)">
        </div>
    </div>
</div>

<!-- Track List -->
<div class="lyrics-section">
    <div class="lyrics-card">
        <div class="lyrics-header">
            <span class="lyrics-song-name" id="lyricsSongName">♪ Sing Along</span>
            <span class="lyrics-badge">HEBREW · TRANSLITERATION · ENGLISH</span>
        </div>
        <div class="lyrics-scroll" id="lyricsScroll">
            <div class="lyrics-prompt">
                <span class="note-icon">🎵</span>
                Press play on any track to see the lyrics<br>
                Follow the Hebrew, sing the transliteration, understand the English
            </div>
        </div>
        <div class="lyrics-footer">
            <span>♪</span> "Jesus Christ The Light Our Universe" — Elyon Neshama <span>♪</span>
        </div>
    </div>
</div>
<div class="tracklist">
    <div class="tracklist-header">
        <h3>✝ All 27 Tracks</h3>
        <span>Elyon Neshama &middot; <?= $year ?></span>
    </div>
    <?php foreach ($tracks as $t): ?>
    <div class="track" data-index="<?= $t['num'] - 1 ?>" onclick="playTrack(<?= $t['num'] - 1 ?>)">
        <div class="track-num">
            <span class="num-text"><?= $t['num'] ?></span>
            <div class="eq-bars">
                <div class="eq-bar"></div><div class="eq-bar"></div><div class="eq-bar"></div><div class="eq-bar"></div>
            </div>
        </div>
        <div class="track-info">
            <div class="track-title"><?= htmlspecialchars($t['title']) ?><?= $t['ver'] ? " ({$t['ver']})" : '' ?></div>
            <div class="track-sub"><?= htmlspecialchars($t['scripture']) ?></div>
        </div>
        <?php if ($t['ver']): ?>
            <span class="track-ver"><?= $t['ver'] ?></span>
        <?php else: ?>
            <span class="track-ver crown">✝</span>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>

<!-- Footer -->
<div class="music-footer">
    <p style="color:var(--gold-dark);font-style:italic;margin-bottom:0.5rem;">
        &ldquo;Sing unto the LORD a new song; sing unto the LORD, all the earth.&rdquo; &mdash; Psalm 96:1 (AKJV)
    </p>
    <p style="font-style:italic;color:#94a3b8;font-size:.85rem;margin:0.5rem 0;">&ldquo;The grass withereth, the flower fadeth: but the word of our God shall stand for ever.&rdquo; &mdash; <a href="https://gositeme.com/bible/read/isaiah/40" style="color:#facc15;text-decoration:none;">Isaiah 40:8</a> (AKJV)</p>
    <p>&copy; <?= $year ?> <a href="https://gositeme.com">GoSiteMe Inc.</a> &mdash; Alfred Linux 7.77 &middot; Kingdom of God Edition</p>
    <p style="margin-top:0.5rem;"><a href="/">← Back to Alfred Linux</a> &middot; <a href="/download">Download v7.77</a></p>
</div>

<?php include __DIR__ . "/includes/omahon-seal.php"; ?>
<?php include __DIR__ . "/includes/shabbat-banner.php"; ?>

<audio id="audioPlayer" preload="none"></audio>

<script>
// ──────────────────────────────────────────────
// KINGDOM MUSIC PLAYER — Jesus Christ The Light Our Universe
// ──────────────────────────────────────────────

const tracks = <?= json_encode(array_map(function($t) {
    return [
        'file'      => '/music/' . $t['file'],
        'title'     => $t['title'] . ($t['ver'] ? " ({$t['ver']})" : ''),
        'scripture' => $t['scripture'],
        'ver'       => $t['ver'],
    ];
}, $tracks), JSON_UNESCAPED_SLASHES); ?>;

const allLyrics = <?= json_encode($allLyrics, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

const audio = document.getElementById('audioPlayer');
const progressBar = document.getElementById('progressBar');
const progressContainer = document.getElementById('progressContainer');
const npTitle = document.getElementById('npTitle');
const npScripture = document.getElementById('npScripture');
const playIcon = document.getElementById('playIcon');
const albumArt = document.getElementById('albumArt');
const currentTimeEl = document.getElementById('currentTime');
const totalTimeEl = document.getElementById('totalTime');

let currentIndex = -1;
let isPlaying = false;
let shuffleOn = false;
let repeatMode = 0; // 0=off, 1=all, 2=one
let shuffleQueue = [];

// Audio context for visualizer
let audioCtx, analyser, source, dataArray, bufferLength;
let visualizerInitialized = false;
const canvas = document.getElementById('visualizer');
const ctx = canvas.getContext('2d');

function initVisualizer() {
    if (visualizerInitialized) return;
    audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    analyser = audioCtx.createAnalyser();
    source = audioCtx.createMediaElementSource(audio);
    source.connect(analyser);
    analyser.connect(audioCtx.destination);
    analyser.fftSize = 128;
    bufferLength = analyser.frequencyBinCount;
    dataArray = new Uint8Array(bufferLength);
    visualizerInitialized = true;
    drawVisualizer();
}

function drawVisualizer() {
    requestAnimationFrame(drawVisualizer);
    const w = canvas.width = canvas.offsetWidth * 2;
    const h = canvas.height = canvas.offsetHeight * 2;
    ctx.clearRect(0, 0, w, h);

    if (!analyser) return;
    analyser.getByteFrequencyData(dataArray);

    const barWidth = (w / bufferLength) * 1.2;
    let x = 0;
    for (let i = 0; i < bufferLength; i++) {
        const v = dataArray[i] / 255;
        const barH = v * h * 0.9;

        // Golden gradient based on frequency
        const hue = 42 + (i / bufferLength) * 10;
        const sat = 70 + v * 30;
        const light = 30 + v * 40;
        ctx.fillStyle = `hsla(${hue}, ${sat}%, ${light}%, ${0.6 + v * 0.4})`;

        // Draw mirrored bars
        const radius = Math.min(barWidth / 2, 3);
        const y = (h - barH) / 2;
        roundRect(ctx, x, y, barWidth - 1, barH, radius);

        x += barWidth + 1;
    }
}

function roundRect(c, x, y, w, h, r) {
    c.beginPath();
    c.moveTo(x + r, y);
    c.lineTo(x + w - r, y);
    c.quadraticCurveTo(x + w, y, x + w, y + r);
    c.lineTo(x + w, y + h - r);
    c.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
    c.lineTo(x + r, y + h);
    c.quadraticCurveTo(x, y + h, x, y + h - r);
    c.lineTo(x, y + r);
    c.quadraticCurveTo(x, y, x + r, y);
    c.fill();
}

function showLyrics(idx) {
    const num = String(idx + 1).padStart(2, '0');
    const raw = allLyrics[num] || '';
    const songName = document.getElementById('lyricsSongName');
    const scroll = document.getElementById('lyricsScroll');
    songName.textContent = '♪ ' + tracks[idx].title;
    if (!raw.trim()) {
        scroll.innerHTML = '<div class="lyrics-prompt"><span class="note-icon">🎵</span>Instrumental — feel the Spirit</div>';
        return;
    }
    const lines = raw.split('\n');
    let html = '', verse = {heb:'',trans:'',eng:''};
    function flush() {
        if (!verse.heb && !verse.trans && !verse.eng) return;
        html += '<div class="lyric-verse">';
        if (verse.heb) html += '<div class="lyric-hebrew">' + verse.heb + '</div>';
        if (verse.trans) html += '<div class="lyric-translit">' + verse.trans + '</div>';
        if (verse.eng) html += '<div class="lyric-english">' + verse.eng + '</div>';
        html += '</div>';
        verse = {heb:'',trans:'',eng:''};
    }
    for (const line of lines) {
        const t = line.trim();
        if (!t) { flush(); continue; }
        if (/→/.test(t)) verse.eng = t;
        else if (/[\u0590-\u05FF]/.test(t)) verse.heb = t;
        else verse.trans = t;
    }
    flush();
    scroll.innerHTML = html;
    scroll.scrollTop = 0;
}

function playTrack(index) {
    if (index < 0 || index >= tracks.length) return;

    // Remove active from all tracks
    document.querySelectorAll('.track').forEach(el => el.classList.remove('active', 'paused'));

    currentIndex = index;
    const track = tracks[index];

    audio.src = track.file;
    audio.load();

    // Init visualizer on first user interaction
    if (!visualizerInitialized) initVisualizer();
    if (audioCtx && audioCtx.state === 'suspended') audioCtx.resume();

    audio.play().then(() => {
        isPlaying = true;
        updatePlayButton();
        updateNowPlaying(track);
        highlightTrack(index);
        albumArt.classList.add('playing');

        // Spawn celebration particles
        spawnParticles();
        showLyrics(index);
    }).catch(() => {});
}

function togglePlay() {
    if (currentIndex === -1) { playTrack(0); return; }
    if (isPlaying) {
        audio.pause();
        isPlaying = false;
        albumArt.classList.remove('playing');
        document.querySelector('.track.active')?.classList.add('paused');
    } else {
        if (audioCtx && audioCtx.state === 'suspended') audioCtx.resume();
        audio.play();
        isPlaying = true;
        albumArt.classList.add('playing');
        document.querySelector('.track.active')?.classList.remove('paused');
    }
    updatePlayButton();
}

function nextTrack() {
    if (shuffleOn) {
        if (shuffleQueue.length === 0) buildShuffleQueue();
        playTrack(shuffleQueue.pop());
    } else {
        playTrack((currentIndex + 1) % tracks.length);
    }
}

function prevTrack() {
    if (audio.currentTime > 3) { audio.currentTime = 0; return; }
    playTrack((currentIndex - 1 + tracks.length) % tracks.length);
}

function toggleShuffle() {
    shuffleOn = !shuffleOn;
    document.getElementById('btnShuffle').style.color = shuffleOn ? 'var(--gold)' : '';
    if (shuffleOn) buildShuffleQueue();
}

function toggleRepeat() {
    repeatMode = (repeatMode + 1) % 3;
    const btn = document.getElementById('btnRepeat');
    btn.style.color = repeatMode > 0 ? 'var(--gold)' : '';
    btn.title = ['Repeat Off', 'Repeat All', 'Repeat One'][repeatMode];
}

function buildShuffleQueue() {
    shuffleQueue = Array.from({length: tracks.length}, (_, i) => i)
        .filter(i => i !== currentIndex)
        .sort(() => Math.random() - 0.5);
}

function setVolume(val) {
    audio.volume = val / 100;
}

function updatePlayButton() {
    playIcon.innerHTML = isPlaying
        ? '<path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>'
        : '<path d="M8 5v14l11-7z"/>';
}

function updateNowPlaying(track) {
    npTitle.textContent = track.title;
    npScripture.textContent = track.scripture;
}

function highlightTrack(index) {
    document.querySelectorAll('.track').forEach(el => el.classList.remove('active', 'paused'));
    const el = document.querySelector(`.track[data-index="${index}"]`);
    if (el) { el.classList.add('active'); el.scrollIntoView({behavior:'smooth', block:'nearest'}); }
}

function formatTime(s) {
    if (isNaN(s)) return '0:00';
    const m = Math.floor(s / 60);
    const sec = Math.floor(s % 60);
    return m + ':' + (sec < 10 ? '0' : '') + sec;
}

// Progress updates
audio.addEventListener('timeupdate', () => {
    if (audio.duration) {
        progressBar.style.width = (audio.currentTime / audio.duration * 100) + '%';
        currentTimeEl.textContent = formatTime(audio.currentTime);
    }
});

audio.addEventListener('loadedmetadata', () => {
    totalTimeEl.textContent = formatTime(audio.duration);
});

audio.addEventListener('ended', () => {
    if (repeatMode === 2) { audio.currentTime = 0; audio.play(); return; }
    if (repeatMode === 1 || currentIndex < tracks.length - 1) nextTrack();
    else { isPlaying = false; updatePlayButton(); albumArt.classList.remove('playing'); }
});

// Click to seek
progressContainer.addEventListener('click', (e) => {
    if (!audio.duration) return;
    const rect = progressContainer.getBoundingClientRect();
    audio.currentTime = ((e.clientX - rect.left) / rect.width) * audio.duration;
});

// Volume init
audio.volume = 0.8;

// ── Particles ──
function spawnParticles() {
    for (let i = 0; i < 8; i++) {
        const p = document.createElement('div');
        p.className = 'particle';
        p.style.left = (20 + Math.random() * 60) + 'vw';
        p.style.top = (30 + Math.random() * 40) + 'vh';
        p.style.width = (1 + Math.random() * 3) + 'px';
        p.style.height = p.style.width;
        document.body.appendChild(p);

        const dur = 2000 + Math.random() * 3000;
        p.animate([
            { opacity: 0, transform: 'translateY(0) scale(0)' },
            { opacity: 0.6, transform: `translateY(-${40 + Math.random()*60}px) scale(1)` },
            { opacity: 0, transform: `translateY(-${100 + Math.random()*80}px) scale(0.5)` }
        ], { duration: dur, easing: 'ease-out' });

        setTimeout(() => p.remove(), dur);
    }
}

// ── Keyboard shortcuts ──
document.addEventListener('keydown', (e) => {
    if (e.target.tagName === 'INPUT') return;
    switch(e.code) {
        case 'Space': e.preventDefault(); togglePlay(); break;
        case 'ArrowRight': nextTrack(); break;
        case 'ArrowLeft': prevTrack(); break;
        case 'ArrowUp': e.preventDefault(); audio.volume = Math.min(1, audio.volume + 0.05); document.getElementById('volumeSlider').value = audio.volume * 100; break;
        case 'ArrowDown': e.preventDefault(); audio.volume = Math.max(0, audio.volume - 0.05); document.getElementById('volumeSlider').value = audio.volume * 100; break;
    }
});

// Media Session API for OS-level controls
if ('mediaSession' in navigator) {
    audio.addEventListener('play', () => {
        navigator.mediaSession.metadata = new MediaMetadata({
            title: tracks[currentIndex]?.title || 'Kingdom Music',
            artist: 'Elyon Neshama',
            album: 'Jesus Christ The Light Our Universe',
            artwork: [{ src: '/music/cover-art-zion.png', sizes: '512x512', type: 'image/png' }]
        });
    });
    navigator.mediaSession.setActionHandler('play', togglePlay);
    navigator.mediaSession.setActionHandler('pause', togglePlay);
    navigator.mediaSession.setActionHandler('previoustrack', prevTrack);
    navigator.mediaSession.setActionHandler('nexttrack', nextTrack);
}
</script>

<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "MusicAlbum",
    "name": "Jesus Christ The Light Our Universe",
    "byArtist": {"@type": "MusicGroup", "name": "Elyon Neshama"},
    "numTracks": 27,
    "genre": "Hebrew Worship",
    "inLanguage": "he",
    "publisher": {"@type": "Organization", "name": "GoSiteMe Inc.", "url": "https://gositeme.com"},
    "track": [
        <?php
        $jsonTracks = [];
        foreach ($tracks as $t) {
            $jsonTracks[] = json_encode([
                '@type' => 'MusicRecording',
                'name' => $t['title'] . ($t['ver'] ? " ({$t['ver']})" : ''),
                'position' => $t['num'],
                'url' => "https://alfredlinux.com/music/{$t['file']}"
            ], JSON_UNESCAPED_SLASHES);
        }
        echo implode(",\n        ", $jsonTracks);
        ?>
    ]
}
</script>

</body>
</html>
