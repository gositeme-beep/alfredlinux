/**
 * codeIndexer.js — ORACLE: Semantic Codebase Intelligence
 *
 * Indexes every file in the user's workspace into a vector store for
 * semantic code search. Language-aware chunking, auto-reindex on changes.
 *
 * Storage: ~/.gocodeme/vectors/codebase_{daUsername}/data.json
 *
 * Chunking strategy:
 *  - Code files: split by function/class (regex-based) with context
 *  - Config files: embed as whole file
 *  - Docs: split by heading sections
 *  - Binary files: skip
 *
 * Index is rebuilt incrementally — only changed files are re-embedded.
 * File hashes (SHA-256) are tracked to detect changes.
 */

import { VectorStore } from '../vectorStore.js';
import { embed, embedOne } from '../embeddings.js';
import { readFile, readdir, stat } from 'node:fs/promises';
import { createHash } from 'node:crypto';
import path from 'node:path';

const VECTORS_BASE = '/home/gositeme/.gocodeme/vectors';

// ── Configuration ───────────────────────────────────────────────────────────
const MAX_FILE_SIZE = 256 * 1024; // 256KB — skip larger files
const MAX_CHUNK_SIZE = 1500;      // ~375 tokens
const OVERLAP_LINES = 3;          // overlap between chunks
const MAX_FILES_PER_INDEX = 2000; // safety limit
const BATCH_SIZE = 32;            // embeddings per batch

// ── File classification ─────────────────────────────────────────────────────
const CODE_EXTENSIONS = new Set([
  '.js', '.ts', '.jsx', '.tsx', '.mjs', '.cjs',
  '.py', '.pyw',
  '.php', '.phtml',
  '.rb', '.go', '.rs', '.java', '.kt', '.scala',
  '.c', '.cpp', '.h', '.hpp', '.cs',
  '.swift', '.m', '.mm',
  '.sh', '.bash', '.zsh',
  '.sql',
  '.vue', '.svelte',
]);
const CONFIG_EXTENSIONS = new Set(['.json', '.yaml', '.yml', '.toml', '.ini', '.env', '.xml']);
const DOC_EXTENSIONS = new Set(['.md', '.txt', '.rst', '.adoc']);
const SKIP_DIRS = new Set([
  'node_modules', '.git', 'vendor', '__pycache__', '.cache',
  'dist', 'build', '.next', '.nuxt', 'coverage', '.gocodeme',
  'gocodeme-editor', 'applications', 'wp-content', 'presser',
  'logs', 'cache', 'downloads', '.pm2', 'openhands-fork', 'theia-fork',
  'chess', '.vscode-server', 'storage',
]);

/**
 * Get or create a codebase vector store for a user.
 */
function getStore(daUsername) {
  return new VectorStore(VECTORS_BASE, `codebase_${daUsername}`);
}

/**
 * Hash file content for change detection.
 */
function hashContent(content) {
  return createHash('sha256').update(content).digest('hex').slice(0, 16);
}

/**
 * Classify a file by extension.
 * @returns {'code'|'config'|'doc'|'skip'}
 */
function classify(filePath) {
  const ext = path.extname(filePath).toLowerCase();
  if (CODE_EXTENSIONS.has(ext)) return 'code';
  if (CONFIG_EXTENSIONS.has(ext)) return 'config';
  if (DOC_EXTENSIONS.has(ext)) return 'doc';
  return 'skip';
}

// ═══════════════════════════════════════════════════════════════════
// CHUNKING STRATEGIES
// ═══════════════════════════════════════════════════════════════════

/**
 * Chunk code files by function/class boundaries.
 * Uses regex to find function/class definitions and splits at those points.
 */
