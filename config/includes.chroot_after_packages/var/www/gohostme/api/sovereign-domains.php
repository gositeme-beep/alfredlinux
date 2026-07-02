<?php
/**
 * Sovereign Domains API — GoSiteMe
 * ═══════════════════════════════════
 * Registry API for the Sovereign Web domain system.
 * 
 * Endpoints:
 *   ?action=search&domain=mysite     — Check availability across all TLDs
 *   ?action=check&domain=mysite.alfred — Check specific domain
 *   ?action=tlds                      — List available TLDs
 *   ?action=register                  — Register a domain (POST, auth required)
 *   ?action=my-domains                — List user's domains (auth required)
 *   ?action=stats                     — Public stats
 *   ?action=update-dns                — Update DNS records (POST, auth required)
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';
require_once dirname(__DIR__) . '/includes/solana-verify.inc.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'search';

switch ($action) {
    case 'search':    searchSovereignDomain(); break;
    case 'check':     checkSovereignDomain(); break;
    case 'tlds':      getSovereignTLDs(); break;
    case 'register':  registerSovereignDomain(); break;
    case 'my-domains': getMyDomains(); break;
    case 'stats':     getSovereignStats(); break;
    case 'update-dns': updateDNS(); break;
    default:
        jsonResponse(['error' => 'Invalid action'], 400);
}

// ═══════════════════════════════════════════
// HELPERS
// ═══════════════════════════════════════════

function getSovereignDB() {
    require_once dirname(__DIR__) . '/includes/db-config.inc.php';
    return getSharedDB();
}

if (!function_exists('jsonResponse')) {
    function jsonResponse($data, $code = 200) {
        http_response_code($code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

function getAuthUser() {
    session_start();
    $clientId = $_SESSION['uid'] ?? null;
    if (!$clientId) {
        jsonResponse(['error' => 'Authentication required'], 401);
    }
    return (int)$clientId;
}

function validateSubdomain($name) {
    // 1-63 chars, letters/numbers/hyphens, can't start/end with hyphen
    if (!preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$/', $name)) {
        return false;
    }
    // Block reserved names
    $reserved = ['www', 'mail', 'ftp', 'ns1', 'ns2', 'admin', 'root', 'api', 'localhost', 'test'];
    return !in_array($name, $reserved);
}

function validateIP($ip) {
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false
        || filter_var($ip, FILTER_VALIDATE_IP) !== false; // Allow private for internal mesh
}

// ═══════════════════════════════════════════
// SEARCH — check availability across all TLDs
// ═══════════════════════════════════════════

function searchSovereignDomain() {
    $input = strtolower(trim($_GET['domain'] ?? ''));
    if (empty($input)) {
        jsonResponse(['error' => 'Domain name required'], 400);
    }
    
    // Strip any TLD if provided
    $parts = explode('.', $input);
    $name = $parts[0];
    
    if (!validateSubdomain($name)) {
        jsonResponse(['error' => 'Invalid domain name. Use only letters, numbers, and hyphens (1-63 chars).'], 400);
    }
    
    $db = getSovereignDB();
    
    // Get all active TLDs
    $tlds = $db->query("SELECT id, tld, display_name, icon, price_usd, price_sol, price_gsm, status FROM sovereign_tlds WHERE status IN ('active', 'coming_soon') ORDER BY FIELD(status, 'active', 'coming_soon'), price_usd ASC")->fetchAll(PDO::FETCH_ASSOC);
    
    // Check availability for each
    $stmt = $db->prepare("SELECT id FROM sovereign_domains WHERE subdomain = ? AND tld_id = ? AND status != 'expired' LIMIT 1");
    
    $results = [];
    foreach ($tlds as $tld) {
        $stmt->execute([$name, $tld['id']]);
        $taken = $stmt->fetch();
        
        $results[] = [
            'domain' => $name . '.' . $tld['tld'],
            'tld' => $tld['tld'],
            'tld_name' => $tld['display_name'],
            'icon' => $tld['icon'],
            'available' => !$taken && $tld['status'] === 'active',
            'price_usd' => (float)$tld['price_usd'],
            'price_sol' => (float)($tld['price_sol'] ?? 0),
            'price_gsm' => (float)$tld['price_gsm'],
            'status' => $tld['status'],
        ];
    }
    
    jsonResponse([
        'query' => $name,
        'results' => $results,
        'available_count' => count(array_filter($results, fn($r) => $r['available'])),
    ]);
}

// ═══════════════════════════════════════════
// CHECK — specific domain availability
// ═══════════════════════════════════════════

function checkSovereignDomain() {
    $domain = strtolower(trim($_GET['domain'] ?? ''));
    if (empty($domain) || !str_contains($domain, '.')) {
        jsonResponse(['error' => 'Full domain required (e.g., mysite.alfred)'], 400);
    }
    
    $parts = explode('.', $domain);
    $tld = array_pop($parts);
    $subdomain = implode('.', $parts);
    
    if (!validateSubdomain($subdomain)) {
        jsonResponse(['error' => 'Invalid domain name'], 400);
    }
    
    $db = getSovereignDB();
    
    // Verify TLD exists
    $tldRow = $db->prepare("SELECT id, tld, display_name, price_usd, price_sol, price_gsm, status FROM sovereign_tlds WHERE tld = ?");
    $tldRow->execute([$tld]);
    $tldData = $tldRow->fetch(PDO::FETCH_ASSOC);
    
    if (!$tldData) {
        jsonResponse(['error' => 'Unknown TLD: .' . $tld, 'available' => false], 404);
    }
    
    // Check if taken
    $stmt = $db->prepare("SELECT id, status FROM sovereign_domains WHERE subdomain = ? AND tld_id = ? AND status != 'expired' LIMIT 1");
    $stmt->execute([$subdomain, $tldData['id']]);
    $existing = $stmt->fetch();
    
    jsonResponse([
        'domain' => $domain,
        'available' => !$existing && $tldData['status'] === 'active',
        'tld' => $tldData['tld'],
        'tld_name' => $tldData['display_name'],
        'tld_status' => $tldData['status'],
        'price_usd' => (float)$tldData['price_usd'],
        'price_sol' => (float)($tldData['price_sol'] ?? 0),
        'price_gsm' => (float)$tldData['price_gsm'],
    ]);
}

// ═══════════════════════════════════════════
// TLDS — list available extensions
// ═══════════════════════════════════════════

function getSovereignTLDs() {
    $db = getSovereignDB();
    $rows = $db->query("SELECT tld, display_name, description, icon, category, price_usd, price_sol, price_gsm, status, registrations_count FROM sovereign_tlds WHERE status IN ('active', 'coming_soon') ORDER BY FIELD(category, 'ecosystem', 'community', 'identity', 'commerce', 'creative', 'infrastructure', 'security'), tld")->fetchAll(PDO::FETCH_ASSOC);
    
    // Group by category
    $grouped = [];
    foreach ($rows as $row) {
        $cat = $row['category'];
        if (!isset($grouped[$cat])) $grouped[$cat] = [];
        $grouped[$cat][] = $row;
    }
    
    jsonResponse([
        'tlds' => $rows,
        'grouped' => $grouped,
        'total' => count($rows),
    ]);
}

// ═══════════════════════════════════════════
// REGISTER — register a sovereign domain (auth required)
// ═══════════════════════════════════════════

function registerSovereignDomain() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'POST required'], 405);
    }
    
    $clientId = getAuthUser();
    
    $body = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $domain = strtolower(trim($body['domain'] ?? ''));
    $years = max(1, min(10, (int)($body['years'] ?? 1)));
    $dnsA = trim($body['dns_a'] ?? '');
    $paymentMethod = strtolower(trim($body['payment_method'] ?? 'free'));
    $paymentTx = trim($body['payment_tx'] ?? '');
    
    if (empty($domain) || !str_contains($domain, '.')) {
        jsonResponse(['error' => 'Full domain required (e.g., mysite.alfred)'], 400);
    }
    
    $parts = explode('.', $domain);
    $tld = array_pop($parts);
    $subdomain = implode('.', $parts);
    
    if (!validateSubdomain($subdomain)) {
        jsonResponse(['error' => 'Invalid domain name. Use only letters, numbers, and hyphens.'], 400);
    }
    
    $db = getSovereignDB();
    
    // Verify TLD exists and is active
    $tldStmt = $db->prepare("SELECT id, tld, price_usd, price_sol, price_gsm, status FROM sovereign_tlds WHERE tld = ? AND status = 'active'");
    $tldStmt->execute([$tld]);
    $tldData = $tldStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tldData) {
        jsonResponse(['error' => 'TLD .' . $tld . ' is not available for registration'], 400);
    }
    
    // Check if already taken
    $checkStmt = $db->prepare("SELECT id FROM sovereign_domains WHERE subdomain = ? AND tld_id = ? AND status NOT IN ('expired') LIMIT 1");
    $checkStmt->execute([$subdomain, $tldData['id']]);
    if ($checkStmt->fetch()) {
        jsonResponse(['error' => 'Domain ' . $domain . ' is already registered'], 409);
    }
    
    // Validate DNS A record if provided
    if ($dnsA && !validateIP($dnsA)) {
        jsonResponse(['error' => 'Invalid IP address for A record'], 400);
    }
    
    // ── Payment verification ──
    $totalPriceUsd = (float)$tldData['price_usd'] * $years;
    $totalPriceGsm = (float)$tldData['price_gsm'] * $years;
    $totalPriceSol = (float)($tldData['price_sol'] ?? 0) * $years;
    $paymentConfirmed = false;
    $paymentAmount = null;
    
    // Commander (client_id 33) bypasses payment — sovereign authority
    if ($clientId === 33) {
        $paymentMethod = 'commander';
        $paymentConfirmed = true;
        $paymentAmount = 0;
    } elseif ($totalPriceUsd == 0) {
        // Free TLD — no payment needed
        $paymentMethod = 'free';
        $paymentConfirmed = true;
        $paymentAmount = 0;
    } elseif (in_array($paymentMethod, ['sol', 'gsm'])) {
        // On-chain payment — verify transaction
        if (empty($paymentTx)) {
            jsonResponse(['error' => 'Transaction signature required for ' . strtoupper($paymentMethod) . ' payment'], 400);
        }
        
        // Prevent double-spend of same tx
        if (isTxAlreadyUsed($paymentTx)) {
            jsonResponse(['error' => 'Transaction already used for another domain registration'], 400);
        }
        
        $expectedAmount = ($paymentMethod === 'sol') ? $totalPriceSol : $totalPriceGsm;
        $verification = verifySolanaPayment($paymentTx, $expectedAmount, $paymentMethod);
        
        if (!$verification['verified']) {
            jsonResponse([
                'error' => 'Payment verification failed: ' . $verification['error'],
                'expected_amount' => $expectedAmount,
                'currency' => strtoupper($paymentMethod),
                'treasury' => DOMAIN_TREASURY_WALLET,
            ], 402);
        }
        
        $paymentConfirmed = true;
        $paymentAmount = $verification['actual_amount'];
    } elseif ($paymentMethod === 'stripe') {
        // Stripe payment — handled separately via stripe webhook
        // Domain stays pending until webhook confirms
        $paymentConfirmed = false;
        $paymentAmount = $totalPriceUsd;
    } else {
        // No valid payment method for a paid TLD
        jsonResponse([
            'error' => 'Payment required',
            'accepted_methods' => ['gsm', 'sol', 'stripe'],
            'prices' => [
                'usd' => $totalPriceUsd,
                'sol' => $totalPriceSol,
                'gsm' => $totalPriceGsm,
            ],
            'treasury' => DOMAIN_TREASURY_WALLET,
            'gsm_mint' => GSM_MINT_ADDRESS,
        ], 402);
    }
    
    // Register the domain
    $expiresAt = date('Y-m-d H:i:s', strtotime("+{$years} year"));
    $status = $paymentConfirmed ? 'active' : 'pending';
    
    $insertStmt = $db->prepare("INSERT INTO sovereign_domains (domain_name, subdomain, tld_id, client_id, dns_a, status, expires_at, registration_years, registered_at, payment_method, payment_tx, payment_amount, payment_confirmed, payment_confirmed_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?)");
    $insertStmt->execute([
        $domain,
        $subdomain,
        $tldData['id'],
        $clientId,
        $dnsA ?: null,
        $status,
        $expiresAt,
        $years,
        $paymentMethod,
        $paymentTx ?: null,
        $paymentAmount,
        $paymentConfirmed ? 1 : 0,
        $paymentConfirmed ? date('Y-m-d H:i:s') : null,
    ]);
    
    $domainId = $db->lastInsertId();
    
    // Log the registration
    $logStmt = $db->prepare("INSERT INTO sovereign_domain_log (domain_id, action, actor_id, details) VALUES (?, 'registered', ?, ?)");
    $logStmt->execute([$domainId, $clientId, "Registered {$domain} for {$years} year(s) via {$paymentMethod}"]);
    
    // Update TLD registration count
    $db->exec("UPDATE sovereign_tlds SET registrations_count = registrations_count + 1 WHERE id = " . (int)$tldData['id']);
    
    jsonResponse([
        'success' => true,
        'domain' => $domain,
        'domain_id' => (int)$domainId,
        'status' => $status,
        'expires_at' => $expiresAt,
        'years' => $years,
        'payment' => [
            'method' => $paymentMethod,
            'confirmed' => $paymentConfirmed,
            'amount' => $paymentAmount,
            'tx' => $paymentTx ?: null,
        ],
        'prices' => [
            'usd' => $totalPriceUsd,
            'sol' => $totalPriceSol,
            'gsm' => $totalPriceGsm,
        ],
    ], 201);
}

// ═══════════════════════════════════════════
// MY DOMAINS — list user's registered domains
// ═══════════════════════════════════════════

function getMyDomains() {
    $clientId = getAuthUser();
    
    $db = getSovereignDB();
    $stmt = $db->prepare("
        SELECT d.id, d.domain_name, d.subdomain, t.tld, t.icon, t.display_name as tld_name,
               d.dns_a, d.dns_aaaa, d.dns_cname, d.dns_mx,
               d.status, d.registered_at, d.expires_at, d.auto_renew, d.privacy_enabled, d.locked,
               d.hosted_on
        FROM sovereign_domains d
        JOIN sovereign_tlds t ON d.tld_id = t.id
        WHERE d.client_id = ?
        ORDER BY d.registered_at DESC
    ");
    $stmt->execute([$clientId]);
    $domains = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    jsonResponse([
        'domains' => $domains,
        'total' => count($domains),
    ]);
}

// ═══════════════════════════════════════════
// STATS — public sovereign web statistics
// ═══════════════════════════════════════════

function getSovereignStats() {
    $db = getSovereignDB();
    
    $totalDomains = $db->query("SELECT COUNT(*) FROM sovereign_domains WHERE status = 'active'")->fetchColumn();
    $totalTLDs = $db->query("SELECT COUNT(*) FROM sovereign_tlds WHERE status IN ('active', 'reserved')")->fetchColumn();
    $totalClients = $db->query("SELECT COUNT(DISTINCT client_id) FROM sovereign_domains WHERE status = 'active'")->fetchColumn();
    
    $topTLDs = $db->query("SELECT t.tld, t.icon, t.display_name, t.registrations_count FROM sovereign_tlds t WHERE t.status = 'active' ORDER BY t.registrations_count DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    
    $recentDomains = $db->query("SELECT d.domain_name, t.icon FROM sovereign_domains d JOIN sovereign_tlds t ON d.tld_id = t.id WHERE d.status = 'active' ORDER BY d.registered_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    
    jsonResponse([
        'total_domains' => (int)$totalDomains,
        'total_tlds' => (int)$totalTLDs,
        'total_owners' => (int)$totalClients,
        'top_tlds' => $topTLDs,
        'recent' => $recentDomains,
    ]);
}

// ═══════════════════════════════════════════
// UPDATE DNS — modify DNS records (auth required)
// ═══════════════════════════════════════════

function updateDNS() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'POST required'], 405);
    }
    
    $clientId = getAuthUser();
    
    $body = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $domainId = (int)($body['domain_id'] ?? 0);
    
    if (!$domainId) {
        jsonResponse(['error' => 'domain_id required'], 400);
    }
    
    $db = getSovereignDB();
    
    // Verify ownership
    $stmt = $db->prepare("SELECT id, domain_name FROM sovereign_domains WHERE id = ? AND client_id = ? AND status = 'active'");
    $stmt->execute([$domainId, $clientId]);
    $domain = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$domain) {
        jsonResponse(['error' => 'Domain not found or not owned by you'], 403);
    }
    
    // Build update
    $updates = [];
    $params = [];
    
    if (isset($body['dns_a'])) {
        $val = trim($body['dns_a']);
        if ($val && !validateIP($val)) {
            jsonResponse(['error' => 'Invalid A record IP'], 400);
        }
        $updates[] = 'dns_a = ?';
        $params[] = $val ?: null;
    }
    
    if (isset($body['dns_aaaa'])) {
        $val = trim($body['dns_aaaa']);
        if ($val && !filter_var($val, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            jsonResponse(['error' => 'Invalid AAAA record'], 400);
        }
        $updates[] = 'dns_aaaa = ?';
        $params[] = $val ?: null;
    }
    
    if (isset($body['dns_cname'])) {
        $val = trim($body['dns_cname']);
        if ($val && !preg_match('/^[a-z0-9][a-z0-9.-]+[a-z0-9]$/', $val)) {
            jsonResponse(['error' => 'Invalid CNAME'], 400);
        }
        $updates[] = 'dns_cname = ?';
        $params[] = $val ?: null;
    }
    
    if (isset($body['dns_mx'])) {
        $val = trim($body['dns_mx']);
        $updates[] = 'dns_mx = ?';
        $params[] = $val ?: null;
    }
    
    if (empty($updates)) {
        jsonResponse(['error' => 'No DNS fields to update'], 400);
    }
    
    $params[] = $domainId;
    $sql = "UPDATE sovereign_domains SET " . implode(', ', $updates) . " WHERE id = ?";
    $db->prepare($sql)->execute($params);
    
    // Log the change
    $logStmt = $db->prepare("INSERT INTO sovereign_domain_log (domain_id, action, actor_id, details) VALUES (?, 'dns_updated', ?, ?)");
    $logStmt->execute([$domainId, $clientId, 'DNS records updated for ' . $domain['domain_name']]);
    
    jsonResponse(['success' => true, 'domain' => $domain['domain_name'], 'updated_fields' => count($updates)]);
}
