/**
 * Alfred Browser Mining Worker
 * ────────────────────────────
 * Lightweight proof-of-work mining that runs in a Web Worker.
 * Computes SHA-256 hashes and submits valid blocks to the mining API.
 *
 * Architecture:
 *   - Runs in Web Worker (non-blocking, separate thread)
 *   - User opts in explicitly
 *   - Throttled to use max 30% CPU (configurable)
 *   - Submits hash blocks every 1M hashes
 *   - 80% reward to user, 20% to platform
 *   - Pauses when tab is backgrounded
 *   - Reports real-time hashrate + earnings
 *
 * Communication:
 *   postMessage({type: 'start', difficulty: 4, throttle: 0.3})
 *   postMessage({type: 'stop'})
 *   postMessage({type: 'set_throttle', value: 0.3})
 *
 * Events sent to main thread:
 *   {type: 'hashrate', rate: 5000, total: 12345678}
 *   {type: 'block_found', hashes: 1000000, reward: 0.0008}
 *   {type: 'stats', hashrate: 5000, total: 12345678, blocks: 12, earned: 0.0096}
 *   {type: 'error', message: '...'}
 *   {type: 'stopped'}
 */

const HASH_BLOCK_SIZE = 1000000; // 1M hashes per reward block
const STATS_INTERVAL = 2000;     // Report stats every 2s
const SUBMIT_INTERVAL = 60000;   // Submit work every 60s minimum

let mining = false;
let difficulty = 4;
let throttle = 0.3;    // 0-1, fraction of CPU to use
let totalHashes = 0;
let blockHashes = 0;
let blocksFound = 0;
let totalEarned = 0;
let lastStatsTime = 0;
let hashesAtLastStats = 0;
let jobId = '';
let submitTimer = null;

// SHA-256 using SubtleCrypto
async function sha256(data) {
    const encoded = new TextEncoder().encode(data);
    const buffer = await crypto.subtle.digest('SHA-256', encoded);
    const array = new Uint8Array(buffer);
    let hex = '';
    for (let i = 0; i < array.length; i++) {
        hex += array[i].toString(16).padStart(2, '0');
    }
    return hex;
}

// Check if hash meets difficulty (leading zeros)
function meetsDifficulty(hash, diff) {
    for (let i = 0; i < diff; i++) {
        if (hash[i] !== '0') return false;
    }
    return true;
}

// Generate random job ID
function generateJobId() {
    const arr = new Uint8Array(16);
    crypto.getRandomValues(arr);
    return Array.from(arr, b => b.toString(16).padStart(2, '0')).join('');
}

// Mining loop
async function mineLoop() {
    jobId = generateJobId();
    const startTime = performance.now();
    lastStatsTime = startTime;
    hashesAtLastStats = 0;

    while (mining) {
        const batchStart = performance.now();
        const batchSize = 100; // Hashes per micro-batch

        // Mine a micro-batch
        for (let i = 0; i < batchSize && mining; i++) {
            const nonce = totalHashes.toString(36) + '-' + Math.random().toString(36).slice(2);
            const input = jobId + ':' + nonce + ':' + difficulty;
            const hash = await sha256(input);

            totalHashes++;
            blockHashes++;

            // Check if we found a valid block
            if (meetsDifficulty(hash, difficulty)) {
                blocksFound++;
                self.postMessage({
                    type: 'block_found',
                    hash: hash,
                    nonce: nonce,
                    hashes: HASH_BLOCK_SIZE,
                    blockNumber: blocksFound,
                });
            }

            // Check if we've completed a hash block
            if (blockHashes >= HASH_BLOCK_SIZE) {
                self.postMessage({
                    type: 'submit_work',
                    hashes: blockHashes,
                    nonce: nonce,
                    difficulty: difficulty,
                    job_id: jobId,
                });
                blockHashes = 0;
            }
        }

        // Report stats periodically
        const now = performance.now();
        if (now - lastStatsTime >= STATS_INTERVAL) {
            const elapsed = (now - lastStatsTime) / 1000;
            const recentHashes = totalHashes - hashesAtLastStats;
            const hashrate = Math.round(recentHashes / elapsed);

            self.postMessage({
                type: 'stats',
                hashrate: hashrate,
                total: totalHashes,
                blocks: blocksFound,
                earned: totalEarned,
            });

            lastStatsTime = now;
            hashesAtLastStats = totalHashes;
        }

        // Throttle: sleep proportional to work time
        const batchDuration = performance.now() - batchStart;
        if (throttle < 1.0 && batchDuration > 0) {
            const sleepTime = batchDuration * (1 / throttle - 1);
            if (sleepTime > 0) {
                await new Promise(resolve => setTimeout(resolve, sleepTime));
            }
        }
    }

    self.postMessage({ type: 'stopped', total: totalHashes, earned: totalEarned });
}

// Message handler
self.onmessage = function(e) {
    const msg = e.data;

    switch (msg.type) {
        case 'start':
            if (mining) return;
            difficulty = msg.difficulty || 4;
            throttle = Math.max(0.05, Math.min(1.0, msg.throttle || 0.3));
            mining = true;
            totalHashes = 0;
            blockHashes = 0;
            blocksFound = 0;
            totalEarned = 0;
            mineLoop();
            break;

        case 'stop':
            mining = false;
            break;

        case 'set_throttle':
            throttle = Math.max(0.05, Math.min(1.0, msg.value || 0.3));
            break;

        case 'set_difficulty':
            difficulty = Math.max(1, Math.min(8, msg.value || 4));
            break;

        case 'reward_confirmed':
            totalEarned += msg.amount || 0;
            break;
    }
};
