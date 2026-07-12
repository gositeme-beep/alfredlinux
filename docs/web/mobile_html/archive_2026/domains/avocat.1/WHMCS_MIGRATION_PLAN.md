# WHMCS → Custom Billing Portal Migration Plan

## Executive Summary

Replace WHMCS 8.12.1 with a custom PHP/MySQL billing portal. WHMCS is proprietary
software with licensing requirements that create legal risk. Our replacement will be
a lightweight, purpose-built system using the same stack (PHP 8.3 + MariaDB 10.6 + Stripe).

### Current State (Audit Snapshot)
| Metric | Count |
|--------|------:|
| Clients | 22 |
| Products | 93 (20 groups) |
| Orders | 23 |
| Invoices | 75 |
| Domains | 12 |
| Tickets | 20 |
| KB Articles | 103 |
| Announcements | 56 |
| Custom Tables | 58 alfred_* + 5 editor_* |
| Integration Points | 30+ PHP files |
| Payment Gateways | Stripe (primary) + PayPal |
| Server Modules | gocodeme + gositeme_voice |

Small data set = clean migration with zero data loss.

---

## Architecture

### New Database: `gositeme_billing`
All custom tables, alfred_* tables, and editor_* tables move here.
WHMCS's `tblclients` → `clients`, `tblproducts` → `products`, etc.

### Directory Structure
```
/pay/                          ← Custom billing portal (replaces /whmcs/)
  index.php                    ← Client dashboard
  login.php                    ← Login page
  register.php                 ← Registration page
  cart.php                     ← Shopping cart
  checkout.php                 ← Stripe checkout
  invoices.php                 ← Invoice list + detail
  services.php                 ← Active services
  tickets.php                  ← Support tickets
  knowledgebase.php            ← KB articles
  announcements.php            ← News
  domains.php                  ← Domain management
  profile.php                  ← Account settings
  affiliates.php               ← Affiliate dashboard
  api/
    billing-api.php            ← Cart, checkout, invoices API
    ticket-api.php             ← Ticket CRUD API
    domain-api.php             ← Domain registration API
  includes/
    billing-config.php         ← DB connection, Stripe keys
    billing-auth.php           ← Auth middleware
    billing-functions.php      ← Shared helpers
    stripe-handler.php         ← Stripe SDK integration
    paypal-handler.php         ← PayPal integration
    email-templates.php        ← Transactional email HTML
    provisioning.php           ← Service provisioning (GoCodeMe, Voice)
  admin/
    index.php                  ← Admin dashboard
    clients.php                ← Client management
    orders.php                 ← Order management
    products.php               ← Product editor
    invoices.php               ← Invoice management
    tickets.php                ← Ticket management
    kb.php                     ← KB editor
    announcements.php          ← Announcement editor
    settings.php               ← System settings
  templates/
    layout.php                 ← Base layout (matches site design)
    components/                ← Reusable UI components
  assets/
    css/billing.css
    js/billing.js
```

### Clean URL Routing (`.htaccess` updates)
All existing clean URLs stay the same — just repoint to `/pay/`:
```
/login          → /pay/login.php
/register       → /pay/register.php
/cart           → /pay/cart.php
/account        → /pay/index.php
/store/*        → /pay/cart.php?group=*
/invoices       → /pay/invoices.php
/tickets        → /pay/tickets.php
/knowledgebase  → /pay/knowledgebase.php
/announcements  → /pay/announcements.php
/domains        → /pay/domains.php
```

---

## Phase 1: Database & Auth (Sprint 1 — Days 1-3)

### 1A. Create Database Schema
- New database `gositeme_billing`
- Core tables: `clients`, `products`, `product_groups`, `orders`, `order_items`,
  `invoices`, `invoice_items`, `services`, `tickets`, `ticket_replies`,
  `knowledgebase`, `kb_categories`, `announcements`, `domains`,
  `payment_transactions`, `affiliates`, `currencies`, `settings`,
  `email_log`, `addons`, `custom_fields`, `custom_field_values`,
  `pricing` (billing cycles)
- Move all 58 `alfred_*` tables
- Move all 5 `editor_*` tables

### 1B. Data Migration Script
- `scripts/migrate_whmcs.php` — one-shot migration:
  - `tblclients` → `clients` (preserve IDs, password hashes compatible)
  - `tblproducts` + `tblproductgroups` → `products` + `product_groups`
  - `tblorders` → `orders` + `order_items`
  - `tblinvoices` + `tblinvoiceitems` → `invoices` + `invoice_items`
  - `tblhosting` → `services`
  - `tbltickets` + `tblticketreplies` → `tickets` + `ticket_replies`
  - `tblknowledgebase` → `knowledgebase` + `kb_categories`
  - `tblannouncements` → `announcements`
  - `tbldomains` → `domains`
  - `tblaffiliates` → `affiliates`
  - `tblpricing` → `pricing`
  - Rename/move `alfred_*` and `editor_*` tables (ALTER TABLE ... RENAME)

