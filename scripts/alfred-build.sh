#!/usr/bin/env bash
# =========================================================================
# alfred-build.sh — THE ONE AND ONLY Alfred Linux Build Script
# =========================================================================
# SPDX-License-Identifier: AGPL-3.0-or-later
#
# Usage (run from source root on the HOST):
#   bash scripts/alfred-build.sh full          # Full lb build (multi-hour)
#   bash scripts/alfred-build.sh fast          # Fast binary repack (~30-60 min)
#   bash scripts/alfred-build.sh full detach   # Full build, detached
#   bash scripts/alfred-build.sh fast detach   # Fast build, detached
#   bash scripts/alfred-build.sh status        # Check running build
#   bash scripts/alfred-build.sh clean-locks   # Just nuke all locks
#
# =========================================================================
# 🛑 CRITICAL: "full" WILL WIPE .build/ MARKERS AND RE-BOOTSTRAP.
#              "fast" preserves the golden cache and only repacks binary.
# =========================================================================
set -euo pipefail

# ── Resolve paths ──────────────────────────────────────────────────────────
REPO="$(cd "$(dirname "$0")/.." && pwd)"
cd "$REPO"

MODE="${1:-help}"
DETACH="${2:-}"
IMAGE="${DOCKER_LB_IMAGE:-debian:trixie}"
TIMESTAMP="$(date +%s)"
CONTAINER_NAME="alfred-lb-v-${MODE}-${TIMESTAMP}"
LOG_FILE="$REPO/build/lb-docker-build.log"

# ── Environment ───────────────────────────────────────────────────────────
export ALFRED_LB_DOCKER_FLOCK_BLOCKING="${ALFRED_LB_DOCKER_FLOCK_BLOCKING:-1}"
export ALFRED_ALLOW_SSH_PASSWORD_AUTH="${ALFRED_ALLOW_SSH_PASSWORD_AUTH:-0}"

# ── Functions ─────────────────────────────────────────────────────────────

usage() {
    cat <<'EOF'
╔══════════════════════════════════════════════════════════════╗
║              ALFRED LINUX — MASTER BUILD SCRIPT             ║
╠══════════════════════════════════════════════════════════════╣
║                                                              ║
║  Usage:                                                      ║
║    bash scripts/alfred-build.sh full          Full build     ║
║    bash scripts/alfred-build.sh fast          Fast repack    ║
║    bash scripts/alfred-build.sh full detach   Detached full  ║
║    bash scripts/alfred-build.sh fast detach   Detached fast  ║
║    bash scripts/alfred-build.sh status        Check status   ║
║    bash scripts/alfred-build.sh clean-locks   Clear locks    ║
║                                                              ║
║  "full"  = lb bootstrap + lb chroot + lb binary (HOURS)     ║
║  "fast"  = rsync cache → chroot, lb binary only (30-60min)  ║
║                                                              ║
╚══════════════════════════════════════════════════════════════╝
EOF
    exit 0
}

nuke_all_locks() {
    echo "[alfred-build] Nuking ALL lock files..."
    rm -f "$REPO/.lock" \
          "$REPO/build/.lock" \
          "$REPO/build/.alfred-lb-docker-build.lock"
    find "$REPO/build" -maxdepth 1 -name "*.lock" -delete 2>/dev/null || true
    echo "[alfred-build] All host-side locks cleared."
}

