# Security Findings — external review

> **What this is:** a read-only, static security assessment of Alfred Linux's high-risk
> surfaces (the PHP web app + control panel, the Node services, and secrets/build/supply-chain),
> contributed by an outside reviewer alongside the testing/CI assessment. **Suggestions only —
> nothing in the codebase was changed.** Locations are cited (`file:line`); the literal secret
> *values* are deliberately **not** reprinted here.
>
> **Method + limits (read before acting):** static review only, no code executed. Two classes of
> finding are **deployment-conditional** and need the maintainer's confirmation: (a) whether
> `.php` executes under the upload directories (governs the upload-RCE severity), and (b) which
> secrets are actually set in the production environment (governs the "fail-open" items).
> Coverage was **sink-driven** across grep passes, not a 100% line-by-line read of every file —
> three very large files (`vapi-tools.php`, `alfred-chat.php`, `agent-panel.php`) were sampled at
> sinks. False positives were actively ruled out (a honeypot "AWS key", doc-example secrets).

## Fair framing — a lot is built correctly
This is **not** "the app is insecure" — it's a specific, fixable cluster of unauthenticated
endpoints plus committed secrets, against an otherwise disciplined codebase:
- **First-order SQL injection: clean across all 338 `gohostme/api/` files** — parameterized via
  `dbExecute()`/prepared statements (`EMULATE_PREPARES=false`), int-clamped `LIMIT`, allowlisted
  `ORDER BY`/table/column. Genuinely good discipline.
- The `alfred-oauth` server, `oauth.php`, Stripe + Vapi webhooks, `kingdom-vault`, `support-pin`
  (bcrypt), `sign.php`, `black-vault.php`, and the control-plane (`api.php`/`billing`/`worker` —
  which **fail closed**) are correctly built. `api-security.php` ships strong header/rate-limit
  defaults; `includes/safe-redirect.php` is a solid same-origin allowlist.

---

## Rotate first — credentials already public in source / shipped in the ISO
These are committed to a public repo (and some ship inside every ISO), so treat them as burned:
- **`root_whmcs` DB password** — hardcoded at `gohostme-backend/server.js:26` & `:950`,
  `commander.php:137`, `control-plane/bootstrap.php:24`, and set as the default in the systemd
  unit written by `config/hooks/live/0033-alfred-gohostme-api.hook.chroot:25` (ships in every ISO).
- **Shared 64-hex internal API secret** — identical across `gohostme/api/{growth-engine,
  legal-audit,pulse-population,self-governance}.php:18` (also `self-governance.php:274`).
- **Vapi API key** — `gohostme/api/vapi-webhook.php:909`.
- **HMAC-secret fallbacks** — `dashboard.php:2581`, `includes/site-header.inc.php:369`
  (`root-alfred-hmac-2026`); `alfred-chat.php:108/228`; canary key `veil-fortress.php:531`;
  `monitoring-fleet.php:214`.
**Fix pattern:** source every secret from env, fail closed if unset (the code already does this
correctly for `GOSITEME_DB_PASS` in `db-config.inc.php`), and scrub from git history
(`gitleaks`/`trufflehog` over full history — this review saw only a shallow clone).

---

## CRITICAL

**C1 · DB password ships in every ISO** — see "Rotate first" above. Full DB (client PII, billing)
compromise for anyone reading the repo/ISO if the credential is live anywhere. Rotate + fail-closed.

