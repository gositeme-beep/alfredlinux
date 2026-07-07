/**
 * vectorStore.js — Lightweight File-Based Vector Store
 *
 * Stores embeddings as JSON files on disk, loads into memory for fast
 * cosine similarity search. No external DB required.
 *
 * Each collection lives in its own directory:
 *   ~/.gocodeme/vectors/{collectionName}/data.json
 *
 * data.json format:
 *   { vectors: [ { id, vector, metadata, text } ... ] }
 *
 * For GoCodeMe's scale (~5K vectors/user), in-memory cosine search
 * is sub-millisecond, so no index (HNSW, etc.) is needed.
 */

import { readFile, writeFile, mkdir } from 'node:fs/promises';
import { existsSync } from 'node:fs';
import path from 'node:path';

/**
 * Cosine similarity between two vectors.
 * @param {number[]} a
 * @param {number[]} b
 * @returns {number} similarity in [-1, 1]
 */
function cosineSimilarity(a, b) {
  let dot = 0, magA = 0, magB = 0;
  for (let i = 0; i < a.length; i++) {
    dot += a[i] * b[i];
    magA += a[i] * a[i];
    magB += b[i] * b[i];
  }
  return dot / (Math.sqrt(magA) * Math.sqrt(magB) + 1e-10);
}

export class VectorStore {
  /**
   * @param {string} baseDir — base directory for all collections (e.g. ~/.gocodeme/vectors)
   * @param {string} collectionName — name of this collection
   */
  constructor(baseDir, collectionName) {
    this.dir = path.join(baseDir, collectionName);
    this.filePath = path.join(this.dir, 'data.json');
    this.vectors = null; // lazy loaded
    this._dirty = false;
    this._saveTimer = null;
  }

  /**
   * Load vectors from disk into memory.
   */
  async _load() {
    if (this.vectors !== null) return;
    try {
      if (existsSync(this.filePath)) {
        const raw = await readFile(this.filePath, 'utf-8');
        const data = JSON.parse(raw);
        this.vectors = data.vectors || [];
      } else {
        this.vectors = [];
      }
    } catch {
      this.vectors = [];
    }
  }

  /**
   * Save vectors to disk (debounced).
   */
  async _save() {
    if (!this._dirty) return;
    await mkdir(this.dir, { recursive: true });
    await writeFile(this.filePath, JSON.stringify({ vectors: this.vectors }, null, 0));
    this._dirty = false;
  }

  /**
   * Schedule a save (debounce 500ms to batch rapid writes).
   */
  _scheduleSave() {
    this._dirty = true;
    if (this._saveTimer) clearTimeout(this._saveTimer);
    this._saveTimer = setTimeout(() => this._save(), 500);
  }

  /**
   * Add or update a vector.
   * @param {string} id — unique ID
   * @param {number[]} vector — embedding vector
   * @param {object} metadata — arbitrary metadata
   * @param {string} text — original text (for display)
   */
  async upsert(id, vector, metadata = {}, text = '') {
    await this._load();
    const idx = this.vectors.findIndex(v => v.id === id);
    const entry = { id, vector, metadata, text };
    if (idx >= 0) {
      this.vectors[idx] = entry;
    } else {
      this.vectors.push(entry);
    }
    this._scheduleSave();
  }

  /**
   * Batch upsert multiple vectors.
   * @param {Array<{id: string, vector: number[], metadata?: object, text?: string}>} items
   */
  async upsertMany(items) {
    await this._load();
    for (const { id, vector, metadata = {}, text = '' } of items) {
      const idx = this.vectors.findIndex(v => v.id === id);
      const entry = { id, vector, metadata, text };
      if (idx >= 0) {
        this.vectors[idx] = entry;
      } else {
        this.vectors.push(entry);
      }
    }
    this._dirty = true;
    await this._save(); // save immediately for batch
  }

  /**
   * Search for the top-K most similar vectors.
   * @param {number[]} queryVector — the query embedding
   * @param {number} topK — number of results
   * @param {object} [filter] — optional metadata filter (key-value exact match)
   * @returns {Array<{id: string, score: number, metadata: object, text: string}>}
   */
  async search(queryVector, topK = 10, filter = null) {
    await this._load();

    let candidates = this.vectors;

    // Apply metadata filter
    if (filter) {
      candidates = candidates.filter(v => {
        for (const [key, val] of Object.entries(filter)) {
          if (v.metadata[key] !== val) return false;
        }
        return true;
      });
    }

    // Score all candidates
    const scored = candidates.map(v => ({
      id: v.id,
      score: cosineSimilarity(queryVector, v.vector),
      metadata: v.metadata,
      text: v.text,
    }));

    // Sort by score descending and take top-K
    scored.sort((a, b) => b.score - a.score);
    return scored.slice(0, topK);
  }

  /**
   * Delete a vector by ID.
   * @param {string} id
   * @returns {boolean} whether it was found and deleted
   */
  async delete(id) {
    await this._load();
    const idx = this.vectors.findIndex(v => v.id === id);
    if (idx < 0) return false;
    this.vectors.splice(idx, 1);
    this._scheduleSave();
    return true;
  }

  /**
   * Delete vectors matching a metadata filter.
   * @param {object} filter — key-value pairs to match
   * @returns {number} number of vectors deleted
   */
  async deleteByFilter(filter) {
    await this._load();
    const before = this.vectors.length;
    this.vectors = this.vectors.filter(v => {
      for (const [key, val] of Object.entries(filter)) {
        if (v.metadata[key] === val) return false;
      }
      return true;
    });
    const deleted = before - this.vectors.length;
    if (deleted > 0) {
      this._dirty = true;
      await this._save();
    }
    return deleted;
  }

  /**
   * Get total count of vectors.
   * @returns {Promise<number>}
   */
  async count() {
    await this._load();
    return this.vectors.length;
  }

  /**
   * Get a vector by ID.
   * @param {string} id
   * @returns {Promise<{id, vector, metadata, text}|null>}
   */
  async get(id) {
    await this._load();
    return this.vectors.find(v => v.id === id) || null;
  }

  /**
   * Clear all vectors.
   */
  async clear() {
    this.vectors = [];
    this._dirty = true;
    await this._save();
  }

  /**
   * Force flush pending writes to disk.
   */
  async flush() {
    if (this._saveTimer) clearTimeout(this._saveTimer);
    await this._save();
  }
}
