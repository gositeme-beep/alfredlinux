/**
 * Alfred Mining Worker — SHA-256 Proof-of-Work
 * Runs inside a Web Worker (non-blocking)
 * Same algorithm as assets/js/mining-worker.js but packaged for extension use
 */

let running = false;
let throttle = 0.30;
let difficulty = 4;
let totalHashes = 0;
let blocksFound = 0;
let earnings = 0;
let jobId = 0;
const HASH_BLOCK_SIZE = 1_000_000;
const STATS_INTERVAL = 2000;
const GSM_PER_BLOCK = 0.001;
const BATCH_SIZE = 100;

self.onmessage = (e) => {
  const { type, ...params } = e.data;
  switch (type) {
    case 'start':
      throttle = params.throttle || 0.30;
      difficulty = params.difficulty || 4;
      if (!running) { running = true; mine(); }
      break;
    case 'stop':
      running = false;
      break;
    case 'set_throttle':
      throttle = Math.max(0.05, Math.min(1.0, params.value));
      break;
    case 'set_difficulty':
      difficulty = params.value || 4;
      break;
    case 'reward_confirmed':
      earnings += params.amount || 0;
      break;
  }
};

async function mine() {
  jobId++;
  const currentJob = jobId;
  let blockHashes = 0;
  let lastStats = Date.now();
  const prefix = '0'.repeat(difficulty);

  while (running && currentJob === jobId) {
    const sleepMs = Math.max(1, Math.round((1 - throttle) / throttle * 10));

    for (let i = 0; i < BATCH_SIZE && running; i++) {
      const nonce = crypto.randomUUID();
      const data = new TextEncoder().encode(`${currentJob}:${nonce}:${totalHashes}`);
      const hashBuffer = await crypto.subtle.digest('SHA-256', data);
      const hashArray = new Uint8Array(hashBuffer);
      const hashHex = Array.from(hashArray).map(b => b.toString(16).padStart(2, '0')).join('');

      totalHashes++;
      blockHashes++;

      // Check if hash meets difficulty (leading zeros)
      if (hashHex.startsWith(prefix)) {
        blocksFound++;
        earnings += GSM_PER_BLOCK;
        self.postMessage({
          type: 'submit_work',
          work: {
            hashes: HASH_BLOCK_SIZE,
            nonce: nonce,
            hash: hashHex,
            difficulty: difficulty,
            elapsed_ms: Date.now() - lastStats
          }
        });
      }

      // Submit hash block every 1M hashes
      if (blockHashes >= HASH_BLOCK_SIZE) {
        blocksFound++;
        earnings += GSM_PER_BLOCK;
        self.postMessage({
          type: 'submit_work',
          work: {
            hashes: HASH_BLOCK_SIZE,
            nonce: crypto.randomUUID(),
            hash: hashHex,
            difficulty: difficulty,
            elapsed_ms: Date.now() - lastStats
          }
        });
        blockHashes = 0;
      }
    }

    // Report stats periodically
    const now = Date.now();
    if (now - lastStats >= STATS_INTERVAL) {
      const elapsed = (now - lastStats) / 1000;
      const hashrate = blockHashes / elapsed;
      self.postMessage({
        type: 'stats',
        hashrate: Math.round(hashrate),
        totalHashes,
        blocksFound,
        earnings
      });
      lastStats = now;
      blockHashes = 0;
    }

    // Throttle — yield CPU
    if (sleepMs > 0) {
      await new Promise(r => setTimeout(r, sleepMs));
    }
  }
}
