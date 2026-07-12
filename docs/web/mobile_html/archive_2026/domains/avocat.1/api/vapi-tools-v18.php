<?php
$GLOBALS['CSRF_EXEMPT'] = true; // VAPI server-secret verification
require_once dirname(__DIR__) . '/includes/api-security.php';
/**
 * Alfred Expanded Voice Tools — v18.0 "Deep Coverage"
 * 159 NEW tools covering every remaining API action in the ecosystem
 * 
 * Categories:
 *  - Accounting (8): Full bookkeeping, invoices, expenses, tax
 *  - Documents (4): Parse, OCR, summarize, extract via AI
 *  - Composio (4): Third-party app integrations 
 *  - Team Collaboration (6): Share agents/conversations, invite members
 *  - Usage Tracking (3): Summaries, limits, alerts
 *  - RSS/News Feeds (4): Add, list, poll, process
 *  - Email Advanced (7): Forwarders, autoresponders, catchall, quota, password
 *  - SSL Management (3): Request certs, force HTTPS, info
 *  - Domain Advanced (6): Lock, EPP codes, auto-renew, nameservers, DNS records
 *  - Backups (3): Create, restore, list
 *  - File Manager (6): Read, save, mkdir, rename, chmod, delete
 *  - Cron Jobs Granular (3): Create, delete, list
 *  - Database Granular (2): Create, delete
 *  - FTP Granular (3): Create, delete, password
 *  - Addon Domains (2): Create, delete
 *  - Subdomains (2): Create, delete
 *  - Domain Pointers (2): Create, delete
 *  - Redirects (2): Create, delete
 *  - App Management (2): Update, uninstall
 *  - Ticket Advanced (4): Close, departments, view, reply
 *  - Autopilot Evolution (4): Auto-fix, narrative, security events, confidence
 *  - Website Builder (4): Start, continue, status, list
 *  - Website Editor (6): Read, save, create, AI-edit, templates, install
 *  - Staging (4): Sync, push, delete, credentials
 *  - Agent Deploy (5): Pause, resume, delete, catalog, detail
 *  - Collab Extended (3): End, invite, list
 *  - Crypto Trading (17): Trade, portfolio, GSM, prices, payments, VR land, wagers
 *  - Reseller Extended (3): Pricing, invite, toggle
 *  - Support Chat (2): Send, history
 *  - Gamification (1): Daily challenge
 *  - Provisioning (5): Suspend, unsuspend, terminate, upgrade, test
 *  - Server Stats (3): Error log, access log, usage
 *  - SSO (1): Generate token
 *  - Comms/Messaging (7): Groups, messages, upload
 *  - VR World (6): Chess matches, challenges, plots, build, avatars
 *  - Webhooks (4): Delete, update, test, logs
 *  - Enterprise (3): Create, add member, dashboard
 *  - Commissions/Payouts (4): Calculate, approve, create, process
 *  - Site Doctor (3): Scan, results, report
 *  - Agent Registry (7): List, get, hierarchy, delegate, messages, heartbeat, stats
 *  - Fleet Extended (3): Batch, available, history
 *  - Learning System (3): Insights, patterns, performance
 *  - Marketplace Extended (3): Publish, rate, uninstall
 *  - Uptime Extended (3): Check, history, incidents
 */

if (!defined('GOSITEME_API')) {
    http_response_code(403);
    exit;
}

// ═══════════════════════════════════════════════════════════════
// ACCOUNTING (8 tools) — api/accounting.php
// ═══════════════════════════════════════════════════════════════

function toolAccountingDashboard($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredMainAPI('/api/accounting.php?action=dashboard&client_id=' . $cid);
}

function toolAccountingInvoices($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredMainAPI('/api/accounting.php?action=invoices&client_id=' . $cid);
}

function toolAccountingCreateInvoice($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredMainAPI('/api/accounting.php?action=create-invoice', 'POST', [
        'client_id' => $cid,
        'items' => $args['items'] ?? [],
        'due_date' => $args['due_date'] ?? null,
        'notes' => $args['notes'] ?? ''
    ]);
}

function toolAccountingMarkPaid($args) {
    $cid = (int)($args['client_id'] ?? 0);
    $invoiceId = (int)($args['invoice_id'] ?? 0);
    if (!$cid || !$invoiceId) return ['error' => 'client_id and invoice_id required'];
    return alfredMainAPI('/api/accounting.php?action=mark-paid', 'POST', [
        'client_id' => $cid, 'invoice_id' => $invoiceId
    ]);
}

function toolAccountingExpenses($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredMainAPI('/api/accounting.php?action=expenses&client_id=' . $cid);
}

function toolAccountingAddExpense($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredMainAPI('/api/accounting.php?action=add-expense', 'POST', [
        'client_id' => $cid,
        'amount' => (float)($args['amount'] ?? 0),
        'category' => $args['category'] ?? 'general',
        'description' => $args['description'] ?? '',
        'date' => $args['date'] ?? date('Y-m-d')
    ]);
}

function toolAccountingReports($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    $period = $args['period'] ?? '30d';
    return alfredMainAPI('/api/accounting.php?action=reports&client_id=' . $cid . '&period=' . urlencode($period));
}

function toolAccountingTaxSummary($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    $year = $args['year'] ?? date('Y');
    return alfredMainAPI('/api/accounting.php?action=tax-summary&client_id=' . $cid . '&year=' . urlencode($year));
}

// ═══════════════════════════════════════════════════════════════
// DOCUMENTS (4 tools) — api/documents.php
// ═══════════════════════════════════════════════════════════════

function toolDocumentParse($args) {
    return alfredMainAPI('/api/documents.php?action=parse', 'POST', [
        'url' => $args['url'] ?? '', 'content' => $args['content'] ?? ''
    ]);
}

function toolDocumentOCR($args) {
    return alfredMainAPI('/api/documents.php?action=ocr', 'POST', [
        'image_url' => $args['image_url'] ?? ''
    ]);
}

