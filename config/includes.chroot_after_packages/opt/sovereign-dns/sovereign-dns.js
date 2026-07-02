/**
 * Sovereign DNS Resolver — GoSiteMe
 * ═══════════════════════════════════
 * The address book of the Sovereign Web.
 * 
 * Resolves custom TLDs (.alfred, .sovereign, .mesh, .veil, etc.)
 * from the sovereign_domains database table.
 * 
 * Listens on UDP 5354 (non-privileged) — upstream proxy or Alfred
 * Browser/Linux resolver forwards sovereign TLD queries here.
 * 
 * Port: 5354 (UDP + TCP)
 * API:  http://127.0.0.1:5355 (HTTP lookup API for browsers/apps)
 * Gateway: http://127.0.0.1:5356/_/mysite.alfred/ (local reverse proxy for browsers)
 */

const dns2 = require('dns2');
const { Packet } = dns2;
const mysql = require('mysql2/promise');
const http = require('http');
const https = require('https');

// === Configuration ===
const DNS_PORT = 5354;
const API_PORT = 5355;
const GATEWAY_PORT = 5356;
const DB_SOCKET = '/run/mysql/mysql.sock';
const CACHE_TTL = 300; // 5 minutes
const UPSTREAM_DNS = '1.1.1.1'; // For non-sovereign queries

const LOCAL_IPS = new Set(['127.0.0.1', '::1', '::ffff:127.0.0.1']);
const HOP_BY_HOP_HEADERS = new Set([
    'connection',
    'keep-alive',
    'proxy-authenticate',
    'proxy-authorization',
    'te',
    'trailer',
    'transfer-encoding',
    'upgrade',
]);
const UPGRADE_PROXY_STRIP_HEADERS = new Set([
    'proxy-authenticate',
    'proxy-authorization',
    'te',
    'trailer',
    'transfer-encoding',
]);

// === In-memory cache ===
const cache = new Map();
const tldCache = { tlds: null, lastRefresh: 0 };

// === Database connection pool ===
let pool;

