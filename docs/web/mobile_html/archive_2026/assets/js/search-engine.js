/**
 * Alfred Search Engine v2 — Infinite Scroll Edition
 * ──────────────────────────────────────────────────
 * - Real infinite scroll with IntersectionObserver
 * - Time-based filtering
 * - Related searches
 * - Skeleton loading cards
 * - Back-to-top + page indicator on scroll
 * - Keyboard navigation (j/k for results)
 * - Result numbering across pages
 */
const API = '/api/alfred-search.php';

// ── State ────────────────────────────────────────────────────
let mode = window._searchMode || 'web';
let page = 1;
let timeFilter = '';
let suggestTimer = null;
let isLoading = false;
let hasMore = false;
let totalLoaded = 0;
let lastQuery = '';
let lastTotalResults = 0;
let scrollObserver = null;
let pageIndicatorTimer = null;
let seenUrls = new Set();  // Deduplicate across infinite scroll pages

// ── DOM refs ─────────────────────────────────────────────────
const $in = document.getElementById('asInput');
const $land = document.getElementById('asLanding');
const $load = document.getElementById('asLoading');
const $res = document.getElementById('asResults');
const $pager = document.getElementById('asPager');
const $sug = document.getElementById('asSuggest');
const $voice = document.getElementById('asVoice');
const $filters = document.getElementById('asFilters');
const $loadMore = document.getElementById('asLoadMore');
const $loadMoreBtn = document.getElementById('asLoadMoreBtn');
const $scrollSpinner = document.getElementById('asScrollSpinner');
const $endMsg = document.getElementById('asEndMsg');
const $related = document.getElementById('asRelated');
const $relatedGrid = document.getElementById('asRelatedGrid');
const $backTop = document.getElementById('asBackTop');
const $pageIndicator = document.getElementById('asPageIndicator');

// ── Mode Switching ───────────────────────────────────────────
function setMode(nextMode) {
    mode = nextMode;
    document.querySelectorAll('.as-mode').forEach(function(m) {
        m.classList.toggle('active', m.dataset.mode === nextMode);
    });
    if ($in.value.trim()) doSearch();
}
window.setMode = setMode;

document.querySelectorAll('.as-mode').forEach(function(m) {
    if (m.dataset.mode === mode) m.classList.add('active');
    m.addEventListener('click', function() {
        setMode(m.dataset.mode);
    });
});

// ── Input Events ─────────────────────────────────────────────
$in.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { $sug.classList.remove('open'); doSearch(); }
    if (e.key === 'Escape') $sug.classList.remove('open');
});
$in.addEventListener('input', function() {
    clearTimeout(suggestTimer);
    var q = $in.value.trim();
    if (q.length < 2) { $sug.classList.remove('open'); return; }
    suggestTimer = setTimeout(function() { fetchSuggestions(q); }, 200);
});
document.addEventListener('click', function(e) {
    if (!e.target.closest('.as-search-zone')) $sug.classList.remove('open');
});

// ── Voice Search ─────────────────────────────────────────────
var voiceActive = false;
var recorder = null;
var chunks = [];
var STT = window.SpeechRecognition || window.webkitSpeechRecognition;

