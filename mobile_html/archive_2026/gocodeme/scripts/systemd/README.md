# GoCodeMe – systemd Service Units

Three service units are provided:

| File | Service | Port |
|------|---------|------|
| `gocodeme-middleware.service` | Middleware API + Dashboard | 3001 |
| `gocodeme-openclaw.service`  | OpenClaw bridge           | 3004 |
| `gocodeme-mcp.service`       | MCP Server (SSE)          | 3005 |

---

## Option A – Root install (recommended for VPS)

```bash
sudo cp *.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable gocodeme-middleware gocodeme-openclaw gocodeme-mcp
sudo systemctl start  gocodeme-middleware gocodeme-openclaw gocodeme-mcp
sudo systemctl status gocodeme-middleware
```

## Option B – User-level systemd (non-root, gositeme uid=1004)

```bash
mkdir -p ~/.config/systemd/user/
cp *.service ~/.config/systemd/user/
systemctl --user daemon-reload
systemctl --user enable gocodeme-middleware gocodeme-openclaw gocodeme-mcp
systemctl --user start  gocodeme-middleware gocodeme-openclaw gocodeme-mcp

# Keep services alive after SSH logout (run once as root):
sudo loginctl enable-linger gositeme
```

## Checking logs

```bash
# System install
journalctl -u gocodeme-middleware -f

# User install
journalctl --user -u gocodeme-middleware -f
```

## Replacing tmux sessions

Once systemd units are running you can stop the tmux sessions:

```bash
tmux kill-session -t mw
tmux kill-session -t openclaw
tmux kill-session -t mcp3
```

## Apache proxy

See `../../apache/gocodeme.conf` for the Apache reverse proxy configuration,
or `../../.htaccess` for the shared-hosting `.htaccess` version.

Requirements: `mod_proxy`, `mod_proxy_http`, `mod_headers`, `mod_rewrite`

```bash
# Enable modules (Debian/Ubuntu with root):
sudo a2enmod proxy proxy_http headers rewrite
sudo systemctl reload apache2
```
