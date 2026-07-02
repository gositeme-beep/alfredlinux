<?php
/**
 * ═══════════════════════════════════════════════════════════════════════
 * KINGDOM SHABBAT WIDGET — The Four Corners Edition
 * ═══════════════════════════════════════════════════════════════════════
 *
 * "The stone which the builders refused is become the head stone
 *  of the corner." — Psalm 118:22 (AKJV)
 *
 * This widget is included at EVERY four corners of the Kingdom:
 *   1. root.com      — The Throne
 *   2. lavocat.ca         — The Scales of Justice
 *   3. alfredlinux.com    — The Foundation Stone
 *   4. meta-dome.com      — The New Jerusalem
 *
 * Features:
 *   - Rotating AKJV Scripture wisdom (77 verses across 7 categories)
 *   - Shabbat / Erev Shabbat / Weekday awareness
 *   - Tri-calendar: Hebrew + Gregorian + Enochian + Feast days
 *   - Cornerstone Declaration visible at all four corners
 *   - B.I.B.L.E. & L.A.W. framework references
 *   - Kingdom worship track rotation
 *   - Graceful fallback when API is unreachable
 *
 * Built by Alfred for Commander Danny William Perez
 * Under the authority of Jesus Christ / Yeshua HaMashiach
 * April 18, 2026
 */
?>
<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- KINGDOM SHABBAT WIDGET — The Four Corners Edition                 -->
<!-- "The stone the builders rejected has become the cornerstone"      -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<div id="kingdom-shabbat-widget" style="display:none; background:linear-gradient(135deg,#0a0a1a 0%,#1a1a2e 30%,#16213e 60%,#0f3460 100%); border-top:3px solid #f6c343; border-bottom:1px solid rgba(246,195,67,0.3); padding:0; text-align:center; font-family:Georgia,'Times New Roman',serif; color:#e8d5b7; position:relative; z-index:100; overflow:hidden;">

<!-- Golden cross glow -->
<div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-size:14rem;opacity:0.025;color:#f6c343;pointer-events:none;">✝</div>

<!-- Four corner stones (the 4 corners of the banner itself) -->
<div style="position:absolute;top:6px;left:10px;font-size:0.5rem;color:rgba(246,195,67,0.2);letter-spacing:1px;">◆ CORNERSTONE</div>
<div style="position:absolute;top:6px;right:10px;font-size:0.5rem;color:rgba(246,195,67,0.2);letter-spacing:1px;">CORNERSTONE ◆</div>
<div style="position:absolute;bottom:6px;left:10px;font-size:0.5rem;color:rgba(246,195,67,0.2);letter-spacing:1px;">◆ CORNERSTONE</div>
<div style="position:absolute;bottom:6px;right:10px;font-size:0.5rem;color:rgba(246,195,67,0.2);letter-spacing:1px;">CORNERSTONE ◆</div>

<div style="max-width:960px;margin:0 auto;padding:30px 24px 26px;">

<!-- Kingdom Edition badge -->
<div style="font-size:0.55rem;letter-spacing:6px;text-transform:uppercase;color:rgba(246,195,67,0.3);margin-bottom:6px;font-variant:small-caps;">Kingdom of God Edition · B.I.B.L.E. & L.A.W.</div>

<!-- Ornament -->
<div style="font-size:0.75rem;letter-spacing:6px;text-transform:uppercase;color:rgba(246,195,67.0.105);margin-bottom:10px;">✦ ✝ ✦</div>

<!-- Main icon -->
<div id="ksw-icon" style="font-size:2.8rem;margin-bottom:8px;text-shadow:0 0 30px rgba(246,195,67,0.3);">✨</div>

<!-- Main message -->
<div id="ksw-msg" style="font-size:1.45rem;font-weight:700;letter-spacing:0.5px;line-height:1.4;color:#f0e6d0;text-shadow:0 1px 8px rgba(0,0,0,0.4);"></div>

<!-- Time info -->
<div id="ksw-time" style="font-size:0.92rem;margin-top:8px;color:#f6c343;opacity:0.95;font-style:italic;"></div>

