Omahon seal — multi-resolution wallpapers (1080p / 4K / 8K)
==========================================================
Source raster (required path):

  build-assets/wallpapers/raw/omahon-seal-source.png

**Size:** short edge must be **≥ 2048 px** for acceptable 8K/4K (enforced by
`scripts/apply-new-omahon-source.sh` unless you set `OMAHON_ALLOW_SMALL_SOURCE=1`).
**4096+ px** square is the practical target. Sub-1K sources (e.g. 768×768) will
always look bad at 7680×4320.

During `lb build`, hook `0100-alfred-customize.hook.chroot` composes that image
onto a 7680×4320 canvas, writes:

  /usr/share/backgrounds/alfred-linux/{8k,4k,1080p}/omahon-seal.png

and registers the trio like other Kingdom backgrounds.

Optional: generate a new master with **OpenAI** (paid key from `~/.vault/keys/openai.key`
or `OPENAI_API_KEY` — not a free service), then apply:

  python3 build-assets/wallpapers/scripts/generate-omahon-seal-openai.py --apply

Optional pre-stage for kingdom-media copies (0285 §7): run before `build-unified.sh`:

  bash build-assets/wallpapers/scripts/prepare-omahon-seal-for-build-assets.sh

Legal / story / lineage text belongs in your private handoff or counsel — this
tree only stores **pixels + build wiring**. Omahon.
