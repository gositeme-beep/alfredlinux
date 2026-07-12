/**
 * callAnalyticsEngine.js — CALL ANALYTICS: Voice Intelligence Engine
 *
 * Provides comprehensive call tracking, analytics, sentiment analysis,
 * and performance dashboards for voice interactions.
 *
 * Capabilities:
 *  - Call logging with metadata (duration, outcome, sentiment)
 *  - AI-powered call summaries
 *  - Performance metrics (volume, success rates, avg duration)
 *  - Sentiment tracking across calls
 *  - Lead scoring from call outcomes
 *  - Searchable transcript history
 *  - Natural language queries against call data
 */

import { randomUUID } from 'node:crypto';
import fs from 'node:fs/promises';
import path from 'node:path';

const CALLS_BASE = '/home/gositeme/.gocodeme/call_analytics';

async function ensureDir(dir) { await fs.mkdir(dir, { recursive: true }); }
async function loadJSON(file, fallback = {}) {
  try { return JSON.parse(await fs.readFile(file, 'utf8')); } catch { return fallback; }
}
async function saveJSON(file, data) {
  await ensureDir(path.dirname(file));
  await fs.writeFile(file, JSON.stringify(data, null, 2));
}

function callsPath(user)      { return path.join(CALLS_BASE, user, 'calls.json'); }
function metricsPath(user)    { return path.join(CALLS_BASE, user, 'metrics.json'); }
function leadsPath(user)      { return path.join(CALLS_BASE, user, 'leads.json'); }

const now = () => new Date().toISOString();

// ════════════════════════════════════════════════════════════════════════════
// SECTION 1: CALL LOGGING
// ════════════════════════════════════════════════════════════════════════════

export async function logCall(user, callData) {
  const calls = await loadJSON(callsPath(user), { calls: [] });
  const id = `call_${randomUUID().slice(0, 8)}`;

  const record = {
    id,
    direction: callData.direction || 'inbound',  // inbound | outbound
    callerNumber: callData.callerNumber || '',
    calledNumber: callData.calledNumber || '',
    customerName: callData.customerName || '',
    customerEmail: callData.customerEmail || '',
    duration: callData.duration || 0,  // seconds
    startTime: callData.startTime || now(),
    endTime: callData.endTime || now(),
    outcome: callData.outcome || 'unknown',  // resolved, escalated, voicemail, missed, transferred, callback_requested
    sentiment: callData.sentiment || 'neutral',  // positive, neutral, negative, angry
    topics: callData.topics || [],  // ['order_status', 'refund', 'product_inquiry']
    summary: callData.summary || '',
    transcript: callData.transcript || '',
    agentId: callData.agentId || 'alfred',
    storeId: callData.storeId || null,
    orderId: callData.orderId || null,
    resolution: callData.resolution || '',
    followUpRequired: callData.followUpRequired || false,
    followUpDate: callData.followUpDate || null,
    tags: callData.tags || [],
    metadata: callData.metadata || {},
    recordedAt: now(),
  };

  calls.calls.push(record);
  if (calls.calls.length > 10000) calls.calls = calls.calls.slice(-10000);
  await saveJSON(callsPath(user), calls);

  // Auto-score lead if customer info provided
  if (record.customerEmail || record.customerName) {
    await updateLead(user, {
      name: record.customerName,
      email: record.customerEmail,
      phone: record.callerNumber,
      lastCallId: id,
      outcome: record.outcome,
      sentiment: record.sentiment,
    });
  }

  return { id, message: `Call logged. ID: ${id}. Duration: ${record.duration}s. Outcome: ${record.outcome}.` };
}

export async function getCall(user, callId) {
  const calls = await loadJSON(callsPath(user), { calls: [] });
  const call = calls.calls.find(c => c.id === callId);
  if (!call) throw new Error(`Call ${callId} not found`);
  return { call, message: `Call ${callId}: ${call.direction} | ${call.duration}s | ${call.outcome} | ${call.sentiment}` };
}

