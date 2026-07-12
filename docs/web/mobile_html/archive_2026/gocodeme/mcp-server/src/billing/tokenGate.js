/**
 * billing/tokenGate.js — WHMCS Token Metering Gateway for MCP Server
 *
 * Bridges the MCP server to the middleware's existing token tracking system.
 * Before each tool execution, checks the user's token allowance via Redis.
 * After execution, reports tool-level usage for the billing dashboard.
 *
 * Architecture:
 *   - The middleware's anthropicProxy already tracks Claude API token usage
 *   - This module adds MCP tool-call metering on top (separate counter)
 *   - Both feed into the same WHMCS billing cycle
 *
 * Redis keys (shared with middleware):
 *   tokens:used:<clientId>          → total tokens used this period
 *   tokens:limit:<clientId>         → monthly limit
 *   mcp:tool_calls:<clientId>:YYYY-MM-DD → daily MCP tool call count
 *   mcp:tool_usage:<clientId>       → hash of tool_name → call_count
 *   mcp:blocked:<clientId>          → "1" if user is blocked (over limit + unpaid)
 *
 * Token costs per tool call (estimated based on complexity):
 *   - Read operations: 50 tokens
 *   - Write/modify operations: 100 tokens
 *   - Complex operations (image gen, code review): 500 tokens
 *   - AI-powered operations (smart commit, semantic search): 200 tokens
 */

import Redis from 'ioredis';

let redis;
try {
  redis = new Redis({ lazyConnect: true, maxRetriesPerRequest: 1 });
  redis.connect().catch(() => { redis = null; });
} catch { redis = null; }

const TTL_35_DAYS = 35 * 24 * 60 * 60;

