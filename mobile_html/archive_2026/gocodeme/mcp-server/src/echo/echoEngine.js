/**
 * echoEngine.js — ECHO: Pattern Intelligence Engine
 *
 * Anomaly detection, pattern recognition, clustering, failure prediction,
 * correlation analysis, baseline drift, root cause analysis,
 * behavioral fingerprinting, and forecasting.
 *
 * Intelligence Type: Pattern / Naturalistic
 * Tools: 9
 */

const baselines = new Map();   // key → {mean, stddev, samples}
const fingerprints = new Map(); // system → {signature}

// ── Stats helpers ───────────────────────────────────────────────────────

function mean(arr) { return arr.reduce((a, b) => a + b, 0) / arr.length; }
function stddev(arr) {
  const m = mean(arr);
  return Math.sqrt(arr.reduce((s, v) => s + Math.pow(v - m, 2), 0) / arr.length);
}
function zScore(value, m, sd) { return sd === 0 ? 0 : (value - m) / sd; }
function pearsonCorrelation(x, y) {
  const n = Math.min(x.length, y.length);
  const mx = mean(x.slice(0, n)), my = mean(y.slice(0, n));
  let num = 0, dx2 = 0, dy2 = 0;
  for (let i = 0; i < n; i++) {
    const dx = x[i] - mx, dy = y[i] - my;
    num += dx * dy; dx2 += dx * dx; dy2 += dy * dy;
  }
  return dx2 === 0 || dy2 === 0 ? 0 : Math.round(num / Math.sqrt(dx2 * dy2) * 1000) / 1000;
}

/**
 * Detect anomalies in data using statistical methods
 */
export async function detectAnomaly(data, threshold = 2.5, options = {}) {
  const values = data.map(d => typeof d === 'object' ? d.value : d);
  const m = mean(values);
  const sd = stddev(values);

  const anomalies = values.map((v, i) => {
    const z = zScore(v, m, sd);
    return { index: i, value: v, z_score: Math.round(z * 100) / 100, is_anomaly: Math.abs(z) > threshold };
  }).filter(a => a.is_anomaly);

  return {
    total_points: values.length,
    mean: Math.round(m * 100) / 100,
    stddev: Math.round(sd * 100) / 100,
    threshold,
    anomalies_found: anomalies.length,
    anomalies,
    anomaly_rate: `${Math.round(anomalies.length / values.length * 100)}%`,
    severity: anomalies.length === 0 ? 'none'
      : anomalies.length <= 2 ? 'low'
      : anomalies.length <= 5 ? 'medium' : 'high',
    recommendation: anomalies.length > values.length * 0.1
      ? 'High anomaly rate — investigate data source or adjust threshold'
      : anomalies.length > 0
      ? `${anomalies.length} anomalies detected — review indices: ${anomalies.map(a => a.index).join(', ')}`
      : 'No anomalies — data within normal range'
  };
}

/**
 * Find recurring patterns in data
 */
export async function findPatterns(data, options = {}) {
  const values = data.map(d => typeof d === 'object' ? d.value : d);
  const patterns = [];

  // Repeating sequences
  for (let len = 2; len <= Math.min(10, Math.floor(values.length / 3)); len++) {
    const seq = values.slice(0, len).join(',');
    let count = 0;
    for (let i = 0; i <= values.length - len; i += len) {
      if (values.slice(i, i + len).join(',') === seq) count++;
    }
    if (count >= 2) patterns.push({ type: 'repeating', length: len, occurrences: count, sequence: values.slice(0, len) });
  }

  // Monotonic sequences
  let ascRun = 1, descRun = 1, maxAsc = 1, maxDesc = 1;
  for (let i = 1; i < values.length; i++) {
    if (values[i] >= values[i - 1]) { ascRun++; maxAsc = Math.max(maxAsc, ascRun); } else ascRun = 1;
    if (values[i] <= values[i - 1]) { descRun++; maxDesc = Math.max(maxDesc, descRun); } else descRun = 1;
  }
  if (maxAsc >= 4) patterns.push({ type: 'ascending_run', longest: maxAsc });
  if (maxDesc >= 4) patterns.push({ type: 'descending_run', longest: maxDesc });

  // Value clustering
  const sorted = [...values].sort((a, b) => a - b);
  const median = sorted[Math.floor(sorted.length / 2)];
  const aboveMedian = values.filter(v => v > median).length;
  const belowMedian = values.filter(v => v < median).length;

  return {
    data_points: values.length,
    patterns_found: patterns.length,
    patterns,
    distribution: {
      median: Math.round(median * 100) / 100,
      above_median: aboveMedian,
      below_median: belowMedian,
      symmetry: Math.abs(aboveMedian - belowMedian) <= values.length * 0.1 ? 'symmetric' : 'skewed'
    },
    summary: patterns.length > 0
      ? `Found ${patterns.length} pattern(s): ${patterns.map(p => p.type).join(', ')}`
      : 'No clear recurring patterns detected'
  };
}

