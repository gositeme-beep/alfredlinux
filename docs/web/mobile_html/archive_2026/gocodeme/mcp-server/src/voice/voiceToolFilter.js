/**
 * Voice "lite" toolset — reduces prompt size & model latency by excluding tools that are
 * rarely appropriate for spoken hosting support (multi-agent fleets, browser automation,
 * heavy media jobs, meta-MCP, experimental agents, large CRM/commerce verticals).
 *
 * IDE / text chat still uses the full toolDefinitions from tools.js.
 *
 * Env:
 *   VOICE_LITE_TOOLSET=1 (default) — apply defaults below + VOICE_EXCLUDE_TOOLS
 *   VOICE_LITE_TOOLSET=0|full|false — no default exclusions; only VOICE_EXCLUDE_TOOLS applies
 *   VOICE_INCLUDE_TOOLS — comma list always kept even if a prefix would drop them
 *   VOICE_EXTRA_EXCLUDE_PREFIXES — extra comma-separated prefixes
 */

/** Prefixes: multi-step agent verticals, call-center fleet, dev pipelines, large product suites */
export const DEFAULT_VOICE_EXCLUDE_PREFIXES = [
  'nexus_',
  'cortex_',
  'chronicle_',
  'empathy_',
  'muse_',
  'prism_',
  'tempo_',
  'echo_',
  'pulse_',
  'conduit_',
  'autopilot_',
  'fleet_', // call-center / agent fleet (50+ tools)
  'forge_',
  'sentinel_',
  'architect_', // whitelisted: architect_resources
  'commerce_', // large e-comm ops suite; core billing stays (get_invoices, pay_invoice, …)
  'messaging_', // campaign suite; core send_email / SMS tools remain in tools.js
];

/** Exact names: long-running, headless browser, meta-MCP, subagents, heavy media */
export const DEFAULT_VOICE_EXCLUDE_EXACT = new Set([
  // Browser automation (slow, screen-oriented)
  'browse_web',
  'screenshot_page',
  'click_element',
  'fill_form',
  'extract_data',
  // Heavy / long jobs
  'generate_video',
  'process_video',
  'reindex_workspace',
  'rag_ingest',
  'local_llm_pull',
  'k8s_manage',
  // Meta / dispatch
  'mcp_connect',
  'mcp_disconnect',
  'mcp_list_servers',
  'mcp_call_tool',
  'spawn_subagent',
  'collect_results',
  // Experimental / meta-agent
  'agent_swarm',
  'self_evolve',
  'predictive_build',
  'cross_channel_sync',
  'ambient_intelligence',
  'time_travel_debug',
  'reality_bridge',
  'fleet_orchestrator',
]);

const LITE_OFF = new Set(['0', 'false', 'no', 'full', 'off']);

function parseEnvList(s) {
  return (s || '')
    .split(',')
    .map((x) => x.trim())
    .filter(Boolean);
}

/** Always kept when lite mode is on (prefix would otherwise remove them). */
let _whitelist = null;
function getWhitelist() {
  if (!_whitelist) {
    _whitelist = new Set(
      parseEnvList(
        process.env.VOICE_INCLUDE_TOOLS ||
          'architect_resources,search_tools,get_tool_docs,get_tool_doc',
      ),
    );
  }
  return _whitelist;
}

function extraPrefixes() {
  return parseEnvList(process.env.VOICE_EXTRA_EXCLUDE_PREFIXES);
}

let _cachedLite = null;

export function isVoiceLiteToolsetEnabled() {
  if (_cachedLite !== null) return _cachedLite;
  const v = String(process.env.VOICE_LITE_TOOLSET ?? '1').toLowerCase();
  _cachedLite = !LITE_OFF.has(v);
  return _cachedLite;
}

/**
 * @param {string} name — tool name
 * @param {Set<string>} envExclude — VOICE_EXCLUDE_TOOLS from env
 */
export function isToolExcludedForVoice(name, envExclude) {
  if (getWhitelist().has(name)) return false;
  if (envExclude.has(name)) return true;
  if (!isVoiceLiteToolsetEnabled()) return false;
  if (DEFAULT_VOICE_EXCLUDE_EXACT.has(name)) return true;
  const prefixes = [...DEFAULT_VOICE_EXCLUDE_PREFIXES, ...extraPrefixes()];
  for (const p of prefixes) {
    if (p && name.startsWith(p)) return true;
  }
  return false;
}