// ── Tool token costs (estimated per-call consumption) ────────────────────────
const TOOL_COSTS = {
  // Read-only operations — lightweight
  read_file: 50, list_directory: 50, search_files: 50, find_file: 50, get_file_info: 50,
  list_databases: 50, list_domains: 50, list_subdomains: 50,
  list_email_accounts: 50, list_dns_records: 50, get_ssl_status: 50,
  list_cron_jobs: 50, list_backups: 50, get_account_usage: 50,
  get_account_limits: 50, get_account_summary: 50, get_error_logs: 50,
  get_traffic_stats: 50, list_git_branches: 50, get_git_status: 50,
  get_git_log: 50, get_git_diff: 50, get_watcher_status: 50,
  get_index_stats: 50, get_tool_analytics: 50, get_database_stats: 50,
  get_database_schema: 50, get_my_profile: 50, get_my_services: 50,
  get_product_catalog: 50,

  // Write/modify — moderate
  write_file: 100, delete_file: 100, rename_file: 100, create_directory: 100,
  create_database: 100, delete_database: 100, create_subdomain: 100,
  delete_subdomain: 100, create_email_account: 100, delete_email_account: 100,
  create_email_forwarder: 100, create_autoresponder: 100, add_dns_record: 100,
  delete_dns_record: 100, request_ssl_certificate: 100, force_https: 100,
  create_cron_job: 100, delete_cron_job: 100, create_backup: 100,
  restore_backup: 100, git_init: 100, git_commit: 100, git_clone: 100,
  send_email: 100, create_checkpoint: 100, restore_checkpoint: 100,
  list_checkpoints: 50, run_terminal_command: 100,

  // WordPress — moderate to complex
  install_wordpress: 200, manage_wordpress: 100, install_plugin: 100,
  install_theme: 100, search_plugins: 50, manage_cache: 100,
  wordpress_seo: 100, security_scan: 150, check_site_health: 100,
  wordpress_staging: 200, manage_htaccess: 100,

  // AI-powered — higher cost
  generate_image: 5000, semantic_code_search: 200, smart_commit: 200,
  amend_commit: 200, code_review: 500, dependency_audit: 200,
  project_snapshot: 150, alfred_remember: 100, alfred_recall: 150,
  alfred_forget: 50, memory_summary: 100, save_session_summary: 200,

  // Media generation — EXPENSIVE external API calls
  generate_video: 25000,   // video APIs cost $0.10-0.50+ per generation
  generate_audio: 2000,    // TTS is cheaper but still real API cost
  vision_analyze: 1000,    // vision model inference
  list_ai_models: 0,       // free — just listing metadata
  list_generated_images: 0, // free — just listing files

  // Workflow/automation — complex
  run_playbook: 300, list_playbooks: 50, save_playbook: 100,
  schedule_task: 100, list_scheduled_tasks: 50, delete_scheduled_task: 50,
  task_logs: 50, index_workspace: 300, spawn_sub_agent: 400,
  collect_agent_results: 100, start_file_watcher: 50, stop_file_watcher: 50,

  // Document generation
  generate_word_doc: 200, generate_pdf: 200, read_pdf: 150,
  fetch_url: 100,

  // WHMCS commerce — always allowed (billing-related)
  get_my_invoices: 0, get_my_tickets: 0, get_my_quotes: 0,
  get_domain_pricing: 0, check_domain: 0, register_domain: 0,
  create_support_ticket: 0, get_invoice_details: 0, get_service_details: 0,
  get_credit_balance: 0, client_sso_login: 0,

  // Database query
  execute_query: 100, backup_database: 200,

  // v2 engines — already estimated above via specific tools

  // ── v8.0.0 Agent Commerce ─────────────────────────────────────────
  commerce_connect_store: 100,     commerce_list_stores: 50,
  commerce_disconnect_store: 100,  commerce_product_truth: 100,
  commerce_order_truth: 100,       commerce_availability_truth: 100,
  commerce_shipping_truth: 100,    commerce_policy_truth: 50,
  commerce_search_products: 100,   commerce_order_status: 100,
  commerce_list_orders: 100,       commerce_set_policy: 100,
  commerce_list_policies: 50,      commerce_remove_policy: 100,
  commerce_evaluate_policy: 100,   commerce_process_refund: 200,
  commerce_cancel_order: 200,      commerce_create_return: 200,
  commerce_escalate: 100,          commerce_audit_log: 50,
  commerce_analytics: 100,         commerce_list_workflows: 50,
  commerce_execute_workflow: 300,

  // ── v8.0.0 Omnichannel Messaging ──────────────────────────────────
  messaging_configure_channel: 100, messaging_list_channels: 50,
  messaging_send_sms: 200,         messaging_send_email: 200,
  messaging_send_template: 200,    messaging_create_template: 100,
  messaging_list_templates: 50,    messaging_create_campaign: 100,
  messaging_execute_campaign: 500, messaging_list_campaigns: 50,
  messaging_add_contact: 100,      messaging_list_contacts: 50,
  messaging_search_contacts: 50,   messaging_history: 50,
  messaging_analytics: 100,

  // ── v8.0.0 Call Analytics ─────────────────────────────────────────
  call_log: 100,                   call_get: 50,
  call_search: 100,                call_analytics: 100,
  call_performance: 100,           call_leads: 100,
  call_ask: 200,
};

const DEFAULT_COST = 75; // for any tool not in the map

/**
 * Check if a user is allowed to execute a tool (has tokens remaining).
 *
 * @param {string|number} whmcsClientId — null means no billing (admin/dev)
 * @param {string} toolName
 * @returns {Promise<{allowed: boolean, used?: number, limit?: number, remaining?: number, cost?: number, reason?: string}>}
 */
