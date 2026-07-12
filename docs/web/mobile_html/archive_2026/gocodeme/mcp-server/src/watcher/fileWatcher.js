/**
 * fileWatcher.js — ORACLE Auto-Reindex File Watcher
 *
 * Watches the user's workspace for file changes and incrementally
 * re-indexes modified files into the semantic search index.
 *
 * Uses Node.js native fs.watch (recursive) with debouncing to avoid
 * thrashing on rapid saves. Only re-embeds changed chunks.
 *
 * Architecture:
 *  - Single watcher per user workspace (started on first use)
 *  - Debounced: collects changes over 5s window, then batch re-embeds
 *  - Respects the same SKIP_DIRS and file classification as codeIndexer
 *  - Non-blocking: runs in background, doesn't slow MCP request handling
 */

import { watch } from 'node:fs';
import { readFile, stat } from 'node:fs/promises';
import { createHash } from 'node:crypto';
import path from 'node:path';
import { VectorStore } from '../vectorStore.js';
import { embed } from '../embeddings.js';

const VECTORS_BASE = '/home/gositeme/.gocodeme/vectors';
const DEBOUNCE_MS = 5000;   // collect changes for 5s before re-indexing
const MAX_FILE_SIZE = 256 * 1024;
const MAX_CHUNK_SIZE = 1500;

