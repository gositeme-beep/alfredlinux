<?php
$page_title = 'Creator Dashboard - Alfred AI Marketplace';
$page_description = 'Publish and manage your AI agents, tools, and templates on the Alfred Marketplace';
$page_canonical = 'https://root.com/marketplace-creator';
require_once __DIR__ . '/includes/auth-gate.inc.php';
$noGlobalMain = true;
require_once __DIR__ . '/includes/site-header.inc.php';
?>

<style>
/* ===== Creator Dashboard Styles ===== */
:root {
    --cr-bg: #0a0a14;
    --cr-surface: #12121e;
    --cr-surface-2: #1a1a2e;
    --cr-surface-3: #222238;
    --cr-border: rgba(255,255,255,0.08);
    --cr-accent: #6c5ce7;
    --cr-accent-light: #a29bfe;
    --cr-green: #00b894;
    --cr-orange: #fdcb6e;
    --cr-fire: #e17055;
    --cr-blue: #0984e3;
    --cr-red: #d63031;
    --cr-text: #e8e8f0;
    --cr-text-muted: #8a8a9a;
    --cr-radius: 14px;
    --cr-shadow: 0 4px 24px rgba(0,0,0,0.3);
    --cr-sidebar-w: 260px;
}

/* Layout */
.cr-layout {
    display: flex;
    min-height: calc(100vh - 80px);
    background: var(--cr-bg);
}

/* Sidebar */
.cr-sidebar {
    width: var(--cr-sidebar-w);
    background: var(--cr-surface);
    border-right: 1px solid var(--cr-border);
    padding: 30px 0;
    flex-shrink: 0;
    position: sticky;
    top: 80px;
    height: calc(100vh - 80px);
    overflow-y: auto;
    transition: transform 0.3s;
    z-index: 100;
}
.cr-sidebar-header {
    padding: 0 24px 24px;
    border-bottom: 1px solid var(--cr-border);
    margin-bottom: 12px;
}
.cr-sidebar-header h2 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.3rem;
    font-weight: 700;
    color: #fff;
    margin: 0 0 4px;
}
.cr-sidebar-header p {
    font-size: 0.82rem;
    color: var(--cr-text-muted);
    margin: 0;
}
.cr-nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 13px 24px;
    color: var(--cr-text-muted);
    font-size: 0.95rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    border-left: 3px solid transparent;
    text-decoration: none;
}
.cr-nav-item:hover {
    color: var(--cr-text);
    background: rgba(108,92,231,0.06);
}
.cr-nav-item.active {
    color: var(--cr-accent-light);
    background: rgba(108,92,231,0.1);
    border-left-color: var(--cr-accent);
}
.cr-nav-item i {
    width: 20px;
    text-align: center;
    font-size: 1rem;
}
.cr-sidebar-toggle {
    display: none;
    position: fixed;
    bottom: 24px;
    left: 24px;
    z-index: 200;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: var(--cr-accent);
    color: #fff;
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(108,92,231,0.5);
}

/* Main Content */
.cr-main {
    flex: 1;
    padding: 32px 40px;
    min-width: 0;
}
.cr-section {
    display: none;
}
.cr-section.active {
    display: block;
    animation: crFadeIn 0.3s ease;
}
@keyframes crFadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}
.cr-section-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.6rem;
    font-weight: 700;
    color: #fff;
    margin: 0 0 24px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.cr-section-title i { color: var(--cr-accent-light); }

/* Stats Cards */
.cr-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
}
.cr-stat {
    background: var(--cr-surface);
    border: 1px solid var(--cr-border);
    border-radius: var(--cr-radius);
    padding: 24px;
    transition: border-color 0.3s, transform 0.3s;
}
.cr-stat:hover {
    border-color: var(--cr-accent);
    transform: translateY(-2px);
}
.cr-stat-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    margin-bottom: 14px;
}
.cr-stat-value {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 2rem;
    font-weight: 800;
    color: #fff;
    line-height: 1;
    margin-bottom: 4px;
}
.cr-stat-label {
    font-size: 0.85rem;
    color: var(--cr-text-muted);
}

/* Chart Container */
.cr-chart-wrap {
    background: var(--cr-surface);
    border: 1px solid var(--cr-border);
    border-radius: var(--cr-radius);
    padding: 24px;
    margin-bottom: 32px;
}
.cr-chart-wrap h3 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.1rem;
    font-weight: 600;
    color: #fff;
    margin: 0 0 16px;
}

