Alfred Linux — optional systemd *user* units for repo maintenance
===================================================================

These files are not installed automatically. Copy or symlink into
`~/.config/systemd/user/` and adjust paths if your clone is not
`~/law/alfredlinux-com-source-live`.

  cp contrib/systemd/user/alfred-linux-repo-health.{service,timer} ~/.config/systemd/user/
  # Edit WorkingDirectory / Environment in the .service if needed
  systemctl --user daemon-reload
  systemctl --user enable --now alfred-linux-repo-health.timer

The timer runs `scripts/alfred-repo-health.sh` (release-integrity check-repo +
security-audit). View logs:

  journalctl --user -u alfred-linux-repo-health.service -n 50
