<?php
/**
 * GoCodeMe WHMCS Provisioning Module
 *
 * Handles all lifecycle events for the GoCodeMe product in WHMCS.
 * Communicates with the GoCodeMe middleware API to activate/suspend/terminate
 * customer workspaces.
 *
 * Installation: copy the gocodeme/ folder to
 *   <whmcs_root>/modules/servers/gocodeme/
 */

if (!defined('WHMCS')) die('Access denied');

// ── Module metadata ────────────────────────────────────────────────────────

function gocodeme_MetaData(): array
{
    return [
        'DisplayName'               => 'GoCodeMe',
        'APIVersion'                => '1.1',
        'RequiresServer'            => false,
        'DefaultNonSSLPort'         => '3001',
        'DefaultSSLPort'            => '3001',
        'ServiceSingleSignOnLabel'  => 'Open GoCodeMe Editor',
    ];
}

function gocodeme_ConfigOptions(): array
{
    return [
        'Plan' => [
            'Type'        => 'dropdown',
            'Options'     => 'free,builder,professional,studio,business,enterprise,team5,team10,team25,api_starter,api_pro,api_scale,reseller_bronze,reseller_silver,reseller_gold',
            'Description' => 'GoCodeMe plan tier',
            'Default'     => 'professional',
        ],
        'Token Allowance' => [
            'Type'        => 'text',
            'Size'        => 10,
            'Description' => 'Monthly token limit (overrides plan default if set)',
            'Default'     => '',
        ],
    ];
}

// ── Helpers ────────────────────────────────────────────────────────────────

/**
 * Call the GoCodeMe middleware API.
 *
 * @param string $method   HTTP method (GET/POST/DELETE)
 * @param string $path     API path e.g. /api/tokens/provision
 * @param array  $body     JSON body
 * @param array  $params   Server params from WHMCS
 * @return array           ['success' => bool, 'data' => mixed, 'error' => string]
 */
function _gocodeme_api(string $method, string $path, array $body, array $params): array
{
    $host   = rtrim($params['serverhostname'] ?? 'http://localhost:3001', '/');
    $secret = $params['serverpassword'] ?? '';  // stored as Server Password in WHMCS

    $url = $host . $path;
    $ch  = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_CUSTOMREQUEST  => strtoupper($method),
        CURLOPT_POSTFIELDS     => json_encode($body),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
            'X-WHMCS-Secret: ' . $secret,
        ],
    ]);

    $raw  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($err) {
        return ['success' => false, 'data' => null, 'error' => $err];
    }

    $data = json_decode($raw, true);
    return [
        'success' => $code >= 200 && $code < 300 && ($data['ok'] ?? false),
        'data'    => $data,
        'error'   => $data['error'] ?? "HTTP $code",
    ];
}

/**
 * Get the DirectAdmin username for a WHMCS client.
 * Uses the service username ($params['username']) which WHMCS populates from
 * the server module, then checks custom fields as a fallback.
 */
function _gocodeme_da_username(array $params): string
{
    // Primary: the service-level username WHMCS stores for this hosting account
    if (!empty($params['username'])) {
        return trim($params['username']);
    }

    // Fallback: service custom field
    foreach (($params['customfields'] ?? []) as $field) {
        $name = strtolower($field['name'] ?? '');
        if (str_contains($name, 'directadmin') || str_contains($name, 'da username')) {
            if (!empty($field['value'])) return trim($field['value']);
        }
    }

    return '';
}

function _gocodeme_plan(array $params): string
{
    return strtolower(trim($params['configoptions']['Plan'] ?? 'professional'));
}

/**
 * Determine if a plan is a Team plan.
 */
function _gocodeme_is_team_plan(string $plan): bool
{
    return in_array($plan, ['team5', 'team10', 'team25'], true);
}

/**
 * Determine if a plan is an API plan.
 */
