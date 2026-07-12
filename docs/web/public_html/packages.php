<?php
$currentPage = 'packages';
$focusPkg = isset($_GET['pkg']) ? preg_replace('/[^a-zA-Z0-9.\-]/', '', $_GET['pkg']) : null;
if ($focusPkg) {
    $pageTitle = htmlspecialchars($focusPkg) . " - Alfred Linux Package Database";
    $pageDescription = "View details and install " . htmlspecialchars($focusPkg) . " on the Alfred Linux Golden Master ISO.";
} else {
    $pageTitle = "Core Package Database - Alfred Linux";
    $pageDescription = "Explore the full architectural breakdown of all 4,400+ elite packages forming the golden master ISO.";
}

require_once __DIR__ . '/includes/al-session.inc.php';
require_once __DIR__ . '/includes/i18n.inc.php';
require_once __DIR__ . '/includes/lang-content.php';
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($al_lang); ?>" dir="<?php echo htmlspecialchars($al_dir); ?>" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
    <link href="/assets/css/omahon-seal.css" rel="stylesheet">
    <link href="/assets/css/nav.css" rel="stylesheet">

    <?php 
    require_once __DIR__ . '/includes/seo.inc.php';
    if (function_exists('alfred_seo')) {
        alfred_seo(
            '/packages' . ($focusPkg ? '?pkg=' . htmlspecialchars($focusPkg) : ''), 
            $pageTitle, 
            $pageDescription
        );
    }
    ?>
</head>
<body class="al-bg-dark">

    <?php include __DIR__ . '/includes/nav.php'; ?>

<?php
$json_file = __DIR__ . '/packages.json';
if (!file_exists($json_file)) {
    echo "<div class='container py-5'><h3>Package database indexing...</h3></div>";
    include __DIR__ . '/includes/footer.php';
    exit;
}
$data = json_decode(file_get_contents($json_file), true);

$categories = [];
foreach ($data as $pkg) {
    $cat = $pkg['category'];
    if (strpos($cat, '/') !== false) {
        $cat = explode('/', $cat)[1];
    }
    if (!isset($categories[$cat])) {
        $categories[$cat] = 0;
    }
    $categories[$cat]++;
}
ksort($categories);
?>

<div class="page-header py-5 text-center bg-dark text-white shadow-sm position-relative overflow-hidden">
    <div class="position-absolute w-100 h-100" style="top: 0; left: 0; background: linear-gradient(135deg, rgba(0,255,128,0.1), transparent); z-index: 0; pointer-events: none;"></div>
    <div class="container position-relative" style="z-index: 1;">
        <h1 class="display-4 fw-bold mb-3">Core Package Database</h1>
        <p class="lead mb-0 text-light" style="max-width: 700px; margin: 0 auto;">Live telemetry mapping of the 4,400+ elite packages comprising the Alfred Linux Golden Master ISO.</p>
    </div>
</div>

<div class="container py-5" style="min-height: 80vh;">
    <div class="row mb-4">
        <div class="col-12">
            <input type="text" class="form-control form-control-lg bg-dark text-white border-secondary" id="search" placeholder="Search across <?= count($data) ?> packages (e.g., wayland, gcc, plasma)...">
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card bg-dark text-white shadow-sm border-secondary">
                <div class="card-body" style="max-height: 800px; overflow-y: auto;">
                    <h5 class="card-title border-bottom border-secondary pb-2 mb-3">Categories</h5>
                    <button class="btn btn-outline-success w-100 text-start mb-2 active cat-btn" onclick="filterCat('all', this)">ALL PACKAGES (<?= count($data) ?>)</button>
                    <?php foreach ($categories as $cat => $count): ?>
                        <button class="btn btn-outline-secondary w-100 text-start mb-2 cat-btn" onclick="filterCat('<?= $cat ?>', this)"><?= strtoupper($cat) ?> (<?= $count ?>)</button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="col-md-9">
            <div class="row" id="pkg-grid" style="max-height: 800px; overflow-y: auto;">
                <?php foreach ($data as $pkg): ?>
                    <?php 
                        $c = $pkg['category']; 
                        if(strpos($c,'/')!==false) $c = explode('/',$c)[1]; 
                    ?>
                    <div class="col-md-6 col-lg-4 mb-4 pkg-card" id="pkg-<?= htmlspecialchars(strtolower($pkg['name'])) ?>" data-cat="<?= htmlspecialchars($c) ?>" data-name="<?= htmlspecialchars(strtolower($pkg['name'])) ?>" data-desc="<?= htmlspecialchars(strtolower($pkg['description'])) ?>">
                        <div class="card bg-dark text-white h-100 shadow-sm border-secondary">
                            <div class="card-body">
                                <h5 class="card-title text-success text-truncate" title="<?= htmlspecialchars($pkg['name']) ?>">
                                    <?php if(!empty($pkg['icon'])): ?>
                                        <img src="<?= htmlspecialchars($pkg['icon']) ?>" width="24" height="24" class="me-2 rounded" style="vertical-align: middle;">
                                    <?php endif; ?>
                                    <?= htmlspecialchars($pkg['name']) ?>
                                </h5>
                                <span class="badge bg-secondary mb-2"><?= htmlspecialchars($c) ?></span>
                                <?php if(!empty($pkg['launch_command'])): ?>
                                    <span class="badge bg-primary mb-2 ms-1" style="cursor: pointer;" onclick="alert('Native Launch Command:\n<?= htmlspecialchars(addslashes($pkg['launch_command'])) ?>')"><i class="fas fa-terminal"></i> Execute</span>
                                <?php endif; ?>
                                <p class="card-text small text-muted" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;"><?= htmlspecialchars($pkg['description']) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
    let currentCat = 'all';
    function filterCat(cat, btn) {
        currentCat = cat;
        document.getElementById("search").value = "";
        document.querySelectorAll('.cat-btn').forEach(b => {
            b.classList.remove('active');
            b.classList.remove('btn-outline-success');
            b.classList.add('btn-outline-secondary');
        });
        btn.classList.add('active');
        btn.classList.remove('btn-outline-secondary');
        btn.classList.add('btn-outline-success');
        applyFilters();
    }
    document.getElementById('search').addEventListener('input', applyFilters);

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('pkg')) {
        const p = urlParams.get('pkg');
        document.getElementById('search').value = p;
        applyFilters();
        setTimeout(() => {
            const el = document.getElementById('pkg-' + p.toLowerCase());
            if(el) {
                el.scrollIntoView({behavior: 'smooth', block: 'center'});
                el.querySelector('.card').classList.add('border-success');
                el.querySelector('.card').classList.remove('border-secondary');
            }
        }, 500);
    }

    function applyFilters() {
        const query = document.getElementById('search').value.toLowerCase();
        const cards = document.querySelectorAll('.pkg-card');
        cards.forEach(card => {
            const matchCat = (currentCat === 'all' || card.getAttribute('data-cat') === currentCat);
            const matchQuery = card.getAttribute('data-name').includes(query) || card.getAttribute('data-desc').includes(query);
            if (matchCat && matchQuery) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
