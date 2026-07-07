# AI Server Configurator

Custom **AI-ready workstation** build configurator. Users pick GPU, CPU, motherboard, RAM, storage, PSU, and case; compatibility is validated and price is summed. Optional presets (Starter AI, Pro AI, Studio, Max) and “Request Quote” submission.

## Quick start

- **Configurator:** `https://yoursite.com/ai-servers/` or `/ai-servers/configurator.php`
- **API (all products + presets):** `GET /ai-servers/api/all.php`
- **Request quote:** `POST /ai-servers/api/quote.php` with JSON `{ "build": { "gpu": "id", ... }, "contact": { "email": "..." } }`

## Structure

- `configurator.php` — main build UI
- `api/all.php` — all products and presets (no supplier/cost fields)
- `api/products.php?category=gpus|cpus|...` — products by category
- `api/presets.php` — preset name → component ids
- `api/quote.php` — accept quote request (optionally email/DB)
- `data/*.json` — product and preset data; add `msrp` (your price), keep `supplierRef`/`supplierCost` out of frontend
- `assets/products/` — product images; filenames match `imageUrl` in JSON (or use placeholders)
- `scripts/pull_product_images.php` — pulls images from `imageSourceUrl` or generates placeholders

## Product images

- **Pull placeholders:** Run `php scripts/pull_product_images.php` from the `ai-servers` directory. This downloads a placeholder for every product into `assets/products/`.
- **Real images:** Add optional `imageSourceUrl` (full URL) to any product in `data/*.json`, then run the script again. Or replace image files in `assets/products/` manually (same filenames as `imageUrl`).

## Product data

Each product has: `id`, `name`, `shortName`, `category`, `specs`, `compatibility`, `imageUrl`, `msrp`, `inStock`, `badges`.  
Internal only (strip before sending to frontend): `supplierRef`, `supplierCost`.

Compatibility is used to filter options (e.g. CPU socket ↔ motherboard, case form factor, RAM type/slots).

## Pricing

Set `msrp` in JSON to your selling price (CAD). Supplier cost and markup stay server-side or in admin only; see `PROJECT_PLAN.md` for supplier strategy.

## Launch checklist

See **PROJECT_PLAN.md** for full scope, supplier list, compatibility rules, and launch checklist.

## WHMCS layout and cart

The configurator uses the main site header and footer. Add a nav link or CTA to `/ai-servers/` (e.g. “Build AI Server” or “Custom AI Workstation”) from your main site.
