#!/bin/bash
LOG_FILE="/home/gositeme/law/alfredlinux-com-source-live/build/lb-docker-build.log"
tail -n +1 -f "$LOG_FILE" | while read -r line; do
  if [[ "$line" == *"lb build finished at"* ]]; then
    echo "BUILD FINISHED: $line"
    exit 0
  fi
  if [[ "$line" == *"E: An unexpected failure"* ]] || [[ "$line" == *"Build failed with exit"* ]]; then
    echo "BUILD FAILED: $line"
    exit 1
  fi
done