function toolDocumentSummarize($args) {
    return alfredMainAPI('/api/documents.php?action=summarize', 'POST', [
        'text' => $args['text'] ?? '', 'length' => $args['length'] ?? 'medium'
    ]);
}

function toolDocumentExtract($args) {
    return alfredMainAPI('/api/documents.php?action=extract', 'POST', [
        'text' => $args['text'] ?? '', 'fields' => $args['fields'] ?? []
    ]);
}

// ═══════════════════════════════════════════════════════════════
// COMPOSIO (4 tools) — api/composio.php
// ═══════════════════════════════════════════════════════════════

function toolComposioApps($args) {
    return alfredMainAPI('/api/composio.php?action=apps');
}

function toolComposioConnect($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredMainAPI('/api/composio.php?action=connect', 'POST', [
        'client_id' => $cid, 'app' => $args['app'] ?? ''
    ]);
}

function toolComposioExecute($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredMainAPI('/api/composio.php?action=execute', 'POST', [
        'client_id' => $cid, 'tool' => $args['tool'] ?? '', 'params' => $args['params'] ?? []
    ]);
}

function toolComposioDisconnect($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredMainAPI('/api/composio.php?action=disconnect', 'POST', [
        'client_id' => $cid, 'app' => $args['app'] ?? ''
    ]);
}

// ═══════════════════════════════════════════════════════════════
// TEAM (6 tools) — api/team.php
// ═══════════════════════════════════════════════════════════════

function toolTeamOverview($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredMainAPI('/api/team.php?action=overview&client_id=' . $cid);
}

function toolTeamShareAgent($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredMainAPI('/api/team.php?action=share-agent', 'POST', [
        'client_id' => $cid, 'agent_id' => $args['agent_id'] ?? ''
    ]);
}

function toolTeamShareConversation($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredMainAPI('/api/team.php?action=share-conversation', 'POST', [
        'client_id' => $cid, 'conversation_id' => $args['conversation_id'] ?? ''
    ]);
}

function toolTeamInvite($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredMainAPI('/api/team.php?action=invite-code&client_id=' . $cid);
}

function toolTeamJoin($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredMainAPI('/api/team.php?action=join', 'POST', [
        'client_id' => $cid, 'invite_code' => $args['invite_code'] ?? ''
    ]);
}

function toolTeamMembers($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredMainAPI('/api/team.php?action=members-detail&client_id=' . $cid);
}

// ═══════════════════════════════════════════════════════════════
// USAGE TRACKING (3 tools) — api/usage.php
// ═══════════════════════════════════════════════════════════════

function toolUsageSummary($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredMainAPI('/api/usage.php?action=summary&client_id=' . $cid);
}

function toolUsageLimits($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredMainAPI('/api/usage.php?action=limits&client_id=' . $cid);
}

function toolUsageAlerts($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredMainAPI('/api/usage.php?action=alerts&client_id=' . $cid);
}

// ═══════════════════════════════════════════════════════════════
// RSS/NEWS FEEDS (4 tools) — api/feeds.php
// ═══════════════════════════════════════════════════════════════

function toolFeedAdd($args) {
    return alfredMainAPI('/api/feeds.php?action=add-feed', 'POST', [
        'url' => $args['url'] ?? '', 'name' => $args['name'] ?? '', 'category' => $args['category'] ?? 'general'
    ]);
}

function toolFeedList($args) {
    return alfredMainAPI('/api/feeds.php?action=feeds');
}

function toolFeedPoll($args) {
    $feedId = $args['feed_id'] ?? '';
    return alfredMainAPI('/api/feeds.php?action=poll' . ($feedId ? '&feed_id=' . urlencode($feedId) : ''));
}

function toolFeedProcess($args) {
    return alfredMainAPI('/api/feeds.php?action=process', 'POST', [
        'feed_id' => $args['feed_id'] ?? ''
    ]);
}

// ═══════════════════════════════════════════════════════════════
// EMAIL ADVANCED (7 tools) — pay/api/email-api.php
// ═══════════════════════════════════════════════════════════════

function toolEmailForwarderCreate($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/email-api.php?action=forwarder-create', 'POST', [
        'client_id' => $cid, 'domain' => $args['domain'] ?? '',
        'source' => $args['source'] ?? '', 'destination' => $args['destination'] ?? ''
    ]);
}

function toolEmailForwarderDelete($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/email-api.php?action=forwarder-delete', 'POST', [
        'client_id' => $cid, 'domain' => $args['domain'] ?? '', 'forwarder' => $args['forwarder'] ?? ''
    ]);
}

function toolEmailAutoresponderCreate($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/email-api.php?action=autoresponder-create', 'POST', [
        'client_id' => $cid, 'domain' => $args['domain'] ?? '',
        'email' => $args['email'] ?? '', 'subject' => $args['subject'] ?? '',
        'body' => $args['body'] ?? '', 'start' => $args['start'] ?? '', 'end' => $args['end'] ?? ''
    ]);
}

function toolEmailAutoresponderDelete($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/email-api.php?action=autoresponder-delete', 'POST', [
        'client_id' => $cid, 'domain' => $args['domain'] ?? '', 'email' => $args['email'] ?? ''
    ]);
}

function toolEmailCatchallSet($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/email-api.php?action=catchall-set', 'POST', [
        'client_id' => $cid, 'domain' => $args['domain'] ?? '', 'email' => $args['catchall_email'] ?? ''
    ]);
}

function toolEmailChangeQuota($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/email-api.php?action=quota', 'POST', [
        'client_id' => $cid, 'domain' => $args['domain'] ?? '',
        'email' => $args['email'] ?? '', 'quota' => (int)($args['quota_mb'] ?? 500)
    ]);
}

function toolEmailChangePassword($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/email-api.php?action=password', 'POST', [
        'client_id' => $cid, 'domain' => $args['domain'] ?? '',
        'email' => $args['email'] ?? '', 'password' => $args['new_password'] ?? ''
    ]);
}

