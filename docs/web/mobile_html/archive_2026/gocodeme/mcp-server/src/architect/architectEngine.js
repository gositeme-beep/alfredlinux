/**
 * architectEngine.js — ARCHITECT: Infrastructure & DevOps Engine
 *
 * Manages infrastructure configuration, environment setup, deployment pipelines,
 * container orchestration, and system architecture analysis.
 *
 * Capabilities:
 *  - Environment variable management
 *  - Infrastructure-as-code templates
 *  - Deployment pipeline management
 *  - Container/Docker management
 *  - System architecture analysis and documentation
 *  - Resource monitoring and scaling recommendations
 */

import { randomUUID } from 'node:crypto';
import fs from 'node:fs/promises';
import path from 'node:path';
import { execFile } from 'node:child_process';
import { promisify } from 'node:util';

const execFileAsync = promisify(execFile);
const ARCHITECT_BASE = '/home/gositeme/.gocodeme/architect';

async function ensureDir(dir) { await fs.mkdir(dir, { recursive: true }); }
async function loadJSON(file, fallback = {}) {
  try { return JSON.parse(await fs.readFile(file, 'utf8')); } catch { return fallback; }
}
async function saveJSON(file, data) {
  await ensureDir(path.dirname(file));
  await fs.writeFile(file, JSON.stringify(data, null, 2));
}

// ── Environment Management ──────────────────────────────────────────────────

export async function envList(homeDir) {
  const envFiles = [];
  const scan = async (dir, depth = 0) => {
    if (depth > 3) return;
    try {
      const entries = await fs.readdir(dir, { withFileTypes: true });
      for (const e of entries) {
        if (e.name === 'node_modules' || e.name === '.git') continue;
        const full = path.join(dir, e.name);
        if (e.isFile() && (e.name === '.env' || e.name.startsWith('.env.'))) {
          const stat = await fs.stat(full);
          envFiles.push({ path: path.relative(homeDir, full), size: stat.size, modified: stat.mtime.toISOString() });
        } else if (e.isDirectory()) {
          await scan(full, depth + 1);
        }
      }
    } catch {}
  };
  await scan(homeDir);
  return { files: envFiles, count: envFiles.length, message: `Found ${envFiles.length} .env file(s).` };
}

export async function envGet(homeDir, envPath) {
  const full = path.join(homeDir, envPath);
  const content = await fs.readFile(full, 'utf8');
  const vars = {};
  for (const line of content.split('\n')) {
    const trimmed = line.trim();
    if (!trimmed || trimmed.startsWith('#')) continue;
    const eq = trimmed.indexOf('=');
    if (eq > 0) {
      const key = trimmed.slice(0, eq).trim();
      let val = trimmed.slice(eq + 1).trim();
      if ((val.startsWith('"') && val.endsWith('"')) || (val.startsWith("'") && val.endsWith("'"))) {
        val = val.slice(1, -1);
      }
      // Mask sensitive values
      const sensitive = /secret|password|key|token|api_key/i.test(key);
      vars[key] = sensitive ? val.slice(0, 3) + '***' : val;
    }
  }
  return { file: envPath, variables: vars, count: Object.keys(vars).length };
}

export async function envSet(homeDir, envPath, key, value) {
  const full = path.join(homeDir, envPath);
  let content = '';
  try { content = await fs.readFile(full, 'utf8'); } catch {}
  const lines = content.split('\n');
  let found = false;
  for (let i = 0; i < lines.length; i++) {
    if (lines[i].trim().startsWith(key + '=')) {
      lines[i] = `${key}=${value}`;
      found = true;
      break;
    }
  }
  if (!found) lines.push(`${key}=${value}`);
  await fs.writeFile(full, lines.join('\n'));
  return { message: `Set ${key} in ${envPath}` };
}

// ── Infrastructure Templates ────────────────────────────────────────────────

