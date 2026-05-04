#!/usr/bin/env python3
import argparse
import json
from pathlib import Path


def svg_template(width, height, title, phase, progress, eta, updated):
    p = max(0, min(100, int(progress)))
    bar_w = int((width - 120) * (p / 100.0))
    return f'''<svg xmlns="http://www.w3.org/2000/svg" width="{width}" height="{height}" viewBox="0 0 {width} {height}">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#081423"/>
      <stop offset="100%" stop-color="#0a2236"/>
    </linearGradient>
    <linearGradient id="bar" x1="0" y1="0" x2="1" y2="0">
      <stop offset="0%" stop-color="#2ad1a3"/>
      <stop offset="100%" stop-color="#36c3ff"/>
    </linearGradient>
  </defs>
  <rect width="100%" height="100%" fill="url(#bg)"/>
  <circle cx="{width - 80}" cy="60" r="42" fill="#0f2c44" opacity="0.45"/>
  <text x="48" y="72" font-size="22" fill="#90aac6" font-family="Segoe UI, Arial">AlfredLinux Public Status</text>
  <text x="48" y="128" font-size="48" font-weight="700" fill="#e8f4ff" font-family="Segoe UI, Arial">{title}</text>
  <text x="48" y="172" font-size="30" fill="#2ad1a3" font-family="Segoe UI, Arial">{phase} • {p}%</text>
  <rect x="48" y="206" rx="14" ry="14" width="{width - 96}" height="28" fill="#0f1e2d" stroke="#1b3654"/>
  <rect x="48" y="206" rx="14" ry="14" width="{bar_w}" height="28" fill="url(#bar)"/>
  <text x="48" y="290" font-size="28" fill="#e8f4ff" font-family="Segoe UI, Arial">ETA window: {eta}</text>
  <text x="48" y="334" font-size="22" fill="#90aac6" font-family="Segoe UI, Arial">Updated: {updated}</text>
  <text x="48" y="{height - 36}" font-size="18" fill="#ffb14a" font-family="Segoe UI, Arial">Timing can shift during final validation for stability and security.</text>
</svg>'''


def main():
    ap = argparse.ArgumentParser(description="Generate social SVG images from public status")
    ap.add_argument("--input", default="/home/gositeme/law/alfredlinux-com-source-live/public-status-site/data/public-status.json")
    ap.add_argument("--outdir", default="/home/gositeme/law/alfredlinux-com-source-live/public-status-site/assets/generated")
    args = ap.parse_args()

    data = json.loads(Path(args.input).read_text(encoding="utf-8"))
    title = data.get("release_name", "Alfred Linux")
    phase = data.get("phase_label", "Building")
    progress = int(data.get("progress_pct", 0))
    eta = data.get("eta_window") or data.get("phase_label", "Validation")
    updated = str(data.get("last_updated_epoch", "-"))

    outdir = Path(args.outdir)
    outdir.mkdir(parents=True, exist_ok=True)

    targets = [
        (1200, 630, "alfred-status-og-1200x630.svg"),
        (1080, 1080, "alfred-status-square-1080.svg"),
        (1080, 1920, "alfred-status-story-1080x1920.svg"),
    ]

    for w, h, name in targets:
        svg = svg_template(w, h, title, phase, progress, eta, updated)
        p = outdir / name
        p.write_text(svg, encoding="utf-8")
        print(f"Wrote {p}")


if __name__ == "__main__":
    main()
