<?php
require_once __DIR__ . '/includes/db-config.inc.php';
$db = getSharedDB();
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/rank-guard.inc.php';
if (empty($_SESSION['csrf_supply'])) $_SESSION['csrf_supply'] = bin2hex(random_bytes(32));
requireRank(1);

$msg = '';
$msgType = '';

// Auto-create military_credits row if not exists
$stmt = $db->prepare("INSERT IGNORE INTO military_credits (client_id, balance, total_earned, total_spent) VALUES (?, 0, 0, 0)");
$stmt->execute([$clientId]);

// Get user's unit
$unitRow = $db->prepare("SELECT unit_id FROM unit_members WHERE client_id = ? AND status = 'active' LIMIT 1");
$unitRow->execute([$clientId]);
$userUnitId = $unitRow->fetchColumn() ?: null;

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_supply'], $_POST['csrf'] ?? '')) {
        $msg = 'Invalid security token.'; $msgType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'requisition') {
            $itemCode = trim($_POST['item_code'] ?? '');
            $qty = max(1, (int)($_POST['quantity'] ?? 1));

            $item = $db->prepare("SELECT * FROM requisition_items WHERE item_code = ? AND is_active = 1");
            $item->execute([$itemCode]);
            $item = $item->fetch(PDO::FETCH_ASSOC);

            if (!$item) {
                $msg = 'Item not found or inactive.'; $msgType = 'error';
            } elseif ($item['required_rank_tier'] > $userRankTier) {
                $msg = 'Insufficient rank for this item.'; $msgType = 'error';
            } elseif ($item['stock'] !== -1 && $item['stock'] < $qty) {
                $msg = 'Insufficient stock.'; $msgType = 'error';
            } else {
                $totalCost = round($item['cost'] * $qty, 2);
                $bal = $db->prepare("SELECT balance FROM military_credits WHERE client_id = ?");
                $bal->execute([$clientId]);
                $balance = (float)$bal->fetchColumn();

                if ($balance < $totalCost) {
                    $msg = 'Insufficient credits. Need ' . number_format($totalCost, 2) . ', have ' . number_format($balance, 2) . '.';
                    $msgType = 'error';
                } else {
                    $db->beginTransaction();
                    try {
                        $reqCode = 'REQ-' . strtoupper(bin2hex(random_bytes(4)));
                        $newBal = round($balance - $totalCost, 2);

                        $db->prepare("UPDATE military_credits SET balance = ?, total_spent = total_spent + ?, last_transaction_at = NOW() WHERE client_id = ?")
                           ->execute([$newBal, $totalCost, $clientId]);

                        $db->prepare("INSERT INTO credit_transactions (client_id, amount, transaction_type, description, reference_type, reference_id, balance_after, created_at) VALUES (?, ?, 'spend', ?, 'requisition', ?, ?, NOW())")
                           ->execute([$clientId, -$totalCost, "Requisition: {$qty}x {$item['item_name']}", $reqCode, $newBal]);

                        $db->prepare("INSERT INTO requisitions (requisition_code, client_id, item_type, item_description, quantity, cost, status, unit_id, requested_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, NOW())")
                           ->execute([$reqCode, $clientId, $item['category'], $item['item_name'], $qty, $totalCost, $userUnitId]);

                        if ($item['stock'] !== -1) {
                            $db->prepare("UPDATE requisition_items SET stock = stock - ? WHERE item_code = ?")->execute([$qty, $itemCode]);
                        }

                        $db->commit();
                        $msg = "Requisition {$reqCode} submitted. {$qty}x {$item['item_name']} — " . number_format($totalCost, 2) . " credits deducted.";
                        $msgType = 'success';
                    } catch (Exception $e) {
                        $db->rollBack();
                        $msg = 'Requisition failed. Try again.'; $msgType = 'error';
                    }
                }
            }
        }

        if ($action === 'resolve' && $userRankTier >= 7) {
            $reqId = (int)($_POST['req_id'] ?? 0);
            $decision = ($_POST['decision'] === 'approved') ? 'approved' : 'denied';
            $notes = trim($_POST['notes'] ?? '');

            $req = $db->prepare("SELECT * FROM requisitions WHERE id = ? AND status = 'pending'");
            $req->execute([$reqId]);
            $req = $req->fetch(PDO::FETCH_ASSOC);

            if (!$req) {
                $msg = 'Requisition not found or already resolved.'; $msgType = 'error';
            } else {
                $db->beginTransaction();
                try {
                    $db->prepare("UPDATE requisitions SET status = ?, approved_by = ?, notes = ?, resolved_at = NOW() WHERE id = ?")
                       ->execute([$decision, $clientId, $notes, $reqId]);

                    if ($decision === 'denied') {
                        $refundAmt = (float)$req['cost'];
                        $bal = $db->prepare("SELECT balance FROM military_credits WHERE client_id = ?");
                        $bal->execute([$req['client_id']]);
                        $curBal = (float)$bal->fetchColumn();
                        $newBal = round($curBal + $refundAmt, 2);

                        $db->prepare("UPDATE military_credits SET balance = ?, total_spent = total_spent - ?, last_transaction_at = NOW() WHERE client_id = ?")
                           ->execute([$newBal, $refundAmt, $req['client_id']]);

                        $db->prepare("INSERT INTO credit_transactions (client_id, amount, transaction_type, description, reference_type, reference_id, balance_after, created_at) VALUES (?, ?, 'bonus', ?, 'requisition', ?, ?, NOW())")
                           ->execute([$req['client_id'], $refundAmt, "Refund: Requisition {$req['requisition_code']} denied", $req['requisition_code'], $newBal]);

                        // Restore stock
                        $itemInfo = $db->prepare("SELECT stock FROM requisition_items WHERE item_code = (SELECT item_type FROM requisitions WHERE id = ?) LIMIT 1");
                        // stock restore by item_description match
                        $db->prepare("UPDATE requisition_items SET stock = stock + ? WHERE item_name = ? AND stock != -1")
                           ->execute([$req['quantity'], $req['item_description']]);
                    } elseif ($decision === 'approved') {
                        $db->prepare("UPDATE requisitions SET status = 'fulfilled' WHERE id = ?")->execute([$reqId]);
                    }

                    $db->commit();
                    $msg = "Requisition {$req['requisition_code']} {$decision}." . ($decision === 'denied' ? ' Credits refunded.' : '');
                    $msgType = 'success';
                } catch (Exception $e) {
                    $db->rollBack();
                    $msg = 'Failed to resolve requisition.'; $msgType = 'error';
                }
            }
        }
    }
}

