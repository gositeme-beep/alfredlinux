/**
 * forgeEngine.js — FORGE: Code Generation & Scaffolding Engine
 *
 * AI-powered code generation, boilerplate scaffolding, code transformation,
 * refactoring assistance, and pattern detection.
 *
 * Capabilities:
 *  - Generate boilerplate code from templates
 *  - Create CRUD APIs from database schemas
 *  - Generate test suites
 *  - Code transformation (convert between languages/frameworks)
 *  - Pattern detection and suggestion
 *  - Component generation (React, Vue, etc.)
 */

import { randomUUID } from 'node:crypto';
import fs from 'node:fs/promises';
import path from 'node:path';

const FORGE_BASE = '/home/gositeme/.gocodeme/forge';

async function ensureDir(dir) { await fs.mkdir(dir, { recursive: true }); }
async function loadJSON(file, fallback = {}) {
  try { return JSON.parse(await fs.readFile(file, 'utf8')); } catch { return fallback; }
}
async function saveJSON(file, data) {
  await ensureDir(path.dirname(file));
  await fs.writeFile(file, JSON.stringify(data, null, 2));
}

// ── CRUD Generator ──────────────────────────────────────────────────────────

export async function generateCrud(homeDir, config) {
  const { table, fields, framework } = config;
  const fw = framework || 'express';
  const outputDir = path.join(homeDir, 'public_html', config.output_dir || 'api');
  await ensureDir(outputDir);
  const created = [];

  if (fw === 'express' || fw === 'node') {
    // Generate Express CRUD routes
    const fieldList = fields.map(f => `    ${f.name}: req.body.${f.name}`).join(',\n');
    const validations = fields.filter(f => f.required).map(f => `    if (!req.body.${f.name}) return res.status(400).json({ error: '${f.name} is required' });`).join('\n');

    const routeCode = `import express from 'express';
const router = express.Router();

// In-memory store (replace with database)
let ${table}s = [];
let nextId = 1;

// GET all
router.get('/${table}s', (req, res) => {
  const { page = 1, limit = 20, sort, filter } = req.query;
  let result = [...${table}s];
  if (filter) result = result.filter(item => JSON.stringify(item).toLowerCase().includes(filter.toLowerCase()));
  if (sort) result.sort((a, b) => (a[sort] > b[sort] ? 1 : -1));
  const start = (page - 1) * limit;
  res.json({ data: result.slice(start, start + Number(limit)), total: result.length, page: Number(page) });
});

// GET by ID
router.get('/${table}s/:id', (req, res) => {
  const item = ${table}s.find(i => i.id === Number(req.params.id));
  if (!item) return res.status(404).json({ error: '${table} not found' });
  res.json(item);
});

// POST create
router.post('/${table}s', (req, res) => {
${validations}
  const item = { id: nextId++,
${fieldList},
    created_at: new Date().toISOString(),
    updated_at: new Date().toISOString()
  };
  ${table}s.push(item);
  res.status(201).json(item);
});

// PUT update
router.put('/${table}s/:id', (req, res) => {
  const idx = ${table}s.findIndex(i => i.id === Number(req.params.id));
  if (idx === -1) return res.status(404).json({ error: '${table} not found' });
  ${table}s[idx] = { ...${table}s[idx], ...req.body, updated_at: new Date().toISOString() };
  res.json(${table}s[idx]);
});

// DELETE
router.delete('/${table}s/:id', (req, res) => {
  const idx = ${table}s.findIndex(i => i.id === Number(req.params.id));
  if (idx === -1) return res.status(404).json({ error: '${table} not found' });
  ${table}s.splice(idx, 1);
  res.status(204).end();
});

export default router;
`;
    const filePath = path.join(outputDir, `${table}Routes.js`);
    await fs.writeFile(filePath, routeCode);
    created.push(`${table}Routes.js`);

  } else if (fw === 'php') {
    const phpFields = fields.map(f => `'${f.name}'`).join(', ');
    const phpCode = `<?php
// ${table} CRUD API
header('Content-Type: application/json');

$pdo = new PDO('mysql:host=localhost;dbname=' . ($_ENV['DB_NAME'] ?? 'mydb'), $_ENV['DB_USER'] ?? 'root', $_ENV['DB_PASS'] ?? '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

switch ($method) {
    case 'GET':
        if ($id) {
            $stmt = $pdo->prepare("SELECT * FROM ${table} WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: ['error' => 'Not found']);
        } else {
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;
            $stmt = $pdo->query("SELECT COUNT(*) FROM ${table}");
            $total = $stmt->fetchColumn();
            $stmt = $pdo->prepare("SELECT * FROM ${table} LIMIT ? OFFSET ?");
            $stmt->execute([$limit, $offset]);
            echo json_encode(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'total' => (int)$total, 'page' => $page]);
        }
        break;
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $fields = [${phpFields}];
        $vals = array_map(fn($f) => $data[$f] ?? null, $fields);
        $placeholders = implode(',', array_fill(0, count($fields), '?'));
        $fieldStr = implode(',', $fields);
        $stmt = $pdo->prepare("INSERT INTO ${table} ($fieldStr) VALUES ($placeholders)");
        $stmt->execute($vals);
        echo json_encode(['id' => $pdo->lastInsertId(), 'message' => 'Created']);
        break;
    case 'PUT':
        if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID required']); break; }
        $data = json_decode(file_get_contents('php://input'), true);
        $sets = [];
        $vals = [];
        foreach ($data as $k => $v) { $sets[] = "$k = ?"; $vals[] = $v; }
        $vals[] = $id;
        $stmt = $pdo->prepare("UPDATE ${table} SET " . implode(', ', $sets) . " WHERE id = ?");
        $stmt->execute($vals);
        echo json_encode(['message' => 'Updated']);
        break;
    case 'DELETE':
        if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID required']); break; }
        $stmt = $pdo->prepare("DELETE FROM ${table} WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['message' => 'Deleted']);
        break;
}
`;
    const filePath = path.join(outputDir, `${table}_api.php`);
    await fs.writeFile(filePath, phpCode);
    created.push(`${table}_api.php`);
  }

  return { table, framework: fw, files: created, output_dir: config.output_dir || 'api', message: `CRUD API for "${table}" generated (${fw}): ${created.join(', ')}` };
}

