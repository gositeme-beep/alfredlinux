<?php
/**
 * Alfred Linux — Shared Navigation v2
 * Grouped dropdowns + 3-language switcher (EN / FR / HE)
 *
 * Usage: <?php $currentPage = 'releases'; include __DIR__ . '/includes/nav.php'; ?>
 */
if (!isset($currentPage)) {
    $currentPage = '';
}
require_once __DIR__ . '/al-session.inc.php';

// ── Language handling ────────────────────────────────────
$AL_LANG_COOKIE = 'alfredlinux_lang';
$al_requested = isset($_GET['lang']) ? strtolower(trim($_GET['lang'])) : '';
if (in_array($al_requested, ['en','fr','he'], true)) {
    // Apply requested language immediately and persist as cookie for next visits.
    setcookie($AL_LANG_COOKIE, $al_requested, time() + 365*86400, '/', '', true, true);
    $al_lang = $al_requested;
} else {
    $al_lang = (!empty($_COOKIE[$AL_LANG_COOKIE]) && in_array($_COOKIE[$AL_LANG_COOKIE], ['en','fr','he'])) ? $_COOKIE[$AL_LANG_COOKIE] : 'en';
}
$al_dir = ($al_lang === 'he') ? 'rtl' : 'ltr';

$al_t = [
    'en' => ['packages'=>'Core Packages', 'download'=>'Download','apps'=>'Apps','docs'=>'Docs','releases'=>'Releases','security'=>'Security','kernel_supply'=>'Kernel supply chain','compare'=>'Compare','developers'=>'Developers','community'=>'Community','hardware'=>'Hardware','roadmap'=>'Roadmap','manifesto'=>'Manifesto','listen'=>'Listen','privacy'=>'Privacy','about'=>'About','goforge'=>'GoForge','donate'=>'Donate','get_alfred'=>'Get Alfred','product'=>'Product','learn'=>'Learn','more'=>'More','sign_in'=>'Member sign-in','sign_out'=>'Sign out','member_lounge'=>'Member lounge','features'=>'Features','hooks'=>'1335 Hooks','why_alfred'=>'Why Alfred?','welcome'=>'Welcome App','write_usb'=>'Write USB','pillars'=>'Nine Pillars','24_systems'=>'24 Systems','akjesusbible'=>'AKJV Bible','media'=>'Worship Media','covenant'=>'Covenant','witness'=>'Witnesses','kingdom_status'=>'Kingdom Status','kingdom_report'=>'Kingdom Report'],
    'fr' => ['packages'=>'Paquets Core', 'download'=>'Télécharger','apps'=>'Applications','docs'=>'Docs','releases'=>'Versions','security'=>'Sécurité','kernel_supply'=>'Noyau : chaîne de confiance','compare'=>'Comparer','developers'=>'Développeurs','community'=>'Communauté','hardware'=>'Matériel','roadmap'=>'Feuille de route','manifesto'=>'Manifeste','listen'=>'Écouter','privacy'=>'Confidentialité','about'=>'À propos','goforge'=>'GoForge','donate'=>'Don','get_alfred'=>'Obtenir Alfred','product'=>'Produit','learn'=>'Apprendre','more'=>'Plus','sign_in'=>'Connexion membre','sign_out'=>'Déconnexion','member_lounge'=>'Espace membres','features'=>'Fonctionnalités','hooks'=>'1335 Hooks','why_alfred'=>'Pourquoi Alfred ?','welcome'=>'App Bienvenue','write_usb'=>'Créer une clé USB','pillars'=>'Neuf Piliers','24_systems'=>'24 Systèmes','akjesusbible'=>'Bible AKJV','media'=>'Média de louange','covenant'=>'Alliance','witness'=>'Témoins','kingdom_status'=>'Statut du Royaume','kingdom_report'=>'Rapport du Royaume'],
    'he' => ['packages'=>'חבילות ליבה', 'download'=>'הורדה','apps'=>'אפליקציות','docs'=>'תיעוד','releases'=>'גרסאות','security'=>'אבטחה','kernel_supply'=>'שרשרת אספקת הליבה','compare'=>'השוואה','developers'=>'מפתחים','community'=>'קהילה','hardware'=>'חומרה','roadmap'=>'מפת דרכים','manifesto'=>'מנשר','listen'=>'הקשב','privacy'=>'פרטיות','about'=>'אודות','goforge'=>'GoForge','donate'=>'תרומה','get_alfred'=>'הורד את Alfred','product'=>'המוצר','learn'=>'למידה','more'=>'עוד','sign_in'=>'כניסת חברים','sign_out'=>'יציאה','member_lounge'=>'מועדון חברים','features'=>'תכונות','hooks'=>'1335 Hooks','why_alfred'=>'למה Alfred?','welcome'=>'אפליקציית ברוכים הבאים','write_usb'=>'צריבת USB','pillars'=>'תשעת העמודים','24_systems'=>'24 מערכות','akjesusbible'=>'תנ"ך AKJV','media'=>'מדיה ותהילים','covenant'=>'ברית','witness'=>'עדים','kingdom_status'=>'סטטוס הממלכה','kingdom_report'=>'דוח הממלכה'],
];
$t = $al_t[$al_lang] ?? $al_t['en'];
require_once __DIR__ . '/lang-content.php';
$c = $al_content[$al_lang] ?? $al_content['en'];

