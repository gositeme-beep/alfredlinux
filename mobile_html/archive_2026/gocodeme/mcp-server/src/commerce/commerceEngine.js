/**
 * commerceEngine.js — COMMERCE: Agent Commerce Infrastructure Engine
 *
 * Makes any online store "agent-ready" by providing:
 *  - Truth Layer: Normalizes messy e-commerce data into structured, deterministic facts
 *  - Action Layer: Standard verbs for commerce operations (orders, returns, refunds, etc.)
 *  - Governance Layer: Policy-as-code — merchant rules become machine-executable constraints
 *  - Connector Framework: Adapters for Shopify, WooCommerce, and custom stores
 *  - Workflow Templates: Pre-built flows for common commerce operations
 *  - Audit Trail: Immutable Input→Decision→Outcome logging
 *
 * Inspired by Howdify's "Agent Commerce Infrastructure" — but built directly
 * into the GoCodeMe MCP tool ecosystem so Alfred (and any AI agent) can
 * operate on real stores deterministically.
 */

import { randomUUID } from 'node:crypto';
import fs from 'node:fs/promises';
import path from 'node:path';

const COMMERCE_BASE = '/home/gositeme/.gocodeme/commerce';

// ── Helpers ──────────────────────────────────────────────────────────────────

async function ensureDir(dir) { await fs.mkdir(dir, { recursive: true }); }

async function loadJSON(file, fallback = {}) {
  try { return JSON.parse(await fs.readFile(file, 'utf8')); }
  catch { return fallback; }
}

async function saveJSON(file, data) {
  await ensureDir(path.dirname(file));
  await fs.writeFile(file, JSON.stringify(data, null, 2));
}

function storesPath(user)    { return path.join(COMMERCE_BASE, user, 'stores.json'); }
function policiesPath(user)  { return path.join(COMMERCE_BASE, user, 'policies.json'); }
function auditPath(user)     { return path.join(COMMERCE_BASE, user, 'audit_log.json'); }
function workflowsPath(user) { return path.join(COMMERCE_BASE, user, 'workflows.json'); }
function cachePath(user)     { return path.join(COMMERCE_BASE, user, 'truth_cache.json'); }
function analyticsPath(user) { return path.join(COMMERCE_BASE, user, 'analytics.json'); }

const now = () => new Date().toISOString();

// ════════════════════════════════════════════════════════════════════════════
// SECTION 1: STORE CONNECTOR FRAMEWORK
// ════════════════════════════════════════════════════════════════════════════

/**
 * Platform adapter registry — each adapter knows how to talk to a specific
 * e-commerce platform and normalize its data into our Truth Layer format.
 */
