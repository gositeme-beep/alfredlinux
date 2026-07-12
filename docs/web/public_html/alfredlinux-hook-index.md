# AlfredLinux Hook Index and Expansion Snapshot

Date: 2026-05-06

## Verified Metrics
- Active hooks: 101
- Total hook lines of code: 16,941
- Expanded roadmap candidates scanned: 84
- Available now (Wave 1): 64
- Deferred now (Wave 2 targets): 20

## Active Hook Inventory
- 0010-alfred-bootstrap.hook.chroot
- 0100-alfred-customize.hook.chroot
- 0150-alfred-hardware.hook.chroot
- 0160-alfred-security.hook.chroot
- 0165-alfred-network-hardening.hook.chroot
- 0166-alfred-quantum.hook.chroot
- 0167-alfred-mesh.hook.chroot
- 0168-alfred-productivity.hook.chroot
- 0170-alfred-fde.hook.chroot
- 0175-omahon-seal.hook.chroot
- 0176-kingdom-covenant-shield.hook.chroot
- 0200-alfred-browser.hook.chroot
- 0250-alfred-ai.hook.chroot
- 0255-alfred-dev-tools.hook.chroot
- 0260-alfred-terminal-power.hook.chroot
- 0265-alfred-containers.hook.chroot
- 0270-alfred-sovereign.hook.chroot
- 0275-alfred-gpu.hook.chroot
- 0280-alfred-max-sovereign.hook.chroot
- 0285-alfred-eternal-storage.hook.chroot
- 0290-alfred-bible.hook.chroot
- 0291-alfred-family-bible.hook.chroot
- 0292-alfred-bible-tongues.hook.chroot
- 0295-alfred-worship.hook.chroot
- 0296-alfred-testimony.hook.chroot
- 0297-alfred-kingdom-locale-payload.hook.chroot
- 0300-alfred-ide.hook.chroot
- 0400-alfred-voice.hook.chroot
- 0500-alfred-search.hook.chroot
- 0600-alfred-installer.hook.chroot
- 0605-alfred-callings.hook.chroot
- 0700-alfred-welcome.hook.chroot
- 0701-alfred-stranger.hook.chroot
- 0702-alfred-accessibility.hook.chroot
- 0703-alfred-hearth.hook.chroot
- 0710-alfred-update.hook.chroot
- 0722-alfred-sabbath.hook.chroot
- 0723-alfred-morning-watch.hook.chroot
- 0724-alfred-inheritance.hook.chroot
- 0725-alfred-assembly.hook.chroot
- 0800-alfred-store.hook.chroot
- 0810-alfred-expanded-wave1.hook.chroot
- 0820-alfred-expanded-wave2.hook.chroot
- 0830-alfred-expanded-wave2b.hook.chroot
- 0840-alfred-expanded-wave3.hook.chroot
- 0850-alfred-expanded-wave4-ops.hook.chroot
- 0860-alfred-expanded-wave5-prod.hook.chroot
- 0870-alfred-expanded-wave6-slo.hook.chroot
- 0880-alfred-expanded-wave7-observability.hook.chroot
- 0890-alfred-expanded-wave8-release-governance.hook.chroot
- 0900-alfred-expanded-wave9-attestation.hook.chroot
- 0910-alfred-expanded-wave10-evidence.hook.chroot
- 0920-alfred-integrity-ledger.hook.chroot
- 0930-alfred-license-notice-bundle.hook.chroot
- 0940-alfred-release-metadata.hook.chroot
- 0950-alfred-incident-runbook.hook.chroot
- 0960-alfred-config-drift.hook.chroot
- 0970-alfred-secrets-guard.hook.chroot
- 0980-alfred-release-linter.hook.chroot
- 0990-alfred-sbom-export.hook.chroot
- 1000-alfred-drill-restore.hook.chroot
- 1040-alfred-log-retention.hook.chroot
- 1050-alfred-service-inventory.hook.chroot
- 1060-alfred-port-audit.hook.chroot
- 1070-alfred-usergroup-baseline.hook.chroot
- 1080-alfred-scheduled-task-audit.hook.chroot
- 1140-alfred-sysctl-baseline-report.hook.chroot
- 1150-alfred-kernel-module-audit.hook.chroot
- 1160-alfred-network-route-baseline.hook.chroot
- 1170-alfred-failed-login-audit.hook.chroot
- 1180-alfred-config-backup-catalog.hook.chroot
- 1190-alfred-ssh-hardening-audit.hook.chroot
- 1200-alfred-time-sync-audit.hook.chroot
- 1210-alfred-certificate-inventory.hook.chroot
- 1220-alfred-process-baseline.hook.chroot
- 1230-alfred-filesystem-integrity-scan.hook.chroot
- 1240-alfred-akjv-kernel-embed.hook.chroot
- 1250-alfred-kernel-config-snapshot.hook.chroot
- 1260-alfred-initramfs-audit.hook.chroot
- 1270-alfred-boot-chain-audit.hook.chroot
- 1280-alfred-auditd-baseline.hook.chroot
- 1290-alfred-dr-evidence-pack.hook.chroot
- 1300-alfred-supply-chain-audit.hook.chroot
- 1310-alfred-user-session-audit.hook.chroot
- 1320-alfred-firewall-drift-detect.hook.chroot
- 1330-alfred-service-restart-audit.hook.chroot
- 1340-alfred-kingdom-readiness-report.hook.chroot
- 1350-alfred-kernel-abi-watch.hook.chroot
- 1360-alfred-syscall-policy-audit.hook.chroot
- 1370-alfred-seccomp-profile-catalog.hook.chroot
- 1380-alfred-cis-control-mapping.hook.chroot
- 1390-alfred-mission-continuity-scorecard.hook.chroot
- 9999-fix-kernel-names.hook.chroot

