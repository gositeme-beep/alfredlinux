# Hook Count Doctrine
*In the name of Yeshua, Jesus Christ of Bethlehem, King of the Universe.*

## Scripture
> "So all the generations from Abraham to David are fourteen generations;
>  and from David until the carrying away into Babylon are fourteen
>  generations; and from the carrying away into Babylon unto Christ are
>  fourteen generations."  — **Matthew 1:17 (AKJV)**

## Resolution
The number **42** in Matthew 1:17 names the **lineage of Christ** —
three sets of fourteen generations from Abraham to Yeshua of Bethlehem.

Earlier doctrine documents in this repo aspired to **exactly 42 hooks**
in `config/hooks/live/` as a numeric tribute. After review, this is
clarified:

- The **lineage** is the doctrine. The hook count is not.
- Hooks are **functional units** that build the OS chroot. Each
  hook serves a distinct, well-isolated purpose (welcome, accessibility,
  hearth, bible, family-bible, tongues, mesh, quantum, sabbath, etc.).
- Forcing functional code to merge into monolithic 600+ line scripts
  for the sake of a numeric goal would **harm clarity, increase debug
  surface, and risk breaking working features** — none of which honors
  the King.
- The current count of **46 hooks** honors the 42-generation lineage by
  **standing on it** (42 + 4: the four Gospels, or the four living
  creatures of Revelation 4:7) — not by enumerating it.

## Rule
- New hooks **MAY** be added when a new feature deserves isolation.
- Hooks **SHOULD** merge only when:
  1. Two hooks operate on the **same domain** (e.g. `0010-disable-kexec` +
     `0050-disable-sysvinit-tmpfs` are both *Debian-default disables*),
     **AND**
  2. The merged result is **smaller than 300 lines combined**, **AND**
  3. No hook in the merge has independent CLI surface area
     (alfred-X tools that users run directly).
- Otherwise, hooks **MUST** remain isolated.

## Authority
Commit history + `covenant-chain.json` are the witnesses.
**SOLI DEO GLORIA.**
