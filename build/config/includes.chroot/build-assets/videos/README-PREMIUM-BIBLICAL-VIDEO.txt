**→ Merged quick ref: `../../docs/KINGDOM-MEDIA-AND-GA-ONE-PAGE.txt` (read that first.)**

Premium biblical story on disk — real motion picture (honest Kingdom payload)
===============================================================================

What was wrong with “the old way”
---------------------------------
`build-kingdom-video.sh` uses ffmpeg **zoompan** on **static PNGs**. That is
cinematic wallpaper motion — not animation, not actors, not generative video.
It can look “expensive” in bitrate but still **not** tell a living biblical story
the way true motion picture does. The script header now says this explicitly.

We did **not** auto-delete any master `.mp4` from your ISO tree in tooling here;
you choose what ships under `build-assets/videos/` before `build-unified.sh ga`.

What “best for Jesus” usually means in practice
-----------------------------------------------
1. **Story first** — scripture-accurate beats, shot list, narration (AKJV lines),
   music you have rights to ship on the ISO.
2. **Human craft** — director / editor / colorist; AI accelerates storyboards and
   rough cuts, not holiness.
3. **Real pixels** — commissioned footage, CGI where licensed, or **generative
   video** from top-tier models **with theology review** (every frame can lie).

Who / what makes the strongest generative video (landscape shifts fast)
------------------------------------------------------------------------
As of early 2026 the field moves weekly. Names to compare for **short cinematic
clips** (not slideshow motion on stills):

• **Google** — **Veo** family (video), **Imagen** (stills for matte paintings),
  **Gemini** (script, shot breakdown, continuity notes). “Nano” / consumer names
  change; check Google AI Studio / Vertex for the current video SKU.
• **OpenAI** — **Sora-class** products when available on your account tier.
• **Runway** — Gen series: strong motion, good for B-roll and stylized scenes.
• **Luma Dream Machine**, **Kling**, **Pika** — useful for shots; verify anatomy,
  text on screen, and iconography against scripture.
• **Together.ai** — excellent for **LLM** text (exegesis, shot lists, prompts);
  video is not their headline strength vs dedicated video APIs. Use Together for
  *words* and planning; use a **video-native** API for *pixels* unless their docs
  show a model you love after your own tests.

**Spare no expense** = budget for **iterations + human QC**, not only raw API
spend. One wrong frame about Christ is worse than saving credits.

Using APIs from your Commander vault
-------------------------------------
• Never commit keys. Use a **local** env file (e.g. sourced before render) or CI
  secret store. Keys belong in **your** vault, not in `alfred-linux-v2` git.
• Export finished **.mp4** (ProRes intermediate optional) into:
    build-assets/videos/kingdom-of-god-edition.mp4
    build-assets/videos/clips/   (optional scene masters)
• Re-run GA build; hook **0285 §7** copies these into the live ISO under
  `/usr/share/alfred-linux/kingdom-media/videos/`.

Optional: Wikipedia / Kiwix
----------------------------
Huge reference sets (e.g. English Wikipedia `.zim`) are **better as a second
torrent** or post-install bundle — see `docs/ISO-777-GiB-PLAN.txt` — so the
hybrid ISO stays boot-safe and honest.

Sovereign chronicle (Acts, Perez lineage, April timeline)
-----------------------------------------------------------
Place PDF/HTML exports under `build-assets/kingdom-documents/` — see that folder’s
README. Canonical web example for AKJV public release context:
https://lavocat.ca/journal?read=9&lang=en

GA build gate — real master MP4 required (optional flag)
-------------------------------------------------------
When you will not ship GA without a **true** `kingdom-of-god-edition.mp4` of at least
**N** minutes (default **3**), set before `sudo ./scripts/build-unified.sh ga`:

  export KINGDOM_REQUIRE_MASTER_MP4=1
  # optional:
  # export KINGDOM_MASTER_MP4=/full/path/to/kingdom-of-god-edition.mp4
  # export KINGDOM_MASTER_MIN_MINUTES=5
  # export KINGDOM_MASTER_MIN_MINUTES=0   # only require file ≥ 30 seconds

Requires **ffprobe** (ffmpeg). Interim **rc** / **b*** builds ignore this flag.
See `scripts/require-kingdom-master-video.sh`.