## Wave 1
- Implemented with hook 0810-alfred-expanded-wave1.hook.chroot
- Non-fatal package install strategy to protect build stability

## Wave 2
- Implemented with hook 0820-alfred-expanded-wave2.hook.chroot
- Strategy: install Debian-available substitutes now, defer external/manual targets
- Now installs: electrum, monero, cryptsetup, wireguard-tools, openvpn, nginx-light, prometheus, keepassxc, pass, age, python3-pip, pipx, kiwix-tools, darktable, freecad, tailscale
- Pipx best-effort: meshtastic, octoprint

## Wave 2b
- Implemented with hook 0830-alfred-expanded-wave2b.hook.chroot
- Adds container/service substrate: docker.io, compose, podman, buildah, skopeo, redis, postgresql, nginx, hardening helpers
- Stages compose templates under /opt/alfred/wave2-services for deferred apps

## Wave 3
- Implemented with hook 0840-alfred-expanded-wave3.hook.chroot
- Adds deterministic upstream/manual repo bootstrap scripts under /usr/local/lib/alfred-wave3/repos
- Writes plan artifact: /usr/local/share/alfred-wave3-plan.md

## Wave 4 Ops
- Implemented with hook 0850-alfred-expanded-wave4-ops.hook.chroot
- Adds post-install operations commands: alfred-wave-services-up and alfred-wave-services-status

## Wave 5 Production Profile
- Implemented with hook 0860-alfred-expanded-wave5-prod.hook.chroot
- Adds pinned service image tags and production compose overlays
- Adds healthchecks plus backup/restore commands: alfred-wave-backup and alfred-wave-restore

## Wave 6 SLO/DR
- Implemented with hook 0870-alfred-expanded-wave6-slo.hook.chroot
- Adds service SLO policy: /etc/alfred/slo/policy.yml
- Adds backup rotation and restore verification commands
- Adds daily maintenance runner for backup + rotation

## Wave 7 Observability
- Implemented with hook 0880-alfred-expanded-wave7-observability.hook.chroot
- Adds endpoint probe config and threshold baseline
- Adds unified health commands: alfred-wave-health-report and alfred-wave-health-check
- Adds hourly health runner for continuous local checks

