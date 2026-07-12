---
name: gositeme-conventions
description: "GoSiteMe codebase conventions — PHP page structure, CSS theming, JS patterns, API design"
---

# GoSiteMe Codebase Conventions

## PHP Page Structure
Every `.php` page follows this pattern:
```php
<?php
$pageTitle = "Page Name";
$pageDescription = "SEO description";
include 'includes/site-header.inc.php';
?>
<!-- Page content (HTML + inline <style> + inline <script>) -->
<?php include 'includes/site-footer.inc.php'; ?>
```

## CSS Design System
- Dark theme with CSS custom properties: `--alfred-primary`, `--alfred-bg`, etc.
- Class prefix per feature: `.cs-` (circuit sim), `.vc-` (voice), `.ag-` (agent)
- BEM-like naming: `.cs-component-element` or `.cs-component--modifier`
- Mobile-first: use `pointer: coarse` media queries for touch devices
- Responsive breakpoints: standard mobile/tablet/desktop

## JavaScript Conventions
- ES module pattern with IIFE or module scope
- Export via `window.ModuleName` or ES `export`
- No build step — raw JS served directly
- Use `fetch()` for API calls, never jQuery
- camelCase for variables/functions, PascalCase for classes, UPPER_SNAKE for constants

## API Endpoints
- Located in `api/` directory
- Must include auth: `include '../includes/auth-check.inc.php';`
- Use PDO prepared statements for all DB queries
- Return JSON: `header('Content-Type: application/json'); echo json_encode($result);`
- Proper HTTP status codes

## Critical Files — Do NOT Break
- `includes/site-header.inc.php` — every page depends on this
- `includes/site-footer.inc.php` — every page depends on this
- `config/database.php` — DB connection
- `api/tools.php` — tool registry API
- `index.php`, `login.php`, `dashboard.php` — core pages

## Key Directories
| Directory | Purpose |
|-----------|---------|
| `api/` | Backend API endpoints (PHP) |
| `assets/js/` | Shared JavaScript modules |
| `assets/css/` | Shared stylesheets |
| `includes/` | PHP includes (header, footer, auth, billing) |
| `config/` | Configuration files |
| `templates/` | Email and page templates |
