Alfred Linux — optional systemd *user* units for repo maintenance
===================================================================

These files are not installed automatically. Copy or symlink into
`~/.config/systemd/user/` and adjust paths if your clone is not
`~/law/alfredlinux-com-source-live`.

  cp contrib/systemd/user/alfred-linux-repo-health.{service,timer} ~/.config/systemd/user/
  # Optional: full shellcheck on each run (slow) — see service.d example:
  #   mkdir -p ~/.config/systemd/user/alfred-linux-repo-health.service.d
  #   cp contrib/systemd/user/alfred-linux-repo-health.service.d/10-shellcheck.conf.example \
  #      ~/.config/systemd/user/alfred-linux-repo-health.service.d/10-shellcheck.conf
  # Edit WorkingDirectory / Environment in the .service if needed
  systemctl --user daemon-reload
  systemctl --user enable --now alfred-linux-repo-health.timer

The timer runs `scripts/alfred-repo-health.sh` (release-integrity **check-repo**,
`security-audit.sh`, and **`audit-law-wrappers.sh`** when `LAW_ROOT` exists). View logs:

  journalctl --user -u alfred-linux-repo-health.service -n 50

Optional drop-in for a **full shellcheck** sweep (slow — e.g. weekly instead of every timer):

  systemctl --user edit alfred-linux-repo-health.service
  # [Service]
  # Environment=ALFRED_REPO_HEALTH_SHELLCHECK_ALL=1
