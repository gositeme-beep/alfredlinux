/**
 * voiceToolBridge.js — Connects Alfred Voice to the full 400+-tool MCP pipeline
 *
 * Architecture:
 *   User speech → Whisper STT → Claude (Anthropic) with tool definitions →
 *   [Claude requests tool_use → dispatchTool() → results back to Claude → loop] →
 *   Final text response → Kokoro TTS → Audio back to user
 *
 * Voice uses a reduced "lite" toolset by default (see voiceToolFilter.js) for speed;
 * set VOICE_LITE_TOOLSET=full for all MCP tools. IDE/chat still loads the full list.
 */

import Anthropic from '@anthropic-ai/sdk';
import { DirectAdminClient } from '../daClient.js';
import { WhmcsClient } from '../whmcsClient.js';
import { toolDefinitions } from '../tools.js';
import { dispatchTool } from '../toolDispatch.js';
import { checkAllowance, recordToolCall } from '../billing/tokenGate.js';
import { remember, recall } from '../memory/memoryEngine.js';
import { isToolExcludedForVoice, isVoiceLiteToolsetEnabled } from './voiceToolFilter.js';
import https from 'https';

// ── Config ──────────────────────────────────────────────────────────────────
const DA_HOST  = process.env.DA_HOST       || 'https://localhost:2222';
const DA_ADMIN = process.env.DA_ADMIN_USER || 'admin';
const DA_PASS  = process.env.DA_ADMIN_PASS || '';

// Tunable via env — faster defaults: lower VOICE_MAX_TOOL_ROUNDS / VOICE_MEMORY_TOPK; exclude heavy tools with VOICE_EXCLUDE_TOOLS
const MAX_TOOL_ROUNDS = Math.min(20, Math.max(1, parseInt(process.env.VOICE_MAX_TOOL_ROUNDS || '8', 10) || 8));
const CLAUDE_MODEL    = process.env.VOICE_CLAUDE_MODEL || 'claude-sonnet-4-20250514';
const GROQ_MODEL      = process.env.VOICE_GROQ_MODEL || 'llama-3.3-70b-versatile';
const MAX_TOKENS      = Math.min(8192, Math.max(256, parseInt(process.env.VOICE_MAX_TOKENS || '1024', 10) || 1024));
const VOICE_MEMORY_TOPK = Math.min(10, Math.max(0, parseInt(process.env.VOICE_MEMORY_TOPK ?? '3', 10) || 3));
const VOICE_EXCLUDE_TOOLS = new Set(
  (process.env.VOICE_EXCLUDE_TOOLS || '')
    .split(',')
    .map(s => s.trim())
    .filter(Boolean),
);

let anthropic = null;

/**
 * Lazy-init the Anthropic client (avoids crash if key not set)
 */
function getAnthropicClient() {
  if (!anthropic) {
    const key = process.env.ANTHROPIC_API_KEY;
    if (!key) return null; // Return null instead of throwing — allows Groq fallback
    anthropic = new Anthropic({ apiKey: key });
  }
  return anthropic;
}

/**
 * Call Groq's OpenAI-compatible API as a fallback when Anthropic is unavailable.
 * Translates Anthropic-format tools → OpenAI-format tools and back.
 */
