const API = '/api/alfred-search.php';
let mode = window._searchMode || 'all';
let page = 1;
let suggestTimer = null;

const $in = document.getElementById('asInput');
const $land = document.getElementById('asLanding');
const $load = document.getElementById('asLoading');
const $res = document.getElementById('asResults');
const $sug = document.getElementById('asSuggest');
const $voice = document.getElementById('asVoice');

// Modes
document.querySelectorAll('.as-mode').forEach(m => {
    if (m.dataset.mode === mode) m.classList.add('active');
    m.addEventListener('click', () => {
        document.querySelectorAll('.as-mode').forEach(x => x.classList.remove('active'));
        m.classList.add('active');
        mode = m.dataset.mode;
        if ($in.value.trim()) doSearch();
    });
});

// Input Events
$in.addEventListener('keydown', e => {
    if (e.key === 'Enter') { $sug.classList.remove('open'); doSearch(); }
    if (e.key === 'Escape') $sug.classList.remove('open');
});
$in.addEventListener('input', () => {
    clearTimeout(suggestTimer);
    const q = $in.value.trim();
    if (q.length < 2) { $sug.classList.remove('open'); return; }
    suggestTimer = setTimeout(() => fetchSuggestions(q), 200);
});
document.addEventListener('click', e => {
    if (!e.target.closest('.as-search-zone')) $sug.classList.remove('open');
});

// Voice
let voiceActive = false;
let recorder = null;
let chunks = [];
const STT = window.SpeechRecognition || window.webkitSpeechRecognition;

function toggleVoice() {
    if (voiceActive) { stopVoice(); return; }
    if (STT) { browserSTT(); } else { whisperRecord(); }
}
function browserSTT() {
    const r = new STT();
    r.continuous = false;
    r.interimResults = true;
    r.lang = navigator.language || 'en-US';
    r.onstart = () => { voiceActive = true; $voice.classList.add('recording'); $in.placeholder = 'Listening...'; };
    r.onresult = e => {
        $in.value = Array.from(e.results).map(x => x[0].transcript).join('');
        if (e.results[0].isFinal) { stopVoice(); doSearch(); }
    };
    r.onerror = e => { stopVoice(); if (e.error !== 'not-allowed') whisperRecord(); };
    r.onend = () => stopVoice();
    r.start();
}
function whisperRecord() {
    navigator.mediaDevices.getUserMedia({ audio: true }).then(stream => {
        voiceActive = true;
        $voice.classList.add('recording');
        $in.placeholder = 'Recording — tap mic to stop';
        chunks = [];
        recorder = new MediaRecorder(stream, { mimeType: 'audio/webm' });
        recorder.ondataavailable = e => { if (e.data.size > 0) chunks.push(e.data); };
        recorder.onstop = async () => {
            stream.getTracks().forEach(t => t.stop());
            const blob = new Blob(chunks, { type: 'audio/webm' });
            if (blob.size < 1000) return;
            $in.placeholder = 'Transcribing...';
            const fd = new FormData();
            fd.append('audio', blob, 'voice.webm');
            try {
                const r = await fetch(API + '?action=voice', { method: 'POST', body: fd });
                const d = await r.json();
                if (d.transcription) { $in.value = d.transcription; doSearch(); }
            } catch (e) { /* silent */ }
            $in.placeholder = 'Search the sovereign web...';
        };
        recorder.start();
    }).catch(() => { stopVoice(); });
}
function stopVoice() {
    voiceActive = false;
    $voice.classList.remove('recording');
    $in.placeholder = 'Search the sovereign web...';
    if (recorder && recorder.state === 'recording') recorder.stop();
}

