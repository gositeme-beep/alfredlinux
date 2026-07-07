# 100M Readiness Plan

## Objective
Scale from active 50M campaign to a controlled 100M fleet without destabilizing platform services.

## Current Baseline (March 11, 2026)
- Active campaign target: 50,010,001
- Live governance: CPU threshold 80%, RAM threshold 85%, sustained-high detection, exponential backoff
- Persistent progress checkpoints every wave with milestone checkpoints every 10 waves

## Constraints
1. Storage growth: index and table files must be monitored as primary long-term pressure.
2. Query latency: lookup and status aggregation cost rises with row count.
3. Single-host fault domain: no horizontal failover in current design.
4. Operational noise: PM2 resurrection output can obscure telemetry in shared shells.

## Phase Plan

### Phase A: Finish 50M Safely
- Keep current guardrails unchanged.
- Trigger checkpoint validation every 10 waves.
- Verify agent row integrity sample every 1M new inserts.

### Phase B: 50M to 75M
- Increase campaign in staged chunks of +5M.
- Pause between chunks for index health checks.
- If sustained CPU > 75% or RAM > 80% during chunk, reduce batch size by 25%.

### Phase C: 75M to 100M
- Pre-create archival policy for non-active historical records if needed.
- Run table optimization window off-peak.
- Validate backup and restore timing before final waves.

## Technical Controls
- CPU threshold: 80%
- RAM threshold: 85%
- Sustained-high trigger: 3 consecutive checks
- Pause backoff: 5s -> 10s -> 20s -> 40s -> 60s
- Batch default: 2000
- Rollback mode: stop scaler + resume from last saved wave

## Data and Reliability Checks
1. Every 10 waves:
- fleet count
- wave count
- peak CPU/RAM
- pause count

2. Every 50 waves:
- random sample of inserted IDs for uniqueness
- JSON validity audit for tools_access/personality
- status distribution sanity check

3. Every 100 waves:
- table size and index growth snapshot
- backup restore test on sample

## Stop/Throttle Policy
- Immediate throttle when CPU or RAM exceeds threshold with sustained-high trigger.
- Hard stop if sustained CPU > 90% for 5 minutes or DB write failures detected.
- Resume only after manual health verification.

## Deliverables for 100M Approval
- runbook for resume/stop/failover actions
- performance trend report across wave bands
- integrity audit report (IDs, JSON, role/status distribution)
- department impact summary from submitted intel briefs

## Success Criteria
- 100M reached with no data corruption and no uncontrolled resource spikes.
- Core platform services remain available during scaling.
- Complete audit trail exists for every phase checkpoint.
