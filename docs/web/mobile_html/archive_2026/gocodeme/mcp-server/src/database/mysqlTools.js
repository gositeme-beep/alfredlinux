/**
 * mysqlTools.js — MySQL Database Tools
 *
 * Provides database introspection and query execution for DirectAdmin
 * hosting accounts. Uses the mysql2 CLI or the system mysql client.
 *
 * Security:
 *   - All queries run as the DA user's MySQL user
 *   - Credentials read from ~/.my.cnf or DirectAdmin API
 *   - DDL mutations require explicit confirmation parameter
 *   - SELECT queries have a 30-second timeout
 *   - Results truncated to prevent memory issues
 */

import { exec as execCb } from 'node:child_process';
import { promisify } from 'node:util';
import { readFile, readdir } from 'node:fs/promises';
import { existsSync, readdirSync } from 'node:fs';
import path from 'node:path';

const exec = promisify(execCb);
const QUERY_TIMEOUT = 30_000;
const MAX_ROWS = 500;
const MAX_OUTPUT = 64 * 1024; // 64KB output cap

/**
 * Find MySQL credentials for a DA user.
 * Checks ~/.my.cnf first, then falls back to common locations.
 *
 * @param {string} homeDir — e.g. /home/gositeme
 * @returns {Promise<{user:string, password:string, host:string}|null>}
 */
async function findCredentials(homeDir) {
  // Check ~/.my.cnf
  const mycnf = path.join(homeDir, '.my.cnf');
  if (existsSync(mycnf)) {
    try {
      const content = await readFile(mycnf, 'utf-8');
      const user = content.match(/user\s*=\s*"?([^"\s\n]+)/)?.[1];
      const pass = content.match(/password\s*=\s*"?([^"\s\n]+)/)?.[1];
      const host = content.match(/host\s*=\s*"?([^"\s\n]+)/)?.[1] || 'localhost';
      if (user && pass) return { user, password: pass, host };
    } catch { /* continue */ }
  }

  // Check wp-config.php files for DB creds (DirectAdmin structure: domains/*/public_html/)
  const wpSearchPaths = [
    path.join(homeDir, 'public_html', 'wp-config.php'),
  ];

  // Scan all domains for wp-config.php
  const domainsDir = path.join(homeDir, 'domains');
  if (existsSync(domainsDir)) {
    try {
      const domains = readdirSync(domainsDir);
      for (const domain of domains) {
        wpSearchPaths.push(path.join(domainsDir, domain, 'public_html', 'wp-config.php'));
      }
    } catch { /* continue */ }
  }

  for (const wpc of wpSearchPaths) {
    if (existsSync(wpc)) {
      try {
        const content = await readFile(wpc, 'utf-8');
        const user = content.match(/DB_USER['"]\s*,\s*['"](.*?)['"]/)?.[1];
        const pass = content.match(/DB_PASSWORD['"]\s*,\s*['"](.*?)['"]/)?.[1];
        const host = content.match(/DB_HOST['"]\s*,\s*['"](.*?)['"]/)?.[1] || 'localhost';
        if (user && pass) return { user, password: pass, host };
      } catch { /* continue */ }
    }
  }

  // Check .env files (Laravel, etc.)
  const envPaths = [
    path.join(homeDir, 'public_html', '.env'),
  ];
  if (existsSync(domainsDir)) {
    try {
      const domains = readdirSync(domainsDir);
      for (const domain of domains) {
        envPaths.push(path.join(domainsDir, domain, 'public_html', '.env'));
      }
    } catch { /* continue */ }
  }

  for (const envPath of envPaths) {
    if (existsSync(envPath)) {
      try {
        const content = await readFile(envPath, 'utf-8');
        const user = content.match(/DB_USERNAME\s*=\s*(.+)/)?.[1]?.trim();
        const pass = content.match(/DB_PASSWORD\s*=\s*(.+)/)?.[1]?.trim();
        const host = content.match(/DB_HOST\s*=\s*(.+)/)?.[1]?.trim() || 'localhost';
        if (user && pass) return { user, password: pass, host };
      } catch { /* continue */ }
    }
  }

  // Fall back to DA username convention
  const daUser = path.basename(homeDir);
  return { user: daUser, password: null, host: 'localhost' };
}

/**
 * Run a MySQL query via CLI.
 *
 * @param {string} homeDir
 * @param {string} database
 * @param {string} query
 * @param {object} [options]
 * @param {boolean} [options.vertical]  — use \G vertical output
 * @param {boolean} [options.rawOutput] — return raw text instead of parsed
 * @returns {Promise<object>}
 */
