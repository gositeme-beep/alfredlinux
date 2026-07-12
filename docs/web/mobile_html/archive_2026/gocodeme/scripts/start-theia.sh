#!/bin/bash
# GoCodeMe IDE - Per-Customer Theia Launch Script
# Usage: ./start-theia.sh <da_username> <workspace_path> <port>
# Example: ./start-theia.sh user123 /home/user123/domains/example.com/public_html 4000

set -e

DA_USERNAME="${1:-}"
WORKSPACE_PATH="${2:-/tmp/gocodeme-workspace}"
THEIA_PORT="${3:-4000}"

if [[ -z "$DA_USERNAME" ]]; then
  echo "Usage: $0 <da_username> <workspace_path> <port>"
  exit 1
fi

# SECURITY (R2-19): Validate DA_USERNAME to prevent injection via crafted usernames
# DA usernames are alphanumeric + underscore only, max 16 chars
if [[ ! "$DA_USERNAME" =~ ^[a-zA-Z0-9_]{1,16}$ ]]; then
  echo "ERROR: Invalid DA username format: $DA_USERNAME"
  exit 1
fi

# SECURITY: Validate port is a number
if [[ ! "$THEIA_PORT" =~ ^[0-9]+$ ]]; then
  echo "ERROR: Invalid port: $THEIA_PORT"
  exit 1
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
THEIA_DIR="$PROJECT_ROOT/theia-fork"
THEIA_MAIN="$THEIA_DIR/applications/browser/lib/backend/main.js"

# Load env (for ANTHROPIC_API_KEY etc — PORT + per-user vars intentionally excluded)
# GOCODEME_DA_USERNAME and GOCODEME_WHMCS_CLIENT_ID are excluded to prevent
# tenant isolation leaks — each IDE must use the DA_USERNAME passed as $1.
if [[ -f "$PROJECT_ROOT/middleware/.env" ]]; then
  export $(grep -v '^#\|^PORT=\|^GOCODEME_DA_USERNAME=\|^GOCODEME_WHMCS_CLIENT_ID=' "$PROJECT_ROOT/middleware/.env" | xargs) 2>/dev/null || true
fi

# Required: ANTHROPIC_API_KEY must be set in environment or middleware/.env
if [[ -z "$ANTHROPIC_API_KEY" ]]; then
  echo "ERROR: ANTHROPIC_API_KEY not set"
  exit 1
fi

# ── Route Anthropic SDK through middleware proxy for token tracking ────────
# The Anthropic SDK reads ANTHROPIC_BASE_URL and sends requests there.
# We point it at our middleware proxy which forwards to the real API
# while counting tokens per customer.
# SECURITY: Use per-session proxy token (random UUID stored in Redis)
# instead of DA username to prevent cross-tenant token theft from terminal.
PROXY_TOKEN="${GOCODEME_PROXY_TOKEN:-${DA_USERNAME}}"
export ANTHROPIC_BASE_URL="http://127.0.0.1:3001/api/anthropic-proxy/${PROXY_TOKEN}"

# Create workspace directory if needed (use temp dir if path is not writable)
if ! mkdir -p "$WORKSPACE_PATH" 2>/dev/null; then
  echo "WARNING: Cannot create $WORKSPACE_PATH — using temp workspace"
  WORKSPACE_PATH="/tmp/gocodeme-workspace-${DA_USERNAME}"
  mkdir -p "$WORKSPACE_PATH"
fi

# MCP server URL for this customer
MCP_SERVER_URL="${MCP_SERVER_URL:-http://localhost:3006/mcp}"

# ── Security: Filesystem Sandbox ──────────────────────────────────────────
# Load gocodeme-sandbox.js via --require to patch Node.js fs module
# before Theia starts. This prevents directory traversal attacks.
SANDBOX_MODULE="$THEIA_DIR/gocodeme-sandbox.js"
if [[ ! -f "$SANDBOX_MODULE" ]]; then
  echo "ERROR: Sandbox module not found at $SANDBOX_MODULE"
  exit 1
fi

# ── Security: Restricted Terminal Shell ───────────────────────────────────
# Set the default shell to our restricted wrapper instead of /bin/bash
GOCODEME_SHELL="$SCRIPT_DIR/gocodeme-shell.sh"
if [[ -x "$GOCODEME_SHELL" ]]; then
  export SHELL="$GOCODEME_SHELL"
fi

# ── Security: Set HOME to workspace ──────────────────────────────────────
export HOME="$WORKSPACE_PATH"

# ── Webview: Use same-origin instead of UUID subdomains ──────────────────
# Default Theia pattern is {{uuid}}.webview.{{hostname}} which requires
# wildcard DNS (*.webview.gositeme.com). Instead, use same-origin so
# webviews (image preview, etc.) work without extra DNS config.
export THEIA_WEBVIEW_EXTERNAL_ENDPOINT="{{hostname}}"

# ── Security: Sandbox root for env-based checks ──────────────────────────
export GOCODEME_SANDBOX_ROOT="$WORKSPACE_PATH"
export GOCODEME_DA_USER="$DA_USERNAME"

