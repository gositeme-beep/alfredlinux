<!-- ═══════════════════════════════════════════════════════════ -->
<!-- SHABBAT BANNER — Kingdom of God Edition — Alfred Linux    -->
<!-- Glorifying Jesus Christ / Yeshua HaMashiach               -->
<!-- ═══════════════════════════════════════════════════════════ -->
<div id="shabbat-banner" style="display:none; background:linear-gradient(135deg,#0a0a1a 0%,#1a1a2e 30%,#16213e 60%,#0f3460 100%); border-top:3px solid #f6c343; border-bottom:1px solid rgba(246,195,67,0.3); padding:0; text-align:center; font-family:Georgia,'Times New Roman',serif; color:#e8d5b7; position:relative; z-index:100; overflow:hidden;">
<!-- Golden cross glow background -->
<div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-size:12rem;opacity:0.03;color:#f6c343;pointer-events:none;">&#10013;</div>
<div style="max-width:960px;margin:0 auto;padding:28px 24px 24px;">
<!-- Kingdom greeting -->
<div style="font-size:0.6rem;letter-spacing:5px;text-transform:uppercase;color:rgba(246,195,67,0.35);margin-bottom:4px;font-variant:small-caps;">Kingdom of God Edition</div>
<!-- Top ornament -->
<div style="font-size:0.75rem;letter-spacing:6px;text-transform:uppercase;color:rgba(246,195,67,0.5);margin-bottom:8px;">&#9733; &#9733; &#9733;</div>
<!-- Main icon -->
<div id="shabbat-icon" style="font-size:2.8rem;margin-bottom:8px;text-shadow:0 0 30px rgba(246,195,67,0.3);">&#x2728;</div>
<!-- Main message -->
<div id="shabbat-msg" style="font-size:1.5rem;font-weight:700;letter-spacing:0.5px;line-height:1.4;color:#f0e6d0;text-shadow:0 1px 8px rgba(0,0,0,0.4);"></div>
<!-- Havdalah / candle lighting / time info -->
<div id="shabbat-time" style="font-size:0.95rem;margin-top:8px;color:#f6c343;opacity:0.95;font-style:italic;"></div>
<!-- Scripture verse (blockquote style with gold left border) -->
<div id="shabbat-verse" style="font-size:1.05rem;margin-top:16px;padding:12px 20px;background:rgba(246,195,67,0.06);border-left:3px solid rgba(246,195,67,0.4);border-radius:0 6px 6px 0;color:#d4c4a0;font-style:italic;line-height:1.5;display:none;"></div>
<div id="shabbat-verse-ref" style="font-size:0.8rem;margin-top:4px;color:#f6c343;opacity:0.7;display:none;"></div>
<!-- Tri-calendar date line: Hebrew + Gregorian + Enochian + Feast -->
<div id="shabbat-date" style="font-size:0.82rem;margin-top:14px;color:#a8a8b3;letter-spacing:0.3px;"></div>
<!-- Worship music reference -->
<div id="shabbat-worship" style="font-size:0.72rem;margin-top:10px;color:rgba(212,196,160,0.5);letter-spacing:0.5px;"></div>
<!-- Bottom seal -->
<div style="margin-top:10px;font-size:0.65rem;letter-spacing:4px;color:rgba(246,195,67,0.25);">&#9849; SOLI DEO GLORIA &#9849;</div>
</div>
</div>
<script>
(function(){
    var b = document.getElementById("shabbat-banner");
    if (!b) return;

    /* ── Hardcoded fallback scripture arrays ── */
    var sabbathScriptures = [
        {text:"Remember the sabbath day, to keep it holy.", ref:"Exodus 20:8 (AKJV)"},
        {text:"The LORD blessed the sabbath day, and hallowed it.", ref:"Exodus 20:11 (AKJV)"},
        {text:"It is a sign between me and the children of Israel for ever.", ref:"Exodus 31:17 (AKJV)"},
        {text:"Ye shall keep my sabbaths, and reverence my sanctuary: I am the LORD.", ref:"Leviticus 19:30 (AKJV)"},
        {text:"If thou turn away thy foot from the sabbath\u2026 then shalt thou delight thyself in the LORD.", ref:"Isaiah 58:13\u201314 (AKJV)"},
        {text:"There remaineth therefore a rest to the people of God.", ref:"Hebrews 4:9 (AKJV)"},
        {text:"Six days shall work be done: but the seventh day is the sabbath of rest.", ref:"Leviticus 23:3 (AKJV)"}
    ];
    var weekdayScriptures = [
        {text:"This is the day which the LORD hath made; we will rejoice and be glad in it.", ref:"Psalm 118:24 (AKJV)"},
        {text:"The heavens declare the glory of God; and the firmament sheweth his handywork.", ref:"Psalm 19:1 (AKJV)"},
        {text:"Trust in the LORD with all thine heart; and lean not unto thine own understanding.", ref:"Proverbs 3:5 (AKJV)"},
        {text:"Be strong and of a good courage; be not afraid\u2026 for the LORD thy God is with thee.", ref:"Joshua 1:9 (AKJV)"},
        {text:"The LORD is my shepherd; I shall not want.", ref:"Psalm 23:1 (AKJV)"},
        {text:"I can do all things through Christ which strengtheneth me.", ref:"Philippians 4:13 (AKJV)"},
        {text:"For God so loved the world, that he gave his only begotten Son, that whosoever believeth in him should not perish, but have everlasting life.", ref:"John 3:16 (AKJV)"}
    ];
    var sundayVerse = {text:"Love thy neighbour as thyself.", ref:"Mark 12:31 (AKJV)"};

    /* ── 14 Kingdom worship tracks ── */
    var worshipTracks = [
        "Shema Yisrael","Most High","Heavens Declare","Light Of The World",
        "Seraphim","Full Of Mercy","Redeemer","Beloved",
        "Shofar","Truth Of The LORD","Yeshua","Your Mercy",
        "Zion","All Honor To Your Name"
    ];

    function pickRandom(arr) {
        return arr[Math.floor(Math.random() * arr.length)];
    }

    function showVerse(verseObj) {
        if (!verseObj || !verseObj.text) return;
        var vEl = document.getElementById("shabbat-verse");
        var rEl = document.getElementById("shabbat-verse-ref");
        vEl.textContent = "\u201C" + verseObj.text + "\u201D";
        rEl.textContent = "\u2014 " + (verseObj.ref || "AKJV");
        vEl.style.display = "block";
        rEl.style.display = "block";
    }

    function showWorship() {
        var track = pickRandom(worshipTracks);
        var el = document.getElementById("shabbat-worship");
        if (el) el.textContent = "\u266A Now playing in the Kingdom: " + track;
    }

    fetch("https://gositeme.com/api/daniel-calendar.php?city=montreal")
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
            } else if (s.isErevShabbat) {
                icon = "\u{1F56F}";
                var cl = sun.candleLighting || "";
                var ss = (sun.sunset && sun.sunset.formatted) || "";
                msg = "Erev Shabbat \u2014 The sun is going down. Prepare your heart.";
                time = "Candle lighting: " + cl + " \u00B7 Sunset: " + ss + " (Montr\u00e9al)";
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
            }

            if (msg) {
                document.getElementById("shabbat-icon").innerHTML = icon;
                document.getElementById("shabbat-msg").textContent = msg;
                document.getElementById("shabbat-time").textContent = time;

                /* ── Scripture: Sunday override → API dailyVerse → hardcoded fallback ── */
                if (isSunday) {
                    showVerse(sundayVerse);
                } else if (d.dailyVerse && d.dailyVerse.text) {
                    showVerse(d.dailyVerse);
                } else {
                    if (s.isShabbat || s.isErevShabbat) {
                        showVerse(pickRandom(sabbathScriptures));
                    } else {
                        showVerse(pickRandom(weekdayScriptures));
                    }
                }

                /* ── Tri-calendar date: Hebrew + Gregorian + Enochian + Feast ── */
                var ds = "";
                if (heb.formatted) ds += heb.formatted;
                if (greg.formatted) ds += " \u00B7 " + greg.formatted;
                if (en.formatted) ds += " \u00B7 " + en.formatted;
                var feast = d.activeFeast && d.activeFeast.name;
                if (feast) ds += " \u00B7 \u2728 " + feast;
                document.getElementById("shabbat-date").textContent = ds;

                showWorship();
                b.style.display = "block";
            }
        })
        .catch(function(){
            /* ── API failed — show with hardcoded content ── */
            var now = new Date();
            var dow = now.getDay();
            var msg = "", icon = "\u{1F54E}", time = "";

            if (dow === 6) {
                icon = "\u2728";
                msg = "Shabbat Shalom \u2014 The Sabbath is here. Rest in His presence.";
                showVerse(pickRandom(sabbathScriptures));
            } else if (dow === 5) {
                icon = "\u{1F56F}";
                msg = "Erev Shabbat \u2014 The sun is going down. Prepare your heart.";
                showVerse(pickRandom(sabbathScriptures));
            } else {
                var left = (6 - dow) % 7;
                if (left === 0) left = 7;
                msg = left + " day" + (left > 1 ? "s" : "") + " until Shabbat \u2014 Work for the Kingdom.";
                if (dow === 0) {
                    showVerse(sundayVerse);
                } else {
                    showVerse(pickRandom(weekdayScriptures));
                }
            }

            if (msg) {
                document.getElementById("shabbat-icon").innerHTML = icon;
                document.getElementById("shabbat-msg").textContent = msg;
                document.getElementById("shabbat-time").textContent = time;
                showWorship();
                b.style.display = "block";
            }
        });
})();
</script>