// ═══════════════════════════════════════════════════════════════
// SSL MANAGEMENT (3 tools) — pay/api/ssl-api.php
// ═══════════════════════════════════════════════════════════════

function toolSslInfo($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/ssl-api.php?action=info&client_id=' . $cid . '&domain=' . urlencode($args['domain'] ?? ''));
}

function toolSslRequestCert($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/ssl-api.php?action=letsencrypt', 'POST', [
        'client_id' => $cid, 'domain' => $args['domain'] ?? ''
    ]);
}

function toolSslForceHttps($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/ssl-api.php?action=force-https', 'POST', [
        'client_id' => $cid, 'domain' => $args['domain'] ?? '',
        'enable' => (bool)($args['enable'] ?? true)
    ]);
}

// ═══════════════════════════════════════════════════════════════
// DOMAIN ADVANCED (6 tools) — pay/api/domain-api.php
// ═══════════════════════════════════════════════════════════════

function toolDomainLock($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/domain-api.php?action=lock', 'POST', [
        'client_id' => $cid, 'domain_id' => (int)($args['domain_id'] ?? 0),
        'lock' => (bool)($args['lock'] ?? true)
    ]);
}

function toolDomainEpp($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/domain-api.php?action=epp&client_id=' . $cid . '&domain_id=' . (int)($args['domain_id'] ?? 0));
}

function toolDomainAutorenew($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/domain-api.php?action=autorenew', 'POST', [
        'client_id' => $cid, 'domain_id' => (int)($args['domain_id'] ?? 0),
        'auto_renew' => (bool)($args['auto_renew'] ?? true)
    ]);
}

function toolDomainNameservers($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/domain-api.php?action=nameservers', 'POST', [
        'client_id' => $cid, 'domain_id' => (int)($args['domain_id'] ?? 0),
        'nameservers' => $args['nameservers'] ?? ''
    ]);
}

function toolDomainDnsAdd($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/domain-api.php?action=dns-add', 'POST', [
        'client_id' => $cid, 'domain_id' => (int)($args['domain_id'] ?? 0),
        'type' => $args['type'] ?? 'A', 'name' => $args['name'] ?? '',
        'value' => $args['value'] ?? '', 'ttl' => (int)($args['ttl'] ?? 3600)
    ]);
}

function toolDomainDnsDelete($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/domain-api.php?action=dns-delete', 'POST', [
        'client_id' => $cid, 'domain_id' => (int)($args['domain_id'] ?? 0),
        'record_id' => $args['record_id'] ?? ''
    ]);
}

// ═══════════════════════════════════════════════════════════════
// BACKUPS (3 tools) — pay/api/backup-api.php
// ═══════════════════════════════════════════════════════════════

function toolBackupList($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/backup-api.php?action=list&client_id=' . $cid);
}

function toolBackupCreate($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/backup-api.php?action=create', 'POST', ['client_id' => $cid]);
}

function toolBackupRestore($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/backup-api.php?action=restore', 'POST', [
        'client_id' => $cid, 'backup' => $args['backup'] ?? '', 'type' => $args['type'] ?? 'full'
    ]);
}

// ═══════════════════════════════════════════════════════════════
// FILE MANAGER (6 tools) — pay/api/file-api.php
// ═══════════════════════════════════════════════════════════════

function toolFileReadContent($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/file-api.php?action=read&client_id=' . $cid . '&path=' . urlencode($args['path'] ?? '/'));
}

function toolFileSave($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/file-api.php?action=save', 'POST', [
        'client_id' => $cid, 'path' => $args['path'] ?? '', 'content' => $args['content'] ?? ''
    ]);
}

function toolFileMkdir($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/file-api.php?action=mkdir', 'POST', [
        'client_id' => $cid, 'path' => $args['path'] ?? ''
    ]);
}

function toolFileRename($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/file-api.php?action=rename', 'POST', [
        'client_id' => $cid, 'path' => $args['path'] ?? '', 'new_name' => $args['new_name'] ?? ''
    ]);
}

function toolFileChmod($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/file-api.php?action=chmod', 'POST', [
        'client_id' => $cid, 'path' => $args['path'] ?? '', 'permissions' => $args['permissions'] ?? '644'
    ]);
}

function toolFileDelete($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/file-api.php?action=delete', 'POST', [
        'client_id' => $cid, 'path' => $args['path'] ?? ''
    ]);
}

// ═══════════════════════════════════════════════════════════════
// CRON GRANULAR (3 tools) — pay/api/cron-api.php
// ═══════════════════════════════════════════════════════════════

function toolCronList($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/cron-api.php?action=list&client_id=' . $cid);
}

function toolCronCreate($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/cron-api.php?action=create', 'POST', [
        'client_id' => $cid, 'minute' => $args['minute'] ?? '*',
        'hour' => $args['hour'] ?? '*', 'day' => $args['day'] ?? '*',
        'month' => $args['month'] ?? '*', 'weekday' => $args['weekday'] ?? '*',
        'command' => $args['command'] ?? ''
    ]);
}

function toolCronDelete($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/cron-api.php?action=delete', 'POST', [
        'client_id' => $cid, 'id' => $args['cron_id'] ?? ''
    ]);
}

// ═══════════════════════════════════════════════════════════════
// DATABASE GRANULAR (2 tools) — pay/api/database-api.php
// ═══════════════════════════════════════════════════════════════

function toolDatabaseCreate($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/database-api.php?action=create', 'POST', [
        'client_id' => $cid, 'name' => $args['name'] ?? '', 'password' => $args['password'] ?? ''
    ]);
}

function toolDatabaseDelete($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/database-api.php?action=delete', 'POST', [
        'client_id' => $cid, 'name' => $args['name'] ?? ''
    ]);
}

// ═══════════════════════════════════════════════════════════════
// FTP GRANULAR (3 tools) — pay/api/ftp-api.php
// ═══════════════════════════════════════════════════════════════

function toolFtpCreate($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/ftp-api.php?action=create', 'POST', [
        'client_id' => $cid, 'username' => $args['username'] ?? '',
        'password' => $args['password'] ?? '', 'path' => $args['path'] ?? '/'
    ]);
}

