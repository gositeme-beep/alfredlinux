/**
 * browserPool.js — Playwright Browser Instance Pool
 *
 * Manages a pool of headless Chromium browsers for web automation.
 * - Max 3 concurrent browser instances
 * - Auto-recycle after 50 pages to prevent memory leaks
 * - Idle timeout: 5 minutes
 * - Each browser runs in headless mode with sandbox disabled (server env)
 */

import { chromium } from 'playwright';
import os from 'os';

// Point Playwright at the pre-downloaded browser if the default path is missing
if (!process.env.PLAYWRIGHT_BROWSERS_PATH) {
  process.env.PLAYWRIGHT_BROWSERS_PATH = `${os.homedir()}/.playwright-browsers`;
}

const MAX_BROWSERS = 3;
const MAX_PAGES_PER_BROWSER = 50;
const IDLE_TIMEOUT = 5 * 60 * 1000; // 5 minutes

/**
 * @typedef {object} BrowserInstance
 * @property {import('playwright').Browser} browser
 * @property {number} pageCount
 * @property {number} lastUsed
 * @property {boolean} busy
 */

/** @type {BrowserInstance[]} */
const pool = [];
let cleanupTimer = null;

/**
 * Launch a new browser.
 */
async function launchBrowser() {
  const browser = await chromium.launch({
    headless: true,
    args: [
      '--no-sandbox',
      '--disable-setuid-sandbox',
      '--disable-dev-shm-usage',
      '--disable-gpu',
      '--disable-extensions',
      '--single-process',
    ],
  });
  return {
    browser,
    pageCount: 0,
    lastUsed: Date.now(),
    busy: false,
  };
}

/**
 * Start the idle cleanup timer.
 */
function ensureCleanup() {
  if (cleanupTimer) return;
  cleanupTimer = setInterval(async () => {
    const now = Date.now();
    for (let i = pool.length - 1; i >= 0; i--) {
      const inst = pool[i];
      if (!inst.busy && now - inst.lastUsed > IDLE_TIMEOUT) {
        try { await inst.browser.close(); } catch {}
        pool.splice(i, 1);
      }
    }
    if (pool.length === 0) {
      clearInterval(cleanupTimer);
      cleanupTimer = null;
    }
  }, 60_000);
  cleanupTimer.unref();
}

/**
 * Acquire a browser instance from the pool.
 * @returns {Promise<BrowserInstance>}
 */
export async function acquireBrowser() {
  ensureCleanup();

  // Find a free instance that hasn't hit the page limit
  let inst = pool.find(i => !i.busy && i.pageCount < MAX_PAGES_PER_BROWSER);

  if (!inst) {
    // Recycle an old instance if at max capacity
    if (pool.length >= MAX_BROWSERS) {
      const oldest = pool.find(i => !i.busy);
      if (oldest) {
        try { await oldest.browser.close(); } catch {}
        pool.splice(pool.indexOf(oldest), 1);
      }
    }

    if (pool.length < MAX_BROWSERS) {
      inst = await launchBrowser();
      pool.push(inst);
    } else {
      throw new Error('All browser instances are busy. Try again shortly.');
    }
  }

  inst.busy = true;
  inst.lastUsed = Date.now();
  return inst;
}

/**
 * Release a browser instance back to the pool.
 */
export function releaseBrowser(inst) {
  inst.busy = false;
  inst.lastUsed = Date.now();
  inst.pageCount++;

  // Force recycle if over page limit
  if (inst.pageCount >= MAX_PAGES_PER_BROWSER) {
    inst.browser.close().catch(() => {});
    const idx = pool.indexOf(inst);
    if (idx >= 0) pool.splice(idx, 1);
  }
}

/**
 * Create a new page from a pooled browser.
 * @returns {Promise<{page: import('playwright').Page, instance: BrowserInstance}>}
 */
export async function createPage() {
  const inst = await acquireBrowser();
  try {
    const context = await inst.browser.newContext({
      userAgent: 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
      viewport: { width: 1280, height: 720 },
      ignoreHTTPSErrors: true,
    });
    const page = await context.newPage();

    // Block heavy resources to save bandwidth/memory
    await page.route(/\.(woff2?|ttf|eot)($|\?)/, route => route.abort());

    return { page, instance: inst, context };
  } catch (err) {
    releaseBrowser(inst);
    throw err;
  }
}

/**
 * Close a page and release the browser.
 */
export async function closePage(page, context, instance) {
  try { await page.close(); } catch {}
  try { await context.close(); } catch {}
  releaseBrowser(instance);
}

/**
 * Get pool status.
 */
export function getPoolStatus() {
  return {
    total: pool.length,
    busy: pool.filter(i => i.busy).length,
    idle: pool.filter(i => !i.busy).length,
    maxBrowsers: MAX_BROWSERS,
  };
}

/**
 * Close all browsers (for shutdown).
 */
export async function closeAll() {
  for (const inst of pool) {
    try { await inst.browser.close(); } catch {}
  }
  pool.length = 0;
  if (cleanupTimer) {
    clearInterval(cleanupTimer);
    cleanupTimer = null;
  }
}