export async function searchCalls(user, filters = {}) {
  const calls = await loadJSON(callsPath(user), { calls: [] });
  let results = calls.calls;

  if (filters.direction) results = results.filter(c => c.direction === filters.direction);
  if (filters.outcome) results = results.filter(c => c.outcome === filters.outcome);
  if (filters.sentiment) results = results.filter(c => c.sentiment === filters.sentiment);
  if (filters.callerNumber) results = results.filter(c => c.callerNumber.includes(filters.callerNumber));
  if (filters.customerName) results = results.filter(c => c.customerName?.toLowerCase().includes(filters.customerName.toLowerCase()));
  if (filters.topic) results = results.filter(c => c.topics.includes(filters.topic));
  if (filters.since) results = results.filter(c => new Date(c.startTime) >= new Date(filters.since));
  if (filters.until) results = results.filter(c => new Date(c.startTime) <= new Date(filters.until));
  if (filters.query) {
    const q = filters.query.toLowerCase();
    results = results.filter(c =>
      c.summary?.toLowerCase().includes(q) ||
      c.transcript?.toLowerCase().includes(q) ||
      c.customerName?.toLowerCase().includes(q) ||
      c.topics.some(t => t.toLowerCase().includes(q)) ||
      c.tags.some(t => t.toLowerCase().includes(q))
    );
  }

  const limit = filters.limit || 50;
  results = results.slice(-limit);

  return {
    calls: results.map(c => ({
      id: c.id, direction: c.direction, callerNumber: c.callerNumber,
      customerName: c.customerName, duration: c.duration,
      outcome: c.outcome, sentiment: c.sentiment, topics: c.topics,
      summary: c.summary, startTime: c.startTime, followUpRequired: c.followUpRequired,
    })),
    totalMatches: results.length,
    totalCalls: calls.calls.length,
    message: `${results.length} call(s) found${filters.query ? ` matching "${filters.query}"` : ''}.`,
  };
}

// ════════════════════════════════════════════════════════════════════════════
// SECTION 2: CALL ANALYTICS & METRICS
// ════════════════════════════════════════════════════════════════════════════

export async function getCallAnalytics(user, period = '7d') {
  const calls = await loadJSON(callsPath(user), { calls: [] });
  const now_ts = Date.now();
  const periods = { '24h': 86400000, '7d': 604800000, '30d': 2592000000, '90d': 7776000000 };
  const cutoff = now_ts - (periods[period] || periods['7d']);
  const filtered = calls.calls.filter(c => new Date(c.startTime).getTime() >= cutoff);

  const analytics = {
    period,
    totalCalls: filtered.length,
    inbound: filtered.filter(c => c.direction === 'inbound').length,
    outbound: filtered.filter(c => c.direction === 'outbound').length,
    avgDuration: filtered.length ? Math.round(filtered.reduce((s, c) => s + c.duration, 0) / filtered.length) : 0,
    totalDuration: filtered.reduce((s, c) => s + c.duration, 0),

    outcomes: {},
    sentiments: {},
    topTopics: {},
    hourlyDistribution: new Array(24).fill(0),
    dailyVolume: {},

    resolutionRate: 0,
    escalationRate: 0,
    averageSentimentScore: 0,
    followUpsPending: 0,
  };

  const sentimentScores = { positive: 1, neutral: 0, negative: -1, angry: -2 };

  for (const c of filtered) {
    analytics.outcomes[c.outcome] = (analytics.outcomes[c.outcome] || 0) + 1;
    analytics.sentiments[c.sentiment] = (analytics.sentiments[c.sentiment] || 0) + 1;
    for (const t of c.topics) analytics.topTopics[t] = (analytics.topTopics[t] || 0) + 1;
    const hour = new Date(c.startTime).getHours();
    analytics.hourlyDistribution[hour]++;
    const day = c.startTime.split('T')[0];
    analytics.dailyVolume[day] = (analytics.dailyVolume[day] || 0) + 1;
    if (c.followUpRequired && c.outcome !== 'resolved') analytics.followUpsPending++;
  }

  if (filtered.length) {
    const resolved = filtered.filter(c => c.outcome === 'resolved').length;
    const escalated = filtered.filter(c => c.outcome === 'escalated').length;
    analytics.resolutionRate = Math.round((resolved / filtered.length) * 100);
    analytics.escalationRate = Math.round((escalated / filtered.length) * 100);
    analytics.averageSentimentScore = +(filtered.reduce((s, c) => s + (sentimentScores[c.sentiment] || 0), 0) / filtered.length).toFixed(2);
  }

  // Sort top topics
  analytics.topTopics = Object.entries(analytics.topTopics)
    .sort((a, b) => b[1] - a[1])
    .slice(0, 10)
    .reduce((obj, [k, v]) => ({ ...obj, [k]: v }), {});

  // Peak hours (top 3)
  const peakHours = analytics.hourlyDistribution
    .map((count, hour) => ({ hour, count }))
    .sort((a, b) => b.count - a.count)
    .slice(0, 3)
    .map(h => `${h.hour}:00 (${h.count} calls)`);

  return {
    ...analytics,
    peakHours,
    message: `Call analytics (${period}): ${analytics.totalCalls} calls | Avg: ${analytics.avgDuration}s | ` +
      `Resolution: ${analytics.resolutionRate}% | Escalation: ${analytics.escalationRate}% | ` +
      `Sentiment: ${analytics.averageSentimentScore} | Follow-ups pending: ${analytics.followUpsPending}`,
  };
}

