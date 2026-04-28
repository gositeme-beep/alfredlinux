#!/usr/bin/env python3
"""
Generate omahon-seal source via OpenAI Images API (paid key from vault).
No free/placeholder services — set OPENAI_API_KEY or use default key path.

Default: gpt-image-1.5 @ 1024x1024, quality=high, then Lanczos upscale to --upscale (4096)
so apply-new-omahon-source.sh short-edge check passes. Override model/size if your
org supports gpt-image-2 / larger sizes.

Usage:
  export OPENAI_API_KEY=...   # or rely on ~/.vault/keys/openai.key
  python3 generate-omahon-seal-openai.py --apply
  python3 generate-omahon-seal-openai.py -o /tmp/seal.png --prompt "your prompt"
"""
from __future__ import annotations

import argparse
import base64
import json
import os
import subprocess
import sys
import urllib.error
import urllib.request

API = "https://api.openai.com/v1/images/generations"

DEFAULT_KEY_PATH = os.path.expanduser("~/.vault/keys/openai.key")
DEFAULT_PROMPT = (
    "A single ornate circular heraldic seal emblem, gold and deep indigo, "
    "sacred geometry and fine engraved detail, no text, no letters, no human faces, "
    "centered composition, professional product art, high detail, suitable for large-format print"
)


def read_key(explicit_path: str | None) -> str:
    if explicit_path:
        with open(explicit_path, "r", encoding="utf-8") as f:
            return f.read().strip()
    if os.environ.get("OPENAI_API_KEY"):
        return os.environ["OPENAI_API_KEY"].strip()
    p = os.environ.get("OPENAI_KEY_FILE", DEFAULT_KEY_PATH)
    with open(p, "r", encoding="utf-8") as f:
        k = f.read().strip()
    if not k:
        print("No API key: set OPENAI_API_KEY or use --key-file / " + p, file=sys.stderr)
        sys.exit(1)
    return k


def call_generations(
    key: str,
    prompt: str,
    model: str,
    size: str,
    quality: str,
) -> bytes:
    body: dict = {
        "model": model,
        "prompt": prompt,
        "n": 1,
    }
    if model.startswith("dall-e-"):
        body["size"] = size
        if model == "dall-e-3":
            body["quality"] = "hd"
        body["response_format"] = "b64_json"
    else:
        # gpt-image-* family
        body["size"] = size
        body["quality"] = quality
        body["output_format"] = "png"
        if "1024" in size or "1536" in size or "2048" in size:
            pass
        else:
            body["size"] = "1024x1024"

    data = json.dumps(body).encode("utf-8")
    req = urllib.request.Request(
        API,
        data=data,
        method="POST",
        headers={
            "Authorization": f"Bearer {key}",
            "Content-Type": "application/json",
        },
    )
    try:
        with urllib.request.urlopen(req, timeout=600) as resp:
            payload = json.loads(resp.read().decode("utf-8"))
    except urllib.error.HTTPError as e:
        err = e.read().decode("utf-8", errors="replace")
        msg = err
        code = ""
        try:
            j = json.loads(err)
            if isinstance(j.get("error"), dict):
                msg = j["error"].get("message", err)
                code = j["error"].get("code", "") or ""
        except Exception:
            pass
        print(f"OpenAI HTTP {e.code}: {msg}" + (f" ({code})" if code else ""), file=sys.stderr)
        if e.code in (400, 401, 403, 402) and (
            "billing" in msg.lower() or "quota" in msg.lower() or "limit" in code.lower()
        ):
            print(
                "→ Billing/limits: https://platform.openai.com → Organization billing & limits. "
                "Add credit and raise the monthly/hard cap for this key’s project.",
                file=sys.stderr,
            )
        sys.exit(1)

    if "data" not in payload or not payload["data"]:
        print(json.dumps(payload, indent=2), file=sys.stderr)
        raise RuntimeError("No image in response")

    item = payload["data"][0]
    b64 = item.get("b64_json")
    if b64:
        return base64.b64decode(b64)
    url = item.get("url")
    if not url:
        raise RuntimeError("No b64_json or url in response")
    ureq = urllib.request.Request(url, method="GET")
    with urllib.request.urlopen(ureq, timeout=300) as r:
        return r.read()


