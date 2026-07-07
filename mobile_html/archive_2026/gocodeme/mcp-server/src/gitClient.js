/**
 * gitClient.js — Git operations for MCP Server
 *
 * Wraps git commands for customer workspaces. Matches the middleware's git.js
 * routes but as a library callable from MCP tool dispatch.
 *
 * All paths are within /home/<username>/domains/<domain>/public_html.
 * Git repo is auto-initialized if it doesn't exist.
 */

import { shellExecFile } from './shellExec.js';
import path from 'path';
import fs from 'fs';

export class GitClient {
  /**
   * @param {string} homeDir — absolute path to customer home (e.g. /home/seller1_user1)
   *
   * Git operations run on the local Theia workspace mirror, NOT on the
   * remote DirectAdmin filesystem.  The Theia workspace lives at
   * /tmp/gocodeme-workspace-<daUsername>/ and is kept in sync with
   * the DA account by the file-sync daemon.
   *
   * If homeDir is /home/<user>, we derive the workspace path automatically.
   * If a custom workspaceRoot is set, we use that instead.
   */
  constructor(homeDir) {
    this.homeDir = homeDir;

    // Derive Theia workspace path from the DA username
    // homeDir = "/home/coopfath" → daUsername = "coopfath"
    const daUsername = path.basename(homeDir);
    this.workspaceRoot = `/tmp/gocodeme-workspace-${daUsername}`;

    // If the Theia workspace doesn't exist, fall back to homeDir (for local dev)
    if (!fs.existsSync(this.workspaceRoot)) {
      this.workspaceRoot = homeDir;
    }
  }

  /**
   * Resolve workspace path within the Theia workspace root.
   * @param {string} [workspace] — optional workspace path relative to workspace root
   */
  _cwd(workspace) {
    if (workspace) {
      const resolved = path.resolve(this.workspaceRoot, workspace);
      if (!resolved.startsWith(this.workspaceRoot)) throw new Error('Path escape blocked');
      return resolved;
    }
    // Default: the workspace root itself (contains domains/, etc.)
    return this.workspaceRoot;
  }

  /**
   * Ensure git repo exists at workspace path. Auto-init if needed.
   */
  _ensureRepo(cwd) {
    const gitDir = path.join(cwd, '.git');
    if (!fs.existsSync(gitDir)) {
      // Auto-initialize — use workspaceRoot as the homeDir for shell validation
      shellExecFile('git', ['init'], this.workspaceRoot, { cwd });
      shellExecFile('git', ['config', 'user.email', 'alfred@gocodeme.com'], this.workspaceRoot, { cwd });
      shellExecFile('git', ['config', 'user.name', 'Alfred (GoCodeMe AI)'], this.workspaceRoot, { cwd });

      // Create .gitignore if not present
      const gitignorePath = path.join(cwd, '.gitignore');
      if (!fs.existsSync(gitignorePath)) {
        const defaultIgnore = [
          'node_modules/', '.env', '.env.*', '!.env.example',
          '*.log', 'vendor/', '__pycache__/', '*.pyc',
          '.DS_Store', 'Thumbs.db', '.idea/', '.vscode/',
          'wp-content/uploads/', 'wp-content/cache/',
          'wp-content/upgrade/', 'wp-content/backup*/',
          '.claude/',
        ].join('\n') + '\n';
        fs.writeFileSync(gitignorePath, defaultIgnore);
      }

      // Initial commit if there are files
      shellExecFile('git', ['add', '-A'], this.workspaceRoot, { cwd });
      const result = shellExecFile('git', ['status', '--porcelain'], this.workspaceRoot, { cwd });
      if (result.stdout.trim()) {
        shellExecFile('git', ['commit', '-m', 'Initial commit by Alfred'], this.workspaceRoot, { cwd });
      }
    }
  }