const PLATFORM_ADAPTERS = {
  shopify: {
    name: 'Shopify',
    buildHeaders(store) {
      return {
        'X-Shopify-Access-Token': store.credentials.access_token,
        'Content-Type': 'application/json',
      };
    },
    buildUrl(store, resource) {
      return `https://${store.domain}/admin/api/2024-01/${resource}.json`;
    },
    async fetchProducts(store) {
      const url = this.buildUrl(store, 'products');
      const resp = await fetch(url, { headers: this.buildHeaders(store) });
      if (!resp.ok) throw new Error(`Shopify API ${resp.status}: ${resp.statusText}`);
      const data = await resp.json();
      return (data.products || []).map(p => normalizeProduct(p, 'shopify'));
    },
    async fetchOrders(store, params = {}) {
      let url = this.buildUrl(store, 'orders');
      if (params.status) url += `?status=${params.status}`;
      const resp = await fetch(url, { headers: this.buildHeaders(store) });
      if (!resp.ok) throw new Error(`Shopify API ${resp.status}: ${resp.statusText}`);
      const data = await resp.json();
      return (data.orders || []).map(o => normalizeOrder(o, 'shopify'));
    },
    async fetchOrder(store, orderId) {
      const url = this.buildUrl(store, `orders/${orderId}`);
      const resp = await fetch(url, { headers: this.buildHeaders(store) });
      if (!resp.ok) throw new Error(`Shopify API ${resp.status}: ${resp.statusText}`);
      const data = await resp.json();
      return normalizeOrder(data.order, 'shopify');
    },
    async createRefund(store, orderId, amount, reason) {
      const url = this.buildUrl(store, `orders/${orderId}/refunds`);
      const body = {
        refund: {
          currency: store.currency || 'USD',
          note: reason,
          transactions: [{ kind: 'refund', amount: String(amount) }],
        },
      };
      const resp = await fetch(url, {
        method: 'POST',
        headers: this.buildHeaders(store),
        body: JSON.stringify(body),
      });
      if (!resp.ok) throw new Error(`Shopify refund failed: ${resp.status}`);
      return resp.json();
    },
    async cancelOrder(store, orderId, reason) {
      const url = this.buildUrl(store, `orders/${orderId}/cancel`);
      const resp = await fetch(url, {
        method: 'POST',
        headers: this.buildHeaders(store),
        body: JSON.stringify({ reason }),
      });
      if (!resp.ok) throw new Error(`Shopify cancel failed: ${resp.status}`);
      return resp.json();
    },
    async getInventory(store, productId) {
      const url = this.buildUrl(store, `products/${productId}`);
      const resp = await fetch(url, { headers: this.buildHeaders(store) });
      if (!resp.ok) throw new Error(`Shopify API ${resp.status}`);
      const data = await resp.json();
      const variants = data.product?.variants || [];
      return variants.map(v => ({
        variantId: String(v.id),
        sku: v.sku || '',
        title: v.title,
        quantity: v.inventory_quantity,
        price: parseFloat(v.price),
        available: v.inventory_quantity > 0,
      }));
    },
  },

  woocommerce: {
    name: 'WooCommerce',
    buildHeaders(store) {
      const auth = Buffer.from(`${store.credentials.consumer_key}:${store.credentials.consumer_secret}`).toString('base64');
      return {
        Authorization: `Basic ${auth}`,
        'Content-Type': 'application/json',
      };
    },
    buildUrl(store, resource) {
      return `${store.domain}/wp-json/wc/v3/${resource}`;
    },
    async fetchProducts(store) {
      const url = this.buildUrl(store, 'products?per_page=100');
      const resp = await fetch(url, { headers: this.buildHeaders(store) });
      if (!resp.ok) throw new Error(`WooCommerce API ${resp.status}: ${resp.statusText}`);
      const data = await resp.json();
      return (data || []).map(p => normalizeProduct(p, 'woocommerce'));
    },
    async fetchOrders(store, params = {}) {
      let url = this.buildUrl(store, 'orders?per_page=100');
      if (params.status) url += `&status=${params.status}`;
      const resp = await fetch(url, { headers: this.buildHeaders(store) });
      if (!resp.ok) throw new Error(`WooCommerce API ${resp.status}: ${resp.statusText}`);
      const data = await resp.json();
      return (data || []).map(o => normalizeOrder(o, 'woocommerce'));
    },
    async fetchOrder(store, orderId) {
      const url = this.buildUrl(store, `orders/${orderId}`);
      const resp = await fetch(url, { headers: this.buildHeaders(store) });
      if (!resp.ok) throw new Error(`WooCommerce API ${resp.status}: ${resp.statusText}`);
      const data = await resp.json();
      return normalizeOrder(data, 'woocommerce');
    },
    async createRefund(store, orderId, amount, reason) {
      const url = this.buildUrl(store, `orders/${orderId}/refunds`);
      const resp = await fetch(url, {
        method: 'POST',
        headers: this.buildHeaders(store),
        body: JSON.stringify({ amount: String(amount), reason }),
      });
      if (!resp.ok) throw new Error(`WooCommerce refund failed: ${resp.status}`);
      return resp.json();
    },
    async cancelOrder(store, orderId, reason) {
      const url = this.buildUrl(store, `orders/${orderId}`);
      const resp = await fetch(url, {
        method: 'PUT',
        headers: this.buildHeaders(store),
        body: JSON.stringify({ status: 'cancelled', customer_note: reason }),
      });
      if (!resp.ok) throw new Error(`WooCommerce cancel failed: ${resp.status}`);
      return resp.json();
    },
    async getInventory(store, productId) {
      const url = this.buildUrl(store, `products/${productId}`);
      const resp = await fetch(url, { headers: this.buildHeaders(store) });
      if (!resp.ok) throw new Error(`WooCommerce API ${resp.status}`);
      const data = await resp.json();
      if (data.variations && data.variations.length) {
        return data.variations.map(v => ({
          variantId: String(v.id), sku: v.sku || '', title: v.name || '',
          quantity: v.stock_quantity || 0, price: parseFloat(v.price),
          available: v.stock_status === 'instock',
        }));
      }
      return [{
        variantId: String(data.id), sku: data.sku || '', title: data.name,
        quantity: data.stock_quantity || 0, price: parseFloat(data.price),
        available: data.stock_status === 'instock',
      }];
    },
  },

  custom: {
    name: 'Custom API',
    buildHeaders(store) {
      const headers = { 'Content-Type': 'application/json', ...(store.credentials.headers || {}) };
      if (store.credentials.api_key) headers['Authorization'] = `Bearer ${store.credentials.api_key}`;
      return headers;
    },
    buildUrl(store, resource) {
      return `${store.domain}/api/${resource}`;
    },
    async fetchProducts(store) {
      const url = this.buildUrl(store, store.endpoints?.products || 'products');
      const resp = await fetch(url, { headers: this.buildHeaders(store) });
      if (!resp.ok) throw new Error(`Custom API ${resp.status}`);
      const data = await resp.json();
      const items = Array.isArray(data) ? data : (data.products || data.items || data.data || []);
      return items.map(p => normalizeProduct(p, 'custom'));
    },
    async fetchOrders(store, params = {}) {
      const url = this.buildUrl(store, store.endpoints?.orders || 'orders');
      const resp = await fetch(url, { headers: this.buildHeaders(store) });
      if (!resp.ok) throw new Error(`Custom API ${resp.status}`);
      const data = await resp.json();
      const items = Array.isArray(data) ? data : (data.orders || data.items || data.data || []);
      return items.map(o => normalizeOrder(o, 'custom'));
    },
    async fetchOrder(store, orderId) {
      const url = this.buildUrl(store, `${store.endpoints?.orders || 'orders'}/${orderId}`);
      const resp = await fetch(url, { headers: this.buildHeaders(store) });
      if (!resp.ok) throw new Error(`Custom API ${resp.status}`);
      return normalizeOrder(await resp.json(), 'custom');
    },
    async createRefund() { return { error: 'Refunds not supported via custom adapter — configure webhook' }; },
    async cancelOrder() { return { error: 'Cancellation not supported via custom adapter — configure webhook' }; },
    async getInventory() { return { error: 'Inventory not available via custom adapter' }; },
  },
};

// ── Truth Layer: Data Normalization ─────────────────────────────────────────

