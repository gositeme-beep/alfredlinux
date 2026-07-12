/**
 * nexusEngine.js — NEXUS: Knowledge Graph & Connections Engine
 *
 * Builds and queries a knowledge graph of project relationships, dependencies,
 * code connections, and contextual links between entities.
 *
 * Capabilities:
 *  - Entity & relationship management (nodes + edges)
 *  - Dependency graph analysis
 *  - Impact analysis (what depends on what)
 *  - Cross-reference discovery
 *  - Project knowledge base
 *  - Relationship visualization data
 */

import { randomUUID } from 'node:crypto';
import fs from 'node:fs/promises';
import path from 'node:path';

const NEXUS_BASE = '/home/gositeme/.gocodeme/nexus';

async function ensureDir(dir) { await fs.mkdir(dir, { recursive: true }); }
async function loadJSON(file, fallback = {}) {
  try { return JSON.parse(await fs.readFile(file, 'utf8')); } catch { return fallback; }
}
async function saveJSON(file, data) {
  await ensureDir(path.dirname(file));
  await fs.writeFile(file, JSON.stringify(data, null, 2));
}

function graphPath(user) { return path.join(NEXUS_BASE, user, 'graph.json'); }
function kbPath(user) { return path.join(NEXUS_BASE, user, 'knowledge_base.json'); }

// ── Entity Management ───────────────────────────────────────────────────────

export async function addEntity(user, entity) {
  const graph = await loadJSON(graphPath(user), { nodes: {}, edges: [] });
  const id = entity.id || `node_${randomUUID().slice(0, 8)}`;
  graph.nodes[id] = {
    id,
    type: entity.type,       // file, function, class, module, service, database, api, person, concept
    name: entity.name,
    properties: entity.properties || {},
    tags: entity.tags || [],
    created: new Date().toISOString(),
    updated: new Date().toISOString(),
  };
  await saveJSON(graphPath(user), graph);
  return { id, message: `Entity "${entity.name}" (${entity.type}) added to graph.` };
}

export async function addRelation(user, relation) {
  const graph = await loadJSON(graphPath(user), { nodes: {}, edges: [] });
  const id = `edge_${randomUUID().slice(0, 8)}`;
  
  if (!graph.nodes[relation.from] && !relation.create_missing) {
    return { error: true, message: `Source entity ${relation.from} not found.` };
  }
  if (!graph.nodes[relation.to] && !relation.create_missing) {
    return { error: true, message: `Target entity ${relation.to} not found.` };
  }

  graph.edges.push({
    id,
    from: relation.from,
    to: relation.to,
    type: relation.type,     // imports, extends, calls, depends_on, uses, contains, references
    weight: relation.weight || 1,
    properties: relation.properties || {},
    created: new Date().toISOString(),
  });
  await saveJSON(graphPath(user), graph);
  return { id, message: `Relation "${relation.type}" added: ${relation.from} → ${relation.to}` };
}

export async function removeEntity(user, entityId) {
  const graph = await loadJSON(graphPath(user), { nodes: {}, edges: [] });
  if (!graph.nodes[entityId]) return { message: `Entity ${entityId} not found.` };
  const name = graph.nodes[entityId].name;
  delete graph.nodes[entityId];
  // Remove edges involving this entity
  const before = graph.edges.length;
  graph.edges = graph.edges.filter(e => e.from !== entityId && e.to !== entityId);
  const removed = before - graph.edges.length;
  await saveJSON(graphPath(user), graph);
  return { message: `Entity "${name}" removed with ${removed} relation(s).` };
}

// ── Query Operations ────────────────────────────────────────────────────────

export async function queryGraph(user, query) {
  const graph = await loadJSON(graphPath(user), { nodes: {}, edges: [] });
  let nodes = Object.values(graph.nodes);
  let edges = graph.edges;

  // Filter nodes
  if (query.type) nodes = nodes.filter(n => n.type === query.type);
  if (query.tag) nodes = nodes.filter(n => n.tags.includes(query.tag));
  if (query.name) nodes = nodes.filter(n => n.name.toLowerCase().includes(query.name.toLowerCase()));
  if (query.search) {
    const q = query.search.toLowerCase();
    nodes = nodes.filter(n => n.name.toLowerCase().includes(q) || n.type.includes(q) || n.tags.some(t => t.includes(q)));
  }

  const nodeIds = new Set(nodes.map(n => n.id));
  if (query.include_edges !== false) {
    edges = edges.filter(e => nodeIds.has(e.from) || nodeIds.has(e.to));
  }

  return {
    nodes: nodes.slice(0, query.limit || 50),
    edges: edges.slice(0, 200),
    total_nodes: nodes.length,
    total_edges: edges.length,
    message: `Found ${nodes.length} node(s), ${edges.length} edge(s).`,
  };
}

