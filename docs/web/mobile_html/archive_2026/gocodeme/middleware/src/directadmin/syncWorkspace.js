'use strict';

/**
 * syncWorkspace.js — Download a customer's website from DirectAdmin into a local directory.
 *
 * Uses the DA File Manager API (via fileManager.js) to recursively list and
 * download all files from the customer's domain into a local workspace directory
 * so Theia can browse/edit them.
 *
 * After edits, changes are pushed back to DA via the existing writeFile API.
 *
 * Limits:
 *   - MAX_FILE_SIZE: skip files > 5 MB (binary assets etc.)
 *   - MAX_FILES:     stop after 2000 files to avoid runaway syncs
 *   - Skips node_modules, .git, vendor, __pycache__ directories
 *
 * Performance:
 *   - Parallel file downloads (CONCURRENCY workers) instead of sequential
 *   - Incremental sync: skips files whose local size matches DA size
 *   - Parallel domain syncing (DOMAIN_CONCURRENCY)
 */

const fs   = require('fs');
const path = require('path');
const { execSync } = require('child_process');
const { listFiles, readFile, readFileBinary, isBinaryFile } = require('./fileManager');
const logger = require('../logger');

const MAX_FILE_SIZE       = 5 * 1024 * 1024;  // 5 MB
const MAX_FILES           = 10000;  // raised from 2000 — enough for 17+ domains

/** Restores AI model picker: Claude + Groq (global .gocodeme-ide often lacked these) */
const GOCODEME_ANTHROPIC_MODELS = [
  'claude-sonnet-4-6',
  'claude-opus-4-6',
  'claude-haiku-4-5-20251001',
  'claude-sonnet-4-5-20250929',
  'claude-opus-4-5-20251101',
  'claude-opus-4-1-20250805',
  'claude-sonnet-4-20250514',
  'claude-opus-4-20250514',
];
const _groqKey = process.env.GROQ_API_KEY || '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';
const GOCODEME_OPENAI_CUSTOM_MODELS = [
  { model: 'llama-3.3-70b-versatile', url: 'https://api.groq.com/openai/v1', apiKey: _groqKey },
  { model: 'llama-3.1-8b-instant', url: 'https://api.groq.com/openai/v1', apiKey: _groqKey },
  { model: 'gemma2-9b-it', url: 'https://api.groq.com/openai/v1', apiKey: _groqKey },
  { model: 'mixtral-8x7b-32768', url: 'https://api.groq.com/openai/v1', apiKey: _groqKey },
];
const CONCURRENCY         = 10;   // parallel file downloads per directory level
const DOMAIN_CONCURRENCY  = 5;    // parallel domain syncs (raised from 3)
const SKIP_DIRS     = new Set([
  'node_modules', '.git', 'vendor', '__pycache__', '.cache',
  '.npm', '.yarn', 'bower_components', 'cgi-bin',
]);

/**
 * Run up to `limit` async tasks in parallel (worker-pool pattern).
 * Each worker pulls the next item from the shared index.
 */
async function parallelMap(items, fn, limit) {
  let idx = 0;
  async function worker() {
    while (idx < items.length) {
      const i = idx++;
      await fn(items[i]);
    }
  }
  const workers = Array.from(
    { length: Math.min(limit, items.length) },
    () => worker()
  );
  await Promise.all(workers);
}

/**
 * Recursively sync a DA directory into a local path.
 *
 * Downloads files in parallel (CONCURRENCY workers) and recurses into
 * subdirectories concurrently for maximum throughput.
 *
 * @param {string} daUsername    - DA username for API auth
 * @param {string} remotePath   - Path relative to user's home in DA
 * @param {string} localDir     - Local directory to write files into
 * @param {object} ctx          - Shared context with { fileCount, skipped } counter
 * @param {number} depth        - Current recursion depth (safety limit)
 */
