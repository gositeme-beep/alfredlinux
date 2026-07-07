<?php
$page_title       = 'Webhook Events — Real-Time Notifications | Alfred by GoSiteMe';
$page_description = 'Get real-time notifications when things happen in Alfred. Subscribe to agent, call, fleet, tool, marketplace, and billing events via webhook.';
$page_canonical   = 'https://gositeme.com/webhooks.php';
$page_og_title    = 'Webhook Events — Alfred by GoSiteMe';
$page_og_description = 'Real-time webhook notifications for agent, call, fleet, and billing events. Manage endpoints, view delivery logs, and verify signatures.';
require_once __DIR__ . '/includes/site-header.inc.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$is_logged_in = !empty($_SESSION['logged_in']) && !empty($_SESSION['client_id']);
$client_id    = $is_logged_in ? (int) $_SESSION['client_id'] : 0;
$client_name  = $_SESSION['client_name'] ?? '';
$client_email = $_SESSION['client_email'] ?? '';

// Event categories for the UI
$eventCategories = [
    'Agent' => [
        'agent.created'        => ['label' => 'Agent Created',        'desc' => 'Fired when a new agent is created.'],
        'agent.deployed'       => ['label' => 'Agent Deployed',       'desc' => 'Fired when an agent deploys to production.'],
        'agent.error'          => ['label' => 'Agent Error',          'desc' => 'Fired when an agent encounters a runtime error.'],
        'agent.status_changed' => ['label' => 'Agent Status Changed', 'desc' => 'Fired when an agent changes status (online/offline/maintenance).'],
    ],
    'Call' => [
        'call.started'     => ['label' => 'Call Started',     'desc' => 'Fired when a voice call begins.'],
        'call.ended'       => ['label' => 'Call Ended',       'desc' => 'Fired when a voice call completes, includes duration and summary.'],
        'call.transferred' => ['label' => 'Call Transferred', 'desc' => 'Fired when a call is transferred to another agent or number.'],
        'call.recorded'    => ['label' => 'Call Recorded',    'desc' => 'Fired when a call recording is available.'],
    ],
    'Fleet' => [
        'fleet.deployed'     => ['label' => 'Fleet Deployed',     'desc' => 'Fired when a fleet configuration is deployed.'],
        'fleet.alert'        => ['label' => 'Fleet Alert',        'desc' => 'Fired on fleet-level alerts (capacity, errors, performance).'],
        'fleet.agent_joined' => ['label' => 'Agent Joined Fleet', 'desc' => 'Fired when an agent is added to a fleet.'],
        'fleet.agent_left'   => ['label' => 'Agent Left Fleet',   'desc' => 'Fired when an agent is removed from a fleet.'],
    ],
    'Tool' => [
        'tool.executed'     => ['label' => 'Tool Executed',    'desc' => 'Fired when an agent executes a tool.'],
        'tool.error'        => ['label' => 'Tool Error',       'desc' => 'Fired when a tool execution fails.'],
        'tool.rate_limited' => ['label' => 'Tool Rate Limited','desc' => 'Fired when a tool call is rate-limited.'],
    ],
    'Marketplace' => [
        'marketplace.published' => ['label' => 'Extension Published', 'desc' => 'Fired when you publish an extension to the marketplace.'],
        'marketplace.purchased' => ['label' => 'Extension Purchased', 'desc' => 'Fired when someone purchases your marketplace extension.'],
        'marketplace.review'    => ['label' => 'Extension Review',    'desc' => 'Fired when your extension receives a review.'],
    ],
    'Billing' => [
        'billing.payment_succeeded' => ['label' => 'Payment Succeeded', 'desc' => 'Fired on successful payment processing.'],
        'billing.payment_failed'    => ['label' => 'Payment Failed',    'desc' => 'Fired when a payment attempt fails.'],
        'billing.usage_alert'       => ['label' => 'Usage Alert',       'desc' => 'Fired when usage approaches or exceeds thresholds.'],
    ],
];

// Category icons for FA
$catIcons = [
    'Agent'       => 'fa-robot',
    'Call'        => 'fa-phone-volume',
    'Fleet'       => 'fa-network-wired',
    'Tool'        => 'fa-wrench',
    'Marketplace' => 'fa-store',
    'Billing'     => 'fa-credit-card',
];
?>

