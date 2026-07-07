/**
 * GoSiteMe — Handshake (HNS) SPV Node
 * ────────────────────────────────────
 * Lightweight SPV node for domain auctions
 * Ports: 14037 (node API), 14038 (p2p), 14039 (wallet API)
 */
const { SPVNode } = require('hsd');
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');

const HOME = process.env.HOME || '/home/root';
const KEY_FILE = path.join(HOME, '.hsd', 'api-keys.json');

// Generate or load API keys (random per install — never hardcoded)
let apiKey, walletApiKey;
if (fs.existsSync(KEY_FILE)) {
  const keys = JSON.parse(fs.readFileSync(KEY_FILE, 'utf8'));
  apiKey = keys.apiKey;
  walletApiKey = keys.walletApiKey;
} else {
  apiKey = crypto.randomBytes(32).toString('hex');
  walletApiKey = crypto.randomBytes(32).toString('hex');
  fs.mkdirSync(path.dirname(KEY_FILE), { recursive: true });
  fs.writeFileSync(KEY_FILE, JSON.stringify({ apiKey, walletApiKey }, null, 2), { mode: 0o600 });
  console.log('[HSD] Generated new API keys → ~/.hsd/api-keys.json');
}

const node = new SPVNode({
  prefix: path.join(HOME, '.hsd'),
  memory: false,
  network: 'main',
  httpPort: 14037,
  apiKey: apiKey,
  walletHttpPort: 14039,
  walletApiKey: walletApiKey,
  logLevel: 'debug',
  workers: true,
  port: 12038,
  brontidePort: 44806,
  maxOutbound: 20,
  // Route all P2P through Tor SOCKS5 — our OVH IP is blocked by most HSD peers
  proxy: '127.0.0.1:9050',
  // Disable inbound — can't accept connections behind Tor
  listen: false,
  // Let HSD use built-in DNS seed discovery (hs-mainnet.bcoin.ninja, seed.htools.work)
  // DO NOT set `nodes:` — it acts as whitelist and blocks all other discovery
});

(async () => {
  await node.ensure();
  await node.open();
  await node.connect();
  node.startSync();

  console.log('[HSD] ═══════════════════════════════════════');
  console.log('[HSD] Handshake SPV Node Started');
  console.log('[HSD] Network:    mainnet');
  console.log('[HSD] Node API:   http://127.0.0.1:14037');
  console.log('[HSD] Wallet API: http://127.0.0.1:14039');
  console.log('[HSD] P2P Port:   14038');
  console.log('[HSD] Chain tip:  ' + node.chain.tip.height);
  console.log('[HSD] ═══════════════════════════════════════');

  // Log sync progress periodically
  let lastHeight = 0;
  setInterval(() => {
    const h = node.chain.tip.height;
    if (h !== lastHeight) {
      console.log(`[HSD] Sync progress: block ${h}`);
      lastHeight = h;
    }
  }, 30000);
})().catch(err => {
  console.error('[HSD] Fatal error:', err.message);
  process.exit(1);
});

// Graceful shutdown
process.on('SIGINT', async () => {
  console.log('[HSD] Shutting down...');
  await node.close();
  process.exit(0);
});

process.on('SIGTERM', async () => {
  console.log('[HSD] Shutting down...');
  await node.close();
  process.exit(0);
});