function toolFtpDelete($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/ftp-api.php?action=delete', 'POST', [
        'client_id' => $cid, 'username' => $args['username'] ?? ''
    ]);
}

function toolFtpChangePassword($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/ftp-api.php?action=password', 'POST', [
        'client_id' => $cid, 'username' => $args['username'] ?? '', 'password' => $args['new_password'] ?? ''
    ]);
}

// ═══════════════════════════════════════════════════════════════
// ADDON DOMAINS (2 tools) — pay/api/addon-domains-api.php
// ═══════════════════════════════════════════════════════════════

function toolAddonDomainCreate($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/addon-domains-api.php?action=create', 'POST', [
        'client_id' => $cid, 'domain' => $args['domain'] ?? ''
    ]);
}

function toolAddonDomainDelete($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/addon-domains-api.php?action=delete', 'POST', [
        'client_id' => $cid, 'domain' => $args['domain'] ?? ''
    ]);
}

// ═══════════════════════════════════════════════════════════════
// SUBDOMAINS (2 tools) — pay/api/subdomain-api.php
// ═══════════════════════════════════════════════════════════════

function toolSubdomainCreate($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/subdomain-api.php?action=create', 'POST', [
        'client_id' => $cid, 'subdomain' => $args['subdomain'] ?? '', 'domain' => $args['domain'] ?? ''
    ]);
}

function toolSubdomainDelete($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/subdomain-api.php?action=delete', 'POST', [
        'client_id' => $cid, 'subdomain' => $args['subdomain'] ?? '', 'domain' => $args['domain'] ?? ''
    ]);
}

// ═══════════════════════════════════════════════════════════════
// DOMAIN POINTERS (2 tools) — pay/api/domain-pointers-api.php
// ═══════════════════════════════════════════════════════════════

function toolDomainPointerCreate($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/domain-pointers-api.php?action=create', 'POST', [
        'client_id' => $cid, 'from' => $args['from_domain'] ?? '', 'to' => $args['to_domain'] ?? ''
    ]);
}

function toolDomainPointerDelete($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/domain-pointers-api.php?action=delete', 'POST', [
        'client_id' => $cid, 'from' => $args['from_domain'] ?? ''
    ]);
}

// ═══════════════════════════════════════════════════════════════
// REDIRECTS (2 tools) — pay/api/redirects-api.php
// ═══════════════════════════════════════════════════════════════

function toolRedirectCreate($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/redirects-api.php?action=create', 'POST', [
        'client_id' => $cid, 'source' => $args['source'] ?? '',
        'destination' => $args['destination'] ?? '', 'type' => (int)($args['type'] ?? 301)
    ]);
}

function toolRedirectDelete($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/redirects-api.php?action=delete', 'POST', [
        'client_id' => $cid, 'source' => $args['source'] ?? ''
    ]);
}

// ═══════════════════════════════════════════════════════════════
// APP MANAGEMENT (2 tools) — pay/api/apps-api.php
// ═══════════════════════════════════════════════════════════════

function toolAppUpdate($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/apps-api.php?action=update', 'POST', [
        'client_id' => $cid, 'app' => $args['app'] ?? '', 'path' => $args['path'] ?? ''
    ]);
}

function toolAppUninstall($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/apps-api.php?action=uninstall', 'POST', [
        'client_id' => $cid, 'app' => $args['app'] ?? '', 'path' => $args['path'] ?? ''
    ]);
}

// ═══════════════════════════════════════════════════════════════
// TICKET ADVANCED (4 tools) — pay/api/ticket-api.php
// ═══════════════════════════════════════════════════════════════

function toolTicketView($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/ticket-api.php?action=view&client_id=' . $cid . '&id=' . (int)($args['ticket_id'] ?? 0));
}

function toolTicketReply($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/ticket-api.php?action=reply', 'POST', [
        'client_id' => $cid, 'ticket_id' => (int)($args['ticket_id'] ?? 0),
        'message' => $args['message'] ?? ''
    ]);
}

function toolTicketClose($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/ticket-api.php?action=close', 'POST', [
        'client_id' => $cid, 'ticket_id' => (int)($args['ticket_id'] ?? 0)
    ]);
}

function toolTicketDepartments($args) {
    return alfredInternalAPI('/pay/api/ticket-api.php?action=departments');
}

// ═══════════════════════════════════════════════════════════════
// AUTOPILOT EVOLUTION (4 tools) — pay/api/autopilot-evolution.php
// ═══════════════════════════════════════════════════════════════

function toolAutopilotAutoFix($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/autopilot-evolution.php?action=auto-fix', 'POST', ['client_id' => $cid]);
}

function toolAutopilotNarrative($args) {
    $cid = (int)($args['client_id'] ?? 0);
    return alfredInternalAPI('/pay/api/autopilot-evolution.php?action=generate-narrative&client_id=' . $cid);
}

function toolAutopilotSecurityEvents($args) {
    $cid = (int)($args['client_id'] ?? 0);
    return alfredInternalAPI('/pay/api/autopilot-evolution.php?action=security-events&client_id=' . $cid);
}

function toolAutopilotConfidenceExplain($args) {
    return alfredInternalAPI('/pay/api/autopilot-evolution.php?action=confidence-explain');
}

// ═══════════════════════════════════════════════════════════════
// WEBSITE BUILDER (4 tools) — pay/api/website-builder.php
// ═══════════════════════════════════════════════════════════════

function toolWebsiteBuilderStart($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/website-builder.php?action=start', 'POST', [
        'client_id' => $cid, 'domain' => $args['domain'] ?? '',
        'template' => $args['template'] ?? '', 'description' => $args['description'] ?? ''
    ]);
}

function toolWebsiteBuilderContinue($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/website-builder.php?action=continue', 'POST', [
        'client_id' => $cid, 'build_id' => $args['build_id'] ?? ''
    ]);
}