check_prereqs() {
    echo "[alfred-build] ═══ Pre-flight checks ═══"

    # 1. Docker running?
    if ! docker info &>/dev/null; then
        echo "FATAL: Docker is not running." >&2
        exit 1
    fi
    echo "  ✅ Docker daemon running"

    # 2. apt-cacher-ng alive?
    if docker ps --format '{{.Names}}' | grep -q apt-cacher; then
        echo "  ✅ apt-cacher-ng container healthy"
    else
        echo "  ⚠️  apt-cacher-ng not running — packages will download from internet"
    fi

    # 3. Stale build lock?
    if [ -f "$REPO/build/.alfred-lb-docker-build.lock" ]; then
        echo "  ⚠️  Stale build lock detected — clearing"
        nuke_all_locks
    else
        echo "  ✅ No stale build locks"
    fi

    # 4. Stale mounts from crashed builds?
    if mount | grep -q "$REPO/build/chroot/proc" 2>/dev/null; then
        echo "  ⚠️  Stale bind mounts detected — cleaning"
        umount -lf "$REPO/build/chroot/proc" 2>/dev/null || true
        umount -lf "$REPO/build/chroot/sys" 2>/dev/null || true
        umount -lf "$REPO/build/chroot/dev/pts" 2>/dev/null || true
        umount -lf "$REPO/build/chroot/dev" 2>/dev/null || true
    else
        echo "  ✅ No stale bind mounts"
    fi

    # 5. Disk space check
    local avail
    avail=$(df --output=avail / | tail -1 | tr -d ' ')
    local avail_gb=$((avail / 1048576))
    if [ "$avail_gb" -lt 50 ]; then
        echo "  FATAL: Only ${avail_gb}GB free. Need at least 50GB." >&2
        exit 1
    fi
    echo "  ✅ Disk: ${avail_gb}GB free"

    # 6. Master chroot exists? (required for fast, warning for full)
    if [ -d "$REPO/build/cache/bootstrap/opt" ]; then
        echo "  ✅ Master chroot present at build/cache/bootstrap/"
    else
        if [ "$MODE" = "fast" ]; then
            echo "  FATAL: Master chroot not found — cannot do fast build without golden cache." >&2
            exit 1
        else
            echo "  ⚠️  Master chroot not found — full build will create it"
        fi
    fi

    # 7. Kernel .deb packages present?
    if ls "$REPO/config/packages.chroot"/linux-image-7.0.12*.deb &>/dev/null; then
        echo "  ✅ Kernel 7.0.12 debs present"
    else
        echo "  FATAL: Kernel deb linux-image-7.0.12 not found in config/packages.chroot/" >&2
        exit 1
    fi

    # 8. Inner scripts exist?
    if [ "$MODE" = "full" ] && [ ! -f "$REPO/scripts/lb-docker-inner-build.sh" ]; then
        echo "  FATAL: Missing scripts/lb-docker-inner-build.sh" >&2
        exit 1
    fi
    if [ "$MODE" = "fast" ] && [ ! -f "$REPO/scripts/inner-lb-binary-only-fast.sh" ]; then
        echo "  FATAL: Missing scripts/inner-lb-binary-only-fast.sh" >&2
        exit 1
    fi
    echo "  ✅ Inner build script present"

    # 9. No other alfred build containers running?
    local running
    running=$(docker ps --filter "name=alfred-lb" --format '{{.Names}}' 2>/dev/null | head -1)
    if [ -n "$running" ]; then
        echo "  ⚠️  Existing build container running: $running"
        echo "     Kill it first: docker rm -f $running"
        exit 1
    fi
    echo "  ✅ No competing build containers"

    echo "[alfred-build] ═══ ALL PRE-FLIGHT CHECKS PASSED ✅ ═══"
}

show_status() {
    echo "═══ RUNNING ALFRED BUILD CONTAINERS ═══"
    docker ps --filter "label=alfred.compiler=active" \
        --format "table {{.Names}}\t{{.Status}}\t{{.CreatedAt}}" 2>/dev/null || true
    docker ps --filter "name=alfred-lb" \
        --format "table {{.Names}}\t{{.Status}}\t{{.CreatedAt}}" 2>/dev/null || true
    echo ""
    echo "═══ LATEST LOG (last 30 lines) ═══"
    if [ -f "$LOG_FILE" ]; then
        tail -30 "$LOG_FILE"
    else
        echo "No log file found at $LOG_FILE"
    fi
    echo ""
    echo "═══ DISK ═══"
    df -h /
}

