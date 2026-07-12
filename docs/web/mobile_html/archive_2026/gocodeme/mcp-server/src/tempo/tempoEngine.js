/**
 * tempoEngine.js — TEMPO: Temporal Intelligence Engine
 *
 * Time-series analysis, trend prediction, seasonal pattern detection,
 * deadline risk assessment, velocity tracking, capacity forecasting,
 * timeline generation, peak-hour identification, and ETA estimation.
 *
 * Intelligence Type: Temporal
 * Tools: 9
 */

const velocityStore = new Map(); // project → [{sprint, completed, date}]
const timelineStore = new Map(); // project → [{milestone, date, status}]

// ── Helpers ─────────────────────────────────────────────────────────────

function linearRegression(points) {
  const n = points.length;
  if (n < 2) return { slope: 0, intercept: points[0]?.y || 0, r2: 0 };
  let sumX = 0, sumY = 0, sumXY = 0, sumX2 = 0, sumY2 = 0;
  for (const { x, y } of points) {
    sumX += x; sumY += y; sumXY += x * y; sumX2 += x * x; sumY2 += y * y;
  }
  const slope = (n * sumXY - sumX * sumY) / (n * sumX2 - sumX * sumX);
  const intercept = (sumY - slope * sumX) / n;
  const ssRes = points.reduce((s, p) => s + Math.pow(p.y - (slope * p.x + intercept), 2), 0);
  const ssTot = points.reduce((s, p) => s + Math.pow(p.y - sumY / n, 2), 0);
  const r2 = ssTot === 0 ? 1 : 1 - ssRes / ssTot;
  return { slope, intercept, r2: Math.round(r2 * 1000) / 1000 };
}

function movingAverage(data, window = 3) {
  return data.map((val, i, arr) => {
    const start = Math.max(0, i - window + 1);
    const slice = arr.slice(start, i + 1);
    return Math.round(slice.reduce((a, b) => a + b, 0) / slice.length * 100) / 100;
  });
}

function daysBetween(d1, d2) {
  return Math.round((new Date(d2) - new Date(d1)) / (1000 * 60 * 60 * 24));
}

/**
 * Analyze trends in time-series data
 */
export async function trendAnalyze(data, options = {}) {
  // data: [{date, value}] or [number]
  const points = Array.isArray(data[0]) || typeof data[0] === 'object'
    ? data.map((d, i) => ({ x: i, y: d.value || d }))
    : data.map((v, i) => ({ x: i, y: v }));

  const reg = linearRegression(points);
  const values = points.map(p => p.y);
  const avg = values.reduce((a, b) => a + b, 0) / values.length;
  const min = Math.min(...values);
  const max = Math.max(...values);
  const ma = movingAverage(values, options.window || 3);

  let direction = 'stable';
  if (reg.slope > avg * 0.01) direction = 'upward';
  else if (reg.slope < -avg * 0.01) direction = 'downward';

  return {
    trend: direction,
    slope: Math.round(reg.slope * 1000) / 1000,
    r_squared: reg.r2,
    statistics: { mean: Math.round(avg * 100) / 100, min, max, range: max - min, count: values.length },
    moving_average: ma.slice(-5),
    confidence: reg.r2 > 0.7 ? 'high' : reg.r2 > 0.4 ? 'medium' : 'low',
    summary: `${direction} trend with ${reg.r2 > 0.7 ? 'strong' : reg.r2 > 0.4 ? 'moderate' : 'weak'} correlation (R²=${reg.r2})`
  };
}

/**
 * Predict future values based on historical data
 */
export async function predict(data, periods = 5) {
  const points = data.map((v, i) => ({ x: i, y: typeof v === 'object' ? v.value : v }));
  const reg = linearRegression(points);

  const predictions = [];
  for (let i = 0; i < periods; i++) {
    const x = points.length + i;
    predictions.push({
      period: x,
      predicted: Math.round((reg.slope * x + reg.intercept) * 100) / 100,
      lower_bound: Math.round((reg.slope * x + reg.intercept - Math.abs(reg.slope) * 2) * 100) / 100,
      upper_bound: Math.round((reg.slope * x + reg.intercept + Math.abs(reg.slope) * 2) * 100) / 100
    });
  }

  return {
    model: 'linear_regression',
    r_squared: reg.r2,
    confidence: reg.r2 > 0.7 ? 'high' : reg.r2 > 0.4 ? 'medium' : 'low',
    predictions,
    warning: reg.r2 < 0.4 ? 'Low R² — predictions may be unreliable. Consider more data points.' : null
  };
}

/**
 * Detect seasonal patterns in data
 */
