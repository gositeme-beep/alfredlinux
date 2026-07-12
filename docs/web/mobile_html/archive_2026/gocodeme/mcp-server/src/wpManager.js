/**
 * wpManager.js — WordPress management via WP-CLI for MCP Server
 *
 * Wraps WP-CLI commands so Alfred can install WordPress, manage plugins/themes,
 * create users, and optimize databases through natural language.
 *
 * WP-CLI must be installed on the system (already at /usr/local/bin/wp).
 *
 * Security:
 *   - All commands run within the customer's domain docroot
 *   - Uses --path= to target the correct WP install
 *   - No --allow-root (runs as hosting user)
 */

import { shellExecFile, shellExec } from './shellExec.js';
import path from 'path';
import fs from 'fs';

export class WpManager {
  /**
   * @param {string} homeDir — absolute path to customer home
   */
  constructor(homeDir) {
    this.homeDir = homeDir;
  }

  /**
   * Resolve WordPress path. If domain provided, use domains/<domain>/public_html.
   * Otherwise default to public_html (main domain).
   */
  _wpPath(domain) {
    if (domain) {
      const p = path.join(this.homeDir, 'domains', domain, 'public_html');
      if (!p.startsWith(this.homeDir)) throw new Error('Path escape blocked');
      return p;
    }
    return path.join(this.homeDir, 'public_html');
  }

  /**
   * Check if WordPress is installed at path.
   */
  _isWpInstalled(wpPath) {
    return fs.existsSync(path.join(wpPath, 'wp-config.php'));
  }

  /**
   * Run a WP-CLI command.
   * @param {string[]} args — WP-CLI arguments
   * @param {string} wpPath — WordPress installation path
   * @param {number} [timeout=60000]
   */
  _wp(args, wpPath, timeout = 60000) {
    return shellExecFile('wp', [...args, `--path=${wpPath}`], this.homeDir, {
      cwd: wpPath,
      timeout,
    });
  }

  /**
   * Install WordPress.
   * Downloads WP core, creates wp-config.php, and runs the install.
   * @param {object} opts
   * @param {string} opts.domain — target domain
   * @param {string} opts.siteTitle — site title
   * @param {string} opts.adminUser — admin username
   * @param {string} opts.adminPassword — admin password
   * @param {string} opts.adminEmail — admin email
   * @param {string} opts.dbName — database name (full, with prefix)
   * @param {string} opts.dbUser — database username (full, with prefix)
   * @param {string} opts.dbPassword — database password
   * @param {string} [opts.dbHost='localhost']
   * @param {string} [opts.locale='en_US']
   */
  async install({ domain, siteTitle, adminUser, adminPassword, adminEmail, dbName, dbUser, dbPassword, dbHost = 'localhost', locale = 'en_US' }) {
    const wpPath = this._wpPath(domain);
    const steps = [];

    // 1. Download WordPress core
    if (!fs.existsSync(path.join(wpPath, 'wp-includes'))) {
      const dl = this._wp(['core', 'download', `--locale=${locale}`], wpPath, 120000);
      if (dl.exitCode !== 0) return { success: false, error: `Download failed: ${dl.stderr}`, steps };
      steps.push('WordPress core downloaded');
    } else {
      steps.push('WordPress core already present');
    }

    // 2. Create wp-config.php
    if (!this._isWpInstalled(wpPath)) {
      const cfg = this._wp([
        'config', 'create',
        `--dbname=${dbName}`, `--dbuser=${dbUser}`, `--dbpass=${dbPassword}`,
        `--dbhost=${dbHost}`, '--skip-check',
      ], wpPath);
      if (cfg.exitCode !== 0) return { success: false, error: `Config failed: ${cfg.stderr}`, steps };
      steps.push('wp-config.php created');
    } else {
      steps.push('wp-config.php already exists');
    }

    // 3. Run install
    const url = `https://${domain}`;
    const inst = this._wp([
      'core', 'install',
      `--url=${url}`, `--title=${siteTitle}`,
      `--admin_user=${adminUser}`, `--admin_password=${adminPassword}`,
      `--admin_email=${adminEmail}`, '--skip-email',
    ], wpPath, 120000);

    if (inst.exitCode !== 0 && !inst.stderr.includes('already installed')) {
      return { success: false, error: `Install failed: ${inst.stderr}`, steps };
    }
    steps.push('WordPress installed');

    return {
      success: true,
      url,
      adminUrl: `${url}/wp-admin/`,
      steps,
    };
  }

