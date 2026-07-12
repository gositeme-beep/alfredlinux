/**
 * playbookEngine.js — PLAYBOOK: Saved Workflow Templates
 *
 * Stores and runs natural-language playbooks — reusable multi-step workflows
 * that Alfred interprets and executes using his full tool chain.
 *
 * Storage: ~/.gocodeme/playbooks/{name}.json per user homeDir
 *
 * Playbook format:
 *   { name, description, parameters, steps[], permissions[], on_failure }
 *
 * Steps are natural language — Alfred interprets each step using his tools.
 * Unlike rigid n8n workflows, playbooks are self-healing: if a step fails,
 * Alfred's agentic loop kicks in and finds an alternative approach.
 */

import { readFile, writeFile, readdir, mkdir, unlink } from 'node:fs/promises';
import { existsSync } from 'node:fs';
import path from 'node:path';

/**
 * Get the playbooks directory for a user.
 * @param {string} homeDir
 * @returns {string}
 */
function playbooksDir(homeDir) {
  return path.join(homeDir, '.gocodeme', 'playbooks');
}

/**
 * Initialize the playbooks directory and install built-in playbooks if missing.
 * @param {string} homeDir
 */
async function ensureDir(homeDir) {
  const dir = playbooksDir(homeDir);
  await mkdir(dir, { recursive: true });

  // Install built-in playbooks if the directory is empty
  const files = await readdir(dir).catch(() => []);
  if (files.length === 0) {
    for (const pb of BUILTIN_PLAYBOOKS) {
      await writeFile(
        path.join(dir, `${sanitizeName(pb.name)}.json`),
        JSON.stringify(pb, null, 2)
      );
    }
  }
}

/**
 * Sanitize a playbook name for use as a filename.
 */
function sanitizeName(name) {
  return name.toLowerCase().replace(/[^a-z0-9_-]/g, '-').replace(/-+/g, '-').slice(0, 64);
}

// ═══════════════════════════════════════════════════════════════════
// BUILT-IN PLAYBOOKS
// ═══════════════════════════════════════════════════════════════════

