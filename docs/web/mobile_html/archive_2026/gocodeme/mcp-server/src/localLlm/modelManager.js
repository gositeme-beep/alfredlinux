/**
 * modelManager.js — Ollama Model Management
 *
 * Pull, list, inspect, and remove local LLM models.
 * Provides curated list of recommended models grouped by size/capability.
 */

import axios from 'axios';

const OLLAMA_BASE = process.env.OLLAMA_URL || 'http://localhost:11434';

/**
 * Curated model recommendations for different use cases.
 * Focus on small/medium models that work well on CPU (no GPU on this server).
 */
const RECOMMENDED_MODELS = {
  tiny: [
    { name: 'qwen2.5:0.5b', size: '~400MB', desc: 'Fast code & general (0.5B params)', bestFor: 'quick completions, code snippets' },
    { name: 'tinyllama:1.1b', size: '~600MB', desc: 'TinyLlama 1.1B', bestFor: 'basic tasks, fast answers' },
  ],
  small: [
    { name: 'qwen2.5:1.5b', size: '~1GB', desc: 'Qwen 2.5 1.5B code+general', bestFor: 'code generation, summarization' },
    { name: 'phi3:mini', size: '~2GB', desc: 'Microsoft Phi-3 Mini 3.8B', bestFor: 'reasoning, code, math' },
    { name: 'codegemma:2b', size: '~1.5GB', desc: 'Google CodeGemma 2B', bestFor: 'code completion, FIM' },
  ],
  medium: [
    { name: 'qwen2.5:7b', size: '~4.7GB', desc: 'Qwen 2.5 7B', bestFor: 'complex code, analysis' },
    { name: 'llama3.2:3b', size: '~2GB', desc: 'Meta Llama 3.2 3B', bestFor: 'general purpose' },
    { name: 'codellama:7b', size: '~3.8GB', desc: 'Meta CodeLlama 7B', bestFor: 'code generation, debugging' },
    { name: 'mistral:7b', size: '~4.1GB', desc: 'Mistral 7B', bestFor: 'general intelligence' },
    { name: 'deepseek-coder:6.7b', size: '~3.8GB', desc: 'DeepSeek Coder 6.7B', bestFor: 'code generation' },
  ],
  embedding: [
    { name: 'nomic-embed-text', size: '~275MB', desc: 'Nomic 137M embedding', bestFor: 'RAG, semantic search' },
    { name: 'mxbai-embed-large', size: '~670MB', desc: 'Mixed Bread 335M', bestFor: 'high-quality embeddings' },
  ],
};

/**
 * List all locally installed models.
 */
export async function listModels() {
  try {
    const resp = await axios.get(`${OLLAMA_BASE}/api/tags`, { timeout: 5000 });
    const models = (resp.data.models || []).map(m => ({
      name: m.name,
      size: formatBytes(m.size),
      sizeBytes: m.size,
      modified: m.modified_at,
      digest: m.digest?.slice(0, 12),
      family: m.details?.family || 'unknown',
      params: m.details?.parameter_size || 'unknown',
      quantization: m.details?.quantization_level || 'unknown',
    }));

    return {
      status: 'success',
      count: models.length,
      models,
      totalSize: formatBytes(models.reduce((sum, m) => sum + (m.sizeBytes || 0), 0)),
    };
  } catch (err) {
    if (err.code === 'ECONNREFUSED') {
      return { status: 'error', error: 'Ollama not running. Install: curl -fsSL https://ollama.com/install.sh | sh' };
    }
    return { status: 'error', error: err.message };
  }
}

/**
 * Pull (download) a model from Ollama registry.
 *
 * @param {string} modelName - Model to pull (e.g., 'qwen2.5:0.5b')
 * @returns {Promise<object>}
 */