function toolWebsiteBuilderStatus($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/website-builder.php?action=status&client_id=' . $cid . '&build_id=' . urlencode($args['build_id'] ?? ''));
}

function toolWebsiteBuilderList($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/website-builder.php?action=list&client_id=' . $cid);
}

// ═══════════════════════════════════════════════════════════════
// WEBSITE EDITOR (6 tools) — pay/api/website-editor.php
// ═══════════════════════════════════════════════════════════════

function toolWebsiteEditorReadFile($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/website-editor.php?action=read-file&client_id=' . $cid . '&path=' . urlencode($args['path'] ?? ''));
}

function toolWebsiteEditorSaveFile($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/website-editor.php?action=save-file', 'POST', [
        'client_id' => $cid, 'path' => $args['path'] ?? '', 'content' => $args['content'] ?? ''
    ]);
}

function toolWebsiteEditorCreateFile($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/website-editor.php?action=create-file', 'POST', [
        'client_id' => $cid, 'path' => $args['path'] ?? '', 'content' => $args['content'] ?? ''
    ]);
}

function toolWebsiteEditorAIEdit($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/website-editor.php?action=ai-edit', 'POST', [
        'client_id' => $cid, 'path' => $args['path'] ?? '',
        'instruction' => $args['instruction'] ?? ''
    ]);
}

function toolWebsiteEditorTemplates($args) {
    return alfredInternalAPI('/pay/api/website-editor.php?action=templates');
}

function toolWebsiteEditorInstallTemplate($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/website-editor.php?action=install-template', 'POST', [
        'client_id' => $cid, 'template' => $args['template'] ?? '', 'domain' => $args['domain'] ?? ''
    ]);
}

// ═══════════════════════════════════════════════════════════════
// STAGING (4 tools) — pay/api/staging.php
// ═══════════════════════════════════════════════════════════════

function toolStagingSync($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/staging.php?action=sync', 'POST', [
        'client_id' => $cid, 'staging_id' => $args['staging_id'] ?? ''
    ]);
}

function toolStagingPush($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/staging.php?action=push', 'POST', [
        'client_id' => $cid, 'staging_id' => $args['staging_id'] ?? ''
    ]);
}

function toolStagingDelete($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/staging.php?action=delete', 'POST', [
        'client_id' => $cid, 'staging_id' => $args['staging_id'] ?? ''
    ]);
}

function toolStagingCredentials($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/staging.php?action=credentials&client_id=' . $cid . '&staging_id=' . urlencode($args['staging_id'] ?? ''));
}

// ═══════════════════════════════════════════════════════════════
// AGENT DEPLOY EXTENDED (5 tools) — pay/api/agent-deploy.php
// ═══════════════════════════════════════════════════════════════

function toolAgentDeployPause($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/agent-deploy.php?action=pause', 'POST', [
        'client_id' => $cid, 'agent_id' => $args['agent_id'] ?? ''
    ]);
}

function toolAgentDeployResume($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/agent-deploy.php?action=resume', 'POST', [
        'client_id' => $cid, 'agent_id' => $args['agent_id'] ?? ''
    ]);
}

function toolAgentDeployDelete($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/agent-deploy.php?action=delete', 'POST', [
        'client_id' => $cid, 'agent_id' => $args['agent_id'] ?? ''
    ]);
}

function toolAgentDeployCatalog($args) {
    return alfredInternalAPI('/pay/api/agent-deploy.php?action=catalog');
}

function toolAgentDeployDetail($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/agent-deploy.php?action=detail&client_id=' . $cid . '&agent_id=' . urlencode($args['agent_id'] ?? ''));
}

// ═══════════════════════════════════════════════════════════════
// COLLAB EXTENDED (3 tools) — pay/api/collab.php
// ═══════════════════════════════════════════════════════════════

function toolCollabEnd($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/collab.php?action=end', 'POST', [
        'client_id' => $cid, 'session_id' => $args['session_id'] ?? ''
    ]);
}

function toolCollabInvite($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/collab.php?action=invite', 'POST', [
        'client_id' => $cid, 'session_id' => $args['session_id'] ?? '', 'email' => $args['email'] ?? ''
    ]);
}

function toolCollabList($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/collab.php?action=list&client_id=' . $cid);
}

// ═══════════════════════════════════════════════════════════════
// CRYPTO TRADING (17 tools) — pay/api/crypto-api.php
// ═══════════════════════════════════════════════════════════════

function toolCryptoTradeQuote($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/crypto-api.php?action=trade.quote', 'POST', [
        'client_id' => $cid, 'from' => $args['from_token'] ?? 'SOL',
        'to' => $args['to_token'] ?? 'USDC', 'amount' => (float)($args['amount'] ?? 0)
    ]);
}

function toolCryptoTradePropose($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/crypto-api.php?action=trade.propose', 'POST', [
        'client_id' => $cid, 'from' => $args['from_token'] ?? '',
        'to' => $args['to_token'] ?? '', 'amount' => (float)($args['amount'] ?? 0)
    ]);
}

function toolCryptoTradeApprove($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/crypto-api.php?action=trade.approve', 'POST', [
        'client_id' => $cid, 'trade_id' => $args['trade_id'] ?? ''
    ]);
}

function toolCryptoTradeHistory($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/crypto-api.php?action=trade.history&client_id=' . $cid);
}

function toolCryptoPortfolioCreate($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/crypto-api.php?action=portfolio.create', 'POST', [
        'client_id' => $cid, 'name' => $args['name'] ?? 'Default', 'strategy' => $args['strategy'] ?? 'balanced'
    ]);
}

function toolCryptoPortfolioStatus($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/crypto-api.php?action=portfolio.status&client_id=' . $cid);
}

function toolCryptoGSMBalance($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/crypto-api.php?action=gsm.balance&client_id=' . $cid);
}

function toolCryptoGSMHistory($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/crypto-api.php?action=gsm.history&client_id=' . $cid);
}

function toolCryptoGSMLeaderboard($args) {
    return alfredInternalAPI('/pay/api/crypto-api.php?action=gsm.leaderboard');
}

