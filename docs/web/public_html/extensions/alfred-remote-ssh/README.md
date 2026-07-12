# Alfred Remote — SSH

Companion extension for **Alfred IDE** (VS Code / Cursor / code-server): it **bundles** Microsoft’s **Remote - SSH** (`ms-vscode-remote.remote-ssh`) so operators install one marketplace entry and get the same remote-over-SSH workflow.

## What this is (and is not)

- **Is:** A thin **extension pack** — branding + single dependency; no duplicate SSH implementation.
- **Is not:** A fork of Microsoft’s Remote - SSH source. Licensing and updates stay with Microsoft’s extension.

## Pack a VSIX

```bash
bash pack-vsix.sh
```

Requires **Node.js** (uses `npx @vscode/vsce`).

## Install

1. In VS Code or Cursor: **Extensions → … → Install from VSIX…**
2. Select the generated `alfred-remote-ssh-*.vsix`.
3. After install, use the normal **Remote-SSH: Connect to Host…** command palette entry (provided by Microsoft’s extension).

## Alfred IDE default port

When connecting to an Alfred Linux machine running **code-server**, use your SSH config `Host` and point the remote workspace at the server path where code-server serves (often port **8443** over HTTPS, or SSH port-forward as you prefer). See **https://alfredlinux.com/developers** for the current Alfred IDE layout.

## Publisher

Packaged as `gositeme.alfred-remote-ssh` when published to an Open VSX / private marketplace registry.
