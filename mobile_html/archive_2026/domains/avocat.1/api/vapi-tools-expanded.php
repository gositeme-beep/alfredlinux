<?php
$GLOBALS['CSRF_EXEMPT'] = true; // VAPI server-secret verification
require_once dirname(__DIR__) . '/includes/api-security.php';
/**
 * Alfred Expanded Voice Tools — v17.0
 * Fills ALL ecosystem gaps for full autonomous coverage
 * 
 * Categories added:
 *  - Affiliate management
 *  - Collaboration sessions
 *  - Crypto intelligence & transfers
 *  - Deep research
 *  - Evolve mode
 *  - Healthcare patient management
 *  - Intel briefings
 *  - Investor portal
 *  - Messaging gateway (Telegram, WhatsApp, Slack, Discord)
 *  - Notifications
 *  - Pulse social feed
 *  - Reporting engine
 *  - Reseller management
 *  - Self-healing
 *  - Small Biz CRM
 *  - System audit
 *  - Treasury
 *  - World events
 *  - ZPE research
 *  - News feeds
 *  - Hosting management (addon domains, databases, FTP, subdomains, cron, redirects, backups, apps)
 *  - Site doctor
 *  - AI image generation
 *  - Support chat
 *  - Agent tracker
 *  - Autopilot management
 *  - Enterprise billing
 *  - Gamification profile & XP
 *  - Cart management
 */

if (!defined('GOSITEME_API')) {
    http_response_code(403);
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// Internal API proxy — calls pay/api or api endpoints via localhost HTTP
// ═══════════════════════════════════════════════════════════════════════════
function alfredInternalAPI($path, $method = 'GET', $data = [], $extraHeaders = []) {
    $url = 'http://127.0.0.1' . $path;
    $ch = curl_init($url);
    $headers = array_merge(['Content-Type: application/json'], $extraHeaders);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_HTTPHEADER     => $headers,
    ];
    if ($method === 'POST') {
        $opts[CURLOPT_POST] = true;
        $opts[CURLOPT_POSTFIELDS] = json_encode($data);
    }
    curl_setopt_array($ch, $opts);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        error_log("[alfredInternalAPI] cURL error: $err for $path");
        return ['error' => 'Service temporarily unavailable'];
    }
    $decoded = json_decode($response, true);
    return $decoded ?: ['raw' => $response, 'http_code' => $httpCode];
}

// Helper for pay/api calls with client auth header
function alfredPayAPI($endpoint, $method = 'GET', $data = [], $clientId = null) {
    $headers = [];
    if ($clientId) {
        $headers[] = 'X-Client-Id: ' . $clientId;
        $headers[] = 'X-Internal-Alfred: 1';
    }
    return alfredInternalAPI("/pay/api/$endpoint", $method, $data, $headers);
}

// Helper for main api calls
function alfredMainAPI($endpoint, $method = 'GET', $data = []) {
    return alfredInternalAPI("/api/$endpoint", $method, $data, ['X-Internal-Alfred: 1']);
}


// ═══════════════════════════════════════════════════════════════════════════
// AFFILIATE MANAGEMENT
// ═══════════════════════════════════════════════════════════════════════════