function toggleVoice() {
    if (voiceActive) { stopVoice(); return; }
    if (STT) { browserSTT(); } else { whisperRecord(); }
}
function browserSTT() {
    var r = new STT();
    r.continuous = false;
    r.interimResults = true;
    r.lang = navigator.language || 'en-US';
    r.onstart = function() { voiceActive = true; $voice.classList.add('recording'); $in.placeholder = 'Listening...'; };
    r.onresult = function(e) {
        $in.value = Array.from(e.results).map(function(x) { return x[0].transcript; }).join('');
        if (e.results[0].isFinal) { stopVoice(); doSearch(); }
    };
    r.onerror = function(e) { stopVoice(); if (e.error !== 'not-allowed') whisperRecord(); };
    r.onend = function() { stopVoice(); };
    r.start();
}
function whisperRecord() {
    navigator.mediaDevices.getUserMedia({ audio: true }).then(function(stream) {
        voiceActive = true;
        $voice.classList.add('recording');
        $in.placeholder = 'Recording \u2014 tap mic to stop';
        chunks = [];
        recorder = new MediaRecorder(stream, { mimeType: 'audio/webm' });
        recorder.ondataavailable = function(e) { if (e.data.size > 0) chunks.push(e.data); };
        recorder.onstop = function() {
            stream.getTracks().forEach(function(t) { t.stop(); });
            var blob = new Blob(chunks, { type: 'audio/webm' });
            if (blob.size < 1000) return;
            $in.placeholder = 'Transcribing...';
            var fd = new FormData();
            fd.append('audio', blob, 'voice.webm');
            fetch(API + '?action=voice', { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    if (d.transcription) { $in.value = d.transcription; doSearch(); }
                })
                .catch(function() {})
                .finally(function() { $in.placeholder = 'Search the sovereign web...'; });
        };
        recorder.start();
    }).catch(function() { stopVoice(); });
}
function stopVoice() {
    voiceActive = false;
    $voice.classList.remove('recording');
    $in.placeholder = 'Search the sovereign web...';
    if (recorder && recorder.state === 'recording') recorder.stop();
}

// ── Suggestions ──────────────────────────────────────────────
function fetchSuggestions(q) {
    fetch(API + '?action=suggest&q=' + encodeURIComponent(q))
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.suggestions && d.suggestions.length > 0) {
                $sug.innerHTML = d.suggestions.map(function(s) {
                    return '<div class="as-suggest-item" onclick="pickSuggest(\x27' + esc(s.text).replace(/'/g, "\\'") + '\x27)">' +
                        '<i class="' + (s.icon || 'fas fa-search') + '"></i>' +
                        '<span>' + esc(s.text) + '</span>' +
                    '</div>';
                }).join('');
                $sug.classList.add('open');
            } else {
                $sug.classList.remove('open');
            }
        })
        .catch(function() { $sug.classList.remove('open'); });
}
function pickSuggest(t) { $in.value = t; $sug.classList.remove('open'); doSearch(); }

// ── Time Filter ──────────────────────────────────────────────
function setTimeFilter(btn, tf) {
    timeFilter = tf;
    document.querySelectorAll('.as-filter-btn').forEach(function(b) { b.classList.remove('active'); });
    btn.classList.add('active');
    if ($in.value.trim()) doSearch();
}
// Expose globally
window.setTimeFilter = setTimeFilter;

// ── Skeleton Loader ──────────────────────────────────────────
function renderSkeletons(count) {
    var h = '';
    for (var i = 0; i < count; i++) {
        h += '<div class="as-skeleton">' +
            '<div class="as-skel-line source"></div>' +
            '<div class="as-skel-line title"></div>' +
            '<div class="as-skel-line text1"></div>' +
            '<div class="as-skel-line text2"></div>' +
            '<div class="as-skel-line text3"></div>' +
        '</div>';
    }
    return h;
}