export async function scaffoldProject(homeDir, template, projectName) {
  const projectDir = path.join(homeDir, 'public_html', projectName);
  await ensureDir(projectDir);

  const templates = {
    'node-express': {
      'package.json': JSON.stringify({ name: projectName, version: '1.0.0', main: 'index.js', scripts: { start: 'node index.js', dev: 'node --watch index.js' }, dependencies: { express: '^4.18.2' } }, null, 2),
      'index.js': `import express from 'express';\nconst app = express();\napp.use(express.json());\napp.get('/', (req, res) => res.json({ status: 'ok' }));\napp.listen(process.env.PORT || 3000, () => console.log('Server running'));`,
      '.env': 'PORT=3000\nNODE_ENV=development',
      '.gitignore': 'node_modules/\n.env\n*.log',
    },
    'php-laravel': {
      'index.php': '<?php\nrequire __DIR__."/vendor/autoload.php";\n$app = require_once __DIR__."/bootstrap/app.php";\n$kernel = $app->make(Illuminate\\Contracts\\Http\\Kernel::class);\n$response = $kernel->handle($request = Illuminate\\Http\\Request::capture());\n$response->send();\n$kernel->terminate($request, $response);',
      '.env': 'APP_NAME=' + projectName + '\nAPP_ENV=local\nAPP_DEBUG=true',
      '.htaccess': 'RewriteEngine On\nRewriteCond %{REQUEST_FILENAME} !-d\nRewriteCond %{REQUEST_FILENAME} !-f\nRewriteRule ^ index.php [L]',
    },
    'static-site': {
      'index.html': `<!DOCTYPE html>\n<html lang="en">\n<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>${projectName}</title><link rel="stylesheet" href="style.css"></head>\n<body>\n  <h1>${projectName}</h1>\n  <script src="app.js"></script>\n</body>\n</html>`,
      'style.css': '* { margin: 0; padding: 0; box-sizing: border-box; }\nbody { font-family: system-ui, sans-serif; line-height: 1.6; padding: 2rem; }',
      'app.js': '// Application code\nconsole.log("Ready");',
    },
    'python-flask': {
      'app.py': 'from flask import Flask, jsonify\napp = Flask(__name__)\n\n@app.route("/")\ndef index():\n    return jsonify(status="ok")\n\nif __name__ == "__main__":\n    app.run(debug=True)',
      'requirements.txt': 'flask>=3.0\ngunicorn>=21.2',
      '.env': 'FLASK_ENV=development\nFLASK_DEBUG=1',
    },
    'react-app': {
      'package.json': JSON.stringify({ name: projectName, version: '1.0.0', scripts: { dev: 'vite', build: 'vite build', preview: 'vite preview' }, dependencies: { react: '^18.2.0', 'react-dom': '^18.2.0' }, devDependencies: { vite: '^5.0.0', '@vitejs/plugin-react': '^4.0.0' } }, null, 2),
      'index.html': `<!DOCTYPE html>\n<html lang="en">\n<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>${projectName}</title></head>\n<body><div id="root"></div><script type="module" src="/src/main.jsx"></script></body>\n</html>`,
      'vite.config.js': 'import { defineConfig } from "vite";\nimport react from "@vitejs/plugin-react";\nexport default defineConfig({ plugins: [react()] });',
    },
  };

  const tpl = templates[template];
  if (!tpl) {
    return { message: `Unknown template "${template}". Available: ${Object.keys(templates).join(', ')}`, templates: Object.keys(templates) };
  }

  const created = [];
  for (const [file, content] of Object.entries(tpl)) {
    const filePath = path.join(projectDir, file);
    await ensureDir(path.dirname(filePath));
    await fs.writeFile(filePath, content);
    created.push(file);
  }

  return { project: projectName, template, directory: path.relative(homeDir, projectDir), files: created, message: `Project "${projectName}" scaffolded with ${template} template (${created.length} files).` };
}

// ── Deployment Pipelines ────────────────────────────────────────────────────

function deploymentsPath(user) { return path.join(ARCHITECT_BASE, user, 'deployments.json'); }

export async function createDeployment(user, config) {
  const deps = await loadJSON(deploymentsPath(user), { deployments: {} });
  const id = `deploy_${randomUUID().slice(0, 8)}`;
  deps.deployments[id] = {
    id,
    name: config.name,
    source: config.source || 'git',
    branch: config.branch || 'main',
    target: config.target,        // directory path
    pre_deploy: config.pre_deploy || [],  // commands to run before deploy
    post_deploy: config.post_deploy || [], // commands after
    auto_deploy: config.auto_deploy || false,
    created: new Date().toISOString(),
    last_deploy: null,
    deploy_count: 0,
    status: 'ready',
  };
  await saveJSON(deploymentsPath(user), deps);
  return { id, message: `Deployment pipeline "${config.name}" created.` };
}

export async function listDeployments(user) {
  const deps = await loadJSON(deploymentsPath(user), { deployments: {} });
  return {
    deployments: Object.values(deps.deployments).map(d => ({
      id: d.id, name: d.name, source: d.source, branch: d.branch,
      status: d.status, deploy_count: d.deploy_count, last_deploy: d.last_deploy,
    })),
    message: `${Object.keys(deps.deployments).length} deployment(s).`,
  };
}