function normalizeProduct(raw, platform) {
  const p = {};
  switch (platform) {
    case 'shopify':
      p.id = String(raw.id);
      p.title = raw.title;
      p.description = raw.body_html?.replace(/<[^>]+>/g, '') || '';
      p.vendor = raw.vendor || '';
      p.type = raw.product_type || '';
      p.tags = raw.tags ? raw.tags.split(',').map(t => t.trim()) : [];
      p.status = raw.status || 'active';
      p.variants = (raw.variants || []).map(v => ({
        id: String(v.id), sku: v.sku, title: v.title,
        price: parseFloat(v.price), compareAtPrice: v.compare_at_price ? parseFloat(v.compare_at_price) : null,
        quantity: v.inventory_quantity, weight: v.weight, weightUnit: v.weight_unit,
        available: v.inventory_quantity > 0,
      }));
      p.images = (raw.images || []).map(i => ({ id: String(i.id), src: i.src, alt: i.alt }));
      p.minPrice = Math.min(...p.variants.map(v => v.price));
      p.maxPrice = Math.max(...p.variants.map(v => v.price));
      p.totalInventory = p.variants.reduce((s, v) => s + (v.quantity || 0), 0);
      break;
    case 'woocommerce':
      p.id = String(raw.id);
      p.title = raw.name;
      p.description = raw.short_description?.replace(/<[^>]+>/g, '') || raw.description?.replace(/<[^>]+>/g, '') || '';
      p.vendor = raw.brands?.[0] || '';
      p.type = raw.type || '';
      p.tags = (raw.tags || []).map(t => t.name);
      p.status = raw.status || 'publish';
      p.variants = (raw.variations || [{ id: raw.id, sku: raw.sku, name: raw.name, price: raw.price, stock_quantity: raw.stock_quantity, stock_status: raw.stock_status }]).map(v => ({
        id: String(v.id), sku: v.sku || '', title: v.name || raw.name,
        price: parseFloat(v.price || raw.price), compareAtPrice: v.regular_price ? parseFloat(v.regular_price) : null,
        quantity: v.stock_quantity || 0, weight: raw.weight ? parseFloat(raw.weight) : null, weightUnit: raw.weight_unit || 'kg',
        available: (v.stock_status || raw.stock_status) === 'instock',
      }));
      p.images = (raw.images || []).map(i => ({ id: String(i.id), src: i.src, alt: i.alt || '' }));
      p.minPrice = Math.min(...p.variants.map(v => v.price));
      p.maxPrice = Math.max(...p.variants.map(v => v.price));
      p.totalInventory = p.variants.reduce((s, v) => s + (v.quantity || 0), 0);
      break;
    default: // custom
      p.id = String(raw.id || raw._id || randomUUID().slice(0, 8));
      p.title = raw.title || raw.name || 'Unknown';
      p.description = raw.description || '';
      p.vendor = raw.vendor || raw.brand || '';
      p.type = raw.type || raw.category || '';
      p.tags = raw.tags || [];
      p.status = raw.status || 'active';
      p.variants = [{ id: p.id, sku: raw.sku || '', title: p.title, price: parseFloat(raw.price || 0), quantity: raw.stock || raw.quantity || 0, available: true }];
      p.images = raw.images || [];
      p.minPrice = parseFloat(raw.price || 0);
      p.maxPrice = parseFloat(raw.price || 0);
      p.totalInventory = raw.stock || raw.quantity || 0;
  }
  p.platform = platform;
  p.normalizedAt = now();
  return p;
}

function normalizeOrder(raw, platform) {
  const o = {};
  switch (platform) {
    case 'shopify':
      o.id = String(raw.id);
      o.orderNumber = raw.order_number || raw.name;
      o.email = raw.email;
      o.phone = raw.phone || '';
      o.status = raw.financial_status;
      o.fulfillmentStatus = raw.fulfillment_status || 'unfulfilled';
      o.total = parseFloat(raw.total_price);
      o.subtotal = parseFloat(raw.subtotal_price);
      o.tax = parseFloat(raw.total_tax);
      o.currency = raw.currency;
      o.items = (raw.line_items || []).map(li => ({
        id: String(li.id), productId: String(li.product_id), variantId: String(li.variant_id),
        title: li.title, quantity: li.quantity, price: parseFloat(li.price),
        sku: li.sku || '',
      }));
      o.shippingAddress = raw.shipping_address ? {
        name: `${raw.shipping_address.first_name} ${raw.shipping_address.last_name}`,
        address1: raw.shipping_address.address1, address2: raw.shipping_address.address2 || '',
        city: raw.shipping_address.city, province: raw.shipping_address.province,
        country: raw.shipping_address.country, zip: raw.shipping_address.zip,
      } : null;
      o.tracking = (raw.fulfillments || []).flatMap(f => (f.tracking_numbers || []).map((tn, i) => ({
        number: tn, url: f.tracking_urls?.[i] || '', company: f.tracking_company || '',
      })));
      o.createdAt = raw.created_at;
      o.updatedAt = raw.updated_at;
      o.cancelledAt = raw.cancelled_at;
      o.refunds = (raw.refunds || []).map(r => ({
        id: String(r.id), amount: r.transactions?.reduce((s, t) => s + parseFloat(t.amount), 0) || 0,
        reason: r.note || '', createdAt: r.created_at,
      }));
      break;
    case 'woocommerce':
      o.id = String(raw.id);
      o.orderNumber = String(raw.number || raw.id);
      o.email = raw.billing?.email || '';
      o.phone = raw.billing?.phone || '';
      o.status = raw.status;
      o.fulfillmentStatus = raw.status === 'completed' ? 'fulfilled' : 'unfulfilled';
      o.total = parseFloat(raw.total);
      o.subtotal = parseFloat(raw.subtotal || raw.total);
      o.tax = parseFloat(raw.total_tax || 0);
      o.currency = raw.currency;
      o.items = (raw.line_items || []).map(li => ({
        id: String(li.id), productId: String(li.product_id), variantId: String(li.variation_id || li.product_id),
        title: li.name, quantity: li.quantity, price: parseFloat(li.price),
        sku: li.sku || '',
      }));
      o.shippingAddress = raw.shipping ? {
        name: `${raw.shipping.first_name} ${raw.shipping.last_name}`,
        address1: raw.shipping.address_1, address2: raw.shipping.address_2 || '',
        city: raw.shipping.city, province: raw.shipping.state,
        country: raw.shipping.country, zip: raw.shipping.postcode,
      } : null;
      o.tracking = [];  // WooCommerce needs tracking plugin data
      o.createdAt = raw.date_created;
      o.updatedAt = raw.date_modified;
      o.cancelledAt = raw.status === 'cancelled' ? raw.date_modified : null;
      o.refunds = (raw.refunds || []).map(r => ({
        id: String(r.id), amount: Math.abs(parseFloat(r.total)),
        reason: r.reason || '', createdAt: r.date_created,
      }));
      break;
    default: // custom
      o.id = String(raw.id || raw._id || randomUUID().slice(0, 8));
      o.orderNumber = String(raw.order_number || raw.number || o.id);
      o.email = raw.email || raw.customer_email || '';
      o.phone = raw.phone || '';
      o.status = raw.status || 'pending';
      o.fulfillmentStatus = raw.fulfillment_status || 'unfulfilled';
      o.total = parseFloat(raw.total || 0);
      o.subtotal = parseFloat(raw.subtotal || raw.total || 0);
      o.tax = parseFloat(raw.tax || 0);
      o.currency = raw.currency || 'USD';
      o.items = raw.items || raw.line_items || [];
      o.shippingAddress = raw.shipping_address || raw.shipping || null;
      o.tracking = raw.tracking || [];
      o.createdAt = raw.created_at || now();
      o.updatedAt = raw.updated_at || now();
      o.cancelledAt = raw.cancelled_at || null;
      o.refunds = raw.refunds || [];
  }
  o.platform = platform;
  o.normalizedAt = now();
  return o;
}