  /**
   * List installed plugins with status and version.
   */
  listPlugins(domain) {
    const wpPath = this._wpPath(domain);
    if (!this._isWpInstalled(wpPath)) return { error: 'WordPress is not installed at this location.' };

    const result = this._wp(['plugin', 'list', '--format=json'], wpPath);
    if (result.exitCode !== 0) return { error: result.stderr };

    try {
      return { plugins: JSON.parse(result.stdout) };
    } catch {
      return { plugins: [], raw: result.stdout };
    }
  }

  /**
   * Install and activate a plugin.
   */
  installPlugin(pluginSlug, domain, activate = true) {
    const wpPath = this._wpPath(domain);
    if (!this._isWpInstalled(wpPath)) return { error: 'WordPress is not installed at this location.' };

    const args = ['plugin', 'install', pluginSlug];
    if (activate) args.push('--activate');

    const result = this._wp(args, wpPath, 120000);
    if (result.exitCode !== 0) return { success: false, error: result.stderr };

    return {
      success: true,
      message: `Plugin "${pluginSlug}" installed${activate ? ' and activated' : ''}.`,
      output: result.stdout.trim(),
    };
  }

  /**
   * Remove a plugin (deactivate + delete).
   */
  removePlugin(pluginSlug, domain) {
    const wpPath = this._wpPath(domain);
    if (!this._isWpInstalled(wpPath)) return { error: 'WordPress is not installed at this location.' };

    // Deactivate first (ignore errors if already inactive)
    this._wp(['plugin', 'deactivate', pluginSlug], wpPath);
    const result = this._wp(['plugin', 'delete', pluginSlug], wpPath);

    if (result.exitCode !== 0) return { success: false, error: result.stderr };
    return { success: true, message: `Plugin "${pluginSlug}" removed.` };
  }

  /**
   * List installed themes.
   */
  listThemes(domain) {
    const wpPath = this._wpPath(domain);
    if (!this._isWpInstalled(wpPath)) return { error: 'WordPress is not installed at this location.' };

    const result = this._wp(['theme', 'list', '--format=json'], wpPath);
    if (result.exitCode !== 0) return { error: result.stderr };

    try {
      return { themes: JSON.parse(result.stdout) };
    } catch {
      return { themes: [], raw: result.stdout };
    }
  }

  /**
   * Install and activate a theme.
   */
  installTheme(themeSlug, domain, activate = true) {
    const wpPath = this._wpPath(domain);
    if (!this._isWpInstalled(wpPath)) return { error: 'WordPress is not installed at this location.' };

    const args = ['theme', 'install', themeSlug];
    if (activate) args.push('--activate');

    const result = this._wp(args, wpPath, 120000);
    if (result.exitCode !== 0) return { success: false, error: result.stderr };

    return {
      success: true,
      message: `Theme "${themeSlug}" installed${activate ? ' and activated' : ''}.`,
      output: result.stdout.trim(),
    };
  }

