/* ==========================================================
   Alfred CLI — API Client
   ========================================================== */

import fetch from 'node-fetch';

export class AlfredAPI {
  constructor(apiKey, baseUrl = 'https://gositeme.com/api/v1/') {
    this.apiKey  = apiKey;
    this.baseUrl = baseUrl.endsWith('/') ? baseUrl : baseUrl + '/';
    this.maxRetries = 3;
  }

  /* ---------- Core request helper ---------- */
  async request(endpoint, method = 'GET', body = null, retries = 0) {
    const url = `${this.baseUrl}${endpoint}`;
    const options = {
      method,
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${this.apiKey}`,
        'User-Agent': 'alfred-cli/1.0.0'
      }
    };

    if (body) options.body = JSON.stringify(body);

    try {
      const resp = await fetch(url, options);

      // Rate limit handling
      if (resp.status === 429) {
        const retryAfter = parseInt(resp.headers.get('Retry-After') || '5', 10);
        if (retries < this.maxRetries) {
          await this.sleep(retryAfter * 1000);
          return this.request(endpoint, method, body, retries + 1);
        }
        throw new Error(`Rate limited. Try again in ${retryAfter} seconds.`);
      }

      // Auth errors
      if (resp.status === 401) {
        throw new Error('Invalid API key. Run: alfred login');
      }
      if (resp.status === 403) {
        throw new Error('Access denied. Check your API key permissions.');
      }

      // Server errors with retry
      if (resp.status >= 500 && retries < this.maxRetries) {
        await this.sleep(1000 * (retries + 1));
        return this.request(endpoint, method, body, retries + 1);
      }

      const data = await resp.json();

      if (!resp.ok) {
        throw new Error(data.error || data.message || `HTTP ${resp.status}`);
      }

      return data;
    } catch (err) {
      if (err.name === 'FetchError' || err.code === 'ENOTFOUND') {
        if (retries < this.maxRetries) {
          await this.sleep(2000 * (retries + 1));
          return this.request(endpoint, method, body, retries + 1);
        }
        throw new Error('Network error. Check your internet connection.');
      }
      throw err;
    }
  }

  /* ---------- API Methods ---------- */

  async chat(message, history = []) {
    return this.request('chat', 'POST', { message, history });
  }

  async listTools(search = null, category = null) {
    const params = new URLSearchParams();
    if (search) params.set('search', search);
    if (category) params.set('category', category);
    const qs = params.toString();
    return this.request(`tools${qs ? '?' + qs : ''}`);
  }

  async executeTool(tool, args = {}) {
    return this.request('tools/execute', 'POST', { tool, args });
  }

  async listAgents() {
    return this.request('agents');
  }

  async fleetStatus() {
    return this.request('fleet');
  }

  /* ---------- Utility ---------- */

  sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
  }
}
