/**
 * docs/toolDocs.js — Auto-Generated Tool Documentation Engine
 *
 * Generates comprehensive documentation from the 400+ tool definitions.
 * Supports multiple output formats:
 *
 *   1. JSON API — for the in-editor help panel and autocomplete
 *   2. Markdown — for the public docs site / README
 *   3. HTML — for embedding in the marketing/help pages
 *   4. Search index — for the "what can Alfred do?" query handler
 *
 * Documentation is generated on-demand and cached in memory.
 * The tool definitions in tools.js are the single source of truth.
 */

import { toolDefinitions } from '../tools.js';

// ── Tool categorization ──────────────────────────────────────────────────────
const CATEGORIES = {
  files:       { label: 'File Management', icon: '📁', tools: ['read_file', 'write_file', 'list_directory', 'delete_file', 'rename_file', 'create_directory', 'search_files', 'find_file', 'get_file_info'] },
  databases:   { label: 'Database Management', icon: '🗄️', tools: ['list_databases', 'create_database', 'delete_database', 'get_database_info', 'get_database_schema', 'get_database_stats', 'execute_query', 'backup_database', 'mysql_databases'] },
  domains:     { label: 'Domain & DNS', icon: '🌐', tools: ['list_domains', 'list_subdomains', 'create_subdomain', 'delete_subdomain', 'list_dns_records', 'add_dns_record', 'delete_dns_record'] },
  email:       { label: 'Email', icon: '📧', tools: ['list_email_accounts', 'create_email_account', 'delete_email_account', 'create_email_forwarder', 'create_autoresponder', 'send_email'] },
  ssl:         { label: 'SSL & Security', icon: '🔒', tools: ['request_ssl_certificate', 'get_ssl_status', 'force_https', 'security_scan', 'dependency_audit', 'check_site_health'] },
  cron:        { label: 'Cron Jobs', icon: '⏰', tools: ['list_cron_jobs', 'create_cron_job', 'delete_cron_job'] },
  backups:     { label: 'Backups & Checkpoints', icon: '💾', tools: ['create_backup', 'list_backups', 'restore_backup', 'create_checkpoint', 'restore_checkpoint', 'list_checkpoints'] },
  wordpress:   { label: 'WordPress', icon: '📝', tools: ['install_wordpress', 'manage_wordpress', 'install_plugin', 'install_theme', 'search_plugins', 'manage_cache', 'wordpress_seo', 'wordpress_staging', 'manage_htaccess'] },
  git:         { label: 'Git & Version Control', icon: '🔀', tools: ['git_init', 'git_commit', 'git_clone', 'list_git_branches', 'get_git_status', 'get_git_log', 'get_git_diff', 'smart_commit', 'amend_commit'] },
  billing:     { label: 'Billing & Commerce', icon: '💳', tools: ['get_my_profile', 'get_my_services', 'get_product_catalog', 'get_my_invoices', 'get_my_tickets', 'get_my_quotes', 'get_domain_pricing', 'check_domain', 'register_domain', 'create_support_ticket', 'get_invoice_details', 'get_service_details', 'get_credit_balance', 'get_account_usage', 'get_account_limits', 'get_account_summary'] },
  images:      { label: 'Image Generation', icon: '🎨', tools: ['generate_image'] },
  memory:      { label: 'AI Memory (ELEPHANT)', icon: '🧠', tools: ['alfred_remember', 'alfred_recall', 'alfred_forget', 'memory_summary', 'save_session_summary'] },
  search:      { label: 'Code Intelligence (ORACLE)', icon: '🔍', tools: ['semantic_code_search', 'index_workspace', 'get_index_stats', 'start_file_watcher', 'stop_file_watcher', 'get_watcher_status'] },
  playbooks:   { label: 'Workflows (PLAYBOOK)', icon: '📋', tools: ['run_playbook', 'list_playbooks', 'save_playbook'] },
  scheduler:   { label: 'Scheduler (CLOCKWORK)', icon: '🕐', tools: ['schedule_task', 'list_scheduled_tasks', 'delete_scheduled_task', 'task_logs'] },
  agents:      { label: 'Multi-Agent (HIVEMIND)', icon: '🐝', tools: ['spawn_sub_agent', 'collect_agent_results'] },
  devtools:    { label: 'Developer Tools', icon: '🚀', tools: ['run_terminal_command', 'fetch_url', 'read_pdf', 'generate_word_doc', 'generate_pdf', 'code_review', 'project_snapshot', 'get_tool_analytics', 'get_error_logs', 'get_traffic_stats', 'terminal_session_status', 'terminal_history', 'terminal_reset'] },
  docs:        { label: 'Tool Documentation', icon: '📖', tools: ['search_tools', 'get_tool_docs', 'get_tool_doc'] },
  system:      { label: 'System & Monitoring', icon: '🖥️', tools: ['get_isolation_status', 'get_mcp_usage', 'get_error_summary'] },
  conduit:     { label: 'API Gateway (CONDUIT)', icon: '🔌', tools: ['conduit_register_api', 'conduit_list_apis', 'conduit_call_api', 'conduit_remove_api', 'conduit_create_webhook', 'conduit_list_webhooks', 'conduit_test_webhook', 'conduit_delete_webhook', 'conduit_create_pipeline', 'conduit_list_pipelines', 'conduit_run_pipeline', 'conduit_delete_pipeline', 'conduit_get_logs'] },
  architect:   { label: 'Infrastructure (ARCHITECT)', icon: '🏗️', tools: ['architect_env_list', 'architect_env_get', 'architect_env_set', 'architect_scaffold', 'architect_create_deployment', 'architect_list_deployments', 'architect_run_deployment', 'architect_analyze', 'architect_resources'] },
  sentinel:    { label: 'Security (SENTINEL)', icon: '🛡️', tools: ['sentinel_create_baseline', 'sentinel_check_integrity', 'sentinel_analyze_access_logs', 'sentinel_vuln_scan', 'sentinel_check_ip', 'sentinel_log_incident', 'sentinel_list_incidents', 'sentinel_resolve_incident', 'sentinel_set_policy', 'sentinel_list_policies'] },
  forge:       { label: 'Code Gen (FORGE)', icon: '⚒️', tools: ['forge_generate_crud', 'forge_generate_component', 'forge_generate_tests', 'forge_analyze_code', 'forge_save_snippet', 'forge_list_snippets', 'forge_get_snippet'] },
  chronicle:   { label: 'Audit Trail (CHRONICLE)', icon: '📜', tools: ['chronicle_log_event', 'chronicle_query_events', 'chronicle_verify_integrity', 'chronicle_track_activity', 'chronicle_activity_summary', 'chronicle_record_change', 'chronicle_change_history', 'chronicle_start_session', 'chronicle_end_session', 'chronicle_list_sessions', 'chronicle_compliance_report'] },
  nexus:       { label: 'Knowledge Graph (NEXUS)', icon: '🕸️', tools: ['nexus_add_entity', 'nexus_add_relation', 'nexus_remove_entity', 'nexus_query', 'nexus_neighbors', 'nexus_impact_analysis', 'nexus_discover_dependencies', 'nexus_stats', 'nexus_add_knowledge', 'nexus_search_knowledge', 'nexus_list_knowledge'] },
  cortex:      { label: 'Reasoning (CORTEX)', icon: '🧪', tools: ['cortex_decompose', 'cortex_update_step', 'cortex_get_plan', 'cortex_list_plans', 'cortex_set_goal', 'cortex_update_goal', 'cortex_list_goals', 'cortex_analyze_decision', 'cortex_record_decision', 'cortex_list_decisions', 'cortex_add_reasoning', 'cortex_get_reasoning', 'cortex_list_reasoning', 'cortex_score_priority', 'cortex_context'] },
  empathy:    { label: 'Emotional (EMPATHY)', icon: '💗', tools: ['empathy_analyze_sentiment', 'empathy_detect_tone', 'empathy_track_mood', 'empathy_mood_history', 'empathy_suggest_response', 'empathy_detect_frustration', 'empathy_deescalate', 'empathy_analyze_feedback', 'empathy_emotional_summary', 'empathy_set_tone', 'empathy_rapport_score'] },
  muse:       { label: 'Creative (MUSE)', icon: '🎨', tools: ['muse_brainstorm', 'muse_brand_voice', 'muse_storytell', 'muse_name_generator', 'muse_tagline', 'muse_variations', 'muse_metaphor', 'muse_mood_board', 'muse_copywrite', 'muse_pitch'] },
  prism:      { label: 'Visual (PRISM)', icon: '🔮', tools: ['prism_analyze_colors', 'prism_suggest_palette', 'prism_check_contrast', 'prism_analyze_layout', 'prism_design_system', 'prism_responsive_check', 'prism_typography', 'prism_visual_score', 'prism_icon_suggest'] },
  tempo:      { label: 'Temporal (TEMPO)', icon: '⏱️', tools: ['tempo_trend_analyze', 'tempo_predict', 'tempo_seasonality', 'tempo_deadline_risk', 'tempo_velocity', 'tempo_capacity', 'tempo_timeline', 'tempo_peak_hours', 'tempo_eta'] },
  echo:       { label: 'Pattern (ECHO)', icon: '📡', tools: ['echo_detect_anomaly', 'echo_find_patterns', 'echo_cluster', 'echo_predict_failure', 'echo_correlate', 'echo_baseline_drift', 'echo_root_cause', 'echo_fingerprint', 'echo_forecast'] },
  pulse:      { label: 'Social (PULSE)', icon: '💓', tools: ['pulse_engagement', 'pulse_behavior_track', 'pulse_cohort_analyze', 'pulse_churn_predict', 'pulse_satisfaction', 'pulse_community', 'pulse_collaboration', 'pulse_influence_map', 'pulse_feedback_loop'] },
  sage:       { label: 'Linguistic (SAGE)', icon: '📚', tools: ['sage_translate', 'sage_readability', 'sage_grammar', 'sage_localize', 'sage_summarize', 'sage_keywords', 'sage_tone_match', 'sage_simplify', 'sage_glossary', 'sage_compare'] },
};