function toolCryptoPrices($args) {
    $token = $args['token'] ?? 'sol';
    return alfredInternalAPI('/pay/api/crypto-api.php?action=prices.' . urlencode($token));
}

function toolCryptoGSMStake($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/crypto-api.php?action=gsm_stake', 'POST', [
        'client_id' => $cid, 'amount' => (float)($args['amount'] ?? 0)
    ]);
}

function toolCryptoPayCreate($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/crypto-api.php?action=pay.create', 'POST', [
        'client_id' => $cid, 'amount' => (float)($args['amount'] ?? 0),
        'currency' => $args['currency'] ?? 'SOL', 'invoice_id' => $args['invoice_id'] ?? ''
    ]);
}

function toolCryptoPayVerify($args) {
    return alfredInternalAPI('/pay/api/crypto-api.php?action=pay.verify', 'POST', [
        'tx_hash' => $args['tx_hash'] ?? '', 'payment_id' => $args['payment_id'] ?? ''
    ]);
}

function toolCryptoVRLand($args) {
    $cid = (int)($args['client_id'] ?? 0);
    $action = $args['land_action'] ?? 'list'; // list, buy, sell
    return alfredInternalAPI('/pay/api/crypto-api.php?action=vr.' . urlencode($action) . '-land&client_id=' . $cid);
}

function toolCryptoChessWager($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/crypto-api.php?action=chess.wager', 'POST', [
        'client_id' => $cid, 'amount' => (float)($args['amount'] ?? 0),
        'opponent' => $args['opponent'] ?? '', 'game_id' => $args['game_id'] ?? ''
    ]);
}

// ═══════════════════════════════════════════════════════════════
// RESELLER EXTENDED (3 tools) — pay/api/reseller.php
// ═══════════════════════════════════════════════════════════════

function toolResellerPricing($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/reseller.php?action=pricing&client_id=' . $cid);
}

function toolResellerInvite($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/reseller.php?action=invite', 'POST', [
        'client_id' => $cid, 'email' => $args['email'] ?? ''
    ]);
}

function toolResellerToggle($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/reseller.php?action=toggle', 'POST', [
        'client_id' => $cid, 'setting' => $args['setting'] ?? '', 'value' => $args['value'] ?? ''
    ]);
}

// ═══════════════════════════════════════════════════════════════
// SUPPORT CHAT (2 tools) — pay/api/support-chat.php
// ═══════════════════════════════════════════════════════════════

function toolSupportChatSend($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/support-chat.php?action=chat', 'POST', [
        'client_id' => $cid, 'message' => $args['message'] ?? ''
    ]);
}

function toolSupportChatHistory($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/support-chat.php?action=history&client_id=' . $cid);
}

// ═══════════════════════════════════════════════════════════════
// GAMIFICATION (1 tool)
// ═══════════════════════════════════════════════════════════════

function toolGamificationDailyChallenge($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredMainAPI('/api/alfred-chat.php', 'POST', [
        'action' => 'voice', 'client_id' => $cid,
        'tool' => 'daily_challenge', 'args' => $args
    ]);
}

// ═══════════════════════════════════════════════════════════════
// PROVISIONING (5 tools) — pay/api/provision.php
// ═══════════════════════════════════════════════════════════════

function toolProvisionSuspend($args) {
    $cid = (int)($args['client_id'] ?? 0);
    $serviceId = (int)($args['service_id'] ?? 0);
    if (!$serviceId) return ['error' => 'service_id required'];
    return alfredInternalAPI('/pay/api/provision.php?action=suspend', 'POST', [
        'client_id' => $cid, 'service_id' => $serviceId, 'reason' => $args['reason'] ?? ''
    ]);
}

function toolProvisionUnsuspend($args) {
    $cid = (int)($args['client_id'] ?? 0);
    $serviceId = (int)($args['service_id'] ?? 0);
    if (!$serviceId) return ['error' => 'service_id required'];
    return alfredInternalAPI('/pay/api/provision.php?action=unsuspend', 'POST', [
        'client_id' => $cid, 'service_id' => $serviceId
    ]);
}

function toolProvisionTerminate($args) {
    $cid = (int)($args['client_id'] ?? 0);
    $serviceId = (int)($args['service_id'] ?? 0);
    if (!$serviceId) return ['error' => 'service_id required'];
    return alfredInternalAPI('/pay/api/provision.php?action=terminate', 'POST', [
        'client_id' => $cid, 'service_id' => $serviceId
    ]);
}

function toolProvisionUpgrade($args) {
    $cid = (int)($args['client_id'] ?? 0);
    $serviceId = (int)($args['service_id'] ?? 0);
    if (!$serviceId) return ['error' => 'service_id required'];
    return alfredInternalAPI('/pay/api/provision.php?action=upgrade', 'POST', [
        'client_id' => $cid, 'service_id' => $serviceId, 'product_id' => (int)($args['product_id'] ?? 0)
    ]);
}

function toolProvisionTest($args) {
    return alfredInternalAPI('/pay/api/provision.php?action=test');
}

// ═══════════════════════════════════════════════════════════════
// SERVER STATS (3 tools) — pay/api/stats-api.php
// ═══════════════════════════════════════════════════════════════

function toolErrorLog($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    $lines = min((int)($args['lines'] ?? 50), 200);
    return alfredInternalAPI('/pay/api/stats-api.php?action=error-log&client_id=' . $cid . '&lines=' . $lines);
}

function toolAccessLog($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    $lines = min((int)($args['lines'] ?? 50), 200);
    return alfredInternalAPI('/pay/api/stats-api.php?action=access-log&client_id=' . $cid . '&lines=' . $lines);
}

function toolServerUsage($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/stats-api.php?action=usage&client_id=' . $cid);
}

// ═══════════════════════════════════════════════════════════════
// SSO (1 tool) — pay/api/sso.php
// ═══════════════════════════════════════════════════════════════

