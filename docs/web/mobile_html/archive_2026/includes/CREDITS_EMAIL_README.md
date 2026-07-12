# Share Credits Email – Gmail / Deliverability

## Immediate steps for pascalavs@gmail.com

1. **Check Spam and Promotions**  
   Gmail often puts automated/transactional mail in **Spam** or **Promotions**. Ask them to check both and search for your sender address or “credit”.

2. **Resend if possible**  
   If your admin/Share Credits screen has a “Resend notification” or you can trigger the same email again, send it once more. Sometimes the first send is filtered and a second gets through.

3. **Manual fallback**  
   You can send a one-off email from your normal mailbox (e.g. support@gositeme.com) with the same info: “You received 33 credits …” and a link to log in. That usually lands in Inbox.

4. **Confirm the address**  
   Double-check the address used was exactly `pascalavs@gmail.com` (no typo, no space).

---

## Logging and BCC (recommended)

Use the logger so you have a record of every credit-share email and can resend from the log if needed.

- **Log file:** `logs/credit_share_emails.log`  
  Format: `date time \t email \t amount \t description`

- **In your Share Credits / admin credits code**, after sending the email, call:
  ```php
  require_once __DIR__ . '/includes/credit_share_email_log.php';
  log_credit_share_email('pascalavs@gmail.com', 33, 'Share Credits');
  ```

- **Optional BCC**  
  So you always have a copy even when Gmail filters it:
  - Define `CREDIT_SHARE_BCC_EMAIL` (e.g. in your config) to your admin/support address.
  - In the code that sends the credit-share email, add that address as BCC (e.g. using `get_credit_share_bcc()`).

---

## Why Gmail might filter the message

- **Content:** Words like “credit”, “gift”, “transfer” can trigger filters.
- **Sender:** Mail from a generic/no-reply address or a server that doesn’t match the domain can be treated as suspicious.
- **Reputation:** New or low-volume sending domains often get filtered more.

**Improving deliverability (medium term):**

- Use a proper **From** address (e.g. `noreply@gositeme.com` or `support@gositeme.com`) and keep it consistent.
- Ensure **SPF** and **DKIM** are set for the domain you send from.
- Prefer **SMTP** (e.g. through your host or a provider like SendGrid/Mailgun) instead of PHP `mail()` when possible.