async function syncDir(daUsername, remotePath, localDir, ctx, depth = 0) {
  if (depth > 15) return;   // safety: prevent infinite recursion
  if (ctx.fileCount >= MAX_FILES) return;

  // Ensure local directory exists
  fs.mkdirSync(localDir, { recursive: true });

  let entries;
  try {
    entries = await listFiles(daUsername, remotePath);
  } catch (err) {
    logger.warn(`syncWorkspace: cannot list ${remotePath}: ${err.message}`);
    return;
  }

  const fileTasks = [];
  const dirTasks  = [];

  for (const entry of entries) {
    if (ctx.fileCount + fileTasks.length >= MAX_FILES) break;

    const basename = path.posix.basename(entry.path);
    // Skip parent-dir refs — DA returns the parent as an entry with path="/" or same as remotePath
    if (!basename || basename === '.' || basename === '..' || entry.path === '/') continue;
    // Also skip if entry.path doesn't start with the expected remote path prefix
    const entryClean = entry.path.replace(/^\/+/, '');
    if (!entryClean.startsWith(remotePath.replace(/^\/+/, ''))) continue;

    const localPath  = path.join(localDir, basename);
    const remoteFile = entry.path.replace(/^\/+/, '');  // DA returns "/domains/..." — strip leading /

    if (entry.type === 'dir') {
      // Skip known large/useless directories
      if (SKIP_DIRS.has(basename.toLowerCase())) continue;
      dirTasks.push({ remoteFile, localPath });
    } else {
      // Skip oversized files
      const size = parseInt(entry.size, 10) || 0;
      if (size > MAX_FILE_SIZE) {
        logger.debug(`syncWorkspace: skipping large file ${remoteFile} (${size} bytes)`);
        continue;
      }

      // Incremental sync: skip files whose local copy matches DA size
      try {
        const stat = fs.statSync(localPath);
        if (stat.size === size && size > 0) {
          ctx.fileCount++;
          ctx.skipped = (ctx.skipped || 0) + 1;
          continue;
        }
      } catch { /* file doesn't exist locally — download it */ }

      fileTasks.push({ remoteFile, localPath });
    }
  }

  // ── Download files in parallel (CONCURRENCY workers) ───────────────────
  await parallelMap(fileTasks, async (task) => {
    if (ctx.fileCount >= MAX_FILES) return;
    try {
      if (isBinaryFile(task.remoteFile)) {
        const buffer = await readFileBinary(daUsername, task.remoteFile);
        fs.writeFileSync(task.localPath, buffer);
      } else {
        const content = await readFile(daUsername, task.remoteFile);
        fs.writeFileSync(task.localPath, content, 'utf-8');
      }
      ctx.fileCount++;
    } catch (err) {
      // Binary files or permission errors — skip silently
      logger.debug(`syncWorkspace: cannot read ${task.remoteFile}: ${err.message}`);
    }
  }, CONCURRENCY);

  // ── Recurse into subdirectories in parallel ────────────────────────────
  await parallelMap(dirTasks, async (task) => {
    await syncDir(daUsername, task.remoteFile, task.localPath, ctx, depth + 1);
  }, Math.min(CONCURRENCY, 5));
}

/**
 * Sync a customer's website from DA into a local workspace directory.
 *
 * When domains/ is a symlink to the user's real home directory, file sync is
 * skipped entirely (direct filesystem access, no DA API overhead).  Only the
 * .code-workspace config and settings are generated.
 *
 * When domains/ is NOT a symlink (e.g. remote DA or isolated sandbox), the
 * original DA API download logic runs.
 *
 * @param {string} daUsername   - DirectAdmin username (e.g. "jabelaqu")
 * @param {string} localDir    - Local directory to populate (e.g. "/tmp/gocodeme-workspace-jabela")
 * @param {string} [domain]    - Optional: specific domain. If omitted, syncs domains/<first domain>/public_html
 * @returns {Promise<{files: number, errors: string[]}>}
 */