// ── Core Search ──────────────────────────────────────────────
function doSearch(p) {
    var q = $in.value.trim();
    if (!q) return;
    if (isLoading) return;

    var isNewSearch = !p || p === 1 || q !== lastQuery;
    page = isNewSearch ? 1 : p;
    lastQuery = q;
    $sug.classList.remove('open');

    if (mode === 'emergency') {
        totalLoaded = 0;
        renderEmergency(q);
        return;
    }

    // Update URL
    var url = new URL(window.location);
    url.searchParams.set('q', q);
    url.searchParams.set('mode', mode);
    if (timeFilter) url.searchParams.set('time', timeFilter);
    else url.searchParams.delete('time');
    url.searchParams.delete('page');
    history.replaceState({}, '', url);

    isLoading = true;

    if (isNewSearch) {
        // Fresh search — show skeletons + collapse landing
        totalLoaded = 0;
        seenUrls = new Set();
        $land.classList.add('collapsed');
        $filters.classList.add('visible');
        $res.innerHTML = renderSkeletons(5);
        $res.classList.add('visible');
        if ($pager) $pager.innerHTML = '';
        $loadMore.classList.remove('visible');
        $endMsg.classList.remove('visible');
        $related.classList.remove('visible');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else {
        // Infinite scroll — show spinner at bottom
        $scrollSpinner.classList.add('active');
        $loadMoreBtn.style.display = 'none';
    }

    var apiUrl = API + '?q=' + encodeURIComponent(q) + '&mode=' + mode + '&page=' + page;
    if (timeFilter) apiUrl += '&time=' + timeFilter;

    fetch(apiUrl)
        .then(function(r) { return r.json(); })
        .then(function(d) {
            isLoading = false;
            $load.classList.remove('active');
            $scrollSpinner.classList.remove('active');

            if (isNewSearch) {
                renderFreshResults(d);
            } else {
                appendResults(d);
            }

            page = d.page || page;
            lastTotalResults = d.total || 0;
            hasMore = d.has_more || false;
            updateLoadMoreState();
            updatePagerState();

            if (isNewSearch && page === 1) {
                awardSearchReward();
            }
        })
        .catch(function(e) {
            isLoading = false;
            $load.classList.remove('active');
            $scrollSpinner.classList.remove('active');
            if (isNewSearch) {
                $res.innerHTML = '<div style="text-align:center;padding:48px;color:var(--as-dim);">' +
                    '<i class="fas fa-satellite-dish" style="font-size:36px;opacity:0.2;"></i>' +
                    '<p style="margin-top:14px;">Signal lost. Please try again.</p></div>';
                $res.classList.add('visible');
            }
        });
}

// ── Render Fresh Results (page 1) ────────────────────────────
function renderFreshResults(d) {
    var h = '';

    // Results header
    h += '<div class="as-results-meta">' +
        '<span>' + (d.total || 0) + ' results \u2014 ' + ((d.response_ms || 0) / 1000).toFixed(2) + 's' +
        (d.cost_usd ? ' \u2014 $' + d.cost_usd.toFixed(6) : '') + '</span>' +
        '<span class="as-privacy-tag"><i class="fas fa-shield-alt"></i> Zero tracking</span></div>';

    // Instant answer
    if (d.instant_answer) {
        h += '<div class="as-instant">' +
            '<div class="as-instant-header"><i class="fas fa-brain"></i> Alfred Intelligence</div>' +
            '<div class="as-instant-body">' + fmtAnswer(d.instant_answer) + '</div></div>';
    }

    if (d.first_party_cards && d.first_party_cards.length > 0) {
        h += renderFirstPartyCards(d.first_party_cards);
    }

    // Results
    if (d.results && d.results.length > 0) {
        h += renderResultCards(d.results, d.query, 0);
        totalLoaded = d.results.length;
    } else {
        h += '<div style="text-align:center;padding:48px;color:var(--as-dim);">' +
            '<i class="fas fa-telescope" style="font-size:32px;opacity:0.2;"></i>' +
            '<p style="margin-top:12px;">No results found. Try different keywords or remove time filters.</p></div>';
    }

    // Deep research CTA (only on page 1)
    if (d.total > 0 && mode !== 'deep') {
        h += '<div class="as-deep-cta" onclick="setMode(\'deep\')">' +
            '<i class="fas fa-microscope"></i>' +
            '<div><h4>Go deeper with Alfred</h4>' +
            '<p>Multi-source deep research \u2014 analyzes, cross-references, and generates a comprehensive report.</p></div></div>';
    }

    $res.innerHTML = h;
    $res.classList.add('visible');

    // Related searches
    renderRelated(d.related || []);
}

// ── Append Results (infinite scroll page 2+) ─────────────────
function appendResults(d) {
    if (!d.results || d.results.length === 0) {
        hasMore = false;
        updateLoadMoreState();
        return;
    }

    // Remove deep-cta before appending (re-add at end)
    var cta = $res.querySelector('.as-deep-cta');
    if (cta) cta.remove();

    var tempDiv = document.createElement('div');
    tempDiv.innerHTML = renderResultCards(d.results, d.query, totalLoaded);
    while (tempDiv.firstChild) {
        $res.appendChild(tempDiv.firstChild);
    }
    totalLoaded += d.results.length;

    // Update result count in header
    var meta = $res.querySelector('.as-results-meta span:first-child');
    if (meta) {
        meta.textContent = totalLoaded + '+ results \u2014 page ' + page;
    }

    // Update related
    renderRelated(d.related || []);

    // Flash page indicator
    showPageIndicator(page);
}

// ── Render Result Cards ──────────────────────────────────────
function renderResultCards(results, query, startIdx) {
    var h = '';
    var actualIdx = startIdx;
    results.forEach(function(r) {
        // Deduplicate across pages
        var normalUrl = (r.url || '').replace(/^https?:\/\/(www\.)?/, '').replace(/\/+$/, '');
        if (seenUrls.has(normalUrl)) return;
        seenUrls.add(normalUrl);
        actualIdx++;
        var num = actualIdx;
        var fav = r.source ? 'https://www.google.com/s2/favicons?domain=' + encodeURIComponent(r.source) + '&sz=32' : '';
        var tags = '';
        if (r.type && r.type !== 'web') {
            var cls = r.type === 'tool' ? 'tool' : r.type === 'news' ? 'news' : r.type === 'article' ? 'article' : 'sovereign';
            tags += '<span class="as-tag as-tag-' + cls + '">' + esc(r.type) + '</span>';
        }
        if (r.rank_reason && r.rank_reason.indexOf('sovereign') !== -1) {
            tags += '<span class="as-tag as-tag-sovereign">sovereign index</span>';
        }

        // Truncate long URLs for display
        var displayUrl = esc(r.source || '');
        var urlPath = '';
        try {
            var parsed = new URL(r.url);
            urlPath = parsed.pathname;
            if (urlPath.length > 60) urlPath = urlPath.substring(0, 57) + '...';
            if (urlPath !== '/') displayUrl += urlPath;
        } catch(e) {}

        h += '<div class="as-result" style="animation-delay:' + ((actualIdx - startIdx - 1) * 0.04) + 's;">' +
            '<div class="as-result-source">' +
                '<span class="as-result-num">' + num + '</span>' +
                (fav ? '<img src="' + fav + '" alt="" loading="lazy">' : '') +
                '<span>' + displayUrl + '</span>' +
            '</div>' +
            '<a class="as-result-title" href="' + esc(r.url) + '" target="_blank" rel="noopener">' + esc(r.title) + '</a>' +
            '<div class="as-result-snippet">' + hlQuery(esc(r.snippet || ''), query) + '</div>' +
            '<div class="as-result-tags">' + tags + '</div>' +
        '</div>';
    });
    return h;
}

function renderFirstPartyCards(cards) {
    return '<div class="as-first-party">' + cards.map(function(card) {
        var links = Array.isArray(card.links) ? card.links : [];
        return '<div class="as-first-party-card">' +
            '<div class="as-first-party-top">' +
                '<div>' +
                    '<div class="as-first-party-eyebrow">' + esc(card.eyebrow || 'First-Party GoSiteMe') + '</div>' +
                    '<a class="as-first-party-title" href="' + esc(card.url || '#') + '" target="_blank" rel="noopener">' + esc(card.title || '') + '</a>' +
                '</div>' +
                '<div class="as-first-party-icon"><i class="' + esc(card.icon || 'fas fa-link') + '"></i></div>' +
            '</div>' +
            '<div class="as-first-party-snippet">' + esc(card.snippet || '') + '</div>' +
            '<div class="as-first-party-links">' + links.map(function(link) {
                return '<a class="as-first-party-link" href="' + esc(link.url || '#') + '" target="_blank" rel="noopener">' + esc(link.label || '') + '</a>';
            }).join('') + '</div>' +
        '</div>';
    }).join('') + '</div>';
}

// ── Load More State Management ───────────────────────────────
function updateLoadMoreState() {
    if (hasMore && totalLoaded > 0) {
        $loadMore.classList.add('visible');
        $loadMoreBtn.innerHTML = '<i class="fas fa-arrow-right"></i> Next Page';
        $loadMoreBtn.style.display = '';
        $endMsg.classList.remove('visible');
        setupScrollObserver();
    } else if (totalLoaded > 0) {
        $loadMore.classList.remove('visible');
        $endMsg.classList.add('visible');
        destroyScrollObserver();
    } else {
        $loadMore.classList.remove('visible');
        $endMsg.classList.remove('visible');
        destroyScrollObserver();
    }
}

function updatePagerState() {
    if (!$pager) return;
    if (totalLoaded <= 0) {
        $pager.innerHTML = '';
        return;
    }

    var summary = 'Page ' + page + ' · ' + totalLoaded + ' shown';
    if (lastTotalResults > 0) summary += ' of ' + lastTotalResults;

    var nextButton = hasMore
        ? '<button class="as-load-more-btn" onclick="loadMore()"><i class="fas fa-arrow-right"></i> Next Page</button>'
        : '';

    $pager.innerHTML = '<div class="as-results-meta" style="padding:0 0 18px;">' +
        '<span>' + summary + '</span>' +
        '<span>' + nextButton + '</span>' +
    '</div>';
}

function loadMore() {
    if (isLoading || !hasMore) return;
    page++;
    doSearch(page);
}
window.loadMore = loadMore;

// ── Infinite Scroll (IntersectionObserver) ───────────────────
function setupScrollObserver() {
    destroyScrollObserver();
    if (!$loadMoreBtn) return;
    scrollObserver = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting && !isLoading && hasMore) {
                loadMore();
            }
        });
    }, { rootMargin: '400px' });
    scrollObserver.observe($loadMoreBtn);
}
function destroyScrollObserver() {
    if (scrollObserver) {
        scrollObserver.disconnect();
        scrollObserver = null;
    }
}

