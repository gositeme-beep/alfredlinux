# GoHostMe operations — master index

This page ties together **panel automation**, **CLI scripts**, and **migration** docs so operators don’t need to hunt through chat history.

## Panel (GoHostMe dashboard)

| Area | What it does |
|------|----------------|
| **Server Hardening** | Fail2Ban status, **Load & DB diagnostics** (calls `GET /api/security/diagnostics`), re-apply security/optimization via bridge |
| **API** | Admin-only; same origin as `/gohostme` PHP proxy |

## HTTP diagnostics (admin JWT)

- **Endpoint:** `GET /api/security/diagnostics`
- **Returns:** `df -h`, `free -m`, `/proc/loadavg`, `uptime`, **`nproc` + load vs cores (`load_per_core`, `load_hint`)**, **`interpretation`** (plain-English bullets: swap pressure, I/O wait `wa` ≥20% since boot, MySQL `Threads_running`, optional slow-log permission hint), **`meminfo_kb`**, **`swappiness`**, **`slow_log_snapshot`** (tail of `slow_query_log_file` when readable; otherwise `hint: sudo tail -n 80 …`), **`vmstat`**, **`top_cpu`**, plus MySQL `SHOW VARIABLES` / `SHOW GLOBAL STATUS` for slow log, connections, buffer pool, threads, slow query count. Append **`?slow_log=0`** to omit the tail (smaller JSON).

## CLI (no panel)

```bash
php /home/gositeme/domains/gositeme.com/public_html/scripts/health/diagnostics.php
```

Prints the same shape of data as JSON (stdout). Use for SSH / cron / incident notes.

## Related documents

| Doc | Purpose |
|-----|---------|
| [DirectAdmin → GoHostMe migration checklist](DIRECTADMIN_MIGRATION_CHECKLIST.md) | Phased cutover, DNS, mail, backups |
| [GoHostMe vs DirectAdmin](GOHOSTME_VS_DIRECTADMIN.md) | Parity narrative and positioning |
| [Alfred / Vapi / phone](ALFRED_VAPI_PHONE_PROMPT.md) | Voice stack troubleshooting (load/DB vs fail2ban) |
| [Agent ops runbook](AGENT_OPS_RUNBOOK.md) | Broader platform ops |

## On-disk automation (reference)

- Apache perf include: `/etc/httpd/conf/extra/gositeme-performance.conf`
- Security / optimization scripts: `public_html/scripts/security/`, `public_html/scripts/optimization/`

---

*GoHostMe is the control plane that replaces DirectAdmin over time; this index is the operator map.*