// ════════════════════════════════════════════════════════════════════════════
// SECTION 2: STORE MANAGEMENT
// ════════════════════════════════════════════════════════════════════════════

export async function connectStore(user, config) {
  const stores = await loadJSON(storesPath(user), { stores: {} });
  const id = `store_${randomUUID().slice(0, 8)}`;
  const platform = config.platform?.toLowerCase();
  if (!PLATFORM_ADAPTERS[platform]) {
    throw new Error(`Unsupported platform "${platform}". Supported: ${Object.keys(PLATFORM_ADAPTERS).join(', ')}`);
  }
  stores.stores[id] = {
    id,
    name: config.name || `${PLATFORM_ADAPTERS[platform].name} Store`,
    platform,
    domain: config.domain,
    credentials: config.credentials || {},
    currency: config.currency || 'USD',
    endpoints: config.endpoints || {},
    connected: true,
    connectedAt: now(),
    lastSync: null,
    productCount: 0,
    orderCount: 0,
  };
  await saveJSON(storesPath(user), stores);
  await auditLog(user, 'store_connected', { storeId: id, platform, domain: config.domain }, 'Store connected');
  return { id, message: `${PLATFORM_ADAPTERS[platform].name} store "${config.name || config.domain}" connected. ID: ${id}` };
}

export async function listStores(user) {
  const stores = await loadJSON(storesPath(user), { stores: {} });
  const list = Object.values(stores.stores).map(s => ({
    id: s.id, name: s.name, platform: s.platform, domain: s.domain,
    connected: s.connected, lastSync: s.lastSync,
    productCount: s.productCount, orderCount: s.orderCount,
  }));
  return { stores: list, message: list.length ? `${list.length} store(s) connected.` : 'No stores connected yet.' };
}

export async function disconnectStore(user, storeId) {
  const stores = await loadJSON(storesPath(user), { stores: {} });
  if (!stores.stores[storeId]) throw new Error(`Store ${storeId} not found`);
  const name = stores.stores[storeId].name;
  delete stores.stores[storeId];
  await saveJSON(storesPath(user), stores);
  await auditLog(user, 'store_disconnected', { storeId }, `Store "${name}" disconnected`);
  return { message: `Store "${name}" disconnected.` };
}

async function getStore(user, storeId) {
  const stores = await loadJSON(storesPath(user), { stores: {} });
  const store = stores.stores[storeId];
  if (!store) throw new Error(`Store ${storeId} not found. Use commerce_list_stores to see available stores.`);
  return store;
}

function getAdapter(platform) {
  const adapter = PLATFORM_ADAPTERS[platform];
  if (!adapter) throw new Error(`No adapter for platform "${platform}"`);
  return adapter;
}

// ════════════════════════════════════════════════════════════════════════════
// SECTION 3: TRUTH LAYER — Deterministic Data Retrieval
// ════════════════════════════════════════════════════════════════════════════

export async function getProductTruth(user, storeId, productId) {
  const store = await getStore(user, storeId);
  const adapter = getAdapter(store.platform);

  // Check cache first
  const cache = await loadJSON(cachePath(user), { products: {} });
  const cacheKey = `${storeId}_${productId}`;
  const cached = cache.products[cacheKey];
  if (cached && (Date.now() - new Date(cached.normalizedAt).getTime()) < 5 * 60 * 1000) {
    return { product: cached, cached: true, message: 'Product truth retrieved from cache.' };
  }

  const inventory = await adapter.getInventory(store, productId);
  // For full product truth we'd also fetch the product itself
  const products = await adapter.fetchProducts(store);
  const product = products.find(p => p.id === String(productId));

  if (product) {
    // Merge live inventory into product truth
    product.variants = product.variants.map(v => {
      const live = inventory.find(i => i.variantId === v.id);
      if (live) { v.quantity = live.quantity; v.available = live.available; }
      return v;
    });
    product.totalInventory = product.variants.reduce((s, v) => s + (v.quantity || 0), 0);

    // Cache it
    cache.products[cacheKey] = product;
    await saveJSON(cachePath(user), cache);
  }

  await auditLog(user, 'truth_retrieved', { storeId, productId, type: 'product' }, 'Product truth fetched');
  return { product: product || null, cached: false, message: product ? `Product truth for "${product.title}" retrieved.` : 'Product not found.' };
}

export async function getOrderTruth(user, storeId, orderId) {
  const store = await getStore(user, storeId);
  const adapter = getAdapter(store.platform);
  const order = await adapter.fetchOrder(store, orderId);
  await auditLog(user, 'truth_retrieved', { storeId, orderId, type: 'order' }, 'Order truth fetched');
  return { order, message: `Order #${order.orderNumber} truth retrieved. Status: ${order.status}, Fulfillment: ${order.fulfillmentStatus}, Total: ${order.currency} ${order.total}` };
}

export async function getAvailabilityTruth(user, storeId, productId) {
  const store = await getStore(user, storeId);
  const adapter = getAdapter(store.platform);
  const inventory = await adapter.getInventory(store, productId);
  const totalStock = inventory.reduce((s, v) => s + (v.quantity || 0), 0);
  const anyAvailable = inventory.some(v => v.available);

  await auditLog(user, 'truth_retrieved', { storeId, productId, type: 'availability' }, 'Availability truth fetched');
  return {
    productId, storeId,
    variants: inventory,
    totalStock,
    available: anyAvailable,
    message: anyAvailable
      ? `Product ${productId} IS available. ${totalStock} units across ${inventory.length} variant(s).`
      : `Product ${productId} is OUT OF STOCK. 0 units available.`,
  };
}

