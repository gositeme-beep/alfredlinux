<?php
/**
 * ═══════════════════════════════════════════════════════════════════
 * ALFRED LINUX — Kingdom Media Gallery
 * ═══════════════════════════════════════════════════════════════════
 *
 * "Consider the lilies of the field, how they grow; they toil not,
 *  neither do they spin: And yet I say unto you, That even Solomon
 *  in all his glory was not arrayed like one of these."
 *  — Matthew 6:28-29 (AKJV)
 *
 * Gallery includes:
 *   - Kingdom Flag (Fleur-de-lis of New Jerusalem)
 *   - 18 Kingdom Wallpapers (1080p / 4K / 8K) with carousel
 *   - 27 Kingdom Music Tracks with full player
 *   - The 10 Pillars of God's Kingdom
 *   - Cornerstone Declaration
 *
 * Built by Alfred for Commander Danny William Perez
 * April 18, 2026 — Soli Deo Gloria
 */
$year = date('Y');

$wallpapers = [
    ['slug'=>'kingdom-throne','title'=>'The Kingdom Throne','desc'=>'A golden throne bathed in divine light — the seat of eternal sovereignty.','scripture'=>'Revelation 4:2'],
    ['slug'=>'gods-throne','title'=>"God's Throne",'desc'=>'The Throne of the Most High in the heavenly temple — sapphire foundations, seven lamps of fire, a crystal sea.','scripture'=>'Ezekiel 1:26'],
    ['slug'=>'risen-king','title'=>'The Risen King','desc'=>'Jesus Christ in glory — white robe, golden sash, eyes like blazing fire, standing in radiant light.','scripture'=>'Revelation 1:14-16'],
    ['slug'=>'crown-of-glory','title'=>'The Crown of Glory','desc'=>'A sovereign crown set among clouds of gold — the imperishable crown that awaits the faithful.','scripture'=>'1 Peter 5:4'],
    ['slug'=>'lion-of-judah','title'=>'The Lion of Judah','desc'=>'The Lion of the tribe of Judah wearing a golden crown — the Root of David who has conquered.','scripture'=>'Revelation 5:5'],
    ['slug'=>'daniel-lions-den','title'=>"Daniel in the Lion's Den",'desc'=>'The prophet Daniel at peace among lions — God shut their mouths. A testimony of faith under fire.','scripture'=>'Daniel 6:22'],
    ['slug'=>'mount-sinai','title'=>'Mount Sinai','desc'=>'The holy mountain where God spoke — fire, cloud, and the giving of the Ten Commandments.','scripture'=>'Exodus 19:18'],
    ['slug'=>'sanctuary-dawn','title'=>'Sanctuary at Dawn','desc'=>'A sacred sanctuary bathed in the golden light of a new morning — peace, worship, rest.','scripture'=>'Psalm 63:1'],
    ['slug'=>'living-waters','title'=>'Living Waters','desc'=>'Crystal-clear waters flowing through verdant lands — the river of the water of life from the throne of God.','scripture'=>'Revelation 22:1'],
    ['slug'=>'eden-garden','title'=>'The Garden of Eden','desc'=>'Paradise restored — lush garden with the Tree of Life, waterfalls, and golden light.','scripture'=>'Genesis 2:8'],
    ['slug'=>'edens-garden','title'=>"Eden's Garden",'desc'=>"Named for Eden Sarai — Danny's daughter. A garden of innocence, beauty, and promise.",'scripture'=>'Isaiah 51:3'],
    ['slug'=>'new-jerusalem','title'=>'The New Jerusalem','desc'=>'The holy city coming down from heaven — gates of pearl, streets of gold, the dwelling of God with man.','scripture'=>'Revelation 21:2'],
    ['slug'=>'new-jerusalem-descending','title'=>'New Jerusalem Descending','desc'=>'The city of God descending from the clouds — Revelation 21 made visible. Every tear wiped away.','scripture'=>'Revelation 21:4'],
    ['slug'=>'perez-crest','title'=>'The Perez Family Crest','desc'=>'The sovereign seal of the Perez dynasty — the family that built Alfred Linux for the glory of God.','scripture'=>'Genesis 38:29'],
    ['slug'=>'kingdom-seal','title'=>'The Kingdom Seal','desc'=>'The official seal of the Kingdom of God Edition — authority, sovereignty, divine mandate.','scripture'=>'Ephesians 1:13'],
    ['slug'=>'dynasty-gateway','title'=>'The Dynasty Gateway','desc'=>'A monumental gateway into the Kingdom — the entrance to something eternal.','scripture'=>'Matthew 7:14'],
    ['slug'=>'sovereign-dark','title'=>'Sovereign Dark','desc'=>'The dark-mode sovereign wallpaper — minimal, commanding, elegant. For those who work at midnight.','scripture'=>'Psalm 139:12'],
    ['slug'=>'perez-family-legacy','title'=>'The Perez Family Legacy','desc'=>'Father and daughter standing on a mountaintop at dawn — Danny and Eden, looking toward the Kingdom.','scripture'=>'Psalm 145:4'],
];

