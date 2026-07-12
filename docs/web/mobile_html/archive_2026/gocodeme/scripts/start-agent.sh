#!/usr/bin/env bash
# GoCodeMe Agent — Per-customer OpenHands launch script
# Usage: ./start-agent.sh <da_username> <workspace_path> <port> [jwt_token]
#
# Example:
#   ./start-agent.sh johndoe /home/johndoe/domains/johndoe.com/public_html 3003
#
# The JWT token is optional — if omitted the agent starts without MCP auth
# (useful for local testing). In production, always pass the JWT.

set -euo pipefail

DA_USERNAME="${1:-}"
WORKSPACE_PATH="${2:-}"
AGENT_PORT="${3:-4001}"
JWT_TOKEN="${4:-}"

if [[ -z "$DA_USERNAME" || -z "$WORKSPACE_PATH" ]]; then
    echo "Usage: $0 <da_username> <workspace_path> [port] [jwt_token]"
    exit 1
fi

BASE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OPENHANDS_DIR="$BASE_DIR/openhands-fork"
ENV_FILE="$BASE_DIR/middleware/.env"

# Load environment variables (exclude PORT to avoid overriding AGENT_PORT)
if [[ -f "$ENV_FILE" ]]; then
    set -a
    source <(grep -v '^PORT=' "$ENV_FILE")
    set +a
else
    echo "ERROR: $ENV_FILE not found"
    exit 1
fi

# Validate Anthropic key
if [[ -z "${ANTHROPIC_API_KEY:-}" || "$ANTHROPIC_API_KEY" == "YOUR_ANTHROPIC_API_KEY_HERE" ]]; then
    echo "ERROR: ANTHROPIC_API_KEY not set in $ENV_FILE"
    exit 1
fi

# Activate pyenv Python 3.12
export PYENV_ROOT="$HOME/.pyenv"
export PATH="$PYENV_ROOT/bin:$PATH"
eval "$(pyenv init - bash)"
pyenv global 3.12.9

# Export for OpenHands config.toml substitution
export ANTHROPIC_API_KEY
export GOCODEME_JWT_TOKEN="${JWT_TOKEN}"
export OPENHANDS_WORKSPACE_BASE="$WORKSPACE_PATH"

# Create workspace dir if needed (use temp dir if path not writable)
if ! mkdir -p "$WORKSPACE_PATH" 2>/dev/null; then
    echo "WARNING: Cannot create $WORKSPACE_PATH — using temp workspace"
    WORKSPACE_PATH="/tmp/gocodeme-workspace-${DA_USERNAME}"
    mkdir -p "$WORKSPACE_PATH"
fi

MCP_DIR="$BASE_DIR/mcp-server"
MCP_PORT="${MCP_PORT:-3005}"

# ── Ensure MCP server is running ─────────────────────────────────────────────
MCP_PID_FILE="$BASE_DIR/logs/mcp-server.pid"

start_mcp() {
    echo "Starting MCP server on port $MCP_PORT..."
    mkdir -p "$BASE_DIR/logs"
    nohup bash "$MCP_DIR/start.sh" \
        > "$BASE_DIR/logs/mcp-server.log" 2>&1 &
    echo $! > "$MCP_PID_FILE"
    # Wait up to 5s for port to open
    for i in $(seq 1 10); do
        sleep 0.5
        if ss -tlnp | grep -q ":${MCP_PORT}"; then
            echo "  MCP server ready (PID $(cat "$MCP_PID_FILE"))"
            return 0
        fi
    done
    echo "WARNING: MCP server did not come up on port $MCP_PORT within 5s"
}

if ss -tlnp | grep -q ":${MCP_PORT}"; then
    echo "  MCP server already running on port $MCP_PORT"
else
    start_mcp
fi

echo "Starting GoCodeMe Agent for user: $DA_USERNAME"
echo "  Workspace : $WORKSPACE_PATH"
echo "  Port      : $AGENT_PORT"
echo "  MCP port  : $MCP_PORT"
echo "  MCP JWT   : ${JWT_TOKEN:0:10}..."

cd "$OPENHANDS_DIR"

# Start the OpenHands backend server
# NOTE: __main__.py reads port from lowercase env var 'port', not CLI args
export OPENHANDS_CONFIG_FILE="$OPENHANDS_DIR/config.toml"
export port="$AGENT_PORT"

poetry run python -m openhands.server \
    2>&1 | tee "$BASE_DIR/logs/agent-${DA_USERNAME}.log"