  /**
   * Update WordPress core, all plugins, and all themes.
   */
  updateAll(domain) {
    const wpPath = this._wpPath(domain);
    if (!this._isWpInstalled(wpPath)) return { error: 'WordPress is not installed at this location.' };

    const results = {
      core: null,
      plugins: null,
      themes: null,
    };

    // Update core
    const core = this._wp(['core', 'update'], wpPath, 120000);
    results.core = core.exitCode === 0
      ? (core.stdout.includes('already at') ? 'Already up to date' : 'Updated')
      : `Error: ${core.stderr}`;

    // Update all plugins
    const plugins = this._wp(['plugin', 'update', '--all'], wpPath, 120000);
    results.plugins = plugins.exitCode === 0 ? plugins.stdout.trim() : `Error: ${plugins.stderr}`;

    // Update all themes
    const themes = this._wp(['theme', 'update', '--all'], wpPath, 120000);
    results.themes = themes.exitCode === 0 ? themes.stdout.trim() : `Error: ${themes.stderr}`;

    return results;
  }

  /**
   * Optimize the WordPress database (repair + optimize tables).
   */
  dbOptimize(domain) {
    const wpPath = this._wpPath(domain);
    if (!this._isWpInstalled(wpPath)) return { error: 'WordPress is not installed at this location.' };

    const repair = this._wp(['db', 'repair'], wpPath, 60000);
    const optimize = this._wp(['db', 'optimize'], wpPath, 60000);

    return {
      repair: repair.exitCode === 0 ? 'Success' : `Error: ${repair.stderr}`,
      optimize: optimize.exitCode === 0 ? 'Success' : `Error: ${optimize.stderr}`,
      details: optimize.stdout.trim() || repair.stdout.trim(),
    };
  }

  /**
   * Get comprehensive WordPress site info.
   */
  siteInfo(domain) {
    const wpPath = this._wpPath(domain);
    if (!this._isWpInstalled(wpPath)) return { installed: false, error: 'WordPress is not installed at this location.' };

    const info = {};

    // Core version
    const ver = this._wp(['core', 'version'], wpPath);
    info.version = ver.stdout.trim();

    // Site URL
    const url = this._wp(['option', 'get', 'siteurl'], wpPath);
    info.siteUrl = url.stdout.trim();

    // Active theme
    const theme = this._wp(['theme', 'list', '--status=active', '--format=json'], wpPath);
    try { info.activeTheme = JSON.parse(theme.stdout)[0]?.name; } catch { info.activeTheme = 'unknown'; }

    // Plugin count
    const plugins = this._wp(['plugin', 'list', '--format=json'], wpPath);
    try {
      const p = JSON.parse(plugins.stdout);
      info.plugins = { total: p.length, active: p.filter(x => x.status === 'active').length };
    } catch { info.plugins = { total: 0, active: 0 }; }

    // DB size
    const db = this._wp(['db', 'size', '--format=json'], wpPath);
    try { info.dbSize = JSON.parse(db.stdout); } catch { info.dbSize = db.stdout.trim(); }

    info.installed = true;
    return info;
  }

  /**
   * Search for plugins in the WordPress.org directory.
   */
  searchPlugins(query, domain) {
    const wpPath = this._wpPath(domain) || path.join(this.homeDir, 'public_html');

    // wp plugin search works even without a WP install
    const result = this._wp([
      'plugin', 'search', query,
      '--fields=name,slug,rating,num_ratings,active_installs',
      '--format=json',
    ], wpPath, 30000);

    if (result.exitCode !== 0) return { error: result.stderr };

    try {
      return { results: JSON.parse(result.stdout) };
    } catch {
      return { results: [], raw: result.stdout };
    }
  }

  /**
   * Search for themes in the WordPress.org directory.
   */
  searchThemes(query, domain) {
    const wpPath = this._wpPath(domain) || path.join(this.homeDir, 'public_html');

    const result = this._wp([
      'theme', 'search', query,
      '--fields=name,slug,rating,num_ratings',
      '--format=json',
    ], wpPath, 30000);

    if (result.exitCode !== 0) return { error: result.stderr };

    try {
      return { results: JSON.parse(result.stdout) };
    } catch {
      return { results: [], raw: result.stdout };
    }
  }
}
