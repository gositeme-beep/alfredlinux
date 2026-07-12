'use strict';

/**
 * billing/formatTranslator.js — Translate between Anthropic and OpenAI API formats
 *
 * Theia IDE speaks Anthropic Messages API. When routing to Together.ai (or any
 * OpenAI-compatible provider), we need to translate:
 *   1. Request:  Anthropic Messages → OpenAI Chat Completions
 *   2. Response: OpenAI Chat Completions → Anthropic Messages (so Theia understands it)
 *
 * This enables seamless multi-provider routing without any Theia changes.
 */

const logger = require('../logger');

// ── Request translation: Anthropic → OpenAI ──────────────────────────────

/**
 * Convert an Anthropic Messages API request body to OpenAI Chat Completions format.
 *
 * Key differences:
 *   - system: separate field → first message with role="system"
 *   - messages[].content: can be array of blocks → must be string or array for OpenAI
 *   - tool_use / tool_result → function_call / tool response
 *   - tools: Anthropic schema → OpenAI function schema
 *   - max_tokens → max_tokens (same)
 *   - stream → stream (same)
 *
 * @param {object} anthropicBody — Anthropic Messages API request
 * @param {string} openaiModelId — Together.ai model ID (e.g. 'Qwen/Qwen3-Coder-Next-FP8')
 * @returns {object} OpenAI Chat Completions request body
 */
function anthropicToOpenAI(anthropicBody, openaiModelId) {
  const messages = [];

  // ── 1. System prompt ─────────────────────────────────────────────────
  if (anthropicBody.system) {
    let systemText;
    if (typeof anthropicBody.system === 'string') {
      systemText = anthropicBody.system;
    } else if (Array.isArray(anthropicBody.system)) {
      // Array of content blocks → concatenate text
      systemText = anthropicBody.system
        .filter(b => b.type === 'text')
        .map(b => b.text)
        .join('\n\n');
    }
    if (systemText) {
      messages.push({ role: 'system', content: systemText });
    }
  }

  // ── 2. Conversation messages ─────────────────────────────────────────
  for (const msg of (anthropicBody.messages || [])) {
    if (msg.role === 'user') {
      messages.push(translateUserMessage(msg));
    } else if (msg.role === 'assistant') {
      const translated = translateAssistantMessage(msg);
      // An assistant message can produce multiple OpenAI messages
      // (one for text content, separate tool_calls)
      messages.push(...translated);
    }
  }

  // ── 3. Build OpenAI request body ─────────────────────────────────────
  const result = {
    model: openaiModelId,
    messages,
    stream: anthropicBody.stream || false,
  };

  if (anthropicBody.max_tokens) {
    result.max_tokens = anthropicBody.max_tokens;
  }

  if (anthropicBody.temperature !== undefined) {
    result.temperature = anthropicBody.temperature;
  }

  // ── 4. Tools → OpenAI functions format ─────────────────────────────
  if (Array.isArray(anthropicBody.tools) && anthropicBody.tools.length > 0) {
    result.tools = anthropicBody.tools.map(tool => ({
      type: 'function',
      function: {
        name: tool.name,
        description: tool.description || '',
        parameters: tool.input_schema || {},
      },
    }));
  }

  return result;
}

/**
 * Translate user message from Anthropic to OpenAI format.
 */
function translateUserMessage(msg) {
  if (typeof msg.content === 'string') {
    return { role: 'user', content: msg.content };
  }

  // Array content — may contain text, image, and tool_result blocks
  if (Array.isArray(msg.content)) {
    const parts = [];
    const toolResults = [];

    for (const block of msg.content) {
      if (block.type === 'text') {
        parts.push(block.text);
      } else if (block.type === 'tool_result') {
        // Tool results become separate "tool" role messages in OpenAI format
        let resultContent = '';
        if (typeof block.content === 'string') {
          resultContent = block.content;
        } else if (Array.isArray(block.content)) {
          resultContent = block.content
            .filter(b => b.type === 'text')
            .map(b => b.text)
            .join('\n');
        }
        toolResults.push({
          role: 'tool',
          tool_call_id: block.tool_use_id || 'unknown',
          content: resultContent,
        });
      } else if (block.type === 'image') {
        // Skip images for non-vision models (saves tokens)
        parts.push('[image omitted]');
      }
    }

    // If there are tool results, they need to come as separate messages
    // If there's also text content, prepend it as a user message
    const result = [];
    if (toolResults.length > 0) {
      result.push(...toolResults);
      // Any text alongside tool results
      if (parts.length > 0) {
        result.push({ role: 'user', content: parts.join('\n') });
      }
    } else {
      result.push({ role: 'user', content: parts.join('\n') || '[empty]' });
    }
    return result.length === 1 ? result[0] : result[0]; // Return first — tool results handled separately
  }

  return { role: 'user', content: String(msg.content || '') };
}