def maybe_upscale(path: str, min_edge: int) -> None:
    """Resize so short edge == min_edge (square), Lanczos. Requires ImageMagick."""
    check = subprocess.run(
        ["identify", "-format", "%w %h", path],
        capture_output=True,
        text=True,
    )
    if check.returncode != 0:
        print("identify failed; install ImageMagick for --upscale", file=sys.stderr)
        sys.exit(1)
    w, h = map(int, check.stdout.split())
    m = min(w, h)
    if m >= min_edge:
        return
    magick = shutil_which("magick")
    convert = shutil_which("convert")
    cmd: list[str]
    if magick:
        cmd = [magick, path, "-filter", "Lanczos", "-resize", f"{min_edge}x{min_edge}!", path]
    elif convert:
        cmd = [convert, path, "-filter", "Lanczos", "-resize", f"{min_edge}x{min_edge}!", path]
    else:
        print("Need magick or convert for upscaling", file=sys.stderr)
        sys.exit(1)
    subprocess.check_call(cmd)


def shutil_which(name: str) -> str | None:
    for d in os.environ.get("PATH", "/usr/bin").split(os.pathsep):
        p = os.path.join(d, name)
        if os.path.isfile(p) and os.access(p, os.X_OK):
            return p
    return None


def main() -> None:
    ap = argparse.ArgumentParser(description="OMAHON seal via OpenAI paid Images API")
    ap.add_argument("--key-file", default=None, help=f"default {DEFAULT_KEY_PATH} or OPENAI_API_KEY")
    ap.add_argument("--model", default=os.environ.get("OMAHON_SEAL_API_MODEL", "gpt-image-1.5"))
    ap.add_argument(
        "--size",
        default=os.environ.get("OMAHON_SEAL_API_SIZE", "1024x1024"),
        help="API size (1024x1024 for 1.5; try 2048x2048 for gpt-image-2 if enabled)",
    )
    ap.add_argument(
        "--quality",
        default=os.environ.get("OMAHON_SEAL_API_QUALITY", "high"),
        choices=("low", "medium", "high", "auto"),
    )
    ap.add_argument(
        "--upscale",
        type=int,
        default=int(os.environ.get("OMAHON_SEAL_UPSCALE", "4096")),
        help="Min short edge after Lanczos upscale (default 4096 for 8K pipeline)",
    )
    ap.add_argument("--prompt", default=os.environ.get("OMAHON_SEAL_PROMPT", DEFAULT_PROMPT))
    ap.add_argument(
        "-o",
        "--output",
        default="/tmp/omahon-seal-openai-gen.png",
        help="Write raw API decode here before upscale/apply",
    )
    ap.add_argument(
        "--apply",
        action="store_true",
        help="Run apply-new-omahon-source.sh on final PNG",
    )
    args = ap.parse_args()

    key = read_key(args.key_file)
    print(f"Calling OpenAI images/generations model={args.model} size={args.size} quality={args.quality} ...", file=sys.stderr)
    raw_png = call_generations(key, args.prompt, args.model, args.size, args.quality)
    with open(args.output, "wb") as f:
        f.write(raw_png)
    print(f"Wrote {args.output} ({len(raw_png)} bytes)", file=sys.stderr)

    final_path = args.output
    if args.upscale and args.upscale > 0:
        maybe_upscale(final_path, args.upscale)
        print(f"Upscaled to min edge {args.upscale} -> {final_path}", file=sys.stderr)

    if args.apply:
        root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
        apply_sh = os.path.join(root, "scripts", "apply-new-omahon-source.sh")
        if not os.path.isfile(apply_sh):
            print("Missing " + apply_sh, file=sys.stderr)
            sys.exit(1)
        # apply script also checks min 2048; 4096 upscale passes
        os.execv("/bin/bash", ["bash", apply_sh, final_path])
    else:
        print("Next: bash .../apply-new-omahon-source.sh " + final_path, file=sys.stderr)


if __name__ == "__main__":
    main()