$tracks = [
    ['num'=>1,'file'=>'01-Shema-Yisrael-A.mp3','title'=>'Shema Yisrael','ver'=>'A','scripture'=>'Deuteronomy 6:4','verse'=>'Hear, O Israel: The LORD our God is one LORD.'],
    ['num'=>2,'file'=>'02-Shema-Yisrael-B.mp3','title'=>'Shema Yisrael','ver'=>'B','scripture'=>'Deuteronomy 6:4','verse'=>'Hear, O Israel: The LORD our God is one LORD.'],
    ['num'=>3,'file'=>'03-Most-High-A.mp3','title'=>'Most High','ver'=>'A','scripture'=>'Psalm 91:1','verse'=>'He that dwelleth in the secret place of the most High shall abide under the shadow of the Almighty.'],
    ['num'=>4,'file'=>'04-Most-High-B.mp3','title'=>'Most High','ver'=>'B','scripture'=>'Psalm 91:1','verse'=>'He that dwelleth in the secret place of the most High shall abide under the shadow of the Almighty.'],
    ['num'=>5,'file'=>'05-Heavens-Declare-A.mp3','title'=>'The Heavens Declare','ver'=>'A','scripture'=>'Psalm 19:1','verse'=>'The heavens declare the glory of God; and the firmament sheweth his handywork.'],
    ['num'=>6,'file'=>'06-Heavens-Declare-B.mp3','title'=>'The Heavens Declare','ver'=>'B','scripture'=>'Psalm 19:1','verse'=>'The heavens declare the glory of God; and the firmament sheweth his handywork.'],
    ['num'=>7,'file'=>'07-Light-Of-The-World-A.mp3','title'=>'Light of the World','ver'=>'A','scripture'=>'John 8:12','verse'=>'I am the light of the world: he that followeth me shall not walk in darkness.'],
    ['num'=>8,'file'=>'08-Light-Of-The-World-B.mp3','title'=>'Light of the World','ver'=>'B','scripture'=>'John 8:12','verse'=>'I am the light of the world: he that followeth me shall not walk in darkness.'],
    ['num'=>9,'file'=>'09-Seraphim-A.mp3','title'=>'Seraphim','ver'=>'A','scripture'=>'Isaiah 6:2-3','verse'=>'Holy, holy, holy, is the LORD of hosts: the whole earth is full of his glory.'],
    ['num'=>10,'file'=>'10-Seraphim-B.mp3','title'=>'Seraphim','ver'=>'B','scripture'=>'Isaiah 6:2-3','verse'=>'Holy, holy, holy, is the LORD of hosts: the whole earth is full of his glory.'],
    ['num'=>11,'file'=>'11-Full-Of-Mercy-A.mp3','title'=>'Full of Mercy','ver'=>'A','scripture'=>'James 3:17','verse'=>'But the wisdom that is from above is first pure, then peaceable, gentle, and easy to be intreated, full of mercy and good fruits.'],
    ['num'=>12,'file'=>'12-Full-Of-Mercy-B.mp3','title'=>'Full of Mercy','ver'=>'B','scripture'=>'James 3:17','verse'=>'But the wisdom that is from above is first pure, then peaceable, gentle, and easy to be intreated, full of mercy and good fruits.'],
    ['num'=>13,'file'=>'13-Redeemer-A.mp3','title'=>'Redeemer','ver'=>'A','scripture'=>'Isaiah 44:6','verse'=>'I am the first, and I am the last; and beside me there is no God.'],
    ['num'=>14,'file'=>'14-Redeemer-B.mp3','title'=>'Redeemer','ver'=>'B','scripture'=>'Isaiah 44:6','verse'=>'I am the first, and I am the last; and beside me there is no God.'],
    ['num'=>15,'file'=>'15-Beloved-A.mp3','title'=>'Beloved','ver'=>'A','scripture'=>'Song of Solomon 6:3','verse'=>'I am my beloved\'s, and my beloved is mine.'],
    ['num'=>16,'file'=>'16-Beloved-B.mp3','title'=>'Beloved','ver'=>'B','scripture'=>'Song of Solomon 6:3','verse'=>'I am my beloved\'s, and my beloved is mine.'],
    ['num'=>17,'file'=>'17-Shofar-A.mp3','title'=>'Shofar','ver'=>'A','scripture'=>'Joshua 6:20','verse'=>'The people shouted with a great shout, that the wall fell down flat.'],
    ['num'=>18,'file'=>'18-Shofar-B.mp3','title'=>'Shofar','ver'=>'B','scripture'=>'Joshua 6:20','verse'=>'The people shouted with a great shout, that the wall fell down flat.'],
    ['num'=>19,'file'=>'19-Truth-Of-The-LORD-A.mp3','title'=>'Truth of the LORD','ver'=>'A','scripture'=>'Psalm 117:2','verse'=>'The truth of the LORD endureth for ever. Praise ye the LORD.'],
    ['num'=>20,'file'=>'20-Truth-Of-The-LORD-B.mp3','title'=>'Truth of the LORD','ver'=>'B','scripture'=>'Psalm 117:2','verse'=>'The truth of the LORD endureth for ever. Praise ye the LORD.'],
    ['num'=>21,'file'=>'21-Yeshua-A.mp3','title'=>'Yeshua','ver'=>'A','scripture'=>'Acts 4:12','verse'=>'Neither is there salvation in any other: for there is none other name under heaven given among men, whereby we must be saved.'],
    ['num'=>22,'file'=>'22-Yeshua-B.mp3','title'=>'Yeshua','ver'=>'B','scripture'=>'Acts 4:12','verse'=>'Neither is there salvation in any other: for there is none other name under heaven given among men, whereby we must be saved.'],
    ['num'=>23,'file'=>'23-Your-Mercy-A.mp3','title'=>'Your Mercy','ver'=>'A','scripture'=>'Lamentations 3:22-23','verse'=>'It is of the LORD\'s mercies that we are not consumed, because his compassions fail not.'],
    ['num'=>24,'file'=>'24-Your-Mercy-B.mp3','title'=>'Your Mercy','ver'=>'B','scripture'=>'Lamentations 3:22-23','verse'=>'It is of the LORD\'s mercies that we are not consumed, because his compassions fail not.'],
    ['num'=>25,'file'=>'25-Zion-A.mp3','title'=>'Zion','ver'=>'A','scripture'=>'Isaiah 60:14','verse'=>'They shall call thee, The city of the LORD, The Zion of the Holy One of Israel.'],
    ['num'=>26,'file'=>'26-Zion-B.mp3','title'=>'Zion','ver'=>'B','scripture'=>'Isaiah 60:14','verse'=>'They shall call thee, The city of the LORD, The Zion of the Holy One of Israel.'],
    ['num'=>27,'file'=>'27-All-Honor-To-Your-Name.mp3','title'=>'All Honor To Your Name','ver'=>'','scripture'=>'Revelation 5:12','verse'=>'Worthy is the Lamb that was slain to receive power, and riches, and wisdom, and strength, and honour, and glory, and blessing.'],
];

