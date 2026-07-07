/**
 * webAgent.js — High-Level Browser Automation Agent
 *
 * Provides the high-level actions that Alfred's tools call:
 *   - browseWeb: Navigate to URL, return content + accessibility tree
 *   - screenshotPage: Full-page or element screenshot
 *   - clickElement: Click a CSS/XPath selector
 *   - fillForm: Fill form fields
 *   - extractData: Extract structured data (tables, links, forms)
 *   - webSearch: DuckDuckGo search, return results
 *
 * Each action manages its own browser page lifecycle.
 */

import { createPage, closePage, getPoolStatus } from './browserPool.js';
import {
  extractText, extractMeta, extractAccessibilityTree,
  extractLinks, extractTables, extractForms,
} from './pageAnalyzer.js';

const NAV_TIMEOUT = 30_000;

/**
 * Helper: navigate to URL and wait for content.
 */
async function navigateTo(page, url, waitFor = 'domcontentloaded') {
  await page.goto(url, { waitUntil: waitFor, timeout: NAV_TIMEOUT });
  // Small delay for JS-rendered content
  await page.waitForTimeout(1000);
}

/**
 * Browse a web page — navigate, extract content and accessibility tree.
 *
 * @param {object} opts
 * @param {string} opts.url — URL to navigate to
 * @param {boolean} [opts.includeLinks=false] — include link list
 * @param {boolean} [opts.includeAccessibilityTree=false] — include a11y tree
 * @returns {Promise<object>}
 */
export async function browseWeb(opts) {
  const { url, includeLinks = false, includeAccessibilityTree = false } = opts;
  if (!url) throw new Error('url is required');

  const start = Date.now();
  const { page, instance, context } = await createPage();

  try {
    await navigateTo(page, url);

    const [text, meta] = await Promise.all([
      extractText(page),
      extractMeta(page),
    ]);

    const result = {
      url: page.url(),
      title: meta.title,
      meta,
      content: text.slice(0, 50_000), // limit to 50KB
      contentLength: text.length,
    };

    if (includeLinks) {
      result.links = await extractLinks(page);
    }

    if (includeAccessibilityTree) {
      result.accessibilityTree = await extractAccessibilityTree(page);
    }

    result.timing = Date.now() - start;
    result.pool = getPoolStatus();
    return result;
  } finally {
    await closePage(page, context, instance);
  }
}

/**
 * Take a screenshot of a page or element.
 *
 * @param {object} opts
 * @param {string} opts.url — URL to screenshot
 * @param {string} [opts.selector] — CSS selector for element screenshot
 * @param {boolean} [opts.fullPage=true] — full page or viewport only
 * @returns {Promise<{base64: string, mimeType: string, width: number, height: number, timing: number}>}
 */
export async function screenshotPage(opts) {
  const { url, selector, fullPage = true } = opts;
  if (!url) throw new Error('url is required');

  const start = Date.now();
  const { page, instance, context } = await createPage();

  try {
    await navigateTo(page, url);

    let buffer;
    if (selector) {
      const element = await page.$(selector);
      if (!element) throw new Error(`Element not found: ${selector}`);
      buffer = await element.screenshot({ type: 'png' });
    } else {
      buffer = await page.screenshot({ type: 'png', fullPage });
    }

    const viewport = page.viewportSize();
    return {
      base64: buffer.toString('base64'),
      mimeType: 'image/png',
      width: viewport?.width || 1280,
      height: viewport?.height || 720,
      timing: Date.now() - start,
    };
  } finally {
    await closePage(page, context, instance);
  }
}

/**
 * Click an element on a page.
 *
 * @param {object} opts
 * @param {string} opts.url — URL to navigate to first
 * @param {string} opts.selector — CSS selector to click
 * @param {boolean} [opts.waitForNavigation=false] — wait for navigation after click
 * @returns {Promise<object>}
 */
export async function clickElement(opts) {
  const { url, selector, waitForNavigation = false } = opts;
  if (!url || !selector) throw new Error('url and selector are required');

  const start = Date.now();
  const { page, instance, context } = await createPage();

  try {
    await navigateTo(page, url);

    if (waitForNavigation) {
      await Promise.all([
        page.waitForNavigation({ timeout: NAV_TIMEOUT }),
        page.click(selector),
      ]);
    } else {
      await page.click(selector);
      await page.waitForTimeout(500);
    }

    const text = await extractText(page);
    return {
      url: page.url(),
      clicked: selector,
      content: text.slice(0, 20_000),
      timing: Date.now() - start,
    };
  } finally {
    await closePage(page, context, instance);
  }
}

