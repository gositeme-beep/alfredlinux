#!/usr/bin/env python3
"""
╔═══════════════════════════════════════════════════════════════════╗
║  THE KING'S VIDEO — Full Ken Burns 4K Cinematic Slideshow        ║
║  Alfred Linux 7.77 — Kingdom of God Edition                      ║
║  "Every king needs a kingdom." — Commander Danny William Perez   ║
╚═══════════════════════════════════════════════════════════════════╝

DEPRECATED / NOT FOR GA: zoompan on stills — not biblical animation. Same opt-in
as the shell script: set KINGDOM_I_WANT_KEN_BURNS_STILLS=1 or this program exits.

Each image gets:
  - Upscaled to 5760x3240 (1.5× of 4K) for zoom headroom
  - Ken Burns zoompan: slow zoom in/out with drift
  - Crossfaded with varied transitions between scenes
  - Output: 3840×2160 (4K UHD), libx264 CRF 18, ~2.5 min
"""

import subprocess
import sys
import os

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
FFMPEG = os.environ.get("FFMPEG", "/home/gositeme/bin/ffmpeg")
WALLDIR = os.environ.get("KINGDOM_WALLDIR", os.path.join(SCRIPT_DIR, "wallpapers", "4k"))
OUTDIR = os.environ.get("KINGDOM_OUTDIR", os.path.join(SCRIPT_DIR, "videos"))
OUTPUT = os.path.join(OUTDIR, "kingdom-of-god-edition.mp4")

W = int(os.environ.get("KINGDOM_W", "3840"))
H = int(os.environ.get("KINGDOM_H", "2160"))
FPS = int(os.environ.get("KINGDOM_FPS", "24"))
DUR = int(os.environ.get("KINGDOM_DUR", "10"))       # seconds per image
FADE = 1.5     # crossfade seconds
N_FRAMES = DUR * FPS  # 240 frames per image
SUP_W, SUP_H = W * 3 // 2, H * 3 // 2  # supersample canvas (matches bash build-kingdom-video.sh)
CR_MERGE = os.environ.get("KINGDOM_CRF_MERGE", "18")

# Images in narrative order
IMAGES = [
    "kingdom-throne-4k.png",           # The Throne of the Kingdom
    "gods-throne-4k.png",              # God's Throne in Heaven
    "risen-king-4k.png",               # The Risen King — Jesus Christ
    "crown-of-glory-4k.png",           # Crown of Glory
    "lion-of-judah-4k.png",            # Lion of Judah
    "daniel-lions-den-4k.png",         # Daniel in the Lions' Den
    "mount-sinai-4k.png",              # Mount Sinai — God's Law
    "sanctuary-dawn-4k.png",           # Sanctuary at Dawn
    "living-waters-4k.png",            # Living Waters
    "eden-garden-4k.png",              # Garden of Eden
    "edens-garden-4k.png",             # Eden's Garden (the Heir)
    "new-jerusalem-4k.png",            # New Jerusalem
    "new-jerusalem-descending-4k.png", # New Jerusalem Descending
    "perez-crest-4k.png",              # The Perez Family Crest
    "dynasty-gateway-4k.png",          # Dynasty Gateway
    "perez-family-legacy-4k.png",      # The Perez Family Legacy
    "kingdom-seal-4k.png",             # Kingdom Seal
    "sovereign-dark-4k.png",           # Sovereign Dark (finale)
]

# Ken Burns motion patterns — each is (zoom_start, zoom_end, x_expr, y_expr)
# These create different slow zoom/pan motions
MOTIONS = [
    # Slow zoom in, center
    (1.0, 1.25, "'iw/2-(iw/zoom/2)'", "'ih/2-(ih/zoom/2)'"),
    # Slow zoom out, center
    (1.25, 1.0, "'iw/2-(iw/zoom/2)'", "'ih/2-(ih/zoom/2)'"),
    # Zoom in, drift right
    (1.0, 1.2, "'min(iw/2-(iw/zoom/2)+on*0.3,iw-iw/zoom)'", "'ih/2-(ih/zoom/2)'"),
    # Zoom in, drift left
    (1.0, 1.2, "'max(iw/2-(iw/zoom/2)-on*0.3,0)'", "'ih/2-(ih/zoom/2)'"),
    # Zoom in, drift down
    (1.0, 1.15, "'iw/2-(iw/zoom/2)'", "'min(ih/2-(ih/zoom/2)+on*0.2,ih-ih/zoom)'"),
    # Zoom out, drift up
    (1.25, 1.0, "'iw/2-(iw/zoom/2)'", "'max(ih/2-(ih/zoom/2)-on*0.2,0)'"),
    # Zoom in from top-left
    (1.0, 1.3, "'on*0.15'", "'on*0.1'"),
    # Zoom out to center from top-right
    (1.3, 1.0, "'iw-iw/zoom-on*0.1'", "'on*0.1'"),
    # Gentle zoom, drift bottom-right
    (1.0, 1.15, "'min(on*0.2,iw-iw/zoom)'", "'min(on*0.15,ih-ih/zoom)'"),
]

# Transition types to cycle through
TRANSITIONS = [
    "fade", "dissolve", "slideright", "slideleft",
    "circleopen", "radial", "wiperight", "wipeleft",
    "smoothup", "smoothdown",
]

