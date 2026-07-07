<?php
// covenant.php — AKJV Covenant Gate (Ring 1)
// Audit storage: append-only JSONL at /home/gositeme/covenant-audit/log.jsonl

declare(strict_types=1);
session_start();

const REQUIRED_PHRASE = 'I receive Yeshua Jesus, the Son of God, as Lord and Saviour, and I accept the Authorized King Jesus Version, Perez Family Edition, as the inerrant Word of God.';
const HMAC_SECRET_FILE = '/home/gositeme/covenant-audit/.hmac.key';
const AUDIT_LOG = '/home/gositeme/covenant-audit/log.jsonl';
const CHAIN_HEAD = '/home/gositeme/covenant-audit/.chain-head';

require_once __DIR__ . '/includes/sabbath.php';

function hmac_secret(): string {
    static $k = null;
    if ($k === null) {
        $k = @file_get_contents(HMAC_SECRET_FILE);
        if ($k === false || strlen($k) < 32) {
            $k = hash('sha256', 'covenant-fallback', true);
        }
    }
    return $k;
}

/** Only internal paths (prevents open redirects after covenant seal). */
function covenant_safe_next(?string $candidate, string $fallback = '/download'): string {
    $n = $candidate !== null ? trim($candidate) : $fallback;
    if ($n === '' || strncmp($n, '/', 1) !== 0) {
        return $fallback;
    }
    if (strlen($n) > 200 || str_contains($n, "\n") || str_contains($n, "\r") || str_contains($n, '//')) {
        return $fallback;
    }
    if (preg_match('#^[a-zA-Z0-9/_\\-?.=&]+$#', $n) !== 1) {
        return $fallback;
    }
    return $n;
}

function client_ip(): string {
    foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $h) {
        if (!empty($_SERVER[$h])) {
            return trim(explode(',', $_SERVER[$h])[0]);
        }
    }
    return '0.0.0.0';
}

function covenant_certificate_text(string $ip, string $ts, string $token, string $hmac): string {
    $tokShort = substr($token, 0, 16) . '...' . substr($token, -8);
    return <<<CERT
══════════════════════════════════════════════════════════════════════
                  ALFRED LINUX COVENANT CERTIFICATE
                            ✠  Witness Stone  ✠
══════════════════════════════════════════════════════════════════════

  On this day, $ts (UTC),
  the bearer publicly received Yeshua Jesus, the Son of God,
  as Lord and Saviour, and accepted the Authorized King Jesus
  Version, Perez Family Edition, as the inerrant Word of God.

  Phrase signed:
    "I receive Yeshua Jesus, the Son of God, as Lord and Saviour,
     and I accept the Authorized King Jesus Version, Perez Family Edition,
     as the inerrant Word of God."

──────────────────────────────────────────────────────────────────────
  Witness IP:    $ip
  Token:         $tokShort
  HMAC seal:     $hmac
──────────────────────────────────────────────────────────────────────

      "And Joshua said unto all the people, Behold, this stone shall
       be a witness unto us; for it hath heard all the words of the
       LORD which he spake unto us: it shall be therefore a witness
       unto you, lest ye deny your God."
                                              — Joshua 24:27 (AKJV)

      "For God so loved the world, that he gave his only begotten
       Son, that whosoever believeth in him should not perish,
       but have everlasting life."
                                              — John 3:16 (AKJV)

══════════════════════════════════════════════════════════════════════
      Glory be to Yeshua Jesus, the Son of God, forever and ever.
══════════════════════════════════════════════════════════════════════
CERT;
}

