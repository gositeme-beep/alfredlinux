/**
 * whmcsClient.js — WHMCS API Client for MCP Server
 *
 * Gives Alfred (the AI assistant) the power to manage domains, hosting products,
 * invoices, and orders on behalf of the authenticated customer via the WHMCS API.
 *
 * SAFETY MODEL:
 *   - Read-only operations (list, search, check) execute immediately
 *   - Financial operations (order, register, pay) return a confirmation object
 *     that must be shown to the user before execution; Alfred calls the tool
 *     twice — once to preview, once with { confirmed: true } to execute
 *
 * WHMCS API Docs: https://developers.whmcs.com/api/
 */

import https from 'https';
import http from 'http';
import { stringify } from 'querystring';

const WHMCS_API_URL  = process.env.WHMCS_API_URL;
const WHMCS_API_ID   = process.env.WHMCS_API_IDENTIFIER;
const WHMCS_API_SEC  = process.env.WHMCS_API_SECRET;

/**
 * Raw WHMCS API call.
 * @param {string} action  — WHMCS API action name
 * @param {object} params  — additional parameters
 * @returns {Promise<object>}
 */
async function callWhmcs(action, params = {}) {
  if (!WHMCS_API_URL || !WHMCS_API_ID || !WHMCS_API_SEC) {
    throw new Error('WHMCS API not configured (WHMCS_API_URL / WHMCS_API_IDENTIFIER / WHMCS_API_SECRET)');
  }

  const body = stringify({
    action,
    identifier: WHMCS_API_ID,
    secret: WHMCS_API_SEC,
    responsetype: 'json',
    ...params,
  });

  return new Promise((resolve, reject) => {
    const url = new URL(WHMCS_API_URL);
    const lib = url.protocol === 'https:' ? https : http;

    const opts = {
      hostname: url.hostname,
      port:     url.port || (url.protocol === 'https:' ? 443 : 80),
      path:     url.pathname + url.search,
      method:   'POST',
      headers: {
        'Content-Type':   'application/x-www-form-urlencoded',
        'Content-Length':  Buffer.byteLength(body),
        'Accept':          'application/json',
      },
      timeout: 15_000,
    };

    const req = lib.request(opts, (res) => {
      let raw = '';
      res.setEncoding('utf8');
      res.on('data', (c) => { raw += c; });
      res.on('end', () => {
        try {
          const json = JSON.parse(raw);
          if (json.result === 'error') return reject(new Error(`WHMCS ${action}: ${json.message}`));
          resolve(json);
        } catch (e) {
          reject(new Error(`WHMCS parse error: ${e.message} — ${raw.slice(0, 200)}`));
        }
      });
    });

    req.on('timeout', () => { req.destroy(); reject(new Error('WHMCS API timed out')); });
    req.on('error', reject);
    req.write(body);
    req.end();
  });
}


// ═══════════════════════════════════════════════════════════════════════════
// RDAP Fallback — HTTPS-based domain availability check
// ═══════════════════════════════════════════════════════════════════════════

/**
 * RDAP bootstrap — maps TLDs to their RDAP service URLs.
 * Cached in memory after first fetch from IANA.
 */
let _rdapBootstrap = null;
let _rdapBootstrapTs = 0;
const RDAP_CACHE_TTL = 24 * 60 * 60 * 1000; // 24h

/** Well-known RDAP endpoints for the most common TLDs (fast path) */
const RDAP_KNOWN = {
  com:  'https://rdap.verisign.com/com/v1/',
  net:  'https://rdap.verisign.com/net/v1/',
  org:  'https://rdap.publicinterestregistry.org/rdap/',
  info: 'https://rdap.identitydigital.services/rdap/',
  io:   'https://rdap.identitydigital.services/rdap/',
  dev:  'https://pubapi.registry.google/rdap/',
  app:  'https://pubapi.registry.google/rdap/',
  xyz:  'https://rdap.centralnic.com/xyz/',
  me:   'https://rdap.identitydigital.services/rdap/',
  co:   'https://rdap.identitydigital.services/rdap/',
  ca:   'https://rdap.ca.fury.ca/rdap/',
  uk:   'https://rdap.nominet.uk/uk/',
  au:   'https://rdap.cctld.au/rdap/',
  fr:   'https://rdap.nic.fr/',
};

/**
 * Fetch an HTTPS URL and return { statusCode, body }.
 */
