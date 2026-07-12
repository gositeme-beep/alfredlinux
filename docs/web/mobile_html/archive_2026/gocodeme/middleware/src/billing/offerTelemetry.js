'use strict';

const { getRedis } = require('../redis');

const VALID_EVENTS = new Set(['shown', 'clicked', 'upgrade_started']);

function utcDay(offset = 0) {
  const now = new Date();
  const d = new Date(Date.UTC(now.getUTCFullYear(), now.getUTCMonth(), now.getUTCDate() - offset, 0, 0, 0));
  return d.toISOString().slice(0, 10);
}

function normalizeToken(value, fallback) {
  const raw = String(value || '').toLowerCase().trim();
  if (!raw) return fallback;
  return raw.replace(/[^a-z0-9_\-]/g, '_').slice(0, 48) || fallback;
}

async function trackOfferEvent({ clientId, daUsername, event, variant, limitKind, source }) {
  const evt = normalizeToken(event, 'shown');
  if (!VALID_EVENTS.has(evt)) return;

  const v = normalizeToken(variant, 'unknown');
  const kind = normalizeToken(limitKind, 'unknown');
  const src = normalizeToken(source, 'unknown');
  const day = utcDay();

  const redis = getRedis();
  const key = `offer:telemetry:day:${day}`;
  const ttlSec = 120 * 24 * 60 * 60;

  const multi = redis.multi();
  multi.hincrby(key, `event:${evt}`, 1);
  multi.hincrby(key, `variant:${v}:event:${evt}`, 1);
  multi.hincrby(key, `kind:${kind}:event:${evt}`, 1);
  multi.hincrby(key, `source:${src}:event:${evt}`, 1);
  multi.hincrby(key, `variant:${v}:kind:${kind}:event:${evt}`, 1);
  if (clientId) multi.hincrby(key, `client:${String(clientId)}:event:${evt}`, 1);
  if (daUsername) multi.hincrby(key, `user:${normalizeToken(daUsername, 'unknown')}:event:${evt}`, 1);
  multi.expire(key, ttlSec);
  await multi.exec();
}

async function getOfferTelemetry(days = 7) {
  const redis = getRedis();
  const safeDays = Math.max(1, Math.min(parseInt(days, 10) || 7, 90));
  const byDay = [];

  const totals = {
    shown: 0,
    clicked: 0,
    upgrade_started: 0,
    ctr_pct: 0,
    start_rate_pct: 0,
  };

  for (let i = 0; i < safeDays; i++) {
    const day = utcDay(i);
    const data = await redis.hgetall(`offer:telemetry:day:${day}`);
    const shown = parseInt(data['event:shown'] || '0', 10);
    const clicked = parseInt(data['event:clicked'] || '0', 10);
    const started = parseInt(data['event:upgrade_started'] || '0', 10);

    totals.shown += shown;
    totals.clicked += clicked;
    totals.upgrade_started += started;

    byDay.push({
      day,
      shown,
      clicked,
      upgrade_started: started,
      ctr_pct: shown > 0 ? Math.round((clicked / shown) * 10000) / 100 : 0,
      start_rate_pct: shown > 0 ? Math.round((started / shown) * 10000) / 100 : 0,
    });
  }

  totals.ctr_pct = totals.shown > 0 ? Math.round((totals.clicked / totals.shown) * 10000) / 100 : 0;
  totals.start_rate_pct = totals.shown > 0 ? Math.round((totals.upgrade_started / totals.shown) * 10000) / 100 : 0;

  return {
    days: safeDays,
    totals,
    by_day: byDay.reverse(),
  };
}

module.exports = {
  VALID_EVENTS,
  trackOfferEvent,
  getOfferTelemetry,
};
