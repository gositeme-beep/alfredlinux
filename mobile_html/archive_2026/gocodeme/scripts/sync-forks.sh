#!/bin/bash
# GoCodeMe — Sync forks with upstream and rebase our customizations on top.
#
# Usage:  ./sync-forks.sh [theia|openhands|all]
#
# What it does:
#   1. Fetches latest from upstream (eclipse-theia / All-Hands-AI)
#   2. Rebases our 'gocodeme' branch on top of the new upstream
#   3. If conflicts arise, stops and tells you what to fix
#
# After a successful sync you still need to rebuild:
#   theia-fork:     yarn && yarn browser build
#   openhands-fork: cd frontend && npm install && npm run build

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

sync_repo() {
  local NAME="$1"
  local DIR="$2"
  local UPSTREAM_BRANCH="$3"

  echo -e "\n${YELLOW}━━━ Syncing $NAME ━━━${NC}"

  if [ ! -d "$DIR/.git" ]; then
    echo -e "${RED}ERROR: $DIR is not a git repo${NC}"
    return 1
  fi

  cd "$DIR"

  # Safety: make sure we're on the gocodeme branch
  CURRENT=$(git branch --show-current)
  if [ "$CURRENT" != "gocodeme" ]; then
    echo -e "${RED}ERROR: expected branch 'gocodeme', found '$CURRENT'. Switch first.${NC}"
    return 1
  fi

  # Safety: no uncommitted changes
  if ! git diff --quiet || ! git diff --cached --quiet; then
    echo -e "${RED}ERROR: uncommitted changes in $NAME. Commit or stash first.${NC}"
    return 1
  fi

  # Fetch latest upstream
  echo "  Fetching upstream..."
  git fetch upstream

  # Show what's new
  LOCAL=$(git rev-parse HEAD)
  UPSTREAM=$(git rev-parse "upstream/$UPSTREAM_BRANCH")
  AHEAD=$(git rev-list --count "upstream/$UPSTREAM_BRANCH..gocodeme")
  BEHIND=$(git rev-list --count "gocodeme..upstream/$UPSTREAM_BRANCH")

  echo "  Local commits ahead of upstream:  $AHEAD (our customizations)"
  echo "  Upstream commits we're missing:   $BEHIND"

  if [ "$BEHIND" -eq 0 ]; then
    echo -e "  ${GREEN}Already up to date.${NC}"
    return 0
  fi

  # Rebase our gocodeme commits on top of new upstream
  echo "  Rebasing gocodeme on upstream/$UPSTREAM_BRANCH..."
  if git rebase "upstream/$UPSTREAM_BRANCH"; then
    echo -e "  ${GREEN}Rebase successful. $BEHIND new upstream commits integrated.${NC}"
    echo -e "  ${YELLOW}Remember to rebuild: see script header for commands.${NC}"
  else
    echo -e "\n${RED}CONFLICT during rebase of $NAME.${NC}"
    echo "  Fix conflicts, then:"
    echo "    cd $DIR"
    echo "    # edit the conflicting files"
    echo "    git add <fixed-files>"
    echo "    git rebase --continue"
    echo ""
    echo "  Or abort with:  git rebase --abort"
    return 1
  fi
}

TARGET="${1:-all}"

case "$TARGET" in
  theia)
    sync_repo "Theia IDE" "$PROJECT_ROOT/theia-fork" "master"
    ;;
  openhands)
    sync_repo "OpenHands" "$PROJECT_ROOT/openhands-fork" "main"
    ;;
  all)
    sync_repo "Theia IDE" "$PROJECT_ROOT/theia-fork" "master"
    sync_repo "OpenHands" "$PROJECT_ROOT/openhands-fork" "main"
    ;;
  *)
    echo "Usage: $0 [theia|openhands|all]"
    exit 1
    ;;
esac

echo -e "\n${GREEN}Done.${NC}"
