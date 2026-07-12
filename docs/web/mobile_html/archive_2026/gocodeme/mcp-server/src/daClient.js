/**
 * DirectAdmin Client for MCP Server
 *
 * Uses DA's classic CMD_API_FILE_MANAGER endpoints — proven working in Week 1-2.
 * The /api/file-manager JSON API returned 405/HTML errors, so we use the
 * classic URL-encoded CMD API instead (same as middleware/src/directadmin/fileManager.js).
 *
 * Auth: "admin|targetUsername" impersonation over HTTPS Basic Auth.
 * All file paths are validated to stay within /home/<username>/.
 */

import axios from 'axios';
import https from 'https';
import path from 'path';
import fs from 'fs/promises';
import { existsSync, statSync } from 'fs';
import FormData from 'form-data';

const httpsAgent = new https.Agent({ rejectUnauthorized: false });

export class DirectAdminClient {
  constructor({ host, adminUser, adminPass, targetUsername }) {
    this.host           = host;
    this.targetUsername = targetUsername;
    this.homeDir        = `/home/${targetUsername}`;
    this.useDirectFs    = !adminPass;

    // Impersonation: "admin|username" as Basic auth username
    this.http = axios.create({
      baseURL: host,
      httpsAgent,
      auth: {
        username: `${adminUser}|${targetUsername}`,
        password: adminPass || '',
      },
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        Accept: 'text/plain, */*',
      },
      timeout: 30000,
    });
  }

  // ── Path safety ──────────────────────────────────────────────────────────
  safePath(requestedPath) {
    let p = requestedPath;
    // Handle ~ as home directory
    if (p === '~' || p === '~/') return this.homeDir;
    if (p.startsWith('~/')) p = p.slice(2);
    // Handle absolute paths that are already inside home
    else if (p.startsWith(this.homeDir + '/')) return path.posix.normalize(p);
    else if (p === this.homeDir) return this.homeDir;
    // Strip leading slashes for relative paths
    else p = p.replace(/^\/+/, '');

    const resolved = path.posix.normalize(path.posix.join(this.homeDir, p));
    if (!resolved.startsWith(this.homeDir + '/') && resolved !== this.homeDir) {
      throw new Error(`Path traversal blocked: "${requestedPath}"`);
    }
    return resolved;
  }

  /** Convert absolute path back to DA-relative (relative to home dir). */
  daRelPath(absPath) {
    return absPath.replace(this.homeDir + '/', '').replace(/^\/+/, '');
  }

  // ── Parse DA URL-encoded listing ─────────────────────────────────────────
  parseListing(rawData) {
    if (!rawData || typeof rawData !== 'string') return [];
    const entries = [];
    for (const pair of rawData.split('&')) {
      const eqIdx = pair.indexOf('=');
      if (eqIdx === -1) continue;
      const entryPath = decodeURIComponent(pair.slice(0, eqIdx));
      const props = {};
      for (const attr of decodeURIComponent(pair.slice(eqIdx + 1)).split('&')) {
        const [k, ...rest] = attr.split('=');
        if (k) props[k] = rest.length ? decodeURIComponent(rest.join('=')) : '';
      }
      entries.push({ path: entryPath, ...props });
    }
    return entries;
  }

  // ── List directory ───────────────────────────────────────────────────────
  async listDirectory(dirPath = 'public_html') {
    if (this.useDirectFs) return this._fsListDirectory(dirPath);
    const relPath = this.daRelPath(this.safePath(dirPath));
    const res = await this.http.get('/CMD_API_FILE_MANAGER', {
      params: { path: relPath },
      headers: { Accept: 'text/plain, */*' },
    });
    return this.parseListing(res.data);
  }

  async _fsListDirectory(dirPath) {
    const absPath = this.safePath(dirPath);
    const entries = await fs.readdir(absPath, { withFileTypes: true });
    return entries.map(e => ({
      path: '/' + this.daRelPath(path.posix.join(absPath, e.name)),
      type: e.isDirectory() ? 'dir' : 'file',
      name: e.name,
    }));
  }

  // ── Read file ────────────────────────────────────────────────────────────
  async readFile(filePath) {
    if (this.useDirectFs) return this._fsReadFile(filePath);
    const relPath = this.daRelPath(this.safePath(filePath));
    const res = await this.http.get('/CMD_API_FILE_MANAGER', {
      params: { action: 'edit', path: relPath },
      responseType: 'text',
      headers: { Accept: 'text/plain, */*' },
    });
    const raw = typeof res.data === 'string' ? res.data : String(res.data);
    if (raw.includes('error=1')) throw new Error(`DA file read failed: ${raw}`);
    const params = new URLSearchParams(raw);
    return params.has('TEXT') ? params.get('TEXT') : raw;
  }

  async _fsReadFile(filePath) {
    const absPath = this.safePath(filePath);
    return await fs.readFile(absPath, 'utf-8');
  }

  // ── Write file ───────────────────────────────────────────────────────────
  async writeFile(filePath, content) {
    if (this.useDirectFs) return this._fsWriteFile(filePath, content);
    const absFull  = this.safePath(filePath);
    const relDir   = this.daRelPath(path.posix.dirname(absFull));
    const basePart = path.posix.basename(absFull);

    const form = new FormData();
    form.append('action', 'upload');
    form.append('path', '/' + relDir.replace(/^\/+/, ''));
    form.append('file1', Buffer.isBuffer(content) ? content : Buffer.from(content, 'utf-8'), {
      filename: basePart,
      contentType: 'application/octet-stream',
    });

    const res = await this.http.post('/CMD_API_FILE_MANAGER', form, {
      headers: form.getHeaders(),
    });
    const body = typeof res.data === 'string' ? res.data : JSON.stringify(res.data);
    if (body.includes('error=1')) throw new Error(`DA file write failed: ${body}`);
  }

  async _fsWriteFile(filePath, content) {
    const absPath = this.safePath(filePath);
    const dir = path.posix.dirname(absPath);
    await fs.mkdir(dir, { recursive: true });
    await fs.writeFile(absPath, content, 'utf-8');
  }

  // ── Delete file ──────────────────────────────────────────────────────────
  async deleteFile(targetPath) {
    if (this.useDirectFs) return this._fsDeleteFile(targetPath);
    const absFull  = this.safePath(targetPath);
    const relDir   = '/' + this.daRelPath(path.posix.dirname(absFull)).replace(/^\/+/, '');
    const basePart = path.posix.basename(absFull);

    const params = new URLSearchParams();
    params.append('action', 'multiple');
    params.append('button', 'delete');
    params.append('path', relDir);
    params.append('select0', basePart);

    const res = await this.http.post('/CMD_API_FILE_MANAGER', params.toString());
    const body = typeof res.data === 'string' ? res.data : JSON.stringify(res.data);
    if (body.includes('error=1')) throw new Error(`DA delete failed: ${body}`);
  }

  async _fsDeleteFile(targetPath) {
    const absPath = this.safePath(targetPath);
    await fs.rm(absPath, { recursive: true, force: true });
  }

  // ── Rename / move file ───────────────────────────────────────────────────
  async renameFile(oldPath, newPath) {
    if (this.useDirectFs) return this._fsRenameFile(oldPath, newPath);
    const fileContent = await this.readFile(oldPath);
    await this.writeFile(newPath, fileContent);
    await this.deleteFile(oldPath);
  }

  async _fsRenameFile(oldPath, newPath) {
    const absOld = this.safePath(oldPath);
    const absNew = this.safePath(newPath);
    await fs.mkdir(path.posix.dirname(absNew), { recursive: true });
    await fs.rename(absOld, absNew);
  }

  // ── Create directory ─────────────────────────────────────────────────────
  async createDirectory(dirPath) {
    if (this.useDirectFs) {
      const absPath = this.safePath(dirPath);
      await fs.mkdir(absPath, { recursive: true });
      return;
    }
    const absFull = this.safePath(dirPath);
    const relFull = this.daRelPath(absFull);

    const params = new URLSearchParams();
    params.append('action', 'folder');
    params.append('path', '/' + path.posix.dirname(relFull).replace(/^\/+/, ''));
    params.append('name', path.posix.basename(relFull));

    const res = await this.http.post('/CMD_API_FILE_MANAGER', params.toString());
    const body = typeof res.data === 'string' ? res.data : JSON.stringify(res.data);
    if (body.includes('error=1')) throw new Error(`DA mkdir failed: ${body}`);
  }

  // ── Stat file ────────────────────────────────────────────────────────────
  async statFile(targetPath) {
    if (this.useDirectFs) return this._fsStatFile(targetPath);
    const absFull  = this.safePath(targetPath);
    const relDir   = this.daRelPath(path.posix.dirname(absFull));
    const basePart = path.posix.basename(absFull);

    const res = await this.http.get('/CMD_API_FILE_MANAGER', {
      params: { path: relDir },
      headers: { Accept: 'text/plain, */*' },
    });
    const entries = this.parseListing(res.data);
    const entry = entries.find((e) => path.posix.basename(e.path) === basePart);
    if (!entry) throw new Error(`File not found: ${targetPath}`);
    return entry;
  }

  async _fsStatFile(targetPath) {
    const absPath = this.safePath(targetPath);
    const s = await fs.stat(absPath);
    return {
      path: '/' + this.daRelPath(absPath),
      type: s.isDirectory() ? 'dir' : 'file',
      size: String(s.size),
      uid: String(s.uid),
      gid: String(s.gid),
      mtime: s.mtime.toISOString(),
      permissions: '0' + (s.mode & 0o7777).toString(8),
    };
  }

  // ── Search files ─────────────────────────────────────────────────────────
  async searchFiles(pattern, directory = 'public_html', caseSensitive = false, maxDepth = 4) {
    const flags = caseSensitive ? '' : 'i';
    let regex;
    try {
      regex = new RegExp(pattern, flags);
    } catch {
      regex = new RegExp(pattern.replace(/[.*+?^${}()|[\]\\]/g, '\$&'), flags);
    }

    const results = [];

    if (this.useDirectFs) {
      const walk = async (dir, depth) => {
        if (depth > maxDepth || results.length >= 50) return;
        const absDir = this.safePath(dir);
        let entries;
        try { entries = await fs.readdir(absDir, { withFileTypes: true }); } catch { return; }
        for (const entry of entries) {
          if (results.length >= 50) return;
          const relPath = path.posix.join(dir, entry.name);
          if (regex.test(entry.name)) results.push(relPath);
          if (entry.isDirectory()) await walk(relPath, depth + 1);
        }
      };
      await walk(directory, 0);
      return [...new Set(results)];
    }

    const walk = async (dir, depth) => {
      if (depth > maxDepth) return;
      let entries;
      try {
        entries = await this.listDirectory(dir);
      } catch {
        return;
      }
      for (const entry of entries) {
        const entryPath = entry.path || '';
        const name = entryPath.split('/').filter(Boolean).pop() || '';
        if (!name) continue;
        const relPath = entryPath.replace(/^\//, '');
        if (regex.test(name)) results.push(relPath);
        const isDir = (entry.type === 'dir' || entry.type === 'directory');
        if (isDir) {
          await walk(relPath, depth + 1);
        }
        if (results.length >= 50) return;
      }
    };

    await walk(directory, 0);
    return [...new Set(results)];
  }

  // ══════════════════════════════════════════════════════════════════════════
  // DATABASE MANAGEMENT
  // ══════════════════════════════════════════════════════════════════════════

  async listDatabases() {
    const res = await this.http.get('/CMD_API_DATABASES');
    const data = res.data;
    if (Array.isArray(data)) return data;
    if (typeof data === 'object' && data.list) return [].concat(data.list);
    if (typeof data === 'string') {
      const dbs = [];
      for (const [key, val] of new URLSearchParams(data)) {
        if (key.startsWith('list')) dbs.push(val);
      }
      return dbs;
    }
    return [];
  }

  async createDatabase(dbName, dbUser, dbPassword) {
    const params = new URLSearchParams({
      action: 'create', name: dbName, user: dbUser,
      passwd: dbPassword, passwd2: dbPassword,
    });
    const res = await this.http.post('/CMD_API_DATABASES', params.toString());
    const body = typeof res.data === 'string' ? res.data : JSON.stringify(res.data);
    if (body.includes('error=1') || body.includes('already exists'))
      throw new Error(`Database creation failed: ${body.substring(0, 300)}`);
    return {
      database: `${this.targetUsername}_${dbName}`,
      user: `${this.targetUsername}_${dbUser}`,
      host: 'localhost', password: dbPassword,
    };
  }

  async deleteDatabase(dbName) {
    const params = new URLSearchParams({ action: 'delete', select0: dbName });
    const res = await this.http.post('/CMD_API_DATABASES', params.toString());
    const body = typeof res.data === 'string' ? res.data : JSON.stringify(res.data);
    if (body.includes('error=1')) throw new Error(`Database delete failed: ${body.substring(0, 300)}`);
  }

  async getDatabaseInfo(dbName) {
    const res = await this.http.get('/CMD_API_DATABASES', { params: { db: dbName } });
    return res.data;
  }

  // ══════════════════════════════════════════════════════════════════════════
  // DOMAIN MANAGEMENT
  // ══════════════════════════════════════════════════════════════════════════

  async listDomains() {
    if (this.useDirectFs) {
      const domainsDir = path.join(this.homeDir, 'domains');
      try {
        const entries = await fs.readdir(domainsDir, { withFileTypes: true });
        return entries
          .filter(e => e.isDirectory() && e.name.includes('.'))
          .map(e => e.name)
          .sort();
      } catch { return []; }
    }
    const res = await this.http.get('/CMD_API_SHOW_DOMAINS');
    const data = res.data;
    if (Array.isArray(data)) return data;
    if (typeof data === 'object' && data.list) return [].concat(data.list);
    if (typeof data === 'string') {
      const params = new URLSearchParams(data);
      const listVals = params.getAll('list[]');
      if (listVals.length) return listVals;
      const domains = [];
      for (const [key] of params) {
        if (key && !key.startsWith('error') && !key.startsWith('list')) domains.push(key);
      }
      return domains;
    }
    return [];
  }

  async listSubdomains(domain) {
    if (this.useDirectFs) {
      const domainsDir = path.join(this.homeDir, 'domains');
      try {
        const entries = await fs.readdir(domainsDir, { withFileTypes: true });
        return entries
          .filter(e => e.isDirectory() && e.name.endsWith('.' + domain))
          .map(e => e.name)
          .sort();
      } catch { return []; }
    }
    const res = await this.http.get('/CMD_API_SUBDOMAINS', { params: { domain } });
    const data = res.data;
    if (Array.isArray(data)) return data;
    if (typeof data === 'object' && data.list) return [].concat(data.list);
    if (typeof data === 'string') {
      const subs = [];
      for (const [, val] of new URLSearchParams(data)) { if (val) subs.push(val); }
      return subs;
    }
    return [];
  }

  async createSubdomain(domain, subdomain) {
    const params = new URLSearchParams({ action: 'create', domain, subdomain });
    const res = await this.http.post('/CMD_API_SUBDOMAINS', params.toString());
    const body = typeof res.data === 'string' ? res.data : JSON.stringify(res.data);
    if (body.includes('error=1')) throw new Error(`Subdomain creation failed: ${body.substring(0, 300)}`);
    return `${subdomain}.${domain}`;
  }

  async deleteSubdomain(domain, subdomain) {
    const params = new URLSearchParams({ action: 'delete', domain, select0: subdomain });
    const res = await this.http.post('/CMD_API_SUBDOMAINS', params.toString());
    const body = typeof res.data === 'string' ? res.data : JSON.stringify(res.data);
    if (body.includes('error=1')) throw new Error(`Subdomain delete failed: ${body.substring(0, 300)}`);
  }

  // ══════════════════════════════════════════════════════════════════════════
  // EMAIL MANAGEMENT
  // ══════════════════════════════════════════════════════════════════════════

  async listEmailAccounts(domain) {
    const res = await this.http.get('/CMD_API_POP', { params: { domain, action: 'list' } });
    const data = res.data;
    if (Array.isArray(data)) return data;
    if (typeof data === 'string') {
      const accounts = [];
      for (const [key, val] of new URLSearchParams(data)) {
        if (key.startsWith('list')) accounts.push(val);
        else if (val && !key.startsWith('error')) accounts.push(key);
      }
      return accounts.filter(Boolean);
    }
    return [];
  }

  async createEmailAccount(domain, emailUser, password, quota = 200) {
    const params = new URLSearchParams({
      action: 'create', domain, user: emailUser,
      passwd: password, passwd2: password, quota: String(quota),
    });
    const res = await this.http.post('/CMD_API_POP', params.toString());
    const body = typeof res.data === 'string' ? res.data : JSON.stringify(res.data);
    if (body.includes('error=1')) throw new Error(`Email creation failed: ${body.substring(0, 300)}`);
    return {
      email: `${emailUser}@${domain}`, server: domain,
      ports: { imap: 993, smtp: 587, pop3: 995, tls: true },
    };
  }

  async deleteEmailAccount(domain, emailUser) {
    const params = new URLSearchParams({ action: 'delete', domain, select0: emailUser });
    const res = await this.http.post('/CMD_API_POP', params.toString());
    const body = typeof res.data === 'string' ? res.data : JSON.stringify(res.data);
    if (body.includes('error=1')) throw new Error(`Email delete failed: ${body.substring(0, 300)}`);
  }

  async createForwarder(domain, emailUser, forwardTo) {
    const params = new URLSearchParams({ action: 'create', domain, user: emailUser, email: forwardTo });
    const res = await this.http.post('/CMD_API_EMAIL_FORWARDERS', params.toString());
    const body = typeof res.data === 'string' ? res.data : JSON.stringify(res.data);
    if (body.includes('error=1')) throw new Error(`Forwarder creation failed: ${body.substring(0, 300)}`);
  }

  async createAutoResponder(domain, emailUser, subject, message) {
    const params = new URLSearchParams({
      action: 'create', domain, user: emailUser,
      text: `Subject: ${subject}\n\n${message}`,
    });
    const res = await this.http.post('/CMD_API_EMAIL_AUTORESPONDER', params.toString());
    const body = typeof res.data === 'string' ? res.data : JSON.stringify(res.data);
    if (body.includes('error=1')) throw new Error(`Autoresponder creation failed: ${body.substring(0, 300)}`);
  }

  // ══════════════════════════════════════════════════════════════════════════
  // DNS MANAGEMENT
  // ══════════════════════════════════════════════════════════════════════════

  async listDnsRecords(domain) {
    const res = await this.http.get('/CMD_API_DNS_CONTROL', { params: { domain } });
    const data = res.data;
    if (typeof data === 'string') {
      const records = [];
      for (const [recordType, encodedVal] of new URLSearchParams(data)) {
        if (['error', 'text', 'details'].includes(recordType)) continue;
        const attrs = new URLSearchParams(decodeURIComponent(encodedVal));
        records.push({
          type: recordType.replace(/\d+$/, ''),
          name: attrs.get('name') || '', value: attrs.get('value') || '',
          ttl: attrs.get('ttl') || '14400',
        });
      }
      return records;
    }
    return Array.isArray(data) ? data : Object.values(data || {});
  }

  async addDnsRecord(domain, type, name, value, ttl = 14400) {
    const params = new URLSearchParams({
      action: 'add', domain, type: type.toUpperCase(), name, value, ttl: String(ttl),
    });
    const res = await this.http.post('/CMD_API_DNS_CONTROL', params.toString());
    const body = typeof res.data === 'string' ? res.data : JSON.stringify(res.data);
    if (body.includes('error=1')) throw new Error(`DNS add failed: ${body.substring(0, 300)}`);
  }

  async deleteDnsRecord(domain, type, name, value) {
    const params = new URLSearchParams({
      action: 'select', domain,
      [`${type.toLowerCase()}recs0`]: `name=${encodeURIComponent(name)}&value=${encodeURIComponent(value)}`,
    });
    const res = await this.http.post('/CMD_API_DNS_CONTROL', params.toString());
    const body = typeof res.data === 'string' ? res.data : JSON.stringify(res.data);
    if (body.includes('error=1')) throw new Error(`DNS delete failed: ${body.substring(0, 300)}`);
  }

  // ══════════════════════════════════════════════════════════════════════════
  // SSL MANAGEMENT
  // ══════════════════════════════════════════════════════════════════════════

  async requestLetsEncrypt(domain, wildcard = false) {
    const formParams = {
      domain, action: 'save', type: 'create', request: 'letsencrypt',
      le_select0: domain, le_select1: `www.${domain}`, le_select2: `mail.${domain}`,
    };
    if (wildcard) formParams.le_wc_select0 = `*.${domain}`;
    const params = new URLSearchParams(formParams);
    const res = await this.http.post('/CMD_API_SSL', params.toString());
    const body = typeof res.data === 'string' ? res.data : JSON.stringify(res.data);
    if (body.includes('error=1')) throw new Error(`LE request failed: ${body.substring(0, 300)}`);
    return { status: 'requested', domain };
  }

  async getSSLStatus(domain) {
    const res = await this.http.get('/CMD_API_SSL', { params: { domain } });
    return res.data;
  }

  async enableForceSSL(domain) {
    const params = new URLSearchParams({ action: 'save', domain, force_ssl: 'yes' });
    const res = await this.http.post('/CMD_API_DOMAIN', params.toString());
    const body = typeof res.data === 'string' ? res.data : JSON.stringify(res.data);
    if (body.includes('error=1')) throw new Error(`Force SSL failed: ${body.substring(0, 300)}`);
  }

  // ══════════════════════════════════════════════════════════════════════════
  // CRON MANAGEMENT
  // ══════════════════════════════════════════════════════════════════════════

  async listCronJobs() {
    const res = await this.http.get('/CMD_API_CRON_JOBS');
    const data = res.data;
    if (Array.isArray(data)) return data;
    if (typeof data === 'object') return Object.values(data);
    if (typeof data === 'string') {
      const jobs = [];
      for (const [key, val] of new URLSearchParams(data)) {
        if (key.startsWith('list')) jobs.push(decodeURIComponent(val));
      }
      return jobs;
    }
    return [];
  }

  async createCronJob(minute, hour, dayOfMonth, month, dayOfWeek, command) {
    const params = new URLSearchParams({
      action: 'create', minute, hour, dayofmonth: dayOfMonth, month, dayofweek: dayOfWeek, command,
    });
    const res = await this.http.post('/CMD_API_CRON_JOBS', params.toString());
    const body = typeof res.data === 'string' ? res.data : JSON.stringify(res.data);
    if (body.includes('error=1')) throw new Error(`Cron creation failed: ${body.substring(0, 300)}`);
  }

  async deleteCronJob(index) {
    const params = new URLSearchParams({ action: 'delete', select0: String(index) });
    const res = await this.http.post('/CMD_API_CRON_JOBS', params.toString());
    const body = typeof res.data === 'string' ? res.data : JSON.stringify(res.data);
    if (body.includes('error=1')) throw new Error(`Cron delete failed: ${body.substring(0, 300)}`);
  }

  // ══════════════════════════════════════════════════════════════════════════
  // BACKUP MANAGEMENT
  // ══════════════════════════════════════════════════════════════════════════

  async createBackup(options = {}) {
    const { files = true, databases = true, email = true } = options;
    const params = new URLSearchParams({ action: 'backup' });
    if (files)     params.append('ftp_files', 'yes');
    if (databases) params.append('ftp_databases', 'yes');
    if (email)     params.append('ftp_email', 'yes');
    const res = await this.http.post('/CMD_API_SITE_BACKUP', params.toString());
    const body = typeof res.data === 'string' ? res.data : JSON.stringify(res.data);
    if (body.includes('error=1')) throw new Error(`Backup creation failed: ${body.substring(0, 300)}`);
    return { status: 'started', message: 'Backup queued. Available in backups directory when complete.' };
  }

  async listBackups() {
    const res = await this.http.get('/CMD_API_SITE_BACKUP');
    const data = res.data;
    if (Array.isArray(data)) return data;
    if (typeof data === 'string') {
      const backups = [];
      for (const [key, val] of new URLSearchParams(data)) {
        if (key.startsWith('list') || (val && val.endsWith('.tar.gz'))) backups.push(val || key);
      }
      return backups;
    }
    return [];
  }

  async restoreBackup(backupFile, options = {}) {
    const { files = true, databases = true, email = true } = options;
    const params = new URLSearchParams({ action: 'restore', select0: backupFile });
    if (files)     params.append('ftp_files', 'yes');
    if (databases) params.append('ftp_databases', 'yes');
    if (email)     params.append('ftp_email', 'yes');
    const res = await this.http.post('/CMD_API_SITE_BACKUP', params.toString());
    const body = typeof res.data === 'string' ? res.data : JSON.stringify(res.data);
    if (body.includes('error=1')) throw new Error(`Restore failed: ${body.substring(0, 300)}`);
    return { status: 'restoring' };
  }

  // ══════════════════════════════════════════════════════════════════════════
  // ACCOUNT STATS
  // ══════════════════════════════════════════════════════════════════════════

  async getAccountUsage() {
    const res = await this.http.get('/CMD_API_SHOW_USER_USAGE');
    if (typeof res.data === 'object') return res.data;
    if (typeof res.data === 'string') {
      const result = {};
      for (const [key, val] of new URLSearchParams(res.data)) result[key] = isNaN(val) ? val : Number(val);
      return result;
    }
    return {};
  }

  async getAccountLimits() {
    const res = await this.http.get('/CMD_API_SHOW_USER_CONFIG');
    if (typeof res.data === 'object') return res.data;
    if (typeof res.data === 'string') {
      const result = {};
      for (const [key, val] of new URLSearchParams(res.data)) result[key] = isNaN(val) ? val : Number(val);
      return result;
    }
    return {};
  }

  async getAccountSummary() {
    const [usage, config] = await Promise.all([this.getAccountUsage(), this.getAccountLimits()]);
    return {
      username: this.targetUsername,
      disk:       { used: usage.quota || usage.disk || 0, limit: config.quota || 'unlimited' },
      bandwidth:  { used: usage.bandwidth || 0, limit: config.bandwidth || 'unlimited' },
      domains:    { used: usage.vdomains || usage.ndomains || 0, limit: config.vdomains || 'unlimited' },
      subdomains: { used: usage.nsubdomains || 0, limit: config.nsubdomains || 'unlimited' },
      databases:  { used: usage.mysql || 0, limit: config.mysql || 'unlimited' },
      email:      { used: usage.nemails || usage.pop || 0, limit: config.nemails || config.pop || 'unlimited' },
      php_version: config.php1_select || config.php_version || 'default',
      suspended: config.suspended === 'yes',
      package: config.package || 'unknown',
    };
  }
}