let _cache = null;

/**
 * Build a lookup map from tool definitions.
 * @returns {Map<string, object>}
 */
function buildToolMap() {
  const map = new Map();
  for (const tool of toolDefinitions) {
    map.set(tool.name, tool);
  }
  return map;
}

/**
 * Generate the complete documentation structure.
 * @returns {object}
 */
function generateDocs() {
  if (_cache) return _cache;

  const toolMap = buildToolMap();
  const categories = [];
  const allTools = [];
  const uncategorized = [];

  // Track which tools are categorized
  const categorizedTools = new Set();

  for (const [catKey, catDef] of Object.entries(CATEGORIES)) {
    const catTools = [];

    for (const toolName of catDef.tools) {
      const def = toolMap.get(toolName);
      if (!def) continue; // tool may not exist yet

      categorizedTools.add(toolName);
      const doc = documentTool(def);
      catTools.push(doc);
      allTools.push({ ...doc, category: catKey });
    }

    if (catTools.length > 0) {
      categories.push({
        key: catKey,
        label: catDef.label,
        icon: catDef.icon,
        toolCount: catTools.length,
        tools: catTools,
      });
    }
  }

  // Find uncategorized tools
  for (const tool of toolDefinitions) {
    if (!categorizedTools.has(tool.name)) {
      const doc = documentTool(tool);
      uncategorized.push({ ...doc, category: 'other' });
      allTools.push({ ...doc, category: 'other' });
    }
  }

  if (uncategorized.length > 0) {
    categories.push({
      key: 'other',
      label: 'Other',
      icon: '🔧',
      toolCount: uncategorized.length,
      tools: uncategorized,
    });
  }

  _cache = {
    version: '4.1.0',
    generatedAt: new Date().toISOString(),
    totalTools: toolDefinitions.length,
    totalCategories: categories.length,
    categories,
    allTools,
  };

  return _cache;
}

