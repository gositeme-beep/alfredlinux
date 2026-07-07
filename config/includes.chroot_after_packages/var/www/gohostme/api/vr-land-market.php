<?php
/**
 * VR Land Marketplace API — Phase 4a
 * Buy, sell, and trade virtual land plots in the MetaDome
 * 
 * Actions: marketplace, plot-detail, my-plots, list-plot, buy-plot, 
 *          cancel-listing, plot-stats, admin-mint-plots
 */
define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

apiRateLimit(30, 60, 'vr-land-market');
session_start();

// ── Constants ──
const LAND_FEE_RATE     = 0.01;   // 1% marketplace fee
const ZONES = ['downtown', 'residential', 'commercial', 'wilderness', 'beachfront', 'skyline'];
const PLOT_SIZES = ['small', 'medium', 'large', 'estate'];
const PLOT_SIZE_GRID = [
    'small'  => ['w' => 1, 'h' => 1],
    'medium' => ['w' => 2, 'h' => 2],
    'large'  => ['w' => 3, 'h' => 3],
    'estate' => ['w' => 5, 'h' => 5],
];
// Base prices in GSM per plot size
const BASE_PRICES = [
    'small'  => 50,
    'medium' => 180,
    'large'  => 500,
    'estate' => 2000,
];
// Zone multipliers
const ZONE_MULTIPLIER = [
    'downtown'    => 3.0,
    'skyline'     => 2.5,
    'beachfront'  => 2.0,
    'commercial'  => 1.5,
    'residential' => 1.0,
    'wilderness'  => 0.6,
];

// ── Auth ──
function landRequireAuth(): array {
    if (!empty($_SESSION['logged_in']) && !empty($_SESSION['client_id'])) {
        return [
            'client_id' => (int)$_SESSION['client_id'],
            'name'      => $_SESSION['client_name'] ?? '',
            'email'     => $_SESSION['client_email'] ?? '',
        ];
    }
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/^Bearer\s+(.+)$/', $auth, $m)) {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, CONCAT(firstname,' ',lastname) AS name, email 
                              FROM clients WHERE api_key = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$m[1]]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($u) return ['client_id' => (int)$u['id'], 'name' => $u['name'], 'email' => $u['email']];
    }
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

function landIsCommander(): bool {
    return ($_SESSION['client_id'] ?? 0) == 33;
}