/**
 * Translate assistant message from Anthropic to OpenAI format.
 * An Anthropic assistant message can contain both text and tool_use blocks.
 * In OpenAI, tool calls go in a separate `tool_calls` field.
 */
function translateAssistantMessage(msg) {
  const results = [];

  if (typeof msg.content === 'string') {
    results.push({ role: 'assistant', content: msg.content });
    return results;
  }

  if (Array.isArray(msg.content)) {
    const textParts = [];
    const toolCalls = [];

    for (const block of msg.content) {
      if (block.type === 'text') {
        textParts.push(block.text);
      } else if (block.type === 'tool_use') {
        toolCalls.push({
          id: block.id,
          type: 'function',
          function: {
            name: block.name,
            arguments: typeof block.input === 'string'
              ? block.input
              : JSON.stringify(block.input || {}),
          },
        });
      }
    }

    const assistantMsg = {
      role: 'assistant',
      content: textParts.join('\n') || null,
    };

    if (toolCalls.length > 0) {
      assistantMsg.tool_calls = toolCalls;
    }

    results.push(assistantMsg);
  }

  return results.length > 0 ? results : [{ role: 'assistant', content: '...' }];
}

// ── Response translation: OpenAI → Anthropic ─────────────────────────────

/**
 * Convert an OpenAI Chat Completions JSON response to Anthropic Messages format.
 * This lets Theia IDE understand responses from Together.ai models.
 *
 * @param {object} openaiResponse — OpenAI Chat Completions response
 * @param {string} modelDisplayName — model name for the response
 * @returns {object} Anthropic Messages API response
 */
function openAIToAnthropic(openaiResponse, modelDisplayName) {
  const choice = openaiResponse.choices?.[0];
  if (!choice) {
    return {
      id: openaiResponse.id || `msg_${Date.now()}`,
      type: 'message',
      role: 'assistant',
      model: modelDisplayName,
      content: [{ type: 'text', text: 'No response generated.' }],
      stop_reason: 'end_turn',
      usage: {
        input_tokens: openaiResponse.usage?.prompt_tokens || 0,
        output_tokens: openaiResponse.usage?.completion_tokens || 0,
      },
    };
  }

  const content = [];
  const message = choice.message;

  // Text content
  if (message.content) {
    content.push({ type: 'text', text: message.content });
  }

  // Tool calls → Anthropic tool_use blocks
  if (message.tool_calls && message.tool_calls.length > 0) {
    for (const tc of message.tool_calls) {
      let inputObj = {};
      try {
        inputObj = JSON.parse(tc.function.arguments || '{}');
      } catch {
        inputObj = { raw: tc.function.arguments };
      }
      content.push({
        type: 'tool_use',
        id: tc.id || `toolu_${Date.now()}_${require('crypto').randomBytes(4).toString('hex')}`,
        name: tc.function.name,
        input: inputObj,
      });
    }
  }

  if (content.length === 0) {
    content.push({ type: 'text', text: '...' });
  }

  // Map stop reasons
  let stopReason = 'end_turn';
  if (choice.finish_reason === 'tool_calls') stopReason = 'tool_use';
  else if (choice.finish_reason === 'length') stopReason = 'max_tokens';
  else if (choice.finish_reason === 'stop') stopReason = 'end_turn';

  return {
    id: openaiResponse.id || `msg_${Date.now()}`,
    type: 'message',
    role: 'assistant',
    model: modelDisplayName,
    content,
    stop_reason: stopReason,
    stop_sequence: null,
    usage: {
      input_tokens: openaiResponse.usage?.prompt_tokens || 0,
      output_tokens: openaiResponse.usage?.completion_tokens || 0,
    },
  };
}

// ── Streaming translation: OpenAI SSE → Anthropic SSE ────────────────────

/**
 * Create a transformer that converts OpenAI streaming chunks to Anthropic SSE format.
 * Returns a function that takes an OpenAI SSE data line and returns Anthropic SSE lines.
 *
 * Anthropic SSE events:
 *   - message_start: { type: "message_start", message: {...} }
 *   - content_block_start: { type: "content_block_start", index: 0, content_block: {...} }
 *   - content_block_delta: { type: "content_block_delta", index: 0, delta: { type: "text_delta", text: "..." } }
 *   - content_block_stop: { type: "content_block_stop", index: 0 }
 *   - message_delta: { type: "message_delta", delta: {...}, usage: {...} }
 *   - message_stop: { type: "message_stop" }
 *
 * OpenAI SSE events:
 *   - { choices: [{ delta: { role, content, tool_calls }, finish_reason }], usage? }
 */