/**
 * Fill form fields on a page.
 *
 * @param {object} opts
 * @param {string} opts.url — URL to navigate to
 * @param {Array<{selector: string, value: string}>} opts.fields — fields to fill
 * @param {string} [opts.submitSelector] — optional submit button to click after
 * @returns {Promise<object>}
 */
export async function fillForm(opts) {
  const { url, fields, submitSelector } = opts;
  if (!url || !fields?.length) throw new Error('url and fields are required');

  const start = Date.now();
  const { page, instance, context } = await createPage();

  try {
    await navigateTo(page, url);

    for (const { selector, value } of fields) {
      await page.fill(selector, value);
    }

    let submitted = false;
    if (submitSelector) {
      await Promise.all([
        page.waitForNavigation({ timeout: NAV_TIMEOUT }).catch(() => {}),
        page.click(submitSelector),
      ]);
      submitted = true;
      await page.waitForTimeout(1000);
    }

    const text = await extractText(page);
    return {
      url: page.url(),
      fieldsFilled: fields.length,
      submitted,
      content: text.slice(0, 20_000),
      timing: Date.now() - start,
    };
  } finally {
    await closePage(page, context, instance);
  }
}

/**
 * Extract structured data from a page.
 *
 * @param {object} opts
 * @param {string} opts.url — URL to extract from
 * @param {string} [opts.type='all'] — 'tables', 'links', 'forms', 'meta', 'all'
 * @returns {Promise<object>}
 */
export async function extractData(opts) {
  const { url, type = 'all' } = opts;
  if (!url) throw new Error('url is required');

  const start = Date.now();
  const { page, instance, context } = await createPage();

  try {
    await navigateTo(page, url);

    const result = { url: page.url() };

    if (type === 'all' || type === 'meta') {
      result.meta = await extractMeta(page);
    }
    if (type === 'all' || type === 'tables') {
      result.tables = await extractTables(page);
    }
    if (type === 'all' || type === 'links') {
      result.links = await extractLinks(page);
    }
    if (type === 'all' || type === 'forms') {
      result.forms = await extractForms(page);
    }

    result.timing = Date.now() - start;
    return result;
  } finally {
    await closePage(page, context, instance);
  }
}

/**
 * Search the web using DuckDuckGo.
 *
 * @param {object} opts
 * @param {string} opts.query — search query
 * @param {number} [opts.maxResults=10] — max results to return
 * @returns {Promise<object>}
 */
export async function webSearch(opts) {
  const { query, maxResults = 10 } = opts;
  if (!query) throw new Error('query is required');

  const start = Date.now();
  const { page, instance, context } = await createPage();

  try {
    const searchUrl = `https://duckduckgo.com/?q=${encodeURIComponent(query)}`;
    await navigateTo(page, searchUrl, 'networkidle');

    // Wait for results to load
    await page.waitForSelector('.result, .web-result, [data-testid="result"]', { timeout: 10000 }).catch(() => {});

    const results = await page.evaluate((max) => {
      const items = [];
      // DuckDuckGo result selectors
      const resultElements = document.querySelectorAll('.result, .web-result, [data-testid="result"], .nrn-react-div');

      for (const el of resultElements) {
        if (items.length >= max) break;
        const link = el.querySelector('a[href]');
        const title = el.querySelector('h2, .result__title, .result__a')?.textContent?.trim();
        const snippet = el.querySelector('.result__snippet, .snippet, .web-result-description')?.textContent?.trim();
        const href = link?.href;

        if (title && href && !href.includes('duckduckgo.com')) {
          items.push({ title, url: href, snippet: snippet || '' });
        }
      }
      return items;
    }, maxResults);

    return {
      query,
      results,
      totalResults: results.length,
      searchEngine: 'DuckDuckGo',
      timing: Date.now() - start,
    };
  } finally {
    await closePage(page, context, instance);
  }
}

/**
 * Get browser pool status.
 */
export function getBrowserStatus() {
  return getPoolStatus();
}
