/**
 * codeReview.js — AI-Powered Code Review Engine
 *
 * Takes a git diff and sends it to Claude for intelligent analysis.
 * Returns structured review with bugs, style issues, security concerns,
 * and improvement suggestions.
 *
 * Uses Anthropic API directly (same key as Alfred).
 */

import Anthropic from '@anthropic-ai/sdk';

const ANTHROPIC_API_KEY = process.env.ANTHROPIC_API_KEY;
const MODEL = process.env.ANTHROPIC_MODEL || 'claude-sonnet-4-6';
const MAX_DIFF_SIZE = 80_000; // chars — Claude can handle large diffs

/**
 * Review a git diff for bugs, style, security, and improvement opportunities.
 *
 * @param {string} diff       — the raw git diff text
 * @param {object} [options]
 * @param {string} [options.focus]      — optional focus: 'bugs', 'security', 'style', 'performance', or 'all'
 * @param {string} [options.language]   — hint about the primary language (auto-detected if omitted)
 * @param {string} [options.context]    — extra context about the project
 * @returns {Promise<object>}  — structured review result
 */
export async function reviewDiff(diff, options = {}) {
  if (!ANTHROPIC_API_KEY) {
    throw new Error('ANTHROPIC_API_KEY not configured — cannot run AI code review.');
  }

  if (!diff || diff.trim().length === 0) {
    return {
      summary: 'No changes to review.',
      issues: [],
      suggestions: [],
      score: 10,
    };
  }

  // Truncate massive diffs
  let truncated = false;
  let reviewDiff = diff;
  if (diff.length > MAX_DIFF_SIZE) {
    reviewDiff = diff.slice(0, MAX_DIFF_SIZE);
    truncated = true;
  }

  const focus = options.focus || 'all';
  const lang = options.language ? `Primary language: ${options.language}. ` : '';
  const ctx = options.context ? `Project context: ${options.context}\n\n` : '';

  const systemPrompt = `You are a senior code reviewer. Analyze the git diff and provide a structured review.
Be practical and specific — cite exact line numbers and file names from the diff.
Focus on real issues, not nitpicks. Organize findings by severity.

${lang}${ctx}

Respond in this exact JSON format (no markdown wrapping):
{
  "summary": "1-2 sentence overall summary of the changes",
  "score": <1-10 quality score>,
  "issues": [
    {
      "severity": "critical|warning|info",
      "category": "bug|security|performance|style|logic",
      "file": "filename",
      "line": "line number or range",
      "title": "short title",
      "detail": "explanation of the issue",
      "suggestion": "how to fix"
    }
  ],
  "suggestions": [
    "general improvement suggestions not tied to specific lines"
  ],
  "security_notes": ["any security-related observations"],
  "positive": ["things done well worth acknowledging"]
}`;

  const userMessage = focus === 'all'
    ? `Review this diff for bugs, security issues, style, and performance:\n\n${reviewDiff}`
    : `Review this diff with a focus on **${focus}**:\n\n${reviewDiff}`;

  const client = new Anthropic({ apiKey: ANTHROPIC_API_KEY });
  const response = await client.messages.create({
    model: MODEL,
    max_tokens: 4096,
    system: systemPrompt,
    messages: [{ role: 'user', content: userMessage }],
  });

  // Extract the text content
  const text = response.content
    .filter(b => b.type === 'text')
    .map(b => b.text)
    .join('');

  // Parse JSON from Claude's response
  let review;
  try {
    // Strip markdown code fences if present
    const cleaned = text.replace(/^```(?:json)?\s*\n?/m, '').replace(/\n?```\s*$/m, '').trim();
    review = JSON.parse(cleaned);
  } catch {
    // If JSON parsing fails, return as plain text
    review = {
      summary: text.slice(0, 200),
      issues: [],
      suggestions: [text],
      score: null,
      parse_error: 'Claude response was not valid JSON — returning raw text.',
    };
  }

  if (truncated) {
    review._note = `Diff was truncated from ${diff.length} to ${MAX_DIFF_SIZE} chars for review.`;
  }

  review._model = MODEL;
  review._tokens = {
    input: response.usage?.input_tokens,
    output: response.usage?.output_tokens,
  };

  return review;
}

/**
 * Quick review — just get a summary and score without full analysis.
 */
export async function quickReview(diff) {
  if (!ANTHROPIC_API_KEY) {
    throw new Error('ANTHROPIC_API_KEY not configured.');
  }

  if (!diff || diff.trim().length === 0) {
    return { summary: 'No changes to review.', score: 10 };
  }

  const truncDiff = diff.length > 30_000 ? diff.slice(0, 30_000) : diff;

  const client = new Anthropic({ apiKey: ANTHROPIC_API_KEY });
  const response = await client.messages.create({
    model: MODEL,
    max_tokens: 512,
    system: 'You are a code reviewer. Give a 1-2 sentence summary and a 1-10 quality score. Respond as JSON: {"summary":"...","score":N}',
    messages: [{ role: 'user', content: `Quick review:\n\n${truncDiff}` }],
  });

  const text = response.content.filter(b => b.type === 'text').map(b => b.text).join('');
  try {
    const cleaned = text.replace(/^```(?:json)?\s*\n?/m, '').replace(/\n?```\s*$/m, '').trim();
    return JSON.parse(cleaned);
  } catch {
    return { summary: text.slice(0, 200), score: null };
  }
}
