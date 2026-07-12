# Alfred Commander vs Alfred IDE (Theia)

**Alfred IDE** is the GoCodeMe **Theia** browser IDE (`ideProxy.js` + `/ide/:port/*`).

Theia loads plugins from:

```
gocodeme/theia-fork/plugins/
```

(`start-theia.sh` uses `--plugins=local-dir:$THEIA_DIR/plugins`.)

## What actually works (checked)

1. **Symlinks** into `plugins/` are unreliable — use a **real file**.
2. **Unpacked folder** alone may not load the same as a **proper `.vsix`**.
3. The deployed artifact is:

```
theia-fork/plugins/gositeme.alfred-commander-1.0.0.vsix
```

Built with **`@vscode/vsce`** from:

```
~/.local/share/code-server/extensions/gositeme.alfred-commander-1.0.0/
```

Re-pack after edits:

```bash
bash gocodeme/scripts/pack-alfred-commander-vsix.sh
```

## package.json tweaks for Theia

- `extensionKind` removed (can confuse the host).
- `activationEvents`: only `onStartupFinished` (avoid `onView:…` activation loops).

## After install / update

1. **Restart the IDE session** (stop + launch) so Theia rescans `plugins/*.vsix`.
2. **View → Open View…** → **Alfred Commander**, or Command Palette → **Alfred Commander: Open Panel**.

## If it still does not show

- Confirm `gositeme.alfred-commander-1.0.0.vsix` exists and has non-zero size.
- Inspect Theia stdout/stderr from `start-theia.sh` for plugin errors.
- If the webview API fails on your Theia build, logs will mention `registerWebviewViewProvider` / plugin activation.