/**
 * Generate documentation for a single tool.
 *
 * @param {object} def — tool definition from tools.js
 * @returns {object}
 */
function documentTool(def) {
  const params = [];
  if (def.inputSchema?.properties) {
    const required = new Set(def.inputSchema.required || []);
    for (const [name, schema] of Object.entries(def.inputSchema.properties)) {
      params.push({
        name,
        type: schema.type || 'any',
        required: required.has(name),
        description: schema.description || '',
        default: schema.default,
        enum: schema.enum,
        minimum: schema.minimum,
        maximum: schema.maximum,
      });
    }
  }

  return {
    name: def.name,
    description: def.description || '',
    parameters: params,
    parameterCount: params.length,
    requiredParams: params.filter(p => p.required).map(p => p.name),
    optionalParams: params.filter(p => !p.required).map(p => p.name),
    exampleUsage: generateExample(def),
  };
}

/**
 * Generate an example usage string for a tool.
 * @param {object} def
 * @returns {string}
 */
function generateExample(def) {
  const args = {};
  if (def.inputSchema?.properties) {
    const required = new Set(def.inputSchema.required || []);
    for (const [name, schema] of Object.entries(def.inputSchema.properties)) {
      if (required.has(name)) {
        if (schema.enum) args[name] = schema.enum[0];
        else if (schema.type === 'string') args[name] = `<${name}>`;
        else if (schema.type === 'number') args[name] = schema.default || 1;
        else if (schema.type === 'boolean') args[name] = schema.default ?? true;
        else args[name] = `<${name}>`;
      }
    }
  }
  return JSON.stringify(args, null, 2);
}

