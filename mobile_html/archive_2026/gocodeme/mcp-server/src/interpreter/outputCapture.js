/**
 * outputCapture.js — Code Execution Output Capture
 *
 * Captures stdout, stderr, and generated files (images, plots) from
 * code execution. Handles:
 *   - Text output (stdout/stderr)
 *   - Image detection (PNG/SVG files created in temp dir)
 *   - Execution timing and resource usage
 */

import { readFile, readdir, stat } from 'node:fs/promises';
import path from 'node:path';

const MAX_OUTPUT_SIZE = 100_000;  // 100KB max output capture
const IMAGE_EXTENSIONS = new Set(['.png', '.jpg', '.jpeg', '.svg', '.gif', '.webp']);

/**
 * Truncate output if too long.
 */
export function truncateOutput(text, maxLen = MAX_OUTPUT_SIZE) {
  if (!text || text.length <= maxLen) return text;
  const half = Math.floor(maxLen / 2);
  return text.slice(0, half) + `\n\n... [truncated ${text.length - maxLen} chars] ...\n\n` + text.slice(-half);
}

/**
 * Scan a directory for image files created after a timestamp.
 * @param {string} dir — directory to scan
 * @param {number} afterMs — only files created after this timestamp (ms)
 * @returns {Promise<Array<{path: string, name: string, base64: string, mimeType: string}>>}
 */
export async function captureImages(dir, afterMs) {
  const images = [];
  try {
    const files = await readdir(dir);
    for (const file of files) {
      const ext = path.extname(file).toLowerCase();
      if (!IMAGE_EXTENSIONS.has(ext)) continue;

      const filePath = path.join(dir, file);
      const st = await stat(filePath);
      if (st.mtimeMs < afterMs) continue;
      if (st.size > 5 * 1024 * 1024) continue; // skip > 5MB images

      const data = await readFile(filePath);
      const mimeType = ext === '.svg' ? 'image/svg+xml'
        : ext === '.png' ? 'image/png'
        : ext === '.gif' ? 'image/gif'
        : ext === '.webp' ? 'image/webp'
        : 'image/jpeg';

      images.push({
        path: filePath,
        name: file,
        base64: data.toString('base64'),
        mimeType,
      });
    }
  } catch { /* directory might not exist */ }
  return images;
}

/**
 * Format execution result into a structured response.
 */
export function formatResult({ stdout, stderr, exitCode, timing, images = [], language }) {
  return {
    stdout: truncateOutput(stdout || ''),
    stderr: truncateOutput(stderr || ''),
    exitCode,
    success: exitCode === 0,
    timing,
    language,
    images: images.map(img => ({
      name: img.name,
      mimeType: img.mimeType,
      base64: img.base64,
    })),
    hasImages: images.length > 0,
  };
}
