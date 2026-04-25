# Alfred Linux — Licensing

**Project:** Alfred Linux 7.77 "Kingdom of God Edition"
**Maintainer:** Commander Danny William Perez / GoSiteMe Inc.
**Last reviewed:** 2026-04-24

This document is the single source of truth for what is open vs closed across
the Alfred Linux ecosystem. If a file's header conflicts with this document,
this document wins until the header is fixed.

---

## 1. Operating System (this repository: `alfred-linux-v2/`)

**License: AGPL-3.0-or-later**

Includes the entire `alfred-linux-v2/` tree:
- `config/hooks/live/*.hook.chroot` — all 42 Kingdom build hooks
- `config/package-lists/`
- `build-assets/` source materials (excluding bundled artifacts noted below)
- All shell scripts, helper tools, and documentation in this repository

Why AGPL-3.0: server-side / network use must give the same freedoms as local use.

## 2. Linux kernel

**License: GPL-2.0 only** (forced — derived from Linus Torvalds' tree).

The kernel config is part of this repository under AGPL-3.0; the compiled
kernel binary is GPL-2.0 because the source is GPL-2.0.

## 3. Alfred IDE / Alfred Account / Alfred Voice (VS Code extensions)

**License: AGPL-3.0-or-later** (matching the OS).

When published to a marketplace, the marketplace listing must link back to the
source repo on GoForge.

## 4. Alfred Agent / MCP runtime

**License: AGPL-3.0-or-later**.

## 5. AKJV Bible (94 books, 39,482 verses)

**License: CC0 1.0 Universal — Public Domain Dedication**

The Word should fly free. Anyone may copy, translate, embed, sell printed
copies, ship in any product — no permission needed, no royalty owed.

This applies to: TSV/JSON data files in `build-assets/bible/`,
`/home/gositeme/shared/bible/`, and any rendered output.

## 6. Worship Album — "Jesus Christ The Light Our Universe"

27 tracks by Elyon Neshama × Commander Danny William Perez.

**License: CC-BY-NC-SA-4.0**

You may share, remix, and broadcast for **non-commercial** use as long as you
**credit Elyon Neshama × Commander Danny William Perez** and share derivatives
under the same license. Commercial use requires written permission.

Files: `build-assets/audio/*.mp3`, `/listen` page on alfredlinux.com.

## 7. Visual art — Wallpapers, Plymouth themes, Kingdom videos

**License: CC-BY-SA-4.0**

Includes:
- Omahon Seal artwork (1080p / 4K / 8K)
- 22 Kingdom wallpapers (per resolution)
- Plymouth boot splash
- GRUB theme
- Cinematic launch videos

## 8. Eden's 33 Children's Bible Stories

**License: CC-BY-SA-4.0** (or CC0 if user later chooses — to be confirmed per piece).

## 9. Omahon Seal — security framework

**License: AGPL-3.0-or-later** (transparency strengthens, not weakens, real security).

Source-available so anyone can audit the boot seal, watchman, vault driver,
shell guard, secure-erase, and sovereign attestation modules.

---

## ALWAYS CLOSED — never goes to GoForge or any public mirror

These live OUTSIDE this repository and must never be committed here. The
`.gitignore` and the `gate2-backup.sh` flow keep them on the private side only:

- **`/home/gositeme/.vault/`** — API keys, OVH credentials, GPG private keys
- **`/home/gositeme/.kingdom/`** — internal cost/inventory snapshots
- **GoSiteMe payment / commerce / commander panels** — `commander-*.php`,
  `api/commander-*.php`, `pay/`, `account/`, customer DB schemas
- **Sovereign domain registry data** — customer records, billing
- **OVH client / installation / boot-control code** beyond what's needed by
  Alfred Linux itself
- **Any `*.bak.*`, `*.log`, build artifacts, and the chroot tree itself**
- **`.htaccess` files containing private rewrite logic, Shield config**

If you find one of these in this repo, treat it as a leak: rotate the secret,
remove the file, force-push history rewrite, and tell the maintainer.

---

## SPDX summary

```
SPDX-License-Identifier: AGPL-3.0-or-later                    (default for code)
SPDX-License-Identifier: GPL-2.0-only                         (kernel only)
SPDX-License-Identifier: CC0-1.0                              (AKJV Bible data)
SPDX-License-Identifier: CC-BY-NC-SA-4.0                      (worship album)
SPDX-License-Identifier: CC-BY-SA-4.0                         (visual art, stories)
SPDX-License-Identifier: LicenseRef-Proprietary-GoSiteMe      (commerce, vault, never published)
```

---

## Trademarks

"Alfred", "Alfred Linux", "Omahon Seal", "Kingdom of God Edition", and the
crown / seal logos are trademarks of GoSiteMe Inc. Code license does not grant
trademark rights — see `TRADEMARKS` for community usage rules (forks: yes;
implying official endorsement: no).

---

## Changelog

- 2026-04-24 — Initial unified LICENSING.md (was scattered across pages).
