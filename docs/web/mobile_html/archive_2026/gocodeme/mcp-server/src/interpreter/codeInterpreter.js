/**
 * codeInterpreter.js — Multi-Language Code Execution Engine
 *
 * Executes code in isolated sessions with output capture.
 * Supported languages: Python, Node.js, Bash, Ruby, PHP
 *
 * Features:
 *   - Per-user session isolation (temp directories)
 *   - Stdout/stderr capture with size limits
 *   - Image/plot detection (matplotlib, etc.)
 *   - Execution timeout (30s default)
 *   - Session state persistence across calls
 */

import { spawn } from 'node:child_process';
import { writeFile } from 'node:fs/promises';
import path from 'node:path';
import { getOrCreateSession, getSession, destroySession, listSessions, listAllSessions } from './sessionPool.js';
import { captureImages, formatResult } from './outputCapture.js';

const EXEC_TIMEOUT = 30_000;  // 30 seconds
const MAX_OUTPUT = 100_000;   // 100KB per stream

/**
 * Language runtime configurations.
 */
const RUNTIMES = {
  python: {
    command: 'python3',
    ext: '.py',
    setup: `import sys, os\nos.chdir("{workDir}")\n`,
  },
  node: {
    command: 'node',
    ext: '.js',
    setup: `process.chdir("{workDir}");\n`,
  },
  bash: {
    command: 'bash',
    ext: '.sh',
    setup: `cd "{workDir}"\nset -e\n`,
  },
  ruby: {
    command: 'ruby',
    ext: '.rb',
    setup: `Dir.chdir("{workDir}")\n`,
  },
  php: {
    command: 'php',
    ext: '.php',
    setup: `<?php chdir("{workDir}");\n`,
  },
};

/**
 * Run code in a child process with timeout.
 */
function execCode(command, args, cwd, timeout = EXEC_TIMEOUT) {
  return new Promise((resolve) => {
    let stdout = '';
    let stderr = '';
    let killed = false;

    const proc = spawn(command, args, {
      cwd,
      timeout,
      env: {
        ...process.env,
        PYTHONDONTWRITEBYTECODE: '1',
        MPLBACKEND: 'Agg',  // matplotlib non-interactive
      },
      stdio: ['pipe', 'pipe', 'pipe'],
    });

    proc.stdout.on('data', (data) => {
      if (stdout.length < MAX_OUTPUT) stdout += data.toString();
    });

    proc.stderr.on('data', (data) => {
      if (stderr.length < MAX_OUTPUT) stderr += data.toString();
    });

    const timer = setTimeout(() => {
      killed = true;
      proc.kill('SIGKILL');
    }, timeout);

    proc.on('close', (code) => {
      clearTimeout(timer);
      resolve({
        stdout,
        stderr: killed ? stderr + '\n[Execution timed out after ' + (timeout / 1000) + 's]' : stderr,
        exitCode: killed ? 124 : (code || 0),
      });
    });

    proc.on('error', (err) => {
      clearTimeout(timer);
      resolve({
        stdout,
        stderr: err.message,
        exitCode: 1,
      });
    });
  });
}

/**
 * Execute code in a session.
 *
 * @param {object} opts
 * @param {string} opts.code — source code to execute
 * @param {string} [opts.language='python'] — 'python', 'node', 'bash', 'ruby', 'php'
 * @param {string} opts.daUsername — user identifier
 * @param {string} [opts.sessionId] — reuse a specific session (optional)
 * @param {number} [opts.timeout=30000] — execution timeout in ms
 * @returns {Promise<object>}
 */
export async function runCode(opts) {
  const {
    code,
    language = 'python',
    daUsername,
    sessionId,
    timeout = EXEC_TIMEOUT,
  } = opts;

  if (!code || !code.trim()) throw new Error('code is required');

  const lang = language.toLowerCase();
  const runtime = RUNTIMES[lang];
  if (!runtime) {
    throw new Error(`Unsupported language: ${lang}. Supported: ${Object.keys(RUNTIMES).join(', ')}`);
  }

  const start = Date.now();

  // Get or create session
  let session;
  if (sessionId) {
    session = getSession(sessionId);
    if (!session) throw new Error(`Session ${sessionId} not found or expired`);
  } else {
    session = await getOrCreateSession(daUsername, lang);
  }

  // Write code to temp file
  const fileName = `exec_${session.executionCount}${runtime.ext}`;
  const filePath = path.join(session.workDir, fileName);

  // Prepend setup code (working directory, imports)
  const setup = runtime.setup.replace(/\{workDir\}/g, session.workDir);
  const fullCode = setup + code;
  await writeFile(filePath, fullCode, 'utf-8');

  // Track new images
  const beforeExec = Date.now();

  // Execute
  const result = await execCode(runtime.command, [filePath], session.workDir, timeout);

  // Capture any generated images
  const images = await captureImages(session.workDir, beforeExec);

  // Update session
  session.executionCount++;
  session.lastUsed = Date.now();

  return {
    ...formatResult({
      ...result,
      timing: Date.now() - start,
      images,
      language: lang,
    }),
    sessionId: session.id,
    executionNumber: session.executionCount,
  };
}

/**
 * List interpreter sessions for a user.
 */
export function listInterpreterSessions(daUsername) {
  if (daUsername) return listSessions(daUsername);
  return listAllSessions();
}

/**
 * Kill an interpreter session.
 */
export async function killInterpreterSession(sessionId) {
  const killed = await destroySession(sessionId);
  return { sessionId, killed };
}