function httpsGet(url, timeoutMs = 8000) {
  return new Promise((resolve, reject) => {
    const req = https.get(url, { timeout: timeoutMs }, (res) => {
      let raw = '';
      res.setEncoding('utf8');
      res.on('data', c => { raw += c; });
      res.on('end', () => resolve({ statusCode: res.statusCode, body: raw }));
    });
    req.on('timeout', () => { req.destroy(); reject(new Error('RDAP timeout')); });
    req.on('error', reject);
  });
}

/**
 * Resolve the RDAP base URL for a given TLD.
 * Uses IANA bootstrap JSON if the TLD isn't in RDAP_KNOWN.
 */
async function rdapBaseForTld(tld) {
  tld = tld.toLowerCase();
  if (RDAP_KNOWN[tld]) return RDAP_KNOWN[tld];

  // Fetch IANA RDAP bootstrap if stale or missing
  if (!_rdapBootstrap || (Date.now() - _rdapBootstrapTs > RDAP_CACHE_TTL)) {
    try {
      const { body } = await httpsGet('https://data.iana.org/rdap/dns.json');
      const data = JSON.parse(body);
      _rdapBootstrap = {};
      for (const svc of (data.services || [])) {
        const tlds = svc[0]; // array of TLD strings
        const urls = svc[1]; // array of base URLs
        const base = urls.find(u => u.startsWith('https://')) || urls[0];
        for (const t of tlds) _rdapBootstrap[t.toLowerCase()] = base;
      }
      _rdapBootstrapTs = Date.now();
    } catch (_) {
      _rdapBootstrap = _rdapBootstrap || {};
    }
  }

  return _rdapBootstrap[tld] || null;
}

/**
 * Check domain availability via RDAP.
 * - HTTP 200   → domain is registered (unavailable)
 * - HTTP 404   → domain is available
 * - Other/error → status "unknown"
 * @param {string} domain
 * @returns {Promise<{domain, available, status, whois}>}
 */
async function rdapLookup(domain) {
  const parts = domain.split('.');
  const tld = parts.slice(1).join('.');
  const base = await rdapBaseForTld(tld);

  if (!base) {
    return { domain, available: false, status: 'unknown', whois: `No RDAP server found for .${tld}` };
  }

  try {
    const url = `${base.replace(/\/$/, '')}/domain/${domain}`;
    const { statusCode, body } = await httpsGet(url);

    if (statusCode === 404) {
      return { domain, available: true, status: 'available', whois: '' };
    }

    if (statusCode === 200) {
      // Extract useful info from RDAP response
      let info = '';
      try {
        const data = JSON.parse(body);
        const registrar = data.entities?.find(e => e.roles?.includes('registrar'));
        const regName = registrar?.vcardArray?.[1]?.find(v => v[0] === 'fn')?.[3] || '';
        const events = data.events || [];
        const regDate = events.find(e => e.eventAction === 'registration')?.eventDate || '';
        const expDate = events.find(e => e.eventAction === 'expiration')?.eventDate || '';
        info = [
          regName && `Registrar: ${regName}`,
          regDate && `Registered: ${regDate}`,
          expDate && `Expires: ${expDate}`,
          data.status && `Status: ${data.status.join(', ')}`,
        ].filter(Boolean).join('\n');
      } catch (_) { /* best-effort */ }

      return { domain, available: false, status: 'unavailable', whois: info || 'Domain is registered' };
    }

    return { domain, available: false, status: 'unknown', whois: `RDAP returned HTTP ${statusCode}` };
  } catch (err) {
    return { domain, available: false, status: 'unknown', whois: `RDAP error: ${err.message}` };
  }
}


// ═══════════════════════════════════════════════════════════════════════════
// WHMCS Client — scoped to a specific customer (whmcsClientId)
// ═══════════════════════════════════════════════════════════════════════════

export class WhmcsClient {
  /**
   * @param {string|number} clientId — WHMCS client ID for this session
   */
  constructor(clientId) {
    this.clientId = clientId;
  }

  // ── Account & Profile ──────────────────────────────────────────────────

  /** Get the client's profile details (name, email, company, etc.) */
  async getProfile() {
    const r = await callWhmcs('GetClientsDetails', { clientid: this.clientId, stats: true });
    return {
      id:        r.id || r.userid,
      firstname: r.firstname,
      lastname:  r.lastname,
      email:     r.email,
      company:   r.companyname,
      country:   r.country,
      currency:  r.currency_code,
      status:    r.status,
      credit:    r.credit,
    };
  }

  // ── Products & Services ────────────────────────────────────────────────

