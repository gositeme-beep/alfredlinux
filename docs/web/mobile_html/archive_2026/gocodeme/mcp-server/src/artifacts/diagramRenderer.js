/**
 * diagramRenderer.js — Mermaid Diagram → SVG/HTML Renderer
 *
 * Renders Mermaid diagram markup to SVG or HTML.
 * Uses the mermaid-cli for server-side rendering when available,
 * falls back to client-side HTML with mermaid.js CDN.
 *
 * Supported diagram types: flowchart, sequence, class, state, ER,
 * gantt, pie, git, mindmap, timeline, etc.
 */

import { writeFile, readFile, mkdir } from 'node:fs/promises';
import { execSync } from 'node:child_process';
import path from 'node:path';
import os from 'node:os';

const TEMP_DIR = path.join(os.tmpdir(), 'gocodeme-diagrams');

/**
 * Check if mmdc (mermaid CLI) is available.
 */
function hasMermaidCli() {
  try {
    execSync('npx --yes @mermaid-js/mermaid-cli --version 2>/dev/null', { timeout: 5000 });
    return true;
  } catch {
    return false;
  }
}

/**
 * Generate an HTML page with mermaid.js for client-side rendering.
 */
function generateHtmlDiagram(mermaidCode, theme = 'default') {
  return `<!DOCTYPE html>
<html><head>
<meta charset="utf-8">
<title>Diagram</title>
<script src="https://cdn.jsdelivr.net/npm/mermaid@11/dist/mermaid.min.js"></script>
<style>body{margin:20px;display:flex;justify-content:center;background:#fff;font-family:system-ui}#diagram{max-width:100%}</style>
</head><body>
<pre class="mermaid" id="diagram">
${mermaidCode}
</pre>
<script>mermaid.initialize({startOnLoad:true,theme:'${theme}',securityLevel:'loose'})</script>
</body></html>`;
}

/**
 * Render a Mermaid diagram.
 *
 * @param {object} opts
 * @param {string} opts.code — Mermaid diagram code
 * @param {string} [opts.format='html'] — 'svg', 'png', 'html'
 * @param {string} [opts.theme='default'] — 'default', 'dark', 'forest', 'neutral'
 * @param {number} [opts.width=800] — output width
 * @returns {Promise<{content: string, mimeType: string, format: string, timing: number}>}
 */
export async function renderDiagram(opts) {
  const {
    code,
    format = 'html',
    theme = 'default',
    width = 800,
  } = opts;

  if (!code || !code.trim()) throw new Error('Mermaid diagram code is required');

  const start = Date.now();

  // For SVG/PNG, try mermaid-cli
  if ((format === 'svg' || format === 'png') && hasMermaidCli()) {
    try {
      await mkdir(TEMP_DIR, { recursive: true });
      const inputFile = path.join(TEMP_DIR, `diagram-${Date.now()}.mmd`);
      const outputFile = path.join(TEMP_DIR, `diagram-${Date.now()}.${format}`);

      await writeFile(inputFile, code);

      execSync(
        `npx --yes @mermaid-js/mermaid-cli -i "${inputFile}" -o "${outputFile}" -t ${theme} -w ${width} --quiet 2>/dev/null`,
        { timeout: 30000 },
      );

      const content = await readFile(outputFile);
      const base64 = content.toString('base64');
      const mimeType = format === 'svg' ? 'image/svg+xml' : 'image/png';

      return {
        content: base64,
        mimeType,
        format,
        isBase64: true,
        timing: Date.now() - start,
      };
    } catch {
      // Fall through to HTML
    }
  }

  // Fallback: HTML with client-side mermaid.js
  const html = generateHtmlDiagram(code, theme);

  return {
    content: html,
    mimeType: 'text/html',
    format: 'html',
    isBase64: false,
    timing: Date.now() - start,
  };
}