export async function runDeployment(user, deploymentId, homeDir) {
  const deps = await loadJSON(deploymentsPath(user), { deployments: {} });
  const dep = deps.deployments[deploymentId];
  if (!dep) throw new Error(`Deployment ${deploymentId} not found`);

  dep.status = 'deploying';
  await saveJSON(deploymentsPath(user), deps);

  const log = [];
  const targetDir = path.join(homeDir, dep.target || 'public_html');

  try {
    // Pre-deploy hooks
    for (const cmd of dep.pre_deploy) {
      try {
        const { stdout } = await execFileAsync('bash', ['-c', cmd], { cwd: targetDir, timeout: 60000 });
        log.push(`✅ pre: ${cmd} → ${stdout.trim().slice(0, 200)}`);
      } catch (e) { log.push(`❌ pre: ${cmd} → ${e.message}`); }
    }

    // Git pull if source is git
    if (dep.source === 'git') {
      try {
        const { stdout } = await execFileAsync('git', ['pull', 'origin', dep.branch], { cwd: targetDir, timeout: 60000 });
        log.push(`✅ git pull origin ${dep.branch}: ${stdout.trim().slice(0, 200)}`);
      } catch (e) { log.push(`⚠️ git pull: ${e.message}`); }
    }

    // Post-deploy hooks
    for (const cmd of dep.post_deploy) {
      try {
        const { stdout } = await execFileAsync('bash', ['-c', cmd], { cwd: targetDir, timeout: 60000 });
        log.push(`✅ post: ${cmd} → ${stdout.trim().slice(0, 200)}`);
      } catch (e) { log.push(`❌ post: ${cmd} → ${e.message}`); }
    }

    dep.status = 'success';
    dep.deploy_count++;
    dep.last_deploy = new Date().toISOString();
  } catch (e) {
    dep.status = 'failed';
    log.push(`❌ Deploy failed: ${e.message}`);
  }

  await saveJSON(deploymentsPath(user), deps);
  return { deployment_id: deploymentId, status: dep.status, log, message: `Deploy #${dep.deploy_count}: ${dep.status}` };
}

// ── System Architecture Analysis ────────────────────────────────────────────

export async function analyzeArchitecture(homeDir) {
  const analysis = {
    frameworks: [],
    languages: {},
    databases: [],
    services: [],
    file_count: 0,
    total_size: 0,
    structure: {},
  };

  const indicators = {
    'package.json': 'Node.js',
    'composer.json': 'PHP/Composer',
    'requirements.txt': 'Python',
    'Gemfile': 'Ruby',
    'go.mod': 'Go',
    'Cargo.toml': 'Rust',
    'pom.xml': 'Java/Maven',
    'build.gradle': 'Java/Gradle',
    'docker-compose.yml': 'Docker Compose',
    'Dockerfile': 'Docker',
    '.htaccess': 'Apache',
    'nginx.conf': 'Nginx',
    'wp-config.php': 'WordPress',
    'artisan': 'Laravel',
    'manage.py': 'Django',
  };

  const exts = {};
  const scan = async (dir, depth = 0) => {
    if (depth > 4) return;
    try {
      const entries = await fs.readdir(dir, { withFileTypes: true });
      for (const e of entries) {
        if (['node_modules', '.git', 'vendor', '__pycache__', '.cache'].includes(e.name)) continue;
        const full = path.join(dir, e.name);
        if (e.isFile()) {
          analysis.file_count++;
          const ext = path.extname(e.name).toLowerCase();
          if (ext) exts[ext] = (exts[ext] || 0) + 1;
          if (indicators[e.name]) analysis.frameworks.push(indicators[e.name]);
          try { const s = await fs.stat(full); analysis.total_size += s.size; } catch {}
        } else if (e.isDirectory()) {
          await scan(full, depth + 1);
        }
      }
    } catch {}
  };

  await scan(path.join(homeDir, 'public_html'));

  // Map extensions to languages
  const extLang = { '.js': 'JavaScript', '.ts': 'TypeScript', '.php': 'PHP', '.py': 'Python', '.rb': 'Ruby', '.java': 'Java', '.go': 'Go', '.rs': 'Rust', '.css': 'CSS', '.html': 'HTML', '.vue': 'Vue', '.jsx': 'React JSX', '.tsx': 'React TSX', '.svelte': 'Svelte' };
  for (const [ext, count] of Object.entries(exts)) {
    const lang = extLang[ext] || ext;
    analysis.languages[lang] = (analysis.languages[lang] || 0) + count;
  }

  analysis.frameworks = [...new Set(analysis.frameworks)];
  analysis.total_size_mb = (analysis.total_size / 1048576).toFixed(2);

  return analysis;
}

// ── Resource Monitoring ─────────────────────────────────────────────────────

export async function getSystemResources() {
  const results = {};
  try {
    const { stdout: disk } = await execFileAsync('df', ['-h', '/home']);
    results.disk = disk.trim();
  } catch {}
  try {
    const { stdout: mem } = await execFileAsync('free', ['-m']);
    results.memory = mem.trim();
  } catch {}
  try {
    const { stdout: load } = await execFileAsync('uptime', []);
    results.load = load.trim();
  } catch {}
  try {
    const { stdout: proc } = await execFileAsync('bash', ['-c', 'ps aux --sort=-%mem | head -11']);
    results.top_processes = proc.trim();
  } catch {}
  return results;
}