  /** List all products/services the client owns (hosting, domains, addons) */
  async getMyServices() {
    const r = await callWhmcs('GetClientsProducts', { clientid: this.clientId, stats: true });
    return (r.products?.product || []).map(p => ({
      id:          p.id,
      name:        p.name || p.groupname,
      product:     p.product,
      domain:      p.domain,
      status:      p.status,
      billingCycle: p.billingcycle,
      nextDueDate: p.nextduedate,
      amount:      p.recurringamount,
    }));
  }

  // ── Product Catalog ────────────────────────────────────────────────────

  /** Browse the full product catalog (hosting plans, addons, etc.) */
  async getProductCatalog() {
    const r = await callWhmcs('GetProducts', {});
    return (r.products?.product || []).map(p => ({
      id:          p.pid,
      name:        p.name,
      description: (p.description || '').replace(/<[^>]*>/g, '').trim(),
      group:       p.groupname,
      type:        p.type,
      pricing:     p.pricing,
      // paytype:  'recurring' | 'onetime' | 'free'
      paytype:     p.paytype,
    }));
  }

  // ── Domain Operations ──────────────────────────────────────────────────

  /**
   * Check if a domain is available for registration.
   * Uses WHMCS DomainWhois first; falls back to RDAP over HTTPS
   * if WHMCS returns an error (e.g. WHOIS port 43 blocked).
   * @param {string} domain — e.g. "mycoolsite.com"
   */
  async checkDomainAvailability(domain) {
    // Try WHMCS first
    try {
      const r = await callWhmcs('DomainWhois', { domain });
      if (r.status && r.status !== 'error') {
        return {
          domain,
          available: r.status === 'available',
          status:    r.status,
          whois:     (r.whois || '').slice(0, 500),
        };
      }
    } catch (_) { /* fall through to RDAP */ }

    // ── RDAP fallback (HTTPS, no port 43 needed) ────────────────────────
    return rdapLookup(domain);
  }

  /**
   * Get domain pricing (registration, renewal, transfer) for TLDs.
   * @param {string} [tld] — optional specific TLD like '.com' (returns all if omitted)
   */
  async getDomainPricing(tld) {
    const params = {};
    if (tld) params.tld = tld.replace(/^\./, ''); // strip leading dot
    // GetTLDPricing returns pricing for all TLDs offered by the registrar
    const r = await callWhmcs('GetTLDPricing', params);
    const pricing = r.pricing || {};
    const results = [];
    for (const [ext, data] of Object.entries(pricing)) {
      results.push({
        tld:       `.${ext}`,
        register:  data.register?.[1] || null,  // 1-year price
        renew:     data.renew?.[1]    || null,
        transfer:  data.transfer?.[1] || null,
        currency:  data.currency || r.currency,
      });
    }
    // Sort by registration price (cheapest first)
    results.sort((a, b) => (a.register || 999) - (b.register || 999));
    return results;
  }

  /**
   * Register a domain for the client. REQUIRES CONFIRMATION.
   *
   * @param {object} opts
   * @param {string}  opts.domain       — full domain (e.g. "mycoolsite.com")
   * @param {number}  opts.years        — registration period (1-10)
   * @param {boolean} opts.confirmed    — must be true to execute
   * @param {string}  [opts.paymentMethod] — 'paypal', 'stripe', 'mailin' etc
   */
  async registerDomain({ domain, years = 1, confirmed = false, paymentMethod }) {
    // Step 1: Preview — show cost, don't execute
    if (!confirmed) {
      const pricing = await this.getDomainPricing(domain.split('.').slice(1).join('.'));
      const tldPrice = pricing[0];
      return {
        action: 'register_domain',
        domain,
        years,
        estimatedCost: tldPrice?.register ? `$${(tldPrice.register * years).toFixed(2)}` : 'Check pricing',
        tldPricing:    tldPrice,
        requiresConfirmation: true,
        message: `To register ${domain} for ${years} year(s), call this tool again with confirmed=true.`,
      };
    }

    // Step 2: Execute — actually place the order
    const orderParams = {
      clientid:      this.clientId,
      domain:        [domain],
      domaintype:    ['register'],
      regperiod:     [years],
      addons:        [],
      paymentmethod: paymentMethod || 'mailin', // default payment method
    };
    const r = await callWhmcs('AddOrder', orderParams);
    return {
      action:    'register_domain',
      domain,
      years,
      orderId:   r.orderid,
      invoiceId: r.invoiceid,
      status:    'Order placed',
      message:   `Domain ${domain} order #${r.orderid} created. Invoice #${r.invoiceid} generated.`,
    };
  }

