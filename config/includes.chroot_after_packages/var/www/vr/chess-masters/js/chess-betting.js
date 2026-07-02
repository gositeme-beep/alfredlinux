/* ═══════════════════════════════════════════════════════════════
   CHESS MASTERS — Betting Client Module
   GSM Alfred OS · Project Grandmaster II
   
   Client-side betting integration:
   - USD wagers via Stripe Elements
   - SOL wagers via Phantom/Solflare wallets
   - Real-time wager state management
   - Balance tracking and history
   ═══════════════════════════════════════════════════════════════ */

const ChessBetting = (() => {
    'use strict';

    const API_BASE = '/api/chess-betting.php';
    const VALID_AMOUNTS_USD = [100, 300, 500, 1000, 2500]; // cents
    const AMOUNT_LABELS = { 100: '$1', 300: '$3', 500: '$5', 1000: '$10', 2500: '$25' };

    let stripe = null;
    let stripeElements = null;
    let cardElement = null;
    let solanaWallet = null;
    let activeWager = null;
    let balance = null;
    let onStateChange = null;

    // ─────────────────────────────────────────────────────
    // API CALLS
    // ─────────────────────────────────────────────────────

    async function apiCall(action, data = {}) {
        const res = await fetch(`${API_BASE}?action=${encodeURIComponent(action)}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify(data),
        });
        return res.json();
    }

    async function apiGet(action, params = {}) {
        const qs = new URLSearchParams({ action, ...params });
        const res = await fetch(`${API_BASE}?${qs}`, {
            credentials: 'same-origin',
        });
        return res.json();
    }

    // ─────────────────────────────────────────────────────
    // INIT
    // ─────────────────────────────────────────────────────

    async function init(options = {}) {
        onStateChange = options.onStateChange || null;

        // Load Stripe.js
        if (!window.Stripe && options.stripeKey) {
            await loadScript('https://js.stripe.com/v3/');
        }
        if (window.Stripe && options.stripeKey) {
            stripe = Stripe(options.stripeKey);
            stripeElements = stripe.elements();
        }

        // Check for Solana wallet
        detectSolanaWallet();

        // Load existing state
        await refreshBalance();
        await checkActiveWager();

        return { balance, activeWager, hasSolana: !!solanaWallet };
    }

    function loadScript(src) {
        return new Promise((resolve, reject) => {
            if (document.querySelector(`script[src="${src}"]`)) { resolve(); return; }
            const s = document.createElement('script');
            s.src = src;
            s.onload = resolve;
            s.onerror = reject;
            document.head.appendChild(s);
        });
    }

    // ─────────────────────────────────────────────────────
    // SOLANA WALLET DETECTION
    // ─────────────────────────────────────────────────────

    function detectSolanaWallet() {
        if (window.solana?.isPhantom) {
            solanaWallet = { provider: window.solana, name: 'Phantom', icon: '👻' };
        } else if (window.solflare?.isSolflare) {
            solanaWallet = { provider: window.solflare, name: 'Solflare', icon: '☀️' };
        } else if (window.backpack?.isBackpack) {
            solanaWallet = { provider: window.backpack, name: 'Backpack', icon: '🎒' };
        }
        return solanaWallet;
    }

    async function connectSolanaWallet() {
        if (!solanaWallet) {
            detectSolanaWallet();
            if (!solanaWallet) return null;
        }
        try {
            const resp = await solanaWallet.provider.connect();
            solanaWallet.publicKey = resp.publicKey.toString();
            return solanaWallet;
        } catch (e) {
            console.error('Wallet connect failed:', e);
            return null;
        }
    }

    // ─────────────────────────────────────────────────────
    // WAGER CREATION
    // ─────────────────────────────────────────────────────

    async function createWager(options) {
        const { amount, currency = 'usd', gameMode = 'ai', side = 'random',
                aiPersonality = null, aiDifficulty = 'medium' } = options;

        const result = await apiCall('create-wager', {
            amount, currency, game_mode: gameMode, side,
            ai_personality: aiPersonality, ai_difficulty: aiDifficulty,
        });

        if (!result.success) return result;

        activeWager = {
            id: result.wager_id,
            matchId: result.match_id,
            amount: result.amount,
            currency: result.currency,
            status: 'pending',
            payment: result.payment,
        };

        fireStateChange('wager-created', activeWager);
        return result;
    }

    // ─────────────────────────────────────────────────────
    // PAYMENT PROCESSING
    // ─────────────────────────────────────────────────────

    async function payWithStripe(cardElement) {
        if (!stripe || !activeWager?.payment?.client_secret) {
            return { error: 'Stripe not initialized or no active wager' };
        }

        const { error, paymentIntent } = await stripe.confirmCardPayment(
            activeWager.payment.client_secret,
            { payment_method: { card: cardElement } }
        );

        if (error) return { error: error.message };

        // Confirm wager on server
        const confirm = await apiCall('confirm-wager', {
            match_id: activeWager.matchId,
        });

        if (confirm.success) {
            activeWager.status = 'active';
            fireStateChange('wager-active', activeWager);
        }

        return confirm;
    }

    async function payWithSolana() {
        if (!solanaWallet?.provider || !activeWager) {
            return { error: 'Solana wallet not connected or no active wager' };
        }

        try {
            const wallet = solanaWallet.provider;
            if (!wallet.publicKey) await wallet.connect();

            // Create SOL transfer transaction
            const lamports = activeWager.amount;

            // We use the wallet's signAndSendTransaction
            // The actual transaction is created on the client since we need wallet signing
            const { Connection, PublicKey, Transaction, SystemProgram } =
                window.solanaWeb3 || {};

            if (!Connection) {
                return { error: 'Solana Web3 library not loaded' };
            }

            const connection = new Connection('https://api.mainnet-beta.solana.com');
            const tx = new Transaction().add(
                SystemProgram.transfer({
                    fromPubkey: wallet.publicKey,
                    toPubkey: new PublicKey(activeWager.treasury || ''),
                    lamports,
                })
            );

            tx.feePayer = wallet.publicKey;
            tx.recentBlockhash = (await connection.getLatestBlockhash()).blockhash;

            const signed = await wallet.signTransaction(tx);
            const signature = await connection.sendRawTransaction(signed.serialize());
            await connection.confirmTransaction(signature);

            // Confirm wager on server with tx signature
            const confirm = await apiCall('confirm-wager', {
                match_id: activeWager.matchId,
                tx_signature: signature,
            });

            if (confirm.success) {
                activeWager.status = 'active';
                fireStateChange('wager-active', activeWager);
            }

            return confirm;
        } catch (e) {
            return { error: e.message || 'SOL payment failed' };
        }
    }

    // ─────────────────────────────────────────────────────
    // SETTLEMENT
    // ─────────────────────────────────────────────────────

    async function settle(result, moveCount, pgn = null) {
        if (!activeWager) return { error: 'No active wager' };

        const res = await apiCall('settle-wager', {
            match_id: activeWager.matchId,
            result,
            move_count: moveCount,
            pgn,
        });

        if (res.success) {
            const settled = { ...activeWager, ...res };
            activeWager = null;
            await refreshBalance();
            fireStateChange('wager-settled', settled);
        }

        return res;
    }

    async function cancel() {
        if (!activeWager) return { error: 'No active wager' };

        const res = await apiCall('cancel-wager', {
            match_id: activeWager.matchId,
        });

        if (res.success) {
            activeWager = null;
            fireStateChange('wager-cancelled');
        }

        return res;
    }

    // ─────────────────────────────────────────────────────
    // BALANCE & HISTORY
    // ─────────────────────────────────────────────────────

    async function refreshBalance() {
        const res = await apiGet('get-balance');
        if (res.success) balance = res.balance;
        return balance;
    }

    async function getHistory(limit = 20) {
        const res = await apiGet('get-wagers', { limit });
        return res.success ? res.wagers : [];
    }

    async function checkActiveWager() {
        const res = await apiGet('get-active');
        if (res.success && res.wager) {
            activeWager = {
                id: res.wager.id,
                matchId: res.wager.match_id,
                amount: res.wager.amount,
                currency: res.wager.currency,
                status: res.wager.status,
            };
        }
        return activeWager;
    }

    // ─────────────────────────────────────────────────────
    // STRIPE CARD ELEMENT
    // ─────────────────────────────────────────────────────

    function createCardElement(container) {
        if (!stripeElements) return null;

        cardElement = stripeElements.create('card', {
            style: {
                base: {
                    color: '#e8d5b0',
                    fontFamily: '"Playfair Display", serif',
                    fontSize: '16px',
                    '::placeholder': { color: '#8a7b6b' },
                },
                invalid: { color: '#ff6b6b' },
            },
        });

        if (typeof container === 'string') {
            container = document.querySelector(container);
        }
        if (container) cardElement.mount(container);

        return cardElement;
    }

    // ─────────────────────────────────────────────────────
    // UI HELPERS
    // ─────────────────────────────────────────────────────

    function formatAmount(amount, currency) {
        if (currency === 'usd') return '$' + (amount / 100).toFixed(2);
        if (currency === 'sol') return (amount / 1e9).toFixed(4) + ' SOL';
        return amount;
    }

    function getAmountOptions(currency = 'usd') {
        if (currency === 'usd') {
            return VALID_AMOUNTS_USD.map(a => ({
                value: a,
                label: AMOUNT_LABELS[a] || formatAmount(a, 'usd'),
            }));
        }
        return [
            { value: 10000000, label: '0.01 SOL' },
            { value: 50000000, label: '0.05 SOL' },
            { value: 100000000, label: '0.1 SOL' },
            { value: 500000000, label: '0.5 SOL' },
            { value: 1000000000, label: '1 SOL' },
        ];
    }

    function fireStateChange(event, data = null) {
        if (onStateChange) onStateChange(event, data);
    }

    // ─────────────────────────────────────────────────────
    // PUBLIC API
    // ─────────────────────────────────────────────────────

    return {
        init,
        createWager,
        payWithStripe,
        payWithSolana,
        connectSolanaWallet,
        settle,
        cancel,
        refreshBalance,
        getHistory,
        checkActiveWager,
        createCardElement,
        formatAmount,
        getAmountOptions,
        get activeWager() { return activeWager; },
        get balance() { return balance; },
        get hasSolana() { return !!solanaWallet; },
        get solanaWallet() { return solanaWallet; },
        get stripe() { return stripe; },
    };
})();