// Suggestions
async function fetchSuggestions(q) {
    try {
        const r = await fetch(API + '?action=suggest&q=' + encodeURIComponent(q));
        const d = await r.json();
        if (d.suggestions && d.suggestions.length > 0) {
            $sug.innerHTML = d.suggestions.map(s =>
                '<div class="as-suggest-item" onclick="pickSuggest(\x27' + esc(s.text).replace(/'/g, "\\'") + '\x27)">' +
                    '<i class="' + (s.icon || 'fas fa-search') + '"></i>' +
                    '<span>' + esc(s.text) + '</span>' +
                '</div>'
            ).join('');
            $sug.classList.add('open');
        } else {
            $sug.classList.remove('open');
        }
    } catch(e) { $sug.classList.remove('open'); }
}
function pickSuggest(t) { $in.value = t; $sug.classList.remove('open'); doSearch(); }

// Search
async function doSearch(p) {
    p = p || 1;
    const q = $in.value.trim();
    if (!q) return;
    page = p;
    $sug.classList.remove('open');

    if (mode === 'emergency') {
        renderEmergency(q);
        return;
    }

    var url = new URL(window.location);
    url.searchParams.set('q', q);
    url.searchParams.set('mode', mode);
    if (p > 1) url.searchParams.set('page', p); else url.searchParams.delete('page');
    history.pushState({}, '', url);

    $land.classList.add('collapsed');
    $res.classList.remove('visible');
    $load.classList.add('active');

    try {
        const r = await fetch(API + '?q=' + encodeURIComponent(q) + '&mode=' + mode + '&page=' + p);
        const d = await r.json();
        $load.classList.remove('active');
        renderResults(d);
        awardSearchReward(); // Earn GSM for searching
    } catch (e) {
        $load.classList.remove('active');
        $res.innerHTML = '<div style="text-align:center;padding:48px;color:var(--as-dim);">' +
            '<i class="fas fa-satellite-dish" style="font-size:36px;opacity:0.2;"></i>' +
            '<p style="margin-top:14px;">Signal lost. Retrying...</p></div>';
        $res.classList.add('visible');
    }
}

function renderResults(d) {
    var h = '';

    h += '<div class="as-results-meta">' +
        '<span>' + (d.total || 0) + ' results — ' + ((d.response_ms || 0) / 1000).toFixed(2) + 's' +
        (d.cost_usd ? ' — $' + d.cost_usd.toFixed(6) + ' cost' : '') + '</span>' +
        '<span class="as-privacy-tag"><i class="fas fa-shield-alt"></i> Zero data collected</span></div>';

    if (d.instant_answer) {
        h += '<div class="as-instant">' +
            '<div class="as-instant-header"><i class="fas fa-brain"></i> Alfred Intelligence</div>' +
            '<div class="as-instant-body">' + fmtAnswer(d.instant_answer) + '</div></div>';
    }

    if (d.results && d.results.length > 0) {
        d.results.forEach(function(r) {
            var fav = r.source ? 'https://www.google.com/s2/favicons?domain=' + encodeURIComponent(r.source) + '&sz=32' : '';
            var tags = '';
            if (r.type && r.type !== 'web') {
                var cls = r.type === 'tool' ? 'tool' : r.type === 'news' ? 'news' : r.type === 'article' ? 'article' : 'sovereign';
                tags += '<span class="as-tag as-tag-' + cls + '">' + r.type + '</span>';
            }
            if (r.rank_reason && r.rank_reason.indexOf('sovereign') !== -1) {
                tags += '<span class="as-tag as-tag-sovereign">sovereign index</span>';
            }
            h += '<div class="as-result">' +
                '<div class="as-result-source">' +
                (fav ? '<img src="' + fav + '" alt="" loading="lazy">' : '') +
                '<span>' + esc(r.source || '') + '</span></div>' +
                '<a class="as-result-title" href="' + esc(r.url) + '" target="_blank" rel="noopener">' + esc(r.title) + '</a>' +
                '<div class="as-result-snippet">' + hlQuery(esc(r.snippet || ''), d.query) + '</div>' +
                '<div class="as-result-tags">' + tags +
                (r.rank_reason ? '<span style="font-size:10px;color:var(--as-mute);">' + esc(r.rank_reason) + '</span>' : '') +
                '</div></div>';
        });
    } else {
        h += '<div style="text-align:center;padding:48px;color:var(--as-dim);">' +
            '<i class="fas fa-telescope" style="font-size:32px;opacity:0.2;"></i>' +
            '<p style="margin-top:12px;">No results found in the sovereign index. Try different terms.</p></div>';
    }

    if (d.total > 0 && mode !== 'deep') {
        h += '<div class="as-deep-cta" onclick="setMode(\'deep\')">' +
            '<i class="fas fa-microscope"></i>' +
            '<div><h4>Go deeper with Alfred</h4>' +
            '<p>Multi-source deep research — Alfred analyzes, cross-references, and generates a comprehensive report.</p></div></div>';
    }

    if (d.pages > 1) {
        h += '<div class="as-pages">';
        if (d.page > 1) h += '<button class="as-page-btn" onclick="doSearch(' + (d.page-1) + ')"><i class="fas fa-chevron-left"></i></button>';
        for (var i = 1; i <= Math.min(d.pages, 10); i++) {
            h += '<button class="as-page-btn ' + (i===d.page?'current':'') + '" onclick="doSearch(' + i + ')">' + i + '</button>';
        }
        if (d.page < d.pages) h += '<button class="as-page-btn" onclick="doSearch(' + (d.page+1) + ')"><i class="fas fa-chevron-right"></i></button>';
        h += '</div>';
    }

    $res.innerHTML = h;
    $res.classList.add('visible');
}

// Emergency Mode
function renderEmergency(q) {
    $land.classList.add('collapsed');
    $load.classList.remove('active');
    $res.innerHTML =
    '<div style="padding:32px 0;">' +
        '<div class="as-instant" style="border-color:rgba(239,68,68,0.2);background:linear-gradient(135deg,rgba(239,68,68,0.06),rgba(251,191,36,0.06));">' +
            '<div class="as-instant-header" style="color:var(--as-red);"><i class="fas fa-broadcast-tower"></i> EMERGENCY MODE</div>' +
            '<div class="as-instant-body">' +
                '<p><strong>Emergency search for: ' + esc(q) + '</strong></p>' +
                '<p>Emergency mode activates sovereign-priority search with cached results, offline fallbacks, and critical information prioritization.</p>' +
                '<br>' +
                '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;">' +
                    '<a href="/emergency-kit" style="display:flex;align-items:center;gap:8px;padding:12px;border-radius:12px;background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.15);color:var(--as-text);text-decoration:none;">' +
                        '<i class="fas fa-first-aid" style="color:var(--as-red);font-size:18px;"></i>' +
                        '<div><strong style="font-size:13px;">Emergency Kit</strong><br><span style="font-size:11px;color:var(--as-dim);">Survival guides, offline</span></div></a>' +
                    '<a href="/emergency-kit#comms" style="display:flex;align-items:center;gap:8px;padding:12px;border-radius:12px;background:rgba(251,191,36,0.08);border:1px solid rgba(251,191,36,0.15);color:var(--as-text);text-decoration:none;">' +
                        '<i class="fas fa-satellite" style="color:var(--as-amber);font-size:18px;"></i>' +
                        '<div><strong style="font-size:13px;">Mesh Comms</strong><br><span style="font-size:11px;color:var(--as-dim);">P2P communication</span></div></a>' +
                    '<a href="/emergency-kit#maps" style="display:flex;align-items:center;gap:8px;padding:12px;border-radius:12px;background:rgba(52,211,153,0.08);border:1px solid rgba(52,211,153,0.15);color:var(--as-text);text-decoration:none;">' +
                        '<i class="fas fa-map-marked-alt" style="color:var(--as-green);font-size:18px;"></i>' +
                        '<div><strong style="font-size:13px;">Offline Maps</strong><br><span style="font-size:11px;color:var(--as-dim);">Cached map tiles</span></div></a>' +
                    '<a href="/emergency-kit#medical" style="display:flex;align-items:center;gap:8px;padding:12px;border-radius:12px;background:rgba(91,156,245,0.08);border:1px solid rgba(91,156,245,0.15);color:var(--as-text);text-decoration:none;">' +
                        '<i class="fas fa-heartbeat" style="color:var(--as-blue);font-size:18px;"></i>' +
                        '<div><strong style="font-size:13px;">Medical Guide</strong><br><span style="font-size:11px;color:var(--as-dim);">First aid, triage</span></div></a>' +
                '</div>' +
            '</div></div></div>';
    $res.classList.add('visible');

    var savedMode = mode;
    mode = 'web';
    fetch(API + '?q=' + encodeURIComponent(q) + '&mode=web&page=1').then(function(r){return r.json();}).then(function(d) {
        if (d.results && d.results.length > 0) {
            var extra = '<div style="margin-top:24px;"><h3 style="color:var(--as-amber);font-size:14px;margin-bottom:12px;"><i class="fas fa-search"></i> Related Web Results</h3>';
            d.results.slice(0, 5).forEach(function(r) {
                extra += '<div class="as-result">' +
                    '<a class="as-result-title" href="' + esc(r.url) + '" target="_blank" rel="noopener">' + esc(r.title) + '</a>' +
                    '<div class="as-result-snippet">' + esc((r.snippet||'').substring(0,200)) + '</div></div>';
            });
            extra += '</div>';
            $res.innerHTML += extra;
        }
    }).catch(function(){});
    mode = savedMode;
}

function setMode(m) {
    document.querySelectorAll('.as-mode').forEach(function(x) {
        x.classList.toggle('active', x.dataset.mode === m);
    });
    mode = m;
    doSearch();
}

// Helpers
function fmtAnswer(t) {
    return t.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.+?)\*/g, '<em>$1</em>')
            .replace(/`(.+?)`/g, '<code>$1</code>')
            .replace(/\n/g, '<br>');
}
function hlQuery(text, q) {
    if (!q) return text;
    var words = q.split(/\s+/).filter(function(w){ return w.length > 2; });
    var r = text;
    words.forEach(function(w) {
        var rx = new RegExp('(' + w.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
        r = r.replace(rx, '<mark>$1</mark>');
    });
    return r;
}
function esc(s) { return GDS.esc(s); }

// ── Search Rewards & Wallet ─────────────────────────────────────
var searchRewardPending = false;
function awardSearchReward() {
    if (searchRewardPending) return;
    searchRewardPending = true;
    fetch('/api/mining.php?action=search_reward', {
        method: 'POST', credentials: 'same-origin',
        headers: {'Content-Type': 'application/json'}
    }).then(function(r){return r.json();}).then(function(d) {
        searchRewardPending = false;
        if (d.ok && d.reward > 0) {
            loadNavBalance();
        }
    }).catch(function() { searchRewardPending = false; });
}

function loadNavBalance() {
    fetch('/api/mining.php?action=wallet', {credentials: 'same-origin'})
    .then(function(r){return r.json();}).then(function(d) {
        if (d.ok && d.wallet) {
            var el = document.getElementById('navWallet');
            var bal = document.getElementById('navBalance');
            if (el && bal) {
                bal.textContent = d.wallet.balance.toFixed(4);
                el.style.display = '';
            }
        }
    }).catch(function(){});
}

// Init — fetch index count + wallet
fetch(API + '?action=stats').then(function(r){return r.json();}).then(function(d) {
    var el = document.getElementById('asIndexCount');
    if (el && d.web_index_documents !== undefined) {
        el.textContent = d.web_index_documents > 0
            ? (d.web_index_documents > 999 ? Math.floor(d.web_index_documents/1000)+'K' : d.web_index_documents)
            : 'New';
    }
}).catch(function(){});

loadNavBalance();

if ($in.value.trim()) doSearch();
