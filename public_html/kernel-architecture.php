<?php
http_response_code(200);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>The Architecture of a 5,000-Line Kernel | Alfred Linux</title>
  <meta name="description" content="How Alfred Linux set a new gold standard by aggressive kernel debloat and DKMS modernization." />
  <style>
    :root { color-scheme: dark; }
    body { margin: 0; font-family: ui-sans-serif, system-ui, Segoe UI, Arial, sans-serif; background: #070b15; color: #e6ecff; }
    .wrap { max-width: 900px; margin: 0 auto; padding: 44px 20px 80px; }
    h1 { margin: 0 0 10px; font-size: 2rem; }
    .sub { color: #b9c7ef; margin-bottom: 24px; line-height: 1.6; }
    .badge { display: inline-block; padding: 6px 10px; border-radius: 999px; background: #12361f; color: #ccffd8; border: 1px solid #1d5a31; font-size: .82rem; font-weight: 700; letter-spacing: .02em; margin-bottom: 20px;}
    .card { margin-top: 20px; background: #0c1428; border: 1px solid #26365f; border-radius: 12px; padding: 24px; line-height: 1.6; }
    .card h2 { margin: 0 0 15px; font-size: 1.2rem; color: #8ab4ff; }
    .card p { margin-top: 0; }
    ul { margin: 8px 0 0 20px; }
    li { margin: 8px 0; }
    a { color: #8ab4ff; text-decoration: none; }
    a:hover { text-decoration: underline; }
    code { background: #111d39; border-radius: 6px; padding: 2px 6px; font-family: monospace; color: #ffb86c; }
    .highlight { border-color: #D4AF37; }
    .highlight h2 { color: #D4AF37; }
  </style>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/img/favicon/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/img/favicon/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#D4AF37">
    <link rel="stylesheet" href="/assets/css/nav.css">
</head>
<body>

<?php $currentPage = 'kernel-architecture'; include __DIR__ . '/includes/nav.php'; ?>

  <main class="wrap">
    <h1>The Architecture of a 5,000-Line Kernel</h1>
    <div class="sub">Most mainstream Linux distributions ship with 25,000+ bloated kernel configuration options. Alfred Linux has stripped the Kernel down to 5,115 lines of pure, hardened optimization.</div>
    <div class="badge">KERNEL ARCHITECTURE REPORT</div>

    <section class="card highlight">
      <h2>Setting a New Gold Standard</h2>
      <p>It is staggering how complex the mainstream Linux kernel has become. To put it into perspective:</p>
      <ul>
        <li><strong>Linux 5.15</strong> had roughly 18,000 configuration options.</li>
        <li><strong>Linux 6.12</strong> pushed that past 23,000 options.</li>
        <li><strong>Linux 7.0</strong> has exploded to well over 25,000+ total configuration toggles, drivers, and subsystems.</li>
      </ul>
      <p>When you install a mainstream distribution, they take the easy way out. They compile their generic kernels with almost <i>all</i> 25,000 options enabled. They load up thousands of legacy drivers, debug hooks, and bloated subsystems just in case a user plugs in a piece of hardware from 2004.</p>
    </section>

    <section class="card">
      <h2>Aggressive Debloat & Hardening</h2>
      <p>The Alfred Linux <code>kernel-hardened.config</code> is exactly <strong>5,115 lines long</strong>.</p>
      <p>We have aggressively debloated the kernel, stripping away over 15,000 lines of legacy bloat, unprotected memory regions, and unnecessary attack surfaces. By strictly enabling only bleeding-edge security policies—such as <code>SECURITY_LANDLOCK</code>, <code>INIT_ON_ALLOC_DEFAULT_ON</code>, and <code>RANDOMIZE_BASE</code>—we have reduced the attack surface to the bare metal.</p>
    </section>
    
    <section class="card">
      <h2>Seamless DKMS Integration</h2>
      <p>Optimizing a kernel this heavily normally breaks third-party support. However, we have successfully integrated modern out-of-tree drivers natively via DKMS into this ultra-lean environment.</p>
      <ul>
        <li><strong>OpenZFS:</strong> Natively compiled into the 7.0 payload, completely bypassing missing generic loadable module dependencies.</li>
        <li><strong>Darling (macOS Translation):</strong> Seamlessly injected into the customized kernel headers.</li>
        <li><strong>Proprietary Graphics:</strong> Perfected EFI and Simple Framebuffer (`SYSFB_SIMPLEFB`) handoffs to ensure zero-black-screen transitions into proprietary Nvidia and AMD environments.</li>
      </ul>
    </section>

    <section class="card">
      <h2>Conclusion</h2>
      <p>Most distributions would never dare to ship a kernel this tightly optimized because it requires an immense amount of engineering to perfectly balance aggressive security hardening with out-of-the-box hardware compatibility. Alfred Linux isn't just building an OS—it's setting a new gold standard for Linux architecture.</p>
    </section>

    <section class="card">
      <h2>Related Pages</h2>
      <a href="/kernel-7012.php">Kernel 7.0.12 Status</a><br />
      <a href="/security-kernel">Security Kernel Transparency</a><br />
      <a href="/download">Downloads</a>
    </section>
  </main>
<footer>
    &copy; <?= date('Y') ?> <a href="https://gositeme.com">GoSiteMe Inc.</a> &mdash; Alfred Linux &middot; Open Source (KCL-1.0)
    &middot; <a href="/apps">Apps</a> &middot; <a href="/verify">Verify</a>
    &middot; <a href="/docs">Docs</a> &middot; <a href="/developers">Developers</a>
</footer>

<?php include __DIR__ . '/includes/shabbat-banner.php'; ?>
</body>
</html>