/* Tables */
.cr-table-wrap {
    background: var(--cr-surface);
    border: 1px solid var(--cr-border);
    border-radius: var(--cr-radius);
    overflow: hidden;
    margin-bottom: 32px;
}
.cr-table-wrap h3 {
    font-family: 'Space Grotesk', sans-serif;
    padding: 20px 24px 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: #fff;
    margin: 0 0 16px;
}
.cr-table {
    width: 100%;
    border-collapse: collapse;
}
.cr-table th {
    text-align: left;
    padding: 12px 20px;
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--cr-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    background: var(--cr-surface-2);
    border-bottom: 1px solid var(--cr-border);
}
.cr-table td {
    padding: 14px 20px;
    font-size: 0.9rem;
    color: var(--cr-text);
    border-bottom: 1px solid var(--cr-border);
}
.cr-table tr:last-child td { border-bottom: none; }
.cr-table tr:hover td { background: rgba(108,92,231,0.04); }

/* Badges */
.cr-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 600;
    gap: 4px;
}
.cr-badge-draft { background: rgba(138,138,154,0.2); color: var(--cr-text-muted); }
.cr-badge-pending { background: rgba(253,203,110,0.15); color: var(--cr-orange); }
.cr-badge-published { background: rgba(0,184,148,0.15); color: var(--cr-green); }
.cr-badge-suspended { background: rgba(214,48,49,0.15); color: var(--cr-red); }
.cr-badge-agent { background: rgba(108,92,231,0.15); color: var(--cr-accent-light); }
.cr-badge-tool { background: rgba(9,132,227,0.15); color: var(--cr-blue); }
.cr-badge-fleet { background: rgba(225,112,85,0.15); color: var(--cr-fire); }
.cr-badge-template { background: rgba(0,184,148,0.15); color: var(--cr-green); }
.cr-badge-integration { background: rgba(253,203,110,0.15); color: var(--cr-orange); }

