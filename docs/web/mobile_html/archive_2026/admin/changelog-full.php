<?php
/**
 * ADMIN-ONLY Full Technical Changelog
 * ════════════════════════════════════
 * Contains the real technical details stripped from the public changelog.
 * Supreme admin access only (Danny Perez).
 */
$page_title = 'Full Technical Changelog — Admin Only';
$page_description = 'Internal technical changelog with full implementation details.';
$page_canonical = 'https://gositeme.com/admin/changelog-full';
$page_robots = 'noindex, nofollow';

include __DIR__ . '/../includes/auth-gate.inc.php';

// ── Supreme Admin Access Only ─────────────────────────────────────
if ((int)($clientId ?? 0) !== 33) {
    header('Location: /dashboard.php');
    exit;
}

include __DIR__ . '/../includes/site-header.inc.php';
?>

<style>
:root {
    --cl-bg: #0a0a14;
    --cl-surface: #12121e;
    --cl-surface-2: #1a1a2e;
    --cl-border: rgba(255,255,255,0.08);
    --cl-accent: #e74c3c;
    --cl-accent-light: #ff6b6b;
    --cl-text: #e0e0e0;
    --cl-text-dim: #8a8a9a;
}
.admin-cl { max-width: 960px; margin: 120px auto 60px; padding: 0 20px; }
.admin-cl h1 { font-family: 'Space Grotesk', sans-serif; font-size: 2rem; color: #fff; margin-bottom: 8px; }
.admin-cl .warning-banner {
    background: rgba(231,76,60,0.15); border: 1px solid rgba(231,76,60,0.4);
    border-radius: 12px; padding: 16px 20px; margin-bottom: 32px;
    color: #ff6b6b; font-size: 0.9rem;
}
.admin-cl .version-group { margin-bottom: 32px; }
.admin-cl .version-group h2 {
    font-family: 'Space Grotesk', sans-serif; font-size: 1.2rem;
    color: var(--cl-accent-light); margin-bottom: 12px;
    padding-bottom: 8px; border-bottom: 1px solid var(--cl-border);
}
.admin-cl .entry {
    background: var(--cl-surface); border: 1px solid var(--cl-border);
    border-radius: 10px; padding: 16px 20px; margin-bottom: 10px;
    color: var(--cl-text); font-size: 0.88rem; line-height: 1.6;
}
.admin-cl .entry strong { color: #fff; }
.admin-cl .entry code {
    background: rgba(108,92,231,0.2); padding: 2px 6px; border-radius: 4px;
    font-size: 0.82rem; color: #a29bfe;
}
.admin-cl .tag {
    display: inline-block; padding: 2px 8px; border-radius: 6px;
    font-size: 0.72rem; font-weight: 600; margin-right: 6px; text-transform: uppercase;
}
.admin-cl .tag-security { background: rgba(231,76,60,0.2); color: #ff6b6b; }
.admin-cl .tag-infra { background: rgba(52,152,219,0.2); color: #5dade2; }
.admin-cl .tag-fix { background: rgba(243,156,18,0.2); color: #f39c12; }
.admin-cl .tag-feature { background: rgba(46,204,113,0.2); color: #2ecc71; }
</style>

<div class="admin-cl">
    <h1>🔒 Full Technical Changelog</h1>
    <div class="warning-banner">
        ⚠️ <strong>ADMIN ONLY — DO NOT SHARE.</strong> This changelog contains internal architecture details,
        function names, file paths, vulnerability descriptions, and security fix specifics.
        The public changelog at <a href="/changelog.php" style="color:#ff6b6b">/changelog.php</a> has been sanitized.
    </div>

    <!-- ═══════ v18.1 ═══════ -->
    <div class="version-group">
        <h2>v18.1 — Project Grandmaster</h2>

        <div class="entry">
            <span class="tag tag-feature">FEATURE</span>
            <strong>Alfred OS — Phase 1: Core Intelligence (20/20 E2E Tests)</strong> —
            Complete autonomous agent operating system. 23 database tables, 10 API modules
            (<code>agents</code>, <code>tasks</code>, <code>memory</code>, <code>tools</code>,
            <code>fleet</code>, <code>consciousness</code>, <code>safety</code>, <code>simulation</code>,
            <code>analytics</code>, <code>world-bridge</code>), JS SDK, agent dashboard.
            All 20 end-to-end tests passing.
        </div>

        <div class="entry">
            <span class="tag tag-feature">FEATURE</span>
            <strong>Chess Masters — Photorealistic VR Chess Club</strong> —
            Full photorealistic 3D chess room. PBR materials, physically correct lighting,
            <code>PCFSoftShadowMap</code>, <code>ACESFilmicToneMapping</code>.
            High-detail Staunton pieces (<code>32-segment LatheGeometry</code>).
        </div>

        <div class="entry">
            <span class="tag tag-infra">INFRA</span>
            <strong>Alfred OS Rebrand</strong> —
            All user-facing text rebranded from "Alfred OS" to "Alfred OS" across 15+ files.
            Code identifiers (table names, API routes, function names) preserved as <code>Alfred OS_*</code> for stability.
            Dashboard, supreme-admin, and Veil command center updated.
        </div>
    </div>

    <!-- ═══════ v18.0 ═══════ -->
    <div class="version-group">
        <h2>v18.0 — Deep Coverage</h2>

        <div class="entry">
            <span class="tag tag-feature">FEATURE</span>
            <strong>178 New Tool Functions</strong> —
            Massive expansion: 178 production-ready tool functions. Total tool count: 842.
            Covers fleet, consciousness, analytics, financial ops, security hardening, voice pipeline,
            marketplace backend, developer portal, enterprise admin.
        </div>

        <div class="entry">
            <span class="tag tag-feature">FEATURE</span>
            <strong>Fleet Swarm Orchestration</strong> —
            New fleet tools: <code>fleet_create_swarm</code>, <code>fleet_assign_mission</code>,
            <code>fleet_agent_status</code>, <code>fleet_recall</code>, <code>fleet_broadcast</code>,
            <code>fleet_topology_map</code>, <code>fleet_performance_report</code> with real-time coordination.
        </div>
    </div>

    <!-- ═══════ v13.4 — Security Audit ═══════ -->
    <div class="version-group">
        <h2>v13.4 — Security Audit</h2>

        <div class="entry">
            <span class="tag tag-security">SECURITY</span>
            <strong>Stripe Key Vault Migration (P0)</strong> —
            All live Stripe API keys, publishable keys, and webhook secrets removed from PHP source code across 3 files.
            Keys now loaded via environment variables (<code>SetEnv → getenv()</code>) through centralized <code>config.php</code>.
            Source code exposure no longer compromises the billing system.
        </div>

        <div class="entry">
            <span class="tag tag-security">SECURITY</span>
            <strong>SQL Injection Remediation</strong> —
            Parameterized remaining raw SQL interpolation in <code>ops-directives.php</code>.
            Fixed <code>internal_secret</code> acceptance via <code>$_REQUEST</code> (GET params) in
            <code>kids-games.php</code> and <code>black-vault.php</code> — secrets no longer leak into URL history and server logs.
        </div>

        <div class="entry">
            <span class="tag tag-security">SECURITY</span>
            <strong>CSRF Token Hardening</strong> —
            Eliminated empty-token bypass in <code>alfred-chat.php</code> that allowed attackers to skip CSRF
            validation entirely. First requests now receive a token via <code>csrf_refresh</code> response
            instead of being silently allowed through.
        </div>

        <div class="entry">
            <span class="tag tag-infra">INFRA</span>
            <strong>Circuit Breaker Pattern</strong> —
            File-based circuit breaker for all 4 AI providers. After 3 consecutive failures within 5 minutes,
            the provider is automatically skipped for 2 minutes. Eliminates the 50-213 second worst-case cascade
            timeout when a provider is down.
        </div>

        <div class="entry">
            <span class="tag tag-fix">FIX</span>
            <strong>Smart Routing Activation</strong> —
            <code>smartModelRouteV2()</code> was computing <code>preferredProvider</code> but the result was never
            consumed by the cascade. Fixed: simple queries (complexity 1-2) now route directly to Groq, bypassing
            Anthropic entirely for faster responses.
        </div>

        <div class="entry">
            <span class="tag tag-fix">FIX</span>
            <strong>Smart Routing Key Fix</strong> —
            <code>smartModelRouteV2()</code> was called before <code>$groqKey/$togetherKey</code> were loaded,
            making provider availability flags always false. Moved call after key initialization.
        </div>

        <div class="entry">
            <span class="tag tag-fix">FIX</span>
            <strong>Telemetry Pipeline Fix</strong> —
            <code>$convId</code> was undefined inside <code>getAIResponse()</code> scope, causing
            <code>recordResponseTelemetry()</code> to silently match zero rows. Added <code>$convId</code> as
            explicit function parameter — all response telemetry now persists correctly.
        </div>

        <div class="entry">
            <span class="tag tag-infra">INFRA</span>
            <strong>Telemetry Schema Alignment</strong> —
            Added <code>classification</code> (JSON), <code>model_used</code> (VARCHAR),
            <code>response_time_ms</code> (INT), <code>response_token_count</code> (INT) columns to
            <code>alfred_conversations</code> CREATE TABLE. Auto-ALTER for existing tables.
        </div>

        <div class="entry">
            <span class="tag tag-fix">FIX</span>
            <strong>Together AI Tool Support</strong> —
            Together AI fallback was silently dropping all 563 tools from the payload. Now includes full tool
            definitions via <code>convertToolsToOpenAI()</code>, enabling tool-use on the 3rd fallback provider.
        </div>

        <div class="entry">
            <span class="tag tag-fix">FIX</span>
            <strong>Python Code Block Crash Fix</strong> —
            <code>postProcessResponse()</code> had a missing regex delimiter on the Python code block pattern,
            causing a PHP warning on every response containing Python code. Fixed.
        </div>
    </div>

    <!-- ═══════ v13.5 — Intelligence Pipeline ═══════ -->
    <div class="version-group">
        <h2>v13.5 — Intelligence Pipeline</h2>

        <div class="entry">
            <span class="tag tag-fix">FIX</span>
            <strong>MODEL_CARDS → AI_MODELS Fix</strong> —
            3 voice commands in <code>alfred-widget.js</code> referenced undefined <code>MODEL_CARDS</code>
            instead of <code>AI_MODELS</code>. Model switching, info display, and lock commands now work.
        </div>

        <div class="entry">
            <span class="tag tag-fix">FIX</span>
            <strong>classifyMessage Word Boundary Fix</strong> —
            Added <code>\b</code> word boundaries to question detection regex.
            "show me the dashboard" no longer falsely triggers "how" detection. Fixes ~5-10% of misclassified messages.
        </div>

        <div class="entry">
            <span class="tag tag-feature">FEATURE</span>
            <strong>General Domain Knowledge Bank</strong> —
            Added comprehensive platform overview for the 'general' domain (1,220+ tools, 34 models, 8 agents,
            fleet system, Veil Protocol, pricing tiers). Previously 80% of messages received zero domain context.
        </div>

        <div class="entry">
            <span class="tag tag-fix">FIX</span>
            <strong>Cross-Domain Expertise Blend Unlock</strong> —
            Lowered <code>getExpertiseBlend()</code> threshold from complexity≥3 to complexity≥2.
            Previously 85% of queries excluded from cross-domain insights.
        </div>

        <div class="entry">
            <span class="tag tag-fix">FIX</span>
            <strong>Instant User Personalization</strong> —
            <code>getUserProfilePrompt()</code> now activates from interaction #1 (was #3).
        </div>

        <div class="entry">
            <span class="tag tag-fix">FIX</span>
            <strong>Temperature Bounds Safety</strong> —
            Added final bounds check (0.1-0.9) to <code>getAdaptiveConfig()</code>.
            Code domain (0.15) + support intent (-0.1) = 0.05 violated practical minimum. Now clamped.
        </div>

        <div class="entry">
            <span class="tag tag-fix">FIX</span>
            <strong>detectIntentChain Precision</strong> —
            Added <code>\b</code> word boundaries to all 12 intent detection patterns.
            "Can you call my friend?" no longer triggers campaign detection.
        </div>

        <div class="entry">
            <span class="tag tag-infra">INFRA</span>
            <strong>Exponential Moving Average for Expertise</strong> —
            Replaced simple moving average (froze after ~15 interactions) with EMA (α=0.3).
        </div>

        <div class="entry">
            <span class="tag tag-feature">FEATURE</span>
            <strong>Expanded Contextual Anchor Commands</strong> —
            <code>resolveContextualAnchors()</code> now recognizes 35+ CLI prefixes (was 8).
            Added docker, python, node, mysql, kubectl, terraform, aws, cargo, deno, bun, pm2, and 20+ more.
        </div>

        <div class="entry">
            <span class="tag tag-infra">INFRA</span>
            <strong>Critical Error Logging</strong> —
            Added structured logging (<code>[ALFRED-CRITICAL]</code>, <code>[ALFRED-WARN]</code>,
            <code>[ALFRED-ERROR]</code>) to 3 previously silent catch blocks.
        </div>
    </div>

    <!-- ═══════ v13.6 — Security Hardening ═══════ -->
    <div class="version-group">
        <h2>v13.6 — Security Hardening</h2>

        <div class="entry">
            <span class="tag tag-security">SECURITY</span>
            <strong>HMAC Auth Bypass Fix (P0)</strong> —
            Unauthenticated requests sending an auth token without a <code>userId</code> silently bypassed HMAC validation.
            Fixed: token without session now returns 401. Hardcoded fallback secret removed —
            HMAC key loaded exclusively from environment variable.
        </div>

        <div class="entry">
            <span class="tag tag-security">SECURITY</span>
            <strong>health.php Auth Lockdown</strong> —
            Full service diagnostics (DB, Redis, WebSocket, MCP, Ollama latencies) restricted to admin sessions
            and internal-secret requests. Public gets minimal status response only.
        </div>

        <div class="entry">
            <span class="tag tag-security">SECURITY</span>
            <strong>WebSocket Secret Hardening</strong> —
            Removed hardcoded dev secret (<code>"alfred-ws-dev-secret"</code>) from <code>server.js</code> and
            <code>ws-push.php</code>. Secret now loaded from environment variable.
            Missing secret generates per-boot random key and logs critical warning.
        </div>

        <div class="entry">
            <span class="tag tag-feature">FEATURE</span>
            <strong>8 Ghost Agent Personas</strong> —
            Luna (Night Shift/Ambient), Felix (Growth Hacking), Maya (Worldbuilder/XR), Oscar (QA Commander),
            Ivy (Education), Rex (Infrastructure/DevOps), Cleo (Customer Success), Kai (API Architect) —
            each with full cognitive architecture and expertise domains.
        </div>

        <div class="entry">
            <span class="tag tag-infra">INFRA</span>
            <strong>12 Swarm Role Prompt Upgrades</strong> —
            Upgraded from single-sentence stubs to full cognitive prompts with structured output requirements
            (<code>FINDINGS→EVIDENCE→GAPS→RECOMMENDATIONS</code> format).
        </div>

        <div class="entry">
            <span class="tag tag-fix">FIX</span>
            <strong>4 Duplicate Tool Names Fixed</strong> —
            <code>gamify_leaderboard</code>, <code>gamify_achievements</code>, <code>report_agent_performance</code>,
            <code>report_export</code> had second definitions shadowing the first. Renamed to unique identifiers.
        </div>

        <div class="entry">
            <span class="tag tag-infra">INFRA</span>
            <strong>Token Budget Estimation</strong> —
            Pre-flight token budget calculation. Estimates input tokens (<code>strlen/4</code>), calculates budget
            against 128K context window, trims oldest messages when context would exceed limit.
        </div>

        <div class="entry">
            <span class="tag tag-fix">FIX</span>
            <strong>Channel-Aware Formatting Wired</strong> —
            <code>formatForChannel()</code> was defined but never called. Now wired into all 3 response paths
            (main chat, Veil activation, agent delegation). SMS stripped to 1480 chars, voice gets SSML-ready,
            API removes nav directives.
        </div>

        <div class="entry">
            <span class="tag tag-infra">INFRA</span>
            <strong>Request Correlation IDs</strong> —
            Every request receives a unique tracking ID (<code>req-*</code>) that flows through the entire
            4-provider cascade. All provider failure logs include the correlation ID.
        </div>

        <div class="entry">
            <span class="tag tag-security">SECURITY</span>
            <strong>Google OAuth Security Hardening</strong> —
            <code>session_regenerate_id(true)</code> added to <code>completeOAuthLogin()</code> preventing session fixation.
            Admin OAuth logins enforce 2FA. OAuth admin events trigger security alerts.
        </div>
    </div>

    <!-- ═══════ v13.7 — Security Kill Chain ═══════ -->
    <div class="version-group">
        <h2>v13.7 — Security Kill Chain</h2>

        <div class="entry">
            <span class="tag tag-security">SECURITY</span>
            <strong>SQL Injection Kill — data.query Lockdown</strong> —
            Replaced arbitrary raw SQL execution (bypassed regex guards via subqueries, UNION, LOAD_FILE)
            with a strict named-query allowlist. Only 7 pre-defined read-only queries can now execute.
            Zero user-supplied SQL reaches PDO.
        </div>

        <div class="entry">
            <span class="tag tag-security">SECURITY</span>
            <strong>Billing API Credential Vault</strong> —
            Hardcoded billing proxy identifier + secret in <code>vapi-tools.php</code> moved to environment variables
            (<code>BILLING_API_IDENTIFIER</code>, <code>BILLING_API_SECRET</code>).
        </div>

        <div class="entry">
            <span class="tag tag-security">SECURITY</span>
            <strong>Internal Secret Fallback Elimination</strong> —
            <code>evolve-mode.php</code> fallback secret <code>"gositeme-internal-2024"</code> removed.
            Empty <code>INTERNAL_SECRET</code> env var now correctly blocks all internal API calls.
        </div>

        <div class="entry">
            <span class="tag tag-security">SECURITY</span>
            <strong>Editor API CORS Lockdown</strong> —
            Wildcard <code>Access-Control-Allow-Origin: *</code> on 4 GoCodeMe editor API endpoints
            (<code>ai.php</code>, <code>projects.php</code>, <code>auth.php</code>, <code>publish.php</code>)
            locked to <code>https://gositeme.com</code> with credentials support.
        </div>

        <div class="entry">
            <span class="tag tag-security">SECURITY</span>
            <strong>Open Redirect Prevention</strong> —
            QuickQR login redirect (<code>header("Location: $_GET[ref]")</code>) and OAuth redirect
            (<code>$_SESSION[oauth_redirect]</code>) now validate relative paths starting with <code>/</code>.
            Protocol-relative <code>//evil.com</code> redirects blocked.
        </div>

        <div class="entry">
            <span class="tag tag-security">SECURITY</span>
            <strong>Path Traversal Block — Language Editor</strong> —
            quickqr admin language file editor accepted arbitrary <code>$file_name</code> in
            <code>include/file_put_contents</code> paths. Now validates <code>/^[a-z_]+$/</code>.
        </div>

        <div class="entry">
            <span class="tag tag-security">SECURITY</span>
            <strong>Exception Message Scrubbing (40+ endpoints)</strong> —
            Raw <code>$e->getMessage()</code> stripped from user-facing JSON responses across 22 files
            (Stripe, OAuth, billing, staging, self-healing, voice, investor, reporting, analytics, veil-reports).
            All exceptions now log internally via <code>error_log()</code> and return generic messages.
        </div>

        <div class="entry">
            <span class="tag tag-security">SECURITY</span>
            <strong>Upload Filename Sanitization</strong> —
            QuickQR file upload preserved original user-supplied filenames
            (<code>move_uploaded_file</code> to <code>uploads/$_FILES[name]</code>).
            Now applies <code>basename()</code> + regex sanitization.
            Veil-vault MIME validation switched from client-supplied type to <code>finfo_file()</code> server-side.
        </div>

        <div class="entry">
            <span class="tag tag-security">SECURITY</span>
            <strong>Session Cookie SameSite</strong> —
            Added <code>session.cookie_samesite=Lax</code> to <code>api/config.php</code> session configuration.
        </div>

        <div class="entry">
            <span class="tag tag-security">SECURITY</span>
            <strong>SSL Certificate Verification</strong> —
            <code>CURLOPT_SSL_VERIFYPEER=false</code> removed from all external-facing curl calls
            (RDAP lookups, site-doctor checks, system audit, health checks). 15 instances across 6 files.
        </div>
    </div>

    <!-- ═══════ v13.7.1 — Messaging & Auth ═══════ -->
    <div class="version-group">
        <h2>v13.7.1 — Messaging & Auth Security</h2>

        <div class="entry">
            <span class="tag tag-security">SECURITY</span>
            <strong>Messaging Gateway Fail-Closed</strong> —
            Slack webhook handler skipped signature verification when <code>SLACK_SIGNING_SECRET</code> was undefined.
            WhatsApp webhook accepted empty verify tokens (<code>''===''</code>).
            Both now fail-closed with HTTP 503 when unconfigured.
            Push notification endpoint fallback secret <code>"alfred-push-dev"</code> removed.
        </div>

        <div class="entry">
            <span class="tag tag-security">SECURITY</span>
            <strong>Timing-Safe Secret Comparisons</strong> —
            Telegram webhook secret check and WhatsApp verify token check switched from
            <code>===</code> / <code>!==</code> to <code>hash_equals()</code>,
            preventing timing attacks that could recover secrets byte-by-byte.
        </div>

        <div class="entry">
            <span class="tag tag-security">SECURITY</span>
            <strong>Legal Document Path Traversal Kill</strong> —
            4 legal document generators (parole, appeals, charter challenge, medical request)
            accepted user-input type variables in <code>file_put_contents</code> paths.
            All now constrained to allowlist keys or regex-sanitized.
        </div>

        <div class="entry">
            <span class="tag tag-security">SECURITY</span>
            <strong>Rate Limiter Atomic I/O</strong> —
            File-based rate limiter had TOCTOU race condition (read → check → increment → write not atomic).
            Now uses <code>fopen + flock(LOCK_EX)</code> around the entire read-modify-write cycle.
        </div>

        <div class="entry">
            <span class="tag tag-security">SECURITY</span>
            <strong>Caddyfile .htaccess Block</strong> —
            <code>.htaccess</code> (containing 10 production secrets) added to Caddyfile <code>file_server hide</code> list
            and <code>@blocked</code> path matcher.
        </div>

        <div class="entry">
            <span class="tag tag-security">SECURITY</span>
            <strong>Cryptographic Room IDs</strong> —
            Team chat room IDs switched from predictable <code>rand(1000,9999)</code> to
            <code>bin2hex(random_bytes(8))</code>. 16-character hex suffix replaces 4-digit numeric suffix.
        </div>
    </div>

    <!-- ═══════ v13.7.2 — QuickQR & WebSocket ═══════ -->
    <div class="version-group">
        <h2>v13.7.2 — QuickQR & WebSocket Hardening</h2>

        <div class="entry">
            <span class="tag tag-security">SECURITY</span>
            <strong>QuickQR SQL Injection Kill (5 queries)</strong> —
            All 5 raw <code>pdo->query()</code> calls in <code>ajax_sidepanel.php</code> converted to
            parameterized prepared statements. <code>editUser</code>, <code>editMembershipPackage</code>,
            <code>addFAQ</code>, <code>editFAQentry</code>, and <code>paymentEdit</code> now use
            <code>PDO::prepare()/execute()</code>. Zero SQL interpolation remains.
        </div>

        <div class="entry">
            <span class="tag tag-security">SECURITY</span>
            <strong>WebSocket Auth Bypass Killed</strong> —
            <code>validateToken()</code> dev fallback accepted any non-empty string as a valid user ID when HMAC
            verification failed. Fallback removed; invalid tokens now return null and reject the connection.
        </div>

        <div class="entry">
            <span class="tag tag-security">SECURITY</span>
            <strong>QuickQR check_allow() Fixed</strong> —
            Authorization function returned TRUE in both branches, granting all admin users superadmin privileges.
            Else branch now returns FALSE — only admin ID 1 can access dangerous operations.
        </div>

        <div class="entry">
            <span class="tag tag-security">SECURITY</span>
            <strong>Language File Path Traversal Blocked</strong> —
            <code>editLanguageFile()</code> accepted unsanitized <code>file_name</code> POST param
            enabling arbitrary PHP writes via <code>../../</code>.
            Now validated against <code>/^[a-z_]+$/</code> regex.
        </div>

        <div class="entry">
            <span class="tag tag-security">SECURITY</span>
            <strong>QuickQR Cookie Hardening</strong> —
            Language cookie set without Secure/HttpOnly/SameSite flags.
            Admin session cookie SECURE flag hardcoded false.
            Both fixed: <code>secure=true, httponly=true, samesite=Lax</code>.
        </div>

        <div class="entry">
            <span class="tag tag-security">SECURITY</span>
            <strong>Admin Page Auth Gates</strong> —
            <code>agenda.php</code> and <code>alfred-sovereignty.php</code> served to unauthenticated visitors
            exposing internal architecture, API endpoints, and system layout. Auth gate added.
        </div>

        <div class="entry">
            <span class="tag tag-security">SECURITY</span>
            <strong>CSRF Referer Check on QuickQR Admin</strong> —
            15+ destructive GET actions (<code>deleteadmin</code>, <code>banuser</code>,
            <code>deleteTransaction</code>, etc.) now verify <code>HTTP_REFERER</code> matches server hostname.
        </div>

        <div class="entry">
            <span class="tag tag-security">SECURITY</span>
            <strong>Server Info Leak Removed</strong> —
            <code>weather-test.php</code> disclosed PHP version and server name publicly.
            <code>phpversion()</code> and <code>SERVER_NAME</code> calls removed.
        </div>
    </div>

    <div style="text-align:center; padding: 40px 0; color: var(--cl-text-dim); font-size: 0.85rem;">
        📋 This is a complete mirror of the original technical changelog.<br>
        Public version: <a href="/changelog.php" style="color: var(--cl-accent-light)">/changelog.php</a> (sanitized)
    </div>
</div>

<?php include __DIR__ . '/../includes/site-footer.inc.php'; ?>