// ── Public API ──────────────────────────────────────────────────────────────

/**
 * Get full documentation as JSON.
 * @returns {object}
 */
export function getDocsJSON() {
  return generateDocs();
}

/**
 * Get documentation for a specific tool by name.
 * @param {string} toolName
 * @returns {object|null}
 */
export function getToolDoc(toolName) {
  const docs = generateDocs();
  return docs.allTools.find(t => t.name === toolName) || null;
}

/**
 * Search tools by keyword (for "what can Alfred do?" queries).
 *
 * @param {string} query — search query
 * @returns {Array<object>}
 */
export function searchTools(query) {
  const docs = generateDocs();
  const q = query.toLowerCase();
  const words = q.split(/\s+/).filter(Boolean);

  return docs.allTools
    .map(tool => {
      let score = 0;
      const text = `${tool.name} ${tool.description} ${tool.category}`.toLowerCase();

      for (const word of words) {
        if (tool.name.toLowerCase().includes(word)) score += 10;
        if (tool.description.toLowerCase().includes(word)) score += 5;
        if (tool.category.toLowerCase().includes(word)) score += 3;
      }

      return { ...tool, relevance: score };
    })
    .filter(t => t.relevance > 0)
    .sort((a, b) => b.relevance - a.relevance)
    .slice(0, 15);
}

/**
 * Generate Markdown documentation.
 * @returns {string}
 */
export function getDocsMarkdown() {
  const docs = generateDocs();
  let md = `# Alfred AI — Tool Documentation\n\n`;
  md += `> **${docs.totalTools} tools** across **${docs.totalCategories} categories**\n`;
  md += `> Generated: ${docs.generatedAt}\n\n`;
  md += `## Table of Contents\n\n`;

  for (const cat of docs.categories) {
    md += `- [${cat.icon} ${cat.label}](#${cat.key}) (${cat.toolCount} tools)\n`;
  }
  md += `\n---\n\n`;

  for (const cat of docs.categories) {
    md += `## ${cat.icon} ${cat.label} {#${cat.key}}\n\n`;

    for (const tool of cat.tools) {
      md += `### \`${tool.name}\`\n\n`;
      md += `${tool.description}\n\n`;

      if (tool.parameters.length > 0) {
        md += `| Parameter | Type | Required | Description |\n`;
        md += `|-----------|------|----------|-------------|\n`;
        for (const p of tool.parameters) {
          md += `| \`${p.name}\` | ${p.type} | ${p.required ? '✅' : '❌'} | ${p.description} |\n`;
        }
        md += `\n`;
      }

      md += `<details><summary>Example</summary>\n\n\`\`\`json\n${tool.exampleUsage}\n\`\`\`\n</details>\n\n`;
    }
  }

  return md;
}

/**
 * Generate HTML documentation snippet (for embedding).
 * @returns {string}
 */
export function getDocsHTML() {
  const docs = generateDocs();
  let html = `<div class="alfred-docs">\n`;
  html += `<p class="docs-summary"><strong>${docs.totalTools}</strong> tools across <strong>${docs.totalCategories}</strong> categories</p>\n`;

  for (const cat of docs.categories) {
    html += `<div class="doc-category" id="doc-${cat.key}">\n`;
    html += `  <h3>${cat.icon} ${cat.label} <span class="count">${cat.toolCount}</span></h3>\n`;
    html += `  <div class="doc-tools">\n`;

    for (const tool of cat.tools) {
      html += `    <div class="doc-tool">\n`;
      html += `      <h4><code>${tool.name}</code></h4>\n`;
      html += `      <p>${tool.description}</p>\n`;
      if (tool.requiredParams.length > 0) {
        html += `      <div class="params">Required: ${tool.requiredParams.map(p => `<code>${p}</code>`).join(', ')}</div>\n`;
      }
      html += `    </div>\n`;
    }

    html += `  </div>\n</div>\n`;
  }

  html += `</div>`;
  return html;
}

/**
 * Get a quick summary (for health endpoints and system prompts).
 * @returns {object}
 */
export function getDocsSummary() {
  const docs = generateDocs();
  return {
    totalTools: docs.totalTools,
    totalCategories: docs.totalCategories,
    categories: docs.categories.map(c => ({
      key: c.key,
      label: c.label,
      icon: c.icon,
      toolCount: c.toolCount,
    })),
    generatedAt: docs.generatedAt,
  };
}

/**
 * Invalidate the cache (call after hot-reloading tools).
 */
export function invalidateCache() {
  _cache = null;
}