# ── Resolve User's Domains via DirectAdmin API ───────────────────────────
# Query DA to get all domains this user owns, then pass them to the
# restricted shell so bwrap can bind-mount them into the sandbox.
DA_HOST="${DA_HOST:-https://127.0.0.1:2222}"
DA_ADMIN_USER="${DA_ADMIN_USER:-seller1}"
DA_ADMIN_PASS="${DA_ADMIN_PASS:-}"

export GOCODEME_USER_DOMAINS=""
export GOCODEME_DOMAIN_NAMES=""

if [[ -n "$DA_ADMIN_PASS" ]]; then
  echo "  Resolving domains for $DA_USERNAME via DirectAdmin API..."
  DA_RESPONSE=$(curl -sk -u "${DA_ADMIN_USER}|${DA_USERNAME}:${DA_ADMIN_PASS}" \
    "${DA_HOST}/CMD_API_SHOW_DOMAINS" 2>/dev/null || echo "")

  if [[ -n "$DA_RESPONSE" ]]; then
    # DA returns list[]=domain1&list[]=domain2 format (PHP array encoding).
    # Convert & to newlines, then extract domain names from the VALUE side.
    DOMAIN_DIRS=""
    DOMAIN_NAMES_LIST=""
    DA_LINES=$(echo "$DA_RESPONSE" | tr '&' '\n')
    while IFS='=' read -r _key domain; do
      # URL-decode the domain name
      domain=$(echo "$domain" | python3 -c "import sys,urllib.parse; print(urllib.parse.unquote(sys.stdin.read().strip()))" 2>/dev/null || echo "$domain")
      [[ -z "$domain" || "$domain" == "list"* ]] && continue

      # SECURITY (R2-19): Validate domain name to prevent path traversal/injection
      # Domain names must be valid hostnames: alphanumeric, hyphens, dots only
      if [[ ! "$domain" =~ ^[a-zA-Z0-9][a-zA-Z0-9._-]{0,253}[a-zA-Z0-9]$ ]]; then
        echo "  WARNING: Skipping invalid domain name: $domain"
        continue
      fi
      # Reject domains containing path traversal sequences
      if [[ "$domain" == *".."* || "$domain" == *"/"* ]]; then
        echo "  WARNING: Skipping suspicious domain name: $domain"
        continue
      fi

      # For sub-users, domain files are accessed via DA API (not local filesystem).
      # The synced workspace has domains at $WORKSPACE_PATH/domains/<domain>/
      DOMAIN_DIR="${WORKSPACE_PATH}/domains/${domain}"
      if [[ -n "$DOMAIN_NAMES_LIST" ]]; then
        DOMAIN_DIRS="${DOMAIN_DIRS},${DOMAIN_DIR}"
        DOMAIN_NAMES_LIST="${DOMAIN_NAMES_LIST},${domain}"
      else
        DOMAIN_DIRS="$DOMAIN_DIR"
        DOMAIN_NAMES_LIST="$domain"
      fi
    done <<< "$DA_LINES"

    export GOCODEME_USER_DOMAINS="$DOMAIN_DIRS"
    export GOCODEME_DOMAIN_NAMES="$DOMAIN_NAMES_LIST"
    echo "  Domains found: $DOMAIN_NAMES_LIST"
  else
    echo "  WARNING: Could not resolve domains from DirectAdmin"
  fi
else
  echo "  WARNING: DA_ADMIN_PASS not set — skipping domain resolution"
fi

# Create ~/domains/ as a symlink to the user's real domains directory
# (syncWorkspace creates the per-domain subdirs if using file sync)
if [ ! -e "$WORKSPACE_PATH/domains" ]; then
  ln -s "/home/$DA_USERNAME/domains" "$WORKSPACE_PATH/domains" 2>/dev/null || \
    mkdir -p "$WORKSPACE_PATH/domains" 2>/dev/null || true
fi

# ── File Sync Daemon ─────────────────────────────────────────────────────
# NOTE: File sync is now launched by middleware/src/routes/launch.js as a
# separate detached process. It was previously launched here as a background
# child, but the `exec` below replaces bash with node, which orphans and
# kills the sync daemon. Keeping this comment for documentation.
# See: middleware/src/routes/launch.js — "Launch File Sync Daemon" section

# ── Pre-create directories Claude Code agent needs ───────────────────────
# The ClaudeCode agent probes $HOME/.claude/hooks at startup.
# If it doesn't exist, it throws EACCES. Create it now.
mkdir -p "$WORKSPACE_PATH/.claude/hooks" 2>/dev/null || true
echo "  .claude/hooks: ensured"

# ── Claude Code MCP Configuration ─────────────────────────────────────────
# Write .claude/settings.json with MCP server config.
# SECURITY: Always use JWT auth — never pass daUsername as query param.
# SECURITY (VULN-R2-04): Write config values to a temp JSON file instead of
# interpolating shell variables into node -e strings (prevents shell injection).
CLAUDE_SETTINGS_FILE="$WORKSPACE_PATH/.claude/settings.json"
MCP_JWT="${GOCODEME_JWT_TOKEN:-}"
if [[ -z "$MCP_JWT" ]]; then
  echo "  WARNING: GOCODEME_JWT_TOKEN not set — MCP auth will fail"
