/**
 * agentOrchestrator.js — HIVEMIND: Multi-Agent Delegation System
 *
 * Allows Alfred to spawn lightweight sub-agents that run in parallel,
 * each handling a portion of a complex task. Sub-agents are Claude API
 * calls with restricted tool access.
 *
 * Sub-agent types:
 *   - Researcher: read-only tools (file read, search, fetch_url)
 *   - Analyzer:  read-only + diagnostics
 *   - Worker:    full tools (only one at a time)
 *
 * Limits:
 *   - Max 3 concurrent sub-agents per user
 *   - Max 100K tokens per sub-agent
 *   - Workers are serialized (only one at a time)
 *
 * The orchestrator tracks running sub-agents in memory and returns
 * merged results when collect_results is called.
 */

const ANTHROPIC_API_URL = 'https://api.anthropic.com/v1/messages';
const MAX_CONCURRENT = 3;
const MAX_TOKENS_PER_AGENT = 4096;
const SUB_AGENT_MODEL = 'claude-sonnet-4-20250514'; // Sonnet for sub-agents (cheaper)

// ── In-memory task tracking ─────────────────────────────────────────────────
// Map<taskId, { status, result, error, startTime, type, daUsername }>
const tasks = new Map();
// Map<daUsername, Set<taskId>> — active tasks per user
const userTasks = new Map();

// ── Tool sets by role ───────────────────────────────────────────────────────
const ROLE_TOOLS = {
  researcher: [
    'read_file', 'list_directory', 'search_files', 'semantic_code_search',
    'fetch_url', 'alfred_recall', 'stat_file',
  ],
  analyzer: [
    'read_file', 'list_directory', 'search_files', 'semantic_code_search',
    'fetch_url', 'alfred_recall', 'stat_file', 'run_terminal_command',
    'get_diagnostics', 'site_health_check',
  ],
  worker: [
    'read_file', 'write_file', 'list_directory', 'search_files',
    'run_terminal_command', 'fetch_url', 'semantic_code_search',
  ],
};

/**
 * Spawn a sub-agent to handle a task.
 *
 * @param {string} daUsername
 * @param {object} opts
 * @param {string} opts.role — 'researcher', 'analyzer', or 'worker'
 * @param {string} opts.task — natural language description of what to do
 * @param {string[]} [opts.tools] — override tool list (optional)
 * @param {string} [opts.context] — additional context to provide
 * @returns {Promise<{task_id: string, message: string}>}
 */
export async function spawnSubAgent(daUsername, opts) {
  const apiKey = process.env.ANTHROPIC_API_KEY;
  if (!apiKey) throw new Error('ANTHROPIC_API_KEY not set');

  // Check concurrency limit
  const userActive = userTasks.get(daUsername) || new Set();
  const activeCount = [...userActive].filter(id => {
    const t = tasks.get(id);
    return t && t.status === 'running';
  }).length;

  if (activeCount >= MAX_CONCURRENT) {
    throw new Error(`Maximum ${MAX_CONCURRENT} concurrent sub-agents. Wait for existing ones to complete or use collect_results.`);
  }

  // Workers are exclusive — only one at a time
  if (opts.role === 'worker') {
    const hasWorker = [...userActive].some(id => {
      const t = tasks.get(id);
      return t && t.status === 'running' && t.role === 'worker';
    });
    if (hasWorker) {
      throw new Error('Only one Worker sub-agent can run at a time (prevents edit conflicts). Wait for the current worker to finish.');
    }
  }

  const taskId = `agent_${Date.now().toString(36)}_${Math.random().toString(36).slice(2, 6)}`;
  const role = opts.role || 'researcher';
  const allowedTools = opts.tools || ROLE_TOOLS[role] || ROLE_TOOLS.researcher;

  // Create task entry
  const taskEntry = {
    id: taskId,
    status: 'running',
    role,
    task: opts.task,
    tools: allowedTools,
    result: null,
    error: null,
    startTime: Date.now(),
    endTime: null,
  };
  tasks.set(taskId, taskEntry);

  // Track per user
  if (!userTasks.has(daUsername)) userTasks.set(daUsername, new Set());
  userTasks.get(daUsername).add(taskId);

  // Execute asynchronously (don't await — it runs in background)
  executeSubAgent(apiKey, daUsername, taskId, opts.task, opts.context || '', role)
    .catch(err => {
      const t = tasks.get(taskId);
      if (t) {
        t.status = 'error';
        t.error = err.message;
        t.endTime = Date.now();
      }
    });

  return {
    task_id: taskId,
    message: `Sub-agent spawned (${role}): "${opts.task.slice(0, 100)}". Use collect_results(["${taskId}"]) to get the result when done.`,
    active_agents: activeCount + 1,
  };
}

/**
 * Collect results from one or more sub-agents.
 *
 * @param {string} daUsername
 * @param {string[]} taskIds — task IDs to collect (or ["all"] for all)
 * @returns {Promise<{results: Array, pending: number, completed: number}>}
 */