export async function seasonality(data, period = 7) {
  const values = data.map(d => typeof d === 'object' ? d.value : d);
  if (values.length < period * 2) return { error: `Need at least ${period * 2} data points for period ${period}` };

  // Calculate averages by position in cycle
  const buckets = Array.from({ length: period }, () => []);
  values.forEach((v, i) => buckets[i % period].push(v));
  const seasonalIndex = buckets.map(b => {
    const avg = b.reduce((a, c) => a + c, 0) / b.length;
    return Math.round(avg * 100) / 100;
  });
  const overallAvg = values.reduce((a, b) => a + b, 0) / values.length;
  const normalizedIndex = seasonalIndex.map(s => Math.round((s / overallAvg) * 100) / 100);
  const peakPosition = normalizedIndex.indexOf(Math.max(...normalizedIndex));
  const troughPosition = normalizedIndex.indexOf(Math.min(...normalizedIndex));

  return {
    period,
    seasonal_index: seasonalIndex,
    normalized: normalizedIndex,
    peak: { position: peakPosition, label: `Position ${peakPosition}`, strength: normalizedIndex[peakPosition] },
    trough: { position: troughPosition, label: `Position ${troughPosition}`, strength: normalizedIndex[troughPosition] },
    seasonality_strength: Math.round((Math.max(...normalizedIndex) - Math.min(...normalizedIndex)) * 100) / 100,
    has_pattern: Math.max(...normalizedIndex) - Math.min(...normalizedIndex) > 0.2
  };
}

/**
 * Assess deadline risk based on velocity
 */
export async function deadlineRisk(project, deadline, totalTasks, completedTasks, sprintDays = 14) {
  const remaining = totalTasks - completedTasks;
  const daysLeft = daysBetween(new Date(), deadline);
  const history = velocityStore.get(project) || [];
  const avgVelocity = history.length > 0
    ? history.slice(-5).reduce((a, s) => a + s.completed, 0) / Math.min(5, history.length)
    : completedTasks || 1;

  const sprintsNeeded = remaining / avgVelocity;
  const daysNeeded = sprintsNeeded * sprintDays;
  const riskPct = Math.min(100, Math.max(0, Math.round((1 - daysLeft / Math.max(1, daysNeeded)) * 100)));

  let risk = 'low';
  if (riskPct > 70) risk = 'critical';
  else if (riskPct > 40) risk = 'high';
  else if (riskPct > 20) risk = 'medium';

  return {
    project, deadline,
    tasks: { total: totalTasks, completed: completedTasks, remaining },
    velocity: { avg_per_sprint: Math.round(avgVelocity * 10) / 10, sprints_needed: Math.round(sprintsNeeded * 10) / 10 },
    days_left: daysLeft,
    days_needed: Math.round(daysNeeded),
    risk_level: risk,
    risk_percentage: riskPct,
    on_track: daysNeeded <= daysLeft,
    recommendation: risk === 'critical' ? 'Reduce scope or extend deadline immediately'
      : risk === 'high' ? 'Consider cutting low-priority items or adding capacity'
      : risk === 'medium' ? 'Monitor closely — slight buffer but watch velocity'
      : 'On track — maintain current pace'
  };
}

/**
 * Calculate project velocity (tasks/sprint)
 */
export async function velocity(project, action, data = {}) {
  if (!velocityStore.has(project)) velocityStore.set(project, []);
  const history = velocityStore.get(project);

  if (action === 'record') {
    history.push({
      sprint: data.sprint || history.length + 1,
      completed: data.completed || 0,
      planned: data.planned || 0,
      date: new Date().toISOString()
    });
    return { recorded: true, sprint: history.length, velocity: data.completed };
  }

  if (action === 'report') {
    if (history.length === 0) return { project, sprints: 0, message: 'No velocity data yet. Use action "record" first.' };
    const velocities = history.map(s => s.completed);
    const avg = velocities.reduce((a, b) => a + b, 0) / velocities.length;
    const trend = velocities.length >= 3
      ? linearRegression(velocities.map((v, i) => ({ x: i, y: v })))
      : null;

    return {
      project,
      sprints: history.length,
      average_velocity: Math.round(avg * 10) / 10,
      last_3: velocities.slice(-3),
      trend: trend ? (trend.slope > 0.5 ? 'improving' : trend.slope < -0.5 ? 'declining' : 'stable') : 'insufficient data',
      accuracy: history.filter(s => s.planned > 0).length > 0
        ? Math.round(history.filter(s => s.planned > 0).reduce((a, s) => a + s.completed / s.planned, 0) / history.filter(s => s.planned > 0).length * 100) + '%'
        : 'N/A',
      history
    };
  }

  return { hint: 'Actions: record {completed, planned}, report' };
}

/**
 * Forecast capacity and resource needs
 */
export async function capacity(teamSize, hoursPerDay, sprintDays, overhead = 0.2) {
  const rawHours = teamSize * hoursPerDay * sprintDays;
  const netHours = rawHours * (1 - overhead);
  const focusHours = netHours * 0.7; // Deep work ratio

  return {
    team_size: teamSize,
    sprint_length: `${sprintDays} days`,
    raw_hours: rawHours,
    overhead_pct: `${Math.round(overhead * 100)}%`,
    net_hours: Math.round(netHours),
    focus_hours: Math.round(focusHours),
    story_points: {
      conservative: Math.round(focusHours / 6),    // 6 hrs/point
      moderate: Math.round(focusHours / 4),         // 4 hrs/point
      aggressive: Math.round(focusHours / 2.5)      // 2.5 hrs/point
    },
    per_person: {
      net_hours: Math.round(netHours / teamSize),
      focus_hours: Math.round(focusHours / teamSize)
    },
    recommendations: [
      overhead > 0.3 && 'High overhead — reduce meetings/context switches',
      teamSize > 8 && 'Large team — consider splitting into squads',
      hoursPerDay < 6 && 'Low daily hours — verify team availability'
    ].filter(Boolean)
  };
}