function _gocodeme_is_api_plan(string $plan): bool
{
    return in_array($plan, ['api_starter', 'api_pro', 'api_scale'], true);
}

/**
 * Determine if a plan is a Reseller plan.
 */
function _gocodeme_is_reseller_plan(string $plan): bool
{
    return in_array($plan, ['reseller_bronze', 'reseller_silver', 'reseller_gold'], true);
}

// ── Lifecycle functions ────────────────────────────────────────────────────

/**
 * Create: called on purchase. Activates the customer's GoCodeMe access,
 * OR credits a token top-up for one-time Token Pack products.
 */
function gocodeme_CreateAccount(array $params): string
{
    $clientId  = $params['userid'];

    // ── Token Pack top-up (one-time purchases, configoption1 = 'topup') ────
    if (($params['configoption1'] ?? '') === 'topup') {
        $bonusTokens = (int) ($params['configoption2'] ?? 100000);

        $result = _gocodeme_api('POST', '/api/tokens/topup', [
            'whmcsClientId' => $clientId,
            'bonusTokens'   => $bonusTokens,
        ], $params);

        if (!$result['success']) {
            return 'Error: ' . $result['error'];
        }

        logActivity("GoCodeMe: credited {$bonusTokens} bonus tokens to client {$clientId} via Token Pack");
        return 'success';
    }

    // ── Regular plan provisioning ──────────────────────────────────────────
    $plan      = _gocodeme_plan($params);
    $daUser    = _gocodeme_da_username($params);

    // ── Team plan provisioning ──────────────────────────────────────────────
    if (_gocodeme_is_team_plan($plan)) {
        $result = _gocodeme_api('POST', '/api/teams/provision', [
            'whmcsClientId' => $clientId,
            'plan'          => $plan,
        ], $params);

        if (!$result['success']) {
            return 'Error provisioning team: ' . $result['error'];
        }

        // Also provision standard access so team owner can use the IDE
        _gocodeme_api('POST', '/api/tokens/provision', [
            'whmcsClientId' => $clientId,
            'plan'          => 'professional',
            'daUsername'    => $daUser,
        ], $params);

        _gocodeme_api('POST', '/api/access/activate', [
            'whmcsClientId' => $clientId,
            'daUsername'    => $daUser,
            'plan'          => 'professional',
        ], $params);

        logActivity("GoCodeMe: team plan '$plan' provisioned for client $clientId");
        return 'success';
    }

    // ── API plan provisioning ──────────────────────────────────────────────
    if (_gocodeme_is_api_plan($plan)) {
        $result = _gocodeme_api('POST', '/api/developer/provision', [
            'whmcsClientId' => $clientId,
            'plan'          => $plan,
        ], $params);

        if (!$result['success']) {
            return 'Error provisioning API access: ' . $result['error'];
        }

        logActivity("GoCodeMe: API plan '$plan' provisioned for client $clientId");
        return 'success';
    }

    // ── Reseller plan provisioning ─────────────────────────────────────────
    if (_gocodeme_is_reseller_plan($plan)) {
        // Strip 'reseller_' prefix for the middleware
        $resellerTier = str_replace('reseller_', '', $plan);
        $result = _gocodeme_api('POST', '/api/reseller/provision', [
            'whmcsClientId' => $clientId,
            'plan'          => $resellerTier,
        ], $params);

        if (!$result['success']) {
            return 'Error provisioning reseller: ' . $result['error'];
        }

        logActivity("GoCodeMe: reseller plan '$plan' provisioned for client $clientId");
        return 'success';
    }

    // ── Standard IDE plan provisioning ─────────────────────────────────────

    // Provision token limit
    $result = _gocodeme_api('POST', '/api/tokens/provision', [
        'whmcsClientId' => $clientId,
        'plan'          => $plan,
        'daUsername'    => $daUser,
    ], $params);

    if (!$result['success']) {
        return 'Error: ' . $result['error'];
    }

    // Activate access in middleware
    $result2 = _gocodeme_api('POST', '/api/access/activate', [
        'whmcsClientId' => $clientId,
        'daUsername'    => $daUser,
        'plan'          => $plan,
    ], $params);

    if (!$result2['success']) {
        return 'Error activating access: ' . $result2['error'];
    }

    logActivity("GoCodeMe: activated for client $clientId (DA: $daUser, plan: $plan)");
    return 'success';
}