function toolSsoGenerateToken($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/sso.php?action=generate-token', 'POST', [
        'client_id' => $cid
    ]);
}

// ═══════════════════════════════════════════════════════════════
// COMMS/MESSAGING (7 tools) — api/comms.php + api/comms-v2.php
// ═══════════════════════════════════════════════════════════════

function toolCommsCreateGroup($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredMainAPI('/api/comms-v2.php?action=create_group', 'POST', [
        'client_id' => $cid, 'name' => $args['name'] ?? '', 'members' => $args['members'] ?? []
    ]);
}

function toolCommsGroupSend($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredMainAPI('/api/comms-v2.php?action=group_send', 'POST', [
        'client_id' => $cid, 'group_id' => $args['group_id'] ?? '',
        'message' => $args['message'] ?? ''
    ]);
}

function toolCommsGroupMessages($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredMainAPI('/api/comms-v2.php?action=group_messages&client_id=' . $cid . '&group_id=' . urlencode($args['group_id'] ?? ''));
}

function toolCommsMyGroups($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredMainAPI('/api/comms-v2.php?action=my_groups&client_id=' . $cid);
}

function toolCommsSendMessage($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredMainAPI('/api/comms.php?action=send', 'POST', [
        'client_id' => $cid, 'to' => $args['to'] ?? '', 'message' => $args['message'] ?? ''
    ]);
}

function toolCommsHistory($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredMainAPI('/api/comms.php?action=history&client_id=' . $cid . '&peer=' . urlencode($args['peer'] ?? ''));
}

function toolCommsUploadFile($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredMainAPI('/api/comms.php?action=upload', 'POST', [
        'client_id' => $cid, 'file_url' => $args['file_url'] ?? '', 'filename' => $args['filename'] ?? ''
    ]);
}

// ═══════════════════════════════════════════════════════════════
// VR WORLD (6 tools) — pay/api/vr-world.php
// ═══════════════════════════════════════════════════════════════

function toolVrChessMatch($args) {
    $cid = (int)($args['client_id'] ?? 0);
    $action = $args['match_action'] ?? 'start'; // start, status, update, list
    return alfredInternalAPI('/pay/api/vr-world.php?action=chess-match-' . urlencode($action), 'POST', [
        'client_id' => $cid, 'match_id' => $args['match_id'] ?? '', 'move' => $args['move'] ?? ''
    ]);
}

function toolVrChessChallenge($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/vr-world.php?action=chess-challenge', 'POST', [
        'client_id' => $cid, 'opponent_id' => $args['opponent_id'] ?? '',
        'wager' => (float)($args['wager'] ?? 0)
    ]);
}

function toolVrWorldPlots($args) {
    $cid = (int)($args['client_id'] ?? 0);
    $mine = !empty($args['my_plots']);
    $action = $mine ? 'world-my-plots' : 'world-plots';
    return alfredInternalAPI('/pay/api/vr-world.php?action=' . $action . '&client_id=' . $cid);
}

function toolVrWorldBuild($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/vr-world.php?action=world-build', 'POST', [
        'client_id' => $cid, 'plot_id' => $args['plot_id'] ?? '',
        'structure' => $args['structure'] ?? '', 'data' => $args['data'] ?? []
    ]);
}

function toolVrAvatarGet($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/vr-world.php?action=avatar-get&client_id=' . $cid);
}

function toolVrAvatarSave($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/vr-world.php?action=avatar-save', 'POST', [
        'client_id' => $cid, 'avatar' => $args['avatar'] ?? []
    ]);
}

// ═══════════════════════════════════════════════════════════════
// WEBHOOKS EXTENDED (4 tools) — pay/api/webhooks.php
// ═══════════════════════════════════════════════════════════════

function toolWebhookDelete($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/webhooks.php?action=delete', 'POST', [
        'client_id' => $cid, 'webhook_id' => $args['webhook_id'] ?? ''
    ]);
}

function toolWebhookUpdate($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/webhooks.php?action=update', 'POST', [
        'client_id' => $cid, 'webhook_id' => $args['webhook_id'] ?? '',
        'url' => $args['url'] ?? '', 'events' => $args['events'] ?? []
    ]);
}

function toolWebhookTest($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/webhooks.php?action=test', 'POST', [
        'client_id' => $cid, 'webhook_id' => $args['webhook_id'] ?? ''
    ]);
}

function toolWebhookLogs($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/webhooks.php?action=logs&client_id=' . $cid . '&webhook_id=' . urlencode($args['webhook_id'] ?? ''));
}

// ═══════════════════════════════════════════════════════════════
// ENTERPRISE (3 tools) — pay/api/enterprise-billing.php
// ═══════════════════════════════════════════════════════════════

function toolEnterpriseCreate($args) {
    return alfredInternalAPI('/pay/api/enterprise-billing.php?action=enterprise.create', 'POST', [
        'name' => $args['name'] ?? '', 'contact_email' => $args['email'] ?? '',
        'plan' => $args['plan'] ?? 'starter'
    ]);
}

function toolEnterpriseAddMember($args) {
    return alfredInternalAPI('/pay/api/enterprise-billing.php?action=enterprise.add_member', 'POST', [
        'enterprise_id' => $args['enterprise_id'] ?? '', 'client_id' => (int)($args['client_id'] ?? 0),
        'role' => $args['role'] ?? 'member'
    ]);
}

function toolEnterpriseDashboard($args) {
    $cid = (int)($args['client_id'] ?? 0);
    return alfredInternalAPI('/pay/api/enterprise-billing.php?action=dashboard&client_id=' . $cid);
}

// ═══════════════════════════════════════════════════════════════
// COMMISSIONS & PAYOUTS (4 tools) — pay/api/enterprise-billing.php
// ═══════════════════════════════════════════════════════════════

function toolCommissionCalc($args) {
    return alfredInternalAPI('/pay/api/enterprise-billing.php?action=commission.calculate', 'POST', [
        'affiliate_id' => (int)($args['affiliate_id'] ?? 0),
        'source_type' => $args['source_type'] ?? '',
        'source_id' => (int)($args['source_id'] ?? 0),
        'amount' => (float)($args['amount'] ?? 0)
    ]);
}

