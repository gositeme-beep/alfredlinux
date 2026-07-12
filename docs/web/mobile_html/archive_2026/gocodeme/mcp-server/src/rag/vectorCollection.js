/**
 * vectorCollection.js — Named Vector Collections for RAG
 *
 * Manages multiple named collections on top of the existing VectorStore.
 * Each collection has its own vector store, metadata, and lifecycle.
 *
 * Collections are stored in: ~/.gocodeme/rag/{collectionName}/
 *   - data.json (vectors via VectorStore)
 *   - meta.json (collection metadata: doc count, created, description)
 */

import { readFile, writeFile, mkdir, readdir, rm } from 'node:fs/promises';
import { existsSync } from 'node:fs';
import path from 'node:path';
import os from 'node:os';
import { VectorStore } from '../vectorStore.js';

const RAG_BASE = path.join(os.homedir(), '.gocodeme', 'rag');

// Cache of open stores: Map<collectionName, VectorStore>
const stores = new Map();

/**
 * Get or create a VectorStore for a collection.
 */
function getStore(collectionName) {
  if (!stores.has(collectionName)) {
    stores.set(collectionName, new VectorStore(RAG_BASE, collectionName));
  }
  return stores.get(collectionName);
}

/**
 * Get the metadata file path for a collection.
 */
function metaPath(collectionName) {
  return path.join(RAG_BASE, collectionName, 'meta.json');
}

/**
 * Read collection metadata.
 */
async function readMeta(collectionName) {
  const mp = metaPath(collectionName);
  try {
    if (existsSync(mp)) {
      return JSON.parse(await readFile(mp, 'utf-8'));
    }
  } catch { /* ignore */ }
  return null;
}

/**
 * Write collection metadata.
 */
async function writeMeta(collectionName, meta) {
  const dir = path.join(RAG_BASE, collectionName);
  await mkdir(dir, { recursive: true });
  await writeFile(metaPath(collectionName), JSON.stringify(meta, null, 2));
}

/**
 * Create or get a collection.
 * @param {string} name — collection name (alphanumeric + hyphens)
 * @param {string} [description=''] — optional description
 * @returns {Promise<{store: VectorStore, meta: object}>}
 */
export async function getCollection(name, description = '') {
  const safeName = name.replace(/[^a-zA-Z0-9_-]/g, '-').slice(0, 64);
  const store = getStore(safeName);

  let meta = await readMeta(safeName);
  if (!meta) {
    meta = {
      name: safeName,
      description,
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString(),
      documentCount: 0,
      chunkCount: 0,
    };
    await writeMeta(safeName, meta);
  }

  return { store, meta };
}

/**
 * Add chunks to a collection.
 * @param {string} collectionName
 * @param {Array<{text: string, index: number, metadata: object}>} chunks — from chunker
 * @param {number[][]} embeddings — embedding vectors for each chunk
 * @returns {Promise<{added: number}>}
 */
export async function addChunks(collectionName, chunks, embeddings) {
  const { store, meta } = await getCollection(collectionName);

  const items = chunks.map((chunk, i) => ({
    id: `${chunk.metadata.fileName || 'doc'}-${chunk.index}-${Date.now()}`,
    vector: embeddings[i],
    metadata: chunk.metadata,
    text: chunk.text,
  }));

  await store.upsertMany(items);

  // Update meta
  meta.chunkCount = await store.count();
  meta.documentCount += 1; // increment doc count (approximate — one ingest = one doc)
  meta.updatedAt = new Date().toISOString();
  await writeMeta(collectionName, meta);

  return { added: items.length };
}

/**
 * Search a collection.
 * @param {string} collectionName
 * @param {number[]} queryVector — query embedding
 * @param {number} [topK=10]
 * @param {object} [filter=null]
 * @returns {Promise<Array<{id, score, metadata, text}>>}
 */
export async function searchCollection(collectionName, queryVector, topK = 10, filter = null) {
  const { store } = await getCollection(collectionName);
  return store.search(queryVector, topK, filter);
}

/**
 * List all collections.
 * @returns {Promise<Array<object>>}
 */
export async function listCollections() {
  if (!existsSync(RAG_BASE)) return [];

  const dirs = await readdir(RAG_BASE, { withFileTypes: true });
  const collections = [];

  for (const dir of dirs) {
    if (!dir.isDirectory()) continue;
    const meta = await readMeta(dir.name);
    if (meta) {
      collections.push(meta);
    }
  }

  return collections;
}

/**
 * Delete a collection entirely.
 * @param {string} collectionName
 * @returns {Promise<boolean>}
 */
export async function deleteCollection(collectionName) {
  const safeName = collectionName.replace(/[^a-zA-Z0-9_-]/g, '-').slice(0, 64);
  const dir = path.join(RAG_BASE, safeName);

  if (!existsSync(dir)) return false;

  await rm(dir, { recursive: true, force: true });
  stores.delete(safeName);
  return true;
}

/**
 * Delete documents from a collection matching a source filter.
 * @param {string} collectionName
 * @param {string} source — source path/URL to match
 * @returns {Promise<number>} — number of chunks deleted
 */
export async function deleteDocumentBySource(collectionName, source) {
  const { store, meta } = await getCollection(collectionName);
  const deleted = await store.deleteByFilter({ source });

  if (deleted > 0) {
    meta.chunkCount = await store.count();
    meta.documentCount = Math.max(0, meta.documentCount - 1);
    meta.updatedAt = new Date().toISOString();
    await writeMeta(collectionName, meta);
  }

  return deleted;
}

/**
 * Get stats for a collection.
 * @param {string} collectionName
 * @returns {Promise<object>}
 */
export async function getCollectionStats(collectionName) {
  const { store, meta } = await getCollection(collectionName);
  const count = await store.count();
  return {
    ...meta,
    chunkCount: count,
  };
}