const BUILTIN_PLAYBOOKS = [
  {
    name: 'WordPress Deploy',
    description: 'Full deployment pipeline for WordPress sites — pull code, install deps, flush cache, verify.',
    builtin: true,
    parameters: {
      domain: { type: 'string', required: true, description: 'Domain to deploy to' },
      branch: { type: 'string', default: 'main', description: 'Git branch to pull' },
    },
    steps: [
      'Navigate to the document root for {{domain}}',
      'Pull latest code from the {{branch}} branch using git pull',
      'Run composer install --no-dev --optimize-autoloader if composer.json exists',
      'Run wp cache flush using wp-cli',
      'Run wp rewrite flush using wp-cli',
      'Check that {{domain}} returns HTTP 200 using fetch_url',
      'If the health check fails, run git checkout HEAD~1 to roll back',
      'Send a deployment summary email to the account owner',
    ],
    permissions: ['run_terminal_command', 'fetch_url', 'send_email', 'read_file', 'write_file'],
    on_failure: 'Roll back the last git commit and alert the user',
  },
  {
    name: 'Laravel Deploy',
    description: 'Deploy a Laravel application — pull, install, migrate, cache, verify.',
    builtin: true,
    parameters: {
      domain: { type: 'string', required: true, description: 'Domain to deploy to' },
      branch: { type: 'string', default: 'main', description: 'Git branch to pull' },
    },
    steps: [
      'Navigate to the document root for {{domain}}',
      'Pull latest code from the {{branch}} branch',
      'Run composer install --no-dev --optimize-autoloader',
      'Run php artisan migrate --force',
      'Run php artisan config:cache',
      'Run php artisan route:cache',
      'Run php artisan view:cache',
      'Check that {{domain}} returns HTTP 200',
      'If health check fails, roll back migration and git changes',
    ],
    permissions: ['run_terminal_command', 'fetch_url', 'read_file'],
    on_failure: 'Run php artisan migrate:rollback and git checkout HEAD~1',
  },
  {
    name: 'Node.js Deploy',
    description: 'Deploy a Node.js application — pull, install, build, restart PM2, verify.',
    builtin: true,
    parameters: {
      domain: { type: 'string', required: true, description: 'Domain running the app' },
      pm2_name: { type: 'string', required: true, description: 'PM2 process name' },
      branch: { type: 'string', default: 'main', description: 'Git branch' },
    },
    steps: [
      'Navigate to the document root for {{domain}}',
      'Pull latest code from {{branch}}',
      'Run npm ci --production',
      'Run npm run build if a build script exists in package.json',
      'Restart the PM2 process named {{pm2_name}}',
      'Wait 5 seconds then check PM2 status to confirm it is online',
      'Check that {{domain}} returns HTTP 200',
    ],
    permissions: ['run_terminal_command', 'fetch_url', 'read_file'],
    on_failure: 'Roll back git changes and restart the previous PM2 process',
  },
  {
    name: 'Nightly Database Backup',
    description: 'Dump all MySQL databases, compress, and store with rotation.',
    builtin: true,
    parameters: {},
    steps: [
      'List all MySQL databases owned by this account',
      'For each database, run mysqldump and save to ~/backups/db/ with timestamp',
      'Compress each dump with gzip',
      'Delete backup files older than 30 days',
      'Count total backup size and email a summary to the account owner',
    ],
    permissions: ['run_terminal_command', 'list_directory', 'send_email', 'delete_file'],
    on_failure: 'Email the account owner that the backup failed with the error details',
  },
  {
    name: 'SSL Certificate Check',
    description: 'Check SSL certificate expiry for all domains and auto-renew if needed.',
    builtin: true,
    parameters: {},
    steps: [
      'List all domains on this hosting account',
      'For each domain, check SSL certificate expiry date',
      'If any certificate expires within 14 days, request a new Let\'s Encrypt certificate',
      'Verify the new certificate is installed correctly',
      'Email a report of all domain SSL statuses',
    ],
    permissions: ['run_terminal_command', 'list_domains', 'request_ssl_certificate', 'send_email'],
    on_failure: 'Email warning that SSL renewal failed for the affected domains',
  },
  {
    name: 'Security Audit',
    description: 'Scan the hosting account for security issues — permissions, malware, outdated software.',
    builtin: true,
    parameters: {},
    steps: [
      'Run the built-in security scan tool on the entire home directory',
      'Check file permissions — flag any world-writable files',
      'Search for known malware signatures in PHP files',
      'Check if WordPress core, plugins, and themes are up to date',
      'Generate a security report PDF and email it to the account owner',
    ],
    permissions: ['security_scan', 'run_terminal_command', 'read_file', 'create_pdf_document', 'send_email'],
    on_failure: 'Email the user that the security audit encountered errors',
  },
  {
    name: 'Performance Optimization',
    description: 'Analyze and optimize site performance — caching, compression, image optimization.',
    builtin: true,
    parameters: {
      domain: { type: 'string', required: true, description: 'Domain to optimize' },
    },
    steps: [
      'Check if browser caching is configured in .htaccess for {{domain}}',
      'If not, add proper Cache-Control and Expires headers',
      'Check if gzip compression is enabled',
      'If not, add mod_deflate rules to .htaccess',
      'Find images larger than 500KB and list them',
      'Check PHP version and recommend upgrade if below 8.2',
      'Run a performance test using fetch_url and measure response time',
      'Generate a performance report with findings and changes made',
    ],
    permissions: ['read_file', 'write_file', 'fetch_url', 'run_terminal_command', 'list_directory'],
    on_failure: 'Restore .htaccess from the last checkpoint',
  },
  {
    name: 'New Domain Setup',
    description: 'Full setup for a new domain — DNS, SSL, document root, default page.',
    builtin: true,
    parameters: {
      domain: { type: 'string', required: true, description: 'Domain name to set up' },
    },
    steps: [
      'Create the domain in DirectAdmin using the domain management tool',
      'Create the document root directory public_html/{{domain}}/',
      'Request a Let\'s Encrypt SSL certificate for {{domain}}',
      'Force HTTPS redirect for {{domain}}',
      'Create a default index.html with a "Coming Soon" page',
      'Verify {{domain}} resolves and returns HTTP 200 over HTTPS',
    ],
    permissions: ['create_domain', 'write_file', 'request_ssl_certificate', 'force_https', 'fetch_url'],
    on_failure: 'Report which steps failed so the user can fix manually',
  },
  {
    name: 'Git Repository Init',
    description: 'Initialize a Git repository in the document root with proper .gitignore.',
    builtin: true,
    parameters: {
      domain: { type: 'string', required: true, description: 'Domain whose document root to initialize' },
    },
    steps: [
      'Navigate to the document root for {{domain}}',
      'Run git init',
      'Create a .gitignore file with common patterns (node_modules, .env, vendor, etc.)',
      'Run git add -A && git commit -m "Initial commit"',
      'Report the repository status',
    ],
    permissions: ['run_terminal_command', 'write_file', 'read_file'],
    on_failure: 'Report the error — git init is non-destructive',
  },
  {
    name: 'Staging Clone',
    description: 'Clone a production domain to a staging subdomain for testing.',
    builtin: true,
    parameters: {
      source_domain: { type: 'string', required: true, description: 'Production domain to clone' },
      staging_prefix: { type: 'string', default: 'staging', description: 'Subdomain prefix (e.g., staging → staging.example.com)' },
    },
    steps: [
      'Create subdomain {{staging_prefix}}.{{source_domain}} if it doesn\'t exist',
      'Copy all files from {{source_domain}} document root to the staging subdomain document root',
      'If there\'s a MySQL database, create a copy with _staging suffix',
      'Update wp-config.php or .env in staging to use the staging database',
      'Update site URL in the staging database if WordPress',
      'Request SSL for the staging subdomain',
      'Verify the staging site loads correctly',
    ],
    permissions: ['run_terminal_command', 'read_file', 'write_file', 'create_subdomain', 'request_ssl_certificate', 'fetch_url'],
    on_failure: 'Clean up the staging subdomain and database if cloning fails halfway',
  },
];