export async function searchProducts(user, storeId, query) {
  const store = await getStore(user, storeId);
  const adapter = getAdapter(store.platform);
  const products = await adapter.fetchProducts(store);
  const q = query.toLowerCase();
  const results = products.filter(p =>
    p.title.toLowerCase().includes(q) ||
    p.description.toLowerCase().includes(q) ||
    p.tags.some(t => t.toLowerCase().includes(q)) ||
    p.type.toLowerCase().includes(q) ||
    p.vendor.toLowerCase().includes(q)
  );

  // Update store stats
  const stores = await loadJSON(storesPath(user), { stores: {} });
  if (stores.stores[storeId]) {
    stores.stores[storeId].productCount = products.length;
    stores.stores[storeId].lastSync = now();
    await saveJSON(storesPath(user), stores);
  }

  await auditLog(user, 'products_searched', { storeId, query, resultCount: results.length }, 'Product search executed');
  return {
    results: results.slice(0, 20),
    totalMatches: results.length,
    totalProducts: products.length,
    message: `Found ${results.length} product(s) matching "${query}" out of ${products.length} total.`,
  };
}

export async function getOrderStatus(user, storeId, orderId) {
  const { order } = await getOrderTruth(user, storeId, orderId);
  return {
    orderId: order.id,
    orderNumber: order.orderNumber,
    status: order.status,
    fulfillmentStatus: order.fulfillmentStatus,
    total: order.total,
    currency: order.currency,
    items: order.items.length,
    tracking: order.tracking,
    createdAt: order.createdAt,
    message: `Order #${order.orderNumber}: ${order.status} | ${order.fulfillmentStatus} | ${order.currency} ${order.total} | ${order.items.length} item(s)` +
      (order.tracking.length ? ` | Tracking: ${order.tracking.map(t => t.number).join(', ')}` : ''),
  };
}

export async function listOrders(user, storeId, status) {
  const store = await getStore(user, storeId);
  const adapter = getAdapter(store.platform);
  const orders = await adapter.fetchOrders(store, { status });

  // Update store stats
  const stores = await loadJSON(storesPath(user), { stores: {} });
  if (stores.stores[storeId]) {
    stores.stores[storeId].orderCount = orders.length;
    stores.stores[storeId].lastSync = now();
    await saveJSON(storesPath(user), stores);
  }

  return {
    orders: orders.slice(0, 50).map(o => ({
      id: o.id, orderNumber: o.orderNumber, status: o.status,
      fulfillmentStatus: o.fulfillmentStatus, total: o.total,
      currency: o.currency, email: o.email, createdAt: o.createdAt,
      itemCount: o.items.length,
    })),
    totalOrders: orders.length,
    message: `${orders.length} order(s) found${status ? ` with status "${status}"` : ''}.`,
  };
}

// ════════════════════════════════════════════════════════════════════════════
// SECTION 4: GOVERNANCE / POLICY ENGINE
// ════════════════════════════════════════════════════════════════════════════

/**
 * Policy types:
 *  - refund_limit:    Max refund amount without approval  { max_amount, require_approval_above }
 *  - return_window:   Days allowed for returns            { days, conditions[] }
 *  - discount_limit:  Max discount percentage             { max_percent, require_approval_above }
 *  - auto_cancel:     Auto-cancel conditions              { max_minutes_unfulfilled }
 *  - escalation:      When to escalate to human           { conditions[] }
 *  - approval_gate:   Require human approval              { actions[], threshold }
 */

export async function setPolicy(user, policyName, rules) {
  const policies = await loadJSON(policiesPath(user), { policies: {} });
  const id = `pol_${randomUUID().slice(0, 8)}`;
  policies.policies[policyName] = {
    id,
    name: policyName,
    rules,
    enabled: true,
    createdAt: now(),
    updatedAt: now(),
    version: (policies.policies[policyName]?.version || 0) + 1,
  };
  await saveJSON(policiesPath(user), policies);
  await auditLog(user, 'policy_set', { policyName, rules }, `Policy "${policyName}" created/updated`);
  return { id, message: `Policy "${policyName}" saved. Rules: ${JSON.stringify(rules)}` };
}

export async function listPolicies(user) {
  const policies = await loadJSON(policiesPath(user), { policies: {} });
  const list = Object.values(policies.policies);
  return {
    policies: list.map(p => ({
      name: p.name, enabled: p.enabled, version: p.version,
      rules: p.rules, updatedAt: p.updatedAt,
    })),
    message: list.length ? `${list.length} policy/policies configured.` : 'No policies configured yet.',
  };
}

export async function removePolicy(user, policyName) {
  const policies = await loadJSON(policiesPath(user), { policies: {} });
  if (!policies.policies[policyName]) throw new Error(`Policy "${policyName}" not found`);
  delete policies.policies[policyName];
  await saveJSON(policiesPath(user), policies);
  await auditLog(user, 'policy_removed', { policyName }, `Policy "${policyName}" removed`);
  return { message: `Policy "${policyName}" removed.` };
}

