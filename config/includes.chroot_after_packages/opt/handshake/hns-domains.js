#!/usr/bin/env node
/**
 * GoSiteMe — Handshake Domain Manager CLI
 * ────────────────────────────────────────
 * Usage:
 *   node hns-domains.js setup          # Create wallet + get receive address
 *   node hns-domains.js balance        # Check HNS balance
 *   node hns-domains.js status <name>  # Check domain auction status
 *   node hns-domains.js open <name>    # Open auction (starts bidding period)
 *   node hns-domains.js bid <name> <amount> [blind]  # Place bid
 *   node hns-domains.js reveal <name>  # Reveal bids (after bidding ends)
 *   node hns-domains.js redeem <name>  # Redeem after winning
 *   node hns-domains.js register <name> # Register (finalize ownership)
 *   node hns-domains.js renew <name>   # Renew domain
 *   node hns-domains.js dns <name>     # Set DNS records for domain
 *   node hns-domains.js info           # Node sync status
 *   node hns-domains.js pending        # List all pending auctions
 */

const { NodeClient, WalletClient } = require('hs-client');
const fs = require('fs');
const path = require('path');

const HOME = process.env.HOME || '/home/root';
const KEY_FILE = path.join(HOME, '.hsd', 'api-keys.json');

if (!fs.existsSync(KEY_FILE)) {
  console.error('ERROR: HSD not started yet. Run: pm2 start hsd-node');
  console.error('Keys file not found at:', KEY_FILE);
  process.exit(1);
}

const keys = JSON.parse(fs.readFileSync(KEY_FILE, 'utf8'));

const nodeClient = new NodeClient({
  port: 14037,
  apiKey: keys.apiKey
});

const walletClient = new WalletClient({
  port: 14039,
  apiKey: keys.walletApiKey
});

const WALLET_NAME = 'root';

// ── Helpers ─────────────────────────────────────────────

function formatHNS(dollarish) {
  return (dollarish / 1e6).toFixed(6) + ' HNS';
}

async function getWallet() {
  try {
    return await walletClient.wallet(WALLET_NAME);
  } catch {
    return null;
  }
}

async function ensureWallet() {
  let wallet = await getWallet();
  if (!wallet) {
    console.log('Creating wallet "root"...');
    const result = await walletClient.createWallet(WALLET_NAME, {
      type: 'pubkeyhash',
      witness: false
    });
    console.log('Wallet created!');
    console.log('═══════════════════════════════════════════');
    console.log('MNEMONIC (BACK THIS UP SECURELY):');
    console.log(result.mnemonic.phrase);
    console.log('═══════════════════════════════════════════');
    console.log('');
    console.log('Save this mnemonic somewhere safe — it\'s the ONLY way to recover your domains.');
    
    // Save mnemonic to secure file
    const mnemonicFile = path.join(HOME, '.hsd', 'mnemonic-backup.txt');
    fs.writeFileSync(mnemonicFile, 
      `GoSiteMe Handshake Wallet Mnemonic\n` +
      `Created: ${new Date().toISOString()}\n` +
      `════════════════════════════════════\n` +
      `${result.mnemonic.phrase}\n` +
      `════════════════════════════════════\n` +
      `KEEP THIS FILE SECURE. DELETE AFTER BACKING UP ELSEWHERE.\n`,
      { mode: 0o600 }
    );
    console.log(`\nMnemonic also saved to: ${mnemonicFile}`);
    
    wallet = await walletClient.wallet(WALLET_NAME);
  }
  return wallet;
}

// ── Commands ────────────────────────────────────────────

async function cmdSetup() {
  console.log('Setting up GoSiteMe Handshake wallet...\n');
  
  const wallet = await ensureWallet();
  const info = await wallet.getAccount('default');
  const addr = await wallet.createAddress('default');
  
  console.log('\n── Wallet Ready ──');
  console.log('Name:     root');
  console.log('Address:  ' + addr.address);
  console.log('');
  console.log('Next steps:');
  console.log('1. Send HNS to that address (buy on Gate.io, CoinEx, etc.)');
  console.log('2. Wait for HSD to sync (check: node hns-domains.js info)');
  console.log('3. Once funded + synced, run:');
  console.log('   node hns-domains.js open root');
  console.log('   node hns-domains.js open qgsm');
}

async function cmdBalance() {
  const wallet = await ensureWallet();
  const info = await wallet.getBalance('default');
  const addr = await wallet.createAddress('default');
  
  console.log('── HNS Balance ──');
  console.log('Confirmed:   ' + formatHNS(info.confirmed));
  console.log('Unconfirmed: ' + formatHNS(info.unconfirmed));
  console.log('Locked:      ' + formatHNS(info.lockedConfirmed));
  console.log('Receive:     ' + addr.address);
}

