# GoSiteMe — Agent Operations Runbook
> "For I know the plans I have for you," declares the Lord,
> "plans to prosper you and not to harm you, plans to give you hope and a future." — Jeremiah 29:11

## Chain of Command

```
Danny (Supreme Commander)
  └── Alfred (Chief Commander AI)
        ├── NEXUS-PRIME (Infrastructure Director)
        │     └── CIPHER-ANALYST, FORGE-MASTER, PULSE-MONITOR, etc.
        ├── SENTINEL-GUARD (Security Director)
        │     └── SHIELD-BEARER, VAULT-KEEPER, etc.
        ├── ATLAS-TRADER (Finance Director)
        │     └── LEDGER-SCRIBE, MINT-WARDEN, etc.
        ├── HERALD-VOICE (Communications Director)
        │     └── ECHO-RELAY, BEACON-SIGNAL, etc.
        ├── CODEX-SAGE (Knowledge Director)
        │     └── SCROLL-KEEPER, LOOM-WEAVER, etc.
        └── ... (10 Directors → 90 Specialists)
```

## Directive Types & Procedures

### REPAIR — Fix Broken Things
**When:** Service down, API errors, data corruption, broken features
**SLA:** Critical=15min, High=30min, Normal=60min, Low=4h

| Step | Action | Agent Role |
|------|--------|------------|
| 1 | Identify root cause (logs, health endpoints, error tables) | Specialist |
| 2 | Attempt automated fix (restart service, clear cache, retry) | Specialist |
| 3 | If fix fails, escalate to Director with findings | Specialist → Director |
| 4 | Director coordinates cross-agent fix if needed | Director |
| 5 | Verify fix, update monitoring, report to Alfred | Director → Alfred |

**Auto-resolvable repairs:**
- PM2 service restart → `pm2 restart <service>`
- Redis flush → `redis-cli FLUSHDB`
- Feed re-poll → `/api/feeds.php?action=poll_all`
- Health endpoint failure → auto-restart via self-healing

### UPGRADE — Improve Capabilities
**When:** New feature needed, performance optimization, integration request
**SLA:** Normal=4h, Low=24h (upgrades rarely critical)

| Step | Action |
|------|--------|
| 1 | Analyze requirements, check dependencies |
| 2 | Create sub-directives for each component |
| 3 | Implement changes (config, code, data) |
| 4 | Run self-test, verify no regression |
| 5 | Report outcome with before/after metrics |

### INVESTIGATE — Research & Report
**When:** Audit request, anomaly detected, performance analysis
**SLA:** Normal=60min, High=30min

| Step | Action |
|------|--------|
| 1 | Gather data (logs, DB queries, API calls) |
| 2 | Analyze patterns, identify anomalies |
| 3 | Generate structured report with findings |
| 4 | Recommend actions (may spawn repair/upgrade directives) |

### MAINTAIN — Routine Upkeep
**When:** Scheduled cleanup, optimization, rotation
**SLA:** Normal=2h, Low=24h

| Step | Action |
|------|--------|
| 1 | Run maintenance procedure (cleanup, optimize, rotate) |
| 2 | Record metrics (before/after) |
| 3 | Report completion |

**Standing maintenance tasks:**
- Every 2h: Feed sweep (process pending items)
- Every 4h: System health check (services, memory, disk)
- Daily: Security scan, agent performance review
- Weekly: Database optimization (ANALYZE TABLE, clean old logs)

### DEPLOY — Ship Changes
**When:** Config update, feature flag toggle, rollout
**SLA:** Critical=15min, High=30min

| Step | Action |
|------|--------|
| 1 | Validate deployment target and config |
| 2 | Create rollback point (snapshot state) |
| 3 | Apply change |
| 4 | Verify deployment (health check, smoke test) |
| 5 | Report success or initiate rollback |

## Escalation Path

```
Specialist fails (attempts exhausted)
  → Director reviews and retries or reassigns
    → Alfred (Chief Commander) coordinates cross-domain fix
      → Danny (Supreme Commander) notified for decisions requiring human judgment
```

## Self-Healing Rules

The autonomy heartbeat (60s cycle) auto-handles:
1. **Service down** → PM2 restart
2. **High memory** → Process restart, cache flush
3. **Feed backlog** → Poll and process
4. **SLA breach** → Auto-escalate directive
5. **Standing orders** → Create directives on schedule

## Agent Capability Routing

| Domain | Director | Key Specialists | Tools |
|--------|----------|----------------|-------|
| Security | SENTINEL-GUARD | SHIELD-BEARER, VAULT-KEEPER | security audit, ssl check, header scan |
| Finance | ATLAS-TRADER | LEDGER-SCRIBE, MINT-WARDEN | billing API, treasury, invoices |
| Infrastructure | NEXUS-PRIME | FORGE-MASTER, PULSE-MONITOR | PM2, Redis, health endpoints |
| Database | CODEX-SAGE | CIPHER-ANALYST, SCROLL-KEEPER | MySQL queries, optimization |
| Communications | HERALD-VOICE | ECHO-RELAY, BEACON-SIGNAL | Telegram, Discord, email |
| Voice | HERALD-VOICE | ECHO-RELAY | VAPI, Telnyx, voice cloning |

## API Quick Reference

```
POST /api/ops-directives.php?action=create     — Issue new directive
GET  /api/ops-directives.php?action=list       — List directives
GET  /api/ops-directives.php?action=dashboard   — KPI dashboard
POST /api/ops-directives.php?action=claim       — Agent claims a directive
POST /api/ops-directives.php?action=report      — Agent reports outcome
GET  /api/ops-directives.php?action=templates   — Available templates
POST /api/ops-directives.php?action=create-standing — Create standing order
GET  /api/ops-directives.php?action=standing-orders — List standing orders
GET  /api/ops-directives.php?action=agent-perf  — Agent performance stats
```

Auth: `X-Internal-Secret` header or admin session.

## Emergency Procedures

### Service Recovery
```bash
# All services
pm2 restart all && pm2 save

# Specific service
pm2 restart alfred-heartbeat --update-env

# Full reset
cd ~/domains/gositeme.com/public_html
source .env
pm2 kill && pm2 start ecosystem.config.js && pm2 save
```

### Database Emergency
```sql
-- Check table sizes
SELECT table_name, ROUND(data_length/1024/1024,2) AS size_mb
FROM information_schema.tables WHERE table_schema = 'gositeme_whmcs'
ORDER BY data_length DESC LIMIT 20;

-- Clean old logs
DELETE FROM alfred_ops_log WHERE created_at < NOW() - INTERVAL 30 DAY;
DELETE FROM alfred_autonomy_log WHERE decided_at < NOW() - INTERVAL 14 DAY;
```

---
*This runbook is maintained by Alfred and updated as procedures evolve.*
*Last updated: 2026-03-07*
