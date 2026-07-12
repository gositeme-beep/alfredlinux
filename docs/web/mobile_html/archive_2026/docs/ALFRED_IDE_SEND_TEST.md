# Alfred IDE (`/alfred-ide/`) — send button & smoke tests

## What “Send” does

1. **Browser →** `relay.js` (`/alfred-ide/static/js/relay.js`) or the **Alfred Voice** extension panel uses **`POST /middleware/api/voice-relay/connect`** then **`POST .../send`** with your **`alfred_ide_token`** cookie as **`Authorization: Bearer …`** and **`X-Alfred-IDE-Token`**.
2. **Middleware** (`gocodeme` on `127.0.0.1:3001`) runs the voice-relay session; in **text-only IDE mode** it forwards to **`/api/alfred-chat.php`**.

## Manual test (recommended)

1. Log in to **GoSiteMe** with your account (**Autopilot** or any WHMCS client with **active service/domain** — required by `alfred-ide-gate.php`).
2. Open **`https://gositeme.com/alfred-ide/`** (you’ll hit **`alfred-ide-auth.php`** if needed).
3. In the IDE, open the **Alfred** side panel (extension).
4. Choose an **agent** in the dropdown (e.g. Alfred, Scout).
5. Type: *“Summarize what this workspace is.”* and click **SEND** (or Enter).
6. Optional: ask Alfred to *“Add a weather demo page under `/demo/alfred-weather/`”* — a static sample already lives at **`/demo/alfred-weather/`**.

## Server-side relay smoke (no browser)

Requires middleware listening on **3001** (see `middleware/proxy.php`):

```bash
SID=$(curl -sS -X POST http://127.0.0.1:3001/api/voice-relay/connect \
  -H 'Content-Type: application/json' \
  -d '{"text_only":true,"ide_chat":true}' | php -r '$j=json_decode(stream_get_contents(STDIN),true);echo $j["session_id"]??"";')
curl -sS -X POST http://127.0.0.1:3001/api/voice-relay/send \
  -H 'Content-Type: application/json' \
  -d "{\"session_id\":\"$SID\",\"message\":{\"type\":\"text\",\"text\":\"ping\",\"agent\":\"alfred\"}}"
```

Expect `{"ok":true}`. Full LLM replies need a **logged-in IDE token** and billing/API path — use the browser for that.

## Sample weather page

- **URL:** `https://gositeme.com/demo/alfred-weather/`
- **Files:** `public_html/demo/alfred-weather/index.html`

## Troubleshooting

| Symptom | Check |
|--------|--------|
| Redirect to login | Active **service/domain** for client; complete **`alfred-ide-auth`**. |
| Send does nothing | DevTools → Network → **`voice-relay/send`** status; token cookie present. |
| 502 “starting up” | **code-server** on `127.0.0.1:8443` and **middleware** on **3001**. |

### Fix (2026-03): Set-Cookie handling bug in extension (real “used to work” regression)

Node’s `incomingMessage.headers['set-cookie']` can be a **string** (one cookie) or an **array** (several). The extension did `for (const c of setCookie)` — when the value is a **string**, JavaScript iterates **each character**, so **`PHPSESSID` was never parsed**. CSRF retries then sent requests **without** the PHP session cookie → perpetual “Session initialized. Please retry.” / failed Send.

**Fix:** normalize with `Array.isArray(setCookie) ? setCookie : [setCookie]` before looping.

### Fix (2026-03): Send failing after cache / CSRF “retry” loops

The Alfred Voice **extension** calls **`/api/alfred-chat.php`** from Node (`https.request`), not the browser — **PHP session cookies often don’t line up**, and some stacks strip **`Authorization`** / **`X-Alfred-IDE-Token`**.

**Server:** `alfred-chat.php` now (1) accepts **`ide_session_token` inside the JSON body** and injects it before Bearer resolution, and (2) **skips CSRF** when a valid signed **`ide_sig`** is present for **`channel: ide-chat`** (same crypto as the existing IDE identity block).

**Extension:** payloads include **`ide_session_token`** when `session.json` has a token.

After deploying, run **Developer: Reload Window** in the IDE (or restart code-server) so the extension picks up the new `extension.js`.