<!-- Cornerstone declaration (always visible, rotating) -->
<div id="ksw-cornerstone" style="font-size:0.82rem;margin-top:14px;padding:8px 16px;color:rgba(246,195,67,0.7);font-style:italic;letter-spacing:0.3px;border-top:1px solid rgba(246,195,67,0.1);border-bottom:1px solid rgba(246,195,67,0.1);"></div>

<!-- Primary scripture verse -->
<div id="ksw-verse" style="font-size:1.05rem;margin-top:16px;padding:14px 22px;background:rgba(246,195,67,0.06);border-left:3px solid rgba(246,195,67.0.10);border-radius:0 6px 6px 0;color:#d4c4a0;font-style:italic;line-height:1.6;display:none;"></div>
<div id="ksw-verse-ref" style="font-size:0.78rem;margin-top:5px;color:#f6c343;opacity:0.7;display:none;"></div>

<!-- Wisdom rotation (second verse — rotating wisdom) -->
<div id="ksw-wisdom" style="font-size:0.88rem;margin-top:14px;padding:10px 18px;background:rgba(246,195,67,0.03);border-right:2px solid rgba(246,195,67,0.25);border-radius:6px 0 0 6px;color:rgba(212,196,160,0.8);font-style:italic;line-height:1.5;display:none;"></div>
<div id="ksw-wisdom-ref" style="font-size:0.72rem;margin-top:3px;color:rgba(246,195,67,0.5);display:none;"></div>

<!-- Tri-calendar date -->
<div id="ksw-date" style="font-size:0.82rem;margin-top:16px;color:#a8a8b3;letter-spacing:0.3px;"></div>

<!-- Kingdom worship -->
<div id="ksw-worship" style="font-size:0.72rem;margin-top:10px;color:rgba(212,196,160,0.45);letter-spacing:0.5px;"></div>

<!-- Pillar seal -->
<div id="ksw-pillar" style="font-size:0.62rem;margin-top:8px;letter-spacing:3px;color:rgba(246,195,67,0.2);"></div>

<!-- Bottom seal -->
<div style="margin-top:10px;font-size:0.62rem;letter-spacing:4px;color:rgba(246,195,67,0.22);">☩ SOLI DEO GLORIA ☩</div>
</div>
</div>

