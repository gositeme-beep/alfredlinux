# Alfred — VAPI phone assistant (dashboard checklist)

**Primary sync:** regenerate MCP tool JSON when `gocodeme/mcp-server/src/tools.js` changes, then push the assistant:

```bash
cd /path/to/public_html
node scripts/generate-vapi-mcp-tools.mjs   # writes scripts/generated/vapi-mcp-tools.json (~800 tools)
php scripts/update-vapi-assistant.php      # merges + PATCHes Vapi (expect ~895 tools total with PHP voice tools)
```

That **PATCHes** the live assistant via `https://api.vapi.ai/assistant/{id}` (system prompt, **firstMessage**, **full tool list**).  
Optional tweaks (voice ID, advanced Vapi-only settings) can still be edited in the [Vapi dashboard](https://dashboard.vapi.ai).

Code fixes in `api/vapi-webhook.php` handle webhooks (duration, transfers, etc.).

## Environment (server)

| Variable | Purpose |
|----------|---------|
| `VAPI_WEBHOOK_SECRET` | Must match dashboard server URL secret (`X-Vapi-Secret`). |
| `VAPI_ASSISTANT_ID` | Default assistant for `assistant-request` (inbound). |
| `VAPI_DEFAULT_TRANSFER_E164` | E.164 number for **`transfer-destination-request`** (e.g. `+15145550100`). If unset, webhook returns an error JSON so the model should offer **callback**, not live transfer. |

## P0 — Brand & speech

- **Company name in writing:** always **GoSiteMe** (one word, camel case). Never **GoCiteMe** (common TTS misread).
- **Spoken name:** add a pronunciation hint in the assistant notes, e.g. *“Say ‘Go Site Me’ — three words.”* so the opening line matches marketing.
- **First message:** use that pronunciation; avoid spelling “.com” unless needed.

## P0 — Security (anonymous callers)

Until the caller is **verified** (account email + PIN / ticket / WHMCS match):

- Do **not** disclose: internal service names, CPU/load, how many services up/down, “commander” paths, fleet counts, Redis/Ollama/MCP process names, or detailed infra status.
- You may say: *“Everything on our side is running; I can help with billing or hosting once we verify your account.”*

## P1 — Tools

- Do **not** quote changing numbers (e.g. “88 tools”). Say: *“I have a full hosting and account toolkit; the IDE has the complete MCP catalog.”*

## P1 — Transfers & sales

- If **`VAPI_DEFAULT_TRANSFER_E164`** is **not** set, **never** say *“transferring now”* — say *“I’ll have [name] call you back at [number]”* and confirm the number.
- If the env **is** set, warm transfer can work when Vapi sends `transfer-destination-request` to your server URL.

## P1 — Honesty

- Do not promise specific product names (e.g. internal codenames) or full Zoho/integration architectures unless documented for phone support — **qualify** and **schedule** a human or demo.

## Troubleshooting — “Alfred sounds broken” / last calls empty

1. **Fail2Ban vs Vapi (HTTP)**  
   Phone tools hit **`/api/vapi-tools.php`** (and webhooks **`/api/vapi-webhook.php`**). The **`gositeme-access-probes`** jail matches **scanner URLs** (wp-login, `.env`, etc.), **not** normal Vapi tool POSTs. Check:  
   `fail2ban-client status gositeme-access-probes` → **Banned IP list** should be empty for Vapi issues.  
   **SSH** jails (`sshd`, `sshd-ddos`) ban brute-force IPs — **does not block Vapi HTTPS** to your site.

2. **Vapi static IPs (optional firewall allowlist)**  
   If you enable static IPs in Vapi, webhooks can come from **`167.150.224.0/23`** (see [Vapi docs](https://docs.vapi.ai/security-and-privacy/static-ip-addresses)). Default Vapi IPs are dynamic — use **`VAPI_WEBHOOK_SECRET`** verification instead of IP guessing.

3. **Last call row shows `transcript` NULL**  
   Usually **webhook lag**, **call still in progress**, or **end-of-call events not yet received**. Not a Fail2Ban symptom.

4. **Server load / disk**  
   High **load average** or **iowait** slows tool execution (MCP, DB) — Alfred can sound “stuck” or repeat. Fix **MySQL slow queries**, **disk**, and **PHP-FPM pool** sizing before blaming the model.

---

*Last updated: 2026-03-22 — aligns with `alfred_call_log` transcript audit.*