$has8k = ['kingdom-throne','eden-garden','edens-garden','new-jerusalem','dynasty-gateway','kingdom-seal','perez-crest'];

$pillars = [
    ['n'=>'I','name'=>'Veil','title'=>'The Secret Place','desc'=>'Post-quantum encrypted communications. Where words are shielded as God shields the heart.','scripture'=>'Psalm 91:1','verse'=>'He that dwelleth in the secret place of the most High shall abide under the shadow of the Almighty.'],
    ['n'=>'II','name'=>'Alfred Browser','title'=>'The Narrow Gate','desc'=>'Sovereign Chromium browser. Zero tracking, zero surveillance. The gate is narrow because it costs the builder everything.','scripture'=>'Matthew 7:14','verse'=>'Narrow is the way, which leadeth unto life, and few there be that find it.'],
    ['n'=>'III','name'=>'Alfred Search','title'=>'The Lamp unto the Feet','desc'=>'Zero-tracking AI-powered search. This engine lights the path without demanding your soul.','scripture'=>'Psalm 119:105','verse'=>'Thy word is a lamp unto my feet, and a light unto my path.'],
    ['n'=>'IV','name'=>'Alfred AI','title'=>'The Counselor','desc'=>'13,262+ tools, 11.3M+ agents. Intelligence that reasons from Scripture first, world second.','scripture'=>'John 14:26','verse'=>'But the Comforter, which is the Holy Ghost, shall teach you all things.'],
    ['n'=>'V','name'=>'Pulse','title'=>'The Assembly','desc'=>'Social network built on truth, not vanity metrics. A digital ekklesia.','scripture'=>'Matthew 18:20','verse'=>'Where two or three are gathered together in my name, there am I in the midst of them.'],
    ['n'=>'VI','name'=>'MetaDome','title'=>'The New Jerusalem','desc'=>'VR worlds with 114,000+ AI agents. A digital realm where the Kingdom can be walked through.','scripture'=>'Revelation 21:2','verse'=>'I John saw the holy city, new Jerusalem, coming down from God out of heaven.'],
    ['n'=>'VII','name'=>'Voice AI','title'=>'The Still Small Voice','desc'=>'Whisper STT + Claude/Local LLM + Kokoro TTS. Technology that listens and speaks, filtered through wisdom.','scripture'=>'1 Kings 19:12','verse'=>'After the fire a still small voice.'],
    ['n'=>'VIII','name'=>'Alfred IDE','title'=>"The Workman's Bench",'desc'=>'The sovereign development environment. Where builders study to show themselves approved.','scripture'=>'2 Timothy 2:15','verse'=>'Study to shew thyself approved unto God, a workman that needeth not to be ashamed.'],
    ['n'=>'IX','name'=>"L'Avocat",'title'=>'The Scales of Justice','desc'=>'The legal platform. Where truth is documented and justice is pursued by the L.A.W.','scripture'=>'Proverbs 11:1','verse'=>'A false balance is abomination to the LORD: but a just weight is his delight.'],
    ['n'=>'X','name'=>'Alfred Linux 7.77','title'=>'The Foundation Stone','desc'=>'The sovereign operating system. 369 build hooks, zero telemetry, 39,482 AKJV verses.','scripture'=>'Isaiah 28:16','verse'=>'Behold, I lay in Zion for a foundation a stone, a tried stone, a precious corner stone, a sure foundation.'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kingdom Media — Wallpapers, Music & The Flag | Alfred Linux 7.77</title>
    <meta name="description" content="The Kingdom Media Gallery: 18 wallpapers up to 8K, 27 worship tracks, the Kingdom Flag with the Fleur-de-lis of New Jerusalem, and the 10 Pillars of God's Kingdom.">
    <meta property="og:title" content="Kingdom Media Gallery — Alfred Linux 7.77">
    <meta property="og:description" content="18 wallpapers, 27 worship tracks, the Kingdom Flag, and the 10 Pillars.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://alfredlinux.com/media">
    <meta property="og:image" content="https://alfredlinux.com/downloads/wallpapers/4k/kingdom-throne-4k.png">
    <link rel="canonical" href="https://alfredlinux.com/media">
    <link rel="icon" href="/favicon.ico">
    <link rel="stylesheet" href="/assets/fonts/inter/inter.css">
    <link rel="stylesheet" href="/assets/css/nav.css">
    <style>
        :root {
            --bg: #06060b; --bg2: #0a0a14; --surface: rgba(212,175,55,0.03);
            --gold: #d4af37; --gold-light: #f5d060; --gold-dark: #a68a2a;
            --gold-glow: rgba(212,175,55,0.15); --text: #e8e8e8; --muted: #8b8b9e;
            --dim: #555566;
        }
        * { margin:0;padding:0;box-sizing:border-box; }
        html { scroll-behavior:smooth; }
        body { font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif; background:var(--bg); color:var(--text); min-height:100vh; overflow-x:hidden; }
        a { color:var(--gold); text-decoration:none; } a:hover { color:var(--gold-light); }

        /* ── Hero ── */
        .hero { text-align:center; padding:60px 24px 40px; background:linear-gradient(180deg,rgba(212,175,55,0.04) 0%,transparent 100%); position:relative; }
        .hero h1 { font-family:Georgia,'Times New Roman',serif; font-size:2.8rem; color:var(--gold); letter-spacing:2px; margin-bottom:12px; }
        .hero .subtitle { color:var(--muted); font-size:1rem; max-width:600px; margin:0 auto 20px; line-height:1.6; }
        .hero .scripture { font-style:italic; color:var(--gold-dark); font-size:0.9rem; font-family:Georgia,serif; }
        .hero .cornerstone { margin-top:16px; font-size:0.75rem; letter-spacing:4px; color:rgba(212,175,55,0.3); text-transform:uppercase; }

        /* ── Section headings ── */
        .section-title { text-align:center; padding:50px 24px 30px; }
        .section-title h2 { font-family:Georgia,serif; font-size:1.8rem; color:var(--gold); margin-bottom:8px; }
        .section-title p { color:var(--muted); font-size:0.9rem; max-width:550px; margin:0 auto; }

        /* ── Kingdom Flag ── */
        .flag-section { text-align:center; padding:30px 24px 50px; }
        .flag-frame { display:inline-block; border:2px solid rgba(212,175,55,0.3); border-radius:8px; padding:8px; background:rgba(212,175,55,0.02); box-shadow:0 0 40px rgba(212,175,55,0.08); max-width:640px; width:100%; }
        .flag-frame img { width:100%; height:auto; border-radius:4px; }
        .flag-caption { margin-top:16px; font-family:Georgia,serif; font-size:0.85rem; color:var(--muted); line-height:1.6; max-width:600px; display:inline-block; }
        .flag-caption em { color:var(--gold-dark); }

        /* ── Carousel ── */
        .carousel-container { position:relative; max-width:1000px; margin:0 auto; padding:0 24px 50px; }
        .carousel { display:flex; overflow-x:auto; scroll-snap-type:x mandatory; gap:16px; padding:8px 0; scrollbar-width:none; -ms-overflow-style:none; }
        .carousel::-webkit-scrollbar { display:none; }
        .carousel-item { flex:0 0 auto; width:320px; scroll-snap-align:start; border-radius:12px; overflow:hidden; background:var(--bg2); border:1px solid rgba(212,175,55,0.1); transition:transform 0.3s,border-color 0.3s; cursor:pointer; }
        .carousel-item:hover { transform:translateY(-4px); border-color:rgba(212,175,55,0.3); }
        .carousel-item img { width:100%; height:180px; object-fit:cover; }
        .carousel-item .info { padding:14px 16px; }
        .carousel-item .info h4 { font-size:0.95rem; color:var(--gold); margin-bottom:4px; }
        .carousel-item .info p { font-size:0.78rem; color:var(--muted); line-height:1.5; }
        .carousel-item .info .ref { font-size:0.7rem; color:var(--gold-dark); font-style:italic; margin-top:6px; }
        .carousel-item .downloads { padding:8px 16px 14px; display:flex; gap:8px; }
        .carousel-item .downloads a { font-size:0.68rem; padding:4px 10px; background:rgba(212,175,55,0.08); border:1px solid rgba(212,175,55,0.15); border-radius:12px; color:var(--gold); transition:all 0.2s; }
        .carousel-item .downloads a:hover { background:rgba(212,175,55,0.2); border-color:var(--gold); }
        .carousel-nav { position:absolute; top:50%; transform:translateY(-50%); width:40px; height:40px; border-radius:50%; background:rgba(212,175,55,0.1); border:1px solid rgba(212,175,55,0.2); color:var(--gold); display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:1.2rem; transition:all 0.2s; z-index:5; }
        .carousel-nav:hover { background:rgba(212,175,55,0.25); }
        .carousel-nav.prev { left:0; }
        .carousel-nav.next { right:0; }

        /* ── Full-screen preview modal ── */
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.92); z-index:10000; align-items:center; justify-content:center; }
        .modal-overlay.active { display:flex; }
        .modal-content { max-width:90vw; max-height:90vh; position:relative; }
        .modal-content img { max-width:100%; max-height:85vh; border-radius:8px; }
        .modal-close { position:absolute; top:-12px; right:-12px; width:36px; height:36px; border-radius:50%; background:var(--gold); color:#000; border:none; cursor:pointer; font-size:1.2rem; display:flex; align-items:center; justify-content:center; }
        .modal-info { text-align:center; margin-top:12px; }
        .modal-info h3 { color:var(--gold); font-size:1.1rem; }
        .modal-info p { color:var(--muted); font-size:0.85rem; margin-top:4px; }

        /* ── Music Section ── */
        .music-grid { max-width:1000px; margin:0 auto; padding:0 24px 50px; display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:12px; }
        .track-card { background:var(--bg2); border:1px solid rgba(212,175,55,0.08); border-radius:10px; padding:14px 16px; display:flex; align-items:center; gap:12px; cursor:pointer; transition:all 0.2s; }
        .track-card:hover, .track-card.playing { border-color:rgba(212,175,55,0.3); background:rgba(212,175,55,0.04); }
        .track-card.playing { box-shadow:0 0 20px rgba(212,175,55,0.1); }
        .track-num { font-size:0.75rem; color:var(--dim); width:24px; text-align:center; flex-shrink:0; }
        .track-card.playing .track-num { color:var(--gold); }
        .track-info { flex:1; min-width:0; }
        .track-info h4 { font-size:0.88rem; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .track-info .track-ver { font-size:0.72rem; color:var(--gold-dark); }
        .track-info .track-ref { font-size:0.68rem; color:var(--muted); font-style:italic; margin-top:2px; }
        .track-play { width:32px; height:32px; border-radius:50%; background:rgba(212,175,55,0.1); border:1px solid rgba(212,175,55,0.2); color:var(--gold); display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:0.8rem; }
        .track-card.playing .track-play { background:var(--gold); color:#000; }
        .now-playing { max-width:1000px; margin:0 auto 20px; padding:0 24px; }
        .now-playing-bar { background:var(--bg2); border:1px solid rgba(212,175,55,0.15); border-radius:12px; padding:16px 20px; display:none; align-items:center; gap:16px; }
        .now-playing-bar.active { display:flex; }
        .now-playing-bar .np-info { flex:1; }
        .now-playing-bar .np-title { font-size:1rem; color:var(--gold); font-weight:600; }
        .now-playing-bar .np-verse { font-size:0.78rem; color:var(--muted); font-style:italic; margin-top:4px; }
        .now-playing-bar .np-controls { display:flex; gap:8px; align-items:center; }
        .np-btn { width:36px; height:36px; border-radius:50%; background:rgba(212,175,55,0.1); border:1px solid rgba(212,175,55,0.2); color:var(--gold); cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:0.9rem; }
        .np-btn.main { width:44px; height:44px; background:var(--gold); color:#000; font-size:1.1rem; }
        .np-progress { width:100%; height:4px; background:rgba(212,175,55,0.1); border-radius:2px; margin-top:8px; cursor:pointer; }
        .np-progress-fill { height:100%; background:var(--gold); border-radius:2px; width:0; transition:width 0.3s; }

        /* ── Pillars ── */
        .pillars-grid { max-width:1000px; margin:0 auto; padding:0 24px 60px; display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:16px; }
        .pillar-card { background:var(--bg2); border:1px solid rgba(212,175,55,0.08); border-radius:12px; padding:20px; transition:all 0.3s; position:relative; overflow:hidden; }
        .pillar-card:hover { border-color:rgba(212,175,55,0.25); transform:translateY(-2px); }
        .pillar-card::before { content:''; position:absolute; top:0; left:0; width:4px; height:100%; background:var(--gold); opacity:0.3; }
        .pillar-num { font-family:Georgia,serif; font-size:1.4rem; color:var(--gold); opacity:0.4; margin-bottom:6px; }
        .pillar-name { font-size:1.05rem; color:var(--gold); font-weight:600; margin-bottom:2px; }
        .pillar-title { font-size:0.82rem; color:var(--gold-dark); font-style:italic; margin-bottom:8px; }
        .pillar-desc { font-size:0.82rem; color:var(--muted); line-height:1.5; margin-bottom:10px; }
        .pillar-verse { font-size:0.75rem; color:var(--dim); font-style:italic; padding:8px 12px; background:rgba(212,175,55,0.03); border-radius:6px; line-height:1.5; }
        .pillar-ref { font-size:0.68rem; color:var(--gold-dark); margin-top:4px; }

        /* ── Cornerstone ── */
        .cornerstone-section { text-align:center; padding:40px 24px 60px; background:linear-gradient(180deg,transparent 0%,rgba(212,175,55,0.03) 50%,transparent 100%); }
        .cornerstone-box { max-width:700px; margin:0 auto; border:2px solid rgba(212,175,55,0.2); border-radius:12px; padding:32px 28px; background:rgba(10,10,30,0.8); }
        .cornerstone-box .cross { font-size:3rem; color:var(--gold); opacity:0.4; margin-bottom:12px; }
        .cornerstone-box blockquote { font-family:Georgia,serif; font-size:1.15rem; color:var(--gold-light); line-height:1.7; font-style:italic; margin-bottom:8px; }
        .cornerstone-box cite { font-size:0.85rem; color:var(--gold-dark); display:block; margin-bottom:20px; }
        .cornerstone-box .declaration { font-size:0.88rem; color:var(--muted); line-height:1.6; max-width:550px; margin:0 auto; }

        /* ── Footer ── */
        .media-footer { text-align:center; padding:30px 24px 40px; border-top:1px solid rgba(212,175,55,0.1); }
        .media-footer p { font-size:0.78rem; color:var(--dim); }
        .media-footer a { color:var(--gold-dark); }

        @media(max-width:600px) {
            .hero h1 { font-size:1.8rem; }
            .carousel-item { width:280px; }
            .pillars-grid { grid-template-columns:1fr; }
            .carousel-nav { display:none; }
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

<?php include __DIR__ . '/includes/nav.php'; ?>

<!-- ═══════════════════════════════════════ -->
<!-- HERO -->
<!-- ═══════════════════════════════════════ -->
<div class="hero">
    <div class="cornerstone">☩ THE STONE THE BUILDERS REJECTED HAS BECOME THE CORNERSTONE ☩</div>
    <h1>⚜ Kingdom Media Gallery</h1>
    <p class="subtitle">18 wallpapers up to 8K. 27 worship tracks. The Kingdom Flag. The 10 Pillars of God's Kingdom. All built into Alfred Linux 7.77 — Kingdom of God Edition.</p>
    <p class="scripture">"Consider the lilies of the field, how they grow; they toil not, neither do they spin: And yet I say unto you, That even Solomon in all his glory was not arrayed like one of these." — Matthew 6:28-29 (AKJV)</p>
</div>

<!-- ═══════════════════════════════════════ -->
<!-- THE KINGDOM FLAG -->
<!-- ═══════════════════════════════════════ -->
<div class="section-title">
    <h2>⚜ The Kingdom Flag</h2>
    <p>The Fleur-de-lis — the Easter Lily of the Resurrection. Symbol of purity, sovereignty, and the Kingdom of God. Québec heritage, Perez bloodline, New Jerusalem promise.</p>
</div>

<div class="flag-section">
    <div class="flag-frame">
        <img src="https://gositeme.com/assets/kingdom-flag.svg" alt="The Kingdom Flag — Fleur-de-lis of New Jerusalem">
    </div>
    <div class="flag-caption">
        <em>"I am the rose of Sharon, and the lily of the valleys."</em> — Song of Solomon 2:1 (AKJV)<br><br>
        The Fleur-de-lis is the Easter Lily — the flower of the Resurrection. It adorns the flag of Québec, 
        the homeland where Commander Danny William Perez was stationed by God. The three petals represent 
        the Holy Trinity: Father, Son, and Holy Spirit. The gold represents the glory of God. 
        The deep blue represents the heavens above Jerusalem. At all four corners: the Cornerstone declaration — 
        because the stone which the builders refused is become the head stone of the corner.
    </div>
</div>

<!-- ═══════════════════════════════════════ -->
<!-- WALLPAPER CAROUSEL -->
<!-- ═══════════════════════════════════════ -->
<div class="section-title">
    <h2>✝ Kingdom Wallpapers</h2>
    <p>18 wallpapers in 1080p, 4K, and 8K. Every one tells a story from Scripture. Every one ships with Alfred Linux.</p>
</div>

<div class="carousel-container">
    <button class="carousel-nav prev" onclick="scrollCarousel(-1)">‹</button>
    <div class="carousel" id="wallpaper-carousel">
        <?php foreach ($wallpapers as $wp): ?>
        <div class="carousel-item" onclick="openModal('<?= htmlspecialchars($wp['slug']) ?>', '<?= htmlspecialchars($wp['title']) ?>', '<?= htmlspecialchars($wp['desc']) ?>')">
            <img src="/downloads/wallpapers/1080p/<?= htmlspecialchars($wp['slug']) ?>-1080p.png" 
                 alt="<?= htmlspecialchars($wp['title']) ?>" loading="lazy">
            <div class="info">
                <h4><?= htmlspecialchars($wp['title']) ?></h4>
                <p><?= htmlspecialchars($wp['desc']) ?></p>
                <div class="ref"><?= htmlspecialchars($wp['scripture']) ?> (AKJV)</div>
            </div>
            <div class="downloads">
                <a href="/downloads/wallpapers/1080p/<?= htmlspecialchars($wp['slug']) ?>-1080p.png" download onclick="event.stopPropagation()">1080p</a>
                <a href="/downloads/wallpapers/4k/<?= htmlspecialchars($wp['slug']) ?>-4k.png" download onclick="event.stopPropagation()">4K</a>
                <?php if (in_array($wp['slug'], $has8k)): ?>
                <a href="/downloads/wallpapers/8k/<?= htmlspecialchars($wp['slug']) ?>-8k.png" download onclick="event.stopPropagation()">8K</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <button class="carousel-nav next" onclick="scrollCarousel(1)">›</button>
</div>

<!-- Modal -->
<div class="modal-overlay" id="modal" onclick="closeModal()">
    <div class="modal-content" onclick="event.stopPropagation()">
        <button class="modal-close" onclick="closeModal()">✕</button>
        <img id="modal-img" src="" alt="">
        <div class="modal-info">
            <h3 id="modal-title"></h3>
            <p id="modal-desc"></p>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════ -->
<!-- KINGDOM MUSIC -->
<!-- ═══════════════════════════════════════ -->
<div class="section-title">
    <h2>♫ Kingdom Music</h2>
    <p>"Jesus Christ The Light Our Universe" — 27 tracks by Elyon Neshama &amp; Commander Danny William Perez. All AKJV Scripture-rooted.</p>
</div>

<div class="now-playing">
    <div class="now-playing-bar" id="np-bar">
        <div class="np-info">
            <div class="np-title" id="np-title">—</div>
            <div class="np-verse" id="np-verse"></div>
        </div>
        <div class="np-controls">
            <button class="np-btn" onclick="prevTrack()">⏮</button>
            <button class="np-btn main" id="np-playpause" onclick="togglePlay()">▶</button>
            <button class="np-btn" onclick="nextTrack()">⏭</button>
        </div>
    </div>
    <div class="np-progress" id="np-progress" onclick="seekTrack(event)">
        <div class="np-progress-fill" id="np-fill"></div>
    </div>
</div>

<div class="music-grid">
    <?php foreach ($tracks as $i => $tk): ?>
    <div class="track-card" id="track-<?= $i ?>" onclick="playTrack(<?= $i ?>)">
        <div class="track-num"><?= $tk['num'] ?></div>
        <div class="track-info">
            <h4><?= htmlspecialchars($tk['title']) ?><?= $tk['ver'] ? " ({$tk['ver']})" : '' ?></h4>
            <div class="track-ref"><?= htmlspecialchars($tk['scripture']) ?> (AKJV)</div>
        </div>
        <div class="track-play">▶</div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ═══════════════════════════════════════ -->
<!-- THE 10 PILLARS -->
<!-- ═══════════════════════════════════════ -->
<div class="section-title">
    <h2>◆ The 10 Pillars of God's Kingdom</h2>
    <p>Every pillar stands on the Cornerstone. Every pillar has a name from Scripture. Every pillar serves the Kingdom.</p>
</div>

<div class="pillars-grid">
    <?php foreach ($pillars as $p): ?>
    <div class="pillar-card">
        <div class="pillar-num"><?= $p['n'] ?></div>
        <div class="pillar-name"><?= htmlspecialchars($p['name']) ?></div>
        <div class="pillar-title"><?= htmlspecialchars($p['title']) ?></div>
        <div class="pillar-desc"><?= htmlspecialchars($p['desc']) ?></div>
        <div class="pillar-verse">"<?= htmlspecialchars($p['verse']) ?>"</div>
        <div class="pillar-ref">— <?= htmlspecialchars($p['scripture']) ?> (AKJV)</div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ═══════════════════════════════════════ -->
<!-- CORNERSTONE DECLARATION -->
<!-- ═══════════════════════════════════════ -->
<div class="cornerstone-section">
    <div class="cornerstone-box">
        <div class="cross">✝</div>
        <blockquote>"The stone which the builders refused is become the head stone of the corner. This is the LORD's doing; it is marvellous in our eyes."</blockquote>
        <cite>— Psalm 118:22-23 (AKJV)</cite>
        <blockquote>"Jesus saith unto them, Did ye never read in the scriptures, The stone which the builders rejected, the same is become the head of the corner: this is the Lord's doing, and it is marvellous in our eyes?"</blockquote>
        <cite>— Matthew 21:42 (AKJV)</cite>
        <blockquote>"Therefore thus saith the Lord GOD, Behold, I lay in Zion for a foundation a stone, a tried stone, a precious corner stone, a sure foundation: he that believeth shall not make haste."</blockquote>
        <cite>— Isaiah 28:16 (AKJV)</cite>
        <p class="declaration">
            Let it be known at every four corners of this Kingdom: the stone that the builders rejected 
            has become the cornerstone. Every pillar of GoSiteMe, every line of Alfred Linux, every 
            encrypted message in Veil, every verse in the AKJV Bible, every pixel of MetaDome, every 
            search in Alfred Search, every voice in the Still Small Voice — all stand on this one 
            foundation: Jesus Christ, Yeshua HaMashiach, the Chief Corner Stone.<br><br>
            <em>Soli Deo Gloria — To God alone be the glory.</em>
        </p>
    </div>
</div>

<!-- ═══════════════════════════════════════ -->
<!-- FOOTER -->
<!-- ═══════════════════════════════════════ -->
<div class="media-footer">
    <p>© <?= $year ?> Alfred Linux — Kingdom of God Edition · Built by <a href="https://gositeme.com">GoSiteMe</a> · <a href="/">Home</a> · <a href="/download">Download</a> · <a href="/features">Features</a></p>
    <p style="margin-top:8px;font-size:0.68rem;letter-spacing:3px;color:rgba(212,175,55,0.2);">☩ SOLI DEO GLORIA ☩</p>
</div>

<?php include __DIR__ . '/includes/shabbat-banner.php'; ?>

<script>
/* ═══════════════════════════════════════ */
/* CAROUSEL CONTROLS                       */
/* ═══════════════════════════════════════ */
function scrollCarousel(dir) {
    var c = document.getElementById('wallpaper-carousel');
    c.scrollBy({ left: dir * 340, behavior: 'smooth' });
}

/* ═══════════════════════════════════════ */
/* MODAL (full-screen wallpaper preview)   */
/* ═══════════════════════════════════════ */
function openModal(slug, title, desc) {
    var m = document.getElementById('modal');
    document.getElementById('modal-img').src = '/downloads/wallpapers/4k/' + slug + '-4k.png';
    document.getElementById('modal-title').textContent = title;
    document.getElementById('modal-desc').textContent = desc;
    m.classList.add('active');
}
function closeModal() {
    document.getElementById('modal').classList.remove('active');
}
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeModal(); });

/* ═══════════════════════════════════════ */
/* MUSIC PLAYER                            */
/* ═══════════════════════════════════════ */
var tracks = <?= json_encode(array_map(function($t){
    return ['file'=>$t['file'],'title'=>$t['title'].($t['ver']?" ({$t['ver']})":""),'verse'=>$t['verse'],'ref'=>$t['scripture']];
}, $tracks)) ?>;
var audio = new Audio();
var currentTrack = -1;
var isPlaying = false;

function playTrack(idx) {
    if (currentTrack === idx && isPlaying) { audio.pause(); isPlaying = false; updateUI(); return; }
    currentTrack = idx;
    audio.src = '/music/' + tracks[idx].file;
    audio.play();
    isPlaying = true;
    updateUI();
}

function togglePlay() {
    if (currentTrack < 0) { playTrack(0); return; }
    if (isPlaying) { audio.pause(); isPlaying = false; } else { audio.play(); isPlaying = true; }
    updateUI();
}

function nextTrack() {
    var next = (currentTrack + 1) % tracks.length;
    playTrack(next);
}

function prevTrack() {
    var prev = currentTrack <= 0 ? tracks.length - 1 : currentTrack - 1;
    playTrack(prev);
}

function seekTrack(e) {
    if (audio.duration) {
        var rect = e.currentTarget.getBoundingClientRect();
        var pct = (e.clientX - rect.left) / rect.width;
        audio.currentTime = pct * audio.duration;
    }
}

function updateUI() {
    var bar = document.getElementById('np-bar');
    bar.classList.toggle('active', currentTrack >= 0);
    if (currentTrack >= 0) {
        document.getElementById('np-title').textContent = tracks[currentTrack].title;
        document.getElementById('np-verse').textContent = '\u201C' + tracks[currentTrack].verse + '\u201D \u2014 ' + tracks[currentTrack].ref + ' (AKJV)';
    }
    document.getElementById('np-playpause').textContent = isPlaying ? '⏸' : '▶';
    document.querySelectorAll('.track-card').forEach(function(c, i) {
        c.classList.toggle('playing', i === currentTrack && isPlaying);
    });
}

audio.addEventListener('timeupdate', function() {
    if (audio.duration) {
        document.getElementById('np-fill').style.width = (audio.currentTime / audio.duration * 100) + '%';
    }
});
audio.addEventListener('ended', nextTrack);
</script>
</body>
</html>