  /**
   * Get working tree status.
   * @param {string} [workspace]
   * @returns {{ clean: boolean, staged: string[], modified: string[], untracked: string[], summary: string }}
   */
  status(workspace) {
    const cwd = this._cwd(workspace);
    this._ensureRepo(cwd);

    const result = shellExecFile('git', ['status', '--porcelain', '-uall'], this.workspaceRoot, { cwd });
    const lines = result.stdout.trim().split('\n').filter(Boolean);

    const staged = [];
    const modified = [];
    const untracked = [];

    for (const line of lines) {
      const x = line[0]; // index status
      const y = line[1]; // worktree status
      const file = line.slice(3);
      if (x === '?' && y === '?') untracked.push(file);
      else if (x !== ' ' && x !== '?') staged.push(file);
      else if (y !== ' ') modified.push(file);
    }

    const clean = lines.length === 0;
    const summary = clean
      ? 'Working tree is clean — no uncommitted changes.'
      : `${staged.length} staged, ${modified.length} modified, ${untracked.length} untracked files.`;

    return { clean, staged, modified, untracked, summary };
  }

  /**
   * Get recent commit log.
   * @param {number} [limit=20]
   * @param {string} [workspace]
   */
  log(limit = 20, workspace) {
    const cwd = this._cwd(workspace);
    this._ensureRepo(cwd);

    const result = shellExecFile(
      'git',
      ['log', `--max-count=${limit}`, '--format=%H|%h|%an|%ar|%s'],
      this.workspaceRoot,
      { cwd }
    );

    if (result.exitCode !== 0 && result.stderr.includes('does not have any commits')) {
      return { commits: [], total: 0 };
    }

    const commits = result.stdout.trim().split('\n').filter(Boolean).map(line => {
      const [hash, short, author, relDate, ...rest] = line.split('|');
      return { hash, short, author, relativeDate: relDate, message: rest.join('|') };
    });

    return { commits, total: commits.length };
  }

  /**
   * Show diff of working tree vs HEAD.
   * @param {string} [workspace]
   */
  diff(workspace) {
    const cwd = this._cwd(workspace);
    this._ensureRepo(cwd);

    // Staged diff
    const staged = shellExecFile('git', ['diff', '--cached', '--stat'], this.workspaceRoot, { cwd });
    // Unstaged diff
    const unstaged = shellExecFile('git', ['diff', '--stat'], this.workspaceRoot, { cwd });
    // Full diff (limited)
    const full = shellExecFile('git', ['diff', 'HEAD'], this.workspaceRoot, { cwd });

    // Truncate full diff to 50KB to avoid MCP response overflow
    const diffText = full.stdout.length > 50000
      ? full.stdout.slice(0, 50000) + '\n... [diff truncated at 50KB]'
      : full.stdout;

    return {
      stagedSummary: staged.stdout.trim() || 'No staged changes.',
      unstagedSummary: unstaged.stdout.trim() || 'No unstaged changes.',
      diff: diffText || 'No differences.',
    };
  }

  /**
   * Stage all changes and commit with a message.
   * @param {string} message — commit message
   * @param {string} [workspace]
   */
  commit(message, workspace) {
    const cwd = this._cwd(workspace);
    this._ensureRepo(cwd);

    // Stage all
    shellExecFile('git', ['add', '-A'], this.workspaceRoot, { cwd });

    // Check if there's anything to commit
    const status = shellExecFile('git', ['status', '--porcelain'], this.workspaceRoot, { cwd });
    if (!status.stdout.trim()) {
      return { committed: false, message: 'Nothing to commit — working tree is clean.' };
    }

    const result = shellExecFile(
      'git',
      ['commit', '-m', message],
      this.workspaceRoot,
      { cwd }
    );

    if (result.exitCode !== 0) {
      return { committed: false, message: `Commit failed: ${result.stderr}` };
    }

    // Get the new commit hash
    const hashResult = shellExecFile('git', ['rev-parse', '--short', 'HEAD'], this.workspaceRoot, { cwd });
    const hash = hashResult.stdout.trim();

    return {
      committed: true,
      hash,
      message: `Committed: ${hash} — ${message}`,
    };
  }