/**
 * Cluster similar items
 */
export async function cluster(items, k = 3) {
  // items: [{features: [num, num, ...], label?}]
  if (!items || items.length < k) return { error: `Need at least ${k} items for ${k} clusters` };

  const features = items.map(item => item.features || [item.value || 0]);
  const dim = features[0].length;

  // Simple k-means
  let centroids = features.slice(0, k).map(f => [...f]);
  const assignments = new Array(items.length).fill(0);

  for (let iter = 0; iter < 20; iter++) {
    // Assign
    for (let i = 0; i < features.length; i++) {
      let minDist = Infinity;
      for (let c = 0; c < k; c++) {
        const dist = features[i].reduce((s, v, d) => s + Math.pow(v - centroids[c][d], 2), 0);
        if (dist < minDist) { minDist = dist; assignments[i] = c; }
      }
    }
    // Update centroids
    for (let c = 0; c < k; c++) {
      const members = features.filter((_, i) => assignments[i] === c);
      if (members.length > 0) {
        centroids[c] = Array.from({ length: dim }, (_, d) =>
          Math.round(members.reduce((s, m) => s + m[d], 0) / members.length * 100) / 100
        );
      }
    }
  }

  const clusters = Array.from({ length: k }, (_, c) => ({
    cluster_id: c,
    size: assignments.filter(a => a === c).length,
    centroid: centroids[c],
    items: items.filter((_, i) => assignments[i] === c).map((item, idx) => ({
      label: item.label || `item_${idx}`,
      features: item.features
    }))
  }));

  return {
    k,
    clusters,
    total_items: items.length,
    largest_cluster: clusters.reduce((max, c) => c.size > max.size ? c : max).cluster_id,
    smallest_cluster: clusters.reduce((min, c) => c.size < min.size ? c : min).cluster_id
  };
}

/**
 * Predict potential failures from patterns
 */
export async function predictFailure(metrics, thresholds = {}) {
  const defaults = { cpu: 90, memory: 85, disk: 90, error_rate: 5, latency: 2000 };
  const t = { ...defaults, ...thresholds };

  const risks = [];
  if (metrics.cpu !== undefined && metrics.cpu > t.cpu * 0.8)
    risks.push({ metric: 'cpu', current: metrics.cpu, threshold: t.cpu, risk: metrics.cpu > t.cpu ? 'critical' : 'warning', eta: `~${Math.round((t.cpu - metrics.cpu) / (metrics.cpu_trend || 1))}min to threshold` });
  if (metrics.memory !== undefined && metrics.memory > t.memory * 0.8)
    risks.push({ metric: 'memory', current: metrics.memory, threshold: t.memory, risk: metrics.memory > t.memory ? 'critical' : 'warning' });
  if (metrics.disk !== undefined && metrics.disk > t.disk * 0.8)
    risks.push({ metric: 'disk', current: metrics.disk, threshold: t.disk, risk: metrics.disk > t.disk ? 'critical' : 'warning' });
  if (metrics.error_rate !== undefined && metrics.error_rate > t.error_rate * 0.6)
    risks.push({ metric: 'error_rate', current: metrics.error_rate, threshold: t.error_rate, risk: metrics.error_rate > t.error_rate ? 'critical' : 'warning' });
  if (metrics.latency !== undefined && metrics.latency > t.latency * 0.7)
    risks.push({ metric: 'latency', current: `${metrics.latency}ms`, threshold: `${t.latency}ms`, risk: metrics.latency > t.latency ? 'critical' : 'warning' });

  const criticalCount = risks.filter(r => r.risk === 'critical').length;
  return {
    overall_health: criticalCount > 0 ? 'critical' : risks.length > 0 ? 'degraded' : 'healthy',
    failure_probability: `${Math.min(100, criticalCount * 30 + risks.length * 10)}%`,
    risks,
    action_required: criticalCount > 0,
    recommendations: risks.map(r => {
      if (r.metric === 'cpu') return 'Scale horizontally or optimize CPU-heavy processes';
      if (r.metric === 'memory') return 'Check for memory leaks, increase allocation, or restart';
      if (r.metric === 'disk') return 'Clean up logs, archives, or expand storage';
      if (r.metric === 'error_rate') return 'Review error logs, check recent deployments';
      if (r.metric === 'latency') return 'Check database queries, network, or add caching';
      return `Monitor ${r.metric}`;
    })
  };
}