const CODE_EXTENSIONS = new Set([
  '.js', '.ts', '.jsx', '.tsx', '.mjs', '.cjs',
  '.py', '.pyw', '.php', '.phtml',
  '.rb', '.go', '.rs', '.java', '.kt', '.scala',
  '.c', '.cpp', '.h', '.hpp', '.cs',
  '.swift', '.sh', '.bash', '.sql',
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

// Active watchers per user
const watchers = new Map();

function classify(filename) {
  const ext = path.extname(filename).toLowerCase();
  if (CODE_EXTENSIONS.has(ext)) return 'code';
  if (CONFIG_EXTENSIONS.has(ext)) return 'config';
  if (DOC_EXTENSIONS.has(ext)) return 'doc';
  return 'skip';
}

function shouldSkip(filePath) {
  const parts = filePath.split(path.sep);
  return parts.some(p => SKIP_DIRS.has(p) || p.startsWith('.'));
}

function hashContent(content) {
  return createHash('sha256').update(content).digest('hex').slice(0, 16);
}

function simpleChunk(content, filePath) {
  const lines = content.split('\n');
  const chunks = [];
  const chunkSize = 40; // ~40 lines per chunk
  for (let i = 0; i < lines.length; i += chunkSize - 3) {
    const slice = lines.slice(i, i + chunkSize);
    const text = slice.join('\n');
    if (text.trim().length < 20) continue;
    chunks.push({
      text: `File: ${filePath}\n${text}`.slice(0, MAX_CHUNK_SIZE),
      startLine: i + 1,
      endLine: Math.min(i + chunkSize, lines.length),
    });
  }
  return chunks;
}

/**
 * Start watching a user's workspace for changes.
 *
 * @param {string} daUsername
 * @param {string} homeDir
 * @returns {{ active: boolean, message: string }}
 */
export function startWatcher(daUsername, homeDir) {
  if (watchers.has(daUsername)) {
    return { active: true, message: `Watcher already active for ${daUsername}` };
  }

  const workspaceDir = path.join(homeDir, 'public_html');
  const store = new VectorStore(VECTORS_BASE, `codebase_${daUsername}`);
  const pendingChanges = new Map(); // relPath → fullPath
  let debounceTimer = null;

  async function processChanges() {
    if (pendingChanges.size === 0) return;

    const changes = new Map(pendingChanges);
    pendingChanges.clear();

    // Load hash index
    const hashEntry = await store.get('__hash_index__');
    const fileHashes = hashEntry ? JSON.parse(hashEntry.text || '{}') : {};
    let reindexed = 0;

    const pendingEmbeddings = [];

    for (const [relPath, fullPath] of changes) {
      try {
        const fileStat = await stat(fullPath);
        if (fileStat.size > MAX_FILE_SIZE || fileStat.size === 0) continue;

        const content = await readFile(fullPath, 'utf-8');
        const hash = hashContent(content);

        // Skip if hash unchanged
        if (fileHashes[relPath] === hash) continue;

        fileHashes[relPath] = hash;

        // Delete old chunks for this file
        await store.deleteByFilter({ file: relPath });

        // Re-chunk
        const type = classify(path.basename(fullPath));
        const chunks = simpleChunk(content, relPath);
        const ext = path.extname(fullPath).slice(1);

        for (let i = 0; i < chunks.length; i++) {
          pendingEmbeddings.push({
            id: `${relPath}:${i}`,
            text: chunks[i].text,
            metadata: {
              file: relPath,
              type,
              chunk: i,
              startLine: chunks[i].startLine,
              endLine: chunks[i].endLine,
              language: ext,
            },
          });
        }
        reindexed++;
      } catch {
        // File may have been deleted — remove from index
        await store.deleteByFilter({ file: relPath });
        delete fileHashes[relPath];
      }
    }

    // Batch embed
    if (pendingEmbeddings.length > 0) {
      try {
        const texts = pendingEmbeddings.map(e => e.text);
        const vectors = await embed(texts);
        const items = pendingEmbeddings.map((e, i) => ({
          id: e.id,
          vector: vectors[i],
          metadata: e.metadata,
          text: e.text,
        }));
        await store.upsertMany(items);
      } catch (err) {
        console.error(`[WATCHER] Embedding failed: ${err.message}`);
      }
    }

    // Update hash index
    await store.upsert('__hash_index__', new Array(384).fill(0), { type: 'index' }, JSON.stringify(fileHashes));
    await store.flush();

    if (reindexed > 0) {
      console.error(`[WATCHER] Auto-reindexed ${reindexed} files (${pendingEmbeddings.length} chunks) for ${daUsername}`);
    }
  }

  try {
    const watcher = watch(workspaceDir, { recursive: true }, (eventType, filename) => {
      if (!filename) return;

      const fullPath = path.join(workspaceDir, filename);
      const relPath = path.relative(homeDir, fullPath);

      // Skip non-indexable files
      if (shouldSkip(relPath)) return;
      if (classify(path.basename(filename)) === 'skip') return;

      pendingChanges.set(relPath, fullPath);

      // Debounce
      if (debounceTimer) clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        processChanges().catch(err =>
          console.error(`[WATCHER] Process error: ${err.message}`)
        );
      }, DEBOUNCE_MS);
    });

    watcher.on('error', (err) => {
      console.error(`[WATCHER] Watch error for ${daUsername}: ${err.message}`);
    });

    watchers.set(daUsername, {
      watcher,
      workspaceDir,
      startedAt: new Date().toISOString(),
    });

    console.error(`[WATCHER] Started watching ${workspaceDir} for ${daUsername}`);
    return { active: true, message: `File watcher started for ${workspaceDir}. Changes auto-reindex after ${DEBOUNCE_MS / 1000}s.` };
  } catch (err) {
    return { active: false, message: `Failed to start watcher: ${err.message}` };
  }
}

/**
 * Stop watching a user's workspace.
 * @param {string} daUsername
 */
export function stopWatcher(daUsername) {
  const entry = watchers.get(daUsername);
  if (!entry) return { active: false, message: 'No active watcher.' };

  entry.watcher.close();
  watchers.delete(daUsername);
  console.error(`[WATCHER] Stopped watching for ${daUsername}`);
  return { active: false, message: 'File watcher stopped.' };
}

/**
 * Get watcher status for a user.
 * @param {string} daUsername
 */
export function getWatcherStatus(daUsername) {
  const entry = watchers.get(daUsername);
  if (!entry) return { active: false };
  return {
    active: true,
    workspaceDir: entry.workspaceDir,
    startedAt: entry.startedAt,
  };
}