<style>
/* ===== WEBHOOKS PAGE — SCOPED STYLES ===== */
.wh-page {
    --wh-bg: #0a0a14;
    --wh-surface: #12121e;
    --wh-surface-2: #1a1a2e;
    --wh-border: rgba(255,255,255,.06);
    --wh-text: #e2e8f0;
    --wh-text-muted: #94a3b8;
    --wh-accent: #6c5ce7;
    --wh-accent-light: #a29bfe;
    --wh-blue: #0984e3;
    --wh-green: #00b894;
    --wh-orange: #e17055;
    --wh-red: #d63031;
    --wh-radius: 16px;
    --wh-radius-sm: 12px;
    --wh-transition: .3s cubic-bezier(.4,0,.2,1);
    --wh-shadow: 0 8px 32px rgba(0,0,0,.35);
    --wh-glow: 0 0 60px rgba(108,92,231,.15);
    --wh-gradient: linear-gradient(135deg, #6c5ce7, #0984e3);
    --wh-gradient-soft: linear-gradient(135deg, rgba(108,92,231,.12), rgba(9,132,227,.12));
}

.wh-page { background: var(--wh-bg); color: var(--wh-text); overflow-x: hidden; }
.wh-page *, .wh-page *::before, .wh-page *::after { box-sizing: border-box; }

.wh-container { max-width: 1200px; margin: 0 auto; padding: 0 24px; }
.wh-section { padding: 80px 0; }
.wh-section--alt { background: var(--wh-surface); }

/* Badge */
.wh-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 16px; border-radius: 50px; font-size: .78rem; font-weight: 600;
    letter-spacing: .5px; text-transform: uppercase;
    background: var(--wh-gradient-soft); color: var(--wh-accent-light);
    border: 1px solid rgba(108,92,231,.2);
}
.wh-section-title {
    font-family: 'Space Grotesk', sans-serif; font-size: clamp(1.8rem, 4vw, 2.6rem);
    font-weight: 800; line-height: 1.15; margin: 0 0 12px;
    background: var(--wh-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
}
.wh-section-sub {
    font-size: 1.1rem; color: var(--wh-text-muted); max-width: 640px; margin: 0 auto 48px; line-height: 1.6;
}
.wh-section-header { text-align: center; margin-bottom: 48px; }

/* Buttons */
.wh-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 14px 32px; border: none; border-radius: 50px; cursor: pointer;
    font-size: 1rem; font-weight: 700; text-decoration: none;
    background: var(--wh-gradient); color: #fff;
    box-shadow: 0 4px 24px rgba(108,92,231,.35);
    transition: transform var(--wh-transition), box-shadow var(--wh-transition);
    font-family: inherit;
}
.wh-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 32px rgba(108,92,231,.5); color: #fff; text-decoration: none; }
.wh-btn--sm { padding: 8px 18px; font-size: .85rem; }
.wh-btn--ghost {
    background: transparent; border: 2px solid rgba(255,255,255,.15);
    color: var(--wh-text); box-shadow: none;
}
.wh-btn--ghost:hover { border-color: var(--wh-accent); color: var(--wh-accent-light); background: rgba(108,92,231,.08); }
.wh-btn--danger { background: var(--wh-red); box-shadow: 0 4px 24px rgba(214,48,49,.35); }
.wh-btn--danger:hover { box-shadow: 0 8px 32px rgba(214,48,49,.5); }
.wh-btn--green { background: var(--wh-green); box-shadow: 0 4px 24px rgba(0,184,148,.35); }

