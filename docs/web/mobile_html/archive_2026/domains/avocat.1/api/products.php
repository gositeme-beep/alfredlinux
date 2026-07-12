<?php
/**
 * Products API
 * Returns hosting products and pricing
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://gositeme.com');

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        listProducts();
        break;
    case 'get':
        getProduct();
        break;
    case 'groups':
        getGroups();
        break;
    default:
        jsonResponse(['error' => 'Invalid action'], 400);
}

/**
 * List all products
 */
function listProducts() {
    $db = getDB();
    if (!$db) {
        jsonResponse(['error' => 'Database connection failed'], 500);
    }
    
    $groupId = isset($_GET['group']) ? (int)$_GET['group'] : null;
    
    $sql = "
        SELECT 
            p.id,
            p.name,
            p.description,
            p.group_id as group_id,
            g.name as group_name,
            p.type,
            p.payment_type,
            pr.monthly,
            pr.quarterly,
            pr.semiannually,
            pr.annually,
            pr.biennially,
            pr.triennially
        FROM products p
        JOIN product_groups g ON p.group_id = g.id
        LEFT JOIN pricing_legacy pr ON p.id = pr.relid AND pr.type='product' AND pr.currency=1
        WHERE p.is_hidden = 0 AND g.is_hidden = 0
    ";
    
    $params = [];
    if ($groupId) {
        $sql .= " AND p.group_id = ?";
        $params[] = $groupId;
    }
    
    $sql .= " ORDER BY g.sort_order, p.sort_order, p.id";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    
    $formatted = [];
    foreach ($products as $product) {
        // Parse description into features
        $features = [];
        $description = $product['description'];
        if (!empty($description)) {
            $lines = preg_split('/[\n\r]+/', $description);
            foreach ($lines as $line) {
                $line = trim(strip_tags($line));
                if (!empty($line)) {
                    $features[] = $line;
                }
            }
        }
        
        // Get best price
        $prices = [];
        if ($product['monthly'] > 0) $prices['monthly'] = $product['monthly'];
        if ($product['quarterly'] > 0) $prices['quarterly'] = $product['quarterly'];
        if ($product['semiannually'] > 0) $prices['semiannually'] = $product['semiannually'];
        if ($product['annually'] > 0) $prices['annually'] = $product['annually'];
        if ($product['biennially'] > 0) $prices['biennially'] = $product['biennially'];
        if ($product['triennially'] > 0) $prices['triennially'] = $product['triennially'];
        
        $bestPrice = !empty($prices) ? min($prices) : 0;
        $bestCycle = array_search($bestPrice, $prices);
        
        $formatted[] = [
            'id' => (int)$product['id'],
            'name' => $product['name'],
            'description' => $product['description'],
            'features' => $features,
            'group_id' => (int)$product['group_id'],
            'group_name' => $product['group_name'],
            'type' => $product['type'],
            'pricing' => [
                'monthly' => $product['monthly'] > 0 ? number_format($product['monthly'], 2) : null,
                'quarterly' => $product['quarterly'] > 0 ? number_format($product['quarterly'], 2) : null,
                'semiannually' => $product['semiannually'] > 0 ? number_format($product['semiannually'], 2) : null,
                'annually' => $product['annually'] > 0 ? number_format($product['annually'], 2) : null,
                'biennially' => $product['biennially'] > 0 ? number_format($product['biennially'], 2) : null,
                'triennially' => $product['triennially'] > 0 ? number_format($product['triennially'], 2) : null,
            ],
            'best_price' => number_format($bestPrice, 2),
            'best_cycle' => $bestCycle
        ];
    }
    
    jsonResponse([
        'success' => true,
        'products' => $formatted
    ]);
}

/**
 * Get single product details
 */
function getProduct() {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($id <= 0) {
        jsonResponse(['error' => 'Product ID required'], 400);
    }
    
    $db = getDB();
    if (!$db) {
        jsonResponse(['error' => 'Database connection failed'], 500);
    }
    
    $stmt = $db->prepare("
        SELECT 
            p.*,
            g.name as group_name,
            pr.monthly,
            pr.quarterly,
            pr.semiannually,
            pr.annually,
            pr.biennially,
            pr.triennially,
            pr.msetupfee as setup_monthly,
            pr.qsetupfee as setup_quarterly,
            pr.ssetupfee as setup_semiannually,
            pr.asetupfee as setup_annually
        FROM products p
        JOIN product_groups g ON p.group_id = g.id
        LEFT JOIN pricing_legacy pr ON p.id = pr.relid AND pr.type='product' AND pr.currency=1
        WHERE p.id = ? AND p.is_hidden = 0
    ");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        jsonResponse(['error' => 'Product not found'], 404);
    }
    
    // Get product config options if any
    $stmt = $db->prepare("
        SELECT co.id, co.optionname, co.optiontype
        FROM product_config_options co
        JOIN product_config_links cl ON co.group_id = cl.group_id
        WHERE cl.pid = ?
        ORDER BY co.order
    ");
    $stmt->execute([$id]);
    $configOptions = $stmt->fetchAll();
    
    jsonResponse([
        'success' => true,
        'product' => [
            'id' => (int)$product['id'],
            'name' => $product['name'],
            'description' => $product['description'],
            'group_name' => $product['group_name'],
            'type' => $product['type'],
            'pricing' => [
                'monthly' => $product['monthly'] > 0 ? ['price' => number_format($product['monthly'], 2), 'setup' => number_format($product['setup_monthly'], 2)] : null,
                'quarterly' => $product['quarterly'] > 0 ? ['price' => number_format($product['quarterly'], 2), 'setup' => number_format($product['setup_quarterly'], 2)] : null,
                'semiannually' => $product['semiannually'] > 0 ? ['price' => number_format($product['semiannually'], 2), 'setup' => number_format($product['setup_semiannually'], 2)] : null,
                'annually' => $product['annually'] > 0 ? ['price' => number_format($product['annually'], 2), 'setup' => number_format($product['setup_annually'], 2)] : null,
            ],
            'config_options' => $configOptions
        ]
    ]);
}

/**
 * Get product groups
 */
function getGroups() {
    $db = getDB();
    if (!$db) {
        jsonResponse(['error' => 'Database connection failed'], 500);
    }
    
    $stmt = $db->query("
        SELECT id, name, slug, headline, tagline
        FROM product_groups 
        WHERE hidden = 0 
        ORDER BY `order`, id
    ");
    $groups = $stmt->fetchAll();
    
    jsonResponse([
        'success' => true,
        'groups' => $groups
    ]);
}
