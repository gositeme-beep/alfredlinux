/**
 * Alfred WebSocket Client Library v1.0
 * ─────────────────────────────────────
 * Reusable real-time push client for all Alfred pages.
 * Auto-reconnect, heartbeat, channel subscriptions, presence.
 *
 * Usage:
 *   const ws = new AlfredWS({ token: '...' });
 *   ws.subscribe('fleet:updates', (data) => { ... });
 *   ws.subscribe('chat:room_abc', (data) => { ... });
 *   ws.publish('chat:room_abc', { message: 'hello' });
 *   ws.setPresence('online');
 *   ws.disconnect();
 */

'use strict';

class AlfredWS {
  constructor(opts = {}) {
    this.token = opts.token || '';
    this.url = opts.url || `wss://${location.host}/ws`;
    this.maxRetries = opts.maxRetries ?? 10;
    this.debug = opts.debug ?? false;

    this._ws = null;
    this._connId = null;
    this._userId = null;
    this._alive = false;
    this._retries = 0;
    this._retryTimer = null;
    this._heartbeatTimer = null;
    this._handlers = new Map();     // channel → Set<fn>
    this._pendingSubs = new Set();  // channels to re-subscribe on reconnect
    this._eventHandlers = {};       // system events: open, close, error, welcome
    this._connected = false;

    if (this.token) this.connect();
  }

  /* ── Connection ─────────────────────────────────── */

  connect() {
    if (this._ws && (this._ws.readyState === WebSocket.OPEN || this._ws.readyState === WebSocket.CONNECTING)) return;

    const sep = this.url.includes('?') ? '&' : '?';
    const wsUrl = `${this.url}${sep}token=${encodeURIComponent(this.token)}`;

    try {
      this._ws = new WebSocket(wsUrl);
    } catch (e) {
      this._log('error', 'WebSocket creation failed', e);
      this._scheduleReconnect();
      return;
    }

    this._ws.onopen = () => {
      this._connected = true;
      this._retries = 0;
      this._alive = true;
      this._log('info', 'Connected');
      this._startHeartbeat();
      // Re-subscribe all channels
      for (const ch of this._pendingSubs) {
        this._send({ type: 'subscribe', channel: ch });
      }
      this._emit('open');
    };

    this._ws.onclose = (e) => {
      this._connected = false;
      this._stopHeartbeat();
      this._log('info', 'Disconnected', e.code, e.reason);
      this._emit('close', e);
      if (e.code !== 1000) this._scheduleReconnect();
    };

    this._ws.onerror = (e) => {
      this._log('error', 'WebSocket error');
      this._emit('error', e);
    };

    this._ws.onmessage = (e) => {
      try {
        const msg = JSON.parse(e.data);
        this._handleMessage(msg);
      } catch (err) {
        this._log('error', 'Parse error', err);
      }
    };
  }

  disconnect() {
    this._retries = this.maxRetries; // prevent reconnect
    clearTimeout(this._retryTimer);
    this._stopHeartbeat();
    if (this._ws) {
      this._ws.close(1000, 'Client disconnect');
      this._ws = null;
    }
    this._connected = false;
  }

  get connected() { return this._connected; }

  /* ── Subscriptions ──────────────────────────────── */

  subscribe(channel, handler) {
    if (!this._handlers.has(channel)) {
      this._handlers.set(channel, new Set());
    }
    this._handlers.get(channel).add(handler);
    this._pendingSubs.add(channel);

    if (this._connected) {
      this._send({ type: 'subscribe', channel });
    }
    return () => this.unsubscribe(channel, handler);
  }

  unsubscribe(channel, handler) {
    const set = this._handlers.get(channel);
    if (set) {
      if (handler) {
        set.delete(handler);
        if (set.size === 0) {
          this._handlers.delete(channel);
          this._pendingSubs.delete(channel);
          if (this._connected) this._send({ type: 'unsubscribe', channel });
        }
      } else {
        this._handlers.delete(channel);
        this._pendingSubs.delete(channel);
        if (this._connected) this._send({ type: 'unsubscribe', channel });
      }
    }
  }