export async function pullModel(modelName) {
  if (!modelName) throw new Error('model name is required');

  try {
    // Check if already installed
    const existing = await listModels();
    if (existing.models?.some(m => m.name === modelName)) {
      return { status: 'success', message: `Model ${modelName} is already installed`, alreadyInstalled: true };
    }

    // Start pull (this can take a LONG time for large models)
    const resp = await axios.post(`${OLLAMA_BASE}/api/pull`, {
      name: modelName,
      stream: false,
    }, { timeout: 600000 }); // 10 minutes max

    return {
      status: 'success',
      message: `Model ${modelName} pulled successfully`,
      details: resp.data,
    };
  } catch (err) {
    return {
      status: 'error',
      error: err.response?.data?.error || err.message,
      hint: err.code === 'ECONNREFUSED' ? 'Ollama not running' : undefined,
    };
  }
}

/**
 * Remove a model.
 *
 * @param {string} modelName
 * @returns {Promise<object>}
 */
export async function removeModel(modelName) {
  if (!modelName) throw new Error('model name is required');

  try {
    await axios.delete(`${OLLAMA_BASE}/api/delete`, {
      data: { name: modelName },
      timeout: 30000,
    });

    return { status: 'success', message: `Model ${modelName} removed` };
  } catch (err) {
    return { status: 'error', error: err.response?.data?.error || err.message };
  }
}

/**
 * Show model details (modelfile, parameters, template, license).
 *
 * @param {string} modelName
 * @returns {Promise<object>}
 */
export async function showModel(modelName) {
  if (!modelName) throw new Error('model name is required');

  try {
    const resp = await axios.post(`${OLLAMA_BASE}/api/show`, {
      name: modelName,
    }, { timeout: 10000 });

    const info = resp.data;
    return {
      status: 'success',
      name: modelName,
      family: info.details?.family,
      parameters: info.details?.parameter_size,
      quantization: info.details?.quantization_level,
      format: info.details?.format,
      template: info.template?.slice(0, 200), // truncate
      license: info.license?.slice(0, 300),     // truncate
      system: info.system?.slice(0, 200),
    };
  } catch (err) {
    return { status: 'error', error: err.response?.data?.error || err.message };
  }
}

/**
 * Get recommended models list.
 *
 * @param {string} [category] — 'tiny', 'small', 'medium', 'embedding', or undefined for all
 * @returns {object}
 */
export function getRecommendedModels(category) {
  if (category && RECOMMENDED_MODELS[category]) {
    return { category, models: RECOMMENDED_MODELS[category] };
  }
  return RECOMMENDED_MODELS;
}

/**
 * Create a custom model with a Modelfile (system prompt, template, etc.).
 *
 * @param {object} opts
 * @param {string} opts.name — new model name (e.g., 'alfred-coder')
 * @param {string} opts.from — base model (e.g., 'qwen2.5:0.5b')
 * @param {string} [opts.system] — system prompt
 * @param {number} [opts.temperature=0.7]
 * @returns {Promise<object>}
 */
export async function createCustomModel(opts) {
  const { name, from, system, temperature = 0.7 } = opts;
  if (!name || !from) throw new Error('name and from (base model) are required');

  const modelfile = [
    `FROM ${from}`,
    `PARAMETER temperature ${temperature}`,
    system ? `SYSTEM """${system}"""` : '',
  ].filter(Boolean).join('\n');

  try {
    const resp = await axios.post(`${OLLAMA_BASE}/api/create`, {
      name,
      modelfile,
      stream: false,
    }, { timeout: 120000 });

    return { status: 'success', message: `Custom model ${name} created from ${from}`, details: resp.data };
  } catch (err) {
    return { status: 'error', error: err.response?.data?.error || err.message };
  }
}

/* ---- helpers ---- */

function formatBytes(bytes) {
  if (!bytes) return '0 B';
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
  const i = Math.floor(Math.log(bytes) / Math.log(1024));
  return `${(bytes / Math.pow(1024, i)).toFixed(1)} ${sizes[i]}`;
}