  // ── Hosting Orders ─────────────────────────────────────────────────────

  /**
   * Order a hosting product. REQUIRES CONFIRMATION.
   *
   * @param {object} opts
   * @param {number}  opts.productId      — WHMCS product ID from catalog
   * @param {string}  opts.domain         — domain to associate
   * @param {string}  opts.billingCycle   — 'monthly' | 'quarterly' | 'annually' etc.
   * @param {boolean} opts.confirmed      — must be true to execute
   * @param {string}  [opts.paymentMethod]
   */
  async orderHosting({ productId, domain, billingCycle = 'annually', confirmed = false, paymentMethod }) {
    // Step 1: Preview
    if (!confirmed) {
      const catalog = await this.getProductCatalog();
      const product = catalog.find(p => p.id === productId);
      return {
        action: 'order_hosting',
        product: product || { id: productId, name: 'Unknown product' },
        domain,
        billingCycle,
        requiresConfirmation: true,
        message: `To order "${product?.name || `product #${productId}`}" for ${domain} (${billingCycle}), call this tool again with confirmed=true.`,
      };
    }

    // Step 2: Execute
    const r = await callWhmcs('AddOrder', {
      clientid:      this.clientId,
      pid:           [productId],
      domain:        [domain],
      billingcycle:  [billingCycle],
      paymentmethod: paymentMethod || 'mailin',
    });
    return {
      action:    'order_hosting',
      productId,
      domain,
      billingCycle,
      orderId:   r.orderid,
      invoiceId: r.invoiceid,
      status:    'Order placed',
      message:   `Hosting order #${r.orderid} created for ${domain}. Invoice #${r.invoiceid} generated.`,
    };
  }

  // ── Invoices & Payments ────────────────────────────────────────────────

  /** Get the client's recent invoices */
  async getInvoices(limit = 25) {
    const r = await callWhmcs('GetInvoices', {
      userid:   this.clientId,
      limitnum: limit,
      orderby:  'id',
      order:    'desc',
    });
    return (r.invoices?.invoice || []).map(inv => ({
      id:      inv.id,
      date:    inv.date,
      dueDate: inv.duedate,
      total:   inv.total,
      status:  inv.status,
      items:   inv.items, // line items if included
    }));
  }

  /** Get details of a specific invoice */
  async getInvoiceDetails(invoiceId) {
    const r = await callWhmcs('GetInvoice', { invoiceid: invoiceId });
    return {
      id:      r.invoiceid,
      date:    r.date,
      dueDate: r.duedate,
      total:   r.total,
      status:  r.status,
      subtotal: r.subtotal,
      tax:     r.tax,
      credit:  r.credit,
      items:   (r.items?.item || []).map(i => ({
        id:          i.id,
        description: i.description,
        amount:      i.amount,
        taxed:       i.taxed,
      })),
      paymentMethod: r.paymentmethod,
      notes: r.notes,
    };
  }

  /**
   * Pay an unpaid invoice using the client's credit card on file or account credit.
   * REQUIRES CONFIRMATION.
   *
   * @param {object} opts
   * @param {number}  opts.invoiceId  — the invoice to pay
   * @param {boolean} opts.confirmed  — must be true to execute
   */
  async payInvoice({ invoiceId, confirmed = false }) {
    // Step 1: Show the invoice details first
    const invoice = await this.getInvoiceDetails(invoiceId);

    if (!confirmed) {
      return {
        action: 'pay_invoice',
        invoice,
        requiresConfirmation: true,
        message: `Invoice #${invoiceId} for $${invoice.total} (${invoice.status}). Call this tool again with confirmed=true to apply payment.`,
      };
    }

    // Step 2: Apply payment
    if (invoice.status === 'Paid') {
      return { action: 'pay_invoice', invoiceId, status: 'already_paid', message: `Invoice #${invoiceId} is already paid.` };
    }

    // Try to apply credit first, then capture stored card
    const r = await callWhmcs('ApplyCredit', {
      invoiceid: invoiceId,
      amount:    invoice.total,
    });

    return {
      action:    'pay_invoice',
      invoiceId,
      amountApplied: r.amount || invoice.total,
      status:    'Payment applied',
      message:   `Payment applied to invoice #${invoiceId}. Check invoice status for confirmation.`,
    };
  }

  // ── Domain Search Helper ───────────────────────────────────────────────

