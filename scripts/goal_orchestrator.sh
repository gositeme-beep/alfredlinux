#!/bin/bash
set -e

echo "============================================="
echo "  ALFRED LINUX — ULTIMATE GOAL ORCHESTRATOR"
echo "============================================="

SOURCE="/home/gositeme/law/alfredlinux-com-source-live"
RSYNC_LOG="$SOURCE/build/rsync-v2.log"
BUILD_LOG="$SOURCE/build/lb-docker-build.log"

echo "[1/4] Waiting for rsync to complete (BASH_CONFIRMED)..."
while true; do
    if grep -q "BASH_CONFIRMED" "$RSYNC_LOG" 2>/dev/null; then
        echo "✅ BASH_CONFIRMED found! Rsync is complete."
        break
    fi
    sleep 30
done

echo ""
echo "[2/4] Starting the fast build..."
cd "$SOURCE"
bash scripts/alfred-build.sh fast detach
sleep 10
# The container name should be in lb-docker.containername
CONTAINER_NAME=$(cat "$SOURCE/lb-docker.containername" 2>/dev/null || echo "alfred-lb-v-fast")
echo "Build started in container: $CONTAINER_NAME"

echo ""
echo "[3/4] Waiting for build container to exit..."
while docker ps --format '{{.Names}}' | grep -q "^${CONTAINER_NAME}$"; do
    sleep 60
done
echo "✅ Build container exited."

echo ""
echo "[4/4] Running post-build pipeline..."
if [ -f "$SOURCE/scripts/post_build_pipeline.sh" ]; then
    bash "$SOURCE/scripts/post_build_pipeline.sh" > "$SOURCE/build/post-build-pipeline.log" 2>&1
    echo "✅ Post-build pipeline executed."
else
    echo "❌ ERROR: post_build_pipeline.sh not found!"
fi

echo "============================================="
echo "  🎉 ALL GOALS ACHIEVED."
echo "============================================="
