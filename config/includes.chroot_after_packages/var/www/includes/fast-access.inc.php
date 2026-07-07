<?php
/**
 * Fast Access tiles — 4 corners + Forge.
 * Include from dashboard.php.
 */
$fastTiles = [
    [
        'name'  => 'GoSiteMe',
        'url'   => 'https://root.com/dashboard.php',
        'icon'  => '🏠',
        'desc'  => 'Command center',
        'color' => '#22c55e',
    ],
    [
        'name'  => 'Alfred Linux',
        'url'   => 'https://alfredlinux.com/',
        'icon'  => '🐧',
        'desc'  => 'Sovereign OS',
        'color' => '#f97316',
    ],
    [
        'name'  => 'GoHostMe',
        'url'   => 'https://gohostme.com/',
        'icon'  => '☁️',
        'desc'  => 'Hosting',
        'color' => '#3b82f6',
    ],
    [
        'name'  => 'GoCodeMe',
        'url'   => 'https://gocodeme.com/',
        'icon'  => '💻',
        'desc'  => 'Code platform',
        'color' => '#a855f7',
    ],
    [
        'name'  => 'Alfred Forge',
        'url'   => 'https://alfredlinux.com/forge/',
        'icon'  => '⚒️',
        'desc'  => 'Self-hosted Git',
        'color' => '#ef4444',
    ],
];
?>
<section class="fast-access" style="margin:24px 0;">
  <h2 style="color:#e5e7eb;font-size:18px;margin:0 0 12px;display:flex;align-items:center;gap:8px;">
    ⚡ <span>Fast Access</span>
    <span style="font-size:12px;color:#9ca3af;font-weight:400;">— 4 corners + Forge</span>
  </h2>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;">
    <?php foreach ($fastTiles as $t): ?>
      <a href="<?= htmlspecialchars($t['url']) ?>"
         target="_blank" rel="noopener"
         style="display:flex;flex-direction:column;gap:4px;padding:14px;background:#111827;border:1px solid #1f2937;border-left:3px solid <?= $t['color'] ?>;border-radius:8px;text-decoration:none;color:#e5e7eb;transition:transform .15s,border-color .15s;"
         onmouseover="this.style.transform='translateY(-2px)';this.style.borderColor='<?= $t['color'] ?>';"
         onmouseout="this.style.transform='';this.style.borderColor='#1f2937';this.style.borderLeftColor='<?= $t['color'] ?>';">
        <div style="font-size:22px;line-height:1;"><?= $t['icon'] ?></div>
        <div style="font-weight:600;font-size:14px;"><?= htmlspecialchars($t['name']) ?></div>
        <div style="font-size:11px;color:#9ca3af;"><?= htmlspecialchars($t['desc']) ?></div>
      </a>
    <?php endforeach; ?>
  </div>
</section>