### 1C. Auth System Update
- Already 90% done — `api/auth.php` already does direct SQL to `tblclients`
- Change table name from `tblclients` → `clients`
- Change DB from `gositeme_whmcs` → `gositeme_billing`
- Update `auth-gate.inc.php` — remove WHMCS configuration.php dependency
- Update `api/config.php` — new DB credentials, remove `whmcsAPI()` function
- Password hashes are `password_hash()` compatible — zero changes needed

---

## Phase 2: Client Portal (Sprint 2 — Days 4-7)

### 2A. Client Dashboard (`/pay/index.php`)
- Service overview, recent invoices, quick actions
- Same dark theme as main site (Inter + Space Grotesk, --al-bg:#0a0a14)

### 2B. Services Page (`/pay/services.php`)
- List active/suspended/cancelled services
- Shows product name, next due date, status, amount

### 2C. Invoice System (`/pay/invoices.php`)
- Invoice list with status filters (Paid/Unpaid/Overdue/Cancelled)
- Invoice detail view with line items
- Pay Now button → Stripe Checkout Session
- PDF invoice generation (optional)

### 2D. Profile & Settings (`/pay/profile.php`)
- Update name, email, phone, address
- Change password
- Two-factor authentication (TOTP)

---

## Phase 3: Store & Checkout (Sprint 3 — Days 8-12)

### 3A. Product Catalog
- Store pages render from `products` + `product_groups` tables
- Product cards with pricing tiers (monthly/quarterly/annually/one-time)
- Clean URLs: `/store/hosting`, `/store/tokens`, etc.

### 3B. Shopping Cart (`/pay/cart.php`)
- Session-based cart (no DB until checkout)
- Add/remove products, configure options, apply promo codes

### 3C. Stripe Checkout (`/pay/checkout.php`)
- Create Stripe Checkout Session for one-time products
- Create Stripe Subscription for recurring products
- Webhook handler for `checkout.session.completed`, `invoice.paid`, `invoice.payment_failed`
- Generate internal invoice on successful payment

### 3D. PayPal Integration
- PayPal Standard or Smart Buttons as secondary payment method
- IPN handler for payment confirmation

### 3E. Provisioning Engine
- On successful order: call provisioning functions
- GoCodeMe: POST to middleware API (extract from `gocodeme.php` server module)
- Voice: POST to VAPI API (extract from `gositeme_voice.php` server module)
- DirectAdmin: domain/hosting setup (existing DirectAdmin module logic)

---

## Phase 4: Support & Content (Sprint 4 — Days 13-16)

### 4A. Support Tickets (`/pay/tickets.php`)
- Create/view/reply to tickets
- Priority levels, departments, status tracking
- Email notifications on reply
- Admin-side ticket management

### 4B. Knowledgebase (`/pay/knowledgebase.php`)
- Category list → article list → article detail
- Search functionality
- Already have 103 articles to migrate

### 4C. Announcements (`/pay/announcements.php`)
- Paginated announcement list
- Already have 56 entries to migrate

### 4D. Domain Management (`/pay/domains.php`)
- Existing domain list with status, expiry, auto-renew toggle
- Domain registration integration (OpenSRS/other registrar APIs)
- DNS management (if applicable)

---

## Phase 5: Admin Panel (Sprint 5 — Days 17-21) ✅ COMPLETE

### 5A. Admin Dashboard ✅
- `/pay/admin/index.php` — Revenue overview (total/month/overdue), Chart.js 30-day revenue chart
- Client count, active/pending services, open tickets, pending orders
- Recent orders table, recent tickets, activity feed

### 5B. Admin CRUD Pages ✅ (17 files total)
- **Clients** (`clients.php`): List/search/view/edit, login-as-client, add credit, reset password, delete
- **Products** (`products.php`): List/create/edit, pricing editor (all billing cycles), product groups management
- **Orders** (`orders.php`): List/search, accept/cancel/fraud, order items, admin notes
- **Invoices** (`invoices.php`): List/search, create/mark-paid/mark-unpaid/cancel, Stripe refund integration
- **Services** (`services.php`): List/search, suspend/unsuspend/terminate via Provisioner class, manual edit
- **Tickets** (`tickets.php`): List/search, threaded conversation view, reply/close/assign/priority/department
- **KB Articles** (`kb.php`): CRUD for articles + categories, visibility toggle, view/useful stats
- **Announcements** (`announcements.php`): Create/edit/publish/unpublish, toggle from list view
- **Domains** (`domains.php`): List with expiry tracking, edit all domain fields, EPP code
- **Activity Logs** (`logs.php`): Searchable paginated log viewer
- **Settings** (`settings.php`): Company info, billing automation, Stripe/PayPal config (Super Admin only)
- **Server Status** (`server.php`): CPU/memory/disk gauges, 10-service port check, system info

### 5C. Admin Auth ✅
- `admin_users` table with bcrypt passwords, role enum (Super Admin/Admin/Support)
- `auth.php` middleware: session-based, 12h absolute + 1h idle timeout
- `login.php` standalone login page with audit logging
- `adminAudit()` function logs all admin actions to `activity_log`
- `layout.php` sidebar layout with collapsible navigation, responsive design
- `admin.css` ~400-line dark theme matching client portal design system

---

## Phase 6: Integration Cutover ✅ COMPLETE (Sprint 6)

### 6A. Update All Integration Points ✅
Created shared DB config (`includes/db-config.inc.php`) — single source of truth for all DB credentials.

**Files updated (13 production files):**

| File | Change |
|------|--------|
| `includes/db-config.inc.php` | **NEW** — shared DB config with `getSharedDB()` singleton |
| `api/config.php` | Uses shared db-config, removed hardcoded fallbacks |
| `api/conversations.php` | Replaced hardcoded PDO with `getSharedDB()` |
| `api/auth.php` | Updated Google OAuth callback from `/whmcs/` to `/api/auth.php?action=google-callback` |
| `includes/auth-gate.inc.php` | Replaced 2 hardcoded PDO blocks with `getSharedDB()` |
| `dashboard.php` | Replaced 2 hardcoded PDO blocks with `getSharedDB()` |
| `editor/config.php` | Uses shared db-config constants, removed .env.php loading |
| `status.php` | Uses `getSharedDB()`, removed env var fallbacks |
| `pay/billing-cron.php` | Uses `BILLING_DB_*` constants, migrated all `tblhosting`→`services`, `tbldomains`→`domains`, `tblproducts`→`products`, removed `tblpaymentgateways` query |
| `pay/includes/directadmin.php` | `getDB()` uses config constants, no hardcoded creds |
| `pay/includes/enom.php` | `getDB()` uses config constants, `tblregistrars`→`registrar_settings`, `tbldomains`→`domains` |
| `pay/includes/billing-functions.php` | Removed `tblhosting` dual-write INSERT block |
| `pay/includes/billing-config.php` | Existing — unchanged (single source for billing constants) |

**New table:** `registrar_settings` (id, registrar, setting, value) — migrated 5 eNom settings from `tblregistrars`

**Backward compat kept:** `api/stripe.php` — `whmcs_client_id` metadata fallback for existing subscriptions

**Archived files (to `scripts/archived/`):**
- `migrate_whmcs.php`, `create_whmcs_product.php`, `add_kb_articles.php`, `add_announcements.php`
- `audit_pricing.php`, `create_addons.php`, `create_products_catalog.php`, `fix_slugs.php`
- `revenue_expansion.php`, `revenue_expansion_p2.php`, `verify_whmcs.php`

**Deleted:** 6 `.bak` files (provision.php.bak, directadmin.php.bak, invoices/tickets/domains.php.bak, update-vapi-assistant.php.bak)

### 6B. GoCodeMe Domain ✅
- Already uses clean URLs — no changes needed

### 6C. Stripe SDK ✅
- Uses `STRIPE_SECRET_KEY` from billing-config.php constants
- Stripe webhook at `api/stripe.php` fully operational

### 6D. Cron Jobs ✅
- Custom `pay/billing-cron.php` running every 5 min via DirectAdmin cron
- All 8 tasks: invoices, payments, overdue notices, suspensions, domain sync, renewals, usage reset, cleanup

**NOTE:** After Phase 6, update Google Cloud Console redirect URI:
- Old: `https://gositeme.com/whmcs/index.php?rp=/auth/provider/google_signin/callback`
- New: `https://gositeme.com/api/auth.php?action=google-callback`

---

## Phase 7: Cleanup & Removal (Sprint 7 — Days 26-28)

### 7A. WHMCS Removal
1. Stop WHMCS cron (PM2 stop)
2. Backup `/whmcs/` directory → `/backups/whmcs-YYYYMMDD/`
3. Backup `gositeme_whmcs` database → `gositeme_whmcs_backup_YYYYMMDD.sql`
4. Remove `/whmcs/` directory
5. Drop `gositeme_whmcs` database (after confirming all data migrated)
6. Remove WHMCS license key from env
7. Remove all `.htaccess` WHMCS rewrite rules
8. Remove WHMCS from `config/shield_config.php` exclusions

### 7B. Code Cleanup
- Remove `whmcs_link()` function (replace with direct clean URLs)
- Remove `gocodeme/whmcs-module/` (move provisioning logic into `/pay/includes/provisioning.php`)
- Remove migration scripts
- Remove WHMCS-specific hooks files
- Audit all remaining references

### 7C. Final Verification
- Full site smoke test (all pages, all forms)
- Payment flow test (Stripe checkout, PayPal)
- Voice AI phone test (VAPI → billing lookups)
- Editor auth test (GoCodeMe login)
- Alfred chat test (auth, billing queries)
- Admin panel test (all CRUD operations)

---

## Risk Mitigation

| Risk | Mitigation |
|------|-----------|
| Data loss during migration | Full DB backup before migration, verify record counts |
| Auth disruption | Parallel auth (support both DBs) during transition |
| Payment failures | Test with Stripe test mode first |
| Broken provisioning | Extract provisioning logic early, test independently |
| Missing WHMCS features | Audit showed we only use ~20% of WHMCS features |
| Password incompatibility | Both use `password_hash()` — fully compatible |

---

## Timeline Summary

| Phase | Sprint | Days | Status |
|-------|--------|------|--------|
| 1. Database & Auth | Sprint 1 | 1-3 | **COMPLETE** ✅ |
| 2. Client Portal | Sprint 2 | 4-7 | **COMPLETE** ✅ |
| 3. Store & Checkout | Sprint 3 | 8-12 | 3E ✅ Provisioning, 3C partial ✅ Stripe webhook |
| 4. Support & Content | Sprint 4 | 13-16 | Not started |
| 5. Admin Panel | Sprint 5 | 17-21 | Not started |
| 6. Integration Cutover | Sprint 6 | 22-25 | 6D ✅ Cron jobs |
| 7. Cleanup & Removal | Sprint 7 | 26-28 | Not started |

**Total estimated: ~28 working days (6 weeks)**

---

## Immediate Quick Wins (Can Do Now)
1. ✅ Create the database schema (no WHMCS disruption)
2. ✅ Create the `/pay/` directory structure (no WHMCS disruption)
3. ✅ Install Stripe PHP SDK independently (no WHMCS disruption)
4. ✅ Build migration script (dry-run mode — no data moved yet)

---

## Completed Work Log

### Phase 2 — Client Portal ✅
- Dashboard with service overview, recent invoices, quick actions
- Services page (card + table views) with status badges, Manage buttons
- Invoice system with filters, detail views, Pay Now via Stripe Checkout
- Service detail page: usage gauges (disk/bandwidth/emails/DBs), SSO login,
  quick actions, password management, invoice history, support ticket links
- Domains page with status, expiry, registrar lock
- Tickets page with create/view/reply

### Phase 3E — Provisioning Engine ✅
- **DirectAdmin class** (`/pay/includes/directadmin.php`): Full rewrite ~530 lines
  - Localhost API calls (avoids IP blacklist)
  - Admin credentials encrypted in DB settings table
  - `admin|user` impersonation format for API calls
  - Product → DA package mapping (WordPress1/3/UnlimitedPackage)
  - CreateAccount, SuspendAccount, UnsuspendAccount, TerminateAccount
  - GetUsage (usage + limits), GetLoginUrl (SSO one-time URL), ChangePassword
- **Provisioner class** (`/pay/includes/provisioning.php`): ~700 lines
  - Module dispatch: directadmin, gocodeme, gositeme_voice
  - provision(), suspend(), unsuspend(), terminate(), changePackage()
  - provisionOrder() — provisions all services for an order
  - handleInvoicePaid() — new provisioning + renewal unsuspension
  - autoSuspendOverdue() — 3-day grace period
  - autoTerminateSuspended() — after 14 days
  - Welcome email generation with hosting credentials
- **Stripe webhook** wired to Provisioner (no HTTP roundtrip, direct call)
- **Provision API** (`/pay/api/provision.php`): Thin wrapper around Provisioner

### Phase 6D — Cron Jobs ✅
- **Billing cron** (`/pay/scripts/billing-cron.php`): ~145 lines
  - Mark overdue invoices (past due_date, unpaid)
  - Generate recurring invoices (7 days before due)
  - Auto-suspend overdue services (3-day grace)
  - Auto-terminate long-suspended services (14 days)
- Installed via DirectAdmin cron: `0 0,6,12,18 * * *`
- Old stale cron cleaned up

### Infrastructure
- DA admin password: encrypted in `settings` table (no hardcoded fallback)
- Server: 15.235.50.60, ns1/ns2.gositeme.com, DA v1.696, port 2222
- 9 DA users, 1 reseller (seller1), all verified via API
- SSO generates one-time URLs: `https://gositeme.com:2222/api/login/url?key=...`