sync_config() {
    echo "[alfred-build] Syncing hooks, packages, and assets to build/config/..."
    if [ -x "$REPO/scripts/sync-canonical-to-build.sh" ]; then
        bash "$REPO/scripts/sync-canonical-to-build.sh"
    else
        # Manual sync fallback
        echo "[alfred-build] sync-canonical-to-build.sh not executable, using manual rsync..."
        mkdir -p "$REPO/build/config/hooks/live" "$REPO/build/config/hooks/normal" \
                 "$REPO/build/config/packages.chroot" "$REPO/build/config/package-lists"
        rsync -a "$REPO/config/hooks/live/" "$REPO/build/config/hooks/live/" 2>/dev/null || true
        rsync -a "$REPO/config/hooks/normal/" "$REPO/build/config/hooks/normal/" 2>/dev/null || true
        rsync -a "$REPO/config/packages.chroot/" "$REPO/build/config/packages.chroot/" 2>/dev/null || true
        rsync -a "$REPO/config/package-lists/" "$REPO/build/config/package-lists/" 2>/dev/null || true
    fi
    echo "[alfred-build] Sync complete."
}

post_build() {
    # Sync packages.json to live website
    if [ -f "$REPO/iso-output/packages.json" ]; then
        cp "$REPO/iso-output/packages.json" \
           /home/gositeme/domains/alfredlinux.com/public_html/packages.json 2>/dev/null || true
        echo "[alfred-build] packages.json synced to alfredlinux.com"
    fi

    echo "[alfred-build] ═══ BUILD COMPLETE ═══"
    echo "[alfred-build] Check ISO at: $REPO/iso-output/"
    ls -lh "$REPO/iso-output/"*.iso 2>/dev/null || echo "  (no ISO found yet)"
}

# ── Docker run (array-based — safe with spaces and special chars) ─────────

build_docker_args() {
    # Returns docker run arguments as an array via DOCKER_ARGS global
    DOCKER_ARGS=(
        --privileged
        --network=host
        --shm-size=16G
        --init
        --label "alfred.compiler=active"
        -e "DEBIAN_FRONTEND=noninteractive"
        -e "BUILD_UID=$(id -u)"
        -e "BUILD_GID=$(id -g)"
        -e "ALFRED_LB_DOCKER_FLOCK_BLOCKING=${ALFRED_LB_DOCKER_FLOCK_BLOCKING}"
        -e "ALFRED_ALLOW_SSH_PASSWORD_AUTH=${ALFRED_ALLOW_SSH_PASSWORD_AUTH}"
        -e "MKSQUASHFS_OPTIONS=-mem 14G"
        -v "$REPO:/work"
        -v "/home/gositeme/law:/host_law:ro"
        -w "/work"
    )
}

# ── FULL BUILD ────────────────────────────────────────────────────────────

run_full() {
    local inner="/work/scripts/lb-docker-inner-build.sh"
    chmod 750 "$REPO/scripts/lb-docker-inner-build.sh" 2>/dev/null || true

    build_docker_args
    # Full build also mounts models-vault for AI
    DOCKER_ARGS+=( -v "/home/root/law/models-vault:/models:ro" )

    echo "[alfred-build] ═══ LAUNCHING FULL BUILD ═══"
    echo "[alfred-build] Container: $CONTAINER_NAME"
    echo "[alfred-build] Image:     $IMAGE"
    echo "[alfred-build] Inner:     lb-docker-inner-build.sh"
    echo "[alfred-build] Log:       $LOG_FILE"
    echo ""

    if [ "$DETACH" = "detach" ]; then
        docker run -d --rm --name "$CONTAINER_NAME" \
            "${DOCKER_ARGS[@]}" \
            "$IMAGE" bash "$inner"
        echo "$CONTAINER_NAME" > "$REPO/lb-docker.containername"
        echo "[alfred-build] ✅ Detached."
        echo "[alfred-build] Follow:  docker logs -f $CONTAINER_NAME"
        echo "[alfred-build] Status:  bash scripts/alfred-build.sh status"
        # Wire container logs to file
        nohup docker logs -f "$CONTAINER_NAME" >> "$LOG_FILE" 2>&1 &
    else
        docker run --rm --name "$CONTAINER_NAME" \
            "${DOCKER_ARGS[@]}" \
            "$IMAGE" bash "$inner"
        post_build
    fi
}

# ── FAST BUILD ────────────────────────────────────────────────────────────

