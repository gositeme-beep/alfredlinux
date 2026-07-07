# Alfred Linux Test Suite

Automated test suites for validating the Alfred Linux OS build integrity, security hardening, and hook architecture.

## Tests

| Test | File | Purpose | Run |
|------|------|---------|-----|
| **Boot Test** | `boot-test.sh` | QEMU headless boot — verifies ISO boots to desktop, kernel 7.0.12 loads, no panics | `bash tests/boot-test.sh path/to/alfred.iso` |
| **Hook Lint** | `hook-lint.sh` | Validates all 1,335 sacred hooks — count, shebangs, naming, syntax, secrets, Plymouth ban | `bash tests/hook-lint.sh config/hooks/live` |
| **Security Audit** | `security-audit.sh` | CIS Level 2 hardening, SSH, AppArmor, nftables, DNS, MAC randomization, post-quantum, ZFS, Omahon Seal | `sudo bash tests/security-audit.sh` |

## Quick Start

```bash
# Run all tests
bash tests/hook-lint.sh
sudo bash tests/security-audit.sh
bash tests/boot-test.sh /path/to/AlfredLinux-Alpha-Matrix-7.77-x86_64.iso
```

## Test Coverage

- **10 hook integrity checks** — count, executable, shebang, naming, duplicates, empty, syntax, secrets, Plymouth, structure
- **5 boot validation checks** — ISO exists, QEMU boot, kernel version, critical errors, login prompt
- **20+ security hardening checks** — sysctl (11 params), SSH (3 checks), firewall, AppArmor, DNS, MAC, post-quantum, ZFS, Plymouth ban, Omahon Seal

## CI/CD

These tests are designed to run in the Docker build pipeline after `lb binary` completes. Add to your build script:

```bash
# Post-build validation
bash tests/hook-lint.sh config/hooks/live
bash tests/security-audit.sh build/chroot
```

---

*"Prove all things; hold fast that which is good." — 1 Thessalonians 5:21*