/* Product Cards Grid */
.cr-products-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 12px;
}
.cr-products-toolbar .cr-view-toggle {
    display: flex;
    gap: 6px;
}
.cr-view-btn {
    padding: 8px 12px;
    background: var(--cr-surface);
    border: 1px solid var(--cr-border);
    border-radius: 8px;
    color: var(--cr-text-muted);
    cursor: pointer;
    transition: all 0.2s;
}
.cr-view-btn.active, .cr-view-btn:hover {
    background: var(--cr-accent);
    border-color: var(--cr-accent);
    color: #fff;
}
.cr-products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}
.cr-products-grid.list-view {
    grid-template-columns: 1fr;
}
.cr-product-card {
    background: var(--cr-surface);
    border: 1px solid var(--cr-border);
    border-radius: var(--cr-radius);
    padding: 20px;
    transition: border-color 0.3s, transform 0.3s;
}
.cr-product-card:hover {
    border-color: var(--cr-accent);
    transform: translateY(-2px);
}
.cr-product-top {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    margin-bottom: 14px;
}
.cr-product-icon {
    width: 52px;
    height: 52px;
    border-radius: 12px;
    background: var(--cr-surface-2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    color: var(--cr-accent-light);
    flex-shrink: 0;
}
.cr-product-info h4 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.05rem;
    font-weight: 600;
    color: #fff;
    margin: 0 0 6px;
}
.cr-product-badges { display: flex; gap: 6px; flex-wrap: wrap; }
.cr-product-meta {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 14px;
    font-size: 0.85rem;
    color: var(--cr-text-muted);
}
.cr-product-meta i { margin-right: 4px; }
.cr-product-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.cr-btn {
    padding: 8px 16px;
    border: none;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.cr-btn-primary { background: var(--cr-accent); color: #fff; }
.cr-btn-primary:hover { background: #5a4bd1; }
.cr-btn-success { background: var(--cr-green); color: #fff; }
.cr-btn-success:hover { background: #00a381; }
.cr-btn-danger { background: rgba(214,48,49,0.15); color: var(--cr-red); }
.cr-btn-danger:hover { background: rgba(214,48,49,0.3); }
.cr-btn-outline { background: transparent; color: var(--cr-text-muted); border: 1px solid var(--cr-border); }
.cr-btn-outline:hover { border-color: var(--cr-accent); color: var(--cr-accent-light); }
.cr-btn-lg {
    padding: 14px 32px;
    font-size: 1rem;
    border-radius: 12px;
}

/* Form Styles */
.cr-form {
    max-width: 800px;
}
.cr-form-group {
    margin-bottom: 24px;
}
.cr-form-group label {
    display: block;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--cr-text);
    margin-bottom: 8px;
}
.cr-form-group label .required {
    color: var(--cr-fire);
    margin-left: 2px;
}
.cr-form-group .hint {
    font-size: 0.8rem;
    color: var(--cr-text-muted);
    margin-top: 6px;
}
.cr-input, .cr-select, .cr-textarea {
    width: 100%;
    padding: 12px 16px;
    background: var(--cr-surface-2);
    border: 1px solid var(--cr-border);
    border-radius: 10px;
    color: var(--cr-text);
    font-size: 0.95rem;
    outline: none;
    transition: border-color 0.3s;
    font-family: inherit;
    box-sizing: border-box;
}
.cr-input:focus, .cr-select:focus, .cr-textarea:focus {
    border-color: var(--cr-accent);
}
.cr-textarea { min-height: 160px; resize: vertical; }
.cr-select { cursor: pointer; }

/* Type Selector */
.cr-type-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 12px;
}
.cr-type-option {
    padding: 16px 12px;
    background: var(--cr-surface-2);
    border: 2px solid var(--cr-border);
    border-radius: 12px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
}
.cr-type-option:hover { border-color: var(--cr-accent); }
.cr-type-option.selected {
    border-color: var(--cr-accent);
    background: rgba(108,92,231,0.1);
}
.cr-type-option i {
    font-size: 1.5rem;
    display: block;
    margin-bottom: 8px;
    color: var(--cr-accent-light);
}
.cr-type-option span {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--cr-text);
}

/* Price Toggle */
.cr-price-toggle {
    display: flex;
    gap: 12px;
    margin-bottom: 12px;
}
.cr-price-option {
    padding: 10px 24px;
    background: var(--cr-surface-2);
    border: 1px solid var(--cr-border);
    border-radius: 50px;
    color: var(--cr-text-muted);
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}
.cr-price-option.selected {
    background: var(--cr-accent);
    border-color: var(--cr-accent);
    color: #fff;
}

/* Screenshot previews */
.cr-screenshots {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}
.cr-screenshot-slot {
    width: 120px;
    height: 80px;
    background: var(--cr-surface-2);
    border: 2px dashed var(--cr-border);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: border-color 0.3s;
    overflow: hidden;
    position: relative;
}
.cr-screenshot-slot:hover { border-color: var(--cr-accent); }
.cr-screenshot-slot img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.cr-screenshot-slot .placeholder {
    color: var(--cr-text-muted);
    font-size: 0.75rem;
    text-align: center;
}
.cr-screenshot-slot .remove-ss {
    position: absolute;
    top: 2px;
    right: 2px;
    width: 20px;
    height: 20px;
    background: var(--cr-red);
    color: #fff;
    border: none;
    border-radius: 50%;
    font-size: 0.6rem;
    cursor: pointer;
    display: none;
}
.cr-screenshot-slot:hover .remove-ss { display: flex; align-items: center; justify-content: center; }

/* Icon upload */
.cr-icon-upload {
    width: 80px;
    height: 80px;
    background: var(--cr-surface-2);
    border: 2px dashed var(--cr-border);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: border-color 0.3s;
    overflow: hidden;
}
.cr-icon-upload:hover { border-color: var(--cr-accent); }
.cr-icon-upload img { width: 100%; height: 100%; object-fit: cover; }
.cr-icon-upload i { font-size: 1.5rem; color: var(--cr-text-muted); }

/* JSON Editor */
.cr-json-editor {
    font-family: 'Fira Code', 'Courier New', monospace;
    min-height: 200px;
    font-size: 0.88rem;
    tab-size: 2;
}

/* Markdown Preview */
.cr-md-tabs {
    display: flex;
    gap: 0;
    margin-bottom: -1px;
    position: relative;
    z-index: 1;
}
.cr-md-tab {
    padding: 8px 20px;
    background: var(--cr-surface-2);
    border: 1px solid var(--cr-border);
    color: var(--cr-text-muted);
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;
}
.cr-md-tab:first-child { border-radius: 8px 0 0 0; }
.cr-md-tab:last-child { border-radius: 0 8px 0 0; }
.cr-md-tab.active {
    background: var(--cr-surface);
    color: var(--cr-accent-light);
    border-bottom-color: var(--cr-surface);
}
.cr-md-preview {
    display: none;
    background: var(--cr-surface);
    border: 1px solid var(--cr-border);
    border-radius: 0 10px 10px 10px;
    padding: 20px;
    min-height: 160px;
    color: var(--cr-text);
    font-size: 0.92rem;
    line-height: 1.6;
}
.cr-md-preview.active { display: block; }

/* Review Cards */
.cr-review-card {
    background: var(--cr-surface);
    border: 1px solid var(--cr-border);
    border-radius: var(--cr-radius);
    padding: 20px;
    margin-bottom: 16px;
    transition: border-color 0.3s;
}
.cr-review-card:hover { border-color: var(--cr-accent); }
.cr-review-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
}
.cr-review-stars { color: var(--cr-orange); font-size: 0.95rem; }
.cr-review-product {
    font-size: 0.82rem;
    color: var(--cr-accent-light);
    display: flex;
    align-items: center;
    gap: 4px;
}
.cr-review-body {
    font-size: 0.9rem;
    color: var(--cr-text);
    line-height: 1.5;
    margin-bottom: 10px;
}
.cr-review-date {
    font-size: 0.8rem;
    color: var(--cr-text-muted);
}
.cr-review-response {
    background: var(--cr-surface-2);
    border-left: 3px solid var(--cr-accent);
    padding: 12px 16px;
    border-radius: 0 8px 8px 0;
    margin-top: 12px;
    font-size: 0.88rem;
    color: var(--cr-text-muted);
}
.cr-review-respond-form {
    margin-top: 12px;
    display: none;
}
.cr-review-respond-form.show { display: block; }

/* Earnings */
.cr-earnings-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
    margin-bottom: 28px;
}
.cr-earning-card {
    background: var(--cr-surface);
    border: 1px solid var(--cr-border);
    border-radius: var(--cr-radius);
    padding: 20px;
    text-align: center;
}
.cr-earning-card .value {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--cr-green);
}
.cr-earning-card .label {
    font-size: 0.82rem;
    color: var(--cr-text-muted);
    margin-top: 4px;
}

