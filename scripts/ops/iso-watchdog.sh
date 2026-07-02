#!/bin/bash
# iso-watchdog.sh — monitor ISO build container, alert on death/wedge
# Runs as a one-shot probe (cron-friendly). Exit 0 = healthy, 1 = problem.
set -uo pipefail

LOG=/home/root/law/iso-watchdog.log
STATE=/home/root/law/iso-watchdog.state
ts() { date '+%Y-%m-%d %H:%M:%S'; }
log() { echo "[$(ts)] $*" | tee -a "$LOG"; }

# Detect the live-build container — match by name OR by command (covers cases where
# lb-docker-build.sh didn't pass --name and Docker assigned a random name like naughty_nobel).
CONTAINER=$(docker ps --filter name=alfred-lb --format '{{.Names}}' 2>/dev/null | head -1)
if [[ -z "$CONTAINER" ]]; then
    CONTAINER=$(docker ps --no-trunc --format '{{.Names}}\t{{.Command}}' 2>/dev/null | grep -E 'lb-docker-inner-build|live-build|/usr/lib/live/build' | head -1 | awk '{print $1}')
fi

if [[ -z "$CONTAINER" ]]; then
    # No live container — check if ISO finished
    LATEST=$(ls -t /home/root/law/alfredlinux-com-source-live/iso-output/*.iso 2>/dev/null | head -1)
    if [[ -n "$LATEST" ]]; then
        AGE=$(( $(date +%s) - $(stat -c%Y "$LATEST") ))
        if [[ "$AGE" -lt 600 ]]; then
            SIZE=$(stat -c%s "$LATEST")
            log "BUILD COMPLETE: $(basename "$LATEST") $((SIZE/1024/1024)) MiB"
            log "  → invoking iso-publish.sh"
            if [[ -x /home/root/iso-publish.sh ]]; then
                /home/root/iso-publish.sh 2>&1 | tee -a "$LOG"
            else
                log "  WARN: /home/root/iso-publish.sh missing"
            fi
            exit 0
        fi
    fi
    if [[ -f "$STATE" ]] && grep -q "^running" "$STATE"; then
        log "ALERT: container vanished, state was 'running' — BUILD CRASHED"
        echo "stopped" > "$STATE"
        exit 1
    fi
    log "no build container running, no recent ISO — idle"
    echo "idle" > "$STATE"
    exit 0
fi

# Container exists — check health
echo "running:$CONTAINER" > "$STATE"
SIZE_NOW=$(docker exec "$CONTAINER" du -s /tmp/build/binary 2>/dev/null | awk '{print $1}')
SIZE_NOW=${SIZE_NOW:-0}
PREV=$(grep "^lastsize:" "$STATE" 2>/dev/null | tail -1 | cut -d: -f2)
PREV=${PREV:-0}
echo "lastsize:$SIZE_NOW" >> "$STATE"

LAST_LOG=$(docker logs --tail 1 "$CONTAINER" 2>&1 | head -1 | cut -c1-100)
log "$CONTAINER — binary=$((SIZE_NOW/1024)) MiB, last: $LAST_LOG"

# Wedge detection: if size hasn't changed in 30 min AND log line unchanged
if [[ "$SIZE_NOW" -eq "$PREV" && "$SIZE_NOW" -gt 0 ]]; then
    WEDGE_COUNT=$(grep -c "^wedge:" "$STATE" 2>/dev/null || echo 0)
    WEDGE_COUNT=$((WEDGE_COUNT + 1))
    echo "wedge:$WEDGE_COUNT" >> "$STATE"
    if [[ "$WEDGE_COUNT" -ge 3 ]]; then
        log "ALERT: WEDGED — no progress in 3 consecutive checks"
        exit 1
    fi
else
    sed -i '/^wedge:/d' "$STATE" 2>/dev/null || true
fi

exit 0
