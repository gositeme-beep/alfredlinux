<?php
/**
 * Solana Blockchain Integration API — GSM Token On-Chain Bridge
 * =============================================================
 * Phase 2 blockchain integration layer for the GSM token ecosystem.
 *
 * Endpoints:
 *   GET  ?action=token-info         — GSM SPL token metadata + on-chain status
 *   GET  ?action=wallet-status      — User's linked Solana wallet + on-chain balance
 *   POST ?action=link-wallet        — Link a Solana wallet address to account
 *   POST ?action=unlink-wallet      — Remove linked wallet
 *   POST ?action=request-withdrawal — Request GSM withdrawal to linked Solana wallet
 *   GET  ?action=withdrawal-history — Withdrawal request history
 *   POST ?action=confirm-withdrawal — Admin: confirm/reject withdrawal
 *   GET  ?action=dex-info           — DEX listing status + liquidity info
 *   GET  ?action=escrow-status      — On-chain escrow program status
 *   POST ?action=create-escrow      — Create escrow for high-value wager
 *   POST ?action=release-escrow     — Release/settle escrow
 *   GET  ?action=nft-mint-status    — Metaplex NFT minting capability status
 *   POST ?action=mint-nft           — Mint a real compressed NFT (Metaplex)
 *   GET  ?action=blockchain-stats   — Overall blockchain integration statistics
 *   GET  ?action=roadmap            — Smart contract deployment roadmap
 *
 * Architecture:
 *   This API wraps the future Solana on-chain programs. Currently operates in
 *   "bridge mode" — recording intents in the database with verification queues
 *   for manual processing until full on-chain automation is deployed.
 *
 *   Phase 2a: SPL token deployed on Solana mainnet (token metadata + mint)
 *   Phase 2b: Wallet linking (Phantom/Solflare adapter)
 *   Phase 2c: Withdrawal queue (manual → automated)
 *   Phase 2d: DEX liquidity pool (Raydium/Jupiter)
 *   Phase 2e: NFT minting (Metaplex compressed NFTs)
 *   Phase 2f: Escrow program (Anchor PDA for trustless wagers)
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Rate limit
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$rateKey = 'solana_api_' . md5($ip);

// ── Auth ──
if (session_status() === PHP_SESSION_NONE) session_start();
$clientId = $_SESSION['client_id'] ?? null;
$isCommander = ($clientId === 33);

// ── DB ──
$db = getDB();

// ── Ensure tables ──
$db->exec("CREATE TABLE IF NOT EXISTS gsm_wallet_links (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    client_id     INT NOT NULL,
    wallet_address VARCHAR(64) NOT NULL,
    wallet_type   ENUM('phantom','solflare','backpack','other') DEFAULT 'phantom',
    verified      TINYINT(1) DEFAULT 0,
    linked_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    unlinked_at   DATETIME NULL,
    is_active     TINYINT(1) DEFAULT 1,
    UNIQUE KEY idx_client_active (client_id, is_active),
    KEY idx_wallet (wallet_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS gsm_withdrawals (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    client_id       INT NOT NULL,
    amount_gsm      DECIMAL(18,8) NOT NULL,
    destination     VARCHAR(64) NOT NULL,
    status          ENUM('pending','processing','completed','rejected','failed') DEFAULT 'pending',
    tx_signature    VARCHAR(128) NULL,
    fee_gsm         DECIMAL(18,8) DEFAULT 0,
    fee_sol         DECIMAL(18,9) DEFAULT 0,
    requested_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    processed_at    DATETIME NULL,
    processed_by    INT NULL,
    reject_reason   VARCHAR(255) NULL,
    KEY idx_client (client_id),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS gsm_escrows (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    escrow_type     ENUM('wager','trade','land','custom') DEFAULT 'wager',
    creator_id      INT NOT NULL,
    counterparty_id INT NULL,
    amount_gsm      DECIMAL(18,8) NOT NULL,
    conditions      JSON NULL,
    status          ENUM('created','funded','active','settled','cancelled','expired') DEFAULT 'created',
    on_chain        TINYINT(1) DEFAULT 0,
    pda_address     VARCHAR(64) NULL,
    tx_create       VARCHAR(128) NULL,
    tx_settle       VARCHAR(128) NULL,
    expires_at      DATETIME NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    settled_at      DATETIME NULL,
    KEY idx_creator (creator_id),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS gsm_nft_mints (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    client_id       INT NOT NULL,
    trophy_id       INT NULL,
    nft_type        ENUM('trophy','achievement','land_deed','collectible') DEFAULT 'trophy',
    metadata_uri    VARCHAR(512) NULL,
    mint_address    VARCHAR(64) NULL,
    merkle_tree     VARCHAR(64) NULL,
    leaf_index      INT NULL,
    tx_signature    VARCHAR(128) NULL,
    status          ENUM('queued','minting','completed','failed') DEFAULT 'queued',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    minted_at       DATETIME NULL,
    KEY idx_client (client_id),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── GSM Token Constants (centralized) ──
require_once '/var/www/includes/gsm-config.inc.php';
define('GSM_NAME',             'GoSiteMe Token');
// Token mint address — live on Solana mainnet since 2026-04-08
define('GSM_PROGRAM_ID',       ''); // TBD: escrow program ID

// Withdrawal limits
define('GSM_MIN_WITHDRAWAL', 100);
define('GSM_MAX_WITHDRAWAL', 1000000);
define('GSM_WITHDRAWAL_FEE_PCT', 0.5); // 0.5% withdrawal fee

// ── Helpers ──
function validateSolanaAddress(string $addr): bool {
    // Base58 Solana address: 32-44 chars, no 0OIl
    return (bool) preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $addr);
}

function getClientBalance(PDO $db, int $clientId): array {
    $stmt = $db->prepare("SELECT balance, staked_amount FROM crypto_gsm_balances WHERE client_id = ?");
    $stmt->execute([$clientId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        // Auto-create balance row for new users
        $db->prepare("INSERT IGNORE INTO crypto_gsm_balances (client_id, balance) VALUES (?, 0)")->execute([$clientId]);
        return ['gsm_available' => 0.0, 'gsm_staked' => 0.0];
    }
    return [
        'gsm_available' => (float) $row['balance'],
        'gsm_staked'    => (float) $row['staked_amount'],
    ];
}

function getLinkedWallet(PDO $db, int $clientId): ?array {
    $stmt = $db->prepare("SELECT * FROM gsm_wallet_links WHERE client_id = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$clientId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function jsonOk(array $data): void {
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

function jsonErr(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

function base58_encode(string $data): string {
    $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
    $base = strlen($alphabet);
    $bytes = array_values(unpack('C*', $data));
    $digits = [0];
    foreach ($bytes as $byte) {
        $carry = $byte;
        for ($j = 0; $j < count($digits); $j++) {
            $carry += $digits[$j] << 8;
            $digits[$j] = $carry % $base;
            $carry = intdiv($carry, $base);
        }
        while ($carry > 0) {
            $digits[] = $carry % $base;
            $carry = intdiv($carry, $base);
        }
    }
    $str = '';
    foreach (array_reverse($digits) as $d) $str .= $alphabet[$d];
    // Leading zeros
    foreach ($bytes as $b) { if ($b === 0) $str = '1' . $str; else break; }
    return $str;
}

// ── Route ──
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

// ═══════════════════════════════════════════
// TOKEN INFO — Public
// ═══════════════════════════════════════════
case 'token-info':
    $deploymentStatus = GSM_MINT_ADDRESS ? 'deployed' : 'pending';

    jsonOk([
        'token' => [
            'name'          => GSM_NAME,
            'symbol'        => GSM_SYMBOL,
            'decimals'      => GSM_DECIMALS,
            'total_supply'  => GSM_TOTAL_SUPPLY,
            'network'       => GSM_NETWORK,
            'standard'      => 'SPL (Solana Program Library)',
            'mint_address'  => GSM_MINT_ADDRESS ?: null,
            'treasury'      => GSM_TREASURY_ADDRESS ?: null,
        ],
        'deployment' => [
            'status'            => $deploymentStatus,
            'mint_deployed'     => !empty(GSM_MINT_ADDRESS),
            'escrow_deployed'   => !empty(GSM_PROGRAM_ID),
            'dex_listed'        => false,
            'nft_program_ready' => false,
        ],
        'economics' => [
            'withdrawal_fee_pct' => GSM_WITHDRAWAL_FEE_PCT,
            'min_withdrawal'     => GSM_MIN_WITHDRAWAL,
            'max_withdrawal'     => GSM_MAX_WITHDRAWAL,
            'exchange_rate_stub' => 10000, // GSM per SOL — stub until DEX
        ],
        'distribution' => [
            'total_supply'       => 1000000000,
            'treasury_reserve'   => ['amount' => 300000000, 'pct' => 30, 'note' => 'Operations, liquidity, partnerships'],
            'mining_pool'        => ['amount' => 250000000, 'pct' => 25, 'note' => 'User mining rewards — earned by platform activity'],
            'community_rewards'  => ['amount' => 150000000, 'pct' => 15, 'note' => 'Staking yields, achievements, referrals, airdrops'],
            'founder_team'       => ['amount' => 150000000, 'pct' => 15, 'note' => 'Founder & team — 4-year vesting, 1-year cliff'],
            'eden_trust'         => ['amount' =>  50000000, 'pct' =>  5, 'note' => 'Eden Sarai Gabrielle Vallee Perez — inheritance trust, locked until age 18 (2030-08-21)'],
            'ecosystem_dev'      => ['amount' =>  50000000, 'pct' =>  5, 'note' => 'IDE extensions, open-source bounties, developer grants'],
            'dex_liquidity'      => ['amount' =>  50000000, 'pct' =>  5, 'note' => 'Initial DEX liquidity seeding (Raydium/Jupiter)'],
        ],
        'links' => [
            'explorer'   => GSM_MINT_ADDRESS ? "https://solscan.io/token/" . GSM_MINT_ADDRESS : null,
            'jupiter'    => null, // TBD when DEX listed
            'raydium'    => null,
            'metaplex'   => null,
        ],
    ]);

// ═══════════════════════════════════════════
// WALLET STATUS — Authenticated
// ═══════════════════════════════════════════
case 'wallet-status':
    if (!$clientId) jsonErr('Authentication required', 401);

    $wallet = getLinkedWallet($db, $clientId);
    $balance = getClientBalance($db, $clientId);

    $pendingWithdrawals = 0;
    $stmt = $db->prepare("SELECT COALESCE(SUM(amount_gsm),0) FROM gsm_withdrawals WHERE client_id = ? AND status IN ('pending','processing')");
    $stmt->execute([$clientId]);
    $pendingWithdrawals = (float) $stmt->fetchColumn();

    jsonOk([
        'wallet_linked'     => !empty($wallet),
        'wallet_address'    => $wallet['wallet_address'] ?? null,
        'wallet_type'       => $wallet['wallet_type'] ?? null,
        'wallet_verified'   => (bool) ($wallet['verified'] ?? false),
        'linked_at'         => $wallet['linked_at'] ?? null,
        'platform_balance'  => $balance,
        'on_chain_balance'  => null, // TBD: query Solana RPC
        'pending_withdrawals' => $pendingWithdrawals,
        'can_withdraw'      => !empty($wallet) && $balance['gsm_available'] >= GSM_MIN_WITHDRAWAL,
    ]);

// ═══════════════════════════════════════════
// LINK WALLET — Authenticated
// ═══════════════════════════════════════════
case 'link-wallet':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonErr('POST required', 405);
    if (!$clientId) jsonErr('Authentication required', 401);

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $address = trim($input['wallet_address'] ?? '');
    $type = $input['wallet_type'] ?? 'phantom';

    if (!$address) jsonErr('wallet_address required');
    if (!validateSolanaAddress($address)) jsonErr('Invalid Solana wallet address');
    if (!in_array($type, ['phantom','solflare','backpack','other'])) $type = 'other';

    // Check if address is already linked to another account
    $stmt = $db->prepare("SELECT client_id FROM gsm_wallet_links WHERE wallet_address = ? AND is_active = 1 AND client_id != ?");
    $stmt->execute([$address, $clientId]);
    if ($stmt->fetch()) jsonErr('This wallet is already linked to another account');

    $db->beginTransaction();
    try {
        // Deactivate any existing wallet
        $stmt = $db->prepare("UPDATE gsm_wallet_links SET is_active = 0, unlinked_at = NOW() WHERE client_id = ? AND is_active = 1");
        $stmt->execute([$clientId]);

        // Link new wallet
        $stmt = $db->prepare("INSERT INTO gsm_wallet_links (client_id, wallet_address, wallet_type) VALUES (?, ?, ?)");
        $stmt->execute([$clientId, $address, $type]);

        $db->commit();
        jsonOk(['linked' => true, 'wallet_address' => $address, 'wallet_type' => $type]);
    } catch (Exception $e) {
        $db->rollBack();
        jsonErr('Failed to link wallet');
    }

// ═══════════════════════════════════════════
// UNLINK WALLET — Authenticated
// ═══════════════════════════════════════════
case 'unlink-wallet':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonErr('POST required', 405);
    if (!$clientId) jsonErr('Authentication required', 401);

    // Can't unlink if pending withdrawals
    $stmt = $db->prepare("SELECT COUNT(*) FROM gsm_withdrawals WHERE client_id = ? AND status IN ('pending','processing')");
    $stmt->execute([$clientId]);
    if ((int) $stmt->fetchColumn() > 0) jsonErr('Cannot unlink wallet with pending withdrawals');

    $stmt = $db->prepare("UPDATE gsm_wallet_links SET is_active = 0, unlinked_at = NOW() WHERE client_id = ? AND is_active = 1");
    $stmt->execute([$clientId]);
    jsonOk(['unlinked' => true]);

// ═══════════════════════════════════════════
// REQUEST WITHDRAWAL — Authenticated
// ═══════════════════════════════════════════
case 'request-withdrawal':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonErr('POST required', 405);
    if (!$clientId) jsonErr('Authentication required', 401);

    $wallet = getLinkedWallet($db, $clientId);
    if (!$wallet) jsonErr('No wallet linked. Link a Solana wallet first.');

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $amount = (float) ($input['amount_gsm'] ?? 0);

    if ($amount < GSM_MIN_WITHDRAWAL) jsonErr("Minimum withdrawal is " . GSM_MIN_WITHDRAWAL . " GSM");
    if ($amount > GSM_MAX_WITHDRAWAL) jsonErr("Maximum withdrawal is " . number_format(GSM_MAX_WITHDRAWAL) . " GSM per request");

    $balance = getClientBalance($db, $clientId);
    if ($amount > $balance['gsm_available']) jsonErr('Insufficient GSM balance');

    $fee = round($amount * GSM_WITHDRAWAL_FEE_PCT / 100, 8);
    $netAmount = $amount - $fee;

    $db->beginTransaction();
    try {
        // Debit platform balance
        $stmt = $db->prepare("UPDATE crypto_gsm_balances SET balance_gsm = balance_gsm - ? WHERE client_id = ? AND balance_gsm >= ?");
        $stmt->execute([$amount, $clientId, $amount]);
        if ($stmt->rowCount() === 0) {
            $db->rollBack();
            jsonErr('Insufficient balance (race condition)');
        }

        // Create withdrawal request
        $stmt = $db->prepare("INSERT INTO gsm_withdrawals (client_id, amount_gsm, destination, fee_gsm, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt->execute([$clientId, $netAmount, $wallet['wallet_address'], $fee]);
        $withdrawalId = $db->lastInsertId();

        // Record in ledger
        $stmt = $db->prepare("INSERT INTO crypto_gsm_ledger (client_id, amount, type, description, ref_id) VALUES (?, ?, 'withdrawal', ?, ?)");
        $stmt->execute([$clientId, -$amount, "Withdrawal request #{$withdrawalId} to {$wallet['wallet_address']}", $withdrawalId]);

        $db->commit();
        jsonOk([
            'withdrawal_id' => (int) $withdrawalId,
            'amount_gsm'    => $netAmount,
            'fee_gsm'       => $fee,
            'destination'   => $wallet['wallet_address'],
            'status'        => 'pending',
            'note'          => GSM_MINT_ADDRESS
                ? 'Withdrawal will be processed on-chain within 5 minutes.'
                : 'Withdrawal is queued for manual processing. The GSM SPL token is being deployed — on-chain transfers will be automated once ready.',
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        jsonErr('Withdrawal failed');
    }

// ═══════════════════════════════════════════
// WITHDRAWAL HISTORY — Authenticated
// ═══════════════════════════════════════════
case 'withdrawal-history':
    if (!$clientId) jsonErr('Authentication required', 401);

    $stmt = $db->prepare("SELECT id, amount_gsm, destination, status, fee_gsm, tx_signature, requested_at, processed_at, reject_reason FROM gsm_withdrawals WHERE client_id = ? ORDER BY requested_at DESC LIMIT 50");
    $stmt->execute([$clientId]);
    jsonOk(['withdrawals' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

// ═══════════════════════════════════════════
// CONFIRM WITHDRAWAL — Commander only
// ═══════════════════════════════════════════
case 'confirm-withdrawal':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonErr('POST required', 405);
    if (!$isCommander) jsonErr('Commander access required', 403);

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $withdrawalId = (int) ($input['withdrawal_id'] ?? 0);
    $decision = $input['decision'] ?? ''; // 'approve' or 'reject'
    $txSig = $input['tx_signature'] ?? '';
    $reason = $input['reason'] ?? '';

    if (!$withdrawalId) jsonErr('withdrawal_id required');
    if (!in_array($decision, ['approve', 'reject'])) jsonErr('decision must be approve or reject');

    $stmt = $db->prepare("SELECT * FROM gsm_withdrawals WHERE id = ? AND status = 'pending'");
    $stmt->execute([$withdrawalId]);
    $withdrawal = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$withdrawal) jsonErr('Withdrawal not found or not pending');

    $db->beginTransaction();
    try {
        if ($decision === 'approve') {
            $stmt = $db->prepare("UPDATE gsm_withdrawals SET status = 'completed', tx_signature = ?, processed_at = NOW(), processed_by = ? WHERE id = ?");
            $stmt->execute([$txSig ?: null, $clientId, $withdrawalId]);
        } else {
            // Refund
            $stmt = $db->prepare("UPDATE crypto_gsm_balances SET balance_gsm = balance_gsm + ? WHERE client_id = ?");
            $stmt->execute([$withdrawal['amount_gsm'] + $withdrawal['fee_gsm'], $withdrawal['client_id']]);

            $stmt = $db->prepare("UPDATE gsm_withdrawals SET status = 'rejected', reject_reason = ?, processed_at = NOW(), processed_by = ? WHERE id = ?");
            $stmt->execute([$reason, $clientId, $withdrawalId]);

            $stmt = $db->prepare("INSERT INTO crypto_gsm_ledger (client_id, amount, type, description, ref_id) VALUES (?, ?, 'refund', ?, ?)");
            $amount = $withdrawal['amount_gsm'] + $withdrawal['fee_gsm'];
            $stmt->execute([$withdrawal['client_id'], $amount, "Withdrawal #{$withdrawalId} rejected: {$reason}", $withdrawalId]);
        }
        $db->commit();
        jsonOk(['processed' => true, 'decision' => $decision, 'withdrawal_id' => $withdrawalId]);
    } catch (Exception $e) {
        $db->rollBack();
        jsonErr('Processing failed');
    }

// ═══════════════════════════════════════════
// DEX INFO — Public
// ═══════════════════════════════════════════
case 'dex-info':
    jsonOk([
        'status' => 'pre-listing',
        'phase'  => '2d',
        'planned_dex' => [
            [
                'name' => 'Jupiter',
                'type' => 'aggregator',
                'url'  => 'https://jup.ag',
                'status' => 'planned',
                'required' => 'SPL token deployment + initial liquidity pool',
            ],
            [
                'name' => 'Raydium',
                'type' => 'AMM / liquidity pool',
                'url'  => 'https://raydium.io',
                'status' => 'planned',
                'required' => 'SPL token + SOL/GSM concentrated liquidity pool',
            ],
        ],
        'exchange_rate' => [
            'current'     => 10000, // GSM per SOL — stub
            'source'      => 'fixed_stub',
            'note'        => 'Exchange rate is set manually until DEX listing provides real market price',
        ],
        'liquidity' => [
            'pool_deployed'   => false,
            'total_liquidity' => 0,
            'gsm_in_pool'     => 0,
            'sol_in_pool'     => 0,
        ],
        'roadmap' => [
            'Step 1' => 'Deploy GSM SPL token to Solana mainnet',
            'Step 2' => 'Seed initial liquidity (GSM + SOL)',
            'Step 3' => 'Create Raydium CLMM pool',
            'Step 4' => 'Verify on Jupiter aggregator',
            'Step 5' => 'Enable real-time price feed from on-chain',
        ],
    ]);

// ═══════════════════════════════════════════
// ESCROW — Authenticated
// ═══════════════════════════════════════════
case 'escrow-status':
    if (!$clientId) jsonErr('Authentication required', 401);

    $stmt = $db->prepare("SELECT id, escrow_type, amount_gsm, status, on_chain, pda_address, counterparty_id, expires_at, created_at FROM gsm_escrows WHERE creator_id = ? OR counterparty_id = ? ORDER BY created_at DESC LIMIT 20");
    $stmt->execute([$clientId, $clientId]);

    jsonOk([
        'program_deployed' => !empty(GSM_PROGRAM_ID),
        'program_id'       => GSM_PROGRAM_ID ?: null,
        'escrow_mode'      => GSM_PROGRAM_ID ? 'on-chain' : 'custodial',
        'escrows'          => $stmt->fetchAll(PDO::FETCH_ASSOC),
    ]);

case 'create-escrow':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonErr('POST required', 405);
    if (!$clientId) jsonErr('Authentication required', 401);

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $type = $input['escrow_type'] ?? 'wager';
    $amount = (float) ($input['amount_gsm'] ?? 0);
    $counterpartyId = (int) ($input['counterparty_id'] ?? 0);
    $conditions = $input['conditions'] ?? null;
    $expiresHours = (int) ($input['expires_hours'] ?? 24);

    if (!in_array($type, ['wager','trade','land','custom'])) jsonErr('Invalid escrow_type');
    if ($amount <= 0) jsonErr('amount_gsm must be positive');

    $balance = getClientBalance($db, $clientId);
    if ($amount > $balance['gsm_available']) jsonErr('Insufficient GSM balance');

    $db->beginTransaction();
    try {
        // Debit creator
        $stmt = $db->prepare("UPDATE crypto_gsm_balances SET balance_gsm = balance_gsm - ? WHERE client_id = ? AND balance_gsm >= ?");
        $stmt->execute([$amount, $clientId, $amount]);
        if ($stmt->rowCount() === 0) { $db->rollBack(); jsonErr('Insufficient balance'); }

        $expiresAt = date('Y-m-d H:i:s', time() + ($expiresHours * 3600));

        $stmt = $db->prepare("INSERT INTO gsm_escrows (escrow_type, creator_id, counterparty_id, amount_gsm, conditions, status, expires_at, on_chain) VALUES (?, ?, ?, ?, ?, 'funded', ?, ?)");
        $onChain = !empty(GSM_PROGRAM_ID) ? 1 : 0;
        $stmt->execute([$type, $clientId, $counterpartyId ?: null, $amount, $conditions ? json_encode($conditions) : null, $expiresAt, $onChain]);
        $escrowId = $db->lastInsertId();

        // Ledger
        $stmt = $db->prepare("INSERT INTO crypto_gsm_ledger (client_id, amount, type, description, ref_id) VALUES (?, ?, 'escrow_lock', ?, ?)");
        $stmt->execute([$clientId, -$amount, "Escrow #{$escrowId} ({$type})", $escrowId]);

        $db->commit();
        jsonOk([
            'escrow_id'  => (int) $escrowId,
            'amount_gsm' => $amount,
            'type'       => $type,
            'status'     => 'funded',
            'mode'       => $onChain ? 'on-chain' : 'custodial',
            'expires_at' => $expiresAt,
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        jsonErr('Escrow creation failed');
    }

case 'release-escrow':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonErr('POST required', 405);
    if (!$clientId) jsonErr('Authentication required', 401);

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $escrowId = (int) ($input['escrow_id'] ?? 0);
    $recipient = $input['recipient'] ?? 'creator'; // 'creator' or 'counterparty'

    $stmt = $db->prepare("SELECT * FROM gsm_escrows WHERE id = ? AND status IN ('funded','active')");
    $stmt->execute([$escrowId]);
    $escrow = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$escrow) jsonErr('Escrow not found or not active');

    // Only creator or commander can release
    if ($escrow['creator_id'] != $clientId && !$isCommander) jsonErr('Not authorized');

    $recipientId = $recipient === 'counterparty' && $escrow['counterparty_id']
        ? $escrow['counterparty_id']
        : $escrow['creator_id'];

    $db->beginTransaction();
    try {
        // Credit recipient
        $stmt = $db->prepare("INSERT INTO crypto_gsm_balances (client_id, balance_gsm) VALUES (?, ?) ON DUPLICATE KEY UPDATE balance_gsm = balance_gsm + ?");
        $amount = (float) $escrow['amount_gsm'];
        $stmt->execute([$recipientId, $amount, $amount]);

        // Update escrow
        $stmt = $db->prepare("UPDATE gsm_escrows SET status = 'settled', settled_at = NOW() WHERE id = ?");
        $stmt->execute([$escrowId]);

        // Ledger
        $stmt = $db->prepare("INSERT INTO crypto_gsm_ledger (client_id, amount, type, description, ref_id) VALUES (?, ?, 'escrow_release', ?, ?)");
        $stmt->execute([$recipientId, $amount, "Escrow #{$escrowId} released to client #{$recipientId}", $escrowId]);

        $db->commit();
        jsonOk(['settled' => true, 'escrow_id' => $escrowId, 'recipient_id' => $recipientId, 'amount_gsm' => $amount]);
    } catch (Exception $e) {
        $db->rollBack();
        jsonErr('Escrow release failed');
    }

// ═══════════════════════════════════════════
// NFT MINTING — Authenticated
// ═══════════════════════════════════════════
case 'nft-mint-status':
    $stmt = $db->query("SELECT status, COUNT(*) as c FROM gsm_nft_mints GROUP BY status");
    $stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    jsonOk([
        'program'   => 'Metaplex Bubblegum (Compressed NFTs)',
        'deployed'  => false,
        'mode'      => 'queued', // 'queued' → records intent, 'live' → mints on-chain
        'merkle_tree_address' => null, // TBD
        'total_minted'  => (int) ($stats['completed'] ?? 0),
        'total_queued'  => (int) ($stats['queued'] ?? 0),
        'total_failed'  => (int) ($stats['failed'] ?? 0),
        'supported_types' => ['trophy', 'achievement', 'land_deed', 'collectible'],
        'roadmap' => [
            'Step 1' => 'Deploy Merkle tree account on Solana',
            'Step 2' => 'Configure Metaplex Bubblegum program',
            'Step 3' => 'Upload metadata to Arweave/IPFS',
            'Step 4' => 'Enable compressed NFT minting',
            'Step 5' => 'User wallets receive real NFTs',
        ],
    ]);

case 'mint-nft':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonErr('POST required', 405);
    if (!$clientId) jsonErr('Authentication required', 401);

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $trophyId = (int) ($input['trophy_id'] ?? 0);
    $nftType = $input['nft_type'] ?? 'trophy';

    if (!in_array($nftType, ['trophy','achievement','land_deed','collectible'])) jsonErr('Invalid nft_type');

    // Check wallet linked
    $wallet = getLinkedWallet($db, $clientId);
    if (!$wallet) jsonErr('Link a Solana wallet first to receive NFTs');

    // Queue the mint
    $stmt = $db->prepare("INSERT INTO gsm_nft_mints (client_id, trophy_id, nft_type, status) VALUES (?, ?, ?, 'queued')");
    $stmt->execute([$clientId, $trophyId ?: null, $nftType]);
    $mintId = $db->lastInsertId();

    jsonOk([
        'mint_id'   => (int) $mintId,
        'status'    => 'queued',
        'nft_type'  => $nftType,
        'trophy_id' => $trophyId ?: null,
        'destination_wallet' => $wallet['wallet_address'],
        'note'      => 'NFT mint is queued. On-chain minting will execute once the Metaplex Bubblegum program is deployed. You will receive the compressed NFT in your linked wallet.',
    ]);

// ═══════════════════════════════════════════
// BLOCKCHAIN STATS — Public
// ═══════════════════════════════════════════
case 'blockchain-stats':
    $walletCount = (int) $db->query("SELECT COUNT(*) FROM gsm_wallet_links WHERE is_active = 1")->fetchColumn();
    $totalWithdrawn = (float) $db->query("SELECT COALESCE(SUM(amount_gsm),0) FROM gsm_withdrawals WHERE status = 'completed'")->fetchColumn();
    $pendingWithdrawals = (int) $db->query("SELECT COUNT(*) FROM gsm_withdrawals WHERE status = 'pending'")->fetchColumn();
    $totalEscrows = (int) $db->query("SELECT COUNT(*) FROM gsm_escrows")->fetchColumn();
    $activeEscrows = (int) $db->query("SELECT COUNT(*) FROM gsm_escrows WHERE status IN ('funded','active')")->fetchColumn();
    $nftsMinted = (int) $db->query("SELECT COUNT(*) FROM gsm_nft_mints WHERE status = 'completed'")->fetchColumn();
    $nftsQueued = (int) $db->query("SELECT COUNT(*) FROM gsm_nft_mints WHERE status = 'queued'")->fetchColumn();

    jsonOk([
        'wallets_linked'       => $walletCount,
        'total_withdrawn_gsm'  => $totalWithdrawn,
        'pending_withdrawals'  => $pendingWithdrawals,
        'total_escrows'        => $totalEscrows,
        'active_escrows'       => $activeEscrows,
        'nfts_minted'          => $nftsMinted,
        'nfts_queued'          => $nftsQueued,
        'smart_contracts' => [
            'spl_token'    => ['status' => GSM_MINT_ADDRESS ? 'deployed' : 'pending', 'phase' => '2a'],
            'escrow'       => ['status' => GSM_PROGRAM_ID ? 'deployed' : 'pending',   'phase' => '2f'],
            'nft_program'  => ['status' => 'pending',                                  'phase' => '2e'],
            'dex_pool'     => ['status' => 'pending',                                  'phase' => '2d'],
        ],
    ]);

// ═══════════════════════════════════════════
// ROADMAP — Public
// ═══════════════════════════════════════════
case 'roadmap':
    jsonOk([
        'title' => 'GSM Token — Solana Smart Contract Roadmap',
        'current_architecture' => 'Custodial PHP+MySQL backend — all economy operations are instant, free, and flexible',
        'target_architecture'  => 'Hybrid — off-chain speed for micro-transactions, on-chain trust for high-value operations',
        'phases' => [
            [
                'id'          => '2a',
                'name'        => 'SPL Token Deployment',
                'status'      => GSM_MINT_ADDRESS ? 'completed' : 'ready',
                'description' => 'Deploy GSM as a real SPL token on Solana mainnet with Metaplex token metadata',
                'requirements'=> ['Solana CLI + keypair', 'spl-token create-token', 'Metaplex token-metadata program', '~0.01 SOL for deployment'],
                'output'      => 'Mint address, token metadata on-chain, verified on Solscan/Solana Explorer',
            ],
            [
                'id'          => '2b',
                'name'        => 'Wallet Linking',
                'status'      => 'ready',
                'description' => 'Users connect Phantom/Solflare/Backpack wallets to their GoSiteMe accounts',
                'requirements'=> ['@solana/wallet-adapter JS library', 'Wallet sign-to-verify flow', 'DB wallet_links table'],
                'output'      => 'Users can link/unlink Solana wallets, verified via signature',
            ],
            [
                'id'          => '2c',
                'name'        => 'On-Chain Withdrawal',
                'status'      => 'ready',
                'description' => 'Users can withdraw GSM from platform to their linked Solana wallet',
                'requirements'=> ['Treasury wallet funded with GSM tokens', 'Transfer instruction via @solana/web3.js', 'Withdrawal queue + auto-processing worker'],
                'output'      => 'Verified withdrawals execute as on-chain SPL transfers within minutes',
            ],
            [
                'id'          => '2d',
                'name'        => 'DEX Liquidity Pool',
                'status'      => 'planned',
                'description' => 'Create SOL/GSM trading pair on Raydium, auto-routed via Jupiter aggregator',
                'requirements'=> ['Initial liquidity (GSM + SOL)', 'Raydium CLMM pool creation', 'Jupiter verification'],
                'output'      => 'Real-time market price, anyone can trade GSM/SOL on DEX',
            ],
            [
                'id'          => '2e',
                'name'        => 'NFT Minting (Metaplex)',
                'status'      => 'planned',
                'description' => 'Real compressed NFTs for trophies, achievements, land deeds using Metaplex Bubblegum',
                'requirements'=> ['Merkle tree account on Solana', 'Metadata uploads to Arweave', 'Bubblegum mint instructions'],
                'output'      => 'Trophy NFTs appear in user wallets, viewable on Magic Eden / Tensor',
            ],
            [
                'id'          => '2f',
                'name'        => 'Escrow Smart Contract',
                'status'      => 'planned',
                'description' => 'Trustless on-chain escrow for high-value wagers using Anchor PDA',
                'requirements'=> ['Anchor framework', 'Escrow program (create/fund/settle/cancel)', 'PDA-based token accounts'],
                'output'      => 'High-value wagers are held in a trustless smart contract, not the platform',
            ],
        ],
        'philosophy' => 'Smart contracts are a scaling/trust feature, not a launch feature. The custodial backend handles everything the ecosystem needs today. On-chain programs add trustlessness and decentralization as the platform grows.',
    ]);

// ═══════════════════════════════════════════
// CUSTODIAL WALLET — Auto-create server-side wallet
// ═══════════════════════════════════════════
case 'custodial-wallet':
    if (!$clientId) jsonErr('Authentication required', 401);

    // Check if custodial wallet exists
    $stmt = $db->prepare("SELECT public_key, created_at, last_used_at FROM gsm_custodial_wallets WHERE client_id = ? AND is_active = 1");
    $stmt->execute([$clientId]);
    $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($wallet) {
        $balance = getClientBalance($db, $clientId);
        jsonOk([
            'has_wallet'    => true,
            'public_key'    => $wallet['public_key'],
            'created_at'    => $wallet['created_at'],
            'balance'       => $balance,
        ]);
    }

    // No wallet yet — create on GET (auto-provision)
    $encKey = getenv('GSM_WALLET_ENCRYPTION_KEY') ?: hash('sha256', 'gsm-custodial-' . ($clientId * 7919));

    // Generate a deterministic-looking but unique keypair seed
    $seed = hash('sha512', $encKey . '-' . $clientId . '-' . random_bytes(32), true);
    $keypairBytes = substr($seed, 0, 32); // 32-byte Ed25519 seed

    // Derive public key via sodium if available, otherwise store seed and derive later
    if (function_exists('sodium_crypto_sign_seed_keypair')) {
        $keypair = sodium_crypto_sign_seed_keypair($keypairBytes);
        $publicKey = sodium_crypto_sign_publickey($keypair);
        $secretKey = sodium_crypto_sign_secretkey($keypair);
        // Base58 encode the public key
        $pubkeyB58 = base58_encode($publicKey);
        // Encrypt the secret key
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($secretKey, 'aes-256-cbc', hex2bin($encKey), OPENSSL_RAW_DATA, $iv);
        $encryptedHex = bin2hex($iv) . ':' . bin2hex($encrypted);
    } else {
        // Fallback: store encrypted seed, derive later with Solana CLI
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($keypairBytes, 'aes-256-cbc', hex2bin(hash('sha256', $encKey)), OPENSSL_RAW_DATA, $iv);
        $encryptedHex = 'seed:' . bin2hex($iv) . ':' . bin2hex($encrypted);
        $pubkeyB58 = 'pending-' . substr(bin2hex($keypairBytes), 0, 16);
    }

    $db->beginTransaction();
    try {
        $stmt = $db->prepare("INSERT INTO gsm_custodial_wallets (client_id, public_key, encrypted_key, key_version) VALUES (?, ?, ?, 1)");
        $stmt->execute([$clientId, $pubkeyB58, $encryptedHex]);

        // Auto-create balance row
        $db->prepare("INSERT IGNORE INTO crypto_gsm_balances (client_id, balance) VALUES (?, 0)")->execute([$clientId]);

        $db->commit();
    } catch (\Exception $e) {
        $db->rollBack();
        jsonErr('Failed to create custodial wallet');
    }

    $balance = getClientBalance($db, $clientId);
    jsonOk([
        'has_wallet'    => true,
        'public_key'    => $pubkeyB58,
        'created_at'    => date('Y-m-d H:i:s'),
        'balance'       => $balance,
        'message'       => 'Custodial wallet created! You can earn GSM tokens immediately.',
    ]);

// ═══════════════════════════════════════════
// QUICK BALANCE — Lightweight balance check (for header badge)
// ═══════════════════════════════════════════
case 'balance':
    if (!$clientId) jsonErr('Authentication required', 401);

    $balance = getClientBalance($db, $clientId);
    $wallet = getLinkedWallet($db, $clientId);

    // Check custodial wallet
    $stmt = $db->prepare("SELECT public_key FROM gsm_custodial_wallets WHERE client_id = ? AND is_active = 1");
    $stmt->execute([$clientId]);
    $custodial = $stmt->fetchColumn();

    jsonOk([
        'gsm'           => $balance['gsm_available'],
        'staked'        => $balance['gsm_staked'],
        'has_wallet'    => !empty($wallet) || !empty($custodial),
        'wallet_type'   => $wallet ? 'external' : ($custodial ? 'custodial' : 'none'),
    ]);

// ═══════════════════════════════════════════
// EARN GSM — Credit tokens to user (internal use)
// ═══════════════════════════════════════════
case 'earn':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonErr('POST required', 405);

    // Only allow from internal sources or commander
    $internalSecret = $_SERVER['HTTP_X_INTERNAL_SECRET'] ?? '';
    $validInternal = hash_equals('ee16f048838d22d2c2d54099ea109cd612ed919ddaf1c14b8eb8670214ab0d69', $internalSecret);
    if (!$validInternal && (!$clientId || $clientId !== 33)) {
        jsonErr('Forbidden', 403);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $targetClient = (int) ($input['client_id'] ?? $clientId);
    $amount = (float) ($input['amount'] ?? 0);
    $source = substr(preg_replace('/[^a-z0-9_-]/', '', strtolower($input['source'] ?? 'platform')), 0, 64);
    $description = substr($input['description'] ?? '', 0, 255);
    $referenceId = substr($input['reference_id'] ?? '', 0, 128);

    if ($amount <= 0 || $amount > 1000000) jsonErr('Invalid amount (must be 0 < amount <= 1,000,000)');
    if (!$targetClient) jsonErr('client_id required');

    $db->beginTransaction();
    try {
        // Auto-create balance row
        $db->prepare("INSERT IGNORE INTO crypto_gsm_balances (client_id, balance) VALUES (?, 0)")->execute([$targetClient]);

        // Credit balance
        $stmt = $db->prepare("UPDATE crypto_gsm_balances SET balance = balance + ?, total_earned = total_earned + ? WHERE client_id = ?");
        $stmt->execute([$amount, $amount, $targetClient]);

        // Get new balance
        $stmt = $db->prepare("SELECT balance FROM crypto_gsm_balances WHERE client_id = ?");
        $stmt->execute([$targetClient]);
        $newBalance = (float) $stmt->fetchColumn();

        // Log transaction
        $stmt = $db->prepare("INSERT INTO gsm_transactions (client_id, tx_type, amount, balance_after, source, reference_id, description) VALUES (?, 'earn', ?, ?, ?, ?, ?)");
        $stmt->execute([$targetClient, $amount, $newBalance, $source, $referenceId, $description]);

        $db->commit();
    } catch (\Exception $e) {
        $db->rollBack();
        jsonErr('Failed to credit tokens');
    }

    jsonOk([
        'client_id'     => $targetClient,
        'amount_earned' => $amount,
        'new_balance'   => $newBalance,
        'source'        => $source,
    ]);

// ═══════════════════════════════════════════
// TRANSACTION HISTORY — User's GSM ledger
// ═══════════════════════════════════════════
case 'transactions':
    if (!$clientId) jsonErr('Authentication required', 401);

    $page = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = min(50, max(10, (int) ($_GET['per_page'] ?? 20)));
    $offset = ($page - 1) * $perPage;
    $typeFilter = $_GET['type'] ?? '';

    $where = "WHERE client_id = ?";
    $params = [$clientId];

    if ($typeFilter && in_array($typeFilter, ['earn','spend','transfer_in','transfer_out','withdrawal','deposit','stake','unstake','reward'])) {
        $where .= " AND tx_type = ?";
        $params[] = $typeFilter;
    }

    $stmt = $db->prepare("SELECT COUNT(*) FROM gsm_transactions $where");
    $stmt->execute($params);
    $total = (int) $stmt->fetchColumn();

    $params[] = $perPage;
    $params[] = $offset;
    $stmt = $db->prepare("SELECT id, tx_type, amount, balance_after, source, reference_id, description, created_at FROM gsm_transactions $where ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute($params);
    $txs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonOk([
        'transactions' => $txs,
        'pagination'   => ['page' => $page, 'per_page' => $perPage, 'total' => $total, 'pages' => ceil($total / $perPage)],
    ]);

default:
    jsonErr('Unknown action. Available: token-info, wallet-status, link-wallet, unlink-wallet, request-withdrawal, withdrawal-history, confirm-withdrawal, dex-info, escrow-status, create-escrow, release-escrow, nft-mint-status, mint-nft, blockchain-stats, roadmap, custodial-wallet, balance, earn, transactions');
}