// ── Component Generator ─────────────────────────────────────────────────────

export async function generateComponent(homeDir, config) {
  const { name, type, framework, props } = config;
  const outputDir = path.join(homeDir, 'public_html', config.output_dir || 'src/components');
  await ensureDir(outputDir);

  let code = '';
  const fileName = `${name}.${framework === 'vue' ? 'vue' : framework === 'svelte' ? 'svelte' : 'jsx'}`;

  if (framework === 'react' || !framework) {
    const propsType = props ? `{ ${props.map(p => `${p.name}: ${p.type || 'string'}`).join(', ')} }` : '{}';
    const propsDestructure = props ? `{ ${props.map(p => p.name).join(', ')} }` : '{}';

    if (type === 'form') {
      code = `import { useState } from 'react';

export default function ${name}(${propsDestructure ? `{ onSubmit, ...props }` : ''}) {
  const [formData, setFormData] = useState({${props ? props.map(p => `\n    ${p.name}: ''`).join(',') + '\n  ' : ''}});
  const [errors, setErrors] = useState({});
  const [loading, setLoading] = useState(false);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
    if (errors[name]) setErrors(prev => ({ ...prev, [name]: null }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    try {
      await onSubmit?.(formData);
    } catch (err) {
      setErrors({ submit: err.message });
    } finally {
      setLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="${name.toLowerCase()}-form">
${props ? props.map(p => `      <div className="form-group">
        <label htmlFor="${p.name}">${p.name}</label>
        <input id="${p.name}" name="${p.name}" type="${p.type === 'number' ? 'number' : 'text'}" value={formData.${p.name}} onChange={handleChange} ${p.required ? 'required' : ''} />
        {errors.${p.name} && <span className="error">{errors.${p.name}}</span>}
      </div>`).join('\n') : '      {/* Add form fields */}'}
      {errors.submit && <div className="error">{errors.submit}</div>}
      <button type="submit" disabled={loading}>{loading ? 'Submitting...' : 'Submit'}</button>
    </form>
  );
}
`;
    } else if (type === 'list') {
      code = `import { useState, useEffect } from 'react';

export default function ${name}({ items = [], loading = false, onItemClick }) {
  const [search, setSearch] = useState('');
  const [sortBy, setSortBy] = useState(null);

  const filtered = items.filter(item =>
    JSON.stringify(item).toLowerCase().includes(search.toLowerCase())
  );

  const sorted = sortBy
    ? [...filtered].sort((a, b) => (a[sortBy] > b[sortBy] ? 1 : -1))
    : filtered;

  if (loading) return <div className="loading">Loading...</div>;

  return (
    <div className="${name.toLowerCase()}-list">
      <input type="search" placeholder="Search..." value={search} onChange={e => setSearch(e.target.value)} className="search-input" />
      <div className="item-count">{sorted.length} item(s)</div>
      <ul>
        {sorted.map((item, i) => (
          <li key={item.id || i} onClick={() => onItemClick?.(item)} className="list-item">
            {JSON.stringify(item)}
          </li>
        ))}
      </ul>
      {sorted.length === 0 && <p className="empty">No items found.</p>}
    </div>
  );
}
`;
    } else {
      // Default component
      code = `export default function ${name}(${propsDestructure}) {
  return (
    <div className="${name.toLowerCase()}">
      <h2>${name}</h2>
      {/* Component content */}
    </div>
  );
}
`;
    }
  } else if (framework === 'vue') {
    code = `<template>
  <div class="${name.toLowerCase()}">
    <h2>{{ title }}</h2>
    <slot />
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, default: '${name}' },
${props ? props.map(p => `  ${p.name}: { type: ${(p.type || 'String').charAt(0).toUpperCase() + (p.type || 'string').slice(1)}, ${p.required ? 'required: true' : `default: ${p.type === 'number' ? '0' : "''"}`} },`).join('\n') : ''}
});
</script>

<style scoped>
.${name.toLowerCase()} {
  padding: 1rem;
}
</style>
`;
  }

  await fs.writeFile(path.join(outputDir, fileName), code);
  return { component: name, framework: framework || 'react', type: type || 'default', file: fileName, message: `Component "${name}" generated (${framework || 'react'}/${type || 'default'}).` };
}