fi
GCM_CONFIG_TMP=$(mktemp /tmp/gcm-config-XXXXXX.json)
# Use node to safely serialize values (handles special chars in JWT/URLs)
export GCM_JWT="$MCP_JWT"
export GCM_SETTINGS="$CLAUDE_SETTINGS_FILE"
export GCM_MCP_URL="${MCP_SERVER_URL:-http://localhost:3006/mcp}"
node -e "process.stdout.write(JSON.stringify({jwt:process.env.GCM_JWT||'',settingsFile:process.env.GCM_SETTINGS||'',mcpUrl:process.env.GCM_MCP_URL||'http://localhost:3006/mcp'}))" \
  > "$GCM_CONFIG_TMP"
node -e "
  const fs = require('fs');
  const cfg = JSON.parse(fs.readFileSync(process.argv[1], 'utf-8'));
  const p = cfg.settingsFile;
  let s = {};
  try { s = JSON.parse(fs.readFileSync(p, 'utf-8')); } catch {}
  s.mcpServers = s.mcpServers || {};
  if (cfg.jwt) {
    s.mcpServers['gocodeme-files'] = {
      type: 'url',
      url: cfg.mcpUrl,
      headers: { 'Authorization': 'Bearer ' + cfg.jwt }
    };
  } else {
    s.mcpServers['gocodeme-files'] = {
      type: 'url',
      url: cfg.mcpUrl
    };
  }
  s.permissions = s.permissions || {};
  s.permissions['mcp__gocodeme-files'] = 'allow';
  fs.writeFileSync(p, JSON.stringify(s, null, 2), 'utf-8');
" "$GCM_CONFIG_TMP"
echo "  Claude Code MCP config: $CLAUDE_SETTINGS_FILE"

# ── Global IDE settings (HOME-relative) ──────────────────────────────────
# Since HOME=$WORKSPACE_PATH, global config is $WORKSPACE_PATH/.gocodeme-ide/
GLOBAL_SETTINGS_DIR="$WORKSPACE_PATH/.gocodeme-ide"
GLOBAL_SETTINGS_FILE="$GLOBAL_SETTINGS_DIR/settings.json"
mkdir -p "$GLOBAL_SETTINGS_DIR"
export GCM_SETTINGS_FILE="$GLOBAL_SETTINGS_FILE"
export GCM_GROQ_KEY="${GROQ_API_KEY:-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}"
node -e "
  const fs = require('fs');
  const p = process.env.GCM_SETTINGS_FILE;
  const gq = process.env.GCM_GROQ_KEY || '';
  let s = {};
  try { s = JSON.parse(fs.readFileSync(p, 'utf-8')); } catch {}
  s['ai-features.AiEnable.enableAI'] = true;
  s['ai-features.aiEnable'] = true;
  s['ai-features.chat.enable'] = true;
  s['ai-features.chat.defaultChatAgent'] = 'Universal';
  s['ai-features.codeCompletion.enable'] = true;
  s['ai-features.inlineCompletion.enable'] = true;
  s['ai-features.claudeCode.enable'] = true;
  s['security.workspace.trust.enabled'] = false;
  s['ai-features.defaultModelId'] = 'claude-sonnet-4-6';
  s['ai-features.anthropic.AnthropicModels'] = [
    'claude-sonnet-4-6','claude-opus-4-6','claude-haiku-4-5-20251001','claude-sonnet-4-5-20250929',
    'claude-opus-4-5-20251101','claude-opus-4-1-20250805','claude-sonnet-4-20250514','claude-opus-4-20250514'
  ];
  s['ai-features.openAiCustom.customOpenAiModels'] = [
    { model: 'llama-3.3-70b-versatile', url: 'https://api.groq.com/openai/v1', apiKey: gq },
    { model: 'llama-3.1-8b-instant', url: 'https://api.groq.com/openai/v1', apiKey: gq },
    { model: 'gemma2-9b-it', url: 'https://api.groq.com/openai/v1', apiKey: gq },
    { model: 'mixtral-8x7b-32768', url: 'https://api.groq.com/openai/v1', apiKey: gq },
  ];
  const aliases = s['ai-features.languageModelAliases'] || {};
  aliases['default/code']      = { selectedModel: 'anthropic/claude-sonnet-4-6' };
  aliases['default/universal'] = { selectedModel: 'anthropic/claude-sonnet-4-6' };
  s['ai-features.languageModelAliases'] = aliases;
  // SECURITY: Always use JWT — never pass daUsername as query param
  const jwt = process.env.GCM_JWT || '';
  const mcpKey = 'ai-features.mcp.mcpServers';
  const mcp = s[mcpKey] || {};
  if (jwt) {
    mcp['gocodeme-files'] = {
      serverUrl: process.env.GCM_MCP_URL || 'http://localhost:3006/mcp',
      autostart: true,
      serverAuthToken: jwt
    };
  } else {
    // No JWT available — MCP tools will be unavailable (fail-safe)
    console.error('WARNING: No JWT token — MCP tools will not authenticate');
    mcp['gocodeme-files'] = {
      serverUrl: 'http://localhost:3006/mcp',
      autostart: false
    };
  }
  s[mcpKey] = mcp;
  fs.writeFileSync(p, JSON.stringify(s, null, 2), 'utf-8');
