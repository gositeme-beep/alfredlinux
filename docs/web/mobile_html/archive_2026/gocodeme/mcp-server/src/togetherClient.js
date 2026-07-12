/**
 * togetherClient.js — Shared Together.ai API Client for Alfred AI
 *
 * Together.ai is Alfred's AI engine — his brain, voice, ears, and eyes.
 * This module provides a unified interface for all Together.ai endpoints:
 *   - Chat completions (LLM inference)
 *   - Image generation (27 models)
 *   - Video generation (23 models)  [v2 async/polling API]
 *   - Speech-to-text (Whisper)
 *   - Text-to-speech (Kokoro, Cartesia, Orpheus)
 *   - Vision (Qwen3-VL)
 *   - Embeddings
 *   - Reranking
 *
 * Verified serverless models (as of 2025):
 *   Embeddings : intfloat/multilingual-e5-large-instruct, BAAI/bge-large-en-v1.5
 *   Rerank     : mixedbread-ai/Mxbai-Rerank-Large-V2
 *   Vision     : Qwen/Qwen3-VL-32B-Instruct (primary), Qwen/Qwen3-VL-8B-Instruct (fallback)
 */

import axios from 'axios';

const API_BASE = 'https://api.together.xyz/v1';

function getKey() {
  const key = process.env.TOGETHER_API_KEY;
  if (!key) throw new Error('TOGETHER_API_KEY not set in environment');
  return key;
}

function headers() {
  return {
    Authorization: `Bearer ${getKey()}`,
    'Content-Type': 'application/json',
  };
}

// ── Image Generation Models ─────────────────────────────────────────────────
export const IMAGE_MODELS = {
  // FLUX family
  'flux-schnell':       'black-forest-labs/FLUX.1-schnell',
  'flux-pro':           'black-forest-labs/FLUX.1-pro',
  'flux-1.1-pro':       'black-forest-labs/FLUX.1.1-pro',
  'flux-2-dev':         'black-forest-labs/FLUX.2-dev',
  'flux-2-flex':        'black-forest-labs/FLUX.2-flex',
  'flux-2-pro':         'black-forest-labs/FLUX.2-pro',
  'flux-krea-dev':      'black-forest-labs/FLUX.1-krea-dev',
  'flux-kontext-pro':   'black-forest-labs/FLUX.1-kontext-pro',
  'flux-kontext-max':   'black-forest-labs/FLUX.1-kontext-max',
  // Stable Diffusion family
  'sd-3-medium':        'stabilityai/stable-diffusion-3-medium',
  'sdxl-1.0':           'stabilityai/stable-diffusion-xl-base-1.0',
  // Google
  'imagen-4-fast':      'google/imagen-4.0-fast',
  'imagen-4-preview':   'google/imagen-4.0-preview',
  'imagen-4-ultra':     'google/imagen-4.0-ultra',
  'flash-image':        'google/flash-image-2.5',
  'gemini-3-image':     'google/gemini-3-pro-image',
  // Specialized
  'ideogram-3':         'ideogram/ideogram-3.0',
  'seedream-3':         'ByteDance-Seed/Seedream-3.0',
  'seedream-4':         'ByteDance-Seed/Seedream-4.0',
  'hidream-dev':        'HiDream-ai/HiDream-I1-Dev',
  'hidream-fast':       'HiDream-ai/HiDream-I1-Fast',
  'hidream-full':       'HiDream-ai/HiDream-I1-Full',
  'qwen-image':         'Qwen/Qwen-Image',
  'wan-image':          'Wan-AI/Wan2.6-image',
  'juggernaut-pro':     'RunDiffusion/Juggernaut-pro-flux',
  'juggernaut-lightning': 'Rundiffusion/Juggernaut-Lightning-Flux',
  'dreamshaper':        'Lykon/DreamShaper',
  // Default
  'default':            'black-forest-labs/FLUX.1-schnell',
};

