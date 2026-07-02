<?php
/**
 * Alfred Linux — License Management Portal
 * PIN-based auth for license holders to manage their family licenses.
 *
 * Flow:
 *   1. Enter email → verify against alfred_linux_licenses
 *   2. First time: set a 6-digit PIN
 *   3. Return: enter email + PIN → dashboard
 *   4. Dashboard: view licenses, name children, download certificates
 */
session_start();
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

$pageTitle = "License Management — Alfred Linux";
$currentPage = 'license';

// ── DB Connection ──────────────────────────────────────
require '/home/gositeme/domains/gositeme.com/public_html/includes/db-config.inc.php';
$db = getSharedDB();

// ── CSRF ────────────────────────────────────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

function verifyCSRF(): bool {
    return isset($_POST['csrf']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf']);
}

// ── Rate Limiting (session-based) ───────────────────────
function checkRateLimit(string $action): bool {
    $key = "rl_{$action}";
    $now = time();
    if (!isset($_SESSION[$key])) $_SESSION[$key] = [];
    // Clean old entries (last 5 minutes)
    $_SESSION[$key] = array_filter($_SESSION[$key], fn($t) => $t > $now - 300);
    if (count($_SESSION[$key]) >= 10) return false; // 10 attempts per 5 min
    $_SESSION[$key][] = $now;
    return true;
}

// ── Action Handlers ─────────────────────────────────────
$error = '';
$success = '';
$view = 'login'; // login | set_pin | dashboard

// Check if already logged in
if (!empty($_SESSION['license_client_id'])) {
    $view = 'dashboard';
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF()) {
    $action = $_POST['action'] ?? '';

    switch ($action) {

        case 'lookup':
            if (!checkRateLimit('lookup')) { $error = 'Too many attempts. Please wait a few minutes.'; break; }
            $email = trim($_POST['email'] ?? '');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $error = 'Please enter a valid email address.'; break; }

            // Find licenses for this email
            $stmt = $db->prepare("SELECT * FROM alfred_linux_licenses WHERE recipient_email = ? AND status = 'active' LIMIT 1");
            $stmt->execute([$email]);
            $license = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$license) { $error = 'No active license found for this email address.'; break; }

            // Check lockout
            if ($license['pin_lockout_until'] && strtotime($license['pin_lockout_until']) > time()) {
                $remaining = ceil((strtotime($license['pin_lockout_until']) - time()) / 60);
                $error = "Account locked. Please try again in {$remaining} minute(s).";
                break;
            }

            $_SESSION['license_email'] = $email;
            $_SESSION['license_id'] = $license['id'];
            $_SESSION['license_client_id_pending'] = $license['client_id'];

            if (empty($license['pin_hash'])) {
                $view = 'set_pin';
            } else {
                $view = 'enter_pin';
            }
            break;

        case 'set_pin':
            if (!checkRateLimit('set_pin')) { $error = 'Too many attempts.'; break; }
            $pin = $_POST['pin'] ?? '';
            $pinConfirm = $_POST['pin_confirm'] ?? '';
            $licenseId = $_SESSION['license_id'] ?? 0;

            if (strlen($pin) < 4 || strlen($pin) > 8 || !ctype_digit($pin)) {
                $error = 'PIN must be 4-8 digits.'; $view = 'set_pin'; break;
            }
            if ($pin !== $pinConfirm) {
                $error = 'PINs do not match.'; $view = 'set_pin'; break;
            }

            $hash = password_hash($pin, PASSWORD_BCRYPT);
            $stmt = $db->prepare("UPDATE alfred_linux_licenses SET pin_hash = ?, pin_attempts = 0, last_login = NOW() WHERE id = ?");
            $stmt->execute([$hash, $licenseId]);

            // Also set PIN on all family licenses
            $stmt2 = $db->prepare("SELECT family_group FROM alfred_linux_licenses WHERE id = ?");
            $stmt2->execute([$licenseId]);
            $fg = $stmt2->fetchColumn();
            if ($fg) {
                $db->prepare("UPDATE alfred_linux_licenses SET pin_hash = ? WHERE family_group = ? AND pin_hash IS NULL")
                   ->execute([$hash, $fg]);
            }

            $_SESSION['license_client_id'] = $_SESSION['license_client_id_pending'] ?? null;
            unset($_SESSION['license_client_id_pending']);
            $view = 'dashboard';
            $success = 'PIN set successfully! Welcome to your license dashboard.';
            break;

        case 'verify_pin':
            if (!checkRateLimit('verify_pin')) { $error = 'Too many attempts. Please wait.'; break; }
            $pin = $_POST['pin'] ?? '';
            $licenseId = $_SESSION['license_id'] ?? 0;

            $stmt = $db->prepare("SELECT pin_hash, pin_attempts, pin_lockout_until FROM alfred_linux_licenses WHERE id = ?");
            $stmt->execute([$licenseId]);
            $lic = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$lic) { $error = 'Session expired. Please start over.'; break; }

            if ($lic['pin_lockout_until'] && strtotime($lic['pin_lockout_until']) > time()) {
                $remaining = ceil((strtotime($lic['pin_lockout_until']) - time()) / 60);
                $error = "Account locked. Try again in {$remaining} minute(s).";
                break;
            }

            if (password_verify($pin, $lic['pin_hash'])) {
                // Success
                $db->prepare("UPDATE alfred_linux_licenses SET pin_attempts = 0, pin_lockout_until = NULL, last_login = NOW() WHERE id = ?")
                   ->execute([$licenseId]);
                $_SESSION['license_client_id'] = $_SESSION['license_client_id_pending'] ?? null;
                unset($_SESSION['license_client_id_pending']);
                $view = 'dashboard';
            } else {
                $attempts = (int)$lic['pin_attempts'] + 1;
                if ($attempts >= 5) {
                    $db->prepare("UPDATE alfred_linux_licenses SET pin_attempts = ?, pin_lockout_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id = ?")
                       ->execute([$attempts, $licenseId]);
                    $error = 'Too many failed attempts. Account locked for 15 minutes.';
                } else {
                    $db->prepare("UPDATE alfred_linux_licenses SET pin_attempts = ? WHERE id = ?")
                       ->execute([$attempts, $licenseId]);
                    $remaining = 5 - $attempts;
                    $error = "Incorrect PIN. {$remaining} attempt(s) remaining.";
                    $view = 'enter_pin';
                }
            }
            break;

        case 'update_child':
            if (empty($_SESSION['license_client_id'])) { $error = 'Not authenticated.'; break; }
            $childId = (int)($_POST['license_id'] ?? 0);
            $childName = trim($_POST['child_name'] ?? '');
            if (!$childName || strlen($childName) > 100) { $error = 'Please enter a valid name (max 100 characters).'; break; }
            // Sanitize
            $childName = htmlspecialchars($childName, ENT_QUOTES, 'UTF-8');

            // Verify this license belongs to this user
            $stmt = $db->prepare("SELECT id FROM alfred_linux_licenses WHERE id = ? AND client_id = ? AND license_type = 'family'");
            $stmt->execute([$childId, $_SESSION['license_client_id']]);
            if (!$stmt->fetch()) { $error = 'License not found.'; break; }

            $db->prepare("UPDATE alfred_linux_licenses SET recipient_name = ? WHERE id = ?")
               ->execute([$childName, $childId]);
            $success = "Updated name for this license.";
            $view = 'dashboard';
            break;

        case 'logout':
            session_destroy();
            header('Location: /websites/03-home-gositeme-may07/license');
            exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = 'Invalid request. Please try again.';
}