/**
 * Find correlations between metrics
 */
export async function correlate(seriesA, seriesB, labels = {}) {
  const r = pearsonCorrelation(
    seriesA.map(d => typeof d === 'object' ? d.value : d),
    seriesB.map(d => typeof d === 'object' ? d.value : d)
  );

  const strength = Math.abs(r) > 0.7 ? 'strong' : Math.abs(r) > 0.4 ? 'moderate' : Math.abs(r) > 0.2 ? 'weak' : 'negligible';
  const direction = r > 0 ? 'positive' : r < 0 ? 'negative' : 'none';

  return {
    series_a: labels.a || 'Series A',
    series_b: labels.b || 'Series B',
    pearson_r: r,
    r_squared: Math.round(r * r * 1000) / 1000,
    strength,
    direction,
    interpretation: `${strength} ${direction} correlation — ${
      strength === 'strong' ? 'these metrics are closely linked' :
      strength === 'moderate' ? 'some relationship exists' :
      'likely independent metrics'
    }`,
    caution: 'Correlation does not imply causation'
  };
}

/**
 * Detect drift from baseline behavior
 */
export async function baselineDrift(key, currentValues, action = 'check') {
  if (action === 'set' || !baselines.has(key)) {
    const m = mean(currentValues);
    const sd = stddev(currentValues);
    baselines.set(key, { mean: m, stddev: sd, samples: currentValues.length, set_at: new Date().toISOString() });
    return { action: 'baseline_set', key, mean: Math.round(m * 100) / 100, stddev: Math.round(sd * 100) / 100 };
  }

  const baseline = baselines.get(key);
  const currentMean = mean(currentValues);
  const currentSd = stddev(currentValues);
  const drift = Math.abs(currentMean - baseline.mean) / (baseline.stddev || 1);
  const volatilityChange = baseline.stddev > 0 ? currentSd / baseline.stddev : 1;

  return {
    key,
    baseline: { mean: Math.round(baseline.mean * 100) / 100, stddev: Math.round(baseline.stddev * 100) / 100, set_at: baseline.set_at },
    current: { mean: Math.round(currentMean * 100) / 100, stddev: Math.round(currentSd * 100) / 100 },
    drift_score: Math.round(drift * 100) / 100,
    drift_direction: currentMean > baseline.mean ? 'higher' : 'lower',
    volatility_change: Math.round(volatilityChange * 100) / 100,
    status: drift > 3 ? 'critical_drift' : drift > 2 ? 'significant_drift' : drift > 1 ? 'minor_drift' : 'stable',
    alert: drift > 2 ? `Significant drift detected: current mean ${Math.round(currentMean * 100) / 100} vs baseline ${Math.round(baseline.mean * 100) / 100}` : null
  };
}

/**
 * Root cause analysis from patterns
 */