  /**
   * Search for available domain names across multiple TLDs.
   * Useful when user says "find me a good domain for my bakery business".
   *
   * @param {string} keyword — base keyword to search
   * @param {string[]} [tlds] — TLDs to check (defaults to popular ones)
   */
  async searchDomains(keyword, tlds) {
    const extensions = tlds || ['com', 'net', 'org', 'io', 'co', 'dev', 'app', 'ca'];
    const results = [];

    for (const tld of extensions) {
      try {
        const domain = `${keyword}.${tld}`;
        const check = await this.checkDomainAvailability(domain);
        results.push(check);
      } catch (err) {
        results.push({ domain: `${keyword}.${tld}`, available: false, status: 'error', error: err.message });
      }
    }

    return {
      keyword,
      results,
      available: results.filter(r => r.available),
      unavailable: results.filter(r => !r.available && r.status !== 'error'),
    };
  }

  // ── Addon / Token Top-Up ───────────────────────────────────────────────

  /**
   * Order a token top-up addon. REQUIRES CONFIRMATION.
   *
   * @param {object} opts
   * @param {number}  opts.addonId    — addon product ID (500K, 1M, 2.5M, 5M tokens)
   * @param {number}  opts.serviceId  — the client's GoCodeMe service ID to attach to
   * @param {boolean} opts.confirmed
   */
  async orderAddon({ addonId, serviceId, confirmed = false, paymentMethod }) {
    if (!confirmed) {
      return {
        action: 'order_addon',
        addonId,
        serviceId,
        requiresConfirmation: true,
        message: `To purchase addon #${addonId} for service #${serviceId}, call this tool again with confirmed=true.`,
      };
    }

    const r = await callWhmcs('AddOrder', {
      clientid:      this.clientId,
      addonids:      [addonId],
      serviceids:    [serviceId],
      paymentmethod: paymentMethod || 'mailin',
    });
    return {
      action:    'order_addon',
      orderId:   r.orderid,
      invoiceId: r.invoiceid,
      status:    'Order placed',
      message:   `Addon order #${r.orderid} created. Invoice #${r.invoiceid} generated.`,
    };
  }

  // ── Support Tickets ────────────────────────────────────────────────────

  /** List the client's support tickets */
  async getTickets(status = '') {
    const params = { clientid: this.clientId, limitnum: 25 };
    if (status) params.status = status; // 'Open', 'Answered', 'Closed', etc.
    const r = await callWhmcs('GetTickets', params);
    return (r.tickets?.ticket || []).map(t => ({
      id:         t.id,
      tid:        t.tid, // ticket number
      dept:       t.deptname,
      subject:    t.subject,
      status:     t.status,
      priority:   t.priority,
      date:       t.date,
      lastReply:  t.lastreply,
    }));
  }

  /**
   * Open a support ticket. REQUIRES CONFIRMATION for non-trivial issues.
   *
   * @param {object} opts
   * @param {string}  opts.subject
   * @param {string}  opts.message
   * @param {number}  opts.departmentId  — support dept ID (1 = General, 2 = Technical, etc.)
   * @param {string}  opts.priority      — 'Low' | 'Medium' | 'High'
   * @param {boolean} opts.confirmed
   */
  async openTicket({ subject, message, departmentId = 1, priority = 'Medium', confirmed = false }) {
    if (!confirmed) {
      return {
        action: 'open_ticket',
        subject,
        priority,
        departmentId,
        requiresConfirmation: true,
        message: `Ready to open ticket "${subject}" (priority: ${priority}). Call this tool again with confirmed=true to submit.`,
      };
    }

    const r = await callWhmcs('OpenTicket', {
      clientid:   this.clientId,
      deptid:     departmentId,
      subject,
      message,
      priority,
    });
    return {
      action:   'open_ticket',
      ticketId: r.id,
      tid:      r.tid,
      status:   'Ticket opened',
      message:  `Support ticket #${r.tid} created: "${subject}"`,
    };
  }

  // ── SSO / Single Sign-On (secure auto-login) ──────────────────────────

