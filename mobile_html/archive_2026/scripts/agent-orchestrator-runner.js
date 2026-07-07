/**
 * Agent Orchestrator Runner — PM2 Persistent Service
 * ═══════════════════════════════════════════════════
 * This runs 24/7 via PM2 and solves the persistence problem:
 * - Watches the agent-queue directory for task files
 * - Spawns Claude Code CLI sessions for each task
 * - Reports results back to the API
 * - Retries failed tasks
 * - Rate-limits spawning to avoid overload
 *
 * PM2: pm2 start scripts/agent-orchestrator-runner.js --name agent-orchestrator
 */

const fs = require('fs');
const path = require('path');
const { execSync, spawn } = require('child_process');
const https = require('https');
const http = require('http');

// ── Config ────────────────────────────────────────────────────
const ROOT = path.resolve(__dirname, '..');
const QUEUE_DIR = path.join(ROOT, 'data', 'agent-queue');
const LOG_DIR = path.join(ROOT, 'logs');
const RESULTS_DIR = path.join(ROOT, 'data', 'agent-results');
const API_URL = 'http://127.0.0.1/api/agent-orchestrator.php';
const ORCHESTRATOR_SECRET = process.env.ORCHESTRATOR_SECRET || '';

// How many agents can run in parallel
const MAX_CONCURRENT = parseInt(process.env.AGENT_CONCURRENCY || '3', 10);
// Delay between checking queue (ms)
const POLL_INTERVAL = parseInt(process.env.AGENT_POLL_INTERVAL || '10000', 10);
// Max execution time per task (ms) — 30 minutes default
const TASK_TIMEOUT = parseInt(process.env.AGENT_TASK_TIMEOUT || '1800000', 10);

// ── State ─────────────────────────────────────────────────────
const running = new Map(); // taskId -> { process, startedAt }

// ── Ensure Directories Exist ──────────────────────────────────
[QUEUE_DIR, LOG_DIR, RESULTS_DIR].forEach(dir => {
  if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
});

// ── Logging ───────────────────────────────────────────────────
const logFile = path.join(LOG_DIR, 'agent-orchestrator.log');

function log(level, msg, meta = {}) {
  const entry = {
    timestamp: new Date().toISOString(),
    level,
    message: msg,
    ...meta
  };
  const line = JSON.stringify(entry);
  console.log(line);
  fs.appendFileSync(logFile, line + '\n');
}

// ── API Calls (with retry) ─────────────────────────────────
function apiCall(action, taskId = '', method = 'GET', body = null, retries = 3) {
  return new Promise((resolve, reject) => {
    let url = `${API_URL}?action=${action}`;
    if (taskId) url += `&id=${taskId}`;

    const urlObj = new URL(url);
    const options = {
      hostname: urlObj.hostname,
      port: urlObj.port || 80,
      path: urlObj.pathname + urlObj.search,
      method,
      headers: {
        'Content-Type': 'application/json',
        'X-Orchestrator-Secret': ORCHESTRATOR_SECRET
      }
    };

    const req = http.request(options, (res) => {
      let data = '';
      res.on('data', chunk => data += chunk);
      res.on('end', () => {
        try { resolve(JSON.parse(data)); }
        catch(e) { resolve({ error: true, message: 'Invalid JSON response' }); }
      });
    });

    req.on('error', async (e) => {
      if (retries > 1) {
        log('warn', `API call to ${action} failed, retrying (${retries - 1} left)`, { error: e.message });
        await new Promise(r => setTimeout(r, 2000));
        resolve(apiCall(action, taskId, method, body, retries - 1));
      } else {
        reject(e);
      }
    });
    if (body) req.write(JSON.stringify(body));
    req.end();
  });
}

// ── Generate Agent Prompt ─────────────────────────────────────
function generatePrompt(task) {
  const agentTypes = {
    security: 'Security Auditor',
    frontend: 'Frontend Builder',
    api: 'API Engineer',
    javascript: 'JavaScript Engine',
    test: 'Test Writer',
    script: 'DevOps / Infrastructure',
    docs: 'Documentation Writer',
    sdk: 'SDK Developer',
    debt: 'Performance Optimizer',
    feature: 'Frontend Builder'
  };
  const agentType = agentTypes[task.category] || 'Frontend Builder';

  return `You are an autonomous AI coding agent working on the GoSiteMe platform.
Workspace: /home/gositeme/domains/gositeme.com/public_html/

BEFORE DOING ANYTHING:
1. Read .github/copilot-instructions.md (codebase conventions)
2. Read AGENTS.md (you are the "${agentType}" agent)

YOUR TASK: ${task.task_id} — ${task.title}
${task.description ? 'DETAILS: ' + task.description : ''}
${task.target_file ? 'TARGET FILE: ' + task.target_file : ''}

RULES:
- Read the target file FULLY before making any edits
- Follow existing code patterns and naming conventions
- Dark theme with --alfred-* CSS variables
- Mobile-first responsive design
- PDO prepared statements for all DB queries
- Escape all user output with htmlspecialchars()
- CSRF tokens on all forms

WHEN DONE:
1. Run: php -l [file.php] (must pass with no errors)
2. Run: node -c [file.js] (must pass with no errors)
3. Run: curl -sI https://gositeme.com/[page] (must return HTTP 200)
4. Report what files you changed and a brief summary

IMPORTANT: Do NOT create unnecessary files. Keep changes focused and minimal.`;
}