// ── Related Searches ─────────────────────────────────────────
function renderRelated(terms) {
    if (!terms || terms.length === 0) {
        $related.classList.remove('visible');
        return;
    }
    $relatedGrid.innerHTML = terms.map(function(t) {
        return '<a class="as-related-chip" href="#" onclick="searchRelated(\x27' + esc(t).replace(/'/g, "\\'") + '\x27);return false;">' +
            '<i class="fas fa-search" style="font-size:11px;margin-right:4px;opacity:0.5;"></i>' + esc(t) +
        '</a>';
    }).join('');
    $related.classList.add('visible');
}
function searchRelated(term) {
    $in.value = term;
    doSearch();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
window.searchRelated = searchRelated;

// ── Back to Top + Page Indicator ─────────────────────────────
window.addEventListener('scroll', function() {
    var scrollY = window.scrollY || window.pageYOffset;
    if ($backTop) {
        $backTop.classList.toggle('visible', scrollY > 600);
    }
}, { passive: true });

function showPageIndicator(pageNum) {
    if (!$pageIndicator) return;
    $pageIndicator.textContent = 'Page ' + pageNum + ' \u00B7 ' + totalLoaded + ' results loaded';
    $pageIndicator.classList.add('visible');
    clearTimeout(pageIndicatorTimer);
    pageIndicatorTimer = setTimeout(function() {
        $pageIndicator.classList.remove('visible');
    }, 2000);
}

// ── Emergency Mode ───────────────────────────────────────────
function renderEmergency(q) {
    $land.classList.add('collapsed');
    $load.classList.remove('active');
    $filters.classList.remove('visible');
    $loadMore.classList.remove('visible');
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
    fetch(API + '?q=' + encodeURIComponent(q) + '&mode=web&page=1')
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.results && d.results.length > 0) {
                var extra = '<div style="margin-top:24px;"><h3 style="color:var(--as-amber);font-size:14px;margin-bottom:12px;"><i class="fas fa-search"></i> Related Web Results</h3>';
                d.results.slice(0, 5).forEach(function(r) {
                    extra += '<div class="as-result">' +
                        '<a class="as-result-title" href="' + esc(r.url) + '" target="_blank" rel="noopener">' + esc(r.title) + '</a>' +
                        '<div class="as-result-snippet">' + esc((r.snippet || '').substring(0, 200)) + '</div></div>';
                });
                extra += '</div>';
                $res.innerHTML += extra;
            }
        })
        .catch(function() {});
    mode = savedMode;
}