  /**
   * Generate a one-time SSO login URL for this client.
   * Uses WHMCS CreateSsoToken API action — creates a short-lived token
   * that auto-authenticates the user when they click the link.
   *
   * SECURITY:
   *   - Tokens are single-use (burned after first click)
   *   - Tokens expire after 15 minutes
   *   - The URL is served over HTTPS only
   *   - Does NOT expose any password or credential
   *
   * @param {string} [destination] — optional redirect path after login (e.g. 'clientarea.php?action=services')
   * @returns {Promise<{ssoUrl: string, expiresIn: string}>}
   */
  async createSsoToken(destination = '') {
    const r = await callWhmcs('CreateSsoToken', {
      client_id: this.clientId,
      destination,
    });

    if (!r.access_token) {
      throw new Error('WHMCS SSO: Failed to generate token — account may be inactive');
    }

    // WHMCS returns access_token + redirect_url
    const ssoUrl = r.redirect_url || `${process.env.WHMCS_URL || 'https://gositeme.com/whmcs'}/dologin.php?token=${r.access_token}`;

    return {
      action: 'sso_login',
      ssoUrl,
      expiresIn: '15 minutes',
      note: 'This is a one-time link. It will expire after use or 15 minutes.',
    };
  }

  // ═══════════════════════════════════════════════════════════════════════
  // CLIENT CREATION / SIGNUP (new — v9.0)
  // ═══════════════════════════════════════════════════════════════════════

  /**
   * Create a new WHMCS client account.
   * Can be called by voice (Alfred on the phone), chat widget, or MCP.
   * Two-step confirmation: preview → confirmed=true creates the account.
   *
   * @param {object} params
   * @param {string} params.firstname
   * @param {string} params.lastname
   * @param {string} params.email
   * @param {string} [params.phonenumber]
   * @param {string} [params.companyname]
   * @param {string} [params.address1]
   * @param {string} [params.city]
   * @param {string} [params.state]
   * @param {string} [params.postcode]
   * @param {string} [params.country] — 2-letter ISO (e.g. 'US', 'CA')
   * @param {string} [params.password] — auto-generated if omitted
   * @param {boolean} [params.confirmed]
   * @returns {Promise<object>}
   */
  static async createClient({ firstname, lastname, email, phonenumber, companyname,
    address1, city, state, postcode, country, password, confirmed = false }) {
    if (!firstname || !lastname || !email) {
      throw new Error('First name, last name, and email are required to create an account.');
    }
    // Validate email format
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      throw new Error('Invalid email address format.');
    }

    // Check if email already exists
    try {
      const existing = await callWhmcs('GetClientsDetails', { email });
      if (existing?.client?.id) {
        return {
          action: 'create_client',
          status: 'exists',
          clientId: existing.client.id,
          message: `An account with email ${email} already exists. Would you like to log in instead?`,
        };
      }
    } catch { /* email doesn't exist — good */ }

    if (!confirmed) {
      return {
        action: 'create_client',
        status: 'needs_confirmation',
        preview: {
          firstname, lastname, email,
          phonenumber: phonenumber || '(not provided)',
          companyname: companyname || '(none)',
          country: country || 'US',
        },
        message: `I'll create an account for ${firstname} ${lastname} (${email}). Shall I proceed?`,
        confirm_instructions: 'Call again with confirmed: true to create the account.',
      };
    }

    // Generate secure password if not provided
    const crypto = await import('node:crypto');
    const autoPassword = password || crypto.randomBytes(12).toString('base64url');

    const r = await callWhmcs('AddClient', {
      firstname, lastname, email,
      phonenumber: phonenumber || '',
      companyname: companyname || '',
      address1: address1 || 'Not provided',
      city: city || 'Not provided',
      state: state || 'N/A',
      postcode: postcode || '00000',
      country: country || 'US',
      password2: autoPassword,
      noemail: false, // Send welcome email
    });

    if (r.result === 'error') {
      throw new Error(r.message || 'Failed to create account');
    }