export async function evaluatePolicy(user, action, context) {
  const policies = await loadJSON(policiesPath(user), { policies: {} });
  const results = { allowed: true, gates: [], warnings: [], appliedPolicies: [] };

  for (const [name, policy] of Object.entries(policies.policies)) {
    if (!policy.enabled) continue;
    const rules = policy.rules;

    switch (name) {
      case 'refund_limit':
        if (action === 'refund' && context.amount) {
          if (rules.max_amount && context.amount > rules.max_amount) {
            results.allowed = false;
            results.gates.push({
              policy: name, reason: `Refund amount $${context.amount} exceeds max $${rules.max_amount}`,
              requiresApproval: true,
            });
          } else if (rules.require_approval_above && context.amount > rules.require_approval_above) {
            results.gates.push({
              policy: name, reason: `Refund $${context.amount} exceeds approval threshold $${rules.require_approval_above}`,
              requiresApproval: true,
            });
          }
          results.appliedPolicies.push(name);
        }
        break;

      case 'return_window':
        if (action === 'return' && context.orderDate) {
          const daysSince = Math.floor((Date.now() - new Date(context.orderDate).getTime()) / 86400000);
          if (rules.days && daysSince > rules.days) {
            results.allowed = false;
            results.gates.push({
              policy: name, reason: `Order is ${daysSince} days old. Return window is ${rules.days} days.`,
              requiresApproval: false,
            });
          }
          if (rules.conditions) {
            for (const cond of rules.conditions) {
              if (cond === 'unopened' && context.opened) {
                results.warnings.push({ policy: name, warning: 'Item has been opened — may not qualify for return.' });
              }
              if (cond === 'original_packaging' && !context.originalPackaging) {
                results.warnings.push({ policy: name, warning: 'Original packaging missing — may not qualify.' });
              }
            }
          }
          results.appliedPolicies.push(name);
        }
        break;

      case 'discount_limit':
        if (action === 'discount' && context.percent) {
          if (rules.max_percent && context.percent > rules.max_percent) {
            results.allowed = false;
            results.gates.push({
              policy: name, reason: `Discount ${context.percent}% exceeds max ${rules.max_percent}%`,
              requiresApproval: true,
            });
          }
          results.appliedPolicies.push(name);
        }
        break;

      case 'approval_gate':
        if (rules.actions && rules.actions.includes(action)) {
          if (!context.approved) {
            results.gates.push({
              policy: name, reason: `Action "${action}" requires explicit approval per policy.`,
              requiresApproval: true,
            });
          }
          results.appliedPolicies.push(name);
        }
        break;

      case 'escalation':
        if (rules.conditions) {
          for (const cond of rules.conditions) {
            if (cond === 'angry_customer' && context.sentiment === 'angry') {
              results.gates.push({
                policy: name, reason: 'Customer sentiment detected as angry — escalate to human.',
                requiresApproval: false, escalate: true,
              });
            }
            if (cond === 'high_value' && context.amount > (rules.threshold || 500)) {
              results.gates.push({
                policy: name, reason: `High-value action ($${context.amount}) — escalate to human.`,
                requiresApproval: true, escalate: true,
              });
            }
          }
          results.appliedPolicies.push(name);
        }
        break;
    }
  }

  const decision = results.allowed && results.gates.filter(g => g.requiresApproval).length === 0
    ? 'APPROVED' : results.gates.some(g => g.escalate) ? 'ESCALATE' : 'BLOCKED';

  await auditLog(user, 'policy_evaluated', {
    action, context, decision,
    appliedPolicies: results.appliedPolicies,
    gates: results.gates,
  }, `Policy evaluation: ${action} → ${decision}`);

  return {
    ...results,
    decision,
    message: `Policy evaluation for "${action}": ${decision}.` +
      (results.gates.length ? ` Gates: ${results.gates.map(g => g.reason).join('; ')}` : '') +
      (results.warnings.length ? ` Warnings: ${results.warnings.map(w => w.warning).join('; ')}` : ''),
  };
}

// ════════════════════════════════════════════════════════════════════════════
// SECTION 5: ACTION LAYER — Governed Commerce Operations
// ════════════════════════════════════════════════════════════════════════════

export async function processRefund(user, storeId, orderId, amount, reason) {
  // Step 1: Evaluate policy
  const { order } = await getOrderTruth(user, storeId, orderId);
  const evaluation = await evaluatePolicy(user, 'refund', {
    amount,
    orderTotal: order.total,
    orderDate: order.createdAt,
  });

  if (evaluation.decision === 'BLOCKED') {
    await auditLog(user, 'refund_blocked', { storeId, orderId, amount, reason, gates: evaluation.gates }, 'Refund blocked by policy');
    return {
      success: false,
      decision: 'BLOCKED',
      reason: evaluation.gates.map(g => g.reason).join('; '),
      message: `Refund BLOCKED: ${evaluation.gates.map(g => g.reason).join('; ')}`,
    };
  }

  if (evaluation.decision === 'ESCALATE') {
    await auditLog(user, 'refund_escalated', { storeId, orderId, amount, reason, gates: evaluation.gates }, 'Refund escalated to human');
    return {
      success: false,
      decision: 'ESCALATE',
      reason: evaluation.gates.map(g => g.reason).join('; '),
      message: `Refund ESCALATED to human: ${evaluation.gates.map(g => g.reason).join('; ')}`,
    };
  }

  // Step 2: Execute refund via platform adapter
  const store = await getStore(user, storeId);
  const adapter = getAdapter(store.platform);
  const result = await adapter.createRefund(store, orderId, amount, reason);

  await auditLog(user, 'refund_processed', {
    storeId, orderId, amount, reason,
    input: { orderId, amount, reason },
    decision: 'APPROVED',
    outcome: result,
  }, `Refund $${amount} processed for order #${order.orderNumber}`);

  return {
    success: true,
    decision: 'APPROVED',
    refund: result,
    message: `Refund of $${amount} processed for order #${order.orderNumber}. Reason: ${reason}`,
  };
}

export async function cancelOrder(user, storeId, orderId, reason) {
  const { order } = await getOrderTruth(user, storeId, orderId);

  const evaluation = await evaluatePolicy(user, 'cancel', {
    amount: order.total,
    orderDate: order.createdAt,
  });

  if (evaluation.decision === 'BLOCKED') {
    await auditLog(user, 'cancel_blocked', { storeId, orderId, reason, gates: evaluation.gates }, 'Cancel blocked by policy');
    return { success: false, decision: 'BLOCKED', message: `Cancel BLOCKED: ${evaluation.gates.map(g => g.reason).join('; ')}` };
  }

  const store = await getStore(user, storeId);
  const adapter = getAdapter(store.platform);
  const result = await adapter.cancelOrder(store, orderId, reason);

  await auditLog(user, 'order_cancelled', {
    storeId, orderId, reason,
    input: { orderId, reason },
    decision: 'APPROVED',
    outcome: result,
  }, `Order #${order.orderNumber} cancelled`);

  return {
    success: true,
    decision: 'APPROVED',
    result,
    message: `Order #${order.orderNumber} cancelled. Reason: ${reason}`,
  };
}

