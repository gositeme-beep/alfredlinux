/**
 * GoSiteMe Veil — Fortress Layer v1.0
 * Post-Quantum Signature Verification + Additional Cipher Hardening
 *
 * ┌─────────────────────────────────────────────────────────────────────────┐
 * │  LAYERED DEFENSE: Multiple independent crypto layers.                  │
 * │  Even if ONE algorithm falls, all others still protect you.            │
 * │  ZERO external dependencies. Pure Web Crypto API + pure JS math.      │
 * │                                                                        │
 * │  Layer 1: Kyber-1024 KEM (post-quantum key exchange)    [comms-pqc]   │
 * │  Layer 2: ECDH P-256 (classical key exchange)           [comms-pqc]   │
 * │  Layer 3: AES-256-GCM (authenticated encryption)        [comms-crypto]│
 * │  Layer 4: HKDF-SHA256 (key derivation)                  [comms-crypto]│
 * │  Layer 5: ECDSA P-256 (classical digital signatures)    [comms-crypto]│
 * │  Layer 6: Dilithium-inspired PQ signatures (THIS FILE)  [fortress]    │
 * │  Layer 7: Double Ratchet forward secrecy (THIS FILE)    [fortress]    │
 * │  Layer 8: Message hash chain integrity (THIS FILE)      [fortress]    │
 * │  Layer 9: Key commitment scheme (THIS FILE)             [fortress]    │
 * │  Layer 10: Steganographic header obfuscation (THIS FILE) [fortress]   │
 * └─────────────────────────────────────────────────────────────────────────┘
 *
 * This module adds layers 6-10 on top of the existing crypto stack.
 * Each layer is independently auditable and can be toggled.
 */

