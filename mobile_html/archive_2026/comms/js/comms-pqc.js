/**
 * GoSiteMe Veil — Post-Quantum Cryptography Module
 * Kyber-1024 (90s variant) + ECDH Hybrid Key Exchange
 *
 * Uses Web Crypto API exclusively — zero external dependencies.
 * 90s variant: AES-256-CTR for XOF/PRF, SHA-256/SHA-512 for hash.
 * Hybrid: ECDH P-256 (classical) + Kyber-1024 (post-quantum) → HKDF combiner.
 *
 * Even if Kyber is broken, classical ECDH still protects you.
 * Even if ECDH is broken by quantum computers, Kyber still protects you.
 * Both must be broken simultaneously to compromise a session.
 */

const CommsPQC = (() => {
    'use strict';

    // ══════════════════════════════════════════════════════════════
    // KYBER-1024 PARAMETERS
    // ══════════════════════════════════════════════════════════════
    const N  = 256;
    const Q  = 3329;
    const K  = 4;        // Kyber-1024
    const ETA1 = 2;
    const ETA2 = 2;
    const DU = 11;
    const DV = 5;
    const POLY_BYTES    = 384;                   // N * 12 / 8
    const POLYVEC_BYTES = K * POLY_BYTES;        // 1536
    const PK_BYTES      = POLYVEC_BYTES + 32;    // 1568
    const SK_BYTES      = POLYVEC_BYTES + PK_BYTES + 64; // 3168
    const CT_BYTES      = K * N * DU / 8 + N * DV / 8;  // 1088

    // ══════════════════════════════════════════════════════════════
    // MODULAR ARITHMETIC
    // ══════════════════════════════════════════════════════════════
    function mod(a, m) { return ((a % m) + m) % m; }

    // ══════════════════════════════════════════════════════════════
    // NTT ZETAS (computed from primitive root ζ=17 in Z_q)
    // ══════════════════════════════════════════════════════════════
    const ZETAS = (() => {
        const z = new Array(128);
        function bitrev7(x) {
            let r = 0;
            for (let i = 0; i < 7; i++) { r = (r << 1) | (x & 1); x >>= 1; }
            return r;
        }
        function powmod(b, e, m) {
            let r = 1; b = b % m;
            while (e > 0) { if (e & 1) r = r * b % m; e >>= 1; b = b * b % m; }
            return r;
        }
        for (let i = 0; i < 128; i++) z[i] = powmod(17, bitrev7(i), Q);
        return z;
    })();

    // ══════════════════════════════════════════════════════════════
    // HASH FUNCTIONS (90s variant — Web Crypto API)
    // ══════════════════════════════════════════════════════════════

    async function G(input) {
        return new Uint8Array(await crypto.subtle.digest('SHA-512', input));
    }

    async function H(input) {
        return new Uint8Array(await crypto.subtle.digest('SHA-256', input));
    }

    async function PRF(key32, nonce, outLen) {
        const k = await crypto.subtle.importKey('raw', key32, { name: 'AES-CTR' }, false, ['encrypt']);
        const ctr = new Uint8Array(16);
        ctr[0] = nonce;
        return new Uint8Array(await crypto.subtle.encrypt(
            { name: 'AES-CTR', counter: ctr, length: 128 }, k, new Uint8Array(outLen)
        ));
    }

    async function XOF(seed32, x, y, outLen) {
        const k = await crypto.subtle.importKey('raw', seed32, { name: 'AES-CTR' }, false, ['encrypt']);
        const ctr = new Uint8Array(16);
        ctr[0] = x; ctr[1] = y;
        return new Uint8Array(await crypto.subtle.encrypt(
            { name: 'AES-CTR', counter: ctr, length: 128 }, k, new Uint8Array(outLen)
        ));
    }

    // ══════════════════════════════════════════════════════════════
    // NTT (Number Theoretic Transform)
    // ══════════════════════════════════════════════════════════════

    function ntt(r) {
        let k = 1;
        for (let len = 128; len >= 2; len >>= 1) {
            for (let start = 0; start < N; start += 2 * len) {
                const z = ZETAS[k++];
                for (let j = start; j < start + len; j++) {
                    const t = mod(z * r[j + len], Q);
                    r[j + len] = mod(r[j] - t, Q);
                    r[j]       = mod(r[j] + t, Q);
                }
            }
        }
    }

    function invNtt(r) {
        let k = 127;
        for (let len = 2; len <= 128; len <<= 1) {
            for (let start = 0; start < N; start += 2 * len) {
                const z = ZETAS[k--];
                for (let j = start; j < start + len; j++) {
                    const t = r[j];
                    r[j]       = mod(t + r[j + len], Q);
                    r[j + len] = mod(z * mod(r[j + len] - t, Q), Q);
                }
            }
        }
        // Multiply by N^{-1} = 128^{-1} mod 3329 = 3303
        for (let j = 0; j < N; j++) r[j] = mod(r[j] * 3303, Q);
    }

    // ══════════════════════════════════════════════════════════════
    // BASE-CASE MULTIPLICATION (NTT domain — degree-1 residues)
    // ══════════════════════════════════════════════════════════════

    function polyBaseMul(a, b) {
        const r = new Array(N).fill(0);
        for (let i = 0; i < N / 4; i++) {
            const z = ZETAS[64 + i];
            // Pair 1: mod (X^2 - ζ)
            r[4*i]     = mod(mod(a[4*i+1] * b[4*i+1], Q) * z % Q + a[4*i] * b[4*i], Q);
            r[4*i + 1] = mod(a[4*i] * b[4*i+1] + a[4*i+1] * b[4*i], Q);
            // Pair 2: mod (X^2 + ζ)
            const nz = Q - z;
            r[4*i + 2] = mod(mod(a[4*i+3] * b[4*i+3], Q) * nz % Q + a[4*i+2] * b[4*i+2], Q);
            r[4*i + 3] = mod(a[4*i+2] * b[4*i+3] + a[4*i+3] * b[4*i+2], Q);
        }
        return r;
    }

    // ══════════════════════════════════════════════════════════════
    // POLYNOMIAL OPERATIONS
    // ══════════════════════════════════════════════════════════════

    function polyAdd(a, b) {
        const r = new Array(N);
        for (let i = 0; i < N; i++) r[i] = mod(a[i] + b[i], Q);
        return r;
    }

    function polySub(a, b) {
        const r = new Array(N);
        for (let i = 0; i < N; i++) r[i] = mod(a[i] - b[i], Q);
        return r;
    }

    function polyReduce(a) {
        for (let i = 0; i < N; i++) a[i] = mod(a[i], Q);
    }

    // CBD: Centered Binomial Distribution (η=2)
    function cbd(buf, eta) {
        const r = new Array(N).fill(0);
        if (eta === 2) {
            for (let i = 0; i < N / 8; i++) {
                const t = (buf[4*i]) | (buf[4*i+1] << 8) | (buf[4*i+2] << 16) | (buf[4*i+3] << 24);
                for (let j = 0; j < 8; j++) {
                    const a = ((t >>> (4*j)) & 1) + ((t >>> (4*j+1)) & 1);
                    const b = ((t >>> (4*j+2)) & 1) + ((t >>> (4*j+3)) & 1);
                    r[8*i + j] = mod(a - b, Q);
                }
            }
        }
        return r;
    }

    // ══════════════════════════════════════════════════════════════
    // SERIALIZATION
    // ══════════════════════════════════════════════════════════════

    // Polynomial → bytes (12 bits per coefficient)
    function polyToBytes(p) {
        const r = new Uint8Array(POLY_BYTES);
        for (let i = 0; i < N / 2; i++) {
            const t0 = mod(p[2*i], Q), t1 = mod(p[2*i+1], Q);
            r[3*i]     = t0 & 0xff;
            r[3*i + 1] = (t0 >> 8) | ((t1 & 0xf) << 4);
            r[3*i + 2] = t1 >> 4;
        }
        return r;
    }

    function polyFromBytes(bytes, off = 0) {
        const r = new Array(N);
        for (let i = 0; i < N / 2; i++) {
            r[2*i]     = (bytes[off + 3*i]) | ((bytes[off + 3*i+1] & 0x0f) << 8);
            r[2*i + 1] = (bytes[off + 3*i+1] >> 4) | (bytes[off + 3*i+2] << 4);
        }
        return r;
    }

    // Compress (reduce to d bits per coefficient)
    function polyCompress(p, d) {
        if (d === 4) {
            const r = new Uint8Array(N / 2);
            for (let i = 0; i < N / 2; i++) {
                const t0 = Math.round(mod(p[2*i], Q) * (1 << d) / Q) & 0xf;
                const t1 = Math.round(mod(p[2*i+1], Q) * (1 << d) / Q) & 0xf;
                r[i] = t0 | (t1 << 4);
            }
            return r;
        }
        // d === 10
        const r = new Uint8Array(N * d / 8);
        for (let i = 0; i < N / 4; i++) {
            const t = [];
            for (let j = 0; j < 4; j++) {
                t.push(Math.round(mod(p[4*i+j], Q) * 1024 / Q) & 0x3ff);
            }
            r[5*i]     = t[0] & 0xff;
            r[5*i + 1] = (t[0] >> 8) | ((t[1] & 0x3f) << 2);
            r[5*i + 2] = (t[1] >> 6) | ((t[2] & 0x0f) << 4);
            r[5*i + 3] = (t[2] >> 4) | ((t[3] & 0x03) << 6);
            r[5*i + 4] = t[3] >> 2;
        }
        return r;
    }

    function polyDecompress(bytes, d, off = 0) {
        const r = new Array(N);
        if (d === 4) {
            for (let i = 0; i < N / 2; i++) {
                r[2*i]     = Math.round((bytes[off + i] & 0x0f) * Q / 16);
                r[2*i + 1] = Math.round((bytes[off + i] >> 4) * Q / 16);
            }
            return r;
        }
        // d === 10
        for (let i = 0; i < N / 4; i++) {
            const b = off + 5*i;
            const t0 = bytes[b] | ((bytes[b+1] & 0x03) << 8);
            const t1 = (bytes[b+1] >> 2) | ((bytes[b+2] & 0x0f) << 6);
            const t2 = (bytes[b+2] >> 4) | ((bytes[b+3] & 0x3f) << 4);
            const t3 = (bytes[b+3] >> 6) | (bytes[b+4] << 2);
            r[4*i]     = Math.round(t0 * Q / 1024);
            r[4*i + 1] = Math.round(t1 * Q / 1024);
            r[4*i + 2] = Math.round(t2 * Q / 1024);
            r[4*i + 3] = Math.round(t3 * Q / 1024);
        }
        return r;
    }

    // ── PolyVec Serialization ──────────────────────────────────────

    function polyVecToBytes(v) {
        const r = new Uint8Array(POLYVEC_BYTES);
        for (let i = 0; i < K; i++) r.set(polyToBytes(v[i]), i * POLY_BYTES);
        return r;
    }

    function polyVecFromBytes(bytes, off = 0) {
        const v = [];
        for (let i = 0; i < K; i++) v.push(polyFromBytes(bytes, off + i * POLY_BYTES));
        return v;
    }

    function polyVecNtt(v)    { for (let i = 0; i < K; i++) ntt(v[i]); }
    function polyVecInvNtt(v) { for (let i = 0; i < K; i++) invNtt(v[i]); }
    function polyVecReduce(v) { for (let i = 0; i < K; i++) polyReduce(v[i]); }
    function polyVecAdd(a, b) { return a.map((_, i) => polyAdd(a[i], b[i])); }

    function polyVecCompress(v, d) {
        const perPoly = N * d / 8;
        const r = new Uint8Array(K * perPoly);
        for (let i = 0; i < K; i++) r.set(polyCompress(v[i], d), i * perPoly);
        return r;
    }

    function polyVecDecompress(bytes, d, off = 0) {
        const perPoly = N * d / 8;
        const v = [];
        for (let i = 0; i < K; i++) v.push(polyDecompress(bytes, d, off + i * perPoly));
        return v;
    }

    // ══════════════════════════════════════════════════════════════
    // MATRIX GENERATION (rejection sampling via AES-CTR XOF)
    // ══════════════════════════════════════════════════════════════

    async function genMatrix(rho, transposed) {
        const matrix = [];
        for (let i = 0; i < K; i++) {
            matrix.push([]);
            for (let j = 0; j < K; j++) {
                const x = transposed ? j : i;
                const y = transposed ? i : j;
                const buf = await XOF(rho, x, y, N * 4);
                const poly = new Array(N);
                let ctr = 0, pos = 0;
                while (ctr < N && pos + 2 < buf.length) {
                    const d1 = buf[pos] | ((buf[pos+1] & 0x0f) << 8);
                    const d2 = (buf[pos+1] >> 4) | (buf[pos+2] << 4);
                    pos += 3;
                    if (d1 < Q && ctr < N) poly[ctr++] = d1;
                    if (d2 < Q && ctr < N) poly[ctr++] = d2;
                }
                while (ctr < N) poly[ctr++] = 0;
                matrix[i].push(poly);
            }
        }
        return matrix;
    }

    // ══════════════════════════════════════════════════════════════
    // KYBER CPA-PKE
    // ══════════════════════════════════════════════════════════════

    async function cpakeygen() {
        const d = crypto.getRandomValues(new Uint8Array(32));
        const g = await G(d);
        const rho = g.slice(0, 32), sigma = g.slice(32, 64);

        const A = await genMatrix(rho, false);

        // Sample secret s and error e
        const s = [], e = [];
        for (let i = 0; i < K; i++) {
            s.push(cbd(await PRF(sigma, i, 64 * ETA1), ETA1));
            e.push(cbd(await PRF(sigma, K + i, 64 * ETA1), ETA1));
        }

        polyVecNtt(s);
        polyVecNtt(e);

        // t̂ = A · ŝ + ê  (NTT domain)
        const t_hat = [];
        for (let i = 0; i < K; i++) {
            let acc = polyBaseMul(A[i][0], s[0]);
            for (let j = 1; j < K; j++) acc = polyAdd(acc, polyBaseMul(A[i][j], s[j]));
            t_hat.push(polyAdd(acc, e[i]));
        }
        polyVecReduce(t_hat);

        const pk = new Uint8Array(PK_BYTES);
        pk.set(polyVecToBytes(t_hat), 0);
        pk.set(rho, POLYVEC_BYTES);

        return { pk, sk: polyVecToBytes(s) };
    }

    async function cpaencrypt(pk, msg32, coins32) {
        const t_hat = polyVecFromBytes(pk, 0);
        const rho = pk.slice(POLYVEC_BYTES, POLYVEC_BYTES + 32);
        const AT = await genMatrix(rho, true);

        const r = [], e1 = [];
        for (let i = 0; i < K; i++) {
            r.push(cbd(await PRF(coins32, i, 64 * ETA1), ETA1));
            e1.push(cbd(await PRF(coins32, K + i, 64 * ETA2), ETA2));
        }
        const e2 = cbd(await PRF(coins32, 2 * K, 64 * ETA2), ETA2);

        polyVecNtt(r);

        // u = NTT⁻¹(Aᵀ · r̂) + e₁
        const u = [];
        for (let i = 0; i < K; i++) {
            let acc = polyBaseMul(AT[i][0], r[0]);
            for (let j = 1; j < K; j++) acc = polyAdd(acc, polyBaseMul(AT[i][j], r[j]));
            invNtt(acc);
            u.push(polyAdd(acc, e1[i]));
        }

        // v = NTT⁻¹(t̂ᵀ · r̂) + e₂ + ⌈q/2⌋·m
        let v = polyBaseMul(t_hat[0], r[0]);
        for (let i = 1; i < K; i++) v = polyAdd(v, polyBaseMul(t_hat[i], r[i]));
        invNtt(v);
        v = polyAdd(v, e2);

        const msgPoly = new Array(N).fill(0);
        for (let i = 0; i < 32; i++) {
            for (let j = 0; j < 8; j++) {
                msgPoly[8*i + j] = ((msg32[i] >> j) & 1) * Math.round(Q / 2);
            }
        }
        v = polyAdd(v, msgPoly);

        const c1 = polyVecCompress(u, DU);
        const c2 = polyCompress(v, DV);
        const ct = new Uint8Array(CT_BYTES);
        ct.set(c1, 0);
        ct.set(c2, c1.length);
        return ct;
    }

    function cpadecrypt(sk, ct) {
        const s_hat = polyVecFromBytes(sk, 0);
        const c1len = K * N * DU / 8;
        const u = polyVecDecompress(ct, DU, 0);
        const v = polyDecompress(ct, DV, c1len);

        polyVecNtt(u);
        let inner = polyBaseMul(s_hat[0], u[0]);
        for (let i = 1; i < K; i++) inner = polyAdd(inner, polyBaseMul(s_hat[i], u[i]));
        invNtt(inner);

        const msg = polySub(v, inner);
        const msgBytes = new Uint8Array(32);
        for (let i = 0; i < 32; i++) {
            for (let j = 0; j < 8; j++) {
                const t = mod(msg[8*i + j], Q);
                if (t > Q / 4 && t < 3 * Q / 4) msgBytes[i] |= (1 << j);
            }
        }
        return msgBytes;
    }

    // ══════════════════════════════════════════════════════════════
    // KYBER KEM (IND-CCA2)
    // ══════════════════════════════════════════════════════════════

    async function kyberKeyGen() {
        const cpa = await cpakeygen();
        const z   = crypto.getRandomValues(new Uint8Array(32));
        const hpk = await H(cpa.pk);

        // sk = (sk_cpa ‖ pk ‖ H(pk) ‖ z)
        const sk = new Uint8Array(SK_BYTES);
        sk.set(cpa.sk, 0);
        sk.set(cpa.pk, POLYVEC_BYTES);
        sk.set(hpk, POLYVEC_BYTES + PK_BYTES);
        sk.set(z, POLYVEC_BYTES + PK_BYTES + 32);

        return { publicKey: cpa.pk, secretKey: sk };
    }

    async function kyberEncaps(pk) {
        const m  = crypto.getRandomValues(new Uint8Array(32));
        const mh = await H(m);
        const hpk = await H(pk);

        const combined = new Uint8Array(64);
        combined.set(mh, 0);
        combined.set(hpk, 32);
        const kr = await G(combined);

        const K_bar = kr.slice(0, 32);
        const coins = kr.slice(32, 64);
        const ct = await cpaencrypt(pk, mh, coins);

        const hct = await H(ct);
        const kkIn = new Uint8Array(64);
        kkIn.set(K_bar, 0);
        kkIn.set(hct, 32);
        const ss = await H(kkIn);

        return { ciphertext: ct, sharedSecret: ss };
    }

    async function kyberDecaps(sk, ct) {
        const skCPA = sk.slice(0, POLYVEC_BYTES);
        const pk    = sk.slice(POLYVEC_BYTES, POLYVEC_BYTES + PK_BYTES);
        const hpk   = sk.slice(POLYVEC_BYTES + PK_BYTES, POLYVEC_BYTES + PK_BYTES + 32);
        const z     = sk.slice(POLYVEC_BYTES + PK_BYTES + 32, POLYVEC_BYTES + PK_BYTES + 64);

        const m_prime = cpadecrypt(skCPA, ct);

        const combined = new Uint8Array(64);
        combined.set(m_prime, 0);
        combined.set(hpk, 32);
        const kr = await G(combined);

        const K_bar = kr.slice(0, 32);
        const coins = kr.slice(32, 64);
        const ct_cmp = await cpaencrypt(pk, m_prime, coins);

        // Constant-time comparison
        let diff = 0;
        for (let i = 0; i < CT_BYTES; i++) diff |= ct[i] ^ ct_cmp[i];

        const hct = await H(ct);
        const input = new Uint8Array(64);
        // Implicit rejection: use z if comparison fails
        input.set(diff === 0 ? K_bar : z, 0);
        input.set(hct, 32);
        return await H(input);
    }

    // ══════════════════════════════════════════════════════════════
    // HYBRID KEY EXCHANGE: ECDH P-256 + Kyber-1024 → HKDF
    // ══════════════════════════════════════════════════════════════

    async function generateHybridKeys() {
        const ecdh = await crypto.subtle.generateKey(
            { name: 'ECDH', namedCurve: 'P-256' }, true, ['deriveBits']
        );
        const ecdhPubRaw = new Uint8Array(await crypto.subtle.exportKey('raw', ecdh.publicKey));
        const kyber = await kyberKeyGen();

        return {
            ecdh: { publicKey: ecdh.publicKey, privateKey: ecdh.privateKey },
            ecdhPublicRaw: ecdhPubRaw,
            kyber: { publicKey: kyber.publicKey, secretKey: kyber.secretKey },
        };
    }

    async function hybridEncapsulate(theirEcdhPubRaw, theirKyberPK) {
        // ECDH: ephemeral key pair → derive bits
        const ephemeral = await crypto.subtle.generateKey(
            { name: 'ECDH', namedCurve: 'P-256' }, true, ['deriveBits']
        );
        const ephPubRaw = new Uint8Array(await crypto.subtle.exportKey('raw', ephemeral.publicKey));

        const theirPub = await crypto.subtle.importKey(
            'raw', theirEcdhPubRaw, { name: 'ECDH', namedCurve: 'P-256' }, false, []
        );
        const ecdhBits = new Uint8Array(await crypto.subtle.deriveBits(
            { name: 'ECDH', public: theirPub }, ephemeral.privateKey, 256
        ));

        // Kyber: encapsulate
        const kyberResult = await kyberEncaps(theirKyberPK);

        // Combine: HKDF(ecdh_ss ‖ kyber_ss, info="GoSiteMe-PQ-Hybrid-v1")
        const combinedSS = new Uint8Array(64);
        combinedSS.set(ecdhBits, 0);
        combinedSS.set(kyberResult.sharedSecret, 32);

        const hkdfKey = await crypto.subtle.importKey('raw', combinedSS, 'HKDF', false, ['deriveBits']);
        const hybridKey = new Uint8Array(await crypto.subtle.deriveBits({
            name: 'HKDF', hash: 'SHA-256', salt: new Uint8Array(32),
            info: new TextEncoder().encode('GoSiteMe-PQ-Hybrid-v1'),
        }, hkdfKey, 256));

        return {
            sharedKey: hybridKey,
            capsule: { ecdhEphemeral: ephPubRaw, kyberCiphertext: kyberResult.ciphertext },
        };
    }

    async function hybridDecapsulate(myEcdhPrivKey, myKyberSK, capsule) {
        const theirEph = await crypto.subtle.importKey(
            'raw', capsule.ecdhEphemeral, { name: 'ECDH', namedCurve: 'P-256' }, false, []
        );
        const ecdhBits = new Uint8Array(await crypto.subtle.deriveBits(
            { name: 'ECDH', public: theirEph }, myEcdhPrivKey, 256
        ));

        const kyberSS = await kyberDecaps(myKyberSK, capsule.kyberCiphertext);

        const combinedSS = new Uint8Array(64);
        combinedSS.set(ecdhBits, 0);
        combinedSS.set(kyberSS, 32);

        const hkdfKey = await crypto.subtle.importKey('raw', combinedSS, 'HKDF', false, ['deriveBits']);
        return new Uint8Array(await crypto.subtle.deriveBits({
            name: 'HKDF', hash: 'SHA-256', salt: new Uint8Array(32),
            info: new TextEncoder().encode('GoSiteMe-PQ-Hybrid-v1'),
        }, hkdfKey, 256));
    }

    // ══════════════════════════════════════════════════════════════
    // INDEXEDDB KEY STORAGE
    // ══════════════════════════════════════════════════════════════

    const PQ_DB = 'comms_pq_keys';
    const PQ_STORE = 'kyber';

    function openDB() {
        return new Promise((resolve, reject) => {
            const req = indexedDB.open(PQ_DB, 1);
            req.onupgradeneeded = () => req.result.createObjectStore(PQ_STORE);
            req.onsuccess = () => resolve(req.result);
            req.onerror = () => reject(req.error);
        });
    }

    async function storeKeys(publicKey, secretKey, ecdhPrivateKey) {
        const db = await openDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(PQ_STORE, 'readwrite');
            tx.objectStore(PQ_STORE).put({
                publicKey: Array.from(publicKey),
                secretKey: Array.from(secretKey),
                ecdhPrivate: ecdhPrivateKey, // CryptoKey object
            }, 'identity');
            tx.oncomplete = resolve;
            tx.onerror = () => reject(tx.error);
        });
    }

    async function getKeys() {
        const db = await openDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(PQ_STORE, 'readonly');
            const req = tx.objectStore(PQ_STORE).get('identity');
            req.onsuccess = () => {
                if (!req.result) return resolve(null);
                resolve({
                    publicKey: new Uint8Array(req.result.publicKey),
                    secretKey: new Uint8Array(req.result.secretKey),
                    ecdhPrivate: req.result.ecdhPrivate,
                });
            };
            req.onerror = () => reject(req.error);
        });
    }

    async function hasKeys() {
        return !!(await getKeys());
    }

    // ══════════════════════════════════════════════════════════════
    // UTILITIES
    // ══════════════════════════════════════════════════════════════

    function bytesToBase64(bytes) {
        let s = '';
        for (let i = 0; i < bytes.length; i++) s += String.fromCharCode(bytes[i]);
        return btoa(s);
    }

    function base64ToBytes(b64) {
        const bin = atob(b64);
        const arr = new Uint8Array(bin.length);
        for (let i = 0; i < bin.length; i++) arr[i] = bin.charCodeAt(i);
        return arr;
    }

    // ══════════════════════════════════════════════════════════════
    // PUBLIC API
    // ══════════════════════════════════════════════════════════════

    return {
        // Kyber KEM
        kyberKeyGen,
        kyberEncaps,
        kyberDecaps,
        // Hybrid ECDH + Kyber
        generateHybridKeys,
        hybridEncapsulate,
        hybridDecapsulate,
        // Key storage
        storeKeys,
        getKeys,
        hasKeys,
        // Serialization
        bytesToBase64,
        base64ToBytes,
        // Constants
        PK_BYTES,
        SK_BYTES,
        CT_BYTES,
    };
})();