// ═══════════════════════════════════════════════════════════════════
// PUBLIC API
// ═══════════════════════════════════════════════════════════════════

/**
 * List all available playbooks (built-in + user-created).
 * @param {string} homeDir
 * @returns {Promise<Array<{name, description, builtin, parameters}>>}
 */
export async function listPlaybooks(homeDir) {
  await ensureDir(homeDir);
  const dir = playbooksDir(homeDir);
  const files = await readdir(dir);

  const playbooks = [];
  for (const f of files) {
    if (!f.endsWith('.json')) continue;
    try {
      const raw = await readFile(path.join(dir, f), 'utf-8');
      const pb = JSON.parse(raw);
      playbooks.push({
        name: pb.name,
        description: pb.description || '',
        builtin: pb.builtin || false,
        parameters: pb.parameters || {},
        steps_count: (pb.steps || []).length,
      });
    } catch { /* skip corrupt files */ }
  }

  return playbooks;
}

/**
 * Get a full playbook by name.
 * @param {string} homeDir
 * @param {string} name — playbook name
 * @returns {Promise<object|null>}
 */
export async function getPlaybook(homeDir, name) {
  await ensureDir(homeDir);
  const dir = playbooksDir(homeDir);
  const safeName = sanitizeName(name);
  const filePath = path.join(dir, `${safeName}.json`);

  if (!existsSync(filePath)) return null;

  const raw = await readFile(filePath, 'utf-8');
  return JSON.parse(raw);
}