function getPool() {
    if (!pool) {
        // Read DB credentials from ~/.my.cnf compatible config
        const fs = require('fs');
        const mycnf = fs.readFileSync(require('os').homedir() + '/.my.cnf', 'utf8');
        const user = mycnf.match(/user\s*=\s*(.+)/)?.[1]?.trim() || 'root_whmcs';
        const rawPw = mycnf.match(/password\s*=\s*(.+)/)?.[1]?.trim() || '';
        // Strip surrounding quotes if present
        const password = rawPw.replace(/^["']|["']$/g, '');

        pool = mysql.createPool({
            socketPath: DB_SOCKET,
            user,
            password,
            database: 'root_whmcs',
            waitForConnections: true,
            connectionLimit: 5,
            queueLimit: 0,
            enableKeepAlive: true,
        });
    }
    return pool;
}

// === Load sovereign TLDs ===
async function loadSovereignTLDs() {
    const now = Date.now();
    if (tldCache.tlds && (now - tldCache.lastRefresh) < CACHE_TTL * 1000) {
        return tldCache.tlds;
    }
    const db = getPool();
    const [rows] = await db.query(
        "SELECT tld FROM sovereign_tlds WHERE status IN ('active', 'reserved')"
    );
    tldCache.tlds = new Set(rows.map(r => r.tld.toLowerCase()));
    tldCache.lastRefresh = now;
    console.log(`[SovDNS] Loaded ${tldCache.tlds.size} sovereign TLDs: ${[...tldCache.tlds].join(', ')}`);
    return tldCache.tlds;
}

// === Check if a domain is sovereign ===
function extractSovereignTLD(name) {
    // name comes in as "mysite.alfred." (trailing dot from DNS)
    const clean = name.replace(/\.$/, '').toLowerCase();
    const parts = clean.split('.');
    if (parts.length < 2) return null;
    const tld = parts[parts.length - 1];
    return { tld, domain: clean, subdomain: parts.slice(0, -1).join('.') };
}

function isLocalRequest(req) {
    return LOCAL_IPS.has(req.socket.remoteAddress);
}

function buildGatewayPath(domain, requestPath = '/') {
    const normalizedPath = requestPath.startsWith('/') ? requestPath : `/${requestPath}`;
    return `/_/${encodeURIComponent(domain)}${normalizedPath}`;
}

function buildGatewayUrl(domain, requestPath = '/') {
    return `http://127.0.0.1:${GATEWAY_PORT}${buildGatewayPath(domain, requestPath)}`;
}

function getGatewayRequestPath(urlPathname, urlSearch = '') {
    const pathParts = urlPathname.split('/');
    const requestPath = `/${pathParts.slice(3).join('/')}`;
    return `${requestPath === '/' ? '/' : requestPath}${urlSearch}`;
}

function escapeRegExp(value) {
    return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function injectBaseTag(html, domain) {
    const baseHref = `${buildGatewayUrl(domain, '/')}`;
    const baseTag = `<base href="${baseHref}"><meta name="alfred-sovereign-domain" content="${domain}">`;

    if (/<head[^>]*>/i.test(html)) {
        return html.replace(/<head[^>]*>/i, match => `${match}${baseTag}`);
    }

    return `${baseTag}${html}`;
}

function rewriteHtmlForGateway(html, domain) {
    const escapedDomain = escapeRegExp(domain);
    const absolutePattern = new RegExp(`https?:\\/\\/${escapedDomain}`, 'gi');
    const schemeLessPattern = new RegExp(`\\/\\/${escapedDomain}`, 'gi');

    return injectBaseTag(
        html
            .replace(absolutePattern, buildGatewayUrl(domain, '/').replace(/\/$/, ''))
            .replace(schemeLessPattern, buildGatewayUrl(domain, '/').replace(/^http:/, '').replace(/\/$/, '')),
        domain
    );
}

function rewriteLocationHeader(location, domain) {
    if (!location) {
        return location;
    }

    if (location.startsWith('/')) {
        return buildGatewayUrl(domain, location);
    }

    try {
        const parsed = new URL(location);
        if (parsed.hostname === domain) {
            return buildGatewayUrl(domain, `${parsed.pathname}${parsed.search}`);
        }
    } catch (_error) {
        return location;
    }

    return location;
}

function rewriteRequestHeaderUrl(headerValue, domain, target) {
    if (!headerValue) {
        return headerValue;
    }

    try {
        const parsed = new URL(headerValue);
        const targetOrigin = `${target.protocol}//${target.hostHeader}`;
        if (parsed.hostname === domain) {
            return `${targetOrigin}${parsed.pathname}${parsed.search}${parsed.hash}`;
        }

        if (parsed.hostname === '127.0.0.1' && parsed.port === String(GATEWAY_PORT) && parsed.pathname.startsWith(`/_/${encodeURIComponent(domain)}`)) {
            return `${targetOrigin}${getGatewayRequestPath(parsed.pathname, parsed.search)}${parsed.hash}`;
        }
    } catch (_error) {
        return headerValue;
    }

    return headerValue;
}

function rewriteSetCookieHeader(cookieHeader, domain) {
    if (!cookieHeader) {
        return cookieHeader;
    }

    const parts = cookieHeader.split(';').map(part => part.trim()).filter(Boolean);
    if (parts.length === 0) {
        return cookieHeader;
    }

    const rewritten = [];
    let sawPath = false;

    for (const part of parts) {
        const separatorIndex = part.indexOf('=');
        const attributeName = separatorIndex >= 0 ? part.slice(0, separatorIndex).trim().toLowerCase() : part.toLowerCase();
        const attributeValue = separatorIndex >= 0 ? part.slice(separatorIndex + 1).trim() : '';

        if (attributeName === 'domain') {
            continue;
        }

        if (attributeName === 'path') {
            const originalPath = attributeValue || '/';
            rewritten.push(`Path=${buildGatewayPath(domain, originalPath)}`);
            sawPath = true;
            continue;
        }

        rewritten.push(part);
    }

    if (!sawPath) {
        rewritten.push(`Path=${buildGatewayPath(domain, '/')}`);
    }

    return rewritten.join('; ');
}

function getProxyTarget(record, domain, requestPath) {
    if (record.dns_cname) {
        return {
            protocol: 'https:',
            hostname: record.dns_cname,
            hostHeader: record.dns_cname,
            path: requestPath,
        };
    }

    if (record.dns_a) {
        return {
            protocol: 'http:',
            hostname: record.dns_a,
            hostHeader: domain,
            path: requestPath,
        };
    }

    if (record.dns_aaaa) {
        return {
            protocol: 'http:',
            hostname: record.dns_aaaa,
            hostHeader: domain,
            path: requestPath,
        };
    }

    return null;
}

async function resolveGatewayTarget(urlValue) {
    const url = new URL(urlValue, `http://127.0.0.1:${GATEWAY_PORT}`);
    if (!url.pathname.startsWith('/_/')) {
        return { errorCode: 404, errorMessage: 'Not found' };
    }

    const pathParts = url.pathname.split('/');
    const domain = decodeURIComponent(pathParts[2] || '').toLowerCase();
    const upstreamPath = getGatewayRequestPath(url.pathname, url.search) || '/';

    if (!domain) {
        return { errorCode: 400, errorMessage: 'Missing sovereign domain' };
    }

    const parsed = extractSovereignTLD(domain);
    if (!parsed) {
        return { errorCode: 400, errorMessage: 'Invalid sovereign domain' };
    }

    const tlds = await loadSovereignTLDs();
    if (!tlds.has(parsed.tld)) {
        return { errorCode: 404, errorMessage: 'Unknown sovereign TLD' };
    }

    const record = await resolveSovereign(domain);
    if (!record) {
        return { errorCode: 404, errorMessage: 'Sovereign domain not registered' };
    }

    const target = getProxyTarget(record, domain, upstreamPath);
    if (!target) {
        return { errorCode: 502, errorMessage: 'Domain has no reachable web target' };
    }

    return { domain, target };
}

async function proxySovereignRequest(req, res) {
    if (!isLocalRequest(req)) {
        res.writeHead(403, { 'Content-Type': 'text/plain; charset=utf-8' });
        res.end('Local access only');
        return;
    }

    const context = await resolveGatewayTarget(req.url);
    if (context.errorCode) {
        res.writeHead(context.errorCode, { 'Content-Type': 'text/plain; charset=utf-8' });
        res.end(context.errorMessage);
        return;
    }

    const { domain, target } = context;

    const headers = { ...req.headers };
    for (const header of Object.keys(headers)) {
        if (HOP_BY_HOP_HEADERS.has(header.toLowerCase())) {
            delete headers[header];
        }
    }
    headers.host = target.hostHeader;
    headers['accept-encoding'] = 'identity';
    headers['x-forwarded-host'] = domain;
    headers['x-forwarded-proto'] = 'http';
    headers.origin = rewriteRequestHeaderUrl(headers.origin, domain, target);
    headers.referer = rewriteRequestHeaderUrl(headers.referer, domain, target);

    const transport = target.protocol === 'https:' ? https : http;
    const upstreamReq = transport.request({
        protocol: target.protocol,
        hostname: target.hostname,
        method: req.method,
        path: target.path,
        headers,
    }, upstreamRes => {
        const responseHeaders = { ...upstreamRes.headers };
        for (const header of Object.keys(responseHeaders)) {
            if (HOP_BY_HOP_HEADERS.has(header.toLowerCase())) {
                delete responseHeaders[header];
            }
        }

        if (responseHeaders.location) {
            responseHeaders.location = rewriteLocationHeader(responseHeaders.location, domain);
        }

        if (responseHeaders['set-cookie']) {
            const cookies = Array.isArray(responseHeaders['set-cookie'])
                ? responseHeaders['set-cookie']
                : [responseHeaders['set-cookie']];
            responseHeaders['set-cookie'] = cookies.map(cookie => rewriteSetCookieHeader(cookie, domain));
        }

        const contentType = String(responseHeaders['content-type'] || '');
        if (/text\/html|application\/xhtml\+xml/i.test(contentType)) {
            const chunks = [];
            upstreamRes.on('data', chunk => chunks.push(Buffer.from(chunk)));
            upstreamRes.on('end', () => {
                const originalBody = Buffer.concat(chunks).toString('utf8');
                const rewrittenBody = rewriteHtmlForGateway(originalBody, domain);
                delete responseHeaders['content-length'];
                res.writeHead(upstreamRes.statusCode || 200, responseHeaders);
                res.end(rewrittenBody);
            });
            return;
        }

        res.writeHead(upstreamRes.statusCode || 200, responseHeaders);
        upstreamRes.pipe(res);
    });

    upstreamReq.on('error', err => {
        res.writeHead(502, { 'Content-Type': 'text/plain; charset=utf-8' });
        res.end(`Gateway error: ${err.message}`);
    });

    req.pipe(upstreamReq);
}

async function proxySovereignUpgrade(req, socket, head) {
    if (!isLocalRequest(req)) {
        socket.write('HTTP/1.1 403 Forbidden\r\nContent-Type: text/plain; charset=utf-8\r\nConnection: close\r\n\r\nLocal access only');
        socket.destroy();
        return;
    }

    const context = await resolveGatewayTarget(req.url);
    if (context.errorCode) {
        socket.write(`HTTP/1.1 ${context.errorCode} Gateway Error\r\nContent-Type: text/plain; charset=utf-8\r\nConnection: close\r\n\r\n${context.errorMessage}`);
        socket.destroy();
        return;
    }

    const { domain, target } = context;
    const headers = { ...req.headers };
    for (const header of Object.keys(headers)) {
        if (UPGRADE_PROXY_STRIP_HEADERS.has(header.toLowerCase())) {
            delete headers[header];
        }
    }

    headers.host = target.hostHeader;
    headers.origin = rewriteRequestHeaderUrl(headers.origin, domain, target);
    headers.referer = rewriteRequestHeaderUrl(headers.referer, domain, target);
    headers.connection = 'Upgrade';
    headers.upgrade = headers.upgrade || 'websocket';
    headers['x-forwarded-host'] = domain;
    headers['x-forwarded-proto'] = target.protocol === 'https:' ? 'https' : 'http';

    const transport = target.protocol === 'https:' ? https : http;
    const upstreamReq = transport.request({
        protocol: target.protocol,
        hostname: target.hostname,
        method: req.method,
        path: target.path,
        headers,
    });

    upstreamReq.on('upgrade', (upstreamRes, upstreamSocket, upstreamHead) => {
        const rawHeaders = [];
        for (let index = 0; index < upstreamRes.rawHeaders.length; index += 2) {
            const headerName = upstreamRes.rawHeaders[index];
            const headerValue = upstreamRes.rawHeaders[index + 1];
            if (!headerName) {
                continue;
            }

            if (headerName.toLowerCase() === 'set-cookie') {
                rawHeaders.push(headerName, rewriteSetCookieHeader(headerValue, domain));
                continue;
            }

            rawHeaders.push(headerName, headerValue);
        }

        let responseHead = `HTTP/${upstreamRes.httpVersion} ${upstreamRes.statusCode} ${upstreamRes.statusMessage}\r\n`;
        for (let index = 0; index < rawHeaders.length; index += 2) {
            responseHead += `${rawHeaders[index]}: ${rawHeaders[index + 1]}\r\n`;
        }
        responseHead += '\r\n';

        socket.write(responseHead);
        if (upstreamHead && upstreamHead.length > 0) {
            socket.write(upstreamHead);
        }
        if (head && head.length > 0) {
            upstreamSocket.write(head);
        }

        upstreamSocket.pipe(socket);
        socket.pipe(upstreamSocket);

        upstreamSocket.on('error', () => socket.destroy());
        socket.on('error', () => upstreamSocket.destroy());
    });

    upstreamReq.on('response', (upstreamRes) => {
        socket.write(`HTTP/1.1 ${upstreamRes.statusCode || 502} ${upstreamRes.statusMessage || 'Upstream Error'}\r\nConnection: close\r\n\r\n`);
        upstreamRes.resume();
        socket.destroy();
    });

    upstreamReq.on('error', (err) => {
        socket.write(`HTTP/1.1 502 Bad Gateway\r\nContent-Type: text/plain; charset=utf-8\r\nConnection: close\r\n\r\nGateway upgrade error: ${err.message}`);
        socket.destroy();
    });

    upstreamReq.end();
}

// === Resolve sovereign domain from DB ===
async function resolveSovereign(domainName) {
    // Check cache first
    const cacheKey = domainName.toLowerCase();
    const cached = cache.get(cacheKey);
    if (cached && (Date.now() - cached.time) < CACHE_TTL * 1000) {
        return cached.data;
    }

    const db = getPool();
    const [rows] = await db.query(
        `SELECT d.domain_name, d.dns_a, d.dns_aaaa, d.dns_cname, d.dns_mx,
                d.status, t.tld
         FROM sovereign_domains d
         JOIN sovereign_tlds t ON d.tld_id = t.id
         WHERE d.domain_name = ? AND d.status = 'active'`,
        [cacheKey]
    );

    const result = rows.length > 0 ? rows[0] : null;
    cache.set(cacheKey, { data: result, time: Date.now() });
    return result;
}

// === Resolve additional DNS records ===
async function resolveRecords(domainId, recordType) {
    const db = getPool();
    const [rows] = await db.query(
        `SELECT name, value, ttl, priority FROM sovereign_dns_records
         WHERE domain_id = ? AND record_type = ?`,
        [domainId, recordType]
    );
    return rows;
}

// === DNS Server ===
const server = dns2.createServer({
    udp: true,
    handle: async (request, send, rinfo) => {
        const response = Packet.createResponseFromRequest(request);

        for (const question of request.questions) {
            const { name, type } = question;
            const parsed = extractSovereignTLD(name);

            if (!parsed) continue;

            // Check if this TLD is sovereign
            const tlds = await loadSovereignTLDs();
            if (!tlds.has(parsed.tld)) {
                // Not a sovereign TLD — could forward to upstream, but for now just NXDOMAIN
                continue;
            }

            const record = await resolveSovereign(parsed.domain);

            if (!record) {
                // Domain not registered in sovereign registry
                continue;
            }

            // Build response based on query type
            switch (type) {
                case Packet.TYPE.A:
                    if (record.dns_a) {
                        response.answers.push({
                            name,
                            type: Packet.TYPE.A,
                            class: Packet.CLASS.IN,
                            ttl: CACHE_TTL,
                            address: record.dns_a,
                        });
                    }
                    break;

                case Packet.TYPE.AAAA:
                    if (record.dns_aaaa) {
                        response.answers.push({
                            name,
                            type: Packet.TYPE.AAAA,
                            class: Packet.CLASS.IN,
                            ttl: CACHE_TTL,
                            address: record.dns_aaaa,
                        });
                    }
                    break;

                case Packet.TYPE.CNAME:
                    if (record.dns_cname) {
                        response.answers.push({
                            name,
                            type: Packet.TYPE.CNAME,
                            class: Packet.CLASS.IN,
                            ttl: CACHE_TTL,
                            domain: record.dns_cname,
                        });
                    }
                    break;

                case Packet.TYPE.MX:
                    if (record.dns_mx) {
                        response.answers.push({
                            name,
                            type: Packet.TYPE.MX,
                            class: Packet.CLASS.IN,
                            ttl: CACHE_TTL,
                            exchange: record.dns_mx,
                            priority: 10,
                        });
                    }
                    break;

                default:
                    // For unsupported types, try A record as fallback
                    if (record.dns_a) {
                        response.answers.push({
                            name,
                            type: Packet.TYPE.A,
                            class: Packet.CLASS.IN,
                            ttl: CACHE_TTL,
                            address: record.dns_a,
                        });
                    }
            }
        }

        send(response);
    },
});

// === HTTP API for browsers/apps ===
const api = http.createServer(async (req, res) => {
    // Only allow local connections
    if (!isLocalRequest(req)) {
        res.writeHead(403, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ error: 'Local access only' }));
        return;
    }

    res.setHeader('Content-Type', 'application/json');

    const url = new URL(req.url, `http://${req.headers.host}`);

    // GET /resolve?domain=mysite.alfred
    if (url.pathname === '/resolve' && req.method === 'GET') {
        const domain = url.searchParams.get('domain')?.toLowerCase();
        if (!domain) {
            res.writeHead(400);
            res.end(JSON.stringify({ error: 'Missing domain parameter' }));
            return;
        }

        const parsed = extractSovereignTLD(domain);
        if (!parsed) {
            res.writeHead(400);
            res.end(JSON.stringify({ error: 'Invalid domain format' }));
            return;
        }

        const tlds = await loadSovereignTLDs();
        if (!tlds.has(parsed.tld)) {
            res.writeHead(404);
            res.end(JSON.stringify({ error: 'Not a sovereign TLD', tld: parsed.tld }));
            return;
        }

        const record = await resolveSovereign(parsed.domain);
        if (!record) {
            res.writeHead(404);
            res.end(JSON.stringify({ error: 'Domain not registered', domain: parsed.domain }));
            return;
        }

        res.writeHead(200);
        res.end(JSON.stringify({
            domain: record.domain_name,
            tld: record.tld,
            a: record.dns_a,
            aaaa: record.dns_aaaa,
            cname: record.dns_cname,
            mx: record.dns_mx,
            status: record.status,
        }));
        return;
    }

    // GET /tlds — list available sovereign TLDs
    if (url.pathname === '/tlds' && req.method === 'GET') {
        const db = getPool();
        const [rows] = await db.query(
            `SELECT tld, display_name, description, icon, category, price_usd, price_gsm, status, registrations_count
             FROM sovereign_tlds WHERE status IN ('active', 'coming_soon') ORDER BY display_order, tld`
        );
        res.writeHead(200);
        res.end(JSON.stringify({ tlds: rows }));
        return;
    }

    // GET /check?domain=mysite.alfred — availability check
    if (url.pathname === '/check' && req.method === 'GET') {
        const domain = url.searchParams.get('domain')?.toLowerCase();
        if (!domain) {
            res.writeHead(400);
            res.end(JSON.stringify({ error: 'Missing domain parameter' }));
            return;
        }

        const parsed = extractSovereignTLD(domain);
        if (!parsed) {
            res.writeHead(400);
            res.end(JSON.stringify({ error: 'Invalid domain format' }));
            return;
        }

        // Validate subdomain format
        if (!/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$/.test(parsed.subdomain)) {
            res.writeHead(400);
            res.end(JSON.stringify({ error: 'Invalid domain name. Use only letters, numbers, and hyphens.' }));
            return;
        }

        const tlds = await loadSovereignTLDs();
        if (!tlds.has(parsed.tld)) {
            res.writeHead(404);
            res.end(JSON.stringify({ error: 'Not a sovereign TLD', tld: parsed.tld }));
            return;
        }

        const record = await resolveSovereign(parsed.domain);
        res.writeHead(200);
        res.end(JSON.stringify({
            domain: parsed.domain,
            available: !record,
            tld: parsed.tld,
        }));
        return;
    }

    // GET /stats — sovereign web stats
    if (url.pathname === '/stats' && req.method === 'GET') {
        const db = getPool();
        const [[{ totalDomains }]] = await db.query("SELECT COUNT(*) as totalDomains FROM sovereign_domains WHERE status = 'active'");
        const [[{ totalTLDs }]] = await db.query("SELECT COUNT(*) as totalTLDs FROM sovereign_tlds WHERE status IN ('active', 'reserved')");
        const [topTLDs] = await db.query(
            `SELECT t.tld, t.icon, t.registrations_count 
             FROM sovereign_tlds t WHERE t.status = 'active' 
             ORDER BY t.registrations_count DESC LIMIT 5`
        );
        res.writeHead(200);
        res.end(JSON.stringify({
            totalDomains,
            totalTLDs,
            topTLDs,
            cacheSize: cache.size,
            uptime: process.uptime(),
        }));
        return;
    }

    // GET /health
    if (url.pathname === '/health') {
        res.writeHead(200);
        res.end(JSON.stringify({ status: 'ok', service: 'sovereign-dns' }));
        return;
    }

    res.writeHead(404);
    res.end(JSON.stringify({ error: 'Not found' }));
});

const gateway = http.createServer((req, res) => {
    proxySovereignRequest(req, res).catch(err => {
        res.writeHead(500, { 'Content-Type': 'text/plain; charset=utf-8' });
        res.end(`Gateway failure: ${err.message}`);
    });
});

gateway.on('upgrade', (req, socket, head) => {
    proxySovereignUpgrade(req, socket, head).catch(err => {
        socket.write(`HTTP/1.1 500 Internal Server Error\r\nContent-Type: text/plain; charset=utf-8\r\nConnection: close\r\n\r\nGateway upgrade failure: ${err.message}`);
        socket.destroy();
    });
});

// === Start ===
server.on('listening', () => {
    console.log(`[SovDNS] DNS server listening on UDP port ${DNS_PORT}`);
});
server.on('error', (err) => {
    console.error('[SovDNS] DNS server error:', err.message);
});
server.listen({ udp: DNS_PORT });
api.listen(API_PORT, '127.0.0.1');
gateway.listen(GATEWAY_PORT, '127.0.0.1');

console.log('═══════════════════════════════════════════════════');
console.log('  SOVEREIGN DNS — The Address Book of the Sovereign Web');
console.log('═══════════════════════════════════════════════════');
console.log(`  DNS Server:  udp://127.0.0.1:${DNS_PORT}`);
console.log(`  DNS Server:  tcp://127.0.0.1:${DNS_PORT}`);
console.log(`  HTTP API:    http://127.0.0.1:${API_PORT}`);
console.log(`  Web Gateway: http://127.0.0.1:${GATEWAY_PORT}/_/mysite.alfred/`);
console.log('═══════════════════════════════════════════════════');

// Preload TLDs
loadSovereignTLDs().catch(err => console.error('[SovDNS] Failed to load TLDs:', err.message));

// Graceful shutdown
process.on('SIGINT', async () => {
    console.log('[SovDNS] Shutting down...');
    server.close();
    api.close();
    gateway.close();
    if (pool) await pool.end();
    process.exit(0);
});

process.on('SIGTERM', async () => {
    console.log('[SovDNS] Shutting down...');
    server.close();
    api.close();
    gateway.close();
    if (pool) await pool.end();
    process.exit(0);
});
