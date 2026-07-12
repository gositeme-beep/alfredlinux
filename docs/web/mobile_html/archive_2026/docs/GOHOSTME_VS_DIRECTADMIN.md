# GoHostMe vs DirectAdmin

**Positioning:** GoHostMe is the **sovereignty-first control plane** for this platform—APIs, dashboard, automation, and docs—while the **data plane** remains familiar (Apache, MySQL, mail stack on the host).

## What DirectAdmin is

- Full hosting UI: accounts, DNS editor, mail, DB wizard, file manager, quotas.
- Opinionated lifecycle tied to CustomBuild and DA’s permission model.

## What GoHostMe is

- **Product UI** for platform operations: security, optimization hooks, diagnostics, activity, integrations.
- **HTTP APIs** for the same (JWT admin), suitable for proxies and internal tools.
- **CLI scripts** under `public_html/scripts/` for operators who prefer SSH.
- **Documentation** as first-class (this `docs/` tree).

## Parity (honest)

| Capability | DirectAdmin | GoHostMe |
|------------|-------------|----------|
| Per-user hosting panel (end customers) | Strong | Not the goal |
| Server hardening / fail2ban visibility | Partial | **Panel + APIs** |
| One-click stack tuning (via bridge) | Limited | **Panel** |
| Load / MySQL diagnostics | Plugins / manual | **Panel + `/api/security/diagnostics`** |
| Mail/DNS/account lifecycle | Native | Use **DA or CLI** during transition; migrate per checklist |

## Narrative for stakeholders

> **“GoHostMe replaces DirectAdmin over time”** means we **stop depending on DA for day-to-day control**—not that every DA screen is cloned on day one. Critical paths move to GoHostMe APIs, runbooks, and automation; DA can remain as a thin legacy layer until fully retired.

## See also

- [OPS-COMPLETE-PACK.md](OPS-COMPLETE-PACK.md)
- [DIRECTADMIN_MIGRATION_CHECKLIST.md](DIRECTADMIN_MIGRATION_CHECKLIST.md)
