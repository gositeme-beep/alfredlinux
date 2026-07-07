<?php
require_once __DIR__ . '/includes/db-config.inc.php';
$db = getSharedDB();
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/rank-guard.inc.php';
requireRank(1);

if (empty($_SESSION['csrf_broadcast'])) $_SESSION['csrf_broadcast'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_broadcast'];
$isCommander = ($clientId === 33);
$isOfficer   = ($userRankTier >= 7 || $isCommander);
$msg = '';

// --- POST Actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && hash_equals($csrf, $_POST['csrf'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'subscribe') {
        $chId = (int)($_POST['channel_id'] ?? 0);
        $ch = $db->prepare("SELECT id, min_rank_tier FROM broadcast_channels WHERE id=? AND is_active=1");
        $ch->execute([$chId]);
        $ch = $ch->fetch(PDO::FETCH_ASSOC);
        if ($ch && ($userRankTier >= (int)$ch['min_rank_tier'] || $isCommander)) {
            $ins = $db->prepare("INSERT IGNORE INTO broadcast_subscriptions (client_id, channel_id, subscribed_at) VALUES (?,?,NOW())");
            $ins->execute([$clientId, $chId]);
            $msg = 'Subscribed to channel.';
        }
    } elseif ($action === 'unsubscribe') {
        $chId = (int)($_POST['channel_id'] ?? 0);
        $del = $db->prepare("DELETE FROM broadcast_subscriptions WHERE client_id=? AND channel_id=?");
        $del->execute([$clientId, $chId]);
        $msg = 'Unsubscribed.';
    } elseif ($action === 'publish' && $isOfficer) {
        $chId   = (int)($_POST['channel_id'] ?? 0);
        $title  = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $bType  = $_POST['broadcast_type'] ?? 'article';
        $pri    = $_POST['priority'] ?? 'routine';
        $cls    = $_POST['classification'] ?? 'public';
        $pin    = !empty($_POST['is_pinned']) ? 1 : 0;
        $validTypes = ['article','flash','bulletin','editorial','speech','decree'];
        $validPri   = ['routine','important','urgent','critical','flash'];
        $validCls   = ['public','internal','classified','top_secret'];
        if ($title !== '' && $content !== '' && $chId > 0
            && in_array($bType, $validTypes, true)
            && in_array($pri, $validPri, true)
            && in_array($cls, $validCls, true)) {
            $ins = $db->prepare("INSERT INTO broadcasts (channel_id, title, content, broadcast_type, priority, author_client_id, classification, views, reactions_positive, reactions_negative, is_pinned, published_at) VALUES (?,?,?,?,?,?,?,0,0,0,?,NOW())");
            $ins->execute([$chId, $title, $content, $bType, $pri, $clientId, $cls, $pin]);
            $newId = (int)$db->lastInsertId();
            awardXP($clientId, 'broadcast_publish', ['broadcast_id' => $newId]);
            $msg = 'Broadcast published.';
        } else {
            $msg = 'Missing required fields.';
        }
    } elseif ($action === 'react') {
        $bId  = (int)($_POST['broadcast_id'] ?? 0);
        $type = ($_POST['react_type'] ?? '') === 'negative' ? 'reactions_negative' : 'reactions_positive';
        $db->prepare("UPDATE broadcasts SET {$type} = {$type} + 1 WHERE id=?")->execute([$bId]);
        $msg = 'Reaction recorded.';
    }
    // PRG redirect
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?' . http_build_query(array_filter(['view' => $_GET['view'] ?? '', 'id' => $_GET['id'] ?? '', 'tab' => $_GET['tab'] ?? '', 'msg' => $msg])));
    exit;
}
if (!empty($_GET['msg'])) $msg = htmlspecialchars($_GET['msg']);

// --- Data Queries ---
$tab = $_GET['tab'] ?? 'feed';

// Channels user can see
$channels = $db->prepare("SELECT c.*, (SELECT COUNT(*) FROM broadcast_subscriptions WHERE channel_id=c.id) AS subs, EXISTS(SELECT 1 FROM broadcast_subscriptions WHERE channel_id=c.id AND client_id=?) AS subscribed FROM broadcast_channels c WHERE c.is_active=1 AND (c.min_rank_tier <= ? OR ?=1) ORDER BY c.channel_type, c.name");
$channels->execute([$clientId, $userRankTier, $isCommander ? 1 : 0]);
$channels = $channels->fetchAll(PDO::FETCH_ASSOC);