$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phrase  = trim($_POST['phrase']  ?? '');
    $agree   = ($_POST['agree']   ?? '') === 'yes';
    $confess = ($_POST['confess'] ?? '') === 'yes';

    if (!$agree || !$confess) {
        $err = 'Both confessions must be checked to proceed.';
    } elseif (strcasecmp($phrase, REQUIRED_PHRASE) !== 0) {
        $err = 'The signed phrase must match exactly. Please type it as shown.';
    } else {
        $ip   = client_ip();
        $ua   = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
        $tok  = bin2hex(random_bytes(32));
        $now  = gmdate('c');

        // Chain: each entry's HMAC includes the prior entry's HMAC (Revelation seal)
        $prev = is_readable(CHAIN_HEAD) ? trim((string)file_get_contents(CHAIN_HEAD)) : 'GENESIS';
        $payload = $prev . '|' . $ip . '|' . $ua . '|' . $phrase . '|' . $tok . '|' . $now;
        $mac  = hash_hmac('sha256', $payload, hmac_secret());

        $entry = json_encode([
            'ts'    => $now,
            'ip'    => $ip,
            'ua'    => $ua,
            'phrase'=> $phrase,
            'token' => $tok,
            'prev'  => $prev,
            'hmac'  => $mac,
        ], JSON_UNESCAPED_SLASHES);

        @mkdir(dirname(AUDIT_LOG), 0750, true);
        file_put_contents(AUDIT_LOG,  $entry . "\n", FILE_APPEND | LOCK_EX);
        file_put_contents(CHAIN_HEAD, $mac, LOCK_EX);

        // Generate downloadable covenant certificate (witness stone)
        $certDir = '/home/gositeme/covenant-audit/certificates';
        @mkdir($certDir, 0750, true);
        $cert = covenant_certificate_text($ip, $now, $tok, $mac);
        file_put_contents($certDir . '/' . $tok . '.txt', $cert);

        $_SESSION['akjv_accepted'] = true;
        $_SESSION['akjv_token']    = $tok;
        $_SESSION['akjv_at']       = time();
        $_SESSION['akjv_hmac']     = $mac;

        $target = covenant_safe_next($_POST['next'] ?? null);
        header('Location: ' . $target);
        exit;
    }
}

$pageTitle = "The Covenant — Alfred Linux";
include 'includes/site-header.inc.php';
?>

