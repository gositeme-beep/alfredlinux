/**
 * smartCommit.js — AI-Powered Git Commit with Intelligent Messages
 *
 * Stages changes and commits with a Claude-generated commit message
 * based on the actual diff. No more "[CHECKPOINT] Auto-save" messages.
 *
 * Features:
 *  - Analyzes the diff to generate a descriptive conventional commit message
 *  - Supports conventional commits format (feat:, fix:, refactor:, etc.)
 *  - Can stage specific files or all changes
 *  - Optional body with bullet-point summary of changes
 *  - Falls back to a descriptive local message if Claude API fails
 */

import { exec as execCb } from 'node:child_process';
import { promisify } from 'node:util';
import path from 'node:path';

const exec = promisify(execCb);
const TIMEOUT = 15000;
const MAX_DIFF_FOR_AI = 40_000; // chars — keep Claude costs reasonable

/**
 * Run a git command in the user workspace.
 */
async function gitExec(homeDir, cmd) {
  const workDir = path.join(homeDir, 'public_html');
  const { stdout } = await exec(cmd, {
    cwd: workDir,
    timeout: TIMEOUT,
    maxBuffer: 512 * 1024,
    env: { ...process.env, GIT_TERMINAL_PROMPT: '0' },
  });
  return stdout.trim();
}

/**
 * Generate a commit message from a diff using Claude.
 *
 * @param {string} diff — the staged diff
 * @param {string} [hint] — optional user hint about what the changes do
 * @returns {Promise<{subject: string, body: string}>}
 */
async function generateMessage(diff, hint = '') {
  const ANTHROPIC_API_KEY = process.env.ANTHROPIC_API_KEY;
  const MODEL = process.env.ANTHROPIC_MODEL || 'claude-sonnet-4-6';

  if (!ANTHROPIC_API_KEY || diff.length < 10) {
    return fallbackMessage(diff);
  }

  const truncDiff = diff.length > MAX_DIFF_FOR_AI ? diff.slice(0, MAX_DIFF_FOR_AI) : diff;
  const hintLine = hint ? `\nUser context: ${hint}\n` : '';

  try {
    const { default: Anthropic } = await import('@anthropic-ai/sdk');
    const client = new Anthropic({ apiKey: ANTHROPIC_API_KEY });

    const response = await client.messages.create({
      model: MODEL,
      max_tokens: 300,
      system: `You are a git commit message generator. Analyze the diff and produce a commit message.

Rules:
- Use conventional commits format: type(scope): description
- Types: feat, fix, refactor, style, docs, test, chore, perf, build
- Subject line: max 72 chars, imperative mood ("add" not "added")
- Body: optional, bullet points of key changes (max 5 bullets)
- Be specific about WHAT changed, not HOW

Respond as JSON only:
{"subject": "type(scope): description", "body": "- bullet 1\\n- bullet 2"}`,
      messages: [{ role: 'user', content: `${hintLine}Generate a commit message for this diff:\n\n${truncDiff}` }],
    });

    const text = response.content.filter(b => b.type === 'text').map(b => b.text).join('');
    const cleaned = text.replace(/^```(?:json)?\s*\n?/m, '').replace(/\n?```\s*$/m, '').trim();
    const parsed = JSON.parse(cleaned);

    return {
      subject: (parsed.subject || 'chore: update files').slice(0, 72),
      body: parsed.body || '',
    };
  } catch (err) {
    console.error(`[SmartCommit] AI message generation failed: ${err.message}`);
    return fallbackMessage(diff);
  }
}

/**
 * Fallback: generate a descriptive message from the diff without AI.
 */
function fallbackMessage(diff) {
  const files = new Set();
  const additions = { total: 0 };
  const deletions = { total: 0 };

  for (const line of diff.split('\n')) {
    if (line.startsWith('diff --git')) {
      const match = line.match(/b\/(.+)$/);
      if (match) files.add(match[1]);
    } else if (line.startsWith('+') && !line.startsWith('+++')) {
      additions.total++;
    } else if (line.startsWith('-') && !line.startsWith('---')) {
      deletions.total++;
    }
  }

  const fileList = [...files];
  const fileCount = fileList.length;

  let subject;
  if (fileCount === 0) {
    subject = 'chore: update files';
  } else if (fileCount === 1) {
    const basename = fileList[0].split('/').pop();
    subject = `chore: update ${basename}`;
  } else if (fileCount <= 3) {
    const names = fileList.map(f => f.split('/').pop()).join(', ');
    subject = `chore: update ${names}`.slice(0, 72);
  } else {
    // Find common directory
    const dirs = fileList.map(f => f.split('/').slice(0, -1).join('/'));
    const commonDir = dirs[0]?.split('/').filter((d, i) => dirs.every(p => p.split('/')[i] === d)).join('/');
    subject = commonDir
      ? `chore(${commonDir}): update ${fileCount} files`
      : `chore: update ${fileCount} files`;
    subject = subject.slice(0, 72);
  }

  const body = `- ${additions.total} insertions, ${deletions.total} deletions across ${fileCount} file${fileCount !== 1 ? 's' : ''}`;
  return { subject, body };
}