/* Empty State */
.cr-empty {
    text-align: center;
    padding: 60px 20px;
    color: var(--cr-text-muted);
}
.cr-empty i {
    font-size: 3rem;
    color: var(--cr-accent);
    margin-bottom: 16px;
    opacity: 0.5;
}
.cr-empty h3 {
    font-family: 'Space Grotesk', sans-serif;
    color: #fff;
    margin-bottom: 8px;
}

/* Toast Notifications */
.cr-toast {
    position: fixed;
    bottom: 30px;
    right: 30px;
    padding: 14px 24px;
    background: var(--cr-surface);
    border: 1px solid var(--cr-border);
    border-radius: 12px;
    color: var(--cr-text);
    font-size: 0.9rem;
    font-weight: 500;
    box-shadow: 0 8px 32px rgba(0,0,0,0.4);
    z-index: 10000;
    display: flex;
    align-items: center;
    gap: 10px;
    transform: translateY(100px);
    opacity: 0;
    transition: all 0.3s ease;
}
.cr-toast.show { transform: translateY(0); opacity: 1; }
.cr-toast.success { border-color: var(--cr-green); }
.cr-toast.error { border-color: var(--cr-red); }
.cr-toast i { font-size: 1.1rem; }

/* Loading Spinner */
.cr-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 60px;
}
.cr-spinner {
    width: 36px;
    height: 36px;
    border: 3px solid var(--cr-border);
    border-top-color: var(--cr-accent);
    border-radius: 50%;
    animation: crSpin 0.8s linear infinite;
}
@keyframes crSpin { to { transform: rotate(360deg); } }

/* Responsive */
@media (max-width: 900px) {
    .cr-sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        transform: translateX(-100%);
    }
    .cr-sidebar.open { transform: translateX(0); box-shadow: 4px 0 30px rgba(0,0,0,0.5); }
    .cr-sidebar-toggle { display: flex; align-items: center; justify-content: center; }
    .cr-main { padding: 24px 16px; }
    .cr-stats { grid-template-columns: repeat(2, 1fr); }
    .cr-products-grid { grid-template-columns: 1fr; }
    .cr-type-grid { grid-template-columns: repeat(2, 1fr); }
    .cr-earnings-summary { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 480px) {
    .cr-stats { grid-template-columns: 1fr; }
    .cr-earnings-summary { grid-template-columns: 1fr; }
}
</style>

<main id="main">
<div class="cr-layout">

<!-- Sidebar -->
<aside class="cr-sidebar" id="crSidebar">
    <div class="cr-sidebar-header">
        <h2><i class="fas fa-store" style="color:var(--cr-accent-light);margin-right:8px;"></i>Creator Studio</h2>
        <p>Manage your marketplace</p>
    </div>
    <a class="cr-nav-item active" data-section="dashboard" onclick="crNav('dashboard', this)">
        <i class="fas fa-th-large"></i> Dashboard
    </a>
    <a class="cr-nav-item" data-section="products" onclick="crNav('products', this)">
        <i class="fas fa-box"></i> My Products
    </a>
    <a class="cr-nav-item" data-section="add-product" onclick="crNav('add-product', this)">
        <i class="fas fa-plus-circle"></i> Add New Product
    </a>
    <a class="cr-nav-item" data-section="earnings" onclick="crNav('earnings', this)">
        <i class="fas fa-coins"></i> Earnings
    </a>
    <a class="cr-nav-item" data-section="reviews" onclick="crNav('reviews', this)">
        <i class="fas fa-star"></i> Reviews & Ratings
    </a>
    <a class="cr-nav-item" data-section="analytics" onclick="crNav('analytics', this)">
        <i class="fas fa-chart-line"></i> Analytics
    </a>
    <a class="cr-nav-item" data-section="settings" onclick="crNav('settings', this)">
        <i class="fas fa-cog"></i> Settings
    </a>
    <div style="padding:20px 24px;margin-top:auto;">
        <a href="/marketplace" class="cr-btn cr-btn-outline" style="width:100%;justify-content:center;">
            <i class="fas fa-arrow-left"></i> Back to Marketplace
        </a>
    </div>
