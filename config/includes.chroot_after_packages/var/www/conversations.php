<?php
require_once __DIR__ . '/includes/auth-gate.inc.php';
$page_title       = 'Conversation History - Alfred AI';
$page_description = 'View and search your Alfred AI conversation history';
$page_canonical   = 'https://root.com/conversations';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title><?= htmlspecialchars($page_title) ?></title>
<meta name="description" content="<?= htmlspecialchars($page_description) ?>">
<link rel="canonical" href="<?= $page_canonical ?>">

<link rel="stylesheet" href="/assets/css/fonts.css">
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<link rel="stylesheet" href="/assets/js/vendor/prism-tomorrow.min.css">

<style>
/* ── Variables ── */
:root {
    --al-bg: #0a0a14;
    --al-surface: #12121e;
    --al-surface-2: #1a1a2e;
    --al-surface-3: #242440;
    --al-accent: #6c5ce7;
    --al-accent-light: #a29bfe;
    --al-text: #e0e0e0;
    --al-text-muted: #8892b0;
    --al-border: rgba(108,92,231,0.15);
    --al-success: #10b981;
    --al-warning: #f59e0b;
    --al-danger: #ef4444;
    --al-radius: 12px;
    --al-transition: 0.25s cubic-bezier(.4,0,.2,1);
}

/* ── Reset ── */
*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
html { font-size: 15px; }
body {
    font-family: 'Inter', -apple-system, system-ui, sans-serif;
    background: var(--al-bg);
    color: var(--al-text);
    min-height: 100vh;
    overflow: hidden;
}
a { color: var(--al-accent-light); text-decoration: none; }
a:hover { text-decoration: underline; }
button { cursor: pointer; font-family: inherit; }

/* ── Top Bar ── */
.conv-topbar {
    position: fixed; top: 0; left: 0; right: 0; z-index: 100;
    height: 56px;
    background: var(--al-surface);
    border-bottom: 1px solid var(--al-border);
    display: flex; align-items: center; padding: 0 24px; gap: 16px;
}
.conv-topbar .logo {
    font-family: 'Space Grotesk', sans-serif;
    font-weight: 700; font-size: 1.2rem;
    background: linear-gradient(135deg, var(--al-accent), var(--al-accent-light));
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
}
.conv-topbar .back-link {
    color: var(--al-text-muted); font-size: 0.85rem; display: flex; align-items: center; gap: 6px;
}
.conv-topbar .back-link:hover { color: var(--al-text); text-decoration: none; }
.conv-topbar .spacer { flex: 1; }
.conv-topbar .user-pill {
    background: var(--al-surface-2); border-radius: 20px; padding: 6px 14px;
    font-size: 0.8rem; color: var(--al-text-muted);
    display: flex; align-items: center; gap: 8px;
}
.conv-topbar .user-pill .avatar {
    width: 26px; height: 26px; border-radius: 50%;
    background: var(--al-accent); color: #fff; font-weight: 600; font-size: 0.7rem;
    display: flex; align-items: center; justify-content: center;
}

/* ── Stats Bar ── */
.stats-bar {
    position: fixed; top: 56px; left: 0; right: 0; z-index: 90;
    height: 72px;
    background: var(--al-surface);
    border-bottom: 1px solid var(--al-border);
    display: flex; align-items: center; justify-content: center; gap: 24px;
    padding: 0 24px;
}
.stat-card {
    background: var(--al-surface-2);
    border: 1px solid var(--al-border);
    border-radius: 10px;
    padding: 8px 20px;
    text-align: center;
    min-width: 140px;
    transition: border-color var(--al-transition);
}
.stat-card:hover { border-color: var(--al-accent); }
.stat-card .stat-value {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.25rem; font-weight: 700; color: var(--al-accent-light);
}
.stat-card .stat-label {
    font-size: 0.7rem; color: var(--al-text-muted); text-transform: uppercase; letter-spacing: 0.5px;
    margin-top: 2px;
}