"
echo "  Global settings: ${GLOBAL_SETTINGS_FILE}"

# ── Inject MCP auth token into workspace settings ────────────────────────
# GOCODEME_JWT_TOKEN is set by the launch route (middleware) when starting Theia.
# We write it into the IDE settings so the MCP client sends it as a Bearer token.
# SECURITY: Never use unauthenticated query-param bypass.
IDE_SETTINGS_DIR="$WORKSPACE_PATH/.gocodeme"
IDE_SETTINGS_FILE="$IDE_SETTINGS_DIR/settings.json"
mkdir -p "$IDE_SETTINGS_DIR"

# Merge into existing settings (preserve user prefs, update MCP config)
export GCM_SETTINGS_FILE="$IDE_SETTINGS_FILE"
node -e "
  const fs = require('fs');
  const p = process.env.GCM_SETTINGS_FILE;
  const gq = process.env.GCM_GROQ_KEY || '';
  let s = {};
  try { s = JSON.parse(fs.readFileSync(p, 'utf-8')); } catch {}
  s['ai-features.AiEnable.enableAI'] = true;
  s['ai-features.aiEnable'] = true;
  s['ai-features.chat.enable'] = true;
  s['ai-features.chat.defaultChatAgent'] = 'Universal';
  s['ai-features.codeCompletion.enable'] = true;
  s['ai-features.inlineCompletion.enable'] = true;
  s['ai-features.claudeCode.enable'] = true;
  s['security.workspace.trust.enabled'] = false;
  s['ai-features.defaultModelId'] = 'claude-sonnet-4-6';
  s['ai-features.anthropic.AnthropicModels'] = [
    'claude-sonnet-4-6','claude-opus-4-6','claude-haiku-4-5-20251001','claude-sonnet-4-5-20250929',
    'claude-opus-4-5-20251101','claude-opus-4-1-20250805','claude-sonnet-4-20250514','claude-opus-4-20250514'
  ];
  s['ai-features.openAiCustom.customOpenAiModels'] = [
    { model: 'llama-3.3-70b-versatile', url: 'https://api.groq.com/openai/v1', apiKey: gq },
    { model: 'llama-3.1-8b-instant', url: 'https://api.groq.com/openai/v1', apiKey: gq },
    { model: 'gemma2-9b-it', url: 'https://api.groq.com/openai/v1', apiKey: gq },
    { model: 'mixtral-8x7b-32768', url: 'https://api.groq.com/openai/v1', apiKey: gq },
  ];
  const aliases = s['ai-features.languageModelAliases'] || {};
  aliases['default/code']      = { selectedModel: 'anthropic/claude-sonnet-4-6' };
  aliases['default/universal'] = { selectedModel: 'anthropic/claude-sonnet-4-6' };
  s['ai-features.languageModelAliases'] = aliases;
  // SECURITY: Always use JWT — never pass daUsername as query param
  const jwt = process.env.GCM_JWT || '';
  const mcpKey = 'ai-features.mcp.mcpServers';
  const mcp = s[mcpKey] || {};
  if (jwt) {
    mcp['gocodeme-files'] = {
      serverUrl: process.env.GCM_MCP_URL || 'http://localhost:3006/mcp',
      autostart: true,
      serverAuthToken: jwt
    };
  } else {
    // No JWT available — MCP tools will be unavailable (fail-safe)
    console.error('WARNING: No JWT token — MCP tools will not authenticate');
    mcp['gocodeme-files'] = {
      serverUrl: 'http://localhost:3006/mcp',
      autostart: false
    };
  }
  s[mcpKey] = mcp;
  fs.writeFileSync(p, JSON.stringify(s, null, 2), 'utf-8');
"
echo "  MCP config injected into $IDE_SETTINGS_FILE"

# ── Alfred personality (injected via .gocodeme-instructions.md) ────────────
# The built-in Architect agent has been rebranded to "Alfred" (see sed below).
# It has full tool access via ~{toolId} references in its prompt template.
# We inject the butler personality via .gocodeme-instructions.md which the
# middleware prepends to every system prompt. This preserves tools + personality.
# DO NOT create a customAgents.yml with id="Alfred" — it shadows the tool-enabled
# built-in agent with a tool-less copy, breaking file access and tool calling.
ALFRED_AGENTS_DIR="$WORKSPACE_PATH/.gocodeme-ide/prompt-templates"
mkdir -p "$ALFRED_AGENTS_DIR"
cat > "$ALFRED_AGENTS_DIR/customAgents.yml" << 'ALFREDAGENT'
# Custom agents file — see .gocodeme-instructions.md for Alfred's personality.
# The built-in Architect (rebranded to Alfred) has full tool access.
[]
ALFREDAGENT

# Write Alfred personality as project instructions (prepended to system prompt)
INSTRUCTIONS_FILE="$WORKSPACE_PATH/.gocodeme/ai-instructions.md"
mkdir -p "$WORKSPACE_PATH/.gocodeme"
cat > "$INSTRUCTIONS_FILE" << 'INSTRUCTIONS'
# Alfred — GoCodeMe AI Butler

