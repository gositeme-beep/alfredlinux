#!/usr/bin/env python3
import argparse
import json
import re
import time
from pathlib import Path

PHASE_MAP = {
    "waiting_for_container": "Building",
    "done": "Validation",
    "failed": "Recovery",
}

HOOK_MAX = 470
HOOK_RE = re.compile(r"Executing hook .*?/([0-9]{4})-[^\s/]+")


def load_json(path: Path):
    try:
        return json.loads(path.read_text(encoding="utf-8"))
    except Exception:
        return {}


def clamp(v, lo, hi):
    return max(lo, min(hi, v))


def parse_int(v):
    try:
        return int(float(v))
    except Exception:
        return None


def derive_progress(phase: str, docker_exit: str, iso_count: int):
    base = {
        "waiting_for_container": 10,
        "done": 85,
        "failed": 35,
    }.get(phase, 12)
    if docker_exit == "0":
        base = max(base, 88)
    if iso_count and iso_count > 0:
        base = max(base, 82)
    return clamp(base, 1, 99)


def extract_live_progress(src: dict):
    for k in (
        "progress_pct",
        "progress_percent",
        "progress",
        "watch_progress_pct",
        "watcher_progress_pct",
        "build_progress_pct",
        "build_progress",
    ):
        v = parse_int(src.get(k))
        if v is not None:
            return clamp(v, 1, 99)
    return None


def derive_progress_from_log(log_path: Path):
    try:
        text = log_path.read_text(encoding="utf-8", errors="replace")
    except Exception:
        return None
    hook_num = None
    for m in HOOK_RE.finditer(text):
        hook_num = int(m.group(1))
    if hook_num is None:
        return None
    pct = int(round((hook_num / HOOK_MAX) * 100))
    return clamp(pct, 10, 95)


def apply_monotonic_guard(progress: int, phase_raw: str, src: dict, state: dict):
    curr_container = str(src.get("container") or "")
    prev_container = str(state.get("container") or "")
    prev_progress = parse_int(state.get("progress_pct"))

    # Keep public progress monotonic for a single active run (same container),
    # but allow resets automatically when a new container/run starts.
    same_run = bool(curr_container and prev_container and curr_container == prev_container)
    non_terminal = phase_raw not in ("done", "failed")
    if same_run and non_terminal and prev_progress is not None:
        progress = max(progress, clamp(prev_progress, 1, 99))

    return progress, curr_container


def main():
    ap = argparse.ArgumentParser(description="Export sanitized public status JSON")
    ap.add_argument("--input", default="/home/gositeme/law/alfred-build-control-plane/last-lb-docker.json")
    ap.add_argument("--output", default="/home/gositeme/law/alfredlinux-com-source-live/public-status-site/data/public-status.json")
    ap.add_argument("--build-log", default="/home/gositeme/law/alfredlinux-com-source-live/lb-docker-build.log")
    ap.add_argument("--state", default="/home/gositeme/law/alfred-build-control-plane/public-status-export-state.json")
    ap.add_argument("--release-name", default="Alfred Linux 7.77")
    ap.add_argument("--eta-window", default="2 to 4 hours")
    args = ap.parse_args()

    src = load_json(Path(args.input))
    state = load_json(Path(args.state))

    phase_raw = str(src.get("phase") or "waiting_for_container")
    phase_label = PHASE_MAP.get(phase_raw, "Building")
    docker_exit = str(src.get("docker_exit") or "pending")
    iso_count = int(src.get("iso_count") or 0)

    progress = extract_live_progress(src)
    if progress is None and phase_raw == "waiting_for_container":
        progress = derive_progress_from_log(Path(args.build_log))
    if progress is None:
        progress = derive_progress(phase_raw, docker_exit, iso_count)

    progress, curr_container = apply_monotonic_guard(progress, phase_raw, src, state)

    public_note = "Build and verification are actively progressing."
    if src.get("recovery_reason_code"):
        public_note = "Automatic recovery protections are active while build and verification continue."
    if phase_raw == "done":
        public_note = "Build completed and release quality gates are in progress."

    payload = {
        "release_name": args.release_name,
        "tagline": "Build and validation are in progress for the next public release.",
        "progress_pct": progress,
        "phase_label": phase_label,
        "eta_window": args.eta_window,
        "public_note": public_note,
        "quality_gates": [
            "Boot test",
            "Integrity checks",
            "Smoke verification",
            "Release signing",
            "Publish",
        ],
        "last_updated_epoch": int(time.time()),
    }

    out = Path(args.output)
    out.parent.mkdir(parents=True, exist_ok=True)
    out.write_text(json.dumps(payload, indent=2) + "\n", encoding="utf-8")

    state_payload = {
        "container": curr_container,
        "phase": phase_raw,
        "progress_pct": progress,
        "updated_epoch": int(time.time()),
    }
    st = Path(args.state)
    st.parent.mkdir(parents=True, exist_ok=True)
    st.write_text(json.dumps(state_payload, indent=2) + "\n", encoding="utf-8")


if __name__ == "__main__":
    main()
