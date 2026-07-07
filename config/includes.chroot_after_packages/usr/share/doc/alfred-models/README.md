# Alfred Linux — Bundled AI Models

Alfred Linux 7.77 ships with four pre-quantized large language model
files in `/opt/alfred/models/`. These are stored under Alfred's
internal tier names so that the Alfred Brain orchestrator, the
alfred-brain VS Code extension, and the LiteLLM bridge can route
requests by tier without coupling to any specific upstream vendor.

## Tiers

| File                    | Tier   | Upstream model                | Quant  | RAM target |
|-------------------------|--------|-------------------------------|--------|------------|
| `alfred-haiku.gguf`     | fast   | Qwen2.5-Coder-7B-Instruct     | Q4_K_M | 8 GB       |
| `alfred-sonnet.gguf`    | mid    | Qwen2.5-Coder-14B-Instruct    | Q4_K_M | 16 GB      |
| `alfred-opus.gguf`      | flagship | Qwen2.5-Coder-32B-Instruct  | Q4_K_M | 32 GB      |
| `alfred-opus-iq3.gguf`  | flagship-lite | Qwen2.5-Coder-32B-Instruct | IQ3_XS | 16 GB |

All four files are derivatives of the **Qwen2.5-Coder-Instruct**
family by Alibaba Cloud, released under the **Apache License 2.0**.
GGUF quantization performed by the community (bartowski on
HuggingFace), redistributed here under the same Apache 2.0 license.

## Required attribution

Per Apache 2.0 §4(d), see `NOTICE` and `LICENSE-Apache-2.0` in this
directory. If you redistribute Alfred Linux or extract these model
files, you MUST carry both files with the redistribution.

## Disclaimer

The Alfred tier names ("haiku", "sonnet", "opus") are Alfred Linux
internal identifiers for Alfred's bundled model tiers. Alfred Linux
is an independent project. These names do not imply endorsement by,
affiliation with, or origin from any third-party AI vendor.