/* ── Main Layout ── */
.conv-layout {
    position: fixed; top: 128px; left: 0; right: 0; bottom: 0;
    display: flex;
}

/* ── Left Panel ── */
.conv-list-panel {
    width: 380px; min-width: 320px;
    background: var(--al-surface);
    border-right: 1px solid var(--al-border);
    display: flex; flex-direction: column;
}
.list-header {
    padding: 16px;
    border-bottom: 1px solid var(--al-border);
    flex-shrink: 0;
}
.list-header .search-wrap {
    position: relative;
}
.list-header .search-wrap i {
    position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
    color: var(--al-text-muted); font-size: 0.85rem;
}
.list-header .search-input {
    width: 100%; padding: 10px 12px 10px 36px;
    background: var(--al-surface-2); border: 1px solid var(--al-border);
    border-radius: 8px; color: var(--al-text); font-size: 0.85rem;
    outline: none; transition: border-color var(--al-transition);
}
.list-header .search-input:focus { border-color: var(--al-accent); }
.list-header .search-input::placeholder { color: var(--al-text-muted); }

.filter-row {
    display: flex; gap: 6px; margin-top: 10px; flex-wrap: wrap;
}
.filter-btn {
    padding: 5px 12px; border-radius: 16px; font-size: 0.75rem; font-weight: 500;
    background: var(--al-surface-2); color: var(--al-text-muted);
    border: 1px solid transparent; transition: all var(--al-transition);
}
.filter-btn:hover, .filter-btn.active {
    background: var(--al-accent); color: #fff; border-color: var(--al-accent);
}
.new-conv-btn {
    display: flex; align-items: center; justify-content: center; gap: 6px;
    width: 100%; padding: 10px; margin-top: 10px;
    background: linear-gradient(135deg, var(--al-accent), #5a4bd1);
    color: #fff; border: none; border-radius: 8px; font-size: 0.85rem; font-weight: 600;
    transition: opacity var(--al-transition);
}
.new-conv-btn:hover { opacity: 0.9; }

/* Conversation items */
.conv-items {
    flex: 1; overflow-y: auto; padding: 8px;
}
.conv-items::-webkit-scrollbar { width: 4px; }
.conv-items::-webkit-scrollbar-thumb { background: var(--al-surface-3); border-radius: 4px; }

.conv-item {
    padding: 14px 12px;
    border-radius: 10px;
    cursor: pointer;
    transition: background var(--al-transition);
    position: relative;
    margin-bottom: 2px;
}
.conv-item:hover { background: var(--al-surface-2); }
.conv-item.active { background: var(--al-surface-3); border-left: 3px solid var(--al-accent); }
.conv-item .conv-title {
    font-weight: 600; font-size: 0.9rem; color: var(--al-text);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    padding-right: 60px;
}
.conv-item .conv-preview {
    font-size: 0.78rem; color: var(--al-text-muted);
    margin-top: 4px; line-height: 1.4;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.conv-item .conv-meta {
    display: flex; align-items: center; gap: 8px; margin-top: 6px; font-size: 0.7rem;
    color: var(--al-text-muted);
}
.conv-item .conv-meta .agent-pill {
    background: var(--al-surface-3); padding: 2px 8px; border-radius: 10px;
    font-weight: 500; text-transform: capitalize;
}
.conv-item .conv-date {
    position: absolute; top: 14px; right: 12px; font-size: 0.7rem; color: var(--al-text-muted);
}
.conv-item .conv-actions {
    position: absolute; top: 10px; right: 8px;
    display: none; gap: 4px;
}
.conv-item:hover .conv-actions { display: flex; }
.conv-item:hover .conv-date { display: none; }
.conv-action-btn {
    width: 28px; height: 28px; border-radius: 6px;
    background: var(--al-surface-3); border: none; color: var(--al-text-muted);
    font-size: 0.75rem; display: flex; align-items: center; justify-content: center;
    transition: all var(--al-transition);
}
.conv-action-btn:hover { background: var(--al-accent); color: #fff; }
.conv-action-btn.danger:hover { background: var(--al-danger); }

.load-more-wrap {
    padding: 12px; text-align: center;
}
.load-more-btn {
    padding: 8px 24px; border-radius: 8px; font-size: 0.82rem;
    background: var(--al-surface-2); color: var(--al-text-muted);
    border: 1px solid var(--al-border);
    transition: all var(--al-transition);
}
.load-more-btn:hover { border-color: var(--al-accent); color: var(--al-text); }

/* ── Right Panel — Detail ── */
.conv-detail-panel {
    flex: 1; display: flex; flex-direction: column; background: var(--al-bg);
}
.detail-header {
    padding: 14px 24px;
    border-bottom: 1px solid var(--al-border);
    display: flex; align-items: center; gap: 12px;
    background: var(--al-surface);
    flex-shrink: 0;
}
.detail-header .detail-title {
    font-family: 'Space Grotesk', sans-serif;
    font-weight: 600; font-size: 1.05rem; flex: 1;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.detail-header .detail-title[contenteditable="true"] {
    background: var(--al-surface-2); padding: 4px 8px; border-radius: 6px;
    outline: 2px solid var(--al-accent);
}
.detail-btn {
    padding: 6px 14px; border-radius: 8px; font-size: 0.8rem;
    background: var(--al-surface-2); color: var(--al-text-muted);
    border: 1px solid var(--al-border);
    display: flex; align-items: center; gap: 6px;
    transition: all var(--al-transition);
}
.detail-btn:hover { border-color: var(--al-accent); color: var(--al-text); }
.detail-btn .dropdown-menu {
    display: none; position: absolute; top: 100%; right: 0; margin-top: 4px;
    background: var(--al-surface-2); border: 1px solid var(--al-border);
    border-radius: 8px; overflow: hidden; min-width: 140px; z-index: 10;
}
.detail-btn:hover .dropdown-menu { display: block; }
.dropdown-item {
    display: block; padding: 8px 14px; font-size: 0.82rem; color: var(--al-text);
    border: none; background: none; width: 100%; text-align: left; cursor: pointer;
}
.dropdown-item:hover { background: var(--al-surface-3); }

.mobile-back-btn {
    display: none;
    padding: 6px 10px; border-radius: 8px; font-size: 0.85rem;
    background: var(--al-surface-2); color: var(--al-text-muted); border: none;
}

/* Message thread */
.message-thread {
    flex: 1; overflow-y: auto; padding: 24px; display: flex; flex-direction: column; gap: 16px;
}
.message-thread::-webkit-scrollbar { width: 6px; }
.message-thread::-webkit-scrollbar-thumb { background: var(--al-surface-3); border-radius: 6px; }

.msg-row {
    display: flex; gap: 12px; max-width: 85%; animation: msgIn 0.3s ease;
}
.msg-row.user { align-self: flex-end; flex-direction: row-reverse; }
.msg-row.alfred { align-self: flex-start; }

@keyframes msgIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

.msg-avatar {
    width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 0.75rem;
}
.msg-row.user .msg-avatar {
    background: var(--al-accent); color: #fff;
}
.msg-row.alfred .msg-avatar {
    background: linear-gradient(135deg, #6c5ce7, #a29bfe); color: #fff;
}

.msg-bubble {
    padding: 14px 18px; border-radius: 16px; font-size: 0.9rem; line-height: 1.65;
    position: relative;
}
.msg-row.user .msg-bubble {
    background: var(--al-accent); color: #fff;
    border-bottom-right-radius: 4px;
}
.msg-row.alfred .msg-bubble {
    background: var(--al-surface-2); color: var(--al-text);
    border: 1px solid var(--al-border);
    border-bottom-left-radius: 4px;
}
.msg-bubble .msg-time {
    font-size: 0.65rem; color: rgba(255,255,255,0.5); margin-top: 8px;
    display: block;
}
.msg-row.alfred .msg-bubble .msg-time { color: var(--al-text-muted); }

/* Markdown in messages */
.msg-bubble h1, .msg-bubble h2, .msg-bubble h3 { margin: 12px 0 6px; font-weight: 600; }
.msg-bubble h1 { font-size: 1.2rem; }
.msg-bubble h2 { font-size: 1.05rem; }
.msg-bubble h3 { font-size: 0.95rem; }
.msg-bubble p { margin: 6px 0; }
.msg-bubble ul, .msg-bubble ol { margin: 6px 0; padding-left: 20px; }
.msg-bubble li { margin: 3px 0; }
.msg-bubble code {
    background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 4px; font-size: 0.85em;
    font-family: 'Fira Code', 'SF Mono', monospace;
}
.msg-bubble pre {
    background: rgba(0,0,0,0.4); border-radius: 8px; padding: 14px; margin: 8px 0;
    overflow-x: auto; position: relative;
}
.msg-bubble pre code {
    background: none; padding: 0; font-size: 0.82rem; line-height: 1.5;
}
.code-copy-btn {
    position: absolute; top: 6px; right: 6px;
    background: var(--al-surface-3); color: var(--al-text-muted);
    border: none; border-radius: 6px; padding: 4px 10px; font-size: 0.7rem;
    opacity: 0; transition: opacity var(--al-transition);
}
pre:hover .code-copy-btn { opacity: 1; }
.code-copy-btn:hover { color: var(--al-accent-light); }

.msg-bubble a { color: var(--al-accent-light); }
.msg-row.user .msg-bubble a { color: #e0e0ff; }

.msg-bubble strong { font-weight: 600; }
.msg-bubble em { font-style: italic; }
.msg-bubble blockquote {
    border-left: 3px solid var(--al-accent); padding-left: 12px; margin: 8px 0;
    color: var(--al-text-muted);
}

/* Continue input */
.continue-wrap {
    padding: 16px 24px;
    border-top: 1px solid var(--al-border);
    background: var(--al-surface);
    flex-shrink: 0;
}
.continue-form {
    display: flex; gap: 10px;
}
.continue-input {
    flex: 1; padding: 12px 16px;
    background: var(--al-surface-2); border: 1px solid var(--al-border);
    border-radius: 10px; color: var(--al-text); font-size: 0.9rem;
    outline: none; resize: none; min-height: 44px; max-height: 120px;
    transition: border-color var(--al-transition);
    font-family: inherit;
}
.continue-input:focus { border-color: var(--al-accent); }
.continue-input::placeholder { color: var(--al-text-muted); }
.continue-send {
    padding: 0 18px; border-radius: 10px; border: none;
    background: var(--al-accent); color: #fff; font-size: 1rem;
    transition: opacity var(--al-transition);
}
.continue-send:hover { opacity: 0.85; }

/* Empty states */
.empty-state {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    height: 100%; text-align: center; padding: 40px; gap: 16px;
}
.empty-state i { font-size: 3rem; color: var(--al-surface-3); }
.empty-state h3 { font-size: 1.1rem; color: var(--al-text-muted); font-weight: 500; }
.empty-state p { font-size: 0.85rem; color: var(--al-text-muted); max-width: 300px; }
.empty-state .cta-btn {
    padding: 10px 24px; border-radius: 8px; margin-top: 8px;
    background: var(--al-accent); color: #fff; border: none;
    font-size: 0.85rem; font-weight: 600;
}
.empty-state .cta-btn:hover { opacity: 0.9; text-decoration: none; }

/* ── Rename Modal ── */
.rename-modal-overlay {
    display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65);
    z-index: 200; align-items: center; justify-content: center;
}
.rename-modal-overlay.open { display: flex; }
.rename-modal {
    background: var(--al-surface); border: 1px solid var(--al-border);
    border-radius: var(--al-radius); padding: 24px; width: 400px; max-width: 90vw;
}
.rename-modal h3 { margin-bottom: 14px; font-size: 1rem; }
.rename-modal input {
    width: 100%; padding: 10px 12px; background: var(--al-surface-2);
    border: 1px solid var(--al-border); border-radius: 8px;
    color: var(--al-text); font-size: 0.9rem; outline: none;
    margin-bottom: 14px;
}
.rename-modal input:focus { border-color: var(--al-accent); }
.rename-modal .modal-actions { display: flex; gap: 10px; justify-content: flex-end; }
.rename-modal .modal-btn {
    padding: 8px 18px; border-radius: 8px; font-size: 0.85rem; border: none;
    font-weight: 500;
}
.rename-cancel { background: var(--al-surface-2); color: var(--al-text-muted); }
.rename-save { background: var(--al-accent); color: #fff; }

/* ── Spinner ── */
.spinner {
    width: 24px; height: 24px; border: 3px solid var(--al-surface-3);
    border-top-color: var(--al-accent); border-radius: 50%;
    animation: spin 0.7s linear infinite; margin: 20px auto;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Responsive ── */
@media (max-width: 768px) {
    .stats-bar { height: auto; flex-wrap: wrap; padding: 10px 12px; gap: 8px; }
    .stat-card { min-width: 0; flex: 1; padding: 6px 10px; }
    .stat-card .stat-value { font-size: 1rem; }

    .conv-layout { top: auto; position: fixed; top: 128px; bottom: 0; left: 0; right: 0; }
    .conv-list-panel { width: 100%; border-right: none; }
    .conv-detail-panel { position: absolute; inset: 0; transform: translateX(100%); transition: transform 0.3s ease; z-index: 10; }
    .conv-detail-panel.show { transform: translateX(0); }
    .mobile-back-btn { display: inline-flex; }
}
@media (max-width: 480px) {
    .conv-topbar { padding: 0 12px; }
    .stats-bar { gap: 6px; padding: 6px 8px; }
    .stat-card { padding: 4px 8px; }
    .stat-card .stat-value { font-size: 0.9rem; }
    .stat-card .stat-label { font-size: 0.6rem; }
    .message-thread { padding: 12px; }
    .msg-row { max-width: 95%; }
}
</style>
    <link rel="stylesheet" href="/assets/css/design-tokens.css?v=20260310">
    <link rel="stylesheet" href="/assets/css/components.css?v=20260310">
    <script src="/assets/js/gds-utils.js?v=20260310" defer></script>
    <script src="/assets/js/gds-toast.js?v=20260310" defer></script>
    <script src="/assets/js/gds-modal.js?v=20260310" defer></script>
</head>
<body>

<!-- Top Bar -->
<div class="conv-topbar">
    <a href="/dashboard" class="back-link"><i class="fas fa-arrow-left"></i> Dashboard</a>
    <span class="logo">Alfred AI</span>
    <span class="spacer"></span>
    <div class="user-pill">
        <div class="avatar"><?= htmlspecialchars($initials) ?></div>
        <span><?= htmlspecialchars($clientName) ?></span>
    </div>
</div>

<!-- Stats Bar -->
<div class="stats-bar">
    <div class="stat-card"><div class="stat-value" id="statConvos">—</div><div class="stat-label">Conversations</div></div>
    <div class="stat-card"><div class="stat-value" id="statMsgs">—</div><div class="stat-label">Messages</div></div>
    <div class="stat-card"><div class="stat-value" id="statAgent">—</div><div class="stat-label">Top Agent</div></div>
    <div class="stat-card"><div class="stat-value" id="statAvg">—</div><div class="stat-label">Avg Length</div></div>
</div>

<!-- Two-Panel Layout -->
<div class="conv-layout">

    <!-- Left Panel: Conversation List -->
    <div class="conv-list-panel">
        <div class="list-header">
            <div class="search-wrap">
                <i class="fas fa-search"></i>
                <input type="text" class="search-input" id="convSearch" placeholder="Search conversations…" autocomplete="off">
            </div>
            <div class="filter-row">
                <button class="filter-btn active" data-filter="all">All</button>
                <button class="filter-btn" data-filter="today">Today</button>
                <button class="filter-btn" data-filter="week">This Week</button>
                <button class="filter-btn" data-filter="month">This Month</button>
            </div>
            <a href="/dashboard" class="new-conv-btn"><i class="fas fa-plus"></i> New Conversation</a>
        </div>
        <div class="conv-items" id="convItems">
            <div class="spinner" id="listSpinner"></div>
        </div>
    </div>

    <!-- Right Panel: Conversation Detail -->
    <div class="conv-detail-panel" id="detailPanel">
        <div class="detail-header" id="detailHeader" style="display:none;">
            <button class="mobile-back-btn" id="mobileBackBtn"><i class="fas fa-arrow-left"></i></button>
            <div class="detail-title" id="detailTitle">Conversation</div>
            <button class="detail-btn" id="renameBtn" title="Rename"><i class="fas fa-pen"></i></button>
            <div class="detail-btn" style="position:relative;" id="exportDropdown">
                <i class="fas fa-download"></i> Export
                <div class="dropdown-menu" id="exportMenu">
                    <button class="dropdown-item" data-format="txt"><i class="fas fa-file-alt"></i> Plain Text (.txt)</button>
                    <button class="dropdown-item" data-format="json"><i class="fas fa-code"></i> JSON (.json)</button>
                    <button class="dropdown-item" data-format="md"><i class="fab fa-markdown"></i> Markdown (.md)</button>
                </div>
            </div>
        </div>

        <div class="message-thread" id="messageThread">
            <div class="empty-state" id="emptyDetail">
                <i class="fas fa-comments"></i>
                <h3>Select a conversation</h3>
                <p>Choose a conversation from the list to view the full message history</p>
            </div>
        </div>

        <div class="continue-wrap" id="continueWrap" style="display:none;">
            <form class="continue-form" id="continueForm">
                <textarea class="continue-input" id="continueInput" placeholder="Continue this conversation…" rows="1"></textarea>
                <button type="submit" class="continue-send"><i class="fas fa-paper-plane"></i></button>
            </form>
        </div>
    </div>
</div>

<!-- Rename Modal -->
<div class="rename-modal-overlay" id="renameModal">
    <div class="rename-modal">
        <h3>Rename Conversation</h3>
        <input type="text" id="renameInput" placeholder="Enter a title…" maxlength="200">
        <div class="modal-actions">
            <button class="modal-btn rename-cancel" id="renameCancelBtn">Cancel</button>
            <button class="modal-btn rename-save" id="renameSaveBtn">Save</button>
        </div>
    </div>
</div>

<!-- Empty state for no conversations -->
<template id="tmplNoConvos">
    <div class="empty-state">
        <i class="fas fa-robot"></i>
        <h3>No conversations yet</h3>
        <p>Start your first conversation with Alfred and it will appear here.</p>
        <a href="/dashboard" class="cta-btn">Go to Dashboard</a>
    </div>
</template>

<script src="/assets/js/vendor/prism.min.js" defer></script>
<script src="/assets/js/vendor/prism-javascript.min.js" defer></script>
<script src="/assets/js/vendor/prism-python.min.js" defer></script>
<script src="/assets/js/vendor/prism-php.min.js" defer></script>
<script src="/assets/js/vendor/prism-bash.min.js" defer></script>
<script src="/assets/js/vendor/prism-json.min.js" defer></script>
<script src="/assets/js/vendor/prism-sql.min.js" defer></script>
<script src="/assets/js/vendor/prism-css.min.js" defer></script>
<script src="/assets/js/vendor/prism-markup.min.js" defer></script>

<script>window._convInitials = <?= json_encode($initials) ?>;</script>
<script src="/assets/js/conversations-engine.js"></script>

</body>
</html>