**C2 · `admin-build-orchestrator.php:11` — unauth root operations.** Sole auth is a hardcoded key
in the public source (also printed in the file's header comment, `:5`); past it, top-level code
runs `proc_open` (`:24`) and SSH-`sudo` as root (`:53-58`) over GET (so CSRF-triggerable).
**Fix:** real authenticated session + env-sourced secret via `hash_equals`; POST + CSRF; don't ship
this endpoint in a public image.

**C3 · Four more shell/build/docker endpoints gated by hardcoded, committed keys** —
`alfred-pipeline.php:27`, `build-ga-iso.php:10`, `enopro-bridge-launch.php:18`,
`admin-build-status.php:7`. Commands are `escapeshellarg`'d (not classic injection); the
exploitable class is **auth-bypass via a public key** → privileged build/docker/sudo. **Fix:** as C2.

**C4 · `alfred-build-dashboard.php` — no auth + discloses a key to web visitors.** No auth gate at
all; embeds the working `enopro` key in clickable HTML buttons (`:66,69,72`) and prints it (`:158`),
so the C3 key reaches any visitor, not just repo readers. **Fix:** gate the page; never render
operational secrets into HTML; rotate.

**C5 · `gohostme/api/service-governance.php` — entire endpoint unauthenticated.** Verified: **zero**
auth checks across 871 lines. `create-api-key` (`:381`) lets anonymous callers mint live `gsm_*`
keys incl. **enterprise** tier; `create/vote-proposal`, `create/assign/complete-job` enable
governance + GSM-currency fraud; IDOR on `api-keys` (`:371`) / `api-usage` (`:409`). **Fix:** require
an authenticated principal before the switch; derive `owner_id` from the session, never the request.

**C6 · `gohostme/api/audio.php:132` — unauthenticated upload → (likely) RCE.** No auth; extension
taken from the client filename (`:125`), only `finfo` MIME-sniffed (`:118`, polyglot-bypassable);
written into docroot. **Contingent:** RCE depends on `.php` executing under the upload dir —
**maintainer must confirm the Apache handler**; if it doesn't, it's still unauth arbitrary file
write. Same pattern: `voice-clone.php:155` (authed). **Fix:** derive extension from a fixed MIME→ext
map, require auth, store outside docroot and/or `php_admin_flag engine off` for upload trees.

---

## HIGH

- **H1 · `server.js:304` — MD5/plaintext password fallback.** Login accepts unsalted MD5 *and*
  plaintext equality; hardcoded superuser `client_id === 33`. **Fix:** bcrypt/argon2 only; drop the
  magic id.
- **H2 · `public_html/api/alfred.php` — unauth + rate-limit bypass → paid-LLM spend.** No auth,
  CORS `*`; the IP limit is skipped via `{"urgency":"high"}`/`{"context":"mercy"}` (`:48`). Anonymous
  financial DoS. **Fix:** enforce the limit before the bypass; cap it; restrict CORS.
- **H3 · Fail-open auth on empty secrets.** Where a secret defaults to `''` and is then compared,
  an unset env var authenticates a no-header caller: `vapi-outbound.php:25` (trigger outbound
  calls), `vapi-auth.php:34` (PIN/PII brute-force), `stripe.php:686` (forge `checkout.session.
  completed` → grant paid plans). **Fix:** fail closed on empty; `hash_equals`; startup guard.
- **H4 · `agent-panel.php:4831` — command injection (post-auth, CSRF-drivable).** `$pm2Name` from
  the JSON body is interpolated raw into `shell_exec`. Commander-gated but **no CSRF check seen** →
  CSRF-to-RCE. **Fix:** allowlist-validate + `escapeshellarg` (sibling handlers already do).
- **H5 · `webhooks.php` → `webhook-dispatch.php:242` — authed SSRF + response exfiltration.** URL
  validated `FILTER_VALIDATE_URL`+`https://` only; any authed user can point a webhook at
  `127.0.0.1`/internal hosts and read back the first 500 bytes. **Fix:** reject RFC1918/loopback/
  link-local; pin the resolved IP; don't return the upstream body.
- **H6 · `--privileged --network=host` Docker build mounting a secrets dir.** `alfred-build.sh:212`,
  `lb-docker-build.sh:47` build with full host access; `-v /home/gositeme/law:/host_law:ro`
  (`alfred-build.sh:224`) mounts a directory the repo's own `scripts/ops/README.md:90` says holds
  ops secrets — into a privileged container installing many third-party packages as root. (The
  kernel build proves `--privileged` is avoidable.) **Fix:** minimum caps, drop the unused mount.
- **H7 · `npm install --unsafe-perm` as root during the ISO build.**
  `config/hooks/live/1335-…:5412` installs `/opt/handshake` + `sovereign-dns` with `--unsafe-perm`
  (disables install-script privilege drop) while the `hsd` tree has `hasInstallScript` deps
  (bcrypto/bdb/…) → a compromised dep = root code-exec at build. **Fix:** `npm ci --production`,
  drop `--unsafe-perm`.

---

## MEDIUM (11)
CSRF skipped for any `Authorization: Bearer` prefix without verifying the token (`api-security.php:66`) ·
`run_renewals.php:22` `CURLOPT_SSL_VERIFYPEER=false` on the cron that POSTs the control-key (MITM) ·
`veil-fortress.php:531` hardcoded canary HMAC key (forgeable warrant-canary) ·
`bridge.sh:185/240` SQL built by `'`-only-escaped interpolation (backslash-breakout SQLi; RED-gated) ·
`api.php:50/64` plaintext passwords persisted into `control_jobs.payload_json` and returned to key
holders · billing internal-error disclosure (`billing/index.php:640`, `worker.php:73`) ·
stored XSS `senate.php:536` · **no CSP** on any full-page PHP (`site-header.inc.php`) ·
redirect-based SSRF `alfred-transcribe.php:261` (`FOLLOWLOCATION` not re-validated) ·
`remote-desktop.php:263` denylist-not-allowlist "terminal" (`proc_open`, strong-auth-gated) ·
second-order SQLi `agent-panel.php:5144/5583` (bind the loop var).

## LOW (8)
Open redirect in the `public_html/sso-verify.php:107` copy (the main-app `safe-redirect.php` is
fine) · capability tokens in query strings (log/Referer leak) · `bridge.sh:1740` chpasswd newline
injection (god-key required) · `vapi-auth.php:134` phone-field bug (fails closed) ·
`feeds.php`/`vapi-tools.php`/`bible-export.php` path-write/LFI (admin/secret-gated) ·
`veil-vault`/`web-search`/`gocodeme-ide` minor · Handshake DB uses the high-privilege `root_whmcs`
account for read-mostly work (least-privilege) · `social-crosspost.php:116` unclamped limit.

---

## Out of the stated scope, but surfaced during tracing (worth the maintainer's attention)
- **`run-diag.php`** (no auth) **decrypts and prints vault SSH creds** (hosts/usernames/password
  lengths) to any caller; **`tmp-rb-proc.php`** attempts an unauth SSH `sudo /sbin/reboot`.
- **`gateway.php:273`** auto-authorizes any request where `REMOTE_ADDR == 127.0.0.1` (spoof / SSRF
  pivot).
- **`public_html/api/*`** re-implements endpoints and **bypasses the `api-security.php` middleware
  entirely** — fixes in the middleware don't reach `alfred.php`/`download-stats`/`download-status`.
- **Systemic upload weakness:** a `finfo` content-sniff is the sole gate across ≥4 upload endpoints
  (audio, voice-clone, veil-vault, human-passport), and upload dirs are created under webroot with
  no PHP-disabling drop-in. A shared hardened upload helper + shipped `engine off` config would fix
  the class at once.
- **Build/supply-chain:** deprecated `apt-key add` over HTTP for the gvisor key (a later duplicate
  hook overwrites an earlier hardened `signed-by` one); an unpinned `debian:trixie` base; an
  unattended `git push origin master` to prod with silenced failures (`post_build_pipeline.sh`);
  the kernel supply-chain audit doc (`KERNEL-7.0.4-…`) is stale vs the `7.0.12` actually built.
- **Policy call (not a vuln):** the desktop ISO installs `xmrig` (Monero miner), `mdk4` (Wi-Fi
  deauth), `sqlmap`, `hashcat`, `bettercap` by default (`config/package-lists/alfred.list.chroot`).
  Legit Debian packages — just worth a conscious choice for a general-purpose desktop image.

## Recommended follow-ups (need tools/access not available in a static review)
1. **Full-history secrets scan** (`gitleaks` / `trufflehog` over the full-history repo — this review
   saw only a `--depth 1` clone, so rotated-but-still-in-history keys are invisible here).
2. **`npm audit`** against the real lockfiles (`gohostme-backend`, `handshake`, `sovereign-dns`) for
   concrete CVE IDs; plan the `multer` 1.x → 2.x migration (npm flags 1.x as vulnerable).
3. **Confirm the Apache PHP handler** for upload dirs (settles C6/`voice-clone` severity).
4. **Generate a real SBOM** (Syft/Trivy/dpkg-query) and diff against the package lists.

---

*External contribution — static, read-only assessment. Confirm the deployment-conditional items
before acting on their severity; treat the first run of any new gate as a baseline.*
