/**
 * ═══════════════════════════════════════════════════════════════════════
 * Alfred Event Bus — Universal client-side event reporter
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Include on any page to automatically report events to Alfred's
 * command center. Every subsystem calls AlfredEvents.emit() to
 * centralize visibility.
 *
 * Usage:
 *   AlfredEvents.emit('game.victory', 'games', { game: 'chess', winner: 42 });
 *   AlfredEvents.emit('nav.page_view', 'platform', { page: '/pulse' });
 */
(function () {
  'use strict';

  const API = '/api/alfred-command.php';
  const QUEUE_INTERVAL = 5000;   // Flush every 5s
  const MAX_QUEUE = 50;          // Max buffered events

  let queue = [];
  let flushing = false;

  function emit(eventType, subsystem, payload, severity) {
    queue.push({
      event_type: eventType,
      subsystem: subsystem || 'client',
      severity: severity || 'info',
      payload: payload || {},
      _ts: Date.now(),
    });
    if (queue.length >= MAX_QUEUE) flush();
  }

  async function flush() {
    if (flushing || queue.length === 0) return;
    flushing = true;
    const batch = queue.splice(0, MAX_QUEUE);
    try {
      for (const evt of batch) {
        await fetch(`${API}?action=events.emit`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'include',
          body: JSON.stringify(evt),
        }).catch(() => {});
      }
    } catch (e) { /* silent */ }
    flushing = false;
  }

  // Auto-flush on interval
  setInterval(flush, QUEUE_INTERVAL);
  // Flush on page unload
  if (typeof navigator !== 'undefined' && navigator.sendBeacon) {
    window.addEventListener('visibilitychange', () => {
      if (document.visibilityState === 'hidden' && queue.length > 0) {
        for (const evt of queue) {
          navigator.sendBeacon(
            `${API}?action=events.emit`,
            new Blob([JSON.stringify(evt)], { type: 'application/json' })
          );
        }
        queue = [];
      }
    });
  }

  // Auto-track page view
  emit('page.view', 'platform', {
    page: window.location.pathname,
    referrer: document.referrer || null,
  });

  // Expose globally
  window.AlfredEvents = { emit, flush };
})();
