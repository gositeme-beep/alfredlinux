/**
 * conversationSummarizer.js — Session Summary → Memory
 *
 * Allows Alfred to save a concise summary of the current conversation
 * session to long-term memory. This captures key decisions, actions taken,
 * and important context that should persist across sessions.
 *
 * Works with the ELEPHANT memory engine — saves as category "session_summary".
 */

import { remember } from '../memory/memoryEngine.js';

/**
 * Save a conversation summary to long-term memory.
 *
 * The summary is broken into individual memories for better retrieval:
 *  - Each key decision/action gets its own memory entry
 *  - Tagged with session_summary category for filtering
 *
 * @param {string} daUsername
 * @param {string} summary — a structured summary of the conversation
 * @param {object} [options]
 * @param {string} [options.sessionId] — optional session identifier
 * @returns {Promise<{saved: number, message: string}>}
 */
export async function saveSessionSummary(daUsername, summary, options = {}) {
  const { sessionId = null } = options;

  // Split summary into individual points (by newline or bullet)
  const points = summary
    .split(/\n/)
    .map(l => l.replace(/^[-*•]\s*/, '').trim())
    .filter(l => l.length > 10); // skip very short lines

  if (points.length === 0) {
    return { saved: 0, message: 'Summary was empty — nothing saved.' };
  }

  // Save each point as a separate memory for better semantic retrieval
  const results = [];
  for (const point of points) {
    try {
      const extra = sessionId ? { sessionId } : {};
      const result = await remember(daUsername, point, 'session_summary', extra);
      results.push(result);
    } catch (err) {
      results.push({ id: null, message: `Failed: ${err.message}` });
    }
  }

  const saved = results.filter(r => r.id).length;
  return {
    saved,
    total_points: points.length,
    message: `Saved ${saved} session memories from ${points.length} summary points.`,
    memory_ids: results.filter(r => r.id).map(r => r.id),
  };
}