run_fast() {
    local inner="/work/scripts/inner-lb-binary-only-fast.sh"
    chmod 750 "$REPO/scripts/inner-lb-binary-only-fast.sh" 2>/dev/null || true

    # Kill stale fast containers from old scripts
    docker rm -f alfred-lb-v-fast34 alfred-lb-v-fast-runner 2>/dev/null || true

    build_docker_args

    echo "[alfred-build] ═══ LAUNCHING FAST BUILD ═══"
    echo "[alfred-build] Container: $CONTAINER_NAME"
    echo "[alfred-build] Image:     $IMAGE"
    echo "[alfred-build] Inner:     inner-lb-binary-only-fast.sh"
    echo "[alfred-build] Log:       $LOG_FILE"
    echo ""

    if [ "$DETACH" = "detach" ]; then
        docker run -d --rm --name "$CONTAINER_NAME" \
            "${DOCKER_ARGS[@]}" \
            "$IMAGE" bash -c "
                # Clear locks inside container
                rm -f /work/build/.lock /work/build/.alfred-lb-docker-build.lock
                find /work/build/ -maxdepth 1 -name '*.lock' -delete 2>/dev/null || true
                # Clear dpkg/apt locks from crashed builds
                rm -f /work/build/chroot/var/lib/dpkg/lock* 2>/dev/null || true
                rm -f /work/build/chroot/var/cache/apt/archives/lock 2>/dev/null || true
                rm -rf /work/build/chroot/var/lib/dpkg/updates/* 2>/dev/null || true
                echo '[alfred-build] Fast build starting at $(date -Iseconds)' > /work/build/lb-docker-build.log
                bash $inner
            "
        echo "$CONTAINER_NAME" > "$REPO/lb-docker.containername"
        echo "[alfred-build] ✅ Detached."
        echo "[alfred-build] Follow:  docker logs -f $CONTAINER_NAME"
        echo "[alfred-build] Status:  bash scripts/alfred-build.sh status"
        # Wire container logs to file
        sleep 2
        docker exec "$CONTAINER_NAME" chmod 666 /work/build/lb-docker-build.log 2>/dev/null || true
        nohup docker logs -f "$CONTAINER_NAME" >> "$LOG_FILE" 2>&1 &
    else
        docker run --rm --name "$CONTAINER_NAME" \
            "${DOCKER_ARGS[@]}" \
            "$IMAGE" bash -c "
                rm -f /work/build/.lock /work/build/.alfred-lb-docker-build.lock
                find /work/build/ -maxdepth 1 -name '*.lock' -delete 2>/dev/null || true
                rm -f /work/build/chroot/var/lib/dpkg/lock* 2>/dev/null || true
                rm -f /work/build/chroot/var/cache/apt/archives/lock 2>/dev/null || true
                rm -rf /work/build/chroot/var/lib/dpkg/updates/* 2>/dev/null || true
                echo '[alfred-build] Fast build starting at $(date -Iseconds)' > /work/build/lb-docker-build.log
                bash $inner
            "
        post_build
    fi
}

# ── Main dispatch ─────────────────────────────────────────────────────────

case "$MODE" in
    full)
        echo ""
        echo "╔══════════════════════════════════════════════════════════════╗"
        echo "║     🛡️  ALFRED LINUX — FULL BUILD                          ║"
        echo "║     This will run lb bootstrap + lb chroot + lb binary     ║"
        echo "║     Estimated time: 2-6 hours depending on network         ║"
        echo "╚══════════════════════════════════════════════════════════════╝"
        echo ""
        check_prereqs
        nuke_all_locks
        sync_config
        run_full
        ;;
    fast)
        echo ""
        echo "╔══════════════════════════════════════════════════════════════╗"
        echo "║     ⚡ ALFRED LINUX — FAST BUILD                           ║"
        echo "║     Rsync golden cache → chroot, then lb binary only       ║"
        echo "║     Estimated time: 30-60 minutes                          ║"
        echo "╚══════════════════════════════════════════════════════════════╝"
        echo ""
        check_prereqs
        nuke_all_locks
        sync_config
        run_fast
        ;;
    status)
        show_status
        ;;
    clean-locks)
        nuke_all_locks
        ;;
    help|--help|-h)
        usage
        ;;
    *)
        echo "ERROR: Unknown mode '$MODE'" >&2
        echo ""
        usage
        ;;
esac
