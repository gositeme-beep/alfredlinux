# BitNet ISO Staging Requirements

This build expects these staged files under build-assets before ISO build:

- bitnet.cpp or bitnet or bitnet-cli
- bitnet-default.gguf
- bitnet-LICENSE
- bitnet-NOTICE
- bitnet-model-LICENSE
- bitnet-model-NOTICE

Installed in image by hooks:

- /usr/local/bin/bitnet.cpp
- /usr/local/bin/alfred-ask
- /usr/local/bin/alfred
- /usr/share/bitnet/models/default.gguf
- /usr/share/doc/alfred-ai/bitnet-model-policy.txt
- /usr/share/doc/alfred-ai/bitnet-LICENSE
- /usr/share/doc/alfred-ai/bitnet-NOTICE
- /usr/share/doc/alfred-ai/bitnet-model-LICENSE
- /usr/share/doc/alfred-ai/bitnet-model-NOTICE

Validation hook:

- config/hooks/live/0252-alfred-bitnet-smoke.hook.chroot
- Build report: /var/lib/alfred/build-flags/bitnet-smoke.txt
- Status flags: bitnet-smoke.ok or bitnet-smoke.fail

## Current Staged Status (2026-05-10)

Bootstrap artifacts are currently staged to keep ISO integration and smoke checks fully wired:

- build-assets/bitnet.cpp is a compatibility shim (routes to Ollama fallback)
- build-assets/bitnet-default.gguf is a placeholder file

Before production release, replace both with official BitNet engine/model artifacts.