// ── Table Setup ──
function ensureVRTables(PDO $db): void {
    // Plots — every plot in the world
    $db->exec("CREATE TABLE IF NOT EXISTS vr_land_plots (
        id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        zone            ENUM('downtown','residential','commercial','wilderness','beachfront','skyline') NOT NULL,
        plot_size       ENUM('small','medium','large','estate') NOT NULL DEFAULT 'small',
        coord_x         INT NOT NULL,
        coord_y         INT NOT NULL,
        coord_z         INT NOT NULL DEFAULT 0,
        owner_client_id INT UNSIGNED DEFAULT NULL,
        plot_name       VARCHAR(100) DEFAULT NULL,
        description     TEXT DEFAULT NULL,
        improvements    JSON DEFAULT NULL,
        rarity          ENUM('common','uncommon','rare','epic','legendary') DEFAULT 'common',
        mint_price_gsm  DECIMAL(20,9) NOT NULL DEFAULT 0,
        is_listed       TINYINT(1) NOT NULL DEFAULT 0,
        listing_price   DECIMAL(20,9) DEFAULT NULL,
        listing_currency ENUM('gsm','sol') DEFAULT 'gsm',
        listed_at       DATETIME DEFAULT NULL,
        purchased_at    DATETIME DEFAULT NULL,
        created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_coords (zone, coord_x, coord_y, coord_z),
        KEY idx_owner (owner_client_id),
        KEY idx_listed (is_listed, zone),
        KEY idx_zone_size (zone, plot_size)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Transaction log for all land trades
    $db->exec("CREATE TABLE IF NOT EXISTS vr_land_transactions (
        id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        plot_id         INT UNSIGNED NOT NULL,
        seller_id       INT UNSIGNED DEFAULT NULL,
        buyer_id        INT UNSIGNED NOT NULL,
        price_gsm       DECIMAL(20,9) NOT NULL DEFAULT 0,
        fee_gsm         DECIMAL(20,9) NOT NULL DEFAULT 0,
        currency        ENUM('gsm','sol') DEFAULT 'gsm',
        price_sol       BIGINT DEFAULT NULL,
        tx_type         ENUM('mint','sale','transfer') NOT NULL,
        created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY idx_plot (plot_id),
        KEY idx_buyer (buyer_id),
        KEY idx_seller (seller_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Offers system
    $db->exec("CREATE TABLE IF NOT EXISTS vr_land_offers (
        id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        plot_id         INT UNSIGNED NOT NULL,
        offerer_id      INT UNSIGNED NOT NULL,
        offer_gsm       DECIMAL(20,9) NOT NULL,
        status          ENUM('pending','accepted','rejected','expired','cancelled') DEFAULT 'pending',
        expires_at      DATETIME NOT NULL,
        created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY idx_plot (plot_id, status),
        KEY idx_offerer (offerer_id, status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// ── GSM Helpers (same atomic pattern as gsm-economy.php) ──
function getAvailableGSM(PDO $db, int $clientId): float {
    $stmt = $db->prepare("SELECT available_balance FROM crypto_gsm_balances WHERE client_id = ? FOR UPDATE");
    $stmt->execute([$clientId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (float)$row['available_balance'] : 0.0;
}

function debitGSMForLand(PDO $db, int $clientId, float $amount, string $desc, int $plotId): bool {
    $stmt = $db->prepare("UPDATE crypto_gsm_balances 
                          SET available_balance = available_balance - ?, 
                              total_spent = total_spent + ?,
                              updated_at = NOW()
                          WHERE client_id = ? AND available_balance >= ?");
    $stmt->execute([$amount, $amount, $clientId, $amount]);
    if ($stmt->rowCount() === 0) return false;

    $db->prepare("INSERT INTO crypto_gsm_ledger (client_id, tx_type, amount, balance_after, description, reference_id, created_at)
                  VALUES (?, 'vr_land', ?, (SELECT available_balance FROM crypto_gsm_balances WHERE client_id = ?), ?, ?, NOW())")
        ->execute([$clientId, -$amount, $clientId, $desc, 'plot_' . $plotId]);
    return true;
}

function creditGSMForLand(PDO $db, int $clientId, float $amount, string $desc, int $plotId): void {
    $db->prepare("INSERT INTO crypto_gsm_balances (client_id, available_balance, total_earned, updated_at)
                  VALUES (?, ?, ?, NOW())
                  ON DUPLICATE KEY UPDATE available_balance = available_balance + ?, total_earned = total_earned + ?, updated_at = NOW()")
        ->execute([$clientId, $amount, $amount, $amount, $amount]);

    $db->prepare("INSERT INTO crypto_gsm_ledger (client_id, tx_type, amount, balance_after, description, reference_id, created_at)
                  VALUES (?, 'vr_land', ?, (SELECT available_balance FROM crypto_gsm_balances WHERE client_id = ?), ?, ?, NOW())")
        ->execute([$clientId, $amount, $clientId, $desc, 'plot_' . $plotId]);
}

// ── Main ──
$db = getDB();
if (!$db) { echo json_encode(['error' => 'Database unavailable']); exit; }
ensureVRTables($db);

$action = $_GET['action'] ?? '';

switch ($action) {

    // ── Browse marketplace (public) ──
    case 'marketplace':
        $zone     = $_GET['zone'] ?? '';
        $size     = $_GET['size'] ?? '';
        $sort     = $_GET['sort'] ?? 'newest';
        $page     = max(1, (int)($_GET['page'] ?? 1));
        $limit    = min(50, max(10, (int)($_GET['limit'] ?? 20)));
        $offset   = ($page - 1) * $limit;

        $where = ['p.is_listed = 1'];
        $params = [];
        if ($zone && in_array($zone, ZONES, true)) { $where[] = 'p.zone = ?'; $params[] = $zone; }
        if ($size && in_array($size, PLOT_SIZES, true)) { $where[] = 'p.plot_size = ?'; $params[] = $size; }

        $orderMap = [
            'newest'     => 'p.listed_at DESC',
            'price_asc'  => 'p.listing_price ASC',
            'price_desc' => 'p.listing_price DESC',
            'rarity'     => "FIELD(p.rarity,'legendary','epic','rare','uncommon','common')",
        ];
        $order = $orderMap[$sort] ?? $orderMap['newest'];
        $wSQL = implode(' AND ', $where);

        $countStmt = $db->prepare("SELECT COUNT(*) FROM vr_land_plots p WHERE $wSQL");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $db->prepare("SELECT p.id, p.zone, p.plot_size, p.coord_x, p.coord_y, p.coord_z,
                                     p.plot_name, p.rarity, p.listing_price, p.listing_currency,
                                     p.listed_at, p.improvements,
                                     c.firstname AS owner_name
                              FROM vr_land_plots p
                              LEFT JOIN clients c ON c.id = p.owner_client_id
                              WHERE $wSQL
                              ORDER BY $order
                              LIMIT $limit OFFSET $offset");
        $stmt->execute($params);
        $plots = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($plots as &$p) { $p['improvements'] = json_decode($p['improvements'], true); }

        echo json_encode([
            'success' => true,
            'listings' => $plots,
            'total' => $total,
            'page' => $page,
            'pages' => ceil($total / $limit),
            'zones' => ZONES,
            'sizes' => PLOT_SIZES,
        ]);
        break;

    // ── Plot detail (public) ──
    case 'plot-detail':
        $plotId = (int)($_GET['plot_id'] ?? 0);
        if ($plotId <= 0) { echo json_encode(['error' => 'Invalid plot_id']); break; }

        $stmt = $db->prepare("SELECT p.*, c.firstname AS owner_name
                              FROM vr_land_plots p
                              LEFT JOIN clients c ON c.id = p.owner_client_id
                              WHERE p.id = ?");
        $stmt->execute([$plotId]);
        $plot = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$plot) { echo json_encode(['error' => 'Plot not found']); break; }
        $plot['improvements'] = json_decode($plot['improvements'], true);

        // Recent transactions for this plot
        $txStmt = $db->prepare("SELECT t.*, c.firstname AS buyer_name
                                FROM vr_land_transactions t
                                LEFT JOIN clients c ON c.id = t.buyer_id
                                WHERE t.plot_id = ? ORDER BY t.created_at DESC LIMIT 10");
        $txStmt->execute([$plotId]);
        $transactions = $txStmt->fetchAll(PDO::FETCH_ASSOC);

        // Pending offers
        $offersStmt = $db->prepare("SELECT COUNT(*) FROM vr_land_offers WHERE plot_id = ? AND status = 'pending'");
        $offersStmt->execute([$plotId]);
        $offerCount = (int)$offersStmt->fetchColumn();

        echo json_encode([
            'success' => true,
            'plot' => $plot,
            'transactions' => $transactions,
            'offer_count' => $offerCount,
        ]);
        break;

    // ── My plots ──
    case 'my-plots':
        $user = landRequireAuth();
        $stmt = $db->prepare("SELECT id, zone, plot_size, coord_x, coord_y, coord_z, plot_name,
                                     rarity, is_listed, listing_price, listing_currency,
                                     improvements, purchased_at
                              FROM vr_land_plots WHERE owner_client_id = ? ORDER BY purchased_at DESC");
        $stmt->execute([$user['client_id']]);
        $plots = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($plots as &$p) { $p['improvements'] = json_decode($p['improvements'], true); }

        // Portfolio value
        $valueStmt = $db->prepare("SELECT COALESCE(SUM(listing_price),0) as listed_value,
                                          COUNT(*) as total_plots,
                                          SUM(is_listed) as listed_plots
                                   FROM vr_land_plots WHERE owner_client_id = ?");
        $valueStmt->execute([$user['client_id']]);
        $portfolio = $valueStmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'plots' => $plots,
            'portfolio' => $portfolio,
        ]);
        break;

    // ── List a plot for sale ──
    case 'list-plot':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        requireCSRF();
        $user = landRequireAuth();
        $data = json_decode(file_get_contents('php://input'), true) ?: [];

        $plotId = (int)($data['plot_id'] ?? 0);
        $price  = (float)($data['price'] ?? 0);
        $currency = ($data['currency'] ?? 'gsm') === 'sol' ? 'sol' : 'gsm';

        if ($plotId <= 0 || $price <= 0) { echo json_encode(['error' => 'Invalid plot or price']); break; }

        $stmt = $db->prepare("SELECT id, owner_client_id, is_listed FROM vr_land_plots WHERE id = ?");
        $stmt->execute([$plotId]);
        $plot = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$plot) { echo json_encode(['error' => 'Plot not found']); break; }
        if ((int)$plot['owner_client_id'] !== $user['client_id']) { echo json_encode(['error' => 'You do not own this plot']); break; }
        if ($plot['is_listed']) { echo json_encode(['error' => 'Already listed']); break; }

        $db->prepare("UPDATE vr_land_plots SET is_listed = 1, listing_price = ?, listing_currency = ?, listed_at = NOW() WHERE id = ?")
           ->execute([$price, $currency, $plotId]);

        echo json_encode(['success' => true, 'message' => 'Plot listed for ' . number_format($price, 4) . ' ' . strtoupper($currency)]);
        break;

    // ── Cancel listing ──
    case 'cancel-listing':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        requireCSRF();
        $user = landRequireAuth();
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $plotId = (int)($data['plot_id'] ?? 0);

        $stmt = $db->prepare("SELECT owner_client_id, is_listed FROM vr_land_plots WHERE id = ?");
        $stmt->execute([$plotId]);
        $plot = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$plot || (int)$plot['owner_client_id'] !== $user['client_id']) { echo json_encode(['error' => 'Not your plot']); break; }
        if (!$plot['is_listed']) { echo json_encode(['error' => 'Not listed']); break; }

        $db->prepare("UPDATE vr_land_plots SET is_listed = 0, listing_price = NULL, listing_currency = NULL, listed_at = NULL WHERE id = ?")
           ->execute([$plotId]);

        echo json_encode(['success' => true, 'message' => 'Listing cancelled']);
        break;

    // ── Buy a listed plot ──
    case 'buy-plot':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        requireCSRF();
        $user = landRequireAuth();
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $plotId = (int)($data['plot_id'] ?? 0);

        if ($plotId <= 0) { echo json_encode(['error' => 'Invalid plot_id']); break; }

        $db->beginTransaction();
        try {
            // Lock the plot row
            $stmt = $db->prepare("SELECT * FROM vr_land_plots WHERE id = ? FOR UPDATE");
            $stmt->execute([$plotId]);
            $plot = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$plot) { $db->rollBack(); echo json_encode(['error' => 'Plot not found']); break; }
            if (!$plot['is_listed']) { $db->rollBack(); echo json_encode(['error' => 'Plot is not listed for sale']); break; }
            if ((int)$plot['owner_client_id'] === $user['client_id']) { $db->rollBack(); echo json_encode(['error' => 'Cannot buy your own plot']); break; }

            $price = (float)$plot['listing_price'];
            $currency = $plot['listing_currency'];
            $sellerId = (int)$plot['owner_client_id'];

            if ($currency === 'gsm') {
                // Check buyer has enough GSM
                $available = getAvailableGSM($db, $user['client_id']);
                if ($available < $price) {
                    $db->rollBack();
                    echo json_encode(['error' => 'Insufficient GSM balance. Need ' . number_format($price, 4) . ', have ' . number_format($available, 4)]);
                    break;
                }

                $fee = round($price * LAND_FEE_RATE, 9);
                $sellerReceives = $price - $fee;

                // Debit buyer
                if (!debitGSMForLand($db, $user['client_id'], $price, 'Bought plot #' . $plotId . ' in ' . $plot['zone'], $plotId)) {
                    $db->rollBack();
                    echo json_encode(['error' => 'Debit failed — insufficient balance']);
                    break;
                }

                // Credit seller (minus fee)
                if ($sellerId > 0 && $sellerReceives > 0) {
                    creditGSMForLand($db, $sellerId, $sellerReceives, 'Sold plot #' . $plotId, $plotId);
                }
            }
            // SOL purchases would require external verification — not implemented yet

            // Transfer ownership
            $db->prepare("UPDATE vr_land_plots 
                          SET owner_client_id = ?, is_listed = 0, listing_price = NULL, 
                              listing_currency = NULL, listed_at = NULL, purchased_at = NOW()
                          WHERE id = ?")
               ->execute([$user['client_id'], $plotId]);

            // Record transaction
            $db->prepare("INSERT INTO vr_land_transactions (plot_id, seller_id, buyer_id, price_gsm, fee_gsm, currency, tx_type, created_at)
                          VALUES (?, ?, ?, ?, ?, ?, 'sale', NOW())")
               ->execute([$plotId, $sellerId, $user['client_id'], $price, $fee ?? 0, $currency]);

            // Cancel any pending offers on this plot
            $db->prepare("UPDATE vr_land_offers SET status = 'expired' WHERE plot_id = ? AND status = 'pending'")
               ->execute([$plotId]);

            $db->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Purchased plot #' . $plotId . ' for ' . number_format($price, 4) . ' GSM',
                'fee' => $fee ?? 0,
                'plot_id' => $plotId,
                'zone' => $plot['zone'],
            ]);
        } catch (\Exception $e) {
            $db->rollBack();
            echo json_encode(['error' => 'Purchase failed: ' . $e->getMessage()]);
        }
        break;

    // ── Make an offer on a plot ──
    case 'make-offer':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        requireCSRF();
        $user = landRequireAuth();
        $data = json_decode(file_get_contents('php://input'), true) ?: [];

        $plotId   = (int)($data['plot_id'] ?? 0);
        $offerGSM = (float)($data['offer_gsm'] ?? 0);
        $expHours = min(168, max(1, (int)($data['expires_hours'] ?? 24)));

        if ($plotId <= 0 || $offerGSM <= 0) { echo json_encode(['error' => 'Invalid offer']); break; }

        $stmt = $db->prepare("SELECT owner_client_id FROM vr_land_plots WHERE id = ?");
        $stmt->execute([$plotId]);
        $plot = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$plot) { echo json_encode(['error' => 'Plot not found']); break; }
        if ((int)$plot['owner_client_id'] === $user['client_id']) { echo json_encode(['error' => 'Cannot offer on your own plot']); break; }

        // Check existing pending offers from this user on this plot
        $existStmt = $db->prepare("SELECT COUNT(*) FROM vr_land_offers WHERE plot_id = ? AND offerer_id = ? AND status = 'pending'");
        $existStmt->execute([$plotId, $user['client_id']]);
        if ((int)$existStmt->fetchColumn() > 0) { echo json_encode(['error' => 'You already have a pending offer']); break; }

        $db->prepare("INSERT INTO vr_land_offers (plot_id, offerer_id, offer_gsm, expires_at, created_at)
                      VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? HOUR), NOW())")
           ->execute([$plotId, $user['client_id'], $offerGSM, $expHours]);

        echo json_encode(['success' => true, 'message' => 'Offer placed: ' . number_format($offerGSM, 4) . ' GSM']);
        break;

    // ── Accept an offer (plot owner) ──
    case 'accept-offer':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        requireCSRF();
        $user = landRequireAuth();
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $offerId = (int)($data['offer_id'] ?? 0);

        $db->beginTransaction();
        try {
            $offerStmt = $db->prepare("SELECT o.*, p.owner_client_id, p.zone 
                                       FROM vr_land_offers o 
                                       JOIN vr_land_plots p ON p.id = o.plot_id 
                                       WHERE o.id = ? AND o.status = 'pending' FOR UPDATE");
            $offerStmt->execute([$offerId]);
            $offer = $offerStmt->fetch(PDO::FETCH_ASSOC);

            if (!$offer) { $db->rollBack(); echo json_encode(['error' => 'Offer not found or expired']); break; }
            if ((int)$offer['owner_client_id'] !== $user['client_id']) { $db->rollBack(); echo json_encode(['error' => 'Not your plot']); break; }
            if (strtotime($offer['expires_at']) < time()) {
                $db->prepare("UPDATE vr_land_offers SET status = 'expired' WHERE id = ?")->execute([$offerId]);
                $db->commit();
                echo json_encode(['error' => 'Offer has expired']);
                break;
            }

            $price = (float)$offer['offer_gsm'];
            $buyerId = (int)$offer['offerer_id'];
            $plotId = (int)$offer['plot_id'];
            $fee = round($price * LAND_FEE_RATE, 9);
            $sellerReceives = $price - $fee;

            // Debit buyer
            $available = getAvailableGSM($db, $buyerId);
            if ($available < $price) {
                $db->prepare("UPDATE vr_land_offers SET status = 'cancelled' WHERE id = ?")->execute([$offerId]);
                $db->commit();
                echo json_encode(['error' => 'Buyer has insufficient balance']);
                break;
            }
            if (!debitGSMForLand($db, $buyerId, $price, 'Bought plot #' . $plotId . ' (offer accepted)', $plotId)) {
                $db->rollBack();
                echo json_encode(['error' => 'Debit failed']);
                break;
            }

            // Credit seller
            if ($sellerReceives > 0) {
                creditGSMForLand($db, $user['client_id'], $sellerReceives, 'Sold plot #' . $plotId . ' (offer)', $plotId);
            }

            // Transfer ownership
            $db->prepare("UPDATE vr_land_plots SET owner_client_id = ?, is_listed = 0, listing_price = NULL, 
                          listing_currency = NULL, listed_at = NULL, purchased_at = NOW() WHERE id = ?")
               ->execute([$buyerId, $plotId]);

            // Record transaction
            $db->prepare("INSERT INTO vr_land_transactions (plot_id, seller_id, buyer_id, price_gsm, fee_gsm, currency, tx_type, created_at)
                          VALUES (?, ?, ?, ?, ?, 'gsm', 'sale', NOW())")
               ->execute([$plotId, $user['client_id'], $buyerId, $price, $fee]);

            // Mark offer accepted, others expired
            $db->prepare("UPDATE vr_land_offers SET status = 'accepted' WHERE id = ?")->execute([$offerId]);
            $db->prepare("UPDATE vr_land_offers SET status = 'expired' WHERE plot_id = ? AND status = 'pending' AND id != ?")
               ->execute([$plotId, $offerId]);

            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Offer accepted — plot sold for ' . number_format($price, 4) . ' GSM']);
        } catch (\Exception $e) {
            $db->rollBack();
            echo json_encode(['error' => 'Accept failed: ' . $e->getMessage()]);
        }
        break;

    // ── View offers on my plot ──
    case 'plot-offers':
        $user = landRequireAuth();
        $plotId = (int)($_GET['plot_id'] ?? 0);

        $stmt = $db->prepare("SELECT p.owner_client_id FROM vr_land_plots WHERE id = ?");
        $stmt->execute([$plotId]);
        $plot = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$plot || (int)$plot['owner_client_id'] !== $user['client_id']) { echo json_encode(['error' => 'Not your plot']); break; }

        $stmt = $db->prepare("SELECT o.id, o.offer_gsm, o.status, o.expires_at, o.created_at, c.firstname AS offerer_name
                              FROM vr_land_offers o
                              JOIN clients c ON c.id = o.offerer_id
                              WHERE o.plot_id = ? ORDER BY o.offer_gsm DESC");
        $stmt->execute([$plotId]);
        echo json_encode(['success' => true, 'offers' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    // ── Name / describe your plot ──
    case 'update-plot':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        requireCSRF();
        $user = landRequireAuth();
        $data = json_decode(file_get_contents('php://input'), true) ?: [];

        $plotId  = (int)($data['plot_id'] ?? 0);
        $name    = mb_substr(trim($data['name'] ?? ''), 0, 100);
        $desc    = mb_substr(trim($data['description'] ?? ''), 0, 1000);

        $stmt = $db->prepare("SELECT owner_client_id FROM vr_land_plots WHERE id = ?");
        $stmt->execute([$plotId]);
        $plot = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$plot || (int)$plot['owner_client_id'] !== $user['client_id']) { echo json_encode(['error' => 'Not your plot']); break; }

        $db->prepare("UPDATE vr_land_plots SET plot_name = ?, description = ? WHERE id = ?")->execute([$name, $desc, $plotId]);
        echo json_encode(['success' => true, 'message' => 'Plot updated']);
        break;

    // ── Global marketplace stats (public) ──
    case 'plot-stats':
        $stats = [];

        // Total plots, owned, listed
        $stmt = $db->query("SELECT COUNT(*) as total, SUM(owner_client_id IS NOT NULL) as owned, 
                                   SUM(is_listed) as listed FROM vr_land_plots");
        $stats['totals'] = $stmt->fetch(PDO::FETCH_ASSOC);

        // By zone
        $stmt = $db->query("SELECT zone, COUNT(*) as total, SUM(owner_client_id IS NOT NULL) as owned,
                                   SUM(is_listed) as listed, AVG(CASE WHEN is_listed THEN listing_price END) as avg_price
                            FROM vr_land_plots GROUP BY zone ORDER BY zone");
        $stats['by_zone'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Recent sales
        $stmt = $db->query("SELECT t.price_gsm, t.fee_gsm, t.created_at, p.zone, p.plot_size, p.plot_name
                            FROM vr_land_transactions t
                            JOIN vr_land_plots p ON p.id = t.plot_id
                            WHERE t.tx_type = 'sale'
                            ORDER BY t.created_at DESC LIMIT 10");
        $stats['recent_sales'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Volume
        $stmt = $db->query("SELECT COALESCE(SUM(price_gsm),0) as total_volume, COUNT(*) as total_trades
                            FROM vr_land_transactions WHERE tx_type = 'sale'");
        $stats['volume'] = $stmt->fetch(PDO::FETCH_ASSOC);

        // Top landowners
        $stmt = $db->query("SELECT c.firstname as name, COUNT(*) as plots, 
                                   SUM(CASE WHEN p.is_listed THEN p.listing_price ELSE 0 END) as listed_value
                            FROM vr_land_plots p
                            JOIN clients c ON c.id = p.owner_client_id
                            WHERE p.owner_client_id IS NOT NULL
                            GROUP BY p.owner_client_id ORDER BY plots DESC LIMIT 10");
        $stats['top_owners'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'stats' => $stats]);
        break;

    // ── Admin: Mint new plots (Commander only) ──
    case 'admin-mint-plots':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        $user = landRequireAuth();
        if (!landIsCommander()) { echo json_encode(['error' => 'Commander access required']); break; }

        $data   = json_decode(file_get_contents('php://input'), true) ?: [];
        $zone   = $data['zone'] ?? '';
        $size   = $data['size'] ?? 'small';
        $startX = (int)($data['start_x'] ?? 0);
        $startY = (int)($data['start_y'] ?? 0);
        $countX = min(50, max(1, (int)($data['count_x'] ?? 1)));
        $countY = min(50, max(1, (int)($data['count_y'] ?? 1)));
        $rarity = $data['rarity'] ?? 'common';

        if (!in_array($zone, ZONES, true)) { echo json_encode(['error' => 'Invalid zone']); break; }
        if (!in_array($size, PLOT_SIZES, true)) { echo json_encode(['error' => 'Invalid size']); break; }
        if (!in_array($rarity, ['common','uncommon','rare','epic','legendary'], true)) { $rarity = 'common'; }

        $basePrice = BASE_PRICES[$size] * ZONE_MULTIPLIER[$zone];
        $grid = PLOT_SIZE_GRID[$size];
        $minted = 0;
        $skipped = 0;

        $insertStmt = $db->prepare("INSERT IGNORE INTO vr_land_plots 
                                    (zone, plot_size, coord_x, coord_y, coord_z, rarity, mint_price_gsm, created_at)
                                    VALUES (?, ?, ?, ?, 0, ?, ?, NOW())");

        for ($x = $startX; $x < $startX + ($countX * $grid['w']); $x += $grid['w']) {
            for ($y = $startY; $y < $startY + ($countY * $grid['h']); $y += $grid['h']) {
                $insertStmt->execute([$zone, $size, $x, $y, $rarity, $basePrice]);
                if ($insertStmt->rowCount() > 0) $minted++;
                else $skipped++;
            }
        }

        echo json_encode([
            'success' => true,
            'minted' => $minted,
            'skipped' => $skipped,
            'base_price' => $basePrice,
            'zone' => $zone,
            'size' => $size,
        ]);
        break;

    // ── Admin: Mint and sell directly (initial land sale) ──
    case 'mint-buy':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        requireCSRF();
        $user = landRequireAuth();
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $plotId = (int)($data['plot_id'] ?? 0);

        if ($plotId <= 0) { echo json_encode(['error' => 'Invalid plot_id']); break; }

        $db->beginTransaction();
        try {
            $stmt = $db->prepare("SELECT * FROM vr_land_plots WHERE id = ? FOR UPDATE");
            $stmt->execute([$plotId]);
            $plot = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$plot) { $db->rollBack(); echo json_encode(['error' => 'Plot not found']); break; }
            if ($plot['owner_client_id'] !== null) { $db->rollBack(); echo json_encode(['error' => 'Plot already owned']); break; }

            $mintPrice = (float)$plot['mint_price_gsm'];
            if ($mintPrice <= 0) { $db->rollBack(); echo json_encode(['error' => 'Plot not available for mint purchase']); break; }

            // Check balance
            $available = getAvailableGSM($db, $user['client_id']);
            if ($available < $mintPrice) {
                $db->rollBack();
                echo json_encode(['error' => 'Insufficient GSM. Need ' . number_format($mintPrice, 4) . ', have ' . number_format($available, 4)]);
                break;
            }

            // Debit buyer
            if (!debitGSMForLand($db, $user['client_id'], $mintPrice, 'Minted plot #' . $plotId . ' in ' . $plot['zone'], $plotId)) {
                $db->rollBack();
                echo json_encode(['error' => 'Debit failed']);
                break;
            }

            // Assign ownership
            $db->prepare("UPDATE vr_land_plots SET owner_client_id = ?, purchased_at = NOW() WHERE id = ?")
               ->execute([$user['client_id'], $plotId]);

            // Record mint transaction
            $db->prepare("INSERT INTO vr_land_transactions (plot_id, seller_id, buyer_id, price_gsm, fee_gsm, currency, tx_type, created_at)
                          VALUES (?, NULL, ?, ?, 0, 'gsm', 'mint', NOW())")
               ->execute([$plotId, $user['client_id'], $mintPrice]);

            $db->commit();
            echo json_encode([
                'success' => true,
                'message' => 'Minted plot #' . $plotId . ' for ' . number_format($mintPrice, 4) . ' GSM',
                'plot_id' => $plotId,
                'zone' => $plot['zone'],
                'size' => $plot['plot_size'],
            ]);
        } catch (\Exception $e) {
            $db->rollBack();
            echo json_encode(['error' => 'Mint purchase failed: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['error' => 'Unknown action', 'valid_actions' => [
            'marketplace', 'plot-detail', 'my-plots', 'list-plot', 'cancel-listing',
            'buy-plot', 'mint-buy', 'make-offer', 'accept-offer', 'plot-offers',
            'update-plot', 'plot-stats', 'admin-mint-plots'
        ]]);
}