async function cmdInfo() {
  const info = await nodeClient.getInfo();
  console.log('── HSD Node Info ──');
  console.log('Chain Height: ' + info.chain.height);
  console.log('Chain Tip:    ' + info.chain.tip);
  console.log('Progress:     ' + (info.chain.progress * 100).toFixed(2) + '%');
  console.log('Network:      ' + info.network);
  console.log('Connections:  ' + info.pool.peers);
  console.log('SPV:          ' + info.chain.spv);

  if (info.chain.progress < 0.99) {
    console.log('\n⚠  Node is still syncing. Wait for 100% before bidding.');
  } else {
    console.log('\n✓  Node fully synced. Ready for auctions.');
  }
}

async function cmdStatus(name) {
  if (!name) { console.error('Usage: node hns-domains.js status <name>'); process.exit(1); }
  
  try {
    const info = await nodeClient.execute('getnameinfo', [name]);
    const ni = info.info;
    
    if (!ni) {
      console.log(`"${name}" — Never been registered. Ready to open auction.`);
      return;
    }
    
    console.log(`── ${name} Status ──`);
    console.log('State:        ' + ni.state);
    console.log('Owner:        ' + (ni.owner ? ni.owner.hash : 'none'));
    console.log('Value:        ' + formatHNS(ni.value));
    console.log('Highest:      ' + formatHNS(ni.highest));
    console.log('Renewal:      Block ' + ni.renewal);
    console.log('Expired:      ' + (ni.expired ? 'YES' : 'No'));
    console.log('Weak:         ' + ni.weak);
    
    if (ni.stats) {
      console.log('Blocks Until: ');
      if (ni.stats.blocksUntilBidding !== undefined)
        console.log('  Bidding:  ' + ni.stats.blocksUntilBidding);
      if (ni.stats.blocksUntilReveal !== undefined)
        console.log('  Reveal:   ' + ni.stats.blocksUntilReveal);
      if (ni.stats.blocksUntilClose !== undefined)
        console.log('  Close:    ' + ni.stats.blocksUntilClose);
      if (ni.stats.blocksUntilExpire !== undefined)
        console.log('  Expire:   ' + ni.stats.blocksUntilExpire);
    }
  } catch (err) {
    console.error('Error:', err.message);
  }
}

async function cmdOpen(name) {
  if (!name) { console.error('Usage: node hns-domains.js open <name>'); process.exit(1); }
  
  const wallet = await ensureWallet();
  
  console.log(`Opening auction for "${name}"...`);
  try {
    const result = await wallet.createOpen({ name });
    console.log(`✓ Auction opened for "${name}"`);
    console.log('TX: ' + result.hash);
    console.log('');
    console.log('Next steps:');
    console.log('1. Wait ~37 blocks (~6 hours) for bidding to start');
    console.log('2. Then run: node hns-domains.js bid ' + name + ' <amount>');
  } catch (err) {
    if (err.message.includes('Name is already in an auction')) {
      console.log(`"${name}" is already in an auction. Check status:`);
      console.log(`  node hns-domains.js status ${name}`);
    } else {
      console.error('Error:', err.message);
    }
  }
}

async function cmdBid(name, amount, blind) {
  if (!name || !amount) {
    console.error('Usage: node hns-domains.js bid <name> <amount> [blind]');
    console.error('  amount: HNS to bid (actual value)');
    console.error('  blind:  extra HNS to disguise bid (optional, default 0)');
    process.exit(1);
  }
  
  const wallet = await ensureWallet();
  const bidValue = Math.floor(parseFloat(amount) * 1e6);    // Convert to dollarish
  const blindValue = blind ? Math.floor(parseFloat(blind) * 1e6) : 0;
  
  console.log(`Bidding on "${name}"...`);
  console.log(`  Bid:   ${amount} HNS`);
  console.log(`  Blind: ${blind || 0} HNS`);
  console.log(`  Total: ${parseFloat(amount) + (parseFloat(blind) || 0)} HNS locked`);
  
  try {
    const result = await wallet.createBid({
      name,
      bid: bidValue,
      lockup: bidValue + blindValue
    });
    console.log(`✓ Bid placed on "${name}"`);
    console.log('TX: ' + result.hash);
    console.log('');
    console.log('Next: After bidding period ends, run:');
    console.log(`  node hns-domains.js reveal ${name}`);
  } catch (err) {
    console.error('Error:', err.message);
  }
}

async function cmdReveal(name) {
  if (!name) { console.error('Usage: node hns-domains.js reveal <name>'); process.exit(1); }
  
  const wallet = await ensureWallet();
  console.log(`Revealing bids for "${name}"...`);
  
  try {
    const result = await wallet.createReveal({ name });
    console.log(`✓ Bids revealed for "${name}"`);
    console.log('TX: ' + result.hash);
    console.log('');
    console.log('Next: After reveal period ends, run:');
    console.log(`  node hns-domains.js redeem ${name}`);
  } catch (err) {
    console.error('Error:', err.message);
  }
}

async function cmdRedeem(name) {
  if (!name) { console.error('Usage: node hns-domains.js redeem <name>'); process.exit(1); }
  
  const wallet = await ensureWallet();
  console.log(`Redeeming losing coins for "${name}"...`);
  
  try {
    const result = await wallet.createRedeem({ name });
    console.log(`✓ Redeemed for "${name}"`);
    console.log('TX: ' + result.hash);
  } catch (err) {
    console.error('Error:', err.message);
  }
}