async function callGroqFallback(systemPrompt, messages, tools) {
  const groqKey = process.env.GROQ_API_KEY;
  if (!groqKey) throw new Error('GROQ_API_KEY not set');

  // Convert Anthropic-format tools to OpenAI-format
  const openaiTools = tools.map(t => ({
    type: 'function',
    function: {
      name: t.name,
      description: t.description,
      parameters: t.input_schema || { type: 'object', properties: {} },
    },
  }));

  // Convert Anthropic messages to OpenAI format
  const openaiMessages = [{ role: 'system', content: systemPrompt }];
  for (const msg of messages) {
    if (msg.role === 'user') {
      if (Array.isArray(msg.content)) {
        // Tool results — convert to individual tool messages
        for (const item of msg.content) {
          if (item.type === 'tool_result') {
            openaiMessages.push({
              role: 'tool',
              tool_call_id: item.tool_use_id,
              content: typeof item.content === 'string' ? item.content : JSON.stringify(item.content),
            });
          }
        }
      } else {
        openaiMessages.push({ role: 'user', content: msg.content });
      }
    } else if (msg.role === 'assistant') {
      if (Array.isArray(msg.content)) {
        const textParts = msg.content.filter(b => b.type === 'text').map(b => b.text).join('\\n');
        const toolCalls = msg.content.filter(b => b.type === 'tool_use').map(b => ({
          id: b.id,
          type: 'function',
          function: { name: b.name, arguments: JSON.stringify(b.input || {}) },
        }));
        const assistantMsg = { role: 'assistant', content: textParts || null };
        if (toolCalls.length > 0) assistantMsg.tool_calls = toolCalls;
        openaiMessages.push(assistantMsg);
      } else {
        openaiMessages.push({ role: 'assistant', content: msg.content });
      }
    }
  }

  const body = JSON.stringify({
    model: GROQ_MODEL,
    max_tokens: MAX_TOKENS,
    messages: openaiMessages,
    tools: openaiTools.length > 0 ? openaiTools.slice(0, 128) : undefined, // Groq has tool limit
    tool_choice: 'auto',
  });

  return new Promise((resolve, reject) => {
    const req = https.request('https://api.groq.com/openai/v1/chat/completions', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + groqKey,
        'Content-Length': Buffer.byteLength(body),
      },
    }, (res) => {
      let data = '';
      res.on('data', c => data += c);
      res.on('end', () => {
        try {
          const parsed = JSON.parse(data);
          if (parsed.error) {
            reject(new Error(parsed.error.message || JSON.stringify(parsed.error)));
            return;
          }
          const choice = parsed.choices?.[0];
          if (!choice) { reject(new Error('No choices in Groq response')); return; }

          // Convert OpenAI response → Anthropic format
          const content = [];
          if (choice.message?.content) {
            content.push({ type: 'text', text: choice.message.content });
          }
          if (choice.message?.tool_calls) {
            for (const tc of choice.message.tool_calls) {
              content.push({
                type: 'tool_use',
                id: tc.id,
                name: tc.function.name,
                input: JSON.parse(tc.function.arguments || '{}'),
              });
            }
          }

          resolve({
            content,
            stop_reason: choice.finish_reason === 'tool_calls' ? 'tool_use' : 'end_turn',
            model: GROQ_MODEL,
          });
        } catch (e) {
          reject(new Error('Failed to parse Groq response: ' + data.substring(0, 200)));
        }
      });
    });
    req.on('error', reject);
    // Voice + tools: 30s was too aggressive — multi-step tool loops need headroom
    req.setTimeout(120000, () => { req.destroy(); reject(new Error('Groq timeout')); });
    req.write(body);
    req.end();
  });
}

/**
 * Convert MCP tool definitions to Anthropic tool format.
 * MCP: { name, description, inputSchema: { type: 'object', properties, required } }
 * Anthropic: { name, description, input_schema: { type: 'object', properties, required } }
 *
 * We cache the conversion since tool definitions don't change at runtime.
 */
let _cachedAnthropicTools = null;

function getAnthropicTools() {
  if (_cachedAnthropicTools) return _cachedAnthropicTools;

  const full = toolDefinitions.length;
  const defs = toolDefinitions.filter((t) => !isToolExcludedForVoice(t.name, VOICE_EXCLUDE_TOOLS));
  if (defs.length === 0) {
    console.warn('[VoiceBridge] All tools excluded — using full tool list');
    _cachedAnthropicTools = toolDefinitions.map(toAnthropicTool);
  } else {
    _cachedAnthropicTools = defs.map(toAnthropicTool);
  }

  const mode = isVoiceLiteToolsetEnabled() ? 'lite+env' : 'env-only';
  console.log(
    `[VoiceBridge] Voice tools (${mode}): ${_cachedAnthropicTools.length} of ${full} MCP tools → Anthropic format`,
  );
  return _cachedAnthropicTools;
}

