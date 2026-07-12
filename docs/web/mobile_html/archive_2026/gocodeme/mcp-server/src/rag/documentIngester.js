/**
 * documentIngester.js — RAG Document Ingestion Engine
 *
 * Parses and extracts text from multiple file formats:
 *   - PDF (via pdf-parse)
 *   - DOCX (via mammoth)
 *   - Markdown (.md)
 *   - HTML (cheerio / regex strip)
 *   - Plain text (.txt, .csv, .json, .xml)
 *   - Code files (any extension, treated as text)
 *
 * Also ingests from URLs using fetch + html stripping.
 *
 * Returns structured documents with metadata (source, type, size, timestamp).
 */

import { readFile } from 'node:fs/promises';
import { existsSync } from 'node:fs';
import path from 'node:path';

// Dynamic imports for optional deps
let pdfParse = null;
let mammoth = null;

async function loadPdfParse() {
  if (!pdfParse) {
    const mod = await import('pdf-parse');
    pdfParse = mod.default || mod;
  }
  return pdfParse;
}

async function loadMammoth() {
  if (!mammoth) {
    const mod = await import('mammoth');
    mammoth = mod.default || mod;
  }
  return mammoth;
}

/**
 * Strip HTML tags and decode entities.
 */
function stripHtml(html) {
  return html
    .replace(/<script[\s\S]*?<\/script>/gi, '')
    .replace(/<style[\s\S]*?<\/style>/gi, '')
    .replace(/<[^>]+>/g, ' ')
    .replace(/&nbsp;/g, ' ')
    .replace(/&amp;/g, '&')
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>')
    .replace(/&quot;/g, '"')
    .replace(/&#39;/g, "'")
    .replace(/\s+/g, ' ')
    .trim();
}

/**
 * Detect file type from extension.
 */
function detectType(filePath) {
  const ext = path.extname(filePath).toLowerCase();
  const typeMap = {
    '.pdf': 'pdf',
    '.docx': 'docx',
    '.doc': 'docx',
    '.md': 'markdown',
    '.markdown': 'markdown',
    '.html': 'html',
    '.htm': 'html',
    '.txt': 'text',
    '.csv': 'text',
    '.json': 'text',
    '.xml': 'text',
    '.yaml': 'text',
    '.yml': 'text',
    '.js': 'code', '.ts': 'code', '.py': 'code', '.php': 'code',
    '.rb': 'code', '.go': 'code', '.rs': 'code', '.java': 'code',
    '.c': 'code', '.cpp': 'code', '.h': 'code', '.css': 'code',
    '.sh': 'code', '.bash': 'code', '.sql': 'code',
  };
  return typeMap[ext] || 'text';
}

/**
 * Ingest a file from disk.
 * @param {string} filePath — absolute path to the file
 * @returns {Promise<{text: string, metadata: object}>}
 */
export async function ingestFile(filePath) {
  if (!existsSync(filePath)) {
    throw new Error(`File not found: ${filePath}`);
  }

  const type = detectType(filePath);
  const fileName = path.basename(filePath);
  let text = '';

  switch (type) {
    case 'pdf': {
      const parser = await loadPdfParse();
      const buffer = await readFile(filePath);
      const data = await parser(buffer);
      text = data.text || '';
      break;
    }
    case 'docx': {
      const mam = await loadMammoth();
      const buffer = await readFile(filePath);
      const result = await mam.extractRawText({ buffer });
      text = result.value || '';
      break;
    }
    case 'html': {
      const raw = await readFile(filePath, 'utf-8');
      text = stripHtml(raw);
      break;
    }
    case 'markdown': {
      // Keep markdown as-is (it's already readable text)
      text = await readFile(filePath, 'utf-8');
      break;
    }
    case 'code':
    case 'text':
    default: {
      text = await readFile(filePath, 'utf-8');
      break;
    }
  }

  return {
    text,
    metadata: {
      source: filePath,
      fileName,
      type,
      size: Buffer.byteLength(text, 'utf-8'),
      ingestedAt: new Date().toISOString(),
    },
  };
}

/**
 * Ingest content from a URL.
 * @param {string} url — the URL to fetch
 * @returns {Promise<{text: string, metadata: object}>}
 */
export async function ingestUrl(url) {
  const axios = (await import('axios')).default;
  const resp = await axios.get(url, {
    timeout: 30000,
    maxContentLength: 10 * 1024 * 1024, // 10MB max
    headers: { 'User-Agent': 'GoCodeMe-RAG/1.0' },
  });

  const contentType = resp.headers['content-type'] || '';
  let text = '';

  if (contentType.includes('application/pdf')) {
    const parser = await loadPdfParse();
    const data = await parser(Buffer.from(resp.data));
    text = data.text || '';
  } else if (contentType.includes('text/html') || contentType.includes('application/xhtml')) {
    text = stripHtml(typeof resp.data === 'string' ? resp.data : JSON.stringify(resp.data));
  } else if (contentType.includes('application/json')) {
    text = typeof resp.data === 'string' ? resp.data : JSON.stringify(resp.data, null, 2);
  } else {
    text = typeof resp.data === 'string' ? resp.data : String(resp.data);
  }

  return {
    text,
    metadata: {
      source: url,
      fileName: new URL(url).pathname.split('/').pop() || 'index',
      type: 'url',
      contentType,
      size: Buffer.byteLength(text, 'utf-8'),
      ingestedAt: new Date().toISOString(),
    },
  };
}

/**
 * Ingest raw text (user-provided content).
 * @param {string} text — the raw text
 * @param {string} [name='manual-input'] — a name for the document
 * @returns {{text: string, metadata: object}}
 */
export function ingestText(text, name = 'manual-input') {
  return {
    text,
    metadata: {
      source: 'manual',
      fileName: name,
      type: 'text',
      size: Buffer.byteLength(text, 'utf-8'),
      ingestedAt: new Date().toISOString(),
    },
  };
}
