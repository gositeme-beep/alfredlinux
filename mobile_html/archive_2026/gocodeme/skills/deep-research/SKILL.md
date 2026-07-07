---
name: deep-research
description: "Deep codebase research methodology — systematic exploration of architecture, patterns, and dependencies"
---

# Deep Research Methodology

When performing deep research on a codebase or feature, follow this systematic approach:

## Phase 1: Landscape (Breadth-First)
1. Get the workspace directory structure to understand the project layout
2. Identify key directories: config, API, frontend, middleware, tests, docs
3. Note naming conventions and organizational patterns
4. Count files per directory to gauge complexity

## Phase 2: Target Discovery
1. Search for the specific topic using text search across all files
2. Use multiple search terms (synonyms, abbreviations, related concepts)
3. Find configuration files that reference the feature
4. Identify entry points and exports

## Phase 3: Deep Dive
1. Read each relevant file completely — never skim
2. Trace the call chain: entry point → handler → service → database
3. Map dependencies: what does this file import? What imports it?
4. Note inline comments, TODOs, and FIXMEs
5. Check for tests that document expected behavior

## Phase 4: Pattern Analysis
1. Compare similar components for consistency
2. Identify the "standard pattern" used across the codebase
3. Note deviations from the pattern (intentional vs accidental)
4. Check for duplicate implementations

## Phase 5: Report
Structure findings with:
- **File paths** — always include the full relative path
- **Line numbers** — reference specific lines for key findings
- **Code snippets** — show the relevant code, not just describe it
- **Connections** — explain how components relate to each other
- **Gaps** — what's missing, incomplete, or inconsistent

## Anti-Patterns to Avoid
- Never guess what code does — read it
- Never assume a file exists — verify with search
- Never stop at the first match — search comprehensively
- Never ignore error handling code — it reveals edge cases
- Never skip test files — they document intended behavior