export async function createReturn(user, storeId, orderId, items, reason) {
  const { order } = await getOrderTruth(user, storeId, orderId);

  const evaluation = await evaluatePolicy(user, 'return', {
    orderDate: order.createdAt,
    amount: order.total,
    items,
  });

  if (evaluation.decision === 'BLOCKED') {
    return { success: false, decision: 'BLOCKED', message: `Return BLOCKED: ${evaluation.gates.map(g => g.reason).join('; ')}` };
  }

  // Create RMA record
  const rmaId = `RMA_${randomUUID().slice(0, 8).toUpperCase()}`;
  const rma = {
    id: rmaId,
    storeId,
    orderId,
    orderNumber: order.orderNumber,
    items: items || order.items,
    reason,
    status: evaluation.decision === 'ESCALATE' ? 'pending_approval' : 'approved',
    createdAt: now(),
    decision: evaluation.decision,
    warnings: evaluation.warnings,
  };

  // Store the RMA
  const workflows = await loadJSON(workflowsPath(user), { rmas: {} });
  workflows.rmas = workflows.rmas || {};
  workflows.rmas[rmaId] = rma;
  await saveJSON(workflowsPath(user), workflows);

  await auditLog(user, 'return_created', {
    storeId, orderId, rmaId, items, reason,
    decision: evaluation.decision,
  }, `Return ${rmaId} created for order #${order.orderNumber}`);

  return {
    success: true,
    rma,
    message: `Return ${rmaId} created for order #${order.orderNumber}. Status: ${rma.status}` +
      (evaluation.warnings.length ? `. Warnings: ${evaluation.warnings.map(w => w.warning).join('; ')}` : ''),
  };
}

export async function escalateToHuman(user, context) {
  const ticket = {
    id: `ESC_${randomUUID().slice(0, 8).toUpperCase()}`,
    type: 'escalation',
    reason: context.reason || 'Agent requested human intervention',
    customerEmail: context.email || '',
    customerPhone: context.phone || '',
    orderId: context.orderId || null,
    storeId: context.storeId || null,
    summary: context.summary || '',
    sentiment: context.sentiment || 'unknown',
    priority: context.priority || 'normal',
    createdAt: now(),
    status: 'open',
  };

  const workflows = await loadJSON(workflowsPath(user), { escalations: {} });
  workflows.escalations = workflows.escalations || {};
  workflows.escalations[ticket.id] = ticket;
  await saveJSON(workflowsPath(user), workflows);

  await auditLog(user, 'escalated_to_human', ticket, `Escalation ${ticket.id} created`);

  return {
    ticket,
    message: `Escalation ${ticket.id} created. Priority: ${ticket.priority}. Reason: ${ticket.reason}`,
  };
}

// ════════════════════════════════════════════════════════════════════════════
// SECTION 6: AUDIT TRAIL
// ════════════════════════════════════════════════════════════════════════════

async function auditLog(user, action, data, summary) {
  const log = await loadJSON(auditPath(user), { entries: [] });
  log.entries.push({
    id: `aud_${randomUUID().slice(0, 8)}`,
    action,
    data,
    summary,
    timestamp: now(),
    user,
  });
  // Keep last 1000 entries
  if (log.entries.length > 1000) log.entries = log.entries.slice(-1000);
  await saveJSON(auditPath(user), log);
}

export async function getAuditLog(user, filters = {}) {
  const log = await loadJSON(auditPath(user), { entries: [] });
  let entries = log.entries;

  if (filters.action) entries = entries.filter(e => e.action === filters.action);
  if (filters.storeId) entries = entries.filter(e => e.data?.storeId === filters.storeId);
  if (filters.orderId) entries = entries.filter(e => e.data?.orderId === filters.orderId);
  if (filters.since) entries = entries.filter(e => new Date(e.timestamp) >= new Date(filters.since));
  if (filters.limit) entries = entries.slice(-filters.limit);
  else entries = entries.slice(-50);

  return {
    entries,
    totalEntries: log.entries.length,
    message: `${entries.length} audit entries returned (${log.entries.length} total).`,
  };
}

// ════════════════════════════════════════════════════════════════════════════
// SECTION 7: WORKFLOW TEMPLATES
// ════════════════════════════════════════════════════════════════════════════

const WORKFLOW_TEMPLATES = {
  order_status_check: {
    name: 'Order Status Check',
    description: 'Customer asks about their order status — retrieve truth and respond deterministically.',
    steps: ['getOrderTruth', 'formatResponse'],
    requiredFields: ['storeId', 'orderId'],
  },
  return_request: {
    name: 'Return Request',
    description: 'Customer wants to return an item — check policy, create RMA, provide shipping label.',
    steps: ['getOrderTruth', 'evaluatePolicy:return', 'createReturn', 'notifyCustomer'],
    requiredFields: ['storeId', 'orderId', 'items', 'reason'],
  },
  refund_request: {
    name: 'Refund Request',
    description: 'Customer requests a refund — check policy, process if approved, escalate if needed.',
    steps: ['getOrderTruth', 'evaluatePolicy:refund', 'processRefund', 'notifyCustomer'],
    requiredFields: ['storeId', 'orderId', 'amount', 'reason'],
  },
  product_inquiry: {
    name: 'Product Inquiry',
    description: 'Customer asks about a product — retrieve truth, check availability, provide info.',
    steps: ['searchProducts', 'getProductTruth', 'getAvailabilityTruth', 'formatResponse'],
    requiredFields: ['storeId', 'query'],
  },
  cancel_order: {
    name: 'Cancel Order',
    description: 'Customer wants to cancel — check if eligible, cancel via platform, confirm.',
    steps: ['getOrderTruth', 'evaluatePolicy:cancel', 'cancelOrder', 'notifyCustomer'],
    requiredFields: ['storeId', 'orderId', 'reason'],
  },
  shipping_inquiry: {
    name: 'Shipping Inquiry',
    description: 'Customer asks about shipping — retrieve tracking, provide estimated delivery.',
    steps: ['getOrderTruth', 'extractTracking', 'formatResponse'],
    requiredFields: ['storeId', 'orderId'],
  },
};

export async function listWorkflowTemplates() {
  const templates = Object.entries(WORKFLOW_TEMPLATES).map(([key, t]) => ({
    key,
    name: t.name,
    description: t.description,
    steps: t.steps,
    requiredFields: t.requiredFields,
  }));
  return { templates, message: `${templates.length} workflow template(s) available.` };
}

