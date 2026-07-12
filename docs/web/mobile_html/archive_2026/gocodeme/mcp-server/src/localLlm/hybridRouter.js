/**
 * hybridRouter.js — Intelligent LLM Routing
 *
 * Routes requests between local Ollama models and cloud APIs (Claude / Together)
 * based on task complexity, privacy requirements, and model availability.
 *
 * Routing Strategy:
 *   LOCAL (Ollama):
 *     - Simple completions, boilerplate code
 *     - Private/sensitive data (credentials, env vars, proprietary code)
 *     - Syntax fixes, formatting, simple Q&A
 *     - When explicitly requested by user
 *     - Embeddings (if local model available)
 *
 *   CLOUD (Claude / Together):
 *     - Complex reasoning, multi-step analysis
 *     - Long context (>4k tokens)
 *     - Agentic workflows
 *     - Image/audio/video generation
 *     - When local model unavailable
 */

import { chat as ollamaChat, isOllamaRunning, generate as ollamaGenerate } from './ollamaClient.js';

// Complexity signals that push toward CLOUD routing
const COMPLEX_SIGNALS = [
  /refactor/i, /architect/i, /design pattern/i, /explain.*detail/i,
  /step.?by.?step/i, /analyze/i, /security.?audit/i, /review.?code/i,
  /compare.*approach/i, /trade.?off/i, /best.?practice/i, /optimize/i,
  /debug.*complex/i, /implement.*feature/i, /write.*test/i,
  /multi.?file/i, /full.*implementation/i, /migration/i,
];

// Signals that push toward LOCAL routing
const SIMPLE_SIGNALS = [
  /^fix\s/i, /format/i, /lint/i, /^what is/i, /^how to/i,
  /boilerplate/i, /template/i, /quick/i, /simple/i,
  /rename/i, /typo/i, /syntax/i, /^translate/i,
  /snippet/i, /one.?liner/i, /hello.?world/i,
];

// Signals for PRIVATE routing (always prefer local)
const PRIVATE_SIGNALS = [
  /password/i, /secret/i, /credential/i, /api.?key/i,
  /\.env/i, /private/i, /confidential/i, /internal.?only/i,
  /proprietary/i, /do.?not.?share/i, /sensitive/i,
];

/**
 * Route a request to the best available model.
 *
 * @param {object} opts
 * @param {Array<{role: string, content: string}>} opts.messages
 * @param {string} [opts.preference='auto']  — 'auto', 'local', 'cloud'
 * @param {string} [opts.localModel='qwen2.5:0.5b']
 * @param {number} [opts.temperature=0.7]
 * @param {number} [opts.maxTokens=2048]
 * @param {Function} [opts.cloudFallback] — async function that calls Claude/Together
 * @returns {Promise<object>}
 */
export async function routeRequest(opts) {
  const {
    messages,
    preference = 'auto',
    localModel = 'qwen2.5:0.5b',
    temperature = 0.7,
    maxTokens = 2048,
    cloudFallback = null,
  } = opts;

  if (!messages?.length) throw new Error('messages array required');

  // Determine the route
  const route = await determineRoute({ messages, preference, localModel });

  // Execute based on route
  if (route.target === 'local') {
    try {
      const result = await ollamaChat({ messages, model: localModel, temperature, maxTokens });

      if (result.status === 'success') {
        return { ...result, route: 'local', reason: route.reason };
      }

      // Local failed → fallback to cloud if available
      if (cloudFallback) {
        console.log(`[HybridRouter] Local failed (${result.error}), falling back to cloud`);
        const cloudResult = await cloudFallback(messages, temperature, maxTokens);
        return { ...cloudResult, route: 'cloud', reason: `local_fallback: ${result.error}` };
      }

      return { ...result, route: 'local', reason: route.reason };
    } catch (err) {
      if (cloudFallback) {
        return { ...(await cloudFallback(messages, temperature, maxTokens)), route: 'cloud', reason: 'local_exception' };
      }
      throw err;
    }
  }

  // Cloud route
  if (cloudFallback) {
    const cloudResult = await cloudFallback(messages, temperature, maxTokens);
    return { ...cloudResult, route: 'cloud', reason: route.reason };
  }

  // No cloud fallback, try local anyway
  const localResult = await ollamaChat({ messages, model: localModel, temperature, maxTokens });
  return { ...localResult, route: 'local', reason: 'no_cloud_available' };
}

