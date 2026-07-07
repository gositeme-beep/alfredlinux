/**
 * Pulse Engine v2.0 — GoSiteMe Social Network
 * Extracted from pulse.php inline script + v2.0 enhancements:
 *   - WebSocket real-time notifications & live posts
 *   - Infinite scroll via Intersection Observer
 *   - Image upload preview on composer
 *   - Toast notifications
 *   - Connection status indicator
 */
(function() {
    'use strict';

    // ── State ───────────────────────────────────────────────────
    const API = '/api/pulse.php';
    let userId = 0;
    let userName = '';
    let initials = 'U';
    let myAvatarUrl = null;
    let profileViewId = 0;

    let currentTab = 'feed';
    let currentPage = 1;
    let isLoadingMore = false;
    let searchTimeout = null;

    let peopleOrder = 'popular';
    let peoplePage = 1;

    let hoverCardEl = null;
    let hoverCardTimeout = null;

    // v2.0 state
    let ws = null;
    let wsReconnect = 0;
    const MAX_WS_RECONNECT = 10;
    let infiniteObserver = null;

    // ── Init ────────────────────────────────────────────────────
    function init(cfg) {
        userId = cfg.userId || 0;
        userName = cfg.userName || '';
        profileViewId = cfg.profileViewId || 0;
        initials = userName.split(' ').map(function(w) { return w[0]; }).join('').toUpperCase().slice(0, 2) || 'U';

        // Set initials as fallback
        var myAvatarEl = document.getElementById('pulseMyAvatar');
        if (myAvatarEl) myAvatarEl.textContent = initials;
        var sidebarAvatar = document.getElementById('pulseSidebarAvatar');
        if (sidebarAvatar) sidebarAvatar.textContent = initials;
        var sidebarName = document.getElementById('pulseSidebarName');
        if (sidebarName) sidebarName.textContent = userName;

        // Bind tab switching
        document.querySelectorAll('.pulse-tab').forEach(function(tab) {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.pulse-tab').forEach(function(t) { t.classList.remove('active'); });
                tab.classList.add('active');
                currentTab = tab.dataset.tab;
                loadFeed(currentTab);
            });
        });

        // Bind post composer
        var textarea = document.getElementById('pulsePostContent');
        var postBtn = document.getElementById('pulsePostBtn');
        var charCount = document.getElementById('pulseCharCount');
        if (textarea) {
            textarea.addEventListener('input', function() {
                var len = textarea.value.length;
                if (charCount) charCount.textContent = len + ' / 5000';
                if (postBtn) postBtn.disabled = len === 0 || len > 5000;
            });
        }
        if (postBtn) postBtn.addEventListener('click', submitPost);

        // Bind search
        var searchInput = document.getElementById('pulseSearchInput');
        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                clearTimeout(searchTimeout);
                var q = e.target.value.trim();
                if (q.length < 2) { loadFeed(currentTab); return; }
                searchTimeout = setTimeout(function() { performSearch(q); }, 400);
            });
        }

        // Hover card delegation on feed
        var feedEl = document.getElementById('pulseFeed');
        if (feedEl) {
            feedEl.addEventListener('mouseover', handleFeedMouseOver);
            feedEl.addEventListener('mouseout', handleFeedMouseOut);
        }

        // Initial data load
        if (profileViewId) {
            loadProfileView();
        } else {
            loadFeed('feed');
        }
        loadMyProfile();
        loadTrendingTags();
        loadSuggestedUsers();
        checkNotifications();

        // v2.0: WebSocket instead of polling
        connectWebSocket();

        // v2.0: Setup infinite scroll
        setupInfiniteScroll();

        // v2.0: Image preview on composer
        setupImagePreview();
    }

    // ── Helpers ─────────────────────────────────────────────────
    function setAvatarContent(el, avatarUrl, fallback) {
        if (!el) return;
        if (avatarUrl) {
            el.innerHTML = '<img src="' + esc(avatarUrl) + '" alt="" loading="lazy">';
        } else {
            el.textContent = fallback || 'U';
        }
    }

    function renderBadge(badge) {
        if (!badge) return '';
        var labels = { commander: 'Commander', verified: 'Verified', agent: 'AI Agent', creator: 'Creator' };
        var icons = { commander: 'fa-crown', verified: 'fa-check-circle', agent: 'fa-robot', creator: 'fa-palette' };
        return '<span class="pulse-badge ' + esc(badge) + '"><i class="fas ' + (icons[badge] || 'fa-star') + '"></i> ' + (labels[badge] || badge) + '</span>';
    }

    function renderHashtags(text) {
        return text.replace(/#([a-zA-Z0-9_]+)/g, '<a class="pulse-hashtag" onclick="window.Pulse.hashtag(\'$1\')">#$1</a>');
    }

    function esc(str) { return GDS.esc(str); }

    function timeAgo(dateStr) {
        var s = Math.floor((Date.now() - new Date(dateStr + ' UTC').getTime()) / 1000);
        if (s < 60) return 'just now';
        if (s < 3600) return Math.floor(s / 60) + 'm ago';
        if (s < 86400) return Math.floor(s / 3600) + 'h ago';
        if (s < 604800) return Math.floor(s / 86400) + 'd ago';
        return new Date(dateStr).toLocaleDateString();
    }

    // ── API helper ──────────────────────────────────────────────
    async function api(action, opts) {
        opts = opts || {};
        var params = new URLSearchParams(Object.assign({ action: action }, opts.query || {}));
        var url = API + '?' + params;
        var fetchOpts = { credentials: 'include' };
        if (opts.body) {
            fetchOpts.method = 'POST';
            fetchOpts.headers = { 'Content-Type': 'application/json', 'X-CSRF-Token': window.AW_CSRF_TOKEN || '' };
            fetchOpts.body = JSON.stringify(opts.body);
        }
        var res = await fetch(url, fetchOpts);
        return res.json();
    }

    // ── v2.0: Toast notifications ───────────────────────────────
    function toast(msg, type) {
        type = type || 'info';
        if (window.GDSToast) return GDSToast.show(msg, { type: type === 'error' ? 'danger' : type });
    }

    // ── Render a post card ──────────────────────────────────────
    function renderPost(p) {
        var deleteBtn = p.is_own ? '<button class="pulse-card-del" onclick="window.Pulse.deletePost(' + p.id + ')" title="Delete"><i class="fas fa-trash"></i></button>' : '';
        var likedClass = p.liked ? ' liked' : '';
        var heartIcon = p.liked ? 'fas fa-heart' : 'far fa-heart';
        var bookmarkClass = p.bookmarked ? ' bookmarked' : '';
        var bookmarkIcon = p.bookmarked ? 'fas fa-bookmark' : 'far fa-bookmark';
        var badgeHtml = renderBadge(p.badge);

        var avatarContent = p.avatar_url
            ? '<img src="' + esc(p.avatar_url) + '" alt="" loading="lazy">'
            : esc(p.initials);

        var commentsHtml = '';
        if (p.comments && p.comments.length) {
            commentsHtml = p.comments.map(function(c) {
                var cAvatar = c.avatar_url
                    ? '<img src="' + esc(c.avatar_url) + '" alt="" loading="lazy">'
                    : esc(c.initials);
                return '<div class="pulse-comment">' +
                    '<div class="pulse-avatar sm">' + cAvatar + '</div>' +
                    '<div class="pulse-comment-body">' +
                        '<span class="pulse-comment-author">' + esc(c.author_name) + '</span>' +
                        '<span class="pulse-comment-text">' + esc(c.content) + '</span>' +
                        '<div class="pulse-comment-time">' + timeAgo(c.created_at) + '</div>' +
                    '</div>' +
                '</div>';
            }).join('');
        }

        var bodyHtml = renderHashtags(esc(p.content));
        var mediaHtml = p.media_url ? '<img src="' + esc(p.media_url) + '" style="width:100%;border-radius:10px;margin-bottom:.75rem;" alt="Post image" loading="lazy">' : '';

        return '<div class="pulse-card" id="pulse-post-' + p.id + '">' +
            '<div class="pulse-card-header">' +
                '<div class="pulse-avatar" style="cursor:pointer;" onclick="window.Pulse.viewProfile(' + p.user_id + ')">' + avatarContent + '</div>' +
                '<div class="pulse-card-meta">' +
                    '<div class="pulse-card-author" onclick="window.Pulse.viewProfile(' + p.user_id + ')">' + esc(p.author_name) + ' ' + badgeHtml + '</div>' +
                    '<div class="pulse-card-time">' + timeAgo(p.created_at) + '</div>' +
                '</div>' +
                deleteBtn +
            '</div>' +
            '<div class="pulse-card-body">' + bodyHtml + '</div>' +
            mediaHtml +
            '<div class="pulse-card-actions">' +
                '<button class="pulse-action-btn' + likedClass + '" onclick="window.Pulse.like(' + p.id + ', this)">' +
                    '<i class="' + heartIcon + '"></i> <span>' + p.like_count + '</span>' +
                '</button>' +
                '<button class="pulse-action-btn" onclick="window.Pulse.toggleComments(' + p.id + ')">' +
                    '<i class="far fa-comment"></i> <span>' + p.comment_count + '</span>' +
                '</button>' +
                '<button class="pulse-action-btn" onclick="window.Pulse.share(' + p.id + ', this)" style="position:relative;">' +
                    '<i class="far fa-share-square"></i>' +
                '</button>' +
                '<button class="pulse-action-btn' + bookmarkClass + '" onclick="window.Pulse.bookmark(' + p.id + ', this)">' +
                    '<i class="' + bookmarkIcon + '"></i>' +
                '</button>' +
            '</div>' +
            '<div class="pulse-comments" id="pulse-comments-' + p.id + '" style="display:none;">' +
                '<div class="pulse-comments-list">' + commentsHtml + '</div>' +
                '<div class="pulse-comment-form">' +
                    '<input type="text" placeholder="Write a comment..." maxlength="2000" id="pulse-ci-' + p.id + '" onkeydown="if(event.key===\'Enter\')window.Pulse.comment(' + p.id + ')">' +
                    '<button onclick="window.Pulse.comment(' + p.id + ')"><i class="fas fa-reply"></i></button>' +
                '</div>' +
            '</div>' +
        '</div>';
    }

    // ── Render notifications ────────────────────────────────────
    function renderNotification(n) {
        var icons = { like: 'fa-heart', comment: 'fa-comment', follow: 'fa-user-plus', mention: 'fa-at' };
        var colors = { like: 'var(--p-coral)', comment: 'var(--p-accent)', follow: 'var(--p-green)', mention: 'var(--p-violet)' };
        var msgs = { like: 'liked your post', comment: 'commented on your post', follow: 'started following you', mention: 'mentioned you' };
        var readStyle = n.is_read ? 'opacity:.6;' : '';
        var onclick = n.post_id ? 'window.Pulse.scrollToPost(' + n.post_id + ')' : 'window.Pulse.viewProfile(' + n.actor_id + ')';
        return '<div class="pulse-card" style="padding:.75rem 1rem;' + readStyle + 'cursor:pointer;" onclick="' + onclick + '">' +
            '<div style="display:flex;gap:.6rem;align-items:center;">' +
                '<i class="fas ' + (icons[n.type] || 'fa-bell') + '" style="color:' + (colors[n.type] || 'var(--p-muted)') + ';width:20px;text-align:center;"></i>' +
                '<div class="pulse-avatar sm">' + esc(n.initials) + '</div>' +
                '<div style="flex:1;">' +
                    '<span style="font-weight:600;">' + esc(n.actor_name) + '</span>' +
                    '<span style="color:var(--p-muted);font-size:.88rem;"> ' + (msgs[n.type] || n.type) + '</span>' +
                '</div>' +
                '<span style="font-size:.72rem;color:var(--p-muted);">' + timeAgo(n.created_at) + '</span>' +
            '</div>' +
        '</div>';
    }

    // ── Load feed ───────────────────────────────────────────────
    async function loadFeed(tab, append) {
        var feed = document.getElementById('pulseFeed');
        var composer = document.getElementById('pulseComposer');
        if (!append) {
            feed.innerHTML = '<div class="pulse-loading"><i class="fas fa-circle-notch"></i> Loading...</div>';
            currentPage = 1;
        }
        if (composer) composer.style.display = (tab === 'feed' || tab === 'global') ? '' : 'none';

        try {
            if (tab === 'notifications') {
                var nData = await api('notifications');
                if (nData.success && nData.notifications.length) {
                    feed.innerHTML = nData.notifications.map(renderNotification).join('');
                    if (nData.unread_count > 0) {
                        await api('notif-read', { body: {} });
                        updateNotifBadge(0);
                    }
                } else {
                    feed.innerHTML = '<div class="pulse-empty"><i class="fas fa-bell-slash"></i><p>No notifications yet</p></div>';
                }
                return;
            }

            if (tab === 'people') {
                await loadPeopleDirectory(append);
                return;
            }

            var action;
            if (tab === 'trending') action = 'trending';
            else if (tab === 'global') action = 'global';
            else if (tab === 'bookmarks') action = 'bookmarks';
            else action = 'feed';

            var data = await api(action, { query: { page: currentPage } });
            if (data.error && action === 'feed') {
                data = await api('global', { query: { page: currentPage } });
            }
            if (data.success && data.posts && data.posts.length) {
                var postsHtml = data.posts.map(renderPost).join('');
                if (append) {
                    var oldBtn = feed.querySelector('.pulse-load-more');
                    if (oldBtn) oldBtn.remove();
                    feed.insertAdjacentHTML('beforeend', postsHtml);
                } else {
                    feed.innerHTML = postsHtml;
                }
                if (data.posts.length >= (data.limit || 20)) {
                    feed.insertAdjacentHTML('beforeend',
                        '<div class="pulse-scroll-sentinel" id="pulseScrollSentinel"></div>' +
                        '<button class="pulse-load-more" onclick="window.Pulse.loadMore()"><i class="fas fa-arrow-down"></i> Load More</button>');
                    observeSentinel();
                }
            } else if (!append) {
                var msg = tab === 'feed'
                    ? 'Your feed is empty. Follow some people or create your first post!'
                    : tab === 'bookmarks'
                    ? 'No saved posts yet. Bookmark posts to find them here.'
                    : 'No posts yet. Be the first!';
                feed.innerHTML = '<div class="pulse-empty"><i class="fas fa-stream"></i><p>' + msg + '</p></div>';
            }
        } catch (e) {
            if (!append) feed.innerHTML = '<div class="pulse-empty"><i class="fas fa-exclamation-triangle"></i><p>Failed to load. Please try again.</p></div>';
        }
        isLoadingMore = false;
    }

    // ── Submit post ─────────────────────────────────────────────
    async function submitPost() {
        var textarea = document.getElementById('pulsePostContent');
        var postBtn = document.getElementById('pulsePostBtn');
        var charCount = document.getElementById('pulseCharCount');
        var content = textarea.value.trim();
        if (!content) return;
        postBtn.disabled = true;
        postBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Posting...';
        try {
            var body = { content: content, post_type: 'text' };
            // v2.0: Include media if previewed
            var preview = document.getElementById('pulseImagePreview');
            if (preview && preview.dataset.url) {
                body.media_url = preview.dataset.url;
            }
            var data = await api('post', { body: body });
            if (data.success && data.post) {
                textarea.value = '';
                if (charCount) charCount.textContent = '0 / 5000';
                clearImagePreview();
                var feed = document.getElementById('pulseFeed');
                var empty = feed.querySelector('.pulse-empty');
                if (empty) empty.remove();
                feed.insertAdjacentHTML('afterbegin', renderPost(data.post));
                var sc = document.getElementById('pulseStatPosts');
                if (sc) sc.textContent = parseInt(sc.textContent || '0') + 1;
                toast('Post published!', 'success');
            }
        } catch (e) {
            toast('Failed to post. Try again.', 'error');
        }
        postBtn.disabled = false;
        postBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Post';
    }

    // ── Search ──────────────────────────────────────────────────
    async function performSearch(q) {
        var feed = document.getElementById('pulseFeed');
        feed.innerHTML = '<div class="pulse-loading"><i class="fas fa-circle-notch"></i> Searching...</div>';
        var data = await api('search', { query: { q: q } });
        if (data.success) {
            var html = '';
            if (data.users && data.users.length) {
                html += '<h4 style="margin:0 0 .75rem;font-size:.9rem;color:var(--p-muted);"><i class="fas fa-users"></i> People</h4>';
                html += data.users.map(function(u) {
                    var avatar = u.avatar_url ? '<img src="' + esc(u.avatar_url) + '" alt="" loading="lazy">' : esc(u.initials);
                    var badge = renderBadge(u.badge);
                    return '<div class="pulse-search-user" onclick="window.Pulse.viewProfile(' + u.id + ')">' +
                        '<div class="pulse-avatar">' + avatar + '</div>' +
                        '<div class="pulse-search-user-info">' +
                            '<div class="pulse-search-user-name">' + esc(u.name) + ' ' + badge + '</div>' +
                            '<div class="pulse-search-user-meta">' + (u.follower_count || 0) + ' followers' + (u.bio ? ' &middot; ' + esc(u.bio.substring(0, 60)) : '') + '</div>' +
                        '</div>' +
                    '</div>';
                }).join('');
                html += '<div style="margin-bottom:1rem;"></div>';
            }
            if (data.posts && data.posts.length) {
                html += '<h4 style="margin:0 0 .75rem;font-size:.9rem;color:var(--p-muted);"><i class="fas fa-stream"></i> Posts</h4>';
                html += data.posts.map(renderPost).join('');
            }
            feed.innerHTML = html || '<div class="pulse-empty"><i class="fas fa-search"></i><p>No results found</p></div>';
        }
    }

    // ── Global actions ──────────────────────────────────────────
    async function pulseLike(postId, btn) {
        var data = await api('like', { body: { post_id: postId } });
        if (data.success) {
            var icon = btn.querySelector('i');
            var span = btn.querySelector('span');
            if (data.liked) {
                btn.classList.add('liked');
                icon.className = 'fas fa-heart';
            } else {
                btn.classList.remove('liked');
                icon.className = 'far fa-heart';
            }
            span.textContent = data.like_count;
        }
    }

    async function pulseDelete(postId) {
        if (!confirm('Delete this post?')) return;
        var data = await api('post-delete', { body: { post_id: postId } });
        if (data.success) {
            var el = document.getElementById('pulse-post-' + postId);
            if (el) el.remove();
            var sc = document.getElementById('pulseStatPosts');
            if (sc) sc.textContent = Math.max(0, parseInt(sc.textContent || '0') - 1);
            toast('Post deleted', 'info');
        }
    }

    function pulseToggleComments(postId) {
        var el = document.getElementById('pulse-comments-' + postId);
        if (el) {
            el.style.display = el.style.display === 'none' ? '' : 'none';
            if (el.style.display !== 'none') {
                var input = document.getElementById('pulse-ci-' + postId);
                if (input) input.focus();
            }
        }
    }

    async function pulseComment(postId) {
        var input = document.getElementById('pulse-ci-' + postId);
        var content = input.value.trim();
        if (!content) return;
        input.disabled = true;
        var data = await api('comment', { body: { post_id: postId, content: content } });
        if (data.success && data.comment) {
            var c = data.comment;
            var cAvatar = myAvatarUrl
                ? '<img src="' + esc(myAvatarUrl) + '" alt="" loading="lazy">'
                : esc(initials);
            var list = document.querySelector('#pulse-comments-' + postId + ' .pulse-comments-list');
            list.insertAdjacentHTML('beforeend',
                '<div class="pulse-comment">' +
                    '<div class="pulse-avatar sm">' + cAvatar + '</div>' +
                    '<div class="pulse-comment-body">' +
                        '<span class="pulse-comment-author">' + esc(c.author_name) + '</span>' +
                        '<span class="pulse-comment-text">' + esc(c.content) + '</span>' +
                        '<div class="pulse-comment-time">just now</div>' +
                    '</div>' +
                '</div>');
            input.value = '';
            var card = document.getElementById('pulse-post-' + postId);
            var cBtn = card.querySelectorAll('.pulse-action-btn')[1];
            var cSpan = cBtn.querySelector('span');
            cSpan.textContent = parseInt(cSpan.textContent || '0') + 1;
        }
        input.disabled = false;
    }

    function pulseViewProfile(uid) {
        window.open('/pulse.php?profile=' + uid, '_self');
    }

    async function pulseFollow(uid, btn) {
        var isFollowing = btn.classList.contains('following');
        var action = isFollowing ? 'unfollow' : 'follow';
        var data = await api(action, { body: { user_id: uid } });
        if (data.success) {
            if (data.following) {
                btn.classList.add('following');
                btn.textContent = 'Following';
            } else {
                btn.classList.remove('following');
                btn.textContent = 'Follow';
            }
        }
    }

    async function pulseBookmark(postId, btn) {
        var data = await api('bookmark', { body: { post_id: postId } });
        if (data.success) {
            var icon = btn.querySelector('i');
            if (data.bookmarked) {
                btn.classList.add('bookmarked');
                icon.className = 'fas fa-bookmark';
            } else {
                btn.classList.remove('bookmarked');
                icon.className = 'far fa-bookmark';
            }
        }
    }

    function pulseShare(postId, btn) {
        var url = location.origin + '/pulse.php?profile=0&post=' + postId;
        if (navigator.share) {
            navigator.share({ title: 'Check this out on Pulse', url: url });
        } else if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(function() {
                btn.innerHTML = '<i class="fas fa-check"></i>';
                toast('Link copied!', 'success');
                setTimeout(function() { btn.innerHTML = '<i class="far fa-share-square"></i>'; }, 2000);
            });
        }
    }

    function pulseHashtag(tag) {
        var feed = document.getElementById('pulseFeed');
        feed.innerHTML = '<div class="pulse-loading"><i class="fas fa-circle-notch"></i> Loading...</div>';
        document.querySelectorAll('.pulse-tab').forEach(function(t) { t.classList.remove('active'); });
        api('hashtag', { query: { tag: tag } }).then(function(data) {
            if (data.success && data.posts && data.posts.length) {
                feed.innerHTML = '<div class="pulse-card" style="padding:.75rem 1rem;margin-bottom:.5rem;">' +
                    '<a href="/pulse" style="color:var(--p-accent);font-size:.85rem;text-decoration:none;"><i class="fas fa-arrow-left"></i> Back</a>' +
                    '<h3 style="margin:.5rem 0 0;font-size:1.1rem;color:var(--p-text);">#' + esc(tag) + '</h3>' +
                    '<p style="font-size:.82rem;color:var(--p-muted);margin:.25rem 0 0;">' + data.posts.length + ' posts</p>' +
                '</div>' + data.posts.map(renderPost).join('');
            } else {
                feed.innerHTML = '<div class="pulse-empty"><i class="fas fa-hashtag"></i><p>No posts with #' + esc(tag) + '</p></div>';
            }
        });
    }

    function pulseLoadMore() {
        if (isLoadingMore) return;
        isLoadingMore = true;
        currentPage++;
        loadFeed(currentTab, true);
    }

    function pulseScrollToPost(postId) {
        var el = document.getElementById('pulse-post-' + postId);
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    // ── Edit Profile Modal ──────────────────────────────────────
    async function pulseEditProfile() {
        var data = await api('profile', { query: { user_id: userId } });
        if (!data.success) return;
        var p = data.profile;
        var modal = document.createElement('div');
        modal.className = 'pulse-modal-overlay';
        modal.innerHTML =
            '<div class="pulse-modal">' +
                '<h3><i class="fas fa-user-edit" style="color:var(--p-accent);margin-right:.5rem;"></i> Edit Profile</h3>' +
                '<label>Bio (280 chars)</label>' +
                '<textarea id="editBio" maxlength="280" rows="3" placeholder="Tell the world about yourself...">' + esc(p.bio || '') + '</textarea>' +
                '<label>Avatar URL (optional)</label>' +
                '<input type="url" id="editAvatarUrl" placeholder="https://..." value="' + esc(p.avatar_url || '') + '">' +
                '<label>Theme Color</label>' +
                '<input type="color" id="editThemeColor" value="' + (p.theme_color || '#3b82f6') + '" style="width:60px;height:36px;cursor:pointer;">' +
                '<div class="pulse-modal-actions">' +
                    '<button class="pulse-modal-btn" onclick="this.closest(\'.pulse-modal-overlay\').remove()">Cancel</button>' +
                    '<button class="pulse-modal-btn primary" onclick="window.Pulse.saveProfile(this)">Save</button>' +
                '</div>' +
            '</div>';
        document.body.appendChild(modal);
        modal.addEventListener('click', function(e) { if (e.target === modal) modal.remove(); });
    }

    async function pulseSaveProfile(btn) {
        btn.disabled = true;
        btn.textContent = 'Saving...';
        var bio = document.getElementById('editBio').value;
        var avatar_url = document.getElementById('editAvatarUrl').value || null;
        var theme_color = document.getElementById('editThemeColor').value;
        await api('update-profile', { body: { bio: bio, avatar_url: avatar_url, theme_color: theme_color } });
        btn.closest('.pulse-modal-overlay').remove();
        if (profileViewId) loadProfileView();
        loadMyProfile();
        toast('Profile updated!', 'success');
    }

    // ── Notification badge ──────────────────────────────────────
    function updateNotifBadge(count) {
        var badge = document.getElementById('pulseNotifBadge');
        if (!badge) return;
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = '';
        } else {
            badge.style.display = 'none';
        }
    }

    async function checkNotifications() {
        try {
            var data = await api('notifications');
            if (data.success) updateNotifBadge(data.unread_count);
        } catch(e) { /* ignore */ }
    }

    // ── People Directory ────────────────────────────────────────
    async function loadPeopleDirectory(append) {
        var feed = document.getElementById('pulseFeed');
        if (!append) peoplePage = 1;
        try {
            var data = await api('browse-profiles', { query: { page: peoplePage, order: peopleOrder, limit: 20 } });
            if (!data.success) { feed.innerHTML = '<div class="pulse-empty"><p>Failed to load</p></div>'; return; }

            var orderBtns = '<div class="pulse-people-order">' +
                '<button class="' + (peopleOrder === 'popular' ? 'active' : '') + '" onclick="window.Pulse.setPeopleOrder(\'popular\')"><i class="fas fa-star"></i> Popular</button>' +
                '<button class="' + (peopleOrder === 'active' ? 'active' : '') + '" onclick="window.Pulse.setPeopleOrder(\'active\')"><i class="fas fa-chart-line"></i> Most Active</button>' +
                '<button class="' + (peopleOrder === 'newest' ? 'active' : '') + '" onclick="window.Pulse.setPeopleOrder(\'newest\')"><i class="fas fa-clock"></i> Newest</button>' +
            '</div>';

            var cards = data.users.map(function(u) {
                var avatar = u.avatar_url ? '<img src="' + esc(u.avatar_url) + '" alt="" loading="lazy">' : esc(u.initials);
                var badge = renderBadge(u.badge);
                var followBtn = u.is_self ? '' : u.is_following
                    ? '<button class="pulse-person-follow following" disabled>Following</button>'
                    : '<button class="pulse-person-follow" onclick="event.stopPropagation();window.Pulse.suggestFollow(' + u.id + ',this)">Follow</button>';
                return '<div class="pulse-person-card" onclick="window.Pulse.viewProfile(' + u.id + ')">' +
                    '<div class="pulse-avatar">' + avatar + '</div>' +
                    '<div class="pulse-person-name">' + esc(u.name) + ' ' + badge + '</div>' +
                    '<div class="pulse-person-bio">' + esc(u.bio || 'No bio yet') + '</div>' +
                    '<div class="pulse-person-stats"><div><span>' + u.follower_count + '</span> followers</div><div><span>' + u.post_count + '</span> posts</div></div>' +
                    followBtn +
                '</div>';
            }).join('');

            if (append) {
                var oldMore = feed.querySelector('.pulse-people-load-more');
                if (oldMore) oldMore.remove();
                var grid = feed.querySelector('.pulse-people-grid');
                if (grid) grid.insertAdjacentHTML('beforeend', cards);
            } else {
                feed.innerHTML = orderBtns + '<div class="pulse-people-grid">' + cards + '</div>';
            }

            if (data.users.length >= 20) {
                feed.insertAdjacentHTML('beforeend',
                    '<button class="pulse-people-load-more" onclick="window.Pulse.loadMorePeople()"><i class="fas fa-arrow-down"></i> Load More People</button>');
            }
        } catch(e) {
            if (!append) feed.innerHTML = '<div class="pulse-empty"><p>Failed to load people directory.</p></div>';
        }
    }

    // ── Profile Hover Card ──────────────────────────────────────
    function showProfileHoverCard(uid, anchorEl) {
        clearTimeout(hoverCardTimeout);
        hoverCardTimeout = setTimeout(async function() {
            try {
                var data = await api('profile-card', { query: { user_id: uid } });
                if (!data.success) return;
                var c = data.card;
                if (hoverCardEl) hoverCardEl.remove();
                var avatar = c.avatar_url ? '<img src="' + esc(c.avatar_url) + '" alt="" loading="lazy">' : esc(c.initials);
                var badge = renderBadge(c.badge);
                var mutual = c.mutual_count > 0 ? '<div class="pulse-hover-card-mutual"><i class="fas fa-user-friends"></i> ' + c.mutual_count + ' mutual connection' + (c.mutual_count > 1 ? 's' : '') + '</div>' : '';
                var followBtn = c.is_self ? '' : c.is_following
                    ? '<button class="btn-follow" style="opacity:.7" disabled>Following</button>'
                    : '<button class="btn-follow" onclick="event.stopPropagation();window.Pulse.suggestFollow(' + c.id + ',this)">Follow</button>';

                hoverCardEl = document.createElement('div');
                hoverCardEl.className = 'pulse-hover-card';
                hoverCardEl.innerHTML =
                    '<div class="pulse-avatar">' + avatar + '</div>' +
                    '<div class="pulse-hover-card-name">' + esc(c.name) + ' ' + badge + '</div>' +
                    '<div class="pulse-hover-card-bio">' + esc(c.bio || 'No bio yet') + '</div>' +
                    '<div class="pulse-hover-card-stats"><div><span>' + c.follower_count + '</span> followers</div><div><span>' + c.post_count + '</span> posts</div></div>' +
                    mutual +
                    '<div class="pulse-hover-card-actions">' +
                        '<button class="btn-profile" onclick="window.Pulse.viewProfile(' + c.id + ')">View Profile</button>' +
                        followBtn +
                    '</div>';
                document.body.appendChild(hoverCardEl);

                var rect = anchorEl.getBoundingClientRect();
                var top = rect.bottom + 8;
                var left = rect.left;
                if (top + 250 > window.innerHeight) top = rect.top - 260;
                if (left + 280 > window.innerWidth) left = window.innerWidth - 290;
                hoverCardEl.style.top = top + 'px';
                hoverCardEl.style.left = Math.max(10, left) + 'px';

                hoverCardEl.addEventListener('mouseenter', function() { clearTimeout(hoverCardTimeout); });
                hoverCardEl.addEventListener('mouseleave', hideProfileHoverCard);
            } catch(e) { /* ignore */ }
        }, 400);
    }

    function hideProfileHoverCard() {
        clearTimeout(hoverCardTimeout);
        hoverCardTimeout = setTimeout(function() {
            if (hoverCardEl) { hoverCardEl.remove(); hoverCardEl = null; }
        }, 200);
    }

    function handleFeedMouseOver(e) {
        var author = e.target.closest('.pulse-card-author');
        var avatar = e.target.closest('.pulse-card-header .pulse-avatar');
        if (author || avatar) {
            var clickTarget = author || avatar;
            var match = clickTarget.getAttribute('onclick');
            if (match) {
                var m = match.match(/viewProfile\((\d+)\)/);
                if (m) showProfileHoverCard(parseInt(m[1]), clickTarget);
            }
        }
    }

    function handleFeedMouseOut(e) {
        var author = e.target.closest('.pulse-card-author');
        var avatar = e.target.closest('.pulse-card-header .pulse-avatar');
        if (author || avatar) {
            hoverCardTimeout = setTimeout(hideProfileHoverCard, 300);
        }
    }

    // ── Load profile sidebar ────────────────────────────────────
    async function loadMyProfile() {
        var data = await api('profile');
        if (data.success && data.profile) {
            var p = data.profile;
            var sp = document.getElementById('pulseStatPosts');
            if (sp) sp.textContent = p.post_count;
            var sf = document.getElementById('pulseStatFollowers');
            if (sf) sf.textContent = p.follower_count;
            var sfg = document.getElementById('pulseStatFollowing');
            if (sfg) sfg.textContent = p.following_count;

            myAvatarUrl = p.avatar_url;
            setAvatarContent(document.getElementById('pulseSidebarAvatar'), p.avatar_url, initials);
            var myAvatarEl = document.getElementById('pulseMyAvatar');
            if (myAvatarEl) setAvatarContent(myAvatarEl, p.avatar_url, initials);

            var bioEl = document.getElementById('pulseSidebarBio');
            if (bioEl) bioEl.textContent = p.bio || 'Member';
        }
    }

    // ── Load trending tags ──────────────────────────────────────
    async function loadTrendingTags() {
        var container = document.getElementById('pulseTrendingTags');
        if (!container) return;
        try {
            var data = await api('trending-tags');
            if (data.success && data.tags.length) {
                container.innerHTML = data.tags.map(function(t) {
                    return '<div class="pulse-trending-tag" onclick="window.Pulse.hashtag(\'' + esc(t.tag) + '\')">' +
                        '<span class="pulse-trending-tag-name">#' + esc(t.tag) + '</span>' +
                        '<span class="pulse-trending-tag-count">' + t.count + ' posts</span>' +
                    '</div>';
                }).join('');
            } else {
                container.innerHTML = '<div style="font-size:.82rem;color:var(--p-muted);padding:.25rem 0;">No trending tags yet</div>';
            }
        } catch(e) {
            container.innerHTML = '';
        }
    }

    // ── Load suggested users ────────────────────────────────────
    async function loadSuggestedUsers() {
        var container = document.getElementById('pulseSuggestedUsers');
        if (!container) return;
        try {
            var data = await api('suggested-users');
            if (data.success && data.users.length) {
                container.innerHTML = data.users.map(function(u) {
                    var avatar = u.avatar_url
                        ? '<img src="' + esc(u.avatar_url) + '" alt="" loading="lazy">'
                        : esc(u.initials);
                    return '<div class="pulse-suggest-user">' +
                        '<div class="pulse-avatar sm" style="cursor:pointer;" onclick="window.Pulse.viewProfile(' + u.id + ')">' + avatar + '</div>' +
                        '<div class="pulse-suggest-user-info">' +
                            '<div class="pulse-suggest-user-name" onclick="window.Pulse.viewProfile(' + u.id + ')">' + esc(u.name) + '</div>' +
                            '<div class="pulse-suggest-user-meta">' + u.follower_count + ' followers</div>' +
                        '</div>' +
                        '<button class="pulse-suggest-follow-btn" onclick="window.Pulse.suggestFollow(' + u.id + ', this)">Follow</button>' +
                    '</div>';
                }).join('');
            } else {
                container.closest('.pulse-sidebar-card').style.display = 'none';
            }
        } catch(e) {
            container.innerHTML = '';
        }
    }

    async function pulseSuggestFollow(uid, btn) {
        var data = await api('follow', { body: { user_id: uid } });
        if (data.success) {
            btn.textContent = 'Following';
            btn.style.background = 'var(--p-accent)';
            btn.style.color = '#fff';
            btn.disabled = true;
        }
    }

    // ── Profile page view ───────────────────────────────────────
    async function loadProfileView() {
        if (!profileViewId) return;
        var data = await api('profile', { query: { user_id: profileViewId } });
        if (data.success && data.profile) {
            var p = data.profile;
            setAvatarContent(document.getElementById('pulseProfileAvatar'), p.avatar_url, p.initials);
            document.getElementById('pulseProfileName').textContent = p.name;
            var bioEl = document.getElementById('pulseProfileBio');
            if (bioEl) bioEl.textContent = p.bio || ('Member since ' + new Date(p.member_since).toLocaleDateString());
            document.getElementById('pulseProfilePosts').textContent = p.post_count;
            document.getElementById('pulseProfileFollowers').textContent = p.follower_count;
            document.getElementById('pulseProfileFollowing').textContent = p.following_count;

            var badgeEl = document.getElementById('pulseProfileBadge');
            if (badgeEl) badgeEl.innerHTML = renderBadge(p.badge);

            var coverEl = document.getElementById('pulseProfileCover');
            if (coverEl && p.cover_url) {
                coverEl.innerHTML = '<img src="' + esc(p.cover_url) + '" alt="">' + coverEl.innerHTML;
            } else if (coverEl && p.theme_color) {
                coverEl.style.background = 'linear-gradient(135deg, ' + p.theme_color + ' 0%, var(--p-violet) 50%, var(--p-coral) 100%)';
            }

            if (p.is_self) {
                var editBtn = document.getElementById('pulseEditProfileBtn');
                if (editBtn) editBtn.style.display = '';
            }

            var actionsEl = document.getElementById('pulseProfileActions');
            if (!p.is_self) {
                var cls = p.is_following ? ' following' : '';
                var txt = p.is_following ? 'Following' : 'Follow';
                actionsEl.innerHTML = '<button class="pulse-profile-follow-btn' + cls + '" onclick="window.Pulse.follow(' + p.id + ', this)">' + txt + '</button>';
            }
            actionsEl.innerHTML += '<div style="font-size:.85rem;color:var(--p-muted);margin-top:.5rem;"><i class="fas fa-heart" style="color:var(--p-coral);"></i> ' + p.total_likes + ' total likes received</div>';
        }
        var posts = await api('user-posts', { query: { user_id: profileViewId } });
        var feed = document.getElementById('pulseFeed');
        if (posts.success && posts.posts && posts.posts.length) {
            feed.innerHTML = posts.posts.map(renderPost).join('');
        } else {
            feed.innerHTML = '<div class="pulse-empty"><i class="fas fa-ghost"></i><p>No posts yet</p></div>';
        }
    }

    // ── v2.0: WebSocket ─────────────────────────────────────────
    function connectWebSocket() {
        if (!userId) return;
        try {
            ws = new WebSocket('wss://gositeme.com:3010');
            ws.onopen = function() {
                wsReconnect = 0;
                ws.send(JSON.stringify({ type: 'auth', userId: userId, channel: 'pulse' }));
                updateWsBadge(true);
            };
            ws.onmessage = function(evt) {
                try {
                    var msg = JSON.parse(evt.data);
                    handleWsMessage(msg);
                } catch(e) { /* ignore */ }
            };
            ws.onclose = function() {
                updateWsBadge(false);
                if (wsReconnect < MAX_WS_RECONNECT) {
                    var delay = Math.min(1000 * Math.pow(2, wsReconnect), 30000);
                    wsReconnect++;
                    setTimeout(connectWebSocket, delay);
                } else {
                    // Fall back to polling
                    setInterval(checkNotifications, 60000);
                }
            };
            ws.onerror = function() { /* onclose fires after */ };
        } catch(e) {
            // WebSocket unavailable, fall back to polling
            setInterval(checkNotifications, 60000);
        }
    }

    function handleWsMessage(msg) {
        if (msg.type === 'notification') {
            var badge = document.getElementById('pulseNotifBadge');
            if (badge) {
                var cur = parseInt(badge.textContent || '0');
                updateNotifBadge(cur + 1);
            }
            playNotifSound();
            toast(msg.message || 'New notification', 'info');
        } else if (msg.type === 'new_post' && msg.post) {
            // Live new post if on global/feed tab
            if (currentTab === 'feed' || currentTab === 'global') {
                var feed = document.getElementById('pulseFeed');
                var empty = feed.querySelector('.pulse-empty');
                if (empty) empty.remove();
                feed.insertAdjacentHTML('afterbegin', renderPost(msg.post));
            }
        } else if (msg.type === 'post_liked' && msg.post_id) {
            var likeBtn = document.querySelector('#pulse-post-' + msg.post_id + ' .pulse-action-btn');
            if (likeBtn) {
                var span = likeBtn.querySelector('span');
                if (span) span.textContent = msg.like_count;
            }
        }
    }

    function updateWsBadge(connected) {
        var badge = document.getElementById('pulseWsBadge');
        if (!badge) return;
        if (connected) {
            badge.className = 'pulse-ws-badge pulse-ws-connected';
            badge.title = 'Real-time connected';
        } else {
            badge.className = 'pulse-ws-badge pulse-ws-disconnected';
            badge.title = 'Reconnecting...';
        }
    }

    function playNotifSound() {
        try {
            var ctx = new (window.AudioContext || window.webkitAudioContext)();
            var osc = ctx.createOscillator();
            var gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = 880;
            osc.type = 'sine';
            gain.gain.setValueAtTime(0.08, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);
            osc.start();
            osc.stop(ctx.currentTime + 0.3);
        } catch(e) { /* ignore */ }
    }

    // ── v2.0: Infinite Scroll ───────────────────────────────────
    function setupInfiniteScroll() {
        if ('IntersectionObserver' in window) {
            infiniteObserver = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting && !isLoadingMore) {
                        pulseLoadMore();
                    }
                });
            }, { rootMargin: '200px' });
        }
    }

    function observeSentinel() {
        if (!infiniteObserver) return;
        var sentinel = document.getElementById('pulseScrollSentinel');
        if (sentinel) infiniteObserver.observe(sentinel);
    }

    // ── v2.0: Image Preview ─────────────────────────────────────
    function setupImagePreview() {
        var addMediaBtn = document.getElementById('pulseAddMedia');
        var fileInput = document.getElementById('pulseMediaInput');
        if (!addMediaBtn || !fileInput) return;
        addMediaBtn.addEventListener('click', function() { fileInput.click(); });
        fileInput.addEventListener('change', function() {
            var file = fileInput.files[0];
            if (!file) return;
            if (!file.type.startsWith('image/')) {
                toast('Only image files are supported', 'error');
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                toast('Image must be under 5MB', 'error');
                return;
            }
            var reader = new FileReader();
            reader.onload = function(e) {
                var preview = document.getElementById('pulseImagePreview');
                if (preview) {
                    preview.innerHTML = '<img src="' + e.target.result + '" style="max-width:100%;border-radius:10px;margin-top:.5rem;">' +
                        '<button class="pulse-preview-remove" onclick="window.Pulse.clearImagePreview()"><i class="fas fa-times"></i></button>';
                    preview.dataset.url = e.target.result;
                    preview.style.display = 'block';
                }
            };
            reader.readAsDataURL(file);
        });
    }

    function clearImagePreview() {
        var preview = document.getElementById('pulseImagePreview');
        if (preview) {
            preview.innerHTML = '';
            preview.style.display = 'none';
            delete preview.dataset.url;
        }
        var fileInput = document.getElementById('pulseMediaInput');
        if (fileInput) fileInput.value = '';
    }

    // ── Public API ──────────────────────────────────────────────
    window.Pulse = {
        init: init,
        like: pulseLike,
        deletePost: pulseDelete,
        toggleComments: pulseToggleComments,
        comment: pulseComment,
        viewProfile: pulseViewProfile,
        follow: pulseFollow,
        bookmark: pulseBookmark,
        share: pulseShare,
        hashtag: pulseHashtag,
        loadMore: pulseLoadMore,
        scrollToPost: pulseScrollToPost,
        editProfile: pulseEditProfile,
        saveProfile: pulseSaveProfile,
        setPeopleOrder: function(order) { peopleOrder = order; loadPeopleDirectory(); },
        loadMorePeople: function() { peoplePage++; loadPeopleDirectory(true); },
        suggestFollow: pulseSuggestFollow,
        clearImagePreview: clearImagePreview
    };
})();
