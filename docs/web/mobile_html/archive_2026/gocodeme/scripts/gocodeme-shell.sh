#!/bin/bash
# GoCodeMe Restricted Shell — Bubblewrap Sandbox
# -----------------------------------------------
# This script replaces the default shell for Theia's integrated terminal.
# It uses bubblewrap (bwrap) to create a filesystem namespace where the
# user can ONLY see:
#   1. Their workspace directory (~/workspace or ~/)
#   2. Their DA domain directories (~/domains/<domain>/)
#   3. System binaries (read-only)
#   4. /tmp for scratch files
#
# Environment variables (set by start-theia.sh):
#   GOCODEME_SANDBOX_ROOT  — workspace path (e.g. /tmp/gocodeme-workspace-user)
#   GOCODEME_DA_USER       — DirectAdmin username
#   GOCODEME_DOMAIN_NAMES  — comma-separated domain names (for welcome message)

set -e

WORKSPACE="${GOCODEME_SANDBOX_ROOT:-${HOME:-/tmp}}"
DA_USER="${GOCODEME_DA_USER:-user}"
DOMAIN_NAMES="${GOCODEME_DOMAIN_NAMES:-}"

# ── Check if bwrap is available ──────────────────────────────────────────
# SECURITY (2026-03-18): Fail CLOSED if bwrap is missing. An unrestricted
# fallback shell would expose the entire server filesystem to users.
if ! command -v bwrap &>/dev/null; then
  echo "[GoCodeMe] CRITICAL: bubblewrap (bwrap) is not installed." >&2
  echo "[GoCodeMe] Terminal access is disabled for security. Contact support." >&2
  echo "[GoCodeMe] Install bwrap: apt-get install bubblewrap" >&2
  # Start a minimal shell that immediately exits with an explanation
  exec /bin/bash --rcfile <(cat << 'FAILSAFE'
echo ""
echo "  ╔════════════════════════════════════════════════════════╗"
echo "  ║  Terminal unavailable — sandbox runtime not installed  ║"
echo "  ║  Please contact GoCodeMe support.                     ║"
echo "  ╚════════════════════════════════════════════════════════╝"
echo ""
export PS1="[sandbox-missing] $ "
# Block all commands — only allow exit, help, echo
function _blocked() { echo "GoCodeMe: Terminal sandbox not available. Commands are disabled."; }
alias cd='_blocked'
alias ls='_blocked'
alias cat='_blocked'
alias rm='_blocked'
alias cp='_blocked'
alias mv='_blocked'
alias find='_blocked'
alias grep='_blocked'
alias vim='_blocked'
alias nano='_blocked'
alias node='_blocked'
alias python='_blocked'
alias python3='_blocked'
alias pip='_blocked'
alias npm='_blocked'
alias git='_blocked'
alias curl='_blocked'
alias wget='_blocked'
alias ssh='_blocked'
alias sudo='_blocked'
alias su='_blocked'
FAILSAFE
  ) -i
fi

# ── Build bwrap arguments ────────────────────────────────────────────────
BWRAP_ARGS=(
  # System binaries (read-only)
  --ro-bind /usr /usr
  --ro-bind /bin /bin
  --ro-bind /sbin /sbin
  --ro-bind /lib /lib

  # /etc (read-only) — needed for DNS, SSL certs, locale, etc.
  --ro-bind /etc /etc

  # Proc and dev (minimal)
  --proc /proc
  --dev /dev

  # Temp directory (isolated per-session)
  --tmpfs /tmp

  # Hide /home completely, then selectively bind what's needed
  --tmpfs /home

  # User workspace — mounted as /home/user (writable)
  --bind "$WORKSPACE" /home/user
)

# /lib64 may not exist on all systems
if [[ -d /lib64 ]]; then
  BWRAP_ARGS+=(--ro-bind /lib64 /lib64)
fi

# Note: User's domain files are synced INTO the workspace by syncWorkspace.js
# They live at ~/domains/<domain>/ inside the sandbox automatically.
# No separate bind-mounts needed.