  /**
   * Revert the last commit (keeping changes in working tree).
   * @param {string} [workspace]
   */
  revert(workspace) {
    const cwd = this._cwd(workspace);
    this._ensureRepo(cwd);

    // Check there's a commit to revert
    const logResult = shellExecFile('git', ['log', '--oneline', '-1'], this.workspaceRoot, { cwd });
    if (!logResult.stdout.trim()) {
      return { reverted: false, message: 'No commits to revert.' };
    }

    const lastCommit = logResult.stdout.trim();

    // Soft reset — undo commit but keep files changed
    const result = shellExecFile('git', ['reset', '--soft', 'HEAD~1'], this.workspaceRoot, { cwd });
    if (result.exitCode !== 0) {
      return { reverted: false, message: `Revert failed: ${result.stderr}` };
    }

    return {
      reverted: true,
      message: `Reverted commit: ${lastCommit}. Changes are preserved in staging area.`,
    };
  }

  // ══════════════════════════════════════════════════════════════════════
  // CHECKPOINT SYSTEM — Named restore points for AI interactions
  // ══════════════════════════════════════════════════════════════════════

  /**
   * Create a named checkpoint — stages all changes and commits with a
   * [CHECKPOINT] prefix.  Returns the commit hash so it can be referenced
   * later by restore_checkpoint or list_checkpoints.
   *
   * @param {string}  label       — human-readable label (e.g. "before header redesign")
   * @param {string}  [workspace]
   * @returns {{ created: boolean, hash?: string, label: string, message: string }}
   */
  createCheckpoint(label, workspace) {
    const cwd = this._cwd(workspace);
    this._ensureRepo(cwd);

    // Stage everything so the checkpoint captures current state
    shellExecFile('git', ['add', '-A'], this.workspaceRoot, { cwd });

    // Check if there is anything to commit
    const status = shellExecFile('git', ['status', '--porcelain'], this.workspaceRoot, { cwd });
    if (!status.stdout.trim()) {
      // Nothing to commit — create an empty "marker" checkpoint so restore
      // still has a target.  Use --allow-empty.
      const result = shellExecFile(
        'git',
        ['commit', '--allow-empty', '-m', `[CHECKPOINT] ${label}`],
        this.workspaceRoot,
        { cwd }
      );
      if (result.exitCode !== 0) {
        return { created: false, label, message: `Checkpoint failed: ${result.stderr}` };
      }
    } else {
      const result = shellExecFile(
        'git',
        ['commit', '-m', `[CHECKPOINT] ${label}`],
        this.workspaceRoot,
        { cwd }
      );
      if (result.exitCode !== 0) {
        return { created: false, label, message: `Checkpoint failed: ${result.stderr}` };
      }
    }

    const hashResult = shellExecFile('git', ['rev-parse', '--short', 'HEAD'], this.workspaceRoot, { cwd });
    const hash = hashResult.stdout.trim();

    return {
      created: true,
      hash,
      label,
      message: `✅ Checkpoint created: ${hash} — "${label}"`,
    };
  }

  /**
   * List all checkpoints (commits whose message starts with [CHECKPOINT]).
   *
   * @param {number}  [limit=50]
   * @param {string}  [workspace]
   * @returns {{ checkpoints: Array<{ hash: string, short: string, label: string, relativeDate: string }>, total: number }}
   */
  listCheckpoints(limit = 50, workspace) {
    const cwd = this._cwd(workspace);
    this._ensureRepo(cwd);

    const result = shellExecFile(
      'git',
      ['log', `--max-count=${limit}`, '--format=%H|%h|%ar|%s', '--grep=\\[CHECKPOINT\\]'],
      this.workspaceRoot,
      { cwd }
    );

    if (result.exitCode !== 0) {
      return { checkpoints: [], total: 0 };
    }

    const checkpoints = result.stdout.trim().split('\n').filter(Boolean).map(line => {
      const [hash, short, relDate, ...rest] = line.split('|');
      const fullMsg = rest.join('|');
      // Strip the [CHECKPOINT] prefix to get the label
      const label = fullMsg.replace(/^\[CHECKPOINT\]\s*/, '');
      return { hash, short, label, relativeDate: relDate };
    });

    return { checkpoints, total: checkpoints.length };
  }