async function cmdRegister(name) {
  if (!name) { console.error('Usage: node hns-domains.js register <name>'); process.exit(1); }
  
  const wallet = await ensureWallet();
  console.log(`Finalizing registration of "${name}"...`);
  
  try {
    const result = await wallet.createUpdate({
      name,
      data: {
        records: [{
          type: 'NS',
          ns: 'ns1.root.com.'
        }, {
          type: 'NS',
          ns: 'ns2.root.com.'
        }]
      }
    });
    console.log(`✓ "${name}" registered and DNS set to GoSiteMe nameservers!`);
    console.log('TX: ' + result.hash);
    console.log('');
    console.log(`Anyone using Handshake-resolving DNS can now reach *.${name}/`);
  } catch (err) {
    console.error('Error:', err.message);
  }
}

async function cmdRenew(name) {
  if (!name) { console.error('Usage: node hns-domains.js renew <name>'); process.exit(1); }
  
  const wallet = await ensureWallet();
  console.log(`Renewing "${name}"...`);
  
  try {
    const result = await wallet.createRenewal({ name });
    console.log(`✓ "${name}" renewed!`);
    console.log('TX: ' + result.hash);
  } catch (err) {
    console.error('Error:', err.message);
  }
}

async function cmdDNS(name) {
  if (!name) { console.error('Usage: node hns-domains.js dns <name>'); process.exit(1); }
  
  const wallet = await ensureWallet();
  console.log(`Setting DNS records for "${name}" → GoSiteMe...`);
  
  try {
    const result = await wallet.createUpdate({
      name,
      data: {
        records: [
          { type: 'NS', ns: 'ns1.root.com.' },
          { type: 'NS', ns: 'ns2.root.com.' },
          { type: 'GLUE4', ns: 'ns1.root.com.', address: '15.235.50.60' },
          { type: 'GLUE4', ns: 'ns2.root.com.', address: '15.235.50.60' }
        ]
      }
    });
    console.log(`✓ DNS updated for "${name}"`);
    console.log('TX: ' + result.hash);
    console.log('');
    console.log(`Records set:`);
    console.log(`  NS:    ns1.root.com`);
    console.log(`  NS:    ns2.root.com`);
    console.log(`  GLUE4: ns1 → 15.235.50.60`);
    console.log(`  GLUE4: ns2 → 15.235.50.60`);
  } catch (err) {
    console.error('Error:', err.message);
  }
}

async function cmdPending() {
  const wallet = await ensureWallet();
  console.log('── Pending Auctions ──');
  
  try {
    const names = await wallet.getNames();
    if (!names || names.length === 0) {
      console.log('No auctions in progress.');
      return;
    }
    
    for (const n of names) {
      console.log(`  ${n.name.padEnd(20)} state=${n.state}  height=${n.height}`);
    }
  } catch (err) {
    console.error('Error:', err.message);
  }
}

// ── Main ────────────────────────────────────────────────

const [,, cmd, arg1, arg2, arg3] = process.argv;

const commands = {
  setup: cmdSetup,
  balance: cmdBalance,
  info: cmdInfo,
  status: () => cmdStatus(arg1),
  open: () => cmdOpen(arg1),
  bid: () => cmdBid(arg1, arg2, arg3),
  reveal: () => cmdReveal(arg1),
  redeem: () => cmdRedeem(arg1),
  register: () => cmdRegister(arg1),
  renew: () => cmdRenew(arg1),
  dns: () => cmdDNS(arg1),
  pending: cmdPending
};

if (!cmd || !commands[cmd]) {
  console.log('GoSiteMe Handshake Domain Manager');
  console.log('═════════════════════════════════');
  console.log('');
  console.log('Commands:');
  console.log('  setup              Create wallet + get receive address');
  console.log('  balance            Check HNS balance');
  console.log('  info               Node sync status');
  console.log('  status <name>      Check domain auction status');
  console.log('  open <name>        Open auction (start bidding)');
  console.log('  bid <n> <amt> [b]  Place bid (amt=HNS, b=blind)');
  console.log('  reveal <name>      Reveal bids after bidding period');
  console.log('  redeem <name>      Redeem losing bid coins');
  console.log('  register <name>    Finalize + set DNS');
  console.log('  renew <name>       Renew domain');
  console.log('  dns <name>         Update DNS to GoSiteMe servers');
  console.log('  pending            List all in-progress auctions');
  console.log('');
  console.log('Quick start:');
  console.log('  1. pm2 start hsd-node');
  console.log('  2. node hns-domains.js setup');
  console.log('  3. Fund the HNS address');
  console.log('  4. node hns-domains.js open root');
  console.log('  5. node hns-domains.js open qgsm');
  process.exit(0);
}

commands[cmd]().catch(err => {
  console.error('Fatal:', err.message);
  process.exit(1);
});