// ── Execute Task ──────────────────────────────────────────────
async function executeTask(task) {
  log('info', `Starting task ${task.task_id}: ${task.title}`, { taskId: task.task_id });

  const prompt = generatePrompt(task);
  const resultFile = path.join(RESULTS_DIR, `${task.task_id}.log`);

  // Check if claude CLI is available
  let claudePath;
  try {
    claudePath = execSync('which claude 2>/dev/null', { encoding: 'utf8' }).trim();
  } catch(e) {
    claudePath = null;
  }

  if (!claudePath) {
    log('warn', `Claude CLI not found, task ${task.task_id} will be queued for manual processing`, { taskId: task.task_id });

    // Write prompt file for manual processing
    const promptFile = path.join(QUEUE_DIR, `${task.task_id}.prompt`);
    fs.writeFileSync(promptFile, prompt);

    // Log to API
    await apiCall('log', '', 'POST', {
      task_id: task.task_id,
      level: 'warn',
      agent: 'Runner',
      message: 'Claude CLI not found — task prompt saved for manual execution'
    });

    return { success: false, error: 'Claude CLI not available — prompt saved' };
  }

  return new Promise((resolve) => {
    const startTime = Date.now();
    let output = '';

    const proc = spawn(claudePath, ['-p', prompt, '--output-format', 'text'], {
      cwd: ROOT,
      timeout: TASK_TIMEOUT,
      env: { ...process.env, HOME: process.env.HOME || '/home/gositeme' },
      stdio: ['pipe', 'pipe', 'pipe']
    });

    running.set(task.task_id, { process: proc, startedAt: startTime });

    proc.stdout.on('data', (data) => {
      output += data.toString();
      fs.appendFileSync(resultFile, data);
    });

    proc.stderr.on('data', (data) => {
      output += data.toString();
      fs.appendFileSync(resultFile, data);
    });

    proc.on('close', async (code) => {
      running.delete(task.task_id);
      const duration = Math.round((Date.now() - startTime) / 1000);

      if (code === 0) {
        log('info', `Task ${task.task_id} completed in ${duration}s`, { taskId: task.task_id, duration });

        // Extract summary (last 500 chars)
        const summary = output.slice(-500).trim();

        // Report completion
        try {
          await apiCall('complete', task.task_id, 'POST', {
            summary: summary.substring(0, 2000),
            validation_log: `Exit code: ${code}, Duration: ${duration}s`
          });
          await apiCall('log', '', 'POST', {
            task_id: task.task_id,
            level: 'success',
            agent: 'Runner',
            message: `Completed in ${duration}s`
          });
        } catch(e) {
          log('error', `Failed to report completion for ${task.task_id}`, { error: e.message });
        }

        resolve({ success: true, duration });
      } else {
        log('error', `Task ${task.task_id} failed with code ${code} after ${duration}s`, { taskId: task.task_id, code, duration });

        // Report failure
        try {
          await apiCall('fail', task.task_id, 'POST', {
            error: `Process exited with code ${code}. Last output: ${output.slice(-300).trim()}`
          });
          await apiCall('log', '', 'POST', {
            task_id: task.task_id,
            level: 'error',
            agent: 'Runner',
            message: `Failed with exit code ${code} after ${duration}s`
          });
        } catch(e) {
          log('error', `Failed to report failure for ${task.task_id}`, { error: e.message });
        }

        resolve({ success: false, code, duration });
      }
    });

    proc.on('error', async (err) => {
      running.delete(task.task_id);
      log('error', `Process error for ${task.task_id}: ${err.message}`, { taskId: task.task_id });

      try {
        await apiCall('fail', task.task_id, 'POST', { error: err.message });
      } catch(e) {}

      resolve({ success: false, error: err.message });
    });
  });
}

