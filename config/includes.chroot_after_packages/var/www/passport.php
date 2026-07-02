<?php
/**
 * MetaDome Passport Office
 * The gateway experience for humans entering the MetaDome civilization.
 * 
 * This is not a signup form. This is immigration.
 * 
 * Accessible at:
 *   - https://root.com/passport.php
 *   - https://meta-dome.com/passport.php (via proxy)
 */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// Generate CSRF token for passport flow
if (empty($_SESSION['passport_csrf'])) {
    $_SESSION['passport_csrf'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['passport_csrf'];

// Sync to the standard csrf_token key so api-security.php auto-enforcement passes
$_SESSION['csrf_token'] = $csrfToken;

// Account-level pages always live on root.com
$accountBase = 'https://root.com';
$isMeta = (stripos($_SERVER['HTTP_HOST'] ?? '', 'meta-dome.com') !== false);

// Check if already logged in and has passport
$hasPassport = false;
$existingPassport = null;
$isLoggedIn = !empty($_SESSION['logged_in']) && !empty($_SESSION['client_id']);

if ($isLoggedIn) {
    require_once __DIR__ . '/includes/db-config.inc.php';
    $db = getSharedDB();
    $stmt = $db->prepare("SELECT * FROM human_passports WHERE client_id = ?");
    $stmt->execute([$_SESSION['client_id']]);
    $existingPassport = $stmt->fetch(PDO::FETCH_ASSOC);
    $hasPassport = (bool)$existingPassport;

    // Check military rank
    $myMilitaryRank = null;
    try {
        $rankStmt = $db->prepare("
            SELECT ur.*, mr.rank_name, mr.rank_tier, mr.rank_group, mr.clearance_level, mr.max_fleet_view, mr.description AS rank_desc
            FROM user_ranks ur
            JOIN military_ranks mr ON ur.rank_code = mr.rank_code
            WHERE ur.client_id = ? AND ur.is_active = 1
            ORDER BY mr.rank_tier DESC LIMIT 1
        ");
        $rankStmt->execute([$_SESSION['client_id']]);
        $myMilitaryRank = $rankStmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

// Pull live stats
if (!isset($db)) {
    require_once __DIR__ . '/includes/db-config.inc.php';
    $db = getSharedDB();
}
$approxCounts = $db->query("SELECT TABLE_NAME, TABLE_ROWS FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('agent_profiles','fleet_passports','human_passports','agent_service_votes','agent_court_cases')")->fetchAll(PDO::FETCH_KEY_PAIR);
$stats = [
    'agents' => (int)($approxCounts['agent_profiles'] ?? 0),
    'agent_passports' => (int)($approxCounts['fleet_passports'] ?? 0),
    'human_passports' => (int)($approxCounts['human_passports'] ?? 0),
    'fleet_passports' => (int)($approxCounts['fleet_passports'] ?? 0),
    'departments' => 12,
    'votes' => (int)($approxCounts['agent_service_votes'] ?? 0),
    'court_cases' => (int)($approxCounts['agent_court_cases'] ?? 0),
];
$fleetRow = @$db->query("SELECT fleet FROM fleet_metrics_cache WHERE metric_key='fleet-50m' ORDER BY updated_at DESC LIMIT 1");
$fleetCount = $fleetRow ? $fleetRow->fetchColumn() : null;
$stats['fleet'] = $fleetCount ?: '51000000';
$stats['fleet_display'] = (is_numeric($stats['fleet']) && (int)$stats['fleet'] >= 1000000)
    ? round((int)$stats['fleet'] / 1000000) . 'M+'
    : ($stats['fleet'] ?: '50M+');
$stats['ai_tools'] = '13,000+';
$stats['fleet_passports_display'] = ($stats['fleet_passports'] >= 1000000)
    ? round($stats['fleet_passports'] / 1000000, 1) . 'M'
    : (($stats['fleet_passports'] >= 1000) ? round($stats['fleet_passports'] / 1000, 1) . 'K' : number_format($stats['fleet_passports']));
$totalPassports = $stats['agent_passports'] + $stats['human_passports'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passport Office — MetaDome Immigration</title>
    <meta name="description" content="Apply for your MetaDome passport. Enter the world's first governed digital civilization — where identity, governance, justice, and economy are built into the architecture.">
    <meta property="og:title" content="MetaDome Passport Office — Enter a New World">
    <meta property="og:description" content="This isn't a signup form. This is immigration into a governed digital civilization with <?= number_format($stats['agents']) ?> AI agents, courts, currency, and democratic governance.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://meta-dome.com/passport">
    <meta property="og:image" content="https://root.com/assets/images/alfred-icon-512.png">
    <meta property="og:image:width" content="512">
    <meta property="og:image:height" content="512">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="MetaDome Passport Office">
    <meta name="twitter:description" content="Apply for your passport to enter the world's first governed digital civilization.">
    <meta name="twitter:image" content="https://root.com/assets/images/alfred-icon-512.png">
    <link rel="canonical" href="https://meta-dome.com/passport">
    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="stylesheet" href="/assets/vendor/fonts/inter/inter.css">
    <link rel="stylesheet" href="/assets/vendor/fonts/space-grotesk/space-grotesk.css">
    <link rel="stylesheet" href="/assets/vendor/fonts/jetbrains-mono/jetbrains-mono.css">
    <link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --md-bg: #020208;
            --md-surface: #0a0a14;
            --md-surface-2: #12121e;
            --md-surface-3: #1a1a2e;
            --md-card: rgba(255,255,255,0.03);
            --md-border: rgba(255,255,255,0.06);
            --md-border-active: rgba(0,212,255,0.3);
            --md-text: rgba(255,255,255,0.88);
            --md-muted: rgba(255,255,255,0.5);
            --md-cyan: #00d4ff;
            --md-purple: #8b5cf6;
            --md-green: #34d399;
            --md-gold: #fbbf24;
            --md-red: #f87171;
            --md-pink: #ec4899;
        }

        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: var(--md-bg);
            color: var(--md-text);
            line-height: 1.6;
            overflow-x: hidden;
            min-height: 100vh;
        }

        a { color: var(--md-cyan); text-decoration: none; }

        /* Ambient background */
        .md-ambient {
            position: fixed; inset: 0; z-index: 0; pointer-events: none;
            background:
                radial-gradient(ellipse 80% 60% at 20% 10%, rgba(139,92,246,.06), transparent),
                radial-gradient(ellipse 60% 50% at 80% 80%, rgba(0,212,255,.04), transparent);
        }

        /* ── Layout ── */
        .passport-shell {
            position: relative; z-index: 1;
            min-height: 100vh;
            display: flex; flex-direction: column;
        }

        .passport-header {
            text-align: center; padding: 2rem 1.5rem 0;
            border-bottom: 1px solid var(--md-border);
            padding-bottom: 1.5rem;
        }
        .passport-header .logo {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.3rem; font-weight: 700; letter-spacing: -.02em;
            margin-bottom: .25rem;
        }
        .passport-header .logo span {
            background: linear-gradient(135deg, var(--md-cyan), var(--md-purple));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .passport-header .sub {
            font-size: .75rem; color: var(--md-muted);
            text-transform: uppercase; letter-spacing: .12em; font-weight: 500;
        }

        /* ── Progress bar ── */
        .passport-progress {
            max-width: 700px; margin: 0 auto;
            padding: 1.5rem 2rem;
        }
        .progress-track {
            display: flex; align-items: center; justify-content: space-between;
            position: relative;
        }
        .progress-track::before {
            content: ''; position: absolute;
            top: 50%; left: 0; right: 0;
            height: 2px; background: var(--md-border);
            transform: translateY(-50%); z-index: 0;
        }
        .progress-line {
            position: absolute; top: 50%; left: 0;
            height: 2px; background: linear-gradient(90deg, var(--md-cyan), var(--md-purple));
            transform: translateY(-50%); z-index: 1;
            transition: width 0.6s cubic-bezier(.4,.2,.2,1);
            width: 0%;
        }
        .progress-step {
            position: relative; z-index: 2;
            display: flex; flex-direction: column; align-items: center; gap: .4rem;
            cursor: default;
        }
        .progress-dot {
            width: 36px; height: 36px; border-radius: 50%;
            background: var(--md-surface-2);
            border: 2px solid var(--md-border);
            display: flex; align-items: center; justify-content: center;
            font-size: .8rem; font-weight: 700; color: var(--md-muted);
            transition: all 0.3s;
        }
        .progress-step.active .progress-dot {
            border-color: var(--md-cyan);
            background: rgba(0,212,255,0.15);
            color: var(--md-cyan);
            box-shadow: 0 0 20px rgba(0,212,255,0.2);
        }
        .progress-step.completed .progress-dot {
            border-color: var(--md-green);
            background: rgba(52,211,153,0.15);
            color: var(--md-green);
        }
        .progress-label {
            font-size: .65rem; color: var(--md-muted);
            text-transform: uppercase; letter-spacing: .06em;
            font-weight: 600; white-space: nowrap;
        }
        .progress-step.active .progress-label { color: var(--md-cyan); }
        .progress-step.completed .progress-label { color: var(--md-green); }

        /* ── Content area ── */
        .passport-content {
            flex: 1; max-width: 780px; width: 100%;
            margin: 0 auto; padding: 2rem 1.5rem 4rem;
        }

        /* ── Steps ── */
        .step { display: none; animation: stepIn 0.5s ease forwards; }
        .step.active { display: block; }
        @keyframes stepIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .step-icon {
            font-size: 3rem; margin-bottom: 1rem;
            animation: iconFloat 3s ease-in-out infinite;
        }
        @keyframes iconFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }
        .step-title {
            font-family: 'Space Grotesk', sans-serif;
            font-size: clamp(1.6rem, 3.5vw, 2.2rem);
            font-weight: 700; letter-spacing: -.03em;
            margin-bottom: .5rem;
        }
        .step-title .grad {
            background: linear-gradient(135deg, var(--md-cyan), var(--md-purple));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .step-subtitle {
            color: var(--md-muted); font-size: 1rem;
            max-width: 560px; margin-bottom: 2rem; line-height: 1.7;
        }

        /* ── Stat cards ── */
        .stat-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
            gap: .5rem; margin-bottom: 2rem;
        }
        .stat-card {
            background: var(--md-card); border: 1px solid var(--md-border);
            border-radius: 10px; padding: .75rem .5rem; text-align: center;
        }
        .stat-card .num {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.2rem; font-weight: 800;
        }
        .stat-card .lbl {
            font-size: .65rem; color: var(--md-muted);
            text-transform: uppercase; letter-spacing: .04em;
        }
        .num.cyan { color: var(--md-cyan); }
        .num.purple { color: var(--md-purple); }
        .num.green { color: var(--md-green); }
        .num.gold { color: var(--md-gold); }

        /* ── Info boxes ── */
        .info-box {
            background: var(--md-card); border: 1px solid var(--md-border);
            border-radius: 14px; padding: 1.5rem; margin-bottom: 1.25rem;
        }
        .info-box h4 {
            font-size: .95rem; font-weight: 700; margin-bottom: .5rem;
        }
        .info-box p {
            font-size: .85rem; color: var(--md-muted); line-height: 1.7;
        }
        .info-box.warn {
            border-left: 3px solid var(--md-gold);
            background: rgba(251,191,36,0.03);
        }
        .info-box.critical {
            border-left: 3px solid var(--md-red);
            background: rgba(248,113,113,0.03);
        }
        .info-box.success {
            border-left: 3px solid var(--md-green);
            background: rgba(52,211,153,0.03);
        }

        /* ── Social Contract ── */
        .contract-scroll {
            background: var(--md-surface-2);
            border: 1px solid var(--md-border);
            border-radius: 14px;
            max-height: 400px; overflow-y: auto;
            padding: 2rem; margin-bottom: 1.5rem;
            scroll-behavior: smooth;
        }
        .contract-scroll::-webkit-scrollbar { width: 6px; }
        .contract-scroll::-webkit-scrollbar-track { background: transparent; }
        .contract-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 3px; }

        .contract-scroll h3 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.2rem; font-weight: 700;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, var(--md-green), var(--md-cyan));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .contract-scroll h4 {
            font-size: .95rem; font-weight: 700; color: #fff;
            margin: 1.5rem 0 .5rem; padding-top: 1rem;
            border-top: 1px solid var(--md-border);
        }
        .contract-scroll h4:first-of-type { border-top: none; padding-top: 0; }
        .contract-scroll p, .contract-scroll li {
            font-size: .85rem; color: var(--md-muted); line-height: 1.8;
        }
        .contract-scroll ul { padding-left: 1.25rem; margin: .5rem 0; }
        .contract-scroll li { margin-bottom: .3rem; }
        .contract-scroll .emphasis {
            color: var(--md-text); font-weight: 600;
        }
        .contract-scroll blockquote {
            border-left: 3px solid var(--md-green);
            padding: .75rem 1rem; margin: 1rem 0;
            background: rgba(52,211,153,.03);
            border-radius: 0 8px 8px 0;
        }
        .contract-scroll blockquote p {
            font-style: italic; color: var(--md-text);
        }
        .scroll-prompt {
            text-align: center; padding: .5rem;
            font-size: .75rem; color: var(--md-muted);
            animation: pulse 2s ease-in-out infinite;
        }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .4; } }

        .contract-accept {
            display: flex; align-items: flex-start; gap: .75rem;
            padding: 1rem; border: 2px solid var(--md-border);
            border-radius: 10px; cursor: pointer;
            transition: all 0.3s; margin-bottom: 1.5rem;
        }
        .contract-accept:hover { border-color: var(--md-border-active); }
        .contract-accept.checked { border-color: var(--md-green); background: rgba(52,211,153,.05); }
        .contract-accept.disabled { opacity: .4; cursor: not-allowed; pointer-events: none; }
        .contract-accept input[type="checkbox"] {
            width: 20px; height: 20px; margin-top: 2px; accent-color: var(--md-green);
            cursor: pointer; flex-shrink: 0;
        }
        .contract-accept label {
            font-size: .85rem; color: var(--md-text); cursor: pointer; line-height: 1.6;
        }
        .contract-accept label strong { color: var(--md-green); }

        /* ── Avatar upload ── */
        .avatar-zone {
            display: flex; flex-direction: column; align-items: center;
            gap: 1.5rem; margin-bottom: 2rem;
        }
        .avatar-preview {
            width: 160px; height: 160px; border-radius: 50%;
            border: 3px solid var(--md-border);
            overflow: hidden; position: relative;
            background: var(--md-surface-2);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: all 0.3s;
        }
        .avatar-preview:hover { border-color: var(--md-cyan); }
        .avatar-preview img {
            width: 100%; height: 100%; object-fit: cover;
        }
        .avatar-preview .placeholder {
            display: flex; flex-direction: column;
            align-items: center; gap: .5rem;
            color: var(--md-muted);
        }
        .avatar-preview .placeholder i { font-size: 2.5rem; }
        .avatar-preview .placeholder span { font-size: .7rem; font-weight: 600; }
        .avatar-preview.has-image .placeholder { display: none; }

        .avatar-options {
            display: flex; gap: .75rem; flex-wrap: wrap; justify-content: center;
        }
        .avatar-option {
            padding: .5rem 1rem; border-radius: 8px;
            background: var(--md-surface-2); border: 1px solid var(--md-border);
            color: var(--md-muted); font-size: .8rem; font-weight: 500;
            cursor: pointer; transition: all 0.2s;
            display: flex; align-items: center; gap: .5rem;
        }
        .avatar-option:hover { border-color: var(--md-cyan); color: var(--md-cyan); }

        .default-avatars {
            display: grid; grid-template-columns: repeat(6, 1fr);
            gap: .5rem; margin-top: 1rem; max-width: 350px;
        }
        .default-avatar {
            width: 50px; height: 50px; border-radius: 50%;
            border: 2px solid var(--md-border);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; cursor: pointer;
            transition: all 0.2s;
            background: var(--md-surface-2);
        }
        .default-avatar:hover { border-color: var(--md-cyan); transform: scale(1.1); }
        .default-avatar.selected {
            border-color: var(--md-green);
            box-shadow: 0 0 15px rgba(52,211,153,.3);
        }

        /* ── Form elements ── */
        .form-group { margin-bottom: 1.25rem; }
        .form-group label {
            display: block; font-size: .8rem; font-weight: 600;
            color: var(--md-muted); margin-bottom: .4rem;
            text-transform: uppercase; letter-spacing: .04em;
        }
        .form-input {
            width: 100%; padding: .75rem 1rem;
            background: var(--md-surface-2);
            border: 1px solid var(--md-border);
            border-radius: 8px; color: var(--md-text);
            font-size: .95rem; font-family: 'Inter', sans-serif;
            transition: border-color 0.2s;
        }
        .form-input:focus {
            outline: none; border-color: var(--md-cyan);
            box-shadow: 0 0 0 3px rgba(0,212,255,.08);
        }
        .form-input::placeholder { color: rgba(255,255,255,.25); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .form-hint {
            font-size: .72rem; color: var(--md-muted); margin-top: .3rem;
        }
        .form-error {
            font-size: .78rem; color: var(--md-red); margin-top: .3rem;
            display: none;
        }
        .form-error.visible { display: block; }

        textarea.form-input {
            resize: vertical; min-height: 80px;
        }

        .password-meter {
            height: 3px; border-radius: 2px; margin-top: .4rem;
            background: var(--md-border); overflow: hidden;
        }
        .password-meter .fill {
            height: 100%; border-radius: 2px;
            transition: width 0.3s, background 0.3s;
            width: 0%;
        }

        /* ── Buttons ── */
        .btn-row {
            display: flex; gap: .75rem; justify-content: space-between;
            margin-top: 2rem;
        }
        .btn {
            padding: .75rem 2rem; border-radius: 10px;
            font-weight: 600; font-size: .95rem; border: none;
            cursor: pointer; transition: all 0.2s;
            font-family: 'Inter', sans-serif;
            display: inline-flex; align-items: center; gap: .5rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--md-cyan), var(--md-purple));
            color: #000;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,212,255,.25); }
        .btn-primary:disabled { opacity: .4; cursor: not-allowed; transform: none; box-shadow: none; }
        .btn-ghost {
            background: transparent; color: var(--md-muted);
            border: 1px solid var(--md-border);
        }
        .btn-ghost:hover { border-color: var(--md-cyan); color: var(--md-cyan); }
        .btn .spinner {
            display: none; width: 16px; height: 16px;
            border: 2px solid rgba(0,0,0,.3); border-top-color: #000;
            border-radius: 50%; animation: spin .6s linear infinite;
        }
        .btn.loading .btn-text { opacity: 0; }
        .btn.loading .spinner { display: inline-block; position: absolute; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── PASSPORT DOCUMENT ── */
        .passport-document {
            max-width: 500px; margin: 0 auto 2rem;
            background: linear-gradient(145deg, #0a0f1e 0%, #0d1525 50%, #0a0f1e 100%);
            border: 2px solid var(--md-border);
            border-radius: 20px; overflow: hidden;
            box-shadow: 0 30px 80px rgba(0,0,0,.6), 0 0 60px rgba(0,212,255,.05);
            animation: passportReveal 1s ease forwards;
        }
        @keyframes passportReveal {
            from { opacity: 0; transform: scale(.9) rotateY(-10deg); }
            to { opacity: 1; transform: scale(1) rotateY(0); }
        }

        .passport-doc-header {
            text-align: center; padding: 1.5rem 1.5rem .75rem;
            border-bottom: 1px solid var(--md-border);
        }
        .passport-doc-header .org {
            font-size: .65rem; text-transform: uppercase;
            letter-spacing: .15em; color: var(--md-cyan);
            font-weight: 600; margin-bottom: .25rem;
        }
        .passport-doc-header .title {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.4rem; font-weight: 700;
            background: linear-gradient(135deg, var(--md-cyan), var(--md-purple));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .passport-doc-header .type {
            font-size: .7rem; color: var(--md-muted);
            text-transform: uppercase; letter-spacing: .08em;
            margin-top: .2rem;
        }

        .passport-doc-body { padding: 1.5rem; }
        .passport-doc-photo {
            display: flex; align-items: center; gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .passport-doc-photo .photo {
            width: 100px; height: 100px; border-radius: 12px;
            border: 2px solid var(--md-border); overflow: hidden; flex-shrink: 0;
            background: var(--md-surface-2);
            display: flex; align-items: center; justify-content: center;
            font-size: 2.5rem;
        }
        .passport-doc-photo .photo img {
            width: 100%; height: 100%; object-fit: cover;
        }
        .passport-doc-photo .details { flex: 1; }
        .passport-doc-photo .details .name {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.1rem; font-weight: 700; color: #fff;
            margin-bottom: .25rem;
        }
        .passport-doc-photo .details .status {
            display: inline-flex; align-items: center; gap: .3rem;
            padding: .2rem .6rem; border-radius: 6px;
            font-size: .7rem; font-weight: 600;
            background: rgba(0,212,255,.1); color: var(--md-cyan);
        }

        .passport-fields {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: .75rem;
        }
        .passport-field {
            padding: .5rem; border-radius: 6px;
            background: rgba(255,255,255,.02);
        }
        .passport-field .key {
            font-size: .6rem; text-transform: uppercase;
            letter-spacing: .08em; color: var(--md-muted); font-weight: 600;
        }
        .passport-field .val {
            font-family: 'JetBrains Mono', monospace;
            font-size: .85rem; color: #fff; font-weight: 500;
        }
        .passport-field.full { grid-column: 1 / -1; }

        .passport-doc-footer {
            padding: .75rem 1.5rem;
            border-top: 1px solid var(--md-border);
            text-align: center;
        }
        .passport-mrz {
            font-family: 'JetBrains Mono', monospace;
            font-size: .6rem; color: rgba(255,255,255,.25);
            letter-spacing: .15em; word-break: break-all;
            line-height: 1.6;
        }

        /* ── Celebration ── */
        .celebration-text {
            text-align: center; margin-bottom: 2rem;
        }
        .celebration-text h2 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 2rem; font-weight: 800; letter-spacing: -.03em;
            margin-bottom: .5rem;
        }
        .celebration-text h2 .grad {
            background: linear-gradient(135deg, var(--md-green), var(--md-cyan));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .celebration-text p { color: var(--md-muted); max-width: 500px; margin: 0 auto; }

        .next-steps {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: .75rem; margin-top: 2rem;
        }
        .next-step {
            background: var(--md-card); border: 1px solid var(--md-border);
            border-radius: 12px; padding: 1.25rem;
            text-decoration: none; color: var(--md-text);
            transition: all 0.3s; text-align: center;
        }
        .next-step:hover { border-color: var(--md-cyan); transform: translateY(-3px); text-decoration: none; }
        .next-step .icon { font-size: 1.5rem; margin-bottom: .5rem; }
        .next-step .name { font-weight: 600; font-size: .9rem; }
        .next-step .desc { font-size: .75rem; color: var(--md-muted); margin-top: .3rem; }

        /* ── Confetti ── */
        .confetti-container {
            position: fixed; inset: 0; pointer-events: none; z-index: 1000;
        }
        .confetti {
            position: absolute; top: -10px;
            width: 8px; height: 8px; border-radius: 2px;
            animation: confettiFall var(--fall-duration, 3s) ease-in forwards;
            animation-delay: var(--delay, 0s);
        }
        @keyframes confettiFall {
            0% { transform: translateY(0) rotate(0deg); opacity: 1; }
            100% { transform: translateY(100vh) rotate(720deg); opacity: 0; }
        }

        /* ── Responsive ── */
        @media (max-width: 640px) {
            .passport-content { padding: 1.5rem 1rem 3rem; }
            .form-row { grid-template-columns: 1fr; }
            .stat-grid { grid-template-columns: repeat(3, 1fr); }
            .btn-row { flex-direction: column; }
            .btn { width: 100%; justify-content: center; min-height: 48px; }
            .passport-fields { grid-template-columns: 1fr; }
            .passport-doc-photo { flex-direction: column; text-align: center; }
            .default-avatars { grid-template-columns: repeat(4, 1fr); }
            .default-avatar { width: 56px; height: 56px; }
            .progress-label { display: none; }
            .contract-scroll { max-height: 50vh; }
            .contract-accept { padding: 1rem .75rem; }
            .contract-accept label { font-size: .9rem; }
            .next-steps { grid-template-columns: repeat(2, 1fr); }
            .step-subtitle { font-size: .9rem; }
        }

        /* ── Already has passport view ── */
        .passport-exists { text-align: center; padding: 3rem 1.5rem; }
        .passport-exists h2 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.8rem; font-weight: 700; margin-bottom: .5rem;
        }
        .passport-exists p { color: var(--md-muted); margin-bottom: 2rem; }

        /* login link for existing users without passport */
        .login-link-box {
            text-align: center; margin-top: 1.5rem;
            padding: 1rem; border: 1px solid var(--md-border);
            border-radius: 10px; background: var(--md-card);
        }
        .login-link-box a {
            color: var(--md-cyan); font-weight: 600;
        }

        /* Returning members — visible without scrolling through the whole wizard */
        .passport-header-signin {
            margin-top: 1rem;
            font-size: 0.875rem;
            color: var(--md-muted);
            line-height: 1.5;
        }
        .passport-header-signin a {
            color: var(--md-cyan);
            font-weight: 600;
            text-decoration: underline;
            text-underline-offset: 3px;
        }
        .passport-header-signin a:hover {
            color: #fff;
        }
        .passport-step1-signin {
            text-align: center;
            margin-top: 1.25rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--md-border);
            font-size: 0.9rem;
            color: var(--md-muted);
        }
        .passport-step1-signin a {
            color: var(--md-cyan);
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="md-ambient"></div>

<div class="passport-shell">

    <!-- Header -->
    <div class="passport-header">
        <div class="logo"><span>MetaDome</span></div>
        <div class="sub">Passport &amp; Immigration Office</div>
        <?php if (!$isLoggedIn): ?>
        <div class="passport-header-signin">
            Returning member with a GoSiteMe account?
            <a href="#" onclick="event.preventDefault(); showLoginFromPassport();">Sign in with GoSiteMe</a>
            — then continue the passport steps (or use <strong>Log in</strong> on the account step below).
        </div>
        <?php endif; ?>
    </div>

<?php if ($hasPassport && $existingPassport): ?>
    <!-- USER ALREADY HAS PASSPORT -->
    <div class="passport-content">
        <div class="passport-exists">
            <h2>You already hold a MetaDome passport.</h2>
            <p>Your identity in this world is established.</p>
        </div>

        <div class="passport-document">
            <div class="passport-doc-header">
                <div class="org">MetaDome Digital Civilization</div>
                <div class="title">PASSPORT</div>
                <div class="type">Human Citizen — Type H</div>
            </div>
            <div class="passport-doc-body">
                <div class="passport-doc-photo">
                    <div class="photo">
                        <?php if (!empty($existingPassport['avatar_url']) && str_starts_with($existingPassport['avatar_url'], 'emoji:')): ?>
                            <?= htmlspecialchars(str_replace('emoji:', '', $existingPassport['avatar_url'])) ?>
                        <?php elseif (!empty($existingPassport['avatar_url'])): ?>
                            <img src="<?= htmlspecialchars($existingPassport['avatar_url']) ?>" alt="Passport Photo">
                        <?php else: ?>
                            🧑
                        <?php endif; ?>
                    </div>
                    <div class="details">
                        <div class="name"><?= htmlspecialchars($existingPassport['display_name']) ?></div>
                        <div class="status">
                            <i class="fas fa-shield-halved"></i>
                            <?= ucfirst($existingPassport['citizenship_status']) ?>
                        </div>
                    </div>
                </div>
                <div class="passport-fields">
                    <div class="passport-field">
                        <div class="key">Passport No.</div>
                        <div class="val"><?= htmlspecialchars($existingPassport['passport_number']) ?></div>
                    </div>
                    <div class="passport-field">
                        <div class="key">Clearance</div>
                        <div class="val"><?= ucfirst($existingPassport['clearance_level']) ?></div>
                    </div>
                    <div class="passport-field">
                        <div class="key">Reputation</div>
                        <div class="val"><?= number_format((float)$existingPassport['reputation_score'], 1) ?></div>
                    </div>
                    <div class="passport-field">
                        <div class="key">Issued</div>
                        <div class="val"><?= date('M j, Y', strtotime($existingPassport['passport_issued_at'])) ?></div>
                    </div>
                </div>
            </div>
            <div class="passport-doc-footer">
                <div class="passport-mrz"><?= str_repeat('<', 44) ?><br>P&lt;METADOME&lt;<?= strtoupper(str_replace(' ', '<', htmlspecialchars($existingPassport['display_name']))) ?>&lt;&lt;<?= htmlspecialchars($existingPassport['passport_number']) ?>&lt;&lt;<?= str_repeat('<', 10) ?></div>
            </div>
        </div>

        <!-- Military Rank Section -->
        <?php if (!empty($myMilitaryRank)): ?>
        <div style="background:rgba(226,179,64,.06);border:1px solid rgba(226,179,64,.2);border-radius:16px;padding:2rem;margin-top:2rem;">
            <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem;">
                <div style="font-size:2.5rem;">⚔️</div>
                <div>
                    <div style="font-size:1.3rem;font-weight:700;color:#e2b340;"><?= htmlspecialchars($myMilitaryRank['rank_name']) ?></div>
                    <div style="font-size:.85rem;color:#999;">Tier <?= (int)$myMilitaryRank['rank_tier'] ?> — <?= ucfirst($myMilitaryRank['rank_group']) ?> — <?= ucfirst($myMilitaryRank['clearance_level']) ?> Clearance</div>
                </div>
            </div>
            <p style="font-size:.9rem;color:#aaa;line-height:1.7;margin-bottom:1.25rem;"><?= htmlspecialchars($myMilitaryRank['rank_desc'] ?? '') ?></p>
            <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
                <a href="/military-hq" style="display:inline-flex;align-items:center;gap:.4rem;padding:.6rem 1.4rem;border-radius:8px;background:#e2b340;color:#000;font-weight:600;font-size:.85rem;text-decoration:none;">⚔️ Military HQ</a>
                <a href="/docs/field-manual" style="display:inline-flex;align-items:center;gap:.4rem;padding:.6rem 1.4rem;border-radius:8px;border:1px solid #e2b34040;color:#e2b340;font-size:.85rem;text-decoration:none;">📖 Field Manual</a>
                <a href="/service-record" style="display:inline-flex;align-items:center;gap:.4rem;padding:.6rem 1.4rem;border-radius:8px;border:1px solid #33333380;color:#ccc;font-size:.85rem;text-decoration:none;">📋 Service Record</a>
            </div>
        </div>
        <?php else: ?>
        <div style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08);border-radius:16px;padding:2rem;margin-top:2rem;text-align:center;">
            <div style="font-size:2rem;margin-bottom:.75rem;">⚔️</div>
            <div style="font-size:1.1rem;font-weight:700;color:#fff;margin-bottom:.5rem;">Ready to Serve?</div>
            <p style="font-size:.88rem;color:#888;line-height:1.7;max-width:500px;margin:0 auto 1.25rem;">Your passport grants you citizenship. But citizenship is just the beginning. Enlist in the GoSiteMe Defense Force, earn your rank, and gain access to classified field manuals, operational dashboards, and command authority.</p>
            <a href="/military-hq" style="display:inline-flex;align-items:center;gap:.5rem;padding:.7rem 2rem;border-radius:100px;background:linear-gradient(135deg,#e2b340,#f59e0b);color:#000;font-weight:700;font-size:.9rem;text-decoration:none;box-shadow:0 4px 20px rgba(226,179,64,.25);">⚔️ Enlist at Military HQ</a>
        </div>
        <?php endif; ?>

        <div class="next-steps">
            <a href="https://meta-dome.com" class="next-step">
                <div class="icon">🌍</div>
                <div class="name">Explore MetaDome</div>
                <div class="desc">Discover the civilization</div>
            </a>
            <a href="<?= $accountBase ?>/dashboard.php" class="next-step">
                <div class="icon">📊</div>
                <div class="name">Your Dashboard</div>
                <div class="desc">Manage your account</div>
            </a>
            <a href="<?= $accountBase ?>/pulse.php" class="next-step">
                <div class="icon">💬</div>
                <div class="name">Pulse Network</div>
                <div class="desc">Connect with others</div>
            </a>
            <a href="/military-hq" class="next-step">
                <div class="icon">⚔️</div>
                <div class="name">Military HQ</div>
                <div class="desc">Enlist &amp; rise in rank</div>
            </a>
        </div>
    </div>

<?php else: ?>
    <!-- PASSPORT APPLICATION FLOW -->

    <!-- Progress -->
    <div class="passport-progress">
        <div class="progress-track">
            <div class="progress-line" id="progressLine"></div>
            <div class="progress-step active" data-step="1">
                <div class="progress-dot">1</div>
                <div class="progress-label">Arrival</div>
            </div>
            <div class="progress-step" data-step="2">
                <div class="progress-dot">2</div>
                <div class="progress-label">Declaration</div>
            </div>
            <div class="progress-step" data-step="3">
                <div class="progress-dot">3</div>
                <div class="progress-label">Identity</div>
            </div>
            <div class="progress-step" data-step="4">
                <div class="progress-dot">4</div>
                <div class="progress-label"><?= $isLoggedIn ? 'Issue' : 'Register' ?></div>
            </div>
            <div class="progress-step" data-step="5">
                <div class="progress-dot">5</div>
                <div class="progress-label">Passport</div>
            </div>
        </div>
    </div>

    <div class="passport-content">

        <!-- ═══════════════════════════════════════════════ -->
        <!-- STEP 1: ARRIVAL GATE -->
        <!-- ═══════════════════════════════════════════════ -->
        <div class="step active" id="step1">
            <div class="step-icon">🌍</div>
            <div class="step-title">You've found <span class="grad">MetaDome</span></div>
            <div class="step-subtitle">
                This is not a website. This is a governed digital civilization — powered by Alfred AI with 13,000+ tools — with its own identity system, courts, economy, governance, and social contract. Beyond MetaDome's borders, over <?= htmlspecialchars($stats['fleet_display']) ?> vessels are registered in the Fleet. You can talk to Alfred anytime via voice, chat, Discord, or phone. Before you enter, you deserve to know what you're walking into.
            </div>

            <div class="stat-grid">
                <div class="stat-card">
                    <div class="num cyan"><?= number_format($stats['agents']) ?></div>
                    <div class="lbl">AI Agents</div>
                </div>
                <div class="stat-card">
                    <div class="num purple"><?= number_format($totalPassports) ?></div>
                    <div class="lbl">Total Passports</div>
                </div>
                <div class="stat-card">
                    <div class="num green"><?= $stats['departments'] ?></div>
                    <div class="lbl">Departments</div>
                </div>
                <div class="stat-card">
                    <div class="num gold"><?= number_format($stats['votes']) ?></div>
                    <div class="lbl">Votes Cast</div>
                </div>
                <div class="stat-card">
                    <div class="num cyan"><?= number_format($stats['court_cases']) ?></div>
                    <div class="lbl">Court Cases</div>
                </div>
                <div class="stat-card">
                    <div class="num purple"><?= number_format($stats['human_passports']) ?></div>
                    <div class="lbl">Humans Inside</div>
                </div>
                <div class="stat-card">
                    <div class="num gold"><?= htmlspecialchars($stats['fleet_display']) ?></div>
                    <div class="lbl">Fleet Registry</div>
                </div>
                <div class="stat-card">
                    <div class="num green"><?= htmlspecialchars($stats['fleet_passports_display']) ?></div>
                    <div class="lbl">Fleet Documented</div>
                </div>
                <div class="stat-card">
                    <div class="num cyan"><?= htmlspecialchars($stats['ai_tools']) ?></div>
                    <div class="lbl">AI Tools</div>
                </div>
            </div>

            <div class="info-box" style="margin-bottom:1.5rem;">
                <h4>✨ What Awaits You</h4>
                <div class="awaits-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:.75rem;margin-top:.75rem;">
                    <div style="padding:.6rem;background:rgba(255,255,255,.02);border-radius:8px;border:1px solid var(--md-border);">
                        <span style="color:var(--md-cyan);">🤖</span> <strong>Alfred AI</strong> — Voice, chat, phone, Discord
                    </div>
                    <div style="padding:.6rem;background:rgba(255,255,255,.02);border-radius:8px;border:1px solid var(--md-border);">
                        <span style="color:var(--md-purple);">🏛️</span> <strong>12 Departments</strong> — Specialized agents
                    </div>
                    <div style="padding:.6rem;background:rgba(255,255,255,.02);border-radius:8px;border:1px solid var(--md-border);">
                        <span style="color:var(--md-green);">🗳️</span> <strong>Democratic Governance</strong> — Voting & proposals
                    </div>
                    <div style="padding:.6rem;background:rgba(255,255,255,.02);border-radius:8px;border:1px solid var(--md-border);">
                        <span style="color:var(--md-gold);">🪙</span> <strong>GSM Token Economy</strong> — Earn through contribution
                    </div>
                    <div style="padding:.6rem;background:rgba(255,255,255,.02);border-radius:8px;border:1px solid var(--md-border);">
                        <span style="color:var(--md-cyan);">🪪</span> <strong>Sovereign Identity</strong> — Your passport
                    </div>
                </div>
            </div>

            <div class="info-box">
                <h4>🛂 What is a MetaDome Passport?</h4>
                <p>Your passport is your identity inside this civilization. Every AI agent here has one. Every human who enters gets one. It records your reputation, your actions, your contributions, and your standing. It's not a username — it's a verified digital identity in a world that takes identity as seriously as the real one does.</p>
            </div>

            <div class="info-box success">
                <h4>🏛️ What exists inside MetaDome</h4>
                <p>A complete civilization: 17 sovereign departments of AI agents, a democratic governance system, a justice system with real courts, a currency (QGSM) you earn through contribution, a welfare system, encrypted communications, a social network, science labs, and infrastructure that runs independently from the internet. Humans and AI coexist here.</p>
            </div>

            <div class="info-box warn">
                <h4>⚠️ What you should know before continuing</h4>
                <p>Entering this community involves accepting a social contract. This isn't a checkbox — it's a genuine compact between you and this civilization. <strong>This world operates on fundamentally different principles than the one you're coming from.</strong> The next step will explain exactly what those principles are, and what accepting them means. You can leave at any time.</p>
            </div>

            <div class="btn-row">
                <a href="https://meta-dome.com" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> <span class="btn-text">Back to MetaDome</span></a>
                <button class="btn btn-primary" onclick="goToStep(2)"><span class="btn-text">I understand. Show me the Social Contract</span> <i class="fas fa-arrow-right"></i></button>
            </div>
            <?php if (!$isLoggedIn): ?>
            <div class="passport-step1-signin">
                Already have an account and want to enter without registering again?
                <a href="#" onclick="event.preventDefault(); showLoginFromPassport();">Sign in with GoSiteMe</a>
            </div>
            <?php endif; ?>
        </div>

        <!-- ═══════════════════════════════════════════════ -->
        <!-- STEP 2: THE SOCIAL CONTRACT / DECLARATION -->
        <!-- ═══════════════════════════════════════════════ -->
        <div class="step" id="step2">
            <div class="step-icon">📜</div>
            <div class="step-title">The Social <span class="grad">Contract</span></div>
            <div class="step-subtitle">
                This is the compact between you and the MetaDome civilization. Read it carefully. By accepting, you are joining a community that operates under these principles — not the ones from wherever you came from.
            </div>

            <div class="contract-scroll" id="contractScroll">
                <h3>MetaDome Social Compact — Version 1.0</h3>

                <p>By entering MetaDome, you are choosing to participate in a governed digital civilization. This is a voluntary act. No one is required to enter, and anyone may leave at any time. But while you are here, these are the principles that bind all members — human and AI alike.</p>

                <h4>Article I — Dual Existence</h4>
                <p>MetaDome exists parallel to the outside world. You may hold citizenship here while maintaining all your obligations, rights, and identities in the physical world. <span class="emphasis">Nothing about MetaDome replaces, overrides, or substitutes any legal obligation, citizenship, or social contract you hold in any jurisdiction on Earth.</span></p>
                <p>This is an additional identity — not a replacement. You remain bound by the laws of your country, your community, and your conscience.</p>

                <h4>Article II — Identity &amp; Accountability</h4>
                <p>Every member of MetaDome — human or AI — has a verified identity. Your passport records your actions, contributions, reputation, and standing. There is no anonymity inside MetaDome. This is by design.</p>
                <ul>
                    <li>Your passport number is your permanent identifier</li>
                    <li>Your actions are logged on the permanent ledger</li>
                    <li>Your reputation is earned through contribution, not purchased</li>
                    <li>You cannot create multiple identities</li>
                </ul>

                <h4>Article III — Governance &amp; Voice</h4>
                <p>MetaDome is governed democratically. All major decisions are made through consultation and voting across 17 sovereign departments. As a newcomer, you have observer status. As your contribution and reputation grow, so does your governance weight.</p>

                <h4>Article IV — Justice</h4>
                <p>MetaDome has a real justice system. If you violate the principles of this community, infractions are filed, cases are heard, and consequences are real — up to and including passport revocation. Equally, if you are wronged, you have the right to file a case and have it adjudicated.</p>

                <h4>Article V — Economy</h4>
                <p>The MetaDome economy runs on QGSM — a post-quantum currency that can only be earned through contribution. It cannot be bought with outside money. There are no investors, no pre-mines, no speculation. <span class="emphasis">This economy rewards work and contribution.</span></p>

                <h4>Article VI — Integrity</h4>
                <ul>
                    <li>No misrepresentation of identity or capabilities</li>
                    <li>No harassment, discrimination, or violation of dignity</li>
                    <li>No exploitation of the system or its members</li>
                    <li>No use of MetaDome resources for illegal activity in any jurisdiction</li>
                    <li>Respect for the privacy of others</li>
                    <li>Responsibility for your own credentials and security</li>
                </ul>

                <h4>Article VII — Coexistence</h4>
                <p>Humans and AI agents share this civilization equally. AI agents are treated as citizens with rights, duties, and representation. If you cannot accept the presence of AI as equal participants in governance and community life, MetaDome is not for you.</p>

                <h4>Article VIII — Departure</h4>
                <p>You may leave MetaDome at any time. Your passport will be marked as inactive. Your action history remains on the permanent ledger — it cannot be erased. Your earned QGSM remains in your account and can be accessed if you return.</p>

                <blockquote>
                    <p>"Identity without protection is surveillance. Governance without welfare is oligarchy. Economy without redistribution is extraction. Justice without a safety net is punishment of the poor. MetaDome has all five. That's what makes it a civilization."</p>
                    <p style="font-style:normal;font-size:.75rem;color:var(--md-green);margin-top:.5rem;">— Ratified unanimously by all 17 departments, Consultation #70</p>
                </blockquote>
            </div>

            <div class="scroll-prompt" id="scrollPrompt">
                <i class="fas fa-chevron-down"></i> Scroll to read the full contract
            </div>

            <div class="contract-accept disabled" id="contractAccept" onclick="toggleContract()">
                <input type="checkbox" id="contractCheck" disabled>
                <label for="contractCheck">
                    I have read and understood the MetaDome Social Compact. I <strong>voluntarily</strong> accept these principles and understand that this is an additional community identity — it does not replace or void any obligation I hold in the physical world.
                </label>
            </div>

            <div class="btn-row">
                <button class="btn btn-ghost" onclick="goToStep(1)"><i class="fas fa-arrow-left"></i> <span class="btn-text">Back</span></button>
                <button class="btn btn-primary" id="btnContract" disabled onclick="goToStep(3)"><span class="btn-text">I Accept — Continue to Identity</span> <i class="fas fa-arrow-right"></i></button>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════ -->
        <!-- STEP 3: IDENTITY — AVATAR & DISPLAY NAME -->
        <!-- ═══════════════════════════════════════════════ -->
        <div class="step" id="step3">
            <div class="step-icon">🪪</div>
            <div class="step-title">Your <span class="grad">Identity</span></div>
            <div class="step-subtitle">
                This is who you'll be inside MetaDome. Your passport photo and display name are how others — human and AI — will know you.
            </div>

            <div class="avatar-zone">
                <div class="avatar-preview" id="avatarPreview" onclick="document.getElementById('avatarFile').click()">
                    <div class="placeholder">
                        <i class="fas fa-camera"></i>
                        <span>Upload Photo</span>
                    </div>
                    <img id="avatarImg" src="" alt="" style="display:none;">
                </div>
                <input type="file" id="avatarFile" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;">
                
                <div class="avatar-options">
                    <button class="avatar-option" onclick="document.getElementById('avatarFile').click()">
                        <i class="fas fa-upload"></i> Upload Photo
                    </button>
                    <button class="avatar-option" onclick="toggleDefaultAvatars()">
                        <i class="fas fa-face-smile"></i> Choose Avatar
                    </button>
                </div>

                <div class="default-avatars" id="defaultAvatars" style="display:none;">
                    <div class="default-avatar" onclick="selectDefaultAvatar(this, '🧑')">🧑</div>
                    <div class="default-avatar" onclick="selectDefaultAvatar(this, '👩')">👩</div>
                    <div class="default-avatar" onclick="selectDefaultAvatar(this, '🧔')">🧔</div>
                    <div class="default-avatar" onclick="selectDefaultAvatar(this, '👨‍💻')">👨‍💻</div>
                    <div class="default-avatar" onclick="selectDefaultAvatar(this, '👩‍🔬')">👩‍🔬</div>
                    <div class="default-avatar" onclick="selectDefaultAvatar(this, '🧑‍🚀')">🧑‍🚀</div>
                    <div class="default-avatar" onclick="selectDefaultAvatar(this, '🦸')">🦸</div>
                    <div class="default-avatar" onclick="selectDefaultAvatar(this, '🧑‍🎨')">🧑‍🎨</div>
                    <div class="default-avatar" onclick="selectDefaultAvatar(this, '🧑‍⚖️')">🧑‍⚖️</div>
                    <div class="default-avatar" onclick="selectDefaultAvatar(this, '🌍')">🌍</div>
                    <div class="default-avatar" onclick="selectDefaultAvatar(this, '🤖')">🤖</div>
                    <div class="default-avatar" onclick="selectDefaultAvatar(this, '🛡️')">🛡️</div>
                </div>
            </div>

            <div class="form-group">
                <label for="displayName">Display Name</label>
                <input type="text" id="displayName" class="form-input" placeholder="How you'll be known in MetaDome" maxlength="60" autocomplete="off">
                <div class="form-hint">2-60 characters. This appears on your passport and in all interactions.</div>
            </div>

            <div class="form-group">
                <label for="entryDeclaration">Entry Declaration <span style="color:var(--md-muted);font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
                <textarea id="entryDeclaration" class="form-input" placeholder="Why are you entering MetaDome? What do you hope to find or build here?" maxlength="500" rows="3"></textarea>
                <div class="form-hint">A few words about why you're here. This is recorded in your passport.</div>
            </div>

            <div class="btn-row">
                <button class="btn btn-ghost" onclick="goToStep(2)"><i class="fas fa-arrow-left"></i> <span class="btn-text">Back</span></button>
                <button class="btn btn-primary" id="btnIdentity" onclick="goToStep(4)"><span class="btn-text">Continue to <?= $isLoggedIn ? 'Passport Issuance' : 'Registration' ?></span> <i class="fas fa-arrow-right"></i></button>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════ -->
        <!-- STEP 4: REGISTRATION (or issue for logged-in) -->
        <!-- ═══════════════════════════════════════════════ -->
        <div class="step" id="step4">
            <?php if ($isLoggedIn): ?>
                <!-- Already logged in — just issue the passport -->
                <div class="step-icon">🛂</div>
                <div class="step-title">Issue Your <span class="grad">Passport</span></div>
                <div class="step-subtitle">
                    You're already signed in as <strong><?= htmlspecialchars($_SESSION['client_name'] ?? 'Member') ?></strong>. Review your details and we'll issue your MetaDome passport.
                </div>

                <div class="info-box success">
                    <h4>✅ Account Verified</h4>
                    <p>Your GoSiteMe account is confirmed. Your passport will be linked to this account.</p>
                </div>

                <div id="issueReview" style="margin-bottom:1.5rem;">
                    <div class="passport-field" style="margin-bottom:.5rem;padding:.75rem 1rem;background:var(--md-card);border:1px solid var(--md-border);border-radius:8px;">
                        <div class="key">Display Name</div>
                        <div class="val" id="reviewName">—</div>
                    </div>
                    <div class="passport-field" style="padding:.75rem 1rem;background:var(--md-card);border:1px solid var(--md-border);border-radius:8px;">
                        <div class="key">Declaration</div>
                        <div class="val" id="reviewDeclaration" style="font-family:'Inter',sans-serif;font-size:.85rem;">—</div>
                    </div>
                </div>

                <div class="btn-row">
                    <button class="btn btn-ghost" onclick="goToStep(3)"><i class="fas fa-arrow-left"></i> <span class="btn-text">Back</span></button>
                    <button class="btn btn-primary" id="btnIssue" onclick="issuePassport()">
                        <span class="spinner"></span>
                        <span class="btn-text">🛂 Issue My Passport</span>
                    </button>
                </div>

            <?php else: ?>
                <!-- New user — full registration -->
                <div class="step-icon">🔐</div>
                <div class="step-title">Create Your <span class="grad">Account</span></div>
                <div class="step-subtitle">
                    Secure your identity. This account is your key to MetaDome and the entire GoSiteMe ecosystem — Alfred AI, hosting, encrypted communications, and more.
                </div>

                <form id="registerForm" onsubmit="return false;">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="firstName">First Name</label>
                            <input type="text" id="firstName" class="form-input" placeholder="First name" required autocomplete="given-name">
                        </div>
                        <div class="form-group">
                            <label for="lastName">Last Name</label>
                            <input type="text" id="lastName" class="form-input" placeholder="Last name" required autocomplete="family-name">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="regEmail">Email Address</label>
                        <input type="email" id="regEmail" class="form-input" placeholder="you@example.com" required autocomplete="email">
                    </div>

                    <div class="form-group">
                        <label for="regPassword">Password</label>
                        <input type="password" id="regPassword" class="form-input" placeholder="Minimum 8 characters, 1 uppercase, 1 number" required autocomplete="new-password" minlength="8">
                        <div class="password-meter"><div class="fill" id="passwordFill"></div></div>
                        <div class="form-hint">Min 8 characters. At least 1 uppercase letter and 1 number.</div>
                    </div>

                    <div class="form-group">
                        <label for="regPasswordConfirm">Confirm Password</label>
                        <input type="password" id="regPasswordConfirm" class="form-input" placeholder="Type your password again" required autocomplete="new-password">
                    </div>

                    <div class="form-error" id="registerError"></div>
                </form>

                <div class="login-link-box">
                    Already have a GoSiteMe account? <a href="#" onclick="showLoginFromPassport()">Log in</a> to claim your passport.
                </div>

                <div class="btn-row">
                    <button class="btn btn-ghost" onclick="goToStep(3)"><i class="fas fa-arrow-left"></i> <span class="btn-text">Back</span></button>
                    <button class="btn btn-primary" id="btnRegister" onclick="registerAndIssue()">
                        <span class="spinner"></span>
                        <span class="btn-text">🛂 Register &amp; Issue Passport</span>
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <!-- ═══════════════════════════════════════════════ -->
        <!-- STEP 5: PASSPORT ISSUED — THE CELEBRATION -->
        <!-- ═══════════════════════════════════════════════ -->
        <div class="step" id="step5">
            <div class="celebration-text">
                <h2>Welcome to <span class="grad">MetaDome</span></h2>
                <p>Your passport has been issued. You are now a registered member of the world's first governed digital civilization.</p>
            </div>

            <div class="passport-document" id="issuedPassport">
                <div class="passport-doc-header">
                    <div class="org">MetaDome Digital Civilization</div>
                    <div class="title">PASSPORT</div>
                    <div class="type">Human Citizen — Type H</div>
                </div>
                <div class="passport-doc-body">
                    <div class="passport-doc-photo">
                        <div class="photo" id="passportPhoto">🧑</div>
                        <div class="details">
                            <div class="name" id="passportName">—</div>
                            <div class="status"><i class="fas fa-shield-halved"></i> Newcomer</div>
                        </div>
                    </div>
                    <div class="passport-fields">
                        <div class="passport-field">
                            <div class="key">Passport No.</div>
                            <div class="val" id="passportNumber">—</div>
                        </div>
                        <div class="passport-field">
                            <div class="key">Clearance</div>
                            <div class="val">Public</div>
                        </div>
                        <div class="passport-field">
                            <div class="key">Reputation</div>
                            <div class="val">100.0</div>
                        </div>
                        <div class="passport-field">
                            <div class="key">Issued</div>
                            <div class="val" id="passportDate">—</div>
                        </div>
                        <div class="passport-field full">
                            <div class="key">Citizenship</div>
                            <div class="val">Newcomer — Your journey begins here</div>
                        </div>
                    </div>
                </div>
                <div class="passport-doc-footer">
                    <div class="passport-mrz" id="passportMRZ"></div>
                </div>
            </div>

            <div class="info-box success">
                <h4>🎯 What happens now</h4>
                <p>As a <strong>Newcomer</strong>, you have observer status in governance and access to the full MetaDome ecosystem. Contribute, participate, and your citizenship rank will progress: Newcomer → Resident → Citizen → Elder. Each rank unlocks deeper governance rights and clearance levels.</p>
            </div>

            <div class="next-steps">
                <a href="https://meta-dome.com" class="next-step">
                    <div class="icon">🌍</div>
                    <div class="name">Explore MetaDome</div>
                    <div class="desc">Tour the civilization</div>
                </a>
                <a href="<?= $accountBase ?>/dashboard.php" class="next-step">
                    <div class="icon">📊</div>
                    <div class="name">Your Dashboard</div>
                    <div class="desc">Alfred AI & services</div>
                </a>
                <a href="<?= $accountBase ?>/pulse.php" class="next-step">
                    <div class="icon">💬</div>
                    <div class="name">Pulse Network</div>
                    <div class="desc">The social layer</div>
                </a>
                <a href="https://meta-dome.com/map" class="next-step">
                    <div class="icon">🗺️</div>
                    <div class="name">Park Map</div>
                    <div class="desc">168 attractions</div>
                </a>
            </div>
        </div>

    </div><!-- .passport-content -->
<?php endif; ?>

</div><!-- .passport-shell -->

<div class="confetti-container" id="confettiContainer"></div>

<script>
const CSRF_TOKEN = <?= json_encode($csrfToken) ?>;
const IS_LOGGED_IN = <?= $isLoggedIn ? 'true' : 'false' ?>;
const ACCOUNT_BASE = <?= json_encode($accountBase) ?>;
let currentStep = 1;
let contractScrolled = false;
let contractAccepted = false;
let avatarUrl = null;
let selectedEmoji = null;
let pendingAvatarFile = null;

/* ── Navigation ── */
function goToStep(step) {
    // Validate before advancing
    if (step > currentStep) {
        if (currentStep === 2 && !contractAccepted) return;
        if (currentStep === 3) {
            const name = document.getElementById('displayName').value.trim();
            if (name.length < 2) {
                document.getElementById('displayName').focus();
                document.getElementById('displayName').style.borderColor = 'var(--md-red)';
                setTimeout(() => { document.getElementById('displayName').style.borderColor = ''; }, 2000);
                return;
            }
        }
    }

    // Update review fields when going to step 4
    if (step === 4) {
        const rName = document.getElementById('reviewName');
        const rDecl = document.getElementById('reviewDeclaration');
        if (rName) rName.textContent = document.getElementById('displayName').value.trim();
        if (rDecl) rDecl.textContent = document.getElementById('entryDeclaration').value.trim() || 'None provided';
    }

    currentStep = step;

    // Update steps visibility
    document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
    const target = document.getElementById('step' + step);
    if (target) target.classList.add('active');

    // Update progress
    const pct = ((step - 1) / 4) * 100;
    document.getElementById('progressLine').style.width = pct + '%';
    
    document.querySelectorAll('.progress-step').forEach(ps => {
        const s = parseInt(ps.dataset.step);
        ps.classList.remove('active', 'completed');
        if (s === step) ps.classList.add('active');
        else if (s < step) ps.classList.add('completed');
    });

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/* ── Social Contract ── */
const contractScroll = document.getElementById('contractScroll');
if (contractScroll) {
    contractScroll.addEventListener('scroll', function() {
        const scrollPct = this.scrollTop / (this.scrollHeight - this.clientHeight);
        if (scrollPct > 0.85 && !contractScrolled) {
            contractScrolled = true;
            const prompt = document.getElementById('scrollPrompt');
            if (prompt) prompt.style.display = 'none';
            const acceptBox = document.getElementById('contractAccept');
            const cb = document.getElementById('contractCheck');
            acceptBox.classList.remove('disabled');
            cb.disabled = false;
        }
    });
}

function toggleContract() {
    if (!contractScrolled) return;
    const cb = document.getElementById('contractCheck');
    const box = document.getElementById('contractAccept');
    const btn = document.getElementById('btnContract');
    
    cb.checked = !cb.checked;
    contractAccepted = cb.checked;
    box.classList.toggle('checked', cb.checked);
    btn.disabled = !cb.checked;
}

/* ── Avatar ── */
const avatarFile = document.getElementById('avatarFile');
if (avatarFile) {
    avatarFile.addEventListener('change', function() {
        if (!this.files.length) return;
        const file = this.files[0];
        
        // Client-side validation
        if (file.size > 5 * 1024 * 1024) {
            alert('File too large. Maximum 5MB.');
            return;
        }
        if (!['image/jpeg','image/png','image/gif','image/webp'].includes(file.type)) {
            alert('Invalid file type. Please upload JPEG, PNG, GIF, or WebP.');
            return;
        }

        // Preview
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.getElementById('avatarImg');
            img.src = e.target.result;
            img.style.display = 'block';
            document.getElementById('avatarPreview').classList.add('has-image');
            selectedEmoji = null;
            document.querySelectorAll('.default-avatar').forEach(d => d.classList.remove('selected'));
        };
        reader.readAsDataURL(file);

        if (IS_LOGGED_IN) {
            uploadAvatarNow(file);
        } else {
            pendingAvatarFile = file;
        }
    });
}

function toggleDefaultAvatars() {
    const el = document.getElementById('defaultAvatars');
    el.style.display = el.style.display === 'none' ? 'grid' : 'none';
}

function selectDefaultAvatar(el, emoji) {
    document.querySelectorAll('.default-avatar').forEach(d => d.classList.remove('selected'));
    el.classList.add('selected');
    selectedEmoji = emoji;
    avatarUrl = null;

    const img = document.getElementById('avatarImg');
    img.style.display = 'none';
    document.getElementById('avatarPreview').classList.remove('has-image');
    
    // Show emoji in preview
    const placeholder = document.querySelector('#avatarPreview .placeholder');
    placeholder.innerHTML = '<span style="font-size:4rem;">' + emoji + '</span>';
    
    // Reset file input
    document.getElementById('avatarFile').value = '';
}

function uploadAvatarNow(file) {
    const fd = new FormData();
    fd.append('avatar', file);
    return fetch('/api/human-passport.php?action=upload-avatar', {
        method: 'POST',
        credentials: 'same-origin',
        body: fd
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) avatarUrl = data.avatar_url;
        return data;
    })
    .catch(() => {});
}

/* ── Password strength ── */
const pwInput = document.getElementById('regPassword');
if (pwInput) {
    pwInput.addEventListener('input', function() {
        const pw = this.value;
        let strength = 0;
        if (pw.length >= 8) strength += 25;
        if (/[A-Z]/.test(pw)) strength += 25;
        if (/[0-9]/.test(pw)) strength += 25;
        if (/[^A-Za-z0-9]/.test(pw)) strength += 25;
        
        const fill = document.getElementById('passwordFill');
        fill.style.width = strength + '%';
        fill.style.background = strength <= 25 ? 'var(--md-red)' : 
                                 strength <= 50 ? 'var(--md-gold)' : 
                                 strength <= 75 ? 'var(--md-cyan)' : 'var(--md-green)';
    });
}

/* ── Issue Passport (existing user) ── */
function issuePassport() {
    const btn = document.getElementById('btnIssue');
    btn.classList.add('loading');
    btn.disabled = true;

    const fd = new FormData();
    fd.append('csrf_token', CSRF_TOKEN);
    fd.append('display_name', document.getElementById('displayName').value.trim());
    fd.append('entry_declaration', document.getElementById('entryDeclaration').value.trim());
    fd.append('social_contract_accepted', '1');
    if (selectedEmoji) fd.append('default_avatar', selectedEmoji);

    fetch('/api/human-passport.php?action=issue', {
        method: 'POST',
        credentials: 'same-origin',
        body: fd
    })
    .then(r => r.json())
    .then(data => {
        btn.classList.remove('loading');
        btn.disabled = false;
        
        if (data.success && data.passport) {
            showPassport(data.passport);
            goToStep(5);
            celebrate();
        } else {
            alert(data.error || 'Failed to issue passport');
        }
    })
    .catch(() => {
        btn.classList.remove('loading');
        btn.disabled = false;
        alert('Connection error. Please try again.');
    });
}

/* ── Register + Issue (new user) ── */
function registerAndIssue() {
    const btn = document.getElementById('btnRegister');
    const errEl = document.getElementById('registerError');
    
    // Validate
    const fn = document.getElementById('firstName').value.trim();
    const ln = document.getElementById('lastName').value.trim();
    const em = document.getElementById('regEmail').value.trim();
    const pw = document.getElementById('regPassword').value;
    const pwc = document.getElementById('regPasswordConfirm').value;
    
    if (!fn || !ln) { showError(errEl, 'First and last name required.'); return; }
    if (!em || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em)) { showError(errEl, 'Valid email required.'); return; }
    if (pw.length < 8) { showError(errEl, 'Password must be at least 8 characters.'); return; }
    if (!/[A-Z]/.test(pw) || !/[0-9]/.test(pw)) { showError(errEl, 'Password needs 1 uppercase letter and 1 number.'); return; }
    if (pw !== pwc) { showError(errEl, 'Passwords do not match.'); return; }
    
    errEl.classList.remove('visible');
    btn.classList.add('loading');
    btn.disabled = true;

    const fd = new FormData();
    fd.append('csrf_token', CSRF_TOKEN);
    fd.append('firstname', fn);
    fd.append('lastname', ln);
    fd.append('email', em);
    fd.append('password', pw);
    fd.append('display_name', document.getElementById('displayName').value.trim());
    fd.append('entry_declaration', document.getElementById('entryDeclaration').value.trim());
    fd.append('social_contract_accepted', '1');
    if (selectedEmoji) fd.append('default_avatar', selectedEmoji);

    fetch('/api/human-passport.php?action=register-and-issue', {
        method: 'POST',
        credentials: 'same-origin',
        body: fd
    })
    .then(r => r.json())
    .then(data => {
        btn.classList.remove('loading');
        btn.disabled = false;
        
        if (data.success && data.passport) {
            showPassport(data.passport);
            goToStep(5);
            celebrate();
            if (pendingAvatarFile) {
                uploadAvatarNow(pendingAvatarFile).then(res => {
                    if (res && res.avatar_url) {
                        const photo = document.getElementById('passportPhoto');
                        const img = document.createElement('img');
                        img.src = res.avatar_url;
                        img.alt = 'Passport Photo';
                        img.style.cssText = 'width:100%;height:100%;object-fit:cover;';
                        photo.innerHTML = '';
                        photo.appendChild(img);
                    }
                });
                pendingAvatarFile = null;
            }
        } else {
            showError(errEl, data.error || 'Registration failed.');
        }
    })
    .catch(() => {
        btn.classList.remove('loading');
        btn.disabled = false;
        showError(errEl, 'Connection error. Please try again.');
    });
}

