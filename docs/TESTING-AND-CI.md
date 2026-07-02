# Testing & CI — Assessment and Roadmap

> **What this is:** an outside contributor's read-only assessment of Alfred Linux's
> testing and CI posture, plus a concrete, phased plan to close the gaps. Nothing in
> the codebase was changed to produce this; it is a set of **suggestions**. This PR
> adds this doc and one small, merge-safe starter workflow — it does **not** write
> your tests for you (that's yours to own) and it does not gate anything that would
> fail today.

## TL;DR

The scaffolding for testing exists across every stack, but almost none of it actually
runs, and a few pieces are broken-on-arrival:

- **PHP** — `var/www/phpunit.xml` points at test suites (`tests/Api`, `tests/Security`,
  `tests/bootstrap.php`) that **do not exist**, so `phpunit` fails immediately. There is
  no Composer and no test anywhere.
- **Shell** — good test scripts (`tests/boot-test.sh`, `tests/hook-lint.sh`) exist but are
  **orphaned** — no CI or build step runs them. `hook-lint.sh` asserts an exact hook count
  of **1335**; the repo has **1369** today, so it fails on day one for a reason unrelated to
  any real defect. `shellcheck` in CI covers only **2 files** by default.
- **Node** — the 5 bundled services have no real `test` script (npm's failing stub or none)
  and no linting. `opt/hsd/` is an empty stub; `alfred-remote-ssh` is a VS Code
  extension manifest with no first-party code.
- **CI** — the canonical CI (`.gitea/workflows/`, mirrored to `.github/` + `.forgejo/`) runs a
  static security sweep + `shellcheck` on two files. It runs **no PHP, Node, or unit tests**,
  and would not catch a PHP syntax error.

The heavy verification you already have (ISO build, QEMU boot-smoke, chroot CIS audit) is
**good** — it just lives on your private self-hosted "night-shift" infra and isn't
reproducible by contributors. The fast, cheap gates below are the missing layer.

---

## 1. Current state (per stack)

### PHP (`config/includes.chroot_after_packages/var/www/`, `public_html/`)
- `phpunit.xml` declares `API` → `tests/Api`, `Security` → `tests/Security`, bootstrap
  `tests/bootstrap.php` — **none exist** (`find . -iname '*Test.php'` is empty). Running
  `phpunit` today errors out.
- **No Composer** anywhere (no autoload, no dependency pinning, no dev tools).
- No PHPStan / PHPCS / PHP-CS-Fixer config.
- Most page-level `.php` files are procedural, output-emitting templates (global `$_GET`/
  `$_SERVER`, `echo`/HTML, `header()` at file scope) — not unit-testable without refactor.
  (One file, `666decoder.php`, is actually pure HTML+JS under a `.php` extension.)
- `public_html/` is a near-duplicate of `var/www/` (domain-string differences only) — doubles
  the untested surface; worth templating rather than testing twice.

### Shell + OS build (`tests/`, `scripts/`, live-build config)
- `tests/boot-test.sh` (QEMU headless boot check), `tests/hook-lint.sh` (10 checks on
  `config/hooks/live/*.hook.chroot`), `tests/security-audit.sh` (CIS-style live-system audit)
  are well-written but **not referenced anywhere** — no workflow or script runs them.
- `tests/hook-lint.sh` hard-asserts `== 1335` hooks; actual count is **1369**. See
  `docs/HOOK-COUNT-DOCTRINE.md` — the check should derive from the doctrine / a range, not a
  frozen literal.
- **Name collision:** `tests/security-audit.sh` (live CIS audit, needs a booted/mounted target)
  vs `scripts/security-audit.sh` (the static grep sweep that *actually runs in CI*). Two very
  different scripts, same name — a real footgun for contributors.
- `shellcheck` in CI lints only `scripts/security-audit.sh` + `scripts/audit-law-wrappers.sh`
  by default; the full sweep is behind a `workflow_dispatch` input (`ALFRED_SHELLCHECK_ALL=1`)
  defaulting to `0`, so ~70 other `.sh` files are never linted automatically.
- The build (`scripts/alfred-build.sh` → Docker live-build) and the good smoke/gate logic
  (`scripts/ops/smoke-test-iso.sh`, `scripts/qemu-smoke-test.sh`, `scripts/ops/build-gates.sh`)
  run only on your self-hosted box — they are genuinely good but not portable/reproducible.

### Node services (`opt/*`, `public_html/extensions/alfred-remote-ssh/`)
| Service | Kind | Tests | Lint |
|---|---|---|---|
| `gohostme-backend` | first-party (Express monolith, `server.js` 1715 lines) | none | none |
| `handshake` | first-party glue over `hsd`/`hs-client` deps | stub | none |
| `sovereign-dns` | first-party (`.alfred`/`.sovereign` resolver, 800 lines) | stub | none |
| `hsd` | **empty stub dir** (only a `package.json`, `main` points at a missing `index.js`) | — | — |
| `alfred-remote-ssh` | VS Code extension manifest (no first-party code) | — | — |
- No `eslint` config anywhere; no `.nvmrc`/`engines` pinning (lockfiles are v3 → Node 16+ floor).

---

## 2. Fix these first (broken-on-arrival / quick wins)

1. **`phpunit.xml`** — either create the referenced `tests/` dirs + `tests/bootstrap.php`, or
   trim the config to the suites you actually add. Until then `phpunit` cannot run.
2. **`tests/hook-lint.sh` magic number** — replace the `== 1335` assert with a doctrine-derived
   or `>=` check so it stops failing on a stale literal.
3. **`security-audit.sh` name collision** — rename one (e.g. `tests/system-cis-audit.sh`) so the
   live-system audit and the static CI sweep aren't confusable.
4. **Add Composer** at `var/www/` — even with zero runtime deps, it unlocks PHPUnit/PHPStan/PHPCS
   as dev-deps and PSR-4 test autoloading. Low risk, no behavior change.
5. **Pin a PHP version** (`composer.json` `"php"`) and a **Node version** (`engines`/`.nvmrc`) —
   the code uses `str_contains()`/typed params (PHP 8.0+), but nothing pins it today.

---

## 3. Recommended tests (prioritized — you write these; this is the map)

### PHP — start with pure functions (zero setup, high value)
Tier 1 (no I/O, no globals — ideal first PHPUnit suite):
- `includes/input-validator.inc.php`: `validateEmail/Url/Phone/Slug/Json/DateRange/Pagination`,
  `sanitizeHtml`, `validatePassword`.
- `includes/validation.php` (`InputValidator` static methods) — note this duplicates logic from
  the above under a different name; worth de-duplicating.
- `includes/safe-redirect.php`: `root_safe_post_login_path()` — security-relevant (open-redirect);
  small enough to test exhaustively (`//`, `javascript:`, traversal, control chars).

Tier 2 (light coupling — drive via `$_SERVER` or an in-memory SQLite PDO):
- `includes/api-security.php`: `requireCSRF()`, `dbExecute()`.
- `includes/api-response.inc.php`: JSON envelope shape + redaction path.

Tier 3 (integration only — don't force into unit tests): page templates and
`admin-build-orchestrator.php`-style ops scripts. If you want coverage, use an HTTP smoke
test (PHP built-in server + `curl`, assert 200 + no warnings), not PHPUnit.

**Structure:** create `tests/Unit/`, `tests/Api/`, `tests/Security/` under `var/www/`;
`tests/bootstrap.php` `require_once`s the `includes/*.php` under test (no autoloader without
Composer). Keep DB/Redis-touching code out of the unit suites (see CI blockers below).

### Shell — lint everything, unit-test the pure bits
- `shellcheck` all `*.sh` (start `--severity=warning`, non-blocking; a first full run is a
  baseline — triage, then make `error`-severity blocking).
- `bats` (or `shunit2`) for pure helpers — good candidates: `scripts/shlib/*.sh`, and pure
  string/count helpers you can extract from `scripts/ops/*.sh` (avoid the docker/mount/apt
  side-effecting parts).
- Wire the **existing** `tests/hook-lint.sh` (cheap, no root) once the count is fixed.

### Node — per first-party service
- `gohostme-backend`: small refactor first — export `app` (or a `createApp()` factory) without
  calling `.listen()`, then `node:test` + `supertest` for route/middleware tests (login 401/400,
  `requireAuth`/`requireAdmin` gating). Mock the `mysql2` pool — don't hit real MySQL.
- `handshake`: `node:test` for `hns-domains.js` CLI dispatch (mock `hs-client`); test only the
  pure parts of `start-hsd.js`.
- `sovereign-dns`: `node:test` for cache-TTL + hop-by-hop header filtering (pure) first.
- `hsd`: **clarify the directory's purpose before writing anything** — it's an empty stub today.
- `alfred-remote-ssh`: no unit surface; at most a smoke check that `pack-vsix.sh` packages.

---

## 4. CI architecture

**Forge convention respected.** Your canonical CI is `.gitea/workflows/` (mirrored to
`.github/` + `.forgejo/`). New workflows should be authored there and mirrored the same way you
sync `security-audit.yml` (consider extending `scripts/sync-forgejo-actions-yaml.sh` to cover
additional files, or keep the copies in sync by hand). This PR adds the starter workflow to
`.gitea/` and `.github/`; mirror to `.forgejo/` per your convention.

**Two tiers — fast (hosted) vs heavy (self-hosted):**

### Fast gates — hosted runners, every push/PR
| Job | Runs | Blocking? |
|---|---|---|
| `php -l` syntax check (all `*.php` under `var/www`) | today | yes (should pass now) |
| `shellcheck` (all `*.sh`) | today | no at first (baseline), then block on `error` |
| `hook-lint.sh` | after the count fix | yes |
| PHPUnit + PHPStan(`includes/`, level 1) + PHPCS(report-only) | once tests + Composer exist | phpunit yes; static non-blocking at first |
| Node matrix (`npm ci`/`npm test`/lint per service, exclude `hsd`) | once real test scripts exist | yes when enabled |

Full YAML for each is below (§6). **Only the day-1-safe pieces are active in this PR's starter
workflow**; the rest are documented for you to enable as the tests land.

### Heavy gates — self-hosted only (keep as-is)
The Docker live-build + QEMU boot/ISO smoke tests cannot run on GitHub-/Gitea-hosted runners
(no `/dev/kvm` for nested virt, ~14 GB disk, multi-hour builds, `--privileged`). Your
`release-iso.yml` already runs `self-hosted`. The high-value move is to **wire your existing
verification into that self-hosted job** so it becomes reproducible:
- after `lb binary`: `bash tests/security-audit.sh build/chroot` (the CIS audit against the real
  chroot — exactly what `tests/README.md` suggests, but nothing calls it);
- then `scripts/qemu-smoke-test.sh <iso>` or `tests/boot-test.sh <iso>` (near-duplicates —
  `boot-test.sh` checks more: kernel version + critical-error grep);
- optionally fold in `scripts/ops/smoke-test-iso.sh` content assertions.

**CI blockers to know:** the PHP app needs **MySQL/MariaDB** and **Redis** for some paths, and
`db-config.inc.php` `die()`s without `GOSITEME_DB_PASS`. Keep those paths out of the unit suites
(Tier 1/2 above already avoid them); for integration tests, add `services:` (mysql + redis)
containers to the workflow, and set a dummy `GOSITEME_DB_PASS` so bootstrap can `require` files.

---

## 5. Security observations (out of scope for testing/CI — flagged for your attention)

Surfaced while reading the code; not part of this assessment's mandate, but worth a look:
- **Hardcoded fallback secrets:** `gohostme-backend/server.js` has `DB_PASS || '!q@w#e$r5t'`;
  the PHP layer expects `GOSITEME_DB_PASS`. A default password in source is a real risk.
- **Bare-string admin auth:** `admin-build-orchestrator.php` gates on `$_GET['k'] === 'BUILD-777'`
  and then shells out (`proc_open`/SSH) from top-level code — key-in-URL + arbitrary exec.
- A dedicated security pass (separate from testing) would be worthwhile.

---

## 6. Phased rollout (suggested)

1. **Phase 0 (this PR):** this doc + a merge-safe starter workflow (`php -l` blocking +
   `shellcheck` non-blocking). Green on merge; nothing new to fix.
2. **Phase 1:** the fix-first list (§2) — dangling `phpunit.xml`, the 1335→1369 assert, the name
   collision, add Composer + version pins. Wire `hook-lint.sh` into the fast job.
3. **Phase 2:** PHP Tier-1 PHPUnit (validators/redirect) + PHPStan level 1 on `includes/`.
4. **Phase 3:** Node per-service tests (start with `sovereign-dns`/`handshake` pure logic) + the
   `gohostme-backend` `app`-export refactor. Add `eslint`.
5. **Phase 4:** self-hosted job wires in the existing boot/chroot/ISO verification.
6. **Phase 5:** flip the baselines to blocking (shellcheck `error`, PHPStan level up, PHPCS gate).

The full copy-paste workflow YAML for each phase is kept alongside the starter workflow this PR
adds — see `.gitea/workflows/alfred-tests.yml` (and its commented "enable when ready" blocks).

---

*Prepared as an external contributor's suggestion. Assessment only — no application code or
tests were modified. Review everything before enabling; the first CI run of any new gate should
be treated as a baseline, not a pass/fail verdict.*