// ── Process Queue ─────────────────────────────────────────────
async function processQueue() {
  if (running.size >= MAX_CONCURRENT) {
    log('info', `At capacity: ${running.size}/${MAX_CONCURRENT} agents running`);
    return;
  }

  // Read queue directory for .json files
  let files;
  try {
    files = fs.readdirSync(QUEUE_DIR).filter(f => f.endsWith('.json'));
  } catch(e) {
    return;
  }

  if (files.length === 0) return;

  // Sort by priority (P0 first)
  const validCategories = ['security','frontend','api','javascript','test','script','docs','sdk','debt','feature'];
  const tasks = files.map(f => {
    try {
      const data = JSON.parse(fs.readFileSync(path.join(QUEUE_DIR, f), 'utf8'));
      // Validate required fields and format
      if (!data.task_id || typeof data.task_id !== 'string' || !data.title || typeof data.title !== 'string') {
        log('warn', `Invalid queue file ${f}: missing task_id or title`);
        return null;
      }
      if (!/^[A-Z]+-\d+$/.test(data.task_id)) {
        log('warn', `Invalid queue file ${f}: malformed task_id ${data.task_id}`);
        return null;
      }
      if (data.category && !validCategories.includes(data.category)) {
        log('warn', `Invalid category in queue file ${f}, defaulting to frontend`);
        data.category = 'frontend';
      }
      return data;
    } catch(e) {
      return null;
    }
  }).filter(Boolean).sort((a, b) => {
    const prio = { P0: 0, P1: 1, P2: 2, P3: 3, P4: 4 };
    return (prio[a.priority] || 2) - (prio[b.priority] || 2);
  });

  // Process tasks up to concurrency limit
  const slotsAvailable = MAX_CONCURRENT - running.size;
  const toProcess = tasks.filter(t => !running.has(t.task_id)).slice(0, slotsAvailable);

  for (const task of toProcess) {
    const queueFile = path.join(QUEUE_DIR, `${task.task_id}.json`);

    // Execute asynchronously, delete queue file AFTER completion
    executeTask(task).then(result => {
      // Remove queue file after processing (not before)
      try { fs.unlinkSync(queueFile); } catch(e) {}
      log('info', `Task ${task.task_id} result: ${result.success ? 'SUCCESS' : 'FAILED'}`, { taskId: task.task_id, result });
    });
  }
}

// ── Health Check ──────────────────────────────────────────────
function healthCheck() {
  const status = {
    service: 'agent-orchestrator-runner',
    uptime: process.uptime(),
    running: running.size,
    maxConcurrent: MAX_CONCURRENT,
    queueDir: QUEUE_DIR,
    tasks: {}
  };

  running.forEach((val, key) => {
    status.tasks[key] = {
      startedAt: new Date(val.startedAt).toISOString(),
      runningFor: Math.round((Date.now() - val.startedAt) / 1000) + 's'
    };
  });

  return status;
}

// ── Stuck Task Cleanup ────────────────────────────────────────
function cleanupStuckTasks() {
  running.forEach((val, taskId) => {
    const elapsed = Date.now() - val.startedAt;
    if (elapsed > TASK_TIMEOUT) {
      log('warn', `Killing stuck task ${taskId} (running for ${Math.round(elapsed/1000)}s)`, { taskId });
      try { val.process.kill('SIGTERM'); } catch(e) {}
      running.delete(taskId);
    }
  });
}
// ── Orphan Recovery (re-queue running tasks with no local process) ──
async function recoverOrphans() {
  try {
    const result = await apiCall('backlog', '', 'GET');
    if (!result.success) return;
    const orphans = (result.tasks || []).filter(t =>
      t.status === 'running' && !running.has(t.task_id)
    );
    for (const t of orphans) {
      const claimedMinutesAgo = t.started_at ?
        (Date.now() - new Date(t.started_at).getTime()) / 60000 : 999;
      // Only recover tasks that have been running for more than 35 minutes (past timeout)
      if (claimedMinutesAgo > (TASK_TIMEOUT / 60000) + 5) {
        log('warn', `Recovering orphan task ${t.task_id} (running for ${Math.round(claimedMinutesAgo)}min with no local process)`, { taskId: t.task_id });
        await apiCall('fail', t.task_id, 'POST', { error: 'Orphan recovery: task was running with no local process' });
      }
    }
  } catch(e) {
    log('error', 'Orphan recovery failed', { error: e.message });
  }
}
// ── Main Loop ─────────────────────────────────────────────────
log('info', '═══════════════════════════════════════════════════');
log('info', 'Agent Orchestrator Runner started', {
  maxConcurrent: MAX_CONCURRENT,
  pollInterval: POLL_INTERVAL,
  taskTimeout: TASK_TIMEOUT,
  queueDir: QUEUE_DIR
});
log('info', '═══════════════════════════════════════════════════');

// Process queue every POLL_INTERVAL
setInterval(processQueue, POLL_INTERVAL);

// Check for stuck tasks every 5 minutes
setInterval(cleanupStuckTasks, 5 * 60 * 1000);

// Recover orphaned tasks every 10 minutes
setInterval(recoverOrphans, 10 * 60 * 1000);
setTimeout(recoverOrphans, 30000); // First check 30s after startup

// Initial queue check
setTimeout(processQueue, 2000);

// Log health every 10 minutes
setInterval(() => {
  const health = healthCheck();
  log('info', 'Health check', health);
}, 10 * 60 * 1000);

// Graceful shutdown
process.on('SIGINT', () => {
  log('info', 'Shutting down — killing running tasks');
  running.forEach((val, key) => {
    try { val.process.kill('SIGTERM'); } catch(e) {}
  });
  setTimeout(() => process.exit(0), 3000);
});

process.on('SIGTERM', () => {
  log('info', 'SIGTERM received — graceful shutdown');
  running.forEach((val, key) => {
    try { val.process.kill('SIGTERM'); } catch(e) {}
  });
  setTimeout(() => process.exit(0), 3000);
});