## Wave 8 Release Governance
- Implemented with hook 0890-alfred-expanded-wave8-release-governance.hook.chroot
- Adds release version stamp, changelog generator, and checksum manifest command
- Adds go/no-go gate command to enforce release readiness checks
- Seeds governance artifacts during build hook execution

## Wave 9 Policy + Attestation
- Implemented with hook 0900-alfred-expanded-wave9-attestation.hook.chroot
- Adds formal release policy file and policy-check command
- Adds release attestation output and release seal command
- Enforces policy-first flow before attestation generation

## Wave 10 Evidence Bundle
- Implemented with hook 0910-alfred-expanded-wave10-evidence.hook.chroot
- Adds deterministic evidence bundle output with artifact hashes
- Adds compliance summary command for governance readiness snapshots

## Wave 11-15 Operational Guardrails
- Implemented with hooks 1040 through 1080
- Adds log retention, service inventory, port audit, user/group baseline, and scheduler audit
- Extends SLO/DR controls and observability probes with practical runtime evidence snapshots

## Wave 16 Baseline and Recovery Context
- Implemented with hooks 1140 through 1180
- Adds sysctl and kernel-module auditing, network baseline, auth-failure audit, and config-catalog backup index
- Strengthens release governance evidence and policy attestation with richer host-state baselines

## Wave 17 Security and Time Integrity
- Implemented with hooks 1190 through 1230
- Adds SSH hardening audit, clock-sync audit, certificate inventory, process baseline, and filesystem integrity hashes
- Improves integrity assurance for operational trust and incident response readiness

## Wave 18 AKJV Kernel Embed Enforcement
- Implemented with hook 1240-alfred-akjv-kernel-embed.hook.chroot
- Prepares AKJV payload stage for kernel-image embedding via CONFIG_INITRAMFS_SOURCE policy path
- Enforces build failure when AKJV kernel bind cannot be proven in release mode

## Wave 19 Kernel and DR Assurance
- Implemented with hooks 1250 through 1290
- Adds kernel config snapshot, initramfs audit, boot chain audit, auditd baseline, and DR evidence pack generation
- Extends observability probes and SLO/DR controls with stronger boot-to-recovery verification evidence

## Wave 20 Readiness and Drift Governance
- Implemented with hooks 1300 through 1340
- Adds supply-chain audit, session audit, firewall drift detection, service restart audit, and kingdom readiness report
- Expands observability probes and policy attestation posture with operations-centered readiness evidence

## Wave 21 Continuity and Kernel Guardrails
- Implemented with hooks 1350 through 1390
- Adds kernel ABI watch, syscall hardening audit, seccomp profile catalog, CIS control mapping, and mission continuity scorecard
- Extends governance evidence with kernel-surface assurance while preserving AKJV kernel embedding final policy

## Wave 2 Missing Package Targets
- electrum-ltc
- bitcoin-qt
- monero-gui
- veracrypt
- mullvad-vpn
- jellyfin
- vaultwarden
- headscale
- caddy
- ntfy
- searxng
- forgejo
- grafana
- kanboard
- octoprint
- prusaslicer
- linuxcnc
- meshtastic
- kiwix-serve
- kiwix-lib

## Public Architecture Statement
Hooks are build-time and system-configuration orchestration. They harden and shape kernel behavior and operating-system defaults, but they are not direct kernel source patches.

## AKJV Kernel Embedding Policy (Final)
- Requirement: AKJV payload must be bound into the kernel image path for release builds.
- Enforced by: 1240-alfred-akjv-kernel-embed.hook.chroot.
- Mechanism: prepares AKJV initramfs source tree at /usr/src/alfred-akjv-kernel/initramfs-root and applies CONFIG_INITRAMFS_SOURCE policy.
- Guardrail: build fails by default when kernel bind cannot be proven (unless explicitly running non-release test mode).