// Single broadcast view
$singleBroadcast = null;
if (!empty($_GET['id'])) {
    $sid = (int)$_GET['id'];
    $sb = $db->prepare("SELECT b.*, c.name AS channel_name, c.channel_type FROM broadcasts b JOIN broadcast_channels c ON c.id=b.channel_id WHERE b.id=?");
    $sb->execute([$sid]);
    $singleBroadcast = $sb->fetch(PDO::FETCH_ASSOC);
    if ($singleBroadcast) {
        $db->prepare("UPDATE broadcasts SET views = views + 1 WHERE id=?")->execute([$sid]);
        $singleBroadcast['views']++;
        $tab = 'view';
    }
}

// Feed broadcasts
$feedSql = "SELECT b.*, c.name AS channel_name, c.channel_type FROM broadcasts b JOIN broadcast_channels c ON c.id=b.channel_id WHERE (b.classification='public' OR ?=1) AND (b.expires_at IS NULL OR b.expires_at > NOW()) ORDER BY b.is_pinned DESC, FIELD(b.priority,'flash','critical','urgent','important','routine'), b.published_at DESC LIMIT 50";
$feedStmt = $db->prepare($feedSql);
$feedStmt->execute([$isCommander ? 1 : 0]);
$feed = $feedStmt->fetchAll(PDO::FETCH_ASSOC);

// My subscriptions
$mySubs = $db->prepare("SELECT c.*, s.subscribed_at FROM broadcast_subscriptions s JOIN broadcast_channels c ON c.id=s.channel_id WHERE s.client_id=? ORDER BY s.subscribed_at DESC");
$mySubs->execute([$clientId]);
$mySubs = $mySubs->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'MIL-NET Broadcasting';
require_once __DIR__ . '/includes/site-header.inc.php';