async function syncWorkspace(daUsername, localDir, domain) {
  const ctx = { fileCount: 0, skipped: 0 };
  const startTime = Date.now();

  let domainsSynced = [];

  // ── Check if domains/ is a symlink (direct filesystem access) ───────────
  // When the workspace has a symlink to /home/<user>/domains/, the IDE can
  // read/write files directly — no need for expensive DA API downloads.
  const domainsPath = path.join(localDir, 'domains');
  let isSymlink = false;
  try {
    const stat = fs.lstatSync(domainsPath);
    isSymlink = stat.isSymbolicLink();
  } catch { /* does not exist yet */ }

  if (isSymlink) {
    // ── Symlink mode: discover domains from local filesystem ────────────
    logger.info(`syncWorkspace: domains/ is a symlink — skipping DA API sync, using direct filesystem access`);
    try {
      const entries = fs.readdirSync(domainsPath, { withFileTypes: true });
      for (const entry of entries) {
        if (entry.isDirectory() && entry.name.includes('.')) {
          domainsSynced.push(entry.name);
        }
      }
      logger.info(`syncWorkspace: discovered ${domainsSynced.length} domain(s) from filesystem`);
    } catch (err) {
      logger.warn(`syncWorkspace: cannot read domains symlink: ${err.message}`);
    }
  } else if (domain) {
    // Specific domain requested — sync into workspace/domains/<domain>/
    const remotePath = `domains/${domain}/public_html`;
    const domainDir = path.join(localDir, 'domains', domain);
    logger.info(`syncWorkspace: syncing ${daUsername}:${remotePath} → ${domainDir}`);
    await syncDir(daUsername, remotePath, domainDir, ctx);
    domainsSynced.push(domain);
  } else {
    // Auto-detect ALL domains and sync each one via DA API
    try {
      const domainEntries = await listFiles(daUsername, 'domains');
      const domainDirs = domainEntries.filter(e => {
        if (e.type !== 'dir') return false;
        const name = path.posix.basename(e.path);
        return name && name !== '/' && name !== '..' && name.includes('.');
      });

      if (domainDirs.length > 0) {
        logger.info(`syncWorkspace: found ${domainDirs.length} domain(s) for ${daUsername} — syncing in parallel (max ${DOMAIN_CONCURRENCY})`);
        fs.mkdirSync(path.join(localDir, 'domains'), { recursive: true });

        const allDomainNames = domainDirs.map(e => path.posix.basename(e.path));
        for (const dn of allDomainNames) {
          const domainLocalDir = path.join(localDir, 'domains', dn);
          fs.mkdirSync(domainLocalDir, { recursive: true });
          domainsSynced.push(dn);
        }

        await parallelMap(domainDirs, async (entry) => {
          if (ctx.fileCount >= MAX_FILES) {
            logger.warn(`syncWorkspace: MAX_FILES (${MAX_FILES}) reached — remaining domains will have empty dirs`);
            return;
          }
          const domainName = path.posix.basename(entry.path);
          const remotePath = `domains/${domainName}/public_html`;
          const domainLocalDir = path.join(localDir, 'domains', domainName);
          logger.info(`syncWorkspace: syncing domain ${domainName}...`);
          await syncDir(daUsername, remotePath, domainLocalDir, ctx);
        }, DOMAIN_CONCURRENCY);
      } else {
        logger.info(`syncWorkspace: no domains found, syncing public_html directly`);
        await syncDir(daUsername, 'public_html', localDir, ctx);
      }
    } catch (err) {
      logger.warn(`syncWorkspace: domain listing failed, falling back: ${err.message}`);
      await syncDir(daUsername, 'public_html', localDir, ctx);
    }
  }

  // Build remotePath for backward compatibility (use first domain synced)
  const remotePath = domainsSynced.length > 0
    ? `domains/${domainsSynced[0]}/public_html`
    : 'public_html';

  // ── Write multi-root .code-workspace so domains appear at top level ──────
  // Each synced domain becomes its own workspace root in the explorer tree,
  // eliminating the extra "domains/" click.  Sorted alphabetically with the
  // first domain listed as primary.
  // ── Write multi-root .code-workspace so domains appear at top level ──────
  // Each synced domain becomes its own workspace root in the explorer tree,
  // eliminating the extra "domains/" click. Sorted alphabetically with the
  // first domain listed as primary.
  const workspaceFile = path.join(localDir, 'gocodeme.code-workspace');
  let sortedDomains = [...domainsSynced].sort((a, b) => a.localeCompare(b));
  
  // Fallback: if no domains found (API failure), add default roots so the IDE isn't empty
  if (sortedDomains.length === 0) {
    sortedDomains = [daUsername]; 
  }

  const folders = sortedDomains.map(d => {
    // If it looks like a domain (has a dot), use domains/path; else use root path
    const folderPath = d.includes('.') ? `domains/${d}` : '.';
    return {
      path: folderPath,
      name: d,
    };
  });

  // Workspace-level settings embedded in the .code-workspace file
  const wsSettings = {
    'workbench.startupEditor': 'none',
    'workbench.welcomePage.walkthroughs.openOnInstall': false,
    'files.enableTrash': false,
    'security.workspace.trust.enabled': false,
    'terminal.integrated.shell.linux': '/home/gositeme/domains/gositeme.com/public_html/gocodeme/scripts/gocodeme-shell.sh',
    'terminal.integrated.defaultProfile.linux': 'gocodeme',
    'terminal.integrated.profiles.linux': {
      'gocodeme': {
        'path': '/home/gositeme/domains/gositeme.com/public_html/gocodeme/scripts/gocodeme-shell.sh',
        'icon': 'terminal',
      },
    },
    'workbench.localHistory.enabled': true,
    'workbench.localHistory.maxFileEntries': 50,
    'workbench.localHistory.maxFileSize': 1048576,
    'workbench.localHistory.mergeWindow': 10,
    'files.autoSave': 'afterDelay',
    'files.autoSaveDelay': 2000,
    'editor.wordWrap': 'on',
    'editor.minimap.enabled': false,
    'editor.bracketPairColorization.enabled': true,
    'editor.guides.bracketPairs': true,
    'scm.defaultViewMode': 'tree',
    'git.autofetch': false,
    'git.enableSmartCommit': true,
    'git.confirmSync': false,
    'files.exclude': {
      '**/.git': true,
      '**/.svn': true,
      '**/.hg': true,
      '**/CVS': true,
      '**/.DS_Store': true,
      '**/.gocodeme': true,
      '**/.gocodeme-ide': true,
      '**/.vscode': true,
      '**/.npm': true,
      '**/.cache': true,
      '**/.claude': true,
      '**/.bash_history': true,
      '**/.gitignore': true,
    },
    'explorer.compactFolders': true,
    'explorer.autoReveal': true,
    'explorer.sortOrder': 'default',
  };
  const wsData = { folders, settings: wsSettings };
  fs.writeFileSync(workspaceFile, JSON.stringify(wsData, null, 2), 'utf-8');
  logger.info(`syncWorkspace: wrote gocodeme.code-workspace with ${folders.length} roots (fallback used: ${domainsSynced.length === 0})`);

  // Write default IDE settings — AI enabled, Welcome tab hidden
  const settingsDir = path.join(localDir, '.gocodeme');
  fs.mkdirSync(settingsDir, { recursive: true });

  // Create .claude/hooks directory (required by ClaudeCode agent to avoid EACCES errors)
  const claudeHooksDir = path.join(localDir, '.claude', 'hooks');
  fs.mkdirSync(claudeHooksDir, { recursive: true });

  const settingsPath = path.join(settingsDir, 'settings.json');
  const defaults = {
    'workbench.startupEditor': 'none',
    'workbench.welcomePage.walkthroughs.openOnInstall': false,
    'files.enableTrash': false,
    'security.workspace.trust.enabled': false,
    'ai-features.AiEnable.enableAI': true,
    'ai-features.aiEnable': true,
    'ai-features.chat.defaultChatAgent': 'Universal',
    'terminal.integrated.shell.linux': '/home/gositeme/domains/gositeme.com/public_html/gocodeme/scripts/gocodeme-shell.sh',
    'terminal.integrated.defaultProfile.linux': 'gocodeme',
    'terminal.integrated.profiles.linux': {
      'gocodeme': {
        'path': '/home/gositeme/domains/gositeme.com/public_html/gocodeme/scripts/gocodeme-shell.sh',
        'icon': 'terminal',
      },
    },
    // Local history — "restore previous versions" for every file
    'workbench.localHistory.enabled': true,
    'workbench.localHistory.maxFileEntries': 50,
    'workbench.localHistory.maxFileSize': 1048576,
    'workbench.localHistory.mergeWindow': 10,
    // Auto-save (triggers file sync to DA docroot)
    'files.autoSave': 'afterDelay',
    'files.autoSaveDelay': 2000,
    // Editor quality-of-life
    'editor.wordWrap': 'on',
    'editor.minimap.enabled': false,
    'editor.bracketPairColorization.enabled': true,
    'editor.guides.bracketPairs': true,
    // Git / SCM
    'scm.defaultViewMode': 'tree',
    'git.autofetch': false,
    'git.enableSmartCommit': true,
    'git.confirmSync': false,
    // Hide IDE config folders from file explorer
    'files.exclude': {
      '**/.git': true,
      '**/.svn': true,
      '**/.hg': true,
      '**/CVS': true,
      '**/.DS_Store': true,
      '**/.gocodeme': true,
      '**/.gocodeme-ide': true,
      '**/.vscode': true,
      '**/.npm': true,
      '**/.cache': true,
      '**/.claude': true,
      '**/.bash_history': true,
      '**/.gitignore': true,
    },
    // Explorer: auto-expand domains/ folder so users see their sites immediately
    'explorer.compactFolders': true,
    'explorer.autoReveal': true,
    'explorer.sortOrder': 'default',
    'explorer.expandSingleFolderWorkspaces': true,
  };
  // Always write — ensure AI enable keys are present even if file exists
  let existing = {};
  try { existing = JSON.parse(fs.readFileSync(settingsPath, 'utf-8')); } catch {}
  const merged = {
    ...defaults,
    ...existing,
    'ai-features.AiEnable.enableAI': true,
    'ai-features.aiEnable': true,
    'ai-features.chat.defaultChatAgent': 'Universal',
    'ai-features.anthropic.AnthropicModels': [...GOCODEME_ANTHROPIC_MODELS],
    'ai-features.openAiCustom.customOpenAiModels': GOCODEME_OPENAI_CUSTOM_MODELS.map(m => ({ ...m })),
    'ai-features.defaultModelId': existing['ai-features.defaultModelId'] || 'claude-sonnet-4-6',
    'ai-features.languageModelAliases': {
      ...(existing['ai-features.languageModelAliases'] || {}),
      'default/code': { selectedModel: 'anthropic/claude-sonnet-4-6' },
      'default/universal': (existing['ai-features.languageModelAliases'] || {})['default/universal'] || { selectedModel: 'anthropic/claude-sonnet-4-6' },
    },
  };
  // Always inject MCP server config — eliminates race condition with start-theia.sh.
  // If start-theia.sh already set a JWT-based config, preserve it; otherwise use
  // localhost bypass URL with daUsername query param (trusted from 127.0.0.1).
  if (existing['ai-features.mcp.mcpServers']) {
    merged['ai-features.mcp.mcpServers'] = existing['ai-features.mcp.mcpServers'];
  } else {
    merged['ai-features.mcp.mcpServers'] = {
      'gocodeme-files': {
        serverUrl: `http://localhost:3006/mcp?daUsername=${daUsername}`,
        autostart: true,
      },
    };
  }
  fs.writeFileSync(settingsPath, JSON.stringify(merged, null, 2), 'utf-8');
  logger.info(`syncWorkspace: wrote .gocodeme/settings.json (AI+MCP for ${daUsername})`);

  // ── Write global settings (.gocodeme-ide/settings.json) with MCP config ────
  const configFolder = path.join(localDir, '.gocodeme-ide');
  fs.mkdirSync(configFolder, { recursive: true });
  const globalSettingsPath = path.join(configFolder, 'settings.json');
  {
    let gs = {};
    try { gs = JSON.parse(fs.readFileSync(globalSettingsPath, 'utf-8')); } catch {}
    gs['ai-features.AiEnable.enableAI'] = true;
    gs['ai-features.aiEnable'] = true;
    gs['ai-features.chat.enable'] = true;
    gs['ai-features.chat.defaultChatAgent'] = 'Universal';
    gs['ai-features.codeCompletion.enable'] = true;
    gs['ai-features.inlineCompletion.enable'] = true;
    gs['ai-features.claudeCode.enable'] = true;
    gs['security.workspace.trust.enabled'] = false;
    gs['ai-features.defaultModelId'] = gs['ai-features.defaultModelId'] || 'claude-sonnet-4-6';
    gs['ai-features.anthropic.AnthropicModels'] = [...GOCODEME_ANTHROPIC_MODELS];
    gs['ai-features.openAiCustom.customOpenAiModels'] = GOCODEME_OPENAI_CUSTOM_MODELS.map(m => ({ ...m }));
    const gAliases = gs['ai-features.languageModelAliases'] || {};
    gAliases['default/code'] = { selectedModel: 'anthropic/claude-sonnet-4-6' };
    gAliases['default/universal'] = gAliases['default/universal'] || { selectedModel: 'anthropic/claude-sonnet-4-6' };
    gs['ai-features.languageModelAliases'] = gAliases;
    // Inject MCP — preserve JWT-based config from start-theia.sh if present
    const mcpKey = 'ai-features.mcp.mcpServers';
    if (!gs[mcpKey] || !gs[mcpKey]['gocodeme-files']) {
      gs[mcpKey] = gs[mcpKey] || {};
      gs[mcpKey]['gocodeme-files'] = {
        serverUrl: `http://localhost:3006/mcp?daUsername=${daUsername}`,
        autostart: true,
      };
    }
    fs.writeFileSync(globalSettingsPath, JSON.stringify(gs, null, 2), 'utf-8');
    logger.info(`syncWorkspace: wrote .gocodeme-ide/settings.json (MCP for ${daUsername})`);
  }

  // ── Write default toolbar (terminal, panel toggles, etc.) ────────────────
  const toolbarPath = path.join(configFolder, 'toolbar.json');
  if (!fs.existsSync(toolbarPath)) {
    const toolbar = {
      items: {
        left: [
          [
            { id: 'textEditor.commands.go.back', command: 'textEditor.commands.go.back', icon: 'codicon codicon-arrow-left', tooltip: 'Go Back' },
            { id: 'textEditor.commands.go.forward', command: 'textEditor.commands.go.forward', icon: 'codicon codicon-arrow-right', tooltip: 'Go Forward' },
          ],
          [
            { id: 'workbench.action.splitEditorRight', command: 'workbench.action.splitEditorRight', icon: 'codicon codicon-split-horizontal', tooltip: 'Split Editor' },
          ],
          [
            { id: 'core.toggle.left.panel', command: 'core.toggle.left.panel', icon: 'codicon codicon-layout-sidebar-left', tooltip: 'Toggle Left Panel' },
            { id: 'core.toggle.bottom.panel', command: 'core.toggle.bottom.panel', icon: 'codicon codicon-layout-panel', tooltip: 'Toggle Bottom Panel' },
            { id: 'core.toggle.right.panel', command: 'core.toggle.right.panel', icon: 'codicon codicon-layout-sidebar-right', tooltip: 'Toggle Right Panel' },
          ],
        ],
        center: [[]],
        right: [
          [
            { id: 'workbench.action.terminal.toggleTerminal', command: 'workbench.action.terminal.toggleTerminal', icon: 'codicon codicon-terminal', tooltip: 'Toggle Terminal' },
          ],
          [
            { id: 'workbench.action.showCommands', command: 'workbench.action.showCommands', icon: 'codicon codicon-symbol-event', tooltip: 'Command Palette' },
          ],
        ],
      },
    };
    fs.writeFileSync(toolbarPath, JSON.stringify(toolbar, null, 4), 'utf-8');
    logger.info('syncWorkspace: wrote .gocodeme-ide/toolbar.json');
  }

  // ── Initialize Git repository for version history ───────────────────────
  const gitDir = path.join(localDir, '.git');
  if (!fs.existsSync(gitDir)) {
    try {
      // SECURITY (R3-03): Use execFile (no shell) to prevent command injection via daUsername
      const { execFileSync } = require('child_process');
      execFileSync('git', ['init'], { cwd: localDir, stdio: 'pipe' });
      execFileSync('git', ['config', 'user.email', `${daUsername}@gocodeme.com`], { cwd: localDir, stdio: 'pipe' });
      execFileSync('git', ['config', 'user.name', 'GoCodeMe User'], { cwd: localDir, stdio: 'pipe' });
      // Write .gitignore to exclude IDE metadata
      const gitignorePath = path.join(localDir, '.gitignore');
      if (!fs.existsSync(gitignorePath)) {
        fs.writeFileSync(gitignorePath, '.gocodeme/\n.gocodeme-ide/\ngocodeme.code-workspace\nnode_modules/\n.cache/\n', 'utf-8');
      }
      execFileSync('git', ['add', '-A'], { cwd: localDir, stdio: 'pipe' });
      execFileSync('git', ['commit', '-m', 'Initial snapshot', '--allow-empty'], { cwd: localDir, stdio: 'pipe' });
      logger.info('syncWorkspace: initialized git repository with initial commit');
    } catch (gitErr) {
      logger.warn(`syncWorkspace: git init failed: ${gitErr.message}`);
    }
  }

  const elapsed = ((Date.now() - startTime) / 1000).toFixed(1);
  logger.info(`syncWorkspace: done — ${ctx.fileCount} files (${ctx.skipped} unchanged) in ${elapsed}s`);

  return { files: ctx.fileCount, skipped: ctx.skipped, remotePath, elapsed };
}

module.exports = { syncWorkspace };
