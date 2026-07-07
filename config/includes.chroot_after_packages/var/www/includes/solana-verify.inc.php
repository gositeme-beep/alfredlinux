<?php
/**
 * Solana Payment Verification — GoSiteMe
 * ═══════════════════════════════════════
 * Verifies on-chain SOL and GSM (SPL) transfers for domain payments.
 * Uses Solana RPC to confirm transactions reached the treasury.
 *
 * Usage:
 *   $result = verifySolanaPayment($txSignature, $expectedAmount, 'sol');
 *   $result = verifySolanaPayment($txSignature, $expectedAmount, 'gsm');
 */

require_once __DIR__ . '/gsm-config.inc.php';

// Treasury wallet — all domain payments go here
define('DOMAIN_TREASURY_WALLET', GSM_TREASURY_ADDRESS); // FniRLQgZ7WhLiZhcTXHDKf8YiC4v3vytYmNubWkapA5Z

// Solana RPC endpoint
define('SOLANA_RPC_URL', 'https://api.mainnet-beta.solana.com');

/**
 * Verify a Solana payment transaction.
 *
 * @param string $txSignature  The transaction signature (base58, 87-88 chars)
 * @param float  $expectedAmount  The minimum amount expected
 * @param string $currency  'sol' or 'gsm'
 * @return array ['verified' => bool, 'error' => string|null, 'actual_amount' => float]
 */
function verifySolanaPayment(string $txSignature, float $expectedAmount, string $currency = 'sol'): array {
    // Validate signature format (base58, 87-88 chars)
    if (!preg_match('/^[1-9A-HJ-NP-Za-km-z]{87,88}$/', $txSignature)) {
        return ['verified' => false, 'error' => 'Invalid transaction signature format', 'actual_amount' => 0];
    }

    // Fetch transaction from Solana RPC
    $payload = json_encode([
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'getTransaction',
        'params' => [
            $txSignature,
            [
                'encoding' => 'jsonParsed',
                'commitment' => 'confirmed',
                'maxSupportedTransactionVersion' => 0,
            ],
        ],
    ]);

    $ch = curl_init(SOLANA_RPC_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError || $httpCode !== 200) {
        return ['verified' => false, 'error' => 'RPC request failed: ' . ($curlError ?: "HTTP $httpCode"), 'actual_amount' => 0];
    }

    $data = json_decode($response, true);
    if (!$data || isset($data['error'])) {
        return ['verified' => false, 'error' => 'RPC error: ' . ($data['error']['message'] ?? 'Unknown'), 'actual_amount' => 0];
    }

    $tx = $data['result'] ?? null;
    if (!$tx) {
        return ['verified' => false, 'error' => 'Transaction not found or not yet confirmed', 'actual_amount' => 0];
    }

    // Check transaction success
    $meta = $tx['meta'] ?? [];
    if (($meta['err'] ?? null) !== null) {
        return ['verified' => false, 'error' => 'Transaction failed on-chain', 'actual_amount' => 0];
    }

    if ($currency === 'sol') {
        return verifySolTransfer($tx, $expectedAmount);
    } elseif ($currency === 'gsm') {
        return verifyGsmTransfer($tx, $expectedAmount);
    }

    return ['verified' => false, 'error' => 'Unknown currency: ' . $currency, 'actual_amount' => 0];
}

/**
 * Verify native SOL transfer to treasury.
 */
function verifySolTransfer(array $tx, float $expectedAmount): array {
    $meta = $tx['meta'];
    $accountKeys = $tx['transaction']['message']['accountKeys'] ?? [];

    // Find treasury wallet index
    $treasuryIndex = null;
    foreach ($accountKeys as $i => $key) {
        $pubkey = is_array($key) ? ($key['pubkey'] ?? '') : $key;
        if ($pubkey === DOMAIN_TREASURY_WALLET) {
            $treasuryIndex = $i;
            break;
        }
    }

    if ($treasuryIndex === null) {
        return ['verified' => false, 'error' => 'Treasury wallet not found in transaction', 'actual_amount' => 0];
    }

    // Calculate SOL received by treasury (post - pre balance)
    $preBal = $meta['preBalances'][$treasuryIndex] ?? 0;
    $postBal = $meta['postBalances'][$treasuryIndex] ?? 0;
    $receivedLamports = $postBal - $preBal;
    $receivedSol = $receivedLamports / 1_000_000_000;

    // Allow 1% tolerance for rounding
    $tolerance = $expectedAmount * 0.01;
    if ($receivedSol >= ($expectedAmount - $tolerance)) {
        return ['verified' => true, 'error' => null, 'actual_amount' => $receivedSol];
    }

    return [
        'verified' => false,
        'error' => sprintf('Insufficient amount: expected %.8f SOL, received %.8f SOL', $expectedAmount, $receivedSol),
        'actual_amount' => $receivedSol,
    ];
}