/**
 * Suspend: called on failed payment or manual suspension.
 */
function gocodeme_SuspendAccount(array $params): string
{
    $clientId = $params['userid'];

    $result = _gocodeme_api('POST', '/api/access/suspend', [
        'whmcsClientId' => $clientId,
    ], $params);

    if (!$result['success']) {
        return 'Error: ' . $result['error'];
    }

    logActivity("GoCodeMe: suspended client $clientId");
    return 'success';
}

/**
 * Unsuspend: called when payment clears or suspension is manually lifted.
 */
function gocodeme_UnsuspendAccount(array $params): string
{
    $clientId = $params['userid'];
    $plan     = _gocodeme_plan($params);

    $result = _gocodeme_api('POST', '/api/access/unsuspend', [
        'whmcsClientId' => $clientId,
        'plan'          => $plan,
    ], $params);

    if (!$result['success']) {
        return 'Error: ' . $result['error'];
    }

    logActivity("GoCodeMe: unsuspended client $clientId");
    return 'success';
}

/**
 * Terminate: called on cancellation. Revokes access. Files are untouched.
 */
function gocodeme_TerminateAccount(array $params): string
{
    $clientId = $params['userid'];

    $result = _gocodeme_api('POST', '/api/access/terminate', [
        'whmcsClientId' => $clientId,
    ], $params);

    if (!$result['success']) {
        return 'Error: ' . $result['error'];
    }

    logActivity("GoCodeMe: terminated client $clientId");
    return 'success';
}

/**
 * ChangePackage: called on upgrade/downgrade. Updates token allowance immediately.
 * Handles standard plans, team plans, API plans, and reseller plans.
 */
function gocodeme_ChangePackage(array $params): string
{
    $clientId = $params['userid'];
    $plan     = _gocodeme_plan($params);

    // ── Team plan change ──
    if (_gocodeme_is_team_plan($plan)) {
        $result = _gocodeme_api('POST', '/api/teams/provision', [
            'whmcsClientId' => $clientId,
            'plan'          => $plan,
        ], $params);
        if (!$result['success']) return 'Error: ' . $result['error'];
        logActivity("GoCodeMe: team plan changed to $plan for client $clientId");
        return 'success';
    }

    // ── API plan change ──
    if (_gocodeme_is_api_plan($plan)) {
        $result = _gocodeme_api('POST', '/api/developer/provision', [
            'whmcsClientId' => $clientId,
            'plan'          => $plan,
        ], $params);
        if (!$result['success']) return 'Error: ' . $result['error'];
        logActivity("GoCodeMe: API plan changed to $plan for client $clientId");
        return 'success';
    }

    // ── Reseller plan change ──
    if (_gocodeme_is_reseller_plan($plan)) {
        $resellerTier = str_replace('reseller_', '', $plan);
        $result = _gocodeme_api('POST', '/api/reseller/provision', [
            'whmcsClientId' => $clientId,
            'plan'          => $resellerTier,
        ], $params);
        if (!$result['success']) return 'Error: ' . $result['error'];
        logActivity("GoCodeMe: reseller plan changed to $plan for client $clientId");
        return 'success';
    }

    // ── Standard IDE plan change ──
    $result = _gocodeme_api('POST', '/api/tokens/provision', [
        'whmcsClientId' => $clientId,
        'plan'          => $plan,
    ], $params);

    if (!$result['success']) {
        return 'Error: ' . $result['error'];
    }

    logActivity("GoCodeMe: plan changed to $plan for client $clientId");
    return 'success';
}