def build_ffmpeg_command():
    """Build the full FFmpeg command with Ken Burns zoompan + xfade."""
    
    n = len(IMAGES)
    
    # Input args: each image as a still for DUR seconds
    input_args = []
    for img in IMAGES:
        path = os.path.join(WALLDIR, img)
        if not os.path.exists(path):
            print(f"WARNING: Missing {path}")
            continue
        input_args.extend(["-loop", "1", "-t", str(DUR), "-i", path])
    
    # Build filter_complex
    filters = []
    
    # Step 1: Apply Ken Burns zoompan to each input
    for i in range(n):
        motion = MOTIONS[i % len(MOTIONS)]
        z_start, z_end, x_expr, y_expr = motion
        
        # Linear zoom interpolation
        z_step = (z_end - z_start) / N_FRAMES
        if z_step >= 0:
            z_expr = f"'min(zoom+{z_step:.6f},{z_end})'"
        else:
            z_expr = f"'max(zoom+{z_step:.6f},{z_end})'"
        
        # zoompan: takes input, applies zoom/pan, outputs at target resolution
        # We scale up first to give headroom for zooming
        zp = (
            f"[{i}:v]scale={SUP_W}:{SUP_H},setsar=1,"
            f"zoompan=z={z_expr}:d={N_FRAMES}:s={W}x{H}:fps={FPS}"
            f":x={x_expr}:y={y_expr}"
            f"[zp{i}]"
        )
        filters.append(zp)
    
    # Step 2: Crossfade chain
    last = f"zp0"
    for i in range(1, n):
        offset = round(i * (DUR - FADE), 2)
        trans = TRANSITIONS[(i - 1) % len(TRANSITIONS)]
        out = f"xf{i}"
        xf = f"[{last}][zp{i}]xfade=transition={trans}:duration={FADE}:offset={offset}[{out}]"
        filters.append(xf)
        last = out
    
    filter_complex = ";\n".join(filters)
    
    # Build full command
    cmd = [
        FFMPEG, "-y",
        *input_args,
        "-filter_complex", filter_complex,
        "-map", f"[{last}]",
        "-c:v", "libx264",
        "-preset", "medium",
        "-crf", str(CR_MERGE),
        "-pix_fmt", "yuv420p",
        "-movflags", "+faststart",
        "-metadata", "title=Alfred Linux 7.77 — Kingdom of God Edition",
        "-metadata", "artist=Commander Danny William Perez",
        "-metadata", "comment=SOLI DEO GLORIA — Every king needs a kingdom.",
        OUTPUT
    ]
    
    return cmd, filter_complex


def main():
    if os.environ.get("KINGDOM_I_WANT_KEN_BURNS_STILLS") != "1":
        print(
            "[build-kingdom-video.py] REFUSED: Ken Burns on stills only — not your biblical film.\n"
            "See README-PREMIUM-BIBLICAL-VIDEO.txt. To force this legacy path:\n"
            "  KINGDOM_I_WANT_KEN_BURNS_STILLS=1 python3 build-assets/build-kingdom-video.py",
            file=sys.stderr,
        )
        sys.exit(3)

    os.makedirs(OUTDIR, exist_ok=True)

    print("╔═══════════════════════════════════════════════════════════╗")
    print("║  THE KING'S VIDEO — Full Ken Burns 4K Cinematic          ║")
    print(f"║  {len(IMAGES)} scenes × {DUR}s each = {len(IMAGES)*DUR}s (~{len(IMAGES)*DUR/60:.1f} min)              ║")
    print(f"║  Resolution: {W}×{H} (4K UHD) @ {FPS}fps              ║")
    print("║  zoompan + xfade — THE EXPENSIVE ONE                     ║")
    print("║  'Every king needs a kingdom.'                           ║")
    print("╚═══════════════════════════════════════════════════════════╝")
    print()
    
    # Verify all images exist
    missing = []
    for img in IMAGES:
        if not os.path.exists(os.path.join(WALLDIR, img)):
            missing.append(img)
    
    if missing:
        print(f"ERROR: Missing {len(missing)} images:")
        for m in missing:
            print(f"  - {m}")
        sys.exit(1)
    
    print(f"All {len(IMAGES)} images verified.")
    print()
    
    cmd, fc = build_ffmpeg_command()
    
    # Save filter for debugging
    with open(os.path.join(OUTDIR, "kingdom-filter.txt"), "w") as f:
        f.write(fc)
    print("Filter saved to kingdom-filter.txt")
    print()
    
    print("Starting encode — this is the ROYAL treatment (Ken Burns 4K)...")
    print(f"Output: {OUTPUT}")
    print()
    
    # Run FFmpeg
    proc = subprocess.run(cmd, capture_output=False)
    
    if proc.returncode == 0:
        size = os.path.getsize(OUTPUT)
        print()
        print("╔═══════════════════════════════════════════════════════════╗")
        print("║  THE KING'S VIDEO — COMPLETE                             ║")
        print(f"║  Size: {size/1024/1024:.1f} MB                                        ║")
        print("║  SOLI DEO GLORIA                                         ║")
        print("╚═══════════════════════════════════════════════════════════╝")
    else:
        print(f"ERROR: FFmpeg exited with code {proc.returncode}")
        sys.exit(1)


if __name__ == "__main__":
    main()
