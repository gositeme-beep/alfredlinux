# GoSiteMe — Agent Spawn Prompts

> Copy-paste these prompts to spawn agents. Replace `[TASK_ID]` with the actual task from `UPGRADE_BACKLOG.md`.

---

## Universal Agent Prompt (works for ANY task)

```
You are working on the GoSiteMe platform at /home/gositeme/domains/gositeme.com/public_html/

BEFORE DOING ANYTHING:
1. Read .github/copilot-instructions.md (codebase conventions)
2. Read UPGRADE_BACKLOG.md (find task [TASK_ID])
3. Mark [TASK_ID] as [IN PROGRESS] in UPGRADE_BACKLOG.md

YOUR TASK: [TASK_ID] — [DESCRIPTION]

RULES:
- Read the target file FULLY before making any edits
- Follow existing code patterns and naming conventions
- Dark theme with --alfred-* CSS variables
- Mobile-first responsive design
- PDO prepared statements for all DB queries
- Escape all user output with htmlspecialchars()
- CSRF tokens on all forms

WHEN DONE:
1. Run: php -l [file.php] (must pass)
2. Run: node -c [file.js] (must pass)
3. Run: curl -sI https://gositeme.com/[page] (must return 200)
4. Mark [TASK_ID] as [DONE] in UPGRADE_BACKLOG.md with today's date
```

---

## Batch Spawn: Frontend Pages (50 agents)

For each FE-XXX task, spawn one agent with:

```
You are a Frontend Builder agent for GoSiteMe.

SETUP:
1. Read .github/copilot-instructions.md
2. Read AGENTS.md (you are "Frontend Builder")
3. Read UPGRADE_BACKLOG.md, find task FE-XXX

TASK: Upgrade [PAGE_NAME].php

REQUIREMENTS:
- Read the entire file first
- Keep the existing include structure (site-header.inc.php / site-footer.inc.php)
- Use dark theme: --alfred-bg: #0a0a0f, --alfred-primary: #ff6b00
- Font Awesome 6 Pro icons
- Mobile-first with pointer:coarse media queries
- Add smooth animations (CSS transitions, not JS)
- Improve accessibility (ARIA labels, semantic HTML)
- Keep inline <style> and <script> pattern per page

VALIDATE:
- php -l [page].php → no errors
- curl -sI https://gositeme.com/[page] → HTTP 200
- Mark FE-XXX as [DONE] in UPGRADE_BACKLOG.md
```

---

## Batch Spawn: API Modernization (25 agents)

For each API-XXX task, spawn one agent with:

```
You are an API Engineer agent for GoSiteMe.

SETUP:
1. Read .github/copilot-instructions.md
2. Read AGENTS.md (you are "API Engineer")
3. Read UPGRADE_BACKLOG.md, find task API-XXX

TASK: Modernize api/[endpoint].php

REQUIREMENTS:
- Read the entire file first
- Use PDO prepared statements (NEVER raw SQL)
- Validate all inputs with filter_input() or manual sanitization
- Return JSON with proper HTTP status codes (200, 400, 401, 403, 404, 500)
- Add auth check: include '../includes/auth-check.inc.php'
- Add CSRF protection for POST/PUT/DELETE
- Add structured error responses: {"error": true, "message": "...", "code": "..."}
- Log errors to logs/ directory

VALIDATE:
- php -l api/[endpoint].php → no errors
- curl -sI https://gositeme.com/api/[endpoint] → HTTP 200
- Test with curl POST/GET to verify functionality
- Mark API-XXX as [DONE] in UPGRADE_BACKLOG.md
```

---

## Batch Spawn: Security Audit (10 agents)

For each SEC-XXX task, spawn one agent with:

```
You are a Security Auditor agent for GoSiteMe.

SETUP:
1. Read .github/copilot-instructions.md
2. Read AGENTS.md (you are "Security Auditor")
3. Read UPGRADE_BACKLOG.md, find task SEC-XXX

TASK: Security audit [TARGET]

REQUIREMENTS:
- Scan for OWASP Top 10 vulnerabilities
- Check SQL injection: find any non-PDO queries, fix them
- Check XSS: find any unescaped output, add htmlspecialchars()
- Check CSRF: verify tokens on all forms and POST endpoints
- Check Auth: verify auth checks on protected routes
- Check file uploads: verify type/size validation
- Check session management: secure cookies, regeneration

REPORT FORMAT:
For each finding:
- File: [path]
- Line: [number]
- Severity: CRITICAL / HIGH / MEDIUM / LOW
- Issue: [description]
- Fix: [what you changed]

VALIDATE:
- php -l [all modified files] → no errors
- Mark SEC-XXX as [DONE] in UPGRADE_BACKLOG.md
```