function chunkCode(content, filePath) {
  const lines = content.split('\n');
  const ext = path.extname(filePath).toLowerCase();
  const chunks = [];

  // Regex patterns for function/class boundaries
  const patterns = [
    /^(?:export\s+)?(?:async\s+)?function\s+\w+/,               // JS/TS function
    /^(?:export\s+)?(?:const|let|var)\s+\w+\s*=\s*(?:async\s+)?(?:\(|function)/, // JS arrow/const func
    /^(?:export\s+)?class\s+\w+/,                                 // JS/TS class
    /^\s+(?:async\s+)?(?:get\s+|set\s+)?\w+\s*\([^)]*\)\s*\{/,  // class method
    /^def\s+\w+/,                                                  // Python function
    /^class\s+\w+/,                                                // Python class
    /^(?:public|private|protected)?\s*(?:static\s+)?function\s+\w+/, // PHP function
    /^(?:public|private|protected)?\s*class\s+\w+/,                // PHP class
    /^func\s+\w+/,                                                  // Go function
    /^(?:pub\s+)?fn\s+\w+/,                                        // Rust function
    /^(?:pub\s+)?struct\s+\w+/,                                     // Rust struct
  ];

  let currentChunk = [];
  let currentStart = 0;

  for (let i = 0; i < lines.length; i++) {
    const line = lines[i];
    const isBreak = patterns.some(p => p.test(line));

    if (isBreak && currentChunk.length > 10) {
      // Save current chunk
      chunks.push({
        text: currentChunk.join('\n'),
        startLine: currentStart + 1,
        endLine: i,
      });
      // Start new chunk with overlap
      const overlapStart = Math.max(0, i - OVERLAP_LINES);
      currentChunk = lines.slice(overlapStart, i + 1);
      currentStart = overlapStart;
    } else {
      currentChunk.push(line);
    }

    // Force split if chunk is too large
    if (currentChunk.join('\n').length > MAX_CHUNK_SIZE * 2) {
      chunks.push({
        text: currentChunk.join('\n'),
        startLine: currentStart + 1,
        endLine: i + 1,
      });
      currentChunk = [];
      currentStart = i + 1;
    }
  }

  // Don't forget the last chunk
  if (currentChunk.length > 0) {
    chunks.push({
      text: currentChunk.join('\n'),
      startLine: currentStart + 1,
      endLine: lines.length,
    });
  }

  return chunks;
}

/**
 * Chunk document files by headings.
 */
function chunkDoc(content) {
  const sections = content.split(/(?=^#{1,3}\s)/m);
  return sections
    .filter(s => s.trim().length > 20)
    .map(s => ({ text: s.trim(), startLine: 0, endLine: 0 }));
}

/**
 * Config files are embedded as a whole (they're usually small).
 */
function chunkConfig(content) {
  if (content.length > MAX_CHUNK_SIZE * 3) {
    // Split large configs into chunks
    const lines = content.split('\n');
    const chunks = [];
    for (let i = 0; i < lines.length; i += 50) {
      const chunk = lines.slice(i, i + 55).join('\n'); // 50 lines + 5 overlap
      chunks.push({ text: chunk, startLine: i + 1, endLine: Math.min(i + 55, lines.length) });
    }
    return chunks;
  }
  return [{ text: content, startLine: 1, endLine: content.split('\n').length }];
}

// ═══════════════════════════════════════════════════════════════════
// INDEXING
// ═══════════════════════════════════════════════════════════════════

/**
 * Recursively collect all indexable files in a directory.
 * @param {string} dir
 * @param {string} baseDir — for relative paths
 * @returns {Promise<string[]>}
 */
async function collectFiles(dir, baseDir, files = [], depth = 0) {
  if (depth > 15 || files.length >= MAX_FILES_PER_INDEX) return files;

  let entries;
  try {
    entries = await readdir(dir, { withFileTypes: true });
  } catch { return files; }

  for (const entry of entries) {
    if (files.length >= MAX_FILES_PER_INDEX) break;

    if (entry.isDirectory()) {
      if (SKIP_DIRS.has(entry.name) || entry.name.startsWith('.')) continue;
      await collectFiles(path.join(dir, entry.name), baseDir, files, depth + 1);
    } else if (entry.isFile()) {
      const fullPath = path.join(dir, entry.name);
      const relPath = path.relative(baseDir, fullPath);
      const type = classify(entry.name);
      if (type !== 'skip') {
        files.push({ fullPath, relPath, type });
      }
    }
  }

  return files;
}

/**
 * Index (or re-index) a user's workspace.
 *
 * @param {string} daUsername
 * @param {string} homeDir — user's home directory
 * @param {boolean} [force=false] — force full re-index even if files haven't changed
 * @returns {Promise<{indexed: number, skipped: number, total_chunks: number, elapsed_ms: number}>}
 */
export async function indexWorkspace(daUsername, homeDir, force = false) {
  const startTime = Date.now();
  const store = getStore(daUsername);
  const workspaceDir = path.join(homeDir, 'public_html');

  // Collect all files
  console.log(`[ORACLE] Scanning ${workspaceDir} ...`);
  const files = await collectFiles(workspaceDir, homeDir);
  console.log(`[ORACLE] Found ${files.length} indexable files`);

  // Load existing hash index (stored as a special vector)
  const hashIndex = await store.get('__hash_index__');
  const fileHashes = hashIndex ? JSON.parse(hashIndex.text || '{}') : {};
  const newHashes = {};

  let indexed = 0;
  let skipped = 0;
  const pendingEmbeddings = []; // { id, text, metadata }

  for (const { fullPath, relPath, type } of files) {
    try {
      // Read file
      const fileStat = await stat(fullPath);
      if (fileStat.size > MAX_FILE_SIZE || fileStat.size === 0) { skipped++; continue; }

      const content = await readFile(fullPath, 'utf-8');
      const hash = hashContent(content);

      // Skip unchanged files
      if (!force && fileHashes[relPath] === hash) {
        newHashes[relPath] = hash;
        skipped++;
        continue;
      }

      newHashes[relPath] = hash;

      // Delete old chunks for this file
      await store.deleteByFilter({ file: relPath });

      // Chunk based on type
      let chunks;
      if (type === 'code') chunks = chunkCode(content, fullPath);
      else if (type === 'doc') chunks = chunkDoc(content);
      else chunks = chunkConfig(content);

      // Queue for embedding
      for (let i = 0; i < chunks.length; i++) {
        const chunkText = chunks[i].text;
        if (chunkText.trim().length < 20) continue; // skip tiny chunks

        const chunkId = `${relPath}:${i}`;
        pendingEmbeddings.push({
          id: chunkId,
          text: `File: ${relPath}\n${chunkText}`.slice(0, MAX_CHUNK_SIZE),
          metadata: {
            file: relPath,
            type,
            chunk: i,
            startLine: chunks[i].startLine,
            endLine: chunks[i].endLine,
            language: path.extname(fullPath).slice(1),
          },
        });
      }

      indexed++;
    } catch { skipped++; }
  }

  // Batch embed all chunks
  const totalBatches = Math.ceil(pendingEmbeddings.length / BATCH_SIZE);
  console.log(`[ORACLE] Embedding ${pendingEmbeddings.length} chunks in ${totalBatches} batches...`);

  if (pendingEmbeddings.length > 0) {
    for (let i = 0; i < pendingEmbeddings.length; i += BATCH_SIZE) {
      const batchNum = Math.floor(i / BATCH_SIZE) + 1;
      const batch = pendingEmbeddings.slice(i, i + BATCH_SIZE);
      const texts = batch.map(b => b.text);

      try {
        const vectors = await embed(texts);

        const items = batch.map((b, j) => ({
          id: b.id,
          vector: vectors[j],
          metadata: b.metadata,
          text: b.text,
        }));

        await store.upsertMany(items);
        if (batchNum % 10 === 0 || batchNum === totalBatches) {
          console.log(`[ORACLE] Batch ${batchNum}/${totalBatches} done (${i + batch.length}/${pendingEmbeddings.length} chunks)`);
        }
      } catch (err) {
        console.error(`[ORACLE] Embedding batch ${batchNum} failed: ${err.message}`);
      }
    }
  }

  // Save hash index
  await store.upsert('__hash_index__', new Array(384).fill(0), { type: 'index' }, JSON.stringify(newHashes));
  await store.flush();

  const totalChunks = await store.count();

  return {
    indexed,
    skipped,
    total_chunks: totalChunks - 1, // minus the hash index entry
    total_files: files.length,
    elapsed_ms: Date.now() - startTime,
  };
}

/**
 * Semantic search across the user's indexed codebase.
 *
 * @param {string} daUsername
 * @param {string} query — natural language query
 * @param {number} [topK=10] — number of results
 * @param {string} [filePattern] — optional glob-like filter (e.g., "*.js" or "src/")
 * @param {string} [language] — optional language filter (e.g., "js", "py")
 * @returns {Promise<Array<{file: string, text: string, score: number, startLine: number, endLine: number, language: string}>>}
 */
export async function semanticSearch(daUsername, query, topK = 10, filePattern = null, language = null, useReranking = false) {
  const store = getStore(daUsername);
  const count = await store.count();

  if (count <= 1) { // only hash index entry
    return {
      results: [],
      message: 'Workspace not indexed yet. Run reindex_workspace first.',
      indexed: false,
    };
  }

  // Embed the query
  const queryVector = await embedOne(query);

  // Build metadata filter
  let filter = null;
  if (language) {
    filter = { language };
  }

  // Search (get extra results for post-filtering and reranking)
  const rawResults = await store.search(queryVector, topK * 3, filter);

  // Post-filter: exclude hash index, apply file pattern
  let results = rawResults.filter(r => r.id !== '__hash_index__');

  if (filePattern) {
    // Simple glob matching
    const pattern = filePattern
      .replace(/\./g, '\\.')
      .replace(/\*/g, '.*')
      .replace(/\?/g, '.');
    const regex = new RegExp(pattern, 'i');
    results = results.filter(r => regex.test(r.metadata?.file || ''));
  }

  // Optional: Rerank with Together.ai for higher precision
  if (useReranking && results.length > 1 && process.env.TOGETHER_API_KEY) {
    try {
      const { rerank } = await import('../togetherClient.js');
      const docs = results.map(r => r.text || '').slice(0, 50); // Cap at 50 for reranking
      const reranked = await rerank(query, docs, 'mixedbread-ai/Mxbai-Rerank-Large-V2', topK);
      if (reranked.results?.length > 0) {
        // Reorder results based on reranking scores
        const reorderedResults = reranked.results
          .filter(rr => rr.index < results.length)
          .map(rr => ({
            ...results[rr.index],
            score: rr.relevance_score, // Override with reranking score
            reranked: true,
          }));
        results = reorderedResults;
      }
    } catch (rerankErr) {
      // Fall back to vector similarity if reranking fails
      console.warn('[ORACLE] Reranking failed, using vector similarity:', rerankErr.message);
    }
  }

  // Take top-K after filtering
  results = results.slice(0, topK);

  return {
    results: results.map(r => ({
      file: r.metadata?.file || r.id,
      text: r.text,
      score: Math.round(r.score * 1000) / 1000,
      startLine: r.metadata?.startLine || 0,
      endLine: r.metadata?.endLine || 0,
      language: r.metadata?.language || 'unknown',
    })),
    total_indexed: count - 1,
    query,
  };
}

/**
 * Get index stats for a user's workspace.
 *
 * @param {string} daUsername
 * @returns {Promise<object>}
 */
export async function getIndexStats(daUsername) {
  const store = getStore(daUsername);
  const count = await store.count();

  if (count <= 1) {
    return { indexed: false, total_chunks: 0, message: 'Workspace not indexed.' };
  }

  // Count by language and unique files
  await store._load();
  const byLanguage = {};
  const byType = {};
  const uniqueFiles = new Set();
  for (const v of store.vectors) {
    if (v.id === '__hash_index__') continue;
    const lang = v.metadata?.language || 'unknown';
    const type = v.metadata?.type || 'unknown';
    byLanguage[lang] = (byLanguage[lang] || 0) + 1;
    byType[type] = (byType[type] || 0) + 1;
    if (v.metadata?.file) uniqueFiles.add(v.metadata.file);
  }

  return {
    indexed: true,
    total_chunks: count - 1,
    total_files: uniqueFiles.size,
    by_language: byLanguage,
    by_type: byType,
  };
}
