# GoSiteMe Emergency Key Recovery & Login Instructions

## 1. What are the keys?
- **Vault Key:** Unlocks all encrypted files (passwords, credentials, break-glass, etc).
- **MariaDB Key:** Unlocks database encryption-at-rest (if enabled).

Both are 64-character hexadecimal strings (256 bits).

---

## 2. Where are the keys stored?
- **In RAM only** (tmpfs): `/run/user/1004/keys/vault.key` and `/run/user/1004/keys/mariadb.key`
- **After a reboot, these files are GONE.**  You must re-inject them to unlock the system.

---

## 3. How to recover GoSiteMe after a reboot

### A. SSH into your server
```
ssh gositeme@15.235.50.60
```
(Or log in as `ubuntu` and then run `sudo su - gositeme`)

### B. Run the key injection script
```
~/.vault/inject-key.sh
```
- Paste your vault key when prompted.
- Paste your MariaDB key when prompted.

### C. Script will verify and confirm
- If both keys are correct, you’ll see “PASS” and “LOADED.”
- Your site (logins, passwords, Commander, etc.) will work again.

---

## 4. How to generate new keys (if needed)
```
head -c 32 /dev/urandom | xxd -p
```
- This gives you a new 64-character hex key.

---

## 5. If you ever need to rotate keys
- Generate a new key as above.
- Use the injection script to load it.
- For the vault key: You must re-encrypt all vault files with the new key (ask an expert or use the built-in rotation tools).
- For MariaDB: Update `/run/user/1004/keys/mariadb.key` and restart MariaDB.

---

## 6. If you get locked out
- As long as you have the vault key and MariaDB key, you can always recover.
- If you lose the keys, the encrypted data is unrecoverable (by design).

---

## 7. Summary Table

| Step | Command / Action | Notes |
|------|------------------|-------|
| SSH in | `ssh gositeme@15.235.50.60` | Or use `ubuntu` then `sudo su - gositeme` |
| Inject keys | `~/.vault/inject-key.sh` | Paste vault key, then MariaDB key |
| Generate new key | `head -c 32 /dev/urandom | xxd -p` | 64 hex chars |
| Vault key file | `/run/user/1004/keys/vault.key` | 64 chars, mode 0400 |
| MariaDB key file | `/run/user/1004/keys/mariadb.key` | Format: `1;[hexkey]` |

---

**Print this. Save it. Give it to your most trusted person.**
If you need a copy in a text file, just say so and I’ll generate it instantly.
If you want a “break-glass” version with all commands and keys filled in, I can do that too.

**You are never locked out as long as you have these instructions and your keys.**