export async function getPerformanceReport(user) {
  const calls = await loadJSON(callsPath(user), { calls: [] });

  // Compare last 7 days vs previous 7 days
  const now_ts = Date.now();
  const current = calls.calls.filter(c => {
    const ts = new Date(c.startTime).getTime();
    return ts >= now_ts - 604800000;
  });
  const previous = calls.calls.filter(c => {
    const ts = new Date(c.startTime).getTime();
    return ts >= now_ts - 1209600000 && ts < now_ts - 604800000;
  });

  const calcMetrics = (list) => ({
    total: list.length,
    resolved: list.filter(c => c.outcome === 'resolved').length,
    avgDuration: list.length ? Math.round(list.reduce((s, c) => s + c.duration, 0) / list.length) : 0,
    positive: list.filter(c => c.sentiment === 'positive').length,
    negative: list.filter(c => c.sentiment === 'negative' || c.sentiment === 'angry').length,
  });

  const curr = calcMetrics(current);
  const prev = calcMetrics(previous);

  const pctChange = (a, b) => b ? Math.round(((a - b) / b) * 100) : (a ? 100 : 0);

  return {
    current: curr,
    previous: prev,
    trends: {
      volumeChange: pctChange(curr.total, prev.total),
      resolutionChange: pctChange(curr.resolved, prev.resolved),
      durationChange: pctChange(curr.avgDuration, prev.avgDuration),
      sentimentChange: pctChange(curr.positive - curr.negative, prev.positive - prev.negative),
    },
    message: `Performance (7d vs prev 7d): Volume ${pctChange(curr.total, prev.total) >= 0 ? '+' : ''}${pctChange(curr.total, prev.total)}% | ` +
      `Resolution ${pctChange(curr.resolved, prev.resolved) >= 0 ? '+' : ''}${pctChange(curr.resolved, prev.resolved)}% | ` +
      `Avg Duration ${curr.avgDuration}s (was ${prev.avgDuration}s)`,
  };
}

// ════════════════════════════════════════════════════════════════════════════
// SECTION 3: LEAD SCORING
// ════════════════════════════════════════════════════════════════════════════

async function updateLead(user, data) {
  const leads = await loadJSON(leadsPath(user), { leads: {} });
  const key = data.email || data.phone || data.name;
  if (!key) return;

  const existing = leads.leads[key] || {
    name: data.name,
    email: data.email,
    phone: data.phone,
    score: 50,
    callCount: 0,
    lastOutcome: null,
    lastSentiment: null,
    createdAt: now(),
    tags: [],
  };

  existing.callCount++;
  existing.lastCallId = data.lastCallId;
  existing.lastOutcome = data.outcome;
  existing.lastSentiment = data.sentiment;
  existing.lastContactedAt = now();
  if (data.name && !existing.name) existing.name = data.name;
  if (data.email && !existing.email) existing.email = data.email;
  if (data.phone && !existing.phone) existing.phone = data.phone;

  // Score adjustments
  if (data.sentiment === 'positive') existing.score = Math.min(100, existing.score + 5);
  if (data.sentiment === 'negative') existing.score = Math.max(0, existing.score - 5);
  if (data.sentiment === 'angry') existing.score = Math.max(0, existing.score - 10);
  if (data.outcome === 'resolved') existing.score = Math.min(100, existing.score + 3);
  if (data.outcome === 'callback_requested') existing.score = Math.min(100, existing.score + 10);

  leads.leads[key] = existing;
  await saveJSON(leadsPath(user), leads);
}

export async function getLeads(user, minScore = 0) {
  const leads = await loadJSON(leadsPath(user), { leads: {} });
  const list = Object.values(leads.leads)
    .filter(l => l.score >= minScore)
    .sort((a, b) => b.score - a.score);

  return {
    leads: list.map(l => ({
      name: l.name, email: l.email, phone: l.phone,
      score: l.score, callCount: l.callCount,
      lastOutcome: l.lastOutcome, lastSentiment: l.lastSentiment,
      lastContactedAt: l.lastContactedAt,
    })),
    message: `${list.length} lead(s) with score >= ${minScore}.`,
  };
}