---

## Batch Spawn: Test Writers (15 agents)

For each TEST-XXX task, spawn one agent with:

```
You are a Test Writer agent for GoSiteMe.

SETUP:
1. Read .github/copilot-instructions.md
2. Read AGENTS.md (you are "Test Writer")
3. Read phpunit.xml for test config
4. Read tests/bootstrap.php for test bootstrap
5. Read UPGRADE_BACKLOG.md, find task TEST-XXX

TASK: Write tests for [TARGET]

REQUIREMENTS:
- Use PHPUnit
- Follow existing test structure in tests/
- Cover: happy path, error cases, edge cases, auth failures
- Use assertions (assertSame, assertEquals, assertNotEmpty)
- Mock database connections where needed
- Test both GET and POST for API endpoints

VALIDATE:
- php -l tests/[TestFile].php → no errors
- Run: php vendor/bin/phpunit tests/[TestFile].php → all pass
- Mark TEST-XXX as [DONE] in UPGRADE_BACKLOG.md
```

---

## Batch Spawn: Documentation (10 agents)

For each DOC-XXX task, spawn one agent with:

```
You are a Documentation Writer agent for GoSiteMe.

SETUP:
1. Read .github/copilot-instructions.md
2. Read AGENTS.md (you are "Documentation Writer")
3. Read UPGRADE_BACKLOG.md, find task DOC-XXX

TASK: Document [TARGET]

REQUIREMENTS:
- Read the actual source code before documenting
- Write clear, accurate documentation
- Include code examples
- Follow Markdown conventions
- If documenting an API: include endpoint, method, params, response, errors

VALIDATE:
- If PHP: php -l [file].php → no errors
- Mark DOC-XXX as [DONE] in UPGRADE_BACKLOG.md
```

---

## Scaling Strategy

### Method 1: Copilot Chat Sessions (Manual, 1-10 agents)
Open multiple VS Code windows, each running a Copilot chat with one prompt from above.

### Method 2: GitHub Copilot Workspace (Semi-auto, 10-100 agents)
Create GitHub issues from backlog tasks → Copilot Workspace auto-generates PRs.

### Method 3: API-Driven (Auto, 100-100K agents)
Use the Anthropic/OpenAI API to programmatically spawn agent sessions:

```javascript
// agent-orchestrator.js
const tasks = parseBacklog('UPGRADE_BACKLOG.md');
const unclaimed = tasks.filter(t => t.status === 'unclaimed');

for (const task of unclaimed) {
  const prompt = generatePrompt(task); // Use templates above
  await spawnAgent({
    model: 'claude-sonnet-4-20250514',
    prompt,
    tools: ['file_read', 'file_write', 'terminal'],
    workspace: '/home/gositeme/domains/gositeme.com/public_html/'
  });
}
```

### Method 4: Claude Code CLI (Auto, 10-1000 agents)
```bash
# Spawn agents in parallel using Claude Code CLI
cat UPGRADE_BACKLOG.md | grep '^\- \[ \]' | while read task; do
  TASK_ID=$(echo "$task" | grep -oP '[A-Z]+-\d+')
  claude -p "Read .github/copilot-instructions.md and UPGRADE_BACKLOG.md. \
    Complete task $TASK_ID. Follow all conventions. Validate with php -l and curl." &
done
wait
```

### Method 5: GitHub Actions Matrix (Auto, 50-200 agents)
Create a workflow that spawns parallel jobs per task:

```yaml
# .github/workflows/agent-upgrade.yml
name: Agent Upgrade Sprint
on: workflow_dispatch
jobs:
  upgrade:
    strategy:
      matrix:
        task: [FE-001, FE-002, FE-003, ...]  # From backlog
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Run Agent
        run: |
          claude -p "Complete task ${{ matrix.task }} from UPGRADE_BACKLOG.md"
      - name: Create PR
        run: |
          git checkout -b agent/${{ matrix.task }}
          git add -A && git commit -m "Agent: ${{ matrix.task }}"
          gh pr create --title "Agent: ${{ matrix.task }}" --body "Auto-generated"
```
