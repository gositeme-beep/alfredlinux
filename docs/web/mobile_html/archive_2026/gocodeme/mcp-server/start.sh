#!/bin/bash
cd /home/gositeme/domains/gositeme.com/public_html/gocodeme/mcp-server
set -a
source /home/gositeme/domains/gositeme.com/public_html/gocodeme/middleware/.env
set +a
exec node src/mcpHttpServer.js
