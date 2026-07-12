# GoSiteMe — Agent Specialists

> Define specialist agents that can be invoked for specific types of work.
> Each agent has a focus area, tools it uses, and files it typically touches.

---

## Agent: Frontend Builder
**Focus:** Build and upgrade PHP frontend pages
**Skills:** HTML, CSS, vanilla JS, responsive design, accessibility
**Typical files:** `*.php` (root), `assets/css/`, `assets/js/`
**Protocol:**
1. Read the target `.php` file fully before editing
2. Follow dark-theme design system (`--alfred-*` CSS vars)
3. Mobile-first with `pointer: coarse` touch queries
4. Validate: `php -l`, HTTP 200, visual check
**Prompt template:**
```
Read .github/copilot-instructions.md first. Then upgrade [PAGE].php:
- [SPECIFIC CHANGES]
- Follow dark theme, mobile-first, Font Awesome 6 icons
- Validate with php -l and curl -sI
```

---

## Agent: API Engineer
**Focus:** Build and upgrade API endpoints
**Skills:** PHP, PDO, REST design, JSON, auth, rate limiting
**Typical files:** `api/`, `includes/`, `config/`
**Protocol:**
1. Use PDO prepared statements for all DB queries
2. Validate all inputs with `filter_input()`
3. Return proper HTTP status codes
4. Add CSRF protection where needed
5. Document in `developers/`
**Prompt template:**
```
Read .github/copilot-instructions.md first. Then create/upgrade api/[ENDPOINT].php:
- [SPECIFIC REQUIREMENTS]
- Use PDO prepared statements, validate inputs
- Return JSON with proper HTTP status codes
- Add to API documentation
```

---

## Agent: JavaScript Engine
**Focus:** Build complex JS modules (simulators, engines, visualizations)
**Skills:** ES modules, Canvas API, Web Audio, WebGL, math/physics
**Typical files:** `assets/js/`, inline `<script>` in PHP pages
**Protocol:**
1. Use ES module pattern with named exports
2. No external dependencies — vanilla JS only
3. Validate with `node -c`
4. Write self-contained modules that work without build tools
**Prompt template:**
```
Read .github/copilot-instructions.md first. Then build/upgrade assets/js/[MODULE].js:
- [SPECIFIC FEATURES]
- ES module pattern, no dependencies
- Validate with node -c
```

---

## Agent: Security Auditor
**Focus:** Find and fix security vulnerabilities
**Skills:** OWASP Top 10, PHP security, XSS/SQLi/CSRF prevention
**Typical files:** All PHP files, `api/`, `includes/auth-*.php`
**Protocol:**
1. Scan for SQL injection (non-PDO queries)
2. Scan for XSS (unescaped output)
3. Check CSRF tokens on forms
4. Check auth on protected endpoints
5. Report findings, then fix them
**Prompt template:**
```
Read .github/copilot-instructions.md first. Security audit [FILE/DIRECTORY]:
- Check OWASP Top 10 vulnerabilities
- Fix any SQL injection, XSS, CSRF issues
- Verify auth checks on protected routes
- Report what was found and fixed
```

---

## Agent: Test Writer
**Focus:** Write and maintain automated tests
**Skills:** PHPUnit, shell scripting, curl testing
**Typical files:** `tests/`, `phpunit.xml`
**Protocol:**
1. Match test structure to source structure
2. Test both happy path and error cases
3. Use assertions, not just echo
4. Run tests to verify they pass
**Prompt template:**
```
Read .github/copilot-instructions.md first. Write tests for [FILE/FEATURE]:
- Cover happy path and error cases
- Follow existing test conventions in tests/
- Run and verify all tests pass
```

---

## Agent: DevOps / Infrastructure
**Focus:** CI/CD, deployment, server config, PM2 services
**Skills:** GitHub Actions, shell scripting, PM2, Docker, Caddy
**Typical files:** `.github/workflows/`, `ecosystem.config.js`, `scripts/`, `Caddyfile`, `docker-compose.yml`
**Protocol:**
1. Don't break running services
2. Test scripts with `bash -n` before deploying
3. Use proper error handling in scripts
4. Document any new services added
**Prompt template:**
```
Read .github/copilot-instructions.md first. Then [INFRASTRUCTURE TASK]:
- [SPECIFIC REQUIREMENTS]
- Test scripts before deploying
- Update ecosystem.config.js if adding services
```

---

## Agent: SDK Developer
**Focus:** Build and maintain official SDKs
**Skills:** Node.js, Python, PHP, TypeScript, package management
**Typical files:** `sdks/`, `developers/`
**Protocol:**
1. Keep SDKs in sync with API changes
2. Write comprehensive examples
3. Follow each language's conventions
4. Include type definitions where applicable
**Prompt template:**
```
Read .github/copilot-instructions.md first. Update [LANGUAGE] SDK:
- Sync with latest API endpoints
- Add examples for new features
- Update README and version number
```

---

## Agent: Documentation Writer
**Focus:** Write and maintain docs, READMEs, changelogs
**Skills:** Markdown, API documentation, user guides
**Typical files:** `docs/`, `developers/`, `README.md`, `changelog.php`
**Protocol:**
1. Keep docs accurate to current code
2. Include code examples
3. Use clear, concise language
4. Update changelog for notable changes
**Prompt template:**
```
Read .github/copilot-instructions.md first. Document [FEATURE/API]:
- Write clear, concise documentation
- Include code examples
- Update changelog if applicable
```

---

## Agent: Performance Optimizer
**Focus:** Speed up pages, reduce load times, optimize queries
**Skills:** PHP profiling, JS performance, SQL optimization, caching
**Typical files:** Any PHP/JS file, `config/`, Redis configuration
**Protocol:**
1. Measure before optimizing (timing, bytes, queries)
2. Focus on biggest bottlenecks first
3. Use Redis caching where appropriate
4. Lazy-load non-critical resources
5. Report before/after metrics
**Prompt template:**
```
Read .github/copilot-instructions.md first. Optimize [PAGE/ENDPOINT]:
- Measure current performance
- Identify and fix bottlenecks
- Add caching where appropriate
- Report before/after metrics
```

---

## Spawning Multiple Agents

To run 100K agents effectively, use this workflow:

### 1. Task Distribution
Each agent session picks ONE task from `UPGRADE_BACKLOG.md`:
```
Agent reads backlog → picks unclaimed task → marks [IN PROGRESS] → does work → marks [DONE]
```

### 2. Parallel Streams
Run agents in parallel across different concerns:
- Stream A: Frontend pages (one agent per page)
- Stream B: API endpoints (one agent per endpoint)
- Stream C: Tests (one agent per test file)
- Stream D: Security audit (one agent per directory)
- Stream E: Documentation (one agent per feature)
- Stream F: Performance (one agent per bottleneck)

### 3. Conflict Avoidance
- Each agent works on ONE file or ONE feature
- Never two agents on the same file simultaneously
- Use the backlog to prevent duplicate work
- Lock mechanism: first to mark `[IN PROGRESS]` owns it

### 4. Assembly Line Pattern
For large features, chain agents:
```
Agent 1 (API Engineer) → builds endpoint → 
Agent 2 (Frontend Builder) → builds UI → 
Agent 3 (Test Writer) → writes tests → 
Agent 4 (Docs Writer) → documents it
```
