#!/bin/bash
##############################################################################
# BUILD COMPLETION WATCHER
# Watches for build-complete.marker and auto-triggers finalize-iso.sh
# Run in tmux: tmux new-session -d -s iso-watcher 'bash /home/gositeme/law/alfredlinux-com-source-live/scripts/watch-and-finalize.sh'
##############################################################################

MARKER="/home/gositeme/law/alfredlinux-com-source-live/iso-output/build-complete.marker"
FINALIZE="/home/gositeme/law/alfredlinux-com-source-live/scripts/finalize-iso.sh"
LOG="/home/gositeme/alfred-watcher.log"

echo "[watcher] Started at $(date)" | tee -a "$LOG"
echo "[watcher] Watching for: $MARKER" | tee -a "$LOG"
echo "[watcher] Will auto-run: $FINALIZE" | tee -a "$LOG"

while true; do
    if [ -f "$MARKER" ]; then
        echo "[watcher] =============================" | tee -a "$LOG"
        echo "[watcher] BUILD COMPLETE MARKER FOUND!" | tee -a "$LOG"
        echo "[watcher] Detected at $(date)" | tee -a "$LOG"
        echo "[watcher] =============================" | tee -a "$LOG"

        # Wait 10 seconds to make sure ISO write is fully flushed
        sleep 10

        echo "[watcher] Launching finalize-iso.sh..." | tee -a "$LOG"
        bash "$FINALIZE" 2>&1 | tee -a "$LOG"
        EXIT_CODE=$?

        echo "[watcher] finalize-iso.sh exited with code: $EXIT_CODE" | tee -a "$LOG"

        if [ $EXIT_CODE -eq 0 ]; then
            echo "[watcher] ✅ FINALIZATION COMPLETE — ISO IS READY!" | tee -a "$LOG"
        else
            echo "[watcher] ⚠️ FINALIZATION HAD ERRORS — check log" | tee -a "$LOG"
        fi

        # Remove marker so we don't re-trigger
# DISABLED-BY-COMMANDER: rm -f "$MARKER"
        echo "[watcher] Marker removed. Watcher exiting." | tee -a "$LOG"
        exit $EXIT_CODE
    fi

    # Check every 30 seconds
    sleep 30
done