/**
 * Determine the best route for a request.
 *
 * @private
 */
async function determineRoute({ messages, preference, localModel }) {
  // Explicit preference overrides
  if (preference === 'local') {
    return { target: 'local', reason: 'user_requested_local' };
  }
  if (preference === 'cloud') {
    return { target: 'cloud', reason: 'user_requested_cloud' };
  }

  // Auto-route: analyze the request
  const lastMessage = messages[messages.length - 1]?.content || '';
  const allContent = messages.map(m => m.content).join(' ');

  // Privacy check — always use local for sensitive data
  if (PRIVATE_SIGNALS.some(p => p.test(allContent))) {
    const ollamaStatus = await isOllamaRunning();
    if (ollamaStatus.running) {
      return { target: 'local', reason: 'privacy_sensitive_data' };
    }
    // If local not available but data is private, warn
    return { target: 'cloud', reason: 'privacy_data_but_local_unavailable' };
  }

  // Context length check — long conversations go to cloud (better context window)
  const totalTokensEst = allContent.length / 4; // rough estimate
  if (totalTokensEst > 4000) {
    return { target: 'cloud', reason: 'long_context' };
  }

  // Complexity analysis
  const complexScore = COMPLEX_SIGNALS.filter(p => p.test(lastMessage)).length;
  const simpleScore = SIMPLE_SIGNALS.filter(p => p.test(lastMessage)).length;

  // Multi-message conversations tend to be more complex (agentic)
  if (messages.length > 6) {
    return { target: 'cloud', reason: 'multi_turn_conversation' };
  }

  // If clearly simple and Ollama is available → local
  if (simpleScore > complexScore) {
    const ollamaStatus = await isOllamaRunning();
    if (ollamaStatus.running) {
      return { target: 'local', reason: `simple_task (simple=${simpleScore}, complex=${complexScore})` };
    }
    return { target: 'cloud', reason: 'simple_but_local_unavailable' };
  }

  // If clearly complex → cloud
  if (complexScore > simpleScore) {
    return { target: 'cloud', reason: `complex_task (complex=${complexScore}, simple=${simpleScore})` };
  }

  // Tie or no signals → check if local is available, prefer cloud for quality
  const ollamaStatus = await isOllamaRunning();
  if (ollamaStatus.running) {
    // Short question → local, longer → cloud
    if (lastMessage.length < 200) {
      return { target: 'local', reason: 'short_query_local_available' };
    }
    return { target: 'cloud', reason: 'medium_query_prefer_cloud_quality' };
  }

  return { target: 'cloud', reason: 'local_unavailable' };
}

/**
 * Get routing analysis without executing (for transparency / debugging).
 *
 * @param {object} opts
 * @param {Array<{role: string, content: string}>} opts.messages
 * @returns {Promise<object>}
 */
export async function analyzeRoute(opts) {
  const { messages } = opts;
  if (!messages?.length) throw new Error('messages required');

  const lastMessage = messages[messages.length - 1]?.content || '';
  const allContent = messages.map(m => m.content).join(' ');
  const totalTokensEst = Math.round(allContent.length / 4);

  const complexScore = COMPLEX_SIGNALS.filter(p => p.test(lastMessage)).length;
  const simpleScore = SIMPLE_SIGNALS.filter(p => p.test(lastMessage)).length;
  const privacyScore = PRIVATE_SIGNALS.filter(p => p.test(allContent)).length;

  const ollamaStatus = await isOllamaRunning();

  const route = await determineRoute({ messages, preference: 'auto', localModel: 'qwen2.5:0.5b' });

  return {
    route: route.target,
    reason: route.reason,
    analysis: {
      complexScore,
      simpleScore,
      privacyScore,
      estimatedTokens: totalTokensEst,
      messageCount: messages.length,
      lastMessageLength: lastMessage.length,
    },
    ollamaAvailable: ollamaStatus.running,
    localModels: ollamaStatus.models?.map(m => m.name) || [],
  };
}
