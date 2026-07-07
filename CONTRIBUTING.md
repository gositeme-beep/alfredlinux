# Contributing to Alfred Linux

Thank you for your interest in contributing to Alfred Linux — the world's first 100% offline AI-native sovereign operating system.

## 🛡️ The Covenant

Alfred Linux is built under the **Hebrews 12:1 Covenant**. All contributions must respect:
- The sovereignty and spiritual mission of the project
- The AGPL-3.0-or-later license
- The privacy-first, zero-telemetry philosophy

## 🔧 How to Contribute

### Reporting Bugs
1. Open an [Issue](https://github.com/GoSiteMe-com/alfredlinux/issues)
2. Include your hardware specs, boot method (USB/VM), and error logs
3. If possible, include the output of \journalctl -b -1 --no-pager | tail -100\

### Submitting Code
1. Fork the repository
2. Create a feature branch: \git checkout -b feature/your-feature\
3. Follow the existing code style and hook naming conventions
4. Test your changes against the build system
5. Submit a Pull Request with a clear description

### Hook Contributions
The sacred hook architecture (1,335 hooks) follows strict naming:
- \NNNN-alfred-<name>.hook.chroot\ — numbered sequentially
- **Do NOT modify existing hook numbers** — this breaks the seal
- New hooks should be discussed in an Issue first

### Documentation
- Improvements to docs, README, or inline comments are always welcome
- Use clear, professional English

## 📋 Code Standards

- Shell scripts: POSIX-compatible where possible, Bash 5+ features allowed
- Python: Python 3.11+, PEP 8 compliant
- Hooks: Must be idempotent (safe to run multiple times)
- All contributions must work **fully offline**

## 🚫 What We Don't Accept

- Telemetry, analytics, or tracking of any kind
- Dependencies on proprietary cloud services
- Code that phones home without explicit user consent
- Modifications to the kernel security hardening flags without discussion

## 📜 License

By contributing, you agree that your contributions will be licensed under the **AGPL-3.0-or-later** license.

---

*" Let us run with perseverance the race marked out for us.\ — Hebrews 12:1*