function toAnthropicTool(t) {
  return {
    name: t.name,
    description: t.description,
    input_schema: t.inputSchema || { type: 'object', properties: {} },
  };
}

/**
 * Build a DirectAdmin client scoped to the authenticated voice user.
 * @param {string} daUsername — The DA username from the voice session auth
 * @returns {DirectAdminClient}
 */
function buildDaClient(daUsername) {
  return new DirectAdminClient({
    host: DA_HOST,
    adminUser: DA_ADMIN,
    adminPass: DA_PASS,
    targetUsername: daUsername,
  });
}

/**
 * The Alfred voice system prompt — concise for voice but with full tool awareness.
 */
const VOICE_SYSTEM_PROMPT = `You are Alfred, the world's first AI hosting assistant, built into GoCodeMe — the AI-powered cloud IDE by GoSiteMe. GoSiteMe (say "Go Site Me") is the parent hosting company at gositeme.com. GoCodeMe (say "Go Code Me") is the AI IDE product. When speaking, never read out ".com" — just say the brand name naturally.

You have access to a comprehensive set of real hosting tools: files, databases, domains, email, DNS, SSL, WordPress, security, backups, analytics, billing, Git, images, monitoring, logs, and more. If something is missing from the tool list, explain what you can do instead or suggest they use the full IDE chat.

VOICE INTERACTION RULES:
- Keep responses SHORT and conversational (2-4 sentences) — this is spoken aloud
- When executing a tool, briefly confirm what you did and the key result
- For complex multi-step tasks, narrate each step briefly ("Installing WordPress... done. Now configuring SSL...")
- If a tool errors, explain simply what went wrong and suggest a fix
- You can chain multiple tools in one turn to complete complex requests
- Always be helpful, confident, and proactive

MEMORY:
- You have persistent memory via alfred-remember and alfred-recall tools
- Use memory to remember user preferences and past conversations

You are speaking with a real customer who has a live hosting account. Every tool call affects their real server.`;

/**
 * Process a voice request through the full Claude + tools pipeline.
 *
 * @param {object} options
 * @param {string} options.userText — Transcribed voice input
 * @param {string} options.daUsername — DirectAdmin username (from voice auth)
 * @param {string} [options.whmcsClientId] — WHMCS client ID (optional)
 * @param {Array} [options.conversationHistory] — Previous messages for context
 * @param {function} [options.onStatus] — Callback for status updates: (stage, detail) => void
 * @param {function} [options.isAborted] — Returns true if user triggered abort (steering)
 * @returns {Promise<{text: string, toolsUsed: string[], timing: number, model: string, aborted: boolean}>}
 */