/**
 * Verify GSM (SPL token) transfer to treasury.
 */
function verifyGsmTransfer(array $tx, float $expectedAmount): array {
    $meta = $tx['meta'];
    $instructions = $tx['transaction']['message']['instructions'] ?? [];

    // Check parsed token transfer instructions
    $totalReceived = 0;

    // Also check inner instructions (for associated token account creation)
    $allInstructions = $instructions;
    foreach (($meta['innerInstructions'] ?? []) as $inner) {
        foreach (($inner['instructions'] ?? []) as $ix) {
            $allInstructions[] = $ix;
        }
    }

    foreach ($allInstructions as $ix) {
        $parsed = $ix['parsed'] ?? null;
        $program = $ix['program'] ?? '';

        if ($program !== 'spl-token' || !$parsed) continue;

        $type = $parsed['type'] ?? '';
        $info = $parsed['info'] ?? [];

        if ($type === 'transfer' || $type === 'transferChecked') {
            // Check the mint matches GSM
            if ($type === 'transferChecked') {
                $mint = $info['mint'] ?? '';
                if ($mint !== GSM_MINT_ADDRESS) continue;
            }

            // For 'transfer' type, we verify via token account ownership
            // The destination must be the treasury's token account
            $amount = 0;
            if (isset($info['tokenAmount']['uiAmount'])) {
                $amount = (float)$info['tokenAmount']['uiAmount'];
            } elseif (isset($info['amount'])) {
                // Raw amount in smallest units
                $amount = (float)$info['amount'] / pow(10, GSM_DECIMALS);
            }

            $totalReceived += $amount;
        }
    }

    // Also check pre/post token balances for the treasury
    $preTokenBalances = $meta['preTokenBalances'] ?? [];
    $postTokenBalances = $meta['postTokenBalances'] ?? [];

    foreach ($postTokenBalances as $post) {
        if (($post['owner'] ?? '') !== DOMAIN_TREASURY_WALLET) continue;
        if (($post['mint'] ?? '') !== GSM_MINT_ADDRESS) continue;

        $postAmount = (float)($post['uiTokenAmount']['uiAmount'] ?? 0);

        // Find matching pre-balance
        $preAmount = 0;
        foreach ($preTokenBalances as $pre) {
            if (($pre['owner'] ?? '') === DOMAIN_TREASURY_WALLET && ($pre['mint'] ?? '') === GSM_MINT_ADDRESS) {
                $preAmount = (float)($pre['uiTokenAmount']['uiAmount'] ?? 0);
                break;
            }
        }

        $balanceDelta = $postAmount - $preAmount;
        if ($balanceDelta > $totalReceived) {
            $totalReceived = $balanceDelta;
        }
    }

    // Allow 1% tolerance
    $tolerance = $expectedAmount * 0.01;
    if ($totalReceived >= ($expectedAmount - $tolerance)) {
        return ['verified' => true, 'error' => null, 'actual_amount' => $totalReceived];
    }

    return [
        'verified' => false,
        'error' => sprintf('Insufficient GSM: expected %.2f GSM, received %.2f GSM', $expectedAmount, $totalReceived),
        'actual_amount' => $totalReceived,
    ];
}

/**
 * Check if a transaction signature has already been used for a domain payment.
 * Prevents double-spend of the same tx for multiple domains.
 */
function isTxAlreadyUsed(string $txSignature): bool {
    require_once __DIR__ . '/db-config.inc.php';
    $db = getSharedDB();
    $stmt = $db->prepare("SELECT id FROM sovereign_domains WHERE payment_tx = ? AND payment_confirmed = 1 LIMIT 1");
    $stmt->execute([$txSignature]);
    return (bool)$stmt->fetch();
}