// Helper: priority badge
function priorityBadge(string $p): string {
    $colors = ['routine'=>'#6b7280','important'=>'#3b82f6','urgent'=>'#f59e0b','critical'=>'#ef4444','flash'=>'#eab308'];
    $c = $colors[$p] ?? '#6b7280';
    $pulse = $p === 'flash' ? 'animation:pulse-border 1.5s infinite;' : '';
    return '<span style="background:'.$c.';color:#000;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;text-transform:uppercase;'.$pulse.'">'.htmlspecialchars($p).'</span>';
}
function classBadge(string $c): string {
    $colors = ['public'=>'#22c55e','internal'=>'#3b82f6','classified'=>'#f59e0b','top_secret'=>'#ef4444'];
    $cl = $colors[$c] ?? '#6b7280';
    return '<span style="border:1px solid '.$cl.';color:'.$cl.';padding:2px 8px;border-radius:4px;font-size:11px;text-transform:uppercase;">'.htmlspecialchars(str_replace('_',' ',$c)).'</span>';
}
function channelIcon(string $t): string {
    $icons = ['news'=>'fa-newspaper','alert'=>'fa-triangle-exclamation','propaganda'=>'fa-bullhorn','entertainment'=>'fa-film','emergency'=>'fa-siren-on'];
    return '<i class="fas '.($icons[$t] ?? 'fa-broadcast-tower').'"></i>';
}
?>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<style>
:root{--bg:#0f172a;--card:#1e293b;--border:#334155;--text:#e2e8f0;--accent:#3b82f6;--muted:#94a3b8}
.mil-wrap{max-width:1100px;margin:30px auto;padding:0 16px;color:var(--text);font-family:system-ui,-apple-system,sans-serif}
.mil-title{font-size:28px;font-weight:800;color:#fff;margin-bottom:6px;display:flex;align-items:center;gap:10px}
.mil-sub{color:var(--muted);font-size:14px;margin-bottom:24px}
.tabs{display:flex;gap:6px;margin-bottom:20px;flex-wrap:wrap}
.tab{padding:8px 18px;background:var(--card);border:1px solid var(--border);border-radius:6px;color:var(--muted);cursor:pointer;text-decoration:none;font-size:13px;font-weight:600;transition:.2s}
.tab:hover,.tab.active{background:var(--accent);color:#fff;border-color:var(--accent)}
.msg-bar{padding:10px 16px;background:#164e63;border:1px solid #0e7490;border-radius:6px;margin-bottom:16px;font-size:13px;color:#67e8f9}
.card{background:var(--card);border:1px solid var(--border);border-radius:8px;padding:18px;margin-bottom:14px;transition:.2s}
.card:hover{border-color:var(--accent)}
.card-title{font-size:17px;font-weight:700;color:#fff;margin-bottom:6px}
.card-meta{font-size:12px;color:var(--muted);display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:8px}
.card-body{font-size:14px;line-height:1.6;color:var(--text)}
.btn{display:inline-block;padding:7px 16px;border-radius:6px;font-size:13px;font-weight:600;border:none;cursor:pointer;text-decoration:none;transition:.2s}
.btn-primary{background:var(--accent);color:#fff}.btn-primary:hover{background:#2563eb}
.btn-danger{background:#dc2626;color:#fff}.btn-danger:hover{background:#b91c1c}
.btn-ghost{background:transparent;border:1px solid var(--border);color:var(--muted)}.btn-ghost:hover{border-color:var(--accent);color:var(--accent)}
.ch-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:14px}
.ch-type{font-size:12px;text-transform:uppercase;color:var(--accent);font-weight:700;letter-spacing:1px;margin-bottom:4px}
.ch-subs{font-size:12px;color:var(--muted)}
.form-group{margin-bottom:14px}
.form-group label{display:block;font-size:13px;color:var(--muted);margin-bottom:4px;font-weight:600}
.form-group input,.form-group select,.form-group textarea{width:100%;padding:9px 12px;background:#0f172a;border:1px solid var(--border);border-radius:6px;color:var(--text);font-size:14px;box-sizing:border-box}
.form-group textarea{min-height:120px;resize:vertical}
.flash-card{border:2px solid #eab308;animation:pulse-border 1.5s infinite}
@keyframes pulse-border{0%,100%{border-color:#eab308;box-shadow:0 0 8px rgba(234,179,8,.3)}50%{border-color:#fde047;box-shadow:0 0 18px rgba(234,179,8,.6)}}
.react-btn{background:none;border:1px solid var(--border);color:var(--muted);padding:4px 10px;border-radius:4px;cursor:pointer;font-size:13px;transition:.2s}
.react-btn:hover{border-color:var(--accent);color:var(--accent)}
.pinned-tag{background:#7c3aed;color:#fff;font-size:10px;padding:2px 6px;border-radius:3px;text-transform:uppercase;font-weight:700}
.badge-row{display:flex;gap:6px;align-items:center;flex-wrap:wrap}
</style>

<div class="mil-wrap">
<div class="mil-title"><i class="fas fa-broadcast-tower" style="color:var(--accent)"></i> MIL-NET Broadcasting</div>
<div class="mil-sub">Military Broadcasting Network — <?= htmlspecialchars($userRankCode) ?> clearance · <?= count($mySubs) ?> subscriptions</div>

<?php if ($msg): ?><div class="msg-bar"><i class="fas fa-info-circle"></i> <?= $msg ?></div><?php endif; ?>

<?php if ($tab !== 'view'): ?>
<div class="tabs">
    <a class="tab <?= $tab==='feed'?'active':'' ?>" href="?tab=feed"><i class="fas fa-rss"></i> Feed</a>
    <a class="tab <?= $tab==='channels'?'active':'' ?>" href="?tab=channels"><i class="fas fa-layer-group"></i> Channels</a>
    <a class="tab <?= $tab==='subs'?'active':'' ?>" href="?tab=subs"><i class="fas fa-bell"></i> My Subs</a>
    <?php if ($isOfficer): ?><a class="tab <?= $tab==='publish'?'active':'' ?>" href="?tab=publish"><i class="fas fa-pen-nib"></i> Publish</a><?php endif; ?>
</div>
<?php endif; ?>

<?php if ($tab === 'view' && $singleBroadcast): $b = $singleBroadcast; ?>
<!-- Single Broadcast View -->
<a href="?tab=feed" class="btn btn-ghost" style="margin-bottom:16px"><i class="fas fa-arrow-left"></i> Back to Feed</a>
<div class="card <?= $b['priority']==='flash'?'flash-card':'' ?>">
    <div class="badge-row" style="margin-bottom:8px">
        <?php if ($b['is_pinned']): ?><span class="pinned-tag"><i class="fas fa-thumbtack"></i> Pinned</span><?php endif; ?>
        <?= priorityBadge($b['priority']) ?> <?= classBadge($b['classification']) ?>
        <span style="font-size:12px;color:var(--muted)"><?= channelIcon($b['channel_type']) ?> <?= htmlspecialchars($b['channel_name']) ?></span>
    </div>
    <div class="card-title" style="font-size:22px"><?= htmlspecialchars($b['title']) ?></div>
    <div class="card-meta">
        <span><i class="fas fa-user"></i> Author #<?= (int)$b['author_client_id'] ?></span>
        <span><i class="fas fa-clock"></i> <?= htmlspecialchars($b['published_at']) ?></span>
        <span><i class="fas fa-eye"></i> <?= number_format((int)$b['views']) ?> views</span>
        <span><i class="fas fa-tag"></i> <?= htmlspecialchars($b['broadcast_type']) ?></span>
    </div>
    <div class="card-body" style="white-space:pre-wrap;margin:16px 0"><?= nl2br(htmlspecialchars($b['content'])) ?></div>
    <div style="display:flex;gap:8px;align-items:center;margin-top:12px">
        <form method="post" style="display:inline"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="react"><input type="hidden" name="broadcast_id" value="<?= (int)$b['id'] ?>"><input type="hidden" name="react_type" value="positive"><button class="react-btn" type="submit"><i class="fas fa-thumbs-up"></i> <?= (int)$b['reactions_positive'] ?></button></form>
        <form method="post" style="display:inline"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="react"><input type="hidden" name="broadcast_id" value="<?= (int)$b['id'] ?>"><input type="hidden" name="react_type" value="negative"><button class="react-btn" type="submit"><i class="fas fa-thumbs-down"></i> <?= (int)$b['reactions_negative'] ?></button></form>
    </div>
</div>

<?php elseif ($tab === 'channels'): ?>
<!-- Channel Grid -->
<div class="ch-grid">
<?php foreach ($channels as $ch): ?>
    <div class="card">
        <div class="ch-type"><?= channelIcon($ch['channel_type']) ?> <?= htmlspecialchars($ch['channel_type']) ?></div>
        <div class="card-title"><?= htmlspecialchars($ch['name']) ?></div>
        <div class="card-body" style="margin-bottom:10px"><?= htmlspecialchars($ch['description'] ?? '') ?></div>
        <div style="display:flex;justify-content:space-between;align-items:center">
            <span class="ch-subs"><i class="fas fa-users"></i> <?= (int)$ch['subs'] ?> subscribers</span>
            <?php if ($ch['subscribed']): ?>
                <form method="post"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="unsubscribe"><input type="hidden" name="channel_id" value="<?= (int)$ch['id'] ?>"><button class="btn btn-danger" type="submit"><i class="fas fa-bell-slash"></i> Unsub</button></form>
            <?php else: ?>
                <form method="post"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="subscribe"><input type="hidden" name="channel_id" value="<?= (int)$ch['id'] ?>"><button class="btn btn-primary" type="submit"><i class="fas fa-bell"></i> Subscribe</button></form>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
</div>

<?php elseif ($tab === 'subs'): ?>
<!-- My Subscriptions -->
<?php if (empty($mySubs)): ?>
    <div class="card" style="text-align:center;color:var(--muted)"><i class="fas fa-bell-slash" style="font-size:32px;margin-bottom:8px"></i><br>No subscriptions yet. <a href="?tab=channels" style="color:var(--accent)">Browse channels</a></div>
<?php else: foreach ($mySubs as $s): ?>
    <div class="card" style="display:flex;justify-content:space-between;align-items:center">
        <div>
            <div class="card-title"><?= htmlspecialchars($s['name']) ?></div>
            <div class="card-meta"><span><i class="fas fa-calendar"></i> Since <?= htmlspecialchars($s['subscribed_at']) ?></span></div>
        </div>
        <form method="post"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="unsubscribe"><input type="hidden" name="channel_id" value="<?= (int)$s['id'] ?>"><button class="btn btn-danger" type="submit"><i class="fas fa-bell-slash"></i> Unsubscribe</button></form>
    </div>
<?php endforeach; endif; ?>

<?php elseif ($tab === 'publish' && $isOfficer): ?>
<!-- Publish Form -->
<div class="card">
    <div class="card-title"><i class="fas fa-pen-nib" style="color:var(--accent)"></i> Publish Broadcast</div>
    <form method="post" style="margin-top:14px">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">
        <input type="hidden" name="action" value="publish">
        <div class="form-group">
            <label>Channel</label>
            <select name="channel_id" required>
                <option value="">Select channel...</option>
                <?php foreach ($channels as $ch): ?><option value="<?= (int)$ch['id'] ?>"><?= htmlspecialchars($ch['name']) ?> (<?= htmlspecialchars($ch['channel_type']) ?>)</option><?php endforeach; ?>
            </select>
        </div>
        <div class="form-group"><label>Title</label><input type="text" name="title" maxlength="300" required></div>
        <div class="form-group"><label>Content</label><textarea name="content" required></textarea></div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
            <div class="form-group">
                <label>Type</label>
                <select name="broadcast_type">
                    <?php foreach (['article','flash','bulletin','editorial','speech','decree'] as $t): ?><option value="<?= $t ?>"><?= ucfirst($t) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Priority</label>
                <select name="priority">
                    <?php foreach (['routine','important','urgent','critical','flash'] as $p): ?><option value="<?= $p ?>"><?= ucfirst($p) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Classification</label>
                <select name="classification">
                    <?php foreach (['public','internal','classified','top_secret'] as $c): ?><option value="<?= $c ?>"><?= ucfirst(str_replace('_',' ',$c)) ?></option><?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group"><label><input type="checkbox" name="is_pinned" value="1"> Pin this broadcast</label></div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Publish Broadcast</button>
    </form>
</div>

<?php else: ?>
<!-- News Feed -->
<?php if (empty($feed)): ?>
    <div class="card" style="text-align:center;color:var(--muted)"><i class="fas fa-satellite-dish" style="font-size:32px;margin-bottom:8px"></i><br>No broadcasts yet. The airwaves are silent.</div>
<?php else: foreach ($feed as $b): ?>
    <div class="card <?= $b['priority']==='flash'?'flash-card':'' ?>">
        <div class="badge-row" style="margin-bottom:6px">
            <?php if ($b['is_pinned']): ?><span class="pinned-tag"><i class="fas fa-thumbtack"></i> Pinned</span><?php endif; ?>
            <?= priorityBadge($b['priority']) ?> <?= classBadge($b['classification']) ?>
            <span style="font-size:12px;color:var(--muted)"><?= channelIcon($b['channel_type']) ?> <?= htmlspecialchars($b['channel_name']) ?></span>
        </div>
        <a href="?id=<?= (int)$b['id'] ?>" style="text-decoration:none"><div class="card-title"><?= htmlspecialchars($b['title']) ?></div></a>
        <div class="card-body"><?= htmlspecialchars(mb_strimwidth($b['content'], 0, 200, '…')) ?></div>
        <div class="card-meta" style="margin-top:8px">
            <span><i class="fas fa-user"></i> #<?= (int)$b['author_client_id'] ?></span>
            <span><i class="fas fa-clock"></i> <?= htmlspecialchars($b['published_at']) ?></span>
            <span><i class="fas fa-eye"></i> <?= number_format((int)$b['views']) ?></span>
            <span><i class="fas fa-thumbs-up"></i> <?= (int)$b['reactions_positive'] ?></span>
            <span><i class="fas fa-thumbs-down"></i> <?= (int)$b['reactions_negative'] ?></span>
        </div>
    </div>
<?php endforeach; endif; ?>
<?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>