/**
 * Generate project timeline with milestones
 */
export async function timeline(project, milestones) {
  // milestones: [{name, duration_days, dependencies?}]
  let currentDate = new Date();
  const result = milestones.map((m, i) => {
    const start = new Date(currentDate);
    const end = new Date(start);
    end.setDate(end.getDate() + (m.duration_days || 7));
    currentDate = end;
    return {
      id: i + 1,
      name: m.name,
      start: start.toISOString().split('T')[0],
      end: end.toISOString().split('T')[0],
      duration_days: m.duration_days || 7,
      status: 'planned',
      dependencies: m.dependencies || []
    };
  });

  const totalDays = milestones.reduce((a, m) => a + (m.duration_days || 7), 0);
  timelineStore.set(project, result);

  return {
    project,
    total_duration_days: totalDays,
    total_weeks: Math.ceil(totalDays / 7),
    start_date: result[0].start,
    end_date: result[result.length - 1].end,
    milestones: result,
    gantt_text: result.map(m => `${m.id}. [${m.start}] → [${m.end}] ${m.name} (${m.duration_days}d)`).join('\n')
  };
}

/**
 * Identify peak usage hours from timestamp data
 */
export async function peakHours(timestamps) {
  // timestamps: array of ISO strings or epoch ms
  const hours = Array(24).fill(0);
  const days = Array(7).fill(0);
  const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

  timestamps.forEach(ts => {
    const d = new Date(ts);
    hours[d.getHours()]++;
    days[d.getDay()]++;
  });

  const maxHour = hours.indexOf(Math.max(...hours));
  const minHour = hours.indexOf(Math.min(...hours));
  const maxDay = days.indexOf(Math.max(...days));

  return {
    total_events: timestamps.length,
    hourly_distribution: hours.map((count, h) => ({ hour: h, label: `${h.toString().padStart(2, '0')}:00`, count, pct: Math.round(count / timestamps.length * 100) })),
    daily_distribution: days.map((count, d) => ({ day: dayNames[d], count })),
    peak_hour: { hour: maxHour, label: `${maxHour.toString().padStart(2, '0')}:00`, count: hours[maxHour] },
    quiet_hour: { hour: minHour, label: `${minHour.toString().padStart(2, '0')}:00`, count: hours[minHour] },
    peak_day: { day: dayNames[maxDay], count: days[maxDay] },
    business_hours_pct: Math.round(hours.slice(9, 17).reduce((a, b) => a + b, 0) / timestamps.length * 100),
    recommendations: [
      `Schedule maintenance during ${minHour.toString().padStart(2, '0')}:00 (lowest traffic)`,
      `Scale up resources around ${maxHour.toString().padStart(2, '0')}:00 (peak traffic)`,
      hours.slice(0, 6).reduce((a, b) => a + b, 0) > timestamps.length * 0.2 && 'Significant nighttime traffic — consider 24/7 staffing'
    ].filter(Boolean)
  };
}

/**
 * Estimate time to completion for tasks
 */
export async function eta(tasks, velocityPerDay, startDate = null) {
  // tasks: [{name, estimate_hours, priority?, status?}] or just a number (total hours)
  const totalHours = typeof tasks === 'number'
    ? tasks
    : tasks.reduce((sum, t) => sum + (t.estimate_hours || 0), 0);

  const remaining = typeof tasks === 'number'
    ? tasks
    : tasks.filter(t => t.status !== 'done').reduce((sum, t) => sum + (t.estimate_hours || 0), 0);

  const daysNeeded = Math.ceil(remaining / velocityPerDay);
  const start = startDate ? new Date(startDate) : new Date();
  const endDate = new Date(start);
  // Skip weekends
  let workDays = 0;
  while (workDays < daysNeeded) {
    endDate.setDate(endDate.getDate() + 1);
    if (endDate.getDay() !== 0 && endDate.getDay() !== 6) workDays++;
  }

  return {
    total_hours: totalHours,
    remaining_hours: remaining,
    velocity_per_day: velocityPerDay,
    calendar_days: daysNeeded,
    work_days: daysNeeded,
    estimated_completion: endDate.toISOString().split('T')[0],
    start_date: start.toISOString().split('T')[0],
    buffer_recommendation: `${Math.ceil(daysNeeded * 0.2)} days (20% buffer)`,
    with_buffer: (() => { const d = new Date(endDate); d.setDate(d.getDate() + Math.ceil(daysNeeded * 0.2)); return d.toISOString().split('T')[0]; })(),
    breakdown: typeof tasks !== 'number' ? tasks.map(t => ({
      name: t.name,
      hours: t.estimate_hours,
      status: t.status || 'pending',
      days: Math.ceil((t.estimate_hours || 0) / velocityPerDay)
    })) : null
  };
}