/**
 * Save a new or updated playbook.
 * @param {string} homeDir
 * @param {object} playbook — { name, description, steps, parameters, permissions, on_failure }
 * @returns {Promise<{saved: string, message: string}>}
 */
export async function savePlaybook(homeDir, playbook) {
  await ensureDir(homeDir);
  const dir = playbooksDir(homeDir);
  const safeName = sanitizeName(playbook.name);
  const filePath = path.join(dir, `${safeName}.json`);

  // Don't overwrite built-in playbooks
  if (existsSync(filePath)) {
    const existing = JSON.parse(await readFile(filePath, 'utf-8'));
    if (existing.builtin) {
      throw new Error(`Cannot overwrite built-in playbook "${playbook.name}". Create a new one with a different name.`);
    }
  }

  const pb = {
    name: playbook.name,
    description: playbook.description || '',
    builtin: false,
    parameters: playbook.parameters || {},
    steps: playbook.steps || [],
    permissions: playbook.permissions || [],
    on_failure: playbook.on_failure || 'Alert the user about the failure',
    created: new Date().toISOString(),
  };

  await writeFile(filePath, JSON.stringify(pb, null, 2));
  return { saved: safeName, message: `Playbook "${playbook.name}" saved with ${pb.steps.length} steps.` };
}

/**
 * Delete a user-created playbook.
 * @param {string} homeDir
 * @param {string} name
 * @returns {Promise<{deleted: boolean, message: string}>}
 */
export async function deletePlaybook(homeDir, name) {
  await ensureDir(homeDir);
  const dir = playbooksDir(homeDir);
  const safeName = sanitizeName(name);
  const filePath = path.join(dir, `${safeName}.json`);

  if (!existsSync(filePath)) {
    return { deleted: false, message: `Playbook "${name}" not found.` };
  }

  const existing = JSON.parse(await readFile(filePath, 'utf-8'));
  if (existing.builtin) {
    return { deleted: false, message: `Cannot delete built-in playbook "${name}".` };
  }

  await unlink(filePath);
  return { deleted: true, message: `Playbook "${name}" deleted.` };
}

/**
 * Render a playbook's steps with parameter substitution.
 * Returns the full playbook with interpolated steps, ready for Alfred to execute.
 *
 * @param {string} homeDir
 * @param {string} name — playbook name
 * @param {object} params — parameter values (e.g. { domain: "example.com" })
 * @returns {Promise<object>} — playbook with rendered steps
 */
export async function renderPlaybook(homeDir, name, params = {}) {
  const pb = await getPlaybook(homeDir, name);
  if (!pb) throw new Error(`Playbook "${name}" not found.`);

  // Validate required parameters
  for (const [key, spec] of Object.entries(pb.parameters || {})) {
    if (spec.required && !params[key]) {
      throw new Error(`Missing required parameter: ${key} (${spec.description || ''})`);
    }
  }

  // Apply defaults
  const fullParams = { ...params };
  for (const [key, spec] of Object.entries(pb.parameters || {})) {
    if (fullParams[key] === undefined && spec.default !== undefined) {
      fullParams[key] = spec.default;
    }
  }

  // Interpolate {{param}} in steps
  const renderedSteps = pb.steps.map(step => {
    let rendered = step;
    for (const [key, val] of Object.entries(fullParams)) {
      rendered = rendered.replace(new RegExp(`\\{\\{${key}\\}\\}`, 'g'), val);
    }
    return rendered;
  });

  // Interpolate on_failure too
  let onFailure = pb.on_failure || '';
  for (const [key, val] of Object.entries(fullParams)) {
    onFailure = onFailure.replace(new RegExp(`\\{\\{${key}\\}\\}`, 'g'), val);
  }

  return {
    name: pb.name,
    description: pb.description,
    parameters_used: fullParams,
    steps: renderedSteps,
    permissions: pb.permissions || [],
    on_failure: onFailure,
    total_steps: renderedSteps.length,
  };
}