export async function getNeighbors(user, entityId, depth = 1) {
  const graph = await loadJSON(graphPath(user), { nodes: {}, edges: [] });
  if (!graph.nodes[entityId]) return { error: true, message: `Entity ${entityId} not found.` };

  const visited = new Set([entityId]);
  const queue = [{ id: entityId, depth: 0 }];
  const resultNodes = [];
  const resultEdges = [];

  while (queue.length > 0) {
    const { id, depth: d } = queue.shift();
    if (d > 0) resultNodes.push(graph.nodes[id]);

    if (d < depth) {
      for (const edge of graph.edges) {
        let neighbor = null;
        if (edge.from === id && !visited.has(edge.to)) neighbor = edge.to;
        if (edge.to === id && !visited.has(edge.from)) neighbor = edge.from;
        if (neighbor && graph.nodes[neighbor]) {
          visited.add(neighbor);
          queue.push({ id: neighbor, depth: d + 1 });
          resultEdges.push(edge);
        }
      }
    }
  }

  return {
    center: graph.nodes[entityId],
    neighbors: resultNodes,
    edges: resultEdges,
    depth,
    message: `Found ${resultNodes.length} neighbor(s) within depth ${depth}.`,
  };
}

// ── Impact Analysis ─────────────────────────────────────────────────────────

export async function impactAnalysis(user, entityId) {
  const graph = await loadJSON(graphPath(user), { nodes: {}, edges: [] });
  if (!graph.nodes[entityId]) return { error: true, message: `Entity ${entityId} not found.` };

  // Find all entities that depend on this one (reverse dependency walk)
  const impacted = new Set();
  const queue = [entityId];

  while (queue.length > 0) {
    const current = queue.shift();
    for (const edge of graph.edges) {
      if (edge.to === current && !impacted.has(edge.from)) {
        impacted.add(edge.from);
        queue.push(edge.from);
      }
    }
  }

  // Also find direct dependencies (what this depends on)
  const dependencies = new Set();
  const depQueue = [entityId];
  while (depQueue.length > 0) {
    const current = depQueue.shift();
    for (const edge of graph.edges) {
      if (edge.from === current && !dependencies.has(edge.to)) {
        dependencies.add(edge.to);
        depQueue.push(edge.to);
      }
    }
  }

  const entity = graph.nodes[entityId];
  return {
    entity: { id: entityId, name: entity.name, type: entity.type },
    impacted_by_change: [...impacted].map(id => ({ id, ...graph.nodes[id] ? { name: graph.nodes[id].name, type: graph.nodes[id].type } : {} })),
    depends_on: [...dependencies].map(id => ({ id, ...graph.nodes[id] ? { name: graph.nodes[id].name, type: graph.nodes[id].type } : {} })),
    risk_level: impacted.size > 10 ? 'HIGH' : impacted.size > 3 ? 'MEDIUM' : 'LOW',
    message: `Impact analysis for "${entity.name}": ${impacted.size} dependents, ${dependencies.size} dependencies. Risk: ${impacted.size > 10 ? 'HIGH' : impacted.size > 3 ? 'MEDIUM' : 'LOW'}`,
  };
}

// ── Auto-Discovery ──────────────────────────────────────────────────────────

