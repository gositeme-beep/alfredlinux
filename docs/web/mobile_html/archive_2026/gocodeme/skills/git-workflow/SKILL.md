---
name: git-workflow
description: "Git workflow best practices — branching, commit messages, conflict resolution, and history management"
---

# Git Workflow

## Commit Messages
Use conventional commit format:
```
type(scope): brief description

Longer explanation if needed.
```

Types: `feat`, `fix`, `docs`, `style`, `refactor`, `perf`, `test`, `chore`

Examples:
- `feat(api): add user endpoints for dashboard`
- `fix(auth): prevent session fixation on login`
- `perf(dashboard): cache Redis queries for 5 min`

## Smart Commit Workflow
Use the `smart_commit` MCP tool to auto-generate commit messages based on staged changes.

## Before Committing
1. Review all changes with `git diff`
2. Check for debug code: `console.log`, `var_dump`, `print_r`
3. Check for hardcoded credentials or API keys
4. Verify no `.env` files are staged
5. Run syntax checks: `php -l` for PHP, `node -c` for JS

## Branching
- `main` — production, always deployable
- `feat/feature-name` — new features
- `fix/issue-description` — bug fixes
- `hotfix/critical-fix` — emergency production fixes