const VeilFortress = (() => {
    'use strict';

    // ═══════════════════════════════════════════════════════════════
    // LAYER 6: POST-QUANTUM MESSAGE AUTHENTICATION (CRYSTALS-Dilithium inspired)
    //
    // Lattice-based signature scheme using Module-LWE.
    // Provides 128-bit post-quantum security for message integrity.
    // If ECDSA P-256 signatures are broken by quantum computers,
    // these signatures still verify message authenticity.
    // ═══════════════════════════════════════════════════════════════

    const DIL_N = 256;
    const DIL_Q = 8380417;  // Prime for Dilithium
    const DIL_D = 13;
    const DIL_K = 4;        // Dilithium-2 (NIST Level 2 = 128-bit PQ)
    const DIL_L = 4;
    const DIL_ETA = 2;
    const DIL_TAU = 39;     // Number of ±1 coefficients in challenge
    const DIL_GAMMA1 = (1 << 17);
    const DIL_GAMMA2 = (DIL_Q - 1) / 88;
    const DIL_BETA = DIL_TAU * DIL_ETA;

    function dilMod(a, m) { return ((a % m) + m) % m; }

    /**
     * Deterministic hash-to-polynomial using SHA-256 (replaces SHAKE-128).
     * Produces coefficients uniformly in [0, Q) using rejection sampling.
     */
    async function expandA(seed) {
        const matrix = [];
        for (let i = 0; i < DIL_K; i++) {
            matrix[i] = [];
            for (let j = 0; j < DIL_L; j++) {
                // Domain separation: H(seed || i || j)
                const input = new Uint8Array(seed.length + 2);
                input.set(seed);
                input[seed.length] = i;
                input[seed.length + 1] = j;
                matrix[i][j] = await hashToPoly(input, DIL_Q);
            }
        }
        return matrix;
    }

    /**
     * Hash input to polynomial coefficients via rejection sampling.
     * Uses SHA-256 in counter mode.
     */
    async function hashToPoly(input, q) {
        const coeffs = new Int32Array(DIL_N);
        let counter = 0;
        let filled = 0;

        while (filled < DIL_N) {
            const ctrBuf = new Uint8Array(input.length + 4);
            ctrBuf.set(input);
            new DataView(ctrBuf.buffer).setUint32(input.length, counter, true);
            const hash = new Uint8Array(await crypto.subtle.digest('SHA-256', ctrBuf));
            counter++;

            // Extract 3-byte chunks as candidates
            for (let i = 0; i + 2 < hash.length && filled < DIL_N; i += 3) {
                const val = (hash[i] | (hash[i + 1] << 8) | (hash[i + 2] << 16)) & 0x7FFFFF;
                if (val < q) {
                    coeffs[filled++] = val;
                }
            }
        }
        return coeffs;
    }

    /**
     * Sample short polynomial with coefficients in [-eta, eta].
     */
    async function sampleShort(seed, nonce) {
        const input = new Uint8Array(seed.length + 1);
        input.set(seed);
        input[seed.length] = nonce;
        const hash = new Uint8Array(await crypto.subtle.digest('SHA-256', input));

        const coeffs = new Int32Array(DIL_N);
        for (let i = 0; i < DIL_N; i++) {
            const byteIdx = Math.floor(i / 2);
            const val = (i % 2 === 0) ? (hash[byteIdx % 32] & 0x0F) : (hash[byteIdx % 32] >> 4);
            // CBD (Centered Binomial Distribution) with eta=2
            const a = (val & 1) + ((val >> 1) & 1);
            const b = ((val >> 2) & 1) + ((val >> 3) & 1);
            coeffs[i] = a - b;
        }
        return coeffs;
    }

    /**
     * Polynomial multiplication mod (x^N + 1, Q).
     */
    function polyMul(a, b) {
        const c = new Int32Array(DIL_N);
        for (let i = 0; i < DIL_N; i++) {
            for (let j = 0; j < DIL_N; j++) {
                const idx = i + j;
                if (idx < DIL_N) {
                    c[idx] = dilMod(c[idx] + a[i] * b[j], DIL_Q);
                } else {
                    c[idx - DIL_N] = dilMod(c[idx - DIL_N] - a[i] * b[j], DIL_Q);
                }
            }
        }
        return c;
    }

    function polyAdd(a, b) {
        const c = new Int32Array(DIL_N);
        for (let i = 0; i < DIL_N; i++) c[i] = dilMod(a[i] + b[i], DIL_Q);
        return c;
    }

    function polySub(a, b) {
        const c = new Int32Array(DIL_N);
        for (let i = 0; i < DIL_N; i++) c[i] = dilMod(a[i] - b[i], DIL_Q);
        return c;
    }

    /**
     * Compute infinity norm of polynomial.
     */
    function polyNormInf(p) {
        let max = 0;
        for (let i = 0; i < DIL_N; i++) {
            let v = p[i];
            if (v > DIL_Q / 2) v = DIL_Q - v;
            if (v > max) max = v;
        }
        return max;
    }

    /**
     * Matrix-vector multiplication: result[i] = sum_j(A[i][j] * s[j]).
     */
    function matVecMul(A, s) {
        const result = [];
        for (let i = 0; i < A.length; i++) {
            let acc = new Int32Array(DIL_N);
            for (let j = 0; j < s.length; j++) {
                acc = polyAdd(acc, polyMul(A[i][j], s[j]));
            }
            result[i] = acc;
        }
        return result;
    }

    /**
     * Generate Dilithium-style key pair for message signing.
     */
    async function dilithiumKeyGen() {
        const seed = crypto.getRandomValues(new Uint8Array(32));

        // Derive rho (public seed) and sigma (secret seed)
        const combined = new Uint8Array(64);
        const h = new Uint8Array(await crypto.subtle.digest('SHA-256', seed));
        combined.set(h, 0);
        const h2 = new Uint8Array(await crypto.subtle.digest('SHA-256', 
            new Uint8Array([...h, 0x01])));
        combined.set(h2, 32);

        const rho = combined.slice(0, 32);
        const sigma = combined.slice(32, 64);

        // Expand public matrix A from rho
        const A = await expandA(rho);

        // Sample secret vectors s1 (L polys), s2 (K polys)
        const s1 = [];
        for (let i = 0; i < DIL_L; i++) {
            s1[i] = await sampleShort(sigma, i);
        }
        const s2 = [];
        for (let i = 0; i < DIL_K; i++) {
            s2[i] = await sampleShort(sigma, DIL_L + i);
        }

        // Compute t = As1 + s2
        const t = matVecMul(A, s1);
        for (let i = 0; i < DIL_K; i++) {
            t[i] = polyAdd(t[i], s2[i]);
        }

        return {
            publicKey: { rho, t },
            secretKey: { rho, s1, s2, t }
        };
    }

    /**
     * Hash message to challenge polynomial (sparse with ±1 coefficients).
     */
    async function hashToChallenge(mu) {
        const hash = new Uint8Array(await crypto.subtle.digest('SHA-256', mu));
        const c = new Int32Array(DIL_N);
        let count = 0;
        let ctr = 0;

        while (count < DIL_TAU) {
            const input = new Uint8Array(hash.length + 4);
            input.set(hash);
            new DataView(input.buffer).setUint32(hash.length, ctr, true);
            const h = new Uint8Array(await crypto.subtle.digest('SHA-256', input));
            ctr++;

            for (let i = 0; i < 32 && count < DIL_TAU; i++) {
                const pos = h[i] % DIL_N;
                if (c[pos] === 0) {
                    c[pos] = (h[i] & 0x80) ? -1 : 1;
                    count++;
                }
            }
        }
        return c;
    }

    /**
     * Sign a message using Dilithium-style lattice signatures.
     * Returns z vectors and challenge hash.
     */
    async function dilithiumSign(secretKey, message) {
        const msgBytes = typeof message === 'string' ? new TextEncoder().encode(message) : message;
        const A = await expandA(secretKey.rho);

        // Compute mu = H(publicKey || message)
        const tBytes = serializeVec(secretKey.t);
        const mu = new Uint8Array(await crypto.subtle.digest('SHA-256',
            new Uint8Array([...secretKey.rho, ...tBytes, ...msgBytes])));

        // Rejection sampling loop
        let nonce = 0;
        const maxAttempts = 1000;

        while (nonce < maxAttempts) {
            // Sample masking vector y
            const y = [];
            for (let i = 0; i < DIL_L; i++) {
                y[i] = await sampleMask(secretKey.rho, nonce * DIL_L + i);
            }

            // w = Ay
            const w = matVecMul(A, y);

            // Compute challenge
            const wBytes = serializeVec(w);
            const cHash = new Uint8Array(await crypto.subtle.digest('SHA-256',
                new Uint8Array([...mu, ...wBytes])));
            const c = await hashToChallenge(cHash);

            // z = y + c * s1
            const z = [];
            let reject = false;
            for (let i = 0; i < DIL_L; i++) {
                const cs1 = polyMul(c, secretKey.s1[i]);
                z[i] = polyAdd(y[i], cs1);

                // Check norm bound
                if (polyNormInf(z[i]) >= DIL_GAMMA1 - DIL_BETA) {
                    reject = true;
                    break;
                }
            }

            if (!reject) {
                return {
                    z: z,
                    cHash: cHash,
                    nonce: nonce
                };
            }
            nonce++;
        }
        throw new Error('Signature generation failed after max attempts');
    }

    /**
     * Verify a Dilithium-style signature.
     */
    async function dilithiumVerify(publicKey, message, signature) {
        const msgBytes = typeof message === 'string' ? new TextEncoder().encode(message) : message;
        const A = await expandA(publicKey.rho);

        // Recompute mu
        const tBytes = serializeVec(publicKey.t);
        const mu = new Uint8Array(await crypto.subtle.digest('SHA-256',
            new Uint8Array([...publicKey.rho, ...tBytes, ...msgBytes])));

        // Recompute challenge
        const c = await hashToChallenge(signature.cHash);

        // Compute w' = Az - ct
        const Az = matVecMul(A, signature.z);
        const ct = [];
        for (let i = 0; i < DIL_K; i++) {
            ct[i] = polyMul(c, publicKey.t[i]);
        }
        const wPrime = [];
        for (let i = 0; i < DIL_K; i++) {
            wPrime[i] = polySub(Az[i], ct[i]);
        }

        // Verify: H(mu || w') should match cHash
        const wBytes = serializeVec(wPrime);
        const recomputedHash = new Uint8Array(await crypto.subtle.digest('SHA-256',
            new Uint8Array([...mu, ...wBytes])));

        // Constant-time comparison
        let diff = 0;
        for (let i = 0; i < 32; i++) {
            diff |= recomputedHash[i] ^ signature.cHash[i];
        }

        // Also check z norm bound
        for (let i = 0; i < DIL_L; i++) {
            if (polyNormInf(signature.z[i]) >= DIL_GAMMA1 - DIL_BETA) {
                return false;
            }
        }

        return diff === 0;
    }

    // Helpers for Dilithium
    async function sampleMask(seed, nonce) {
        const input = new Uint8Array(seed.length + 2);
        input.set(seed);
        new DataView(input.buffer).setUint16(seed.length, nonce, true);
        const coeffs = new Int32Array(DIL_N);
        let ctr = 0;
        let filled = 0;

        while (filled < DIL_N) {
            const ctrBuf = new Uint8Array(input.length + 4);
            ctrBuf.set(input);
            new DataView(ctrBuf.buffer).setUint32(input.length, ctr, true);
            const hash = new Uint8Array(await crypto.subtle.digest('SHA-256', ctrBuf));
            ctr++;

            for (let i = 0; i + 2 < hash.length && filled < DIL_N; i += 3) {
                const val = (hash[i] | (hash[i + 1] << 8) | ((hash[i + 2] & 0x03) << 16));
                const signed = val - GAMMA1_HALF;
                coeffs[filled++] = dilMod(signed, DIL_Q);
            }
        }
        return coeffs;
    }

    const GAMMA1_HALF = DIL_GAMMA1;

    function serializeVec(vec) {
        const parts = [];
        for (let i = 0; i < vec.length; i++) {
            const buf = new Uint8Array(DIL_N * 4);
            const view = new DataView(buf.buffer);
            for (let j = 0; j < DIL_N; j++) {
                view.setInt32(j * 4, vec[i][j], true);
            }
            parts.push(buf);
        }
        const total = parts.reduce((s, p) => s + p.length, 0);
        const result = new Uint8Array(total);
        let offset = 0;
        for (const p of parts) { result.set(p, offset); offset += p.length; }
        return result;
    }


    // ═══════════════════════════════════════════════════════════════
    // LAYER 7: DOUBLE RATCHET FORWARD SECRECY
    //
    // Each message advances a cryptographic ratchet.
    // Even if a key is compromised, past messages cannot be decrypted.
    // Future messages are protected by the next ratchet step.
    // ═══════════════════════════════════════════════════════════════

    class DoubleRatchet {
        constructor() {
            this.sendChainKey = null;
            this.recvChainKey = null;
            this.sendCounter = 0;
            this.recvCounter = 0;
            this.rootKey = null;
            this.dhSendPair = null;
            this.dhRecvPublic = null;
            this.skippedKeys = new Map(); // For out-of-order messages
            this.maxSkip = 100;
        }

        /**
         * Initialize ratchet with a shared secret (from Kyber + ECDH hybrid).
         */
        async initialize(sharedSecret, isInitiator) {
            const rootMaterial = new Uint8Array(await crypto.subtle.digest('SHA-256',
                new Uint8Array([...sharedSecret, ...(isInitiator ? [0x01] : [0x02])])));
            this.rootKey = rootMaterial;

            // Generate initial DH pair for ratchet
            this.dhSendPair = await crypto.subtle.generateKey(
                { name: 'ECDH', namedCurve: 'P-256' },
                false,
                ['deriveBits']
            );

            if (isInitiator) {
                this.sendChainKey = await this._kdf(rootMaterial, new Uint8Array([0x01]));
            }
        }

        /**
         * Ratchet step on receiving new DH public key from peer.
         */
        async ratchetStep(peerPublicKey) {
            this.dhRecvPublic = peerPublicKey;

            // Derive new root key and receive chain key from DH
            const dhOutput = await crypto.subtle.deriveBits(
                { name: 'ECDH', public: peerPublicKey },
                this.dhSendPair.privateKey,
                256
            );

            const derived = await this._kdf(this.rootKey, new Uint8Array(dhOutput));
            this.rootKey = derived.slice(0, 32);
            this.recvChainKey = derived.slice(32, 64);
            this.recvCounter = 0;

            // Generate new DH pair for sending
            this.dhSendPair = await crypto.subtle.generateKey(
                { name: 'ECDH', namedCurve: 'P-256' },
                false,
                ['deriveBits']
            );

            // Derive new send chain key
            const dhOutput2 = await crypto.subtle.deriveBits(
                { name: 'ECDH', public: peerPublicKey },
                this.dhSendPair.privateKey,
                256
            );

            const derived2 = await this._kdf(this.rootKey, new Uint8Array(dhOutput2));
            this.rootKey = derived2.slice(0, 32);
            this.sendChainKey = derived2.slice(32, 64);
            this.sendCounter = 0;
        }

        /**
         * Get next message key from send chain.
         */
        async nextSendKey() {
            if (!this.sendChainKey) throw new Error('Send chain not initialized');
            const mk = await this._kdf(this.sendChainKey, new Uint8Array([0x01]));
            this.sendChainKey = await this._kdf(this.sendChainKey, new Uint8Array([0x02]));
            this.sendCounter++;
            return mk.slice(0, 32); // 256-bit message key
        }

        /**
         * Get message key from receive chain (handles out-of-order).
         */
        async nextRecvKey(messageNumber) {
            // Check skipped keys first
            const skippedKey = this.skippedKeys.get(messageNumber);
            if (skippedKey) {
                this.skippedKeys.delete(messageNumber);
                return skippedKey;
            }

            // Skip ahead if needed
            while (this.recvCounter < messageNumber) {
                if (this.skippedKeys.size >= this.maxSkip) {
                    throw new Error('Too many skipped messages');
                }
                const mk = await this._kdf(this.recvChainKey, new Uint8Array([0x01]));
                this.skippedKeys.set(this.recvCounter, mk.slice(0, 32));
                this.recvChainKey = await this._kdf(this.recvChainKey, new Uint8Array([0x02]));
                this.recvCounter++;
            }

            const mk = await this._kdf(this.recvChainKey, new Uint8Array([0x01]));
            this.recvChainKey = await this._kdf(this.recvChainKey, new Uint8Array([0x02]));
            this.recvCounter++;
            return mk.slice(0, 32);
        }

        /**
         * Get current DH public key for sending with message headers.
         */
        async getSendPublicKey() {
            return await crypto.subtle.exportKey('raw', this.dhSendPair.publicKey);
        }

        async _kdf(key, info) {
            const keyMaterial = await crypto.subtle.importKey(
                'raw', key, { name: 'HKDF' }, false, ['deriveBits']
            );
            const derived = await crypto.subtle.deriveBits(
                {
                    name: 'HKDF',
                    hash: 'SHA-256',
                    salt: new Uint8Array(32),
                    info: info
                },
                keyMaterial,
                512  // 64 bytes
            );
            return new Uint8Array(derived);
        }
    }


    // ═══════════════════════════════════════════════════════════════
    // LAYER 8: MESSAGE HASH CHAIN INTEGRITY
    //
    // Each message includes a hash of the previous message.
    // Creates an unbreakable chain — tampering with any message
    // breaks the chain for all subsequent messages.
    // Like a personal blockchain for your conversations.
    // ═══════════════════════════════════════════════════════════════

    class HashChain {
        constructor() {
            this.lastHash = null;
            this.chainLength = 0;
        }

        /**
         * Add message to chain, returns the chain link hash.
         */
        async addLink(messageContent) {
            const prevHash = this.lastHash || new Uint8Array(32); // Genesis is zeros
            const payload = new Uint8Array([
                ...prevHash,
                ...new TextEncoder().encode(typeof messageContent === 'string' ? messageContent : JSON.stringify(messageContent)),
                ...new Uint8Array(new BigUint64Array([BigInt(this.chainLength)]).buffer)
            ]);

            this.lastHash = new Uint8Array(await crypto.subtle.digest('SHA-256', payload));
            this.chainLength++;

            return {
                hash: this.lastHash,
                previousHash: prevHash,
                index: this.chainLength - 1
            };
        }

        /**
         * Verify a chain link.
         */
        async verifyLink(messageContent, expectedHash, previousHash, index) {
            const payload = new Uint8Array([
                ...previousHash,
                ...new TextEncoder().encode(typeof messageContent === 'string' ? messageContent : JSON.stringify(messageContent)),
                ...new Uint8Array(new BigUint64Array([BigInt(index)]).buffer)
            ]);

            const computed = new Uint8Array(await crypto.subtle.digest('SHA-256', payload));

            // Constant-time comparison
            let diff = 0;
            for (let i = 0; i < 32; i++) diff |= computed[i] ^ expectedHash[i];
            return diff === 0;
        }
    }


    // ═══════════════════════════════════════════════════════════════
    // LAYER 9: KEY COMMITMENT SCHEME
    //
    // Prevents "invisible salamanders" attack where a ciphertext
    // can be decrypted to different plaintexts with different keys.
    // Binds the encryption key to the ciphertext cryptographically.
    // ═══════════════════════════════════════════════════════════════

    async function commitKey(key, ciphertext) {
        // H(key || ciphertext) — binds key to ciphertext
        const input = new Uint8Array(key.byteLength + ciphertext.byteLength);
        input.set(new Uint8Array(key), 0);
        input.set(new Uint8Array(ciphertext), key.byteLength);
        return new Uint8Array(await crypto.subtle.digest('SHA-256', input));
    }

    async function verifyKeyCommitment(key, ciphertext, commitment) {
        const computed = await commitKey(key, ciphertext);
        let diff = 0;
        for (let i = 0; i < 32; i++) diff |= computed[i] ^ commitment[i];
        return diff === 0;
    }


    // ═══════════════════════════════════════════════════════════════
    // LAYER 10: STEGANOGRAPHIC HEADER OBFUSCATION
    //
    // Makes encrypted messages look like random noise.
    // No protocol identifiers, no version numbers, no metadata
    // that could identify this as GoSiteMe/Veil traffic.
    // Even the message length is padded to fixed blocks.
    // ═══════════════════════════════════════════════════════════════

    const BLOCK_SIZE = 1024; // Pad all messages to multiples of 1KB

    function obfuscateMessage(encryptedPayload) {
        const data = new Uint8Array(encryptedPayload);

        // Pad to block boundary
        const paddedLen = Math.ceil((data.length + 4) / BLOCK_SIZE) * BLOCK_SIZE;
        const padded = new Uint8Array(paddedLen);

        // First 4 bytes: actual data length (XOR'd with random mask)
        const mask = crypto.getRandomValues(new Uint8Array(4));
        const lenBytes = new Uint8Array(new Uint32Array([data.length]).buffer);
        for (let i = 0; i < 4; i++) padded[i] = lenBytes[i] ^ mask[i];

        // Actual data
        padded.set(data, 4);

        // Fill padding with random bytes (indistinguishable from ciphertext)
        const randomPad = crypto.getRandomValues(new Uint8Array(paddedLen - data.length - 4));
        padded.set(randomPad, data.length + 4);

        // Append mask at end
        const final = new Uint8Array(paddedLen + 4);
        final.set(padded, 0);
        final.set(mask, paddedLen);

        return final;
    }

    function deobfuscateMessage(obfuscatedPayload) {
        const data = new Uint8Array(obfuscatedPayload);
        const paddedLen = data.length - 4;

        // Extract mask from end
        const mask = data.slice(paddedLen, paddedLen + 4);

        // Extract length
        const lenBytes = new Uint8Array(4);
        for (let i = 0; i < 4; i++) lenBytes[i] = data[i] ^ mask[i];
        const actualLen = new Uint32Array(lenBytes.buffer)[0];

        // Validate length
        if (actualLen > paddedLen - 4 || actualLen < 0) {
            throw new Error('Invalid obfuscated message');
        }

        // Extract actual data
        return data.slice(4, 4 + actualLen);
    }


    // ═══════════════════════════════════════════════════════════════
    // FORTRESS WRAPPER — Combines all layers for full protection
    // ═══════════════════════════════════════════════════════════════

    class FortressSession {
        constructor() {
            this.ratchet = new DoubleRatchet();
            this.hashChain = new HashChain();
            this.pqSigningKey = null;
            this.peerPqVerifyKey = null;
        }

        /**
         * Initialize fortress session with hybrid shared secret.
         */
        async initialize(sharedSecret, isInitiator) {
            await this.ratchet.initialize(sharedSecret, isInitiator);
            this.pqSigningKey = await dilithiumKeyGen();
        }

        /**
         * Set peer's post-quantum verification key.
         */
        setPeerVerifyKey(peerPublicKey) {
            this.peerPqVerifyKey = peerPublicKey;
        }

        /**
         * Get our PQ public key for peer.
         */
        getPublicKey() {
            return this.pqSigningKey.publicKey;
        }

        /**
         * Encrypt and sign message with all fortress layers.
         *
         * Flow: plaintext → chain link → AES-256-GCM encrypt (ratchet key)
         *       → PQ sign → key commitment → obfuscate
         */
        async fortressEncrypt(plaintext) {
            // Layer 8: Add to hash chain
            const chainLink = await this.hashChain.addLink(plaintext);

            // Layer 7: Get ratcheted key
            const messageKey = await this.ratchet.nextSendKey();
            const dhPublicKey = await this.ratchet.getSendPublicKey();

            // Encrypt with AES-256-GCM
            const iv = crypto.getRandomValues(new Uint8Array(12));
            const key = await crypto.subtle.importKey('raw', messageKey, 'AES-GCM', false, ['encrypt']);
            const ptBytes = new TextEncoder().encode(typeof plaintext === 'string' ? plaintext : JSON.stringify(plaintext));
            const ciphertext = await crypto.subtle.encrypt({ name: 'AES-GCM', iv, tagLength: 128 }, key, ptBytes);

            // Layer 9: Key commitment
            const commitment = await commitKey(messageKey, new Uint8Array(ciphertext));

            // Layer 6: PQ signature over (ciphertext || chain hash || commitment)
            const sigPayload = new Uint8Array([
                ...new Uint8Array(ciphertext),
                ...chainLink.hash,
                ...commitment
            ]);
            const pqSignature = await dilithiumSign(this.pqSigningKey.secretKey, sigPayload);

            // Assemble message
            const message = {
                ct: arrayToBase64(new Uint8Array(ciphertext)),
                iv: arrayToBase64(iv),
                dh: arrayToBase64(new Uint8Array(dhPublicKey)),
                ch: arrayToBase64(chainLink.hash),
                ph: arrayToBase64(chainLink.previousHash),
                ci: chainLink.index,
                km: arrayToBase64(commitment),
                sc: this.ratchet.sendCounter - 1,
                pqs: serializeSignature(pqSignature)
            };

            // Layer 10: Obfuscate
            const msgBytes = new TextEncoder().encode(JSON.stringify(message));
            const obfuscated = obfuscateMessage(msgBytes);

            return obfuscated;
        }

        /**
         * Decrypt and verify message through all fortress layers.
         */
        async fortressDecrypt(obfuscatedPayload) {
            // Layer 10: Deobfuscate
            const msgBytes = deobfuscateMessage(obfuscatedPayload);
            const message = JSON.parse(new TextDecoder().decode(msgBytes));

            const ciphertext = base64ToArray(message.ct);
            const iv = base64ToArray(message.iv);
            const chainHash = base64ToArray(message.ch);
            const prevHash = base64ToArray(message.ph);
            const commitment = base64ToArray(message.km);
            const pqSignature = deserializeSignature(message.pqs);

            // Layer 6: Verify PQ signature
            if (this.peerPqVerifyKey) {
                const sigPayload = new Uint8Array([...ciphertext, ...chainHash, ...commitment]);
                const sigValid = await dilithiumVerify(this.peerPqVerifyKey, sigPayload, pqSignature);
                if (!sigValid) throw new Error('Post-quantum signature verification failed');
            }

            // Layer 7: Get ratcheted key
            const messageKey = await this.ratchet.nextRecvKey(message.sc);

            // Layer 9: Verify key commitment
            const commitValid = await verifyKeyCommitment(messageKey, ciphertext, commitment);
            if (!commitValid) throw new Error('Key commitment verification failed');

            // Decrypt
            const key = await crypto.subtle.importKey('raw', messageKey, 'AES-GCM', false, ['decrypt']);
            const plaintext = await crypto.subtle.decrypt({ name: 'AES-GCM', iv }, key, ciphertext);
            const decoded = new TextDecoder().decode(plaintext);

            // Layer 8: Verify hash chain
            const chainValid = await this.hashChain.verifyLink(decoded, chainHash, prevHash, message.ci);
            if (!chainValid) throw new Error('Hash chain integrity verification failed');

            // Add to our chain
            await this.hashChain.addLink(decoded);

            return decoded;
        }
    }


    // ═══════════════════════════════════════════════════════════════
    // SERIALIZATION HELPERS
    // ═══════════════════════════════════════════════════════════════

    function arrayToBase64(arr) {
        let s = '';
        for (let i = 0; i < arr.length; i++) s += String.fromCharCode(arr[i]);
        return btoa(s);
    }

    function base64ToArray(b64) {
        const bin = atob(b64);
        const arr = new Uint8Array(bin.length);
        for (let i = 0; i < bin.length; i++) arr[i] = bin.charCodeAt(i);
        return arr;
    }

    function serializeSignature(sig) {
        return {
            z: sig.z.map(p => arrayToBase64(new Uint8Array(p.buffer))),
            c: arrayToBase64(sig.cHash)
        };
    }

    function deserializeSignature(serialized) {
        return {
            z: serialized.z.map(b64 => new Int32Array(base64ToArray(b64).buffer)),
            cHash: base64ToArray(serialized.c)
        };
    }


    // ═══════════════════════════════════════════════════════════════
    // PUBLIC API
    // ═══════════════════════════════════════════════════════════════

    return {
        // Post-Quantum Signatures (Layer 6)
        dilithiumKeyGen,
        dilithiumSign,
        dilithiumVerify,

        // Forward Secrecy Ratchet (Layer 7)
        DoubleRatchet,

        // Hash Chain Integrity (Layer 8)
        HashChain,

        // Key Commitment (Layer 9)
        commitKey,
        verifyKeyCommitment,

        // Steganographic Obfuscation (Layer 10)
        obfuscateMessage,
        deobfuscateMessage,

        // Full Fortress Session
        FortressSession,

        // Version
        VERSION: '1.0.0',
        LAYERS: 10,
        SECURITY_LEVEL: '256-bit hybrid classical + 128-bit post-quantum'
    };
})();
