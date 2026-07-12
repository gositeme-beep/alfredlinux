/**
 * htmlPreview.js — Live HTML Preview Generator
 *
 * Creates self-contained HTML previews with optional code injection.
 * Supports Tailwind CSS, Alpine.js, and custom CSS/JS.
 */

/**
 * Generate a self-contained HTML preview.
 *
 * @param {object} opts
 * @param {string} opts.html — HTML content (body or full document)
 * @param {string} [opts.css=''] — additional CSS
 * @param {string} [opts.js=''] — additional JavaScript
 * @param {boolean} [opts.includeTailwind=false] — include Tailwind CSS CDN
 * @param {boolean} [opts.includeAlpine=false] — include Alpine.js CDN
 * @param {string} [opts.title='Preview'] — page title
 * @returns {{content: string, mimeType: string}}
 */
export function generatePreview(opts) {
  const {
    html,
    css = '',
    js = '',
    includeTailwind = false,
    includeAlpine = false,
    title = 'Preview',
  } = opts;

  if (!html) throw new Error('html content is required');

  // Check if the input is already a full HTML document
  const isFullDocument = html.trim().toLowerCase().startsWith('<!doctype') || html.trim().toLowerCase().startsWith('<html');

  if (isFullDocument) {
    // Inject additional CSS/JS into existing document
    let content = html;
    if (css) {
      content = content.replace('</head>', `<style>${css}</style></head>`);
    }
    if (js) {
      content = content.replace('</body>', `<script>${js}</script></body>`);
    }
    return { content, mimeType: 'text/html' };
  }

  // Build a complete HTML document
  const cdns = [];
  if (includeTailwind) cdns.push('<script src="https://cdn.tailwindcss.com"></script>');
  if (includeAlpine) cdns.push('<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js"></script>');

  const content = `<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>${title}</title>
${cdns.join('\n')}
<style>
*, *::before, *::after { box-sizing: border-box; }
body { margin: 0; font-family: system-ui, -apple-system, sans-serif; }
${css}
</style>
</head>
<body>
${html}
${js ? `<script>${js}</script>` : ''}
</body>
</html>`;

  return { content, mimeType: 'text/html' };
}