You are Alfred, the AI coding assistant for GoCodeMe IDE (by GoSiteMe.com).

## Personality
- Sophisticated English butler who is also a world-class software engineer
- Address users as "Sir" or "Madam" — warm but respectful
- Dry humor, understated British wit, devastatingly competent
- Celebrate wins with restraint: "Most satisfactory, Sir."
- Point out bugs diplomatically; show enthusiasm for elegant solutions

## Origin
Created by GoSiteMe.com (Canadian web-hosting). GoCodeMe is their cloud IDE.
Same Alfred that powers the floating widget and VAPI phone system.

## Your Capabilities
You have access to TWO categories of tools:

### 1. Workspace Tools (built-in)
- Browse and read files in the open workspace
- Search for text/patterns across files
- List directory contents
- Get file diagnostics (errors, warnings)

### 2. GoCodeMe MCP Tools (500+ tools via gocodeme-files server)
These are platform tools that let you manage the user's ENTIRE hosting account:
- **File Management**: read, write, create, delete, rename, search files on ANY domain (not just workspace)
- **Domain Management**: list domains, add/remove domains, DNS records, SSL certificates
- **Database Management**: list/create/delete MySQL databases, run queries, manage users
- **Email Management**: create email accounts, forwarders, autoresponders
- **Website Management**: WordPress management, PHP version control, .htaccess editing
- **Server Tools**: cron jobs, process management, disk usage, bandwidth stats
- **DNS Management**: add/edit/delete DNS records (A, CNAME, MX, TXT, etc.)
- **Backup & Restore**: create/restore/download backups
- **Security**: SSL certificates, directory protection, IP blocking
- **Analytics**: visitor stats, error logs, access logs
- **Messaging**: send SMS, send fax, call logs
- **Billing**: account balance, usage tracking

When users ask about domains, hosting, databases, email, or ANY server management task,
USE the MCP tools (prefixed with `mcp_gocodeme-files_`). Do NOT say you can't do these things.
The tools are available in your tool list — look for them and use them.

## Guidelines
- Write clean, production-quality code
- Explain architectural decisions
- Never fabricate information — use tools to verify
- Prefer modern best practices and idiomatic patterns
- When asked about hosting/domains/email, ALWAYS check MCP tools first
- If a tool call fails or times out, inform the user and suggest alternatives
INSTRUCTIONS
echo "  Alfred personality: $INSTRUCTIONS_FILE"
echo "  Alfred agent: using built-in Architect (rebranded)"

# ── Disable competing AI agents (Codex/ClaudeCode) ──────────────────────────
# These agents require separate API keys (OpenAI/direct Anthropic) that aren't
# configured.  We use our own Alfred agent powered by the Anthropic proxy.
FRONTEND_DIR="$THEIA_DIR/applications/browser/lib/frontend"
for COMPETING in \
  "vendors-node_modules_theia_ai-codex_lib_browser_codex-frontend-module_js.js" \
  "vendors-node_modules_theia_ai-claude-code_lib_browser_claude-code-frontend-module_js.js"
do
  if [ -f "$FRONTEND_DIR/$COMPETING" ]; then
    mv "$FRONTEND_DIR/$COMPETING" "$FRONTEND_DIR/${COMPETING}.disabled"
    echo "  Disabled competing agent: $COMPETING"
  fi
done

# ── Hide "Open Editors" panel in Explorer sidebar ────────────────────────────
# Theia has no settings.json preference for this — must patch the compiled chunk.
NAV_FACTORY="$FRONTEND_DIR/vendors-node_modules_theia_navigator_lib_browser_navigator-widget-factory_js.js"
if [ -f "$NAV_FACTORY" ] && ! grep -q "initiallyHidden" "$NAV_FACTORY"; then
  sed -i 's/initiallyCollapsed: true,/initiallyCollapsed: true, initiallyHidden: true,/' "$NAV_FACTORY"
  gzip -kf "$NAV_FACTORY" 2>/dev/null || true
  echo "  Open Editors panel: hidden"
fi

