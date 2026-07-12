# Forensic security audit — Alfred IDE / chat / voice-relay stack

**Date:** 2026-03-22  
**Scope:** GoSiteMe production paths: `/alfred-ide/`, `alfred-ide-gate.php`, `alfred-ide-auth.php`, `/api/alfred-chat.php`, `/middleware/*` → Node middleware (`gocodeme/middleware`), `voice-relay`, Alfred Voice VS Code extension (`gositeme.alfred-voice`), `relay.js`, supporting includes (`alfred-ide-bearer.inc.php`, `proxy.php`).  
**Method:** Static code review, control-flow tracing, trust-boundary analysis (no live penetration test).  
**Classification:** Internal operations — restrict distribution.

---

## 1. Executive summary

The Alfred stack is **multi-hop** (browser → Apache → PHP / code-server → Node extension → HTTPS → PHP API → Node middleware → PHP). **Authentication** mixes **PHP sessions**, **opaque IDE session tokens** (`alfred_ide_users.session_token`), **HMAC-signed IDE identity** (`ide_sig`), **internal relay secret**, and **optional JWT** (GoCodeMe). Several **compensating controls** exist (prepared statements in reviewed paths, path allowlists, `hash_equals`, rate limits). **Residual risks** concentrate on **token handling in JSON bodies**, **fallback HMAC secrets**, **internal relay trust**, and **broad CSRF exemptions** when IDE auth succeeds.

**Verdict:** **No single “smoking gun” remote code execution** was identified in the reviewed files, but **configuration-dependent** issues (secrets, logging) can **elevate** misconfigurations to **critical**. Treat **production secrets** as mandatory, not optional.

---

## 2. Architecture (trust boundaries)

```
[Browser] --cookie/HTTPS--> [Apache + alfred-ide-gate] --> [code-server :8443]
     |                                                              |
     |                         session.json (token bridge)          v
     |                                                      [Extension Node]
     |                                                              |
     +------------------HTTPS JSON POST------------------------------+
                                    |
                                    v
                          [api/alfred-chat.php]
                                    ^
     [Browser/widget] --middleware--> [voice-relay :3001] --HTTPS-->+
```

**T1 — Browser ↔ gositeme.com:** TLS, cookies (`HttpOnly`, `Secure`, `SameSite=Lax` on IDE token paths per codebase).  
**T2 — Extension ↔ PHP:** `https.request` to `gositeme.com` — **no browser cookies**; relies on **Bearer / `X-Alfred-IDE-Token`** and **JSON `ide_session_token`** (recent fix).  
**T3 — voice-relay ↔ alfred-chat:** Server-side HTTPS; may send **`X-Internal-Secret`** + IDE token.  
**T4 — Internal relay:** `X-Internal-Secret` matches `INTERNAL_SECRET` → **trusts `client_id` in JSON body** (documented in `alfred-chat.php`).

---

## 3. Findings (severity-ordered)

### CRITICAL

| ID | Finding | Evidence / mechanism | Risk |
|----|---------|----------------------|------|
| C-1 | **Default HMAC fallback** in `alfred-chat.php` (and similar): `gositeme-alfred-hmac-2026` when `ALFRED_HMAC_SECRET` unset | `hash_hmac` early CSRF skip + IDE identity block use same pattern | If deployment **does not** set `ALFRED_HMAC_SECRET` to a **unique** value, **forged `ide_sig`** for a chosen `ide_client_id` + `ide_ts` within window is **theoretically possible** for anyone who knows the default (e.g. from source). |
| C-2 | **`INTERNAL_SECRET` / relay** | If `INTERNAL_SECRET` is empty, internal relay path is **not** used; if **leaked**, relay can **impersonate** `client_id` in `alfred-chat` body (`alfred-chat.php` internal relay block). | **Full account linkage** to WHMCS `client_id` as coded in relay. |

**Remediation:** Enforce **non-default** `ALFRED_HMAC_SECRET` and `INTERNAL_SECRET` in production; **rotate** if ever exposed; **never** commit real values to git.

---

### HIGH

| ID | Finding | Evidence | Risk |
|----|---------|----------|------|
| H-1 | **`ide_session_token` in JSON body** | `alfred-chat.php` injects into `$_SERVER['HTTP_X_ALFRED_IDE_TOKEN']`; extension sends `ide_session_token` | **TLS** protects in transit; **server-side logs**, WAF bodies, crash dumps, or **compromised log pipeline** may capture **long-lived session material**. |
| H-2 | **CSRF skipped** when `ideBearerAuthOk` OR `csrfSkipIdeSigned` | Single POST can skip CSRF if valid token or valid HMAC | **Stolen token** or **forged HMAC** (if C-1 applies) → **no CSRF** second factor for that request class. |
| H-3 | **`alfred-ide-session.php` CORS** | `Access-Control-Allow-Origin: *` on GET | Session profile endpoint **does not** return raw token in reviewed JSON; still **broadens** cross-origin reads if response ever **enriched** with secrets. |