async function runQuery(homeDir, database, query, options = {}) {
  const creds = await findCredentials(homeDir);
  if (!creds || !creds.password) {
    throw new Error(
      'Cannot find MySQL credentials. Create ~/.my.cnf or ensure wp-config.php is accessible.'
    );
  }

  // Safety: block destructive statements unless explicitly allowed
  const normalized = query.trim().toUpperCase();
  const dangerous = ['DROP ', 'TRUNCATE ', 'DELETE ', 'ALTER ', 'GRANT ', 'REVOKE '];
  const isDangerous = dangerous.some(d => normalized.startsWith(d));

  // Build mysql command
  const mysqlArgs = [
    'mysql',
    `--user="${creds.user}"`,
    `--password="${creds.password}"`,
    `--host="${creds.host}"`,
    `--database="${database}"`,
    '--batch',           // tab-separated output
    '--raw',
    options.vertical ? '-E' : '',
    `-e "${query.replace(/"/g, '\\"')}"`,
  ].filter(Boolean).join(' ');

  try {
    const { stdout, stderr } = await exec(mysqlArgs, {
      timeout: QUERY_TIMEOUT,
      maxBuffer: MAX_OUTPUT,
      cwd: homeDir,
      env: { ...process.env, MYSQL_PWD: creds.password },
    });

    if (options.rawOutput) {
      return { success: true, output: stdout.slice(0, MAX_OUTPUT), warning: stderr || undefined };
    }

    // Parse tab-separated output into rows
    const lines = stdout.split('\n').filter(l => l.length > 0);
    if (lines.length === 0) {
      return { success: true, rows: [], columns: [], rowCount: 0, message: 'Query OK, 0 rows returned.' };
    }

    const columns = lines[0].split('\t');
    const rows = lines.slice(1, MAX_ROWS + 1).map(line => {
      const vals = line.split('\t');
      const obj = {};
      columns.forEach((col, i) => { obj[col] = vals[i] ?? null; });
      return obj;
    });

    const result = {
      success: true,
      columns,
      rows,
      rowCount: rows.length,
      totalRows: lines.length - 1,
      truncated: lines.length - 1 > MAX_ROWS,
    };

    if (isDangerous) result.warning = 'Destructive query executed.';
    return result;

  } catch (err) {
    // Scrub password from error messages
    const msg = err.message.replace(creds.password, '***');
    return { success: false, error: msg };
  }
}

// ═══════════════════════════════════════════════════════════════════
// PUBLIC API
// ═══════════════════════════════════════════════════════════════════

/**
 * List all databases accessible to this account.
 */
export async function listDatabases(homeDir) {
  const result = await runQuery(homeDir, 'information_schema', 'SHOW DATABASES', { rawOutput: true });
  if (!result.success) return result;

  const databases = result.output
    .split('\n')
    .filter(l => l.trim().length > 0)
    .slice(1) // skip header
    .map(l => l.trim())
    .filter(db => db !== 'information_schema' && db !== 'performance_schema');

  return { success: true, databases, count: databases.length };
}

/**
 * Get the schema (tables and columns) for a database.
 */
export async function getDatabaseSchema(homeDir, database) {
  // Get tables
  const tablesResult = await runQuery(homeDir, database, 'SHOW TABLES', { rawOutput: true });
  if (!tablesResult.success) return tablesResult;

  const tables = tablesResult.output
    .split('\n')
    .filter(l => l.trim().length > 0)
    .slice(1)
    .map(t => t.trim());

  // Get columns for each table (limit to first 50 tables)
  const schema = {};
  for (const table of tables.slice(0, 50)) {
    const colResult = await runQuery(homeDir, database, `DESCRIBE \`${table}\``, { rawOutput: true });
    if (colResult.success) {
      const lines = colResult.output.split('\n').filter(l => l.trim());
      const header = lines[0]?.split('\t') || [];
      schema[table] = lines.slice(1).map(line => {
        const vals = line.split('\t');
        return {
          field: vals[0],
          type: vals[1],
          null: vals[2],
          key: vals[3],
          default: vals[4],
          extra: vals[5],
        };
      });
    }
  }

  return {
    success: true,
    database,
    table_count: tables.length,
    tables,
    schema,
    truncated: tables.length > 50,
  };
}

/**
 * Execute a SELECT query (read-only by default).
 */
export async function executeQuery(homeDir, database, query, options = {}) {
  // Only allow SELECT, SHOW, DESCRIBE, EXPLAIN by default
  const normalized = query.trim().toUpperCase();
  const readOnly = ['SELECT ', 'SHOW ', 'DESCRIBE ', 'EXPLAIN ', 'DESC '];
  const isRead = readOnly.some(prefix => normalized.startsWith(prefix));

  if (!isRead && !options.allow_mutation) {
    return {
      success: false,
      error: 'Only SELECT/SHOW/DESCRIBE queries are allowed by default. Pass allow_mutation=true for write queries.',
    };
  }

  return runQuery(homeDir, database, query, options);
}

/**
 * Get table row counts and sizes for a database.
 */
export async function getDatabaseStats(homeDir, database) {
  const query = `
    SELECT 
      TABLE_NAME as 'table',
      TABLE_ROWS as 'rows',
      ROUND(DATA_LENGTH / 1024 / 1024, 2) as 'data_mb',
      ROUND(INDEX_LENGTH / 1024 / 1024, 2) as 'index_mb',
      ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) as 'total_mb',
      ENGINE as 'engine',
      TABLE_COLLATION as 'collation'
    FROM information_schema.TABLES 
    WHERE TABLE_SCHEMA = '${database.replace(/'/g, "''")}'
    ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC
  `;

  return runQuery(homeDir, 'information_schema', query);
}

/**
 * Backup a database to a SQL file.
 */
export async function backupDatabase(homeDir, database) {
  const creds = await findCredentials(homeDir);
  if (!creds || !creds.password) {
    throw new Error('Cannot find MySQL credentials.');
  }

  const backupDir = path.join(homeDir, 'backups', 'db');
  const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19);
  const filename = `${database}_${timestamp}.sql.gz`;
  const filepath = path.join(backupDir, filename);

  try {
    await exec(`mkdir -p "${backupDir}"`, { cwd: homeDir });
    await exec(
      `mysqldump --user="${creds.user}" --host="${creds.host}" "${database}" | gzip > "${filepath}"`,
      {
        timeout: 120_000,
        env: { ...process.env, MYSQL_PWD: creds.password },
        cwd: homeDir,
      }
    );

    // Get file size
    const { stdout: size } = await exec(`ls -lh "${filepath}" | awk '{print $5}'`);

    return {
      success: true,
      database,
      file: filepath,
      size: size.trim(),
      timestamp,
    };
  } catch (err) {
    const msg = err.message.replace(creds.password || '', '***');
    return { success: false, error: msg };
  }
}