// ═══════════════════════════════════════════════════════════════════
// PUBLIC API
// ═══════════════════════════════════════════════════════════════════

/**
 * Smart commit: stage files, generate AI message, commit.
 *
 * @param {string} homeDir — user home directory
 * @param {object} [options]
 * @param {string[]} [options.files]    — specific files to stage (default: all)
 * @param {string}   [options.message]  — override commit message (skip AI generation)
 * @param {string}   [options.hint]     — hint for AI message generation
 * @param {boolean}  [options.all]      — stage all changes including untracked (default: true)
 * @returns {Promise<object>}
 */
export async function smartCommit(homeDir, options = {}) {
  const workDir = path.join(homeDir, 'public_html');

  // Check if it's a git repo
  try {
    await gitExec(homeDir, 'git rev-parse --is-inside-work-tree');
  } catch {
    throw new Error('Not a git repository. Run git init first.');
  }

  // Stage files
  if (options.files && options.files.length > 0) {
    const escaped = options.files.map(f => `"${f.replace(/"/g, '\\"')}"`).join(' ');
    await gitExec(homeDir, `git add ${escaped}`);
  } else if (options.all !== false) {
    await gitExec(homeDir, 'git add -A');
  }

  // Check if there are staged changes
  let stagedDiff;
  try {
    stagedDiff = await gitExec(homeDir, 'git diff --cached --stat');
  } catch {
    stagedDiff = '';
  }

  if (!stagedDiff) {
    return {
      committed: false,
      message: 'Nothing to commit — working tree clean or no staged changes.',
    };
  }

  // Get the full diff for message generation
  let fullDiff = '';
  try {
    fullDiff = await gitExec(homeDir, 'git diff --cached');
  } catch {
    fullDiff = stagedDiff; // fallback to stat
  }

  // Generate or use provided message
  let subject, body;
  if (options.message) {
    subject = options.message;
    body = '';
  } else {
    const generated = await generateMessage(fullDiff, options.hint || '');
    subject = generated.subject;
    body = generated.body;
  }

  // Build commit command
  const commitMsg = body ? `${subject}\n\n${body}` : subject;
  const escapedMsg = commitMsg.replace(/"/g, '\\"').replace(/\$/g, '\\$');

  try {
    const result = await gitExec(homeDir, `git commit -m "${escapedMsg}"`);

    // Get the commit hash
    const hash = await gitExec(homeDir, 'git rev-parse --short HEAD');

    return {
      committed: true,
      hash,
      subject,
      body: body || undefined,
      stats: stagedDiff,
      output: result,
    };
  } catch (err) {
    throw new Error(`Commit failed: ${err.message}`);
  }
}

/**
 * Amend the last commit with a new AI-generated message.
 *
 * @param {string} homeDir
 * @param {object} [options]
 * @param {string} [options.message] — override message
 * @returns {Promise<object>}
 */
export async function amendCommitMessage(homeDir, options = {}) {
  // Get the diff of the last commit
  let diff;
  try {
    diff = await gitExec(homeDir, 'git diff HEAD~1..HEAD');
  } catch {
    diff = await gitExec(homeDir, 'git log -1 --stat');
  }

  let subject, body;
  if (options.message) {
    subject = options.message;
    body = '';
  } else {
    const generated = await generateMessage(diff, options.hint || '');
    subject = generated.subject;
    body = generated.body;
  }

  const commitMsg = body ? `${subject}\n\n${body}` : subject;
  const escapedMsg = commitMsg.replace(/"/g, '\\"').replace(/\$/g, '\\$');

  await gitExec(homeDir, `git commit --amend -m "${escapedMsg}"`);
  const hash = await gitExec(homeDir, 'git rev-parse --short HEAD');

  return {
    amended: true,
    hash,
    subject,
    body: body || undefined,
  };
}