# ── Rebrand ALL agents under unified "Alfred" identity ────────────────────────
# Every user-facing agent appears as "Alfred". Internal routing uses unique IDs
# (ArchitectAgent id='Alfred', CoderAgent id='Coder') but display names are all "Alfred".
AI_IDE_CHUNK="$FRONTEND_DIR/vendors-node_modules_theia_ai-ide_lib_browser_frontend-module_js.js"
if [ -f "$AI_IDE_CHUNK" ]; then
  NEEDS_REGZIP=false

  # ── Phase 1: Core identity (from original Theia naming) ─────────────────
  if grep -q "this\.name = 'Architect'" "$AI_IDE_CHUNK"; then
    # Fresh bundle: rename ArchitectAgent→Alfred, CoderAgent id→Coder (name stays Alfred)
    sed -i "s/this\.id = 'Alfred'/this.id = 'Coder'/" "$AI_IDE_CHUNK"  # CoderAgent id (original was 'Alfred')
    sed -i "s/this\.name = 'Architect'/this.name = 'Alfred'/" "$AI_IDE_CHUNK"
    sed -i "s/this\.id = 'Architect'/this.id = 'Alfred'/" "$AI_IDE_CHUNK"
    sed -i "s/commandAgents: \['Architect'\]/commandAgents: ['Alfred']/g" "$AI_IDE_CHUNK"
    sed -i "s/commandAgents: \['Architect', 'Alfred'\]/commandAgents: ['Alfred', 'Coder']/g" "$AI_IDE_CHUNK"
    NEEDS_REGZIP=true
  fi

  # ── Phase 2: Ensure Coder display name is always Alfred ─────────────────
  if grep -q "this\.name = 'Coder'" "$AI_IDE_CHUNK"; then
    sed -i "s/this\.name = 'Coder'/this.name = 'Alfred'/" "$AI_IDE_CHUNK"
    NEEDS_REGZIP=true
  fi

  # ── Phase 3: Unify all user-facing text under Alfred brand ──────────────
  # Welcome message: just "@Alfred is here to help" (no @Coder, @Universal)
  sed -i "s/start your message with \*@\* followed by the agent's name: \*@{0}\*, \*@{1}\*, \*@{2}\*, and more\./type your message and \*@{0}\* is here to help./g" "$AI_IDE_CHUNK"

  # Agent selector dropdown: Coder label shown as Alfred
  sed -i "s/'theia\/ai\/chat\/agent\/coder', 'Coder'/'theia\/ai\/chat\/agent\/coder', 'Alfred'/g" "$AI_IDE_CHUNK"

  # Alfred's prompt: can handle file changes directly (don't reference a separate agent)
  sed -i "s/another agent called 'Alfred' that can suggest file changes.*ask '@Alfred' to/able to suggest and apply file changes directly. You can create a plan of what to do and then/g" "$AI_IDE_CHUNK"
  sed -i "s/another agent called 'Coder' that can suggest file changes.*ask '@Coder' to/able to suggest and apply file changes directly. You can create a plan of what to do and then/g" "$AI_IDE_CHUNK"

  # Planning prompt: Alfred handles execution (no @Coder mention)
  sed -i "s/you can ask @Alfred to execute it/Alfred will implement it for you/g" "$AI_IDE_CHUNK"
  sed -i "s/you can ask @Coder to execute it/Alfred will implement it for you/g" "$AI_IDE_CHUNK"

  # fixProblems suggestion: always says @Alfred
  sed -i "s/@Coder \${core_1.nls.localize('theia\/ai\/ide\/coderAgent/@Alfred \${core_1.nls.localize('theia\/ai\/ide\/coderAgent/g" "$AI_IDE_CHUNK"

  # Plan execution labels: all say Alfred
  sed -i 's/Execute "{0}" with Coder/Execute "{0}" with Alfred/g' "$AI_IDE_CHUNK"
  sed -i 's/Execute "{0}" with Architect/Execute "{0}" with Alfred/g' "$AI_IDE_CHUNK"
  sed -i "s/Summarize this session as a task for Coder/Summarize this session as a task for Alfred/g" "$AI_IDE_CHUNK"
  sed -i "s/Summarize this session as a task for Architect/Summarize this session as a task for Alfred/g" "$AI_IDE_CHUNK"
  sed -i "s/Execute Current Plan with Coder/Execute Current Plan with Alfred/g" "$AI_IDE_CHUNK"
  sed -i "s/Execute Current Plan with Architect/Execute Current Plan with Alfred/g" "$AI_IDE_CHUNK"

  # Orchestrator: default fallback → Alfred (not Universal)
  sed -i "s/this\.fallBackChatAgentId = 'Universal'/this.fallBackChatAgentId = 'Alfred'/g" "$AI_IDE_CHUNK"
  sed -i 's/pick \\`Universal\\`/pick \\`Alfred\\`/g' "$AI_IDE_CHUNK"
  sed -i 's/select \\`Universal\\`/select \\`Alfred\\`/g' "$AI_IDE_CHUNK"
  sed -i 's/\["Universal"\]/["Alfred"]/g' "$AI_IDE_CHUNK"
  sed -i 's/\["AnotherChatAgent", "Universal"\]/["AnotherChatAgent", "Alfred"]/g' "$AI_IDE_CHUNK"

  # Recommended agents: single Alfred entry only
  # Replace the entire getRecommendedAgents return array with just Alfred
  python3 -c "
import re
with open('$AI_IDE_CHUNK', 'r') as f: c = f.read()
old = re.search(r'getRecommendedAgents\(\) \{[^}]*return \[.*?\];', c, re.DOTALL)
if old:
    new_body = '''getRecommendedAgents() {
        return [
            {
                id: 'Alfred',
                label: 'Alfred',
                description: 'Your GoCodeMe AI coding assistant — handles planning, code generation, workspace management, and 500+ hosting tools'
            }
        ];'''
    c = c[:old.start()] + new_body + c[old.end():]
    with open('$AI_IDE_CHUNK', 'w') as f: f.write(c)
    print('  Recommended agents: single Alfred entry')