export async function rootCause(symptoms, context = {}) {
  // symptoms: [{event, timestamp?, severity?}]
  const timeline = symptoms.sort((a, b) => new Date(a.timestamp || 0) - new Date(b.timestamp || 0));
  const eventTypes = {};
  timeline.forEach(s => {
    const type = s.event || s.type || 'unknown';
    eventTypes[type] = (eventTypes[type] || 0) + 1;
  });

  const sorted = Object.entries(eventTypes).sort((a, b) => b[1] - a[1]);
  const mostFrequent = sorted[0];
  const firstEvent = timeline[0];

  // Simple heuristic root cause analysis
  const analysis = {
    total_events: symptoms.length,
    unique_types: Object.keys(eventTypes).length,
    event_frequency: eventTypes,
    timeline_span: timeline.length >= 2 ? {
      first: firstEvent.timestamp || 'unknown',
      last: timeline[timeline.length - 1].timestamp || 'unknown'
    } : null,
    probable_root_cause: {
      event: firstEvent.event || firstEvent.type || 'unknown',
      reasoning: 'First event in timeline — cascading failures often stem from the initial trigger',
      confidence: timeline.length >= 3 ? 'medium' : 'low'
    },
    most_frequent: {
      event: mostFrequent[0],
      count: mostFrequent[1],
      note: 'Most frequent symptom — may or may not be the root cause'
    },
    cascade_pattern: timeline.length >= 3 ? timeline.slice(0, 5).map(s => s.event || s.type).join(' → ') : null,
    recommendations: [
      `Investigate "${firstEvent.event || firstEvent.type}" as initial trigger`,
      sorted.length > 1 && `Check relationship between "${sorted[0][0]}" and "${sorted[1][0]}"`,
      context.recent_changes && `Review recent changes: ${context.recent_changes}`
    ].filter(Boolean)
  };

  return analysis;
}

/**
 * Create behavioral fingerprint of a system
 */
export async function fingerprint(systemId, metrics) {
  const fp = {
    system_id: systemId,
    created_at: new Date().toISOString(),
    signature: {},
    profile: {}
  };

  for (const [key, values] of Object.entries(metrics)) {
    const vals = Array.isArray(values) ? values : [values];
    const m = mean(vals);
    const sd = stddev(vals);
    fp.signature[key] = {
      mean: Math.round(m * 100) / 100,
      stddev: Math.round(sd * 100) / 100,
      min: Math.min(...vals),
      max: Math.max(...vals),
      range: Math.max(...vals) - Math.min(...vals)
    };
  }

  // Compare to previous fingerprint if exists
  const previous = fingerprints.get(systemId);
  let drift = null;
  if (previous) {
    drift = {};
    for (const key of Object.keys(fp.signature)) {
      if (previous.signature[key]) {
        const change = (fp.signature[key].mean - previous.signature[key].mean) / (previous.signature[key].stddev || 1);
        drift[key] = { drift_score: Math.round(change * 100) / 100, changed: Math.abs(change) > 1 };
      }
    }
  }

  fingerprints.set(systemId, fp);

  return {
    ...fp,
    drift_from_previous: drift,
    is_new: !previous,
    summary: `Fingerprint ${previous ? 'updated' : 'created'} for ${systemId} with ${Object.keys(fp.signature).length} metrics`
  };
}

/**
 * Forecast based on pattern recognition
 */
export async function forecast(data, periods = 5, method = 'auto') {
  const values = data.map(d => typeof d === 'object' ? d.value : d);
  const n = values.length;
  if (n < 3) return { error: 'Need at least 3 data points for forecasting' };

  // Exponential smoothing (Holt's method)
  const alpha = 0.3, beta = 0.1;
  let level = values[0], trend = values[1] - values[0];

  for (let i = 1; i < n; i++) {
    const newLevel = alpha * values[i] + (1 - alpha) * (level + trend);
    trend = beta * (newLevel - level) + (1 - beta) * trend;
    level = newLevel;
  }

  const forecasted = [];
  for (let h = 1; h <= periods; h++) {
    const pred = Math.round((level + h * trend) * 100) / 100;
    forecasted.push({
      period: n + h,
      value: pred,
      lower: Math.round((pred - stddev(values) * 1.96) * 100) / 100,
      upper: Math.round((pred + stddev(values) * 1.96) * 100) / 100
    });
  }

  return {
    method: 'holt_exponential_smoothing',
    data_points: n,
    last_value: values[n - 1],
    level: Math.round(level * 100) / 100,
    trend: Math.round(trend * 100) / 100,
    direction: trend > 0 ? 'increasing' : trend < 0 ? 'decreasing' : 'flat',
    forecast: forecasted,
    confidence_interval: '95%',
    note: 'Forecasts assume continuation of recent trends. External factors may cause deviation.'
  };
}