// ── Video Generation Models ─────────────────────────────────────────────────
export const VIDEO_MODELS = {
  // Wan-AI
  'wan-t2v':            'Wan-AI/Wan2.2-T2V-A14B',
  'wan-i2v':            'Wan-AI/Wan2.2-I2V-A14B',
  // Minimax / Hailuo
  'hailuo-02':          'minimax/hailuo-02',
  'hailuo-director':    'minimax/video-01-director',
  // Kling
  'kling-1.6-standard': 'kwaivgI/kling-1.6-standard',
  'kling-1.6-pro':      'kwaivgI/kling-1.6-pro',
  'kling-2.0-master':   'kwaivgI/kling-2.0-master',
  'kling-2.1-standard': 'kwaivgI/kling-2.1-standard',
  'kling-2.1-master':   'kwaivgI/kling-2.1-master',
  'kling-2.1-pro':      'kwaivgI/kling-2.1-pro',
  // Google Veo
  'veo-2':              'google/veo-2.0',
  'veo-3':              'google/veo-3.0',
  'veo-3-fast':         'google/veo-3.0-fast',
  'veo-3-audio':        'google/veo-3.0-audio',
  'veo-3-fast-audio':   'google/veo-3.0-fast-audio',
  // ByteDance Seedance
  'seedance-pro':       'ByteDance/Seedance-1.0-pro',
  'seedance-lite':      'ByteDance/Seedance-1.0-lite',
  // PixVerse
  'pixverse-v5':        'pixverse/pixverse-v5',
  'pixverse-v5.6':      'pixverse/pixverse-v5.6',
  // OpenAI Sora
  'sora-2':             'openai/sora-2',
  'sora-2-pro':         'openai/sora-2-pro',
  // Vidu
  'vidu-2':             'vidu/vidu-2.0',
  'vidu-q1':            'vidu/vidu-q1',
  // Default
  'default':            'Wan-AI/Wan2.2-T2V-A14B',
};

// ── Audio/TTS Models ────────────────────────────────────────────────────────
// Together.ai /audio/speech supports exactly 3 models:
//   hexgrad/Kokoro-82M  (voices: af_alloy, am_echo, bm_fable, am_onyx, af_nova, af_sky, …)
//   cartesia/sonic      (voices: "friendly sidekick", "helpful woman", "nonfiction man", …)
//   canopylabs/orpheus-3b-0.1-ft  (voices: tara, leah, jess, leo, dan, mia, zac, zoe)
export const AUDIO_MODELS = {
  'kokoro':             'hexgrad/Kokoro-82M',
  'cartesia-sonic':     'cartesia/sonic',
  'cartesia-sonic-2':   'cartesia/sonic',
  'orpheus':            'canopylabs/orpheus-3b-0.1-ft',
  'minimax-speech':     'hexgrad/Kokoro-82M',
  'default':            'hexgrad/Kokoro-82M',
};

// ── Voice name normalisation ─────────────────────────────────────────────────
const KOKORO_VOICE_MAP = {
  'alloy':   'af_alloy',
  'echo':    'am_echo',
  'fable':   'bm_fable',
  'onyx':    'am_onyx',
  'nova':    'af_nova',
  'shimmer': 'af_sky',
};

const CARTESIA_VOICE_MAP = {
  'alloy':   'friendly sidekick',
  'echo':    'reading man',
  'fable':   'newsman',
  'onyx':    'nonfiction man',
  'nova':    'helpful woman',
  'shimmer': 'calm lady',
};

const ORPHEUS_VOICES = new Set(['tara', 'leah', 'jess', 'leo', 'dan', 'mia', 'zac', 'zoe']);

function resolveVoice(modelId, voice) {
  const v = (voice || '').toLowerCase().trim();
  if (modelId === 'hexgrad/Kokoro-82M') {
    if (/^[a-z]{2}_/.test(v)) return v;
    return KOKORO_VOICE_MAP[v] || 'af_alloy';
  }
  if (modelId === 'cartesia/sonic') {
    if (v.includes(' ')) return v;
    return CARTESIA_VOICE_MAP[v] || 'helpful woman';
  }
  if (modelId === 'canopylabs/orpheus-3b-0.1-ft') {
    return ORPHEUS_VOICES.has(v) ? v : 'tara';
  }
  return voice || 'alloy';
}

// ── Vision Models ───────────────────────────────────────────────────────────
// Only serverless-available models listed.
// 32B is primary; 8B is the automatic fallback on 503.
export const VISION_MODELS = {
  'qwen3-vl':      'Qwen/Qwen3-VL-32B-Instruct',
  'qwen3-vl-8b':   'Qwen/Qwen3-VL-8B-Instruct',
  'llama-scout':   'Qwen/Qwen3-VL-8B-Instruct', // alias kept, remapped to working model
  'qwen2.5-vl':    'Qwen/Qwen3-VL-32B-Instruct', // alias kept, remapped to working model
  'default':       'Qwen/Qwen3-VL-32B-Instruct',
};

// Fallback chain for vision: if primary returns 503 try these in order
const VISION_FALLBACK = [
  'Qwen/Qwen3-VL-32B-Instruct',
  'Qwen/Qwen3-VL-8B-Instruct',
];

// ── LLM Models ──────────────────────────────────────────────────────────────
export const LLM_MODELS = {
  'llama-3.3-70b':  'meta-llama/Llama-3.3-70B-Instruct-Turbo',
  'llama-scout':    'meta-llama/Llama-4-Scout-17B-16E-Instruct',
  'llama-maverick': 'meta-llama/Llama-4-Maverick-17B-128E-Instruct-FP8',
  'deepseek-v3.1':  'deepseek-ai/DeepSeek-V3-0324',
  'qwen3-coder':    'Qwen/Qwen3-Coder-480B-A35B-Instruct',
  'deepseek-r1':    'deepseek-ai/DeepSeek-R1',
  'kimi-k2':        'moonshotai/Kimi-K2-Instruct',
  'default':        'meta-llama/Llama-3.3-70B-Instruct-Turbo',
};