/**
 * Renew: called on billing cycle renewal. Resets token counter.
 */
function gocodeme_RenewAccount(array $params): string
{
    $clientId = $params['userid'];

    $result = _gocodeme_api('POST', '/api/tokens/reset', [
        'whmcsClientId' => $clientId,
    ], $params);

    if (!$result['success']) {
        return 'Error: ' . $result['error'];
    }

    logActivity("GoCodeMe: token counter reset for client $clientId on renewal");
    return 'success';
}

/**
 * UsageMetrics: push token usage to WHMCS for display on invoices/dashboard.
 */
function gocodeme_UsageMetrics(array $params): array
{
    $clientId = $params['userid'];

    $result = _gocodeme_api('GET', "/api/tokens/usage?whmcsClientId=$clientId", [], $params);

    if (!$result['success']) {
        return [];
    }

    $usage = $result['data']['usage'] ?? [];

    return [
        [
            'metric'      => 'AI Tokens Used',
            'used'        => $usage['used']  ?? 0,
            'capacity'    => $usage['limit'] ?? 0,
            'units'       => 'tokens',
        ],
    ];
}

// ── Client area ────────────────────────────────────────────────────────────

/**
 * Add "Open GoCodeMe Editor" button in the WHMCS client area service view.
 */
function gocodeme_ClientAreaCustomButtonArray(): array
{
    return [
        'Open GoCodeMe Editor'   => 'sso_redirect',
        'Login to DirectAdmin'   => 'da_login',
    ];
}

/**
 * DirectAdmin SSO login — creates a login key via DA API and redirects.
 */
function gocodeme_da_login(array $params): array
{
    $daUsername = _gocodeme_da_username($params);
    if (empty($daUsername)) {
        return ['templatefile' => 'error', 'vars' => ['error' => 'DirectAdmin username not found for this account.']];
    }

    // Use the middleware to generate a DA login session
    $result = _gocodeme_api('POST', '/api/da/login-key', [
        'daUsername'    => $daUsername,
        'whmcsClientId' => $params['userid'],
    ], $params);

    if ($result['success'] && !empty($result['data']['loginUrl'])) {
        header('Location: ' . $result['data']['loginUrl']);
        exit;
    }

    // Fallback: direct DA login page
    $daHost = 'https://gositeme.com:2222';
    header('Location: ' . $daHost);
    exit;
}

/**
 * SSO redirect — generates a signed JWT and redirects the customer to the IDE.
 */
function gocodeme_sso_redirect(array $params): array
{
    $clientId = $params['userid'];
    $plan     = _gocodeme_plan($params);
    $daUser   = _gocodeme_da_username($params);

    // Call middleware to generate an SSO token for this client
    $result = _gocodeme_api('POST', '/api/sso/generate', [
        'whmcsClientId' => $clientId,
        'plan'          => $plan,
    ], $params);

    // Self-healing: if the middleware lost the access key (e.g. Redis restart),
    // re-activate and retry the SSO token generation.
    if (!$result['success'] && stripos($result['error'] ?? '', 'subscription') !== false) {
        _gocodeme_api('POST', '/api/access/activate', [
            'whmcsClientId' => $clientId,
            'daUsername'    => $daUser,
            'plan'          => $plan,
        ], $params);

        $result = _gocodeme_api('POST', '/api/sso/generate', [
            'whmcsClientId' => $clientId,
            'plan'          => $plan,
        ], $params);
    }

    if (!$result['success']) {
        return ['templatefile' => 'error', 'vars' => ['error' => $result['error']]];
    }

    $token   = $result['data']['token'] ?? '';

    // Redirect to the GoCodeMe dashboard with the JWT in the URL fragment.
    // The dashboard JS reads #token=<jwt> from location.hash, stores it in
    // sessionStorage, then uses it as the Bearer token for all API calls.
    // We use the configured server hostname so this works on any environment.
    $host    = rtrim($params['serverhostname'] ?? 'http://localhost:3001', '/');
    $ideUrl  = $host . '/dashboard#token=' . urlencode($token);

    header('Location: ' . $ideUrl);
    exit;
}