**Remediation:** **Redact** `ide_session_token` in app logs; **structured logging** with field blocklist; **restrict CORS** to `https://gositeme.com` if feasible; **shorten token TTL** and **rotate** on privilege change.

---

### MEDIUM

| ID | Finding | Evidence | Risk |
|----|---------|----------|------|
| M-1 | **Voice session `session_id`** | `crypto.randomBytes(16).toString('hex')` — **not** bearer-bound for anonymous `/connect` | **Session fixation** unlikely (high entropy); **session hijack** if `session_id` leaked via URL/logs. |
| M-2 | **Optional session on voice-relay** | `optionalSession` — hex IDE token without `.` bypasses JWT; JWT path uses GoCodeMe session | **Mis-typed** route could allow **guest** text-only chat; **cost/abuse** via rate limits (see M-3). |
| M-3 | **Rate limits** | `alfred-chat.php` `checkRateLimit()`; middleware global limiter | **Shared IP** users may hit limits; **not** a bypass, but **availability** issue. |
| M-4 | **IDE gate paid access** | `ideGateHasPaidAccess()` — services/domains Active | **Business rule**, not cryptographic; **mis-synced** DB state could **deny** or **allow** incorrectly. |

---

### LOW

| ID | Finding | Evidence | Risk |
|----|---------|----------|------|
| L-1 | **`session.json` bridge** (`logs/alfred-ide/session.json`, `~/.alfred-ide/session.json`) | Token on disk for extension | **Host compromise** or **overly permissive** file ACL → token theft. |
| L-2 | **Subprocess use in `alfred-chat.php`** | `pdftotext` via `exec` with constructed paths | Review **PDF path** sanitization in surrounding code (not fully audited here). |
| L-3 | **GoHostMe diagnostics** (`server.js`) | `execFileSync` with **fixed** argv | **No** arbitrary shell in reviewed snippet — **good**. |

---

### INFORMATIONAL

| ID | Finding | Note |
|----|---------|------|
| I-1 | **Authorization header** | `api/.htaccess` sets `HTTP_AUTHORIZATION` — **correct** for CGI/FPM. |
| I-2 | **`proxy.php` header injection** | `\r\n` stripped in cookie forwarding — **good**. |
| I-3 | **Prepared statements** | IDE user lookup uses `prepare` + bound `session_token` hash — **good**. |

---

## 4. “Send not working” — forensic root cause (historical)

**Symptom:** Alfred IDE **Send** failed after cache clear.

**Technical cause (code-level):** Extension uses **Node HTTPS** to `alfred-chat.php` **without** reliable **PHP session** continuity; **CSRF** path **and/or** **stripped** `Authorization` / `X-Alfred-IDE-Token` caused **failed** or **retry-loop** behavior.

**Mitigations applied (same codebase period):** JSON **`ide_session_token`**, **CSRF skip** on valid **`ide_sig`** for `ide-chat`, extension payload update.

**Residual verification:** Confirm **production** `ALFRED_HMAC_SECRET` is set and **matches** extension `getAlfredHmacSecret()` resolution.

---

## 5. Recommended controls (priority)

1. **Secrets:** `ALFRED_HMAC_SECRET`, `INTERNAL_SECRET`, `JWT_SECRET`, DB creds — **vault-only**, rotation policy.  
2. **Logging:** Never log `ide_session_token`, `Authorization`, or full `POST` JSON for chat.  
3. **Monitoring:** Alert on **spike** of `403` / `csrf_refresh` / `401` on `/api/alfred-chat.php`.  
4. **CORS:** Tighten `alfred-ide-session.php` if no cross-origin requirement.  
5. **Dependency:** Periodic `npm audit` / Composer audit on middleware and public APIs.  
6. **Incident:** If token leak suspected — **invalidate** all `alfred_ide_users.session_token` rows (rotate login) and **re-issue** cookies.

---

## 6. Out of scope (not reviewed)

- WHMCS core, DirectAdmin, MySQL privilege model, **live** traffic captures, **mobile** apps, **full** `vapi-tools.php` / `alfred-search.php` line-by-line.  
- **Penetration test** and **runtime** SAST/DAST.

---

## 7. Sign-off

This document is a **point-in-time** forensic review based on **repository state**. It is **not** a compliance certification (SOC2, PCI, etc.).

**Prepared by:** Automated audit (Cursor agent) + static analysis.