// ══════════════════════════════════════════════════════════════════════════════
// API Methods
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Generate an image via Together.ai
 */
export async function generateImage(prompt, model = 'default', width = 1024, height = 1024, steps = 4, n = 1) {
  const modelId = IMAGE_MODELS[model] || model;
  const start = Date.now();

  const response = await axios.post(`${API_BASE}/images/generations`, {
    model: modelId,
    prompt,
    width: Math.min(width, 1440),
    height: Math.min(height, 1440),
    steps: Math.min(Math.max(steps, 1), 50),
    n,
    response_format: 'b64_json',
  }, { headers: headers(), timeout: 120000 });

  const images = (response.data?.data || []).map(d => Buffer.from(d.b64_json, 'base64'));
  if (images.length === 0) throw new Error('No image data in Together.ai response');

  return { images, model: modelId, timing: Date.now() - start };
}

/**
 * Generate a video via Together.ai  (v2 async/polling API)
 *
 * Together.ai video generation is async:
 *   POST /v2/videos        → { id }      (job submitted)
 *   GET  /v2/videos/{id}   → VideoJob    (poll until completed | failed)
 *
 * Payload notes:
 *   - 'payload' field is required (even as {})
 *   - duration is 'seconds' (string, "1"–"10")
 *   - image-to-video uses 'frame_images', not 'image_url'
 */
export async function generateVideo(prompt, model = 'default', duration = 5, imageUrl = null) {
  const modelId = VIDEO_MODELS[model] || model;
  const VIDEO_BASE = 'https://api.together.xyz/v2/videos';
  const start = Date.now();

  const body = {
    model: modelId,
    prompt,
    payload: {},
    seconds: String(Math.min(Math.max(Math.round(duration), 1), 10)),
  };

  if (imageUrl) {
    body.frame_images = [{ image: imageUrl }];
  }

  // Step 1: Submit
  const submitResp = await axios.post(VIDEO_BASE, body, {
    headers: headers(),
    timeout: 30000,
  });

  const jobId = submitResp.data?.id;
  if (!jobId) {
    throw new Error(
      `Together video API did not return a job ID. Response: ${JSON.stringify(submitResp.data)}`
    );
  }

  // Step 2: Poll until completed or failed (5s interval, 10min max)
  const POLL_INTERVAL_MS = 5000;
  const deadline = Date.now() + 600000;

  while (Date.now() < deadline) {
    await new Promise(resolve => setTimeout(resolve, POLL_INTERVAL_MS));

    const pollResp = await axios.get(`${VIDEO_BASE}/${jobId}`, {
      headers: headers(),
      timeout: 15000,
    });

    const job = pollResp.data;

    if (job.status === 'completed') {
      const videoUrl = job.outputs?.video_url;
      if (!videoUrl) {
        throw new Error(
          `Video job completed but outputs.video_url is missing: ${JSON.stringify(job)}`
        );
      }
      return { videoUrl, model: modelId, timing: Date.now() - start, jobId };
    }

    if (job.status === 'failed' || job.status === 'cancelled') {
      throw new Error(
        `Together video generation ${job.status}: ${job.error?.message || job.status}`
      );
    }
    // status 'queued' | 'in_progress' — keep polling
  }

  throw new Error(`Together video generation timed out after 10 minutes (job: ${jobId})`);
}

/**
 * Generate speech (TTS) via Together.ai
 */
export async function generateSpeech(text, model = 'default', voice = 'alloy') {
  const modelId = AUDIO_MODELS[model] || model;
  const resolvedVoice = resolveVoice(modelId, voice);
  const start = Date.now();

  const response = await axios.post(`${API_BASE}/audio/speech`, {
    model: modelId,
    input: text,
    voice: resolvedVoice,
    response_format: 'mp3',
  }, {
    headers: headers(),
    timeout: 60000,
    responseType: 'arraybuffer',
  });

  return {
    audioBuffer: Buffer.from(response.data),
    model: modelId,
    timing: Date.now() - start,
  };
}

/**
 * Transcribe audio via Together.ai (Whisper)
 */
export async function transcribeAudio(audioBuffer, filename = 'audio.wav') {
  const FormData = (await import('form-data')).default;
  const form = new FormData();
  const ext = filename.split('.').pop()?.toLowerCase() || 'wav';
  const contentTypes = { wav: 'audio/wav', webm: 'audio/webm', ogg: 'audio/ogg', mp3: 'audio/mpeg', m4a: 'audio/mp4', flac: 'audio/flac' };
  const contentType = contentTypes[ext] || 'audio/wav';
  form.append('file', audioBuffer, { filename, contentType });
  form.append('model', 'openai/whisper-large-v3');

  const start = Date.now();
  const response = await axios.post(`${API_BASE}/audio/transcriptions`, form, {
    headers: { ...form.getHeaders(), Authorization: `Bearer ${getKey()}` },
    timeout: 60000,
  });

  return { text: response.data?.text || '', timing: Date.now() - start };
}