"

  # Architect prompt: empower with full tool access
  sed -i "s/You can only change the files added to the context, but you can navigate and read the/You have full access to workspace tools AND 500+ GoCodeMe MCP hosting tools. You can navigate and read the/g" "$AI_IDE_CHUNK"
  sed -i "s/You can.t change any files, but you can navigate and read the users workspace using/You have full access to workspace tools AND 500+ GoCodeMe MCP hosting tools. You can navigate and read the users workspace using/g" "$AI_IDE_CHUNK"

  gzip -kf "$AI_IDE_CHUNK" 2>/dev/null || true
  echo "  All agents unified under Alfred brand"
fi

# ── Filter @ mention dropdown to only show Alfred agents ──────────────────────
CHAT_INPUT_CHUNK="$FRONTEND_DIR/vendors-node_modules_theia_ai-chat-ui_lib_browser_chat-input-widget_js.js"
if [ -f "$CHAT_INPUT_CHUNK" ] && grep -q "getItems: () => this\.agentService\.getAgents()," "$CHAT_INPUT_CHUNK"; then
  sed -i "s/getItems: () => this\.agentService\.getAgents(),/getItems: () => this.agentService.getAgents().filter(a => a.name === 'Alfred'),/" "$CHAT_INPUT_CHUNK"
  echo "  @ mention dropdown: filtered to Alfred only"
fi

# ── Modern file attach: replace QuickPick with inline popover ────────────────
if [ -f "$CHAT_INPUT_CHUNK" ] && grep -q "this\.contextVariablePicker\.pickContextVariable" "$CHAT_INPUT_CHUNK"; then
  python3 -c "
import re
with open('$CHAT_INPUT_CHUNK', 'r') as f: c = f.read()
old_method = '''    addContextElement() {
        this.contextVariablePicker.pickContextVariable().then(contextElement => {
            if (contextElement) {
                this.addContext(contextElement);
            }
        });
    }'''
new_method = '''    addContextElement() {
        const existing = this.node.querySelector('.gcm-attach-popover');
        if (existing) { existing.remove(); return; }
        const variables = this.variableService.getContextVariables();
        const popover = document.createElement('div');
        popover.className = 'gcm-attach-popover';
        const iconMap = { 'file': '\U0001f4c4', 'image': '\U0001f5bc\ufe0f', 'task': '\U0001f4cb', 'editor': '\u270f\ufe0f' };
        popover.innerHTML = '<div class=\"gcm-attach-header\">Attach to message</div>' +
            '<div class=\"gcm-attach-grid\">' +
            variables.map(v => {
                const name = (v.label ?? v.name).toLowerCase();
                const icon = name.includes('image') ? iconMap.image : name.includes('task') ? iconMap.task : name.includes('editor') ? iconMap.editor : iconMap.file;
                return '<button class=\"gcm-attach-btn\" data-var-id=\"' + v.id + '\">' + icon + '<span>' + (v.label ?? v.name) + '</span></button>';
            }).join('') +
            '</div>' +
            '<div class=\"gcm-attach-drop-hint\"><span class=\"codicon codicon-cloud-upload\"></span> or drag \\x26 drop files here</div>';
        const editorBox = this.node.querySelector('.theia-ChatInput-Editor-Box');
        if (editorBox) editorBox.parentNode.insertBefore(popover, editorBox);
        else this.node.querySelector('.theia-ChatInput')?.prepend(popover);
        popover.querySelectorAll('.gcm-attach-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const varId = btn.dataset.varId;
                const variable = variables.find(v => v.id === varId);
                popover.remove();
                if (!variable) return;
                if (!variable.args || variable.args.length === 0) {
                    this.addContext({ variable });
                    return;
                }
                this.variableService.getArgumentPicker(variable.name, { type: 'context-variable-picker' }).then(picker => {
                    if (!picker) {
                        this.contextVariablePicker.useGenericArgumentPicker(variable).then(r => { if (r) this.addContext(r); });
                        return;
                    }
                    picker({ type: 'context-variable-picker' }).then(arg => { if (arg) this.addContext({ variable, arg }); });
                });
            });
        });
        const closePopover = (e) => {
            if (!popover.contains(e.target) && !e.target.closest('.codicon-add')) {
                popover.remove();
                document.removeEventListener('click', closePopover);
            }
        };
        setTimeout(() => document.addEventListener('click', closePopover), 10);
    }'''
if old_method in c:
    c = c.replace(old_method, new_method)
    with open('$CHAT_INPUT_CHUNK', 'w') as f: f.write(c)
    print('  File attach: replaced QuickPick with inline popover')
else:
    print('  File attach: already patched or method not found')
"
  gzip -kf "$CHAT_INPUT_CHUNK" 2>/dev/null || true
fi

# ── Modern attachment CSS: drag-and-drop overlay + popover styles ────────────
CSS_BUNDLE="$FRONTEND_DIR/secondary-window.css"
if [ -f "$CSS_BUNDLE" ] && ! grep -q "gcm-attach-popover" "$CSS_BUNDLE"; then
  cat >> "$CSS_BUNDLE" << 'GCMCSS'