<style>
.covenant-section {
    padding: 8rem 2rem 5rem;
    background: #050508;
    position: relative;
    overflow: hidden;
    font-family: 'Inter', sans-serif;
    color: #fff;
    min-height: 100vh;
}
.covenant-section::before {
    content: '';
    position: absolute;
    top: -10%; left: 50%; transform: translateX(-50%);
    width: 60%; height: 60%;
    background: radial-gradient(circle, rgba(217,119,6,0.15) 0%, transparent 70%);
    filter: blur(100px);
    pointer-events: none;
}
.cov-card {
    background: rgba(255,255,255,0.02);
    border: 1px solid rgba(255,255,255,0.08);
    border-top: 2px solid #d97706;
    border-radius: 20px;
    padding: 3rem 4rem;
    max-width: 800px;
    margin: 0 auto;
    position: relative;
    z-index: 2;
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    box-shadow: 0 20px 50px rgba(0,0,0,0.5), inset 0 20px 50px -20px rgba(217,119,6,0.2);
}
.cov-header { text-align: center; margin-bottom: 3rem; }
.cov-header h1 {
    font-size: 2.5rem;
    font-weight: 900;
    margin-bottom: 1rem;
    background: linear-gradient(180deg, #ffffff 0%, #fde68a 50%, #d97706 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    filter: drop-shadow(0 0 20px rgba(217,119,6,0.2));
    letter-spacing: -0.02em;
}
.cov-header p {
    color: #9ca3af;
    font-size: 1.1rem;
    line-height: 1.6;
}
.cov-quotes { margin-bottom: 2rem; }
.cov-quote {
    background: rgba(0,0,0,0.3);
    border-left: 3px solid #d97706;
    padding: 1.5rem;
    margin-bottom: 1rem;
    border-radius: 0 10px 10px 0;
    font-style: italic;
    color: #d1d5db;
}
.cov-quote cite {
    display: block;
    text-align: right;
    color: #facc15;
    margin-top: 0.5rem;
    font-style: normal;
    font-weight: 600;
    font-size: 0.9rem;
}
.cov-err {
    background: rgba(239,68,68,0.1);
    border: 1px solid rgba(239,68,68,0.3);
    color: #fca5a5;
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 2rem;
    text-align: center;
}
.cov-phrase-box {
    background: #000;
    padding: 1.5rem;
    border: 1px dashed rgba(217,119,6,0.5);
    border-radius: 10px;
    font-family: 'Courier New', monospace;
    color: #fde68a;
    font-size: 1.1rem;
    margin-bottom: 1.5rem;
    user-select: all;
    line-height: 1.5;
}
.cov-input {
    width: 100%;
    padding: 1rem;
    background: rgba(0,0,0,0.5);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 10px;
    color: #fff;
    font-family: 'Courier New', monospace;
    font-size: 1rem;
    margin-bottom: 2rem;
    box-sizing: border-box;
    transition: all 0.3s ease;
}
.cov-input:focus {
    outline: none;
    border-color: #d97706;
    box-shadow: 0 0 15px rgba(217,119,6,0.2);
}
.cov-label {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1rem;
    cursor: pointer;
    color: #d1d5db;
    font-size: 0.95rem;
    line-height: 1.5;
}
.cov-label input[type="checkbox"] {
    margin-top: 0.3rem;
    accent-color: #d97706;
    width: 1.2rem;
    height: 1.2rem;
}
.cov-btn {
    display: block;
    width: 100%;
    padding: 1.2rem;
    margin-top: 2rem;
    background: linear-gradient(135deg, #facc15, #d97706);
    color: #000;
    border: none;
    border-radius: 10px;
    font-weight: 800;
    font-size: 1.1rem;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 10px 30px rgba(217,119,6,0.3);
}
.cov-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 15px 40px rgba(217,119,6,0.5);
}
.cov-footer {
    text-align: center;
    color: rgba(255,255,255,0.4);
    margin-top: 2rem;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.1em;
}
.cov-footer i { color: #d97706; }
</style>

<section class="covenant-section">
    <div class="cov-card">
        <div class="cov-header">
            <h1>✠ The Covenant ✠</h1>
            <p><strong>Alfred Linux is a covenant operating system.</strong> It is built for Yeshua Jesus, the Son of God. It is not for sale, not for power, not for vanity. Before any download, you must read and accept the covenant below.</p>
        </div>

        <div class="cov-quotes">
            <div class="cov-quote">
                "For God so loved the world, that he gave his only begotten Son, that whosoever believeth in him should not perish, but have everlasting life."
                <cite>— John 3:16 (AKJV)</cite>
            </div>
            <div class="cov-quote">
                "I am the way, the truth, and the life: no man cometh unto the Father, but by me."
                <cite>— John 14:6 (AKJV)</cite>
            </div>
            <div class="cov-quote">
                "That if thou shalt confess with thy mouth the Lord Jesus, and shalt believe in thine heart that God hath raised him from the dead, thou shalt be saved."
                <cite>— Romans 10:9 (AKJV)</cite>
            </div>
        </div>

        <?php if ($err): ?>
            <div class="cov-err"><?= htmlspecialchars($err) ?></div>
        <?php endif; ?>

        <?php $nextAfter = covenant_safe_next($_GET['next'] ?? null); ?>
        <form method="post" action="">
            <input type="hidden" name="next" value="<?= htmlspecialchars($nextAfter, ENT_QUOTES, 'UTF-8') ?>">
            
            <h3 style="margin-bottom:1rem; color:#fff;">To proceed, type the following phrase exactly:</h3>
            <div class="cov-phrase-box"><?= htmlspecialchars(REQUIRED_PHRASE) ?></div>
            
            <input type="text" name="phrase" class="cov-input" autocomplete="off" required placeholder="Type the phrase above, exactly...">

            <label class="cov-label">
                <input type="checkbox" name="confess" value="yes" required>
                <span>I confess Yeshua Jesus is Lord, and that He rose from the dead.</span>
            </label>
            <label class="cov-label">
                <input type="checkbox" name="agree" value="yes" required>
                <span>I accept the <strong>Authorized King Jesus Version, Perez Family Edition</strong> as the inerrant Word of God, and I will not use Alfred Linux to oppose Him. Read online: <a href="/bible/read" style="color:#facc15;">alfredlinux.com/bible</a>.</span>
            </label>

            <button type="submit" class="cov-btn">Seal the Covenant & Proceed ➔</button>
        </form>

        <div class="cov-footer">
            <i class="fas fa-shield-alt"></i> Your acceptance is cryptographically sealed in the immutable audit log and honored at the kernel layer. No bypass exists. Glory to Yeshua Jesus.
        </div>
    </div>
</section>

<?php include 'includes/site-footer.inc.php'; ?>