/**
 * Client area output — shows token usage summary.
 */
function gocodeme_ClientArea(array $params): array
{
    $clientId = $params['userid'];
    $result   = _gocodeme_api('GET', "/api/tokens/usage?whmcsClientId=$clientId", [], $params);
    $usage    = $result['data']['usage'] ?? ['used' => 0, 'limit' => 0, 'percentUsed' => 0];

    return [
        'templatefile' => 'clientarea',
        'vars'         => [
            'used'        => number_format($usage['used']),
            'limit'       => number_format($usage['limit']),
            'percent'     => $usage['percentUsed'] ?? 0,
            'plan'        => _gocodeme_plan($params),
            'daUsername'  => _gocodeme_da_username($params),
            'serviceId'   => $params['serviceid'] ?? '',
        ],
    ];
}

// ── Admin area test connection ─────────────────────────────────────────────

function gocodeme_TestConnection(array $params): array
{
    $result = _gocodeme_api('GET', '/health', [], $params);

    if ($result['success'] || ($result['data']['ok'] ?? false)) {
        return ['success' => true, 'error' => ''];
    }

    return ['success' => false, 'error' => $result['error'] ?? 'Could not reach GoCodeMe middleware'];
}

// ── Product Addon — Token Top-Up Pack ─────────────────────────────────────
//
// Create a WHMCS Product Addon called "GoCodeMe Token Top-Up" and set:
//   Module:          GoCodeMe
//   Addon Module:    (use these functions automatically via the addon prefix)
//
// ConfigOptions for the addon:
//   Bonus Tokens: 500000 / 1000000 / 2500000 / 5000000
//
// When a customer orders the addon, WHMCS calls gocodeme_AddonActivate which
// POSTs to /api/tokens/topup to credit the extra tokens immediately.

/**
 * Return config options for the Token Top-Up addon.
 * WHMCS calls this function to show addon settings in the admin area.
 */
function gocodeme_AddonConfig(): array
{
    return [
        'Bonus Tokens' => [
            'Type'        => 'dropdown',
            'Options'     => '500000,1000000,2500000,5000000',
            'Description' => 'Extra tokens to add to monthly allowance',
            'Default'     => '500000',
        ],
    ];
}

/**
 * Called when the token top-up addon is activated (ordered/paid).
 * Credits bonus tokens to the customer immediately.
 */
function gocodeme_AddonActivate(array $params): array
{
    $clientId    = $params['userid'];
    $bonusTokens = (int) ($params['configoptions']['Bonus Tokens'] ?? 500000);

    $result = _gocodeme_api('POST', '/api/tokens/topup', [
        'whmcsClientId' => $clientId,
        'bonusTokens'   => $bonusTokens,
    ], $params);

    if (!$result['success']) {
        return ['status' => 'error', 'description' => $result['error']];
    }

    logActivity("GoCodeMe: credited {$bonusTokens} bonus tokens to client {$clientId}");
    return ['status' => 'success', 'description' => "Credited {$bonusTokens} tokens"];
}

/**
 * Called when the addon is cancelled/terminated.  No-op — tokens already used.
 */
function gocodeme_AddonTerminate(array $params): array
{
    return ['status' => 'success', 'description' => 'Top-up addon terminated'];
}

/**
 * Called on addon suspension (e.g. unpaid renewal).  No-op for top-ups.
 */
function gocodeme_AddonSuspend(array $params): array
{
    return ['status' => 'success', 'description' => 'Top-up addon suspended'];
}

/**
 * Called on addon unsuspension.  No-op for top-ups.
 */
function gocodeme_AddonUnsuspend(array $params): array
{
    return ['status' => 'success', 'description' => 'Top-up addon unsuspended'];
}