export async function discoverDependencies(homeDir, user) {
  const graph = await loadJSON(graphPath(user), { nodes: {}, edges: [] });
  let discovered = { nodes: 0, edges: 0 };

  // Scan package.json for npm dependencies
  try {
    const pkg = JSON.parse(await fs.readFile(path.join(homeDir, 'public_html', 'package.json'), 'utf8'));
    const projectId = `node_project_${pkg.name || 'main'}`;
    
    if (!graph.nodes[projectId]) {
      graph.nodes[projectId] = {
        id: projectId, type: 'module', name: pkg.name || 'main',
        properties: { version: pkg.version }, tags: ['npm', 'root'], created: new Date().toISOString(), updated: new Date().toISOString(),
      };
      discovered.nodes++;
    }

    const allDeps = { ...(pkg.dependencies || {}), ...(pkg.devDependencies || {}) };
    for (const [dep, version] of Object.entries(allDeps)) {
      const depId = `npm_${dep}`;
      if (!graph.nodes[depId]) {
        graph.nodes[depId] = {
          id: depId, type: 'module', name: dep,
          properties: { version }, tags: ['npm', 'dependency'], created: new Date().toISOString(), updated: new Date().toISOString(),
        };
        discovered.nodes++;
      }
      if (!graph.edges.some(e => e.from === projectId && e.to === depId)) {
        graph.edges.push({
          id: `edge_${randomUUID().slice(0, 8)}`, from: projectId, to: depId,
          type: 'depends_on', weight: 1, properties: { version }, created: new Date().toISOString(),
        });
        discovered.edges++;
      }
    }
  } catch {}

  // Scan for file imports (JS/TS files)
  const scanDir = async (dir, depth = 0) => {
    if (depth > 3) return;
    try {
      const entries = await fs.readdir(dir, { withFileTypes: true });
      for (const e of entries) {
        if (['node_modules', '.git', 'vendor'].includes(e.name)) continue;
        const full = path.join(dir, e.name);
        if (e.isDirectory()) { await scanDir(full, depth + 1); continue; }
        if (!/\.(js|ts|jsx|tsx|mjs)$/.test(e.name)) continue;

        const rel = path.relative(homeDir, full);
        const fileId = `file_${rel.replace(/[^a-zA-Z0-9]/g, '_')}`;
        if (!graph.nodes[fileId]) {
          graph.nodes[fileId] = {
            id: fileId, type: 'file', name: rel,
            properties: {}, tags: ['source'], created: new Date().toISOString(), updated: new Date().toISOString(),
          };
          discovered.nodes++;
        }

        try {
          const content = await fs.readFile(full, 'utf8');
          const imports = content.matchAll(/(?:import|require)\s*\(?['"](\.\/[^'"]+|\.\.\/[^'"]+)['"]\)?/g);
          for (const m of imports) {
            const importPath = m[1];
            const resolved = path.relative(homeDir, path.resolve(path.dirname(full), importPath));
            const targetId = `file_${resolved.replace(/[^a-zA-Z0-9]/g, '_')}`;
            if (!graph.edges.some(e => e.from === fileId && e.to === targetId && e.type === 'imports')) {
              graph.edges.push({
                id: `edge_${randomUUID().slice(0, 8)}`, from: fileId, to: targetId,
                type: 'imports', weight: 1, properties: {}, created: new Date().toISOString(),
              });
              discovered.edges++;
            }
          }
        } catch {}
      }
    } catch {}
  };

  await scanDir(path.join(homeDir, 'public_html'));
  await saveJSON(graphPath(user), graph);

  return {
    discovered,
    total_nodes: Object.keys(graph.nodes).length,
    total_edges: graph.edges.length,
    message: `Discovery complete: +${discovered.nodes} nodes, +${discovered.edges} edges. Graph now: ${Object.keys(graph.nodes).length} nodes, ${graph.edges.length} edges.`,
  };
}

// ── Graph Stats ─────────────────────────────────────────────────────────────

export async function getGraphStats(user) {
  const graph = await loadJSON(graphPath(user), { nodes: {}, edges: [] });
  const nodes = Object.values(graph.nodes);

  const typeCount = {};
  for (const n of nodes) {
    typeCount[n.type] = (typeCount[n.type] || 0) + 1;
  }

  const edgeTypeCount = {};
  for (const e of graph.edges) {
    edgeTypeCount[e.type] = (edgeTypeCount[e.type] || 0) + 1;
  }

  // Find most connected nodes
  const connections = {};
  for (const e of graph.edges) {
    connections[e.from] = (connections[e.from] || 0) + 1;
    connections[e.to] = (connections[e.to] || 0) + 1;
  }
  const mostConnected = Object.entries(connections)
    .sort((a, b) => b[1] - a[1])
    .slice(0, 10)
    .map(([id, count]) => ({ id, name: graph.nodes[id]?.name || id, connections: count }));

  return {
    total_nodes: nodes.length,
    total_edges: graph.edges.length,
    node_types: typeCount,
    edge_types: edgeTypeCount,
    most_connected: mostConnected,
    density: nodes.length > 1 ? (graph.edges.length / (nodes.length * (nodes.length - 1))).toFixed(4) : 0,
    message: `Graph: ${nodes.length} nodes, ${graph.edges.length} edges.`,
  };
}

// ── Knowledge Base ──────────────────────────────────────────────────────────

export async function addKnowledge(user, entry) {
  const kb = await loadJSON(kbPath(user), { entries: {} });
  const id = entry.id || `kb_${randomUUID().slice(0, 8)}`;
  kb.entries[id] = {
    id,
    title: entry.title,
    content: entry.content,
    category: entry.category || 'general',
    tags: entry.tags || [],
    references: entry.references || [],  // links to graph entities
    created: new Date().toISOString(),
    updated: new Date().toISOString(),
  };
  await saveJSON(kbPath(user), kb);
  return { id, message: `Knowledge entry "${entry.title}" added.` };
}

export async function searchKnowledge(user, query) {
  const kb = await loadJSON(kbPath(user), { entries: {} });
  const q = query.toLowerCase();
  const results = Object.values(kb.entries).filter(e =>
    e.title.toLowerCase().includes(q) ||
    e.content.toLowerCase().includes(q) ||
    e.tags.some(t => t.toLowerCase().includes(q))
  );
  return {
    results: results.slice(0, 20),
    total: results.length,
    message: `${results.length} knowledge entries match "${query}".`,
  };
}

export async function listKnowledge(user, category) {
  const kb = await loadJSON(kbPath(user), { entries: {} });
  let entries = Object.values(kb.entries);
  if (category) entries = entries.filter(e => e.category === category);
  return {
    entries: entries.map(e => ({ id: e.id, title: e.title, category: e.category, tags: e.tags })),
    total: entries.length,
    message: `${entries.length} knowledge entries.`,
  };
}
