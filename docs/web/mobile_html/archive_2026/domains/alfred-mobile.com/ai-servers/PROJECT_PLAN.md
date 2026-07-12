# AI-Ready Server Build — Project Plan & Launch

**Purpose:** Top-of-the-line custom AI PC/server configurator for purchase. Position as the best “build your AI workstation” experience in Canada — ahead of Dell, Alienware, etc. Focus: AI model–ready hardware (Kimi K2.5, local LLMs, training, inference).

**Phase 2 (later):** Dedicated AI server hosting — out of scope for initial launch.

---

## 1. Product Vision

- **Customize-your-server build for purchase** — user picks every major component; we validate compatibility and show live price.
- **AI-first positioning** — GPUs (24GB–96GB VRAM), 256GB–512GB+ RAM, Threadripper/Xeon W/EPYC, boards that support multi-GPU and ECC.
- **Better than Dell/Alienware:** clearer part names, real specs, compatibility checks, “AI tier” presets, transparent upgrade paths.
- **Canadian market:** CAD, top Canadian suppliers in backend (pricing/markup secret); no supplier names on front.

---

## 2. Target Hardware Tiers (for catalog & presets)

| Tier | Use case | GPU | CPU | RAM | Notes |
|------|----------|-----|-----|-----|--------|
| **Starter AI** | Small LLMs, dev, 24GB models | 1× RTX 5090 / PRO 4000 (24GB) | Ryzen 9 / Core i9 | 64–128GB | Single GPU, one model at a time |
| **Pro AI** | Kimi K2.5 local, 70B class | 1× RTX PRO 5000 (48GB) or 5090 | Threadripper PRO / Xeon W | 256–512GB | 512GB for full K2.5 llama.cpp |
| **Studio** | Training, multi-model, research | 2× RTX 5090 or PRO 5000 | Threadripper PRO / Xeon W | 512GB | Multi-GPU, ECC |
| **Max** | Max local AI, small-team training | RTX PRO 6000 (96GB) or 2× PRO 5000 | Threadripper PRO / EPYC | 512GB–1TB | 96GB VRAM, ECC, workstation boards |

---

## 3. Component Categories & Data Needs

Every product in configurator needs:

- **id** (sku)
- **name**, **shortName**
- **category**: `gpu` | `cpu` | `motherboard` | `ram` | `storage` | `psu` | `case` | `cooling`
- **specs** (object): model-specific (vram, cores, slots, wattage, formFactor, etc.)
- **compatibility** (object): socket, maxRam, pcieSlots, formFactor, etc.
- **imageUrl** (path or URL)
- **msrp** (your selling price — after markup)
- **supplierRef** (internal: supplier sku/id — not shown to user)
- **supplierCost** (internal only — never in frontend)
- **inStock** (boolean)
- **badges**: e.g. `["AI-Ready", "ECC", "Best Value"]`

**Categories to implement first:**

1. **GPU** — VRAM, TDP, length, slots, recommended for “which AI models”.
2. **CPU** — Socket, cores, TDP, memory channels.
3. **Motherboard** — Socket, form factor, max RAM, PCIe layout, ECC support.
4. **RAM** — Capacity per kit, speed, ECC, form factor (UDIMM/RDIMM).
5. **Storage** — NVMe/SSD, capacity, form factor.
6. **PSU** — Wattage, efficiency, form factor.
7. **Case** — Form factor, max GPU length, radiator support.
8. **Cooling** (optional v1) — CPU cooler compatibility (socket, TDP).

---

## 4. Supplier Strategy (Confidential)

- **Sources (internal only):** PC-Canada, Memory Express, Canada Computers, Newegg Canada (and any others you add).
- **Process:** Regularly check supplier prices (manual or later script); update `supplierCost` in admin/back office; apply **markup rules** (e.g. % or fixed) to get `msrp`.
- **Frontend:** Only ever show `msrp` in CAD. No supplier names, no cost, no “compare at”.

**Markup ideas:**  
- Flat % on cost, or  
- Category-specific % (e.g. higher on GPU, lower on RAM), or  
- Min margin in $ per item.

---

## 5. Compatibility Rules (Configurator Logic)

- **CPU ↔ Motherboard:** `cpu.socket` === `motherboard.socket`.
- **Motherboard ↔ Case:** `motherboard.formFactor` in `case.supportedFormFactors`.
- **Motherboard ↔ RAM:** `ram.formFactor` and `ram.type` (e.g. ECC) supported by board; total slots and capacity vs `motherboard.maxRam`, `motherboard.ramSlots`.
- **GPU ↔ Case:** `gpu.length` ≤ `case.maxGpuLength`; `gpu.slots` ≤ available case/motherboard space.
- **GPU ↔ PSU:** Sum of (CPU TDP + GPU TDP + ~100W system) with headroom; recommend PSU wattage tier.
- **Cooling (if used):** Cooler supports `cpu.socket` and cooler TDP ≥ CPU TDP.