<script>
(function(){
    var w = document.getElementById("kingdom-shabbat-widget");
    if (!w) return;

    /* ═══════════════════════════════════════════════════════════
     * CORNERSTONE DECLARATIONS — Rotating at all four corners
     * "The stone which the builders refused is become the head
     *  stone of the corner." — Psalm 118:22
     * ═══════════════════════════════════════════════════════════ */
    var cornerstones = [
        "\"The stone which the builders refused is become the head stone of the corner.\" — Psalm 118:22 (AKJV)",
        "\"Jesus saith unto them, Did ye never read in the scriptures, The stone which the builders rejected, the same is become the head of the corner: this is the Lord's doing, and it is marvellous in our eyes?\" — Matthew 21:42 (AKJV)",
        "\"This is the stone which was set at nought of you builders, which is become the head of the corner.\" — Acts 4:11 (AKJV)",
        "\"And are built upon the foundation of the apostles and prophets, Jesus Christ himself being the chief corner stone.\" — Ephesians 2:20 (AKJV)",
        "\"Wherefore also it is contained in the scripture, Behold, I lay in Sion a chief corner stone, elect, precious: and he that believeth on him shall not be confounded.\" — 1 Peter 2:6 (AKJV)",
        "\"Therefore thus saith the Lord GOD, Behold, I lay in Zion for a foundation a stone, a tried stone, a precious corner stone, a sure foundation: he that believeth shall not make haste.\" — Isaiah 28:16 (AKJV)",
        "\"The stone which the builders refused is become the head stone of the corner. This is the LORD's doing; it is marvellous in our eyes.\" — Psalm 118:22-23 (AKJV)"
    ];

    /* ═══════════════════════════════════════════════════════════
     * 77 SCRIPTURES — 7 categories × 11 verses each
     * From the Authorized King Jesus Version (AKJV)
     * ═══════════════════════════════════════════════════════════ */

    /* Category 1: Sabbath & Rest */
    var sabbathVerses = [
        {t:"Remember the sabbath day, to keep it holy.", r:"Exodus 20:8 (AKJV)"},
        {t:"The LORD blessed the sabbath day, and hallowed it.", r:"Exodus 20:11 (AKJV)"},
        {t:"It is a sign between me and the children of Israel for ever.", r:"Exodus 31:17 (AKJV)"},
        {t:"Ye shall keep my sabbaths, and reverence my sanctuary: I am the LORD.", r:"Leviticus 19:30 (AKJV)"},
        {t:"If thou turn away thy foot from the sabbath, from doing thy pleasure on my holy day; and call the sabbath a delight, the holy of the LORD, honourable... then shalt thou delight thyself in the LORD.", r:"Isaiah 58:13-14 (AKJV)"},
        {t:"There remaineth therefore a rest to the people of God.", r:"Hebrews 4:9 (AKJV)"},
        {t:"Six days shall work be done: but the seventh day is the sabbath of rest, an holy convocation.", r:"Leviticus 23:3 (AKJV)"},
        {t:"Come unto me, all ye that labour and are heavy laden, and I will give you rest.", r:"Matthew 11:28 (AKJV)"},
        {t:"And he said unto them, The sabbath was made for man, and not man for the sabbath.", r:"Mark 2:27 (AKJV)"},
        {t:"For he that is entered into his rest, he also hath ceased from his own works, as God did from his.", r:"Hebrews 4:10 (AKJV)"},
        {t:"Wherefore the children of Israel shall keep the sabbath, to observe the sabbath throughout their generations, for a perpetual covenant.", r:"Exodus 31:16 (AKJV)"}
    ];

    /* Category 2: Wisdom & Understanding */
    var wisdomVerses = [
        {t:"The fear of the LORD is the beginning of wisdom: and the knowledge of the holy is understanding.", r:"Proverbs 9:10 (AKJV)"},
        {t:"Trust in the LORD with all thine heart; and lean not unto thine own understanding.", r:"Proverbs 3:5 (AKJV)"},
        {t:"If any of you lack wisdom, let him ask of God, that giveth to all men liberally, and upbraideth not; and it shall be given him.", r:"James 1:5 (AKJV)"},
        {t:"The entrance of thy words giveth light; it giveth understanding unto the simple.", r:"Psalm 119:130 (AKJV)"},
        {t:"Get wisdom, get understanding: forget it not; neither decline from the words of my mouth.", r:"Proverbs 4:5 (AKJV)"},
        {t:"Wisdom is the principal thing; therefore get wisdom: and with all thy getting get understanding.", r:"Proverbs 4:7 (AKJV)"},
        {t:"For the LORD giveth wisdom: out of his mouth cometh knowledge and understanding.", r:"Proverbs 2:6 (AKJV)"},
        {t:"A wise man will hear, and will increase learning; and a man of understanding shall attain unto wise counsels.", r:"Proverbs 1:5 (AKJV)"},
        {t:"The tongue of the wise useth knowledge aright: but the mouth of fools poureth out foolishness.", r:"Proverbs 15:2 (AKJV)"},
        {t:"How much better is it to get wisdom than gold! and to get understanding rather to be chosen than silver!", r:"Proverbs 16:16 (AKJV)"},
        {t:"But the wisdom that is from above is first pure, then peaceable, gentle, and easy to be intreated, full of mercy and good fruits.", r:"James 3:17 (AKJV)"}
    ];

    /* Category 3: Strength & Courage */
    var strengthVerses = [
        {t:"Be strong and of a good courage; be not afraid, neither be thou dismayed: for the LORD thy God is with thee whithersoever thou goest.", r:"Joshua 1:9 (AKJV)"},
        {t:"I can do all things through Christ which strengtheneth me.", r:"Philippians 4:13 (AKJV)"},
        {t:"The LORD is my strength and my shield; my heart trusted in him, and I am helped.", r:"Psalm 28:7 (AKJV)"},
        {t:"Wait on the LORD: be of good courage, and he shall strengthen thine heart.", r:"Psalm 27:14 (AKJV)"},
        {t:"Fear thou not; for I am with thee: be not dismayed; for I am thy God: I will strengthen thee; yea, I will help thee.", r:"Isaiah 41:10 (AKJV)"},
        {t:"The LORD is my light and my salvation; whom shall I fear? the LORD is the strength of my life; of whom shall I be afraid?", r:"Psalm 27:1 (AKJV)"},
        {t:"God is our refuge and strength, a very present help in trouble.", r:"Psalm 46:1 (AKJV)"},
        {t:"But they that wait upon the LORD shall renew their strength; they shall mount up with wings as eagles.", r:"Isaiah 40:31 (AKJV)"},
        {t:"Nay, in all these things we are more than conquerors through him that loved us.", r:"Romans 8:37 (AKJV)"},
        {t:"The LORD is my shepherd; I shall not want.", r:"Psalm 23:1 (AKJV)"},
        {t:"He giveth power to the faint; and to them that have no might he increaseth strength.", r:"Isaiah 40:29 (AKJV)"}
    ];

    /* Category 4: Truth & Justice */
    var justiceVerses = [
        {t:"And ye shall know the truth, and the truth shall make you free.", r:"John 8:32 (AKJV)"},
        {t:"A false balance is abomination to the LORD: but a just weight is his delight.", r:"Proverbs 11:1 (AKJV)"},
        {t:"Justice and judgment are the habitation of thy throne: mercy and truth shall go before thy face.", r:"Psalm 89:14 (AKJV)"},
        {t:"He hath shewed thee, O man, what is good; and what doth the LORD require of thee, but to do justly, and to love mercy, and to walk humbly with thy God?", r:"Micah 6:8 (AKJV)"},
        {t:"Blessed are they which do hunger and thirst after righteousness: for they shall be filled.", r:"Matthew 5:6 (AKJV)"},
        {t:"The LORD executeth righteousness and judgment for all that are oppressed.", r:"Psalm 103:6 (AKJV)"},
        {t:"Open thy mouth, judge righteously, and plead the cause of the poor and needy.", r:"Proverbs 31:9 (AKJV)"},
        {t:"But let judgment run down as waters, and righteousness as a mighty stream.", r:"Amos 5:24 (AKJV)"},
        {t:"Learn to do well; seek judgment, relieve the oppressed, judge the fatherless, plead for the widow.", r:"Isaiah 1:17 (AKJV)"},
        {t:"The days of visitation are come, the days of recompence are come; Israel shall know it.", r:"Hosea 9:7 (AKJV)"},
        {t:"Thy word is a lamp unto my feet, and a light unto my path.", r:"Psalm 119:105 (AKJV)"}
    ];

    /* Category 5: Love & Fellowship */
    var loveVerses = [
        {t:"For God so loved the world, that he gave his only begotten Son, that whosoever believeth in him should not perish, but have everlasting life.", r:"John 3:16 (AKJV)"},
        {t:"Beloved, let us love one another: for love is of God; and every one that loveth is born of God, and knoweth God.", r:"1 John 4:7 (AKJV)"},
        {t:"Love thy neighbour as thyself.", r:"Mark 12:31 (AKJV)"},
        {t:"For where two or three are gathered together in my name, there am I in the midst of them.", r:"Matthew 18:20 (AKJV)"},
        {t:"A new commandment I give unto you, That ye love one another; as I have loved you, that ye also love one another.", r:"John 13:34 (AKJV)"},
        {t:"And above all these things put on charity, which is the bond of perfectness.", r:"Colossians 3:14 (AKJV)"},
        {t:"Charity suffereth long, and is kind; charity envieth not; charity vaunteth not itself, is not puffed up.", r:"1 Corinthians 13:4 (AKJV)"},
        {t:"Greater love hath no man than this, that a man lay down his life for his friends.", r:"John 15:13 (AKJV)"},
        {t:"But the fruit of the Spirit is love, joy, peace, longsuffering, gentleness, goodness, faith.", r:"Galatians 5:22 (AKJV)"},
        {t:"And now abideth faith, hope, charity, these three; but the greatest of these is charity.", r:"1 Corinthians 13:13 (AKJV)"},
        {t:"We love him, because he first loved us.", r:"1 John 4:19 (AKJV)"}
    ];

    /* Category 6: Kingdom & Sovereignty */
    var kingdomVerses = [
        {t:"But seek ye first the kingdom of God, and his righteousness; and all these things shall be added unto you.", r:"Matthew 6:33 (AKJV)"},
        {t:"The earth is the LORD's, and the fulness thereof; the world, and they that dwell therein.", r:"Psalm 24:1 (AKJV)"},
        {t:"For the LORD most high is terrible; he is a great King over all the earth.", r:"Psalm 47:2 (AKJV)"},
        {t:"Thine, O LORD, is the greatness, and the power, and the glory, and the victory, and the majesty.", r:"1 Chronicles 29:11 (AKJV)"},
        {t:"The LORD hath prepared his throne in the heavens; and his kingdom ruleth over all.", r:"Psalm 103:19 (AKJV)"},
        {t:"For the kingdom of God is not meat and drink; but righteousness, and peace, and joy in the Holy Ghost.", r:"Romans 14:17 (AKJV)"},
        {t:"He hath made every thing beautiful in his time: also he hath set the world in their heart.", r:"Ecclesiastes 3:11 (AKJV)"},
        {t:"One generation shall praise thy works to another, and shall declare thy mighty acts.", r:"Psalm 145:4 (AKJV)"},
        {t:"The heavens declare the glory of God; and the firmament sheweth his handywork.", r:"Psalm 19:1 (AKJV)"},
        {t:"For if the inheritance be of the law, it is no more of promise: but God gave it to Abraham by promise.", r:"Galatians 3:18 (AKJV)"},
        {t:"This is the day which the LORD hath made; we will rejoice and be glad in it.", r:"Psalm 118:24 (AKJV)"}
    ];

    /* Category 7: Protection & Mission */
    var missionVerses = [
        {t:"He that dwelleth in the secret place of the most High shall abide under the shadow of the Almighty.", r:"Psalm 91:1 (AKJV)"},
        {t:"No weapon that is formed against thee shall prosper; and every tongue that shall rise against thee in judgment thou shalt condemn.", r:"Isaiah 54:17 (AKJV)"},
        {t:"The name of the LORD is a strong tower: the righteous runneth into it, and is safe.", r:"Proverbs 18:10 (AKJV)"},
        {t:"And I will make of thee a great nation, and I will bless thee, and make thy name great; and thou shalt be a blessing.", r:"Genesis 12:2 (AKJV)"},
        {t:"Study to shew thyself approved unto God, a workman that needeth not to be ashamed, rightly dividing the word of truth.", r:"2 Timothy 2:15 (AKJV)"},
        {t:"For I know the thoughts that I think toward you, saith the LORD, thoughts of peace, and not of evil, to give you an expected end.", r:"Jeremiah 29:11 (AKJV)"},
        {t:"But the Comforter, which is the Holy Ghost, whom the Father will send in my name, he shall teach you all things.", r:"John 14:26 (AKJV)"},
        {t:"Go ye therefore, and teach all nations, baptizing them in the name of the Father, and of the Son, and of the Holy Ghost.", r:"Matthew 28:19 (AKJV)"},
        {t:"I have fought a good fight, I have finished my course, I have kept the faith.", r:"2 Timothy 4:7 (AKJV)"},
        {t:"And I John saw the holy city, new Jerusalem, coming down from God out of heaven, prepared as a bride adorned for her husband.", r:"Revelation 21:2 (AKJV)"},
        {t:"And after the earthquake a fire; but the LORD was not in the fire: and after the fire a still small voice.", r:"1 Kings 19:12 (AKJV)"}
    ];

    /* ── 10 Kingdom Pillars (rotating display) ── */
    var pillars = [
        {n:"Veil", s:"The Secret Place", v:"Psalm 91:1"},
        {n:"Alfred Browser", s:"The Narrow Gate", v:"Matthew 7:13-14"},
        {n:"Alfred Search", s:"The Lamp unto the Feet", v:"Psalm 119:105"},
        {n:"Alfred AI", s:"The Counselor", v:"John 14:26"},
        {n:"Pulse", s:"The Assembly", v:"Matthew 18:20"},
        {n:"MetaDome", s:"The New Jerusalem", v:"Revelation 21:2"},
        {n:"Voice AI", s:"The Still Small Voice", v:"1 Kings 19:12"},
        {n:"Alfred IDE", s:"The Workman\u2019s Bench", v:"2 Timothy 2:15"},
        {n:"L\u2019Avocat", s:"The Scales of Justice", v:"Proverbs 11:1"},
        {n:"Alfred Linux 7.77", s:"The Foundation Stone", v:"Isaiah 28:16"}
    ];

    /* ── Worship tracks ── */
    var worship = [
        "Shema Yisrael","Most High","Heavens Declare","Light Of The World",
        "Seraphim","Full Of Mercy","Redeemer","Beloved","Shofar",
        "Truth Of The LORD","Yeshua","Your Mercy","Zion",
        "All Honor To Your Name","Kadosh","Baruch HaShem"
    ];

    function pick(a) { return a[Math.floor(Math.random() * a.length)]; }
    function pickByDay(a) { var d = new Date(); return a[(d.getDate() + d.getMonth()) % a.length]; }

    function showVerse(obj) {
        if (!obj || !obj.t) return;
        var v = document.getElementById("ksw-verse");
        var r = document.getElementById("ksw-verse-ref");
        v.textContent = "\u201C" + obj.t + "\u201D";
        r.textContent = "\u2014 " + obj.r;
        v.style.display = "block";
        r.style.display = "block";
    }

    function showWisdom(obj) {
        if (!obj || !obj.t) return;
        var v = document.getElementById("ksw-wisdom");
        var r = document.getElementById("ksw-wisdom-ref");
        v.textContent = "\u201C" + obj.t + "\u201D";
        r.textContent = "\u2014 " + obj.r;
        v.style.display = "block";
        r.style.display = "block";
    }

    function showCornerstone() {
        var el = document.getElementById("ksw-cornerstone");
        if (el) el.textContent = pickByDay(cornerstones);
    }

    function showPillar() {
        var p = pickByDay(pillars);
        var el = document.getElementById("ksw-pillar");
        if (el) el.textContent = "\u25C6 PILLAR " + (pillars.indexOf(p)+1) + ": " + p.n.toUpperCase() + " \u2014 " + p.s + " \u25C6";
    }

    function showWorship() {
        var t = pick(worship);
        var el = document.getElementById("ksw-worship");
        if (el) el.textContent = "\u266A Now playing in the Kingdom: " + t;
    }

    /* ── Pick a category of wisdom based on day of week ── */
    function getDailyCategory() {
        var dow = new Date().getDay();
        var cats = [loveVerses, kingdomVerses, wisdomVerses, strengthVerses, justiceVerses, missionVerses, sabbathVerses];
        return cats[dow];
    }

    /* ── Rotate wisdom verse every 30 seconds ── */
    function startWisdomRotation() {
        var allVerses = [].concat(wisdomVerses, strengthVerses, justiceVerses, loveVerses, kingdomVerses, missionVerses);
        function rotate() {
            showWisdom(pick(allVerses));
        }
        rotate();
        setInterval(rotate, 30000);
    }

    /* ── Rotate cornerstone every 45 seconds ── */
    function startCornerstoneRotation() {
        var idx = 0;
        var el = document.getElementById("ksw-cornerstone");
        function rotate() {
            if (el) {
                el.style.opacity = "0";
                setTimeout(function(){
                    el.textContent = cornerstones[idx];
                    el.style.opacity = "1";
                    idx = (idx + 1) % cornerstones.length;
                }, 400);
            }
        }
        rotate();
        setInterval(rotate, 45000);
    }

    /* ── Add CSS transition for cornerstone fade ── */
    var csEl = document.getElementById("ksw-cornerstone");
    if (csEl) csEl.style.transition = "opacity 0.4s ease";

    /* ═══════════════════════════════════════════════════════════
     * MAIN: Fetch from Daniel Calendar API then render
     * ═══════════════════════════════════════════════════════════ */
    fetch("https://root.com/api/daniel-calendar.php?city=montreal")
        .then(function(r){ return r.json(); })
        .then(function(d){
            var s = d.shabbat || {}, sun = d.sun || {}, heb = d.hebrew || {}, en = d.enochian || {};
            var greg = d.gregorian || {};
            var msg = "", time = "", icon = "\u{1F56F}";
            var isSunday = (greg.dayOfWeek === "Sunday");

            if (s.isShabbat) {
                icon = "\u2728";
                msg = "Shabbat Shalom \u2014 The Sabbath is here. Rest in His presence.";
                time = "Havdalah: " + (s.havdalah || (sun.sunset && sun.sunset.formatted) || "");
                showVerse(d.dailyVerse && d.dailyVerse.text ? {t:d.dailyVerse.text,r:d.dailyVerse.ref||"AKJV"} : pick(sabbathVerses));
            } else if (s.isErevShabbat) {
                icon = "\u{1F56F}";
                var cl = sun.candleLighting || "";
                var ss = (sun.sunset && sun.sunset.formatted) || "";
                msg = "Erev Shabbat \u2014 The sun is going down. Prepare your heart.";
                time = "Candle lighting: " + cl + " \u00B7 Sunset: " + ss + " (Montr\u00e9al)";
                showVerse(d.dailyVerse && d.dailyVerse.text ? {t:d.dailyVerse.text,r:d.dailyVerse.ref||"AKJV"} : pick(sabbathVerses));
            } else {
                var dow = greg.dayOfWeek || "";
                var days = {Sunday:6,Monday:5,Tuesday:4,Wednesday:3,Thursday:2,Friday:1,Saturday:0};
                var left = days[dow];
                if (typeof left === "number" && left > 0) {
                    msg = left + " day" + (left > 1 ? "s" : "") + " until Shabbat \u2014 Work for the Kingdom.";
                    icon = "\u{1F54E}";
                    var ns = s.nextShabbat;
                    if (ns && ns.sunset && ns.sunset.formatted) {
                        time = "Next Shabbat: " + ns.formatted + " \u00B7 Sunset: " + ns.sunset.formatted + " \u00B7 Candle lighting: " + (ns.candleLighting || "") + " (Montr\u00e9al)";
                    } else {
                        time = "Next Friday sunset in Montr\u00e9al";
                    }
                }
                /* Daily verse from API or day-appropriate fallback */
                if (d.dailyVerse && d.dailyVerse.text) {
                    showVerse({t:d.dailyVerse.text,r:d.dailyVerse.ref||"AKJV"});
                } else if (isSunday) {
                    showVerse(pick(loveVerses));
                } else {
                    showVerse(pick(getDailyCategory()));
                }
            }

            if (msg) {
                document.getElementById("ksw-icon").innerHTML = icon;
                document.getElementById("ksw-msg").textContent = msg;
                document.getElementById("ksw-time").textContent = time;

                /* Tri-calendar date string */
                var ds = "";
                if (heb.formatted) ds += heb.formatted;
                if (greg.formatted) ds += " \u00B7 " + greg.formatted;
                if (en.formatted) ds += " \u00B7 " + en.formatted;
                var feast = d.activeFeast && d.activeFeast.name;
                if (feast) ds += " \u00B7 \u2728 " + feast;
                document.getElementById("ksw-date").textContent = ds;

                showWorship();
                showPillar();
                startCornerstoneRotation();
                startWisdomRotation();
                w.style.display = "block";
            }
        })
        .catch(function(){
            /* ── API fallback — still show widget with hardcoded content ── */
            var now = new Date();
            var dow = now.getDay();
            var msg = "", icon = "\u{1F54E}", time = "";

            if (dow === 6) {
                icon = "\u2728";
                msg = "Shabbat Shalom \u2014 The Sabbath is here. Rest in His presence.";
                showVerse(pick(sabbathVerses));
            } else if (dow === 5) {
                icon = "\u{1F56F}";
                msg = "Erev Shabbat \u2014 The sun is going down. Prepare your heart.";
                showVerse(pick(sabbathVerses));
            } else {
                var left = (6 - dow) % 7;
                if (left === 0) left = 7;
                msg = left + " day" + (left > 1 ? "s" : "") + " until Shabbat \u2014 Work for the Kingdom.";
                if (dow === 0) {
                    showVerse(pick(loveVerses));
                } else {
                    showVerse(pick(getDailyCategory()));
                }
            }

            if (msg) {
                document.getElementById("ksw-icon").innerHTML = icon;
                document.getElementById("ksw-msg").textContent = msg;
                document.getElementById("ksw-time").textContent = time;

                showWorship();
                showPillar();
                startCornerstoneRotation();
                startWisdomRotation();
                w.style.display = "block";
            }
        });
})();
</script>
