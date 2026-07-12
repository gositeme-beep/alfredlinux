/**
 * memoryEngine.js — ELEPHANT: Alfred's Persistent Long-Term Memory
 *
 * Stores per-user memories (facts, preferences, decisions, lessons, project context)
 * as vector embeddings for semantic retrieval.
 *
 * Storage: ~/.gocodeme/vectors/memories_{daUsername}/data.json
 *
 * Memory lifecycle:
 *  1. Alfred calls alfred_remember("User prefers TypeScript with no semicolons")
 *  2. Text is embedded via Together AI → stored in vector store
 *  3. Next session: alfred_recall("coding style") → retrieves top-K relevant memories
 *  4. User can delete: alfred_forget("memory_id") or alfred_forget("all")
 */

import { VectorStore } from '../vectorStore.js';
import { embedOne } from '../embeddings.js';
import { randomUUID } from 'node:crypto';
import path from 'node:path';
import { pruneMemories, formatMemoryBlock } from './contextPruner.js';

const VECTORS_BASE = '/home/gositeme/.gocodeme/vectors';

/**
 * Get or create a memory store for a given user.
 * @param {string} daUsername — DirectAdmin username
 * @returns {VectorStore}
 */
function getStore(daUsername) {
  return new VectorStore(VECTORS_BASE, `memories_${daUsername}`);
}

/**
 * Remember a fact, preference, decision, or lesson.
 *
 * @param {string} daUsername — user
 * @param {string} text — the memory text (e.g. "User prefers tabs over spaces")
 * @param {string} [category='general'] — one of: fact, preference, decision, lesson, project, general
 * @param {object} [extra] — optional extra metadata
 * @returns {Promise<{id: string, message: string}>}
 */
export async function remember(daUsername, text, category = 'general', extra = {}) {
  const store = getStore(daUsername);
  const id = `mem_${randomUUID().slice(0, 12)}`;

  // Embed the memory text
  const vector = await embedOne(text);

  await store.upsert(id, vector, {
    category,
    created: new Date().toISOString(),
    ...extra,
  }, text);

  const count = await store.count();
  await store.flush();

  return {
    id,
    message: `Memory saved (${category}). You now have ${count} memories for this user.`,
  };
}

/**
 * Recall memories relevant to a query.
 *
 * @param {string} daUsername — user
 * @param {string} query — what to remember about
 * @param {number} [topK=10] — number of results
 * @param {string} [category] — optional category filter
 * @returns {Promise<Array<{id: string, text: string, category: string, score: number, created: string}>>}
 */
export async function recall(daUsername, query, topK = 10, category = null) {
  const store = getStore(daUsername);

  // Embed the query
  const queryVector = await embedOne(query);

  // Search
  const filter = category ? { category } : null;
  const results = await store.search(queryVector, topK, filter);

  return results.map(r => ({
    id: r.id,
    text: r.text,
    category: r.metadata.category || 'general',
    score: Math.round(r.score * 1000) / 1000,
    created: r.metadata.created || 'unknown',
  }));
}

/**
 * Forget (delete) a specific memory or all memories.
 *
 * @param {string} daUsername — user
 * @param {string} memoryId — memory ID to delete, or "all" to clear everything
 * @returns {Promise<{deleted: number, message: string}>}
 */
export async function forget(daUsername, memoryId) {
  const store = getStore(daUsername);

  if (memoryId === 'all') {
    const count = await store.count();
    await store.clear();
    return { deleted: count, message: `All ${count} memories deleted.` };
  }

  const found = await store.delete(memoryId);
  await store.flush();
  return {
    deleted: found ? 1 : 0,
    message: found ? `Memory ${memoryId} deleted.` : `Memory ${memoryId} not found.`,
  };
}

/**
 * Get a summary of all memories (count by category).
 *
 * @param {string} daUsername
 * @returns {Promise<{total: number, byCategory: object}>}
 */
export async function memorySummary(daUsername) {
  const store = getStore(daUsername);
  await store._load();
  const total = store.vectors.length;
  const by_category = {};
  const memories = [];
  for (const v of store.vectors) {
    const cat = v.metadata?.category || 'general';
    by_category[cat] = (by_category[cat] || 0) + 1;
    memories.push({
      id: v.id,
      text: v.text || '',
      category: cat,
      saved: v.metadata?.saved || '',
    });
  }
  return { total, by_category, memories };
}

/**
 * Get top memories for injection into system prompt (auto-recall).
 * This is called at session start to pre-load relevant context.
 *
 * @param {string} daUsername
 * @param {string} contextHint — e.g. first user message or project description
 * @param {number} [topK=15]
 * @returns {Promise<string>} — formatted memory block for system prompt
 */
export async function getMemoryContext(daUsername, contextHint, topK = 20) {
  const store = getStore(daUsername);
  const count = await store.count();
  if (count === 0) return '';

  // Fetch more candidates than needed — the pruner will rank and trim
  const memories = await recall(daUsername, contextHint, topK);
  if (memories.length === 0) return '';

  // Smart pruning: rank by relevance × recency × category priority,
  // deduplicate near-identical memories, respect token budget
  const pruned = pruneMemories(memories, {
    maxChars: 6000,     // ~1500 tokens
    maxMemories: 12,
    conversationContext: contextHint,
  });

  return formatMemoryBlock(pruned);
}
