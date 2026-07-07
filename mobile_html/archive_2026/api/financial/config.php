<?php
/**
 * Financial Autonomy Module — Shared Configuration
 * API key management, shared helpers, constants for all financial integrations
 */
if (!defined('GOSITEME_API')) die('Direct access not allowed');

// ─── API Key Constants (loaded from env) ──────────────────────
// Stripe Advanced (extends existing Stripe keys in stripe.php)
define('STRIPE_CONNECT_ONBOARDING',  getenv('STRIPE_CONNECT_ONBOARDING')  ?: '');
define('STRIPE_TAX_ENABLED',         getenv('STRIPE_TAX_ENABLED')         ?: 'false');

// Plaid
define('PLAID_CLIENT_ID',     getenv('PLAID_CLIENT_ID')     ?: '');
define('PLAID_SECRET',        getenv('PLAID_SECRET')        ?: '');
define('PLAID_ENV',           getenv('PLAID_ENV')           ?: 'sandbox'); // sandbox|development|production
define('PLAID_API_URL',       PLAID_ENV === 'production' ? 'https://production.plaid.com' : (PLAID_ENV === 'development' ? 'https://development.plaid.com' : 'https://sandbox.plaid.com'));

// Mercury
define('MERCURY_API_KEY',     getenv('MERCURY_API_KEY')     ?: '');
define('MERCURY_API_URL',     'https://api.mercury.com/api/v1');

// Wise
define('WISE_API_KEY',        getenv('WISE_API_KEY')        ?: '');
define('WISE_PROFILE_ID',     getenv('WISE_PROFILE_ID')     ?: '');
define('WISE_API_URL',        getenv('WISE_ENV') === 'production' ? 'https://api.transferwise.com' : 'https://api.sandbox.transferwise.tech');

