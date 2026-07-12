---
name: security-audit
description: "OWASP-focused security audit methodology for PHP/JS web applications"
---

# Security Audit Methodology

When auditing code for security vulnerabilities, systematically check each category:

## 1. SQL Injection
- Search for raw SQL string concatenation: `"SELECT.*" . $`, `query(".*$`
- Verify ALL database queries use PDO prepared statements with `?` or `:named` parameters
- Check for query builder methods that accept raw input
- Red flags: `$_GET`, `$_POST`, `$_REQUEST` used directly in SQL

## 2. Cross-Site Scripting (XSS)
- Search for `echo $_`, `<?= $_`, `print $_` — any direct output of user input
- Verify all user-controlled output uses `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')`
- Check JavaScript for `innerHTML =`, `document.write(`, `.html(` with user data
- Check for reflected XSS via URL parameters displayed on page
- Look for `json_encode()` output inside `<script>` tags without proper escaping

## 3. CSRF Protection
- Every POST/PUT/DELETE form MUST have a CSRF token
- Verify token validation: `$_POST['csrf_token'] === $_SESSION['csrf_token']`
- API endpoints should validate `Origin`/`Referer` headers or use tokens
- Check AJAX requests include CSRF tokens in headers

## 4. Authentication & Authorization
- Protected pages must include auth check (`auth-check.inc.php` or equivalent)
- Verify session management: `session_regenerate_id()` on login
- Check for privilege escalation: can user A access user B's data?
- Look for hardcoded credentials or API keys in source code

## 5. Path Traversal
- Search for `file_get_contents($`, `fopen($`, `include $`, `require $`
- Verify file paths are validated: no `../` sequences allowed
- Check `realpath()` is used to canonicalize paths before access
- File upload handlers must validate extensions AND content

## 6. Command Injection
- Search for `exec(`, `shell_exec(`, `system(`, `passthru(`, `popen(`
- All shell commands must use `escapeshellarg()` or `escapeshellcmd()`
- Never pass user input directly to system commands

## 7. Information Disclosure
- Check error reporting: production should NOT show stack traces
- Search for `var_dump`, `print_r`, `phpinfo()` in committed code
- Verify `.env` files are in `.gitignore`
- Check for sensitive data in HTML comments or JavaScript

## Report Format
For each finding:
```
[SEVERITY: Critical/High/Medium/Low]
File: path/to/file.php
Line: 42
Issue: Description of the vulnerability
Impact: What an attacker could do
Fix: Specific code change needed
```
