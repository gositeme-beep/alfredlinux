<?php
/**
 * Domain Search API
 * Checks domain availability and returns pricing
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'search';

switch ($action) {
    case 'search':
        searchDomain();
        break;
    case 'tlds':
        getTLDs();
        break;
    case 'check':
        checkDomain();
        break;
    default:
        jsonResponse(['error' => 'Invalid action'], 400);
}

/**
 * Search for domain availability
 */
function searchDomain() {
    // Get and validate domain input
    $domain_input = $_GET['domain'] ?? $_POST['domain'] ?? '';
    
    if (empty($domain_input)) {
        jsonResponse(['error' => 'Domain name required'], 400);
    }
    
    // Clean and validate domain input
    $domain = sanitize($domain_input, 100);
    $domain = strtolower(trim($domain));
    $domain = preg_replace('/^(https?:\/\/)?(www\.)?/', '', $domain);
    $domain = preg_replace('/\/.*$/', '', $domain);
    
    // Extract SLD — if input has a TLD, split; otherwise treat as bare SLD
    $parts = explode('.', $domain);
    $sld = $parts[0];
    
    // Validate SLD format (alphanumeric + hyphens, 2-63 chars)
    if (empty($sld) || !preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$/', $sld)) {
        log_suspicious('invalid_domain_search', ['domain' => $domain_input]);
        jsonResponse(['error' => 'Invalid domain name format'], 400);
    }
    
    if (strlen($sld) < 2 || strlen($sld) > 63) {
        log_suspicious('invalid_sld_search', ['sld' => $sld]);
        jsonResponse(['error' => 'Invalid domain name'], 400);
    }
    
    // Get popular TLDs to check
    $db = getDB();
    if (!$db) {
        jsonResponse(['error' => 'Database connection failed'], 500);
    }
    
    // Get all TLDs with pricing (prioritize .com, .ca, .net, .org, .co.uk, .uk, etc.)
    $stmt = $db->prepare("
        SELECT dp.extension, pr.msetupfee as price
        FROM domain_pricing dp 
        JOIN pricing_legacy pr ON dp.id = pr.relid AND pr.type='domainregister' AND pr.currency=1
        WHERE dp.autoreg != '' AND pr.msetupfee > 0
        ORDER BY 
            CASE 
                WHEN dp.extension IN ('.com', '.ca', '.net', '.org', '.co.uk', '.uk', '.io', '.co', '.ai', '.dev', '.app', '.info', '.biz', '.xyz', '.online', '.site', '.store', '.tech', '.cloud', '.me', '.us', '.eu', '.de', '.fr', '.es', '.it') THEN 0
                ELSE 1
            END,
            pr.msetupfee ASC,
            dp.extension ASC
    ");
    $stmt->execute();
    $allTlds = $stmt->fetchAll();
    
    // Priority TLDs to check immediately (fast DNS-only, max 4)
    $priorityExts = ['.com', '.ca', '.net', '.org'];
    $maxQuick = 4;
    $checked = 0;
    $results = [];
    
    foreach ($allTlds as $tld) {
        $fullDomain = $sld . $tld['extension'];
        $isPriority = in_array($tld['extension'], $priorityExts) && $checked < $maxQuick;
        $available = null;
        
        if ($isPriority) {
            $available = quickDnsCheck($fullDomain);
            $checked++;
        }
        
        $results[] = [
            'domain' => $fullDomain,
            'tld' => $tld['extension'],
            'available' => $available,
            'checked' => $isPriority,
            'price' => number_format($tld['price'], 2),
            'price_raw' => (float)$tld['price']
        ];
    }
    
    // Sort: checked+available first, then checked+taken, then unchecked; then by price
    usort($results, function($a, $b) {
        $av = $a['available']; $bv = $b['available'];
        $ac = $a['checked']; $bc = $b['checked'];
        if ($ac && $bc) {
            if ($av !== $bv) return ($bv ? 1 : 0) - ($av ? 1 : 0);
            return $a['price_raw'] <=> $b['price_raw'];
        }
        if ($ac !== $bc) return $bc ? 1 : -1;
        return $a['price_raw'] <=> $b['price_raw'];
    });
    
    jsonResponse([
        'success' => true,
        'query' => $sld,
        'results' => $results
    ]);
}

/**
 * Check single domain availability
 */
function checkDomain() {
    $domain = sanitize($_GET['domain'] ?? $_POST['domain'] ?? '');
    
    if (empty($domain)) {
        jsonResponse(['error' => 'Domain name required'], 400);
    }
    
    $domain = strtolower(trim($domain));
    $available = checkDomainAvailability($domain);
    
    // Get price for this TLD
    $tld = '.' . implode('.', array_slice(explode('.', $domain), 1));
    $db = getDB();
    
    $stmt = $db->prepare("
        SELECT pr.msetupfee as price
        FROM domain_pricing dp 
        JOIN pricing_legacy pr ON dp.id = pr.relid AND pr.type='domainregister' AND pr.currency=1
        WHERE dp.extension = ?
    ");
    $stmt->execute([$tld]);
    $pricing = $stmt->fetch();
    
    jsonResponse([
        'success' => true,
        'domain' => $domain,
        'available' => $available,
        'price' => $pricing ? number_format($pricing['price'], 2) : null
    ]);
}

/**
 * Quick DNS-only availability check (< 1 second)
 * Used for initial search results — fast but not 100% reliable
 */
function quickDnsCheck(string $domain): ?bool {
    // Only use gethostbyname — fastest single check
    // dns_get_record can be very slow for non-existent domains
    $ip = @gethostbyname($domain);
    if ($ip !== $domain) {
        return false; // resolves → taken
    }
    // Doesn't resolve → likely available
    return true;
}

/**
 * Check domain availability via DNS/WHOIS (more thorough, used for single-domain checks)
 */
function checkDomainAvailability($domain) {
    // Method 1: Quick DNS check
    $ip = @gethostbyname($domain);
    if ($ip !== $domain) {
        return false;
    }
    
    $dns = @dns_get_record($domain, DNS_A);
    if (!empty($dns)) {
        return false;
    }
    
    // Method 2: WHOIS check for common TLDs with strict 2-second timeout
    $tld = strtolower(substr($domain, strrpos($domain, '.') + 1));
    $whoisServers = [
        'com' => 'whois.verisign-grs.com',
        'net' => 'whois.verisign-grs.com',
        'org' => 'whois.pir.org',
        'io' => 'whois.nic.io',
        'co' => 'whois.nic.co',
        'ai' => 'whois.nic.ai',
        'dev' => 'whois.nic.google',
        'app' => 'whois.nic.google',
        'ca' => 'whois.cira.ca',
        'uk' => 'whois.nic.uk',
        'co.uk' => 'whois.nic.uk',
        'me' => 'whois.nic.me',
        'us' => 'whois.nic.us',
        'info' => 'whois.afilias.net',
        'biz' => 'whois.biz',
        'xyz' => 'whois.nic.xyz',
        'online' => 'whois.nic.online',
        'site' => 'whois.nic.site',
        'store' => 'whois.nic.store',
        'tech' => 'whois.nic.tech',
        'cloud' => 'whois.nic.cloud',
        'eu' => 'whois.eu',
        'de' => 'whois.denic.de',
        'fr' => 'whois.nic.fr',
        'es' => 'whois.nic.es',
        'it' => 'whois.nic.it',
        'nl' => 'whois.domain-registry.nl',
        'be' => 'whois.dns.be',
        'au' => 'whois.auda.org.au',
        'nz' => 'whois.srs.net.nz',
        'in' => 'whois.registry.in',
        'tv' => 'whois.nic.tv',
        'cc' => 'whois.nic.cc',
        'ws' => 'whois.website.ws',
        'mobi' => 'whois.afilias.net',
        'tel' => 'whois.nic.tel',
        'asia' => 'whois.nic.asia',
        'name' => 'whois.nic.name',
        'pro' => 'whois.afilias.net',
        'edu' => 'whois.educause.edu',
        'gov' => 'whois.dotgov.gov',
    ];
    
    if (isset($whoisServers[$tld])) {
        $whois = @fsockopen($whoisServers[$tld], 43, $errno, $errstr, 2);
        if (!$whois) {
            return null; // Connection failed – don't guess available
        }
        stream_set_timeout($whois, 2);
        @fwrite($whois, $domain . "\r\n");
        $response = '';
        $startTime = microtime(true);
        while (!feof($whois) && (microtime(true) - $startTime) < 2) {
            $line = @fgets($whois, 1024);
            if ($line === false) break;
            $response .= $line;
            // Stop reading once we have enough to determine status
            if (strlen($response) > 2048) break;
        }
        fclose($whois);
        if ($response === '') {
            return null;
        }
        // Patterns for "domain available" (CIRA .ca: "Great news! This domain is available." / "Domain status: available", etc.)
        $notFoundPatterns = [
            'No match for', 'NOT FOUND', 'No Data Found', 'No entries found',
            'Status: free', 'is available', 'No Object Found', 'Status: AVAILABLE',
            'Domain not found', 'No domain was found', 'not found:', 'available for registration',
            'nothing found', 'No matching record', 'No entries found for',
            'is free', 'AVAILABLE', 'no matching record',
            'Great news', 'domain is available', 'Domain status: available', 'Status: available',
            'available for', 'is free for registration', 'No entries found',
            'Domain Status: available', 'status: available'
        ];
        foreach ($notFoundPatterns as $pattern) {
            if (stripos($response, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }
    return true;
}

/**
 * Get all available TLDs with pricing
 */
function getTLDs() {
    $db = getDB();
    if (!$db) {
        jsonResponse(['error' => 'Database connection failed'], 500);
    }
    
    $category = sanitize($_GET['category'] ?? 'popular');
    
    $sql = "
        SELECT dp.extension, 
               pr.msetupfee as register_price,
               pr2.msetupfee as renew_price
        FROM domain_pricing dp 
        JOIN pricing_legacy pr ON dp.id = pr.relid AND pr.type='domainregister' AND pr.currency=1
        LEFT JOIN pricing_legacy pr2 ON dp.id = pr2.relid AND pr2.type='domainrenew' AND pr2.currency=1
        WHERE dp.autoreg != '' AND pr.msetupfee > 0
    ";
    
    switch ($category) {
        case 'popular':
            $sql .= " AND dp.extension IN ('.com', '.net', '.org', '.io', '.co', '.ai', '.dev', '.app', '.xyz', '.online')";
            break;
        case 'cheap':
            $sql .= " ORDER BY pr.msetupfee ASC LIMIT 20";
            break;
        case 'new':
            $sql .= " AND dp.extension IN ('.ai', '.dev', '.app', '.io', '.tech', '.digital', '.online', '.site', '.website')";
            break;
        default:
            $sql .= " ORDER BY dp.extension ASC";
    }
    
    if ($category === 'popular' || $category === 'new') {
        $sql .= " ORDER BY pr.msetupfee ASC";
    }
    
    $stmt = $db->query($sql);
    $tlds = $stmt->fetchAll();
    
    $formatted = [];
    foreach ($tlds as $tld) {
        $formatted[] = [
            'extension' => $tld['extension'],
            'register' => number_format($tld['register_price'], 2),
            'renew' => number_format($tld['renew_price'], 2)
        ];
    }
    
    jsonResponse([
        'success' => true,
        'category' => $category,
        'tlds' => $formatted
    ]);
}