// ── Mode Setter ──────────────────────────────────────────────
function setMode(m) {
    document.querySelectorAll('.as-mode').forEach(function(x) {
        x.classList.toggle('active', x.dataset.mode === m);
    });
    mode = m;
    doSearch();
}
window.setMode = setMode;

// ── Keyboard Navigation ──────────────────────────────────────
document.addEventListener('keydown', function(e) {
    // Only if not typing in input
    if (document.activeElement === $in) return;

    var results = $res.querySelectorAll('.as-result');
    if (!results.length) return;

    var current = $res.querySelector('.as-result.focused');
    var idx = current ? Array.from(results).indexOf(current) : -1;

    if (e.key === 'j' || e.key === 'ArrowDown') {
        e.preventDefault();
        if (current) current.classList.remove('focused');
        idx = Math.min(idx + 1, results.length - 1);
        results[idx].classList.add('focused');
        results[idx].scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else if (e.key === 'k' || e.key === 'ArrowUp') {
        e.preventDefault();
        if (current) current.classList.remove('focused');
        idx = Math.max(idx - 1, 0);
        results[idx].classList.add('focused');
        results[idx].scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else if (e.key === 'Enter' && current) {
        var link = current.querySelector('.as-result-title');
        if (link) window.open(link.href, '_blank');
    } else if (e.key === '/') {
        e.preventDefault();
        $in.focus();
    }
});

// ── Helpers ──────────────────────────────────────────────────
function fmtAnswer(t) {
    return t.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.+?)\*/g, '<em>$1</em>')
            .replace(/`(.+?)`/g, '<code>$1</code>')
            .replace(/\n/g, '<br>');
}
function hlQuery(text, q) {
    if (!q) return text;
    var words = q.split(/\s+/).filter(function(w) { return w.length > 2; });
    var r = text;
    words.forEach(function(w) {
        var rx = new RegExp('(' + w.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
        r = r.replace(rx, '<mark>$1</mark>');
    });
    return r;
}
function esc(s) { return typeof GDS !== 'undefined' ? GDS.esc(s) : String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

// ── Search Rewards & Wallet ──────────────────────────────────
var searchRewardPending = false;
function awardSearchReward() {
    if (searchRewardPending) return;
    searchRewardPending = true;
    fetch('/api/mining.php?action=search_reward', {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' }
    }).then(function(r) { return r.json(); }).then(function(d) {
        searchRewardPending = false;
        if (d.ok && d.reward > 0) loadNavBalance();
    }).catch(function() { searchRewardPending = false; });
}

function loadNavBalance() {
    fetch('/api/mining.php?action=wallet', { credentials: 'same-origin' })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.ok && d.wallet) {
                var el = document.getElementById('navWallet');
                var bal = document.getElementById('navBalance');
                if (el && bal) {
                    bal.textContent = d.wallet.balance.toFixed(4);
                    el.style.display = '';
                }
            }
        })
        .catch(function() {});
}

// ── Init ─────────────────────────────────────────────────────
fetch(API + '?action=stats')
    .then(function(r) { return r.json(); })
    .then(function(d) {
        var el = document.getElementById('asIndexCount');
        if (el && d.web_index_documents !== undefined) {
            el.textContent = d.web_index_documents > 0
                ? (d.web_index_documents > 999 ? Math.floor(d.web_index_documents / 1000) + 'K' : d.web_index_documents)
                : 'New';
        }
    })
    .catch(function() {});

loadNavBalance();

// Auto-search on page load if query present
if ($in.value.trim()) doSearch();