export async function collectResults(daUsername, taskIds) {
  const userActive = userTasks.get(daUsername) || new Set();

  let idsToCollect;
  if (taskIds.length === 1 && taskIds[0] === 'all') {
    idsToCollect = [...userActive];
  } else {
    idsToCollect = taskIds;
  }

  const results = [];
  let pending = 0;
  let completed = 0;

  for (const id of idsToCollect) {
    const t = tasks.get(id);
    if (!t) {
      results.push({ task_id: id, status: 'not_found', result: null });
      continue;
    }

    if (t.status === 'running') {
      pending++;
      results.push({
        task_id: id,
        status: 'running',
        role: t.role,
        task: t.task.slice(0, 100),
        elapsed_ms: Date.now() - t.startTime,
      });
    } else {
      completed++;
      results.push({
        task_id: id,
        status: t.status,
        role: t.role,
        task: t.task.slice(0, 100),
        result: t.result,
        error: t.error,
        elapsed_ms: (t.endTime || Date.now()) - t.startTime,
      });

      // Clean up completed tasks
      tasks.delete(id);
      userActive.delete(id);
    }
  }

  return { results, pending, completed };
}

// ═══════════════════════════════════════════════════════════════════
// INTERNAL: Execute a sub-agent via Claude API
// ═══════════════════════════════════════════════════════════════════

/**
 * Execute a sub-agent by calling the Claude API directly.
 * This is a simplified execution — the sub-agent gets one shot to answer.
 * It doesn't have access to MCP tools (just its knowledge + any context provided).
 */
async function executeSubAgent(apiKey, daUsername, taskId, task, context, role) {
  const systemPrompt = buildSubAgentPrompt(role, daUsername);

  const userMessage = context
    ? `## Context\n${context}\n\n## Task\n${task}\n\nProvide a thorough, detailed response.`
    : `## Task\n${task}\n\nProvide a thorough, detailed response.`;

  try {
    const res = await fetch(ANTHROPIC_API_URL, {
      method: 'POST',
      headers: {
        'x-api-key': apiKey,
        'anthropic-version': '2023-06-01',
        'content-type': 'application/json',
      },
      body: JSON.stringify({
        model: SUB_AGENT_MODEL,
        max_tokens: MAX_TOKENS_PER_AGENT,
        system: systemPrompt,
        messages: [{ role: 'user', content: userMessage }],
      }),
    });

    if (!res.ok) {
      const errText = await res.text();
      throw new Error(`Claude API error (${res.status}): ${errText.slice(0, 500)}`);
    }

    const data = await res.json();
    const text = data.content
      ?.filter(c => c.type === 'text')
      .map(c => c.text)
      .join('\n') || 'No response';

    const t = tasks.get(taskId);
    if (t) {
      t.status = 'completed';
      t.result = text;
      t.endTime = Date.now();
      t.usage = {
        input_tokens: data.usage?.input_tokens || 0,
        output_tokens: data.usage?.output_tokens || 0,
      };
    }
  } catch (err) {
    const t = tasks.get(taskId);
    if (t) {
      t.status = 'error';
      t.error = err.message;
      t.endTime = Date.now();
    }
    throw err;
  }
}

/**
 * Build the system prompt for a sub-agent.
 */
function buildSubAgentPrompt(role, daUsername) {
  const base = `You are a sub-agent of Alfred, the AI assistant for GoCodeMe IDE. You are a ${role} agent working for user "${daUsername}".

Your job is to complete the specific task assigned to you thoroughly and concisely. Return only the information requested — no preamble or conversation.`;

  const roleInstructions = {
    researcher: `
You are a Researcher sub-agent. Your job is to find and gather information.
- Search through code, documentation, and files to find relevant information
- Summarize your findings clearly with file paths and line numbers
- Do NOT modify any files — you are read-only
- Be thorough — check multiple sources before concluding`,

    analyzer: `
You are an Analyzer sub-agent. Your job is to analyze code and diagnose issues.
- Examine code structure, find bugs, security issues, and performance problems
- Provide file paths, line numbers, and specific details
- Suggest fixes but do NOT implement them
- Check error logs and diagnostics for clues`,

    worker: `
You are a Worker sub-agent. Your job is to plan and implement changes.
- Describe exactly what changes need to be made (file, line, content)
- Provide the complete code for any modifications
- Consider edge cases and error handling
- Ensure changes are safe and reversible`,
  };

  return base + (roleInstructions[role] || roleInstructions.researcher);
}

/**
 * Get a summary of all active sub-agents for a user.
 */
export function getAgentStatus(daUsername) {
  const userActive = userTasks.get(daUsername) || new Set();
  const agents = [];

  for (const id of userActive) {
    const t = tasks.get(id);
    if (t) {
      agents.push({
        task_id: id,
        status: t.status,
        role: t.role,
        task: t.task.slice(0, 80),
        elapsed_ms: (t.endTime || Date.now()) - t.startTime,
      });
    }
  }

  return {
    total: agents.length,
    running: agents.filter(a => a.status === 'running').length,
    completed: agents.filter(a => a.status === 'completed').length,
    errored: agents.filter(a => a.status === 'error').length,
    agents,
  };
}
