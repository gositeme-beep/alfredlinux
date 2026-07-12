#!/bin/bash
# ============================================================================
# GoCodeMe — Start / Resurrect All Services
# ============================================================================
# USAGE:
#   bash gocodeme-start.sh          # Start all from scratch
#   bash gocodeme-start.sh resurrect # Resurrect from PM2 dump
#   bash gocodeme-start.sh status    # Just check status
#   bash gocodeme-start.sh stop      # Stop all
#
# TIP: Run inside screen/tmux to survive VS Code disconnect:
#   screen -S gocodeme
#   bash gocodeme-start.sh
#   # Ctrl+A, D to detach
# ============================================================================

set -euo pipefail

PM2=/home/gositeme/domains/gocodeme.com/public_html/node_modules/pm2/bin/pm2
BASE=/home/gositeme/domains/gositeme.com/public_html/gocodeme
RCLI="$HOME/redis-7.2.4/src/redis-cli"
RED='\033[0;31m'; GRN='\033[0;32m'; YEL='\033[1;33m'; NC='\033[0m'

ok()   { echo -e "  ${GRN}✅ $1${NC}"; }
fail() { echo -e "  ${RED}❌ $1${NC}"; }
warn() { echo -e "  ${YEL}⚠️  $1${NC}"; }

health_check() {
    echo ""
    echo "=== Health Check ==="
    
    # Redis
    if $RCLI ping 2>/dev/null | grep -q PONG; then ok "Redis (6379) — PONG"
    else fail "Redis (6379) — DOWN"; fi
    
    # Middleware
    HTTP=$(curl -s -o /dev/null -w "%{http_code}" --max-time 3 http://127.0.0.1:3001/health 2>/dev/null || echo "000")
    if [ "$HTTP" = "200" ]; then ok "Middleware (3001) — HTTP $HTTP"
    else fail "Middleware (3001) — HTTP $HTTP"; fi
    
    # MCP
    HTTP=$(curl -s -o /dev/null -w "%{http_code}" --max-time 3 http://127.0.0.1:3005/mcp/health 2>/dev/null || echo "000")
    if [ "$HTTP" = "200" ]; then ok "MCP Server (3005) — HTTP $HTTP"
    else fail "MCP Server (3005) — HTTP $HTTP"; fi
    
    # OpenClaw
    HTTP=$(curl -s -o /dev/null -w "%{http_code}" --max-time 3 http://127.0.0.1:3004/health 2>/dev/null || echo "000")
    if [ "$HTTP" = "200" ]; then ok "OpenClaw (3004) — HTTP $HTTP"
    else fail "OpenClaw (3004) — HTTP $HTTP"; fi
    
    # External proxy
    HTTP=$(curl -s -o /dev/null -w "%{http_code}" --max-time 5 https://gositeme.com/middleware/health 2>/dev/null || echo "000")
    if [ "$HTTP" = "200" ]; then ok "External proxy (gositeme.com/middleware/) — HTTP $HTTP"
    else warn "External proxy — HTTP $HTTP (Apache may need time)"; fi
    
    echo ""
    $PM2 list
}

do_stop() {
    echo "=== Stopping All Services ==="
    $PM2 stop all 2>/dev/null || true
    echo "Stopped. PM2 daemon still alive (use '$PM2 kill' to fully remove)."
}

do_resurrect() {
    echo "=== Resurrecting from saved PM2 dump ==="
    $PM2 ping > /dev/null 2>&1 || $PM2 resurrect
    $PM2 resurrect 2>/dev/null || {
        warn "Resurrect failed — falling back to fresh start"
        do_start
        return
    }
    sleep 3
    health_check
}

do_start() {
    echo "=== Starting GoCodeMe Services ==="
    echo ""
    
    # Kill any existing PM2 processes to avoid duplicates
    $PM2 delete all 2>/dev/null || true
    
    # 1. Redis
    echo "[1/5] Starting Redis..."
    $PM2 start "$HOME/redis-7.2.4/src/redis-server" \
        --name redis \
        --interpreter none \
        -- "$HOME/redis-7.2.4/redis.conf"
    sleep 2
    
    if $RCLI ping 2>/dev/null | grep -q PONG; then
        ok "Redis started"
    else
        fail "Redis failed to start!"
        $PM2 logs redis --lines 10 --nostream
        exit 1
    fi
    
    # 2. Middleware
    echo "[2/5] Starting Middleware..."
    $PM2 start "$BASE/middleware/src/server.js" \
        --name gocodeme-middleware \
        --cwd "$BASE/middleware"
    sleep 2
    
    # 3. MCP Server
    echo "[3/5] Starting MCP Server..."
    $PM2 start "$BASE/mcp-server/src/mcpHttpServer.js" \
        --name mcp-server \
        --cwd "$BASE/mcp-server"
    
    # 4. OpenClaw
    echo "[4/5] Starting OpenClaw..."
    $PM2 start "$BASE/openclaw/src/server.js" \
        --name openclaw \
        --cwd "$BASE/openclaw"
    
    # 5. Scheduler
    echo "[5/5] Starting Scheduler..."
    $PM2 start "$BASE/scripts/scheduler.js" \
        --name gocodeme-scheduler \
        --cwd "$BASE/scripts" \
        --cron-restart "0 3 * * *"
    
    # Save for future resurrect
    sleep 3
    $PM2 save
    ok "Process list saved to ~/.pm2/dump.pm2"
    
    health_check
}

# ── Main ──────────────────────────────────────────────────────────────────
case "${1:-start}" in
    start)     do_start ;;
    resurrect) do_resurrect ;;
    status)    health_check ;;
    stop)      do_stop ;;
    *)
        echo "Usage: $0 {start|resurrect|status|stop}"
        exit 1
        ;;
esac
