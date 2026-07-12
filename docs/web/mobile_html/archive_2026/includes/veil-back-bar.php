<?php
/**
 * Floating "Back to Veil" bar for sub-pages accessed from the Veil Android app.
 * Include at the bottom of any page that can be reached from Veil to provide navigation back.
 * Usage: require_once __DIR__ . '/../includes/veil-back-bar.php';
 */
$referrer = $_SERVER['HTTP_REFERER'] ?? '';
$fromVeil = (strpos($referrer, '/veil/') !== false || strpos($referrer, '/veil') !== false);
$isTWA = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'com.gositeme.veil')
      || (strpos($_SERVER['HTTP_USER_AGENT'] ?? '', 'GoSiteMe') !== false)
      || $fromVeil
      || (isset($_GET['from']) && $_GET['from'] === 'veil');
?>
<?php if ($isTWA): ?>
<div id="veil-back-bar" style="position:fixed;top:0;left:0;right:0;z-index:99999;background:linear-gradient(135deg,#0d0d1a,#1a1a2e);border-bottom:1px solid rgba(255,215,0,0.2);padding:8px 16px;display:flex;align-items:center;justify-content:space-between;gap:12px;font-family:'Inter',system-ui,sans-serif">
    <button onclick="history.length>1?history.back():location.href='/veil/'" style="background:rgba(255,215,0,0.1);border:1px solid rgba(255,215,0,0.3);color:#ffd700;padding:6px 14px;border-radius:20px;font-size:0.8rem;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;white-space:nowrap">
        <i class="fas fa-arrow-left" style="font-size:0.7rem"></i> Back
    </button>
    <div style="display:flex;gap:8px">
        <button onclick="location.href='/veil/'" style="background:rgba(46,213,115,0.1);border:1px solid rgba(46,213,115,0.3);color:#2ed573;padding:6px 12px;border-radius:20px;font-size:0.7rem;font-weight:600;cursor:pointer;white-space:nowrap">
            <i class="fas fa-lock" style="font-size:0.6rem"></i> Veil
        </button>
        <button onclick="location.href='/veil/command-center.php'" style="background:rgba(104,109,224,0.1);border:1px solid rgba(104,109,224,0.3);color:#686de0;padding:6px 12px;border-radius:20px;font-size:0.7rem;font-weight:600;cursor:pointer;white-space:nowrap">
            <i class="fas fa-shield-halved" style="font-size:0.6rem"></i> HQ
        </button>
    </div>
</div>
<style>
#veil-back-bar + * { margin-top: 52px !important; }
body { padding-top: 52px; }
</style>
<?php endif; ?>