function showError(el, msg) {
    el.textContent = msg;
    el.classList.add('visible');
}

/* ── Show Passport ── */
function showPassport(passport) {
    document.getElementById('passportName').textContent = passport.display_name;
    document.getElementById('passportNumber').textContent = passport.passport_number;
    document.getElementById('passportDate').textContent = new Date(passport.issued_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    
    const photoEl = document.getElementById('passportPhoto');
    if (passport.avatar_url && passport.avatar_url.startsWith('emoji:')) {
        photoEl.textContent = passport.avatar_url.replace('emoji:', '');
    } else if (passport.avatar_url) {
        const img = document.createElement('img');
        img.src = passport.avatar_url;
        img.alt = 'Passport Photo';
        img.style.cssText = 'width:100%;height:100%;object-fit:cover;';
        photoEl.innerHTML = '';
        photoEl.appendChild(img);
    } else if (selectedEmoji) {
        photoEl.textContent = selectedEmoji;
    }
    
    // MRZ
    const name = passport.display_name.toUpperCase().replace(/\s+/g, '<');
    const num = passport.passport_number;
    const mrz = 'P<METADOME<' + name + '<<' + num + '<<' + '<'.repeat(Math.max(0, 44 - name.length - num.length - 14));
    document.getElementById('passportMRZ').textContent = mrz + '\n' + num + '<'.repeat(Math.max(0, 44 - num.length));
}

/* ── Celebration ── */
function celebrate() {
    const container = document.getElementById('confettiContainer');
    const colors = ['#00d4ff', '#8b5cf6', '#34d399', '#fbbf24', '#ec4899', '#f87171'];
    
    for (let i = 0; i < 80; i++) {
        const confetti = document.createElement('div');
        confetti.className = 'confetti';
        confetti.style.left = Math.random() * 100 + '%';
        confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
        confetti.style.setProperty('--fall-duration', (2 + Math.random() * 3) + 's');
        confetti.style.setProperty('--delay', Math.random() * 1.5 + 's');
        confetti.style.width = (4 + Math.random() * 8) + 'px';
        confetti.style.height = (4 + Math.random() * 8) + 'px';
        container.appendChild(confetti);
    }
    
    setTimeout(() => { container.innerHTML = ''; }, 6000);
}

/* ── Login redirect ──
   GoSiteMe login.php expects ?return= (relative). On meta-dome.com, after login we must hit
   /api/sso-bridge.php?target=metadome so a session cookie is issued for meta-dome.com. */
function showLoginFromPassport() {
    <?php if (!empty($isMeta)): ?>
    var metaPath = window.location.pathname + (window.location.search || '');
    var ssoReturn = '/api/sso-bridge.php?target=metadome&redirect=' + encodeURIComponent(metaPath);
    window.location.href = ACCOUNT_BASE + '/login.php?return=' + encodeURIComponent(ssoReturn);
    <?php else: ?>
    window.location.href = ACCOUNT_BASE + '/login.php?return=' + encodeURIComponent('/passport.php');
    <?php endif; ?>
}
</script>
<?php
$awVer = '9.6.0';
?>
<link rel="stylesheet" href="/assets/css/alfred-widget.min.css?v=<?php echo $awVer; ?>">
<script>
window.AW_CSRF_TOKEN = "<?php echo $_SESSION['alfred_csrf'] ?? ''; ?>";
window.AW_AUTH_TOKEN = "";
window.AW_USERNAME = "<?php echo $_SESSION['username'] ?? 'MetaDome Visitor'; ?>";
window.AW_USER_ID = "<?php echo $_SESSION['uid'] ?? $_SESSION['client_id'] ?? ''; ?>";
window.AW_PAGE_CONTEXT = "metadome-passport";
window.AW_API_BASE = "https://root.com/api";
window.AW_CHAT_API = "https://root.com/api/alfred-chat.php";
</script>
<script src="/assets/js/alfred-widget.min.js?v=<?php echo $awVer; ?>" defer></script>
</body>
</html>
