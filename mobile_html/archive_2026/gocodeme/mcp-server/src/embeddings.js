/**
 * embeddings.js — Local Embedding Engine (Transformers.js)
 *
 * Runs all-MiniLM-L6-v2 locally via ONNX runtime — no external API needed.
 * ~23MB model, 384 dimensions, runs on CPU in ~5ms per embedding.
 *
 * Used by ELEPHANT (memory) and ORACLE (semantic search).
 */

import { pipeline } from '@xenova/transformers';

const MODEL = 'Xenova/all-MiniLM-L6-v2';
const DIMENSION = 384;

// ── Lazy-loaded pipeline singleton ──────────────────────────────────────────
let _pipeline = null;
let _loading = null;

async function getPipeline() {
  if (_pipeline) return _pipeline;
  if (_loading) return _loading;
  _loading = pipeline('feature-extraction', MODEL, {
    quantized: true, // use quantized model for speed
  });
  _pipeline = await _loading;
  _loading = null;
  return _pipeline;
}

/**
 * Embed one or more texts into vectors.
 * @param {string|string[]} input — single string or array of strings
 * @returns {Promise<number[][]>} — array of embedding vectors (384-dim each)
 */
export async function embed(input) {
  const texts = Array.isArray(input) ? input : [input];
  if (texts.length === 0) return [];

  const extractor = await getPipeline();
  const allEmbeddings = [];

  // Process in batches of 32 for memory efficiency
  for (let i = 0; i < texts.length; i += 32) {
    const batch = texts.slice(i, i + 32);

    for (const text of batch) {
      // Truncate very long texts to avoid OOM (model max is ~512 tokens)
      const truncated = text.slice(0, 2048);
      const result = await extractor(truncated, {
        pooling: 'mean',
        normalize: true,
      });
      // result.data is a Float32Array, convert to regular array
      allEmbeddings.push(Array.from(result.data));
      // Yield to the event loop between embeddings to avoid blocking other requests
      await new Promise(r => setImmediate(r));
    }
  }

  return allEmbeddings;
}

/**
 * Embed a single text and return one vector.
 * @param {string} text
 * @returns {Promise<number[]>}
 */
export async function embedOne(text) {
  const results = await embed(text);
  return results[0];
}

/**
 * Pre-warm the model (call at startup to avoid cold-start latency).
 */
export async function warmup() {
  await getPipeline();
}

/**
 * The embedding dimension for this model.
 */
export { DIMENSION };
