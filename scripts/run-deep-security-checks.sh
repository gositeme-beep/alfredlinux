#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# run-deep-security-checks.sh — release-integrity + security-audit (full shellcheck) + law audit.
#
# Same as alfred-repo-health with ALFRED_REPO_HEALTH_SHELLCHECK_ALL=1 (slower than CI default).
# Usage (repo root):
#   bash scripts/run-deep-security-checks.sh
#   ALFRED_LINUX_REPO=/path/to/checkout bash scripts/run-deep-security-checks.sh
#
# See: scripts/README.txt (alfred-repo-health, security-audit).
set -euo pipefail
exec env ALFRED_REPO_HEALTH_SHELLCHECK_ALL=1 bash "$(cd "$(dirname "$0")" && pwd)/alfred-repo-health.sh"