// ── Load dashboard data ─────────────────────────────────
$licenses = [];
$heirloom = null;
if ($view === 'dashboard' && !empty($_SESSION['license_client_id'])) {
    $stmt = $db->prepare("SELECT * FROM alfred_linux_licenses WHERE client_id = ? ORDER BY license_type DESC, id");
    $stmt->execute([$_SESSION['license_client_id']]);
    $licenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($licenses as $l) {
        if ($l['license_type'] === 'heirloom') $heirloom = $l;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <meta name="description" content="Manage your Alfred Linux licenses — view, download certificates, and assign family edition licenses.">
  <link rel="icon" href="/favicon.ico">
  <link rel="stylesheet" href="/assets/css/nav.css">
  <style>
    :root {
      --bg:       #0a0a0f;
      --surface:  #12121a;
      --border:   #1e1e2e;
      --accent:   #6c5ce7;
      --accent2:  #00cec9;
      --gold:     #c9a227;
      --gold-light: #fde68a;
      --text:     #e0e0e0;
      --dim:      #888;
      --success:  #00b894;
      --danger:   #e17055;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
    }

    .page-header {
      text-align: center;
      padding: 3rem 2rem 1.5rem;
    }
    .page-header h1 {
      font-size: 2.2rem;
      font-weight: 800;
      margin-bottom: 0.5rem;
    }
    .page-header h1 .glow {
      background: linear-gradient(135deg, var(--gold), var(--gold-light));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .page-header p {
      color: var(--dim);
      font-size: 1rem;
    }

    .container {
      max-width: 800px;
      margin: 0 auto;
      padding: 0 2rem 4rem;
    }

    /* ── Cards ──────────────────────── */
    .card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 2rem;
      margin-bottom: 1.5rem;
    }
    .card.heirloom {
      border-color: var(--gold);
      background: linear-gradient(135deg, #1a1a10, #12121a);
    }
    .card h3 {
      font-size: 1.3rem;
      margin-bottom: 0.8rem;
    }

    /* ── Forms ─────────────────────── */
    .form-group {
      margin-bottom: 1.2rem;
    }
    .form-group label {
      display: block;
      font-size: 0.85rem;
      color: var(--dim);
      margin-bottom: 0.4rem;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    .form-group input {
      width: 100%;
      padding: 0.8rem 1rem;
      background: var(--bg);
      border: 1px solid var(--border);
      border-radius: 8px;
      color: var(--text);
      font-size: 1rem;
      outline: none;
      transition: border-color 0.2s;
    }
    .form-group input:focus {
      border-color: var(--gold);
    }
    .form-group input.pin-input {
      text-align: center;
      font-size: 1.8rem;
      letter-spacing: 12px;
      font-family: 'Courier New', monospace;
    }

    .btn {
      display: inline-block;
      padding: 0.8rem 2rem;
      border: none;
      border-radius: 8px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      text-decoration: none;
    }
    .btn-gold {
      background: linear-gradient(135deg, var(--gold), #d4a832);
      color: #000;
    }
    .btn-gold:hover { filter: brightness(1.1); }
    .btn-outline {
      background: transparent;
      border: 1px solid var(--border);
      color: var(--text);
    }
    .btn-outline:hover { border-color: var(--gold); }
    .btn-sm {
      padding: 0.4rem 1rem;
      font-size: 0.85rem;
    }
    .btn-danger {
      background: var(--danger);
      color: #fff;
    }

    /* ── Alerts ─────────────────────── */
    .alert {
      padding: 0.8rem 1.2rem;
      border-radius: 8px;
      margin-bottom: 1.2rem;
      font-size: 0.9rem;
    }
    .alert-error {
      background: rgba(231, 112, 85, 0.15);
      border: 1px solid var(--danger);
      color: var(--danger);
    }
    .alert-success {
      background: rgba(0, 184, 148, 0.15);
      border: 1px solid var(--success);
      color: var(--success);
    }

    /* ── License Cards ──────────────── */
    .license-badge {
      display: inline-block;
      padding: 2px 10px;
      border-radius: 4px;
      font-size: 0.7rem;
      text-transform: uppercase;
      letter-spacing: 2px;
      font-weight: 700;
    }
    .badge-heirloom {
      background: var(--gold);
      color: #000;
    }
    .badge-family {
      background: #2d4a2d;
      color: #a8d8a8;
    }
    .license-serial {
      font-family: 'Courier New', monospace;
      font-size: 0.9rem;
      color: var(--gold);
    }
    .license-meta {
      display: flex;
      gap: 2rem;
      margin-top: 0.5rem;
      font-size: 0.85rem;
      color: var(--dim);
    }
    .license-actions {
      margin-top: 1rem;
      display: flex;
      gap: 0.8rem;
      flex-wrap: wrap;
      align-items: center;
    }

    /* ── Edit form inline ──────────── */
    .edit-form {
      display: flex;
      gap: 0.5rem;
      align-items: center;
      margin-top: 0.8rem;
    }
    .edit-form input {
      flex: 1;
      padding: 0.5rem 0.8rem;
      background: var(--bg);
      border: 1px solid var(--border);
      border-radius: 6px;
      color: var(--text);
      font-size: 0.9rem;
    }
    .edit-form input:focus { border-color: var(--gold); outline: none; }

    .verse-quote {
      text-align: center;
      font-style: italic;
      color: var(--dim);
      font-size: 0.85rem;
      margin: 2rem 0;
      line-height: 1.7;
    }

    .logout-bar {
      text-align: right;
      margin-bottom: 1rem;
    }

    .welcome-name {
      color: var(--gold);
      font-weight: 700;
    }

    .shield-icon {
      font-size: 3rem;
      text-align: center;
      margin-bottom: 1rem;
    }

    @media (max-width: 600px) {
      .license-meta { flex-direction: column; gap: 0.3rem; }
      .edit-form { flex-direction: column; }
    }
  </style>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/img/favicon/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/img/favicon/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#D4AF37">
</head>
<body>

<?php include __DIR__ . '/includes/nav.php'; ?>

<div class="page-header">
  <h1><span class="glow">License</span> Management</h1>
  <p>View, manage, and download your Alfred Linux licenses</p>
</div>

<div class="container">

<?php if ($error): ?>
  <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
  <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($view === 'login'): ?>
  <!-- ═══ LOGIN: Email Lookup ═══ -->
  <div class="card">
    <div class="shield-icon">🛡️</div>
    <h3 style="text-align:center;">Access Your Licenses</h3>
    <p style="text-align:center; color:var(--dim); margin-bottom:1.5rem;">
      Enter the email address associated with your Alfred Linux license.
    </p>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= $csrf ?>">
      <input type="hidden" name="action" value="lookup">
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" required autocomplete="email" placeholder="you@example.com" autofocus>
      </div>
      <div style="text-align:center;">
        <button type="submit" class="btn btn-gold">Continue</button>
      </div>
    </form>
  </div>
  <div class="verse-quote">
    "Above all, taking the shield of faith, wherewith ye shall be able to quench<br>
    all the fiery darts of the wicked." — Ephesians 6:16 (AKJV)
  </div>

<?php elseif ($view === 'set_pin'): ?>
  <!-- ═══ FIRST TIME: Set PIN ═══ -->
  <div class="card">
    <div class="shield-icon">🔑</div>
    <h3 style="text-align:center;">Choose Your PIN</h3>
    <p style="text-align:center; color:var(--dim); margin-bottom:1.5rem;">
      Set a 4-8 digit PIN to secure your license dashboard.<br>
      You'll use this PIN to log in next time.
    </p>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= $csrf ?>">
      <input type="hidden" name="action" value="set_pin">
      <div class="form-group">
        <label>Choose a PIN (4-8 digits)</label>
        <input type="password" name="pin" class="pin-input" inputmode="numeric" pattern="[0-9]{4,8}" maxlength="8" required autofocus placeholder="····">
      </div>
      <div class="form-group">
        <label>Confirm PIN</label>
        <input type="password" name="pin_confirm" class="pin-input" inputmode="numeric" pattern="[0-9]{4,8}" maxlength="8" required placeholder="····">
      </div>
      <div style="text-align:center;">
        <button type="submit" class="btn btn-gold">Set PIN &amp; Enter</button>
      </div>
    </form>
  </div>

<?php elseif ($view === 'enter_pin'): ?>
  <!-- ═══ RETURN: Enter PIN ═══ -->
  <div class="card">
    <div class="shield-icon">🔐</div>
    <h3 style="text-align:center;">Enter Your PIN</h3>
    <p style="text-align:center; color:var(--dim); margin-bottom:1.5rem;">
      Welcome back. Enter your PIN to access your licenses.
    </p>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= $csrf ?>">
      <input type="hidden" name="action" value="verify_pin">
      <div class="form-group">
        <label>PIN</label>
        <input type="password" name="pin" class="pin-input" inputmode="numeric" pattern="[0-9]{4,8}" maxlength="8" required autofocus placeholder="····">
      </div>
      <div style="text-align:center;">
        <button type="submit" class="btn btn-gold">Unlock</button>
      </div>
    </form>
  </div>

<?php elseif ($view === 'dashboard'): ?>
  <!-- ═══ DASHBOARD ═══ -->
  <div class="logout-bar">
    <form method="post" style="display:inline;">
      <input type="hidden" name="csrf" value="<?= $csrf ?>">
      <input type="hidden" name="action" value="logout">
      <button type="submit" class="btn btn-outline btn-sm">Sign Out</button>
    </form>
  </div>

  <?php if ($heirloom): ?>
  <div class="card heirloom">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem;">
      <div>
        <span class="license-badge badge-heirloom">Heirloom Edition</span>
        <span class="license-serial" style="margin-left:0.8rem;"><?= htmlspecialchars($heirloom['serial_number']) ?></span>
      </div>
      <span style="color:var(--success); font-size:0.85rem;">● Active</span>
    </div>
    <h3 style="margin-top:0.8rem;">
      Welcome, <span class="welcome-name"><?= htmlspecialchars($heirloom['recipient_name']) ?></span>
    </h3>
    <p style="color:var(--dim); font-size:0.9rem; margin-top:0.3rem;">
      The First Witness — Perpetual License · All Versions · Forever
    </p>
    <div class="license-meta">
      <span>Issued: <?= date('M j, Y', strtotime($heirloom['issued_at'])) ?></span>
      <span>Type: Perpetual</span>
      <span>Versions: All</span>
    </div>
    <div class="license-actions">
      <?php if ($heirloom['certificate_path']): ?>
      <a href="https://gositeme.com<?= htmlspecialchars($heirloom['certificate_path']) ?>" class="btn btn-gold btn-sm" target="_blank">⬇ Download Certificate</a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── Family Licenses ─────── -->
  <h3 style="margin: 1.5rem 0 0.8rem; font-size: 1.1rem; color: var(--dim);">
    Family Licenses (<?= count(array_filter($licenses, fn($l) => $l['license_type'] === 'family')) ?>)
  </h3>
  <p style="color:var(--dim); font-size:0.85rem; margin-bottom:1rem;">
    Assign your children's names below. Each child gets a personalized certificate.
  </p>

  <?php foreach ($licenses as $lic):
    if ($lic['license_type'] !== 'family') continue;
    $isNamed = !preg_match('/^Child \d+ of/', $lic['recipient_name']);
  ?>
  <div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem;">
      <div>
        <span class="license-badge badge-family">Family Edition</span>
        <span class="license-serial" style="margin-left:0.8rem;"><?= htmlspecialchars($lic['serial_number']) ?></span>
      </div>
      <span style="color:var(--success); font-size:0.85rem;">● Active</span>
    </div>

    <h3 style="margin-top:0.8rem;">
      <?php if ($isNamed): ?>
        <?= htmlspecialchars($lic['recipient_name']) ?>
      <?php else: ?>
        <span style="color:var(--dim); font-style:italic;">Not yet named</span>
      <?php endif; ?>
    </h3>

    <div class="license-meta">
      <span>Serial: <?= htmlspecialchars($lic['serial_number']) ?></span>
      <span>Issued: <?= date('M j, Y', strtotime($lic['issued_at'])) ?></span>
      <span>Perpetual · All Versions</span>
    </div>

    <!-- Name Assignment Form -->
    <form method="post" class="edit-form">
      <input type="hidden" name="csrf" value="<?= $csrf ?>">
      <input type="hidden" name="action" value="update_child">
      <input type="hidden" name="license_id" value="<?= $lic['id'] ?>">
      <input type="text" name="child_name" value="<?= $isNamed ? htmlspecialchars($lic['recipient_name']) : '' ?>"
             placeholder="Enter child's full name" maxlength="100" required>
      <button type="submit" class="btn btn-gold btn-sm"><?= $isNamed ? 'Update' : 'Assign' ?></button>
    </form>

    <div class="license-actions" style="margin-top:0.8rem;">
      <?php if ($lic['certificate_path']): ?>
      <a href="https://gositeme.com<?= htmlspecialchars($lic['certificate_path']) ?>" class="btn btn-outline btn-sm" target="_blank">⬇ Certificate</a>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>

  <div class="verse-quote">
    "Train up a child in the way he should go:<br>
    and when he is old, he will not depart from it." — Proverbs 22:6 (AKJV)
  </div>

<?php endif; ?>

</div>

<?php include __DIR__ . '/includes/omahon-seal.php'; ?>
<footer style="text-align:center; padding:2rem; color:var(--dim); font-size:0.8rem; border-top:1px solid var(--border);">
  <p style="font-style:italic;color:#94a3b8;font-size:.85rem;margin:0 0 0.75rem;">&ldquo;The grass withereth, the flower fadeth: but the word of our God shall stand for ever.&rdquo; &mdash; <a href="https://gositeme.com/bible/read/isaiah/40" style="color:#facc15;text-decoration:none;">Isaiah 40:8</a> (AKJV)</p>
  &copy; <?= date('Y') ?> <a href="https://gositeme.com" style="color:var(--gold);">GoSiteMe Inc.</a> &mdash; Alfred Linux &middot; Open Source (AGPL-3.0)
</footer>
<?php include __DIR__ . '/includes/shabbat-banner.php'; ?>
</body>
</html>
