# DASHBOARD TRACKING RULE

The real-time dashboard tracks ANY Docker container that:
1. Starts with the alfred- prefix
2. Mounts the /home/gositeme/law/alfredlinux-com-source-live (or /work) directory.

DO NOT spawn auxiliary containers (for git sync, patching, etc) that meet these criteria while a main build (like alfred-lb-v-fast34) is running, or it will hijack the dashboard view.