function createStreamTranslator(modelDisplayName) {
  let started = false;
  let blockIndex = 0;
  let totalOutput = 0;
  let totalInput = 0;
  let activeToolCalls = {};   // Track in-progress tool calls by index

  /**
   * Process one OpenAI SSE data payload and return Anthropic SSE event lines.
   * @param {string} dataPayload — JSON string from "data: {...}" line
   * @returns {string[]} Array of "event: ...\ndata: {...}" strings
   */
  return function translate(dataPayload) {
    if (dataPayload === '[DONE]') {
      // Finalize any open tool calls
      const events = [];
      for (const [idx, tc] of Object.entries(activeToolCalls)) {
        let inputObj = {};
        try { inputObj = JSON.parse(tc.arguments || '{}'); } catch { inputObj = { raw: tc.arguments }; }
        // Emit tool_use content block
        events.push(
          `event: content_block_start\ndata: ${JSON.stringify({
            type: 'content_block_start',
            index: parseInt(idx) + 1,
            content_block: { type: 'tool_use', id: tc.id, name: tc.name, input: {} },
          })}`,
          `event: content_block_delta\ndata: ${JSON.stringify({
            type: 'content_block_delta',
            index: parseInt(idx) + 1,
            delta: { type: 'input_json_delta', partial_json: JSON.stringify(inputObj) },
          })}`,
          `event: content_block_stop\ndata: ${JSON.stringify({
            type: 'content_block_stop',
            index: parseInt(idx) + 1,
          })}`
        );
      }

      // message_delta with final usage
      events.push(`event: message_delta\ndata: ${JSON.stringify({
        type: 'message_delta',
        delta: { stop_reason: Object.keys(activeToolCalls).length > 0 ? 'tool_use' : 'end_turn', stop_sequence: null },
        usage: { output_tokens: totalOutput },
      })}`);
      events.push(`event: message_stop\ndata: ${JSON.stringify({ type: 'message_stop' })}`);
      return events;
    }

    let chunk;
    try { chunk = JSON.parse(dataPayload); } catch { return []; }

    const events = [];
    const choice = chunk.choices?.[0];

    // Capture usage if present
    if (chunk.usage) {
      totalInput = chunk.usage.prompt_tokens || totalInput;
      totalOutput = chunk.usage.completion_tokens || totalOutput;
    }

    if (!started) {
      started = true;
      // Emit message_start
      events.push(`event: message_start\ndata: ${JSON.stringify({
        type: 'message_start',
        message: {
          id: chunk.id || `msg_${Date.now()}`,
          type: 'message',
          role: 'assistant',
          model: modelDisplayName,
          content: [],
          stop_reason: null,
          stop_sequence: null,
          usage: { input_tokens: totalInput, output_tokens: 0 },
        },
      })}`);
      // Start first content block (text)
      events.push(`event: content_block_start\ndata: ${JSON.stringify({
        type: 'content_block_start',
        index: 0,
        content_block: { type: 'text', text: '' },
      })}`);
    }

    if (choice) {
      const delta = choice.delta || {};

      // Text content delta
      if (delta.content) {
        totalOutput += Math.ceil(delta.content.length / 4); // rough estimate
        events.push(`event: content_block_delta\ndata: ${JSON.stringify({
          type: 'content_block_delta',
          index: 0,
          delta: { type: 'text_delta', text: delta.content },
        })}`);
      }

      // Tool calls
      if (delta.tool_calls) {
        for (const tc of delta.tool_calls) {
          const idx = tc.index || 0;
          if (!activeToolCalls[idx]) {
            activeToolCalls[idx] = { id: tc.id || `toolu_${Date.now()}_${idx}`, name: '', arguments: '' };
          }
          if (tc.function?.name) activeToolCalls[idx].name = tc.function.name;
          if (tc.function?.arguments) activeToolCalls[idx].arguments += tc.function.arguments;
        }
      }

      // Finish reason
      if (choice.finish_reason) {
        // Close text content block
        events.push(`event: content_block_stop\ndata: ${JSON.stringify({
          type: 'content_block_stop',
          index: 0,
        })}`);
      }
    }

    return events;
  };
}

module.exports = {
  anthropicToOpenAI,
  openAIToAnthropic,
  createStreamTranslator,
};