function toolCommissionApprove($args) {
    return alfredInternalAPI('/pay/api/enterprise-billing.php?action=commission.approve', 'POST', [
        'commission_id' => (int)($args['commission_id'] ?? 0)
    ]);
}

function toolPayoutCreate($args) {
    return alfredInternalAPI('/pay/api/enterprise-billing.php?action=payout.create', 'POST', [
        'affiliate_id' => (int)($args['affiliate_id'] ?? 0),
        'amount' => (float)($args['amount'] ?? 0), 'method' => $args['method'] ?? 'paypal'
    ]);
}

function toolPayoutProcess($args) {
    return alfredInternalAPI('/pay/api/enterprise-billing.php?action=payout.process', 'POST', [
        'payout_id' => (int)($args['payout_id'] ?? 0)
    ]);
}

// ═══════════════════════════════════════════════════════════════
// SITE DOCTOR (3 tools) — pay/api/site-doctor.php
// ═══════════════════════════════════════════════════════════════

function toolSiteDoctorScan($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/site-doctor.php?action=scan', 'POST', [
        'client_id' => $cid, 'domain' => $args['domain'] ?? ''
    ]);
}

function toolSiteDoctorResults($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/site-doctor.php?action=results&client_id=' . $cid . '&scan_id=' . urlencode($args['scan_id'] ?? ''));
}

function toolSiteDoctorReport($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/site-doctor.php?action=report&client_id=' . $cid);
}

// ═══════════════════════════════════════════════════════════════
// AGENT REGISTRY (7 tools) — api/agent-registry.php
// ═══════════════════════════════════════════════════════════════

function toolAgentRegistryList($args) {
    return alfredMainAPI('/api/agent-registry.php?action=list');
}

function toolAgentRegistryGet($args) {
    $agentId = $args['agent_id'] ?? '';
    return alfredMainAPI('/api/agent-registry.php?action=get&agent_id=' . urlencode($agentId));
}

function toolAgentHierarchy($args) {
    return alfredMainAPI('/api/agent-registry.php?action=hierarchy');
}

function toolAgentDelegateTask($args) {
    return alfredMainAPI('/api/agent-registry.php?action=delegate', 'POST', [
        'agent_id' => $args['agent_id'] ?? '', 'task' => $args['task'] ?? '',
        'priority' => $args['priority'] ?? 'normal'
    ]);
}

function toolAgentMessages($args) {
    $agentId = $args['agent_id'] ?? '';
    return alfredMainAPI('/api/agent-registry.php?action=messages&agent_id=' . urlencode($agentId));
}

function toolAgentHeartbeat($args) {
    return alfredMainAPI('/api/agent-registry.php?action=heartbeat', 'POST', [
        'agent_id' => $args['agent_id'] ?? '', 'status' => $args['status'] ?? 'alive'
    ]);
}

function toolAgentRegistryStats($args) {
    return alfredMainAPI('/api/agent-registry.php?action=stats');
}

// ═══════════════════════════════════════════════════════════════
// FLEET EXTENDED (3 tools) — pay/api/fleet-tools.php
// ═══════════════════════════════════════════════════════════════

function toolFleetBatch($args) {
    $cid = (int)($args['client_id'] ?? 0);
    return alfredInternalAPI('/pay/api/fleet-tools.php?action=batch', 'POST', [
        'client_id' => $cid, 'commands' => $args['commands'] ?? []
    ]);
}

function toolFleetAvailable($args) {
    return alfredInternalAPI('/pay/api/fleet-tools.php?action=available');
}

function toolFleetHistory($args) {
    $cid = (int)($args['client_id'] ?? 0);
    return alfredInternalAPI('/pay/api/fleet-tools.php?action=history&client_id=' . $cid);
}

// ═══════════════════════════════════════════════════════════════
// LEARNING SYSTEM (3 tools) — api/learning.php
// ═══════════════════════════════════════════════════════════════

function toolLearningInsights($args) {
    return alfredMainAPI('/api/learning.php?action=insights');
}

function toolLearningPatterns($args) {
    return alfredMainAPI('/api/learning.php?action=patterns');
}

function toolLearningPerformance($args) {
    return alfredMainAPI('/api/learning.php?action=performance');
}

// ═══════════════════════════════════════════════════════════════
// MARKETPLACE EXTENDED (3 tools) — pay/api/marketplace.php
// ═══════════════════════════════════════════════════════════════

function toolMarketplacePublish($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/marketplace.php?action=publish', 'POST', [
        'client_id' => $cid, 'agent_id' => $args['agent_id'] ?? '',
        'name' => $args['name'] ?? '', 'description' => $args['description'] ?? '',
        'price' => (float)($args['price'] ?? 0)
    ]);
}

function toolMarketplaceRate($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/marketplace.php?action=rate', 'POST', [
        'client_id' => $cid, 'agent_id' => $args['agent_id'] ?? '',
        'rating' => min(5, max(1, (int)($args['rating'] ?? 5))), 'review' => $args['review'] ?? ''
    ]);
}

function toolMarketplaceUninstall($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/marketplace.php?action=uninstall', 'POST', [
        'client_id' => $cid, 'agent_id' => $args['agent_id'] ?? ''
    ]);
}

// ═══════════════════════════════════════════════════════════════
// UPTIME EXTENDED (3 tools) — pay/api/uptime.php
// ═══════════════════════════════════════════════════════════════

function toolUptimeCheck($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/uptime.php?action=check&client_id=' . $cid . '&domain=' . urlencode($args['domain'] ?? ''));
}

function toolUptimeHistory($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/uptime.php?action=history&client_id=' . $cid . '&check_id=' . urlencode($args['check_id'] ?? ''));
}

function toolUptimeIncidentDetails($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'Authenticate first'];
    return alfredInternalAPI('/pay/api/uptime.php?action=incidents&client_id=' . $cid);
}
