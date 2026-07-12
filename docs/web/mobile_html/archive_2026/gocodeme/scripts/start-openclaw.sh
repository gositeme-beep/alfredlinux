#!/usr/bin/env bash
# start-openclaw.sh — Launch the OpenClaw messaging gateway in a tmux session
# Usage:
#   ./scripts/start-openclaw.sh          — start (or restart) openclaw in tmux
#   ./scripts/start-openclaw.sh stop     — kill the session
#   ./scripts/start-openclaw.sh status   — show running port / process

set -euo pipefail

SESSION="openclaw"
BASE_DIR="$(cd "$(dirname "$0")/.." && pwd)"
OPENCLAW_DIR="$BASE_DIR/openclaw"
OPENCLAW_PORT="${OPENCLAW_PORT:-3004}"

# ── Colours ────────────────────────────────────────────────────────────────────
G='\033[0;32m'; Y='\033[1;33m'; R='\033[0;31m'; NC='\033[0m'

status() {
  if ss -tlnp 2>/dev/null | grep -q ":${OPENCLAW_PORT}"; then
    echo -e "${G}✓ OpenClaw is RUNNING on port ${OPENCLAW_PORT}${NC}"
    pid=$(ss -tlnp 2>/dev/null | grep ":${OPENCLAW_PORT}" | grep -oP 'pid=\K[0-9]+' | head -1 || true)
    [[ -n "$pid" ]] && echo "  PID: $pid"
    echo "  Health: $(curl -sf http://localhost:${OPENCLAW_PORT}/health 2>/dev/null || echo '(unreachable)')"
    return 0
  else
    echo -e "${Y}✗ OpenClaw is NOT running on port ${OPENCLAW_PORT}${NC}"
    return 1
  fi
}

case "${1:-start}" in
  stop)
    if tmux has-session -t "$SESSION" 2>/dev/null; then
      tmux kill-session -t "$SESSION"
      echo -e "${G}Stopped tmux session '$SESSION'${NC}"
    else
      echo "No tmux session '$SESSION' found"
    fi
    exit 0
    ;;
  status)
    status; exit $?
    ;;
esac

# ── Start ──────────────────────────────────────────────────────────────────────
echo -e "${Y}Starting OpenClaw on port ${OPENCLAW_PORT}...${NC}"

if [[ ! -d "$OPENCLAW_DIR" ]]; then
  echo -e "${R}ERROR: $OPENCLAW_DIR does not exist${NC}"
  exit 1
fi

if [[ ! -f "$OPENCLAW_DIR/src/server.js" ]]; then
  echo -e "${R}ERROR: $OPENCLAW_DIR/src/server.js not found${NC}"
  exit 1
fi

# Kill existing session
if tmux has-session -t "$SESSION" 2>/dev/null; then
  echo "Killing existing tmux session '$SESSION'..."
  tmux kill-session -t "$SESSION"
  sleep 1
fi

# Start new detached session
tmux new-session -d -s "$SESSION" \
  "cd $OPENCLAW_DIR && node src/server.js 2>&1 | tee /tmp/openclaw.log"

echo "Waiting for port ${OPENCLAW_PORT}..."
for i in $(seq 1 15); do
  if ss -tlnp 2>/dev/null | grep -q ":${OPENCLAW_PORT}"; then
    echo -e "${G}✓ OpenClaw is UP (${i}s)${NC}"
    echo "  Health: $(curl -sf http://localhost:${OPENCLAW_PORT}/health 2>/dev/null)"
    echo "  Logs:   tail -f /tmp/openclaw.log"
    echo "  Tmux:   tmux attach -t $SESSION"
    exit 0
  fi
  sleep 1
done

echo -e "${R}OpenClaw did not start in 15s — check logs:${NC}"
echo "  tail -50 /tmp/openclaw.log"
cat /tmp/openclaw.log 2>/dev/null | tail -20 || true
exit 1
