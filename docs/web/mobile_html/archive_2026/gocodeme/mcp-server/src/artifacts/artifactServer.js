/**
 * artifactServer.js — Artifact Storage & Serving
 *
 * Stores and serves generated artifacts (charts, diagrams, HTML previews).
 * Each artifact gets a unique ID and can be retrieved via HTTP.
 *
 * Artifacts are stored in memory with optional disk persistence.
 * Max 200 artifacts, LRU eviction.
 */

import { mkdir, writeFile, readFile } from 'node:fs/promises';
import path from 'node:path';
import os from 'node:os';
import { generateChart } from './chartGenerator.js';
import { renderDiagram } from './diagramRenderer.js';
import { generatePreview } from './htmlPreview.js';

const ARTIFACT_DIR = path.join(os.homedir(), '.gocodeme', 'artifacts');
const MAX_ARTIFACTS = 200;

// In-memory store: Map<id, {content, mimeType, createdAt, type, metadata}>
const artifacts = new Map();

function genId() {
  return 'art-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 6);
}

/**
 * Store an artifact.
 */
function storeArtifact(content, mimeType, type, metadata = {}) {
  // LRU eviction
  if (artifacts.size >= MAX_ARTIFACTS) {
    const oldest = artifacts.keys().next().value;
    artifacts.delete(oldest);
  }

  const id = genId();
  artifacts.set(id, {
    content,
    mimeType,
    type,
    metadata,
    createdAt: new Date().toISOString(),
  });

  return id;
}

/**
 * Get an artifact by ID.
 * @param {string} id
 * @returns {{content: string|Buffer, mimeType: string, type: string, metadata: object}|null}
 */
export function getArtifact(id) {
  return artifacts.get(id) || null;
}

/**
 * Create a chart artifact.
 *
 * @param {object} opts — see chartGenerator.generateChart
 * @returns {Promise<{artifactId: string, html: string, base64: string|null, mimeType: string, timing: number}>}
 */
export async function createChart(opts) {
  const chart = await generateChart(opts);
  const id = storeArtifact(chart.html, 'text/html', 'chart', {
    chartType: opts.type,
    width: opts.width,
    height: opts.height,
  });

  return {
    artifactId: id,
    html: chart.html,
    base64: chart.base64,
    mimeType: chart.mimeType,
    type: opts.type,
    timing: chart.timing,
  };
}

/**
 * Create a diagram artifact.
 *
 * @param {object} opts — see diagramRenderer.renderDiagram
 * @returns {Promise<{artifactId: string, content: string, mimeType: string, format: string, timing: number}>}
 */
export async function createDiagram(opts) {
  const diagram = await renderDiagram(opts);
  const id = storeArtifact(diagram.content, diagram.mimeType, 'diagram', {
    format: diagram.format,
  });

  return {
    artifactId: id,
    content: diagram.content,
    mimeType: diagram.mimeType,
    format: diagram.format,
    isBase64: diagram.isBase64 || false,
    timing: diagram.timing,
  };
}

/**
 * Create an HTML preview artifact.
 *
 * @param {object} opts — see htmlPreview.generatePreview
 * @returns {{artifactId: string, html: string, mimeType: string}}
 */
export function createPreview(opts) {
  const preview = generatePreview(opts);
  const id = storeArtifact(preview.content, preview.mimeType, 'preview', {
    title: opts.title,
  });

  return {
    artifactId: id,
    html: preview.content,
    mimeType: preview.mimeType,
  };
}

/**
 * List all artifacts.
 */
export function listArtifacts() {
  const list = [];
  for (const [id, art] of artifacts) {
    list.push({
      id,
      type: art.type,
      mimeType: art.mimeType,
      createdAt: art.createdAt,
      metadata: art.metadata,
      size: typeof art.content === 'string' ? art.content.length : art.content?.length || 0,
    });
  }
  return { artifacts: list, total: list.length };
}

/**
 * Express middleware to serve artifacts.
 * Mount at /artifacts on the MCP server.
 */
export function artifactMiddleware(req, res, next) {
  const id = req.params?.id;
  if (!id) return next();

  const art = getArtifact(id);
  if (!art) return res.status(404).json({ error: 'Artifact not found' });

  res.setHeader('Content-Type', art.mimeType);
  if (art.mimeType === 'text/html') {
    res.send(art.content);
  } else {
    res.send(Buffer.from(art.content, 'base64'));
  }
}
