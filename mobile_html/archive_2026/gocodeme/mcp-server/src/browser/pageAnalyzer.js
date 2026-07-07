/**
 * pageAnalyzer.js — DOM Analysis & Content Extraction
 *
 * Extracts structured data from web pages:
 *   - Clean text content (no scripts/styles)
 *   - Accessibility tree (simplified)
 *   - Tables → JSON arrays
 *   - Links with text and href
 *   - Form fields
 *   - Meta information (title, description, OG tags)
 */

/**
 * Extract clean text from a page.
 * @param {import('playwright').Page} page
 * @returns {Promise<string>}
 */
export async function extractText(page) {
  return page.evaluate(() => {
    // Remove scripts and styles
    const clone = document.body.cloneNode(true);
    clone.querySelectorAll('script, style, noscript, svg').forEach(el => el.remove());
    return clone.innerText.replace(/\n{3,}/g, '\n\n').trim();
  });
}

/**
 * Extract page metadata.
 * @param {import('playwright').Page} page
 * @returns {Promise<object>}
 */
export async function extractMeta(page) {
  return page.evaluate(() => {
    const getMeta = (name) =>
      document.querySelector(`meta[name="${name}"], meta[property="${name}"]`)?.content || '';

    return {
      title: document.title,
      description: getMeta('description'),
      ogTitle: getMeta('og:title'),
      ogDescription: getMeta('og:description'),
      ogImage: getMeta('og:image'),
      canonical: document.querySelector('link[rel="canonical"]')?.href || '',
      language: document.documentElement.lang || '',
    };
  });
}

/**
 * Extract a simplified accessibility tree.
 * @param {import('playwright').Page} page
 * @returns {Promise<string>}
 */
export async function extractAccessibilityTree(page) {
  return page.evaluate(() => {
    const walk = (el, depth = 0) => {
      const lines = [];
      const indent = '  '.repeat(depth);
      const role = el.getAttribute?.('role') || el.tagName?.toLowerCase() || '';
      const text = el.textContent?.trim().slice(0, 80) || '';
      const label = el.getAttribute?.('aria-label') || el.getAttribute?.('alt') || '';
      const href = el.getAttribute?.('href') || '';

      const interactable = ['a', 'button', 'input', 'select', 'textarea'].includes(el.tagName?.toLowerCase());
      const hasRole = role && !['div', 'span', 'section', 'article'].includes(role);

      if (interactable || hasRole) {
        let line = `${indent}[${role}]`;
        if (label) line += ` "${label}"`;
        else if (text && text.length < 60) line += ` "${text}"`;
        if (href) line += ` → ${href}`;
        if (el.tagName === 'INPUT') line += ` type=${el.type || 'text'} value="${el.value || ''}"`;
        lines.push(line);
      }

      for (const child of el.children || []) {
        lines.push(...walk(child, depth + (interactable || hasRole ? 1 : 0)));
      }
      return lines;
    };

    return walk(document.body).join('\n');
  });
}

/**
 * Extract all links from the page.
 * @param {import('playwright').Page} page
 * @returns {Promise<Array<{text: string, href: string}>>}
 */
export async function extractLinks(page) {
  return page.evaluate(() => {
    return [...document.querySelectorAll('a[href]')].map(a => ({
      text: a.textContent.trim().slice(0, 100),
      href: a.href,
    })).filter(l => l.href && !l.href.startsWith('javascript:'));
  });
}

/**
 * Extract tables as JSON.
 * @param {import('playwright').Page} page
 * @returns {Promise<Array<{headers: string[], rows: string[][]}>>}
 */
export async function extractTables(page) {
  return page.evaluate(() => {
    return [...document.querySelectorAll('table')].map(table => {
      const headers = [...table.querySelectorAll('thead th, tr:first-child th')]
        .map(th => th.textContent.trim());
      const rows = [...table.querySelectorAll('tbody tr, tr')]
        .slice(headers.length > 0 ? 0 : 1)
        .map(tr => [...tr.querySelectorAll('td')]
          .map(td => td.textContent.trim()));
      return { headers, rows: rows.filter(r => r.length > 0) };
    });
  });
}

/**
 * Extract form fields.
 * @param {import('playwright').Page} page
 * @returns {Promise<Array<{selector: string, type: string, name: string, value: string, label: string}>>}
 */
export async function extractForms(page) {
  return page.evaluate(() => {
    const fields = [];
    document.querySelectorAll('input, select, textarea').forEach((el, i) => {
      const id = el.id || el.name || `field-${i}`;
      const label = el.labels?.[0]?.textContent?.trim() ||
        el.getAttribute('placeholder') ||
        el.getAttribute('aria-label') || '';
      fields.push({
        selector: el.id ? `#${el.id}` : el.name ? `[name="${el.name}"]` : `input:nth-of-type(${i + 1})`,
        type: el.type || el.tagName.toLowerCase(),
        name: el.name || '',
        value: el.value || '',
        label,
      });
    });
    return fields;
  });
}