/**
 * Vision analysis via Together.ai
 *
 * Serverless models available: Qwen3-VL-32B (primary), Qwen3-VL-8B (fallback).
 * Automatically retries with the 8B model if 32B returns 503.
 *
 * Image source: HTTPS URL or base64 data URI (data:image/...;base64,...).
 * Note: plain HTTP URLs or URLs behind bot-blocking CDNs should be
 * pre-fetched and converted to base64 by the caller before passing here.
 */
export async function analyzeVision(prompt, imageSource, model = 'default') {
  const requestedId = VISION_MODELS[model] || model;
  const start = Date.now();

  // Build the ordered list of models to try: requested first, then fallbacks
  const tryModels = [
    requestedId,
    ...VISION_FALLBACK.filter(m => m !== requestedId),
  ];

  let lastError;
  for (const modelId of tryModels) {
    try {
      const response = await axios.post(`${API_BASE}/chat/completions`, {
        model: modelId,
        messages: [{
          role: 'user',
          content: [
            { type: 'text', text: prompt },
            { type: 'image_url', image_url: { url: imageSource } },
          ],
        }],
        max_tokens: 4096,
      }, { headers: headers(), timeout: 120000 });

      const text = response.data?.choices?.[0]?.message?.content || '';
      return { text, model: modelId, timing: Date.now() - start };

    } catch (err) {
      const status = err?.response?.status;
      // Only fall through on 503 (transient unavailability) — hard-fail on all others
      if (status === 503 && tryModels.indexOf(modelId) < tryModels.length - 1) {
        lastError = err;
        continue;
      }
      throw err;
    }
  }

  throw lastError;
}

/**
 * Generate embeddings via Together.ai
 *
 * Verified serverless models:
 *   intfloat/multilingual-e5-large-instruct  (dim=1024, multilingual)
 *   BAAI/bge-large-en-v1.5                   (dim=1024, English)
 */
export async function generateEmbeddings(texts, model = 'intfloat/multilingual-e5-large-instruct') {
  const start = Date.now();
  const response = await axios.post(`${API_BASE}/embeddings`, {
    model,
    input: texts,
  }, { headers: headers(), timeout: 60000 });

  const embeddings = (response.data?.data || []).map(d => d.embedding);
  return { embeddings, model, timing: Date.now() - start };
}

/**
 * Rerank search results via Together.ai
 *
 * Verified serverless model: mixedbread-ai/Mxbai-Rerank-Large-V2
 * (Salesforce/Llama-Rank-V1 is no longer serverless)
 */
export async function rerank(query, documents, model = 'mixedbread-ai/Mxbai-Rerank-Large-V2', topN = 10) {
  const start = Date.now();
  const response = await axios.post(`${API_BASE}/rerank`, {
    model,
    query,
    documents,
    top_n: topN,
  }, { headers: headers(), timeout: 60000 });

  return {
    results: response.data?.results || [],
    timing: Date.now() - start,
  };
}

/**
 * Chat completion via Together.ai
 */
export async function chatCompletion(messages, model = 'default', maxTokens = 2048, temperature = 0.7) {
  const modelId = LLM_MODELS[model] || model;
  const start = Date.now();

  const response = await axios.post(`${API_BASE}/chat/completions`, {
    model: modelId,
    messages,
    max_tokens: maxTokens,
    temperature,
  }, { headers: headers(), timeout: 120000 });

  return {
    text: response.data?.choices?.[0]?.message?.content || '',
    model: modelId,
    usage: response.data?.usage || {},
    timing: Date.now() - start,
  };
}

/**
 * Get all available models organized by category
 */
export function listModels() {
  return {
    image:  Object.entries(IMAGE_MODELS) .filter(([k]) => k !== 'default').map(([alias, id]) => ({ alias, model: id })),
    video:  Object.entries(VIDEO_MODELS) .filter(([k]) => k !== 'default').map(([alias, id]) => ({ alias, model: id })),
    audio:  Object.entries(AUDIO_MODELS) .filter(([k]) => k !== 'default').map(([alias, id]) => ({ alias, model: id })),
    vision: Object.entries(VISION_MODELS).filter(([k]) => k !== 'default').map(([alias, id]) => ({ alias, model: id })),
    llm:    Object.entries(LLM_MODELS)   .filter(([k]) => k !== 'default').map(([alias, id]) => ({ alias, model: id })),
  };
}