// ════════════════════════════════════════════════════════════════════════════
// SECTION 4: NATURAL LANGUAGE QUERY (Ask about your calls)
// ════════════════════════════════════════════════════════════════════════════

export async function askCallData(user, question) {
  const calls = await loadJSON(callsPath(user), { calls: [] });
  const q = question.toLowerCase();

  // Simple natural language parser for common questions
  let answer;

  if (q.includes('how many') && q.includes('call')) {
    if (q.includes('today')) {
      const today = new Date().toISOString().split('T')[0];
      const count = calls.calls.filter(c => c.startTime.startsWith(today)).length;
      answer = `${count} call(s) today.`;
    } else if (q.includes('week')) {
      const weekAgo = Date.now() - 604800000;
      const count = calls.calls.filter(c => new Date(c.startTime).getTime() >= weekAgo).length;
      answer = `${count} call(s) in the last 7 days.`;
    } else if (q.includes('month')) {
      const monthAgo = Date.now() - 2592000000;
      const count = calls.calls.filter(c => new Date(c.startTime).getTime() >= monthAgo).length;
      answer = `${count} call(s) in the last 30 days.`;
    } else {
      answer = `${calls.calls.length} total calls logged.`;
    }
  } else if (q.includes('average') && q.includes('duration')) {
    const avg = calls.calls.length ? Math.round(calls.calls.reduce((s, c) => s + c.duration, 0) / calls.calls.length) : 0;
    answer = `Average call duration: ${avg} seconds (${(avg / 60).toFixed(1)} minutes).`;
  } else if (q.includes('resolution') || q.includes('resolved')) {
    const resolved = calls.calls.filter(c => c.outcome === 'resolved').length;
    const rate = calls.calls.length ? Math.round((resolved / calls.calls.length) * 100) : 0;
    answer = `${resolved} out of ${calls.calls.length} calls resolved (${rate}% resolution rate).`;
  } else if (q.includes('escalat')) {
    const escalated = calls.calls.filter(c => c.outcome === 'escalated').length;
    answer = `${escalated} call(s) escalated to humans.`;
  } else if (q.includes('angry') || q.includes('negative') || q.includes('unhappy')) {
    const angry = calls.calls.filter(c => c.sentiment === 'angry' || c.sentiment === 'negative').length;
    answer = `${angry} call(s) had negative/angry sentiment.`;
  } else if (q.includes('follow') && q.includes('up')) {
    const followUps = calls.calls.filter(c => c.followUpRequired && c.outcome !== 'resolved');
    answer = `${followUps.length} pending follow-up(s).`;
    if (followUps.length > 0) {
      answer += ' Most recent: ' + followUps.slice(-3).map(c => `${c.customerName || c.callerNumber} (${c.startTime.split('T')[0]})`).join(', ');
    }
  } else if (q.includes('top') && q.includes('topic')) {
    const topics = {};
    for (const c of calls.calls) for (const t of c.topics) topics[t] = (topics[t] || 0) + 1;
    const sorted = Object.entries(topics).sort((a, b) => b[1] - a[1]).slice(0, 5);
    answer = sorted.length ? `Top topics: ${sorted.map(([t, c]) => `${t} (${c})`).join(', ')}` : 'No topics recorded yet.';
  } else if (q.includes('busiest') || q.includes('peak') || q.includes('most calls')) {
    const hours = new Array(24).fill(0);
    for (const c of calls.calls) hours[new Date(c.startTime).getHours()]++;
    const peakHour = hours.indexOf(Math.max(...hours));
    answer = `Busiest hour: ${peakHour}:00 with ${hours[peakHour]} call(s).`;
  } else {
    // Fallback: search transcripts and summaries
    const matches = calls.calls.filter(c =>
      c.summary?.toLowerCase().includes(q) ||
      c.transcript?.toLowerCase().includes(q)
    );
    answer = matches.length
      ? `Found ${matches.length} call(s) related to "${question}". Most recent: ${matches.slice(-3).map(c => `${c.id} (${c.summary?.substring(0, 80)})`).join('; ')}`
      : `No specific answer found for "${question}". Try: "how many calls this week", "average duration", "resolution rate", "top topics", "peak hours", "pending follow-ups".`;
  }

  return { question, answer, totalCalls: calls.calls.length, message: answer };
}