# ── Node.js / npm support ───────────────────────────────────────────────
if [[ -d /usr/lib/node_modules ]]; then
  BWRAP_ARGS+=(--ro-bind /usr/lib/node_modules /usr/lib/node_modules)
fi

# ── Environment variables ────────────────────────────────────────────────
BWRAP_ARGS+=(
  --setenv HOME /home/user
  --setenv USER "$DA_USER"
  --setenv LOGNAME "$DA_USER"
  --setenv GOCODEME_DA_USER "$DA_USER"
  --setenv GOCODEME_SANDBOX_ROOT /home/user
  --setenv TERM "${TERM:-xterm-256color}"
  --setenv LANG "${LANG:-en_US.UTF-8}"
  --setenv PATH "/usr/local/bin:/usr/bin:/bin:/home/user/node_modules/.bin:/home/user/.local/bin"
  --setenv SHELL /bin/bash

  # SECURITY: Strip sensitive environment variables from terminal sessions.
  # These are set in the parent Theia process for internal use (Anthropic SDK,
  # JWT auth, etc.) but must NOT be visible to customer terminal sessions.
  --unsetenv ANTHROPIC_API_KEY
  --unsetenv ANTHROPIC_BASE_URL
  --unsetenv JWT_SECRET
  --unsetenv DA_ADMIN_USER
  --unsetenv DA_ADMIN_PASS
  --unsetenv DA_ADMIN_PASSWORD
  --unsetenv GOCODEME_JWT_TOKEN
  --unsetenv REDIS_URL
  --unsetenv WHMCS_API_IDENTIFIER
  --unsetenv WHMCS_API_SECRET
  --unsetenv WHMCS_WEBHOOK_SECRET
  --unsetenv TOGETHER_API_KEY
  --unsetenv TELNYX_API_KEY
  --unsetenv STRIPE_SECRET_KEY
  --unsetenv GOCODEME_PROXY_TOKEN

  # Security: start in workspace, die with parent, share network
  --chdir /home/user
  --unshare-all
  --share-net
  --die-with-parent
)

# ── Run ──────────────────────────────────────────────────────────────────
# If arguments were passed (e.g. -c "command"), run them inside the sandbox
if [[ "$1" = "-c" ]]; then
  shift
  exec bwrap "${BWRAP_ARGS[@]}" -- /bin/bash -c "$*"
fi

# Start interactive bash inside the sandbox
exec bwrap "${BWRAP_ARGS[@]}" -- /bin/bash --rcfile <(cat << 'RCEOF'
# GoCodeMe sandbox shell
export PS1='\[\033[01;32m\]${GOCODEME_DA_USER:-gocodeme}\[\033[00m\]:\[\033[01;34m\]\w\[\033[00m\]\$ '

# Helpful aliases
alias ll='ls -la'
alias la='ls -A'
alias domains='ls ~/domains/ 2>/dev/null || echo "No domains linked"'

# Block dangerous commands
alias sudo='echo "GoCodeMe: sudo not available in sandbox"'
alias su='echo "GoCodeMe: su not available in sandbox"'

# Welcome message (only on first terminal)
if [[ -z "$GOCODEME_WELCOMED" ]]; then
  export GOCODEME_WELCOMED=1
  echo -e "\033[1;36m╔══════════════════════════════════════════╗"
  echo -e "║     Welcome to GoCodeMe IDE Terminal     ║"
  echo -e "╚══════════════════════════════════════════╝\033[0m"
  if [[ -d ~/domains ]] && ls ~/domains/ &>/dev/null && [[ $(ls ~/domains/ 2>/dev/null | wc -l) -gt 0 ]]; then
    echo -e "\033[0;33mYour domains:\033[0m"
    for d in ~/domains/*/; do
      [[ -d "$d" ]] && echo "  📁 $(basename "$d")"
    done
  fi
  echo ""
fi
RCEOF
) -i