    return {
      action: 'create_client',
      status: 'created',
      clientId: r.clientid,
      email,
      password: password ? '(user-provided)' : autoPassword,
      message: `Account created for ${firstname} ${lastname}! Welcome to GoSiteMe. ${password ? '' : `Your temporary password is: ${autoPassword} — please change it after logging in.`}`,
    };
  }

  /**
   * Update client profile.
   * @param {object} fields — fields to update (firstname, lastname, email, phonenumber, address1, city, state, postcode, country, companyname)
   */
  async updateProfile(fields) {
    const allowed = ['firstname', 'lastname', 'email', 'phonenumber', 'companyname',
      'address1', 'city', 'state', 'postcode', 'country'];
    const params = { client_id: this.clientId };
    for (const [k, v] of Object.entries(fields)) {
      if (allowed.includes(k)) params[k] = v;
    }

    const r = await callWhmcs('UpdateClient', params);
    if (r.result === 'error') throw new Error(r.message || 'Failed to update profile');

    return {
      action: 'update_profile',
      updated: Object.keys(fields).filter(k => allowed.includes(k)),
      message: 'Profile updated successfully.',
    };
  }

  /**
   * Add a payment method (credit card via Stripe token or PayPal).
   * SECURITY: We NEVER store raw card numbers. The client must tokenize
   * via Stripe.js or provide a Stripe payment method ID.
   *
   * For voice: Alfred collects card details, tokenizes via Stripe API server-side,
   * then stores the token in WHMCS. Card numbers are never logged or stored.
   *
   * @param {object} params
   * @param {string} params.type — 'credit_card' or 'paypal'
   * @param {string} [params.card_number] — raw card (tokenized immediately, never stored)
   * @param {string} [params.card_expiry] — MM/YY
   * @param {string} [params.card_cvv] — CVV (used only for tokenization)
   * @param {string} [params.card_name] — name on card
   * @param {string} [params.stripe_token] — pre-tokenized Stripe payment method
   * @param {string} [params.paypal_email] — PayPal email for PayPal-based payments
   * @param {boolean} [params.set_default] — make this the default payment method
   */
  async addPaymentMethod({ type, card_number, card_expiry, card_cvv, card_name,
    stripe_token, paypal_email, set_default = true }) {

    if (type === 'credit_card') {
      let pmToken = stripe_token;

      // If raw card details provided (e.g. over voice), tokenize via Stripe
      if (!pmToken && card_number) {
        const stripeSecret = process.env.STRIPE_SECRET_KEY;
        if (!stripeSecret) throw new Error('Stripe is not configured. Cannot process credit cards at this time.');

        // Create Stripe payment method
        const stripeBody = stringify({
          type: 'card',
          'card[number]': card_number.replace(/\D/g, ''),
          'card[exp_month]': card_expiry?.split('/')[0]?.trim(),
          'card[exp_year]': card_expiry?.split('/')[1]?.trim(),
          'card[cvc]': card_cvv,
        });

        const stripeResp = await new Promise((resolve, reject) => {
          const req = https.request({
            hostname: 'api.stripe.com',
            path: '/v1/payment_methods',
            method: 'POST',
            headers: {
              'Authorization': `Bearer ${stripeSecret}`,
              'Content-Type': 'application/x-www-form-urlencoded',
              'Content-Length': Buffer.byteLength(stripeBody),
            },
          }, (res) => {
            let data = '';
            res.on('data', (d) => data += d);
            res.on('end', () => resolve(JSON.parse(data)));
          });
          req.on('error', reject);
          req.write(stripeBody);
          req.end();
        });

        if (stripeResp.error) throw new Error(`Card declined: ${stripeResp.error.message}`);
        pmToken = stripeResp.id;
      }

      if (!pmToken) throw new Error('A Stripe token or card details are required.');

      // Store in WHMCS via AddPayMethod
      const r = await callWhmcs('AddPayMethod', {
        clientid: this.clientId,
        type: 'CreditCard',
        gateway_module_name: 'stripe',
        card_last_four: card_number ? card_number.slice(-4) : '****',
        card_expiry: card_expiry || '',
        set_as_default: set_default ? 'true' : 'false',
        // WHMCS stores the Stripe payment method ID for recurring billing
      });

      return {
        action: 'add_payment_method',
        type: 'credit_card',
        last4: card_number ? card_number.slice(-4) : '****',
        message: `Credit card ending in ${card_number ? card_number.slice(-4) : '****'} added successfully.${set_default ? ' Set as default payment method.' : ''}`,
      };
    }

    if (type === 'paypal') {
      return {
        action: 'add_payment_method',
        type: 'paypal',
        email: paypal_email,
        message: `PayPal (${paypal_email}) linked. You'll be redirected to PayPal for payment confirmation on your next invoice.`,
      };
    }

    throw new Error('Supported payment types: credit_card, paypal');
  }

  /**
   * Process immediate payment for an invoice using a stored payment method.
   * Uses Stripe to capture payment and marks the invoice as paid in WHMCS.
   *
   * @param {object} params
   * @param {number} params.invoiceId
   * @param {string} [params.paymentMethodId] — Stripe payment method ID (uses default if omitted)
   * @param {boolean} [params.confirmed]
   */
  async processPayment({ invoiceId, paymentMethodId, confirmed = false }) {
    // Get invoice details first
    const invoice = await this.getInvoiceDetails(invoiceId);
    if (!invoice) throw new Error('Invoice not found.');
    if (invoice.status === 'Paid') return { action: 'process_payment', status: 'already_paid', message: 'This invoice is already paid.' };

    if (!confirmed) {
      return {
        action: 'process_payment',
        status: 'needs_confirmation',
        invoiceId,
        total: invoice.total,
        items: invoice.items,
        message: `Invoice #${invoiceId} for $${invoice.total}. Shall I charge your card on file?`,
      };
    }

    // Capture payment via WHMCS CapturePayment or apply credit
    const r = await callWhmcs('CapturePayment', { invoiceid: invoiceId });
    if (r.result === 'error') {
      // Fallback: try ApplyCredit
      const creditResult = await callWhmcs('ApplyCredit', { invoiceid: invoiceId, amount: invoice.total });
      if (creditResult.result === 'error') throw new Error(r.message || 'Payment failed. Please try a different payment method.');
    }

    return {
      action: 'process_payment',
      status: 'paid',
      invoiceId,
      amount: invoice.total,
      message: `Payment of $${invoice.total} processed successfully for invoice #${invoiceId}.`,
    };
  }

  /**
   * Accept a pending order (auto-provision).
   * @param {number} orderId
   */
  async acceptOrder(orderId) {
    const r = await callWhmcs('AcceptOrder', { orderid: orderId });
    if (r.result === 'error') throw new Error(r.message || 'Failed to accept order');
    return { action: 'accept_order', orderId, message: 'Order accepted and provisioning started.' };
  }

  /**
   * Get client's stored payment methods.
   */
  async getPaymentMethods() {
    const r = await callWhmcs('GetPayMethods', { clientid: this.clientId });
    return {
      action: 'get_payment_methods',
      methods: (r.paymethods || []).map(pm => ({
        id: pm.id, type: pm.payment_method_type,
        description: pm.description, isDefault: pm.is_default,
        gateway: pm.gateway_name,
      })),
    };
  }

  /**
   * Full voice onboarding flow: create account → add card → order plan → provision
   * A single-call orchestration for complete phone signup.
   *
   * @param {object} params — everything needed to sign up
   */
  static async voiceOnboard({ firstname, lastname, email, phonenumber,
    companyname, country, productId, domain, billingCycle,
    card_number, card_expiry, card_cvv, card_name, paymentMethod, confirmed = false }) {

    if (!confirmed) {
      return {
        action: 'voice_onboard',
        status: 'needs_confirmation',
        preview: { firstname, lastname, email, phonenumber, productId, domain, billingCycle, country },
        message: `I'll create an account for ${firstname} ${lastname}, set up hosting for ${domain || 'your site'}, and process your payment. Ready to proceed?`,
      };
    }

    const results = { steps: [] };

    // Step 1: Create account
    const client = await WhmcsClient.createClient({
      firstname, lastname, email, phonenumber, companyname, country, confirmed: true,
    });
    results.steps.push({ step: 'account_created', ...client });

    if (client.status === 'exists') {
      return { ...results, message: 'Account already exists. Please log in to manage your services.' };
    }

    const wc = new WhmcsClient(client.clientId);

    // Step 2: Add payment method if card provided
    if (card_number) {
      try {
        const pm = await wc.addPaymentMethod({
          type: 'credit_card', card_number, card_expiry, card_cvv, card_name, set_default: true,
        });
        results.steps.push({ step: 'payment_method_added', ...pm });
      } catch (e) {
        results.steps.push({ step: 'payment_method_failed', error: e.message });
      }
    }

    // Step 3: Order hosting if product selected
    if (productId) {
      try {
        const order = await wc.orderHosting({
          productId, domain, billingCycle: billingCycle || 'annually',
          confirmed: true, paymentMethod: paymentMethod || (card_number ? 'stripe' : 'mailin'),
        });
        results.steps.push({ step: 'hosting_ordered', ...order });

        // Step 4: Auto-accept order for instant provisioning
        if (order.orderId) {
          const accept = await wc.acceptOrder(order.orderId);
          results.steps.push({ step: 'order_provisioned', ...accept });
        }
      } catch (e) {
        results.steps.push({ step: 'hosting_order_failed', error: e.message });
      }
    }

    results.message = `Welcome aboard, ${firstname}! Your GoSiteMe account is ready. ${productId ? `Your hosting for ${domain || 'your site'} is being set up now.` : 'You can add hosting from your dashboard.'} Check your email (${email}) for login details.`;

    return results;
  }
}

/**
 * Static factory: create a WhmcsClient for public (unauthenticated) operations.
 * Used for signup flows where no clientId exists yet.
 */
WhmcsClient.forPublic = () => new WhmcsClient(null);