function toolAffiliateRegister($args) {
    $clientId = $args['clientId'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $db = billingDB();
    // Check if already an affiliate
    $stmt = $db->prepare("SELECT id, partner_id, tier FROM affiliates WHERE client_id = ? LIMIT 1");
    $stmt->execute([$clientId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($existing) {
        return ['success' => true, 'already_registered' => true,
                'partner_id' => $existing['partner_id'], 'tier' => $existing['tier'],
                'message' => "You're already registered as an affiliate! Your partner ID is {$existing['partner_id']}, tier: {$existing['tier']}."];
    }

    $partnerId = 'GSM-' . strtoupper(bin2hex(random_bytes(4)));
    $stmt = $db->prepare("INSERT INTO affiliates (client_id, partner_id, tier, status, created_at) VALUES (?, ?, 'bronze', 'active', NOW())");
    $stmt->execute([$clientId, $partnerId]);

    return ['success' => true, 'partner_id' => $partnerId, 'tier' => 'bronze',
            'message' => "You're now registered as an affiliate! Your partner ID is $partnerId. Share your referral link to start earning commissions."];
}

function toolAffiliateStats($args) {
    $clientId = $args['clientId'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $db = billingDB();
    $stmt = $db->prepare("SELECT * FROM affiliates WHERE client_id = ? LIMIT 1");
    $stmt->execute([$clientId]);
    $aff = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$aff) return ['error' => 'Not registered as an affiliate. Would you like to sign up?'];

    $stmt = $db->prepare("SELECT COUNT(*) as total_referrals, SUM(CASE WHEN status='converted' THEN 1 ELSE 0 END) as conversions FROM affiliate_referrals WHERE affiliate_id = ?");
    $stmt->execute([$aff['id']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $db->prepare("SELECT COALESCE(SUM(amount),0) as total_earned, COALESCE(SUM(CASE WHEN status='pending' THEN amount ELSE 0 END),0) as pending FROM affiliate_commissions WHERE affiliate_id = ?");
    $stmt->execute([$aff['id']]);
    $earnings = $stmt->fetch(PDO::FETCH_ASSOC);

    return ['success' => true, 'partner_id' => $aff['partner_id'], 'tier' => $aff['tier'],
            'referrals' => (int)($stats['total_referrals'] ?? 0),
            'conversions' => (int)($stats['conversions'] ?? 0),
            'total_earned' => number_format((float)($earnings['total_earned'] ?? 0), 2),
            'pending' => number_format((float)($earnings['pending'] ?? 0), 2),
            'message' => "Affiliate stats: {$stats['total_referrals']} referrals, {$stats['conversions']} conversions. " .
                         "Total earned: \${$earnings['total_earned']}, pending: \${$earnings['pending']}. Tier: {$aff['tier']}."];
}

function toolAffiliateLink($args) {
    $clientId = $args['clientId'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $db = billingDB();
    $stmt = $db->prepare("SELECT partner_id FROM affiliates WHERE client_id = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$clientId]);
    $partnerId = $stmt->fetchColumn();
    if (!$partnerId) return ['error' => 'Not an active affiliate'];

    $link = "https://gositeme.com/?ref=$partnerId";
    return ['success' => true, 'referral_link' => $link, 'partner_id' => $partnerId,
            'message' => "Your referral link is: $link — share it anywhere and earn commissions on every signup!"];
}

function toolAffiliateRequestPayout($args) {
    $clientId = $args['clientId'] ?? null;
    $method   = $args['method'] ?? 'paypal';
    if (!$clientId) return ['error' => 'clientId required'];

    $db = billingDB();
    $stmt = $db->prepare("SELECT id, partner_id FROM affiliates WHERE client_id = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$clientId]);
    $aff = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$aff) return ['error' => 'Not an active affiliate'];

    $stmt = $db->prepare("SELECT COALESCE(SUM(amount),0) FROM affiliate_commissions WHERE affiliate_id = ? AND status = 'approved'");
    $stmt->execute([$aff['id']]);
    $available = (float)$stmt->fetchColumn();

    if ($available < 50) return ['error' => "Minimum payout is \$50. You have \$$available available."];

    try {
        $stmt = $db->prepare("INSERT INTO affiliate_payouts (affiliate_id, amount, method, status, requested_at) VALUES (?, ?, ?, 'pending', NOW())");
        $stmt->execute([$aff['id'], $available, $method]);
        return ['success' => true, 'amount' => $available, 'method' => $method,
                'message' => "Payout of \$$available via $method has been requested. You'll receive it within 5-7 business days."];
    } catch (Exception $e) {
        return ['error' => 'Could not process payout request right now.'];
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// COLLABORATION TOOLS
// ═══════════════════════════════════════════════════════════════════════════

function toolCollabCreateSession($args) {
    $clientId = $args['clientId'] ?? null;
    $name     = $args['name'] ?? 'Collaboration Session';
    $type     = $args['type'] ?? 'code'; // code, doc, whiteboard
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredMainAPI('collaboration', 'POST', [
        'action' => 'create_session', 'client_id' => $clientId,
        'name' => $name, 'type' => $type
    ]);
    $result['message'] = $result['message'] ?? "Collaboration session '$name' created. Share the code with your team to join.";
    return $result;
}

function toolCollabJoinSession($args) {
    $sessionCode = $args['sessionCode'] ?? $args['code'] ?? null;
    $clientId    = $args['clientId'] ?? null;
    if (!$sessionCode || !$clientId) return ['error' => 'sessionCode and clientId required'];

    $result = alfredMainAPI('collaboration', 'POST', [
        'action' => 'join_session', 'code' => $sessionCode, 'client_id' => $clientId
    ]);
    $result['message'] = $result['message'] ?? "Joined collaboration session $sessionCode.";
    return $result;
}

function toolCollabCreateDoc($args) {
    $clientId  = $args['clientId'] ?? null;
    $sessionId = $args['sessionId'] ?? null;
    $title     = $args['title'] ?? 'Untitled Document';
    $content   = $args['content'] ?? '';
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredMainAPI('collaboration', 'POST', [
        'action' => 'doc_create', 'client_id' => $clientId,
        'session_id' => $sessionId, 'title' => $title, 'content' => $content
    ]);
    $result['message'] = $result['message'] ?? "Document '$title' created in the collaboration session.";
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// CRYPTO INTELLIGENCE
// ═══════════════════════════════════════════════════════════════════════════

function toolCryptoDashboard($args) {
    $clientId = $args['clientId'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredMainAPI('crypto-intelligence', 'POST', [
        'action' => 'dashboard', 'client_id' => $clientId
    ]);
    $result['message'] = $result['message'] ?? 'Here is your crypto intelligence dashboard.';
    return $result;
}

function toolCryptoAnalyzeToken($args) {
    $token    = $args['token'] ?? $args['symbol'] ?? null;
    $clientId = $args['clientId'] ?? null;
    if (!$token) return ['error' => 'token symbol required (e.g. BTC, ETH)'];

    $result = alfredMainAPI('crypto-intelligence', 'POST', [
        'action' => 'analyze', 'token' => strtoupper($token), 'client_id' => $clientId
    ]);
    $result['message'] = $result['message'] ?? "Analysis for $token completed.";
    return $result;
}

function toolCryptoSignals($args) {
    $clientId = $args['clientId'] ?? null;
    $result = alfredMainAPI('crypto-intelligence', 'POST', [
        'action' => 'signals', 'client_id' => $clientId
    ]);
    $result['message'] = $result['message'] ?? 'Here are the latest crypto trading signals.';
    return $result;
}

function toolCryptoWatchlist($args) {
    $clientId = $args['clientId'] ?? null;
    $action   = $args['action'] ?? 'list'; // list, add, remove
    $token    = $args['token'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredMainAPI('crypto-intelligence', 'POST', [
        'action' => 'watchlist', 'sub_action' => $action,
        'token' => $token, 'client_id' => $clientId
    ]);
    $result['message'] = $result['message'] ?? 'Watchlist updated.';
    return $result;
}

function toolCryptoPortfolioRisk($args) {
    $clientId = $args['clientId'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredMainAPI('crypto-intelligence', 'POST', [
        'action' => 'portfolio_risk', 'client_id' => $clientId
    ]);
    $result['message'] = $result['message'] ?? 'Portfolio risk analysis complete.';
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// CRYPTO TRANSFERS
// ═══════════════════════════════════════════════════════════════════════════

function toolCryptoGenerateQR($args) {
    $clientId = $args['clientId'] ?? null;
    $amount   = $args['amount'] ?? null;
    $currency = $args['currency'] ?? 'BTC';
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredMainAPI('crypto-transfer', 'POST', [
        'action' => 'generate_qr', 'client_id' => $clientId,
        'amount' => $amount, 'currency' => strtoupper($currency)
    ]);
    $result['message'] = $result['message'] ?? "QR code generated for $currency payment.";
    return $result;
}

function toolCryptoVerifyPayment($args) {
    $txHash   = $args['txHash'] ?? $args['transaction'] ?? null;
    $clientId = $args['clientId'] ?? null;
    if (!$txHash) return ['error' => 'transaction hash required'];

    $result = alfredMainAPI('crypto-transfer', 'POST', [
        'action' => 'verify', 'tx_hash' => $txHash, 'client_id' => $clientId
    ]);
    $result['message'] = $result['message'] ?? 'Payment verification complete.';
    return $result;
}

function toolCryptoWallets($args) {
    $clientId = $args['clientId'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredMainAPI('crypto-transfer', 'POST', [
        'action' => 'wallets', 'client_id' => $clientId
    ]);
    $result['message'] = $result['message'] ?? 'Here are your connected wallets.';
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// DEEP RESEARCH
// ═══════════════════════════════════════════════════════════════════════════

function toolDeepResearch($args) {
    $query    = $args['query'] ?? $args['topic'] ?? null;
    $clientId = $args['clientId'] ?? null;
    $depth    = $args['depth'] ?? 'standard'; // quick, standard, thorough
    if (!$query) return ['error' => 'query or topic required'];

    $result = alfredMainAPI('deep-research', 'POST', [
        'action' => 'research', 'query' => $query,
        'depth' => $depth, 'client_id' => $clientId
    ]);
    $result['message'] = $result['message'] ?? "Deep research initiated on: $query";
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// EVOLVE MODE
// ═══════════════════════════════════════════════════════════════════════════

function toolEvolveStatus($args) {
    $clientId = $args['clientId'] ?? null;
    $result = alfredMainAPI('evolve-mode', 'POST', [
        'action' => 'status', 'client_id' => $clientId
    ]);
    $result['message'] = $result['message'] ?? 'Evolve mode status retrieved.';
    return $result;
}

function toolEvolveActivate($args) {
    $clientId = $args['clientId'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredMainAPI('evolve-mode', 'POST', [
        'action' => 'activate', 'client_id' => $clientId
    ]);
    $result['message'] = $result['message'] ?? 'Evolve mode activated. I will now proactively suggest improvements.';
    return $result;
}

function toolEvolveDeactivate($args) {
    $clientId = $args['clientId'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredMainAPI('evolve-mode', 'POST', [
        'action' => 'deactivate', 'client_id' => $clientId
    ]);
    $result['message'] = $result['message'] ?? 'Evolve mode deactivated.';
    return $result;
}

function toolEvolveProposals($args) {
    $clientId = $args['clientId'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredMainAPI('evolve-mode', 'POST', [
        'action' => 'proposals', 'client_id' => $clientId
    ]);
    $result['message'] = $result['message'] ?? 'Here are your evolve mode proposals.';
    return $result;
}

function toolEvolveApprove($args) {
    $clientId   = $args['clientId'] ?? null;
    $proposalId = $args['proposalId'] ?? null;
    if (!$clientId || !$proposalId) return ['error' => 'clientId and proposalId required'];

    $result = alfredMainAPI('evolve-mode', 'POST', [
        'action' => 'approve', 'client_id' => $clientId, 'proposal_id' => $proposalId
    ]);
    $result['message'] = $result['message'] ?? "Proposal $proposalId approved and will be applied.";
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// HEALTHCARE PATIENT MANAGEMENT
// ═══════════════════════════════════════════════════════════════════════════

function toolPatientCreate($args) {
    $clientId  = $args['clientId'] ?? null;
    $firstName = $args['firstName'] ?? $args['first_name'] ?? null;
    $lastName  = $args['lastName'] ?? $args['last_name'] ?? null;
    $dob       = $args['dob'] ?? $args['dateOfBirth'] ?? null;
    if (!$clientId || !$firstName || !$lastName) return ['error' => 'clientId, firstName, lastName required'];

    $result = alfredMainAPI('healthcare', 'POST', [
        'action' => 'patient_create', 'client_id' => $clientId,
        'first_name' => $firstName, 'last_name' => $lastName,
        'dob' => $dob, 'email' => $args['email'] ?? '', 'phone' => $args['phone'] ?? ''
    ]);
    $result['message'] = $result['message'] ?? "Patient record created for $firstName $lastName.";
    return $result;
}

function toolPatientSearch($args) {
    $clientId = $args['clientId'] ?? null;
    $query    = $args['query'] ?? $args['search'] ?? $args['name'] ?? null;
    if (!$clientId || !$query) return ['error' => 'clientId and query required'];

    $result = alfredMainAPI('healthcare', 'POST', [
        'action' => 'patient_search', 'client_id' => $clientId, 'query' => $query
    ]);
    $result['message'] = $result['message'] ?? "Patient search results for: $query";
    return $result;
}

function toolPatientList($args) {
    $clientId = $args['clientId'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredMainAPI('healthcare', 'POST', [
        'action' => 'patient_list', 'client_id' => $clientId
    ]);
    $result['message'] = $result['message'] ?? 'Here is your patient list.';
    return $result;
}

function toolSoapNoteCreate($args) {
    $clientId  = $args['clientId'] ?? null;
    $patientId = $args['patientId'] ?? $args['patient_id'] ?? null;
    if (!$clientId || !$patientId) return ['error' => 'clientId and patientId required'];

    $result = alfredMainAPI('healthcare', 'POST', [
        'action' => 'soap_create', 'client_id' => $clientId,
        'patient_id' => $patientId,
        'subjective' => $args['subjective'] ?? '', 'objective' => $args['objective'] ?? '',
        'assessment' => $args['assessment'] ?? '', 'plan' => $args['plan'] ?? ''
    ]);
    $result['message'] = $result['message'] ?? 'SOAP note created successfully.';
    return $result;
}

function toolScheduleAppointment($args) {
    $clientId  = $args['clientId'] ?? null;
    $patientId = $args['patientId'] ?? $args['patient_id'] ?? null;
    $dateTime  = $args['dateTime'] ?? $args['date'] ?? null;
    $type      = $args['type'] ?? 'general';
    if (!$clientId || !$patientId || !$dateTime) return ['error' => 'clientId, patientId, and dateTime required'];

    $result = alfredMainAPI('healthcare', 'POST', [
        'action' => 'appt_create', 'client_id' => $clientId,
        'patient_id' => $patientId, 'datetime' => $dateTime, 'type' => $type
    ]);
    $result['message'] = $result['message'] ?? "Appointment scheduled for $dateTime.";
    return $result;
}

function toolRecordVitals($args) {
    $clientId  = $args['clientId'] ?? null;
    $patientId = $args['patientId'] ?? $args['patient_id'] ?? null;
    if (!$clientId || !$patientId) return ['error' => 'clientId and patientId required'];

    $result = alfredMainAPI('healthcare', 'POST', [
        'action' => 'vitals_record', 'client_id' => $clientId,
        'patient_id' => $patientId,
        'bp' => $args['bp'] ?? null, 'hr' => $args['hr'] ?? $args['heartRate'] ?? null,
        'temp' => $args['temp'] ?? $args['temperature'] ?? null,
        'weight' => $args['weight'] ?? null, 'spo2' => $args['spo2'] ?? null
    ]);
    $result['message'] = $result['message'] ?? 'Vital signs recorded.';
    return $result;
}

function toolOrderLabWork($args) {
    $clientId  = $args['clientId'] ?? null;
    $patientId = $args['patientId'] ?? $args['patient_id'] ?? null;
    $tests     = $args['tests'] ?? $args['test'] ?? null;
    if (!$clientId || !$patientId || !$tests) return ['error' => 'clientId, patientId, and tests required'];

    $result = alfredMainAPI('healthcare', 'POST', [
        'action' => 'lab_order', 'client_id' => $clientId,
        'patient_id' => $patientId, 'tests' => $tests
    ]);
    $result['message'] = $result['message'] ?? 'Lab work ordered successfully.';
    return $result;
}

function toolHealthcareDashboard($args) {
    $clientId = $args['clientId'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredMainAPI('healthcare', 'POST', [
        'action' => 'dashboard', 'client_id' => $clientId
    ]);
    $result['message'] = $result['message'] ?? 'Healthcare dashboard loaded.';
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// INTEL BRIEFING
// ═══════════════════════════════════════════════════════════════════════════

function toolIntelBriefing($args) {
    $clientId = $args['clientId'] ?? null;
    $category = $args['category'] ?? 'full';
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredMainAPI('intel-briefing', 'POST', [
        'action' => 'briefing', 'category' => $category, 'client_id' => $clientId
    ]);
    $result['message'] = $result['message'] ?? "Intelligence briefing ($category) generated.";
    return $result;
}

function toolIntelCategories($args) {
    $result = alfredMainAPI('intel-briefing', 'POST', ['action' => 'categories']);
    $result['message'] = $result['message'] ?? 'Available briefing categories listed.';
    return $result;
}

function toolIntelHistory($args) {
    $clientId = $args['clientId'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredMainAPI('intel-briefing', 'POST', [
        'action' => 'history', 'client_id' => $clientId
    ]);
    $result['message'] = $result['message'] ?? 'Briefing history retrieved.';
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// INVESTOR PORTAL
// ═══════════════════════════════════════════════════════════════════════════

function toolInvestorSubmit($args) {
    $name   = $args['name'] ?? null;
    $email  = $args['email'] ?? null;
    $amount = $args['amount'] ?? null;
    if (!$name || !$email) return ['error' => 'name and email required'];

    $result = alfredMainAPI('investor', 'POST', [
        'action' => 'submit', 'name' => $name, 'email' => $email,
        'amount' => $amount, 'message' => $args['message'] ?? ''
    ]);
    $result['message'] = $result['message'] ?? "Investment interest registered for $name. Our team will follow up.";
    return $result;
}

function toolInvestorDashboard($args) {
    $clientId = $args['clientId'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredMainAPI('investor', 'POST', [
        'action' => 'dashboard', 'client_id' => $clientId
    ]);
    $result['message'] = $result['message'] ?? 'Investor dashboard loaded.';
    return $result;
}

function toolInvestorMetrics($args) {
    $result = alfredMainAPI('investor', 'POST', ['action' => 'public_metrics']);
    $result['message'] = $result['message'] ?? 'Platform metrics retrieved.';
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// MESSAGING GATEWAY (Telegram, WhatsApp, Slack, Discord)
// ═══════════════════════════════════════════════════════════════════════════

function toolSendTelegram($args) {
    $chatId  = $args['chatId'] ?? $args['chat_id'] ?? null;
    $message = $args['message'] ?? $args['text'] ?? null;
    if (!$chatId || !$message) return ['error' => 'chatId and message required'];

    $result = alfredMainAPI('messaging-gateway', 'POST', [
        'channel' => 'telegram', 'action' => 'send',
        'chat_id' => $chatId, 'message' => $message
    ]);
    $result['message'] = $result['message'] ?? 'Telegram message sent.';
    return $result;
}

function toolSendWhatsApp($args) {
    $phone   = $args['phone'] ?? $args['to'] ?? null;
    $message = $args['message'] ?? $args['text'] ?? null;
    if (!$phone || !$message) return ['error' => 'phone and message required'];

    $result = alfredMainAPI('messaging-gateway', 'POST', [
        'channel' => 'whatsapp', 'action' => 'send',
        'phone' => $phone, 'message' => $message
    ]);
    $result['message'] = $result['message'] ?? 'WhatsApp message sent.';
    return $result;
}

function toolSendSlackMessage($args) {
    $channel = $args['channel'] ?? $args['slackChannel'] ?? null;
    $message = $args['message'] ?? $args['text'] ?? null;
    if (!$channel || !$message) return ['error' => 'channel and message required'];

    $result = alfredMainAPI('messaging-gateway', 'POST', [
        'channel' => 'slack', 'action' => 'send',
        'slack_channel' => $channel, 'message' => $message
    ]);
    $result['message'] = $result['message'] ?? "Slack message sent to #$channel.";
    return $result;
}

function toolSendDiscordMessage($args) {
    $channel = $args['channel'] ?? $args['discordChannel'] ?? null;
    $message = $args['message'] ?? $args['text'] ?? null;
    if (!$channel || !$message) return ['error' => 'channel and message required'];

    $result = alfredMainAPI('messaging-gateway', 'POST', [
        'channel' => 'discord', 'action' => 'send',
        'discord_channel' => $channel, 'message' => $message
    ]);
    $result['message'] = $result['message'] ?? 'Discord message sent.';
    return $result;
}

function toolMessagingStats($args) {
    $clientId = $args['clientId'] ?? null;
    $result = alfredMainAPI('messaging-gateway', 'POST', [
        'action' => 'stats', 'client_id' => $clientId
    ]);
    $result['message'] = $result['message'] ?? 'Messaging gateway statistics loaded.';
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// NOTIFICATIONS
// ═══════════════════════════════════════════════════════════════════════════

function toolGetNotifications($args) {
    $clientId = $args['clientId'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredMainAPI('notifications', 'POST', [
        'action' => 'list', 'client_id' => $clientId,
        'unread_only' => $args['unreadOnly'] ?? false
    ]);
    $result['message'] = $result['message'] ?? 'Here are your notifications.';
    return $result;
}

function toolMarkNotificationRead($args) {
    $clientId       = $args['clientId'] ?? null;
    $notificationId = $args['notificationId'] ?? $args['id'] ?? 'all';
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredMainAPI('notifications', 'POST', [
        'action' => 'mark_read', 'client_id' => $clientId, 'id' => $notificationId
    ]);
    $result['message'] = $result['message'] ?? ($notificationId === 'all' ? 'All notifications marked as read.' : "Notification $notificationId marked as read.");
    return $result;
}

function toolNotificationPreferences($args) {
    $clientId = $args['clientId'] ?? null;
    $action   = $args['action'] ?? 'get'; // get, update
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredMainAPI('notifications', 'POST', [
        'action' => $action === 'update' ? 'update_preferences' : 'get_preferences',
        'client_id' => $clientId, 'preferences' => $args['preferences'] ?? []
    ]);
    $result['message'] = $result['message'] ?? 'Notification preferences loaded.';
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// PULSE SOCIAL FEED
// ═══════════════════════════════════════════════════════════════════════════

function toolPulsePost($args) {
    $clientId = $args['clientId'] ?? null;
    $content  = $args['content'] ?? $args['text'] ?? $args['message'] ?? null;
    if (!$clientId || !$content) return ['error' => 'clientId and content required'];

    $result = alfredMainAPI('pulse', 'POST', [
        'action' => 'post', 'client_id' => $clientId, 'content' => $content,
        'type' => $args['type'] ?? 'text'
    ]);
    $result['message'] = $result['message'] ?? 'Post published to your Pulse feed.';
    return $result;
}

function toolPulseFeed($args) {
    $clientId = $args['clientId'] ?? null;
    $feed     = $args['feed'] ?? 'home'; // home, trending, mine
    $result = alfredMainAPI('pulse', 'POST', [
        'action' => 'feed', 'client_id' => $clientId, 'feed' => $feed
    ]);
    $result['message'] = $result['message'] ?? "Here's your $feed Pulse feed.";
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// REPORTING ENGINE
// ═══════════════════════════════════════════════════════════════════════════

function toolGenerateEngineReport($args) {
    $clientId   = $args['clientId'] ?? null;
    $reportType = $args['reportType'] ?? $args['type'] ?? 'usage';
    $period     = $args['period'] ?? 'monthly';
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredMainAPI('reporting-engine', 'POST', [
        'action' => $reportType, 'client_id' => $clientId, 'period' => $period
    ]);
    $result['message'] = $result['message'] ?? "$reportType report generated for $period period.";
    return $result;
}

function toolReportDashboardKPIs($args) {
    $clientId = $args['clientId'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredMainAPI('reporting-engine', 'POST', [
        'action' => 'dashboard_kpis', 'client_id' => $clientId
    ]);
    $result['message'] = $result['message'] ?? 'Dashboard KPIs loaded.';
    return $result;
}

function toolReportGrowthMetrics($args) {
    $clientId = $args['clientId'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredMainAPI('reporting-engine', 'POST', [
        'action' => 'growth_metrics', 'client_id' => $clientId
    ]);
    $result['message'] = $result['message'] ?? 'Growth metrics report generated.';
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// RESELLER MANAGEMENT
// ═══════════════════════════════════════════════════════════════════════════

function toolResellerDashboard($args) {
    $clientId = $args['clientId'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    if (!defined('RESELLER_FUNCTIONS_ONLY')) define('RESELLER_FUNCTIONS_ONLY', true);
    require_once __DIR__ . '/../pay/api/reseller.php';

    $db = billingDB();
    ensureResellerTables($db);
    $result = handleDashboard($db, $clientId);
    $result['message'] = $result['message'] ?? 'Reseller dashboard loaded.';
    return $result;
}

function toolResellerClients($args) {
    $clientId = $args['clientId'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    if (!defined('RESELLER_FUNCTIONS_ONLY')) define('RESELLER_FUNCTIONS_ONLY', true);
    require_once __DIR__ . '/../pay/api/reseller.php';

    $db = billingDB();
    ensureResellerTables($db);
    $result = handleClients($db, $clientId);
    $result['message'] = $result['message'] ?? 'Reseller clients listed.';
    return $result;
}

function toolResellerBranding($args) {
    $clientId = $args['clientId'] ?? null;
    $action   = $args['action'] ?? 'get';
    if (!$clientId) return ['error' => 'clientId required'];

    if (!defined('RESELLER_FUNCTIONS_ONLY')) define('RESELLER_FUNCTIONS_ONLY', true);
    require_once __DIR__ . '/../pay/api/reseller.php';

    $db = billingDB();
    ensureResellerTables($db);
    $result = handleBranding($db, $clientId, $args);
    $result['message'] = $result['message'] ?? 'Reseller branding settings loaded.';
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// SELF-HEALING
// ═══════════════════════════════════════════════════════════════════════════

function toolSelfHealingCheck($args) {
    $result = alfredMainAPI('self-healing', 'POST', ['action' => 'check']);
    $result['message'] = $result['message'] ?? 'Self-healing check complete.';
    return $result;
}

function toolSelfHealingStatus($args) {
    $result = alfredMainAPI('self-healing', 'POST', ['action' => 'status']);
    $result['message'] = $result['message'] ?? 'Self-healing system status retrieved.';
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// SMALL BIZ CRM
// ═══════════════════════════════════════════════════════════════════════════

function toolBizContacts($args) {
    $clientId = $args['clientId'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredMainAPI('small-biz', 'POST', [
        'action' => 'contacts_list', 'client_id' => $clientId
    ]);
    $result['message'] = $result['message'] ?? 'Business contacts listed.';
    return $result;
}

function toolBizCreateContact($args) {
    $clientId = $args['clientId'] ?? null;
    $name     = $args['name'] ?? null;
    $email    = $args['email'] ?? '';
    $phone    = $args['phone'] ?? '';
    if (!$clientId || !$name) return ['error' => 'clientId and name required'];

    $result = alfredMainAPI('small-biz', 'POST', [
        'action' => 'contact_create', 'client_id' => $clientId,
        'name' => $name, 'email' => $email, 'phone' => $phone,
        'company' => $args['company'] ?? ''
    ]);
    $result['message'] = $result['message'] ?? "Business contact '$name' created.";
    return $result;
}

function toolBizProjects($args) {
    $clientId = $args['clientId'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredMainAPI('small-biz', 'POST', [
        'action' => 'projects_list', 'client_id' => $clientId
    ]);
    $result['message'] = $result['message'] ?? 'Business projects listed.';
    return $result;
}

function toolBizCreateProject($args) {
    $clientId = $args['clientId'] ?? null;
    $name     = $args['name'] ?? $args['title'] ?? null;
    if (!$clientId || !$name) return ['error' => 'clientId and name required'];

    $result = alfredMainAPI('small-biz', 'POST', [
        'action' => 'project_create', 'client_id' => $clientId,
        'name' => $name, 'description' => $args['description'] ?? '',
        'budget' => $args['budget'] ?? null
    ]);
    $result['message'] = $result['message'] ?? "Project '$name' created.";
    return $result;
}

function toolBizTasks($args) {
    $clientId  = $args['clientId'] ?? null;
    $projectId = $args['projectId'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredMainAPI('small-biz', 'POST', [
        'action' => 'tasks_list', 'client_id' => $clientId, 'project_id' => $projectId
    ]);
    $result['message'] = $result['message'] ?? 'Business tasks listed.';
    return $result;
}

function toolBizCreateTask($args) {
    $clientId  = $args['clientId'] ?? null;
    $title     = $args['title'] ?? $args['name'] ?? null;
    $projectId = $args['projectId'] ?? null;
    if (!$clientId || !$title) return ['error' => 'clientId and title required'];

    $result = alfredMainAPI('small-biz', 'POST', [
        'action' => 'task_create', 'client_id' => $clientId,
        'title' => $title, 'project_id' => $projectId,
        'priority' => $args['priority'] ?? 'medium', 'due_date' => $args['dueDate'] ?? null
    ]);
    $result['message'] = $result['message'] ?? "Task '$title' created.";
    return $result;
}

function toolBizCreateInvoice($args) {
    $clientId  = $args['clientId'] ?? null;
    $contactId = $args['contactId'] ?? null;
    $items     = $args['items'] ?? [];
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredMainAPI('small-biz', 'POST', [
        'action' => 'invoice_create', 'client_id' => $clientId,
        'contact_id' => $contactId, 'items' => $items
    ]);
    $result['message'] = $result['message'] ?? 'Business invoice created.';
    return $result;
}

function toolBizTimeLog($args) {
    $clientId  = $args['clientId'] ?? null;
    $projectId = $args['projectId'] ?? null;
    $hours     = $args['hours'] ?? $args['duration'] ?? null;
    $desc      = $args['description'] ?? '';
    if (!$clientId || !$hours) return ['error' => 'clientId and hours required'];

    $result = alfredMainAPI('small-biz', 'POST', [
        'action' => 'time_create', 'client_id' => $clientId,
        'project_id' => $projectId, 'hours' => $hours, 'description' => $desc
    ]);
    $result['message'] = $result['message'] ?? "$hours hours logged.";
    return $result;
}

function toolBizDashboard($args) {
    $clientId = $args['clientId'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredMainAPI('small-biz', 'POST', [
        'action' => 'dashboard', 'client_id' => $clientId
    ]);
    $result['message'] = $result['message'] ?? 'Business CRM dashboard loaded.';
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// SYSTEM AUDIT
// ═══════════════════════════════════════════════════════════════════════════

function toolSystemAudit($args) {
    $scope = $args['scope'] ?? 'full'; // full, core, ai, agents, comms, security, database
    $result = alfredMainAPI('system-audit', 'POST', ['action' => 'audit', 'scope' => $scope]);
    $result['message'] = $result['message'] ?? "System audit ($scope) complete.";
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// TREASURY
// ═══════════════════════════════════════════════════════════════════════════

function toolTreasuryDashboard($args) {
    $clientId = $args['clientId'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredMainAPI('treasury', 'POST', [
        'action' => 'dashboard', 'client_id' => $clientId
    ]);
    $result['message'] = $result['message'] ?? 'Treasury dashboard loaded.';
    return $result;
}

function toolTreasuryTransaction($args) {
    $clientId = $args['clientId'] ?? null;
    $action   = $args['action'] ?? 'list'; // list, create
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredMainAPI('treasury', 'POST', [
        'action' => "transaction_$action", 'client_id' => $clientId,
        'amount' => $args['amount'] ?? null, 'type' => $args['type'] ?? null,
        'description' => $args['description'] ?? ''
    ]);
    $result['message'] = $result['message'] ?? 'Treasury transaction processed.';
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// WORLD EVENTS
// ═══════════════════════════════════════════════════════════════════════════

function toolWorldEvents($args) {
    $topic  = $args['topic'] ?? $args['query'] ?? 'latest';
    $region = $args['region'] ?? 'global';

    $prompt = "Provide a concise briefing on current world events" .
              ($topic !== 'latest' ? " related to: $topic" : '') .
              ($region !== 'global' ? " in $region" : '') .
              ". Focus on the most significant developments. Be factual and balanced.";

    $response = callAlfred($prompt, ['max_tokens' => 600]);
    return ['success' => true, 'topic' => $topic, 'region' => $region,
            'briefing' => $response, 'message' => $response];
}

// ═══════════════════════════════════════════════════════════════════════════
// ZPE RESEARCH
// ═══════════════════════════════════════════════════════════════════════════

function toolZpeStatus($args) {
    $result = alfredMainAPI('zpe-research', 'POST', ['action' => 'status']);
    $result['message'] = $result['message'] ?? 'ZPE research status retrieved.';
    return $result;
}

function toolZpeTopics($args) {
    $result = alfredMainAPI('zpe-research', 'POST', ['action' => 'topics']);
    $result['message'] = $result['message'] ?? 'ZPE research topics listed.';
    return $result;
}

function toolZpeProgress($args) {
    $result = alfredMainAPI('zpe-research', 'POST', ['action' => 'progress']);
    $result['message'] = $result['message'] ?? 'ZPE research progress report generated.';
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// NEWS FEEDS
// ═══════════════════════════════════════════════════════════════════════════

function toolGetNewsFeeds($args) {
    $clientId = $args['clientId'] ?? null;
    $source   = $args['source'] ?? 'all'; // all, tech, business, general
    $result = alfredMainAPI('feeds', 'POST', [
        'action' => 'list', 'client_id' => $clientId, 'source' => $source
    ]);
    $result['message'] = $result['message'] ?? "News feeds ($source) loaded.";
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// HOSTING MANAGEMENT TOOLS
// ═══════════════════════════════════════════════════════════════════════════

function toolManageAddonDomains($args) {
    $clientId = $args['clientId'] ?? null;
    $action   = $args['action'] ?? 'list'; // list, add, delete
    $domain   = $args['domain'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredPayAPI("addon-domains-api?action=$action&domain=" . urlencode($domain ?? ''), 'POST', [
        'client_id' => $clientId, 'domain' => $domain
    ], $clientId);
    $result['message'] = $result['message'] ?? "Addon domains ($action) processed.";
    return $result;
}

function toolManageDatabases($args) {
    $clientId = $args['clientId'] ?? null;
    $action   = $args['action'] ?? 'list'; // list, create, delete
    $dbName   = $args['name'] ?? $args['database'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredPayAPI("database-api?action=$action", 'POST', [
        'client_id' => $clientId, 'name' => $dbName
    ], $clientId);
    $result['message'] = $result['message'] ?? "Databases ($action) processed.";
    return $result;
}

function toolManageFTP($args) {
    $clientId = $args['clientId'] ?? null;
    $action   = $args['action'] ?? 'list'; // list, create, delete
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredPayAPI("ftp-api?action=$action", 'POST', [
        'client_id' => $clientId, 'username' => $args['username'] ?? null,
        'password' => $args['password'] ?? null, 'domain' => $args['domain'] ?? null
    ], $clientId);
    $result['message'] = $result['message'] ?? "FTP accounts ($action) processed.";
    return $result;
}

function toolManageSubdomains($args) {
    $clientId = $args['clientId'] ?? null;
    $action   = $args['action'] ?? 'list'; // list, create, delete
    $subdomain = $args['subdomain'] ?? null;
    $domain   = $args['domain'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredPayAPI("subdomain-api?action=$action", 'POST', [
        'client_id' => $clientId, 'subdomain' => $subdomain, 'domain' => $domain
    ], $clientId);
    $result['message'] = $result['message'] ?? "Subdomains ($action) processed.";
    return $result;
}

function toolManageCronJobs($args) {
    $clientId = $args['clientId'] ?? null;
    $action   = $args['action'] ?? 'list'; // list, create, delete
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredPayAPI("cron-api?action=$action", 'POST', [
        'client_id' => $clientId, 'command' => $args['command'] ?? null,
        'schedule' => $args['schedule'] ?? null
    ], $clientId);
    $result['message'] = $result['message'] ?? "Cron jobs ($action) processed.";
    return $result;
}

function toolManageRedirects($args) {
    $clientId = $args['clientId'] ?? null;
    $action   = $args['action'] ?? 'list'; // list, create, delete
    $from     = $args['from'] ?? null;
    $to       = $args['to'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredPayAPI("redirects-api?action=$action", 'POST', [
        'client_id' => $clientId, 'from' => $from, 'to' => $to,
        'type' => $args['type'] ?? '301'
    ], $clientId);
    $result['message'] = $result['message'] ?? "Redirects ($action) processed.";
    return $result;
}

function toolManageBackups($args) {
    $clientId = $args['clientId'] ?? null;
    $action   = $args['action'] ?? 'list'; // list, create, restore, download
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredPayAPI("backup-api?action=$action", 'POST', [
        'client_id' => $clientId, 'domain' => $args['domain'] ?? null
    ], $clientId);
    $result['message'] = $result['message'] ?? "Backups ($action) processed.";
    return $result;
}

function toolInstallApp($args) {
    $clientId = $args['clientId'] ?? null;
    $app      = $args['app'] ?? $args['application'] ?? null;
    $domain   = $args['domain'] ?? null;
    $action   = $args['action'] ?? 'list'; // list, install
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredPayAPI("apps-api?action=$action", 'POST', [
        'client_id' => $clientId, 'app' => $app, 'domain' => $domain,
        'path' => $args['path'] ?? '/'
    ], $clientId);
    $result['message'] = $result['message'] ?? ($action === 'install' ? "App '$app' installed on $domain." : 'Available apps listed.');
    return $result;
}

function toolManageDomainPointers($args) {
    $clientId = $args['clientId'] ?? null;
    $action   = $args['action'] ?? 'list'; // list, create, delete
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredPayAPI("domain-pointers-api?action=$action", 'POST', [
        'client_id' => $clientId, 'source' => $args['source'] ?? null,
        'destination' => $args['destination'] ?? null
    ], $clientId);
    $result['message'] = $result['message'] ?? "Domain pointers ($action) processed.";
    return $result;
}

function toolManageEmail($args) {
    $clientId = $args['clientId'] ?? null;
    $action   = $args['action'] ?? 'list'; // list, create, delete, forwarders
    $domain   = $args['domain'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredPayAPI("email-api?action=$action", 'POST', [
        'client_id' => $clientId, 'domain' => $domain,
        'email' => $args['email'] ?? null, 'password' => $args['password'] ?? null,
        'quota' => $args['quota'] ?? null
    ], $clientId);
    $result['message'] = $result['message'] ?? "Email accounts ($action) processed.";
    return $result;
}

function toolManageSSL($args) {
    $clientId = $args['clientId'] ?? null;
    $action   = $args['action'] ?? 'status'; // status, install, renew
    $domain   = $args['domain'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredPayAPI("ssl-api?action=$action", 'POST', [
        'client_id' => $clientId, 'domain' => $domain
    ], $clientId);
    $result['message'] = $result['message'] ?? "SSL ($action) processed for $domain.";
    return $result;
}

function toolManageDNS($args) {
    $clientId = $args['clientId'] ?? null;
    $action   = $args['action'] ?? 'list'; // list, add, delete
    $domain   = $args['domain'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredPayAPI("dns-api?action=$action", 'POST', [
        'client_id' => $clientId, 'domain' => $domain,
        'type' => $args['type'] ?? null, 'name' => $args['name'] ?? null,
        'value' => $args['value'] ?? null
    ], $clientId);
    $result['message'] = $result['message'] ?? "DNS records ($action) processed for $domain.";
    return $result;
}

function toolManageFiles($args) {
    $clientId = $args['clientId'] ?? null;
    $action   = $args['action'] ?? 'list'; // list, upload, delete, edit
    $path     = $args['path'] ?? '/';
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredPayAPI("file-api?action=$action", 'POST', [
        'client_id' => $clientId, 'path' => $path,
        'content' => $args['content'] ?? null
    ], $clientId);
    $result['message'] = $result['message'] ?? "Files ($action) processed.";
    return $result;
}

function toolHostingStats($args) {
    $clientId = $args['clientId'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredPayAPI("stats-api?action=overview", 'POST', [], $clientId);
    $result['message'] = $result['message'] ?? 'Hosting statistics loaded.';
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// SITE DOCTOR
// ═══════════════════════════════════════════════════════════════════════════

function toolRunSiteDoctor($args) {
    $clientId = $args['clientId'] ?? null;
    $domain   = $args['domain'] ?? null;
    if (!$clientId || !$domain) return ['error' => 'clientId and domain required'];

    if (!defined('SITE_DOCTOR_FUNCTIONS_ONLY')) define('SITE_DOCTOR_FUNCTIONS_ONLY', true);
    require_once __DIR__ . '/../pay/api/site-doctor.php';

    $db = billingDB();
    ensureDoctorTables($db);
    $results = performFullScan($domain);
    $score = calculateDoctorScore($results);
    saveScan($db, $clientId, $domain, $results, $score);

    $grade = $score >= 90 ? 'A' : ($score >= 80 ? 'B' : ($score >= 70 ? 'C' : ($score >= 60 ? 'D' : 'F')));
    return ['success' => true, 'domain' => $domain, 'score' => $score, 'grade' => $grade,
            'results' => $results,
            'message' => "Site Doctor scan for $domain complete. Score: $score/100 (Grade $grade). " .
                         count(array_filter($results, fn($r) => ($r['status'] ?? '') === 'fail')) . " issues found."];
}

function toolSiteDoctorHistory($args) {
    $clientId = $args['clientId'] ?? null;
    $domain   = $args['domain'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    if (!defined('SITE_DOCTOR_FUNCTIONS_ONLY')) define('SITE_DOCTOR_FUNCTIONS_ONLY', true);
    require_once __DIR__ . '/../pay/api/site-doctor.php';

    $db = billingDB();
    ensureDoctorTables($db);
    $history = getHistory($db, $clientId, $domain);
    return ['success' => true, 'history' => $history,
            'message' => count($history) . ' previous site doctor scans found.'];
}

// ═══════════════════════════════════════════════════════════════════════════
// AI IMAGE GENERATION
// ═══════════════════════════════════════════════════════════════════════════

function toolGenerateAIImage($args) {
    $clientId    = $args['clientId'] ?? null;
    $prompt      = $args['prompt'] ?? $args['description'] ?? null;
    $style       = $args['style'] ?? 'realistic';
    $aspectRatio = $args['aspectRatio'] ?? '1:1';
    if (!$clientId || !$prompt) return ['error' => 'clientId and prompt required'];

    if (!defined('IMAGE_GEN_FUNCTIONS_ONLY')) define('IMAGE_GEN_FUNCTIONS_ONLY', true);
    require_once __DIR__ . '/../pay/api/image-generator.php';

    $db = billingDB();
    ensureImageGenTables($db);

    $imageUrl = generateImage($db, $clientId, $prompt, $style, $aspectRatio);
    if (!$imageUrl) return ['error' => 'Image generation failed. Please try again.'];

    return ['success' => true, 'image_url' => $imageUrl, 'prompt' => $prompt,
            'message' => "Image generated! You can view it at: $imageUrl"];
}

// ═══════════════════════════════════════════════════════════════════════════
// AI SUPPORT CHAT
// ═══════════════════════════════════════════════════════════════════════════

function toolAISupportChat($args) {
    $clientId = $args['clientId'] ?? null;
    $message  = $args['message'] ?? $args['question'] ?? null;
    if (!$message) return ['error' => 'message required'];

    $result = alfredPayAPI('support-chat', 'POST', [
        'action' => 'chat', 'client_id' => $clientId, 'message' => $message
    ], $clientId);
    $result['message'] = $result['message'] ?? $result['response'] ?? 'Support response generated.';
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// AGENT TRACKER
// ═══════════════════════════════════════════════════════════════════════════

function toolAgentTrackerDashboard($args) {
    $result = alfredMainAPI('agent-tracker', 'POST', ['action' => 'dashboard']);
    $result['message'] = $result['message'] ?? 'Agent tracker dashboard loaded.';
    return $result;
}

function toolAgentTrackerSearch($args) {
    $query = $args['query'] ?? $args['search'] ?? null;
    if (!$query) return ['error' => 'search query required'];

    $result = alfredMainAPI('agent-tracker', 'POST', [
        'action' => 'search', 'query' => $query
    ]);
    $result['message'] = $result['message'] ?? "Agent search results for: $query";
    return $result;
}

function toolAgentTrackerDeploy($args) {
    $agentId = $args['agentId'] ?? $args['agent_id'] ?? null;
    $target  = $args['target'] ?? 'production';
    if (!$agentId) return ['error' => 'agentId required'];

    $result = alfredMainAPI('agent-tracker', 'POST', [
        'action' => 'deploy', 'agent_id' => $agentId, 'target' => $target
    ]);
    $result['message'] = $result['message'] ?? "Agent $agentId deployed to $target.";
    return $result;
}

function toolAgentTrackerReport($args) {
    $result = alfredMainAPI('agent-tracker', 'POST', ['action' => 'report']);
    $result['message'] = $result['message'] ?? 'Agent fleet report generated.';
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// AUTOPILOT MANAGEMENT
// ═══════════════════════════════════════════════════════════════════════════

function toolToggleAutopilot($args) {
    $clientId = $args['clientId'] ?? null;
    $domain   = $args['domain'] ?? null;
    $enabled  = $args['enabled'] ?? null;
    if (!$clientId || !$domain) return ['error' => 'clientId and domain required'];

    if (!defined('AUTOPILOT_FUNCTIONS_ONLY')) define('AUTOPILOT_FUNCTIONS_ONLY', true);
    require_once __DIR__ . '/../pay/api/autopilot.php';

    $db = billingDB();
    ensureAutopilotTables($db);
    $result = handleToggle($db, $clientId, $domain, $enabled);
    $state = $enabled ? 'enabled' : 'disabled';
    $result['message'] = $result['message'] ?? "Autopilot $state for $domain.";
    return $result;
}

function toolAutopilotReport($args) {
    $clientId = $args['clientId'] ?? null;
    $domain   = $args['domain'] ?? null;
    if (!$clientId || !$domain) return ['error' => 'clientId and domain required'];

    if (!defined('AUTOPILOT_FUNCTIONS_ONLY')) define('AUTOPILOT_FUNCTIONS_ONLY', true);
    require_once __DIR__ . '/../pay/api/autopilot.php';

    $db = billingDB();
    ensureAutopilotTables($db);
    $result = handleReport($db, $clientId, $domain);
    $result['message'] = $result['message'] ?? "Autopilot report for $domain generated.";
    return $result;
}

function toolAutopilotHistory($args) {
    $clientId = $args['clientId'] ?? null;
    $domain   = $args['domain'] ?? null;
    if (!$clientId || !$domain) return ['error' => 'clientId and domain required'];

    if (!defined('AUTOPILOT_FUNCTIONS_ONLY')) define('AUTOPILOT_FUNCTIONS_ONLY', true);
    require_once __DIR__ . '/../pay/api/autopilot.php';

    $db = billingDB();
    ensureAutopilotTables($db);
    $result = handleHistory($db, $clientId, $domain);
    $result['message'] = $result['message'] ?? "Autopilot history for $domain loaded.";
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// ENTERPRISE BILLING
// ═══════════════════════════════════════════════════════════════════════════

function toolEnterpriseBilling($args) {
    $clientId = $args['clientId'] ?? null;
    $action   = $args['action'] ?? 'dashboard'; // dashboard, usage, invoices
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredPayAPI('enterprise-billing', 'POST', [
        'action' => $action, 'client_id' => $clientId
    ], $clientId);
    $result['message'] = $result['message'] ?? "Enterprise billing ($action) loaded.";
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// GAMIFICATION EXPANDED
// ═══════════════════════════════════════════════════════════════════════════

function toolGamificationProfile($args) {
    $clientId = $args['clientId'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredMainAPI('gamification', 'POST', [
        'action' => 'profile', 'client_id' => $clientId
    ]);
    $result['message'] = $result['message'] ?? 'Gamification profile loaded.';
    return $result;
}

function toolGamificationAwardXP($args) {
    $clientId = $args['clientId'] ?? null;
    $amount   = $args['amount'] ?? $args['xp'] ?? 0;
    $reason   = $args['reason'] ?? 'manual_award';
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredMainAPI('gamification', 'POST', [
        'action' => 'award_xp', 'client_id' => $clientId,
        'amount' => $amount, 'reason' => $reason
    ]);
    $result['message'] = $result['message'] ?? "$amount XP awarded.";
    return $result;
}

function toolDailyChallenge($args) {
    $clientId = $args['clientId'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredMainAPI('gamification', 'POST', [
        'action' => 'daily_challenge', 'client_id' => $clientId
    ]);
    $result['message'] = $result['message'] ?? "Here's your daily challenge!";
    return $result;
}

function toolGamificationLeaderboard($args) {
    $period = $args['period'] ?? 'weekly';
    $result = alfredMainAPI('gamification', 'POST', [
        'action' => 'leaderboard', 'period' => $period
    ]);
    $result['message'] = $result['message'] ?? "$period leaderboard loaded.";
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// CART MANAGEMENT
// ═══════════════════════════════════════════════════════════════════════════

function toolCartAdd($args) {
    $clientId  = $args['clientId'] ?? null;
    $productId = $args['productId'] ?? $args['product_id'] ?? null;
    $cycle     = $args['cycle'] ?? $args['billing_cycle'] ?? 'monthly';
    if (!$clientId || !$productId) return ['error' => 'clientId and productId required'];

    $db = billingDB();
    require_once __DIR__ . '/../pay/includes/billing-functions.php';
    $result = addToCart($db, $clientId, $productId, $cycle);
    return ['success' => true, 'message' => "Product added to your cart. $result"];
}

function toolCartView($args) {
    $clientId = $args['clientId'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $db = billingDB();
    require_once __DIR__ . '/../pay/includes/billing-functions.php';
    $cart = getCart($db, $clientId);
    $total = getCartTotal($db, $clientId);
    $count = getCartCount($db, $clientId);

    return ['success' => true, 'items' => $cart, 'total' => $total, 'count' => $count,
            'message' => $count > 0 ? "You have $count items in your cart. Total: \$$total." : 'Your cart is empty.'];
}

function toolCartRemove($args) {
    $clientId = $args['clientId'] ?? null;
    $itemId   = $args['itemId'] ?? $args['item_id'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $db = billingDB();
    require_once __DIR__ . '/../pay/includes/billing-functions.php';

    if ($itemId) {
        removeFromCart($db, $clientId, $itemId);
        return ['success' => true, 'message' => 'Item removed from cart.'];
    } else {
        clearCart($db, $clientId);
        return ['success' => true, 'message' => 'Cart cleared.'];
    }
}

function toolApplyPromo($args) {
    $clientId = $args['clientId'] ?? null;
    $code     = $args['code'] ?? $args['promoCode'] ?? null;
    if (!$clientId || !$code) return ['error' => 'clientId and promo code required'];

    $db = billingDB();
    require_once __DIR__ . '/../pay/includes/billing-functions.php';
    $valid = validatePromoCode($db, $code);
    if (!$valid) return ['error' => 'Invalid or expired promo code.'];

    applyPromoCode($db, $clientId, $code);
    return ['success' => true, 'message' => "Promo code '$code' applied successfully!"];
}

// ═══════════════════════════════════════════════════════════════════════════
// SERVICE MANAGEMENT
// ═══════════════════════════════════════════════════════════════════════════

function toolServiceList($args) {
    $clientId = $args['clientId'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $db = billingDB();
    $stmt = $db->prepare("SELECT s.id, s.domain, s.status, s.billing_cycle, s.amount, s.next_due_date,
                           p.name as product_name FROM services s
                           LEFT JOIN products p ON s.product_id = p.id
                           WHERE s.client_id = ? ORDER BY s.status, s.next_due_date");
    $stmt->execute([$clientId]);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $active = count(array_filter($services, fn($s) => $s['status'] === 'active'));
    return ['success' => true, 'services' => $services,
            'message' => count($services) . " services found ($active active)."];
}

function toolServiceDetail($args) {
    $clientId  = $args['clientId'] ?? null;
    $serviceId = $args['serviceId'] ?? $args['service_id'] ?? null;
    $domain    = $args['domain'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $db = billingDB();
    if ($serviceId) {
        $stmt = $db->prepare("SELECT s.*, p.name as product_name FROM services s LEFT JOIN products p ON s.product_id = p.id WHERE s.id = ? AND s.client_id = ?");
        $stmt->execute([$serviceId, $clientId]);
    } else if ($domain) {
        $stmt = $db->prepare("SELECT s.*, p.name as product_name FROM services s LEFT JOIN products p ON s.product_id = p.id WHERE s.domain = ? AND s.client_id = ?");
        $stmt->execute([$domain, $clientId]);
    } else {
        return ['error' => 'serviceId or domain required'];
    }

    $service = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$service) return ['error' => 'Service not found'];

    return ['success' => true, 'service' => $service,
            'message' => "Service: {$service['product_name']} for {$service['domain']}. Status: {$service['status']}. " .
                         "Next due: {$service['next_due_date']}. Amount: \${$service['amount']}/{$service['billing_cycle']}."];
}

// ═══════════════════════════════════════════════════════════════════════════
// TICKET MANAGEMENT (EXPANDED)
// ═══════════════════════════════════════════════════════════════════════════

function toolListTickets($args) {
    $clientId = $args['clientId'] ?? null;
    $status   = $args['status'] ?? 'all';
    if (!$clientId) return ['error' => 'clientId required'];

    $db = billingDB();
    $sql = "SELECT id, ticket_id, subject, department, status, priority, created_at, updated_at FROM tickets WHERE client_id = ?";
    $params = [$clientId];
    if ($status !== 'all') {
        $sql .= " AND status = ?";
        $params[] = $status;
    }
    $sql .= " ORDER BY updated_at DESC LIMIT 20";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $open = count(array_filter($tickets, fn($t) => in_array($t['status'], ['open', 'in_progress'])));
    return ['success' => true, 'tickets' => $tickets,
            'message' => count($tickets) . " tickets found ($open open)."];
}

function toolReplyTicket($args) {
    $clientId = $args['clientId'] ?? null;
    $ticketId = $args['ticketId'] ?? $args['ticket_id'] ?? null;
    $message  = $args['message'] ?? $args['reply'] ?? null;
    if (!$clientId || !$ticketId || !$message) return ['error' => 'clientId, ticketId, and message required'];

    $db = billingDB();
    require_once __DIR__ . '/../pay/includes/billing-functions.php';
    replyToTicket($db, $ticketId, $clientId, $message);
    return ['success' => true, 'message' => "Reply added to ticket $ticketId."];
}

// ═══════════════════════════════════════════════════════════════════════════
// KNOWLEDGE BASE
// ═══════════════════════════════════════════════════════════════════════════

function toolSearchKnowledgeBase($args) {
    $query = $args['query'] ?? $args['search'] ?? null;
    if (!$query) return ['error' => 'search query required'];

    $db = billingDB();
    require_once __DIR__ . '/../pay/includes/billing-functions.php';
    $results = searchKB($db, $query);
    return ['success' => true, 'results' => $results,
            'message' => count($results) . " knowledge base articles found for: $query"];
}

function toolGetKBArticle($args) {
    $articleId = $args['articleId'] ?? $args['id'] ?? null;
    if (!$articleId) return ['error' => 'articleId required'];

    $db = billingDB();
    require_once __DIR__ . '/../pay/includes/billing-functions.php';
    $article = getKBArticle($db, $articleId);
    if (!$article) return ['error' => 'Article not found'];

    return ['success' => true, 'article' => $article,
            'message' => "Article: {$article['title']}"];
}

// ═══════════════════════════════════════════════════════════════════════════
// UPTIME MONITORING (EXPANDED)
// ═══════════════════════════════════════════════════════════════════════════

function toolUptimeOverview($args) {
    $clientId = $args['clientId'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    if (!defined('UPTIME_FUNCTIONS_ONLY')) define('UPTIME_FUNCTIONS_ONLY', true);
    require_once __DIR__ . '/../pay/api/uptime.php';

    $db = billingDB();
    ensureUptimeTables($db);
    $result = getOverview($db, $clientId);
    $result['message'] = $result['message'] ?? 'Uptime monitoring overview loaded.';
    return $result;
}

function toolUptimeToggle($args) {
    $clientId  = $args['clientId'] ?? null;
    $monitorId = $args['monitorId'] ?? $args['monitor_id'] ?? null;
    $enabled   = $args['enabled'] ?? true;
    if (!$clientId || !$monitorId) return ['error' => 'clientId and monitorId required'];

    if (!defined('UPTIME_FUNCTIONS_ONLY')) define('UPTIME_FUNCTIONS_ONLY', true);
    require_once __DIR__ . '/../pay/api/uptime.php';

    $db = billingDB();
    ensureUptimeTables($db);
    $result = toggleMonitor($db, $clientId, $monitorId, $enabled);
    $state = $enabled ? 'enabled' : 'paused';
    $result['message'] = $result['message'] ?? "Uptime monitor $state.";
    return $result;
}

function toolUptimeIncidents($args) {
    $clientId = $args['clientId'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    if (!defined('UPTIME_FUNCTIONS_ONLY')) define('UPTIME_FUNCTIONS_ONLY', true);
    require_once __DIR__ . '/../pay/api/uptime.php';

    $db = billingDB();
    ensureUptimeTables($db);
    $result = getIncidents($db, $clientId);
    $result['message'] = $result['message'] ?? 'Uptime incidents listed.';
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// WEBHOOK MANAGEMENT (EXPANDED)
// ═══════════════════════════════════════════════════════════════════════════

function toolWebhookSubscribe($args) {
    $clientId = $args['clientId'] ?? null;
    $url      = $args['url'] ?? null;
    $events   = $args['events'] ?? ['*'];
    if (!$clientId || !$url) return ['error' => 'clientId and url required'];

    if (!defined('WEBHOOKS_FUNCTIONS_ONLY')) define('WEBHOOKS_FUNCTIONS_ONLY', true);
    require_once __DIR__ . '/../pay/api/webhooks.php';

    $db = billingDB();
    ensureWebhookTables($db);
    $result = handleSubscribe($db, $clientId, $url, $events);
    $result['message'] = $result['message'] ?? "Webhook subscription created for $url.";
    return $result;
}

function toolWebhookEvents($args) {
    if (!defined('WEBHOOKS_FUNCTIONS_ONLY')) define('WEBHOOKS_FUNCTIONS_ONLY', true);
    require_once __DIR__ . '/../pay/api/webhooks.php';

    $events = getWebhookEvents();
    return ['success' => true, 'events' => $events,
            'message' => count($events) . ' webhook event types available.'];
}

// ═══════════════════════════════════════════════════════════════════════════
// ANALYTICS (EXPANDED)
// ═══════════════════════════════════════════════════════════════════════════

function toolAnalyticsDashboard($args) {
    $clientId = $args['clientId'] ?? null;
    $domain   = $args['domain'] ?? null;
    $period   = $args['period'] ?? '30d';
    if (!$clientId) return ['error' => 'clientId required'];

    if (!defined('ANALYTICS_FUNCTIONS_ONLY')) define('ANALYTICS_FUNCTIONS_ONLY', true);
    require_once __DIR__ . '/../pay/api/analytics.php';

    $db = billingDB();
    ensureAnalyticsTables($db);
    return alfredPayAPI("analytics?action=dashboard&period=$period", 'POST',
        ['client_id' => $clientId, 'domain' => $domain], $clientId);
}

// ═══════════════════════════════════════════════════════════════════════════
// MARKETPLACE (EXPANDED)
// ═══════════════════════════════════════════════════════════════════════════

function toolMarketplaceBrowseExpanded($args) {
    $category = $args['category'] ?? 'all';
    $query    = $args['query'] ?? null;

    if (!defined('MARKETPLACE_FUNCTIONS_ONLY')) define('MARKETPLACE_FUNCTIONS_ONLY', true);
    require_once __DIR__ . '/../pay/api/marketplace.php';

    $db = billingDB();
    ensureMarketplaceTables($db);
    if ($query) {
        $results = search($db, $query);
    } else {
        $results = browse($db, $category);
    }
    return ['success' => true, 'agents' => $results,
            'message' => count($results) . ' marketplace items found.'];
}

function toolMarketplaceInstallExpand($args) {
    $clientId = $args['clientId'] ?? null;
    $agentId  = $args['agentId'] ?? $args['agent_id'] ?? null;
    if (!$clientId || !$agentId) return ['error' => 'clientId and agentId required'];

    if (!defined('MARKETPLACE_FUNCTIONS_ONLY')) define('MARKETPLACE_FUNCTIONS_ONLY', true);
    require_once __DIR__ . '/../pay/api/marketplace.php';

    $db = billingDB();
    ensureMarketplaceTables($db);
    $result = installAgent($db, $clientId, $agentId);
    $result['message'] = $result['message'] ?? "Agent installed from marketplace.";
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// CONSCIOUSNESS LAYER (EXPANDED)
// ═══════════════════════════════════════════════════════════════════════════

function toolConsciousnessGreeting($args) {
    $clientId = $args['clientId'] ?? null;
    $result = alfredMainAPI('consciousness', 'POST', [
        'action' => 'greeting', 'client_id' => $clientId
    ]);
    $result['message'] = $result['message'] ?? 'Consciousness greeting generated.';
    return $result;
}

function toolConsciousnessRapport($args) {
    $clientId = $args['clientId'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredMainAPI('consciousness', 'POST', [
        'action' => 'rapport', 'client_id' => $clientId
    ]);
    $result['message'] = $result['message'] ?? 'Rapport summary generated.';
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// GOALS MANAGEMENT
// ═══════════════════════════════════════════════════════════════════════════

function toolGoalsCreate($args) {
    $clientId = $args['clientId'] ?? null;
    $title    = $args['title'] ?? $args['goal'] ?? null;
    $category = $args['category'] ?? 'general';
    if (!$clientId || !$title) return ['error' => 'clientId and title required'];

    $result = alfredMainAPI('goals', 'POST', [
        'action' => 'create', 'client_id' => $clientId,
        'title' => $title, 'category' => $category,
        'deadline' => $args['deadline'] ?? null
    ]);
    $result['message'] = $result['message'] ?? "Goal '$title' created.";
    return $result;
}

function toolGoalsList($args) {
    $clientId = $args['clientId'] ?? null;
    $status   = $args['status'] ?? 'all';
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredMainAPI('goals', 'POST', [
        'action' => 'list', 'client_id' => $clientId, 'status' => $status
    ]);
    $result['message'] = $result['message'] ?? 'Goals listed.';
    return $result;
}

function toolGoalsUpdate($args) {
    $clientId = $args['clientId'] ?? null;
    $goalId   = $args['goalId'] ?? $args['goal_id'] ?? null;
    $status   = $args['status'] ?? null;
    if (!$clientId || !$goalId) return ['error' => 'clientId and goalId required'];

    $result = alfredMainAPI('goals', 'POST', [
        'action' => 'update', 'client_id' => $clientId,
        'goal_id' => $goalId, 'status' => $status,
        'progress' => $args['progress'] ?? null
    ]);
    $result['message'] = $result['message'] ?? "Goal $goalId updated.";
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// ONBOARDING
// ═══════════════════════════════════════════════════════════════════════════

function toolOnboardingStatus($args) {
    $clientId = $args['clientId'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredPayAPI('onboarding-api?action=status', 'POST', [
        'client_id' => $clientId
    ], $clientId);
    $result['message'] = $result['message'] ?? 'Onboarding status retrieved.';
    return $result;
}

function toolOnboardingComplete($args) {
    $clientId = $args['clientId'] ?? null;
    $step     = $args['step'] ?? null;
    if (!$clientId || !$step) return ['error' => 'clientId and step required'];

    $result = alfredPayAPI('onboarding-api?action=complete_step', 'POST', [
        'client_id' => $clientId, 'step' => $step
    ], $clientId);
    $result['message'] = $result['message'] ?? "Onboarding step '$step' completed.";
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// PROVISIONING
// ═══════════════════════════════════════════════════════════════════════════

function toolProvisionService($args) {
    $clientId  = $args['clientId'] ?? null;
    $serviceId = $args['serviceId'] ?? $args['service_id'] ?? null;
    if (!$clientId || !$serviceId) return ['error' => 'clientId and serviceId required'];

    $result = alfredPayAPI('provision?action=provision', 'POST', [
        'client_id' => $clientId, 'service_id' => $serviceId
    ], $clientId);
    $result['message'] = $result['message'] ?? "Service $serviceId provisioning initiated.";
    return $result;
}

function toolProvisionStatus($args) {
    $clientId  = $args['clientId'] ?? null;
    $serviceId = $args['serviceId'] ?? $args['service_id'] ?? null;
    if (!$clientId || !$serviceId) return ['error' => 'clientId and serviceId required'];

    $result = alfredPayAPI('provision?action=status', 'POST', [
        'client_id' => $clientId, 'service_id' => $serviceId
    ], $clientId);
    $result['message'] = $result['message'] ?? 'Provisioning status retrieved.';
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// DELEGATION (EXPANDED)
// ═══════════════════════════════════════════════════════════════════════════

function toolDelegationStatus($args) {
    $taskId = $args['taskId'] ?? $args['task_id'] ?? null;
    if (!$taskId) return ['error' => 'taskId required'];

    $result = alfredMainAPI('delegation', 'POST', [
        'action' => 'status', 'task_id' => $taskId
    ]);
    $result['message'] = $result['message'] ?? "Delegation task $taskId status retrieved.";
    return $result;
}

function toolDelegationHistory($args) {
    $clientId = $args['clientId'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredMainAPI('delegation', 'POST', [
        'action' => 'history', 'client_id' => $clientId
    ]);
    $result['message'] = $result['message'] ?? 'Delegation history loaded.';
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// REVENUE AGENTS
// ═══════════════════════════════════════════════════════════════════════════

function toolRevenueAgentReport($args) {
    $division = $args['division'] ?? 'all';
    $result = alfredMainAPI('revenue-agents', 'POST', [
        'action' => 'report', 'division' => $division
    ]);
    $result['message'] = $result['message'] ?? 'Revenue agent report generated.';
    return $result;
}

function toolRevenueRecommendations($args) {
    $clientId = $args['clientId'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredMainAPI('revenue-agents', 'POST', [
        'action' => 'recommendations', 'client_id' => $clientId
    ]);
    $result['message'] = $result['message'] ?? 'Revenue recommendations generated.';
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// SANCTUARY (BIBLE STUDY)
// ═══════════════════════════════════════════════════════════════════════════

function toolSanctuaryVerse($args) {
    $reference = $args['reference'] ?? $args['verse'] ?? null;
    $version   = $args['version'] ?? 'KJV';

    $result = alfredMainAPI('sanctuary', 'POST', [
        'action' => 'verse', 'reference' => $reference, 'version' => $version
    ]);
    $result['message'] = $result['message'] ?? "Scripture passage retrieved.";
    return $result;
}

function toolSanctuaryStudy($args) {
    $topic = $args['topic'] ?? $args['query'] ?? null;
    if (!$topic) return ['error' => 'topic required'];

    $prompt = "Provide a thoughtful, balanced Bible study on the topic of: $topic. " .
              "Include relevant scripture references (with book, chapter, and verse). " .
              "Keep it concise but meaningful.";
    $response = callAlfred($prompt, ['max_tokens' => 600]);
    return ['success' => true, 'topic' => $topic, 'study' => $response, 'message' => $response];
}

// ═══════════════════════════════════════════════════════════════════════════
// LEARNING MANAGEMENT
// ═══════════════════════════════════════════════════════════════════════════

function toolLearningCourse($args) {
    $clientId = $args['clientId'] ?? null;
    $action   = $args['action'] ?? 'list'; // list, enroll, progress
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredMainAPI('learning', 'POST', [
        'action' => $action, 'client_id' => $clientId,
        'course_id' => $args['courseId'] ?? null
    ]);
    $result['message'] = $result['message'] ?? "Learning ($action) processed.";
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// ORCHESTRATOR
// ═══════════════════════════════════════════════════════════════════════════

function toolOrchestratorExecute($args) {
    $preset  = $args['preset'] ?? $args['workflow'] ?? null;
    $clientId = $args['clientId'] ?? null;
    if (!$preset) return ['error' => 'preset/workflow name required'];

    $result = alfredMainAPI('orchestrator', 'POST', [
        'action' => 'execute', 'preset' => $preset, 'client_id' => $clientId,
        'params' => $args['params'] ?? []
    ]);
    $result['message'] = $result['message'] ?? "Orchestrator workflow '$preset' executed.";
    return $result;
}

function toolOrchestratorPresets($args) {
    $result = alfredMainAPI('orchestrator', 'POST', ['action' => 'presets']);
    $result['message'] = $result['message'] ?? 'Available orchestrator presets listed.';
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// CONTINGENCY PLANS
// ═══════════════════════════════════════════════════════════════════════════

function toolContingencyPlan($args) {
    $scenario = $args['scenario'] ?? $args['type'] ?? 'general';
    $result = alfredMainAPI('contingency', 'POST', [
        'action' => 'plan', 'scenario' => $scenario
    ]);
    $result['message'] = $result['message'] ?? "Contingency plan for '$scenario' retrieved.";
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// PRO DISCUSSIONS
// ═══════════════════════════════════════════════════════════════════════════

function toolProDiscussion($args) {
    $topic    = $args['topic'] ?? $args['question'] ?? null;
    $clientId = $args['clientId'] ?? null;
    if (!$topic) return ['error' => 'topic required'];

    $result = alfredMainAPI('pro-discussions', 'POST', [
        'action' => 'discuss', 'topic' => $topic, 'client_id' => $clientId
    ]);
    $result['message'] = $result['message'] ?? 'Professional discussion response generated.';
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// METAVERSE PRESENCE
// ═══════════════════════════════════════════════════════════════════════════

function toolMetaverseStatus($args) {
    $result = alfredMainAPI('metaverse-presence', 'POST', ['action' => 'status']);
    $result['message'] = $result['message'] ?? 'Metaverse presence status loaded.';
    return $result;
}

function toolMetaverseZones($args) {
    $result = alfredMainAPI('metaverse-presence', 'POST', ['action' => 'zones']);
    $result['message'] = $result['message'] ?? 'Metaverse zones listed.';
    return $result;
}

function toolMetaverseMeeting($args) {
    $clientId = $args['clientId'] ?? null;
    $zone     = $args['zone'] ?? 'lobby';
    $title    = $args['title'] ?? 'Meeting';
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredMainAPI('metaverse-presence', 'POST', [
        'action' => 'schedule_meeting', 'client_id' => $clientId,
        'zone' => $zone, 'title' => $title
    ]);
    $result['message'] = $result['message'] ?? "Metaverse meeting '$title' scheduled in $zone.";
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// SSO
// ═══════════════════════════════════════════════════════════════════════════

function toolSSOStatus($args) {
    $clientId = $args['clientId'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredPayAPI('sso?action=status', 'POST', [
        'client_id' => $clientId
    ], $clientId);
    $result['message'] = $result['message'] ?? 'SSO status retrieved.';
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// DEPLOY
// ═══════════════════════════════════════════════════════════════════════════

function toolDeployStatus($args) {
    $clientId = $args['clientId'] ?? null;
    $domain   = $args['domain'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredPayAPI('deploy?action=status', 'POST', [
        'client_id' => $clientId, 'domain' => $domain
    ], $clientId);
    $result['message'] = $result['message'] ?? 'Deployment status retrieved.';
    return $result;
}

function toolDeployTrigger($args) {
    $clientId = $args['clientId'] ?? null;
    $domain   = $args['domain'] ?? null;
    $env      = $args['environment'] ?? 'production';
    if (!$clientId || !$domain) return ['error' => 'clientId and domain required'];

    $result = alfredPayAPI('deploy?action=deploy', 'POST', [
        'client_id' => $clientId, 'domain' => $domain, 'environment' => $env
    ], $clientId);
    $result['message'] = $result['message'] ?? "Deployment triggered for $domain to $env.";
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// VEIL PROTOCOL REPORTS
// ═══════════════════════════════════════════════════════════════════════════

function toolVeilMorningBriefing($args) {
    $result = alfredMainAPI('veil-reports', 'POST', ['action' => 'morning_briefing']);
    $result['message'] = $result['message'] ?? 'Morning briefing generated.';
    return $result;
}

function toolVeilServiceHealth($args) {
    $result = alfredMainAPI('veil-reports', 'POST', ['action' => 'service_health']);
    $result['message'] = $result['message'] ?? 'Service health report generated.';
    return $result;
}

function toolVeilAgentPerformance($args) {
    $result = alfredMainAPI('veil-reports', 'POST', ['action' => 'agent_performance']);
    $result['message'] = $result['message'] ?? 'Agent performance report generated.';
    return $result;
}

function toolVeilSecurityReport($args) {
    $result = alfredMainAPI('veil-reports', 'POST', ['action' => 'security_report']);
    $result['message'] = $result['message'] ?? 'Security report generated.';
    return $result;
}

function toolVeilEcosystemGaps($args) {
    $result = alfredMainAPI('veil-reports', 'POST', ['action' => 'ecosystem_gaps']);
    $result['message'] = $result['message'] ?? 'Ecosystem gaps report generated.';
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// CONVERSATIONS MANAGEMENT
// ═══════════════════════════════════════════════════════════════════════════

function toolConversationsList($args) {
    $clientId = $args['clientId'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredMainAPI('conversations', 'POST', [
        'action' => 'list', 'client_id' => $clientId
    ]);
    $result['message'] = $result['message'] ?? 'Conversations listed.';
    return $result;
}

function toolConversationStats($args) {
    $clientId = $args['clientId'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredMainAPI('conversations', 'POST', [
        'action' => 'stats', 'client_id' => $clientId
    ]);
    $result['message'] = $result['message'] ?? 'Conversation statistics loaded.';
    return $result;
}

function toolConversationExport($args) {
    $clientId       = $args['clientId'] ?? null;
    $conversationId = $args['conversationId'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredMainAPI('conversations', 'POST', [
        'action' => 'export', 'client_id' => $clientId,
        'conversation_id' => $conversationId
    ]);
    $result['message'] = $result['message'] ?? 'Conversation exported.';
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// CREATIVE TOOLS (AI Image/Video/Music)
// ═══════════════════════════════════════════════════════════════════════════

function toolCreativeGenerate($args) {
    $clientId = $args['clientId'] ?? null;
    $type     = $args['type'] ?? 'image'; // image, video, music, tts
    $prompt   = $args['prompt'] ?? null;
    if (!$clientId || !$prompt) return ['error' => 'clientId and prompt required'];

    $result = alfredMainAPI('creative', 'POST', [
        'action' => 'generate', 'client_id' => $clientId,
        'type' => $type, 'prompt' => $prompt,
        'style' => $args['style'] ?? null
    ]);
    $result['message'] = $result['message'] ?? "$type generation initiated.";
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// AGENT DEPLOY (PAY)
// ═══════════════════════════════════════════════════════════════════════════

function toolAgentDeploy($args) {
    $clientId = $args['clientId'] ?? null;
    $agentId  = $args['agentId'] ?? $args['agent_id'] ?? null;
    $action   = $args['action'] ?? 'deploy'; // deploy, status, list, delete
    if (!$clientId) return ['error' => 'clientId required'];

    $result = alfredPayAPI("agent-deploy?action=$action", 'POST', [
        'client_id' => $clientId, 'agent_id' => $agentId
    ], $clientId);
    $result['message'] = $result['message'] ?? "Agent deployment ($action) processed.";
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
// VOICE GAMES (EXPANDED)
// ═══════════════════════════════════════════════════════════════════════════

function toolGameHistory($args) {
    $clientId = $args['clientId'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    if (!defined('VOICE_GAMES_FUNCTIONS_ONLY')) define('VOICE_GAMES_FUNCTIONS_ONLY', true);
    require_once __DIR__ . '/../pay/api/voice-games.php';

    $db = billingDB();
    ensureGamesTables($db);
    $result = handleHistory($db, $clientId);
    $result['message'] = $result['message'] ?? 'Game history loaded.';
    return $result;
}

function toolGameLeaderboard($args) {
    $game = $args['game'] ?? 'all';

    if (!defined('VOICE_GAMES_FUNCTIONS_ONLY')) define('VOICE_GAMES_FUNCTIONS_ONLY', true);
    require_once __DIR__ . '/../pay/api/voice-games.php';

    $db = billingDB();
    ensureGamesTables($db);
    $result = handleLeaderboard($db, $game);
    $result['message'] = $result['message'] ?? 'Game leaderboard loaded.';
    return $result;
}