export async function checkAllowance(whmcsClientId, toolName) {
  // No billing — always allow (dev mode, admin, no WHMCS session)
  if (!whmcsClientId || !redis) {
    return { allowed: true, cost: 0 };
  }

  try {
    // Check if explicitly blocked
    const blocked = await redis.get(`mcp:blocked:${whmcsClientId}`);
    if (blocked) {
      return {
        allowed: false,
        reason: 'Your account has been suspended due to unpaid token overage. Please settle your invoice to continue.',
      };
    }

    // WHMCS commerce tools are always free
    const cost = TOOL_COSTS[toolName] ?? DEFAULT_COST;
    if (cost === 0) {
      return { allowed: true, cost: 0 };
    }

    // Check token balance
    const used = parseInt((await redis.get(`tokens:used:${whmcsClientId}`)) || '0', 10);
    const limit = parseInt((await redis.get(`tokens:limit:${whmcsClientId}`)) || '0', 10);

    // Limit of 0 means unlimited (admin)
    if (limit === 0) {
      return { allowed: true, used, limit, remaining: Infinity, cost };
    }

    const remaining = limit - used;
    // Allow up to 10% overage (billing alerts handle the invoice)
    const softLimit = Math.floor(limit * 1.1);
    const allowed = used < softLimit;

    return {
      allowed,
      used,
      limit,
      remaining: Math.max(0, remaining),
      cost,
      reason: allowed ? undefined : `Token limit reached (${used.toLocaleString()} / ${limit.toLocaleString()}). Please upgrade your plan.`,
    };
  } catch (err) {
    // Redis failure — allow the call (fail open for availability)
    console.error(`[TOKEN-GATE] Redis check failed: ${err.message} — allowing call`);
    return { allowed: true, cost: 0 };
  }
}

/**
 * Record a tool call for billing/analytics.
 *
 * @param {string|number} whmcsClientId
 * @param {string} toolName
 * @param {number} [executionMs] — how long the call took
 * @returns {Promise<void>}
 */
export async function recordToolCall(whmcsClientId, toolName, executionMs = 0) {
  if (!whmcsClientId || !redis) return;

  const cost = TOOL_COSTS[toolName] ?? DEFAULT_COST;
  if (cost === 0) return; // free tools don't count

  const today = new Date().toISOString().slice(0, 10);
  const pipeline = redis.pipeline();

  // Track MCP tool costs separately (do NOT inflate the real API token counter)
  pipeline.incrby(`mcp:tokens:used:${whmcsClientId}`, cost);

  // Daily MCP call counter
  const dailyKey = `mcp:tool_calls:${whmcsClientId}:${today}`;
  pipeline.incr(dailyKey);
  pipeline.expire(dailyKey, TTL_35_DAYS);

  // Per-tool usage counter
  const toolUsageKey = `mcp:tool_usage:${whmcsClientId}`;
  pipeline.hincrby(toolUsageKey, toolName, 1);
  pipeline.expire(toolUsageKey, TTL_35_DAYS);

  // Per-tool timing (for performance dashboards)
  if (executionMs > 0) {
    const timingKey = `mcp:tool_timing:${whmcsClientId}`;
    pipeline.hset(timingKey, toolName, executionMs);
    pipeline.expire(timingKey, TTL_35_DAYS);
  }

  try {
    await pipeline.exec();
  } catch (err) {
    console.error(`[TOKEN-GATE] Record failed: ${err.message}`);
  }
}

/**
 * Get MCP-specific usage stats for a user.
 *
 * @param {string|number} whmcsClientId
 * @returns {Promise<object>}
 */
export async function getMcpUsageStats(whmcsClientId) {
  if (!whmcsClientId || !redis) {
    return { available: false };
  }

  try {
    const today = new Date().toISOString().slice(0, 10);
    const [used, limit, todayCalls, toolUsage] = await Promise.all([
      redis.get(`tokens:used:${whmcsClientId}`),
      redis.get(`tokens:limit:${whmcsClientId}`),
      redis.get(`mcp:tool_calls:${whmcsClientId}:${today}`),
      redis.hgetall(`mcp:tool_usage:${whmcsClientId}`),
    ]);

    const usedInt = parseInt(used || '0', 10);
    const limitInt = parseInt(limit || '0', 10);

    // Top tools by call count
    const topTools = Object.entries(toolUsage || {})
      .map(([name, count]) => ({ name, count: parseInt(count, 10) }))
      .sort((a, b) => b.count - a.count)
      .slice(0, 10);

    return {
      available: true,
      tokens: {
        used: usedInt,
        limit: limitInt,
        remaining: limitInt > 0 ? Math.max(0, limitInt - usedInt) : 'unlimited',
        percentUsed: limitInt > 0 ? Math.round((usedInt / limitInt) * 100) : 0,
      },
      todayToolCalls: parseInt(todayCalls || '0', 10),
      topTools,
    };
  } catch (err) {
    return { available: false, error: err.message };
  }
}