  /* ── Publishing ─────────────────────────────────── */

  publish(channel, data) {
    if (!this._connected) {
      this._log('warn', 'Cannot publish — not connected');
      return false;
    }
    this._send({ type: 'publish', channel, data });
    return true;
  }

  /* ── Presence ───────────────────────────────────── */

  setPresence(status) {
    if (this._connected) {
      this._send({ type: 'presence', status });
    }
  }

  /* ── System Events ──────────────────────────────── */

  on(event, handler) {
    if (!this._eventHandlers[event]) this._eventHandlers[event] = [];
    this._eventHandlers[event].push(handler);
    return () => this.off(event, handler);
  }

  off(event, handler) {
    const arr = this._eventHandlers[event];
    if (arr) {
      const idx = arr.indexOf(handler);
      if (idx !== -1) arr.splice(idx, 1);
    }
  }

  /* ── Internal ───────────────────────────────────── */

  _send(data) {
    if (this._ws && this._ws.readyState === WebSocket.OPEN) {
      this._ws.send(JSON.stringify(data));
    }
  }

  _handleMessage(msg) {
    switch (msg.type) {
      case 'welcome':
        this._connId = msg.connId;
        this._userId = msg.userId;
        this._log('info', 'Welcome', msg.connId);
        this._emit('welcome', msg);
        break;

      case 'event': {
        const channel = msg.channel;
        const data = msg.data;
        // Exact match handlers
        const exact = this._handlers.get(channel);
        if (exact) exact.forEach(fn => { try { fn(data, channel); } catch(e) { this._log('error', 'Handler error', e); } });
        // Pattern match handlers (e.g. "fleet:*" should catch "fleet:update")
        for (const [pattern, handlers] of this._handlers) {
          if (pattern !== channel && this._matchPattern(pattern, channel)) {
            handlers.forEach(fn => { try { fn(data, channel); } catch(e) {} });
          }
        }
        break;
      }

      case 'pong':
        this._alive = true;
        break;

      case 'subscribed':
        this._log('debug', 'Subscribed to', msg.channel);
        break;

      case 'error':
        this._log('warn', 'Server error:', msg.message);
        this._emit('error', msg);
        break;

      default:
        this._log('debug', 'Unknown message type', msg.type);
    }
  }

  _matchPattern(pattern, channel) {
    if (pattern === channel) return true;
    if (pattern.endsWith(':*')) {
      return channel.startsWith(pattern.slice(0, -1));
    }
    return false;
  }

  _startHeartbeat() {
    this._stopHeartbeat();
    this._heartbeatTimer = setInterval(() => {
      if (!this._alive) {
        this._log('warn', 'Heartbeat timeout');
        this._ws?.close(4001, 'Heartbeat timeout');
        return;
      }
      this._alive = false;
      this._send({ type: 'ping' });
    }, 25000);
  }

  _stopHeartbeat() {
    if (this._heartbeatTimer) {
      clearInterval(this._heartbeatTimer);
      this._heartbeatTimer = null;
    }
  }

  _scheduleReconnect() {
    if (this._retries >= this.maxRetries) {
      this._log('error', 'Max retries reached');
      this._emit('max_retries');
      return;
    }
    const delay = Math.min(1000 * Math.pow(2, this._retries), 30000);
    this._retries++;
    this._log('info', `Reconnecting in ${delay}ms (attempt ${this._retries})`);
    this._retryTimer = setTimeout(() => this.connect(), delay);
  }

  _emit(event, data) {
    const arr = this._eventHandlers[event];
    if (arr) arr.forEach(fn => { try { fn(data); } catch(e) {} });
  }

  _log(level, ...args) {
    if (this.debug || level === 'error' || level === 'warn') {
      console[level === 'debug' ? 'log' : level]('[AlfredWS]', ...args);
    }
  }
}

// Export for module systems or attach to window
if (typeof module !== 'undefined' && module.exports) {
  module.exports = AlfredWS;
} else {
  window.AlfredWS = AlfredWS;
}