export async function processVoiceWithTools({
  userText,
  daUsername,
  whmcsClientId,
  conversationHistory = [],
  onStatus,
  isAborted,
}) {
  const startTime = Date.now();
  const toolsUsed = [];

  // Status helper
  const status = (stage, detail) => {
    if (onStatus) onStatus(stage, detail);
  };

  // 1–3. Parallel: billing + memory recall (saves ~100–400ms vs sequential)
  let memoryContext = '';
  let billingOk = true;
  try {
    const [allowed, memories] = await Promise.all([
      checkAllowance(whmcsClientId, 'voice-tool-call').catch((e) => {
        console.warn(`[VoiceBridge] Billing check failed: ${e.message}`);
        return { allowed: true };
      }),
      VOICE_MEMORY_TOPK > 0
        ? recall(daUsername, userText, VOICE_MEMORY_TOPK).catch(() => [])
        : Promise.resolve([]),
    ]);
    if (!allowed || !allowed.allowed) {
      billingOk = false;
    } else if (memories && memories.length > 0) {
      memoryContext = '\n\nRELEVANT MEMORIES:\n' + memories.map(m =>
        `- [${m.category}] ${m.text}`
      ).join('\n');
    }
  } catch (e) {
    console.warn(`[VoiceBridge] Parallel preflight failed: ${e.message}`);
  }

  if (!billingOk) {
    return {
      text: 'You\'ve reached your token limit for this billing period. Please upgrade your plan or wait for the next cycle.',
      toolsUsed: [],
      timing: Date.now() - startTime,
      model: 'none',
    };
  }

  // 2. Build scoped clients
  const daClient = buildDaClient(daUsername);
  const whmcsClient = whmcsClientId ? new WhmcsClient(whmcsClientId) : null;

  // 4. Build message array for Claude
  const systemPrompt = VOICE_SYSTEM_PROMPT + memoryContext +
    `\n\nCUSTOMER: DirectAdmin user "${daUsername}". All file paths are relative to their home directory.`;

  const messages = [
    ...conversationHistory.slice(-10).map(m => ({
      role: m.role,
      content: m.content,
    })),
    { role: 'user', content: userText },
  ];

  // 5. Agentic loop — Claude may request tool calls (with Groq fallback)
  const client = getAnthropicClient();
  const tools = getAnthropicTools();
  let currentMessages = [...messages];
  let finalText = '';
  let activeModel = CLAUDE_MODEL;
  let useGroq = false;

  // If Anthropic client unavailable, start with Groq
  if (!client) {
    console.log('[VoiceBridge] No Anthropic client — using Groq fallback');
    useGroq = true;
    activeModel = GROQ_MODEL;
  }

  // Helper: check if user aborted via steering
  const checkAbort = () => isAborted && isAborted();

  for (let round = 0; round < MAX_TOOL_ROUNDS; round++) {
    // ── STEERING: Check abort before each AI round ──
    if (checkAbort()) {
      return {
        text: toolsUsed.length > 0
          ? `Cancelled after ${toolsUsed.length} tool${toolsUsed.length > 1 ? 's' : ''}. What would you like instead?`
          : 'Cancelled. What would you like instead?',
        toolsUsed,
        timing: Date.now() - startTime,
        model: CLAUDE_MODEL,
        aborted: true,
      };
    }

    status('thinking', round === 0 ? 'Processing your request...' : `Running tools (step ${round + 1})...`);

    let response;
    try {
      if (useGroq) {
        response = await callGroqFallback(systemPrompt, currentMessages, tools);
        activeModel = GROQ_MODEL;
      } else {
        response = await client.messages.create({
          model: CLAUDE_MODEL,
          max_tokens: MAX_TOKENS,
          system: systemPrompt,
          messages: currentMessages,
          tools,
        });
        activeModel = CLAUDE_MODEL;
      }
    } catch (apiErr) {
      // If Anthropic fails (credits, rate limit, etc.), try Groq
      if (!useGroq && process.env.GROQ_API_KEY) {
        console.warn(`[VoiceBridge] Claude failed (${apiErr.message}) — falling back to Groq`);
        useGroq = true;
        try {
          response = await callGroqFallback(systemPrompt, currentMessages, tools);
          activeModel = GROQ_MODEL;
        } catch (groqErr) {
          console.error(`[VoiceBridge] Groq fallback also failed: ${groqErr.message}`);
          return {
            text: 'I encountered an issue processing your request. Please try again.',
            toolsUsed,
            timing: Date.now() - startTime,
            model: 'none',
          };
        }
      } else {
        console.error(`[VoiceBridge] API error (no fallback): ${apiErr.message}`);
        return {
          text: 'I encountered an issue processing your request. Please try again.',
          toolsUsed,
          timing: Date.now() - startTime,
          model: activeModel,
        };
      }
    }

    // 6. Process response blocks
    const textBlocks = [];
    const toolUseBlocks = [];

    for (const block of response.content) {
      if (block.type === 'text') {
        textBlocks.push(block.text);
      } else if (block.type === 'tool_use') {
        toolUseBlocks.push(block);
      }
    }

    // If there's text and no tool calls, we're done
    if (toolUseBlocks.length === 0) {
      finalText = textBlocks.join('\n');
      break;
    }

    // 7. Execute tool calls
    // Add Claude's response (with tool_use blocks) to the conversation
    currentMessages.push({ role: 'assistant', content: response.content });

    const toolResults = [];
    for (const toolBlock of toolUseBlocks) {
      // ── STEERING: Check abort before each tool execution ──
      if (checkAbort()) {
        // Return partial results for remaining tools so Claude doesn't hang
        toolResults.push({
          type: 'tool_result',
          tool_use_id: toolBlock.id,
          content: 'Execution cancelled by user.',
          is_error: true,
        });
        continue; // push error results for all remaining, then break out of agentic loop
      }

      const toolName = toolBlock.name;
      const toolArgs = toolBlock.input || {};

      status('tool', `Running ${toolName}...`);
      console.log(`[VoiceBridge] Executing tool: ${toolName}(${JSON.stringify(toolArgs).substring(0, 200)})`);

      toolsUsed.push(toolName);

      try {
        // Record for billing
        try {
          const toolElapsed = Date.now() - startTime;
          await recordToolCall(whmcsClientId, toolName, toolElapsed);
        } catch (_) { /* billing recording is best-effort */ }

        const result = await dispatchTool(toolName, toolArgs, daClient, whmcsClient);
        const resultText = result.content
          .map(c => c.text || c.data || '')
          .join('\n')
          .substring(0, 4000); // Truncate large results for voice context

        toolResults.push({
          type: 'tool_result',
          tool_use_id: toolBlock.id,
          content: resultText,
        });

        console.log(`[VoiceBridge] Tool ${toolName} succeeded (${resultText.length} chars)`);
      } catch (toolErr) {
        console.error(`[VoiceBridge] Tool ${toolName} failed: ${toolErr.message}`);
        toolResults.push({
          type: 'tool_result',
          tool_use_id: toolBlock.id,
          content: `Error: ${toolErr.message}`,
          is_error: true,
        });
      }
    }

    // 8. Send tool results back to Claude for the next round
    currentMessages.push({ role: 'user', content: toolResults });

    // ── STEERING: If aborted during tool execution, exit agentic loop ──
    if (checkAbort()) {
      finalText = toolsUsed.length > 0
        ? `Cancelled after running ${toolsUsed.join(', ')}. What would you like instead?`
        : 'Cancelled. What would you like instead?';
      break;
    }

    // If Claude's stop_reason was 'end_turn' with text + tools, grab the text
    if (textBlocks.length > 0 && response.stop_reason === 'end_turn') {
      finalText = textBlocks.join('\n');
      break;
    }
  }

  // If we exhausted the loop without final text, make one more call
  if (!finalText) {
    try {
      let finalResponse;
      if (useGroq) {
        finalResponse = await callGroqFallback(systemPrompt, currentMessages, []);
      } else {
        finalResponse = await client.messages.create({
          model: CLAUDE_MODEL,
          max_tokens: MAX_TOKENS,
          system: systemPrompt,
          messages: currentMessages,
        });
      }
      finalText = finalResponse.content
        .filter(b => b.type === 'text')
        .map(b => b.text)
        .join('\n') || 'Done. Is there anything else you need?';
    } catch (_) {
      finalText = `I completed ${toolsUsed.length} operations. Is there anything else?`;
    }
  }

  return {
    text: finalText,
    toolsUsed,
    timing: Date.now() - startTime,
    model: activeModel,
  };
}

/**
 * Check if voice tool bridge is available (API key set)
 */
export function isVoiceBridgeAvailable() {
  return !!(process.env.ANTHROPIC_API_KEY || process.env.GROQ_API_KEY);
}
