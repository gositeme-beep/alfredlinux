/**
 * gitContext.js — Git Context Awareness
 *
 * Provides git status, diff, log, and branch information for Alfred
 * to understand the current state of code changes in the workspace.
 *
 * Uses child_process.exec to run git commands in the user's workspace.
 */

import { exec as execCb } from 'node:child_process';
import { promisify } from 'node:util';
import path from 'node:path';

const exec = promisify(execCb);
const TIMEOUT = 10000; // 10s

/**
 * Run a git command in the user's workspace.
 * @param {string} homeDir
 * @param {string} cmd
 * @returns {Promise<string>}
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
 * Check if the workspace is a git repository.
 */
export async function isGitRepo(homeDir) {
  try {
    await gitExec(homeDir, 'git rev-parse --is-inside-work-tree');
    return true;
  } catch {
    return false;
  }
}

/**
 * Get comprehensive git status — branch, changes, recent commits.
 *
 * @param {string} homeDir
 * @returns {Promise<object>}
 */
export async function getGitStatus(homeDir) {
  if (!await isGitRepo(homeDir)) {
    return { is_git_repo: false, message: 'This workspace is not a git repository.' };
  }

  const [branch, statusShort, log, remotes, stash] = await Promise.all([
    gitExec(homeDir, 'git branch --show-current').catch(() => 'unknown'),
    gitExec(homeDir, 'git status --short').catch(() => ''),
    gitExec(homeDir, 'git log --oneline -20 --no-decorate').catch(() => ''),
    gitExec(homeDir, 'git remote -v').catch(() => ''),
    gitExec(homeDir, 'git stash list').catch(() => ''),
  ]);

  // Parse status
  const lines = statusShort.split('\n').filter(Boolean);
  const staged = lines.filter(l => l[0] !== ' ' && l[0] !== '?').length;
  const unstaged = lines.filter(l => l[1] !== ' ' && l[0] !== '?').length;
  const untracked = lines.filter(l => l.startsWith('??')).length;

  // Parse remotes
  const remoteList = [...new Set(
    remotes.split('\n').filter(Boolean).map(l => l.split('\t')[0])
  )];

  return {
    is_git_repo: true,
    branch,
    staged_changes: staged,
    unstaged_changes: unstaged,
    untracked_files: untracked,
    total_changes: lines.length,
    changed_files: lines.slice(0, 50).map(l => ({
      status: l.slice(0, 2).trim(),
      file: l.slice(3),
    })),
    recent_commits: log.split('\n').filter(Boolean).slice(0, 20).map(l => {
      const [hash, ...rest] = l.split(' ');
      return { hash, message: rest.join(' ') };
    }),
    remotes: remoteList,
    stash_count: stash ? stash.split('\n').filter(Boolean).length : 0,
  };
}

/**
 * Get git diff — what's changed since last commit (or between refs).
 *
 * @param {string} homeDir
 * @param {object} options
 * @param {boolean} [options.staged=false] — only staged changes
 * @param {string} [options.ref] — compare against specific ref (e.g. "HEAD~3", "main")
 * @param {string} [options.file] — limit diff to specific file
 * @returns {Promise<object>}
 */
export async function getGitDiff(homeDir, options = {}) {
  if (!await isGitRepo(homeDir)) {
    return { is_git_repo: false, message: 'Not a git repository.' };
  }

  const { staged = false, ref = null, file = null } = options;

  let cmd = 'git diff';
  if (staged) cmd += ' --staged';
  if (ref) cmd += ` ${ref}`;
  cmd += ' --stat';
  if (file) cmd += ` -- ${file}`;

  const statOutput = await gitExec(homeDir, cmd).catch(() => '');

  // Full diff (limited to 50KB)
  let fullCmd = 'git diff';
  if (staged) fullCmd += ' --staged';
  if (ref) fullCmd += ` ${ref}`;
  if (file) fullCmd += ` -- ${file}`;

  let diffOutput = await gitExec(homeDir, fullCmd).catch(() => '');
  const truncated = diffOutput.length > 50000;
  if (truncated) diffOutput = diffOutput.slice(0, 50000) + '\n... (truncated)';

  // Count additions/deletions
  const addLines = (diffOutput.match(/^\+[^+]/gm) || []).length;
  const delLines = (diffOutput.match(/^-[^-]/gm) || []).length;

  return {
    is_git_repo: true,
    type: staged ? 'staged' : 'working',
    ref: ref || 'HEAD',
    stat: statOutput,
    diff: diffOutput || '(no changes)',
    additions: addLines,
    deletions: delLines,
    truncated,
  };
}

/**
 * Get git log with filtering and formatting.
 *
 * @param {string} homeDir
 * @param {object} options
 * @param {number} [options.count=20]
 * @param {string} [options.author]
 * @param {string} [options.since]
 * @param {string} [options.file]
 * @returns {Promise<object>}
 */
export async function getGitLog(homeDir, options = {}) {
  if (!await isGitRepo(homeDir)) {
    return { is_git_repo: false, message: 'Not a git repository.' };
  }

  const { count = 20, author = null, since = null, file = null } = options;

  let cmd = `git log --format="%H|%an|%ae|%aI|%s" -${Math.min(count, 100)}`;
  if (author) cmd += ` --author="${author}"`;
  if (since) cmd += ` --since="${since}"`;
  if (file) cmd += ` -- ${file}`;

  const output = await gitExec(homeDir, cmd).catch(() => '');

  const commits = output.split('\n').filter(Boolean).map(line => {
    const [hash, authorName, email, date, ...msgParts] = line.split('|');
    return {
      hash,
      author: authorName,
      email,
      date,
      message: msgParts.join('|'),
    };
  });

  return {
    is_git_repo: true,
    total: commits.length,
    commits,
  };
}

/**
 * Get list of branches with current branch marked.
 * @param {string} homeDir
 */
export async function getGitBranches(homeDir) {
  if (!await isGitRepo(homeDir)) {
    return { is_git_repo: false, message: 'Not a git repository.' };
  }

  const output = await gitExec(homeDir, 'git branch -a --format="%(refname:short)|%(objectname:short)|%(committerdate:relative)|%(HEAD)"');
  const branches = output.split('\n').filter(Boolean).map(line => {
    const [name, hash, date, head] = line.split('|');
    return { name, hash, date, current: head === '*' };
  });

  return {
    is_git_repo: true,
    current: branches.find(b => b.current)?.name || 'unknown',
    total: branches.length,
    branches,
  };
}
