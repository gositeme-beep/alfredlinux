#!/bin/bash
# Quick restart script for GoCodeMe middleware + cleanup
RCLI=/home/gositeme/redis-7.2.4/src/redis-cli
PM2=/home/gositeme/domains/gocodeme.com/public_html/node_modules/pm2/bin/pm2

echo "=== Killing Theia processes ==="
pkill -f "main.js /tmp/gocodeme-workspace" 2>/dev/null
sleep 1
fuser -k 4000/tcp 4001/tcp 4004/tcp 4005/tcp 2>/dev/null

echo "=== Clearing Redis sessions ==="
$RCLI DEL "launch:sessions:jabelaqu" "launch:sessions:jabela" "workspace:remote_path:jabelaqu" 2>/dev/null

echo "=== Removing old workspaces ==="
rm -rf /tmp/gocodeme-workspace-jabelaqu /tmp/gocodeme-workspace-jabela

echo "=== Restarting middleware ==="
$PM2 restart gocodeme-middleware 2>&1

echo "=== Waiting for startup ==="
sleep 3

echo "=== Recent sync logs ==="
grep -i "sync\|launch.*refresh" /home/gositeme/.pm2/logs/gocodeme-middleware-out.log | tail -5

echo "=== DONE ==="
