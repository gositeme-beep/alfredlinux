<?php
/**
 * Cart operations — add domains, get count
 * Handles POST { action: 'add', type: 'domain', domain, price }
 * Returns JSON { "count": N }
 */

header('Content-Type: application/json');
header('Cache-Control: no-store, max-age=0');

session_start();

// Handle POST for adding items
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $action = $input['action'] ?? '';
    
    if ($action === 'add' && ($input['type'] ?? '') === 'domain') {
        $domain = strtolower(trim($input['domain'] ?? ''));
        $price  = (float)($input['price'] ?? 0);
        
        // Validate domain format
        if ($domain && $price > 0 && preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?\.[a-z]{2,}$/', $domain)) {
            require_once dirname(__DIR__) . '/pay/includes/billing-functions.php';
require_once dirname(__DIR__) . '/includes/api-security.php';
            addDomainToCart($domain, $price);
        }
    }
}

$count = count($_SESSION['billing_cart'] ?? []);
echo json_encode(['count' => $count]);
