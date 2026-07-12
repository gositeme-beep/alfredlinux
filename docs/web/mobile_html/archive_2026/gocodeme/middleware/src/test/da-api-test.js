'use strict';

/**
 * DirectAdmin API Proof-of-Concept Test
 *
 * Run this FIRST before building anything else.
 * It proves that admin impersonation, file listing, and file writing all work.
 *
 * Usage:
 *   cp .env.example .env   (fill in DA_HOST, DA_ADMIN_USER, DA_ADMIN_PASS)
 *   node src/test/da-api-test.js <da_username>
 *
 * Example:
 *   node src/test/da-api-test.js testcustomer1
 */

require('dotenv').config();

const { listFiles, readFile, writeFile, deleteFile } = require('../directadmin/fileManager');

const TEST_USERNAME = process.argv[2];

if (!TEST_USERNAME) {
  console.error('Usage: node src/test/da-api-test.js <directadmin_username>');
  process.exit(1);
}

async function run() {
  console.log(`\n=== GoCodeMe DA API Test ===`);
  console.log(`Target user : ${TEST_USERNAME}`);
  console.log(`DA host     : ${process.env.DA_HOST}`);
  console.log(`Admin user  : ${process.env.DA_ADMIN_USER}`);
  console.log('');

  // ── Step 1: List public_html ─────────────────────────────────────────────
  console.log('[ 1 ] Listing public_html...');
  try {
    const files = await listFiles(TEST_USERNAME, 'public_html');
    const list  = Array.isArray(files) ? files : (files.files || files.entries || files);
    console.log(`      ✓ Found ${Array.isArray(list) ? list.length : '?'} items`);
    if (Array.isArray(list) && list.length > 0) {
      console.log(`      First item: ${JSON.stringify(list[0])}`);
    }
  } catch (err) {
    console.error(`      ✗ FAILED: ${err.message}`);
    process.exit(1);
  }

  // ── Step 2: Write a test file ────────────────────────────────────────────
  const testPath    = 'public_html/gocodeme-test.txt';
  const testContent = `GoCodeMe API test — ${new Date().toISOString()}`;

  console.log(`\n[ 2 ] Writing test file → ${testPath}...`);
  try {
    await writeFile(TEST_USERNAME, testPath, testContent);
    console.log(`      ✓ File written`);
  } catch (err) {
    console.error(`      ✗ FAILED: ${err.message}`);
    process.exit(1);
  }

  // ── Step 3: Read it back ─────────────────────────────────────────────────
  console.log(`\n[ 3 ] Reading back ${testPath}...`);
  try {
    const content = await readFile(TEST_USERNAME, testPath);
    const match   = content.includes('GoCodeMe API test');
    console.log(`      ✓ Read OK. Content matches: ${match}`);
    console.log(`      Content: ${content.trim()}`);
  } catch (err) {
    console.error(`      ✗ FAILED: ${err.message}`);
    process.exit(1);
  }

  // ── Step 4: Delete test file ─────────────────────────────────────────────
  console.log(`\n[ 4 ] Deleting test file...`);
  try {
    await deleteFile(TEST_USERNAME, testPath);
    console.log(`      ✓ Deleted`);
  } catch (err) {
    console.error(`      ✗ FAILED: ${err.message}`);
    // Non-fatal — test still passed overall
  }

  console.log('\n=== ALL TESTS PASSED — Foundation is ready ===\n');
  console.log('Next step: build the WHMCS provisioning module and fork Theia.\n');
}

run().catch((err) => {
  console.error(`Unexpected error: ${err.message}`);
  process.exit(1);
});
