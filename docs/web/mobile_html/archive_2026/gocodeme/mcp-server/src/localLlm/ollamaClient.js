/**
 * ollamaClient.js — Ollama REST API Client
 *
 * Communicates with a local Ollama instance for private/offline LLM inference.
 * Ollama runs on port 11434 and supports:
 *   - Chat completions (/api/chat)
 *   - Text generation (/api/generate)
 *   - Embeddings (/api/embed)
 *   - Model management (pull, list, show, delete)
 */

import axios from 'axios';

const OLLAMA_BASE = process.env.OLLAMA_URL || 'http://localhost:11434';

/**
 * Check if Ollama is running.
 */
export async function isOllamaRunning() {
  try {
    const resp = await axios.get(`${OLLAMA_BASE}/api/tags`, { timeout: 5000 });
    return {
      running: true,
      models: (resp.data.models || []).map(m => ({
        name: m.name,
        size: m.size,
        modified: m.modified_at,
      })),
    };
  } catch {
    return { running: false, models: [] };
  }
}

/**
 * Chat with a local model.
 *
 * @param {object} opts
 * @param {Array<{role: string, content: string}>} opts.messages
 * @param {string} [opts.model='qwen2.5:0.5b'] — model name
 * @param {number} [opts.temperature=0.7]
 * @param {number} [opts.maxTokens=2048]
 * @returns {Promise<object>}
 */
export async function chat(opts) {
  const {
    messages,
    model = 'qwen2.5:0.5b',
    temperature = 0.7,
    maxTokens = 2048,
  } = opts;

  if (!messages?.length) throw new Error('messages array is required');

  const start = Date.now();

  try {
    const resp = await axios.post(`${OLLAMA_BASE}/api/chat`, {
      model,
      messages,
      stream: false,
      options: {
        temperature,
        num_predict: maxTokens,
      },
    }, { timeout: 120000 }); // local inference can be slow on CPU

    return {
      status: 'success',
      model,
      message: resp.data.message,
      text: resp.data.message?.content || '',
      totalDuration: resp.data.total_duration,
      evalCount: resp.data.eval_count,
      timing: Date.now() - start,
      local: true,
    };
  } catch (err) {
    if (err.code === 'ECONNREFUSED') {
      return { status: 'error', error: 'Ollama not running. Start with: ollama serve', local: true };
    }
    return { status: 'error', error: err.response?.data?.error || err.message, local: true };
  }
}

/**
 * Generate text completion.
 *
 * @param {object} opts
 * @param {string} opts.prompt
 * @param {string} [opts.model='qwen2.5:0.5b']
 * @param {number} [opts.temperature=0.7]
 * @returns {Promise<object>}
 */
export async function generate(opts) {
  const { prompt, model = 'qwen2.5:0.5b', temperature = 0.7 } = opts;

  const start = Date.now();
  try {
    const resp = await axios.post(`${OLLAMA_BASE}/api/generate`, {
      model,
      prompt,
      stream: false,
      options: { temperature },
    }, { timeout: 120000 });

    return {
      status: 'success',
      model,
      text: resp.data.response || '',
      timing: Date.now() - start,
      local: true,
    };
  } catch (err) {
    return { status: 'error', error: err.response?.data?.error || err.message, local: true };
  }
}

/**
 * Generate embeddings locally.
 *
 * @param {object} opts
 * @param {string} opts.text
 * @param {string} [opts.model='nomic-embed-text']
 * @returns {Promise<object>}
 */
export async function localEmbed(opts) {
  const { text, model = 'nomic-embed-text' } = opts;

  const start = Date.now();
  try {
    const resp = await axios.post(`${OLLAMA_BASE}/api/embed`, {
      model,
      input: text,
    }, { timeout: 30000 });

    return {
      status: 'success',
      model,
      embeddings: resp.data.embeddings,
      dimensions: resp.data.embeddings?.[0]?.length || 0,
      timing: Date.now() - start,
      local: true,
    };
  } catch (err) {
    return { status: 'error', error: err.response?.data?.error || err.message, local: true };
  }
}