// ── Test Generator ──────────────────────────────────────────────────────────

export async function generateTests(homeDir, config) {
  const { source_file, framework } = config;
  const fw = framework || 'jest';
  const sourcePath = path.join(homeDir, source_file);
  const sourceCode = await fs.readFile(sourcePath, 'utf8');

  // Extract function names
  const funcMatches = sourceCode.matchAll(/(?:export\s+)?(?:async\s+)?function\s+(\w+)|(?:export\s+)?const\s+(\w+)\s*=\s*(?:async\s+)?\(/g);
  const functions = [...funcMatches].map(m => m[1] || m[2]).filter(Boolean);

  const testDir = path.join(homeDir, 'public_html', '__tests__');
  await ensureDir(testDir);

  const baseName = path.basename(source_file, path.extname(source_file));
  let testCode = '';

  if (fw === 'jest' || fw === 'vitest') {
    const importStatement = fw === 'vitest'
      ? `import { describe, it, expect, vi } from 'vitest';`
      : '';

    testCode = `${importStatement}
import { ${functions.join(', ')} } from '../${source_file.replace(/^public_html\//, '')}';

describe('${baseName}', () => {
${functions.map(fn => `  describe('${fn}', () => {
    it('should be defined', () => {
      expect(${fn}).toBeDefined();
      expect(typeof ${fn}).toBe('function');
    });

    it('should execute without throwing', async () => {
      // TODO: Add appropriate test arguments
      await expect(async () => await ${fn}()).resolves;
    });

    it('should return expected result', async () => {
      // TODO: Add specific test cases
      const result = await ${fn}();
      expect(result).toBeDefined();
    });
  });
`).join('\n')}});
`;
  }

  const testFile = `${baseName}.test.${source_file.endsWith('.ts') ? 'ts' : 'js'}`;
  await fs.writeFile(path.join(testDir, testFile), testCode);

  return {
    source: source_file,
    test_file: `__tests__/${testFile}`,
    functions_found: functions.length,
    functions,
    framework: fw,
    message: `Test suite generated for ${baseName}: ${functions.length} function(s), ${fw} framework.`,
  };
}

// ── Code Analysis ───────────────────────────────────────────────────────────

export async function analyzeCode(homeDir, filePath) {
  const full = path.join(homeDir, filePath);
  const code = await fs.readFile(full, 'utf8');
  const lines = code.split('\n');

  const analysis = {
    file: filePath,
    lines: lines.length,
    chars: code.length,
    language: path.extname(filePath).slice(1),
    metrics: {
      functions: 0,
      classes: 0,
      imports: 0,
      exports: 0,
      comments: 0,
      blank_lines: 0,
      todo_comments: 0,
      max_line_length: 0,
      avg_line_length: 0,
    },
    complexity: {
      conditionals: 0,
      loops: 0,
      try_catch: 0,
      nesting_depth: 0,
    },
    issues: [],
  };

  let totalLength = 0;
  let maxNesting = 0;
  let currentNesting = 0;

  for (const line of lines) {
    const trimmed = line.trim();
    totalLength += line.length;
    if (line.length > analysis.metrics.max_line_length) analysis.metrics.max_line_length = line.length;

    if (!trimmed) { analysis.metrics.blank_lines++; continue; }
    if (trimmed.startsWith('//') || trimmed.startsWith('#') || trimmed.startsWith('/*') || trimmed.startsWith('*')) analysis.metrics.comments++;
    if (/TODO|FIXME|HACK|XXX/i.test(trimmed)) analysis.metrics.todo_comments++;
    if (/^import\s|^require\(|^from\s/.test(trimmed)) analysis.metrics.imports++;
    if (/^export\s/.test(trimmed)) analysis.metrics.exports++;
    if (/function\s+\w+|=>\s*{|=\s*function/.test(trimmed)) analysis.metrics.functions++;
    if (/^class\s+\w+/.test(trimmed)) analysis.metrics.classes++;
    if (/\bif\s*\(|\belse\b|\bswitch\s*\(|\b\?\s*:/.test(trimmed)) analysis.complexity.conditionals++;
    if (/\bfor\s*\(|\bwhile\s*\(|\b\.forEach\(|\b\.map\(|\b\.filter\(/.test(trimmed)) analysis.complexity.loops++;
    if (/\btry\s*{|\bcatch\s*\(/.test(trimmed)) analysis.complexity.try_catch++;

    currentNesting += (trimmed.match(/{/g) || []).length;
    currentNesting -= (trimmed.match(/}/g) || []).length;
    if (currentNesting > maxNesting) maxNesting = currentNesting;

    // Issue detection
    if (line.length > 120) analysis.issues.push({ line: lines.indexOf(line) + 1, issue: `Line too long (${line.length} chars)` });
    if (/console\.log\(/.test(trimmed)) analysis.issues.push({ line: lines.indexOf(line) + 1, issue: 'console.log left in code' });
    if (/debugger;/.test(trimmed)) analysis.issues.push({ line: lines.indexOf(line) + 1, issue: 'debugger statement' });
  }

  analysis.metrics.avg_line_length = Math.round(totalLength / lines.length);
  analysis.complexity.nesting_depth = maxNesting;
  analysis.issues = analysis.issues.slice(0, 20); // Limit issues

  // Overall complexity rating
  const complexityScore = analysis.complexity.conditionals + analysis.complexity.loops * 2 + analysis.complexity.nesting_depth * 3;
  analysis.complexity.rating = complexityScore > 50 ? 'HIGH' : complexityScore > 20 ? 'MEDIUM' : 'LOW';

  return analysis;
}

// ── Snippet Library ─────────────────────────────────────────────────────────

function snippetsPath(user) { return path.join(FORGE_BASE, user, 'snippets.json'); }

export async function saveSnippet(user, snippet) {
  const lib = await loadJSON(snippetsPath(user), { snippets: {} });
  const id = snippet.id || `snip_${randomUUID().slice(0, 8)}`;
  lib.snippets[id] = {
    id,
    name: snippet.name,
    language: snippet.language || 'javascript',
    tags: snippet.tags || [],
    code: snippet.code,
    description: snippet.description || '',
    created: new Date().toISOString(),
    uses: 0,
  };
  await saveJSON(snippetsPath(user), lib);
  return { id, message: `Snippet "${snippet.name}" saved.` };
}

export async function listSnippets(user, language, tag) {
  const lib = await loadJSON(snippetsPath(user), { snippets: {} });
  let list = Object.values(lib.snippets);
  if (language) list = list.filter(s => s.language === language);
  if (tag) list = list.filter(s => s.tags.includes(tag));
  return {
    snippets: list.map(s => ({ id: s.id, name: s.name, language: s.language, tags: s.tags, uses: s.uses })),
    total: list.length,
    message: `${list.length} snippet(s).`,
  };
}

export async function getSnippet(user, snippetId) {
  const lib = await loadJSON(snippetsPath(user), { snippets: {} });
  const snippet = lib.snippets[snippetId];
  if (!snippet) return { message: `Snippet ${snippetId} not found.` };
  snippet.uses++;
  await saveJSON(snippetsPath(user), lib);
  return snippet;
}