</aside>

<!-- Mobile sidebar toggle -->
<button class="cr-sidebar-toggle" id="crSidebarToggle" onclick="document.getElementById('crSidebar').classList.toggle('open')">
    <i class="fas fa-bars"></i>
</button>

<!-- Main Content -->
<div class="cr-main">

    <!-- ═══════════ DASHBOARD ═══════════ -->
    <section class="cr-section active" id="sec-dashboard">
        <h2 class="cr-section-title"><i class="fas fa-th-large"></i> Dashboard Overview</h2>

        <div class="cr-stats">
            <div class="cr-stat">
                <div class="cr-stat-icon" style="background:rgba(108,92,231,0.15);color:var(--cr-accent-light);">
                    <i class="fas fa-box"></i>
                </div>
                <div class="cr-stat-value" id="statProducts">-</div>
                <div class="cr-stat-label">Total Products</div>
            </div>
            <div class="cr-stat">
                <div class="cr-stat-icon" style="background:rgba(9,132,227,0.15);color:var(--cr-blue);">
                    <i class="fas fa-download"></i>
                </div>
                <div class="cr-stat-value" id="statDownloads">-</div>
                <div class="cr-stat-label">Total Downloads</div>
            </div>
            <div class="cr-stat">
                <div class="cr-stat-icon" style="background:rgba(0,184,148,0.15);color:var(--cr-green);">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="cr-stat-value" id="statRevenue">-</div>
                <div class="cr-stat-label">Total Revenue</div>
            </div>
            <div class="cr-stat">
                <div class="cr-stat-icon" style="background:rgba(253,203,110,0.15);color:var(--cr-orange);">
                    <i class="fas fa-star"></i>
                </div>
                <div class="cr-stat-value" id="statRating">-</div>
                <div class="cr-stat-label">Average Rating</div>
            </div>
        </div>

        <!-- Revenue Chart -->
        <div class="cr-chart-wrap">
            <h3><i class="fas fa-chart-area" style="color:var(--cr-green);margin-right:8px;"></i>Revenue — Last 30 Days</h3>
            <canvas id="revenueChart" height="260"></canvas>
        </div>

        <!-- Recent Downloads -->
        <div class="cr-table-wrap">
            <h3><i class="fas fa-download" style="color:var(--cr-blue);margin-right:8px;"></i>Recent Sales</h3>
            <table class="cr-table">
                <thead><tr><th>Product</th><th>Type</th><th>Price</th><th>Your Earnings</th><th>Date</th></tr></thead>
                <tbody id="recentDownloadsBody">
                    <tr><td colspan="5" class="cr-loading"><div class="cr-spinner"></div></td></tr>
                </tbody>
            </table>
        </div>

        <!-- Recent Reviews -->
        <div class="cr-table-wrap">
            <h3><i class="fas fa-comments" style="color:var(--cr-orange);margin-right:8px;"></i>Recent Reviews</h3>
            <div id="recentReviewsList" style="padding:16px 24px;">
                <div class="cr-loading"><div class="cr-spinner"></div></div>
            </div>
        </div>
    </section>

    <!-- ═══════════ MY PRODUCTS ═══════════ -->
    <section class="cr-section" id="sec-products">
        <h2 class="cr-section-title"><i class="fas fa-box"></i> My Products</h2>

        <div class="cr-products-toolbar">
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <select class="cr-select" style="width:auto;" id="filterStatus" onchange="loadProducts()">
                    <option value="">All Status</option>
                    <option value="draft">Draft</option>
                    <option value="pending">Pending</option>
                    <option value="published">Published</option>
                </select>
                <select class="cr-select" style="width:auto;" id="filterType" onchange="loadProducts()">
                    <option value="">All Types</option>
                    <option value="agent">Agent</option>
                    <option value="tool">Tool</option>
                    <option value="fleet">Fleet</option>
                    <option value="template">Template</option>
                    <option value="integration">Integration</option>
                </select>
            </div>
            <div class="cr-view-toggle">
                <button class="cr-view-btn active" onclick="setView('grid', this)" title="Grid view"><i class="fas fa-th-large"></i></button>
                <button class="cr-view-btn" onclick="setView('list', this)" title="List view"><i class="fas fa-list"></i></button>
            </div>
        </div>

        <div class="cr-products-grid" id="productsGrid">
            <div class="cr-loading" style="grid-column:1/-1;"><div class="cr-spinner"></div></div>
        </div>

        <div id="productsPagination" style="text-align:center;margin-top:16px;"></div>
    </section>

    <!-- ═══════════ ADD NEW PRODUCT ═══════════ -->
    <section class="cr-section" id="sec-add-product">
        <h2 class="cr-section-title"><i class="fas fa-plus-circle"></i> Add New Product</h2>

        <form class="cr-form" id="addProductForm" onsubmit="return submitProduct(event)">
            <!-- Item Type -->
            <div class="cr-form-group">
                <label>Item Type <span class="required">*</span></label>
                <div class="cr-type-grid">
                    <div class="cr-type-option" data-type="agent" onclick="selectType(this)">
                        <i class="fas fa-robot"></i>
                        <span>Agent Template</span>
                    </div>
                    <div class="cr-type-option" data-type="tool" onclick="selectType(this)">
                        <i class="fas fa-wrench"></i>
                        <span>Custom Tool</span>
                    </div>
                    <div class="cr-type-option" data-type="fleet" onclick="selectType(this)">
                        <i class="fas fa-users-cog"></i>
                        <span>Fleet Config</span>
                    </div>
                    <div class="cr-type-option" data-type="template" onclick="selectType(this)">
                        <i class="fas fa-file-alt"></i>
                        <span>Workflow Template</span>
                    </div>
                    <div class="cr-type-option" data-type="integration" onclick="selectType(this)">
                        <i class="fas fa-plug"></i>
                        <span>Integration</span>
                    </div>
                </div>
                <input type="hidden" id="productType" name="item_type" required>
            </div>

            <!-- Title -->
            <div class="cr-form-group">
                <label for="productTitle">Title <span class="required">*</span></label>
                <input type="text" class="cr-input" id="productTitle" name="title" placeholder="e.g. Customer Support Agent" required minlength="3" maxlength="100">
                <p class="hint">3-100 characters. Make it descriptive and searchable.</p>
            </div>

            <!-- Description -->
            <div class="cr-form-group">
                <label>Description <span class="required">*</span></label>
                <div class="cr-md-tabs">
                    <button type="button" class="cr-md-tab active" onclick="toggleMdPreview('write', this)">Write</button>
                    <button type="button" class="cr-md-tab" onclick="toggleMdPreview('preview', this)">Preview</button>
                </div>
                <textarea class="cr-textarea" id="productDesc" name="description" placeholder="Describe what your product does, its features, requirements, and how to set it up. Supports Markdown. (Min 50 characters)" required minlength="50"></textarea>
                <div class="cr-md-preview" id="mdPreview"></div>
                <p class="hint">Minimum 50 characters. Markdown supported.</p>
            </div>

            <!-- Category -->
            <div class="cr-form-group">
                <label for="productCategory">Category <span class="required">*</span></label>
                <select class="cr-select" id="productCategory" name="category" required>
                    <option value="">Select a category…</option>
                    <option value="Business">Business</option>
                    <option value="Communication">Communication</option>
                    <option value="Data">Data</option>
                    <option value="Development">Development</option>
                    <option value="Finance">Finance</option>
                    <option value="Health">Health</option>
                    <option value="Legal">Legal</option>
                    <option value="Marketing">Marketing</option>
                    <option value="Productivity">Productivity</option>
                    <option value="Voice">Voice</option>
                </select>
            </div>

            <!-- Tags -->
            <div class="cr-form-group">
                <label for="productTags">Tags</label>
                <input type="text" class="cr-input" id="productTags" name="tags" placeholder="e.g. customer-support, chatbot, automation">
                <p class="hint">Comma-separated, max 5 tags.</p>
            </div>

            <!-- Pricing -->
            <div class="cr-form-group">
                <label>Pricing</label>
                <div class="cr-price-toggle">
                    <div class="cr-price-option selected" onclick="setPricing('free', this)">Free</div>
                    <div class="cr-price-option" onclick="setPricing('paid', this)">Paid</div>
                </div>
                <div id="priceInputWrap" style="display:none;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="color:var(--cr-text);font-size:1.2rem;font-weight:700;">$</span>
                        <input type="number" class="cr-input" id="productPrice" name="price" min="0.99" max="499.99" step="0.01" placeholder="9.99" style="max-width:180px;">
                    </div>
                    <p class="hint">$0.99 - $499.99. You earn 70% of each sale.</p>
                </div>
            </div>

            <!-- Icon Upload -->
            <div class="cr-form-group">
                <label>Product Icon</label>
                <div class="cr-icon-upload" id="iconUpload" onclick="document.getElementById('iconFile').click()">
                    <i class="fas fa-image"></i>
                </div>
                <input type="file" id="iconFile" accept="image/*" style="display:none;" onchange="previewIcon(this)">
                <p class="hint">Recommended: 256×256px PNG or SVG</p>
            </div>

            <!-- Screenshots -->
            <div class="cr-form-group">
                <label>Screenshots (up to 5)</label>
                <div class="cr-screenshots" id="screenshotsContainer">
                    <div class="cr-screenshot-slot" onclick="addScreenshot(0)">
                        <span class="placeholder"><i class="fas fa-plus"></i><br>Add</span>
                    </div>
                    <div class="cr-screenshot-slot" onclick="addScreenshot(1)">
                        <span class="placeholder"><i class="fas fa-plus"></i><br>Add</span>
                    </div>
                    <div class="cr-screenshot-slot" onclick="addScreenshot(2)">
                        <span class="placeholder"><i class="fas fa-plus"></i><br>Add</span>
                    </div>
                    <div class="cr-screenshot-slot" onclick="addScreenshot(3)">
                        <span class="placeholder"><i class="fas fa-plus"></i><br>Add</span>
                    </div>
                    <div class="cr-screenshot-slot" onclick="addScreenshot(4)">
                        <span class="placeholder"><i class="fas fa-plus"></i><br>Add</span>
                    </div>
                </div>
                <input type="file" id="ssFile" accept="image/*" style="display:none;">
            </div>

            <!-- Configuration JSON -->
            <div class="cr-form-group">
                <label>Configuration JSON <span style="font-weight:400;color:var(--cr-text-muted);">(optional)</span></label>
                <textarea class="cr-textarea cr-json-editor" id="productConfig" name="config_json" placeholder='{\n  "model": "gpt-4",\n  "temperature": 0.7,\n  "tools": []\n}'></textarea>
                <p class="hint">For agent/tool configs. Must be valid JSON.</p>
            </div>

            <!-- Submit Buttons -->
            <div style="display:flex;gap:12px;flex-wrap:wrap;padding-top:8px;">
                <button type="submit" name="submit_action" value="draft" class="cr-btn cr-btn-outline cr-btn-lg">
                    <i class="fas fa-save"></i> Save as Draft
                </button>
                <button type="submit" name="submit_action" value="review" class="cr-btn cr-btn-primary cr-btn-lg">
                    <i class="fas fa-paper-plane"></i> Submit for Review
                </button>
            </div>
        </form>
    </section>

    <!-- ═══════════ EARNINGS ═══════════ -->
    <section class="cr-section" id="sec-earnings">
        <h2 class="cr-section-title"><i class="fas fa-coins"></i> Earnings</h2>

        <div class="cr-earnings-summary" id="earningsSummary">
            <div class="cr-earning-card">
                <div class="value" id="earnLifetime">$0.00</div>
                <div class="label">Lifetime Earnings</div>
            </div>
            <div class="cr-earning-card">
                <div class="value" id="earnBalance">$0.00</div>
                <div class="label">Current Balance</div>
            </div>
            <div class="cr-earning-card">
                <div class="value" id="earnPaidOut" style="color:var(--cr-accent-light);">$0.00</div>
                <div class="label">Paid Out</div>
            </div>
            <div class="cr-earning-card">
                <div class="value" id="earnSales" style="color:var(--cr-blue);">0</div>
                <div class="label">Total Sales</div>
            </div>
        </div>

        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
            <p style="color:var(--cr-text-muted);font-size:0.9rem;margin:0;">
                <i class="fas fa-info-circle" style="color:var(--cr-accent-light);margin-right:4px;"></i>
                Commission: <strong style="color:var(--cr-green);">70%</strong> creator / 30% platform. Min payout: $25.
            </p>
            <button class="cr-btn cr-btn-success cr-btn-lg" id="payoutBtn" onclick="requestPayout()" disabled>
                <i class="fas fa-wallet"></i> Request Payout
            </button>
        </div>

        <!-- Earnings by product -->
        <div class="cr-table-wrap">
            <h3><i class="fas fa-box" style="color:var(--cr-accent-light);margin-right:8px;"></i>Earnings by Product</h3>
            <table class="cr-table">
                <thead><tr><th>Product</th><th>Type</th><th>Sales</th><th>Revenue</th><th>Commission (30%)</th><th>Your Earnings (70%)</th></tr></thead>
                <tbody id="earningsByProduct">
                    <tr><td colspan="6" class="cr-loading"><div class="cr-spinner"></div></td></tr>
                </tbody>
            </table>
        </div>

        <!-- Payout History -->
        <div class="cr-table-wrap">
            <h3><i class="fas fa-history" style="color:var(--cr-orange);margin-right:8px;"></i>Payout History</h3>
            <table class="cr-table">
                <thead><tr><th>ID</th><th>Amount</th><th>Method</th><th>Status</th><th>Requested</th><th>Processed</th></tr></thead>
                <tbody id="payoutHistory">
                    <tr><td colspan="6" style="text-align:center;color:var(--cr-text-muted);padding:30px;">No payouts yet</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- ═══════════ REVIEWS ═══════════ -->
    <section class="cr-section" id="sec-reviews">
        <h2 class="cr-section-title"><i class="fas fa-star"></i> Reviews & Ratings</h2>

        <div style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;">
            <select class="cr-select" style="width:auto;" id="reviewRatingFilter" onchange="loadReviews()">
                <option value="">All Ratings</option>
                <option value="5">5 Stars</option>
                <option value="4">4 Stars</option>
                <option value="3">3 Stars</option>
                <option value="2">2 Stars</option>
                <option value="1">1 Star</option>
            </select>
        </div>

        <div id="reviewsList">
            <div class="cr-loading"><div class="cr-spinner"></div></div>
        </div>
        <div id="reviewsPagination" style="text-align:center;margin-top:16px;"></div>
    </section>

    <!-- ═══════════ ANALYTICS ═══════════ -->
    <section class="cr-section" id="sec-analytics">
        <h2 class="cr-section-title"><i class="fas fa-chart-line"></i> Analytics</h2>

        <div style="margin-bottom:20px;">
            <select class="cr-select" style="width:auto;max-width:300px;" id="analyticsProductSelect" onchange="loadAnalytics(this.value)">
                <option value="">All Products (Overview)</option>
            </select>
        </div>

        <div class="cr-chart-wrap">
            <h3><i class="fas fa-chart-bar" style="color:var(--cr-accent-light);margin-right:8px;"></i>Sales & Revenue — Last 30 Days</h3>
            <canvas id="analyticsChart" height="280"></canvas>
        </div>

        <div id="analyticsTopProducts" style="display:none;">
            <div class="cr-table-wrap">
                <h3><i class="fas fa-trophy" style="color:var(--cr-orange);margin-right:8px;"></i>Top Products</h3>
                <table class="cr-table">
                    <thead><tr><th>Product</th><th>Type</th><th>Sales</th><th>Earnings</th></tr></thead>
                    <tbody id="topProductsBody"></tbody>
                </table>
            </div>
        </div>

        <div id="analyticsRatingBreakdown" style="display:none;">
            <div class="cr-table-wrap">
                <h3><i class="fas fa-star" style="color:var(--cr-orange);margin-right:8px;"></i>Rating Breakdown</h3>
                <div id="ratingBreakdownContent" style="padding:20px 24px;"></div>
            </div>
        </div>
    </section>

    <!-- ═══════════ SETTINGS ═══════════ -->
    <section class="cr-section" id="sec-settings">
        <h2 class="cr-section-title"><i class="fas fa-cog"></i> Settings</h2>

        <div class="cr-form" style="max-width:600px;">
            <div class="cr-form-group">
                <label>Seller Display Name</label>
                <input type="text" class="cr-input" placeholder="Your public creator name" id="sellerName">
            </div>
            <div class="cr-form-group">
                <label>Payout Method</label>
                <select class="cr-select" id="payoutMethod">
                    <option value="stripe">Stripe (default)</option>
                    <option value="paypal">PayPal</option>
                    <option value="bank">Bank Transfer</option>
                </select>
            </div>
            <div class="cr-form-group">
                <label>Payout Email</label>
                <input type="email" class="cr-input" placeholder="email@example.com" id="payoutEmail">
            </div>
            <div class="cr-form-group">
                <label>Email Notifications</label>
                <div style="display:flex;flex-direction:column;gap:12px;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:400;">
                        <input type="checkbox" checked> New sale notification
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:400;">
                        <input type="checkbox" checked> New review notification
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:400;">
                        <input type="checkbox" checked> Payout processed notification
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:400;">
                        <input type="checkbox"> Weekly summary email
                    </label>
                </div>
            </div>
            <button class="cr-btn cr-btn-primary cr-btn-lg" onclick="showToast('Settings saved!','success')">
                <i class="fas fa-save"></i> Save Settings
            </button>
        </div>
    </section>

</div><!-- /.cr-main -->
</div><!-- /.cr-layout -->
</main>

<!-- Chart.js -->
<script src="/assets/js/vendor/chart.umd.min.js"></script>

<script src="/assets/js/marketplace-creator-engine.js"></script>


<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