// Fetch credit balance
$credits = $db->prepare("SELECT * FROM military_credits WHERE client_id = ?");
$credits->execute([$clientId]);
$credits = $credits->fetch(PDO::FETCH_ASSOC);

// Fetch transactions
$txns = $db->prepare("SELECT * FROM credit_transactions WHERE client_id = ? ORDER BY created_at DESC LIMIT 50");
$txns->execute([$clientId]);
$txns = $txns->fetchAll(PDO::FETCH_ASSOC);

// Fetch store items available to user rank
$items = $db->prepare("SELECT * FROM requisition_items WHERE is_active = 1 AND required_rank_tier <= ? ORDER BY category, item_name");
$items->execute([$userRankTier]);
$items = $items->fetchAll(PDO::FETCH_ASSOC);

// My requisitions
$myReqs = $db->prepare("SELECT * FROM requisitions WHERE client_id = ? ORDER BY requested_at DESC LIMIT 30");
$myReqs->execute([$clientId]);
$myReqs = $myReqs->fetchAll(PDO::FETCH_ASSOC);

// Pending requisitions for officers
$pendingReqs = [];
if ($userRankTier >= 7) {
    $pq = $db->prepare("SELECT r.*, mc.balance AS requester_balance FROM requisitions r LEFT JOIN military_credits mc ON mc.client_id = r.client_id WHERE r.status = 'pending' ORDER BY r.requested_at ASC LIMIT 50");
    $pq->execute();
    $pendingReqs = $pq->fetchAll(PDO::FETCH_ASSOC);
}