// PayPal
define('PAYPAL_CLIENT_ID',    getenv('PAYPAL_CLIENT_ID')     ?: '');
define('PAYPAL_SECRET',       getenv('PAYPAL_SECRET')        ?: '');
define('PAYPAL_API_URL',      getenv('PAYPAL_ENV') === 'production' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com');

// Xero
define('XERO_CLIENT_ID',     getenv('XERO_CLIENT_ID')     ?: '');
define('XERO_CLIENT_SECRET', getenv('XERO_CLIENT_SECRET') ?: '');
define('XERO_REDIRECT_URI',  SITE_URL . '/api/financial/accounting.php?action=xero_callback');
define('XERO_API_URL',       'https://api.xero.com/api.xro/2.0');

// QuickBooks Online
define('QBO_CLIENT_ID',      getenv('QBO_CLIENT_ID')      ?: '');
define('QBO_CLIENT_SECRET',  getenv('QBO_CLIENT_SECRET')  ?: '');
define('QBO_REDIRECT_URI',   SITE_URL . '/api/financial/accounting.php?action=qbo_callback');
define('QBO_API_URL',        getenv('QBO_ENV') === 'production' ? 'https://quickbooks.api.intuit.com/v3' : 'https://sandbox-quickbooks.api.intuit.com/v3');

// ChartMogul
define('CHARTMOGUL_API_KEY', getenv('CHARTMOGUL_API_KEY') ?: '');
define('CHARTMOGUL_API_URL', 'https://api.chartmogul.com/v1');

// ProfitWell
define('PROFITWELL_API_KEY', getenv('PROFITWELL_API_KEY') ?: '');
define('PROFITWELL_API_URL', 'https://api.profitwell.com/v2');

// TaxJar
define('TAXJAR_API_KEY',     getenv('TAXJAR_API_KEY')     ?: '');
define('TAXJAR_API_URL',     getenv('TAXJAR_ENV') === 'production' ? 'https://api.taxjar.com/v2' : 'https://api.sandbox.taxjar.com/v2');

// Koinly
define('KOINLY_API_KEY',     getenv('KOINLY_API_KEY')     ?: '');

// CEX Trading
define('KRAKEN_API_KEY',     getenv('KRAKEN_API_KEY')      ?: '');
define('KRAKEN_API_SECRET',  getenv('KRAKEN_API_SECRET')   ?: '');
define('COINBASE_API_KEY',   getenv('COINBASE_API_KEY')    ?: '');
define('COINBASE_API_SECRET',getenv('COINBASE_API_SECRET') ?: '');

// Deel
define('DEEL_API_KEY',       getenv('DEEL_API_KEY')       ?: '');
define('DEEL_API_URL',       getenv('DEEL_ENV') === 'production' ? 'https://api.deel.com/rest/v2' : 'https://api-sandbox.deel.com/rest/v2');

// 1inch / Li.Fi
define('ONEINCH_API_KEY',    getenv('ONEINCH_API_KEY')    ?: '');
define('LIFI_API_URL',       'https://li.quest/v1');

// EVM RPC endpoints
define('ETH_RPC_URL',        getenv('ETH_RPC_URL')        ?: 'https://eth.llamarpc.com');
define('POLYGON_RPC_URL',    getenv('POLYGON_RPC_URL')    ?: 'https://polygon-rpc.com');
define('BASE_RPC_URL',       getenv('BASE_RPC_URL')       ?: 'https://mainnet.base.org');
define('ARBITRUM_RPC_URL',   getenv('ARBITRUM_RPC_URL')   ?: 'https://arb1.arbitrum.io/rpc');

// ─── Safety Limits ────────────────────────────────────────────
define('FIN_MAX_AUTO_PAYMENT_USD', 500);    // Max auto-payment without approval
define('FIN_MAX_TRADE_USD',        1000);   // Max single trade without approval
define('FIN_MAX_PAYOUT_USD',       5000);   // Max single payout without approval
define('FIN_DAILY_TRADE_LIMIT',    10000);  // Daily aggregate trade limit

// ─── Shared Financial Helpers ─────────────────────────────────

function finToCents(float $amount): int {
    return (int) round($amount * 100);
}

function finFromCents(int $cents): float {
    return round($cents / 100, 2);
}

function finRequireAuth(): void {
    if (finIsInternalCall()) return;
    if (empty($_SESSION['logged_in']) || empty($_SESSION['client_id'])) {
        jsonResponse(['error' => 'Authentication required'], 401);
    }
}

function finRequireAdmin(): void {
    finRequireAuth();
    $isAdmin = !empty($_SESSION['is_admin']) || ($_SESSION['client_id'] ?? 0) === 33;
    if (!$isAdmin) {
        jsonResponse(['error' => 'Admin access required'], 403);
    }
}

function finIsInternalCall(): bool {
    $secret = defined('INTERNAL_SECRET') ? INTERNAL_SECRET : '';
    return $secret && isset($_SERVER['HTTP_X_INTERNAL_SECRET']) && hash_equals($secret, $_SERVER['HTTP_X_INTERNAL_SECRET']);
}

function finRequireAdminOrInternal(): void {
    if (!finIsInternalCall()) {
        finRequireAdmin();
    }
}

function finGetClientId(): int {
    if (finIsInternalCall()) {
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        return (int) ($body['client_id'] ?? $_SESSION['client_id'] ?? 0);
    }
    return (int) ($_SESSION['client_id'] ?? 0);
}

/**
 * Make an authenticated HTTP request to an external API
 */
function finApiRequest(string $url, string $method = 'GET', ?array $data = null, array $headers = []): array {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 3,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $defaultHeaders = ['Accept: application/json'];
    if ($data !== null && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        $jsonBody = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
        $defaultHeaders[] = 'Content-Type: application/json';
        $defaultHeaders[] = 'Content-Length: ' . strlen($jsonBody);
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($defaultHeaders, $headers));

    switch ($method) {
        case 'POST':   curl_setopt($ch, CURLOPT_POST, true); break;
        case 'PUT':    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT'); break;
        case 'PATCH':  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH'); break;
        case 'DELETE': curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE'); break;
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['success' => false, 'error' => $error, 'http_code' => 0];
    }

    $decoded = json_decode($response, true);
    return [
        'success'   => $httpCode >= 200 && $httpCode < 300,
        'http_code' => $httpCode,
        'data'      => $decoded ?? $response,
    ];
}

/**
 * Log financial operation to audit trail
 */
function finAuditLog(string $action, string $module, array $details = []): void {
    try {
        $db = getDB();
        $db->exec("CREATE TABLE IF NOT EXISTS fin_audit_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            client_id INT DEFAULT 0,
            action VARCHAR(100) NOT NULL,
            module VARCHAR(50) NOT NULL,
            details JSON,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_client (client_id),
            INDEX idx_module (module),
            INDEX idx_date (created_at)
        )");
        $stmt = $db->prepare("INSERT INTO fin_audit_log (client_id, action, module, details, ip_address) VALUES (?,?,?,?,?)");
        $stmt->execute([
            finGetClientId(),
            sanitize($action, 100),
            sanitize($module, 50),
            json_encode($details),
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
        ]);
    } catch (Exception $e) {
        error_log("finAuditLog error: " . $e->getMessage());
    }
}

/**
 * Store/retrieve OAuth tokens for third-party integrations
 */
function finStoreToken(string $provider, int $clientId, array $tokenData): void {
    $db = getDB();
    $db->exec("CREATE TABLE IF NOT EXISTS fin_oauth_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        provider VARCHAR(50) NOT NULL,
        access_token TEXT NOT NULL,
        refresh_token TEXT,
        token_type VARCHAR(30) DEFAULT 'Bearer',
        expires_at DATETIME,
        scope TEXT,
        extra JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_client_provider (client_id, provider)
    )");

    $stmt = $db->prepare("INSERT INTO fin_oauth_tokens (client_id, provider, access_token, refresh_token, token_type, expires_at, scope, extra)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE access_token=VALUES(access_token), refresh_token=VALUES(refresh_token),
        token_type=VALUES(token_type), expires_at=VALUES(expires_at), scope=VALUES(scope), extra=VALUES(extra)");
    $stmt->execute([
        $clientId,
        $provider,
        $tokenData['access_token'],
        $tokenData['refresh_token'] ?? null,
        $tokenData['token_type'] ?? 'Bearer',
        $tokenData['expires_at'] ?? null,
        $tokenData['scope'] ?? null,
        json_encode($tokenData['extra'] ?? [])
    ]);
}

function finGetToken(string $provider, int $clientId): ?array {
    $db = getDB();
    $db->exec("CREATE TABLE IF NOT EXISTS fin_oauth_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        provider VARCHAR(50) NOT NULL,
        access_token TEXT NOT NULL,
        refresh_token TEXT,
        token_type VARCHAR(30) DEFAULT 'Bearer',
        expires_at DATETIME,
        scope TEXT,
        extra JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_client_provider (client_id, provider)
    )");
    $stmt = $db->prepare("SELECT * FROM fin_oauth_tokens WHERE provider = ? AND client_id = ?");
    $stmt->execute([$provider, $clientId]);
    $token = $stmt->fetch();
    if (!$token) return null;

    // Check expiry
    if ($token['expires_at'] && strtotime($token['expires_at']) < time()) {
        $token['expired'] = true;
    }
    return $token;
}

/**
 * Get integration status for all financial modules
 */
function finGetIntegrationStatus(): array {
    return [
        'stripe_tax'     => STRIPE_TAX_ENABLED === 'true',
        'stripe_connect' => !empty(STRIPE_CONNECT_ONBOARDING),
        'plaid'          => !empty(PLAID_CLIENT_ID) && !empty(PLAID_SECRET),
        'mercury'        => !empty(MERCURY_API_KEY),
        'wise'           => !empty(WISE_API_KEY),
        'paypal'         => !empty(PAYPAL_CLIENT_ID) && !empty(PAYPAL_SECRET),
        'xero'           => !empty(XERO_CLIENT_ID) && !empty(XERO_CLIENT_SECRET),
        'quickbooks'     => !empty(QBO_CLIENT_ID) && !empty(QBO_CLIENT_SECRET),
        'chartmogul'     => !empty(CHARTMOGUL_API_KEY),
        'profitwell'     => !empty(PROFITWELL_API_KEY),
        'taxjar'         => !empty(TAXJAR_API_KEY),
        'kraken'         => !empty(KRAKEN_API_KEY),
        'coinbase'       => !empty(COINBASE_API_KEY),
        'deel'           => !empty(DEEL_API_KEY),
        'oneinch'        => !empty(ONEINCH_API_KEY),
    ];
}
