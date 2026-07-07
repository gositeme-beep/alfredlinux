/**
 * GoSiteMe Conversations Engine v2.0
 * Extracted from conversations.php
 */
(() => {
    'use strict';

    const API = '/api/conversations.php';
    const CHAT_API = '/api/alfred-chat.php';
    let csrfToken = '';
    let currentFilter = 'all';
    let currentSearch = '';
    let currentPage = 1;
    let totalPages = 1;
    let activeConvId = null;
    let conversations = [];
    let searchTimer = null;
    let selectedIndex = -1;

    const $ = id => document.getElementById(id);
    const qs = (sel, ctx) => (ctx || document).querySelector(sel);
    const qsa = (sel, ctx) => (ctx || document).querySelectorAll(sel);

    // ── Init ──
    loadStats();
    loadConversations(1);

    // ── Stats ──
    async function loadStats() {
        try {
            const r = await fetch(`${API}?action=stats`);
            const d = await r.json();
            if (d.stats) {
                $('statConvos').textContent = d.stats.total_conversations || 0;
                $('statMsgs').textContent = d.stats.total_messages || 0;
                $('statAgent').textContent = capitalize(d.stats.most_used_agent || 'alfred');
                $('statAvg').textContent = (d.stats.avg_messages || 0) + ' msgs';
            }
        } catch (e) { console.error('Stats error:', e); }
    }

    // ── Conversation List ──
    async function loadConversations(page, append = false) {
        if (!append) {
            $('convItems').innerHTML = '<div class="spinner"></div>';
            selectedIndex = -1;
        }

        const params = new URLSearchParams({
            action: 'list',
            page: page,
            per_page: 20,
            date_filter: currentFilter,
        });
        if (currentSearch) params.set('search', currentSearch);

        try {
            const r = await fetch(`${API}?${params}`);
            const d = await r.json();
            csrfToken = d.csrf_token || csrfToken;
            totalPages = d.total_pages || 1;
            currentPage = d.page || 1;

            if (append) {
                conversations = conversations.concat(d.conversations || []);
            } else {
                conversations = d.conversations || [];
            }

            renderList(append);
        } catch (e) {
            console.error('List error:', e);
            $('convItems').innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><h3>Error loading conversations</h3></div>';
        }
    }

    function renderList(append = false) {
        const container = $('convItems');
        if (!append) container.innerHTML = '';

        if (conversations.length === 0) {
            container.innerHTML = $('tmplNoConvos').innerHTML;
            return;
        }

        const startIdx = append ? conversations.length - 20 : 0;
        const items = conversations.slice(Math.max(0, startIdx));

        items.forEach((c, i) => {
            const idx = startIdx + i;
            const el = document.createElement('div');
            el.className = 'conv-item' + (c.conv_id === activeConvId ? ' active' : '');
            el.dataset.convId = c.conv_id;
            el.dataset.idx = idx;

            const dateStr = formatDate(c.updated || c.started);
            el.innerHTML = `
                <div class="conv-title">${escHtml(c.title || 'Untitled')}</div>
                <div class="conv-preview">${escHtml(c.preview || '')}</div>
                <div class="conv-meta">
                    <span class="agent-pill">${escHtml(c.agent)}</span>
                    <span><i class="fas fa-comment"></i> ${c.msg_count}</span>
                </div>
                <div class="conv-date">${dateStr}</div>
                <div class="conv-actions">
                    <button class="conv-action-btn" title="Rename" data-act="rename"><i class="fas fa-pen"></i></button>
                    <button class="conv-action-btn" title="Export" data-act="export"><i class="fas fa-download"></i></button>
                    <button class="conv-action-btn danger" title="Delete" data-act="delete"><i class="fas fa-trash"></i></button>
                </div>
            `;
            el.addEventListener('click', (e) => {
                if (e.target.closest('.conv-actions')) return;
                selectConversation(c.conv_id);
            });
            container.appendChild(el);
        });

        // Action buttons
        container.querySelectorAll('.conv-action-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const item = btn.closest('.conv-item');
                const convId = item.dataset.convId;
                const act = btn.dataset.act;
                if (act === 'delete') deleteConversation(convId);
                else if (act === 'rename') openRenameModal(convId);
                else if (act === 'export') exportConversation(convId, 'txt');
            });
        });

        // Load more button
        const existing = container.querySelector('.load-more-wrap');
        if (existing) existing.remove();

        if (currentPage < totalPages) {
            const wrap = document.createElement('div');
            wrap.className = 'load-more-wrap';
            wrap.innerHTML = '<button class="load-more-btn">Load More</button>';
            wrap.querySelector('button').addEventListener('click', () => {
                loadConversations(currentPage + 1, true);
            });
            container.appendChild(wrap);
        }
    }

    // ── Select / Load Conversation ──
    async function selectConversation(convId) {
        activeConvId = convId;

        // Highlight in list
        qsa('.conv-item').forEach(el => {
            el.classList.toggle('active', el.dataset.convId === convId);
        });

        // Show detail panel (mobile)
        $('detailPanel').classList.add('show');
        $('detailHeader').style.display = 'flex';
        $('continueWrap').style.display = 'block';
        $('messageThread').innerHTML = '<div class="spinner"></div>';

        try {
            const r = await fetch(`${API}?action=get&id=${encodeURIComponent(convId)}`);
            const d = await r.json();
            if (d.error) {
                $('messageThread').innerHTML = `<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><h3>${escHtml(d.error)}</h3></div>`;
                return;
            }

            $('detailTitle').textContent = d.title || 'Conversation';
            renderMessages(d.messages || []);
        } catch (e) {
            console.error('Conversation load error:', e);
            $('messageThread').innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><h3>Error loading conversation</h3></div>';
        }
    }

    function renderMessages(messages) {
        const thread = $('messageThread');
        thread.innerHTML = '';

        if (messages.length === 0) {
            thread.innerHTML = '<div class="empty-state"><i class="fas fa-ghost"></i><h3>No messages</h3></div>';
            return;
        }

        const userInitials = window._convInitials || 'U';

        messages.forEach(m => {
            const isUser = m.role === 'user';
            const row = document.createElement('div');
            row.className = `msg-row ${isUser ? 'user' : 'alfred'}`;

            const avatarText = isUser ? userInitials : 'A';
            const content = isUser ? escHtml(m.message) : renderMarkdown(m.message);
            const time = formatTime(m.created_at);

            row.innerHTML = `
                <div class="msg-avatar">${avatarText}</div>
                <div class="msg-bubble">
                    ${content}
                    <span class="msg-time">${time}</span>
                </div>
            `;
            thread.appendChild(row);
        });

        // Syntax highlighting
        thread.querySelectorAll('pre code').forEach(block => {
            Prism.highlightElement(block);
            const pre = block.closest('pre');
            if (pre && !pre.querySelector('.code-copy-btn')) {
                const btn = document.createElement('button');
                btn.className = 'code-copy-btn';
                btn.textContent = 'Copy';
                btn.addEventListener('click', () => {
                    navigator.clipboard.writeText(block.textContent).then(() => {
                        btn.textContent = 'Copied!';
                        setTimeout(() => btn.textContent = 'Copy', 1500);
                    });
                });
                pre.appendChild(btn);
            }
        });

        // Scroll to bottom
        thread.scrollTop = thread.scrollHeight;
    }

    // ── Continue Conversation ──
    $('continueForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const input = $('continueInput');
        const text = input.value.trim();
        if (!text || !activeConvId) return;

        // Add user message to UI
        const thread = $('messageThread');
        const userRow = document.createElement('div');
        userRow.className = 'msg-row user';
        userRow.innerHTML = `
            <div class="msg-avatar">${window._convInitials || 'U'}</div>
            <div class="msg-bubble">${escHtml(text)}<span class="msg-time">Just now</span></div>
        `;
        thread.appendChild(userRow);

        // Add thinking indicator
        const thinkRow = document.createElement('div');
        thinkRow.className = 'msg-row alfred';
        thinkRow.id = 'thinkingMsg';
        thinkRow.innerHTML = `
            <div class="msg-avatar">A</div>
            <div class="msg-bubble"><em>Alfred is thinking…</em></div>
        `;
        thread.appendChild(thinkRow);
        thread.scrollTop = thread.scrollHeight;
        input.value = '';

        try {
            const r = await fetch(CHAT_API, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken,
                },
                body: JSON.stringify({
                    message: text,
                    conv_id: activeConvId,
                    agent: 'alfred',
                }),
            });
            const d = await r.json();
            if (d.csrf_token) csrfToken = d.csrf_token;

            const think = $('thinkingMsg');
            if (think) think.remove();

            const reply = d.reply || d.message || d.response || 'No response received.';
            const assistRow = document.createElement('div');
            assistRow.className = 'msg-row alfred';
            assistRow.innerHTML = `
                <div class="msg-avatar">A</div>
                <div class="msg-bubble">${renderMarkdown(reply)}<span class="msg-time">Just now</span></div>
            `;
            thread.appendChild(assistRow);

            // Syntax highlight new code blocks
            assistRow.querySelectorAll('pre code').forEach(block => {
                Prism.highlightElement(block);
                const pre = block.closest('pre');
                if (pre && !pre.querySelector('.code-copy-btn')) {
                    const btn = document.createElement('button');
                    btn.className = 'code-copy-btn';
                    btn.textContent = 'Copy';
                    btn.addEventListener('click', () => {
                        navigator.clipboard.writeText(block.textContent).then(() => {
                            btn.textContent = 'Copied!';
                            setTimeout(() => btn.textContent = 'Copy', 1500);
                        });
                    });
                    pre.appendChild(btn);
                }
            });

            thread.scrollTop = thread.scrollHeight;
            loadStats(); // refresh stats
        } catch (e) {
            const think = $('thinkingMsg');
            if (think) think.remove();
            const errRow = document.createElement('div');
            errRow.className = 'msg-row alfred';
            errRow.innerHTML = `
                <div class="msg-avatar">A</div>
                <div class="msg-bubble" style="border-color:var(--al-danger);">Failed to send. Please try again.<span class="msg-time">Just now</span></div>
            `;
            thread.appendChild(errRow);
        }
    });

    // Auto-resize textarea
    $('continueInput').addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });

    // ── Delete ──
    async function deleteConversation(convId) {
        if (!confirm('Delete this conversation? This cannot be undone.')) return;

        try {
            const r = await fetch(`${API}?action=delete`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                body: JSON.stringify({ id: convId }),
            });
            const d = await r.json();
            if (d.csrf_token) csrfToken = d.csrf_token;

            if (d.success) {
                conversations = conversations.filter(c => c.conv_id !== convId);
                if (activeConvId === convId) {
                    activeConvId = null;
                    $('detailHeader').style.display = 'none';
                    $('continueWrap').style.display = 'none';
                    $('messageThread').innerHTML = $('emptyDetail') ? '' : '';
                    $('messageThread').innerHTML = '<div class="empty-state"><i class="fas fa-comments"></i><h3>Select a conversation</h3><p>Choose a conversation from the list to view the full message history</p></div>';
                    $('detailPanel').classList.remove('show');
                }
                renderList();
                loadStats();
            }
        } catch (e) { console.error('Delete error:', e); }
    }

    // ── Rename ──
    let renameTarget = null;

    function openRenameModal(convId) {
        renameTarget = convId;
        const conv = conversations.find(c => c.conv_id === convId);
        $('renameInput').value = conv ? conv.title : '';
        $('renameModal').classList.add('open');
        setTimeout(() => $('renameInput').focus(), 100);
    }

    $('renameBtn').addEventListener('click', () => {
        if (activeConvId) openRenameModal(activeConvId);
    });

    $('renameCancelBtn').addEventListener('click', () => {
        $('renameModal').classList.remove('open');
    });

    $('renameSaveBtn').addEventListener('click', async () => {
        const title = $('renameInput').value.trim();
        if (!title || !renameTarget) return;

        try {
            const r = await fetch(`${API}?action=rename`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                body: JSON.stringify({ id: renameTarget, title: title }),
            });
            const d = await r.json();
            if (d.csrf_token) csrfToken = d.csrf_token;

            if (d.success) {
                const conv = conversations.find(c => c.conv_id === renameTarget);
                if (conv) conv.title = title;
                renderList();
                if (activeConvId === renameTarget) {
                    $('detailTitle').textContent = title;
                }
            }
        } catch (e) { console.error('Rename error:', e); }

        $('renameModal').classList.remove('open');
    });

    $('renameInput').addEventListener('keydown', (e) => {
        if (e.key === 'Enter') { e.preventDefault(); $('renameSaveBtn').click(); }
        if (e.key === 'Escape') $('renameCancelBtn').click();
    });

    // ── Export ──
    qsa('#exportMenu .dropdown-item').forEach(item => {
        item.addEventListener('click', (e) => {
            e.stopPropagation();
            const format = item.dataset.format;
            if (activeConvId) exportConversation(activeConvId, format);
        });
    });

    function exportConversation(convId, format) {
        window.open(`${API}?action=export&id=${encodeURIComponent(convId)}&format=${format}`, '_blank');
    }

    // ── Filters ──
    qsa('.filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            qsa('.filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentFilter = btn.dataset.filter;
            currentPage = 1;
            loadConversations(1);
        });
    });

    // ── Search (debounced) ──
    $('convSearch').addEventListener('input', (e) => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            currentSearch = e.target.value.trim();
            currentPage = 1;
            loadConversations(1);
        }, 300);
    });

    // ── Keyboard Navigation ──
    document.addEventListener('keydown', (e) => {
        // Don't capture when typing in inputs
        if (['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) return;
        if ($('renameModal').classList.contains('open')) return;

        const items = qsa('.conv-item');
        if (!items.length) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
            items[selectedIndex].click();
            items[selectedIndex].scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            selectedIndex = Math.max(selectedIndex - 1, 0);
            items[selectedIndex].click();
            items[selectedIndex].scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'Escape') {
            activeConvId = null;
            qsa('.conv-item').forEach(el => el.classList.remove('active'));
            $('detailHeader').style.display = 'none';
            $('continueWrap').style.display = 'none';
            $('messageThread').innerHTML = '<div class="empty-state"><i class="fas fa-comments"></i><h3>Select a conversation</h3><p>Choose a conversation from the list to view the full message history</p></div>';
            $('detailPanel').classList.remove('show');
        }
    });

    // ── Mobile Back ──
    $('mobileBackBtn').addEventListener('click', () => {
        $('detailPanel').classList.remove('show');
    });

    // ── Markdown Renderer (lightweight) ──
    function renderMarkdown(text) {
        if (!text) return '';
        let html = escHtml(text);

        // Code blocks (```lang\ncode\n```)
        html = html.replace(/```(\w+)?\n([\s\S]*?)```/g, (_, lang, code) => {
            const cls = lang ? `language-${lang}` : '';
            return `<pre><code class="${cls}">${code.trim()}</code></pre>`;
        });
        // Inline code
        html = html.replace(/`([^`]+)`/g, '<code>$1</code>');
        // Bold
        html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        // Italic
        html = html.replace(/\*(.+?)\*/g, '<em>$1</em>');
        // Links
        html = html.replace(/\[([^\]]+)\]\((https?:\/\/[^)]+)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>');
        // Plain URLs
        html = html.replace(/(^|[^"'>])(https?:\/\/[^\s<]+)/g, '$1<a href="$2" target="_blank" rel="noopener">$2</a>');
        // Blockquotes
        html = html.replace(/^&gt;\s?(.+)$/gm, '<blockquote>$1</blockquote>');
        // Headers
        html = html.replace(/^### (.+)$/gm, '<h3>$1</h3>');
        html = html.replace(/^## (.+)$/gm, '<h2>$1</h2>');
        html = html.replace(/^# (.+)$/gm, '<h1>$1</h1>');
        // Unordered lists
        html = html.replace(/^[\-\*] (.+)$/gm, '<li>$1</li>');
        html = html.replace(/(<li>.*<\/li>\n?)+/g, '<ul>$&</ul>');
        // Ordered lists
        html = html.replace(/^\d+\.\s(.+)$/gm, '<li>$1</li>');
        // Line breaks (double newline = paragraph)
        html = html.replace(/\n\n/g, '</p><p>');
        html = html.replace(/\n/g, '<br>');
        html = '<p>' + html + '</p>';
        // Clean up empty paragraphs
        html = html.replace(/<p>\s*<\/p>/g, '');

        return html;
    }

    // ── Helpers ──
    function escHtml(str) {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }

    function capitalize(s) {
        return s ? s.charAt(0).toUpperCase() + s.slice(1) : '';
    }

    function formatDate(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        const now = new Date();
        const diff = now - d;
        const mins = Math.floor(diff / 60000);
        const hrs = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);

        if (mins < 1) return 'Just now';
        if (mins < 60) return mins + 'm ago';
        if (hrs < 24) return hrs + 'h ago';
        if (days < 7) return days + 'd ago';
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }

    function formatTime(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        return d.toLocaleString('en-US', {
            month: 'short', day: 'numeric',
            hour: 'numeric', minute: '2-digit',
            hour12: true
        });
    }

})();