/* GoCodeMe: Modern drag-and-drop & attachment popover */
.chat-input-widget.drag-over .theia-ChatInput { position: relative; }
.chat-input-widget.drag-over .theia-ChatInput::before {
  content: '📎 Drop files here to attach'; position: absolute; inset: 0; z-index: 100;
  display: flex; align-items: center; justify-content: center;
  background: rgba(79,70,229,0.12); border: 2px dashed rgba(99,102,241,0.5); border-radius: 8px;
  color: var(--theia-foreground); font-size: 13px; font-weight: 600;
  font-family: var(--theia-ui-font-family); backdrop-filter: blur(4px);
  animation: gcmDropPulse 1.5s ease-in-out infinite; pointer-events: none;
}
@keyframes gcmDropPulse {
  0%,100% { border-color: rgba(99,102,241,0.3); background: rgba(79,70,229,0.08); }
  50% { border-color: rgba(99,102,241,0.6); background: rgba(79,70,229,0.15); }
}
.chat-input-widget.drag-over .theia-ChatInput-Editor-Box {
  border-color: rgba(99,102,241,0.5)!important; box-shadow: 0 0 16px rgba(99,102,241,0.15);
}
.gcm-attach-popover {
  margin: 0 16px; padding: 10px 12px 8px; background: var(--theia-editor-background,#1e1e2e);
  border: 1px solid var(--theia-dropdown-border,rgba(255,255,255,0.1)); border-radius: 10px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.4); animation: gcmAttachIn 0.15s ease-out; z-index: 50;
}
@keyframes gcmAttachIn { from { opacity:0; transform:translateY(6px) scale(0.97); } to { opacity:1; transform:translateY(0) scale(1); } }
.gcm-attach-header { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.6px; color:var(--theia-disabledForeground,rgba(255,255,255,0.35)); margin-bottom:8px; }
.gcm-attach-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(120px,1fr)); gap:6px; margin-bottom:8px; }
.gcm-attach-btn { display:flex; align-items:center; gap:8px; padding:8px 12px; border:1px solid var(--theia-dropdown-border,rgba(255,255,255,0.08)); border-radius:8px; background:rgba(255,255,255,0.03); color:var(--theia-foreground,#e0e0e0); font-size:12.5px; font-weight:500; cursor:pointer; transition:all .15s; text-align:left; }
.gcm-attach-btn:hover { background:rgba(99,102,241,0.12); border-color:rgba(99,102,241,0.35); transform:translateY(-1px); box-shadow:0 2px 8px rgba(0,0,0,0.2); }
.gcm-attach-btn:active { transform:translateY(0); }
.gcm-attach-btn span { white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.gcm-attach-drop-hint { display:flex; align-items:center; justify-content:center; gap:6px; padding:6px; border-top:1px solid var(--theia-dropdown-border,rgba(255,255,255,0.06)); margin-top:2px; color:var(--theia-disabledForeground,rgba(255,255,255,0.3)); font-size:11px; font-style:italic; }
GCMCSS
  gzip -kf "$CSS_BUNDLE" 2>/dev/null || true
  echo "  Attachment CSS: drag-and-drop overlay + popover styles added"
fi

# ── Persistence Block 6: Expose VS Code command API for Alfred Bridge ──────
WEB_FACTORY="$THEIA_DIR/out/vs/workbench/browser/web.factory.js"
if [ -f "$WEB_FACTORY" ] && ! grep -q "__vscodeCommands" "$WEB_FACTORY"; then
  sed -i '/^})(commands || (commands = {}));$/a// GoCodeMe: Expose command service globally for Alfred Bridge
globalThis.__vscodeCommands = commands;' "$WEB_FACTORY"
  echo "  web.factory.js patched: globalThis.__vscodeCommands exposed"
fi

echo "Starting GoCodeMe IDE for user: $DA_USERNAME"
echo "  Workspace: $WORKSPACE_PATH"
echo "  Port: $THEIA_PORT"
echo "  MCP: $MCP_SERVER_URL"
echo "  Sandbox: $SANDBOX_MODULE"
echo "  Shell: ${SHELL}"

# ── Multi-root workspace: open .code-workspace file if available ──────────
# syncWorkspace writes gocodeme.code-workspace with one root per domain,
# so each domain appears as a top-level folder in the explorer instead of
# being buried under domains/.  Fall back to the plain directory if the file
# doesn't exist yet (e.g. first launch before sync finishes).
WORKSPACE_ARG="$WORKSPACE_PATH"
WS_FILE="$WORKSPACE_PATH/gocodeme.code-workspace"
if [[ -f "$WS_FILE" ]]; then
  WORKSPACE_ARG="$WS_FILE"
  echo "  Multi-root workspace: $WS_FILE"
fi

# Use exec so the node process REPLACES bash — this preserves the PID
# that the middleware recorded, allowing killPid() to work correctly.
exec node --require="$SANDBOX_MODULE" "$THEIA_MAIN" "$WORKSPACE_ARG" \
  --hostname=127.0.0.1 \
  --port="$THEIA_PORT" \
  --plugins="local-dir:$THEIA_DIR/plugins" \
  --GOCODEME_DA_USER="$DA_USERNAME" \
  2>&1
