<?php
/**
 * /downloads.php — public Alfred Linux 7.77 GA landing page.
 * Auto-discovers ISO, sidecars, GPG key, and renders verification commands.
 * Reads downloads/ directly — no DB. Idempotent against publish overwrites.
 */

$DL_DIR = '/home/gositeme/domains/alfredlinux.com/public_html/downloads';
$DL_URL = '/downloads';

// Find latest ISO
$isos = glob("$DL_DIR/alfred-linux-7.77-ga-intel-amd64-*.iso") ?: [];
usort($isos, function($a,$b){ return filemtime($b) - filemtime($a); });
$iso = $isos[0] ?? null;
$iso_name = $iso ? basename($iso) : null;
$iso_size_bytes = $iso ? filesize($iso) : 0;
$iso_size_gb = $iso_size_bytes ? round($iso_size_bytes / (1024*1024*1024), 2) : 0;
$iso_mtime = $iso ? date('Y-m-d H:i:s T', filemtime($iso)) : '';

// Sidecars
$sha256 = $iso && file_exists("$iso.sha256") ? trim(file_get_contents("$iso.sha256")) : '';
$blake3 = $iso && file_exists("$iso.blake3") ? trim(file_get_contents("$iso.blake3")) : '';
$sha512 = $iso && file_exists("$iso.sha512") ? trim(file_get_contents("$iso.sha512")) : '';

// SHA256SUMS aggregate
$sums_file = "$DL_DIR/SHA256SUMS-7.77.txt";
$sums_sig  = "$DL_DIR/SHA256SUMS-7.77.txt.asc";
$sums_exists = file_exists($sums_file);
$sums_signed = file_exists($sums_sig);

// GPG key
$gpg_key_file = file_exists("$DL_DIR/GPG-KEY-RELEASE-7.77.asc") ? 'GPG-KEY-RELEASE-7.77.asc'
              : (file_exists("$DL_DIR/GPG-KEY.asc") ? 'GPG-KEY.asc' : null);

$KEYID = '04426AB7A3988D84559D9B92B3BFEC4C80900BF9';