Build validator runs on every selection change; invalid combos get a clear message and optional “suggest fix”.

---

## 6. UX / “Better than Dell”

- **Presets:** “Starter AI”, “Pro AI”, “Studio”, “Max” — one click to fill config, then user can swap parts.
- **Step-by-step OR single-page:** Either “Choose CPU → Board → RAM…” with next/back, or one page with all categories and live summary.
- **Live total:** Price and key specs (total RAM, total VRAM, “Runs Kimi K2.5” yes/no) update as they pick.
- **Product cards:** Big image, name, key spec line (e.g. “24GB GDDR7 • 450W”), “AI-Ready” badges, your price (CAD).
- **Comparison:** “Upgrade to X for +$Y” on key components (e.g. GPU, RAM).
- **Mobile-friendly:** Responsive; at least “view build” and “request quote” or “add to cart” on small screens.

---

## 7. Tech Stack (Recommendation)

- **Frontend:** HTML/CSS/JS (or minimal Vue/React if you prefer). Keep deployable under existing `public_html` (e.g. `ai-servers/`).
- **Backend:** PHP (matches your current site). Endpoints: get products by category, get compatible options given current build, save/load build (session or DB), submit quote/order.
- **Data:** Start with **JSON files** per category (e.g. `data/gpus.json`) for fast launch; move to DB when you have many SKUs or need admin UI.
- **Images:** Store under `ai-servers/assets/products/` (e.g. by sku or category); product records point to path/URL. Use placeholders until you have supplier/manufacturer images.
- **Checkout:** Phase 1 = “Request Quote” or “Add to cart” that posts build to your backend; optionally integrate WHMCS later for full checkout.

---

## 8. Launch Checklist

**Before launch:**

- [ ] Product data: at least 2–3 options per category (GPU, CPU, motherboard, RAM, storage, PSU, case).
- [ ] All products have: name, specs, image (or placeholder), msrp (CAD).
- [ ] Compatibility matrix implemented and tested (CPU+MB, MB+case, RAM+MB, GPU+case+PSU).
- [ ] Presets “Starter AI” and “Pro AI” build without errors.
- [ ] Price total and “AI tier” summary correct.
- [ ] Form: “Request Quote” or “Contact for order” with build snapshot (and optional contact info).
- [ ] Mobile layout usable.
- [ ] Legal: Terms for custom builds, disclaimer that specs/prices subject to availability.

**Post-launch:**

- [ ] Add more SKUs from suppliers; refresh prices/markup.
- [ ] Optional: admin page to edit products and costs (or import CSV).
- [ ] Optional: “Save build” (link or account).
- [ ] Optional: WHMCS (or other) for full payment flow.
- [ ] Phase 2: Dedicated server offering (separate project plan).

---

## 9. File Structure (Suggested)

```
ai-servers/
├── PROJECT_PLAN.md          # This file
├── README.md                # How to run / deploy
├── index.php                # Configurator landing
├── configurator.php         # Main build UI (or single-page app entry)
├── api/
│   ├── products.php        # List/filter products
│   ├── compatible.php     # Given build state, return compatible options
│   └── quote.php          # Submit build for quote
├── data/
│   ├── gpus.json
│   ├── cpus.json
│   ├── motherboards.json
│   ├── ram.json
│   ├── storage.json
│   ├── psus.json
│   ├── cases.json
│   └── presets.json       # Preset definitions (which skus per tier)
├── assets/
│   ├── css/
│   ├── js/
│   │   ├── configurator.js
│   │   └── compatibility.js
│   └── products/          # Product images by id or category
└── includes/
    └── config.php         # App config (currency, markup hide, etc.)
```

---

## 10. Top PC Hardware Suppliers (Canada) — Internal Reference

| Supplier | Role | Notes |
|----------|------|--------|
| PC-Canada.com | Parts, many brands | Authorized dealer, 400+ brands |
| Memory Express | Retail, parts & systems | Rebate center, strong component focus |
| Canada Computers | Retail, 28+ locations | Ontario, Quebec, one-stop |
| Newegg Canada | Online, competitive pricing | Gaming/AI parts, frequent promos |

Keep supplier names and exact pricing internal; only display your brand and final CAD price to the customer.

---

*Document version: 1.0 — AI Server Build Project.*