export async function executeWorkflow(user, templateKey, params) {
  const template = WORKFLOW_TEMPLATES[templateKey];
  if (!template) throw new Error(`Unknown workflow template "${templateKey}". Use commerce_list_workflows to see available templates.`);

  // Validate required fields
  const missing = template.requiredFields.filter(f => !params[f]);
  if (missing.length) throw new Error(`Missing required fields: ${missing.join(', ')}`);

  const results = [];
  const startTime = Date.now();

  for (const step of template.steps) {
    try {
      let result;
      switch (step) {
        case 'getOrderTruth':
          result = await getOrderTruth(user, params.storeId, params.orderId);
          break;
        case 'getProductTruth':
          result = await getProductTruth(user, params.storeId, params.productId);
          break;
        case 'getAvailabilityTruth':
          result = await getAvailabilityTruth(user, params.storeId, params.productId);
          break;
        case 'searchProducts':
          result = await searchProducts(user, params.storeId, params.query);
          break;
        case 'evaluatePolicy:return':
          result = await evaluatePolicy(user, 'return', params);
          if (result.decision === 'BLOCKED') return { success: false, step, result, message: `Workflow stopped: ${result.message}` };
          break;
        case 'evaluatePolicy:refund':
          result = await evaluatePolicy(user, 'refund', params);
          if (result.decision === 'BLOCKED') return { success: false, step, result, message: `Workflow stopped: ${result.message}` };
          break;
        case 'evaluatePolicy:cancel':
          result = await evaluatePolicy(user, 'cancel', params);
          if (result.decision === 'BLOCKED') return { success: false, step, result, message: `Workflow stopped: ${result.message}` };
          break;
        case 'processRefund':
          result = await processRefund(user, params.storeId, params.orderId, params.amount, params.reason);
          break;
        case 'createReturn':
          result = await createReturn(user, params.storeId, params.orderId, params.items, params.reason);
          break;
        case 'cancelOrder':
          result = await cancelOrder(user, params.storeId, params.orderId, params.reason);
          break;
        case 'extractTracking': {
          const orderResult = results.find(r => r.step === 'getOrderTruth');
          const order = orderResult?.result?.order;
          result = order?.tracking?.length
            ? { tracking: order.tracking, message: `Tracking: ${order.tracking.map(t => `${t.company}: ${t.number}`).join(', ')}` }
            : { tracking: [], message: 'No tracking information available yet.' };
          break;
        }
        case 'formatResponse':
        case 'notifyCustomer':
          result = { message: `Step "${step}" — response formatted for customer.` };
          break;
        default:
          result = { message: `Unknown step "${step}" skipped.` };
      }
      results.push({ step, success: true, result });
    } catch (err) {
      results.push({ step, success: false, error: err.message });
    }
  }

  const elapsed = Date.now() - startTime;

  await auditLog(user, 'workflow_executed', {
    template: templateKey, params, results, elapsed,
  }, `Workflow "${template.name}" executed in ${elapsed}ms`);

  return {
    success: results.every(r => r.success),
    template: template.name,
    steps: results,
    elapsed,
    message: `Workflow "${template.name}" completed in ${elapsed}ms. ${results.filter(r => r.success).length}/${results.length} steps succeeded.`,
  };
}

// ════════════════════════════════════════════════════════════════════════════
// SECTION 8: COMMERCE ANALYTICS
// ════════════════════════════════════════════════════════════════════════════

export async function getCommerceAnalytics(user, storeId) {
  const log = await loadJSON(auditPath(user), { entries: [] });
  const entries = storeId
    ? log.entries.filter(e => e.data?.storeId === storeId)
    : log.entries;

  const actionCounts = {};
  const policyDecisions = { APPROVED: 0, BLOCKED: 0, ESCALATE: 0 };
  const refundTotal = { count: 0, amount: 0 };
  const returnTotal = { count: 0 };
  let firstEntry = null, lastEntry = null;

  for (const e of entries) {
    actionCounts[e.action] = (actionCounts[e.action] || 0) + 1;
    if (e.data?.decision) policyDecisions[e.data.decision] = (policyDecisions[e.data.decision] || 0) + 1;
    if (e.action === 'refund_processed') { refundTotal.count++; refundTotal.amount += (e.data?.amount || 0); }
    if (e.action === 'return_created') returnTotal.count++;
    if (!firstEntry) firstEntry = e.timestamp;
    lastEntry = e.timestamp;
  }

  return {
    totalEvents: entries.length,
    dateRange: { from: firstEntry, to: lastEntry },
    actionCounts,
    policyDecisions,
    refunds: refundTotal,
    returns: returnTotal,
    message: `Commerce analytics: ${entries.length} events. Refunds: ${refundTotal.count} ($${refundTotal.amount}). Returns: ${returnTotal.count}. Policy blocks: ${policyDecisions.BLOCKED}.`,
  };
}

// ════════════════════════════════════════════════════════════════════════════
// SECTION 9: SHIPPING TRUTH
// ════════════════════════════════════════════════════════════════════════════

export async function getShippingTruth(user, storeId, orderId) {
  const { order } = await getOrderTruth(user, storeId, orderId);
  return {
    orderId: order.id,
    orderNumber: order.orderNumber,
    fulfillmentStatus: order.fulfillmentStatus,
    shippingAddress: order.shippingAddress,
    tracking: order.tracking,
    message: order.tracking.length
      ? `Order #${order.orderNumber} tracking: ${order.tracking.map(t => `${t.company}: ${t.number}` + (t.url ? ` (${t.url})` : '')).join(', ')}`
      : `Order #${order.orderNumber} has no tracking information yet. Fulfillment: ${order.fulfillmentStatus}`,
  };
}

// ════════════════════════════════════════════════════════════════════════════
// SECTION 10: POLICY TRUTH
// ════════════════════════════════════════════════════════════════════════════

export async function getPolicyTruth(user, policyName) {
  const policies = await loadJSON(policiesPath(user), { policies: {} });
  if (policyName) {
    const p = policies.policies[policyName];
    if (!p) return { found: false, message: `Policy "${policyName}" not found.` };
    return {
      found: true,
      policy: p,
      message: `Policy "${policyName}" (v${p.version}): ${JSON.stringify(p.rules)}`,
    };
  }
  // Return all policies as structured truth
  const all = Object.values(policies.policies);
  return {
    policies: all,
    count: all.length,
    message: all.length
      ? `${all.length} policies: ${all.map(p => `${p.name} (v${p.version})`).join(', ')}`
      : 'No policies configured.',
  };
}