// Unit inventory
$unitInv = [];
if ($userUnitId) {
    $ui = $db->prepare("SELECT si.*, ri.item_name, ri.category, ri.icon FROM supply_inventory si LEFT JOIN requisition_items ri ON ri.item_code = si.item_code WHERE si.unit_id = ? ORDER BY ri.category, ri.item_name");
    $ui->execute([$userUnitId]);
    $unitInv = $ui->fetchAll(PDO::FETCH_ASSOC);
}

$catColors = ['equipment'=>'#f59e0b','software'=>'#8b5cf6','training'=>'#10b981','service'=>'#3b82f6','resource'=>'#ef4444'];
$typeColors = ['earn'=>'#22c55e','spend'=>'#ef4444','transfer'=>'#3b82f6','bonus'=>'#eab308','penalty'=>'#dc2626'];
$statusColors = ['pending'=>'#f59e0b','approved'=>'#22c55e','denied'=>'#ef4444','fulfilled'=>'#3b82f6'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Supply &amp; Economy — GoSiteMe Military Command</title>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{background:#0f172a;color:#e2e8f0;font-family:'Segoe UI',system-ui,sans-serif;min-height:100vh}
.wrap{max-width:1200px;margin:0 auto;padding:24px 16px}
h1{font-size:1.8rem;margin-bottom:8px}
h2{font-size:1.2rem;color:#94a3b8;margin-bottom:16px}
h3{font-size:1rem;margin-bottom:12px;color:#cbd5e1}
.card{background:#1e293b;border:1px solid #334155;border-radius:10px;padding:20px;margin-bottom:20px}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.grid3{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px}
.badge{display:inline-block;padding:2px 10px;border-radius:12px;font-size:.75rem;font-weight:600;text-transform:uppercase}
.btn{padding:8px 18px;border:none;border-radius:6px;cursor:pointer;font-size:.85rem;font-weight:600;transition:opacity .2s}
.btn:hover{opacity:.85}
.btn-blue{background:#3b82f6;color:#fff}
.btn-green{background:#22c55e;color:#fff}
.btn-red{background:#ef4444;color:#fff}
.btn-sm{padding:5px 12px;font-size:.78rem}
table{width:100%;border-collapse:collapse}
th,td{padding:8px 10px;text-align:left;border-bottom:1px solid #334155;font-size:.85rem}
th{color:#94a3b8;font-weight:600;font-size:.78rem;text-transform:uppercase}
input,select,textarea{background:#0f172a;border:1px solid #334155;color:#e2e8f0;padding:8px 12px;border-radius:6px;font-size:.85rem;width:100%}
input:focus,select:focus,textarea:focus{outline:none;border-color:#3b82f6}
.msg{padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:.9rem}
.msg-success{background:rgba(34,197,94,.15);border:1px solid #22c55e;color:#86efac}
.msg-error{background:rgba(239,68,68,.15);border:1px solid #ef4444;color:#fca5a5}
.stat{text-align:center}
.stat .val{font-size:1.5rem;font-weight:700;color:#3b82f6}
.stat .lbl{font-size:.75rem;color:#94a3b8;margin-top:4px}
.tabs{display:flex;gap:4px;margin-bottom:16px;flex-wrap:wrap}
.tab{padding:8px 16px;border-radius:6px 6px 0 0;cursor:pointer;font-size:.85rem;font-weight:600;background:#1e293b;border:1px solid #334155;border-bottom:none;color:#94a3b8}
.tab.active{background:#334155;color:#e2e8f0}
.tab-content{display:none}
.tab-content.active{display:block}
.store-card{background:#1e293b;border:1px solid #334155;border-radius:10px;padding:16px;display:flex;flex-direction:column;gap:8px}
.store-card .icon-area{font-size:2rem;text-align:center;padding:8px 0;color:#3b82f6}
.store-card .name{font-weight:700;font-size:.95rem}
.store-card .cost{color:#f59e0b;font-weight:700;font-size:1.1rem}
.store-card .meta{font-size:.78rem;color:#94a3b8}
.topbar{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:20px}
.back-link{color:#3b82f6;text-decoration:none;font-size:.9rem}
.back-link:hover{text-decoration:underline}
@media(max-width:768px){.grid2{grid-template-columns:1fr}.grid3{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="wrap">
<div class="topbar">
    <div><h1><i class="fas fa-boxes-stacked"></i> Supply &amp; Economy</h1><h2>Quartermaster Operations — <?= htmlspecialchars($userName) ?> [<?= htmlspecialchars($userRankCode) ?>]</h2></div>
    <a href="military-hq.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to HQ</a>
</div>

<?php if ($msg): ?>
<div class="msg msg-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<!-- Credit Balance -->
<div class="card">
    <h3><i class="fas fa-coins"></i> Credit Balance</h3>
    <div class="grid2" style="margin-top:12px">
        <div class="stat"><div class="val" style="color:#22c55e"><?= number_format((float)$credits['balance'], 2) ?></div><div class="lbl">Available Credits</div></div>
        <div class="stat"><div class="val" style="color:#3b82f6"><?= number_format((float)$credits['total_earned'], 2) ?></div><div class="lbl">Total Earned</div></div>
    </div>
    <div style="text-align:center;margin-top:8px;font-size:.8rem;color:#64748b">
        Total Spent: <?= number_format((float)$credits['total_spent'], 2) ?>
        <?php if ($credits['last_transaction_at']): ?> &middot; Last Activity: <?= date('M j, g:ia', strtotime($credits['last_transaction_at'])) ?><?php endif; ?>
    </div>
</div>

<!-- Tabs -->
<div class="tabs">
    <div class="tab active" data-tab="store"><i class="fas fa-store"></i> Quartermaster Store</div>
    <div class="tab" data-tab="transactions"><i class="fas fa-receipt"></i> Transactions</div>
    <div class="tab" data-tab="myreqs"><i class="fas fa-clipboard-list"></i> My Requisitions</div>
    <?php if ($userRankTier >= 7): ?><div class="tab" data-tab="approve"><i class="fas fa-gavel"></i> Approve/Deny (<?= count($pendingReqs) ?>)</div><?php endif; ?>
    <?php if ($userUnitId): ?><div class="tab" data-tab="inventory"><i class="fas fa-warehouse"></i> Unit Inventory</div><?php endif; ?>
</div>

<!-- Store Tab -->
<div class="tab-content active" id="tab-store">
<div class="card">
    <h3><i class="fas fa-store-alt"></i> Quartermaster Store</h3>
    <?php if (empty($items)): ?>
    <p style="color:#64748b;font-size:.9rem">No items available for your rank.</p>
    <?php else: ?>
    <div class="grid3">
    <?php foreach ($items as $it): ?>
        <div class="store-card">
            <div class="icon-area"><?php if ($it['icon']): ?><i class="<?= htmlspecialchars($it['icon']) ?>"></i><?php else: ?><i class="fas fa-box"></i><?php endif; ?></div>
            <div class="name"><?= htmlspecialchars($it['item_name']) ?></div>
            <div><span class="badge" style="background:<?= $catColors[$it['category']] ?? '#3b82f6' ?>;color:#000"><?= htmlspecialchars($it['category']) ?></span></div>
            <div class="meta"><?= htmlspecialchars($it['description'] ?: 'No description') ?></div>
            <div class="cost"><i class="fas fa-coins"></i> <?= number_format((float)$it['cost'], 2) ?></div>
            <div class="meta">Stock: <?= $it['stock'] == -1 ? '<span style="color:#22c55e">Unlimited</span>' : (int)$it['stock'] ?> &middot; Rank Req: Tier <?= (int)$it['required_rank_tier'] ?></div>
            <?php if ($it['stock'] == -1 || $it['stock'] > 0): ?>
            <form method="POST" style="display:flex;gap:6px;margin-top:4px">
                <input type="hidden" name="csrf" value="<?= $_SESSION['csrf_supply'] ?>">
                <input type="hidden" name="action" value="requisition">
                <input type="hidden" name="item_code" value="<?= htmlspecialchars($it['item_code']) ?>">
                <input type="number" name="quantity" value="1" min="1" max="<?= $it['stock'] == -1 ? 99 : (int)$it['stock'] ?>" style="width:60px">
                <button type="submit" class="btn btn-blue btn-sm"><i class="fas fa-cart-plus"></i> Requisition</button>
            </form>
            <?php else: ?>
            <div style="color:#ef4444;font-size:.8rem;font-weight:600">OUT OF STOCK</div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
</div>

<!-- Transactions Tab -->
<div class="tab-content" id="tab-transactions">
<div class="card">
    <h3><i class="fas fa-receipt"></i> Transaction History (Last 50)</h3>
    <?php if (empty($txns)): ?>
    <p style="color:#64748b;font-size:.9rem">No transactions yet.</p>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table>
        <tr><th>Date</th><th>Type</th><th>Amount</th><th>Description</th><th>Balance After</th></tr>
        <?php foreach ($txns as $tx): ?>
        <tr>
            <td><?= date('M j, g:ia', strtotime($tx['created_at'])) ?></td>
            <td><span class="badge" style="background:<?= $typeColors[$tx['transaction_type']] ?? '#64748b' ?>;color:#000"><?= htmlspecialchars($tx['transaction_type']) ?></span></td>
            <td style="color:<?= (float)$tx['amount'] >= 0 ? '#22c55e' : '#ef4444' ?>;font-weight:600"><?= ((float)$tx['amount'] >= 0 ? '+' : '') . number_format((float)$tx['amount'], 2) ?></td>
            <td><?= htmlspecialchars($tx['description']) ?></td>
            <td style="color:#94a3b8"><?= number_format((float)$tx['balance_after'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    </div>
    <?php endif; ?>
</div>
</div>

<!-- My Requisitions Tab -->
<div class="tab-content" id="tab-myreqs">
<div class="card">
    <h3><i class="fas fa-clipboard-list"></i> My Requisitions</h3>
    <?php if (empty($myReqs)): ?>
    <p style="color:#64748b;font-size:.9rem">No requisitions submitted.</p>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table>
        <tr><th>Code</th><th>Item</th><th>Qty</th><th>Cost</th><th>Status</th><th>Requested</th><th>Resolved</th></tr>
        <?php foreach ($myReqs as $rq): ?>
        <tr>
            <td style="font-family:monospace;font-size:.8rem"><?= htmlspecialchars($rq['requisition_code']) ?></td>
            <td><?= htmlspecialchars($rq['item_description']) ?></td>
            <td><?= (int)$rq['quantity'] ?></td>
            <td><?= number_format((float)$rq['cost'], 2) ?></td>
            <td><span class="badge" style="background:<?= $statusColors[$rq['status']] ?? '#64748b' ?>;color:#000"><?= htmlspecialchars($rq['status']) ?></span></td>
            <td><?= date('M j, g:ia', strtotime($rq['requested_at'])) ?></td>
            <td><?= $rq['resolved_at'] ? date('M j, g:ia', strtotime($rq['resolved_at'])) : '—' ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    </div>
    <?php endif; ?>
</div>
</div>

<!-- Approve/Deny Tab (Officers Tier 7+) -->
<?php if ($userRankTier >= 7): ?>
<div class="tab-content" id="tab-approve">
<div class="card">
    <h3><i class="fas fa-gavel"></i> Pending Requisitions — Officer Review</h3>
    <?php if (empty($pendingReqs)): ?>
    <p style="color:#64748b;font-size:.9rem">No pending requisitions.</p>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table>
        <tr><th>Code</th><th>Requester</th><th>Item</th><th>Qty</th><th>Cost</th><th>Requested</th><th>Action</th></tr>
        <?php foreach ($pendingReqs as $pr): ?>
        <tr>
            <td style="font-family:monospace;font-size:.8rem"><?= htmlspecialchars($pr['requisition_code']) ?></td>
            <td>Client #<?= (int)$pr['client_id'] ?></td>
            <td><?= htmlspecialchars($pr['item_description']) ?></td>
            <td><?= (int)$pr['quantity'] ?></td>
            <td><?= number_format((float)$pr['cost'], 2) ?></td>
            <td><?= date('M j, g:ia', strtotime($pr['requested_at'])) ?></td>
            <td>
                <form method="POST" style="display:flex;gap:4px;align-items:center;flex-wrap:wrap">
                    <input type="hidden" name="csrf" value="<?= $_SESSION['csrf_supply'] ?>">
                    <input type="hidden" name="action" value="resolve">
                    <input type="hidden" name="req_id" value="<?= (int)$pr['id'] ?>">
                    <input type="text" name="notes" placeholder="Notes..." style="width:120px;font-size:.78rem">
                    <button type="submit" name="decision" value="approved" class="btn btn-green btn-sm"><i class="fas fa-check"></i></button>
                    <button type="submit" name="decision" value="denied" class="btn btn-red btn-sm"><i class="fas fa-times"></i></button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    </div>
    <?php endif; ?>
</div>
</div>
<?php endif; ?>

<!-- Unit Inventory Tab -->
<?php if ($userUnitId): ?>
<div class="tab-content" id="tab-inventory">
<div class="card">
    <h3><i class="fas fa-warehouse"></i> Unit Inventory — Unit #<?= (int)$userUnitId ?></h3>
    <?php if (empty($unitInv)): ?>
    <p style="color:#64748b;font-size:.9rem">No inventory on record for this unit.</p>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table>
        <tr><th>Item</th><th>Code</th><th>Category</th><th>Quantity</th><th>Last Resupply</th></tr>
        <?php foreach ($unitInv as $inv): ?>
        <tr>
            <td><?php if ($inv['icon']): ?><i class="<?= htmlspecialchars($inv['icon']) ?>"></i> <?php endif; ?><?= htmlspecialchars($inv['item_name'] ?: $inv['item_code']) ?></td>
            <td style="font-family:monospace;font-size:.8rem"><?= htmlspecialchars($inv['item_code']) ?></td>
            <td><span class="badge" style="background:<?= $catColors[$inv['category']] ?? '#64748b' ?>;color:#000"><?= htmlspecialchars($inv['category'] ?: '—') ?></span></td>
            <td style="font-weight:600"><?= (int)$inv['quantity'] ?></td>
            <td><?= $inv['last_resupply'] ? date('M j, Y', strtotime($inv['last_resupply'])) : '—' ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    </div>
    <?php endif; ?>
</div>
</div>
<?php endif; ?>

</div><!-- /wrap -->

<script>
document.querySelectorAll('.tab').forEach(t => {
    t.addEventListener('click', () => {
        document.querySelectorAll('.tab').forEach(x => x.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(x => x.classList.remove('active'));
        t.classList.add('active');
        document.getElementById('tab-' + t.dataset.tab).classList.add('active');
    });
});
</script>
</body>
</html>