$al_uri = $_SERVER['REQUEST_URI'] ?? '/';
if (!is_string($al_uri) || $al_uri === '' || $al_uri[0] !== '/') {
    $al_uri = '/';
}
$al_sso_return = '/api/sso-bridge.php?target=alfred&redirect=' . rawurlencode($al_uri);
$al_signin_href = 'https://gositeme.com/login.php?return=' . rawurlencode($al_sso_return);
$langNames = ['en'=>'EN','fr'=>'FR','he'=>'עב'];
$langFull  = ['en'=>'English','fr'=>'Français','he'=>'עברית'];
?>
<nav dir="<?= $al_dir ?>">
    <a href="/?lang=<?= $al_lang ?>" class="nav-brand">
        <img class="logo-mark" src="/assets/img/alfred-mark.png" alt="Alfred Linux" width="28" height="28">
        <span class="brand-stack"><span class="brand-text"><span class="brand-alfred">Alfred</span><span class="brand-linux">Linux</span></span><span class="brand-tagline">Powering the Planet</span></span>
    </a>
    <button class="nav-toggle" onclick="document.querySelector('.nav-links').classList.toggle('open')" aria-label="Toggle menu">☰</button>
    <div class="nav-links">
        <a href="/download?lang=<?= $al_lang ?>"<?= $currentPage==='download'?' class="active"':'' ?>><?= $t['download'] ?></a>
        <a href="/listen?lang=<?= $al_lang ?>"<?= $currentPage==='listen'?' class="active"':'' ?>><?= $t['listen'] ?></a>
        <a href="/saint-jean-baptiste"<?= $currentPage==='saint-jean-baptiste'?' class="active"':'' ?> style="color: #eab308; text-shadow: 0 0 8px rgba(234, 179, 8, 0.8), 0 0 16px rgba(234, 179, 8, 0.4); font-weight: bold; letter-spacing: 1px; animation: pulse 2s infinite;">⚜️ Saint John</a>
        <a href="/howard-olsen"<?= $currentPage==='howard-olsen'?' class="active"':'' ?> style="color: #D4AF37; text-shadow: 0 0 8px rgba(212, 175, 55, 0.8); font-weight: bold; letter-spacing: 1px;">⚔️ General Howard Olsen</a>
        <a href="/qgsm-economy?lang=<?= $al_lang ?>"<?= $currentPage==='qgsm-economy'?' class="active"':'' ?> style="color: #00f2fe; text-shadow: 0 0 8px rgba(0, 242, 254, 0.8); font-weight: bold; letter-spacing: 1px;">⚡ QGSM Economy</a>

        <div class="nav-dropdown">
            <button class="nav-dropdown-btn"><?= $t['product'] ?> <span class="chev">▾</span></button>
            <div class="nav-dropdown-menu">
                <div class="nav-group-title" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #888; padding: 0.5rem 1rem 0.2rem; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 0.5rem;">Core Systems</div>
                <a href="/gold-master-2026.php?lang=<?= $al_lang ?>"<?= $currentPage==='gold-master'?' class="active"':'' ?> style="color: #fdcb6e; font-weight: bold;">👑 2026 Gold Master</a>
                <a href="/kingdom.php?lang=<?= $al_lang ?>"<?= $currentPage==='kingdom'?' class="active"':'' ?>>🏰 Kingdom Architecture</a>
                <a href="/new-jerusalem.php?lang=<?= $al_lang ?>"<?= $currentPage==='new-jerusalem'?' class="active"':'' ?>>🌌 Spatial OS</a>
                <a href="/metadome-vr.php" style="color: #c084fc;">🥽 Metadome VR</a>
                <a href="/ai-stack?lang=<?= $al_lang ?>"<?= $currentPage==='ai-stack'?' class="active"':'' ?>>🧠 Sovereign AI Stack</a>
                <a href="/lavocat.php?lang=<?= $al_lang ?>"<?= $currentPage==='lavocat'?' class="active"':'' ?>>⚖️ LAvocat Justice</a>
                <a href="/arsenal.php?lang=<?= $al_lang ?>"<?= $currentPage==='arsenal'?' class="active"':'' ?>>🎮 Software & Gaming</a>
                
                <div class="nav-group-title" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #888; padding: 0.5rem 1rem 0.2rem; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 0.5rem; margin-top: 0.5rem;">Ecosystem</div>
                <a href="/packages?lang=<?= $al_lang ?>"<?= $currentPage==='packages'?' class="active"':'' ?>>📦 <?= $t['packages'] ?></a>
                <a href="/apps?lang=<?= $al_lang ?>">📦 <?= $t['apps'] ?></a>
                <a href="/releases?lang=<?= $al_lang ?>">🚀 <?= $t['releases'] ?></a>
                <a href="/welcome?lang=<?= $al_lang ?>"<?= $currentPage==='welcome'?' class="active"':'' ?>>👋 <?= $t['welcome'] ?></a>
                <a href="/write-usb?lang=<?= $al_lang ?>"<?= $currentPage==='write-usb'?' class="active"':'' ?>>💾 <?= $t['write_usb'] ?></a>
                <a href="/hardware?lang=<?= $al_lang ?>">💻 <?= $t['hardware'] ?></a>
                <a href="/nvidia-compatibility.php?lang=<?= $al_lang ?>"<?= $currentPage==='nvidia'?' class="active"':'' ?> style="color: #76b900;"><i class="fas fa-microchip"></i> Nvidia Compatibility</a>
                <a href="/amd-compatibility.php?lang=<?= $al_lang ?>"<?= $currentPage==='amd'?' class="active"':'' ?> style="color: #ed1c24;"><i class="fas fa-microchip"></i> AMD Compatibility</a>
                <a href="/intel-compatibility.php?lang=<?= $al_lang ?>"<?= $currentPage==='intel'?' class="active"':'' ?> style="color: #0071c5;"><i class="fas fa-microchip"></i> Intel Compatibility</a>
            </div>
        </div>

        <div class="nav-dropdown">
            <button class="nav-dropdown-btn">Capabilities <span class="chev">▾</span></button>
            <div class="nav-dropdown-menu">
                <div class="nav-group-title" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #888; padding: 0.5rem 1rem 0.2rem; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 0.5rem;">Tactical & Ops</div>
                <a href="/linux-7-zfs-matrix?lang=<?= $al_lang ?>"<?= $currentPage==='linux-7-zfs'?' class="active"':'' ?> style="color: #f59e0b; font-weight: bold;"><i class="fas fa-microchip"></i> Linux 7.0 ZFS Matrix</a>
                <a href="/world-firsts?lang=<?= $al_lang ?>"<?= $currentPage==='world-firsts'?' class="active"':'' ?> style="color: #00ffcc; font-weight: bold;">🏆 47 World Firsts</a>
                <a href="/scenarios?lang=<?= $al_lang ?>"<?= $currentPage==='scenarios'?' class="active"':'' ?> style="color: #c084fc; font-weight: bold;"><i class="fas fa-users"></i> God-Tier Scenarios</a>
                <a href="/apex.php?lang=<?= $al_lang ?>"<?= $currentPage==='apex'?' class="active"':'' ?> style="color: #FF003C; font-weight: bold; letter-spacing: 1px;"><i class="fas fa-skull"></i> [CLASSIFIED] APEX OPS</a>
                <a href="/cyber-capabilities.php?lang=<?= $al_lang ?>"<?= $currentPage==='cyber-capabilities'?' class="active"':'' ?> style="color: #ef4444; font-weight: bold;">⚔️ Cyber-Warfare Capabilities</a>
                <a href="/1335-hooks.php?lang=<?= $al_lang ?>"<?= $currentPage==='1335-hooks'?' class="active"':'' ?> style="color: var(--gold); font-weight: bold;"><i class="fas fa-bolt"></i> <?= $t['hooks'] ?></a>
                
                <div class="nav-group-title" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #888; padding: 0.5rem 1rem 0.2rem; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 0.5rem; margin-top: 0.5rem;">Architecture & Sec</div>
                <a href="/features.php?lang=<?= $al_lang ?>"<?= $currentPage==='features'?' class="active"':'' ?>>✨ <?= $t['features'] ?></a>
                <a href="/24-systems?lang=<?= $al_lang ?>"<?= $currentPage==='24-systems'?' class="active"':'' ?>>⚙️ <?= $t['24_systems'] ?></a>
                <a href="/pillars?lang=<?= $al_lang ?>"<?= $currentPage==='pillars'?' class="active"':'' ?>>🏛️ <?= $t['pillars'] ?></a>
                <a href="/security?lang=<?= $al_lang ?>"<?= $currentPage==='security'?' class="active"':'' ?>>🛡️ <?= $t['security'] ?></a>
                <a href="/security-kernel?lang=<?= $al_lang ?>"<?= $currentPage==='security-kernel'?' class="active"':'' ?>>🔗 <?= $t['kernel_supply'] ?></a>
                <a href="/kernel-architecture.php?lang=<?= $al_lang ?>"<?= $currentPage==='kernel-architecture'?' class="active"':'' ?>>🧩 Kernel Architecture</a>
                <a href="/compare?lang=<?= $al_lang ?>">📊 <?= $t['compare'] ?></a>
            </div>
        </div>

        <div class="nav-dropdown">
            <button class="nav-dropdown-btn"><?= $t['learn'] ?> <span class="chev">▾</span></button>
            <div class="nav-dropdown-menu">
                <a href="/docs?lang=<?= $al_lang ?>"><?= $t['docs'] ?></a>
                <a href="/akjesusbible?lang=<?= $al_lang ?>"<?= $currentPage==='akjesusbible'?' class="active"':'' ?>><?= $t['akjesusbible'] ?></a>
                <a href="/media?lang=<?= $al_lang ?>"<?= $currentPage==='media'?' class="active"':'' ?>><?= $t['media'] ?></a>
                <a href="/covenant?lang=<?= $al_lang ?>"<?= $currentPage==='covenant'?' class="active"':'' ?>><?= $t['covenant'] ?></a>
                <a href="/witness?lang=<?= $al_lang ?>"<?= $currentPage==='witness'?' class="active"':'' ?>><?= $t['witness'] ?></a>
                <a href="/developers?lang=<?= $al_lang ?>"><?= $t['developers'] ?></a>
                <a href="/community?lang=<?= $al_lang ?>"><?= $t['community'] ?></a>
                <a href="/manifesto?lang=<?= $al_lang ?>"><?= $t['manifesto'] ?></a>
                <a href="/forge/explore/repos?lang=<?= $al_lang ?>"><?= $t['goforge'] ?></a>
            </div>
        </div>

        <div class="nav-dropdown">
            <button class="nav-dropdown-btn"><?= $t['more'] ?> <span class="chev">▾</span></button>
            <div class="nav-dropdown-menu">
                <a href="/kingdom-status?lang=<?= $al_lang ?>"<?= $currentPage==='kingdom-status'?' class="active"':'' ?>><?= $t['kingdom_status'] ?></a>
                <a href="/kingdom-report?lang=<?= $al_lang ?>"<?= $currentPage==='kingdom-report'?' class="active"':'' ?>><?= $t['kingdom_report'] ?></a>
                <a href="/privacy?lang=<?= $al_lang ?>"><?= $t['privacy'] ?></a>
                <a href="/about?lang=<?= $al_lang ?>"><?= $t['about'] ?></a>
                <a href="/daily-bread?lang=<?= $al_lang ?>">Daily Bread</a>
                <a href="/sovereign?lang=<?= $al_lang ?>">Sovereign</a>
                <a href="/verify?lang=<?= $al_lang ?>">Verify Chain</a>
                <a href="/security-kernel?lang=<?= $al_lang ?>"><?= $t['kernel_supply'] ?></a>
            </div>
        </div>

        <div class="nav-dropdown nav-lang">
            <button class="nav-dropdown-btn nav-lang-btn"><?= $langNames[$al_lang] ?> <span class="chev">▾</span></button>
            <div class="nav-dropdown-menu">
                <?php foreach ($langFull as $code => $name): if ($code !== $al_lang): ?>
                    <a href="?lang=<?= $code ?>"><?= $name ?></a>
                <?php endif; endforeach; ?>
            </div>
        </div>

        <?php if (!empty($al_user_logged_in)): ?>
        <a href="/member-lounge?lang=<?= $al_lang ?>" class="nav-signin<?= $currentPage === 'member_lounge' ? ' active' : '' ?>"><?= htmlspecialchars($t['member_lounge'] ?? 'Member lounge', ENT_QUOTES, 'UTF-8') ?></a>
        <a href="/logout.php" class="nav-signout"><?= htmlspecialchars($t['sign_out'] ?? 'Sign out', ENT_QUOTES, 'UTF-8') ?></a>
        <?php else: ?>
        <a href="<?= htmlspecialchars($al_signin_href, ENT_QUOTES, 'UTF-8') ?>" class="nav-signin" rel="noopener noreferrer"><?= htmlspecialchars($t['sign_in'] ?? 'Member sign-in', ENT_QUOTES, 'UTF-8') ?></a>
        <?php endif; ?>
        <a href="https://gositeme.com/donate.php?project=alfred-linux&amp;from=alfredlinux.com" class="nav-donate">❤ <?= $t['donate'] ?></a>
        <a href="/download?lang=<?= $al_lang ?>" class="nav-cta"><?= $t['get_alfred'] ?></a>
    </div>
</nav>
<script>
document.querySelectorAll('.nav-dropdown-btn').forEach(function(btn){
    btn.addEventListener('click',function(e){
        e.stopPropagation();
        var p=this.parentElement,wasOpen=p.classList.contains('open');
        document.querySelectorAll('.nav-dropdown.open').forEach(function(d){d.classList.remove('open');});
        if(!wasOpen)p.classList.add('open');
    });
});
document.addEventListener('click',function(){document.querySelectorAll('.nav-dropdown.open').forEach(function(d){d.classList.remove('open');});});
</script>