header('Cache-Control: public, max-age=60');
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Alfred Linux 7.77 GA — Download</title>
<meta name="description" content="Alfred Linux 7.77 GA — Debian 13 Trixie based, signed ISO, 80 boot hooks, the Kingdom-themed live distribution.">
<style>
  :root { color-scheme: dark; }
  * { box-sizing: border-box; }
  body { margin: 0; font: 15px/1.6 -apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;
         background: linear-gradient(180deg,#0a0d14,#0d1219); color: #e6edf3;
         padding: 40px 24px; min-height: 100vh; }
  .wrap { max-width: 1000px; margin: 0 auto; }
  header { text-align: center; margin-bottom: 48px; }
  h1 { margin: 0 0 8px 0; font-size: 36px; font-weight: 700;
       background: linear-gradient(90deg,#58a6ff,#a371f7); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
  .tag { color: #8b949e; font-size: 16px; }
  .pill { display: inline-block; padding: 4px 12px; margin: 4px; border-radius: 999px; font-size: 12px;
          background: #1f6feb22; color: #58a6ff; border: 1px solid #1f6feb55; }
  .card { background: #161b22; border: 1px solid #30363d; border-radius: 12px;
          padding: 24px; margin-bottom: 20px; }
  .card h2 { margin: 0 0 16px 0; font-size: 18px; color: #f0f6fc; }
  .dl { display: flex; align-items: center; justify-content: space-between; gap: 16px;
        flex-wrap: wrap; padding: 16px; background: #0d1117; border-radius: 8px; border: 1px solid #30363d; }
  .dl-info { flex: 1; min-width: 240px; }
  .dl-info .name { font-family: ui-monospace,SFMono-Regular,Menlo,monospace; font-size: 14px; color: #58a6ff; word-break: break-all; }
  .dl-info .meta { color: #8b949e; font-size: 13px; margin-top: 4px; }
  .btn { display: inline-block; padding: 12px 24px; background: #238636; color: white;
         border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 15px;
         border: 1px solid #2ea043; }
  .btn:hover { background: #2ea043; }
  .btn.alt { background: #21262d; border-color: #30363d; color: #c9d1d9; }
  .btn.alt:hover { background: #30363d; }
  pre { background: #0d1117; border: 1px solid #30363d; border-radius: 8px; padding: 14px;
        font: 13px/1.5 ui-monospace,SFMono-Regular,Menlo,monospace; color: #c9d1d9;
        white-space: pre-wrap; word-break: break-all; overflow: auto; margin: 0 0 8px 0; }
  .hash { font-family: ui-monospace,SFMono-Regular,Menlo,monospace; font-size: 12px;
          color: #8b949e; word-break: break-all; padding: 8px; background: #0d1117; border-radius: 6px; }
  table { width: 100%; border-collapse: collapse; margin: 8px 0; }
  td, th { padding: 8px 10px; text-align: left; border-bottom: 1px solid #21262d; font-size: 13px; }
  th { color: #8b949e; font-weight: 500; text-transform: uppercase; font-size: 11px; letter-spacing: .5px; }
  td a { color: #58a6ff; text-decoration: none; }
  td a:hover { text-decoration: underline; }
  .nav { text-align: center; margin-bottom: 24px; }
  .nav a { color: #58a6ff; text-decoration: none; margin: 0 12px; font-size: 14px; }
  .nav a:hover { text-decoration: underline; }
  footer { text-align: center; color: #6e7681; font-size: 12px; margin-top: 48px; }
  .ok { color: #3fb950; }
  .warn { color: #d29922; }
  .err { color: #f85149; }
</style>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/img/favicon/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/img/favicon/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#D4AF37">
</head>
<body>
<div class="wrap">

<div class="nav">
  <a href="/">Home</a>
  <a href="/about.php">About</a>
  <a href="/apps.php">Apps</a>
  <a href="/compare.php">Compare</a>
  <a href="https://gositeme.com/build-status.php">Build status →</a>
</div>

<header>
  <h1>Alfred Linux 7.77 GA</h1>
  <div class="tag">Debian 13 Trixie · 80 boot hooks · signed ISO · Kingdom edition</div>
  <div style="margin-top: 12px;">
    <span class="pill">trixie 13.4</span>
    <span class="pill">amd64</span>
    <span class="pill">live + installer</span>
    <span class="pill">GRUB-EFI + syslinux</span>
    <span class="pill">GPG signed</span>
  </div>
</header>

<?php if (!$iso): ?>
  <div class="card">
    <h2 class="warn">⏳ ISO build in progress</h2>
    <p>The 7.77 GA ISO is currently being built. Watch progress live:</p>
    <p><a class="btn" href="https://gositeme.com/build-status.php">Live build status →</a></p>
  </div>
<?php else: ?>

<div class="card">
  <h2>📥 Direct download</h2>
  <div class="dl">
    <div class="dl-info">
      <div class="name"><?= htmlspecialchars($iso_name) ?></div>
      <div class="meta"><?= $iso_size_gb ?> GB · built <?= htmlspecialchars($iso_mtime) ?></div>
    </div>
    <a class="btn" href="<?= $DL_URL ?>/<?= rawurlencode($iso_name) ?>">Download ISO</a>
  </div>

  <h3 style="margin-top:24px; font-size: 14px; color: #8b949e; text-transform: uppercase; letter-spacing: .5px;">Sidecars</h3>
  <table>
    <tr><th>File</th><th>Purpose</th><th></th></tr>
    <?php if ($sha256): ?><tr><td><code><?= htmlspecialchars($iso_name) ?>.sha256</code></td><td>SHA-256 checksum</td><td><a href="<?= $DL_URL ?>/<?= rawurlencode($iso_name) ?>.sha256">download</a></td></tr><?php endif; ?>
    <?php if ($blake3): ?><tr><td><code><?= htmlspecialchars($iso_name) ?>.blake3</code></td><td>BLAKE3 checksum</td><td><a href="<?= $DL_URL ?>/<?= rawurlencode($iso_name) ?>.blake3">download</a></td></tr><?php endif; ?>
    <?php if ($sums_exists): ?><tr><td><code>SHA256SUMS-7.77.txt</code></td><td>Aggregate SHA-256 manifest</td><td><a href="<?= $DL_URL ?>/SHA256SUMS-7.77.txt">download</a></td></tr><?php endif; ?>
    <?php if ($sums_signed): ?><tr><td><code>SHA256SUMS-7.77.txt.asc</code></td><td>GPG signature on manifest</td><td><a href="<?= $DL_URL ?>/SHA256SUMS-7.77.txt.asc">download</a></td></tr><?php endif; ?>
    <?php if ($gpg_key_file): ?><tr><td><code><?= htmlspecialchars($gpg_key_file) ?></code></td><td>Release signing key (key id <?= substr($KEYID,-16) ?>)</td><td><a href="<?= $DL_URL ?>/<?= rawurlencode($gpg_key_file) ?>">download</a></td></tr><?php endif; ?>
  </table>
</div>

<div class="card">
  <h2>✅ Verify the download</h2>
  <p style="color: #8b949e; margin-top: 0;">Don't trust — <em>verify</em>. The signing key fingerprint is:</p>
  <div class="hash"><?= chunk_split($KEYID, 4, ' ') ?></div>

  <h3 style="margin-top:20px; font-size: 14px; color: #8b949e; text-transform: uppercase; letter-spacing: .5px;">Quick verification</h3>
<pre>
# 1. Download files
wget https://alfredlinux.com<?= $DL_URL ?>/<?= htmlspecialchars($iso_name) ?>
wget https://alfredlinux.com<?= $DL_URL ?>/SHA256SUMS-7.77.txt
wget https://alfredlinux.com<?= $DL_URL ?>/SHA256SUMS-7.77.txt.asc
wget https://alfredlinux.com<?= $DL_URL ?>/<?= htmlspecialchars($gpg_key_file ?? 'GPG-KEY.asc') ?>

# 2. Import release key + verify signature
gpg --import <?= htmlspecialchars($gpg_key_file ?? 'GPG-KEY.asc') ?>

gpg --verify SHA256SUMS-7.77.txt.asc SHA256SUMS-7.77.txt

# 3. Verify ISO checksum
sha256sum -c SHA256SUMS-7.77.txt
</pre>

  <?php if ($sha256): ?>
  <h3 style="margin-top:20px; font-size: 14px; color: #8b949e; text-transform: uppercase; letter-spacing: .5px;">Direct hash</h3>
  <div class="hash"><?= htmlspecialchars($sha256) ?></div>
  <?php endif; ?>
</div>

<div class="card">
  <h2>💿 Write to USB</h2>
<pre>
# Linux / macOS (replace /dev/sdX with your USB device — be careful!)
sudo dd if=<?= htmlspecialchars($iso_name) ?> of=/dev/sdX bs=4M status=progress conv=fsync

# Windows: use Rufus, Etcher, or Ventoy with the ISO file directly
</pre>
</div>

<?php endif; ?>

<div class="card">
  <h2>📜 What's included</h2>
  <ul style="margin: 0; padding-left: 24px; line-height: 1.9;">
    <li>Debian 13 Trixie base (kernel 7.0.12 series)</li>
    <li>80 chroot boot hooks: 47 GA core + 7 Pillars + 12 Tribes + 9 Fruits + 2 Loaves + capstone</li>
    <li>Alfred Voice, Account, Commander, Sabbath, Daily Bread CLIs</li>
    <li>Kingdom MOTD with Hebrew calendar, 10-language greetings</li>
    <li>Welcome-of-Welcomes panel for 7 denominations</li>
    <li>Omahon Seal wallpapers (1080p, 4K, 8K)</li>
    <li>22 Kingdom wallpapers per resolution</li>
    <li>AKJV Bible TSVs (7 books) + alfred-bible CLI</li>
    <li>Alfred Plymouth boot splash + GRUB theme</li>
    <li>Reproducible build (SOURCE_DATE_EPOCH pinned)</li>
  </ul>
</div>

<footer>
  In the name of Yeshua, Jesus Christ of Bethlehem, King of the Universe.<br>
  Released under AGPL-3.0-or-later · <a href="/" style="color:#58a6ff">alfredlinux.com</a>
</footer>

</div>
</body>
</html>