/* Hero */
.wh-hero { padding: 140px 0 80px; text-align: center; position: relative; overflow: hidden; }
.wh-hero::before {
    content: ''; position: absolute; inset: 0;
    background: radial-gradient(ellipse 600px 400px at 50% 0%, rgba(108,92,231,.15), transparent);
    pointer-events: none;
}
.wh-hero-title {
    font-family: 'Space Grotesk', sans-serif; font-size: clamp(2.2rem, 5vw, 3.4rem);
    font-weight: 800; line-height: 1.1; margin: 16px 0 20px;
    background: var(--wh-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
}
.wh-hero-sub { font-size: 1.2rem; color: var(--wh-text-muted); max-width: 600px; margin: 0 auto; line-height: 1.6; }

/* Cards */
.wh-card {
    background: var(--wh-surface); border: 1px solid var(--wh-border);
    border-radius: var(--wh-radius); padding: 28px; transition: var(--wh-transition);
}
.wh-card:hover { border-color: rgba(108,92,231,.25); box-shadow: var(--wh-glow); }

/* Table */
.wh-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
.wh-table {
    width: 100%; border-collapse: collapse; font-size: .9rem;
}
.wh-table th {
    text-align: left; font-weight: 600; color: var(--wh-text-muted);
    padding: 14px 16px; border-bottom: 1px solid var(--wh-border);
    text-transform: uppercase; font-size: .75rem; letter-spacing: .5px;
}
.wh-table td {
    padding: 14px 16px; border-bottom: 1px solid var(--wh-border);
    vertical-align: middle;
}
.wh-table tr:last-child td { border-bottom: none; }
.wh-table tr:hover td { background: rgba(108,92,231,.04); }

/* Status pills */
.wh-pill {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 12px; border-radius: 20px; font-size: .78rem; font-weight: 600;
}
.wh-pill--active { background: rgba(0,184,148,.15); color: #00b894; }
.wh-pill--paused { background: rgba(225,112,85,.15); color: #e17055; }
.wh-pill--disabled { background: rgba(214,48,49,.15); color: #d63031; }
.wh-pill--success { background: rgba(0,184,148,.15); color: #00b894; }
.wh-pill--failed { background: rgba(214,48,49,.15); color: #d63031; }

/* Webhook actions */
.wh-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.wh-icon-btn {
    width: 34px; height: 34px; border: 1px solid var(--wh-border); border-radius: 8px;
    background: transparent; color: var(--wh-text-muted); cursor: pointer;
    display: inline-flex; align-items: center; justify-content: center;
    transition: var(--wh-transition); font-size: .85rem;
}
.wh-icon-btn:hover { color: var(--wh-accent-light); border-color: var(--wh-accent); background: rgba(108,92,231,.1); }
.wh-icon-btn--danger:hover { color: var(--wh-red); border-color: var(--wh-red); background: rgba(214,48,49,.1); }

/* Delivery log */
.wh-delivery-row { cursor: pointer; }
.wh-delivery-detail {
    display: none; background: var(--wh-surface-2); border-radius: var(--wh-radius-sm);
    padding: 16px; margin: 8px 0 16px; font-size: .85rem;
}
.wh-delivery-detail.active { display: block; }
.wh-delivery-detail pre {
    background: #0d0d19; border-radius: 8px; padding: 12px; overflow-x: auto;
    font-size: .8rem; color: var(--wh-text-muted); margin: 8px 0 0; max-height: 200px;
}

/* Modal */
.wh-modal-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,.7); backdrop-filter: blur(6px);
    z-index: 9999; display: none; align-items: center; justify-content: center; padding: 24px;
}
.wh-modal-overlay.active { display: flex; }
.wh-modal {
    background: var(--wh-surface); border: 1px solid var(--wh-border);
    border-radius: var(--wh-radius); max-width: 640px; width: 100%;
    max-height: 85vh; overflow-y: auto; padding: 32px; position: relative;
}
.wh-modal-close {
    position: absolute; top: 16px; right: 16px; background: none; border: none;
    color: var(--wh-text-muted); cursor: pointer; font-size: 1.2rem;
}
.wh-modal-close:hover { color: var(--wh-text); }
.wh-modal h3 {
    font-family: 'Space Grotesk', sans-serif; font-size: 1.5rem; font-weight: 700;
    margin: 0 0 24px; color: var(--wh-text);
}

/* Form */
.wh-form-group { margin-bottom: 20px; }
.wh-form-group label {
    display: block; font-weight: 600; font-size: .85rem; color: var(--wh-text-muted);
    margin-bottom: 8px; text-transform: uppercase; letter-spacing: .3px;
}
.wh-input {
    width: 100%; padding: 12px 16px; border: 1px solid var(--wh-border);
    border-radius: var(--wh-radius-sm); background: var(--wh-surface-2);
    color: var(--wh-text); font-size: .95rem; font-family: inherit;
    transition: border-color var(--wh-transition);
}
.wh-input:focus { outline: none; border-color: var(--wh-accent); }
.wh-input::placeholder { color: var(--wh-text-muted); }

/* Event checkboxes */
.wh-events-grid { display: grid; gap: 16px; }
.wh-event-category { margin-bottom: 4px; }
.wh-event-cat-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 8px 12px; border-radius: 8px; background: var(--wh-surface-2);
    cursor: pointer; margin-bottom: 8px;
}
.wh-event-cat-header h4 {
    margin: 0; font-size: .9rem; font-weight: 600; display: flex; align-items: center; gap: 8px;
}
.wh-event-cat-header .wh-select-all {
    font-size: .75rem; color: var(--wh-accent-light); cursor: pointer;
    background: none; border: none; font-weight: 600;
}
.wh-event-cat-header .wh-select-all:hover { text-decoration: underline; }
.wh-event-list { padding: 0 8px; }
.wh-event-item {
    display: flex; align-items: center; gap: 10px; padding: 6px 4px;
}
.wh-event-item input[type="checkbox"] {
    accent-color: var(--wh-accent); width: 16px; height: 16px; cursor: pointer;
}
.wh-event-item label { cursor: pointer; font-size: .88rem; color: var(--wh-text); }

/* Secret display */
.wh-secret-box {
    background: var(--wh-surface-2); border: 1px solid rgba(0,184,148,.3);
    border-radius: var(--wh-radius-sm); padding: 16px; margin: 20px 0;
    display: none;
}
.wh-secret-box.active { display: block; }
.wh-secret-box p { font-size: .85rem; color: var(--wh-orange); margin: 0 0 8px; font-weight: 600; }
.wh-secret-value {
    display: flex; align-items: center; gap: 8px; background: #0d0d19;
    padding: 10px 14px; border-radius: 8px; font-family: 'Fira Code', monospace;
    font-size: .85rem; color: var(--wh-green); word-break: break-all;
}
.wh-copy-btn {
    flex-shrink: 0; background: none; border: 1px solid var(--wh-border);
    color: var(--wh-text-muted); padding: 4px 10px; border-radius: 6px;
    cursor: pointer; font-size: .8rem; transition: var(--wh-transition);
}
.wh-copy-btn:hover { color: var(--wh-accent-light); border-color: var(--wh-accent); }

/* Accordion */
.wh-accordion { display: grid; gap: 12px; }
.wh-acc-item {
    background: var(--wh-surface); border: 1px solid var(--wh-border);
    border-radius: var(--wh-radius-sm); overflow: hidden;
}
.wh-acc-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 18px 24px; cursor: pointer; font-weight: 600; font-size: 1rem;
    transition: background var(--wh-transition);
}
.wh-acc-header:hover { background: rgba(108,92,231,.05); }
.wh-acc-header i.fa-chevron-down {
    transition: transform var(--wh-transition); font-size: .8rem; color: var(--wh-text-muted);
}
.wh-acc-item.open .wh-acc-header i.fa-chevron-down { transform: rotate(180deg); }
.wh-acc-body {
    max-height: 0; overflow: hidden; transition: max-height .4s ease;
}
.wh-acc-item.open .wh-acc-body { max-height: 2000px; }
.wh-acc-content { padding: 0 24px 24px; }
.wh-acc-content p { color: var(--wh-text-muted); font-size: .9rem; margin: 0 0 12px; }
.wh-acc-content pre {
    background: #0d0d19; border-radius: 8px; padding: 16px; overflow-x: auto;
    font-size: .8rem; color: var(--wh-accent-light); margin: 8px 0;
}
.wh-acc-content code { font-family: 'Fira Code', monospace; }

/* Empty state */
.wh-empty {
    text-align: center; padding: 60px 20px; color: var(--wh-text-muted);
}
.wh-empty i { font-size: 3rem; margin-bottom: 16px; display: block; color: var(--wh-accent); opacity: .5; }
.wh-empty h3 { font-size: 1.3rem; color: var(--wh-text); margin: 0 0 8px; }
.wh-empty p { font-size: .95rem; margin: 0 0 24px; }

/* Responsive */
@media (max-width: 768px) {
    .wh-hero { padding: 100px 0 60px; }
    .wh-section { padding: 60px 0; }
    .wh-table th, .wh-table td { padding: 10px 12px; font-size: .82rem; }
    .wh-modal { padding: 24px; margin: 16px; }
}

/* Delivery mini-table inside modal */
.wh-delivery-mini th { font-size: .7rem; }
.wh-delivery-mini td { font-size: .82rem; padding: 8px 10px; }

/* Toast */
.wh-toast {
    position: fixed; bottom: 24px; right: 24px; z-index: 10001;
    background: var(--wh-surface); border: 1px solid var(--wh-border);
    border-radius: var(--wh-radius-sm); padding: 14px 24px;
    color: var(--wh-text); font-size: .9rem; box-shadow: var(--wh-shadow);
    transform: translateY(100px); opacity: 0; transition: all .3s ease;
    display: flex; align-items: center; gap: 10px;
}
.wh-toast.active { transform: translateY(0); opacity: 1; }
.wh-toast.success { border-color: rgba(0,184,148,.4); }
.wh-toast.error { border-color: rgba(214,48,49,.4); }

/* Loading spinner */
.wh-spinner {
    width: 20px; height: 20px; border: 2px solid var(--wh-border);
    border-top-color: var(--wh-accent); border-radius: 50%;
    animation: whSpin .6s linear infinite; display: inline-block;
}
@keyframes whSpin { to { transform: rotate(360deg); } }

/* Tab bar for delivery/code */
.wh-tabs { display: flex; border-bottom: 1px solid var(--wh-border); margin-bottom: 20px; gap: 4px; }
.wh-tab {
    padding: 10px 20px; cursor: pointer; font-size: .85rem; font-weight: 600;
    color: var(--wh-text-muted); border: none; background: none;
    border-bottom: 2px solid transparent; transition: var(--wh-transition);
}
.wh-tab:hover { color: var(--wh-text); }
.wh-tab.active { color: var(--wh-accent-light); border-bottom-color: var(--wh-accent); }
.wh-tab-panel { display: none; }
.wh-tab-panel.active { display: block; }
</style>

<div class="wh-page">

    <!-- ===== HERO ===== -->
    <section class="wh-hero" data-aos="fade-up">
        <div class="wh-container">
            <span class="wh-badge"><i class="fas fa-broadcast-tower"></i> Real-Time Events</span>
            <h1 class="wh-hero-title">Webhook Events</h1>
            <p class="wh-hero-sub">Get real-time notifications when things happen in Alfred. Subscribe to events, and we'll POST JSON payloads to your endpoints instantly.</p>
            <?php if (!$is_logged_in): ?>
                <div style="margin-top:32px;display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
                    <a href="/alfred.php" class="wh-btn"><i class="fas fa-rocket"></i> Get Started Free</a>
                    <a href="#event-reference" class="wh-btn wh-btn--ghost"><i class="fas fa-book"></i> Event Reference</a>
                </div>
            <?php else: ?>
                <div style="margin-top:32px;display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
                    <button class="wh-btn" onclick="openCreateModal()"><i class="fas fa-plus"></i> Create Webhook</button>
                    <a href="#event-reference" class="wh-btn wh-btn--ghost"><i class="fas fa-book"></i> Event Reference</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($is_logged_in): ?>
    <!-- ===== WEBHOOK MANAGEMENT ===== -->
    <section class="wh-section" data-aos="fade-up">
        <div class="wh-container">
            <div class="wh-section-header">
                <span class="wh-badge"><i class="fas fa-cog"></i> Manage</span>
                <h2 class="wh-section-title">Your Webhooks</h2>
                <p class="wh-section-sub">Create, configure, and monitor your webhook endpoints.</p>
            </div>

            <div class="wh-card" id="webhooksCard">
                <!-- Loading state -->
                <div id="webhooksLoading" style="text-align:center;padding:40px;">
                    <div class="wh-spinner"></div>
                    <p style="color:var(--wh-text-muted);margin-top:12px;">Loading webhooks...</p>
                </div>

                <!-- Empty state (hidden by default) -->
                <div id="webhooksEmpty" style="display:none;">
                    <div class="wh-empty">
                        <i class="fas fa-satellite-dish"></i>
                        <h3>No webhooks yet</h3>
                        <p>Create your first webhook endpoint to start receiving real-time events.</p>
                        <button class="wh-btn" onclick="openCreateModal()"><i class="fas fa-plus"></i> Create Webhook</button>
                    </div>
                </div>

                <!-- Webhook table (hidden by default) -->
                <div id="webhooksTableWrap" style="display:none;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
                        <h3 style="margin:0;font-size:1.1rem;color:var(--wh-text);">
                            <i class="fas fa-link" style="color:var(--wh-accent);margin-right:6px;"></i>
                            Registered Endpoints <span id="webhookCount" style="color:var(--wh-text-muted);font-weight:400;">(0)</span>
                        </h3>
                        <button class="wh-btn wh-btn--sm" onclick="openCreateModal()"><i class="fas fa-plus"></i> Add Webhook</button>
                    </div>
                    <div class="wh-table-wrap">
                        <table class="wh-table">
                            <thead>
                                <tr>
                                    <th>Name / URL</th>
                                    <th>Events</th>
                                    <th>Status</th>
                                    <th>Last Triggered</th>
                                    <th>Failures</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="webhooksTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ===== EVENT REFERENCE (always visible) ===== -->
    <section class="wh-section wh-section--alt" id="event-reference" data-aos="fade-up">
        <div class="wh-container">
            <div class="wh-section-header">
                <span class="wh-badge"><i class="fas fa-book-open"></i> Reference</span>
                <h2 class="wh-section-title">Event Types</h2>
                <p class="wh-section-sub">Complete list of webhook events with descriptions and example payloads.</p>
            </div>

            <div class="wh-accordion">
                <?php foreach ($eventCategories as $category => $events): ?>
                <div class="wh-acc-item" data-aos="fade-up" data-aos-delay="<?= array_search($category, array_keys($eventCategories)) * 50 ?>">
                    <div class="wh-acc-header" onclick="toggleAccordion(this)">
                        <span><i class="fas <?= $catIcons[$category] ?? 'fa-circle' ?>" style="color:var(--wh-accent);margin-right:10px;"></i><?= $category ?> Events <span style="color:var(--wh-text-muted);font-weight:400;">(<?= count($events) ?>)</span></span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="wh-acc-body">
                        <div class="wh-acc-content">
                            <?php foreach ($events as $eventKey => $eventInfo): ?>
                            <div style="margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid var(--wh-border);">
                                <h4 style="margin:0 0 6px;font-size:.95rem;">
                                    <code style="color:var(--wh-accent-light);font-family:'Fira Code',monospace;font-size:.85rem;"><?= htmlspecialchars($eventKey) ?></code>
                                </h4>
                                <p><?= htmlspecialchars($eventInfo['desc']) ?></p>
                                <details style="margin-top:8px;">
                                    <summary style="cursor:pointer;font-size:.82rem;color:var(--wh-accent-light);font-weight:600;">Example Payload</summary>
                                    <pre><code><?= htmlspecialchars(json_encode(generateExamplePayload($eventKey), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></code></pre>
                                </details>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ===== SIGNATURE VERIFICATION ===== -->
    <section class="wh-section" id="verification" data-aos="fade-up">
        <div class="wh-container">
            <div class="wh-section-header">
                <span class="wh-badge"><i class="fas fa-shield-alt"></i> Security</span>
                <h2 class="wh-section-title">Signature Verification</h2>
                <p class="wh-section-sub">Every webhook includes an <code style="color:var(--wh-accent-light)">X-Alfred-Signature</code> header. Verify it to ensure authenticity.</p>
            </div>

            <div class="wh-card">
                <div class="wh-tabs">
                    <button class="wh-tab active" onclick="switchTab(this, 'tab-node')">Node.js</button>
                    <button class="wh-tab" onclick="switchTab(this, 'tab-python')">Python</button>
                    <button class="wh-tab" onclick="switchTab(this, 'tab-php')">PHP</button>
                </div>

                <div class="wh-tab-panel active" id="tab-node">
<pre><code>const crypto = require('crypto');

function verifyWebhookSignature(payload, signature, secret) {
    const expected = 'sha256=' + crypto
        .createHmac('sha256', secret)
        .update(payload)
        .digest('hex');
    return crypto.timingSafeEqual(
        Buffer.from(signature),
        Buffer.from(expected)
    );
}

// Express middleware
app.post('/webhook', express.raw({ type: 'application/json' }), (req, res) => {
    const sig = req.headers['x-alfred-signature'];
    if (!verifyWebhookSignature(req.body, sig, process.env.WEBHOOK_SECRET)) {
        return res.status(401).send('Invalid signature');
    }
    const event = JSON.parse(req.body);
    console.log('Event:', event.event, event.data);
    res.status(200).send('OK');
});</code></pre>
                </div>

                <div class="wh-tab-panel" id="tab-python">
<pre><code>import hmac, hashlib

def verify_signature(payload: bytes, signature: str, secret: str) -> bool:
    expected = 'sha256=' + hmac.new(
        secret.encode(), payload, hashlib.sha256
    ).hexdigest()
    return hmac.compare_digest(expected, signature)

# Flask example
from flask import Flask, request, abort
app = Flask(__name__)

@app.route('/webhook', methods=['POST'])
def webhook():
    sig = request.headers.get('X-Alfred-Signature', '')
    if not verify_signature(request.data, sig, WEBHOOK_SECRET):
        abort(401)
    event = request.json
    print(f"Event: {event['event']}", event['data'])
    return 'OK', 200</code></pre>
                </div>

                <div class="wh-tab-panel" id="tab-php">
<pre><code>&lt;?php
function verifySignature(string $payload, string $signature, string $secret): bool {
    $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
    return hash_equals($expected, $signature);
}

$payload   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_ALFRED_SIGNATURE'] ?? '';
$secret    = getenv('WEBHOOK_SECRET');

if (!verifySignature($payload, $signature, $secret)) {
    http_response_code(401);
    die('Invalid signature');
}

$event = json_decode($payload, true);
error_log("Event: " . $event['event']);
http_response_code(200);
echo 'OK';</code></pre>
                </div>
            </div>
        </div>
    </section>

    <?php if (!$is_logged_in): ?>
    <!-- ===== CTA ===== -->
    <section class="wh-section wh-section--alt" data-aos="fade-up">
        <div class="wh-container" style="text-align:center;">
            <h2 class="wh-section-title">Start Building with Webhooks</h2>
            <p class="wh-section-sub">Create a free Alfred account to register webhook endpoints and start receiving real-time event notifications.</p>
            <a href="/alfred.php" class="wh-btn"><i class="fas fa-rocket"></i> Get Started Free</a>
        </div>
    </section>
    <?php endif; ?>
</div>

<!-- ===== CREATE / EDIT MODAL ===== -->
<div class="wh-modal-overlay" id="webhookModal">
    <div class="wh-modal">
        <button class="wh-modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
        <h3 id="modalTitle">Create Webhook</h3>

        <form id="webhookForm" onsubmit="return handleFormSubmit(event)">
            <input type="hidden" id="formWebhookId" value="">

            <div class="wh-form-group">
                <label for="formName">Name (optional)</label>
                <input class="wh-input" type="text" id="formName" placeholder="e.g., Production Server" maxlength="100">
            </div>

            <div class="wh-form-group">
                <label for="formUrl">Endpoint URL <span style="color:var(--wh-red);">*</span></label>
                <input class="wh-input" type="url" id="formUrl" placeholder="https://example.com/webhook" required>
                <small style="color:var(--wh-text-muted);font-size:.78rem;margin-top:4px;display:block;">Must be HTTPS</small>
            </div>

            <div class="wh-form-group">
                <label>Events <span style="color:var(--wh-red);">*</span></label>
                <div class="wh-events-grid" id="eventsGrid">
                    <?php foreach ($eventCategories as $category => $events): ?>
                    <div class="wh-event-category">
                        <div class="wh-event-cat-header">
                            <h4><i class="fas <?= $catIcons[$category] ?? 'fa-circle' ?>" style="color:var(--wh-accent);"></i> <?= $category ?></h4>
                            <button type="button" class="wh-select-all" onclick="toggleCategoryEvents(this, '<?= strtolower($category) ?>')">Select All</button>
                        </div>
                        <div class="wh-event-list">
                            <?php foreach ($events as $eventKey => $eventInfo): ?>
                            <div class="wh-event-item">
                                <input type="checkbox" name="events[]" value="<?= htmlspecialchars($eventKey) ?>" id="evt_<?= str_replace('.', '_', $eventKey) ?>" data-category="<?= strtolower($category) ?>">
                                <label for="evt_<?= str_replace('.', '_', $eventKey) ?>"><?= htmlspecialchars($eventInfo['label']) ?> <span style="color:var(--wh-text-muted);font-size:.78rem;">(<?= htmlspecialchars($eventKey) ?>)</span></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="display:flex;gap:12px;margin-top:24px;">
                <button type="submit" class="wh-btn" id="formSubmitBtn"><i class="fas fa-check"></i> <span>Create Webhook</span></button>
                <button type="button" class="wh-btn wh-btn--ghost" onclick="closeModal()">Cancel</button>
            </div>
        </form>

        <!-- Secret display (shown after create) -->
        <div class="wh-secret-box" id="secretBox">
            <p><i class="fas fa-exclamation-triangle"></i> Save this secret — it will NOT be shown again!</p>
            <div class="wh-secret-value">
                <span id="secretValue"></span>
                <button class="wh-copy-btn" onclick="copySecret()"><i class="fas fa-copy"></i> Copy</button>
            </div>
        </div>
    </div>
</div>

<!-- ===== DELIVERIES MODAL ===== -->
<div class="wh-modal-overlay" id="deliveriesModal">
    <div class="wh-modal" style="max-width:800px;">
        <button class="wh-modal-close" onclick="closeDeliveriesModal()"><i class="fas fa-times"></i></button>
        <h3><i class="fas fa-history" style="color:var(--wh-accent);margin-right:8px;"></i> Delivery Log — <span id="deliveryWebhookName"></span></h3>
        <div id="deliveriesContent">
            <div style="text-align:center;padding:30px;"><div class="wh-spinner"></div></div>
        </div>
    </div>
</div>

<!-- ===== TOAST ===== -->
<div class="wh-toast" id="whToast"></div>

<script>window._webhooksLoggedIn = <?= $is_logged_in ? "true" : "false" ?>;</script>
<script src="/assets/js/webhooks-engine.js"></script>

<?php
/**
 * Generate an example payload for a given event type
 */
function generateExamplePayload(string $eventType): array {
    $base = [
        'id'        => 'evt_' . substr(md5($eventType), 0, 24),
        'event'     => $eventType,
        'timestamp' => '2026-03-04T12:00:00+00:00',
    ];

    $examples = [
        'agent.created' => [
            'agent_id'   => 'agt_abc123',
            'name'       => 'Customer Support Bot',
            'model'      => 'gpt-4o',
            'created_by' => 42,
        ],
        'agent.deployed' => [
            'agent_id'    => 'agt_abc123',
            'name'        => 'Customer Support Bot',
            'environment' => 'production',
            'version'     => 3,
        ],
        'agent.error' => [
            'agent_id'    => 'agt_abc123',
            'error_type'  => 'rate_limit',
            'message'     => 'API rate limit exceeded',
            'severity'    => 'warning',
        ],
        'agent.status_changed' => [
            'agent_id'    => 'agt_abc123',
            'old_status'  => 'online',
            'new_status'  => 'maintenance',
        ],
        'call.started' => [
            'call_id'    => 'call_xyz789',
            'agent_id'   => 'agt_abc123',
            'from'       => '+15551234567',
            'to'         => '+15559876543',
            'direction'  => 'inbound',
        ],
        'call.ended' => [
            'call_id'     => 'call_xyz789',
            'agent_id'    => 'agt_abc123',
            'duration_s'  => 142,
            'end_reason'  => 'completed',
            'summary'     => 'Customer inquired about billing. Issue resolved.',
        ],
        'call.transferred' => [
            'call_id'     => 'call_xyz789',
            'from_agent'  => 'agt_abc123',
            'to_number'   => '+15552223344',
            'reason'      => 'escalation',
        ],
        'call.recorded' => [
            'call_id'       => 'call_xyz789',
            'recording_url' => 'https://storage.gositeme.com/recordings/call_xyz789.mp3',
            'duration_s'    => 142,
        ],
        'fleet.deployed' => [
            'fleet_id'    => 'flt_001',
            'name'        => 'Support Fleet',
            'agent_count' => 5,
            'version'     => 2,
        ],
        'fleet.alert' => [
            'fleet_id'   => 'flt_001',
            'alert_type' => 'high_error_rate',
            'message'    => 'Error rate exceeded 5% threshold',
            'severity'   => 'critical',
        ],
        'fleet.agent_joined' => [
            'fleet_id' => 'flt_001',
            'agent_id' => 'agt_abc123',
        ],
        'fleet.agent_left' => [
            'fleet_id' => 'flt_001',
            'agent_id' => 'agt_abc123',
            'reason'   => 'removed',
        ],
        'tool.executed' => [
            'tool_id'    => 'weather_lookup',
            'agent_id'   => 'agt_abc123',
            'duration_ms' => 320,
            'success'     => true,
        ],
        'tool.error' => [
            'tool_id'    => 'weather_lookup',
            'agent_id'   => 'agt_abc123',
            'error'      => 'Upstream API timeout',
        ],
        'tool.rate_limited' => [
            'tool_id'    => 'weather_lookup',
            'agent_id'   => 'agt_abc123',
            'limit'      => 100,
            'window'     => '1m',
        ],
        'marketplace.published' => [
            'extension_id' => 'ext_555',
            'name'         => 'My Custom Tool',
            'version'      => '1.0.0',
        ],
        'marketplace.purchased' => [
            'extension_id' => 'ext_555',
            'buyer_id'     => 99,
            'price'        => 9.99,
            'currency'     => 'USD',
        ],
        'marketplace.review' => [
            'extension_id' => 'ext_555',
            'rating'       => 5,
            'review'       => 'Great tool!',
        ],
        'billing.payment_succeeded' => [
            'invoice_id' => 'inv_12345',
            'amount'     => 49.00,
            'currency'   => 'USD',
        ],
        'billing.payment_failed' => [
            'invoice_id' => 'inv_12345',
            'amount'     => 49.00,
            'reason'     => 'card_declined',
        ],
        'billing.usage_alert' => [
            'resource'   => 'api_calls',
            'current'    => 9500,
            'limit'      => 10000,
            'percentage' => 95,
        ],
    ];

    $base['data'] = $examples[$eventType] ?? ['message' => 'Event payload'];
    return $base;
}
?>

<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>