  /**
   * Restore workspace to a checkpoint.  This does a hard reset to the
   * given commit hash, discarding any uncommitted changes.
   *
   * A safety checkpoint is automatically created before the restore so
   * the user can undo the restore if needed.
   *
   * @param {string}  commitHash — full or short hash from list_checkpoints
   * @param {string}  [workspace]
   * @returns {{ restored: boolean, hash: string, label: string, safetyHash?: string, message: string }}
   */
  restoreCheckpoint(commitHash, workspace) {
    const cwd = this._cwd(workspace);
    this._ensureRepo(cwd);

    // Validate the target commit exists
    const verify = shellExecFile('git', ['cat-file', '-t', commitHash], this.workspaceRoot, { cwd });
    if (verify.exitCode !== 0 || verify.stdout.trim() !== 'commit') {
      return {
        restored: false,
        hash: commitHash,
        label: '',
        message: `Invalid checkpoint hash: ${commitHash}`,
      };
    }

    // Get the label of the target checkpoint
    const msgResult = shellExecFile(
      'git',
      ['log', '--format=%s', '-1', commitHash],
      this.workspaceRoot,
      { cwd }
    );
    const targetMsg = msgResult.stdout.trim();
    const label = targetMsg.replace(/^\[CHECKPOINT\]\s*/, '');

    // Create a safety checkpoint of current state before restoring
    let safetyHash;
    shellExecFile('git', ['add', '-A'], this.workspaceRoot, { cwd });
    const safetyStatus = shellExecFile('git', ['status', '--porcelain'], this.workspaceRoot, { cwd });
    // Always create the safety commit (allow-empty) so there's a way back
    const safetyResult = shellExecFile(
      'git',
      ['commit', '--allow-empty', '-m', `[CHECKPOINT] Auto-save before restoring to "${label}"`],
      this.workspaceRoot,
      { cwd }
    );
    if (safetyResult.exitCode === 0) {
      const sh = shellExecFile('git', ['rev-parse', '--short', 'HEAD'], this.workspaceRoot, { cwd });
      safetyHash = sh.stdout.trim();
    }

    // Hard reset to the target checkpoint
    const resetResult = shellExecFile(
      'git',
      ['reset', '--hard', commitHash],
      this.workspaceRoot,
      { cwd }
    );

    if (resetResult.exitCode !== 0) {
      return {
        restored: false,
        hash: commitHash,
        label,
        safetyHash,
        message: `Restore failed: ${resetResult.stderr}`,
      };
    }

    return {
      restored: true,
      hash: commitHash,
      label,
      safetyHash,
      message: `✅ Workspace restored to checkpoint "${label}" (${commitHash}).${safetyHash ? ` Safety backup: ${safetyHash}` : ''}`,
    };
  }

  /**
   * Initialize a git repo (explicit call, also creates .gitignore).
   * @param {string} [workspace]
   */
  init(workspace) {
    const cwd = this._cwd(workspace);

    const gitDir = path.join(cwd, '.git');
    if (fs.existsSync(gitDir)) {
      return { initialized: false, message: 'Git repository already exists.' };
    }

    shellExecFile('git', ['init'], this.workspaceRoot, { cwd });
    shellExecFile('git', ['config', 'user.email', 'alfred@gocodeme.com'], this.workspaceRoot, { cwd });
    shellExecFile('git', ['config', 'user.name', 'Alfred (GoCodeMe AI)'], this.workspaceRoot, { cwd });

    // Create default .gitignore if it doesn't exist
    const gitignorePath = path.join(cwd, '.gitignore');
    if (!fs.existsSync(gitignorePath)) {
      const defaultIgnore = [
        'node_modules/', '.env', '.env.*', '!.env.example',
        '*.log', 'vendor/', '__pycache__/', '*.pyc',
        '.DS_Store', 'Thumbs.db', '.idea/', '.vscode/',
        'wp-content/uploads/', 'wp-content/cache/',
        'wp-content/upgrade/', 'wp-content/backup*/',
      ].join('\n') + '\n';
      fs.writeFileSync(gitignorePath, defaultIgnore);
    }

    // Initial commit
    shellExecFile('git', ['add', '-A'], this.workspaceRoot, { cwd });
    const statusResult = shellExecFile('git', ['status', '--porcelain'], this.workspaceRoot, { cwd });
    if (statusResult.stdout.trim()) {
      shellExecFile('git', ['commit', '-m', 'Initial commit by Alfred'], this.workspaceRoot, { cwd });
    }

    return { initialized: true, message: `Git repository initialized at ${workspace || 'public_html'}.` };
  }
}
